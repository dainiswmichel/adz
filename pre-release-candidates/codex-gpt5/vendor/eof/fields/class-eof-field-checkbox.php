<?php
/**
 * TOF Checkbox Field Class
 *
 * All the logic for this field type
 *
 * @class       EOF_field_checkbox
 * @extends     EOF_field
 * @package     EOF
 * @subpackages Fields
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EOF_field_checkbox' ) ) :

	/**
	 * Configured eof field checkbox component.
	 */
	class EOF_Field_Checkbox extends EOF_field {

		/**
		 * __construct
		 *
		 * This function will setup the field type data
		 *
		 * @since 1.0
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
					'default'  => null,
					'readonly' => false,
					'style'    => 1,
					'options'  => null,
				)
			);

			parent::__construct( $this->field );
		}

		/**
		 * Render field
		 *
		 * Create the HTML interface for your field
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function render_field() {

			// multicheck.
			if ( isset( $this->field['options'] ) && null !== $this->field['options'] ) {

				$values = (array) $this->value;
				?>
			<input type="hidden" name="<?php echo esc_attr( $this->option_name ); ?>" value="0" />
				<?php
				foreach ( $this->field['options'] as $key => $label ) {
					$enabled = null;
					if ( in_array( $key, $values ) ) {
						$enabled = 1;
					}
					if ( isset( $this->field['style'] ) && '2' === (string) $this->field['style'] ) {
						?>
				<label for="<?php echo esc_attr( $this->option_name . '[]' ); ?>" style="display:inline-block;margin-right:10px;">
				<input type="checkbox" name="<?php echo esc_attr( $this->option_name . '[]' ); ?>" id="<?php echo esc_attr( $this->option_id ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( 1, $enabled, true ); ?> >
						<?php echo esc_html( $label ); ?></label>
						<?php
					} else {
						?>
				<input type="checkbox" name="<?php echo esc_attr( $this->option_name . '[]' ); ?>" id="<?php echo esc_attr( $this->option_id ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( 1, $enabled, true ); ?> >
				<label for="<?php echo esc_attr( $this->option_name . '[]' ); ?>"><?php echo esc_html( $label ); ?></label>
				<br>
						<?php
					}
				}
				echo '<p class="description" style="margin-top:15px;">' . wp_kses_post( $this->field['desc'] ) . '</p>';
			} else {
				?>
			<input type="hidden" name="<?php echo esc_attr( $this->option_name ); ?>" value="0" />
		<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>" id="<?php echo esc_attr( $this->option_id ); ?>" value="1" <?php checked( 1, $this->value, true ); ?> >
		<span class="description"><?php echo wp_kses_post( $this->field['desc'] ); ?></span>
				<?php
			}
		}

		/**
		 * Sanitize the configured field value.
		 *
		 * @param mixed $input Submitted field value.
		 */
		public function sanitize( $input ) {
			$sanitize_input = null;

			if ( is_array( $input ) ) {
				$sanitize_input = $input;
			} else {
				$sanitize_input = ( $input ) ? '1' : '0';
			}

			return $sanitize_input;
		}
	}

endif;

?>