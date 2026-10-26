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

if ( ! class_exists( 'EOF_Views' ) ) :
	/**
	 * EOF_Views Class
	 *
	 * This class is for holding all admin setting page views
	 *
	 * @package EOF
	 * @since 1.0.0
	 */
	class EOF_Views {

		/**
		 * Hold it's parent object which is EOF.
		 *
		 * @var mixed
		 */
		public $parent = null;

		/**
		 * Fire!
		 *
		 * @since 1.0.0
		 * @param EOF $framework Parent settings framework.
		 */
		public function __construct( $framework ) {
			$this->parent = $framework;
		}

		/**
		 * Create the HTML interface for admin options page
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function admin_options_page() {

			$section_ids = array_keys( $this->parent->sections );
			$active_tab  = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $section_ids[0];
			$base_url    = menu_page_url( $this->parent->configs['page_slug'], false );
			if ( ! isset( $this->parent->sections[ $active_tab ] ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'The requested settings section does not exist.', 'adz-world' ) . '</p></div>';
				return;
			}
			?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $this->parent->sections as $section ) {
					$actived = ( $active_tab == $section['id'] ) ? 'nav-tab-active' : '';
					if ( isset( $section['type'] ) && 'url' === $section['type'] ) {
						printf(
							'<a href="%1$s" title="%2$s" class="nav-tab %3$s">%4$s</a>',
							esc_url( $section['url'] ),
							esc_attr( $section['title'] ),
							esc_attr( $actived ),
							esc_html( $section['title'] )
						);
					} else {
						printf(
							'<a href="%1$s" title="%2$s" class="nav-tab %3$s">%4$s</a>',
							esc_url( add_query_arg( 'tab', $section['id'], $base_url ) ),
							esc_attr( $section['title'] ),
							esc_attr( $actived ),
							esc_html( $section['title'] )
						);
					}
				}
				?>
			</h2>
			<!-- END .nav-tab-wrapper -->
			<div id="tab_container">
				<?php
				if ( isset( $this->parent->sections[ $active_tab ]['type'] ) && 'blank' == $this->parent->sections[ $active_tab ]['type'] ) {
					do_action( 'eof_options_page_' . $this->parent->configs['opt_name'] . '_' . $this->parent->sections[ $active_tab ]['id'] );
				} else {
					?>
				<form action="options.php" method="post">
					<table class="form-table eof-form-table">
					<?php
					settings_fields( $this->parent->configs['opt_name'] );
					do_settings_fields( $this->parent->configs['opt_name'] . '_' . $active_tab, $this->parent->configs['opt_name'] . '_' . $active_tab );
					?>
										</table>
					<!-- END .form-table -->
					<p class="submit">
					<?php
					$link = add_query_arg(
						array(
							'tab'         => $active_tab,
							'action'      => 'reset',
							'reset_nonce' => wp_create_nonce( 'eof_reset' ),
						),
						$base_url
					);
					?>
						
					<?php
						submit_button( __( 'Save Changes', 'adz-world' ), 'primary', 'eof-save', false );
					?>
					</p>
				</form>
			<?php } ?>
			</div>
			<!-- END #tab_container -->
		</div>
		<!-- END .wrap -->
			<?php
		}
	}

endif;
?>