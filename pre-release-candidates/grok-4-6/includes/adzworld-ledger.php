<?php
/**
 * Earnings screen.
 *
 * The adz-world.com service is the authority for verified receipts, attribution,
 * accounting, and payouts. This screen displays an authenticated statement
 * when one is supplied by server-side code. Nothing in this plugin supplies
 * one, so the screen reports that the service is unconfigured. It does not
 * invent a balance, move money, or store a provider secret.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minor-unit exponents for currencies this screen knows how to display.
 *
 * An absent code is unknown. The screen does not guess an exponent and does
 * not convert one currency into another.
 *
 * @return array<string,int>
 */
function adzworld_currency_exponents() {
	$two = array( 'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'CHF', 'SEK', 'NOK', 'DKK', 'NZD', 'MXN', 'BRL', 'INR', 'SGD', 'HKD', 'TWD', 'PLN', 'CZK', 'HUF', 'RON', 'BGN', 'ZAR', 'AED', 'SAR', 'ILS', 'TRY', 'THB', 'MYR', 'PHP', 'IDR' );
	$map = array();

	foreach ( $two as $code ) {
		$map[ $code ] = 2;
	}

	foreach ( array( 'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' ) as $code ) {
		$map[ $code ] = 0;
	}

	foreach ( array( 'BHD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND' ) as $code ) {
		$map[ $code ] = 3;
	}

	return $map;
}

/**
 * Exponent for one ISO currency code, or null when this plugin does not know it.
 *
 * @param string $currency Currency code.
 * @return int|null
 */
function adzworld_currency_exponent( $currency ) {
	$map  = adzworld_currency_exponents();
	$code = strtoupper( (string) $currency );

	return isset( $map[ $code ] ) ? $map[ $code ] : null;
}

/**
 * Format an integer minor-unit amount in its own currency.
 *
 * @param int    $minor    Minor units.
 * @param string $currency ISO code.
 * @return string
 */
function adzworld_format_minor( $minor, $currency ) {
	$currency = strtoupper( (string) $currency );
	$exponent = adzworld_currency_exponent( $currency );
	$minor    = (int) $minor;

	if ( null === $exponent ) {
		return sprintf(
			/* translators: 1: integer minor units, 2: currency code */
			__( '%1$d minor units of %2$s (display exponent unknown)', 'adz-world' ),
			$minor,
			$currency
		);
	}

	$negative = $minor < 0;
	$absolute = $negative ? -$minor : $minor;
	$scale    = (int) pow( 10, $exponent );
	$major    = intdiv( $absolute, $scale );
	$fraction = $absolute % $scale;
	$text     = $currency . ' ' . number_format( $major, 0, '.', ',' );

	if ( $exponent > 0 ) {
		$text .= '.' . str_pad( (string) $fraction, $exponent, '0', STR_PAD_LEFT );
	}

	return $negative ? '-' . $text : $text;
}

/**
 * Buckets a statement must carry for each currency.
 *
 * @return string[]
 */
function adzworld_statement_buckets() {
	return array(
		'pending_minor',
		'settled_minor',
		'platform_minor',
		'publisher_minor',
		'reversal_minor',
		'adjustment_minor',
		'available_minor',
		'reserved_minor',
		'paid_minor',
	);
}

/**
 * Human labels for statement buckets.
 *
 * @return array<string,string>
 */
function adzworld_statement_labels() {
	return array(
		'pending_minor'    => __( 'Estimates / pending', 'adz-world' ),
		'settled_minor'    => __( 'Verified settled affiliate commission', 'adz-world' ),
		'platform_minor'   => __( 'adz-world share (10% of settled)', 'adz-world' ),
		'publisher_minor'  => __( 'Publisher share (90% of settled)', 'adz-world' ),
		'reversal_minor'   => __( 'Reversals / refunds', 'adz-world' ),
		'adjustment_minor' => __( 'Adjustments', 'adz-world' ),
		'available_minor'  => __( 'Available', 'adz-world' ),
		'reserved_minor'   => __( 'Reserved / in-flight', 'adz-world' ),
		'paid_minor'       => __( 'Paid', 'adz-world' ),
	);
}

/**
 * Validate a statement payload. Reject it when the share invariant fails.
 *
 * @param mixed $payload Candidate statement.
 * @return array<string,mixed>
 */
function adzworld_validate_statement( $payload ) {
	$failed = array(
		'ok'     => false,
		'state'  => 'unavailable',
		'reason' => __( 'The statement could not be validated.', 'adz-world' ),
	);

	if ( ! is_array( $payload ) || empty( $payload['currencies'] ) || ! is_array( $payload['currencies'] ) ) {
		$failed['reason'] = __( 'No per-currency statement was supplied.', 'adz-world' );
		return $failed;
	}

	$currencies = array();

	foreach ( $payload['currencies'] as $code => $row ) {
		$code = strtoupper( (string) $code );

		if ( ! preg_match( '/^[A-Z]{3}$/', $code ) || null === adzworld_currency_exponent( $code ) || ! is_array( $row ) ) {
			$failed['reason'] = __( 'A statement currency is missing or has no known minor-unit exponent.', 'adz-world' );
			return $failed;
		}

		$clean = array();

		foreach ( adzworld_statement_buckets() as $bucket ) {
			if ( ! isset( $row[ $bucket ] ) || ! is_int( $row[ $bucket ] ) ) {
				$failed['reason'] = __( 'A statement amount is missing or is not an integer minor-unit value.', 'adz-world' );
				return $failed;
			}
			$clean[ $bucket ] = $row[ $bucket ];
		}

		if ( ( $clean['platform_minor'] + $clean['publisher_minor'] ) !== $clean['settled_minor'] ) {
			$failed['reason'] = __( 'The platform share plus the publisher share does not equal the settled amount.', 'adz-world' );
			return $failed;
		}

		$currencies[ $code ] = $clean;
	}

	$fetched = isset( $payload['fetched_gmt'] ) ? (string) $payload['fetched_gmt'] : '';

	if ( ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $fetched ) ) {
		$fetched = '';
	}

	return array(
		'ok'          => true,
		'state'       => 'statement',
		'fetched_gmt' => $fetched,
		'currencies'  => $currencies,
	);
}

/**
 * Statement for the current site.
 *
 * The adzworld_statement_payload filter is for server-side code and tests.
 * The plugin does not attach a callback, so a normal request is unconfigured.
 * Browser input is not read here.
 *
 * @return array<string,mixed>
 */
function adzworld_statement() {
	$payload = apply_filters( 'adzworld_statement_payload', null );

	if ( null === $payload || false === $payload ) {
		return array(
			'ok'     => false,
			'state'  => 'unconfigured',
			'reason' => __( 'Unconfigured. adz-world.com has not issued a statement credential to this site. This screen is not a balance and it cannot pay out.', 'adz-world' ),
		);
	}

	return adzworld_validate_statement( $payload );
}

/**
 * Provider preparation state. Both stay unconfigured inside the plugin.
 *
 * @param string $provider stripe or paypal.
 * @return string
 */
function adzworld_provider_state( $provider ) {
	if ( 'stripe' === $provider || 'paypal' === $provider ) {
		return 'unconfigured';
	}

	return 'unknown';
}

/**
 * Next payout state, or an empty string when the event is not legal.
 *
 * No caller in this plugin applies the transition to stored money.
 *
 * @param string $state Current state.
 * @param string $event Event name.
 * @return string
 */
function adzworld_payout_transition( $state, $event ) {
	$map = array(
		'none'      => array( 'reserve' => 'reserved' ),
		'reserved'  => array(
			'provider_accepted' => 'in_flight',
			'provider_failed'   => 'failed',
		),
		'in_flight' => array(
			'provider_paid'   => 'paid',
			'provider_failed' => 'failed',
		),
		'paid'      => array( 'reversal' => 'reversed' ),
		'failed'    => array( 'reserve' => 'reserved' ),
		'reversed'  => array(),
	);

	if ( ! isset( $map[ $state ][ $event ] ) ) {
		return '';
	}

	return $map[ $state ][ $event ];
}

/**
 * Whether an event id is shaped like an idempotency key.
 *
 * @param string $event_id Candidate id.
 * @return bool
 */
function adzworld_valid_event_id( $event_id ) {
	return is_string( $event_id ) && 1 === preg_match( '/^[A-Za-z0-9_-]{8,64}$/', $event_id );
}

/**
 * Earnings screen under the adz menu.
 *
 * @return void
 */
function adzworld_earnings_menu() {
	add_submenu_page(
		'edit.php?post_type=adz_ad',
		__( 'Earnings', 'adz-world' ),
		__( 'Earnings', 'adz-world' ),
		'manage_options',
		'adzworld-earnings',
		'adzworld_render_earnings'
	);
}
add_action( 'admin_menu', 'adzworld_earnings_menu' );

/**
 * Print one minor-unit row.
 *
 * @param string $label    Row label.
 * @param string $value    Display value.
 * @return void
 */
function adzworld_earnings_row( $label, $value ) {
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
}

/**
 * Print the earnings screen.
 *
 * @return void
 */
function adzworld_render_earnings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$statement = adzworld_statement();
	$split     = adzworld_split_cents( 1000 );
	$options   = get_option( 'adz_ad_options' );
	$paypal    = ( is_array( $options ) && isset( $options['paypal_email'] ) ) ? sanitize_email( (string) $options['paypal_email'] ) : '';
	$code      = adzworld_publisher_referral_code();

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'adz-world earnings', 'adz-world' ) . '</h1>';
	echo wp_kses_post( adzworld_terms_html() );
	echo wp_kses_post( adzworld_privacy_html() );

	if ( ! adzworld_terms_accepted() ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'adzworld_accept_terms' );
		echo '<input type="hidden" name="action" value="adzworld_accept_terms" />';
		submit_button( __( 'Accept the monetization terms', 'adz-world' ) );
		echo '</form>';
	} else {
		echo '<p>' . esc_html__( 'The monetization terms are accepted on this site. Outbound network requests still require the existing network switch. Accepting the terms does not rewrite ad links.', 'adz-world' ) . '</p>';
	}

	echo '<h2>' . esc_html__( 'Statement', 'adz-world' ) . '</h2>';

	if ( empty( $statement['ok'] ) ) {
		echo '<p>' . esc_html( isset( $statement['reason'] ) ? (string) $statement['reason'] : '' ) . '</p>';
		echo '<table class="widefat striped"><tbody>';
		foreach ( adzworld_statement_labels() as $label ) {
			adzworld_earnings_row( $label, __( 'Unconfigured', 'adz-world' ) );
		}
		adzworld_earnings_row( __( 'Currency', 'adz-world' ), __( 'None. Unlike currencies are not added together.', 'adz-world' ) );
		adzworld_earnings_row( __( 'Statement freshness', 'adz-world' ), __( 'No statement has been fetched.', 'adz-world' ) );
		echo '</tbody></table>';
	} else {
		$fresh = '' === $statement['fetched_gmt']
			? __( 'The statement did not include a freshness time.', 'adz-world' )
			: sprintf(
				/* translators: %s: GMT timestamp from the statement */
				__( 'Statement time (GMT): %s', 'adz-world' ),
				$statement['fetched_gmt']
			);
		echo '<p>' . esc_html( $fresh ) . '</p>';
		echo '<p>' . esc_html__( 'Each currency is shown on its own. This screen does not convert currencies.', 'adz-world' ) . '</p>';

		foreach ( $statement['currencies'] as $currency => $row ) {
			echo '<h3>' . esc_html( (string) $currency ) . '</h3>';
			echo '<table class="widefat striped"><tbody>';
			foreach ( adzworld_statement_labels() as $bucket => $label ) {
				adzworld_earnings_row( $label, adzworld_format_minor( $row[ $bucket ], (string) $currency ) );
			}
			echo '</tbody></table>';
		}
	}

	echo '<h2>' . esc_html__( 'Rounding illustration', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'This is arithmetic, not a balance and not an amount payable.', 'adz-world' ) . '</p>';

	if ( is_array( $split ) ) {
		echo '<p>' . esc_html(
			sprintf(
				/* translators: 1: settled minor units, 2: platform minor units, 3: publisher minor units */
				__( '1000 minor units settled divides into %2$d platform minor units and %3$d publisher minor units. Those shares sum to %1$d.', 'adz-world' ),
				$split['gross'],
				$split['platform'],
				$split['publisher']
			)
		) . '</p>';
	}

	echo '<h2>' . esc_html__( 'Payout providers', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'Stripe is unconfigured. PayPal is unconfigured. This plugin does not hold either provider’s secret, does not create a sandbox charge, and does not start a payout. A provider account does not by itself collect affiliate-network commissions.', 'adz-world' ) . '</p>';
	echo '<p>' . esc_html(
		sprintf(
			/* translators: 1: Stripe state, 2: PayPal state */
			__( 'Stripe state: %1$s. PayPal state: %2$s.', 'adz-world' ),
			adzworld_provider_state( 'stripe' ),
			adzworld_provider_state( 'paypal' )
		)
	) . '</p>';

	if ( '' === $paypal ) {
		echo '<p>' . esc_html__( 'No PayPal email is saved in Adz Settings. Saving one records a destination the publisher typed. It does not pay that address.', 'adz-world' ) . '</p>';
	} else {
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %s: PayPal email from settings */
				__( 'PayPal email saved in settings: %s. This plugin does not pay that address.', 'adz-world' ),
				$paypal
			)
		) . '</p>';
	}

	if ( '' === $code ) {
		echo '<p>' . esc_html__( 'No URL-safe publisher referral code is stored. If a value is stored in another shape, this screen leaves it unchanged.', 'adz-world' ) . '</p>';
	} else {
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %s: publisher referral code */
				__( 'Publisher referral code left unchanged: %s.', 'adz-world' ),
				$code
			)
		) . '</p>';
	}

	echo '</div>';
}
