<?php
/**
 * The one place a reader's request becomes product cards.
 *
 * Several provider files can answer the same request: eBay, Etsy and Best Buy
 * each return products for the reader's own words rather than a search page.
 * Each one appends its rows to the adzworld_affiliate_product_results filter.
 * This file merges them, normalises them to a single shape, interleaves them
 * so no one provider takes the whole slot, and renders them.
 *
 * Provider files do not render. They return rows. That is the whole contract,
 * and it is why a fourth provider needs no change here.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/** Most cards shown in one slot, across every provider. */
const ADZWORLD_PRODUCTS_LIMIT = 6;

/**
 * Human labels for the providers that can return products.
 *
 * The label is shown on the card. A reader is told which marketplace an offer
 * came from rather than being shown an unattributed product.
 *
 * @return array<string,string> Provider id to label.
 */
function adzworld_products_provider_labels() {
	return apply_filters(
		'adzworld_products_provider_labels',
		array(
			'ebay'         => 'eBay',

			/*
			 * Labelled as a test, always. Sandbox inventory is invented, and a
			 * reader shown it as a real offer would be misled.
			 */
			'ebay-sandbox' => 'eBay sandbox (test data)',
			'etsy'         => 'Etsy',
			'bestbuy'      => 'Best Buy',
		)
	);
}

/**
 * Reduce one provider's row to the shape the renderer accepts.
 *
 * Three provider files were written separately and return slightly different
 * arrays. Rather than ask each to change, every row passes through here, so
 * the renderer can rely on exactly five keys and the markup never has to test
 * whether a field exists.
 *
 * @param mixed  $row         One provider row.
 * @param string $provider_id Provider that supplied it.
 * @return array<string,string>|null Normalised row, or null when unusable.
 */
function adzworld_products_normalise_row( $row, $provider_id = '' ) {
	if ( ! is_array( $row ) ) {
		return null;
	}

	$title = isset( $row['title'] ) ? trim( wp_strip_all_tags( (string) $row['title'] ) ) : '';
	$url   = isset( $row['url'] ) ? (string) $row['url'] : '';

	/*
	 * A card with no destination earns nothing and a card with no title tells
	 * the reader nothing, so either one is dropped rather than rendered empty.
	 */
	if ( '' === $title || '' === $url ) {
		return null;
	}

	/*
	 * Only https. A provider returning a plain-http image would downgrade the
	 * page, and one returning a non-URL has misunderstood the contract.
	 */
	$url = esc_url_raw( $url );
	if ( '' === $url || 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
		return null;
	}

	$image = isset( $row['image'] ) ? esc_url_raw( (string) $row['image'] ) : '';
	if ( '' !== $image && 'https' !== strtolower( (string) wp_parse_url( $image, PHP_URL_SCHEME ) ) ) {
		$image = '';
	}

	$provider = isset( $row['provider'] ) ? (string) $row['provider'] : (string) $provider_id;
	$labels   = adzworld_products_provider_labels();

	return array(
		'title'    => $title,
		'url'      => $url,
		'image'    => $image,
		'price'    => isset( $row['price'] ) ? trim( wp_strip_all_tags( (string) $row['price'] ) ) : '',
		'provider' => $provider,
		'source'   => isset( $labels[ $provider ] ) ? $labels[ $provider ] : '',
	);
}

/**
 * Take one row from each provider in turn, until the cap is reached.
 *
 * Appending the providers end to end would give the whole slot to whichever
 * file happened to load first. Round-robin means a reader sees a spread.
 *
 * @param array<int,array<string,string>> $rows  Normalised rows.
 * @param int                             $limit Most rows to return.
 * @return array<int,array<string,string>> Interleaved rows.
 */
function adzworld_products_interleave( $rows, $limit = ADZWORLD_PRODUCTS_LIMIT ) {
	$queues = array();
	foreach ( $rows as $row ) {
		$key = '' === $row['provider'] ? '_' : $row['provider'];
		if ( ! isset( $queues[ $key ] ) ) {
			$queues[ $key ] = array();
		}
		$queues[ $key ][] = $row;
	}

	$out   = array();
	$taken = 0;
	while ( $taken < $limit && $queues ) {
		foreach ( array_keys( $queues ) as $key ) {
			$next = array_shift( $queues[ $key ] );
			if ( null === $next ) {
				unset( $queues[ $key ] );
				continue;
			}
			$out[] = $next;
			++$taken;
			if ( $taken >= $limit ) {
				break;
			}
		}
	}

	return $out;
}

/**
 * Every provider's products for one request, merged and ready to render.
 *
 * @param string $request Reader's request: the category and its refinements.
 * @return array<int,array<string,string>> Normalised, interleaved rows.
 */
function adzworld_products_collect( $request ) {
	$request = trim( wp_strip_all_tags( (string) $request ) );

	if ( '' === $request ) {
		return array();
	}

	/**
	 * Collect products for a reader's request.
	 *
	 * A provider file appends its own rows. Each row needs a title, an https
	 * url, and ideally an image, a price and its provider id. A provider that
	 * cannot answer returns the rows it was given, unchanged.
	 *
	 * @param array<int,array<string,string>> $rows    Rows so far.
	 * @param string                          $request Reader's request.
	 */
	$rows = apply_filters( 'adzworld_affiliate_product_results', array(), $request );

	if ( ! is_array( $rows ) ) {
		return array();
	}

	$clean = array();
	$seen  = array();
	foreach ( $rows as $row ) {
		$normal = adzworld_products_normalise_row( $row );
		if ( null === $normal ) {
			continue;
		}

		/* Two providers listing the same destination is one card, not two. */
		$fingerprint = md5( $normal['url'] );
		if ( isset( $seen[ $fingerprint ] ) ) {
			continue;
		}
		$seen[ $fingerprint ] = true;
		$clean[]              = $normal;
	}

	return adzworld_products_interleave( $clean );
}

/**
 * Product cards for a reader's request.
 *
 * @param array<int,array<string,string>> $results Normalised rows.
 * @param string                          $request Reader's request, for the heading.
 * @return string Markup, or an empty string when there is nothing to show.
 */
function adzworld_products_markup( $results, $request ) {
	if ( ! $results ) {
		return '';
	}

	$request = trim( wp_strip_all_tags( (string) $request ) );

	$html = '<div class="adzworld-products">';

	$html .= '<p class="adzworld-products__head">'
		. esc_html(
			sprintf(
				/* translators: %s: the reader's own request text. */
				__( 'Matching %s right now', 'adz-world' ),
				$request
			)
		)
		. '</p>';

	$html .= '<ul class="adzworld-products__list">';

	foreach ( $results as $result ) {
		$html .= '<li class="adzworld-product">';
		$html .= '<a class="adzworld-product__link" href="' . esc_url( $result['url'] ) . '" rel="sponsored nofollow" target="_blank">';

		if ( '' !== $result['image'] ) {
			/*
			 * The title sits beside the picture, so the picture carries no
			 * alternative text of its own and is not announced twice.
			 */
			$html .= '<span class="adzworld-product__media"><img class="adzworld-product__img" src="'
				. esc_url( $result['image'] ) . '" alt="" loading="lazy" decoding="async"></span>';
		}

		$html .= '<span class="adzworld-product__title">' . esc_html( $result['title'] ) . '</span>';

		if ( '' !== $result['price'] ) {
			$html .= '<span class="adzworld-product__price">' . esc_html( $result['price'] ) . '</span>';
		}

		if ( '' !== $result['source'] ) {
			$html .= '<span class="adzworld-product__source">' . esc_html( $result['source'] ) . '</span>';
		}

		$html .= '</a></li>';
	}

	return $html . '</ul></div>';
}
