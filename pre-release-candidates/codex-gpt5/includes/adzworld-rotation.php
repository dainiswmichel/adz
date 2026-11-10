<?php
/**
 * Explicitly configured advertising rotations.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'adz_rotation', 'adzworld_render_rotation' );
add_action( 'admin_menu', 'adzworld_rotation_admin_menu' );

if ( ! defined( 'ADZWORLD_ROTATION_MAX_SLOTS' ) ) {
	define( 'ADZWORLD_ROTATION_MAX_SLOTS', 200 );
}

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
	$networks   = function_exists( 'adzworld_network_rotation_sources' ) ? adzworld_network_rotation_sources() : array();
	return array_merge( $saved, $registered, $networks );
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
 * Sources an administrator may place in a named rotation.
 *
 * @return array<string,string>
 */
function adzworld_rotation_slot_choices() {
	$choices = array(
		'intro' => __( 'Adz invitation and user hub', 'adz-world' ),
	);
	$ads     = get_posts(
		array(
			'fields'           => 'ids',
			'numberposts'      => -1,
			'order'            => 'ASC',
			'orderby'          => 'title',
			'post_status'      => 'publish',
			'post_type'        => 'adz_ad',
			'suppress_filters' => false,
		)
	);
	foreach ( $ads as $ad_id ) {
		$choices[ 'ad:' . $ad_id ] = sprintf(
			/* translators: 1: advertisement id, 2: advertisement title. */
			__( 'Local adz #%1$d — %2$s', 'adz-world' ),
			$ad_id,
			get_the_title( $ad_id )
		);
	}
	foreach ( adzworld_rotation_sources() as $key => $source ) {
		$choices[ 'source:' . $key ] = sprintf(
			/* translators: %s: advertising source label. */
			__( 'Provider — %s', 'adz-world' ),
			$source['label']
		);
	}
	return $choices;
}

/**
 * Validate one stored rotation slot against currently available content.
 *
 * @param mixed                     $slot    Candidate slot reference.
 * @param array<string,string>|null $choices Available choices, when already loaded.
 * @return string Valid reference, or an empty string.
 */
function adzworld_sanitize_rotation_slot( $slot, $choices = null ) {
	$slot    = is_scalar( $slot ) ? sanitize_text_field( (string) $slot ) : '';
	$choices = is_array( $choices ) ? $choices : adzworld_rotation_slot_choices();
	return isset( $choices[ $slot ] ) ? $slot : '';
}

/**
 * Sanitize all named rotation definitions.
 *
 * @param mixed $raw Stored rotations.
 * @return array<string,array<string,mixed>>
 */
function adzworld_sanitize_named_rotations( $raw ) {
	$clean   = array();
	$choices = adzworld_rotation_slot_choices();
	if ( ! is_array( $raw ) ) {
		return $clean;
	}
	foreach ( array_slice( $raw, 0, 50, true ) as $key => $rotation ) {
		$key = sanitize_key( $key );
		if ( '' === $key || ! is_array( $rotation ) ) {
			continue;
		}
		$name        = isset( $rotation['name'] ) ? sanitize_text_field( $rotation['name'] ) : '';
		$slots       = isset( $rotation['slots'] ) && is_array( $rotation['slots'] ) ? $rotation['slots'] : array();
		$slots       = array_slice( $slots, 0, ADZWORLD_ROTATION_MAX_SLOTS );
		$clean_slots = array();
		foreach ( $slots as $slot ) {
			$slot = adzworld_sanitize_rotation_slot( $slot, $choices );
			if ( '' !== $slot ) {
				$clean_slots[] = $slot;
			}
		}
		if ( '' !== $name && ! empty( $clean_slots ) ) {
			$clean[ $key ] = array(
				'name'  => $name,
				'slots' => $clean_slots,
			);
		}
	}
	return $clean;
}

/**
 * Read named rotations.
 *
 * @return array<string,array<string,mixed>>
 */
function adzworld_named_rotations() {
	return adzworld_sanitize_named_rotations( get_option( 'adzworld_named_rotations', array() ) );
}

/**
 * Starter rotation shown until an administrator saves the first rotation.
 *
 * @return array<string,mixed>
 */
function adzworld_starter_rotation() {
	$slots = array_keys( adzworld_rotation_slot_choices() );
	return array(
		'name'  => __( 'Main rotation', 'adz-world' ),
		'slots' => empty( $slots ) ? array( 'intro' ) : $slots,
	);
}

/**
 * Return the slot list for a named rotation.
 *
 * @param string $name Rotation key.
 * @return array<int,string>
 */
function adzworld_named_rotation_slots( $name ) {
	$name      = sanitize_key( $name );
	$rotations = adzworld_named_rotations();
	return isset( $rotations[ $name ] ) ? $rotations[ $name ]['slots'] : array();
}

/**
 * Save one named rotation.
 *
 * @return void
 */
function adzworld_save_named_rotation() {
	check_admin_referer( 'adzworld_save_named_rotation', 'adzworld_rotation_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	$key      = isset( $_POST['rotation_key'] ) && is_string( $_POST['rotation_key'] ) ? sanitize_key( wp_unslash( $_POST['rotation_key'] ) ) : '';
	$original = isset( $_POST['original_rotation_key'] ) && is_string( $_POST['original_rotation_key'] ) ? sanitize_key( wp_unslash( $_POST['original_rotation_key'] ) ) : '';
	$name     = isset( $_POST['rotation_name'] ) && is_string( $_POST['rotation_name'] ) ? sanitize_text_field( wp_unslash( $_POST['rotation_name'] ) ) : '';
	$slots    = isset( $_POST['rotation_slots'] ) && is_array( $_POST['rotation_slots'] ) ? map_deep( wp_unslash( $_POST['rotation_slots'] ), 'sanitize_text_field' ) : array();
	$stored   = adzworld_named_rotations();
	$error    = '';
	$delete   = isset( $_POST['delete_rotation'] ) && is_string( $_POST['delete_rotation'] ) ? sanitize_text_field( wp_unslash( $_POST['delete_rotation'] ) ) : '';

	if ( '1' === $delete && '' !== $original ) {
		unset( $stored[ $original ] );
		update_option( 'adzworld_named_rotations', $stored, false );
		set_transient( 'adzworld_rotation_notice_' . get_current_user_id(), __( 'Rotation deleted.', 'adz-world' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( adzworld_desk_url( 'rotation' ) );
		exit;
	}
	if ( '' === $key || ! preg_match( '/^[a-z][a-z0-9_-]{0,49}$/D', $key ) ) {
		$error = __( 'The rotation key must begin with a letter and use only lowercase letters, numbers, underscores, or hyphens.', 'adz-world' );
	} elseif ( '' === $name ) {
		$error = __( 'A rotation name is required.', 'adz-world' );
	} elseif ( count( $slots ) > ADZWORLD_ROTATION_MAX_SLOTS ) {
		$error = sprintf(
			/* translators: %d: maximum number of slots. */
			__( 'A rotation can contain up to %d slots.', 'adz-world' ),
			ADZWORLD_ROTATION_MAX_SLOTS
		);
	} else {
		$choices     = adzworld_rotation_slot_choices();
		$clean_slots = array();
		foreach ( $slots as $slot ) {
			$slot = adzworld_sanitize_rotation_slot( $slot, $choices );
			if ( '' !== $slot ) {
				$clean_slots[] = $slot;
			}
		}
		if ( empty( $clean_slots ) || count( $clean_slots ) !== count( $slots ) ) {
			$error = __( 'Every rotation slot must refer to an available invitation, local adz, or provider source.', 'adz-world' );
		}
	}
	if ( '' !== $error ) {
		set_transient( 'adzworld_rotation_error_' . get_current_user_id(), $error, MINUTE_IN_SECONDS );
		wp_safe_redirect( adzworld_desk_url( 'rotation' ) );
		exit;
	}
	if ( '' !== $original && $original !== $key ) {
		unset( $stored[ $original ] );
	}
	$stored[ $key ] = array(
		'name'  => $name,
		'slots' => $clean_slots,
	);
	update_option( 'adzworld_named_rotations', $stored, false );
	set_transient( 'adzworld_rotation_notice_' . get_current_user_id(), __( 'Rotation saved.', 'adz-world' ), MINUTE_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'rotation', $key, adzworld_desk_url( 'rotation' ) ) );
	exit;
}
add_action( 'admin_post_adzworld_save_named_rotation', 'adzworld_save_named_rotation' );

/**
 * Render the live content for one administrator rotation slot.
 *
 * @param string $slot Valid slot reference.
 * @return string
 */
function adzworld_admin_rotation_slot_preview( $slot ) {
	$slot = adzworld_sanitize_rotation_slot( $slot );
	if ( '' === $slot ) {
		return '<p role="alert">' . esc_html__( 'This slot is no longer available.', 'adz-world' ) . '</p>';
	}
	if ( 'intro' === $slot ) {
		ob_start();
		echo '<div class="adz-panel"><h3 class="adz-panel__title">' . esc_html__( 'Advertising by invitation', 'adz-world' ) . '</h3>';
		adzworld_render_intro( 'full' );
		echo '</div>';
		$content = (string) ob_get_clean();
	} elseif ( 0 === strpos( $slot, 'ad:' ) ) {
		$content = adzworld_local_ad_slide( get_post( (int) substr( $slot, 3 ) ) );
	} else {
		$key     = substr( $slot, 7 );
		$sources = adzworld_rotation_sources();
		$content = '<div class="adzworld-provider">' . do_shortcode( $sources[ $key ]['shortcode'] ) . '</div>'
			. '<p class="adzworld-match-label">' . esc_html( $sources[ $key ]['label'] ) . '</p>';
	}
	return '<div class="adzworld-rotation adzworld-admin-slot-preview">' . $content . '</div>';
}

/**
 * Refresh one administrator preview without changing the saved rotation.
 *
 * @return void
 */
function adzworld_refresh_admin_rotation_slot() {
	check_ajax_referer( 'adzworld_refresh_rotation_slot', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Administrator permission is required.', 'adz-world' ) ), 403 );
	}
	$slot = isset( $_POST['slot'] ) && is_string( $_POST['slot'] ) ? sanitize_text_field( wp_unslash( $_POST['slot'] ) ) : '';
	$slot = adzworld_sanitize_rotation_slot( $slot );
	if ( '' === $slot ) {
		wp_send_json_error( array( 'message' => __( 'That rotation slot is not available.', 'adz-world' ) ), 400 );
	}
	wp_send_json_success( array( 'html' => adzworld_admin_rotation_slot_preview( $slot ) ) );
}
add_action( 'wp_ajax_adzworld_refresh_rotation_slot', 'adzworld_refresh_admin_rotation_slot' );

/**
 * Render one editable vertical rotation slot.
 *
 * @param int                  $index   Zero-based position.
 * @param string               $slot    Slot reference.
 * @param array<string,string> $choices Available choices.
 * @param string               $form_id Save form id.
 * @return void
 */
function adzworld_admin_rotation_slot_row( $index, $slot, $choices, $form_id ) {
	echo '<article class="adzworld-admin-slot" data-adzworld-admin-slot><header><strong data-adzworld-slot-number>';
	printf(
		/* translators: %d: rotation slot number. */
		esc_html__( 'Slot %d', 'adz-world' ),
		absint( $index + 1 )
	);
	echo '</strong><div class="adzworld-admin-slot__actions"><button type="button" class="button" data-adzworld-slot-up aria-label="' . esc_attr__( 'Move slot up', 'adz-world' ) . '">&#8593;</button>';
	echo '<button type="button" class="button" data-adzworld-slot-down aria-label="' . esc_attr__( 'Move slot down', 'adz-world' ) . '">&#8595;</button>';
	echo '<button type="button" class="button" data-adzworld-slot-remove>' . esc_html__( 'Remove', 'adz-world' ) . '</button></div></header>';
	echo '<label>' . esc_html__( 'Slot content', 'adz-world' ) . '<select name="rotation_slots[]" form="' . esc_attr( $form_id ) . '" data-adzworld-slot-select>';
	foreach ( $choices as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '"' . selected( $slot, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></label><div class="adzworld-admin-slot__preview" data-adzworld-slot-preview><div data-adzworld-slot-preview-body>';
	echo wp_kses_post( adzworld_admin_rotation_slot_preview( $slot ) );
	echo '</div><button type="button" class="button" data-adzworld-refresh-slot>' . esc_html__( 'Refresh this adz preview', 'adz-world' ) . '</button><span class="spinner" data-adzworld-slot-spinner></span></div></article>';
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
	$rotations = adzworld_named_rotations();
	$requested = filter_input( INPUT_GET, 'rotation', FILTER_UNSAFE_RAW );
	$requested = is_string( $requested ) ? sanitize_key( $requested ) : '';
	if ( 'new' === $requested ) {
		$key      = '';
		$rotation = array(
			'name'  => __( 'New rotation', 'adz-world' ),
			'slots' => array( 'intro' ),
		);
	} elseif ( isset( $rotations[ $requested ] ) ) {
		$key      = $requested;
		$rotation = $rotations[ $requested ];
	} elseif ( ! empty( $rotations ) ) {
		$key      = array_key_first( $rotations );
		$rotation = $rotations[ $key ];
	} else {
		$key      = 'main';
		$rotation = adzworld_starter_rotation();
	}
	$form_id = 'adzworld-rotation-editor';
	$choices = adzworld_rotation_slot_choices();
	$notice  = 'adzworld_rotation_notice_' . get_current_user_id();
	$error   = 'adzworld_rotation_error_' . get_current_user_id();

	echo '<div class="wrap adzworld-rotation-admin"><h2>' . esc_html__( 'Rotations', 'adz-world' ) . '</h2>';
	if ( get_transient( $notice ) ) {
		echo '<div class="notice notice-success"><p>' . esc_html( get_transient( $notice ) ) . '</p></div>';
		delete_transient( $notice );
	}
	if ( get_transient( $error ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( get_transient( $error ) ) . '</p></div>';
		delete_transient( $error );
	}
	echo '<p>' . esc_html__( 'Each named rotation is an ordered vertical list of actual adz slots. Local adz, the invitation hub, and enabled provider units render below. Provider delivery still depends on that provider approving and returning an ad for this administrator page.', 'adz-world' ) . '</p>';
	echo '<nav class="adzworld-rotation-picker" aria-label="' . esc_attr__( 'Named rotations', 'adz-world' ) . '">';
	foreach ( $rotations as $rotation_key => $saved_rotation ) {
		echo '<a class="button' . ( $rotation_key === $key ? ' button-primary' : '' ) . '" href="' . esc_url( add_query_arg( 'rotation', $rotation_key, adzworld_desk_url( 'rotation' ) ) ) . '">' . esc_html( $saved_rotation['name'] ) . '</a> ';
	}
	echo '<a class="button" href="' . esc_url( add_query_arg( 'rotation', 'new', adzworld_desk_url( 'rotation' ) ) ) . '">' . esc_html__( 'Add rotation', 'adz-world' ) . '</a></nav>';
	echo '<form id="' . esc_attr( $form_id ) . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="adzworld_save_named_rotation"><input type="hidden" name="original_rotation_key" value="' . esc_attr( $key ) . '">';
	wp_nonce_field( 'adzworld_save_named_rotation', 'adzworld_rotation_nonce' );
	echo '</form><section class="adzworld-rotation-meta"><p><label for="adzworld-rotation-name">' . esc_html__( 'Rotation name', 'adz-world' ) . '</label><br><input id="adzworld-rotation-name" name="rotation_name" form="' . esc_attr( $form_id ) . '" value="' . esc_attr( $rotation['name'] ) . '" maxlength="100" required></p>';
	echo '<p><label for="adzworld-rotation-key">' . esc_html__( 'Rotation key', 'adz-world' ) . '</label><br><input id="adzworld-rotation-key" name="rotation_key" form="' . esc_attr( $form_id ) . '" value="' . esc_attr( $key ) . '" maxlength="50" pattern="[a-z][a-z0-9_-]{0,49}" required></p>';
	if ( '' !== $key && isset( $rotations[ $key ] ) ) {
		echo '<p class="adzworld-rotation-shortcode"><span>' . esc_html__( 'Widget shortcode', 'adz-world' ) . '</span><br><code>[adz_rotation name=&quot;' . esc_html( $key ) . '&quot; display=&quot;flat&quot; controls=&quot;both&quot;]</code></p>';
	}
	echo '</section><div class="adzworld-admin-slots" data-adzworld-admin-slots>';
	foreach ( $rotation['slots'] as $index => $slot ) {
		adzworld_admin_rotation_slot_row( $index, $slot, $choices, $form_id );
	}
	echo '</div><template id="adzworld-slot-template">';
	adzworld_admin_rotation_slot_row( 0, array_key_first( $choices ), $choices, $form_id );
	echo '</template><p class="adzworld-rotation-controls"><button type="button" class="button" data-adzworld-add-slot>' . esc_html__( 'Add slot', 'adz-world' ) . '</button> ';
	echo '<button type="submit" class="button button-primary" form="' . esc_attr( $form_id ) . '">' . esc_html__( 'Save rotation', 'adz-world' ) . '</button></p>';
	if ( '' !== $key && isset( $rotations[ $key ] ) ) {
		echo '<p><label><input type="checkbox" name="delete_rotation" value="1" form="' . esc_attr( $form_id ) . '"> ' . esc_html__( 'Delete this rotation when saving', 'adz-world' ) . '</label></p>';
	}
	echo '<p>' . sprintf(
		/* translators: %d: maximum number of slots. */
		esc_html__( 'Rotations support up to %d ordered slots.', 'adz-world' ),
		absint( ADZWORLD_ROTATION_MAX_SLOTS )
	) . '</p>';
	adzworld_admin_format_gallery( $key );
	echo '</div>';
}

/**
 * The display format reference on the Rotation screen.
 *
 * Every format and every fixed size the shortcode accepts, grouped, each with
 * the exact shortcode to copy. It is generated from the two allowlists, so a
 * format cannot appear here that the shortcode would reject, and a format
 * cannot be added without appearing here.
 *
 * @param string $key Rotation key to write into the sample shortcodes, if any.
 * @return void
 */
function adzworld_admin_format_gallery( $key = '' ) {
	$formats = adzworld_rotation_display_formats();
	$sizes   = adzworld_rotation_size_presets();
	// Built as plain text and escaped at the echo, so the escaping is visible
	// at the point of output rather than carried in a pre-built string.
	$name = '' === $key ? '' : ' name="' . $key . '"';

	$grouped = array();
	foreach ( $formats as $format => $meta ) {
		$grouped[ $meta['group'] ][ $format ] = $meta;
	}

	echo '<section class="adzworld-format-gallery">';
	echo '<h2>' . esc_html__( 'Display formats', 'adz-world' ) . '</h2>';
	echo '<p class="adzworld-format-intro">' . esc_html__( 'Each format changes only the frame. The slots, their order, the preference matching and the escaping are the same in all of them, so a format can be changed at any time without touching a rotation.', 'adz-world' ) . '</p>';

	foreach ( $grouped as $group => $members ) {
		echo '<h3 class="adzworld-format-group">' . esc_html( $group ) . '</h3>';
		echo '<ul class="adzworld-format-list">';
		foreach ( $members as $format => $meta ) {
			echo '<li class="adzworld-format">';
			echo '<span class="adzworld-format-swatch adzworld-format-swatch--' . esc_attr( $format ) . '" aria-hidden="true"></span>';
			echo '<span class="adzworld-format-body">';
			echo '<strong class="adzworld-format-name">' . esc_html( $meta['label'] ) . '</strong>';
			echo '<span class="adzworld-format-note">' . esc_html( $meta['note'] ) . '</span>';
			echo '<code class="adzworld-format-code">' . esc_html( '[adz_rotation' . $name . ' display="' . $format . '"]' ) . '</code>';
			echo '</span></li>';
		}
		echo '</ul>';
	}

	echo '<h3 class="adzworld-format-group">' . esc_html__( 'Fixed sizes', 'adz-world' ) . '</h3>';
	echo '<p class="adzworld-format-intro">' . esc_html__( 'Add a size to hold the frame to a standard creative size. Each one caps its width at the space available, so a wide size in a narrow column shrinks instead of overflowing. Leave the size out for a fluid frame, which is what responsive provider units expect.', 'adz-world' ) . '</p>';
	echo '<table class="adzworld-format-sizes"><thead><tr>';
	echo '<th scope="col">' . esc_html__( 'Size', 'adz-world' ) . '</th>';
	echo '<th scope="col">' . esc_html__( 'Name', 'adz-world' ) . '</th>';
	echo '<th scope="col">' . esc_html__( 'Shortcode', 'adz-world' ) . '</th>';
	echo '</tr></thead><tbody>';
	foreach ( $sizes as $size => $meta ) {
		echo '<tr>';
		echo '<td><code>' . esc_html( $size ) . '</code></td>';
		echo '<td>' . esc_html( $meta['label'] ) . '</td>';
		echo '<td><code>' . esc_html( '[adz_rotation' . $name . ' size="' . $size . '"]' ) . '</code></td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '</section>';
}

/**
 * Validate the ordered, weighted slot specification without inserting content.
 *
 * @param string $specification Comma-separated ad:ID:weight, source:key:weight or intro:weight entries.
 * @return array|WP_Error Validated slots.
 */
function adzworld_parse_rotation( $specification ) {
	if ( ! is_string( $specification ) || '' === trim( $specification ) || strlen( $specification ) > 20000 ) {
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
		if ( $weight > 100 || count( $slots ) >= ADZWORLD_ROTATION_MAX_SLOTS || $total + $weight > 20000 ) {
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

	/*
	 * The invitation is always the first carousel position. The configured
	 * order of every advertising slot remains stable after it.
	 */
	$invitations = array();
	$advertising = array();
	foreach ( $slots as $slot ) {
		if ( 'intro' === $slot['type'] ) {
			$invitations[] = $slot;
		} else {
			$advertising[] = $slot;
		}
	}
	$slots = array_merge( $invitations, $advertising );
	return $slots;
}

/**
 * Load the common rotation presentation assets.
 *
 * @return void
 */
function adzworld_enqueue_rotation_assets() {
	$style_path  = dirname( __DIR__ ) . '/assets/rotation.css';
	$script_path = dirname( __DIR__ ) . '/assets/rotation.js';
	wp_enqueue_style(
		'adzworld-rotation',
		plugins_url( 'assets/rotation.css', dirname( __DIR__ ) . '/adz-world.php' ),
		array(),
		(string) filemtime( $style_path )
	);
	wp_enqueue_script(
		'adzworld-rotation',
		plugins_url( 'assets/rotation.js', dirname( __DIR__ ) . '/adz-world.php' ),
		array(),
		(string) filemtime( $script_path ),
		true
	);
	wp_localize_script(
		'adzworld-rotation',
		'adzworldRotation',
		array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'taboolaBase'     => 'https://cdn.taboola.com/libtrc/',
			'preferenceNonce' => wp_create_nonce( 'adzworld_preference' ),
			'feedbackNonce'   => wp_create_nonce( 'adzworld_feedback' ),
			'labels'          => array(
				'strongDislike' => __( 'Strongly dislike', 'adz-world' ),
				'dislike'       => __( 'Dislike', 'adz-world' ),
				'neutral'       => __( 'Neutral', 'adz-world' ),
				'like'          => __( 'Like', 'adz-world' ),
				'strongLike'    => __( 'Strongly like', 'adz-world' ),
			),
		)
	);
}

/**
 * Load the vertical rotation editor only on its administrator tab.
 *
 * @return void
 */
function adzworld_rotation_admin_assets() {
	if ( ! function_exists( 'adzworld_desk_tab' ) || 'rotation' !== adzworld_desk_tab() ) {
		return;
	}
	adzworld_enqueue_rotation_assets();
	$path = dirname( __DIR__ ) . '/assets/admin-rotation.js';
	wp_enqueue_script(
		'adzworld-admin-rotation',
		plugins_url( 'assets/admin-rotation.js', dirname( __DIR__ ) . '/adz-world.php' ),
		array( 'adzworld-rotation' ),
		(string) filemtime( $path ),
		true
	);
	wp_localize_script(
		'adzworld-admin-rotation',
		'adzworldAdminRotation',
		array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'adzworld_refresh_rotation_slot' ),
			'maxSlots' => ADZWORLD_ROTATION_MAX_SLOTS,
			'labels'   => array(
				'loading' => __( 'Refreshing…', 'adz-world' ),
				'refresh' => __( 'Refresh this adz preview', 'adz-world' ),
				'failed'  => __( 'The preview could not be refreshed.', 'adz-world' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'adzworld_rotation_admin_assets', 20 );

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
 * Render one local adz as creative followed by one caption.
 *
 * Text-only adz are the caption. Media adz retain their creative and use the
 * post title as the caption. Long text can be expanded without duplication.
 *
 * @param WP_Post $ad Adz post.
 * @return string Adz slide content.
 */
function adzworld_local_ad_slide( $ad ) {
	$content   = wp_kses_post( $ad->post_content );
	$plain     = trim( wp_strip_all_tags( $ad->post_content ) );
	$has_media = (bool) preg_match( '/<(?:img|picture|video|audio|iframe|svg)\b/i', $ad->post_content );

	if ( $has_media ) {
		return '<div class="adzworld-creative">' . $content . '</div>'
			. '<p class="adzworld-caption">' . esc_html( $ad->post_title ) . '</p>';
	}

	if ( mb_strlen( $plain ) <= 180 ) {
		return '<p class="adzworld-caption">' . esc_html( $plain ) . '</p>';
	}

	return '<details class="adzworld-caption adzworld-caption--expandable"><summary>'
		. esc_html( mb_substr( $plain, 0, 177 ) . '…' ) . '</summary><div class="adzworld-caption__full">'
		. $content . '</div></details>';
}

/**
 * Duplicate configured local adz into the number of slots requested per category.
 *
 * A duplicate is an intentional placeholder until category search supplies a
 * distinct result for that slot. The category and slot index remain stable so
 * the interface can return to the exact result the visitor selected.
 *
 * @param array $slots Configured rotation slots.
 * @param array $pref  Current preference hierarchy.
 * @return array Expanded rotation slots.
 */
function adzworld_expand_category_slots( $slots, $pref ) {
	$expanded = array();

	foreach ( $slots as $slot ) {
		if ( 'ad' !== $slot['type'] ) {
			$expanded[] = $slot;
			continue;
		}

		$category = adzworld_ad_category( get_post( (int) $slot['value'] ), $pref );
		$count    = in_array( $category, $pref['categories'], true )
			? adzworld_category_slot_count( $pref, $category )
			: 1;

		for ( $index = 0; $index < $count; ++$index ) {
			$copy                        = $slot;
			$copy['category']            = $category;
			$copy['category_slot']       = $index;
			$copy['category_slot_count'] = $count;
			$expanded[]                  = $copy;
		}
	}

	return $expanded;
}

/**
 * Render the compact category and sub-ad selector inside one category slide.
 *
 * @param string $category     Category represented by the slide.
 * @param int    $count        Number of category slots.
 * @param int    $current_slot Current zero-based slot.
 * @return string Category slot controls.
 */
function adzworld_category_slot_control( $category, $count, $current_slot ) {
	if ( '' === $category ) {
		return '';
	}

	$count        = max( 1, min( ADZWORLD_CATEGORY_MAX_SLOTS, (int) $count ) );
	$current_slot = max( 0, min( $count - 1, (int) $current_slot ) );
	$label        = sprintf(
		/* translators: %s: category name. */
		__( '%s adz slots', 'adz-world' ),
		$category
	);

	$markup = '<div class="adzworld-category-slots"><div class="adzworld-category-slots__heading">'
		. '<button type="button" class="adzworld-category-slots__category" data-adzworld-category-first="' . esc_attr( $category ) . '">'
		. esc_html( $category ) . '</button>'
		. '<button type="button" class="adzworld-category-slots__add" data-adzworld-add-category-slot="' . esc_attr( $category ) . '" aria-label="'
		. esc_attr(
			sprintf(
				/* translators: %s: category name. */
				__( 'Add another %s adz slot', 'adz-world' ),
				$category
			)
		) . '"><span aria-hidden="true">+</span></button></div>'
		. '<div class="adzworld-category-slots__positions" aria-label="' . esc_attr( $label ) . '">';

	for ( $index = 0; $index < $count; ++$index ) {
		$markup .= '<button type="button" data-adzworld-category-slot-category="' . esc_attr( $category ) . '" data-adzworld-category-slot="'
			. esc_attr( (string) $index ) . '" aria-pressed="' . ( $index === $current_slot ? 'true' : 'false' ) . '" aria-label="'
			. esc_attr(
				sprintf(
					/* translators: 1: category name, 2: slot number. */
					__( 'Show %1$s adz %2$d', 'adz-world' ),
					$category,
					$index + 1
				)
			) . '"><span class="screen-reader-text">' . esc_html( (string) ( $index + 1 ) ) . '</span></button>';
	}

	return $markup . '</div><span class="adzworld-category-slots__status" data-adzworld-category-slot-status role="status" aria-live="polite"></span></div>';
}

/**
 * Display formats a rotation may be rendered in.
 *
 * Every format is presentation only: the markup, the slot ordering, the
 * preference matching and the escaping are identical in all of them. A format
 * is a modifier class on the container, so adding one changes a stylesheet and
 * this allowlist and touches no security surface.
 *
 * Keys are emitted as `adzworld-rotation--<key>` and must stay restricted to
 * lowercase letters, digits and hyphens.
 *
 * @return array<string,array{label:string,group:string,note:string}>
 */
function adzworld_rotation_display_formats() {
	return array(
		'single'      => array(
			'label' => __( 'Single', 'adz-world' ),
			'group' => __( 'Carousel', 'adz-world' ),
			'note'  => __( 'One slot at a time, the rest hidden. The safest default.', 'adz-world' ),
		),
		'flat'        => array(
			'label' => __( 'Flat carousel', 'adz-world' ),
			'group' => __( 'Carousel', 'adz-world' ),
			'note'  => __( 'Slides move sideways in a straight line.', 'adz-world' ),
		),
		'3d'          => array(
			'label' => __( '3D carousel', 'adz-world' ),
			'group' => __( 'Carousel', 'adz-world' ),
			'note'  => __( 'The active slot faces the reader; neighbours angle away.', 'adz-world' ),
		),
		'coverflow'   => array(
			'label' => __( 'Coverflow', 'adz-world' ),
			'group' => __( 'Carousel', 'adz-world' ),
			'note'  => __( 'Neighbours stay visible at the edges, scaled back.', 'adz-world' ),
		),
		'fade'        => array(
			'label' => __( 'Crossfade', 'adz-world' ),
			'group' => __( 'Carousel', 'adz-world' ),
			'note'  => __( 'Slots occupy one frame and fade between each other.', 'adz-world' ),
		),
		'stack'       => array(
			'label' => __( 'Card stack', 'adz-world' ),
			'group' => __( 'Carousel', 'adz-world' ),
			'note'  => __( 'Layered cards; the depth shows how many remain.', 'adz-world' ),
		),
		'filmstrip'   => array(
			'label' => __( 'Filmstrip', 'adz-world' ),
			'group' => __( 'Multiple', 'adz-world' ),
			'note'  => __( 'A scrollable strip that snaps to each slot.', 'adz-world' ),
		),
		'grid'        => array(
			'label' => __( 'Grid', 'adz-world' ),
			'group' => __( 'Multiple', 'adz-world' ),
			'note'  => __( 'All slots at once, reflowing by available width.', 'adz-world' ),
		),
		'banner'      => array(
			'label' => __( 'Banner', 'adz-world' ),
			'group' => __( 'Banner', 'adz-world' ),
			'note'  => __( 'One wide horizontal strip, for above or below content.', 'adz-world' ),
		),
		'billboard'   => array(
			'label' => __( 'Billboard', 'adz-world' ),
			'group' => __( 'Banner', 'adz-world' ),
			'note'  => __( 'A taller full-width banner for the top of a page.', 'adz-world' ),
		),
		'inline-card' => array(
			'label' => __( 'In-article card', 'adz-world' ),
			'group' => __( 'Widget', 'adz-world' ),
			'note'  => __( 'A bordered card sized to the text column it sits in.', 'adz-world' ),
		),
		'sidebar'     => array(
			'label' => __( 'Sidebar widget', 'adz-world' ),
			'group' => __( 'Widget', 'adz-world' ),
			'note'  => __( 'A narrow card for a sidebar or widget area.', 'adz-world' ),
		),
		'rail-left'   => array(
			'label' => __( 'Left rail', 'adz-world' ),
			'group' => __( 'Vertical', 'adz-world' ),
			'note'  => __( 'A vertical bar that sticks to the left of the viewport.', 'adz-world' ),
		),
		'rail-right'  => array(
			'label' => __( 'Right rail', 'adz-world' ),
			'group' => __( 'Vertical', 'adz-world' ),
			'note'  => __( 'A vertical bar that sticks to the right of the viewport.', 'adz-world' ),
		),
		'skyscraper'  => array(
			'label' => __( 'Skyscraper', 'adz-world' ),
			'group' => __( 'Vertical', 'adz-world' ),
			'note'  => __( 'A tall in-flow column, for a wide sidebar.', 'adz-world' ),
		),
		'dock-bottom' => array(
			'label' => __( 'Bottom dock', 'adz-world' ),
			'group' => __( 'Anchored', 'adz-world' ),
			'note'  => __( 'A dismissible bar anchored to the bottom of the viewport.', 'adz-world' ),
		),
		'dock-top'    => array(
			'label' => __( 'Top dock', 'adz-world' ),
			'group' => __( 'Anchored', 'adz-world' ),
			'note'  => __( 'A dismissible bar anchored to the top of the viewport.', 'adz-world' ),
		),
		'corner'      => array(
			'label' => __( 'Corner card', 'adz-world' ),
			'group' => __( 'Anchored', 'adz-world' ),
			'note'  => __( 'A dismissible card that settles in the bottom corner.', 'adz-world' ),
		),
	);
}

/**
 * Standard advertising sizes a rotation frame can be fixed to.
 *
 * These are the common IAB and Google Ad Manager fixed sizes publishers are
 * given creative for. The dimensions live in the stylesheet, one class per
 * size, because a size written into a style attribute would be an inline
 * style; this allowlist only names them.
 *
 * An empty size means the frame is fluid and takes the width it is given,
 * which is what responsive network units expect.
 *
 * @return array<string,array{label:string,width:int,height:int}>
 */
function adzworld_rotation_size_presets() {
	return array(
		'300x250' => array(
			'label'  => __( 'Medium rectangle', 'adz-world' ),
			'width'  => 300,
			'height' => 250,
		),
		'336x280' => array(
			'label'  => __( 'Large rectangle', 'adz-world' ),
			'width'  => 336,
			'height' => 280,
		),
		'250x250' => array(
			'label'  => __( 'Square', 'adz-world' ),
			'width'  => 250,
			'height' => 250,
		),
		'200x200' => array(
			'label'  => __( 'Small square', 'adz-world' ),
			'width'  => 200,
			'height' => 200,
		),
		'180x150' => array(
			'label'  => __( 'Small rectangle', 'adz-world' ),
			'width'  => 180,
			'height' => 150,
		),
		'728x90'  => array(
			'label'  => __( 'Leaderboard', 'adz-world' ),
			'width'  => 728,
			'height' => 90,
		),
		'970x90'  => array(
			'label'  => __( 'Super leaderboard', 'adz-world' ),
			'width'  => 970,
			'height' => 90,
		),
		'970x250' => array(
			'label'  => __( 'Billboard', 'adz-world' ),
			'width'  => 970,
			'height' => 250,
		),
		'468x60'  => array(
			'label'  => __( 'Full banner', 'adz-world' ),
			'width'  => 468,
			'height' => 60,
		),
		'234x60'  => array(
			'label'  => __( 'Half banner', 'adz-world' ),
			'width'  => 234,
			'height' => 60,
		),
		'300x600' => array(
			'label'  => __( 'Half page', 'adz-world' ),
			'width'  => 300,
			'height' => 600,
		),
		'160x600' => array(
			'label'  => __( 'Wide skyscraper', 'adz-world' ),
			'width'  => 160,
			'height' => 600,
		),
		'120x600' => array(
			'label'  => __( 'Skyscraper', 'adz-world' ),
			'width'  => 120,
			'height' => 600,
		),
		'320x50'  => array(
			'label'  => __( 'Mobile banner', 'adz-world' ),
			'width'  => 320,
			'height' => 50,
		),
		'320x100' => array(
			'label'  => __( 'Large mobile banner', 'adz-world' ),
			'width'  => 320,
			'height' => 100,
		),
	);
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
			'name'     => '',
			'slots'    => '',
			'display'  => 'single',
			'controls' => 'both',
			'size'     => '',
			'label'    => __( 'Advertising', 'adz-world' ),
		),
		$attributes,
		'adz_rotation'
	);
	if ( '' !== $attributes['name'] ) {
		$named_slots = adzworld_named_rotation_slots( $attributes['name'] );
		if ( empty( $named_slots ) ) {
			return '<p class="adzworld-rotation-error" role="alert">' . esc_html__( 'The named rotation is not available.', 'adz-world' ) . '</p>';
		}
		$attributes['slots'] = implode( ',', $named_slots );
	}
	$slots = adzworld_parse_rotation( $attributes['slots'] );
	if ( is_wp_error( $slots ) ) {
		return '<p class="adzworld-rotation-error" role="alert">' . esc_html( $slots->get_error_message() ) . '</p>';
	}
	$formats = adzworld_rotation_display_formats();
	$sizes   = adzworld_rotation_size_presets();
	if ( ! isset( $formats[ $attributes['display'] ] )
		|| ! in_array( $attributes['controls'], array( 'arrows', 'dots', 'both', 'none' ), true )
		|| ( '' !== $attributes['size'] && ! isset( $sizes[ $attributes['size'] ] ) ) ) {
		return '<p class="adzworld-rotation-error" role="alert">' . esc_html__( 'The rotation display, size or controls setting is not supported.', 'adz-world' ) . '</p>';
	}
	$display  = $attributes['display'];
	$controls = $attributes['controls'];
	$size     = $attributes['size'];
	$pref     = adzworld_preference_with_catalog( adzworld_current_preference() );
	if ( adzworld_has_preference( $pref ) ) {
		foreach ( $slots as $index => $candidate ) {
			$slots[ $index ]['order'] = $index;
			$slots[ $index ]['score'] = 'ad' === $candidate['type']
				? adzworld_score_ad( (int) $candidate['value'], $pref )
				: 0;
		}
		usort(
			$slots,
			static function ( $first, $second ) {
				if ( 'intro' === $first['type'] || 'intro' === $second['type'] ) {
					return 'intro' === $first['type'] ? -1 : 1;
				}
				if ( $first['score'] === $second['score'] ) {
					return $first['order'] <=> $second['order'];
				}

				return $second['score'] <=> $first['score'];
			}
		);
	}
	if ( 'single' === $display ) {
		$draw = wp_rand( 1, array_sum( array_column( $slots, 'weight' ) ) );
		$slot = adzworld_rotation_weighted_slot( $slots, $draw );
		if ( is_wp_error( $slot ) ) {
			return '<p class="adzworld-rotation-error" role="alert">' . esc_html( $slot->get_error_message() ) . '</p>';
		}
		$slots = array( $slot );
	} else {
		$slots = adzworld_expand_category_slots( $slots, $pref );
	}

	adzworld_enqueue_rotation_assets();
	$id        = wp_unique_id( 'adzworld-rotation-' );
	$sources   = adzworld_rotation_sources();
	$slides    = '';
	$dots      = '';
	$adz_index = 0;
	$rendering = true;
	try {
		foreach ( $slots as $index => $slot ) {
			$slide_id      = $id . '-slide-' . $index;
			$category      = isset( $slot['category'] ) ? (string) $slot['category'] : '';
			$category_slot = isset( $slot['category_slot'] ) ? (int) $slot['category_slot'] : 0;
			$slot_count    = isset( $slot['category_slot_count'] ) ? (int) $slot['category_slot_count'] : 1;
			if ( 'intro' === $slot['type'] ) {
				$html = adzworld_rotation_introduction( $slide_id );
			} elseif ( 'ad' === $slot['type'] ) {
				$ad = get_post( (int) $slot['value'] );
				if ( '' === $category ) {
					$category = adzworld_ad_category( $ad, $pref );
				}
				$html = adzworld_local_ad_slide( $ad )
					. adzworld_feedback_control( 'ad-' . $ad->ID, $category );
				if ( in_array( $category, $pref['categories'], true ) ) {
					$html .= adzworld_category_slot_control( $category, $slot_count, $category_slot );
					if ( 0 === $category_slot ) {
						$html .= adzworld_affiliate_markup( $pref, $category );
					}
				}
			} else {
				$category = isset( $pref['active_category'] ) ? (string) $pref['active_category'] : '';
				$html     = '<div class="adzworld-provider" aria-label="' . esc_attr( $sources[ $slot['value'] ]['label'] ) . '">'
					. do_shortcode( $sources[ $slot['value'] ]['shortcode'] ) . '</div>'
					. '<p class="adzworld-match-label">' . esc_html( $sources[ $slot['value'] ]['label'] ) . '</p>'
					. adzworld_feedback_control( 'source-' . $slot['value'], $category );
			}
			$category_attribute = '' !== $category ? ' data-adzworld-category="' . esc_attr( $category ) . '"' : '';
			$slot_attribute     = '' !== $category ? ' data-adzworld-category-slot="' . esc_attr( (string) $category_slot ) . '"' : '';
			$slides            .= '<section class="adzworld-slide" id="' . esc_attr( $slide_id ) . '"' . $category_attribute . $slot_attribute . ' role="group" aria-roledescription="'
				. esc_attr__( 'slide', 'adz-world' ) . '" aria-label="' . esc_attr( ( $index + 1 ) . ' / ' . count( $slots ) ) . '">' . $html . '</section>';
			if ( 'intro' === $slot['type'] ) {
				$dot_class = ' class="adzworld-hub-position"';
				$dot_label = __( 'Show your adz hub', 'adz-world' );
			} else {
				++$adz_index;
				$dot_class = '';
				/* translators: %d: adz position, excluding the user hub. */
				$dot_label = sprintf( __( 'Show adz %d', 'adz-world' ), $adz_index );
			}
			$dots .= '<button type="button"' . $dot_class . ' data-adzworld-position="' . esc_attr( $index ) . '" aria-controls="' . esc_attr( $slide_id ) . '" aria-label="'
				. esc_attr( $dot_label ) . '"><span class="screen-reader-text">' . esc_html( $dot_label ) . '</span></button>';
		}
	} finally {
		$rendering = false;
	}
	$navigation = '';
	if ( count( $slots ) > 1 && in_array( $controls, array( 'arrows', 'both' ), true ) ) {
		$navigation .= '<button class="adzworld-previous" type="button" data-adzworld-step="-1"><span aria-hidden="true">&#8249;</span><span class="screen-reader-text">'
			. esc_html__( 'Previous', 'adz-world' ) . '</span></button>'
			. '<button class="adzworld-next" type="button" data-adzworld-step="1"><span aria-hidden="true">&#8250;</span><span class="screen-reader-text">'
			. esc_html__( 'Next', 'adz-world' ) . '</span></button>';
	}
	if ( count( $slots ) > 1 && in_array( $controls, array( 'dots', 'both' ), true ) ) {
		$navigation .= '<div class="adzworld-dots">' . $dots . '</div>';
	}
	$classes = 'adzworld-rotation adzworld-rotation--' . $display;
	if ( '' !== $size ) {
		$classes .= ' adzworld-rotation--size-' . $size;
	}

	/*
	 * Formats that place every slot on screen at once. The script reads this to
	 * keep sibling slots visible instead of hiding them; every other format
	 * keeps the one-at-a-time behaviour unchanged.
	 */
	$multiple = in_array( $display, array( 'grid', 'filmstrip', 'coverflow', 'stack' ), true ) ? ' data-adzworld-multiple="1"' : '';

	/*
	 * The anchored formats cover part of the viewport, so each one carries a
	 * dismiss control. Without it a reader has no way to clear the frame, which
	 * is the usual complaint about docked and corner advertising.
	 */
	$dismiss = '';
	if ( in_array( $display, array( 'dock-bottom', 'dock-top', 'corner' ), true ) ) {
		$dismiss = '<button class="adzworld-dismiss" type="button" data-adzworld-dismiss="1">'
			. '<span aria-hidden="true">&#215;</span><span class="screen-reader-text">'
			. esc_html__( 'Dismiss this advertising', 'adz-world' ) . '</span></button>';
	}

	return '<div class="' . esc_attr( $classes ) . '" id="' . esc_attr( $id )
		. '"' . $multiple . ' role="region" aria-label="' . esc_attr( $attributes['label'] ) . '"><div class="adzworld-carousel"><div class="adzworld-slides">' . $slides
		. '</div><div class="adzworld-navigation" hidden>' . $navigation . '</div>' . $dismiss . '</div></div>';
}

/**
 * Render an optional, local-only preference introduction.
 *
 * @param string $id Unique element prefix.
 * @return string Introduction form.
 */
function adzworld_rotation_introduction( $id ) {
	unset( $id );
	$preference = adzworld_preference_with_catalog( adzworld_current_preference() );
	ob_start();
	adzworld_render_preference_panel( $preference, adzworld_has_preference( $preference ), 'full' );
	return (string) ob_get_clean();
}
