<?php
/*
 * Rotation ordering. Extracts adzworld_build_rotation() from the candidate and
 * exercises its spacing, so the assertions run against the shipped source
 * rather than a copy that can drift from it.
 *
 * Defaults to the claude-code-web candidate; pass another candidate's path as
 * the first argument.
 */
$adz_plugin = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : dirname( __DIR__, 2 ) . '/pre-release-candidates/claude-code-web';

if ( ! is_dir( $adz_plugin ) ) {
	fwrite( STDERR, "Not a directory: $adz_plugin\n" );
	exit( 2 );
}

$src = file_get_contents( $adz_plugin . '/includes/adz-rotation.php' );
preg_match( '/function adzworld_build_rotation.*?\n}/s', $src, $m );
eval( $m[0] );


$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

/**
 * Record one invariant.
 *
 * @param string $label What is being asserted.
 * @param bool   $ok    Whether it held.
 * @return void
 */
function assert_that( $label, $ok ) {
	if ( $ok ) {
		++$GLOBALS['pass'];
		return;
	}

	++$GLOBALS['fail'];
	printf( "  [FAIL] %s\n", $label );
}

function slot( $n ) { return array( 'type' => 'network', 'label' => 'n' . $n ); }
function adz( $n ) { return array( 'type' => 'adz', 'label' => 'A' . $n ); }

$cases = array(
	array( 1, 0 ), array( 1, 1 ), array( 9, 1 ), array( 9, 2 ), array( 9, 3 ),
	array( 2, 3 ), array( 0, 2 ), array( 0, 0 ), array( 4, 1 ), array( 24, 4 ),
);

foreach ( $cases as list( $n, $a ) ) {
	$net = array(); for ( $i = 1; $i <= $n; $i++ ) { $net[] = slot( $i ); }
	$ads = array(); for ( $i = 1; $i <= $a; $i++ ) { $ads[] = adz( $i ); }

	$out = adzworld_build_rotation( $net, $ads, 12 );
	$seq = implode( ' ', array_column( $out, 'label' ) );

	$net_out = count( array_filter( $out, fn( $s ) => 'network' === $s['type'] ) );
	$adz_out = count( array_filter( $out, fn( $s ) => 'adz' === $s['type'] ) );
	$dupes   = count( array_column( $out, 'label' ) ) - count( array_unique( array_column( $out, 'label' ) ) );
	$first   = $out ? $out[0]['type'] : '-';

	printf(
		"net=%-2d adz=%-2d -> %-2d slides | kept %d/%d net, %d/%d adz | dupes=%d | first=%s | %s\n",
		$n, $a, count( $out ), $net_out, min( $n, 12 ), $adz_out, min( $a, 12 ), $dupes, $first, $seq
	);

	// Invariants that must hold for every combination.
	assert_that( "net=$n adz=$a: no slide repeated", 0 === $dupes );
	assert_that( "net=$n adz=$a: within the cap", count( $out ) <= 12 );

	if ( $n > 0 && $a > 0 ) {
		assert_that( "net=$n adz=$a: a publisher slot goes first", 'network' === $first );
		assert_that( "net=$n adz=$a: at least one adz placed", $adz_out > 0 );
	}

	if ( $n > 0 && 0 === $a ) {
		assert_that( "net=$n adz=$a: no adz appear", 0 === $adz_out );
	}

	// No two adz adjacent while publisher slots remain unplaced.
	$types = array_column( $out, 'type' );
	for ( $i = 1; $i < count( $types ); $i++ ) {
		if ( 'adz' === $types[ $i ] && 'adz' === $types[ $i - 1 ] ) {
			$remaining = count( array_slice( $types, $i ) );
			$nets_left = count( array_filter( array_slice( $types, $i ), function ( $t ) { return 'network' === $t; } ) );
			assert_that( "net=$n adz=$a: adz only adjacent once slots are spent", 0 === $nets_left );
		}
	}
}

printf( "\n%d passed, %d failed\n", $GLOBALS['pass'], $GLOBALS['fail'] );
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
