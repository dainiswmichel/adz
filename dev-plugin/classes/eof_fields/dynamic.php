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
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( !class_exists('EOF_field_dynamic') ) :

class EOF_field_dynamic extends EOF_field {

	/**
	 * __construct
	 *
	 * This function will setup the field type data
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct( $field, $value, $parent ) {
		//vars
		$this->parent = $parent;
		$this->option_name = $field['option_name'];
		$this->option_id   = parent::beautifyid($field['option_name']);

		$this->value = $value;
		$this->field = wp_parse_args( $field, array(
			'id'			=> '',
			'title'			=> '',
			'content'		=> '',
			'desc'			=> '',
			'default' 		=> ''
		) );

		// If value does not set, use the default
		if( is_null($this->value) ) {
			$this->value = $this->field['default'];
		}

		if (isset($this->field['callback'])) {
			add_filter('dyanmic_field_'.$this->option_id,$this->field['callback']);
		}

		//parent::__construct($field);
	}

	/**
	 * Render field
	 *
	 * Create the HTML interface for your field
	 *
	 * @param $field - an array holding all the field's data
	 *
	 * @since 1.0
	 * @return void
	 */
	public function render_field() {

		$html = do_shortcode(apply_filters('dyanmic_field_'.$this->option_id,$this->field['content']));
		echo $html;
	}

}

endif;

add_filter('eof_fieldTypes', function ($field_types) {
	if (is_array($field_types)) {
		$field_types[] = 'EOF_field_dynamic';
	}
	return $field_types;
});
