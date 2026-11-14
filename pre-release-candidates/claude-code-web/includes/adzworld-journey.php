<?php
/**
 * The slot journey.
 *
 * A slot starts with a request. That request generates the initial adzad.
 * From there the rounds are numbered from 1, and each round holds the adzad
 * that was shown, what the reader thought of it, and what they said next:
 *
 *   request     the category string, which generated the initial adzad
 *   1  ad       the adzad shown
 *      rating   the reader's rating of it, -100 to 100
 *      refinement  what the reader typed next
 *   2  ad, rating, refinement
 *   ...
 *
 * Each round's refinement generates the next round's adzad. Nothing the
 * reader types is altered.
 *
 * A journey has no round limit. It is the record of what a reader did and
 * none of it is discarded here. A cookie cannot hold more than about 4096
 * bytes, so the anonymous storage path has to decide what it can keep when
 * it is written; that is a limit of that one store, not of this record, and
 * it does not apply to a member whose journey is held in user meta.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * A stable key for one slot's journey.
 *
 * @param string $rotation Rotation name.
 * @param int    $slot     Slot index within it.
 * @return string Key.
 */
function adzworld_journey_key( $rotation, $slot ) {
	return sanitize_key( $rotation ) . ':' . (int) $slot;
}

/**
 * An empty journey for one slot.
 *
 * @param string $request The category string that starts it.
 * @return array<string,mixed> Journey.
 */
function adzworld_journey_start( $request ) {
	return array(
		'request' => mb_substr( trim( sanitize_text_field( (string) $request ) ), 0, 200 ),
		'rounds'  => array(),
	);
}

/**
 * Add a round to a journey, numbered from 1.
 *
 * @param array<string,mixed> $journey    The journey.
 * @param string              $ad         The adzad shown, as source:id.
 * @param string              $category   The category in force at this round.
 * @param mixed               $rating     The reader's rating of it, or null.
 * @param string              $refinement What the reader typed next.
 * @return array<string,mixed> The journey, with the round appended.
 */
function adzworld_journey_add( $journey, $ad, $category, $rating, $refinement ) {
	$journey             = adzworld_sanitize_slot_journey( $journey );
	$journey['rounds'][] = adzworld_journey_round( $ad, $category, $rating, $refinement );

	return $journey;
}

/**
 * One round of a slot journey.
 *
 * The category travels with the round rather than sitting once at the top,
 * because a reader can change what a slot is about part way through. Round
 * one may be couches and round four a bicycle, and the record has to say
 * which adzad belonged to which.
 *
 * @param string $ad         The adzad shown, as source:id.
 * @param string $category   The category in force at this round.
 * @param mixed  $rating     The reader's rating of it, -100 to 100, or null.
 * @param string $refinement What the reader typed next.
 * @return array<string,mixed> The round.
 */
function adzworld_journey_round( $ad, $category, $rating, $refinement ) {
	return array(
		'ad'         => mb_substr( sanitize_text_field( (string) $ad ), 0, 120 ),
		'category'   => mb_substr( trim( sanitize_text_field( (string) $category ) ), 0, 120 ),
		'rating'     => is_numeric( $rating ) ? max( -100, min( 100, (int) $rating ) ) : null,
		'refinement' => mb_substr( trim( sanitize_text_field( (string) $refinement ) ), 0, 200 ),
	);
}

/**
 * Whether an adzad is the publisher's own or a provider's.
 *
 * An internal adzad is one this site published, so a reader's verdict on it
 * belongs to the site: it ranks that adzad for them next time. An external
 * adzad belongs to a provider, so the verdict has nowhere to go except into
 * the request string the provider is sent.
 *
 * @param string $ad The adzad id, as source:id.
 * @return bool Whether it is internal.
 */
function adzworld_journey_is_internal( $ad ) {
	return 0 === strpos( (string) $ad, 'internal:' );
}

/**
 * Reduce one slot's journey to the shape above.
 *
 * It may have come from a cookie, so the shape and the bounds are enforced
 * rather than assumed.
 *
 * @param mixed $stored Raw stored value.
 * @return array<string,mixed> Journey.
 */
function adzworld_sanitize_slot_journey( $stored ) {
	$journey = adzworld_journey_start( is_array( $stored ) && isset( $stored['request'] ) ? $stored['request'] : '' );

	if ( ! is_array( $stored ) || ! isset( $stored['rounds'] ) || ! is_array( $stored['rounds'] ) ) {
		return $journey;
	}

	foreach ( $stored['rounds'] as $round ) {
		if ( ! is_array( $round ) ) {
			continue;
		}

		$row = adzworld_journey_round(
			isset( $round['ad'] ) ? $round['ad'] : '',
			isset( $round['category'] ) ? $round['category'] : '',
			isset( $round['rating'] ) ? $round['rating'] : null,
			isset( $round['refinement'] ) ? $round['refinement'] : ''
		);

		if ( '' === $row['ad'] && '' === $row['category'] && null === $row['rating'] && '' === $row['refinement'] ) {
			continue;
		}

		$journey['rounds'][] = $row;
	}

	return $journey;
}

/**
 * Every journey held for a reader, by slot key.
 *
 * @param mixed $stored Raw stored value.
 * @return array<string,array<string,mixed>> Journeys by slot key.
 */
function adzworld_sanitize_journey( $stored ) {
	if ( ! is_array( $stored ) ) {
		return array();
	}

	$clean = array();

	foreach ( $stored as $key => $journey ) {
		$key = sanitize_text_field( (string) $key );

		if ( '' === $key ) {
			continue;
		}

		$slot = adzworld_sanitize_slot_journey( $journey );

		if ( '' !== $slot['request'] || $slot['rounds'] ) {
			$clean[ $key ] = $slot;
		}
	}

	return $clean;
}

/**
 * How a reader's rating is said to a provider.
 *
 * Three states, which is what a provider can act on today:
 *
 *   dislike       the reader rated it below zero
 *   no response   the reader did not rate it
 *   like          the reader rated it above zero
 *
 * The -100 to 100 scale is kept whole in storage. This is only how it is
 * expressed outward, so the slider keeps every notch it has.
 *
 * A rating of exactly zero reports as no response. A reader who moves the
 * slider to the middle has said neither dislike nor like, and those are the
 * only other two words available.
 *
 * @param mixed $rating The stored rating, or null.
 * @return string One of dislike, no response, like.
 */
function adzworld_journey_response( $rating ) {
	if ( ! is_numeric( $rating ) ) {
		return 'no response';
	}

	$rating = (int) $rating;

	if ( $rating < 0 ) {
		return 'dislike';
	}

	if ( $rating > 0 ) {
		return 'like';
	}

	return 'no response';
}

/**
 * The adzad ids already shown in one slot.
 *
 * @param array<string,mixed> $journey The journey.
 * @return array<int,string> Ids, oldest first.
 */
function adzworld_journey_seen( $journey ) {
	$journey = adzworld_sanitize_slot_journey( $journey );
	$seen    = array();

	foreach ( $journey['rounds'] as $round ) {
		if ( '' !== $round['ad'] ) {
			$seen[] = $round['ad'];
		}
	}

	return array_values( array_unique( $seen ) );
}

/**
 * The full request: what was shown, what the reader thought, what they said.
 *
 * This is the whole record projected outward, in the order it happened:
 *
 *   couch dislike ebay:110588014268 "no i want a brown couch"
 *         like etsy:1566104376 "show only 3 seater brown couches"
 *
 * No provider connected today can read it. Every one of them searches for
 * each word, so "dislike" and "ebay:110588014268" are words they would hunt
 * for in product titles and never find. It is built because it is the whole
 * truth of what the reader did, and because a provider that can act on a
 * sequence should be handed the sequence.
 *
 * For what goes over the wire today, use adzworld_journey_keywords().
 *
 * @param array<string,mixed> $journey The journey.
 * @return string Full request.
 */
function adzworld_journey_request_string( $journey ) {
	$journey = adzworld_sanitize_slot_journey( $journey );
	$parts   = '' === $journey['request'] ? array() : array( $journey['request'] );

	$category = $journey['request'];

	foreach ( $journey['rounds'] as $round ) {
		$said = array();

		/* A reader who changed what the slot is about says so here. */
		if ( '' !== $round['category'] && $round['category'] !== $category ) {
			$category = $round['category'];
			$said[]   = $category;
		}

		if ( '' !== $round['ad'] ) {
			$said[] = adzworld_journey_response( $round['rating'] ) . ' ' . $round['ad'];
		}

		if ( '' !== $round['refinement'] ) {
			$said[] = '"' . str_replace( '"', "'", $round['refinement'] ) . '"';
		}

		if ( $said ) {
			$parts[] = implode( ' ', $said );
		}
	}

	return trim( implode( ' ', $parts ) );
}

/**
 * The same journey as keywords, for a provider that searches word by word.
 *
 * The opening request and every refinement, each one quoted and whole. What
 * is left out is what a keyword search cannot use: the adzad ids and the
 * response words, which it would look for in product titles.
 *
 * Nothing inside a refinement is altered. Whole statements are carried.
 *
 * @param array<string,mixed> $journey The journey.
 * @return string Keyword request.
 */
function adzworld_journey_keywords( $journey ) {
	$journey = adzworld_sanitize_slot_journey( $journey );
	$parts   = '' === $journey['request'] ? array() : array( $journey['request'] );

	$category = $journey['request'];

	foreach ( $journey['rounds'] as $round ) {
		if ( '' !== $round['category'] && $round['category'] !== $category ) {
			$category = $round['category'];
			$parts[]  = $category;
		}

		if ( '' !== $round['refinement'] ) {
			$parts[] = '"' . str_replace( '"', "'", $round['refinement'] ) . '"';
		}
	}

	return trim( implode( ' ', $parts ) );
}
