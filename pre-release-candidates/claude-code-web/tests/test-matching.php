<?php
/**
 * Matching and scoring checks for one candidate tree.
 *
 * @package adz-world
 */

// phpcs:disable WordPress.Security.EscapeOutput -- CLI harness: output goes to a terminal, not a page.
// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DevelopmentFunctions -- stubs stand in for the WordPress functions under test.
// phpcs:disable WordPress.WP.GlobalVariablesOverride -- $adz_* globals are this harness's own state.
// phpcs:disable Squiz.Commenting, Generic.Files.OneObjectStructurePerFile, Universal.Files.SeparateFunctionsFromOO, Generic.CodeAnalysis.UnusedFunctionParameter, WordPress.Files.FileName -- stub signatures and the stub WP_Post must match WordPress.

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
	$p               = new WP_Post();
	$p->ID           = $id;
	$p->post_title   = $GLOBALS['adz_catalogue'][ $id ];
	$p->post_content = '';
	return $p;
}
// adzworld_get_all_ad_ids() is NOT stubbed. Stubbing it is what hid a fatal: the
// plugin called it, nothing defined it, and these tests passed anyway. The
// plugin's own implementation now runs here, against stubs for the WordPress
// functions it depends on.
define( 'MINUTE_IN_SECONDS', 60 );

function get_posts( $args = array() ) {
	$GLOBALS['adz_get_posts_args'] = $args;
	$out                           = array();
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
	$GLOBALS['adz_cache_writes']            = ( $GLOBALS['adz_cache_writes'] ?? 0 ) + 1;
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

$pass                    = 0;
$fail                    = 0;
$GLOBALS['adz_feedback'] = array();

/**
 * The reader's ratings, as the plugin reads them.
 *
 * @return array<string,array<string,int>> Ratings by target key.
 */
function adzworld_current_feedback() {
	return $GLOBALS['adz_feedback'];
}

function check( $label, $got, $want ) {
	global $pass, $fail;
	$ok = ( $got === $want );
	$ok ? $pass++ : $fail++;
	printf( "  [%s] %-46s got %s\n", $ok ? 'ok' : 'FAIL', $label, var_export( $got, true ) );
}
function titles( $ids ) {
	return array_map(
		function ( $i ) {
			return $GLOBALS['adz_catalogue'][ $i ];
		},
		$ids
	);
}

echo "=== a visitor who has typed nothing still gets adz ===\n";
$empty = adzworld_empty_preference();
$ids   = adzworld_match_adz( $empty, 3 );
check( 'no preference returns adz', count( $ids ) > 0, true );
check( 'returns up to the limit', count( $ids ), 3 );
check( 'original order preserved', $ids, array( 11, 12, 13 ) );

echo "\n=== count=0 means none, which is the shortcode's job not the matcher's ===\n";
check( 'matcher never returns zero on request', count( adzworld_match_adz( $empty, 1 ) ), 1 );

echo "\n=== a preference reorders rather than gates ===\n";
$p   = adzworld_parse_refinement( 'a light couch for 2 ppl', null );
$ids = adzworld_match_adz( $p, 6 );
echo '  ' . implode( "\n  ", titles( $ids ) ) . "\n";
check( 'best match first', $ids[0], 13 );
check( 'unmatched adz still present', count( $ids ), 6 );

echo "\n=== an exclusion is absolute, not a demotion ===\n";
$p   = adzworld_parse_refinement( 'a couch but not leather', null );
$ids = adzworld_match_adz( $p, 6 );
echo '  ' . implode( "\n  ", titles( $ids ) ) . "\n";
check( 'leather sofa excluded entirely', in_array( 14, $ids, true ), false );
check( 'fabric couch kept', in_array( 13, $ids, true ), true );

echo "\n=== the car example ranks buying a used car over leasing ===\n";
$p   = adzworld_parse_refinement( 'i do not want to lease a car i want to purchase a used car', null );
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

echo "\n=== the plugin's own adzworld_get_all_ad_ids() runs, not a stub ===\n";
$GLOBALS['adz_cache']        = array();
$GLOBALS['adz_cache_writes'] = 0;
$ids                         = adzworld_get_all_ad_ids();
check( 'returns the published adz', $ids, array( 11, 12, 13, 14, 15, 16 ) );
check( 'caches on first call', $GLOBALS['adz_cache_writes'], 1 );
adzworld_get_all_ad_ids();
check( 'second call served from cache', $GLOBALS['adz_cache_writes'], 1 );
adzworld_flush_ad_pool();
adzworld_get_all_ad_ids();
check( 'flush forces a refetch', $GLOBALS['adz_cache_writes'], 2 );
check( 'pool size is capped', $GLOBALS['adz_get_posts_args']['posts_per_page'], 200 );
check( 'asks for published adz only', $GLOBALS['adz_get_posts_args']['post_status'], 'publish' );
check( 'asks for the adz post type', $GLOBALS['adz_get_posts_args']['post_type'], 'adz_ad' );
check( 'requests objects so the post cache primes', isset( $GLOBALS['adz_get_posts_args']['fields'] ), false );

echo "\n=== a refinement changes which adzad is shown ===\n";
/*
 * The claim: the loop works on internal adz alone, with no network and no
 * API. A refinement is scoped to a category, so it has to reorder the adz
 * inside that category and must not reach adz in another one.
 */
$GLOBALS['adz_catalogue'] = array(
	21 => 'Kids bicycle, 20 inch, suits ages 8 to 11',
	22 => 'Toddler balance bicycle, pink, ages 2 to 5',
	23 => 'Light grey two seater fabric couch, free delivery',
);
adzworld_flush_ad_pool();

$base               = adzworld_empty_preference();
$base['categories'] = array( 'bicycle' );
$before             = adzworld_match_adz( $base, 3 );

$refined                         = $base;
$refined['category_refinements'] = array( adzworld_pair_join( 'bicycle', 'pink' ) );
$after                           = adzworld_match_adz( $refined, 3 );

check( 'with no refinement the newest bicycle adzad leads', $before[0], 21 );
check( 'after refining, the matching adzad leads instead', $after[0], 22 );
check( 'nothing is dropped, only reordered', count( $after ), count( $before ) );

/*
 * The bug this covers: adzworld_ad_thing() guessed "ages" from these titles
 * and that guess outranked the category the visitor stated, so refinements
 * were filed under "bicycle" while the adzad was scored as "ages" and nothing
 * matched. The visitor's own category wins now.
 */
check( 'the adzad takes the category the visitor stated', adzworld_ad_category( get_post( 21 ), $base ), 'bicycle' );

/*
 * The title guesser is gone. An adzad outside every stated category now has
 * no category rather than one invented from the last noun in its title. The
 * stub returns no ad_taxonomy terms, so this is the no-term case.
 */
check( 'an adzad outside every stated category has no category', adzworld_ad_category( get_post( 21 ), adzworld_empty_preference() ), '' );
check( 'nothing guesses a category from the title', function_exists( 'adzworld_ad_thing' ), false );
check( 'the assigned-category reader exists under its own name', function_exists( 'adzworld_ad_assigned_category' ), true );

/*
 * A refinement stated for one category must not score adz in another, or a
 * slot drifts away from what was asked for in the slot it was asked in.
 */
$couch               = adzworld_empty_preference();
$couch['categories'] = array( 'couch' );
$couch_refined       = $couch;

$couch_refined['category_refinements'] = array( adzworld_pair_join( 'bicycle', 'pink' ) );
check( 'a bicycle refinement does not rank a couch differently', adzworld_rank_ad( 23, $couch_refined ), adzworld_rank_ad( 23, $couch ) );

/* "no pedals" is read as a negation, so it excludes rather than demotes. */
$GLOBALS['adz_catalogue'][24] = 'Junior bicycle with pedals and training wheels';
adzworld_flush_ad_pool();
$negated                         = $base;
$negated['category_refinements'] = array( adzworld_pair_join( 'bicycle', 'no pedals' ) );
check( 'an adzad the visitor ruled out is removed', in_array( 24, adzworld_match_adz( $negated, 4 ), true ), false );
check( 'and the others are kept', in_array( 22, adzworld_match_adz( $negated, 4 ), true ), true );

echo "\n=== the reader's rating is used on the scale they set it on ===\n";
/*
 * It used to be divided by ten before being added to a sum of invented
 * constants, so 201 notches collapsed to 21 and a full -100 moved the result
 * by the same amount that one matching word added.
 */
$GLOBALS['adz_catalogue'] = array(
	31 => 'Kids bicycle, 20 inch, blue',
	32 => 'Kids bicycle, 20 inch, red',
);
adzworld_flush_ad_pool();

$rated               = adzworld_empty_preference();
$rated['categories'] = array( 'bicycle' );

$GLOBALS['adz_feedback'] = array();
check( 'unrated, the first one leads', adzworld_match_adz( $rated, 2 )[0], 31 );

$GLOBALS['adz_feedback'] = array( 'ad-31' => array( 'score' => -4 ) );
check( 'a rating of -4 reorders, where it used to round to zero', adzworld_match_adz( $rated, 2 )[0], 32 );

$GLOBALS['adz_feedback'] = array(
	'ad-31' => array( 'score' => 95 ),
	'ad-32' => array( 'score' => 100 ),
);
check( '95 is distinguishable from 100', adzworld_match_adz( $rated, 2 )[0], 32 );

$GLOBALS['adz_feedback'] = array(
	'ad-31' => array( 'score' => 100 ),
	'ad-32' => array( 'score' => 95 ),
);
check( 'and the order follows the reader, not the catalogue', adzworld_match_adz( $rated, 2 )[0], 31 );

/*
 * A reader who moved the control to the middle engaged. One who never touched
 * it did not. Both used to rank as 0 and the difference was lost. Engagement
 * sits below the rating, so it only ever decides a tie: an adzad rated below
 * zero must not outrank an unrated one just for having been touched.
 */
$GLOBALS['adz_feedback'] = array( 'ad-32' => array( 'score' => 0 ) );
check( 'a chosen zero outranks an adzad never rated', adzworld_match_adz( $rated, 2 )[0], 32 );
check( 'the chosen zero is marked as engaged', adzworld_rank_ad( 32, $rated )['engaged'], 1 );
check( 'the untouched one is not', adzworld_rank_ad( 31, $rated )['engaged'], 0 );

$GLOBALS['adz_feedback'] = array( 'ad-31' => array( 'score' => -4 ) );
check( 'and engagement never outranks a rating', adzworld_match_adz( $rated, 2 )[0], 32 );

echo "\n=== a term matches on word boundaries, not as a substring ===\n";
$GLOBALS['adz_catalogue'] = array(
	41 => 'Bicycle catalogue, 2026 edition',
	42 => 'Bicycle with a cat basket on the handlebars',
	43 => 'Plain bicycle, no basket',
);
adzworld_flush_ad_pool();
$GLOBALS['adz_feedback'] = array();

$ruled               = adzworld_empty_preference();
$ruled['categories'] = array( 'bicycle' );
$ruled['exclude']    = array( 'cat' );
$survivors           = adzworld_match_adz( $ruled, 3 );
check( 'the adzad with a cat basket is ruled out', in_array( 42, $survivors, true ), false );
check( 'the catalogue is not, because "cat" is not a word in it', in_array( 41, $survivors, true ), true );
check( 'and an unrelated adzad is untouched', in_array( 43, $survivors, true ), true );

/*
 * Not fixed, and stated so it is not mistaken for fixed: an adzad whose own
 * copy reads "no pedals" is still ruled out by a reader excluding "pedals",
 * because the word is genuinely there. Reading the negation in an adzad's
 * own copy means interpreting it, which is not done here.
 */
$GLOBALS['adz_catalogue'] = array( 44 => 'Toddler balance bicycle, no pedals, pink' );
adzworld_flush_ad_pool();
$known               = adzworld_empty_preference();
$known['categories'] = array( 'bicycle' );
$known['exclude']    = array( 'pedals' );
check( 'known limitation: "no pedals" copy is still excluded by "pedals"', in_array( 44, adzworld_match_adz( $known, 2 ), true ), false );

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail > 0 ? 1 : 0 );
