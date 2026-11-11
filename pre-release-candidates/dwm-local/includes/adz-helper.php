<?php
/**
 * Helper functions for serving adz.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Render the scheduled page-advertising request controls.
 *
 * @param int    $ad_visibility Countdown seconds.
 * @param int    $ad_visibility_interval Minimum seconds between ads.
 * @param mixed  $repeat_times Repeat count or infinite.
 * @param string $target_type Target kind.
 * @param int    $target Target identifier.
 * @param mixed  $ad_to_serve Selected ad identifier.
 * @param string $rotations_id Saved rotation identifier.
 * @param string $sequence Configured ad sequence.
 * @param string $display_type Presentation kind.
 * @param string $adz_template Template filename.
 */
function adzworld_get_advertise( $ad_visibility, $ad_visibility_interval, $repeat_times, $target_type, $target, $ad_to_serve, $rotations_id, $sequence, $display_type, $adz_template ) {

	if ( ! isset( $_SESSION['first_time'] ) ) {
		$_SESSION['first_time'] = time() + $ad_visibility_interval;
	}

	?>
	<script type="text/javascript">

		jQuery(document).ready(function(){
			var repeat_adz = 'yes';
			var interval = setInterval(function () {
				if(repeat_adz == 'yes'){
					
					browsing_advertise();	
				}				
					
			}, 60000);
			function browsing_advertise(){

							
				jQuery.ajax({
					url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
					type : 'post',
					data: {
						action :'adzworld_get_advertise_content',
						target_type : '<?php echo esc_js( $target_type ); ?>',
						target : '<?php echo esc_js( $target ); ?>',
						ad_visibility_interval : '<?php echo esc_js( $ad_visibility_interval ); ?>',
						ad_to_serve : '<?php echo esc_js( $ad_to_serve ); ?>',
						rotations_id : '<?php echo esc_js( $rotations_id ); ?>',
						sequence : '<?php echo esc_js( $sequence ); ?>',
						display_type : '<?php echo esc_js( $display_type ); ?>',
						adz_template : '<?php echo esc_js( $adz_template ); ?>',
						repeat_times : '<?php echo esc_js( $repeat_times ); ?>' ,
						adzworld_context: '<?php echo esc_js( adzworld_ad_request_token( $target_type, $target, $ad_visibility_interval, $repeat_times, $ad_to_serve, $rotations_id, $sequence, $display_type, $adz_template ) ); ?>',
						adz_nonce: '<?php echo esc_js( wp_create_nonce( 'adzdotworld' ) ); ?>'
					},
					success:function(data) {
						
						if(jQuery.trim(data) != 'continue'){
							<?php if ( is_category() ) { ?>
								jQuery('.page-header').after('<div class="adz-world-category-wapper"></div>');
								jQuery('.status-publish').remove();
								jQuery('.pagination').remove();

							<?php } ?>
							<?php if ( is_category() ) { ?>
								jQuery(document).find('.adz-world-category-wapper').html(window.adzworldDecodeAd(data));
								var i = <?php echo absint( $ad_visibility ); ?>;
								var adz_interval = setInterval(function () {
									if (i >= 0) {
										jQuery('.adz_timer').text(" "+i+" ");
										i--;
									} else {
										jQuery('.continue_reading').text('Continue >>');
										jQuery('.continue_reading').addClass('close_adz');	
										clearInterval(adz_interval);
										repeat_adz = 'no';
									}
								}, 1000);
							<?php } else { ?>
								jQuery('.adz-world-container').html(window.adzworldDecodeAd(data));
								var i = <?php echo absint( $ad_visibility ); ?>;
								var adz_interval = setInterval(function () {
									if (i >= 0) {
										jQuery('.adz_timer').text(" "+i+" ");
										i--;
									} else {
										jQuery('.continue_reading').text('Continue >>');
										jQuery('.continue_reading').addClass('close_adz');	
										clearInterval(adz_interval);
										repeat_adz = 'no';
									}
								}, 1000);
							<?php } ?>


						}else{
						}					
					},
					error: function(errorThrown){
						console.log(errorThrown);
					}
				});
			}

			jQuery(document).on('click','.close_adz',function(){
				jQuery.ajax({
					url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
					type : 'post',
					data: {
						action :'adzworld_get_advertise_content',
						target_type : '<?php echo esc_js( $target_type ); ?>',
						target : '<?php echo esc_js( $target ); ?>',
						ad_visibility_interval : '<?php echo esc_js( $ad_visibility_interval ); ?>',
						repeat_times : '<?php echo esc_js( $repeat_times ); ?>',
						close_adz : 'true',
						rotations_id : '<?php echo esc_js( $rotations_id ); ?>',
						adzworld_context: '<?php echo esc_js( adzworld_ad_request_token( $target_type, $target, $ad_visibility_interval, $repeat_times, '', $rotations_id, '', '', '', 'true' ) ); ?>',
						adz_nonce: '<?php echo esc_js( wp_create_nonce( 'adzdotworld' ) ); ?>'


					},
					success:function(data) {
						if(jQuery.trim(data) != 'continue'){
							repeat_adz = 'yes';
							location.reload();
						}					
					},
					error: function(errorThrown){
						console.log(errorThrown);
					}
				});
				
			});
		});

	</script>
	<?php
}
/**
 * Render the scheduled popup advertising request controls.
 *
 * @param int    $ad_visibility Countdown seconds.
 * @param int    $ad_visibility_interval Minimum seconds between ads.
 * @param mixed  $repeat_times Repeat count or infinite.
 * @param string $target_type Target kind.
 * @param int    $target Target identifier.
 * @param mixed  $ad_to_serve Selected ad identifier.
 * @param string $rotations_id Saved rotation identifier.
 * @param string $sequence Configured ad sequence.
 * @param string $display_type Presentation kind.
 * @param string $adz_template Template filename.
 */
function adzworld_get_advertise_popup( $ad_visibility, $ad_visibility_interval, $repeat_times, $target_type, $target, $ad_to_serve, $rotations_id, $sequence, $display_type, $adz_template ) {

	if ( ! isset( $_SESSION['first_time'] ) ) {
		$_SESSION['first_time'] = time() + $ad_visibility_interval;

	}
	?>
	<div id="show_overlay" class="adz_overlay">
		<div class="adz_popup">
							
			<div class="content"> </div>			
		</div>
	</div>

	<script type="text/javascript">
	jQuery(document).ready(function(){	

		var repeat_adz = 'yes';
		var interval = setInterval(function () {
			if(repeat_adz == 'yes'){
				browsing_advertise();
			}
				
		}, 60000);
		function browsing_advertise(){
			
			jQuery.ajax({
				url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
				type : 'post',
				data: {
					action :'adzworld_get_advertise_content',
					target_type : '<?php echo esc_js( $target_type ); ?>',
					target : '<?php echo esc_js( $target ); ?>',
					ad_visibility_interval : '<?php echo esc_js( $ad_visibility_interval ); ?>',
					ad_to_serve : '<?php echo esc_js( $ad_to_serve ); ?>',
					rotations_id : '<?php echo esc_js( $rotations_id ); ?>',
					sequence : '<?php echo esc_js( $sequence ); ?>',
					display_type : '<?php echo esc_js( $display_type ); ?>',
					adz_template : '<?php echo esc_js( $adz_template ); ?>',
					repeat_times : '<?php echo esc_js( $repeat_times ); ?>',
					adzworld_context: '<?php echo esc_js( adzworld_ad_request_token( $target_type, $target, $ad_visibility_interval, $repeat_times, $ad_to_serve, $rotations_id, $sequence, $display_type, $adz_template ) ); ?>',
					show_popup : 'true',
					adz_nonce: '<?php echo esc_js( wp_create_nonce( 'adzdotworld' ) ); ?>'

				},
				success:function(data) {
				
					if(jQuery.trim(data) != 'continue'){
						jQuery('#show_overlay').addClass('adzworld-is-visible');
						jQuery('.adz_popup .content').html(window.adzworldDecodeAd(data));
						

						var i = <?php echo absint( $ad_visibility ); ?>;
						var adz_interval = setInterval(function () {
							if (i >= 0) {
								jQuery('.adz_timer').text(" "+i+" ");
								i--;
							} else {
								jQuery('.continue_reading').text('Continue >>');
								jQuery('.continue_reading').addClass('close_adz');	
								clearInterval(adz_interval);
								repeat_adz = 'no';
							}
						}, 1000);
					}					
				},
				error: function(errorThrown){
					console.log(errorThrown);
				}
			});
			
		}	
		
		jQuery(document).on('click','.close_adz',function(){
			jQuery.ajax({
				url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
				type : 'post',
				data: {
					action :'adzworld_get_advertise_content',
					target_type : '<?php echo esc_js( $target_type ); ?>',
					target : '<?php echo esc_js( $target ); ?>',
					ad_visibility_interval : '<?php echo esc_js( $ad_visibility_interval ); ?>',
					repeat_times : '<?php echo esc_js( $repeat_times ); ?>',
					close_adz : 'true',
					rotations_id : '<?php echo esc_js( $rotations_id ); ?>',
					adzworld_context: '<?php echo esc_js( adzworld_ad_request_token( $target_type, $target, $ad_visibility_interval, $repeat_times, '', $rotations_id, '', '', '', 'true' ) ); ?>',
					show_popup : 'true',
					adz_nonce: '<?php echo esc_js( wp_create_nonce( 'adzdotworld' ) ); ?>'
				},
				success:function(data) {
					jQuery('#show_overlay').removeClass('adzworld-is-visible');
					repeat_adz = 'yes';            
				},
				error: function(errorThrown){
					console.log(errorThrown);
				}
			});
			 
			 
		});
	});
	
	</script>
	<?php
}

/**
 * Return the published, publicly available publisher-ad sequence.
 *
 * @return string Comma-separated ad identifiers.
 */
function adzworld_get_publisher_ads() {

	$args              = array(
		'posts_per_page' => -1,
		'post_type'      => 'adz_ad',
		'post_status'    => 'publish',
		'has_password'   => false,
	);
	$posts_array       = get_posts( $args );
	$publisher_adz_ids = '';
	if ( ! empty( $posts_array ) ) {
		foreach ( $posts_array as $posts ) {

			$publisher_adz_ids .= ( adzworld_network_enabled() ? get_post_meta( $posts->ID, 'network_ad_id', true ) : $posts->ID ) . ',';
		}
	}

	return $publisher_adz_ids;
}//end adzworld_get_publisher_ads()

/**
 * Resolve local posts to the identifiers used by the selected ad service.
 *
 * @param array $adz_ids Local post identifiers.
 * @return string Comma-separated ad identifiers.
 */
function adzworld_get_network_id( $adz_ids ) {
	$network_ids = '';
	if ( ! empty( $adz_ids ) ) {
		foreach ( $adz_ids as $adz ) {
			$network_ids .= ( adzworld_network_enabled() ? get_post_meta( $adz, 'network_ad_id', true ) : absint( $adz ) ) . ',';
		}
	}
	return rtrim( $network_ids, ',' );
}//end adzworld_get_network_id()

/**
 * Return a published local ad through the existing renderer's response shape.
 *
 * @param int $post_id Local ad identifier.
 * @return array|WP_Error A local content response, never an outbound request.
 */
function adzworld_local_ad_response( $post_id ) {
	$post = get_post( absint( $post_id ) );
	if ( ! $post || 'adz_ad' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password ) {
		return new WP_Error( 'adzworld_ad_unavailable', __( 'The selected local ad is not publicly available.', 'adz-world' ) );
	}
	return array(
		'response' => array( 'code' => 200 ),
		'headers'  => array(),
		'body'     => wp_json_encode( array( array( 'content' => wp_kses_post( $post->post_content ) ) ) ),
	);
}

/**
 * Return the HTML elements permitted in an advertising response.
 *
 * @return array HTML allowlist including the legacy iframe presentation.
 */
function adzworld_ad_allowed_html() {
	$allowed           = wp_kses_allowed_html( 'post' );
	$allowed['iframe'] = array(
		'src'    => true,
		'height' => true,
		'width'  => true,
		'title'  => true,
	);
	return $allowed;
}

/**
 * Render only the plugin's iframe shortcode within sanitized ad content.
 *
 * An advertising service must not be able to execute arbitrary site shortcodes.
 *
 * @param string $content Advertising markup.
 * @return string Safe ad markup.
 */
function adzworld_ad_content( $content ) {
	$content = wp_kses( $content, adzworld_ad_allowed_html() );
	$content = preg_replace_callback( '/' . get_shortcode_regex( array( 'adzworld_iframe' ) ) . '/', 'do_shortcode_tag', $content );
	return wp_kses( $content, adzworld_ad_allowed_html() );
}

/**
 * Look up the current account only when the external service is enabled.
 *
 * @return int|false Account identifier, or false when not logged in.
 */
function adzworld_logged_in() {

	// With no network connected there is no remote visitor to look up, and
	// 1.0.8 sent the visitor's IP address here on every call.
	if ( ! adzworld_network_enabled() ) {
		return is_user_logged_in() ? get_current_user_id() : false;
	}

	global $adzworld_network_base_url;
	$visitor_ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$ad_network_url = add_query_arg( 'visitor_ip', $visitor_ip, $adzworld_network_base_url . 'wp-json/adz_server/v1/ads/is_visitor_logged' );
	$args           = array();
	$response       = wp_remote_get( $ad_network_url, $args );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body          = wp_remote_retrieve_body( $response );
	$json_repsonse = json_decode( $body );

	// A failed or empty response decodes to null; reading ->status on that is
	// a warning on PHP 8, which the activation check treats as a failure.
	if ( is_object( $json_repsonse ) && isset( $json_repsonse->status ) && 'logged_in' === $json_repsonse->status ) {
		return $json_repsonse->user_id;
	} else {
		return false;
	}
}//end adzworld_logged_in()

/**
 * Select the next unserved ad from a configured rotation.
 *
 * @param array $rotation Saved rotation configuration.
 * @return string|false Next ad identifier, or false when complete.
 */
function adzworld_check_rotation_completed( $rotation ) {
	$rotation_adz_pool = get_option( $rotation['rotation_id'] );
	if ( '' === rtrim( $rotation['sequence'], ',' ) ) {
		return 'network';
	}

	if ( ! $rotation_adz_pool ) {
		$rotation_sequence_array = explode( ',', $rotation['sequence'] );
		$return                  = array_shift( $rotation_sequence_array );

	} elseif ( ! empty( $rotation_adz_pool['un_served'] ) ) {

		$return = array_shift( $rotation_adz_pool['un_served'] );

	} else {
		$return = false;
	}
	return $return;
}//end adzworld_check_rotation_completed()
/**
 * Validate a rotation id arriving from a request.
 *
 * The AJAX endpoint is public (wp_ajax_nopriv), and 1.0.8 passed
 * $_POST['rotations_id'] straight into get_option() and update_option() as the
 * option NAME. Any visitor could therefore read or overwrite any option on the
 * site -- siteurl, admin_email, stored credentials. Sanitising the value does
 * not help: the problem is which option is addressed, not what it contains.
 *
 * Legitimate ids are generated by the settings UI in the form adz_rotation_<n>.
 * Anything that does not match that exactly is refused.
 *
 * @param mixed $rotation_id Candidate rotation id from the request.
 * @return string The validated id, or '' when it is not a real rotation id.
 */
function adzworld_validate_rotation_id( $rotation_id ) {
	$rotation_id = is_scalar( $rotation_id ) ? (string) $rotation_id : '';

	return preg_match( '/^adz_rotation_[0-9]+$/', $rotation_id ) ? $rotation_id : '';
}

/**
 * Resolve a rotation option only when it belongs to saved plugin settings.
 *
 * @param mixed $rotation_id Requested rotation identifier.
 * @return string Configured identifier, or an empty string when unavailable.
 */
function adzworld_configured_rotation_id( $rotation_id ) {
	$rotation_id = adzworld_validate_rotation_id( $rotation_id );
	if ( '' === $rotation_id ) {
		return '';
	}

	$options = get_option( 'adz_ad_options' );
	if ( ! is_array( $options ) || ! isset( $options['ad_settings'] ) || ! is_array( $options['ad_settings'] ) ) {
		return '';
	}

	foreach ( $options['ad_settings'] as $rotation ) {
		if ( is_array( $rotation ) && isset( $rotation['rotation_id'] ) && $rotation_id === $rotation['rotation_id'] ) {
			return $rotation_id;
		}
	}

	return '';
}

/**
 * Store a rotation's state, but only under a genuine rotation id.
 *
 * @param mixed $rotation_id Candidate rotation id from the request.
 * @param mixed $state       Rotation state to store.
 * @return bool True when written, false when the id was refused.
 */
function adzworld_update_rotation_state( $rotation_id, $state ) {
	$rotation_id = adzworld_configured_rotation_id( $rotation_id );

	if ( '' === $rotation_id ) {
		return false;
	}

	return update_option( $rotation_id, $state );
}

/**
 * Whether this site is connected to a remote adz network.
 *
 * Off unless the site owner turns it on. With it off ads come from this site's
 * own adz_ad posts. Separately consented earnings requests are independent.
 *
 * 1.0.8 called adz.world unconditionally, on page loads, sending visitor IP
 * addresses with no disclosure and no way to decline. Those requests require
 * review against external-service guidelines; the actual closure guideline
 * has not been established.
 *
 * @return bool
 */
function adzworld_network_enabled() {
	$options = get_option( 'adz_ad_options' );

	if ( empty( $options['connect_to_network'] ) ) {
		return false;
	}
	$base_url = adzworld_network_base_url();
	if ( 'adz-world.com' === wp_parse_url( $base_url, PHP_URL_HOST ) && empty( $options['adzworld_monetization_consent'] ) ) {
		return false;
	}

	return '' !== $base_url;
}

/**
 * Base URL of the adz network this site talks to.
 *
 * Filterable so a site can point at a different network.
 *
 * @return string Trailing-slashed URL, or '' when none is configured.
 */
function adzworld_network_base_url() {
	$url = apply_filters( 'adzworld_ad_network_base_url', 'https://adz-world.com/' );

	return $url ? trailingslashit( esc_url_raw( $url ) ) : '';
}

/**
 * Resolve an adz template filename to a real file inside adz-templates/.
 *
 * SECURITY. 1.0.8 built an include path by concatenating $_POST['adz_template']
 * onto the templates directory and passing it to require_once(), on an endpoint
 * registered for unauthenticated AJAX. sanitize_text_field() does not strip
 * "../", so a crafted value escaped the directory and executed any PHP file the
 * web user could read. The nonce guarding that endpoint is printed into public
 * page source, so it stopped nobody.
 *
 * The name is now matched against the files that actually exist in
 * adz-templates/. Nothing outside that directory is reachable, whatever the
 * input contains.
 *
 * @param mixed $template Requested template filename.
 * @return string Absolute path to a real template, or '' when there is no match.
 */
function adzworld_resolve_template( $template ) {
	if ( ! is_string( $template ) || basename( $template ) !== $template ) {
		return '';
	}
	$template = trim( $template );

	if ( '' === $template || '.php' !== substr( $template, -4 ) ) {
		return '';
	}

	if ( ! in_array( $template, adzworld_available_templates(), true ) ) {
		return '';
	}

	return ADZ_WORLD . 'adz-templates/' . $template;
}

/**
 * The template files shipped in adz-templates/.
 *
 * @return string[] Filenames.
 */
function adzworld_available_templates() {
	static $templates = null;

	if ( null !== $templates ) {
		return $templates;
	}

	$found     = glob( ADZ_WORLD . 'adz-templates/*.php' );
	$templates = $found ? array_map( 'basename', $found ) : array();

	return $templates;
}

/**
 * Build a privacy-preserving key for one visitor's daily view record.
 *
 * The raw address is never stored. WordPress salts make the digest specific to
 * this installation, and the date naturally separates daily records.
 *
 * @param string $visitor_ip Visitor address.
 * @param int    $target_id Target post identifier.
 * @param string $target_type Display context.
 * @return string Transient key.
 */
function adzworld_view_key( $visitor_ip, $target_id, $target_type ) {
	$identity = wp_json_encode(
		array(
			sanitize_text_field( (string) $visitor_ip ),
			absint( $target_id ),
			sanitize_key( $target_type ),
			current_time( 'Y-m-d' ),
		)
	);

	return 'adzworld_view_' . hash_hmac( 'sha256', $identity, wp_salt( 'nonce' ) );
}

/**
 * Look up today's view record for a visitor against one target.
 *
 * @param string $visitor_ip Visitor address.
 * @param int    $target_id Target post identifier.
 * @param string $target_type Display context.
 * @return object|null View record, or null when no view was recorded today.
 */
function adzworld_get_view_row( $visitor_ip, $target_id, $target_type = '' ) {
	$record = get_transient( adzworld_view_key( $visitor_ip, $target_id, $target_type ) );
	return is_array( $record ) ? (object) $record : null;
}

/**
 * Record a completed ad view until the end of the current site-local day.
 *
 * @param string $visitor_ip Visitor address.
 * @param int    $target_id Target post identifier.
 * @param string $target_type Display context.
 * @return bool Whether the record was stored.
 */
function adzworld_record_view( $visitor_ip, $target_id, $target_type ) {
	$key      = adzworld_view_key( $visitor_ip, $target_id, $target_type );
	$existing = get_transient( $key );
	$count    = is_array( $existing ) && isset( $existing['number_of_times'] ) ? absint( $existing['number_of_times'] ) + 1 : 1;
	$now      = current_datetime();
	$tomorrow = $now->setTime( 0, 0 )->modify( '+1 day' );
	$expires  = max( HOUR_IN_SECONDS, $tomorrow->getTimestamp() - $now->getTimestamp() );

	return set_transient(
		$key,
		array(
			'number_of_times' => $count,
			'updated'         => current_time( 'mysql' ),
		),
		$expires
	);
}

/**
 * Normalize native and historical double-serialized template settings.
 *
 * @param mixed $settings Stored option value.
 * @return array Template settings.
 */
function adzworld_template_settings( $settings ) {
	if ( is_array( $settings ) ) {
		return $settings;
	}
	if ( ! is_string( $settings ) || ! is_serialized( $settings ) || preg_match( '/(?:^|[;}])(?:O|C):[0-9]+:/', $settings ) ) {
		return array();
	}

	$decoded = maybe_unserialize( $settings );
	return is_array( $decoded ) ? $decoded : array();
}
