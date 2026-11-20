<?php
/*
 * Rotation parsing and weighted selection. Extracts the current rotation
 * functions from the tree under test so the assertions exercise shipped code.
 */
$adz_plugin = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : dirname( __DIR__, 2 ) . '/adz-world';
$rotation   = $adz_plugin . '/includes/adzworld-rotation.php';

if ( ! is_file( $rotation ) ) {
	printf( "SKIP: %s has no includes/adzworld-rotation.php\n", basename( $adz_plugin ) );
	exit( 3 );
}

$src = file_get_contents( $rotation );
foreach ( array( 'adzworld_parse_rotation', 'adzworld_rotation_weighted_slot' ) as $function ) {
	$matched = preg_match( '/function ' . $function . '.*?\n}/s', $src, $match );
	if ( 1 !== $matched ) {
		fwrite( STDERR, "Could not extract $function from $rotation\n" );
		exit( 2 );
	}
	eval( $match[0] );
}

if ( ! defined( 'ADZWORLD_ROTATION_MAX_SLOTS' ) ) {
	define( 'ADZWORLD_ROTATION_MAX_SLOTS', 200 );
}

class WP_Error {
	private $code;

	public function __construct( $code ) {
		$this->code = $code;
	}

	public function get_error_code() {
		return $this->code;
	}
}

function __( $text ) {
	return $text;
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
}

function adzworld_rotation_sources() {
	return array();
}

function adzworld_local_ad_response() {
	return array();
}

function shortcode_exists() {
	return false;
}

function current_user_can() {
	return false;
}

$pass = 0;
$fail = 0;

function check( $label, $got, $want ) {
	global $pass, $fail;
	$ok = $got === $want;
	$ok ? ++$pass : ++$fail;
	printf( "  [%s] %-44s got %s\n", $ok ? 'ok' : 'FAIL', $label, var_export( $got, true ) );
}

$slots = adzworld_parse_rotation( 'ad:11:2,intro:1,ad:12:3' );
check( 'valid rotation parses', is_wp_error( $slots ), false );
check( 'invitation moves to the first position', array_column( $slots, 'type' ), array( 'intro', 'ad', 'ad' ) );
check( 'advertising order remains stable', array_column( $slots, 'value' ), array( '', '11', '12' ) );
check( 'configured weights remain attached', array_column( $slots, 'weight' ), array( 1, 2, 3 ) );
check( 'original positions remain attached', array_column( $slots, 'position' ), array( 1, 0, 2 ) );

$draws = array();
for ( $draw = 1; $draw <= 6; ++$draw ) {
	$selected = adzworld_rotation_weighted_slot( $slots, $draw );
	$draws[]  = $selected['type'] . ':' . $selected['value'];
}
check(
	'weighted draws select the configured slots',
	$draws,
	array( 'intro:', 'ad:11', 'ad:11', 'ad:12', 'ad:12', 'ad:12' )
);

$invalid_weight = adzworld_parse_rotation( 'intro:0' );
check( 'zero weight is rejected', $invalid_weight->get_error_code(), 'adzworld_rotation_weight' );
$excessive_weight = adzworld_parse_rotation( 'intro:101' );
check( 'weight above 100 is rejected', $excessive_weight->get_error_code(), 'adzworld_rotation_limit' );
$unknown_type = adzworld_parse_rotation( 'unknown:item' );
check( 'unknown slot type is rejected', $unknown_type->get_error_code(), 'adzworld_rotation_type' );
$outside_draw = adzworld_rotation_weighted_slot( $slots, 7 );
check( 'draw outside total weight is rejected', $outside_draw->get_error_code(), 'adzworld_rotation_draw' );

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail > 0 ? 1 : 0 );
