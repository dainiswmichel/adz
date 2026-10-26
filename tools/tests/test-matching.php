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


// A tiny catalogue of adz, keyed by id, so the matcher has something to rank.
$GLOBALS['adz_catalogue'] = array(
	11 => 'Used Toyota Corolla for sale, 2019, great condition',
	12 => 'Lease a new BMW 3 series, low monthly payments',
	13 => 'Light grey two seater fabric couch, free delivery',
	14 => 'Brown leather three seater sofa, clearance',
	15 => 'Kids bicycle, 20 inch, suits ages 8 to 11',
	16 => 'Garden shed assembly service',
);

class WP_Post {
	public $ID;
	public $post_title;
	public $post_content;
}
function get_post( $id = 0 ) {
	$id = (int) $id;
	if ( ! isset( $GLOBALS['adz_catalogue'][ $id ] ) ) {
		return null;
	}
	$p = new WP_Post();
	$p->ID = $id;
	$p->post_title = $GLOBALS['adz_catalogue'][ $id ];
	$p->post_content = '';
	return $p;
}
// adz_get_all_ad_ids() is NOT stubbed. Stubbing it is what hid a fatal: the
// plugin called it, nothing defined it, and these tests passed anyway. The
// plugin's own implementation now runs here, against stubs for the WordPress
// functions it depends on.
define( 'MINUTE_IN_SECONDS', 60 );

function get_posts( $args = array() ) {
	$GLOBALS['adz_get_posts_args'] = $args;
	$out = array();
	foreach ( $GLOBALS['adz_catalogue'] as $id => $title ) {
		$out[] = get_post( $id );
	}
	return array_slice( $out, 0, isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : -1 );
}
function wp_list_pluck( $rows, $field ) {
	return array_map(
		function ( $r ) use ( $field ) {
			return is_object( $r ) ? $r->$field : $r[ $field ];
		},
		$rows
	);
}
function wp_cache_get( $key, $group = '' ) {
	return $GLOBALS['adz_cache'][ $group ][ $key ] ?? false;
}
function wp_cache_set( $key, $value, $group = '', $ttl = 0 ) {
	$GLOBALS['adz_cache'][ $group ][ $key ] = $value;
	$GLOBALS['adz_cache_writes'] = ( $GLOBALS['adz_cache_writes'] ?? 0 ) + 1;
	return true;
}
function wp_cache_delete( $key, $group = '' ) {
	unset( $GLOBALS['adz_cache'][ $group ][ $key ] );
	return true;
}
$GLOBALS['adz_cache'] = array();

$base = $adz_plugin . '/includes/';
require $base . 'adz-preferences.php';
require $base . 'adz-preference-ui.php';

$pass = 0; $fail = 0;
function check( $label, $got, $want ) {
	global $pass, $fail;
	$ok = ( $got === $want );
	$ok ? $pass++ : $fail++;
	printf( "  [%s] %-46s got %s\n", $ok ? 'ok' : 'FAIL', $label, var_export( $got, true ) );
}
function titles( $ids ) {
	return array_map( function ( $i ) { return $GLOBALS['adz_catalogue'][ $i ]; }, $ids );
}

echo "=== a visitor who has typed nothing still gets adz ===\n";
$empty = adzworld_empty_preference();
$ids = adzworld_match_adz( $empty, 3 );
check( 'no preference returns adz', count( $ids ) > 0, true );
check( 'returns up to the limit', count( $ids ), 3 );
check( 'original order preserved', $ids, array( 11, 12, 13 ) );

echo "\n=== count=0 means none, which is the shortcode's job not the matcher's ===\n";
check( 'matcher never returns zero on request', count( adzworld_match_adz( $empty, 1 ) ), 1 );

echo "\n=== a preference reorders rather than gates ===\n";
$p = adzworld_parse_refinement( 'a light couch for 2 ppl', null );
$ids = adzworld_match_adz( $p, 6 );
echo '  ' . implode( "\n  ", titles( $ids ) ) . "\n";
check( 'best match first', $ids[0], 13 );
check( 'unmatched adz still present', count( $ids ), 6 );

echo "\n=== an exclusion is absolute, not a demotion ===\n";
$p = adzworld_parse_refinement( 'a couch but not leather', null );
$ids = adzworld_match_adz( $p, 6 );
echo '  ' . implode( "\n  ", titles( $ids ) ) . "\n";
check( 'leather sofa excluded entirely', in_array( 14, $ids, true ), false );
check( 'fabric couch kept', in_array( 13, $ids, true ), true );

echo "\n=== the car example ranks buying a used car over leasing ===\n";
$p = adzworld_parse_refinement( 'i do not want to lease a car i want to purchase a used car', null );
$ids = adzworld_match_adz( $p, 6 );
echo '  ' . implode( "\n  ", titles( $ids ) ) . "\n";
check( 'used car first', $ids[0], 11 );
check( 'lease listing excluded', in_array( 12, $ids, true ), false );

echo "\n=== ties keep catalogue order on every run ===\n";
$runs = array();
for ( $i = 0; $i < 25; $i++ ) {
	$runs[] = implode( ',', adzworld_match_adz( $empty, 6 ) );
}
check( 'ordering is deterministic', count( array_unique( $runs ) ), 1 );

echo "\n=== the plugin's own adz_get_all_ad_ids() runs, not a stub ===\n";
$GLOBALS['adz_cache']        = array();
$GLOBALS['adz_cache_writes'] = 0;
$ids = adz_get_all_ad_ids();
check( 'returns the published adz', $ids, array( 11, 12, 13, 14, 15, 16 ) );
check( 'caches on first call', $GLOBALS['adz_cache_writes'], 1 );
adz_get_all_ad_ids();
check( 'second call served from cache', $GLOBALS['adz_cache_writes'], 1 );
adzworld_flush_ad_pool();
adz_get_all_ad_ids();
check( 'flush forces a refetch', $GLOBALS['adz_cache_writes'], 2 );
check( 'pool size is capped', $GLOBALS['adz_get_posts_args']['posts_per_page'], 200 );
check( 'asks for published adz only', $GLOBALS['adz_get_posts_args']['post_status'], 'publish' );
check( 'asks for the adz post type', $GLOBALS['adz_get_posts_args']['post_type'], 'adz_ad' );
check( 'requests objects so the post cache primes', isset( $GLOBALS['adz_get_posts_args']['fields'] ), false );

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail > 0 ? 1 : 0 );
