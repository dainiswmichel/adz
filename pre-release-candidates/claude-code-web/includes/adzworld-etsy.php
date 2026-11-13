<?php
/**
 * Etsy listing fetcher for one adzAdRequest search string.
 *
 * The search string is the category plus the refinements the visitor stated.
 * Keywords on findAllListingsActive are that string. The visitor's rating and
 * the ad already served stay off the keyword. Listing images, titles, and
 * prices come from Etsy Open API v3. The click is the Rakuten deep link this
 * plugin already builds, using the publisher site id and advertiser id the
 * administrator saved. No identifier is invented. An incomplete account
 * returns no listings.
 *
 * Etsy states that the affiliate programme is moving from Awin to Rakuten:
 * https://help.etsy.com/hc/en-us/articles/360000335987-The-Affiliate-Program
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read the saved Etsy account, or an empty row.
 *
 * @return array<string,string>
 */
function adzworld_etsy_account() {
	$accounts = function_exists( 'adzworld_affiliate_accounts' ) ? adzworld_affiliate_accounts() : array();
	$account  = isset( $accounts['etsy'] ) && is_array( $accounts['etsy'] ) ? $accounts['etsy'] : array();

	return wp_parse_args(
		$account,
		array(
			'enabled'       => '0',
			'keystring'     => '',
			'shared_secret' => '',
			'id'            => '',
			'mid'           => '',
		)
	);
}

/**
 * Whether the saved account can call Etsy and emit a tracked click.
 *
 * @param array<string,string> $account Saved Etsy fields.
 * @return bool
 */
function adzworld_etsy_account_ready( $account ) {
	foreach ( array( 'keystring', 'shared_secret', 'id', 'mid' ) as $field ) {
		if ( empty( $account[ $field ] ) ) {
			return false;
		}
	}

	return isset( $account['enabled'] ) && '1' === (string) $account['enabled'];
}

/**
 * Accept an Etsy https URL whose path is a listing or a search.
 *
 * @param string $url Candidate URL.
 * @return string URL, or an empty string.
 */
function adzworld_etsy_page_url( $url ) {
	$url  = esc_url_raw( (string) $url );
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $host ) || ! is_string( $path ) ) {
		return '';
	}
	$host = strtolower( $host );
	if ( 'etsy.com' !== $host && '.etsy.com' !== substr( $host, -9 ) ) {
		return '';
	}
	if ( 0 !== strpos( $path, '/listing/' ) && 0 !== strpos( $path, '/search' ) ) {
		return '';
	}

	return $url;
}

/**
 * Accept an Etsy-hosted listing image URL.
 *
 * @param string $url Candidate image URL.
 * @return string URL, or an empty string.
 */
function adzworld_etsy_image_url( $url ) {
	$url  = esc_url_raw( (string) $url );
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! is_string( $host ) ) {
		return '';
	}
	$host = strtolower( $host );
	if ( 'etsystatic.com' !== $host && '.etsystatic.com' !== substr( $host, -15 ) ) {
		return '';
	}

	return $url;
}

/**
 * Rakuten deep link for one Etsy page.
 *
 * @param string               $destination Listing or search URL.
 * @param array<string,string> $account     Saved Etsy fields.
 * @return string Tracked URL, or an empty string.
 */
function adzworld_etsy_tracked_url( $destination, $account ) {
	$destination = adzworld_etsy_page_url( $destination );
	if ( '' === $destination || empty( $account['id'] ) || empty( $account['mid'] ) ) {
		return '';
	}

	return 'https://click.linksynergy.com/deeplink?id=' . rawurlencode( $account['id'] )
		. '&mid=' . rawurlencode( $account['mid'] )
		. '&murl=' . rawurlencode( $destination );
}

/**
 * Format an Etsy Money object as an amount and its currency code.
 *
 * @param mixed $price Price object from a listing.
 * @return string Price text, empty when the object is incomplete.
 */
function adzworld_etsy_price_text( $price ) {
	if ( ! is_array( $price ) || ! isset( $price['amount'] ) || ! is_numeric( $price['amount'] ) ) {
		return '';
	}
	$divisor  = isset( $price['divisor'] ) ? (int) $price['divisor'] : 0;
	$currency = isset( $price['currency_code'] ) ? (string) $price['currency_code'] : '';
	if ( $divisor < 1 || 1 !== preg_match( '/^[A-Z]{3}$/D', $currency ) ) {
		return '';
	}
	$decimals = 1 === $divisor ? 0 : strlen( (string) $divisor ) - 1;

	return number_format( ( (float) $price['amount'] ) / $divisor, $decimals, '.', '' ) . ' ' . $currency;
}

/**
 * First usable image URL on a listing or image-list payload.
 *
 * @param array<string,mixed> $source Listing or image resource.
 * @return string Image URL, or an empty string.
 */
function adzworld_etsy_image_from_payload( $source ) {
	$images = array();
	if ( isset( $source['images'] ) && is_array( $source['images'] ) ) {
		$images = $source['images'];
	} elseif ( isset( $source['results'] ) && is_array( $source['results'] ) ) {
		$images = $source['results'];
	}
	foreach ( $images as $image ) {
		if ( ! is_array( $image ) ) {
			continue;
		}
		foreach ( array( 'url_570xN', 'url_fullxfull', 'url_170x135' ) as $field ) {
			$url = isset( $image[ $field ] ) ? adzworld_etsy_image_url( $image[ $field ] ) : '';
			if ( '' !== $url ) {
				return $url;
			}
		}
	}

	return '';
}

/**
 * Map one Etsy listing into the shared result shape.
 *
 * @param mixed                $listing Listing object.
 * @param array<string,string> $account Saved Etsy fields.
 * @return array{image:string,title:string,price:string,url:string,listing_id:string}|null
 */
function adzworld_etsy_map_listing( $listing, $account ) {
	if ( ! is_array( $listing ) ) {
		return null;
	}
	$listing_id = isset( $listing['listing_id'] ) ? (string) $listing['listing_id'] : '';
	if ( 1 !== preg_match( '/^[1-9][0-9]{0,18}$/D', $listing_id ) ) {
		return null;
	}
	$page = isset( $listing['url'] ) ? adzworld_etsy_page_url( $listing['url'] ) : '';
	if ( '' === $page ) {
		$page = adzworld_etsy_page_url( 'https://www.etsy.com/listing/' . $listing_id );
	}
	$url   = adzworld_etsy_tracked_url( $page, $account );
	$title = isset( $listing['title'] ) ? sanitize_text_field( (string) $listing['title'] ) : '';
	if ( '' === $url || '' === $title ) {
		return null;
	}

	return array(
		'image'      => adzworld_etsy_image_from_payload( $listing ),
		'title'      => $title,
		'price'      => adzworld_etsy_price_text( isset( $listing['price'] ) ? $listing['price'] : array() ),
		'url'        => $url,
		'listing_id' => $listing_id,
	);
}

/**
 * GET one Etsy Open API URL with the saved key.
 *
 * @param string               $url     Absolute API URL.
 * @param array<string,string> $account Saved Etsy fields.
 * @return array<string,mixed>|WP_Error Decoded JSON object, or an error.
 */
function adzworld_etsy_api_get( $url, $account ) {
	$response = wp_safe_remote_get(
		$url,
		array(
			'timeout' => 8,
			'headers' => array(
				'x-api-key' => $account['keystring'] . ':' . $account['shared_secret'],
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || ! is_array( $data ) ) {
		return new WP_Error( 'adzworld_etsy_http', __( 'Etsy did not return a listing response.', 'adz-world' ) );
	}

	return $data;
}

/**
 * Retrieve three tracked Etsy listings for a request string.
 *
 * @param string $request Category and refinements.
 * @return array<int,array{image:string,title:string,price:string,url:string}>|WP_Error
 */
function adzworld_etsy_product_results( $request ) {
	$account = adzworld_etsy_account();
	$request = trim( wp_strip_all_tags( (string) $request ) );
	if ( ! adzworld_etsy_account_ready( $account ) ) {
		return new WP_Error( 'adzworld_etsy_credentials', __( 'Etsy API credentials are incomplete.', 'adz-world' ) );
	}
	if ( '' === $request ) {
		return new WP_Error( 'adzworld_etsy_request', __( 'The Etsy listing request is empty.', 'adz-world' ) );
	}
	if ( strlen( $request ) > 2000 ) {
		return new WP_Error( 'adzworld_etsy_request_length', __( 'The Etsy listing request exceeds the length this endpoint accepts.', 'adz-world' ) );
	}

	$result_key = 'adzworld_etsy_results_' . md5( $account['keystring'] . '|' . $account['mid'] . '|' . $request );
	$cached     = get_transient( $result_key );
	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	$data = adzworld_etsy_api_get(
		add_query_arg(
			array(
				'keywords' => $request,
				'limit'    => 3,
				'sort_on'  => 'score',
			),
			'https://api.etsy.com/v3/application/listings/active'
		),
		$account
	);
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$listings = isset( $data['results'] ) && is_array( $data['results'] ) ? $data['results'] : array();
	$results  = array();
	foreach ( $listings as $listing ) {
		$mapped = adzworld_etsy_map_listing( $listing, $account );
		if ( null === $mapped ) {
			continue;
		}
		if ( '' === $mapped['image'] ) {
			$images = adzworld_etsy_api_get(
				'https://api.etsy.com/v3/application/listings/' . $mapped['listing_id'] . '/images',
				$account
			);
			if ( ! is_wp_error( $images ) ) {
				$mapped['image'] = adzworld_etsy_image_from_payload( $images );
			}
		}
		if ( '' === $mapped['image'] ) {
			continue;
		}
		unset( $mapped['listing_id'] );
		$results[] = $mapped;
		if ( 3 === count( $results ) ) {
			break;
		}
	}

	set_transient( $result_key, $results, 15 * MINUTE_IN_SECONDS );
	return $results;
}

/**
 * Add the Etsy credential fields to the shared affiliate account screen.
 *
 * These were written directly into the core networks array in the candidate
 * this fetcher came from. They are registered through the filter here so the
 * core array stays the same for every provider.
 *
 * @param array<string,array<string,mixed>> $networks Affiliate providers.
 * @return array<string,array<string,mixed>> Affiliate providers.
 */
function adzworld_etsy_register_network( $networks ) {
	$networks['etsy'] = array(
		'label'   => 'Etsy',
		'account' => 'https://www.etsy.com/developers/your-apps',
		'note'    => __( 'The keystring and shared secret come from an Etsy app. Since 9 February 2026 the x-api-key header is keystring:shared_secret. The fetcher sends the request string as the listing keywords. Etsy’s affiliate programme is moving from Awin to Rakuten, so id is the Rakuten publisher site id and mid is the Etsy advertiser id from the programme you joined. Neither id is supplied by this plugin.', 'adz-world' ),
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
	);

	return $networks;
}

/**
 * Add the Etsy account procedure to the registration desk.
 *
 * @param array<string,array<string,mixed>> $catalog Registration procedures.
 * @return array<string,array<string,mixed>> Registration procedures.
 */
function adzworld_etsy_register_catalog( $catalog ) {
	$catalog['etsy'] = array(
		'label'        => 'Etsy',
		'group'        => 'affiliate',
		'signup'       => 'https://www.etsy.com/developers/your-apps',
		'signup_label' => __( 'Open your Etsy apps', 'adz-world' ),
		'pays'         => __( 'The Rakuten publisher site id pays this site.', 'adz-world' ),
		'steps'        => array(
			__( 'Create an Etsy app and copy its keystring and shared secret. The shared secret is stored as a secret.', 'adz-world' ),
			__( 'Join the Etsy programme on Rakuten Advertising. Etsy is moving the affiliate programme from Awin to Rakuten.', 'adz-world' ),
			__( 'Copy your Rakuten publisher site id and the Etsy advertiser id from that programme.', 'adz-world' ),
			__( 'Paste the four values in this section, enable the account, and save. The fetcher sends the category and its refinements as the listing keywords.', 'adz-world' ),
		),
	);

	return $catalog;
}

/**
 * Mark Etsy as a provider that receives the reader's request.
 *
 * @param array<string,bool> $providers Provider request behavior.
 * @return array<string,bool> Provider request behavior.
 */
function adzworld_etsy_register_request_behavior( $providers ) {
	$providers['etsy'] = true;

	return $providers;
}

/**
 * Append Etsy's listings to the shared result set.
 *
 * @param array<int,array<string,string>> $results Rows from earlier providers.
 * @param string                          $request Reader's request.
 * @return array<int,array<string,string>> Rows with Etsy's appended.
 */
function adzworld_etsy_append_product_results( $results, $request ) {
	$listings = adzworld_etsy_product_results( $request );

	if ( is_wp_error( $listings ) || ! is_array( $listings ) ) {
		return $results;
	}

	foreach ( $listings as $row ) {
		$row['provider'] = 'etsy';
		$results[]       = $row;
	}

	return $results;
}

/**
 * Supply Etsy readiness to the shared connection check.
 *
 * @param bool                 $ready   Existing readiness.
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool
 */
function adzworld_etsy_filter_account_ready( $ready, $id, $account ) {
	if ( 'etsy' !== $id ) {
		return $ready;
	}

	return adzworld_etsy_account_ready( $account );
}

add_filter( 'adzworld_affiliate_account_ready', 'adzworld_etsy_filter_account_ready', 10, 3 );
add_filter( 'adzworld_affiliate_networks', 'adzworld_etsy_register_network' );
add_filter( 'adzworld_registration_catalog', 'adzworld_etsy_register_catalog' );
add_filter( 'adzworld_getpaid_affiliate_serves_adz', 'adzworld_etsy_register_request_behavior' );
add_filter( 'adzworld_affiliate_product_results', 'adzworld_etsy_append_product_results', 10, 2 );
