<?php
/**
 * One slot journey in a rotation.
 *
 * Run: php tools/tests/test-journey.php
 */

define( 'ABSPATH', '/stub/' );
define( 'ADZWORLD_REFINEMENT_MAX_LENGTH', 10000 );

function wp_strip_all_tags( $str ) {
	return trim( strip_tags( (string) $str ) );
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Test stub for the translated thank-you sentence.
	 *
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) {
		unset( $domain );
		return $text;
	}
}

$file = dirname( __DIR__, 2 ) . '/pre-release-candidates/grok-4-7/includes/adzworld-journey.php';

if ( ! is_file( $file ) ) {
	fwrite( STDERR, "Missing $file\n" );
	exit( 2 );
}

require $file;

/**
 * Evaluation labels used by the sent string.
 *
 * @param int $score Score.
 * @return string
 */
function adzworld_feedback_label( $score ) {
	if ( $score <= -75 ) {
		return 'Strongly dislike';
	}
	if ( $score <= -25 ) {
		return 'Dislike';
	}
	if ( $score < 25 ) {
		return 'Neutral';
	}
	if ( $score < 75 ) {
		return 'Like';
	}

	return 'Strongly like';
}

$logs = dirname( __DIR__, 2 ) . '/pre-release-candidates/grok-4-7/includes/adzworld-logs.php';

if ( ! is_file( $logs ) ) {
	fwrite( STDERR, "Missing $logs\n" );
	exit( 2 );
}

require $logs;

$pass = 0;
$fail = 0;

/**
 * Compare one result.
 *
 * @param string $label Label.
 * @param mixed  $got   Actual.
 * @param mixed  $want  Expected.
 * @return void
 */
function journey_check( $label, $got, $want ) {
	global $pass, $fail;

	$ok = ( $got === $want );
	if ( $ok ) {
		++$pass;
	} else {
		++$fail;
	}

	printf( "  [%s] %s\n", $ok ? 'ok' : 'FAIL', $label );

	if ( ! $ok ) {
		echo '       got  ' . var_export( $got, true ) . "\n";
		echo '       want ' . var_export( $want, true ) . "\n";
	}
}

$slot = adzworld_slot_journey_start( 'main', 1, 'bicycle', 'for a nine year old' );
$slot = adzworld_slot_journey_show( $slot, 'ebay:1' );
$slot = adzworld_slot_journey_respond( $slot, 'bicycle', -100, 'no pedals', true );
$slot = adzworld_slot_journey_show( $slot, 'etsy:2' );
$slot = adzworld_slot_journey_respond( $slot, 'bicycle', -40, 'small wheels', true );
$slot = adzworld_slot_journey_show( $slot, 'apple:3' );
$slot = adzworld_slot_journey_respond( $slot, 'bicycle', 0, 'light fabric', true );
$slot = adzworld_slot_journey_show( $slot, 'bestbuy:4' );
$slot = adzworld_slot_journey_respond( $slot, 'bicycle', '80', array( 'red', 'basket' ), true );

$record = adzworld_slot_journey_record( $slot );

journey_check( 'the journey is in rotation main', $record['rotation'], 'main' );
journey_check( 'the journey is in slot 1', $record['slot'], 1 );
journey_check( 'the opening category is bicycle', $record['category'], 'bicycle' );
journey_check( 'the opening refinements stay whole', $record['refinements'], 'for a nine year old' );
journey_check( 'four ads appear', count( $record['journey'] ), 4 );

journey_check(
	'ad 1 is at the rotation, slot, category, and refinements',
	$record['journey'][0]['where'],
	array(
		'rotation'    => 'main',
		'slot'        => 1,
		'category'    => 'bicycle',
		'refinements' => 'for a nine year old',
	)
);
journey_check( 'ad 1 is ebay:1', $record['journey'][0]['ad'], 'ebay:1' );
journey_check( 'ad 1 feedback keeps the rating', $record['journey'][0]['feedback']['rating'], -100 );
journey_check( 'ad 1 feedback keeps the refinements', $record['journey'][0]['feedback']['refinements'], 'no pedals' );
journey_check( 'external feedback carries the modified string', $record['journey'][0]['feedback']['string'], 'bicycle -100 no pedals' );

journey_check(
	'ad 2 is at the rotation and the slot',
	$record['journey'][1]['where'],
	array(
		'rotation' => 'main',
		'slot'     => 1,
	)
);
journey_check( 'ad 2 is etsy:2', $record['journey'][1]['ad'], 'etsy:2' );
journey_check( 'ad 2 category is bicycle', $record['journey'][1]['feedback']['category'], 'bicycle' );
journey_check( 'ad 2 rating is minus 40', $record['journey'][1]['feedback']['rating'], -40 );
journey_check( 'ad 2 refinements are small wheels', $record['journey'][1]['feedback']['refinements'], 'small wheels' );

journey_check( 'zero stays zero', $record['journey'][2]['feedback']['rating'], 0 );
journey_check( 'ad 4 rating came from the numeric string', $record['journey'][3]['feedback']['rating'], 80 );
journey_check( 'listed refinements stay in order', $record['journey'][3]['feedback']['refinements'], 'red basket' );
journey_check( 'the modified string follows those refinements', $record['journey'][3]['feedback']['string'], 'bicycle 80 red basket' );

$internal = adzworld_slot_journey_start( 'main', 0, 'bicycle', 'light fabric' );
$internal = adzworld_slot_journey_show( $internal, 'ad-11' );
$internal = adzworld_slot_journey_respond( $internal, 'bicycle', 20, 'light fabric', false );
$internal = adzworld_slot_journey_record( $internal );

journey_check( 'an internal slot keeps one ad', count( $internal['journey'] ), 1 );
journey_check( 'internal feedback is sent to the site', $internal['journey'][0]['feedback']['site'], true );
journey_check( 'internal feedback has no modified string', isset( $internal['journey'][0]['feedback']['string'] ), false );
journey_check( 'the site sentence is the thank-you sentence', adzworld_slot_journey_thanks(), 'Thank you for your feedback.' );

$quiet = adzworld_slot_journey_start( 'main', 2, 'car', '' );
$quiet = adzworld_slot_journey_show( $quiet, 'ad-9' );
$quiet = adzworld_slot_journey_respond( $quiet, '', null, '' );
$quiet = adzworld_slot_journey_record( $quiet );
journey_check( 'an unanswered ad stays in the journey', $quiet['journey'][0]['ad'], 'ad-9' );
journey_check( 'an unanswered ad has no feedback', $quiet['journey'][0]['feedback'], null );

$word = adzworld_slot_journey_start( 'main', 3, 'car', '' );
$word = adzworld_slot_journey_show( $word, 'ebay:9' );
$word = adzworld_slot_journey_respond( $word, 'car', 'dislike', 'red', true );
$word = adzworld_slot_journey_record( $word );
journey_check( 'a word stays off the rating', $word['journey'][0]['feedback']['rating'], null );
journey_check( 'the modified string keeps the category and the refinements', $word['journey'][0]['feedback']['string'], 'car red' );

$clean = adzworld_slot_journey_sanitize( $slot );
journey_check( 'sanitize keeps the opening refinements', $clean['refinements'], 'for a nine year old' );
journey_check( 'sanitize keeps four ads', count( $clean['journey'] ), 4 );
journey_check( 'sanitize keeps ad 2 rating', $clean['journey'][1]['feedback']['rating'], -40 );

$unix  = gmmktime( 22, 5, 9, 11, 14, 2026 );
$stamp = adzworld_michel_timestamp( $unix );
journey_check( 'the michel time stamp', $stamp, '2026y-11m-14d-UTC22h-05m-09s' );
journey_check(
	'a later stamp sorts after',
	adzworld_logs_compare(
		array(
			'mts' => adzworld_michel_timestamp( $unix + 1 ),
			'n'   => 0,
		),
		array(
			'mts' => $stamp,
			'n'   => 0,
		)
	) > 0,
	true
);

$nested = adzworld_logs_place( 'bikes', 0, 'main', 1 );
$logged = adzworld_logs_entries( $nested, 'ad-11', 'ad:11', 'bicycle', 'for a nine year old', $stamp, null );
journey_check( 'a rotation in a slot is logged twice', count( $logged ), 2 );
journey_check( 'the outer row is main slot 1', $logged[0]['rotation'] === 'main' && 1 === $logged[0]['slot'], true );
journey_check( 'the outer slot is configured as the rotation', $logged[0]['configured'], 'r:bikes' );
journey_check( 'the outer slot shows what was served', $logged[0]['ad'], 'ad-11' );
journey_check( 'the outer slot holds bikes at slot 0', $logged[0]['holds'] === 'bikes' && 0 === $logged[0]['inner_slot'], true );
journey_check( 'the inner row is bikes slot 0', $logged[1]['rotation'] === 'bikes' && 0 === $logged[1]['slot'], true );
journey_check( 'the inner slot keeps its configuration', $logged[1]['configured'], 'ad:11' );
journey_check( 'both rows share the stamp', $logged[0]['mts'] === $stamp && $logged[1]['mts'] === $stamp, true );

$groups = adzworld_logs_by_rotation( $logged );
journey_check( 'the log lists main', isset( $groups['main']['slots'][1] ), true );
journey_check( 'main slot 1 showed bikes', $groups['main']['slots'][1]['holds'], 'bikes' );
journey_check( 'the log lists bikes', isset( $groups['bikes']['slots'][0] ), true );
journey_check( 'bikes slot 0 served ad-11', $groups['bikes']['slots'][0]['rows'][0]['ad'], 'ad-11' );

$one_second = adzworld_michel_timestamp( $unix + 1 );
journey_check( 'a short date is not a stamp', adzworld_logs_stamp( '2026y-11m-14d' ), '' );
journey_check( 'the range bound is the full stamp', adzworld_logs_stamp( $stamp ), '2026y-11m-14d-UTC22h-05m-09s' );
$inside = adzworld_logs_range(
	array(
		array(
			'mts'      => $stamp,
			'rotation' => 'main',
			'slot'     => 1,
			'ad'       => 'ad-11',
		),
		array(
			'mts'      => $one_second,
			'rotation' => 'main',
			'slot'     => 1,
			'ad'       => 'ad-11',
		),
	),
	$stamp,
	$stamp
);
journey_check( 'the range keeps the matching full stamp', count( $inside ), 1 );
journey_check( 'the kept row uses the full stamp', $inside[0]['mts'], '2026y-11m-14d-UTC22h-05m-09s' );
$reversed = adzworld_logs_bounds( $one_second, $stamp, false );
journey_check( 'a reversed pair is rejected', $reversed['error'], 'order' );
$short = adzworld_logs_bounds( '2026y-11m-14d', $stamp, false );
journey_check( 'a shortened bound is rejected', $short['error'], 'stamp' );
$export = adzworld_logs_export_text( array( $logged[0] ) );
journey_check( 'the export starts with the full stamp', 0 === strpos( $export, '2026y-11m-14d-UTC22h-05m-09s' ), true );

$sent = adzworld_logs_sent_string( 'source-ebay', 'bicycle', -100, 'no pedals' );
journey_check( 'the sent string is the ad, the category, the word, and the refinement', $sent, 'source-ebay bicycle Strongly dislike no pedals' );
journey_check( 'the sent string leaves the rating number out', false === strpos( $sent, '-100' ), true );

$neutral = adzworld_logs_sent_string( 'source-ebay', 'bicycle', 0, 'light fabric' );
journey_check( 'zero uses the neutral word', $neutral, 'source-ebay bicycle Neutral light fabric' );
journey_check( 'zero stays out of the sent string', false === strpos( $neutral, '0' ), true );

$open = adzworld_logs_sent_string( 'source-ebay', 'bicycle', null, 'no pedals' );
journey_check( 'an empty rating adds no word', $open, 'source-ebay bicycle no pedals' );

$external = adzworld_logs_apply_response(
	array(
		'mts'          => $stamp,
		'rotation'     => 'main',
		'slot'         => 1,
		'ad'           => 'source-ebay',
		'configured'   => 'source:ebay',
		'category'     => 'bicycle',
		'rating'       => null,
		'refinements'  => 'for a nine year old',
	),
	array(
		'category'   => 'car',
		'rating'     => -100,
		'refinement' => 'no pedals',
	),
	true
);
journey_check( 'the displayed category stays', $external['category'], 'bicycle' );
journey_check( 'the category change is stored', $external['category_change'], 'car' );
journey_check( 'the displayed rating stays empty', $external['rating'], null );
journey_check( 'the rating change is stored', $external['rating_change'], -100 );
journey_check( 'the displayed refinements stay', $external['refinements'], 'for a nine year old' );
journey_check( 'the refinement change is stored', $external['refinement_change'], 'no pedals' );
journey_check( 'the slot configuration stays', $external['configured'], 'source:ebay' );
journey_check( 'an external response stores the sent string', $external['sent_string'], 'source-ebay car Strongly dislike for a nine year old no pedals' );
journey_check( 'an external response is not only logged', $external['logged'], 0 );

$internal_row = adzworld_logs_apply_response(
	array(
		'mts'         => $stamp,
		'rotation'    => 'main',
		'slot'        => 0,
		'ad'          => 'ad-11',
		'configured'  => 'ad:11',
		'category'    => 'bicycle',
		'refinements' => 'light fabric',
	),
	array(
		'rating' => 20,
	),
	false
);
journey_check( 'an internal response is logged to the site', $internal_row['logged'], 1 );
journey_check( 'an internal response has no sent string', $internal_row['sent_string'], '' );

$same = adzworld_logs_apply_response(
	array(
		'mts'      => $stamp,
		'rotation' => 'main',
		'slot'     => 0,
		'ad'       => 'ad-11',
		'category' => 'bicycle',
	),
	array(
		'category'   => 'bicycle',
		'rating'     => 'dislike',
		'refinement' => '',
	),
	true
);
journey_check( 'an unchanged response stays unanswered', $same['responded'], 0 );
journey_check( 'a word stays off the rating change', $same['rating_change'], null );

echo "\n$pass passed, $fail failed\n";
exit( $fail > 0 ? 1 : 0 );
