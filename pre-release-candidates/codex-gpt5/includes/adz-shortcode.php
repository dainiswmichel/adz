<?php
/**
 * Legacy iframe and gated-content shortcodes.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


add_shortcode( 'adzworld_iframe', 'adz_iframe_shortcode' );
/**
 * Render an escaped, configured iframe.
 *
 * @param array $atts Shortcode attributes.
 * @return string Iframe markup.
 */
function adz_iframe_shortcode( $atts ) {

	$attributes = shortcode_atts(
		array(
			'adz_url' => 'https://adz-world.com',
			'height'  => 600,
			'width'   => '100%',
		),
		$atts
	);

	/*
	 * WordPress echoes whatever a shortcode returns, so everything in it is
	 * escaped here rather than trusted. 1.0.8 concatenated the raw attributes
	 * straight into the markup.
	 */
	$width = trim( (string) $attributes['width'] );
	$width = preg_match( '/^[0-9]+%?$/', $width ) ? $width : '100%';

	return sprintf(
		'<iframe src="%s" height="%d" width="%s"></iframe>',
		esc_url( $attributes['adz_url'] ),
		absint( $attributes['height'] ),
		esc_attr( $width )
	);
}

add_shortcode( 'Adzworld', 'adz_shortcode' );
/**
 * Gate enclosed content behind the selected local or connected-network ad.
 *
 * @param array       $atts Shortcode attributes.
 * @param string|null $content Enclosed content.
 * @return string Content and its request controls.
 */
function adz_shortcode( $atts, $content = null ) {

	$attributes = shortcode_atts(
		array(
			'view_more_text'   => 'view adz to read more',
			'adz_duration'     => 10,
			'adz_interval_sec' => 20,
			'adz_display'      => 'popup',
			'adz_sequence'     => '',
			'adz_template'     => 'template1.php',
		),
		$atts
	);

	$more_text        = $attributes['view_more_text'];
	$adz_duration     = absint( $attributes['adz_duration'] );
	$adz_interval_sec = absint( $attributes['adz_interval_sec'] );
	$adz_display      = $attributes['adz_display'];
	$adz_template     = $attributes['adz_template'];

	if ( '' === trim( $attributes['adz_sequence'] ) ) {
		$adz_sequence = rtrim( adz_get_publisher_adz(), ',' );
	} else {
		$adz_sequence = $attributes['adz_sequence'];
	}

	$output     = $content;
	$visitor_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	$adz_views = adz_get_view_row( $visitor_ip, (int) get_the_ID(), 'shortcode' );

	$adz_to_serve = adz_check_shortcode_roatation_completed_or_not( $adz_sequence );

	ob_start();
	if ( $adz_to_serve ) {
		if ( empty( $adz_views ) ) {

			$output = '<div id="content_after_ad"><a id="show_adz" href="javascript:void(0)">' . esc_html( $more_text ) . '</a></div>';
			if ( 'page' === $adz_display ) {
				adz_in_page( $adz_interval_sec, $adz_duration, $adz_sequence, $adz_template, $adz_to_serve );
			} else {
				adz_in_popup( $adz_interval_sec, $adz_duration, $adz_sequence, $adz_template, $adz_display, $adz_to_serve, $content );
			}
		} else {

			$last_update     = strtotime( $adz_views->updated );
			$current_time    = time();
			$seconds         = $current_time - $last_update;
			$interval_in_sec = $adz_interval_sec;

			if ( $seconds >= $interval_in_sec ) {
				$output = '<div id="content_after_ad"><a id="show_adz" href="javascript:void(0)">' . esc_html( $more_text ) . '</a></div>';
				if ( 'page' === $adz_display ) {
					adz_in_page( $adz_interval_sec, $adz_duration, $adz_sequence, $adz_template, $adz_to_serve );
				} elseif ( 'popup' === $adz_display ) {
					adz_in_popup( $adz_interval_sec, $adz_duration, $adz_sequence, $adz_template, $adz_display, $adz_to_serve, $content );
				}
			} else {
				$output = $content;
			}
		}
	}

	return $output . ob_get_clean();
}

/**
 * Render inline gated-ad controls.
 *
 * @param int    $adz_interval_min Minimum interval in seconds (legacy name).
 * @param int    $adz_duration Countdown seconds.
 * @param string $adz_sequence Configured sequence.
 * @param string $adz_template Template filename.
 * @param mixed  $adz_to_serve Selected ad identifier.
 */
function adz_in_page( $adz_interval_min, $adz_duration, $adz_sequence = '', $adz_template = 'template1.php', $adz_to_serve = '' ) {
	?>
	<script type="text/javascript">
	jQuery(document).ready(function(){

		jQuery('#show_adz').click(function(){
			jQuery.ajax({
				url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
				type : 'post',
				data: {
					action :'adz_get_advertise_content',
					target_type : 'shortcode',
					target : '<?php echo absint( get_the_ID() ); ?>',
					ad_visibility_interval : '<?php echo esc_js( $adz_interval_min ); ?>',
					repeat_times : 'infinite',
					ad_to_serve : '<?php echo esc_js( $adz_to_serve ); ?>',
					sequence : '<?php echo esc_js( $adz_sequence ); ?>',
					display_type : 'page',
					adz_template : '<?php echo esc_js( $adz_template ); ?>',
					adzworld_context: '<?php echo esc_js( adzworld_ad_request_token( 'shortcode', get_the_ID(), $adz_interval_min, 'infinite', $adz_to_serve, '', $adz_sequence, 'page', $adz_template ) ); ?>',
					adz_nonce: '<?php echo esc_js( wp_create_nonce( 'adzdotworld' ) ); ?>'
				},
				success:function(data) {
					if(data != 'continue'){
						jQuery('#content_after_ad').html(window.adzworldDecodeAd(data));
						//jQuery('body').show();

						var i = <?php echo absint( $adz_duration ); ?>;
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

		jQuery(document).on('click','.close_adz',function(){
			jQuery.ajax({
				url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
				type : 'post',
				data: {
					action :'adz_get_advertise_content',
					target_type : 'shortcode',
					target : '<?php echo absint( get_the_ID() ); ?>',
					ad_visibility_interval : '<?php echo esc_js( $adz_interval_min ); ?>',
					repeat_times : 'infinite',
					close_adz : 'true',
					adzworld_context: '<?php echo esc_js( adzworld_ad_request_token( 'shortcode', get_the_ID(), $adz_interval_min, 'infinite', '', '', '', '', '', 'true' ) ); ?>',
					adz_nonce: '<?php echo esc_js( wp_create_nonce( 'adzdotworld' ) ); ?>'

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
/**
 * Render popup gated-ad controls and the enclosed content.
 *
 * @param int    $adz_interval_min Minimum interval in seconds (legacy name).
 * @param int    $adz_duration Countdown seconds.
 * @param string $adz_sequence Configured sequence.
 * @param string $adz_template Template filename.
 * @param string $adz_display Presentation kind.
 * @param mixed  $adz_to_serve Selected ad identifier.
 * @param string $content Enclosed content.
 */
function adz_in_popup( $adz_interval_min, $adz_duration, $adz_sequence, $adz_template, $adz_display, $adz_to_serve, $content ) {
	?>
	<div id="show_overlay" class="adz_overlay">
		<div class="adz_popup">
							
			
			<div class="content">
			
			</div>
			
		</div>
	</div>
	<script type="text/javascript">
	jQuery(document).ready(function(){
		var content_after_ad = <?php echo wp_json_encode( (string) $content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
		jQuery('#show_adz').click(function(){
			jQuery('.adz_popup .close_adz').hide();
			jQuery.ajax({
				url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
				type : 'post',
				data: {
					action :'adz_get_advertise_content',
					target_type : 'shortcode',
					target : '<?php echo absint( get_the_ID() ); ?>',
					ad_visibility_interval : '<?php echo esc_js( $adz_interval_min ); ?>',
					sequence : '<?php echo esc_js( $adz_sequence ); ?>',
					display_type : '<?php echo esc_js( $adz_display ); ?>',
					adz_template : '<?php echo esc_js( $adz_template ); ?>',
					ad_to_serve : '<?php echo esc_js( $adz_to_serve ); ?>',
					repeat_times : 'infinite',
					show_popup : 'true',
					adzworld_context: '<?php echo esc_js( adzworld_ad_request_token( 'shortcode', get_the_ID(), $adz_interval_min, 'infinite', $adz_to_serve, '', $adz_sequence, $adz_display, $adz_template ) ); ?>',
					adz_nonce: '<?php echo esc_js( wp_create_nonce( 'adzdotworld' ) ); ?>'
				},
				success:function(data) {
					if(data != 'continue'){
						jQuery('#show_overlay').css('visibility','visible');
						jQuery('#show_overlay').css('opacity','1');
						jQuery('.adz_popup .content').html(window.adzworldDecodeAd(data));
						var i = <?php echo absint( $adz_duration ); ?>;
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
				url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
				type : 'post',
				data: {
					action :'adz_get_advertise_content',
					target_type : 'shortcode',
					target : '<?php echo absint( get_the_ID() ); ?>',
					ad_visibility_interval : '<?php echo esc_js( $adz_interval_min ); ?>',
					repeat_times : 'infinite',
					close_adz : 'true',
					adzworld_context: '<?php echo esc_js( adzworld_ad_request_token( 'shortcode', get_the_ID(), $adz_interval_min, 'infinite', '', '', '', '', '', 'true' ) ); ?>',
					show_popup : 'true',
					adz_nonce: '<?php echo esc_js( wp_create_nonce( 'adzdotworld' ) ); ?>'

				},
				success:function(data) {
					jQuery('#show_overlay').css('visibility','hidden');
					jQuery('#show_overlay').css('opacity','0');                        
				},
				error: function(errorThrown){
					console.log(errorThrown);
				}
			});
			jQuery('#content_after_ad').html(content_after_ad);
		});
	});
		
	</script>
	<?php
}

/**
 * Select the next ad for the current shortcode's rotation.
 *
 * @param string $sequence Comma-separated ad identifiers.
 * @return string|false Selected identifier, or false when complete.
 */
function adz_check_shortcode_roatation_completed_or_not( $sequence ) {

	$publisher_rotations_stats = get_post_meta( get_the_ID(), 'adz_rotation_shortcode', true );

	if ( empty( $publisher_rotations_stats['un_served'] ) ) {
		delete_post_meta( get_the_ID(), 'adz_rotation_shortcode' );
	}
	$publisher_rotations_stats = get_post_meta( get_the_ID(), 'adz_rotation_shortcode', true );
	if ( ! $publisher_rotations_stats ) {
		$sequence_array = explode( ',', $sequence );
		$return         = array_shift( $sequence_array );

	} elseif ( ! empty( $publisher_rotations_stats['un_served'] ) ) {

		$return = array_shift( $publisher_rotations_stats['un_served'] );

	} else {
		$return = false;
	}
	return $return;
}
?>