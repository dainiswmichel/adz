<?php
@session_start();
// Register Custom Post Type
function adz_ad_post_type() {

	$labels = array(
		'name'                  => _x( 'Adz', 'Post Type General Name', 'text_domain' ),
		'singular_name'         => _x( 'Ad', 'Post Type Singular Name', 'text_domain' ),
		'menu_name'             => __( 'Adz network', 'text_domain' ),
		'name_admin_bar'        => __( 'Ad', 'text_domain' ),
		'archives'              => __( 'Ad Archives', 'text_domain' ),
		'attributes'            => __( 'Ad Attributes', 'text_domain' ),
		'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
		'all_items'             => __( 'All Adz', 'text_domain' ),
		'add_new_item'          => __( 'Add New Ad', 'text_domain' ),
		'add_new'               => __( 'Add New', 'text_domain' ),
		'new_item'              => __( 'New Adz Ad', 'text_domain' ),
		'edit_item'             => __( 'Edit Adz Ad', 'text_domain' ),
		'update_item'           => __( 'Update Ad', 'text_domain' ),
		'view_item'             => __( 'View Ad', 'text_domain' ),
		'view_items'            => __( 'View Ad', 'text_domain' ),
		'search_items'          => __( 'Search Ad', 'text_domain' ),
		'not_found'             => __( 'Not found', 'text_domain' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
		'featured_image'        => __( 'Featured Image', 'text_domain' ),
		'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
		'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
		'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
		'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
		'uploaded_to_this_item' => __( 'Uploaded to this Ad', 'text_domain' ),
		'items_list'            => __( 'Ads list', 'text_domain' ),
		'items_list_navigation' => __( 'Ads list navigation', 'text_domain' ),
		'filter_items_list'     => __( 'Filter ads list', 'text_domain' ),
	);
	$args = array(
		'label'                 => __( 'Ad', 'text_domain' ),
		'description'           => __( 'Adz to serve up to network', 'text_domain' ),
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
		'show_in_rest'          => true,
		'rest_controller_class' => 'WP_REST_Ads_Controller',
	);
	register_post_type( 'adz_ad', $args );

}
add_action( 'init', 'adz_ad_post_type', 0 );


function ad_network_meta_box_markup( $object, $box)
{

    wp_nonce_field(basename(__FILE__), "meta-box-nonce");
		$ad_coverage = get_post_meta( $object->ID, 'ad-coverage', true );
		$netsel = '';
		$pubsel = '';
		$typsel = '';
		switch($ad_coverage) {
			case 'network':
				$netsel = 'selected="selected"';
			break;
			case 'publisher':
				$pubsel = 'selected="selected"';
			break;
			case 'ad_types':
				$typsel = 'selected="selected"';
			break;

		}
    ?>

	<div>
	<p><strong>Coverage for Ad</strong></p>
		<select name="ad-coverage">
			<option value="">Select Coverage Option</option>
			<option <?php echo $netsel; ?> value="network">Whole Network</option>
			<option <?php echo $pubsel; ?> value="publisher">Individual Publisher</option>
			<option <?php echo $typsel; ?> value="ad_types">Selected Ad Types</option>
		</select>
			<p>Select ad coverage option:<br/>
				<ul><li><strong>Whole Network:</strong> This ad gets put into the rotation without restriction.</li>
				<li><strong>Individual Publisher</strong> This ad is only valid for a particular publisher.</li>
				<li><strong>Selected Ad Types</strong>Will be displayed to publishers accepting these ad types.</li>
				</ul>
			</p>
	</div>

	<?php
	$affiliation_network_name = get_post_meta( $object->ID, 'affiliation_network_name', true );
    $affiliation_network_url = get_post_meta( $object->ID, 'affiliation_network_url', true );
    $affiliation_id = get_post_meta( $object->ID, 'affiliation_id', true );
    ?>
    <table>
    	<tr>
    		<td>
	    		<label><b>Affiliation Network Name: </b> </label>		
	    	</td>
	    </tr>
	    <tr>	
	    	<td>
	    		<input type="text" name="affiliation_network_name" value="<?php echo $affiliation_network_name; ?>">
	    	</td>
    	</tr>
    	<tr>
    		<td>
	    		<label><b>Affiliation Network URL: </b> </label>		
	    	</td>
	    </tr>
	    <tr>	
	    	<td>
	    		<input type="text" name="affiliation_network_url" value="<?php echo $affiliation_network_url; ?>">
	    	</td>
    	</tr>
	    <tr>
	    	<td>
	    		<label><b>Affiliation id: </b></label>		
	    	</td>
	    </tr>
	    <tr>	
	    	<td>
	    		<input type="text" name="affiliation_id" value="<?php echo $affiliation_id; ?>">
	    	</td>

	    </tr>
    
    </table>


	<?php

}

function display_publishers($object, $box) {
wp_nonce_field(basename(__FILE__), "meta-box-nonce");
$publisher_id = get_post_meta( $object->ID, 'publisher_id', true );
?>
<p>Enter the Publisher ID For Whom This Ad Applies</p>
<p><em>Individual Publisher coverage only</em></p>
	<input type="text" name="publisher_id" value="<?php echo $publisher_id; ?>" />
	<?php

}

function network_rotation_ad_type($object, $box) {
	wp_nonce_field(basename(__FILE__), "meta-box-nonce");
	$is_network_adz = get_post_meta( $object->ID, 'is_network_adz', true );
	$network_adz_type = get_post_meta( $object->ID, 'network_adz_type', true );
	$network_adz_type_meta_value = get_post_meta( $object->ID, 'network_adz_type_meta_value', true );
	?>
		<script type="text/javascript">
			jQuery(document).ready(function(){

				if(jQuery('.is_network_adz').val() != 'yes'){
					jQuery('.network_adz_type_container').show();
				}else{
					jQuery('.network_adz_type_container').hide();
				}

				jQuery('.is_network_adz').change(function(){
					if(jQuery(this).val() == 'yes'){
						jQuery('.network_adz_type_container').show();
					}else{
						jQuery('.network_adz_type_container').hide();
					}
				});

				if(jQuery('.network_adz_type').val() == 'mimicked_ad'){
					jQuery('.network_adz_type_meta').show();
					jQuery('.network_adz_type_meta_label').text('Enter ad id which you wants to mimmic.');
				}
				else if(jQuery('.network_adz_type').val() == 'referrer_ad'){
					jQuery('.network_adz_type_meta').show();
					jQuery('.network_adz_type_meta_label').text('Enter Referrer ID.');
				}else{
					jQuery('.network_adz_type_meta').hide();
					jQuery('.network_adz_type_meta_value').val('');
				}

				jQuery('.network_adz_type').change(function(){
					if(jQuery(this).val() == 'mimicked_ad'){
						jQuery('.network_adz_type_meta').show();
						jQuery('.network_adz_type_meta_label').text('Enter ad id which you wants to mimmic.');
					}
					else if(jQuery(this).val() == 'referrer_ad'){
						jQuery('.network_adz_type_meta').show();
						jQuery('.network_adz_type_meta_label').text('Enter Referrer ID.');
					}else{
						jQuery('.network_adz_type_meta').hide();
						jQuery('.network_adz_type_meta_value').val('');
					}
				});
			});
		</script>
		<p><em>Is this advertisment a publisher, network, or visitor adz ad?</em></p>
		<p>
			<input type="radio" name="is_network_adz" class="is_network_adz" id="is_network_adz_0" value="yes"  <?php echo $is_network_adz == 'yes' ? 'checked="checked"' : '' ?>> <label for="is_network_adz_0"> Network </label>
			<input type="radio" name="is_network_adz" class="is_network_adz" id="is_network_adz_1" value="no" <?php if($is_network_adz == ''){ echo 'checked="checked"'; }  ?> <?php echo $is_network_adz == 'no' ? 'checked="checked"' : '' ?>> <label for="is_network_adz_1"> Publisher </label>

			<input type="radio" name="is_network_adz" class="is_network_adz" id="is_network_adz_2" value="visitor" <?php echo $is_network_adz == 'visitor' ? 'checked="checked"' : '' ?>> <label for="is_network_adz_2"> Visitor </label>
		</p>
		<div class="network_adz_type_container" style="display: none">
			<p><em>Specify the advertise type ?</em></p>
			<p>
				<input type="radio" name="network_adz_type" id="network_adz_type_0" class="network_adz_type" value="mimicked_ad" <?php echo $network_adz_type == 'mimicked_ad' ? 'checked="checked"' : '' ?>> <label for="network_adz_type_0"> Mimmicked Ad </label>
				<input type="radio" name="network_adz_type" id="network_adz_type_1" class="network_adz_type" value="referrer_ad" <?php echo $network_adz_type == 'referrer_ad' ? 'checked="checked"' : '' ?>> <label for="network_adz_type_1"> Referrer Ad </label>
				<input type="radio" name="network_adz_type" id="network_adz_type_2" class="network_adz_type" value="network_wide_ad" <?php echo $network_adz_type == 'network_wide_ad' ? 'checked="checked"' : '' ?>> <label for="network_adz_type_2"> Network wide Ad </label>
				<input type="radio" name="network_adz_type" id="network_adz_type_3" class="network_adz_type" value="none" <?php echo $network_adz_type == 'none' ? 'checked="checked"' : '' ?> <?php if($network_adz_type == ''){ echo 'checked="checked"'; }  ?>> <label for="network_adz_type_3"> None </label>
			</p>
			<div class="network_adz_type_meta" style="display: none">
				<p class="network_adz_type_meta_label"><em> </em></p>
				<p><input type="text" class="network_adz_type_meta_value" name="network_adz_type_meta_value" value="<?php echo $network_adz_type_meta_value; ?>"></p>

			</div>
		</div>	
		
		
	<?php

}



function adz_save_ad_meta($post_id) {
	if ( !isset( $_POST['meta-box-nonce'] ) || !wp_verify_nonce( $_POST['meta-box-nonce'], basename( __FILE__ ) ) ){
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ){
		return;
	}
	if ( isset( $_POST['publisher_id'] ) ) {
		update_post_meta( $post_id, 'publisher_id', sanitize_text_field( $_POST['publisher_id'] ) );
	}
	if ( isset( $_POST['ad-coverage'] ) ) {
		update_post_meta( $post_id, 'ad-coverage', sanitize_text_field( $_POST['ad-coverage'] ) );
	}

	if ( isset( $_POST['is_network_adz'] ) ) {
		update_post_meta( $post_id, 'is_network_adz', sanitize_text_field( $_POST['is_network_adz'] ) );
	}

	if ( isset( $_POST['network_adz_type'] ) ) {
		update_post_meta( $post_id, 'network_adz_type', sanitize_text_field( $_POST['network_adz_type'] ) );
	}

	if ( isset( $_POST['network_adz_type_meta_value'] ) ) {
		update_post_meta( $post_id, 'network_adz_type_meta_value', sanitize_text_field( $_POST['network_adz_type_meta_value'] ) );
	}

}

add_action( 'save_post_adz_ad', 'adz_save_ad_meta', 10, 2 );

function add_ad_network_meta_box()
{
   // add_meta_box("ad-network-meta-box", "Settings for This Ad", "ad_network_meta_box_markup", "adz_ad", "side", "high", null);
		add_meta_box("ad-network-publisher-meta-box", "Publisher(s) for This Adz Ad", "display_publishers", "adz_ad", "normal", "high", null);

	add_meta_box("ad-network-meta-box", "Which type of Adz Ad ?", "network_rotation_ad_type", "adz_ad", "normal", "high", null);	

}

add_action("add_meta_boxes", "add_ad_network_meta_box");

// Register Custom Taxonomy
function ad_taxonomy() {

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
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => false,
		'show_tagcloud'              => false,
		'rewrite'                    => false,
		'show_in_rest'               => true,
		'rest_base'                  => 'ad_type',
    'rest_controller_class'      => 'WP_REST_Terms_Controller'
	);
	register_taxonomy( 'ad_taxonomy', array( 'adz_ad','adz_publisher' ), $args );

}
add_action( 'init', 'ad_taxonomy', 0 );

function create_publisher_ad($ad_title,$ad_content,$publisher_id,$ad_type_slugs,$custom_field) {

	$post = array();
	$post['post_title'] = $ad_title;
	$post['post_content'] = $ad_content;
	$post['post_type'] = 'adz_ad';
	$post['post_status'] = 'publish';
	$post['filter'] = true; 
	error_log("Trying to post:".var_export($post, true));
kses_remove_filters();
	$post_id = wp_insert_post($post);
	kses_init_filters();
	if ($post_id) {

		 add_post_meta($post_id, 'publisher_id', $publisher_id, true );
		 add_post_meta($post_id, 'ad-coverage', 'publisher', true);
		 foreach ($custom_field as $key => $value) {
		 	
		 	update_post_meta($post_id, $key, $value);
		 }
		 wp_set_post_terms( $post_id, $ad_type_slugs, 'ad_taxonomy', false );
		/* $post['post_title'] = $ad_title.' (Network)';
		 $mimicked_ad = wp_insert_post($post);
		 if($mimicked_ad){
		 	add_post_meta( $mimicked_ad, 'publisher_id', $publisher_id, true );
		 	add_post_meta( $mimicked_ad, 'ad-coverage', 'publisher', true );
		 	add_post_meta( $mimicked_ad, 'publisher_ad_id', $post_id, true );
		 	foreach ($custom_field as $key => $value) {		 		
		 		update_post_meta($mimicked_ad, $key, $value);
		 	}
		 	wp_set_post_terms( $mimicked_ad, $ad_type_slugs, 'ad_taxonomy', false );
		 }*/
		 
	} else {
		error_log("Create Failure");
	}




	return $post_id;
}

function update_publisher_ad($post_id, $ad_title,$ad_content,$publisher_id,$ad_type_slugs,$custom_field) {
	$post = array();
	$post['ID'] = $post_id;
	$post['post_title'] = $ad_title;
	$post['post_content'] = $ad_content;
	$post['post_type'] = 'adz_ad';
	$post['post_status'] = 'publish';
	$post['filter'] = true;
	$post_pub_id = get_post_meta($post_id, 'publisher_id', true);
	foreach ($custom_field as $key => $value) {
		update_post_meta($post_id, $key, $value);
	}
	$ok = false;
	if ($post_pub_id == $publisher_id ) {
		$ad_coverage = get_post_meta($post_id, 'ad-coverage', true);
		if ($ad_coverage == 'publisher') {
			kses_remove_filters();
			$ok = wp_update_post($post);
			kses_init_filters();
			if ($ok) {
				$ok = $post_id;
				 wp_set_post_terms( $post_id, $ad_type_slugs, 'ad_taxonomy', false );
			}
		}
	}

	return $ok;
}

function delete_publisher_ad($post_id, $publisher_id) {
	$post_pub_id = get_post_meta($post_id, 'publisher_id', true);
	if ($post_pub_id == $publisher_id ) {
		$ad_coverage = get_post_meta($post_id, 'ad-coverage', true);
		if ($ad_coverage == 'publisher') {
			$post_id = wp_delete_post( $post_id, true );
		}
		else {
			return false;
		}
	} else {
		return false;
	}

	return $post_id;
}

add_shortcode( 'adzworld_iframe', 'adz_iframe_shortcode' );
function adz_iframe_shortcode( $atts, $content = null ) {

	$attributes = shortcode_atts( array(
		'adz_url' => 'http://adz.world',
		'height' => 600,
		'width' => '100%',
	), $atts );
	$output  = '<iframe src="'.$attributes['adz_url'].'" height="'.$attributes['height'].'" width="'.$attributes['width'].'"></iframe>';

	return $output;
}

function set_visitor_session( $user_login, $user ) {
	global $wpdb;
	$visitor_ip = $_SERVER['REMOTE_ADDR'];

	$logged_in_visitor = $wpdb->get_results("SELECT * FROM visitor_loging_ip WHERE visitor_ip = '".$visitor_ip."' AND user_id = '".$user->data->ID."'");
	
		
	if(count($logged_in_visitor) > 0){
		
		$wpdb->update('visitor_loging_ip',array('visitor_ip' => $visitor_ip,'user_id' => $user->data->ID),array('visitor_ip' => $visitor_ip ));

	}else{
		
		global $wpdb;

		$wpdb->query("INSERT INTO visitor_loging_ip (visitor_ip, user_id) VALUES ('".$visitor_ip."', '".$user->data->ID."')" ); 	

	}
	
	//$user = wp_get_current_user();
	//$_SESSION['adz_world_visitor'] = $user->data->ID;
	//setcookie("TestCookie11", "sujeet", time() + 3600);
}
add_action('wp_login', 'set_visitor_session', 10, 2);


function check_user_logged_in($visitor_ip){
	global $wpdb;
	$logged_in_visitor = $wpdb->get_results("SELECT * FROM visitor_loging_ip WHERE visitor_ip = '".$visitor_ip."'");
	
	if(!empty($logged_in_visitor)){
		return $logged_in_visitor[0]->user_id;
	}else{
		return false;
	}
}

function delete_visitor_session() {
	global $wpdb;
	$wpdb->query('DELETE  FROM `visitor_loging_ip` WHERE visitor_ip = "'.$_SERVER['REMOTE_ADDR'].'"' );
	//$wpdb->delete( 'visitor_loging_ip', array( 'visitor_ip' => $_SERVER['REMOTE_ADDR'] ));
}
add_action('wp_logout', 'delete_visitor_session');

function update_current_user_ip(){
	global $wpdb;

	if(is_user_logged_in()){
		$visitor_ip = $_SERVER['REMOTE_ADDR'];
		$wpdb->update('visitor_loging_ip',array('visitor_ip' => $visitor_ip,'user_id' => get_current_user_id()),array('visitor_ip' => $visitor_ip ));
	}
}

add_action('init','update_current_user_ip');