<?php
/**
 * Existing REST adapter contract.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Methods shared by the legacy PHP and WordPress HTTP adapters.
 */
interface IRestClient {

	/**
	 * Initialize the remote endpoint and authentication.
	 *
	 * @param string $base_url Remote base URL.
	 * @param string $authentication_scheme Authentication mode.
	 * @param array  $authentication_parameters Mode-specific credentials.
	 * @param bool   $accept_self_signed Whether to skip certificate verification.
	 */
	public function __construct( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array(), $accept_self_signed = false );
	/**
	 * Change the connection settings.
	 *
	 * @param string $base_url Remote base URL.
	 * @param string $authentication_scheme Authentication mode.
	 * @param array  $authentication_parameters Mode-specific credentials.
	 */
	public function update_connection( $base_url, $authentication_scheme = 'Basic', $authentication_parameters = array() );
	/**
	 * Change authentication without changing the endpoint.
	 *
	 * @param string $authentication_scheme Authentication mode.
	 * @param array  $authentication_parameters Mode-specific credentials.
	 */
	public function update_authentication( $authentication_scheme = 'Basic', $authentication_parameters = array() );
	/**
	 * Set additional request headers.
	 *
	 * @param array $headers Header names and values.
	 */
	public function custom_headers( $headers );
	/**
	 * Fetch a resource.
	 *
	 * @param string $path Resource path.
	 * @param array  $params Query parameters.
	 * @return array|WP_Error Response or failure.
	 */
	public function rest_get( $path, $params );
	/**
	 * Replace a resource.
	 *
	 * @param string $path Resource path.
	 * @param mixed  $body Request body.
	 * @return array|WP_Error Response or failure.
	 */
	public function rest_put( $path, $body );
	/**
	 * Delete a resource.
	 *
	 * @param string $path Resource path.
	 * @param mixed  $body Request body.
	 * @return array|WP_Error Response or failure.
	 */
	public function rest_delete( $path, $body = array() );
	/**
	 * Create or update a resource.
	 *
	 * @param string $path Resource path.
	 * @param mixed  $body Request body.
	 * @return array|WP_Error Response or failure.
	 */
	public function rest_post( $path, $body );
}
