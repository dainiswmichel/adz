<?php
/**
 * Exercise real mutual TLS against an explicitly local disposable TLS server.
 *
 * ADZWORLD_TEST_CLIENT_PEM identifies a locally generated client PEM bundle.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}
require __DIR__ . '/regression.php';
require_once dirname( __DIR__ ) . '/classes/rest-client/class-phprestclient.php';
$adzworld_pem = getenv( 'ADZWORLD_TEST_CLIENT_PEM' );
if ( ! is_string( $adzworld_pem ) || ! is_readable( $adzworld_pem ) ) {
	WP_CLI::error( 'A readable disposable client PEM bundle is required.' );
}
$adzworld_guard = static function ( $preempt, $args, $url ) {
	if ( '127.0.0.1' !== wp_parse_url( $url, PHP_URL_HOST ) || 18778 !== wp_parse_url( $url, PHP_URL_PORT ) ) {
		throw new RuntimeException( 'Certificate contract attempted an unexpected host or port.' );
	}
	return $preempt;
};
add_filter( 'pre_http_request', $adzworld_guard, 10, 3 );
try {
	$adzworld_plain = new PHPRestClient( 'https://127.0.0.1:18778', 'None', array(), true );
	adzworld_test_assert( is_wp_error( $adzworld_plain->rest_get( '/', array() ) ), 'The actual TLS server rejects a request without a client certificate' );
	$adzworld_client   = new PHPRestClient(
		'https://127.0.0.1:18778',
		'cert',
		array(
			'cert_file'     => $adzworld_pem,
			'cert_password' => '',
		),
		true
	);
	$adzworld_hooks    = array( has_action( 'http_api_curl' ), has_action( 'requests-requests.before_request' ) );
	$adzworld_response = $adzworld_client->rest_get( '/', array() );
	adzworld_test_assert( ! is_wp_error( $adzworld_response ) && 200 === $adzworld_response['code'] && false !== strpos( $adzworld_response['body'], 'Adz disposable client' ), 'The real WordPress HTTP transport presents the configured client certificate and receives a successful mutual-TLS response' );
	adzworld_test_assert( array( has_action( 'http_api_curl' ), has_action( 'requests-requests.before_request' ) ) === $adzworld_hooks, 'Certificate configuration hooks are removed after the request' );
	$adzworld_client->update_connection(
		'http://127.0.0.1:18778',
		'cert',
		array(
			'cert_file'     => $adzworld_pem,
			'cert_password' => '',
		)
	);
	adzworld_test_assert( is_wp_error( $adzworld_client->rest_get( '/', array() ) ), 'Certificate mode rejects a plaintext HTTP target rather than silently dropping authentication' );
} finally {
	remove_filter( 'pre_http_request', $adzworld_guard, 10 );
}
WP_CLI::success( 'Actual client-certificate transport contracts passed.' );
