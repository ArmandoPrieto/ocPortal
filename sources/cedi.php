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
 * @package		cedi
 */

/*
The concept of a chain is crucial to proper understanding of the Wiki+ system. Pages in Wiki+ are not tied to any paticular hierarchical location, but rather may be found via a "chain" of links. For usability, a "bread crumb" trail is shown as you move through Wiki+, and this should reflect the path chosen to get to the current page - thus a chain is passed through the URLs to encode this.
*/

/**
 * Get tempcode for a Wiki+ post 'feature box' for the given row
 *
 * @param  array			The database field row of it
 * @param  ID_TEXT		The zone to use
 * @param  boolean		Whether to put it in a box
 * @return tempcode		A box for it, linking to the full page
 */
function render_cedi_post_box($row,$zone='_SEARCH',$put_in_box=true)
{
	$url=build_url(array('page'=>'cedi','type'=>'misc','id'=>$row['page_id']),$zone);
	$url->attach('#post_'.strval($row['id']));

	$breadcrumbs=mixed();
	$title=mixed();
	if ($put_in_box)
	{
		$breadcrumbs=cedi_breadcrumbs(strval($row['page_id']),NULL,true);
		$title=do_lang_tempcode('CEDI_POST');
	}

	return do_template('SIMPLE_PREVIEW_BOX',array('_GUID'=>'f271c035af57eb45b7f3b37e437baf3c','TITLE'=>$title,'BREADCRUMBS'=>$breadcrumbs,'SUMMARY'=>get_translated_tempcode($row['the_message']),'URL'=>$url));
}

/**
 * Get tempcode for a CEDI post 'feature box' for the given row
 *
 * @param  array			The database field row of it
 * @param  ID_TEXT		The zone to use
 * @param  boolean		Whether to put it in a box with a title
 * @return tempcode		A box for it, linking to the full page
 */
function render_cedi_page_box($row,$zone='_SEARCH',$put_in_box=true)
{
	$content=paragraph(get_translated_tempcode($row['description']),'tyrtfjhggfdf');
	$url=build_url(array('page'=>'cedi','type'=>'misc','id'=>$row['id']),$zone);

	$breadcrumbs=mixed();
	$title=mixed();
	if ($put_in_box)
	{
		$title=do_lang_tempcode('CEDI_PAGE',escape_html(get_translated_text($row['title'])));

		$chain=cedi_derive_chain($row['id']);
		$chain=preg_replace('#/[^/]+#','',$chain);
		if ($chain!='')
			$breadcrumbs=cedi_breadcrumbs($chain,NULL,true);
	}

	return do_template('SIMPLE_PREVIEW_BOX',array('_GUID'=>'d2c37a1f68e684dc4ac85e3d4e4bf959','TITLE'=>$title,'BREADCRUMBS'=>$breadcrumbs,'SUMMARY'=>$content,'URL'=>$url));
}

/**
 * Edit a CEDI post
 *
 * @param  AUTO_LINK		The page ID
 * @param  string			The new post
 * @param  BINARY			Whether the post will be validated
 * @param  ?MEMBER		The member doing the action (NULL: current member)
 * @param  boolean		Whether to send out a notification out
 * @return AUTO_LINK		The post ID
 */
function cedi_add_post($page_id,$message,$validated=1,$member=NULL,$send_notification=true)
{
	if (is_null($member)) $member=get_member();

	require_code('comcode_check');
	check_comcode($message,NULL,false,NULL,true);

	if (!addon_installed('unvalidated')) $validated=1;
	$id=$GLOBALS['SITE_DB']->query_insert('seedy_posts',array('validated'=>$validated,'edit_date'=>NULL,'the_message'=>0,'the_user'=>$member,'date_and_time'=>time(),'page_id'=>$page_id,'seedy_views'=>0),true);
	require_code('attachments2');
	$the_message=insert_lang_comcode_attachments(2,$message,'cedi_post',strval($id));
	$GLOBALS['SITE_DB']->query_update('seedy_posts',array('the_message'=>$the_message),array('id'=>$id),'',1);

	// Log
	$GLOBALS['SITE_DB']->query_insert('seedy_changes',array('the_action'=>'CEDI_MAKE_POST','the_page'=>$page_id,'ip'=>get_ip_address(),'the_user'=>$member,'date_and_time'=>time()));

	// Update post count
	if (addon_installed('points'))
	{
		require_code('points');
		$_count=point_info($member);
		$count=array_key_exists('points_gained_seedy',$_count)?$_count['points_gained_seedy']:0;
		$GLOBALS['FORUM_DRIVER']->set_custom_field($member,'points_gained_seedy',$count+1);
	}

	// Stat
	update_stat('num_seedy_posts',1);
	//update_stat('num_seedy_files',count($_FILES));

	if ($send_notification)
	{
		if (post_param_integer('send_notification',NULL)!==0)
		{
			dispatch_cedi_post_notification($id,'ADD');
		}
	}

	if (get_option('show_post_validation')=='1') decache('main_staff_checklist');

	return $id;
}

/**
 * Edit a Wiki+ post
 *
 * @param  AUTO_LINK		The post ID
 * @param  string			The new post
 * @param  BINARY			Whether the post will be validated
 * @param  ?MEMBER		The member doing the action (NULL: current member)
 */
function cedi_edit_post($id,$message,$validated,$member=NULL)
{
	if (is_null($member)) $member=get_member();

	$rows=$GLOBALS['SITE_DB']->query_select('seedy_posts',array('*'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$rows[0];
	$original_poster=$myrow['the_user'];
	$page_id=$myrow['page_id'];

	$_message=$GLOBALS['SITE_DB']->query_value('seedy_posts','the_message',array('id'=>$id));

	require_code('attachments2');
	require_code('attachments3');

	if (!addon_installed('unvalidated')) $validated=1;

	require_code('submit');
	$just_validated=(!content_validated('cedi_post',strval($id))) && ($validated==1);
	if ($just_validated)
	{
		send_content_validated_notification('cedi_post',strval($id));
	}

	$GLOBALS['SITE_DB']->query_update('seedy_posts',array('validated'=>$validated,'edit_date'=>time(),'the_message'=>update_lang_comcode_attachments($_message,$message,'cedi_post',strval($id),NULL,true,$original_poster)),array('id'=>$id),'',1);

	$GLOBALS['SITE_DB']->query_insert('seedy_changes',array('the_action'=>'CEDI_EDIT_POST','the_page'=>$page_id,'ip'=>get_ip_address(),'the_user'=>$member,'date_and_time'=>time()));

	if (post_param_integer('send_notification',NULL)!==0)
	{
		if ($just_validated)
		{
			dispatch_cedi_post_notification($id,'ADD');
		} else
		{
			dispatch_cedi_post_notification($id,'EDIT');
		}
	}
}

/**
 * Delete a Wiki+ post
 *
 * @param  AUTO_LINK		The post ID
 * @param  ?MEMBER		The member doing the action (NULL: current member)
 */
function cedi_delete_post($post_id,$member=NULL)
{
	if (is_null($member)) $member=get_member();

	$original_poster=$GLOBALS['SITE_DB']->query_value('seedy_posts','the_user',array('id'=>$post_id));

	$_message=$GLOBALS['SITE_DB']->query_value('seedy_posts','the_message',array('id'=>$post_id));

	require_code('attachments2');
	require_code('attachments3');
	delete_lang_comcode_attachments($_message,'cedi_post',strval($post_id));

	$GLOBALS['SITE_DB']->query_delete('seedy_posts',array('id'=>$post_id),'',1);
	$GLOBALS['SITE_DB']->query_delete('rating',array('rating_for_type'=>'seedy_post','rating_for_id'=>$post_id));

	$GLOBALS['SITE_DB']->query_insert('seedy_changes',array('the_action'=>'CEDI_DELETE_POST','the_page'=>$post_id,'ip'=>get_ip_address(),'the_user'=>$member,'date_and_time'=>time()));

	$GLOBALS['SITE_DB']->query_update('catalogue_fields f JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'catalogue_efv_short v ON v.cf_id=f.id',array('cv_value'=>''),array('cv_value'=>strval($post_id),'cf_type'=>'wiki_post'));

	// Stat
	update_stat('num_seedy_posts',-1);
}

/**
 * Add a Wiki+ page
 *
 * @param  SHORT_TEXT	The page title
 * @param  LONG_TEXT		The page description
 * @param  LONG_TEXT		Hidden notes pertaining to the page
 * @param  BINARY			Whether to hide the posts on the page by default
 * @param  ?MEMBER		The member doing the action (NULL: current member)
 * @return AUTO_LINK		The page ID
 */
function cedi_add_page($title,$description,$notes,$hide_posts,$member=NULL)
{
	if (is_null($member)) $member=get_member();

	require_code('comcode_check');
	check_comcode($description,NULL,false,NULL,true);

	if ($description!='')
	{
		$id=$GLOBALS['SITE_DB']->query_insert('seedy_pages',array('submitter'=>$member,'hide_posts'=>$hide_posts,'seedy_views'=>0,'notes'=>$notes,'description'=>0,'title'=>insert_lang($title,2),'add_date'=>time()),true);

		require_code('attachments2');
		$GLOBALS['SITE_DB']->query_update('seedy_pages',array('description'=>insert_lang_comcode_attachments(2,$description,'cedi_page',strval($id),NULL,false,$member)),array('id'=>$id),'',1);
	} else
	{
		$id=$GLOBALS['SITE_DB']->query_insert('seedy_pages',array('submitter'=>$member,'hide_posts'=>$hide_posts,'seedy_views'=>0,'notes'=>$notes,'description'=>insert_lang($description,2),'title'=>insert_lang($title,2),'add_date'=>time()),true);
	}

	update_stat('num_seedy_pages',1);

	$GLOBALS['SITE_DB']->query_insert('seedy_changes',array('the_action'=>'CEDI_ADD_PAGE','the_page'=>$id,'date_and_time'=>time(),'ip'=>get_ip_address(),'the_user'=>$member));

	require_code('seo2');
	seo_meta_set_for_implicit('seedy_page',strval($id),array($title,$description),$description);

	if (post_param_integer('send_notification',NULL)!==0)
	{
		dispatch_cedi_page_notification($id,'ADD');
	}

	return $id;
}

/**
 * Edit a Wiki+ page
 *
 * @param  AUTO_LINK		The page ID
 * @param  SHORT_TEXT	The page title
 * @param  LONG_TEXT		The page description
 * @param  LONG_TEXT		Hidden notes pertaining to the page
 * @param  BINARY			Whether to hide the posts on the page by default
 * @param  SHORT_TEXT	Meta keywords
 * @param  LONG_TEXT		Meta description
 * @param  ?MEMBER		The member doing the action (NULL: current member)
 */
function cedi_edit_page($id,$title,$description,$notes,$hide_posts,$meta_keywords,$meta_description,$member=NULL)
{
	if (is_null($member)) $member=get_member();

	$pages=$GLOBALS['SITE_DB']->query_select('seedy_pages',array('*'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$pages)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$page=$pages[0];
	$_description=$page['description'];
	$_title=$page['title'];

	require_code('attachments2');
	require_code('attachments3');
	$GLOBALS['SITE_DB']->query_update('seedy_pages',array('hide_posts'=>$hide_posts,'description'=>update_lang_comcode_attachments($_description,$description,'cedi_page',strval($id),NULL,true),'notes'=>$notes,'title'=>lang_remap($_title,$title)),array('id'=>$id),'',1);
	$GLOBALS['SITE_DB']->query_insert('seedy_changes',array('the_action'=>'CEDI_EDIT_PAGE','the_page'=>$id,'date_and_time'=>time(),'ip'=>get_ip_address(),'the_user'=>$member));

	require_code('seo2');
	seo_meta_set_for_explicit('seedy_page',strval($id),$meta_keywords,$meta_description);

	if (post_param_integer('send_notification',NULL)!==0)
	{
		dispatch_cedi_page_notification($id,'EDIT');
	}
}

/**
 * Delete a Wiki+ page
 *
 * @param  AUTO_LINK		The page ID
 */
function cedi_delete_page($id)
{
	if (function_exists('set_time_limit')) @set_time_limit(0);

	$start=0;
	do
	{
		$posts=$GLOBALS['SITE_DB']->query_select('seedy_posts',array('id'),array('page_id'=>$id),'',500,$start);
		foreach ($posts as $post)
		{
			cedi_delete_post($post['id']);
		}
		$start+=500;
	}
	while (array_key_exists(0,$posts));
	$pages=$GLOBALS['SITE_DB']->query_select('seedy_pages',array('*'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$pages)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$page=$pages[0];
	$_description=$page['description'];
	$_title=$page['title'];
	delete_lang($_description);
	delete_lang($_title);
	$GLOBALS['SITE_DB']->query_delete('seedy_pages',array('id'=>$id),'',1);
	$GLOBALS['SITE_DB']->query_delete('seedy_children',array('parent_id'=>$id));
	$GLOBALS['SITE_DB']->query_delete('seedy_children',array('child_id'=>$id));
	$GLOBALS['SITE_DB']->query_delete('seedy_changes',array('the_page'=>$id));

	$GLOBALS['SITE_DB']->query_update('catalogue_fields f JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'catalogue_efv_short v ON v.cf_id=f.id',array('cv_value'=>''),array('cv_value'=>strval($id),'cf_type'=>'wiki_page'));
}

/**
 * Get a chain script parameter or just an ID, in which case it does more work), and converts it into a id/chain pair
 *
 * @param  ID_TEXT		The name of the GET parameter that stores the chain
 * @param  ?string		The default value for the chain (NULL: no default)
 * @return array			An array of two elements: an ID and a chain
 */
function get_param_cedi_chain($parameter_name,$default_value=NULL)
{
	$value=get_param($parameter_name,$default_value,true);
	if (is_numeric($value)) // If you head to a page directly, e.g. via [[example]], should auto-derive breadcrumbs
	{
		$id=intval($value);
		$chain=cedi_derive_chain($id);
	} else
	{
		$chain=$value;
		$parts=explode('/',$chain);
		$id=intval($parts[count($parts)-1]);
	}
	return array($id,$chain);
}

/**
 * Convert a Wiki+ chain to a nice breadcrumb trail.
 *
 * @param  string			The chain to convert (which should include the current page ID)
 * @param  ?string		The title of the current Wiki+ page (if not given, it is looked up) (NULL: work it out)
 * @param  boolean		Whether to show the final breadcrumbs element with a link to it (all others will always have links if $links is true)
 * @param  boolean		Whether to show links to pages in the breadcrumbs
 * @param  boolean		Whether to make the link as a virtual-root link (only applies if $final_link is true)
 * @return tempcode		Tempcode of the breadcrumb XHTML
 */
function cedi_breadcrumbs($chain,$current_title=NULL,$final_link=false,$links=true,$this_link_virtual_root=false)
{
	$insbreadcrumbs=new ocp_tempcode();
	$token=strtok($chain,'/');
	$rebuild_chain='';
	while ($token!==false)
	{
		$next_token=strtok('/');

		if ($rebuild_chain!='') $rebuild_chain.='/';
		$rebuild_chain.=$token;
		$id=($this_link_virtual_root && ($next_token===false))?$token:$rebuild_chain;
		$url=build_url(array('page'=>'cedi','type'=>'misc','id'=>$id)+(($this_link_virtual_root&&($next_token===false))?array('keep_cedi_root'=>$id):array()),get_module_zone('cedi'));
		if ($next_token!==false) // If not the last token (i.e. current page)
		{
			$title=$GLOBALS['SITE_DB']->query_value_null_ok('seedy_pages','title',array('id'=>intval($token)));
			if (is_null($title)) continue;
			$token_title=get_translated_text($title);
			$content=($links)?hyperlink($url,escape_html($token_title),false,false,do_lang_tempcode('GO_BACKWARDS_TO',$token_title),NULL,NULL,'up'):make_string_tempcode(escape_html($token_title));
			if ($insbreadcrumbs->is_empty())
			{
				$insbreadcrumbs->attach($content);
			}
			else
			{
				$insbreadcrumbs->attach(do_template('BREADCRUMB_SEPARATOR'));
				$insbreadcrumbs->attach($content);
			}
		} else
		{
			if (!$insbreadcrumbs->is_empty())
			{
				$insbreadcrumbs->attach(do_template('BREADCRUMB_SEPARATOR'));
			}
			if (is_null($current_title))
			{
				$_current_title=$GLOBALS['SITE_DB']->query_value_null_ok('seedy_pages','title',array('id'=>intval($token)));
				$current_title=is_null($_current_title)?do_lang('MISSING_RESOURCE'):get_translated_text($_current_title);
			}
			if ($final_link)
			{
				$insbreadcrumbs->attach(hyperlink($url,escape_html($current_title),false,false,$this_link_virtual_root?do_lang_tempcode('VIRTUAL_ROOT'):do_lang_tempcode('GO_BACKWARDS_TO',$current_title),NULL,NULL,'up'));
			} else
			{
				$insbreadcrumbs->attach(protect_from_escaping('<span>'.escape_html($current_title).'</span>'));
			}
		}

		$token=$next_token;
	}

	return $insbreadcrumbs;
}

/**
 * Create a Wiki+ chain from the specified page ID
 *
 * @param  AUTO_LINK		The ID of the page to derive a chain for
 * @return string			The Wiki+ chain derived
 */
function cedi_derive_chain($id)
{
	static $parents=array();

	$temp_id=$id;
	$chain=strval($id);
	$seen_before=array();
	$root=get_param_integer('keep_cedi_root',db_get_first_id());
	while ($temp_id>$root)
	{
		$temp_id=array_key_exists($temp_id,$parents)?$parents[$temp_id]:$GLOBALS['SITE_DB']->query_value_null_ok('seedy_children','parent_id',array('child_id'=>$temp_id));
		$parents[$temp_id]=$temp_id;
		if (array_key_exists($temp_id,$seen_before)) break;
		$seen_before[$temp_id]=1;
		if (is_null($temp_id)) break; // Orphaned, so we can't find a chain
		$chain=($chain!='')?strval($temp_id).'/'.$chain:strval($temp_id);
	}
	return $chain;
}

/**
 * Get a nice formatted XHTML list of all the children beneath the specified Wiki+ page. This function is recursive.
 *
 * @param  ?AUTO_LINK	The Wiki+ page to select by default (NULL: none)
 * @param  ?AUTO_LINK	The Wiki+ page to look beneath (NULL: the root)
 * @param  string			Breadcrumbs built up so far, in recursion (blank: starting recursion)
 * @param  boolean		Whether to include orphaned pages in the breadcrumbs
 * @param  boolean		Whether to create a compound list (gets pairs: tempcode, and comma-separated list of children)
 * @param  boolean		Whether to use titles in IDs after a ! (used on tree edit page)
 * @return mixed			Tempcode for the list / pair of tempcode and compound
 */
function cedi_show_tree($select=NULL,$id=NULL,$breadcrumbs='',$include_orphans=true,$use_compound_list=false,$ins_format=false)
{
	if (is_null($id)) $id=db_get_first_id();

	if ($GLOBALS['SITE_DB']->query_value('seedy_pages','COUNT(*)')>1000) return new ocp_tempcode();

	$cedi_seen=array();
	$title=get_translated_text($GLOBALS['SITE_DB']->query_value('seedy_pages','title',array('id'=>$id)));
	$out=_cedi_show_tree($cedi_seen,$select,$id,$breadcrumbs,$title,$use_compound_list,$ins_format);

	if ($include_orphans)
	{
		if (!db_has_subqueries($GLOBALS['SITE_DB']->connection_read))
		{
			$cedi_seen=array(db_get_first_id());
			get_cedi_page_tree($cedi_seen,is_null($id)?NULL:intval($id)); // To build up $cedi_seen
			$where='';
			foreach ($cedi_seen as $seen)
			{
				if ($where!='') $where.=' AND ';
				$where.='p.id<>'.strval((integer)$seen);
			}

			$orphans=$GLOBALS['SITE_DB']->query('SELECT p.id,text_original,p.title FROM '.get_table_prefix().'seedy_pages p LEFT JOIN '.get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND t.id=p.title WHERE '.$where.' ORDER BY add_date DESC',50/*reasonable limit*/);
		} else
		{
			$orphans=$GLOBALS['SITE_DB']->query('SELECT p.id,text_original,p.title FROM '.get_table_prefix().'seedy_pages p LEFT JOIN '.get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND t.id=p.title WHERE p.id<>'.strval(db_get_first_id()).' AND NOT EXISTS(SELECT * FROM '.get_table_prefix().'seedy_children WHERE child_id=p.id) ORDER BY add_date DESC',50/*reasonable limit*/);
			if (count($orphans)<50)
			{
				global $M_SORT_KEY;
				$M_SORT_KEY='text_original';
				usort($orphans,'multi_sort');
			}
		}

		foreach ($orphans as $orphan)
		{
			if (!has_category_access(get_member(),'seedy_page',strval($orphan['id']))) continue;

			if ($GLOBALS['RECORD_LANG_STRINGS_CONTENT'] || is_null($orphan['text_original'])) $orphan['text_original']=get_translated_text($orphan['title']);

			$title=$orphan['text_original'];
			//$out->attach(form_input_list_entry(strval($orphan['id']),($select==$orphan['id']),do_template('WIKI_LIST_TREE_LINE',array('_GUID'=>'e3eb3decfac32382cdcb5b745ef0ad7e','BREADCRUMBS'=>'?','TITLE'=>$title,'ID'=>$orphan['id']))));
//			$out.='<option value="'.$orphan['id'].'"> ? '.$title.'</option>';
			$out->attach(form_input_list_entry($ins_format?(strval($orphan['id']).'!'.$title):strval($orphan['id']),false,do_lang('CEDI_ORPHANED').' > '.$title));
		}
	}

	return $out;
}

/**
 * Helper function. Get a nice formatted XHTML list of all the children beneath the specified Wiki+ page. This function is recursive.
 *
 * @param  array			A list of pages we've already seen (we don't repeat them in multiple list positions)
 * @param  ?AUTO_LINK	The Wiki+ page to select by default (NULL: none)
 * @param  AUTO_LINK		The Wiki+ page to look beneath
 * @param  string			Breadcrumbs built up so far, in recursion (blank: starting recursion)
 * @param  SHORT_TEXT	The title of the Wiki+ page to look beneath
 * @param  boolean		Whether to create a compound list (gets pairs: tempcode, and comma-separated list of children)
 * @param  boolean		Whether to use titles in IDs after a ! (used on tree edit page)
 * @return mixed			Tempcode for the list / pair of tempcode and compound
 */
function _cedi_show_tree(&$cedi_seen,$select,$id,$breadcrumbs,$title,$use_compound_list=false,$ins_format=false)
{
	$cedi_seen[]=$id;

	$sub_breadcrumbs=($breadcrumbs=='')?($title.' > '):($breadcrumbs.$title.' > ');

	$rows=$GLOBALS['SITE_DB']->query_select('seedy_children',array('*'),array('parent_id'=>$id),'ORDER BY title',300/*reasonable limit*/);
	$compound_list=strval($id).',';
	$_below=new ocp_tempcode();
	foreach ($rows as $i=>$myrow)
	{
		if (!in_array($myrow['child_id'],$cedi_seen))
		{
			if (!has_category_access(get_member(),'seedy_page',strval($myrow['child_id']))) continue;

			if (is_null($myrow['title']))
			{
				$temp_rows=$GLOBALS['SITE_DB']->query_select('seedy_pages',array('title'),array('id'=>$myrow['child_id']),'',1);
				$myrow['title']=get_translated_text($temp_rows[0]['title']);
				$rows[$i]['title']=$myrow['title'];
				$GLOBALS['SITE_DB']->query_update('seedy_children',array('title'=>$myrow['title']),array('parent_id'=>$id,'child_id'=>$myrow['child_id']));
			}
			$below=_cedi_show_tree($cedi_seen,$select,$myrow['child_id'],$sub_breadcrumbs,$myrow['title'],$use_compound_list,$ins_format);
			if ($use_compound_list)
			{
				list($below,$_compound_list)=$below;
				$compound_list.=$_compound_list;
			}
			$_below->attach($below);
		}
	}

//	$out=form_input_list_entry(strval($id),($select==$id),do_template('WIKI_LIST_TREE_LINE',array('_GUID'=>'d9d4a951df598edd3f08f87be634965b','BREADCRUMBS'=>$breadcrumbs,'TITLE'=>$title,'ID'=>$id)));
//	$out='<option value="'.(!$use_compound_list?$id:$compound_list).'">'.$breadcrumbs.$title.'</option>';
//	$out.=$_below;
	$out=form_input_list_entry(((!$use_compound_list)?strval($id):$compound_list).($ins_format?('!'.$title):''),false,$breadcrumbs.$title);
	$out->attach($_below);

	if ($use_compound_list) return array($out,$compound_list); else return $out;
}

/**
 * Get a list of maps containing all the subpages, and path information, of the specified page - and those beneath it, recursively.
 *
 * @param  array			A list of pages we've already seen (we don't repeat them in multiple list positions)
 * @param  ?AUTO_LINK	The page being at the root of our recursion (NULL: true root page)
 * @param  ?string		The breadcrumbs up to this point in the recursion (NULL: blank, as we are starting the recursion)
 * @param  ?ID_TEXT		The name of the $page_id we are currently going through (NULL: look it up). This is here for efficiency reasons, as finding children IDs to recurse to also reveals the childs title
 * @param  boolean		Whether to collect post counts with our breadcrumbs information
 * @param  boolean		Whether to make a compound list (a pair of a comma-separated list of children, and the child array)
 * @param  ?integer		The number of recursive levels to search (NULL: all)
 * @return array			A list of maps for all subcategories. Each map entry containins the fields 'id' (category ID) and 'breadcrumbs' (path to the category, including the categories own title). There is also an additional 'downloadcount' entry if stats were requested
 */
function get_cedi_page_tree(&$cedi_seen,$page_id=NULL,$breadcrumbs=NULL,$title=NULL,$do_stats=true,$use_compound_list=false,$levels=NULL)
{
	if ($levels==-1) return array();

	if (is_null($page_id)) $page_id=db_get_first_id();
	$cedi_seen[]=$page_id;

	if (is_null($breadcrumbs)) $breadcrumbs='';

	// Put our title onto our breadcrumbs
	if (is_null($title)) $title=get_translated_text($GLOBALS['SITE_DB']->query_value('seedy_pages','title',array('id'=>$page_id)));
	$breadcrumbs.=$title;

	// We'll be putting all children in this entire tree into a single list
	$children=array();
	$children[0]=array();
	$children[0]['id']=$page_id;
	$children[0]['title']=$title;
	$children[0]['breadcrumbs']=$breadcrumbs;
	$children[0]['compound_list']=strval($page_id).',';
	if ($do_stats) $children[0]['filecount']=$GLOBALS['SITE_DB']->query_value('seedy_posts','COUNT(*)',array('page_id'=>$page_id));

	// Children of this category
	$rows=$GLOBALS['SITE_DB']->query_select('seedy_children',array('*'),array('parent_id'=>$page_id),'ORDER BY title',300/*reasonable limit*/);
	$children[0]['child_count']=count($rows);
	$breadcrumbs.=' > ';
	if ($levels!==0)
	{
		foreach ($rows as $child)
		{
			if (!in_array($child['child_id'],$cedi_seen))
			{
				if (!has_category_access(get_member(),'seedy_page',strval($child['child_id']))) continue;

				if (is_null($child['title']))
				{
					$temp_rows=$GLOBALS['SITE_DB']->query_select('seedy_pages',array('title'),array('id'=>$child['child_id']),'',1);
					$child['title']=get_translated_text($temp_rows[0]['title']);

					$GLOBALS['SITE_DB']->query_update('seedy_children',array('title'=>$child['title']),array('parent_id'=>$page_id,'child_id'=>$child['child_id']));
				}

				$child_id=$child['child_id'];
				$child_title=$child['title'];
				$child_breadcrumbs=$breadcrumbs;

				$child_children=get_cedi_page_tree($cedi_seen,$child_id,$child_breadcrumbs,$child_title,$do_stats,$use_compound_list,is_null($levels)?NULL:($levels-1));
				if ($use_compound_list)
				{
					list($child_children,$_compound_list)=$child_children;
					$children[0]['compound_list'].=$_compound_list;
				}

				$children=array_merge($children,$child_children);
			}
		}
	}

	return $use_compound_list?array($children,$children[0]['compound_list']):$children;
}

/**
 * Dispatch a notification about a Wiki+ post
 *
 * @param  AUTO_LINK		The post ID
 * @param  ID_TEXT		The action type
 * @set ADD EDIT
 */
function dispatch_cedi_post_notification($post_id,$type)
{
	$page_id=$GLOBALS['SITE_DB']->query_value('seedy_posts','page_id',array('id'=>$post_id));
	$the_message=$GLOBALS['SITE_DB']->query_value('seedy_posts','the_message',array('id'=>$post_id));
	$page_name=get_translated_text($GLOBALS['SITE_DB']->query_value('seedy_pages','title',array('id'=>$page_id)));
	$_the_message=get_translated_text($the_message);

	$_view_url=build_url(array('page'=>'cedi','type'=>'misc','id'=>$page_id),get_page_zone('cedi'),NULL,false,false,true);
	$view_url=$_view_url->evaluate();
	$their_username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());

	$subject=do_lang($type.'_CEDI_POST_SUBJECT',$page_name,NULL,NULL,get_site_default_lang());
	$message_raw=do_lang($type.'_CEDI_POST_BODY',comcode_escape($their_username),comcode_escape($page_name),array(comcode_escape($view_url),$_the_message),get_site_default_lang());

	require_code('notifications');
	dispatch_notification('cedi',strval($page_id),$subject,$message_raw);
}

/**
 * Dispatch a notification about a Wiki+ page
 *
 * @param  AUTO_LINK		The page ID
 * @param  ID_TEXT		The action type
 * @set ADD EDIT
 */
function dispatch_cedi_page_notification($page_id,$type)
{
	$page_name=get_translated_text($GLOBALS['SITE_DB']->query_value('seedy_pages','title',array('id'=>$page_id)));
	$_the_message=get_translated_text($GLOBALS['SITE_DB']->query_value('seedy_pages','description',array('id'=>$page_id)));

	$_view_url=build_url(array('page'=>'cedi','type'=>'misc','id'=>$page_id),get_page_zone('cedi'),NULL,false,false,true);
	$view_url=$_view_url->evaluate();
	$their_username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());

	$subject=do_lang($type.'_CEDI_PAGE_SUBJECT',$page_name,NULL,NULL,get_site_default_lang());
	$message_raw=do_lang($type.'_CEDI_PAGE_BODY',comcode_escape($their_username),comcode_escape($page_name),array(comcode_escape($view_url),$_the_message),get_site_default_lang());

	require_code('notifications');
	dispatch_notification('cedi',strval($page_id),$subject,$message_raw);
}
