<?php
/**
 * Plugin Name: adz.world
 * Description: Create a permission-based advertising ecosystem on your site: visitors choose to view adz in exchange for access to content, and publishers serve their own adz without surveillance or a third-party broker.
 * Version: 2.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: dainismichel
 * Author URI: http://www.dainiswmichel.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: adz-world
 *
 * @package adz.world
 */

defined( 'ABSPATH' ) || exit;

define( 'ADZ_WORLD', plugin_dir_path( __FILE__ ) );
define( 'ADZ_WORLD_VERSION', '2.0.0' );

/**
 * Base URL of the ad network this site federates with.
 *
 * Empty by default: this plugin serves the publisher's own adz and needs no
 * remote network. The central adz.world server it was built against in 1.0.8
 * no longer exists. Filter this to point at a node you control.
 *
 * @since 2.0.0
 */
$adz_ad_network_base_url = apply_filters( 'adz_ad_network_base_url', '' );

require_once ADZ_WORLD . 'session-handling.php';
require_once ADZ_WORLD . 'includes/adz-visitor.php';
require_once ADZ_WORLD . 'includes/adz-views.php';
require_once ADZ_WORLD . 'includes/adz-ad-source.php';
require_once ADZ_WORLD . 'vendor/eof/eof.php';
require_once ADZ_WORLD . 'vendor/eof/core/field.php';
require_once ADZ_WORLD . 'vendor/eof/adz-config.php';
require_once ADZ_WORLD . 'classes/network_authorization.class.php';
require_once ADZ_WORLD . 'classes/eof_fields/dynamic.php';
require_once ADZ_WORLD . 'classes/eof_fields/select-caps.php';
require_once ADZ_WORLD . 'includes/adz-helper.php';
require_once ADZ_WORLD . 'includes/adz-cpt-ads.php';
require_once ADZ_WORLD . 'includes/adz-shortcode.php';

/**
 * Create the views table on activation.
 *
 * @return void
 */
function activate_adz_world() {
	adz_views_install();
}
register_activation_hook( __FILE__, 'activate_adz_world' );

/**
 * Upgrade the schema for sites updated in place.
 *
 * register_activation_hook does not fire on update, so installs that came from
 * 1.0.8 would otherwise keep the old table.
 *
 * @return void
 */
add_action( 'init', 'adz_views_maybe_upgrade' );
register_activation_hook( __FILE__,'activate_adz_world' );

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
}// End of thr function.


add_action( 'admin_enqueue_scripts', 'adz_enqueue_scripts_admin' );


/**
 * AJAX endpoint: decide whether to serve an ad, and serve it.
 *
 * Rewritten for the standalone plugin. The 1.0.8 version fetched every ad from
 * adz.world over HTTP, returned early unless the site was registered with that
 * network, and repeated the same rotation-advance block four times. Ads now
 * come from local adz_ad posts and the rotation is handled in one place.
 *
 * Echoes "continue" to let the visitor through, or renders the ad template.
 *
 * @return void
 */
function adz_get_advertise_content() {
	$nonce = isset( $_POST['adz_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['adz_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'adzdotworld' ) ) {
		echo 'continue';
		exit;
	}

	$interval = isset( $_POST['ad_visibility_interval'] ) ? $_POST['ad_visibility_interval'] : '';

	if ( ! is_numeric( $interval ) ) {
		echo 'continue';
		exit;
	}

	$interval     = max( 0, (int) $interval );
	$target       = isset( $_POST['target'] ) ? (int) $_POST['target'] : 0;
	$target_type  = isset( $_POST['target_type'] ) ? sanitize_key( wp_unslash( $_POST['target_type'] ) ) : '';
	$display_type = isset( $_POST['display_type'] ) ? sanitize_key( wp_unslash( $_POST['display_type'] ) ) : 'popup';
	$repeat_times = isset( $_POST['repeat_times'] ) ? sanitize_text_field( wp_unslash( $_POST['repeat_times'] ) ) : 'infinite';
	$rotation_id  = isset( $_POST['rotations_id'] ) ? sanitize_key( wp_unslash( $_POST['rotations_id'] ) ) : '';
	$template     = isset( $_POST['adz_template'] ) ? adz_safe_template( wp_unslash( $_POST['adz_template'] ) ) : '';
	$closing      = isset( $_POST['close_adz'] ) && 'true' === $_POST['close_adz'];

	if ( ! $target || ! $template ) {
		echo 'continue';
		exit;
	}

	// The visitor finished watching: record the view, open the browsing window.
	if ( $closing ) {
		adz_record_view( $target, $target_type );
		adz_visitor_start_grace_period( $interval );

		echo 'close';
		exit;
	}

	// Inside the window a previous view bought them: no ad.
	if ( adz_visitor_in_grace_period() ) {
		echo 'continue';
		exit;
	}

	if ( ! adz_view_required( $target, $target_type, $repeat_times ) ) {
		echo 'continue';
		exit;
	}

	// Choose the ad.
	$sequence = isset( $_POST['sequence'] ) ? wp_unslash( $_POST['sequence'] ) : '';
	$sequence = array_filter( array_map( 'trim', explode( ',', (string) $sequence ) ) );

	if ( empty( $sequence ) ) {
		$sequence = adz_get_all_ad_ids();
	}

	$state_key   = $rotation_id ? $rotation_id : 'adz_rotation_' . $target . '_' . $target_type;
	$ad_to_serve = adz_next_ad_in_sequence( $sequence, $state_key );
	$ad_text     = adz_get_ad_content( $ad_to_serve );

	// No usable ad on this site means nothing to require: let the visitor read.
	if ( '' === $ad_text ) {
		echo 'continue';
		exit;
	}

	$ad_visibility          = isset( $_POST['ad_visibility'] ) ? max( 0, (int) $_POST['ad_visibility'] ) : 10;
	$ad_visibility_interval = $interval;

	require __DIR__ . '/adz-templates/' . $template;
	exit;
}
add_action('wp_ajax_adz_get_advertise_content', 'adz_get_advertise_content');
add_action('wp_ajax_nopriv_adz_get_advertise_content', 'adz_get_advertise_content');


function adz_add_adz_wrapper($content) {
    global $post;

    return '<div class="adz-world-container">'.$content.'</div>';
}

add_filter('the_content', 'adz_add_adz_wrapper');



/* This function is use for fetching adz rotation and create there flow. */ 

function adz_check_thru_page_adz(){	
	
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
	
	if( !empty($ad_settings_options['ad_settings']) && !is_home() ){
		$category_add = 'false';
		$publisher_rotations = array();

		foreach ( $ad_settings_options['ad_settings'] as $options ){
			if( !isset($options['no_of_times'])){
				$options['no_of_times'] = '';
			}

			if( !isset($options['rotation_categories']) || !is_array($options['rotation_categories']) ){
				$options['rotation_categories'] = array();
			} 

			if( !isset($options['rotation_tags']) || !is_array($options['rotation_tags']) ){
				$options['rotation_tags'] = array(); 
			} 

			if( !isset($options['rotation_pages']) || !is_array($options['rotation_pages']) ){
				$options['rotation_pages'] = array(); 
			} 

			if( !isset($options['ad_sequences']) || !is_array($options['ad_sequences']) ){
				$options['ad_sequences'] = array();
			}	
				
				
			
			if(array_intersect($options['rotation_categories'], $category_ids) || in_array(get_the_ID(),$options['rotation_pages']) || array_intersect($options['rotation_tags'], $tag_ids)){
				

				if( 'in_sequence' == $options['adz_rotation'] ){

					$adz_sequences = adz_get_network_id($options['ad_sequences']);
				}else{

					$adz_sequences = adz_get_publisher_adz();
				}

				if( function_exists( 'wc_memberships' ) && is_user_logged_in() && !wc_memberships_is_user_active_member(get_current_user_id(),'never-show-adz') ){

					if( wc_memberships_is_user_active_member(get_current_user_id(),$options['member_level']) ) {

						$publisher_rotations[] = array('sequence' => $adz_sequences,'roatation_name' => $options['rotation_name'], 'loop' => $options['loop'], 'no_of_times' => $options['no_of_times'], 'ad_seconds' => $options['ad_seconds'], 'browse_seconds' => $options['browse_seconds'], 'popup_or_page' => $options['popup_or_page'],'rotation_id' => $options['rotation_id'],'rotation_page' => get_the_ID(),'adz_template' => $options['adz_template']) ;

					}
				}elseif( function_exists( 'wc_memberships' ) &&  wc_memberships_is_user_active_member(get_current_user_id(),$options['member_level']) && $options['member_level'] == 'never-show-adz' ){

					$publisher_rotations[] = array('sequence' => $adz_sequences,  'roatation_name' => $options['rotation_name'], 'loop' => $options['loop'], 'no_of_times' => $options['no_of_times'], 'ad_seconds' => $options['ad_seconds'], 'browse_seconds' => $options['browse_seconds'], 'popup_or_page' => $options['popup_or_page'],'rotation_id' => $options['rotation_id'],'rotation_page' => get_the_ID(),'adz_template' => $options['adz_template']) ;


				}elseif( !is_user_logged_in() && $options['member_level'] == 'non-logged' ){

					$publisher_rotations[] = array('sequence' => $adz_sequences,  'roatation_name' => $options['rotation_name'], 'loop' => $options['loop'], 'no_of_times' => $options['no_of_times'], 'ad_seconds' => $options['ad_seconds'], 'browse_seconds' => $options['browse_seconds'], 'popup_or_page' => $options['popup_or_page'],'rotation_id' => $options['rotation_id'],'rotation_page' => get_the_ID(),'adz_template' => $options['adz_template']) ;					

				}
						
			}				
		}//End of foreach

		$GLOBALS['publisher_rotations'] = $publisher_rotations;

		if( !empty($publisher_rotations) ){

			$rotation_reset = "false";
			foreach ( $publisher_rotations as $roatations ) {
				$rotation_adz_pool = get_option($roatations['rotation_id']);
				if( $rotation_adz_pool ){
					if( empty($rotation_adz_pool['un_served']) || (count($rotation_adz_pool['un_served']) == 1 && $rotation_adz_pool['un_served'][0] == '') ){
						$rotation_reset = "true";
					}else{
						$rotation_reset = "false";
					}
				}else{
					$rotation_reset = "false";
				}
			}
			
			if( $rotation_reset == "true" ){
				foreach ( $publisher_rotations as $roatations ) {
					delete_option( $roatations['rotation_id'] );
				}
			}
			foreach ( $publisher_rotations as $rotations ) {
					
				$ad_to_serve = adz_check_roatation_completed_or_not($rotations);
				if( $ad_to_serve ){

					if( !empty($rotations['loop']) && is_array($rotations['loop']) && $rotations['loop'][0] == 'yes' ){
						$repeat_times = 'infinite';
						
					}elseif( $rotations['no_of_times'] != '' ){
						$repeat_times = $rotations['no_of_times'];	
						
					}else{
						$repeat_times = 'infinite';
					}
					
					if( $rotations['popup_or_page'] == 'page' ){						
						break;

					}elseif( $rotations['popup_or_page'] == 'popup' ){

						break;

					}elseif( $rotations['popup_or_page'] == 'thru_page' ){
						
						adz_show_advertise( $rotations, $ad_to_serve, $repeat_times );
						
					}
				}// End Of the Coddition to check ad_to_serve.
				
					
			}// End of the loop of publisher rotations
		}// End of condition to check for publisher rotations is empty of not.
	
	}// End of condition to check for adz rotation empty or not.

}// End of the function check_thru_page_adz.

## This rotation is use to add needed javascript in the header for ads rotation ##

function adz_display_adz(){

	global $publisher_rotations;
	if( !empty($publisher_rotations) ){
		
		foreach ( $publisher_rotations as $rotations ) {
			$ad_to_serve = adz_check_roatation_completed_or_not( $rotations );
			if( $ad_to_serve ){

				if( !empty($rotations['loop']) && is_array($rotations['loop']) && $rotations['loop'][0] == 'yes' ){
					$repeat_times = 'infinite';
					
				}elseif( $rotations['no_of_times'] != '' ){
					$repeat_times = $rotations['no_of_times'];	
					
				}else{
					$repeat_times = 'infinite';
				}

				if( $rotations['popup_or_page'] == 'page' ){
						
					adz_get_advertise( $rotations['ad_seconds'], $rotations['browse_seconds'], $repeat_times, 'page', $rotations['rotation_page'], $ad_to_serve,$rotations['rotation_id'], $rotations['sequence'], $rotations['popup_or_page'], $rotations['adz_template'] );
					break;

				}elseif( $rotations['popup_or_page'] == 'popup' ){

					adz_get_advertise_popup( $rotations['ad_seconds'], $rotations['browse_seconds'], $repeat_times, 'page', $rotations['rotation_page'], $ad_to_serve, $rotations['rotation_id'], $rotations['sequence'], $rotations['popup_or_page'], $rotations['adz_template'] );
					break;

				}				
				
			}// End Of the Coddition to check ad_to_serve.				
		}// End of the loop of publisher rotations
	}// End of condition to check for publisher rotations is empty of not.

}// End of the function display_adz.

add_action('wp_head','adz_display_adz');


## This function is use to Show Advertise when a throw Page display is set in the adz rotation ##

/**
 * Render the full-page ad that stands in front of gated content.
 *
 * Called from adz_check_thru_page_adz() on the 'wp' hook, before the post is
 * rendered. When an ad is due this loads the template and exits, so the post
 * itself never reaches the browser; the visitor continues once the timer runs
 * out and the AJAX handler records their view.
 *
 * In 1.0.8 this fetched the ad from adz.world and returned early unless the
 * site was registered there, which is why the gate stopped engaging when the
 * network went away.
 *
 * @param array  $roatations   Rotation settings for the matched rule.
 * @param mixed  $ad_to_serve  Ad reference chosen by the caller.
 * @param mixed  $repeat_times Maximum ads per day, or 'infinite'.
 * @return void
 */
function adz_show_advertise( $roatations, $ad_to_serve, $repeat_times ) {
	$target      = (int) get_the_ID();
	$target_type = 'thru_page';

	if ( ! $target ) {
		return;
	}

	$template = adz_safe_template( isset( $roatations['adz_template'] ) ? $roatations['adz_template'] : '' );

	if ( '' === $template ) {
		return;
	}

	if ( ! adz_view_required( $target, $target_type, $repeat_times ) ) {
		return;
	}

	if ( adz_visitor_in_grace_period() ) {
		return;
	}

	// Choose the ad: the caller's pick, else the next one in this rotation.
	$ad_text = adz_get_ad_content( $ad_to_serve );

	if ( '' === $ad_text ) {
		$sequence = isset( $roatations['sequence'] ) ? $roatations['sequence'] : '';
		$sequence = array_filter( array_map( 'trim', explode( ',', (string) $sequence ) ) );

		if ( empty( $sequence ) ) {
			$sequence = adz_get_all_ad_ids();
		}

		$state_key   = ! empty( $roatations['rotation_id'] ) ? $roatations['rotation_id'] : 'adz_rotation_' . $target;
		$ad_to_serve = adz_next_ad_in_sequence( $sequence, $state_key );
		$ad_text     = adz_get_ad_content( $ad_to_serve );
	}

	// Nothing to show means nothing to require: let the visitor read the post.
	if ( '' === $ad_text ) {
		return;
	}

	// Variables consumed by the template.
	$ad_visibility          = isset( $roatations['ad_seconds'] ) ? max( 0, (int) $roatations['ad_seconds'] ) : 10;
	$ad_visibility_interval = isset( $roatations['browse_seconds'] ) ? max( 0, (int) $roatations['browse_seconds'] ) : 0;
	$display_type           = isset( $roatations['popup_or_page'] ) ? $roatations['popup_or_page'] : 'thru_page';

	require ADZ_WORLD . 'adz-templates/' . $template;
	exit;
}

add_action('wp','adz_check_thru_page_adz');
