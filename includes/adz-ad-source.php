<?php
/**
 * Local ad source.
 *
 * In 1.0.8 every ad was fetched over HTTP from adz.world. That network has been
 * gone for years, so each fetch returned nothing, $ad_text stayed empty, no
 * template was ever loaded and the content gate silently never engaged.
 *
 * Ads now come from the site's own adz_ad posts. The functions here return the
 * same shape the old REST calls did, so the serving code downstream is
 * unchanged in structure.
 *
 * @package adz.world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve an ad reference to a local post ID.
 *
 * Accepts either a local adz_ad post ID or a legacy network_ad_id left over
 * from when the site was registered with adz.world, so sequences saved under
 * the old scheme keep working.
 *
 * @param mixed $reference Local post ID or legacy network ad ID.
 * @return int Local post ID, or 0 when it cannot be resolved.
 */
function adz_resolve_ad_id( $reference ) {
	$reference = trim( (string) $reference );

	if ( '' === $reference || ! is_numeric( $reference ) ) {
		return 0;
	}

	$reference = (int) $reference;

	$post = get_post( $reference );
	if ( $post instanceof WP_Post && 'adz_ad' === $post->post_type ) {
		return $post->ID;
	}

	// Legacy: the reference is an ID the old network assigned.
	$legacy = get_posts(
		array(
			'post_type'        => 'adz_ad',
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => false,
			'meta_query'       => array(
				array(
					'key'   => 'network_ad_id',
					'value' => $reference,
				),
			),
		)
	);

	return empty( $legacy ) ? 0 : (int) $legacy[0];
}

/**
 * The displayable content of an ad.
 *
 * @param mixed $reference Local post ID or legacy network ad ID.
 * @return string Ad markup, or '' when the ad does not exist or is not published.
 */
function adz_get_ad_content( $reference ) {
	$ad_id = adz_resolve_ad_id( $reference );

	if ( ! $ad_id ) {
		return '';
	}

	$ad = get_post( $ad_id );

	if ( ! $ad instanceof WP_Post || 'publish' !== $ad->post_status ) {
		return '';
	}

	return (string) $ad->post_content;
}

/**
 * Fetch an ad in the shape the old network response had.
 *
 * The serving code reads $response['body'], json_decodes it and takes
 * element [0]'s ->content. Returning that same structure keeps the call sites
 * honest while the transport becomes a local lookup.
 *
 * @param mixed $reference Local post ID or legacy network ad ID.
 * @return array|false Response-shaped array, or false when there is no such ad.
 */
function adz_fetch_ad( $reference ) {
	$content = adz_get_ad_content( $reference );

	if ( '' === $content ) {
		return false;
	}

	return array(
		'body' => wp_json_encode(
			array(
				array(
					'ID'      => adz_resolve_ad_id( $reference ),
					'content' => $content,
				),
			)
		),
	);
}

/**
 * Every published ad on this site, newest first.
 *
 * @return int[] Post IDs.
 */
function adz_get_all_ad_ids() {
	$ids = get_posts(
		array(
			'post_type'        => 'adz_ad',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		)
	);

	return array_map( 'intval', (array) $ids );
}

/**
 * Pick the next ad to serve from a sequence.
 *
 * Rotation state is kept per sequence: each ad is served once before the
 * sequence starts over, so a visitor is not shown the same ad repeatedly while
 * others go unseen.
 *
 * @param int[]  $sequence  Candidate ad IDs, in order.
 * @param string $state_key Rotation identifier. Namespaced before use as an
 *                          option name, so a request-supplied value can never
 *                          address an option outside this plugin's own.
 * @return int Ad ID to serve, or 0 when the sequence holds no usable ad.
 */
function adz_next_ad_in_sequence( $sequence, $state_key ) {
	$state_key = adz_rotation_option_name( $state_key );

	$sequence = array_values( array_filter( array_map( 'adz_resolve_ad_id', (array) $sequence ) ) );

	if ( empty( $sequence ) ) {
		return 0;
	}

	$state = get_option( $state_key );

	if ( ! is_array( $state ) || empty( $state['un_served'] ) ) {
		$state = array(
			'served'    => array(),
			'un_served' => $sequence,
		);
	}

	// Drop anything that has since been unpublished or deleted.
	$state['un_served'] = array_values( array_intersect( $state['un_served'], $sequence ) );

	if ( empty( $state['un_served'] ) ) {
		$state['un_served'] = $sequence;
		$state['served']    = array();
	}

	$next = (int) array_shift( $state['un_served'] );

	$state['served'][] = $next;

	update_option( $state_key, $state, false );

	return $next;
}

/**
 * Validate a template filename supplied by the request.
 *
 * 1.0.8 passed $_POST['adz_template'] straight into require_once() after only
 * sanitize_text_field(), which leaves "../" intact. Any visitor could therefore
 * make the plugin include an arbitrary file. Templates are now matched against
 * the files that actually exist in adz-templates/, so nothing outside that
 * directory can be reached.
 *
 * @param string $template Requested template filename.
 * @return string Validated filename, or '' when it is not a real template.
 */
function adz_safe_template( $template ) {
	$template = basename( trim( (string) $template ) );

	if ( '' === $template || '.php' !== substr( $template, -4 ) ) {
		return '';
	}

	$available = adz_available_templates();

	return in_array( $template, $available, true ) ? $template : '';
}

/**
 * Template files shipped in adz-templates/.
 *
 * @return string[] Filenames.
 */
function adz_available_templates() {
	static $templates = null;

	if ( null !== $templates ) {
		return $templates;
	}

	$found = glob( ADZ_WORLD . 'adz-templates/*.php' );

	$templates = $found ? array_map( 'basename', $found ) : array();

	return $templates;
}

/**
 * Build the option name holding a rotation's state.
 *
 * The AJAX endpoint is public (wp_ajax_nopriv), and 1.0.8 passed
 * $_POST['rotations_id'] straight into get_option() and update_option() as the
 * option NAME. Any visitor could therefore read or overwrite any option in the
 * site -- siteurl, admin_email, stored keys. Sanitising the value does not help,
 * because the problem is which option is addressed, not what it contains.
 *
 * Every rotation key is now forced inside the adz_rot_ namespace, so a crafted
 * value can only ever touch this plugin's own rotation state.
 *
 * @param string $state_key Caller-supplied rotation identifier.
 * @return string Safe option name.
 */
function adz_rotation_option_name( $state_key ) {
	$state_key = sanitize_key( (string) $state_key );

	if ( '' === $state_key ) {
		$state_key = 'default';
	}

	// Keep the option name within the 191-char index limit.
	if ( strlen( $state_key ) > 150 ) {
		$state_key = substr( $state_key, 0, 100 ) . md5( $state_key );
	}

	return 'adz_rot_' . $state_key;
}
