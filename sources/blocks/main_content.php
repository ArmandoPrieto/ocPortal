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
 * @package		core
 */

class Block_main_content
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
		$info['parameters']=array('param','efficient','id','filter','filter_b','title','zone','no_links','give_context','include_breadcrumbs','render_if_empty','guid','as_guest');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='((addon_installed(\'content_privacy\')) && (!(array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false)))?NULL:array(array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false,array_key_exists(\'render_if_empty\',$map)?$map[\'render_if_empty\']:\'1\',array_key_exists(\'guid\',$map)?$map[\'guid\']:\'\',(array_key_exists(\'give_context\',$map)?$map[\'give_context\']:\'0\')==\'1\',(array_key_exists(\'include_breadcrumbs\',$map)?$map[\'include_breadcrumbs\']:\'0\')==\'1\',array_key_exists(\'no_links\',$map)?$map[\'no_links\']:0,array_key_exists(\'title\',$map)?$map[\'title\']:\'\',$GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true),array_key_exists(\'param\',$map)?$map[\'param\']:\'download\',array_key_exists(\'id\',$map)?$map[\'id\']:\'\',array_key_exists(\'efficient\',$map)?$map[\'efficient\']:\'_SEARCH\',array_key_exists(\'filter\',$map)?$map[\'filter\']:\'\',array_key_exists(\'filter_b\',$map)?$map[\'filter_b\']:\'\',array_key_exists(\'zone\',$map)?$map[\'zone\']:\'_SEARCH\')';
		$info['ttl']=(get_value('no_block_timeout')==='1')?60*60*24*365*5/*5 year timeout*/:60*24; // Intentionally, do randomisation acts as 'of the day'
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		$guid=isset($map['guid'])?$map['guid']:'';
		if (isset($map['param']))
		{
			$content_type=$map['param'];
		} else
		{
			if (addon_installed('downloads'))
			{
				$content_type='download';
			} else
			{
				$hooks=find_all_hooks('systems','content_meta_aware');
				$content_type=key($hooks);
			}
		}
		$content_id=isset($map['id'])?$map['id']:NULL;
		if ($content_id==='') return new ocp_tempcode(); // Might have happened due to some bad chaining in a template
		$randomise=($content_id===NULL);
		$zone=isset($map['zone'])?$map['zone']:'_SEARCH';
		$efficient=(isset($map['efficient'])?$map['efficient']:'1')=='1';
		$filter=isset($map['filter'])?$map['filter']:'';
		$filter_b=isset($map['filter_b'])?$map['filter_b']:'';
		$title=isset($map['title'])?$map['title']:NULL;
		$give_context=(isset($map['give_context'])?$map['give_context']:'0')=='1';
		$include_breadcrumbs=(isset($map['include_breadcrumbs'])?$map['include_breadcrumbs']:'0')=='1';

		if ((!file_exists(get_file_base().'/sources/hooks/systems/content_meta_aware/'.filter_naughty_harsh($content_type,true).'.php')) && (!file_exists(get_file_base().'/sources_custom/hooks/systems/content_meta_aware/'.filter_naughty_harsh($content_type,true).'.php')))
			return paragraph(do_lang_tempcode('NO_SUCH_CONTENT_TYPE',$content_type),'','red_alert');

		require_code('content');
		$object=get_content_object($content_type);
		$info=$object->info();
		if ($info===NULL) warn_exit(do_lang_tempcode('IMPOSSIBLE_TYPE_USED'));
		if ($title===NULL) $title=do_lang('RANDOM_CONTENT',do_lang($info['content_type_label']));
		if (((!array_key_exists('id_field_numeric',$info)) || ($info['id_field_numeric'])) && ($content_id!==NULL) && (!is_numeric($content_id)))
		{
			list(,$resource_page,$resource_type)=explode(':',$info['view_page_link_pattern']);
			$content_id=$info['connection']->query_select_value_if_there('url_id_monikers','m_resource_id',array('m_resource_page'=>$resource_page,'m_resource_type'=>$resource_type,'m_moniker'=>$content_id));
			if ($content_id===NULL) return new ocp_tempcode();
		}

		global $TABLE_LANG_FIELDS_CACHE;
		$lang_fields=isset($TABLE_LANG_FIELDS_CACHE[$info['table']])?$TABLE_LANG_FIELDS_CACHE[$info['table']]:array();
		foreach ($lang_fields as $lang_field=>$lang_field_type)
		{
			unset($lang_fields[$lang_field]);
			$lang_fields['r.'.$lang_field]=$lang_field_type;
		}

		$submit_url=$info['add_url'];
		if ($submit_url!==NULL)
		{
			list($submit_url_zone,$submit_url_map,$submit_url_hash)=page_link_decode($submit_url);
			$submit_url=static_evaluate_tempcode(build_url($submit_url_map,$submit_url_zone,NULL,false,false,false,$submit_url_hash));
		} else $submit_url='';
		if (!has_actual_page_access(NULL,$info['cms_page'],NULL,NULL)) $submit_url='';

		// Randomisation mode
		if ($randomise)
		{
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

			$where='1=1';
			$query='FROM '.get_table_prefix().$info['table'].' r';
			if ((!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) && (!$efficient))
			{
				if (addon_installed('content_privacy'))
				{
					require_code('content_privacy');
					$as_guest=array_key_exists('as_guest',$map)?($map['as_guest']=='1'):false;
					$viewing_member_id=$as_guest?$GLOBALS['FORUM_DRIVER']->get_guest_id():mixed();
					list($privacy_join,$privacy_where)=get_privacy_where_clause($content_type,'r',$viewing_member_id);
					$query.=$privacy_join;
					$where.=$privacy_where;
				}

				$_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups(get_member(),false,true);
				$groups='';
				foreach ($_groups as $group)
				{
					if ($groups!='') $groups.=' OR ';
					$groups.='a.group_id='.strval($group);
				}

				if ($category_field_access!==NULL)
				{
					if ($category_type_access==='<zone>')
					{
						$query.=' LEFT JOIN '.get_table_prefix().'group_zone_access a ON (r.'.$category_field_access.'=a.zone_name)';
						$query.=' LEFT JOIN '.get_table_prefix().'group_zone_access ma ON (r.'.$category_field_access.'=ma.zone_name)';
					}
					elseif ($category_type_access==='<page>')
					{
						$query.=' LEFT JOIN '.get_table_prefix().'group_page_access a ON (r.'.$category_field_filter.'=a.page_name AND r.'.$category_field_access.'=a.zone_name AND ('.$groups.'))';
						$query.=' LEFT JOIN '.get_table_prefix().'group_zone_access a2 ON (r.'.$category_field_access.'=a2.zone_name)';
						$query.=' LEFT JOIN '.get_table_prefix().'group_zone_access ma2 ON (r.'.$category_field_access.'=ma2.zone_name)';
					} else
					{
						$query.=' LEFT JOIN '.get_table_prefix().'group_category_access a ON ('.db_string_equal_to('a.module_the_name',$category_type_access).' AND r.'.$category_field_access.'=a.category_name)';
						$query.=' LEFT JOIN '.get_table_prefix().'member_category_access ma ON ('.db_string_equal_to('ma.module_the_name',$category_type_access).' AND r.'.$category_field_access.'=ma.category_name)';
					}
				}
				if (($category_field_filter!==NULL) && ($category_field_filter!=$category_field_access) && ($info['category_type']!=='<page>') && ($info['category_type']!=='<zone>'))
				{
					$query.=' LEFT JOIN '.get_table_prefix().'group_category_access a2 ON ('.db_string_equal_to('a.module_the_name',$category_type_filter).' AND r.'.$category_field_filter.'=a2.category_name)';
					$query.=' LEFT JOIN '.get_table_prefix().'member_category_access ma2 ON ('.db_string_equal_to('ma2.module_the_name',$category_type_access).' AND r.'.$category_field_access.'=ma2.category_name)';
				}
				if ($category_field_access!==NULL)
				{
					$where.=' AND ';
					if ($info['category_type']==='<page>')
					{
						$where.='(a.group_id IS NULL) AND ('.str_replace('a.','a2.',$groups).') AND (a2.group_id IS NOT NULL)';
						// NB: too complex to handle member-specific page permissions in this
					} else
					{
						$where.='(('.$groups.') AND (a.group_id IS NOT NULL) OR ((ma.active_until IS NULL OR ma.active_until>'.strval(time()).') AND ma.member_id='.strval(get_member()).'))';
					}
				}
				if (($category_field_filter!==NULL) && ($category_field_filter!=$category_field_access) && ($info['category_type']!=='<page>'))
				{
					$where.=' AND ';
					$where.='(('.str_replace('a.group_id','a2.group_id',$groups).') AND (a2.group_id IS NOT NULL) OR ((ma2.active_until IS NULL OR ma2.active_until>'.strval(time()).') AND ma2.member_id='.strval(get_member()).'))';
				}
				if (array_key_exists('where',$info))
				{
					$where.=' AND ';
					$where.=$info['where'];
				}
			}

			if ((array_key_exists('validated_field',$info)) && ($info['validated_field']!=''))
			{
				$where.=' AND ';
				$where.=$info['validated_field'].'=1';
			}

			$x1='';
			$x2='';
			if (($filter!='') && ($category_field_access!==NULL))
			{
				$x1=$this->build_filter($filter,$info,$category_field_access,is_array($info['category_is_string'])?$info['category_is_string'][0]:$info['category_is_string']);
				$parent_spec__table_name=array_key_exists('parent_spec__table_name',$info)?$info['parent_spec__table_name']:NULL;
				if (($parent_spec__table_name!==NULL) && ($parent_spec__table_name!=$info['table']))
				{
					$query.=' LEFT JOIN '.$info['connection']->get_table_prefix().$parent_spec__table_name.' parent ON parent.'.$info['parent_spec__field_name'].'=r.'.$info['id_field'];
				}
			}
			if (($filter_b!='') && ($category_field_filter!==NULL))
			{
				$x2=$this->build_filter($filter_b,$info,$category_field_filter,is_array($info['category_is_string'])?$info['category_is_string'][1]:$info['category_is_string']);
			}

			if ($where.$x1.$x2!='')
			{
				if ($where=='') $where='1=1';
				$query.=' WHERE '.$where;
				if ($x1!='') $query.=' AND ('.$x1.')';
				if ($x2!='') $query.=' AND ('.$x2.')';
			}

			$rows=$info['connection']->query('SELECT COUNT(*) as cnt '.$query);

			$cnt=$rows[0]['cnt'];
			if ($cnt==0)
			{
				return do_template('BLOCK_NO_ENTRIES',array(
					'_GUID'=>($guid!='')?$guid:'13f060922a5ab6c370f218b2ecc6fe9c',
					'HIGH'=>true,
					'TITLE'=>$title,
					'MESSAGE'=>do_lang_tempcode('NO_ENTRIES'),
					'ADD_NAME'=>do_lang_tempcode('ADD'),
					'SUBMIT_URL'=>str_replace('=%21','__ignore=1',$submit_url),
				));
			}

			$rows=$info['connection']->query('SELECT * '.$query,1,mt_rand(0,$cnt-1),false,false,$lang_fields);
			$award_content_row=$rows[0];

			// Get content ID
			$content_id=extract_content_str_id_from_data($award_content_row,$info);
		}

		// Select mode
		else
		{
			if ($content_type=='comcode_page') // FUDGEFUDGE
			{
				// Try and force a parse of the page, so it's in the system
				$bits=explode(':',$content_id);
				$result=request_page(array_key_exists(1,$bits)?$bits[1]:get_comcode_zone($bits[0]),false,$bits[0],'comcode_custom',true);
				if ($result===NULL || $result->is_empty()) return new ocp_tempcode();
			}

			$wherea=get_content_where_for_str_id($content_id,$info,'r');

			$rows=$info['connection']->query_select($info['table'].' r',array('r.*'),$wherea,'',1,NULL,false,$lang_fields);
			if (!array_key_exists(0,$rows))
			{
				if ((isset($map['render_if_empty'])) && ($map['render_if_empty']=='0')) return new ocp_tempcode();

				return do_template('BLOCK_NO_ENTRIES',array(
					'_GUID'=>($guid!='')?$guid:'12d8cdc62cd78480b83c8daaaa68b686',
					'HIGH'=>true,
					'TITLE'=>$title,
					'MESSAGE'=>do_lang_tempcode('MISSING_RESOURCE'),
					'ADD_NAME'=>do_lang_tempcode('ADD'),
					'SUBMIT_URL'=>str_replace('=%21','__ignore=1',$submit_url),
				));
			}
			$award_content_row=$rows[0];
		}

		if ($award_content_row===NULL)
		{
			warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		}

		$submit_url=str_replace('%21',$content_id,$submit_url);

		if ($info['archive_url']!==NULL)
		{
			list($archive_url_zone,$archive_url_map,$archive_url_hash)=page_link_decode($info['archive_url']);
			$archive_url=build_url($archive_url_map,$archive_url_zone,NULL,false,false,false,$archive_url_hash);
		} else $archive_url=new ocp_tempcode();

		$rendered_content=$object->run($award_content_row,$zone,$give_context,$include_breadcrumbs,NULL,false,$guid);

		if ((isset($map['no_links'])) && ($map['no_links']=='1'))
		{
			$submit_url='';
			$archive_url=new ocp_tempcode();
		}

		$raw_date=($info['date_field']=='')?mixed():$award_content_row[$info['date_field']];
		return do_template('BLOCK_MAIN_CONTENT',array(
			'_GUID'=>($guid!='')?$guid:'fce1eace6008d650afc0283a7be9ec30',
			'TYPE'=>do_lang_tempcode($info['content_type_label']),
			'TITLE'=>$title,
			'RAW_AWARD_DATE'=>($raw_date===NULL)?'':strval($raw_date),
			'AWARD_DATE'=>($raw_date===NULL)?'':get_timezoned_date($raw_date),
			'CONTENT'=>$rendered_content,
			'SUBMIT_URL'=>$submit_url,
			'ARCHIVE_URL'=>$archive_url,
		));
	}

	/**
	 * Make a filter SQL fragment.
	 *
	 * @param  string		The filter string.
	 * @param  array		Map of details of our content type.
	 * @param  string		The field name of the category to filter against.
	 * @param  boolean	Whether the category is a string.
	 * @return string		SQL fragment.
	 */
	function build_filter($filter,$info,$category_field_filter,$category_is_string)
	{
		$parent_spec__table_name=array_key_exists('parent_spec__table_name',$info)?$info['parent_spec__table_name']:$info['table'];
		$parent_field_name=array_key_exists('parent_category_field',$info)?$info['parent_category_field']:NULL;
		$parent_spec__parent_name=array_key_exists('parent_spec__parent_name',$info)?$info['parent_spec__parent_name']:NULL;
		$parent_spec__field_name=array_key_exists('parent_spec__field_name',$info)?$info['parent_spec__field_name']:NULL;
		require_code('ocfiltering');
		return ocfilter_to_sqlfragment($filter,$category_field_filter,$parent_spec__table_name,$parent_spec__parent_name,$parent_field_name,$parent_spec__field_name,!$category_is_string,!$category_is_string);
	}
}


