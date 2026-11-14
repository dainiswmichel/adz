<?php
/**
 * Standalone proof for the Apple Search fetcher.
 *
 * Usage: ADZ_SRC=pre-release-candidates/claude-code-web php tools/test-itunes-fetcher.php
 *
 * The response bodies here are abridged from real responses captured from
 * itunes.apple.com during development, so the field names are the live ones
 * rather than names taken from memory.
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
	return array( 'itunes' => $adz_account );
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
/* WordPress accepts both add_query_arg( array, url ) and ( key, value, url ). */
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

require $adz_src . '/includes/adzworld-itunes.php';

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
			'enabled' => '1',
			'media'   => 'music',
			'at'      => '',
		),
		$account
	);
}

/* Abridged from a real itunes.apple.com response. */
$adz_body = wp_json_encode_shim(
	array(
		'resultCount' => 3,
		'results'     => array(
			array(
				'trackName'     => 'Jazz Piano',
				'trackViewUrl'  => 'https://music.apple.com/us/album/jazz-piano/998128716?i=998128730&uo=4',
				'artworkUrl100' => 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/9f/95/c6/x/100x100bb.jpg',
				'trackPrice'    => 0.99,
				'currency'      => 'USD',
			),
			array(
				'collectionName'    => 'Piano - The Jazz Piano Trio Creations',
				'collectionViewUrl' => 'https://music.apple.com/us/album/piano/1566104376?uo=4',
				'artworkUrl100'     => 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/80/8f/ca/y/100x100bb.jpg',
				'collectionPrice'   => 11.61,
				'currency'          => 'USD',
			),
			array(
				'trackName'    => 'Free Track',
				'trackViewUrl' => 'https://music.apple.com/us/album/free/1?uo=4',
				'trackPrice'   => -1,
				'currency'     => 'USD',
			),
		),
	)
);
$adz_ok   = array(
	'code' => 200,
	'body' => $adz_body,
);

echo "ADZ_SRC=$adz_src\n\n";

echo "1. It is off unless the publisher turns it on\n";
adz_reset( array( 'enabled' => '0' ) );
adz_check( 'a disabled account returns nothing', array() === adzworld_itunes_search( 'jazz' ) );
adz_check( 'and makes no request', 0 === count( $GLOBALS['adz_calls'] ) );
adz_reset( array() );
adz_check( 'an empty request returns nothing', array() === adzworld_itunes_search( '  ' ) );
adz_check( 'and makes no request', 0 === count( $GLOBALS['adz_calls'] ) );

echo "\n2. No credential is required to serve products\n";
adz_reset( array( 'at' => '' ), array( $adz_ok ) );
$rows = adzworld_itunes_search( 'jazz piano trio' );
adz_check( 'products return with no token saved', 3 === count( $rows ), count( $rows ) . ' rows' );
adz_check( 'the request carries no key of any kind', false === strpos( $GLOBALS['adz_calls'][0][1], 'key' ), $GLOBALS['adz_calls'][0][1] );
adz_check( 'links are the plain store urls', false === strpos( $rows[0]['url'], 'at=' ), $rows[0]['url'] );

echo "\n3. What goes over the wire\n";
$url = $GLOBALS['adz_calls'][0][1];
adz_check( 'the documented endpoint is used', 0 === strpos( $url, 'https://itunes.apple.com/search?' ), $url );
adz_check( 'the reader request is the term, url-encoded', false !== strpos( $url, 'term=jazz%20piano%20trio' ), $url );
adz_check( 'the chosen catalogue is sent', false !== strpos( $url, 'media=music' ) );
adz_check( 'the result count is capped', false !== strpos( $url, 'limit=3' ) );
adz_check( 'no reader identifier is attached', false === strpos( $url, 'session' ) && false === strpos( $url, 'cookie' ) );

echo "\n4. Reading the response\n";
adz_check( 'a track name is read', 'Jazz Piano' === $rows[0]['title'], $rows[0]['title'] );
adz_check( 'a collection name is read when there is no track name', 'Piano - The Jazz Piano Trio Creations' === $rows[1]['title'], $rows[1]['title'] );
adz_check( 'the price carries its currency', 'USD 0.99' === $rows[0]['price'], $rows[0]['price'] );
adz_check( 'a collection price is read', 'USD 11.61' === $rows[1]['price'], $rows[1]['price'] );
adz_check( 'cover art is requested at a size worth rendering', false !== strpos( $rows[0]['image'], '400x400bb' ), $rows[0]['image'] );
/*
 * A priceless item is kept, not dropped. Apple returns -1 for an item with no
 * purchasable price, and a free track is still a real product; it renders
 * with no price rather than with a nonsense one.
 */
adz_check( 'a priceless item is kept and shows no price', '' === $rows[2]['price'], var_export( $rows[2]['price'], true ) );
adz_check( 'and it is never shown as costing -1', false === strpos( wp_json_encode_shim( $rows ), '-1' ), wp_json_encode_shim( $rows ) );

echo "\n5. The affiliate token, when the publisher has one\n";
adz_reset( array( 'at' => '1000lABCD' ), array( $adz_ok ) );
$tracked = adzworld_itunes_search( 'jazz piano trio' );
adz_check( 'the token is appended to the store link', false !== strpos( $tracked[0]['url'], 'at=1000lABCD' ), $tracked[0]['url'] );
adz_check( 'the original query string is kept intact', false !== strpos( $tracked[0]['url'], 'i=998128730' ) && false !== strpos( $tracked[0]['url'], 'uo=4' ), $tracked[0]['url'] );

echo "\n6. Caching\n";
$before = count( $GLOBALS['adz_calls'] );
$again  = adzworld_itunes_search( 'jazz piano trio' );
$after  = count( $GLOBALS['adz_calls'] );
adz_check( 'the same request makes no second call', $after === $before, "$after calls" );
adz_check( 'and returns the same rows', $tracked === $again );
$ttls = array();
foreach ( $GLOBALS['adz_calls'] as $call ) {
	if ( 'set_transient' === $call[0] ) {
		$ttls[] = $call[2];
	}
}
adz_check( 'results cached for 15 minutes', in_array( 900, $ttls, true ), implode( ',', $ttls ) );

echo "\n7. Every way the response can be wrong\n";
foreach ( array(
	'transport error'     => array( 'adz_transport_error' => true ),
	'a 503'               => array(
		'code' => 503,
		'body' => '',
	),
	'a non-json body'     => array(
		'code' => 200,
		'body' => 'Error 400',
	),
	'no results'          => array(
		'code' => 200,
		'body' => '{"resultCount":0,"results":[]}',
	),
	'results not a list'  => array(
		'code' => 200,
		'body' => '{"results":"wat"}',
	),
	'an item is a scalar' => array(
		'code' => 200,
		'body' => '{"results":[7]}',
	),
	'an item has no url'  => array(
		'code' => 200,
		'body' => '{"results":[{"trackName":"No link"}]}',
	),
	'an item has no name' => array(
		'code' => 200,
		'body' => '{"results":[{"trackViewUrl":"https://music.apple.com/x"}]}',
	),
) as $label => $response ) {
	adz_reset( array(), array( $response ) );
	$out = adzworld_itunes_search( 'jazz' );
	adz_check( "$label: returns an empty list, no crash", array() === $out, wp_json_encode_shim( $out ) );
}

echo "\n8. What it hands to the shared collector, and its registration\n";
adz_reset( array(), array( $adz_ok ) );
$appended = adzworld_itunes_append_product_results( array( array( 'title' => 'prior' ) ), 'jazz' );
adz_check( 'earlier rows are preserved', 'prior' === $appended[0]['title'] );
adz_check( 'each row names itunes as its provider', 'itunes' === $appended[1]['provider'] );
adz_check( 'readiness answers only for itunes', 'untouched' === adzworld_itunes_filter_account_ready( 'untouched', 'ebay', array() ) );
adz_check( 'enabling it is the whole requirement', true === adzworld_itunes_filter_account_ready( false, 'itunes', array( 'enabled' => '1' ) ) );
adz_check( 'a disabled account is not ready', false === adzworld_itunes_filter_account_ready( true, 'itunes', array( 'enabled' => '0' ) ) );
$networks = adzworld_itunes_register_network( array() );
adz_check( 'the catalogue choices are registered', in_array( 'music', $networks['itunes']['fields']['media']['choices'], true ) );
adz_check( 'it is marked as taking the reader request', true === adzworld_itunes_register_request_behavior( array() )['itunes'] );

function wp_json_encode_shim( $value ) {
	return json_encode( $value );
}

echo "\n----\n$adz_pass passed, $adz_fail failed\n";
exit( $adz_fail > 0 ? 1 : 0 );
