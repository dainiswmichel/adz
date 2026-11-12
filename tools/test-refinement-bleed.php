<?php
/**
 * Prove that a refinement stays attached to its own category.
 *
 * perbeya -- permission based adz -- is session, then category, then
 * refinement, then the adz that come back. A refinement that leaks from one
 * category to another corrupts that chain: the visitor asked for a light
 * fabric couch and an electric car, and gets adz for an electric couch.
 *
 * This runs on plain PHP with no WordPress, so anyone can check the claim
 * against any candidate tree without installing anything:
 *
 *   ADZ_SRC=pre-release-candidates/claude-code-web php tools/test-refinement-bleed.php
 *
 * It is written to fail against the code it was written for. Run it against
 * pre-release-candidates/dwm-local as of main d6788ea and the bleed assertion
 * fails, which is what makes a pass here mean something.
 *
 * Exit status is 0 when every assertion holds and 1 when any fails.
 *
 * @package adz-world
 */

define( 'ABSPATH', __DIR__ );

function sanitize_text_field( $s ) {
	$s = wp_strip_all_tags( (string) $s );
	$s = preg_replace( '/[\r\n\t ]+/', ' ', $s );
	return trim( $s );
}
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
function wp_parse_args( $a, $d = array() ) { return array_merge( (array) $d, (array) $a ); }
function wp_list_pluck( $l, $f ) { return array_map( static fn( $i ) => is_array( $i ) ? $i[ $f ] : $i->$f, (array) $l ); }
function __( $t, $d = null ) { return $t; }
function number_format_i18n( $n ) { return (string) $n; }
function get_post( $id ) { return null; }
function wp_get_post_terms( $id, $tax, $args = array() ) { return array(); }
function wp_cache_get( $k, $g = '' ) { return false; }
function wp_cache_set( $k, $v, $g = '', $e = 0 ) { return true; }
function wp_cache_delete( $k, $g = '' ) { return true; }
function add_action( ...$a ) { return true; }
function add_filter( ...$a ) { return true; }
function apply_filters( $t, $v, ...$r ) { return $v; }
function do_action( ...$a ) { return null; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function absint( $n ) { return abs( (int) $n ); }
function get_option( $k, $d = false ) { return $d; }
function update_option( ...$a ) { return true; }
function is_wp_error( $t ) { return false; }


$adzworld_src = getenv( 'ADZ_SRC' );

if ( ! is_string( $adzworld_src ) || '' === $adzworld_src ) {
	fwrite( STDERR, "Set ADZ_SRC to a plugin tree, e.g. pre-release-candidates/claude-code-web\n" );
	exit( 2 );
}

require rtrim( $adzworld_src, '/' ) . '/includes/adz-preferences.php';


$pass = 0; $fail = 0;
function check( $label, $ok, $detail = '' ) {
	global $pass, $fail;
	if ( $ok ) { ++$pass; printf( "  PASS  %s\n", $label ); }
	else { ++$fail; printf( "  FAIL  %s\n        %s\n", $label, $detail ); }
}

/* ---------------------------------------------------------------------- */
echo "session -> category -> refinement -> adz : no bleeding\n\n";

/* 1. The collision that motivated the fix. */
$pref = adzworld_empty_preference();
$pref['categories']          = array( 'a', 'a--b' );
$pref['category_refinements'] = array(
	adzworld_pair_join( 'a', 'zebra' ),
	adzworld_pair_join( 'a--b', 'walrus' ),
);
$a  = adzworld_category_refinement_preference( $pref, 'a' );
$ab = adzworld_category_refinement_preference( $pref, 'a--b' );
$atext  = strtolower( implode( ' ', array_merge( $a['include'], $a['attributes'], $a['raw'] ) ) );
$abtext = strtolower( implode( ' ', array_merge( $ab['include'], $ab['attributes'], $ab['raw'] ) ) );
check(
	"category 'a' keeps its own refinement",
	false !== strpos( $atext, 'zebra' ),
	"a = $atext"
);
check(
	"category 'a' does NOT receive category 'a--b' refinement",
	false === strpos( $atext, 'walrus' ),
	"a = $atext   <-- 'walrus' belongs to 'a--b'"
);
check(
	"category 'a--b' keeps its own refinement",
	false !== strpos( $abtext, 'walrus' ),
	"a--b = $abtext"
);
check(
	"the two pairs are stored as different strings",
	$pref['category_refinements'][0] !== $pref['category_refinements'][1],
	implode( ' == ', $pref['category_refinements'] )
);

/* 2. Ordinary categories must not share refinements. */
$pref = adzworld_empty_preference();
$pref['categories']           = array( 'couch', 'car' );
$pref['category_refinements'] = array(
	adzworld_pair_join( 'couch', 'light fabric' ),
	adzworld_pair_join( 'car', 'electric' ),
);
$couch = adzworld_category_refinement_preference( $pref, 'couch' );
$car   = adzworld_category_refinement_preference( $pref, 'car' );
$ctext = strtolower( implode( ' ', array_merge( $couch['include'], $couch['attributes'], $couch['raw'] ) ) );
$rtext = strtolower( implode( ' ', array_merge( $car['include'], $car['attributes'], $car['raw'] ) ) );
check( "couch keeps 'light fabric'",        false !== strpos( $ctext, 'fabric' ),   "couch = $ctext" );
check( "couch does not receive 'electric'", false === strpos( $ctext, 'electric' ), "couch = $ctext" );
check( "car keeps 'electric'",              false !== strpos( $rtext, 'electric' ), "car = $rtext" );
check( "car does not receive 'fabric'",     false === strpos( $rtext, 'fabric' ),   "car = $rtext" );

/* 3. A category that is a prefix of another must not absorb it. */
$pref = adzworld_empty_preference();
$pref['categories']           = array( 'car', 'car cover' );
$pref['category_refinements'] = array(
	adzworld_pair_join( 'car', 'electric' ),
	adzworld_pair_join( 'car cover', 'waterproof' ),
);
$car   = adzworld_category_refinement_preference( $pref, 'car' );
$cover = adzworld_category_refinement_preference( $pref, 'car cover' );
$ctext = strtolower( implode( ' ', array_merge( $car['include'], $car['attributes'], $car['raw'] ) ) );
check( "'car' does not absorb 'car cover' refinements", false === strpos( $ctext, 'waterproof' ), "car = $ctext" );

/* 4. The separator inside a refinement survives. */
$pair = adzworld_pair_join( 'couch', 'two--seater' );
list( $cat, $ref ) = adzworld_pair_split( $pair );
check( "a refinement containing '--' round-trips", 'couch' === $cat && 'two--seater' === $ref, "$cat | $ref" );

/* 5. Colon and pipe in a category survive, per the stated convention. */
$pair = adzworld_pair_join( 'canon: eos r5', 'cheap' );
list( $cat, $ref ) = adzworld_pair_split( $pair );
check( "a category containing ':' round-trips", 'canon: eos r5' === $cat && 'cheap' === $ref, "$cat | $ref" );
$pair = adzworld_pair_join( 'a|b', 'x' );
list( $cat, $ref ) = adzworld_pair_split( $pair );
check( "a category containing '|' round-trips", 'a|b' === $cat && 'x' === $ref, "$cat | $ref" );

/* 6. The stored form must survive sanitize_text_field() unchanged. */
$pair = adzworld_pair_join( 'canon: eos r5', 'under $2,000 | not used' );
check( "the stored pair is lossless through sanitize_text_field()", sanitize_text_field( $pair ) === $pair, $pair );

/* 7. A legacy unescaped pair still reads. */
list( $cat, $ref ) = adzworld_pair_split( 'couch--light fabric' );
check( "a pair saved by an earlier version still reads", 'couch' === $cat && 'light fabric' === $ref, "$cat | $ref" );

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail > 0 ? 1 : 0 );
