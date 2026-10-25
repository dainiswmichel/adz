<?php
/**
 * Opt-in, administrator-only payment-provider evaluation. Never live payments.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Require an explicitly configured settlement test host and administrator.
 *
 * @return true|WP_Error Whether sandbox execution is authorized.
 */
function adzworld_sandbox_permission() {
	if ( ! defined( 'ADZWORLD_ENABLE_SANDBOX_PAYMENTS' ) || true !== ADZWORLD_ENABLE_SANDBOX_PAYMENTS
		|| ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'adzworld_sandbox_disabled', __( 'Payment sandbox execution requires explicit server configuration and administrator authorization.', 'adz-world' ) );
	}
	return true;
}

/**
 * Create a reproducible sandbox payout request without credentials or HTTP.
 *
 * @param string $provider  stripe or paypal.
 * @param int    $minor     Positive currency minor units.
 * @param string $currency  Supported payout currency.
 * @param string $recipient Connected test account ID or sandbox PayPal email.
 * @param string $operation Stable random operation ID, reused for retries.
 * @return array|WP_Error Validated request descriptor.
 */
function adzworld_sandbox_request( $provider, $minor, $currency, $recipient, $operation ) {
	$currencies = array( 'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'CHF', 'NZD', 'JPY' );
	if ( ! is_int( $minor ) || $minor <= 0 || $minor > 99999999 || ! in_array( $currency, $currencies, true )
		|| ! is_string( $operation ) || ! preg_match( '/^[A-Za-z0-9_-]{16,21}$/D', $operation ) || ! is_string( $recipient ) ) {
		return new WP_Error( 'adzworld_payout_input', __( 'A valid sandbox amount, currency, recipient, and operation ID are required.', 'adz-world' ) );
	}
	if ( 'stripe' === $provider && preg_match( '/^acct_[A-Za-z0-9]+$/D', $recipient ) ) {
		return array(
			'url'     => 'https://api.stripe.com/v1/transfers',
			'headers' => array( 'Idempotency-Key' => 'adzworld_' . $operation ),
			'body'    => array(
				'amount'      => $minor,
				'currency'    => strtolower( $currency ),
				'destination' => $recipient,
			),
		);
	}
	if ( 'paypal' === $provider && is_email( $recipient ) ) {
		$formatted = adzworld_format_money( $minor, $currency );
		return array(
			'url'     => 'https://api-m.sandbox.paypal.com/v1/payments/payouts',
			'headers' => array(
				'PayPal-Request-Id' => $operation,
				'Content-Type'      => 'application/json',
			),
			'body'    => array(
				'sender_batch_header' => array( 'sender_batch_id' => 'adzworld_' . $operation ),
				'items'               => array(
					array(
						'recipient_type' => 'EMAIL',
						'receiver'       => $recipient,
						'sender_item_id' => $operation,
						'amount'         => array(
							'value'    => substr( $formatted, 4 ),
							'currency' => $currency,
						),
					),
				),
			),
		);
	}
	return new WP_Error( 'adzworld_payout_provider', __( 'Unsupported sandbox provider or recipient.', 'adz-world' ) );
}

/**
 * Execute a test transfer, with persistent request identity and explicit errors.
 *
 * No hook calls this function. A settlement-host administrator must explicitly
 * invoke it in an authenticated server-side test. It does not record earnings,
 * reserve balances, execute live payouts, or mark any publisher as paid.
 *
 * @param string $provider  Provider identifier.
 * @param int    $minor     Positive minor units.
 * @param string $currency  Supported currency.
 * @param string $recipient Sandbox recipient.
 * @param string $operation Stable operation ID for retries.
 * @return array|WP_Error Sandbox provider reference, not a paid ledger entry.
 */
function adzworld_execute_sandbox_payout( $provider, $minor, $currency, $recipient, $operation ) {
	$permission = adzworld_sandbox_permission();
	if ( is_wp_error( $permission ) ) {
		return $permission;
	}
	$request = adzworld_sandbox_request( $provider, $minor, $currency, $recipient, $operation );
	if ( is_wp_error( $request ) ) {
		return $request;
	}
	$headers = $request['headers'];
	if ( 'stripe' === $provider ) {
		if ( ! defined( 'ADZWORLD_STRIPE_TEST_SECRET' ) || ! is_string( ADZWORLD_STRIPE_TEST_SECRET )
			|| ! preg_match( '/^sk_test_[A-Za-z0-9]+$/D', ADZWORLD_STRIPE_TEST_SECRET ) ) {
			return new WP_Error( 'adzworld_stripe_config', __( 'A Stripe test secret is required. Live keys are rejected.', 'adz-world' ) );
		}
		$headers['Authorization'] = 'Bearer ' . ADZWORLD_STRIPE_TEST_SECRET;
	} else {
		if ( ! defined( 'ADZWORLD_PAYPAL_SANDBOX_CLIENT' ) || ! defined( 'ADZWORLD_PAYPAL_SANDBOX_SECRET' )
			|| ! is_string( ADZWORLD_PAYPAL_SANDBOX_CLIENT ) || ! ADZWORLD_PAYPAL_SANDBOX_CLIENT
			|| ! is_string( ADZWORLD_PAYPAL_SANDBOX_SECRET ) || ! ADZWORLD_PAYPAL_SANDBOX_SECRET ) {
			return new WP_Error( 'adzworld_paypal_config', __( 'PayPal sandbox client credentials are required.', 'adz-world' ) );
		}
		$auth = wp_safe_remote_post(
			'https://api-m.sandbox.paypal.com/v1/oauth2/token',
			array(
				'timeout'     => 15,
				'redirection' => 0,
				'headers'     => array( 'Authorization' => 'Basic ' . base64_encode( ADZWORLD_PAYPAL_SANDBOX_CLIENT . ':' . ADZWORLD_PAYPAL_SANDBOX_SECRET ) ),
				'body'        => array( 'grant_type' => 'client_credentials' ),
			)
		);
		if ( is_wp_error( $auth ) ) {
			return $auth;
		}
		$body = json_decode( wp_remote_retrieve_body( $auth ), true );
		if ( 200 !== wp_remote_retrieve_response_code( $auth ) || empty( $body['access_token'] ) || ! is_string( $body['access_token'] )
			|| preg_match( '/[\r\n]/', $body['access_token'] ) ) {
			return new WP_Error( 'adzworld_paypal_auth', __( 'PayPal sandbox authentication failed.', 'adz-world' ) );
		}
		$headers['Authorization'] = 'Bearer ' . $body['access_token'];
	}
	$option = 'adzworld_sandbox_' . $provider . '_' . $operation;
	$hash   = hash( 'sha256', wp_json_encode( $request['body'] ) );
	if ( ! add_option( $option, $hash, '', false ) && get_option( $option ) !== $hash ) {
		return new WP_Error( 'adzworld_payout_conflict', __( 'This operation ID already belongs to a different sandbox request.', 'adz-world' ) );
	}
	$response = wp_safe_remote_post(
		$request['url'],
		array(
			'timeout'             => 30,
			'redirection'         => 0,
			'limit_response_size' => 65536,
			'headers'             => $headers,
			'body'                => 'paypal' === $provider ? wp_json_encode( $request['body'] ) : $request['body'],
		)
	);
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'adzworld_payout_unknown', __( 'Sandbox request outcome is unknown. Reconciliation or a retry with the same operation ID is required; no payment is marked complete.', 'adz-world' ) );
	}
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$code = wp_remote_retrieve_response_code( $response );
	if ( ! in_array( $code, array( 200, 201, 202 ), true ) || ! is_array( $body ) ) {
		return new WP_Error( 'adzworld_payout_rejected', __( 'The sandbox provider rejected the request or returned an invalid response. The same operation ID is required for reconciliation.', 'adz-world' ) );
	}
	if ( 'stripe' === $provider ) {
		if ( ! isset( $body['id'], $body['livemode'], $body['amount'], $body['currency'], $body['destination'] )
			|| false !== $body['livemode'] || ! is_string( $body['id'] ) || ! preg_match( '/^tr_[A-Za-z0-9]+$/D', $body['id'] )
			|| $minor !== $body['amount'] || strtolower( $currency ) !== $body['currency'] || $recipient !== $body['destination'] ) {
			return new WP_Error( 'adzworld_stripe_response', __( 'Stripe did not confirm the expected test transfer.', 'adz-world' ) );
		}
		$reference = $body['id'];
	} else {
		if ( empty( $body['batch_header']['payout_batch_id'] ) || ! is_string( $body['batch_header']['payout_batch_id'] )
			|| empty( $body['batch_header']['sender_batch_header']['sender_batch_id'] )
			|| 'adzworld_' . $operation !== $body['batch_header']['sender_batch_header']['sender_batch_id'] ) {
			return new WP_Error( 'adzworld_paypal_response', __( 'PayPal did not confirm the expected sandbox batch.', 'adz-world' ) );
		}
		$reference = $body['batch_header']['payout_batch_id'];
	}
	return array(
		'provider'  => $provider,
		'reference' => $reference,
		'state'     => 'sandbox_submitted_not_settled',
	);
}
