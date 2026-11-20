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

$adzworld_slider_stylesheet = get_option( 'adzworld_ratings_slider_stylesheet', array() );
if ( is_array( $adzworld_slider_stylesheet ) && isset( $adzworld_slider_stylesheet['file'] ) && is_string( $adzworld_slider_stylesheet['file'] ) ) {
	$adzworld_uploads         = wp_upload_dir();
	$adzworld_upload_base     = isset( $adzworld_uploads['basedir'] ) ? trailingslashit( wp_normalize_path( $adzworld_uploads['basedir'] ) ) : '';
	$adzworld_slider_file     = wp_normalize_path( $adzworld_slider_stylesheet['file'] );
	$adzworld_file_in_uploads = '' !== $adzworld_upload_base && 0 === strpos( $adzworld_slider_file, $adzworld_upload_base );
	if ( $adzworld_file_in_uploads && is_file( $adzworld_slider_file ) ) {
		wp_delete_file( $adzworld_slider_file );
	}
}

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

foreach ( array( 'adzworld_logs_settings', 'adzworld_rating_bands', 'adzworld_ratings_slider_theme', 'adzworld_ratings_slider_themes', 'adzworld_ratings_slider_stylesheet', 'adzworld_response_message', 'adzworld_profile_saving' ) as $adzworld_option ) {
	delete_option( $adzworld_option );
}

foreach ( array( 'adzworld_save_with_profile', 'adzworld_preference', 'adzworld_feedback' ) as $adzworld_meta_key ) {
	delete_metadata( 'user', 0, $adzworld_meta_key, '', true );
}
