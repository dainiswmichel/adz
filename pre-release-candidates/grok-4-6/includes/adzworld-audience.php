<?php
/**
 * Visitor and guest ad preferences.
 *
 * A visitor choice lives in the adzworld_prefs session cookie. Persistence is
 * not guaranteed. A guest is a logged-in WordPress user; their choice is user
 * meta on this site. Member capabilities are not implemented.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cookie name for a visitor's category ids.
 *
 * @return string
 */
function adzworld_pref_cookie() {
	return 'adzworld_prefs';
}

/**
 * Term ids from a list, limited to published ad categories on this site.
 *
 * @param mixed $raw Ids.
 * @return int[]
 */
function adzworld_sanitize_pref_terms( $raw ) {
	$ids = array();

	if ( is_string( $raw ) ) {
		$raw = explode( ',', $raw );
	}

	if ( ! is_array( $raw ) ) {
		return array();
	}

	foreach ( $raw as $id ) {
		$term_id = absint( $id );

		if ( $term_id && term_exists( $term_id, 'ad_taxonomy' ) ) {
			$ids[] = $term_id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Visitor preference term ids from the cookie.
 *
 * @return int[]
 */
function adzworld_visitor_pref_terms() {
	if ( ! isset( $_COOKIE[ adzworld_pref_cookie() ] ) ) {
		return array();
	}

	$raw = sanitize_text_field( wp_unslash( $_COOKIE[ adzworld_pref_cookie() ] ) );

	return adzworld_sanitize_pref_terms( $raw );
}

/**
 * Preference term ids for the current person.
 *
 * Logged-in guests use user meta. Everyone else uses the session cookie.
 *
 * @return int[]
 */
function adzworld_current_pref_terms() {
	if ( is_user_logged_in() ) {
		$stored = get_user_meta( get_current_user_id(), 'adzworld_pref_terms', true );

		return adzworld_sanitize_pref_terms( $stored );
	}

	return adzworld_visitor_pref_terms();
}

/**
 * Store a visitor cookie for this browser session.
 *
 * A zero expiry asks the browser to drop the cookie when the session ends.
 * Persistence is still not guaranteed.
 *
 * @param int[] $term_ids Term ids.
 * @return void
 */
function adzworld_set_visitor_cookie( $term_ids ) {
	$value = implode( ',', array_map( 'strval', $term_ids ) );

	setcookie(
		adzworld_pref_cookie(),
		$value,
		array(
			'expires'  => 0,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);

	$_COOKIE[ adzworld_pref_cookie() ] = $value;
}

/**
 * Save a posted preference form.
 *
 * @return void
 */
function adzworld_save_preferences() {
	check_admin_referer( 'adzworld_preferences' );

	$posted = isset( $_POST['adzworld_terms'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['adzworld_terms'] ) ) : array();
	$ids    = adzworld_sanitize_pref_terms( $posted );

	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), 'adzworld_pref_terms', $ids );
	} else {
		adzworld_set_visitor_cookie( $ids );
	}

	$back = wp_get_referer();
	wp_safe_redirect( $back ? $back : home_url( '/' ) );
	exit;
}
add_action( 'admin_post_adzworld_save_preferences', 'adzworld_save_preferences' );
add_action( 'admin_post_nopriv_adzworld_save_preferences', 'adzworld_save_preferences' );

/**
 * Preference form. Visitors get a session cookie. Guests get user meta.
 *
 * @return string
 */
function adzworld_preferences_shortcode() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'ad_taxonomy',
			'hide_empty' => false,
		)
	);
	$chosen = adzworld_current_pref_terms();

	ob_start();
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'adzworld_preferences' );
	echo '<input type="hidden" name="action" value="adzworld_save_preferences" />';

	if ( is_user_logged_in() ) {
		echo '<p>' . esc_html__( 'You are logged in. These ad categories are saved on your account on this site.', 'adz-world' ) . '</p>';
	} else {
		echo '<p>' . esc_html__( 'You are a visitor. These ad categories are saved for this browser session and are not guaranteed to hold. Logging in lets you keep them on your account on this site.', 'adz-world' ) . '</p>';
	}

	if ( is_array( $terms ) && $terms ) {
		echo '<ul>';
		foreach ( $terms as $term ) {
			echo '<li><label><input type="checkbox" name="adzworld_terms[]" value="' . esc_attr( (string) $term->term_id ) . '" ' . checked( in_array( (int) $term->term_id, $chosen, true ), true, false ) . ' /> ' . esc_html( $term->name ) . '</label></li>';
		}
		echo '</ul>';
	} else {
		echo '<p>' . esc_html__( 'This site has no ad categories yet.', 'adz-world' ) . '</p>';
	}

	echo '<button type="submit">' . esc_html__( 'Save ad preferences', 'adz-world' ) . '</button>';
	echo '</form>';

	return (string) ob_get_clean();
}
add_shortcode( 'adzworld_preferences', 'adzworld_preferences_shortcode' );

/**
 * Tags allowed in publisher ad markup rendered by a template.
 *
 * Post content tags, plus an iframe. Script tags are not included.
 *
 * @return array<string,array<string,bool>>
 */
function adzworld_ad_allowed_html() {
	$allowed            = wp_kses_allowed_html( 'post' );
	$allowed['iframe'] = array(
		'src'             => true,
		'width'           => true,
		'height'          => true,
		'title'           => true,
		'frameborder'     => true,
		'allow'           => true,
		'allowfullscreen' => true,
		'loading'         => true,
		'referrerpolicy'  => true,
	);

	return $allowed;
}

/**
 * Template settings stored under one option, with a read of the legacy per-file option.
 *
 * @param string $file Template filename.
 * @return array<string,string>
 */
function adzworld_template_settings( $file ) {
	$file = adzworld_template_filename( $file );
	$out  = array(
		'header_text'  => '',
		'header_style' => 'center',
		'footer_text'  => '',
		'footer_style' => 'center',
	);

	if ( '' === $file ) {
		return $out;
	}

	$all = get_option( 'adzworld_template_settings', array() );

	if ( is_array( $all ) && isset( $all[ $file ] ) && is_array( $all[ $file ] ) ) {
		return array_merge( $out, adzworld_sanitize_template_row( $all[ $file ] ) );
	}

	$legacy = get_option( 'template_' . $file );

	if ( is_string( $legacy ) ) {
		$legacy = unserialize( $legacy, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- legacy row, classes refused.
	}

	if ( is_array( $legacy ) ) {
		return array_merge( $out, adzworld_sanitize_template_row( $legacy ) );
	}

	return $out;
}

/**
 * A template filename that exists in this plugin's adz-templates directory.
 *
 * @param mixed $file Candidate filename.
 * @return string
 */
function adzworld_template_filename( $file ) {
	$file = is_scalar( $file ) ? basename( (string) $file ) : '';

	if ( ! preg_match( '/^[a-z0-9_-]+\.php$/', $file ) ) {
		return '';
	}

	$path = dirname( __DIR__ ) . '/adz-templates/' . $file;

	return is_file( $path ) ? $file : '';
}

/**
 * Keep template text and alignment inside the values the form offers.
 *
 * @param array<string,mixed> $row Row.
 * @return array<string,string>
 */
function adzworld_sanitize_template_row( $row ) {
	$align = array( 'left', 'right', 'center' );

	return array(
		'header_text'  => isset( $row['header_text'] ) ? sanitize_text_field( (string) $row['header_text'] ) : '',
		'footer_text'  => isset( $row['footer_text'] ) ? sanitize_text_field( (string) $row['footer_text'] ) : '',
		'header_style' => ( isset( $row['header_style'] ) && in_array( $row['header_style'], $align, true ) ) ? $row['header_style'] : 'center',
		'footer_style' => ( isset( $row['footer_style'] ) && in_array( $row['footer_style'], $align, true ) ) ? $row['footer_style'] : 'center',
	);
}

/**
 * Save template header and footer under the single template option.
 *
 * @param string              $file Template filename.
 * @param array<string,mixed> $row  Posted row.
 * @return void
 */
function adzworld_save_template_settings( $file, $row ) {
	$file = adzworld_template_filename( $file );

	if ( '' === $file ) {
		return;
	}

	$all = get_option( 'adzworld_template_settings', array() );

	if ( ! is_array( $all ) ) {
		$all = array();
	}

	$all[ $file ] = adzworld_sanitize_template_row( $row );
	update_option( 'adzworld_template_settings', $all, false );
}
