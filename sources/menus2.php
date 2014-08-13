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
 * @package		core_menus
 */

/**
 * Standard code module initialisation function.
 */
function init__menus2()
{
	global $ADD_MENU_COUNTER;
	$ADD_MENU_COUNTER=10;
}

/**
 * Move a menu branch.
 */
function menu_management_script()
{
	$id=get_param_integer('id');
	$to_menu=get_param('menu');
	$changes=array('i_menu'=>$to_menu);

	$rows=$GLOBALS['SITE_DB']->query_select('menu_items',array('*'),array('id'=>$id),'',1);
	if (array_key_exists(0,$rows)) $row=$rows[0]; else $row=NULL;

	$test=false;

	foreach (array_keys($test?$_GET:$_POST) as $key)
	{
		$val=$test?get_param($key):post_param($key);
		$key=preg_replace('#\_\d+$#','',$key);
		if (($key=='caption') || ($key=='caption_long'))
		{
			if (is_null($row))
			{
				$changes+=insert_lang('i_'.$key,$val,2);
			} else
			{
				$changes+=lang_remap('i_'.$key,$row['i_'.$key],$val);
			}
		} elseif (($key=='url') || ($key=='theme_img_code'))
		{
			$changes['i_'.$key]=$val;
		} elseif ($key=='match_tags')
		{
			$changes['i_page_only']=$val;
		}
	}
	$changes['i_order']=post_param_integer('order_'.strval($id),0);
	$changes['i_new_window']=post_param_integer('new_window_'.strval($id),0);
	$changes['i_check_permissions']=post_param_integer('check_perms_'.strval($id),0);
	$changes['i_expanded']=0;
	$changes['i_parent']=NULL;

	if (is_null($row))
	{
		$GLOBALS['SITE_DB']->query_insert('menu_items',$changes);
	} else
	{
		$GLOBALS['SITE_DB']->query_update('menu_items',$changes,array('id'=>$id),'',1);
	}
}

/**
 * Add a menu item, without giving tedious/unnecessary detail.
 *
 * @param  SHORT_TEXT	The name of the menu to add the item to.
 * @param  ?mixed			The menu item ID of the parent branch of the menu item (AUTO_LINK) / the URL of something else on the same menu (URLPATH) (NULL: is on root).
 * @param  SHORT_TEXT	The caption.
 * @param  SHORT_TEXT	The URL (in entry point form).
 * @param  BINARY			Whether it is an expanded branch.
 * @param  BINARY			Whether people who may not view the entry point do not see the link.
 * @param  boolean		Whether the caption is a language code.
 * @param  SHORT_TEXT	The tooltip (blank: none).
 * @param  BINARY			Whether the link will open in a new window.
 * @param  ID_TEXT		The theme image code.
 * @param  ?integer		Order to use (NULL: automatic, after the ones that have it specified).
 * @return AUTO_LINK		The ID of the newly added menu item.
 */
function add_menu_item_simple($menu,$parent,$caption,$url='',$expanded=0,$check_permissions=0,$dereference_caption=true,$caption_long='',$new_window=0,$theme_image_code='',$order=NULL)
{
	global $ADD_MENU_COUNTER;

	$id=$GLOBALS['SITE_DB']->query_value_null_ok('menu_items','id',array('i_url'=>$url,'i_menu'=>$menu));
	if (!is_null($id)) return $id; // Already exists
	if (is_string($parent))
	{
		$parent=$GLOBALS['SITE_DB']->query_value_null_ok('menu_items','i_parent',array('i_url'=>$parent));
	}

	$_caption=(strpos($caption,':')===false)?do_lang($caption,NULL,NULL,NULL,NULL,false):NULL;
	if (is_null($_caption)) $_caption=$caption;
	$id=add_menu_item($menu,$ADD_MENU_COUNTER,$parent,$dereference_caption?$_caption:$caption,$url,$check_permissions,'',$expanded,$new_window,$caption_long,$theme_image_code);

	$ADD_MENU_COUNTER++;

	return $id;
}

/**
 * Delete a menu item, without giving tedious/unnecessary detail.
 *
 * @param  SHORT_TEXT  The URL (in entry point form).
 */
function delete_menu_item_simple($url)
{
	$GLOBALS['SITE_DB']->query_delete('menu_items',array('i_url'=>$url));

	$_id=$GLOBALS['SITE_DB']->query_select('menu_items',array('id'),array($GLOBALS['SITE_DB']->translate_field_ref('i_caption')=>$url));
	foreach ($_id as $id)
		$GLOBALS['SITE_DB']->query_delete('menu_items',array('i_caption'=>$id['id']));
}

/**
 * Add a menu item.
 *
 * @param  SHORT_TEXT	The name of the menu to add the item to.
 * @param  integer		The relative order of this item on the menu.
 * @param  ?AUTO_LINK	The menu item ID of the parent branch of the menu item (NULL: is on root).
 * @param  SHORT_TEXT	The caption.
 * @param  SHORT_TEXT	The URL (in entry point form).
 * @param  BINARY			Whether people who may not view the entry point do not see the link.
 * @param  SHORT_TEXT	Match-keys to identify what pages the item is shown on.
 * @param  BINARY			Whether it is an expanded branch.
 * @param  BINARY			Whether the link will open in a new window.
 * @param  SHORT_TEXT	The tooltip (blank: none).
 * @param  ID_TEXT		The theme image code.
 * @return AUTO_LINK		The ID of the newly added menu item.
 */
function add_menu_item($menu,$order,$parent,$caption,$url,$check_permissions,$page_only,$expanded,$new_window,$caption_long,$theme_image_code='')
{
	$map=array(
		'i_menu'=>$menu,
		'i_order'=>$order,
		'i_parent'=>$parent,
		'i_url'=>$url,
		'i_check_permissions'=>$check_permissions,
		'i_page_only'=>$page_only,
		'i_expanded'=>$expanded,
		'i_new_window'=>$new_window,
		'i_theme_img_code'=>$theme_image_code,
	);
	$map+=insert_lang_comcode('i_caption',$caption,1);
	$map+=insert_lang_comcode('i_caption_long',$caption_long,1);
	$id=$GLOBALS['SITE_DB']->query_insert('menu_items',$map,true);

	log_it('ADD_MENU_ITEM',strval($id),$caption);

	return $id;
}

/**
 * Edit a menu item.
 *
 * @param  AUTO_LINK		The ID of the menu item to edit.
 * @param  SHORT_TEXT	The name of the menu to add the item to.
 * @param  integer		The relative order of this item on the menu.
 * @param  ?AUTO_LINK	The menu item ID of the parent branch of the menu item (NULL: is on root).
 * @param  SHORT_TEXT	The caption.
 * @param  SHORT_TEXT	The URL (in entry point form).
 * @param  BINARY			Whether people who may not view the entry point do not see the link.
 * @param  SHORT_TEXT	Match-keys to identify what pages the item is shown on.
 * @param  BINARY			Whether it is an expanded branch.
 * @param  BINARY			Whether the link will open in a new window.
 * @param  SHORT_TEXT	The tooltip (blank: none).
 * @param  ID_TEXT		The theme image code.
 */
function edit_menu_item($id,$menu,$order,$parent,$caption,$url,$check_permissions,$page_only,$expanded,$new_window,$caption_long,$theme_image_code)
{
	$_caption=$GLOBALS['SITE_DB']->query_value('menu_items','i_caption',array('id'=>$id));
	$_caption_long=$GLOBALS['SITE_DB']->query_value('menu_items','i_caption_long',array('id'=>$id));

	$map=array(
		'i_menu'=>$menu,
		'i_order'=>$order,
		'i_parent'=>$parent,
		'i_url'=>$url,
		'i_check_permissions'=>$check_permissions,
		'i_page_only'=>$page_only,
		'i_expanded'=>$expanded,
		'i_new_window'=>$new_window,
	);
	$map+=lang_remap_comcode('i_caption',$_caption,$caption);
	$map+=lang_remap_comcode('i_caption_long',$_caption_long,$caption_long);
	$GLOBALS['SITE_DB']->query_update('menu_items',$map,array('id'=>$id),'',1);

	log_it('EDIT_MENU_ITEM',strval($id),$caption);
}

/**
 * Delete a menu item.
 *
 * @param  AUTO_LINK		The ID of the menu item to delete.
 */
function delete_menu_item($id)
{
	$_caption=$GLOBALS['SITE_DB']->query_value('menu_items','i_caption',array('id'=>$id));
	$_caption_long=$GLOBALS['SITE_DB']->query_value('menu_items','i_caption_long',array('id'=>$id));
	$GLOBALS['SITE_DB']->query_delete('menu_items',array('id'=>$id),'',1);
	$caption=get_translated_text($_caption);
	delete_lang($_caption);
	delete_lang($_caption_long);
	log_it('DELETE_MENU_ITEM',strval($id),$caption);
}


