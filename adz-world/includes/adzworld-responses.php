<?php
/**
 * Rating bands and slot response messages.
 *
 * A reader rates an adz on a slider from -100 to 100. Ratings bands turn that
 * number into words. An adzadmin sets where each band starts, where it ends,
 * and the words it shows. The bands must cover -100 through 100 with no gap
 * and no overlap, so every score has words.
 *
 * The slider colour theme is saved with the bands. Standard is the curve in
 * assets/rotation.css. Ember and any theme an adzadmin names use three hex
 * colours. The plugin draws the gradient.
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

/** Option holding named slider themes an adzadmin has saved. */
const ADZWORLD_RATINGS_SLIDER_THEMES_OPTION = 'adzworld_ratings_slider_themes';

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
 * A #RRGGBB colour, or an empty string when the text is not one.
 *
 * @param mixed $raw Candidate colour.
 * @return string
 */
function adzworld_ratings_slider_hex( $raw ) {
	$raw = is_string( $raw ) ? strtoupper( trim( $raw ) ) : '';
	if ( preg_match( '/^#[0-9A-F]{6}$/', $raw ) ) {
		return $raw;
	}
	if ( preg_match( '/^[0-9A-F]{6}$/', $raw ) ) {
		return '#' . $raw;
	}

	return '';
}

/**
 * Built-in slider themes.
 *
 * Standard is the curve in assets/rotation.css. Tide is the blue curve in
 * assets/ratings-slider-tide.css. Ember is ashy grey, black, and glowing
 * ember orange. Its gradient is drawn by this file.
 *
 * @return array<string,array{name:string,colors:array<int,string>}>
 */
function adzworld_ratings_slider_builtin_themes() {
	return array(
		'standard' => array(
			'name'   => __( 'Standard', 'adz-world' ),
			'colors' => array(),
		),
		'ember'    => array(
			'name'   => __( 'Ember', 'adz-world' ),
			'colors' => array( '#A3A7AD', '#000000', '#FF6A00' ),
		),
		'tide'     => array(
			'name'   => __( 'Tide', 'adz-world' ),
			'colors' => array(),
		),
	);
}

/**
 * Named slider themes saved by an adzadmin.
 *
 * @return array<string,array{name:string,colors:array<int,string>}>
 */
function adzworld_ratings_slider_custom_themes() {
	$stored = get_option( ADZWORLD_RATINGS_SLIDER_THEMES_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		return array();
	}
	$themes = array();
	foreach ( $stored as $slug => $theme ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug || isset( adzworld_ratings_slider_builtin_themes()[ $slug ] ) || ! is_array( $theme ) ) {
			continue;
		}
		$name   = isset( $theme['name'] ) && is_string( $theme['name'] ) ? sanitize_text_field( $theme['name'] ) : '';
		$colors = isset( $theme['colors'] ) && is_array( $theme['colors'] ) ? $theme['colors'] : array();
		$clean  = array();
		foreach ( $colors as $color ) {
			$hex = adzworld_ratings_slider_hex( is_string( $color ) ? $color : '' );
			if ( '' !== $hex ) {
				$clean[] = $hex;
			}
		}
		if ( '' === $name || 3 !== count( $clean ) ) {
			continue;
		}
		$themes[ $slug ] = array(
			'name'   => $name,
			'colors' => $clean,
		);
	}

	return $themes;
}

/**
 * Slider colour themes an adzadmin can choose.
 *
 * @return array<string,array{name:string,colors:array<int,string>}>
 */
function adzworld_ratings_slider_themes() {
	return array_merge( adzworld_ratings_slider_builtin_themes(), adzworld_ratings_slider_custom_themes() );
}

/**
 * The slider theme in use.
 *
 * @return string Theme slug.
 */
function adzworld_ratings_slider_theme() {
	$stored = get_option( ADZWORLD_RATINGS_SLIDER_OPTION, 'standard' );
	$stored = is_string( $stored ) ? sanitize_key( $stored ) : 'standard';

	return isset( adzworld_ratings_slider_themes()[ $stored ] ) ? $stored : 'standard';
}

/**
 * Stylesheet class for the active slider theme.
 *
 * Standard uses the curve in assets/rotation.css. Tide uses
 * assets/ratings-slider-tide.css. Every hex theme uses
 * adzworld-ratings-slider-custom, whose rules are built from that theme.
 *
 * @return string
 */
function adzworld_ratings_slider_theme_class() {
	$themes = adzworld_ratings_slider_themes();
	$theme  = adzworld_ratings_slider_theme();
	if ( 'tide' === $theme ) {
		return 'adzworld-ratings-slider-tide';
	}
	$colors = isset( $themes[ $theme ]['colors'] ) ? $themes[ $theme ]['colors'] : array();

	return 3 === count( $colors ) ? 'adzworld-ratings-slider-custom' : 'adzworld-ratings-slider-standard';
}

/**
 * Mix two #RRGGBB colours. $amount is 0 at $from and 1 at $to.
 *
 * @param string $from   Start colour.
 * @param string $to     End colour.
 * @param float  $amount Blend amount.
 * @return string
 */
function adzworld_ratings_slider_mix( $from, $to, $amount ) {
	$amount = max( 0, min( 1, $amount ) );
	$left   = sscanf( $from, '#%02x%02x%02x' );
	$right  = sscanf( $to, '#%02x%02x%02x' );
	if ( ! is_array( $left ) || 3 !== count( $left ) || ! is_array( $right ) || 3 !== count( $right ) ) {
		return $from;
	}
	$mixed = array();
	foreach ( array( 0, 1, 2 ) as $channel ) {
		$mixed[] = (int) round( $left[ $channel ] + ( $right[ $channel ] - $left[ $channel ] ) * $amount );
	}

	return sprintf( '#%02X%02X%02X', $mixed[0], $mixed[1], $mixed[2] );
}

/**
 * Gradient stops for three hex colours.
 *
 * The middle colour is held from 38% to 62%, which is scores -24 through 24.
 * The plugin chooses the stops. The adzadmin chooses the three colours.
 *
 * @param array<int,string> $colors Dislike, middle, and like colours.
 * @return array<int,array{0:float,1:string}>
 */
function adzworld_ratings_slider_stops( $colors ) {
	return array(
		array( 0.00, $colors[0] ),
		array( 0.20, adzworld_ratings_slider_mix( $colors[0], $colors[1], 0.72 ) ),
		array( 0.38, $colors[1] ),
		array( 0.62, $colors[1] ),
		array( 0.80, adzworld_ratings_slider_mix( $colors[1], $colors[2], 0.72 ) ),
		array( 1.00, $colors[2] ),
	);
}

/**
 * The colour at one position on a three-colour slider.
 *
 * @param array<int,string> $colors   Dislike, middle, and like colours.
 * @param float             $position Position from 0 to 1.
 * @return string
 */
function adzworld_ratings_slider_color_at( $colors, $position ) {
	$stops = adzworld_ratings_slider_stops( $colors );
	$last  = count( $stops ) - 1;
	if ( $position <= $stops[0][0] ) {
		return $stops[0][1];
	}
	if ( $position >= $stops[ $last ][0] ) {
		return $stops[ $last ][1];
	}
	foreach ( $stops as $index => $stop ) {
		if ( $index === $last ) {
			break;
		}
		$next = $stops[ $index + 1 ];
		if ( $position < $stop[0] || $position > $next[0] ) {
			continue;
		}
		$span  = $next[0] - $stop[0];
		$share = $span <= 0 ? 0 : ( $position - $stop[0] ) / $span;

		return adzworld_ratings_slider_mix( $stop[1], $next[1], $share );
	}

	return $colors[2];
}

/**
 * CSS gradient for three hex colours.
 *
 * @param array<int,string> $colors Dislike, middle, and like colours.
 * @return string
 */
function adzworld_ratings_slider_gradient( $colors ) {
	$parts = array();
	foreach ( adzworld_ratings_slider_stops( $colors ) as $stop ) {
		$parts[] = $stop[1] . ' ' . (string) (int) round( $stop[0] * 100 ) . '%';
	}

	return 'linear-gradient(90deg, ' . implode( ', ', $parts ) . ')';
}

/**
 * Track and thumb rules for the active hex theme.
 *
 * @param array<int,string> $colors Dislike, middle, and like colours.
 * @return string
 */
function adzworld_ratings_slider_css( $colors ) {
	if ( 3 !== count( $colors ) ) {
		return '';
	}
	$lines = array(
		'.adzworld-ratings-slider-custom input[type="range"] { background: ' . adzworld_ratings_slider_gradient( $colors ) . '; }',
	);
	for ( $score = ADZWORLD_RATING_MIN; $score <= ADZWORLD_RATING_MAX; $score++ ) {
		$name    = $score < 0 ? 'minus-' . (string) abs( $score ) : (string) $score;
		$color   = adzworld_ratings_slider_color_at( $colors, ( $score + 100 ) / 200 );
		$lines[] = '.adzworld-ratings-slider-custom input.is-score-' . $name . ' { --adzworld-thumb: ' . $color . '; }';
	}

	return implode( "\n", $lines );
}

/**
 * Admin swatches for every hex theme.
 *
 * @return string
 */
function adzworld_ratings_slider_swatch_css() {
	$lines = array();
	foreach ( adzworld_ratings_slider_themes() as $slug => $theme ) {
		if ( 3 !== count( $theme['colors'] ) ) {
			continue;
		}
		$lines[] = '.adzworld-bands__swatch--' . sanitize_html_class( $slug ) . ' { background: ' . adzworld_ratings_slider_gradient( $theme['colors'] ) . '; }';
	}

	return implode( "\n", $lines );
}

/**
 * Store a named three-colour theme and make it the active theme.
 *
 * @param string            $name   Theme name.
 * @param array<int,string> $colors Dislike, middle, and like colours.
 * @return string|WP_Error Stored slug, or the problem found.
 */
function adzworld_ratings_slider_save_named( $name, $colors ) {
	$name = mb_substr( sanitize_text_field( $name ), 0, 40 );
	if ( '' === $name ) {
		return new WP_Error( 'adzworld_slider_theme_name', __( 'Name the slider theme.', 'adz-world' ) );
	}
	$slug = sanitize_title( $name );
	if ( '' === $slug || isset( adzworld_ratings_slider_builtin_themes()[ $slug ] ) ) {
		return new WP_Error( 'adzworld_slider_theme_reserved', __( 'That slider theme name is reserved. Choose another name.', 'adz-world' ) );
	}
	$clean = array();
	foreach ( $colors as $color ) {
		$hex = adzworld_ratings_slider_hex( $color );
		if ( '' === $hex ) {
			return new WP_Error( 'adzworld_slider_theme_hex', __( 'Each slider colour must be a hex value such as #A3A7AD.', 'adz-world' ) );
		}
		$clean[] = $hex;
	}
	if ( 3 !== count( $clean ) ) {
		return new WP_Error( 'adzworld_slider_theme_hex', __( 'A slider theme needs three hex colours.', 'adz-world' ) );
	}
	$stored          = adzworld_ratings_slider_custom_themes();
	$stored[ $slug ] = array(
		'name'   => $name,
		'colors' => $clean,
	);
	update_option( ADZWORLD_RATINGS_SLIDER_THEMES_OPTION, $stored, false );
	update_option( ADZWORLD_RATINGS_SLIDER_OPTION, $slug, false );

	return $slug;
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
 * Save a named slider theme from the Templates tab.
 *
 * @return string|WP_Error
 */
function adzworld_save_named_slider_theme() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'adzworld_responses_forbidden', __( 'Administrator permission is required.', 'adz-world' ) );
	}
	$nonce = isset( $_POST['adzworld_responses_nonce'] ) && is_string( $_POST['adzworld_responses_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_responses_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'adzworld_responses_save' ) ) {
		return new WP_Error( 'adzworld_responses_nonce', __( 'The ratings save request could not be verified.', 'adz-world' ) );
	}
	$name   = isset( $_POST['adzworld_theme_name'] ) && is_string( $_POST['adzworld_theme_name'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_theme_name'] ) ) : '';
	$colors = array();
	for ( $index = 0; $index < 3; $index++ ) {
		$hex      = isset( $_POST['adzworld_theme_hex'][ $index ] ) && is_string( $_POST['adzworld_theme_hex'][ $index ] )
			? sanitize_text_field( wp_unslash( $_POST['adzworld_theme_hex'][ $index ] ) ) : '';
		$picker   = isset( $_POST['adzworld_theme_picker'][ $index ] ) && is_string( $_POST['adzworld_theme_picker'][ $index ] )
			? sanitize_text_field( wp_unslash( $_POST['adzworld_theme_picker'][ $index ] ) ) : '';
		$colors[] = '' !== adzworld_ratings_slider_hex( $hex ) ? adzworld_ratings_slider_hex( $hex ) : $picker;
	}

	return adzworld_ratings_slider_save_named( $name, $colors );
}

/**
 * Render the ratings bands and default response message editor.
 *
 * @return void
 */
function adzworld_response_settings() {
	$result  = null;
	$success = __( 'Ratings bands, slider colours, and response message saved.', 'adz-world' );
	if ( null !== filter_input( INPUT_POST, 'adzworld_slider_theme_save', FILTER_UNSAFE_RAW ) ) {
		$result  = adzworld_save_named_slider_theme();
		$success = __( 'Slider theme saved.', 'adz-world' );
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
	$active_colors = isset( adzworld_ratings_slider_themes()[ $theme ]['colors'] ) ? adzworld_ratings_slider_themes()[ $theme ]['colors'] : array();
	if ( 3 !== count( $active_colors ) ) {
		$active_colors = adzworld_ratings_slider_builtin_themes()['ember']['colors'];
	}
	echo '<fieldset class="adzworld-bands__themes"><legend>' . esc_html__( 'Slider colours', 'adz-world' ) . '</legend>';
	echo '<p>' . esc_html__( 'Standard keeps its gradient. Tide is the blue theme. Ember is ashy grey, black, and glowing ember orange. A named theme uses three hex colours, and the plugin draws the gradient.', 'adz-world' ) . '</p>';
	foreach ( adzworld_ratings_slider_themes() as $slug => $slider_theme ) {
		echo '<label class="adzworld-bands__theme"><input type="radio" name="adzworld_ratings_slider_theme" value="' . esc_attr( $slug ) . '" ' . checked( $theme, $slug, false ) . '>';
		echo '<span class="adzworld-bands__swatch adzworld-bands__swatch--' . esc_attr( sanitize_html_class( $slug ) ) . '" aria-hidden="true"></span>';
		echo '<span>' . esc_html( $slider_theme['name'] ) . '</span></label>';
	}
	echo '<p class="adzworld-bands__save-theme"><label for="adzworld-theme-name">' . esc_html__( 'Theme name', 'adz-world' ) . '</label><br>';
	echo '<input class="regular-text" type="text" maxlength="40" id="adzworld-theme-name" name="adzworld_theme_name" value=""></p>';
	$hex_labels = array(
		__( 'Dislike', 'adz-world' ),
		__( 'Neutral', 'adz-world' ),
		__( 'Like', 'adz-world' ),
	);
	foreach ( $hex_labels as $index => $hex_label ) {
		$hex_value = strtolower( $active_colors[ $index ] );
		echo '<label class="adzworld-bands__hex">' . esc_html( $hex_label );
		echo '<input type="color" name="adzworld_theme_picker[' . esc_attr( (string) $index ) . ']" value="' . esc_attr( $hex_value ) . '">';
		echo '<input type="text" maxlength="7" spellcheck="false" name="adzworld_theme_hex[' . esc_attr( (string) $index ) . ']" value="' . esc_attr( strtoupper( $active_colors[ $index ] ) ) . '"></label>';
	}
	echo '<p class="adzworld-bands__save-theme">';
	submit_button( __( 'Save as slider theme', 'adz-world' ), 'secondary', 'adzworld_slider_theme_save', false );
	echo '</p></fieldset>';
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
	submit_button( __( 'Save ratings bands and slider colours', 'adz-world' ), 'secondary', 'adzworld_responses_submit', false );
	echo '</p></form></section>';
}
