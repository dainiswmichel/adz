<?php
/**
 * Money arithmetic.
 *
 * Every amount in this plugin is an integer in the currency's minor unit --
 * cents for USD, yen for JPY, fils for KWD. Floats are never used for money:
 * they lose fractions of a unit silently and the loss is not reproducible.
 *
 * adz-world.com is the authority for what is owed. This file exists so that
 * what the plugin displays is arithmetically consistent with the statements it
 * receives, not so that the plugin can decide anything about money.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/** adz-world's share of settled commission, in basis points. 1000 = 10%. */
const ADZ_FEE_BASIS_POINTS = 1000;

/**
 * Minor-unit exponents for currencies that are not two-decimal.
 *
 * ISO 4217. Anything absent from this table is treated as two decimals, which
 * is correct for the large majority of currencies.
 *
 * @var array<string,int>
 */
const ADZ_CURRENCY_EXPONENTS = array(
	'BIF' => 0,
	'CLP' => 0,
	'DJF' => 0,
	'GNF' => 0,
	'ISK' => 0,
	'JPY' => 0,
	'KMF' => 0,
	'KRW' => 0,
	'PYG' => 0,
	'RWF' => 0,
	'UGX' => 0,
	'UYI' => 0,
	'VND' => 0,
	'VUV' => 0,
	'XAF' => 0,
	'XOF' => 0,
	'XPF' => 0,
	'BHD' => 3,
	'IQD' => 3,
	'JOD' => 3,
	'KWD' => 3,
	'LYD' => 3,
	'OMR' => 3,
	'TND' => 3,
);

/**
 * How many decimal places a currency uses.
 *
 * @param string $currency ISO 4217 code.
 * @return int
 */
function adz_currency_exponent( $currency ) {
	$currency = strtoupper( substr( (string) $currency, 0, 3 ) );

	return array_key_exists( $currency, ADZ_CURRENCY_EXPONENTS )
		? ADZ_CURRENCY_EXPONENTS[ $currency ]
		: 2;
}

/**
 * Whether a string is a well-formed ISO 4217 code.
 *
 * @param string $currency Candidate code.
 * @return bool
 */
function adz_is_valid_currency( $currency ) {
	return 1 === preg_match( '/^[A-Z]{3}$/', (string) $currency );
}

/**
 * Split a settled commission into adz-world's share and the publisher's.
 *
 * The fee is computed first, with half-up rounding at the minor unit, and the
 * publisher's share is then the remainder. Taking the remainder rather than
 * computing both independently is what makes the invariant exact: the two
 * shares always sum to the settled amount, with no rounding drift, for every
 * input including odd amounts and zero-decimal currencies.
 *
 * @param int $settled_minor Settled commission in minor units. Must not be negative.
 * @param int $basis_points  adz-world's share in basis points. Defaults to 10%.
 * @return array{settled_minor:int,fee_minor:int,publisher_minor:int,basis_points:int}
 */
function adz_split_commission( $settled_minor, $basis_points = ADZ_FEE_BASIS_POINTS ) {
	$settled_minor = (int) $settled_minor;
	$basis_points  = max( 0, min( 10000, (int) $basis_points ) );

	if ( $settled_minor < 0 ) {
		// A reversal splits the same way, so the invariant holds on the way back out.
		$split = adz_split_commission( -$settled_minor, $basis_points );

		return array(
			'settled_minor'   => $settled_minor,
			'fee_minor'       => -$split['fee_minor'],
			'publisher_minor' => -$split['publisher_minor'],
			'basis_points'    => $basis_points,
		);
	}

	$fee_minor = intdiv( ( $settled_minor * $basis_points ) + 5000, 10000 );

	return array(
		'settled_minor'   => $settled_minor,
		'fee_minor'       => $fee_minor,
		'publisher_minor' => $settled_minor - $fee_minor,
		'basis_points'    => $basis_points,
	);
}

/**
 * Check that a split satisfies the accounting invariant.
 *
 * Called before any split is stored or displayed. A split that fails this is a
 * bug, and showing a publisher a number that does not reconcile is worse than
 * showing them an error.
 *
 * @param array $split Result of adz_split_commission().
 * @return bool
 */
function adz_split_is_consistent( $split ) {
	if ( ! isset( $split['settled_minor'], $split['fee_minor'], $split['publisher_minor'] ) ) {
		return false;
	}

	return ( (int) $split['fee_minor'] + (int) $split['publisher_minor'] ) === (int) $split['settled_minor'];
}

/**
 * Format an amount for display.
 *
 * Deliberately does not convert between currencies and does not accept a
 * locale-specific shortcut: the code is always shown, so a publisher reading a
 * statement can never mistake one currency for another.
 *
 * @param int    $minor    Amount in minor units.
 * @param string $currency ISO 4217 code.
 * @return string e.g. "1,234.56 USD", "1,234 JPY".
 */
function adz_format_money( $minor, $currency ) {
	$currency = strtoupper( substr( (string) $currency, 0, 3 ) );
	$exponent = adz_currency_exponent( $currency );
	$minor    = (int) $minor;
	$negative = $minor < 0;
	$minor    = abs( $minor );

	if ( 0 === $exponent ) {
		$amount = number_format_i18n( $minor );
	} else {
		$divisor = 10 ** $exponent;
		$whole   = intdiv( $minor, $divisor );
		$frac    = str_pad( (string) ( $minor % $divisor ), $exponent, '0', STR_PAD_LEFT );
		$amount  = number_format_i18n( $whole ) . '.' . $frac;
	}

	return ( $negative ? '-' : '' ) . $amount . ' ' . $currency;
}

/**
 * Sum amounts that share a currency, refusing to mix.
 *
 * Returns a WP_Error rather than a number when asked to add unlike currencies.
 * There is no exchange rate in this plugin and inventing one would make every
 * downstream figure fiction.
 *
 * @param array $rows Each row with 'minor' and 'currency' keys.
 * @return array<string,int>|WP_Error Totals keyed by currency.
 */
function adz_sum_by_currency( $rows ) {
	$totals = array();

	foreach ( (array) $rows as $row ) {
		if ( ! isset( $row['currency'], $row['minor'] ) ) {
			return new WP_Error( 'adz_bad_row', __( 'An amount was missing its currency.', 'adz-world' ) );
		}

		$currency = strtoupper( substr( (string) $row['currency'], 0, 3 ) );

		if ( ! adz_is_valid_currency( $currency ) ) {
			return new WP_Error( 'adz_bad_currency', __( 'An amount carried an invalid currency code.', 'adz-world' ) );
		}

		if ( ! isset( $totals[ $currency ] ) ) {
			$totals[ $currency ] = 0;
		}

		$totals[ $currency ] += (int) $row['minor'];
	}

	return $totals;
}
