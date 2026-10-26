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

/**
 * Sign an explicitly constructed public test request.
 *
 * @param array $body Request fields.
 * @return array Signed fields.
 */
function adzworld_test_signed_body( $body ) {
	$action = adzworld_ad_request_action( $body );
	adzworld_test_assert( ! is_wp_error( $action ), 'Test configuration can be signed' );
	$body['adzworld_context'] = wp_create_nonce( $action );
	return $body;
}

global $wpdb;

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
			'body' => adzworld_test_signed_body(
				array(
					'action'                 => 'adz_get_advertise_content',
					'adz_nonce'              => wp_create_nonce( 'adzdotworld' ),
					'ad_visibility_interval' => 60,
					'ad_to_serve'            => $local_ad,
					'target_type'            => 'page',
					'target'                 => $local_ad,
					'rotations_id'           => 'adz_rotation_99999999999999999999',
					'sequence'               => (string) $local_ad,
					'repeat_times'           => 1,
					'display_type'           => 'popup',
					'adz_template'           => 'template1.php',
				)
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
			'body' => adzworld_test_signed_body(
				array(
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
				)
			),
		)
	);
	$rendered = is_wp_error( $response ) ? '' : base64_decode( wp_remote_retrieve_body( $response ), true );
	adzworld_test_assert( is_string( $rendered ) && false !== strpos( $rendered, 'adzworld-local-ad-regression-marker' ), 'Published local ad renders over HTTP without a service registration' );
	$adzworld_valid_body = adzworld_test_signed_body(
		array(
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
		)
	);
	foreach ( array(
		'target'                 => $local_ad + 1,
		'target_type'            => 'page',
		'rotations_id'           => 'adz_rotation_99999999999999999999',
		'sequence'               => '999999',
		'repeat_times'           => 'infinite',
		'adz_template'           => '../adz-world.php',
		'ad_to_serve'            => array( $local_ad ),
		'ad_visibility_interval' => str_repeat( '1', 8193 ),
		'close_adz'              => 'true',
		'adzworld_context'       => '',
	) as $adzworld_field => $adzworld_tampered_value ) {
		$adzworld_body                    = $adzworld_valid_body;
		$adzworld_body[ $adzworld_field ] = $adzworld_tampered_value;
		$response                         = wp_remote_post( admin_url( 'admin-ajax.php' ), array( 'body' => $adzworld_body ) );
		adzworld_test_assert( ! is_wp_error( $response ) && 403 === wp_remote_retrieve_response_code( $response ), 'Configuration signature rejects a changed field: ' . $adzworld_field );
	}
	adzworld_test_assert( ! adz_get_view_row( '127.0.0.1', $local_ad, 'shortcode' ), 'Rejected close mutation did not create a view record' );
	$adzworld_preserved_rows = array();
	foreach ( array(
		array(
			'type'  => 'shortcode',
			'date'  => '2000-01-01',
			'count' => 7,
		),
		array(
			'type'  => 'page',
			'date'  => current_time( 'Y-m-d' ),
			'count' => 11,
		),
	) as $adzworld_other_view ) {
		$adzworld_inserted = $wpdb->insert(
			$wpdb->prefix . 'adz_views',
			array(
				'visitor_ip'      => '127.0.0.1',
				'targate_id'      => $local_ad,
				'target_type'     => $adzworld_other_view['type'],
				'number_of_times' => $adzworld_other_view['count'],
				'ad_date'         => $adzworld_other_view['date'],
				'updated'         => $adzworld_other_view['date'] . ' 00:00:00',
			)
		);
		adzworld_test_assert( 1 === $adzworld_inserted, 'An unrelated view-history fixture is created' );
		$adzworld_preserved_rows[ $wpdb->insert_id ] = $adzworld_other_view;
	}
	for ( $adzworld_close_count = 1; $adzworld_close_count <= 2; ++$adzworld_close_count ) {
		$response = wp_remote_post(
			admin_url( 'admin-ajax.php' ),
			array(
				'body' => adzworld_test_signed_body(
					array(
						'action'                 => 'adz_get_advertise_content',
						'adz_nonce'              => wp_create_nonce( 'adzdotworld' ),
						'ad_visibility_interval' => 60,
						'target'                 => $local_ad,
						'target_type'            => 'shortcode',
						'close_adz'              => 'true',
					)
				),
			)
		);
		adzworld_test_assert( ! is_wp_error( $response ) && 'close' === wp_remote_retrieve_body( $response ), 'Close action works without unused ad-rendering fields' );
	}
	$adzworld_recorded_views = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT number_of_times FROM {$wpdb->prefix}adz_views WHERE targate_id = %d AND target_type = %s AND ad_date = %s",
			$local_ad,
			'shortcode',
			current_time( 'Y-m-d' )
		)
	);
	adzworld_test_assert( 2 === (int) $adzworld_recorded_views, 'First close inserts and second close increments the view record' );
	foreach ( $adzworld_preserved_rows as $adzworld_row_id => $adzworld_expected_view ) {
		$adzworld_preserved_view = $wpdb->get_row(
			$wpdb->prepare( "SELECT number_of_times, ad_date FROM {$wpdb->prefix}adz_views WHERE id = %d", $adzworld_row_id )
		);
		adzworld_test_assert(
			$adzworld_preserved_view && $adzworld_expected_view['count'] === (int) $adzworld_preserved_view->number_of_times
			&& $adzworld_expected_view['date'] === $adzworld_preserved_view->ad_date,
			'Closing this ad preserves other target types and historical days'
		);
	}
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
	$wpdb->delete( $wpdb->prefix . 'adz_views', array( 'targate_id' => $local_ad ), array( '%d' ) );
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
