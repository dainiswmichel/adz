<?php
require_once( 'RestClient.interface.php' );

/**
* WPRestClinent implements a REST client useing WordPress HTTP library
*/

class WPRestClient implements iRestClient{

	protected $authentication_scheme;
	protected $authentication_parameters;
	protected $base_url;
	protected $accept_self_signed;
	protected $headers;
	protected $cache;


	public function __construct( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array(), $accept_self_signed = false ) {
		$this->accept_self_signed = $accept_self_signed;
		$this->update_connection( $base_url, $authentication_scheme, $authentication_parameters );
		$this->cache = null;
		if ( function_exists( 'wp_cache_set' ) ) {
			require_once( __DIR__ . '/cache-interface/wp-cache-class.php' );
			$this->cache = new WPCacheImpl( 'aretex-forte-api-cache' );
		}
	}

	public function update_connection( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array() ) {
		$this->authentication_scheme = $authentication_scheme;
		$this->authentication_parameters = $authentication_parameters;
		$this->base_url = $base_url;
	}

	public function update_authentication( $authentication_scheme = 'Basic', $authentication_parameters = array() ) {
		$this->auhtentication_scheme = $authentication_scheme;
		$this->authentication_parameters = $authentication_parameters;
	}

	public function custom_headers( $headers ) {
		$this->headers = $headers;
	}

	public function rest_put( $path, $body ) {

		$api_url = rtrim( $this->base_url,'/' ).'/'.ltrim( $path,'/' );
		$headers = $this->autentication_headers( 'PUT' );

		$req_args = array(
			'method' => 'PUT',
			'body' => $body,
			'headers' => $headers,
			'sslverify' => ! $this->accept_self_signed,
		);

		$req_args = apply_filters( 'rest_put_args', $req_args );

		// make the remote request
		$result = wp_remote_request( $api_url , $req_args );

		return $result;
	}

	public function rest_delete( $path, $body=array() ) {
		$api_url = $this->authentication_query( rtrim( $this->base_url,'/' ).'/'.ltrim( $path,'/' ) );
		$headers = $this->authentication_headers( 'DELETE' );

		$req_args = array(
			'method' => 'DELETE',
			'body' => $body,
			'headers' => $headers,
			'sslverify' => ! $this->accept_self_signed,
		);

		$req_args = apply_filters( 'rest_delete_args', $req_args );
		// make the remote request
		$result = wp_remote_request( $api_url, $req_args );

		return $result;
	}

	public function rest_post( $path, $body ) {
		$api_url = $this->authentication_query( rtrim( $this->base_url,'/' ).'/'.ltrim( $path,'/' ) );
		$headers = $this->authentication_headers( 'POST' );

		$args = array(
			'headers' => $headers,
			'body' => $body,
			'sslverify' => ! $this->accept_self_signed,

		);

		$args = apply_filters( 'rest_post_args', $args );

		$results = wp_remote_post( $api_url, $args );

		return $results;
	}

	public  function rest_get( $path, $params ) {

		$api_url = rtrim( $this->base_url,'/' ).'/'.ltrim( $path,'/' );
		$cache_key = md5( $api_url . serialize( $params ) );
		$cached_value = $this->cache->get( $cache_key );
		if ( $cached_value ) {
	
			return $cached_value;

		}
		$headers = $this->authentication_headers( 'GET' );

		$args = array(
			'headers' => $headers,
		);

		$args = apply_filters( 'rest_get_args', $args );
		if ( ! empty( $params ) ) {
			$q = http_build_query( $params );
			$api_url = $api_url.'?'.$q;
		}

		$api_url = $this->authentication_query( $api_url );

		$results = wp_remote_get( $api_url,$args );

		if ( is_object( $this->cache ) && method_exists( $this->cache, 'set' ) ) {
			$this->cache->set( $cache_key, $results, 300 );
		}

		return $results;
	}

	protected function authentication_query( $url ) {
		if ( is_array( $this->authentication_parameters ) && 'query' == strtolower( $this->authentication_scheme ) ) {
			$query_string = http_build_query( $this->authentication_parameters );
			if ( strstr( $url,'?' ) ) {
				$url .= '&'.$query_string;
			} else {
				$url .= '?'.$query_string;
			}
		}
		return $url;
	}

	protected function authentication_headers( $method ) {
		$headers = array();
		switch ( strtolower( $this->authentication_scheme ) ) {
			case 'basic':
				$username = $this->authentication_parameters['username'];
				$password = $this->authentication_parameters['password'];
				$headers['Authorization'] = 'Basic '.base64_encode( "$username:$password" );
			break;
			case 'header':
				$headers = $this->authentication_parameters;
			break;
			case 'digest':
				$headers = $this->digest_authenticate( $method );
			break;
		}

		if ( is_array( $this->headers ) ) {
			$headers = array_merge( $headers, $this->headers );
		}

		return $headers;
	}

	// Credit: https://gist.github.com/funkatron/949952
	// Credit: https://www.sitepoint.com/understanding-http-digest-access-authentication/
	// See also: https://tools.ietf.org/html/rfc2617#page-6
	protected function digest_authenticate( $method ) {
		// If already pre-built:
		if ( isset( $this->authentication_parameters['digest_header'] ) ) {
			$headers['Authorization'] = 'Digest ' . $this->authentication_parameters['digest_header'];
		} else if ( is_array( $this->authentication_parameters['digest_parameters'] ) ) {
			$args = array();
			$uri = isset( $this->authentication_parameters['digest_parameters']['url'] ) ?
			$this->authentication_parameters['digest_parameters']['url'] : $this->base_url;

			$req_args = array(
				'method' => $method,
				'sslverify' => ! $this->accept_self_signed,
			);

			$req_args = apply_filters( 'rest_authenticate_args', $req_args );

			$response = wp_remote_request( $uri, $req_args );
			if ( ! is_array( $response ) ) {
				return false;
			} else if ( 401 == $response['response']['code'] ) {
				$username = $this->authentication_parameters['digest_parameters']['username'];
				$username = $this->authentication_parameters['digest_parameters']['password'];
				$headers = $response['headers'];
				if ( isset( $headers['WWW-Authenticate'] ) ) {
					$auth_resp_header = $headers['WWW-Authenticate'];
					$auth_resp_header = explode( ',', preg_replace( '/^Digest/i', '', $auth_resp_header ) );
					$auth_pieces = array();
					foreach ( $auth_resp_header as &$piece ) {
						$piece = trim( $piece );
						$piece = explode( '=', $piece );
						$auth_pieces[ $piece[0] ] = trim( $piece[1], '"' );
					}
					// build response digest
					$nc = str_pad( '1', 8, '0', STR_PAD_LEFT );
					$A1 = md5( "{$username}:{$auth_pieces['realm']}:{$password}" );
					$A2 = md5( "{$method}:{$uri}" );
					$cnonce = uniqid();
					$auth_pieces['response'] = md5( "{$A1}:{$auth_pieces['nonce']}:{$nc}:{$cnonce}:{$auth_pieces['qop']}:${A2}" );
					$digest_header = "Digest username=\"{$username}\", realm=\"{$auth_pieces['realm']}\", nonce=\"{$auth_pieces['nonce']}\", uri=\"{$uri}\", cnonce=\"{$cnonce}\", nc={$nc}, qop=\"{$auth_pieces['qop']}\", response=\"{$auth_pieces['response']}\", opaque=\"{$auth_pieces['opaque']}\", algorithm=\"{$auth_pieces['algorithm']}\"";
					$headers['Authorization'] = $digest_header;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}

		return $headers;
	}
}
