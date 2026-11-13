<?php
/**
 * Regression checks for the shared product collector and showcase slide.
 *
 * @package adz-world
 */

define( 'ABSPATH', __DIR__ );

$adz_filters = array();
$adz_pass    = 0;
$adz_fail    = 0;

function add_action() {}
function add_filter( $hook, $callback, $priority = 10 ) {
	global $adz_filters;
	$adz_filters[ $hook ][ $priority ][] = $callback;
}
function apply_filters( $hook, $value ) {
	global $adz_filters;
	$args = func_get_args();
	array_shift( $args );
	if ( empty( $adz_filters[ $hook ] ) ) {
		return $value;
	}
	ksort( $adz_filters[ $hook ] );
	foreach ( $adz_filters[ $hook ] as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$args[0] = call_user_func_array( $callback, $args );
		}
	}
	return $args[0];
}
function wp_strip_all_tags( $value ) {
	return strip_tags( (string) $value );
}
function esc_url_raw( $value ) {
	return filter_var( $value, FILTER_VALIDATE_URL ) ? $value : '';
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function esc_url( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}
function __( $value ) {
	return $value;
}
function esc_html__( $value ) {
	return esc_html( $value );
}
function esc_attr__( $value ) {
	return esc_attr( $value );
}

require dirname( __DIR__, 2 ) . '/pre-release-candidates/dwm-local/includes/adzworld-products.php';
require dirname( __DIR__, 2 ) . '/pre-release-candidates/dwm-local/includes/adzworld-affiliates.php';

/* Replace live provider callbacks with deterministic rows for this harness. */
$adz_filters['adzworld_affiliate_product_results'] = array();
$adz_calls                                          = 0;

add_filter(
	'adzworld_affiliate_product_results',
	function ( $rows ) use ( &$adz_calls ) {
		++$adz_calls;
		for ( $i = 1; $i <= 4; ++$i ) {
			$rows[] = array(
				'provider' => 'travelpayouts',
				'title'    => 'RIX to VIE ' . $i,
				'image'    => '',
				'price'    => 'EUR ' . ( 90 + $i ),
				'url'      => 'https://example.com/flight-' . $i,
			);
		}
		for ( $i = 1; $i <= 4; ++$i ) {
			$rows[] = array(
				'provider' => 'ebay',
				'title'    => 'Offer ' . $i,
				'image'    => 'https://example.com/image-' . $i . '.jpg',
				'price'    => '$' . $i,
				'url'      => 1 === $i ? 'https://example.com/flight-1' : 'https://example.com/offer-' . $i,
			);
		}
		return $rows;
	},
	10,
	2
);

function adz_assert( $condition, $label ) {
	global $adz_pass, $adz_fail;
	if ( $condition ) {
		++$adz_pass;
		echo "ok  - {$label}\n";
		return;
	}
	++$adz_fail;
	echo "FAIL - {$label}\n";
}

$results = adzworld_affiliate_product_results( 'Riga to Vienna' );
adz_assert( 1 === $adz_calls, 'the provider filter runs once' );
adz_assert( 6 === count( $results ), 'the shared six-card cap applies' );
adz_assert( 'travelpayouts' === $results[0]['provider'], 'the first provider is retained' );
adz_assert( 'ebay' === $results[1]['provider'], 'providers are interleaved' );
adz_assert( count( array_unique( array_column( $results, 'url' ) ) ) === count( $results ), 'duplicate destinations are removed' );
adz_assert( 'Travelpayouts' === $results[0]['source'], 'the route carries its provider label' );

$route = adzworld_affiliate_result_slide( $results[0], 0, count( $results ) );
adz_assert( false !== strpos( $route, 'RIX to VIE 1' ), 'an image-free route renders' );
adz_assert( false !== strpos( $route, 'adzworld-results__offer--text' ), 'an image-free route uses the text layout' );
adz_assert( false === strpos( $route, '<img' ), 'an image-free route has no empty image element' );
adz_assert( false !== strpos( $route, 'Travelpayouts' ), 'the route displays its provider' );

$product = adzworld_affiliate_result_slide( $results[1], 1, count( $results ) );
adz_assert( false !== strpos( $product, '<img' ), 'an image product keeps its image' );

echo "\n{$adz_pass} passed, {$adz_fail} failed\n";
exit( $adz_fail > 0 ? 1 : 0 );
