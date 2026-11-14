<?php
/**
 * The slot journey: what was shown, what the reader thought, what they said.
 *
 * Nothing the reader types is ever altered. Their words reach the provider as
 * they wrote them. The plugin decides which statements to carry; it does not
 * edit, trim, stem or reword any of them.
 *
 * One entry per round:
 *
 *   shown   the provider ids displayed that round, as provider:id
 *   rating  the reader's verdict on that round, -100 to 100, or null
 *   said    what they typed next, in their own words
 *
 * The record is sequential because the order is what separates a reader
 * adding a detail from a reader correcting a mistake. The point of keeping it
 * is the request handed to a provider: each round the reader tells us more,
 * and each round the provider is given a more specific request and can return
 * better adz for it.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/** Rounds kept per slot. The cookie that may hold this is capped at 4096 bytes. */
const ADZWORLD_JOURNEY_ROUNDS = 8;

/** Longest request string a provider is given, matching the existing cap. */
const ADZWORLD_JOURNEY_MAX_LENGTH = 240;

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
 * Reduce a stored journey to the shape this file guarantees.
 *
 * This may have come from a cookie, so the shape, the lengths and the rating
 * range are enforced rather than assumed. What a reader wrote is not edited
 * here beyond the tag stripping every stored string gets.
 *
 * @param mixed $stored Raw stored value.
 * @return array<string,array<int,array<string,mixed>>> Journeys by slot key.
 */
function adzworld_sanitize_journey( $stored ) {
	if ( ! is_array( $stored ) ) {
		return array();
	}

	$clean = array();

	foreach ( $stored as $key => $rounds ) {
		$key = sanitize_text_field( (string) $key );

		if ( '' === $key || ! is_array( $rounds ) ) {
			continue;
		}

		$kept = array();

		foreach ( $rounds as $round ) {
			if ( ! is_array( $round ) ) {
				continue;
			}

			$shown = array();

			if ( isset( $round['shown'] ) && is_array( $round['shown'] ) ) {
				foreach ( array_slice( $round['shown'], 0, 10 ) as $id ) {
					$id = sanitize_text_field( (string) $id );

					if ( '' !== $id ) {
						$shown[] = mb_substr( $id, 0, 120 );
					}
				}
			}

			$rating = null;

			if ( isset( $round['rating'] ) && is_numeric( $round['rating'] ) ) {
				$rating = max( -100, min( 100, (int) $round['rating'] ) );
			}

			$said = isset( $round['said'] ) ? sanitize_text_field( (string) $round['said'] ) : '';
			$said = mb_substr( trim( $said ), 0, 200 );

			if ( ! $shown && null === $rating && '' === $said ) {
				continue;
			}

			$kept[] = array(
				'shown'  => $shown,
				'rating' => $rating,
				'said'   => $said,
			);
		}

		if ( $kept ) {
			$clean[ $key ] = array_slice( $kept, -ADZWORLD_JOURNEY_ROUNDS );
		}
	}

	return $clean;
}

/**
 * Every id this reader has already been shown in one slot.
 *
 * What a reader has seen and rejected should not come back, and that needs
 * the ids the fetchers carry on each row.
 *
 * @param array<int,array<string,mixed>> $rounds One slot's rounds.
 * @return array<int,string> Provider ids, oldest first.
 */
function adzworld_journey_seen( $rounds ) {
	$seen = array();

	foreach ( (array) $rounds as $round ) {
		if ( isset( $round['shown'] ) && is_array( $round['shown'] ) ) {
			foreach ( $round['shown'] as $id ) {
				$seen[] = (string) $id;
			}
		}
	}

	return array_values( array_unique( $seen ) );
}

/**
 * The request handed to a provider, built from what the reader actually said.
 *
 * Each statement is carried whole and quoted, so the words that reach the
 * provider are the words that were written, and a provider that treats a
 * quoted phrase as a phrase is given the sentence rather than a bag of loose
 * words.
 *
 * The only judgement is which rounds are carried. A round the reader rated at
 * or below the reset is them saying the direction was wrong, so what they say
 * next replaces what they were narrowing towards instead of piling on top of
 * it. Whole statements are kept or dropped; none is edited.
 *
 * @param array<int,array<string,mixed>> $rounds   One slot's rounds, oldest first.
 * @param string                         $category The slot's subject.
 * @param int                            $reset    Rating at or below which a round starts over.
 * @return string Request string.
 */
function adzworld_journey_request_string( $rounds, $category, $reset = -50 ) {
	$category = trim( wp_strip_all_tags( (string) $category ) );
	$said     = array();

	foreach ( (array) $rounds as $round ) {
		$rating = isset( $round['rating'] ) && is_numeric( $round['rating'] ) ? (int) $round['rating'] : null;
		$text   = isset( $round['said'] ) ? trim( wp_strip_all_tags( (string) $round['said'] ) ) : '';

		if ( null !== $rating && $rating <= $reset ) {
			$said = array();
		}

		if ( '' !== $text ) {
			/* A double quote inside the text would close the phrase early. */
			$said[] = '"' . str_replace( '"', "'", $text ) . '"';
		}
	}

	$request = trim( $category . ' ' . implode( ' ', $said ) );

	/*
	 * Over the cap the oldest statement goes first, so the newest thing the
	 * reader said always survives whole. Nothing is cut mid-sentence.
	 */
	while ( $said && strlen( $request ) > ADZWORLD_JOURNEY_MAX_LENGTH ) {
		array_shift( $said );
		$request = trim( $category . ' ' . implode( ' ', $said ) );
	}

	return $request;
}
