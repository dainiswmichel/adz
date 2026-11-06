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

adzworld_test_assert( '127.0.0.1' === wp_parse_url( home_url(), PHP_URL_HOST ), 'HTTP checks are confined to the local test WordPress' );
$adzworld_probe         = 'adzworld_http_probe_' . wp_generate_password( 10, false );
$adzworld_previous_pool = get_option( 'next_adz_pool' );
$adzworld_local_ad      = wp_insert_post(
	array(
		'post_type'    => 'adz_ad',
		'post_status'  => 'publish',
		'post_title'   => 'Codex disposable local ad',
		'post_content' => '<p>adzworld-local-ad-regression-marker</p>',
	),
	true
);
adzworld_test_assert( ! is_wp_error( $adzworld_local_ad ), 'Published local ad fixture is created' );
add_option( $adzworld_probe, 'retained', '', false );
try {
	$adzworld_response = wp_remote_post(
		home_url( '/' ),
		array( 'body' => array( 'adz_ad_options' => array( 'ad_settings' => array( array( 'rotation_id' => $adzworld_probe ) ) ) ) )
	);
	adzworld_test_assert( ! is_wp_error( $adzworld_response ) && 200 === wp_remote_retrieve_response_code( $adzworld_response ), 'Unauthenticated homepage POST completes' );
	wp_cache_delete( $adzworld_probe, 'options' );
	adzworld_test_assert( 'retained' === get_option( $adzworld_probe ), 'Unauthenticated HTTP POST cannot delete arbitrary options' );
	foreach ( array( 'adz_reset_registration', 'get_template_settings' ) as $adzworld_test_action ) {
		$adzworld_response = wp_remote_post( admin_url( 'admin-ajax.php' ), array( 'body' => array( 'action' => $adzworld_test_action ) ) );
		adzworld_test_assert( ! is_wp_error( $adzworld_response ) && 400 === wp_remote_retrieve_response_code( $adzworld_response ), 'Anonymous AJAX action is unregistered: ' . $adzworld_test_action );
	}
	adzworld_test_assert( ! adzworld_network_enabled(), 'Local serving test has no external service enabled' );
	$adzworld_response = wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'body' => adzworld_test_signed_body(
				array(
					'action'                 => 'adzworld_get_advertise_content',
					'adz_nonce'              => wp_create_nonce( 'adzdotworld' ),
					'ad_visibility_interval' => 60,
					'ad_to_serve'            => $adzworld_local_ad,
					'target_type'            => 'page',
					'target'                 => $adzworld_local_ad,
					'rotations_id'           => 'adz_rotation_99999999999999999999',
					'sequence'               => (string) $adzworld_local_ad,
					'repeat_times'           => 1,
					'display_type'           => 'popup',
					'adz_template'           => 'template1.php',
				)
			),
		)
	);
	adzworld_test_assert( ! is_wp_error( $adzworld_response ) && 400 === wp_remote_retrieve_response_code( $adzworld_response ), 'Public ad request rejects a syntactically valid but unconfigured rotation before rendering' );
	$adzworld_data = json_decode( wp_remote_retrieve_body( $adzworld_response ), true );
	adzworld_test_assert( is_array( $adzworld_data ) && false === $adzworld_data['success'], 'Unconfigured rotation returns an explicit failure, not a successful ad response' );
	adzworld_test_assert( in_array( (string) $adzworld_local_ad, explode( ',', adzworld_get_publisher_ads() ), true ), 'Local ad sequence uses the local post ID without network metadata' );
	wp_update_post(
		array(
			'ID'          => $adzworld_local_ad,
			'post_status' => 'draft',
		)
	);
	adzworld_test_assert( is_wp_error( adzworld_local_ad_response( $adzworld_local_ad ) ), 'Draft local ads are not publicly served' );
	wp_update_post(
		array(
			'ID'            => $adzworld_local_ad,
			'post_status'   => 'publish',
			'post_password' => 'disposable-test-password',
		)
	);
	adzworld_test_assert( is_wp_error( adzworld_local_ad_response( $adzworld_local_ad ) ), 'Password-protected local ads are not publicly served' );
	wp_update_post(
		array(
			'ID'            => $adzworld_local_ad,
			'post_password' => '',
			'post_type'     => 'post',
		)
	);
	adzworld_test_assert( is_wp_error( adzworld_local_ad_response( $adzworld_local_ad ) ), 'Ordinary posts cannot be served as local ads' );
	wp_update_post(
		array(
			'ID'        => $adzworld_local_ad,
			'post_type' => 'adz_ad',
		)
	);
	$adzworld_response = wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'body' => adzworld_test_signed_body(
				array(
					'action'                 => 'adzworld_get_advertise_content',
					'adz_nonce'              => wp_create_nonce( 'adzdotworld' ),
					'ad_visibility_interval' => 60,
					'ad_to_serve'            => $adzworld_local_ad,
					'target'                 => $adzworld_local_ad,
					'target_type'            => 'shortcode',
					'display_type'           => 'popup',
					'adz_template'           => 'template1.php',
					'sequence'               => (string) $adzworld_local_ad,
					'repeat_times'           => 1,
				)
			),
		)
	);
	$adzworld_rendered = is_wp_error( $adzworld_response ) ? '' : wp_remote_retrieve_body( $adzworld_response );
	adzworld_test_assert( is_string( $adzworld_rendered ) && false !== strpos( $adzworld_rendered, 'adzworld-local-ad-regression-marker' ), 'Published local ad renders over HTTP without a service registration' );
	$adzworld_valid_body = adzworld_test_signed_body(
		array(
			'action'                 => 'adzworld_get_advertise_content',
			'adz_nonce'              => wp_create_nonce( 'adzdotworld' ),
			'ad_visibility_interval' => 60,
			'ad_to_serve'            => $adzworld_local_ad,
			'target'                 => $adzworld_local_ad,
			'target_type'            => 'shortcode',
			'display_type'           => 'popup',
			'adz_template'           => 'template1.php',
			'sequence'               => (string) $adzworld_local_ad,
			'repeat_times'           => 1,
		)
	);
	foreach ( array(
		'target'                 => $adzworld_local_ad + 1,
		'target_type'            => 'page',
		'rotations_id'           => 'adz_rotation_99999999999999999999',
		'sequence'               => '999999',
		'repeat_times'           => 'infinite',
		'adz_template'           => '../adz-world.php',
		'ad_to_serve'            => array( $adzworld_local_ad ),
		'ad_visibility_interval' => str_repeat( '1', 8193 ),
		'close_adz'              => 'true',
		'adzworld_context'       => '',
	) as $adzworld_field => $adzworld_tampered_value ) {
		$adzworld_body                    = $adzworld_valid_body;
		$adzworld_body[ $adzworld_field ] = $adzworld_tampered_value;
		$adzworld_response                = wp_remote_post( admin_url( 'admin-ajax.php' ), array( 'body' => $adzworld_body ) );
		adzworld_test_assert( ! is_wp_error( $adzworld_response ) && 403 === wp_remote_retrieve_response_code( $adzworld_response ), 'Configuration signature rejects a changed field: ' . $adzworld_field );
	}
	adzworld_test_assert( ! adzworld_get_view_row( '127.0.0.1', $adzworld_local_ad, 'shortcode' ), 'Rejected close mutation did not create a view record' );
	$adzworld_page_view_key = adzworld_view_key( '127.0.0.1', $adzworld_local_ad, 'page' );
	$adzworld_inserted      = set_transient(
		$adzworld_page_view_key,
		array(
			'number_of_times' => 11,
			'updated'         => current_time( 'mysql' ),
		),
		HOUR_IN_SECONDS
	);
	adzworld_test_assert( $adzworld_inserted, 'An unrelated view-history fixture is created' );
	for ( $adzworld_close_count = 1; $adzworld_close_count <= 2; ++$adzworld_close_count ) {
		$adzworld_response = wp_remote_post(
			admin_url( 'admin-ajax.php' ),
			array(
				'body' => adzworld_test_signed_body(
					array(
						'action'                 => 'adzworld_get_advertise_content',
						'adz_nonce'              => wp_create_nonce( 'adzdotworld' ),
						'ad_visibility_interval' => 60,
						'target'                 => $adzworld_local_ad,
						'target_type'            => 'shortcode',
						'close_adz'              => 'true',
					)
				),
			)
		);
		adzworld_test_assert( ! is_wp_error( $adzworld_response ) && 'close' === wp_remote_retrieve_body( $adzworld_response ), 'Close action works without unused ad-rendering fields' );
	}
	// The HTTP requests update options in another PHP request; discard this
	// WP-CLI process's earlier negative transient lookup before asserting.
	wp_cache_flush();
	$adzworld_recorded_views = adzworld_get_view_row( '127.0.0.1', $adzworld_local_ad, 'shortcode' );
	adzworld_test_assert( $adzworld_recorded_views && 2 === (int) $adzworld_recorded_views->number_of_times, 'First close inserts and second close increments the view record' );
	$adzworld_preserved_view = adzworld_get_view_row( '127.0.0.1', $adzworld_local_ad, 'page' );
	adzworld_test_assert(
		$adzworld_preserved_view && 11 === (int) $adzworld_preserved_view->number_of_times,
		'Closing this ad preserves the record for another target type'
	);
	$adzworld_administrators = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
		)
	);
	$adzworld_user_id        = $adzworld_administrators[0]->ID;
	$adzworld_expiry         = time() + 300;
	$adzworld_token          = WP_Session_Tokens::get_instance( $adzworld_user_id )->create( $adzworld_expiry );
	$adzworld_logged_in      = wp_generate_auth_cookie( $adzworld_user_id, $adzworld_expiry, 'logged_in', $adzworld_token );
	$adzworld_auth           = wp_generate_auth_cookie( $adzworld_user_id, $adzworld_expiry, 'auth', $adzworld_token );
	wp_set_current_user( $adzworld_user_id );
	$_COOKIE[ LOGGED_IN_COOKIE ] = $adzworld_logged_in;
	$adzworld_headers            = array( 'Cookie' => AUTH_COOKIE . '=' . $adzworld_auth . '; ' . LOGGED_IN_COOKIE . '=' . $adzworld_logged_in );
	$adzworld_response           = wp_remote_get( admin_url(), array( 'headers' => $adzworld_headers ) );
	$adzworld_admin_body         = wp_remote_retrieve_body( $adzworld_response );
	adzworld_test_assert(
		! is_wp_error( $adzworld_response ) && 200 === wp_remote_retrieve_response_code( $adzworld_response )
		&& false === strpos( $adzworld_admin_body, 'eof-style-css' )
		&& false === strpos( $adzworld_admin_body, 'adz_admin_script-js' ),
		'Plugin assets are absent from unrelated administrator screens'
	);
	$adzworld_response   = wp_remote_get(
		admin_url( 'edit.php?post_type=adz_ad&page=_adz_ad_options' ),
		array( 'headers' => $adzworld_headers )
	);
	$adzworld_admin_body = wp_remote_retrieve_body( $adzworld_response );
	adzworld_test_assert(
		! is_wp_error( $adzworld_response ) && 200 === wp_remote_retrieve_response_code( $adzworld_response )
		&& false !== strpos( $adzworld_admin_body, 'eof-style-css' )
		&& false !== strpos( $adzworld_admin_body, 'adz_admin_script-js' ),
		'Plugin assets load on the adz-world settings screen'
	);
	$adzworld_response = wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'headers' => $adzworld_headers,
			'body'    => array(
				'action'            => 'get_template_settings',
				'selected_template' => 'template1.php',
				'nonce'             => wp_create_nonce( 'adzworld_template_settings' ),
			),
		)
	);
	$adzworld_data     = is_wp_error( $adzworld_response ) ? null : json_decode( wp_remote_retrieve_body( $adzworld_response ), true );
	adzworld_test_assert( is_array( $adzworld_data ) && isset( $adzworld_data['header_text'], $adzworld_data['footer_style'] ), 'Authorized administrator can still retrieve template settings' );
	$adzworld_response = wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'headers' => $adzworld_headers,
			'body'    => array(
				'action'            => 'get_template_settings',
				'selected_template' => 'template1.php',
				'nonce'             => 'invalid',
			),
		)
	);
	adzworld_test_assert( ! is_wp_error( $adzworld_response ) && 403 === wp_remote_retrieve_response_code( $adzworld_response ), 'Administrator without valid nonce is rejected' );
	$adzworld_response = wp_remote_get( admin_url( 'edit.php?post_type=adz_ad&page=adzworld-earnings' ), array( 'headers' => $adzworld_headers ) );
	adzworld_test_assert(
		! is_wp_error( $adzworld_response ) && 200 === wp_remote_retrieve_response_code( $adzworld_response )
		&& false !== strpos( wp_remote_retrieve_body( $adzworld_response ), 'Not connected.' ),
		'Earnings dashboard renders an explicit unconnected state'
	);
} finally {
	delete_option( $adzworld_probe );
	wp_delete_post( $adzworld_local_ad, true );
	delete_transient( adzworld_view_key( '127.0.0.1', $adzworld_local_ad, 'shortcode' ) );
	delete_transient( adzworld_view_key( '127.0.0.1', $adzworld_local_ad, 'page' ) );
	if ( false === $adzworld_previous_pool ) {
		delete_option( 'next_adz_pool' );
	} else {
		update_option( 'next_adz_pool', $adzworld_previous_pool );
	}
	if ( isset( $adzworld_user_id, $adzworld_token ) ) {
		WP_Session_Tokens::get_instance( $adzworld_user_id )->destroy( $adzworld_token );
	}
	unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
	wp_set_current_user( 0 );
}
WP_CLI::success( 'Local HTTP authorization and dashboard checks passed.' );
