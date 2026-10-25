<?php
function role_exists( $role ) {

  if( ! empty( $role ) ) {
    return $GLOBALS['wp_roles']->is_role( $role );
  }
  
  return false;
}

function adz_get_advertise($ad_visibility,$ad_visibility_interval,$repeat_times,$target_type,$target,$ad_to_serve,$rotations_id,$sequence,$display_type,$adz_template){
	
	if( !isset($_SESSION['first_time']) && @$_SESSION['first_time'] == '' ){
		$_SESSION['first_time'] = time()+$ad_visibility_interval;
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
			        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
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
			        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
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

	if(!isset($_SESSION['first_time']) && $_SESSION['first_time'] == ''){
		$_SESSION['first_time'] = time()+$ad_visibility_interval;	

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
		        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
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
		        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
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

	$args = array(
		'posts_per_page'   => -1,	
		'post_type'        => 'adz_ad',	
		'post_status'      => 'publish',
	);
	$posts_array = get_posts( $args ); 
	$publisher_adz_ids = '';
	if(!empty($posts_array)){
		foreach ($posts_array as $posts) {

			$publisher_adz_ids .= get_post_meta($posts->ID,"network_ad_id",true).',';
		}
	}

	return $publisher_adz_ids;

}// End of the function get_publisher_adz

/* This Function Return the network adz id */
function adz_get_network_id($adz_ids){
	$network_ids = '';
	if(!empty($adz_ids)){
		foreach ($adz_ids as $adz) {
			$network_ids .= get_post_meta($adz,"network_ad_id",true).',';
		}
	}
	return rtrim($network_ids);

}// End of the function get_network_id

/* This Function is user check Visitor is logged in or not on adz.world */
function adz_world_logged_in(){
	
	global $adz_ad_network_base_url;
	$ad_network_url = $adz_ad_network_base_url."wp-json/adz_server/v1/ads/is_visitor_logged?visitor_ip={$_SERVER['REMOTE_ADDR']}";
	$args = array();	
	$response = wp_remote_get( $ad_network_url , $args );
	$body = wp_remote_retrieve_body($response);
	$json_repsonse = json_decode($body);

	if($json_repsonse->status == 'logged_in'){
		return $json_repsonse->user_id;
	}else{
		return false;
	}

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