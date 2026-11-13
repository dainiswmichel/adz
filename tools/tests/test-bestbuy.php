<?php
/**
 * Focused contract checks for the Best Buy result adapter.
 *
 * @package adz-world-tests
 */

$adz_plugin = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : dirname( __DIR__, 2 ) . '/pre-release-candidates/dwm-local';
$adz_file   = $adz_plugin . '/includes/adzworld-bestbuy.php';
if ( ! is_file( $adz_file ) ) {
	printf( "SKIP: %s has no includes/adzworld-bestbuy.php\n", basename( $adz_plugin ) );
	exit( 3 );
}

define( 'ABSPATH', '/stub/' );
define( 'MINUTE_IN_SECONDS', 60 );

class WP_Error {
	public $code;

	public function __construct( $code ) {
		$this->code = $code;
	}
}

function __( $value ) {
	return $value;
}
function add_filter( $hook, $callback, $priority = 10, $arguments = 1 ) {
	$GLOBALS['adz_filters'][ $hook ][] = array( $callback, $priority, $arguments );
}
function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, (array) $args );
}
function wp_strip_all_tags( $value ) {
	return trim( strip_tags( (string) $value ) );
}
function sanitize_text_field( $value ) {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $value ) ) );
}
function add_query_arg( $args, $url ) {
	return $url . '?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
}
function esc_url_raw( $url ) {
	return filter_var( (string) $url, FILTER_VALIDATE_URL ) ? (string) $url : '';
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function number_format_i18n( $number, $decimals = 0 ) {
	return number_format( (float) $number, (int) $decimals, '.', ',' );
}
function get_transient() {
	return false;
}
function set_transient( $key, $value, $expiration ) {
	$GLOBALS['adz_transient'] = array( $key, $value, $expiration );
	return true;
}
function wp_safe_remote_get( $url ) {
	$GLOBALS['adz_requested_url'] = $url;
	return $GLOBALS['adz_http_response'];
}
function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}
function wp_remote_retrieve_body( $response ) {
	return $response['body'];
}
function wp_remote_retrieve_response_code( $response ) {
	return $response['response']['code'];
}
function adzworld_affiliate_accounts() {
	return $GLOBALS['adz_accounts'];
}

$GLOBALS['adz_accounts'] = array(
	'bestbuy' => array(
		'enabled' => '1',
		'api_key' => 'private-api-key',
		'ipid'    => 'impact-123',
	),
);

require $adz_file;

$pass = 0;
$fail = 0;

function adzworld_bestbuy_check( $label, $actual, $expected ) {
	global $pass, $fail;
	$ok = $actual === $expected;
	$ok ? ++$pass : ++$fail;
	printf( "  [%s] %-47s\n", $ok ? 'ok' : 'FAIL', $label );
	if ( ! $ok ) {
		printf( "       actual: %s\n      expected: %s\n", var_export( $actual, true ), var_export( $expected, true ) );
	}
}

$account = $GLOBALS['adz_accounts']['bestbuy'];
$url     = adzworld_bestbuy_products_url( 'couch light fabric', $account );
adzworld_bestbuy_check( 'each request word is an API search term', false !== strpos( $url, 'search=couch&search=light&search=fabric' ), true );
adzworld_bestbuy_check( 'API key is included for server request', false !== strpos( $url, 'apiKey=private-api-key' ), true );
adzworld_bestbuy_check( 'Impact Partner ID requests attributed URLs', false !== strpos( $url, 'IPID=impact-123' ), true );
adzworld_bestbuy_check( 'only three products are requested', false !== strpos( $url, 'pageSize=3' ), true );

$product = array(
	'name'         => 'Light fabric loveseat',
	'salePrice'    => 137.89,
	'largeImage'   => 'https://pisces.bbystatic.com/image.jpg',
	'url'          => 'https://www.bestbuy.com/untracked',
	'affiliateUrl' => 'https://api.bestbuy.com/click/tracked/product',
);
$mapped  = adzworld_bestbuy_map_product( $product );
adzworld_bestbuy_check( 'the attributed URL wins over the plain URL', $mapped['url'], $product['affiliateUrl'] );
adzworld_bestbuy_check( 'the API price is formatted as US dollars', $mapped['price'], '$137.89' );
unset( $product['affiliateUrl'] );
adzworld_bestbuy_check( 'an untracked product is rejected', adzworld_bestbuy_map_product( $product ), null );

$GLOBALS['adz_http_response'] = array(
	'response' => array( 'code' => 200 ),
	'body'     => json_encode(
		array(
			'products' => array(
				array(
					'name'         => 'Product one',
					'salePrice'    => 10,
					'image'        => 'https://pisces.bbystatic.com/one.jpg',
					'affiliateUrl' => 'https://api.bestbuy.com/click/one',
				),
				array(
					'name'         => 'Product two',
					'salePrice'    => 20,
					'image'        => 'https://pisces.bbystatic.com/two.jpg',
					'affiliateUrl' => 'https://api.bestbuy.com/click/two',
				),
			),
		)
	),
);
$results = adzworld_bestbuy_product_results( 'couch light fabric' );
adzworld_bestbuy_check( 'the response becomes two carousel results', count( $results ), 2 );
adzworld_bestbuy_check( 'the live request uses the full search string', false !== strpos( $GLOBALS['adz_requested_url'], 'search=couch&search=light&search=fabric' ), true );
adzworld_bestbuy_check( 'results cache for fifteen minutes', $GLOBALS['adz_transient'][2], 15 * MINUTE_IN_SECONDS );

$GLOBALS['adz_accounts']['bestbuy']['ipid'] = '';
$error = adzworld_bestbuy_product_results( 'couch' );
adzworld_bestbuy_check( 'missing attribution credentials block the fetch', is_wp_error( $error ), true );
adzworld_bestbuy_check( 'the credential error is explicit', $error->code, 'adzworld_bestbuy_credentials' );

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail > 0 ? 1 : 0 );
