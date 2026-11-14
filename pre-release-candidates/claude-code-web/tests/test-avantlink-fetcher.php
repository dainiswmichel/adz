<?php
/**
 * Standalone proof for the AvantLink ProductSearch fetcher.
 *
 * Usage: ADZ_SRC=pre-release-candidates/claude-code-web php tools/test-avantlink-fetcher.php
 *
 * No WordPress, no network, no credentials. Every assertion is about this
 * plugin's own code: what it sends, how it reads a tab-delimited body, and
 * what it does when that body is wrong.
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
	return array( 'avantlink' => $adz_account );
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
function add_query_arg( $args, $url ) {
	$pairs = array();
	foreach ( $args as $k => $v ) {
		$pairs[] = $k . '=' . $v;
	}
	return $url . '?' . implode( '&', $pairs );
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

require $adz_src . '/includes/adzworld-avantlink.php';

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
			'enabled'      => '1',
			'affiliate_id' => '123456',
			'website_id'   => '789012',
		),
		$account
	);
}

/* A body in the exact column order adzworld_avantlink_fields() asks for. */
$adz_body = implode(
	"\n",
	array(
		"Product SKU\tProduct Name\tRetail Price\tSale Price\tMedium Image\tBuy URL\tMerchant Name",
		"SKU-7781\tMid-century walnut couch\t899.00\t749.00\thttps://img.example.com/a.jpg\thttps://www.avantlink.com/click.php?tt=cl&ti=1\tHome Shop",
		"SKU-7782\tGrey linen sleeper couch\t1249.95\t\thttps://img.example.com/b.jpg\thttps://www.avantlink.com/click.php?tt=cl&ti=2\tSofa Co",
	)
);
$adz_ok   = array(
	'code' => 200,
	'body' => $adz_body,
);

echo "ADZ_SRC=$adz_src\n\n";

echo "1. Without both ids the fetcher stays out of the way\n";
foreach ( array(
	'no affiliate id' => array( 'affiliate_id' => '' ),
	'no website id'   => array( 'website_id' => '' ),
	'account off'     => array( 'enabled' => '0' ),
) as $label => $account ) {
	adz_reset( $account );
	adz_check( "$label: returns nothing", array() === adzworld_avantlink_search( 'couch' ) );
	adz_check( "$label: makes no request", 0 === count( $GLOBALS['adz_calls'] ) );
}

echo "\n2. A refinement's negation becomes AvantLink's own exclusion syntax\n";
adz_check( 'plain words pass through', 'couch grey linen' === adzworld_avantlink_search_term( 'couch grey linen' ), adzworld_avantlink_search_term( 'couch grey linen' ) );
adz_check( '"no pedals" becomes -pedals', 'bicycle -pedals' === adzworld_avantlink_search_term( 'bicycle no pedals' ), adzworld_avantlink_search_term( 'bicycle no pedals' ) );
adz_check( '"without arms" becomes -arms', 'couch -arms' === adzworld_avantlink_search_term( 'couch without arms' ), adzworld_avantlink_search_term( 'couch without arms' ) );
adz_check( 'only the next word is negated', 'bicycle -pedals pink' === adzworld_avantlink_search_term( 'bicycle no pedals pink' ), adzworld_avantlink_search_term( 'bicycle no pedals pink' ) );
adz_check( 'an empty request yields an empty term', '' === adzworld_avantlink_search_term( '   ' ) );
adz_check( 'tags are stripped before anything is sent', false === strpos( adzworld_avantlink_search_term( '<b>couch</b>' ), '<' ), adzworld_avantlink_search_term( '<b>couch</b>' ) );

echo "\n3. What goes over the wire\n";
adz_reset( array(), array( $adz_ok ) );
$rows = adzworld_avantlink_search( 'couch no arms' );
$url  = $GLOBALS['adz_calls'][0][1];
adz_check( 'the documented endpoint is used', 0 === strpos( $url, 'https://classic.avantlink.com/api.php?' ), $url );
adz_check( 'the ProductSearch module is named', false !== strpos( $url, 'module=ProductSearch' ) );
adz_check( 'the affiliate id is sent', false !== strpos( $url, 'affiliate_id=123456' ) );
adz_check( 'the website id is sent', false !== strpos( $url, 'website_id=789012' ) );
adz_check( 'the negated term is sent, url-encoded', false !== strpos( $url, 'search_term=couch%20-arms' ), $url );
adz_check( 'tab output is asked for, so columns are positional', false !== strpos( $url, 'output=tab' ) );
adz_check( 'advanced syntax is enabled, or the minus would be literal', false !== strpos( $url, 'search_advanced_syntax=1' ) );
adz_check( 'the column list is named explicitly', false !== strpos( $url, 'Product%20SKU' ) && false !== strpos( $url, 'Buy%20URL' ), $url );
adz_check( 'no reader identifier is attached', false === strpos( $url, 'session' ) && false === strpos( $url, 'cookie' ) );

echo "\n4. Reading the body\n";
adz_check( 'both products returned, header row skipped', 2 === count( $rows ), count( $rows ) . ' rows' );
adz_check( 'title read', 'Mid-century walnut couch' === $rows[0]['title'], $rows[0]['title'] );
adz_check( 'the tracked Buy URL is the destination', false !== strpos( $rows[0]['url'], 'avantlink.com/click.php' ), $rows[0]['url'] );
adz_check( 'the sale price is preferred over retail', '$749.00' === $rows[0]['price'], $rows[0]['price'] );
adz_check( 'retail is used when there is no sale price', '$1249.95' === $rows[1]['price'], $rows[1]['price'] );
adz_check( 'image read', 'https://img.example.com/a.jpg' === $rows[0]['image'] );
adz_check( 'the product SKU is kept as the row identifier', 'SKU-7781' === $rows[0]['id'], $rows[0]['id'] );

echo "\n5. Caching\n";
$before = count( $GLOBALS['adz_calls'] );
$again  = adzworld_avantlink_search( 'couch no arms' );
$after  = count( $GLOBALS['adz_calls'] );
adz_check( 'the same request makes no second call', $after === $before, "$after calls" );
adz_check( 'and returns the same rows', $rows === $again );
$ttls = array();
foreach ( $GLOBALS['adz_calls'] as $call ) {
	if ( 'set_transient' === $call[0] ) {
		$ttls[] = $call[2];
	}
}
adz_check( 'results cached for 15 minutes', in_array( 900, $ttls, true ), implode( ',', $ttls ) );

echo "\n6. Every way the body can be wrong\n";
foreach ( array(
	'transport error'        => array( 'adz_transport_error' => true ),
	'a 500'                  => array(
		'code' => 500,
		'body' => '',
	),
	'an empty body'          => array(
		'code' => 200,
		'body' => '',
	),
	'only a header row'      => array(
		'code' => 200,
		'body' => "Product Name\tRetail Price\tSale Price\tMedium Image\tBuy URL\tMerchant Name",
	),
	'too few columns'        => array(
		'code' => 200,
		'body' => "Some couch\t10.00",
	),
	'a row with no Buy URL'  => array(
		'code' => 200,
		'body' => "Some couch\t10.00\t\thttps://img/a.jpg\t\tShop",
	),
	'a row with no title'    => array(
		'code' => 200,
		'body' => "\t10.00\t\thttps://img/a.jpg\thttps://www.avantlink.com/click.php\tShop",
	),
	'an HTML error document' => array(
		'code' => 200,
		'body' => '<html><body>Invalid affiliate id</body></html>',
	),
) as $label => $response ) {
	adz_reset( array(), array( $response ) );
	$out = adzworld_avantlink_search( 'couch' );
	adz_check( "$label: returns an empty list, no crash", array() === $out, wp_json_encode_shim( $out ) );
}

echo "\n7. What AvantLink hands to the shared collector\n";
adz_reset( array(), array( $adz_ok ) );
$appended = adzworld_avantlink_append_product_results( array( array( 'title' => 'prior' ) ), 'couch' );
adz_check( 'earlier rows are preserved', 'prior' === $appended[0]['title'] );
adz_check( 'three rows in total', 3 === count( $appended ), count( $appended ) . ' rows' );
adz_check( 'each row names avantlink as its provider', 'avantlink' === $appended[1]['provider'] );

echo "\n8. Registration\n";
$networks = adzworld_avantlink_register_network( array() );
adz_check( 'the credential fields are registered', isset( $networks['avantlink']['fields']['affiliate_id'], $networks['avantlink']['fields']['website_id'] ) );
adz_check( 'only numeric ids are accepted', 1 === preg_match( $networks['avantlink']['fields']['affiliate_id']['pattern'], '123456' ) && 0 === preg_match( $networks['avantlink']['fields']['affiliate_id']['pattern'], 'abc' ) );
adz_check( 'it is marked as taking the reader request', true === adzworld_avantlink_register_request_behavior( array() )['avantlink'] );
adz_check( 'readiness answers only for avantlink', 'untouched' === adzworld_avantlink_filter_account_ready( 'untouched', 'ebay', array() ) );
adz_check(
	'readiness is true with both ids',
	true === adzworld_avantlink_filter_account_ready(
		false,
		'avantlink',
		array(
			'enabled'      => '1',
			'affiliate_id' => '1',
			'website_id'   => '2',
		)
	)
);
adz_check(
	'readiness is false with one id',
	false === adzworld_avantlink_filter_account_ready(
		false,
		'avantlink',
		array(
			'enabled'      => '1',
			'affiliate_id' => '1',
			'website_id'   => '',
		)
	)
);

function wp_json_encode_shim( $value ) {
	return json_encode( $value );
}

echo "\n----\n$adz_pass passed, $adz_fail failed\n";
exit( $adz_fail > 0 ? 1 : 0 );
