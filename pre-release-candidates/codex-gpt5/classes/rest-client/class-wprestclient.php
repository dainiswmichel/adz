<?php
/**
 * WordPress HTTP adapter for the existing network REST client.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
require_once __DIR__ . '/restclient-interface.php';

/**
 * REST client with authentication-scoped caching and explicit transport errors.
 */
class WPRestClient implements iRestClient {

	/**
	 * Authentication mode.
	 *
	 * @var string
	 */
	protected $authentication_scheme;
	/**
	 * Authentication parameters.
	 *
	 * @var array
	 */
	protected $authentication_parameters;
	/**
	 * Remote base URL.
	 *
	 * @var string
	 */
	protected $base_url;
	/**
	 * Whether a trusted caller permits self-signed certificates.
	 *
	 * @var bool
	 */
	protected $accept_self_signed;
	/**
	 * Additional request headers.
	 *
	 * @var array
	 */
	protected $headers = array();
	/**
	 * Response cache.
	 *
	 * @var WPCacheImpl
	 */
	protected $cache;


	/**
	 * Initialize the existing client contract.
	 *
	 * @param string $base_url Remote base URL.
	 * @param string $authentication_scheme Authentication mode.
	 * @param array  $authentication_parameters Authentication parameters.
	 * @param mixed  $accept_self_signed Accept self signed.
	 */
	public function __construct( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array(), $accept_self_signed = false ) {
		$this->accept_self_signed = $accept_self_signed;
		$this->update_connection( $base_url, $authentication_scheme, $authentication_parameters );
		require_once __DIR__ . '/cache-interface/class-wpcacheimpl.php';
		$this->cache = new WPCacheImpl( 'adzworld-rest' );
	}

	/**
	 * Update the remote endpoint and authentication.
	 *
	 * @param string $base_url Remote base URL.
	 * @param string $authentication_scheme Authentication mode.
	 * @param array  $authentication_parameters Authentication parameters.
	 */
	public function update_connection( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array() ) {
		$this->authentication_scheme     = $authentication_scheme;
		$this->authentication_parameters = $authentication_parameters;
		$this->base_url                  = $base_url;
	}

	/**
	 * Change the credentials used by subsequent requests.
	 *
	 * @param string $authentication_scheme Authentication mode.
	 * @param array  $authentication_parameters Authentication parameters.
	 */
	public function update_authentication( $authentication_scheme = 'Basic', $authentication_parameters = array() ) {
		$this->authentication_scheme     = $authentication_scheme;
		$this->authentication_parameters = $authentication_parameters;
	}

	/**
	 * Set additional request headers.
	 *
	 * @param array $headers Header names and values.
	 */
	public function custom_headers( $headers ) {
		$this->headers = $headers;
	}

	/**
	 * Replace a remote resource.
	 *
	 * @param string $path Resource path.
	 * @param mixed  $body Request body.
	 * @return array|WP_Error HTTP response or explicit failure.
	 */
	public function rest_put( $path, $body ) {

		$api_url = $this->authentication_query( rtrim( $this->base_url, '/' ) . '/' . ltrim( $path, '/' ) );
		$headers = $this->authentication_headers( 'PUT', $api_url );
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$req_args = array(
			'method'      => 'PUT',
			'body'        => $body,
			'headers'     => $headers,
			'sslverify'   => ! $this->accept_self_signed,
			'redirection' => 0,
		);

		$req_args = apply_filters( 'rest_put_args', $req_args );

		// A mutation invalidates cached reads even if the remote result is uncertain.
		$this->cache->clear_cache();
		$result = wp_remote_request( $api_url, $req_args );

		return $result;
	}

	/**
	 * Delete a remote resource.
	 *
	 * @param string $path Resource path.
	 * @param mixed  $body Request body.
	 * @return array|WP_Error HTTP response or explicit failure.
	 */
	public function rest_delete( $path, $body = array() ) {
		$api_url = $this->authentication_query( rtrim( $this->base_url, '/' ) . '/' . ltrim( $path, '/' ) );
		$headers = $this->authentication_headers( 'DELETE', $api_url );
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$req_args = array(
			'method'      => 'DELETE',
			'body'        => $body,
			'headers'     => $headers,
			'sslverify'   => ! $this->accept_self_signed,
			'redirection' => 0,
		);

		$req_args = apply_filters( 'rest_delete_args', $req_args );
		$this->cache->clear_cache();
		$result = wp_remote_request( $api_url, $req_args );

		return $result;
	}

	/**
	 * Create or update a remote resource.
	 *
	 * @param string $path Resource path.
	 * @param mixed  $body Request body.
	 * @return array|WP_Error HTTP response or explicit failure.
	 */
	public function rest_post( $path, $body ) {
		$api_url = $this->authentication_query( rtrim( $this->base_url, '/' ) . '/' . ltrim( $path, '/' ) );
		$headers = $this->authentication_headers( 'POST', $api_url );
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$args = array(
			'headers'     => $headers,
			'body'        => $body,
			'sslverify'   => ! $this->accept_self_signed,
			'redirection' => 0,

		);

		$args = apply_filters( 'rest_post_args', $args );

		$this->cache->clear_cache();
		$results = wp_remote_post( $api_url, $args );

		return $results;
	}

	/**
	 * Fetch a resource without sharing responses between credential sets.
	 *
	 * @param string $path Resource path.
	 * @param array  $params Query parameters.
	 * @return array|WP_Error HTTP response or explicit failure.
	 */
	public function rest_get( $path, $params ) {

		$api_url = rtrim( $this->base_url, '/' ) . '/' . ltrim( $path, '/' );
		if ( ! empty( $params ) ) {
			$api_url = add_query_arg( urlencode_deep( $params ), $api_url );
		}
		$api_url = $this->authentication_query( $api_url );
		$headers = $this->authentication_headers( 'GET', $api_url );
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}
		$args         = apply_filters(
			'rest_get_args',
			array(
				'headers'     => $headers,
				'sslverify'   => ! $this->accept_self_signed,
				'redirection' => 0,
			)
		);
		$cache_key    = hash( 'sha256', wp_json_encode( array( $api_url, $args, $this->authentication_scheme, $this->authentication_parameters ) ) );
		$cached_value = $this->cache->get( $cache_key );
		if ( false !== $cached_value ) {
			return $cached_value;
		}
		$results = wp_remote_get( $api_url, $args );

		if ( ! is_wp_error( $results ) && 200 === wp_remote_retrieve_response_code( $results ) ) {
			$this->cache->set( $cache_key, $results, 300 );
		}

		return $results;
	}

	/**
	 * Append configured query authentication without losing existing query fields.
	 *
	 * @param string $url Request URL.
	 * @return string Authenticated URL.
	 */
	protected function authentication_query( $url ) {
		if ( is_array( $this->authentication_parameters ) && is_string( $this->authentication_scheme ) && 'query' === strtolower( $this->authentication_scheme ) ) {
			$url = add_query_arg( urlencode_deep( $this->authentication_parameters ), $url );
		}
		return $url;
	}

	/**
	 * Build authentication headers or report invalid credentials.
	 *
	 * @param string $method HTTP method.
	 * @param string $url Actual request URL.
	 * @return array|WP_Error Headers or explicit configuration failure.
	 */
	protected function authentication_headers( $method, $url ) {
		if ( ! is_string( $this->authentication_scheme ) || ! is_array( $this->authentication_parameters ) || ! is_array( $this->headers ) ) {
			return new WP_Error( 'adzworld_rest_configuration', __( 'REST authentication and headers have an invalid configuration.', 'adz-world' ) );
		}
		$headers = array();
		switch ( strtolower( $this->authentication_scheme ) ) {
			case 'basic':
				if ( ! isset( $this->authentication_parameters['username'], $this->authentication_parameters['password'] )
					|| ! is_string( $this->authentication_parameters['username'] ) || ! is_string( $this->authentication_parameters['password'] ) ) {
					return new WP_Error( 'adzworld_rest_credentials', __( 'Basic authentication requires a username and password.', 'adz-world' ) );
				}
				$username                 = $this->authentication_parameters['username'];
				$password                 = $this->authentication_parameters['password'];
				$headers['Authorization'] = 'Basic ' . base64_encode( "$username:$password" );
				break;
			case 'header':
				$headers = $this->authentication_parameters;
				break;
			case 'digest':
				$headers = $this->digest_authenticate( $method, $url );
				if ( is_wp_error( $headers ) ) {
					return $headers;
				}
				break;
			case 'none':
			case 'query':
				break;
			default:
				return new WP_Error( 'adzworld_rest_authentication', __( 'The WordPress REST client does not support that authentication mode.', 'adz-world' ) );
		}

		$headers = array_merge( $headers, $this->headers );
		foreach ( $headers as $key => $value ) {
			if ( ! is_string( $key ) || ! preg_match( '/^[!#$%&\'*+.^_`|~0-9a-z-]+$/iD', $key )
				|| ( ! is_string( $value ) && ! is_int( $value ) ) || preg_match( '/[\r\n]/', (string) $value ) ) {
				return new WP_Error( 'adzworld_rest_header', __( 'A REST request header is invalid.', 'adz-world' ) );
			}
		}

		return $headers;
	}

	/**
	 * Respond to an HTTP Digest challenge using the actual resource URI.
	 *
	 * Supports the existing MD5 contract and SHA-256, with optional auth qop.
	 *
	 * @param string $method HTTP method.
	 * @param string $url Actual request URL.
	 * @return array|WP_Error Authentication header or explicit challenge failure.
	 */
	protected function digest_authenticate( $method, $url ) {
		if ( isset( $this->authentication_parameters['digest_header'] ) && is_string( $this->authentication_parameters['digest_header'] ) && '' !== trim( $this->authentication_parameters['digest_header'] ) ) {
			return array( 'Authorization' => 'Digest ' . $this->authentication_parameters['digest_header'] );
		}
		$parameters = isset( $this->authentication_parameters['digest_parameters'] ) ? $this->authentication_parameters['digest_parameters'] : array();
		if ( ! is_array( $parameters ) || ! isset( $parameters['username'], $parameters['password'] )
			|| ! is_string( $parameters['username'] ) || ! is_string( $parameters['password'] ) ) {
			return new WP_Error( 'adzworld_digest_credentials', __( 'Digest authentication requires a username and password.', 'adz-world' ) );
		}
		$response = wp_remote_request(
			$url,
			apply_filters(
				'rest_authenticate_args',
				array(
					'method'      => $method,
					'sslverify'   => ! $this->accept_self_signed,
					'redirection' => 0,
				)
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$challenge = wp_remote_retrieve_header( $response, 'www-authenticate' );
		if ( 401 !== wp_remote_retrieve_response_code( $response ) || ! is_string( $challenge )
			|| ! preg_match( '/^Digest\s+/i', $challenge ) ) {
			return new WP_Error( 'adzworld_digest_challenge', __( 'The service did not return an HTTP Digest challenge.', 'adz-world' ) );
		}
		preg_match_all( '/([a-z0-9_-]+)\s*=\s*(?:"((?:\\\\.|[^"\\\\])*)"|([^,\s]+))/i', substr( $challenge, 7 ), $matches, PREG_SET_ORDER );
		$pieces = array();
		foreach ( $matches as $match ) {
			$pieces[ strtolower( $match[1] ) ] = isset( $match[3] ) && '' !== $match[3] ? $match[3] : preg_replace( '/\\\\(.)/s', '$1', $match[2] );
		}
		$algorithm = isset( $pieces['algorithm'] ) ? strtolower( $pieces['algorithm'] ) : 'md5';
		$hash_type = str_replace( '-sess', '', $algorithm );
		if ( ! isset( $pieces['realm'], $pieces['nonce'] ) || '' === $pieces['nonce']
			|| ! in_array( $algorithm, array( 'md5', 'md5-sess', 'sha-256', 'sha-256-sess' ), true ) ) {
			return new WP_Error( 'adzworld_digest_algorithm', __( 'The HTTP Digest challenge is incomplete or uses an unsupported algorithm.', 'adz-world' ) );
		}
		$hash_type = 'sha-256' === $hash_type ? 'sha256' : $hash_type;
		$qop       = '';
		if ( isset( $pieces['qop'] ) ) {
			if ( ! in_array( 'auth', array_map( 'trim', explode( ',', $pieces['qop'] ) ), true ) ) {
				return new WP_Error( 'adzworld_digest_qop', __( 'The service does not offer the supported HTTP Digest auth quality of protection.', 'adz-world' ) );
			}
			$qop = 'auth';
		}
		$uri   = wp_parse_url( $url, PHP_URL_PATH );
		$uri   = $uri ? $uri : '/';
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		if ( is_string( $query ) && '' !== $query ) {
			$uri .= '?' . $query;
		}
		$cnonce = wp_generate_password( 32, false );
		$nc     = '00000001';
		$a1     = hash( $hash_type, $parameters['username'] . ':' . $pieces['realm'] . ':' . $parameters['password'] );
		if ( '-sess' === substr( $algorithm, -5 ) ) {
			$a1 = hash( $hash_type, $a1 . ':' . $pieces['nonce'] . ':' . $cnonce );
		}
		$a2     = hash( $hash_type, $method . ':' . $uri );
		$digest = hash( $hash_type, $a1 . ':' . $pieces['nonce'] . ':' . ( $qop ? $nc . ':' . $cnonce . ':' . $qop . ':' : '' ) . $a2 );
		$values = array(
			'username' => $parameters['username'],
			'realm'    => $pieces['realm'],
			'nonce'    => $pieces['nonce'],
			'uri'      => $uri,
			'response' => $digest,
		);
		if ( isset( $pieces['opaque'] ) ) {
			$values['opaque'] = $pieces['opaque'];
		}
		if ( $qop || '-sess' === substr( $algorithm, -5 ) ) {
			$values['cnonce'] = $cnonce;
		}
		$parts = array();
		foreach ( $values as $key => $value ) {
			$parts[] = $key . '="' . addcslashes( $value, '\\"' ) . '"';
		}
		$parts[] = 'algorithm=' . strtoupper( $algorithm );
		if ( $qop ) {
			$parts[] = 'qop=auth';
			$parts[] = 'nc=' . $nc;
		}
		return array( 'Authorization' => 'Digest ' . implode( ', ', $parts ) );
	}
}
