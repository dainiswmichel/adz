<?php
/**
 * One slot journey is in the rotation.
 *
 * The slot record is the rotation, the slot, the category, and the
 * refinements. The slot journey is the ads that then appear.
 *
 * Ad 1 is at that rotation, slot, category, and refinements. Each later ad
 * is at the same rotation and slot. A response on an ad is its category,
 * rating, and refinements.
 *
 * Internal feedback is sent to the site. External feedback keeps those three
 * fields and carries the modified string. This file does not call a provider
 * and does not choose the next ad.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keep a stated string whole.
 *
 * @param string $text Reader's text, or an ad id.
 * @return string
 */
function adzworld_slot_journey_text( $text ) {
	$text = wp_strip_all_tags( (string) $text );
	$text = str_replace( "\0", '', $text );
	$text = trim( $text );

	if ( defined( 'ADZWORLD_REFINEMENT_MAX_LENGTH' ) && function_exists( 'mb_substr' ) ) {
		$text = mb_substr( $text, 0, ADZWORLD_REFINEMENT_MAX_LENGTH );
	}

	return $text;
}

/**
 * Refinements as the reader set them.
 *
 * A list of phrases stays in that order, one space between phrases.
 *
 * @param mixed $refinements One phrase, or a list of phrases.
 * @return string
 */
function adzworld_slot_journey_refinements( $refinements ) {
	if ( is_array( $refinements ) ) {
		$parts = array();

		foreach ( $refinements as $phrase ) {
			$phrase = adzworld_slot_journey_text( $phrase );
			if ( '' !== $phrase ) {
				$parts[] = $phrase;
			}
		}

		return implode( ' ', $parts );
	}

	return adzworld_slot_journey_text( $refinements );
}

/**
 * The rating number, when the reader set one.
 *
 * Empty, a word, and a number outside -100 to 100 stay off the rating.
 * Zero stays zero.
 *
 * @param mixed $rating Rating number, or empty when the reader did not set one.
 * @return int|null
 */
function adzworld_slot_journey_rating( $rating ) {
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
			return null;
		}
	} else {
		return null;
	}

	if ( $number < -100 || $number > 100 ) {
		return null;
	}

	return $number;
}

/**
 * The modified string for an external slot.
 *
 * Category, rating number, refinements, one space between the parts that are present.
 *
 * @param string   $category    Category.
 * @param int|null $rating      Rating number, or null.
 * @param string   $refinements Refinements.
 * @return string
 */
function adzworld_slot_journey_string( $category, $rating, $refinements ) {
	$parts = array();

	if ( '' !== $category ) {
		$parts[] = $category;
	}

	if ( is_int( $rating ) ) {
		$parts[] = (string) $rating;
	}

	if ( '' !== $refinements ) {
		$parts[] = $refinements;
	}

	return implode( ' ', $parts );
}

/**
 * The sentence shown when internal feedback is sent to the site.
 *
 * @return string
 */
function adzworld_slot_journey_thanks() {
	return __( 'Thank you for your feedback.', 'adz-world' );
}

/**
 * One slot in a rotation, before any ad appears.
 *
 * @param string $rotation    Rotation name.
 * @param int    $slot        Zero-based slot index.
 * @param string $category    Opening category.
 * @param mixed  $refinements Opening refinements.
 * @return array{rotation:string,slot:int,category:string,refinements:string,journey:array<int,array{ad:string,feedback:?array}>}
 */
function adzworld_slot_journey_start( $rotation, $slot, $category, $refinements ) {
	return array(
		'rotation'    => adzworld_slot_journey_text( $rotation ),
		'slot'        => (int) $slot,
		'category'    => adzworld_slot_journey_text( $category ),
		'refinements' => adzworld_slot_journey_refinements( $refinements ),
		'journey'     => array(),
	);
}

/**
 * Append the ad that appeared in this slot.
 *
 * @param array<string,mixed> $journey Slot record.
 * @param string              $ad      Ad id.
 * @return array<string,mixed>
 */
function adzworld_slot_journey_show( $journey, $ad ) {
	$journey = adzworld_slot_journey_sanitize( $journey );
	$ad      = adzworld_slot_journey_text( $ad );

	if ( '' === $ad ) {
		return $journey;
	}

	$journey['journey'][] = array(
		'ad'       => $ad,
		'feedback' => null,
	);

	return $journey;
}

/**
 * Store the response on the ad that just appeared.
 *
 * Internal feedback is sent to the site. External feedback keeps the three
 * fields and adds the modified string.
 *
 * @param array<string,mixed> $journey     Slot record.
 * @param string              $category    Category the reader set.
 * @param mixed               $rating      Rating number, or empty.
 * @param mixed               $refinements Refinements the reader set.
 * @param bool                $external    True when the modified string goes to an API.
 * @return array<string,mixed>
 */
function adzworld_slot_journey_respond( $journey, $category, $rating, $refinements, $external = false ) {
	$journey     = adzworld_slot_journey_sanitize( $journey );
	$category    = adzworld_slot_journey_text( $category );
	$refinements = adzworld_slot_journey_refinements( $refinements );
	$number      = adzworld_slot_journey_rating( $rating );
	$last        = count( $journey['journey'] ) - 1;

	if ( $last < 0 || ( '' === $category && null === $number && '' === $refinements ) ) {
		return $journey;
	}

	$feedback = array(
		'category'    => $category,
		'rating'      => $number,
		'refinements' => $refinements,
	);

	if ( $external ) {
		$feedback['string'] = adzworld_slot_journey_string( $category, $number, $refinements );
	} else {
		$feedback['site'] = true;
	}

	$journey['journey'][ $last ]['feedback'] = $feedback;

	return $journey;
}

/**
 * Reduce stored data to the slot record.
 *
 * @param mixed $stored Raw value.
 * @return array{rotation:string,slot:int,category:string,refinements:string,journey:array<int,array{ad:string,feedback:?array}>}
 */
function adzworld_slot_journey_sanitize( $stored ) {
	$rotation    = '';
	$slot        = 0;
	$category    = '';
	$refinements = '';

	if ( is_array( $stored ) ) {
		if ( isset( $stored['rotation'] ) ) {
			$rotation = adzworld_slot_journey_text( $stored['rotation'] );
		}
		if ( isset( $stored['slot'] ) && is_numeric( $stored['slot'] ) ) {
			$slot = (int) $stored['slot'];
		}
		if ( isset( $stored['category'] ) ) {
			$category = adzworld_slot_journey_text( $stored['category'] );
		}
		if ( isset( $stored['refinements'] ) ) {
			$refinements = adzworld_slot_journey_refinements( $stored['refinements'] );
		}
	}

	$journey = adzworld_slot_journey_start( $rotation, $slot, $category, $refinements );
	$rows    = ( is_array( $stored ) && isset( $stored['journey'] ) && is_array( $stored['journey'] ) ) ? $stored['journey'] : array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$ad = isset( $row['ad'] ) ? adzworld_slot_journey_text( $row['ad'] ) : '';
		if ( '' === $ad ) {
			continue;
		}

		$feedback = null;
		if ( isset( $row['feedback'] ) && is_array( $row['feedback'] ) ) {
			$feedback_category    = isset( $row['feedback']['category'] ) ? adzworld_slot_journey_text( $row['feedback']['category'] ) : '';
			$feedback_refinements = isset( $row['feedback']['refinements'] ) ? adzworld_slot_journey_refinements( $row['feedback']['refinements'] ) : '';
			$feedback_rating      = array_key_exists( 'rating', $row['feedback'] ) ? adzworld_slot_journey_rating( $row['feedback']['rating'] ) : null;

			if ( '' !== $feedback_category || null !== $feedback_rating || '' !== $feedback_refinements ) {
				$feedback = array(
					'category'    => $feedback_category,
					'rating'      => $feedback_rating,
					'refinements' => $feedback_refinements,
				);

				if ( ! empty( $row['feedback']['string'] ) ) {
					$feedback['string'] = adzworld_slot_journey_string( $feedback_category, $feedback_rating, $feedback_refinements );
				} else {
					$feedback['site'] = true;
				}
			}
		}

		$journey['journey'][] = array(
			'ad'       => $ad,
			'feedback' => $feedback,
		);
	}

	return $journey;
}

/**
 * Where one ad in the slot journey sits.
 *
 * The first ad sits at the rotation, the slot, the category, and the refinements.
 * Each later ad sits at the rotation and the slot.
 *
 * @param array<string,mixed> $journey Slot record.
 * @param int                 $index   Zero-based ad index.
 * @return array<string,mixed>
 */
function adzworld_slot_journey_where( $journey, $index ) {
	$where = array(
		'rotation' => $journey['rotation'],
		'slot'     => $journey['slot'],
	);

	if ( 0 === (int) $index ) {
		$where['category']    = $journey['category'];
		$where['refinements'] = $journey['refinements'];
	}

	return $where;
}

/**
 * The slot record, with the journey in order.
 *
 * @param array<string,mixed> $journey Slot record.
 * @return array{rotation:string,slot:int,category:string,refinements:string,journey:array<int,array{ad:string,where:array<string,mixed>,feedback:?array}>}
 */
function adzworld_slot_journey_record( $journey ) {
	$journey = adzworld_slot_journey_sanitize( $journey );
	$rows    = array();
	$index   = 0;

	foreach ( $journey['journey'] as $row ) {
		$rows[] = array(
			'ad'       => $row['ad'],
			'where'    => adzworld_slot_journey_where( $journey, $index ),
			'feedback' => $row['feedback'],
		);
		++$index;
	}

	return array(
		'rotation'    => $journey['rotation'],
		'slot'        => $journey['slot'],
		'category'    => $journey['category'],
		'refinements' => $journey['refinements'],
		'journey'     => $rows,
	);
}
