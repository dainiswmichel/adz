<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
// Register Custom Post Type
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


function add_ad_network_meta_box_pub() {
	add_meta_box( 'ad-network-meta-box', 'Settings for This Ad', 'ad_network_meta_box_markup_pub', 'adz_ad', 'normal', 'high', null );
}

add_action( 'add_meta_boxes', 'add_ad_network_meta_box_pub' );


function set_network_adz_id_column( $columns ) {
	unset( $columns['author'] );
	$columns['network_ad_id'] = 'Advertise Id';

	return $columns;
}
add_filter( 'manage_adz_ad_posts_columns', 'set_network_adz_id_column' );

function network_adz_id_column( $column, $post_id ) {
	switch ( $column ) {

		case 'network_ad_id':
			$network_ad_id = get_post_meta( $post_id, 'network_ad_id', true );

			echo esc_html( $network_ad_id );
			break;

	}
}

add_action( 'manage_adz_ad_posts_custom_column', 'network_adz_id_column', 10, 2 );

function adz_ad_columns_head( $defaults ) {
	/*
	 * This filter runs on the list table, so the current screen already knows
	 * which post type is being listed. Reading it from WordPress rather than
	 * from the query string means there is no request data here to validate.
	 */
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( $screen instanceof WP_Screen && 'adz_ad' === $screen->post_type ) {

		$new           = array();
		$network_ad_id = $defaults['network_ad_id'];  // save the tags column
		unset( $defaults['network_ad_id'] );   // remove it from the columns list

		foreach ( $defaults as $key => $value ) {

			if ( $key == 'date' ) {  // when we find the date column
				$new['network_ad_id'] = $network_ad_id;  // put the tags column before it
			}
			$new[ $key ] = $value;
		}
		return $new;
	} else {
		return $defaults;
	}
}
add_filter( 'manage_posts_columns', 'adz_ad_columns_head' );
// Register Custom Taxonomy
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
function adz_sync_network_ads( $post_id, $post_after, $post_before ) {
	if ( $post_after->post_type == 'adz_ad' ) {
		adz_NetworkAuthorization::update_ad( $post_id, $post_after );
	}
}

add_action( 'post_updated', 'adz_sync_network_ads', 10, 3 );



function adz_sync_network_ads_delete( $post_id ) {

	// We check if the global post type isn't ours and just return
	global $post_type;
	if ( $post_type != 'adz_ad' ) {
			return;
	}
		adz_NetworkAuthorization::delete_ad( $post_id );
	// My custom stuff for deleting my custom post type here
}

add_action( 'before_delete_post', 'adz_sync_network_ads_delete' );

function adz_ifram_button() {
	echo '<a title="Insert Adz.world iFrame" class="button insert-media add_media" id="insert-adz-iframe-button" href="#">Adz.world iFrame</a>';
}

add_action( 'media_buttons', 'adz_ifram_button', 11 );

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


// function to add new submenu for Adz template setting.
function adz_templates_setting_menu() {
	add_submenu_page( 'edit.php?post_type=adz_ad', 'Adz Template Settings', 'Adz Template Settings', 'manage_options', 'adz-template-setting', 'adz_template_setting' );
}
add_action( 'admin_menu', 'adz_templates_setting_menu', 11 );


// Function to add Adz template setting form in backend.
function adz_template_setting() {
	/*
	 * Being on an admin page is not authorisation. 1.0.8 wrote these values
	 * with no capability check and no nonce, used the submitted template name
	 * as the option name, stored four unsanitised fields that are later echoed
	 * into every visitor's page, and called serialize() explicitly -- which is
	 * what forced the matching unserialize() in the template.
	 */
	if ( isset( $_POST['adz_template'] ) && '' !== $_POST['adz_template'] ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to change template settings.', 'adz-world' ), 403 );
		}

		check_admin_referer( 'adz_template_settings' );

		$template = adz_resolve_template(
			isset( $_POST['adz_template'] ) ? sanitize_text_field( wp_unslash( $_POST['adz_template'] ) ) : ''
		);

		if ( '' !== $template ) {
			$template_settings = array(
				'header_text'  => isset( $_POST['header_text'] ) ? wp_kses_post( wp_unslash( $_POST['header_text'] ) ) : '',
				'header_style' => adz_sanitize_alignment( isset( $_POST['header_style'] ) ? sanitize_text_field( wp_unslash( $_POST['header_style'] ) ) : '' ),
				'footer_text'  => isset( $_POST['footer_text'] ) ? wp_kses_post( wp_unslash( $_POST['footer_text'] ) ) : '',
				'footer_style' => adz_sanitize_alignment( isset( $_POST['footer_style'] ) ? sanitize_text_field( wp_unslash( $_POST['footer_style'] ) ) : '' ),
			);

			// Stored as an array. update_option() serializes it; we do not.
			update_option( 'template_' . basename( $template ), $template_settings, true );
		}
	}
	echo '<form method="post" action="">';
	wp_nonce_field( 'adz_template_settings' );
	echo ' <h3>Edit your Adz template header and footer text here.</h3>';
	echo '<label>Select Adz Template</label><br>';

	echo "<select name='adz_template' id='adz_template_list'>";
	echo "<option value=''>Select Template</option>";
	$dir       = ADZ_WORLD . 'adz-templates';
	$templates = scandir( $dir, 1 );
	if ( ! empty( $templates ) ) {
		foreach ( $templates as $template ) {
			$ext = pathinfo( $template, PATHINFO_EXTENSION );
			if ( $ext == 'php' ) {
				printf(
					'<option value="%1$s">%2$s</option>',
					esc_attr( $template ),
					esc_html( $template )
				);
			}
		}
	}

	echo "</select> <span class='loading_data' style='display:none'>Please Wait While its Loading...</span>";
	echo '<br><label>Enter Header Text here.</label><br>';
	echo '<textarea name="header_text" id="header_text"></textarea>';
	echo '<br><label>Select Text Alignment For Header.</label><br>';
	echo '<input type="radio" name="header_style" value="right" id="header_right"> Right  <input type="radio" name="header_style" value="left" id="header_left"> Left  <input type="radio" name="header_style" value="center" id="header_center"> Center';
	echo '<br><label>Enter Footer Text here.</label><br>';
	echo '<textarea name="footer_text" id="footer_text"></textarea>';
	echo '<br><label>Select Text Alignment For Footer.</label><br>';
	echo '<input type="radio" name="footer_style" value="right" id="footer_right"> Right  <input type="radio" name="footer_style" value="left" id="footer_left"> Left  <input type="radio" name="footer_style" value="center" id="footer_center"> Center';
	submit_button();
	echo '</form>';
}

/**
 * Return a template's stored header/footer settings to the settings screen.
 *
 * 1.0.8 registered this for unauthenticated AJAX, took the option name from the
 * request, ran unserialize() on whatever came back, and used an unprefixed
 * function name likely to collide with another plugin. All four are repaired:
 * administrators only, nonce verified, template matched against the shipped
 * allowlist, and no unserialize() anywhere in the path.
 *
 * @return void
 */
function adzworld_ajax_get_template_settings() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Not permitted.', 'adz-world' ) ), 403 );
	}

	check_ajax_referer( 'adz_admin', 'nonce' );

	$template = isset( $_POST['selected_template'] ) ? sanitize_text_field( wp_unslash( $_POST['selected_template'] ) ) : '';

	if ( '' === adz_resolve_template( $template ) ) {
		wp_send_json_error( array( 'message' => __( 'Unknown template.', 'adz-world' ) ), 400 );
	}

	wp_send_json_success( adz_get_template_settings_array( $template ) );
}
add_action( 'wp_ajax_get_template_settings', 'adzworld_ajax_get_template_settings' );
add_action( 'save_post_adz_ad', 'adzworld_save_ad_meta', 10, 2 );
/**
 * Save the affiliation fields from the ad meta box.
 *
 * The nonce field at the top of this file has existed since 1.0.8 and was never
 * verified anywhere. Meanwhile the three values were written from
 * adz_NetworkAuthorization::update_ad() on the post_updated hook, with no
 * capability check, no nonce, and no isset() guard -- so any update path that
 * does not carry those fields (Quick Edit, bulk edit, a scheduled publish via
 * wp-cron, REST, WP-CLI) silently overwrote all three with empty strings.
 *
 * Saving now happens here, once, and only writes a field the form actually
 * submitted.
 *
 * @param int     $post_id Post being saved.
 * @param WP_Post $post    Post object.
 * @return void
 */
function adzworld_save_ad_meta( $post_id, $post ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['meta-box-nonce'] ) ) {
		// Not our form: Quick Edit, cron, REST, WP-CLI. Leave the values alone.
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meta-box-nonce'] ) ), basename( __FILE__ ) ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['affiliation_network_name'] ) ) {
		update_post_meta(
			$post_id,
			'affiliation_network_name',
			sanitize_text_field( wp_unslash( $_POST['affiliation_network_name'] ) )
		);
	}

	if ( isset( $_POST['affiliation_network_url'] ) ) {
		update_post_meta(
			$post_id,
			'affiliation_network_url',
			esc_url_raw( wp_unslash( $_POST['affiliation_network_url'] ) )
		);
	}

	if ( isset( $_POST['affiliation_id'] ) ) {
		update_post_meta(
			$post_id,
			'affiliation_id',
			sanitize_text_field( wp_unslash( $_POST['affiliation_id'] ) )
		);
	}
}
