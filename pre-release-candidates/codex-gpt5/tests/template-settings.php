<?php
/**
 * Administrator template-save authorization and storage compatibility.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

require __DIR__ . '/regression.php';

$adzworld_original_template = get_option( 'template_template1.php', null );
$adzworld_original_user     = get_current_user_id();
try {
	wp_set_current_user( 0 );
	adzworld_test_assert( is_wp_error( adzworld_save_template_settings() ), 'Anonymous requests cannot save template settings' );
	$adzworld_admins = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
		)
	);
	adzworld_test_assert( ! empty( $adzworld_admins ), 'An administrator exists for template checks' );
	wp_set_current_user( $adzworld_admins[0]->ID );
	$_POST = array(
		'adz_template' => 'template1.php',
		'header_text'  => wp_slash( "Captain's header" ),
		'footer_text'  => 'Continue',
		'header_style' => 'left',
		'footer_style' => 'right',
	);
	adzworld_test_assert( is_wp_error( adzworld_save_template_settings() ), 'Administrator without a template-save nonce is rejected' );
	$_POST['adzworld_template_nonce'] = wp_create_nonce( 'adzworld_template_save' );
	adzworld_test_assert( true === adzworld_save_template_settings(), 'Authorized administrator can save a shipped template' );
	$adzworld_saved = get_option( 'template_template1.php' );
	adzworld_test_assert( is_array( $adzworld_saved ) && "Captain's header" === $adzworld_saved['header_text'], 'Native option storage preserves unslashed template text' );
	adzworld_test_assert( true === adzworld_save_template_settings(), 'Saving identical settings is a successful no-op' );
	$_POST['header_style'] = 'center; background: red';
	adzworld_test_assert( is_wp_error( adzworld_save_template_settings() ), 'Injected CSS is rejected rather than stored as alignment' );
	adzworld_test_assert( get_option( 'template_template1.php' ) === $adzworld_saved, 'Rejected alignment does not change the stored template' );
	$_POST['header_style'] = 'left';
	$_POST['adz_template'] = '../template1.php';
	adzworld_test_assert( is_wp_error( adzworld_save_template_settings() ), 'Template saves cannot address a path outside the shipped template list' );
	$adzworld_output = array();
	foreach ( array( $adzworld_saved, maybe_serialize( $adzworld_saved ) ) as $adzworld_storage ) {
		update_option( 'template_template1.php', $adzworld_storage, false );
		$display_type = 'page';
		$ad_text      = '<p>codex-template-marker</p>';
		ob_start();
		require dirname( __DIR__ ) . '/adz-templates/template1.php';
		$adzworld_output[] = ob_get_clean();
	}
	adzworld_test_assert( $adzworld_output[0] === $adzworld_output[1], 'Native options and historical double-serialized options render identical templates' );
	$adzworld_decoded = base64_decode( $adzworld_output[0], true );
	adzworld_test_assert(
		is_string( $adzworld_decoded ) && false !== strpos( $adzworld_decoded, 'codex-template-marker' )
		&& false !== strpos( $adzworld_decoded, 'Captain&#039;s header' ),
		'Template rendering preserves ad content and escapes administrator text'
	);
} finally {
	$_POST = array();
	wp_set_current_user( $adzworld_original_user );
	if ( null === $adzworld_original_template ) {
		delete_option( 'template_template1.php' );
	} else {
		update_option( 'template_template1.php', $adzworld_original_template, false );
	}
}
WP_CLI::success( 'Template settings checks passed.' );
