<?php
/**
 * The slot journey.
 *
 * One row per round, four fields:
 *
 *   ad          the adzad that was shown, as provider:id
 *   category    the category string for that round
 *   rating      the reader's rating of that adzad, -100 to 100
 *   refinement  the refinement string the reader typed
 *
 * Rows are appended. Nothing the reader types is altered.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/** Rounds kept per slot. A cookie holding this is capped at 4096 bytes. */
const ADZWORLD_JOURNEY_ROUNDS = 8;

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
 * One round, with its four fields.
 *
 * @param string $ad         The adzad shown, as provider:id.
 * @param string $category   The category string for that round.
 * @param mixed  $rating     The reader's rating, -100 to 100, or null.
 * @param string $refinement The refinement string the reader typed.
 * @return array<string,mixed> The round.
 */
function adzworld_journey_round( $ad, $category, $rating, $refinement ) {
	return array(
		'ad'         => mb_substr( sanitize_text_field( (string) $ad ), 0, 120 ),
		'category'   => mb_substr( sanitize_text_field( (string) $category ), 0, 120 ),
		'rating'     => is_numeric( $rating ) ? max( -100, min( 100, (int) $rating ) ) : null,
		'refinement' => mb_substr( trim( sanitize_text_field( (string) $refinement ) ), 0, 200 ),
	);
}

/**
 * Reduce a stored journey to the shape above.
 *
 * It may have come from a cookie, so the shape and the bounds are enforced
 * rather than assumed.
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

			$row = adzworld_journey_round(
				isset( $round['ad'] ) ? $round['ad'] : '',
				isset( $round['category'] ) ? $round['category'] : '',
				isset( $round['rating'] ) ? $round['rating'] : null,
				isset( $round['refinement'] ) ? $round['refinement'] : ''
			);

			if ( '' === $row['ad'] && '' === $row['category'] && null === $row['rating'] && '' === $row['refinement'] ) {
				continue;
			}

			$kept[] = $row;
		}

		if ( $kept ) {
			$clean[ $key ] = array_slice( $kept, -ADZWORLD_JOURNEY_ROUNDS );
		}
	}

	return $clean;
}

/**
 * The adzad ids already shown in one slot.
 *
 * @param array<int,array<string,mixed>> $rounds One slot's rounds.
 * @return array<int,string> Ids, oldest first.
 */
function adzworld_journey_seen( $rounds ) {
	$seen = array();

	foreach ( (array) $rounds as $round ) {
		if ( ! empty( $round['ad'] ) ) {
			$seen[] = (string) $round['ad'];
		}
	}

	return array_values( array_unique( $seen ) );
}

/**
 * The request string: the category, then every refinement, each one whole.
 *
 * Each refinement is quoted so it reaches the provider as the reader wrote
 * it. Nothing is edited.
 *
 * @param array<int,array<string,mixed>> $rounds One slot's rounds, oldest first.
 * @return string Request string.
 */
function adzworld_journey_request_string( $rounds ) {
	$rounds = (array) $rounds;
	$parts  = array();

	foreach ( $rounds as $round ) {
		if ( ! empty( $round['category'] ) ) {
			$parts[0] = (string) $round['category'];
		}
	}

	foreach ( $rounds as $round ) {
		if ( ! empty( $round['refinement'] ) ) {
			$parts[] = '"' . str_replace( '"', "'", (string) $round['refinement'] ) . '"';
		}
	}

	return trim( implode( ' ', $parts ) );
}
