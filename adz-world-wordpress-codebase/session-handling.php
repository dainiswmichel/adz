<?php
/**
 * Legacy plugin and settings-framework support.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Provides "Session Support" for WordPress.    In normal PHP, this is handled with
 * native session variables.
 */
function adzworld_start_session() {
	add_option( 'adz_session_option', array() );
}
add_action( 'init', 'adzworld_start_session' );

/**
 * Clear the legacy shared plugin cache.
 */
function adzworld_end_session() {
	delete_option( 'adz_session_option' );
}

add_action( 'wp_logout', 'adzworld_end_session' );
add_action( 'wp_login', 'adzworld_end_session' );

/**
 * Update an entry in the legacy shared plugin cache.
 *
 * @param string $key Storage key.
 * @param mixed  $value Current field value.
 */
function adzworld_update_session( $key, $value ) {
	$session_array         = get_option( 'adz_session_option', array() );
	$session_array[ $key ] = $value;
	update_option( 'adz_session_option', $session_array );
}

/**
 * Read an entry from the legacy shared plugin cache.
 *
 * @param string $key Storage key.
 * @param mixed  $default_value Value returned when the key is absent.
 */
function adzworld_get_session( $key, $default_value = false ) {
	$session_array = get_option( 'adz_session_option', array() );
	return isset( $session_array[ $key ] ) ? $session_array[ $key ] : $default_value;
}
