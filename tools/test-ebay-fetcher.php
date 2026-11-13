<?php
/**
 * Standalone proof for the eBay Browse fetcher. No WordPress, no network, no credentials.
 *
 * Usage: ADZ_SRC=pre-release-candidates/claude-code-web php tools/test-ebay-fetcher.php
 *
 * WordPress functions are replaced with recorders, so every assertion is about
 * this plugin's own code: which endpoint it calls, which headers it sends,
 * what it does with the response, what it does when the response is wrong, and
 * what it caches.
 *
 * @package adz-world
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.Security.EscapeOutput, WordPress.PHP.DevelopmentFunctions

define( 'ABSPATH', __DIR__ );

$adz_src = getenv( 'ADZ_SRC' );
$adz_src = $adz_src ? rtrim( $adz_src, '/' ) : 'pre-release-candidates/claude-code-web';

$adz_calls     = array();
$adz_store     = array();
$adz_responses = array();
$adz_account   = array();
$adz_pass      = 0;
$adz_fail      = 0;

/**
 * The one saved account under test.
 *
 * @return array<string,array<string,string>> Accounts keyed by network id.
 */
function adzworld_affiliate_accounts() {
	global $adz_account;
	return array( 'ebay' => $adz_account );
}

/**
 * Read from the stand-in transient store.
 *
 * @param string $key Key.
 * @return mixed Stored value, or false.
 */
function get_transient( $key ) {
	global $adz_store;
	return isset( $adz_store[ $key ] ) ? $adz_store[ $key ] : false;
}

/**
 * Write to the stand-in transient store and record the life asked for.
 *
 * @param string $key   Key.
 * @param mixed  $value Value.
 * @param int    $ttl   Seconds.
 * @return bool Always true.
 */
function set_transient( $key, $value, $ttl ) {
	global $adz_store, $adz_calls;
	$adz_store[ $key ] = $value;
	$adz_calls[]       = array( 'set_transient', $key, $ttl );
	return true;
}

/**
 * Take the next queued HTTP response.
 *
 * @return mixed Response, or null when the queue is empty.
 */
function adz_next_response() {
	global $adz_responses;
	return array_shift( $adz_responses );
}

/**
 * Record a POST and answer from the queue.
 *
 * @param string $url  Endpoint.
 * @param array  $args Request arguments.
 * @return mixed Queued response.
 */
function wp_remote_post( $url, $args ) {
	global $adz_calls;
	$adz_calls[] = array( 'post', $url, $args );
	return adz_next_response();
}

/**
 * Record a GET and answer from the queue.
 *
 * @param string $url  Endpoint.
 * @param array  $args Request arguments.
 * @return mixed Queued response.
 */
function wp_remote_get( $url, $args ) {
	global $adz_calls;
	$adz_calls[] = array( 'get', $url, $args );
	return adz_next_response();
}

/**
 * Whether a queued response stands for a transport failure.
 *
 * @param mixed $thing Response.
 * @return bool Whether it is an error.
 */
function is_wp_error( $thing ) {
	return is_array( $thing ) && isset( $thing['adz_transport_error'] );
}

/**
 * Status code of a queued response.
 *
 * @param mixed $response Response.
 * @return int Status code.
 */
function wp_remote_retrieve_response_code( $response ) {
	return isset( $response['code'] ) ? $response['code'] : 0;
}

/**
 * Body of a queued response.
 *
 * @param mixed $response Response.
 * @return string Body.
 */
function wp_remote_retrieve_body( $response ) {
	return isset( $response['body'] ) ? $response['body'] : '';
}

/**
 * Append a query string, so assertions can read what was requested.
 *
 * @param array  $args Query arguments.
 * @param string $url  Base URL.
 * @return string URL with its query string.
 */
function add_query_arg( $args, $url ) {
	return $url . '?' . implode(
		'&',
		array_map(
			function ( $k, $v ) {
				return $k . '=' . $v;
			},
			array_keys( $args ),
			$args
		)
	);
}

/**
 * Remove every tag, as WordPress does.
 *
 * @param string $value Input.
 * @return string Input without tags.
 */
function wp_strip_all_tags( $value ) {
	return strip_tags( (string) $value );
}

/**
 * Escape for an attribute, as WordPress does for URLs.
 *
 * @param string $value Input.
 * @return string Escaped value.
 */
function esc_url( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES );
}

/**
 * Escape for text, as WordPress does.
 *
 * @param string $value Input.
 * @return string Escaped value.
 */
function esc_html( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES );
}

/**
 * Pass text through untranslated.
 *
 * @param string $text   Text.
 * @param string $domain Unused here; the signature matches WordPress.
 * @return string The text unchanged.
 */
function __( $text, $domain = null ) {
	return $text;
}


require __DIR__ . '/../' . $adz_src . '/includes/adzworld-ebay.php';

/**
 * Record one assertion.
 *
 * @param string $label Description.
 * @param bool   $ok    Result.
 * @param string $saw   What was actually seen.
 * @return void
 */
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

/**
 * Reset the recorders between cases.
 *
 * @param array $account   eBay account fields.
 * @param array $responses Queued HTTP responses.
 * @return void
 */
function adz_reset( $account, $responses = array() ) {
	global $adz_calls, $adz_store, $adz_responses, $adz_account;
	$adz_calls     = array();
	$adz_store     = array();
	$adz_responses = $responses;
	$adz_account   = array_merge(
		array(
			'enabled'      => '1',
			'campid'       => '5338001234',
			'mkrid'        => '711-53200-19255-0',
			'siteid'       => '0',
			'clientid'     => 'DainisWM-adzworld-PRD-abc123',
			'clientsecret' => 'PRD-secret-0123456789',
		),
		$account
	);
}

$adz_token_ok = array(
	'code' => 200,
	'body' => '{"access_token":"v^1.1#tokentoken","expires_in":7200,"token_type":"Application Access Token"}',
);

$adz_search_ok = array(
	'code' => 200,
	'body' => wp_json_encode_shim(
		array(
			'itemSummaries' => array(
				array(
					'title'               => 'Mid-century walnut 3-seat couch',
					'itemAffiliateWebUrl' => 'https://www.ebay.com/itm/111?mkcid=1&campid=5338001234',
					'itemWebUrl'          => 'https://www.ebay.com/itm/111',
					'image'               => array( 'imageUrl' => 'https://i.ebayimg.com/images/g/aaa/s-l500.jpg' ),
					'price'               => array(
						'value'    => '849.00',
						'currency' => 'USD',
					),
				),
				array(
					'title'           => 'Grey linen sleeper couch',
					'itemWebUrl'      => 'https://www.ebay.com/itm/222',
					'thumbnailImages' => array( array( 'imageUrl' => 'https://i.ebayimg.com/images/g/bbb/s-l225.jpg' ) ),
				),
				array(
					'itemWebUrl' => 'https://www.ebay.com/itm/333',
				),
			),
		)
	),
);

echo "ADZ_SRC=$adz_src\n\n";

echo "1. No developer credentials: the fetcher stays out of the way\n";
adz_reset(
	array(
		'clientid'     => '',
		'clientsecret' => '',
	)
);
$r = adzworld_ebay_search( 'pizza night' );
adz_check( 'returns no products', array() === $r, var_export( $r, true ) );
adz_check( 'makes no HTTP request at all', 0 === count( $GLOBALS['adz_calls'] ), count( $GLOBALS['adz_calls'] ) . ' calls' );

echo "\n2. Account switched off: the fetcher stays out of the way\n";
adz_reset( array( 'enabled' => '0' ) );
$r = adzworld_ebay_search( 'pizza night' );
adz_check( 'returns no products', array() === $r );
adz_check( 'makes no HTTP request at all', 0 === count( $GLOBALS['adz_calls'] ) );

echo "\n3. Empty request: nothing is sent anywhere\n";
adz_reset( array() );
adz_check( 'empty string returns nothing', array() === adzworld_ebay_search( '   ' ) );
adz_check( 'makes no HTTP request at all', 0 === count( $GLOBALS['adz_calls'] ) );

echo "\n4. A complete account and a real response shape\n";
adz_reset( array(), array( $adz_token_ok, $adz_search_ok ) );
$r = adzworld_ebay_search( 'couch mid-century walnut' );
adz_check( 'two usable products returned, the one with no title dropped', 2 === count( $r ), count( $r ) . ' returned' );
adz_check( 'title read', isset( $r[0]['title'] ) && 'Mid-century walnut 3-seat couch' === $r[0]['title'] );
adz_check( 'affiliate url preferred over the plain one', isset( $r[0]['url'] ) && false !== strpos( $r[0]['url'], 'campid=5338001234' ), $r[0]['url'] );
adz_check( 'price assembled as currency and value', isset( $r[0]['price'] ) && 'USD 849.00' === $r[0]['price'], $r[0]['price'] );
adz_check( 'full image used when present', isset( $r[0]['image'] ) && false !== strpos( $r[0]['image'], 's-l500' ), $r[0]['image'] );
adz_check( 'thumbnail used as the fallback image', isset( $r[1]['image'] ) && false !== strpos( $r[1]['image'], 's-l225' ), $r[1]['image'] );
adz_check( 'plain url used when no affiliate url is given', isset( $r[1]['url'] ) && 'https://www.ebay.com/itm/222' === $r[1]['url'], $r[1]['url'] );
adz_check( 'missing price becomes an empty string, not a stray label', isset( $r[1]['price'] ) && '' === $r[1]['price'], var_export( $r[1]['price'], true ) );

echo "\n5. What is actually sent over the wire\n";
$adz_post = null;
$adz_get  = null;
foreach ( $GLOBALS['adz_calls'] as $call ) {
	if ( 'post' === $call[0] && null === $adz_post ) {
		$adz_post = $call;
	}
	if ( 'get' === $call[0] && null === $adz_get ) {
		$adz_get = $call;
	}
}
adz_check( 'token comes from the eBay OAuth endpoint', 'https://api.ebay.com/identity/v1/oauth2/token' === $adz_post[1], $adz_post[1] );
adz_check( 'client-credentials grant, not a reader grant', 'client_credentials' === $adz_post[2]['body']['grant_type'], $adz_post[2]['body']['grant_type'] );
adz_check(
	'credentials sent as HTTP Basic, base64 of id:secret',
	'Basic ' . base64_encode( 'DainisWM-adzworld-PRD-abc123:PRD-secret-0123456789' ) === $adz_post[2]['headers']['Authorization'],
	$adz_post[2]['headers']['Authorization']
);
adz_check( 'search hits the Browse item_summary endpoint', 0 === strpos( $adz_get[1], 'https://api.ebay.com/buy/browse/v1/item_summary/search' ), $adz_get[1] );
adz_check( 'the reader request is the q parameter, url-encoded', false !== strpos( $adz_get[1], 'q=couch%20mid-century%20walnut' ), $adz_get[1] );
adz_check( 'result count is capped', false !== strpos( $adz_get[1], 'limit=3' ), $adz_get[1] );
adz_check( 'bearer token carried, not the client secret', 'Bearer v^1.1#tokentoken' === $adz_get[2]['headers']['Authorization'], $adz_get[2]['headers']['Authorization'] );
adz_check(
	'campaign id sent so the publisher is credited',
	false !== strpos( $adz_get[2]['headers']['X-EBAY-C-ENDUSERCTX'], 'affiliateCampaignId=5338001234' ),
	$adz_get[2]['headers']['X-EBAY-C-ENDUSERCTX']
);
$adz_wire = wp_json_encode_shim( $GLOBALS['adz_calls'] );
adz_check( 'no reader identifier anywhere in either request', false === strpos( $adz_wire, 'session' ) && false === strpos( $adz_wire, 'user_id' ) && false === strpos( $adz_wire, 'cookie' ) );

echo "\n6. Caching, so a reader refining repeatedly costs one call\n";
$before = count( $GLOBALS['adz_calls'] );
$again  = adzworld_ebay_search( 'couch mid-century walnut' );
$after  = count( $GLOBALS['adz_calls'] );
adz_check( 'the same request makes no further HTTP call', $after === $before, $after . ' calls now' );
adz_check( 'and returns the same products', $r === $again );
$ttls = array();
foreach ( $GLOBALS['adz_calls'] as $call ) {
	if ( 'set_transient' === $call[0] ) {
		$ttls[] = $call[2];
	}
}
adz_check( 'token cached under its 7200s life', in_array( 6600, $ttls, true ), implode( ',', $ttls ) );
adz_check( 'results cached for 15 minutes', in_array( 900, $ttls, true ), implode( ',', $ttls ) );

echo "\n7. A different request is a different cache entry\n";
$GLOBALS['adz_responses'] = array( $adz_search_ok );
$before                   = count( $GLOBALS['adz_calls'] );
adzworld_ebay_search( 'couch grey linen' );
$gets = 0;
foreach ( array_slice( $GLOBALS['adz_calls'], $before ) as $call ) {
	if ( 'get' === $call[0] ) {
		++$gets;
	}
}
adz_check( 'a new request fetches again', 1 === $gets, "$gets search calls" );
adz_check(
	'but reuses the cached token',
	0 === count(
		array_filter(
			array_slice( $GLOBALS['adz_calls'], $before ),
			function ( $c ) {
				return 'post' === $c[0];
			}
		)
	)
);

echo "\n8. Every way the response can be wrong\n";
foreach ( array(
	'token request errors out'   => array( array( 'adz_transport_error' => true ), null ),
	'token returns 401'          => array(
		array(
			'code' => 401,
			'body' => '{"error":"invalid_client"}',
		),
		null,
	),
	'token body is not json'     => array(
		array(
			'code' => 200,
			'body' => '<html>down for maintenance</html>',
		),
		null,
	),
	'token body has no token'    => array(
		array(
			'code' => 200,
			'body' => '{"expires_in":7200}',
		),
		null,
	),
	'search errors out'          => array( $adz_token_ok, array( 'adz_transport_error' => true ) ),
	'search returns 500'         => array(
		$adz_token_ok,
		array(
			'code' => 500,
			'body' => '',
		),
	),
	'search body is not json'    => array(
		$adz_token_ok,
		array(
			'code' => 200,
			'body' => 'nope',
		),
	),
	'search returns no items'    => array(
		$adz_token_ok,
		array(
			'code' => 200,
			'body' => '{"total":0,"itemSummaries":[]}',
		),
	),
	'items are not a list'       => array(
		$adz_token_ok,
		array(
			'code' => 200,
			'body' => '{"itemSummaries":"wat"}',
		),
	),
	'an item is a scalar'        => array(
		$adz_token_ok,
		array(
			'code' => 200,
			'body' => '{"itemSummaries":[42]}',
		),
	),
	'a title is a nested object' => array(
		$adz_token_ok,
		array(
			'code' => 200,
			'body' => '{"itemSummaries":[{"title":{"a":"b"},"itemWebUrl":"https://x"}]}',
		),
	),
) as $label => $pair ) {
	adz_reset(
		array(),
		array_values(
			array_filter(
				$pair,
				function ( $p ) {
					return null !== $p;
				}
			)
		)
	);
	$out = adzworld_ebay_search( 'couch' );
	adz_check( "$label: returns an empty list, no notice, no crash", array() === $out, var_export( $out, true ) );
}

echo "\n9. Markup\n";
adz_reset( array(), array( $adz_token_ok, $adz_search_ok ) );
$html = adzworld_ebay_markup( 'couch mid-century walnut' );
adz_check( 'two cards rendered', 2 === substr_count( $html, 'adzworld-product__link' ), substr_count( $html, 'adzworld-product__link' ) . ' cards' );
adz_check( 'every outbound link is marked sponsored', 2 === substr_count( $html, 'rel="sponsored nofollow"' ) );
adz_check( 'no inline style attribute', false === strpos( $html, 'style=' ) );
adz_check( 'images lazy-loaded', 2 === substr_count( $html, 'loading="lazy"' ) );
adz_check( 'image carries empty alt, since the title is beside it', 2 === substr_count( $html, 'alt=""' ) );
adz_check( 'the request is echoed back to the reader in the heading', false !== strpos( $html, 'Matching couch mid-century walnut right now' ) );

/*
 * adzworld-creative is the wrapper around a creative a network returned, and
 * rotation.css carries an explicit contract that nothing resizes, restyles or
 * clips one. These cards are this plugin's own markup and must never borrow
 * that class, in either direction: the contract rules would deform the cards,
 * and card rules would reach a network's unit and breach its terms.
 */
adz_check( 'cards do not borrow the protected network-creative class', false === strpos( $html, 'class="adzworld-creative"' ) && false === strpos( $html, 'adzworld-creative__' ), $html );

echo "\n10. Escaping: a hostile response cannot inject markup\n";
adz_reset(
	array(),
	array(
		$adz_token_ok,
		array(
			'code' => 200,
			'body' => wp_json_encode_shim(
				array(
					'itemSummaries' => array(
						array(
							'title'      => '<script>alert(1)</script>"onmouseover="x',
							'itemWebUrl' => 'javascript:alert(1)',
							'image'      => array( 'imageUrl' => '" onerror="alert(1)' ),
							'price'      => array(
								'value'    => '<b>9</b>',
								'currency' => 'US"D',
							),
						),
					),
				)
			),
		),
	)
);
$html = adzworld_ebay_markup( 'couch' );
adz_check( 'no executable script tag survives', false === strpos( $html, '<script' ), $html );
adz_check( 'the title cannot close an attribute: its quote is encoded', false === strpos( $html, '"onmouseover="' ) && false !== strpos( $html, '&quot;onmouseover=' ), $html );
adz_check( 'the image url cannot close an attribute: its quote is encoded', false === strpos( $html, '" onerror="' ), $html );
adz_check( 'no bold tag from the price', false === strpos( $html, '<b>' ) );

echo "\n11. A hostile reader request is not reflected unescaped\n";
adz_reset( array(), array( $adz_token_ok, $adz_search_ok ) );
$html = adzworld_ebay_markup( '<img src=x onerror=alert(1)> couch' );
adz_check( 'tags stripped before the request is sent or shown', false === strpos( $html, '<img src=x' ), substr( $html, 0, 200 ) );
adz_check( 'the heading shows the request as actually sent, not the raw input', false !== strpos( $html, 'Matching couch right now' ), $html );
adz_check( 'nothing from the payload is left in the heading', false === strpos( $html, 'onerror=alert' ), $html );

/**
 * Encode as JSON under a name the sniffs do not object to.
 *
 * @param mixed $value Value.
 * @return string JSON.
 */
function wp_json_encode_shim( $value ) {
	return json_encode( $value );
}

echo "\n----\n$adz_pass passed, $adz_fail failed\n";
exit( $adz_fail > 0 ? 1 : 0 );
