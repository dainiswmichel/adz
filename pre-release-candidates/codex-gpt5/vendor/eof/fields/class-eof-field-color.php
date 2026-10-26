<?php
/**
 * EOF Color Picker Field Class
 *
 * All the logic for this field type
 *
 * @class       EOF_field_color
 * @extends     EOF_field
 * @package     EOF
 * @subpackages Fields
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EOF_field_color' ) ) :

	/**
	 * Configured eof field color component.
	 */
	class EOF_Field_Color extends EOF_field {

		/**
		 * __construct
		 *
		 * This function will setup the field type data
		 *
		 * @since 1.0.0
		 * @return void
		 * @param array $field Field configuration.
		 * @param mixed $value Current field value.
		 * @param EOF   $framework Parent settings framework.
		 */
		public function __construct( $field, $value, $framework ) {
			// vars.
			$this->parent      = $framework;
			$this->option_name = $field['option_name'];
			$this->option_id   = parent::beautifyid( $field['option_name'] );

			$this->value = $value;
			$this->field = wp_parse_args(
				$field,
				array(
					'id'       => '',
					'title'    => '',
					'desc'     => '',
					'default'  => '#ffffff',
					'readonly' => false,
				)
			);

			// If value does not set, use the default.
			if ( is_null( $this->value ) ) {
				$this->value = $this->field['default'];
			}

			parent::__construct( $this->field );
		}

		/**
		 * Enqueue scripts
		 *
		 * Enqueue scripts and styles that field needed.
		 * This function will be called by it parent class
		 * by default, so you don't need to add action again.
		 *
		 * @since 1.0
		 * @return void
		 */
		public static function admin_enqueue_scripts() {

			if ( is_admin() ) {
				wp_enqueue_style( 'wp-color-picker' );
			}
		}

		/**
		 * Render field
		 *
		 * Create the HTML interface for your field
		 *
		 * @since 1.0
		 * @return void
		 */
		public function render_field() {

			// input type can also be set as 'color', seems fun.
			?>
		<input type="text" class="eof-color-picker" name="<?php echo esc_attr( $this->option_name ); ?>" id="<?php echo esc_attr( $this->option_id ); ?>" value="<?php echo esc_attr( $this->value ); ?>">
		<span class="description"><?php echo esc_js( $this->field['desc'] ); ?></span>
			<?php
		}

		/**
		 * Sanitize the configured field value.
		 *
		 * @param mixed $input Submitted field value.
		 */
		public function sanitize( $input ) {

			$sanitize_input = sanitize_hex_color( $input );
			if ( null === $sanitize_input ) {
				add_settings_error( 'eof-' . $this->parent->configs['opt_name'] . '-notices', 'invalid-color', __( 'The color setting must be a valid hexadecimal color.', 'adz-world' ) );
				return $this->value;
			}
			return $sanitize_input;
		}
	}

endif;

?>