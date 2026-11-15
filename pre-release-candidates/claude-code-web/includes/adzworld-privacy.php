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
			__( 'When this plugin starts recording anything', 'adz-world' ),
			__( 'Only once a visitor types a category or a refinement, rates an advertisement, or makes a profile. A visitor who is simply shown advertising and does not use these controls is not recorded at all: no session is opened for them, no cookie is set, and no log entry is written. Using a control is how a visitor chooses to be remembered.', 'adz-world' ),
		),
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
			__( 'Advertising logs are kept on the site until an administrator clears or exports them. The browser session identifier is gone as soon as the browser closes, so a log from one visit cannot be joined to a log from the next. A visitor who never uses a control leaves nothing to keep.', 'adz-world' ),
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

/**
 * The standing explanation of what this plugin does and does not know.
 *
 * Opened from a small (i) in the corner of an adz panel. It is a details
 * element rather than a hover tooltip: hover alone cannot be reached by a
 * keyboard and does not exist on a phone, and this is the one thing on the
 * page a reader is most entitled to read.
 *
 * Three stages, in the order a reader actually meets them.
 *
 * @since 1.0.0
 *
 * @return string Markup for the control and its explanation.
 */
function adzworld_privacy_note() {
	$stages = array(
		array(
			__( 'Before you say anything', 'adz-world' ),
			__( 'We do not know who you are and we collect nothing. No session is started, no cookie is set, and nothing about you is written down. You are shown adz, and that is all that happens.', 'adz-world' ),
		),
		array(
			__( 'What we cannot speak for', 'adz-world' ),
			__( 'A slot on this page may be filled by an advertising network that watches people across the web, such as Google AdSense, Google Ad Manager, Taboola, Outbrain, Criteo or Amazon Advertising. Those networks answer to the site owner and to their own terms, not to us, and we cannot stop what they do. We can tell you they are there.', 'adz-world' ),
		),
		array(
			__( 'When you tell us what you want', 'adz-world' ),
			__( 'Typing what you are looking for, or rating adz, starts a session. The session is a timestamped identifier and the words you chose: use these to pick adz. It is not your name, your email or your address, it is not joined to anything else, and it ends when you close your browser.', 'adz-world' ),
		),
		array(
			__( 'If you make a profile', 'adz-world' ),
			__( 'Your advertising preferences are kept with your account on this site, so this plugin can act for you the way a lawyer acts for a client: speaking on your behalf to advertisers, so that what reaches you is what you said you were interested in buying. You can see it, change it, and delete it.', 'adz-world' ),
		),
	);

	$html = '<details class="adzworld-note">'
		. '<summary class="adzworld-note__open" aria-label="'
		. esc_attr__( 'What this plugin knows about you', 'adz-world' ) . '">i</summary>'
		. '<div class="adzworld-note__body">';

	foreach ( $stages as $stage ) {
		$html .= '<p class="adzworld-note__stage"><span class="adzworld-note__heading">'
			. esc_html( $stage[0] ) . '</span> ' . esc_html( $stage[1] ) . '</p>';
	}

	$html .= '<p class="adzworld-note__close">'
		. esc_html__( 'Adz are shown because you asked for them. That is the whole idea.', 'adz-world' )
		. '</p></div></details>';

	return $html;
}

/**
 * Print the standing explanation.
 *
 * A printing wrapper so no caller has to echo unescaped markup. Every part
 * of it is escaped where it is assembled.
 *
 * @since 1.0.0
 *
 * @return void
 */
function adzworld_privacy_note_echo() {
	echo wp_kses(
		adzworld_privacy_note(),
		array(
			'details' => array( 'class' => true ),
			'summary' => array(
				'class'      => true,
				'aria-label' => true,
			),
			'div'     => array( 'class' => true ),
			'p'       => array( 'class' => true ),
			'span'    => array( 'class' => true ),
		)
	);
}
