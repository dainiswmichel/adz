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
function adz_ad_post_type_pub() {

	$labels = array(
		'name'                  => _x( 'Adz.world', 'Post Type General Name', 'adz-world' ),
		'singular_name'         => _x( 'Adz.world', 'Post Type Singular Name', 'adz-world' ),
		'menu_name'             => __( 'Adz.world', 'adz-world' ),
		'name_admin_bar'        => __( 'Adz.world', 'adz-world' ),
		'archives'              => __( 'Adz.world Archives', 'adz-world' ),
		'attributes'            => __( 'TV Station Ad Attributes', 'adz-world' ),
		'parent_item_colon'     => __( 'Parent Item:', 'adz-world' ),
		'all_items'             => __( 'All Adz', 'adz-world' ),
		'add_new_item'          => __( 'Add New Ad', 'adz-world' ),
		'add_new'               => __( 'Add New Adz', 'adz-world' ),
		'new_item'              => __( 'New Ad', 'adz-world' ),
		'edit_item'             => __( 'Edit Ad', 'adz-world' ),
		'update_item'           => __( 'Update Ad', 'adz-world' ),
		'view_item'             => __( 'View Ad', 'adz-world' ),
		'view_items'            => __( 'View Ad', 'adz-world' ),
		'search_items'          => __( 'Search Adz.world', 'adz-world' ),
		'not_found'             => __( 'Not found', 'adz-world' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'adz-world' ),
		'featured_image'        => __( 'Featured Image', 'adz-world' ),
		'set_featured_image'    => __( 'Set featured image', 'adz-world' ),
		'remove_featured_image' => __( 'Remove featured image', 'adz-world' ),
		'use_featured_image'    => __( 'Use as featured image', 'adz-world' ),
		'insert_into_item'      => __( 'Insert into item', 'adz-world' ),
		'uploaded_to_this_item' => __( 'Uploaded to this Ad', 'adz-world' ),
		'items_list'            => __( 'Adz.world list', 'adz-world' ),
		'items_list_navigation' => __( 'Adz.world list navigation', 'adz-world' ),
		'filter_items_list'     => __( 'Filter ads list', 'adz-world' ),
	);
	$args   = array(
		'label'               => __( 'Adz.world', 'adz-world' ),
		'description'         => __( 'Adz.world to serve up to network', 'adz-world' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'excrpt', 'editor', 'custom-fields' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
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
add_action( 'init', 'adz_ad_post_type_pub', 0 );




/**
 * Render escaped affiliate metadata for an advertisement.
 *
 * @param WP_Post $post Advertisement being edited.
 */
function ad_network_meta_box_markup_pub( $post ) {

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
 * Register the advertisement's metadata editor.
 */
function add_ad_network_meta_box_pub() {
	add_meta_box( 'ad-network-meta-box', 'Settings for This Ad', 'ad_network_meta_box_markup_pub', 'adz_ad', 'normal', 'high', null );
}

add_action( 'add_meta_boxes', 'add_ad_network_meta_box_pub' );


/**
 * Add the network identifier to the advertisement listing.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function set_network_adz_id_column( $columns ) {
	unset( $columns['author'] );
	$columns['network_ad_id'] = 'Advertise Id';

	return $columns;
}
add_filter( 'manage_adz_ad_posts_columns', 'set_network_adz_id_column' );

/**
 * Render the escaped network identifier.
 *
 * @param string $column Column key.
 * @param int    $post_id Advertisement identifier.
 */
function network_adz_id_column( $column, $post_id ) {
	switch ( $column ) {

		case 'network_ad_id':
			$network_ad_id = get_post_meta( $post_id, 'network_ad_id', true );

				echo esc_html( $network_ad_id );
			break;

	}
}

add_action( 'manage_adz_ad_posts_custom_column', 'network_adz_id_column', 10, 2 );

/**
 * Position the network identifier before the date column.
 *
 * @param array $defaults Existing columns.
 * @return array Ordered columns.
 */
function adz_ad_columns_head( $defaults ) {
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
add_filter( 'manage_posts_columns', 'adz_ad_columns_head' );
/**
 * Register advertisement categories.
 */
function ad_taxonomy_pub() {

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
add_action( 'init', 'ad_taxonomy_pub', 0 );

/* Sync with Server */
/**
 * Synchronize an updated advertisement when the network is enabled.
 *
 * @param int     $post_id Advertisement identifier.
 * @param WP_Post $post_after Updated advertisement.
 */
function adz_sync_network_ads( $post_id, $post_after ) {
	if ( 'adz_ad' === $post_after->post_type ) {
		adz_NetworkAuthorization::update_ad( $post_id, $post_after );
	}
}

add_action( 'post_updated', 'adz_sync_network_ads', 10, 2 );



/**
 * Synchronize deletion of an advertisement when the network is enabled.
 *
 * @param int $post_id Advertisement identifier.
 */
function adz_sync_network_ads_delete( $post_id ) {
	if ( 'adz_ad' !== get_post_type( $post_id ) ) {
			return;
	}
		adz_NetworkAuthorization::delete_ad( $post_id );
}

add_action( 'before_delete_post', 'adz_sync_network_ads_delete' );

/**
 * Render the legacy iframe-insertion button.
 */
function adz_ifram_button() {
	echo '<a title="Insert Adz.world iFrame" class="button insert-media add_media" id="insert-adz-iframe-button" href="#">Adz.world iFrame</a>';
}

add_action( 'media_buttons', 'adz_ifram_button', 11 );

/**
 * Attach the legacy editor's iframe insertion behavior.
 */
function adz_button_js() {
		echo '<script type="text/javascript">
	jQuery(document).ready(function(){
	   jQuery("#insert-adz-iframe-button").click(function() {
	      send_to_editor("[adzworld_iframe adz_url=\"http://adz.world\" height=\"600\" width=\"100%\" ]");
	      return false;
	   });
	});
	</script>';
}
add_action( 'admin_print_footer_scripts', 'adz_button_js', 199 );


/**
 * Register the template settings screen.
 */
function adz_templates_setting_menu() {
	add_submenu_page( 'edit.php?post_type=adz_ad', 'Adz Template Settings', 'Adz Template Settings', 'manage_options', 'adz-template-setting', 'adz_template_setting' );
}
add_action( 'admin_menu', 'adz_templates_setting_menu', 11 );


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
	if ( '' === adz_resolve_template( $template ) ) {
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
function adz_template_setting() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	if ( isset( $_POST['adz_template'] ) ) {
		check_admin_referer( 'adzworld_template_save', 'adzworld_template_nonce' );
		$result = adzworld_save_template_settings();
		echo '<div class="notice ' . ( is_wp_error( $result ) ? 'notice-error' : 'notice-success' ) . '"><p>'
			. esc_html( is_wp_error( $result ) ? $result->get_error_message() : __( 'Template settings saved.', 'adz-world' ) ) . '</p></div>';
	}
	echo '<form method="post" action="">';
	wp_nonce_field( 'adzworld_template_save', 'adzworld_template_nonce' );
	echo ' <h3>Edit your Adz template header and footer text here.</h3>';
	echo '<label>Select Adz Template</label><br>';

	echo "<select name='adz_template' id='adz_template_list'>";
	echo "<option value=''>Select Template</option>";
	$dir       = ADZ_WORLD . 'adz-templates';
	$templates = scandir( $dir, 1 );
	if ( ! empty( $templates ) ) {
		foreach ( $templates as $template ) {
			$ext = pathinfo( $template, PATHINFO_EXTENSION );
			if ( 'php' === $ext ) {
				echo "<option value='" . esc_attr( $template ) . "'>" . esc_html( $template ) . '</option>';
			}
		}
	}

	echo "</select> <span class='loading_data' style='display:none'>Please Wait While its Loading...</span>";
	echo '<br><label>Enter Header Text here.</label><br>';
	echo '<textarea name="header_text" id="header_text"></textarea>';
	echo '<br><label>Select Text Alignment For Header.</label><br>';
	echo '<input type="radio" name="header_style" value="right" id="header_right"> Right  <input type="radio" name="header_style" value="left" id="header_left"> Left  <input type="radio" name="header_style" value="center" id="header_center" checked> Center';
	echo '<br><label>Enter Footer Text here.</label><br>';
	echo '<textarea name="footer_text" id="footer_text"></textarea>';
	echo '<br><label>Select Text Alignment For Footer.</label><br>';
	echo '<input type="radio" name="footer_style" value="right" id="footer_right"> Right  <input type="radio" name="footer_style" value="left" id="footer_left"> Left  <input type="radio" name="footer_style" value="center" id="footer_center" checked> Center';
	submit_button();
	echo '</form>';
}

/**
 * Return a shipped template's settings to an authorized administrator.
 */
function get_template_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Administrator access is required.', 'adz-world' ) ), 403 );
	}
	check_ajax_referer( 'adzworld_template_settings', 'nonce' );
	$template_name = isset( $_POST['selected_template'] ) && is_string( $_POST['selected_template'] )
		? sanitize_file_name( wp_unslash( $_POST['selected_template'] ) ) : '';
	if ( ! in_array( $template_name, adz_available_templates(), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Unknown ad template.', 'adz-world' ) ), 400 );
	}
	$template_data = get_option( 'template_' . $template_name );
	$settings      = is_array( $template_data ) ? $template_data : ( is_string( $template_data ) ? unserialize( $template_data, array( 'allowed_classes' => false ) ) : array() );
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
add_action( 'wp_ajax_get_template_settings', 'get_template_settings' );