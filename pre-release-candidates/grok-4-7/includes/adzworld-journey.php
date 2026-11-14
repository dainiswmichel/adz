<?php
/**
 * The slot journey.
 *
 * One slot keeps what the reader stated. The opening request is the category
 * string. Each round, numbered from 1 by its place in the list, holds the ad
 * that was shown, the rating word the reader chose, and the refinement they
 * typed next.
 *
 * The rating words are strongly dislike, dislike, neutral, like, and strongly
 * like. An empty rating means the reader did not respond. The word neutral is
 * stored only when the reader chose it.
 *
 * The search string for a provider is the opening request and those
 * refinements, in order, with one space between them. The rating and the ad
 * id stay on the round. This file does not decide which ad comes next.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rating words a reader can choose.
 *
 * @return string[]
 */
function adzworld_journey_rating_words() {
	return array(
		'strongly dislike',
		'dislike',
		'neutral',
		'like',
		'strongly like',
	);
}

/**
 * Keep a stated string whole.
 *
 * HTML tags and NUL bytes cannot be stored. The words are not lowercased,
 * stemmed, or rewritten. A stated phrase uses the refinement length already
 * set for this plugin.
 *
 * @param string $text Reader's text, or an ad id.
 * @return string
 */
function adzworld_journey_statement( $text ) {
	$text = wp_strip_all_tags( (string) $text );
	$text = str_replace( "\0", '', $text );
	$text = trim( $text );

	if ( defined( 'ADZWORLD_REFINEMENT_MAX_LENGTH' ) && function_exists( 'mb_substr' ) ) {
		$text = mb_substr( $text, 0, ADZWORLD_REFINEMENT_MAX_LENGTH );
	}

	return $text;
}

/**
 * Reduce a rating to one of the five words, or to no response.
 *
 * @param mixed $rating A rating word, empty for no response, or something else.
 * @return string|null|false The word, null when the reader did not respond, false when the value is not a rating word.
 */
function adzworld_journey_normalize_rating( $rating ) {
	if ( null === $rating || false === $rating ) {
		return null;
	}

	if ( ! is_string( $rating ) ) {
		return false;
	}

	$rating = strtolower( trim( wp_strip_all_tags( $rating ) ) );

	if ( '' === $rating ) {
		return null;
	}

	if ( in_array( $rating, adzworld_journey_rating_words(), true ) ) {
		return $rating;
	}

	return false;
}

/**
 * The word for a stored rating. Empty storage is no response.
 *
 * @param string|null $rating Stored rating.
 * @return string
 */
function adzworld_journey_rating_word( $rating ) {
	if ( is_string( $rating ) && in_array( $rating, adzworld_journey_rating_words(), true ) ) {
		return $rating;
	}

	return 'no response';
}

/**
 * An empty journey for one slot.
 *
 * @param string $request Opening category string.
 * @return array{request:string,rounds:array<int,array{ad:string,rating:?string,refinement:string}>}
 */
function adzworld_journey_start( $request ) {
	return array(
		'request' => adzworld_journey_statement( $request ),
		'rounds'  => array(),
	);
}

/**
 * Append one round.
 *
 * A round without an ad is not stored. A rating that is not one of the five
 * words is not stored and is not replaced with another word.
 *
 * @param array<string,mixed> $journey    Journey.
 * @param string              $ad         Ad id, as provider:id.
 * @param mixed               $rating     Rating word, or empty for no response.
 * @param string              $refinement Refinement the reader typed. May be empty.
 * @return array<string,mixed> Journey, with the round appended when the ad and rating are usable.
 */
function adzworld_journey_add( $journey, $ad, $rating, $refinement ) {
	$journey    = adzworld_journey_sanitize( $journey );
	$ad         = adzworld_journey_statement( $ad );
	$refinement = adzworld_journey_statement( $refinement );
	$rating     = adzworld_journey_normalize_rating( $rating );

	if ( '' === $ad || false === $rating ) {
		return $journey;
	}

	$journey['rounds'][] = array(
		'ad'         => $ad,
		'rating'     => $rating,
		'refinement' => $refinement,
	);

	return $journey;
}

/**
 * Reduce stored data to the journey shape.
 *
 * @param mixed $stored Raw value.
 * @return array{request:string,rounds:array<int,array{ad:string,rating:?string,refinement:string}>}
 */
function adzworld_journey_sanitize( $stored ) {
	$request = '';

	if ( is_array( $stored ) && isset( $stored['request'] ) ) {
		$request = adzworld_journey_statement( $stored['request'] );
	}

	$journey = adzworld_journey_start( $request );
	$rounds  = ( is_array( $stored ) && isset( $stored['rounds'] ) && is_array( $stored['rounds'] ) ) ? $stored['rounds'] : array();

	foreach ( $rounds as $round ) {
		if ( ! is_array( $round ) ) {
			continue;
		}

		$ad         = isset( $round['ad'] ) ? adzworld_journey_statement( $round['ad'] ) : '';
		$refinement = isset( $round['refinement'] ) ? adzworld_journey_statement( $round['refinement'] ) : '';
		$rating     = array_key_exists( 'rating', $round ) ? adzworld_journey_normalize_rating( $round['rating'] ) : null;

		if ( '' === $ad || false === $rating ) {
			continue;
		}

		$journey['rounds'][] = array(
			'ad'         => $ad,
			'rating'     => $rating,
			'refinement' => $refinement,
		);
	}

	return $journey;
}

/**
 * Ad ids already shown, oldest first.
 *
 * @param array<string,mixed> $journey Journey.
 * @return string[]
 */
function adzworld_journey_seen( $journey ) {
	$journey = adzworld_journey_sanitize( $journey );
	$seen    = array();

	foreach ( $journey['rounds'] as $round ) {
		if ( '' === $round['ad'] || isset( $seen[ $round['ad'] ] ) ) {
			continue;
		}

		$seen[ $round['ad'] ] = true;
	}

	return array_keys( $seen );
}

/**
 * The search string a provider can take.
 *
 * The opening request, then each refinement that was typed, in order. Rating
 * words and ad ids are not included.
 *
 * @param array<string,mixed> $journey Journey.
 * @return string
 */
function adzworld_journey_keywords( $journey ) {
	$journey = adzworld_journey_sanitize( $journey );
	$parts   = array();

	if ( '' !== $journey['request'] ) {
		$parts[] = $journey['request'];
	}

	foreach ( $journey['rounds'] as $round ) {
		if ( '' !== $round['refinement'] ) {
			$parts[] = $round['refinement'];
		}
	}

	return implode( ' ', $parts );
}

/**
 * The record of the slot, with rounds numbered from 1.
 *
 * This is the log. It is not the search string.
 *
 * @param array<string,mixed> $journey Journey.
 * @return array{request:string,rounds:array<int,array{round:int,ad:string,rating:string,refinement:string}>}
 */
function adzworld_journey_record( $journey ) {
	$journey = adzworld_journey_sanitize( $journey );
	$rows    = array();
	$number  = 0;

	foreach ( $journey['rounds'] as $round ) {
		++$number;
		$rows[] = array(
			'round'      => $number,
			'ad'         => $round['ad'],
			'rating'     => adzworld_journey_rating_word( $round['rating'] ),
			'refinement' => $round['refinement'],
		);
	}

	return array(
		'request' => $journey['request'],
		'rounds'  => $rows,
	);
}
