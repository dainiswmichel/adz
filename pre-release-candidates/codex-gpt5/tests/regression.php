<?php
/**
 * Local WordPress regression checks: wp eval-file path/to/this/file.php.
 *
 * HTTP uses mocked provider responses, never real payment credentials.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

/**
 * Fail loudly and count checked assertions.
 *
 * @param bool   $condition Test result.
 * @param string $message   Assertion description.
 * @return void
 * @throws RuntimeException On an assertion failure.
 */
function adzworld_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( esc_html( $message ) );
	}
	WP_CLI::log( 'PASS ' . $message );
}

foreach ( array( 0, 1, 4, 5, 9, 10, 15, 99, 100, 101, 100005, PHP_INT_MAX ) as $amount ) {
	$split = adzworld_split_commission( $amount );
	adzworld_test_assert( $split['service_minor'] + $split['publisher_minor'] === $amount, 'Exact split conserves ' . $amount . ' minor units' );
}
adzworld_test_assert(
	array(
		'service_minor'   => 10,
		'publisher_minor' => 91,
	) === adzworld_split_commission( 101 ),
	'101 cents splits into 10 and 91 cents'
);
adzworld_test_assert( is_wp_error( adzworld_split_commission( -1 ) ), 'Negative commissions are rejected' );
adzworld_test_assert( is_wp_error( adzworld_split_commission( 1.0 ) ), 'Floating-point commissions are rejected' );
adzworld_test_assert( 'USD 1.01' === adzworld_format_money( 101, 'USD' ), 'USD uses two decimals' );
adzworld_test_assert( 'JPY 101' === adzworld_format_money( 101, 'JPY' ), 'JPY uses no decimals' );
adzworld_test_assert( 'KWD 0.101' === adzworld_format_money( 101, 'KWD' ), 'KWD uses three decimals' );
adzworld_test_assert( 'USD -0.01' === adzworld_format_money( -1, 'USD' ), 'Negative adjustments retain their sign' );
adzworld_test_assert( is_wp_error( adzworld_format_money( 1, 'XXX' ) ), 'Unknown currency is rejected' );

$statement = array(
	'publisher_id' => 'publisher_test',
	'generated_at' => time(),
	'currencies'   => array(
		array(
			'currency'        => 'USD',
			'settled_minor'   => 101,
			'reversed_minor'  => 0,
			'estimated_minor' => 15,
			'service_minor'   => 10,
			'publisher_minor' => 91,
			'available_minor' => 51,
			'reserved_minor'  => 10,
			'paid_minor'      => 30,
		),
	),
);
adzworld_test_assert( is_array( adzworld_validate_statement( $statement, 'publisher_test' ) ), 'Balanced statement is accepted' );
adzworld_test_assert( is_wp_error( adzworld_validate_statement( $statement, 'another_publisher' ) ), 'Another publisher statement is rejected' );
$bad = $statement;
++$bad['currencies'][0]['available_minor'];
adzworld_test_assert( is_wp_error( adzworld_validate_statement( $bad, 'publisher_test' ) ), 'Unbalanced available balance is rejected' );
$bad['currencies'] = array( $statement['currencies'][0], $statement['currencies'][0] );
adzworld_test_assert( is_wp_error( adzworld_validate_statement( $bad, 'publisher_test' ) ), 'Duplicate currency totals are rejected' );
$bad                  = $statement;
$bad['generated_at'] -= 2 * DAY_IN_SECONDS;
adzworld_test_assert( is_wp_error( adzworld_validate_statement( $bad, 'publisher_test' ) ), 'Stale statement is rejected' );
adzworld_test_assert( is_wp_error( adzworld_fetch_statement() ), 'Unconfigured statement connection is explicitly unavailable' );

foreach ( array( '../wp-config.php', '../../template1.php', '/tmp/template1.php', array(), 'template1.php/../bad.php' ) as $template ) {
	adzworld_test_assert( '' === adz_resolve_template( $template ), 'Invalid template path/type is rejected' );
}
adzworld_test_assert( is_file( adz_resolve_template( 'template1.php' ) ), 'Shipped template remains available' );
adzworld_test_assert( '' === adz_validate_rotation_id( 'siteurl' ), 'Core option is not a rotation ID' );
adzworld_test_assert( 'retained-content' === adz_shortcode( array( 'adz_sequence' => '0' ), 'retained-content' ), 'Shortcode without an available ad retains the article content' );

$probe = 'adzworld_codex_probe_' . wp_generate_password( 12, false );
add_option( $probe, 'retained', '', false );
$_POST['adz_ad_options'] = array( 'ad_settings' => array( array( 'rotation_id' => $probe ) ) );
wp_set_current_user( 0 );
adz_check_post_function();
adzworld_test_assert( 'retained' === get_option( $probe ), 'Unauthenticated init callback cannot delete arbitrary options' );
unset( $_POST['adz_ad_options'] );
delete_option( $probe );

$stripe = adzworld_sandbox_request( 'stripe', 101, 'USD', 'acct_test', 'test_operation_01' );
$paypal = adzworld_sandbox_request( 'paypal', 101, 'USD', 'sandbox@example.test', 'test_operation_01' );
adzworld_test_assert( 101 === $stripe['body']['amount'], 'Stripe payload uses integer minor units' );
adzworld_test_assert( '1.01' === $paypal['body']['items'][0]['amount']['value'], 'PayPal payload uses exact decimal strings' );
adzworld_test_assert( false !== strpos( $paypal['url'], '.sandbox.paypal.com/' ), 'PayPal destination is sandbox only' );
adzworld_test_assert( is_wp_error( adzworld_sandbox_request( 'stripe', 101, 'KWD', 'acct_test', 'test_operation_01' ) ), 'Unsupported provider currency is not silently converted' );
adzworld_test_assert( is_wp_error( adzworld_sandbox_request( 'paypal', 101, 'USD', 'sandbox@example.test', str_repeat( 'a', 22 ) ) ), 'Provider operation IDs leave room for the PayPal batch prefix' );
adzworld_test_assert( is_wp_error( adzworld_execute_sandbox_payout( 'stripe', 101, 'USD', 'acct_test', 'test_operation_01' ) ), 'Unconfigured sandbox cannot execute' );

WP_CLI::success( 'Candidate regression checks passed. Provider HTTP and financial settlement are not verified by these checks.' );
