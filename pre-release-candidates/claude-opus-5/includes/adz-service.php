<?php
/**
 * Client for the adz-world settlement service.
 *
 * IMPORTANT, AND TRUE AS OF THIS COMMIT: the settlement service described here
 * does not exist yet. No endpoint has been built, and no API contract has been
 * agreed. Nothing in this file invents one.
 *
 * What this file does instead:
 *   - states the contract the plugin would need, in one place, so the service
 *     can be built against it;
 *   - implements the client so that an unconfigured install reports exactly
 *     that, and never shows a balance it did not receive;
 *   - refuses to display or act on any figure the service has not sent.
 *
 * An unconfigured install is the normal state today. It must look like one.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * The service contract this client expects.
 *
 * PROPOSED. Not implemented anywhere. Recorded here so that whoever builds the
 * service builds the thing the plugin already speaks.
 *
 * Base: https://adz-world.com/wp-json/adzworld/v1
 * Auth: Authorization: Bearer <publisher token>, over TLS, certificate
 *       verification left on. The token is issued per site at opt-in and is
 *       stored on the site; it is not a platform secret and grants read access
 *       to that publisher's own statement plus the ability to request a payout
 *       against their own balance.
 *
 *   GET  /statement?since=<ISO8601 cursor>
 *        -> { "cursor": "...", "generated_at": "...", "lines": [ line, ... ] }
 *
 *        line = {
 *          "external_ref":  unique, stable, the idempotency key for the mirror
 *          "occurred_at_gmt": ISO8601
 *          "line_type":     "commission" | "adjustment"
 *          "state":         "pending" | "settled" | "reversed" | "adjusted"
 *          "currency":      ISO 4217
 *          "settled_minor": integer minor units, may be negative for reversals
 *          "fee_minor":     integer, adz-world's share, must match the split
 *          "basis_points":  integer, the rate applied to THIS line
 *          "merchant":      string
 *          "description":   string
 *        }
 *
 *   GET  /balances
 *        -> { "balances": { "<CUR>": { "available_minor": n, ... } } }
 *        The service is authoritative. The mirror is checked against this and
 *        a disagreement is surfaced, never silently reconciled.
 *
 *   POST /payouts
 *        { "idempotency_key": uuid4, "currency": ISO4217, "amount_minor": int,
 *          "method": "stripe" | "paypal" }
 *        -> { "external_ref": "...", "state": "requested", ... }
 *        The service re-checks ownership and the available balance server-side.
 *        A browser-supplied amount is a request, never an authorisation.
 *
 *   GET  /payouts/<external_ref>
 *        -> { "state": "requested"|"approved"|"in_flight"|"paid"|"failed"|"cancelled", ... }
 *
 * Provider webhooks (Stripe, PayPal) are received by adz-world.com, verified
 * against the provider's signature there, and reflected into the statement.
 * They are never delivered to publisher installations, and no provider secret
 * is ever held by this plugin.
 *
 * @return array The contract, for display in the dashboard and in docs.
 */
function adz_service_contract() {
	return array(
		'status'   => 'proposed',
		'base'     => 'https://adz-world.com/wp-json/adzworld/v1',
		'built'    => false,
		'endpoints' => array(
			'GET /statement?since=',
			'GET /balances',
			'POST /payouts',
			'GET /payouts/{ref}',
		),
	);
}

/**
 * Whether this site has been connected to the settlement service.
 *
 * @return bool
 */
function adz_service_is_configured() {
	$creds = get_option( 'adz_service_credentials' );

	return ! empty( $creds['base_url'] ) && ! empty( $creds['token'] );
}

/**
 * Why the service is unavailable, in words a publisher can act on.
 *
 * @return WP_Error|null Null when it is configured.
 */
function adz_service_unavailable_reason() {
	if ( ! adz_monetization_enabled() ) {
		return new WP_Error(
			'adz_not_opted_in',
			__( 'Monetization is switched off for this site. No adz are being monetized and there is nothing to report.', 'adz-world' )
		);
	}

	if ( ! adz_service_is_configured() ) {
		return new WP_Error(
			'adz_unconfigured',
			__( 'This site is not connected to the adz-world settlement service. The service is not yet available, so no earnings can be reported.', 'adz-world' )
		);
	}

	return null;
}

/**
 * Call the service.
 *
 * Returns WP_Error for every failure mode, including "not built yet". It never
 * returns a figure it did not receive, and it never falls back to a sample,
 * a cached guess or a zero presented as a balance.
 *
 * @param string $method HTTP method.
 * @param string $path   Path below the base, e.g. '/statement'.
 * @param array  $body   Request body for POST.
 * @return array|WP_Error Decoded response, or the reason it could not be had.
 */
function adz_service_request( $method, $path, $body = array() ) {
	$unavailable = adz_service_unavailable_reason();

	if ( $unavailable ) {
		return $unavailable;
	}

	$creds = get_option( 'adz_service_credentials' );
	$url   = untrailingslashit( esc_url_raw( $creds['base_url'] ) ) . '/' . ltrim( $path, '/' );

	$args = array(
		'method'    => strtoupper( $method ),
		'timeout'   => 15,
		'sslverify' => true,
		'headers'   => array(
			'Authorization' => 'Bearer ' . $creds['token'],
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
		),
	);

	if ( ! empty( $body ) ) {
		$args['body'] = wp_json_encode( $body );
	}

	$response = wp_remote_request( $url, $args );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error(
			'adz_service_http_' . $code,
			sprintf(
				/* translators: %d: HTTP status code. */
				__( 'The adz-world service returned status %d.', 'adz-world' ),
				$code
			)
		);
	}

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'adz_service_bad_payload', __( 'The adz-world service returned a response that could not be read.', 'adz-world' ) );
	}

	return $data;
}

/**
 * Pull new statement lines into the mirror.
 *
 * Idempotent: a line already held is counted as a duplicate and ignored, so a
 * replayed or overlapping statement cannot double-count.
 *
 * @return array|WP_Error Counts, or why it could not run.
 */
function adz_service_sync_statement() {
	$since    = get_option( 'adz_ledger_cursor', '' );
	$response = adz_service_request( 'GET', '/statement?since=' . rawurlencode( $since ) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$counts = array(
		'inserted'  => 0,
		'duplicate' => 0,
		'rejected'  => 0,
	);

	foreach ( (array) ( isset( $response['lines'] ) ? $response['lines'] : array() ) as $line ) {
		$result = adz_ledger_record( $line );
		++$counts[ $result ];
	}

	if ( ! empty( $response['cursor'] ) ) {
		update_option( 'adz_ledger_cursor', sanitize_text_field( $response['cursor'] ), false );
	}

	update_option( 'adz_ledger_last_synced', time(), false );

	return $counts;
}
