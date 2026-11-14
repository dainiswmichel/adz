<?php
/**
 * Travelpayouts: flight prices for a reader's own words.
 *
 * This is the provider where a refinement is most visibly doing something,
 * because each refinement becomes an explicit parameter rather than a hint:
 *
 *   "lisbon"            resolves to the IATA code LIS and becomes destination
 *   "in march"          becomes depart_date=YYYY-03
 *   "direct"            switches the endpoint from prices/cheap to
 *                       prices/direct, which returns non-stop tickets only
 *   "under 300"         drops any fare above that price
 *
 * The city lookup is Travelpayouts' own places endpoint, which needs no
 * credential. The price endpoints need the affiliate token, which the free
 * Travelpayouts account issues immediately in the profile.
 *
 * Not the real-time Flight Search API, which Travelpayouts grants only to
 * projects above 50,000 monthly active users. The Data API used here is
 * cached price data, which is why a fare carries an expiry and why a stale
 * one is dropped rather than shown.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/** How long a fare set is kept. Data API prices are cached upstream anyway. */
const ADZWORLD_TRAVELPAYOUTS_RESULT_TTL = 1800;

/** A city's IATA code does not change, so it is kept for a week. */
const ADZWORLD_TRAVELPAYOUTS_PLACE_TTL = 604800;

/** Fares shown per request. */
const ADZWORLD_TRAVELPAYOUTS_LIMIT = 3;

/**
 * Add the Travelpayouts fields to the shared account screen.
 *
 * @param array<string,array<string,mixed>> $networks Affiliate providers.
 * @return array<string,array<string,mixed>> Affiliate providers.
 */
function adzworld_travelpayouts_register_network( $networks ) {
	$networks['travelpayouts'] = array(
		'label'   => 'Travelpayouts',
		'account' => 'https://www.travelpayouts.com/developers/api',
		'note'    => __( 'The API token is issued immediately in a free Travelpayouts profile. Origin is the airport your readers fly from, as an IATA code such as RIX or ATL. The marker is your affiliate id and is what credits a booking to you.', 'adz-world' ),
		'fields'  => array(
			'token'    => array(
				'label'   => __( 'API token', 'adz-world' ),
				'pattern' => '/^[A-Za-z0-9]{16,64}$/D',
				'secret'  => true,
			),
			'origin'   => array(
				'label'   => __( 'Origin airport (IATA)', 'adz-world' ),
				'pattern' => '/^[A-Za-z]{3}$/D',
			),
			'marker'   => array(
				'label'   => __( 'Affiliate marker', 'adz-world' ),
				'pattern' => '/^[A-Za-z0-9_.-]{1,32}$/D',
			),
			'currency' => array(
				'label'   => __( 'Currency', 'adz-world' ),
				'choices' => array( 'usd', 'eur', 'gbp' ),
			),
		),
	);

	return $networks;
}

/**
 * Add the Travelpayouts procedure to the registration desk.
 *
 * @param array<string,array<string,mixed>> $catalog Registration procedures.
 * @return array<string,array<string,mixed>> Registration procedures.
 */
function adzworld_travelpayouts_register_catalog( $catalog ) {
	$catalog['travelpayouts'] = array(
		'label'        => 'Travelpayouts',
		'group'        => 'affiliate',
		'signup'       => 'https://www.travelpayouts.com/',
		'signup_label' => __( 'Join Travelpayouts', 'adz-world' ),
		'pays'         => __( 'The affiliate marker pays this site.', 'adz-world' ),
		'steps'        => array(
			__( 'Join Travelpayouts. The account is free and there is no review for the Data API.', 'adz-world' ),
			__( 'Open your profile and copy the API token from the API token section.', 'adz-world' ),
			__( 'Copy your affiliate marker from the same account.', 'adz-world' ),
			__( 'Paste the token and the marker here, set the airport your readers fly from, enable the account, and save.', 'adz-world' ),
		),
	);

	return $catalog;
}

/**
 * Mark Travelpayouts as a provider that receives the reader's request.
 *
 * @param array<string,bool> $providers Provider request behavior.
 * @return array<string,bool> Provider request behavior.
 */
function adzworld_travelpayouts_register_request_behavior( $providers ) {
	$providers['travelpayouts'] = true;

	return $providers;
}

/**
 * The saved account, or an empty array when it cannot serve.
 *
 * @return array<string,string> Account fields, or empty.
 */
function adzworld_travelpayouts_account() {
	$accounts = function_exists( 'adzworld_affiliate_accounts' ) ? adzworld_affiliate_accounts() : array();
	$account  = isset( $accounts['travelpayouts'] ) && is_array( $accounts['travelpayouts'] ) ? $accounts['travelpayouts'] : array();

	if ( empty( $account['enabled'] ) || '1' !== (string) $account['enabled'] ) {
		return array();
	}

	if ( empty( $account['token'] ) || empty( $account['origin'] ) ) {
		return array();
	}

	return $account;
}

/**
 * Supply readiness to the shared connection check.
 *
 * @param bool                 $ready   Existing readiness.
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool
 */
function adzworld_travelpayouts_filter_account_ready( $ready, $id, $account ) {
	if ( 'travelpayouts' !== $id ) {
		return $ready;
	}

	return ! empty( $account['enabled'] ) && '1' === (string) $account['enabled']
		&& ! empty( $account['token'] ) && ! empty( $account['origin'] );
}

/**
 * Whether this account is credited for a booking.
 *
 * @param bool                 $earns   Whether the account earns.
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool
 */
function adzworld_travelpayouts_filter_account_earns( $earns, $id, $account ) {
	if ( 'travelpayouts' !== $id ) {
		return $earns;
	}

	return ! empty( $account['marker'] );
}

/**
 * Words that are instructions rather than a place name.
 *
 * @return array<int,string> Lowercase words.
 */
function adzworld_travelpayouts_stop_words() {
	return array(
		'flight',
		'flights',
		'fly',
		'flying',
		'ticket',
		'tickets',
		'to',
		'in',
		'on',
		'a',
		'the',
		'cheap',
		'cheapest',
		'direct',
		'nonstop',
		'non-stop',
		'stops',
		'under',
		'below',
		'less',
		'than',
		'from',
		'return',
		'trip',
	);
}

/**
 * Read a reader's request into the parameters this API actually takes.
 *
 * Every piece a reader adds maps to something explicit, which is what makes
 * the refinement visible rather than a matter of trust.
 *
 * @param string $request Reader's request.
 * @return array<string,mixed> Parsed request: place, month, direct, max_price.
 */
function adzworld_travelpayouts_parse_request( $request ) {
	$request = strtolower( trim( wp_strip_all_tags( (string) $request ) ) );

	$parsed = array(
		'place'     => '',
		'month'     => '',
		'direct'    => false,
		'max_price' => 0,
	);

	if ( '' === $request ) {
		return $parsed;
	}

	/* A non-stop request is a different endpoint, not a filter. */
	if ( preg_match( '/\b(direct|non-?stop|no stops)\b/', $request ) ) {
		$parsed['direct'] = true;
	}

	if ( preg_match( '/\b(?:under|below|less than|max(?:imum)?)\s*\$?\s*([0-9]{1,6})\b/', $request, $match ) ) {
		$parsed['max_price'] = (int) $match[1];
	}

	$months = array( 'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december' );

	foreach ( $months as $index => $name ) {
		if ( preg_match( '/\b' . $name . '\b/', $request ) ) {
			$month = $index + 1;
			$year  = (int) gmdate( 'Y' );

			/* A month already past this year means the next occurrence of it. */
			if ( $month < (int) gmdate( 'n' ) ) {
				++$year;
			}

			$parsed['month'] = sprintf( '%04d-%02d', $year, $month );
			break;
		}
	}

	/* Whatever is left, once the instructions are removed, names the place. */
	$words = preg_split( '/[^a-z]+/', $request, -1, PREG_SPLIT_NO_EMPTY );
	$words = is_array( $words ) ? $words : array();
	$keep  = array();

	foreach ( $words as $word ) {
		if ( in_array( $word, adzworld_travelpayouts_stop_words(), true ) || in_array( $word, $months, true ) ) {
			continue;
		}

		$keep[] = $word;
	}

	$parsed['place'] = implode( ' ', $keep );

	return $parsed;
}

/**
 * The IATA code for a place name, using Travelpayouts' own lookup.
 *
 * Needs no credential. Cached for a week, because a city's code does not move.
 *
 * @param string $name Place name as the reader wrote it.
 * @return string IATA code, or an empty string.
 */
function adzworld_travelpayouts_place_code( $name ) {
	$name = trim( (string) $name );

	if ( '' === $name ) {
		return '';
	}

	$key    = 'adzworld_tp_place_' . md5( strtolower( $name ) );
	$cached = get_transient( $key );

	if ( is_string( $cached ) ) {
		return $cached;
	}

	$response = wp_remote_get(
		add_query_arg(
			array(
				'term'    => rawurlencode( $name ),
				'locale'  => 'en',
				'types[]' => 'city',
			),
			'https://autocomplete.travelpayouts.com/places2'
		),
		array( 'timeout' => 8 )
	);

	$code = '';

	if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( is_array( $body ) && isset( $body[0]['code'] ) && is_string( $body[0]['code'] ) ) {
			$code = strtoupper( $body[0]['code'] );
			$code = 1 === preg_match( '/^[A-Z]{3}$/D', $code ) ? $code : '';
		}
	}

	set_transient( $key, $code, ADZWORLD_TRAVELPAYOUTS_PLACE_TTL );

	return $code;
}

/**
 * The booking link for one fare, credited to the publisher's marker.
 *
 * @param array<string,string> $account     Saved account.
 * @param string               $destination Destination IATA.
 * @param string               $depart      Departure date, or an empty string.
 * @return string Booking URL.
 */
function adzworld_travelpayouts_link( $account, $destination, $depart ) {
	$args = array(
		'origin_iata'      => strtoupper( $account['origin'] ),
		'destination_iata' => $destination,
	);

	if ( '' !== $depart ) {
		$args['depart_date'] = $depart;
	}

	if ( ! empty( $account['marker'] ) ) {
		$args['marker'] = $account['marker'];
	}

	return add_query_arg( $args, 'https://www.aviasales.com/search' );
}

/**
 * Fares for one request string.
 *
 * @param string $request Reader's request.
 * @param int    $limit   Fares wanted.
 * @return array<int,array<string,string>> Results, each with title, price and url.
 */
function adzworld_travelpayouts_search( $request, $limit = ADZWORLD_TRAVELPAYOUTS_LIMIT ) {
	$account = adzworld_travelpayouts_account();
	$limit   = max( 1, min( 10, (int) $limit ) );

	if ( empty( $account ) ) {
		return array();
	}

	$parsed = adzworld_travelpayouts_parse_request( $request );

	if ( '' === $parsed['place'] ) {
		return array();
	}

	$destination = adzworld_travelpayouts_place_code( $parsed['place'] );

	if ( '' === $destination ) {
		return array();
	}

	$origin   = strtoupper( $account['origin'] );
	$currency = ! empty( $account['currency'] ) ? $account['currency'] : 'usd';

	/* Non-stop is its own endpoint, not a parameter on the other one. */
	$endpoint = $parsed['direct'] ? 'direct' : 'cheap';

	$key    = 'adzworld_tp_' . md5( implode( '|', array( $origin, $destination, $parsed['month'], $endpoint, $currency, (string) $limit ) ) );
	$cached = get_transient( $key );

	if ( is_array( $cached ) ) {
		return adzworld_travelpayouts_apply_price_cap( $cached, $parsed['max_price'] );
	}

	$query = array(
		'origin'      => $origin,
		'destination' => $destination,
		'currency'    => $currency,
	);

	if ( '' !== $parsed['month'] ) {
		$query['depart_date'] = $parsed['month'];
	}

	$response = wp_remote_get(
		add_query_arg( $query, 'https://api.travelpayouts.com/v1/prices/' . $endpoint ),
		array(
			'timeout' => 8,
			'headers' => array( 'x-access-token' => $account['token'] ),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return array();
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) || empty( $body['success'] ) || empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		return array();
	}

	$results = array();

	foreach ( $body['data'] as $fares ) {
		if ( ! is_array( $fares ) ) {
			continue;
		}

		foreach ( $fares as $fare ) {
			if ( ! is_array( $fare ) || empty( $fare['price'] ) || ! is_numeric( $fare['price'] ) ) {
				continue;
			}

			/*
			 * The Data API serves cached prices and says when each one stops
			 * being usable. An expired fare is dropped rather than advertised.
			 */
			if ( ! empty( $fare['expires_at'] ) && is_string( $fare['expires_at'] ) ) {
				$expires = strtotime( $fare['expires_at'] );

				if ( false !== $expires && $expires < time() ) {
					continue;
				}
			}

			$depart = '';
			if ( ! empty( $fare['departure_at'] ) && is_string( $fare['departure_at'] ) ) {
				$depart = substr( $fare['departure_at'], 0, 10 );
			}

			$title = sprintf(
				/* translators: 1: origin IATA code, 2: destination IATA code. */
				__( '%1$s to %2$s', 'adz-world' ),
				$origin,
				$destination
			);

			if ( '' !== $depart ) {
				$title .= ', ' . $depart;
			}

			if ( $parsed['direct'] ) {
				$title .= ', ' . __( 'non-stop', 'adz-world' );
			}

			/*
			 * A fare is a route on a date, not a catalogue item, so there is
			 * no provider id to keep. The route and date identify it as well
			 * as anything can, which is what the slug is for.
			 */
			$results[] = array(
				'id'        => strtolower( $origin . '-' . $destination . ( '' === $depart ? '' : '-' . $depart ) ),
				'title'     => $title,
				'image'     => '',
				'price'     => strtoupper( $currency ) . ' ' . $fare['price'],
				'url'       => adzworld_travelpayouts_link( $account, $destination, $depart ),
				'raw_price' => (float) $fare['price'],
			);

			if ( count( $results ) >= $limit ) {
				break 2;
			}
		}
	}

	set_transient( $key, $results, ADZWORLD_TRAVELPAYOUTS_RESULT_TTL );

	return adzworld_travelpayouts_apply_price_cap( $results, $parsed['max_price'] );
}

/**
 * Drop fares above a ceiling the reader stated.
 *
 * Applied after the cache, so "under 300" does not cost a second request when
 * the same route was already fetched without a ceiling.
 *
 * @param array<int,array<string,mixed>> $results Fares.
 * @param int                            $max     Ceiling, or 0 for none.
 * @return array<int,array<string,string>> Fares within the ceiling.
 */
function adzworld_travelpayouts_apply_price_cap( $results, $max ) {
	$kept = array();

	foreach ( $results as $row ) {
		if ( $max > 0 && isset( $row['raw_price'] ) && (float) $row['raw_price'] > $max ) {
			continue;
		}

		unset( $row['raw_price'] );
		$kept[] = $row;
	}

	return $kept;
}

/**
 * Append Travelpayouts' fares to the shared result set.
 *
 * @param array<int,array<string,string>> $results Rows from earlier providers.
 * @param string                          $request Reader's request.
 * @return array<int,array<string,string>> Rows with fares appended.
 */
function adzworld_travelpayouts_append_product_results( $results, $request ) {
	foreach ( adzworld_travelpayouts_search( $request ) as $row ) {
		$row['provider'] = 'travelpayouts';
		$results[]       = $row;
	}

	return $results;
}

add_filter( 'adzworld_affiliate_networks', 'adzworld_travelpayouts_register_network' );
add_filter( 'adzworld_registration_catalog', 'adzworld_travelpayouts_register_catalog' );
add_filter( 'adzworld_getpaid_affiliate_serves_adz', 'adzworld_travelpayouts_register_request_behavior' );
add_filter( 'adzworld_affiliate_account_ready', 'adzworld_travelpayouts_filter_account_ready', 10, 3 );
add_filter( 'adzworld_affiliate_account_earns', 'adzworld_travelpayouts_filter_account_earns', 10, 3 );
add_filter( 'adzworld_affiliate_product_results', 'adzworld_travelpayouts_append_product_results', 10, 2 );
