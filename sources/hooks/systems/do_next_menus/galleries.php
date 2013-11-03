<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		galleries
 */


class Hook_do_next_menus_galleries
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @return array			Array of links and where to show
	 */
	function run()
	{
		if (!addon_installed('galleries')) return array();

		return array(
			array('cms','menu/rich_content/galleries',array('cms_galleries',array('type'=>'misc'),get_module_zone('cms_galleries')),do_lang_tempcode('ITEMS_HERE',do_lang_tempcode('galleries:GALLERIES'),make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value_if_there('images','COUNT(*)',NULL,'',true)+$GLOBALS['SITE_DB']->query_select_value_if_there('videos','COUNT(*)',NULL,'',true))))),'galleries:DOC_GALLERIES'),
			array('rich_content','menu/rich_content/galleries',array('galleries',array(),get_module_zone('galleries')),do_lang_tempcode('galleries:GALLERIES')),
		);
	}

}


