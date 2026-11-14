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
 * @param string              $ad         The adzad shown, as provider:id.
 * @param mixed               $rating     The reader's rating of it, or null.
 * @param string              $refinement What the reader typed next.
 * @return array<string,mixed> The journey, with the round appended.
 */
function adzworld_journey_add( $journey, $ad, $rating, $refinement ) {
	$journey           = adzworld_sanitize_slot_journey( $journey );
	$journey['rounds'] = array_slice(
		array_merge(
			$journey['rounds'],
			array(
				array(
					'ad'         => mb_substr( sanitize_text_field( (string) $ad ), 0, 120 ),
					'rating'     => is_numeric( $rating ) ? max( -100, min( 100, (int) $rating ) ) : null,
					'refinement' => mb_substr( trim( sanitize_text_field( (string) $refinement ) ), 0, 200 ),
				),
			)
		),
		-ADZWORLD_JOURNEY_ROUNDS
	);

	return $journey;
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

		$ad         = isset( $round['ad'] ) ? mb_substr( sanitize_text_field( (string) $round['ad'] ), 0, 120 ) : '';
		$rating     = isset( $round['rating'] ) && is_numeric( $round['rating'] ) ? max( -100, min( 100, (int) $round['rating'] ) ) : null;
		$refinement = isset( $round['refinement'] ) ? mb_substr( trim( sanitize_text_field( (string) $round['refinement'] ) ), 0, 200 ) : '';

		if ( '' === $ad && null === $rating && '' === $refinement ) {
			continue;
		}

		$journey['rounds'][] = array(
			'ad'         => $ad,
			'rating'     => $rating,
			'refinement' => $refinement,
		);
	}

	$journey['rounds'] = array_slice( $journey['rounds'], -ADZWORLD_JOURNEY_ROUNDS );

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
 * The request string: the opening request, then every refinement in order.
 *
 * Each refinement is quoted so it reaches the provider as the reader wrote
 * it. Nothing is edited.
 *
 * @param array<string,mixed> $journey The journey.
 * @return string Request string.
 */
function adzworld_journey_request_string( $journey ) {
	$journey = adzworld_sanitize_slot_journey( $journey );
	$parts   = '' === $journey['request'] ? array() : array( $journey['request'] );

	foreach ( $journey['rounds'] as $round ) {
		if ( '' !== $round['refinement'] ) {
			$parts[] = '"' . str_replace( '"', "'", $round['refinement'] ) . '"';
		}
	}

	return trim( implode( ' ', $parts ) );
}
