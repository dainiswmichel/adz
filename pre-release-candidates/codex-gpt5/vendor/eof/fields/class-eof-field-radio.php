<?php
/**
 * TOF Radio Field Class
 *
 * All the logic for this field type
 *
 * @class       EOF_field_radio
 * @extends     EOF_field
 * @package     EOF
 * @subpackages Fields
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EOF_field_radio' ) ) :

	/**
	 * Configured eof field radio component.
	 */
	class EOF_Field_Radio extends EOF_field {

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
					'readonly' => false,
					'style'    => 1,
					'options'  => null,
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

			foreach ( $this->field['options'] as $key => $label ) {

				if ( '2' === (string) $this->field['style'] ) {
					?>
			<label for="<?php echo esc_attr( $this->option_name ); ?>" style="display:inline-block;margin-right:10px;">
			<input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>" id="<?php echo esc_attr( $this->option_id ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $this->value, true ); ?> >
					<?php echo esc_html( $label ); ?></label>
					<?php
				} else {
					?>
			<input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>" id="<?php echo esc_attr( $this->option_id ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $this->value, true ); ?> >
			<span class="description"><?php echo esc_html( $label ); ?></span>
			<br>
					<?php
				}
			}
			echo '<p class="description" style="margin-top:15px;">' . wp_kses_post( $this->field['desc'] ) . '</p>';
		}

		/**
		 * Sanitize the configured field value.
		 *
		 * @param mixed $value Current field value.
		 */
		public function sanitize( $value ) {
			$sanitize_value = $value;
			return $sanitize_value;
		}
	}

endif;

?>