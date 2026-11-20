<?php
/**
 * Named slider-theme regression checks without a WordPress install.
 */

define( 'ABSPATH', '/stub/' );

$adz_plugin = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : dirname( __DIR__, 2 ) . '/adz-world';
$adz_file   = $adz_plugin . '/includes/adzworld-responses.php';
if ( ! is_file( $adz_file ) ) {
	printf( "SKIP: %s has no includes/adzworld-responses.php\n", basename( $adz_plugin ) );
	exit( 3 );
}

$GLOBALS['adz_test_options'] = array();
$GLOBALS['adz_test_css']     = '';

class WP_Error {
	public $code;
	public $message;

	public function __construct( $code, $message ) {
		$this->code    = $code;
		$this->message = $message;
	}
}

function __( $text ) {
	return $text;
}

function sanitize_text_field( $text ) {
	return trim( strip_tags( (string) $text ) );
}

function sanitize_key( $text ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $text ) );
}

function sanitize_title( $text ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $text ) ), '-' );
}

function sanitize_html_class( $text ) {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $text );
}

function esc_url_raw( $url ) {
	return (string) $url;
}

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['adz_test_options'] ) ? $GLOBALS['adz_test_options'][ $key ] : $default;
}

function update_option( $key, $value ) {
	$GLOBALS['adz_test_options'][ $key ] = $value;
	return true;
}

function wp_upload_bits( $name, $deprecated, $bits ) {
	unset( $deprecated );
	$GLOBALS['adz_test_css'] = $bits;
	return array(
		'file'  => '/tmp/' . $name,
		'url'   => 'https://example.test/uploads/' . $name,
		'error' => false,
	);
}

function wp_delete_file( $file ) {
	unset( $file );
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

require $adz_file;

$passed = 0;
$failed = 0;

function adz_test_check( $label, $ok ) {
	global $passed, $failed;
	if ( $ok ) {
		++$passed;
	} else {
		++$failed;
	}
	printf( "  [%s] %s\n", $ok ? 'ok' : 'FAIL', $label );
}

$builtins = adzworld_ratings_slider_builtin_themes();
adz_test_check( 'Default identifier', isset( $builtins['adz-ratings-default-slider-theme'] ) );
adz_test_check( 'Tide identifier', isset( $builtins['adz-ratings-tide-slider-theme'] ) );
adz_test_check( 'Ember identifier', isset( $builtins['adz-ratings-ember-slider-theme'] ) );

$saved = adzworld_ratings_slider_save_named( 'Sunset', array( '#112233', '#445566', '#778899' ) );
adz_test_check( 'named theme saves', 'sunset' === $saved );
adz_test_check( 'three colours remain stored', array( '#112233', '#445566', '#778899' ) === $GLOBALS['adz_test_options'][ ADZWORLD_RATINGS_SLIDER_THEMES_OPTION ]['sunset']['colors'] );
adz_test_check( 'named theme becomes active', 'sunset' === $GLOBALS['adz_test_options'][ ADZWORLD_RATINGS_SLIDER_OPTION ] );
adz_test_check( 'custom theme has unique class', 'adz-ratings-custom-sunset-slider-theme' === adzworld_ratings_slider_theme_class() );
adz_test_check( 'external CSS contains custom track', false !== strpos( $GLOBALS['adz_test_css'], '.adz-ratings-custom-sunset-slider-theme input[type="range"]' ) );
adz_test_check( 'external CSS contains score endpoints', false !== strpos( $GLOBALS['adz_test_css'], 'input.is-score-minus-100' ) && false !== strpos( $GLOBALS['adz_test_css'], 'input.is-score-100' ) );
adz_test_check( 'external CSS contains admin swatch', false !== strpos( $GLOBALS['adz_test_css'], '.adzworld-bands__swatch--sunset' ) );
adz_test_check( 'built-in name stays reserved', is_wp_error( adzworld_ratings_slider_save_named( 'Default', array( '#112233', '#445566', '#778899' ) ) ) );

printf( "%d passed, %d failed\n", $passed, $failed );
exit( $failed > 0 ? 1 : 0 );
