<?php
/**
 * AdzLogs round trip.
 *
 * Proves the tab has something to show: a served adzad is remembered, a
 * response given to it lands on the same row, a second adzad becomes a second
 * round, and the whole thing reads back grouped by rotation and slot.
 *
 * @package adz-world
 */

require __DIR__ . '/wp-stub.php';

$adz_plugin = getenv( 'ADZ_SRC' ) ? rtrim( getenv( 'ADZ_SRC' ), '/' ) : dirname( __DIR__ );

foreach ( array( 'includes/adzworld-logs.php', 'includes/adzworld-journey.php' ) as $adz_needed ) {
	if ( ! is_file( $adz_plugin . '/' . $adz_needed ) ) {
		printf( "SKIP: %s has no %s\n", basename( $adz_plugin ), $adz_needed );
		exit( 3 );
	}
}

/* The options table and the session, for the duration of this run. */
$GLOBALS['adz_options'] = array();

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default_value = false ) {
		return isset( $GLOBALS['adz_options'][ $name ] ) ? $GLOBALS['adz_options'][ $name ] : $default_value;
	}

	function update_option( $name, $value ) {
		$GLOBALS['adz_options'][ $name ] = $value;

		return true;
	}

	function add_option( $name, $value, $deprecated = '', $autoload = 'yes' ) {
		if ( isset( $GLOBALS['adz_options'][ $name ] ) ) {
			return false;
		}

		$GLOBALS['adz_options'][ $name ] = $value;

		return true;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can() {
		return true;
	}
	function check_admin_referer() {
		return true;
	}
	function admin_url( $path = '' ) {
		return 'https://example.test/wp-admin/' . $path;
	}
	function nocache_headers() {}
	function wp_safe_redirect() {}
	function wp_die( $m = '' ) {
		throw new RuntimeException( (string) $m );
	}
}

$_SESSION = array();

require $adz_plugin . '/includes/adz-preferences.php';
require $adz_plugin . '/includes/adzworld-journey.php';
require $adz_plugin . '/includes/adzworld-logs.php';

$pass = 0;
$fail = 0;

/**
 * Compare one value against what it has to be.
 *
 * @param string $label Description.
 * @param mixed  $got   Actual.
 * @param mixed  $want  Expected.
 * @return void
 */
function check( $label, $got, $want ) {
	global $pass, $fail;

	if ( $got === $want ) {
		++$pass;
		printf( "  [ok] %-44s got %s\n", $label, var_export( $got, true ) );

		return;
	}

	++$fail;
	printf( "  [FAIL] %-42s got %s  want %s\n", $label, var_export( $got, true ), var_export( $want, true ) );
}

echo "=== a served adzad is remembered against its place ===\n";
$place = adzworld_logs_place( 'sidebar', 0 );
adzworld_logs_remember_ad( $place, 'ad-412', 'ad:412', 'couch', array(), null );

$rows = adzworld_logs_rows();
check( 'one row stored', count( $rows ), 1 );
check( 'the rotation it was served in', $rows[0]['rotation'], 'sidebar' );
check( 'the slot it was served in', $rows[0]['slot'], 0 );
check( 'the served adzad', $rows[0]['ad'], 'ad-412' );
check( 'what the slot was configured as', $rows[0]['configured'], 'ad:412' );
check( 'the category in force', $rows[0]['category'], 'couch' );
check( 'no response yet', $rows[0]['responded'], 0 );

echo "\n=== a rating lands on the adzad it answered ===\n";
adzworld_logs_record_response( 'sidebar', '0', '', '0', 'ad-412', array( 'rating' => -40 ), false );

$rows = adzworld_logs_rows();
check( 'still one row, not a second round', count( $rows ), 1 );
check( 'the rating given', $rows[0]['rating_change'], -40 );
check( 'the row is marked as answered', $rows[0]['responded'], 1 );
check( 'an internal adzad is logged to the site', $rows[0]['logged'], 1 );
check( 'and nothing is sent over the wire', $rows[0]['sent_string'], '' );

echo "\n=== a refinement is stored exactly as typed ===\n";
adzworld_logs_record_response( 'sidebar', '0', '', '0', 'ad-412', array( 'refinement' => 'no i want a brown couch' ), false );

$rows = adzworld_logs_rows();
check( 'verbatim, nothing parsed out', $rows[0]['refinement_change'], 'no i want a brown couch' );

echo "\n=== an external adzad stores the string that went out ===\n";
$next = adzworld_logs_place( 'sidebar', 1 );
adzworld_logs_remember_ad( $next, 'source-ebay', 'source:ebay', 'couch', array( 'brown' ), null );
adzworld_logs_record_response( 'sidebar', '1', '', '0', 'source-ebay', array( 'refinement' => 'show only 3 seater' ), true );

$rows = adzworld_logs_rows();
check( 'two rounds now', count( $rows ), 2 );
check( 'the first is untouched', $rows[0]['ad'], 'ad-412' );
check( 'the second is the provider', $rows[1]['ad'], 'source-ebay' );
check( 'a provider request is not logged to the site', $rows[1]['logged'], 0 );
check( 'the sent string is recorded', '' !== $rows[1]['sent_string'], true );
check(
	'and it carries the typed refinement',
	false !== strpos( $rows[1]['sent_string'], 'show only 3 seater' ),
	true
);

echo "\n=== a nested rotation is logged at both levels ===\n";
$inner = adzworld_logs_place( 'inner', 1, 'sidebar', 2 );
adzworld_logs_remember_ad( $inner, 'ad-900', 'ad:900', 'bicycle', array(), null );

$rows = adzworld_logs_rows();
check( 'one adzad, two rows: the outer slot and the inner one', count( $rows ), 4 );
$outer_row = $rows[2];
$inner_row = $rows[3];
check( 'the outer row is in the outer rotation', $outer_row['rotation'], 'sidebar' );
check( 'at the outer slot', $outer_row['slot'], 2 );
check( 'and says which rotation it holds', $outer_row['holds'], 'inner' );
check( 'the outer slot is configured as a rotation', $outer_row['configured'], 'r:inner' );
check( 'the inner row is in the inner rotation', $inner_row['rotation'], 'inner' );
check( 'at the inner slot', $inner_row['slot'], 1 );

echo "\n=== rows group by rotation for the accordion ===\n";
$grouped = adzworld_logs_by_rotation( adzworld_logs_rows() );
check( 'two rotations', count( $grouped ), 2 );

echo "\n=== the export is plain text and carries what was typed ===\n";
$text = adzworld_logs_export_text( adzworld_logs_rows() );
check( 'the export is a string', is_string( $text ), true );
check( 'it names the served adzad', false !== strpos( $text, 'ad-412' ), true );
check( 'it carries the refinement verbatim', false !== strpos( $text, 'no i want a brown couch' ), true );
check( 'it carries the rating', false !== strpos( $text, '-40' ), true );

echo "\n=== the rating is kept as the number the reader set ===\n";
check( 'a negative number stays negative', adzworld_slot_journey_rating( -40 ), -40 );
check( 'a positive number stays positive', adzworld_slot_journey_rating( 73 ), 73 );
check( 'zero is kept, not dropped', adzworld_slot_journey_rating( 0 ), 0 );
check( 'nothing given stays nothing', adzworld_slot_journey_rating( null ), null );

/*
 * The word shown beside the number comes from the feedback module's own
 * labels. adzworld_logs_rating_word() returns an empty string when that
 * module is not loaded, which is what this harness sees: the words are
 * covered where those labels are defined, not here.
 */
check( 'no word without the feedback module', adzworld_logs_rating_word( -40 ), '' );

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail > 0 ? 1 : 0 );
