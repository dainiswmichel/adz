<?php
/**
 * One FLOSC-style settings screen. The left-menu pages resolve to its tabs.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tabs that replace the separate plugin submenu pages.
 *
 * @return array<string,string>
 */
function adzworld_desk_tabs() {
	return array(
		'dashboard'  => __( 'Dashboard', 'adz-world' ),
		'networks'   => __( 'Connections', 'adz-world' ),
		'affiliates' => __( 'Affiliate programs', 'adz-world' ),
		'rotation'   => __( 'Rotation', 'adz-world' ),
		'earnings'   => __( 'Earnings', 'adz-world' ),
		'templates'  => __( 'Templates', 'adz-world' ),
		'starter'    => __( 'Starter packs', 'adz-world' ),
	);
}

/**
 * URL for one dashboard tab.
 *
 * @param string $tab Tab identifier.
 * @return string
 */
function adzworld_desk_url( $tab = 'dashboard' ) {
	return add_query_arg(
		array(
			'page' => 'adzworld',
			'tab'  => isset( adzworld_desk_tabs()[ $tab ] ) ? $tab : 'dashboard',
		),
		admin_url( 'admin.php' )
	);
}

/**
 * Sidebar page slugs that mirror the horizontal settings tabs.
 *
 * @return array<string,string>
 */
function adzworld_desk_submenu_slugs() {
	return array(
		'dashboard'  => 'adzworld',
		'networks'   => 'adzworld-networks',
		'affiliates' => 'adzworld-affiliates',
		'rotation'   => 'adzworld-rotation',
		'earnings'   => 'adzworld-earnings',
		'templates'  => 'adzworld-templates',
		'starter'    => 'adzworld-starter',
	);
}

/**
 * Active desk tab.
 *
 * @return string
 */
function adzworld_desk_tab() {
	$requested = filter_input( INPUT_GET, 'tab', FILTER_UNSAFE_RAW );
	$requested = is_string( $requested ) ? sanitize_key( $requested ) : '';
	if ( isset( adzworld_desk_tabs()[ $requested ] ) ) {
		return $requested;
	}

	$page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
	$page = is_string( $page ) ? sanitize_key( $page ) : '';
	$tab  = array_search( $page, adzworld_desk_submenu_slugs(), true );

	return is_string( $tab ) ? $tab : 'dashboard';
}

/**
 * Replace the separate plugin screens with one tabbed screen.
 *
 * @return void
 */
function adzworld_desk_menu() {
	add_menu_page(
		__( 'adz.world', 'adz-world' ),
		__( 'Adz.world', 'adz-world' ),
		'manage_options',
		'adzworld',
		'adzworld_desk_page',
		'dashicons-welcome-view-site',
		5
	);
	foreach ( adzworld_desk_tabs() as $tab => $label ) {
		add_submenu_page(
			'adzworld',
			$label,
			$label,
			'manage_options',
			adzworld_desk_submenu_slugs()[ $tab ],
			'adzworld_desk_page'
		);
	}
}
add_action( 'admin_menu', 'adzworld_desk_menu', 5 );

/**
 * Remove the obsolete submenu registrations left by the legacy components.
 *
 * Their render callbacks are used by the unified tabs, so this changes only
 * navigation and does not remove settings functionality.
 *
 * @return void
 */
function adzworld_desk_remove_legacy_menu() {
	$legacy_parent = 'edit.php?post_type=adz_ad';
	remove_submenu_page( $legacy_parent, '_adz_ad_options' );
	remove_submenu_page( $legacy_parent, 'adz-template-setting' );
	remove_submenu_page( $legacy_parent, 'adzworld-earnings' );
	remove_submenu_page( $legacy_parent, 'adzworld-rotation-sources' );
	remove_submenu_page( $legacy_parent, 'adzworld-slot' );
}
add_action( 'admin_menu', 'adzworld_desk_remove_legacy_menu', 999 );

/**
 * Keep the matching sidebar shortcut selected while a horizontal tab is open.
 *
 * @param string|null $submenu_file Current submenu file.
 * @param string|null $parent_file  Current parent menu file.
 * @return string|null
 */
function adzworld_desk_submenu_file( $submenu_file, $parent_file ) {
	if ( 'adzworld' !== $parent_file ) {
		return $submenu_file;
	}

	$slugs = adzworld_desk_submenu_slugs();
	$tab   = adzworld_desk_tab();

	return isset( $slugs[ $tab ] ) ? $slugs[ $tab ] : $submenu_file;
}
add_filter( 'submenu_file', 'adzworld_desk_submenu_file', 10, 2 );

/**
 * Load dashboard styles only on the unified plugin screen.
 *
 * @return void
 */
function adzworld_desk_assets() {
	$page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
	$page = is_string( $page ) ? sanitize_key( $page ) : '';
	if ( ! in_array( $page, adzworld_desk_submenu_slugs(), true ) ) {
		return;
	}
	$path = dirname( __DIR__ ) . '/assets/admin-desk.css';
	wp_enqueue_style( 'adzworld-desk', plugins_url( 'assets/admin-desk.css', dirname( __DIR__ ) . '/adz-world.php' ), array(), (string) filemtime( $path ) );
}
add_action( 'admin_enqueue_scripts', 'adzworld_desk_assets' );

/**
 * Render the dashboard overview and its actual connection state.
 *
 * @return void
 */
function adzworld_desk_dashboard() {
	$post_counts = wp_count_posts( 'adz_ad' );
	$local_adz   = isset( $post_counts->publish ) ? (int) $post_counts->publish : 0;
	$earnings    = get_option( 'adzworld_earnings_connection', array() );
	$cards       = array(
		array(
			'label' => __( 'Published local adz', 'adz-world' ),
			'value' => (string) $local_adz,
			'text'  => __( 'Created and served by this WordPress site.', 'adz-world' ),
			'tab'   => 'rotation',
		),
		array(
			'label' => __( 'Affiliate programs', 'adz-world' ),
			'value' => (string) adzworld_affiliate_connected_count(),
			'text'  => __( 'Enabled direct-attribution accounts owned by the site owner.', 'adz-world' ),
			'tab'   => 'affiliates',
		),
		array(
			'label' => __( 'Direct ad-network units', 'adz-world' ),
			'value' => (string) adzworld_enabled_network_count(),
			'text'  => __( 'Enabled units using the site owner’s provider identifiers.', 'adz-world' ),
			'tab'   => 'networks',
		),
		array(
			'label' => __( 'adz-world API', 'adz-world' ),
			'value' => adzworld_network_enabled() ? __( 'Connected', 'adz-world' ) : __( 'Off', 'adz-world' ),
			'text'  => __( 'Optional shared adz service. Local adz continue to work while it is off.', 'adz-world' ),
			'tab'   => 'networks',
		),
		array(
			'label' => __( 'Earnings', 'adz-world' ),
			'value' => ! empty( $earnings['consent'] ) ? __( 'Connected', 'adz-world' ) : __( 'Off', 'adz-world' ),
			'text'  => __( 'Read-only statement access; it does not initiate a payment.', 'adz-world' ),
			'tab'   => 'earnings',
		),
	);

	echo '<div class="adzworld-desk-intro"><h2>' . esc_html__( 'Monetization and delivery', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'Direct affiliate and ad-network connections use the site owner’s own account identifiers. Those providers attribute revenue and pay the site owner under their existing agreements. The optional adz-world service is separate and remains off until explicitly connected.', 'adz-world' ) . '</p></div>';
	echo '<div class="adzworld-desk-grid">';
	foreach ( $cards as $card ) {
		echo '<section class="adzworld-desk-card"><h3>' . esc_html( $card['label'] ) . '</h3><strong>' . esc_html( $card['value'] ) . '</strong><p>' . esc_html( $card['text'] ) . '</p><a class="button" href="' . esc_url( adzworld_desk_url( $card['tab'] ) ) . '">' . esc_html__( 'Open', 'adz-world' ) . '</a></section>';
	}
	echo '</div>';
}

/**
 * Render the tab row and the selected screen.
 *
 * @return void
 */
function adzworld_desk_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	$current = adzworld_desk_tab();
	echo '<div class="wrap adzworld-admin"><h1 class="adzworld-settings-title">' . esc_html__( 'Adz.world', 'adz-world' ) . ' <span class="adzworld-settings-version">v' . esc_html( ADZWORLD_VERSION ) . '</span></h1>';
	echo '<nav class="nav-tab-wrapper adzworld-settings-tabs" aria-label="' . esc_attr__( 'Adz.world tabs', 'adz-world' ) . '">';
	foreach ( adzworld_desk_tabs() as $id => $label ) {
		$url   = adzworld_desk_url( $id );
		$class = 'nav-tab' . ( $current === $id ? ' nav-tab-active' : '' );
		echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</nav></div>';
	if ( 'dashboard' === $current ) {
		echo '<div class="wrap adzworld-desk">';
		adzworld_desk_dashboard();
		echo '</div>';
		return;
	}

	if ( 'starter' === $current ) {
		echo '<div class="wrap adzworld-desk">';
		adzworld_starter_page();
		echo '</div>';
		return;
	}
	if ( 'templates' === $current ) {
		adzworld_template_setting();
		return;
	}
	if ( 'rotation' === $current ) {
		adzworld_rotation_admin_page();
		return;
	}
	if ( 'networks' === $current ) {
		adzworld_network_page();
		return;
	}
	if ( 'earnings' === $current ) {
		adzworld_earnings_page();
		return;
	}
	if ( 'affiliates' === $current ) {
		echo '<div class="wrap">';
		adzworld_affiliate_page();
		echo '</div>';
		return;
	}
	if ( isset( $GLOBALS['adzworld_eof'] ) && is_object( $GLOBALS['adzworld_eof'] ) ) {
		$GLOBALS['adzworld_eof']->eof_views->admin_options_page();
	}
}
