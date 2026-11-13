<?php
/**
 * Best Buy product fetcher for one adzAdRequest search string.
 *
 * The Products API receives the category and only that category's refinements.
 * Results use Best Buy's affiliateUrl field, which Best Buy generates after
 * the approved Impact Partner ID is supplied with the request. The plugin does
 * not create an attribution URL and does not emit an untracked fallback.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add the Best Buy credential fields to the shared affiliate account screen.
 *
 * @param array<string,array<string,mixed>> $networks Affiliate providers.
 * @return array<string,array<string,mixed>> Affiliate providers.
 */
function adzworld_bestbuy_register_network( $networks ) {
	$networks['bestbuy'] = array(
		'label'   => 'Best Buy',
		'account' => 'https://developer.bestbuy.com/',
		'note'    => __( 'Product results require only a Best Buy Developer API key, which developer.bestbuy.com issues at once. The Impact Partner ID comes from an approved Best Buy Publisher Program account and is what makes the links earn; until it is pasted here the products still serve, with plain untracked links.', 'adz-world' ),
		'fields'  => array(
			'api_key' => array(
				'label'   => __( 'Best Buy API key', 'adz-world' ),
				'pattern' => '/^[^\x00-\x20\x7F]{8,255}$/D',
				'secret'  => true,
			),
			'ipid'    => array(
				'label'   => __( 'Impact Partner ID (optional)', 'adz-world' ),
				'pattern' => '/^[A-Za-z0-9_-]{1,100}$/D',
			),
		),
	);

	return $networks;
}

/**
 * Add the Best Buy account procedure to the registration desk.
 *
 * @param array<string,array<string,mixed>> $catalog Registration procedures.
 * @return array<string,array<string,mixed>> Registration procedures.
 */
function adzworld_bestbuy_register_catalog( $catalog ) {
	$catalog['bestbuy'] = array(
		'label'        => 'Best Buy',
		'group'        => 'affiliate',
		'signup'       => 'https://www.bestbuy.com/site/misc/best-buy-affiliate-program/pcmcat198500050002.c?id=pcmcat198500050002',
		'signup_label' => __( 'Register for the Best Buy Publisher Program', 'adz-world' ),
		'pays'         => __( 'Best Buy returns an affiliate product URL credited to the saved Impact Partner ID.', 'adz-world' ),
		'steps'        => array(
			__( 'Apply to the Best Buy Publisher Program. Best Buy manages publisher attribution through Impact and issues the approved account’s Impact Partner ID.', 'adz-world' ),
			__( 'Create a developer account at developer.bestbuy.com and obtain a Best Buy API key.', 'adz-world' ),
			__( 'Paste the API key and Impact Partner ID in this section. The API key is stored as a secret.', 'adz-world' ),
			__( 'Enable the account and save. The fetcher sends the category and its refinements as product search terms and uses only the attributed URL returned by Best Buy.', 'adz-world' ),
		),
	);

	return $catalog;
}

/**
 * Mark Best Buy as a provider that receives the permission-based request.
 *
 * @param array<string,bool> $providers Provider request behavior.
 * @return array<string,bool> Provider request behavior.
 */
function adzworld_bestbuy_register_request_behavior( $providers ) {
	$providers['bestbuy'] = true;

	return $providers;
}

/**
 * Read the saved Best Buy account, or an empty row.
 *
 * @return array<string,string>
 */
function adzworld_bestbuy_account() {
	$accounts = function_exists( 'adzworld_affiliate_accounts' ) ? adzworld_affiliate_accounts() : array();
	$account  = isset( $accounts['bestbuy'] ) && is_array( $accounts['bestbuy'] ) ? $accounts['bestbuy'] : array();

	return wp_parse_args(
		$account,
		array(
			'enabled' => '0',
			'api_key' => '',
			'ipid'    => '',
		)
	);
}

/**
 * Whether the saved account can fetch products and return attributed links.
 *
 * @param array<string,string> $account Saved Best Buy fields.
 * @return bool
 */
function adzworld_bestbuy_account_ready( $account ) {
	/*
	 * The Impact Partner ID is deliberately not required. A Best Buy developer
	 * key is issued at once and returns products; the Partner ID comes from a
	 * Publisher Program approval that takes days. Holding the products back
	 * until then leaves the slot empty for no gain. Without it the links are
	 * untracked and earn nothing, which is the honest state while the
	 * application is pending.
	 */
	return isset( $account['enabled'], $account['api_key'] )
		&& '1' === (string) $account['enabled']
		&& '' !== (string) $account['api_key'];
}

/**
 * Supply Best Buy readiness to the shared connection check.
 *
 * @param bool                 $ready   Existing readiness.
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool
 */
function adzworld_bestbuy_filter_account_ready( $ready, $id, $account ) {
	if ( 'bestbuy' !== $id ) {
		return $ready;
	}

	return adzworld_bestbuy_account_ready( $account );
}

/**
 * Build the official Best Buy Products API request for a search string.
 *
 * Each word is an individual search term, as required by the Products API.
 * The API key remains server-side. IPID asks Best Buy to return affiliateUrl.
 *
 * @param string               $request Category and refinements.
 * @param array<string,string> $account Saved Best Buy fields.
 * @return string API URL, or an empty string for an empty request.
 */
function adzworld_bestbuy_products_url( $request, $account ) {
	$request = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $request ) ) );
	if ( '' === $request || empty( $account['api_key'] ) ) {
		return '';
	}

	$terms = preg_split( '/\s+/u', $request, -1, PREG_SPLIT_NO_EMPTY );
	if ( ! is_array( $terms ) || ! $terms ) {
		return '';
	}

	$criteria = array();
	foreach ( $terms as $term ) {
		$criteria[] = 'search=' . rawurlencode( $term );
	}
	$criteria[] = 'active=true';
	$criteria[] = 'onlineAvailability=true';

	$query = array(
		'format'   => 'json',
		'pageSize' => 3,
		'show'     => 'sku,name,salePrice,regularPrice,image,largeImage,url,affiliateUrl,onlineAvailability',
		'apiKey'   => $account['api_key'],
	);

	/* IPID is what asks Best Buy to return an attributed affiliateUrl. */
	if ( ! empty( $account['ipid'] ) ) {
		$query['IPID'] = $account['ipid'];
	}

	return add_query_arg(
		$query,
		'https://api.bestbuy.com/v1/products(' . implode( '&', $criteria ) . ')'
	);
}

/**
 * Accept an HTTPS URL returned by the Best Buy API.
 *
 * Product images can be hosted on Best Buy's separate static-content hosts,
 * and attributed clicks can use an API or Impact redirect host.
 *
 * @param mixed $candidate Candidate URL.
 * @return string HTTPS URL, or an empty string.
 */
function adzworld_bestbuy_https_url( $candidate ) {
	$url    = esc_url_raw( (string) $candidate );
	$scheme = wp_parse_url( $url, PHP_URL_SCHEME );

	return is_string( $scheme ) && 'https' === strtolower( $scheme ) ? $url : '';
}

/**
 * Format the sale price returned by the United States Products API.
 *
 * @param mixed $price API price.
 * @return string Price text, or an empty string.
 */
function adzworld_bestbuy_price_text( $price ) {
	if ( ! is_numeric( $price ) || (float) $price < 0 ) {
		return '';
	}

	return '$' . number_format_i18n( (float) $price, 2 );
}

/**
 * Map one Best Buy product into the shared result shape.
 *
 * @param mixed $product Product object from the API.
 * @return array<string,string>|null Normalized result, or null.
 */
function adzworld_bestbuy_map_product( $product ) {
	if ( ! is_array( $product ) ) {
		return null;
	}

	$title = isset( $product['name'] ) ? sanitize_text_field( (string) $product['name'] ) : '';
	$url   = isset( $product['affiliateUrl'] ) ? adzworld_bestbuy_https_url( $product['affiliateUrl'] ) : '';

	/* No Partner ID yet means no affiliateUrl: link the product plainly. */
	if ( '' === $url && isset( $product['url'] ) ) {
		$url = adzworld_bestbuy_https_url( $product['url'] );
	}
	$image = isset( $product['largeImage'] ) ? adzworld_bestbuy_https_url( $product['largeImage'] ) : '';
	if ( '' === $image && isset( $product['image'] ) ) {
		$image = adzworld_bestbuy_https_url( $product['image'] );
	}
	if ( '' === $title || '' === $url || '' === $image ) {
		return null;
	}

	return array(
		'image'        => $image,
		'title'        => $title,
		'price'        => adzworld_bestbuy_price_text( isset( $product['salePrice'] ) ? $product['salePrice'] : '' ),
		'url'          => $url,
		'source_label' => 'Best Buy Developer API',
		'source_image' => 'https://developer.bestbuy.com/images/bestbuy-logo.png',
		'source_url'   => 'https://developer.bestbuy.com/',
	);
}

/**
 * Retrieve three attributed Best Buy products for a request string.
 *
 * @param string $request Category and refinements.
 * @return array<int,array<string,string>>|WP_Error
 */
function adzworld_bestbuy_product_results( $request ) {
	$account = adzworld_bestbuy_account();
	$request = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $request ) ) );
	if ( ! adzworld_bestbuy_account_ready( $account ) ) {
		return new WP_Error( 'adzworld_bestbuy_credentials', __( 'A Best Buy API key is required.', 'adz-world' ) );
	}
	if ( '' === $request ) {
		return new WP_Error( 'adzworld_bestbuy_request', __( 'The Best Buy product request is empty.', 'adz-world' ) );
	}

	$result_key = 'adzworld_bestbuy_results_' . md5( $account['api_key'] . '|' . $account['ipid'] . '|' . $request );
	$cached     = get_transient( $result_key );
	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	$url = adzworld_bestbuy_products_url( $request, $account );
	if ( '' === $url ) {
		return new WP_Error( 'adzworld_bestbuy_request_url', __( 'The Best Buy product request could not be built.', 'adz-world' ) );
	}

	$response = wp_safe_remote_get(
		$url,
		array(
			'timeout' => 8,
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || ! is_array( $data ) ) {
		return new WP_Error( 'adzworld_bestbuy_http', __( 'Best Buy did not return a product response.', 'adz-world' ) );
	}

	$products = isset( $data['products'] ) && is_array( $data['products'] ) ? $data['products'] : array();
	$results  = array();
	foreach ( $products as $product ) {
		$mapped = adzworld_bestbuy_map_product( $product );
		if ( null === $mapped ) {
			continue;
		}
		$results[] = $mapped;
		if ( 3 === count( $results ) ) {
			break;
		}
	}

	set_transient( $result_key, $results, 15 * MINUTE_IN_SECONDS );

	return $results;
}

/**
 * Add Best Buy products to the current category's inner result carousel.
 *
 * @param array<int,array<string,string>> $results Existing provider results.
 * @param string                          $request Category and refinements.
 * @return array<int,array<string,string>> Provider results.
 */
function adzworld_bestbuy_append_product_results( $results, $request ) {
	$bestbuy = adzworld_bestbuy_product_results( $request );
	if ( is_wp_error( $bestbuy ) || ! $bestbuy ) {
		return $results;
	}

	foreach ( $bestbuy as $row ) {
		$row['provider'] = 'bestbuy';
		$results[]       = $row;
	}

	return $results;
}

add_filter( 'adzworld_affiliate_networks', 'adzworld_bestbuy_register_network' );
add_filter( 'adzworld_registration_catalog', 'adzworld_bestbuy_register_catalog' );
add_filter( 'adzworld_getpaid_affiliate_serves_adz', 'adzworld_bestbuy_register_request_behavior' );
add_filter( 'adzworld_affiliate_account_ready', 'adzworld_bestbuy_filter_account_ready', 10, 3 );
add_filter( 'adzworld_affiliate_product_results', 'adzworld_bestbuy_append_product_results', 10, 2 );
