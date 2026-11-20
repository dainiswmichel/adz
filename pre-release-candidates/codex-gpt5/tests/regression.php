<?php
/**
 * Local WordPress regression checks: wp eval-file path/to/this/file.php.
 *
 * HTTP uses mocked provider responses, never real payment credentials.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

/**
 * Fail loudly and count checked assertions.
 *
 * @param bool   $condition Test result.
 * @param string $message   Assertion description.
 * @return void
 * @throws RuntimeException On an assertion failure.
 */
function adzworld_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( esc_html( $message ) );
	}
	WP_CLI::log( 'PASS ' . $message );
}

foreach ( array( 0, 1, 4, 5, 9, 10, 15, 99, 100, 101, 100005, PHP_INT_MAX ) as $adzworld_amount ) {
	$adzworld_split = adzworld_split_commission( $adzworld_amount );
	adzworld_test_assert( $adzworld_split['service_minor'] + $adzworld_split['publisher_minor'] === $adzworld_amount, 'Exact split conserves ' . $adzworld_amount . ' minor units' );
}
adzworld_test_assert(
	array(
		'service_minor'   => 10,
		'publisher_minor' => 91,
	) === adzworld_split_commission( 101 ),
	'101 cents splits into 10 and 91 cents'
);
adzworld_test_assert( is_wp_error( adzworld_split_commission( -1 ) ), 'Negative commissions are rejected' );
adzworld_test_assert( is_wp_error( adzworld_split_commission( 1.0 ) ), 'Floating-point commissions are rejected' );
adzworld_test_assert( 'USD 1.01' === adzworld_format_money( 101, 'USD' ), 'USD uses two decimals' );
adzworld_test_assert( 'JPY 101' === adzworld_format_money( 101, 'JPY' ), 'JPY uses no decimals' );
adzworld_test_assert( 'KWD 0.101' === adzworld_format_money( 101, 'KWD' ), 'KWD uses three decimals' );
adzworld_test_assert( 'USD -0.01' === adzworld_format_money( -1, 'USD' ), 'Negative adjustments retain their sign' );
adzworld_test_assert( is_wp_error( adzworld_format_money( 1, 'XXX' ) ), 'Unknown currency is rejected' );

$adzworld_statement = array(
	'publisher_id' => 'publisher_test',
	'generated_at' => time(),
	'currencies'   => array(
		array(
			'currency'        => 'USD',
			'settled_minor'   => 101,
			'reversed_minor'  => 0,
			'estimated_minor' => 15,
			'service_minor'   => 10,
			'publisher_minor' => 91,
			'available_minor' => 51,
			'reserved_minor'  => 10,
			'paid_minor'      => 30,
		),
	),
);
adzworld_test_assert( is_array( adzworld_validate_statement( $adzworld_statement, 'publisher_test' ) ), 'Balanced statement is accepted' );
adzworld_test_assert( is_wp_error( adzworld_validate_statement( $adzworld_statement, 'another_publisher' ) ), 'Another publisher statement is rejected' );
$adzworld_bad = $adzworld_statement;
++$adzworld_bad['currencies'][0]['available_minor'];
adzworld_test_assert( is_wp_error( adzworld_validate_statement( $adzworld_bad, 'publisher_test' ) ), 'Unbalanced available balance is rejected' );
$adzworld_bad['currencies'] = array( $adzworld_statement['currencies'][0], $adzworld_statement['currencies'][0] );
adzworld_test_assert( is_wp_error( adzworld_validate_statement( $adzworld_bad, 'publisher_test' ) ), 'Duplicate currency totals are rejected' );
$adzworld_bad                  = $adzworld_statement;
$adzworld_bad['generated_at'] -= 2 * DAY_IN_SECONDS;
adzworld_test_assert( is_wp_error( adzworld_validate_statement( $adzworld_bad, 'publisher_test' ) ), 'Stale statement is rejected' );
adzworld_test_assert( is_wp_error( adzworld_fetch_statement() ), 'Unconfigured statement connection is explicitly unavailable' );

foreach ( array( '../wp-config.php', '../../template1.php', '/tmp/template1.php', array(), 'template1.php/../bad.php' ) as $adzworld_template ) {
	adzworld_test_assert( '' === adzworld_resolve_template( $adzworld_template ), 'Invalid template path/type is rejected' );
}
adzworld_test_assert( is_file( adzworld_resolve_template( 'template1.php' ) ), 'Shipped template remains available' );
adzworld_test_assert( '' === adzworld_validate_rotation_id( 'siteurl' ), 'Core option is not a rotation ID' );
adzworld_test_assert( 'retained-content' === adzworld_shortcode( array( 'adz_sequence' => '0' ), 'retained-content' ), 'Shortcode without an available ad retains the article content' );
adzworld_test_assert( '' === adzworld_iframe_shortcode( array() ), 'Iframe shortcode has no external default URL' );
adzworld_test_assert( '' === adzworld_iframe_shortcode( array( 'adz_url' => 'javascript:alert(1)' ) ), 'Iframe shortcode rejects non-HTTP URLs' );
$adzworld_iframe = adzworld_iframe_shortcode(
	array(
		'adz_url' => 'https://example.com/advertisement',
		'height'  => 250,
		'width'   => '80%',
		'title'   => 'Safe <advertisement>',
	)
);
adzworld_test_assert(
	false !== strpos( $adzworld_iframe, 'src="https://example.com/advertisement"' )
	&& false !== strpos( $adzworld_iframe, 'loading="lazy"' )
	&& false === strpos( $adzworld_iframe, '<advertisement>' ),
	'Explicit HTTPS iframe renders with escaped accessible markup'
);

adzworld_test_assert( 'https://dainis.net/donate/' === adzworld_donation_url(), 'The support panel uses the verified HTTPS donation page' );
adzworld_test_assert( null === wp_parse_url( adzworld_donation_url(), PHP_URL_QUERY ), 'The donation link contains no referral or tracking query' );
ob_start();
adzworld_support_panel();
$adzworld_support_markup = (string) ob_get_clean();
adzworld_test_assert(
	false !== strpos( $adzworld_support_markup, 'class="button button-primary"' )
	&& false !== strpos( $adzworld_support_markup, 'Buy Dainis a coffee' )
	&& false === strpos( $adzworld_support_markup, implode( '', array( 'sty', 'le=' ) ) ),
	'The support panel uses the standard WordPress button without inline styling'
);

$adzworld_affiliate_fixture = adzworld_sanitize_affiliate_accounts(
	array(
		'amazon' => array(
			'enabled' => '1',
			'host'    => 'www.amazon.com',
			'tag'     => 'publisher-20',
		),
	)
);
adzworld_test_assert( '1' === $adzworld_affiliate_fixture['amazon']['enabled'], 'A valid enabled publisher affiliate account survives validation' );
adzworld_test_assert(
	'https://www.amazon.com/s?k=light%20blue%20couch&tag=publisher-20' === adzworld_affiliate_url( 'amazon', $adzworld_affiliate_fixture['amazon'], 'light blue couch' ),
	'Direct affiliate attribution uses the publisher tracking ID'
);
$adzworld_affiliate_fixture['amazon']['tag'] = '';
adzworld_test_assert( '' === adzworld_affiliate_url( 'amazon', $adzworld_affiliate_fixture['amazon'], 'couch' ), 'An incomplete affiliate account cannot emit a link' );
$adzworld_affiliate_fixture = adzworld_sanitize_affiliate_accounts(
	array(
		'amazon'  => array(
			'enabled' => '1',
			'host'    => 'www.amazon.com',
			'tag'     => 'publisher-20',
		),
		'walmart' => array(
			'ad'      => '456',
			'enabled' => '1',
			'partner' => '123',
			'program' => '789',
		),
		'awin'    => array(
			'advertiser'  => '456',
			'destination' => 'https://example.com/search?q={query}',
			'enabled'     => '1',
			'publisher'   => '123',
		),
		'cj'      => array(
			'aid'         => '5678',
			'destination' => 'https://example.com/search?q={query}',
			'domain'      => 'www.jdoqocy.com',
			'enabled'     => '1',
			'pid'         => '1234',
		),
		'rakuten' => array(
			'destination' => 'https://example.com/search?q={query}',
			'enabled'     => '1',
			'id'          => 'Abc123',
			'mid'         => '789',
		),
		'ebay'    => array(
			'campid'  => '123456',
			'enabled' => '1',
			'mkrid'   => '711-53200-19255-0',
			'siteid'  => '0',
		),
	)
);
foreach ( array( 'amazon', 'walmart', 'awin', 'cj', 'rakuten', 'ebay' ) as $adzworld_affiliate_id ) {
	adzworld_test_assert(
		'' !== adzworld_affiliate_url( $adzworld_affiliate_id, $adzworld_affiliate_fixture[ $adzworld_affiliate_id ], 'light blue couch' ),
		ucfirst( $adzworld_affiliate_id ) . ' emits a tracking URL from a complete publisher account'
	);
}
$adzworld_affiliate_preference                         = adzworld_empty_preference();
$adzworld_affiliate_preference['category_refinements'] = array( 'couch--light blue', 'car--electric' );
adzworld_test_assert( 'couch light blue' === adzworld_affiliate_request_string( $adzworld_affiliate_preference, 'couch' ), 'Affiliate search uses only the active category and its refinements' );

$adzworld_network_fixture = adzworld_sanitize_network_settings(
	array(
		'adsense' => array(
			'accepted' => '1',
			'client'   => 'ca-pub-123456789012',
			'enabled'  => '1',
			'slot'     => '123456',
		),
	)
);
adzworld_test_assert( '1' === $adzworld_network_fixture['adsense']['enabled'], 'A complete accepted publisher ad unit can be enabled' );
$adzworld_network_fixture = adzworld_sanitize_network_settings(
	array(
		'adsense' => array(
			'client'  => 'ca-pub-123456789012',
			'enabled' => '1',
			'slot'    => '123456',
		),
	)
);
adzworld_test_assert( '0' === $adzworld_network_fixture['adsense']['enabled'], 'A publisher ad unit stays disabled without explicit external-host acceptance' );

$adzworld_network_fixture = adzworld_sanitize_network_settings(
	array(
		'outbrain' => array(
			'accepted' => '1',
			'enabled'  => '1',
			'widget'   => 'AR_12',
		),
	)
);
adzworld_test_assert( '1' === $adzworld_network_fixture['outbrain']['enabled'], 'A complete accepted Outbrain widget can be enabled' );
$adzworld_previous_networks = get_option( 'adzworld_networks' );
update_option( 'adzworld_networks', $adzworld_network_fixture, false );
$adzworld_outbrain_markup = adzworld_network_unit_shortcode( array( 'network' => 'outbrain' ) );
adzworld_test_assert(
	false !== strpos( $adzworld_outbrain_markup, 'class="OUTBRAIN adzworld-network-unit"' )
	&& false !== strpos( $adzworld_outbrain_markup, 'data-widget-id="AR_12"' )
	&& false === strpos( $adzworld_outbrain_markup, implode( '', array( 'sty', 'le=' ) ) ),
	'Outbrain renders the official widget container without inline styling'
);
if ( false === $adzworld_previous_networks ) {
	delete_option( 'adzworld_networks' );
} else {
	update_option( 'adzworld_networks', $adzworld_previous_networks, false );
}
$adzworld_managed_networks = adzworld_managed_network_catalog();
foreach ( array( 'adsense', 'gam' ) as $adzworld_api_network ) {
	adzworld_test_assert(
		isset( adzworld_network_catalog()[ $adzworld_api_network ]['api'] )
		&& 0 === strpos( adzworld_network_catalog()[ $adzworld_api_network ]['api'], 'https://developers.google.com/' ),
		ucfirst( $adzworld_api_network ) . ' exposes its official API access documentation'
	);
}
foreach ( array( 'medianet', 'amazonaps', 'ezoic', 'mediavine', 'raptive', 'monumetric' ) as $adzworld_managed_network ) {
	adzworld_test_assert(
		isset( $adzworld_managed_networks[ $adzworld_managed_network ]['signup'], $adzworld_managed_networks[ $adzworld_managed_network ]['setup'] )
		&& 0 === strpos( $adzworld_managed_networks[ $adzworld_managed_network ]['signup'], 'https://' )
		&& 0 === strpos( $adzworld_managed_networks[ $adzworld_managed_network ]['setup'], 'https://' ),
		ucfirst( $adzworld_managed_network ) . ' has official HTTPS enrollment and setup paths'
	);
}
$adzworld_hundred_slot_rotation = adzworld_sanitize_named_rotations(
	array(
		'rotation-b' => array(
			'name'  => 'Rotation B',
			'slots' => array_fill( 0, 100, 'intro' ),
		),
	)
);
adzworld_test_assert(
	100 === count( $adzworld_hundred_slot_rotation['rotation-b']['slots'] ),
	'Named rotations preserve one hundred ordered slots'
);
adzworld_test_assert(
	empty(
		adzworld_sanitize_named_rotations(
			array(
				'invalid' => array(
					'name'  => 'Invalid',
					'slots' => array( 'source:not-registered' ),
				),
			)
		)
	),
	'Named rotations reject unavailable provider sources'
);

$adzworld_probe = 'adzworld_codex_probe_' . wp_generate_password( 12, false );
add_option( $adzworld_probe, 'retained', '', false );
$_POST['adz_ad_options'] = array( 'ad_settings' => array( array( 'rotation_id' => $adzworld_probe ) ) );
wp_set_current_user( 0 );
adzworld_check_post();
adzworld_test_assert( 'retained' === get_option( $adzworld_probe ), 'Unauthenticated init callback cannot delete arbitrary options' );
unset( $_POST['adz_ad_options'] );
delete_option( $adzworld_probe );

WP_CLI::success( 'Candidate regression checks passed. Financial settlement is not implemented or verified by these checks.' );
