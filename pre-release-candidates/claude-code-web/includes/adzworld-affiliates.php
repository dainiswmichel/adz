<?php
/**
 * Publisher affiliate accounts and the request string sent to each network.
 *
 * The request string is the category text plus the refinement text attached
 * to that category. A link is emitted only after the administrator has saved
 * the identifiers from that network's own account. The builders use the
 * tracking URL each network publishes. No identifier is invented.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Networks a publisher can connect, with the account page and the saved fields.
 *
 * @return array<string,array<string,mixed>>
 */
function adzworld_affiliate_networks() {
	return array(
		'amazon'    => array(
			'label'   => 'Amazon Associates',
			'account' => 'https://associates.amazon.com/signup',
			'note'    => __( 'The tracking id is the tag on an Associates link, for example sitename-20. The search uses Amazon’s k parameter. One tag works only on the marketplace where it was issued.', 'adz-world' ),
			'fields'  => array(
				'tag'  => array(
					'label'   => __( 'Tracking ID', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9][A-Za-z0-9_-]{1,40}$/D',
				),
				'host' => array(
					'label'   => __( 'Marketplace', 'adz-world' ),
					'choices' => array(
						'www.amazon.com',
						'www.amazon.ca',
						'www.amazon.com.mx',
						'www.amazon.com.br',
						'www.amazon.co.uk',
						'www.amazon.de',
						'www.amazon.fr',
						'www.amazon.it',
						'www.amazon.es',
						'www.amazon.nl',
						'www.amazon.se',
						'www.amazon.pl',
						'www.amazon.com.be',
						'www.amazon.ie',
						'www.amazon.co.jp',
						'www.amazon.com.au',
						'www.amazon.in',
						'www.amazon.sg',
						'www.amazon.ae',
						'www.amazon.sa',
						'www.amazon.com.tr',
						'www.amazon.eg',
					),
				),
			),
		),
		'walmart'   => array(
			'label'   => 'Walmart Affiliates',
			'account' => 'https://app.impact.com/login.user',
			'note'    => __( 'Walmart’s June 2026 agreement uses Impact. Create one tracking link in Impact and copy the three numbers from /c/PARTNER/AD/PROGRAM. The destination is the Walmart search for this request.', 'adz-world' ),
			'fields'  => array(
				'partner' => array(
					'label'   => __( 'Impact partner id', 'adz-world' ),
					'pattern' => '/^[0-9]{3,12}$/D',
				),
				'ad'      => array(
					'label'   => __( 'Impact ad id', 'adz-world' ),
					'pattern' => '/^[0-9]{3,12}$/D',
				),
				'program' => array(
					'label'   => __( 'Impact program id', 'adz-world' ),
					'pattern' => '/^[0-9]{3,12}$/D',
				),
			),
		),
		'awin'      => array(
			'label'   => 'Awin',
			'account' => 'https://www.awin.com/us/publishers',
			'note'    => __( 'ShareASale closed on 6 October 2025 and its publishers moved to Awin. Existing ShareASale links were kept. A new link is Awin’s cread.php deep link. The destination must be that advertiser’s own search URL and must contain {query}.', 'adz-world' ),
			'fields'  => array(
				'publisher'   => array(
					'label'   => __( 'Publisher id', 'adz-world' ),
					'pattern' => '/^[0-9]{3,10}$/D',
				),
				'advertiser'  => array(
					'label'   => __( 'Advertiser id', 'adz-world' ),
					'pattern' => '/^[0-9]{3,10}$/D',
				),
				'destination' => array(
					'label'   => __( 'Advertiser search URL', 'adz-world' ),
					'pattern' => 'url',
				),
			),
		),
		'cj'        => array(
			'label'   => 'CJ Affiliate',
			'account' => 'https://signup.cj.com/member/signup/publisher/',
			'note'    => __( 'Use the publisher website id and link id from a CJ deep link, which has the form click-PID-AID. The destination must be that advertiser’s own search URL and must contain {query}.', 'adz-world' ),
			'fields'  => array(
				'pid'         => array(
					'label'   => __( 'Publisher website id', 'adz-world' ),
					'pattern' => '/^[0-9]{4,10}$/D',
				),
				'aid'         => array(
					'label'   => __( 'Link id', 'adz-world' ),
					'pattern' => '/^[0-9]{4,12}$/D',
				),
				'domain'      => array(
					'label'   => __( 'Click domain', 'adz-world' ),
					'choices' => array(
						'www.anrdoezrs.net',
						'www.dpbolvw.net',
						'www.jdoqocy.com',
						'www.kqzyfj.com',
						'www.tkqlhce.com',
					),
				),
				'destination' => array(
					'label'   => __( 'Advertiser search URL', 'adz-world' ),
					'pattern' => 'url',
				),
			),
		),
		'rakuten'   => array(
			'label'   => 'Rakuten Advertising',
			'account' => 'https://rakutenadvertising.com/',
			'note'    => __( 'The deep link is click.linksynergy.com. id is the publisher site id and mid is the advertiser. The destination must contain {query}.', 'adz-world' ),
			'fields'  => array(
				'id'          => array(
					'label'   => __( 'Publisher site id', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9]{1,20}$/D',
				),
				'mid'         => array(
					'label'   => __( 'Advertiser id', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9]{1,20}$/D',
				),
				'destination' => array(
					'label'   => __( 'Advertiser search URL', 'adz-world' ),
					'pattern' => 'url',
				),
			),
		),
		'clickbank' => array(
			'label'   => 'ClickBank',
			'account' => 'https://accounts.clickbank.com/master/makebank.html',
			'note'    => __( 'ClickBank is a marketplace of single products rather than a searchable catalogue, so a HopLink points at one vendor\'s page and is not built from what a visitor asked for. Enter your account nickname and the vendor nickname of the product you promote. The tracking id is optional and is yours: it is sent to ClickBank exactly as typed, and nothing a visitor entered is ever added to it.', 'adz-world' ),
			'fields'  => array(
				'nickname' => array(
					'label'   => __( 'Your ClickBank nickname', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9]{2,25}$/D',
				),
				'vendor'   => array(
					'label'   => __( 'Vendor nickname', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9]{2,25}$/D',
				),
				'tid'      => array(
					'label'   => __( 'Tracking id, optional', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9_-]{0,24}$/D',
				),
			),
		),
		'ebay'      => array(
			'label'   => 'eBay Partner Network',
			'account' => 'https://partnernetwork.ebay.com/',
			'note'    => __( 'campid is the EPN campaign id. The US rotation id 711-53200-19255-0 and site id 0 are the values in eBay’s link-parameter article for ebay.com. For another marketplace, paste the rotation id and site id from that article. toolid 10001 is eBay’s custom-link tool.', 'adz-world' ),
			'fields'  => array(
				'campid' => array(
					'label'   => __( 'Campaign id', 'adz-world' ),
					'pattern' => '/^[0-9]{6,12}$/D',
				),
				'mkrid'  => array(
					'label'   => __( 'Rotation id', 'adz-world' ),
					'pattern' => '/^[0-9]{3}-[0-9]{5}-[0-9]{5}-[0-9]$/D',
				),
				'siteid' => array(
					'label'   => __( 'Site id', 'adz-world' ),
					'pattern' => '/^[0-9]{1,4}$/D',
				),
			),
		),
	);
}

/**
 * Saved affiliate accounts, limited to known networks and valid field values.
 *
 * @return array<string,array<string,string>>
 */
function adzworld_affiliate_accounts() {
	return adzworld_sanitize_affiliate_accounts( get_option( 'adzworld_affiliate_accounts', array() ) );
}

/**
 * Limit affiliate account data to known networks and valid field values.
 *
 * @param mixed $saved Stored option or submitted rows.
 * @return array<string,array<string,string>>
 */
function adzworld_sanitize_affiliate_accounts( $saved ) {
	$saved    = is_array( $saved ) ? $saved : array();
	$accounts = array();

	foreach ( adzworld_affiliate_networks() as $id => $network ) {
		$row   = isset( $saved[ $id ] ) && is_array( $saved[ $id ] ) ? $saved[ $id ] : array();
		$clean = array(
			'enabled' => isset( $row['enabled'] ) && '1' === (string) $row['enabled'] ? '1' : '0',
		);
		foreach ( $network['fields'] as $field => $rule ) {
			$value = isset( $row[ $field ] ) ? (string) $row[ $field ] : '';
			if ( isset( $rule['choices'] ) ) {
				$clean[ $field ] = in_array( $value, $rule['choices'], true ) ? $value : '';
			} elseif ( 'url' === $rule['pattern'] ) {
				$clean[ $field ] = adzworld_affiliate_destination_template( $value );
			} else {
				$clean[ $field ] = 1 === preg_match( $rule['pattern'], $value ) ? $value : '';
			}
		}
		$accounts[ $id ] = $clean;
	}

	return $accounts;
}

/**
 * Accept an advertiser search URL only when it is HTTPS and contains {query}.
 *
 * @param string $value Submitted template.
 * @return string Sanitized template, or an empty string.
 */
function adzworld_affiliate_destination_template( $value ) {
	$value = trim( (string) $value );
	if ( false === strpos( $value, '{query}' ) || substr_count( $value, '{' ) !== 1 ) {
		return '';
	}
	$probe = str_replace( '{query}', 'query', $value );
	return wp_http_validate_url( $probe ) ? $value : '';
}

/**
 * Category text plus the refinement text attached to that category.
 *
 * @param array  $pref     Preference hierarchy.
 * @param string $category Category.
 * @return string Request sent to a network.
 */
function adzworld_affiliate_request_string( $pref, $category ) {
	$pref     = wp_parse_args( (array) $pref, adzworld_empty_preference() );
	$category = trim( (string) $category );
	if ( '' === $category ) {
		return '';
	}
	$parts = array( $category );
	foreach ( $pref['category_refinements'] as $stored ) {
		$split = adzworld_pair_split( (string) $stored );
		if ( 2 === count( $split ) && $category === $split[0] && '' !== trim( $split[1] ) ) {
			$parts[] = trim( $split[1] );
		}
	}
	$request = trim( implode( ' ', $parts ) );
	if ( strlen( $request ) <= 240 ) {
		return $request;
	}
	$cut = strrpos( substr( $request, 0, 240 ), ' ' );
	return false === $cut ? substr( $request, 0, 240 ) : substr( $request, 0, $cut );
}

/**
 * Substitute and validate an advertiser search template.
 *
 * @param string $template Template containing {query}.
 * @param string $request  Request string.
 * @return string Destination URL, or an empty string.
 */
function adzworld_affiliate_destination( $template, $request ) {
	$template = adzworld_affiliate_destination_template( $template );
	if ( '' === $template || '' === $request ) {
		return '';
	}
	$url = str_replace( '{query}', rawurlencode( $request ), $template );
	return wp_http_validate_url( $url ) ? $url : '';
}

/**
 * Build one network's tracking URL for a request string.
 *
 * @param string               $id      Network id.
 * @param array<string,string> $account Saved fields.
 * @param string               $request Request string.
 * @return string Tracking URL, or an empty string when the account is incomplete.
 */
function adzworld_affiliate_url( $id, $account, $request ) {
	$request = trim( $request );
	if ( '' === $request ) {
		return '';
	}
	if ( 'amazon' === $id && '' !== $account['tag'] && '' !== $account['host'] ) {
		return 'https://' . $account['host'] . '/s?k=' . rawurlencode( $request ) . '&tag=' . rawurlencode( $account['tag'] );
	}
	if ( 'walmart' === $id && '' !== $account['partner'] && '' !== $account['ad'] && '' !== $account['program'] ) {
		$search = 'https://www.walmart.com/search?q=' . rawurlencode( $request );
		return 'https://goto.walmart.com/c/' . $account['partner'] . '/' . $account['ad'] . '/' . $account['program'] . '?u=' . rawurlencode( $search );
	}
	if ( 'awin' === $id ) {
		$destination = adzworld_affiliate_destination( $account['destination'], $request );
		if ( '' === $destination || '' === $account['publisher'] || '' === $account['advertiser'] ) {
			return '';
		}
		return 'https://www.awin1.com/cread.php?awinmid=' . $account['advertiser'] . '&awinaffid=' . $account['publisher'] . '&ued=' . rawurlencode( $destination );
	}
	if ( 'cj' === $id ) {
		$destination = adzworld_affiliate_destination( $account['destination'], $request );
		if ( '' === $destination || '' === $account['pid'] || '' === $account['aid'] || '' === $account['domain'] ) {
			return '';
		}
		return 'https://' . $account['domain'] . '/click-' . $account['pid'] . '-' . $account['aid'] . '?url=' . rawurlencode( $destination );
	}
	if ( 'rakuten' === $id ) {
		$destination = adzworld_affiliate_destination( $account['destination'], $request );
		if ( '' === $destination || '' === $account['id'] || '' === $account['mid'] ) {
			return '';
		}
		return 'https://click.linksynergy.com/deeplink?id=' . rawurlencode( $account['id'] ) . '&mid=' . rawurlencode( $account['mid'] ) . '&murl=' . rawurlencode( $destination );
	}
	if ( 'clickbank' === $id && '' !== $account['nickname'] && '' !== $account['vendor'] ) {
		/*
		 * The documented HopLink is the affiliate nickname and the vendor
		 * nickname as subdomains of hop.clickbank.net. A vendor's own page with
		 * ?hop=nickname appended is not tracked by ClickBank and is not used.
		 *
		 * Nothing derived from the visitor's request is appended. The other
		 * networks here turn that request into a search, which is the point of
		 * them; sending a visitor's own words into a third party's reporting is
		 * the behaviour this plugin exists to avoid. The tracking id is the
		 * publisher's own static value or nothing.
		 */
		$hoplink = 'https://' . rawurlencode( $account['nickname'] ) . '.' . rawurlencode( $account['vendor'] ) . '.hop.clickbank.net';

		if ( '' !== $account['tid'] ) {
			$hoplink .= '?tid=' . rawurlencode( $account['tid'] );
		}

		return $hoplink;
	}
	if ( 'ebay' === $id && '' !== $account['campid'] && '' !== $account['mkrid'] && '' !== $account['siteid'] ) {
		return 'https://www.ebay.com/sch/?kw=' . rawurlencode( $request )
			. '&mkevt=1&mkcid=1&mkrid=' . rawurlencode( $account['mkrid'] )
			. '&siteid=' . rawurlencode( $account['siteid'] )
			. '&campid=' . rawurlencode( $account['campid'] )
			. '&toolid=10001&customid=adzworld';
	}
	return '';
}

/**
 * Tracking links for every complete account.
 *
 * @param string $request Request string.
 * @return array<int,array{id:string,label:string,url:string}>
 */
function adzworld_affiliate_links( $request ) {
	$links = array();
	foreach ( adzworld_affiliate_accounts() as $id => $account ) {
		if ( '1' !== $account['enabled'] ) {
			continue;
		}
		$url = adzworld_affiliate_url( $id, $account, $request );
		if ( '' === $url ) {
			continue;
		}
		$links[] = array(
			'id'    => $id,
			'label' => adzworld_affiliate_networks()[ $id ]['label'],
			'url'   => $url,
		);
	}
	return $links;
}

/**
 * Count enabled, complete affiliate accounts.
 *
 * @return int
 */
function adzworld_affiliate_connected_count() {
	return count( adzworld_affiliate_links( 'test request' ) );
}

/**
 * Sponsored links for the category currently on screen.
 *
 * @param array  $pref     Preference hierarchy.
 * @param string $category Category.
 * @return string Markup, empty when no account can take this request.
 */
function adzworld_affiliate_markup( $pref, $category ) {
	$request = adzworld_affiliate_request_string( $pref, $category );
	$links   = adzworld_affiliate_links( $request );
	if ( ! $links ) {
		return '';
	}
	$html = '<ul class="adzworld-affiliate-offers">';
	foreach ( $links as $link ) {
		$html .= '<li><a class="adzworld-affiliate-offer" href="' . esc_url( $link['url'] ) . '" rel="sponsored nofollow" target="_blank">'
			. esc_html( $link['label'] . ': ' . $request ) . '</a></li>';
	}
	return $html . '</ul>';
}

/**
 * Save the affiliate account form.
 *
 * @return void
 */
function adzworld_affiliate_save() {
	check_admin_referer( 'adzworld_affiliate_accounts', 'adzworld_affiliate_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	$accounts = array();
	foreach ( adzworld_affiliate_networks() as $id => $network ) {
		$row = array(
			'enabled' => isset( $_POST[ 'adzworld_affiliate_' . $id . '_enabled' ] ) ? '1' : '0',
		);
		foreach ( array_keys( $network['fields'] ) as $field ) {
			$key           = 'adzworld_affiliate_' . $id . '_' . $field;
			$row[ $field ] = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		}
		$accounts[ $id ] = $row;
	}
	update_option( 'adzworld_affiliate_accounts', adzworld_sanitize_affiliate_accounts( $accounts ), false );
	set_transient( 'adzworld_affiliate_saved_' . get_current_user_id(), '1', MINUTE_IN_SECONDS );
	$return_tab = isset( $_POST['adzworld_return_tab'] ) && is_string( $_POST['adzworld_return_tab'] )
		&& 'networks' === sanitize_key( wp_unslash( $_POST['adzworld_return_tab'] ) ) ? 'networks' : 'affiliates';
	wp_safe_redirect( adzworld_desk_url( $return_tab ) );
	exit;
}
add_action( 'admin_post_adzworld_save_affiliates', 'adzworld_affiliate_save' );

/**
 * Render the affiliate account form.
 *
 * @param bool $embedded Whether the form is inside the Connections screen.
 * @return void
 */
function adzworld_affiliate_page( $embedded = false ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	$accounts = adzworld_affiliate_accounts();
	$notice   = 'adzworld_affiliate_saved_' . get_current_user_id();
	if ( get_transient( $notice ) ) {
		delete_transient( $notice );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Affiliate attribution saved.', 'adz-world' ) . '</p></div>';
	}
	if ( ! $embedded && function_exists( 'adzworld_registration_desk' ) ) {
		adzworld_registration_desk();
	}
	echo '<h2>' . esc_html__( 'Affiliate programs', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html(
		sprintf(
			/* translators: %s: product name. */
			__( 'These are your publisher identifiers. %1$s adds them to eligible outbound links so the affiliate network attributes commission to your own account and pays you under that network’s terms. %1$s does not receive or redistribute that direct commission.', 'adz-world' ),
			ADZWORLD_NAME
		)
	) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="adzworld_save_affiliates">';
	if ( $embedded ) {
		echo '<input type="hidden" name="adzworld_return_tab" value="networks">';
	}
	wp_nonce_field( 'adzworld_affiliate_accounts', 'adzworld_affiliate_nonce' );
	echo '<p>' . esc_html__( 'Each link uses the active category plus its refinements. A network is used only when it is enabled and every required identifier is valid.', 'adz-world' ) . '</p>';
	echo '<div class="adzworld-connection-grid adzworld-affiliate-grid">';
	foreach ( adzworld_affiliate_networks() as $id => $network ) {
		$enabled = isset( $accounts[ $id ]['enabled'] ) ? $accounts[ $id ]['enabled'] : '0';
		echo '<fieldset id="adzworld-affiliate-' . esc_attr( $id ) . '" class="adzworld-connection-card"><legend>' . esc_html( $network['label'] ) . '</legend>';
		echo '<span class="adzworld-connection-status' . ( '1' === $enabled ? ' is-enabled' : '' ) . '">' . esc_html( '1' === $enabled ? __( 'Enabled', 'adz-world' ) : __( 'Needs setup', 'adz-world' ) ) . '</span>';
		echo '<p>' . esc_html( $network['note'] ) . '</p>';
		if ( function_exists( 'adzworld_registration_has' ) && adzworld_registration_has( $id ) ) {
			$procedure_tab = ( ! $embedded && function_exists( 'adzworld_desk_tab' ) && 'affiliates' === adzworld_desk_tab() ) ? 'affiliates' : 'networks';
			echo '<p class="adzworld-pay-actions">';
			adzworld_registration_render_signup( $id, false );
			echo ' <a href="' . esc_url( adzworld_desk_url( $procedure_tab ) . '#adzworld-pay-' . rawurlencode( $id ) ) . '">' . esc_html__( 'Registration procedure', 'adz-world' ) . '</a></p>';
			adzworld_registration_render_steps( $id );
		} else {
			echo '<p><a class="button" href="' . esc_url( $network['account'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create or open account', 'adz-world' ) . '</a></p>';
		}
		foreach ( $network['fields'] as $field => $rule ) {
			$value = isset( $accounts[ $id ][ $field ] ) ? $accounts[ $id ][ $field ] : '';
			$input = 'adzworld_affiliate_' . $id . '_' . $field;
			echo '<p><label for="' . esc_attr( $input ) . '">' . esc_html( $rule['label'] ) . '</label><br>';
			if ( isset( $rule['choices'] ) ) {
				echo '<select id="' . esc_attr( $input ) . '" name="' . esc_attr( $input ) . '">';
				echo '<option value="">' . esc_html__( 'Not connected', 'adz-world' ) . '</option>';
				foreach ( $rule['choices'] as $choice ) {
					echo '<option value="' . esc_attr( $choice ) . '"' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
				}
				echo '</select>';
			} else {
				echo '<input class="regular-text" id="' . esc_attr( $input ) . '" name="' . esc_attr( $input ) . '" value="' . esc_attr( $value ) . '">';
			}
			echo '</p>';
		}
		echo '<p><label><input type="checkbox" name="' . esc_attr( 'adzworld_affiliate_' . $id . '_enabled' ) . '" value="1"' . checked( $enabled, '1', false ) . '> '
			. esc_html__( 'Enable links using this account', 'adz-world' ) . '</label></p></fieldset>';
	}
	echo '</div>';
	submit_button( __( 'Save affiliate accounts', 'adz-world' ) );
	echo '</form>';
}
