<?php
/**
 * Existing publisher registration and advertisement synchronization.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Synchronize only when the administrator explicitly enabled the network.
 */
class Adz_NetworkAuthorization {

	/**
	 * Whether this site is connected to a network.
	 *
	 * Every method below returns without contacting anything when it is not,
	 * so an unconnected site makes no outbound request at all.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return function_exists( 'adz_network_enabled' ) && adz_network_enabled();
	}


	/**
	 * Check an existing service registration.
	 *
	 * @return array|WP_Error|null Registration, failure, or no registration.
	 */
	public static function check_registration() {
		if ( ! self::is_enabled() ) {
			return null;
		}

		$adz_ad_network_base_url = untrailingslashit( adz_network_base_url() );
		require_once __DIR__ . '/rest-client/class-wprestclient.php';
		$ad_network_url        = $adz_ad_network_base_url . '/wp-json/adz_server/v1/';
		$client                = new WPRestClient( $ad_network_url, 'None' );
		$adz_publisher_options = get_option( 'adz_publisher_options', array() );
		$response              = null;
		if ( isset( $adz_publisher_options['adz_registered'] ) ) {
			$registration = self::registration();
			if ( is_wp_error( $registration ) ) {
				return $registration;
			}
			$id       = rawurlencode( (string) $registration['ID'] );
			$response = $client->rest_get( 'register/' . $id, array() );

			$response = self::response_body( $response, array( 200 ) );
		}
		return $response;
	}//end check_registration()

	/**
	 * Send changed publisher information after explicit consent.
	 *
	 * @param array $adz_ad_options Saved plugin settings.
	 * @return true|WP_Error|null Success, explicit failure, or no change.
	 */
	public static function update_registration( $adz_ad_options ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$adz_ad_network_base_url = untrailingslashit( adz_network_base_url() );

		$signature             = false;
		$send_registration     = false;
		$adz_publisher_options = get_option( 'adz_publisher_options', array() );
		$adz_publisher_options = is_array( $adz_publisher_options ) ? $adz_publisher_options : array();
		if ( ! empty( $adz_ad_options['accept_tos_checkbox'] ) ) {
			$paypal_email      = isset( $adz_ad_options['paypal_email'] ) ? $adz_ad_options['paypal_email'] : '';
			$adzdotworld_email = isset( $adz_ad_options['adzdotworld_email'] ) ? $adz_ad_options['adzdotworld_email'] : '';

			$referral_code           = isset( $adz_ad_options['referral_code'] ) ? $adz_ad_options['referral_code'] : '';
			$preferences             = isset( $adz_ad_options['type_of_adz'] ) ? $adz_ad_options['type_of_adz'] : array();
			$url                     = get_bloginfo( 'url' );
			$name                    = get_bloginfo( 'name' );
			$email                   = get_bloginfo( 'admin_email' );
			$adz_ad_network_base_url = untrailingslashit( adz_network_base_url() );
			$signature               = hash( 'sha256', wp_json_encode( array( $paypal_email, $adzdotworld_email, $referral_code, $preferences, $url, $name, $email, $adz_ad_network_base_url ) ) );
		}

		if ( isset( $adz_publisher_options['adz_registered'] ) && $adz_publisher_options['adz_registered'] ) {
			if ( isset( $adz_publisher_options['signed'] ) ) {
				$signed = $adz_publisher_options['signed'];
				if ( $signed != $signature ) {
					$send_registration = true;
				}
			} else {
				$send_registration = true;
			}
		} else {
			$send_registration = true;
		}

		if ( $send_registration && $signature ) {

			require_once __DIR__ . '/rest-client/class-wprestclient.php';

			$ad_network_url = $adz_ad_network_base_url . '/wp-json/adz_server/v1/';

			$client                                    = new WPRestClient( $ad_network_url, 'None' );
			$post_data['site_url']                     = get_bloginfo( 'url' );
			$post_data['site_title']                   = get_bloginfo( 'name' );
			$post_data['admin_email']                  = get_bloginfo( 'admin_email' );
			$post_data['publisher_id']                 = isset( $adz_publisher_options['adz_registered']['ID'] ) ? $adz_publisher_options['adz_registered']['ID'] : 0;
			$post_data['adzdotworld_email']            = $adzdotworld_email;
			$post_data['publisher_user_id']            = isset( $adz_publisher_options['adz_registered']['publisher_user_id'] ) ? $adz_publisher_options['adz_registered']['publisher_user_id'] : 0;
			$post_data['ad_networks']                  = array();
			$post_data['ad_networks']['paypal_email']  = $paypal_email;
			$post_data['ad_networks']['referral_code'] = $referral_code;
			$post_data['ad_networks']['preferences']   = $preferences;

			$response = $client->rest_post( 'register', $post_data );

			$body = self::response_body( $response, array( 200, 201 ) );
			if ( is_wp_error( $body ) ) {
				return $body;
			}
			if ( empty( $body['ID'] ) || empty( $body['post_token'] ) || empty( $body['network_token'] )
				|| ! is_scalar( $body['ID'] ) || ! is_string( $body['post_token'] ) || ! is_string( $body['network_token'] ) ) {
				return new WP_Error( 'adzworld_registration_response', __( 'The service response is missing publisher credentials. Existing registration was preserved.', 'adz-world' ) );
			}
			$adz_publisher_options['signed']         = $signature;
			$adz_publisher_options['adz_registered'] = $body;
			if ( ! update_option( 'adz_publisher_options', $adz_publisher_options ) ) {
				return new WP_Error( 'adzworld_registration_storage', __( 'The service registration could not be stored.', 'adz-world' ) );
			}
			return true;
		}
	}//end update_registration()

	/**
	 * Append properly encoded publisher credentials to the legacy endpoint.
	 *
	 * @param string $url Endpoint URL.
	 * @return string|WP_Error Authenticated URL or missing credentials.
	 */
	protected static function authenticate_publisher( $url ) {
		$registration = self::registration();
		if ( is_wp_error( $registration ) ) {
			return $registration;
		}
		return add_query_arg(
			urlencode_deep(
				array(
					'token'         => $registration['post_token'],
					'network_token' => $registration['network_token'],
				)
			),
			untrailingslashit( $url ) . '/' . rawurlencode( (string) $registration['ID'] )
		);
	}

	/**
	 * Delete remotely before removing the retry identifier locally.
	 *
	 * @param int $ad_post_id Local advertisement identifier.
	 * @return true|WP_Error|null Success, failure, or no synchronized ad.
	 */
	public static function delete_ad( $ad_post_id ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$adz_ad_network_base_url = untrailingslashit( adz_network_base_url() );
		$ad_network_url          = $adz_ad_network_base_url . '/wp-json/adz_server/v1';
		$network_ad_id           = get_post_meta( $ad_post_id, 'network_ad_id', true );
		if ( $network_ad_id ) {
				require_once __DIR__ . '/rest-client/class-wprestclient.php';
				$registration = self::registration();
			if ( is_wp_error( $registration ) ) {
				return $registration;
			}
			$adz_publisher_options = array( 'adz_registered' => $registration );
			if ( isset( $adz_publisher_options['adz_registered'] ) ) {
				$authentication_parameters['ad_id']    = $network_ad_id;
				$authentication_parameters['token']    = $adz_publisher_options['adz_registered']['post_token'];
				$authentication_parameters['nettoken'] = $adz_publisher_options['adz_registered']['network_token'];
				$client                                = new WPRestClient( $ad_network_url, 'Query', $authentication_parameters );
				$id                                    = rawurlencode( (string) $registration['ID'] );
				$res                                   = $client->rest_delete( "ads/{$id}" );
				if ( is_wp_error( $res ) ) {
					return $res;
				}
				if ( ! in_array( wp_remote_retrieve_response_code( $res ), array( 200, 202, 204 ), true ) ) {
					return new WP_Error( 'adzworld_delete_response', __( 'The service did not confirm deletion. The network advertisement identifier was retained.', 'adz-world' ) );
				}
				if ( ! delete_post_meta( $ad_post_id, 'network_ad_id' ) ) {
					return new WP_Error( 'adzworld_delete_storage', __( 'The service confirmed deletion, but its local advertisement identifier could not be removed.', 'adz-world' ) );
				}
				return true;
			}
		}
	}//end delete_ad()

	/**
	 * Synchronize content and independently authorized affiliate metadata.
	 *
	 * @param int     $post_id Advertisement identifier.
	 * @param WP_Post $ad_post Saved advertisement.
	 * @return true|WP_Error|null Success, failure, or network disabled.
	 */
	public static function update_ad( $post_id, $ad_post ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( 'publish' === $ad_post->post_status ) {
			$adz_ad_network_base_url = untrailingslashit( adz_network_base_url() );
			require_once __DIR__ . '/rest-client/class-wprestclient.php';
			$registration = self::registration();
			if ( is_wp_error( $registration ) ) {
				return $registration;
			}
			$adz_publisher_options = array( 'adz_registered' => $registration );
			if ( isset( $adz_publisher_options['adz_registered'] ) ) {
				$authentication_parameters['token']    = $adz_publisher_options['adz_registered']['post_token'];
				$authentication_parameters['nettoken'] = $adz_publisher_options['adz_registered']['network_token'];
				$ad_network_url                        = $adz_ad_network_base_url . '/wp-json/adz_server/v1';
				$client                                = new WPRestClient( $ad_network_url, 'Query', $authentication_parameters );
			} else {
				return;
			}
			$ad               = array();
			$ad['post_title'] = $ad_post->post_title;
			$ad['content']    = base64_encode( $ad_post->post_content );
			$terms            = get_the_terms( $ad_post, 'ad_taxonomy' );
			$preferences      = array();
			if ( is_wp_error( $terms ) ) {
				return $terms;
			}
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$preferences[] = $term->slug;
				}
			}

			$ad['ad_type_slugs'] = $preferences;

			$ad['custom_field']['affiliation_network_name'] = get_post_meta( $post_id, 'affiliation_network_name', true );
			$ad['custom_field']['affiliation_network_url']  = get_post_meta( $post_id, 'affiliation_network_url', true );
			$ad['custom_field']['affiliation_id']           = get_post_meta( $post_id, 'affiliation_id', true );
			$network_ad_id                                  = get_post_meta( $post_id, 'network_ad_id', true );
			if ( $network_ad_id ) {

				$ad['ID'] = $network_ad_id;
			}
			$id = rawurlencode( (string) $registration['ID'] );
			if ( $id ) {
					$response = $client->rest_post( "ads/{$id}", $ad );
			} else {
				return new WP_Error( 'adzworld_publisher_missing', __( 'A publisher identifier is required before synchronizing advertisements.', 'adz-world' ) );
			}

			$body = self::response_body( $response, array( 200, 201 ) );
			if ( is_wp_error( $body ) ) {
				update_post_meta( $post_id, 'network_ad_error', true );
				return $body;
			}
			if ( empty( $body['ID'] ) || ! is_scalar( $body['ID'] ) ) {
				update_post_meta( $post_id, 'network_ad_error', true );
				return new WP_Error( 'adzworld_ad_response', __( 'The service response is missing its advertisement identifier.', 'adz-world' ) );
			}
			$network_id = sanitize_text_field( (string) $body['ID'] );
			if ( (string) get_post_meta( $post_id, 'network_ad_id', true ) !== $network_id && ! update_post_meta( $post_id, 'network_ad_id', $network_id ) ) {
				return new WP_Error( 'adzworld_ad_identifier', __( 'The synchronized advertisement identifier could not be stored.', 'adz-world' ) );
			}
			update_post_meta( $post_id, 'network_ad_error', false );
			return true;
		} else {
			return self::delete_ad( $post_id );
		}
	}//end update_ad()

	/**
	 * Fetch service advertising categories.
	 *
	 * @return array|WP_Error Categories or explicit failure.
	 */
	public static function get_ad_types() {
		if ( ! self::is_enabled() ) {
			return array();
		}

		$adz_ad_network_base_url = untrailingslashit( adz_network_base_url() );
		require_once __DIR__ . '/rest-client/class-wprestclient.php';

		$ad_network_url = $adz_ad_network_base_url . '/wp-json/adz_server/v1/';
		$client         = new WPRestClient( $ad_network_url, 'None' );
		$response       = $client->rest_get( 'categories', array() );

		$body = self::response_body( $response, array( 200 ) );
		if ( is_wp_error( $body ) ) {
			return $body;
		}
		foreach ( $body as $category ) {
			if ( ! is_array( $category ) || ! isset( $category['id'], $category['name'] )
				|| ! is_scalar( $category['id'] ) || ! is_string( $category['name'] ) ) {
				return new WP_Error( 'adzworld_categories_response', __( 'The service returned invalid advertisement categories.', 'adz-world' ) );
			}
		}
		return array_map(
			static function ( $category ) {
				return (object) $category;
			},
			$body
		);
	}//end get_ad_types()

	/**
	 * Send the existing premium source-selection preference.
	 *
	 * @param array $premium_setting Premium settings.
	 * @return object|array|WP_Error|null Response, failure, or network disabled.
	 */
	public static function save_premium_settings( $premium_setting ) {
		if ( ! self::is_enabled() ) {
			return null;
		}

		$adz_ad_network_base_url = untrailingslashit( adz_network_base_url() );
		require_once __DIR__ . '/rest-client/class-wprestclient.php';

		if ( ! isset( $premium_setting['adz_serving_from'] ) || ! is_string( $premium_setting['adz_serving_from'] ) ) {
			return new WP_Error( 'adzworld_premium_settings', __( 'An advertisement source preference is required.', 'adz-world' ) );
		}
		$data['adz_serving_from'] = $premium_setting['adz_serving_from'];
		$registration             = self::registration();
		if ( is_wp_error( $registration ) ) {
			return $registration;
		}
		$adz_publisher_options = array( 'adz_registered' => $registration );
		if ( isset( $adz_publisher_options['adz_registered'] ) ) {
			$authentication_parameters['token']    = $adz_publisher_options['adz_registered']['post_token'];
			$authentication_parameters['nettoken'] = $adz_publisher_options['adz_registered']['network_token'];
			$ad_network_url                        = $adz_ad_network_base_url . '/wp-json/adz_server/v1';
			$client                                = new WPRestClient( $ad_network_url, 'Query', $authentication_parameters );
			$id                                    = rawurlencode( (string) $registration['ID'] );
		} else {
			return;
		}
		$response = $client->rest_post( "categories/{$id}", $data );

		$body = self::response_body( $response, array( 200, 201 ) );
		return is_wp_error( $body ) ? $body : json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Keep the three original public method spellings callable.
	 *
	 * @param string $name Legacy method name.
	 * @param array  $arguments Original method arguments.
	 * @return mixed Result of the corresponding standards-named method.
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

	/**
	 * Validate credentials before indexing them or sending a request.
	 *
	 * @return array|WP_Error Existing credentials or an actionable error.
	 */
	protected static function registration() {
		$options      = get_option( 'adz_publisher_options', array() );
		$registration = isset( $options['adz_registered'] ) ? $options['adz_registered'] : array();
		if ( ! is_array( $registration ) || empty( $registration['ID'] )
			|| ! is_scalar( $registration['ID'] ) || empty( $registration['post_token'] ) || ! is_string( $registration['post_token'] )
			|| empty( $registration['network_token'] ) || ! is_string( $registration['network_token'] ) ) {
			return new WP_Error( 'adzworld_registration_missing', __( 'A complete publisher registration is required before contacting the advertising service.', 'adz-world' ) );
		}
		return $registration;
	}

	/**
	 * Validate transport, status and JSON without destroying local state.
	 *
	 * @param array|WP_Error $response WordPress HTTP response.
	 * @param array          $codes Accepted HTTP status codes.
	 * @return array|WP_Error Decoded response or explicit service failure.
	 */
	protected static function response_body( $response, $codes ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( ! in_array( wp_remote_retrieve_response_code( $response ), $codes, true ) ) {
			return new WP_Error( 'adzworld_network_status', __( 'The advertising service returned an unsuccessful status. Local data was preserved.', 'adz-world' ) );
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'adzworld_network_json', __( 'The advertising service returned invalid JSON. Local data was preserved.', 'adz-world' ) );
		}
		return $body;
	}
}//end class
