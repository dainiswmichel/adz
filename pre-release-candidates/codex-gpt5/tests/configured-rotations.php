<?php
/**
 * Saved-rotation identity regression checks, executed through wp eval-file.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

require __DIR__ . '/regression.php';

/**
 * Verify option writes with disposable state and restore the saved settings.
 *
 * @return void
 */
function adzworld_test_configured_rotations() {
	$missing  = new stdClass();
	$original = get_option( 'adz_ad_options', $missing );
	$id       = 'adz_rotation_' . wp_rand( 100000000, 999999999 );
	$state    = array(
		'served'    => array( '123' ),
		'un_served' => array( '456' ),
	);

	adzworld_test_assert( add_option( $id, 'protected-probe', '', false ), 'Disposable rotation option is unique' );
	try {
		update_option( 'adz_ad_options', array( 'ad_settings' => array() ) );
		adzworld_test_assert( '' === adzworld_configured_rotation_id( $id ), 'Prefix alone does not establish a configured rotation' );
		adzworld_test_assert( false === adz_update_rotation_state( $id, $state ), 'Unconfigured rotation state cannot be overwritten' );
		adzworld_test_assert( 'protected-probe' === get_option( $id ), 'Rejected write preserves existing option bytes' );

		update_option( 'adz_ad_options', array( 'ad_settings' => array( array( 'rotation_id' => $id ) ) ) );
		adzworld_test_assert( adzworld_configured_rotation_id( $id ) === $id, 'Saved rotation remains available' );
		adzworld_test_assert( adz_update_rotation_state( $id, $state ), 'Configured rotation still accepts state updates' );
		adzworld_test_assert( get_option( $id ) === $state, 'Configured rotation stores the intended state' );
		adzworld_test_assert( '' === adzworld_configured_rotation_id( array( $id ) ), 'Array identifiers cannot select a rotation' );
		adzworld_test_assert( '' === adzworld_configured_rotation_id( 'siteurl' ), 'Core option identifiers remain excluded' );

		update_option( 'adz_ad_options', array( 'ad_settings' => array( null, 'invalid', array() ) ) );
		adzworld_test_assert( '' === adzworld_configured_rotation_id( $id ), 'Malformed saved rows cannot grant rotation access' );
		update_option( 'adz_ad_options', 'invalid' );
		adzworld_test_assert( '' === adzworld_configured_rotation_id( $id ), 'Malformed saved settings cannot grant rotation access' );
	} finally {
		delete_option( $id );
		if ( $missing === $original ) {
			delete_option( 'adz_ad_options' );
		} else {
			update_option( 'adz_ad_options', $original );
		}
	}
}

adzworld_test_configured_rotations();
WP_CLI::success( 'Configured-rotation checks passed; request target ownership remains a separate open requirement.' );
