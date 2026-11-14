<?php
/**
 * AdzLogs.
 *
 * The page lists rotations as accordion bands, then the slots inside them.
 * When a slot is configured as another rotation, the slot says which rotation
 * it showed, and the turns under it are what was served there. Each turn
 * carries the full Michel Time Stamp. A from/to pair of those stamps exports
 * or archives that range. The rotations stay on the page.
 *
 * A turn is a chat line. The served ad is on the left. The right side is how
 * the slot is configured, the request, and the refinements. A user response
 * is optional and sits on that same side when one was received.
 *
 * A rotation that sits in a slot is written twice, at the same Michel Time
 * Stamp: once under the outer rotation, and once under the inner rotation.
 *
 * The stamp is YYYYy-MMm-DDd-UTCHHh-MMm-SSs, for example
 * 2026y-11m-14d-UTC22h-05m-09s.
 *
 * This file records the display and the response. It does not call a provider
 * and it does not choose the next ad.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Michel Time Stamp in UTC.
 *
 * @param int|null $unix Unix time. Null uses the current time.
 * @return string
 */
function adzworld_michel_timestamp( $unix = null ) {
	if ( null === $unix ) {
		$unix = time();
	}

	$unix = (int) $unix;

	return gmdate( 'Y', $unix ) . 'y-' . gmdate( 'm', $unix ) . 'm-' . gmdate( 'd', $unix ) . 'd-UTC' . gmdate( 'H', $unix ) . 'h-' . gmdate( 'i', $unix ) . 'm-' . gmdate( 's', $unix ) . 's';
}

/**
 * Rotation and slot names used as log keys.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function adzworld_logs_key( $value ) {
	$value = strtolower( adzworld_slot_journey_text( $value ) );
	$value = preg_replace( '/[^a-z0-9_-]/', '', $value );

	if ( ! is_string( $value ) ) {
		return '';
	}

	return substr( $value, 0, 50 );
}

/**
 * Ad id that a slot can serve.
 *
 * @param mixed $value Raw id.
 * @return string `ad-11` or `source-ebay`, or empty.
 */
function adzworld_logs_ad_id( $value ) {
	$value = adzworld_slot_journey_text( $value );

	if ( preg_match( '/^(ad-[1-9][0-9]*|source-[a-z0-9_-]{1,50})$/D', $value ) ) {
		return $value;
	}

	return '';
}

/**
 * The product identifiers a provider actually served in this round.
 *
 * Each is the provider's own identifier namespaced by the provider, such as
 * ebay:110588014268. Without these, every round a provider answered logs as
 * the same row: the provider is named, the product is not.
 *
 * @since 1.0.0
 *
 * @param mixed $value Raw identifiers.
 * @return string Space separated identifiers, or empty.
 */
function adzworld_logs_items( $value ) {
	$clean = array();

	foreach ( (array) $value as $id ) {
		$id = adzworld_slot_journey_text( $id );

		if ( preg_match( '/^[a-z0-9_-]{1,50}:[A-Za-z0-9._-]{1,100}$/D', $id ) && ! in_array( $id, $clean, true ) ) {
			$clean[] = $id;
		}
	}

	return implode( ' ', $clean );
}

/**
 * What was actually on the screen, as the log should name it.
 *
 * A provider slot is configured as source:ebay and its feedback is keyed on
 * source-ebay, but neither of those is the adzad. The adzad is the listing
 * the reader was looking at. When the provider's cards are known, they are
 * the identity; otherwise the slot key stands, because something was shown
 * and the log should not pretend nothing was.
 *
 * @since 1.0.0
 *
 * @param array<string,mixed> $row Log row.
 * @return string
 */
function adzworld_logs_ad_shown( $row ) {
	$items = isset( $row['items'] ) ? adzworld_logs_items( explode( ' ', (string) $row['items'] ) ) : '';

	if ( '' !== $items ) {
		return $items;
	}

	return isset( $row['ad'] ) ? adzworld_logs_ad_id( $row['ad'] ) : '';
}

/**
 * Slot configuration token.
 *
 * @param mixed $value Raw token.
 * @return string `ad:11`, `source:ebay`, or `r:bikes`, or empty.
 */
function adzworld_logs_configured( $value ) {
	$value = adzworld_slot_journey_text( $value );

	if ( preg_match( '/^(ad:[1-9][0-9]*|source:[a-z0-9_-]{1,50}|r:[a-z0-9_-]{1,50})$/D', $value ) ) {
		return $value;
	}

	return '';
}

/**
 * Like/dislike word for a rating number.
 *
 * The bands are the existing evaluation labels. An empty rating has no word.
 *
 * @param int|null $rating Rating number, or null.
 * @return string
 */
function adzworld_logs_rating_word( $rating ) {
	if ( ! is_int( $rating ) || ! function_exists( 'adzworld_feedback_label' ) ) {
		return '';
	}

	return adzworld_slot_journey_text( adzworld_feedback_label( $rating ) );
}

/**
 * Category after a change.
 *
 * @param string $displayed Category on the served ad.
 * @param string $change    Category the reader set, or empty.
 * @return string
 */
function adzworld_logs_current_category( $displayed, $change ) {
	$change = adzworld_slot_journey_text( $change );

	if ( '' !== $change ) {
		return $change;
	}

	return adzworld_slot_journey_text( $displayed );
}

/**
 * Rating number after a change.
 *
 * @param int|null $displayed Rating on the served ad, or null.
 * @param int|null $change    Rating the reader set, or null.
 * @return int|null
 */
function adzworld_logs_current_rating( $displayed, $change ) {
	if ( is_int( $change ) ) {
		return $change;
	}

	if ( is_int( $displayed ) ) {
		return $displayed;
	}

	return null;
}

/**
 * Refinement string after a change.
 *
 * The typed change is kept whole and appended to the refinements that were
 * already on the ad.
 *
 * @param mixed  $displayed Refinements on the served ad.
 * @param string $change    Refinement the reader set, or empty.
 * @return string
 */
function adzworld_logs_current_refinements( $displayed, $change ) {
	$displayed = adzworld_slot_journey_refinements( $displayed );
	$change    = adzworld_slot_journey_text( $change );

	if ( '' === $change || $displayed === $change ) {
		return $displayed;
	}

	if ( '' === $displayed ) {
		return $change;
	}

	return $displayed . ' ' . $change;
}

/**
 * String sent to an API for one response.
 *
 * The parts are the ad id, the most up to date category, the Like/dislike
 * word, and the most up to date refinement string. The rating number stays
 * off this string.
 *
 * @param string   $ad          Ad id.
 * @param string   $category    Most up to date category.
 * @param int|null $rating      Most up to date rating number, or null.
 * @param string   $refinements Most up to date refinement string.
 * @return string
 */
function adzworld_logs_sent_string( $ad, $category, $rating, $refinements ) {
	$parts = array();

	/*
	 * Either shape is valid here: the slot key for an adzad this site owns,
	 * or the provider's own listing identifier when the cards are known.
	 * Validating only the first silently dropped the listing, which is the
	 * more useful of the two.
	 */
	$ad = '' !== adzworld_logs_ad_id( $ad )
		? adzworld_logs_ad_id( $ad )
		: adzworld_logs_items( explode( ' ', (string) $ad ) );

	if ( '' !== $ad ) {
		$parts[] = $ad;
	}

	$category = adzworld_slot_journey_text( $category );
	if ( '' !== $category ) {
		$parts[] = $category;
	}

	$word = adzworld_logs_rating_word( is_int( $rating ) ? $rating : null );
	if ( '' !== $word ) {
		$parts[] = $word;
	}

	$refinements = adzworld_slot_journey_refinements( $refinements );
	if ( '' !== $refinements ) {
		$parts[] = $refinements;
	}

	return implode( ' ', $parts );
}

/**
 * The log rows for one appearance.
 *
 * A plain slot returns one row. A slot that holds a rotation returns two
 * rows with the same stamp: the outer rotation, then the inner rotation.
 * The outer row's configuration is the rotation slot. The inner row keeps
 * the configuration of the slot that served the ad.
 *
 * @param array<string,mixed> $place        Rotation, slot, and the inner rotation when the slot holds one.
 * @param string              $ad           Ad id.
 * @param string              $configured   Slot configuration of the serving slot.
 * @param string              $category     Category displayed with the ad.
 * @param mixed               $refinements  Refinements displayed with the ad.
 * @param string              $mts          Michel Time Stamp.
 * @param int|null            $rating       Rating displayed with the ad, or null.
 * @param string[]            $items        Product identifiers the provider served.
 * @return array<int,array<string,mixed>>
 */
function adzworld_logs_entries( $place, $ad, $configured, $category, $refinements, $mts, $rating = null, $items = array() ) {
	if ( ! is_array( $place ) ) {
		return array();
	}

	$ad          = adzworld_logs_ad_id( $ad );
	$category    = adzworld_slot_journey_text( $category );
	$refinements = adzworld_slot_journey_refinements( $refinements );
	$configured  = adzworld_logs_configured( $configured );
	$rotation    = isset( $place['rotation'] ) ? adzworld_logs_key( $place['rotation'] ) : '';
	$slot        = isset( $place['slot'] ) ? (int) $place['slot'] : 0;
	$inner       = isset( $place['inner_rotation'] ) ? adzworld_logs_key( $place['inner_rotation'] ) : '';
	$inner_slot  = isset( $place['inner_slot'] ) ? (int) $place['inner_slot'] : 0;

	/* Slots count from 1. A zero or a negative is not a slot, it is a bug. */
	if ( '' === $ad || '' === $rotation || $slot < 1 ) {
		return array();
	}

	$row = array(
		'mts'         => adzworld_slot_journey_text( $mts ),
		'items'       => adzworld_logs_items( $items ),
		'rotation'    => $rotation,
		'slot'        => $slot,
		'holds'       => '',
		'inner_slot'  => 0,
		'configured'  => $configured,
		'category'    => $category,
		'rating'      => adzworld_slot_journey_rating( $rating ),
		'refinements' => $refinements,
		'ad'          => $ad,
	);

	if ( '' === $inner ) {
		return array( $row );
	}

	if ( $inner_slot < 1 ) {
		return array();
	}

	$outer                 = $row;
	$outer['holds']        = $inner;
	$outer['inner_slot']   = $inner_slot;
	$outer['configured']   = adzworld_logs_configured( 'r:' . $inner );
	$inner_row             = $row;
	$inner_row['rotation'] = $inner;
	$inner_row['slot']     = $inner_slot;

	return array( $outer, $inner_row );
}

/**
 * Apply one received response to a displayed row.
 *
 * Displayed category, rating, and refinements stay as they were. The change
 * fields record what the reader set. An external slot stores the sent string.
 * An internal slot is logged to the site and stores no string.
 *
 * @param array<string,mixed> $row      Displayed row.
 * @param array<string,mixed> $changes  Keys category, rating, refinement. Absent keys stay as they are.
 * @param bool                $external True when a string is sent to an API.
 * @return array<string,mixed>
 */
function adzworld_logs_apply_response( $row, $changes, $external ) {
	$row = adzworld_logs_sanitize_row( $row );

	if ( ! is_array( $changes ) ) {
		return $row;
	}

	if ( array_key_exists( 'category', $changes ) ) {
		$next = adzworld_slot_journey_text( $changes['category'] );
		if ( '' !== $next && $row['category'] !== $next ) {
			$row['category_change'] = $next;
		} elseif ( $row['category'] === $next ) {
			$row['category_change'] = '';
		}
	}

	if ( array_key_exists( 'rating', $changes ) ) {
		$next = adzworld_slot_journey_rating( $changes['rating'] );
		if ( is_int( $next ) && $row['rating'] !== $next ) {
			$row['rating_change'] = $next;
		} elseif ( is_int( $next ) && $row['rating'] === $next ) {
			$row['rating_change'] = null;
		}
	}

	if ( array_key_exists( 'refinement', $changes ) ) {
		$next = adzworld_slot_journey_text( $changes['refinement'] );
		if ( '' !== $next && $next !== $row['refinements'] ) {
			$row['refinement_change'] = $next;
		}
	}

	$has_change = '' !== $row['category_change'] || is_int( $row['rating_change'] ) || '' !== $row['refinement_change'];

	if ( ! $has_change ) {
		$row['responded']   = 0;
		$row['logged']      = 0;
		$row['sent_string'] = '';
		return $row;
	}

	$row['responded'] = 1;

	if ( $external ) {
		$row['logged']      = 0;
		$row['sent_string'] = adzworld_logs_sent_string(
			adzworld_logs_ad_shown( $row ),
			adzworld_logs_current_category( $row['category'], $row['category_change'] ),
			adzworld_logs_current_rating( $row['rating'], $row['rating_change'] ),
			adzworld_logs_current_refinements( $row['refinements'], $row['refinement_change'] )
		);
		return $row;
	}

	$row['logged']      = 1;
	$row['sent_string'] = '';

	return $row;
}

/**
 * Compare two log rows by Michel Time Stamp, then by write order.
 *
 * @param array<string,mixed> $first  First row.
 * @param array<string,mixed> $second Second row.
 * @return int
 */
function adzworld_logs_compare( $first, $second ) {
	$mts = strcmp( (string) $first['mts'], (string) $second['mts'] );

	if ( 0 !== $mts ) {
		return $mts;
	}

	$first_n  = isset( $first['n'] ) ? (int) $first['n'] : 0;
	$second_n = isset( $second['n'] ) ? (int) $second['n'] : 0;

	return $first_n - $second_n;
}

/**
 * Group log rows by rotation, then by slot.
 *
 * @param array<int,array<string,mixed>> $rows Log rows.
 * @return array<string,array{rotation:string,slots:array<int,array{slot:int,holds:string,rows:array<int,array<string,mixed>>}>}>
 */
function adzworld_logs_by_rotation( $rows ) {
	$groups = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) || empty( $row['rotation'] ) ) {
			continue;
		}

		$name = (string) $row['rotation'];
		$slot = isset( $row['slot'] ) ? (int) $row['slot'] : 0;

		if ( ! isset( $groups[ $name ] ) ) {
			$groups[ $name ] = array(
				'rotation' => $name,
				'slots'    => array(),
			);
		}

		if ( ! isset( $groups[ $name ]['slots'][ $slot ] ) ) {
			$groups[ $name ]['slots'][ $slot ] = array(
				'slot'  => $slot,
				'holds' => isset( $row['holds'] ) ? (string) $row['holds'] : '',
				'rows'  => array(),
			);
		}

		if ( '' === $groups[ $name ]['slots'][ $slot ]['holds'] && ! empty( $row['holds'] ) ) {
			$groups[ $name ]['slots'][ $slot ]['holds'] = (string) $row['holds'];
		}

		$groups[ $name ]['slots'][ $slot ]['rows'][] = $row;
	}

	ksort( $groups );

	foreach ( $groups as $name => $group ) {
		ksort( $groups[ $name ]['slots'] );
		foreach ( $groups[ $name ]['slots'] as $slot => $pack ) {
			usort( $groups[ $name ]['slots'][ $slot ]['rows'], 'adzworld_logs_compare' );
		}
	}

	return $groups;
}

/**
 * Full Michel Time Stamp, or empty when the text is not one.
 *
 * @param mixed $value Raw stamp.
 * @return string
 */
function adzworld_logs_stamp( $value ) {
	$value = adzworld_slot_journey_text( $value );

	if ( preg_match( '/^\d{4}y-\d{2}m-\d{2}d-UTC\d{2}h-\d{2}m-\d{2}s$/', $value ) ) {
		return $value;
	}

	return '';
}

/**
 * Whether a stamp sits inside a from/to pair.
 *
 * An empty bound is open. Comparison is the stamp text, which sorts in time order.
 *
 * @param string $mts  Stamp on the row.
 * @param string $from Full stamp, or empty.
 * @param string $to   Full stamp, or empty.
 * @return bool
 */
function adzworld_logs_in_range( $mts, $from, $to ) {
	$mts = (string) $mts;

	if ( '' !== $from && 0 > strcmp( $mts, $from ) ) {
		return false;
	}

	if ( '' !== $to && 0 < strcmp( $mts, $to ) ) {
		return false;
	}

	return true;
}

/**
 * Read a from/to pair.
 *
 * @param string $from_raw     Posted from text.
 * @param string $to_raw       Posted to text.
 * @param bool   $require_both Archive requires both stamps.
 * @return array{from:string,to:string,error:string}
 */
function adzworld_logs_bounds( $from_raw, $to_raw, $require_both ) {
	$from_raw = adzworld_slot_journey_text( $from_raw );
	$to_raw   = adzworld_slot_journey_text( $to_raw );
	$from     = adzworld_logs_stamp( $from_raw );
	$to       = adzworld_logs_stamp( $to_raw );
	$error    = '';

	if ( ( '' !== $from_raw && '' === $from ) || ( '' !== $to_raw && '' === $to ) ) {
		$error = 'stamp';
	} elseif ( $require_both && ( '' === $from || '' === $to ) ) {
		$error = 'both';
	} elseif ( '' !== $from && '' !== $to && 0 < strcmp( $from, $to ) ) {
		$error = 'order';
	}

	return array(
		'from'  => $from,
		'to'    => $to,
		'error' => $error,
	);
}

/**
 * Rows whose stamp sits in the range.
 *
 * @param array<int,array<string,mixed>> $rows Log rows.
 * @param string                         $from Full stamp, or empty.
 * @param string                         $to   Full stamp, or empty.
 * @return array<int,array<string,mixed>>
 */
function adzworld_logs_range( $rows, $from, $to ) {
	$kept = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$mts = isset( $row['mts'] ) ? (string) $row['mts'] : '';
		if ( adzworld_logs_in_range( $mts, $from, $to ) ) {
			$kept[] = $row;
		}
	}

	return $kept;
}

/**
 * Plain-text archive of log rows. Values are complete.
 *
 * @param array<int,array<string,mixed>> $rows Log rows.
 * @return string
 */
function adzworld_logs_export_text( $rows ) {
	$blocks = array();

	foreach ( $rows as $row ) {
		$clean = adzworld_logs_sanitize_row( $row );
		$lines = array( $clean['mts'], 'rotation ' . $clean['rotation'], 'slot ' . (string) $clean['slot'] );

		if ( '' !== $clean['holds'] ) {
			$lines[] = 'showed ' . $clean['holds'];
			$lines[] = 'slot in ' . $clean['holds'] . ' ' . (string) (int) $clean['inner_slot'];
		}
		if ( '' !== $clean['configured'] ) {
			$lines[] = 'configured ' . $clean['configured'];
		}

		$lines[] = 'ad ' . adzworld_logs_ad_shown( $clean );

		if ( '' !== $clean['items'] && 0 === strpos( $clean['ad'], 'source-' ) ) {
			$lines[] = 'served by ' . substr( $clean['ad'], strlen( 'source-' ) );
		}

		if ( '' !== $clean['category'] ) {
			$lines[] = 'category ' . $clean['category'];
		}
		if ( is_int( $clean['rating'] ) ) {
			$lines[] = 'rating ' . (string) $clean['rating'];
		}
		if ( '' !== $clean['refinements'] ) {
			$lines[] = 'refinements ' . $clean['refinements'];
		}
		if ( ! empty( $clean['responded'] ) ) {
			if ( '' !== $clean['category_change'] ) {
				$lines[] = 'change to category ' . $clean['category_change'];
			}
			if ( is_int( $clean['rating_change'] ) ) {
				$lines[] = 'change to rating ' . (string) $clean['rating_change'];
			}
			if ( '' !== $clean['refinement_change'] ) {
				$lines[] = 'change to refinement ' . $clean['refinement_change'];
			}
			if ( ! empty( $clean['logged'] ) ) {
				$lines[] = 'logged to the site';
			} elseif ( '' !== $clean['sent_string'] ) {
				$lines[] = 'sent ' . $clean['sent_string'];
			}
		}

		$blocks[] = implode( "\n", $lines );
	}

	return implode( "\n\n", $blocks );
}

/**
 * Stored log rows.
 *
 * @return array<int,array<string,mixed>>
 */
function adzworld_logs_rows() {
	$stored = get_option( 'adzworld_logs', array() );

	if ( ! is_array( $stored ) ) {
		return array();
	}

	$rows = array();
	foreach ( $stored as $row ) {
		$clean = adzworld_logs_sanitize_row( $row );
		if ( '' !== $clean['ad'] && '' !== $clean['rotation'] ) {
			$rows[] = $clean;
		}
	}

	return $rows;
}

/**
 * One stored row reduced to the log shape.
 *
 * @param mixed $row Raw row.
 * @return array<string,mixed>
 */
function adzworld_logs_sanitize_row( $row ) {
	$clean = array(
		'mts'               => '',
		'n'                 => 0,
		'visit'             => '',
		'items'             => '',
		'rotation'          => '',
		'slot'              => 0,
		'holds'             => '',
		'inner_slot'        => 0,
		'configured'        => '',
		'category'          => '',
		'rating'            => null,
		'refinements'       => '',
		'ad'                => '',
		'responded'         => 0,
		'category_change'   => '',
		'rating_change'     => null,
		'refinement_change' => '',
		'logged'            => 0,
		'sent_string'       => '',
	);

	if ( ! is_array( $row ) ) {
		return $clean;
	}

	$clean['mts']         = isset( $row['mts'] ) ? adzworld_slot_journey_text( $row['mts'] ) : '';
	$clean['n']           = isset( $row['n'] ) ? (int) $row['n'] : 0;
	$clean['visit']       = isset( $row['visit'] ) ? adzworld_logs_key( $row['visit'] ) : '';
	$clean['items']       = isset( $row['items'] ) ? adzworld_logs_items( explode( ' ', (string) $row['items'] ) ) : '';
	$clean['rotation']    = isset( $row['rotation'] ) ? adzworld_logs_key( $row['rotation'] ) : '';
	$clean['slot']        = isset( $row['slot'] ) ? (int) $row['slot'] : 0;
	$clean['holds']       = isset( $row['holds'] ) ? adzworld_logs_key( $row['holds'] ) : '';
	$clean['inner_slot']  = isset( $row['inner_slot'] ) ? (int) $row['inner_slot'] : 0;
	$clean['configured']  = isset( $row['configured'] ) ? adzworld_logs_configured( $row['configured'] ) : '';
	$clean['category']    = isset( $row['category'] ) ? adzworld_slot_journey_text( $row['category'] ) : '';
	$clean['rating']      = array_key_exists( 'rating', $row ) ? adzworld_slot_journey_rating( $row['rating'] ) : null;
	$clean['refinements'] = isset( $row['refinements'] ) ? adzworld_slot_journey_refinements( $row['refinements'] ) : '';
	$clean['ad']          = isset( $row['ad'] ) ? adzworld_logs_ad_id( $row['ad'] ) : '';
	$clean['responded']   = empty( $row['responded'] ) ? 0 : 1;

	if ( $clean['responded'] ) {
		$clean['category_change']   = isset( $row['category_change'] ) ? adzworld_slot_journey_text( $row['category_change'] ) : '';
		$clean['rating_change']     = array_key_exists( 'rating_change', $row ) ? adzworld_slot_journey_rating( $row['rating_change'] ) : null;
		$clean['refinement_change'] = isset( $row['refinement_change'] ) ? adzworld_slot_journey_text( $row['refinement_change'] ) : '';
		$clean['logged']            = empty( $row['logged'] ) ? 0 : 1;
		$clean['sent_string']       = isset( $row['sent_string'] ) ? adzworld_slot_journey_text( $row['sent_string'] ) : '';
	}

	return $clean;
}

/**
 * Save the log.
 *
 * @param array<int,array<string,mixed>> $rows Rows.
 * @return void
 */
function adzworld_logs_save( $rows ) {
	$clean = array();

	foreach ( $rows as $row ) {
		$item = adzworld_logs_sanitize_row( $row );
		if ( '' !== $item['ad'] && '' !== $item['rotation'] ) {
			$clean[] = $item;
		}
	}

	if ( false === get_option( 'adzworld_logs', false ) ) {
		add_option( 'adzworld_logs', $clean, '', false );
		return;
	}

	update_option( 'adzworld_logs', $clean, false );
}

/**
 * Whether the reader has told this plugin anything.
 *
 * Nothing about a reader is recorded, and no session is opened for them,
 * until they give input of their own accord: a category, a refinement, a
 * rating, or a profile. Being shown an advertisement is not input, and is
 * not a reason to start recording someone.
 *
 * @since 1.0.0
 *
 * @return bool True once the reader has given input.
 */
function adzworld_logs_consented() {
	/* A reader who made a profile told us on purpose. */
	if ( function_exists( 'adzworld_preference_uses_profile' ) && adzworld_preference_uses_profile() ) {
		return true;
	}

	/*
	 * A reader who typed a category or a refinement told us what they want.
	 * That act is the consent: nothing is recorded, and no session is opened,
	 * until they make it.
	 */
	if ( function_exists( 'adzworld_current_preference' ) && function_exists( 'adzworld_has_preference' )
		&& adzworld_has_preference( adzworld_current_preference() ) ) {
		return true;
	}

	/* A rating is the same act by a different control. */
	if ( function_exists( 'adzworld_current_feedback' ) ) {
		$feedback = adzworld_current_feedback();

		if ( is_array( $feedback ) && ! empty( $feedback ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Visit key for this browser.
 *
 * The preference session holds the key when that session is open. A cookie
 * of the same key covers an account that does not use that session, so a
 * display and the later response stay on the same rows. Both expire with
 * the browser.
 *
 * @since 1.0.0
 *
 * @param bool $establish True when the reader has just given input, which
 *                        both consents to the session and creates it. False
 *                        while merely serving, when an identifier is used
 *                        only if one already exists.
 * @return string Visit identifier, or empty when there is nothing to file.
 */
function adzworld_logs_visit( $establish = false ) {
	/*
	 * A visitor who has told us nothing gets no session, no cookie and no
	 * identifier. Serving an adzad to someone is not a reason to start
	 * recording them.
	 */
	if ( ! $establish && ! adzworld_logs_consented() ) {
		return '';
	}

	$profile = function_exists( 'adzworld_preference_uses_profile' ) && adzworld_preference_uses_profile();

	if ( ! $profile && PHP_SESSION_ACTIVE !== session_status() && ! headers_sent() && function_exists( 'adzworld_preference_open_session' ) ) {
		adzworld_preference_open_session();
	}

	$session = '';
	if ( PHP_SESSION_ACTIVE === session_status() && isset( $_SESSION['adzworld_log_visit'] ) ) {
		$session = adzworld_logs_key( sanitize_text_field( wp_unslash( $_SESSION['adzworld_log_visit'] ) ) );
	}

	$cookie = '';
	if ( isset( $_COOKIE['adzworld_log_visit'] ) && is_string( $_COOKIE['adzworld_log_visit'] ) ) {
		$cookie_raw = sanitize_text_field( wp_unslash( $_COOKIE['adzworld_log_visit'] ) );
		$cookie     = adzworld_logs_key( $cookie_raw );
	}

	if ( '' !== $session ) {
		$visit = $session;
	} elseif ( '' !== $cookie ) {
		$visit = $cookie;
	} elseif ( function_exists( 'wp_generate_password' ) ) {
		$visit = adzworld_logs_key( wp_generate_password( 12, false, false ) );
	} else {
		$visit = '';
	}

	if ( '' === $visit ) {
		return '';
	}

	if ( PHP_SESSION_ACTIVE === session_status() ) {
		$_SESSION['adzworld_log_visit'] = $visit;
	}

	if ( $cookie !== $visit && ! headers_sent() && defined( 'COOKIEPATH' ) ) {
		setcookie(
			'adzworld_log_visit',
			$visit,
			array(

				/*
				 * Expires with the browser, like every other cookie this
				 * plugin sets. It was 30 days, which is not a session: it
				 * joined one visit to the next for a month, and the readme
				 * said "browser-session cookie" while the code did otherwise.
				 * A visit is a visit.
				 */
				'expires'  => 0,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
				'secure'   => function_exists( 'is_ssl' ) && is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		$_COOKIE['adzworld_log_visit'] = $visit;
	}

	return $visit;
}

/**
 * Make the visit key available before the rotation prints.
 *
 * @return void
 */
function adzworld_logs_open_visit() {
	adzworld_logs_visit();
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'init', 'adzworld_logs_open_visit', 2 );
}

/**
 * Build the place array from the rotation that rendered the ad.
 *
 * @param string $rotation       Rotation that contains the slide. The inner rotation when this slide is nested.
 * @param int    $slot           Slot index of that slide.
 * @param string $outer_rotation Outer rotation when this rotation sits in a slot.
 * @param int    $outer_slot     Slot of the outer rotation.
 * @return array<string,mixed>
 */
function adzworld_logs_place( $rotation, $slot, $outer_rotation = '', $outer_slot = 0 ) {
	$outer = adzworld_logs_key( $outer_rotation );

	if ( '' !== $outer ) {
		return array(
			'rotation'       => $outer,
			'slot'           => (int) $outer_slot,
			'inner_rotation' => adzworld_logs_key( $rotation ),
			'inner_slot'     => (int) $slot,
		);
	}

	return array(
		'rotation' => adzworld_logs_key( $rotation ),
		'slot'     => (int) $slot,
	);
}

/**
 * Refinement phrases already stored for one category.
 *
 * @param array<string,mixed> $pref     Preference.
 * @param string              $category Category.
 * @return string[]
 */
function adzworld_logs_phrases( $pref, $category ) {
	$phrases = array();

	if ( ! is_array( $pref ) || empty( $pref['category_refinements'] ) || ! is_array( $pref['category_refinements'] ) || ! function_exists( 'adzworld_pair_split' ) ) {
		return $phrases;
	}

	foreach ( $pref['category_refinements'] as $stored ) {
		$split = adzworld_pair_split( $stored );
		if ( 2 === count( $split ) && (string) $category === $split[0] && '' !== $split[1] ) {
			$phrases[] = $split[1];
		}
	}

	return $phrases;
}

/**
 * Record an ad that was displayed.
 *
 * @param array<string,mixed> $place       Place.
 * @param string              $ad          Ad id.
 * @param string              $configured  Slot configuration of the serving slot.
 * @param string              $category    Category displayed with the ad.
 * @param mixed               $refinements Refinements displayed with the ad.
 * @param mixed               $rating      Rating displayed with the ad, or empty.
 * @return void
 */
function adzworld_logs_remember_ad( $place, $ad, $configured, $category, $refinements, $rating = null ) {
	/*
	 * Taken whether or not this round is recorded, so identifiers left by an
	 * unrecorded round cannot leak into the next one.
	 */
	$items = function_exists( 'adzworld_products_remember_served' ) ? adzworld_products_remember_served() : array();

	/*
	 * They belong to a provider slot and to nothing else. An adzad this site
	 * owns is named by its own post and must never be labelled with a
	 * listing a provider rendered elsewhere on the page.
	 */
	if ( 0 !== strpos( adzworld_logs_ad_id( $ad ), 'source-' ) ) {
		$items = array();
	}

	$visit = adzworld_logs_visit();

	/* Nothing was consented, so there is nothing to record. */
	if ( '' === $visit ) {
		return;
	}

	$rows    = adzworld_logs_rows();
	$entries = adzworld_logs_entries( $place, $ad, $configured, $category, $refinements, adzworld_michel_timestamp(), $rating, $items );

	foreach ( $entries as $entry ) {
		$entry['visit']             = $visit;
		$entry['n']                 = count( $rows );
		$entry['responded']         = 0;
		$entry['category_change']   = '';
		$entry['rating_change']     = null;
		$entry['refinement_change'] = '';
		$entry['logged']            = 0;
		$entry['sent_string']       = '';
		$rows[]                     = $entry;
	}

	if ( ! empty( $entries ) ) {
		adzworld_logs_save( $rows );
	}
}

/**
 * Record a response on the latest display of this ad in each logged place.
 *
 * @param array<string,mixed> $place    Place.
 * @param string              $ad       Ad id.
 * @param array<string,mixed> $changes  Category, rating, or refinement keys that arrived.
 * @param bool                $external True when a string is sent to an API.
 * @param string[]            $items    Product identifiers the slide was showing.
 * @return void
 */
function adzworld_logs_remember_response( $place, $ad, $changes, $external, $items = array() ) {
	/*
	 * Giving input is the act that consents, so this call establishes the
	 * session rather than requiring one to exist already.
	 */
	$visit = adzworld_logs_visit( true );
	$rows  = adzworld_logs_rows();
	$ad_id = adzworld_logs_ad_id( $ad );

	/* Same rule on the response path: a provider's listings, nowhere else. */
	if ( 0 !== strpos( $ad_id, 'source-' ) ) {
		$items = array();
	}

	$entries = adzworld_logs_entries( $place, $ad, '', '', '', adzworld_michel_timestamp(), null, $items );
	$items   = adzworld_logs_items( $items );

	if ( '' === $visit ) {
		return;
	}

	if ( empty( $entries ) || '' === $ad_id || ! is_array( $changes ) ) {
		return;
	}

	$found_one = false;

	foreach ( $entries as $entry ) {
		$found = -1;
		$index = count( $rows );

		while ( $index > 0 ) {
			--$index;
			$row = $rows[ $index ];
			if ( $row['visit'] === $visit && $row['rotation'] === $entry['rotation'] && (int) $row['slot'] === (int) $entry['slot'] && $row['holds'] === $entry['holds'] && (int) $row['inner_slot'] === (int) $entry['inner_slot'] && $row['ad'] === $ad_id ) {
				$found = $index;
				break;
			}
		}

		if ( $found < 0 ) {
			/*
			 * The adzad that prompted this was served before the reader had
			 * said anything, so it was not recorded. Their input is the
			 * consent, so the round it answers is created now rather than
			 * dropped: the first thing a reader says is the most telling.
			 */
			$entry['visit']     = $visit;
			$entry['n']         = count( $rows );
			$entry['responded'] = 0;
			$rows[]             = adzworld_logs_apply_response( $entry, $changes, $external );
			$found_one          = true;

			continue;
		}

		/*
		 * A round recorded before the provider's cards were identified, or
		 * recorded by an older version, is completed rather than left
		 * nameless. An identifier already on the row is never overwritten.
		 */
		if ( '' === $rows[ $found ]['items'] && '' !== $items ) {
			$rows[ $found ]['items'] = $items;
		}

		$rows[ $found ] = adzworld_logs_apply_response( $rows[ $found ], $changes, $external );
		$found_one      = true;
	}

	if ( $found_one ) {
		adzworld_logs_save( $rows );
	}
}

/**
 * Record a response posted with the rotation and slot of the displayed ad.
 *
 * @param string              $rotation       Rotation that contains the slide.
 * @param mixed               $slot           Slot index.
 * @param string              $outer_rotation Outer rotation when this rotation sits in a slot.
 * @param mixed               $outer_slot     Slot of the outer rotation.
 * @param string              $ad             Ad id.
 * @param array<string,mixed> $changes        Received changes.
 * @param bool                $external       True when a string is sent to an API.
 * @param string[]            $items          Product identifiers the slide was showing.
 * @return void
 */
function adzworld_logs_record_response( $rotation, $slot, $outer_rotation, $outer_slot, $ad, $changes, $external, $items = array() ) {
	$ad = adzworld_logs_ad_id( $ad );

	if ( '' === $ad || ! is_array( $changes ) ) {
		return;
	}

	$place = adzworld_logs_place( $rotation, $slot, $outer_rotation, $outer_slot );

	if ( '' === $place['rotation'] || (int) $place['slot'] < 1 ) {
		return;
	}

	if ( isset( $place['inner_slot'] ) && (int) $place['inner_slot'] < 1 ) {
		return;
	}

	adzworld_logs_remember_response( $place, $ad, $changes, (bool) $external, $items );
}

/**
 * Print one labeled value.
 *
 * @param string $label Label.
 * @param string $value Value.
 * @return void
 */
function adzworld_logs_echo_pair( $label, $value ) {
	if ( '' === (string) $value ) {
		return;
	}

	echo '<p class="adzworld-log-k">' . esc_html( $label ) . '</p>';
	echo '<p class="adzworld-log-v">' . esc_html( (string) $value ) . '</p>';
}

/**
 * Print one chat turn.
 *
 * @param array<string,mixed> $row Sanitized row.
 * @return void
 */
function adzworld_logs_echo_turn( $row ) {
	$rating = is_int( $row['rating'] ) ? (string) $row['rating'] : '';

	echo '<div class="adzworld-log-turn">';
	echo '<div class="adzworld-log-served">';
	echo '<p class="adzworld-log-ad">' . esc_html( adzworld_logs_ad_shown( $row ) ) . '</p>';
	if ( '' !== (string) $row['holds'] ) {
		adzworld_logs_echo_pair(
			sprintf(
				/* translators: %s: rotation shown in this slot. */
				__( 'Slot in %s', 'adz-world' ),
				(string) $row['holds']
			),
			(string) (int) $row['inner_slot']
		);
	}
	echo '<p class="adzworld-log-mts">' . esc_html( (string) $row['mts'] ) . '</p>';
	echo '</div>';

	echo '<div class="adzworld-log-request">';
	adzworld_logs_echo_pair( __( 'Configured', 'adz-world' ), (string) $row['configured'] );
	if ( '' !== (string) $row['items'] && 0 === strpos( (string) $row['ad'], 'source-' ) ) {
		adzworld_logs_echo_pair( __( 'Served by', 'adz-world' ), substr( (string) $row['ad'], strlen( 'source-' ) ) );
	}
	adzworld_logs_echo_pair( __( 'Category', 'adz-world' ), (string) $row['category'] );
	adzworld_logs_echo_pair( __( 'Rating', 'adz-world' ), $rating );
	adzworld_logs_echo_pair( __( 'Refinements', 'adz-world' ), (string) $row['refinements'] );

	if ( ! empty( $row['responded'] ) ) {
		$rating_change = is_int( $row['rating_change'] ) ? (string) $row['rating_change'] : '';
		echo '<div class="adzworld-log-response">';
		echo '<p class="adzworld-log-k">' . esc_html__( 'Response', 'adz-world' ) . '</p>';
		adzworld_logs_echo_pair( __( 'Change to category', 'adz-world' ), (string) $row['category_change'] );
		adzworld_logs_echo_pair( __( 'Change to rating', 'adz-world' ), $rating_change );
		adzworld_logs_echo_pair( __( 'Change to refinement', 'adz-world' ), (string) $row['refinement_change'] );
		if ( ! empty( $row['logged'] ) ) {
			echo '<p class="adzworld-log-v">' . esc_html__( 'Logged to the site.', 'adz-world' ) . '</p>';
		} elseif ( '' !== (string) $row['sent_string'] ) {
			adzworld_logs_echo_pair( __( 'Sent', 'adz-world' ), (string) $row['sent_string'] );
		}
		echo '</div>';
	}

	echo '</div></div>';
}

/**
 * Sentence for a rejected from/to pair.
 *
 * @param string $error Bound error key.
 * @return string
 */
function adzworld_logs_bounds_message( $error ) {
	if ( 'order' === $error ) {
		return __( 'The first Michel Time Stamp is later than the second.', 'adz-world' );
	}

	if ( 'both' === $error ) {
		return __( 'Archive uses two full Michel Time Stamps.', 'adz-world' );
	}

	return __( 'Use the full Michel Time Stamp through seconds.', 'adz-world' );
}

/**
 * Send the range as a text file. An empty bound exports the whole log.
 *
 * @param string $from Full stamp, or empty.
 * @param string $to   Full stamp, or empty.
 * @return void
 */
function adzworld_logs_send_export( $from, $to ) {
	$text = adzworld_logs_export_text( adzworld_logs_range( adzworld_logs_rows(), $from, $to ) );

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="adzlogs.txt"' );
	file_put_contents( 'php://output', $text );
	exit;
}

/**
 * AdzLogs screen. Rotations are the accordion bands.
 *
 * @return void
 */
function adzworld_logs_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$notice = '';

	if ( isset( $_POST['adzworld_logs_action'] ) ) {
		check_admin_referer( 'adzworld_logs' );

		$action   = sanitize_key( wp_unslash( $_POST['adzworld_logs_action'] ) );
		$from_raw = isset( $_POST['adzworld_logs_from'] ) && is_string( $_POST['adzworld_logs_from'] ) ? sanitize_text_field( wp_unslash( $_POST['adzworld_logs_from'] ) ) : '';
		$to_raw   = isset( $_POST['adzworld_logs_to'] ) && is_string( $_POST['adzworld_logs_to'] ) ? sanitize_text_field( wp_unslash( $_POST['adzworld_logs_to'] ) ) : '';
		$archive  = ( 'archive' === $action );
		$bounds   = adzworld_logs_bounds( $from_raw, $to_raw, $archive );

		if ( '' !== $bounds['error'] ) {
			$notice = adzworld_logs_bounds_message( $bounds['error'] );
		} elseif ( 'export' === $action ) {
			adzworld_logs_send_export( $bounds['from'], $bounds['to'] );
		} elseif ( $archive ) {
			$keep  = array();
			$count = 0;

			foreach ( adzworld_logs_rows() as $row ) {
				$mts = isset( $row['mts'] ) ? (string) $row['mts'] : '';
				if ( adzworld_logs_in_range( $mts, $bounds['from'], $bounds['to'] ) ) {
					++$count;
					continue;
				}
				$keep[] = $row;
			}

			adzworld_logs_save( $keep );
			wp_safe_redirect( add_query_arg( 'archived', (string) $count, admin_url( 'admin.php?page=adzworld-logs' ) ) );
			exit;
		}
	}

	$groups  = adzworld_logs_by_rotation( adzworld_logs_rows() );
	$example = '2026y-11m-14d-UTC22h-05m-09s';

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'AdzLogs', 'adz-world' ) . '</h1>';

	if ( isset( $_GET['archived'] ) ) {
		echo '<div class="notice notice-success"><p>' . esc_html(
			sprintf(
				/* translators: %d: number of archived events. */
				__( 'Archived %d events.', 'adz-world' ),
				absint( wp_unslash( $_GET['archived'] ) )
			)
		) . '</p></div>';
	}

	if ( '' !== $notice ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $notice ) . '</p></div>';
	}

	echo '<form method="post" class="adzworld-log-range">';
	wp_nonce_field( 'adzworld_logs' );
	echo '<p>';
	echo '<label for="adzworld-logs-from">' . esc_html__( 'From', 'adz-world' ) . '</label> ';
	echo '<input type="text" class="regular-text" id="adzworld-logs-from" name="adzworld_logs_from" value="" placeholder="' . esc_attr( $example ) . '" /> ';
	echo '<label for="adzworld-logs-to">' . esc_html__( 'To', 'adz-world' ) . '</label> ';
	echo '<input type="text" class="regular-text" id="adzworld-logs-to" name="adzworld_logs_to" value="" placeholder="' . esc_attr( $example ) . '" /> ';
	echo '<button type="submit" class="button" name="adzworld_logs_action" value="export">' . esc_html__( 'Export', 'adz-world' ) . '</button> ';
	echo '<button type="submit" class="button" name="adzworld_logs_action" value="archive">' . esc_html__( 'Archive', 'adz-world' ) . '</button>';
	echo '</p></form>';

	if ( empty( $groups ) ) {
		echo '<p>' . esc_html__( 'No adz have been logged.', 'adz-world' ) . '</p></div>';
		return;
	}

	foreach ( $groups as $group ) {
		echo '<details class="adzworld-log-rotation">';
		echo '<summary>' . esc_html( $group['rotation'] ) . '</summary>';

		foreach ( $group['slots'] as $pack ) {
			echo '<details class="adzworld-log-slot">';
			if ( '' !== $pack['holds'] ) {
				echo '<summary>' . esc_html(
					sprintf(
						/* translators: 1: slot number, 2: rotation shown in that slot. */
						__( 'Slot %1$d, showed %2$s', 'adz-world' ),
						(int) $pack['slot'],
						$pack['holds']
					)
				) . '</summary>';
			} else {
				echo '<summary>' . esc_html(
					sprintf(
						/* translators: %d: slot number in the rotation. */
						__( 'Slot %d', 'adz-world' ),
						(int) $pack['slot']
					)
				) . '</summary>';
			}

			echo '<div class="adzworld-log-chat">';
			foreach ( $pack['rows'] as $row ) {
				adzworld_logs_echo_turn( $row );
			}
			echo '</div></details>';
		}

		echo '</details>';
	}

	echo '</div>';
}
