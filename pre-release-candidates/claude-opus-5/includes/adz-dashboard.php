<?php
/**
 * Earnings dashboard.
 *
 * Shows what adz-world.com has reported, and nothing else. When the service is
 * not configured -- which is its state today -- the screen says so plainly and
 * shows no figures at all. A zero presented as a balance to a publisher who may
 * be owed money is the worst failure this screen could have, so it is not a
 * state this screen can reach.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

add_action( 'admin_menu', 'adz_dashboard_menu', 12 );
/**
 * Add the Earnings screen under the adz post type.
 *
 * @return void
 */
function adz_dashboard_menu() {
	add_submenu_page(
		'edit.php?post_type=adz_ad',
		__( 'Earnings', 'adz-world' ),
		__( 'Earnings', 'adz-world' ),
		'manage_options',
		'adz-earnings',
		'adz_dashboard_render'
	);
}

/**
 * Render the Earnings screen.
 *
 * @return void
 */
function adz_dashboard_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view earnings.', 'adz-world' ), 403 );
	}

	adz_dashboard_handle_post();

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'adz-world earnings', 'adz-world' ) . '</h1>';

	adz_dashboard_render_consent();

	$unavailable = adz_service_unavailable_reason();

	if ( $unavailable ) {
		adz_dashboard_render_unavailable( $unavailable );
		echo '</div>';

		return;
	}

	adz_dashboard_render_balances();
	echo '</div>';
}

/**
 * Handle the consent and payout forms.
 *
 * @return void
 */
function adz_dashboard_handle_post() {
	if ( empty( $_POST['adz_dashboard_action'] ) ) {
		return;
	}

	check_admin_referer( 'adz_dashboard' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'adz-world' ), 403 );
	}

	$action = sanitize_key( wp_unslash( $_POST['adz_dashboard_action'] ) );
	$result = null;

	if ( 'opt_in' === $action ) {
		$result = adz_monetization_opt_in();
	} elseif ( 'opt_out' === $action ) {
		$result = adz_monetization_opt_out();
	} elseif ( 'sync' === $action ) {
		$result = adz_service_sync_statement();
	} elseif ( 'payout' === $action ) {
		$result = adz_payout_request(
			isset( $_POST['adz_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['adz_currency'] ) ) : '',
			isset( $_POST['adz_amount_minor'] ) ? (int) $_POST['adz_amount_minor'] : 0,
			isset( $_POST['adz_method'] ) ? sanitize_key( wp_unslash( $_POST['adz_method'] ) ) : ''
		);
	}

	if ( is_wp_error( $result ) ) {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( $result->get_error_message() )
		);
	}
}

/**
 * The consent panel, stating the arrangement in full before it is accepted.
 *
 * @return void
 */
function adz_dashboard_render_consent() {
	$consent = adz_monetization_consent_record();
	$on      = adz_monetization_enabled();

	echo '<div class="card" style="max-width:48em;padding:1em 1.5em;">';
	echo '<h2>' . esc_html__( 'Monetization', 'adz-world' ) . '</h2>';

	echo '<p>' . esc_html__(
		'adz served through the adz-world monetization service carry adz-world\'s affiliate codes. adz-world keeps 10% of commission it actually receives and settles 90% to you. The 10% is taken from commission that has been received, not from sales, clicks, impressions or estimates.',
		'adz-world'
	) . '</p>';

	echo '<p>' . esc_html__(
		'Links on your adz that already carry your own affiliate identifier are never rewritten.',
		'adz-world'
	) . '</p>';

	echo '<form method="post">';
	wp_nonce_field( 'adz_dashboard' );

	if ( $on ) {
		printf(
			'<p><strong>%s</strong> %s</p>',
			esc_html__( 'Monetization is on.', 'adz-world' ),
			esc_html(
				sprintf(
					/* translators: 1: user login, 2: date. */
					__( 'Agreed by %1$s on %2$s.', 'adz-world' ),
					adz_dashboard_user_name( $consent['granted_by'] ),
					$consent['granted_at'] ? gmdate( 'j F Y', strtotime( $consent['granted_at'] ) ) : '—'
				)
			)
		);
		echo '<input type="hidden" name="adz_dashboard_action" value="opt_out">';
		submit_button( __( 'Switch monetization off', 'adz-world' ), 'secondary', 'submit', false );
	} else {
		echo '<p>' . esc_html__( 'Monetization is off. No adz on this site are monetized and nothing is sent to adz-world.', 'adz-world' ) . '</p>';
		echo '<input type="hidden" name="adz_dashboard_action" value="opt_in">';
		submit_button( __( 'Switch monetization on', 'adz-world' ), 'primary', 'submit', false );
	}

	echo '</form></div>';
}

/**
 * Say why there are no figures, instead of showing figures there is no basis for.
 *
 * @param WP_Error $reason Why the service is unavailable.
 * @return void
 */
function adz_dashboard_render_unavailable( $reason ) {
	echo '<div class="notice notice-warning inline" style="margin-top:1.5em;padding:1em;">';
	echo '<h2 style="margin-top:0;">' . esc_html__( 'No earnings to report', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html( $reason->get_error_message() ) . '</p>';

	if ( 'adz_unconfigured' === $reason->get_error_code() ) {
		echo '<p>' . esc_html__(
			'The adz-world settlement service has not been built yet. When it exists, this screen will show commission adz-world has actually received, the 10% adz-world share, your 90% share, any reversals, and what is available to withdraw. Until then it shows nothing, because there is nothing it could honestly show.',
			'adz-world'
		) . '</p>';
	}

	echo '</div>';
}

/**
 * The balance tables, one per currency.
 *
 * @return void
 */
function adz_dashboard_render_balances() {
	$balances = adz_ledger_balances();
	$synced   = adz_ledger_last_synced();

	printf(
		'<p>%s</p>',
		$synced
			? esc_html(
				sprintf(
					/* translators: %s: human time difference. */
					__( 'Statement last updated %s ago.', 'adz-world' ),
					human_time_diff( $synced )
				)
			)
			: esc_html__( 'This statement has never been updated from adz-world.', 'adz-world' )
	);

	if ( empty( $balances ) ) {
		echo '<p>' . esc_html__( 'adz-world has reported no commission for this site yet.', 'adz-world' ) . '</p>';

		return;
	}

	foreach ( $balances as $currency => $b ) {
		echo '<h2>' . esc_html( $currency ) . '</h2>';

		if ( ! $b['reconciles'] ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__(
				'These figures do not reconcile: the two shares do not sum to the settled total. This is a fault, and the figures below should not be relied on until adz-world has corrected the statement.',
				'adz-world'
			) . '</p></div>';
		}

		$rows = array(
			array( __( 'Commission adz-world has received', 'adz-world' ), $b['settled_minor'], 'strong' ),
			array( __( 'adz-world share (10%)', 'adz-world' ), $b['fee_minor'], '' ),
			array( __( 'Your share (90%)', 'adz-world' ), $b['publisher_minor'], 'strong' ),
			array( __( 'Reversals and refunds', 'adz-world' ), $b['reversed_minor'], '' ),
			array( __( 'Adjustments', 'adz-world' ), $b['adjusted_minor'], '' ),
			array( __( 'Pending — not yet confirmed, not yours yet', 'adz-world' ), $b['pending_minor'], 'muted' ),
			array( __( 'Reserved against payouts in progress', 'adz-world' ), $b['reserved_minor'], '' ),
			array( __( 'Already paid to you', 'adz-world' ), $b['paid_minor'], '' ),
			array( __( 'Available to withdraw', 'adz-world' ), $b['available_minor'], 'strong' ),
		);

		echo '<table class="widefat striped" style="max-width:40em;"><tbody>';

		foreach ( $rows as $row ) {
			printf(
				'<tr><td%s>%s</td><td style="text-align:right;font-variant-numeric:tabular-nums;">%s</td></tr>',
				'muted' === $row[2] ? ' style="opacity:.7;"' : '',
				'strong' === $row[2] ? '<strong>' . esc_html( $row[0] ) . '</strong>' : esc_html( $row[0] ),
				esc_html( adz_format_money( $row[1], $currency ) )
			);
		}

		echo '</tbody></table>';
	}
}

/**
 * A user's login for display, without assuming the user still exists.
 *
 * @param int $user_id User id.
 * @return string
 */
function adz_dashboard_user_name( $user_id ) {
	$user = get_userdata( (int) $user_id );

	return $user ? $user->user_login : __( 'an administrator', 'adz-world' );
}
