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
	$networks = array(
		'amazon'    => array(
			'label'   => 'Amazon Associates',
			'account' => 'https://affiliate-program.amazon.com/',
			'note'    => __( 'The tracking id is the tag on an Associates link, for example sitename-20. One tag works only on the marketplace where it was issued. Product images, titles, and prices require the Creators API credential id, secret, and version from Associates Central.', 'adz-world' ),
			'fields'  => array(
				'tag'                => array(
					'label'   => __( 'Tracking ID', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9][A-Za-z0-9_-]{1,40}$/D',
				),
				'credential_id'      => array(
					'label'   => __( 'Creators API Credential ID', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9._-]{8,255}$/D',
				),
				'credential_secret'  => array(
					'label'   => __( 'Creators API Credential Secret', 'adz-world' ),
					'pattern' => '/^[^\x00-\x20\x7F]{8,512}$/D',
					'secret'  => true,
				),
				'credential_version' => array(
					'label'   => __( 'Creators API Credential Version', 'adz-world' ),
					'choices' => array( '3.1', '3.2', '3.3' ),
				),
				'host'               => array(
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
			'account' => 'https://publisher.rakutenadvertising.com/',
			'note'    => __( 'SID identifies this publisher site. MID identifies one approved advertiser. Both are required for a tracked deep link, but the advertiser partnership, active offer and product eligibility must also be confirmed in Rakuten.', 'adz-world' ),
			'fields'  => array(
				'id'          => array(
					'label'   => __( 'Publisher site ID (SID)', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9]{1,20}$/D',
					'help'    => __( 'Shown beside your publisher account in the Rakuten dashboard. This identifies the publisher site; it is not an advertiser or product ID.', 'adz-world' ),
				),
				'mid'         => array(
					'label'   => __( 'Current advertiser ID (MID)', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9]{1,20}$/D',
					'help'    => __( 'Choose an advertiser with an approved partnership and active offer. Rakuten shows its MID in advertiser and link details.', 'adz-world' ),
				),
				'destination' => array(
					'label'   => __( 'Eligible advertiser search URL', 'adz-world' ),
					'pattern' => 'url',
					'help'    => __( 'Use an advertiser URL permitted for product or deep linking and put {query} where the reader request belongs. Not every advertiser permits deep links.', 'adz-world' ),
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
		'etsy'      => array(
			'label'   => 'Etsy',
			'account' => 'https://www.etsy.com/developers/your-apps',
			'note'    => __( 'The keystring and shared secret come from an Etsy app. Since 9 February 2026 the x-api-key header is keystring:shared_secret. The fetcher sends the request string as the keywords on findAllListingsActive. Etsy’s affiliate programme is moving from Awin to Rakuten, so id is the Rakuten publisher site id and mid is the Etsy advertiser id from the programme you joined. Neither id is supplied by this plugin.', 'adz-world' ),
			'fields'  => array(
				'keystring'     => array(
					'label'   => __( 'Etsy keystring', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9]{8,80}$/D',
				),
				'shared_secret' => array(
					'label'   => __( 'Etsy shared secret', 'adz-world' ),
					'pattern' => '/^[^\x00-\x20\x7F:]{8,128}$/D',
					'secret'  => true,
				),
				'id'            => array(
					'label'   => __( 'Rakuten publisher site id', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9]{1,20}$/D',
				),
				'mid'           => array(
					'label'   => __( 'Etsy advertiser id', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9]{1,20}$/D',
				),
			),
		),
		'ebay'      => array(
			'label'   => 'eBay Partner Network',
			'account' => 'https://partnernetwork.ebay.com/',
			'note'    => __( 'campid is the EPN campaign id. The US rotation id 711-53200-19255-0 and site id 0 are the values in eBay’s link-parameter article for ebay.com. For another marketplace, paste the rotation id and site id from that article. toolid 10001 is eBay’s custom-link tool.', 'adz-world' ),
			'fields'  => array(
				'environment'  => array(
					'label'   => __( 'Environment (sandbox needs no production approval)', 'adz-world' ),
					'choices' => array( 'production', 'sandbox' ),
				),
				'campid'       => array(
					'label'   => __( 'Campaign id (optional until EPN approves)', 'adz-world' ),
					'pattern' => '/^[0-9]{6,12}$/D',
				),
				'mkrid'        => array(
					'label'   => __( 'Rotation id', 'adz-world' ),
					'pattern' => '/^[0-9]{3}-[0-9]{5}-[0-9]{5}-[0-9]$/D',
				),
				'siteid'       => array(
					'label'   => __( 'Site id', 'adz-world' ),
					'pattern' => '/^[0-9]{1,4}$/D',
				),
				'clientid'     => array(
					'label'   => __( 'Developer App ID', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9\-]{10,80}$/D',
				),
				'clientsecret' => array(
					'label'   => __( 'Developer Cert ID', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9\-]{10,80}$/D',
				),
			),
		),
	);

	/**
	 * Filters the affiliate providers available to the publisher.
	 *
	 * Structured-result adapters use this hook to add their own credential
	 * fields without rewriting the shared affiliate account screen.
	 *
	 * @param array<string,array<string,mixed>> $networks Affiliate providers.
	 */
	return apply_filters( 'adzworld_affiliate_networks', $networks );
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
		$split = adzworld_pair_split( $stored );
		if ( 2 === count( $split ) && $category === $split[0] && '' !== trim( $split[1] ) ) {
			$parts[] = trim( $split[1] );
		}
	}
	return trim( implode( ' ', $parts ) );
}

/**
 * Amazon search URL for one category and the refinements attached to it.
 *
 * @param array  $pref     Preference hierarchy.
 * @param string $category Category.
 * @return string Tagged search URL, empty when Amazon is not connected.
 */
function adzworld_affiliate_category_url( $pref, $category ) {
	$accounts = adzworld_affiliate_accounts();
	if ( ! isset( $accounts['amazon']['enabled'] ) || '1' !== $accounts['amazon']['enabled'] ) {
		return '';
	}

	return adzworld_affiliate_url( 'amazon', $accounts['amazon'], adzworld_affiliate_request_string( $pref, $category ) );
}

/**
 * Showcase readout for one category: the request string, the saved Amazon
 * tracking id, and the tagged search URL, each written as text.
 *
 * @param array  $pref     Preference hierarchy.
 * @param string $category Category.
 * @return string Readout markup, or an empty string when unavailable.
 */
function adzworld_affiliate_category_search_link( $pref, $category ) {
	$request  = adzworld_affiliate_request_string( $pref, $category );
	$url      = adzworld_affiliate_category_url( $pref, $category );
	$accounts = adzworld_affiliate_accounts();
	$tag      = isset( $accounts['amazon']['tag'] ) ? (string) $accounts['amazon']['tag'] : '';

	if ( '' === $request || '' === $url || '' === $tag ) {
		return '';
	}

	return '<div class="adzworld-category-search">'
		. '<p class="adzworld-category-search__line"><span class="adzworld-category-search__label">'
		. esc_html__( 'Request string', 'adz-world' )
		. '</span> <code class="adzworld-category-search__value">'
		. esc_html( $request )
		. '</code></p>'
		. '<p class="adzworld-category-search__line"><span class="adzworld-category-search__label">'
		. esc_html__( 'Amazon tracking id', 'adz-world' )
		. '</span> <code class="adzworld-category-search__value">'
		. esc_html( $tag )
		. '</code></p>'
		. '<p class="adzworld-category-search__line"><a href="'
		. esc_url( $url )
		. '" rel="sponsored nofollow" target="_blank"><code class="adzworld-category-search__value">'
		. esc_html( $url )
		. '</code></a></p></div>';
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
	if ( 'etsy' === $id && function_exists( 'adzworld_etsy_tracked_url' ) ) {
		return adzworld_etsy_tracked_url( 'https://www.etsy.com/search?q=' . rawurlencode( $request ), $account );
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
	$count = 0;
	foreach ( adzworld_affiliate_accounts() as $id => $account ) {
		if ( adzworld_affiliate_account_ready( $id, $account ) ) {
			++$count;
		}
	}

	return $count;
}

/**
 * Whether an enabled affiliate account has everything needed to serve adz.
 *
 * Static-link providers are ready when their tracked URL can be built.
 * Structured-result providers can validate their API and attribution fields
 * with the filter without exposing a placeholder or untracked URL.
 *
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool Whether the provider is ready.
 */
function adzworld_affiliate_account_ready( $id, $account ) {
	$enabled = isset( $account['enabled'] ) && '1' === (string) $account['enabled'];
	$ready   = $enabled && '' !== adzworld_affiliate_url( $id, $account, 'connection test' );

	/**
	 * Filters whether one affiliate account can serve attributed adz.
	 *
	 * @param bool                 $ready   Default readiness.
	 * @param string               $id      Provider id.
	 * @param array<string,string> $account Saved provider fields.
	 */
	return (bool) apply_filters( 'adzworld_affiliate_account_ready', $ready, $id, $account );
}

/**
 * Whether a Rakuten account has an attributed advertiser destination.
 *
 * @param bool                 $earns   Whether the account earns.
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool Whether Rakuten can attribute a qualifying transaction.
 */
function adzworld_rakuten_account_earns( $earns, $id, $account ) {
	if ( 'rakuten' !== $id ) {
		return $earns;
	}

	return ! empty( $account['id'] )
		&& ! empty( $account['mid'] )
		&& ! empty( $account['destination'] )
		&& '' !== adzworld_affiliate_destination_template( $account['destination'] );
}

add_filter( 'adzworld_affiliate_account_earns', 'adzworld_rakuten_account_earns', 10, 3 );

/**
 * Sponsored links for the category currently on screen.
 *
 * @param array  $pref     Preference hierarchy.
 * @param string $category Category.
 * @return string Markup, empty when no account can take this request.
 */
function adzworld_affiliate_markup( $pref, $category ) {
	$request = adzworld_affiliate_request_string( $pref, $category );
	if ( '' === $request ) {
		return '';
	}

	$links = adzworld_affiliate_links( $request );
	$html  = adzworld_products_markup( adzworld_products_collect( $request ), $request );

	if ( ! $links ) {
		return $html;
	}
	$html .= '<ul class="adzworld-affiliate-offers">';
	foreach ( $links as $link ) {
		$html .= '<li><a class="adzworld-affiliate-offer" href="' . esc_url( $link['url'] ) . '" rel="sponsored nofollow" target="_blank">'
			. esc_html( $link['label'] . ': ' . $request ) . '</a></li>';
	}
	return $html . '</ul>';
}

/**
 * Retrieve three tracked Amazon product results.
 *
 * The tag alone does not return images, titles, or prices. Those come from
 * SearchItems after the Creators API credential is saved.
 *
 * @param string $request Category and refinements.
 * @return array<int,array{image:string,title:string,price:string,url:string}>|WP_Error
 */
function adzworld_amazon_product_results( $request ) {
	$accounts = adzworld_affiliate_accounts();
	$amazon   = isset( $accounts['amazon'] ) ? $accounts['amazon'] : array();
	$request  = trim( wp_strip_all_tags( (string) $request ) );
	$required = array( 'tag', 'host', 'credential_id', 'credential_secret', 'credential_version' );

	foreach ( $required as $field ) {
		if ( empty( $amazon[ $field ] ) ) {
			return new WP_Error( 'adzworld_amazon_credentials', __( 'Amazon Creators API credentials are incomplete.', 'adz-world' ) );
		}
	}
	if ( '' === $request ) {
		return new WP_Error( 'adzworld_amazon_request', __( 'The Amazon product request is empty.', 'adz-world' ) );
	}

	$result_key = 'adzworld_amazon_results_' . md5( $amazon['host'] . '|' . $amazon['tag'] . '|' . $request );
	$cached     = get_transient( $result_key );
	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	$token_key = 'adzworld_amazon_token_' . md5( $amazon['credential_id'] . '|' . $amazon['credential_version'] );
	$token     = get_transient( $token_key );
	if ( ! is_string( $token ) || '' === $token ) {
		$endpoints = array(
			'3.1' => 'https://api.amazon.com/auth/o2/token',
			'3.2' => 'https://api.amazon.co.uk/auth/o2/token',
			'3.3' => 'https://api.amazon.co.jp/auth/o2/token',
		);
		$version   = $amazon['credential_version'];
		if ( ! isset( $endpoints[ $version ] ) ) {
			return new WP_Error( 'adzworld_amazon_version', __( 'The Amazon credential version is invalid.', 'adz-world' ) );
		}

		$token_response = wp_safe_remote_post(
			$endpoints[ $version ],
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'grant_type'    => 'client_credentials',
						'client_id'     => $amazon['credential_id'],
						'client_secret' => $amazon['credential_secret'],
						'scope'         => 'creatorsapi::default',
					)
				),
			)
		);
		if ( is_wp_error( $token_response ) ) {
			return $token_response;
		}

		$token_data = json_decode( wp_remote_retrieve_body( $token_response ), true );
		$token      = is_array( $token_data ) && isset( $token_data['access_token'] ) ? (string) $token_data['access_token'] : '';
		if ( 200 !== (int) wp_remote_retrieve_response_code( $token_response ) || '' === $token || 1 === preg_match( '/[\x00-\x20\x7F]/', $token ) ) {
			return new WP_Error( 'adzworld_amazon_token', __( 'Amazon did not issue an access token.', 'adz-world' ) );
		}

		$expires = isset( $token_data['expires_in'] ) ? max( 60, (int) $token_data['expires_in'] - 60 ) : 3300;
		set_transient( $token_key, $token, $expires );
	}

	$response = wp_safe_remote_post(
		'https://creatorsapi.amazon/catalog/v1/searchItems',
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
				'x-marketplace' => $amazon['host'],
			),
			'body'    => wp_json_encode(
				array(
					'marketplace' => $amazon['host'],
					'partnerTag'  => $amazon['tag'],
					'keywords'    => $request,
					'searchIndex' => 'All',
					'itemCount'   => 3,
					'resources'   => array(
						'images.primary.medium',
						'itemInfo.title',
						'offersV2.listings.price',
					),
				)
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || ! is_array( $data ) ) {
		return new WP_Error( 'adzworld_amazon_search', __( 'Amazon did not return product results.', 'adz-world' ) );
	}

	$items   = isset( $data['searchResult']['items'] ) && is_array( $data['searchResult']['items'] ) ? $data['searchResult']['items'] : array();
	$results = array();
	foreach ( $items as $item ) {
		$url   = isset( $item['detailPageURL'] ) ? esc_url_raw( $item['detailPageURL'] ) : '';
		$image = isset( $item['images']['primary']['medium']['url'] ) ? esc_url_raw( $item['images']['primary']['medium']['url'] ) : '';
		$title = isset( $item['itemInfo']['title']['displayValue'] ) ? sanitize_text_field( $item['itemInfo']['title']['displayValue'] ) : '';
		$price = isset( $item['offersV2']['listings'][0]['price']['money']['displayAmount'] ) ? sanitize_text_field( $item['offersV2']['listings'][0]['price']['money']['displayAmount'] ) : '';
		if ( '' === $url || '' === $image || '' === $title ) {
			continue;
		}
		$results[] = array(
			'image' => $image,
			'title' => $title,
			'price' => $price,
			'url'   => $url,
		);
		if ( 3 === count( $results ) ) {
			break;
		}
	}

	set_transient( $result_key, $results, 15 * MINUTE_IN_SECONDS );
	return $results;
}

/**
 * One Amazon product inside the results carousel.
 *
 * @param array{image:string,title:string,price:string,url:string} $result Product result.
 * @param int                                                      $index  Zero-based result position.
 * @param int                                                      $count  Number of results.
 * @return string Result markup.
 */
function adzworld_amazon_result_slide( $result, $index, $count ) {
	return adzworld_affiliate_result_slide( $result, $index, $count );
}

/**
 * One structured product inside the results carousel.
 *
 * @param array $result Product result.
 * @param int   $index  Zero-based result position.
 * @param int   $count  Number of results.
 * @return string Result markup.
 */
function adzworld_affiliate_result_slide( $result, $index, $count ) {
	$provider = isset( $result['provider'] ) ? sanitize_key( (string) $result['provider'] ) : '';
	$item_id  = isset( $result['item_id'] ) ? sanitize_text_field( (string) $result['item_id'] ) : '';
	if ( '' === $item_id ) {
		$item_id = md5( (string) $result['url'] );
	}
	$target      = sanitize_key( 'provider-' . $provider . '-' . $item_id );
	$price       = '';
	$offer_class = 'adzworld-results__offer';
	if ( '' !== $result['price'] ) {
		$price = '<span class="adzworld-results__price">' . esc_html( $result['price'] ) . '</span>';
	}
	$media = '';
	if ( ! empty( $result['image'] ) ) {
		$media = '<span class="adzworld-results__media"><img class="adzworld-results__image" src="' . esc_url( $result['image'] ) . '" alt="" loading="lazy" decoding="async"></span>';
	} else {
		$offer_class .= ' adzworld-results__offer--text';
	}
	$source = '';
	if ( ! empty( $result['source'] ) ) {
		$source = '<span class="adzworld-results__source">' . esc_html( $result['source'] ) . '</span>';
	} elseif ( ! empty( $result['source_image'] ) && ! empty( $result['source_label'] ) && ! empty( $result['source_url'] ) ) {
		$source = '<a class="adzworld-results__source" href="' . esc_url( $result['source_url'] ) . '" target="_blank" rel="noopener noreferrer"><img class="adzworld-results__source-image" src="' . esc_url( $result['source_image'] ) . '" alt="' . esc_attr( $result['source_label'] ) . '" loading="lazy" decoding="async"></a>';
	} elseif ( ! empty( $result['source_label'] ) ) {
		$source = '<span class="adzworld-results__source">' . esc_html( $result['source_label'] ) . '</span>';
	}

	return '<section class="adzworld-results__slide" data-adzworld-result-target="' . esc_attr( $target ) . '" data-adzworld-provider="' . esc_attr( $provider ) . '" data-adzworld-item-id="' . esc_attr( $item_id ) . '"' . ( 0 === $index ? '' : ' hidden' ) . ' aria-roledescription="' . esc_attr__( 'slide', 'adz-world' ) . '" aria-label="'
		. esc_attr( ( $index + 1 ) . ' / ' . $count ) . '"><a class="' . esc_attr( $offer_class ) . '" href="' . esc_url( $result['url'] ) . '" rel="sponsored nofollow" target="_blank">'
		. $media
		. '<span class="adzworld-results__body"><span class="adzworld-results__product-title">' . esc_html( $result['title'] ) . '</span>'
		. $price . '</span></a>' . $source . '</section>';
}

/**
 * Add Amazon Creators API products to the shared collector.
 *
 * @param array<int,array<string,string>> $results Rows from earlier providers.
 * @param string                          $request Reader's request.
 * @return array<int,array<string,string>> Rows with Amazon products appended.
 */
function adzworld_amazon_append_product_results( $results, $request ) {
	$amazon = adzworld_amazon_product_results( $request );
	if ( is_wp_error( $amazon ) || ! is_array( $amazon ) ) {
		return $results;
	}

	foreach ( $amazon as $row ) {
		$row['provider'] = 'amazon';
		$results[]       = $row;
	}

	return $results;
}

add_filter( 'adzworld_affiliate_product_results', 'adzworld_amazon_append_product_results', 5, 2 );

/**
 * Collect normalized product results from every configured search provider.
 *
 * @param string $request Category and category-specific refinements.
 * @return array<int,array<string,string>> Product results.
 */
function adzworld_affiliate_product_results( $request ) {
	return adzworld_products_collect( $request );
}

/**
 * Inner carousel of search results for one category.
 *
 * The outer rotation moves between categories. This carousel moves between
 * attributed product results from connected search providers. AdSense is not
 * a member.
 *
 * @param array  $pref     Preference hierarchy.
 * @param string $category Category.
 * @param string $title    Title row saved on the adz. Empty prints no row.
 * @param string $provider Optional provider id for a provider-specific slot.
 * @param string $rotation Rotation name.
 * @param int    $slot     Category slot number.
 * @return string Results markup, empty when no search account is connected.
 */
function adzworld_affiliate_showcase( $pref, $category, $title = '', $provider = '', $rotation = '', $slot = 0 ) {
	$request = adzworld_affiliate_request_string( $pref, $category );
	$results = adzworld_products_collect(
		$request,
		$provider,
		array(
			'rotation' => $rotation,
			'slot'     => $slot,
			'category' => $category,
		)
	);
	$scoped  = adzworld_category_refinement_preference( $pref, $category );
	$title   = function_exists( 'adzworld_internal_sanitize_title_row' )
		? adzworld_internal_sanitize_title_row( $title )
		: sanitize_text_field( (string) $title );

	if ( ! $results ) {
		return '';
	}

	if ( ! empty( $scoped['exclude'] ) ) {
		$results = array_values(
			array_filter(
				$results,
				static function ( $result ) use ( $scoped ) {
					$title = strtolower( (string) $result['title'] );
					foreach ( $scoped['exclude'] as $term ) {
						if ( preg_match( '/\b' . preg_quote( strtolower( (string) $term ), '/' ) . '\b/', $title ) ) {
							return false;
						}
					}

					return true;
				}
			)
		);
	}

	if ( ! $results ) {
		return '';
	}

	$count   = count( $results );
	$slides  = '';
	$buttons = '';
	$row     = '';

	foreach ( $results as $index => $result ) {
		$slides .= adzworld_affiliate_result_slide( $result, (int) $index, $count );
	}
	if ( '' === $slides ) {
		return '';
	}

	if ( '' !== $title ) {
		$row = '<div class="adzworld-results__title">' . esc_html( $title ) . '</div>';
	}

	if ( $count > 1 ) {
		$buttons = '<button class="adzworld-results__previous" type="button" data-adzworld-result-step="-1"><span aria-hidden="true">&#8249;</span><span class="screen-reader-text">'
			. esc_html__( 'Previous result', 'adz-world' ) . '</span></button>'
			. '<button class="adzworld-results__next" type="button" data-adzworld-result-step="1"><span aria-hidden="true">&#8250;</span><span class="screen-reader-text">'
			. esc_html__( 'Next result', 'adz-world' ) . '</span></button>';
	}

	return '<div class="adzworld-results" data-adzworld-results data-adzworld-request="' . esc_attr( $request ) . '" role="region" aria-label="' . esc_attr( '' !== $title ? $title : __( 'Search results', 'adz-world' ) ) . '">'
		. $row
		. '<div class="adzworld-results__slides">' . $slides . '</div>'
		. '<div class="adzworld-results__navigation" hidden>' . $buttons . '</div></div>';
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
	$previous = adzworld_affiliate_accounts();
	$accounts = array();
	foreach ( adzworld_affiliate_networks() as $id => $network ) {
		$row = array(
			'enabled' => isset( $_POST[ 'adzworld_affiliate_' . $id . '_enabled' ] ) ? '1' : '0',
		);
		foreach ( $network['fields'] as $field => $rule ) {
			$key = 'adzworld_affiliate_' . $id . '_' . $field;
			$raw = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_strip_all_tags( wp_unslash( $_POST[ $key ] ) ) : '';
			if ( ! empty( $rule['secret'] ) ) {
				$row[ $field ] = '' === $raw && isset( $previous[ $id ][ $field ] ) ? $previous[ $id ][ $field ] : $raw;
			} else {
				$row[ $field ] = sanitize_text_field( $raw );
			}
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
