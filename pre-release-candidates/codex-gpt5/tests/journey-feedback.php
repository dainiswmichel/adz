<?php
/**
 * Behavioral checks for explicit adz journey feedback.
 *
 * Run with: php tests/journey-feedback.php
 *
 * @package adz-world
 */

define( 'ABSPATH', __DIR__ );
define( 'DAY_IN_SECONDS', 86400 );

$adz_test_meta = array(
	'adzworld_journey_consent' => '1',
);

/**
 * WordPress action stub.
 *
 * @return void
 */
function add_action() {}

/**
 * Translation stub.
 *
 * @param string $text Source text.
 * @return string
 */
function __( $text ) {
	return $text;
}

/**
 * Logged-in state stub.
 *
 * @return bool
 */
function is_user_logged_in() {
	return true;
}

/**
 * Current user stub.
 *
 * @return int
 */
function get_current_user_id() {
	return 7;
}

/**
 * User-meta read stub.
 *
 * @param int    $user_id User id.
 * @param string $key     Meta key.
 * @return mixed
 */
function get_user_meta( $user_id, $key ) {
	global $adz_test_meta;
	unset( $user_id );

	return isset( $adz_test_meta[ $key ] ) ? $adz_test_meta[ $key ] : '';
}

/**
 * User-meta write stub.
 *
 * @param int    $user_id User id.
 * @param string $key     Meta key.
 * @param mixed  $value   Meta value.
 * @return bool
 */
function update_user_meta( $user_id, $key, $value ) {
	global $adz_test_meta;
	unset( $user_id );
	$adz_test_meta[ $key ] = $value;

	return true;
}

/**
 * Text-only markup stub.
 *
 * @param string $text Input text.
 * @return string
 */
function wp_strip_all_tags( $text ) {
	return preg_replace( '/<[^>]*>/', '', $text );
}

/**
 * Text-field sanitization stub.
 *
 * @param mixed $value Input value.
 * @return string
 */
function sanitize_text_field( $value ) {
	return trim( preg_replace( '/<[^>]*>/', '', (string) $value ) );
}

/**
 * Key sanitization stub.
 *
 * @param mixed $value Input value.
 * @return string
 */
function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

/**
 * Positive integer stub.
 *
 * @param mixed $value Input value.
 * @return int
 */
function absint( $value ) {
	return abs( (int) $value );
}

/**
 * JSON encoding stub.
 *
 * @param mixed $value Input value.
 * @return string
 */
function wp_json_encode( $value ) {
	return implode( '|', array_map( 'strval', $value ) );
}

/**
 * HTML escaping stub.
 *
 * @param mixed $value Input value.
 * @return string
 */
function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

require dirname( __DIR__ ) . '/includes/adzworld-journey.php';

$adz_test_count = 0;

/**
 * Assert an exact value.
 *
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value.
 * @param string $message  Failure message.
 * @return void
 */
function adz_test_same( $expected, $actual, $message ) {
	global $adz_test_count;
	++$adz_test_count;
	if ( $expected !== $actual ) {
		printf( "FAIL: %s\n", esc_html( $message ) );
		exit( 1 );
	}
}

adz_test_same(
	array( 'dislike', 'like' ),
	array_keys( adzworld_journey_responses() ),
	'Only explicit like and dislike responses are accepted.'
);

$item_disliked = 'Listing:ABC/9';
$item_liked    = 'Listing:ABC/10';
$refinement    = 'I want cats, not dogs — show hand-painted work.';

adzworld_journey_record_impression( 'main', 3, 'custom pet portraits', 'etsy', $item_disliked, 'custom pet portraits' );
$journeys = adzworld_journeys();
$key      = adzworld_journey_key( 'main', 3, 'custom pet portraits' );
adz_test_same( '', $journeys[ $key ]['rounds'][0]['response'], 'No response is stored for an impression.' );
adz_test_same( $item_disliked, $journeys[ $key ]['rounds'][0]['item_id'], 'Provider item punctuation is preserved.' );

adz_test_same(
	true,
	adzworld_journey_record_response( 'main', 3, 'custom pet portraits', 'etsy', $item_disliked, 'dislike' ),
	'A dislike is saved against the exact displayed provider item.'
);
adz_test_same(
	array( $item_disliked ),
	adzworld_journey_suppressed_items( 'main', 3, 'custom pet portraits', 'etsy' ),
	'The disliked item is suppressed only inside its journey.'
);

adzworld_journey_record_impression( 'main', 3, 'custom pet portraits', 'etsy', $item_liked, 'custom pet portraits cats' );
adz_test_same(
	true,
	adzworld_journey_record_response( 'main', 3, 'custom pet portraits', 'etsy', $item_liked, 'like' ),
	'A like is saved against the exact displayed provider item.'
);
adz_test_same(
	array( $item_disliked ),
	adzworld_journey_suppressed_items( 'main', 3, 'custom pet portraits', 'etsy' ),
	'A liked item is not suppressed.'
);

adz_test_same(
	true,
	adzworld_journey_record_refinement( 'main', 3, 'custom pet portraits', 'etsy', $item_liked, $refinement ),
	'A refinement attaches to the exact displayed provider item.'
);
$journeys = adzworld_journeys();
adz_test_same(
	$refinement,
	$journeys[ $key ]['rounds'][1]['refinement'],
	'The reader refinement is retained verbatim.'
);

adz_test_same(
	array(),
	adzworld_journey_suppressed_items( 'main', 4, 'personalized jewelry', 'etsy' ),
	'Feedback from one category and slot does not leak into another.'
);
adz_test_same(
	false,
	adzworld_journey_record_response( 'main', 3, 'custom pet portraits', 'etsy', $item_liked, 'neutral' ),
	'Neutral is not silently stored as an explicit response.'
);

printf( "PASS: %d journey feedback checks.\n", esc_html( $adz_test_count ) );
