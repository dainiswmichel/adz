<?php
/**
 * Legacy full-page and AJAX advertising template.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$adzworld_template_name = basename( __FILE__ );
$adzworld_setting_data  = get_option( 'template_' . $adzworld_template_name );
$adzworld_setting_array = adzworld_template_settings( $adzworld_setting_data );
$adzworld_setting_array = wp_parse_args(
	is_array( $adzworld_setting_array ) ? $adzworld_setting_array : array(),
	array(
		'header_text'  => '',
		'footer_text'  => '',
		'header_style' => 'center',
		'footer_style' => 'center',
	)
);
foreach ( array( 'header_style', 'footer_style' ) as $adzworld_style_key ) {
	$adzworld_setting_array[ $adzworld_style_key ] = in_array( $adzworld_setting_array[ $adzworld_style_key ], array( 'left', 'right', 'center' ), true )
		? $adzworld_setting_array[ $adzworld_style_key ] : 'center';
}

if ( 'thru_page' === $display_type ) {

	?>
	<head>
	<?php
	wp_enqueue_style( 'adz_css' );
	wp_print_styles( 'adz_css' );
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
					clearInterval(adz_interval);
				}
			}, 1000);

			jQuery(document).on('click','.close_adz',function(){
				jQuery.ajax({
					url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
					type : 'post',
					data: {
						action :'adzworld_get_advertise_content',
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
	</head>
	<!-- BEGIN: Sticky Header -->
	<div id="adz_header_container">
		<div id="adz_header_content" class="adzworld-align-<?php echo esc_attr( $adzworld_setting_array['header_style'] ); ?>">
			<a href="#" class="adz_header_text"><div class="continue_reading"><?php echo esc_html( $adzworld_setting_array['header_text'] ); ?> <div> <span class="adz_timer" > </span> seconds</div></div></a>
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
		<div id="adz_footer_content" class="adzworld-align-<?php echo esc_attr( $adzworld_setting_array['footer_style'] ); ?>">
			<a href="#" class="adz_footer_text"><p class="continue_reading"><?php echo esc_html( $adzworld_setting_array['footer_text'] ); ?> <span class="adz_timer"> </span> seconds.</p></a>
		</div>
	</div>

	<?php

} else {
	$adzworld_markup = '<!-- BEGIN: Sticky Header -->
		<div id="adz_header_container">
		    <div id="adz_header_content" class="adzworld-align-' . esc_attr( $adzworld_setting_array['header_style'] ) . '">
		        <a href="#" class="adz_header_text"><div class="continue_reading">' . esc_html( $adzworld_setting_array['header_text'] ) . ' <div><span class="adz_timer"> </span> seconds</div></div></a>
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
		    <div id="adz_footer_content" class="adzworld-align-' . esc_attr( $adzworld_setting_array['footer_style'] ) . '">
		        <a href="#" class="adz_footer_text"><p class="continue_reading">' . esc_html( $adzworld_setting_array['footer_text'] ) . ' <span class="adz_timer"> </span> seconds.</p></a>
		    </div>
		</div>
		';

	echo wp_kses( $adzworld_markup, adzworld_ad_allowed_html() );
}
