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
 * @package		quizzes
 */


class Hook_do_next_menus_quiz
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @return array			Array of links and where to show
	 */
	function run()
	{
		if (!addon_installed('quizzes')) return array();

		return array(
			array('audit','menu/rich_content/quiz',array('admin_quiz',array('type'=>'misc'),get_module_zone('admin_quiz')),do_lang_tempcode('quiz:QUIZZES'),'quiz:DOC_QUIZZES'),
			array('cms','menu/rich_content/quiz',array('cms_quiz',array('type'=>'misc'),get_module_zone('cms_quiz')),do_lang_tempcode('ITEMS_HERE',do_lang_tempcode('quiz:QUIZZES'),make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value_if_there('quizzes','COUNT(*)',NULL,'',true))))),'quiz:DOC_QUIZZES'),
			array('rich_content','menu/rich_content/quiz',array('quiz',array(),get_module_zone('quiz')),do_lang_tempcode('quiz:QUIZZES')),
		);
	}

}


