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
 * @package		awards
 */

class Block_main_multi_content
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
		$info['parameters']=array('ocselect','param','efficient','filter','filter_b','title','zone','mode','days','lifetime','pinned','no_links','give_context','include_breadcrumbs','max','start','pagination','root','attach_to_url_filter','render_if_empty','guid');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array		Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(array_key_exists(\'guid\',$map)?$map[\'guid\']:\'\',array_key_exists(\'efficient\',$map) && $map[\'efficient\']==\'1\')?array():$GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true),array_key_exists(\'render_if_empty\',$map)?$map[\'render_if_empty\']:\'0\',((array_key_exists(\'attach_to_url_filter\',$map)?$map[\'attach_to_url_filter\']:\'0\')==\'1\'),get_param_integer($block_id.\'_max\',array_key_exists(\'max\',$map)?intval($map[\'max\']):30),get_param_integer($block_id.\'_start\',array_key_exists(\'start\',$map)?intval($map[\'start\']):0),((array_key_exists(\'pagination\',$map)?$map[\'pagination\']:\'0\')==\'1\'),((array_key_exists(\'root\',$map)) && ($map[\'root\']!=\'\'))?intval($map[\'root\']):get_param_integer(\'keep_\'.(array_key_exists(\'param\',$map)?$map[\'param\']:\'download\').\'_root\',NULL),(array_key_exists(\'give_context\',$map)?$map[\'give_context\']:\'0\')==\'1\',(array_key_exists(\'include_breadcrumbs\',$map)?$map[\'include_breadcrumbs\']:\'0\')==\'1\',array_key_exists(\'ocselect\',$map)?$map[\'ocselect\']:\'\',array_key_exists(\'no_links\',$map)?$map[\'no_links\']:0,((array_key_exists(\'days\',$map)) && ($map[\'days\']!=\'\'))?intval($map[\'days\']):NULL,((array_key_exists(\'lifetime\',$map)) && ($map[\'lifetime\']!=\'\'))?intval($map[\'lifetime\']):NULL,((array_key_exists(\'pinned\',$map)) && ($map[\'pinned\']!=\'\'))?explode(\',\',$map[\'pinned\']):array(),array_key_exists(\'max\',$map)?intval($map[\'max\']):10,array_key_exists(\'title\',$map)?$map[\'title\']:\'\',array_key_exists(\'param\',$map)?$map[\'param\']:\'download\',array_key_exists(\'filter\',$map)?$map[\'filter\']:\'\',array_key_exists(\'filter_b\',$map)?$map[\'filter_b\']:\'\',array_key_exists(\'zone\',$map)?$map[\'zone\']:\'_SEARCH\',array_key_exists(\'mode\',$map)?$map[\'mode\']:\'recent\')';
		$info['ttl']=30;
		return $info;
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		$GLOBALS['SITE_DB']->create_table('feature_lifetime_monitor',array(
			'content_id'=>'*ID_TEXT',
			'block_cache_id'=>'*ID_TEXT',
			'run_period'=>'INTEGER',
			'running_now'=>'BINARY',
			'last_update'=>'TIME',
		));
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('feature_lifetime_monitor');
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		require_lang('awards');
		require_code('awards');

		if (array_key_exists('param',$map))
		{
			$type_id=$map['param'];
		} else
		{
			if (addon_installed('downloads'))
			{
				$type_id='download';
			} else
			{
				$hooks=find_all_hooks('systems','awards');
				$type_id=key($hooks);
			}
		}

		$block_id=get_block_id($map);

		$max=get_param_integer($block_id.'_max',array_key_exists('max',$map)?intval($map['max']):30);
		$start=get_param_integer($block_id.'_start',array_key_exists('start',$map)?intval($map['start']):0);
		$do_pagination=((array_key_exists('pagination',$map)?$map['pagination']:'0')=='1');
		$attach_to_url_filter=((array_key_exists('attach_to_url_filter',$map)?$map['attach_to_url_filter']:'0')=='1');
		$root=((array_key_exists('root',$map)) && ($map['root']!=''))?intval($map['root']):get_param_integer('keep_'.$type_id.'_root',NULL);

		$guid=array_key_exists('guid',$map)?$map['guid']:'';
		$mode=array_key_exists('mode',$map)?$map['mode']:'recent'; // recent|top|views|random|all or some manually typed sort order
		$filter=array_key_exists('filter',$map)?$map['filter']:'';
		$filter_b=array_key_exists('filter_b',$map)?$map['filter_b']:'';
		$ocselect=array_key_exists('ocselect',$map)?$map['ocselect']:'';
		$zone=array_key_exists('zone',$map)?$map['zone']:'_SEARCH';
		$efficient=(array_key_exists('efficient',$map)?$map['efficient']:'1')=='1';
		$title=array_key_exists('title',$map)?$map['title']:'';
		$days=((array_key_exists('days',$map)) && ($map['days']!=''))?intval($map['days']):NULL;
		$lifetime=((array_key_exists('lifetime',$map)) && ($map['lifetime']!=''))?intval($map['lifetime']):NULL;
		$pinned=((array_key_exists('pinned',$map)) && ($map['pinned']!=''))?explode(',',$map['pinned']):array();
		$give_context=(array_key_exists('give_context',$map)?$map['give_context']:'0')=='1';
		$include_breadcrumbs=(array_key_exists('include_breadcrumbs',$map)?$map['include_breadcrumbs']:'0')=='1';

		if ((!file_exists(get_file_base().'/sources/hooks/systems/awards/'.filter_naughty_harsh($type_id).'.php')) && (!file_exists(get_file_base().'/sources_custom/hooks/systems/awards/'.filter_naughty_harsh($type_id).'.php')))
			return paragraph(do_lang_tempcode('NO_SUCH_CONTENT_TYPE',$type_id));

		require_code('hooks/systems/awards/'.filter_naughty_harsh($type_id),true);
		$object=object_factory('Hook_awards_'.$type_id);
		$info=$object->info($zone,($filter_b=='')?NULL:$filter_b);
		if (is_null($info)) warn_exit(do_lang_tempcode('IMPOSSIBLE_TYPE_USED'));
		require_code('content');
		$cma_hook=convert_ocportal_type_codes('award_hook',$type_id,'cma_hook');
		$cma_object=object_factory('Hook_content_meta_aware_'.$cma_hook);
		$info+=$cma_object->info();

		$submit_url=$info['add_url'];
		if (is_object($submit_url)) $submit_url=$submit_url->evaluate();
		if (!has_actual_page_access(NULL,$info['cms_page'],NULL,NULL)) $submit_url='';

		// Get entries

		if (is_array($info['category_field']))
		{
			$category_field_access=$info['category_field'][0];
			$category_field_filter=$info['category_field'][1];
		} else
		{
			$category_field_access=$info['category_field'];
			$category_field_filter=$info['category_field'];
		}
		if (array_key_exists('category_type',$info))
		{
			if (is_array($info['category_type']))
			{
				$category_type_access=$info['category_type'][0];
				$category_type_filter=$info['category_type'][1];
			} else
			{
				$category_type_access=$info['category_type'];
				$category_type_filter=$info['category_type'];
			}
		} else
		{
			$category_type_access=mixed();
			$category_type_filter=mixed();
		}

		$where='';
		$query='FROM '.get_table_prefix().$info['table'].' r';
		if ((!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) && (!$efficient))
		{
			$_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups(get_member(),false,true);
			$groups='';
			foreach ($_groups as $group)
			{
				if ($groups!='') $groups.=' OR ';
				$groups.='a.group_id='.strval((integer)$group);
			}

			if (!is_null($category_field_access))
			{
				if ($category_type_access==='!')
				{
					$query.=' LEFT JOIN '.get_table_prefix().'group_page_access a ON (r.'.$category_field_filter.'=a.page_name AND r.'.$category_field_access.'=a.zone_name AND ('.$groups.'))';
					$query.=' LEFT JOIN '.get_table_prefix().'group_zone_access a2 ON (r.'.$category_field_access.'=a2.zone_name)';
				} else
				{
					$query.=' LEFT JOIN '.get_table_prefix().'group_category_access a ON ('.db_string_equal_to('a.module_the_name',$category_type_access).' AND r.'.$category_field_access.'=a.category_name)';
					$query.=' LEFT JOIN '.get_table_prefix().'member_category_access ma ON ('.db_string_equal_to('ma.module_the_name',$category_type_access).' AND r.'.$category_field_access.'=ma.category_name)';
				}
			}
			if ((!is_null($category_field_filter)) && ($category_field_filter!=$category_field_access) && ($info['category_type']!=='!'))
			{
				$query.=' LEFT JOIN '.get_table_prefix().'group_category_access a2 ON ('.db_string_equal_to('a.module_the_name',$category_type_filter).' AND r.'.$category_field_filter.'=a2.category_name)';
			}
			if (!is_null($category_field_access))
			{
				if ($where!='') $where.=' AND ';
				if ($info['category_type']==='!')
				{
					$where.='(a.group_id IS NULL) AND ('.str_replace('a.','a2.',$groups).') AND (a2.group_id IS NOT NULL)';
				} else
				{
					$where.='(('.$groups.') AND (a.group_id IS NOT NULL) OR (ma.active_until>'.strval(time()).' AND ma.member_id='.strval(get_member()).'))';
				}
			}
			if ((!is_null($category_field_filter)) && ($category_field_filter!=$category_field_access) && ($info['category_type']!=='!'))
			{
				if ($where!='') $where.=' AND ';
				$where.='('.str_replace('a.group_id','a2.group_id',$groups).') AND (a2.group_id IS NOT NULL)';
			}
			if (array_key_exists('where',$info))
			{
				if ($where!='') $where.=' AND ';
				$where.=$info['where'];
			}
		}

		if ((array_key_exists('validated_field',$info)) && ($info['validated_field']!='') && (has_privilege(get_member(),'see_unvalidated')))
		{
			if ($where!='') $where.=' AND ';
			$where.='r.'.$info['validated_field'].'=1';
		}

		$x1='';
		$x2='';
		if (($filter!='') && (!is_null($category_field_filter)))
			$x1=$this->build_filter($filter,$info,'r.'.$category_field_filter);
		if (($filter_b!='') && (!is_null($category_field_access)))
			$x2=$this->build_filter($filter_b,$info,'r.'.$category_field_access);

		if (!is_null($days))
		{
			if ($where!='') $where.=' AND ';
			$where.=$info['date_field'].'>='.strval(time()-60*60*24*$days);
		}

		if (is_array($info['id_field'])) $lifetime=NULL; // Cannot join on this

		if (!is_null($lifetime))
		{
			$block_cache_id=md5(serialize($map));
			$query.=' LEFT JOIN '.$info['connection']->get_table_prefix().'feature_lifetime_monitor m ON m.content_id=r.'.$info['id_field'].' AND '.db_string_equal_to('m.block_cache_id',$block_cache_id);
			if ($where!='') $where.=' AND ';
			$where.='(m.run_period IS NULL OR m.run_period<'.strval($lifetime*60*60*24).')';
		}

		if (array_key_exists('extra_select_sql',$info))
		{
			$extra_select_sql=$info['extra_select_sql'];
		} else $extra_select_sql='';
		if (array_key_exists('extra_table_sql',$info))
		{
			$query.=$info['extra_table_sql'];
		}
		if (array_key_exists('extra_where_sql',$info))
		{
			if ($where!='') $where.=' AND ';
			$where.=$info['extra_where_sql'];
		}

		// ocSelect support
		if ($ocselect!='')
		{
			// Convert the filters to SQL
			require_code('ocselect');
			list($extra_select,$extra_join,$extra_where)=ocselect_to_sql($info['connection'],parse_ocselect($ocselect),$cma_hook,'');
			$extra_select_sql.=implode('',$extra_select);
			$query.=implode('',$extra_join);
			$where.=$extra_where;
		}

		// Need to pull in title?
		if (($mode=='all') || (strpos($mode,'t.text_original')!==false))
		{
			if ((array_key_exists('title_field',$info)) && (strpos($info['title_field'],':')===false))
			{
				$query.=' LEFT JOIN '.get_table_prefix().'translate t ON t.id=r.'.$info['title_field'].' AND '.db_string_equal_to('t.language',user_lang());
			}
		}

		// Put query together
		if ($where.$x1.$x2!='')
		{
			if ($where=='') $where='1=1';
			$query.=' WHERE '.$where;
			if ($x1!='') $query.=' AND ('.$x1.')';
			if ($x2!='') $query.=' AND ('.$x2.')';
		}

		if (($mode=='top') && (array_key_exists('feedback_type',$info)) && (is_null($info['feedback_type']))) $mode='all';

		// Find what kind of query to run and run it
		switch ($mode)
		{
			case 'random':
				$cnt=$info['connection']->query_value_if_there('SELECT COUNT(*) as cnt '.$query);
				$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query,$max,mt_rand(0,max(0,$cnt-$max)));
				break;
			case 'recent':
				if ((array_key_exists('date_field',$info)) && (!is_null($info['date_field'])))
				{
					$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY r.'.$info['date_field'].' DESC',$max,$start);
					break;
				}
			case 'views':
				if ((array_key_exists('views_field',$info)) && (!is_null($info['views_field'])))
				{
					$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY r.'.$info['views_field'].' DESC',$max,$start);
					break;
				}
			case 'top':
				if ((array_key_exists('feedback_type',$info)) && (!is_null($info['feedback_type'])))
				{
					$select_rating=',(SELECT AVG(rating) FROM '.get_table_prefix().'rating WHERE '.db_string_equal_to('rating_for_type',$info['feedback_type']).' AND rating_for_id='.$info['id_field'].') AS compound_rating';
					$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.$select_rating.' '.$query,$max,$start,'ORDER BY compound_rating DESC');
					break;
				}
			case 'all':
				if ((array_key_exists('title_field',$info)) && (strpos($info['title_field'],':')===false))
				{
					if ($info['title_field_dereference'])
					{
						$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY t.text_original ASC',$max,$start);
					} else
					{
						$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY r.'.$info['title_field'].' ASC',$max,$start);
					}
				} else
				{
					$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY r.'.$info['id_field'].' ASC',$max,$start);
				}
				break;
			default: // Some manual order
				$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY '.$mode,$max,$start);
				break;
		}

		$max_rows=$info['connection']->query_value_if_there('SELECT COUNT(*)'.$extra_select_sql.' '.$query);

		$pinned_order=array();

		require_code('content');

		// Add in requested pinned awards
		foreach ($pinned as $i=>$p)
		{
			$awarded_rows=$GLOBALS['SITE_DB']->query_select('award_archive',array('*'),array('a_type_id'=>intval($p)),'ORDER BY date_and_time DESC',1);
			if (!array_key_exists(0,$awarded_rows)) continue;
			$awarded_row=$awarded_rows[0];

			$award_content_row=content_get_row($awarded_row['content_id'],$info);

			if ((!is_null($award_content_row)) && ((!isset($info['validated_field'])) || ($award_content_row[$info['validated_field']]!=0)))
			{
				$pinned_order[$i]=$award_content_row;
			}
		}

		if (count($pinned_order)>0) // Re-sort with pinned awards if appropriate
		{
			if (count($rows)>0)
			{
				//Bit inefficient I know, but it'll mean less rewriting of the later code -- Paul
				$old_rows=$rows;
				$rows=array();
				$total_count=count($old_rows)+count($pinned_order);
				$used_ids=array();

				/*
				 * NOTE: If anything is pinned as the first element, it can't just be passed on directly because
				 * next() will miss the first element of the $old rows array. It is necessary to assess the first
				 * element of the array so if a pinned element must be first, tacking it on to the start of the
				 * array then using the array's first element under either circumstance is the simplest answer.
				 */
				if (array_key_exists(0,$pinned_order))
					array_unshift($old_rows,$pinned_order[0]);

				reset($old_rows); //Why is there no 'get number _then_ goto next element' function?
				$temp_row=current($old_rows);
				$rows[]=$temp_row;
				$used_ids[]=$temp_row[$info['id_field']];

				$n_count=1; //If duplicates exist, position in the new array needs to be maintained.
				//Carry on as it should be
				for ($t_count=1; $t_count<$total_count; $t_count++)
				{
					if (array_key_exists($n_count,$pinned_order))
					{
						if (!in_array($pinned_order[$n_count][$info['id_field']],$used_ids))
						{
							$rows[]=$pinned_order[$n_count];
							$used_ids[]=$pinned_order[$n_count][$info['id_field']];
							$n_count++;
						}
						else
						{
							$temp_row=next($old_rows);
							if (!in_array($temp_row[$info['id_field']],$used_ids))
							{
								$rows[]=$temp_row;
								$used_ids[]=$temp_row[$info['id_field']];
								$n_count++;
							}
						}
					}
					else
					{
						$temp_row=next($old_rows);
						if (!in_array($temp_row[$info['id_field']],$used_ids))
						{
							$rows[]=$temp_row;
							$used_ids[]=$temp_row[$info['id_field']];
							$n_count++;
						}
					}
				}
			}
			else
			{
				switch ($mode)
				{
					case 'recent':
						if (array_key_exists('date_field',$info))
						{
							sort_maps_by($pinned_order,$info['date_field']);
							$rows=array_reverse($pinned_order);
						}
						break;
					case 'views':
						if (array_key_exists('views_field',$info))
						{
							sort_maps_by($pinned_order,$info['views_field']);
							$rows=array_reverse($pinned_order);
						}
						break;
				}
			}
		}

		// Sort out run periods
		if (!is_null($lifetime))
		{
			$lifetime_monitor=list_to_map('content_id',$GLOBALS['SITE_DB']->query_select('feature_lifetime_monitor',array('content_id','run_period','last_update'),array('block_cache_id'=>$block_cache_id,'running_now'=>1)));
		}

		// Move towards render...

		$archive_url=$info['archive_url'];
		$view_url=array_key_exists('view_url',$info)?$info['view_url']:new ocp_tempcode();

		$done_already=array(); // We need to keep track, in case those pulled up via awards would also come up naturally

		$rendered_content=array();
		$content_data=array();
		foreach ($rows as $row)
		{
			if (count($done_already)==$max) break;

			// Get content ID
			if (is_array($info['id_field']))
			{
				$content_id='';
				foreach ($info['id_field'] as $f)
				{
					if ($content_id!='') $content_id.=':';
					$x=$row[$f];
					if (!is_string($x)) $x=strval($x);
					$content_id.=$x;
				}
			} else
			{
				$content_id=$row[$info['id_field']];
				if (!is_string($content_id)) $content_id=strval($content_id);
			}

			if (array_key_exists($content_id,$done_already)) continue;

			$done_already[$content_id]=1;

			// Lifetime managing
			if (!is_null($lifetime))
			{
				if (!array_key_exists($content_id,$lifetime_monitor))
				{
					// Test to see if it is actually there in the past - we only loaded the "running now" ones for performance reasons. Any new ones coming will trigger extra queries to see if they've been used before, as a tradeoff to loading potentially 10's of thousands of rows.
					$lifetime_monitor+=list_to_map('content_id',$GLOBALS['SITE_DB']->query_select('feature_lifetime_monitor',array('content_id','run_period','last_update'),array('block_cache_id'=>$block_cache_id,'content_id'=>$content_id)));
				}

				if (array_key_exists($content_id,$lifetime_monitor))
				{
					$GLOBALS['SITE_DB']->query_update('feature_lifetime_monitor',array(
						'run_period'=>$lifetime_monitor[$content_id]['run_period']+(time()-$lifetime_monitor[$content_id]['last_update']),
						'running_now'=>1,
						'last_update'=>time(),
					),array('content_id'=>$content_id,'block_cache_id'=>$block_cache_id));
					unset($lifetime_monitor[$content_id]);
				} else
				{
					$GLOBALS['SITE_DB']->query_insert('feature_lifetime_monitor',array(
						'content_id'=>$content_id,
						'block_cache_id'=>$block_cache_id,
						'run_period'=>0,
						'running_now'=>1,
						'last_update'=>time(),
					));
				}
			}

			// Render
			$rendered_content[]=$object->run($row,$zone,$give_context,$include_breadcrumbs,$root,$attach_to_url_filter,$guid);

			// Try and get a better submit url
			$submit_url=str_replace('%21',$content_id,$submit_url);

			$content_data[]=array('URL'=>str_replace('%21',$content_id,$view_url->evaluate()));
		}

		// Sort out run periods of stuff gone
		if (!is_null($lifetime))
		{
			foreach (array_keys($lifetime_monitor) as $content_id) // Any remaining have not been pulled up
			{
				if (is_integer($content_id)) $content_id=strval($content_id);

				$GLOBALS['SITE_DB']->query_update('feature_lifetime_monitor',array(
					'run_period'=>$lifetime_monitor[$content_id]['run_period']+(time()-$lifetime_monitor[$content_id]['last_update']),
					'running_now'=>0,
					'last_update'=>time(),
				),array('content_id'=>$content_id,'block_cache_id'=>$block_cache_id));
			}
		}

		if ((array_key_exists('no_links',$map)) && ($map['no_links']=='1'))
		{
			$submit_url=new ocp_tempcode();
			$archive_url=new ocp_tempcode();
		}

		// Empty? Bomb out somehow
		if (count($rendered_content)==0)
		{
			if ((isset($map['render_if_empty'])) && ($map['render_if_empty']=='0'))
			{
				return new ocp_tempcode();
			}
		}

		// Pagination
		$pagination=mixed();
		if ($do_pagination)
		{
			require_code('templates_pagination');
			$pagination=pagination($info['title'],$start,$block_id.'_start',$max,$block_id.'_max',$max_rows);
		}

		return do_template('BLOCK_MAIN_MULTI_CONTENT',array(
			'_GUID'=>($guid!='')?$guid:'9035934bc9b25f57eb8d23bf100b5796',
			'BLOCK_PARAMS'=>block_params_arr_to_str($map),
			'TYPE'=>$info['title'],
			'TITLE'=>$title,
			'CONTENT'=>$rendered_content,
			'CONTENT_DATA'=>$content_data,
			'SUBMIT_URL'=>$submit_url,
			'ARCHIVE_URL'=>$archive_url,
			'PAGINATION'=>$pagination,
		));
	}

	/**
	 * Make a filter SQL fragment.
	 *
	 * @param  string		The filter string.
	 * @param  array		Map of details of our content type.
	 * @param  string		The field name of the category to filter against.
	 * @return string		SQL fragment.
	 */
	function build_filter($filter,$info,$category_field_filter)
	{
		$parent_spec__table_name=array_key_exists('parent_spec__table_name',$info)?$info['parent_spec__table_name']:$info['table'];
		$parent_field_name=array_key_exists('parent_field_name',$info)?$info['parent_field_name']:NULL;
		if (is_null($parent_field_name)) $parent_spec__table_name=NULL;
		$parent_spec__parent_name=array_key_exists('parent_spec__parent_name',$info)?$info['parent_spec__parent_name']:NULL;
		$parent_spec__field_name=array_key_exists('parent_spec__field_name',$info)?$info['parent_spec__field_name']:NULL;
		$id_is_string=((array_key_exists('id_is_string',$info)) && ($info['id_is_string']));
		$category_is_string=((array_key_exists('category_is_string',$info)) && ($info['category_is_string']));
		require_code('ocfiltering');
		return ocfilter_to_sqlfragment($filter,$category_field_filter,$parent_spec__table_name,$parent_spec__parent_name,$parent_field_name,$parent_spec__field_name,!$id_is_string,!$category_is_string);
	}
}


