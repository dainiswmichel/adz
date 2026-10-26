<?php
// Exit if accessed directly
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

		// Hold it's parent object which is EOF
		var $parent = null;

		/**
		 * Fire!
		 *
		 * @since 1.0.0
		 */
		public function __construct( $parent ) {
			$this->parent = $parent;
		}

		/**
		 * Create the HTML interface for admin options page
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function admin_options_page() {

			$section_ids = array_keys( $this->parent->sections );
			/*
			 * Which tab to display, from a navigation link on a screen already
			 * gated by capability. It changes nothing and is validated against
			 * the sections that exist, so a nonce would only break bookmarking
			 * and the back button. PHPCS rates this one "recommended" rather
			 * than required for that reason.
			 */
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab selection, validated against the existing sections below.
			$requested   = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
			$active_tab  = in_array( $requested, $section_ids, true ) ? $requested : $section_ids[0];
			$base_url    = menu_page_url( $this->parent->configs['page_slug'], false );

			ob_start();
			?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $this->parent->sections as $section ) {
					$actived = ( $active_tab == $section['id'] ) ? 'nav-tab-active' : '';
					if ( isset( $section['type'] ) && $section['type'] == 'url' ) {
						printf(
							'<a href="%1$s" title="%2$s" class="nav-tab %3$s">%4$s</a>',
							esc_url( $section['url'] ),
							esc_attr( $section['title'] ),
							esc_attr( $actived ),
							esc_html( $section['title'] )
						);
					} else {
						$domain = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
						// find out the path to the current file:
						$path = isset( $_SERVER['SCRIPT_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';
						// find out the QueryString:
						$queryString = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
						// put it all together:
						$url = 'http://' . $domain . $path . '?' . $queryString;
						printf(
							'<a href="%1$s" title="%2$s" class="nav-tab %3$s">%4$s</a>',
							esc_url( add_query_arg( array( 'tab' => $section['id'] ), $url ) ),
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
			$outup = ob_get_contents();
			ob_end_clean();

			/*
			 * The buffered settings page, assembled by the field classes above.
			 * It contains the form controls a settings screen must emit --
			 * input, select, textarea, button -- none of which survive
			 * wp_kses_post, so filtering here would leave an unusable screen.
			 * Each field escapes its own values at render time, which is where
			 * the escaping belongs; this is only the aggregation point.
			 */
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered settings form; each field escapes its own output and kses would strip the form controls.
			echo $outup;
		}
	}

endif;
?>