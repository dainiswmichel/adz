<?php
/**
 * Browse API for eBay: turn a reader's request into actual products.
 *
 * Every other connected provider returns a link. This one returns creatives:
 * a title, a picture, a price and a tagged URL per result, for whatever the
 * reader typed. That is the difference between a slot that points at a search
 * and a slot that answers a question.
 *
 * It is the only marketplace API this plugin can reach without a sales
 * threshold. Amazon's Creators API requires qualifying sales before it will
 * issue credentials, so a new publisher cannot use it; an eBay developer key
 * is issued immediately.
 *
 * Two credentials are needed, both optional on the account: the developer App
 * ID and Cert ID. Without them this file does nothing at all and the eBay slot
 * falls back to the tagged search link, which still earns.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/** How long an OAuth token is kept. eBay issues them for 7200 seconds. */
const ADZWORLD_EBAY_TOKEN_TTL = 6600;

/** How long a result set is kept, so a reader refining repeatedly costs one call. */
const ADZWORLD_EBAY_RESULT_TTL = 900;

/** Results shown in one slot. */
const ADZWORLD_EBAY_LIMIT = 3;

/**
 * Normalise a request to the exact text that is sent and shown.
 *
 * The search and the heading above the cards both use this, so the reader is
 * never shown a request different from the one that produced the products.
 *
 * @param string $request Reader's request.
 * @return string Normalised request.
 */
function adzworld_ebay_request( $request ) {
	return trim( wp_strip_all_tags( (string) $request ) );
}

/**
 * The saved eBay account, or an empty array when it cannot serve products.
 *
 * @return array<string,string> Account fields, or empty.
 */
function adzworld_ebay_account() {
	$accounts = adzworld_affiliate_accounts();
	$account  = isset( $accounts['ebay'] ) ? $accounts['ebay'] : array();

	if ( empty( $account['enabled'] ) || '1' !== (string) $account['enabled'] ) {
		return array();
	}

	/*
	 * Only the two developer credentials are required. The campaign id is an
	 * eBay Partner Network value and that approval takes days, while a
	 * developer keyset is issued at once. Requiring the campaign id here meant
	 * a publisher could not see a single product until an unrelated
	 * application was approved, so a site with developer keys alone serves
	 * real products now and starts earning on them the day the campaign id is
	 * pasted in.
	 */
	foreach ( array( 'clientid', 'clientsecret' ) as $required ) {
		if ( empty( $account[ $required ] ) ) {
			return array();
		}
	}

	return $account;
}

/**
 * An application access token for the Browse API.
 *
 * Client-credentials grant: the token identifies this site, never a reader.
 * Cached, because eBay rate-limits token issuance far harder than searching.
 *
 * @param array<string,string> $account Saved account.
 * @return string Token, or an empty string when one could not be obtained.
 */
function adzworld_ebay_token( $account ) {
	$key    = 'adzworld_ebay_token_' . md5( $account['clientid'] );
	$cached = get_transient( $key );

	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached;
	}

	$response = wp_remote_post(
		'https://api.ebay.com/identity/v1/oauth2/token',
		array(
			'timeout' => 8,
			'headers' => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => 'Basic ' . base64_encode( $account['clientid'] . ':' . $account['clientsecret'] ),
			),
			'body'    => array(
				'grant_type' => 'client_credentials',
				'scope'      => 'https://api.ebay.com/oauth/api_scope',
			),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return '';
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) || empty( $body['access_token'] ) || ! is_string( $body['access_token'] ) ) {
		return '';
	}

	set_transient( $key, $body['access_token'], ADZWORLD_EBAY_TOKEN_TTL );

	return $body['access_token'];
}

/**
 * Products for one request string.
 *
 * The request is the reader's own words, already assembled from their category
 * and its refinements. It is sent as a search term and nothing else: no
 * identifier for the reader is attached, and eBay is told only which campaign
 * to credit.
 *
 * @param string $request Reader's request.
 * @param int    $limit   Results wanted.
 * @return array<int,array<string,string>> Results, each with title, image, price and url.
 */
function adzworld_ebay_search( $request, $limit = ADZWORLD_EBAY_LIMIT ) {
	$request = adzworld_ebay_request( $request );
	$limit   = max( 1, min( 10, (int) $limit ) );

	if ( '' === $request ) {
		return array();
	}

	$account = adzworld_ebay_account();

	if ( empty( $account ) ) {
		return array();
	}

	$key    = 'adzworld_ebay_q_' . md5( $request . '|' . $limit . '|' . $account['campid'] );
	$cached = get_transient( $key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$token = adzworld_ebay_token( $account );

	if ( '' === $token ) {
		return array();
	}

	$headers = array(
		'Authorization'           => 'Bearer ' . $token,
		'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US',
	);

	/*
	 * The affiliate context is what makes eBay return a tagged
	 * itemAffiliateWebUrl rather than a plain link, so the publisher is
	 * credited for a sale. Without a campaign id the same products come back
	 * with plain item links: the slot works and earns nothing, which is the
	 * right state while the Partner Network application is pending.
	 */
	if ( '' !== $account['campid'] ) {
		$headers['X-EBAY-C-ENDUSERCTX'] = 'affiliateCampaignId=' . $account['campid'] . ',affiliateReferenceId=adzworld';
	}

	$response = wp_remote_get(
		add_query_arg(
			array(
				'q'     => rawurlencode( $request ),
				'limit' => $limit,
			),
			'https://api.ebay.com/buy/browse/v1/item_summary/search'
		),
		array(
			'timeout' => 8,
			'headers' => $headers,
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return array();
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) || empty( $body['itemSummaries'] ) || ! is_array( $body['itemSummaries'] ) ) {
		return array();
	}

	$results = array();

	foreach ( $body['itemSummaries'] as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$url = '';
		if ( ! empty( $item['itemAffiliateWebUrl'] ) && is_string( $item['itemAffiliateWebUrl'] ) ) {
			$url = $item['itemAffiliateWebUrl'];
		} elseif ( ! empty( $item['itemWebUrl'] ) && is_string( $item['itemWebUrl'] ) ) {
			$url = $item['itemWebUrl'];
		}

		$title = ! empty( $item['title'] ) && is_string( $item['title'] ) ? $item['title'] : '';

		if ( '' === $url || '' === $title ) {
			continue;
		}

		$image = '';
		if ( ! empty( $item['image']['imageUrl'] ) && is_string( $item['image']['imageUrl'] ) ) {
			$image = $item['image']['imageUrl'];
		} elseif ( ! empty( $item['thumbnailImages'][0]['imageUrl'] ) && is_string( $item['thumbnailImages'][0]['imageUrl'] ) ) {
			$image = $item['thumbnailImages'][0]['imageUrl'];
		}

		$price = '';
		if ( ! empty( $item['price']['value'] ) && ! empty( $item['price']['currency'] ) ) {
			$price = $item['price']['currency'] . ' ' . $item['price']['value'];
		}

		$results[] = array(
			'title' => $title,
			'image' => $image,
			'price' => $price,
			'url'   => $url,
		);
	}

	set_transient( $key, $results, ADZWORLD_EBAY_RESULT_TTL );

	return $results;
}

/**
 * Append eBay's products to the shared result set.
 *
 * @param array<int,array<string,string>> $results Rows from earlier providers.
 * @param string                          $request Reader's request.
 * @return array<int,array<string,string>> Rows with eBay's appended.
 */
function adzworld_ebay_append_product_results( $results, $request ) {
	foreach ( adzworld_ebay_search( $request ) as $row ) {
		$row['provider'] = 'ebay';
		$results[]       = $row;
	}

	return $results;
}

add_filter( 'adzworld_affiliate_product_results', 'adzworld_ebay_append_product_results', 10, 2 );
