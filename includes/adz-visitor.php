<?php
/**
 * Visitor identity and per-visitor state.
 *
 * Replaces two broken mechanisms from 1.0.8:
 *
 *  1. $_SERVER['REMOTE_ADDR'] as the identity behind the content gate. Every
 *     visitor behind one NAT or carrier gateway shared a single identity, so
 *     one person's ad view unlocked content for everybody sharing that IP.
 *
 *  2. $_SESSION['first_time'] as the "don't show another ad yet" throttle.
 *     session_start() was never called anywhere in the plugin, so on stock
 *     WordPress that key never existed and the throttle never throttled.
 *
 * Identity is now: the user ID when logged in, otherwise a random token in a
 * first-party cookie. State is stored server-side in a transient keyed by that
 * identity, so it works without PHP sessions and survives page loads.
 *
 * @package adz.world
 */

defined( 'ABSPATH' ) || exit;

/** Cookie holding the anonymous visitor token. */
const ADZ_VISITOR_COOKIE = 'adz_visitor';

/** How long an anonymous visitor token stays valid. */
const ADZ_VISITOR_TTL = DAY_IN_SECONDS * 30;

/**
 * Stable identity for the current visitor.
 *
 * Logged-in users are identified by user ID. Anonymous visitors get a random
 * token stored in a cookie; the token is generated once and reused. When
 * cookies are unavailable (first request, or a client that refuses them) we
 * fall back to a salted hash of the IP so the gate still functions, rather
 * than failing open.
 *
 * @return string Identity string, max 64 chars, safe for a DB column.
 */
function adz_visitor_id() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	if ( is_user_logged_in() ) {
		$cached = 'u' . get_current_user_id();
		return $cached;
	}

	if ( ! empty( $_COOKIE[ ADZ_VISITOR_COOKIE ] ) ) {
		$token = sanitize_key( wp_unslash( $_COOKIE[ ADZ_VISITOR_COOKIE ] ) );
		if ( 32 === strlen( $token ) ) {
			$cached = 'v' . $token;
			return $cached;
		}
	}

	$token = wp_generate_password( 32, false, false );

	if ( ! headers_sent() ) {
		setcookie(
			ADZ_VISITOR_COOKIE,
			$token,
			array(
				'expires'  => time() + ADZ_VISITOR_TTL,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		$_COOKIE[ ADZ_VISITOR_COOKIE ] = $token;
		$cached                        = 'v' . $token;
		return $cached;
	}

	// Cookie could not be set. Fall back to a salted IP hash so the gate holds.
	$cached = 'i' . substr( wp_hash( adz_visitor_ip() ), 0, 32 );
	return $cached;
}

/**
 * The visitor's IP address.
 *
 * Kept only for logging and for the fallback identity. Proxy headers are
 * deliberately NOT trusted: they are attacker-controlled, and treating them as
 * identity would let anyone reset their own gate at will.
 *
 * @return string
 */
function adz_visitor_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
	$ip = filter_var( $ip, FILTER_VALIDATE_IP );

	return $ip ? $ip : '0.0.0.0';
}

/**
 * Read a per-visitor state value.
 *
 * @param string $key           State key.
 * @param mixed  $default_value Returned when the key is not set.
 * @return mixed
 */
function adz_visitor_get( $key, $default_value = false ) {
	$state = get_transient( adz_visitor_state_key() );

	if ( ! is_array( $state ) ) {
		return $default_value;
	}

	return array_key_exists( $key, $state ) ? $state[ $key ] : $default_value;
}

/**
 * Write a per-visitor state value.
 *
 * @param string $key   State key.
 * @param mixed  $value Value to store.
 * @return void
 */
function adz_visitor_set( $key, $value ) {
	$state = get_transient( adz_visitor_state_key() );

	if ( ! is_array( $state ) ) {
		$state = array();
	}

	$state[ $key ] = $value;

	set_transient( adz_visitor_state_key(), $state, ADZ_VISITOR_TTL );
}

/**
 * Transient key holding this visitor's state.
 *
 * @return string
 */
function adz_visitor_state_key() {
	return 'adz_vs_' . md5( adz_visitor_id() );
}

/**
 * Whether the visitor may browse without being shown another ad yet.
 *
 * This is the throttle $_SESSION['first_time'] was meant to be.
 *
 * @return bool True while the visitor is inside their paid-for browsing window.
 */
function adz_visitor_in_grace_period() {
	$until = (int) adz_visitor_get( 'grace_until', 0 );

	return $until > time();
}

/**
 * Start a browsing window during which no further ad is shown.
 *
 * @param int $seconds Length of the window.
 * @return void
 */
function adz_visitor_start_grace_period( $seconds ) {
	$seconds = max( 0, (int) $seconds );

	adz_visitor_set( 'grace_until', time() + $seconds );
}
