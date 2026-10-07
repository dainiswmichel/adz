<?php
/**
 * Provides "Session Support" for wordpress.	In normal PHP, this is handled with
 * native session variables.
 */

function start_adz_session() {
	add_option( 'adz_session_option', array() );
}
add_action( 'init', 'start_adz_session' );

function adz_end_session() {
	 delete_option( 'adz_session_option' );
}

add_action( 'wp_logout', 'adz_end_session' );
add_action( 'wp_login', 'adz_end_session' );

function update_adz_session( $key, $value ) {
	$session_array = get_option( 'adz_session_option', array() );
	$session_array[ $key ] = $value;
	update_option( 'adz_session_option', $session_array );
}

function get_adz_session( $key, $default_value = false ) {
	$session_array = get_option( 'adz_session_option', array() );
	return isset( $session_array[ $key ] ) ? $session_array[ $key ] : $default_value;
}