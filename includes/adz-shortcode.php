<?php


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

add_shortcode( 'Adzworld', 'adz_shortcode' );
function adz_shortcode( $atts, $content = null ) {
	global $wpdb;

	$attributes = shortcode_atts( array(
		'view_more_text' => 'view adz to read more',
		'adz_duration' => 10,
		'adz_interval_sec' => 20,
		'adz_display' => 'popup',
		'adz_sequence' => '',
		'adz_template' => 'template1.php',
	), $atts );

	
	$more_text = $attributes['view_more_text'];
	$adz_duration = $attributes['adz_duration'];
	$adz_interval_sec = $attributes['adz_interval_sec'];
	$adz_display = $attributes['adz_display'];
	$adz_template = $attributes['adz_template'];
	
	if(trim($attributes['adz_sequence']) == ''){
		$adz_sequence = rtrim(get_publisher_adz(),',');
	}else{
		$adz_sequence = $attributes['adz_sequence'];
	}

	$visitor_ip = $_SERVER['REMOTE_ADDR'];

	$adz_views = $wpdb->get_row("SELECT *  FROM ".$wpdb->prefix .'adz_views'." WHERE visitor_ip = '".$visitor_ip."' AND `targate_id` = '".get_the_ID()."' AND target_type = 'shortcode' AND ad_date='".date('Y-m-d')."'",OBJECT);

	$adz_to_serve = adz_check_shortcode_roatation_completed_or_not($adz_sequence);
	
	if($adz_to_serve){
		if(empty($adz_views)){	
			
			$output  = '<div id="content_after_ad"><a id="show_adz" href="javascript:void(0)">' . $more_text . '</a><div>';
			if($adz_display == 'page'){
				adz_in_page($adz_interval_sec,$adz_duration);
			}else{
				adz_in_popup($adz_interval_sec,$adz_duration,$adz_sequence,$adz_template,$adz_display,$adz_to_serve,$content);
			}
			
		}else{

			$last_update = strtotime($adz_views->updated);
			$current_time = time();
			$seconds = $current_time-$last_update;
			$interval_in_sec = $adz_interval_sec;

			if($seconds >= $interval_in_sec){
				$output  = '<div id="content_after_ad"><a id="show_adz" href="javascript:void(0)">' . $more_text . '</a><div>';
				if($adz_display == 'page'){
					adz_in_page($adz_interval_min,$adz_duration);
				}elseif($adz_display == 'popup'){
					adz_in_popup($adz_interval_min,$adz_duration,$adz_sequence,$adz_template,$adz_display,$adz_to_serve,$content);
				}
			}else{
				$output =  $content;
			}
		}
	}
	
		

	return $output;
} 

function adz_in_page($adz_interval_min,$adz_duration){
	?>
	<script type="text/javascript">
	jQuery(document).ready(function(){

		jQuery('#show_adz').click(function(){
			jQuery.ajax({
		        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
		        type : 'post',
		        data: {
		            action :'adz_get_advertise_content',
		            target_type : 'shortcode',
		            target : '<?php echo get_the_ID(); ?>',
		            ad_visibility_interval : '<?php echo $adz_interval_min; ?>',
		            repeat_times : 'infinite',
		            adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' );?>'
		        },
		        success:function(data) {
		        	if(data != 'continue'){
						jQuery('.adz-world-container').html(data);
						//jQuery('body').show();

						var i = <?php echo $adz_duration;?>;
						var adz_interval = setInterval(function () {
						    if (i >= 0) {
						    	jQuery('.adz_timer').text(" "+i+" ");
						        i--;
						    } else {
						    	jQuery('.continue_reading').text('Continue reading >>');
						    	jQuery('.continue_reading').addClass('close_adz');	
						    	jQuery('.continue_reading').css('cusrsor','pointer');						       
						    	clearInterval(adz_interval);
						    }
						}, 1000);

						
						
					}else{
						jQuery('body').show();
					}					
		        },
		        error: function(errorThrown){
		            console.log(errorThrown);
		        }
		    });
		});

		jQuery(document).on('click','.close_adz_button',function(){
			jQuery.ajax({
		        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
		        type : 'post',
		        data: {
		            action :'adz_get_advertise_content',
		            target_type : 'shortcode',
		            target : '<?php echo get_the_ID(); ?>',
		            ad_visibility_interval : '<?php echo $adz_interval_min; ?>',
		            repeat_times : 'infinite',
		            close_adz : 'true',
			        adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' );?>'

		        },
		        success:function(data) {
		        	if(data != 'continue'){
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
function adz_in_popup($adz_interval_min,$adz_duration,$adz_sequence,$adz_template,$adz_display,$adz_to_serve,$content){
	?>
	<div id="show_overlay" class="adz_overlay">
		<div class="adz_popup">
							
			
			<div class="content">
			
			</div>
			
		</div>
	</div>
	<script type="text/javascript">
	jQuery(document).ready(function(){
		var content_after_ad = '<?php echo base64_encode($content);?>';
		jQuery('#show_adz').click(function(){
			jQuery('.adz_popup .close_adz').hide();
			jQuery.ajax({
		        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
		        type : 'post',
		        data: {
		            action :'adz_get_advertise_content',
		            target_type : 'shortcode',
		            target : '<?php echo get_the_ID(); ?>',
		            ad_visibility_interval : '<?php echo $adz_interval_min; ?>',
		            sequence : '<?php echo $adz_sequence; ?>',
		            display_type : '<?php echo $adz_display; ?>',
		            adz_template : '<?php echo $adz_template; ?>',
		            ad_to_serve : '<?php echo $adz_to_serve; ?>',
		            repeat_times : 'infinite',
		            show_popup : 'true',
		            adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' );?>'
		        },
		        success:function(data) {
		        	if(data != 'continue'){
		        		jQuery('#show_overlay').css('visibility','visible');
						jQuery('#show_overlay').css('opacity','1');
						jQuery('.adz_popup .content').html(window.atob(data));
						var i = <?php echo $adz_duration;?>;
						var adz_interval = setInterval(function () {
						    if (i >= 0) {
						    	jQuery('.adz_timer').text(" "+i+" ");
						        i--;
						    } else {
						    	jQuery('.continue_reading').text('Continue reading >>');
						    	jQuery('.continue_reading').addClass('close_adz');	
						    	jQuery('.continue_reading').css('cusrsor','pointer');						       
						    	clearInterval(adz_interval);
						    }
						}, 1000);

							
					}					
		        },
		        error: function(errorThrown){
		            console.log(errorThrown);
		        }
		    });
			
		});

		jQuery(document).on('click','.close_adz',function(){
			jQuery.ajax({
		        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
		        type : 'post',
		        data: {
		            action :'adz_get_advertise_content',
		            target_type : 'shortcode',
		            target : '<?php echo get_the_ID(); ?>',
		            ad_visibility_interval : '<?php echo $adz_interval_min; ?>',
		            repeat_times : 'infinite',
		            close_adz : 'true',
		            show_popup : 'true',
		            adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' );?>'

		        },
		        success:function(data) {
		        	jQuery('#show_overlay').css('visibility','hidden');
			 		jQuery('#show_overlay').css('opacity','0');						
		        },
		        error: function(errorThrown){
		            console.log(errorThrown);
		        }
		    });
			jQuery('#content_after_ad').html(window.atob(content_after_ad));
		});
	});
		
	</script>
	<?php
}

function adz_check_shortcode_roatation_completed_or_not($sequence){

	$publisher_rotations_stats = get_post_meta(get_the_ID(),'adz_rotation_shortcode',true);

	if(empty($publisher_rotations_stats['un_served'])){
		delete_post_meta(get_the_ID(), 'adz_rotation_shortcode');
	}
	$publisher_rotations_stats = get_post_meta(get_the_ID(),'adz_rotation_shortcode',true);
	if(!$publisher_rotations_stats){
		$sequence_array = explode(',', $sequence);
		$return = array_shift($sequence_array);		
		
	}elseif(!empty($publisher_rotations_stats['un_served'])){
		
		$return = array_shift($publisher_rotations_stats['un_served']);
		
	}else{	
		$return = false;
	}
	return $return;
}
?>