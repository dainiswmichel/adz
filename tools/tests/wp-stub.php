<?php
// Minimal WordPress surface, only what the two files touch. Not a WP emulation:
// enough to exercise the parser, the chips and the cookie round trip off-WP.
define( 'ABSPATH', '/stub/' );
define( 'COOKIEPATH', '/' );
define( 'COOKIE_DOMAIN', '' );
define( 'YEAR_IN_SECONDS', 31536000 );

function sanitize_text_field( $str ) {
	$str = wp_strip_all_tags( (string) $str );
	$str = preg_replace( '/[\r\n\t ]+/', ' ', $str );
	$str = preg_replace( '/%[a-f0-9]{2}/i', '', $str );
	return trim( $str );
}
function wp_strip_all_tags( $str ) {
	return trim( strip_tags( (string) $str ) );
}
function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}
function wp_unslash( $v ) {
	return is_string( $v ) ? stripslashes( $v ) : $v;
}
function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, (array) $args );
}
function is_user_logged_in() {
	return false;
}
function get_current_user_id() {
	return 0;
}
function is_ssl() {
	return true;
}
function get_user_meta() {
	return '';
}
function update_user_meta() {
	return true;
}
function delete_user_meta() {
	return true;
}
function wp_get_post_terms() {
	return array();
}
function is_wp_error( $t ) {
	return false;
}
function wp_unique_id( $p = '' ) {
	static $i = 0;
	return $p . ++$i;
}
function __( $s ) {
	return $s;
}
function esc_html__( $s ) {
	return $s;
}
function esc_html( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES );
}
function esc_attr( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES );
}
function esc_html_e( $s ) {
	echo esc_html( $s );
}
function esc_attr_e( $s ) {
	echo esc_attr( $s );
}
function wp_nonce_field() {
	echo '<input type="hidden" name="_n" value="stub">';
}
function wp_send_json_success( $d = null ) {
	throw new Exception( 'json_success' );
}
function wp_send_json_error( $d = null, $c = 0 ) {
	throw new Exception( 'json_error' );
}
function add_action() {}
function apply_filters( $t, $v ) {
	return $v;
}
function number_format_i18n( $n, $d = 0 ) {
	return number_format( (float) $n, (int) $d );
}
function absint( $n ) {
	return abs( (int) $n );
}
