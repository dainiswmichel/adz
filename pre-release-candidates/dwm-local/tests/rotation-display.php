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
 * Store one named rotation for a render assertion.
 *
 * Presentation lives on the rotation now, so a render test has to write a
 * record rather than pass attributes. Written through the shared sanitizer, so
 * a test cannot store a shape the plugin itself would refuse.
 *
 * @param string              $key          Rotation key.
 * @param array<int,string>   $slots        Ordered slot references.
 * @param array<string,mixed> $presentation Presentation overrides.
 * @return void
 */
function adzworld_test_rotation_store( $key, $slots, $presentation = array() ) {
	$rotations         = adzworld_named_rotations();
	$rotations[ $key ] = array_merge(
		adzworld_rotation_presentation_defaults(),
		$presentation,
		array(
			'name'  => 'Test ' . $key,
			'slots' => $slots,
		)
	);

	update_option( 'adzworld_named_rotations', adzworld_sanitize_named_rotations( $rotations ), false );
}

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
	$original_named   = get_option( 'adzworld_named_rotations', null );
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
		update_option(
			'adzworld_named_rotations',
			array(
				'rotation-b' => array(
					'name'     => 'Rotation B',
					'display'  => 'flat',
					'controls' => 'both',
					'slots'    => array( 'intro', 'ad:' . $id, 'source:test-provider' ),
				),
			),
			false
		);
		$named_html = adzworld_render_rotation( array( 'name' => 'rotation-b' ) );
		adzworld_test_assert(
			false !== strpos( $named_html, 'codex-rotation-local-marker' ) && false !== strpos( $named_html, 'codex-existing-provider-marker' ),
			'A widget can render a saved named rotation containing local and provider slots'
		);
		adzworld_test_assert(
			false !== strpos( $named_html, 'adzworld-rotation--flat' ),
			'The display format stored on the rotation reaches the markup'
		);

		/*
		 * The whole point of the shortcode carrying only a name: a presentation
		 * attribute written on the shortcode must not change what the rotation
		 * renders as. A page that still carries the old attributes gets the
		 * rotation's own settings.
		 */
		$ignored_html = adzworld_render_rotation(
			array(
				'name'     => 'rotation-b',
				'display'  => 'grid',
				'controls' => 'none',
				'size'     => '728x90',
			)
		);
		adzworld_test_assert(
			false !== strpos( $ignored_html, 'adzworld-rotation--flat' )
				&& false === strpos( $ignored_html, 'adzworld-rotation--grid' ),
			'A display attribute on the shortcode is ignored; the rotation decides'
		);
		adzworld_test_assert(
			false === strpos( $ignored_html, 'adzworld-rotation--size-728x90' )
				&& false !== strpos( $ignored_html, 'data-adzworld-step="-1"' ),
			'Size and controls attributes on the shortcode are ignored too'
		);

		/*
		 * Proof that the size assertion above can fail: the same size, stored on
		 * the rotation, does reach the markup under exactly that class name.
		 */
		adzworld_test_rotation_store( 'rotation-sized', array( 'source:test-provider' ), array( 'size' => '728x90' ) );
		adzworld_test_assert(
			false !== strpos( adzworld_render_rotation( array( 'name' => 'rotation-sized' ) ), 'adzworld-rotation--size-728x90' ),
			'A creative size stored on the rotation does reach the markup'
		);
		adzworld_test_assert(
			false !== strpos( adzworld_render_rotation( array( 'name' => 'no-such-rotation' ) ), 'role="alert"' ),
			'An unknown rotation name reports itself instead of rendering nothing silently'
		);
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
		adzworld_test_rotation_store( 'rotation-provider-only', array( 'source:test-provider' ) );
		$html = adzworld_render_rotation( array( 'name' => 'rotation-provider-only' ) );
		adzworld_test_assert( false !== strpos( $html, 'codex-existing-provider-marker' ) && false === strpos( $html, 'codex-rotation-local-marker' ), 'Provider-only rotation inserts no local ad' );
		adzworld_test_assert( false === strpos( $html, 'class="adz-panel"' ), 'No introduction panel is inserted automatically' );

		adzworld_test_rotation_store(
			'rotation-mixed',
			array( 'source:test-provider', 'ad:' . $id, 'intro' ),
			array(
				'display'  => 'flat',
				'controls' => 'both',
				'label'    => '"><script>invalid()</script>',
			)
		);
		$html = adzworld_render_rotation( array( 'name' => 'rotation-mixed' ) );
		adzworld_test_assert( false !== strpos( $html, 'codex-existing-provider-marker' ) && false !== strpos( $html, 'codex-rotation-local-marker' ), 'Mixed carousel contains both explicitly selected sources' );
		adzworld_test_assert( false !== strpos( $html, 'class="adz-panel"' ), 'Explicit introduction slot includes the preference form' );
		adzworld_test_assert( false !== strpos( $html, 'data-adzworld-step="-1"' ) && false !== strpos( $html, 'data-adzworld-position="2"' ), 'Arrow and position controls are rendered' );
		adzworld_test_assert( false === strpos( $html, '<script>invalid()' ), 'Rotation labels cannot inject markup' );

		/*
		 * A display format can no longer arrive from a page, only from the
		 * stored record, so an unregistered one is a stale or tampered option
		 * rather than an author's mistake. It degrades to Single instead of
		 * replacing the rotation with an error, because the reader should still
		 * see the advertising the publisher configured. An unregistered format
		 * must never reach the markup as a class no stylesheet defines.
		 */
		adzworld_test_rotation_store( 'rotation-stale-format', array( 'intro' ), array( 'display' => 'unknown' ) );
		$stale_html = adzworld_render_rotation( array( 'name' => 'rotation-stale-format' ) );
		adzworld_test_assert(
			false !== strpos( $stale_html, 'adzworld-rotation--single' )
				&& false === strpos( $stale_html, 'adzworld-rotation--unknown' ),
			'An unregistered stored display format degrades to Single and never reaches the markup'
		);
		adzworld_test_assert(
			'single' === adzworld_sanitize_rotation_display( 'unknown' )
				&& 'both' === adzworld_sanitize_rotation_controls( 'sideways' )
				&& '' === adzworld_sanitize_rotation_size( '1x1' )
				&& 'flat' === adzworld_sanitize_rotation_display( 'flat' ),
			'Presentation sanitizers accept registered values and refuse the rest'
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
		if ( null === $original_named ) {
			delete_option( 'adzworld_named_rotations' );
		} else {
			update_option( 'adzworld_named_rotations', $original_named, false );
		}
		wp_delete_post( $id, true );
	}
}

adzworld_test_rotation_display();
WP_CLI::success( 'Rotation display checks passed; no live advertising provider was contacted.' );
