<?php
/**
 * Provider transport contract tests using mocked WordPress HTTP, never money.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

require __DIR__ . '/regression.php';

$administrators = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
	)
);
adzworld_test_assert( ! empty( $administrators ), 'Test WordPress has an administrator' );
wp_set_current_user( $administrators[0]->ID );
define( 'ADZWORLD_ENABLE_SANDBOX_PAYMENTS', true );
define( 'ADZWORLD_STRIPE_TEST_SECRET', 'sk_test_' . str_repeat( 'dummy', 8 ) );
define( 'ADZWORLD_PAYPAL_SANDBOX_CLIENT', 'dummy-client-not-a-credential' );
define( 'ADZWORLD_PAYPAL_SANDBOX_SECRET', 'dummy-secret-not-a-credential' );

$operation = 'codex_' . strtolower( wp_generate_password( 15, false ) );
$requests  = array();
$transport = static function ( $preempt, $args, $url ) use ( &$requests, $operation ) {
	$requests[] = array(
		'url'  => $url,
		'args' => $args,
	);
	if ( 'https://api-m.sandbox.paypal.com/v1/oauth2/token' === $url ) {
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'access_token' => 'mock-token' ) ),
		);
	}
	if ( 'https://api.stripe.com/v1/transfers' === $url ) {
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode(
				array(
					'id'          => 'tr_mock',
					'livemode'    => false,
					'amount'      => 101,
					'currency'    => 'usd',
					'destination' => 'acct_mock',
				)
			),
		);
	}
	if ( 'https://api-m.sandbox.paypal.com/v1/payments/payouts' === $url ) {
		return array(
			'response' => array( 'code' => 201 ),
			'body'     => wp_json_encode(
				array(
					'batch_header' => array(
						'payout_batch_id'     => 'mock-batch',
						'sender_batch_header' => array( 'sender_batch_id' => 'adzworld_' . $operation ),
					),
				)
			),
		);
	}
	return new WP_Error( 'adzworld_test_transport', 'Unexpected HTTP is blocked during provider tests.' );
};
add_filter( 'pre_http_request', $transport, 10, 3 );
try {
	$result = adzworld_execute_sandbox_payout( 'stripe', 101, 'USD', 'acct_mock', $operation );
	adzworld_test_assert( is_array( $result ) && 'sandbox_submitted_not_settled' === $result['state'], 'Stripe test response is submitted, never ledger-paid' );
	adzworld_test_assert( is_wp_error( adzworld_execute_sandbox_payout( 'stripe', 102, 'USD', 'acct_mock', $operation ) ), 'An operation ID cannot be reused for a different amount' );
	$result = adzworld_execute_sandbox_payout( 'paypal', 101, 'USD', 'sandbox@example.test', $operation );
	adzworld_test_assert( is_array( $result ) && 'mock-batch' === $result['reference'], 'PayPal sandbox batch contract is accepted' );
	adzworld_test_assert( 3 === count( $requests ), 'Exactly three mocked provider HTTP requests, no request on conflicting retry' );
	wp_set_current_user( 0 );
	adzworld_test_assert( is_wp_error( adzworld_execute_sandbox_payout( 'stripe', 101, 'USD', 'acct_mock', $operation ) ), 'Anonymous callers cannot execute sandbox transfers even when configured' );
} finally {
	remove_filter( 'pre_http_request', $transport, 10 );
	delete_option( 'adzworld_sandbox_stripe_' . $operation );
	delete_option( 'adzworld_sandbox_paypal_' . $operation );
	wp_set_current_user( 0 );
}
WP_CLI::success( 'Mocked provider contracts passed. No real sandbox account or payment was contacted.' );
