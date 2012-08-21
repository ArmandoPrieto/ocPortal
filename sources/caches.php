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

/**
 * Standard code module initialisation function.
 */
function init__caches()
{
	global $BLOCK_CACHE_ON_CACHE;
	$BLOCK_CACHE_ON_CACHE=NULL;

	global $MEM_CACHE,$SITE_INFO;
	/** The persistent cache access object (NULL if there is no persistent cache).
	 * @global ?object $MEM_CACHE
	 */
	$MEM_CACHE=NULL;
	$use_memcache=((array_key_exists('use_mem_cache',$SITE_INFO)) && ($SITE_INFO['use_mem_cache']=='1'));// Default to off because badly configured caches can result in lots of very slow misses and lots of lost sessions || ((!array_key_exists('use_mem_cache',$SITE_INFO)) && ((function_exists('xcache_get')) || (function_exists('wincache_ucache_get')) || (function_exists('apc_fetch')) || (function_exists('eaccelerator_get')) || (function_exists('mmcache_get'))));
	if (($use_memcache) && ($GLOBALS['IN_MINIKERNEL_VERSION']!=1))
	{
		if (class_exists('Memcache'))
		{
			$MEM_CACHE=new Memcache();
			$MEM_CACHE->connect('localhost',11211) OR $MEM_CACHE=NULL;
		}
		elseif (function_exists('apc_fetch'))
		{
			require_code('caches_apc');

			$MEM_CACHE=new apccache();
			global $PERSISTENT_CACHE_OBJECTS_CACHE;
			$PERSISTENT_CACHE_OBJECTS_CACHE=apc_fetch(get_file_base().'PERSISTENT_CACHE_OBJECTS');
			if ($PERSISTENT_CACHE_OBJECTS_CACHE===false) $PERSISTENT_CACHE_OBJECTS_CACHE=array();
		}
		elseif ((function_exists('eaccelerator_put')) || (function_exists('mmcache_put')))
		{
			require_code('caches_eaccelerator');

			$MEM_CACHE=new eacceleratorcache();
			global $PERSISTENT_CACHE_OBJECTS_CACHE;
			if (function_exists('eaccelerator_get'))
				$PERSISTENT_CACHE_OBJECTS_CACHE=eaccelerator_get(get_file_base().'PERSISTENT_CACHE_OBJECTS');
			if (function_exists('mmcache_get'))
				$PERSISTENT_CACHE_OBJECTS_CACHE=mmcache_get(get_file_base().'PERSISTENT_CACHE_OBJECTS');
			if ($PERSISTENT_CACHE_OBJECTS_CACHE===NULL) $PERSISTENT_CACHE_OBJECTS_CACHE=array();
		}
		elseif (function_exists('xcache_get'))
		{
			require_code('caches_xcache');

			$MEM_CACHE=new xcache();
			global $PERSISTENT_CACHE_OBJECTS_CACHE;
			$PERSISTENT_CACHE_OBJECTS_CACHE=xcache_get(get_file_base().'PERSISTENT_CACHE_OBJECTS');
			if ($PERSISTENT_CACHE_OBJECTS_CACHE===false) $PERSISTENT_CACHE_OBJECTS_CACHE=array();
		}
		elseif (function_exists('wincache_ucache_get'))
		{
			require_code('caches_wincache');

			$MEM_CACHE=new wincache();
			global $PERSISTENT_CACHE_OBJECTS_CACHE;
			$PERSISTENT_CACHE_OBJECTS_CACHE=wincache_ucache_get(get_file_base().'PERSISTENT_CACHE_OBJECTS');
			if ($PERSISTENT_CACHE_OBJECTS_CACHE===false) $PERSISTENT_CACHE_OBJECTS_CACHE=array();
		}
		elseif (file_exists(get_custom_file_base().'/persistent_cache/'))
		{
			require_code('caches_filesystem');
			require_code('files');
			$MEM_CACHE=new filecache();
		}
	}
}

/**
 * Get data from the persistent cache.
 *
 * @param  mixed			Key
 * @param  ?TIME			Minimum timestamp that entries from the cache may hold (NULL: don't care)
 * @return ?mixed			The data (NULL: not found / NULL entry)
 */
function persistent_cache_get($key,$min_cache_date=NULL)
{
	global $MEM_CACHE;
	if (($GLOBALS['DEV_MODE']) && (mt_rand(0,3)==1)) return NULL;
	if ($MEM_CACHE===NULL) return NULL;
	$test=$MEM_CACHE->get(get_file_base().serialize($key),$min_cache_date); // First we'll try specifically for site
	if ($test!==NULL) return $test;
	return $MEM_CACHE->get(('ocp'.float_to_raw_string(ocp_version_number())).serialize($key),$min_cache_date); // And last we'll try server-wide
}

/**
 * Put data into the persistent cache.
 *
 * @param  mixed			Key
 * @param  mixed			The data
 * @param  boolean		Whether it is server-wide data
 * @param  ?integer		The expiration time in seconds. (NULL: Default expiry in 60 minutes, or never if it is server-wide).
 */
function persistent_cache_set($key,$data,$server_wide=false,$expire_secs=NULL)
{
	global $MEM_CACHE;
	if ($MEM_CACHE===NULL) return NULL;
	if ($expire_secs===NULL) $expire_secs=$server_wide?0:(60*60);
	$MEM_CACHE->set(($server_wide?('ocp'.float_to_raw_string(ocp_version_number())):get_file_base()).serialize($key),$data,0,$expire_secs);
}

/**
 * Delete data from the persistent cache.
 *
 * @param  mixed			Key name
 */
function persistent_cache_delete($key)
{
	global $MEM_CACHE;
	if ($MEM_CACHE===NULL) return NULL;
	$MEM_CACHE->delete(get_file_base().serialize($key));
	$MEM_CACHE->delete('ocp'.float_to_raw_string(ocp_version_number()).serialize($key));
}

/**
 * Remove all data from the persistent cache.
 */
function persistent_cache_empty()
{
	global $MEM_CACHE;
	if ($MEM_CACHE===NULL) return NULL;
	$MEM_CACHE->flush();
}

/**
 * Remove an item from the general cache (most commonly used for blocks).
 *
 * @param  ID_TEXT		The type of what we are cacheing (e.g. block name)
 * @param  ?array			A map of identifiying characteristics (NULL: no identifying characteristics, decache all)
 */
function decache($cached_for,$identifier=NULL)
{
	if (running_script('stress_test_loader')) return;
	if (get_page_name()=='admin_import') return;

	// NB: If we use persistent cache we still need to decache from DB, in case we're switching between for whatever reason. Or maybe some users use persistent cache and others don't. Or maybe some nodes do and others don't.

	if ($GLOBALS['MEM_CACHE']!==NULL)
	{
		persistent_cache_delete(array('CACHE',$cached_for));
	}

	$where=array('cached_for'=>$cached_for);
	if ($identifier!==NULL) $where['identifier']=md5(serialize($identifier));
	$GLOBALS['SITE_DB']->query_delete('cache',$where);

	if ($identifier!==NULL)
	{
		$where['identifier']=md5(serialize($identifier));
		$GLOBALS['SITE_DB']->query_delete('cache',$where);
	}
}

/**
 * Find the cache-on parameters for 'codename's cacheing style (prevents us needing to load up extra code to find it).
 *
 * @param  ID_TEXT		The codename of what will be checked for cacheing
 * @return ?array			The cached result (NULL: no cached result)
 */
function find_cache_on($codename)
{
	if (defined('HIPHOP_PHP')) return NULL;

	// See if we have it cached
	global $BLOCK_CACHE_ON_CACHE;
	if ($BLOCK_CACHE_ON_CACHE===NULL)
	{
		$BLOCK_CACHE_ON_CACHE=persistent_cache_get('BLOCK_CACHE_ON_CACHE');
		if ($BLOCK_CACHE_ON_CACHE===NULL)
		{
			$BLOCK_CACHE_ON_CACHE=$GLOBALS['SITE_DB']->query_select('cache_on',array('*'));
			persistent_cache_set('BLOCK_CACHE_ON_CACHE',$BLOCK_CACHE_ON_CACHE);
		}
	}
	foreach ($BLOCK_CACHE_ON_CACHE as $row)
	{
		if ($row['cached_for']==$codename)
		{
			return $row;
		}
	}
	return NULL;
}

/**
 * Find the cached result of what is named by codename and the further constraints.
 *
 * @param  ID_TEXT		The codename to check for cacheing
 * @param  LONG_TEXT		The further restraints (a serialized map)
 * @param  integer		The TTL for the cache entry
 * @param  boolean		Whether we are cacheing Tempcode (needs special care)
 * @param  boolean		Whether to defer caching to CRON. Note that this option only works if the block's defined cache signature depends only on $map (timezone and bot-type are automatically considered)
 * @param  ?array			Parameters to call up block with if we have to defer caching (NULL: none)
 * @return ?mixed			The cached result (NULL: no cached result)
 */
function get_cache_entry($codename,$cache_identifier,$ttl=10000,$tempcode=false,$caching_via_cron=false,$map=NULL) // Default to a very big ttl
{
	if ($GLOBALS['MEM_CACHE']!==NULL)
	{
		$pcache=persistent_cache_get(array('CACHE',$codename));
		if ($pcache===NULL) return NULL;
		$theme=$GLOBALS['FORUM_DRIVER']->get_theme();
		$lang=user_lang();
		$pcache=isset($pcache[$cache_identifier][$lang][$theme])?$pcache[$cache_identifier][$lang][$theme]:NULL;
		if ($pcache===NULL)
		{
			if ($caching_via_cron)
			{
				request_via_cron($codename,$map,$tempcode);
				return paragraph(do_lang_tempcode('CACHE_NOT_READY_YET'),'','nothing_here');
			}
			return NULL;
		}
		$cache_rows=array($pcache);
	} else
	{
		$cache_rows=$GLOBALS['SITE_DB']->query_select('cache',array('*'),array('lang'=>user_lang(),'cached_for'=>$codename,'the_theme'=>$GLOBALS['FORUM_DRIVER']->get_theme(),'identifier'=>md5($cache_identifier)),'',1);
		if (!isset($cache_rows[0])) // No
		{
			if ($caching_via_cron)
			{
				request_via_cron($codename,$map,$tempcode);
				return paragraph(do_lang_tempcode('CACHE_NOT_READY_YET'),'','nothing_here');
			}
			return NULL;
		}

		if ($tempcode)
		{
			$ob=new ocp_tempcode();
			if (!$ob->from_assembly($cache_rows[0]['the_value'],true)) return NULL;
			$cache_rows[0]['the_value']=$ob;
		} else
		{
			$cache_rows[0]['the_value']=unserialize($cache_rows[0]['the_value']);
		}
	}

	$stale=(($ttl!=-1) && (time()>($cache_rows[0]['date_and_time']+$ttl*60)));

	if ((!$caching_via_cron) && ($stale)) // Out of date
	{
		return NULL;
	} else // We can use directly
	{
		if ($stale)
			request_via_cron($codename,$map,$tempcode);

		$cache=$cache_rows[0]['the_value'];
		if ($cache_rows[0]['langs_required']!='')
		{
			$bits=explode('!',$cache_rows[0]['langs_required']);
			$langs_required=explode(':',$bits[0]); // Sometimes lang has got intertwinded with non cacheable stuff (and thus was itself not cached), so we need the lang files
			foreach ($langs_required as $lang)
				if ($lang!='') require_lang($lang,NULL,NULL,true);
			if (isset($bits[1]))
			{
				$javascripts_required=explode(':',$bits[1]);
				foreach ($javascripts_required as $javascript)
					if ($javascript!='') require_javascript($javascript);
			}
			if (isset($bits[2]))
			{
				$csss_required=explode(':',$bits[2]);
				foreach ($csss_required as $css)
					if ($css!='') require_css($css);
			}
		}
		return $cache;
	}
}

/**
 * Request that CRON loads up a block's caching in the background.
 *
 * @param  ID_TEXT		The codename of the block
 * @param  ?array			Parameters to call up block with if we have to defer caching (NULL: none)
 * @param  boolean		Whether we are cacheing Tempcode (needs special care)
 */
function request_via_cron($codename,$map,$tempcode)
{
	global $TEMPCODE_SETGET;
	$map=array(
		'c_theme'=>$GLOBALS['FORUM_DRIVER']->get_theme(),
		'c_lang'=>user_lang(),
		'c_codename'=>$codename,
		'c_map'=>serialize($map),
		'c_timezone'=>get_users_timezone(get_member()),
		'c_is_bot'=>is_null(get_bot_type())?0:1,
		'c_store_as_tempcode'=>$tempcode?1:0,
	);
	if (is_null($GLOBALS['SITE_DB']->query_select_value_if_there('cron_caching_requests','id',$map)))
		$GLOBALS['SITE_DB']->query_insert('cron_caching_requests',$map);
}
