<?php

if ( ! class_exists( 'DWMAdNetworkAdminUI' ) ) {
	class DWMAdNetworkAdminUI {
		public static function menu() {
			add_options_page( 'Admin Ad Network Settings', 'Ad Network', 'manage_options', 'admin-revenue', array( 'DWMAdNetworkAdminUI', 'admin_screen' ) );
		}
		public static function admin_screen() {
			$active_tab = 'default-settings';
			if ( isset( $_GET['tab'] ) ) {
				$active_tab = $_GET['tab'];
			} // end if
			?>
			<h1>DWM Ad Network Settings</h1>
			<h2 class="nav-tab-wrapper">
				<a href="?page=admin-revenue&tab=default-settings" class="nav-tab <?php echo $active_tab == 'default-settings' ? 'nav-tab-active' : ''; ?>">Default Settings</a>
				<a href="?page=admin-revenue&tab=network-settings" class="nav-tab <?php echo $active_tab == 'network-settings' ? 'nav-tab-active' : ''; ?>">Network Settings</a>
				<a href="?page=admin-revenue&tab=adcode-templates" class="nav-tab <?php echo $active_tab == 'adcode-templates' ? 'nav-tab-active' : ''; ?>">Ad Code Templates</a>
			</h2>
			<form method="POST" action="options.php">
				<?php
				settings_fields( 'adz_adnet_options' );
				switch ($active_tab) {
					case 'default-settings':
						do_settings_sections( 'adz_adnet_admin_def' );
					break;
					case 'network-settings' :
						do_settings_sections( 'adz_admin_net_set' );
					break;
					case 'adcode-templates':
						do_settings_sections( 'adz_admin_adtemplates' );
					break;
				}
				echo "<input type='hidden' name='active-tab' value='$active_tab' />";
				echo '<input type="hidden" name="dwm-setting" value="set" />';
				submit_button();
				?>
				</form>
			<?php
		}
		public static function wp_init() {
			// Setup WP hooks
			add_action( 'admin_menu', array( 'DWMAdNetworkAdminUI', 'menu' ) );
			add_action( 'admin_init', array( 'DWMAdNetworkAdminUI', 'setup_options' ) );
		}
		public static function setup_options() {
			register_setting( 'adz_adnet_options', 'adz_adnet_options', array( 'DWMAdNetworkAdminUI', 'adz_adnet_options_validate' ) );
			add_settings_section( 'adz_adnet_admin_default', 'Default Settings',array( 'DWMAdNetworkAdminUI', 'dafault_section_text' ), 'adz_adnet_admin_def' );
			add_settings_field( 'adz_admin_pub_id', 'Publisher ID', array( 'DWMAdNetworkAdminUI', 'pub_id_settings' ), 'adz_adnet_admin_def', 'adz_adnet_admin_default' );
			add_settings_field( 'adz_admin_data_slot', 'Ad Slot', array( 'DWMAdNetworkAdminUI', 'data_slot_settings' ), 'adz_adnet_admin_def', 'adz_adnet_admin_default' );
			add_settings_field( 'adz_admin_publisher_share', 'Default Publisher Share', array( 'DWMAdNetworkAdminUI', 'publisher_share_settings' ), 'adz_adnet_admin_def', 'adz_adnet_admin_default' );

			add_settings_section( 'adz_network_settings', 'Network Settings',array( 'DWMAdNetworkAdminUI', 'network_settings_text' ), 'adz_admin_net_set' );
			add_settings_field( 'adz_admin_place_holder', 'Settings Place Holder', array( 'DWMAdNetworkAdminUI', 'network_settings' ), 'adz_admin_net_set', 'adz_network_settings' );

			add_settings_section( 'adz_ad_code_template', 'Ad Templates',array( 'DWMAdNetworkAdminUI', 'adtemplate_section_text' ), 'adz_admin_adtemplates' );
			add_settings_field( 'adz_admin_adsense', 'Adsense ', array( 'DWMAdNetworkAdminUI', 'adsense_template_settings' ), 'adz_admin_adtemplates', 'adz_ad_code_template' );
			add_settings_field( 'adz_admin_adnetwork_2', 'Ad Network 2', array( 'DWMAdNetworkAdminUI', 'adnet2_settings' ), 'adz_admin_adtemplates', 'adz_ad_code_template' );
			add_settings_field( 'adz_admin_adnetwork_3', 'Ad Network 3', array( 'DWMAdNetworkAdminUI', 'adnet3_settings' ), 'adz_admin_adtemplates', 'adz_ad_code_template' );


		}

		public static function dafault_section_text() {
			?>
			<p>Set up your adsense publisher information and the default publisher revenue share. (<em>Other Ids to Follow</em>)</p>
			<?php
		}

		public static function network_settings_text() {
			?>
			<p>This is a placeholder for network wide settings </p>
			<?php
		}
		public static function adtemplate_section_text() {
			?>
			<p>Enter the code template for each add type.
				<br/>Use <strong>{pub}</strong> as the publisher id place holder.
				<br/>Use <strong>{ad-slot}</strong> as the ad slot id placeholder.
				<br/>Delete all code and leave blank to restore the default code;
			</p>
			<h3>Adcode Templates</h3>
			<?php
		}

		public static function pub_id_settings() {
			$options = get_option( 'adz_adnet_options' );
			echo "<input id='adz_admin_pub_id' name='adz_adnet_options[pub_id]' size='40' type='text' value='{$options['pub_id']}' />";
		}

		public static function data_slot_settings() {
			$options = get_option( 'adz_adnet_options' );
			echo "<input id='adz_admin_data_slot' name='adz_adnet_options[data_slot]' size='40' type='text' value='{$options['data_slot']}' />";
		}

		public static function publisher_share_settings() {
			$options = get_option( 'adz_adnet_options' );
			echo "<input id='adz_admin_publisher_share' name='adz_adnet_options[publisher_share]' size='40' type='text' value='{$options['publisher_share']}' />";
		}

		public static function network_settings() {
			$options = get_option( 'adz_adnet_options' );
			?>
			<textarea id='adz_admin_place_holder' name='adz_adnet_options[settings_placeholder]' style="width: 75%; height: 300px; "><?php echo $options['settings_placeholder']; ?></textarea>
			<?php
		}

		public static function adsense_template_settings() {
			$options = get_option( 'adz_adnet_options' );
			if ( ! isset( $options['adsense-template'] ) || empty( $options['adsense-template'] ) ) {
				$options['adsense-template'] = self::default_adsense_code();
			}
			?>
			<textarea id='adz_admin_adsense' name='adz_adnet_options[adsense-template]' style="width: 75%; height: 200px; "><?php echo $options['adsense-template']; ?></textarea>
			<?php
		}

		public static function adnet2_settings() {
			$options = get_option( 'adz_adnet_options' );
			if ( ! isset( $options['adnet2_template'] ) || empty( $options['adnet2_template'] ) ) {
				$options['adnet2_template'] = '';
			}
			?>
			<textarea id='adz_admin_adnetwork_2' name='adz_adnet_options[adnet2_template]' style="width: 75%; height: 200px; "><?php echo $options['adnet2_template']; ?></textarea>
			<?php
		}

		public static function adnet3_settings() {
			$options = get_option( 'adz_adnet_options' );
			if ( ! isset( $options['adnet3_template'] ) || empty( $options['adnet3_template'] ) ) {
				$options['adnet3_template'] = '';
			}
			?>
			<textarea id='adz_admin_adnetwork_2' name='adz_adnet_options[adnet3_template]' style="width: 75%; height: 200px; "><?php echo $options['adnet3_template']; ?></textarea>
			<?php
		}

		public static function adz_adnet_options_validate( $input ) {
			if (! isset($input['dwm-setting'])) {
				return $input;
			}
			$active_tab = 'default-settings';
			if ( isset( $_POST['active-tab'] ) ) {
				$active_tab = $_POST['active-tab'];
			} else {
				add_settings_error( 'adz_adnet_options', 'smas_auth', 'No Active Tab', 'error' );
			}
			$newinput = array();
			switch ( $active_tab ) {
				case 'default-settings':
					$newinput['pub_id'] = trim( $input['pub_id'] );
					if ( ! preg_match( '/\Apub-\d{16}\z/', $newinput['pub_id'] ) ) {
						$newinput['pub_id'] = '';
						add_settings_error( 'adz_adnet_options', 'smas_auth', 'Invalid Publisher ID', 'error' );
					}

					$newinput['data_slot'] = trim( $input['data_slot'] );
					if ( ! preg_match( '/\d/', $newinput['data_slot'] ) ) {
						$newinput['data_slot'] = '';
						add_settings_error( 'adz_adnet_options', 'smas_auth', 'Invalid Ad Slot - Value Set to Blank', 'error' );
					}
					$newinput['publisher_share'] = $input['publisher_share'];
					if ( ( ! is_numeric( $newinput['publisher_share'] ) ) ||
					($newinput['publisher_share'] > 100 || $newinput['publisher_share'] < 0 ) ) {
						$newinput['publisher_share'] = 50;
						add_settings_error( 'adz_adnet_options', 'smas_auth', 'Invalid Author Share: Reset to 50', 'error' );
					}
				break;
				case 'network-settings':
					$newinput['settings_placeholder'] = $input['settings_placeholder'];
				break;
				case 'adcode-templates':
					$newinput['adsense-template'] = $input['adsense-template'];
					$newinput['adnet2_template'] = $input['adnet2_template'];
					$newinput['adnet3_template'] = $input['adnet3_template'];
				break;
			}
			$options = get_option( 'adz_adnet_options' );
			foreach ( $newinput as $key => $value ) {
				$options[ $key ] = $value;
			}

			return $options;
		}
		protected static function default_adsense_code() {

		}
	}
}

DWMAdNetworkAdminUI::wp_init();
