<?php
/**
 * EOF Textarea Field Class
 *
 * All the logic for this field type
 *
 * @class       EOF_field_textarea
 * @extends     EOF_field
 * @package     EOF
 * @subpackages Fields
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EOF_field_textarea' ) ) :

	/**
	 * Configured eof field textarea component.
	 */
	class EOF_Field_Textarea extends EOF_field {

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
					'default'  => '',
					'rows'     => 20,
					'cols'     => 80,
					'sizes'    => 'large',
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
		 * Render field
		 *
		 * Create the HTML interface for your field
		 *
		 * @since 1.0
		 * @return void
		 */
		public function render_field() {

			$class = '';

			switch ( $this->field['sizes'] ) {
				case 'small':
					$class .= ' small-text';
					break;
				case 'large':
					$class .= ' large-text';
					break;
				default:
					$class .= ' regular-text';
					break;
			}

			?>
		<textarea class="<?php echo esc_attr( $class ); ?>" name="<?php echo esc_attr( $this->option_name ); ?>" id="<?php echo esc_attr( $this->option_id ); ?>" cols="<?php echo absint( ( is_int( $this->field['cols'] ) ) ? $this->field['cols'] : 80 ); ?>" rows="<?php echo absint( ( is_int( $this->field['rows'] ) ) ? $this->field['rows'] : 20 ); ?>"><?php echo esc_textarea( $this->value ); ?></textarea>
		<br/>
		<p class="description"><?php echo wp_kses_post( $this->field['desc'] ); ?></p>
			<?php
		}

		/**
		 * Sanitize the configured field value.
		 *
		 * @param mixed $input Submitted field value.
		 */
		public function sanitize( $input ) {
			// global $allowedposttags;.

			// $sanitize_input = wp_kses( $input, $allowedposttags );
			$sanitize_input = $input;

			return $sanitize_input;
		}
	}

endif;

?>