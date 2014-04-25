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

class Hook_cron_mail_queue
{

	/**
	 * Standard modular run function for CRON hooks. Searches for tasks to perform.
	 */
	function run()
	{
		if (get_option('mail_queue_debug')=='0')
		{
			$mails=$GLOBALS['SITE_DB']->query_select(
				'logged_mail_messages',
				array('id','m_subject','m_message','m_to_email','m_to_name','m_from_email','m_from_name','m_priority','m_attachments','m_no_cc','m_as','m_as_admin','m_in_html','m_date_and_time','m_member_id','m_url','m_template'),
				array('m_queued'=>1),
				'',
				100
			);

			if (count($mails)!=0)
			{
				require_code('mail');

				foreach ($mails as $row)
				{
					$subject=$row['m_subject'];
					$message=$row['m_message'];
					$to_email=@unserialize($row['m_to_email']);
					$to_name=@unserialize($row['m_to_name']);
					$from_email=$row['m_from_email'];
					$from_name=$row['m_from_name'];

					if (is_string($to_email)) // LEGACY issue of bad data stuck in DB
					{
						$to_email=array($to_email);
					}
					if (!is_array($to_email)) continue;

					mail_wrap($subject,$message,$to_email,$to_name,$from_email,$from_name,$row['m_priority'],unserialize($row['m_attachments']),$row['m_no_cc']==1,$row['m_as'],$row['m_as_admin']==1,$row['m_in_html']==1,true,$row['m_template']);

					$GLOBALS['SITE_DB']->query_update('logged_mail_messages',array('m_queued'=>0),array('id'=>$row['id']),'',1);
				}

				decache('main_staff_checklist');
			}
		}
	}

}
