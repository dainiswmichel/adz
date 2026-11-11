<?php
/**
 * EOF Select Field Class
 *
 * All the logic for this field type
 *
 * @class       EOF_field_select
 * @extends     EOF_field
 * @package     EOF
 * @subpackages Fields
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EOF_field_select' ) ) :

	/**
	 * Configured eof field select component.
	 */
	class EOF_Field_Select extends EOF_field {

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
					'holder'   => __( 'Select', 'adz-world' ),
					'sizes'    => 'regular',
					'readonly' => false,
					'options'  => null,
					'multiple' => 'no',

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

			$options = (array) $this->field['options'];

			$class = '';
			switch ( $this->field['sizes'] ) {
				case 'small':
					$class .= ' seof-small-select';
					break;
				case 'large':
					$class .= ' eof-large-select';
					break;
				default:
					$class .= ' eof-regular-select';
					break;
			}

			?>
		
			<?php if ( 'yes' === $this->field['multiple'] ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $this->option_name ); ?>" value="">
			<?php endif; ?>
		<select class="<?php echo esc_attr( $class ); ?>" <?php echo ( 'yes' === $this->field['multiple'] ) ? 'multiple="multiple"' : ''; ?> name="<?php echo ( 'yes' === $this->field['multiple'] ) ? esc_attr( $this->option_name ) . '[]' : esc_attr( $this->option_name ); ?>" id="<?php echo esc_attr( $this->option_id ); ?>">
			<?php
			// Placeholder.
			if ( empty( $options ) ) {
				echo '<option value="" selected>' . esc_html__( 'Nothing found.', 'adz-world' ) . '</option>';
			}
			$count = 0;
			foreach ( $options as $val => $label ) {
				if ( 'yes' === $this->field['multiple'] ) {
					$is_selected = in_array( (string) $val, array_map( 'strval', (array) $this->value ), true );
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $val ),
						selected( true, $is_selected, false ),
						esc_html( $label )
					);
				} else {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $val ),
						selected( $val, $this->value, false ),
						esc_html( $label )
					);
				}
				++$count;
			}
			?>
		</select>
		
		<span class="description"><?php echo wp_kses_post( $this->field['desc'] ); ?></span>
			<?php
		}

		/**
		 * Sanitize the configured field value.
		 *
		 * @param mixed $input Submitted field value.
		 */
		public function sanitize( $input ) {

			$sanitize_input = $input;

			return $sanitize_input;
		}
	}

endif;

?>