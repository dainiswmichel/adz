<?php
/**
 * EOF Heading Field Class
 *
 * All the logic for this field type
 *
 * @class       EOF_field_heading
 * @extends     EOF_field
 * @package     EOF
 * @subpackages Fields
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EOF_field_heading' ) ) :

	/**
	 * Configured eof field heading component.
	 */
	class EOF_Field_Heading extends EOF_field {

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
					'id'      => '',
					'title'   => '',
					'desc'    => '',
					'default' => '',
				)
			);

			// If value does not set, use the default.
			if ( is_null( $this->value ) ) {
				$this->value = $this->field['default'];
			}

			// parent::__construct($field);.
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
			?>
		<hr>
			<?php
			if ( ! empty( $this->field['desc'] ) ) {
				echo '<p class="description">' . wp_kses_post( $this->field['desc'] ) . '</p>';
			}
		}
	}

endif;
?>