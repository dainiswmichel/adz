<?php
/**
 * Consent-aware adz slot journeys.
 *
 * A journey records what was served, the reader's explicit response, and the
 * reader's next refinement. Provider APIs receive only the category and the
 * reader's verbatim refinements; response labels remain local to adz-world.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

const ADZWORLD_JOURNEY_SESSION         = 'adzworld_slot_journeys';
const ADZWORLD_JOURNEY_SESSION_STARTED = 'adzworld_slot_journeys_started';
const ADZWORLD_JOURNEY_META            = 'adzworld_slot_journeys';
const ADZWORLD_JOURNEY_CONSENT_META    = 'adzworld_journey_consent';

/**
 * Responses a reader may explicitly send.
 *
 * No response is represented by the absence of a value, never by a stored
 * neutral response.
 *
 * @return array<string,string> Response key and label pairs.
 */
function adzworld_journey_responses() {
	return array(
		'dislike' => __( 'Dislike', 'adz-world' ),
		'like'    => __( 'Like', 'adz-world' ),
	);
}

/**
 * Remove executable markup without interpreting a reader's language.
 *
 * Case, punctuation, spacing inside the statement, and word order remain as
 * entered. Only markup, NUL bytes, and outer whitespace are removed.
 *
 * @param mixed $statement Candidate statement.
 * @return string Safe verbatim statement.
 */
function adzworld_journey_statement( $statement ) {
	if ( ! is_string( $statement ) ) {
		return '';
	}

	return trim( str_replace( "\0", '', wp_strip_all_tags( $statement ) ) );
}

/**
 * Whether this signed-in reader explicitly consented to profile storage.
 *
 * @return bool True when profile storage is permitted.
 */
function adzworld_journey_profile_storage_allowed() {
	return is_user_logged_in()
		&& '1' === (string) get_user_meta( get_current_user_id(), ADZWORLD_JOURNEY_CONSENT_META, true );
}

/**
 * Open and age the anonymous journey session.
 *
 * @return void
 */
function adzworld_journey_open_session() {
	if ( function_exists( 'adzworld_preference_open_session' ) ) {
		adzworld_preference_open_session();
	} elseif ( PHP_SESSION_ACTIVE !== session_status() && ! headers_sent() ) {
		session_start();
	}

	if ( PHP_SESSION_ACTIVE !== session_status() ) {
		return;
	}

	$started = isset( $_SESSION[ ADZWORLD_JOURNEY_SESSION_STARTED ] )
		? absint( $_SESSION[ ADZWORLD_JOURNEY_SESSION_STARTED ] )
		: 0;
	if ( 0 === $started || time() - $started >= DAY_IN_SECONDS ) {
		$_SESSION[ ADZWORLD_JOURNEY_SESSION ]         = array();
		$_SESSION[ ADZWORLD_JOURNEY_SESSION_STARTED ] = time();
	}
}

/**
 * Sanitize stored journeys structurally without rewriting reader text.
 *
 * @param mixed $journeys Candidate journeys.
 * @return array<string,array<string,mixed>> Valid journeys.
 */
function adzworld_journey_sanitize( $journeys ) {
	$clean = array();
	if ( ! is_array( $journeys ) ) {
		return $clean;
	}

	foreach ( $journeys as $key => $journey ) {
		$key = sanitize_key( (string) $key );
		if ( '' === $key || ! is_array( $journey ) ) {
			continue;
		}

		$rounds = array();
		foreach ( isset( $journey['rounds'] ) && is_array( $journey['rounds'] ) ? $journey['rounds'] : array() as $round ) {
			if ( ! is_array( $round ) ) {
				continue;
			}
			$provider = isset( $round['provider'] ) ? sanitize_key( (string) $round['provider'] ) : '';
			$item_id  = isset( $round['item_id'] ) ? sanitize_text_field( (string) $round['item_id'] ) : '';
			$response = isset( $round['response'] ) ? sanitize_key( (string) $round['response'] ) : '';
			if ( '' === $provider || '' === $item_id ) {
				continue;
			}
			if ( '' !== $response && ! isset( adzworld_journey_responses()[ $response ] ) ) {
				$response = '';
			}
			$rounds[] = array(
				'provider'     => $provider,
				'item_id'      => $item_id,
				'response'     => $response,
				'refinement'   => isset( $round['refinement'] ) ? adzworld_journey_statement( $round['refinement'] ) : '',
				'request'      => isset( $round['request'] ) ? adzworld_journey_statement( $round['request'] ) : '',
				'shown_at'     => isset( $round['shown_at'] ) ? absint( $round['shown_at'] ) : 0,
				'responded_at' => isset( $round['responded_at'] ) ? absint( $round['responded_at'] ) : 0,
			);
		}

		$clean[ $key ] = array(
			'rotation' => isset( $journey['rotation'] ) ? sanitize_key( (string) $journey['rotation'] ) : '',
			'slot'     => isset( $journey['slot'] ) ? absint( $journey['slot'] ) : 0,
			'category' => isset( $journey['category'] ) ? adzworld_journey_statement( $journey['category'] ) : '',
			'rounds'   => $rounds,
		);
	}

	return $clean;
}

/**
 * Return the current reader's journeys.
 *
 * @return array<string,array<string,mixed>> Journeys keyed by scope.
 */
function adzworld_journeys() {
	if ( adzworld_journey_profile_storage_allowed() ) {
		return adzworld_journey_sanitize( get_user_meta( get_current_user_id(), ADZWORLD_JOURNEY_META, true ) );
	}

	adzworld_journey_open_session();
	if ( PHP_SESSION_ACTIVE !== session_status() ) {
		return array();
	}

	$stored = isset( $_SESSION[ ADZWORLD_JOURNEY_SESSION ] )
		? map_deep( wp_unslash( $_SESSION[ ADZWORLD_JOURNEY_SESSION ] ), 'sanitize_text_field' )
		: array();
	return adzworld_journey_sanitize( $stored );
}

/**
 * Store journeys in the consented profile or the 24-hour visitor session.
 *
 * @param array $journeys Journey collection.
 * @return void
 */
function adzworld_store_journeys( $journeys ) {
	$journeys = adzworld_journey_sanitize( $journeys );
	if ( adzworld_journey_profile_storage_allowed() ) {
		update_user_meta( get_current_user_id(), ADZWORLD_JOURNEY_META, $journeys );
		return;
	}

	adzworld_journey_open_session();
	if ( PHP_SESSION_ACTIVE === session_status() ) {
		$_SESSION[ ADZWORLD_JOURNEY_SESSION ] = $journeys;
	}
}

/**
 * Build a stable scope key without exposing reader text as an array key.
 *
 * @param string $rotation Rotation name.
 * @param int    $slot     Slot number.
 * @param string $category Category text.
 * @return string Scope key.
 */
function adzworld_journey_key( $rotation, $slot, $category ) {
	return hash( 'sha256', wp_json_encode( array( sanitize_key( $rotation ), absint( $slot ), adzworld_journey_statement( $category ) ) ) );
}

/**
 * Record one exact provider item when it is displayed.
 *
 * @param string $rotation Rotation name.
 * @param int    $slot     Slot number.
 * @param string $category Category text.
 * @param string $provider Provider key.
 * @param string $item_id  Provider item id.
 * @param string $request  Request used to fetch the item.
 * @return void
 */
function adzworld_journey_record_impression( $rotation, $slot, $category, $provider, $item_id, $request ) {
	$provider = sanitize_key( $provider );
	$item_id  = sanitize_text_field( $item_id );
	$category = adzworld_journey_statement( $category );
	if ( '' === $provider || '' === $item_id || '' === $category ) {
		return;
	}

	$journeys = adzworld_journeys();
	$key      = adzworld_journey_key( $rotation, $slot, $category );
	if ( ! isset( $journeys[ $key ] ) ) {
		$journeys[ $key ] = array(
			'rotation' => sanitize_key( $rotation ),
			'slot'     => absint( $slot ),
			'category' => $category,
			'rounds'   => array(),
		);
	}
	$journeys[ $key ]['rounds'][] = array(
		'provider'     => $provider,
		'item_id'      => $item_id,
		'response'     => '',
		'refinement'   => '',
		'request'      => adzworld_journey_statement( $request ),
		'shown_at'     => time(),
		'responded_at' => 0,
	);
	adzworld_store_journeys( $journeys );
}

/**
 * Attach an explicit response to the latest matching display round.
 *
 * @param string $rotation Rotation name.
 * @param int    $slot     Slot number.
 * @param string $category Category text.
 * @param string $provider Provider key.
 * @param string $item_id  Provider item id.
 * @param string $response Explicit response key.
 * @return bool True when saved.
 */
function adzworld_journey_record_response( $rotation, $slot, $category, $provider, $item_id, $response ) {
	$responses = adzworld_journey_responses();
	$response  = sanitize_key( $response );
	if ( ! isset( $responses[ $response ] ) ) {
		return false;
	}

	$journeys = adzworld_journeys();
	$key      = adzworld_journey_key( $rotation, $slot, $category );
	if ( ! isset( $journeys[ $key ] ) ) {
		adzworld_journey_record_impression( $rotation, $slot, $category, $provider, $item_id, '' );
		$journeys = adzworld_journeys();
	}

	$provider = sanitize_key( $provider );
	$item_id  = sanitize_text_field( $item_id );
	for ( $index = count( $journeys[ $key ]['rounds'] ) - 1; $index >= 0; --$index ) {
		$round = $journeys[ $key ]['rounds'][ $index ];
		if ( $provider === $round['provider'] && $item_id === $round['item_id'] ) {
			$journeys[ $key ]['rounds'][ $index ]['response']     = $response;
			$journeys[ $key ]['rounds'][ $index ]['responded_at'] = time();
			adzworld_store_journeys( $journeys );
			return true;
		}
	}

	/* The response can arrive before the impression request completes. */
	adzworld_journey_record_impression( $rotation, $slot, $category, $provider, $item_id, '' );
	$journeys = adzworld_journeys();
	$index    = count( $journeys[ $key ]['rounds'] ) - 1;
	if ( $index < 0 ) {
		return false;
	}
	$journeys[ $key ]['rounds'][ $index ]['response']     = $response;
	$journeys[ $key ]['rounds'][ $index ]['responded_at'] = time();
	adzworld_store_journeys( $journeys );
	return true;
}

/**
 * Attach the reader's verbatim refinement to the latest matching round.
 *
 * @param string $rotation   Rotation name.
 * @param int    $slot       Slot number.
 * @param string $category   Category text.
 * @param string $provider   Provider key.
 * @param string $item_id    Provider item id.
 * @param string $refinement Reader refinement.
 * @return bool True when saved.
 */
function adzworld_journey_record_refinement( $rotation, $slot, $category, $provider, $item_id, $refinement ) {
	$provider   = sanitize_key( $provider );
	$item_id    = sanitize_text_field( $item_id );
	$refinement = adzworld_journey_statement( $refinement );
	$journeys   = adzworld_journeys();
	$key        = adzworld_journey_key( $rotation, $slot, $category );

	if ( '' === $provider || '' === $item_id || '' === $refinement ) {
		return false;
	}
	if ( ! isset( $journeys[ $key ] ) ) {
		adzworld_journey_record_impression( $rotation, $slot, $category, $provider, $item_id, '' );
		$journeys = adzworld_journeys();
	}

	for ( $index = count( $journeys[ $key ]['rounds'] ) - 1; $index >= 0; --$index ) {
		$round = $journeys[ $key ]['rounds'][ $index ];
		if ( $provider === $round['provider'] && $item_id === $round['item_id'] ) {
			$journeys[ $key ]['rounds'][ $index ]['refinement'] = $refinement;
			adzworld_store_journeys( $journeys );
			return true;
		}
	}

	/* The refinement can arrive before the impression request completes. */
	adzworld_journey_record_impression( $rotation, $slot, $category, $provider, $item_id, '' );
	$journeys = adzworld_journeys();
	$index    = count( $journeys[ $key ]['rounds'] ) - 1;
	if ( $index < 0 ) {
		return false;
	}
	$journeys[ $key ]['rounds'][ $index ]['refinement'] = $refinement;
	adzworld_store_journeys( $journeys );
	return true;
}

/**
 * Exact item ids locally suppressed for one journey and provider.
 *
 * @param string $rotation Rotation name.
 * @param int    $slot     Slot number.
 * @param string $category Category text.
 * @param string $provider Provider key.
 * @return array<int,string> Exact item ids.
 */
function adzworld_journey_suppressed_items( $rotation, $slot, $category, $provider ) {
	$journeys = adzworld_journeys();
	$key      = adzworld_journey_key( $rotation, $slot, $category );
	$items    = array();
	if ( ! isset( $journeys[ $key ] ) ) {
		return $items;
	}
	foreach ( $journeys[ $key ]['rounds'] as $round ) {
		if ( sanitize_key( $provider ) === $round['provider'] && 'dislike' === $round['response'] ) {
			$items[] = $round['item_id'];
		}
	}

	return array_values( array_unique( $items ) );
}

add_action( 'wp_ajax_adzworld_journey_impression', 'adzworld_ajax_journey_impression' );
add_action( 'wp_ajax_nopriv_adzworld_journey_impression', 'adzworld_ajax_journey_impression' );

/**
 * Record a browser-confirmed product impression.
 *
 * @return void
 */
function adzworld_ajax_journey_impression() {
	check_ajax_referer( 'adzworld_feedback', 'nonce' );
	adzworld_journey_record_impression(
		isset( $_POST['rotation'] ) ? sanitize_key( wp_unslash( $_POST['rotation'] ) ) : '',
		isset( $_POST['slot'] ) ? absint( wp_unslash( $_POST['slot'] ) ) : 0,
		isset( $_POST['category'] ) ? adzworld_journey_statement( sanitize_text_field( wp_unslash( $_POST['category'] ) ) ) : '',
		isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '',
		isset( $_POST['item_id'] ) ? sanitize_text_field( wp_unslash( $_POST['item_id'] ) ) : '',
		isset( $_POST['request'] ) ? adzworld_journey_statement( sanitize_text_field( wp_unslash( $_POST['request'] ) ) ) : ''
	);
	wp_send_json_success();
}
