<?php 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.
/*Dynamic template for displaying adz.*/
$template_name = basename( __FILE__ );

/*
 * 1.0.8 called unserialize() on an option value here. The matching explicit
 * serialize() on the settings screen has been removed, and this reader returns
 * a sanitised array, tolerating the legacy stored form without feeding an
 * option value to unserialize().
 */
$setting_array = adz_get_template_settings_array( $template_name );

/*
 * Ad creative arrives from the ad source. It is markup, so it is filtered
 * rather than escaped, and the allowed set is what an ad legitimately needs.
 */
$adz_allowed_html = array(
	'a'      => array( 'href' => true, 'title' => true, 'target' => true, 'rel' => true, 'class' => true ),
	'img'    => array( 'src' => true, 'alt' => true, 'width' => true, 'height' => true, 'class' => true, 'loading' => true ),
	'p'      => array( 'class' => true ),
	'br'     => array(),
	'strong' => array(),
	'em'     => array(),
	'span'   => array( 'class' => true ),
	'div'    => array( 'class' => true, 'id' => true ),
	'h1'     => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(),
	'ul'     => array(), 'ol' => array(), 'li' => array(),
	'iframe' => array( 'src' => true, 'width' => true, 'height' => true, 'frameborder' => true, 'title' => true, 'allow' => true ),
);
$ad_text_safe = wp_kses( (string) $ad_text, $adz_allowed_html );

$style = '<style>		
			#adz_header_container { background:#4A7354; border:1px solid #104A1F;  left:0; position:fixed; width:100%; top:0; }		
			#adz_header_content{  margin:0 auto; width:100%; text-align:center; }
			#adz_container { margin:0 auto; overflow:auto; padding:80px 0; text-align:center; }
			#adz_footer_container { background:#4A7354; border:1px solid #104A1F; bottom:0; height:60px; left:0; position:fixed; width:100%; }
			#adz_footer_content { line-height:60px; margin:0 auto; width:100%; text-align:center !important; }
			.adz_popup #adz_header_container,.adz-world-container #adz_header_container, .adz-world-category-wapper #adz_header_container{ position:inherit;  }
			.adz_popup #adz_footer_container,.adz-world-container #adz_footer_container, .adz-world-category-wapper #adz_footer_container { position:inherit;  }
			.adz_header_text, .adz_footer_text{color:#fff;}
			.adz_timer{font-size:22px;}
			.adz_popup #adz_container { max-height: 600px;overflow-y: scroll; } 
	    </style>';

if($display_type == 'thru_page'){
	
?>
	<head>
	<script type="text/javascript" src="<?php echo site_url();?>/wp-includes/js/jquery/jquery.js"></script>
	<script type="text/javascript">
		jQuery(document).ready(function(){

			var i = <?php echo (int) $ad_visibility; ?>;
			var adz_interval = setInterval(function () {
			    if (i >= 0) {
			    	jQuery('.adz_timer').text(" "+i+" ");
			        i--;
			    } else {
			    	jQuery('.continue_reading').text('Continue >>');
			    	jQuery('.continue_reading').addClass('close_adz');	
			    	jQuery('.continue_reading').css('cusrsor','pointer');						       
			    	clearInterval(adz_interval);
			    }
			}, 1000);

			jQuery(document).on('click','.close_adz',function(){
				jQuery.ajax({
			        url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
			        type : 'post',
			        data: {
			            action :'adz_get_advertise_content',
			            target_type : '<?php echo esc_js( $target_type ); ?>',
			            target : '<?php echo (int) $target; ?>',
			            ad_visibility_interval : '<?php echo (int) $ad_visibility_interval; ?>',
			            repeat_times : '<?php echo esc_js( $repeat_times ); ?>',
			            close_adz : 'true',

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
	/*
	 * $style is the literal stylesheet defined at the top of this file. It
	 * interpolates nothing and carries no input, so it is emitted as-is.
	 */
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup defined in this file.
	echo $style;
	?>
	</head>
	<!-- BEGIN: Sticky Header -->
	<div id="adz_header_container">
	    <div id="adz_header_content" style="text-align: <?php echo esc_attr( $setting_array['header_style'] ); ?>" >
	        <a href="javascript:void()" class="adz_header_text"><div class="continue_reading"><?php echo wp_kses_post( $setting_array['header_text'] ); ?> <div> <span class="adz_timer" > </span> seconds</div></div></a>
	    </div>
	</div>
	<!-- END: Sticky Header -->

	<!-- BEGIN: Page Content -->
	<div id="adz_container">
	    <div id="content">
	        <?php

			if ( false !== strpos( (string) $ad_text, 'adzworld_iframe' ) ) {
				
				echo do_shortcode( $ad_text_safe ); 
			}else{
				
				echo $ad_text_safe; 
			}
			?>
	    </div>
	</div>
	<!-- END: Page Content -->

	<!-- BEGIN: Sticky Footer -->
	<div id="adz_footer_container">
	    <div id="adz_footer_content" style="text-align: <?php echo esc_attr( $setting_array['footer_style'] ); ?>">
	       <a href="javascript:void()" class="adz_footer_text"><p class="continue_reading"><?php echo wp_kses_post( $setting_array['footer_text'] ); ?> <span class="adz_timer"> </span> seconds.</p></a>
	    </div>
	</div>

<?php

}else{

	if ( false !== strpos( (string) $ad_text, 'adzworld_iframe' ) ) {

		echo base64_encode('<!-- BEGIN: Sticky Header -->
		<div id="adz_header_container">
		    <div id="adz_header_content" style="text-align: '.esc_attr( $setting_array['header_style'] ).'">
		        <a href="javascript:void()" class="adz_header_text"><div class="continue_reading">'.wp_kses_post( $setting_array['header_text'] ).' <div><span class="adz_timer" > </span> seconds</div></div></a>
		    </div>
		</div>
		<!-- END: Sticky Header -->

		<!-- BEGIN: Page Content -->
		<div id="adz_container">
		    <div id="content">
		        '.do_shortcode( $ad_text_safe ).'
		    </div>
		</div>
		<!-- END: Page Content -->

		<!-- BEGIN: Sticky Footer -->
		<div id="adz_footer_container">
		    <div id="adz_footer_content" style="text-align: '.esc_attr( $setting_array['footer_style'] ).'">
		       <a href="javascript:void()" class="adz_footer_text"><p class="continue_reading">'.wp_kses_post( $setting_array['footer_text'] ).' <span class="adz_timer"> </span> seconds.</p></a>
		    </div>
		</div>
		'.$style);

	}else{

		echo base64_encode('<!-- BEGIN: Sticky Header -->
		<div id="adz_header_container">
		    <div id="adz_header_content" style="text-align: '.esc_attr( $setting_array['header_style'] ).'">
		        <a href="javascript:void()" class="adz_header_text"><div class="continue_reading">'.wp_kses_post( $setting_array['header_text'] ).' <div> <span class="adz_timer" > </span> seconds</div></div></a>
		    </div>
		</div>
		<!-- END: Sticky Header -->

		<!-- BEGIN: Page Content -->
		<div id="adz_container">
		    <div id="content">
		        '.$ad_text_safe.'
		    </div>
		</div>
		<!-- END: Page Content -->

		<!-- BEGIN: Sticky Footer -->
		<div id="adz_footer_container">
		    <div id="adz_footer_content" style="text-align: '.esc_attr( $setting_array['footer_style'] ).'">
		        <a href="javascript:void()" class="adz_footer_text"><p class="continue_reading">'.wp_kses_post( $setting_array['footer_text'] ).'  <span class="adz_timer"> </span> seconds.</p></a>
		    </div>
		</div>
		'.$style);
	}
}
?>