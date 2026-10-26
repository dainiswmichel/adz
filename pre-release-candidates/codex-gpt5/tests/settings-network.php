<?php
/**
 * Exercise real settings, local metadata and remote failure paths in WordPress.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}
require __DIR__ . '/regression.php';

$adzworld_original_registration = get_option( 'adz_publisher_options', null );
$adzworld_original_user         = get_current_user_id();
$adzworld_connected             = false;
$adzworld_requests              = array();
$adzworld_response              = array(
	'response' => array( 'code' => 503 ),
	'headers'  => array(),
	'body'     => '{"error":"disposable fixture"}',
);
$adzworld_endpoint              = static function () {
	return 'https://network-contract.invalid/';
};
$adzworld_options               = static function ( $preempt ) use ( &$adzworld_connected ) {
	if ( false !== $preempt ) {
		return $preempt;
	}
	$options                       = get_option( 'adzworld_settings_contract', array() );
	$options['connect_to_network'] = $adzworld_connected ? '1' : '0';
	return $options;
};
$adzworld_transport             = static function ( $preempt, $args, $url ) use ( &$adzworld_requests, &$adzworld_response ) {
	if ( 'network-contract.invalid' !== wp_parse_url( $url, PHP_URL_HOST ) ) {
		throw new RuntimeException( 'Settings contract attempted an unexpected remote host.' );
	}
	$adzworld_requests[] = array(
		'url'  => $url,
		'args' => $args,
	);
	return $adzworld_response;
};
add_filter( 'adz_ad_network_base_url', $adzworld_endpoint );
add_filter( 'pre_option_adz_ad_options', $adzworld_options );
add_filter( 'pre_http_request', $adzworld_transport, 10, 3 );
$adzworld_post_id = 0;
try {
	$adzworld_post_id = wp_insert_post(
		array(
			'post_type'   => 'adz_ad',
			'post_status' => 'publish',
			'post_title'  => 'Codex disposable settings contract',
		),
		true
	);
	adzworld_test_assert( ! is_wp_error( $adzworld_post_id ), 'A disposable local advertisement can be created with no connection' );
	Adz_NetworkAuthorization::check_registration();
	Adz_NetworkAuthorization::get_ad_types();
	Adz_NetworkAuthorization::update_registration( array( 'accept_tos_checkbox' => '1' ) );
	Adz_NetworkAuthorization::update_ad( $adzworld_post_id, get_post( $adzworld_post_id ) );
	Adz_NetworkAuthorization::delete_ad( $adzworld_post_id );
	Adz_NetworkAuthorization::save_premium_settings( array( 'adz_serving_from' => 'publisher' ) );
	adzworld_test_assert( 0 === count( $adzworld_requests ), 'Every network entry point makes zero requests when disconnected' );
	wp_set_current_user( 0 );
	adzworld_test_assert( is_wp_error( adzworld_save_ad_metadata( $adzworld_post_id ) ), 'Anonymous metadata writes are rejected' );
	$adzworld_admins = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
		)
	);
	adzworld_test_assert( ! empty( $adzworld_admins ), 'An administrator is available for editor contracts' );
	wp_set_current_user( $adzworld_admins[0]->ID );
	$_POST = array(
		'affiliation_network_name' => wp_slash( "Captain's <b>network</b>" ),
		'affiliation_network_url'  => 'https://example.invalid/?campaign=fixture',
		'affiliation_id'           => 'fixture-42',
	);
	adzworld_test_assert( is_wp_error( adzworld_save_ad_metadata( $adzworld_post_id ) ), 'An administrator without the editor nonce cannot save affiliate metadata' );
	$_POST['meta-box-nonce'] = wp_create_nonce( 'adz-cpt-ads.php' );
	wp_update_post(
		array(
			'ID'         => $adzworld_post_id,
			'post_title' => 'Codex authorized local settings contract',
		)
	);
	adzworld_test_assert( "Captain's network" === get_post_meta( $adzworld_post_id, 'affiliation_network_name', true ) && 'fixture-42' === get_post_meta( $adzworld_post_id, 'affiliation_id', true ), 'The real save hook preserves unslashed affiliate metadata while disconnected' );
	adzworld_test_assert( true === adzworld_save_ad_metadata( $adzworld_post_id ), 'An unchanged authorized metadata save succeeds' );
	$_POST              = array();
	$adzworld_connected = true;
	update_option(
		'adz_publisher_options',
		array(
			'adz_registered' => array(
				'ID'            => 42,
				'post_token'    => 'fixture-post-token',
				'network_token' => 'fixture-network-token',
			),
		)
	);
	update_post_meta( $adzworld_post_id, 'network_ad_id', 'fixture-remote-42' );
	$adzworld_before_registration = get_option( 'adz_publisher_options' );
	adzworld_test_assert( is_wp_error( Adz_NetworkAuthorization::update_registration( array( 'accept_tos_checkbox' => '1' ) ) ), 'Unsuccessful registration is an explicit error' );
	adzworld_test_assert( get_option( 'adz_publisher_options' ) === $adzworld_before_registration, 'Unsuccessful registration preserves the existing tokens' );
	adzworld_test_assert( is_wp_error( Adz_NetworkAuthorization::delete_ad( $adzworld_post_id ) ) && 'fixture-remote-42' === get_post_meta( $adzworld_post_id, 'network_ad_id', true ), 'Failed remote deletion retains its identifier for retry' );
	$adzworld_response = new WP_Error( 'fixture-offline', 'Disposable service outage' );
	adzworld_test_assert( is_wp_error( Adz_NetworkAuthorization::check_registration() ), 'Registration lookup propagates a transport error without dereferencing it' );
	adzworld_test_assert( is_wp_error( Adz_NetworkAuthorization::update_ad( $adzworld_post_id, get_post( $adzworld_post_id ) ) ), 'Advertisement sync propagates a transport error without reading POST fields' );
	$adzworld_response = array(
		'response' => array( 'code' => 200 ),
		'headers'  => array(),
		'body'     => 'not JSON',
	);
	adzworld_test_assert( is_wp_error( Adz_NetworkAuthorization::update_ad( $adzworld_post_id, get_post( $adzworld_post_id ) ) ), 'Invalid successful response JSON is rejected' );
	$adzworld_response['body'] = '{"ID":"fixture-remote-43"}';
	adzworld_test_assert( true === Adz_NetworkAuthorization::update_ad( $adzworld_post_id, get_post( $adzworld_post_id ) ) && 'fixture-remote-43' === get_post_meta( $adzworld_post_id, 'network_ad_id', true ), 'Valid sync updates the remote identifier and uses persisted affiliate metadata' );
	$adzworld_last_request = end( $adzworld_requests );
	adzworld_test_assert( 'fixture-42' === $adzworld_last_request['args']['body']['custom_field']['affiliation_id'], 'Synchronization reads the previously authorized affiliate values' );
	$adzworld_response['response']['code'] = 204;
	$adzworld_response['body']             = '';
	adzworld_test_assert( true === Adz_NetworkAuthorization::delete_ad( $adzworld_post_id ) && '' === get_post_meta( $adzworld_post_id, 'network_ad_id', true ), 'Confirmed remote deletion removes its local retry identifier' );
	$adzworld_settings = null;
	foreach ( $GLOBALS['wp_filter']['admin_init']->callbacks as $adzworld_callbacks ) {
		foreach ( $adzworld_callbacks as $adzworld_callback ) {
			if ( is_array( $adzworld_callback['function'] ) && $adzworld_callback['function'][0] instanceof EOF_Settings ) {
				$adzworld_settings = $adzworld_callback['function'][0];
				break 2;
			}
		}
	}
	adzworld_test_assert( $adzworld_settings instanceof EOF_Settings, 'The actual configured settings framework is available without rendering its fields' );
	$adzworld_clean = $adzworld_settings->sanitize_callback(
		array(
			'referral_code' => '<b>fixture-referral</b>',
			'paypal_email'  => 'fixture@example.invalid',
		)
	);
	adzworld_test_assert( 'fixture-referral' === $adzworld_clean['referral_code'] && 'fixture@example.invalid' === $adzworld_clean['paypal_email'], 'Settings sanitize without a submitted HTTP referrer or prior field rendering' );
	adzworld_test_assert( get_option( 'adz_ad_options' ) === $adzworld_settings->sanitize_callback( array( 'unregistered_setting' => 'reject' ) ), 'Unknown option keys cannot enter the settings collection' );
	adzworld_test_assert( get_option( 'adz_ad_options' ) === $adzworld_settings->sanitize_callback( array( 'referral_code' => array( 'invalid' ) ) ), 'Malformed scalar fields preserve the previous settings' );
	$adzworld_rows = $adzworld_settings->sanitize_callback(
		array(
			'ad_settings' => array(
				'field_count' => array( 'rotation_name' => 'hidden clone' ),
				0             => array(
					'rotation_name' => '<b>First actual row</b>',
					'rotation_id'   => 'adz_rotation_0',
				),
				1             => array(
					'rotation_name' => 'Second actual row',
					'rotation_id'   => 'adz_rotation_1',
				),
			),
		)
	);
	adzworld_test_assert( 2 === count( $adzworld_rows['ad_settings'] ) && 'First actual row' === $adzworld_rows['ad_settings'][0]['rotation_name'], 'Repeat fields remove only the hidden prototype and sanitize nested content without losing the first actual row' );
	adzworld_test_assert( get_option( 'adz_ad_options' ) === $adzworld_settings->sanitize_callback( array( 'ad_settings' => array( array( 'rotation_id' => 'siteurl' ) ) ) ), 'Repeat settings cannot store unrelated option names as rotation identifiers' );
	$_GET = array(
		'tab'         => 'tos',
		'action'      => 'reset',
		'reset_nonce' => wp_create_nonce( 'eof_reset' ),
	);
	wp_set_current_user( 0 );
	$adzworld_before_reset = get_option( 'adz_ad_options' );
	$adzworld_settings->reset_section_options();
	adzworld_test_assert( get_option( 'adz_ad_options' ) === $adzworld_before_reset, 'A settings reset independently requires the configured administrative capability' );
} finally {
	$adzworld_connected = false;
	$_POST              = array();
	if ( $adzworld_post_id && ! is_wp_error( $adzworld_post_id ) ) {
		wp_delete_post( $adzworld_post_id, true );
	}
	if ( null === $adzworld_original_registration ) {
		delete_option( 'adz_publisher_options' );
	} else {
		update_option( 'adz_publisher_options', $adzworld_original_registration );
	}
	$_POST = array();
	$_GET  = array();
	wp_set_current_user( $adzworld_original_user );
	remove_filter( 'pre_http_request', $adzworld_transport, 10 );
	remove_filter( 'adz_ad_network_base_url', $adzworld_endpoint );
	remove_filter( 'pre_option_adz_ad_options', $adzworld_options );
}
WP_CLI::success( 'Settings and synchronization contracts passed without remote traffic.' );
