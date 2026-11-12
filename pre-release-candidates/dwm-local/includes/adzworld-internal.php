<?php
/**
 * Internal Adz: the adz a site owner designs and serves to their own audience.
 *
 * Get Paid is external money in. This is the other half: creatives the owner
 * makes themselves, placed in their own rotations, with no third party, no
 * network terms and no revenue share.
 *
 * An internal adz is a WordPress post. Its body is ordinary post content, so
 * an image and a link is a complete adz and nothing more is required. This is
 * not an editor for building HTML; WordPress already has one.
 *
 * Two things the post type did not carry are added here, because a rotation
 * cannot place an adz without them:
 *
 *   size         which of the standard creative sizes this adz is drawn at,
 *                so a slot can reserve the right space and refuse an adz that
 *                does not fit it
 *   destination  where it goes when clicked, so the link can be marked
 *                rel="sponsored" rather than left to whoever wrote the body
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/** Meta key holding the creative size. */
const ADZWORLD_INTERNAL_SIZE_KEY = 'adzworld_size';

/** Meta key holding the click destination. */
const ADZWORLD_INTERNAL_URL_KEY = 'adzworld_destination';

/**
 * Register the two fields an internal adz needs.
 *
 * Registered rather than saved by hand so the REST API, revisions and the
 * block editor all see them, and so the sanitiser runs on every write path
 * rather than only on the one this file provides.
 *
 * @return void
 */
function adzworld_internal_register_meta() {
	register_post_meta(
		'adz_ad',
		ADZWORLD_INTERNAL_SIZE_KEY,
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'adzworld_internal_sanitize_size',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_post_meta(
		'adz_ad',
		ADZWORLD_INTERNAL_URL_KEY,
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'adzworld_internal_sanitize_destination',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'adzworld_internal_register_meta' );

/**
 * Validate a creative size against the rotation's own allowlist.
 *
 * The sizes are the ones the rotation already knows, so an adz can never be
 * drawn at a size no slot can hold. An empty string means fluid: the adz
 * takes whatever width the slot gives it.
 *
 * @param mixed $size Candidate size.
 * @return string Registered size key, or an empty string for fluid.
 */
function adzworld_internal_sanitize_size( $size ) {
	$size = is_scalar( $size ) ? sanitize_text_field( (string) $size ) : '';

	if ( '' === $size || ! function_exists( 'adzworld_rotation_size_presets' ) ) {
		return '';
	}

	$presets = adzworld_rotation_size_presets();

	return isset( $presets[ $size ] ) ? $size : '';
}

/**
 * Validate a click destination.
 *
 * @param mixed $url Candidate URL.
 * @return string A http or https URL, or an empty string.
 */
function adzworld_internal_sanitize_destination( $url ) {
	$url = is_scalar( $url ) ? esc_url_raw( (string) $url, array( 'http', 'https' ) ) : '';

	return $url;
}

/**
 * Apply an internal adz destination to its rendered creative.
 *
 * The destination field is the canonical click target. Existing anchor tags
 * are removed while their contents are retained, preventing invalid nested
 * links and ensuring the sponsored relationship applies to the whole adz.
 *
 * @param string $creative Filtered creative markup.
 * @param int    $ad_id    Adz post id.
 * @return string Linked creative, or the original creative without a destination.
 */
function adzworld_internal_link_creative( $creative, $ad_id ) {
	$destination = adzworld_internal_sanitize_destination(
		get_post_meta( (int) $ad_id, ADZWORLD_INTERNAL_URL_KEY, true )
	);

	if ( '' === $destination || '' === trim( $creative ) ) {
		return $creative;
	}

	$allowed = wp_kses_allowed_html( 'post' );
	unset( $allowed['a'] );
	$creative = wp_kses( $creative, $allowed );

	return '<a class="adzworld-creative__destination" href="' . esc_url( $destination )
		. '" rel="sponsored nofollow">' . $creative . '</a>';
}

/**
 * The size an adz is drawn at, with its pixel dimensions.
 *
 * @param int $ad_id Adz post id.
 * @return array{key:string,label:string,width:int,height:int} Size, zero dimensions when fluid.
 */
function adzworld_internal_size( $ad_id ) {
	$key     = adzworld_internal_sanitize_size( get_post_meta( (int) $ad_id, ADZWORLD_INTERNAL_SIZE_KEY, true ) );
	$presets = function_exists( 'adzworld_rotation_size_presets' ) ? adzworld_rotation_size_presets() : array();

	if ( '' === $key || ! isset( $presets[ $key ] ) ) {
		return array(
			'key'    => '',
			'label'  => __( 'Fluid', 'adz-world' ),
			'width'  => 0,
			'height' => 0,
		);
	}

	return array(
		'key'    => $key,
		'label'  => $presets[ $key ]['label'],
		'width'  => (int) $presets[ $key ]['width'],
		'height' => (int) $presets[ $key ]['height'],
	);
}

/**
 * The two fields, on the adz editing screen.
 *
 * Deliberately two fields and nothing else. The body of the adz is ordinary
 * post content, written in the editor WordPress already provides.
 *
 * @return void
 */
function adzworld_internal_meta_box() {
	add_meta_box(
		'adzworld-internal',
		__( 'Adz placement', 'adz-world' ),
		'adzworld_internal_meta_box_markup',
		'adz_ad',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'adzworld_internal_meta_box' );

/**
 * Render the placement fields.
 *
 * @param WP_Post $post Adz being edited.
 * @return void
 */
function adzworld_internal_meta_box_markup( $post ) {
	wp_nonce_field( 'adzworld_internal_save', 'adzworld_internal_nonce' );

	$size        = adzworld_internal_sanitize_size( get_post_meta( $post->ID, ADZWORLD_INTERNAL_SIZE_KEY, true ) );
	$destination = get_post_meta( $post->ID, ADZWORLD_INTERNAL_URL_KEY, true );
	$presets     = function_exists( 'adzworld_rotation_size_presets' ) ? adzworld_rotation_size_presets() : array();

	echo '<p><label for="adzworld-internal-size"><strong>' . esc_html__( 'Creative size', 'adz-world' ) . '</strong></label><br>';
	echo '<select class="widefat" id="adzworld-internal-size" name="adzworld_internal_size">';
	echo '<option value="">' . esc_html__( 'Fluid — fills the slot', 'adz-world' ) . '</option>';
	foreach ( $presets as $key => $preset ) {
		echo '<option value="' . esc_attr( $key ) . '"' . selected( $size, $key, false ) . '>'
			. esc_html( $preset['label'] . ' — ' . $key ) . '</option>';
	}
	echo '</select>';
	echo '<span class="description">' . esc_html__( 'A rotation slot set to a fixed size holds only adz drawn at that size.', 'adz-world' ) . '</span></p>';

	echo '<p><label for="adzworld-internal-url"><strong>' . esc_html__( 'Destination', 'adz-world' ) . '</strong></label><br>';
	echo '<input type="url" class="widefat code" id="adzworld-internal-url" name="adzworld_internal_url" value="' . esc_attr( (string) $destination ) . '" placeholder="https://">';
	echo '<span class="description">' . esc_html__( 'Where a reader goes when they click. The link is marked as sponsored automatically.', 'adz-world' ) . '</span></p>';
}

/**
 * Save the placement fields.
 *
 * @param int $post_id Adz post id.
 * @return void
 */
function adzworld_internal_save( $post_id ) {
	if ( ! isset( $_POST['adzworld_internal_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['adzworld_internal_nonce'] ) ), 'adzworld_internal_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( 'adz_ad' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$size = isset( $_POST['adzworld_internal_size'] ) && is_string( $_POST['adzworld_internal_size'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_internal_size'] ) )
		: '';
	$url  = isset( $_POST['adzworld_internal_url'] ) && is_string( $_POST['adzworld_internal_url'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_internal_url'] ) )
		: '';

	update_post_meta( $post_id, ADZWORLD_INTERNAL_SIZE_KEY, adzworld_internal_sanitize_size( $size ) );
	update_post_meta( $post_id, ADZWORLD_INTERNAL_URL_KEY, adzworld_internal_sanitize_destination( $url ) );
}
add_action( 'save_post_adz_ad', 'adzworld_internal_save' );

/**
 * Which tags in an adz body will not survive being rendered.
 *
 * An adz body is stored as written, but it is rendered through
 * wp_kses_post(), which is the correct filter and is not being loosened. That
 * means some pasted advertising code works and some silently does not, and an
 * author has no way to tell which without being told.
 *
 *   works    an image and a link, which is what most affiliate banner code
 *            is, including a ClickBank HopLink banner
 *   works    video and audio
 *   removed  script, which is every tag-based network unit: AdSense, Ad
 *            Manager, Taboola and the rest
 *   removed  iframe and svg, which core does not permit in post content
 *
 * A network unit that needs a script belongs on Get Paid, where the plugin
 * builds the tag from stored identifiers instead of a person pasting markup
 * into a post. That is the distinction the WordPress.org review cares about,
 * and the reason this filter stays as it is.
 *
 * @param WP_Post $ad Adz post.
 * @return string[] Tag names that are present in the body and will be removed.
 */
function adzworld_internal_stripped_tags( $ad ) {
	$found = array();

	foreach ( array( 'script', 'iframe', 'svg', 'embed', 'form' ) as $tag ) {
		if ( preg_match( '/<' . $tag . '\b/i', $ad->post_content ) ) {
			$found[] = $tag;
		}
	}

	$rendered = wp_kses_post( $ad->post_content );

	return array_values(
		array_filter(
			$found,
			static function ( $tag ) use ( $rendered ) {
				return ! preg_match( '/<' . $tag . '\b/i', $rendered );
			}
		)
	);
}

/**
 * One adz, rendered as it will actually appear.
 *
 * This is the real thing, not a thumbnail: the adz's own content, through the
 * same wp_kses_post() the rotation uses, inside a box of its declared
 * dimensions. The card scales that box down to fit, so what is on screen is
 * the adz at its true proportions rather than a reflowed approximation.
 *
 * Video is held to what a reader will tolerate and what a phone will play:
 * muted, inline, and not preloaded. An author who writes autoplay with sound
 * gets a muted autoplay, because the alternative is an adz that silently
 * fails on mobile and is hostile everywhere else.
 *
 * @param WP_Post $ad Adz post.
 * @return string Preview markup.
 */
function adzworld_internal_preview( $ad ) {
	$content = wp_kses_post( $ad->post_content );

	$content = preg_replace( '/<video\b(?![^>]*\bmuted\b)/i', '<video muted ', $content );
	$content = preg_replace( '/<video\b(?![^>]*\bplaysinline\b)/i', '<video playsinline ', $content );

	$stripped = adzworld_internal_stripped_tags( $ad );

	if ( '' === trim( wp_strip_all_tags( $content ) ) && ! preg_match( '/<(?:img|picture|video)\b/i', $content ) ) {
		if ( ! empty( $stripped ) ) {
			return '<p class="adz-int-preview__empty">' . esc_html(
				sprintf(
					/* translators: %s: comma-separated list of HTML tag names. */
					__( 'Nothing will render: %s is removed when an adz is served. Code that needs a script belongs on Get Paid.', 'adz-world' ),
					implode( ', ', $stripped )
				)
			) . '</p>';
		}

		return '<p class="adz-int-preview__empty">' . esc_html__( 'This adz has no content yet.', 'adz-world' ) . '</p>';
	}

	return $content;
}

/**
 * Every internal adz, grouped into the categories it was filed under.
 *
 * An adz with no category appears once, in an uncategorised row, rather than
 * being hidden. An adz in two categories appears in both, because that is
 * what filing it in two categories means.
 *
 * @return array<string,array{label:string,slug:string,adz:WP_Post[]}> Rows.
 */
function adzworld_internal_rows() {
	$adz = get_posts(
		array(
			'post_type'        => 'adz_ad',
			'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
			'numberposts'      => 100,
			'orderby'          => 'modified',
			'order'            => 'DESC',
			'suppress_filters' => false,
		)
	);

	$rows         = array();
	$uncatgorised = array();

	foreach ( $adz as $one ) {
		$terms = wp_get_post_terms( $one->ID, 'ad_taxonomy' );
		$terms = is_wp_error( $terms ) ? array() : $terms;

		if ( empty( $terms ) ) {
			$uncatgorised[] = $one;
			continue;
		}

		foreach ( $terms as $term ) {
			if ( ! isset( $rows[ $term->slug ] ) ) {
				$rows[ $term->slug ] = array(
					'label' => $term->name,
					'slug'  => $term->slug,
					'adz'   => array(),
				);
			}
			$rows[ $term->slug ]['adz'][] = $one;
		}
	}

	ksort( $rows );

	if ( ! empty( $uncatgorised ) ) {
		$rows['__none'] = array(
			'label' => __( 'Not filed yet', 'adz-world' ),
			'slug'  => '',
			'adz'   => $uncatgorised,
		);
	}

	return $rows;
}

/**
 * One adz card: the preview, then what a rotation needs to know about it.
 *
 * @param WP_Post $ad Adz post.
 * @return void
 */
function adzworld_internal_card( $ad ) {
	$size        = adzworld_internal_size( $ad->ID );
	$destination = (string) get_post_meta( $ad->ID, ADZWORLD_INTERNAL_URL_KEY, true );
	$published   = 'publish' === $ad->post_status;
	$ready       = $published && '' !== $destination;

	$classes = 'adz-int-card';
	if ( '' !== $size['key'] ) {
		$classes .= ' adz-int-card--' . sanitize_html_class( $size['key'] );
	} else {
		$classes .= ' adz-int-card--fluid';
	}

	echo '<li class="' . esc_attr( $classes ) . '">';

	echo '<div class="adz-int-preview"><div class="adz-int-preview__inner">';

	/*
	 * Filtered at the point of output rather than only inside the function,
	 * so the escaping is visible where the echo is. wp_kses_post() is
	 * idempotent, so running it over already-filtered markup costs a parse and
	 * changes nothing, and the muted/playsinline attributes the preview adds
	 * are permitted on a video element so they survive it.
	 */
	echo wp_kses_post( adzworld_internal_preview( $ad ) );
	echo '</div></div>';

	echo '<div class="adz-int-meta">';
	echo '<p class="adz-int-title">' . esc_html( get_the_title( $ad ) !== '' ? get_the_title( $ad ) : __( '(untitled)', 'adz-world' ) ) . '</p>';

	echo '<p class="adz-int-facts">';
	echo '<span class="adz-int-size">' . esc_html( '' !== $size['key'] ? $size['key'] : __( 'fluid', 'adz-world' ) ) . '</span>';
	if ( ! $published ) {
		echo '<span class="adz-int-flag adz-int-flag--draft">' . esc_html__( 'draft', 'adz-world' ) . '</span>';
	}
	if ( '' === $destination ) {
		echo '<span class="adz-int-flag adz-int-flag--nolink">' . esc_html__( 'no destination', 'adz-world' ) . '</span>';
	}

	$stripped = adzworld_internal_stripped_tags( $ad );
	if ( ! empty( $stripped ) ) {
		echo '<span class="adz-int-flag adz-int-flag--stripped" title="' . esc_attr(
			sprintf(
				/* translators: %s: comma-separated list of HTML tag names. */
				__( 'These tags are removed when the adz is served: %s. A unit that needs a script belongs on Get Paid.', 'adz-world' ),
				implode( ', ', $stripped )
			)
		) . '">';
		printf(
			/* translators: %s: comma-separated list of HTML tag names. */
			esc_html__( '%s removed', 'adz-world' ),
			esc_html( implode( ', ', $stripped ) )
		);
		echo '</span>';
	}
	if ( $ready ) {
		echo '<span class="adz-int-flag adz-int-flag--ready">' . esc_html__( 'ready', 'adz-world' ) . '</span>';
	}
	echo '</p>';

	echo '<p class="adz-int-actions"><a class="button button-small" href="' . esc_url( (string) get_edit_post_link( $ad->ID ) ) . '">'
		. esc_html__( 'Edit', 'adz-world' ) . '</a></p>';
	echo '</div>';

	echo '</li>';
}

/**
 * Render the Internal Adz screen.
 *
 * One horizontal row per category. A row scrolls sideways rather than
 * wrapping, so a category stays one readable line of adz however many it
 * holds, and the categories themselves stay comparable down the page.
 *
 * @return void
 */
function adzworld_internal_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$rows  = adzworld_internal_rows();
	$total = 0;
	$ready = 0;
	foreach ( $rows as $row ) {
		foreach ( $row['adz'] as $ad ) {
			++$total;
			if ( 'publish' === $ad->post_status && '' !== (string) get_post_meta( $ad->ID, ADZWORLD_INTERNAL_URL_KEY, true ) ) {
				++$ready;
			}
		}
	}

	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Internal Adz', 'adz-world' ) . '</h1>';
	echo ' <a href="' . esc_url( admin_url( 'post-new.php?post_type=adz_ad' ) ) . '" class="page-title-action">'
		. esc_html__( 'Add an adz', 'adz-world' ) . '</a>';
	echo '<hr class="wp-header-end">';

	echo '<p class="description adz-int-intro">' . esc_html__( 'Adz you design and serve to your own audience. No network, no revenue share. An image and a link is a complete adz; the body is ordinary post content.', 'adz-world' ) . '</p>';

	if ( 0 === $total ) {
		echo '<div class="notice notice-info inline"><p>'
			. esc_html__( 'No internal adz yet. Add one, give it a size and a destination, then place it in a rotation.', 'adz-world' )
			. '</p></div>';
		return;
	}

	echo '<p class="adz-int-summary">';
	printf(
		/* translators: 1: adz ready to serve, 2: total adz. */
		esc_html__( '%1$d of %2$d ready to serve. An adz is ready when it is published and has a destination.', 'adz-world' ),
		absint( $ready ),
		absint( $total )
	);
	echo '</p>';

	foreach ( $rows as $row ) {
		echo '<section class="adz-int-row">';
		echo '<h2 class="adz-int-row__head">' . esc_html( $row['label'] );
		echo ' <span class="adz-int-row__count">' . esc_html( (string) count( $row['adz'] ) ) . '</span></h2>';
		echo '<ul class="adz-int-strip">';
		foreach ( $row['adz'] as $ad ) {
			adzworld_internal_card( $ad );
		}
		echo '</ul>';
		echo '</section>';
	}
}
