<?php
/**
 * Legacy plugin and settings-framework support.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
add_action( 'init', 'adz_check_post_function' );
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
function adz_check_post_function() {

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

		$rotation_id = adz_validate_rotation_id( $value['rotation_id'] );

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
function adz_reset_registration() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}

	check_ajax_referer( 'adz_reset_registration', 'adz_nonce' );

	delete_option( 'adz_publisher_options' );

	wp_send_json_success( 'continue' );
}
add_action( 'wp_ajax_adz_reset_registration', 'adz_reset_registration' );

/**
 * Read the bundled settings help document.
 *
 * @param mixed $file File.
 */
function adz_get_data_from_file( $file ) {

	return file_get_contents( __DIR__ . '/' . $file );
}
/**
 * Configure the advertisement settings sections.
 */
function adz_setings_setup() {

	$sections       = array();
	$configs        = array();
	$adz_ad_options = get_option( 'adz_ad_options' );
	// General Section.
	$sections['tos'] = array(
		'title'    => 'Accept TOS and Registration',
		'id'       => 'tos',
		'priority' => 1,
		'fields'   => array(
			'tos_heading'                   => array(
				'id'    => 'tos_heading',
				'type'  => 'heading',
				'title' => 'Terms of Service',
				'desc'  => adz_get_data_from_file( 'tos.html' ),

			),
			'accept_tos_checkbox'           => array(
				'title'   => 'I Accept',
				'desc'    => '',
				'type'    => 'checkbox',
				'default' => '0', // 1 = on | 0 = off
			),
			'connect_to_network'            => array(
				'title'   => 'Connect to the adz-world.com network',
				'type'    => 'checkbox',
				'default' => '0', // 1 = on | 0 = off
				'desc'    => 'Off by default. Your site serves only the adz you create here, and contacts no outside server. Switching this on connects the site to the adz-world.com network: your site will send your site URL, admin email and visitor IP addresses to adz-world.com in order to fetch adz, and the adz you publish will be shared with the network. See <a href="https://adz-world.com/terms/">Terms of Service</a> and <a href="https://adz-world.com/privacy/">Privacy Policy</a>.',
			),
			'adzworld_monetization_consent' => array(
				'title'   => 'Optional adz-world monetization terms',
				'type'    => 'checkbox',
				'default' => '0',
				'desc'    => 'I authorize adz-world service ads to use adz-world affiliate/referral codes. adz-world retains 10% of settled affiliate commissions actually received; my publisher share is 90%. This is not 10% of product sales. Stripe/PayPal payout arrangements and fees remain TBD; this candidate does not enable live payments. Existing publisher links are not rewritten. This consent applies to adz-world.com, not to independently operated services. Terms: https://adz-world.com/terms/ and privacy: https://adz-world.com/privacy/ must be available before service launch.',
			),
			'paypal_email'                  => array(
				'type'    => 'email',
				'title'   => 'Paypal Email',
				'desc'    => '',
				'default' => '',
				'sizes'   => 'regular',
			),

			'adzdotworld_email'             => array(
				'type'    => 'email',
				'title'   => 'Adz.world Email',
				'default' => '',
				'sizes'   => 'regular',
				'desc'    => 'Please enter the Adz.world email address.',

			),

			'type_of_adz'                   => array(
				'id'       => 'type_of_adz',
				'type'     => 'select',
				'options'  => adz_list_all_adz_type(),
				'title'    => 'Types of Advertise',
				'content'  => '',
				'multiple' => 'yes',

			),

			'referral_code'                 => array(
				'id'    => 'referral_code',
				'type'  => 'text',
				'title' => 'Enter Referral Code',
				'desc'  => 'Please enter if you have a referral code',
			),
			'amazon_affiliate_id'           => array(
				'id'    => 'amazon_affiliate_id',
				'type'  => 'text',
				'title' => 'Your Amazon.com Affiliate code.',
				'desc'  => '',
			),
			'exclude_pages_from_rotation'   => array(
				'title'    => 'Select the Pages which you not want to show in Rotation.',
				'type'     => 'select',
				'options'  => adz_get_all_pages(),
				'multiple' => 'yes',

			),

			'reg_status'                    => array(
				'id'       => 'reg_status',
				'type'     => 'dynamic',
				'title'    => 'Registration Status',
				'content'  => '',
				'callback' => 'adz_registration_status',
			),

		),
	);

	$sections['ad_management'] = array(
		'title'    => 'Create Adz Rotation',
		'id'       => 'ad_management',
		'priority' => 2,
		'fields'   => adz_rotation_setting_array(),
	);

	add_action( 'updated_option', 'adz_check_registration', 10, 3 );
	/**
	 * Synchronize changed settings after an authorized save.
	 *
	 * @param mixed $option Option.
	 * @param mixed $old_value Old value.
	 * @param mixed $value Current field value.
	 */
	function adz_check_registration( $option, $old_value, $value ) {

		if ( 'adz_ad_options' === $option ) {

			$result = Adz_NetworkAuthorization::update_registration( $value );
			if ( is_wp_error( $result ) ) {
				adzworld_network_notice( $result );
			}
		}
	}
	$user = wp_get_current_user();

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
		'menu_title'         => 'Adz Settings',
		// Page title of admin menu page.
		'page_title'         => 'Adz Settings',
		// For a full list of options, visit: http://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters
		// 'page_parent'       => 'themes.php',
		// Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
		'menu_priority'      => 10,
		// Specify a custom URL to an icon.
		'menu_icon'          => 'dashicons-visibility',
		// Order where the menu divider appears in the admin area.
		'menu_divider'       => null,
		// Permissions needed to access the options panel.
		'page_permissions'   => /*in_array( 'adz_world_manager', $user->roles ) ? 'adz_world_manager' :*/ 'manage_options',
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
		// Specify option page help tabs. array( array('id' => '', 'title' => '', 'content' => ''), ).
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
		$settings_configs = new eof(
			$configs,
			$sections
		);
	}
}
add_action( 'init', 'adz_setings_setup' );


// Function to create adz rotation setting form.

/**
 * Define the configured legacy rotation fields.
 */
function adz_rotation_setting_array() {
	global $adz_ad_network_base_url;
	$adz_publisher_options = get_option( 'adz_publisher_options' );
	$publisher_user_id     = isset( $adz_publisher_options['adz_registered']['publisher_user_id'] ) ? absint( $adz_publisher_options['adz_registered']['publisher_user_id'] ) : 0;

		$rotation_array = array(
			'ad_settings_head' => array(
				'type'  => 'heading',
				'title' => 'Instructions',
				'desc'  => adz_get_data_from_file( 'instructions.html' ),
			),
			'adz_html'         => array(
				'id'      => 'eof_html',
				'type'    => 'html',
				'title'   => '',
				'content' => '<div style="width:99%; padding: 5px;" class="updated below-h2"><a class="button button-primary" href="' . $adz_ad_network_base_url . 'shop/premium-plugin-membership/" target="_blank">Try the Premium version Click to Purchase. </a></div>',
			),
			'ad_settings'      => array(
				'title'      => 'Ad Settings',
				'type'       => 'repeat',
				'style'      => '',
				'sub_fields' => array(
					'rotation_name'       => array(
						'title' => 'Adz Rotation Name',
						'type'  => 'text',
						'sizes' => 'large',

					),

					'rotation_pages'      => array(
						'title'    => 'Page(s)/Post(s)',
						'type'     => 'select',
						'options'  => adz_get_filterd_pages(),
						'multiple' => 'yes',

					),

					'rotation_categories' => array(
						'title'    => 'Categories',
						'type'     => 'select',
						'options'  => adz_get_all_categories( 'category' ),
						'multiple' => 'yes',

					),
					'rotation_tags'       => array(
						'title'    => 'Tag(s)',
						'type'     => 'select',
						'options'  => adz_get_all_categories( 'post_tag' ),
						'multiple' => 'yes',

					),
					'member_level'        => array(
						'type'    => 'select',
						'title'   => 'Show Adz to',
						'desc'    => '',
						'default' => '',
						'options' => adz_get_membership_level(),

					),

					'adz_rotation'        => array(
						'type'    => 'radio',
						'title'   => 'Show Adz Rotation',
						'desc'    => '',
						'options' => array(
							'in_sequence' => 'In sequence',
							'random'      => 'Randomize',
						),
						'default' => 'in_sequence',

					),

					'ad_sequences'        => array(
						'type'     => 'select',
						'title'    => 'Adz to show',
						'options'  => adz_get_all_adz(),
						'sizes'    => 'small',
						'multiple' => 'yes',
					),

					'ad_seconds'          => array(
						'type'    => 'number',
						'title'   => 'Seconds of Ads',
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
						'title'   => 'Show Adz in popup, within page/post, or thru page',
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
						'title'   => 'Adz template',
						'desc'    => '',
						'default' => '',
						'sizes'   => 'small',
						'options' => adz_get_adz_template(),
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
function adz_get_all_adz() {
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

// Function to check user register to the adz.world.
/**
 * Return escaped registration status and its reset control.
 */
function adz_registration_status() {
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
				. '<input type="hidden" value="' . esc_attr( wp_create_nonce( 'adz_reset_registration' ) ) . '" id="adz_nonce">';
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
function adz_list_all_adz_type() {
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
function adz_get_membership_level() {
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
function adz_get_all_pages() {

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
function adz_get_filterd_pages() {

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
			if ( ! in_array( $page->ID, $exclude_array ) ) {
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
function adz_get_all_categories( $taxonomy ) {
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
function adz_list_all_user_roles() {
	global $wp_roles;

	$roles          = $wp_roles->get_names();
	$roles['guest'] = 'Guest';
	return $roles;
}

// Function to fetch templates lists.
/**
 * List shipped advertisement templates.
 */
function adz_get_adz_template() {
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
