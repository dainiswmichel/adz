<?php
/**
 * What this plugin stores, and what it sends where.
 *
 * WordPress asks every plugin that collects, uses or stores user data, or
 * passes it to a third party, to put suggested text in the site's privacy
 * policy postbox so the administrator can pull it into their own policy.
 * This file is that text and nothing else: it stores nothing and sends
 * nothing.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Suggest privacy policy text describing what adz.world holds.
 *
 * Hooked on admin_init rather than called directly, as the handbook asks,
 * because calling it outside an action hook causes problems.
 *
 * @since 1.0.0
 *
 * @return void
 */
function adzworld_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$sections = array(
		array(
			__( 'What this plugin stores about a visitor', 'adz-world' ),
			__( 'A visitor who is not signed in is known only by a random identifier held in their browser session. It expires when they close the browser, it is not derived from anything about them, and it is never joined to a name, an email address or an account. Signed-in readers have the same information stored against their user account instead, where they can see it.', 'adz-world' ),
		),
		array(
			__( 'What is recorded', 'adz-world' ),
			__( 'The categories and refinements a reader types in, the ratings they give an advertisement, and which advertisement was shown in which rotation slot. The wording of a refinement is stored exactly as it was typed and is not interpreted, guessed at, or expanded.', 'adz-world' ),
		),
		array(
			__( 'What is sent to an advertising provider', 'adz-world' ),
			__( 'When a rotation slot is served by an advertising provider, the category and refinement wording are sent to that provider as a product search, in the same way a search box would send them. No identifier for the reader is sent with it: the provider is not told who asked. Which providers a site uses is chosen by its administrator, and each provider has its own terms and privacy policy.', 'adz-world' ),
		),
		array(
			__( 'How long it is kept', 'adz-world' ),
			__( 'Advertising logs are kept on the site until an administrator clears or exports them. The browser session identifier is gone as soon as the browser closes, so a log from one visit cannot be joined to a log from the next.', 'adz-world' ),
		),
		array(
			__( 'Who can read it', 'adz-world' ),
			__( 'Administrators of this site, through the AdzLogs screen. It is not sent anywhere else by this plugin.', 'adz-world' ),
		),
	);

	$content = '';

	foreach ( $sections as $section ) {
		$content .= '<h3>' . esc_html( $section[0] ) . '</h3>'
			. '<p class="privacy-policy-tutorial">' . esc_html( $section[1] ) . '</p>';
	}

	wp_add_privacy_policy_content( __( 'adz.world', 'adz-world' ), wp_kses_post( $content ) );
}

add_action( 'admin_init', 'adzworld_privacy_policy_content' );
