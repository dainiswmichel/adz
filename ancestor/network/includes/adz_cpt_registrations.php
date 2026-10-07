<?php
// Register Custom Post Type
function adz_registration() {

	$labels = array(
		'name'                  => _x( 'Publishers', 'Post Type General Name', 'text_domain' ),
		'singular_name'         => _x( 'Publisher', 'Post Type Singular Name', 'text_domain' ),
		'menu_name'             => __( 'Publishers', 'text_domain' ),
		'name_admin_bar'        => __( 'Publisher', 'text_domain' ),
		'archives'              => __( 'Publisher Archives', 'text_domain' ),
		'attributes'            => __( 'Publisher Attributes', 'text_domain' ),
		'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
		'all_items'             => __( 'All Publisher', 'text_domain' ),
		'add_new_item'          => __( 'Add New Publisher', 'text_domain' ),
		'add_new'               => __( 'Add New', 'text_domain' ),
		'new_item'              => __( 'New Item', 'text_domain' ),
		'edit_item'             => __( 'Edit Item', 'text_domain' ),
		'update_item'           => __( 'Update Publisher', 'text_domain' ),
		'view_item'             => __( 'View Publisher', 'text_domain' ),
		'view_items'            => __( 'View Publishers', 'text_domain' ),
		'search_items'          => __( 'Search Item', 'text_domain' ),
		'not_found'             => __( 'Not found', 'text_domain' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
		'featured_image'        => __( 'Featured Image', 'text_domain' ),
		'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
		'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
		'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
		'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
		'items_list'            => __( 'Publishers list', 'text_domain' ),
		'items_list_navigation' => __( 'Publihers list navigation', 'text_domain' ),
		'filter_items_list'     => __( 'FilterPublihers list', 'text_domain' ),
	);
	$args = array(
		'label'                 => __( 'Publisher', 'text_domain' ),
		'description'           => __( 'Publisher who serves ads', 'text_domain' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'custom-fields', ),
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => 'edit.php?post_type=adz_ad',
		'menu_position'         => 5,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
		'rest_controller_class' => 'WP_REST_Registration_Controller',
	);
	register_post_type( 'adz_publisher', $args );

}
add_action( 'init', 'adz_registration', 0 );

function add_registration($site_title,$url,$email,$network_ids,$publisher_id,$adzdotworld_email,$publisher_user_id) {
	$post = array();

	$post['post_title'] = $site_title;
	$post['post_content'] = $url;
	$post['post_type'] = 'adz_publisher';
	$post['post_status'] = 'publish';
	$post_data = get_post($publisher_id);
	
	if(empty($post_data)){

		$user_details = get_user_by('email',$adzdotworld_email);

		if(!$user_details){

			$post_id = wp_insert_post($post);
			$user_id = username_exists( $post_id );
			if ( !$user_id && email_exists($adzdotworld_email) == false ) {
				$random_password = wp_generate_password( 12,true );
				$user_id = wp_create_user( $post_id, $random_password, $adzdotworld_email );
				wp_new_user_notification( $user_id,'user' );
				update_post_meta( $post_id, 'publisher_user_id', $user_id );
			}
		}else{
			$post_id = wp_insert_post($post);
			update_post_meta( $post_id, 'publisher_user_id', $user_details->ID );
		}

		/*$user_id = username_exists( $adzdotworld_email );
		if ( !$user_id && email_exists($adzdotworld_email) == false ) {

			$random_password = wp_generate_password( 12,true );
			$user_id = wp_create_user( $adzdotworld_email, $random_password, $adzdotworld_email );
			wp_new_user_notification( $user_id,'user' );	
			$post_id = wp_insert_post($post);	
			update_post_meta( $post_id, 'publisher_user_id', $user_id );	
			
		} else {
			$post_id = wp_insert_post($post);
			update_post_meta( $post_id, 'publisher_user_id', $user_id );	
			//$random_password = __('User already exists.  Password inherited.');
		}	*/

	}else{
		
		$post_id = $post_data->ID;	
		
		global $wpdb;
		$wpdb->update($wpdb->prefix.'users',array('user_email' => $adzdotworld_email),array('ID' => $publisher_user_id));
		/*wp_update_user( array(
	        'ID' => $publisher_user_id,
	        'user_email' => $adzdotworld_email
		) );*/
	}
	
	$options = get_option( 'adz_adnet_options' );
	$pub_share = $options['publisher_share'];
	if ($post_id) {
		 update_post_meta( $post_id, 'admin_email', $adzdotworld_email);
		 if(isset($network_ids['preferences']) && is_array($network_ids['preferences'])){
			 $categories = array_map( 'intval', $network_ids['preferences'] );
			 wp_set_object_terms( $post_id, $categories, 'ad_taxonomy' );
			 unset($network_ids['preferences']);
		}
		 //update_post_meta( $post_id, 'adz_sequence', $adz_sequence );
		 update_post_meta( $post_id, 'paypal_email', $network_ids['paypal_email'] );
		 update_post_meta( $post_id, 'referral_code', $network_ids['referral_code'] );
		 update_post_meta( $post_id, 'network_ids', serialize($network_ids) );
		 $post_token = md5($post_id.'salt');
		 update_post_meta( $post_id, 'post_token', $post_token );
		 $network_token = md5($url);
		 update_post_meta( $post_id, 'network_token', $network_token );
		 update_post_meta( $post_id, 'publisher_share', $pub_share );
		 $publisher_user_id = get_post_meta($post_id,'publisher_user_id',true);
		 return $post_id;

	}else{
		return false;
	}

	
}
