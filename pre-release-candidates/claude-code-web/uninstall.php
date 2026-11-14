<?php
/**
 * Remove what this plugin stored, when the site owner deletes it.
 *
 * Deleting a plugin is a clear instruction. Everything below was written by
 * this plugin and is of no use to anything else, so none of it is left
 * behind: not the publisher's settings, and not one word a reader typed.
 *
 * The reader data matters most. Advertising logs and saved preferences are
 * what people told this site, and a plugin that has been deleted has no
 * business keeping them on the site owner's server.
 *
 * Affiliate credentials go too. They are the publisher's keys to other
 * people's networks and must not sit in the database after the plugin that
 * used them is gone.
 *
 * @package adz-world
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/* Settings, connections and credentials the publisher configured. */
$adzworld_options = array(
	'adz_ad_options',
	'adz_publisher_options',
	'adz_session_option',
	'adzworld_affiliate_accounts',
	'adzworld_earnings_connection',
	'adzworld_named_rotations',
	'adzworld_network_rows',
	'adzworld_networks',
	'adzworld_rotation_sources',
	'next_adz_pool',

	/* What readers told this site. */
	'adzworld_logs',
);

foreach ( $adzworld_options as $adzworld_option ) {
	delete_option( $adzworld_option );
	delete_site_option( $adzworld_option );
}

/*
 * Every reader's saved advertising preferences and ratings. delete_metadata()
 * with $delete_all removes the key for every user in one statement rather
 * than loading the user list.
 */
delete_metadata( 'user', 0, 'adzworld_preference', '', true );
delete_metadata( 'user', 0, 'adzworld_feedback', '', true );
