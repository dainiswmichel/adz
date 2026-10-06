<?php
/**
 * Minimal WordPress stubs so the plugin's logic can be exercised without a
 * WordPress install.
 *
 * This is not a substitute for integration testing against real WordPress. It
 * exists to prove the parts that carry the security and correctness fixes:
 * view records go through a real SQL engine, so an injection attempt either
 * works or it does not.
 *
 * @package adz.world
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'COOKIEPATH', '/' );
define( 'COOKIE_DOMAIN', '' );
define( 'ADZ_WORLD', dirname( __DIR__ ) . '/' );

$GLOBALS['adz_test_options']    = array();
$GLOBALS['adz_test_transients'] = array();
$GLOBALS['adz_test_posts']      = array();
$GLOBALS['adz_test_meta']       = array();
$GLOBALS['adz_test_user']       = 0;

/** Stand-in for WP_Post. */
class WP_Post {
	public $ID;
	public $post_type;
	public $post_status;
	public $post_content;
	public $post_title;

	public function __construct( $data ) {
		foreach ( $data as $k => $v ) {
			$this->$k = $v;
		}
	}
}

/**
 * $wpdb emulation over PDO SQLite.
 *
 * prepare() implements the %s/%d placeholder substitution with the same
 * quoting discipline WordPress uses, so an injected quote is neutralised here
 * exactly as it would be in production.
 */
class Adz_Test_WPDB {
	public $prefix = 'wp_';
	public $pdo;
	public $last_error = '';

	public function __construct() {
		$this->pdo = new PDO( 'sqlite::memory:' );
		$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	}

	public function get_charset_collate() {
		return '';
	}

	public function prepare( $query, ...$args ) {
		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$query = str_replace( array( '%s', '%d' ), array( "'%s'", '%d' ), $query );
		$query = preg_replace( '/\'\'%s\'\'/', "'%s'", $query );

		$i = 0;
		return preg_replace_callback(
			'/%[sd]/',
			function ( $m ) use ( &$i, $args ) {
				$v = isset( $args[ $i ] ) ? $args[ $i ] : '';
				$i++;
				if ( '%d' === $m[0] ) {
					return (string) (int) $v;
				}
				// WordPress escapes the value; the surrounding quotes are already
				// in the query string at this point.
				return str_replace( array( "\\", "'" ), array( "\\\\", "''" ), (string) $v );
			},
			$query
		);
	}

	public function query( $sql ) {
		return $this->pdo->exec( $sql );
	}

	public function get_row( $sql ) {
		$stmt = $this->pdo->query( $sql );
		$row  = $stmt->fetch( PDO::FETCH_OBJ );
		return $row ? $row : null;
	}

	public function get_results( $sql ) {
		return $this->pdo->query( $sql )->fetchAll( PDO::FETCH_OBJ );
	}

	public function get_var( $sql ) {
		$stmt = $this->pdo->query( $sql );
		$row  = $stmt->fetch( PDO::FETCH_NUM );
		return $row ? $row[0] : null;
	}

	public function insert( $table, $data, $formats = array() ) {
		$cols         = array_keys( $data );
		$placeholders = implode( ',', array_fill( 0, count( $cols ), '?' ) );
		$sql          = "INSERT INTO {$table} (" . implode( ',', $cols ) . ") VALUES ({$placeholders})";
		$stmt         = $this->pdo->prepare( $sql );
		return $stmt->execute( array_values( $data ) );
	}

	public function update( $table, $data, $where, $df = array(), $wf = array() ) {
		$set = implode( ',', array_map( fn( $c ) => "{$c}=?", array_keys( $data ) ) );
		$cnd = implode( ' AND ', array_map( fn( $c ) => "{$c}=?", array_keys( $where ) ) );
		$stmt = $this->pdo->prepare( "UPDATE {$table} SET {$set} WHERE {$cnd}" );
		return $stmt->execute( array_merge( array_values( $data ), array_values( $where ) ) );
	}
}

$GLOBALS['wpdb'] = new Adz_Test_WPDB();

// --- Option / transient API -------------------------------------------------

function get_option( $k, $d = false ) {
	return array_key_exists( $k, $GLOBALS['adz_test_options'] ) ? $GLOBALS['adz_test_options'][ $k ] : $d;
}
function update_option( $k, $v, $autoload = null ) {
	$GLOBALS['adz_test_options'][ $k ] = $v;
	return true;
}
function add_option( $k, $v ) {
	if ( ! array_key_exists( $k, $GLOBALS['adz_test_options'] ) ) {
		$GLOBALS['adz_test_options'][ $k ] = $v;
	}
	return true;
}
function delete_option( $k ) {
	unset( $GLOBALS['adz_test_options'][ $k ] );
	return true;
}
function get_transient( $k ) {
	if ( ! isset( $GLOBALS['adz_test_transients'][ $k ] ) ) {
		return false;
	}
	list( $value, $expires ) = $GLOBALS['adz_test_transients'][ $k ];
	if ( $expires && $expires < time() ) {
		unset( $GLOBALS['adz_test_transients'][ $k ] );
		return false;
	}
	return $value;
}
function set_transient( $k, $v, $ttl = 0 ) {
	$GLOBALS['adz_test_transients'][ $k ] = array( $v, $ttl ? time() + $ttl : 0 );
	return true;
}

// --- Users ------------------------------------------------------------------

function is_user_logged_in() {
	return $GLOBALS['adz_test_user'] > 0;
}
function get_current_user_id() {
	return $GLOBALS['adz_test_user'];
}

// --- Posts ------------------------------------------------------------------

function get_post( $id ) {
	$id = (int) $id;
	return isset( $GLOBALS['adz_test_posts'][ $id ] ) ? $GLOBALS['adz_test_posts'][ $id ] : null;
}
function get_posts( $args ) {
	$out = array();
	foreach ( $GLOBALS['adz_test_posts'] as $p ) {
		if ( isset( $args['post_type'] ) && $p->post_type !== $args['post_type'] ) {
			continue;
		}
		if ( isset( $args['post_status'] ) && $p->post_status !== $args['post_status'] ) {
			continue;
		}
		if ( isset( $args['meta_query'] ) ) {
			$mq  = $args['meta_query'][0];
			$val = get_post_meta( $p->ID, $mq['key'], true );
			if ( (string) $val !== (string) $mq['value'] ) {
				continue;
			}
		}
		$out[] = $p->ID;
	}
	if ( isset( $args['posts_per_page'] ) && $args['posts_per_page'] > 0 ) {
		$out = array_slice( $out, 0, $args['posts_per_page'] );
	}
	return $out;
}
function get_post_meta( $id, $key, $single = false ) {
	$v = isset( $GLOBALS['adz_test_meta'][ $id ][ $key ] ) ? $GLOBALS['adz_test_meta'][ $id ][ $key ] : '';
	return $single ? $v : array( $v );
}
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['adz_test_meta'][ $id ][ $key ] = $value;
	return true;
}

// --- Misc -------------------------------------------------------------------

function current_time( $type ) {
	return 'mysql' === $type ? gmdate( 'Y-m-d H:i:s' ) : gmdate( $type );
}
function wp_generate_password( $len = 12, $special = true, $extra = false ) {
	return substr( bin2hex( random_bytes( $len ) ), 0, $len );
}
function wp_hash( $data ) {
	return hash_hmac( 'md5', $data, 'test-salt' );
}
function wp_unslash( $v ) {
	return is_string( $v ) ? stripslashes( $v ) : $v;
}
function sanitize_key( $k ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) );
}
function sanitize_text_field( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function is_ssl() {
	return false;
}
function wp_json_encode( $d ) {
	return json_encode( $d );
}
function apply_filters( $tag, $value ) {
	return $value;
}
function add_action() {}
function add_filter() {}
function plugin_dir_path( $f ) {
	return dirname( $f ) . '/';
}
function dbDelta( $sql ) {
	// Translate the MySQL CREATE TABLE the plugin ships into SQLite dialect so
	// the real query code runs against a real SQL engine.
	$sql = preg_replace( '/bigint\(\d+\)/i', 'INTEGER', $sql );
	$sql = preg_replace( '/varchar\(\d+\)/i', 'TEXT', $sql );
	$sql = preg_replace( '/\bdatetime\b/i', 'TEXT', $sql );
	$sql = preg_replace( '/\bdate\b(?!\w)/i', 'TEXT', $sql );
	$sql = str_replace( 'INTEGER NOT NULL AUTO_INCREMENT', 'INTEGER', $sql );
	$sql = preg_replace( '/,\s*PRIMARY KEY\s+\(id\)/i', '', $sql );
	// Anchor on the standalone "id" column: target_id / targate_id also end in
	// "id" and must not pick up the primary key.
	$sql = preg_replace( '/(?<![\w])id INTEGER/', 'id INTEGER PRIMARY KEY AUTOINCREMENT', $sql, 1 );
	$sql = preg_replace( '/,\s*KEY\s+\w+\s*\([^)]*\)/i', '', $sql );
	$sql = preg_replace( '/\)\s*;?\s*$/', ')', trim( $sql ) );

	$GLOBALS['wpdb']->query( $sql );
}
