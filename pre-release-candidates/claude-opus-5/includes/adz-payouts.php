<?php
/**
 * Payout requests.
 *
 * The plugin never moves money and never holds a provider credential. It asks
 * adz-world.com to pay a publisher what adz-world.com has already decided is
 * owed, and it records the answer.
 *
 * A browser-supplied amount is a request, never an authorisation. The local
 * balance check below exists to give a publisher a sensible error without a
 * round trip; it is not the control. The service re-checks ownership and the
 * available balance server-side before anything is paid, and its answer wins.
 *
 * State machine, and the only legal moves:
 *
 *   requested ──► approved ──► in_flight ──► paid
 *        │             │            │
 *        │             │            └──► failed
 *        │             └──────────────► failed
 *        ├──► cancelled
 *        └──► failed
 *
 * paid, failed and cancelled are terminal. Nothing leaves them.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Legal transitions, keyed by current state.
 *
 * @return array<string,string[]>
 */
function adz_payout_transitions() {
	return array(
		'requested' => array( 'approved', 'cancelled', 'failed' ),
		'approved'  => array( 'in_flight', 'failed' ),
		'in_flight' => array( 'paid', 'failed' ),
		'paid'      => array(),
		'failed'    => array(),
		'cancelled' => array(),
	);
}

/**
 * Whether a payout may move from one state to another.
 *
 * @param string $from Current state.
 * @param string $to   Proposed state.
 * @return bool
 */
function adz_payout_can_transition( $from, $to ) {
	$map = adz_payout_transitions();

	return isset( $map[ $from ] ) && in_array( $to, $map[ $from ], true );
}

/**
 * States in which a payout is holding funds that cannot be requested again.
 *
 * @return string[]
 */
function adz_payout_open_states() {
	return array( 'requested', 'approved', 'in_flight' );
}

/**
 * Request a payout.
 *
 * @param string $currency     ISO 4217 code.
 * @param int    $amount_minor Amount in minor units, as requested by the publisher.
 * @param string $method       'stripe' or 'paypal'.
 * @return array|WP_Error The recorded request, or why it was refused.
 */
function adz_payout_request( $currency, $amount_minor, $method ) {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'adz_forbidden', __( 'You do not have permission to request a payout.', 'adz-world' ), array( 'status' => 403 ) );
	}

	$unavailable = adz_service_unavailable_reason();

	if ( $unavailable ) {
		return $unavailable;
	}

	$currency     = strtoupper( substr( (string) $currency, 0, 3 ) );
	$amount_minor = (int) $amount_minor;
	$method       = sanitize_key( $method );

	if ( ! adz_is_valid_currency( $currency ) ) {
		return new WP_Error( 'adz_bad_currency', __( 'That is not a valid currency code.', 'adz-world' ) );
	}

	if ( $amount_minor <= 0 ) {
		return new WP_Error( 'adz_bad_amount', __( 'A payout amount must be greater than zero.', 'adz-world' ) );
	}

	if ( ! in_array( $method, array( 'stripe', 'paypal' ), true ) ) {
		return new WP_Error( 'adz_bad_method', __( 'Choose a payout method that is available.', 'adz-world' ) );
	}

	$table = adz_payouts_table();

	// One open request per currency. Simplest rule that cannot double-spend.
	$open = adz_payout_open_states();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL -- plugin-owned table; states are a fixed internal list.
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table} WHERE currency = %s AND state IN ('" . implode( "','", $open ) . "') LIMIT 1",
			$currency
		)
	);

	if ( $existing ) {
		return new WP_Error( 'adz_payout_open', __( 'A payout in this currency is already in progress.', 'adz-world' ) );
	}

	$balances  = adz_ledger_balances();
	$available = isset( $balances[ $currency ]['available_minor'] ) ? (int) $balances[ $currency ]['available_minor'] : 0;

	if ( $amount_minor > $available ) {
		return new WP_Error(
			'adz_insufficient',
			sprintf(
				/* translators: %s: formatted available balance. */
				__( 'That is more than the available balance of %s.', 'adz-world' ),
				adz_format_money( $available, $currency )
			)
		);
	}

	// Generated here, not supplied by the browser, so a replayed form submission
	// cannot create a second payout.
	$idempotency_key = wp_generate_uuid4();
	$now             = gmdate( 'Y-m-d H:i:s' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- plugin-owned table.
	$inserted = $wpdb->insert(
		$table,
		array(
			'idempotency_key'  => $idempotency_key,
			'external_ref'     => '',
			'requested_at_gmt' => $now,
			'updated_at_gmt'   => $now,
			'requested_by'     => get_current_user_id(),
			'currency'         => $currency,
			'amount_minor'     => $amount_minor,
			'method'           => $method,
			'destination_hint' => '',
			'state'            => 'requested',
		),
		array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s' )
	);

	if ( ! $inserted ) {
		return new WP_Error( 'adz_payout_not_recorded', __( 'The payout request could not be recorded.', 'adz-world' ) );
	}

	$local_id = (int) $wpdb->insert_id;

	$response = adz_service_request(
		'POST',
		'/payouts',
		array(
			'idempotency_key' => $idempotency_key,
			'currency'        => $currency,
			'amount_minor'    => $amount_minor,
			'method'          => $method,
		)
	);

	if ( is_wp_error( $response ) ) {
		adz_payout_set_state( $local_id, 'failed', $response->get_error_message() );

		return $response;
	}

	if ( ! empty( $response['external_ref'] ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- plugin-owned table.
		$wpdb->update(
			$table,
			array(
				'external_ref'   => substr( sanitize_text_field( $response['external_ref'] ), 0, 191 ),
				'updated_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $local_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	return array(
		'id'              => $local_id,
		'idempotency_key' => $idempotency_key,
		'state'           => 'requested',
		'currency'        => $currency,
		'amount_minor'    => $amount_minor,
	);
}

/**
 * Move a payout to a new state, refusing an illegal move.
 *
 * @param int    $id     Local payout row id.
 * @param string $to     Proposed state.
 * @param string $reason Failure reason, when relevant.
 * @return bool|WP_Error True on success.
 */
function adz_payout_set_state( $id, $to, $reason = '' ) {
	global $wpdb;

	$table = adz_payouts_table();
	$id    = (int) $id;
	$to    = sanitize_key( $to );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- plugin-owned table.
	$from = $wpdb->get_var( $wpdb->prepare( "SELECT state FROM {$table} WHERE id = %d", $id ) );

	if ( null === $from ) {
		return new WP_Error( 'adz_payout_missing', __( 'That payout could not be found.', 'adz-world' ) );
	}

	if ( $from === $to ) {
		// Replayed event for a state already reached. Not an error, not a change.
		return true;
	}

	if ( ! adz_payout_can_transition( $from, $to ) ) {
		return new WP_Error(
			'adz_payout_illegal_transition',
			sprintf(
				/* translators: 1: current state, 2: proposed state. */
				__( 'A payout cannot move from %1$s to %2$s.', 'adz-world' ),
				$from,
				$to
			)
		);
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- plugin-owned table.
	$wpdb->update(
		$table,
		array(
			'state'          => $to,
			'failure_reason' => sanitize_textarea_field( $reason ),
			'updated_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
		),
		array( 'id' => $id ),
		array( '%s', '%s', '%s' ),
		array( '%d' )
	);

	return true;
}
