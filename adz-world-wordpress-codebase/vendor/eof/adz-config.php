<?php
/**
 * Legacy plugin and settings-framework support.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
add_action( 'init', 'adzworld_check_post' );
/**
 * Clear the stored rotation state for rotations submitted on the settings page.
 *
 * SECURITY. Before this was repaired, the function ran on every request --
 * 'init' fires for logged-out visitors too -- and passed a submitted value
 * straight to delete_option() as the option NAME, with no capability check and
 * no nonce. A single unauthenticated POST to any URL on the site could delete
 * siteurl, home, active_plugins or admin_email. Sanitising an arbitrary option
 * name does not make that option safe to delete.
 *
 * Three independent conditions now have to hold: the request is an authorised
 * administrator saving this plugin's own settings form, and the rotation id is
 * one this plugin generates (adz_rotation_<n>, see vendor/eof/fields/class-eof-field-repeat.php).
 *
 * @return void
 */
function adzworld_check_post() {

	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'adz_ad_options-options' ) ) {
		return;
	}

	if ( empty( $_POST['adz_ad_options']['ad_settings'] ) || ! is_array( $_POST['adz_ad_options']['ad_settings'] ) ) {
		return;
	}

	$submitted = map_deep( wp_unslash( $_POST['adz_ad_options']['ad_settings'] ), 'sanitize_text_field' );

	foreach ( $submitted as $value ) {

		if ( ! is_array( $value ) || ! isset( $value['rotation_id'] ) ) {
			continue;
		}

		$rotation_id = adzworld_validate_rotation_id( $value['rotation_id'] );

		if ( '' === $rotation_id ) {
			continue;
		}

		delete_option( $rotation_id );
	}
}
/**
 * Delete this site's network registration and tokens.
 *
 * SECURITY. This was registered for unauthenticated AJAX and guarded only by
 * the 'adzdotworld' nonce, which the plugin prints into public page source for
 * logged-out visitors. Any visitor could read that nonce from a public page and
 * then wipe the site's registration. A nonce is not authorisation.
 *
 * It now requires an administrator and its own nonce, created only on the
 * settings screen, and is no longer reachable without logging in.
 *
 * @return void
 */
function adzworld_reset_registration() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}

	check_ajax_referer( 'adzworld_reset_registration', 'adz_nonce' );

	delete_option( 'adz_publisher_options' );

	wp_send_json_success( 'continue' );
}
add_action( 'wp_ajax_adz_reset_registration', 'adzworld_reset_registration' );

/**
 * Read the bundled settings help document.
 *
 * @param mixed $file File.
 */
function adzworld_get_data_from_file( $file ) {
	global $wp_filesystem;

	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$base_path = realpath( __DIR__ );
	$file_path = realpath( __DIR__ . '/' . ltrim( (string) $file, '/' ) );
	if ( ! $base_path || ! $file_path || 0 !== strpos( $file_path, $base_path . DIRECTORY_SEPARATOR ) || ! WP_Filesystem() ) {
		return '';
	}

	$contents = $wp_filesystem->get_contents( $file_path );
	if ( false === $contents ) {
		return '';
	}
	return str_replace(
		array( '{{name}}', '{{home}}' ),
		array( esc_html( ADZWORLD_NAME ), esc_url( untrailingslashit( ADZWORLD_HOME ) ) ),
		$contents
	);
}
/**
 * Configure the advertisement settings sections.
 */
function adzworld_settings_setup() {
	$sections = array();
	$configs  = array();
	// General Section.
	$sections['tos'] = array(
		'title'    => 'Site settings',
		'id'       => 'tos',
		'priority' => 1,
		'fields'   => array(
			'amazon_affiliate_id'         => array(
				'id'    => 'amazon_affiliate_id',
				'type'  => 'text',
				'title' => 'Your Amazon.com Affiliate code.',
				'desc'  => '',
			),
			'exclude_pages_from_rotation' => array(
				'title'    => 'Select the Pages which you not want to show in Rotation.',
				'type'     => 'select',
				'options'  => adzworld_get_all_pages(),
				'multiple' => 'yes',

			),

		),
	);

	$sections['ad_management'] = array(
		'title'    => 'Create adz rotation',
		'id'       => 'ad_management',
		'priority' => 2,
		'fields'   => adzworld_rotation_setting_array(),
	);

	$configs = array(
		// This is where your data is stored in the database and also becomes your global variable name.
		'opt_name'           => 'adz_ad_options',
		// Set a different name for your global variable other than the opt_name.
		'global_variable'    => '',
		// Specify if the admin menu should appear or not. Options: menu or submenu.
		'page_parent'        => 'edit.php?post_type=adz_ad',
		'menu_type'          => 'submenu',
		// Show the sections below the admin menu item or not.
		'allow_sub_menu'     => false,
		// Admin menu title.
		'menu_title'         => 'adz settings',
		// Page title of admin menu page.
		'page_title'         => 'adz settings',
		// For a full list of options, visit the WordPress add_submenu_page documentation.
		// Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
		'menu_priority'      => 10,
		// Specify a custom URL to an icon.
		'menu_icon'          => 'dashicons-visibility',
		// Order where the menu divider appears in the admin area.
		'menu_divider'       => null,
		// Permissions needed to access the options panel.
		'page_permissions'   => 'manage_options',
		// Page slug used to denote the panel.
		'page_slug'          => '_adz_ad_options',
		// Show the panel pages on the admin bar.
		'admin_bar'          => false,
		// Choose an icon for the admin bar menu.
		'admin_bar_icon'     => 'dashicons-visibility',
		// On load save the defaults to DB before user clicks save or not.
		'save_defaults'      => false,
		// Shows the Import/Export panel when not used as a field.
		'show_import_export' => true,
		// Specify option page help tabs.
		'help_tabs'          => array(
			array(
				'id'      => 'help',
				'title'   => 'Help',
				'content' => '<p>Information on how to use the settings and ad management</p>',
			),
			array(
				'id'      => 'about',
				'title'   => 'About',
				'content' => '<p>Information about stuff.</p>',
			),
		),
		// Specify option page help sidebar content.
		'help_sidebar'       => '<p>Static help and information</p>',
	);

	if ( class_exists( 'eof' ) ) {
		$settings_configs        = new eof(
			$configs,
			$sections
		);
		$GLOBALS['adzworld_eof'] = $settings_configs;
	}
}
add_action( 'init', 'adzworld_settings_setup' );


// Function to create adz rotation setting form.

/**
 * Define the configured legacy rotation fields.
 */
function adzworld_rotation_setting_array() {
		$rotation_array = array(
			'ad_settings_head' => array(
				'type'  => 'heading',
				'title' => 'Instructions',
				'desc'  => adzworld_get_data_from_file( 'instructions.html' ),
			),
			'ad_settings'      => array(
				'title'      => 'adz settings',
				'type'       => 'repeat',
				'style'      => '',
				'sub_fields' => array(
					'rotation_name'       => array(
						'title' => 'adz rotation name',
						'type'  => 'text',
						'sizes' => 'large',

					),

					'rotation_pages'      => array(
						'title'    => 'Page(s)/Post(s)',
						'type'     => 'select',
						'options'  => adzworld_get_filtered_pages(),
						'multiple' => 'yes',

					),

					'rotation_categories' => array(
						'title'    => 'Categories',
						'type'     => 'select',
						'options'  => adzworld_get_all_categories( 'category' ),
						'multiple' => 'yes',

					),
					'rotation_tags'       => array(
						'title'    => 'Tag(s)',
						'type'     => 'select',
						'options'  => adzworld_get_all_categories( 'post_tag' ),
						'multiple' => 'yes',

					),
					'member_level'        => array(
						'type'    => 'select',
						'title'   => 'Show adz to',
						'desc'    => '',
						'default' => '',
						'options' => adzworld_get_membership_level(),

					),

					'adz_rotation'        => array(
						'type'    => 'radio',
						'title'   => 'Show adz rotation',
						'desc'    => '',
						'options' => array(
							'in_sequence' => 'In sequence',
							'random'      => 'Randomize',
						),
						'default' => 'in_sequence',

					),

					'ad_sequences'        => array(
						'type'     => 'select',
						'title'    => 'adz to show',
						'options'  => adzworld_get_all_ads(),
						'sizes'    => 'small',
						'multiple' => 'yes',
					),

					'ad_seconds'          => array(
						'type'    => 'number',
						'title'   => 'Seconds of adz',
						'desc'    => '',
						'default' => '',
						'sizes'   => 'small',
					),
					'browse_seconds'      => array(
						'type'    => 'number',
						'title'   => 'Seconds of Browsing',
						'desc'    => '',
						'default' => '',
						'sizes'   => 'small',
					),
					'popup_or_page'       => array(
						'type'    => 'radio',
						'title'   => 'Show adz in popup, within page/post, or thru page',
						'desc'    => '',
						'options' => array(
							'popup'     => 'Popup',
							'page'      => 'Within page/post',
							'thru_page' => 'Thru Page',
						),
						'default' => 'page',

					),
					'loop'                => array(
						'type'    => 'checkbox',
						'title'   => 'Loop advertize infinite times',
						'desc'    => '',
						'default' => '',
						'options' => array(
							'yes' => 'Yes',
						),

					),
					'no_of_times'         => array(
						'type'    => 'number',
						'title'   => 'Number of time ad will repete',
						'desc'    => '',
						'default' => '',
						'sizes'   => 'small',
					),

					'adz_template'        => array(
						'type'    => 'select',
						'title'   => 'adz template',
						'desc'    => '',
						'default' => '',
						'sizes'   => 'small',
						'options' => adzworld_get_ad_template(),
					),
				),
			),
		);

		return $rotation_array;
}


// Function to fetch all adz.
/**
 * List published advertisements for the settings selectors.
 */
function adzworld_get_all_ads() {
	$adz_list  = array();
	$args      = array(
		'posts_per_page' => -1,
		'post_type'      => array( 'adz_ad' ),
		'post_status'    => 'publish',
	);
	$adz_array = get_posts( $args );
	if ( ! empty( $adz_array ) ) {
		foreach ( $adz_array as $adz ) {
			$adz_list['']         = '';
			$adz_list[ $adz->ID ] = $adz->post_title;
		}
	}
	return $adz_list;
}

// Function to check user register to the adz-world.
/**
 * Return escaped registration status and its reset control.
 */
function adzworld_registration_status() {
	$adz_publisher_options = get_option( 'adz_publisher_options' );
	$reg                   = 'Not Registered';
	if ( isset( $adz_publisher_options['adz_registered'] ) ) {
			$reg = Adz_NetworkAuthorization::check_registration();
		if ( is_wp_error( $reg ) ) {
			return esc_html( $reg->get_error_message() );
		}
		if ( isset( $reg['date'] ) && is_string( $reg['date'] ) ) {
			$reg  = esc_html__( 'Registered Since:', 'adz-world' ) . ' ' . esc_html( $reg['date'] );
			$reg .= ' <a href="#" class="adz-reset-registration">' . esc_html__( 'Reset the linked account.', 'adz-world' ) . '</a>'
				. '<input type="hidden" value="' . esc_attr( wp_create_nonce( 'adzworld_reset_registration' ) ) . '" id="adz_nonce">';
		} else {
			$reg = 'Not Registered';
		}
	}
	return $reg;
}

// Function to fetch all adz terms.

/**
 * List available remote advertisement categories.
 */
function adzworld_list_all_ad_types() {
	$select_box = array();

	$caetgories = Adz_NetworkAuthorization::get_ad_types();
	if ( is_wp_error( $caetgories ) ) {
		adzworld_network_notice( $caetgories );
		return $select_box;
	}
	if ( ! empty( $caetgories ) ) {
		foreach ( $caetgories as $category ) {

			$select_box[ $category->id ] = $category->name;
		}
	}
	return $select_box;
}

// Function to fetch all membership plans of Woocommerce memebership if installed.
/**
 * List optional membership plans and the guest audience.
 */
function adzworld_get_membership_level() {
	$level_list = array();
	if ( function_exists( 'wc_memberships' ) ) {

		$args   = array(
			'posts_per_page' => -1,
			'post_type'      => array( 'wc_membership_plan' ),
			'post_status'    => 'publish',
		);
		$levels = get_posts( $args );

		if ( ! empty( $levels ) ) {
			foreach ( $levels as $level ) {

				$level_list[ $level->post_name ] = $level->post_title;
			}
		}
	}
	$level_list['non-logged'] = 'Guest';
	return $level_list;
}

// Function to fetch pages and posts.
/**
 * List published posts and pages for settings selection.
 */
function adzworld_get_all_pages() {

	$page_list   = array();
	$args        = array(
		'posts_per_page' => -1,
		'post_type'      => array( 'post', 'page' ),
		'post_status'    => 'publish',
	);
	$posts_array = get_posts( $args );
	if ( ! empty( $posts_array ) ) {
		foreach ( $posts_array as $page ) {
			$page_list['']          = '';
			$page_list[ $page->ID ] = $page->post_title;
		}
	}
	return $page_list;
}

// Function to fetch pages and posts filtered which do not want in the rotation settings.
/**
 * List pages not excluded from legacy rotations.
 */
function adzworld_get_filtered_pages() {

	$ad_settings_options = get_option( 'adz_ad_options' );
	$exclude_array       = array();
	if ( isset( $ad_settings_options['exclude_pages_from_rotation'] ) && ! empty( $ad_settings_options['exclude_pages_from_rotation'] ) ) {
		$exclude_array = $ad_settings_options['exclude_pages_from_rotation'];
	}

	$page_list   = array();
	$args        = array(
		'posts_per_page' => -1,
		'post_type'      => array( 'post', 'page' ),
		'post_status'    => 'publish',
	);
	$posts_array = get_posts( $args );
	if ( ! empty( $posts_array ) ) {
		foreach ( $posts_array as $page ) {
			$page_list[''] = '';
			if ( ! in_array( $page->ID, $exclude_array, true ) ) {
				$page_list[ $page->ID ] = $page->post_title;
			}
		}
	}
	return $page_list;
}

// Function to fetch categories.
/**
 * List terms for the configured taxonomy.
 *
 * @param mixed $taxonomy Taxonomy.
 */
function adzworld_get_all_categories( $taxonomy ) {
	$term_list = array();
	$terms     = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		adzworld_network_notice( $terms );
		return $term_list;
	}
	if ( ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			$term_list['']               = '';
			$term_list[ $term->term_id ] = $term->name;
		}
	}
	return $term_list;
}

// Function to fetch all User Roles.
/**
 * List WordPress roles and the guest audience.
 */
function adzworld_list_all_user_roles() {
	global $wp_roles;

	$roles          = $wp_roles->get_names();
	$roles['guest'] = 'Guest';
	return $roles;
}

// Function to fetch templates lists.
/**
 * List shipped advertisement templates.
 */
function adzworld_get_ad_template() {
	$adz_template_list = array();
	$dir               = ADZ_WORLD . 'adz-templates';
	$templates         = scandir( $dir, 1 );
	if ( ! empty( $templates ) ) {
		foreach ( $templates as $template ) {
			$ext = pathinfo( $template, PATHINFO_EXTENSION );
			if ( 'php' === $ext ) {
				$adz_template_list[ $template ] = $template;
			}
		}
	}
	return $adz_template_list;
}
