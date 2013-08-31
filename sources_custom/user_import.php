<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.

*/

function init__user_import()
{
	define('USER_IMPORT_ENABLED',false);
	define('USER_IMPORT_MINUTES',60*24);

	define('USER_IMPORT_TEST_MODE',false);

	define('USER_IMPORT_DELIM',',');

	define('USER_IMPORT_MATCH_KEY','id'); // defined in terms of the local key

	define('USER_IMPORT_URL',get_base_url().'/data_custom/modules/user_export/in.csv'); // Can be remote, we do an HTTP download to the path below (even if local)...
	define('USER_IMPORT_TEMP_PATH','data_custom/modules/user_export/in.csv.tmp');

	global $USER_IMPORT_WANTED;
	$USER_IMPORT_WANTED=array(
		// LOCAL => REMOTE
		'id'=>'ocPortal member ID',
		'm_username'=>'Username',
		'm_email_address'=>'E-mail address',
	);
}

function do_user_import()
{
	if (function_exists('set_time_limit')) @set_time_limit(0);

	if (!USER_IMPORT_TEST_MODE)
	{
		require_code('files');
		$infile=fopen(get_custom_file_base().'/'.USER_IMPORT_TEMP_PATH,'r+b');
		$test=http_download_file(USER_IMPORT_URL,NULL,false,false,'ocPortal',NULL,NULL,NULL,NULL,NULL,$write_to_file);
		if (is_null($test)) return;
	} else
	{
		$infile=fopen(get_custom_file_base().'/'.USER_IMPORT_TEMP_PATH,'rb');
	}

	require_code('ocf_members_action');
	require_code('ocf_members_action2');
	require_code('ocf_members');

	rewind($infile);

	global $USER_IMPORT_WANTED;
	$header_row=fgetcsv($infile,0,USER_IMPORT_DELIM);
	foreach ($USER_IMPORT_WANTED as $local_key=>$remote_key)
	{
		$remote_index=array_search($remote_key,$header_row);
		if ($remote_index!==false)
		{
			$USER_IMPORT_WANTED[$local_key]=$remote_index;
		} else
		{
			fatal_exit('Could not find the '.$remote_key.' field.');
		}
	}

	$cpf_ids=array();
	$fields_to_show=ocf_get_all_custom_fields_match(NULL);
	foreach ($fields_to_show as $field_to_show)
	{
		$cpf_ids[$field_to_show['trans_name']]=$field_to_show['id'];
	}

	do
	{
		$row=fgetcsv($infile,0,USER_IMPORT_DELIM);
		if ($row!==false)
		{
			// Match to ID
			$remote_match_key_value=$row[$USER_IMPORT_WANTED[USER_IMPORT_MATCH_KEY]];
			if ($remote_match_key_value=='') continue; // No key, and it's not a good idea for us to try to match to a blank value
			if ((substr(USER_IMPORT_MATCH_KEY,0,2)!='m_') && (USER_IMPORT_MATCH_KEY!='id'))
			{
				$cpf_id=$cpf_ids[USER_IMPORT_MATCH_KEY];
				$member_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_member_custom_fields','mf_member_id',array('field_'.strval($cpf_id)=>$remote_match_key_value));
			} else
			{
				$member_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_members','id',array(USER_IMPORT_MATCH_KEY=>$remote_match_key_value));
			}

			// Find data
			$username=isset($USER_IMPORT_WANTED['m_username'])?$row[$USER_IMPORT_WANTED['m_username']]:NULL;
			$password=isset($USER_IMPORT_WANTED['m_password'])?$row[$USER_IMPORT_WANTED['m_password']]:NULL;
			$email_address=isset($USER_IMPORT_WANTED['m_email_address'])?$row[$USER_IMPORT_WANTED['m_email_address']]:NULL;
			$groups=NULL;
			$dob_day=isset($USER_IMPORT_WANTED['m_dob_day'])?$row[$USER_IMPORT_WANTED['m_dob_day']]:NULL;
			$dob_month=isset($USER_IMPORT_WANTED['m_dob_month'])?$row[$USER_IMPORT_WANTED['m_dob_month']]:NULL;
			$dob_year=isset($USER_IMPORT_WANTED['m_dob_year'])?$row[$USER_IMPORT_WANTED['m_dob_year']]:NULL;
			$custom_fields=array();
			foreach ($USER_IMPORT_WANTED as $local_key=>$remote_index)
			{
				if ((substr($local_key,0,2)!='m_') && ($local_key!='id'))
				{
					$custom_fields[$cpf_ids[$local_key]]=$row[$remote_index];
				}
			}
			$timezone=isset($USER_IMPORT_WANTED['m_timezone'])?$row[$USER_IMPORT_WANTED['m_timezone']]:NULL;
			$primary_group=isset($USER_IMPORT_WANTED['m_primary_group'])?$row[$USER_IMPORT_WANTED['m_primary_group']]:NULL;
			$photo_url=isset($USER_IMPORT_WANTED['m_photo_url'])?$row[$USER_IMPORT_WANTED['m_photo_url']]:NULL;

			if (is_null($member_id))
			{
				if (!is_null($username))
				{
					// Add
					if (is_null($password)) $password=produce_salt();
					ocf_make_member($username,$password,$email_address,$groups,$dob_day,$dob_month,$dob_year,$custom_fields,$timezone,$primary_group,1,NULL,NULL,'',NULL,'',0,0,1,'',$photo_url,'',1,NULL,NULL,1,1,'',NULL,'',false,'plain');
				}
			} else
			{
				// Edit
				ocf_edit_member($member_id,$email_address,NULL,$dob_day,$dob_month,$dob_year,$timezone,$primary_group,$custom_fields,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,$username,$password,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,$photo_url,NULL,NULL,NULL,true);
				require_code('ocf_groups_action2');
				if (!is_null($groups))
				{
					$members_groups=$GLOBALS['OCF_DRIVER']->get_members_groups($member_id);
					foreach ($groups as $group_id)
					{
						if (!in_array($group_id,$members_groups))
						{
							ocf_add_member_to_group($member_id,$group_id);
						}
					}
					foreach ($members_groups as $group_id)
					{
						if (!in_array($group_id,$groups))
						{
							ocf_member_leave_group($group_id,$member_id);
						}
					}
				}
			}
		}
	}
	while ($row!==false);

	fclose($infile);
}