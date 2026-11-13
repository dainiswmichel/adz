<?php
/**
 * Standalone proof for the Travelpayouts fetcher.
 *
 * Usage: ADZ_SRC=pre-release-candidates/claude-code-web php tools/test-travelpayouts-fetcher.php
 *
 * The response shapes are those in Travelpayouts' published Data API
 * documentation, and the places lookup was exercised live against
 * autocomplete.travelpayouts.com during development.
 *
 * @package adz-world
 */

// phpcs:disable WordPress.Security.EscapeOutput -- CLI harness: output goes to a terminal, not a page.
// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DevelopmentFunctions -- stubs stand in for the WordPress functions under test.
// phpcs:disable WordPress.WP.GlobalVariablesOverride -- $adz_* globals are this harness's own state.
// phpcs:disable Squiz.Commenting, Generic.CodeAnalysis.UnusedFunctionParameter -- stub signatures must match WordPress.

define( 'ABSPATH', __DIR__ );

$adz_src       = getenv( 'ADZ_SRC' );
$adz_src       = $adz_src ? rtrim( $adz_src, '/' ) : 'pre-release-candidates/claude-code-web';
$adz_calls     = array();
$adz_store     = array();
$adz_responses = array();
$adz_account   = array();
$adz_pass      = 0;
$adz_fail      = 0;

function adzworld_affiliate_accounts() {
	global $adz_account;
	return array( 'travelpayouts' => $adz_account );
}
function get_transient( $key ) {
	global $adz_store;
	return isset( $adz_store[ $key ] ) ? $adz_store[ $key ] : false;
}
function set_transient( $key, $value, $ttl ) {
	global $adz_store, $adz_calls;
	$adz_store[ $key ] = $value;
	$adz_calls[]       = array( 'set_transient', $key, $ttl );
	return true;
}
function wp_remote_get( $url, $args ) {
	global $adz_calls, $adz_responses;
	$adz_calls[] = array( 'get', $url, $args );
	return array_shift( $adz_responses );
}
function is_wp_error( $thing ) {
	return is_array( $thing ) && isset( $thing['adz_transport_error'] );
}
function wp_remote_retrieve_response_code( $r ) {
	return isset( $r['code'] ) ? $r['code'] : 0;
}
function wp_remote_retrieve_body( $r ) {
	return isset( $r['body'] ) ? $r['body'] : '';
}
function wp_strip_all_tags( $v ) {
	return strip_tags( (string) $v );
}
function __( $text, $domain = null ) {
	return $text;
}
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
	global $adz_filters;
	$adz_filters[ $hook ][] = $callback;
}
function add_query_arg() {
	$a = func_get_args();
	if ( 3 === count( $a ) ) {
		$args = array( $a[0] => $a[1] );
		$url  = $a[2];
	} else {
		$args = $a[0];
		$url  = $a[1];
	}
	$pairs = array();
	foreach ( $args as $k => $v ) {
		$pairs[] = $k . '=' . $v;
	}
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . implode( '&', $pairs );
}

require __DIR__ . '/../' . $adz_src . '/includes/adzworld-travelpayouts.php';

function adz_check( $label, $ok, $saw = '' ) {
	global $adz_pass, $adz_fail;
	if ( $ok ) {
		++$adz_pass;
		echo "  ok    $label\n";
		return;
	}
	++$adz_fail;
	echo "  FAIL  $label\n";
	if ( '' !== $saw ) {
		echo "        saw: $saw\n";
	}
}

function adz_reset( $account, $responses = array() ) {
	global $adz_calls, $adz_store, $adz_responses, $adz_account;
	$adz_calls     = array();
	$adz_store     = array();
	$adz_responses = $responses;
	$adz_account   = array_merge(
		array(
			'enabled'  => '1',
			'token'    => 'abcdef0123456789abcdef',
			'origin'   => 'RIX',
			'marker'   => '123456',
			'currency' => 'eur',
		),
		$account
	);
}

/* The places lookup, as autocomplete.travelpayouts.com really answers it. */
$adz_place = array(
	'code' => 200,
	'body' => '[{"code":"LIS","name":"Lisbon","country_code":"PT"}]',
);

/* The fare shape from the published Data API documentation. */
$adz_fares = array(
	'code' => 200,
	'body' => wp_json_encode_shim(
		array(
			'success' => true,
			'data'    => array(
				'LIS' => array(
					'0' => array(
						'price'         => 189,
						'airline'       => 'TP',
						'flight_number' => 651,
						'departure_at'  => '2099-03-11T06:20:00Z',
						'expires_at'    => '2099-04-01T00:00:00Z',
					),
					'1' => array(
						'price'        => 420,
						'airline'      => 'AF',
						'departure_at' => '2099-03-14T09:00:00Z',
						'expires_at'   => '2099-04-01T00:00:00Z',
					),
				),
			),
		)
	),
);

echo "ADZ_SRC=$adz_src\n\n";

echo "1. Each refinement becomes an explicit parameter\n";
$p = adzworld_travelpayouts_parse_request( 'flights to lisbon' );
adz_check( 'the place is extracted from the instructions', 'lisbon' === $p['place'], $p['place'] );
adz_check( 'no month is assumed', '' === $p['month'] );
adz_check( 'no stop preference is assumed', false === $p['direct'] );

$p = adzworld_travelpayouts_parse_request( 'cheap direct flight to lisbon in march under 300' );
adz_check( 'the place survives every modifier', 'lisbon' === $p['place'], $p['place'] );
adz_check( '"march" becomes a departure month', 1 === preg_match( '/^[0-9]{4}-03$/', $p['month'] ), $p['month'] );
adz_check( '"direct" is recognised', true === $p['direct'] );
adz_check( '"under 300" becomes a price ceiling', 300 === $p['max_price'], (string) $p['max_price'] );

adz_check( '"non-stop" is recognised too', true === adzworld_travelpayouts_parse_request( 'lisbon non-stop' )['direct'] );
adz_check( 'a request with no place yields no place', '' === adzworld_travelpayouts_parse_request( 'cheap direct flights' )['place'] );
adz_check( 'an empty request yields nothing', '' === adzworld_travelpayouts_parse_request( '  ' )['place'] );

echo "\n2. Without a token or an origin the fetcher stays out of the way\n";
foreach ( array(
	'no token'    => array( 'token' => '' ),
	'no origin'   => array( 'origin' => '' ),
	'account off' => array( 'enabled' => '0' ),
) as $label => $account ) {
	adz_reset( $account );
	adz_check( "$label: returns nothing", array() === adzworld_travelpayouts_search( 'lisbon' ) );
	adz_check( "$label: makes no request", 0 === count( $GLOBALS['adz_calls'] ) );
}

echo "\n3. A place with no IATA code is not guessed at\n";
adz_reset(
	array(),
	array(
		array(
			'code' => 200,
			'body' => '[]',
		),
	)
);
adz_check( 'an unknown place returns no fares', array() === adzworld_travelpayouts_search( 'nowheresville' ) );
adz_check(
	'and no fare request follows the failed lookup',
	1 === count(
		array_filter(
			$GLOBALS['adz_calls'],
			function ( $c ) {
				return 'get' === $c[0];
			}
		)
	)
);

echo "\n4. What goes over the wire\n";
adz_reset( array(), array( $adz_place, $adz_fares ) );
$rows = adzworld_travelpayouts_search( 'flights to lisbon in march' );
$gets = array_values(
	array_filter(
		$GLOBALS['adz_calls'],
		function ( $c ) {
			return 'get' === $c[0];
		}
	)
);
adz_check( 'the place lookup is called first, with no credential', false !== strpos( $gets[0][1], 'autocomplete.travelpayouts.com/places2' ) && ! isset( $gets[0][2]['headers'] ), $gets[0][1] );
adz_check( 'the cheap-fares endpoint is used by default', false !== strpos( $gets[1][1], '/v1/prices/cheap' ), $gets[1][1] );
adz_check( 'the resolved IATA code is the destination', false !== strpos( $gets[1][1], 'destination=LIS' ), $gets[1][1] );
adz_check( "the publisher's origin is sent", false !== strpos( $gets[1][1], 'origin=RIX' ) );
adz_check( 'the month becomes depart_date', 1 === preg_match( '/depart_date=[0-9]{4}-03/', $gets[1][1] ), $gets[1][1] );
adz_check( 'the chosen currency is sent', false !== strpos( $gets[1][1], 'currency=eur' ) );
adz_check( 'the token travels in the header, not the query string', isset( $gets[1][2]['headers']['x-access-token'] ) && false === strpos( $gets[1][1], 'token=' ), $gets[1][1] );

echo "\n5. \"direct\" switches the endpoint, it is not a filter\n";
adz_reset( array(), array( $adz_place, $adz_fares ) );
adzworld_travelpayouts_search( 'direct flight to lisbon' );
$gets = array_values(
	array_filter(
		$GLOBALS['adz_calls'],
		function ( $c ) {
			return 'get' === $c[0];
		}
	)
);
adz_check( 'the non-stop endpoint is called instead', false !== strpos( $gets[1][1], '/v1/prices/direct' ), $gets[1][1] );
adz_check( 'and never the cheap one', false === strpos( $gets[1][1], '/v1/prices/cheap' ) );

echo "\n6. Reading the fares\n";
adz_reset( array(), array( $adz_place, $adz_fares ) );
$rows = adzworld_travelpayouts_search( 'flights to lisbon in march' );
adz_check( 'both fares returned', 2 === count( $rows ), count( $rows ) . ' rows' );
adz_check( 'the route is named in the title', false !== strpos( $rows[0]['title'], 'RIX to LIS' ), $rows[0]['title'] );
adz_check( 'the departure date is in the title', false !== strpos( $rows[0]['title'], '2099-03-11' ), $rows[0]['title'] );
adz_check( 'the price carries its currency', 'EUR 189' === $rows[0]['price'], $rows[0]['price'] );
adz_check( 'the booking link carries the marker', false !== strpos( $rows[0]['url'], 'marker=123456' ), $rows[0]['url'] );
adz_check( 'the link names both airports', false !== strpos( $rows[0]['url'], 'origin_iata=RIX' ) && false !== strpos( $rows[0]['url'], 'destination_iata=LIS' ) );
adz_check( 'the internal raw price never reaches a card', ! isset( $rows[0]['raw_price'] ) );

echo "\n7. A price ceiling the reader stated\n";
adz_reset( array(), array( $adz_place, $adz_fares ) );
$capped = adzworld_travelpayouts_search( 'flights to lisbon in march under 300' );
adz_check( 'the fare above the ceiling is dropped', 1 === count( $capped ), count( $capped ) . ' rows' );
adz_check( 'the one within it is kept', 'EUR 189' === $capped[0]['price'], $capped[0]['price'] );

echo "\n8. An expired fare is not advertised\n";
adz_reset(
	array(),
	array(
		$adz_place,
		array(
			'code' => 200,
			'body' => '{"success":true,"data":{"LIS":{"0":{"price":99,"departure_at":"2015-06-09T21:20:00Z","expires_at":"2015-01-08T18:30:40Z"}}}}',
		),
	)
);
adz_check( 'a fare past its expiry is dropped', array() === adzworld_travelpayouts_search( 'lisbon' ) );

echo "\n9. Every way the response can be wrong\n";
foreach ( array(
	'transport error'      => array( 'adz_transport_error' => true ),
	'a 401'                => array(
		'code' => 401,
		'body' => '',
	),
	'success false'        => array(
		'code' => 200,
		'body' => '{"success":false,"data":null,"error":"bad token"}',
	),
	'no data'              => array(
		'code' => 200,
		'body' => '{"success":true,"data":{}}',
	),
	'data not a list'      => array(
		'code' => 200,
		'body' => '{"success":true,"data":"wat"}',
	),
	'a fare with no price' => array(
		'code' => 200,
		'body' => '{"success":true,"data":{"LIS":{"0":{"airline":"TP"}}}}',
	),
	'a non-json body'      => array(
		'code' => 200,
		'body' => 'Service unavailable',
	),
) as $label => $response ) {
	adz_reset( array(), array( $adz_place, $response ) );
	$out = adzworld_travelpayouts_search( 'lisbon' );
	adz_check( "$label: returns an empty list, no crash", array() === $out, wp_json_encode_shim( $out ) );
}

echo "\n10. The collector contract and registration\n";
adz_reset( array(), array( $adz_place, $adz_fares ) );
$appended = adzworld_travelpayouts_append_product_results( array( array( 'title' => 'prior' ) ), 'lisbon in march' );
adz_check( 'earlier rows are preserved', 'prior' === $appended[0]['title'] );
adz_check( 'each row names travelpayouts as its provider', 'travelpayouts' === $appended[1]['provider'] );
adz_check( 'readiness answers only for travelpayouts', 'untouched' === adzworld_travelpayouts_filter_account_ready( 'untouched', 'ebay', array() ) );
adz_check(
	'readiness needs a token and an origin',
	false === adzworld_travelpayouts_filter_account_ready(
		false,
		'travelpayouts',
		array(
			'enabled' => '1',
			'token'   => 'x',
			'origin'  => '',
		)
	)
);
adz_check( 'earning needs the marker', false === adzworld_travelpayouts_filter_account_earns( false, 'travelpayouts', array( 'marker' => '' ) ) );
adz_check( 'and is true once the marker is saved', true === adzworld_travelpayouts_filter_account_earns( false, 'travelpayouts', array( 'marker' => '123' ) ) );
adz_check( 'it is marked as taking the reader request', true === adzworld_travelpayouts_register_request_behavior( array() )['travelpayouts'] );

function wp_json_encode_shim( $value ) {
	return json_encode( $value );
}

echo "\n----\n$adz_pass passed, $adz_fail failed\n";
exit( $adz_fail > 0 ? 1 : 0 );
