<?php
/*
Plugin Name: Ad Network Control
Description: Ad Netword Controller
Version: 1.0.1
Author: dainis w michel
Author URI: http://www.dainiswmichel.com
*/

require_once('includes/adz_cpt_ads.php');
require_once('includes/ads_rest_controller.class.php');
require_once('includes/adz_cat_rest_controller.class.php');
require_once('includes/adz_cpt_registrations.php');
require_once('includes/adz_reg_rest_controller.class.php');
require_once('includes/adz_ads_server_settings.php');

add_action('admin_menu', 'add_adz_network_setting_menu');
function add_adz_network_setting_menu(){
    add_submenu_page( 'edit.php?post_type=adz_ad', 'Adz Network Settings', 'Adz Network Settings','manage_options', 'ad-network-setting','adz_network_settings');
}

function adz_network_settings(){
	screen_icon();
	if(isset($_POST["submit_settings"])) {
		update_option('manager_can_edit',$_POST["manager_can_edit"],true);
	}
	$manager_can_edit = get_option('manager_can_edit');
	?>
	<form method="post" action="">
	
	<h3></h3>
	<table width="100%">

	<tr valign="top">
	<td scope="row"><label>Enable the the Manage Account Edit ?</label></td>
	</tr>
	<tr>
	<td>
	<input type="radio" id="manager_can_edit_yes" name="manager_can_edit" required="required" value="Yes" <?php if($manager_can_edit == 'Yes') {echo "checked='checked'";}?>  /> <label for="manager_can_edit_yes">Yes </label>
	<input type="radio" id="manager_can_edit_no" name="manager_can_edit" required="required" value="No" <?php if($manager_can_edit == 'No') {echo "checked='checked'";}?> /> <label for="manager_can_edit_no">No </label>  </td>
	</tr>
	</table>
	<input name="submit_settings" id="submit" class="button button-primary" value="Save" type="submit" >
	</form>
	<?php 
}
