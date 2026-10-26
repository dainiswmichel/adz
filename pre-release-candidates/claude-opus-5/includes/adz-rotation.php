<?php
/**
 * Rotation element: adz and the publisher's existing ad networks, in one slot.
 *
 * The design decision here is to integrate with no ad network at all.
 *
 * A publisher already has ad code on their site -- AdSense, Media.net, Sovrn,
 * Mediavine, Raptive, Monetag, whatever they qualified for. Writing an
 * integration per network would mean an account per network, each revocable by
 * its owner, and the plugin would stop working the day any of them said so.
 *
 * Instead a publisher registers their existing ad code as a named slot, once,
 * and the rotation element cycles that slot together with adz matched to what
 * the visitor asked for. It works with every network because it knows about
 * none of them, and nothing an ad network does can switch it off.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Networks offered as presets in the settings screen.
 *
 * These are labels and documentation links only. No preset contains an API
 * call, a key, or anything the named company has to agree to. A publisher
 * pastes the code their own account gave them.
 *
 * @return array<string,array{label:string,note:string}>
 */
function adzworld_network_presets() {
	return array(
		'adsense'   => array(
			'label' => __( 'Google AdSense', 'adz-world' ),
			'note'  => __( 'No traffic minimum. Paste the ad unit code from your AdSense account.', 'adz-world' ),
		),
		'gam'       => array(
			'label' => __( 'Google Ad Manager', 'adz-world' ),
			'note'  => __( 'Paste the GPT slot code for one ad unit.', 'adz-world' ),
		),
		'media_net' => array(
			'label' => __( 'Media.net', 'adz-world' ),
			'note'  => __( 'Quality review rather than a traffic minimum.', 'adz-world' ),
		),
		'sovrn'     => array(
			'label' => __( 'Sovrn', 'adz-world' ),
			'note'  => __( 'Quality review. Aimed at small and mid-sized publishers.', 'adz-world' ),
		),
		'mediavine' => array(
			'label' => __( 'Mediavine', 'adz-world' ),
			'note'  => __( 'Around 50,000 sessions a month. Check their current threshold.', 'adz-world' ),
		),
		'raptive'   => array(
			'label' => __( 'Raptive', 'adz-world' ),
			'note'  => __( 'Around 25,000 monthly pageviews, weighted to certain countries. Check their current threshold.', 'adz-world' ),
		),
		'ezoic'     => array(
			'label' => __( 'Ezoic', 'adz-world' ),
			'note'  => __( 'Raised its minimum substantially in 2026. Check their current threshold.', 'adz-world' ),
		),
		'monetag'   => array(
			'label' => __( 'Monetag', 'adz-world' ),
			'note'  => __( 'No traffic minimum.', 'adz-world' ),
		),
		'adsterra'  => array(
			'label' => __( 'Adsterra', 'adz-world' ),
			'note'  => __( 'No traffic minimum.', 'adz-world' ),
		),
		'custom'    => array(
			'label' => __( 'Any other network', 'adz-world' ),
			'note'  => __( 'Paste any ad code. The rotation does not need to know whose it is.', 'adz-world' ),
		),
	);
}

/**
 * The publisher's registered network slots.
 *
 * @return array<string,array{label:string,code:string}>
 */
function adzworld_get_network_slots() {
	$slots = get_option( 'adzworld_network_slots', array() );

	return is_array( $slots ) ? $slots : array();
}

/**
 * Save a network slot.
 *
 * Ad code is markup and script, so saving it requires unfiltered_html -- the
 * capability WordPress already uses for exactly this. On multisite that is
 * super admins only, which is correct: nobody below that should be able to put
 * arbitrary script into every visitor's page.
 *
 * @param string $key   Slot key.
 * @param string $label Human label.
 * @param string $code  The ad code as the network supplied it.
 * @return true|WP_Error
 */
function adzworld_save_network_slot( $key, $label, $code ) {
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		return new WP_Error(
			'adzworld_forbidden',
			__( 'Saving ad code requires the unfiltered_html capability.', 'adz-world' ),
			array( 'status' => 403 )
		);
	}

	$key = sanitize_key( $key );

	if ( '' === $key ) {
		return new WP_Error( 'adzworld_bad_slot', __( 'A slot needs a name.', 'adz-world' ) );
	}

	$slots = adzworld_get_network_slots();

	$slots[ $key ] = array(
		'label' => sanitize_text_field( $label ),
		'code'  => (string) $code,
	);

	update_option( 'adzworld_network_slots', $slots, false );

	return true;
}

/**
 * Order the rotation: the publisher's adz in the order they registered them,
 * with any matched adz spaced evenly between them.
 *
 * Spacing rather than appending matters. An adz tacked on the end of a carousel
 * is seen by nobody, and an adz placed first displaces the position the
 * publisher is already paid for, so each slot is emitted before the adz that
 * follows it.
 *
 * @param array $network Publisher slots, each renderable once.
 * @param array $adz     Matched adz for this render.
 * @param int   $cap     Hard cap on positions.
 * @return array
 */
function adzworld_build_rotation( $network, $adz, $cap ) {
	if ( empty( $adz ) ) {
		return array_slice( $network, 0, $cap );
	}

	if ( empty( $network ) ) {
		return array_slice( $adz, 0, $cap );
	}

	/*
	 * Both lists are trimmed to fit the cap before spacing, so a publisher with
	 * more registered slots than positions does not push the adz off the end.
	 */
	$adz     = array_slice( $adz, 0, max( 1, (int) floor( $cap / 2 ) ) );
	$network = array_slice( $network, 0, max( 1, $cap - count( $adz ) ) );
	$total   = count( $adz );
	$stride  = count( $network ) / ( $total + 1 );
	$slides  = array();
	$placed  = 0;

	foreach ( $network as $index => $slot ) {
		$slides[] = $slot;

		if ( $placed < $total && $index + 1 >= ( $placed + 1 ) * $stride ) {
			$slides[] = $adz[ $placed ];
			++$placed;
		}
	}

	while ( $placed < $total ) {
		$slides[] = $adz[ $placed ];
		++$placed;
	}

	return array_slice( $slides, 0, $cap );
}

add_shortcode( 'adz_rotation', 'adzworld_rotation_shortcode' );
/**
 * [adz_rotation] - the rotation element.
 *
 * Attributes:
 *   display   carousel-3d | carousel-flat | stack | single
 *   controls  arrows | dots | both | none
 *   slots     comma-separated registered network slot keys, in rotation order
 *   every     one render in N carries an adz; 0 serves the publisher's adz only
 *   count     how many adz to carry on a render that carries any
 *   slides    hard cap on positions in the rotation, 1-24
 *   panel     yes | no - show the permission panel
 *   intro     full | short | none - how much the panel explains
 *   interval  seconds between automatic advances, 0 to disable
 *
 * With every="0" and one registered slot this serves exactly what the publisher
 * serves now, in the same place, through their own account, and the plugin only
 * presents it. Raising "every" adds an adz the visitor asked for to one render
 * in N, and nothing about the publisher's own adz changes.
 *
 * The one-in-N roll happens on the server, so a full-page cache will cache the
 * roll along with the page. On a cached site the honest way to run this is one
 * adz per render ( every="1" ) with the rotation itself doing the spacing, or a
 * cache plugin configured to leave this shortcode uncached.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function adzworld_rotation_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'display'  => 'carousel-flat',
			'controls' => 'both',
			'slots'    => '',
			'every'    => 10,
			'count'    => 1,
			'slides'   => 12,
			'panel'    => 'yes',
			'intro'    => 'full',
			'interval' => 0,
		),
		$atts,
		'adz_rotation'
	);

	$display   = in_array( $atts['display'], array( 'carousel-3d', 'carousel-flat', 'stack', 'single' ), true ) ? $atts['display'] : 'carousel-flat';
	$controls  = in_array( $atts['controls'], array( 'arrows', 'dots', 'both', 'none' ), true ) ? $atts['controls'] : 'both';
	$intro     = in_array( $atts['intro'], array( 'full', 'short', 'none' ), true ) ? $atts['intro'] : 'full';
	$every     = max( 0, min( 1000, (int) $atts['every'] ) );
	$count     = max( 1, min( 12, (int) $atts['count'] ) );
	$positions = max( 1, min( 24, (int) $atts['slides'] ) );
	$interval  = max( 0, min( 120, (int) $atts['interval'] ) );

	$pref     = adzworld_current_preference();
	$has_pref = adzworld_has_preference( $pref );

	// The publisher's existing network ad code, exactly as their account gave
	// it. Each registered slot renders at most once per page: a network keys its
	// own code on a slot id and will not serve the same id twice.
	$registered = adzworld_get_network_slots();
	$network    = array();

	foreach ( array_filter( array_map( 'sanitize_key', explode( ',', $atts['slots'] ) ) ) as $key ) {
		if ( isset( $registered[ $key ] ) ) {
			$network[] = array(
				'type'  => 'network',
				'label' => $registered[ $key ]['label'],
				'html'  => $registered[ $key ]['code'],
			);
		}
	}

	/*
	 * Adz are matched only against a preference the visitor set. With the panel
	 * untouched there is nothing to match, so an untouched rotation is the
	 * publisher's own adz and nothing else.
	 */
	$adz = array();

	if ( $every > 0 && $has_pref && 1 === wp_rand( 1, $every ) ) {
		foreach ( adzworld_match_adz( $pref, $count ) as $ad_id ) {
			$ad = get_post( $ad_id );

			if ( ! $ad instanceof WP_Post ) {
				continue;
			}

			$adz[] = array(
				'type'  => 'adz',
				'label' => $ad->post_title,
				'html'  => apply_filters( 'adzworld_adz_content', wp_kses_post( $ad->post_content ), $ad_id ),
			);
		}
	}

	$slides = adzworld_build_rotation( $network, $adz, $positions );

	if ( empty( $slides ) && 'yes' !== $atts['panel'] ) {
		return '';
	}

	adzworld_enqueue_rotation_assets();

	$id = wp_unique_id( 'adz-rotation-' );

	ob_start();
	?>
	<div
		class="adz-rotation adz-rotation--<?php echo esc_attr( $display ); ?>"
		id="<?php echo esc_attr( $id ); ?>"
		data-adz-interval="<?php echo esc_attr( (string) $interval ); ?>"
		role="region"
		aria-roledescription="carousel"
		aria-label="<?php esc_attr_e( 'Advertising', 'adz-world' ); ?>"
	>
		<?php if ( 'yes' === $atts['panel'] ) : ?>
			<?php adzworld_render_preference_panel( $pref, $has_pref, $intro ); ?>
		<?php endif; ?>

		<?php if ( ! empty( $slides ) ) : ?>
			<div class="adz-rotation__viewport">
				<div class="adz-rotation__track">
					<?php foreach ( $slides as $index => $slide ) : ?>
						<div
							class="adz-rotation__slide adz-rotation__slide--<?php echo esc_attr( $slide['type'] ); ?>"
							role="group"
							aria-roledescription="slide"
							aria-label="
							<?php
							echo esc_attr(
								sprintf(
									/* translators: 1: slide number, 2: total slides. */
									__( '%1$d of %2$d', 'adz-world' ),
									$index + 1,
									count( $slides )
								)
							);
							?>
							"
						>
							<?php
							/*
							 * aria-hidden is set by the script once it has taken
							 * over presentation. Without the script every slide
							 * stays visible, so marking any of them hidden here
							 * would describe the page wrongly.
							 */
							?>
							<?php if ( 'adz' === $slide['type'] ) : ?>
								<div class="adz-rotation__body"><?php echo wp_kses_post( $slide['html'] ); ?></div>
								<p class="adz-rotation__why">
									<?php esc_html_e( 'Shown because you asked for it. Nothing followed you here.', 'adz-world' ); ?>
								</p>
							<?php else : ?>
								<div class="adz-rotation__body adz-rotation__body--network">
									<?php
									/*
									 * The publisher's own ad code, saved by a user with
									 * unfiltered_html. Filtering it would break every
									 * network's script, which is the point of the slot.
									 */
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- publisher ad code, gated on unfiltered_html at save time.
									echo $slide['html'];
									?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( in_array( $controls, array( 'arrows', 'both' ), true ) && count( $slides ) > 1 ) : ?>
				<button type="button" class="adz-rotation__arrow adz-rotation__arrow--prev" aria-controls="<?php echo esc_attr( $id ); ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Previous', 'adz-world' ); ?></span>
					<span aria-hidden="true">&#8249;</span>
				</button>
				<button type="button" class="adz-rotation__arrow adz-rotation__arrow--next" aria-controls="<?php echo esc_attr( $id ); ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Next', 'adz-world' ); ?></span>
					<span aria-hidden="true">&#8250;</span>
				</button>
			<?php endif; ?>

			<?php if ( in_array( $controls, array( 'dots', 'both' ), true ) && count( $slides ) > 1 ) : ?>
				<div class="adz-rotation__dots" role="tablist" aria-label="<?php esc_attr_e( 'Choose a slide', 'adz-world' ); ?>">
					<?php foreach ( $slides as $index => $slide ) : ?>
						<button
							type="button"
							class="adz-rotation__dot adz-rotation__dot--<?php echo esc_attr( $slide['type'] ); ?>"
							role="tab"
							aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
							data-adz-index="<?php echo esc_attr( (string) $index ); ?>"
						>
							<span class="screen-reader-text">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: slide number. */
										__( 'Slide %d', 'adz-world' ),
										$index + 1
									)
								);
								?>
							</span>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Register and enqueue the rotation stylesheet and script.
 *
 * @return void
 */
function adzworld_enqueue_rotation_assets() {
	if ( wp_script_is( 'adzworld-rotation', 'enqueued' ) ) {
		return;
	}

	wp_enqueue_style(
		'adzworld-rotation',
		plugins_url( 'assets/css/adz-rotation.css', ADZ_WORLD . 'adz-world.php' ),
		array(),
		ADZ_WORLD_VERSION
	);

	wp_enqueue_script(
		'adzworld-rotation',
		plugins_url( 'assets/js/adz-rotation.js', ADZ_WORLD . 'adz-world.php' ),
		array(),
		ADZ_WORLD_VERSION,
		true
	);

	wp_localize_script(
		'adzworld-rotation',
		'adzWorldRotation',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'adzworld_preference' ),
		)
	);
}
