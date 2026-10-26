<?php
/**
 * Opt-in, the 10% arithmetic, and the platform query argument.
 *
 * The plugin does not rewrite ad markup and does not replace a publisher's
 * referral code. adz-world.com is the book of record. This file only decides
 * whether an already-built network request may leave the site, and how a
 * settled minor-unit amount splits.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * House code sent as platform_referral_code after explicit opt-in.
 *
 * @return string
 */
function adzworld_platform_referral_code() {
	return 'adzworld';
}

/**
 * Share of a settled commission retained by adz-world. The publisher share is the rest.
 *
 * @return float
 */
function adzworld_platform_rate() {
	return 0.10;
}

/**
 * Whether an administrator has accepted the monetization terms.
 *
 * Installation and activation do not set this option.
 *
 * @return bool
 */
function adzworld_terms_accepted() {
	return '1' === get_option( 'adzworld_terms_accepted' );
}

/**
 * Publisher referral code as stored, when it is a URL-safe token.
 *
 * This reads the existing setting. It does not write it.
 *
 * @return string
 */
function adzworld_publisher_referral_code() {
	$options = get_option( 'adz_ad_options' );
	$code    = '';

	if ( is_array( $options ) && isset( $options['referral_code'] ) && is_scalar( $options['referral_code'] ) ) {
		$code = (string) $options['referral_code'];
	}

	return preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $code ) ? $code : '';
}

/**
 * Split a settled amount in integer minor units.
 *
 * Platform minor units are 10% of the absolute value, rounded half-up by
 * integer division: intdiv( abs + 5, 10 ). Publisher minor units are the
 * remainder. The two shares equal the settled amount, including when the
 * amount is negative. That equality is arithmetic. It does not make a share
 * payable.
 *
 * @param int $gross_minor Settled commission in minor units of one currency.
 * @return array{gross:int,platform:int,publisher:int}|null Null when the value cannot be split.
 */
function adzworld_split_cents( $gross_minor ) {
	if ( is_string( $gross_minor ) && preg_match( '/^-?[0-9]+$/', $gross_minor ) ) {
		$gross_minor = (int) $gross_minor;
	}

	if ( ! is_int( $gross_minor ) || $gross_minor <= -PHP_INT_MAX ) {
		return null;
	}

	$negative = $gross_minor < 0;
	$absolute = $negative ? -$gross_minor : $gross_minor;
	$platform = intdiv( $absolute + 5, 10 );

	if ( $platform > $absolute ) {
		$platform = $absolute;
	}

	$publisher = $absolute - $platform;

	if ( $negative ) {
		$platform  = -$platform;
		$publisher = -$publisher;
	}

	return array(
		'gross'     => $gross_minor,
		'platform'  => $platform,
		'publisher' => $publisher,
	);
}

/**
 * Visitor address, or an empty string when it is not an IP address.
 *
 * @return string
 */
function adzworld_visitor_ip() {
	$raw = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return false === filter_var( $raw, FILTER_VALIDATE_IP ) ? '' : $raw;
}

/**
 * Add the house code to a request that already targets the monetization service.
 *
 * Does not add or replace referral_code. Does not attach preference categories.
 * Returns the original URL when the terms have not been accepted.
 *
 * @param mixed $url Request URL.
 * @return string
 */
function adzworld_with_platform_arg( $url ) {
	if ( ! is_string( $url ) || '' === $url ) {
		return is_string( $url ) ? $url : '';
	}

	if ( ! adzworld_terms_accepted() ) {
		return $url;
	}

	return add_query_arg( 'platform_referral_code', adzworld_platform_referral_code(), $url );
}

/**
 * Terms shown in wp-admin. Draft language, not a published legal page.
 *
 * @return string HTML built from escaped strings.
 */
function adzworld_terms_html() {
	$rate = (int) ( adzworld_platform_rate() * 100 );

	$html  = '<h2>' . esc_html__( 'adz-world terms', 'adz-world' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'The adz-world plugin is free. Installing it is not consent to monetization. These paragraphs are a draft for review. They are not legal advice and they are not an approved policy.', 'adz-world' ) . '</p>';
	$html .= '<p>' . sprintf(
		/* translators: 1: platform percentage, 2: publisher percentage */
		esc_html__( 'When a publisher opts in, adz served through the adz-world monetization service use adz-world affiliate or referral codes. The commission basis is a settled affiliate commission actually received by adz-world. adz-world retains %1$d%% and the publisher share is %2$d%%. The percentage is not applied to product sales, advertiser spend, clicks, impressions, or estimated earnings.', 'adz-world' ),
		$rate,
		100 - $rate
	) . '</p>';
	$html .= '<p>' . esc_html__( 'This plugin does not rewrite ad links and does not replace a referral code already saved in Adz Settings. It is not the authority for revenue. adz-world.com would issue statements. No statement service is connected from this screen.', 'adz-world' ) . '</p>';
	$html .= '<p>' . esc_html__( 'The operator name on this draft is adz-world, USA. The legal entity, business address, and applicable state are not resolved. Balances in different currencies are kept separate. This plugin does not apply an exchange rate.', 'adz-world' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Until these terms are accepted, this site does not contact adz-world.com. Activation does not contact any server. Accepting the terms does not by itself turn on the existing network switch.', 'adz-world' ) . '</p>';

	return $html;
}

/**
 * Privacy text shown beside the terms. Draft language.
 *
 * @return string HTML built from escaped strings.
 */
function adzworld_privacy_html() {
	$html  = '<h2>' . esc_html__( 'adz-world privacy', 'adz-world' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'With the network switch off, ad views stay in this site’s database so the same ad is not repeated on the same page the same day. That record can include the visitor IP. It is not sent to adz-world.com.', 'adz-world' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Access is the general event, whether the party is human, AI, or still unknown. A visit is human access. An access with no profile keeps ad categories in the adzworld_prefs cookie for the browser session, and that session is not guaranteed to hold. A guest is a human visit with a profile. On this site the profile is the WordPress account, and the category ids are stored in that account’s user meta. A member is a guest who has made an adz-world purchase. This plugin does not record purchases, does not decide that an access is human, and does not grant a capability for a purchase.', 'adz-world' ) . '</p>';
	$html .= '<p>' . esc_html__( 'With the network switch on, and only after these terms are accepted, a request for an ad can send the publisher id, access tokens, the publisher referral code, the house code as platform_referral_code, the site URL, the site title, the administrator email, the adz-world email, the PayPal email entered in settings, the Amazon affiliate id, preference categories configured in settings, and the visitor IP. Publishing an ad can send its title, content, and categories. The publisher referral code already on that request is left as stored.', 'adz-world' ) . '</p>';
	$html .= '<p>' . esc_html__( 'This plugin does not receive settled commissions and does not store a payment-provider secret.', 'adz-world' ) . '</p>';

	return $html;
}

/**
 * Record acceptance. Does not create a payment secret and does not call out.
 *
 * @return void
 */
function adzworld_accept_terms() {
	check_admin_referer( 'adzworld_accept_terms' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	update_option( 'adzworld_terms_accepted', '1', false );

	wp_safe_redirect( admin_url( 'edit.php?post_type=adz_ad&page=adzworld-earnings' ) );
	exit;
}
add_action( 'admin_post_adzworld_accept_terms', 'adzworld_accept_terms' );

/**
 * Explain a network switch that cannot call out because the terms are not accepted.
 *
 * @return void
 */
function adzworld_terms_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) || adzworld_terms_accepted() ) {
		return;
	}

	$options = get_option( 'adz_ad_options' );

	if ( empty( $options['connect_to_network'] ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'The adz-world network switch is on, and this site is not calling adz-world.com, because the monetization terms have not been accepted. The Earnings screen is where those terms are accepted.', 'adz-world' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'adzworld_terms_admin_notice' );
