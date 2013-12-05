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

class Hook_addon_registry_quizzes
{
	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Construct competitions, surveys, and tests, for members to perform. Highly configurable, and comes with administrative tools to handle the results.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_quizzes',
		);
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(),
			'recommends'=>array(),
			'conflicts_with'=>array()
		);
	}

	/**
	 * Explicitly say which icon should be used
	 *
	 * @return URLPATH		Icon
	 */
	function get_default_icon()
	{
		return 'themes/default/images/icons/48x48/menu/rich_content/quiz.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images/icons/24x24/menu/cms/quiz/find_winners.png',
			'themes/default/images/icons/24x24/menu/cms/quiz/survey_results.png',
			'themes/default/images/icons/48x48/menu/cms/quiz/find_winners.png',
			'themes/default/images/icons/48x48/menu/cms/quiz/survey_results.png',
			'themes/default/images/icons/24x24/menu/rich_content/quiz.png',
			'themes/default/images/icons/48x48/menu/rich_content/quiz.png',
			'themes/default/images/icons/24x24/menu/cms/quiz/index.html',
			'themes/default/images/icons/48x48/menu/cms/quiz/index.html',
			'sources/hooks/systems/notifications/quiz_results.php',
			'sources/hooks/systems/config/points_ADD_QUIZ.php',
			'sources/hooks/systems/config/quiz_show_stats_count_total_open.php',
			'sources/hooks/systems/meta/quiz.php',
			'sources/hooks/blocks/side_stats/stats_quiz.php',
			'themes/default/templates/QUIZ_ANSWERS_MAIL.tpl',
			'themes/default/templates/QUIZ_ARCHIVE_SCREEN.tpl',
			'themes/default/templates/QUIZ_TEST_ANSWERS_MAIL.tpl',
			'sources/hooks/systems/content_meta_aware/quiz.php',
			'sources/hooks/systems/occle_fs/quizzes.php',
			'sources/hooks/systems/addon_registry/quizzes.php',
			'sources/hooks/modules/admin_import_types/quizzes.php',
			'themes/default/templates/QUIZ_BOX.tpl',
			'themes/default/templates/QUIZ_SCREEN.tpl',
			'themes/default/templates/QUIZ_DONE_SCREEN.tpl',
			'themes/default/templates/SURVEY_RESULTS_SCREEN.tpl',
			'adminzone/pages/modules/admin_quiz.php',
			'cms/pages/modules/cms_quiz.php',
			'lang/EN/quiz.ini',
			'site/pages/modules/quiz.php',
			'sources/hooks/systems/sitemap/quiz.php',
			'sources/hooks/modules/admin_newsletter/quiz.php',
			'sources/hooks/modules/admin_unvalidated/quiz.php',
			'sources/hooks/modules/search/quiz.php',
			'sources/hooks/systems/page_groupings/quiz.php',
			'sources/quiz.php',
			'sources/quiz2.php',
			'sources/hooks/systems/preview/quiz.php',
			'themes/default/css/quizzes.css',
		);
	}


	/**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array			The mapping
	 */
	function tpl_previews()
	{
		return array(
			'SURVEY_RESULTS_SCREEN.tpl'=>'survey_results_screen',
			'QUIZ_BOX.tpl'=>'quiz_archive_screen',
			'QUIZ_ARCHIVE_SCREEN.tpl'=>'quiz_archive_screen',
			'QUIZ_SCREEN.tpl'=>'quiz_screen',
			'QUIZ_TEST_ANSWERS_MAIL.tpl'=>'quiz_test_answers_mail',
			'QUIZ_ANSWERS_MAIL.tpl'=>'quiz_answers_mail',
			'QUIZ_DONE_SCREEN.tpl'=>'quiz_done_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__survey_results_screen()
	{
		$fields=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$fields->attach(do_lorem_template('MAP_TABLE_FIELD_RAW',array(
				'ABBR'=>'',
				'NAME'=>lorem_phrase(),
				'VALUE'=>lorem_phrase()
			)));
		}
		$summary=do_lorem_template('MAP_TABLE',array(
			'WIDTH'=>placeholder_number(),
			'FIELDS'=>$fields
		));

		return array(
			lorem_globalise(do_lorem_template('SURVEY_RESULTS_SCREEN',array(
				'TITLE'=>lorem_title(),
				'SUMMARY'=>$summary,
				'RESULTS'=>placeholder_table(),
			)),NULL,'',true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__quiz_archive_screen()
	{
		$content_tests=new ocp_tempcode();
		$content_competitions=new ocp_tempcode();
		$content_surveys=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$link=do_lorem_template('QUIZ_BOX',array(
				'TYPE'=>lorem_word(),
				'DATE'=>placeholder_time(),
				'URL'=>placeholder_url(),
				'NAME'=>lorem_phrase(),
				'START_TEXT'=>lorem_phrase(),
				'TIMEOUT'=>placeholder_number(),
				'REDO_TIME'=>placeholder_number(),
				'_TYPE'=>lorem_word(),
				'POINTS'=>placeholder_id(),
				'GIVE_CONTEXT'=>true,
			));
		}
		$content_surveys->attach($link);
		$content_tests->attach($link);
		$content_competitions->attach($link);

		return array(
			lorem_globalise(do_lorem_template('QUIZ_ARCHIVE_SCREEN',array(
				'TITLE'=>lorem_title(),
				'CONTENT_SURVEYS'=>$content_surveys,
				'CONTENT_COMPETITIONS'=>$content_competitions,
				'CONTENT_TESTS'=>$content_tests,
				'PAGINATION'=>placeholder_pagination()
			)),NULL,'',true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__quiz_screen()
	{
		//This is for getting the do_ajax_request() javascript function.
		require_javascript('javascript_ajax');

		$warning_details=do_lorem_template('WARNING_BOX',array(
			'WARNING'=>lorem_phrase()
		));

		return array(
			lorem_globalise(do_lorem_template('QUIZ_SCREEN',array(
				'TAGS'=>lorem_word_html(),
				'ID'=>placeholder_id(),
				'WARNING_DETAILS'=>$warning_details,
				'URL'=>placeholder_url(),
				'TITLE'=>lorem_title(),
				'START_TEXT'=>lorem_sentence_html(),
				'FIELDS'=>placeholder_fields(),
				'TIMEOUT'=>'5',
				'EDIT_URL'=>placeholder_url()
			)),NULL,'',true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__quiz_test_answers_mail()
	{
		$_unknowns=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$_unknowns->attach(lorem_phrase());
		}

		$_corrections=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$_corrections->attach(lorem_phrase());
		}

		return array(
			lorem_globalise(do_lorem_template('QUIZ_TEST_ANSWERS_MAIL',array(
				'UNKNOWNS'=>$_unknowns,
				'CORRECTIONS'=>$_corrections,
				'RESULT'=>lorem_phrase(),
				'USERNAME'=>lorem_phrase()
			)),NULL,'',true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__quiz_answers_mail()
	{
		$_answers=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$_answers->attach(lorem_phrase());
		}

		return array(
			lorem_globalise(do_lorem_template('QUIZ_ANSWERS_MAIL',array(
				'ANSWERS'=>$_answers,
				'MEMBER_PROFILE_URL'=>placeholder_url(),
				'USERNAME'=>lorem_phrase(),
				'FORUM_DRIVER'=>NULL
			)),NULL,'',true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__quiz_done_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('QUIZ_DONE_SCREEN',array(
				'RESULT'=>lorem_phrase(),
				'TITLE'=>lorem_title(),
				'TYPE'=>lorem_phrase(),
				'MESSAGE'=>lorem_phrase(),
				'CORRECTIONS_TO_SHOW'=>lorem_phrase(),
				'POINTS_DIFFERENCE'=>placeholder_number()
			)),NULL,'',true)
		);
	}
}
