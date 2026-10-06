<?php
/**
* REST Client Interface
*
*  PHP Applications often need to access external services via REST.
*  HTTP
* */

interface iRestClient {

	/**
	* [__construct Create a REST client ]
	* @param [string] $base_url                  [Base URL of the REST Server]
	* @param string $authentication_scheme     [Type of Authentiation:
	*                                          Basic: Standard HTTP Authentication
	*                                          Digest: Standard HTTP Digest Authentication
	*                                          Header: Hader Authentication
	*                                          Query: Authentication with query string parameters
	*                                          cert: Autenticate with a certificate file 	]
	* @param array  $authentication_parameters [Authentication Parameters depend on authentication scheme]
	* $authentication_scheme='Basic', $authentication_parameters['username'] = username;
	*                                 $authentication_parameters['password'] = password;
	*
	* $authentication_scheme='Digest', $authentication_parameters['digest_header'] = digest header;
	*                                  $authentication_parameters['digest_parameters']['username']
	*                                  $authentication_parameters['digest_parameters']['password']
	*                                  $authentication_parameters['digest_parameters']['url']
	*
	* $authentication_scheme='Header'  $authentication_parameters['header_key'] = value;
	* $authentication_scheme='Query' $authentication_parameters['query_key'] = value
	*
	* $autentication_scheme = 'cert' $authentication_parameters['cert_file'] = system path to cert file
	*                                $authentication_parameters['cert_password'] = password to access cert file
	*
	* @param bool $accept_self_signed - Set to true if you want to accept a self-signed certificate
	*
	*/
	public function __construct( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array(), $accept_self_signed = false );
	/**
	* update_connection Change the connection settings for the REST client
	* @param [string] $base_url                  [Base URL of the REST Server]
	* @param string $authentication_scheme     [Type of Authentiation:
	*                                          Basic: Standard HTTP Authentication
	*                                          Digest: Standard HTTP Digest Authentication
	*                                          Header: Hader Authentication
	*                                          Query: Authentication with query string parameters
	*                                          cert: Autenticate with a certificate file 	]
	* @param array  $authentication_parameters [Authentication Parameters depend on authentication scheme]
	* $authentication_scheme='Basic', $authentication_parameters['username'] = username;
	*                                 $authentication_parameters['password'] = password;
	*
	* $authentication_scheme='Digest', $authentication_parameters['digest_header'] = digest header;
	*                                  $authentication_parameters['digest_parameters']['username']
	*                                  $authentication_parameters['digest_parameters']['password']
	*                                  $authentication_parameters['digest_parameters']['url']
	*
	* $authentication_scheme='Header'  $authentication_parameters['header_key'] = value;
	* $authentication_scheme='Query' $authentication_parameters['query_key'] = value
	*
	* $autentication_scheme = 'cert' $authentication_parameters['cert_file'] = system path to cert file
	*                                $authentication_parameters['cert_password'] = password to access cert file
	*
	* @param bool $accept_self_signed - Set to true if you want to accept a self-signed certificate
	*
	*/
	public function update_connection( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array() );
	/**
	* update_connection Change the connection settings for the REST client
	* @param string $authentication_scheme     [Type of Authentiation:
	*                                          Basic: Standard HTTP Authentication
	*                                          Digest: Standard HTTP Digest Authentication
	*                                          Header: Hader Authentication
	*                                          Query: Authentication with query string parameters
	*                                          cert: Autenticate with a certificate file 	]
	* @param array  $authentication_parameters [Authentication Parameters depend on authentication scheme]
	* $authentication_scheme='Basic', $authentication_parameters['username'] = username;
	*                                 $authentication_parameters['password'] = password;
	*
	* $authentication_scheme='Digest', $authentication_parameters['digest_header'] = digest header;
	*                                  $authentication_parameters['digest_parameters']['username']
	*                                  $authentication_parameters['digest_parameters']['password']
	*                                  $authentication_parameters['digest_parameters']['url']
	*
	* $authentication_scheme='Header'  $authentication_parameters['header_key'] = value;
	* $authentication_scheme='Query' $authentication_parameters['query_key'] = value
	*
	* $autentication_scheme = 'cert' $authentication_parameters['cert_file'] = system path to cert file
	*                                $authentication_parameters['cert_password'] = password to access cert file
	*
	* @param bool $accept_self_signed - Set to true if you want to accept a self-signed certificate
	*
	*/
	public function update_authentication( $authentication_scheme = 'Basic', $authentication_parameters = array() );
	public function custom_headers( $headers );
	public function rest_get( $path, $params );
	public function rest_put( $path, $body );
	public function rest_delete( $path, $body = array());
	public function rest_post( $path, $body );
}
