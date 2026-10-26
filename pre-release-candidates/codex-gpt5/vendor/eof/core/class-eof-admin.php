<?php
/**
 * Legacy plugin and settings-framework support.
 *
 * @package adz-world
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EOF_Admin' ) ) :
	/**
	 * EOF_Admin Class
	 *
	 * This class is for registering new admin page for settings
	 *
	 * @package EOF
	 * @since 1.0.0
	 */
	class EOF_Admin {

		/**
		 * Parent settings framework.
		 *
		 * @var EOF
		 */
		public $parent;

		/**
		 * Registered settings screen identifier.
		 *
		 * @var string
		 */
		public $options_page_id = '';

		/**
		 * Initialize the configured settings component.
		 *
		 * @param EOF $framework Parent settings framework.
		 */
		public function __construct( $framework ) {
			$this->parent = $framework;
			add_action( 'admin_menu', array( $this, 'eof_admin_menu' ) );
			add_filter( 'admin_footer_text', array( $this, 'admin_rate_us' ) );
		}

		/**
		 * Add admin menu page
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function eof_admin_menu() {
			$menu_type     = $this->parent->configs['menu_type'];
			$page_parent   = $this->parent->configs['page_parent'];
			$optioins_page = '';
			// Themes should use add_theme_page() for adding admin pages.
			if ( 'submenu' === $menu_type && isset( $page_parent ) && ! empty( $page_parent ) ) {
				$optioins_page = add_submenu_page(
					$this->parent->configs['page_parent'],
					$this->parent->configs['page_title'],
					$this->parent->configs['menu_title'],
					$this->parent->configs['page_permissions'],
					$this->parent->configs['page_slug'],
					array( $this, 'eof_options_page_callback' )
				);
			} else {
				$optioins_page = add_menu_page(
					$this->parent->configs['page_title'],
					$this->parent->configs['menu_title'],
					$this->parent->configs['page_permissions'],
					$this->parent->configs['page_slug'],
					array( $this, 'eof_options_page_callback' ),
					$this->parent->configs['menu_icon'],
					$this->parent->configs['menu_priority']
				);
			}
			$this->parent->configs['options_page_id'] = $optioins_page;
			$this->options_page_id                    = $optioins_page;

			// Adds help_tab when optioins_page loads.
			add_action( 'load-' . $optioins_page, array( $this, 'options_page_add_help_tab' ) );

			// Add submenu if it is allowed.
			if ( isset( $this->parent->configs['allow_sub_menu'] ) && $this->parent->configs['allow_sub_menu'] ) {
				foreach ( $this->parent->sections as $key => $section ) {
					add_submenu_page(
						$this->parent->configs['page_slug'],
						$section['title'],
						$section['title'],
						$this->parent->configs['page_permissions'],
						add_query_arg( 'tab', $key, menu_page_url( $this->parent->configs['page_slug'], false ) )
					);
				}
			}

			// Add menu dividers if there is set any.
			if ( isset( $this->parent->configs['menu_divider'] ) ) {
				$positions = $this->parent->configs['menu_divider'];
				add_filter(
					'add_menu_classes',
					static function ( $items ) use ( $positions ) {
						foreach ( $positions as $position ) {
							$items[ $position ] = array( '', 'read', 'separator' . $position, '', 'wp-menu-separator' );
						}
						return $items;
					}
				);
			}
		}

		/**
		 * Display options page
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function eof_options_page_callback() {
			$this->parent->eof_views->admin_options_page();
		}

		/**
		 * Add help tab to options page
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function options_page_add_help_tab() {

			if ( empty( $this->parent->configs['help_tabs'] ) ) {
				return;
			}

			$screen = get_current_screen();
			// Add my_help_tab if current screen is My Admin Page.
			foreach ( $this->parent->configs['help_tabs'] as $tab ) {
				$screen->add_help_tab(
					array(
						'id'      => $tab['id'],
						'title'   => $tab['title'],
						'content' => $tab['content'],
					)
				);
			}

			if ( ! empty( $this->parent->configs['help_sidebar'] ) ) {
				$screen->set_help_sidebar( $this->parent->configs['help_sidebar'] );
			}
		}

		/**
		 * Add custom admin footer text
		 *
		 * @since  1.0.0
		 * @param  array $footer_text Existing administrator footer.
		 * @return array
		 */
		public function admin_rate_us( $footer_text ) {

			$screen = get_current_screen();

			if ( $screen->id == $this->options_page_id ) {
				$rate_text = sprintf(
					/* translators: %s: Framework website URL. */
					__( 'Thank you for using <a href="%1$s" target="_blank">Easy Options Framework</a>!', 'adz-world' ),
					'http://www.20theme.com/plugins/easy-options-framework'
				);
				return str_replace( '</span>', '', $footer_text ) . ' | ' . $rate_text . '</span>';
			} else {
				return $footer_text;
			}
		}
	}

endif;
