<?php
/**
 * Explicit-slot rotation regression checks.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

require __DIR__ . '/regression.php';

/**
 * Exercise rendering without third-party traffic or permanent fixtures.
 *
 * @return void
 */
function adzworld_test_rotation_display() {
	$id = wp_insert_post(
		array(
			'post_type'    => 'adz_ad',
			'post_status'  => 'publish',
			'post_title'   => 'Disposable rotation ad',
			'post_content' => '<p>codex-rotation-local-marker</p>',
		),
		true
	);
	adzworld_test_assert( ! is_wp_error( $id ), 'Rotation fixture is created' );
	$tag = 'adzworld_test_provider';
	add_shortcode(
		$tag,
		static function () {
			return '<p>codex-existing-provider-marker</p>';
		}
	);
	$original_sources = adzworld_rotation_sources();
	$original_saved   = get_option( 'adzworld_rotation_sources', null );
	$original_user    = get_current_user_id();
	try {
		wp_set_current_user( 0 );
		adzworld_test_assert( is_wp_error( adzworld_save_rotation_source() ), 'Anonymous requests cannot configure advertising sources' );
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
			)
		);
		adzworld_test_assert( ! empty( $admins ), 'Test site has an administrator' );
		wp_set_current_user( $admins[0]->ID );
		$_POST = array(
			'source_key'       => 'saved-provider-test',
			'source_label'     => 'Saved provider test',
			'source_shortcode' => wp_slash( '[' . $tag . ' placement="unit"]' ),
		);
		adzworld_test_assert( is_wp_error( adzworld_save_rotation_source() ), 'Administrator without a nonce cannot save a source' );
		$_POST['adzworld_source_nonce'] = wp_create_nonce( 'adzworld_rotation_source' );
		adzworld_test_assert( true === adzworld_save_rotation_source(), 'Administrator with a nonce can save an existing shortcode' );
		$saved_sources = get_option( 'adzworld_rotation_sources' );
		adzworld_test_assert( '[' . $tag . ' placement="unit"]' === $saved_sources['saved-provider-test']['shortcode'], 'Provider shortcode attributes survive WordPress request slashing' );
		$_POST['remove_source'] = 'yes';
		adzworld_test_assert( true === adzworld_save_rotation_source(), 'Administrator can remove a saved source' );
		$saved_sources = get_option( 'adzworld_rotation_sources' );
		adzworld_test_assert( ! isset( $saved_sources['saved-provider-test'] ), 'Removed source is no longer stored' );

		adzworld_test_assert( true === adzworld_register_rotation_source( 'test-provider', '[' . $tag . ']', 'Test publisher source' ), 'Existing provider shortcode can register explicitly' );
		adzworld_test_assert( is_wp_error( adzworld_register_rotation_source( 'test-provider', '[' . $tag . ']', 'Duplicate' ) ), 'A source cannot silently overwrite another source' );
		adzworld_test_assert( is_wp_error( adzworld_register_rotation_source( 'missing-provider', '[not_registered_anywhere]', 'Unknown' ) ), 'Unavailable provider shortcode is rejected' );
		$slots = adzworld_parse_rotation( 'source:test-provider:9,ad:' . $id . ':1' );
		adzworld_test_assert( 9 === $slots[0]['weight'] && 1 === $slots[1]['weight'], 'Nine-to-one weights are preserved exactly' );
		$provider_draws = 0;
		$local_draws    = 0;
		for ( $draw = 1; $draw <= 10; ++$draw ) {
			$selected        = adzworld_rotation_weighted_slot( $slots, $draw );
			$provider_draws += 'source' === $selected['type'] ? 1 : 0;
			$local_draws    += 'ad' === $selected['type'] ? 1 : 0;
		}
		adzworld_test_assert( 9 === $provider_draws && 1 === $local_draws, 'Exactly nine of ten possible draws select the provider, and one selects the local ad' );
		adzworld_test_assert( is_wp_error( adzworld_rotation_weighted_slot( $slots, 0 ) ) && is_wp_error( adzworld_rotation_weighted_slot( $slots, 11 ) ), 'Out-of-range draws fail explicitly' );
		$html = adzworld_render_rotation( array( 'slots' => 'source:test-provider' ) );
		adzworld_test_assert( false !== strpos( $html, 'codex-existing-provider-marker' ) && false === strpos( $html, 'codex-rotation-local-marker' ), 'Provider-only rotation inserts no local ad' );
		adzworld_test_assert( false === strpos( $html, 'adzworld-preference' ), 'No introduction panel is inserted automatically' );

		$html = adzworld_render_rotation(
			array(
				'slots'    => 'source:test-provider,ad:' . $id . ',intro',
				'display'  => 'flat',
				'controls' => 'both',
				'label'    => '"><script>invalid()</script>',
			)
		);
		adzworld_test_assert( false !== strpos( $html, 'codex-existing-provider-marker' ) && false !== strpos( $html, 'codex-rotation-local-marker' ), 'Mixed carousel contains both explicitly selected sources' );
		adzworld_test_assert( false !== strpos( $html, 'class="adzworld-preference"' ), 'Explicit introduction slot includes the preference form' );
		adzworld_test_assert( false !== strpos( $html, 'data-adzworld-step="-1"' ) && false !== strpos( $html, 'data-adzworld-position="2"' ), 'Arrow and position controls are rendered' );
		adzworld_test_assert( false === strpos( $html, '<script>invalid()' ), 'Rotation labels cannot inject markup' );
		adzworld_test_assert(
			false !== strpos(
				adzworld_render_rotation(
					array(
						'slots'   => 'intro',
						'display' => 'unknown',
					)
				),
				'role="alert"'
			),
			'Invalid display settings produce a visible error'
		);
		$GLOBALS['adzworld_test_shortcode_executed'] = false;
		add_shortcode(
			'adzworld_test_untrusted',
			static function () {
				$GLOBALS['adzworld_test_shortcode_executed'] = true;
				return 'untrusted-code-ran';
			}
		);
		$advertisement = adzworld_ad_content( '<script>invalid()</script>[adzworld_test_untrusted][adzworld_iframe adz_url="https://example.com/" height="250"]' );
		adzworld_test_assert( ! $GLOBALS['adzworld_test_shortcode_executed'], 'Advertising responses cannot execute unrelated site shortcodes' );
		adzworld_test_assert( false !== strpos( $advertisement, '<iframe' ) && false === strpos( $advertisement, '<script' ), 'Legacy iframe remains available while executable ad markup is stripped' );

		foreach ( array( '', 'unknown:1', 'intro:0', 'intro:101', 'ad:' . $id . ':1:extra', 'source:missing', 'intro:1.5' ) as $invalid ) {
			adzworld_test_assert( is_wp_error( adzworld_parse_rotation( $invalid ) ), 'Invalid slot specifications fail explicitly: ' . $invalid );
		}
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'draft',
			)
		);
		adzworld_test_assert( is_wp_error( adzworld_parse_rotation( 'ad:' . $id ) ), 'Private/unpublished ad cannot enter the public rotation' );
	} finally {
		remove_shortcode( $tag );
		remove_shortcode( 'adzworld_test_untrusted' );
		unset( $GLOBALS['adzworld_test_shortcode_executed'] );
		$GLOBALS['adzworld_rotation_sources'] = $original_sources;
		$_POST                                = array();
		wp_set_current_user( $original_user );
		if ( null === $original_saved ) {
			delete_option( 'adzworld_rotation_sources' );
		} else {
			update_option( 'adzworld_rotation_sources', $original_saved, false );
		}
		wp_delete_post( $id, true );
	}
}

adzworld_test_rotation_display();
WP_CLI::success( 'Rotation display checks passed; no live advertising provider was contacted.' );
