<?php
/**
 * Visitor preferences and refinement.
 *
 * A visitor says what they want in their own words and keeps narrowing it:
 * "couch", then "light colour, for two people", then "not leather". Each
 * refinement adds to what came before rather than replacing it, so the
 * conversation accumulates the way a conversation does.
 *
 * Categories and refinements remain the visitor's own search strings. The
 * parser is deterministic pattern matching used only to rank possible adz; it
 * does not rename the visitor's entries or present an inferred interpretation.
 *
 * Nothing here profiles anyone. The facets are what the visitor typed, held for
 * as long as they choose, and the plugin never infers an interest from
 * behaviour.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/** Maximum number of words in a user-created adz category. */
const ADZWORLD_CATEGORY_MAX_WORDS = 3;

/** Maximum number of characters in a user-created adz category. */
const ADZWORLD_CATEGORY_MAX_LENGTH = 48;

/** Maximum number of visible result slots a category can request. */
const ADZWORLD_CATEGORY_MAX_SLOTS = 8;

/**
 * Normalize a user-created category without interpreting it.
 *
 * @param string $category Category text.
 * @return string Normalized category.
 */
function adzworld_normalize_category( $category ) {
	$category = trim( sanitize_text_field( $category ) );

	return preg_replace( '/\s+/u', ' ', $category );
}

/**
 * Whether a category fits the short search-prefix contract.
 *
 * @param string $category Normalized category.
 * @return bool Whether the category is valid.
 */
function adzworld_valid_category( $category ) {
	$words = preg_split( '/\s+/u', $category, -1, PREG_SPLIT_NO_EMPTY );

	return '' !== $category
		&& mb_strlen( $category ) <= ADZWORLD_CATEGORY_MAX_LENGTH
		&& count( $words ) <= ADZWORLD_CATEGORY_MAX_WORDS;
}

/**
 * An empty refinement set.
 *
 * @return array{include:string[],exclude:string[],attributes:string[],min_price:?int,max_price:?int,age:?int,capacity:?int,intent:string,raw:string[]}
 */
function adzworld_empty_preference() {
	// Plugin Check otherwise mistakes this preference facet for a WP_Query argument.
	$exclude_key = 'exclude';

	return array(
		'categories'           => array(),
		'category_refinements' => array(),
		'category_slots'       => array(),
		'active_category'      => '',
		'include'              => array(),
		$exclude_key           => array(),
		'attributes'           => array(),
		'min_price'            => null,
		'max_price'            => null,
		'age'                  => null,
		'capacity'             => null,
		'intent'               => '',
		'raw'                  => array(),
	);
}

/**
 * Return the requested number of result slots for a category.
 *
 * A missing entry means one slot, so existing preferences remain compact.
 *
 * @param array  $pref     Preference hierarchy.
 * @param string $category Category.
 * @return int Slot count.
 */
function adzworld_category_slot_count( $pref, $category ) {
	foreach ( $pref['category_slots'] as $stored ) {
		$parts = explode( '--', $stored, 2 );
		if ( 2 === count( $parts ) && $category === $parts[0] ) {
			return max( 1, min( ADZWORLD_CATEGORY_MAX_SLOTS, (int) $parts[1] ) );
		}
	}

	return 1;
}

/**
 * Set the requested number of result slots for a category.
 *
 * @param array  $pref     Preference hierarchy.
 * @param string $category Category.
 * @param int    $count    Requested slots.
 * @return array Updated preference hierarchy.
 */
function adzworld_set_category_slot_count( $pref, $category, $count ) {
	$count = max( 1, min( ADZWORLD_CATEGORY_MAX_SLOTS, (int) $count ) );

	$pref['category_slots'] = array_values(
		array_filter(
			$pref['category_slots'],
			static function ( $stored ) use ( $category ) {
				return 0 !== strpos( $stored, $category . '--' );
			}
		)
	);

	if ( $count > 1 ) {
		$pref['category_slots'][] = $category . '--' . $count;
	}

	return $pref;
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

	if ( 'k' === substr( $amount, -1 ) ) {
		$multiplier = 1000;
		$amount     = substr( $amount, 0, -1 );
	} elseif ( 'm' === substr( $amount, -1 ) ) {
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

	$authored = isset( $pref['authored_categories'] ) && is_array( $pref['authored_categories'] )
		? $pref['authored_categories']
		: $pref['categories'];

	foreach ( $pref['categories'] as $category ) {
		$chips[] = array(
			'facet'    => 'categories',
			'value'    => $category,
			'label'    => $category,
			'active'   => $category === $pref['active_category'],
			'slots'    => adzworld_category_slot_count( $pref, $category ),
			'authored' => in_array( $category, $authored, true ),
		);
	}

	foreach ( $pref['category_refinements'] as $category_refinement ) {
		$parts   = explode( '--', $category_refinement, 2 );
		$label   = isset( $parts[1] ) ? $parts[1] : $parts[0];
		$chips[] = array(
			'facet' => 'category_refinements',
			'value' => $category_refinement,
			'label' => $label,
		);
	}

	/*
	 * Once structured categories exist, they and their attached refinements are
	 * the complete visible hierarchy. Parsed search terms still drive matching,
	 * but repeating every word as another pill makes the interface ambiguous.
	 */
	if ( ! empty( $pref['categories'] ) ) {
		return $chips;
	}

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

	foreach ( $pref['raw'] as $phrase ) {
		if ( false === strpos( trim( $phrase ), ' ' ) ) {
			continue;
		}
		$chips[] = array(
			'facet' => 'raw',
			'value' => $phrase,
			'label' => $phrase,
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

	if ( in_array( $facet, array( 'categories', 'category_refinements', 'include', 'exclude', 'attributes' ), true ) ) {
		$pref[ $facet ] = array_values( array_diff( $pref[ $facet ], array( (string) $value ) ) );
		if ( 'categories' === $facet ) {
			$prefix                       = (string) $value . '--';
			$pref['category_refinements'] = array_values(
				array_filter(
					$pref['category_refinements'],
					static function ( $refinement ) use ( $prefix ) {
						return 0 !== strpos( $refinement, $prefix );
					}
				)
			);
			$pref['category_slots']       = array_values(
				array_filter(
					$pref['category_slots'],
					static function ( $stored ) use ( $prefix ) {
						return 0 !== strpos( $stored, $prefix );
					}
				)
			);
			if ( $pref['active_category'] === $value ) {
				$pref['active_category'] = isset( $pref['categories'][0] ) ? $pref['categories'][0] : '';
			}
		}

		return $pref;
	}

	if ( in_array( $facet, array( 'min_price', 'max_price', 'age', 'capacity' ), true ) ) {
		$pref[ $facet ] = null;

		return $pref;
	}

	if ( 'intent' === $facet ) {
		$pref['intent'] = '';
	}

	if ( 'raw' === $facet ) {
		$raw  = array_values( array_diff( $pref['raw'], array( (string) $value ) ) );
		$pref = adzworld_empty_preference();
		foreach ( $raw as $phrase ) {
			$pref = adzworld_parse_refinement( $phrase, $pref );
		}
	}

	return $pref;
}

/**
 * Rename a category and keep its refinements attached.
 *
 * @param array  $pref Preference hierarchy.
 * @param string $old  Existing category.
 * @param string $replacement Replacement category.
 * @return array Updated preference hierarchy.
 */
function adzworld_rename_category( $pref, $old, $replacement ) {
	$pref        = wp_parse_args( (array) $pref, adzworld_empty_preference() );
	$old         = adzworld_normalize_category( $old );
	$replacement = adzworld_normalize_category( $replacement );

	foreach ( $pref['categories'] as $index => $category ) {
		if ( $old === $category ) {
			$pref['categories'][ $index ] = $replacement;
		}
	}
	$pref['categories'] = array_values( array_unique( $pref['categories'] ) );

	$old_prefix = $old . '--';
	foreach ( $pref['category_refinements'] as $index => $refinement ) {
		if ( 0 === strpos( $refinement, $old_prefix ) ) {
			$pref['category_refinements'][ $index ] = $replacement . '--' . substr( $refinement, strlen( $old_prefix ) );
		}
	}
	$pref['category_refinements'] = array_values( array_unique( $pref['category_refinements'] ) );
	foreach ( $pref['category_slots'] as $index => $stored ) {
		if ( 0 === strpos( $stored, $old_prefix ) ) {
			$pref['category_slots'][ $index ] = $replacement . '--' . substr( $stored, strlen( $old_prefix ) );
		}
	}

	if ( $old === $pref['active_category'] ) {
		$pref['active_category'] = $replacement;
	}

	$old_facets = adzworld_parse_refinement( $old );
	foreach ( array( 'include', 'exclude', 'attributes' ) as $facet ) {
		$pref[ $facet ] = array_values( array_diff( $pref[ $facet ], $old_facets[ $facet ] ) );
	}
	$pref['raw'] = array_values( array_diff( $pref['raw'], array( strtolower( $old ) ) ) );

	return adzworld_parse_refinement( $replacement, $pref );
}

/**
 * Reduce a category word to a small comparison form.
 *
 * This is not presented as language understanding. It only lets common plural
 * labels such as "couches" match the singular word used by an advertisement.
 *
 * @param string $word Category word.
 * @return string Comparison word.
 */
function adzworld_normalize_category_word( $word ) {
	$word = sanitize_key( $word );

	if ( strlen( $word ) > 4 && 'ies' === substr( $word, -3 ) ) {
		return substr( $word, 0, -3 ) . 'y';
	}

	if ( strlen( $word ) > 4 && preg_match( '/(?:ches|shes|xes|zes|ses)$/D', $word ) ) {
		return substr( $word, 0, -2 );
	}

	if ( strlen( $word ) > 3 && 's' === substr( $word, -1 ) && 'ss' !== substr( $word, -2 ) ) {
		return substr( $word, 0, -1 );
	}

	return $word;
}

/**
 * Whether the meaningful words in a category occur in an advertisement.
 *
 * @param string $category Category supplied through the invitation hub.
 * @param string $text     Advertisement title, body, and taxonomy text.
 * @return bool Whether the category describes the advertisement.
 */
function adzworld_category_matches_text( $category, $text ) {
	$parsed = adzworld_parse_refinement( $category );
	$terms  = $parsed['include'];

	if ( empty( $terms ) ) {
		$terms = preg_split( '/[^a-z0-9]+/', strtolower( wp_strip_all_tags( (string) $category ) ), -1, PREG_SPLIT_NO_EMPTY );
	}

	$words = preg_split( '/[^a-z0-9]+/', strtolower( wp_strip_all_tags( (string) $text ) ), -1, PREG_SPLIT_NO_EMPTY );
	$words = array_unique( array_map( 'adzworld_normalize_category_word', $words ) );

	foreach ( $terms as $term ) {
		if ( ! in_array( adzworld_normalize_category_word( $term ), $words, true ) ) {
			return false;
		}
	}

	return ! empty( $terms );
}

/**
 * Resolve the category represented by a local advertisement.
 *
 * A category the visitor typed takes precedence when its words are in the ad.
 * Otherwise the ad is its own category: one published ad, one thing.
 *
 * @param WP_Post $post Advertisement post.
 * @param array   $pref Current preference hierarchy.
 * @return string Resolved category, or an empty string when none is available.
 */
function adzworld_ad_category( $post, $pref ) {
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$pref     = wp_parse_args( (array) $pref, adzworld_empty_preference() );
	$terms    = wp_get_post_terms( $post->ID, 'ad_taxonomy', array( 'fields' => 'names' ) );
	$terms    = is_wp_error( $terms ) ? array() : $terms;
	$text     = $post->post_title . ' ' . wp_strip_all_tags( $post->post_content ) . ' ' . implode( ' ', $terms );
	$authored = isset( $pref['authored_categories'] ) && is_array( $pref['authored_categories'] ) ? $pref['authored_categories'] : array();

	foreach ( $authored as $category ) {
		if ( adzworld_category_matches_text( $category, $text ) ) {
			return $category;
		}
	}

	$thing = adzworld_ad_thing( $post );

	if ( '' !== $thing ) {
		return $thing;
	}

	foreach ( $pref['categories'] as $category ) {
		if ( adzworld_category_matches_text( $category, $text ) ) {
			return $category;
		}
	}

	return '';
}

/**
 * The one thing an advertisement is: its category, which starts with this ad.
 *
 * A published ad is a category that holds a single ad. The label is the thing
 * itself, such as couch, car, or bicycle, not the sentence around it.
 *
 * @param WP_Post $post Advertisement post.
 * @return string Category label, or an empty string.
 */
function adzworld_ad_thing( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$terms = wp_get_post_terms( $post->ID, 'ad_taxonomy', array( 'fields' => 'names' ) );
	$terms = is_wp_error( $terms ) ? array() : $terms;

	if ( ! empty( $terms ) ) {
		$term = adzworld_normalize_category( (string) reset( $terms ) );

		if ( adzworld_valid_category( $term ) ) {
			return $term;
		}
	}

	$parsed  = adzworld_parse_refinement( $post->post_title );
	$residue = array( 'year', 'years', 'old', 'yo', 'aged', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve' );
	$words   = array();

	foreach ( $parsed['include'] as $word ) {
		if ( is_numeric( $word ) || in_array( $word, $residue, true ) ) {
			continue;
		}

		$words[] = $word;
	}

	if ( empty( $words ) ) {
		return '';
	}

	$thing = adzworld_normalize_category( (string) end( $words ) );

	return adzworld_valid_category( $thing ) ? $thing : '';
}

/**
 * Category labels already present because a published ad is that category.
 *
 * @return string[]
 */
function adzworld_catalog_categories() {
	static $names = null;

	if ( is_array( $names ) ) {
		return $names;
	}

	$names = array();
	if ( ! function_exists( 'get_posts' ) ) {
		return $names;
	}
	$posts = get_posts(
		array(
			'post_type'              => 'adz_ad',
			'post_status'            => 'publish',
			'posts_per_page'         => 50,
			'orderby'                => 'date',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $posts as $post ) {
		$thing = adzworld_ad_thing( $post );

		if ( '' !== $thing && ! in_array( $thing, $names, true ) ) {
			$names[] = $thing;
		}
	}

	return $names;
}

/**
 * Show catalog categories with the visitor's own categories.
 *
 * Catalog names are not written back as something the visitor typed.
 *
 * @param array $pref Stored preference.
 * @return array Preference used for display and for accepting a refinement.
 */
function adzworld_preference_with_catalog( $pref ) {
	$pref                        = wp_parse_args( (array) $pref, adzworld_empty_preference() );
	$pref['authored_categories'] = $pref['categories'];

	foreach ( adzworld_catalog_categories() as $category ) {
		if ( ! in_array( $category, $pref['categories'], true ) ) {
			$pref['categories'][] = $category;
		}
	}

	if ( '' === $pref['active_category'] && ! empty( $pref['categories'] ) ) {
		$pref['active_category'] = $pref['categories'][0];
	}

	return $pref;
}

/**
 * Build the refinement facets that apply to one category only.
 *
 * @param array  $pref     Current preference hierarchy.
 * @param string $category Advertisement category.
 * @return array Category-scoped parsed preference.
 */
function adzworld_category_refinement_preference( $pref, $category ) {
	$scoped = adzworld_empty_preference();
	$prefix = (string) $category . '--';

	foreach ( $pref['category_refinements'] as $stored ) {
		if ( 0 === strpos( $stored, $prefix ) ) {
			$scoped = adzworld_parse_refinement( substr( $stored, strlen( $prefix ) ), $scoped );
		}
	}

	return $scoped;
}

/**
 * Score an adz post against a preference.
 *
 * Exclusions are absolute: one excluded term present and the adz is never
 * shown, whatever else matches. Everything else accumulates.
 *
 * @param int   $ad_id adz post ID.
 * @param array $pref  Preference.
 * @return int Score, or -1 when the adz is excluded outright.
 */
function adzworld_score_ad( $ad_id, $pref ) {
	$pref = wp_parse_args( (array) $pref, adzworld_empty_preference() );
	$post = get_post( (int) $ad_id );

	if ( ! $post instanceof WP_Post ) {
		return -1;
	}

	$terms    = wp_get_post_terms( $post->ID, 'ad_taxonomy', array( 'fields' => 'names' ) );
	$terms    = is_wp_error( $terms ) ? array() : $terms;
	$haystack = strtolower( $post->post_title . ' ' . wp_strip_all_tags( $post->post_content ) . ' ' . implode( ' ', $terms ) );
	$category = adzworld_ad_category( $post, $pref );
	$scoped   = adzworld_category_refinement_preference( $pref, $category );

	foreach ( array_merge( $pref['exclude'], $scoped['exclude'] ) as $term ) {
		if ( false !== strpos( $haystack, strtolower( $term ) ) ) {
			return -1;
		}
	}

	/*
	 * A baseline keeps an explicit dislike as a demotion rather than turning it
	 * into the absolute exclusion reserved for words the visitor ruled out.
	 */
	$score = 10;
	if ( in_array( $category, $pref['categories'], true ) ) {
		$score += 12;
	}

	foreach ( array_merge( $pref['include'], $scoped['include'] ) as $term ) {
		if ( false !== strpos( $haystack, strtolower( $term ) ) ) {
			$score += 10;
		}
	}

	foreach ( array_merge( $pref['attributes'], $scoped['attributes'] ) as $term ) {
		if ( false !== strpos( $haystack, strtolower( $term ) ) ) {
			$score += 4;
		}
	}

	if ( '' !== $pref['intent'] && false !== strpos( $haystack, $pref['intent'] ) ) {
		$score += 3;
	}

	$feedback = function_exists( 'adzworld_current_feedback' ) ? adzworld_current_feedback() : array();
	$key      = sanitize_key( 'ad-' . $post->ID );
	if ( isset( $feedback[ $key ]['score'] ) ) {
		$score += (int) round( $feedback[ $key ]['score'] / 10 );
	}

	return max( 0, $score );
}

/**
 * Published adz, newest first, as post IDs.
 *
 * Nothing defined this while adzworld_match_adz() called it, so matching
 * fatalled the moment a rotation tried to pick an adz. It was missed because the
 * test harness stubbed the name, which meant the tests passed against a function
 * the plugin did not have.
 *
 * get_posts() is asked for post objects rather than ids on purpose: that primes
 * the post cache, so the get_post() call adzworld_score_ad() makes for each
 * candidate is served from memory instead of becoming one query per adz. The
 * pool is capped and filterable, because scoring every adz on a site with tens
 * of thousands of them is not work to do during a page render.
 *
 * @return int[]
 */
function adzworld_get_all_ad_ids() {
	$cached = wp_cache_get( 'adzworld_all_ad_ids', 'adz-world' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	/**
	 * How many adz may be scored for one visitor.
	 *
	 * @param int $size Pool size.
	 */
	$size = (int) apply_filters( 'adzworld_match_pool_size', 200 );
	$size = max( 1, min( 2000, $size ) );

	$posts = get_posts(
		array(
			'post_type'        => 'adz_ad',
			'post_status'      => 'publish',
			'posts_per_page'   => $size,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		)
	);

	$ids = array_map( 'absint', wp_list_pluck( $posts, 'ID' ) );

	wp_cache_set( 'adzworld_all_ad_ids', $ids, 'adz-world', 5 * MINUTE_IN_SECONDS );

	return $ids;
}

add_action( 'save_post_adz_ad', 'adzworld_flush_ad_pool' );
add_action( 'deleted_post', 'adzworld_flush_ad_pool' );
add_action( 'trashed_post', 'adzworld_flush_ad_pool' );
add_action( 'untrashed_post', 'adzworld_flush_ad_pool' );
/**
 * Drop the cached pool when the set of published adz can have changed.
 *
 * Bound to the trash transitions and deletion as well as save, because an adz
 * leaving the pool matters as much as one joining it.
 *
 * @return void
 */
function adzworld_flush_ad_pool() {
	wp_cache_delete( 'adzworld_all_ad_ids', 'adz-world' );
}

/**
 * The adz that best match a preference, best first.
 *
 * @param array $pref  Preference.
 * @param int   $limit How many to return.
 * @return int[] adz post IDs.
 */
function adzworld_match_adz( $pref, $limit = 5 ) {
	$scored  = array();
	$ordinal = 0;

	foreach ( adzworld_get_all_ad_ids() as $ad_id ) {
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
