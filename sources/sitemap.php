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

/**
 * Standard code module initialisation function.
 */
function init__sitemap()
{
	// Defining what should be gathered with the sitemap
	define('SITEMAP_GATHER_DESCRIPTION',1);
	define('SITEMAP_GATHER_IMAGE',2);
	define('SITEMAP_GATHER_TIMES',4);
	define('SITEMAP_GATHER_SUBMITTER',8);
	define('SITEMAP_GATHER_AUTHOR',16);
	define('SITEMAP_GATHER_VIEWS',32);
	define('SITEMAP_GATHER_RATING',64);
	define('SITEMAP_GATHER_NUM_COMMENTS',128);
	define('SITEMAP_GATHER_META',256);
	define('SITEMAP_GATHER_CATEGORIES',512);
	define('SITEMAP_GATHER_VALIDATED',1024);
	define('SITEMAP_GATHER_DB_ROW',2048);
	define('SITEMAP_GATHER__ALL',0xFFFFFFF);

	// Defining how a node will be handle
	define('SITEMAP_NODE_NOT_HANDLED',0);
	define('SITEMAP_NODE_HANDLED',1);
	define('SITEMAP_NODE_HANDLED_VIRTUALLY',2); // Not a real node, but a virtual node for which we can accumulate real nodes at

	// Sitemap importances
	define('SITEMAP_IMPORTANCE_NONE',0.0);
	//define('SITEMAP_IMPORTANCE_',0.1);
	define('SITEMAP_IMPORTANCE_LOW',0.2);
	//define('SITEMAP_IMPORTANCE_',0.3);
	//define('SITEMAP_IMPORTANCE_',0.4);
	define('SITEMAP_IMPORTANCE_MEDIUM',0.5);
	//define('SITEMAP_IMPORTANCE_',0.6);
	//define('SITEMAP_IMPORTANCE_',0.7);
	define('SITEMAP_IMPORTANCE_HIGH',0.8);
	//define('SITEMAP_IMPORTANCE_',0.9);
	define('SITEMAP_IMPORTANCE_ULTRA',1.0);

	// Defining how the content-selection list should be put together
	define('CSL_PERMISSION_VIEW',1);
	define('CSL_PERMISSION_ADD',2);
	define('CSL_PERMISSION_EDIT',4);
	define('CSL_PERMISSION_DELETE',8);

	// Other constants
	define('SITEMAP_MAX_ROWS_PER_LOOP',500);
}

/**
 * Find details of a position in the sitemap (shortcut into the object structure).
 *
 * @param  ?ID_TEXT 		The page-link we are finding (NULL: root).
 * @param  ?mixed  		Callback function to send discovered page-links to (NULL: return).
 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
 * @param  ?integer		How deep to go from the sitemap root (NULL: no limit).
 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
 * @param  boolean		Whether to filter out non-validated content.
 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
 * @return ?array			Node structure (NULL: working via callback).
 */
function retrieve_sitemap_node($pagelink=NULL,$callback=NULL,$valid_node_types=NULL,$max_recurse_depth=NULL,$require_permission_support=false,$zone='_SEARCH',$consider_validation=false,$consider_secondary_categories=false,$meta_gather=0)
{
	$hook=mixed();
	$is_virtual=false;
	if (is_null($pagelink))
	{
		$hook='root';
		require_code('hooks/systems/sitemap/root');
		$ob=object_factory('Hook_sitemap_root');
		$is_virtual=true;
	} else
	{
		$hooks=find_all_hooks('systems','sitemap');
		foreach (array_keys($hooks) as $_hook)
		{
			require_code('hooks/systems/sitemap/'.$_hook);
			$ob=object_factory('Hook_sitemap_'.$_hook);
			if ($ob->is_active())
			{
				$is_handled=$ob->handles_pagelink($pagelink);
				if ($is_handled!=SITEMAP_NODE_NOT_HANDLED)
				{
					$is_virtual=($is_handled==SITEMAP_NODE_HANDLED_VIRTUALLY);
					$hook=$_hook;
					break;
				}
			}
		}
		if (is_null($hook))
			warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	}

	if ($is_virtual)
		return $ob->get_virtual_nodes($pagelink,$callback,$valid_node_types,$max_recurse_depth,0,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather);
	return $ob->get_node($pagelink,$callback,$valid_node_types,$max_recurse_depth,0,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather);
}

abstract class Hook_sitemap_base
{
	/**
	 * Find whether the hook is active.
	 *
	 * @return boolean		Whether the hook is active.
	 */
	function is_active()
	{
		return true;
	}

	/**
	 * Find if a page-link will be covered by this node.
	 *
	 * @param  ID_TEXT		The page-link.
	 * @return integer		A SITEMAP_NODE_* constant.
	 */
	abstract function handles_pagelink($pagelink);

	/**
	 * Get a particular sitemap object. Used for easily tying in a different kind of child node.
	 *
	 * @param  ID_TEXT		The hook, i.e. the sitemap object type. Usually the same as a content type.
	 * @return object			The sitemap object.
	 */
	protected function _get_sitemap_object($hook)
	{
		require_code('hooks/systems/sitemap/'.filter_naughty($hook));
		return object_factory('Hook_sitemap_'.$hook);
	}

	/**
	 * Find all nodes at the top level position in the sitemap for this hook.
	 * May be a single node (i.e. a category root) or multiple nodes (if there's a flat structure).
	 *
	 * @param  ID_TEXT  		The page-link we are finding.
	 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
	 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
	 * @param  ?integer		How deep to go from the sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by Google sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  ?array			Database row (NULL: lookup).
	 * @return ?array			List of node structures (NULL: working via callback).
	 */
	abstract function get_virtual_nodes($pagelink,$callback=NULL,$valid_node_types=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0,$row=NULL);

	/**
	 * Find details of a position in the sitemap.
	 *
	 * @param  ID_TEXT  		The page-link we are finding.
	 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
	 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
	 * @param  ?integer		How deep to go from the sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by Google sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  ?array			Database row (NULL: lookup).
	 * @return ?array			Node structure (NULL: working via callback).
	 */
	abstract function get_node($pagelink,$callback=NULL,$valid_node_types=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0,$row=NULL);

	/**
	 * Convert a page-link to a category ID and category permission module type.
	 *
	 * @param  string	The page-link
	 * @return ?array	The pair (NULL: permission modules not handled)
	 */
	function extract_child_pagelink_permission_pair($pagelink)
	{
		return NULL;
	}
}

abstract class Hook_sitemap_content extends Hook_sitemap_base
{
	protected $content_type=NULL;
	protected $entry_content_type=NULL;
	protected $entry_sitetree_hook=NULL;
	protected $cma_info=NULL;

	/**
	 * Find if a page-link will be covered by this node.
	 *
	 * @param  ID_TEXT		The page-link.
	 * @return integer		A SITEMAP_NODE_* constant.
	 */
	function handles_pagelink($pagelink)
	{
		$matches=array();
		preg_match('#^([^:]*):([^:]*)#',$pagelink,$matches);
		$page=$matches[2];

		require_code('content');
		$cma_ob=get_content_object($this->content_type);
		$cma_info=$cma_ob->info();
		if ($cma_info['module']==$page)
		{
			if ($matches[0]==$pagelink) return SITEMAP_NODE_HANDLED;
			return SITEMAP_NODE_HANDLED_VIRTUALLY;
		}
		return SITEMAP_NODE_NOT_HANDLED;
	}

	/**
	 * Get a content ID via a page-link.
	 *
	 * @param  ID_TEXT		The page-link.
	 * @return ID_TEXT		The ID.
	 */
	function _get_pagelink_id($pagelink)
	{
		$matches=array();
		preg_match('#^([^:]*):([^:]*):([^:]*):([^:]*)#',$pagelink,$matches);
		return $matches[4];
	}

	/**
	 * Get the CMA info for our content hook.
	 *
	 * @return array			The CMA info.
	 */
	protected function _get_cma_info()
	{
		if ($this->cma_info===NULL)
		{
			require_code('content');
			$cma_ob=get_content_object($this->content_type);
			$this->cma_info=$cma_ob->info();
		}
		return $this->cma_info;
	}

	/**
	 * Get the database row for some content.
	 *
	 * @param  ID_TEXT		The content ID.
	 * @return array			The content row.
	 */
	protected function _get_row($content_id)
	{
		$cma_info=$this->_get_cma_info();
		return content_get_row($content_id,$cma_info);
	}

	/**
	 * Pre-fill part of the node structure, from what we know from the CMA hook.
	 *
	 * @param  ID_TEXT  		The page-link we are finding.
	 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
	 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
	 * @param  ?integer		How deep to go from the sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by Google sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  ?array			Database row (NULL: lookup).
	 * @return ?array			A tuple: content ID, row, partial node structure (NULL: filtered).
	 */
	function _create_partial_node_structure($pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather,$row)
	{
		$content_id=$this->_get_pagelink_id($pagelink);
		if ($row===NULL)
		{
			$row=$this->_get_row($content_id);
		}
		$cma_info=$this->_get_cma_info();

		if (strpos($cma_info['title_field'],'CALL:')!==false)
		{
			$title_value=call_user_func(trim(substr($cma_info['title_field'],5)),array('id'=>$content_id),false);
			$title=make_string_tempcode(escape_html($title_value));
		} else
		{
			$title_value=$row[$cma_info['title_field']];
			if ((isset($cma_info['title_field_supports_comcode'])) && ($cma_info['title_field_supports_comcode']))
			{
				$title=get_translated_tempcode($title_value,$cma_info['connection']);
			} else
			{
				$title=make_string_tempcode(escape_html($cma_info['title_field_dereference']?get_translated_text($title_value,$cma_info['connection']):$title_value));
			}
		}

		$struct=array(
			'title'=>$title,
			'content_type'=>$this->content_type,
			'content_id'=>$content_id,
			'pagelink'=>$pagelink,
			'extra_meta'=>array(
				'description'=>NULL,
				'image'=>NULL,
				'image_2x'=>NULL,
				'add_date'=>NULL,
				'edit_date'=>NULL,
				'submitter'=>NULL,
				'views'=>NULL,
				'rating'=>NULL,
				'meta_keywords'=>NULL,
				'meta_description'=>NULL,
				'categories'=>NULL,
				'validated'=>NULL,
				'db_row'=>NULL,
			),
			'permissions'=>array(
			),
			'has_possible_children'=>$cma_info['is_category'],

			// These are likely to be changed in individual hooks
			'sitemap_priority'=>SITEMAP_IMPORTANCE_MEDIUM,
			'sitemap_refreshfreq'=>'monthly',
		);

		if (isset($cma_info['permissions_type_code']))
		{
			$matches=array();
			preg_match('#^([^:]*):([^:]*):([^:]*):([^:]*)#',$pagelink,$matches);
			$page=$matches[2];

			$struct['permissions'][]=array(
				'type'=>'category',
				'permission_module'=>$cma_info['permissions_type_code'],
				'category_name'=>$cma_info['id_category']?$content_id:$row[$cma_info['category_field']],
				'page_name'=>$page,
			);
		}

		if ((($meta_gather & SITEMAP_GATHER_DESCRIPTION)!=0) && (isset($cma_info['description_field'])))
		{
			$description=$row[$cma_info['description_field']];
			if (is_integer($description))
			{
				$struct['extra_meta']['description']=get_translated_tempcode($description,$cma_info['connection']);
			} else
			{
				$struct['extra_meta']['description']=make_string_tempcode(escape_html($description));
			}
		}

		if ((($meta_gather & SITEMAP_GATHER_IMAGE)!=0) && (isset($cma_info['thumb_field'])))
		{
			if (strpos($cma_info['thumb_field'],'CALL:')!==false)
			{
				$struct['extra_meta']['image']=call_user_func(trim(substr($cma_info['thumb_field'],5)),array('id'=>$content_id),false);
			} else
			{
				$struct['extra_meta']['image']=$row[$cma_info['thumb_field']];
			}
			if ((isset($cma_info['thumb_field_is_theme_image'])) && ($cma_info['thumb_field_is_theme_image']))
			{
				if ($struct['extra_meta']['image']!='')
					$struct['extra_meta']['image']=find_theme_image($struct['extra_meta']['image']);
			}
		}

		if (($meta_gather & SITEMAP_GATHER_TIMES)!=0)
		{
			if (isset($cma_info['add_time_field']))
				$struct['extra_meta']['add_time']=$row[$cma_info['add_time_field']];

			if (isset($cma_info['edit_time_field']))
				$struct['extra_meta']['edit_time']=$row[$cma_info['edit_time_field']];
		}

		if ((($meta_gather & SITEMAP_GATHER_SUBMITTER)!=0) && (isset($cma_info['submitter_field'])))
			$struct['extra_meta']['submitter']=$row[$cma_info['submitter_field']];

		if ((($meta_gather & SITEMAP_GATHER_AUTHOR)!=0) && (isset($cma_info['author_field'])))
			$struct['extra_meta']['author']=$row[$cma_info['author_field']];

		if ((($meta_gather & SITEMAP_GATHER_VIEWS)!=0) && (isset($cma_info['views_field'])))
			$struct['extra_meta']['views']=$row[$cma_info['views_field']];

		if ((($meta_gather & SITEMAP_GATHER_RATING)!=0) && (isset($cma_info['feedback_type_code'])))
		{
			$rating=$GLOBALS['SITE_DB']->query_select_value('rating','AVG(rating)',array('rating_for_type'=>$cma_info['feedback_type_code'],'rating_for_id'=>$content_id));
			$struct['extra_meta']['rating']=$rating;
		}

		if ((($meta_gather & SITEMAP_GATHER_NUM_COMMENTS)!=0) && (isset($cma_info['feedback_type_code'])))
		{
			$num_comments=0;
			$_comments=$GLOBALS['FORUM_DRIVER']->get_forum_topic_posts($GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier(get_option('comments_forum_name'),$cma_info['feedback_type_code'].'_'.$content_id),$num_comments,0,0,false);

			$struct['extra_meta']['num_comments']=$num_comments;
		}

		if ((($meta_gather & SITEMAP_GATHER_META)!=0) && (isset($cma_info['seo_type_code'])))
		{
			list($struct['extra_meta']['meta_keywords'],$struct['extra_meta']['meta_description'])=seo_meta_get_for($this->content_type,$content_id);
		}

		if ((($meta_gather & SITEMAP_GATHER_VALIDATED)!=0) && (isset($cma_info['validated_field'])))
			$struct['extra_meta']['validated']=$row[$cma_info['validated_field']];

		if (($meta_gather & SITEMAP_GATHER_DB_ROW)!=0)
			$struct['extra_meta']['db_row']=$row;

		return array($content_id,$row,$struct);
	}

	/**
	 * Get a list of child nodes, from what we know from the CMA hook.
	 *
	 * @param  ID_TEXT  		The content ID.
	 * @param  ID_TEXT  		The page-link we are finding.
	 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
	 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
	 * @param  ?integer		How deep to go from the sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by Google sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  ?array			Database row (NULL: lookup).
	 * @param  string			Extra SQL piece for considering which entries to load.
	 * @param  ?string		Order by for entries (NULL: alphabetical title)
	 * @param  ?string		Order by for categories (NULL: alphabetical title)
	 * @return array			Child nodes.
	 */
	function _get_children_nodes($content_id,$pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather,$row,$extra_where_entries='',$explicit_order_by_entries=NULL,$explicit_order_by_categories=NULL)
	{
		// Filters...
		if ($recurse_level>=$max_recurse_depth)
		{
			return array();
		}
		if (($valid_node_types!==NULL) && (!in_array($this->content_type,$valid_node_types)))
		{
			return array();
		}

		$cma_info=$this->_get_cma_info();

		$matches=array();
		preg_match('#^([^:]*):([^:]*):([^:]*):([^:]*)#',$pagelink,$matches);
		$page=$matches[2];

		$children=array();

		// Entries...
		if ($cma_info['is_category'])
		{
			if ($this->entry_content_type!==NULL)
			{
				for ($i=0;$i<count($this->entry_content_type);$i++)
				{
					$entry_content_type=$this->entry_content_type[$i];
					$entry_sitetree_hook=$this->entry_sitetree_hook[$i];

					require_code('content');
					$cma_entry_ob=get_content_object($entry_content_type);
					$cma_entry_info=$cma_entry_ob->info();

					if ((!$require_permission_support) || (isset($cma_entry_info['permissions_type_code'])))
					{
						$child_hook_ob=$this->_get_sitemap_object($entry_sitetree_hook);

						$children_entries=array();

						$privacy_join='';
						$privacy_where='';
						if ((isset($cma_entry_info['supports_privacy'])) && ($cma_entry_info['supports_privacy']))
						{
							if (addon_installed('content_privacy'))
							{
								require_code('content_privacy');
								list($privacy_join,$privacy_where)=get_privacy_where_clause($entry_content_type,'r');
							}
						}

						$start=0;
						do
						{
							$where=array();
							$where[$cma_entry_info['category_field']]=$cma_entry_info['id_field_numeric']?intval($content_id):$content_id;
							if (($consider_validation) && (isset($cma_entry_info['validated_field'])))
								$where[$cma_entry_info['validated_field']]=1;
							$rows=$cma_entry_info['connection']->query_select($cma_entry_info['table'].' r'.$privacy_join,array('*'),$where,$extra_where_entries.$privacy_where.(is_null($explicit_order_by_entries)?'':(' ORDER BY '.$explicit_order_by_entries)),SITEMAP_MAX_ROWS_PER_LOOP,$start);
							foreach ($rows as $child_row)
							{
								$child_pagelink=$zone.':'.$page.':'.$child_hook_ob->screen_type.':'.($cma_entry_info['id_field_numeric']?strval($child_row[$cma_entry_info['id_field']]):$child_row[$cma_entry_info['id_field']]);
								$node=$child_hook_ob->_create_partial_node_structure($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather,$child_row);
								if ($node!==NULL)
									$children_entries[]=$node;
							}
							$start+=SITEMAP_MAX_ROWS_PER_LOOP;
						}
						while (count($rows)>0);

						if (is_null($explicit_order_by_entries))
						{
							multi_sort($children_entries,'title');
							$children=array_merge($children,$children_entries);
						}
					}
				}
			}
		}

		// Subcategories...
		if ((isset($cma_info['parent_spec__parent_name'])) && ($cma_info['parent_category_meta_aware_type']==$this->content_type))
		{
			$children_categories=array();

			$start=0;
			do
			{
				$where=array();
				$where[$cma_info['parent_spec__parent_name']]=$cma_info['category_is_string']?$content_id:intval($content_id);
				if (($consider_validation) && (isset($cma_info['validated_field'])))
					$where[$cma_info['validated_field']]=1;
				$rows=$cma_info['connection']->query_select($cma_info['parent_spec__table_name'],array('*'),$where,(is_null($explicit_order_by_entries)?'':('ORDER BY '.$explicit_order_by_subcategories)),SITEMAP_MAX_ROWS_PER_LOOP,$start);
				foreach ($rows as $child_row)
				{
					if ($this->content_type=='comcode_page')
					{
						$child_pagelink=$zone.':'.$child_row['the_page'];
					} else
					{
						$child_pagelink=$zone.':'.$page.':'.$this->screen_type.':'.($cma_info['category_is_string']?$child_row[$cma_info['parent_spec__field_name']]:strval($child_row[$cma_info['parent_spec__field_name']]));
					}
					$node=$this->_create_partial_node_structure($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather,$child_row);
					if ($node!==NULL)
						$children_categories[]=$node;
				}
				$start+=SITEMAP_MAX_ROWS_PER_LOOP;
			}
			while (count($rows)>0);

			if (is_null($explicit_order_by_categories))
			{
				multi_sort($children_categories,'title');
				$children=array_merge($children,$children_categories);
			}
		}

		return $children;
	}

	/**
	 * Convert a page-link to a category ID and category permission module type.
	 *
	 * @param  ID_TEXT		The page-link
	 * @return ?array			The pair (NULL: permission modules not handled)
	 */
	function extract_child_pagelink_permission_pair($pagelink)
	{
		$matches=array();
		preg_match('#^([^:]*):([^:]*):type=misc:id=(.*)$#',$pagelink,$matches);
		$id=$matches[3];

		require_code('content');
		$cma_ob=get_content_object($this->content_type);
		$cma_info=$cma_ob->info();

		return array($id,$cma_info['permissions_type_code']);
	}
}

/**
 * Get an HTML selection list for some part of the sitemap.
 *
 * @param  ID_TEXT  		The page-link we are starting from.
 * @param  ?ID_TEXT		Default selection (NULL: none).
 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
 * @param  ?array			List of node types we will allow to be selectable (NULL: no limit)
 * @param  integer		Check permissions according to this bitmask of possibilities (requiring all in the bitmask to be matched)
 * @param  ?MEMBER		The member we are checking permissions for (NULL: current member)
 * @param  boolean		Whether to filter out non-validated entries if the $check_permissions_for user doesn't have the privilege to see them AND doesn't own them
 * @param  ?MEMBER		The member we are only finding owned content of (NULL: no such limit); nodes leading up to owned content will be shown, but not as selectable
 * @param  boolean		Whether to produce selection IDs as a comma-separated list of all selectable sub-nodes.
 * @param  ?mixed  		Filter function for limiting what rows will be included (NULL: none).
 * @return tempcode		List.
 */
function create_selection_list($root_pagelink,$default=NULL,$valid_node_types=NULL,$valid_selectable_content_types=NULL,$check_permissions_against=0,$check_permissions_for=NULL,$consider_validation=false,$only_owned=NULL,$use_compound_list=false,$filter_func=NULL)
{
	if (is_null($check_permissions_for)) $check_permissions_for=get_member();

	$out=new ocp_tempcode();
	$root_node=retrieve_sitemap_node($root_pagelink,NULL,NULL,false,'_SEARCH',$consider_validation,false,is_null($filter_func)?0:SITEMAP_GATHER_DB_ROW);
	foreach ($root_node['children'] as $child_node)
	{
		_create_selection_list($out,$child_node,$default,$valid_selectable_content_types,$check_permissions_against,$check_permissions_for,$only_owned,$use_compound_list,$filter_func);
	}
	return $out;
}

/**
 * Recurse function for create_selection_list.
 *
 * @param  tempcode  	Output Tempcode.
 * @param  array  		Node being recursed.
 * @param  ?ID_TEXT		Default selection (NULL: none).
 * @param  ?array			List of node types we will allow to be selectable (NULL: no limit)
 * @param  integer		Check permissions according to this bitmask of possibilities (requiring all in the bitmask to be matched)
 * @param  ?MEMBER		The member we are checking permissions for (NULL: current member)
 * @param  ?MEMBER		The member we are only finding owned content of (NULL: no such limit); nodes leading up to owned content will be shown, but not as selectable
 * @param  boolean		Whether to produce selection IDs as a comma-separated list of all selectable sub-nodes.
 * @param  ?mixed  		Filter function for limiting what rows will be included (NULL: none).
 * @param  integer		Recursion depth.
 * @return string			Compound list.
 */
function _create_selection_list(&$out,$node,$default,$valid_selectable_content_types,$check_permissions_against,$check_permissions_for,$only_owned,$use_compound_list,$filter_func,$depth=0)
{
	// Skip?
	if (!is_null($check_permissions_for))
	{
		foreach ($node['permissions'] as $permission)
		{
			if (($check_permissions_against & CSL_PERMISSION_VIEW) != 0)
			{
				switch ($permission['type'])
				{
					case 'non_guests':
						if (is_guest($check_permissions_for))
							return '';
						break;

					case 'zone':
						if (!has_zone_access($check_permissions_for,$permission['zone_name']))
							return '';
						break;

					case 'page':
						if (!has_page_access($check_permissions_for,$permission['zone_name'],$permission['page_name']))
							return '';
						break;

					case 'category':
						if (!has_category_access($check_permissions_for,$permission['permission_module'],$permission['category_name']))
							return '';
						break;
				}
			}
			if ($permission['type']=='privilege')
			{
				if (($check_permissions_against & CSL_PERMISSION_ADD) != 0)
				{
					if (preg_match('#^submit_#',$permission['privilege'])!=0)
					{
						if (!has_privilege($check_permissions_for,$permission['privilege'],$permission['page_name'],array($permission['permission_module'],$permission['category_name'])))
							return '';
					}
				}
				if (($check_permissions_against & CSL_PERMISSION_EDIT) != 0)
				{
					if (preg_match('#^edit_#',$permission['privilege'])!=0)
					{
						if (!has_privilege($check_permissions_for,$permission['privilege'],$permission['page_name'],array($permission['permission_module'],$permission['category_name'])))
							return '';
					}
				}
				if (($check_permissions_against & CSL_PERMISSION_DELETE) != 0)
				{
					if (preg_match('#^delete_#',$permission['privilege'])!=0)
					{
						if (!has_privilege($check_permissions_for,$permission['privilege'],$permission['page_name'],array($permission['permission_module'],$permission['category_name'])))
							return '';
					}
				}
			}
		}
	}
	if (!is_null($only_owned))
	{
		if ($node['submitter']!=$only_owned) return '';
	}
	if (!is_null($filter_func))
	{
		if (!call_user_func($filter_func,$node)) return '';
	}

	$content_id=$node['content_id'];

	// Recurse, working out $children and $compound_list
	$children=new ocp_tempcode();
	$child_compound_list='';
	foreach ($node['children'] as $child_node)
	{
		$_child_compound_list=_create_selection_list($children,$child_node,$default,$valid_selectable_content_types,$check_permissions_against,$check_permissions_for,$only_owned,$use_compound_list,$filter_func,$depth+1);
		if ($_child_compound_list!='')
			$child_compound_list.=($child_compound_list!='')?(','.$_child_compound_list):$_child_compound_list;
	}
	$compound_list=$content_id.(($child_compound_list!='')?(','.$child_compound_list):'');

	// Handle node
	$title=str_repeat(' ',$depth).$node['title'];
	$selected=($content_id===(is_integer($default)?strval($default):$default));
	$disabled=(!is_null($valid_selectable_content_types) && !in_array($node['content_type'],$valid_selectable_content_types));
	$_content_id=$use_compound_list?$compound_list:$content_id;
	$out->attach(form_input_list_entry($_content_id,$selected,$title,false,$disabled));

	// Attach recursion result
	$out->attach($children);

	return $compound_list;
}
