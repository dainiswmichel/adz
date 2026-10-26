<?php
/**
 * Explicitly configured advertising rotations.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'adz_rotation', 'adzworld_render_rotation' );
add_action( 'admin_menu', 'adzworld_rotation_admin_menu' );

/**
 * Register a publisher's existing shortcode as a rotation source.
 *
 * Registration is server-side: page authors cannot execute arbitrary tags or
 * change the provider's configuration through rotation attributes.
 *
 * @param string $key       Source identifier.
 * @param string $shortcode Existing provider shortcode and its configuration.
 * @param string $label     Accessible source label.
 * @return true|WP_Error Whether the source was registered.
 */
function adzworld_register_rotation_source( $key, $shortcode, $label ) {
	if ( ! is_string( $key ) || ! preg_match( '/^[a-z][a-z0-9_-]{0,49}$/D', $key )
		|| ! is_string( $shortcode ) || ! is_string( $label ) || '' === trim( $label )
		|| ! preg_match( '/^\[([a-zA-Z][a-zA-Z0-9_-]*)\b[^\]]*\]$/D', $shortcode, $match )
		|| ! shortcode_exists( $match[1] ) || 'adz_rotation' === $match[1] ) {
		return new WP_Error( 'adzworld_invalid_source', __( 'A rotation source requires a registered provider shortcode and a label.', 'adz-world' ) );
	}

	$sources = adzworld_rotation_sources();
	if ( isset( $sources[ $key ] ) ) {
		return new WP_Error( 'adzworld_duplicate_source', __( 'That rotation source is already registered.', 'adz-world' ) );
	}

	$sources[ $key ]                      = array(
		'shortcode' => $shortcode,
		'label'     => sanitize_text_field( $label ),
	);
	$GLOBALS['adzworld_rotation_sources'] = $sources;

	return true;
}

/**
 * Retrieve source registrations for this request.
 *
 * @return array Registered provider sources.
 */
function adzworld_rotation_sources() {
	$saved = get_option( 'adzworld_rotation_sources', array() );
	$saved = is_array( $saved ) ? $saved : array();
	foreach ( $saved as $key => $source ) {
		if ( ! is_string( $key ) || ! is_array( $source ) || ! isset( $source['shortcode'], $source['label'] )
			|| ! is_string( $source['shortcode'] ) || ! is_string( $source['label'] ) ) {
			unset( $saved[ $key ] );
		}
	}
	$registered = isset( $GLOBALS['adzworld_rotation_sources'] ) && is_array( $GLOBALS['adzworld_rotation_sources'] )
		? $GLOBALS['adzworld_rotation_sources'] : array();
	return array_merge( $saved, $registered );
}

/**
 * Add the existing-source setup page.
 *
 * @return void
 */
function adzworld_rotation_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=adz_ad',
		__( 'Rotation sources', 'adz-world' ),
		__( 'Rotation sources', 'adz-world' ),
		'manage_options',
		'adzworld-rotation-sources',
		'adzworld_rotation_admin_page'
	);
}

/**
 * Save or remove one explicitly configured source with independent authorization.
 *
 * @return true|WP_Error Whether the requested change succeeded.
 */
function adzworld_save_rotation_source() {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'unfiltered_html' ) ) {
		return new WP_Error( 'adzworld_source_forbidden', __( 'Managing executable advertising shortcodes requires administrator and unfiltered HTML permissions.', 'adz-world' ) );
	}
	$nonce = isset( $_POST['adzworld_source_nonce'] ) && is_string( $_POST['adzworld_source_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_source_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'adzworld_rotation_source' ) ) {
		return new WP_Error( 'adzworld_source_nonce', __( 'The source request could not be verified.', 'adz-world' ) );
	}
	$key = isset( $_POST['source_key'] ) && is_string( $_POST['source_key'] )
		? sanitize_key( wp_unslash( $_POST['source_key'] ) ) : '';
	if ( ! preg_match( '/^[a-z][a-z0-9_-]{0,49}$/D', $key ) ) {
		return new WP_Error( 'adzworld_source_key', __( 'The source key must start with a letter and contain only lowercase letters, numbers, underscores or hyphens.', 'adz-world' ) );
	}
	$saved = get_option( 'adzworld_rotation_sources', array() );
	$saved = is_array( $saved ) ? $saved : array();
	if ( isset( $_POST['remove_source'] ) && 'yes' === $_POST['remove_source'] ) {
		unset( $saved[ $key ] );
	} else {
		$code  = isset( $_POST['source_shortcode'] ) && is_string( $_POST['source_shortcode'] ) ? sanitize_textarea_field( wp_unslash( $_POST['source_shortcode'] ) ) : '';
		$label = isset( $_POST['source_label'] ) && is_string( $_POST['source_label'] ) ? sanitize_text_field( wp_unslash( $_POST['source_label'] ) ) : '';
		if ( strlen( $code ) > 2000 || '' === $label || ! preg_match( '/^\[([a-zA-Z][a-zA-Z0-9_-]*)\b[^\]]*\]$/D', $code, $match )
			|| ! shortcode_exists( $match[1] ) || 'adz_rotation' === $match[1] ) {
			return new WP_Error( 'adzworld_source_shortcode', __( 'A label and an existing provider shortcode are required. A rotation cannot include itself.', 'adz-world' ) );
		}
		if ( count( $saved ) >= 50 && ! isset( $saved[ $key ] ) ) {
			return new WP_Error( 'adzworld_source_limit', __( 'Up to fifty advertising sources can be saved.', 'adz-world' ) );
		}
		$saved[ $key ] = array(
			'label'     => $label,
			'shortcode' => $code,
		);
	}
	if ( get_option( 'adzworld_rotation_sources', array() ) !== $saved && ! update_option( 'adzworld_rotation_sources', $saved, false ) ) {
		return new WP_Error( 'adzworld_source_storage', __( 'The advertising source could not be saved.', 'adz-world' ) );
	}
	return true;
}

/**
 * Render the administrator's source registration and shortcode instructions.
 *
 * @return void
 */
function adzworld_rotation_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	echo '<div class="wrap"><h1>' . esc_html__( 'Rotation sources', 'adz-world' ) . '</h1>';
	if ( isset( $_POST['adzworld_source_nonce'] ) ) {
		check_admin_referer( 'adzworld_rotation_source', 'adzworld_source_nonce' );
		$result = adzworld_save_rotation_source();
		echo '<div class="notice ' . ( is_wp_error( $result ) ? 'notice-error' : 'notice-success' ) . '"><p>'
			. esc_html( is_wp_error( $result ) ? $result->get_error_message() : __( 'Source configuration saved.', 'adz-world' ) ) . '</p></div>';
	}
	echo '<p>' . esc_html__( 'Use the shortcode supplied by an advertising plugin already installed on this site. This does not create a provider account or certify provider approval. No source or adz is inserted unless selected in your rotation.', 'adz-world' ) . '</p>';
	echo '<ul>';
	foreach ( adzworld_rotation_sources() as $key => $source ) {
		echo '<li><code>' . esc_html( $key ) . '</code>: ' . esc_html( $source['label'] ) . ' <code>' . esc_html( $source['shortcode'] ) . '</code></li>';
	}
	echo '</ul><form method="post">';
	wp_nonce_field( 'adzworld_rotation_source', 'adzworld_source_nonce' );
	echo '<p><label for="adzworld-source-key">' . esc_html__( 'Source key', 'adz-world' ) . '</label><br><input id="adzworld-source-key" name="source_key" maxlength="50" required></p>'
		. '<p><label for="adzworld-source-label">' . esc_html__( 'Label', 'adz-world' ) . '</label><br><input id="adzworld-source-label" name="source_label" maxlength="200"></p>'
		. '<p><label for="adzworld-source-shortcode">' . esc_html__( 'Existing provider shortcode', 'adz-world' ) . '</label><br><textarea id="adzworld-source-shortcode" name="source_shortcode" maxlength="2000" rows="3" class="large-text"></textarea></p>'
		. '<p><label><input type="checkbox" name="remove_source" value="yes"> ' . esc_html__( 'Remove this source instead of saving it', 'adz-world' ) . '</label></p>';
	submit_button( __( 'Save source configuration', 'adz-world' ) );
	echo '</form><h2>' . esc_html__( 'Examples', 'adz-world' ) . '</h2><p><code>[adz_rotation slots="source:existing:9,ad:123:1" display="single"]</code></p>'
		. '<p><code>[adz_rotation slots="ad:123,ad:456,intro" display="3d" controls="both"]</code></p>'
		. '<p>' . esc_html__( 'Replace example keys and IDs with your own. Single display uses the weights for a random choice per uncached page render, not a guaranteed quota per ten accesses. Page caches also cache that choice. Flat and 3D displays show the configured ordered slides with manual controls; they do not refresh ad-network units.', 'adz-world' ) . '</p></div>';
}

/**
 * Validate the ordered, weighted slot specification without inserting content.
 *
 * @param string $specification Comma-separated ad:ID:weight, source:key:weight or intro:weight entries.
 * @return array|WP_Error Validated slots.
 */
function adzworld_parse_rotation( $specification ) {
	if ( ! is_string( $specification ) || '' === trim( $specification ) || strlen( $specification ) > 4096 ) {
		return new WP_Error( 'adzworld_rotation_empty', __( 'The rotation needs an explicit slot list.', 'adz-world' ) );
	}

	$slots   = array();
	$total   = 0;
	$sources = adzworld_rotation_sources();
	foreach ( explode( ',', $specification ) as $entry ) {
		$parts = explode( ':', trim( $entry ) );
		$type  = array_shift( $parts );
		$value = 'intro' === $type ? '' : array_shift( $parts );
		$raw   = empty( $parts ) ? '1' : array_shift( $parts );
		if ( ! empty( $parts ) || ! is_string( $raw ) || ! preg_match( '/^[1-9][0-9]{0,2}$/D', $raw ) ) {
			return new WP_Error( 'adzworld_rotation_weight', __( 'Each slot weight must be an integer from 1 to 100.', 'adz-world' ) );
		}
		$weight = (int) $raw;
		if ( $weight > 100 || count( $slots ) >= 24 || $total + $weight > 1000 ) {
			return new WP_Error( 'adzworld_rotation_limit', __( 'The rotation exceeds its slot or weight limit.', 'adz-world' ) );
		}
		if ( 'ad' === $type ) {
			if ( ! is_string( $value ) || ! preg_match( '/^[1-9][0-9]*$/D', $value ) || is_wp_error( adzworld_local_ad_response( $value ) ) ) {
				return new WP_Error( 'adzworld_rotation_ad', __( 'A selected ad is not publicly available.', 'adz-world' ) );
			}
		} elseif ( 'source' === $type ) {
			if ( ! is_string( $value ) || ! isset( $sources[ $value ] )
				|| ! preg_match( '/^\[([a-zA-Z][a-zA-Z0-9_-]*)\b[^\]]*\]$/D', $sources[ $value ]['shortcode'], $match )
				|| ! shortcode_exists( $match[1] ) || 'adz_rotation' === $match[1] ) {
				return new WP_Error( 'adzworld_rotation_source', __( 'A selected advertising source is not registered.', 'adz-world' ) );
			}
		} elseif ( 'intro' !== $type ) {
			return new WP_Error( 'adzworld_rotation_type', __( 'The rotation contains an unknown slot type.', 'adz-world' ) );
		}
		$slots[] = array(
			'type'   => $type,
			'value'  => $value,
			'weight' => $weight,
		);
		$total  += $weight;
	}
	return $slots;
}

/**
 * Resolve one random draw against the configured integer weights.
 *
 * @param array $slots Validated rotation slots.
 * @param int   $draw Draw between one and the total weight.
 * @return array|WP_Error Selected slot or an out-of-range error.
 */
function adzworld_rotation_weighted_slot( $slots, $draw ) {
	if ( is_int( $draw ) && $draw > 0 ) {
		foreach ( $slots as $slot ) {
			$draw -= $slot['weight'];
			if ( $draw <= 0 ) {
				return $slot;
			}
		}
	}
	return new WP_Error( 'adzworld_rotation_draw', __( 'The advertising rotation draw is outside its configured range.', 'adz-world' ) );
}

/**
 * Render only administrator-selected content with accessible navigation.
 *
 * Weighted selection applies to a single server-rendered presentation.
 * Carousels contain each configured slot once and do not refresh provider tags.
 *
 * @param array $attributes Shortcode attributes.
 * @return string Rotation markup or an explicit configuration error.
 */
function adzworld_render_rotation( $attributes ) {
	static $rendering = false;
	if ( $rendering ) {
		return '<p role="alert">' . esc_html__( 'Nested advertising rotations are not supported.', 'adz-world' ) . '</p>';
	}
	$attributes = shortcode_atts(
		array(
			'slots'    => '',
			'display'  => 'single',
			'controls' => 'both',
			'label'    => __( 'Advertising', 'adz-world' ),
		),
		$attributes,
		'adz_rotation'
	);
	$slots      = adzworld_parse_rotation( $attributes['slots'] );
	if ( is_wp_error( $slots ) ) {
		return '<p class="adzworld-rotation-error" role="alert">' . esc_html( $slots->get_error_message() ) . '</p>';
	}
	if ( ! in_array( $attributes['display'], array( 'single', 'flat', '3d' ), true )
		|| ! in_array( $attributes['controls'], array( 'arrows', 'dots', 'both', 'none' ), true ) ) {
		return '<p class="adzworld-rotation-error" role="alert">' . esc_html__( 'The rotation display or controls setting is not supported.', 'adz-world' ) . '</p>';
	}
	$display  = $attributes['display'];
	$controls = $attributes['controls'];
	if ( 'single' === $display ) {
		$draw = wp_rand( 1, array_sum( array_column( $slots, 'weight' ) ) );
		$slot = adzworld_rotation_weighted_slot( $slots, $draw );
		if ( is_wp_error( $slot ) ) {
			return '<p class="adzworld-rotation-error" role="alert">' . esc_html( $slot->get_error_message() ) . '</p>';
		}
		$slots = array( $slot );
	}

	wp_enqueue_style( 'adzworld-rotation', plugins_url( 'assets/rotation.css', dirname( __DIR__ ) . '/adz-world.php' ), array(), '1.0.9' );
	wp_enqueue_script( 'adzworld-rotation', plugins_url( 'assets/rotation.js', dirname( __DIR__ ) . '/adz-world.php' ), array(), '1.0.9', true );
	$id        = wp_unique_id( 'adzworld-rotation-' );
	$sources   = adzworld_rotation_sources();
	$slides    = '';
	$dots      = '';
	$rendering = true;
	try {
		foreach ( $slots as $index => $slot ) {
			$slide_id = $id . '-slide-' . $index;
			if ( 'intro' === $slot['type'] ) {
				$html = adzworld_rotation_introduction( $slide_id );
			} elseif ( 'ad' === $slot['type'] ) {
				$ad   = get_post( (int) $slot['value'] );
				$html = '<div class="adzworld-creative">' . wp_kses_post( $ad->post_content ) . '</div>';
			} else {
				$html = '<div class="adzworld-provider" aria-label="' . esc_attr( $sources[ $slot['value'] ]['label'] ) . '">'
					. do_shortcode( $sources[ $slot['value'] ]['shortcode'] ) . '</div>';
			}
			$slides .= '<section class="adzworld-slide" id="' . esc_attr( $slide_id ) . '" role="group" aria-roledescription="'
				. esc_attr__( 'slide', 'adz-world' ) . '" aria-label="' . esc_attr( ( $index + 1 ) . ' / ' . count( $slots ) ) . '">' . $html . '</section>';
			/* translators: %d: slide position. */
			$dot_label = sprintf( __( 'Show advertisement %d', 'adz-world' ), $index + 1 );
			$dots     .= '<button type="button" data-adzworld-position="' . esc_attr( $index ) . '" aria-controls="' . esc_attr( $slide_id ) . '" aria-label="'
				. esc_attr( $dot_label ) . '">' . esc_html( $index + 1 ) . '</button>';
		}
	} finally {
		$rendering = false;
	}
	$navigation = '';
	if ( count( $slots ) > 1 && in_array( $controls, array( 'arrows', 'both' ), true ) ) {
		$navigation .= '<button type="button" data-adzworld-step="-1">' . esc_html__( 'Previous', 'adz-world' ) . '</button>'
			. '<button type="button" data-adzworld-step="1">' . esc_html__( 'Next', 'adz-world' ) . '</button>';
	}
	if ( count( $slots ) > 1 && in_array( $controls, array( 'dots', 'both' ), true ) ) {
		$navigation .= '<div class="adzworld-dots">' . $dots . '</div>';
	}
	return '<div class="adzworld-rotation adzworld-rotation--' . esc_attr( $display ) . '" id="' . esc_attr( $id )
		. '" role="region" aria-label="' . esc_attr( $attributes['label'] ) . '"><div class="adzworld-slides">' . $slides
		. '</div><div class="adzworld-navigation" hidden>' . $navigation . '</div></div>';
}

/**
 * Render an optional, local-only preference introduction.
 *
 * @param string $id Unique element prefix.
 * @return string Introduction form.
 */
function adzworld_rotation_introduction( $id ) {
	return '<form class="adzworld-preference" data-cleared="' . esc_attr__( 'Preferences cleared.', 'adz-world' )
		. '" data-limit="' . esc_attr__( 'This page holds up to ten refinements. Clear them to start again.', 'adz-world' )
		. '" data-noted="' . esc_attr__( 'Preference noted on this page only. No information was sent.', 'adz-world' )
		. '"><h3>' . esc_html__( 'Advertising by invitation', 'adz-world' ) . '</h3>'
		. '<label for="' . esc_attr( $id . '-request' ) . '">'
		. esc_html__( 'You have the option to receive permission-based adz by describing what you would like to purchase in the near future.', 'adz-world' )
		. '</label><textarea id="' . esc_attr( $id . '-request' ) . '" maxlength="1000" required></textarea>'
		. '<p>' . esc_html__( 'Your words stay in this page. This form does not send data, enable tracking, or promise matching offers.', 'adz-world' ) . '</p>'
		. '<button type="submit" disabled>' . esc_html__( 'Add preference', 'adz-world' ) . '</button> '
		. '<button type="button" class="adzworld-clear-preference">' . esc_html__( 'Clear preferences', 'adz-world' ) . '</button>'
		. '<p class="adzworld-preference-status" role="status"></p><ol class="adzworld-preference-history"></ol></form>';
}
