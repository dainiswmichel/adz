<?php
/**
 * Exercise the real WordPress HTTP adapter without contacting a remote service.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

require __DIR__ . '/regression.php';
require_once dirname( __DIR__ ) . '/classes/rest-client/class-wprestclient.php';
require_once dirname( __DIR__ ) . '/classes/rest-client/class-phprestclient.php';
require_once dirname( __DIR__ ) . '/classes/rest-client/cache-interface/class-wpcacheimpl.php';

$adzworld_requests = array();
$adzworld_mock     = static function ( $preempt, $args, $url ) use ( &$adzworld_requests ) {
	$adzworld_requests[] = array(
		'url'  => $url,
		'args' => $args,
	);
	if ( 'rest-contract.invalid' !== wp_parse_url( $url, PHP_URL_HOST ) ) {
		throw new RuntimeException( 'REST contract attempted an unexpected remote host.' );
	}
	if ( false !== strpos( $url, '/transport-error' ) ) {
		return new WP_Error( 'adzworld_test_transport', 'Disposable transport failure' );
	}
	if ( false !== strpos( $url, '/digest' ) && empty( $args['headers']['Authorization'] ) ) {
		$challenge = false !== strpos( $url, 'unsupported' )
			? 'Digest realm="test", nonce="server-nonce", algorithm=MD5, qop="auth-int"'
			: 'Digest realm="test, realm", nonce="server-nonce", algorithm=MD5, qop="auth,auth-int", opaque="test=opaque"';
		return array(
			'response' => array( 'code' => 401 ),
			'headers'  => array( 'www-authenticate' => $challenge ),
			'body'     => '',
		);
	}
	return array(
		'response' => array( 'code' => 200 ),
		'headers'  => array(),
		'body'     => wp_json_encode( array( 'request' => count( $adzworld_requests ) ) ),
	);
};
add_filter( 'pre_http_request', $adzworld_mock, 10, 3 );
$adzworld_cache = new WPCacheImpl( 'adzworld-rest' );
$adzworld_cache->clear_cache();
try {
	$adzworld_client = new WPRestClient( 'https://rest-contract.invalid/api', 'Query', array( 'token' => 'first-fixture' ) );
	foreach ( array( 'get', 'put', 'post', 'delete' ) as $adzworld_method ) {
		$adzworld_result = $adzworld_client->{ 'rest_' . $adzworld_method }( 'resource?existing=retained', array( 'field' => 'value' ) );
		adzworld_test_assert( ! is_wp_error( $adzworld_result ), 'REST verb executes without a missing-method fatal: ' . $adzworld_method );
		$adzworld_request = end( $adzworld_requests );
		parse_str( wp_parse_url( $adzworld_request['url'], PHP_URL_QUERY ), $adzworld_query );
		adzworld_test_assert( 'first-fixture' === $adzworld_query['token'] && 'retained' === $adzworld_query['existing'], 'Query authentication preserves existing URL fields: ' . $adzworld_method );
		adzworld_test_assert(
			strtoupper( $adzworld_method ) === $adzworld_request['args']['method']
			&& true === $adzworld_request['args']['sslverify'] && 0 === $adzworld_request['args']['redirection'],
			'Method, TLS verification and no credential-following redirects are explicit: ' . $adzworld_method
		);
	}
	$adzworld_client->update_authentication( 'Query', array( 'token' => 'fixture&scope=other +/' ) );
	$adzworld_client->rest_get( 'encoded?existing=retained', array( 'search' => 'a&b=c +' ) );
	$adzworld_request = end( $adzworld_requests );
	parse_str( wp_parse_url( $adzworld_request['url'], PHP_URL_QUERY ), $adzworld_query );
	adzworld_test_assert( 'fixture&scope=other +/' === $adzworld_query['token'] && 'a&b=c +' === $adzworld_query['search'] && ! isset( $adzworld_query['scope'] ), 'Query credentials and search fields remain single encoded values, not injected query keys' );
	$adzworld_legacy = new PHPRestClient( 'https://rest-contract.invalid/api', 'Query', array( 'token' => 'legacy-fixture' ) );
	foreach ( array( 'get', 'put', 'post', 'delete' ) as $adzworld_method ) {
		$adzworld_result = $adzworld_legacy->{ 'rest_' . $adzworld_method }( 'legacy', array() );
		adzworld_test_assert( 200 === $adzworld_result['code'] && is_array( $adzworld_result['headers'] ) && isset( $adzworld_result['body'] ), 'Legacy adapter preserves flat responses through the real shared transport: ' . $adzworld_method );
	}
	$adzworld_client->update_authentication( 'Header', array( 'X-Fixture' => "one\r\nInjected: two" ) );
	$adzworld_before = count( $adzworld_requests );
	adzworld_test_assert( is_wp_error( $adzworld_client->rest_get( 'invalid-header', array() ) ) && count( $adzworld_requests ) === $adzworld_before, 'Malformed headers are rejected explicitly before transport' );
	$adzworld_client->update_authentication( array(), array() );
	adzworld_test_assert( is_wp_error( $adzworld_client->rest_get( 'invalid-configuration', array() ) ), 'Malformed authentication configuration returns an error instead of a PHP type fatal' );
	$adzworld_client->update_authentication( 'Header', array( 'Authorization' => 'Bearer first' ) );
	$adzworld_client->rest_get( 'private', array() );
	$adzworld_before = count( $adzworld_requests );
	$adzworld_client->rest_get( 'private', array() );
	adzworld_test_assert( count( $adzworld_requests ) === $adzworld_before, 'Repeated successful GET can use its own cache entry' );
	$adzworld_client->update_authentication( 'Header', array( 'Authorization' => 'Bearer second' ) );
	$adzworld_client->rest_get( 'private', array() );
	$adzworld_request = end( $adzworld_requests );
	adzworld_test_assert(
		count( $adzworld_requests ) === $adzworld_before + 1
		&& 'Bearer second' === $adzworld_request['args']['headers']['Authorization'],
		'Changing authentication changes both headers and the private response-cache identity'
	);
	$adzworld_client->rest_post( 'private', array( 'changed' => true ) );
	$adzworld_before = count( $adzworld_requests );
	$adzworld_client->rest_get( 'private', array() );
	adzworld_test_assert( count( $adzworld_requests ) === $adzworld_before + 1, 'Remote mutation invalidates cached reads' );
	$adzworld_before = count( $adzworld_requests );
	for ( $adzworld_attempt = 0; $adzworld_attempt < 2; ++$adzworld_attempt ) {
		adzworld_test_assert( is_wp_error( $adzworld_client->rest_get( 'transport-error', array() ) ), 'Transport failure remains an explicit WP_Error' );
	}
	adzworld_test_assert( count( $adzworld_requests ) === $adzworld_before + 2, 'Transport failures are not cached as successful responses' );
	$adzworld_client->update_authentication(
		'Basic',
		array(
			'username' => 'fixture',
			'password' => 'test-password',
		)
	);
	$adzworld_client->rest_put( 'basic', array() );
	$adzworld_request = end( $adzworld_requests );
	adzworld_test_assert( 'Basic Zml4dHVyZTp0ZXN0LXBhc3N3b3Jk' === $adzworld_request['args']['headers']['Authorization'], 'Basic authentication remains wire-compatible' );
	$adzworld_client->update_authentication( 'Basic', array() );
	$adzworld_before = count( $adzworld_requests );
	adzworld_test_assert( is_wp_error( $adzworld_client->rest_get( 'invalid', array() ) ), 'Incomplete credentials fail explicitly before transport' );
	adzworld_test_assert( count( $adzworld_requests ) === $adzworld_before, 'Missing credentials do not initiate an outbound request' );
	$adzworld_client->update_authentication(
		'Digest',
		array(
			'digest_parameters' => array(
				'username' => 'fixture',
				'password' => 'test-password',
			),
		)
	);
	$adzworld_client->rest_get( 'digest?resource=exact', array() );
	$adzworld_request = end( $adzworld_requests );
	preg_match_all( '/([a-z]+)=(?:"([^"]*)"|([^,\s]+))/', $adzworld_request['args']['headers']['Authorization'], $adzworld_matches, PREG_SET_ORDER );
	$adzworld_digest = array();
	foreach ( $adzworld_matches as $adzworld_match ) {
		$adzworld_digest[ $adzworld_match[1] ] = isset( $adzworld_match[3] ) ? $adzworld_match[3] : $adzworld_match[2];
	}
	$adzworld_expected = md5(
		md5( 'fixture:test, realm:test-password' ) . ':server-nonce:00000001:' . $adzworld_digest['cnonce'] . ':auth:' . md5( 'GET:/api/digest?resource=exact' )
	);
	adzworld_test_assert(
		'/api/digest?resource=exact' === $adzworld_digest['uri']
		&& $adzworld_expected === $adzworld_digest['response'] && 'test=opaque' === $adzworld_digest['opaque'],
		'Digest signs the actual method/path/query with the correct username and password, including quoted commas and equals'
	);
	adzworld_test_assert( is_wp_error( $adzworld_client->rest_get( 'digest-unsupported', array() ) ), 'Unsupported digest protection fails explicitly without an unauthenticated fallback' );
	$adzworld_first_cache  = new WPCacheImpl( 'adzworld-test-first' );
	$adzworld_second_cache = new WPCacheImpl( 'adzworld-test-second' );
	$adzworld_first_cache->set( 'same-key', 'first', 60 );
	$adzworld_second_cache->set( 'same-key', 'second', 60 );
	$adzworld_first_cache->clear_cache();
	adzworld_test_assert( false === $adzworld_first_cache->get( 'same-key' ) && 'second' === $adzworld_second_cache->get( 'same-key' ), 'Cache-group invalidation does not flush unrelated groups' );
} finally {
	remove_filter( 'pre_http_request', $adzworld_mock, 10 );
	$adzworld_cache->clear_cache();
	if ( isset( $adzworld_first_cache, $adzworld_second_cache ) ) {
		$adzworld_first_cache->clear_cache();
		$adzworld_second_cache->clear_cache();
	}
}
WP_CLI::success( 'REST adapter contracts passed without remote traffic.' );
