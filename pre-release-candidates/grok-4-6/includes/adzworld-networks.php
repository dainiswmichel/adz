<?php
/**
 * Publisher ad-slot registry.
 *
 * A connected network is rendered with that network's own tag, once, in the
 * visible stage. Purchase notes are not added to those requests. Networks
 * that take over the whole page, or that open a pop-up, are named here and
 * are not given a tag.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slot networks this candidate can render, then networks it will not wrap.
 *
 * @return array<string,array<string,mixed>>
 */
function adzworld_network_catalog() {
	$slot = array(
		'adsense' => array(
			'label'   => 'Google AdSense',
			'kind'    => 'slot',
			'host'    => 'pagead2.googlesyndication.com',
			'privacy' => 'https://policies.google.com/privacy',
			'terms'   => 'https://www.google.com/adsense/terms',
			'note'    => __( 'The official ad unit is used. The script loads from pagead2.googlesyndication.com, and one request runs when the unit is the visible item.', 'adz-world' ),
			'fields'  => array(
				'client' => array(
					'label'    => __( 'Publisher id', 'adz-world' ),
					'pattern'  => '/^ca-pub-[0-9]{10,22}$/',
					'required' => true,
				),
				'slot'   => array(
					'label'    => __( 'Ad unit id', 'adz-world' ),
					'pattern'  => '/^[0-9]{6,20}$/',
					'required' => true,
				),
			),
		),
		'gam'     => array(
			'label'   => 'Google Ad Manager',
			'kind'    => 'slot',
			'host'    => 'securepubads.g.doubleclick.net',
			'privacy' => 'https://policies.google.com/privacy',
			'terms'   => 'https://admanager.google.com/home/terms/',
			'note'    => __( 'The official Google Publisher Tag is used. The script loads from securepubads.g.doubleclick.net when this unit is on screen.', 'adz-world' ),
			'fields'  => array(
				'path'   => array(
					'label'    => __( 'Ad unit path', 'adz-world' ),
					'pattern'  => '#^/[0-9]{3,15}(/[A-Za-z0-9._~-]{1,64}){1,6}$#',
					'required' => true,
				),
				'width'  => array(
					'label'    => __( 'Width', 'adz-world' ),
					'pattern'  => '/^[0-9]{2,4}$/',
					'min'      => 50,
					'max'      => 1200,
					'required' => true,
				),
				'height' => array(
					'label'    => __( 'Height', 'adz-world' ),
					'pattern'  => '/^[0-9]{2,4}$/',
					'min'      => 50,
					'max'      => 1200,
					'required' => true,
				),
			),
		),
		'taboola' => array(
			'label'   => 'Taboola',
			'kind'    => 'slot',
			'host'    => 'cdn.taboola.com',
			'privacy' => 'https://www.taboola.com/policies/privacy-policy',
			'terms'   => 'https://www.taboola.com/policies/publisher-terms-and-conditions',
			'note'    => __( 'A thumbnail widget. The loader is cdn.taboola.com and it is requested when this slide is the one on screen.', 'adz-world' ),
			'fields'  => array(
				'publisher' => array(
					'label'    => __( 'Publisher id', 'adz-world' ),
					'pattern'  => '/^[a-z0-9][a-z0-9-]{1,48}$/',
					'required' => true,
				),
				'mode'      => array(
					'label'    => __( 'Widget mode', 'adz-world' ),
					'choices'  => array( 'thumbnails-a', 'alternating-thumbnails-a' ),
					'required' => true,
				),
				'placement' => array(
					'label'    => __( 'Placement name', 'adz-world' ),
					'pattern'  => '/^[A-Za-z0-9][A-Za-z0-9 _-]{0,79}$/',
					'required' => true,
				),
			),
		),
	);

	$outside = array(
		'medianet'   => array(
			'label'  => 'Media.net',
			'kind'   => 'outside',
			'reason' => __( 'The publisher tag is a generated snippet. This candidate does not invent that snippet.', 'adz-world' ),
		),
		'carbon'     => array(
			'label'  => 'Carbon',
			'kind'   => 'outside',
			'reason' => __( 'The creative replaces the script element, so a footer script would not fill this slot.', 'adz-world' ),
		),
		'mgid'       => array(
			'label'  => 'MGID',
			'kind'   => 'outside',
			'reason' => __( 'The site script is a path, not one checked id.', 'adz-world' ),
		),
		'infolinks'  => array(
			'label'  => 'Infolinks',
			'kind'   => 'outside',
			'reason' => __( 'Infolinks writes into article text. It is not a slot in this rotation.', 'adz-world' ),
		),
		'aps'        => array(
			'label'  => 'Amazon Publisher Services',
			'kind'   => 'outside',
			'reason' => __( 'This is header bidding, not a creative in the rotation.', 'adz-world' ),
		),
		'teads'      => array(
			'label'  => 'Teads (includes the former Outbrain)',
			'kind'   => 'outside',
			'reason' => __( 'The publisher loader changed when Outbrain took the Teads name. No old Outbrain URL is shipped.', 'adz-world' ),
		),
		'mediavine'  => array(
			'label'  => 'Mediavine',
			'kind'   => 'outside',
			'reason' => __( 'The contract places tags for the whole page. Those tags are not put inside a rotator.', 'adz-world' ),
		),
		'raptive'    => array(
			'label'  => 'Raptive',
			'kind'   => 'outside',
			'reason' => __( 'The contract places tags for the whole page. Those tags are not put inside a rotator.', 'adz-world' ),
		),
		'ezoic'      => array(
			'label'  => 'Ezoic',
			'kind'   => 'outside',
			'reason' => __( 'The contract places tags for the whole page. Those tags are not put inside a rotator.', 'adz-world' ),
		),
		'monumetric' => array(
			'label'  => 'Monumetric',
			'kind'   => 'outside',
			'reason' => __( 'The contract places tags for the whole page. Those tags are not put inside a rotator.', 'adz-world' ),
		),
		'publift'    => array(
			'label'  => 'Publift',
			'kind'   => 'outside',
			'reason' => __( 'The contract places tags for the whole page. Those tags are not put inside a rotator.', 'adz-world' ),
		),
		'propeller'  => array(
			'label'  => 'PropellerAds',
			'kind'   => 'outside',
			'reason' => __( 'Pop and push formats are not a content slot.', 'adz-world' ),
		),
		'adsterra'   => array(
			'label'  => 'Adsterra',
			'kind'   => 'outside',
			'reason' => __( 'Pop and social-bar formats are not a content slot.', 'adz-world' ),
		),
		'monetag'    => array(
			'label'  => 'Monetag',
			'kind'   => 'outside',
			'reason' => __( 'Pop and push formats are not a content slot.', 'adz-world' ),
		),
	);

	return array_merge( $slot, $outside );
}

/**
 * Ids of networks that can occupy the stage.
 *
 * @return string[]
 */
function adzworld_slot_network_ids() {
	$ids = array();

	foreach ( adzworld_network_catalog() as $id => $network ) {
		if ( isset( $network['kind'] ) && 'slot' === $network['kind'] ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

/**
 * Keep one posted field inside the catalog rule.
 *
 * @param array<string,mixed> $field Field rule.
 * @param mixed               $raw   Posted value.
 * @return string
 */
function adzworld_sanitize_network_field( $field, $raw ) {
	$value = sanitize_text_field( is_scalar( $raw ) ? (string) $raw : '' );

	if ( isset( $field['choices'] ) && is_array( $field['choices'] ) ) {
		return in_array( $value, $field['choices'], true ) ? $value : '';
	}

	if ( empty( $field['pattern'] ) || 1 !== preg_match( $field['pattern'], $value ) ) {
		return '';
	}

	if ( isset( $field['min'], $field['max'] ) ) {
		$number = (int) $value;

		if ( $number < (int) $field['min'] || $number > (int) $field['max'] ) {
			return '';
		}
	}

	return $value;
}

/**
 * Stored network rows. An enabled row has passed its field rules and the acceptance box.
 *
 * @param mixed $raw Option or posted array.
 * @return array<string,array<string,string>>
 */
function adzworld_sanitize_network_settings( $raw ) {
	$clean = array();

	if ( ! is_array( $raw ) ) {
		$raw = array();
	}

	foreach ( adzworld_slot_network_ids() as $id ) {
		$catalog = adzworld_network_catalog();
		$network = $catalog[ $id ];
		$row     = ( isset( $raw[ $id ] ) && is_array( $raw[ $id ] ) ) ? $raw[ $id ] : array();
		$fields  = array();
		$ok      = true;

		foreach ( $network['fields'] as $key => $field ) {
			$value          = adzworld_sanitize_network_field( $field, isset( $row[ $key ] ) ? $row[ $key ] : '' );
			$fields[ $key ] = $value;

			if ( '' === $value ) {
				$ok = false;
			}
		}

		$accepted = ( isset( $row['accepted'] ) && '1' === (string) $row['accepted'] ) ? '1' : '0';
		$enabled  = ( $ok && '1' === $accepted && isset( $row['enabled'] ) && '1' === (string) $row['enabled'] ) ? '1' : '0';

		$clean[ $id ] = array_merge(
			array(
				'enabled'  => $enabled,
				'accepted' => $accepted,
			),
			$fields
		);
	}

	return $clean;
}

/**
 * Saved slot settings, sanitized again on read.
 *
 * @return array<string,array<string,string>>
 */
function adzworld_network_settings() {
	$saved = get_option( 'adzworld_networks', array() );

	return adzworld_sanitize_network_settings( $saved );
}

/**
 * The plugin main file, used to build asset URLs.
 *
 * @return string
 */
function adzworld_plugin_file() {
	return dirname( __DIR__ ) . '/adz-world.php';
}

/**
 * Add the crossorigin attribute the AdSense tag documents.
 *
 * @param string $tag    Script tag.
 * @param string $handle Handle.
 * @return string
 */
function adzworld_network_script_tag( $tag, $handle ) {
	if ( 'adzworld-adsense' === $handle ) {
		return str_replace( ' src=', ' crossorigin="anonymous" src=', $tag );
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'adzworld_network_script_tag', 10, 2 );

/**
 * Enqueue one network library. Taboola also needs the container id so the
 * queue entry exists before the loader runs.
 *
 * @param string $id           Network id.
 * @param string $container_id Taboola container, when this slide is visible now.
 * @return void
 */
function adzworld_enqueue_network( $id, $container_id = '' ) {
	static $done = array();

	if ( isset( $done[ $id ] ) ) {
		return;
	}

	$settings = adzworld_network_settings();

	if ( empty( $settings[ $id ] ) || '1' !== $settings[ $id ]['enabled'] ) {
		return;
	}

	$row         = $settings[ $id ];
	$done[ $id ] = true;

	if ( 'adsense' === $id ) {
		wp_enqueue_script(
			'adzworld-adsense',
			add_query_arg( 'client', $row['client'], 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js' ),
			array(),
			'1.1.0',
			true
		);
		return;
	}

	if ( 'gam' === $id ) {
		wp_enqueue_script(
			'adzworld-gpt',
			'https://securepubads.g.doubleclick.net/tag/js/gpt.js',
			array(),
			'1.1.0',
			true
		);
		return;
	}

	if ( 'taboola' !== $id || '' === $container_id ) {
		return;
	}

	$src = 'https://cdn.taboola.com/libtrc/' . rawurlencode( $row['publisher'] ) . '/loader.js';

	wp_enqueue_script( 'adzworld-taboola', $src, array(), '1.1.0', true );
	wp_add_inline_script(
		'adzworld-taboola',
		'window._taboola = window._taboola || []; window._taboola.push( ' . wp_json_encode(
			array(
				'mode'        => $row['mode'],
				'container'   => $container_id,
				'placement'   => $row['placement'],
				'target_type' => 'mix',
			)
		) . ' );',
		'before'
	);
}

/**
 * Markup for one network unit.
 *
 * @param string $id         Network id.
 * @param bool   $mount_now  Whether this unit is the visible item on this serve.
 * @param bool   $load_script Whether the library should load with this page.
 * @return string
 */
function adzworld_network_slide_html( $id, $mount_now, $load_script ) {
	$settings = adzworld_network_settings();

	if ( empty( $settings[ $id ] ) || '1' !== $settings[ $id ]['enabled'] ) {
		return '';
	}

	$row = $settings[ $id ];

	if ( 'adsense' === $id ) {
		if ( $load_script ) {
			adzworld_enqueue_network( 'adsense' );
		}

		if ( $mount_now ) {
			return '<ins class="adsbygoogle" style="display:block" data-ad-client="' . esc_attr( $row['client'] ) . '" data-ad-slot="' . esc_attr( $row['slot'] ) . '" data-ad-format="auto" data-full-width-responsive="true" data-adzworld-mount="adsense"></ins>';
		}

		return '<div class="adzworld-unit" data-adzworld-mount="adsense" data-client="' . esc_attr( $row['client'] ) . '" data-slot="' . esc_attr( $row['slot'] ) . '"></div>';
	}

	if ( 'gam' === $id ) {
		$div_id = wp_unique_id( 'adzworld-gpt-' );

		if ( $load_script ) {
			adzworld_enqueue_network( 'gam' );
		}

		return '<div id="' . esc_attr( $div_id ) . '" class="adzworld-unit" data-adzworld-mount="gam" data-path="' . esc_attr( $row['path'] ) . '" data-width="' . esc_attr( $row['width'] ) . '" data-height="' . esc_attr( $row['height'] ) . '"></div>';
	}

	if ( 'taboola' === $id ) {
		$div_id = wp_unique_id( 'adzworld-taboola-' );

		if ( $load_script && $mount_now ) {
			adzworld_enqueue_network( 'taboola', $div_id );
		}

		return '<div id="' . esc_attr( $div_id ) . '" class="adzworld-unit" data-adzworld-mount="taboola" data-publisher="' . esc_attr( $row['publisher'] ) . '" data-mode="' . esc_attr( $row['mode'] ) . '" data-placement="' . esc_attr( $row['placement'] ) . '"' . ( $mount_now ? ' data-adzworld-mounted="1"' : '' ) . '></div>';
	}

	return '';
}

/**
 * Ad slot screen.
 *
 * @return void
 */
function adzworld_network_menu() {
	add_submenu_page(
		'edit.php?post_type=adz_ad',
		__( 'Ad slot', 'adz-world' ),
		__( 'Ad slot', 'adz-world' ),
		'manage_options',
		'adzworld-slot',
		'adzworld_render_network_screen'
	);
}
add_action( 'admin_menu', 'adzworld_network_menu' );

/**
 * Print the ad slot screen.
 *
 * @return void
 */
function adzworld_render_network_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = adzworld_network_settings();
	$notice   = 'adzworld_slot_saved_' . get_current_user_id();

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Ad slot', 'adz-world' ) . '</h1>';

	if ( get_transient( $notice ) ) {
		delete_transient( $notice );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'adz-world' ) . '</p></div>';
	}

	echo '<p>' . esc_html__( 'The stage shows one connected unit with that network’s own tag. Every 10th serve, unless the shortcode sets another count, shows an adz instead. The first of those is an introduction where this access can write what they would like to purchase. That note stays on this site.', 'adz-world' ) . '</p>';
	echo '<p><code>[adzworld_stage display="slot" every="10"]</code> <code>[adzworld_stage display="flat" every="10"]</code> <code>[adzworld_stage display="3d" every="10"]</code></p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="adzworld_save_networks" />';
	wp_nonce_field( 'adzworld_save_networks', 'adzworld_network_nonce' );

	foreach ( adzworld_network_catalog() as $id => $network ) {
		echo '<h2>' . esc_html( $network['label'] ) . '</h2>';

		if ( 'outside' === $network['kind'] ) {
			echo '<p>' . esc_html( $network['reason'] ) . '</p>';
			continue;
		}

		$row = isset( $settings[ $id ] ) ? $settings[ $id ] : array();

		echo '<p>' . esc_html( $network['note'] ) . '</p>';
		echo '<p><a href="' . esc_url( $network['terms'] ) . '">' . esc_html__( 'Network terms', 'adz-world' ) . '</a> · <a href="' . esc_url( $network['privacy'] ) . '">' . esc_html__( 'Network privacy', 'adz-world' ) . '</a></p>';

		foreach ( $network['fields'] as $key => $field ) {
			$value = isset( $row[ $key ] ) ? $row[ $key ] : '';
			$name  = 'adzworld_networks[' . $id . '][' . $key . ']';

			echo '<p><label>' . esc_html( $field['label'] ) . '<br />';

			if ( isset( $field['choices'] ) && is_array( $field['choices'] ) ) {
				echo '<select name="' . esc_attr( $name ) . '">';

				foreach ( $field['choices'] as $choice ) {
					echo '<option value="' . esc_attr( $choice ) . '"' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
				}

				echo '</select>';
			} else {
				echo '<input type="text" class="regular-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
			}

			echo '</label></p>';
		}

		echo '<p><label><input type="checkbox" name="' . esc_attr( 'adzworld_networks[' . $id . '][accepted]' ) . '" value="1"' . checked( isset( $row['accepted'] ) ? $row['accepted'] : '', '1', false ) . ' /> ' . esc_html__( 'I have an account on this network and I accept that a visible unit loads from that host. Purchase notes are not sent there.', 'adz-world' ) . '</label></p>';
		echo '<p><label><input type="checkbox" name="' . esc_attr( 'adzworld_networks[' . $id . '][enabled]' ) . '" value="1"' . checked( isset( $row['enabled'] ) ? $row['enabled'] : '', '1', false ) . ' /> ' . esc_html__( 'Use this unit in the stage.', 'adz-world' ) . '</label></p>';
	}

	submit_button( __( 'Save ad slot', 'adz-world' ) );
	echo '</form></div>';
}

/**
 * Save the ad slot screen.
 *
 * @return void
 */
function adzworld_save_networks() {
	check_admin_referer( 'adzworld_save_networks', 'adzworld_network_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to manage these settings.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$raw = isset( $_POST['adzworld_networks'] ) ? map_deep( wp_unslash( $_POST['adzworld_networks'] ), 'sanitize_text_field' ) : array();

	update_option( 'adzworld_networks', adzworld_sanitize_network_settings( $raw ) );
	set_transient( 'adzworld_slot_saved_' . get_current_user_id(), '1', MINUTE_IN_SECONDS );

	wp_safe_redirect( admin_url( 'edit.php?post_type=adz_ad&page=adzworld-slot' ) );
	exit;
}
add_action( 'admin_post_adzworld_save_networks', 'adzworld_save_networks' );
