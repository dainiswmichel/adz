<?php
/**
 * Cache Interface - Provides consistent interface for WP and Non WP Cache
 */

interface CacheInterface  {
	/**
	 * Adds or updates value to the cache
	 * @param  string  $key    Unique key that can be used to access cache
	 * @param mixed $value   value being cached
	 * @param integer $expires Number of seconds before cache expires
	 */
	public function set( $key, $value, $expires = 0 );

	/**
	 * Gets the value for the key
	 * @param  string $key
	 * @return mixed  cached value
	 */
	public function get( $key );

	/**
	 * Deletes the specified key from cache regardless of experation setting
	 * @param  string $key access key
	 * @return mixed      Value of key before it was deleted
	 */
	public function delete( $key );

	/**
	 * Identify cache for grouping purposes
	 * @param string $id Id of the cache
	 */
	public function set_id( $id );


}
