<?php
/**
 * Legacy full-page and AJAX advertising template.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$template_name = basename( __FILE__ );
$setting_data  = get_option( 'template_' . $template_name );
$setting_array = is_array( $setting_data ) ? $setting_data : ( is_string( $setting_data ) ? unserialize( $setting_data, array( 'allowed_classes' => false ) ) : array() );
$setting_array = wp_parse_args(
	is_array( $setting_array ) ? $setting_array : array(),
	array(
		'header_text'  => '',
		'footer_text'  => '',
		'header_style' => 'center',
		'footer_style' => 'center',
	)
);
foreach ( array( 'header_style', 'footer_style' ) as $adzworld_style_key ) {
	$setting_array[ $adzworld_style_key ] = in_array( $setting_array[ $adzworld_style_key ], array( 'left', 'right', 'center' ), true )
		? $setting_array[ $adzworld_style_key ] : 'center';
}

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

if ( 'thru_page' === $display_type ) {

	?>
	<head>
	<?php
	wp_enqueue_script( 'jquery' );
	wp_print_scripts( 'jquery' );
	?>
	<script type="text/javascript">
		jQuery(document).ready(function(){

			var i = <?php echo absint( $ad_visibility ); ?>;
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
						target : '<?php echo esc_js( $target ); ?>',
						ad_visibility_interval : '<?php echo esc_js( $ad_visibility_interval ); ?>',
						repeat_times : '<?php echo esc_js( $repeat_times ); ?>',
						close_adz : 'true',
						rotations_id: '<?php echo esc_js( $rotation_id ); ?>',
						adz_nonce: '<?php echo esc_js( wp_create_nonce( 'adzdotworld' ) ); ?>',
						adzworld_context: '<?php echo esc_js( adzworld_ad_request_token( $target_type, $target, $ad_visibility_interval, $repeat_times, '', $rotation_id, '', '', '', 'true' ) ); ?>',

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
	<?php echo wp_kses( $style, array( 'style' => array() ) ); ?>
	</head>
	<!-- BEGIN: Sticky Header -->
	<div id="adz_header_container">
		<div id="adz_header_content" style="text-align: <?php echo esc_attr( $setting_array['header_style'] ); ?>" >
			<a href="#" class="adz_header_text"><div class="continue_reading"><?php echo esc_html( $setting_array['header_text'] ); ?> <div> <span class="adz_timer" > </span> seconds</div></div></a>
		</div>
	</div>
	<!-- END: Sticky Header -->

	<!-- BEGIN: Page Content -->
	<div id="adz_container">
		<div id="content">
			<?php

			echo wp_kses( adzworld_ad_content( $ad_text ), adzworld_ad_allowed_html() );
			?>
		</div>
	</div>
	<!-- END: Page Content -->

	<!-- BEGIN: Sticky Footer -->
	<div id="adz_footer_container">
		<div id="adz_footer_content" style="text-align: <?php echo esc_attr( $setting_array['footer_style'] ); ?>">
			<a href="#" class="adz_footer_text"><p class="continue_reading"><?php echo esc_html( $setting_array['footer_text'] ); ?> <span class="adz_timer"> </span> seconds.</p></a>
		</div>
	</div>

	<?php

} elseif ( strpos( $ad_text, 'adzworld_iframe' ) !== false ) {


		echo esc_html(
			base64_encode(
				'<!-- BEGIN: Sticky Header -->
		<div id="adz_header_container">
		    <div id="adz_header_content" style="text-align: ' . esc_attr( $setting_array['header_style'] ) . '">
		        <a href="#" class="adz_header_text"><div class="continue_reading">' . esc_html( $setting_array['header_text'] ) . ' <div><span class="adz_timer" > </span> seconds</div></div></a>
		    </div>
		</div>
		<!-- END: Sticky Header -->

		<!-- BEGIN: Page Content -->
		<div id="adz_container">
		    <div id="content">
		        ' . adzworld_ad_content( $ad_text ) . '
		    </div>
		</div>
		<!-- END: Page Content -->

		<!-- BEGIN: Sticky Footer -->
		<div id="adz_footer_container">
		    <div id="adz_footer_content" style="text-align: ' . esc_attr( $setting_array['footer_style'] ) . '">
		       <a href="#" class="adz_footer_text"><p class="continue_reading">' . esc_html( $setting_array['footer_text'] ) . ' <span class="adz_timer"> </span> seconds.</p></a>
		    </div>
		</div>
		' . $style
			)
		);

} else {

	echo esc_html(
		base64_encode(
			'<!-- BEGIN: Sticky Header -->
		<div id="adz_header_container">
		    <div id="adz_header_content" style="text-align: ' . esc_attr( $setting_array['header_style'] ) . '">
		        <a href="#" class="adz_header_text"><div class="continue_reading">' . esc_html( $setting_array['header_text'] ) . ' <div> <span class="adz_timer" > </span> seconds</div></div></a>
		    </div>
		</div>
		<!-- END: Sticky Header -->

		<!-- BEGIN: Page Content -->
		<div id="adz_container">
		    <div id="content">
		        ' . adzworld_ad_content( $ad_text ) . '
		    </div>
		</div>
		<!-- END: Page Content -->

		<!-- BEGIN: Sticky Footer -->
		<div id="adz_footer_container">
		    <div id="adz_footer_content" style="text-align: ' . esc_attr( $setting_array['footer_style'] ) . '">
		        <a href="#" class="adz_footer_text"><p class="continue_reading">' . esc_html( $setting_array['footer_text'] ) . '  <span class="adz_timer"> </span> seconds.</p></a>
		    </div>
		</div>
		' . $style
		)
	);
}