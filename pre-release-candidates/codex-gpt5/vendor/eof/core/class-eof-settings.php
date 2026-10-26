<?php
/**
 * Legacy plugin and settings-framework support.
 *
 * @package adz-world
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EOF_Settings Class
 *
 * This class is for registering settings
 *
 * @package EOF
 * @since 1.0.0
 */
class EOF_Settings {

	/**
	 * Parent settings framework.
	 *
	 * @var EOF
	 */
	public $parent;

	/**
	 * Registered field classes.
	 *
	 * @var array
	 */
	public $field_types = array();

	/**
	 * Initialized settings fields.
	 *
	 * @var array
	 */
	public $setting_fields = array();

	/**
	 * Fire!
	 *
	 * @since 1.0.0
	 * @param EOF $framework Parent settings framework.
	 */
	public function __construct( $framework ) {
		$this->parent = $framework;
		$this->require_files();
		$this->enqueue_field_dependency();
		$this->add_hook();
	}

	/**
	 * Register actions
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function add_hook() {
		add_action( 'admin_init', array( $this, 'run' ) );
		add_action( 'admin_notices', array( $this, 'admin_messages' ) );
	}

	/**
	 * Register action and settings
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function run() {

		// This action must be added after admin_init action!
		if ( isset( $this->parent->configs['options_page_id'] ) ) {
			add_action( 'load-' . $this->parent->configs['options_page_id'], array( $this, 'reset_section_options' ) );
		}

		// Register settings.
		$this->register_settings();
	}

	/**
	 * Regsiter all settings sections and fields
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_settings() {

		if ( false == get_option( $this->parent->configs['opt_name'] ) ) {
			add_option( $this->parent->configs['opt_name'] );

			if ( $this->parent->configs['save_defaults'] ) {
				update_option( $this->parent->configs['opt_name'], $this->parent->default_options );
			}
		}

		foreach ( $this->parent->sections as $section ) {

			// Avoiding blank and link section tab.
			if ( isset( $section['type'] ) && ( 'blank' == $section['type'] || 'url' == $section['type'] ) ) {
				if ( ! empty( $section['callback'] ) ) {
					add_action( 'eof_options_page_' . $this->parent->configs['opt_name'] . '_' . $section['id'], $section['callback'] );
				}
				continue;
			}

			add_settings_section(
				$this->parent->configs['opt_name'] . '_' . $section['id'],
				__return_null(),
				'__return_false',
				$this->parent->configs['opt_name'] . '_' . $section['id']
			);

			if ( isset( $section['fields'] ) && ! empty( $section['fields'] ) ) {
				foreach ( $section['fields'] as $key => $option ) {

					// Add some core configures to field.
					$option['id']          = isset( $key ) ? $key : null;
					$option['section']     = $section['id'];
					$option['option_name'] = "{$this->parent->configs['opt_name']}[{$key}]";
					$option['option_id']   = '';

					add_settings_field(
						$this->parent->configs['opt_name'] . '[' . $key . ']',
						$option['title'],
						array( $this, 'field_callback' ),
						$this->parent->configs['opt_name'] . '_' . $section['id'],
						$this->parent->configs['opt_name'] . '_' . $section['id'],
						$option
					);

				}
			}
		}

		register_setting( $this->parent->configs['opt_name'], $this->parent->configs['opt_name'], array( $this, 'sanitize_callback' ) );
	}

	/**
	 * Field display
	 *
	 * Render the filed output according to field type
	 *
	 * @param array $field Configured field to display.
	 * @since  1.0.0
	 * @return void
	 */
	public function field_callback( $field ) {

		if ( isset( $field['type'] ) ) {

			$display     = true;
			$field_class = "EOF_field_{$field['type']}";
			if ( class_exists( $field_class ) ) {
				$value  = isset( $this->parent->options[ $field['id'] ] ) ? $this->parent->options[ $field['id'] ] : '';
				$render = new $field_class( $field, $value, $this->parent );
				$render->render_field();
			} else {
				esc_html_e( 'This field type does not exist, please check your code.', 'adz-world' );
			}
		}
	}

	/**
	 * Settings Sanitization
	 *
	 * Adds a settings error (for the updated message)
	 * At some point this will validate options
	 *
	 * @param array $options The array of options to sanitize.
	 * @since  1.0.0
	 * @return array $sanitizied_options - The array of sanitized options
	 */
	public function sanitize_callback( $options = array() ) {
		$old_options = get_option( $this->parent->configs['opt_name'], array() );
		$old_options = is_array( $old_options ) ? $old_options : array();
		if ( ! is_array( $options ) ) {
			add_settings_error( 'eof-' . $this->parent->configs['opt_name'] . '-notices', 'invalid-options', __( 'Settings must be a collection of configured fields. Existing settings were preserved.', 'adz-world' ) );
			return $old_options;
		}
		$fields = array();
		foreach ( $this->parent->sections as $section ) {
			if ( ! empty( $section['fields'] ) && is_array( $section['fields'] ) ) {
				$fields = array_merge( $fields, $section['fields'] );
			}
		}
		$sanitizied_options = array();
		foreach ( $options as $key => $value ) {
			if ( ! isset( $fields[ $key ] ) ) {
				add_settings_error( 'eof-' . $this->parent->configs['opt_name'] . '-notices', 'unknown-field', __( 'An unknown or display-only setting was rejected. Existing settings were preserved.', 'adz-world' ) );
				return $old_options;
			}
			if ( in_array( $fields[ $key ]['type'], array( 'heading', 'html', 'dynamic' ), true ) ) {
				continue;
			}
			$field       = $fields[ $key ];
			$field['id'] = $key;
			$value       = $this->sanitize_field( $field, $value );
			if ( is_wp_error( $value ) ) {
				add_settings_error( 'eof-' . $this->parent->configs['opt_name'] . '-notices', $value->get_error_code(), $value->get_error_message() );
				return $old_options;
			}
			$value = apply_filters( "eof_settings_sanitize_{$this->parent->configs['opt_name']}[{$key}]", $value );
			$value = $this->sanitize_field( $field, $value );
			if ( is_wp_error( $value ) ) {
				add_settings_error( 'eof-' . $this->parent->configs['opt_name'] . '-notices', $value->get_error_code(), $value->get_error_message() );
				return $old_options;
			}
			// Keep extension filters, but do not depend on fields being rendered first.
			$sanitizied_options[ $key ] = apply_filters( 'eof_settings_sanitize', $value );
		}
		$output = array_merge( $old_options, $sanitizied_options );
		add_settings_error( 'eof-' . $this->parent->configs['opt_name'] . '-notices', '', __( 'Settings updated!', 'adz-world' ), 'updated' );
		return $output;
	}

	/**
	 * Sanitize configured fields, including repeat children, without form rendering.
	 *
	 * @param array $field Field configuration.
	 * @param mixed $value Submitted value.
	 * @return mixed|WP_Error Sanitized value or an explicit invalid-field error.
	 */
	public function sanitize_field( $field, $value ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		if ( 'repeat' === $type ) {
			if ( null === $value || '' === $value ) {
				return array();
			}
			if ( ! is_array( $value ) ) {
				return new WP_Error( 'invalid-repeat', __( 'Repeat settings must contain rows. Existing settings were preserved.', 'adz-world' ) );
			}
			$rows = array();
			foreach ( $value as $index => $row ) {
				if ( 'field_count' === $index ) {
					continue;
				}
				if ( ! is_array( $row ) ) {
					return new WP_Error( 'invalid-repeat-row', __( 'A repeat settings row is invalid. Existing settings were preserved.', 'adz-world' ) );
				}
				$clean = array();
				foreach ( $field['sub_fields'] as $key => $child ) {
					$clean[ $key ] = $this->sanitize_field( $child, isset( $row[ $key ] ) ? $row[ $key ] : '' );
					if ( is_wp_error( $clean[ $key ] ) ) {
						return $clean[ $key ];
					}
				}
				if ( 'adz_ad_options' === $this->parent->configs['opt_name'] && 'ad_settings' === $field['id'] ) {
					$rotation_id = isset( $row['rotation_id'] ) ? $row['rotation_id'] : 'adz_rotation_' . count( $rows );
					if ( ! is_string( $rotation_id ) || ! preg_match( '/^adz_rotation_[0-9]+$/D', $rotation_id ) ) {
						return new WP_Error( 'invalid-rotation-id', __( 'A rotation identifier is invalid. Existing settings were preserved.', 'adz-world' ) );
					}
					$clean['rotation_id'] = $rotation_id;
				}
				$rows[] = $clean;
			}
			return $rows;
		}
		if ( 'checkbox' === $type ) {
			if ( empty( $field['options'] ) ) {
				return in_array( $value, array( true, 1, '1' ), true ) ? '1' : '0';
			}
			$value = is_array( $value ) ? $value : array();
		}
		if ( in_array( $type, array( 'select', 'radio', 'image_select', 'select_caps', 'checkbox' ), true ) ) {
			if ( null === $value ) {
				$value = '';
			}
			$choices = isset( $field['options'] ) && is_array( $field['options'] ) ? array_keys( $field['options'] ) : array();
			if ( 'select_caps' === $type ) {
				$choices   = array_keys( wp_roles()->get_names() );
				$choices[] = 'guest';
			}
			$choices = array_map( 'strval', $choices );
			$values  = is_array( $value ) ? $value : array( $value );
			$clean   = array();
			foreach ( $values as $entry ) {
				if ( ! is_scalar( $entry ) || ( '' !== (string) $entry && ! in_array( (string) $entry, $choices, true ) ) ) {
					return new WP_Error( 'invalid-choice', __( 'A settings choice is not available. Existing settings were preserved.', 'adz-world' ) );
				}
				if ( '' !== (string) $entry ) {
					$clean[] = (string) $entry;
				}
			}
			return is_array( $value ) || ( isset( $field['multiple'] ) && 'yes' === $field['multiple'] ) ? $clean : ( isset( $clean[0] ) ? $clean[0] : '' );
		}
		if ( ! is_scalar( $value ) && null !== $value ) {
			return new WP_Error( 'invalid-field', __( 'A settings field has an invalid value. Existing settings were preserved.', 'adz-world' ) );
		}
		$value = (string) $value;
		switch ( $type ) {
			case 'email':
				return sanitize_email( $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'rich_editor':
				return wp_kses_post( $value );
			case 'password':
				return $value;
			case 'number':
				if ( '' !== $value && ! is_numeric( $value ) ) {
					return new WP_Error( 'invalid-number', __( 'A numeric setting is invalid. Existing settings were preserved.', 'adz-world' ) );
				}
				return $value;
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Rest all settings belong to current section tab
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function reset_section_options() {

		if ( isset( $_GET['settings-updated'] ) ) {
			return;
		}

		if ( ! isset( $_GET['tab'] ) || ! isset( $_GET['action'] ) || ! isset( $_GET['reset_nonce'] ) ) {
			return;
		}

		$capability = $this->parent->configs['page_permissions'];
		$action     = is_string( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$nonce      = is_string( $_GET['reset_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['reset_nonce'] ) ) : '';
		$tab        = is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		if ( 'reset' !== $action || ! current_user_can( $capability ) || ! wp_verify_nonce( $nonce, 'eof_reset' ) ) {
			add_settings_error( 'eof-' . $this->parent->configs['opt_name'] . '-notices', 'forbidden-reset', __( 'The settings reset could not be authorized.', 'adz-world' ) );
			return;
		}
		if ( empty( $this->parent->sections[ $tab ]['fields'] ) ) {
			add_settings_error( 'eof-' . $this->parent->configs['opt_name'] . '-notices', 'invalid-section', __( 'The requested settings section does not exist.', 'adz-world' ) );
			return;
		}
		if ( 'reset' === $action ) {
			$section     = $this->parent->sections[ $tab ];
			$new_options = array();
			foreach ( $section['fields'] as $key => $field ) {
				$new_options[ $key ] = isset( $field['default'] ) ? $field['default'] : '';
			}
			$new_options = wp_parse_args( $new_options, $this->parent->options );

			update_option( $this->parent->configs['opt_name'], $new_options );

			add_settings_error( 'eof-' . $this->parent->configs['opt_name'] . '-notices', '', __( 'Section Defaults Restored!', 'adz-world' ), 'updated' );

			$this->parent->refresh();
		}
	}

	/**
	 * Require field class files
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function require_files() {
		require EOF_DIR . 'core/class-eof-field.php';
		require EOF_DIR . 'fields/class-eof-field-text.php';
		require EOF_DIR . 'fields/class-eof-field-date.php';
		require EOF_DIR . 'fields/class-eof-field-email.php';
		require EOF_DIR . 'fields/class-eof-field-password.php';
		require EOF_DIR . 'fields/class-eof-field-textarea.php';
		require EOF_DIR . 'fields/class-eof-field-checkbox.php';
		require EOF_DIR . 'fields/class-eof-field-radio.php';
		require EOF_DIR . 'fields/class-eof-field-number.php';
		require EOF_DIR . 'fields/class-eof-field-select.php';
		require EOF_DIR . 'fields/class-eof-field-image-select.php';
		require EOF_DIR . 'fields/class-eof-field-post-select.php';
		require EOF_DIR . 'fields/class-eof-field-media.php';
		require EOF_DIR . 'fields/class-eof-field-rich-editor.php';
		require EOF_DIR . 'fields/class-eof-field-color.php';
		require EOF_DIR . 'fields/class-eof-field-heading.php';
		require EOF_DIR . 'fields/class-eof-field-html.php';
		require EOF_DIR . 'fields/class-eof-field-repeat.php';

		$types             = array(
			'EOF_field_text',
			'EOF_field_password',
			'EOF_field_textarea',
			'EOF_field_rich_editor',
			'EOF_field_color',
			'EOF_field_date',
			'EOF_field_email',
			'EOF_field_image_select',
			'EOF_field_checkbox',
			'EOF_field_post_select',
			'EOF_field_radio',
			'EOF_field_repeat',
			'EOF_field_number',
			'EOF_field_select',
			'EOF_field_media',
			'EOF_field_html',
			'EOF_field_heading',
		);
		$types             = apply_filters_deprecated( 'eof_fieldTypes', array( $types ), '1.1.0', 'eof_field_types' );
		$this->field_types = apply_filters(
			'eof_field_types',
			$types
		);
	}

	/**
	 * Enqueue setting fields
	 * in order to setup field dependency, it must
	 * be called once first to add it's own actions
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_field_dependency() {

		foreach ( $this->parent->sections as $section ) {

			// Avoiding blank section tab.
			if ( isset( $section['type'] ) && ( 'blank' == $section['type'] || 'url' == $section['type'] ) ) {
				continue;
			}

			foreach ( $section['fields'] as $key => $field ) {

				$field['option_name'] = "{$this->parent->configs['opt_name']}[{$key}]";
				$field['option_id']   = '';
				$field['id']          = $key;

				if ( isset( $field['type'] ) ) {
					$field_class = "EOF_field_{$field['type']}";
					if ( class_exists( $field_class ) ) {
						$value = '';
						// $value  = isset ( $this->parent->options[ $field['id'] ] ) ? $this->parent->options[ $field['id'] ] : '';
						$this->setting_fields[ $field['id'] ] = new $field_class( $field, $value, $this->parent );
					}
				}
			}
		}
	}

	/**
	 * Admin notices
	 * Display option messages after updated options
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function admin_messages() {
		settings_errors( 'eof-' . $this->parent->configs['opt_name'] . '-notices' );
	}
}
