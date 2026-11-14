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
$journey = adzworld_journey_add( $journey, 'etsy:2', 'strongly dislike', '' );
$journey = adzworld_journey_add( $journey, 'apple:3', 'neutral', 'no pedals' );
$journey = adzworld_journey_add( $journey, 'bestbuy:4', 'like', 'light fabric' );
$journey = adzworld_journey_add( $journey, 'avantlink:5', 'strongly like', '' );
$journey = adzworld_journey_add( $journey, 'ebay:1', 'dislike', 'small wheels' );

journey_check(
	'search string is the category and the typed refinements',
	adzworld_journey_keywords( $journey ),
	'bicycle for a nine year old no pedals light fabric small wheels'
);

$record = adzworld_journey_record( $journey );

journey_check( 'rounds are numbered from 1', $record['rounds'][0]['round'], 1 );
journey_check( 'sixth round stays', $record['rounds'][5]['round'], 6 );
journey_check( 'an untouched control is no response', $record['rounds'][0]['rating'], 'no response' );
journey_check( 'neutral stays neutral', $record['rounds'][2]['rating'], 'neutral' );
journey_check( 'strongly dislike stays strongly dislike', $record['rounds'][1]['rating'], 'strongly dislike' );
journey_check( 'strongly like stays strongly like', $record['rounds'][4]['rating'], 'strongly like' );
journey_check( 'no pedals is kept whole', $record['rounds'][2]['refinement'], 'no pedals' );

$seen = adzworld_journey_seen( $journey );
journey_check( 'a repeated ad id is listed once', $seen, array( 'ebay:1', 'etsy:2', 'apple:3', 'bestbuy:4', 'avantlink:5' ) );

$before = $journey;
$journey = adzworld_journey_add( $journey, 'ebay:9', 'like a lot', 'red' );
journey_check( 'like a lot is not stored as another word', $journey, $before );

$journey = adzworld_journey_add( $journey, 'ebay:9', -100, 'red' );
journey_check( 'a number is not stored as a rating word', count( $journey['rounds'] ), 6 );

$long = adzworld_journey_start( 'couch' );
$i    = 0;
while ( $i < 12 ) {
	++$i;
	$long = adzworld_journey_add( $long, 'ebay:' . $i, 'like', 'round ' . $i );
}
journey_check( 'twelve rounds are kept', count( $long['rounds'] ), 12 );

echo "\n$pass passed, $fail failed\n";
exit( $fail > 0 ? 1 : 0 );
