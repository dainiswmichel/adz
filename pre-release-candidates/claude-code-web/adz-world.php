<?php
/**
 * Plugin Name: adz-world
 * Description: Run your website like a TV station by requiring adz views in exchange for access to content.
 * Version: 1.0.9
 * Author: dainismichel
 * Author URI: http://www.dainiswmichel.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: adz-world
 * Requires at least: 6.2
 * Requires PHP: 7.4
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
define( 'ADZWORLD_VERSION', '1.0.9' );

/**
 * Product name and public site.
 *
 * A rename edits the Plugin Name header and ADZWORLD_HOME. Screens and links
 * read those two values. Translation calls keep the literal text domain
 * adz-world, which is this plugin's WordPress.org identity.
 */
$adzworld_plugin_header = get_file_data(
	__FILE__,
	array(
		'Name' => 'Plugin Name',
	),
	'plugin'
);
$adzworld_plugin_name   = '';
if ( isset( $adzworld_plugin_header['Name'] ) && is_string( $adzworld_plugin_header['Name'] ) ) {
	$adzworld_plugin_name = $adzworld_plugin_header['Name'];
}
if ( '' === $adzworld_plugin_name ) {
	$adzworld_plugin_name = 'adz-world';
}
define( 'ADZWORLD_NAME', $adzworld_plugin_name );
define( 'ADZWORLD_HOME', 'https://adz-world.com' );
unset( $adzworld_plugin_header, $adzworld_plugin_name );

/**
 * Address on the public site.
 *
 * @param string $path Path after the site address. Empty returns the address with a trailing slash.
 * @return string
 */
function adzworld_home_url( $path = '' ) {
	$home = untrailingslashit( ADZWORLD_HOME );
	$path = ltrim( (string) $path, '/' );
	if ( '' === $path ) {
		return $home . '/';
	}
	return $home . '/' . $path;
}

/**
 * Host of the public site.
 *
 * @return string
 */
function adzworld_home_host() {
	$host = wp_parse_url( ADZWORLD_HOME, PHP_URL_HOST );
	return is_string( $host ) ? $host : '';
}

global $adzworld_network_base_url;
$adzworld_network_base_url = adzworld_home_url();
require_once __DIR__ . '/vendor/eof/eof.php';
require_once __DIR__ . '/vendor/eof/core/class-eof-field.php';
require_once __DIR__ . '/vendor/eof/adz-config.php';
require_once __DIR__ . '/classes/class-adz-networkauthorization.php';
require_once __DIR__ . '/classes/eof_fields/class-eof-field-dynamic.php';
require_once __DIR__ . '/classes/eof_fields/class-eof-field-select-caps.php';
require_once __DIR__ . '/session-handling.php';
require_once __DIR__ . '/includes/adz-helper.php';
require_once __DIR__ . '/includes/adz-cpt-ads.php';
require_once __DIR__ . '/includes/adz-shortcode.php';
require_once __DIR__ . '/includes/adzworld-earnings.php';
require_once __DIR__ . '/includes/adz-preferences.php';
require_once __DIR__ . '/includes/adz-preference-ui.php';
require_once __DIR__ . '/includes/adzworld-feedback.php';
require_once __DIR__ . '/includes/adzworld-affiliates.php';
require_once __DIR__ . '/includes/adzworld-journey.php';
require_once __DIR__ . '/includes/adzworld-products.php';
require_once __DIR__ . '/includes/adzworld-ebay.php';
require_once __DIR__ . '/includes/adzworld-etsy.php';
require_once __DIR__ . '/includes/adzworld-bestbuy.php';
require_once __DIR__ . '/includes/adzworld-avantlink.php';
require_once __DIR__ . '/includes/adzworld-itunes.php';
require_once __DIR__ . '/includes/adzworld-travelpayouts.php';
require_once __DIR__ . '/includes/adzworld-getpaid.php';
require_once __DIR__ . '/includes/adzworld-internal.php';
require_once __DIR__ . '/includes/adzworld-networks.php';
require_once __DIR__ . '/includes/adzworld-registrations.php';
require_once __DIR__ . '/includes/adzworld-desk.php';
require_once __DIR__ . '/includes/adzworld-rotation.php';
require_once __DIR__ . '/includes/adzworld-starter.php';
require_once __DIR__ . '/includes/adzworld-manual.php';
require_once __DIR__ . '/includes/adzworld-request-context.php';
define( 'ADZ_WORLD', plugin_dir_path( __FILE__ ) );

/**
 * Determine whether the current request uses the legacy advertising display.
 *
 * The modern rotation renderer loads its own scoped assets. Loading the legacy
 * presentation assets on every public page adds unused CSS and a blocking
 * script, so they are limited to legacy shortcodes and configured legacy
 * page rotations.
 *
 * @return bool Whether the legacy frontend assets are required.
 */
function adzworld_request_uses_legacy_assets() {
	global $adzworld_publisher_rotations;

	if ( ! empty( $adzworld_publisher_rotations ) ) {
		return true;
	}

	if ( ! is_singular() ) {
		return false;
	}

	$post = get_queried_object();

	return $post instanceof WP_Post && has_shortcode( $post->post_content, 'Adzworld' );
}

/**
 * Enqueue the legacy frontend's styles and required script dependencies.
 */
function adzworld_enqueue_scripts() {
	if ( ! adzworld_request_uses_legacy_assets() ) {
		return;
	}

	wp_enqueue_style( 'adz_custombox', plugin_dir_url( __FILE__ ) . 'css/overlay.css', array(), '1.0.9' );
	wp_enqueue_style( 'adz_css', plugin_dir_url( __FILE__ ) . 'css/styles.css', array(), '1.0.9' );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'adzworld-legacy', plugin_dir_url( __FILE__ ) . 'assets/legacy.js', array( 'jquery' ), '1.0.9', true );
}//end adzworld_enqueue_scripts()

add_action( 'wp_enqueue_scripts', 'adzworld_enqueue_scripts' );

/**
 * Determine whether the current administrator screen belongs to adz-world.
 *
 * @return bool Whether this is an advertisement or adz-world settings screen.
 */
function adzworld_is_plugin_admin_screen() {
	$screen = get_current_screen();
	if ( $screen instanceof WP_Screen && 'adz_ad' === $screen->post_type ) {
		return true;
	}

	$page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
	$page = is_string( $page ) ? sanitize_key( $page ) : '';

	return function_exists( 'adzworld_desk_submenu_slugs' )
		&& in_array( $page, adzworld_desk_submenu_slugs(), true );
}

/**
 * Enqueue the administrator's template and editor controls on plugin screens.
 */
function adzworld_enqueue_scripts_admin() {
	if ( ! adzworld_is_plugin_admin_screen() ) {
		return;
	}

	wp_enqueue_script( 'adz_admin_script', plugin_dir_url( __FILE__ ) . 'js/plugin.js', array( 'jquery' ), '1.0.9', true );
	wp_localize_script(
		'adz_admin_script',
		'adzworldAdmin',
		array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'adzworld_template_settings' ),
			'iframePrompt'  => __( 'Enter the HTTPS URL to display in this advertisement:', 'adz-world' ),
			'iframeInvalid' => __( 'Enter a complete HTTP or HTTPS URL.', 'adz-world' ),
			'settingsError' => __( 'Template settings could not be loaded. Try again.', 'adz-world' ),
		)
	);
}//end adzworld_enqueue_scripts_admin()


add_action( 'admin_enqueue_scripts', 'adzworld_enqueue_scripts_admin' );


/**
 * Serve or close an ad only for a verified, server-rendered configuration.
 */
function adzworld_get_advertise_content() {
	if ( ! adzworld_verify_ad_context( wp_unslash( $_POST ) ) ) {
		wp_send_json_error( array( 'message' => __( 'The advertising configuration could not be verified. Reload the page before trying again.', 'adz-world' ) ), 403 );
	}
	$nonce = isset( $_REQUEST['adz_nonce'] ) && is_string( $_REQUEST['adz_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['adz_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'adzdotworld' ) ) {
		wp_send_json_error( array( 'message' => __( 'The advertising request could not be verified.', 'adz-world' ) ), 403 );
	}
	$closing = isset( $_POST['close_adz'] ) && 'true' === $_POST['close_adz'];
	if ( ! isset( $_POST['ad_visibility_interval'], $_POST['target'] )
		|| ! is_scalar( $_POST['ad_visibility_interval'] ) || ! is_scalar( $_POST['target'] )
		|| ( ! $closing && ( ! isset( $_POST['ad_to_serve'] ) || ! is_scalar( $_POST['ad_to_serve'] ) ) )
		|| ! is_numeric( $_POST['ad_visibility_interval'] ) ) {
		wp_send_json_error( array( 'message' => __( 'The advertising request has invalid target or interval fields.', 'adz-world' ) ), 400 );
	}
	if ( ! $closing && ! isset( $_POST['display_type'], $_POST['adz_template'], $_POST['sequence'], $_POST['repeat_times'] ) ) {
		wp_send_json_error( array( 'message' => __( 'The advertising request is missing its presentation configuration.', 'adz-world' ) ), 400 );
	}
	$adz_ad_options        = get_option( 'adz_ad_options' );
	$adz_publisher_options = get_option( 'adz_publisher_options' );
	$reg                   = false;
	if ( isset( $adz_publisher_options['adz_registered'] ) ) {
		$reg = $adz_publisher_options['adz_registered'];
	}
	$remote_ads = adzworld_network_enabled();
	if ( $remote_ads && ! $reg ) {
		wp_send_json_error( array( 'message' => __( 'The advertising service is enabled but not registered.', 'adz-world' ) ), 503 );
	}
	global $adzworld_network_base_url;
	$next_adz_pool = get_option( 'next_adz_pool' );
	$rotation_id   = isset( $_POST['rotations_id'] ) && is_string( $_POST['rotations_id'] ) ? adzworld_configured_rotation_id( sanitize_text_field( wp_unslash( $_POST['rotations_id'] ) ) ) : '';
	if ( ! isset( $_POST['target_type'] ) || ! is_string( $_POST['target_type'] ) || ( 'shortcode' !== $_POST['target_type'] && '' === $rotation_id ) ) {
		wp_send_json_error( array( 'message' => __( 'The requested ad rotation is not configured.', 'adz-world' ) ), 400 );
	}
	$rotation_adz_pool = $rotation_id ? get_option( $rotation_id ) : array();
	$publisher_user_id = isset( $adz_publisher_options['adz_registered']['publisher_user_id'] ) ? absint( $adz_publisher_options['adz_registered']['publisher_user_id'] ) : 0;
	$ad_to_serve_val   = isset( $_POST['ad_to_serve'] ) && is_scalar( $_POST['ad_to_serve'] ) ? sanitize_text_field( wp_unslash( $_POST['ad_to_serve'] ) ) : '';
	if ( $remote_ads && ! $closing ) {
		if ( 'publisher' === $next_adz_pool || ! $next_adz_pool ) {

			$ad_network_url = $adzworld_network_base_url . "wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$ad_to_serve_val}&ad_from=publisher";

		} elseif ( 'network' === $next_adz_pool ) {
			$ad_network_url = $adzworld_network_base_url . "wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$ad_to_serve_val}&ad_from=network";

		} elseif ( 'visitor' === $next_adz_pool ) {
			$is_visitor_logged_in = adzworld_logged_in();
			if ( $is_visitor_logged_in ) {

				$ad_network_url = $adzworld_network_base_url . "wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$ad_to_serve_val}&ad_from=visitor&publisher_user_id=$publisher_user_id&visitor_user_id=$is_visitor_logged_in&publisher_affiliate_id={$adz_ad_options['amazon_affiliate_id']}";
			}
		}//End of else
	} elseif ( ! $remote_ads ) {
		$next_adz_pool = 'publisher';
	}

	global $adzworld_network_base_url;
	if ( isset( $_POST['target_type'] ) ) {
		$target_type = sanitize_text_field( wp_unslash( $_POST['target_type'] ) );
	} else {
		$target_type = '';
	}

	$visitor_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	$adz_views = adzworld_get_view_row( $visitor_ip, isset( $_POST['target'] ) ? (int) $_POST['target'] : 0, $target_type );

	if ( empty( $adz_views ) ) {
		if ( $closing ) {
			$saved = adzworld_record_view( $visitor_ip, absint( $_POST['target'] ), $target_type );
			if ( ! $saved ) {
				wp_send_json_error( array( 'message' => __( 'The advertising view could not be recorded.', 'adz-world' ) ), 500 );
			}
			echo 'close';
			exit;
		}
	} elseif ( $closing ) {
			$saved = adzworld_record_view( $visitor_ip, absint( $_POST['target'] ), $target_type );
		if ( ! $saved ) {
			wp_send_json_error( array( 'message' => __( 'The advertising view could not be updated.', 'adz-world' ) ), 500 );
		}

			echo 'close';
			exit;
	}

	$ad_text = '';
	$args    = array(); // Will hold authetntication tokens
	// Nothing leaves this site unless the owner opted in.
	$response = $remote_ads ? wp_remote_get( $ad_network_url, $args ) : adzworld_local_ad_response( $ad_to_serve_val );
	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'message' => $response->get_error_message() ), 400 );
	}
	if ( is_array( $response ) ) {
		$body    = wp_remote_retrieve_body( $response );
		$ad_info = json_decode( $body );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) || ! is_array( $ad_info ) || empty( $ad_info )
			|| ! is_object( $ad_info[0] ) || ! isset( $ad_info[0]->content ) || ! is_string( $ad_info[0]->content ) ) {
			wp_send_json_error( array( 'message' => __( 'The advertising service returned an invalid response.', 'adz-world' ) ), 502 );
		}
		if ( is_array( $ad_info ) && ! empty( $ad_info ) ) {

			$ad      = $ad_info[0];
			$ad_text = $ad->content;

			$visitor_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

			$adz_views = adzworld_get_view_row( $visitor_ip, isset( $_POST['target'] ) ? (int) $_POST['target'] : 0, $target_type );

			$display_type = sanitize_text_field( wp_unslash( $_POST['display_type'] ) );

			if ( empty( $adz_views ) ) {
				if ( isset( $_SESSION['first_time'] ) && $_SESSION['first_time'] >= time() ) {
					echo 'continue';
					exit;
				} else {
					$_SESSION['first_time'] = time() + sanitize_text_field( wp_unslash( $_POST['ad_visibility_interval'] ) );
				}

				$adz_template_file = adzworld_resolve_template( isset( $_POST['adz_template'] ) ? sanitize_text_field( wp_unslash( $_POST['adz_template'] ) ) : '' );
				if ( '' === $adz_template_file ) {
					wp_send_json_error( array( 'message' => __( 'The advertising template is not available.', 'adz-world' ) ), 400 );
				}
				require $adz_template_file;
				if ( 'publisher' === $next_adz_pool || ! $next_adz_pool ) {
					if ( 'shortcode' === $target_type ) {
						$publisher_rotations_stats = get_post_meta( sanitize_text_field( wp_unslash( $_POST['target'] ) ), 'adz_rotation_shortcode', true );
						if ( empty( $publisher_rotations_stats['served'] ) || ! $publisher_rotations_stats ) {

							$un_serverd                   = explode( ',', sanitize_text_field( wp_unslash( $_POST['sequence'] ) ) );
							$ad_to_serve                  = array_shift( $un_serverd );
							$roatation_stats['served'][]  = sanitize_text_field( wp_unslash( $_POST['ad_to_serve'] ) );
							$roatation_stats['un_served'] = $un_serverd;
							update_post_meta( sanitize_text_field( wp_unslash( $_POST['target'] ) ), 'adz_rotation_shortcode', $roatation_stats );

						} else {
							array_shift( $publisher_rotations_stats['un_served'] );
							$roatation_stats['served']    = array_merge( $publisher_rotations_stats['served'], array( sanitize_text_field( wp_unslash( $_POST['ad_to_serve'] ) ) ) );
							$roatation_stats['un_served'] = $publisher_rotations_stats['un_served'];
							update_post_meta( sanitize_text_field( wp_unslash( $_POST['target'] ) ), 'adz_rotation_shortcode', $roatation_stats );
						}
					} elseif ( empty( $rotation_adz_pool['served'] ) || ! $rotation_adz_pool ) {
							$un_serverd = explode( ',', sanitize_text_field( wp_unslash( $_POST['sequence'] ) ) );
							array_shift( $un_serverd );
							$roatation_stats['served'][]  = sanitize_text_field( wp_unslash( $_POST['ad_to_serve'] ) );
							$roatation_stats['un_served'] = $un_serverd;
							adzworld_update_rotation_state( $rotation_id, $roatation_stats );

					} else {
						array_shift( $rotation_adz_pool['un_served'] );
						$roatation_stats['served']    = array_merge( $rotation_adz_pool['served'], array( sanitize_text_field( wp_unslash( $_POST['ad_to_serve'] ) ) ) );
						$roatation_stats['un_served'] = $rotation_adz_pool['un_served'];
						adzworld_update_rotation_state( $rotation_id, $roatation_stats );
					}
					update_option( 'next_adz_pool', 'network' );

				} elseif ( 'network' === $next_adz_pool ) {

					if ( adzworld_logged_in() ) {
						update_option( 'next_adz_pool', 'visitor' );
					} else {
						update_option( 'next_adz_pool', 'publisher' );
					}
				} elseif ( 'visitor' === $next_adz_pool ) {

					update_option( 'next_adz_pool', 'publisher' );

				}
				exit;

			} else {

				$last_update     = strtotime( $adz_views->updated );
				$current_time    = time();
				$seconds         = $current_time - $last_update;
				$interval_in_sec = sanitize_text_field( wp_unslash( $_POST['ad_visibility_interval'] ) );
				$display_type    = sanitize_text_field( wp_unslash( $_POST['display_type'] ) );

				if ( $seconds >= $interval_in_sec && ( $adz_views->number_of_times < $_POST['repeat_times'] || 'infinite' === sanitize_text_field( wp_unslash( $_POST['repeat_times'] ) ) ) ) {

					if ( isset( $_SESSION['first_time'] ) && $_SESSION['first_time'] >= time() ) {
						echo 'continue';
						exit;
					} else {
						$_SESSION['first_time'] = time() + intval( $_POST['ad_visibility_interval'] );
					}

					$adz_template_file = adzworld_resolve_template( isset( $_POST['adz_template'] ) ? sanitize_text_field( wp_unslash( $_POST['adz_template'] ) ) : '' );
					if ( '' === $adz_template_file ) {
						wp_send_json_error( array( 'message' => __( 'The advertising template is not available.', 'adz-world' ) ), 400 );
					}
					require $adz_template_file;

					if ( 'publisher' === $next_adz_pool || ! $next_adz_pool ) {

						if ( 'shortcode' === $target_type ) {
							$publisher_rotations_stats = get_post_meta( sanitize_text_field( wp_unslash( $_POST['target'] ) ), 'adz_rotation_shortcode', true );
							if ( empty( $publisher_rotations_stats['served'] ) || ! $publisher_rotations_stats ) {

								$un_serverd                   = explode( ',', sanitize_text_field( wp_unslash( $_POST['sequence'] ) ) );
								$ad_to_serve                  = array_shift( $un_serverd );
								$roatation_stats['served'][]  = sanitize_text_field( wp_unslash( $_POST['ad_to_serve'] ) );
								$roatation_stats['un_served'] = $un_serverd;
								update_post_meta( sanitize_text_field( wp_unslash( $_POST['target'] ) ), 'adz_rotation_shortcode', $roatation_stats );

							} else {
								array_shift( $publisher_rotations_stats['un_served'] );
								$roatation_stats['served'] = array_merge( $publisher_rotations_stats['served'], array( sanitize_text_field( wp_unslash( $_POST['ad_to_serve'] ) ) ) );
								if ( ( ! isset( $publisher_rotations_stats['un_served'][0] ) || '' === $publisher_rotations_stats['un_served'][0] ) ) {
									$publisher_rotations_stats['un_served'] = array();
								}
								$roatation_stats['un_served'] = $publisher_rotations_stats['un_served'];
								update_post_meta( sanitize_text_field( wp_unslash( $_POST['target'] ) ), 'adz_rotation_shortcode', $roatation_stats );

							}
						} elseif ( empty( $rotation_adz_pool['served'] ) || ! $rotation_adz_pool ) {
								$un_serverd = explode( ',', sanitize_text_field( wp_unslash( $_POST['sequence'] ) ) );
								array_shift( $un_serverd );
								$roatation_stats['served'][]  = sanitize_text_field( wp_unslash( $_POST['ad_to_serve'] ) );
								$roatation_stats['un_served'] = $un_serverd;
								adzworld_update_rotation_state( $rotation_id, $roatation_stats );

						} else {
							array_shift( $rotation_adz_pool['un_served'] );
							$roatation_stats['served'] = array_merge( $rotation_adz_pool['served'], array( sanitize_text_field( wp_unslash( $_POST['ad_to_serve'] ) ) ) );
							if ( ( ! isset( $rotation_adz_pool['un_served'][0] ) || '' === $rotation_adz_pool['un_served'][0] ) ) {
								$rotation_adz_pool['un_served'] = array();
							}
							$roatation_stats['un_served'] = $rotation_adz_pool['un_served'];
							adzworld_update_rotation_state( $rotation_id, $roatation_stats );

						}

						update_option( 'next_adz_pool', 'network' );

					} elseif ( 'network' === $next_adz_pool ) {

						if ( adzworld_logged_in() ) {

							update_option( 'next_adz_pool', 'visitor' );
						} else {

							update_option( 'next_adz_pool', 'publisher' );
						}
					} elseif ( 'visitor' === $next_adz_pool ) {

						update_option( 'next_adz_pool', 'publisher' );
					}
					exit;
				} else {
					echo 'continue';
					exit;
				}
			}
		}
	}
}//end adzworld_get_advertise_content()
add_action( 'wp_ajax_adzworld_get_advertise_content', 'adzworld_get_advertise_content' );
add_action( 'wp_ajax_nopriv_adzworld_get_advertise_content', 'adzworld_get_advertise_content' );
add_action( 'wp_ajax_adz_get_advertise_content', 'adzworld_get_advertise_content' );
add_action( 'wp_ajax_nopriv_adz_get_advertise_content', 'adzworld_get_advertise_content' );


/**
 * Wrap article content for the legacy in-page presentation.
 *
 * @param string $content Article markup.
 * @return string Wrapped markup.
 */
function adzworld_add_wrapper( $content ) {

	return '<div class="adz-world-container">' . $content . '</div>';
}

add_filter( 'the_content', 'adzworld_add_wrapper' );



/**
 * Resolve saved rotations for the current page and account.
 */
function adzworld_check_through_page() {

	$category_add        = 'false';
	$ad_settings_options = get_option( 'adz_ad_options' );
	$user                = wp_get_current_user();
	$categories          = wp_get_post_terms( get_the_ID(), 'category', array( 'fields' => 'all' ) );
	$category_ids        = array();
	$tag_ids             = array();
	if ( ! empty( $categories ) ) {
		foreach ( $categories as $category ) {
			$category_ids[] = $category->term_id;
		}
	} else {
		$category_ids = array();
	}
	$tags = wp_get_post_terms( get_the_ID(), 'post_tag', array( 'fields' => 'all' ) );
	if ( ! empty( $tags ) ) {
		foreach ( $tags as $tag ) {
			$tag_ids[] = $tag->term_id;
		}
	} else {
		$tag_ids = array();
	}

	if ( ! empty( $ad_settings_options['ad_settings'] ) && ! is_home() ) {
		$category_add                 = 'false';
		$adzworld_publisher_rotations = array();

		foreach ( $ad_settings_options['ad_settings'] as $options ) {
			if ( ! isset( $options['no_of_times'] ) ) {
				$options['no_of_times'] = '';
			}

			if ( ! isset( $options['rotation_categories'] ) || ! is_array( $options['rotation_categories'] ) ) {
				$options['rotation_categories'] = array();
			}

			if ( ! isset( $options['rotation_tags'] ) || ! is_array( $options['rotation_tags'] ) ) {
				$options['rotation_tags'] = array();
			}

			if ( ! isset( $options['rotation_pages'] ) || ! is_array( $options['rotation_pages'] ) ) {
				$options['rotation_pages'] = array();
			}

			if ( ! isset( $options['ad_sequences'] ) || ! is_array( $options['ad_sequences'] ) ) {
				$options['ad_sequences'] = array();
			}

			if ( array_intersect( $options['rotation_categories'], $category_ids ) || in_array( absint( get_the_ID() ), array_map( 'absint', $options['rotation_pages'] ), true ) || array_intersect( $options['rotation_tags'], $tag_ids ) ) {

				if ( 'in_sequence' === $options['adz_rotation'] ) {

					$adz_sequences = adzworld_get_network_id( $options['ad_sequences'] );
				} else {

					$adz_sequences = adzworld_get_publisher_ads();
				}

				if ( function_exists( 'wc_memberships' ) && is_user_logged_in() && ! wc_memberships_is_user_active_member( get_current_user_id(), 'never-show-adz' ) ) {

					if ( wc_memberships_is_user_active_member( get_current_user_id(), $options['member_level'] ) ) {

						$adzworld_publisher_rotations[] = array(
							'sequence'       => $adz_sequences,
							'roatation_name' => $options['rotation_name'],
							'loop'           => $options['loop'],
							'no_of_times'    => $options['no_of_times'],
							'ad_seconds'     => $options['ad_seconds'],
							'browse_seconds' => $options['browse_seconds'],
							'popup_or_page'  => $options['popup_or_page'],
							'rotation_id'    => $options['rotation_id'],
							'rotation_page'  => get_the_ID(),
							'adz_template'   => $options['adz_template'],
						);

					}
				} elseif ( function_exists( 'wc_memberships' ) && wc_memberships_is_user_active_member( get_current_user_id(), $options['member_level'] ) && 'never-show-adz' === $options['member_level'] ) {

					$adzworld_publisher_rotations[] = array(
						'sequence'       => $adz_sequences,
						'roatation_name' => $options['rotation_name'],
						'loop'           => $options['loop'],
						'no_of_times'    => $options['no_of_times'],
						'ad_seconds'     => $options['ad_seconds'],
						'browse_seconds' => $options['browse_seconds'],
						'popup_or_page'  => $options['popup_or_page'],
						'rotation_id'    => $options['rotation_id'],
						'rotation_page'  => get_the_ID(),
						'adz_template'   => $options['adz_template'],
					);

				} elseif ( ! is_user_logged_in() && 'non-logged' === $options['member_level'] ) {

					$adzworld_publisher_rotations[] = array(
						'sequence'       => $adz_sequences,
						'roatation_name' => $options['rotation_name'],
						'loop'           => $options['loop'],
						'no_of_times'    => $options['no_of_times'],
						'ad_seconds'     => $options['ad_seconds'],
						'browse_seconds' => $options['browse_seconds'],
						'popup_or_page'  => $options['popup_or_page'],
						'rotation_id'    => $options['rotation_id'],
						'rotation_page'  => get_the_ID(),
						'adz_template'   => $options['adz_template'],
					);

				}
			}
		}//End of foreach

		$GLOBALS['adzworld_publisher_rotations'] = $adzworld_publisher_rotations;

		if ( ! empty( $adzworld_publisher_rotations ) ) {

			$rotation_reset = 'false';
			foreach ( $adzworld_publisher_rotations as $roatations ) {
				$rotation_adz_pool = get_option( $roatations['rotation_id'] );
				if ( $rotation_adz_pool ) {
					if ( empty( $rotation_adz_pool['un_served'] ) || ( 1 === count( $rotation_adz_pool['un_served'] ) && ( ! isset( $rotation_adz_pool['un_served'][0] ) || '' === $rotation_adz_pool['un_served'][0] ) ) ) {
						$rotation_reset = 'true';
					} else {
						$rotation_reset = 'false';
					}
				} else {
					$rotation_reset = 'false';
				}
			}

			if ( 'true' === $rotation_reset ) {
				foreach ( $adzworld_publisher_rotations as $roatations ) {
					delete_option( $roatations['rotation_id'] );
				}
			}
			foreach ( $adzworld_publisher_rotations as $rotations ) {

				$ad_to_serve = adzworld_check_rotation_completed( $rotations );
				if ( $ad_to_serve ) {

					if ( ! empty( $rotations['loop'] ) && is_array( $rotations['loop'] ) && 'yes' === $rotations['loop'][0] ) {
						$repeat_times = 'infinite';

					} elseif ( '' !== (string) $rotations['no_of_times'] ) {
						$repeat_times = $rotations['no_of_times'];

					} else {
						$repeat_times = 'infinite';
					}

					if ( 'page' === $rotations['popup_or_page'] ) {
						break;

					} elseif ( 'popup' === $rotations['popup_or_page'] ) {

						break;

					} elseif ( 'thru_page' === $rotations['popup_or_page'] ) {

						adzworld_show_advertisement( $rotations, $ad_to_serve, $repeat_times );

					}
				}// End Of the Coddition to check ad_to_serve.
			}// End of the loop of publisher rotations
		}// End of condition to check for publisher rotations is empty of not.
	}// End of condition to check for adz rotation empty or not.
}//end adzworld_check_through_page()

/**
 * Render the scheduled controls for the first applicable rotation.
 */
function adzworld_display_ads() {

	global $adzworld_publisher_rotations;
	if ( ! empty( $adzworld_publisher_rotations ) ) {

		foreach ( $adzworld_publisher_rotations as $rotations ) {
			$ad_to_serve = adzworld_check_rotation_completed( $rotations );
			if ( $ad_to_serve ) {

				if ( ! empty( $rotations['loop'] ) && is_array( $rotations['loop'] ) && 'yes' === $rotations['loop'][0] ) {
					$repeat_times = 'infinite';

				} elseif ( '' !== (string) $rotations['no_of_times'] ) {
					$repeat_times = $rotations['no_of_times'];

				} else {
					$repeat_times = 'infinite';
				}

				if ( 'page' === $rotations['popup_or_page'] ) {

					adzworld_get_advertise( $rotations['ad_seconds'], $rotations['browse_seconds'], $repeat_times, 'page', $rotations['rotation_page'], $ad_to_serve, $rotations['rotation_id'], $rotations['sequence'], $rotations['popup_or_page'], $rotations['adz_template'] );
					break;

				} elseif ( 'popup' === $rotations['popup_or_page'] ) {

					adzworld_get_advertise_popup( $rotations['ad_seconds'], $rotations['browse_seconds'], $repeat_times, 'page', $rotations['rotation_page'], $ad_to_serve, $rotations['rotation_id'], $rotations['sequence'], $rotations['popup_or_page'], $rotations['adz_template'] );
					break;

				}
			}// End Of the Coddition to check ad_to_serve.
		}// End of the loop of publisher rotations
	}// End of condition to check for publisher rotations is empty of not.
}//end adzworld_display_ads()

add_action( 'wp_head', 'adzworld_display_ads' );


/**
 * Render an applicable through-page advertisement.
 *
 * @param array $roatations Saved rotation configuration (legacy name).
 * @param mixed $ad_to_serve Selected ad identifier.
 * @param mixed $repeat_times Repeat count or infinite.
 */
function adzworld_show_advertisement( $roatations, $ad_to_serve, $repeat_times ) {
	$rotation_adz_pool = get_option( $roatations['rotation_id'] );
	$next_adz_pool     = get_option( 'next_adz_pool' );
	$adz_ad_options    = get_option( 'adz_ad_options' );

	$adz_publisher_options = get_option( 'adz_publisher_options' );
	$publisher_user_id     = isset( $adz_publisher_options['adz_registered']['publisher_user_id'] ) ? absint( $adz_publisher_options['adz_registered']['publisher_user_id'] ) : 0;
	$reg                   = false;
	if ( isset( $adz_publisher_options['adz_registered'] ) ) {
		$reg = $adz_publisher_options['adz_registered'];
	}
	$remote_ads = adzworld_network_enabled();
	if ( $remote_ads && ! $reg ) {
		return;
	}

	global $adzworld_network_base_url;
	if ( $remote_ads ) {
		if ( 'publisher' === $next_adz_pool || ! $next_adz_pool ) {

			$ad_network_url = $adzworld_network_base_url . "wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$ad_to_serve}&ad_from=publisher";

		} elseif ( 'network' === $next_adz_pool ) {

			$ad_network_url = $adzworld_network_base_url . "wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$ad_to_serve}&ad_from=network";

		} elseif ( 'visitor' === $next_adz_pool ) {

			$is_visitor_logged_in = adzworld_logged_in();
			if ( $is_visitor_logged_in ) {

				$ad_network_url = $adzworld_network_base_url . "wp-json/adz_server/v1/ads?id={$reg['ID']}&token={$reg['post_token']}&nettoken={$reg['network_token']}&referral_code={$adz_ad_options['referral_code']}&publisher_ad={$ad_to_serve}&ad_from=visitor&publisher_user_id=$publisher_user_id&visitor_user_id=$is_visitor_logged_in&publisher_affiliate_id={$adz_ad_options['amazon_affiliate_id']}";
			}
		}//End of else
	} else {
		$next_adz_pool = 'publisher';
	}

	$ad_text = '';
	$args    = array();
	// Nothing leaves this site unless the owner opted in.
	$response = $remote_ads ? wp_remote_get( $ad_network_url, $args ) : adzworld_local_ad_response( $ad_to_serve );
	if ( is_wp_error( $response ) ) {
		wp_die( esc_html( $response->get_error_message() ) );
	}
	if ( is_array( $response ) ) {
		$body    = $response['body'];
		$ad_info = json_decode( $body );
		if ( is_array( $ad_info ) && ! empty( $ad_info ) ) {

			$visitor_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$ad         = $ad_info[0];
			$ad_text    = $ad->content;
			$adz_views  = adzworld_get_view_row( $visitor_ip, absint( get_the_ID() ), 'page' );

			if ( empty( $adz_views ) ) {
				$ad_visibility_interval = $roatations['browse_seconds'];
				$ad_visibility          = $roatations['ad_seconds'];
				$target                 = get_the_ID();
				$target_type            = 'page';
				$rotation_id            = $roatations['rotation_id'];
				$display_type           = $roatations['popup_or_page'];
				$adz_template_file      = adzworld_resolve_template( isset( $roatations['adz_template'] ) ? $roatations['adz_template'] : '' );
				if ( '' === $adz_template_file ) {
					return;
				}
				require $adz_template_file;
				if ( 'publisher' === $next_adz_pool || ! $next_adz_pool ) {

					if ( empty( $rotation_adz_pool['served'] ) || ! $rotation_adz_pool ) {
						$un_serverd = explode( ',', $roatations['sequence'] );
						array_shift( $un_serverd );
						$roatation_stats['served'][]  = $ad_to_serve;
						$roatation_stats['un_served'] = $un_serverd;
						update_option( $roatations['rotation_id'], $roatation_stats );

					} else {
						array_shift( $rotation_adz_pool['un_served'] );
						$roatation_stats['served'] = array_merge( $rotation_adz_pool['served'], array( $ad_to_serve ) );
						if ( ( ! isset( $rotation_adz_pool['un_served'][0] ) || '' === $rotation_adz_pool['un_served'][0] ) ) {
							$rotation_adz_pool['un_served'] = array();
						}
						$roatation_stats['un_served'] = $rotation_adz_pool['un_served'];
						update_option( $roatations['rotation_id'], $roatation_stats );

					}//End of if else.
					update_option( 'next_adz_pool', 'network' );

				} elseif ( 'network' === $next_adz_pool ) {

					if ( adzworld_logged_in() ) {
						update_option( 'next_adz_pool', 'visitor' );
					} else {
						update_option( 'next_adz_pool', 'publisher' );
					}
				} elseif ( 'visitor' === $next_adz_pool ) {

					update_option( 'next_adz_pool', 'publisher' );

				}
				exit;

			} else {
				$last_update            = strtotime( $adz_views->updated );
				$current_time           = time();
				$seconds                = $current_time - $last_update;
				$ad_visibility_interval = $roatations['browse_seconds'];
				$ad_visibility          = $roatations['ad_seconds'];
				$target                 = get_the_ID();
				$target_type            = 'page';
				$rotation_id            = $roatations['rotation_id'];
				$display_type           = $roatations['popup_or_page'];

				if ( $seconds >= $roatations['browse_seconds'] && ( $adz_views->number_of_times < $repeat_times || 'infinite' === $repeat_times ) ) {

					$adz_template_file = adzworld_resolve_template( isset( $roatations['adz_template'] ) ? $roatations['adz_template'] : '' );
					if ( '' === $adz_template_file ) {
						return;
					}
					require $adz_template_file;
					if ( 'publisher' === $next_adz_pool || ! $next_adz_pool ) {

						if ( empty( $rotation_adz_pool['served'] ) || ! $rotation_adz_pool ) {
							$un_serverd = explode( ',', $roatations['sequence'] );
							array_shift( $un_serverd );
							$roatation_stats['served'][]  = $ad_to_serve;
							$roatation_stats['un_served'] = $un_serverd;
							update_option( $roatations['rotation_id'], $roatation_stats );

						} else {
							array_shift( $rotation_adz_pool['un_served'] );
							$roatation_stats['served'] = array_merge( $rotation_adz_pool['served'], array( $ad_to_serve ) );
							if ( ( ! isset( $rotation_adz_pool['un_served'][0] ) || '' === $rotation_adz_pool['un_served'][0] ) ) {
								$rotation_adz_pool['un_served'] = array();
							}
							$roatation_stats['un_served'] = $rotation_adz_pool['un_served'];
							update_option( $roatations['rotation_id'], $roatation_stats );

						}//End of if else.

						update_option( 'next_adz_pool', 'network' );

					} elseif ( 'network' === $next_adz_pool ) {

						if ( adzworld_logged_in() ) {
							update_option( 'next_adz_pool', 'visitor' );
						} else {
							update_option( 'next_adz_pool', 'publisher' );
						}
					} elseif ( 'visitor' === $next_adz_pool ) {
						update_option( 'next_adz_pool', 'publisher' );

					}
					exit;
				}// End of if
			}//End of if else
		}//End of if to check adz_content
	}// End of if to check $response
}//end adzworld_show_advertisement()

add_action( 'wp', 'adzworld_check_through_page' );
