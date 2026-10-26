<?php
/**
 * Helper functions for serving adz.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/*
 * A plugin function named role_exists() used to be declared here. Nothing in
 * this plugin called it, and WordPress core defines the same name, so this
 * copy is not declared. An activation fatal from that collision was not
 * reproduced on WordPress 7.1.3 with PHP 8.4.7.
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
	$visitor_ip     = function_exists( 'adzworld_visitor_ip' ) ? adzworld_visitor_ip() : '';
	$ad_network_url = add_query_arg( 'visitor_ip', $visitor_ip, $adz_ad_network_base_url . 'wp-json/adz_server/v1/ads/is_visitor_logged' );
	if ( function_exists( 'adzworld_with_platform_arg' ) ) {
		$ad_network_url = adzworld_with_platform_arg( $ad_network_url );
	}
	$args     = array();
	$response = wp_remote_get( $ad_network_url, $args );

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
	$rotation_adz_pool = adz_rotation_state( isset( $rotation['rotation_id'] ) ? $rotation['rotation_id'] : '' );
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
 * The saved settings row for one rotation id.
 *
 * @param mixed $rotation_id Candidate rotation id.
 * @return array<string,mixed>
 */
function adzworld_saved_rotation_row( $rotation_id ) {
	$rotation_id = adz_validate_rotation_id( $rotation_id );

	if ( '' === $rotation_id ) {
		return array();
	}

	$options = get_option( 'adz_ad_options' );

	if ( ! is_array( $options ) || empty( $options['ad_settings'] ) || ! is_array( $options['ad_settings'] ) ) {
		return array();
	}

	foreach ( $options['ad_settings'] as $rotation ) {
		if ( is_array( $rotation ) && isset( $rotation['rotation_id'] ) && $rotation_id === $rotation['rotation_id'] ) {
			return $rotation;
		}
	}

	return array();
}

/**
 * A rotation id that is present in saved settings. A matching prefix is not enough.
 *
 * @param mixed $rotation_id Candidate rotation id.
 * @return string
 */
function adzworld_configured_rotation_id( $rotation_id ) {
	$row = adzworld_saved_rotation_row( $rotation_id );

	return $row ? adz_validate_rotation_id( $row['rotation_id'] ) : '';
}

/**
 * Whether a saved rotation is configured for this post, page, category, or tag.
 *
 * @param mixed $rotation_id Candidate rotation id.
 * @param mixed $target_id   Post or page id from the request.
 * @return bool
 */
function adzworld_rotation_covers_target( $rotation_id, $target_id ) {
	$row       = adzworld_saved_rotation_row( $rotation_id );
	$target_id = absint( $target_id );

	if ( ! $row || ! $target_id ) {
		return false;
	}

	$pages = isset( $row['rotation_pages'] ) ? array_map( 'absint', (array) $row['rotation_pages'] ) : array();

	if ( in_array( $target_id, $pages, true ) ) {
		return true;
	}

	$wanted_categories = isset( $row['rotation_categories'] ) ? array_map( 'absint', (array) $row['rotation_categories'] ) : array();

	if ( $wanted_categories && array_intersect( $wanted_categories, wp_get_post_categories( $target_id ) ) ) {
		return true;
	}

	$wanted_tags = isset( $row['rotation_tags'] ) ? array_map( 'absint', (array) $row['rotation_tags'] ) : array();

	if ( ! $wanted_tags ) {
		return false;
	}

	$tags = wp_get_post_terms( $target_id, 'post_tag', array( 'fields' => 'ids' ) );

	return is_array( $tags ) && (bool) array_intersect( $wanted_tags, array_map( 'absint', $tags ) );
}

/**
 * A published post whose content contains an adz shortcode.
 *
 * @param mixed $target_id Posted target.
 * @return int
 */
function adzworld_shortcode_rotation_target( $target_id ) {
	$target_id = absint( $target_id );
	$post      = get_post( $target_id );

	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return 0;
	}

	if ( has_shortcode( $post->post_content, 'Adzworld' ) || has_shortcode( $post->post_content, 'adzworld_stage' ) ) {
		return $target_id;
	}

	return 0;
}

/**
 * Keep rotation state to the two lists the rotator writes.
 *
 * @param mixed $state Posted or built state.
 * @return array<string,string[]>|null
 */
function adzworld_sanitize_rotation_state( $state ) {
	if ( ! is_array( $state ) ) {
		return null;
	}

	$clean_list = static function ( $list ) {
		$out = array();

		if ( ! is_array( $list ) ) {
			return $out;
		}

		foreach ( $list as $item ) {
			$item = is_scalar( $item ) ? (string) $item : '';

			if ( '' !== $item && 1 !== preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $item ) ) {
				continue;
			}

			$out[] = $item;

			if ( count( $out ) >= 100 ) {
				break;
			}
		}

		return $out;
	};

	return array(
		'served'    => $clean_list( isset( $state['served'] ) ? $state['served'] : array() ),
		'un_served' => $clean_list( isset( $state['un_served'] ) ? $state['un_served'] : array() ),
	);
}

/**
 * Read a rotation state only when the id is one saved in settings.
 *
 * @param mixed $rotation_id Candidate rotation id.
 * @return mixed Stored state, or false when the id is refused.
 */
function adz_rotation_state( $rotation_id ) {
	$rotation_id = adzworld_configured_rotation_id( $rotation_id );

	if ( '' === $rotation_id ) {
		return false;
	}

	return get_option( $rotation_id );
}

/**
 * Load one template from this plugin's adz-templates directory.
 *
 * A posted filename is reduced to a basename and must already exist in that
 * directory. Anything else is not loaded.
 *
 * @param mixed $file Candidate template filename.
 * @return void
 */
function adz_require_ad_template( $file ) {
	if ( ! function_exists( 'adzworld_template_filename' ) ) {
		return;
	}

	$file = adzworld_template_filename( $file );

	if ( '' === $file ) {
		return;
	}

	$path = dirname( __DIR__ ) . '/adz-templates/' . $file;

	if ( is_file( $path ) ) {
		require_once $path;
	}
}

/**
 * One day's ad-view row for a visitor and a target.
 *
 * @param string $visitor_ip  Visitor address stored by the existing view table.
 * @param int    $target_id   Target id.
 * @param string $target_type Target type, or an empty string when the caller has none.
 * @return object|null
 */
function adz_find_adz_view( $visitor_ip, $target_id, $target_type = '' ) {
	global $wpdb;

	$table = $wpdb->prefix . 'adz_views';
	$day   = date( 'Y-m-d' );
	$ip    = is_scalar( $visitor_ip ) ? (string) $visitor_ip : '';
	$id    = absint( $target_id );

	if ( '' === (string) $target_type ) {
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE visitor_ip = %s AND targate_id = %d AND ad_date = %s",
				$ip,
				$id,
				$day
			),
			OBJECT
		);
	}

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE visitor_ip = %s AND targate_id = %d AND target_type = %s AND ad_date = %s",
			$ip,
			$id,
			sanitize_key( (string) $target_type ),
			$day
		),
		OBJECT
	);
}

/**
 * One day's ad-view row for a visitor, without a target filter.
 *
 * @param string $visitor_ip Visitor address.
 * @return object|null
 */
function adz_find_adz_view_by_ip( $visitor_ip ) {
	global $wpdb;

	$table = $wpdb->prefix . 'adz_views';

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE visitor_ip = %s AND ad_date = %s",
			is_scalar( $visitor_ip ) ? (string) $visitor_ip : '',
			date( 'Y-m-d' )
		),
		OBJECT
	);
}

/**
 * Store a rotation's state, but only under a genuine rotation id.
 *
 * @param mixed $rotation_id Candidate rotation id from the request.
 * @param mixed $state       Rotation state to store.
 * @return bool True when written, false when the id was refused.
 */
function adz_update_rotation_state( $rotation_id, $state, $target_id = null ) {
	$rotation_id = adzworld_configured_rotation_id( $rotation_id );
	$state       = adzworld_sanitize_rotation_state( $state );

	if ( '' === $rotation_id || null === $state ) {
		return false;
	}

	if ( null !== $target_id && ! adzworld_rotation_covers_target( $rotation_id, $target_id ) ) {
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

	if ( empty( $options['connect_to_network'] ) || ! adzworld_terms_accepted() ) {
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
