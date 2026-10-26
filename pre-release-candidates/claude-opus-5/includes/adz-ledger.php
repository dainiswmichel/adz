<?php
/**
 * Local mirror of the publisher's statement.
 *
 * The adz-world.com service is the authority for what has been earned and what is payable.
 * This plugin stores a read-only copy so the dashboard can render without a
 * round trip, and so a publisher can see their history when the service is
 * unreachable. Nothing here decides an amount.
 *
 * This mirror is append-only and idempotent: a line is identified by the
 * service's own reference, and a line that already exists is never rewritten.
 * A statement that arrives twice, or out of order, changes nothing.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/** Bumped when either table changes. */
const ADZ_LEDGER_DB_VERSION = '1';

/**
 * Statement line table name.
 *
 * @return string
 */
function adz_ledger_table() {
	global $wpdb;

	return $wpdb->prefix . 'adz_ledger';
}

/**
 * Payout table name.
 *
 * @return string
 */
function adz_payouts_table() {
	global $wpdb;

	return $wpdb->prefix . 'adz_payouts';
}

/**
 * Create or upgrade the mirror tables.
 *
 * @return void
 */
function adz_ledger_install() {
	global $wpdb;

	if ( ! function_exists( 'dbDelta' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	$charset = $wpdb->get_charset_collate();
	$ledger  = adz_ledger_table();
	$payouts = adz_payouts_table();

	/*
	 * external_ref is unique: it is how a replayed statement is recognised and
	 * ignored rather than double-counted.
	 */
	dbDelta(
		"CREATE TABLE {$ledger} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			external_ref varchar(191) NOT NULL DEFAULT '',
			occurred_at_gmt datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			recorded_at_gmt datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			line_type varchar(32) NOT NULL DEFAULT '',
			state varchar(32) NOT NULL DEFAULT '',
			currency char(3) NOT NULL DEFAULT '',
			settled_minor bigint(20) NOT NULL DEFAULT 0,
			fee_minor bigint(20) NOT NULL DEFAULT 0,
			publisher_minor bigint(20) NOT NULL DEFAULT 0,
			basis_points int(11) NOT NULL DEFAULT 0,
			merchant varchar(191) NOT NULL DEFAULT '',
			description text NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY external_ref (external_ref),
			KEY state_currency (state,currency),
			KEY occurred_at (occurred_at_gmt)
		) {$charset};"
	);

	dbDelta(
		"CREATE TABLE {$payouts} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			idempotency_key char(36) NOT NULL DEFAULT '',
			external_ref varchar(191) NOT NULL DEFAULT '',
			requested_at_gmt datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			updated_at_gmt datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			requested_by bigint(20) unsigned NOT NULL DEFAULT 0,
			currency char(3) NOT NULL DEFAULT '',
			amount_minor bigint(20) NOT NULL DEFAULT 0,
			method varchar(32) NOT NULL DEFAULT '',
			destination_hint varchar(191) NOT NULL DEFAULT '',
			state varchar(32) NOT NULL DEFAULT '',
			failure_reason text NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY idempotency_key (idempotency_key),
			KEY state_currency (state,currency)
		) {$charset};"
	);

	update_option( 'adz_ledger_db_version', ADZ_LEDGER_DB_VERSION, false );
}

/**
 * Run the install when the stored schema version is behind.
 *
 * The register_activation_hook callback does not fire on plugin update.
 *
 * @return void
 */
function adz_ledger_maybe_upgrade() {
	if ( get_option( 'adz_ledger_db_version' ) === ADZ_LEDGER_DB_VERSION ) {
		return;
	}

	adz_ledger_install();
}

/**
 * The states a statement line can be in.
 *
 * Pending means the service expects this but the merchant has not confirmed it.
 * Settled means the merchant has paid the commission to adz-world. Reversed
 * means a previously settled commission was refunded or clawed back. Adjusted
 * means a correction issued by adz-world.
 *
 * Only 'settled' contributes to what a publisher can withdraw. 'pending' is
 * shown, clearly separated, because publishers want to see it -- not because
 * it is money they have.
 *
 * @return string[]
 */
function adz_ledger_states() {
	return array( 'pending', 'settled', 'reversed', 'adjusted' );
}

/**
 * Record one statement line, ignoring a line already held.
 *
 * @param array $line Line as supplied by the service.
 * @return string 'inserted', 'duplicate', or 'rejected'.
 */
function adz_ledger_record( $line ) {
	global $wpdb;

	$ref = isset( $line['external_ref'] ) ? substr( sanitize_text_field( (string) $line['external_ref'] ), 0, 191 ) : '';

	if ( '' === $ref ) {
		return 'rejected';
	}

	$currency = isset( $line['currency'] ) ? strtoupper( substr( (string) $line['currency'], 0, 3 ) ) : '';

	if ( ! adz_is_valid_currency( $currency ) ) {
		return 'rejected';
	}

	$state = isset( $line['state'] ) ? sanitize_key( $line['state'] ) : '';

	if ( ! in_array( $state, adz_ledger_states(), true ) ) {
		return 'rejected';
	}

	// The service supplies the settled amount; the split is recomputed here and
	// checked, so a statement that does not reconcile is refused rather than shown.
	$settled = isset( $line['settled_minor'] ) ? (int) $line['settled_minor'] : 0;
	$points  = isset( $line['basis_points'] ) ? (int) $line['basis_points'] : ADZ_FEE_BASIS_POINTS;
	$split   = adz_split_commission( $settled, $points );

	if ( ! adz_split_is_consistent( $split ) ) {
		return 'rejected';
	}

	if ( isset( $line['fee_minor'] ) && (int) $line['fee_minor'] !== $split['fee_minor'] ) {
		return 'rejected';
	}

	$table = adz_ledger_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- plugin-owned table, no core API.
	$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE external_ref = %s', $table, $ref ) );

	if ( $exists ) {
		return 'duplicate';
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- plugin-owned table.
	$wpdb->insert(
		$table,
		array(
			'external_ref'    => $ref,
			'occurred_at_gmt' => isset( $line['occurred_at_gmt'] ) ? sanitize_text_field( $line['occurred_at_gmt'] ) : gmdate( 'Y-m-d H:i:s' ),
			'recorded_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
			'line_type'       => isset( $line['line_type'] ) ? sanitize_key( $line['line_type'] ) : 'commission',
			'state'           => $state,
			'currency'        => $currency,
			'settled_minor'   => $split['settled_minor'],
			'fee_minor'       => $split['fee_minor'],
			'publisher_minor' => $split['publisher_minor'],
			'basis_points'    => $split['basis_points'],
			'merchant'        => isset( $line['merchant'] ) ? substr( sanitize_text_field( (string) $line['merchant'] ), 0, 191 ) : '',
			'description'     => isset( $line['description'] ) ? sanitize_textarea_field( (string) $line['description'] ) : '',
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' )
	);

	return 'inserted';
}

/**
 * The publisher's balances, per currency.
 *
 * Every figure is derived from recorded lines. Nothing is estimated here and
 * nothing is converted between currencies.
 *
 * @return array<string,array<string,int>> Keyed by currency.
 */
function adz_ledger_balances() {
	global $wpdb;

	$ledger  = adz_ledger_table();
	$payouts = adz_payouts_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- plugin-owned table, no core API.
	$lines = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT currency, state, SUM(settled_minor) AS settled, SUM(fee_minor) AS fee, SUM(publisher_minor) AS publisher FROM %i GROUP BY currency, state',
			$ledger
		)
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- plugin-owned table, no core API.
	$paid = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT currency, state, SUM(amount_minor) AS amount FROM %i GROUP BY currency, state',
			$payouts
		)
	);

	$out = array();

	foreach ( (array) $lines as $row ) {
		$currency = strtoupper( (string) $row->currency );

		if ( ! isset( $out[ $currency ] ) ) {
			$out[ $currency ] = adz_empty_balance();
		}

		switch ( $row->state ) {
			case 'settled':
				$out[ $currency ]['settled_minor']   += (int) $row->settled;
				$out[ $currency ]['fee_minor']       += (int) $row->fee;
				$out[ $currency ]['publisher_minor'] += (int) $row->publisher;
				break;
			case 'pending':
				$out[ $currency ]['pending_minor'] += (int) $row->publisher;
				break;
			case 'reversed':
				$out[ $currency ]['reversed_minor']  += (int) $row->publisher;
				$out[ $currency ]['publisher_minor'] += (int) $row->publisher;
				$out[ $currency ]['settled_minor']   += (int) $row->settled;
				$out[ $currency ]['fee_minor']       += (int) $row->fee;
				break;
			case 'adjusted':
				$out[ $currency ]['adjusted_minor']  += (int) $row->publisher;
				$out[ $currency ]['publisher_minor'] += (int) $row->publisher;
				$out[ $currency ]['settled_minor']   += (int) $row->settled;
				$out[ $currency ]['fee_minor']       += (int) $row->fee;
				break;
		}
	}

	foreach ( (array) $paid as $row ) {
		$currency = strtoupper( (string) $row->currency );

		if ( ! isset( $out[ $currency ] ) ) {
			$out[ $currency ] = adz_empty_balance();
		}

		if ( in_array( $row->state, array( 'requested', 'approved', 'in_flight' ), true ) ) {
			$out[ $currency ]['reserved_minor'] += (int) $row->amount;
		} elseif ( 'paid' === $row->state ) {
			$out[ $currency ]['paid_minor'] += (int) $row->amount;
		}
	}

	foreach ( $out as $currency => $b ) {
		$out[ $currency ]['available_minor'] = $b['publisher_minor'] - $b['reserved_minor'] - $b['paid_minor'];
		$out[ $currency ]['reconciles']      = ( $b['fee_minor'] + $b['publisher_minor'] ) === $b['settled_minor'];
	}

	return $out;
}

/**
 * A zeroed balance row.
 *
 * @return array<string,int>
 */
function adz_empty_balance() {
	return array(
		'settled_minor'   => 0,
		'fee_minor'       => 0,
		'publisher_minor' => 0,
		'pending_minor'   => 0,
		'reversed_minor'  => 0,
		'adjusted_minor'  => 0,
		'reserved_minor'  => 0,
		'paid_minor'      => 0,
		'available_minor' => 0,
		'reconciles'      => true,
	);
}

/**
 * When the mirror was last refreshed from the service.
 *
 * @return int Unix timestamp, or 0 when never.
 */
function adz_ledger_last_synced() {
	return (int) get_option( 'adz_ledger_last_synced', 0 );
}
