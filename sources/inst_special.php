<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

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

/*
These are special functions used by the installer and upgrader.
*/

/**
 * Get the list of files that need CHmodding for write access.
 *
 * @return array			The list of files
 */
function get_chmod_array()
{
	global $LANG;

//	if ((function_exists('ocp_enterprise')) && (ocp_enterprise()))
//	{
		$extra_files=array('collaboration/pages/modules_custom','collaboration/pages/html_custom','collaboration/pages/html_custom/'.$LANG,'collaboration/pages/comcode_custom','collaboration/pages/comcode_custom/'.$LANG,'collaboration/pages/minimodules_custom',);
//	} else $extra_files=array();
	
	if (function_exists('find_all_hooks'))
	{
		$hooks=find_all_hooks('systems','addon_registry');
		$hook_keys=array_keys($hooks);
		foreach ($hook_keys as $hook)
		{
			//require_code('hooks/systems/addon_registry/'.filter_naughty_harsh($hook));
			//$object=object_factory('Hook_addon_registry_'.filter_naughty_harsh($hook));
			//$extra_files=array_merge($extra_files,$object->get_chmod_array());
			
			// Save memory compared to above commented code...
			
			$path=get_custom_file_base().'/sources/hooks/systems/addon_registry/'.filter_naughty_harsh($hook).'.php';
			if (!file_exists($path))
			{
				$path=get_file_base().'/sources/hooks/systems/addon_registry/'.filter_naughty_harsh($hook).'.php';
			}
			$matches=array();
			if (preg_match('#function get_chmod_array\(\)\s*\{([^\}]*)\}#',file_get_contents($path),$matches)!=0)
			{
				if (!defined('HIPHOP_PHP'))
				{
					$extra_files=array_merge($extra_files,eval($matches[1]));
				} else
				{
					require_code('hooks/systems/addon_registry/'.$hook);
					$hook=object_factory('Hook_addon_registry_'.$hook);
					$extra_files=array_merge($extra_files,$hook->get_chmod_array());
				}
			}
		}
	}

	return array_merge($extra_files,array(
						'safe_mode_temp','persistant_cache','data_custom/modules/admin_backup','data_custom/modules/chat','data_custom/fields.xml','data_custom/breadcrumbs.xml','data_custom/modules/admin_stats','data_custom/spelling/write.log','data_custom/spelling/output.log','data_custom/spelling/personal_dicts',
						'themes/map.ini','text_custom','text_custom/'.$LANG,
						'data_custom/modules/chat/chat_last_msg.dat','data_custom/modules/chat/chat_last_event.dat',
						'lang_cached','lang_cached/'.$LANG,'lang_custom','lang_custom/'.$LANG,
						'data_custom/errorlog.php','ocp_sitemap.xml','data_custom/permissioncheckslog.php','data_custom/functions.dat',
						'pages/minimodules_custom','site/pages/minimodules_custom','docs/pages/minimodules_custom','adminzone/pages/minimodules_custom','forum/pages/minimodules_custom','cms/pages/minimodules_custom',
						'pages/modules_custom','site/pages/modules_custom','docs/pages/modules_custom','adminzone/pages/modules_custom','forum/pages/modules_custom','cms/pages/modules_custom',
						'pages/html_custom','site/pages/html_custom','docs/pages/html_custom','adminzone/pages/html_custom','forum/pages/html_custom','cms/pages/html_custom',
						'pages/html_custom/'.$LANG,'site/pages/html_custom/'.$LANG,'docs/pages/html_custom/'.$LANG,'adminzone/pages/html_custom/'.$LANG,'forum/pages/html_custom/'.$LANG,'cms/pages/html_custom/'.$LANG,
						'pages/comcode_custom','site/pages/comcode_custom','docs/pages/comcode_custom','adminzone/pages/comcode_custom','forum/pages/comcode_custom','cms/pages/comcode_custom',
						'pages/comcode_custom/'.$LANG,'site/pages/comcode_custom/'.$LANG,'docs/pages/comcode_custom/'.$LANG,'adminzone/pages/comcode_custom/'.$LANG,'forum/pages/comcode_custom/'.$LANG,'cms/pages/comcode_custom/'.$LANG,
						'themes/default/css_custom','themes/default/images_custom','themes/default/templates_custom','themes/default/templates_cached','themes/default/templates_cached/'.$LANG,'themes/default/theme.ini',
						'uploads/incoming','uploads/banners','uploads/downloads','uploads/galleries','uploads/watermarks','uploads/grepimages','uploads/galleries_thumbs','uploads/iotds','uploads/iotds_thumbs','uploads/catalogues','uploads/attachments','uploads/attachments_thumbs','uploads/auto_thumbs','uploads/ocf_avatars','uploads/ocf_cpf_upload','uploads/ocf_photos','uploads/ocf_photos_thumbs','uploads/filedump',
						'data_custom/temp','info.php','exports/backups','exports/file_backups','exports/mods','imports/mods',
						'site/pages/html_custom/'.$LANG.'/download_tree_made.htm','site/pages/html_custom/'.$LANG.'/cedi_tree_made.htm'
					));
}


