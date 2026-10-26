<?php
require __DIR__ . '/wp-stub.php';
/*
 * The tree under test. Defaults to the claude-code-web candidate; pass another
 * candidate's path as the first argument to run the same checks against it.
 */
$adz_plugin = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : dirname( __DIR__, 2 ) . '/pre-release-candidates/claude-code-web';

if ( ! is_dir( $adz_plugin ) ) {
	fwrite( STDERR, "Not a directory: $adz_plugin\n" );
	exit( 2 );
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
	$d = adzworld_decode_preference( sanitize_text_field( $hostile ) );
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

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail > 0 ? 1 : 0 );
