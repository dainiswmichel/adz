<?php
/**
 * Read-only publisher statements. Settlement authority remains on adz-world.com.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supported accounting currencies; provider support is checked separately.
 *
 * @return array<string,int> Currency minor-unit exponents.
 */
function adzworld_currency_exponents() {
	return array(
		'AUD' => 2,
		'BHD' => 3,
		'CAD' => 2,
		'CHF' => 2,
		'CLP' => 0,
		'EUR' => 2,
		'GBP' => 2,
		'JPY' => 0,
		'KRW' => 0,
		'KWD' => 3,
		'NZD' => 2,
		'OMR' => 3,
		'TND' => 3,
		'USD' => 2,
	);
}

/**
 * Split settled affiliate commission using half-up rounding, without floats.
 *
 * @param int $settled_minor Net settled affiliate commission in minor units.
 * @return array|WP_Error Exact platform and publisher shares, or an error.
 */
function adzworld_split_commission( $settled_minor ) {
	if ( ! is_int( $settled_minor ) || $settled_minor < 0 ) {
		return new WP_Error( 'adzworld_amount', __( 'A non-negative integer commission is required.', 'adz-world' ) );
	}
	$service_minor = intdiv( $settled_minor, 10 ) + ( $settled_minor % 10 >= 5 ? 1 : 0 );
	return array(
		'service_minor'   => $service_minor,
		'publisher_minor' => $settled_minor - $service_minor,
	);
}

/**
 * Format minor units without floating-point conversion.
 *
 * @param int    $minor    Amount, including a possible negative adjustment.
 * @param string $currency Accounting currency.
 * @return string|WP_Error Decimal amount prefixed with its currency.
 */
function adzworld_format_money( $minor, $currency ) {
	$currencies = adzworld_currency_exponents();
	if ( ! is_int( $minor ) || ! is_string( $currency ) || ! isset( $currencies[ $currency ] ) ) {
		return new WP_Error( 'adzworld_currency', __( 'Unsupported amount or currency.', 'adz-world' ) );
	}
	$digits   = $currencies[ $currency ];
	$negative = $minor < 0 ? '-' : '';
	$value    = ltrim( (string) $minor, '-' );
	if ( $digits ) {
		$value = str_pad( $value, $digits + 1, '0', STR_PAD_LEFT );
		$value = substr( $value, 0, -$digits ) . '.' . substr( $value, -$digits );
	}
	return $currency . ' ' . $negative . $value;
}

/**
 * Validate a complete authenticated statement before replacing its cached copy.
 *
 * @param mixed  $statement Decoded service response.
 * @param string $publisher Expected publisher identifier.
 * @return array|WP_Error Validated statement or an explicit contract error.
 */
function adzworld_validate_statement( $statement, $publisher ) {
	$error = new WP_Error( 'adzworld_statement', __( 'The earnings service returned an invalid statement.', 'adz-world' ) );
	if ( ! is_array( $statement ) || ! isset( $statement['publisher_id'], $statement['generated_at'], $statement['currencies'] )
		|| $statement['publisher_id'] !== $publisher || ! is_int( $statement['generated_at'] )
		|| $statement['generated_at'] > time() + 60 || $statement['generated_at'] < time() - DAY_IN_SECONDS
		|| ! is_array( $statement['currencies'] ) || count( $statement['currencies'] ) > count( adzworld_currency_exponents() ) ) {
		return $error;
	}
	$currencies = adzworld_currency_exponents();
	$fields     = array( 'settled_minor', 'reversed_minor', 'estimated_minor', 'service_minor', 'publisher_minor', 'available_minor', 'reserved_minor', 'paid_minor' );
	$seen       = array();
	$validated  = array();
	foreach ( $statement['currencies'] as $row ) {
		if ( ! is_array( $row ) || ! isset( $row['currency'] ) || ! is_string( $row['currency'] )
			|| ! isset( $currencies[ $row['currency'] ] ) || isset( $seen[ $row['currency'] ] ) ) {
			return $error;
		}
		$clean = array( 'currency' => $row['currency'] );
		foreach ( $fields as $field ) {
			if ( ! isset( $row[ $field ] ) || ! is_int( $row[ $field ] )
				|| $row[ $field ] > intdiv( PHP_INT_MAX, 16 ) || $row[ $field ] < -intdiv( PHP_INT_MAX, 16 )
				|| ( 'available_minor' !== $field && $row[ $field ] < 0 ) ) {
				return $error;
			}
			$clean[ $field ] = $row[ $field ];
		}
		if ( $row['service_minor'] + $row['publisher_minor'] !== $row['settled_minor']
			|| $row['available_minor'] + $row['reserved_minor'] + $row['paid_minor'] !== $row['publisher_minor'] ) {
			return $error;
		}
		$seen[ $row['currency'] ] = true;
		$validated[]              = $clean;
	}
	return array(
		'publisher_id' => $publisher,
		'generated_at' => $statement['generated_at'],
		'currencies'   => $validated,
	);
}

/**
 * Fetch a statement only after explicit connection consent and configuration.
 *
 * Tokens are restricted read-only credentials, never payment-provider secrets.
 *
 * @return array|WP_Error Current statement or service/configuration error.
 */
function adzworld_fetch_statement() {
	$options = get_option( 'adzworld_earnings_connection', array() );
	if ( empty( $options['consent'] ) || empty( $options['publisher_id'] ) || empty( $options['token'] ) ) {
		return new WP_Error( 'adzworld_not_connected', __( 'Earnings are not connected. No balance or payout is available from this installation.', 'adz-world' ) );
	}
	$response = wp_safe_remote_get(
		'https://adz-world.com/wp-json/adz-world/v1/earnings',
		array(
			'timeout'             => 15,
			'redirection'         => 0,
			'limit_response_size' => 65536,
			'headers'             => array(
				'Authorization' => 'Bearer ' . $options['token'],
				'Accept'        => 'application/json',
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'adzworld_service', __( 'The earnings service is unavailable or rejected this connection. No payout has been initiated.', 'adz-world' ) );
	}
	return adzworld_validate_statement( json_decode( wp_remote_retrieve_body( $response ), true ), $options['publisher_id'] );
}

/**
 * Add the administrator-only earnings page.
 *
 * @return void
 */
function adzworld_earnings_menu() {
	add_submenu_page( 'edit.php?post_type=adz_ad', __( 'Earnings', 'adz-world' ), __( 'Earnings', 'adz-world' ), 'manage_options', 'adzworld-earnings', 'adzworld_earnings_page' );
}
add_action( 'admin_menu', 'adzworld_earnings_menu' );

/**
 * Render connection controls and authenticated currency-separated statements.
 *
 * @return void
 */
function adzworld_earnings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator access is required.', 'adz-world' ) );
	}
	$options   = get_option( 'adzworld_earnings_connection', array() );
	$notice    = '';
	$statement = null;
	if ( isset( $_POST['adzworld_earnings_action'] ) ) {
		check_admin_referer( 'adzworld_earnings' );
		$action = sanitize_key( wp_unslash( $_POST['adzworld_earnings_action'] ) );
		if ( 'disconnect' === $action ) {
			delete_option( 'adzworld_earnings_connection' );
			$options = array();
			$notice  = __( 'Earnings connection removed. This does not cancel outstanding service obligations.', 'adz-world' );
		} elseif ( 'connect' === $action ) {
			$publisher = isset( $_POST['publisher_id'] ) && is_string( $_POST['publisher_id'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_id'] ) ) : '';
			$token     = isset( $_POST['statement_token'] ) && is_string( $_POST['statement_token'] ) ? sanitize_text_field( wp_unslash( $_POST['statement_token'] ) ) : '';
			if ( empty( $_POST['earnings_consent'] ) || ! preg_match( '/^[A-Za-z0-9_-]{1,100}$/D', $publisher )
				|| ! preg_match( '/^[A-Za-z0-9_-]{32,256}$/D', $token ) ) {
				$notice = __( 'Explicit consent, a publisher ID, and a valid read-only statement token are required.', 'adz-world' );
			} else {
				$options = array(
					'consent'      => time(),
					'publisher_id' => $publisher,
					'token'        => $token,
				);
				update_option( 'adzworld_earnings_connection', $options, false );
				$notice = __( 'Connection saved. Statements can be requested below; no payment was initiated.', 'adz-world' );
			}
		} elseif ( 'refresh' === $action ) {
			$statement = adzworld_fetch_statement();
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'adz-world earnings', 'adz-world' ); ?></h1>
		<p><?php esc_html_e( 'The plugin is free. For the optional adz-world monetization service, adz-world retains 10% of settled affiliate commissions and the publisher receives 90%. This is not a percentage of product sales. Statements are authoritative only at adz-world.com.', 'adz-world' ); ?></p>
		<p><?php esc_html_e( 'Service ads use adz-world affiliate/referral codes with publisher consent. Existing publisher links are not rewritten. Stripe and PayPal selection is TBD; this candidate does not initiate payments.', 'adz-world' ); ?></p>
		<?php if ( $notice ) : ?>
			<div class="notice notice-info"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>
		<?php if ( is_wp_error( $statement ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $statement->get_error_message() ); ?></p></div>
		<?php endif; ?>
		<?php if ( empty( $options['consent'] ) ) : ?>
			<p><?php esc_html_e( 'Not connected. The settlement service and account provisioning must be operational before this connection can return statements. No earnings are assumed to be zero.', 'adz-world' ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'adzworld_earnings' ); ?>
				<p><label><?php esc_html_e( 'Publisher ID', 'adz-world' ); ?> <input name="publisher_id" required maxlength="100"></label></p>
				<p><label><?php esc_html_e( 'Read-only statement token', 'adz-world' ); ?> <input type="password" name="statement_token" required autocomplete="off"></label></p>
				<p><label><input type="checkbox" name="earnings_consent" value="1" required> <?php esc_html_e( 'I authorize requests to adz-world.com to retrieve my publisher earnings statement. Requests send the read-only token and reveal this server’s IP address. This does not enable visitor tracking or authorize payouts.', 'adz-world' ); ?></label></p>
				<button class="button" name="adzworld_earnings_action" value="connect"><?php esc_html_e( 'Save connection', 'adz-world' ); ?></button>
			</form>
		<?php else : ?>
			<form method="post">
				<?php wp_nonce_field( 'adzworld_earnings' ); ?>
				<button class="button button-primary" name="adzworld_earnings_action" value="refresh"><?php esc_html_e( 'Request current statement', 'adz-world' ); ?></button>
				<button class="button" name="adzworld_earnings_action" value="disconnect"><?php esc_html_e( 'Disconnect', 'adz-world' ); ?></button>
			</form>
		<?php endif; ?>
		<?php if ( is_array( $statement ) ) : ?>
			<p><?php echo esc_html( gmdate( 'Y-m-d H:i:s \U\T\C', $statement['generated_at'] ) ); ?></p>
			<?php
			$labels = array(
				'estimated_minor' => __( 'Estimated / not settled', 'adz-world' ),
				'settled_minor'   => __( 'Net settled commission', 'adz-world' ),
				'reversed_minor'  => __( 'Reversals already deducted', 'adz-world' ),
				'service_minor'   => __( 'adz-world share', 'adz-world' ),
				'publisher_minor' => __( 'Publisher share', 'adz-world' ),
				'available_minor' => __( 'Available / negative adjustment', 'adz-world' ),
				'reserved_minor'  => __( 'Reserved / in flight', 'adz-world' ),
				'paid_minor'      => __( 'Paid', 'adz-world' ),
			);
			?>
			<?php foreach ( $statement['currencies'] as $row ) : ?>
				<h2><?php echo esc_html( $row['currency'] ); ?></h2>
				<dl>
					<?php foreach ( $labels as $field => $label ) : ?>
						<dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( adzworld_format_money( $row[ $field ], $row['currency'] ) ); ?></dd>
					<?php endforeach; ?>
				</dl>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
}
