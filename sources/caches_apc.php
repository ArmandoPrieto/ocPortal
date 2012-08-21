<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core
 */

/*EXTRA FUNCTIONS: apc\_.+*/

/**
 * Cache Driver.
 * @package		core
 */
class apccache
{
	/**
	 * (Plug-in replacement for memcache API) Get data from the persistent cache.
	 *
	 * @param  mixed			Key
	 * @param  ?TIME			Minimum timestamp that entries from the cache may hold (NULL: don't care)
	 * @return ?mixed			The data (NULL: not found / NULL entry)
	 */
	function get($key,$min_cache_date=NULL)
	{
		$data=apc_fetch($key);
		if ($data===false) return NULL;
		if (($min_cache_date!==NULL) && ($data[0]<$min_cache_date)) return NULL;
		return $data[1];
	}

	/**
	 * (Plug-in replacement for memcache API) Put data into the persistent cache.
	 *
	 * @param  mixed			Key
	 * @param  mixed			The data
	 * @param  integer		Various flags (parameter not used)
	 * @param  integer		The expiration time in seconds.
	 */
	function set($key,$data,$flags,$expire_secs)
	{
		unset($flags);

		// Update list of e-objects
		global $PERSISTENT_CACHE_OBJECTS_CACHE;
		if (!isset($PERSISTENT_CACHE_OBJECTS_CACHE[$key]))
		{
			$PERSISTENT_CACHE_OBJECTS_CACHE[$key]=1;
			@apc_store(get_file_base().'PERSISTENT_CACHE_OBJECTS',$PERSISTENT_CACHE_OBJECTS_CACHE,0);
		}

		@apc_store($key,array(time(),$data),$expire_secs);
	}

	/**
	 * (Plug-in replacement for memcache API) Delete data from the persistent cache.
	 *
	 * @param  mixed			Key name
	 */
	function delete($key)
	{
		// Update list of e-objects
		global $PERSISTENT_CACHE_OBJECTS_CACHE;
		unset($PERSISTENT_CACHE_OBJECTS_CACHE[$key]);

		@apc_store(get_file_base().'PERSISTENT_CACHE_OBJECTS',$PERSISTENT_CACHE_OBJECTS_CACHE,0);

		apc_delete($key);
	}

	/**
	 * (Plug-in replacement for memcache API) Remove all data from the persistent cache.
	 */
	function flush()
	{
		apc_clear_cache('user');
	}
}
