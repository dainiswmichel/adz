<?php
/**
 * Rating bands and slot response messages.
 *
 * A reader rates an adz on a slider from -100 to 100. Ratings bands turn that
 * number into words. An adzadmin sets how many bands there are, where each one
 * ends, and the words it shows. Bands are stored by their upper end only: the
 * first band starts at -100 and every later band starts one above the end of
 * the band before it. Stored that way, two bands can never overlap and there
 * can never be a gap between them.
 *
 * A response message is the short line an adz panel shows after a reader
 * responds to it: a rating, a refinement, or a category change. Each rotation
 * slot uses the site default message, its own message, or no message.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/** Option holding the ratings bands, lowest band first. */
const ADZWORLD_RATING_BANDS_OPTION = 'adzworld_rating_bands';

/** Option holding the site default response message. */
const ADZWORLD_RESPONSE_OPTION = 'adzworld_response_message';

/** Lowest score on the rating slider. */
const ADZWORLD_RATING_MIN = -100;

/** Highest score on the rating slider. */
const ADZWORLD_RATING_MAX = 100;

/** Longest band wording or response message, in characters. */
const ADZWORLD_RESPONSE_TEXT_MAX = 300;

/** Blank band rows offered in the editor for adding bands. */
const ADZWORLD_RATING_BAND_SPARE_ROWS = 3;

/**
 * The bands a fresh install starts with.
 *
 * These are the five bands the slider has always used.
 *
 * @return array<int,array{to:int,label:string}>
 */
function adzworld_rating_bands_default() {
	return array(
		array(
			'to'    => -75,
			'label' => __( 'Strongly dislike', 'adz-world' ),
		),
		array(
			'to'    => -25,
			'label' => __( 'Dislike', 'adz-world' ),
		),
		array(
			'to'    => 24,
			'label' => __( 'Neutral', 'adz-world' ),
		),
		array(
			'to'    => 74,
			'label' => __( 'Like', 'adz-world' ),
		),
		array(
			'to'    => ADZWORLD_RATING_MAX,
			'label' => __( 'Strongly like', 'adz-world' ),
		),
	);
}

/**
 * Check a list of bands and return it in stored form.
 *
 * Rows whose end and wording are both empty are ignored, which is how an
 * adzadmin removes a band. Every other row needs both.
 *
 * @param mixed $rows Rows of array{to:mixed,label:mixed}.
 * @return array<int,array{to:int,label:string}>|WP_Error Bands, or the first problem found.
 */
function adzworld_validate_rating_bands( $rows ) {
	if ( ! is_array( $rows ) ) {
		return new WP_Error( 'adzworld_bands_missing', __( 'No ratings bands were sent.', 'adz-world' ) );
	}

	$bands    = array();
	$previous = ADZWORLD_RATING_MIN - 1;
	foreach ( $rows as $row ) {
		$to    = isset( $row['to'] ) && is_scalar( $row['to'] ) ? trim( (string) $row['to'] ) : '';
		$label = isset( $row['label'] ) && is_scalar( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '';
		if ( '' === $to && '' === $label ) {
			continue;
		}
		if ( '' === $label ) {
			return new WP_Error( 'adzworld_bands_label', __( 'Every ratings band needs words.', 'adz-world' ) );
		}
		if ( ! preg_match( '/^-?[0-9]{1,3}$/D', $to ) ) {
			return new WP_Error(
				'adzworld_bands_number',
				sprintf(
					/* translators: %s: words of the ratings band. */
					__( 'The band “%s” needs a whole number from -100 to 100 where it ends.', 'adz-world' ),
					$label
				)
			);
		}
		$to = (int) $to;
		if ( $to < ADZWORLD_RATING_MIN || $to > ADZWORLD_RATING_MAX ) {
			return new WP_Error( 'adzworld_bands_range', __( 'Ratings bands must end between -100 and 100.', 'adz-world' ) );
		}
		if ( $to <= $previous ) {
			return new WP_Error(
				'adzworld_bands_order',
				sprintf(
					/* translators: %s: words of the ratings band. */
					__( 'The band “%s” must end higher than the band before it. Bands are listed from -100 upward and cannot overlap.', 'adz-world' ),
					$label
				)
			);
		}
		$bands[]  = array(
			'to'    => $to,
			'label' => mb_substr( $label, 0, ADZWORLD_RESPONSE_TEXT_MAX ),
		);
		$previous = $to;
	}

	if ( empty( $bands ) ) {
		return new WP_Error( 'adzworld_bands_empty', __( 'At least one ratings band is required.', 'adz-world' ) );
	}
	if ( ADZWORLD_RATING_MAX !== $previous ) {
		return new WP_Error( 'adzworld_bands_end', __( 'The last ratings band must end at 100, so every score has words.', 'adz-world' ) );
	}

	return $bands;
}

/**
 * The ratings bands in use, lowest first.
 *
 * @return array<int,array{to:int,label:string}>
 */
function adzworld_rating_bands() {
	$stored = get_option( ADZWORLD_RATING_BANDS_OPTION, null );
	if ( null === $stored ) {
		return adzworld_rating_bands_default();
	}
	$bands = adzworld_validate_rating_bands( $stored );

	return is_wp_error( $bands ) ? adzworld_rating_bands_default() : $bands;
}

/**
 * The bands with their computed start, for display and for the script.
 *
 * @return array<int,array{from:int,to:int,label:string}>
 */
function adzworld_rating_bands_with_start() {
	$from  = ADZWORLD_RATING_MIN;
	$bands = array();
	foreach ( adzworld_rating_bands() as $band ) {
		$bands[] = array(
			'from'  => $from,
			'to'    => $band['to'],
			'label' => $band['label'],
		);
		$from    = $band['to'] + 1;
	}

	return $bands;
}

/**
 * The words for one score.
 *
 * @param int $score Score from -100 to 100.
 * @return string Words of the band containing the score.
 */
function adzworld_rating_label( $score ) {
	$score = (int) $score;
	foreach ( adzworld_rating_bands() as $band ) {
		if ( $score <= $band['to'] ) {
			return $band['label'];
		}
	}

	return '';
}

/**
 * The site default response message.
 *
 * @return string
 */
function adzworld_default_response_message() {
	$stored = get_option( ADZWORLD_RESPONSE_OPTION, null );

	return is_string( $stored ) && '' !== $stored
		? $stored
		: __( 'Thank you. These adz now follow what you told us.', 'adz-world' );
}

/**
 * Response choices an adzadmin can make for one slot.
 *
 * @return array<string,string>
 */
function adzworld_slot_response_modes() {
	return array(
		'default' => __( 'Site default message', 'adz-world' ),
		'custom'  => __( 'This slot’s own message', 'adz-world' ),
		'none'    => __( 'No message', 'adz-world' ),
	);
}

/**
 * Clean one slot's response setting.
 *
 * @param mixed $raw Candidate array{mode:string,text:string}.
 * @return array{mode:string,text:string}
 */
function adzworld_sanitize_slot_response( $raw ) {
	$raw  = is_array( $raw ) ? $raw : array();
	$mode = isset( $raw['mode'] ) && is_string( $raw['mode'] ) ? sanitize_key( $raw['mode'] ) : 'default';
	$text = isset( $raw['text'] ) && is_scalar( $raw['text'] ) ? sanitize_text_field( (string) $raw['text'] ) : '';

	if ( ! isset( adzworld_slot_response_modes()[ $mode ] ) ) {
		$mode = 'default';
	}
	if ( 'custom' === $mode && '' === $text ) {
		$mode = 'default';
	}

	return array(
		'mode' => $mode,
		'text' => mb_substr( $text, 0, ADZWORLD_RESPONSE_TEXT_MAX ),
	);
}

/**
 * The message one slot shows after a reader responds.
 *
 * @param string $rotation Rotation key.
 * @param int    $position Zero-based position of the slot in the saved rotation.
 * @return string Message, or an empty string when the slot shows none.
 */
function adzworld_slot_response_message( $rotation, $position ) {
	$rotations = adzworld_named_rotations();
	$responses = isset( $rotations[ $rotation ]['responses'] ) && is_array( $rotations[ $rotation ]['responses'] )
		? $rotations[ $rotation ]['responses'] : array();
	$response  = adzworld_sanitize_slot_response( isset( $responses[ $position ] ) ? $responses[ $position ] : array() );

	if ( 'none' === $response['mode'] ) {
		return '';
	}

	return 'custom' === $response['mode'] ? $response['text'] : adzworld_default_response_message();
}

/**
 * Save the ratings bands and default response message from the Templates tab.
 *
 * @return true|WP_Error
 */
function adzworld_save_response_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'adzworld_responses_forbidden', __( 'Administrator permission is required.', 'adz-world' ) );
	}
	$nonce = isset( $_POST['adzworld_responses_nonce'] ) && is_string( $_POST['adzworld_responses_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_responses_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'adzworld_responses_save' ) ) {
		return new WP_Error( 'adzworld_responses_nonce', __( 'The ratings save request could not be verified.', 'adz-world' ) );
	}

	$rows  = isset( $_POST['adzworld_bands'] ) && is_array( $_POST['adzworld_bands'] )
		? map_deep( wp_unslash( $_POST['adzworld_bands'] ), 'sanitize_text_field' ) : array();
	$bands = adzworld_validate_rating_bands( array_values( $rows ) );
	if ( is_wp_error( $bands ) ) {
		return $bands;
	}

	$message = isset( $_POST['adzworld_response_message'] ) && is_string( $_POST['adzworld_response_message'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_response_message'] ) ) : '';
	if ( '' === $message ) {
		return new WP_Error( 'adzworld_responses_message', __( 'The default response message cannot be empty. A slot can still be set to show no message.', 'adz-world' ) );
	}

	update_option( ADZWORLD_RATING_BANDS_OPTION, $bands, false );
	update_option( ADZWORLD_RESPONSE_OPTION, mb_substr( $message, 0, ADZWORLD_RESPONSE_TEXT_MAX ), false );

	return true;
}

/**
 * Render the ratings bands and default response message editor.
 *
 * @return void
 */
function adzworld_response_settings() {
	$result = null;
	if ( null !== filter_input( INPUT_POST, 'adzworld_responses_submit', FILTER_UNSAFE_RAW ) ) {
		$result = adzworld_save_response_settings();
	}
	if ( true === $result || is_wp_error( $result ) ) {
		$class   = is_wp_error( $result ) ? 'notice notice-error' : 'notice notice-success';
		$message = is_wp_error( $result ) ? $result->get_error_message() : __( 'Ratings bands and response message saved.', 'adz-world' );
		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}

	$bands = adzworld_rating_bands_with_start();
	echo '<section class="adzworld-desk-intro adzworld-bands"><h2>' . esc_html__( 'Ratings bands', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'Readers rate an adz from -100 to 100. Each band gives a range of scores its own words. The first band starts at -100, each band starts one above the end of the band before it, and the last band ends at 100, so bands never overlap and every score has words. To remove a band, clear its end and its words. To add one, fill in a blank row.', 'adz-world' ) . '</p>';
	echo '<form method="post" action="">';
	wp_nonce_field( 'adzworld_responses_save', 'adzworld_responses_nonce' );
	echo '<table class="widefat striped adzworld-bands__table"><thead><tr><th scope="col">' . esc_html__( 'From', 'adz-world' ) . '</th><th scope="col">'
		. esc_html__( 'To', 'adz-world' ) . '</th><th scope="col">' . esc_html__( 'Words', 'adz-world' ) . '</th></tr></thead><tbody>';
	$total = count( $bands ) + ADZWORLD_RATING_BAND_SPARE_ROWS;
	for ( $index = 0; $index < $total; ++$index ) {
		$band = isset( $bands[ $index ] ) ? $bands[ $index ] : null;
		echo '<tr><td class="adzworld-bands__from">' . ( $band ? esc_html( (string) $band['from'] ) : '&mdash;' ) . '</td>';
		echo '<td><label class="screen-reader-text" for="adzworld-band-to-' . esc_attr( (string) $index ) . '">' . esc_html__( 'Band ends at', 'adz-world' ) . '</label>'
			. '<input class="small-text" type="number" min="-100" max="100" step="1" id="adzworld-band-to-' . esc_attr( (string) $index ) . '" name="adzworld_bands[' . esc_attr( (string) $index ) . '][to]" value="'
			. esc_attr( $band ? (string) $band['to'] : '' ) . '"></td>';
		echo '<td><label class="screen-reader-text" for="adzworld-band-label-' . esc_attr( (string) $index ) . '">' . esc_html__( 'Band words', 'adz-world' ) . '</label>'
			. '<input class="regular-text" type="text" maxlength="300" id="adzworld-band-label-' . esc_attr( (string) $index ) . '" name="adzworld_bands[' . esc_attr( (string) $index ) . '][label]" value="'
			. esc_attr( $band ? $band['label'] : '' ) . '"></td></tr>';
	}
	echo '</tbody></table>';

	echo '<h2>' . esc_html__( 'Response message', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'After a reader rates an adz, refines it, or changes its category, the adz panel shows this message. In the Rotation tab, each slot can use this message, its own message, or none.', 'adz-world' ) . '</p>';
	echo '<p><label for="adzworld-response-message">' . esc_html__( 'Default response message', 'adz-world' ) . '</label><br>'
		. '<input class="large-text" type="text" maxlength="300" id="adzworld-response-message" name="adzworld_response_message" value="' . esc_attr( adzworld_default_response_message() ) . '"></p>';
	echo '<p class="submit">';
	submit_button( __( 'Save ratings bands and response message', 'adz-world' ), 'secondary', 'adzworld_responses_submit', false );
	echo '</p></form></section>';
}
