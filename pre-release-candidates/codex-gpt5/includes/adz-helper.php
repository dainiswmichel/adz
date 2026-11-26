<?php
/**
 * Helper functions for serving adz.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/** Site-controlled text shown by the information control on adz panels. */
const ADZWORLD_NOTICE_OPTION = 'adzworld_notice_text';

/**
 * Default information-panel text.
 *
 * Each entry is one paragraph. A paragraph beginning with a pilcrow and an
 * em dash has its label emphasized when rendered.
 *
 * @return string
 */
function adzworld_notice_default_text() {
	$paragraphs = array(
		__( '¶ 1. Before you say anything — We do not know who you are and we collect nothing. No session is started, no cookie is set, and nothing about you is written down. You are shown adz specified by adzadmins, and that is all that happens.', 'adz-world' ),
		__( 'Most people using modern technology have, often unknowingly, agreed to terms that permit extensive surveillance and tracking by Big Tech. Governments also engage in digital surveillance, sometimes without people’s knowledge or consent.', 'adz-world' ),
		__( 'Every visit to this site costs its owner money, whether the visitor is a human being, a bot, an artificial intelligence system, or a web crawler. Advertising helps pay for the content and services that people enjoy without charge. Site owners also need to protect themselves against malicious or abusive consumption of their resources. But neither the cost of providing a website nor the need to protect it justifies surveilling the people who visit.', 'adz-world' ),
		__( '¶ 2. What we cannot speak for — Some advertising systems follow people across websites and apps and collect information about their activity. They use that information to build profiles, predict interests, and decide which ads to show.', 'adz-world' ),
		__( 'Rather than simply asking people what they want, these systems watch what people do, track and log their behavior, guess what they might want, and attempt to manipulate their decisions.', 'adz-world' ),
		__( 'A slot on this page may be filled by a third-party advertising network, such as Google AdSense, Google Ad Manager, Taboola, Outbrain, Criteo or Amazon Advertising. These networks may employ Surveillance Advertising Display Systems (S.A.D.S.). They operate under their own terms and arrangements with the site owner, not ours. We cannot control their data collection, but we can tell you when their advertising is present.', 'adz-world' ),
		__( '¶ 3. When you tell us what you want — Permission-based advertising works differently. Rather than watching your behavior and guessing what you might want, we simply let you tell us.', 'adz-world' ),
		__( 'Typing what you are looking for, or rating adz, starts a session. The session consists of a timestamped identifier and the words or preferences you chose. These are used to select relevant adz, not to construct a behavioral profile. The session does not require your name, email or address, is not joined to other personal information, and ends when you close your browser.', 'adz-world' ),
		__( '¶ 4. If you make a profile — Your advertising preferences are kept with your account on this site. Rather than allowing advertisers to build a profile about you through surveillance, you create and control your own profile.', 'adz-world' ),
		__( 'The plugin uses the preferences you save to serve ads relevant to your stated interests.', 'adz-world' ),
		__( 'Your preferences belong under your control. You can see them, change them, and delete them.', 'adz-world' ),
		__( '¶ 5. Summary — You can choose what kinds of ads you’d like to see.', 'adz-world' ),
	);

	return implode( "\n\n", $paragraphs );
}

/**
 * Information-panel text configured by this site.
 *
 * @return string
 */
function adzworld_notice_text() {
	$stored = get_option( ADZWORLD_NOTICE_OPTION, null );

	return is_string( $stored ) ? $stored : adzworld_notice_default_text();
}

/**
 * Split site-controlled information text into paragraphs.
 *
 * @return array<int,string>
 */
function adzworld_notice_paragraphs() {
	$text       = str_replace( array( "\r\n", "\r" ), "\n", adzworld_notice_text() );
	$paragraphs = preg_split( '/\n[\t ]*\n+/', trim( $text ) );

	if ( ! is_array( $paragraphs ) ) {
		return array();
	}

	return array_values(
		array_filter(
			array_map( 'trim', $paragraphs ),
			'strlen'
		)
	);
}

/**
 * Build a request URL for the legacy network ad endpoint.
 *
 * @param array  $registration Saved publisher registration.
 * @param string $referral_code Saved referral code, or an empty string.
 * @param mixed  $ad_id         Selected publisher advertisement identifier.
 * @param string $pool          Publisher or network ad pool.
 * @return string Request URL.
 */
function adzworld_legacy_network_ad_url( $registration, $referral_code, $ad_id, $pool ) {
	global $adzworld_network_base_url;

	return add_query_arg(
		urlencode_deep(
			array(
				'id'            => (string) $registration['ID'],
				'token'         => $registration['post_token'],
				'nettoken'      => $registration['network_token'],
				'referral_code' => $referral_code,
				'publisher_ad'  => (string) $ad_id,
				'ad_from'       => $pool,
			)
		),
		$adzworld_network_base_url . 'wp-json/adz_server/v1/ads'
	);
}

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

	if ( false === adzworld_browser_state_get( 'legacy_ad_timer' ) ) {
		adzworld_browser_state_set( 'legacy_ad_timer', time() + (int) $ad_visibility_interval );
	}

	adzworld_legacy_request(
		array(
			'mode'     => 'browse',
			'seconds'  => absint( $ad_visibility ),
			'category' => is_category(),
			'request'  => adzworld_legacy_request_fields( $target_type, $target, $ad_visibility_interval, $repeat_times, $ad_to_serve, $rotations_id, $sequence, $display_type, $adz_template ),
			'close'    => adzworld_legacy_close_fields( $target_type, $target, $ad_visibility_interval, $repeat_times, $rotations_id ),
		)
	);
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

	if ( false === adzworld_browser_state_get( 'legacy_ad_timer' ) ) {
		adzworld_browser_state_set( 'legacy_ad_timer', time() + (int) $ad_visibility_interval );
	}
	?>
	<div id="show_overlay" class="adz_overlay">
		<div class="adz_popup">

			<div class="content"> </div>
		</div>
	</div>

	<?php
	adzworld_legacy_request(
		array(
			'mode'    => 'browse-popup',
			'seconds' => absint( $ad_visibility ),
			'request' => adzworld_legacy_request_fields( $target_type, $target, $ad_visibility_interval, $repeat_times, $ad_to_serve, $rotations_id, $sequence, $display_type, $adz_template, true ),
			'close'   => adzworld_legacy_close_fields( $target_type, $target, $ad_visibility_interval, $repeat_times, $rotations_id, true ),
		)
	);
}

/**
 * Queue one legacy timed or gated adz request for assets/legacy.js.
 *
 * The settings travel as JSON through wp_add_inline_script(), so no script
 * is printed by PHP. See assets/legacy.js for every setting.
 *
 * @param array $settings Request settings: mode, seconds, request, close, and optional category or content.
 * @return void
 */
function adzworld_legacy_request( $settings ) {
	$settings = array_merge(
		array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'continue' => __( 'Continue >>', 'adz-world' ),
		),
		$settings
	);
	wp_enqueue_script( 'adzworld-legacy' );
	wp_add_inline_script(
		'adzworld-legacy',
		'window.adzworldLegacyRequests = window.adzworldLegacyRequests || []; window.adzworldLegacyRequests.push('
			. wp_json_encode( $settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ');',
		'before'
	);
}

/**
 * POST fields that ask the endpoint for one legacy adz.
 *
 * @param string $target_type            Target kind.
 * @param mixed  $target                 Target identifier.
 * @param mixed  $ad_visibility_interval Minimum seconds between adz.
 * @param mixed  $repeat_times           Repeat count or infinite.
 * @param mixed  $ad_to_serve            Selected adz identifier.
 * @param string $rotations_id           Saved rotation identifier.
 * @param string $sequence               Configured adz sequence.
 * @param string $display_type           Presentation kind.
 * @param string $adz_template           Template filename.
 * @param bool   $popup                  Whether the adz opens in the overlay.
 * @return array<string,string>
 */
function adzworld_legacy_request_fields( $target_type, $target, $ad_visibility_interval, $repeat_times, $ad_to_serve, $rotations_id, $sequence, $display_type, $adz_template, $popup = false ) {
	$fields = array(
		'action'                 => 'adzworld_get_advertise_content',
		'target_type'            => (string) $target_type,
		'target'                 => (string) $target,
		'ad_visibility_interval' => (string) $ad_visibility_interval,
		'ad_to_serve'            => (string) $ad_to_serve,
		'rotations_id'           => (string) $rotations_id,
		'sequence'               => (string) $sequence,
		'display_type'           => (string) $display_type,
		'adz_template'           => (string) $adz_template,
		'repeat_times'           => (string) $repeat_times,
		'adzworld_context'       => adzworld_ad_request_token( $target_type, $target, $ad_visibility_interval, $repeat_times, $ad_to_serve, $rotations_id, $sequence, $display_type, $adz_template ),
		'adz_nonce'              => wp_create_nonce( 'adzdotworld' ),
	);
	if ( $popup ) {
		$fields['show_popup'] = 'true';
	}

	return $fields;
}

/**
 * POST fields sent when the reader continues past a legacy adz.
 *
 * @param string $target_type            Target kind.
 * @param mixed  $target                 Target identifier.
 * @param mixed  $ad_visibility_interval Minimum seconds between adz.
 * @param mixed  $repeat_times           Repeat count or infinite.
 * @param string $rotations_id           Saved rotation identifier.
 * @param bool   $popup                  Whether the adz opened in the overlay.
 * @return array<string,string>
 */
function adzworld_legacy_close_fields( $target_type, $target, $ad_visibility_interval, $repeat_times, $rotations_id, $popup = false ) {
	$fields = array(
		'action'                 => 'adzworld_get_advertise_content',
		'target_type'            => (string) $target_type,
		'target'                 => (string) $target,
		'ad_visibility_interval' => (string) $ad_visibility_interval,
		'repeat_times'           => (string) $repeat_times,
		'close_adz'              => 'true',
		'rotations_id'           => (string) $rotations_id,
		'adzworld_context'       => adzworld_ad_request_token( $target_type, $target, $ad_visibility_interval, $repeat_times, '', $rotations_id, '', '', '', 'true' ),
		'adz_nonce'              => wp_create_nonce( 'adzdotworld' ),
	);
	if ( $popup ) {
		$fields['show_popup'] = 'true';
	}

	return $fields;
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
		return new WP_Error( 'adzworld_ad_unavailable', __( 'The selected local adzad is not publicly available.', 'adz-world' ) );
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
 * The WordPress account on this site, when one is signed in.
 *
 * A reader who is not signed in is unknown. This function does not call the
 * network and does not read the visitor address.
 *
 * @return int|false Account identifier, or false when not logged in.
 */
function adzworld_logged_in() {
	return is_user_logged_in() ? get_current_user_id() : false;
}

/**
 * Return the local identity used for view-frequency records.
 *
 * A passive anonymous reader has no identity. The first stated preference or
 * rating creates a short-lived browser session with a Michel Time Stamp. A
 * signed-in reader uses the local WordPress account. This value remains on
 * this WordPress installation and is never sent to an advertising provider.
 *
 * @return string Local identity, or an empty string for an unknown reader.
 */
function adzworld_view_identity() {
	$user_id = adzworld_logged_in();
	if ( $user_id ) {
		return 'user:' . absint( $user_id );
	}

	if ( ! function_exists( 'adzworld_preference_session_id' ) ) {
		return '';
	}

	$session_id = adzworld_preference_session_id();

	return '' === $session_id ? '' : 'session:' . $session_id;
}

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
 * own adz_ad posts.
 *
 * 1.0.8 called adz-world unconditionally, on page loads, sending visitor IP
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
	return '' !== adzworld_network_base_url();
}

/**
 * Base URL of the adz network this site talks to.
 *
 * Filterable so a site can point at a different network.
 *
 * @return string Trailing-slashed URL, or '' when none is configured.
 */
function adzworld_network_base_url() {
	$url = apply_filters( 'adzworld_ad_network_base_url', adzworld_home_url() );

	return $url ? trailingslashit( esc_url_raw( $url ) ) : '';
}

/**
 * The saved lines for one template, including those saved under its old name.
 *
 * Settings are stored in an option keyed by the template's filename. A file
 * that has been renamed would leave the adzadmin's header and footer text
 * stranded under the old key, so the old key is read when the current one
 * holds nothing. Nothing is rewritten: the old option is left where it is and
 * is simply still honoured.
 *
 * @since 1.1.1
 *
 * @param string $template Current template filename.
 * @return array Saved lines, or an empty array when there are none.
 */
function adzworld_template_saved_lines( $template ) {
	$template = adzworld_template_current_name( (string) $template );
	$settings = adzworld_template_settings( get_option( 'template_' . $template ) );

	if ( ! empty( $settings ) ) {
		return $settings;
	}

	foreach ( adzworld_template_renames() as $was => $now ) {
		if ( $now !== $template ) {
			continue;
		}

		$settings = adzworld_template_settings( get_option( 'template_' . $was ) );

		if ( ! empty( $settings ) ) {
			return $settings;
		}
	}

	return array();
}

/**
 * Template filenames that have been renamed, old name to current name.
 *
 * A rotation, a shortcode or a saved option can still name a template by the
 * filename it carried when it was chosen. Renaming a file on disk would make
 * every one of those resolve to nothing and the site would print no adz, so
 * the old names keep working and point at the file that replaced them.
 *
 * @since 1.1.1
 *
 * @return array<string,string> Old filename to current filename.
 */
function adzworld_template_renames() {
	return array(
		'template1.php' => 'countdown-layout.php',
	);
}

/**
 * Translate a stored template filename to the one that exists now.
 *
 * @since 1.1.1
 *
 * @param string $template Stored filename.
 * @return string Current filename, or the input when it was never renamed.
 */
function adzworld_template_current_name( $template ) {
	$renames = adzworld_template_renames();

	return isset( $renames[ $template ] ) ? $renames[ $template ] : $template;
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
	$template = adzworld_template_current_name( trim( $template ) );

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
 * Build a privacy-preserving key for one local viewer's daily view record.
 *
 * WordPress salts make the digest specific to this installation, and the date
 * naturally separates daily records.
 *
 * @param string $viewer_identity Local account or engaged-session identity.
 * @param int    $target_id Target post identifier.
 * @param string $target_type Display context.
 * @return string Empty when the reader has no local identity; otherwise a transient key.
 */
function adzworld_view_key( $viewer_identity, $target_id, $target_type ) {
	$viewer_identity = sanitize_text_field( (string) $viewer_identity );
	if ( '' === $viewer_identity ) {
		return '';
	}

	$identity = wp_json_encode(
		array(
			$viewer_identity,
			absint( $target_id ),
			sanitize_key( $target_type ),
			current_time( 'Y-m-d' ),
		)
	);

	return 'adzworld_view_' . hash_hmac( 'sha256', $identity, wp_salt( 'nonce' ) );
}

/**
 * Look up today's view record for a local viewer against one target.
 *
 * @param string $viewer_identity Local account or engaged-session identity.
 * @param int    $target_id Target post identifier.
 * @param string $target_type Display context.
 * @return object|null View record, or null when no view was recorded today.
 */
function adzworld_get_view_row( $viewer_identity, $target_id, $target_type = '' ) {
	$key = adzworld_view_key( $viewer_identity, $target_id, $target_type );
	if ( '' === $key ) {
		return null;
	}

	$record = get_transient( $key );
	return is_array( $record ) ? (object) $record : null;
}

/**
 * Record a completed ad view until the end of the current site-local day.
 *
 * @param string $viewer_identity Local account or engaged-session identity.
 * @param int    $target_id Target post identifier.
 * @param string $target_type Display context.
 * @return bool Whether the record was stored.
 */
function adzworld_record_view( $viewer_identity, $target_id, $target_type ) {
	$key = adzworld_view_key( $viewer_identity, $target_id, $target_type );
	if ( '' === $key ) {
		return true;
	}

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
