<?php
/**
 * Starter pack: build a page that demonstrates every display format.
 *
 * An administrator who has just installed the plugin cannot easily tell what
 * eighteen format names look like. This creates one WordPress page holding a
 * live example of each, so the choice is made by looking rather than by reading
 * a list.
 *
 * Three deliberate limits:
 *
 * - The page is created as a DRAFT. Nothing the plugin generates appears to the
 *   public until the administrator reads it and publishes it themselves.
 * - It is created once. The page id is recorded, and a second request links to
 *   the existing page instead of making a duplicate.
 * - The option names it writes are fixed strings in this file. Nothing from the
 *   request ever becomes an option name, which is the fault the 29 September
 *   review notice raised against the rotation handler.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option holding the id of the generated showcase page.
 */
const ADZWORLD_SHOWCASE_PAGE_OPTION = 'adzworld_showcase_page';

/**
 * Rotation key the showcase page's shortcodes refer to.
 */
const ADZWORLD_SHOWCASE_ROTATION_KEY = 'adzworld_showcase';

/**
 * The existing showcase page, if one was created and still exists.
 *
 * A page the administrator deleted or moved to the trash does not count, so the
 * starter pack can be run again after a clear-out.
 *
 * @return WP_Post|null
 */
function adzworld_showcase_page() {
	$page_id = (int) get_option( ADZWORLD_SHOWCASE_PAGE_OPTION, 0 );

	if ( $page_id < 1 ) {
		return null;
	}

	$page = get_post( $page_id );

	if ( ! $page instanceof WP_Post || 'page' !== $page->post_type || 'trash' === $page->post_status ) {
		return null;
	}

	return $page;
}

/**
 * Ensure a rotation exists for the showcase shortcodes to refer to.
 *
 * Built from whatever the site already has: the invitation hub, which is always
 * available, plus the first few local adz and provider sources if any are
 * configured. It is written through adzworld_sanitize_named_rotations() rather
 * than straight into the option, so the stored shape is the one every other
 * path produces.
 *
 * An existing rotation under this key is left alone; an administrator may have
 * edited it.
 *
 * @return string The rotation key, or an empty string if nothing could be built.
 */
function adzworld_showcase_rotation() {
	$rotations = adzworld_named_rotations();

	if ( isset( $rotations[ ADZWORLD_SHOWCASE_ROTATION_KEY ] ) ) {
		return ADZWORLD_SHOWCASE_ROTATION_KEY;
	}

	$choices = adzworld_rotation_slot_choices();
	$slots   = array();

	if ( isset( $choices['intro'] ) ) {
		$slots[] = 'intro';
	}

	foreach ( array_keys( $choices ) as $choice ) {
		if ( 'intro' === $choice ) {
			continue;
		}

		$slots[] = $choice;

		if ( count( $slots ) >= 3 ) {
			break;
		}
	}

	if ( empty( $slots ) ) {
		return '';
	}

	$rotations[ ADZWORLD_SHOWCASE_ROTATION_KEY ] = array(
		'name'  => __( 'Display format showcase', 'adz-world' ),
		'slots' => $slots,
	);

	$rotations = adzworld_sanitize_named_rotations( $rotations );

	if ( ! isset( $rotations[ ADZWORLD_SHOWCASE_ROTATION_KEY ] ) ) {
		return '';
	}

	update_option( 'adzworld_named_rotations', $rotations, false );

	return ADZWORLD_SHOWCASE_ROTATION_KEY;
}

/**
 * Build the showcase page's content.
 *
 * One section per format group and one shortcode per format, each introduced by
 * its label and its note so the page reads as a catalogue rather than a wall of
 * frames. The anchored formats are described rather than embedded: a bottom
 * dock and a corner card pin themselves to the viewport, so putting all three
 * on one page would stack them on top of each other.
 *
 * @param string $rotation_key Rotation the shortcodes refer to.
 * @return string Post content.
 */
function adzworld_showcase_content( $rotation_key ) {
	$formats  = adzworld_rotation_display_formats();
	$anchored = array( 'dock-bottom', 'dock-top', 'corner' );
	$grouped  = array();

	foreach ( $formats as $format => $meta ) {
		$grouped[ $meta['group'] ][ $format ] = $meta;
	}

	$lines = array();

	$lines[] = '<!-- wp:paragraph -->';
	$lines[] = '<p>' . esc_html__( 'Every display format the adz-world rotation shortcode accepts, each shown with the shortcode that produces it. This page was created as a draft by the starter pack; edit or delete it freely.', 'adz-world' ) . '</p>';
	$lines[] = '<!-- /wp:paragraph -->';

	foreach ( $grouped as $group => $members ) {
		$lines[] = '<!-- wp:heading {"level":2} -->';
		$lines[] = '<h2>' . esc_html( $group ) . '</h2>';
		$lines[] = '<!-- /wp:heading -->';

		foreach ( $members as $format => $meta ) {
			$shortcode = sprintf(
				'[adz_rotation name="%1$s" display="%2$s" controls="both"]',
				$rotation_key,
				$format
			);

			/*
			 * do_shortcode() runs over the whole post, including the inside of
			 * a code block, so a shortcode shown as an example renders instead
			 * of displaying. Doubling the brackets is WordPress' own escape for
			 * that: [[shortcode]] outputs the literal [shortcode], which is
			 * what a reader needs to copy.
			 */
			$literal = '[' . $shortcode . ']';

			$lines[] = '<!-- wp:heading {"level":3} -->';
			$lines[] = '<h3>' . esc_html( $meta['label'] ) . '</h3>';
			$lines[] = '<!-- /wp:heading -->';

			$lines[] = '<!-- wp:paragraph -->';
			$lines[] = '<p>' . esc_html( $meta['note'] ) . '</p>';
			$lines[] = '<!-- /wp:paragraph -->';

			if ( in_array( $format, $anchored, true ) ) {
				$lines[] = '<!-- wp:paragraph -->';
				$lines[] = '<p><em>' . esc_html__( 'This format pins itself to the edge of the viewport, so it is listed rather than placed here. Add the shortcode below to a page of its own to see it.', 'adz-world' ) . '</em></p>';
				$lines[] = '<!-- /wp:paragraph -->';

				$lines[] = '<!-- wp:code -->';
				$lines[] = '<pre class="wp-block-code"><code>' . esc_html( $literal ) . '</code></pre>';
				$lines[] = '<!-- /wp:code -->';
				continue;
			}

			$lines[] = '<!-- wp:shortcode -->';
			$lines[] = $shortcode;
			$lines[] = '<!-- /wp:shortcode -->';

			$lines[] = '<!-- wp:code -->';
			$lines[] = '<pre class="wp-block-code"><code>' . esc_html( $literal ) . '</code></pre>';
			$lines[] = '<!-- /wp:code -->';
		}
	}

	$lines[] = '<!-- wp:heading {"level":2} -->';
	$lines[] = '<h2>' . esc_html__( 'Fixed sizes', 'adz-world' ) . '</h2>';
	$lines[] = '<!-- /wp:heading -->';

	$lines[] = '<!-- wp:paragraph -->';
	$lines[] = '<p>' . esc_html__( 'Add a size to hold the frame to a standard creative size. Each one caps its width at the space available, so a wide size in a narrow column shrinks instead of overflowing. Leave the size out for a fluid frame.', 'adz-world' ) . '</p>';
	$lines[] = '<!-- /wp:paragraph -->';

	foreach ( adzworld_rotation_size_presets() as $size => $meta ) {
		$lines[] = '<!-- wp:code -->';
		$lines[] = '<pre class="wp-block-code"><code>' . esc_html(
			sprintf(
				'[[adz_rotation name="%1$s" size="%2$s"]]  ',
				$rotation_key,
				$size
			) . $meta['label']
		) . '</code></pre>';
		$lines[] = '<!-- /wp:code -->';
	}

	return implode( "\n", $lines );
}

add_action( 'admin_post_adzworld_create_showcase', 'adzworld_create_showcase' );
/**
 * Create the showcase page.
 *
 * The nonce proves the request came from the Rotation screen; it is not
 * authorisation, so the capability is checked separately. Creating a page needs
 * more than the ability to reach this screen, so publish_pages is required as
 * well as manage_options.
 *
 * @return void
 */
function adzworld_create_showcase() {
	check_admin_referer( 'adzworld_create_showcase', 'adzworld_showcase_nonce' );

	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'publish_pages' ) ) {
		wp_die( esc_html__( 'Administrator permission is required to create a page.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$notice   = 'adzworld_rotation_notice_' . get_current_user_id();
	$existing = adzworld_showcase_page();

	if ( $existing instanceof WP_Post ) {
		set_transient( $notice, __( 'The showcase page already exists; it was not created again.', 'adz-world' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( adzworld_desk_url( 'rotation' ) );
		exit;
	}

	$rotation_key = adzworld_showcase_rotation();

	if ( '' === $rotation_key ) {
		set_transient( $notice, __( 'The showcase page needs at least one rotation slot. Add an adz or connect a provider first.', 'adz-world' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( adzworld_desk_url( 'rotation' ) );
		exit;
	}

	$page_id = wp_insert_post(
		array(
			'post_title'   => __( 'Adz display formats', 'adz-world' ),
			'post_name'    => 'adz-display-formats',
			'post_content' => adzworld_showcase_content( $rotation_key ),
			'post_status'  => 'draft',
			'post_type'    => 'page',
			'post_author'  => get_current_user_id(),
		),
		true
	);

	if ( is_wp_error( $page_id ) || 0 === (int) $page_id ) {
		set_transient( $notice, __( 'The showcase page could not be created.', 'adz-world' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( adzworld_desk_url( 'rotation' ) );
		exit;
	}

	update_option( ADZWORLD_SHOWCASE_PAGE_OPTION, (int) $page_id, false );

	set_transient( $notice, __( 'The showcase page was created as a draft. Open it, look through the formats, then publish or delete it.', 'adz-world' ), MINUTE_IN_SECONDS );
	wp_safe_redirect( adzworld_desk_url( 'rotation' ) );
	exit;
}

/**
 * The starter packs available to an administrator.
 *
 * A registry rather than a single hard-coded pack, so a later pack is an entry
 * here and a render callback, not another tab.
 *
 * @return array<string,array{label:string,note:string,render:callable}>
 */
function adzworld_starter_packs() {
	return array(
		'showcase' => array(
			'label'  => __( 'Display format showcase', 'adz-world' ),
			'note'   => __( 'One page holding a live example of every display format, so a format can be chosen by looking at it rather than by reading a list. Created as a draft: nothing reaches the public until it is published.', 'adz-world' ),
			'render' => 'adzworld_starter_showcase_pack',
		),
	);
}

/**
 * The Starter packs tab.
 *
 * @return void
 */
function adzworld_starter_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$notice = 'adzworld_rotation_notice_' . get_current_user_id();

	if ( get_transient( $notice ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( (string) get_transient( $notice ) ) . '</p></div>';
		delete_transient( $notice );
	}

	echo '<h2>' . esc_html__( 'Starter packs', 'adz-world' ) . '</h2>';
	echo '<p class="adzworld-starter-intro">' . esc_html__( 'Each pack sets up something the plugin can demonstrate on this site. A pack only ever adds; nothing already here is changed or removed, and anything a pack creates can be deleted freely.', 'adz-world' ) . '</p>';

	echo '<div class="adzworld-starter-packs">';

	foreach ( adzworld_starter_packs() as $pack => $meta ) {
		echo '<section class="adzworld-starter-pack">';
		echo '<h3 class="adzworld-starter-pack__name">' . esc_html( $meta['label'] ) . '</h3>';
		echo '<p class="adzworld-starter-pack__note">' . esc_html( $meta['note'] ) . '</p>';

		if ( is_callable( $meta['render'] ) ) {
			call_user_func( $meta['render'] );
		}

		echo '</section>';
	}

	echo '</div>';
}

/**
 * The showcase pack's own state and controls.
 *
 * @return void
 */
function adzworld_starter_showcase_pack() {
	$existing = adzworld_showcase_page();

	if ( $existing instanceof WP_Post ) {
		echo '<p class="adzworld-starter-state">';
		printf(
			/* translators: %s: current post status of the generated page. */
			esc_html__( 'Created. Status: %s.', 'adz-world' ),
			'<strong>' . esc_html( $existing->post_status ) . '</strong>'
		);
		echo '</p>';
		echo '<p class="adzworld-starter-actions">';
		echo '<a class="button button-primary" href="' . esc_url( (string) get_edit_post_link( $existing->ID, 'url' ) ) . '">' . esc_html__( 'Edit the page', 'adz-world' ) . '</a> ';

		if ( 'publish' === $existing->post_status ) {
			echo '<a class="button" href="' . esc_url( (string) get_permalink( $existing->ID ) ) . '">' . esc_html__( 'View it', 'adz-world' ) . '</a>';
		}

		echo '</p>';
		echo '<p class="adzworld-starter-note">' . esc_html__( 'Delete that page and this pack can build a fresh one.', 'adz-world' ) . '</p>';

		return;
	}

	if ( ! current_user_can( 'publish_pages' ) ) {
		echo '<p class="adzworld-starter-note">' . esc_html__( 'This pack needs permission to publish pages.', 'adz-world' ) . '</p>';

		return;
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="adzworld_create_showcase">';
	wp_nonce_field( 'adzworld_create_showcase', 'adzworld_showcase_nonce' );
	echo '<p class="adzworld-starter-actions"><button type="submit" class="button button-primary">' . esc_html__( 'Create the showcase page', 'adz-world' ) . '</button></p>';
	echo '</form>';
}
