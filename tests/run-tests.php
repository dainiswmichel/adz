<?php
/**
 * Adversarial tests for the adz.world standalone rework.
 *
 * Each test attacks or exercises a defect that was present in 1.0.8. Run with:
 *   php tests/run-tests.php
 *
 * @package adz.world
 */

require __DIR__ . '/bootstrap.php';
require dirname( __DIR__ ) . '/includes/adz-visitor.php';
require dirname( __DIR__ ) . '/includes/adz-views.php';
require dirname( __DIR__ ) . '/includes/adz-ad-source.php';

$passed = 0;
$failed = 0;

/**
 * Assert a condition.
 *
 * @param string $name      Test name.
 * @param bool   $condition Result under test.
 * @param string $detail    Shown when the test fails.
 * @return void
 */
function t( $name, $condition, $detail = '' ) {
	global $passed, $failed;

	if ( $condition ) {
		$passed++;
		echo "  PASS  {$name}\n";
		return;
	}

	$failed++;
	echo "  FAIL  {$name}" . ( $detail ? "\n        {$detail}" : '' ) . "\n";
}

/**
 * Create an adz_ad post in the stub store.
 *
 * @param int    $id      Post ID.
 * @param string $content Ad markup.
 * @param string $status  Post status.
 * @return void
 */
function make_ad( $id, $content, $status = 'publish' ) {
	$GLOBALS['adz_test_posts'][ $id ] = new WP_Post(
		array(
			'ID'           => $id,
			'post_type'    => 'adz_ad',
			'post_status'  => $status,
			'post_content' => $content,
			'post_title'   => 'Ad ' . $id,
		)
	);
}

adz_views_install();

echo "\nSQL injection (was: four concatenated queries using sanitize_text_field)\n";

// sanitize_text_field() leaves quotes intact, so this payload reached the old
// WHERE clause verbatim. If prepare() is not doing its job, the tautology makes
// the query match a row that belongs to a different visitor.
$evil = "1' OR '1'='1";

$GLOBALS['adz_test_user'] = 7;
adz_record_view( 42, 'thru_page' );
$legit = adz_get_view( 42, 'thru_page' );
t( 'a genuine view is recorded', null !== $legit );

$GLOBALS['adz_test_user'] = 9; // A different visitor, who has viewed nothing.
$GLOBALS['adz_visitor_cache'] = null;

$injected = null;
try {
	$injected = adz_get_view( $evil, "thru_page' OR '1'='1" );
} catch ( Throwable $e ) {
	$injected = 'threw: ' . $e->getMessage();
}
t(
	'injected target/target_type does not leak another visitor row',
	null === $injected,
	'Got: ' . var_export( $injected, true )
);

// A quote in the payload must survive as data, not break the statement.
$GLOBALS['adz_test_user'] = 11;
adz_record_view( 99, "o'brien" );
$quoted = adz_get_view( 99, "o'brien" );
t( 'a quote in target_type is stored and matched as data', null !== $quoted );

echo "\nFile inclusion (was: \$_POST['adz_template'] into require_once)\n";

$traversals = array(
	'../../../../etc/passwd',
	'../../wp-config.php',
	'..%2F..%2Fwp-config.php',
	'/etc/passwd',
	'template1.php/../../../wp-config.php',
	'',
	'notatemplate.php',
);
$blocked = true;
foreach ( $traversals as $bad ) {
	if ( '' !== adz_safe_template( $bad ) ) {
		$blocked = false;
		echo "        leaked: {$bad} -> " . adz_safe_template( $bad ) . "\n";
	}
}
t( 'every traversal and unknown template is rejected', $blocked );
t( 'the real template is still accepted', 'template1.php' === adz_safe_template( 'template1.php' ) );

echo "\nVisitor identity (was: REMOTE_ADDR, shared by everyone behind a NAT)\n";

$GLOBALS['adz_test_user'] = 101;
$a = adz_visitor_id();
t( 'logged-in identity is the user, not the IP', 'u101' === $a, "Got: {$a}" );

$GLOBALS['adz_test_user'] = 102;
t( 'identity follows a change of current user', 'u102' === adz_visitor_id() );
t( 'identity is not derived from REMOTE_ADDR', false === strpos( $a, '127.0.0' ) );

echo "\nBrowsing throttle (was: \$_SESSION, never started, never throttled)\n";

$GLOBALS['adz_test_user'] = 201;
t( 'no grace period before any view', ! adz_visitor_in_grace_period() );

adz_visitor_start_grace_period( 60 );
t( 'grace period holds after a view', adz_visitor_in_grace_period() );

adz_visitor_start_grace_period( 0 );
t( 'grace period lapses when the window expires', ! adz_visitor_in_grace_period() );

echo "\nAd source (was: keyed on network_ad_id, always empty without the server)\n";

make_ad( 500, '<p>Ad five hundred</p>' );
make_ad( 501, '<p>Ad five oh one</p>' );
make_ad( 502, '<p>Draft ad</p>', 'draft' );

t( 'a published ad resolves by local post ID', 500 === adz_resolve_ad_id( 500 ) );
t( 'its content is returned', '<p>Ad five hundred</p>' === adz_get_ad_content( 500 ) );
t( 'a draft ad serves nothing', '' === adz_get_ad_content( 502 ) );
t( 'an unknown ad serves nothing', '' === adz_get_ad_content( 99999 ) );
t( 'garbage input serves nothing', '' === adz_get_ad_content( '../../etc/passwd' ) );

update_post_meta( 501, 'network_ad_id', '31337' );
t( 'a legacy network_ad_id still resolves', 501 === adz_resolve_ad_id( 31337 ) );

t( 'all published ads are listed', array( 500, 501 ) === adz_get_all_ad_ids() );

echo "\nRotation (was: the same advance block copy-pasted four times)\n";

$seq   = array( 500, 501 );
$order = array();
for ( $i = 0; $i < 4; $i++ ) {
	$order[] = adz_next_ad_in_sequence( $seq, 'adz_test_rotation' );
}
t( 'each ad is served before any repeats', array( 500, 501, 500, 501 ) === $order, 'Got: ' . implode( ',', $order ) );

$empty = adz_next_ad_in_sequence( array(), 'adz_test_empty' );
t( 'an empty sequence serves nothing', 0 === $empty );

$gone = adz_next_ad_in_sequence( array( 99999 ), 'adz_test_gone' );
t( 'a sequence of deleted ads serves nothing', 0 === $gone );

echo "\nGate decision\n";

$GLOBALS['adz_test_user'] = 301;
t( 'a first-time visitor must view an ad', adz_view_required( 42, 'thru_page', 'infinite' ) );

adz_record_view( 42, 'thru_page' );
adz_visitor_start_grace_period( 300 );
t( 'inside the browsing window, no further ad', ! adz_view_required( 42, 'thru_page', 'infinite' ) );

adz_visitor_start_grace_period( 0 );
t( 'once the window lapses, an ad is due again', adz_view_required( 42, 'thru_page', 'infinite' ) );

$GLOBALS['adz_test_user'] = 302;
adz_record_view( 77, 'thru_page' );
adz_visitor_start_grace_period( 0 );
t( 'a daily cap of 1 lets the visitor through after one view', ! adz_view_required( 77, 'thru_page', 1 ) );

echo "\n" . str_repeat( '-', 56 ) . "\n";
echo "  {$passed} passed, {$failed} failed\n\n";

exit( $failed > 0 ? 1 : 0 );
