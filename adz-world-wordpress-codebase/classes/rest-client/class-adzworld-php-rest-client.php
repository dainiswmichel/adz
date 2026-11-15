<?php
/**
 * Legacy flat-response REST adapter backed by the shared WordPress HTTP client.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-adzworld-wp-rest-client.php';

/**
 * Preserve the original response shape and optional client-certificate mode.
 */
class Adzworld_PHP_Rest_Client extends Adzworld_WP_Rest_Client {

	/**
	 * Update the connection, retaining TLS policy unless explicitly provided.
	 *
	 * @param string $base_url Remote base URL.
	 * @param string $authentication_scheme Authentication mode.
	 * @param array  $authentication_parameters Mode-specific credentials.
	 * @param bool   $accept_self_signed Deprecated compatibility argument; ignored.
	 */
	public function update_connection( $base_url, $authentication_scheme = 'None', $authentication_parameters = array(), $accept_self_signed = false ) {
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
	 * Execute the shared transport and adapt only its response shape.
	 *
	 * @param string $method Lowercase HTTP method.
	 * @param string $path Resource path.
	 * @param mixed  $data Query fields or request body.
	 * @return array|WP_Error Flat response or explicit failure.
	 */
	protected function legacy_request( $method, $path, $data ) {
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
