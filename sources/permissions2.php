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

/**
 * Log permission checks to the permission_checks.log file
 *
 * @param  MEMBER         The user checking against
 * @param  ID_TEXT        The function that was called to check a permission
 * @param  array          Parameters to this permission-checking function
 * @param  boolean        Whether the permission was held
 */
function _handle_permission_check_logging($member,$op,$params,$result)
{
	global $PERMISSION_CHECK_LOGGER;

	if ($op=='has_specific_permission')
	{
		require_all_lang();
		$params[0]=$params[0].' ("'.do_lang('PT_'.$params[0]).'")';
	}

	$str=$op;
	if (count($params)!=0)
	{
		$str.=': ';
		foreach ($params as $i=>$p)
		{
			if ($i!=0) $str.=',';

			$str.=is_string($p)?$p:(is_null($p)?'':strval($p));
		}
	}

	$show_all=(get_value('permission_log_success_too')=='1');
	if (($PERMISSION_CHECK_LOGGER!==false) && (($show_all) || (!$result)))
	{
		fwrite($PERMISSION_CHECK_LOGGER,"\t".($show_all?'':'! ').$str);
		$username=$GLOBALS['FORUM_DRIVER']->get_username($member);
		if (is_null($username)) $username=do_lang('UNKNOWN');
		if ($member!=get_member()) fwrite($PERMISSION_CHECK_LOGGER,' -- '.$username);
		if ($show_all)
			fwrite($PERMISSION_CHECK_LOGGER,' --> '.($result?do_lang('YES'):do_lang('NO')).chr(10));
		fwrite($PERMISSION_CHECK_LOGGER,chr(10));
		sync_file(get_custom_file_base().'/data_custom/permissioncheckslog.php');
	}

	if ((function_exists('fb')) && (get_param_integer('keep_firephp',0)==1) && (!headers_sent()))
	{
		fb('Permission check '.($result?'PASSED':'FAILED').': '.$str);
	}
}

/**
 * Find if a group has a specified permission
 *
 * @param  GROUP			The being checked whether to have the permission
 * @param  ID_TEXT		The ID code for the permission being checked for
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 * @return boolean		Whether the member has the permission
 */
function has_specific_permission_group($group_id,$permission,$page=NULL,$cats=NULL)
{
	if (is_null($page)) $page=get_page_name();

	global $GROUP_PRIVILEGE_CACHE;
	if (array_key_exists($group_id,$GROUP_PRIVILEGE_CACHE))
	{
		if (!is_null($cats))
		{
			for ($i=0;$i<intval(floor(count($cats)/2));$i++)
			{
				if (is_null($cats[$i*2])) continue;
				if (isset($GROUP_PRIVILEGE_CACHE[$group_id][$permission][''][$cats[$i*2+0]][$cats[$i*2+1]]))
				{
					return $GROUP_PRIVILEGE_CACHE[$group_id][$permission][''][$cats[$i*2+0]][$cats[$i*2+1]]==1;
				}
			}
		}
		if ($page!='')
		{
			if (isset($GROUP_PRIVILEGE_CACHE[$group_id][$permission][$page]['']['']))
			{
				return $GROUP_PRIVILEGE_CACHE[$group_id][$permission][$page]['']['']==1;
			}
		}
		if (isset($GROUP_PRIVILEGE_CACHE[$group_id][$permission]['']['']['']))
		{
			return $GROUP_PRIVILEGE_CACHE[$group_id][$permission]['']['']['']==1;
		}
		return false;
	}

	$perhaps=$GLOBALS['SITE_DB']->query_select('gsp',array('*'),array('group_id'=>$group_id));
	if ((isset($GLOBALS['FORUM_DB'])) && ($GLOBALS['SITE_DB']->connection_write!=$GLOBALS['FORUM_DB']->connection_write) && (get_forum_type()=='ocf'))
	{
		$perhaps=array_merge($perhaps,$GLOBALS['FORUM_DB']->query('SELECT * FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'gsp WHERE group_id='.strval($group_id).' AND '.db_string_equal_to('module_the_name','forums'),NULL,NULL,false,true));
	}
	$GROUP_PRIVILEGE_CACHE[$group_id]=array();
	foreach ($perhaps as $p)
	{
		if (@$GROUP_PRIVILEGE_CACHE[$group_id][$p['specific_permission']][$p['the_page']][$p['module_the_name']][$p['category_name']]!=1)
			$GROUP_PRIVILEGE_CACHE[$group_id][$p['specific_permission']][$p['the_page']][$p['module_the_name']][$p['category_name']]=$p['the_value'];
	}

	return has_specific_permission_group($group_id,$permission,$page,$cats);
}

/**
 * Gather the permissions for the specified category as a form field input matrix.
 *
 * @param  ID_TEXT		The ID code for the module being checked for category access
 * @param  ID_TEXT		The ID code for the category being checked for access (often, a number cast to a string)
 * @param  ?ID_TEXT		The page this is for (NULL: current page)
 * @param  ?tempcode		Extra help to show in interface (NULL: none)
 * @param  boolean		Whether this is a new category (don't load permissions, default to on)
 * @param  ?tempcode		Label for view permissions (NULL: default)
 * @return tempcode		The form field matrix
 */
function get_category_permissions_for_environment($module,$category,$page=NULL,$help=NULL,$new_category=false,$pinterface_view=NULL)
{
	if (is_null($page)) $page=get_page_name();
	if ($category=='-1') $category=NULL;
	if ($category=='') $category=NULL;

	$server_id=get_module_zone($page).':'.$page; // $category is not of interest to us because we use this to find our inheritance settings

	$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
	$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true,true);

	// View access
	$access=array();
	foreach (array_keys($groups) as $id)
	{
		$access[$id]=$new_category?1:0;
	}
	if (!$new_category)
	{
		$access_rows=$GLOBALS[($module=='forums')?'FORUM_DB':'SITE_DB']->query_select('group_category_access',array('group_id'),array('module_the_name'=>$module,'category_name'=>$category));
		foreach ($access_rows as $row)
		{
			$access[$row['group_id']]=1;
		}
	}

	// privileges
	$specific_permissions=array();
	$access_rows=$GLOBALS[($module=='forums')?'FORUM_DB':'SITE_DB']->query_select('gsp',array('group_id','specific_permission','the_value'),array('module_the_name'=>$module,'category_name'=>$category));
	foreach ($access_rows as $row)
	{
		$specific_permissions[$row['specific_permission']][$row['group_id']]=strval($row['the_value']);
	}

	// Heading
	require_code('zones2');
	$_overridables=extract_module_functions_page(get_module_zone($page),$page,array('get_sp_overrides'));
	$out=new ocp_tempcode;
	if (is_null($_overridables[0]))
	{
		$temp=do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('PERMISSIONS'),'HELP'=>$help,'SECTION_HIDDEN'=>true));
		$overridables=array();
	} else
	{
		require_lang('permissions');
		$temp=do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('PERMISSIONS'),'HELP'=>do_lang_tempcode('PINTERACE_HELP'),'SECTION_HIDDEN'=>true));
		$overridables=is_array($_overridables[0])?call_user_func_array($_overridables[0][0],$_overridables[0][1]):eval($_overridables[0]);
	}
	$out->attach($temp);

	// Find out inherited permissions
	$default_access=array();
	$all_groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true);
	foreach (array_keys($access) as $id)
		if ((!array_key_exists($id,$groups)) && (array_key_exists($id,$all_groups)))
			$groups[$id]=$all_groups[$id];
	foreach ($groups as $id=>$group_name)
	{
		$default_access[$id]=array();
		if (!in_array($id,$admin_groups))
		{
			foreach ($overridables as $override=>$cat_support)
			{
				if (is_array($cat_support)) $cat_support=$cat_support[0];

				$default_access[$id][$override]=array();
				if ($cat_support==0) continue;
				$default_access[$id][$override]=has_specific_permission_group($id,$override,$page)?'1':'0';
			}
		}
	}

	// Render actual permissions matrix
	$out->attach(get_permissions_matrix($server_id,$access,$overridables,$specific_permissions,$default_access,false,$pinterface_view));

	return $out;
}

/**
 * Create a form field input matrix for permission setting.
 *
 * @param  ID_TEXT		Permission ID (pagelink style) for the resource being set
 * @param  array			An inverted list showing what view permissions are set for what we're setting permissions for
 * @param  array			List of overridable privilege codes for what we're setting permissions for
 * @param  array			List of privilege settings relating to what we're setting permissions for, from the database
 * @param  array			Multi-dimensional array showing what the inherited defaults for this permission would be
 * @param  boolean		Whether to not include the stuff to make it fit alongside other form fields in a normal form table
 * @param  ?tempcode		Label for view permissions (NULL: default)
 * @return tempcode		The form field matrix
 */
function get_permissions_matrix($server_id,$access,$overridables,$specific_permissions,$default_access,$no_outer=false,$pinterface_view=NULL)
{
	require_lang('permissions');
	require_javascript('javascript_permissions');

	$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
	$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true,true);

	if (is_null($pinterface_view)) $pinterface_view=do_lang_tempcode('PINTERFACE_VIEW');

	// Permission rows for matrix
	$permission_rows=new ocp_tempcode();
	$all_groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true);
	foreach (array_keys($access) as $id)
		if ((!array_key_exists($id,$groups)) && (array_key_exists($id,$all_groups)))
			$groups[$id]=$all_groups[$id];
	foreach ($groups as $id=>$group_name)
	{
		if (!in_array($id,$admin_groups))
		{
			$perhaps=(count($access)==0)?1:$access[$id];
			$view_access=$perhaps==1;
			$tabindex=get_form_field_tabindex(NULL);

			$overrides=new ocp_tempcode();
			$all_global=true;
			foreach (array_keys($overridables) as $override)
			{
				if (isset($specific_permissions[$override][$id])) $all_global=false;
			}
			foreach ($overridables as $override=>$cat_support)
			{
				$lang_string=do_lang_tempcode('PT_'.$override);
				if (is_array($cat_support)) $lang_string=do_lang_tempcode($cat_support[1]);
				if (is_array($cat_support)) $cat_support=$cat_support[0];
				if ($cat_support==0) continue;

				$overrides->attach(do_template('FORM_SCREEN_INPUT_PERMISSION_OVERRIDE',array('_GUID'=>'115fbf91873be9016c5e192f5a5e090b','FORCE_PRESETS'=>$no_outer,'GROUP_NAME'=>$group_name,'VIEW_ACCESS'=>$view_access,'TABINDEX'=>strval($tabindex),'GROUP_ID'=>strval($id),'SP'=>$override,'ALL_GLOBAL'=>$all_global,'TITLE'=>$lang_string,'DEFAULT_ACCESS'=>$default_access[$id][$override],'CODE'=>isset($specific_permissions[$override][$id])?$specific_permissions[$override][$id]:'-1')));

				check_suhosin_request_quantity(1,strlen('access_'.strval($id).'_sp_'.$override));
			}
			$permission_rows->attach(do_template('FORM_SCREEN_INPUT_PERMISSION',array('_GUID'=>'e2c4459ae995d33376c07e498f1d973a','FORCE_PRESETS'=>$no_outer,'GROUP_NAME'=>$group_name,'OVERRIDES'=>$overrides->evaluate()/*FUDGEFUDGE*/,'ALL_GLOBAL'=>$all_global,'VIEW_ACCESS'=>$view_access,'TABINDEX'=>strval($tabindex),'GROUP_ID'=>strval($id),'PINTERFACE_VIEW'=>$pinterface_view)));

			check_suhosin_request_quantity(2,strlen('access_'.strval($id)));
		} else
		{
			$overridables_filtered=array();
			foreach ($overridables as $override=>$cat_support)
			{
				if (is_array($cat_support)) $cat_support=$cat_support[0];
				if ($cat_support==1) $overridables_filtered[$override]=1;
			}
			$permission_rows->attach(do_template('FORM_SCREEN_INPUT_PERMISSION_ADMIN',array('_GUID'=>'59fafa2fa66ec6eb0fe2432b1d747636','FORCE_PRESETS'=>$no_outer,'OVERRIDES'=>$overridables_filtered,'GROUP_NAME'=>$group_name,'GROUP_ID'=>strval($id),'PINTERFACE_VIEW'=>$pinterface_view)));
		}
	}
	if ((count($overridables)==0) && (!$no_outer))
		return $permission_rows;

	// Find out colour for our vertical text image headings (CSS can't rotate text), using the CSS as a basis
	$tmp_file=@file_get_contents(get_custom_file_base().'/themes/'.$GLOBALS['FORUM_DRIVER']->get_theme().'/templates_cached/'.user_lang().'/global.css');
	$color='FF00FF';
	if ($tmp_file!==false)
	{
		$matches=array();
		if (preg_match('#(\n|\})th[\s,][^\}]*(\s|\{)background-color:\s*\#([\dA-Fa-f]*);color:\s*\#([\dA-Fa-f]*);#sU',$tmp_file,$matches)!=0)
		{
			$color=$matches[3].'&fgcolor='.$matches[4];
		}
	}

	// For heading up the table matrix
	$overrides_array=array();
	foreach ($overridables as $override=>$cat_support)
	{
		$lang_string=do_lang_tempcode('PT_'.$override);
		if (is_array($cat_support)) $lang_string=do_lang_tempcode($cat_support[1]);
		if (is_array($cat_support)) $cat_support=$cat_support[0];
		if ($cat_support==0) continue;

		$overrides_array[$override]=array('TITLE'=>$lang_string);
	}

	// Finish off the matrix and return
	$inner=do_template('FORM_SCREEN_INPUT_PERMISSION_MATRIX',array('_GUID'=>'0f019c7e60366fa04058097ee6f3829a','SERVER_ID'=>$server_id,'COLOR'=>$color,'OVERRIDES'=>$overrides_array,'PERMISSION_ROWS'=>$permission_rows));

	if ($no_outer) return make_string_tempcode(static_evaluate_tempcode($inner));
	return make_string_tempcode(static_evaluate_tempcode(do_template('FORM_SCREEN_INPUT_PERMISSION_MATRIX_OUTER',array('_GUID'=>'2a2f9f78f3639185300c92cab50767c5','INNER'=>$inner))));
}

/**
 * Assuming that permission details are POSTed, set the permissions for the specified category, in the current page
 *
 * @param  ID_TEXT		The ID code for the module being checked for category access
 * @param  ID_TEXT		The ID code for the category being checked for access (often, a number cast to a string)
 * @param  ?ID_TEXT		The page this is for (NULL: current page)
 */
function set_category_permissions_from_environment($module,$category,$page=NULL)
{
	if (is_null($page)) $page=get_page_name();

	require_code('zones2');

	$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
	$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);

	// Based on old access settings, we may need to look at additional groups (clubs) that have permissions here
	$access=array();
	$access_rows=$GLOBALS[($module=='forums')?'FORUM_DB':'SITE_DB']->query_select('group_category_access',array('group_id'),array('module_the_name'=>$module,'category_name'=>$category));
	foreach ($access_rows as $row)
		$access[$row['group_id']]=1;
	$all_groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true);
	foreach (array_keys($access) as $id)
		if ((!array_key_exists($id,$groups)) && (array_key_exists($id,$all_groups)))
			$groups[$id]=$all_groups[$id];

	foreach (array_keys($groups) as $group_id) // Only delete PERMISSIVE groups, so not to effect clubs
	{
		if (in_array($group_id,$admin_groups)) continue;

		$GLOBALS[($module=='forums')?'FORUM_DB':'SITE_DB']->query_delete('group_category_access',array('module_the_name'=>$module,'category_name'=>$category,'group_id'=>$group_id));
	}

	$_overridables=extract_module_functions_page(get_module_zone($page),$page,array('get_sp_overrides'));
	if (is_null($_overridables[0]))
	{
		$overridables=array();
	} else
	{
		$overridables=is_array($_overridables[0])?call_user_func_array($_overridables[0][0],$_overridables[0][1]):eval($_overridables[0]);
	}

	foreach ($overridables as $override=>$cat_support)
	{
		if (is_array($cat_support)) $cat_support=$cat_support[0];
		$GLOBALS[($module=='forums')?'FORUM_DB':'SITE_DB']->query_delete('gsp',array('specific_permission'=>$override,'module_the_name'=>$module,'category_name'=>$category));
	}
	foreach (array_keys($groups) as $group_id)
	{
		if (in_array($group_id,$admin_groups)) continue;

		$value=post_param_integer('access_'.strval($group_id),0);
		if ($value==1)
		{
			$GLOBALS[($module=='forums')?'FORUM_DB':'SITE_DB']->query_insert('group_category_access',array('module_the_name'=>$module,'category_name'=>$category,'group_id'=>$group_id),false,true); // Race/corruption condition
		}
		foreach ($overridables as $override=>$cat_support)
		{
			if (is_array($cat_support)) $cat_support=$cat_support[0];
			if ($cat_support==0) continue;

			$value=post_param_integer('access_'.strval($group_id).'_sp_'.$override,-1);
			if ($value!=-1)
			{
				$GLOBALS[($module=='forums')?'FORUM_DB':'SITE_DB']->query_insert('gsp',array('specific_permission'=>$override,'group_id'=>$group_id,'module_the_name'=>$module,'category_name'=>$category,'the_page'=>'','the_value'=>$value));
			}
		}
	}

	decache('main_sitemap');
}

/**
 * Gather the permissions for the specified page as form field inputs.
 *
 * @param  ID_TEXT		The ID code for the zone
 * @param  ID_TEXT		The ID code for the page
 * @param  ?tempcode		Extra help to show in interface (NULL: none)
 * @return tempcode		The form fields
 */
function get_page_permissions_for_environment($zone,$page,$help=NULL)
{
	require_lang('permissions');

	$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
	$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true,true);

	// View access
	$access=array();
	foreach (array_keys($groups) as $id)
	{
		$access[$id]=0;
	}
	$access_rows=$GLOBALS['SITE_DB']->query_select('group_page_access',array('group_id'),array('zone_name'=>$zone,'page_name'=>$page));
	foreach ($access_rows as $row)
	{
		$access[$row['group_id']]=1;
	}

	// Interface
	$fields=new ocp_tempcode();
	$temp=do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('PERMISSIONS'),'HELP'=>$help,'SECTION_HIDDEN'=>true));
	$fields->attach($temp);
	foreach ($groups as $id=>$group_name)
	{
		if (!in_array($id,$admin_groups))
		{
			$perhaps=$access[$id];
			$overrides=array();
			$temp=form_input_tick(do_lang_tempcode('ACCESS_FOR',escape_html($group_name)),do_lang_tempcode('DESCRIPTION_ACCESS_FOR',escape_html($group_name)),'access_'.strval($id),$perhaps==0);
			$fields->attach($temp);
		}
	}

	return $fields;
}

/**
 * Assuming that permission details are POSTed, set the permissions for the specified category, in the current page
 *
 * @param  ID_TEXT		The ID code for the zone
 * @param  ID_TEXT		The ID code for the page
 */
function set_page_permissions_from_environment($zone,$page)
{
	if (is_null($page)) $page=get_page_name();

	$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
	$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
	$GLOBALS['SITE_DB']->query_delete('group_page_access',array('zone_name'=>$zone,'page_name'=>$page));

	foreach (array_keys($groups) as $group_id)
	{
		if (in_array($group_id,$admin_groups)) continue;

		$value=post_param_integer('access_'.strval($group_id),0);
		if ($value==0)
		{
			$GLOBALS['SITE_DB']->query_insert('group_page_access',array('zone_name'=>$zone,'page_name'=>$page,'group_id'=>$group_id),false,true); // Race/corruption condition
		}
	}

	decache('main_sitemap');
	$GLOBALS['SITE_DB']->query_delete('cache');
	if (function_exists('persistent_cache_empty')) persistent_cache_empty();
}

