<?php
/**
 * Monetization consent and affiliate attribution.
 *
 * Adz served through the adz-world monetization service carry adz-world's
 * affiliate codes. adz-world keeps 10% of commission it actually receives and
 * settles 90% to the publisher.
 *
 * That arrangement is off until a publisher switches it on. Installing a plugin
 * is not consent to have someone else's affiliate identifier placed on your
 * site, and the directory guidelines say so: a plugin may not embed external
 * links or credits on the public site without explicitly asking the user's
 * permission.
 *
 * Two rules follow, and both are enforced here rather than documented and hoped for:
 *
 *   1. Nothing is monetized until an administrator has opted in, and the
 *      opt-in records who agreed, when, and to which version of the terms.
 *   2. A link that already carries the publisher's own affiliate identifier is
 *      never rewritten. The publisher's existing arrangements are theirs.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/** Bumped when the terms change materially enough to need re-consent. */
const ADZ_TERMS_VERSION = '2026-10-25';

/**
 * Whether this site has opted in to monetization.
 *
 * Consent is tied to a terms version. If the terms change, consent lapses and
 * is asked for again rather than inherited.
 *
 * @return bool
 */
function adz_monetization_enabled() {
	$consent = get_option( 'adz_monetization_consent' );

	if ( empty( $consent['granted'] ) ) {
		return false;
	}

	return isset( $consent['terms_version'] ) && ADZ_TERMS_VERSION === $consent['terms_version'];
}

/**
 * Record an administrator's opt-in.
 *
 * @return true|WP_Error
 */
function adz_monetization_opt_in() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'adz_forbidden', __( 'You do not have permission to change monetization.', 'adz-world' ), array( 'status' => 403 ) );
	}

	update_option(
		'adz_monetization_consent',
		array(
			'granted'       => true,
			'terms_version' => ADZ_TERMS_VERSION,
			'granted_by'    => get_current_user_id(),
			'granted_at'    => gmdate( 'c' ),
			'site_url'      => home_url(),
		),
		false
	);

	return true;
}

/**
 * Withdraw consent.
 *
 * Monetization stops immediately. Earnings already settled remain owed; this
 * does not forfeit a balance.
 *
 * @return true|WP_Error
 */
function adz_monetization_opt_out() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'adz_forbidden', __( 'You do not have permission to change monetization.', 'adz-world' ), array( 'status' => 403 ) );
	}

	$consent = get_option( 'adz_monetization_consent', array() );

	$consent['granted']    = false;
	$consent['revoked_by'] = get_current_user_id();
	$consent['revoked_at'] = gmdate( 'c' );

	update_option( 'adz_monetization_consent', $consent, false );

	return true;
}

/**
 * The consent record, for display.
 *
 * @return array
 */
function adz_monetization_consent_record() {
	$consent = get_option( 'adz_monetization_consent', array() );

	return wp_parse_args(
		$consent,
		array(
			'granted'       => false,
			'terms_version' => '',
			'granted_by'    => 0,
			'granted_at'    => '',
		)
	);
}

/**
 * Affiliate parameter names, by network, used to detect an existing identifier.
 *
 * @return array<string,string[]>
 */
function adz_affiliate_parameters() {
	return array(
		'amazon'     => array( 'tag', 'ascsubtag' ),
		'awin'       => array( 'awinaffid' ),
		'cj'         => array( 'PID', 'pid', 'AID' ),
		'impact'     => array( 'irclickid', 'irgwc' ),
		'shareasale' => array( 'u', 'afftrack' ),
		'rakuten'    => array( 'ranMID', 'ranEAID' ),
		'generic'    => array( 'aff', 'affid', 'affiliate', 'ref', 'referral', 'partner', 'tag' ),
	);
}

/**
 * Whether a URL already carries somebody's affiliate identifier.
 *
 * Used to leave a publisher's own links alone. When in doubt this returns true,
 * because wrongly leaving a link untouched costs adz-world a commission, while
 * wrongly rewriting one takes money from the publisher.
 *
 * @param string $url Candidate URL.
 * @return bool
 */
function adz_url_has_affiliate_id( $url ) {
	$query = wp_parse_url( (string) $url, PHP_URL_QUERY );

	if ( empty( $query ) ) {
		return false;
	}

	$params = array();
	wp_parse_str( $query, $params );

	$keys = array_map( 'strtolower', array_keys( $params ) );

	foreach ( adz_affiliate_parameters() as $network_params ) {
		foreach ( $network_params as $param ) {
			if ( in_array( strtolower( $param ), $keys, true ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Apply adz-world attribution to an adz link, when that is permitted.
 *
 * Returns the URL unchanged, and says why, in every case where it must not be
 * rewritten. The reason is returned rather than swallowed so the dashboard can
 * tell a publisher exactly what is and is not being monetized.
 *
 * @param string $url       The destination URL on the adz.
 * @param array  $context   Optional. 'post_id' for sub-identifier attribution.
 * @return array{url:string,monetized:bool,reason:string}
 */
function adz_apply_attribution( $url, $context = array() ) {
	$url = (string) $url;

	if ( ! adz_monetization_enabled() ) {
		return array(
			'url'       => $url,
			'monetized' => false,
			'reason'    => 'not_opted_in',
		);
	}

	if ( ! adz_service_is_configured() ) {
		return array(
			'url'       => $url,
			'monetized' => false,
			'reason'    => 'service_unconfigured',
		);
	}

	if ( adz_url_has_affiliate_id( $url ) ) {
		// The publisher's own arrangement. Not ours to replace.
		return array(
			'url'       => $url,
			'monetized' => false,
			'reason'    => 'publisher_affiliate_present',
		);
	}

	$creds     = get_option( 'adz_service_credentials', array() );
	$publisher = isset( $creds['publisher_id'] ) ? (string) $creds['publisher_id'] : '';

	if ( '' === $publisher ) {
		return array(
			'url'       => $url,
			'monetized' => false,
			'reason'    => 'no_publisher_id',
		);
	}

	/*
	 * Attribution is a redirect through adz-world so that the affiliate
	 * relationship, the merchant programme and the commission record all sit
	 * with the service that is accountable for settling it. The destination is
	 * carried as a parameter; nothing about the visitor is attached.
	 */
	$target = add_query_arg(
		array(
			'p' => rawurlencode( $publisher ),
			'u' => rawurlencode( $url ),
			's' => isset( $context['post_id'] ) ? (int) $context['post_id'] : 0,
		),
		'https://adz-world.com/go/'
	);

	return array(
		'url'       => esc_url_raw( $target ),
		'monetized' => true,
		'reason'    => 'attributed',
	);
}
