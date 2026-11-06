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

$adzworld_probe = 'adzworld_codex_probe_' . wp_generate_password( 12, false );
add_option( $adzworld_probe, 'retained', '', false );
$_POST['adz_ad_options'] = array( 'ad_settings' => array( array( 'rotation_id' => $adzworld_probe ) ) );
wp_set_current_user( 0 );
adzworld_check_post();
adzworld_test_assert( 'retained' === get_option( $adzworld_probe ), 'Unauthenticated init callback cannot delete arbitrary options' );
unset( $_POST['adz_ad_options'] );
delete_option( $adzworld_probe );

WP_CLI::success( 'Candidate regression checks passed. Financial settlement is not implemented or verified by these checks.' );
