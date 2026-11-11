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
 * A display format is a property of the rotation, not of the shortcode that
 * places it, so a page showing eighteen formats needs eighteen rotations. One
 * is created per registered format, all sharing the same slots, so the page
 * compares presentation and nothing else.
 *
 * An existing rotation under any of these keys is left alone; an administrator
 * may have edited it.
 *
 * @return array<string,string> Format key => rotation key, empty if nothing could be built.
 */
function adzworld_showcase_rotations() {
	$rotations = adzworld_named_rotations();

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
		return array();
	}

	$keys = array();

	foreach ( adzworld_rotation_display_formats() as $format => $meta ) {
		$key             = sanitize_key( ADZWORLD_SHOWCASE_ROTATION_KEY . '_' . $format );
		$keys[ $format ] = $key;

		if ( isset( $rotations[ $key ] ) ) {
			continue;
		}

		$rotations[ $key ] = array(
			'name'     => sprintf(
				/* translators: %s: display format name. */
				__( 'Showcase — %s', 'adz-world' ),
				$meta['label']
			),
			'display'  => $format,
			'size'     => '',
			'controls' => 'both',
			'label'    => __( 'Advertising', 'adz-world' ),
			'slots'    => $slots,
		);
	}

	$rotations = adzworld_sanitize_named_rotations( $rotations );

	/*
	 * The sanitizer caps how many rotations are stored, so a site that already
	 * holds many of them may not receive every showcase rotation. Drop the
	 * formats whose rotation did not survive, rather than advertise a shortcode
	 * that resolves to nothing.
	 */
	foreach ( $keys as $format => $key ) {
		if ( ! isset( $rotations[ $key ] ) ) {
			unset( $keys[ $format ] );
		}
	}

	if ( empty( $keys ) ) {
		return array();
	}

	update_option( 'adzworld_named_rotations', $rotations, false );

	return $keys;
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
 * @param array<string,string> $rotation_keys Format key => rotation key.
 * @return string Post content.
 */
function adzworld_showcase_content( $rotation_keys ) {
	$formats  = adzworld_rotation_display_formats();
	$anchored = array( 'dock-bottom', 'dock-top', 'corner' );
	$grouped  = array();

	foreach ( $formats as $format => $meta ) {
		$grouped[ $meta['group'] ][ $format ] = $meta;
	}

	$lines = array();

	$lines[] = '<!-- wp:paragraph -->';
	$lines[] = '<p>' . esc_html__( 'Every display format adz-world offers, each placed by its own rotation. A shortcode names a rotation and nothing else: the format, the creative size and the navigation belong to the rotation and are set on the Rotation tab, so one rotation looks the same everywhere it is placed. That is why this page uses one rotation per format. It was created as a draft; edit or delete it freely.', 'adz-world' ) . '</p>';
	$lines[] = '<!-- /wp:paragraph -->';

	foreach ( $grouped as $group => $members ) {
		$lines[] = '<!-- wp:heading {"level":2} -->';
		$lines[] = '<h2>' . esc_html( $group ) . '</h2>';
		$lines[] = '<!-- /wp:heading -->';

		foreach ( $members as $format => $meta ) {
			if ( ! isset( $rotation_keys[ $format ] ) ) {
				continue;
			}

			$shortcode = sprintf( '[adz_rotation name="%s"]', $rotation_keys[ $format ] );

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
	$lines[] = '<p>' . esc_html__( 'A size holds the frame to a standard creative size. It caps its width at the space available, so a wide size in a narrow column shrinks instead of overflowing. Leaving the size unset gives a fluid frame, which is what responsive provider units expect. The size is chosen per rotation, under Creative size on the Rotation tab; these are the sizes offered there.', 'adz-world' ) . '</p>';
	$lines[] = '<!-- /wp:paragraph -->';

	$rows = array();

	foreach ( adzworld_rotation_size_presets() as $size => $meta ) {
		$rows[] = '<tr><td><code>' . esc_html( $size ) . '</code></td><td>' . esc_html( $meta['label'] ) . '</td></tr>';
	}

	$lines[] = '<!-- wp:table -->';
	$lines[] = '<figure class="wp-block-table"><table><thead><tr><th>' . esc_html__( 'Size', 'adz-world' )
		. '</th><th>' . esc_html__( 'Name', 'adz-world' ) . '</th></tr></thead><tbody>'
		. implode( '', $rows ) . '</tbody></table></figure>';
	$lines[] = '<!-- /wp:table -->';

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

	$rotation_keys = adzworld_showcase_rotations();

	if ( empty( $rotation_keys ) ) {
		set_transient( $notice, __( 'The showcase page needs at least one rotation slot. Add an adz or connect a provider first.', 'adz-world' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( adzworld_desk_url( 'rotation' ) );
		exit;
	}

	$page_id = wp_insert_post(
		array(
			'post_title'   => __( 'Adz display formats', 'adz-world' ),
			'post_name'    => 'adz-display-formats',
			'post_content' => adzworld_showcase_content( $rotation_keys ),
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
 * Each pack is a combination publishers actually run, not every permutation the
 * plugin allows. The display providers the plugin implements are AdSense,
 * Google Ad Manager, Taboola, Outbrain, Media.net and Amazon Publisher
 * Services; the affiliate networks are Amazon, Walmart, Awin, CJ, Rakuten, eBay
 * and ClickBank. A pack names the ones it needs and says so when they are not
 * connected, rather than building a rotation with a slot that cannot render.
 *
 * `requires` holds network ids from the provider catalogue. `slots` is built at
 * run time from what is actually connected, so a pack never stores a slot the
 * rotation validator would reject.
 *
 * @return array<string,array<string,mixed>>
 */
function adzworld_starter_packs() {
	return array(
		'showcase'  => array(
			'label'  => __( 'Display format showcase', 'adz-world' ),
			'note'   => __( 'One page holding a live example of every display format, so a format can be chosen by looking at it rather than by reading a list. Created as a draft: nothing reaches the public until it is published.', 'adz-world' ),
			'render' => 'adzworld_starter_showcase_pack',
		),
		'adsense'   => array(
			'label'      => __( 'AdSense and adz', 'adz-world' ),
			'note'       => __( 'The common starting point for a newer or smaller site: one AdSense unit in the header and adz alongside it in the content, rotating. AdSense needs no traffic minimum, so this is usually the first money a site makes.', 'adz-world' ),
			'requires'   => array( 'adsense' ),
			'rotation'   => 'adzworld_pack_adsense',
			'name'       => __( 'AdSense and adz', 'adz-world' ),
			'placements' => array(
				array(
					'where'   => __( 'Header or above the content', 'adz-world' ),
					'display' => 'banner',
					'size'    => '728x90',
				),
				array(
					'where'   => __( 'In the content', 'adz-world' ),
					'display' => 'inline-card',
					'size'    => '300x250',
				),
			),
		),
		'admanager' => array(
			'label'      => __( 'Ad Manager and adz', 'adz-world' ),
			'note'       => __( 'The step up from AdSense, and the biggest change in a publisher\'s ad setup: Google Ad Manager delivers the inventory and AdSense can backfill what it does not sell. A billboard at the top, adz in the sidebar.', 'adz-world' ),
			'requires'   => array( 'gam' ),
			'rotation'   => 'adzworld_pack_admanager',
			'name'       => __( 'Ad Manager and adz', 'adz-world' ),
			'placements' => array(
				array(
					'where'   => __( 'Top of the page', 'adz-world' ),
					'display' => 'billboard',
					'size'    => '970x250',
				),
				array(
					'where'   => __( 'Sidebar', 'adz-world' ),
					'display' => 'sidebar',
					'size'    => '300x600',
				),
			),
		),
		'discovery' => array(
			'label'        => __( 'Content recommendation and adz', 'adz-world' ),
			'note'         => __( 'Taboola or Outbrain sit below an article, where a reader has finished and is deciding what to do next. A grid puts adz in the same place, so the recommendations and the adz a reader asked for compete on equal terms.', 'adz-world' ),
			'requires'     => array( 'taboola', 'outbrain' ),
			'requires_any' => true,
			'rotation'     => 'adzworld_pack_discovery',
			'name'         => __( 'Below-article discovery', 'adz-world' ),
			'placements'   => array(
				array(
					'where'   => __( 'Below the article', 'adz-world' ),
					'display' => 'grid',
					'size'    => '',
				),
			),
		),
		'mobile'    => array(
			'label'      => __( 'Mobile first', 'adz-world' ),
			'note'       => __( 'For a site read mostly on a phone: a dismissible 320 by 50 bar docked at the bottom and a 300 by 250 in the content. The dock is the only format that covers part of the screen, so it carries a dismiss control and stays dismissed for the visit.', 'adz-world' ),
			'requires'   => array( 'adsense' ),
			'rotation'   => 'adzworld_pack_mobile',
			'name'       => __( 'Mobile first', 'adz-world' ),
			'placements' => array(
				array(
					'where'   => __( 'Anywhere on the page; it docks itself', 'adz-world' ),
					'display' => 'dock-bottom',
					'size'    => '320x50',
				),
				array(
					'where'   => __( 'In the content', 'adz-world' ),
					'display' => 'inline-card',
					'size'    => '300x250',
				),
			),
		),
		'affiliate' => array(
			'label'      => __( 'Affiliate links and adz', 'adz-world' ),
			'note'       => __( 'For a site that reviews things: the affiliate networks turn what a visitor asked for into a search on the merchant, and adz rotate beside them. Connect at least one affiliate program on the Affiliate programs tab first.', 'adz-world' ),
			'affiliates' => true,
			'rotation'   => 'adzworld_pack_affiliate',
			'name'       => __( 'Affiliate and adz', 'adz-world' ),
			'placements' => array(
				array(
					'where'   => __( 'In the content, after the verdict', 'adz-world' ),
					'display' => 'inline-card',
					'size'    => '',
				),
				array(
					'where'   => __( 'Sidebar', 'adz-world' ),
					'display' => 'sidebar',
					'size'    => '300x250',
				),
			),
		),
		'clickbank' => array(
			'label'      => __( 'ClickBank product and adz', 'adz-world' ),
			'note'       => __( 'ClickBank is a marketplace of single products, so a HopLink promotes one product rather than searching a catalogue. This pack puts that one promotion in a card and rotates adz with it. Nothing a visitor typed is ever added to a HopLink.', 'adz-world' ),
			'affiliates' => true,
			'rotation'   => 'adzworld_pack_clickbank',
			'name'       => __( 'ClickBank and adz', 'adz-world' ),
			'placements' => array(
				array(
					'where'   => __( 'In the content', 'adz-world' ),
					'display' => 'inline-card',
					'size'    => '',
				),
				array(
					'where'   => __( 'Bottom corner, dismissible', 'adz-world' ),
					'display' => 'corner',
					'size'    => '',
				),
			),
		),
	);
}

/**
 * Whether a pack's prerequisites are met, and what is missing.
 *
 * @param array $pack Pack definition.
 * @return array{ready:bool,missing:string[]}
 */
function adzworld_starter_requirements( $pack ) {
	$missing = array();

	if ( ! empty( $pack['affiliates'] ) ) {
		$connected = function_exists( 'adzworld_affiliate_connected_count' ) ? (int) adzworld_affiliate_connected_count() : 0;

		if ( $connected < 1 ) {
			$missing[] = __( 'an affiliate program', 'adz-world' );
		}
	}

	if ( empty( $pack['requires'] ) ) {
		return array(
			'ready'   => empty( $missing ),
			'missing' => $missing,
		);
	}

	$sources = adzworld_rotation_sources();
	$catalog = function_exists( 'adzworld_network_catalog' ) ? adzworld_network_catalog() : array();
	$present = array();

	foreach ( $pack['requires'] as $network ) {
		if ( isset( $sources[ 'network_' . $network ] ) ) {
			$present[] = $network;
			continue;
		}

		$label     = isset( $catalog[ $network ]['label'] ) ? $catalog[ $network ]['label'] : $network;
		$missing[] = $label;
	}

	// A pack marked requires_any needs one of its providers, not all of them.
	if ( ! empty( $pack['requires_any'] ) && ! empty( $present ) ) {
		$missing = array();
	}

	return array(
		'ready'   => empty( $missing ),
		'missing' => $missing,
	);
}

/**
 * Slots for a network pack: the invitation hub, the pack's providers, and the
 * first local adz if the site has one.
 *
 * @param array $pack Pack definition.
 * @return string[]
 */
function adzworld_starter_slots( $pack ) {
	$choices = adzworld_rotation_slot_choices();
	$slots   = array();

	if ( isset( $choices['intro'] ) ) {
		$slots[] = 'intro';
	}

	foreach ( (array) ( isset( $pack['requires'] ) ? $pack['requires'] : array() ) as $network ) {
		$slot = 'source:network_' . $network;

		if ( isset( $choices[ $slot ] ) ) {
			$slots[] = $slot;
		}
	}

	foreach ( array_keys( $choices ) as $choice ) {
		if ( 0 === strpos( $choice, 'ad:' ) ) {
			$slots[] = $choice;
			break;
		}
	}

	return $slots;
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

		if ( isset( $meta['render'] ) && is_callable( $meta['render'] ) ) {
			call_user_func( $meta['render'] );
		} else {
			adzworld_starter_rotation_pack( $pack, $meta );
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

add_action( 'admin_post_adzworld_create_pack', 'adzworld_create_pack' );
/**
 * Create a network pack's rotation.
 *
 * The pack id arrives in the request, so it is validated against the registry
 * before anything is read from it. The rotation key is then taken from the
 * registry entry, never from the request: no request value becomes an option
 * name or a stored key, which is the fault the 29 September review notice
 * raised against the rotation handler.
 *
 * @return void
 */
function adzworld_create_pack() {
	check_admin_referer( 'adzworld_create_pack', 'adzworld_pack_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$notice    = 'adzworld_rotation_notice_' . get_current_user_id();
	$requested = isset( $_POST['pack'] ) && is_string( $_POST['pack'] ) ? sanitize_key( wp_unslash( $_POST['pack'] ) ) : '';
	$packs     = adzworld_starter_packs();

	if ( '' === $requested || ! isset( $packs[ $requested ] ) || empty( $packs[ $requested ]['rotation'] ) ) {
		set_transient( $notice, __( 'That starter pack does not exist.', 'adz-world' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( adzworld_desk_url( 'starter' ) );
		exit;
	}

	$pack = $packs[ $requested ];
	$key  = $pack['rotation'];
	$ok   = adzworld_starter_requirements( $pack );

	if ( ! $ok['ready'] ) {
		set_transient(
			$notice,
			sprintf(
				/* translators: %s: comma-separated list of what is not connected. */
				__( 'This pack needs %s before it can be set up.', 'adz-world' ),
				implode( ', ', $ok['missing'] )
			),
			MINUTE_IN_SECONDS
		);
		wp_safe_redirect( adzworld_desk_url( 'starter' ) );
		exit;
	}

	$rotations = adzworld_named_rotations();

	if ( isset( $rotations[ $key ] ) ) {
		set_transient( $notice, __( 'That rotation already exists; it was left as it is.', 'adz-world' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( adzworld_desk_url( 'starter' ) );
		exit;
	}

	$slots = adzworld_starter_slots( $pack );

	if ( empty( $slots ) ) {
		set_transient( $notice, __( 'This pack found nothing to put in a rotation.', 'adz-world' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( adzworld_desk_url( 'starter' ) );
		exit;
	}

	$keys       = adzworld_pack_rotation_keys( $pack );
	$placements = empty( $pack['placements'] ) ? array() : array_values( $pack['placements'] );

	if ( empty( $keys ) ) {
		$keys       = array( 0 => $key );
		$placements = array( 0 => array() );
	}

	foreach ( $keys as $index => $placement_key ) {
		$placement = isset( $placements[ $index ] ) ? $placements[ $index ] : array();
		$name      = $pack['name'];

		if ( count( $keys ) > 1 && isset( $placement['where'] ) ) {
			$name = sprintf(
				/* translators: 1: pack name, 2: where the rotation is placed. */
				__( '%1$s — %2$s', 'adz-world' ),
				$pack['name'],
				$placement['where']
			);
		}

		$rotations[ $placement_key ] = array(
			'name'     => $name,
			'display'  => isset( $placement['display'] ) ? $placement['display'] : 'single',
			'size'     => isset( $placement['size'] ) ? $placement['size'] : '',
			'controls' => 'both',
			'label'    => __( 'Advertising', 'adz-world' ),
			'slots'    => $slots,
		);
	}

	// Stored through the shared sanitiser, so the shape matches every other path.
	$rotations = adzworld_sanitize_named_rotations( $rotations );

	if ( ! isset( $rotations[ $key ] ) ) {
		set_transient( $notice, __( 'The rotation this pack built was not valid and was not saved.', 'adz-world' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( adzworld_desk_url( 'starter' ) );
		exit;
	}

	update_option( 'adzworld_named_rotations', $rotations, false );

	set_transient( $notice, __( 'The rotation was created. Place the shortcodes below where you want them.', 'adz-world' ), MINUTE_IN_SECONDS );
	wp_safe_redirect( adzworld_desk_url( 'starter' ) );
	exit;
}

/**
 * The rotation key a pack creates for each of its placements.
 *
 * A placement is a position on the page with its own display format and
 * creative size. Those are properties of a rotation, not of the shortcode that
 * places it, so a pack offering a banner in the header and a card in the
 * content needs one rotation for each. The first placement keeps the pack's
 * own key so that packs created before this change are still recognised.
 *
 * @param array<string,mixed> $meta Pack definition.
 * @return array<int,string> Placement index => rotation key.
 */
function adzworld_pack_rotation_keys( $meta ) {
	$base = isset( $meta['rotation'] ) && is_string( $meta['rotation'] ) ? $meta['rotation'] : '';

	if ( '' === $base || empty( $meta['placements'] ) || ! is_array( $meta['placements'] ) ) {
		return array();
	}

	$keys = array();

	foreach ( array_keys( array_values( $meta['placements'] ) ) as $index ) {
		$keys[ $index ] = 0 === $index ? $base : sanitize_key( $base . '_' . ( $index + 1 ) );
	}

	return $keys;
}

/**
 * One network pack's state, controls and placements.
 *
 * @param string $pack Pack id.
 * @param array  $meta Pack definition.
 * @return void
 */
function adzworld_starter_rotation_pack( $pack, $meta ) {
	$rotations = adzworld_named_rotations();
	$key       = isset( $meta['rotation'] ) ? $meta['rotation'] : '';
	$exists    = '' !== $key && isset( $rotations[ $key ] );
	$ok        = adzworld_starter_requirements( $meta );
	$keys      = adzworld_pack_rotation_keys( $meta );

	if ( ! empty( $keys ) ) {
		$placements = array_values( $meta['placements'] );

		echo '<ul class="adzworld-starter-placements">';

		foreach ( $keys as $index => $placement_key ) {
			$formats = adzworld_rotation_display_formats();
			$format  = isset( $placements[ $index ]['display'], $formats[ $placements[ $index ]['display'] ] )
				? $formats[ $placements[ $index ]['display'] ]['label']
				: '';
			$size    = isset( $placements[ $index ]['size'] ) ? (string) $placements[ $index ]['size'] : '';

			echo '<li class="adzworld-starter-placement">';
			echo '<span class="adzworld-starter-placement__where">' . esc_html( $placements[ $index ]['where'] ) . '</span>';
			echo '<code class="adzworld-starter-placement__code">' . esc_html( '[adz_rotation name="' . $placement_key . '"]' ) . '</code>';
			echo '<span class="adzworld-starter-placement__settings">';
			if ( '' !== $format && '' !== $size ) {
				printf(
					/* translators: 1: display format name, 2: creative size such as 728x90. */
					esc_html__( 'Rotation settings: %1$s at %2$s.', 'adz-world' ),
					esc_html( $format ),
					esc_html( $size )
				);
			} elseif ( '' !== $format ) {
				printf(
					/* translators: %s: display format name. */
					esc_html__( 'Rotation setting: %s, fluid width.', 'adz-world' ),
					esc_html( $format )
				);
			}
			echo '</span>';
			echo '</li>';
		}

		echo '</ul>';
	}

	if ( $exists ) {
		echo '<p class="adzworld-starter-state">' . esc_html__( 'Set up. The rotation exists and can be edited on the Rotation tab.', 'adz-world' ) . '</p>';
		echo '<p class="adzworld-starter-actions"><a class="button" href="' . esc_url( adzworld_desk_url( 'rotation' ) ) . '">' . esc_html__( 'Edit the rotation', 'adz-world' ) . '</a></p>';

		return;
	}

	if ( ! $ok['ready'] ) {
		echo '<p class="adzworld-starter-state adzworld-starter-state--blocked">';
		printf(
			/* translators: %s: comma-separated list of what is not connected. */
			esc_html__( 'Not connected yet: %s.', 'adz-world' ),
			esc_html( implode( ', ', $ok['missing'] ) )
		);
		echo '</p>';
		echo '<p class="adzworld-starter-actions">';
		echo '<a class="button" href="' . esc_url( adzworld_desk_url( 'networks' ) ) . '">' . esc_html__( 'Connections', 'adz-world' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( adzworld_desk_url( 'affiliates' ) ) . '">' . esc_html__( 'Affiliate programs', 'adz-world' ) . '</a>';
		echo '</p>';

		return;
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="adzworld_create_pack">';
	echo '<input type="hidden" name="pack" value="' . esc_attr( $pack ) . '">';
	wp_nonce_field( 'adzworld_create_pack', 'adzworld_pack_nonce' );
	echo '<p class="adzworld-starter-actions"><button type="submit" class="button button-primary">' . esc_html__( 'Set up this pack', 'adz-world' ) . '</button></p>';
	echo '</form>';
}
