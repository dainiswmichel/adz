<?php
/**
 * Remove plugin settings and audience data when the plugin is deleted.
 *
 * Keep ads and pages, including their content metadata. WordPress runs this
 * file without loading the plugin, so storage keys are written out here.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove the current site's settings, rotation state, and recorded journeys.
 */
function adzworld_uninstall_site_data() {
	wp_clear_scheduled_hook( 'adzworld_logs_cleanup' );

	$offset = 0;
	do {
		$journeys = get_posts(
			array(
				'post_type'   => 'adzworld_journey',
				'post_status' => get_post_stati(),
				'numberposts' => 100,
				'fields'      => 'ids',
				'orderby'     => 'ID',
				'order'       => 'ASC',
				'offset'      => $offset,
			)
		);
		foreach ( $journeys as $journey ) {
			if ( ! wp_delete_post( (int) $journey, true ) ) {
				// Remaining rows from this batch precede the next batch in ID order.
				++$offset;
			}
		}
		$more_journeys = 100 === count( $journeys );
	} while ( $more_journeys );

	// Current settings can identify non-autoloaded legacy state without an index entry.
	$option_names = array_keys( wp_load_alloptions() );
	$settings     = get_option( 'adz_ad_options', array() );
	$rotations    = is_array( $settings ) ? ( $settings['ad_settings'] ?? array() ) : array();
	foreach ( (array) $rotations as $rotation ) {
		if ( is_array( $rotation ) && isset( $rotation['rotation_id'] ) && is_string( $rotation['rotation_id'] ) ) {
			$option_names[] = $rotation['rotation_id'];
		}
	}

	foreach ( array_unique( $option_names ) as $option_name ) {
		if ( preg_match( '/\Aadz_rotation_[0-9]+\z/', $option_name ) ) {
			delete_option( $option_name );
		} elseif ( preg_match( '/\Aadzworld_rotation_state_key_(adz_rotation_[0-9]+)\z/', $option_name, $match ) ) {
			delete_option( $match[1] );
			delete_option( $option_name );
		}
	}

	$option_names = array(
		'adz_ad_options',
		'adz_publisher_options',
		'adz_session_option',
		'adzworld_affiliate_accounts',
		'adzworld_audience_messages',
		'adzworld_logs_settings',
		'adzworld_named_rotations',
		'adzworld_network_rows',
		'adzworld_notice_text',
		'adzworld_profile_saving',
		'adzworld_rating_bands',
		'adzworld_ratings_slider_theme',
		'adzworld_response_message',
		'adzworld_rotation_sources',
		'adzworld_showcase_page',
		'next_adz_pool',
		'template_countdown-layout.php',
		'template_countdown-top-layout.php',
		'template_template1.php',
	);
	foreach ( $option_names as $option_name ) {
		delete_option( $option_name );
	}

	delete_metadata( 'post', 0, 'adz_rotation_shortcode', '', true );
}

if ( is_multisite() ) {
	$adzworld_offset = 0;
	do {
		$adzworld_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'offset' => $adzworld_offset,
			)
		);
		foreach ( $adzworld_sites as $adzworld_site_id ) {
			switch_to_blog( (int) $adzworld_site_id );
			adzworld_uninstall_site_data();
			restore_current_blog();
		}
		$adzworld_site_count = count( $adzworld_sites );
		$adzworld_offset    += $adzworld_site_count;
	} while ( 100 === $adzworld_site_count );
} else {
	adzworld_uninstall_site_data();
}

foreach ( array( 'adzworld_save_with_profile', 'adzworld_preference', 'adzworld_feedback', 'adzworld_audience_member' ) as $adzworld_meta_key ) {
	delete_metadata( 'user', 0, $adzworld_meta_key, '', true );
}
