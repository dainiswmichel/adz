<?php
// Register Custom Post Type
function adz_ad_post_type_pub() {

	$labels = array(
		'name'                  => _x( 'Adz.world', 'Post Type General Name', 'text_domain' ),
		'singular_name'         => _x( 'Adz.world', 'Post Type Singular Name', 'text_domain' ),
		'menu_name'             => __( 'Adz.world', 'text_domain' ),
		'name_admin_bar'        => __( 'Adz.world', 'text_domain' ),
		'archives'              => __( 'Adz.world Archives', 'text_domain' ),
		'attributes'            => __( 'TV Station Ad Attributes', 'text_domain' ),
		'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
		'all_items'             => __( 'All Adz', 'text_domain' ),
		'add_new_item'          => __( 'Add New Ad', 'text_domain' ),
		'add_new'               => __( 'Add New Adz', 'text_domain' ),
		'new_item'              => __( 'New Ad', 'text_domain' ),
		'edit_item'             => __( 'Edit Ad', 'text_domain' ),
		'update_item'           => __( 'Update Ad', 'text_domain' ),
		'view_item'             => __( 'View Ad', 'text_domain' ),
		'view_items'            => __( 'View Ad', 'text_domain' ),
		'search_items'          => __( 'Search Adz.world', 'text_domain' ),
		'not_found'             => __( 'Not found', 'text_domain' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
		'featured_image'        => __( 'Featured Image', 'text_domain' ),
		'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
		'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
		'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
		'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
		'uploaded_to_this_item' => __( 'Uploaded to this Ad', 'text_domain' ),
		'items_list'            => __( 'Adz.world list', 'text_domain' ),
		'items_list_navigation' => __( 'Adz.world list navigation', 'text_domain' ),
		'filter_items_list'     => __( 'Filter ads list', 'text_domain' ),
	);
	$args = array(
		'label'                 => __( 'Adz.world', 'text_domain' ),
		'description'           => __( 'Adz.world to serve up to network', 'text_domain' ),
		'labels'                => $labels,
		'supports'              => array('title', 'excrpt', 'editor', 'custom-fields', ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => false,
		'menu_icon' 			=> 'dashicons-welcome-view-site',
		
		
	);
	register_post_type( 'adz_ad', $args );

}
add_action( 'init', 'adz_ad_post_type_pub', 0 );




function ad_network_meta_box_markup_pub($post){

    wp_nonce_field(basename(__FILE__), "meta-box-nonce");
    $affiliation_network_name = get_post_meta( $post->ID, 'affiliation_network_name', true );
    $affiliation_network_url = get_post_meta( $post->ID, 'affiliation_network_url', true );
    $affiliation_id = get_post_meta( $post->ID, 'affiliation_id', true );
    ?>
    <table>
    	<tr>
    		<td>
	    		<label><b>Affiliation Network Name: </b> </label>		
	    	</td>
	    	<td>
	    		<input type="text" name="affiliation_network_name" value="<?php echo $affiliation_network_name; ?>">
	    	</td>
    	</tr>
    	<tr>
    		<td>
	    		<label><b>Affiliation Network URL: </b> </label>		
	    	</td>
	    	<td>
	    		<input type="text" name="affiliation_network_url" value="<?php echo $affiliation_network_url; ?>">
	    	</td>
    	</tr>
	    <tr>
	    	<td>
	    		<label><b>Affiliation id: </b></label>		
	    	</td>
	    	<td>
	    		<input type="text" name="affiliation_id" value="<?php echo $affiliation_id; ?>">
	    	</td>

	    </tr>
    
    </table>
    <?php

}


function add_ad_network_meta_box_pub(){
    add_meta_box("ad-network-meta-box", "Settings for This Ad", "ad_network_meta_box_markup_pub", "adz_ad", "normal", "high", null);

}

add_action("add_meta_boxes", "add_ad_network_meta_box_pub");


function set_network_adz_id_column($columns) {
    unset( $columns['author'] );
    $columns['network_ad_id'] = 'Advertise Id';

    return $columns;
}
add_filter( 'manage_adz_ad_posts_columns', 'set_network_adz_id_column' );

function network_adz_id_column( $column, $post_id ) {
    switch ( $column ) {

        case 'network_ad_id' :
            $Id = get_post_meta($post_id,'network_ad_id', true);
            
                echo $Id;
            break;

    }
}

add_action( 'manage_adz_ad_posts_custom_column' , 'network_adz_id_column', 10, 2 );

function adz_ad_columns_head($defaults) {  
	if(isset($_GET['post_type']) && $_GET['post_type'] == 'adz_ad'){
    
		$new = array();
	    $network_ad_id = $defaults['network_ad_id'];  // save the tags column
	    unset($defaults['network_ad_id']);   // remove it from the columns list

	    foreach($defaults as $key=>$value) {
	    	
	        if($key == 'date') {  // when we find the date column
	           $new['network_ad_id'] = $network_ad_id;  // put the tags column before it
	        }    
	        $new[$key] = $value;
	    }  	
		return $new;
	}else{
		return $defaults;
	}
      
} 
add_filter('manage_posts_columns', 'adz_ad_columns_head');  
// Register Custom Taxonomy
function ad_taxonomy_pub() {

	$labels = array(
		'name'                       => _x( 'Ad Types', 'Taxonomy General Name', 'text_domain' ),
		'singular_name'              => _x( 'Ad Type', 'Taxonomy Singular Name', 'text_domain' ),
		'menu_name'                  => __( 'Ad Types', 'text_domain' ),
		'all_items'                  => __( 'All Ad Types', 'text_domain' ),
		'parent_item'                => __( 'Parent Item', 'text_domain' ),
		'parent_item_colon'          => __( 'Parent Item:', 'text_domain' ),
		'new_item_name'              => __( 'New Ad Type', 'text_domain' ),
		'add_new_item'               => __( 'Add New Ad Type', 'text_domain' ),
		'edit_item'                  => __( 'Edit Ad Type', 'text_domain' ),
		'update_item'                => __( 'Update Ad Type', 'text_domain' ),
		'view_item'                  => __( 'View Ad Type', 'text_domain' ),
		'separate_items_with_commas' => __( 'Separate items with commas', 'text_domain' ),
		'add_or_remove_items'        => __( 'Add or remove items', 'text_domain' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
		'popular_items'              => __( 'Popular Ad Types', 'text_domain' ),
		'search_items'               => __( 'Search Ad Types', 'text_domain' ),
		'not_found'                  => __( 'Not Found', 'text_domain' ),
		'no_terms'                   => __( 'No Ad Types', 'text_domain' ),
		'items_list'                 => __( 'Ad Type List', 'text_domain' ),
		'items_list_navigation'      => __( 'Ad Type list navigation', 'text_domain' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => false,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => false,
		'show_tagcloud'              => false,
		'rewrite'                    => false,
		'show_in_rest'               => false
	);
	register_taxonomy( 'ad_taxonomy', array( 'adz_ad' ), $args );



}
add_action( 'init', 'ad_taxonomy_pub', 0 );

/* Sync with Server */
function adz_sync_network_ads($post_id, $post_after, $post_before) {
	if ($post_after->post_type == 'adz_ad') {
		adz_NetworkAuthorization::update_ad($post_id, $post_after);
	}
}

add_action( 'post_updated', 'adz_sync_network_ads', 10, 3 );



function adz_sync_network_ads_delete( $post_id ){

    // We check if the global post type isn't ours and just return
    global $post_type;
    if ( $post_type != 'adz_ad' ) {
			return;
		}
		adz_NetworkAuthorization::delete_ad($post_id);
    // My custom stuff for deleting my custom post type here
}

add_action( 'before_delete_post', 'adz_sync_network_ads_delete' );

function adz_ifram_button(){
	echo '<a title="Insert Adz.world iFrame" class="button insert-media add_media" id="insert-adz-iframe-button" href="#">Adz.world iFrame</a>';
}

add_action('media_buttons',  'adz_ifram_button', 11);

function adz_button_js(){
	  echo '<script type="text/javascript">
	jQuery(document).ready(function(){
	   jQuery("#insert-adz-iframe-button").click(function() {
	      send_to_editor("[adzworld_iframe adz_url=\"http://adz.world\" height=\"600\" width=\"100%\" ]");
	      return false;
	   });
	});
	</script>';
}
add_action('admin_print_footer_scripts', 'adz_button_js', 199);


// function to add new submenu for Adz template setting.
function adz_templates_setting_menu() {
	add_submenu_page('edit.php?post_type=adz_ad', 'Adz Template Settings', 'Adz Template Settings', 'manage_options', 'adz-template-setting', 'adz_template_setting');
}
add_action('admin_menu', 'adz_templates_setting_menu', 11 );


// Function to add Adz template setting form in backend.
function adz_template_setting(){
	screen_icon();
	if( isset($_POST['adz_template']) && $_POST['adz_template'] != '' ){

		$template_settings = array('header_text' => $_POST["header_text"],
								   'header_style' => $_POST["header_style"],
							 	   'footer_text' => $_POST["footer_text"],
							 	   'footer_style' => $_POST["footer_style"]
							  );

		update_option("template_".$_POST['adz_template'],serialize($template_settings),true);
	}
	echo '<form method="post" action="">';
	echo ' <h3>Edit your Adz template header and footer text here.</h3>';
	echo '<label>Select Adz Template</label><br>';

	echo "<select name='adz_template' id='adz_template_list'>";
	echo "<option value=''>Select Template</option>";
	$dir    = ADZ_WORLD.'adz-templates';
	$templates = scandir($dir,1);
	if(!empty($templates)){
		foreach ($templates as $template) {
			$ext = pathinfo($template, PATHINFO_EXTENSION);
			if($ext == 'php')
			echo "<option value='".$template."'>".$template."</option>";
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

function get_template_settings(){
	if(isset($_POST['selected_template'])){
		$template_name = $_POST['selected_template'];
		$template_data = get_option('template_'.$template_name);
		
		echo json_encode( unserialize($template_data) );
		exit;
	}

}
add_action('wp_ajax_get_template_settings', 'get_template_settings');
add_action('wp_ajax_nopriv_get_template_settings', 'get_template_settings');