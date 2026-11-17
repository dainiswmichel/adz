<?php
/**
 * AvantLink ProductSearch: real products for a reader's request.
 *
 * AvantLink's ProductSearch module is documented as "Affiliate access, no
 * authentication required", meaning it needs no API key of its own: the
 * publisher's affiliate id and website id are the whole credential. It also
 * returns a Buy URL per product, which is the tracked click-through built
 * from that affiliate id, so a product earns without any per-merchant key.
 *
 * A publisher still has to be accepted into AvantLink. What they do not have
 * to do is apply for API access separately, or wait on a per-advertiser API
 * credential, which is where every other provider here stalls.
 *
 * Results come back tab-delimited with the columns named in the request, so
 * they are read by position. The alternative, XML, names its elements in a
 * way the published documentation does not show, and guessing at element
 * names is how a fetcher silently returns nothing.
 *
 * Documented limits: 3600 requests an hour, 15000 a day.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/** How long a result set is kept, matching the other providers. */
const ADZWORLD_AVANTLINK_RESULT_TTL = 900;

/** Products requested per search. */
const ADZWORLD_AVANTLINK_LIMIT = 3;

/**
 * The columns requested, in order. Results are read by this order.
 *
 * @return array<int,string> Field names as AvantLink spells them.
 */
function adzworld_avantlink_fields() {
	return array(
		'Product Name',
		'Retail Price',
		'Sale Price',
		'Medium Image',
		'Buy URL',
		'Merchant Name',
	);
}

/**
 * Add the AvantLink credential fields to the shared account screen.
 *
 * @param array<string,array<string,mixed>> $networks Affiliate providers.
 * @return array<string,array<string,mixed>> Affiliate providers.
 */
function adzworld_avantlink_register_network( $networks ) {
	$networks['avantlink'] = array(
		'label'   => 'AvantLink',
		'account' => 'https://www.avantlink.com/',
		'note'    => __( 'The affiliate id and website id are both in your AvantLink account; ProductSearch needs no separate API key. AvantLink builds the tracked click-through itself, so products earn as soon as these two values are saved.', 'adz-world' ),
		'fields'  => array(
			'affiliate_id' => array(
				'label'   => __( 'Affiliate id', 'adz-world' ),
				'pattern' => '/^[0-9]{1,12}$/D',
			),
			'website_id'   => array(
				'label'   => __( 'Website id', 'adz-world' ),
				'pattern' => '/^[0-9]{1,12}$/D',
			),
		),
	);

	return $networks;
}

/**
 * Add the AvantLink procedure to the registration desk.
 *
 * @param array<string,array<string,mixed>> $catalog Registration procedures.
 * @return array<string,array<string,mixed>> Registration procedures.
 */
function adzworld_avantlink_register_catalog( $catalog ) {
	$catalog['avantlink'] = array(
		'label'        => 'AvantLink',
		'group'        => 'affiliate',
		'signup'       => 'https://www.avantlink.com/',
		'signup_label' => __( 'Apply to AvantLink', 'adz-world' ),
		'pays'         => __( 'The affiliate id pays this site, through the Buy URL AvantLink returns with each product.', 'adz-world' ),
		'steps'        => array(
			__( 'Apply as an affiliate partner at avantlink.com and add this site to the account.', 'adz-world' ),
			__( 'Copy the affiliate id and the website id from the account. ProductSearch needs no separate API key.', 'adz-world' ),
			__( 'Paste both ids here, enable the account, and save.', 'adz-world' ),
			__( 'Join the merchant programmes you want in AvantLink. Products from programmes you have joined are the ones this site can earn on.', 'adz-world' ),
		),
	);

	return $catalog;
}

/**
 * Mark AvantLink as a provider that receives the reader's request.
 *
 * @param array<string,bool> $providers Provider request behavior.
 * @return array<string,bool> Provider request behavior.
 */
function adzworld_avantlink_register_request_behavior( $providers ) {
	$providers['avantlink'] = true;

	return $providers;
}

/**
 * The saved AvantLink account, or an empty array when it cannot serve.
 *
 * @return array<string,string> Account fields, or empty.
 */
function adzworld_avantlink_account() {
	$accounts = function_exists( 'adzworld_affiliate_accounts' ) ? adzworld_affiliate_accounts() : array();
	$account  = isset( $accounts['avantlink'] ) && is_array( $accounts['avantlink'] ) ? $accounts['avantlink'] : array();

	if ( empty( $account['enabled'] ) || '1' !== (string) $account['enabled'] ) {
		return array();
	}

	if ( empty( $account['affiliate_id'] ) || empty( $account['website_id'] ) ) {
		return array();
	}

	return $account;
}

/**
 * Supply AvantLink readiness to the shared connection check.
 *
 * @param bool                 $ready   Existing readiness.
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool
 */
function adzworld_avantlink_filter_account_ready( $ready, $id, $account ) {
	if ( 'avantlink' !== $id ) {
		return $ready;
	}

	return ! empty( $account['enabled'] ) && '1' === (string) $account['enabled']
		&& ! empty( $account['affiliate_id'] ) && ! empty( $account['website_id'] );
}

/**
 * Turn a reader's request into an AvantLink search term.
 *
 * AvantLink's advanced syntax excludes a word when it is prefixed with "-",
 * which is what a refinement like "no pedals" means. Letting their search
 * engine apply the exclusion is both better than matching substrings here and
 * the reason this plugin does not need to parse the phrase itself.
 *
 * @param string $request Reader's request.
 * @return string Search term.
 */
function adzworld_avantlink_search_term( $request ) {
	$request = trim( wp_strip_all_tags( (string) $request ) );

	if ( '' === $request ) {
		return '';
	}

	$words = preg_split( '/\s+/', $request, -1, PREG_SPLIT_NO_EMPTY );

	if ( ! is_array( $words ) ) {
		return $request;
	}

	$term   = array();
	$negate = false;

	foreach ( $words as $word ) {
		if ( in_array( strtolower( $word ), array( 'no', 'not', 'without', 'exclude' ), true ) ) {
			$negate = true;
			continue;
		}

		$term[] = $negate ? '-' . $word : $word;

		/* One word is negated, not the rest of the phrase. */
		$negate = false;
	}

	return implode( ' ', $term );
}

/**
 * Products for one request string.
 *
 * @param string $request Reader's request.
 * @param int    $limit   Results wanted.
 * @return array<int,array<string,string>> Results, each with title, image, price and url.
 */
function adzworld_avantlink_search( $request, $limit = ADZWORLD_AVANTLINK_LIMIT ) {
	$account = adzworld_avantlink_account();
	$term    = adzworld_avantlink_search_term( $request );
	$limit   = max( 1, min( 10, (int) $limit ) );

	if ( empty( $account ) || '' === $term ) {
		return array();
	}

	$key    = 'adzworld_avantlink_' . md5( $account['affiliate_id'] . '|' . $account['website_id'] . '|' . $term . '|' . $limit );
	$cached = get_transient( $key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$response = wp_remote_get(
		add_query_arg(
			array(
				'module'                 => 'ProductSearch',
				'affiliate_id'           => rawurlencode( $account['affiliate_id'] ),
				'website_id'             => rawurlencode( $account['website_id'] ),
				'search_term'            => rawurlencode( $term ),
				'search_results_count'   => $limit,
				'search_results_fields'  => rawurlencode( implode( '|', adzworld_avantlink_fields() ) ),

				/*
				 * "precise" keeps only the matches covering the most keywords
				 * from the request, which is what a refined request is for.
				 * "interleave" spreads the results across merchants rather
				 * than returning one shop's catalogue.
				 */
				'search_results_options' => rawurlencode( 'precise|interleave' ),
				'search_advanced_syntax' => 1,
				'output'                 => 'tab',
			),
			'https://classic.avantlink.com/api.php'
		),
		array( 'timeout' => 8 )
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return array();
	}

	$results = adzworld_avantlink_parse( wp_remote_retrieve_body( $response ) );

	set_transient( $key, $results, ADZWORLD_AVANTLINK_RESULT_TTL );

	return $results;
}

/**
 * Read the tab-delimited body into rows.
 *
 * Columns arrive in the order adzworld_avantlink_fields() asked for them, so
 * they are read by position. A header row is emitted for some output modes
 * and is recognised by its first cell rather than assumed away.
 *
 * @param string $body Response body.
 * @return array<int,array<string,string>> Rows.
 */
function adzworld_avantlink_parse( $body ) {
	$body = (string) $body;

	if ( '' === trim( $body ) ) {
		return array();
	}

	$fields  = adzworld_avantlink_fields();
	$results = array();

	foreach ( preg_split( '/\r\n|\r|\n/', $body ) as $line ) {
		if ( '' === trim( $line ) ) {
			continue;
		}

		$cells = explode( "\t", $line );

		/* The header row names the first column; it is not a product. */
		if ( isset( $cells[0] ) && 0 === strcasecmp( trim( $cells[0] ), $fields[0] ) ) {
			continue;
		}

		if ( count( $cells ) < count( $fields ) ) {
			continue;
		}

		$row = array_combine( $fields, array_slice( $cells, 0, count( $fields ) ) );
		$row = array_map( 'trim', $row );

		if ( '' === $row['Product Name'] || '' === $row['Buy URL'] ) {
			continue;
		}

		/* The sale price is what a reader would pay, when there is one. */
		$price = '' !== $row['Sale Price'] && 0 < (float) $row['Sale Price'] ? $row['Sale Price'] : $row['Retail Price'];

		$results[] = array(
			'title' => $row['Product Name'],
			'image' => $row['Medium Image'],
			'price' => '' === $price ? '' : '$' . $price,
			'url'   => $row['Buy URL'],
		);
	}

	return $results;
}

/**
 * Append AvantLink's products to the shared result set.
 *
 * @param array<int,array<string,string>> $results Rows from earlier providers.
 * @param string                          $request Reader's request.
 * @return array<int,array<string,string>> Rows with AvantLink's appended.
 */
function adzworld_avantlink_append_product_results( $results, $request ) {
	foreach ( adzworld_avantlink_search( $request ) as $row ) {
		$row['provider'] = 'avantlink';
		$results[]       = $row;
	}

	return $results;
}

add_filter( 'adzworld_affiliate_networks', 'adzworld_avantlink_register_network' );
add_filter( 'adzworld_registration_catalog', 'adzworld_avantlink_register_catalog' );
add_filter( 'adzworld_getpaid_affiliate_serves_adz', 'adzworld_avantlink_register_request_behavior' );
add_filter( 'adzworld_affiliate_account_ready', 'adzworld_avantlink_filter_account_ready', 10, 3 );
add_filter( 'adzworld_affiliate_product_results', 'adzworld_avantlink_append_product_results', 10, 2 );

/**
 * Whether this account is credited for a sale.
 *
 * AvantLink builds the tracked Buy URL from the same affiliate id it needs to
 * return products at all, so an AvantLink account that can serve can also
 * earn. It is the only provider here for which those are one credential.
 *
 * @param bool                 $earns   Whether the account earns.
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool
 */
function adzworld_avantlink_filter_account_earns( $earns, $id, $account ) {
	if ( 'avantlink' !== $id ) {
		return $earns;
	}

	return ! empty( $account['affiliate_id'] ) && ! empty( $account['website_id'] );
}

add_filter( 'adzworld_affiliate_account_earns', 'adzworld_avantlink_filter_account_earns', 10, 3 );
