<?php
/**
 * Prevent the retired revenue-share and statement service from returning.
 *
 * @package adz-world-tests
 */

$adz_plugin = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : dirname( __DIR__, 2 ) . '/adz-world';
if ( ! is_dir( $adz_plugin ) ) {
	printf( "SKIP: %s is not a plugin directory\n", $adz_plugin );
	exit( 3 );
}

$forbidden = array(
	'adzworld_earnings_connection',
	'adzworld_monetization_consent',
	'adzworld_split_commission',
	'wp-json/adz-world/v1/earnings',
	'paypal_email',
	'retains 10%',
	'publisher share is 90%',
	'read-only statement token',
);
$contents  = '';
$iterator  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $adz_plugin ) );

foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || ! in_array( $file->getExtension(), array( 'html', 'js', 'php', 'txt' ), true ) ) {
		continue;
	}
	$contents .= file_get_contents( $file->getPathname() );
}

$pass = 0;
$fail = 0;
foreach ( $forbidden as $term ) {
	if ( false === stripos( $contents, $term ) ) {
		++$pass;
		printf( "  [ok] retired term is absent: %s\n", $term );
	} else {
		++$fail;
		printf( "  [FAIL] retired term returned: %s\n", $term );
	}
}

$required = array(
	'https://dainis.net/donate/',
	'Buy Dainis a coffee',
);
foreach ( $required as $term ) {
	if ( false !== strpos( $contents, $term ) ) {
		++$pass;
		printf( "  [ok] donation support remains: %s\n", $term );
	} else {
		++$fail;
		printf( "  [FAIL] donation support is missing: %s\n", $term );
	}
}

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail > 0 ? 1 : 0 );
