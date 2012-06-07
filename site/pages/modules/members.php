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
 * @package		core_ocf
 */

/**
 * Module page class.
 */
class Module_members
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		$ret=array('misc'=>'MEMBERS'/*,'remote'=>'LEARN_ABOUT_REMOTE_LOGINS'*/);
		if (!is_guest()) $ret['view']='MY_PROFILE';
		return $ret;
	}

	/**
	 * Standard modular new-style deep page-link finder function (does not return the main entry-points).
	 *
	 * @param  string  	Callback function to send discovered page-links to.
	 * @param  MEMBER		The member we are finding stuff for (we only find what the member can view).
	 * @param  integer	Code for how deep we are tunnelling down, in terms of whether we are getting entries as well as categories.
	 * @param  string		Stub used to create page-links. This is passed in because we don't want to assume a zone or page name within this function.
	 */
	function get_sitemap_pagelinks($callback,$member_id,$depth,$pagelink_stub)
	{
		// Entries
		if ($depth>=DEPTH__ENTRIES)
		{
			$start=0;
			do
			{
				$groups=$GLOBALS['FORUM_DB']->query_select('f_members',array('id','m_username AS title','m_join_time'),NULL,'',500,$start);

				foreach ($groups as $row)
				{
					if ($row['id']!=db_get_first_id())
					{
						$pagelink=$pagelink_stub.'view:'.strval($row['id']);
						call_user_func_array($callback,array($pagelink,$pagelink_stub.'misc',$row['m_join_time'],NULL,0.2,$row['title'])); // Callback
					}
				}

				$start+=500;
			}
			while (array_key_exists(0,$groups));
		}
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_css('ocf');

		$type=get_param('type','misc');

		if ($type=='misc') return $this->directory();
		if ($type=='view') return $this->profile();
		//if ($type=='remote') return $this->remote();

		return new ocp_tempcode();
	}

	/**
	 * The UI to show info about remote logins.
	 *
	 * @return tempcode		The UI
	 */
	function remote()
	{
		$title=get_screen_title('LEARN_ABOUT_REMOTE_LOGINS');

		if (get_option('allow_member_integration')=='off') warn_exit(do_lang_tempcode('NO_REMOTE_ON'));

		return do_template('FULL_MESSAGE_SCREEN',array('_GUID'=>'c0d5fa4f2b90e5d8e967763cca787636','TITLE'=>$title,'TEXT'=>do_lang_tempcode('DESCRIPTION_IS_REMOTE_MEMBER',ocp_srv('HTTP_HOST'))));
	}

	/**
	 * The UI to show the member directory.
	 *
	 * @return tempcode		The UI
	 */
	function directory()
	{
		require_javascript('javascript_ajax');
		require_javascript('javascript_ajax_people_lists');

		$title=get_screen_title('MEMBERS');

		require_code('templates_internalise_screen');
		$test_tpl=internalise_own_screen($title);
		if (is_object($test_tpl)) return $test_tpl;

		if (running_script('iframe'))
		{
			$get_url=find_script('iframe');
		} else
		{
			$get_url=find_script('index');
		}
		$hidden=build_keep_form_fields('_SELF',true,array('filter'));

		$start=get_param_integer('md_start',0);
		$max=get_param_integer('md_max',50);
		$sortables=array('m_username'=>do_lang_tempcode('USERNAME'),'m_primary_group'=>do_lang_tempcode('PRIMARY_GROUP'),'m_cache_num_posts'=>do_lang_tempcode('COUNT_POSTS'),'m_join_time'=>do_lang_tempcode('JOIN_DATE'));
		$default_sort_order=get_value('md_default_sort_order');
		if (is_null($default_sort_order))
			$default_sort_order='m_join_time DESC';
		$test=explode(' ',get_param('md_sort',$default_sort_order),2);
		if (count($test)==1) $test[]='ASC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='md_sort';

		$group_filter=get_param('group_filter','');

		$_usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true,false,false,($group_filter=='')?NULL:array(intval($group_filter)));
		$usergroups=array();
		require_code('ocf_groups2');
		foreach ($_usergroups as $group_id=>$group)
		{
			$num=ocf_get_group_members_raw_count($group_id,true);
			$usergroups[$group_id]=array('USERGROUP'=>$group,'NUM'=>strval($num));
		}

		// ocSelect
		$ocselect=either_param('active_filter','');
		if ($ocselect!='')
		{
			require_code('ocselect');
			$content_type='member';
			list($ocselect_extra_select,$ocselect_extra_join,$ocselect_extra_where)=ocselect_to_sql($GLOBALS['SITE_DB'],parse_ocselect($ocselect),$content_type,'');
			$extra_select_sql=implode('',$ocselect_extra_select);
			$extra_join_sql=implode('',$ocselect_extra_join);
		} else
		{
			$extra_select_sql='';
			$extra_join_sql='';
			$ocselect_extra_where='';
		}

		$query='FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members r'.$extra_join_sql.' WHERE id<>'.strval(db_get_first_id()).$ocselect_extra_where;
		if (!has_specific_permission(get_member(),'see_unvalidated')) $query.=' AND m_validated=1';

		if ($group_filter!='')
		{
			if (is_numeric($group_filter))
				$title=get_screen_title('USERGROUP',true,array($usergroups[intval($group_filter)]['USERGROUP']));

			require_code('ocfiltering');
			$filter=ocfilter_to_sqlfragment($group_filter,'m_primary_group','f_groups',NULL,'m_primary_group','id');
			$query.=' AND '.$filter;
		}
		$search=get_param('filter','');
		$sup='';
		if ($search!='')
		{
			$sup=' AND (m_username LIKE \''.db_encode_like(str_replace('*','%',$search)).'\'';
			if (has_specific_permission(get_member(),'member_maintenance'))
				$sup.=' OR m_email_address LIKE \''.db_encode_like(str_replace('*','%',$search)).'\'';
			$sup.=')';
		}
		if ($sortable=='m_join_time')
		{
			$query.=$sup.' ORDER BY m_join_time '.$sort_order.','.'id '.$sort_order;
		} else
		{
			$query.=$sup.' ORDER BY '.$sortable.' '.$sort_order;
		}

		$max_rows=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(*) '.$query);
		$rows=$GLOBALS['FORUM_DB']->query('SELECT r.*'.$extra_select_sql.' '.$query,$max,$start);
		if (count($rows)==0)
		{
			return inform_screen($title,do_lang_tempcode('NO_RESULTS'));
		}
		$members=new ocp_tempcode();
		$member_boxes=array();
		require_code('templates_results_table');
		$fields_title=results_field_title(array(do_lang_tempcode('USERNAME'),do_lang_tempcode('PRIMARY_GROUP'),do_lang_tempcode('COUNT_POSTS'),do_lang_tempcode('JOIN_DATE')),$sortables,'md_sort',$sortable.' '.$sort_order);
		require_code('ocf_members2');
		foreach ($rows as $row)
		{
			$link=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['id'],true,$row['m_username']);
			$url=$GLOBALS['FORUM_DRIVER']->member_profile_url($row['id'],true);
			if ($row['m_validated']==0) $link->attach(do_lang_tempcode('MEMBER_IS_UNVALIDATED'));
			if ($row['m_validated_email_confirm_code']!='') $link->attach(do_lang_tempcode('MEMBER_IS_UNCONFIRMED'));
			$member_primary_group=ocf_get_member_primary_group($row['id']);
			$primary_group=ocf_get_group_link($member_primary_group);

			$members->attach(results_entry(array($link,$primary_group,integer_format($row['m_cache_num_posts']),escape_html(get_timezoned_date($row['m_join_time'])))));

			$box=render_member_box($row['id'],true);
			$_box=do_template('SIMPLE_PREVIEW_BOX',array('SUMMARY'=>$box,'URL'=>$url));
			$member_boxes[]=$_box;
		}
		$results_table=results_table(do_lang_tempcode('MEMBERS'),$start,'md_start',$max,'md_max',$max_rows,$fields_title,$members,$sortables,$sortable,$sort_order,'md_sort');

		$pagination=pagination(do_lang_tempcode('MEMBERS'),NULL,$start,'md_start',$max,'md_max',$max_rows,NULL,NULL,true,true);

		$symbols=NULL;
		if (get_option('allow_alpha_search')=='1')
		{
			$alpha_query=$GLOBALS['FORUM_DB']->query('SELECT m_username FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members WHERE id<>'.strval(db_get_first_id()).' ORDER BY m_username ASC');
			$symbols=array(array('START'=>'0','SYMBOL'=>do_lang('ALL')),array('START'=>'0','SYMBOL'=>'#'));
			foreach (array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z') as $s)
			{
				foreach ($alpha_query as $i=>$q)
				{
					if (strtolower(substr($q['m_username'],0,1))==$s)
					{
						break;
					}
				}
				if (substr(strtolower($q['m_username']),0,1)!=$s) $i=intval($symbols[count($symbols)-1]['START']);
				$symbols[]=array('START'=>strval(intval($max*floor(floatval($i)/floatval($max)))),'SYMBOL'=>$s);
			}
		}

		return do_template('OCF_MEMBER_DIRECTORY_SCREEN',array('_GUID'=>'096767e9aaabce9cb3e6591b7bcf95b8','MAX'=>strval($max),'PAGINATION'=>$pagination,'MEMBER_BOXES'=>$member_boxes,'USERGROUPS'=>$usergroups,'HIDDEN'=>$hidden,'SYMBOLS'=>$symbols,'SEARCH'=>$search,'GET_URL'=>$get_url,'TITLE'=>$title,'RESULTS_TABLE'=>$results_table));
	}

	/**
	 * The UI to show a member's profile.
	 *
	 * @return tempcode		The UI
	 */
	function profile()
	{
		breadcrumb_set_parents(array(array('_SELF:_SELF:misc'.propagate_ocselect_pagelink(),do_lang_tempcode('MEMBERS'))));

		$username=get_param('id',strval(get_member()));
		if ($username=='') $username=strval(get_member());
		if (is_numeric($username))
		{
			$member_id=get_param_integer('id',get_member());
			if (is_guest($member_id))
				access_denied('NOT_AS_GUEST');
			$username=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_username');
			if ((is_null($username)) || (is_guest($member_id))) warn_exit(do_lang_tempcode('USER_NO_EXIST'));
		} else
		{
			$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($username);
			if (is_null($member_id)) warn_exit(do_lang_tempcode('_USER_NO_EXIST',escape_html($username)));
		}

		require_code('ocf_profiles');
		return render_profile_tabset($member_id,get_member(),$username);
	}

}


