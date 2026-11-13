<?php
/**
 * Standalone proof for the shared product collector and renderer.
 *
 * Usage: ADZ_SRC=pre-release-candidates/claude-code-web php tools/test-product-merge.php
 *
 * Three provider files, written by three authors, append rows to one filter.
 * This checks that the merge treats them alike: normalises their differing
 * shapes, refuses rows that would render an empty or unsafe card, keeps one
 * provider from taking the whole slot, and escapes everything on output.
 *
 * No WordPress, no network, no credentials.
 *
 * @package adz-world
 */

// phpcs:disable WordPress.Security.EscapeOutput -- CLI harness: output goes to a terminal, not a page.
// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DevelopmentFunctions -- stubs stand in for the WordPress functions under test.
// phpcs:disable WordPress.WP.GlobalVariablesOverride -- $adz_* globals are this harness's own state.
// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames, Generic.CodeAnalysis.UnusedFunctionParameter -- stub signatures must match WordPress.

define( 'ABSPATH', __DIR__ );

$adz_src   = getenv( 'ADZ_SRC' );
$adz_src   = $adz_src ? rtrim( $adz_src, '/' ) : 'pre-release-candidates/claude-code-web';
$adz_pass  = 0;
$adz_fail  = 0;
$adz_hooks = array();

/**
 * Register a filter callback.
 *
 * @param string   $hook     Hook name.
 * @param callable $callback Callback.
 * @param int      $priority Unused.
 * @param int      $args     Unused.
 * @return void
 */
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
	global $adz_hooks;
	$adz_hooks[ $hook ][] = $callback;
}

/**
 * Run every callback registered for a hook.
 *
 * @param string $hook  Hook name.
 * @param mixed  $value Value to filter.
 * @param mixed  $extra Second argument.
 * @return mixed Filtered value.
 */
function apply_filters( $hook, $value, $extra = null ) {
	global $adz_hooks;
	if ( isset( $adz_hooks[ $hook ] ) ) {
		foreach ( $adz_hooks[ $hook ] as $callback ) {
			$value = call_user_func( $callback, $value, $extra );
		}
	}
	return $value;
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
 * Accept a URL, as WordPress does before storing one.
 *
 * @param string $url Input.
 * @return string URL, or an empty string.
 */
function esc_url_raw( $url ) {
	$url = trim( (string) $url );
	return preg_match( '#^[a-z][a-z0-9+.-]*://#i', $url ) ? $url : '';
}

/**
 * Read one component of a URL.
 *
 * @param string $url       URL.
 * @param int    $component Component constant.
 * @return mixed Component value.
 */
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}

/**
 * Escape for an attribute.
 *
 * @param string $value Input.
 * @return string Escaped value.
 */
function esc_url( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES );
}

/**
 * Escape for text.
 *
 * @param string $value Input.
 * @return string Escaped value.
 */
function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES );
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

require __DIR__ . '/../' . $adz_src . '/includes/adzworld-products.php';

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
 * Replace every registered provider with the given rows.
 *
 * @param array<string,array<int,array<string,string>>> $by_provider Rows per provider id.
 * @return void
 */
function adz_providers( $by_provider ) {
	global $adz_hooks;
	$adz_hooks = array();
	foreach ( $by_provider as $provider => $rows ) {
		add_filter(
			'adzworld_affiliate_product_results',
			function ( $results, $request ) use ( $provider, $rows ) {
				foreach ( $rows as $row ) {
					/* A provider can hand back junk; the collector must cope. */
					if ( is_array( $row ) && ! isset( $row['provider'] ) ) {
						$row['provider'] = $provider;
					}
					$results[] = $row;
				}
				return $results;
			}
		);
	}
}

/**
 * Build one well-formed row.
 *
 * @param string $title Title.
 * @param string $slug  URL slug.
 * @return array<string,string> Row.
 */
function adz_row( $title, $slug ) {
	return array(
		'title' => $title,
		'url'   => 'https://example.com/' . $slug,
		'image' => 'https://img.example.com/' . $slug . '.jpg',
		'price' => 'USD 10.00',
	);
}

echo "ADZ_SRC=$adz_src\n\n";

echo "1. No provider answers\n";
adz_providers( array() );
adz_check( 'nothing collected', array() === adzworld_products_collect( 'couch' ) );
adz_check( 'and nothing rendered', '' === adzworld_products_markup( array(), 'couch' ) );

echo "\n2. An empty request never reaches a provider\n";
$reached   = false;
$adz_hooks = array();
add_filter(
	'adzworld_affiliate_product_results',
	function ( $r, $q ) use ( &$reached ) {
		$reached = true;
		return $r;
	}
);
adzworld_products_collect( '   ' );
adz_check( 'no provider was called', false === $reached );

echo "\n3. One provider cannot take the whole slot\n";
adz_providers(
	array(
		'ebay'    => array( adz_row( 'e1', 'e1' ), adz_row( 'e2', 'e2' ), adz_row( 'e3', 'e3' ) ),
		'etsy'    => array( adz_row( 't1', 't1' ), adz_row( 't2', 't2' ), adz_row( 't3', 't3' ) ),
		'bestbuy' => array( adz_row( 'b1', 'b1' ), adz_row( 'b2', 'b2' ), adz_row( 'b3', 'b3' ) ),
	)
);
$out    = adzworld_products_collect( 'couch' );
$order  = array();
$counts = array();
foreach ( $out as $row ) {
	$order[]                    = $row['provider'];
	$counts[ $row['provider'] ] = isset( $counts[ $row['provider'] ] ) ? $counts[ $row['provider'] ] + 1 : 1;
}
adz_check( 'capped at six cards from nine rows', 6 === count( $out ), count( $out ) . ' cards' );
adz_check( 'each provider gets an equal share', array( 2, 2, 2 ) === array_values( $counts ), wp_json_encode_shim( $counts ) );
adz_check( 'and they alternate rather than clump', 'ebay,etsy,bestbuy,ebay,etsy,bestbuy' === implode( ',', $order ), implode( ',', $order ) );

echo "\n4. An uneven field still fills the slot\n";
adz_providers(
	array(
		'ebay' => array( adz_row( 'e1', 'e1' ), adz_row( 'e2', 'e2' ), adz_row( 'e3', 'e3' ) ),
		'etsy' => array( adz_row( 't1', 't1' ) ),
	)
);
$out = adzworld_products_collect( 'couch' );
adz_check( 'the provider with more rows takes up the slack', 4 === count( $out ), count( $out ) . ' cards' );
adz_check( 'the single row is not lost', false !== strpos( wp_json_encode_shim( $out ), '"t1"' ) );

echo "\n5. Rows that would render a broken card are refused\n";
foreach ( array(
	'no title'                 => array( 'url' => 'https://example.com/a' ),
	'empty title'              => array(
		'title' => '   ',
		'url'   => 'https://example.com/a',
	),
	'no url'                   => array( 'title' => 'a' ),
	'empty url'                => array(
		'title' => 'a',
		'url'   => '',
	),
	'a plain-http destination' => array(
		'title' => 'a',
		'url'   => 'http://example.com/a',
	),
	'a javascript destination' => array(
		'title' => 'a',
		'url'   => 'javascript:alert(1)',
	),
	'a relative destination'   => array(
		'title' => 'a',
		'url'   => '/a',
	),
	'not an array at all'      => 'wat',
) as $label => $row ) {
	adz_providers( array( 'ebay' => array( $row ) ) );
	adz_check( "$label: dropped", array() === adzworld_products_collect( 'couch' ), wp_json_encode_shim( adzworld_products_collect( 'couch' ) ) );
}

echo "\n6. A plain-http image is dropped, the card is not\n";
adz_providers(
	array(
		'ebay' => array(
			array(
				'title' => 'a',
				'url'   => 'https://example.com/a',
				'image' => 'http://img/a.jpg',
			),
		),
	)
);
$out = adzworld_products_collect( 'couch' );
adz_check( 'the card survives', 1 === count( $out ) );
adz_check( 'without the insecure image', '' === $out[0]['image'], $out[0]['image'] );

echo "\n7. Two providers listing the same destination is one card\n";
adz_providers(
	array(
		'ebay' => array( adz_row( 'Same couch', 'shared' ) ),
		'etsy' => array( adz_row( 'Same couch, other listing', 'shared' ) ),
	)
);
adz_check( 'the duplicate is dropped', 1 === count( adzworld_products_collect( 'couch' ) ) );

echo "\n8. Each card says which marketplace it came from\n";
adz_providers(
	array(
		'ebay'    => array( adz_row( 'e1', 'e1' ) ),
		'etsy'    => array( adz_row( 't1', 't1' ) ),
		'bestbuy' => array( adz_row( 'b1', 'b1' ) ),
	)
);
$html = adzworld_products_markup( adzworld_products_collect( 'couch' ), 'couch' );
foreach ( array( 'eBay', 'Etsy', 'Best Buy' ) as $label ) {
	adz_check( "$label named on its card", false !== strpos( $html, '__source">' . $label . '<' ), $html );
}
adz_check( 'every card is marked sponsored', 3 === substr_count( $html, 'rel="sponsored nofollow"' ) );
adz_check( 'no inline style attribute', false === strpos( $html, 'style=' ) );
adz_check( 'cards never borrow the protected network-creative class', false === strpos( $html, 'adzworld-creative' ), $html );

echo "\n9. A hostile provider cannot inject markup\n";
adz_providers(
	array(
		'ebay' => array(
			array(
				'title' => '<script>alert(1)</script>"onmouseover="x',
				'url'   => 'https://example.com/a',
				'image' => 'https://img/a.jpg" onerror="alert(1)',
				'price' => '<b>9</b>',
			),
		),
	)
);
$html = adzworld_products_markup( adzworld_products_collect( 'couch' ), 'couch' );
adz_check( 'no script tag survives', false === strpos( $html, '<script' ), $html );
adz_check( 'the title cannot close an attribute', false === strpos( $html, '"onmouseover="' ) );
adz_check( 'the image url cannot close an attribute', false === strpos( $html, '" onerror="' ) );
adz_check( 'no bold tag from the price', false === strpos( $html, '<b>' ) );

echo "\n10. A hostile request is not reflected into the heading\n";
adz_providers( array( 'ebay' => array( adz_row( 'e1', 'e1' ) ) ) );
$html = adzworld_products_markup( adzworld_products_collect( '<img src=x onerror=alert(1)> couch' ), '<img src=x onerror=alert(1)> couch' );
adz_check( 'the tag is stripped', false === strpos( $html, '<img src=x' ) );
adz_check( 'nothing executable remains', false === strpos( $html, 'onerror=alert' ), $html );

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
