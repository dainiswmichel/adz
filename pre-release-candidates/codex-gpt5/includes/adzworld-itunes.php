<?php
/**
 * Apple Search: real media products, with no credential at all.
 *
 * The iTunes Search API needs no key, no registration and no approval. It
 * answers an arbitrary search term with real products: a title, cover art, a
 * price in a stated currency, and a link to the item in Apple's store. It is
 * the only provider here that serves a reader on the day the plugin is
 * installed.
 *
 * Its catalogue is Apple's media store and nothing else: music, films, TV,
 * audiobooks, books and apps. It has no physical goods. A rotation about
 * couches gets films with "couch" in the title, which is worse than nothing,
 * so this provider is off unless the publisher turns it on and it is best
 * suited to a rotation whose subject is media.
 *
 * Monetisation is separate and optional: Apple's affiliate programme issues a
 * token which, when saved here, is appended to every store link.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/** How long a result set is kept, matching the other providers. */
const ADZWORLD_ITUNES_RESULT_TTL = 900;

/** Products requested per search. */
const ADZWORLD_ITUNES_LIMIT = 3;

/**
 * Add the Apple Search fields to the shared account screen.
 *
 * @param array<string,array<string,mixed>> $networks Affiliate providers.
 * @return array<string,array<string,mixed>> Affiliate providers.
 */
function adzworld_itunes_register_network( $networks ) {
	$networks['itunes'] = array(
		'label'   => 'Apple Search',
		'account' => 'https://performance-partners.apple.com/',
		'note'    => __( 'The Apple Search API needs no key, so enabling this account is enough to serve real products at once. Its catalogue is Apple media only: music, films, TV, audiobooks, books and apps, with no physical goods, so it suits a rotation whose subject is media. The affiliate token is optional and is what makes the links earn.', 'adz-world' ),
		'fields'  => array(
			'media' => array(
				'label'   => __( 'Catalogue', 'adz-world' ),
				'choices' => array( 'all', 'music', 'movie', 'audiobook', 'ebook', 'podcast', 'software' ),
			),
			'at'    => array(
				'label'   => __( 'Affiliate token (optional)', 'adz-world' ),
				'pattern' => '/^[A-Za-z0-9]{0,32}$/D',
			),
		),
	);

	return $networks;
}

/**
 * Add the Apple Search procedure to the registration desk.
 *
 * @param array<string,array<string,mixed>> $catalog Registration procedures.
 * @return array<string,array<string,mixed>> Registration procedures.
 */
function adzworld_itunes_register_catalog( $catalog ) {
	$catalog['itunes'] = array(
		'label'        => 'Apple Search',
		'group'        => 'affiliate',
		'signup'       => 'https://performance-partners.apple.com/',
		'signup_label' => __( 'Apply to Apple Services Performance Partners', 'adz-world' ),
		'pays'         => __( 'The affiliate token pays this site. Without it the products still serve, with plain store links.', 'adz-world' ),
		'steps'        => array(
			__( 'Nothing is required to serve products: enable this account and choose a catalogue.', 'adz-world' ),
			__( 'To earn from the links, apply to Apple Services Performance Partners and copy the affiliate token it issues.', 'adz-world' ),
			__( 'Paste the token here and save. Existing links gain the token at once.', 'adz-world' ),
		),
	);

	return $catalog;
}

/**
 * Mark Apple Search as a provider that receives the reader's request.
 *
 * @param array<string,bool> $providers Provider request behavior.
 * @return array<string,bool> Provider request behavior.
 */
function adzworld_itunes_register_request_behavior( $providers ) {
	$providers['itunes'] = true;

	return $providers;
}

/**
 * Supply Apple Search readiness to the shared connection check.
 *
 * Enabling it is the whole requirement, because there is no credential.
 *
 * @param bool                 $ready   Existing readiness.
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool
 */
function adzworld_itunes_filter_account_ready( $ready, $id, $account ) {
	if ( 'itunes' !== $id ) {
		return $ready;
	}

	return ! empty( $account['enabled'] ) && '1' === (string) $account['enabled'];
}

/**
 * The saved Apple Search account, or an empty array when it is off.
 *
 * @return array<string,string> Account fields, or empty.
 */
function adzworld_itunes_account() {
	$accounts = function_exists( 'adzworld_affiliate_accounts' ) ? adzworld_affiliate_accounts() : array();
	$account  = isset( $accounts['itunes'] ) && is_array( $accounts['itunes'] ) ? $accounts['itunes'] : array();

	if ( empty( $account['enabled'] ) || '1' !== (string) $account['enabled'] ) {
		return array();
	}

	return $account;
}

/**
 * Append the publisher's affiliate token to a store link.
 *
 * @param string               $url     Store URL as Apple returned it.
 * @param array<string,string> $account Saved account.
 * @return string URL, with the token when one is saved.
 */
function adzworld_itunes_tracked_url( $url, $account ) {
	$url = (string) $url;

	if ( '' === $url || empty( $account['at'] ) ) {
		return $url;
	}

	return add_query_arg( 'at', rawurlencode( $account['at'] ), $url );
}

/**
 * Products for one request string.
 *
 * @param string $request Reader's request.
 * @param int    $limit   Results wanted.
 * @return array<int,array<string,string>> Results, each with title, image, price and url.
 */
function adzworld_itunes_search( $request, $limit = ADZWORLD_ITUNES_LIMIT ) {
	$account = adzworld_itunes_account();
	$request = trim( wp_strip_all_tags( (string) $request ) );
	$limit   = max( 1, min( 10, (int) $limit ) );

	if ( empty( $account ) || '' === $request ) {
		return array();
	}

	$media = isset( $account['media'] ) && '' !== $account['media'] ? $account['media'] : 'all';

	$key    = 'adzworld_itunes_' . md5( $media . '|' . $request . '|' . $limit );
	$cached = get_transient( $key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$response = wp_remote_get(
		add_query_arg(
			array(
				'term'    => rawurlencode( $request ),
				'country' => 'US',
				'media'   => rawurlencode( $media ),
				'limit'   => $limit,
			),
			'https://itunes.apple.com/search'
		),
		array( 'timeout' => 8 )
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return array();
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) || empty( $body['results'] ) || ! is_array( $body['results'] ) ) {
		return array();
	}

	$results = array();

	foreach ( $body['results'] as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		/* A track and a collection name the same thing under different keys. */
		$title = '';
		foreach ( array( 'trackName', 'collectionName' ) as $field ) {
			if ( ! empty( $item[ $field ] ) && is_string( $item[ $field ] ) ) {
				$title = $item[ $field ];
				break;
			}
		}

		$url = '';
		foreach ( array( 'trackViewUrl', 'collectionViewUrl' ) as $field ) {
			if ( ! empty( $item[ $field ] ) && is_string( $item[ $field ] ) ) {
				$url = $item[ $field ];
				break;
			}
		}

		if ( '' === $title || '' === $url ) {
			continue;
		}

		/*
		 * Cover art is returned at 100px. The same URL with a larger size in
		 * its filename is served, and the card renders better for it.
		 */
		$image = ! empty( $item['artworkUrl100'] ) && is_string( $item['artworkUrl100'] ) ? $item['artworkUrl100'] : '';
		$image = '' === $image ? '' : str_replace( '100x100bb', '400x400bb', $image );

		$price = '';
		foreach ( array( 'trackPrice', 'collectionPrice' ) as $field ) {
			if ( isset( $item[ $field ] ) && is_numeric( $item[ $field ] ) && 0 < (float) $item[ $field ] ) {
				$currency = ! empty( $item['currency'] ) && is_string( $item['currency'] ) ? $item['currency'] : '';
				$price    = trim( $currency . ' ' . $item[ $field ] );
				break;
			}
		}

		$results[] = array(
			'title' => $title,
			'image' => $image,
			'price' => $price,
			'url'   => adzworld_itunes_tracked_url( $url, $account ),
		);
	}

	set_transient( $key, $results, ADZWORLD_ITUNES_RESULT_TTL );

	return $results;
}

/**
 * Append Apple's products to the shared result set.
 *
 * @param array<int,array<string,string>> $results Rows from earlier providers.
 * @param string                          $request Reader's request.
 * @return array<int,array<string,string>> Rows with Apple's appended.
 */
function adzworld_itunes_append_product_results( $results, $request ) {
	foreach ( adzworld_itunes_search( $request ) as $row ) {
		$row['provider'] = 'itunes';
		$results[]       = $row;
	}

	return $results;
}

add_filter( 'adzworld_affiliate_networks', 'adzworld_itunes_register_network' );
add_filter( 'adzworld_registration_catalog', 'adzworld_itunes_register_catalog' );
add_filter( 'adzworld_getpaid_affiliate_serves_adz', 'adzworld_itunes_register_request_behavior' );
add_filter( 'adzworld_affiliate_account_ready', 'adzworld_itunes_filter_account_ready', 10, 3 );
add_filter( 'adzworld_affiliate_product_results', 'adzworld_itunes_append_product_results', 10, 2 );

/**
 * Whether this account is credited for a sale.
 *
 * The Apple performance-partners token is what the network pays
 * against. Without it the products still serve and the publisher earns
 * nothing from them.
 *
 * @param bool                 $earns   Whether the account earns.
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool
 */
function adzworld_itunes_filter_account_earns( $earns, $id, $account ) {
	if ( 'itunes' !== $id ) {
		return $earns;
	}

	return ! empty( $account['at'] );
}

add_filter( 'adzworld_affiliate_account_earns', 'adzworld_itunes_filter_account_earns', 10, 3 );
