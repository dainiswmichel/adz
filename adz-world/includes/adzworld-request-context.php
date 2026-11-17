<?php
/**
 * Bind public advertising requests to the configuration rendered by the site.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build the nonce action for a complete advertising request.
 *
 * @param array $fields Advertising fields, not arbitrary request keys.
 * @return string|WP_Error Action name, or an invalid-field error.
 */
function adzworld_ad_request_action( $fields ) {
	$canonical = array();
	foreach ( array( 'target_type', 'target', 'ad_visibility_interval', 'repeat_times', 'ad_to_serve', 'rotations_id', 'sequence', 'display_type', 'adz_template', 'close_adz' ) as $key ) {
		$value = isset( $fields[ $key ] ) ? $fields[ $key ] : '';
		if ( ! is_string( $value ) && ! is_int( $value ) ) {
			return new WP_Error( 'adzworld_request_type', __( 'An advertising request field has an invalid type.', 'adz-world' ) );
		}
		if ( strlen( (string) $value ) > 8192 ) {
			return new WP_Error( 'adzworld_request_size', __( 'An advertising request field exceeds its size limit.', 'adz-world' ) );
		}
		$canonical[ $key ] = (string) $value;
	}
	return 'adzworld_context_' . hash( 'sha256', wp_json_encode( $canonical ) );
}

/**
 * Sign exactly the fields emitted by an existing advertising renderer.
 *
 * @param string $target_type Target kind.
 * @param int    $target Target post ID.
 * @param int    $interval Browse interval.
 * @param mixed  $repeat Repeat count or infinite.
 * @param mixed  $ad Ad identifier.
 * @param string $rotation Rotation identifier.
 * @param string $sequence Ad sequence.
 * @param string $display Display kind.
 * @param string $template Template name.
 * @param string $closing Close marker.
 * @return string Configuration-bound nonce.
 */
function adzworld_ad_request_token( $target_type, $target, $interval, $repeat, $ad = '', $rotation = '', $sequence = '', $display = '', $template = '', $closing = '' ) {
	$action = adzworld_ad_request_action(
		array(
			'target_type'            => $target_type,
			'target'                 => $target,
			'ad_visibility_interval' => $interval,
			'repeat_times'           => $repeat,
			'ad_to_serve'            => $ad,
			'rotations_id'           => $rotation,
			'sequence'               => $sequence,
			'display_type'           => $display,
			'adz_template'           => $template,
			'close_adz'              => $closing,
		)
	);
	if ( is_wp_error( $action ) ) {
		wp_die( esc_html( $action->get_error_message() ) );
	}
	return wp_create_nonce( $action );
}

/**
 * Verify that a public request has not changed its rendered configuration.
 *
 * This does not turn a public nonce into administrator permission. It limits
 * public operations to the configuration the server actually supplied.
 *
 * @param array $fields Unslashed request fields.
 * @return bool Whether the configuration signature is valid.
 */
function adzworld_verify_ad_context( $fields ) {
	$action = adzworld_ad_request_action( $fields );
	return ! is_wp_error( $action ) && isset( $fields['adzworld_context'] ) && is_string( $fields['adzworld_context'] )
		&& false !== wp_verify_nonce( $fields['adzworld_context'], $action );
}
