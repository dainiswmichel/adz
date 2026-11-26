<?php
/**
 * Remove what readers left behind when the plugin is deleted.
 *
 * Deletes every adz journey, the AdzLogs, ratings-band and response settings,
 * and each reader's profile choice, saved adz requests and ratings. The plugin
 * files are not loaded during uninstall, so keys are written out here.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

wp_clear_scheduled_hook( 'adzworld_logs_cleanup' );

do {
	$adzworld_journeys = get_posts(
		array(
			'post_type'        => 'adzworld_journey',
			'post_status'      => 'private',
			'numberposts'      => 100,
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);
	foreach ( $adzworld_journeys as $adzworld_journey ) {
		wp_delete_post( (int) $adzworld_journey, true );
	}
	$adzworld_more = 100 === count( $adzworld_journeys );
} while ( $adzworld_more );

/*
 * Rotation state is kept one option per rotation, named adz_rotation_<n>, so
 * those names have to be read out of the settings record before the record
 * itself is deleted below. The pattern is the one the plugin validates against
 * in adzworld_validate_rotation_id(); plugin files are not loaded during
 * uninstall, so it is restated here rather than called.
 */
$adzworld_settings = get_option( 'adz_ad_options', array() );

if ( is_array( $adzworld_settings ) && isset( $adzworld_settings['ad_settings'] ) && is_array( $adzworld_settings['ad_settings'] ) ) {
	foreach ( $adzworld_settings['ad_settings'] as $adzworld_rotation ) {
		if ( ! is_array( $adzworld_rotation ) || ! isset( $adzworld_rotation['rotation_id'] ) || ! is_scalar( $adzworld_rotation['rotation_id'] ) ) {
			continue;
		}

		$adzworld_rotation_id = (string) $adzworld_rotation['rotation_id'];

		if ( 1 === preg_match( '/^adz_rotation_[0-9]+$/', $adzworld_rotation_id ) ) {
			delete_option( $adzworld_rotation_id );
		}
	}
}

/*
 * Each template keeps its saved lines under template_<filename>. The template
 * files are still on disk while uninstall runs, so the names are read from the
 * directory and a template added later is covered without editing this list.
 * template1.php is the name countdown-layout.php was renamed from, and an
 * install upgraded from an earlier version can still hold its settings.
 */
$adzworld_template_names = array( 'template1.php' );
$adzworld_template_files = glob( __DIR__ . '/adz-templates/*.php' );

if ( is_array( $adzworld_template_files ) ) {
	foreach ( $adzworld_template_files as $adzworld_template_file ) {
		$adzworld_template_names[] = basename( $adzworld_template_file );
	}
}

foreach ( array_unique( $adzworld_template_names ) as $adzworld_template_name ) {
	delete_option( 'template_' . $adzworld_template_name );
}

foreach ( array(
	'adz_ad_options',
	'adz_publisher_options',
	'adz_session_option',
	'adzworld_affiliate_accounts',
	'adzworld_audience_messages',
	'adzworld_logs_settings',
	'adzworld_named_rotations',
	'adzworld_network_rows',
	'adzworld_profile_saving',
	'adzworld_rating_bands',
	'adzworld_ratings_slider_theme',
	'adzworld_response_message',
	'adzworld_rotation_sources',
	'next_adz_pool',
) as $adzworld_option ) {
	delete_option( $adzworld_option );
}

foreach ( array( 'adzworld_save_with_profile', 'adzworld_preference', 'adzworld_feedback', 'adzworld_audience_member' ) as $adzworld_meta_key ) {
	delete_metadata( 'user', 0, $adzworld_meta_key, '', true );
}
