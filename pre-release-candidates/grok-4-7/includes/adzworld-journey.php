<?php
/**
 * The slot journey.
 *
 * One slot keeps what the reader stated, in order. The opening request is
 * the category string. Round 1 holds the first ad id and the full response
 * to it. Round 2 holds the next ad id and the full response to that ad.
 *
 * The specific value on a round is the rating number the reader set, from
 * -100 to 100. Store that number as set. Do not divide it. Do not replace
 * it with a word. An empty rating means the reader did not respond. Zero
 * is a response.
 *
 * A translation may be stored on the same round. It is the coarser label:
 * strongly dislike, dislike, neutral, like, strongly like, or no response.
 * A collected translation is kept. When none was collected, the label uses
 * the same cut points as adzworld_feedback_label(). The number stays the
 * specific value, so two different numbers may share one translation.
 *
 * The search string for a provider is the opening request and the typed
 * refinements, in order. The rating, the translation, and the ad id stay
 * on the round. This file does not decide which ad comes next.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

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
 * Reduce a rating to the number the reader set, or to no response.
 *
 * A word is not a rating. This function does not turn a word into a number.
 *
 * @param mixed $rating Integer from -100 to 100, a numeric string in that range, empty for no response, or something else.
 * @return int|null|false The number, null when the reader did not respond, false when the value is not that number.
 */
function adzworld_journey_normalize_rating( $rating ) {
	if ( null === $rating || false === $rating ) {
		return null;
	}

	if ( is_string( $rating ) ) {
		$rating = trim( $rating );

		if ( '' === $rating ) {
			return null;
		}
	}

	if ( is_int( $rating ) ) {
		$number = $rating;
	} elseif ( is_float( $rating ) || ( is_string( $rating ) && is_numeric( $rating ) ) ) {
		$number = (int) $rating;

		if ( (string) $number !== (string) $rating && (float) $number !== (float) $rating ) {
			return false;
		}
	} else {
		return false;
	}

	if ( $number < -100 || $number > 100 ) {
		return false;
	}

	return $number;
}

/**
 * Recognize a collected translation.
 *
 * The stored word is one of the six record labels. "like a lot" is the same
 * label as "strongly like". Any other text is left off the translation.
 *
 * @param mixed $translation Collected label.
 * @return string A record label, or an empty string when none was collected.
 */
function adzworld_journey_normalize_translation( $translation ) {
	if ( ! is_string( $translation ) ) {
		return '';
	}

	$translation = strtolower( $translation );
	$collapsed   = preg_replace( '/\s+/', ' ', $translation );

	if ( ! is_string( $collapsed ) ) {
		return '';
	}

	$translation = trim( $collapsed );

	if ( 'like a lot' === $translation ) {
		$translation = 'strongly like';
	}

	$words = array(
		'strongly dislike',
		'dislike',
		'neutral',
		'like',
		'strongly like',
		'no response',
	);

	if ( in_array( $translation, $words, true ) ) {
		return $translation;
	}

	return '';
}

/**
 * Coarser label for a rating number.
 *
 * The cut points are the ones in adzworld_feedback_label(): -75, -25, 25,
 * and 75. Null uses the label for an unmoved control. The number is unchanged.
 *
 * @param int|null $rating Rating number, or null when the reader did not respond.
 * @return string One of the six record labels.
 */
function adzworld_journey_project_translation( $rating ) {
	if ( null === $rating ) {
		return 'no response';
	}

	if ( $rating <= -75 ) {
		return 'strongly dislike';
	}

	if ( $rating <= -25 ) {
		return 'dislike';
	}

	if ( $rating < 25 ) {
		return 'neutral';
	}

	if ( $rating < 75 ) {
		return 'like';
	}

	return 'strongly like';
}

/**
 * Translation stored on a round.
 *
 * A collected label is kept, including when it is coarser than the number.
 * An empty or unrecognized label uses the projection of the number.
 *
 * @param int|null $rating      Rating number, or null.
 * @param mixed    $translation Collected label.
 * @return string
 */
function adzworld_journey_resolve_translation( $rating, $translation ) {
	$collected = adzworld_journey_normalize_translation( $translation );

	if ( '' !== $collected ) {
		return $collected;
	}

	return adzworld_journey_project_translation( $rating );
}

/**
 * An empty journey for one slot.
 *
 * @param string $request Opening category string.
 * @return array{request:string,rounds:array<int,array{ad:string,rating:?int,translation:string,refinement:string}>}
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
 * A round without an ad is not stored. A rating outside -100 to 100, or a
 * word in the rating argument, is not stored and is not turned into a number.
 * The translation argument is the coarser label and does not change the number.
 *
 * @param array<string,mixed> $journey     Journey.
 * @param string              $ad          Ad id.
 * @param mixed               $rating      Rating number, or empty for no response.
 * @param string              $refinement  Refinement the reader typed. May be empty.
 * @param mixed               $translation Collected translation. Empty when none was collected.
 * @return array<string,mixed> Journey, with the round appended when the ad and rating are usable.
 */
function adzworld_journey_add( $journey, $ad, $rating, $refinement, $translation = null ) {
	$journey    = adzworld_journey_sanitize( $journey );
	$ad         = adzworld_journey_statement( $ad );
	$refinement = adzworld_journey_statement( $refinement );
	$rating     = adzworld_journey_normalize_rating( $rating );

	if ( '' === $ad || false === $rating ) {
		return $journey;
	}

	$journey['rounds'][] = array(
		'ad'          => $ad,
		'rating'      => $rating,
		'translation' => adzworld_journey_resolve_translation( $rating, $translation ),
		'refinement'  => $refinement,
	);

	return $journey;
}

/**
 * Reduce stored data to the journey shape.
 *
 * @param mixed $stored Raw value.
 * @return array{request:string,rounds:array<int,array{ad:string,rating:?int,translation:string,refinement:string}>}
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
		$collected  = array_key_exists( 'translation', $round ) ? $round['translation'] : null;

		if ( '' === $ad || false === $rating ) {
			continue;
		}

		$journey['rounds'][] = array(
			'ad'          => $ad,
			'rating'      => $rating,
			'translation' => adzworld_journey_resolve_translation( $rating, $collected ),
			'refinement'  => $refinement,
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
 * numbers, translations, and ad ids are left on the round.
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
 * Each round is an ad id and the full response. The rating number is the
 * specific value. The translation is the coarser label on that same round.
 * The refinement is the text the reader typed.
 *
 * @param array<string,mixed> $journey Journey.
 * @return array{request:string,rounds:array<int,array{round:int,ad:string,rating:?int,translation:string,refinement:string}>}
 */
function adzworld_journey_record( $journey ) {
	$journey = adzworld_journey_sanitize( $journey );
	$rows    = array();
	$number  = 0;

	foreach ( $journey['rounds'] as $round ) {
		++$number;
		$rows[] = array(
			'round'       => $number,
			'ad'          => $round['ad'],
			'rating'      => $round['rating'],
			'translation' => $round['translation'],
			'refinement'  => $round['refinement'],
		);
	}

	return array(
		'request' => $journey['request'],
		'rounds'  => $rows,
	);
}
