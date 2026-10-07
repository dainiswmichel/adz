<?php
require_once __DIR__ . '/cache-interface.php';
/**
 * WP Cacheing functins wrapped in a standardized interface
 * Uses WordPress object cache with expiration
 */

class WPCacheImpl implements CacheInterface  {

	protected $cache_id;

	function __construct( $cache_id = null ) {
		if ( ! $cache_id ) {
			$this->cache_id = 'adz_default_cache_id';
		} else {
			$this->cache_id = $cache_id;
		}
	}

	/**
	 * Adds or updates value to the cache
	 * @param  string  $key    Unique key that can be used to access cache
	 * @param mixed $value   value being cached
	 * @param integer $expires Number of seconds before cache expires
	 */
	public function set( $key, $value, $expires = 0 ) {
		if ( ! set_transient( $key, $value, $expires )  ) {
			 error_log( "Cache Failure $key:" . var_export( $value, true ) );
		} else {
			$cache_keys = get_adz_session( $this->cache_id , array() );
			$cache_keys[] = $key;
			update_adz_session( $this->cache_id , $cache_keys );
		}
	}

	/**
	 * Gets the value for the key
	 * @param  string $key
	 * @return mixed  cached value
	 */
	public function get( $key ) {
		return get_transient( $key );
	}

	/**
	 * Deletes the specified key from cache regardless of experation setting
	 * @param  string $key access key
	 * @return mixed      Value of key before it was deleted
	 */
	public function delete( $key ) {
		 delete_transient( $key );
	}

	/**
	 * Identify cache for grouping purposes
	 * @param string $id Id of the cache
	 */
	public function set_id( $id ) {
		$this->cache_id = $id;
	}

	public function clear_cache( $id = false ) {
		if ( ! $id ) {
			$id = $this->cache_id;
		}
		$cache_keys = get_adz_session( $id , array() );
		if ( is_array( $cache_keys ) ) {
			foreach ( $cache_keys as $key ) {
				$this->delete( $key );
			}
		}
	}

}
