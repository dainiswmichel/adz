<?php
/**
 * Rating bands and slot response messages.
 *
 * A reader rates an adz on a slider from -100 to 100. Ratings bands turn that
 * number into words. An adzadmin sets where each band starts, where it ends,
 * and the words it shows. The bands must cover -100 through 100 with no gap
 * and no overlap, so every score has words.
 *
 * The slider colour theme is saved with the bands. Default is the curve in
 * assets/rotation.css. Tide and Ember use their own external stylesheets.
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

/** Option holding the active slider theme slug. */
const ADZWORLD_RATINGS_SLIDER_OPTION = 'adzworld_ratings_slider_theme';

/** Option holding the site default response message. */
const ADZWORLD_RESPONSE_OPTION = 'adzworld_response_message';

/** Lowest score on the rating slider. */
const ADZWORLD_RATING_MIN = -100;

/** Highest score on the rating slider. */
const ADZWORLD_RATING_MAX = 100;

/** Longest band wording or response message, in characters. */
const ADZWORLD_RESPONSE_TEXT_MAX = 300;

/** Blank band rows offered in the editor for adding bands. */
const ADZWORLD_RATING_BAND_SPARE_ROWS = 2;

/**
 * The bands a fresh install starts with.
 *
 * These are the five bands the slider has always used.
 *
 * @return array<int,array{from:int,to:int,label:string}>
 */
function adzworld_rating_bands_default() {
	return array(
		array(
			'from'  => ADZWORLD_RATING_MIN,
			'to'    => -75,
			'label' => __( 'Strongly dislike', 'adz-world' ),
		),
		array(
			'from'  => -74,
			'to'    => -25,
			'label' => __( 'Dislike', 'adz-world' ),
		),
		array(
			'from'  => -24,
			'to'    => 24,
			'label' => __( 'Neutral', 'adz-world' ),
		),
		array(
			'from'  => 25,
			'to'    => 74,
			'label' => __( 'Like', 'adz-world' ),
		),
		array(
			'from'  => 75,
			'to'    => ADZWORLD_RATING_MAX,
			'label' => __( 'Strongly like', 'adz-world' ),
		),
	);
}

/**
 * Built-in slider themes.
 *
 * @return array<string,array{name:string}>
 */
function adzworld_ratings_slider_builtin_themes() {
	return array(
		'adz-ratings-default-slider-theme' => array(
			'name' => __( 'Default', 'adz-world' ),
		),
		'adz-ratings-ember-slider-theme'   => array(
			'name' => __( 'Ember', 'adz-world' ),
		),
		'adz-ratings-tide-slider-theme'    => array(
			'name' => __( 'Tide', 'adz-world' ),
		),
	);
}

/**
 * Slider colour themes an adzadmin can choose.
 *
 * @return array<string,array{name:string}>
 */
function adzworld_ratings_slider_themes() {
	return adzworld_ratings_slider_builtin_themes();
}

/**
 * The slider theme in use.
 *
 * @return string Theme slug.
 */
function adzworld_ratings_slider_theme() {
	$stored = get_option( ADZWORLD_RATINGS_SLIDER_OPTION, 'adz-ratings-default-slider-theme' );
	$stored = is_string( $stored ) ? sanitize_key( $stored ) : 'adz-ratings-default-slider-theme';

	return isset( adzworld_ratings_slider_themes()[ $stored ] ) ? $stored : 'adz-ratings-default-slider-theme';
}

/**
 * Stylesheet class for the active slider theme.
 *
 * Default uses the curve in assets/rotation.css. Tide and Ember use external
 * theme stylesheets.
 *
 * @return string
 */
function adzworld_ratings_slider_theme_class() {
	return adzworld_ratings_slider_theme();
}

/**
 * Check a list of bands and return it in stored form.
 *
 * A row with an empty start, end, and wording is ignored, which is how an
 * adzadmin removes a band. Every other row needs all three. A stored band
 * from before starts were saved has no start key; that start is the end of
 * the previous band plus one.
 *
 * @param mixed $rows Rows of array{from?:mixed,to:mixed,label:mixed}.
 * @return array<int,array{from:int,to:int,label:string}>|WP_Error Bands, or the first problem found.
 */
function adzworld_validate_rating_bands( $rows ) {
	if ( ! is_array( $rows ) ) {
		return new WP_Error( 'adzworld_bands_missing', __( 'No ratings bands were sent.', 'adz-world' ) );
	}

	$bands    = array();
	$previous = ADZWORLD_RATING_MIN - 1;
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$has_from = array_key_exists( 'from', $row );
		$from_raw = $has_from && is_scalar( $row['from'] ) ? trim( (string) $row['from'] ) : '';
		$to_raw   = isset( $row['to'] ) && is_scalar( $row['to'] ) ? trim( (string) $row['to'] ) : '';
		$label    = isset( $row['label'] ) && is_scalar( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '';
		if ( '' === $from_raw && '' === $to_raw && '' === $label ) {
			continue;
		}
		if ( '' === $label ) {
			return new WP_Error( 'adzworld_bands_label', __( 'Every ratings band needs words.', 'adz-world' ) );
		}
		$label = mb_substr( $label, 0, ADZWORLD_RESPONSE_TEXT_MAX );
		if ( ! $has_from ) {
			$from = $previous + 1;
		} else {
			$from = adzworld_rating_band_score( $from_raw );
			if ( null === $from ) {
				return new WP_Error(
					'adzworld_bands_number',
					sprintf(
						/* translators: %s: words of the ratings band. */
						__( 'The band “%s” needs a whole number from -100 to 100 where it starts.', 'adz-world' ),
						$label
					)
				);
			}
		}
		$to = adzworld_rating_band_score( $to_raw );
		if ( null === $to ) {
			return new WP_Error(
				'adzworld_bands_number',
				sprintf(
					/* translators: %s: words of the ratings band. */
					__( 'The band “%s” needs a whole number from -100 to 100 where it ends.', 'adz-world' ),
					$label
				)
			);
		}
		if ( $from > $to ) {
			return new WP_Error(
				'adzworld_bands_order',
				sprintf(
					/* translators: %s: words of the ratings band. */
					__( 'The band “%s” must end at or after where it starts.', 'adz-world' ),
					$label
				)
			);
		}
		if ( ADZWORLD_RATING_MIN === $previous + 1 && ADZWORLD_RATING_MIN !== $from ) {
			return new WP_Error( 'adzworld_bands_start', __( 'The first ratings band must start at -100.', 'adz-world' ) );
		}
		if ( $from <= $previous ) {
			return new WP_Error(
				'adzworld_bands_order',
				sprintf(
					/* translators: %s: words of the ratings band. */
					__( 'The band “%s” overlaps the band before it. Bands are listed from -100 upward.', 'adz-world' ),
					$label
				)
			);
		}
		if ( $from !== $previous + 1 ) {
			return new WP_Error(
				'adzworld_bands_gap',
				sprintf(
					/* translators: 1: words of the ratings band, 2: score where the band has to start. */
					__( 'The band “%1$s” must start at %2$d, so no score is left without words.', 'adz-world' ),
					$label,
					$previous + 1
				)
			);
		}
		$bands[]  = array(
			'from'  => $from,
			'to'    => $to,
			'label' => $label,
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
 * A whole score from -100 to 100, or null when the text is not one.
 *
 * @param string $raw Candidate text.
 * @return int|null
 */
function adzworld_rating_band_score( $raw ) {
	if ( ! preg_match( '/^-?[0-9]{1,3}$/D', $raw ) ) {
		return null;
	}
	$score = (int) $raw;
	if ( $score < ADZWORLD_RATING_MIN || $score > ADZWORLD_RATING_MAX ) {
		return null;
	}

	return $score;
}

/**
 * The ratings bands in use, lowest first.
 *
 * @return array<int,array{from:int,to:int,label:string}>
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
 * The bands with their starts, for display and for the script.
 *
 * Starts are stored. This remains so earlier callers keep one function.
 *
 * @return array<int,array{from:int,to:int,label:string}>
 */
function adzworld_rating_bands_with_start() {
	return adzworld_rating_bands();
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
		if ( $score >= $band['from'] && $score <= $band['to'] ) {
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

	$theme = isset( $_POST['adzworld_ratings_slider_theme'] ) && is_string( $_POST['adzworld_ratings_slider_theme'] )
		? sanitize_key( wp_unslash( $_POST['adzworld_ratings_slider_theme'] ) ) : '';
	if ( '' === $theme ) {
		$theme = adzworld_ratings_slider_theme();
	}
	if ( ! isset( adzworld_ratings_slider_themes()[ $theme ] ) ) {
		return new WP_Error( 'adzworld_ratings_slider_theme', __( 'Choose a slider theme.', 'adz-world' ) );
	}

	$message = isset( $_POST['adzworld_response_message'] ) && is_string( $_POST['adzworld_response_message'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_response_message'] ) ) : '';
	if ( '' === $message ) {
		return new WP_Error( 'adzworld_responses_message', __( 'The default response message cannot be empty. A slot can still be set to show no message.', 'adz-world' ) );
	}

	update_option( ADZWORLD_RATING_BANDS_OPTION, $bands, false );
	update_option( ADZWORLD_RATINGS_SLIDER_OPTION, $theme, false );
	update_option( ADZWORLD_RESPONSE_OPTION, mb_substr( $message, 0, ADZWORLD_RESPONSE_TEXT_MAX ), false );

	return true;
}

/**
 * Save only the default response message.
 *
 * @return true|WP_Error
 */
function adzworld_save_response_message() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'adzworld_responses_forbidden', __( 'Administrator permission is required.', 'adz-world' ) );
	}
	$nonce = isset( $_POST['adzworld_responses_nonce'] ) && is_string( $_POST['adzworld_responses_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_responses_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'adzworld_responses_save' ) ) {
		return new WP_Error( 'adzworld_responses_nonce', __( 'The ratings save request could not be verified.', 'adz-world' ) );
	}
	$message = isset( $_POST['adzworld_response_message'] ) && is_string( $_POST['adzworld_response_message'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_response_message'] ) ) : '';
	if ( '' === $message ) {
		return new WP_Error( 'adzworld_responses_message', __( 'The default response message cannot be empty. A slot can still be set to show no message.', 'adz-world' ) );
	}
	update_option( ADZWORLD_RESPONSE_OPTION, mb_substr( $message, 0, ADZWORLD_RESPONSE_TEXT_MAX ), false );

	return true;
}

/**
 * Render the ratings bands and default response message editor.
 *
 * @return void
 */
function adzworld_response_settings() {
	$result  = null;
	$success = __( 'Ratings bands, slider colours, and response message saved.', 'adz-world' );
	if ( null !== filter_input( INPUT_POST, 'adzworld_response_message_save', FILTER_UNSAFE_RAW ) ) {
		$result  = adzworld_save_response_message();
		$success = __( 'Response message saved.', 'adz-world' );
	} elseif ( null !== filter_input( INPUT_POST, 'adzworld_responses_submit', FILTER_UNSAFE_RAW ) ) {
		$result = adzworld_save_response_settings();
	}
	if ( true === $result || is_string( $result ) || is_wp_error( $result ) ) {
		$class   = is_wp_error( $result ) ? 'notice notice-error' : 'notice notice-success';
		$message = is_wp_error( $result ) ? $result->get_error_message() : $success;
		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}

	$bands = adzworld_rating_bands_with_start();
	$theme = adzworld_ratings_slider_theme();
	echo '<section class="adzworld-desk-intro adzworld-bands"><h2>' . esc_html__( 'Ratings bands', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'Readers rate an adzad from -100 to 100. Each row is one band: where it starts, where it ends, and the words for that range. Together the bands cover every score from -100 to 100, with no gap and no overlap. Clear a row to remove it. Fill an empty row to add one.', 'adz-world' ) . '</p>';
	echo '<form method="post" action="">';
	wp_nonce_field( 'adzworld_responses_save', 'adzworld_responses_nonce' );
	echo '<fieldset class="adzworld-bands__themes"><legend>' . esc_html__( 'Slider colours', 'adz-world' ) . '</legend>';
	echo '<p>' . esc_html__( 'Default keeps the original gradient. Tide is blue. Ember is ashy grey, black, and glowing ember orange.', 'adz-world' ) . '</p>';
	foreach ( adzworld_ratings_slider_themes() as $slug => $slider_theme ) {
		echo '<label class="adzworld-bands__theme"><input type="radio" name="adzworld_ratings_slider_theme" value="' . esc_attr( $slug ) . '" ' . checked( $theme, $slug, false ) . '>';
		echo '<span class="adzworld-bands__swatch adzworld-bands__swatch--' . esc_attr( sanitize_html_class( $slug ) ) . '" aria-hidden="true"></span>';
		echo '<span>' . esc_html( $slider_theme['name'] ) . '</span></label>';
	}
	echo '</fieldset>';
	echo '<table class="widefat striped adzworld-bands__table"><thead><tr><th scope="col">' . esc_html__( 'From', 'adz-world' ) . '</th><th scope="col">'
		. esc_html__( 'To', 'adz-world' ) . '</th><th scope="col">' . esc_html__( 'Words', 'adz-world' ) . '</th></tr></thead><tbody>';
	$total = count( $bands ) + ADZWORLD_RATING_BAND_SPARE_ROWS;
	for ( $index = 0; $index < $total; ++$index ) {
		$band = isset( $bands[ $index ] ) ? $bands[ $index ] : null;
		echo '<tr><td><label class="screen-reader-text" for="adzworld-band-from-' . esc_attr( (string) $index ) . '">' . esc_html__( 'Band starts at', 'adz-world' ) . '</label>'
			. '<input class="adzworld-bands__number" type="text" inputmode="numeric" maxlength="4" id="adzworld-band-from-' . esc_attr( (string) $index ) . '" name="adzworld_bands[' . esc_attr( (string) $index ) . '][from]" value="'
			. esc_attr( $band ? (string) $band['from'] : '' ) . '"></td>';
		echo '<td><label class="screen-reader-text" for="adzworld-band-to-' . esc_attr( (string) $index ) . '">' . esc_html__( 'Band ends at', 'adz-world' ) . '</label>'
			. '<input class="adzworld-bands__number" type="text" inputmode="numeric" maxlength="4" id="adzworld-band-to-' . esc_attr( (string) $index ) . '" name="adzworld_bands[' . esc_attr( (string) $index ) . '][to]" value="'
			. esc_attr( $band ? (string) $band['to'] : '' ) . '"></td>';
		echo '<td><label class="screen-reader-text" for="adzworld-band-label-' . esc_attr( (string) $index ) . '">' . esc_html__( 'Band words', 'adz-world' ) . '</label>'
			. '<input class="adzworld-bands__words" type="text" maxlength="300" id="adzworld-band-label-' . esc_attr( (string) $index ) . '" name="adzworld_bands[' . esc_attr( (string) $index ) . '][label]" value="'
			. esc_attr( $band ? $band['label'] : '' ) . '"></td></tr>';
	}
	echo '</tbody></table>';
	echo '<p class="submit">';
	submit_button( __( 'Save ratings bands and slider theme', 'adz-world' ), 'secondary', 'adzworld_responses_submit', false );
	echo '</p>';

	$draft = adzworld_default_response_message();
	if ( is_wp_error( $result ) ) {
		$draft_nonce = isset( $_POST['adzworld_responses_nonce'] ) && is_string( $_POST['adzworld_responses_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['adzworld_responses_nonce'] ) ) : '';
		if ( wp_verify_nonce( $draft_nonce, 'adzworld_responses_save' ) && isset( $_POST['adzworld_response_message'] ) && is_string( $_POST['adzworld_response_message'] ) ) {
			$draft = sanitize_text_field( wp_unslash( $_POST['adzworld_response_message'] ) );
		}
	}
	echo '<h2>' . esc_html__( 'Response message', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'After a reader rates an adzad, refines it, or changes its category, the adzad panel shows this message. In the Rotation tab, each slot can use this message, its own message, or none.', 'adz-world' ) . '</p>';
	echo '<p><label for="adzworld-response-message">' . esc_html__( 'Default response message', 'adz-world' ) . '</label><br>'
		. '<textarea class="adzworld-bands__message" maxlength="300" rows="3" cols="60" id="adzworld-response-message" name="adzworld_response_message">'
		. esc_textarea( $draft ) . '</textarea></p>';
	echo '<p class="submit">';
	submit_button( __( 'Save response message', 'adz-world' ), 'secondary', 'adzworld_response_message_save', false );
	echo '</p></form></section>';
}
