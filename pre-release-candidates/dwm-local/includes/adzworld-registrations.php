<?php
/**
 * Registration procedures for the accounts that pay this site.
 *
 * Each procedure names the provider’s own sign-up page and the identifier
 * that provider pays. The plugin does not create the account and does not
 * submit the form.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accounts with a registration procedure.
 *
 * @return array<string,array<string,mixed>>
 */
function adzworld_registration_catalog() {
	return array(
		'adsense'   => array(
			'label'        => 'Google AdSense',
			'group'        => 'display',
			'signup'       => 'https://adsense.google.com/start/',
			'signup_label' => __( 'Register at AdSense', 'adz-world' ),
			'pays'         => __( 'The publisher id pays this site.', 'adz-world' ),
			'steps'        => array(
				__( 'Use Register at AdSense and sign in with the Google account that should be paid.', 'adz-world' ),
				__( 'Enter this site’s URL. Google reviews the site.', 'adz-world' ),
				__( 'After approval, open Ads, then By ad unit, and create a display unit.', 'adz-world' ),
				__( 'On Connections, choose Google AdSense and use Add network if this section is not here yet. Paste the publisher id (ca-pub-…) and the ad unit id.', 'adz-world' ),
				__( 'Accept the host, enable the row, and save. Add the row to a rotation.', 'adz-world' ),
			),
		),
		'gam'       => array(
			'label'        => 'Google Ad Manager',
			'group'        => 'display',
			'signup'       => 'https://admanager.google.com/',
			'signup_label' => __( 'Register at Ad Manager', 'adz-world' ),
			'pays'         => __( 'The linked Google account pays this site.', 'adz-world' ),
			'steps'        => array(
				__( 'Use Register at Ad Manager. An AdSense account is required first. Google may ask to speak with a representative before the account is active.', 'adz-world' ),
				__( 'Create an ad unit and copy its path. The path starts with a slash and the network code.', 'adz-world' ),
				__( 'On Connections, choose Google Ad Manager and use Add network if this section is not here yet. Paste the path, width, and height.', 'adz-world' ),
				__( 'Accept the host, enable the row, and save. Add the row to a rotation.', 'adz-world' ),
			),
		),
		'walmart'   => array(
			'label'        => __( 'Walmart through Impact', 'adz-world' ),
			'group'        => 'affiliate',
			'signup'       => 'https://app.impact.com/login.user',
			'signup_label' => __( 'Register at Impact', 'adz-world' ),
			'pays'         => __( 'The three numbers on the Impact tracking link pay this site.', 'adz-world' ),
			'steps'        => array(
				__( 'Use Register at Impact. On the sign-in page, choose Partner, then continue with email, Apple, or Google.', 'adz-world' ),
				__( 'Verify the mobile number and finish the checklist: category, this site as a channel, and the public profile. Impact reviews the marketplace application.', 'adz-world' ),
				__( 'Apply to Walmart’s programme inside Impact.', 'adz-world' ),
				__( 'Create one tracking link. Copy the three numbers from /c/PARTNER/AD/PROGRAM.', 'adz-world' ),
				__( 'Paste the partner id, ad id, and program id in this section, enable the account, and save.', 'adz-world' ),
			),
		),
		'amazon'    => array(
			'label'        => 'Amazon Associates',
			'group'        => 'affiliate',
			'signup'       => 'https://associates.amazon.com/signup',
			'signup_label' => __( 'Register at Amazon Associates', 'adz-world' ),
			'pays'         => __( 'The tracking id pays this site on the marketplace that issued it.', 'adz-world' ),
			'steps'        => array(
				__( 'Use Register at Amazon Associates for the marketplace this site will link. A United States tracking id is issued from associates.amazon.com.', 'adz-world' ),
				__( 'Amazon emails the tracking id. It looks like sitename-20. One tracking id pays only on the marketplace that issued it.', 'adz-world' ),
				__( 'Select that marketplace in this section and paste the tracking id.', 'adz-world' ),
				__( 'Enable the account and save. Extra sub-tags are ids Amazon issues. A sub-tag stays separate from a specific visitor.', 'adz-world' ),
			),
		),
		'clickbank' => array(
			'label'        => 'ClickBank',
			'group'        => 'affiliate',
			'signup'       => 'https://accounts.clickbank.com/master/makebank.html',
			'signup_label' => __( 'Register at ClickBank', 'adz-world' ),
			'pays'         => __( 'The account nickname on the HopLink pays this site.', 'adz-world' ),
			'steps'        => array(
				__( 'Use Register at ClickBank and create the account.', 'adz-world' ),
				__( 'Copy the account nickname. Choose the vendor nickname of the one product this site promotes.', 'adz-world' ),
				__( 'The tracking id is optional. Leave it empty, or enter a token this site owns. Words a visitor types are not added to the HopLink.', 'adz-world' ),
				__( 'Paste the nickname and vendor in this section, enable the account, and save.', 'adz-world' ),
			),
		),
		'cj'        => array(
			'label'        => 'CJ Affiliate',
			'group'        => 'affiliate',
			'signup'       => 'https://signup.cj.com/member/signup/publisher/',
			'signup_label' => __( 'Register at CJ', 'adz-world' ),
			'pays'         => __( 'The publisher website id pays this site.', 'adz-world' ),
			'steps'        => array(
				__( 'Use Register at CJ. The form asks for a complete site URL, including https://.', 'adz-world' ),
				__( 'After approval, open a brand and copy a deep link. The path is click-PID-AID. PID is the publisher website id. AID is the link id.', 'adz-world' ),
				__( 'Choose the click domain shown on that link. Paste an advertiser search URL that contains {query} exactly once.', 'adz-world' ),
				__( 'Paste the ids in this section, enable the account, and save.', 'adz-world' ),
			),
		),
		'awin'      => array(
			'label'        => 'Awin',
			'group'        => 'affiliate',
			'signup'       => 'https://www.awin.com/us/publishers',
			'signup_label' => __( 'Register at Awin', 'adz-world' ),
			'pays'         => __( 'The publisher id pays this site.', 'adz-world' ),
			'steps'        => array(
				__( 'Use Register at Awin and complete the publisher sign-up. Add this site as the promotional space. The US application includes a $1 deposit, which Awin credits back on approval.', 'adz-world' ),
				__( 'After approval, copy the publisher id. Apply to an advertiser and copy that advertiser id.', 'adz-world' ),
				__( 'Paste an advertiser search URL that contains {query} exactly once.', 'adz-world' ),
				__( 'Paste the ids in this section, enable the account, and save. ShareASale publishers register here.', 'adz-world' ),
			),
		),
		'rakuten'   => array(
			'label'        => 'Rakuten Advertising',
			'group'        => 'affiliate',
			'signup'       => 'https://rakutenadvertising.com/',
			'signup_label' => __( 'Register at Rakuten', 'adz-world' ),
			'pays'         => __( 'The publisher site id pays this site.', 'adz-world' ),
			'steps'        => array(
				__( 'Use Register at Rakuten and choose Become a Publisher.', 'adz-world' ),
				__( 'Register with email, verify it, and accept the publisher membership agreement. Add this site.', 'adz-world' ),
				__( 'Apply to an advertiser. Copy the publisher site id and the advertiser id from a deep link. Rakuten is moving advertiser programmes onto Impact. A programme that has moved is applied for from the Impact account.', 'adz-world' ),
				__( 'Paste an advertiser search URL that contains {query} exactly once, then paste the ids, enable the account, and save.', 'adz-world' ),
			),
		),
		'ebay'      => array(
			'label'        => 'eBay Partner Network',
			'group'        => 'affiliate',
			'signup'       => 'https://partnernetwork.ebay.com/',
			'signup_label' => __( 'Register at eBay Partner Network', 'adz-world' ),
			'pays'         => __( 'The campaign id pays this site.', 'adz-world' ),
			'steps'        => array(
				__( 'Use Register at eBay Partner Network. Choose Sign Up, then sign in with an eBay account. Choose the Partner Network application.', 'adz-world' ),
				__( 'eBay emails an acceptance decision.', 'adz-world' ),
				__( 'Copy the campaign id. For ebay.com the rotation id is 711-53200-19255-0 and the site id is 0. Another marketplace uses the rotation id and site id from eBay’s link-parameter article.', 'adz-world' ),
				__( 'Paste the three ids in this section, enable the account, and save.', 'adz-world' ),
			),
		),
	);
}

/**
 * Whether this account has a registration procedure.
 *
 * @param string $id Account id.
 * @return bool
 */
function adzworld_registration_has( $id ) {
	return isset( adzworld_registration_catalog()[ $id ] );
}

/**
 * Saved display row for a registration account, preferring an enabled row.
 *
 * @param string $preset Catalogue id.
 * @return array<string,mixed>|null
 */
function adzworld_registration_display_row( $preset ) {
	if ( ! function_exists( 'adzworld_network_rows' ) ) {
		return null;
	}
	$found = null;
	foreach ( adzworld_network_rows() as $row ) {
		$stored = ( is_array( $row ) && isset( $row['preset'] ) ) ? (string) $row['preset'] : '';
		if ( ! is_array( $row ) || $stored !== $preset ) {
			continue;
		}
		if ( null === $found ) {
			$found = $row;
		}
		if ( isset( $row['enabled'] ) && '1' === (string) $row['enabled'] ) {
			return $row;
		}
	}
	return $found;
}

/**
 * Whether the paying identifier is saved and the account is enabled.
 *
 * @param string $id Account id.
 * @return bool
 */
function adzworld_registration_ready( $id ) {
	$catalog = adzworld_registration_catalog();
	if ( ! isset( $catalog[ $id ] ) ) {
		return false;
	}
	if ( 'display' === $catalog[ $id ]['group'] ) {
		if ( ! function_exists( 'adzworld_network_preset_row' ) ) {
			return false;
		}
		$row = adzworld_network_preset_row( $id );
		return is_array( $row ) && isset( $row['enabled'] ) && '1' === (string) $row['enabled'];
	}
	if ( ! function_exists( 'adzworld_affiliate_accounts' ) || ! function_exists( 'adzworld_affiliate_url' ) ) {
		return false;
	}
	$accounts = adzworld_affiliate_accounts();
	if ( ! isset( $accounts[ $id ] ) || '1' !== $accounts[ $id ]['enabled'] ) {
		return false;
	}
	return '' !== adzworld_affiliate_url( $id, $accounts[ $id ], 'registration' );
}

/**
 * Address of the fields for one account.
 *
 * @param string $id Account id.
 * @return string
 */
function adzworld_registration_paste_href( $id ) {
	$catalog = adzworld_registration_catalog();
	if ( isset( $catalog[ $id ] ) && 'display' === $catalog[ $id ]['group'] ) {
		$row  = adzworld_registration_display_row( $id );
		$hash = is_array( $row ) && isset( $row['id'] ) ? '#adzworld-network-row-' . rawurlencode( (string) $row['id'] ) : '#adzworld-add-preset';
		return adzworld_desk_url( 'affiliates' ) . $hash;
	}
	return adzworld_desk_url( 'affiliates' ) . '#adzworld-paid-affiliate-' . rawurlencode( sanitize_key( $id ) );
}

/**
 * Whether the numbered procedure is printed in the registration list.
 *
 * The same steps are printed beside the fields when that card is on screen.
 *
 * @param string $id Account id.
 * @return bool
 */
function adzworld_registration_steps_in_list( $id ) {
	$catalog = adzworld_registration_catalog();
	if ( ! isset( $catalog[ $id ] ) || 'affiliate' === $catalog[ $id ]['group'] ) {
		return false;
	}
	$tab = function_exists( 'adzworld_desk_tab' ) ? adzworld_desk_tab() : '';
	if ( 'networks' !== $tab ) {
		return true;
	}
	return ! is_array( adzworld_registration_display_row( $id ) );
}

/**
 * Print the numbered registration procedure.
 *
 * @param string $id Account id.
 * @return void
 */
function adzworld_registration_render_steps( $id ) {
	$catalog = adzworld_registration_catalog();
	if ( ! isset( $catalog[ $id ]['steps'] ) || ! is_array( $catalog[ $id ]['steps'] ) ) {
		return;
	}
	echo '<ol class="adzworld-connection-steps adzworld-pay-steps">';
	foreach ( $catalog[ $id ]['steps'] as $step ) {
		echo '<li>' . esc_html( $step ) . '</li>';
	}
	echo '</ol>';
}

/**
 * Print the Register control for one account.
 *
 * @param string $id      Account id.
 * @param bool   $primary Whether this is the next account to register.
 * @return void
 */
function adzworld_registration_render_signup( $id, $primary = false ) {
	$catalog = adzworld_registration_catalog();
	if ( ! isset( $catalog[ $id ] ) ) {
		return;
	}
	$class = $primary ? 'button button-primary' : 'button';
	echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $catalog[ $id ]['signup'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $catalog[ $id ]['signup_label'] ) . '</a>';
}

/**
 * Registration list for the accounts that pay this site.
 *
 * @return void
 */
function adzworld_registration_desk() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$catalog = adzworld_registration_catalog();
	$ready   = 0;
	foreach ( array_keys( $catalog ) as $id ) {
		if ( adzworld_registration_ready( $id ) ) {
			++$ready;
		}
	}
	$primary = false;
	echo '<section class="adzworld-pay" aria-labelledby="adzworld-pay-title">';
	echo '<h2 id="adzworld-pay-title">' . esc_html__( 'Get paid', 'adz-world' ) . '</h2>';
	echo '<p class="adzworld-pay-progress">' . esc_html(
		sprintf(
			/* translators: 1: accounts that can pay this site, 2: accounts in the registration list. */
			__( '%1$d of %2$d accounts can pay this site.', 'adz-world' ),
			$ready,
			count( $catalog )
		)
	) . '</p>';
	echo '<p>' . esc_html__( 'Register at the provider. Paste the identifier that provider assigns. Enable the account. The provider pays that identifier.', 'adz-world' ) . '</p>';
	echo '<div class="adzworld-pay-list">';
	foreach ( $catalog as $id => $item ) {
		$is_ready = adzworld_registration_ready( $id );
		$is_next  = ! $is_ready && ! $primary;
		if ( $is_next ) {
			$primary = true;
		}
		echo '<article class="adzworld-pay-account' . ( $is_ready ? ' is-ready' : '' ) . '" id="adzworld-pay-' . esc_attr( $id ) . '">';
		echo '<div class="adzworld-pay-head">';
		echo '<h3>' . esc_html( $item['label'] ) . '</h3>';
		echo '<span class="adzworld-connection-status' . ( $is_ready ? ' is-enabled' : '' ) . '">' . esc_html( $is_ready ? __( 'Paying this site', 'adz-world' ) : __( 'Registration open', 'adz-world' ) ) . '</span>';
		echo '<p class="adzworld-pay-pays">' . esc_html( $item['pays'] ) . '</p>';
		echo '</div>';
		echo '<p class="adzworld-pay-actions">';
		adzworld_registration_render_signup( $id, $is_next );
		$has_fields = is_array( adzworld_registration_display_row( $id ) ) || 'affiliate' === $item['group'];
		echo '<a class="button" href="' . esc_url( adzworld_registration_paste_href( $id ) ) . '">' . esc_html( $has_fields ? __( 'Paste the id', 'adz-world' ) : __( 'Add the network', 'adz-world' ) ) . '</a>';
		echo '</p>';
		echo '<details' . ( $is_next || adzworld_registration_steps_in_list( $id ) ? ' open' : '' ) . '><summary>' . esc_html__( 'Registration procedure', 'adz-world' ) . '</summary>';
		adzworld_registration_render_steps( $id );
		echo '</details>';
		echo '</article>';
	}
	echo '</div></section>';
}
