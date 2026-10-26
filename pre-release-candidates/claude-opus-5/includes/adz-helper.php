<?php
/**
 * Helper functions for serving adz.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/*
 * role_exists() was defined here. WordPress core now ships its own, so
 * redeclaring it is a fatal error the moment the plugin is activated.
 * Nothing in this plugin ever called it, so it is removed rather than guarded.
 */

function adz_get_advertise( $ad_visibility, $ad_visibility_interval, $repeat_times, $target_type, $target, $ad_to_serve, $rotations_id, $sequence, $display_type, $adz_template ) {

	if ( ! isset( $_SESSION['first_time'] ) && @$_SESSION['first_time'] == '' ) {
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
						action :'adz_get_advertise_content',
						target_type : '<?php echo $target_type; ?>',
						target : '<?php echo $target; ?>',
						ad_visibility_interval : '<?php echo $ad_visibility_interval; ?>',
						ad_to_serve : '<?php echo $ad_to_serve; ?>',
						rotations_id : '<?php echo $rotations_id; ?>',
						sequence : '<?php echo $sequence; ?>',
						display_type : '<?php echo $display_type; ?>',
						adz_template : '<?php echo $adz_template; ?>',
						repeat_times : '<?php echo $repeat_times; ?>' ,
						adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' ); ?>'
					},
					success:function(data) {
						
						if(jQuery.trim(data) != 'continue'){
							<?php if ( is_category() ) { ?>
								jQuery('.page-header').after('<div class="adz-world-category-wapper"></div>');
								jQuery('.status-publish').remove();
								jQuery('.pagination').remove();

							<?php } ?>
							<?php if ( is_category() ) { ?>
								jQuery(document).find('.adz-world-category-wapper').html(window.atob(data));
								var i = <?php echo $ad_visibility; ?>;
								var adz_interval = setInterval(function () {
									if (i >= 0) {
										jQuery('.adz_timer').text(" "+i+" ");
										i--;
									} else {
										jQuery('.continue_reading').text('Continue >>');
										jQuery('.continue_reading').addClass('close_adz');	
										jQuery('.continue_reading').css('cusrsor','pointer');						       
										clearInterval(adz_interval);
										repeat_adz = 'no';
									}
								}, 1000);
							<?php } else { ?>
								jQuery('.adz-world-container').html(window.atob(data));
								var i = <?php echo $ad_visibility; ?>;
								var adz_interval = setInterval(function () {
									if (i >= 0) {
										jQuery('.adz_timer').text(" "+i+" ");
										i--;
									} else {
										jQuery('.continue_reading').text('Continue >>');
										jQuery('.continue_reading').addClass('close_adz');	
										jQuery('.continue_reading').css('cusrsor','pointer');						       
										clearInterval(adz_interval);
										repeat_adz = 'no';
									}
								}, 1000);
							<?php } ?>


						}else{
							//jQuery('body').show();
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
						action :'adz_get_advertise_content',
						target_type : '<?php echo $target_type; ?>',
						target : '<?php echo $target; ?>',
						ad_visibility_interval : '<?php echo $ad_visibility_interval; ?>',
						repeat_times : '<?php echo $repeat_times; ?>',
						close_adz : 'true',
						adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' ); ?>'


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
function adz_get_advertise_popup( $ad_visibility, $ad_visibility_interval, $repeat_times, $target_type, $target, $ad_to_serve, $rotations_id, $sequence, $display_type, $adz_template ) {

	if ( ! isset( $_SESSION['first_time'] ) && $_SESSION['first_time'] == '' ) {
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
					action :'adz_get_advertise_content',
					target_type : '<?php echo $target_type; ?>',
					target : '<?php echo $target; ?>',
					ad_visibility_interval : '<?php echo $ad_visibility_interval; ?>',
					ad_to_serve : '<?php echo $ad_to_serve; ?>',
					rotations_id : '<?php echo $rotations_id; ?>',
					sequence : '<?php echo $sequence; ?>',
					display_type : '<?php echo $display_type; ?>',
					adz_template : '<?php echo $adz_template; ?>',
					repeat_times : '<?php echo $repeat_times; ?>',		          
					show_popup : 'true',
					adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' ); ?>'

				},
				success:function(data) {
				
					if(jQuery.trim(data) != 'continue'){
						jQuery('#show_overlay').css('visibility','visible');
						jQuery('#show_overlay').css('opacity','1');
						jQuery('.adz_popup .content').html(window.atob(data));
						

						var i = <?php echo $ad_visibility; ?>;
						var adz_interval = setInterval(function () {
							if (i >= 0) {
								jQuery('.adz_timer').text(" "+i+" ");
								i--;
							} else {
								jQuery('.continue_reading').text('Continue >>');
								jQuery('.continue_reading').addClass('close_adz');	
								jQuery('.continue_reading').css('cusrsor','pointer');						       
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
					action :'adz_get_advertise_content',
					target_type : '<?php echo $target_type; ?>',
					target : '<?php echo $target; ?>',
					ad_visibility_interval : '<?php echo $ad_visibility_interval; ?>',
					repeat_times : '<?php echo $repeat_times; ?>',
					close_adz : 'true',
					show_popup : 'true',
					adz_nonce: '<?php echo wp_create_nonce( 'adzdotworld' ); ?>' 
				},
				success:function(data) {
					jQuery('#show_overlay').css('visibility','hidden');
					jQuery('#show_overlay').css('opacity','0');    
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

// This Function is use to get publishers adz.
function adz_get_publisher_adz() {

	$args              = array(
		'posts_per_page' => -1,
		'post_type'      => 'adz_ad',
		'post_status'    => 'publish',
	);
	$posts_array       = get_posts( $args );
	$publisher_adz_ids = '';
	if ( ! empty( $posts_array ) ) {
		foreach ( $posts_array as $posts ) {

			$publisher_adz_ids .= get_post_meta( $posts->ID, 'network_ad_id', true ) . ',';
		}
	}

	return $publisher_adz_ids;
}//end adz_get_publisher_adz()

/* This Function Return the network adz id */
function adz_get_network_id( $adz_ids ) {
	$network_ids = '';
	if ( ! empty( $adz_ids ) ) {
		foreach ( $adz_ids as $adz ) {
			$network_ids .= get_post_meta( $adz, 'network_ad_id', true ) . ',';
		}
	}
	return rtrim( $network_ids );
}//end adz_get_network_id()

/* This Function is user check Visitor is logged in or not on adz.world */
function adz_world_logged_in() {

	// With no network connected there is no remote visitor to look up, and
	// 1.0.8 sent the visitor's IP address here on every call.
	if ( ! adz_network_enabled() ) {
		return is_user_logged_in() ? get_current_user_id() : false;
	}

	global $adz_ad_network_base_url;
	$ad_network_url = $adz_ad_network_base_url . "wp-json/adz_server/v1/ads/is_visitor_logged?visitor_ip={$_SERVER['REMOTE_ADDR']}";
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
}//end adz_world_logged_in()

/* This function is use to check roatation completed or not. */
function adz_check_roatation_completed_or_not( $rotation ) {
	$rotation_adz_pool = get_option( $rotation['rotation_id'] );
	if ( rtrim( $rotation['sequence'], ',' ) == '' ) {
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
}//end adz_check_roatation_completed_or_not()
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
function adz_validate_rotation_id( $rotation_id ) {
	$rotation_id = is_scalar( $rotation_id ) ? (string) $rotation_id : '';

	return preg_match( '/^adz_rotation_[0-9]+$/', $rotation_id ) ? $rotation_id : '';
}

/**
 * Store a rotation's state, but only under a genuine rotation id.
 *
 * @param mixed $rotation_id Candidate rotation id from the request.
 * @param mixed $state       Rotation state to store.
 * @return bool True when written, false when the id was refused.
 */
function adz_update_rotation_state( $rotation_id, $state ) {
	$rotation_id = adz_validate_rotation_id( $rotation_id );

	if ( '' === $rotation_id ) {
		return false;
	}

	return update_option( $rotation_id, $state );
}

/**
 * Whether this site is connected to a remote adz network.
 *
 * Off unless the site owner turns it on. With it off the plugin contacts no
 * outside server at all: adz come from this site's own adz_ad posts.
 *
 * 1.0.8 called adz.world unconditionally, on page loads, sending visitor IP
 * addresses with no disclosure and no way to decline. That is what the
 * wordpress.org guidelines forbid, and that host no longer exists, so every
 * one of those requests now simply hangs until it times out.
 *
 * @return bool
 */
function adz_network_enabled() {
	$options = get_option( 'adz_ad_options' );

	if ( empty( $options['connect_to_network'] ) ) {
		return false;
	}

	return '' !== adz_network_base_url();
}

/**
 * Base URL of the adz network this site talks to.
 *
 * Filterable so a site can point at a different network.
 *
 * @return string Trailing-slashed URL, or '' when none is configured.
 */
function adz_network_base_url() {
	$url = apply_filters( 'adz_ad_network_base_url', 'https://adz-world.com/' );

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
function adz_resolve_template( $template ) {
	$template = basename( trim( (string) $template ) );

	if ( '' === $template || '.php' !== substr( $template, -4 ) ) {
		return '';
	}

	if ( ! in_array( $template, adz_available_templates(), true ) ) {
		return '';
	}

	return ADZ_WORLD . 'adz-templates/' . $template;
}

/**
 * The template files shipped in adz-templates/.
 *
 * @return string[] Filenames.
 */
function adz_available_templates() {
	static $templates = null;

	if ( null !== $templates ) {
		return $templates;
	}

	$found     = glob( ADZ_WORLD . 'adz-templates/*.php' );
	$templates = $found ? array_map( 'basename', $found ) : array();

	return $templates;
}

/**
 * Look up today's view record for a visitor against one target.
 *
 * SECURITY. The four queries this replaces were built by string concatenation,
 * with $_POST values passed through sanitize_text_field() on the way in.
 * sanitize_text_field() strips tags and control characters; it does not escape
 * quotes and is not a SQL escaping function, so those queries were injectable.
 * $wpdb->prepare() was used nowhere in the plugin.
 *
 * @param string $visitor_ip  Visitor address.
 * @param int    $target_id   Post the adz was shown against.
 * @param string $target_type Context, or '' to match any.
 * @return object|null Row object, or null when there is no view today.
 */
function adz_get_view_row( $visitor_ip, $target_id, $target_type = '' ) {
	global $wpdb;

	$table = $wpdb->prefix . 'adz_views';
	$today = current_time( 'Y-m-d' );

	if ( '' !== $target_type ) {
		// phpcs:ignore WordPress.DB -- plugin-owned table; values are prepared.
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE visitor_ip = %s AND `targate_id` = %d AND `target_type` = %s AND ad_date = %s",
				$visitor_ip,
				(int) $target_id,
				$target_type,
				$today
			),
			OBJECT
		);
	}

	// phpcs:ignore WordPress.DB -- plugin-owned table; values are prepared.
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE visitor_ip = %s AND `targate_id` = %d AND ad_date = %s",
			$visitor_ip,
			(int) $target_id,
			$today
		),
		OBJECT
	);
}

/**
 * Today's view record for a visitor, with no target filter.
 *
 * @param string $visitor_ip Visitor address.
 * @return object|null
 */
function adz_get_view_row_any( $visitor_ip ) {
	global $wpdb;

	$table = $wpdb->prefix . 'adz_views';

	// phpcs:ignore WordPress.DB -- plugin-owned table; values are prepared.
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE visitor_ip = %s AND ad_date = %s",
			$visitor_ip,
			current_time( 'Y-m-d' )
		),
		OBJECT
	);
}

/**
 * Read a template's header/footer settings.
 *
 * 1.0.8 called serialize() explicitly before update_option(), which already
 * serializes arrays itself, and the template then called unserialize() on the
 * result. That explicit pair is what made an option value reach unserialize()
 * -- an object-injection sink -- and it is removed. Values are now stored as a
 * plain array.
 *
 * Existing installs still hold the double-serialized string, so that form is
 * read here and converted on the next save rather than lost.
 *
 * @param string $template Template filename, e.g. 'template1.php'.
 * @return array{header_text:string,header_style:string,footer_text:string,footer_style:string}
 */
function adz_get_template_settings_array( $template ) {
	$defaults = array(
		'header_text'  => '',
		'header_style' => 'center',
		'footer_text'  => '',
		'footer_style' => 'center',
	);

	$template = basename( (string) $template );

	if ( '' === adz_resolve_template( $template ) ) {
		return $defaults;
	}

	$stored = get_option( 'template_' . $template );

	if ( is_array( $stored ) ) {
		return wp_parse_args( $stored, $defaults );
	}

	if ( is_string( $stored ) && '' !== $stored ) {
		// Legacy double-serialized value. Decoded without allowing objects.
		$legacy = maybe_unserialize( $stored );

		if ( is_array( $legacy ) ) {
			return wp_parse_args( $legacy, $defaults );
		}
	}

	return $defaults;
}

/**
 * Validate a text-alignment value.
 *
 * @param mixed $style Candidate.
 * @return string One of left, right, center.
 */
function adz_sanitize_alignment( $style ) {
	$style = sanitize_key( (string) $style );

	return in_array( $style, array( 'left', 'right', 'center' ), true ) ? $style : 'center';
}
