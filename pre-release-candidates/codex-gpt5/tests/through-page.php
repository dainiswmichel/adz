<?php
/**
 * Real HTTP journey through a saved, local through-page advertisement.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

require __DIR__ . '/regression.php';

global $wpdb;

adzworld_test_assert( '127.0.0.1' === wp_parse_url( home_url(), PHP_URL_HOST ) && ! adz_network_enabled(), 'Through-page checks use the local test site without a connected network' );
$adzworld_options  = get_option( 'adz_ad_options', null );
$adzworld_pool     = get_option( 'next_adz_pool', null );
$adzworld_rotation = 'adz_rotation_' . time() . wp_rand( 1000, 9999 );
$adzworld_ad       = 0;
$adzworld_page     = 0;
try {
	$adzworld_ad = wp_insert_post(
		array(
			'post_type'    => 'adz_ad',
			'post_status'  => 'publish',
			'post_title'   => 'Disposable through-page advertisement',
			'post_content' => '<p>codex-through-page-ad-marker</p>',
		),
		true
	);
	adzworld_test_assert( ! is_wp_error( $adzworld_ad ), 'Through-page advertisement fixture is created' );
	$adzworld_page = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Disposable through-page article',
			'post_content' => '<p>codex-through-page-article-marker</p>',
		),
		true
	);
	adzworld_test_assert( ! is_wp_error( $adzworld_page ), 'Through-page article fixture is created' );
	$adzworld_config                = is_array( $adzworld_options ) ? $adzworld_options : array();
	$adzworld_config['ad_settings'] = array(
		array(
			'rotation_id'         => $adzworld_rotation,
			'rotation_name'       => 'Disposable through-page rotation',
			'rotation_pages'      => array( (string) $adzworld_page ),
			'rotation_categories' => array(),
			'rotation_tags'       => array(),
			'adz_rotation'        => 'in_sequence',
			'ad_sequences'        => array( $adzworld_ad ),
			'member_level'        => 'non-logged',
			'loop'                => array( 'no' ),
			'no_of_times'         => '1',
			'ad_seconds'          => '0',
			'browse_seconds'      => '60',
			'popup_or_page'       => 'thru_page',
			'adz_template'        => 'template1.php',
		),
	);
	update_option( 'adz_ad_options', $adzworld_config );
	$adzworld_response = wp_remote_get( get_permalink( $adzworld_page ) );
	adzworld_test_assert( ! is_wp_error( $adzworld_response ) && 200 === wp_remote_retrieve_response_code( $adzworld_response ), 'Configured through-page request succeeds' );
	$adzworld_markup = wp_remote_retrieve_body( $adzworld_response );
	adzworld_test_assert(
		false !== strpos( $adzworld_markup, 'codex-through-page-ad-marker' )
		&& false === strpos( $adzworld_markup, 'codex-through-page-article-marker' ),
		'The configured ad appears before the article'
	);
	preg_match_all( "/([a-z_]+)\\s*:\\s*'([^']*)'/", $adzworld_markup, $adzworld_fields, PREG_SET_ORDER );
	$adzworld_close_body = array();
	foreach ( $adzworld_fields as $adzworld_field ) {
		$adzworld_close_body[ $adzworld_field[1] ] = html_entity_decode( $adzworld_field[2], ENT_QUOTES, 'UTF-8' );
	}
	adzworld_test_assert(
		isset( $adzworld_close_body['adzworld_context'], $adzworld_close_body['adz_nonce'], $adzworld_close_body['rotations_id'] )
		&& $adzworld_rotation === $adzworld_close_body['rotations_id'],
		'Actual through-page output includes its saved rotation and both request tokens'
	);
	$adzworld_response = wp_remote_post( admin_url( 'admin-ajax.php' ), array( 'body' => $adzworld_close_body ) );
	adzworld_test_assert(
		! is_wp_error( $adzworld_response ) && 200 === wp_remote_retrieve_response_code( $adzworld_response )
		&& 'close' === wp_remote_retrieve_body( $adzworld_response ),
		'The unmodified close payload from the actual page is accepted'
	);
	$adzworld_response = wp_remote_get( get_permalink( $adzworld_page ) );
	adzworld_test_assert(
		! is_wp_error( $adzworld_response ) && false !== strpos( wp_remote_retrieve_body( $adzworld_response ), 'codex-through-page-article-marker' ),
		'The article is available after the recorded close'
	);
} finally {
	if ( null === $adzworld_options ) {
		delete_option( 'adz_ad_options' );
	} else {
		update_option( 'adz_ad_options', $adzworld_options );
	}
	if ( null === $adzworld_pool ) {
		delete_option( 'next_adz_pool' );
	} else {
		update_option( 'next_adz_pool', $adzworld_pool );
	}
	delete_option( $adzworld_rotation );
	if ( is_int( $adzworld_page ) && $adzworld_page > 0 ) {
		$wpdb->delete( $wpdb->prefix . 'adz_views', array( 'targate_id' => $adzworld_page ), array( '%d' ) );
		wp_delete_post( $adzworld_page, true );
	}
	if ( is_int( $adzworld_ad ) && $adzworld_ad > 0 ) {
		wp_delete_post( $adzworld_ad, true );
	}
}
WP_CLI::success( 'Through-page HTTP journey passed.' );
