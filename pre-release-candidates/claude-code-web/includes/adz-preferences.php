<?php
/**
 * Visitor preferences and refinement.
 *
 * A visitor says what they want in their own words and keeps narrowing it:
 * "couch", then "light colour, for two people", then "not leather". Each
 * refinement adds to what came before rather than replacing it, so the
 * conversation accumulates the way a conversation does.
 *
 * What is parsed out of that text is shown back as removable chips. That is
 * deliberate. The parser here is deterministic pattern matching, not language
 * understanding, and it will sometimes read a phrase wrongly. Showing the
 * visitor exactly what was understood lets them correct it in one click, which
 * is both more honest than a confident guess and more useful than a black box.
 *
 * Nothing here profiles anyone. The facets are what the visitor typed, held for
 * as long as they choose, and the plugin never infers an interest from
 * behaviour.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * An empty refinement set.
 *
 * @return array{include:string[],exclude:string[],attributes:string[],min_price:?int,max_price:?int,age:?int,capacity:?int,intent:string,raw:string[]}
 */
function adzworld_empty_preference() {
	return array(
		'include'    => array(),
		'exclude'    => array(),
		'attributes' => array(),
		'min_price'  => null,
		'max_price'  => null,
		'age'        => null,
		'capacity'   => null,
		'intent'     => '',
		'raw'        => array(),
	);
}

/**
 * Words carrying no search meaning, dropped from the include terms.
 *
 * @return string[]
 */
function adzworld_stop_words() {
	return array(
		'a',
		'an',
		'and',
		'are',
		'as',
		'at',
		'be',
		'but',
		'by',
		'for',
		'from',
		'i',
		'in',
		'is',
		'it',
		'me',
		'my',
		'of',
		'on',
		'only',
		'or',
		'please',
		'should',
		'thats',
		'the',
		'to',
		'want',
		'with',
		'yes',
		'right',
		'that',
		'would',
		'like',
		'need',
		'looking',
		'some',

		/*
		 * Facet nouns. "a light color" means the colour is light; the word
		 * "color" itself is not a thing anyone is shopping for, so it must not
		 * land in the include list and go on to match adz about paint.
		 */
		'color',
		'colour',
		'colored',
		'coloured',
		'size',
		'sized',
		'price',
		'priced',
		'condition',
		'material',
		'style',
		'kind',
		'type',
		'version',
		'option',
		'options',
	);
}

/**
 * Attribute words the parser recognises, grouped so a chip can say which kind.
 *
 * Deliberately a short, visible list rather than an open vocabulary. Anything
 * not listed stays an ordinary search term, which is the safe failure.
 *
 * @return array<string,string[]>
 */
function adzworld_attribute_vocabulary() {
	return array(
		'colour'    => array( 'light', 'dark', 'black', 'white', 'grey', 'gray', 'red', 'blue', 'green', 'brown', 'beige', 'cream', 'navy', 'pink', 'yellow' ),
		'material'  => array( 'leather', 'fabric', 'velvet', 'cotton', 'wool', 'linen', 'wood', 'oak', 'pine', 'metal', 'steel', 'plastic', 'glass' ),
		'condition' => array( 'new', 'used', 'refurbished', 'secondhand', 'vintage', 'antique' ),
		'audience'  => array( 'girls', 'boys', 'women', 'men', 'kids', 'children', 'adult', 'adults', 'unisex' ),
		'size'      => array( 'small', 'medium', 'large', 'compact', 'oversized', 'mini' ),
	);
}

/**
 * Verbs that say how the visitor wants to acquire the thing.
 *
 * @return array<string,string[]>
 */
function adzworld_intent_vocabulary() {
	return array(
		'buy'    => array( 'buy', 'purchase', 'purchasing', 'own' ),
		'lease'  => array( 'lease', 'leasing', 'finance', 'financing' ),
		'rent'   => array( 'rent', 'renting', 'hire', 'borrow' ),
		'repair' => array( 'repair', 'fix', 'service', 'mend' ),
	);
}

/**
 * Turn "50k", "$1,200", "2.5m" into an integer.
 *
 * @param string $amount Raw match.
 * @return int
 */
function adzworld_parse_amount( $amount ) {
	$amount = strtolower( str_replace( array( ',', '$', '£', '€', ' ' ), '', (string) $amount ) );

	$multiplier = 1;

	if ( str_ends_with( $amount, 'k' ) ) {
		$multiplier = 1000;
		$amount     = substr( $amount, 0, -1 );
	} elseif ( str_ends_with( $amount, 'm' ) ) {
		$multiplier = 1000000;
		$amount     = substr( $amount, 0, -1 );
	}

	return (int) round( (float) $amount * $multiplier );
}

/**
 * Read one line of refinement text into facets.
 *
 * Order matters: negations and numeric constraints are lifted out first, so
 * their words do not also land in the plain include terms. "I do not want to
 * lease a car" must not leave "lease" sitting in the things being searched for.
 *
 * @param string $text   What the visitor typed.
 * @param array  $onto   Existing preference to refine. Optional.
 * @return array The refined preference.
 */
function adzworld_parse_refinement( $text, $onto = null ) {
	$pref = is_array( $onto ) ? wp_parse_args( $onto, adzworld_empty_preference() ) : adzworld_empty_preference();

	$text = strtolower( wp_strip_all_tags( (string) $text ) );

	if ( '' === trim( $text ) ) {
		return $pref;
	}

	$pref['raw'][] = trim( $text );
	$working       = ' ' . preg_replace( '/[^a-z0-9$£€\s\.\-]/', ' ', $text ) . ' ';

	// ---- price floor and ceiling ----
	if ( preg_match( '/\b(?:above|over|more than|at least|from)\s+\$?([0-9][0-9\.,]*[km]?)/', $working, $m ) ) {
		$pref['min_price'] = adzworld_parse_amount( $m[1] );
		$working           = str_replace( $m[0], ' ', $working );
	}

	if ( preg_match( '/\b(?:under|below|less than|at most|up to|max(?:imum)?)\s+\$?([0-9][0-9\.,]*[km]?)/', $working, $m ) ) {
		$pref['max_price'] = adzworld_parse_amount( $m[1] );
		$working           = str_replace( $m[0], ' ', $working );
	}

	if ( preg_match( '/\bbetween\s+\$?([0-9][0-9\.,]*[km]?)\s+and\s+\$?([0-9][0-9\.,]*[km]?)/', $working, $m ) ) {
		$pref['min_price'] = adzworld_parse_amount( $m[1] );
		$pref['max_price'] = adzworld_parse_amount( $m[2] );
		$working           = str_replace( $m[0], ' ', $working );
	}

	// ---- age: "for a 9 year old", "9yo" ----
	if ( preg_match( '/\b([0-9]{1,2})\s*(?:year[\s\-]?old|yo)\b/', $working, $m ) ) {
		$pref['age'] = (int) $m[1];
		$working     = str_replace( $m[0], ' ', $working );
	}

	// ---- capacity: "for 2 ppl", "seats 4", "2 person" ----
	if ( preg_match( '/\b(?:for|seats?|sleeps?)\s+([0-9]{1,2})\s*(?:ppl|people|persons?|seats?)?\b/', $working, $m ) ) {
		$pref['capacity'] = (int) $m[1];
		$working          = str_replace( $m[0], ' ', $working );
	} elseif ( preg_match( '/\b([0-9]{1,2})\s*(?:ppl|people|persons?|seater)\b/', $working, $m ) ) {
		$pref['capacity'] = (int) $m[1];
		$working          = str_replace( $m[0], ' ', $working );
	}

	// ---- negation: everything up to the next clause boundary is excluded ----
	$negations = array( 'do not want', "don't want", 'dont want', 'do not', "don't", 'dont', 'not', 'no', 'without', 'except' );

	foreach ( $negations as $marker ) {
		$pattern = '/\b' . preg_quote( $marker, '/' ) . '\s+(?:to\s+)?(?:a\s+|an\s+|the\s+)?([a-z0-9\-]+(?:\s+[a-z0-9\-]+)?)/';

		while ( preg_match( $pattern, $working, $m ) ) {
			foreach ( preg_split( '/\s+/', trim( $m[1] ) ) as $word ) {
				if ( '' !== $word && ! in_array( $word, adzworld_stop_words(), true ) ) {
					$pref['exclude'][] = $word;
					// A term now excluded must not also be included.
					$pref['include'] = array_values( array_diff( $pref['include'], array( $word ) ) );
				}
			}

			$working = str_replace( $m[0], ' ', $working );
		}
	}

	// ---- intent ----
	foreach ( adzworld_intent_vocabulary() as $intent => $verbs ) {
		foreach ( $verbs as $verb ) {
			if ( preg_match( '/\b' . preg_quote( $verb, '/' ) . '\b/', $working ) ) {
				if ( ! in_array( $verb, $pref['exclude'], true ) ) {
					$pref['intent'] = $intent;
				}

				$working = preg_replace( '/\b' . preg_quote( $verb, '/' ) . '\b/', ' ', $working );
			}
		}
	}

	// ---- attributes ----
	foreach ( adzworld_attribute_vocabulary() as $words ) {
		foreach ( $words as $word ) {
			if ( preg_match( '/\b' . preg_quote( $word, '/' ) . '\b/', $working ) ) {
				if ( ! in_array( $word, $pref['exclude'], true ) && ! in_array( $word, $pref['attributes'], true ) ) {
					$pref['attributes'][] = $word;
				}

				$working = preg_replace( '/\b' . preg_quote( $word, '/' ) . '\b/', ' ', $working );
			}
		}
	}

	// ---- whatever is left is what they are looking for ----
	foreach ( preg_split( '/\s+/', trim( $working ) ) as $word ) {
		$word = trim( $word, '.-' );

		if ( '' === $word || is_numeric( $word ) || strlen( $word ) < 2 ) {
			continue;
		}

		if ( in_array( $word, adzworld_stop_words(), true ) ) {
			continue;
		}

		if ( in_array( $word, $pref['exclude'], true ) || in_array( $word, $pref['include'], true ) ) {
			continue;
		}

		$pref['include'][] = $word;
	}

	return $pref;
}

/**
 * Describe a preference as chips the visitor can read and remove.
 *
 * Each chip carries the facet and value so the form can drop exactly one
 * without the visitor retyping everything.
 *
 * @param array $pref Preference.
 * @return array<int,array{facet:string,value:string,label:string}>
 */
function adzworld_preference_chips( $pref ) {
	$pref  = wp_parse_args( (array) $pref, adzworld_empty_preference() );
	$chips = array();

	foreach ( $pref['include'] as $term ) {
		$chips[] = array(
			'facet' => 'include',
			'value' => $term,
			'label' => $term,
		);
	}

	foreach ( $pref['attributes'] as $term ) {
		$chips[] = array(
			'facet' => 'attributes',
			'value' => $term,
			'label' => $term,
		);
	}

	foreach ( $pref['exclude'] as $term ) {
		$chips[] = array(
			'facet' => 'exclude',
			'value' => $term,
			/* translators: %s: a term the visitor excluded. */
			'label' => sprintf( __( 'not %s', 'adz-world' ), $term ),
		);
	}

	if ( '' !== $pref['intent'] ) {
		$labels = array(
			'buy'    => __( 'to buy', 'adz-world' ),
			'lease'  => __( 'to lease', 'adz-world' ),
			'rent'   => __( 'to rent', 'adz-world' ),
			'repair' => __( 'to repair', 'adz-world' ),
		);

		$chips[] = array(
			'facet' => 'intent',
			'value' => $pref['intent'],
			'label' => isset( $labels[ $pref['intent'] ] ) ? $labels[ $pref['intent'] ] : $pref['intent'],
		);
	}

	if ( null !== $pref['min_price'] ) {
		$chips[] = array(
			'facet' => 'min_price',
			'value' => (string) $pref['min_price'],
			/* translators: %s: a formatted number. */
			'label' => sprintf( __( 'over %s', 'adz-world' ), number_format_i18n( $pref['min_price'] ) ),
		);
	}

	if ( null !== $pref['max_price'] ) {
		$chips[] = array(
			'facet' => 'max_price',
			'value' => (string) $pref['max_price'],
			/* translators: %s: a formatted number. */
			'label' => sprintf( __( 'under %s', 'adz-world' ), number_format_i18n( $pref['max_price'] ) ),
		);
	}

	if ( null !== $pref['age'] ) {
		$chips[] = array(
			'facet' => 'age',
			'value' => (string) $pref['age'],
			/* translators: %d: an age in years. */
			'label' => sprintf( __( 'age %d', 'adz-world' ), $pref['age'] ),
		);
	}

	if ( null !== $pref['capacity'] ) {
		$chips[] = array(
			'facet' => 'capacity',
			'value' => (string) $pref['capacity'],
			/* translators: %d: a number of people. */
			'label' => sprintf( __( 'for %d', 'adz-world' ), $pref['capacity'] ),
		);
	}

	return $chips;
}

/**
 * Remove one facet value from a preference.
 *
 * @param array  $pref  Preference.
 * @param string $facet Facet key.
 * @param string $value Value to drop.
 * @return array
 */
function adzworld_remove_facet( $pref, $facet, $value ) {
	$pref = wp_parse_args( (array) $pref, adzworld_empty_preference() );

	if ( in_array( $facet, array( 'include', 'exclude', 'attributes' ), true ) ) {
		$pref[ $facet ] = array_values( array_diff( $pref[ $facet ], array( (string) $value ) ) );

		return $pref;
	}

	if ( in_array( $facet, array( 'min_price', 'max_price', 'age', 'capacity' ), true ) ) {
		$pref[ $facet ] = null;

		return $pref;
	}

	if ( 'intent' === $facet ) {
		$pref['intent'] = '';
	}

	return $pref;
}

/**
 * Score an adz post against a preference.
 *
 * Exclusions are absolute: one excluded term present and the adz is never
 * shown, whatever else matches. Everything else accumulates.
 *
 * @param int   $ad_id Adz post ID.
 * @param array $pref  Preference.
 * @return int Score, or -1 when the adz is excluded outright.
 */
function adzworld_score_ad( $ad_id, $pref ) {
	$pref = wp_parse_args( (array) $pref, adzworld_empty_preference() );
	$post = get_post( (int) $ad_id );

	if ( ! $post instanceof WP_Post ) {
		return -1;
	}

	$terms = wp_get_post_terms( $post->ID, 'ad_taxonomy', array( 'fields' => 'names' ) );
	$terms = is_wp_error( $terms ) ? array() : $terms;

	$haystack = strtolower( $post->post_title . ' ' . wp_strip_all_tags( $post->post_content ) . ' ' . implode( ' ', $terms ) );

	foreach ( $pref['exclude'] as $term ) {
		if ( false !== strpos( $haystack, strtolower( $term ) ) ) {
			return -1;
		}
	}

	$score = 0;

	foreach ( $pref['include'] as $term ) {
		if ( false !== strpos( $haystack, strtolower( $term ) ) ) {
			$score += 10;
		}
	}

	foreach ( $pref['attributes'] as $term ) {
		if ( false !== strpos( $haystack, strtolower( $term ) ) ) {
			$score += 4;
		}
	}

	if ( '' !== $pref['intent'] && false !== strpos( $haystack, $pref['intent'] ) ) {
		$score += 3;
	}

	return $score;
}

/**
 * The adz that best match a preference, best first.
 *
 * @param array $pref  Preference.
 * @param int   $limit How many to return.
 * @return int[] Adz post IDs.
 */
function adzworld_match_adz( $pref, $limit = 5 ) {
	$scored  = array();
	$ordinal = 0;

	foreach ( adz_get_all_ad_ids() as $ad_id ) {
		$score = adzworld_score_ad( $ad_id, $pref );

		/*
		 * A negative score is an exclusion the visitor stated, and that is
		 * absolute. A zero score only means nothing matched, which is the
		 * normal case for a visitor who has not filled in the panel. Dropping
		 * those would leave the rotation empty whenever nobody has typed
		 * anything, so what a preference changes is which adz come first, not
		 * whether any adz appear at all.
		 */
		if ( $score < 0 ) {
			continue;
		}

		$scored[] = array(
			'id'      => $ad_id,
			'score'   => $score,
			'ordinal' => $ordinal,
		);

		++$ordinal;
	}

	// Highest score first, original order preserved within a tie. usort is only
	// stable from PHP 8.0, so the ordinal does the tie-breaking explicitly.
	usort(
		$scored,
		static function ( $a, $b ) {
			if ( $a['score'] === $b['score'] ) {
				return $a['ordinal'] <=> $b['ordinal'];
			}

			return $b['score'] <=> $a['score'];
		}
	);

	return array_slice( wp_list_pluck( $scored, 'id' ), 0, max( 1, (int) $limit ) );
}
