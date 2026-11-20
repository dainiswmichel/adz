<?php
/**
 * Advertising post types, metadata, and template administration.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Register publisher advertisements.
 */
function adzworld_ad_post_type_pub() {

	$labels = array(
		'name'                  => ADZWORLD_NAME,
		'singular_name'         => ADZWORLD_NAME,
		'menu_name'             => ADZWORLD_NAME,
		'name_admin_bar'        => ADZWORLD_NAME,
		'archives'              => sprintf(
			/* translators: %s: product name. */
			__( '%s Archives', 'adz-world' ),
			ADZWORLD_NAME
		),
		'attributes'            => __( 'adz attributes', 'adz-world' ),
		'parent_item_colon'     => __( 'Parent Item:', 'adz-world' ),
		'all_items'             => __( 'All adz', 'adz-world' ),
		'add_new_item'          => __( 'Add adz', 'adz-world' ),
		'add_new'               => __( 'Add adz', 'adz-world' ),
		'new_item'              => __( 'New adzad', 'adz-world' ),
		'edit_item'             => __( 'Edit adzad', 'adz-world' ),
		'update_item'           => __( 'Update adzad', 'adz-world' ),
		'view_item'             => __( 'View adzad', 'adz-world' ),
		'view_items'            => __( 'View adz', 'adz-world' ),
		'search_items'          => sprintf(
			/* translators: %s: product name. */
			__( 'Search %s', 'adz-world' ),
			ADZWORLD_NAME
		),
		'not_found'             => __( 'Not found', 'adz-world' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'adz-world' ),
		'featured_image'        => __( 'Featured Image', 'adz-world' ),
		'set_featured_image'    => __( 'Set featured image', 'adz-world' ),
		'remove_featured_image' => __( 'Remove featured image', 'adz-world' ),
		'use_featured_image'    => __( 'Use as featured image', 'adz-world' ),
		'insert_into_item'      => __( 'Insert into item', 'adz-world' ),
		'uploaded_to_this_item' => __( 'Uploaded to this adzad', 'adz-world' ),
		'items_list'            => sprintf(
			/* translators: %s: product name. */
			__( '%s list', 'adz-world' ),
			ADZWORLD_NAME
		),
		'items_list_navigation' => sprintf(
			/* translators: %s: product name. */
			__( '%s list navigation', 'adz-world' ),
			ADZWORLD_NAME
		),
		'filter_items_list'     => __( 'Filter adz list', 'adz-world' ),
	);
	$args   = array(
		'label'               => ADZWORLD_NAME,
		'description'         => sprintf(
			/* translators: %s: product name. */
			__( '%s to serve up to network', 'adz-world' ),
			ADZWORLD_NAME
		),
		'labels'              => $labels,
		'supports'            => array( 'title', 'excerpt', 'editor', 'thumbnail', 'custom-fields' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => 'adzworld',
		'menu_position'       => 5,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
		'show_in_rest'        => false,
		'menu_icon'           => 'dashicons-welcome-view-site',

	);
	register_post_type( 'adz_ad', $args );
}
add_action( 'init', 'adzworld_ad_post_type_pub', 0 );




/**
 * Render escaped affiliate metadata for an advertisement.
 *
 * @param WP_Post $post Advertisement being edited.
 */
function adzworld_network_meta_box_markup_pub( $post ) {

	wp_nonce_field( basename( __FILE__ ), 'meta-box-nonce' );
	$affiliation_network_name = get_post_meta( $post->ID, 'affiliation_network_name', true );
	$affiliation_network_url  = get_post_meta( $post->ID, 'affiliation_network_url', true );
	$affiliation_id           = get_post_meta( $post->ID, 'affiliation_id', true );
	?>
	<table>
		<tr>
			<td>
				<label><b>Affiliation Network Name: </b> </label>
			</td>
			<td>
				<input type="text" name="affiliation_network_name" value="<?php echo esc_attr( $affiliation_network_name ); ?>">
			</td>
		</tr>
		<tr>
			<td>
				<label><b>Affiliation Network URL: </b> </label>
			</td>
			<td>
				<input type="text" name="affiliation_network_url" value="<?php echo esc_attr( $affiliation_network_url ); ?>">
			</td>
		</tr>
		<tr>
			<td>
				<label><b>Affiliation id: </b></label>
			</td>
			<td>
				<input type="text" name="affiliation_id" value="<?php echo esc_attr( $affiliation_id ); ?>">
			</td>

		</tr>

	</table>
	<?php
}

/**
 * Save affiliate metadata only from the authorized advertisement editor.
 *
 * Automated updates keep existing metadata instead of reading nonexistent POST keys.
 *
 * @param int $post_id Advertisement identifier.
 * @return true|WP_Error Whether the submitted editor values were saved.
 */
function adzworld_save_ad_metadata( $post_id ) {
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return new WP_Error( 'adzworld_ad_forbidden', __( 'Permission to edit this advertisement is required.', 'adz-world' ) );
	}
	$nonce = isset( $_POST['meta-box-nonce'] ) && is_string( $_POST['meta-box-nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['meta-box-nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, basename( __FILE__ ) ) ) {
		return new WP_Error( 'adzworld_ad_nonce', __( 'The advertisement metadata request could not be verified.', 'adz-world' ) );
	}
	$values = array(
		'affiliation_network_name' => isset( $_POST['affiliation_network_name'] ) && is_string( $_POST['affiliation_network_name'] )
			? sanitize_text_field( wp_unslash( $_POST['affiliation_network_name'] ) ) : '',
		'affiliation_network_url'  => isset( $_POST['affiliation_network_url'] ) && is_string( $_POST['affiliation_network_url'] )
			? esc_url_raw( wp_unslash( $_POST['affiliation_network_url'] ) ) : '',
		'affiliation_id'           => isset( $_POST['affiliation_id'] ) && is_string( $_POST['affiliation_id'] )
			? sanitize_text_field( wp_unslash( $_POST['affiliation_id'] ) ) : '',
	);
	foreach ( $values as $key => $value ) {
		if ( get_post_meta( $post_id, $key, true ) !== $value && ! update_post_meta( $post_id, $key, wp_slash( $value ) ) ) {
			return new WP_Error( 'adzworld_ad_storage', __( 'Advertisement metadata could not be saved.', 'adz-world' ) );
		}
	}
	return true;
}

/**
 * Persist editor metadata before the advertisement is synchronized remotely.
 *
 * @param int $post_id Advertisement identifier.
 */
function adzworld_handle_ad_metadata( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	$nonce = isset( $_POST['meta-box-nonce'] ) && is_string( $_POST['meta-box-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['meta-box-nonce'] ) ) : '';
	if ( '' === $nonce ) {
		return;
	}
	$result = wp_verify_nonce( $nonce, basename( __FILE__ ) )
		? adzworld_save_ad_metadata( $post_id )
		: new WP_Error( 'adzworld_ad_nonce', __( 'The advertisement metadata request could not be verified.', 'adz-world' ) );
	if ( is_wp_error( $result ) ) {
		adzworld_network_notice( $result );
	}
}
add_action( 'save_post_adz_ad', 'adzworld_handle_ad_metadata', 10 );

/**
 * Preserve an actionable, non-secret service error across the admin redirect.
 *
 * @param WP_Error $error Failure to display.
 */
function adzworld_network_notice( $error ) {
	if ( get_current_user_id() ) {
		set_transient( 'adzworld_network_notice_' . get_current_user_id(), $error->get_error_message(), 300 );
	}
}

/**
 * Display and consume the current administrator's service failure notice.
 */
function adzworld_render_network_notice() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$key     = 'adzworld_network_notice_' . get_current_user_id();
	$message = get_transient( $key );
	if ( is_string( $message ) && '' !== $message ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
		delete_transient( $key );
	}
}
add_action( 'admin_notices', 'adzworld_render_network_notice' );

/**
 * Register the advertisement's metadata editor.
 */
function adzworld_add_network_meta_box_pub() {
	add_meta_box( 'ad-network-meta-box', 'Settings for This Ad', 'adzworld_network_meta_box_markup_pub', 'adz_ad', 'normal', 'high', null );
}

add_action( 'add_meta_boxes', 'adzworld_add_network_meta_box_pub' );


/**
 * Add the network identifier to the advertisement listing.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function adzworld_set_network_adz_id_column( $columns ) {
	unset( $columns['author'] );
	$columns['network_ad_id'] = 'Advertise Id';

	return $columns;
}
add_filter( 'manage_adz_ad_posts_columns', 'adzworld_set_network_adz_id_column' );

/**
 * Render the escaped network identifier.
 *
 * @param string $column Column key.
 * @param int    $post_id Advertisement identifier.
 */
function adzworld_network_adz_id_column( $column, $post_id ) {
	switch ( $column ) {

		case 'network_ad_id':
			$network_ad_id = get_post_meta( $post_id, 'network_ad_id', true );

				echo esc_html( $network_ad_id );
			break;

	}
}

add_action( 'manage_adz_ad_posts_custom_column', 'adzworld_network_adz_id_column', 10, 2 );

/**
 * Position the network identifier before the date column.
 *
 * @param array $defaults Existing columns.
 * @return array Ordered columns.
 */
function adzworld_ad_columns_head( $defaults ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'adz_ad' === $screen->post_type && isset( $defaults['network_ad_id'] ) ) {

		$new           = array();
		$network_ad_id = $defaults['network_ad_id'];
		unset( $defaults['network_ad_id'] );

		foreach ( $defaults as $key => $value ) {

			if ( 'date' === $key ) {
				$new['network_ad_id'] = $network_ad_id;
			}
			$new[ $key ] = $value;
		}
		return $new;
	} else {
		return $defaults;
	}
}
add_filter( 'manage_posts_columns', 'adzworld_ad_columns_head' );
/**
 * Register advertisement categories.
 */
function adzworld_ad_taxonomy_pub() {

	$labels = array(
		'name'                       => _x( 'Ad Types', 'Taxonomy General Name', 'adz-world' ),
		'singular_name'              => _x( 'Ad Type', 'Taxonomy Singular Name', 'adz-world' ),
		'menu_name'                  => __( 'Ad Types', 'adz-world' ),
		'all_items'                  => __( 'All Ad Types', 'adz-world' ),
		'parent_item'                => __( 'Parent Item', 'adz-world' ),
		'parent_item_colon'          => __( 'Parent Item:', 'adz-world' ),
		'new_item_name'              => __( 'New Ad Type', 'adz-world' ),
		'add_new_item'               => __( 'Add New Ad Type', 'adz-world' ),
		'edit_item'                  => __( 'Edit Ad Type', 'adz-world' ),
		'update_item'                => __( 'Update Ad Type', 'adz-world' ),
		'view_item'                  => __( 'View Ad Type', 'adz-world' ),
		'separate_items_with_commas' => __( 'Separate items with commas', 'adz-world' ),
		'add_or_remove_items'        => __( 'Add or remove items', 'adz-world' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'adz-world' ),
		'popular_items'              => __( 'Popular Ad Types', 'adz-world' ),
		'search_items'               => __( 'Search Ad Types', 'adz-world' ),
		'not_found'                  => __( 'Not Found', 'adz-world' ),
		'no_terms'                   => __( 'No Ad Types', 'adz-world' ),
		'items_list'                 => __( 'Ad Type List', 'adz-world' ),
		'items_list_navigation'      => __( 'Ad Type list navigation', 'adz-world' ),
	);
	$args   = array(
		'labels'            => $labels,
		'hierarchical'      => false,
		'public'            => true,
		'show_ui'           => false,
		'show_admin_column' => true,
		'show_in_nav_menus' => false,
		'show_tagcloud'     => false,
		'rewrite'           => false,
		'show_in_rest'      => false,
	);
	register_taxonomy( 'ad_taxonomy', array( 'adz_ad' ), $args );
}
add_action( 'init', 'adzworld_ad_taxonomy_pub', 0 );

/* Sync with Server */
/**
 * Synchronize an updated advertisement when the network is enabled.
 *
 * @param int     $post_id Advertisement identifier.
 * @param WP_Post $post_after Updated advertisement.
 */
function adzworld_sync_network_ads( $post_id, $post_after ) {
	if ( 'adz_ad' === $post_after->post_type ) {
		$result = Adz_NetworkAuthorization::update_ad( $post_id, $post_after );
		if ( is_wp_error( $result ) ) {
			adzworld_network_notice( $result );
		}
	}
}

add_action( 'save_post_adz_ad', 'adzworld_sync_network_ads', 30, 2 );



/**
 * Synchronize deletion of an advertisement when the network is enabled.
 *
 * @param int $post_id Advertisement identifier.
 */
function adzworld_sync_network_ads_delete( $post_id ) {
	if ( 'adz_ad' !== get_post_type( $post_id ) ) {
			return;
	}
	$result = Adz_NetworkAuthorization::delete_ad( $post_id );
	if ( is_wp_error( $result ) ) {
		adzworld_network_notice( $result );
	}
}

add_action( 'before_delete_post', 'adzworld_sync_network_ads_delete' );

/**
 * Render the legacy iframe-insertion button.
 */
function adzworld_iframe_button() {
	if ( ! adzworld_is_plugin_admin_screen() ) {
		return;
	}

	echo '<button type="button" title="' . esc_attr__( 'Insert an advertising iframe', 'adz-world' ) . '" class="button insert-media add_media" id="insert-adz-iframe-button">'
		. esc_html__( 'Advertising iframe', 'adz-world' ) . '</button>';
}

add_action( 'media_buttons', 'adzworld_iframe_button', 11 );


/**
 * Register the template settings screen.
 */
function adzworld_templates_setting_menu() {
	add_submenu_page(
		'edit.php?post_type=adz_ad',
		__( 'adz template settings', 'adz-world' ),
		__( 'adz template settings', 'adz-world' ),
		'manage_options',
		'adz-template-setting',
		'adzworld_template_setting'
	);
}
add_action( 'admin_menu', 'adzworld_templates_setting_menu', 11 );


/**
 * Save this site's information-panel text.
 *
 * @return true|WP_Error Whether the save succeeded.
 */
function adzworld_save_notice_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'adzworld_notice_forbidden', __( 'Administrator permission is required.', 'adz-world' ) );
	}

	$nonce = isset( $_POST['adzworld_notice_nonce'] ) && is_string( $_POST['adzworld_notice_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_notice_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'adzworld_notice_save' ) ) {
		return new WP_Error( 'adzworld_notice_nonce', __( 'The disclosure-text save request could not be verified.', 'adz-world' ) );
	}

	$text = isset( $_POST['adzworld_notice_text'] ) && is_string( $_POST['adzworld_notice_text'] )
		? sanitize_textarea_field( wp_unslash( $_POST['adzworld_notice_text'] ) ) : '';
	if ( '' === $text ) {
		return new WP_Error( 'adzworld_notice_empty', __( 'Disclosure text cannot be empty.', 'adz-world' ) );
	}
	if ( get_option( ADZWORLD_NOTICE_OPTION, null ) !== $text && ! update_option( ADZWORLD_NOTICE_OPTION, $text, false ) ) {
		return new WP_Error( 'adzworld_notice_storage', __( 'The disclosure text could not be saved.', 'adz-world' ) );
	}

	return true;
}


/**
 * Render the site-wide information-panel editor.
 *
 * @return void
 */
function adzworld_notice_setting() {
	$result = null;
	$submit = filter_input( INPUT_POST, 'adzworld_notice_submit', FILTER_UNSAFE_RAW );
	if ( null !== $submit ) {
		$result = adzworld_save_notice_settings();
	}

	if ( true === $result || is_wp_error( $result ) ) {
		$message = is_wp_error( $result ) ? $result->get_error_message() : __( 'Disclosure text saved.', 'adz-world' );
		$class   = is_wp_error( $result ) ? 'notice notice-error' : 'notice notice-success';
		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}

	echo '<section class="adzworld-desk-intro"><h2>' . esc_html__( 'Adz information text', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'This text appears from the information control on every adz panel. Separate paragraphs with a blank line. In a paragraph beginning with ¶, text before the first em dash is emphasized.', 'adz-world' ) . '</p>';
	echo '<form method="post" action="">';
	wp_nonce_field( 'adzworld_notice_save', 'adzworld_notice_nonce' );
	echo '<label class="screen-reader-text" for="adzworld_notice_text">' . esc_html__( 'Adz information text', 'adz-world' ) . '</label>';
	echo '<textarea class="large-text code" rows="18" id="adzworld_notice_text" name="adzworld_notice_text">' . esc_textarea( adzworld_notice_text() ) . '</textarea>';
	echo '<p class="submit">';
	submit_button( __( 'Save disclosure text', 'adz-world' ), 'secondary', 'adzworld_notice_submit', false );
	echo '</p></form></section>';
}


/**
 * Save one shipped template's administrator-controlled text and alignment.
 *
 * @return true|WP_Error Whether the save succeeded.
 */
function adzworld_save_template_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'adzworld_template_forbidden', __( 'Administrator permission is required.', 'adz-world' ) );
	}
	$nonce = isset( $_POST['adzworld_template_nonce'] ) && is_string( $_POST['adzworld_template_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_template_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'adzworld_template_save' ) ) {
		return new WP_Error( 'adzworld_template_nonce', __( 'The template save request could not be verified.', 'adz-world' ) );
	}
	$template = isset( $_POST['adz_template'] ) && is_string( $_POST['adz_template'] )
		? sanitize_text_field( wp_unslash( $_POST['adz_template'] ) ) : '';
	if ( '' === adzworld_resolve_template( $template ) ) {
		return new WP_Error( 'adzworld_template_unknown', __( 'A shipped advertising template must be selected.', 'adz-world' ) );
	}
	$settings = array(
		'header_text'  => isset( $_POST['header_text'] ) && is_string( $_POST['header_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['header_text'] ) ) : '',
		'footer_text'  => isset( $_POST['footer_text'] ) && is_string( $_POST['footer_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['footer_text'] ) ) : '',
		'header_style' => isset( $_POST['header_style'] ) && is_string( $_POST['header_style'] ) ? sanitize_key( wp_unslash( $_POST['header_style'] ) ) : '',
		'footer_style' => isset( $_POST['footer_style'] ) && is_string( $_POST['footer_style'] ) ? sanitize_key( wp_unslash( $_POST['footer_style'] ) ) : '',
	);
	if ( ! in_array( $settings['header_style'], array( 'left', 'right', 'center' ), true )
		|| ! in_array( $settings['footer_style'], array( 'left', 'right', 'center' ), true ) ) {
		return new WP_Error( 'adzworld_template_alignment', __( 'Template alignment must be left, right or center.', 'adz-world' ) );
	}
	if ( get_option( 'template_' . $template ) !== $settings && ! update_option( 'template_' . $template, $settings, false ) ) {
		return new WP_Error( 'adzworld_template_storage', __( 'The template settings could not be saved.', 'adz-world' ) );
	}
	return true;
}

/**
 * Render the advertising template settings form and explicit save result.
 */
function adzworld_template_setting() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	adzworld_notice_setting();
	adzworld_response_settings();
	if ( isset( $_POST['adz_template'] ) ) {
		check_admin_referer( 'adzworld_template_save', 'adzworld_template_nonce' );
		$result = adzworld_save_template_settings();
		echo '<div class="notice ' . ( is_wp_error( $result ) ? 'notice-error' : 'notice-success' ) . '"><p>'
			. esc_html( is_wp_error( $result ) ? $result->get_error_message() : __( 'Countdown advertisement lines saved.', 'adz-world' ) ) . '</p></div>';
	}
	$templates = adzworld_available_templates();
	$selected  = $templates ? $templates[0] : '';
	$draft     = isset( $_POST['adzworld_template_nonce'] ) && is_string( $_POST['adzworld_template_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_template_nonce'] ) ) : '';
	$use_post  = wp_verify_nonce( $draft, 'adzworld_template_save' );
	if ( $use_post && isset( $_POST['adz_template'] ) && is_string( $_POST['adz_template'] ) && in_array( sanitize_text_field( wp_unslash( $_POST['adz_template'] ) ), $templates, true ) ) {
		$selected = sanitize_text_field( wp_unslash( $_POST['adz_template'] ) );
	}
	$stored   = adzworld_template_settings( '' !== $selected ? get_option( 'template_' . $selected ) : array() );
	$settings = wp_parse_args(
		is_array( $stored ) ? $stored : array(),
		array(
			'header_text'  => '',
			'footer_text'  => '',
			'header_style' => 'center',
			'footer_style' => 'center',
		)
	);
	foreach ( array( 'header_style', 'footer_style' ) as $style_key ) {
		$settings[ $style_key ] = in_array( $settings[ $style_key ], array( 'left', 'right', 'center' ), true ) ? $settings[ $style_key ] : 'center';
	}
	$saved = $settings;
	if ( $use_post ) {
		$settings['header_text']  = isset( $_POST['header_text'] ) && is_string( $_POST['header_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['header_text'] ) ) : $settings['header_text'];
		$settings['footer_text']  = isset( $_POST['footer_text'] ) && is_string( $_POST['footer_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['footer_text'] ) ) : $settings['footer_text'];
		$settings['header_style'] = isset( $_POST['header_style'] ) && is_string( $_POST['header_style'] ) ? sanitize_key( wp_unslash( $_POST['header_style'] ) ) : $settings['header_style'];
		$settings['footer_style'] = isset( $_POST['footer_style'] ) && is_string( $_POST['footer_style'] ) ? sanitize_key( wp_unslash( $_POST['footer_style'] ) ) : $settings['footer_style'];
	}
	foreach ( array( 'header_style', 'footer_style' ) as $style_key ) {
		$settings[ $style_key ] = in_array( $settings[ $style_key ], array( 'left', 'right', 'center' ), true ) ? $settings[ $style_key ] : 'center';
	}
	$align_labels = array(
		'left'   => __( 'Left', 'adz-world' ),
		'center' => __( 'Center', 'adz-world' ),
		'right'  => __( 'Right', 'adz-world' ),
	);
	$option_key   = '' !== $selected ? 'template_' . $selected : '';
	$none_header  = __( 'No header words', 'adz-world' );
	$none_footer  = __( 'No footer words', 'adz-world' );
	$screen_rows  = array(
		array(
			'key'   => 'header_text',
			'kind'  => 'text',
			'label' => __( 'Header words', 'adz-world' ),
			'value' => '' !== $saved['header_text'] ? $saved['header_text'] : $none_header,
			'does'  => __( 'Words above the advertisement, beside the countdown.', 'adz-world' ),
			'empty' => $none_header,
		),
		array(
			'key'   => 'header_style',
			'kind'  => 'align',
			'label' => __( 'Header alignment', 'adz-world' ),
			'value' => $align_labels[ $saved['header_style'] ],
			'does'  => __( 'Left, center, or right for the header words and the countdown.', 'adz-world' ),
			'empty' => '',
		),
		array(
			'key'   => 'footer_text',
			'kind'  => 'text',
			'label' => __( 'Footer words', 'adz-world' ),
			'value' => '' !== $saved['footer_text'] ? $saved['footer_text'] : $none_footer,
			'does'  => __( 'Words below the advertisement, beside the countdown.', 'adz-world' ),
			'empty' => $none_footer,
		),
		array(
			'key'   => 'footer_style',
			'kind'  => 'align',
			'label' => __( 'Footer alignment', 'adz-world' ),
			'value' => $align_labels[ $saved['footer_style'] ],
			'does'  => __( 'Left, center, or right for the footer words and the countdown.', 'adz-world' ),
			'empty' => '',
		),
		array(
			'key'   => 'adz_template',
			'kind'  => 'text',
			'label' => __( 'Layout file', 'adz-world' ),
			'value' => $selected,
			'does'  => sprintf(
				/* translators: %s: stored option key, such as template_template1.php. */
				__( 'Stored as %s. This file prints the lines and the countdown.', 'adz-world' ),
				$option_key
			),
			'empty' => '',
		),
	);
	$other_rows   = array(
		array(
			'key'   => 'ad_seconds',
			'label' => __( 'Countdown length', 'adz-world' ),
			'value' => __( 'Old rotation', 'adz-world' ),
			'does'  => __( 'Seconds of adz on an old rotation. The number counts down on the full page or in the popup. At zero the line becomes Continue and the reader can leave.', 'adz-world' ),
		),
		array(
			'key'   => 'adz_duration',
			'label' => __( 'Shortcode countdown', 'adz-world' ),
			'value' => __( '[Adzworld] shortcode', 'adz-world' ),
			'does'  => __( 'The countdown number when that shortcode asks for the advertisement.', 'adz-world' ),
		),
		array(
			'key'   => 'popup_or_page',
			'label' => __( 'Placement', 'adz-world' ),
			'value' => __( 'Old rotation', 'adz-world' ),
			'does'  => __( 'Popup, within the page, or full page. These lines are used for the popup and the full page.', 'adz-world' ),
		),
		array(
			'key'   => 'rotation_countdown',
			'label' => __( 'Rotation countdown', 'adz-world' ),
			'value' => __( 'Rotation tab', 'adz-world' ),
			'does'  => __( 'Seconds on a named rotation. The number counts down on each slot. At zero the slot stays.', 'adz-world' ),
		),
		array(
			'key'   => 'browse_seconds',
			'label' => __( 'Browsing gap', 'adz-world' ),
			'value' => __( 'Old rotation', 'adz-world' ),
			'does'  => __( 'Seconds of browsing before the next countdown advertisement.', 'adz-world' ),
		),
		array(
			'key'   => '',
			'label' => __( 'Countdown unit', 'adz-world' ),
			'value' => __( 'seconds', 'adz-world' ),
			'does'  => __( 'The word after the countdown number. It is fixed text, not a saved setting.', 'adz-world' ),
		),
	);
	$header_empty = '' === $settings['header_text'];
	$footer_empty = '' === $settings['footer_text'];

	echo '<form method="post" action="" class="adzworld-template-lines">';
	wp_nonce_field( 'adzworld_template_save', 'adzworld_template_nonce' );
	echo '<h2>' . esc_html__( 'Countdown advertisement lines', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'These are the words and alignment beside the countdown. A visitor sees them on the full-page countdown advertisement and on the popup countdown advertisement. Display formats on the Rotation tab do not use them. This screen does not set the countdown length.', 'adz-world' ) . '</p>';
	echo '<div class="adzworld-template-lines__panel">';
	echo '<table class="widefat striped adzworld-template-lines__now"><thead><tr><th scope="col">' . esc_html__( 'Setting', 'adz-world' ) . '</th><th scope="col">' . esc_html__( 'Saved now', 'adz-world' ) . '</th><th scope="col">' . esc_html__( 'What it does', 'adz-world' ) . '</th></tr></thead><tbody>';
	echo '<tr class="adzworld-template-lines__group"><th colspan="3">' . esc_html__( 'Set on this screen', 'adz-world' ) . '</th></tr>';
	foreach ( $screen_rows as $now_row ) {
		echo '<tr><th scope="row">' . esc_html( $now_row['label'] );
		if ( '' !== $now_row['key'] ) {
			echo '<code>' . esc_html( $now_row['key'] ) . '</code>';
		}
		echo '</th><td data-adzworld-saved="' . esc_attr( $now_row['key'] ) . '" data-kind="' . esc_attr( $now_row['kind'] ) . '" data-empty-label="' . esc_attr( $now_row['empty'] ) . '">' . esc_html( $now_row['value'] ) . '</td>';
		echo '<td>' . esc_html( $now_row['does'] ) . '</td></tr>';
	}
	echo '<tr class="adzworld-template-lines__group"><th colspan="3">' . esc_html__( 'Shown beside the countdown, set elsewhere', 'adz-world' ) . '</th></tr>';
	foreach ( $other_rows as $now_row ) {
		echo '<tr><th scope="row">' . esc_html( $now_row['label'] );
		if ( '' !== $now_row['key'] ) {
			echo '<code>' . esc_html( $now_row['key'] ) . '</code>';
		}
		echo '</th><td>' . esc_html( $now_row['value'] ) . '</td><td>' . esc_html( $now_row['does'] ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
	echo '<h3>' . esc_html__( 'Preview', 'adz-world' ) . '</h3>';
	echo '<p class="adzworld-template-lines__field-note">' . esc_html__( 'The number 10 is a sample. The countdown length is not saved on this screen.', 'adz-world' ) . '</p>';
	echo '<div class="adzworld-template-lines__preview">';
	echo '<p class="adzworld-template-lines__sample adzworld-align-' . esc_attr( $settings['header_style'] ) . '" data-adzworld-template-header-row>';
	echo '<span class="adzworld-template-lines__words' . ( $header_empty ? ' adzworld-template-lines__empty' : '' ) . '" data-adzworld-template-header data-empty-label="' . esc_attr( $none_header ) . '">' . esc_html( $header_empty ? $none_header : $settings['header_text'] ) . '</span>';
	echo '<span class="adzworld-template-lines__count"><span class="adzworld-template-lines__count-num">10</span> ' . esc_html__( 'seconds', 'adz-world' ) . '</span></p>';
	echo '<p class="adzworld-template-lines__ad">' . esc_html__( 'Advertisement', 'adz-world' ) . '</p>';
	echo '<p class="adzworld-template-lines__sample adzworld-align-' . esc_attr( $settings['footer_style'] ) . '" data-adzworld-template-footer-row>';
	echo '<span class="adzworld-template-lines__words' . ( $footer_empty ? ' adzworld-template-lines__empty' : '' ) . '" data-adzworld-template-footer data-empty-label="' . esc_attr( $none_footer ) . '">' . esc_html( $footer_empty ? $none_footer : $settings['footer_text'] ) . '</span>';
	echo '<span class="adzworld-template-lines__count"><span class="adzworld-template-lines__count-num">10</span> ' . esc_html__( 'seconds', 'adz-world' ) . '</span></p>';
	echo '</div>';
	echo '<h3>' . esc_html__( 'Change the lines', 'adz-world' ) . '</h3>';
	echo '<p><label for="adz_template_list">' . esc_html__( 'Layout file', 'adz-world' ) . '</label><br>';
	echo '<select name="adz_template" id="adz_template_list">';
	foreach ( $templates as $template ) {
		echo '<option value="' . esc_attr( $template ) . '" ' . selected( $selected, $template, false ) . '>' . esc_html( $template ) . '</option>';
	}
	echo '</select> <span class="loading_data adzworld-is-hidden">' . esc_html__( 'Loading the saved lines.', 'adz-world' ) . '</span></p>';
	echo '<p class="adzworld-template-lines__field-note">' . esc_html__( 'Parameter adz_template. The file that prints these lines.', 'adz-world' ) . '</p>';
	echo '<p><label for="header_text">' . esc_html__( 'Header words', 'adz-world' ) . '</label><br>';
	echo '<textarea name="header_text" id="header_text" rows="3">' . esc_textarea( $settings['header_text'] ) . '</textarea></p>';
	echo '<p class="adzworld-template-lines__field-note">' . esc_html__( 'Parameter header_text. Words above the advertisement, beside the countdown.', 'adz-world' ) . '</p>';
	echo '<fieldset class="adzworld-template-lines__align"><legend>' . esc_html__( 'Header alignment', 'adz-world' ) . '</legend>';
	foreach ( $align_labels as $align => $align_label ) {
		echo '<label><input type="radio" name="header_style" value="' . esc_attr( $align ) . '" id="header_' . esc_attr( $align ) . '" ' . checked( $settings['header_style'], $align, false ) . '> ' . esc_html( $align_label ) . '</label>';
	}
	echo '</fieldset>';
	echo '<p class="adzworld-template-lines__field-note">' . esc_html__( 'Parameter header_style. Left, center, or right.', 'adz-world' ) . '</p>';
	echo '<p><label for="footer_text">' . esc_html__( 'Footer words', 'adz-world' ) . '</label><br>';
	echo '<textarea name="footer_text" id="footer_text" rows="3">' . esc_textarea( $settings['footer_text'] ) . '</textarea></p>';
	echo '<p class="adzworld-template-lines__field-note">' . esc_html__( 'Parameter footer_text. Words below the advertisement, beside the countdown.', 'adz-world' ) . '</p>';
	echo '<fieldset class="adzworld-template-lines__align"><legend>' . esc_html__( 'Footer alignment', 'adz-world' ) . '</legend>';
	foreach ( $align_labels as $align => $align_label ) {
		echo '<label><input type="radio" name="footer_style" value="' . esc_attr( $align ) . '" id="footer_' . esc_attr( $align ) . '" ' . checked( $settings['footer_style'], $align, false ) . '> ' . esc_html( $align_label ) . '</label>';
	}
	echo '</fieldset>';
	echo '<p class="adzworld-template-lines__field-note">' . esc_html__( 'Parameter footer_style. Left, center, or right.', 'adz-world' ) . '</p>';
	submit_button( __( 'Save countdown lines', 'adz-world' ) );
	echo '</form>';
}

/**
 * Return a shipped template's settings to an authorized administrator.
 */
function adzworld_get_template_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Administrator access is required.', 'adz-world' ) ), 403 );
	}
	check_ajax_referer( 'adzworld_template_settings', 'nonce' );
	$template_name = isset( $_POST['selected_template'] ) && is_string( $_POST['selected_template'] )
		? sanitize_file_name( wp_unslash( $_POST['selected_template'] ) ) : '';
	if ( ! in_array( $template_name, adzworld_available_templates(), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Unknown ad template.', 'adz-world' ) ), 400 );
	}
	$template_data = get_option( 'template_' . $template_name );
	$settings      = adzworld_template_settings( $template_data );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	wp_send_json(
		wp_parse_args(
			$settings,
			array(
				'header_text'  => '',
				'footer_text'  => '',
				'header_style' => 'center',
				'footer_style' => 'center',
			)
		)
	);
}
add_action( 'wp_ajax_get_template_settings', 'adzworld_get_template_settings' );
