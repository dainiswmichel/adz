<?php
/**
 * Retired advertising-network registration.
 *
 * This plugin does not connect to an advertising network. The methods remain
 * so existing callers keep working, and none of them contacts a server.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * No-op stand-in for the removed network client.
 */
class Adz_NetworkAuthorization {

	/**
	 * This plugin does not connect to an advertising network.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return false;
	}

	/**
	 * No registration is fetched.
	 *
	 * @return null
	 */
	public static function check_registration() {
		return null;
	}

	/**
	 * No registration is sent.
	 *
	 * @param array $adz_ad_options Saved plugin settings. Unused.
	 * @return null
	 */
	public static function update_registration( $adz_ad_options ) {
		unset( $adz_ad_options );
		return null;
	}

	/**
	 * No advertisement is deleted remotely.
	 *
	 * @param int $ad_post_id Post id. Unused.
	 * @return null
	 */
	public static function delete_ad( $ad_post_id ) {
		unset( $ad_post_id );
		return null;
	}

	/**
	 * No advertisement is sent.
	 *
	 * @param int   $post_id Post id. Unused.
	 * @param mixed $ad_post Post. Unused.
	 * @return null
	 */
	public static function update_ad( $post_id, $ad_post ) {
		unset( $post_id, $ad_post );
		return null;
	}

	/**
	 * No remote categories are fetched.
	 *
	 * @return array
	 */
	public static function get_ad_types() {
		return array();
	}

	/**
	 * No premium settings are sent.
	 *
	 * @param array $premium_setting Settings. Unused.
	 * @return null
	 */
	public static function save_premium_settings( $premium_setting ) {
		unset( $premium_setting );
		return null;
	}

	/**
	 * Keep the three original public method spellings callable.
	 *
	 * @param string $name      Legacy method name.
	 * @param array  $arguments Original method arguments.
	 * @return mixed Result of the corresponding method.
	 * @throws BadMethodCallException For an unknown method.
	 */
	public static function __callStatic( $name, $arguments ) {
		$methods = array(
			'checkregistration'   => 'check_registration',
			'updateregistration'  => 'update_registration',
			'savepremiumsettings' => 'save_premium_settings',
		);
		$key     = strtolower( $name );
		if ( ! isset( $methods[ $key ] ) ) {
			throw new BadMethodCallException( 'Unknown advertising-service method.' );
		}
		return call_user_func_array( array( __CLASS__, $methods[ $key ] ), $arguments );
	}
}
