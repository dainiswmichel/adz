<?php
/**
 * Sticky header, advertisement, sticky footer, each with a countdown.
 *
 * Renders full page in thru_page mode and inline otherwise. Was template1.php
 * until 1.1.1; adzworld_template_renames() keeps the old name resolving.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$adzworld_template_name = basename( __FILE__ );
$adzworld_setting_array = adzworld_template_saved_lines( $adzworld_template_name );
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
	adzworld_legacy_request(
		array(
			'mode'    => 'thru',
			'seconds' => absint( $ad_visibility ),
			'request' => array(),
			'close'   => adzworld_legacy_close_fields( $target_type, $target, $ad_visibility_interval, $repeat_times, $rotation_id ),
		)
	);
	wp_print_scripts( array( 'jquery', 'adzworld-legacy' ) );
	?>
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
