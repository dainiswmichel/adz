<?php
/**
 * Exercise the real options.php administrator-save flow on the local test site.
 *
 * Run while the local WordPress HTTP server is available.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}
require __DIR__ . '/regression.php';

$adzworld_url = home_url( '/wp-admin/options.php' );
if ( '127.0.0.1' !== wp_parse_url( $adzworld_url, PHP_URL_HOST ) ) {
	WP_CLI::error( 'This contract requires the explicitly local test site.' );
}
$adzworld_original_options = get_option( 'adz_ad_options', null );
$adzworld_original_user    = get_current_user_id();
$adzworld_token            = '';
$adzworld_manager          = null;
$adzworld_http_guard       = static function ( $preempt, $args, $url ) {
	if ( '127.0.0.1' !== wp_parse_url( $url, PHP_URL_HOST ) ) {
		throw new RuntimeException( 'Administrator form contract attempted an unexpected remote host.' );
	}
	return $preempt;
};
add_filter( 'pre_http_request', $adzworld_http_guard, 10, 3 );
try {
	$adzworld_admins = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
		)
	);
	adzworld_test_assert( ! empty( $adzworld_admins ), 'An administrator is available for the actual HTTP form save' );
	wp_set_current_user( $adzworld_admins[0]->ID );
	$adzworld_expiration = time() + 300;
	$adzworld_manager    = WP_Session_Tokens::get_instance( get_current_user_id() );
	$adzworld_token      = $adzworld_manager->create( $adzworld_expiration );
	$adzworld_cookies    = array(
		AUTH_COOKIE      => wp_generate_auth_cookie( get_current_user_id(), $adzworld_expiration, 'auth', $adzworld_token ),
		LOGGED_IN_COOKIE => wp_generate_auth_cookie( get_current_user_id(), $adzworld_expiration, 'logged_in', $adzworld_token ),
	);
	// Bind the form nonce to the disposable HTTP session, not WP-CLI's session.
	$adzworld_old_cookie         = isset( $_COOKIE[ LOGGED_IN_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) : null;
	$_COOKIE[ LOGGED_IN_COOKIE ] = $adzworld_cookies[ LOGGED_IN_COOKIE ];
	$adzworld_nonce              = wp_create_nonce( 'adz_ad_options-options' );
	$adzworld_body               = array(
		'option_page'      => 'adz_ad_options',
		'action'           => 'update',
		'_wpnonce'         => $adzworld_nonce,
		'_wp_http_referer' => '/wp-admin/edit.php?post_type=adz_ad&page=adz-settings&tab=tos',
		'adz_ad_options'   => array(
			'connect_to_network' => '0',
			'referral_code'      => "<b>Captain's HTTP form</b>",
			'type_of_adz'        => '',
		),
	);
	$adzworld_response           = wp_remote_post(
		$adzworld_url,
		array(
			'cookies'     => $adzworld_cookies,
			'body'        => $adzworld_body,
			'redirection' => 0,
		)
	);
	adzworld_test_assert( ! is_wp_error( $adzworld_response ) && 302 === wp_remote_retrieve_response_code( $adzworld_response ), 'A real administrator form POST returns the normal settings redirect' );
	wp_cache_delete( 'adz_ad_options', 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	$adzworld_saved = get_option( 'adz_ad_options' );
	adzworld_test_assert( "Captain's HTTP form" === $adzworld_saved['referral_code'], 'The actual options.php save sanitizes text and preserves apostrophes' );
	adzworld_test_assert( array() === $adzworld_saved['type_of_adz'], 'A cleared multiple selection is actually persisted as an empty selection' );
	$adzworld_export_url = add_query_arg(
		array(
			'action' => 'eof_download_options-adz_ad_options',
			'nonce'  => wp_create_nonce( 'eof-export' ),
		),
		admin_url( 'admin-ajax.php' )
	);
	$adzworld_export     = wp_remote_get(
		$adzworld_export_url,
		array(
			'cookies'     => $adzworld_cookies,
			'redirection' => 0,
		)
	);
	$adzworld_backup     = json_decode( wp_remote_retrieve_body( $adzworld_export ), true );
	adzworld_test_assert( 200 === wp_remote_retrieve_response_code( $adzworld_export ) && is_array( $adzworld_backup ) && '1' === $adzworld_backup['eof-backup'] && "Captain's HTTP form" === $adzworld_backup['referral_code'], 'The actual authenticated export returns valid JSON and the current saved settings (HTTP ' . wp_remote_retrieve_response_code( $adzworld_export ) . ', JSON ' . json_last_error_msg() . ')' );
	$adzworld_anonymous_export = wp_remote_get( $adzworld_export_url, array( 'redirection' => 0 ) );
	adzworld_test_assert( 400 === wp_remote_retrieve_response_code( $adzworld_anonymous_export ) && in_array( trim( wp_remote_retrieve_body( $adzworld_anonymous_export ) ), array( '', '0' ), true ), 'The actual anonymous export action is not registered and does not expose the backup' );
	$adzworld_body['_wpnonce']                        = 'invalid-fixture';
	$adzworld_body['adz_ad_options']['referral_code'] = 'must-not-save';
	$adzworld_response                                = wp_remote_post(
		$adzworld_url,
		array(
			'cookies'     => $adzworld_cookies,
			'body'        => $adzworld_body,
			'redirection' => 0,
		)
	);
	wp_cache_delete( 'adz_ad_options', 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	adzworld_test_assert( 403 === wp_remote_retrieve_response_code( $adzworld_response ) && get_option( 'adz_ad_options' ) === $adzworld_saved, 'A real administrator POST with the wrong nonce is rejected without changing saved settings' );
	$adzworld_body['_wpnonce'] = $adzworld_nonce;
	$adzworld_response         = wp_remote_post(
		$adzworld_url,
		array(
			'body'        => $adzworld_body,
			'redirection' => 0,
		)
	);
	wp_cache_delete( 'adz_ad_options', 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	adzworld_test_assert( 302 === wp_remote_retrieve_response_code( $adzworld_response ) && get_option( 'adz_ad_options' ) === $adzworld_saved, 'An unauthenticated real settings POST redirects to login without changing saved settings' );
	$adzworld_settings = null;
	foreach ( $GLOBALS['wp_filter']['admin_init']->callbacks as $adzworld_callbacks ) {
		foreach ( $adzworld_callbacks as $adzworld_callback ) {
			if ( is_array( $adzworld_callback['function'] ) && $adzworld_callback['function'][0] instanceof EOF_Settings ) {
				$adzworld_settings = $adzworld_callback['function'][0];
				break 2;
			}
		}
	}
	adzworld_test_assert( $adzworld_settings instanceof EOF_Settings, 'The actual settings import controller is available' );
	$adzworld_backup['referral_code'] = "<b>Captain's imported backup</b>";
	adzworld_test_assert( true === $adzworld_settings->parent->eof_port->import_data( $adzworld_backup ) && "Captain's imported backup" === get_option( 'adz_ad_options' )['referral_code'], 'An exported backup can be imported with the same field sanitization and without storing its backup marker' );
	adzworld_test_assert( ! array_key_exists( 'eof-backup', get_option( 'adz_ad_options' ) ), 'The import marker is metadata, not a saved plugin option' );
	$adzworld_before_import = get_option( 'adz_ad_options' );
	adzworld_test_assert(
		is_wp_error(
			$adzworld_settings->parent->eof_port->import_data(
				array(
					'eof-backup'    => '1',
					'unknown-field' => 'must-not-save',
				)
			)
		) && get_option( 'adz_ad_options' ) === $adzworld_before_import,
		'Invalid imported settings are rejected without replacing existing options'
	);
	wp_set_current_user( 0 );
	adzworld_test_assert( is_wp_error( $adzworld_settings->parent->eof_port->import_data( $adzworld_backup ) ), 'A settings import independently requires administrator authority' );
} finally {
	if ( null === $adzworld_original_options ) {
		delete_option( 'adz_ad_options' );
	} else {
		update_option( 'adz_ad_options', $adzworld_original_options );
	}
	if ( $adzworld_manager && $adzworld_token ) {
		$adzworld_manager->destroy( $adzworld_token );
	}
	if ( isset( $adzworld_old_cookie ) ) {
		$_COOKIE[ LOGGED_IN_COOKIE ] = $adzworld_old_cookie;
	} else {
		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
	}
	wp_set_current_user( $adzworld_original_user );
	remove_filter( 'pre_http_request', $adzworld_http_guard, 10 );
}
WP_CLI::success( 'Real WordPress settings HTTP contracts passed; original options and sessions restored.' );
