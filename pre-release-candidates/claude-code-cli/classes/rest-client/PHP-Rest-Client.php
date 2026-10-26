<?php

require_once( 'RestClient.interface.php' );

/**
 * The PHPRestClient class uses CURL to implement the iRestCLient interface.
 */
class PHPRestClient implements iRestClient {
	protected $authentication_scheme;
	protected $authentication_parameters;
	protected $base_url;
	protected $accept_self_signed;
	protected $custom_headers;

	/**
   * [__construct description]
   * @param [type] $base_url                  [description]
   * @param string $authentication_scheme     [description]
   * @param array  $authentication_parameters [description]
	 */
	public function __construct( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array(), $accept_self_signed = false ) {
		$this->auhtentication_scheme = $authentication_scheme;
		$this->authentication_parameters = $authentication_parameters;
		$this->base_url = $base_url;
		$this->accept_self_signed = $accept_self_signed;
		$this->custom_headers = null;
	}

	public function custom_headers( $headers ) {
		$this->custom_headers = $headers;
	}

	public function rest_get( $path, $params ) {
		
		$api_url = rtrim( $this->base_url,'/' ).'/'.ltrim( $path,'/' );
		$headers = false;
		if ( 'query' === strtolower( isset( $this->authentication_scheme ) ) ) {
			$params = array_merge( $params, $this->authentication_parameters );
		} else {
			$headers = $this->setupAutentication();
		}
		return $this->CURLHttp( 'GET', $api_url, $params, $headers );
	}

	public function rest_put( $path, $body ) {
		$api_url = rtrim( $this->base_url,'/' ).'/'.ltrim( $path,'/' );
		if ( 'query' === strtolower( isset( $this->authentication_scheme ) ) ) {
			$api_url .= '?'. http_build_query( $this->authentication_parameters );
		}

		return $this->CURLHttp( 'PUT', $api_url,$body );
	}
	public function rest_delete( $path, $body ) {
		$api_url = rtrim( $this->base_url,'/' ).'/'.ltrim( $path,'/' );
		if ( 'query' === strtolower( isset( $this->authentication_scheme ) ) ) {
			$api_url .= '?'. http_build_query( $this->authentication_parameters );
		}
		return $this->CURLHttp( 'DELETE', $api_url, $body );
	}
	public function rest_post( $path, $body ) {
		$api_url = rtrim( $this->base_url,'/' ).'/'.ltrim( $path,'/' );
		if ( 'query' === strtolower( isset( $this->authentication_scheme ) ) ) {
			$api_url .= '?'. http_build_query( $this->authentication_parameters );
		}
		return $this->CURLHttp( 'POST', $api_url, $body );
	}

	protected function CURLHttp( $method, $url, $data = false, $headers = false, $authenticate = true ) {
		$curl = curl_init();

		switch ( $method ) {
			case 'POST':
				curl_setopt( $curl, CURLOPT_POST, 1 );
				if ( $data ) {
					curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
				}
			break;
			case 'PUT':
				// Credit: http://www.lornajane.net/posts/2009/putting-data-fields-with-php-curl
				curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
				if ( $data ) {
					curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query( $data ) );
				}
			break;
			case 'DELETE':
				curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'DELETE' );
				if ( $data ) { // http://stackoverflow.com/questions/299628/is-an-entity-body-allowed-for-an-http-delete-request
					curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query( $data ) );
				}
			break;
			case 'GET':
				if ( $data ) {
					$url = sprintf( '%s?%s', $url, http_build_query( $data ) );
				}
		}

		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, ! $this->accept_self_signed );
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_VERBOSE, 1 );
		curl_setopt( $curl, CURLOPT_HEADER, 1 );

		if ( is_array( $this->custom_headers ) ) {
			if ( is_array( $headers ) ) {
				$headers = array_merge( $headers, $this->custom_headers );
			} else {
				$headers = $this->custom_headers;
			}
		}

		if ( $authenticate ) {
			$authentication_headers = $this->setupAutentication( $curl,$method );
			if ( is_array( $authentication_headers ) ) {
				if ( is_array( $headers ) ) {
					$headers = array_merge( $headers, $authentication_headers );
				} else {
					$headers = $authentication_headers;
				}
			}
		}
		if ( $headers ) {
			$this->setHeaders( $curl, $headers );
		}

		$info = curl_getinfo( $curl );
		$result = curl_exec( $curl );

		$result = $this->parse_curl_response( $curl, $result );
		curl_close( $curl );

		return $result;
	}


	// Credit: http://stackoverflow.com/questions/10589889/returning-header-as-array-using-curl
	// Credit: http://stackoverflow.com/questions/9183178/php-curl-retrieving-response-headers-and-body-in-a-single-request

	protected function parse_curl_response( $ch, $response ) {
		$result = array();
		$headers = array();

		$result['code'] = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$header_text = substr( $response, 0, $header_size );
		$body = substr( $response, $header_size );

		// Credit: https://core.trac.wordpress.org/browser/tags/4.5.2/src/wp-includes/class-http.php#L533
		// Tolerate line terminator: CRLF = LF (RFC 2616 19.3).
		$header_text = str_replace( "\r\n", "\n", $header_text );
		/*
		* Unfold folded header fields. LWS = [CRLF] 1*( SP | HT ) <US-ASCII SP, space (32)>,
		* <US-ASCII HT, horizontal-tab (9)> (RFC 2616 2.2).
		*/
		$header_text = preg_replace( '/\n[ \t]/', ' ', $header_text );
		// Create the headers array.
		$header_lines = explode( "\n", $header_text );

		foreach ( $header_lines as $i => $line ) {
			if ( 0 === $i ) {
				continue;
			}
			else {
				list ( $key, $value ) = explode( ': ', $line );
				$headers[ $key ] = $value;
			}
		}

		$result['headers'] = $headers;
		$result['body'] = $body;

		return $result;
	}

	protected function setupAutentication( $curl, $method ) {
		$headers = false;
		switch ( strtolower( $this->auhtentication_scheme ) ) {
			case 'basic':
				$username = $this->authentication_parameters['username'];
				$password = $this->authentication_parameters['password'];
				$headers = array();
				$headers['Authorization'] = 'Basic '.base64_encode( "$username:$password" );
			break;
			case 'header':
				$headers = $this->authentication_parameters;
			break;
			case 'digest':
				$headers = $this->digest_authenticate( $method );
			break;
			case 'cert':
			curl_setopt( $curl, CURLOPT_SSLCERT, $this->authentication_parameters['cert_file'] );
			curl_setopt( $curl, CURLOPT_SSLCERTPASSWD, $this->authentication_parameters['cert_password'] );
			break;
		}
		return $headers;
	}

	protected function setHeaders( $curl, $key_value_header ) {
		$headers = array();
		if ( is_array( $key_value_header ) ) {
			foreach ( $key_value_header as $key => $value ) {
				$headers[] = $key . ' : ' . $value;
			}
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	}

	// Credit: https://gist.github.com/funkatron/949952
	// Credit: https://www.sitepoint.com/understanding-http-digest-access-authentication/
	// See also: https://tools.ietf.org/html/rfc2617#page-6
	protected function digest_authenticate( $method ) {
		// If already pre-built:
		if ( isset( $this->authentication_parameters['digest_header'] ) ) {
			$headers['Authorization'] = 'Digest ' . $this->authentication_parameters['digest_header'];
		} else if ( is_array( $this->authentication_parameters['digest_parameters'] ) ) {

			$uri = isset( $this->authentication_parameters['digest_parameters']['url'] ) ?
			$this->authentication_parameters['digest_parameters']['url'] : $this->base_url;

			$response = $this->CURLHttp( $method, $uri, false, false, false );

			if ( ! is_array( $response ) ) {
				return false;
			} else if ( $response['response']['code'] == 401 ) {
				$username = $this->authentication_parameters['digest_parameters']['username'];
				$username = $this->authentication_parameters['digest_parameters']['password'];
				$headers = $response['headers'];
				if ( isset($headers['WWW-Authenticate'] ) ) {
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

	public function update_connection( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array(), $accept_self_signed = false ) {
		$this->auhtentication_scheme = $authentication_scheme;
		$this->authentication_parameters = $authentication_parameters;
		$this->base_url = $base_url;
		$this->accept_self_signed = $accept_self_signed;
		$this->custom_headers = null;
	}

	public function update_authentication( $authentication_scheme = 'Basic', $authentication_parameters = array() ) {
		$this->auhtentication_scheme = $authentication_scheme;
		$this->authentication_parameters = $authentication_parameters;
	}
}
