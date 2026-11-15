<?php
/**
 * Existing publisher ad-network connections.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ADZWORLD_NETWORK_ROW_MAX' ) ) {
	define( 'ADZWORLD_NETWORK_ROW_MAX', 200 );
}

if ( ! defined( 'ADZWORLD_NETWORK_PARAM_MAX' ) ) {
	define( 'ADZWORLD_NETWORK_PARAM_MAX', 30 );
}

/**
 * Width or height rule shared by networks that ask for a creative size.
 *
 * @param string $label Field label.
 * @return array<string,mixed>
 */
function adzworld_network_dimension_rule( $label ) {
	return array(
		'label'   => $label,
		'pattern' => '/^[0-9]{2,4}$/D',
		'min'     => 50,
		'max'     => 1200,
	);
}

/**
 * Ad networks with an official unit that can occupy a rotation slot.
 *
 * Every entry is a rotation source. An empty identifier stays off.
 * Pop and push networks are not entries.
 *
 * @return array<string,array<string,mixed>>
 */
function adzworld_network_catalog() {
	return array(
		'adsense'    => array(
			'label'   => 'Google AdSense',
			'host'    => 'pagead2.googlesyndication.com',
			'signup'  => 'https://adsense.google.com/start/',
			'setup'   => 'https://support.google.com/adsense/answer/9274025',
			'api'     => 'https://developers.google.com/adsense/management/getting_started',
			'terms'   => 'https://www.google.com/adsense/terms/',
			'privacy' => 'https://policies.google.com/privacy',
			'note'    => __( 'Uses the official responsive ad unit with this site owner’s publisher and unit identifiers.', 'adz-world' ),
			'fields'  => array(
				'client' => array(
					'label'   => __( 'Publisher ID', 'adz-world' ),
					'pattern' => '/^ca-pub-[0-9]{10,22}$/D',
				),
				'slot'   => array(
					'label'   => __( 'Ad unit ID', 'adz-world' ),
					'pattern' => '/^[0-9]{6,20}$/D',
				),
			),
		),
		'gam'        => array(
			'label'   => 'Google Ad Manager',
			'host'    => 'securepubads.g.doubleclick.net',
			'signup'  => 'https://admanager.google.com/',
			'setup'   => 'https://developers.google.com/publisher-tag/guides/get-started',
			'api'     => 'https://developers.google.com/ad-manager/api/start',
			'terms'   => 'https://admanager.google.com/home/terms/',
			'privacy' => 'https://policies.google.com/privacy',
			'note'    => __( 'Uses Google Publisher Tag with this site owner’s ad unit path and creative dimensions.', 'adz-world' ),
			'fields'  => array(
				'path'   => array(
					'label'   => __( 'Ad unit path', 'adz-world' ),
					'pattern' => '#^/[0-9]{3,15}(/[A-Za-z0-9._~-]{1,64}){1,6}$#D',
				),
				'width'  => array(
					'label'   => __( 'Width', 'adz-world' ),
					'pattern' => '/^[0-9]{2,4}$/D',
					'min'     => 50,
					'max'     => 1200,
				),
				'height' => array(
					'label'   => __( 'Height', 'adz-world' ),
					'pattern' => '/^[0-9]{2,4}$/D',
					'min'     => 50,
					'max'     => 1200,
				),
			),
		),
		'taboola'    => array(
			'label'   => 'Taboola',
			'host'    => 'cdn.taboola.com',
			'signup'  => 'https://www.taboola.com/publishers/',
			'setup'   => 'https://discover.taboola.com/lp-pub-onboarding-en/',
			'terms'   => 'https://www.taboola.com/policies/publisher-terms-and-conditions',
			'privacy' => 'https://www.taboola.com/policies/privacy-policy',
			'note'    => __( 'Uses the publisher’s existing Taboola widget configuration and loads it only when its rotation slot is shown.', 'adz-world' ),
			'fields'  => array(
				'publisher' => array(
					'label'   => __( 'Publisher ID', 'adz-world' ),
					'pattern' => '/^[a-z0-9][a-z0-9-]{1,48}$/D',
				),
				'mode'      => array(
					'label'   => __( 'Widget mode', 'adz-world' ),
					'choices' => array( 'thumbnails-a', 'alternating-thumbnails-a' ),
				),
				'placement' => array(
					'label'   => __( 'Placement name', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9][A-Za-z0-9 _-]{0,79}$/D',
				),
			),
		),
		'outbrain'   => array(
			'label'   => 'Teads (Outbrain widget)',
			'host'    => 'widgets.outbrain.com',
			'signup'  => 'https://www.outbrain.com/publishers/',
			'setup'   => 'https://developer.outbrain.com/outbrain-javascript-implementation-guide/',
			'terms'   => 'https://www.outbrain.com/legal/terms-of-use/',
			'privacy' => 'https://www.outbrain.com/privacy/',
			'note'    => __( 'Uses the Outbrain widget script and the widget ID from the Teads publisher account. Teads inRead is the next connection.', 'adz-world' ),
			'fields'  => array(
				'widget' => array(
					'label'   => __( 'Widget ID', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9][A-Za-z0-9_-]{0,39}$/D',
				),
			),
		),
		'teads'      => array(
			'label'   => 'Teads inRead',
			'host'    => 'a.teads.tv',
			'signup'  => 'https://www.teads.com/publishers/',
			'setup'   => 'https://support.teads.tv/support/solutions/articles/36000019056-integrating-teads-formats-without-an-ad-server-direct-integration-',
			'terms'   => 'https://www.teads.com/teads-website-terms-and-conditions/',
			'privacy' => 'https://www.teads.com/privacy-policy/',
			'note'    => __( 'Loads the official Teads page tag for the page ID assigned in Teads inventory. The tag is not built from a guessed ID.', 'adz-world' ),
			'fields'  => array(
				'page' => array(
					'label'   => __( 'Page ID', 'adz-world' ),
					'pattern' => '/^[0-9]{3,12}$/D',
				),
			),
		),
		'medianet'   => array(
			'label'   => 'Media.net',
			'host'    => 'contextual.media.net',
			'signup'  => 'https://www.media.net/publishers',
			'setup'   => 'https://www.media.net/publishers',
			'terms'   => 'https://www.media.net/terms',
			'privacy' => 'https://www.media.net/privacy-policy',
			'note'    => __( 'Loads dmedianet.js with this publisher’s customer ID and calls loadTag with the placement ID, version, and size from the Media.net tag.', 'adz-world' ),
			'fields'  => array(
				'cid'     => array(
					'label'   => __( 'Customer ID', 'adz-world' ),
					'pattern' => '/^8CU[A-Z0-9]{6,16}$/D',
					'hint'    => __( 'The cid value in the Media.net tag. It starts with 8CU.', 'adz-world' ),
				),
				'crid'    => array(
					'label'   => __( 'Placement ID', 'adz-world' ),
					'pattern' => '/^[0-9]{5,15}$/D',
				),
				'version' => array(
					'label'   => __( 'Version ID', 'adz-world' ),
					'pattern' => '/^[0-9]{4,12}$/D',
					'hint'    => __( 'The numeric medianet_versionId value from the same tag.', 'adz-world' ),
				),
				'width'   => adzworld_network_dimension_rule( __( 'Width', 'adz-world' ) ),
				'height'  => adzworld_network_dimension_rule( __( 'Height', 'adz-world' ) ),
			),
		),
		'amazonaps'  => array(
			'label'   => 'Amazon Publisher Services',
			'host'    => 'c.amazon-adsystem.com',
			'signup'  => 'https://aps.amazon.com/aps/index.html',
			'setup'   => 'https://aps.amazon.com/aps/solutions-for-publishers/',
			'terms'   => 'https://aps.amazon.com/aps/terms-of-service.html',
			'privacy' => 'https://www.amazon.com/gp/help/customer/display.html?nodeId=GX7NJQ4ZB8MHFRNJ',
			'note'    => __( 'Loads apstag.js and requests a bid for the publisher ID and slot name already created in the APS console. No bidder key is invented.', 'adz-world' ),
			'fields'  => array(
				'pub'      => array(
					'label'   => __( 'Publisher ID', 'adz-world' ),
					'pattern' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/Di',
				),
				'slotname' => array(
					'label'   => __( 'Slot name', 'adz-world' ),
					'pattern' => '#^[A-Za-z0-9][A-Za-z0-9._~/-]{0,120}$#D',
				),
				'width'    => adzworld_network_dimension_rule( __( 'Width', 'adz-world' ) ),
				'height'   => adzworld_network_dimension_rule( __( 'Height', 'adz-world' ) ),
			),
		),
		'ezoic'      => array(
			'label'   => 'Ezoic',
			'host'    => 'www.ezojs.com',
			'signup'  => 'https://www.ezoic.com/',
			'setup'   => 'https://docs.ezoic.com/docs/ezoicads/implementation/',
			'terms'   => 'https://www.ezoic.com/terms/',
			'privacy' => 'https://www.ezoic.com/privacy/',
			'note'    => __( 'Loads the Ezoic privacy scripts and sa.min.js, then calls showAds for the placeholder ID created in the Ezoic dashboard.', 'adz-world' ),
			'fields'  => array(
				'placeholder' => array(
					'label'   => __( 'Placeholder ID', 'adz-world' ),
					'pattern' => '/^[0-9]{1,6}$/D',
					'hint'    => __( 'The number in ezoic-pub-ad-placeholder. Example: 101.', 'adz-world' ),
				),
			),
		),
		'mediavine'  => array(
			'label'   => 'Mediavine',
			'host'    => 'scripts.mediavine.com',
			'signup'  => 'https://www.mediavine.com/apply/',
			'setup'   => 'https://help.mediavine.com/how-to-manually-place-ads-in-your-content-with-content-hints',
			'terms'   => 'https://www.mediavine.com/terms-of-service/',
			'privacy' => 'https://www.mediavine.com/privacy-policy/',
			'note'    => __( 'Loads the script wrapper for the Site ID from Mediavine Ad Setup and places one official content hint in this slot.', 'adz-world' ),
			'fields'  => array(
				'site' => array(
					'label'   => __( 'Site ID', 'adz-world' ),
					'pattern' => '/^[a-z0-9][a-z0-9-]{0,80}$/D',
					'hint'    => __( 'The file name in scripts.mediavine.com/tags/SiteID.js, without .js.', 'adz-world' ),
				),
				'hint' => array(
					'label'   => __( 'Content hint', 'adz-world' ),
					'choices' => array( 'content_hint', 'content_desktop_hint', 'content_mobile_hint' ),
				),
			),
		),
		'raptive'    => array(
			'label'   => 'Raptive',
			'host'    => 'ads.adthrive.com',
			'signup'  => 'https://raptive.com/creators/',
			'setup'   => 'https://help.raptive.com/hc/en-us/articles/44700192266651-Self-Install-Overview-Non-WordPress-sites',
			'terms'   => 'https://raptive.com/terms/',
			'privacy' => 'https://raptive.com/privacy/',
			'note'    => __( 'Loads ads.min.js for the Raptive site ID and marks this slot with the official adthrive-ad element.', 'adz-world' ),
			'fields'  => array(
				'site'     => array(
					'label'   => __( 'Site ID', 'adz-world' ),
					'pattern' => '/^[a-f0-9]{24}$/D',
					'hint'    => __( 'The 24-character id in ads.adthrive.com/sites/SITEID/ads.min.js.', 'adz-world' ),
				),
				'location' => array(
					'label'   => __( 'Location', 'adz-world' ),
					'choices' => array( 'Content', 'Header', 'Sidebar', 'Below_Post' ),
				),
				'sequence' => array(
					'label'   => __( 'Sequence', 'adz-world' ),
					'pattern' => '/^[1-9]$/D',
					'hint'    => __( 'A number from 1 to 9. The element id is AdThrive_Location_N.', 'adz-world' ),
				),
			),
		),
		'monumetric' => array(
			'label'   => 'Monumetric',
			'host'    => 'monu.delivery',
			'signup'  => 'https://www.monumetric.com/join/',
			'setup'   => 'https://www.monumetric.com/approved-wordpress-caching-plugins/',
			'terms'   => 'https://www.monumetric.com/terms/',
			'privacy' => 'https://www.monumetric.com/privacy-policy/',
			'note'    => __( 'Loads the delivery script Monumetric assigned to this site. The path is copied from that script. It is not generated here.', 'adz-world' ),
			'fields'  => array(
				'script' => array(
					'label'   => __( 'Delivery script path', 'adz-world' ),
					'pattern' => '#^[a-z0-9]{1,4}/[a-z0-9]{1,4}/[A-Za-z0-9-]{8,80}\.js$#D',
					'hint'    => __( 'The path after monu.delivery/site/. Example shape: 6/e/file-name.js.', 'adz-world' ),
				),
			),
		),
		'mgid'       => array(
			'label'   => 'MGID',
			'host'    => 'jsc.mgid.com',
			'signup'  => 'https://www.mgid.com/publishers',
			'setup'   => 'https://help.mgid.com/mgid-widget-code-direct-installation',
			'terms'   => 'https://www.mgid.com/terms-and-conditions',
			'privacy' => 'https://www.mgid.com/privacy-policy',
			'note'    => __( 'Loads the MGID site script and the official widget element for the website id and widget id from the MGID dashboard.', 'adz-world' ),
			'fields'  => array(
				'website' => array(
					'label'   => __( 'Website ID', 'adz-world' ),
					'pattern' => '/^[A-Za-z0-9][A-Za-z0-9._-]{0,64}$/D',
					'hint'    => __( 'The value between /site/ and .js in the MGID head script.', 'adz-world' ),
				),
				'widget'  => array(
					'label'   => __( 'Widget ID', 'adz-world' ),
					'pattern' => '/^[0-9]{4,12}$/D',
				),
			),
		),
		'revcontent' => array(
			'label'   => 'RevContent',
			'host'    => 'assets.revcontent.com',
			'signup'  => 'https://www.revcontent.com/publishers',
			'setup'   => 'https://help.revcontent.com/',
			'terms'   => 'https://www.revcontent.com/publisher-terms',
			'privacy' => 'https://www.revcontent.com/privacy-policy',
			'note'    => __( 'Places the official RevContent widget element and loads delivery.js for the widget ID RevContent assigned.', 'adz-world' ),
			'fields'  => array(
				'widget' => array(
					'label'   => __( 'Widget ID', 'adz-world' ),
					'pattern' => '/^[0-9]{3,12}$/D',
				),
			),
		),
		'carbon'     => array(
			'label'   => 'Carbon',
			'host'    => 'cdn.carbonads.com',
			'signup'  => 'https://www.buysellads.com/publishers',
			'setup'   => 'https://www.carbonads.net/',
			'terms'   => 'https://www.buysellads.com/about/terms',
			'privacy' => 'https://www.buysellads.com/about/privacy',
			'note'    => __( 'Inserts the official Carbon script inside this slot. The serve and placement values come from the Carbon install code.', 'adz-world' ),
			'fields'  => array(
				'serve'     => array(
					'label'   => __( 'Serve ID', 'adz-world' ),
					'pattern' => '/^[A-Z0-9]{8,10}$/D',
				),
				'placement' => array(
					'label'   => __( 'Placement', 'adz-world' ),
					'pattern' => '/^[a-z0-9][a-z0-9-]{1,40}$/D',
				),
			),
		),
		'criteo'     => array(
			'label'   => 'Criteo',
			'host'    => 'static.criteo.net',
			'signup'  => 'https://www.criteo.com/publishers/',
			'setup'   => 'https://publisher.docs.criteo.com/',
			'terms'   => 'https://www.criteo.com/terms-and-conditions/',
			'privacy' => 'https://www.criteo.com/privacy/',
			'note'    => __( 'Loads the Criteo publisher tag and displays the zone ID inside this slot.', 'adz-world' ),
			'fields'  => array(
				'zone' => array(
					'label'   => __( 'Zone ID', 'adz-world' ),
					'pattern' => '/^[0-9]{4,12}$/D',
				),
			),
		),
		'dianomi'    => array(
			'label'   => 'Dianomi',
			'host'    => 'www.dianomi.com',
			'signup'  => 'https://www.dianomi.com/publishers/',
			'setup'   => 'https://www.dianomi.com/',
			'terms'   => 'https://www.dianomi.com/legal/terms',
			'privacy' => 'https://www.dianomi.com/legal/privacy',
			'note'    => __( 'Places the official Dianomi context element and loads contextfeed.js for the context ID Dianomi assigned.', 'adz-world' ),
			'fields'  => array(
				'context' => array(
					'label'   => __( 'Context ID', 'adz-world' ),
					'pattern' => '/^[0-9]{3,12}$/D',
				),
			),
		),
		'snigel'     => array(
			'label'   => 'Snigel',
			'host'    => 'cdn.snigelweb.com',
			'signup'  => 'https://snigel.com/publishers',
			'setup'   => 'https://snigel.com/',
			'terms'   => 'https://snigel.com/terms',
			'privacy' => 'https://snigel.com/privacy-policy',
			'note'    => __( 'Loads the Snigel AdEngine loader for this site and places the adngin element for the placement name Snigel assigned.', 'adz-world' ),
			'fields'  => array(
				'site'      => array(
					'label'   => __( 'Site key', 'adz-world' ),
					'pattern' => '/^[a-z0-9][a-z0-9.-]{1,80}$/D',
					'hint'    => __( 'The folder in cdn.snigelweb.com/adengine/SITE/loader.js.', 'adz-world' ),
				),
				'placement' => array(
					'label'   => __( 'Placement', 'adz-world' ),
					'pattern' => '/^[a-z0-9][a-z0-9_-]{0,40}$/D',
					'hint'    => __( 'The placement between adngin- and -0.', 'adz-world' ),
				),
			),
		),
		'freestar'   => array(
			'label'   => 'Freestar',
			'host'    => 'a.pub.network',
			'signup'  => 'https://freestar.com/',
			'setup'   => 'https://freestar.com/',
			'terms'   => 'https://freestar.com/terms/',
			'privacy' => 'https://freestar.com/privacy-policy/',
			'note'    => __( 'Loads pubfig.min.js for the Freestar site name and enables the placement name Freestar assigned.', 'adz-world' ),
			'fields'  => array(
				'site'      => array(
					'label'   => __( 'Site name', 'adz-world' ),
					'pattern' => '/^[a-z0-9][a-z0-9-]{1,60}$/D',
					'hint'    => __( 'The folder in a.pub.network/SITE/pubfig.min.js.', 'adz-world' ),
				),
				'placement' => array(
					'label'   => __( 'Placement name', 'adz-world' ),
					'pattern' => '/^[A-Za-z][A-Za-z0-9_-]{0,80}$/D',
				),
				'size'      => array(
					'label'   => __( 'Size', 'adz-world' ),
					'choices' => array( '300x250', '728x90', '320x50', '300x600', '970x90' ),
				),
			),
		),
	);
}

/**
 * Former card-only networks now live in the mount catalog.
 *
 * @return array<string,array<string,string>>
 */
function adzworld_managed_network_catalog() {
	return array();
}

/**
 * Sanitize one field according to its network definition.
 *
 * @param array<string,mixed> $rule Field rule.
 * @param mixed               $raw  Submitted value.
 * @return string
 */
function adzworld_sanitize_network_field( $rule, $raw ) {
	$value = sanitize_text_field( is_scalar( $raw ) ? (string) $raw : '' );

	if ( false !== strpos( $value, '..' ) || false !== strpos( $value, '//' ) || false !== strpos( $value, '\\' ) ) {
		return '';
	}
	if ( isset( $rule['choices'] ) ) {
		return in_array( $value, $rule['choices'], true ) ? $value : '';
	}
	if ( empty( $rule['pattern'] ) || 1 !== preg_match( $rule['pattern'], $value ) ) {
		return '';
	}
	if ( isset( $rule['min'], $rule['max'] ) && ( (int) $value < $rule['min'] || (int) $value > $rule['max'] ) ) {
		return '';
	}

	return $value;
}

/**
 * Whether a stored row id is a legacy catalogue id or a generated row id.
 *
 * @param string $id Row id.
 * @return bool
 */
function adzworld_network_row_id_ok( $id ) {
	if ( isset( adzworld_network_catalog()[ $id ] ) ) {
		return true;
	}
	return 1 === preg_match( '/^r[a-f0-9]{8}$/D', $id );
}

/**
 * Generate a row id that is not already in use.
 *
 * @param array<string,bool> $used Ids already assigned.
 * @return string
 */
function adzworld_network_new_id( $used ) {
	$used  = is_array( $used ) ? $used : array();
	$id    = '';
	$tries = 0;
	do {
		$id = 'r' . bin2hex( random_bytes( 4 ) );
		++$tries;
	} while ( isset( $used[ $id ] ) && $tries < 5 );
	return $id;
}

/**
 * Sanitize a network name or a parameter label.
 *
 * @param mixed $raw Submitted text.
 * @return string
 */
function adzworld_sanitize_network_label( $raw ) {
	$value = sanitize_text_field( is_scalar( $raw ) ? (string) $raw : '' );
	if ( strlen( $value ) > 80 ) {
		$value = substr( $value, 0, 80 );
	}
	return $value;
}

/**
 * Sanitize one administrator-supplied parameter value.
 *
 * API keys are kept as text. Tags and control characters are removed. The
 * value is stored in WordPress and is not printed on the public page.
 *
 * @param mixed $raw Submitted value.
 * @return string
 */
function adzworld_sanitize_network_parameter( $raw ) {
	$value = is_scalar( $raw ) ? (string) $raw : '';
	$value = wp_check_invalid_utf8( $value );
	$value = wp_strip_all_tags( $value );
	$value = preg_replace( '/[\x00-\x1F\x7F]/', '', $value );
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = trim( $value );
	if ( strlen( $value ) > 1000 ) {
		$value = substr( $value, 0, 1000 );
	}
	return $value;
}

/**
 * Sanitize the administrator's network rows.
 *
 * A listed network is enabled only when every official field is valid and the
 * administrator accepts that host. Another network is enabled only when it has
 * a name, at least one parameter value, and the same acceptance. Removal drops
 * the row. The list stops at the rotation slot ceiling.
 *
 * @param mixed $raw Submitted or stored rows.
 * @return array<int,array<string,mixed>>
 */
function adzworld_sanitize_network_rows( $raw ) {
	$raw     = is_array( $raw ) ? $raw : array();
	$catalog = adzworld_network_catalog();
	$clean   = array();
	$seen    = array();

	foreach ( $raw as $posted_id => $row ) {
		if ( count( $clean ) >= ADZWORLD_NETWORK_ROW_MAX || ! is_array( $row ) ) {
			continue;
		}
		if ( isset( $row['remove'] ) && '1' === (string) $row['remove'] ) {
			continue;
		}
		$key = sanitize_key( is_string( $posted_id ) ? $posted_id : '' );
		$id  = adzworld_network_row_id_ok( $key ) ? $key : '';
		if ( '' === $id && isset( $row['id'] ) ) {
			$id = sanitize_key( (string) $row['id'] );
		}
		if ( ! adzworld_network_row_id_ok( $id ) || isset( $seen[ $id ] ) ) {
			continue;
		}
		$preset = isset( $row['preset'] ) ? sanitize_key( (string) $row['preset'] ) : '';
		if ( isset( $catalog[ $id ] ) ) {
			$preset = $id;
		} elseif ( ! isset( $catalog[ $preset ] ) ) {
			$preset = 'custom';
		}
		$default = isset( $catalog[ $preset ] ) ? (string) $catalog[ $preset ]['label'] : '';
		$label   = adzworld_sanitize_network_label( isset( $row['label'] ) ? $row['label'] : $default );
		if ( '' === $label ) {
			$label = $default;
		}
		$fields   = array();
		$params   = array();
		$complete = true;
		if ( isset( $catalog[ $preset ] ) ) {
			$posted = isset( $row['fields'] ) && is_array( $row['fields'] ) ? $row['fields'] : array();
			foreach ( $catalog[ $preset ]['fields'] as $key => $rule ) {
				$fields[ $key ] = adzworld_sanitize_network_field( $rule, isset( $posted[ $key ] ) ? $posted[ $key ] : '' );
				$complete       = $complete && '' !== $fields[ $key ];
			}
		} else {
			$posted   = isset( $row['params'] ) && is_array( $row['params'] ) ? $row['params'] : array();
			$complete = false;
			foreach ( $posted as $param ) {
				if ( count( $params ) >= ADZWORLD_NETWORK_PARAM_MAX || ! is_array( $param ) ) {
					continue;
				}
				if ( isset( $param['remove'] ) && '1' === (string) $param['remove'] ) {
					continue;
				}
				$param_label = adzworld_sanitize_network_label( isset( $param['label'] ) ? $param['label'] : '' );
				$param_value = adzworld_sanitize_network_parameter( isset( $param['value'] ) ? $param['value'] : '' );
				if ( '' === $param_label ) {
					continue;
				}
				$params[] = array(
					'label' => $param_label,
					'value' => $param_value,
				);
				$complete = $complete || '' !== $param_value;
			}
			$complete = $complete && '' !== $label;
		}
		$accepted    = isset( $row['accepted'] ) && '1' === (string) $row['accepted'] ? '1' : '0';
		$enabled     = $complete && '1' === $accepted && isset( $row['enabled'] ) && '1' === (string) $row['enabled'] ? '1' : '0';
		$seen[ $id ] = true;
		$clean[]     = array(
			'id'       => $id,
			'preset'   => $preset,
			'label'    => $label,
			'accepted' => $accepted,
			'enabled'  => $enabled,
			'fields'   => $fields,
			'params'   => $params,
		);
	}

	return $clean;
}

/**
 * Build rows from the single-slot settings saved before rows existed.
 *
 * An empty catalogue slot is left off the screen. The administrator adds it
 * when that network is wanted.
 *
 * @return array<int,array<string,mixed>>
 */
function adzworld_network_rows_from_legacy() {
	$legacy  = adzworld_sanitize_network_settings( get_option( 'adzworld_networks', array() ) );
	$catalog = adzworld_network_catalog();
	$rows    = array();
	foreach ( $legacy as $id => $stored ) {
		$used = '1' === $stored['accepted'] || '1' === $stored['enabled'];
		if ( ! $used ) {
			foreach ( $stored as $key => $value ) {
				if ( ! in_array( $key, array( 'accepted', 'enabled' ), true ) && '' !== $value ) {
					$used = true;
				}
			}
		}
		if ( ! $used || ! isset( $catalog[ $id ] ) ) {
			continue;
		}
		$fields = $stored;
		unset( $fields['accepted'], $fields['enabled'] );
		$rows[] = array(
			'id'       => $id,
			'preset'   => $id,
			'label'    => (string) $catalog[ $id ]['label'],
			'accepted' => $stored['accepted'],
			'enabled'  => $stored['enabled'],
			'fields'   => $fields,
			'params'   => array(),
		);
	}
	return $rows;
}

/**
 * Saved network rows.
 *
 * @return array<int,array<string,mixed>>
 */
function adzworld_network_rows() {
	$stored = get_option( 'adzworld_network_rows', false );
	if ( false === $stored ) {
		return adzworld_network_rows_from_legacy();
	}
	return adzworld_sanitize_network_rows( $stored );
}

/**
 * One saved row by its id.
 *
 * @param string $id Row id.
 * @return array<string,mixed>|null
 */
function adzworld_network_row( $id ) {
	$id = sanitize_key( (string) $id );
	foreach ( adzworld_network_rows() as $row ) {
		if ( $row['id'] === $id ) {
			return $row;
		}
	}
	return null;
}

/**
 * First enabled row for one listed network.
 *
 * @param string $preset Catalogue id.
 * @return array<string,mixed>|null
 */
function adzworld_network_preset_row( $preset ) {
	$preset = sanitize_key( (string) $preset );
	foreach ( adzworld_network_rows() as $row ) {
		if ( $preset === $row['preset'] && '1' === $row['enabled'] ) {
			return $row;
		}
	}
	return null;
}

/**
 * Empty row created by Add network.
 *
 * @param string             $preset Catalogue id, or custom.
 * @param array<string,bool> $used   Ids already assigned.
 * @return array<string,mixed>
 */
function adzworld_network_blank_row( $preset, $used ) {
	$catalog = adzworld_network_catalog();
	$preset  = sanitize_key( $preset );
	if ( 'custom' !== $preset && ! isset( $catalog[ $preset ] ) ) {
		return array();
	}
	$fields = array();
	if ( isset( $catalog[ $preset ] ) ) {
		foreach ( array_keys( $catalog[ $preset ]['fields'] ) as $key ) {
			$fields[ $key ] = '';
		}
	}
	return array(
		'id'       => adzworld_network_new_id( $used ),
		'preset'   => isset( $catalog[ $preset ] ) ? $preset : 'custom',
		'label'    => isset( $catalog[ $preset ] ) ? (string) $catalog[ $preset ]['label'] : '',
		'accepted' => '0',
		'enabled'  => '0',
		'fields'   => $fields,
		'params'   => array(),
	);
}

/**
 * Sanitize all publisher ad-network settings.
 *
 * A unit is enabled only when its required identifiers are valid and the
 * administrator explicitly accepts that the named external host will load.
 *
 * @param mixed $raw Stored or submitted settings.
 * @return array<string,array<string,string>>
 */
function adzworld_sanitize_network_settings( $raw ) {
	$raw   = is_array( $raw ) ? $raw : array();
	$clean = array();

	foreach ( adzworld_network_catalog() as $id => $network ) {
		$row      = isset( $raw[ $id ] ) && is_array( $raw[ $id ] ) ? $raw[ $id ] : array();
		$complete = true;
		$fields   = array();

		foreach ( $network['fields'] as $key => $rule ) {
			$fields[ $key ] = adzworld_sanitize_network_field( $rule, isset( $row[ $key ] ) ? $row[ $key ] : '' );
			$complete       = $complete && '' !== $fields[ $key ];
		}

		$accepted     = isset( $row['accepted'] ) && '1' === (string) $row['accepted'] ? '1' : '0';
		$enabled      = $complete && '1' === $accepted && isset( $row['enabled'] ) && '1' === (string) $row['enabled'] ? '1' : '0';
		$clean[ $id ] = array_merge(
			array(
				'accepted' => $accepted,
				'enabled'  => $enabled,
			),
			$fields
		);
	}

	return $clean;
}

/**
 * Read sanitized publisher ad-network settings.
 *
 * @return array<string,array<string,string>>
 */
function adzworld_network_settings() {
	$clean = array();
	foreach ( adzworld_network_rows() as $row ) {
		if ( 'custom' === $row['preset'] ) {
			continue;
		}
		$flat = array_merge(
			array(
				'accepted' => $row['accepted'],
				'enabled'  => $row['enabled'],
			),
			$row['fields']
		);
		if ( ! isset( $clean[ $row['preset'] ] ) || ( '1' !== $clean[ $row['preset'] ]['enabled'] && '1' === $row['enabled'] ) ) {
			$clean[ $row['preset'] ] = $flat;
		}
	}
	return $clean;
}

/**
 * Count enabled publisher ad-network units.
 *
 * @return int
 */
function adzworld_enabled_network_count() {
	$count = 0;
	foreach ( adzworld_network_rows() as $row ) {
		$count += '1' === $row['enabled'] ? 1 : 0;
	}
	return $count;
}

/**
 * Add the crossorigin attribute required by AdSense.
 *
 * @param string $tag    Script element.
 * @param string $handle Registered handle.
 * @return string
 */
function adzworld_network_script_tag( $tag, $handle ) {
	$extra = '';

	if ( 'adzworld-adsense' === $handle ) {
		$extra = ' crossorigin="anonymous"';
	} elseif ( 'adzworld-mediavine' === $handle ) {
		$extra = ' data-noptimize="1" data-cfasync="false" async="async"';
	} elseif ( in_array( $handle, array( 'adzworld-ezoic-cmp', 'adzworld-ezoic-gatekeeper', 'adzworld-monumetric', 'adzworld-raptive' ), true ) ) {
		$extra = ' data-cfasync="false"';
	}
	if ( 'adzworld-raptive' === $handle ) {
		$extra .= ' data-no-optimize="1"';
	}
	if ( 'adzworld-ezoic' === $handle && false === strpos( $tag, ' async' ) ) {
		$tag = str_replace( '<script ', '<script async ', $tag );
	}
	if ( '' !== $extra ) {
		$tag = str_replace( ' src=', $extra . ' src=', $tag );
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'adzworld_network_script_tag', 10, 2 );

/**
 * Register one official script.
 *
 * @param string            $handle Script handle.
 * @param string            $src    Script URL on the provider host.
 * @param bool              $footer True when the script prints in the footer.
 * @param array<int,string> $deps   Script dependencies.
 * @param string            $before JavaScript printed before the script.
 * @return void
 */
function adzworld_network_register_script( $handle, $src, $footer, $deps = array(), $before = '' ) {
	static $sent = array();
	if ( ! wp_script_is( $handle, 'enqueued' ) ) {
		wp_enqueue_script( $handle, $src, $deps, '1.0.9', $footer );
	}
	if ( '' === $before ) {
		return;
	}
	$token = $handle . '|' . $before;
	if ( isset( $sent[ $token ] ) ) {
		return;
	}
	$sent[ $token ] = true;
	wp_add_inline_script( $handle, $before, 'before' );
}

/**
 * Enqueue the official script used by one enabled network.
 *
 * Taboola and Carbon are attached inside the slot by the rotation script.
 * Every other catalog network has a fixed host and a checked identifier.
 *
 * @param string                    $id     Catalogue identifier.
 * @param array<string,string>|null $fields Enabled row, when the caller has it.
 * @return void
 */
function adzworld_enqueue_network( $id, $fields = null ) {
	if ( ! is_array( $fields ) ) {
		$settings = adzworld_network_settings();
		if ( empty( $settings[ $id ] ) || '1' !== $settings[ $id ]['enabled'] ) {
			return;
		}
		$fields = $settings[ $id ];
	} elseif ( empty( $fields['enabled'] ) || '1' !== $fields['enabled'] ) {
		return;
	}
	$row = $fields;

	if ( 'adsense' === $id ) {
		adzworld_network_register_script( 'adzworld-adsense', add_query_arg( 'client', $row['client'], 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js' ), true );
	} elseif ( 'gam' === $id ) {
		adzworld_network_register_script( 'adzworld-gpt', 'https://securepubads.g.doubleclick.net/tag/js/gpt.js', true );
	} elseif ( 'outbrain' === $id ) {
		adzworld_network_register_script( 'adzworld-outbrain', 'https://widgets.outbrain.com/outbrain.js', true );
	} elseif ( 'teads' === $id ) {
		adzworld_network_register_script( 'adzworld-teads', 'https://a.teads.tv/page/' . $row['page'] . '/tag', true );
	} elseif ( 'medianet' === $id ) {
		adzworld_network_register_script(
			'adzworld-medianet',
			add_query_arg( 'cid', $row['cid'], 'https://contextual.media.net/dmedianet.js' ),
			true,
			array(),
			'window._mNHandle=window._mNHandle||{};window._mNHandle.queue=window._mNHandle.queue||[];window.medianet_versionId=' . wp_json_encode( $row['version'] ) . ';'
		);
	} elseif ( 'amazonaps' === $id ) {
		adzworld_network_register_script( 'adzworld-aps', 'https://c.amazon-adsystem.com/aax2/apstag.js', true );
	} elseif ( 'ezoic' === $id ) {
		adzworld_network_register_script( 'adzworld-ezoic-cmp', 'https://cmp.gatekeeperconsent.com/min.js', false );
		adzworld_network_register_script( 'adzworld-ezoic-gatekeeper', 'https://the.gatekeeperconsent.com/cmp.min.js', false, array( 'adzworld-ezoic-cmp' ) );
		adzworld_network_register_script(
			'adzworld-ezoic',
			'https://www.ezojs.com/ezoic/sa.min.js',
			false,
			array( 'adzworld-ezoic-gatekeeper' ),
			'window.ezstandalone=window.ezstandalone||{};window.ezstandalone.cmd=window.ezstandalone.cmd||[];'
		);
		adzworld_network_register_script( 'adzworld-ezoic-analytics', 'https://ezoicanalytics.com/analytics.js', false, array( 'adzworld-ezoic' ) );
	} elseif ( 'mediavine' === $id ) {
		adzworld_network_register_script( 'adzworld-mediavine', 'https://scripts.mediavine.com/tags/' . $row['site'] . '.js', false );
	} elseif ( 'raptive' === $id ) {
		adzworld_network_register_script(
			'adzworld-raptive',
			add_query_arg( 'referrer', is_singular() ? get_permalink() : home_url( '/' ), 'https://ads.adthrive.com/sites/' . $row['site'] . '/ads.min.js' ),
			false,
			array(),
			'window.adthrive=window.adthrive||{};window.adthrive.cmd=window.adthrive.cmd||[];window.adthrive.plugin="adthrive-ads-manual";window.adthrive.host="ads.adthrive.com";'
		);
	} elseif ( 'monumetric' === $id ) {
		adzworld_network_register_script( 'adzworld-monumetric', 'https://monu.delivery/site/' . $row['script'], false );
	} elseif ( 'mgid' === $id ) {
		adzworld_network_register_script( 'adzworld-mgid', 'https://jsc.mgid.com/site/' . $row['website'] . '.js', false );
	} elseif ( 'revcontent' === $id ) {
		adzworld_network_register_script( 'adzworld-revcontent', 'https://assets.revcontent.com/master/delivery.js', true );
	} elseif ( 'criteo' === $id ) {
		adzworld_network_register_script( 'adzworld-criteo', 'https://static.criteo.net/js/ld/publishertag.js', true );
	} elseif ( 'dianomi' === $id ) {
		adzworld_network_register_script( 'adzworld-dianomi', 'https://www.dianomi.com/js/contextfeed.js', true );
	} elseif ( 'snigel' === $id ) {
		adzworld_network_register_script( 'adzworld-snigel', 'https://cdn.snigelweb.com/adengine/' . $row['site'] . '/loader.js', false );
	} elseif ( 'freestar' === $id ) {
		adzworld_network_register_script(
			'adzworld-freestar',
			'https://a.pub.network/' . $row['site'] . '/pubfig.min.js',
			true,
			array(),
			'window.freestar=window.freestar||{};window.freestar.config=window.freestar.config||{};window.freestar.config.enabled_slots=window.freestar.config.enabled_slots||[];window.freestar.config.enabled_slots.push({placementName:' . wp_json_encode( $row['placement'] ) . ',slotId:' . wp_json_encode( $row['placement'] ) . '});'
		);
	}
}

/**
 * Print HTML attributes for one network unit.
 *
 * @param array<string,string> $attributes Attribute name and raw value.
 * @return string
 */
function adzworld_network_html_attributes( $attributes ) {
	$html = '';

	foreach ( $attributes as $name => $value ) {
		if ( ! is_string( $name ) || 1 !== preg_match( '/^[a-z][a-z0-9:-]*$/D', $name ) ) {
			continue;
		}
		if ( '' === (string) $value && 'data-rc-widget' !== $name ) {
			continue;
		}
		$html .= ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
	}

	return $html;
}

/**
 * Attribute list for one enabled network unit.
 *
 * @param string               $id  Catalog identifier.
 * @param array<string,string> $row Sanitized settings.
 * @return array<string,string>
 */
function adzworld_network_unit_attributes( $id, $row ) {
	$attributes = array(
		'class'               => 'adzworld-network-unit',
		'data-adzworld-mount' => $id,
	);

	foreach ( $row as $key => $value ) {
		if ( in_array( $key, array( 'accepted', 'enabled' ), true ) || '' === $value ) {
			continue;
		}
		$attributes[ 'data-' . $key ] = $value;
	}

	if ( in_array( $id, array( 'gam', 'taboola', 'medianet', 'amazonaps', 'criteo' ), true ) ) {
		$attributes['id'] = wp_unique_id( 'adzworld-' . $id . '-' );
	}

	if ( 'outbrain' === $id ) {
		$attributes['class']         .= ' OUTBRAIN';
		$attributes['data-src']       = is_singular() ? (string) get_permalink() : home_url( '/' );
		$attributes['data-widget-id'] = $row['widget'];
	} elseif ( 'teads' === $id || 'monumetric' === $id ) {
		$attributes['class'] .= ' adzworld-network-unit--open';
	} elseif ( 'ezoic' === $id ) {
		$attributes['id']     = 'ezoic-pub-ad-placeholder-' . $row['placeholder'];
		$attributes['class'] .= ' adzworld-network-unit--open';
	} elseif ( 'mediavine' === $id ) {
		$allowed = array( 'content_hint', 'content_desktop_hint', 'content_mobile_hint' );
		if ( ! in_array( $row['hint'], $allowed, true ) ) {
			return array();
		}
		$attributes['class'] .= ' ' . $row['hint'];
	} elseif ( 'raptive' === $id ) {
		$locations = array( 'Content', 'Header', 'Sidebar', 'Below_Post' );
		if ( ! in_array( $row['location'], $locations, true ) || 1 !== preg_match( '/^[1-9]$/D', $row['sequence'] ) ) {
			return array();
		}
		$attributes['id']     = 'AdThrive_' . $row['location'] . '_' . $row['sequence'];
		$attributes['class'] .= ' adthrive-ad adthrive-' . strtolower( str_replace( '_', '-', $row['location'] ) );
	} elseif ( 'mgid' === $id ) {
		$attributes['data-type']      = '_mgwidget';
		$attributes['data-widget-id'] = $row['widget'];
	} elseif ( 'revcontent' === $id ) {
		$attributes['data-rc-widget']   = '';
		$attributes['data-widget-host'] = 'habitat';
		$attributes['data-endpoint']    = '//trends.revcontent.com';
		$attributes['data-widget-id']   = $row['widget'];
	} elseif ( 'dianomi' === $id ) {
		$attributes['class']                  .= ' dianomi_context';
		$attributes['data-dianomi-context-id'] = $row['context'];
	} elseif ( 'snigel' === $id ) {
		if ( 1 !== preg_match( '/^[a-z0-9][a-z0-9_-]{0,40}$/D', $row['placement'] ) ) {
			return array();
		}
		$attributes['id'] = 'adngin-' . $row['placement'] . '-0';
	} elseif ( 'freestar' === $id ) {
		$sizes = array( '300x250', '728x90', '320x50', '300x600', '970x90' );
		if ( ! in_array( $row['size'], $sizes, true ) || 1 !== preg_match( '/^[A-Za-z][A-Za-z0-9_-]{0,80}$/D', $row['placement'] ) ) {
			return array();
		}
		$attributes['id']               = $row['placement'];
		$attributes['data-freestar-ad'] = '__' . $row['size'];
	}

	return $attributes;
}

/**
 * Render one connected publisher network unit.
 *
 * @param array<string,string> $attributes Shortcode attributes.
 * @return string
 */
function adzworld_network_unit_shortcode( $attributes ) {
	$attributes = shortcode_atts(
		array(
			'network' => '',
			'row'     => '',
		),
		$attributes,
		'adzworld_network_unit'
	);
	$row_id     = sanitize_key( $attributes['row'] );
	$preset     = sanitize_key( $attributes['network'] );
	$found      = null;
	if ( '' !== $row_id ) {
		$found = adzworld_network_row( $row_id );
	} elseif ( '' !== $preset ) {
		$found = adzworld_network_preset_row( $preset );
	}
	if ( ! is_array( $found ) || '1' !== $found['enabled'] || 'custom' === $found['preset'] || ! isset( adzworld_network_catalog()[ $found['preset'] ] ) ) {
		return '';
	}
	$flat = array_merge(
		array(
			'accepted' => $found['accepted'],
			'enabled'  => $found['enabled'],
		),
		$found['fields']
	);
	adzworld_enqueue_network( $found['preset'], $flat );
	$markup = adzworld_network_unit_attributes( $found['preset'], $flat );
	if ( ! $markup ) {
		return '';
	}

	return '<div' . adzworld_network_html_attributes( $markup ) . '></div>';
}
add_shortcode( 'adzworld_network_unit', 'adzworld_network_unit_shortcode' );

/**
 * Rotation sources supplied by enabled publisher ad networks.
 *
 * @return array<string,array<string,string>>
 */
function adzworld_network_rotation_sources() {
	$sources = array();
	$alias   = array();
	foreach ( adzworld_network_rows() as $row ) {
		if ( '1' !== $row['enabled'] ) {
			continue;
		}
		$label                              = '' !== $row['label'] ? $row['label'] : __( 'New network', 'adz-world' );
		$sources[ 'network_' . $row['id'] ] = array(
			'label'     => $label,
			'shortcode' => '[adzworld_network_unit row="' . $row['id'] . '"]',
		);
		if ( 'custom' !== $row['preset'] && ! isset( $alias[ $row['preset'] ] ) ) {
			$alias[ $row['preset'] ] = 'network_' . $row['id'];
		}
	}
	foreach ( $alias as $preset => $key ) {
		if ( ! isset( $sources[ 'network_' . $preset ] ) ) {
			$sources[ 'network_' . $preset ] = $sources[ $key ];
		}
	}
	return $sources;
}

/**
 * Save publisher ad-network identifiers.
 *
 * @return void
 */
function adzworld_save_networks() {
	check_admin_referer( 'adzworld_save_networks', 'adzworld_network_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$posted = isset( $_POST['adzworld_network_rows'] ) && is_array( $_POST['adzworld_network_rows'] )
		? map_deep( wp_unslash( $_POST['adzworld_network_rows'] ), 'wp_strip_all_tags' )
		: array();
	$rows   = adzworld_sanitize_network_rows( $posted );
	$flag   = '1';
	if ( isset( $_POST['adzworld_add_network'] ) ) {
		$preset = isset( $_POST['adzworld_add_preset'] ) ? sanitize_key( wp_unslash( $_POST['adzworld_add_preset'] ) ) : '';
		if ( '' === $preset ) {
			$flag = 'choose';
		} elseif ( count( $rows ) >= ADZWORLD_NETWORK_ROW_MAX ) {
			$flag = 'full';
		} else {
			$used = array();
			foreach ( $rows as $row ) {
				$used[ $row['id'] ] = true;
			}
			$blank = adzworld_network_blank_row( $preset, $used );
			if ( $blank ) {
				$rows[] = $blank;
				$rows   = adzworld_sanitize_network_rows( $rows );
			} else {
				$flag = 'choose';
			}
		}
	}
	update_option( 'adzworld_network_rows', $rows, false );
	set_transient( 'adzworld_networks_saved_' . get_current_user_id(), $flag, MINUTE_IN_SECONDS );
	wp_safe_redirect( adzworld_desk_url( 'affiliates' ) );
	exit;
}
add_action( 'admin_post_adzworld_save_networks', 'adzworld_save_networks' );

/**
 * Save an existing advertising plugin's registered shortcode as a source.
 *
 * @return void
 */
function adzworld_save_provider_source() {
	$result = adzworld_save_rotation_source();
	$key    = 'adzworld_provider_source_' . get_current_user_id();
	$value  = is_wp_error( $result ) ? $result->get_error_message() : __( 'Advertising source saved and made available to rotations.', 'adz-world' );
	set_transient( $key, $value, MINUTE_IN_SECONDS );
	set_transient( $key . '_type', is_wp_error( $result ) ? 'error' : 'success', MINUTE_IN_SECONDS );
	wp_safe_redirect( adzworld_desk_url( 'affiliates' ) );
	exit;
}
add_action( 'admin_post_adzworld_save_provider_source', 'adzworld_save_provider_source' );

/**
 * Report whether a direct provider row is usable.
 *
 * @param array<string,string> $row Provider settings.
 * @return string
 */
function adzworld_network_status( $row ) {
	if ( '1' === $row['enabled'] ) {
		return __( 'Enabled', 'adz-world' );
	}
	foreach ( $row as $key => $value ) {
		if ( ! in_array( $key, array( 'accepted', 'enabled' ), true ) && '' !== $value ) {
			return __( 'Saved, not enabled', 'adz-world' );
		}
	}
	return __( 'Needs setup', 'adz-world' );
}

/**
 * Print one network section.
 *
 * @param array<string,mixed> $row Saved row.
 * @return void
 */
function adzworld_network_render_row( $row ) {
	$catalog = adzworld_network_catalog();
	$preset  = isset( $row['preset'] ) ? (string) $row['preset'] : 'custom';
	$network = isset( $catalog[ $preset ] ) ? $catalog[ $preset ] : array();
	$id      = (string) $row['id'];
	$prefix  = 'adzworld_network_rows[' . $id . ']';
	$label   = isset( $row['label'] ) && is_string( $row['label'] ) && '' !== $row['label'] ? $row['label'] : __( 'New network', 'adz-world' );
	$flat    = array(
		'accepted' => isset( $row['accepted'] ) ? (string) $row['accepted'] : '0',
		'enabled'  => isset( $row['enabled'] ) ? (string) $row['enabled'] : '0',
	);
	if ( $network ) {
		$fields = isset( $row['fields'] ) && is_array( $row['fields'] ) ? $row['fields'] : array();
		$flat   = array_merge( $flat, $fields );
	} else {
		$params = isset( $row['params'] ) && is_array( $row['params'] ) ? $row['params'] : array();
		foreach ( $params as $index => $param ) {
			$flat[ 'param_' . $index ] = isset( $param['value'] ) ? (string) $param['value'] : '';
		}
	}
	$status = adzworld_network_status( $flat );
	$class  = '1' === $flat['enabled'] ? ' is-enabled' : '';
	echo '<fieldset id="adzworld-network-row-' . esc_attr( $id ) . '" class="adzworld-connection-card"><legend>' . esc_html( $label ) . '</legend>';
	echo '<span class="adzworld-connection-status' . esc_attr( $class ) . '">' . esc_html( $status ) . '</span>';
	echo '<input type="hidden" name="' . esc_attr( $prefix . '[preset]' ) . '" value="' . esc_attr( $preset ) . '">';
	echo '<p><label>' . esc_html__( 'Section label', 'adz-world' ) . '<br>';
	echo '<input type="text" class="regular-text" maxlength="80" name="' . esc_attr( $prefix . '[label]' ) . '" value="' . esc_attr( isset( $row['label'] ) ? (string) $row['label'] : '' ) . '">';
	echo '</label></p>';
	if ( $network ) {
		echo '<p>' . esc_html( $network['note'] ) . '</p>';
		if ( function_exists( 'adzworld_registration_has' ) && adzworld_registration_has( $preset ) ) {
			echo '<p class="adzworld-pay-actions">';
			adzworld_registration_render_signup( $preset, false );
			echo ' <a href="' . esc_url( adzworld_desk_url( 'affiliates' ) . '#adzworld-paid-network-' . rawurlencode( $preset ) ) . '">' . esc_html__( 'Registration procedure', 'adz-world' ) . '</a></p>';
			adzworld_registration_render_steps( $preset );
		} else {
			echo '<ol class="adzworld-connection-steps">';
			echo '<li><a class="button" href="' . esc_url( $network['signup'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create or open account', 'adz-world' ) . '</a></li>';
			echo '<li><a href="' . esc_url( $network['setup'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Find the required identifiers', 'adz-world' ) . '</a></li>';
			if ( isset( $network['api'] ) ) {
				echo '<li><a href="' . esc_url( $network['api'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Optional official API access', 'adz-world' ) . '</a></li>';
			}
			echo '<li>' . esc_html__( 'Save the identifiers and enable this source.', 'adz-world' ) . '</li></ol>';
		}
		foreach ( $network['fields'] as $key => $rule ) {
			$name  = $prefix . '[fields][' . $key . ']';
			$value = isset( $flat[ $key ] ) ? (string) $flat[ $key ] : '';
			echo '<p><label>' . esc_html( $rule['label'] ) . '<br>';
			if ( isset( $rule['choices'] ) ) {
				echo '<select name="' . esc_attr( $name ) . '">';
				foreach ( $rule['choices'] as $choice ) {
					echo '<option value="' . esc_attr( $choice ) . '"' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
				}
				echo '</select>';
			} else {
				echo '<input type="text" class="regular-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
			}
			echo '</label>';
			if ( isset( $rule['hint'] ) ) {
				echo '<span class="description">' . esc_html( $rule['hint'] ) . '</span>';
			}
			echo '</p>';
		}
		/* translators: %s: external advertising network host name. */
		$acceptance = sprintf( __( 'I have this provider account and accept loading a visible unit from %s.', 'adz-world' ), $network['host'] );
	} else {
		echo '<p>' . esc_html__( 'Name the network. Add one parameter row for each value it requires, such as an API key. Fill the empty row and save. The values stay in WordPress.', 'adz-world' ) . '</p>';
		$params   = isset( $row['params'] ) && is_array( $row['params'] ) ? $row['params'] : array();
		$params[] = array(
			'label' => '',
			'value' => '',
		);
		foreach ( $params as $index => $param ) {
			$base = $prefix . '[params][' . $index . ']';
			echo '<div class="adzworld-network-param">';
			echo '<p><label>' . esc_html__( 'Parameter', 'adz-world' ) . '<br><input type="text" maxlength="80" name="' . esc_attr( $base . '[label]' ) . '" value="' . esc_attr( isset( $param['label'] ) ? (string) $param['label'] : '' ) . '"></label></p>';
			echo '<p><label>' . esc_html__( 'Value', 'adz-world' ) . '<br><input type="text" maxlength="1000" autocomplete="off" name="' . esc_attr( $base . '[value]' ) . '" value="' . esc_attr( isset( $param['value'] ) ? (string) $param['value'] : '' ) . '"></label></p>';
			echo '</div>';
			if ( '' !== ( isset( $param['label'] ) ? (string) $param['label'] : '' ) ) {
				echo '<p><label><input type="checkbox" name="' . esc_attr( $base . '[remove]' ) . '" value="1"> ' . esc_html__( 'Remove this parameter', 'adz-world' ) . '</label></p>';
			}
		}
		$acceptance = __( 'I have this provider account and these parameters are the ones it assigned to this site.', 'adz-world' );
	}
	echo '<p><label><input type="checkbox" name="' . esc_attr( $prefix . '[accepted]' ) . '" value="1"' . checked( $flat['accepted'], '1', false ) . '> ' . esc_html( $acceptance ) . '</label></p>';
	echo '<p><label><input type="checkbox" name="' . esc_attr( $prefix . '[enabled]' ) . '" value="1"' . checked( $flat['enabled'], '1', false ) . '> ' . esc_html__( 'Enable this rotation source', 'adz-world' ) . '</label></p>';
	echo '<p><label><input type="checkbox" name="' . esc_attr( $prefix . '[remove]' ) . '" value="1"> ' . esc_html__( 'Remove this network', 'adz-world' ) . '</label></p>';
	if ( $network ) {
		echo '<p class="adzworld-connection-links"><a href="' . esc_url( $network['terms'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Terms', 'adz-world' ) . '</a> · <a href="' . esc_url( $network['privacy'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Privacy', 'adz-world' ) . '</a></p>';
	}
	echo '</fieldset>';
}

/**
 * Render publisher ad-network connections.
 *
 * @return void
 */
function adzworld_network_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	$rows            = adzworld_network_rows();
	$notice          = 'adzworld_networks_saved_' . get_current_user_id();
	$provider_notice = 'adzworld_provider_source_' . get_current_user_id();
	$saved           = get_transient( $notice );

	if ( $saved ) {
		delete_transient( $notice );
		if ( 'full' === $saved ) {
			echo '<div class="notice notice-warning"><p>' . esc_html(
				sprintf(
					/* translators: %d: maximum number of network rows. */
					__( 'The connections were saved. The list holds %d networks.', 'adz-world' ),
					ADZWORLD_NETWORK_ROW_MAX
				)
			) . '</p></div>';
		} elseif ( 'choose' === $saved ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'The connections were saved. Choose a network, then use Add network.', 'adz-world' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Ad-network connections saved.', 'adz-world' ) . '</p></div>';
		}
	}
	if ( get_transient( $provider_notice ) ) {
		$type = get_transient( $provider_notice . '_type' );
		echo '<div class="notice notice-' . ( 'error' === $type ? 'error' : 'success' ) . '"><p>' . esc_html( get_transient( $provider_notice ) ) . '</p></div>';
		delete_transient( $provider_notice );
		delete_transient( $provider_notice . '_type' );
	}
	echo '<div class="wrap adzworld-connections"><h2>' . esc_html__( 'Advertising connections', 'adz-world' ) . '</h2>';
	echo '<p class="adzworld-connections__lead">' . esc_html__( 'Add a network. Each added network is a section for the parameters that network assigned to this site. Revenue attribution and payment stay with the site owner’s provider account.', 'adz-world' ) . '</p>';
	if ( function_exists( 'adzworld_registration_desk' ) ) {
		adzworld_registration_desk();
	}
	echo '<h3>' . esc_html__( 'Ad network units', 'adz-world' ) . '</h3>';
	echo '<p>' . esc_html__( 'Choose a network and add it. The section that appears holds that network’s identifiers or API key. An empty or rejected identifier stays off. No account is invented. Header-bidding exchanges are demand inside the listed accounts. Pop and push networks are not connections. A parameter stored for another network stays in WordPress.', 'adz-world' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="adzworld_save_networks">';
	wp_nonce_field( 'adzworld_save_networks', 'adzworld_network_nonce' );
	echo '<div class="adzworld-network-add"><p><label for="adzworld-add-preset">' . esc_html__( 'Network', 'adz-world' ) . '<br><select id="adzworld-add-preset" name="adzworld_add_preset">';
	echo '<option value="">' . esc_html__( 'Choose a network', 'adz-world' ) . '</option>';
	foreach ( adzworld_network_catalog() as $preset_id => $network ) {
		echo '<option value="' . esc_attr( $preset_id ) . '">' . esc_html( $network['label'] ) . '</option>';
	}
	echo '<option value="custom">' . esc_html__( 'Other network', 'adz-world' ) . '</option></select></label></p>';
	submit_button( __( 'Add network', 'adz-world' ), 'secondary', 'adzworld_add_network', false );
	echo '</div>';
	if ( ! $rows ) {
		echo '<p>' . esc_html__( 'No network is added yet.', 'adz-world' ) . '</p>';
	}
	echo '<div class="adzworld-connection-grid">';
	foreach ( $rows as $row ) {
		adzworld_network_render_row( $row );
	}
	echo '</div>';
	submit_button( __( 'Save ad-network connections', 'adz-world' ) );
	echo '</form>';

	echo '<section class="adzworld-provider-bridge"><h3>' . esc_html__( 'Connect another established provider', 'adz-world' ) . '</h3>';
	echo '<p>' . esc_html(
		sprintf(
			/* translators: %s: product name. */
			__( 'Use a shortcode registered by a provider or advertising plugin already installed on this site. Raw JavaScript is not accepted here. The saved source becomes available to %s rotations.', 'adz-world' ),
			ADZWORLD_NAME
		)
	) . '</p>';
	echo '<p><a class="button" href="' . esc_url(
		add_query_arg(
			array(
				's'    => 'Advanced Ads',
				'tab'  => 'search',
				'type' => 'term',
			),
			admin_url( 'plugin-install.php' )
		)
	) . '">' . esc_html__( 'Find an established ad plugin', 'adz-world' ) . '</a></p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="adzworld_save_provider_source">';
	wp_nonce_field( 'adzworld_rotation_source', 'adzworld_source_nonce' );
	echo '<div class="adzworld-provider-fields"><p><label for="adzworld-source-key">' . esc_html__( 'Source key', 'adz-world' ) . '</label><br><input id="adzworld-source-key" name="source_key" maxlength="50" pattern="[a-z][a-z0-9_-]{0,49}" required></p>';
	echo '<p><label for="adzworld-source-label">' . esc_html__( 'Display label', 'adz-world' ) . '</label><br><input id="adzworld-source-label" name="source_label" maxlength="200" required></p>';
	echo '<p><label for="adzworld-source-shortcode">' . esc_html__( 'Existing provider shortcode', 'adz-world' ) . '</label><br><input id="adzworld-source-shortcode" name="source_shortcode" maxlength="2000" placeholder="[the_ad id=&quot;123&quot;]" required></p></div>';
	echo '<p><label><input type="checkbox" name="remove_source" value="yes"> ' . esc_html__( 'Remove the source with this key', 'adz-world' ) . '</label></p>';
	submit_button( __( 'Save provider source', 'adz-world' ), 'secondary', 'submit', false );
	echo '</form></section>';

	/*
	 * The affiliate cards used to be embedded here as well as on their own
	 * tab, so the same seven providers rendered twice in two different
	 * layouts. Get Paid now holds every provider once.
	 */
	echo '</div>';
}
