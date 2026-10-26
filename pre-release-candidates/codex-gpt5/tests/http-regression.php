<?php
/**
 * HTTP checks against this local test site with its server already running.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

require __DIR__ . '/regression.php';

adzworld_test_assert( '127.0.0.1' === wp_parse_url( home_url(), PHP_URL_HOST ), 'HTTP checks are confined to the local test WordPress' );
$probe         = 'adzworld_http_probe_' . wp_generate_password( 10, false );
$previous_pool = get_option( 'next_adz_pool' );
$local_ad      = wp_insert_post(
	array(
		'post_type'    => 'adz_ad',
		'post_status'  => 'publish',
		'post_title'   => 'Codex disposable local ad',
		'post_content' => '<p>adzworld-local-ad-regression-marker</p>',
	),
	true
);
adzworld_test_assert( ! is_wp_error( $local_ad ), 'Published local ad fixture is created' );
add_option( $probe, 'retained', '', false );
try {
	$response = wp_remote_post(
		home_url( '/' ),
		array( 'body' => array( 'adz_ad_options' => array( 'ad_settings' => array( array( 'rotation_id' => $probe ) ) ) ) )
	);
	adzworld_test_assert( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ), 'Unauthenticated homepage POST completes' );
	wp_cache_delete( $probe, 'options' );
	adzworld_test_assert( 'retained' === get_option( $probe ), 'Unauthenticated HTTP POST cannot delete arbitrary options' );
	foreach ( array( 'adz_reset_registration', 'get_template_settings' ) as $adzworld_test_action ) {
		$response = wp_remote_post( admin_url( 'admin-ajax.php' ), array( 'body' => array( 'action' => $adzworld_test_action ) ) );
		adzworld_test_assert( ! is_wp_error( $response ) && 400 === wp_remote_retrieve_response_code( $response ), 'Anonymous AJAX action is unregistered: ' . $adzworld_test_action );
	}
	adzworld_test_assert( ! adz_network_enabled(), 'Local serving test has no external service enabled' );
	$response = wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'body' => array(
				'action'                 => 'adz_get_advertise_content',
				'adz_nonce'              => wp_create_nonce( 'adzdotworld' ),
				'ad_visibility_interval' => 60,
				'ad_to_serve'            => $local_ad,
				'target_type'            => 'page',
				'target'                 => $local_ad,
				'rotations_id'           => 'adz_rotation_99999999999999999999',
			),
		)
	);
	adzworld_test_assert( ! is_wp_error( $response ) && 400 === wp_remote_retrieve_response_code( $response ), 'Public ad request rejects a syntactically valid but unconfigured rotation before rendering' );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	adzworld_test_assert( is_array( $data ) && false === $data['success'], 'Unconfigured rotation returns an explicit failure, not a successful ad response' );
	adzworld_test_assert( in_array( (string) $local_ad, explode( ',', adz_get_publisher_adz() ), true ), 'Local ad sequence uses the local post ID without network metadata' );
	wp_update_post(
		array(
			'ID'          => $local_ad,
			'post_status' => 'draft',
		)
	);
	adzworld_test_assert( is_wp_error( adzworld_local_ad_response( $local_ad ) ), 'Draft local ads are not publicly served' );
	wp_update_post(
		array(
			'ID'            => $local_ad,
			'post_status'   => 'publish',
			'post_password' => 'disposable-test-password',
		)
	);
	adzworld_test_assert( is_wp_error( adzworld_local_ad_response( $local_ad ) ), 'Password-protected local ads are not publicly served' );
	wp_update_post(
		array(
			'ID'            => $local_ad,
			'post_password' => '',
			'post_type'     => 'post',
		)
	);
	adzworld_test_assert( is_wp_error( adzworld_local_ad_response( $local_ad ) ), 'Ordinary posts cannot be served as local ads' );
	wp_update_post(
		array(
			'ID'        => $local_ad,
			'post_type' => 'adz_ad',
		)
	);
	$response = wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'body' => array(
				'action'                 => 'adz_get_advertise_content',
				'adz_nonce'              => wp_create_nonce( 'adzdotworld' ),
				'ad_visibility_interval' => 60,
				'ad_to_serve'            => $local_ad,
				'target'                 => $local_ad,
				'target_type'            => 'shortcode',
				'display_type'           => 'popup',
				'adz_template'           => 'template1.php',
				'sequence'               => (string) $local_ad,
				'repeat_times'           => 1,
			),
		)
	);
	$rendered = is_wp_error( $response ) ? '' : base64_decode( wp_remote_retrieve_body( $response ), true );
	adzworld_test_assert( is_string( $rendered ) && false !== strpos( $rendered, 'adzworld-local-ad-regression-marker' ), 'Published local ad renders over HTTP without a service registration' );
	$administrators = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
		)
	);
	$user_id        = $administrators[0]->ID;
	$expiry         = time() + 300;
	$token          = WP_Session_Tokens::get_instance( $user_id )->create( $expiry );
	$logged_in      = wp_generate_auth_cookie( $user_id, $expiry, 'logged_in', $token );
	$auth           = wp_generate_auth_cookie( $user_id, $expiry, 'auth', $token );
	wp_set_current_user( $user_id );
	$_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in;
	$headers                     = array( 'Cookie' => AUTH_COOKIE . '=' . $auth . '; ' . LOGGED_IN_COOKIE . '=' . $logged_in );
	$response                    = wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'headers' => $headers,
			'body'    => array(
				'action'            => 'get_template_settings',
				'selected_template' => 'template1.php',
				'nonce'             => wp_create_nonce( 'adzworld_template_settings' ),
			),
		)
	);
	$data                        = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
	adzworld_test_assert( is_array( $data ) && isset( $data['header_text'], $data['footer_style'] ), 'Authorized administrator can still retrieve template settings' );
	$response = wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'headers' => $headers,
			'body'    => array(
				'action'            => 'get_template_settings',
				'selected_template' => 'template1.php',
				'nonce'             => 'invalid',
			),
		)
	);
	adzworld_test_assert( ! is_wp_error( $response ) && 403 === wp_remote_retrieve_response_code( $response ), 'Administrator without valid nonce is rejected' );
	$response = wp_remote_get( admin_url( 'edit.php?post_type=adz_ad&page=adzworld-earnings' ), array( 'headers' => $headers ) );
	adzworld_test_assert(
		! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response )
		&& false !== strpos( wp_remote_retrieve_body( $response ), 'Not connected.' ),
		'Earnings dashboard renders an explicit unconnected state'
	);
} finally {
	delete_option( $probe );
	wp_delete_post( $local_ad, true );
	if ( false === $previous_pool ) {
		delete_option( 'next_adz_pool' );
	} else {
		update_option( 'next_adz_pool', $previous_pool );
	}
	if ( isset( $user_id, $token ) ) {
		WP_Session_Tokens::get_instance( $user_id )->destroy( $token );
	}
	unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
	wp_set_current_user( 0 );
}
WP_CLI::success( 'Local HTTP authorization and dashboard checks passed.' );
