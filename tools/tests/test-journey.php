<?php
/**
 * Slot journey record for the grok-4-7 candidate.
 *
 * Run: php tools/tests/test-journey.php [path-to-candidate]
 */

define( 'ABSPATH', '/stub/' );
define( 'ADZWORLD_REFINEMENT_MAX_LENGTH', 10000 );

function wp_strip_all_tags( $str ) {
	return trim( strip_tags( (string) $str ) );
}

$candidate = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : dirname( __DIR__, 2 ) . '/pre-release-candidates/grok-4-7';
$file      = $candidate . '/includes/adzworld-journey.php';

if ( ! is_file( $file ) ) {
	fwrite( STDERR, "Missing $file\n" );
	exit( 2 );
}

require $file;

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

	printf(
		"  [%s] %s\n",
		$ok ? 'ok' : 'FAIL',
		$label
	);

	if ( ! $ok ) {
		echo '       got  ' . var_export( $got, true ) . "\n";
		echo '       want ' . var_export( $want, true ) . "\n";
	}
}

$journey = adzworld_journey_start( 'bicycle' );
$journey = adzworld_journey_add( $journey, 'ebay:1', null, 'for a nine year old' );
$journey = adzworld_journey_add( $journey, 'etsy:2', -100, '' );
$journey = adzworld_journey_add( $journey, 'apple:3', 0, 'no pedals' );
$journey = adzworld_journey_add( $journey, 'bestbuy:4', 40, 'light fabric' );
$journey = adzworld_journey_add( $journey, 'avantlink:5', 100, '' );
$journey = adzworld_journey_add( $journey, 'ebay:1', -20, 'small wheels' );

journey_check(
	'search string is the category and the typed refinements',
	adzworld_journey_keywords( $journey ),
	'bicycle for a nine year old no pedals light fabric small wheels'
);

$record = adzworld_journey_record( $journey );

journey_check( 'rounds are numbered from 1', $record['rounds'][0]['round'], 1 );
journey_check( 'second ad id follows the first', $record['rounds'][1]['ad'], 'etsy:2' );
journey_check( 'sixth round stays', $record['rounds'][5]['round'], 6 );
journey_check( 'an untouched control stays empty', $record['rounds'][0]['rating'], null );
journey_check( 'zero stays zero', $record['rounds'][2]['rating'], 0 );
journey_check( 'minus 100 stays minus 100', $record['rounds'][1]['rating'], -100 );
journey_check( 'one hundred stays one hundred', $record['rounds'][4]['rating'], 100 );
journey_check( 'forty stays forty', $record['rounds'][3]['rating'], 40 );
journey_check( 'minus 20 stays minus 20', $record['rounds'][5]['rating'], -20 );
journey_check( 'no pedals is kept whole', $record['rounds'][2]['refinement'], 'no pedals' );
journey_check( 'an untouched control translates to no response', $record['rounds'][0]['translation'], 'no response' );
journey_check( 'minus 100 translates to strongly dislike', $record['rounds'][1]['translation'], 'strongly dislike' );
journey_check( 'zero translates to neutral', $record['rounds'][2]['translation'], 'neutral' );
journey_check( 'forty translates to like', $record['rounds'][3]['translation'], 'like' );
journey_check( 'one hundred translates to strongly like', $record['rounds'][4]['translation'], 'strongly like' );
journey_check( 'minus 20 translates through the existing neutral band', $record['rounds'][5]['translation'], 'neutral' );

$seen = adzworld_journey_seen( $journey );
journey_check( 'a repeated ad id is listed once', $seen, array( 'ebay:1', 'etsy:2', 'apple:3', 'bestbuy:4', 'avantlink:5' ) );

$before = $journey;
$journey = adzworld_journey_add( $journey, 'ebay:9', 'dislike', 'red' );
journey_check( 'a word in the rating argument does not become a number', $journey, $before );

$journey = adzworld_journey_add( $journey, 'ebay:9', 101, 'red' );
journey_check( 'a number past 100 is not stored', count( $journey['rounds'] ), 6 );

$journey = adzworld_journey_add( $journey, 'ebay:9', '-40', 'red' );
journey_check( 'a numeric string is stored as that integer', $journey['rounds'][6]['rating'], -40 );
journey_check( 'minus 40 translates to dislike', $journey['rounds'][6]['translation'], 'dislike' );

$span = adzworld_journey_start( 'couch' );
$span = adzworld_journey_add( $span, 'ebay:a', -100, '' );
$span = adzworld_journey_add( $span, 'ebay:b', -80, '' );
$span = adzworld_journey_record( $span );
journey_check( 'minus 100 stays specific beside the shared translation', $span['rounds'][0]['rating'], -100 );
journey_check( 'minus 80 stays specific beside the shared translation', $span['rounds'][1]['rating'], -80 );
journey_check( 'the shared translation is strongly dislike', $span['rounds'][0]['translation'], 'strongly dislike' );
journey_check( 'minus 80 uses that same translation', $span['rounds'][1]['translation'], 'strongly dislike' );

$journey = adzworld_journey_add( $journey, 'ebay:10', -100, 'red', 'dislike' );
journey_check( 'a collected translation stays on the round', $journey['rounds'][7]['translation'], 'dislike' );
journey_check( 'the collected translation leaves minus 100 in place', $journey['rounds'][7]['rating'], -100 );

$journey = adzworld_journey_add( $journey, 'ebay:11', 90, '', 'like a lot' );
journey_check( 'like a lot is stored as strongly like', $journey['rounds'][8]['translation'], 'strongly like' );
journey_check( 'ninety stays ninety', $journey['rounds'][8]['rating'], 90 );

$journey = adzworld_journey_add( $journey, 'ebay:12', 40, 'soft', 'banana' );
journey_check( 'an unrecognized translation uses the band of the number', $journey['rounds'][9]['translation'], 'like' );
journey_check( 'the number stays when the translation is unrecognized', $journey['rounds'][9]['rating'], 40 );

$keys    = adzworld_journey_keywords( $journey );
$journey = adzworld_journey_add( $journey, 'ebay:13', null, '', 'like' );
$last    = count( $journey['rounds'] ) - 1;
journey_check( 'a collected word on an empty rating stays empty as a number', $journey['rounds'][ $last ]['rating'], null );
journey_check( 'that collected word stays on the round', $journey['rounds'][ $last ]['translation'], 'like' );
journey_check( 'the translation stays out of the search string', adzworld_journey_keywords( $journey ), $keys );

journey_check(
	'the search string keeps each typed refinement',
	$keys,
	'bicycle for a nine year old no pedals light fabric small wheels red red soft'
);

$clean = adzworld_journey_sanitize( $journey );
journey_check(
	'sanitize keeps the number and its translation',
	$clean['rounds'][1],
	array(
		'ad'          => 'etsy:2',
		'rating'      => -100,
		'translation' => 'strongly dislike',
		'refinement'  => '',
	)
);

$dirty = array(
	'request' => 'car',
	'rounds'  => array(
		array(
			'ad'          => 'ebay:1',
			'rating'      => 10,
			'translation' => 'banana',
			'refinement'  => 'no pedals',
		),
		array(
			'ad'         => 'ebay:2',
			'rating'     => 'dislike',
			'refinement' => 'red',
		),
	),
);
$dirty = adzworld_journey_sanitize( $dirty );
journey_check( 'sanitize keeps ten', $dirty['rounds'][0]['rating'], 10 );
journey_check( 'sanitize fills an unrecognized translation from the number', $dirty['rounds'][0]['translation'], 'neutral' );
journey_check( 'sanitize keeps no pedals whole', $dirty['rounds'][0]['refinement'], 'no pedals' );
journey_check( 'a stored word in the rating field is not turned into a number', count( $dirty['rounds'] ), 1 );

$long = adzworld_journey_start( 'couch' );
$i    = 0;
while ( $i < 12 ) {
	++$i;
	$long = adzworld_journey_add( $long, 'ebay:' . $i, $i, 'round ' . $i );
}
journey_check( 'twelve rounds are kept', count( $long['rounds'] ), 12 );

echo "\n$pass passed, $fail failed\n";
exit( $fail > 0 ? 1 : 0 );
