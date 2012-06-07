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
 * @package		points
 */

class Hook_addon_registry_points
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
		return 'Allow members to accumulate points via a number of configurable activities, as well as exchange points with each other. Points act as a ranking system as well as a virtual currency.';
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
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'POINTS_PROFILE.tpl',
			'sources/hooks/systems/notifications/received_points.php',
			'sources/hooks/systems/notifications/receive_points_staff.php',
			'sources/hooks/systems/config_default/leaderboard_start_date.php',
			'sources/hooks/systems/config_default/points_joining.php',
			'sources/hooks/systems/config_default/points_per_daily_visit.php',
			'sources/hooks/systems/config_default/points_per_day.php',
			'sources/hooks/systems/config_default/points_posting.php',
			'sources/hooks/systems/config_default/points_rating.php',
			'sources/hooks/systems/config_default/points_show_personal_stats_gift_points_left.php',
			'sources/hooks/systems/config_default/points_show_personal_stats_gift_points_used.php',
			'sources/hooks/systems/config_default/points_show_personal_stats_points_left.php',
			'sources/hooks/systems/config_default/points_show_personal_stats_points_used.php',
			'sources/hooks/systems/config_default/points_show_personal_stats_total_points.php',
			'sources/hooks/systems/config_default/points_voting.php',
			'sources/hooks/systems/realtime_rain/points.php',
			'sources/hooks/modules/admin_setupwizard/leader_board.php',
			'themes/default/images/pagepics/points.png',
			'sources/hooks/systems/addon_registry/points.php',
			'sources/hooks/modules/admin_import_types/points.php',
			'sources/hooks/systems/profiles_tabs/points.php',
			'sources/points3.php',
			'POINTS_GIVE.tpl',
			'POINTS_SCREEN.tpl',
			'POINTS_SEARCH_SCREEN.tpl',
			'POINTS_SEARCH_RESULT.tpl',
			'POINTS_TRANSACTIONS_WRAP.tpl',
			'POINTS_LEADERBOARD.tpl',
			'POINTS_LEADERBOARD_SCREEN.tpl',
			'POINTS_LEADERBOARD_ROW.tpl',
			'POINTS_LEADERBOARD_WEEK.tpl',
			'adminzone/pages/modules/admin_points.php',
			'points.css',
			'themes/default/images/bigicons/pointslog.png',
			'themes/default/images/EN/pageitem/points.png',
			'lang/EN/points.ini',
			'site/pages/modules/points.php',
			'sources/hooks/blocks/main_staff_checklist/points.php',
			'sources/hooks/systems/do_next_menus/points.php',
			'sources/hooks/systems/ocf_cpf_filter/points.php',
			'sources/hooks/systems/rss/points.php',
			'sources/points.php',
			'sources/points2.php',
			'sources/hooks/modules/admin_occle_commands/give.php',
			'site/pages/modules/leader_board.php',
			'sources/blocks/main_leader_board.php',
			'lang/EN/leader_board.ini'
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
			'POINTS_LEADERBOARD_ROW.tpl'=>'points_leaderboard',
			'POINTS_LEADERBOARD.tpl'=>'points_leaderboard',
			'POINTS_LEADERBOARD_WEEK.tpl'=>'points_leaderboard_screen',
			'POINTS_LEADERBOARD_SCREEN.tpl'=>'points_leaderboard_screen',
			'POINTS_SEARCH_RESULT.tpl'=>'points_search_screen',
			'POINTS_SEARCH_SCREEN.tpl'=>'points_search_screen',
			'POINTS_GIVE.tpl'=>'points_screen',
			'POINTS_PROFILE.tpl'=>'points_screen',
			'POINTS_SCREEN.tpl'=>'points_screen',
			'POINTS_TRANSACTIONS_WRAP.tpl'=>'points_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__points_leaderboard()
	{
		$out=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$out->attach(do_lorem_template('POINTS_LEADERBOARD_ROW', array(
				'ID'=>placeholder_id(),
				'POINTS_URL'=>placeholder_url(),
				'PROFILE_URL'=>placeholder_url(),
				'POINTS'=>placeholder_number(),
				'NAME'=>lorem_phrase()
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('POINTS_LEADERBOARD', array(
				'URL'=>placeholder_url(),
				'LIMIT'=>placeholder_number(),
				'ROWS'=>$out
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__points_leaderboard_screen()
	{
		$out=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$week_tpl=new ocp_tempcode();
			foreach (placeholder_array() as $k=>$v)
			{
				$week_tpl->attach(do_lorem_template('POINTS_LEADERBOARD_ROW', array(
					'ID'=>placeholder_id(),
					'POINTS_URL'=>placeholder_url(),
					'PROFILE_URL'=>placeholder_url(),
					'POINTS'=>placeholder_number(),
					'NAME'=>lorem_phrase()
				)));
			}
			$out->attach(do_lorem_template('POINTS_LEADERBOARD_WEEK', array(
				'WEEK'=>placeholder_number(),
				'ROWS'=>$week_tpl
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('POINTS_LEADERBOARD_SCREEN', array(
				'TITLE'=>lorem_title(),
				'WEEKS'=>$out
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__points_search_screen()
	{
		$results=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$results->attach(do_lorem_template('POINTS_SEARCH_RESULT', array(
				'URL'=>placeholder_url(),
				'ID'=>placeholder_id(),
				'NAME'=>lorem_word()
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('POINTS_SEARCH_SCREEN', array(
				'TITLE'=>lorem_title(),
				'RESULTS'=>$results
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__points_screen()
	{
		$chargelog_details=do_lorem_template('POINTS_TRANSACTIONS_WRAP', array(
			'CONTENT'=>placeholder_table(),
			'TITLE'=>lorem_phrase()
		));

		$from=do_lorem_template('POINTS_TRANSACTIONS_WRAP', array(
			'CONTENT'=>placeholder_table(),
			'TITLE'=>lorem_phrase()
		));

		$to=do_lorem_template('POINTS_TRANSACTIONS_WRAP', array(
			'CONTENT'=>placeholder_table(),
			'TITLE'=>lorem_phrase()
		));

		$give_template=do_lorem_template('POINTS_GIVE', array(
			'GIVE_URL'=>placeholder_url(),
			'USER'=>lorem_phrase(),
			'VIEWER_GIFT_POINTS_AVAILABLE'=>placeholder_number()
		));

		$content=do_lorem_template('POINTS_PROFILE', array(
			'MEMBER'=>lorem_phrase(),
			'PROFILE_URL'=>placeholder_url(),
			'NAME'=>lorem_word(),
			'POINTS_JOINING'=>placeholder_number(),
			'POINTS_RATING'=>placeholder_number(),
			'POINTS_VOTING'=>placeholder_number(),
			'POINTS_POSTING'=>placeholder_number(),
			'POINTS_PER_DAY'=>placeholder_number(),
			'POINTS_PER_DAILY_VISIT'=>placeholder_number(),
			'POST_COUNT'=>placeholder_number(),
			'POINTS_GAINED_GIVEN'=>placeholder_number(),
			'POINTS_GAINED_RATING'=>placeholder_number(),
			'POINTS_GAINED_VOTING'=>placeholder_number(),
			'POINTS_GAINED_AUTO'=>placeholder_number(),
			'POINTS_USED'=>placeholder_number(),
			'REMAINING'=>placeholder_number(),
			'GIFT_POINTS_USED'=>placeholder_number(),
			'GIFT_POINTS_AVAILABLE'=>placeholder_number(),
			'DAYS_JOINED'=>placeholder_number(),
			'TO'=>$to,
			'FROM'=>$from,
			'CHARGELOG_DETAILS'=>$chargelog_details,
			'GIVE'=>$give_template,
			'WIKI_POST_COUNT'=>placeholder_number(),
			'POINTS_WIKI_POSTING'=>placeholder_number(),
			'CHAT_POST_COUNT'=>placeholder_number(),
			'POINTS_CHAT_POSTING'=>placeholder_number(),
			'MULT_POINTS_RATING'=>placeholder_number(),
			'MULT_POINTS_VOTING'=>placeholder_number(),
			'MULT_POINTS_CHAT_POSTING'=>placeholder_number(),
			'MULT_POINTS_WIKI_POSTING'=>placeholder_number(),
			'MULT_POINTS_POSTING'=>placeholder_number(),
			'MULT_POINTS_PER_DAY'=>placeholder_number()
		));

		//Create the 'NEXT_BROWSER_BROWSE_NEXT' template
		$browse=do_lorem_template('NEXT_BROWSER_BROWSE_NEXT',array('NEXT_URL'=>placeholder_url(),'PREVIOUS_URL'=>placeholder_url(),'PAGE_NUM'=>placeholder_number(),'NUM_PAGES'=>placeholder_number()));

		return array(
			lorem_globalise(do_lorem_template('POINTS_SCREEN', array(
				'TITLE'=>lorem_title(),
				'BROWSE'=>$browse,
				'CONTENT'=>$content
			)), NULL, '', true)
		);
	}
}
