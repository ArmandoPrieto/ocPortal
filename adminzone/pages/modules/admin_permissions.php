<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_permission_management
 */

/**
 * Module page class.
 */
class Module_admin_permissions
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
		$info['version']=7;
		$info['update_require_upgrade']=1;
		$info['locked']=true;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('group_zone_access');
		$GLOBALS['SITE_DB']->drop_table_if_exists('group_page_access');
		$GLOBALS['SITE_DB']->drop_table_if_exists('match_key_messages');

		$false_permissions=get_false_permissions();
		foreach ($false_permissions as $permission)
			delete_privilege($permission[1]);

		$true_permissions=get_true_permissions();
		foreach ($true_permissions as $permission)
			delete_privilege($permission[1]);

		delete_privilege('assume_any_member');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('match_key_messages',array(
				'id'=>'*AUTO',
				'k_message'=>'LONG_TRANS',
				'k_match_key'=>'SHORT_TEXT'
			));

			// What usergroups may enter this zone
			$GLOBALS['SITE_DB']->create_table('group_zone_access',array(
				'zone_name'=>'*ID_TEXT',
				'group_id'=>'*GROUP'
			));
			$GLOBALS['SITE_DB']->create_index('group_zone_access','group_id',array('group_id'));

			// Some defaults
			$usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list();
			$admin_groups=array_unique(array_merge($GLOBALS['FORUM_DRIVER']->get_super_admin_groups(),$GLOBALS['FORUM_DRIVER']->get_moderator_groups()));
			$guest_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups($GLOBALS['FORUM_DRIVER']->get_guest_id());
			foreach ($usergroups as $id=>$name)
			{
				$GLOBALS['SITE_DB']->query_insert('group_zone_access',array('zone_name'=>'','group_id'=>$id));
				//$GLOBALS['SITE_DB']->query_insert('group_zone_access',array('zone_name'=>'docs','group_id'=>$id));	Docs are admin only now
				$GLOBALS['SITE_DB']->query_insert('group_zone_access',array('zone_name'=>'forum','group_id'=>$id));
				if ($id!=$guest_groups[0]) $GLOBALS['SITE_DB']->query_insert('group_zone_access',array('zone_name'=>'site','group_id'=>$id));
				if ($id!=$guest_groups[0]) $GLOBALS['SITE_DB']->query_insert('group_zone_access',array('zone_name'=>'cms','group_id'=>$id));
				if ((($name==do_lang('SUPER_MEMBERS')) || (in_array($id,$admin_groups)))/* && (ocp_enterprise())*/)
					$GLOBALS['SITE_DB']->query_insert('group_zone_access',array('zone_name'=>'collaboration','group_id'=>$id));
			}
			foreach ($admin_groups as $admin_group)
			{
				$GLOBALS['SITE_DB']->query_insert('group_zone_access',array('zone_name'=>'adminzone','group_id'=>$admin_group));
			}

			// What usergroups may NOT view this page (default is that any page may be viewed if a user can access its zone)
			$GLOBALS['SITE_DB']->create_table('group_page_access',array(
				'page_name'=>'*ID_TEXT',
				'zone_name'=>'*ID_TEXT',
				'group_id'=>'*GROUP'
			));
			foreach (array_keys($usergroups) as $id)
			{
				if ((get_forum_type()=='ocf') && (!is_guest($id)))
					$GLOBALS['SITE_DB']->query_insert('group_page_access',array('page_name'=>'join','zone_name'=>get_module_zone('join'),'group_id'=>$id));

				$GLOBALS['SITE_DB']->query_insert('group_page_access',array('page_name'=>'admin_redirects','zone_name'=>'adminzone','group_id'=>$id)); // We don't want people to redirect themselves passed the page/zone security unless they are admins already
				$GLOBALS['SITE_DB']->query_insert('group_page_access',array('page_name'=>'admin_addons','zone_name'=>'adminzone','group_id'=>$id)); // We don't want people installing new code
				$GLOBALS['SITE_DB']->query_insert('group_page_access',array('page_name'=>'admin_emaillog','zone_name'=>'adminzone','group_id'=>$id)); // We don't want people snooping on admin emails (e.g. password reset)
			}
			$GLOBALS['SITE_DB']->create_index('group_page_access','group_id',array('group_id'));

			// False privileges
			$false_permissions=get_false_permissions();
			foreach ($false_permissions as $permission)
				add_privilege($permission[0],$permission[1],false);

			// For admins only
			add_privilege('STAFF_ACTIONS','assume_any_member',false,true);

			// True privileges
			$true_permissions=get_true_permissions();
			foreach ($true_permissions as $permission)
				add_privilege($permission[0],$permission[1],true);
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		$ret=array('page'=>'PAGE_ACCESS','privileges'=>'PRIVILEGES');
		if (addon_installed('match_key_permissions')) $ret['keys']='MATCH_KEYS';
		return $ret;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (function_exists('set_time_limit')) @set_time_limit(60);

		require_lang('permissions');
		require_css('permissions_editor');
		require_css('forms');

		$type=get_param('type','misc');

		if ($type=='misc') return $this->tree_editor();
		if ($type=='absorb') return $this->absorb();
		if ($type=='_absorb') return $this->_absorb();
		if (addon_installed('match_key_permissions'))
		{
			if ($type=='keys') return $this->interface_keys_access();
			if ($type=='_keys') return $this->set_keys_access();
		}
		if ($type=='page') return $this->interface_page_access();
		if ($type=='_page') return $this->set_page_access();
		if ($type=='_privileges') return $this->set_privileges();
		if ($type=='privileges') return $this->interface_privileges();

		return new ocp_tempcode();
	}

	/**
	 * The UI to absorb usergroup permissions.
	 *
	 * @return tempcode		The UI
	 */
	function absorb()
	{
		$title=get_screen_title('ABSORB_PERMISSIONS');

		set_helper_panel_pic('pagepics/privileges');
		set_helper_panel_tutorial('tut_permissions');

		$groups_without=array();
		$all_groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
		$list1=new ocp_tempcode();
		$list2=new ocp_tempcode();
		$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
		$moderator_groups=$GLOBALS['FORUM_DRIVER']->get_moderator_groups();
		foreach ($all_groups as $id=>$name)
		{
			if (in_array($id,$admin_groups)) continue;

			$test=$GLOBALS['SITE_DB']->query_select_value_if_there('group_privileges','group_id',array('group_id'=>$id));
			if (is_null($test)) $groups_without[$id]=$name;

			$list1->attach(form_input_list_entry($id,is_null($test),$name));
			$list2->attach(form_input_list_entry($id,!is_null($test) && !in_array($id,$moderator_groups),$name));
		}

		$__groups_without=escape_html(implode(', ',$groups_without));
		if ($__groups_without=='') $_groups_without=do_lang_tempcode('NONE_EM'); else $_groups_without=protect_from_escaping($__groups_without);
		$text=do_lang_tempcode('USERGROUPS_WITH_NO_PERMISSIONS',$_groups_without);

		$submit_name=do_lang_tempcode('ABSORB_PERMISSIONS');
		$post_url=build_url(array('page'=>'_SELF','type'=>'_absorb'),'_SELF');

		require_code('form_templates');
		$fields=new ocp_tempcode();
		$fields->attach(form_input_list(do_lang_tempcode('FROM'),do_lang_tempcode('PERMISSIONS_FROM'),'from',$list1));
		$fields->attach(form_input_list(do_lang_tempcode('TO'),do_lang_tempcode('PERMISSIONS_TO'),'to',$list2));

		return do_template('FORM_SCREEN',array('_GUID'=>'9e20011006a26b240fc898279338875c','SKIP_VALIDATION'=>true,'TITLE'=>$title,'HIDDEN'=>'','FIELDS'=>$fields,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'URL'=>$post_url));
	}

	/**
	 * The actualiser to absorb usergroup permissions.
	 *
	 * @return tempcode		The UI
	 */
	function _absorb()
	{
		$to=post_param_integer('to');
		$from=post_param_integer('from');
		if ($to==$from) warn_exit(do_lang_tempcode('MERGE_SAME'));

		$title=get_screen_title('ABSORB_PERMISSIONS');

		set_helper_panel_pic('pagepics/privileges');
		set_helper_panel_tutorial('tut_permissions');

		// Although the code is from OCF, it is safe to use for other forum drivers
		require_code('ocf_groups_action');
		require_code('ocf_groups_action2');
		ocf_group_absorb_privileges_of($to,$from);

		breadcrumb_set_parents(array(array('_SELF:_SELF:absord',do_lang_tempcode('ABSORB_PERMISSIONS'))));
		breadcrumb_set_self(do_lang_tempcode('DONE'));

		$url=build_url(array('page'=>'_SELF','type'=>'absorb'),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The UI to for the permissions-tree-editor (advanced substitute for the combination of the page permissions screen and various other structure/content-attached screens).
	 *
	 * @return tempcode		The UI
	 */
	function tree_editor()
	{
		$title=get_screen_title('PERMISSIONS_TREE');

		if (!has_js())
		{
			// Send them to the page permissions screen
			$url=build_url(array('page'=>'_SELF','type'=>'page'),'_SELF');
			require_code('site2');
			assign_refresh($url,5.0);
			return do_template('REDIRECT_SCREEN',array('_GUID'=>'a376167acf6d0f5ac80ca743a2c728d9','URL'=>$url,'TITLE'=>$title,'TEXT'=>do_lang_tempcode('NO_JS_ADVANCED_SCREEN_PERMISSIONS')));
		}

		require_javascript('javascript_ajax');
		require_javascript('javascript_tree_list');
		require_javascript('javascript_more');
		require_code('form_templates');

		require_css('sitetree_editor');

		$groups=new ocp_tempcode();
		$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
		$all_groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
		$initial_group=NULL;
		foreach ($all_groups as $id=>$group_name)
		{
			if (is_null($initial_group)) $initial_group=$group_name;
			if (!in_array($id,$admin_groups))
			{
				$groups->attach(form_input_list_entry(strval($id),$id==$GLOBALS['FORUM_DRIVER']->get_guest_group(),$group_name));
			}
		}

		$css_path=get_custom_file_base().'/themes/'.$GLOBALS['FORUM_DRIVER']->get_theme().'/templates_cached/'.user_lang().'/global.css';
		$color='FF00FF';
		if (file_exists($css_path))
		{
			$tmp_file=file_get_contents($css_path);
			$matches=array();
			if (preg_match('#(\n|\})th[\s,][^\}]*(\s|\{)background-color:\s*\#([\dA-Fa-f]*);color:\s*\#([\dA-Fa-f]*);#sU',$tmp_file,$matches)!=0)
			{
				$color=$matches[3].'&fgcolor='.$matches[4];
			}
		}

		// Standard editing matrix
		// NB: For permissions tree editor, default access is shown as -1 in editor for clarity (because the parent permissions are easily findable which implies the default access would mean something else which would confuse [+ this would be hard to do due to the dynamicness of the interface])
		require_code('permissions2');
		$editor=get_permissions_matrix('',array(),array(),array(),array(),true);

		return do_template('PERMISSIONS_TREE_EDITOR_SCREEN',array('_GUID'=>'08bb679a7cfab45c0c29b5393666dd57','USERGROUPS'=>$all_groups,'TITLE'=>$title,'INITIAL_GROUP'=>$initial_group,'COLOR'=>$color,'GROUPS'=>$groups,'EDITOR'=>$editor));
	}

	/**
	 * Show the header row for permission editor (all the usergroups, except admin usergroups).
	 *
	 * @param  array			List of admin usergroups
	 * @param  array			Map of usergroups (id=>name)
	 * @return tempcode		The header row
	 */
	function _access_header($admin_groups,$groups)
	{
		require_code('themes2');

		$css_path=get_custom_file_base().'/themes/'.$GLOBALS['FORUM_DRIVER']->get_theme().'/templates_cached/'.user_lang().'/global.css';
		$color='FF00FF';
		if (file_exists($css_path))
		{
			$tmp_file=file_get_contents($css_path);
			$matches=array();
			if (preg_match('#(\n|\})th[\s,][^\}]*(\s|\{)background-color:\s*\#([\dA-Fa-f]*);color:\s*\#([\dA-Fa-f]*);#sU',$tmp_file,$matches)!=0)
			{
				$color=$matches[3].'&fgcolor='.$matches[4];
			}
		}

		require_code('character_sets');

		// Column headers (groups)
		$header_cells=new ocp_tempcode();
		foreach ($groups as $id=>$name)
		{
			if (in_array($id,$admin_groups)) continue;

			$header_cells->attach(do_template('PERMISSION_HEADER_CELL',array('_GUID'=>'c77bd5d8d9dedb6a3e61c477910a06b7','COLOR'=>$color,'GROUP'=>foxy_utf8_to_nce($name))));
		}

		$header_cells->attach(do_template('PERMISSION_HEADER_CELL',array('_GUID'=>'33fde6c008293f20bb3a51e912748c67','COLOR'=>$color,'GROUP'=>foxy_utf8_to_nce('+/-'))));

		return $header_cells;
	}

	/**
	 * The UI to choose a zone to edit permissions for pages in.
	 *
	 * @param  tempcode		The title to use (output of get_screen_title)
	 * @return tempcode		The UI
	 */
	function choose_zone($title)
	{
		$fields=new ocp_tempcode();
		require_code('form_templates');
		require_lang('zones');

		require_code('zones3');
		$zones=nice_get_zones();
		$fields->attach(form_input_list(do_lang_tempcode('ZONE'),'','zone',$zones,NULL,true));

		$post_url=get_self_url(false,false,NULL,false,true);

		breadcrumb_set_self(do_lang_tempcode('CHOOSE'));

		return do_template('FORM_SCREEN',array('_GUID'=>'457a5b8200991996b383bf75515382ab','GET'=>true,'SKIP_VALIDATION'=>true,'HIDDEN'=>'','SUBMIT_NAME'=>do_lang_tempcode('CHOOSE'),'TITLE'=>$title,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>''));
	}

	/**
	 * The UI to set match-keys access.
	 *
	 * @return tempcode		The UI
	 */
	function interface_keys_access()
	{
		require_css('permissions_editor');

		set_helper_panel_pic('pagepics/matchkeysecurity');
		set_helper_panel_tutorial('tut_permissions');

		$title=get_screen_title('PAGE_MATCH_KEY_ACCESS');

		$url=build_url(array('page'=>'_SELF','type'=>'_keys'),'_SELF');

		$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
		$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);

		$header_cells=$this->_access_header($admin_groups,$groups);

		$cols=new ocp_tempcode();
		foreach ($groups as $id=>$g_name)
		{
			if (in_array($id,$admin_groups)) continue;
			$cols->attach(do_template('PERMISSION_COLUMN_SIZER'));
		}

		// Match-key permissions
		$p_rows=$GLOBALS['SITE_DB']->query_select('group_page_access',array('DISTINCT page_name'),array('zone_name'=>'/'),'ORDER BY page_name');
		$p_rows[]=array('page_name'=>'');
		$p_rows[]=array('page_name'=>'');
		$p_rows[]=array('page_name'=>'');
		$p_rows[]=array('page_name'=>'');
		$p_rows[]=array('page_name'=>'');
		$rows=new ocp_tempcode();
		foreach ($p_rows as $id=>$page)
		{
			$cells=new ocp_tempcode();
			$code='';

			$access_rows=collapse_1d_complexity('group_id',$GLOBALS['SITE_DB']->query_select('group_page_access',array('group_id'),array('page_name'=>$page['page_name'])));

			foreach ($groups as $gid=>$g_name)
			{
				if (in_array($gid,$admin_groups)) continue;

				$has_not_restriction=!in_array($gid,$access_rows);

				$cells->attach(do_template('PERMISSION_CELL',array('_GUID'=>'3d5fe8c61007d9665111fc9536f6ddf0','CHECKED'=>!$has_not_restriction,'HUMAN'=>do_lang_tempcode('RESTRICTION_CELL',/*$zone.'__'.*/escape_html($page['page_name']),escape_html($g_name)),'NAME'=>'p_'.strval($id).'__'.strval($gid))));
				$code.='form.elements[\''.'p_'.strval($id).'__'.strval($gid).'\'].checked=this.value==\'+\';';
			}

			$rows->attach(do_template('PERMISSION_KEYS_PERMISSION_ROW',array('_GUID'=>'dd692175fe246c130126ece7bd30ffb1','ALL_OFF'=>count($access_rows)==0,'KEY'=>$page['page_name'],'UID'=>strval($id),'CODE'=>$code,'CELLS'=>$cells)));
		}

		// Match-key messages
		$m_rows=$GLOBALS['SITE_DB']->query_select('match_key_messages',array('*'),NULL,'ORDER BY id');
		$m_rows[]=array('id'=>'new_1','k_message'=>'','k_match_key'=>'');
		$m_rows[]=array('id'=>'new_2','k_message'=>'','k_match_key'=>'');
		$m_rows[]=array('id'=>'new_3','k_message'=>'','k_match_key'=>'');
		$m_rows[]=array('id'=>'new_4','k_message'=>'','k_match_key'=>'');
		$m_rows[]=array('id'=>'new_5','k_message'=>'','k_match_key'=>'');
		$rows2=new ocp_tempcode();
		foreach ($m_rows as $row)
		{
			if ($row['k_message']==='') $msg=''; else $msg=get_translated_text($row['k_message']);
			$rows2->attach(do_template('PERMISSION_KEYS_MESSAGE_ROW',array('_GUID'=>'bf52d4ac938ce5c495b89d06a4cb9e5e','KEY'=>$row['k_match_key'],'MSG'=>$msg,'UID'=>is_integer($row['id'])?strval($row['id']):$row['id'])));
		}

		return do_template('PERMISSION_KEYS_PERMISSIONS_SCREEN',array('_GUID'=>'61a702db2df67adb2702ae6c7081b4ab','TITLE'=>$title,'COLS'=>$cols,'URL'=>$url,'HEADER_CELLS'=>$header_cells,'ROWS'=>$rows,'ROWS2'=>$rows2));
	}

	/**
	 * The actualiser to set match-key access.
	 *
	 * @return tempcode		The UI
	 */
	function set_keys_access()
	{
		set_helper_panel_pic('pagepics/matchkeysecurity');
		set_helper_panel_tutorial('tut_permissions');

		$title=get_screen_title('PAGE_MATCH_KEY_ACCESS');

		// Delete to cleanup
		$GLOBALS['SITE_DB']->query('DELETE FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'group_page_access WHERE page_name LIKE \''.db_encode_like('%:%').'\'');
		$mkeylang=collapse_2d_complexity('id','k_message',$GLOBALS['SITE_DB']->query_select('match_key_messages',array('id','k_message')));
		$GLOBALS['SITE_DB']->query_delete('match_key_messages');

		$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
		foreach ($_POST as $key=>$val)
		{
			if (get_magic_quotes_gpc()) $val=stripslashes($val);

			// See if we can tidy it back to a page-link (assuming it's not one already)
			$page_link=url_to_pagelink($val,true);
			if ($page_link!='') $val=$page_link;

			if ((substr($key,0,4)=='key_') && ($val!=''))
			{
				foreach (array_keys($groups) as $gid)
				{
					if (post_param_integer('p_'.substr($key,4).'__'.strval($gid),0)==1)
					{
						$GLOBALS['SITE_DB']->query_insert('group_page_access',array('zone_name'=>'/','page_name'=>$val,'group_id'=>$gid));
					}
				}
			}

			if ((substr($key,0,5)=='mkey_') && ($val!=''))
			{
				$id=substr($key,5);
				if ((substr($id,0,4)=='new_') || (!array_key_exists(intval($id),$mkeylang)))
				{
					$GLOBALS['SITE_DB']->query_insert('match_key_messages',array(
						'k_message'=>insert_lang(post_param('msg_'.$id),2),
						'k_match_key'=>$val
					));
				} else
				{
					$GLOBALS['SITE_DB']->query_insert('match_key_messages',array(
						'k_message'=>lang_remap($mkeylang[intval($id)],post_param('msg_'.$id)),
						'k_match_key'=>$val
					));
					unset($mkeylang[intval($id)]);
				}
			}
		}
		foreach ($mkeylang as $lid)
		{
			delete_lang($lid);
		}

		decache('main_sitemap');

		log_it('PAGE_MATCH_KEY_ACCESS');

		breadcrumb_set_parents(array(array('_SELF:_SELF:keys',do_lang_tempcode('PAGE_MATCH_KEY_ACCESS'))));
		breadcrumb_set_self(do_lang_tempcode('DONE'));

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'keys'),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The UI to set page access.
	 *
	 * @return tempcode		The UI
	 */
	function interface_page_access()
	{
		set_helper_panel_pic('pagepics/permissionstree');
		set_helper_panel_tutorial('tut_permissions');

		$title=get_screen_title('PAGE_ACCESS');

		$url=build_url(array('page'=>'_SELF','type'=>'_page'),'_SELF');

		$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
		$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);

		$header_cells=$this->_access_header($admin_groups,$groups);

		$cols=new ocp_tempcode();
		foreach ($groups as $id=>$g_name)
		{
			if (in_array($id,$admin_groups)) continue;
			$cols->attach(do_template('PERMISSION_COLUMN_SIZER'));
		}

		// Rows (pages)
		$rows=new ocp_tempcode();
		$zone=get_param('zone','!');
		if ($zone=='!') return $this->choose_zone($title);
		$zones=array($zone);
		$access_rows=$GLOBALS['SITE_DB']->query_select('group_page_access',array('page_name','zone_name','group_id'));
		foreach ($zones as $zone)
		{
			$pages=find_all_pages_wrap($zone);

			foreach (array_keys($pages) as $page)
			{
				$cells=new ocp_tempcode();
				$code='';

				$has=true;
				foreach ($groups as $id=>$g_name)
				{
					if (in_array($id,$admin_groups)) continue;

					$has_not_permission=false;
					foreach ($access_rows as $access_row)
					{
						if ($access_row===array('page_name'=>$page,'zone_name'=>$zone,'group_id'=>$id))
						{
							$has_not_permission=true;
							$has=false;
							break;
						}
					}

					$cells->attach(do_template('PERMISSION_CELL',array('_GUID'=>'094dde94ef78328074409e2d2388dcda','CHECKED'=>(!$has_not_permission),'HUMAN'=>do_lang_tempcode('PERMISSION_CELL',escape_html($page),escape_html($g_name)),'NAME'=>'p_'.$zone.'__'.$page.'__'.strval($id))));
					$code.='form.elements[\''.'p_'.$zone.'__'.$page.'__'.strval($id).'\'].checked=this.value==\'+\';';
				}

				$rows->attach(do_template('PERMISSION_ROW',array('_GUID'=>'127bc51f9d5d2d53c84ad54d09fd4fe6','HAS'=>$has,'ABBR'=>$page,'PERMISSION'=>$page,'CELLS'=>$cells,'CODE'=>$code)));
			}
		}

		breadcrumb_set_parents(array(array('_SELF:_SELF:page',do_lang_tempcode('CHOOSE'))));

		return do_template('PERMISSION_SCREEN_PERMISSIONS_SCREEN',array('_GUID'=>'1cfa15b2fd8c2828c897c6a5c974b201','COLS'=>$cols,'ZONE'=>$zone,'TITLE'=>$title,'URL'=>$url,'HEADER_CELLS'=>$header_cells,'ROWS'=>$rows));
	}

	/**
	 * The actualiser to set page access.
	 *
	 * @return tempcode		The UI
	 */
	function set_page_access()
	{
		set_helper_panel_pic('pagepics/permissionstree');
		set_helper_panel_tutorial('tut_permissions');

		$title=get_screen_title('PAGE_ACCESS');

		// Delete to cleanup
		$zone=post_param('zone');
		$GLOBALS['SITE_DB']->query('DELETE FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'group_page_access WHERE page_name NOT LIKE \''.db_encode_like('%:%').'\' AND '.db_string_equal_to('zone_name',$zone));

		$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
		$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
		$zones=array($zone);
		foreach ($zones as $zone)
		{
			$pages=find_all_pages_wrap($zone);

			foreach (array_keys($pages) as $page)
			{
				foreach (array_keys($groups) as $id)
				{
					if (in_array($id,$admin_groups)) continue;

					$val=post_param_integer('p_'.$zone.'__'.$page.'__'.strval($id),0);

					if ($val==0) // If we're denied permission, we make an entry (we store whether DENIED)
					{
						$GLOBALS['SITE_DB']->query_insert('group_page_access',array('zone_name'=>$zone,'page_name'=>$page,'group_id'=>$id));
					}
				}
			}
		}

		breadcrumb_set_parents(array(array('_SELF:_SELF:page',do_lang_tempcode('CHOOSE')),array('_SELF:_SELF:page:zone='.$zone,do_lang_tempcode('PAGE_ACCESS'))));
		breadcrumb_set_self(do_lang_tempcode('DONE'));

		decache('main_sitemap');
		require_code('caches3');
		erase_block_cache();
		erase_persistent_cache();

		log_it('PAGE_ACCESS');

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'page'),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * Get the list of sections that we can work through, in logical order.
	 *
	 * @return array		The section list
	 */
	function _get_ordered_sections()
	{
		$_sections=list_to_map('p_section',$GLOBALS['SITE_DB']->query_select('privilege_list',array('DISTINCT p_section')));
		foreach ($_sections as $i=>$s)
		{	
			if ($s['p_section']=='SECTION_FORUMS')
			{
				$_sections[$i]['trans']=do_lang('FORUMS_AND_MEMBERS');
			} else
			{
				$_sections[$i]['trans']=do_lang($s['p_section']);
			}
		}
		sort_maps_by($_sections,'trans');
		$orderings=array('SUBMISSION','GENERAL_SETTINGS','SECTION_FORUMS','STAFF_ACTIONS','_COMCODE','_FEEDBACK','POINTS');
		$_sections_prior=array();
		foreach ($orderings as $ordering)
		{
			if (array_key_exists($ordering,$_sections))
			{
				$x=$_sections[$ordering];
				unset($_sections[$ordering]);
				$_sections_prior[$ordering]=$x;
			}
		}
		if (count($_sections_prior)!=0) $_sections_prior['']=NULL;
		$_sections=array_merge($_sections_prior,$_sections);

		return $_sections;
	}

	/**
	 * The UI to set privileges.
	 *
	 * @return tempcode		The UI
	 */
	function interface_privileges()
	{
		require_all_lang();
		require_code('zones2');

		$title=get_screen_title('PRIVILEGES');

		$p_section=get_param('id',NULL);
		if ((is_null($p_section)) || ($p_section==''))
		{
			set_helper_panel_pic('pagepics/privileges');
			set_helper_panel_tutorial('tut_permissions');

			set_helper_panel_pic('pagepics/privileges');

			$fields=new ocp_tempcode();
			require_code('form_templates');

			$_sections=$this->_get_ordered_sections();
			$sections=new ocp_tempcode();
			$sections_common=new ocp_tempcode();
			$sections_uncommon=new ocp_tempcode();
			$doing_uncommon=false;
			foreach ($_sections as $s)
			{
				if (is_null($s))
				{
					$doing_uncommon=true;
				} else
				{
					if (!is_null($s['trans']))
					{
						if ($doing_uncommon)
						{
							$sections_uncommon->attach(form_input_list_entry($s['p_section'],false,$s['trans']));
						} else
						{
							$sections_common->attach(form_input_list_entry($s['p_section'],false,$s['trans']));
						}
					}
				}
			}
			$sections->attach(form_input_list_group(do_lang_tempcode('MOST_COMMON'),$sections_common));
			$sections->attach(form_input_list_group(do_lang_tempcode('OTHER'),$sections_uncommon));
			$fields->attach(form_input_huge_list(do_lang_tempcode('SECTION'),'','id',$sections,NULL,true));

			$post_url=get_self_url(false,false,NULL,false,true);

			return do_template('FORM_SCREEN',array('_GUID'=>'e5d457a49a76706afebc92da3d846e74','GET'=>true,'SKIP_VALIDATION'=>true,'HIDDEN'=>'','SUBMIT_NAME'=>do_lang_tempcode('CHOOSE'),'TITLE'=>$title,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>''));
		}

		$title=get_screen_title('_PRIVILEGES',true,array(do_lang_tempcode($p_section)));

		$url=build_url(array('page'=>'_SELF','type'=>'_privileges','id'=>$p_section),'_SELF');

		$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
		$moderator_groups=$GLOBALS['FORUM_DRIVER']->get_moderator_groups();
		$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);

		$header_cells=$this->_access_header($admin_groups,$groups);

		$cols=new ocp_tempcode();
		foreach ($groups as $id=>$g_name)
		{
			if (in_array($id,$admin_groups)) continue;
			$cols->attach(do_template('PERMISSION_COLUMN_SIZER'));
		}

		// Find all module permission overrides
		$all_module_overrides=array();
		foreach (find_all_zones() as $zone)
		{
			$all_modules=array();
			$all_modules+=find_all_pages($zone,'modules_custom','php',false);
			$all_modules+=find_all_pages($zone,'modules','php',false);

			foreach ($all_modules as $module=>$module_type)
			{
				$functions=extract_module_functions(zone_black_magic_filterer(get_file_base().'/'.$zone.(($zone=='')?'':'/').'pages/'.$module_type.'/'.$module.'.php'),array('get_privilege_overrides'));
				if (!is_null($functions[0]))
				{
					$overrides=is_array($functions[0])?call_user_func_array($functions[0][0],$functions[0][1]):eval($functions[0]);
					foreach (array_keys($overrides) as $override)
					{
						if (!array_key_exists($override,$all_module_overrides)) $all_module_overrides[$override]=array();
						$all_module_overrides[$override][]=$module;
					}
				}
			}
		}
		$all_page_permission_overridding=$GLOBALS['SITE_DB']->query_select('group_privileges',array('the_page','privilege'),array('category_name'=>''));

		// Rows (pages)
		$rows=new ocp_tempcode();
		$where=array('p_section'=>$p_section); // Added in because it was eating up too much memory
		$_permissions=collapse_2d_complexity('the_name','p_section',$GLOBALS['SITE_DB']->query_select('privilege_list',array('p_section','the_name'),$where,'ORDER BY p_section,the_name'));
		$access_rows=$GLOBALS['SITE_DB']->query_select('group_privileges',array('privilege','group_id'),array('the_page'=>'','module_the_name'=>'','category_name'=>''));
		$current_section='';
		$sections=new ocp_tempcode();
		$_false=do_template('PERMISSION_CELL',array('_GUID'=>'61aa7fa739e19caa1efb3695a5e2ab5d','CHECKED'=>false,'HUMAN'=>'__human__','NAME'=>'__name__'));
		$_true=do_template('PERMISSION_CELL',array('_GUID'=>'44a888b40d7a34aed6ed2bf8ff47f1de','CHECKED'=>true,'HUMAN'=>'__human__','NAME'=>'__name__'));
		$true=$_true->evaluate();
		$false=$_false->evaluate();

		// Ad-hoc sorting?
		$orderings=array(
			'submit_low','edit_own_low','edit_low','delete_own_low','delete_low','bypass_validation_low',
			'submit_mid','edit_own_mid','edit_mid','delete_own_mid','delete_mid','bypass_validation_mid',
			'submit_high','edit_own_high','edit_high','delete_own_high','delete_high','bypass_validation_high',
			'submit_cat_low','edit_own_cat_low','edit_cat_low','delete_own_cat_low','delete_cat_low','bypass_cat_validation_low',
			'submit_cat_mid','edit_own_cat_mid','edit_cat_mid','delete_own_cat_mid','delete_cat_mid','bypass_cat_validation_mid',
			'submit_cat_high','edit_own_cat_high','edit_cat_high','delete_own_cat_high','delete_cat_high','bypass_cat_validation_high',
		);
		$permissions_first=array();
		foreach ($orderings as $stub)
		{
			foreach ($_permissions as $permission=>$section)
			{
				if (substr($permission,0,strlen($stub))==$stub)
				{
					$permissions_first[$permission]=$section;
					unset($_permissions[$permission]);
				}
			}
		}
		$_permissions=array_merge($permissions_first,$_permissions);

		// Display
		foreach ($_permissions as $permission=>$section)
		{
			$permission_text=do_lang('PRIVILEGE_'.$permission,NULL,NULL,NULL,NULL,false);
			if (is_null($permission_text)) continue;

			if (($section!=$current_section) && ($current_section!=''))
			{
				$sections->attach(do_template('PERMISSION_S_CONFIG_SECTION',array('_GUID'=>'36bc9dfbeb7ee3d91f2a18057cd30551','HEADER_CELLS'=>$header_cells,'SECTION'=>$rows,'CURRENT_SECTION'=>do_lang_tempcode($current_section))));
				$rows=new ocp_tempcode();
			}

			$cells='';
			$code='';
			$has=true;

			foreach ($groups as $id=>$g_name)
			{
				if (in_array($id,$admin_groups)) continue;

				$has_permission=false;
				foreach ($access_rows as $access_row)
				{
					if (($access_row['privilege']==$permission) && ($access_row['group_id']==$id))
					{
						$has_permission=true;
						break;
					}
				}
				if (!$has_permission) $has=false;

				$cells.=str_replace('__human__',escape_html(addslashes(do_lang('PERMISSION_CELL',$permission_text,$g_name))),str_replace('__name__',$permission.'__'.strval($id),$has_permission?$true:$false));
				if (in_array($id,$moderator_groups)) $code.='form.elements[\''.$permission.'__'.strval($id).'\'].checked=true;'; else $code.='form.elements[\''.$permission.'__'.strval($id).'\'].checked=this.value==\'+\';';
			}

			if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($cells);

			$tpl_map=array('_GUID'=>'075f8855f0fed36b0d0f9c61108dd3de','HAS'=>$has,'ABBR'=>$permission,'PERMISSION'=>$permission_text,'CELLS'=>$cells,'CODE'=>$code);

			// See if any modules can override this
			if (array_key_exists($permission,$all_module_overrides))
			{
				$m_list='';
				$has_actual_overriding=false;
				foreach ($all_module_overrides[$permission] as $module)
				{
					$this_overrides=false;
					foreach ($all_page_permission_overridding as $po_row)
					{
						if (($po_row['the_page']==$module) && ($po_row['privilege']==$permission))
						{
							$this_overrides=true;
							break;
						}
					}

					if ($m_list!='') $m_list.=escape_html(', ');
					if ($this_overrides)
					{
						$has_actual_overriding=true;
						$m_list.='<s>'.escape_html($module).'</s>';
					} else
					{
						$m_list.='<strong>'.escape_html($module).'</strong>';
					}

					if ($module=='topics')
					{
						$m_list.=' ('.strtolower(do_lang((strpos($permission,'lowrange')!==false)?'FORUM_POSTS':'FORUM_TOPICS')).')';
					}
				}
				if (function_exists('ocp_mark_as_escaped')) ocp_mark_as_escaped($m_list);
				$tpl_map['DESCRIPTION']=do_lang_tempcode($has_actual_overriding?'PRIVILEGE_USED_IN_SLASHED':'PRIVILEGE_USED_IN',$m_list);
			}

			// Render row
			$rows->attach(do_template('PERMISSION_ROW',$tpl_map));

			$current_section=$section;
		}
		$sections->attach(do_template('PERMISSION_S_CONFIG_SECTION',array('_GUID'=>'c75a07373f54c0fa31d18e360fcf26f6','COLS'=>$cols,'HEADER_CELLS'=>$header_cells,'SECTION'=>$rows,'CURRENT_SECTION'=>do_lang_tempcode($current_section))));

		breadcrumb_set_parents(array(array('_SELF:_SELF:privileges',do_lang_tempcode('CHOOSE'))));

		return do_template('PERMISSION_S_PERMISSIONS_SCREEN',array('_GUID'=>'11974f0a137266a625991d3611b8e587','TITLE'=>$title,'URL'=>$url,'SECTIONS'=>$sections));
	}

	/**
	 * The actualiser to set privileges.
	 *
	 * @return tempcode		The UI
	 */
	function set_privileges()
	{
		require_all_lang();

		set_helper_panel_pic('pagepics/privileges');
		set_helper_panel_tutorial('tut_permissions');

		if ((count($_POST)==0) && (strtolower(ocp_srv('REQUEST_METHOD'))!='post')) warn_exit(do_lang_tempcode('PERMISSION_TRAGEDY_PREVENTED'));

		$title=get_screen_title('PRIVILEGES');

		$p_section=get_param('id');
		$_sections=$this->_get_ordered_sections();
		$array_keys=array_keys($_sections);
		$next_section=$array_keys[0];
		$counter=0;
		foreach ($_sections as $s)
		{
			if (is_null($s)) continue;

			if ($counter>array_search($p_section,$array_keys))
			{
				$next_section=$s['p_section'];
				break;
			}
			$counter++;
		}

		$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
		$permissions=collapse_1d_complexity('the_name',$GLOBALS['SITE_DB']->query_select('privilege_list',array('the_name'),array('p_section'=>$p_section)));
		$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
		foreach ($permissions as $permission)
		{
			foreach (array_keys($groups) as $id)
			{
				if (in_array($id,$admin_groups)) continue;

				$val=post_param_integer($permission.'__'.strval($id),0);

				// Delete to cleanup
				$GLOBALS['SITE_DB']->query_delete('group_privileges',array('privilege'=>$permission,'group_id'=>$id,'the_page'=>'','module_the_name'=>'','category_name'=>''),'',1);

				if ($val==1)
				{
					$GLOBALS['SITE_DB']->query_insert('group_privileges',array('privilege'=>$permission,'group_id'=>$id,'the_page'=>'','module_the_name'=>'','category_name'=>'','the_value'=>1));
				}
			}
		}

		breadcrumb_set_parents(array(array('_SELF:_SELF:privileges',do_lang_tempcode('CHOOSE'))));

		decache('main_sitemap');
		require_code('caches3');
		erase_block_cache();
		erase_persistent_cache();

		log_it('PRIVILEGES');

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'privileges','id'=>$next_section),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS_NOW_NEXT_SCREEN'));
	}

}


