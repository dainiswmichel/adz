<?php
/**
 * Dynamic template for displaying adz.
 *
 * Included from the ad server with the ad variables already in scope.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template_name = basename( __FILE__ );
$settings      = function_exists( 'adzworld_template_settings' )
	? adzworld_template_settings( $template_name )
	: array(
		'header_text'  => '',
		'header_style' => 'center',
		'footer_text'  => '',
		'footer_style' => 'center',
	);

$alignments   = array( 'left', 'right', 'center' );
$header_align = in_array( $settings['header_style'], $alignments, true ) ? $settings['header_style'] : 'center';
$footer_align = in_array( $settings['footer_style'], $alignments, true ) ? $settings['footer_style'] : 'center';
$header_text  = $settings['header_text'];
$footer_text  = $settings['footer_text'];
$seconds      = isset( $ad_visibility ) ? (int) $ad_visibility : 0;
$interval     = isset( $ad_visibility_interval ) ? (int) $ad_visibility_interval : 0;
$repeats      = isset( $repeat_times ) ? (int) $repeat_times : 0;
$target_attr  = isset( $target ) ? (string) $target : '';
$type_attr    = isset( $target_type ) ? (string) $target_type : '';
$ad_markup    = isset( $ad_text ) ? (string) $ad_text : '';
$uses_iframe  = false !== strpos( $ad_markup, 'adzworld_iframe' );
$ad_body      = $uses_iframe ? do_shortcode( $ad_markup ) : $ad_markup;
$ad_allowed   = function_exists( 'adzworld_ad_allowed_html' ) ? adzworld_ad_allowed_html() : array();

$style = '<style>
#adz_header_container { background:#4A7354; border:1px solid #104A1F; left:0; position:fixed; width:100%; top:0; }
#adz_header_content { margin:0 auto; width:100%; }
#adz_container { margin:0 auto; overflow:auto; padding:80px 0; text-align:center; }
#adz_footer_container { background:#4A7354; border:1px solid #104A1F; bottom:0; height:60px; left:0; position:fixed; width:100%; }
#adz_footer_content { line-height:60px; margin:0 auto; width:100%; }
.adz_popup #adz_header_container, .adz-world-container #adz_header_container, .adz-world-category-wapper #adz_header_container { position:inherit; }
.adz_popup #adz_footer_container, .adz-world-container #adz_footer_container, .adz-world-category-wapper #adz_footer_container { position:inherit; }
.adz_header_text, .adz_footer_text { color:#fff; }
.adz_timer { font-size:22px; }
.adz_popup #adz_container { max-height:600px; overflow-y:scroll; }
.adzworld-align-left { text-align:left; }
.adzworld-align-right { text-align:right; }
.adzworld-align-center { text-align:center; }
</style>';

$header_class = 'adzworld-align-' . $header_align;
$footer_class = 'adzworld-align-' . $footer_align;

if ( isset( $display_type ) && 'thru_page' === $display_type ) {
	?>
	<head>
	<?php
	if ( function_exists( 'wp_enqueue_script' ) && function_exists( 'wp_print_scripts' ) ) {
		wp_enqueue_script( 'jquery' );
		wp_print_scripts( 'jquery' );
	}
	?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			var i = <?php echo (int) $seconds; ?>;
			var adz_interval = setInterval(function () {
				if (i >= 0) {
					jQuery('.adz_timer').text(" " + i + " ");
					i--;
				} else {
					jQuery('.continue_reading').text('Continue >>');
					jQuery('.continue_reading').addClass('close_adz');
					clearInterval(adz_interval);
				}
			}, 1000);

			jQuery(document).on('click', '.close_adz', function(){
				jQuery.ajax({
					url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
					type: 'post',
					data: {
						action: 'adz_get_advertise_content',
						target_type: '<?php echo esc_js( $type_attr ); ?>',
						target: '<?php echo esc_js( $target_attr ); ?>',
						ad_visibility_interval: '<?php echo (int) $interval; ?>',
						repeat_times: '<?php echo (int) $repeats; ?>',
						close_adz: 'true'
					},
					success: function(data) {
						if (data != 'continue') {
							location.reload();
						}
					}
				});
			});
		});
	</script>
	<style>
	#adz_header_container { background:#4A7354; border:1px solid #104A1F; left:0; position:fixed; width:100%; top:0; }
	#adz_header_content { margin:0 auto; width:100%; }
	#adz_container { margin:0 auto; overflow:auto; padding:80px 0; text-align:center; }
	#adz_footer_container { background:#4A7354; border:1px solid #104A1F; bottom:0; height:60px; left:0; position:fixed; width:100%; }
	#adz_footer_content { line-height:60px; margin:0 auto; width:100%; }
	.adz_popup #adz_header_container, .adz-world-container #adz_header_container, .adz-world-category-wapper #adz_header_container { position:inherit; }
	.adz_popup #adz_footer_container, .adz-world-container #adz_footer_container, .adz-world-category-wapper #adz_footer_container { position:inherit; }
	.adz_header_text, .adz_footer_text { color:#fff; }
	.adz_timer { font-size:22px; }
	.adz_popup #adz_container { max-height:600px; overflow-y:scroll; }
	.adzworld-align-left { text-align:left; }
	.adzworld-align-right { text-align:right; }
	.adzworld-align-center { text-align:center; }
	</style>
	</head>
	<div id="adz_header_container">
		<div id="adz_header_content" class="<?php echo esc_attr( $header_class ); ?>">
			<a href="javascript:void(0)" class="adz_header_text"><div class="continue_reading"><?php echo esc_html( $header_text ); ?> <div><span class="adz_timer"></span> <?php esc_html_e( 'seconds', 'adz-world' ); ?></div></div></a>
		</div>
	</div>
	<div id="adz_container">
		<div id="content">
			<?php echo wp_kses( $ad_body, $ad_allowed ); ?>
		</div>
	</div>
	<div id="adz_footer_container">
		<div id="adz_footer_content" class="<?php echo esc_attr( $footer_class ); ?>">
			<a href="javascript:void(0)" class="adz_footer_text"><p class="continue_reading"><?php echo esc_html( $footer_text ); ?> <span class="adz_timer"></span> <?php esc_html_e( 'seconds', 'adz-world' ); ?></p></a>
		</div>
	</div>
	<?php
} else {
	$chrome  = '<!-- BEGIN: Sticky Header -->';
	$chrome .= '<div id="adz_header_container"><div id="adz_header_content" class="' . esc_attr( $header_class ) . '">';
	$chrome .= '<a href="javascript:void(0)" class="adz_header_text"><div class="continue_reading">' . esc_html( $header_text );
	$chrome .= ' <div><span class="adz_timer"></span> ' . esc_html__( 'seconds', 'adz-world' ) . '</div></div></a></div></div>';
	$chrome .= '<div id="adz_container"><div id="content">' . wp_kses( $ad_body, $ad_allowed ) . '</div></div>';
	$chrome .= '<div id="adz_footer_container"><div id="adz_footer_content" class="' . esc_attr( $footer_class ) . '">';
	$chrome .= '<a href="javascript:void(0)" class="adz_footer_text"><p class="continue_reading">' . esc_html( $footer_text );
	$chrome .= ' <span class="adz_timer"></span> ' . esc_html__( 'seconds', 'adz-world' ) . '</p></a></div></div>';
	$chrome .= $style;

	echo esc_html( base64_encode( $chrome ) );
}
