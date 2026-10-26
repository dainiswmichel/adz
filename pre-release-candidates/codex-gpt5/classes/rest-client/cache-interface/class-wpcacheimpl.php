<?php
/**
 * Group-scoped WordPress object-cache adapter.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
require_once __DIR__ . '/cache-interface.php';
/**
 * Grouped cache with generation-based invalidation.
 *
 * Values stay in WordPress's object cache, not the plugin's shared session option.
 */
class WPCacheImpl implements CacheInterface {

	/**
	 * Cache group identifier.
	 *
	 * @var string
	 */
	protected $cache_id;

	/**
	 * Initialize the group.
	 *
	 * @param string|null $cache_id Group identifier.
	 */
	public function __construct( $cache_id = null ) {
		if ( ! $cache_id ) {
			$this->cache_id = 'adz_default_cache_id';
		} else {
			$this->cache_id = $cache_id;
		}
	}

	/**
	 * Add or update a cached value without logging its private payload.
	 *
	 * @param string $key Unique cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $expires Expiration in seconds.
	 * @return bool Whether the value was stored.
	 */
	public function set( $key, $value, $expires = 0 ) {
		return wp_cache_set( $this->key( $key ), $value, $this->cache_id, $expires );
	}

	/**
	 * Get the cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed Cached value, or false on a miss.
	 */
	public function get( $key ) {
		return wp_cache_get( $this->key( $key ), $this->cache_id );
	}

	/**
	 * Delete a cached value.
	 *
	 * @param string $key Cache key.
	 * @return bool Whether the value was deleted.
	 */
	public function delete( $key ) {
		return wp_cache_delete( $this->key( $key ), $this->cache_id );
	}

	/**
	 * Select the cache group.
	 *
	 * @param string $id Group identifier.
	 */
	public function set_id( $id ) {
		$this->cache_id = $id;
	}

	/**
	 * Invalidate the group without flushing unrelated WordPress caches.
	 *
	 * @param string|false $id Optional alternate group.
	 * @return bool Whether a generation was stored.
	 */
	public function clear_cache( $id = false ) {
		if ( ! $id ) {
			$id = $this->cache_id;
		}
		return wp_cache_set( 'adzworld_generation', wp_generate_uuid4(), $id );
	}

	/**
	 * Resolve a key in the group's current generation.
	 *
	 * @param string $key Caller-provided key.
	 * @return string Namespaced cache key.
	 */
	protected function key( $key ) {
		$generation = wp_cache_get( 'adzworld_generation', $this->cache_id );
		if ( false === $generation ) {
			wp_cache_add( 'adzworld_generation', wp_generate_uuid4(), $this->cache_id );
			$generation = wp_cache_get( 'adzworld_generation', $this->cache_id );
		}
		return hash( 'sha256', $generation . ':' . $key );
	}
}
