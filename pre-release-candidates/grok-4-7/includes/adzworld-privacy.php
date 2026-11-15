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

	$sections = adzworld_privacy_terms();

	$content = '';

	foreach ( $sections as $section ) {
		$content .= '<h3>' . esc_html( $section['heading'] ) . '</h3>';
		foreach ( $section['paragraphs'] as $paragraph ) {
			$content .= '<p class="privacy-policy-tutorial">' . esc_html( $paragraph ) . '</p>';
		}
	}

	wp_add_privacy_policy_content( __( 'adz.world', 'adz-world' ), wp_kses_post( $content ) );
}

add_action( 'admin_init', 'adzworld_privacy_policy_content' );

/**
 * The five sections of the terms, in the order a reader meets them.
 *
 * @return array<int,array{heading:string,paragraphs:array<int,string>}>
 */
function adzworld_privacy_terms() {
	return array(
		array(
			'heading'    => __( 'Before you say anything', 'adz-world' ),
			'paragraphs' => array(
				__( 'We do not know who you are and we collect nothing. No session is started, no cookie is set, and nothing about you is written down. You are shown adz specified by adzadmins, and that is all that happens.', 'adz-world' ),
				__( 'Most people using modern technology have, often unknowingly, agreed to terms that permit extensive surveillance and tracking by Big Tech. Governments also engage in digital surveillance, sometimes without people\'s knowledge or consent.', 'adz-world' ),
				__( 'Every visit to this site costs its owner money, whether the visitor is a human being, a bot, an artificial intelligence system, or a web crawler. Advertising helps pay for the content and services that people enjoy without charge. Site owners also need to protect themselves against malicious or abusive consumption of their resources. But neither the cost of providing a website nor the need to protect it justifies surveilling the people who visit.', 'adz-world' ),
			),
		),
		array(
			'heading'    => __( 'What we cannot speak for', 'adz-world' ),
			'paragraphs' => array(
				__( 'Much of today\'s digital advertising operates through surveillance-based capitalism. Surveillance Tracking Advertising, Logging & Knowledge Systems (S.T.A.L.K.S.) follow people across websites and applications, collecting information about their activities. Surveillance Capitalism Advertising Manipulation Systems (S.C.A.M.S.) turn that information into behavioral profiles and predictions designed to influence purchasing decisions. Slimy Loser Advertising Placement Systems (S.L.A.P.S.) then use those predictions to determine which advertisements people see.', 'adz-world' ),
				__( 'Rather than simply asking people what they want, these systems watch what people do, track and log their behavior, guess what they might want, and attempt to manipulate their decisions.', 'adz-world' ),
				__( 'A slot on this page may be filled by a third-party advertising network, such as Google AdSense, Google Ad Manager, Taboola, Outbrain, Criteo or Amazon Advertising. These networks may employ Surveillance Advertising Display Systems (S.A.D.S.). They operate under their own terms and arrangements with the site owner, not ours. We cannot control their data collection, but we can tell you when their advertising is present.', 'adz-world' ),
			),
		),
		array(
			'heading'    => __( 'When you tell us what you want', 'adz-world' ),
			'paragraphs' => array(
				__( 'Permission-based advertising works differently. Rather than watching your behavior and guessing what you might want, we simply let you tell us.', 'adz-world' ),
				__( 'Typing what you are looking for, or rating adz, starts a session. The session consists of a timestamped identifier and the words or preferences you chose. These are used to select relevant adz, not to construct a behavioral profile. The session does not require your name, email or address, is not joined to other personal information, and ends when you close your browser.', 'adz-world' ),
			),
		),
		array(
			'heading'    => __( 'If you make a profile', 'adz-world' ),
			'paragraphs' => array(
				__( 'Your advertising preferences are kept with your account on this site. Rather than allowing advertisers to build a profile about you through surveillance, you create and control your own profile.', 'adz-world' ),
				__( 'This plugin can then act for you the way a lawyer acts for a client: representing your expressed interests to advertisers, so that what reaches you reflects what you actually want to buy, rather than what someone has guessed you might buy.', 'adz-world' ),
				__( 'Your preferences belong under your control. You can see them, change them, and delete them.', 'adz-world' ),
			),
		),
		array(
			'heading'    => __( 'Summary', 'adz-world' ),
			'paragraphs' => array(
				__( 'With his software, Dainis W. Michel is changing the digital landscape from one of surveillance-based advertising to enjoyable permission-based advertising. Adz are shown because you asked for them. "Old style" surveillance-based advertising brings you annoyance and inconvenience. Newer-style permission-based adz bring you joy and convenience: that is the whole idea.', 'adz-world' ),
			),
		),
	);
}

/**
 * The standing explanation of what this plugin does and does not know.
 *
 * The (i) is the bottom-right corner of the hub slide.
 *
 * @since 1.0.0
 *
 * @return string Markup for the explanation.
 */
function adzworld_privacy_note() {
	$html = '<details class="adzworld-note">'
		. '<summary class="adzworld-note__open" aria-label="'
		. esc_attr__( 'What this plugin knows about you', 'adz-world' ) . '">i</summary>'
		. '<div class="adzworld-note__body">';

	$terms = adzworld_privacy_terms();
	$last  = count( $terms ) - 1;
	foreach ( $terms as $index => $section ) {
		$class = 'adzworld-note__stage';
		if ( $last === $index ) {
			$class .= ' adzworld-note__close';
		}
		$html .= '<div class="' . esc_attr( $class ) . '"><p class="adzworld-note__heading">'
			. esc_html( $section['heading'] ) . '</p>';
		foreach ( $section['paragraphs'] as $paragraph ) {
			$html .= '<p>' . esc_html( $paragraph ) . '</p>';
		}
		$html .= '</div>';
	}

	$html .= '</div></details>';

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
			'span'    => array(
				'class'       => true,
				'aria-hidden' => true,
			),
		)
	);
}
