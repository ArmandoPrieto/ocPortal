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
 * @package		search
 */

/**
 * Module page class.
 */
class Module_search
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
		$info['version']=4;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('searches_saved');
		$GLOBALS['SITE_DB']->drop_if_exists('searches_logged');
		delete_menu_item_simple('_SEARCH:search:type=misc:id=ocf_posts');
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
			$GLOBALS['SITE_DB']->create_table('searches_saved',array(
				'id'=>'*AUTO',
				's_title'=>'SHORT_TEXT',
				's_member_id'=>'USER',
				's_time'=>'TIME',
				's_primary'=>'SHORT_TEXT',
				's_auxillary'=>'LONG_TEXT',
			));

			$GLOBALS['SITE_DB']->create_table('searches_logged',array(
				'id'=>'*AUTO',
				's_member_id'=>'USER',
				's_time'=>'TIME',
				's_primary'=>'SHORT_TEXT',
				's_auxillary'=>'LONG_TEXT',
				's_num_results'=>'INTEGER',
			));

			$GLOBALS['SITE_DB']->create_index('searches_logged','past_search',array('s_primary'));

			add_menu_item_simple('forum_features',NULL,'SEARCH','_SEARCH:search:type=misc:id=ocf_posts',0,0,true,do_lang('ZONE_BETWEEN'));
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<4))
		{
			$GLOBALS['SITE_DB']->create_index('searches_logged','#past_search_ft',array('s_primary'));
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return is_guest()?array('misc'=>'SEARCH_TITLE'):array('misc'=>'SEARCH_TITLE','my'=>'SAVED_SEARCHES');
	}
	
	/**
	 * Standard modular page-link finder function (does not return the main entry-points that are not inside the tree).
	 *
	 * @param  ?integer  The number of tree levels to computer (NULL: no limit)
	 * @param  boolean	Whether to not return stuff that does not support permissions (unless it is underneath something that does).
	 * @param  ?string	Position to start at in the tree. Does not need to be respected. (NULL: from root)
	 * @param  boolean	Whether to avoid returning categories.
	 * @return ?array	 	A tuple: 1) full tree structure [made up of (pagelink, permission-module, permissions-id, title, children, ?entry point for the children, ?children permission module, ?whether there are children) OR a list of maps from a get_* function] 2) permissions-page 3) optional base entry-point for the tree 4) optional permission-module 5) optional permissions-id (NULL: disabled).
	 */
	function get_page_links($max_depth=NULL,$require_permission_support=false,$start_at=NULL,$dont_care_about_categories=false)
	{
		$permission_page=NULL;

		if (!is_null($start_at))
		{
			$matches=array();
			if (preg_match('#[^:]*:search:type=misc:id=(.*)#',$start_at,$matches)!=0) // Could only be catalogues
			{
				$kids=array();
				if ($dont_care_about_categories)
				{
					$rows=array();
				} else
				{
					$query='SELECT c.c_title,c.c_name,t.text_original FROM '.get_table_prefix().'catalogues c';
					if (can_arbitrary_groupby())
						$query.=' JOIN '.get_table_prefix().'catalogue_entries e ON e.c_name=c.c_name';
					$query.=' LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND c.c_title=t.id';
					if (can_arbitrary_groupby())
						$query.=' GROUP BY c.c_name';
					$rows=$GLOBALS['SITE_DB']->query($query);
				}
				foreach ($rows as $row)
				{
					if (is_null($row['text_original'])) $row['text_original']=get_translated_text($row['c_title']);

					$kids[]=array('_SELF:_SELF:type=misc:id=catalogue_entries:catalogue_name='.$row['c_name'],NULL,NULL,$row['text_original'],array());
				}

				return array($kids,$permission_page);
			}
		}

		$tree=array();
		if ((!$require_permission_support) && ($max_depth>0))
		{
			$_hooks=find_all_hooks('modules','search');
			foreach (array_keys($_hooks) as $hook)
			{
				require_code('hooks/modules/search/'.filter_naughty_harsh($hook));
				$object=object_factory('Hook_search_'.filter_naughty_harsh($hook),true);
				if (is_null($object)) continue;
				$info=$object->info();
				if (is_null($info)) continue;

				if (($hook=='catalogue_entries') || (array_key_exists('special_on',$info)) || (array_key_exists('special_off',$info)) || (method_exists($object,'get_tree')) || (method_exists($object,'ajax_tree')))
				{
					$kids=array();
					if (($hook=='catalogue_entries') && ($max_depth>1))
					{
						if ($dont_care_about_categories)
						{
							$rows=array();
						} else
						{
							$query='SELECT c.c_title,c.c_name,t.text_original FROM '.get_table_prefix().'catalogues c';
							if (can_arbitrary_groupby())
								$query.=' JOIN '.get_table_prefix().'catalogue_entries e ON e.c_name=c.c_name';
							$query.=' LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND c.c_title=t.id';
							if (can_arbitrary_groupby())
								$query.=' GROUP BY c.c_name';
							$rows=$GLOBALS['SITE_DB']->query($query);
						}
						foreach ($rows as $row)
						{
							if (!has_category_access(get_member(),'catalogues_catalogue',$row['c_name'])) continue;
							
							if (is_null($row['text_original'])) $row['text_original']=get_translated_text($row['c_title']);

							$kids[]=array('_SELF:_SELF:type=misc:id='.$hook.':catalogue_name='.$row['c_name'],NULL,NULL,$row['text_original'],array());
						}
					}
					$tree[]=array('_SELF:_SELF:type=misc:id='.$hook,NULL,NULL,$info['lang'],$kids,'','',$hook=='catalogue_entries');
				}
			}
		}
		return array($tree,$permission_page);
	}

	/**
	 * Standard modular new-style deep page-link finder function (does not return the main entry-points).
	 *
	 * @param  string  	Callback function to send discovered page-links to.
	 * @param  MEMBER		The member we are finding stuff for (we only find what the member can view).
	 * @param  integer	Code for how deep we are tunnelling down, in terms of whether we are getting entries as well as categories.
	 * @param  string		Stub used to create page-links. This is passed in because we don't want to assume a zone or page name within this function.
	 * @param  ?string	Where we're looking under (NULL: root of tree). We typically will NOT show a root node as there's often already an entry-point representing it.
	 * @param  integer	Our recursion depth (used to calculate importance of page-link, used for instance by Google sitemap). Deeper is typically less important.
	 * @param  ?array		Non-standard for API [extra parameter tacked on] (NULL: yet unknown). Contents of database table for performance.
	 * @param  ?array		Non-standard for API [extra parameter tacked on] (NULL: yet unknown). Contents of database table for performance.
	 */
	function get_sitemap_pagelinks($callback,$member_id,$depth,$pagelink_stub,$parent_pagelink=NULL,$recurse_level=0,$category_data=NULL,$entry_data=NULL)
	{
		$parent_pagelink=$pagelink_stub.':misc'; // This is the entry-point we're under

		$_hooks=find_all_hooks('modules','search');
		foreach (array_keys($_hooks) as $hook)
		{
			require_code('hooks/modules/search/'.filter_naughty_harsh($hook));
			$object=object_factory('Hook_search_'.filter_naughty_harsh($hook),true);
			if (is_null($object)) continue;
			$info=$object->info();
			if (is_null($info)) continue;

			if (($hook=='catalogue_entries') || (array_key_exists('special_on',$info)) || (array_key_exists('special_off',$info)) || (method_exists($object,'get_tree')) || (method_exists($object,'ajax_tree')))
			{
				$kids=array();
				if ($hook=='catalogue_entries')
				{
					$query='SELECT c.c_title,c.c_name,t.text_original FROM '.get_table_prefix().'catalogues c';
					if (can_arbitrary_groupby())
						$query.=' JOIN '.get_table_prefix().'catalogue_entries e ON e.c_name=c.c_name';
					$query.=' LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND c.c_title=t.id';
					if (can_arbitrary_groupby())
						$query.=' GROUP BY c.c_name';
					$rows=$GLOBALS['SITE_DB']->query($query);
					foreach ($rows as $row)
					{
						if (!has_category_access($member_id,'catalogues_catalogue',$row['c_name'])) continue;
						
						if (is_null($row['text_original'])) $row['text_original']=get_translated_text($row['c_title']);

						$pagelink=$pagelink_stub.'misc:id='.$hook.':catalogue_name='.$row['c_name'];
						call_user_func_array($callback,array($pagelink,$pagelink_stub.'misc:id='.$hook,NULL,NULL,0.2,$row['text_original'])); // Callback
					}
				}

				$pagelink=$pagelink_stub.'misc:id='.$hook;
				call_user_func_array($callback,array($pagelink,$parent_pagelink,NULL,NULL,0.2,$info['lang'])); // Callback
			}
		}
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_lang('search');
		require_css('search');
		require_code('database_search');

		if (function_exists('set_time_limit')) @set_time_limit(15); // We really don't want to let it thrash the DB too long

		$type=get_param('type','misc');
		if (($type=='misc') || ($type=='results')) return $this->form();
		//if ($type=='results') return $this->results();
		if ($type=='my') return $this->my();
		if ($type=='_delete') return $this->_delete();

		return new ocp_tempcode();
	}

	/**
	 * The UI to choose a saved search.
	 *
	 * @return tempcode		The UI
	 */
	function my()
	{
		if (is_guest()) access_denied('NOT_AS_GUEST');

		require_code('templates_results_table');

		$title=get_page_title('SAVED_SEARCHES');

		$start=get_param_integer('start',0);
		$max=get_param_integer('max',50);
		$sortables=array('s_time'=>do_lang_tempcode('DATE_TIME'),'s_title'=>do_lang_tempcode('TITLE'));
		list($sortable,$sort_order)=explode(' ',get_param('sort','s_time DESC'),2);
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';
		$fields_title=results_field_title(array(do_lang_tempcode('TITLE'),do_lang_tempcode('DATE_TIME'),do_lang_tempcode('DELETE'),do_lang_tempcode('RUN_SEARCH')),$sortables,'sort',$sortable.' '.$sort_order);
		$max_rows=$GLOBALS['SITE_DB']->query_value('searches_saved','COUNT(*)',array('s_member_id'=>get_member()));
		$rows=$GLOBALS['SITE_DB']->query_select('searches_saved',array('*'),array('s_member_id'=>get_member()),'ORDER BY '.$sortable.' '.$sort_order,$max,$start);
		$fields=new ocp_tempcode();
		foreach ($rows as $row)
		{
			$post_url=build_url(array('page'=>'_SELF','type'=>'_delete'),'_SELF');
			$deletion_button=do_template('SEARCH_SAVED_DELETION_BUTTON',array('_GUID'=>'ac55dd5cd40e2ee09f5ac48110ee7215','NAME'=>$row['s_title'],'URL'=>$post_url,'ID'=>strval($row['id'])));

			$post_url=build_url(array('page'=>'_SELF','type'=>'results'),'_SELF',NULL,false,true);
			$hidden=new ocp_tempcode();
			$post=unserialize($row['s_auxillary']);
			foreach ($post as $key=>$val)
			{
				if ($key!='save_title')
				{
					if (get_magic_quotes_gpc()) $val=stripslashes($val);
					$hidden->attach(form_input_hidden($key,$val));
				}
			}
			$run_button=do_template('SEARCH_SAVED_RUN_BUTTON',array('_GUID'=>'8ce6e09b76cfd6a1db59f1ab46376feb','NAME'=>$row['s_title'],'URL'=>$post_url,'HIDDEN'=>$hidden));

			$fields->attach(results_entry(array($row['s_title'],get_timezoned_date($row['s_time']),$deletion_button,$run_button),true));
		}
		$searches=results_table(do_lang_tempcode('SAVED_SEARCHES'),$start,'start',$max,'max',$max_rows,$fields_title,$fields,$sortables,$sortable,$sort_order,'sort',new ocp_tempcode());

		$post_url=build_url(array('page'=>'_SELF','type'=>'my'),'_SELF');

		return do_template('SEARCH_SAVED_SCREEN',array('_GUID'=>'f9a7116b8525eb223bde50dfb991f39f','TITLE'=>$title,'SEARCHES'=>$searches,'URL'=>$post_url));
	}

	/**
	 * The actualiser to delete a saved search.
	 *
	 * @return tempcode		The UI
	 */
	function _delete()
	{
		$title=get_page_title('DELETE_SAVED_SEARCH');

		if (is_guest()) access_denied('NOT_AS_GUEST');

		$GLOBALS['SITE_DB']->query_delete('searches_saved',array('id'=>post_param_integer('id'),'s_member_id'=>get_member()),'',1);

		$url=build_url(array('page'=>'_SELF','type'=>'my'),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The UI to do a search.
	 *
	 * @return tempcode		The UI
	 */
	function form()
	{
		$id=get_param('id','');

		$title=get_page_title('SEARCH_TITLE');

		require_code('templates_internalise_screen');

		if ($id!='') // Specific screen, prepare
		{
			require_code('hooks/modules/search/'.filter_naughty_harsh($id),true);
			$object=object_factory('Hook_search_'.filter_naughty_harsh($id));
			$info=$object->info();

			if (!is_null($info))
				$title=get_page_title('_SEARCH_TITLE',true,array($info['lang']));

			breadcrumb_set_parents(array(array('_SELF:_SELF',do_lang_tempcode('SEARCH_FOR'))));
			breadcrumb_set_self($info['lang']);

			$under=get_param('search_under','!',true);
			if ((!is_null($info)) && (method_exists($object,'get_tree'))) $object->get_tree($under);
			
			if (!is_null($info))
				$test_tpl=internalise_own_screen($title);
			else $test_tpl=NULL;
		} else
		{
			$test_tpl=internalise_own_screen($title);
		}

		if (is_object($test_tpl))
		{
			return $test_tpl;
		}

		require_javascript('javascript_ajax');
		require_javascript('javascript_ajax_people_lists');

		$content=get_param('content',NULL,true);

		$user_label=do_lang_tempcode('SEARCH_USER');
		$days_label=do_lang_tempcode('SUBMITTED_WITHIN');

		$extra_sort_fields=array();

		if ($id!='') // Specific screen
		{
			$url_map=array('page'=>'_SELF','type'=>'results','id'=>$id,'specific'=>1);
			$catalogue_name=get_param('catalogue_name','');
			if ($catalogue_name!='') $url_map['catalogue_name']=$catalogue_name;
			$force_non_tabular=get_param_integer('force_non_tabular',0);
			if ($force_non_tabular==1) $url_map['force_non_tabular']=1;
			$url=build_url($url_map,'_SELF',NULL,false,true);

			require_code('hooks/modules/search/'.filter_naughty_harsh($id),true);
			$object=object_factory('Hook_search_'.filter_naughty_harsh($id));
			$info=$object->info();
			if (is_null($info)) warn_exit(do_lang_tempcode('SEARCH_HOOK_NOT_AVAILABLE'));

			if (array_key_exists('user_label',$info)) $user_label=$info['user_label'];
			if (array_key_exists('days_label',$info)) $days_label=$info['days_label'];
			
			$extra_sort_fields=array_key_exists('extra_sort_fields',$info)?$info['extra_sort_fields']:array();

			$under=NULL;
			if (method_exists($object,'ajax_tree'))
			{
				require_javascript('javascript_tree_list');
				require_javascript('javascript_more');
				$ajax=true;
				$under=get_param('search_under','-1',true);
				list($ajax_hook,$ajax_options)=$object->ajax_tree();

				require_code('hooks/systems/ajax_tree/'.$ajax_hook);
				$tree_hook_object=object_factory('Hook_'.$ajax_hook);
				$simple_content=$tree_hook_object->simple(NULL,$ajax_options,preg_replace('#,.*$#','',$under));

				$nice_label=$under;
				if (!is_null($under))
				{
					$simple_content_evaluated=$simple_content->evaluate();
					$matches=array();
					if (preg_match('#<option [^>]*value="'.str_replace('#','\#',preg_quote($under)).'('.((strpos($under,',')===false)?',':'').'[^"]*)?"[^>]*>([^>]* &gt; )?([^>]*)</option>#',$simple_content_evaluated,$matches)!=0)
					{
						if (strpos($under,',')===false) $under=$under.$matches[1];
						$nice_label=trim($matches[3]);
					}
				}

				require_code('form_templates');
				$tree=do_template('FORM_SCREEN_INPUT_TREE_LIST',array('_GUID'=>'25368e562be3b4b9c6163aa008b47c91','TABINDEX'=>strval(get_form_field_tabindex()),'NICE_LABEL'=>(is_null($nice_label) || $nice_label=='-1')?'':$nice_label,'END_OF_FORM'=>true,'REQUIRED'=>false,'USE_SERVER_ID'=>false,'NAME'=>'search_under','DEFAULT'=>$under,'HOOK'=>$ajax_hook,'ROOT_ID'=>'','OPTIONS'=>serialize($ajax_options)));
			} else
			{
				$ajax=false;
				$tree=form_input_list_entry('!',false,do_lang_tempcode('NA_EM'));
				if (method_exists($object,'get_tree'))
				{
					$under=get_param('search_under','!',true);
					$tree->attach($object->get_tree($under));
				}
			}

			$options=new ocp_tempcode();
			if (array_key_exists('special_on',$info))
				foreach ($info['special_on'] as $name=>$display)
					$options->attach(do_template('SEARCH_FOR_SEARCH_DOMAIN_OPTION',array('_GUID'=>'c1853f42d0a110026453f8b94c9f623c','CHECKED'=>(!is_null($content)) || (get_param_integer('option_'.$id.'_'.$name,0)==1),'NAME'=>'option_'.$id.'_'.$name,'DISPLAY'=>$display)));
			if (array_key_exists('special_off',$info))
				foreach ($info['special_off'] as $name=>$display)
					$options->attach(do_template('SEARCH_FOR_SEARCH_DOMAIN_OPTION',array('_GUID'=>'2223ada7636c85e6879feb9a6f6885d2','CHECKED'=>(get_param_integer('option_'.$id.'_'.$name,0)==1),'NAME'=>'option_'.$id.'_'.$name,'DISPLAY'=>$display)));
			if (method_exists($object,'get_fields'))
			{
				$fields=$object->get_fields();
				foreach ($fields as $field)
				{
					$options->attach(do_template('SEARCH_FOR_SEARCH_DOMAIN_OPTION'.$field['TYPE'],array('_GUID'=>'a223ada7636c85e6879feb9a6f6885d2','NAME'=>'option_'.$field['NAME'],'DISPLAY'=>$field['DISPLAY'],'SPECIAL'=>$field['SPECIAL'],'CHECKED'=>array_key_exists('checked',$field)?$field['CHECKED']:false)));
				}
			}

			$specialisation=do_template('SEARCH_ADVANCED',array('_GUID'=>'fad0c147b8291ba972f105c65715f1ac','AJAX'=>$ajax,'OPTIONS'=>$options,'TREE'=>$tree,'UNDERNEATH'=>!is_null($under)));

		} else // General screen
		{
			$map=array('page'=>'_SELF','type'=>'results');
			$under=get_param('search_under','-1',true);
			if ($under!='-1') $map['search_under']=$under;
			$url=build_url($map,'_SELF',NULL,false,true);

			$search_domains=new ocp_tempcode();
			$_search_domains=array();
			$_hooks=find_all_hooks('modules','search');
			foreach (array_keys($_hooks) as $hook)
			{
				require_code('hooks/modules/search/'.filter_naughty_harsh($hook));
				$object=object_factory('Hook_search_'.filter_naughty_harsh($hook),true);
				if (is_null($object)) continue;
				$info=$object->info();
				if (is_null($info)) continue;

				$is_default_or_advanced=(($info['default']) && ($id=='')) || ($hook==$id);

				$checked=(get_param_integer('search_'.$hook,((is_null($content)) || (get_param_integer('all_defaults',0)==1))?($is_default_or_advanced?1:0):0)==1);
	
				$options=((array_key_exists('special_on',$info)) || (array_key_exists('special_off',$info)) || (array_key_exists('extra_sort_fields',$info)) || (method_exists($object,'get_fields')) || (method_exists($object,'get_tree')) || (method_exists($object,'get_ajax_tree')))?build_url(array('page'=>'_SELF','id'=>$hook),'_SELF',NULL,false,true):new ocp_tempcode();

				$_search_domains[]=array('_GUID'=>'3d3099872184923aec0f49388f52c750','ADVANCED_ONLY'=>(array_key_exists('advanced_only',$info)) && ($info['advanced_only']),'CHECKED'=>$checked,'OPTIONS'=>$options,'LANG'=>$info['lang'],'NAME'=>$hook);
			}
			global $M_SORT_KEY;
			$M_SORT_KEY='LANG';
			usort($_search_domains,'multi_sort');
			foreach ($_search_domains as $sd)
			{
				$search_domains->attach(do_template('SEARCH_FOR_SEARCH_DOMAIN',$sd));
			}

			$specialisation=do_template('SEARCH_DOMAINS',array('_GUID'=>'1fd8718b540ec475988070ee7a444dc1','SEARCH_DOMAINS'=>$search_domains));
		}

		$author=get_param('author','');
		$author_id=($author!='')?$GLOBALS['FORUM_DRIVER']->get_member_from_username($author):NULL;
		$days=get_param_integer('days',60);
		$sort=get_param('sort','relevance');
		$direction=get_param('direction','DESC');
		if (!in_array(strtoupper($direction),array('ASC','DESC'))) log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';
		$only_titles=get_param_integer('only_titles',0)==1;
		$search_under=get_param('search_under','!',true);
		if ($search_under=='') $search_under='!';
		$boolean_operator=get_param('conjunctive_operator','OR');

		$test=db_has_full_text($GLOBALS['SITE_DB']->connection_read);
		$old_mysql=!$test;

		$can_order_by_rating=db_has_subqueries($GLOBALS['SITE_DB']->connection_read);
		
		// Perform search, if we did one
		$out=NULL;
		$results_browser='';
		$num_results=0;
		if (!is_null($content))
		{
			list($out,$results_browser,$num_results)=$this->results($id,$author,$author_id,$days,$sort,$direction,$only_titles,$search_under);

			if (has_zone_access(get_member(),'adminzone'))
			{
				$admin_search_url=build_url(array('page'=>'admin','type'=>'search','search_content'=>$content),'adminzone');
				attach_message(do_lang_tempcode('ALSO_ADMIN_ZONE_SEARCH',escape_html($admin_search_url->evaluate())),'inform');
			}
		}

		return do_template('SEARCH_FORM_SCREEN',array('_GUID'=>'8bb208185740183323a6fe6e89d55de5','SEARCH_TERM'=>is_null($content)?'':$content,'NUM_RESULTS'=>integer_format($num_results),'CAN_ORDER_BY_RATING'=>$can_order_by_rating,'EXTRA_SORT_FIELDS'=>$extra_sort_fields,'USER_LABEL'=>$user_label,'DAYS_LABEL'=>$days_label,'BOOLEAN_SEARCH'=>$this->_is_boolean_search(),'AND'=>$boolean_operator=='AND','ONLY_TITLES'=>$only_titles,'DAYS'=>strval($days),'SORT'=>$sort,'DIRECTION'=>$direction,'CONTENT'=>$content,'RESULTS'=>$out,'RESULTS_BROWSER'=>$results_browser,'OLD_MYSQL'=>$old_mysql,'TITLE'=>$title,'AUTHOR'=>$author,'SPECIALISATION'=>$specialisation,'URL'=>$url));
	}

	/**
	 * Find whether we are doing a boolean search.
	 *
	 * @return boolean		Whether we are
	 */
	function _is_boolean_search()
	{
		$content=get_param('content','',true);

		$boolean_search=get_param_integer('boolean_search',0)==1;
		if (get_value('disable_boolean_search')==='1')
		{
			$boolean_search=false;
			if ((db_has_full_text($GLOBALS['SITE_DB']->connection_read)) && (method_exists($GLOBALS['SITE_DB']->static_ob,'db_has_full_text_boolean')) && ($GLOBALS['SITE_DB']->static_ob->db_has_full_text_boolean()))
			{
				$boolean_search=(preg_match('#["\+\-]#',$content)!=0);
			}
		}
		return $boolean_search;
	}

	/**
	 * The actualiser of a search.
	 *
	 * @param  ID_TEXT		Codename for what's being searched (blank: mixed search)
	 * @param  string			Author name
	 * @param  ?AUTO_LINK	Author ID (NULL: none given)
	 * @param  integer		Days to search
	 * @param  ID_TEXT		Sort key
	 * @param  ID_TEXT		Sort direction
	 * @set    ASC DESC
	 * @param  boolean		Whether to only search titles
	 * @param  string			Comma-separated list of categories to search under
	 * @return array			A triple: The results, results browser, the number of results
	 */
	function results($id,$author,$author_id,$days,$sort,$direction,$only_titles,$search_under)
	{
		$title=get_page_title('RESULTS');

		cache_module_installed_status();

		$cutoff=($days==-1)?NULL:(time()-$days*24*60*60);

		// What we're searching for
		$content=get_param('content',false,true);

		// Search keyword highlighting in any loaded Comcode
		global $SEARCH__CONTENT_BITS;
		$_content_bits=explode(' ',str_replace('"','',preg_replace('#(^|\s)\+#','',preg_replace('#(^|\s)\-#','',$content))));
		$SEARCH__CONTENT_BITS=array();
		require_code('textfiles');
		$too_common_words=explode(chr(10),read_text_file('too_common_words','',true));
		foreach ($_content_bits as $content_bit)
		{
			$content_bit=trim($content_bit);
			if ($content_bit=='') continue;
			if (!in_array(strtolower($content_bit),$too_common_words))
			{
				$SEARCH__CONTENT_BITS[]=$content_bit;
			}
		}

		$start=get_param_integer('start',0);
		$default_max=10;
		if ((ini_get('memory_limit')!='-1') && (ini_get('memory_limit')!='0'))
		{
			if (intval(preg_replace('#M$#','',ini_get('memory_limit')))<20) $default_max=5;
		}
		$max=get_param_integer('max',$default_max);  // Also see get_search_rows

		$save_title=post_param('save_title','');
		if ((!is_guest()) && ($save_title!='') && ($start==0))
		{
			$GLOBALS['SITE_DB']->query_insert('searches_saved',array(
				's_title'=>$save_title,
				's_member_id'=>get_member(),
				's_time'=>time(),
				's_primary'=>$content,
				's_auxillary'=>serialize(array_merge($_POST,$_GET)),
			));
		}

		$boolean_operator=get_param('conjunctive_operator','OR');
		$boolean_search=$this->_is_boolean_search();
		$content_where=build_content_where($content,$boolean_search,$boolean_operator);

		disable_php_memory_limit();

		// Search under all hooks we've asked to search under
		$results=array();
		$_hooks=find_all_hooks('modules','search');
		foreach (array_keys($_hooks) as $hook)
		{
			require_code('hooks/modules/search/'.filter_naughty_harsh($hook));
			$object=object_factory('Hook_search_'.filter_naughty_harsh($hook),true);
			if (is_null($object)) continue;
			$info=$object->info();
			if (is_null($info)) continue;

			$test=get_param_integer('search_'.$hook,0);
			if ((($test==1) || ((get_param_integer('all_defaults',0)==1) && ($info['default'])) || ($id==$hook)) && (($id=='') || ($id==$hook)))
			{
				// Category filter
				if (($search_under!='!') && ($search_under!='-1') && (array_key_exists('category',$info)))
				{
					$cats=explode(',',$search_under);
					$where_clause='(';
					foreach ($cats as $cat)
					{
						if (trim($cat)=='') continue;
	
						if ($where_clause!='(') $where_clause.=' OR ';
						if ($info['integer_category'])
						{
							$where_clause.=((strpos($info['category'],'.')!==false)?'':'r.').$info['category'].'='.strval((integer)$cat);
						} else
						{
							$where_clause.=db_string_equal_to(((strpos($info['category'],'.')!==false)?'':'r.').$info['category'],$cat);
						}
					}
					$where_clause.=')';
				} else $where_clause='';
	
				$only_search_meta=get_param_integer('only_search_meta',0)==1;
				$direction=get_param('direction','ASC');
				if (function_exists('set_time_limit')) @set_time_limit(5); // Prevent errant search hooks (easily written!) taking down a server. Each call given 5 seconds (calling set_time_limit resets the timer).
				$hook_results=$object->run($content,$only_search_meta,$direction,$max,$start,$only_titles,$content_where,$author,$author_id,$cutoff,$sort,$max,$boolean_operator,$where_clause,$search_under,$boolean_search?1:0);
				if (is_null($hook_results)) continue;
				foreach ($hook_results as $i=>$result)
				{
					$result['object']=$object;
					$hook_results[$i]=$result;
				}

				$results=sort_search_results($hook_results,$results,$direction);
			}
		}

		if (function_exists('set_time_limit')) @set_time_limit(15);

		global $EXTRA_HEAD;
		$EXTRA_HEAD->attach('<meta name="robots" content="noindex,nofollow" />'); // XHTMLXHTML

		// Now glue our templates together
		$out=build_search_results_interface($results,$start,$max,$direction,$id=='');
		if ($out->is_empty())
		{
			if (($days!=-1) && ($GLOBALS['TOTAL_RESULTS']==0))
			{
				$ret_maybe=$this->results($id,$author,$author_id,-1,$sort,$direction,$only_titles,$search_under);
				if (!$ret_maybe[0]->is_empty())
				{
					attach_message(do_lang_tempcode('NO_RESULTS_DAYS',escape_html(integer_format($days))),'warn');
					return $ret_maybe;
				}
			}

			return array(new ocp_tempcode(),new ocp_tempcode(),0);
		}

		require_code('templates_results_browser');
		$results_browser=results_browser(do_lang_tempcode('RESULTS'),NULL,$start,'start',$max,'max',$GLOBALS['TOTAL_RESULTS'],NULL,'results',true,true);

		if ($start==0)
		{
			$GLOBALS['SITE_DB']->query_insert('searches_logged',array(
				's_member_id'=>get_member(),
				's_time'=>time(),
				's_primary'=>substr($content,0,255),
				's_auxillary'=>serialize(array_merge($_POST,$_GET)),
				's_num_results'=>count($results),
			));
		}

		return array($out,$results_browser,$GLOBALS['TOTAL_RESULTS']);
	}

}


