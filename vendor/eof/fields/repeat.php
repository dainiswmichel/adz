<?php  
/**
 * EOF Repeat Field Class
 *
 * All the logic for this field type
 *
 * @class       EOF_field_repeat
 * @extends     EOF_field
 * @package     EOF
 * @subpackages Fields
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( !class_exists('EOF_field_repeat') ) :

class EOF_field_repeat extends EOF_field {

	/**
	 * __construct
	 *
	 * This function will setup the field type data
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct( $field, $value, $parent ) {
		
		$this->parent = $parent;
		$this->option_name = $field['option_name'];
		$this->option_id   = parent::beautifyid($field['option_name']);

		$this->value = $value;
		$this->field = wp_parse_args( $field, array(
			'id'			=> '',
			'title'			=> '',
			'desc'			=> '',
			'default' 		=> null,
			'sizes'			=> '',
			'readonly'		=> false,
			'sub_fields'	=> null
		) );

		// If value does not set, use the default
		if( is_null($this->value) ) {
			$this->value = $this->field['default'];
		}

		parent::__construct($this->field);
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
		$values = (array) $this->value;
		$count = 0;

		$class = 'widefat eof-repeat-table';
		switch ($this->field['sizes']) {
			case "small":
				$class .= ' small-table';
				break;
			case "regular":
				$class .= ' regular-table';
				break;
			default:
				$class .= '';
				break;
		}
	?>
		<div class="eof-repeat-table-wrap">
			<table id="<?php echo $this->field['id'] ?>" class="<?php echo $class; ?>">
				<thead>
					<tr>
					<?php  
						foreach ($this->field['sub_fields'] as $key => $sub_field) {
							printf('<th scope="col">%1$s</th>',
								esc_html($sub_field['title'])
							);
						}
					?>
						<th scope="col" class="item-action"><?php _e('Action', 'eof'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr class="clone" style="display: none;">
					<?php 
						$i = 0;
						foreach ($this->field['sub_fields'] as $key => $sub_field) {

							// Add some core configures to field
							$sub_field['id'] = isset($key) ? $key : null;
							$sub_field['option_name'] = "{$this->option_name}[field_count][{$key}]";
							$sub_field['option_id']   = '';
							$sub_field['default'] = isset($sub_field['default']) ? $sub_field['default'] : null;
							echo '<td>';
							if( !empty($sub_field['type']) ) {

								$field_class = "EOF_field_{$sub_field['type']}";
								if( class_exists($field_class) ) {
									$render = new $field_class( $sub_field, $sub_field['default'], $this->parent);
									$render->render_field();
								} else {
									_e('The field type does not exisits, please check your code.', 'eof');
								}

							}
							echo '</td>';	
							$i += 1;
						}; 
					?>
						<td class="item-action">

							<a class="duplicate_row" title="Duplicate" href="javascript:void()"><span class="dashicons dashicons-welcome-add-page"></span></a> <a class="remove_row" title="Delete" href="javascript:void()"><span class="dashicons dashicons-trash"></span></a> 
													<input name="adz_ad_options[ad_settings][field_count][rotation_id]"  value="adz_rotation_field_count" type="hidden">
						</td>
					</tr>
				<?php foreach ($values as $value) : ?>
					<tr>
					<?php  
						foreach ($this->field['sub_fields'] as $key => $sub_field) {

							// Add some core configures to field
							$sub_field['id'] = isset($key) ? $key : null;
							$sub_field['option_name'] = "{$this->option_name}[{$count}][{$key}]";
							$sub_field['option_id']   = '';

							echo '<td>';
							if( isset($sub_field['type']) ) {

								$field_class = "EOF_field_{$sub_field['type']}";
								if( class_exists($field_class) ) {
									
									$field_value = isset($value[$key]) ? $value[$key] : null;
									$render = new $field_class($sub_field, $field_value, $this->parent);
									$render->render_field();
								} else {
									_e('The field type does not exisits, please check your code.', 'eof');
								}

							}
							echo '</td>';	
						}
					?>
						<td class="item-action"><a class="duplicate_row" title="Duplicate" href="javascript:void()"><span class="dashicons dashicons-welcome-add-page"></span></a> <a class="remove_row" title="Delete" href="javascript:void()"><span class="dashicons dashicons-trash"></span></a> 
						<input name="adz_ad_options[ad_settings][<?php echo $count; ?>][rotation_id]"  value="adz_rotation_<?php echo $count; ?>" type="hidden"> </td>
					</tr>	
				<?php $count += 1;endforeach; ?>
					
				</tbody>
			</table>
			<p class="description"><?php echo $this->field['desc']; ?></p>
			<p><input class="button add-row" type="button" data-count="<?php echo $count; ?>" value="<?php _e('Add', 'eof') ?>" /></p>
		</div>
	<?php
	}

	public function sanitize( $value ) {
		// Remove the hidden element for clone.
		$sanitize_value = array_slice($value, 1);
		return $sanitize_value;
	}
}

endif;


?>