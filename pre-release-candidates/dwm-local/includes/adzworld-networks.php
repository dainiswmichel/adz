<?php
/**
 * Existing publisher ad-network connections.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ad networks with an official unit that can occupy a rotation slot.
 *
 * @return array<string,array<string,mixed>>
 */
function adzworld_network_catalog() {
	return array(
		'adsense'  => array(
			'label'   => 'Google AdSense',
			'host'    => 'pagead2.googlesyndication.com',
			'signup'  => 'https://adsense.google.com/start/',
			'setup'   => 'https://support.google.com/adsense/answer/9274025',
			'api'     => 'https://developers.google.com/adsense/management/getting_started',
			'terms'   => 'https://www.google.com/adsense/terms/',
			'privacy' => 'https://policies.google.com/privacy',
			'note'    => __( 'Uses the official responsive ad unit with this site owner’s publisher and unit identifiers.', 'adz-world' ),
			'fields'  => array(
				'client' => array(
					'label'   => __( 'Publisher ID', 'adz-world' ),
					'pattern' => '/^ca-pub-[0-9]{10,22}$/D',
				),
				'slot'   => array(
					'label'   => __( 'Ad unit ID', 'adz-world' ),
					'pattern' => '/^[0-9]{6,20}$/D',
				),
			),
		),
		'gam'      => array(
			'label'   => 'Google Ad Manager',
			'host'    => 'securepubads.g.doubleclick.net',
			'signup'  => 'https://admanager.google.com/',
			'setup'   => 'https://developers.google.com/publisher-tag/guides/get-started',
			'api'     => 'https://developers.google.com/ad-manager/api/start',
			'terms'   => 'https://admanager.google.com/home/terms/',
			'privacy' => 'https://policies.google.com/privacy',
			'note'    => __( 'Uses Google Publisher Tag with this site owner’s ad unit path and creative dimensions.', 'adz-world' ),
			'fields'  => array(
				'path'   => array(
					'label'   => __( 'Ad unit path', 'adz-world' ),
					'pattern' => '#^/[0-9]{3,15}(/[A-Za-z0-9._~-]{1,64}){1,6}$#D',
				),
				'width'  => array(
					'label'   => __( 'Width', 'adz-world' ),
					'pattern' => '/^[0-9]{2,4}$/D',
					'min'     => 50,
					'max'     => 1200,
				),
				'height' => array(
					'label'   => __( 'Height', 'adz-world' ),
					'pattern' => '/^[0-9]{2,4}$/D',
					'min'     => 50,
					'max'     => 1200,
				),
			),
		),
		'taboola'  => array(
			'label'   => 'Taboola',
			'host'    => 'cdn.taboola.com',
			'signup'  => 'https://www.taboola.com/publishers/',
			'setup'   => 'https://discover.taboola.com/lp-pub-onboarding-en/',
			'terms'   => 'https://www.taboola.com/policies/publisher-terms-and-conditions',
			'privacy' => 'https://www.taboola.com/policies/privacy-policy',
			'note'    => __( 'Uses the publisher’s existing Taboola widget configuration and loads it only when its rotation slot is shown.', 'adz-world' ),
			'fields'  => array(
				'publisher' => array(
					'label'   => __( 'Publisher ID', 'adz-world' ),
					'pattern' => '/^[a-z0-9][a-z0-9-]{1,48}$/D',
				),
				'mode'      => array(
					'label'   => __( 'Widget mode', 'adz-world' ),
					'choices' => array( 'thumbnails-a', 'alternating-thumbnails-a' ),
				),
				'placement' => array(
					'label'   => __( 'Placement name', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9][A-Za-z0-9 _-]{0,79}$/D',
				),
			),
		),
		'outbrain' => array(
			'label'   => 'Outbrain',
			'host'    => 'widgets.outbrain.com',
			'signup'  => 'https://www.outbrain.com/publishers/',
			'setup'   => 'https://developer.outbrain.com/outbrain-javascript-implementation-guide/',
			'terms'   => 'https://www.outbrain.com/legal/terms-of-use/',
			'privacy' => 'https://www.outbrain.com/privacy/',
			'note'    => __( 'Uses Outbrain’s official widget script and the widget ID assigned to this publisher account.', 'adz-world' ),
			'fields'  => array(
				'widget' => array(
					'label'   => __( 'Widget ID', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9][A-Za-z0-9_-]{0,39}$/D',
				),
			),
		),
	);
}

/**
 * Major providers that use managed onboarding or an official integration.
 *
 * These providers do not expose a generic unit that adz.world can safely
 * manufacture from an account ID. Their approved integration remains the
 * source of the advertising shortcode or site-wide inventory.
 *
 * @return array<string,array<string,string>>
 */
function adzworld_managed_network_catalog() {
	return array(
		'medianet'   => array(
			'label'  => 'Media.net',
			'method' => __( 'Publisher account and provider-supplied implementation', 'adz-world' ),
			'signup' => 'https://www.media.net/about.php',
			'setup'  => 'https://www.media.net/contactus.php',
			'note'   => __( 'Apply for a publisher account, then use the implementation Media.net supplies. If its WordPress integration exposes a shortcode, connect that shortcode below.', 'adz-world' ),
		),
		'amazonaps'  => array(
			'label'  => 'Amazon Publisher Services',
			'method' => __( 'Managed publisher onboarding and header bidding', 'adz-world' ),
			'signup' => 'https://aps.amazon.com/aps/index.html',
			'setup'  => 'https://aps.amazon.com/aps/solutions-for-publishers/',
			'note'   => __( 'APS onboarding and demand access are controlled by Amazon. Connect the approved APS or wrapper integration; adz.world does not invent an APS account or bidder key.', 'adz-world' ),
		),
		'ezoic'      => array(
			'label'  => 'Ezoic',
			'method' => __( 'Official WordPress plugin', 'adz-world' ),
			'signup' => 'https://www.ezoic.com/',
			'setup'  => 'https://support.ezoic.com/kb/article/ezoic-wordpress-plugin-javascript-integration-guide',
			'plugin' => 'Ezoic',
			'note'   => __( 'Create the Ezoic account, then use Ezoic’s official WordPress integration for its site-wide advertising and consent requirements.', 'adz-world' ),
		),
		'mediavine'  => array(
			'label'  => 'Mediavine',
			'method' => __( 'Managed onboarding and official WordPress plugin', 'adz-world' ),
			'signup' => 'https://www.mediavine.com/apply/',
			'setup'  => 'https://help.mediavine.com/what-does-it-take-to-get-approved-by-mediavine',
			'plugin' => 'Mediavine Control Panel',
			'note'   => __( 'Apply to Mediavine first. After approval, use its official plugin and onboarding instructions; do not duplicate its site-wide ad placement in an adz rotation.', 'adz-world' ),
		),
		'raptive'    => array(
			'label'  => 'Raptive',
			'method' => __( 'Managed publisher onboarding', 'adz-world' ),
			'signup' => 'https://raptive.com/creators/',
			'setup'  => 'https://help.raptive.com/hc/en-us/articles/360032840891-Who-is-eligible-for-Raptive',
			'note'   => __( 'Apply through Raptive and follow its approved implementation. Add a rotation source below only when that implementation provides a shortcode intended for an ad slot.', 'adz-world' ),
		),
		'monumetric' => array(
			'label'  => 'Monumetric',
			'method' => __( 'Managed publisher onboarding', 'adz-world' ),
			'signup' => 'https://www.monumetric.com/join/',
			'setup'  => 'https://www.monumetric.com/what-are-the-requirements-to-work-with-monumetric/',
			'note'   => __( 'Apply through Monumetric and use its approved implementation. A provider shortcode can be connected below when Monumetric supplies one for a specific slot.', 'adz-world' ),
		),
	);
}

/**
 * Sanitize one field according to its network definition.
 *
 * @param array<string,mixed> $rule Field rule.
 * @param mixed               $raw  Submitted value.
 * @return string
 */
function adzworld_sanitize_network_field( $rule, $raw ) {
	$value = sanitize_text_field( is_scalar( $raw ) ? (string) $raw : '' );

	if ( isset( $rule['choices'] ) ) {
		return in_array( $value, $rule['choices'], true ) ? $value : '';
	}
	if ( empty( $rule['pattern'] ) || 1 !== preg_match( $rule['pattern'], $value ) ) {
		return '';
	}
	if ( isset( $rule['min'], $rule['max'] ) && ( (int) $value < $rule['min'] || (int) $value > $rule['max'] ) ) {
		return '';
	}

	return $value;
}

/**
 * Sanitize all publisher ad-network settings.
 *
 * A unit is enabled only when its required identifiers are valid and the
 * administrator explicitly accepts that the named external host will load.
 *
 * @param mixed $raw Stored or submitted settings.
 * @return array<string,array<string,string>>
 */
function adzworld_sanitize_network_settings( $raw ) {
	$raw   = is_array( $raw ) ? $raw : array();
	$clean = array();

	foreach ( adzworld_network_catalog() as $id => $network ) {
		$row      = isset( $raw[ $id ] ) && is_array( $raw[ $id ] ) ? $raw[ $id ] : array();
		$complete = true;
		$fields   = array();

		foreach ( $network['fields'] as $key => $rule ) {
			$fields[ $key ] = adzworld_sanitize_network_field( $rule, isset( $row[ $key ] ) ? $row[ $key ] : '' );
			$complete       = $complete && '' !== $fields[ $key ];
		}

		$accepted     = isset( $row['accepted'] ) && '1' === (string) $row['accepted'] ? '1' : '0';
		$enabled      = $complete && '1' === $accepted && isset( $row['enabled'] ) && '1' === (string) $row['enabled'] ? '1' : '0';
		$clean[ $id ] = array_merge(
			array(
				'accepted' => $accepted,
				'enabled'  => $enabled,
			),
			$fields
		);
	}

	return $clean;
}

/**
 * Read sanitized publisher ad-network settings.
 *
 * @return array<string,array<string,string>>
 */
function adzworld_network_settings() {
	return adzworld_sanitize_network_settings( get_option( 'adzworld_networks', array() ) );
}

/**
 * Count enabled publisher ad-network units.
 *
 * @return int
 */
function adzworld_enabled_network_count() {
	$count = 0;
	foreach ( adzworld_network_settings() as $row ) {
		$count += '1' === $row['enabled'] ? 1 : 0;
	}
	return $count;
}

/**
 * Add the crossorigin attribute required by AdSense.
 *
 * @param string $tag    Script element.
 * @param string $handle Registered handle.
 * @return string
 */
function adzworld_network_script_tag( $tag, $handle ) {
	return 'adzworld-adsense' === $handle ? str_replace( ' src=', ' crossorigin="anonymous" src=', $tag ) : $tag;
}
add_filter( 'script_loader_tag', 'adzworld_network_script_tag', 10, 2 );

/**
 * Enqueue the official script used by one enabled network.
 *
 * @param string $id Network identifier.
 * @return void
 */
function adzworld_enqueue_network( $id ) {
	$settings = adzworld_network_settings();
	if ( empty( $settings[ $id ] ) || '1' !== $settings[ $id ]['enabled'] ) {
		return;
	}

	if ( 'adsense' === $id ) {
		wp_enqueue_script(
			'adzworld-adsense',
			add_query_arg( 'client', $settings[ $id ]['client'], 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js' ),
			array(),
			'1.0.9',
			true
		);
	} elseif ( 'gam' === $id ) {
		wp_enqueue_script( 'adzworld-gpt', 'https://securepubads.g.doubleclick.net/tag/js/gpt.js', array(), '1.0.9', true );
	} elseif ( 'outbrain' === $id ) {
		wp_enqueue_script( 'adzworld-outbrain', 'https://widgets.outbrain.com/outbrain.js', array(), '1.0.9', true );
	}
}

/**
 * Render one connected publisher network unit.
 *
 * @param array<string,string> $attributes Shortcode attributes.
 * @return string
 */
function adzworld_network_unit_shortcode( $attributes ) {
	$attributes = shortcode_atts( array( 'network' => '' ), $attributes, 'adzworld_network_unit' );
	$id         = sanitize_key( $attributes['network'] );
	$settings   = adzworld_network_settings();

	if ( ! isset( adzworld_network_catalog()[ $id ], $settings[ $id ] ) || '1' !== $settings[ $id ]['enabled'] ) {
		return '';
	}
	$row = $settings[ $id ];
	adzworld_enqueue_network( $id );

	if ( 'adsense' === $id ) {
		return '<div class="adzworld-network-unit" data-adzworld-mount="adsense" data-client="' . esc_attr( $row['client'] ) . '" data-slot="' . esc_attr( $row['slot'] ) . '"></div>';
	}
	if ( 'gam' === $id ) {
		return '<div id="' . esc_attr( wp_unique_id( 'adzworld-gpt-' ) ) . '" class="adzworld-network-unit" data-adzworld-mount="gam" data-path="' . esc_attr( $row['path'] ) . '" data-width="' . esc_attr( $row['width'] ) . '" data-height="' . esc_attr( $row['height'] ) . '"></div>';
	}
	if ( 'taboola' === $id ) {
		return '<div id="' . esc_attr( wp_unique_id( 'adzworld-taboola-' ) ) . '" class="adzworld-network-unit" data-adzworld-mount="taboola" data-publisher="' . esc_attr( $row['publisher'] ) . '" data-mode="' . esc_attr( $row['mode'] ) . '" data-placement="' . esc_attr( $row['placement'] ) . '"></div>';
	}
	if ( 'outbrain' === $id ) {
		$url = is_singular() ? get_permalink() : home_url( '/' );
		return '<div class="OUTBRAIN adzworld-network-unit" data-adzworld-mount="outbrain" data-src="' . esc_url( $url ) . '" data-widget-id="' . esc_attr( $row['widget'] ) . '"></div>';
	}

	return '';
}
add_shortcode( 'adzworld_network_unit', 'adzworld_network_unit_shortcode' );

/**
 * Rotation sources supplied by enabled publisher ad networks.
 *
 * @return array<string,array<string,string>>
 */
function adzworld_network_rotation_sources() {
	$sources = array();
	foreach ( adzworld_network_settings() as $id => $row ) {
		if ( '1' !== $row['enabled'] ) {
			continue;
		}
		$sources[ 'network_' . $id ] = array(
			'label'     => adzworld_network_catalog()[ $id ]['label'],
			'shortcode' => '[adzworld_network_unit network="' . $id . '"]',
		);
	}
	return $sources;
}

/**
 * Save publisher ad-network identifiers.
 *
 * @return void
 */
function adzworld_save_networks() {
	check_admin_referer( 'adzworld_save_networks', 'adzworld_network_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$raw = isset( $_POST['adzworld_networks'] ) ? map_deep( wp_unslash( $_POST['adzworld_networks'] ), 'sanitize_text_field' ) : array();
	update_option( 'adzworld_networks', adzworld_sanitize_network_settings( $raw ), false );
	set_transient( 'adzworld_networks_saved_' . get_current_user_id(), '1', MINUTE_IN_SECONDS );
	wp_safe_redirect( adzworld_desk_url( 'networks' ) );
	exit;
}
add_action( 'admin_post_adzworld_save_networks', 'adzworld_save_networks' );

/**
 * Save an existing advertising plugin's registered shortcode as a source.
 *
 * @return void
 */
function adzworld_save_provider_source() {
	$result = adzworld_save_rotation_source();
	$key    = 'adzworld_provider_source_' . get_current_user_id();
	$value  = is_wp_error( $result ) ? $result->get_error_message() : __( 'Advertising source saved and made available to rotations.', 'adz-world' );
	set_transient( $key, $value, MINUTE_IN_SECONDS );
	set_transient( $key . '_type', is_wp_error( $result ) ? 'error' : 'success', MINUTE_IN_SECONDS );
	wp_safe_redirect( adzworld_desk_url( 'networks' ) );
	exit;
}
add_action( 'admin_post_adzworld_save_provider_source', 'adzworld_save_provider_source' );

/**
 * Report whether a direct provider row is usable.
 *
 * @param array<string,string> $row Provider settings.
 * @return string
 */
function adzworld_network_status( $row ) {
	if ( '1' === $row['enabled'] ) {
		return __( 'Enabled', 'adz-world' );
	}
	foreach ( $row as $key => $value ) {
		if ( ! in_array( $key, array( 'accepted', 'enabled' ), true ) && '' !== $value ) {
			return __( 'Saved, not enabled', 'adz-world' );
		}
	}
	return __( 'Needs setup', 'adz-world' );
}

/**
 * Render publisher ad-network connections.
 *
 * @return void
 */
function adzworld_network_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	$settings        = adzworld_network_settings();
	$notice          = 'adzworld_networks_saved_' . get_current_user_id();
	$provider_notice = 'adzworld_provider_source_' . get_current_user_id();

	if ( get_transient( $notice ) ) {
		delete_transient( $notice );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Ad-network connections saved.', 'adz-world' ) . '</p></div>';
	}
	if ( get_transient( $provider_notice ) ) {
		$type = get_transient( $provider_notice . '_type' );
		echo '<div class="notice notice-' . ( 'error' === $type ? 'error' : 'success' ) . '"><p>' . esc_html( get_transient( $provider_notice ) ) . '</p></div>';
		delete_transient( $provider_notice );
		delete_transient( $provider_notice . '_type' );
	}
	echo '<div class="wrap adzworld-connections"><h2>' . esc_html__( 'Advertising connections', 'adz-world' ) . '</h2>';
	echo '<p class="adzworld-connections__lead">' . esc_html__( 'Create or open each provider account, copy the identifiers it assigns to you, and enable the connection here. Revenue attribution and payment stay with the site owner’s provider account.', 'adz-world' ) . '</p>';
	echo '<h3>' . esc_html__( 'Direct ad units', 'adz-world' ) . '</h3>';
	echo '<p>' . esc_html__( 'These four provider units can become adz.world rotation sources directly. No publisher ID, unit ID, or approval is invented by this plugin.', 'adz-world' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="adzworld_save_networks">';
	wp_nonce_field( 'adzworld_save_networks', 'adzworld_network_nonce' );
	echo '<div class="adzworld-connection-grid">';
	foreach ( adzworld_network_catalog() as $id => $network ) {
		$row    = $settings[ $id ];
		$status = adzworld_network_status( $row );
		$class  = '1' === $row['enabled'] ? ' is-enabled' : '';
		echo '<fieldset class="adzworld-connection-card"><legend>' . esc_html( $network['label'] ) . '</legend>';
		echo '<span class="adzworld-connection-status' . esc_attr( $class ) . '">' . esc_html( $status ) . '</span>';
		echo '<p>' . esc_html( $network['note'] ) . '</p><ol class="adzworld-connection-steps">';
		echo '<li><a class="button" href="' . esc_url( $network['signup'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create or open account', 'adz-world' ) . '</a></li>';
		echo '<li><a href="' . esc_url( $network['setup'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Find the required identifiers', 'adz-world' ) . '</a></li>';
		if ( isset( $network['api'] ) ) {
			echo '<li><a href="' . esc_url( $network['api'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Optional official API access', 'adz-world' ) . '</a></li>';
		}
		echo '<li>' . esc_html__( 'Save the identifiers and enable this source.', 'adz-world' ) . '</li></ol>';
		foreach ( $network['fields'] as $key => $rule ) {
			$name  = 'adzworld_networks[' . $id . '][' . $key . ']';
			$value = $row[ $key ];
			echo '<p><label>' . esc_html( $rule['label'] ) . '<br>';
			if ( isset( $rule['choices'] ) ) {
				echo '<select name="' . esc_attr( $name ) . '">';
				foreach ( $rule['choices'] as $choice ) {
					echo '<option value="' . esc_attr( $choice ) . '"' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
				}
				echo '</select>';
			} else {
				echo '<input type="text" class="regular-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
			}
			echo '</label></p>';
		}
		/* translators: %s: external advertising network host name. */
		$acceptance = sprintf( __( 'I have this provider account and accept loading a visible unit from %s.', 'adz-world' ), $network['host'] );
		echo '<p><label><input type="checkbox" name="' . esc_attr( 'adzworld_networks[' . $id . '][accepted]' ) . '" value="1"' . checked( $row['accepted'], '1', false ) . '> ' . esc_html( $acceptance ) . '</label></p>';
		echo '<p><label><input type="checkbox" name="' . esc_attr( 'adzworld_networks[' . $id . '][enabled]' ) . '" value="1"' . checked( $row['enabled'], '1', false ) . '> ' . esc_html__( 'Enable this rotation source', 'adz-world' ) . '</label></p>';
		echo '<p class="adzworld-connection-links"><a href="' . esc_url( $network['terms'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Terms', 'adz-world' ) . '</a> · <a href="' . esc_url( $network['privacy'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Privacy', 'adz-world' ) . '</a></p></fieldset>';
	}
	echo '</div>';
	submit_button( __( 'Save ad-network connections', 'adz-world' ) );
	echo '</form>';

	echo '<hr><h3>' . esc_html__( 'Managed and official-plugin networks', 'adz-world' ) . '</h3>';
	echo '<p>' . esc_html__( 'These networks control onboarding or site-wide delivery. Their approved integration remains in charge; adz.world does not fabricate credentials or duplicate its code.', 'adz-world' ) . '</p>';
	echo '<div class="adzworld-managed-grid">';
	foreach ( adzworld_managed_network_catalog() as $network ) {
		echo '<section class="adzworld-managed-card"><h4>' . esc_html( $network['label'] ) . '</h4><strong>' . esc_html( $network['method'] ) . '</strong><p>' . esc_html( $network['note'] ) . '</p><p class="adzworld-connection-actions">';
		echo '<a class="button" href="' . esc_url( $network['signup'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Apply or sign in', 'adz-world' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( $network['setup'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Official setup', 'adz-world' ) . '</a>';
		if ( isset( $network['plugin'] ) ) {
			$plugin_url = add_query_arg(
				array(
					's'    => $network['plugin'],
					'tab'  => 'search',
					'type' => 'term',
				),
				admin_url( 'plugin-install.php' )
			);
			echo ' <a class="button" href="' . esc_url( $plugin_url ) . '">' . esc_html__( 'Find official plugin', 'adz-world' ) . '</a>';
		}
		echo '</p></section>';
	}
	echo '</div>';

	echo '<section class="adzworld-provider-bridge"><h3>' . esc_html__( 'Connect another established provider', 'adz-world' ) . '</h3>';
	echo '<p>' . esc_html__( 'Use a shortcode registered by a provider or advertising plugin already installed on this site. Raw JavaScript is not accepted here. The saved source becomes available to adz.world rotations.', 'adz-world' ) . '</p>';
	echo '<p><a class="button" href="' . esc_url(
		add_query_arg(
			array(
				's'    => 'Advanced Ads',
				'tab'  => 'search',
				'type' => 'term',
			),
			admin_url( 'plugin-install.php' )
		)
	) . '">' . esc_html__( 'Find an established ad plugin', 'adz-world' ) . '</a></p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="adzworld_save_provider_source">';
	wp_nonce_field( 'adzworld_rotation_source', 'adzworld_source_nonce' );
	echo '<div class="adzworld-provider-fields"><p><label for="adzworld-source-key">' . esc_html__( 'Source key', 'adz-world' ) . '</label><br><input id="adzworld-source-key" name="source_key" maxlength="50" pattern="[a-z][a-z0-9_-]{0,49}" required></p>';
	echo '<p><label for="adzworld-source-label">' . esc_html__( 'Display label', 'adz-world' ) . '</label><br><input id="adzworld-source-label" name="source_label" maxlength="200" required></p>';
	echo '<p><label for="adzworld-source-shortcode">' . esc_html__( 'Existing provider shortcode', 'adz-world' ) . '</label><br><input id="adzworld-source-shortcode" name="source_shortcode" maxlength="2000" placeholder="[the_ad id=&quot;123&quot;]" required></p></div>';
	echo '<p><label><input type="checkbox" name="remove_source" value="yes"> ' . esc_html__( 'Remove the source with this key', 'adz-world' ) . '</label></p>';
	submit_button( __( 'Save provider source', 'adz-world' ), 'secondary', 'submit', false );
	echo '</form></section>';

	echo '<hr><div class="adzworld-connections-affiliates">';
	adzworld_affiliate_page( true );
	echo '</div></div>';
}
