<?php
/**
 * Legacy flat-response REST adapter backed by the shared WordPress HTTP client.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wprestclient.php';

/**
 * Preserve the original response shape and optional client-certificate mode.
 */
class PHPRestClient extends WPRestClient {

	/**
	 * Update the connection, retaining TLS policy unless explicitly provided.
	 *
	 * @param string $base_url Remote base URL.
	 * @param string $authentication_scheme Authentication mode.
	 * @param array  $authentication_parameters Mode-specific credentials.
	 * @param bool   $accept_self_signed Optional explicit TLS-verification override.
	 */
	public function update_connection( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array(), $accept_self_signed = false ) {
		if ( func_num_args() > 3 ) {
			$this->accept_self_signed = $accept_self_signed;
		}
		parent::update_connection( $base_url, $authentication_scheme, $authentication_parameters );
		$this->headers = array();
	}

	/**
	 * Fetch a resource using the legacy flat-response shape.
	 *
	 * @param string $path Resource path.
	 * @param array  $params Query parameters.
	 * @return array|WP_Error Flat response or explicit failure.
	 */
	public function rest_get( $path, $params ) {
		return $this->legacy_request( 'get', $path, $params );
	}

	/**
	 * Replace a resource using the legacy flat-response shape.
	 *
	 * @param string $path Resource path.
	 * @param mixed  $body Request body.
	 * @return array|WP_Error Flat response or explicit failure.
	 */
	public function rest_put( $path, $body ) {
		return $this->legacy_request( 'put', $path, $body );
	}

	/**
	 * Create a resource using the legacy flat-response shape.
	 *
	 * @param string $path Resource path.
	 * @param mixed  $body Request body.
	 * @return array|WP_Error Flat response or explicit failure.
	 */
	public function rest_post( $path, $body ) {
		return $this->legacy_request( 'post', $path, $body );
	}

	/**
	 * Delete a resource using the legacy flat-response shape.
	 *
	 * @param string $path Resource path.
	 * @param mixed  $body Request body.
	 * @return array|WP_Error Flat response or explicit failure.
	 */
	public function rest_delete( $path, $body = array() ) {
		return $this->legacy_request( 'delete', $path, $body );
	}

	/**
	 * Validate certificate authentication without treating it as a header.
	 *
	 * @param string $method HTTP method.
	 * @param string $url Actual request URL.
	 * @return array|WP_Error Request headers or configuration failure.
	 */
	protected function authentication_headers( $method, $url ) {
		if ( ! is_string( $this->authentication_scheme ) || 'cert' !== strtolower( $this->authentication_scheme ) ) {
			return parent::authentication_headers( $method, $url );
		}
		if ( ! isset( $this->authentication_parameters['cert_file'], $this->authentication_parameters['cert_password'] )
			|| ! is_string( $this->authentication_parameters['cert_file'] ) || ! is_readable( $this->authentication_parameters['cert_file'] )
			|| ! is_string( $this->authentication_parameters['cert_password'] ) || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			return new WP_Error( 'adzworld_certificate_configuration', __( 'Certificate authentication requires HTTPS, a readable PEM certificate and its password.', 'adz-world' ) );
		}
		$scheme                      = $this->authentication_scheme;
		$this->authentication_scheme = 'None';
		try {
			return parent::authentication_headers( $method, $url );
		} finally {
			$this->authentication_scheme = $scheme;
		}
	}

	/**
	 * Execute the shared transport and adapt only its response shape.
	 *
	 * Client certificates use WordPress's documented cURL hook, scoped to the
	 * exact request. The Requests transport is pinned so it cannot silently fall
	 * back to a transport that omits the configured certificate.
	 *
	 * @param string $method Lowercase HTTP method.
	 * @param string $path Resource path.
	 * @param mixed  $data Query fields or request body.
	 * @return array|WP_Error Flat response or explicit failure.
	 */
	protected function legacy_request( $method, $path, $data ) {
		$certificate_mode = is_string( $this->authentication_scheme ) && 'cert' === strtolower( $this->authentication_scheme );
		$target           = untrailingslashit( $this->base_url ) . '/' . ltrim( $path, '/' );
		if ( 'get' === $method && $data ) {
			$target = add_query_arg( urlencode_deep( $data ), $target );
		}
		if ( $certificate_mode ) {
			$headers = $this->authentication_headers( strtoupper( $method ), $target );
			if ( is_wp_error( $headers ) ) {
				return $headers;
			}
			if ( ! function_exists( 'curl_setopt_array' ) || ! class_exists( '\WpOrg\Requests\Transport\Curl' ) ) {
				return new WP_Error( 'adzworld_certificate_transport', __( 'Client-certificate authentication requires the WordPress Requests cURL transport.', 'adz-world' ) );
			}
			$parameters  = $this->authentication_parameters;
			$transport   = static function ( $url, $request_headers, $request_data, $type, &$options ) use ( $target ) {
				if ( $target === $url ) {
					$options['transport'] = new \WpOrg\Requests\Transport\Curl();
				}
			};
			$certificate = static function ( $handle, $args, $url ) use ( $target, $parameters ) {
				if ( $target === $url && ! curl_setopt_array(
					$handle,
					array(
						CURLOPT_SSLCERT       => $parameters['cert_file'],
						CURLOPT_SSLCERTPASSWD => $parameters['cert_password'],
					)
				) ) {
					throw new \WpOrg\Requests\Exception( 'Client certificate configuration failed.', 'adzworld_certificate' );
				}
			};
			add_action( 'requests-requests.before_request', $transport, 10, 5 );
			add_action( 'http_api_curl', $certificate, 10, 3 );
		}
		try {
			switch ( $method ) {
				case 'get':
					$response = parent::rest_get( $path, $data );
					break;
				case 'put':
					$response = parent::rest_put( $path, $data );
					break;
				case 'post':
					$response = parent::rest_post( $path, $data );
					break;
				case 'delete':
					$response = parent::rest_delete( $path, $data );
					break;
				default:
					return new WP_Error( 'adzworld_rest_method', __( 'The requested REST method is not supported.', 'adz-world' ) );
			}
		} finally {
			if ( $certificate_mode ) {
				remove_action( 'requests-requests.before_request', $transport, 10 );
				remove_action( 'http_api_curl', $certificate, 10 );
			}
		}
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$headers = wp_remote_retrieve_headers( $response );
		return array(
			'code'    => wp_remote_retrieve_response_code( $response ),
			'headers' => $headers instanceof Traversable ? iterator_to_array( $headers ) : $headers,
			'body'    => wp_remote_retrieve_body( $response ),
		);
	}
}
