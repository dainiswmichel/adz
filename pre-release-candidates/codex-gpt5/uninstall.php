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

foreach ( array( 'adzworld_logs_settings', 'adzworld_rating_bands', 'adzworld_ratings_slider_theme', 'adzworld_response_message', 'adzworld_profile_saving' ) as $adzworld_option ) {
	delete_option( $adzworld_option );
}

foreach ( array( 'adzworld_save_with_profile', 'adzworld_preference', 'adzworld_feedback' ) as $adzworld_meta_key ) {
	delete_metadata( 'user', 0, $adzworld_meta_key, '', true );
}
