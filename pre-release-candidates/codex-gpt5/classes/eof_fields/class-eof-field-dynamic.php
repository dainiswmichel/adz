<?php
/**
 * EOF HTML Field Class
 *
 * All the logic for this field type
 *
 * @class       EOF_field_dynamic
 * @extends     EOF_field
 * @package     EOF
 * @subpackages Fields
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EOF_field_dynamic' ) ) :

	/**
	 * Render configured dynamic settings content.
	 */
	class EOF_Field_Dynamic extends EOF_field {

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
					'content' => '',
					'desc'    => '',
					'default' => '',
				)
			);

			// If value does not set, use the default.
			if ( is_null( $this->value ) ) {
				$this->value = $this->field['default'];
			}

			if ( isset( $this->field['callback'] ) ) {
				add_filter( 'dyanmic_field_' . $this->option_id, $this->field['callback'] );
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

			$html = do_shortcode( apply_filters( 'dyanmic_field_' . $this->option_id, $this->field['content'] ) );
			echo wp_kses(
				$html,
				array_merge(
					wp_kses_allowed_html( 'post' ),
					array(
						'input' => array(
							'type'  => true,
							'id'    => true,
							'name'  => true,
							'value' => true,
						),
					)
				)
			);
		}
	}

endif;

add_filter(
	'eof_field_types',
	function ( $field_types ) {
		if ( is_array( $field_types ) ) {
			$field_types[] = 'EOF_field_dynamic';
		}
		return $field_types;
	}
);
