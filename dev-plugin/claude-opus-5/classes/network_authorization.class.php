<?php
/* Class for Authrize the publisher from Adz.world*/
class adz_NetworkAuthorization {

	/*Function to check user Registration.*/
	public static function checkRegistration() {
		global $adz_ad_network_base_url;
		require_once( __DIR__ . '/rest-client/WP-Rest-Client.php');
		$ad_network_url = $adz_ad_network_base_url."/wp-json/adz_server/v1/";
		$client = new WPRestClient($ad_network_url,'None'); // No Authentication
		$adz_publisher_options = get_option('adz_publisher_options');
		$response = null;
		if ( isset($adz_publisher_options['adz_registered']) ) {
			$id = $adz_publisher_options['adz_registered']['ID'];
			$response = $client->rest_get('register/'.$id, array());
			
			if ( $response['response']['code'] == 200 ) {
					$response = json_decode($response['body'], true);
			} else {
				$response = null;
			}

		}
		return $response;
	}//End of function.

	/*Function to register and update user.*/

	public static function updateRegistration( $adz_ad_options ) {
		global $adz_ad_network_base_url;

		$signature = false;
		$send_registration = false;
		$adz_publisher_options = get_option( 'adz_publisher_options' );
		if ( $adz_ad_options['accept_tos_checkbox'] ) {
			$paypal_email = $adz_ad_options['paypal_email'];
			$adzdotworld_email = $adz_ad_options['adzdotworld_email'];
			
			$referral_code = $adz_ad_options['referral_code'];
			$preferences = $adz_ad_options['type_of_adz'];			
			$url = get_bloginfo('url');
			$name = get_bloginfo('name');
			$email =  get_bloginfo('admin_email');
			global $adz_ad_network_base_url;
			$signature = md5($paypal_email.$adzdotworld_email.$url.$name.$email.$adz_ad_network_base_url);
		}
		
		if ( isset($adz_publisher_options['adz_registered']) && $adz_publisher_options['adz_registered'] ) {
			if ( isset($adz_publisher_options['signed']) ) {
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
			
			require_once( __DIR__ . '/rest-client/WP-Rest-Client.php');
			
			$ad_network_url = $adz_ad_network_base_url."/wp-json/adz_server/v1/";
			
			$client = new WPRestClient( $ad_network_url,'None' ); // No Authentication
			$post_data['site_url']    = get_bloginfo( 'url' );
			$post_data['site_title']  = get_bloginfo( 'name' );
			$post_data['admin_email'] = get_bloginfo( 'admin_email' );
			$post_data['publisher_id'] = $adz_publisher_options['adz_registered']['ID'];
			$post_data['adzdotworld_email'] = $adzdotworld_email;	
			$post_data['publisher_user_id'] = $adz_publisher_options['adz_registered']['publisher_user_id'];
			$post_data['ad_networks'] = array();
			$post_data['ad_networks']['paypal_email'] = $paypal_email;
			$post_data['ad_networks']['referral_code'] = $referral_code;
			$post_data['ad_networks']['preferences'] = $preferences;
		

			$response = $client->rest_post( 'register', $post_data );			
		
			if ( $response['response']['code'] == 200 || $response['response']['code'] == 201 ) {
				$body = json_decode($response['body'], true);
				$adz_publisher_options['signed'] = $signature;
				$adz_publisher_options['adz_registered'] = $body;
				
				
				update_option( 'adz_publisher_options', $adz_publisher_options );
			}
		}
	}// Endo of the function.

    protected static function authenticate_publisher( $url ) {
		$adz_publisher_options = get_option('adz_publisher_options');
		if (isset($adz_publisher_options['adz_registered'])) {
			$id = $adz_publisher_options['adz_registered']['ID'];
			$post_token = $adz_publisher_options['adz_registered']['post_token'];
			$network_token = $adz_publisher_options['adz_registered']['network_token'];
			$url = "{$url}/{$id}?token={$post_token}&network_token={$network_token}";
		}

		return $url;

	}

  	public static function delete_ad( $ad_post_id ) {
		global $adz_ad_network_base_url;
		$ad_network_url = $adz_ad_network_base_url."/wp-json/adz_server/v1";
		$network_ad_id = get_post_meta($ad_post_id,'network_ad_id', true);
		if ($network_ad_id) {
			  require_once( __DIR__ . '/rest-client/WP-Rest-Client.php');
			  $adz_publisher_options = get_option('adz_publisher_options');
				if ( isset($adz_publisher_options['adz_registered']) ) {
				$authentication_parameters['ad_id'] = $network_ad_id;
				$authentication_parameters['token'] = $adz_publisher_options['adz_registered']['post_token'];
				$authentication_parameters['nettoken'] = $adz_publisher_options['adz_registered']['network_token'];
				$client = new WPRestClient($ad_network_url,'Query',$authentication_parameters );
				$id = $adz_publisher_options['adz_registered']['ID'];
				delete_post_meta($ad_post_id,'network_ad_id');
				$res = $client->rest_delete("ads/{$id}");
			}
		}

	}// End of function.

	public static function update_ad($post_id, $ad_post) {
		if ($ad_post->post_status == 'publish') {
			global $adz_ad_network_base_url;
			require_once( __DIR__ . '/rest-client/WP-Rest-Client.php');
			$adz_publisher_options = get_option('adz_publisher_options');
			if (isset($adz_publisher_options['adz_registered'])) {
				$authentication_parameters['token'] = $adz_publisher_options['adz_registered']['post_token'];
				$authentication_parameters['nettoken'] = $adz_publisher_options['adz_registered']['network_token'];
				$ad_network_url = $adz_ad_network_base_url."/wp-json/adz_server/v1";
				$client = new WPRestClient($ad_network_url,'Query',$authentication_parameters);
			} else {
				return;
			}
			$ad = array();
			$ad['post_title'] = $ad_post->post_title;
			$ad['content'] = base64_encode($ad_post->post_content);
			$terms = get_the_terms($ad_post,'ad_taxonomy');
			$preferences = array();
			if (!empty($terms)) {
				foreach($terms as $term) {
					$preferences[] = $term->slug;
				}
			}
			
			$ad['ad_type_slugs'] = $preferences;	
			
			update_post_meta( $post_id, 'affiliation_network_name', sanitize_text_field( $_POST['affiliation_network_name'] ) );
			update_post_meta( $post_id, 'affiliation_network_url', sanitize_text_field( $_POST['affiliation_network_url'] ) );
			update_post_meta( $post_id, 'affiliation_id', sanitize_text_field( $_POST['affiliation_id'] ) );	
			$ad['custom_field']['affiliation_network_name'] = get_post_meta( $post_id, 'affiliation_network_name', true );
   			$ad['custom_field']['affiliation_network_url'] = get_post_meta( $post_id, 'affiliation_network_url', true );
   			$ad['custom_field']['affiliation_id'] = get_post_meta( $post_id, 'affiliation_id', true );
   			$network_ad_id = get_post_meta($post_id,'network_ad_id', true);
			if ($network_ad_id) {

				$ad['ID'] = $network_ad_id;
			}
			$id = $adz_publisher_options['adz_registered']['ID'];
			if ($id) {
					$response = $client->rest_post("ads/{$id}",$ad);
			}


			if ($response['response']['code'] == 200 || $response['response']['code'] == 201) {
				$body = json_decode($response['body'], true);
				if ($body['ID']) {
					update_post_meta($post_id,'network_ad_error',false);
					update_post_meta($post_id,'network_ad_id',$body['ID']);
				} else {
					update_post_meta($post_id,'network_ad_error',true);
				}
			} else {
				update_post_meta($post_id,'network_ad_error',true);
			}

		}
		else {
			self::delete_ad($post_id);
		}
	}//End of function.

	public static function get_ad_types(){

		global $adz_ad_network_base_url;
		require_once( __DIR__ . '/rest-client/WP-Rest-Client.php');
		
		$ad_network_url = $adz_ad_network_base_url."/wp-json/adz_server/v1/";
		$client = new WPRestClient($ad_network_url,'None'); // No Authentication
		$response = $client->rest_get("categories",array());
	
		return json_decode(wp_remote_retrieve_body($response));
	
	}//End of function

	public static function savePremiumSettings( $premium_setting ){

		global $adz_ad_network_base_url;
		require_once( __DIR__ . '/rest-client/WP-Rest-Client.php');
		
		$data['adz_serving_from'] = $premium_setting['adz_serving_from'];
		$adz_publisher_options = get_option('adz_publisher_options');
		if (isset($adz_publisher_options['adz_registered'])) {
			$authentication_parameters['token'] = $adz_publisher_options['adz_registered']['post_token'];
			$authentication_parameters['nettoken'] = $adz_publisher_options['adz_registered']['network_token'];
			$ad_network_url = $adz_ad_network_base_url."/wp-json/adz_server/v1";
			$client = new WPRestClient($ad_network_url,'Query',$authentication_parameters);
			$id = $adz_publisher_options['adz_registered']['ID'];
		} else {
			return;
		}
		$response = $client->rest_post("categories/{$id}",$data);
		
		return json_decode( wp_remote_retrieve_body($response) );
	
	}//End of function.

}// end of class.