<?php
require __DIR__ . '/wp-stub.php';
/*
 * The tree under test. Defaults to the claude-code-web candidate; pass another
 * candidate's path as the first argument to run the same checks against it.
 */
$adz_plugin = getenv( 'ADZ_SRC' ) ? rtrim( getenv( 'ADZ_SRC' ), '/' ) : dirname( __DIR__ );

if ( ! is_dir( $adz_plugin ) ) {
	fwrite( STDERR, "Not a directory: $adz_plugin\n" );
	exit( 2 );
}

$adz_requires = array(
	'includes/adz-preferences.php',
	'includes/adz-preference-ui.php',
);

/*
 * This harness targets specific files. A candidate that does not have them is
 * not a failure, it is simply out of scope, so it exits 3 and says so. Exiting
 * 0 here would report a pass for a test that never ran.
 */
foreach ( $adz_requires as $adz_needed ) {
	if ( ! is_file( $adz_plugin . '/' . $adz_needed ) ) {
		printf( "SKIP: %s has no %s\n", basename( $adz_plugin ), $adz_needed );
		exit( 3 );
	}
}


$base = $adz_plugin . '/includes/';
require $base . 'adz-preferences.php';
require $base . 'adz-preference-ui.php';

// The exact phrasings Dainis specified.
$inputs = array(
	"yes, that's right but the couch should be a light color and for 2 ppl",
	'i do not want to lease a car i want to purchase a used car',
	'cars above 50k only',
	'bicycles for 9 year old girls only',
);

$pass = 0;
$fail = 0;

function check( $label, $got, $want ) {
	global $pass, $fail;
	$ok = ( $got === $want );
	$ok ? $pass++ : $fail++;
	printf( "  [%s] %-34s got %-28s want %s\n", $ok ? 'ok' : 'FAIL', $label, var_export( $got, true ), var_export( $want, true ) );
}

foreach ( $inputs as $text ) {
	echo "\n> \"$text\"\n";
	$pref = adzworld_parse_refinement( $text, null );

	printf(
		"  include=%s exclude=%s attrs=%s min=%s max=%s age=%s cap=%s intent=%s\n",
		'[' . implode( ', ', $pref['include'] ) . ']',
		'[' . implode( ', ', $pref['exclude'] ) . ']',
		'[' . implode( ', ', $pref['attributes'] ) . ']',
		var_export( $pref['min_price'], true ),
		var_export( $pref['max_price'], true ),
		var_export( $pref['age'], true ),
		var_export( $pref['capacity'], true ),
		var_export( $pref['intent'], true )
	);

	$chips = adzworld_preference_chips( $pref );
	echo '  chips: ' . implode( ' / ', array_column( $chips, 'label' ) ) . "\n";

	// Cookie round trip must be lossless for the facets it carries.
	$encoded = adzworld_encode_preference( $pref );
	$decoded = adzworld_decode_preference( $encoded );
	echo '  cookie: ' . $encoded . "\n";

	$a = $pref;
	$b = $decoded;
	unset( $a['raw'], $b['raw'] );
	check( 'cookie round trip lossless', $a === $b, true );
	check( 'cookie charset restricted', (bool) preg_match( '/^[a-z0-9,:|_\-]*$/', $encoded ), true );
	check( 'sanitize_text_field idempotent', sanitize_text_field( $encoded ) === $encoded, true );
}

echo "\n=== specific expectations ===\n";

$p = adzworld_parse_refinement( "yes, that's right but the couch should be a light color and for 2 ppl", null );
check( 'couch captured', in_array( 'couch', $p['include'], true ), true );
check( 'light captured', in_array( 'light', $p['attributes'], true ), true );
check( 'capacity 2', $p['capacity'], 2 );

$p = adzworld_parse_refinement( 'i do not want to lease a car i want to purchase a used car', null );
check( 'intent is buy not lease', $p['intent'], 'buy' );
check( 'lease excluded', in_array( 'lease', $p['exclude'], true ), true );
check( 'car captured', in_array( 'car', $p['include'], true ), true );
check( 'used captured', in_array( 'used', $p['attributes'], true ), true );

$p = adzworld_parse_refinement( 'cars above 50k only', null );
check( 'min price 50000', $p['min_price'], 50000 );
check( 'no max price set', $p['max_price'], null );

$p = adzworld_parse_refinement( 'bicycles for 9 year old girls only', null );
check( 'age 9', $p['age'], 9 );
check( 'bicycles captured', in_array( 'bicycles', $p['include'], true ), true );
check( 'girls captured', in_array( 'girls', $p['attributes'], true ) || in_array( 'girls', $p['include'], true ), true );

echo "\n=== refinement accumulates onto an existing preference ===\n";
$p = adzworld_parse_refinement( 'a couch', null );
$p = adzworld_parse_refinement( 'not leather', $p );
check( 'couch survives refinement', in_array( 'couch', $p['include'], true ), true );
check( 'leather excluded', in_array( 'leather', $p['exclude'], true ), true );

$p = adzworld_remove_facet( $p, 'exclude', 'leather' );
check( 'chip removal drops exclusion', in_array( 'leather', $p['exclude'], true ), false );
check( 'chip removal keeps the rest', in_array( 'couch', $p['include'], true ), true );

echo "\n=== hostile cookie values are rebuilt, not trusted ===\n";
foreach ( array(
	'i:<script>alert(1)</script>',
	'min:-99999999999',
	't:__proto__',
	'i:' . str_repeat( 'a,', 500 ),
	'garbage without separators',
	'',
) as $hostile ) {
	$d   = adzworld_decode_preference( sanitize_text_field( $hostile ) );
	$enc = adzworld_encode_preference( $d );
	printf(
		"  %-34s -> terms=%d min=%d intent=%s re-encoded=%s\n",
		substr( $hostile, 0, 32 ),
		count( $d['include'] ),
		$d['min_price'],
		var_export( $d['intent'], true ),
		preg_match( '/^[a-z0-9,:|_\-]*$/', $enc ) ? 'safe' : 'UNSAFE'
	);
	check( 'min never negative', $d['min_price'] >= 0, true );
	check( 'terms capped at 20', count( $d['include'] ) <= 20, true );
}

echo "\n=== empty preference matches nothing ===\n";
$empty = adzworld_empty_preference();
check( 'has_preference false when empty', adzworld_has_preference( $empty ), false );
check( 'has_preference true with a term', adzworld_has_preference( adzworld_parse_refinement( 'a couch', null ) ), true );

echo "\n=== category tone comes from the name, not the position ===\n";
$names = array( 'couch', 'bicycle', 'dinner in atlanta', 'shoes', 'laptop', 'coffee', 'a', 'zzzz' );

foreach ( $names as $name ) {
	$hue = adzworld_category_tone( $name );
	printf( "  %-20s hue %d\n", $name, $hue );
	check( 'hue at or above 200', $hue >= 200, true );
	check( 'hue at or below 305', $hue <= 305, true );
}

/* The same name must give the same tone wherever it appears in a list. */
check( 'tone is stable', adzworld_category_tone( 'couch' ), adzworld_category_tone( 'couch' ) );
check( 'case does not change it', adzworld_category_tone( 'Couch' ), adzworld_category_tone( 'couch' ) );
check( 'surrounding space ignored', adzworld_category_tone( '  couch ' ), adzworld_category_tone( 'couch' ) );

/*
 * The band exists to keep category chips away from the three scoring
 * colours. Green sits near 145, yellow near 100, red near 25: none of
 * those may be reachable from any name.
 */
$lowest = 400;

foreach ( array_merge( $names, array( 'x', 'bike', 'rug', 'tv', 'hat', 'pan', 'book', 'desk', 'lamp' ) ) as $name ) {
	$lowest = min( $lowest, adzworld_category_tone( $name ) );
}

check( 'never reaches green, yellow or red', $lowest > 180, true );

echo "\n=== whatever is typed becomes a category, however long ===\n";
$typed = array(
	'couch'                                        => array( 'couch', '' ),
	'couch brown leather no arms'                  => array( 'couch brown leather no', 'arms' ),
	'dinner in atlanta vegetarian outdoor seating' => array( 'dinner in atlanta', 'vegetarian outdoor seating' ),
	'second hand mountain bike frame under 200'    => array( 'second hand mountain', 'bike frame under 200' ),
);

foreach ( $typed as $text => $want ) {
	$got = adzworld_split_category_input( $text );
	check( 'category from "' . substr( $text, 0, 22 ) . '"', $got['category'], $want[0] );
	check( 'refinement from it', $got['refinement'], $want[1] );
	check( 'the category is always valid', adzworld_valid_category( $got['category'] ), true );
	check( 'and fits a pill', mb_strlen( $got['category'] ) <= ADZWORLD_CATEGORY_MAX_LENGTH, true );
}

/* A category is no longer capped at three words, only at a pill's width. */
$got = adzworld_split_category_input( 'dinner in atlanta tonight' );
check( 'four words are one category', $got['category'], 'dinner in atlanta' );
$got = adzworld_split_category_input( 'red leather couch' );
check( 'three words stay together', $got['category'], 'red leather couch' );
check( 'with nothing pushed out', $got['refinement'], '' );

/* Words are never cut in half. */
$got = adzworld_split_category_input( 'extraordinarily comfortable chair' );
check( 'a word is never split', $got['category'], 'extraordinarily' );
check( 'the rest follows whole', $got['refinement'], 'comfortable chair' );

/* A sentence of any length is accepted, never refused. */
$long = str_repeat( 'word ', 60 );
$got  = adzworld_split_category_input( $long );
check( 'a very long sentence still gives a category', '' !== $got['category'], true );
check( 'and it is within the limits', adzworld_valid_category( $got['category'] ), true );
check( 'the rest is kept as the refinement', '' !== $got['refinement'], true );

/* A single word longer than the limit still names something. */
$huge = str_repeat( 'z', 120 );
$got  = adzworld_split_category_input( $huge );
check( 'one enormous word is cut, not refused', adzworld_valid_category( $got['category'] ), true );

/* Nothing typed gives nothing, and that is not an error either. */
$got = adzworld_split_category_input( '   ' );
check( 'empty in, empty out', $got['category'], '' );

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail > 0 ? 1 : 0 );
