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
 * @package		points
 */

class Hook_task_export_points_log
{
	/**
	 * Run the task hook.
	 *
	 * @param  TIME			Date from
	 * @param  TIME			Date to
	 * @return ?array			A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (NULL: show standard success message)
	 */
	function run($from,$to)
	{
		require_lang('points');
		require_code('points');

		$label=do_lang('POINTS_GAINED_BETWEEN',get_timezoned_date($from,false,false,false,true),get_timezoned_date($to,false,false,false,true));

		$data=array();

		$total_gained_points=0;

		$quizzes=array();
		if (addon_installed('quizzes'))
		{
			require_lang('quiz');
			$quizzes=$GLOBALS['SITE_DB']->query_select('quizzes',array('id','q_name'),NULL,'ORDER BY q_add_date DESC',100);
		}

		$members=$GLOBALS['FORUM_DRIVER']->get_matching_members('',10000/*reasonable limit -- works via returning 'most active' first*/);
		$all_usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list();
		foreach ($members as $member)
		{
			$member_id=$GLOBALS['FORUM_DRIVER']->mrow_id($member);
			$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id);
			$email=$GLOBALS['FORUM_DRIVER']->get_member_email_address($member_id);

			$usergroups='';
			$_usergroups=$GLOBALS['FORUM_DRIVER']->get_members_groups($member_id);
			foreach ($_usergroups as $_usergroup)
			{
				if ($usergroups!='') $usergroups.=', ';
				$usergroups.=$all_usergroups[$_usergroup];
			}

			$points_gained=total_points($member_id,$to)-total_points($member_id,$from);
			$points_now=total_points($member_id);

			$data_point=array();

			$data_point[do_lang('IDENTIFIER')]=$member_id;
			$data_point[do_lang('USERNAME')]=$username;
			$data_point[$label]=$points_gained;
			$data_point[do_lang('POINTS_NOW')]=$points_now;
			$data_point[do_lang('GROUPS')]=$usergroups;
			$data_point[do_lang('EMAIL')]=$email;

			if (addon_installed('quizzes'))
			{
				foreach ($quizzes as $quiz)
				{
					$entered=!is_null($GLOBALS['SITE_DB']->query_select_value_if_there('quiz_entries','id',array('q_member'=>$member_id,'q_quiz'=>$quiz['id'])));
					$data_point[do_lang('ENTERED_THIS_QUIZ',get_translated_text($quiz['q_name']))]=do_lang($entered?'YES':'NO');
				}
			}

			$data[]=$data_point;

			$total_gained_points+=$points_gained;
		}

		// Ordering for automatic 'lottery'
		$winner_data=array();
		while (count($data)!=0)
		{
			$rand=mt_rand(0,$total_gained_points);
			$so_far=0;
			foreach ($data as $i=>$data_point)
			{
				$so_far+=$data_point[$label];

				if (($rand<$so_far) || (($rand==$so_far) && ($so_far==$total_gained_points)))
				{
					$winner_data[]=$data_point;
					unset($data[$i]);
					$total_gained_points-=$data_point[$label];

					break;
				}
			}
		}

		$filename='points_log.csv';

		$headers=array();
		$headers['Content-type']='text/csv';
		$headers['Content-Disposition']='attachment; filename="'.str_replace("\r",'',str_replace("\n",'',addslashes($filename))).'"';

		$ini_set=array();
		$ini_set['ocproducts.xss_detect']='0';

		require_code('files2');
		$outfile_path=ocp_tempnam('csv');
		make_csv($winner_data,$filename,false,false,$outfile_path);
		return array('text/csv',array($filename,$outfile_path),$headers,$ini_set);
	}
}