<?php
/**
 * Helper functions for serving adz.
 *
 * @package adz.world
 */

defined( 'ABSPATH' ) || exit;

/*
 * role_exists() used to be defined here. WordPress core now ships its own,
 * so redeclaring it is a fatal error on activation. Nothing in this plugin
 * called it, so it is simply gone.
 */

function adz_get_advertise($ad_visibility,$ad_visibility_interval,$repeat_times,$target_type,$target,$ad_to_serve,$rotations_id,$sequence,$display_type,$adz_template){
	
	// 1.0.8 wrote $_SESSION['first_time'] here, but session_start() was never
	// called anywhere in the plugin, so the throttle never actually throttled.
	if ( ! adz_visitor_in_grace_period() ) {
		adz_visitor_start_grace_period( $ad_visibility_interval );
	}
	
	?>
	<script type="text/javascript">

		jQuery(document).ready(function(){
			var repeat_adz = 'yes';
			var interval = setInterval(function () {
				if(repeat_adz == 'yes'){
					
					browsing_advertise();	
				}				
				    
			}, 60000);
			function browsing_advertise(){

							
				jQuery.ajax({
			        url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
			        type : 'post',
			        data: {
			            action :'adz_get_advertise_content',
			            target_type : '<?php echo $target_type;?>',
			            target : '<?php echo $target; ?>',
			            ad_visibility_interval : '<?php echo $ad_visibility_interval; ?>',
			            ad_to_serve : '<?php echo $ad_to_serve;?>',
			            rotations_id : '<?php echo $rotations_id;?>',
			            sequence : '<?php echo $sequence;?>',
			            display_type : '<?php echo $display_type;?>',
			            adz_template : '<?php echo $adz_template;?>',
			            repeat_times : '<?php echo $repeat_times;?>' ,
			            adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' );?>'
			        },
			        success:function(data) {
			        	
			        	if(jQuery.trim(data) != 'continue'){
			        		<?php if(is_category()){ ?>
			        			jQuery('.page-header').after('<div class="adz-world-category-wapper"></div>');
			        			jQuery('.status-publish').remove();
			        			jQuery('.pagination').remove();

			        		<?php } ?>
			        		<?php if(is_category()){ ?>
								jQuery(document).find('.adz-world-category-wapper').html(window.atob(data));
								var i = <?php echo $ad_visibility;?>;
								var adz_interval = setInterval(function () {
								    if (i >= 0) {
								    	jQuery('.adz_timer').text(" "+i+" ");
								        i--;
								    } else {
								    	jQuery('.continue_reading').text('Continue >>');
								    	jQuery('.continue_reading').addClass('close_adz');	
								    	jQuery('.continue_reading').css('cusrsor','pointer');						       
								    	clearInterval(adz_interval);
								    	repeat_adz = 'no';
								    }
								}, 1000);
							<?php }else{ ?>
								jQuery('.adz-world-container').html(window.atob(data));
								var i = <?php echo $ad_visibility;?>;
								var adz_interval = setInterval(function () {
								    if (i >= 0) {
								    	jQuery('.adz_timer').text(" "+i+" ");
								        i--;
								    } else {
								    	jQuery('.continue_reading').text('Continue >>');
								    	jQuery('.continue_reading').addClass('close_adz');	
								    	jQuery('.continue_reading').css('cusrsor','pointer');						       
								    	clearInterval(adz_interval);
								    	repeat_adz = 'no';
								    }
								}, 1000);
							<?php }	?>


						}else{
							//jQuery('body').show();
						}					
			        },
			        error: function(errorThrown){
			            console.log(errorThrown);
			        }
			    });
			}

			jQuery(document).on('click','.close_adz',function(){
				jQuery.ajax({
			        url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
			        type : 'post',
			        data: {
			            action :'adz_get_advertise_content',
			            target_type : '<?php echo $target_type;?>',
			            target : '<?php echo $target; ?>',
			            ad_visibility_interval : '<?php echo $ad_visibility_interval; ?>',
			            repeat_times : '<?php echo $repeat_times;?>',
			            close_adz : 'true',
			            adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' );?>'


			        },
			        success:function(data) {
			        	if(jQuery.trim(data) != 'continue'){
			        		repeat_adz = 'yes';
							location.reload();
						}					
			        },
			        error: function(errorThrown){
			            console.log(errorThrown);
			        }
			    });
				
			});
		});

	</script>
	<?php

}
function adz_get_advertise_popup($ad_visibility,$ad_visibility_interval,$repeat_times,$target_type,$target,$ad_to_serve,$rotations_id,$sequence,$display_type,$adz_template){

	// See adz_get_advertise(): the original $_SESSION throttle never ran.
	if ( ! adz_visitor_in_grace_period() ) {
		adz_visitor_start_grace_period( $ad_visibility_interval );
	}
	?>
	<div id="show_overlay" class="adz_overlay">
		<div class="adz_popup">
							
			<div class="content"> </div>			
		</div>
	</div>

	<script type="text/javascript">
	jQuery(document).ready(function(){	

		var repeat_adz = 'yes';
		var interval = setInterval(function () {
			if(repeat_adz == 'yes'){
				browsing_advertise();
			}
			    
		}, 60000);
		function browsing_advertise(){
			
			jQuery.ajax({
		        url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
		        type : 'post',
		        data: {
		            action :'adz_get_advertise_content',
		            target_type : '<?php echo $target_type; ?>',
		            target : '<?php echo $target; ?>',
		            ad_visibility_interval : '<?php echo $ad_visibility_interval; ?>',
		            ad_to_serve : '<?php echo $ad_to_serve;?>',
		            rotations_id : '<?php echo $rotations_id;?>',
		            sequence : '<?php echo $sequence;?>',
		            display_type : '<?php echo $display_type;?>',
		            adz_template : '<?php echo $adz_template;?>',
		            repeat_times : '<?php echo $repeat_times;?>',		          
		            show_popup : 'true',
		            adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' );?>'

		        },
		        success:function(data) {
		        
		        	if(jQuery.trim(data) != 'continue'){
		        		jQuery('#show_overlay').css('visibility','visible');
						jQuery('#show_overlay').css('opacity','1');
		        		jQuery('.adz_popup .content').html(window.atob(data));
						

						var i = <?php echo $ad_visibility;?>;
						var adz_interval = setInterval(function () {
						    if (i >= 0) {
						    	jQuery('.adz_timer').text(" "+i+" ");
						        i--;
						    } else {
						    	jQuery('.continue_reading').text('Continue >>');
						    	jQuery('.continue_reading').addClass('close_adz');	
						    	jQuery('.continue_reading').css('cusrsor','pointer');						       
						    	clearInterval(adz_interval);
						    	repeat_adz = 'no';
						    }
						}, 1000);
					}					
		        },
		        error: function(errorThrown){
		            console.log(errorThrown);
		        }
		    });
			
		}	
		
		jQuery(document).on('click','.close_adz',function(){
			jQuery.ajax({
		        url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
		        type : 'post',
		        data: {
		            action :'adz_get_advertise_content',
		            target_type : '<?php echo $target_type;?>',
		            target : '<?php echo $target; ?>',
		            ad_visibility_interval : '<?php echo $ad_visibility_interval; ?>',
		            repeat_times : '<?php echo $repeat_times;?>',
		            close_adz : 'true',
		            show_popup : 'true',
		            adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' );?>' 
		        },
		        success:function(data) {
		        	jQuery('#show_overlay').css('visibility','hidden');
			 		jQuery('#show_overlay').css('opacity','0');	
			 		repeat_adz = 'yes';			
		        },
		        error: function(errorThrown){
		            console.log(errorThrown);
		        }
		    });
			 
			 
		});
	});
	
	</script>
	<?php

}

## This Function is use to get publishers adz.
function adz_get_publisher_adz(){

	/*
	 * 1.0.8 returned each ad's network_ad_id -- the ID the adz.world server
	 * assigned. With no server that meta is empty, so this returned ",,,,"
	 * and no ad was ever addressable. Local post IDs are the identity now.
	 */
	$publisher_adz_ids = '';

	foreach ( adz_get_all_ad_ids() as $ad_id ) {
		$publisher_adz_ids .= $ad_id . ',';
	}

	return $publisher_adz_ids;

}// End of the function get_publisher_adz

/* This Function Return the network adz id */
function adz_get_network_id($adz_ids){

	/*
	 * Kept for call-site compatibility. Resolves each reference to a local post
	 * ID, accepting legacy network_ad_id values from sites that were once
	 * registered with adz.world.
	 */
	$ids = array();

	foreach ( (array) $adz_ids as $adz ) {
		$resolved = adz_resolve_ad_id( $adz );

		if ( $resolved ) {
			$ids[] = $resolved;
		}
	}

	return implode( ',', $ids );

}// End of the function get_network_id

/* This Function is user check Visitor is logged in or not on adz.world */
function adz_world_logged_in(){

	/*
	 * 1.0.8 asked adz.world whether this visitor's IP belonged to a logged-in
	 * network member. Without that network, identity is this site's own.
	 */
	return is_user_logged_in() ? get_current_user_id() : false;

}// End of the function.

/* This function is use to check roatation completed or not. */
function adz_check_roatation_completed_or_not( $rotation ){
	$rotation_adz_pool = get_option($rotation['rotation_id']);
	if( rtrim($rotation['sequence'],",") == '' ){
		return "network";
	}
	
	if( !$rotation_adz_pool ){
		$rotation_sequence_array = explode(',', $rotation['sequence']);
		$return = array_shift($rotation_sequence_array);		
		
	}elseif( !empty($rotation_adz_pool['un_served']) ){
		
		$return = array_shift($rotation_adz_pool['un_served']);
		
	}else{	
		$return = false;
	}
	return $return;
}// End of function check_roatation_completed_or_not