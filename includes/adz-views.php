<?php
/**
 * Ad view records.
 *
 * Replaces the hand-concatenated SQL in 1.0.8, where $_POST values reached the
 * query through sanitize_text_field(). That function strips tags; it does not
 * escape quotes and was never a SQL escaping function, so those queries were
 * injectable. Everything here goes through $wpdb->prepare() or $wpdb's own
 * escaping in insert()/update().
 *
 * @package adz.world
 */

defined( 'ABSPATH' ) || exit;

/** Current schema version, bumped when the table changes. */
const ADZ_VIEWS_DB_VERSION = '2';

/**
 * Fully qualified views table name.
 *
 * @return string
 */
function adz_views_table() {
	global $wpdb;

	return $wpdb->prefix . 'adz_views';
}

/**
 * Create or upgrade the views table.
 *
 * The 1.0.8 table keyed views on visitor_ip and carried a misspelled
 * targate_id column. The new columns sit alongside the old ones so existing
 * installs keep their data; nothing is dropped.
 *
 * @return void
 */
function adz_views_install() {
	global $wpdb;

	if ( ! function_exists( 'dbDelta' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	$table           = adz_views_table();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		visitor_id varchar(64) NOT NULL DEFAULT '',
		visitor_ip varchar(45) NOT NULL DEFAULT '',
		target_id bigint(20) NOT NULL DEFAULT 0,
		targate_id bigint(20) NOT NULL DEFAULT 0,
		target_type varchar(32) NOT NULL DEFAULT '',
		number_of_times bigint(20) NOT NULL DEFAULT 0,
		ad_date date NOT NULL DEFAULT '1970-01-01',
		updated datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
		PRIMARY KEY  (id),
		KEY visitor_lookup (visitor_id,target_id,target_type,ad_date)
	) {$charset_collate};";

	dbDelta( $sql );

	update_option( 'adz_views_db_version', ADZ_VIEWS_DB_VERSION, false );
}

/**
 * Run the table upgrade when the stored schema version is behind.
 *
 * register_activation_hook does not fire on plugin update, so this also runs
 * on init for sites updated in place.
 *
 * @return void
 */
function adz_views_maybe_upgrade() {
	if ( get_option( 'adz_views_db_version' ) === ADZ_VIEWS_DB_VERSION ) {
		return;
	}

	adz_views_install();
}

/**
 * The visitor's view record for a target today, if any.
 *
 * @param int    $target_id   Post the ad was shown against.
 * @param string $target_type Context: 'shortcode', 'thru_page', 'popup' or ''.
 * @return object|null Row object, or null when there is no view today.
 */
function adz_get_view( $target_id, $target_type = '' ) {
	global $wpdb;

	$table      = adz_views_table();
	$visitor_id = adz_visitor_id();
	$today      = current_time( 'Y-m-d' );

	if ( '' !== $target_type ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- plugin-owned table, no core API for it.
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				 WHERE visitor_id = %s AND target_id = %d AND target_type = %s AND ad_date = %s",
				$visitor_id,
				(int) $target_id,
				$target_type,
				$today
			)
		);
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- plugin-owned table, no core API for it.
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table}
			 WHERE visitor_id = %s AND target_id = %d AND ad_date = %s",
			$visitor_id,
			(int) $target_id,
			$today
		)
	);
}

/**
 * Record that the visitor viewed an ad, creating or incrementing their row.
 *
 * @param int    $target_id   Post the ad was shown against.
 * @param string $target_type Context the ad was shown in.
 * @return int The visitor's view count for this target today.
 */
function adz_record_view( $target_id, $target_type = '' ) {
	global $wpdb;

	$table      = adz_views_table();
	$visitor_id = adz_visitor_id();
	$target_id  = (int) $target_id;
	$now        = current_time( 'mysql' );
	$today      = current_time( 'Y-m-d' );

	$existing = adz_get_view( $target_id, $target_type );

	if ( $existing ) {
		$count = (int) $existing->number_of_times + 1;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- plugin-owned table.
		$wpdb->update(
			$table,
			array(
				'number_of_times' => $count,
				'updated'         => $now,
			),
			array( 'id' => (int) $existing->id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return $count;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- plugin-owned table.
	$wpdb->insert(
		$table,
		array(
			'visitor_id'      => $visitor_id,
			'visitor_ip'      => adz_visitor_ip(),
			'target_id'       => $target_id,
			'targate_id'      => $target_id,
			'target_type'     => (string) $target_type,
			'number_of_times' => 1,
			'ad_date'         => $today,
			'updated'         => $now,
		),
		array( '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s' )
	);

	return 1;
}

/**
 * Whether the visitor still owes an ad view for this target.
 *
 * The gate opens when the visitor has viewed at least once today AND is inside
 * the browsing window that view bought them. Once the window lapses, and while
 * the repeat limit allows, the gate closes again.
 *
 * @param int    $target_id     Post being requested.
 * @param string $target_type   Context the ad is shown in.
 * @param int    $repeat_times  Maximum ads per day; 0 or 'infinite' for no cap.
 * @return bool True when an ad must be shown before the content.
 */
function adz_view_required( $target_id, $target_type, $repeat_times ) {
	$view = adz_get_view( $target_id, $target_type );

	if ( ! $view ) {
		return true;
	}

	if ( adz_visitor_in_grace_period() ) {
		return false;
	}

	$unlimited = ( 'infinite' === $repeat_times || ! $repeat_times );

	if ( ! $unlimited && (int) $view->number_of_times >= (int) $repeat_times ) {
		// The visitor has already seen today's allowance; let them through.
		return false;
	}

	return true;
}
