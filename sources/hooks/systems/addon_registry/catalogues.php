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
 * @package		catalogues
 */

class Hook_addon_registry_catalogues
{
	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Describe your own custom data record types (by choosing and configuring fields) and populate with records. Supports tree structures, and most standard ocPortal features (e.g. ratings).';
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(),
			'recommends'=>array(),
			'conflicts_with'=>array()
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'sources/hooks/systems/snippets/exists_catalogue.php',
			'sources/hooks/systems/module_permissions/catalogues_catalogue.php',
			'sources/hooks/systems/module_permissions/catalogues_category.php',
			'sources/hooks/systems/rss/catalogues.php',
			'sources/hooks/systems/do_next_menus/catalogues.php',
			'sources/hooks/systems/trackback/catalogues.php',
			'sources/hooks/modules/search/catalogue_categories.php',
			'sources/hooks/modules/search/catalogue_entries.php',
			'sources/hooks/systems/ajax_tree/choose_catalogue_category.php',
			'sources/hooks/systems/ajax_tree/choose_catalogue_entry.php',
			'sources/hooks/systems/awards/catalogue.php',
			'sources/hooks/systems/awards/catalogue_category.php',
			'sources/hooks/systems/awards/catalogue_entry.php',
			'sources/hooks/systems/cron/catalogue_entry_timeouts.php',
			'sources/hooks/systems/cron/catalogue_view_reports.php',
			'sources/hooks/systems/meta/catalogue_category.php',
			'sources/hooks/systems/meta/catalogue_entry.php',
			'JAVASCRIPT_CATALOGUES.tpl',
			'sources/hooks/modules/admin_import_types/catalogues.php',
			'sources/hooks/systems/content_meta_aware/catalogue_category.php',
			'sources/hooks/systems/content_meta_aware/catalogue_entry.php',
			'sources/hooks/systems/addon_registry/catalogues.php',
			'CATALOGUE_ADDING_SCREEN.tpl',
			'CATALOGUE_EDITING_SCREEN.tpl',
			'CATALOGUE_CATEGORIES_LIST_LINE.tpl',
			'CATALOGUE_DEFAULT_CATEGORY_EMBED.tpl',
			'CATALOGUE_DEFAULT_CATEGORY_SCREEN.tpl',
			'CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP.tpl',
			'CATALOGUE_DEFAULT_FIELDMAP_ENTRY_FIELD.tpl',
			'CATALOGUE_DEFAULT_GRID_ENTRY_WRAP.tpl',
			'CATALOGUE_DEFAULT_GRID_ENTRY_FIELD.tpl',
			'CATALOGUE_DEFAULT_ENTRY_SCREEN.tpl',
			'CATALOGUE_DEFAULT_TITLELIST_ENTRY.tpl',
			'CATALOGUE_DEFAULT_TITLELIST_WRAP.tpl',
			'CATALOGUE_ENTRIES_LIST_LINE.tpl',
			'SEARCH_RESULT_CATALOGUE_ENTRIES.tpl',
			'CATALOGUE_DEFAULT_TABULAR_ENTRY_WRAP.tpl',
			'CATALOGUE_DEFAULT_TABULAR_ENTRY_FIELD.tpl',
			'CATALOGUE_DEFAULT_TABULAR_HEADCELL.tpl',
			'CATALOGUE_DEFAULT_TABULAR_WRAP.tpl',
			'CATALOGUE_links_TABULAR_ENTRY_WRAP.tpl',
			'CATALOGUE_links_TABULAR_ENTRY_FIELD.tpl',
			'CATALOGUE_links_TABULAR_HEADCELL.tpl',
			'CATALOGUE_links_TABULAR_WRAP.tpl',
			'CATALOGUE_CATEGORY_HEADING.tpl',
			'uploads/catalogues/index.html',
			'uploads/catalogues/.htaccess',
			'themes/default/images/bigicons/catalogues.png',
			'themes/default/images/bigicons/add_one_catalogue.png',
			'themes/default/images/bigicons/add_to_catalogue.png',
			'themes/default/images/bigicons/edit_one_catalogue.png',
			'themes/default/images/bigicons/edit_this_catalogue.png',
			'themes/default/images/bigicons/of_catalogues.png',
			'themes/default/images/pagepics/catalogues.png',
			'cms/pages/modules/cms_catalogues.php',
			'lang/EN/catalogues.ini',
			'site/pages/modules/catalogues.php',
			'sources/hooks/systems/notifications/catalogue_view_reports.php',
			'sources/hooks/systems/notifications/catalogue_entry.php',
			'sources/catalogues.php',
			'sources/hooks/modules/admin_import/catalogues.php',
			'sources/catalogues2.php',
			'sources/hooks/modules/admin_newsletter/catalogues.php',
			'sources/hooks/modules/admin_setupwizard/catalogues.php',
			'sources/hooks/modules/admin_unvalidated/catalogue_entry.php',
			'sources/hooks/systems/attachments/catalogue_entry.php',
			'sources/blocks/main_cc_embed.php',
			'catalogues.css',
			'site/catalogue_file.php',
			'sources/hooks/systems/symbols/CATALOGUE_ENTRY_BACKREFS.php',
			'sources/hooks/systems/symbols/CATALOGUE_ENTRY_FIELD_VALUE.php',
			'sources/hooks/systems/symbols/CATALOGUE_ENTRY_FIELD_VALUE_PLAIN.php',
			'sources/blocks/main_contact_catalogues.php',
			'sources/hooks/systems/symbols/CATALOGUE_ENTRY_ALL_FIELD_VALUES.php'
		);
	}


	/**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array			The mapping
	 */
	function tpl_previews()
	{
		return array(
			'CATALOGUE_ADDING_SCREEN.tpl'=>'administrative__catalogue_adding_screen',
			'CATALOGUE_EDITING_SCREEN.tpl'=>'administrative__catalogue_editing_screen',
			'CATALOGUE_ENTRIES_LIST_LINE.tpl'=>'catalogue_entries_list_line',
			'CATALOGUE_CATEGORIES_LIST_LINE.tpl'=>'catalogue_categories_list_line',
			'SEARCH_RESULT_CATALOGUE_ENTRIES.tpl'=>'search_result_catalogue_entries',
			'CATALOGUE_DEFAULT_CATEGORY_EMBED.tpl'=>'fieldmap_catalogue_embed_screen',

			'CATALOGUE_CATEGORY_HEADING.tpl'=>'fieldmap_category_screen',
			'CATALOGUE_DEFAULT_CATEGORY_SCREEN.tpl'=>'fieldmap_category_screen',

			'CATALOGUE_DEFAULT_TABULAR_WRAP.tpl'=>'tabular_category_screen',
			'CATALOGUE_DEFAULT_TABULAR_HEADCELL.tpl'=>'tabular_category_screen',
			'CATALOGUE_DEFAULT_TABULAR_ENTRY_WRAP.tpl'=>'tabular_category_screen',
			'CATALOGUE_DEFAULT_TABULAR_ENTRY_FIELD.tpl'=>'tabular_category_screen',

			'CATALOGUE_DEFAULT_GRID_ENTRY_WRAP.tpl'=>'grid_category_screen',
			'CATALOGUE_DEFAULT_GRID_ENTRY_FIELD.tpl'=>'grid_category_screen',

			'CATALOGUE_links_TABULAR_WRAP.tpl'=>'tabular_category_screen__links',
			'CATALOGUE_links_TABULAR_HEADCELL.tpl'=>'tabular_category_screen__links',
			'CATALOGUE_links_TABULAR_ENTRY_WRAP.tpl'=>'tabular_category_screen__links',
			'CATALOGUE_links_TABULAR_ENTRY_FIELD.tpl'=>'tabular_category_screen__links',

			'CATALOGUE_DEFAULT_TITLELIST_ENTRY.tpl'=>'list_category_screen',
			'CATALOGUE_DEFAULT_TITLELIST_WRAP.tpl'=>'list_category_screen',

			'CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP.tpl'=>'entry_screen',
			'CATALOGUE_DEFAULT_FIELDMAP_ENTRY_FIELD.tpl'=>'entry_screen',

			'CATALOGUE_DEFAULT_ENTRY_SCREEN.tpl'=>'entry_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__grid_category_screen()
	{
		$subcategories=new ocp_tempcode();
		$subcategories->attach(do_lorem_template('SIMPLE_PREVIEW_BOX', array(
			'TITLE'=>lorem_phrase(),
			'SUMMARY'=>lorem_paragraph_html(),
			'URL'=>placeholder_url(),
		)));
		$tags=do_lorem_template('TAGS', array(
			'TAGS'=>placeholder_array(),
			'TYPE'=>NULL,
			'LINK_FULLSCOPE'=>placeholder_url(),
			'TAG'=>lorem_word()
		));

		$entries=new ocp_tempcode();
		$fields=new ocp_tempcode();
		foreach (placeholder_array() as $v)
		{
			$fields->attach(do_lorem_template('CATALOGUE_DEFAULT_GRID_ENTRY_FIELD', array(
				'ENTRYID'=>placeholder_random_id(),
				'CATALOGUE'=>lorem_phrase(),
				'TYPE'=>lorem_word(),
				'FIELD'=>lorem_word(),
				'FIELDID'=>placeholder_random_id(),
				'_FIELDID'=>placeholder_id(),
				'FIELDTYPE'=>lorem_word(),
				'VALUE_PLAIN'=>lorem_phrase(),
				'VALUE'=>lorem_phrase()
			)));
		}
		$content=do_lorem_template('CATALOGUE_DEFAULT_GRID_ENTRY_WRAP', array(
			'FIELDS'=>$fields,
			'VIEW_URL'=>placeholder_url(),
			'FIELD_0'=>lorem_word()
		));
		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN', array(
				'ID'=>placeholder_id(),
				'ADD_DATE_RAW'=>placeholder_time(),
				'TITLE'=>lorem_title(),
				'_TITLE'=>lorem_phrase(),
				'TAGS'=>$tags,
				'CATALOGUE'=>lorem_word_2(),
				'ADD_ENTRY_URL'=>placeholder_url(),
				'ADD_CAT_URL'=>placeholder_url(),
				'EDIT_CAT_URL'=>placeholder_url(),
				'EDIT_CATALOGUE_URL'=>placeholder_url(),
				'ENTRIES'=>$entries,
				'SUBCATEGORIES'=>$subcategories,
				'DESCRIPTION'=>lorem_sentence(),
				'CART_LINK'=>placeholder_link(),
				'TREE'=>lorem_phrase(),
				'DISPLAY_TYPE'=>'0',
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__fieldmap_category_screen()
	{
		$subcategories=new ocp_tempcode();
		$tags=do_lorem_template('TAGS', array(
			'TAGS'=>placeholder_array(),
			'TYPE'=>NULL,
			'LINK_FULLSCOPE'=>placeholder_url(),
			'TAG'=>lorem_word()
		));

		$entries=new ocp_tempcode();
		$fields=new ocp_tempcode();
		foreach (placeholder_array() as $v)
		{
			$fields->attach(do_lorem_template('CATALOGUE_DEFAULT_FIELDMAP_ENTRY_FIELD', array(
				'ENTRYID'=>placeholder_random_id(),
				'CATALOGUE'=>lorem_phrase(),
				'TYPE'=>lorem_word(),
				'FIELD'=>lorem_word(),
				'FIELDID'=>placeholder_random_id(),
				'_FIELDID'=>placeholder_id(),
				'FIELDTYPE'=>lorem_word(),
				'VALUE_PLAIN'=>lorem_phrase(),
				'VALUE'=>lorem_phrase()
			)));
		}
		$content=do_lorem_template('CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP', array(
			'ID'=>placeholder_id(),
			'FIELDS'=>$fields,
			'VIEW_URL'=>placeholder_url(),
			'FIELD_0'=>lorem_word(),
			'GIVE_CONTEXT'=>false,
		));
		foreach (placeholder_array(2) as $v)
		{
			$entries->attach(do_lorem_template('CATALOGUE_CATEGORY_HEADING', array(
				'LETTER'=>lorem_phrase(),
				'ENTRIES'=>$content
			)));
		}
		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN', array(
				'ID'=>placeholder_id(),
				'ADD_DATE_RAW'=>placeholder_time(),
				'TITLE'=>lorem_title(),
				'_TITLE'=>lorem_phrase(),
				'TAGS'=>$tags,
				'CATALOGUE'=>lorem_word_2(),
				'ADD_ENTRY_URL'=>placeholder_url(),
				'ADD_CAT_URL'=>placeholder_url(),
				'EDIT_CAT_URL'=>placeholder_url(),
				'EDIT_CATALOGUE_URL'=>placeholder_url(),
				'ENTRIES'=>$entries,
				'SUBCATEGORIES'=>$subcategories,
				'DESCRIPTION'=>lorem_sentence(),
				'CART_LINK'=>placeholder_link(),
				'TREE'=>lorem_phrase(),
				'DISPLAY_TYPE'=>'0',
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__list_category_screen()
	{
		$type='default';
		$content=new ocp_tempcode();
		foreach (placeholder_array() as $v)
		{
			$content->attach(do_lorem_template('CATALOGUE_DEFAULT_TITLELIST_ENTRY', array(
				'VIEW_URL'=>placeholder_url(),
				'ID'=>placeholder_url(),
				'FIELD_0'=>lorem_word_2(),
				'FIELD_0_PLAIN'=>lorem_word()
			)));
		}
		$entries=do_lorem_template('CATALOGUE_DEFAULT_TITLELIST_WRAP', array(
			'CATALOGUE'=>lorem_word(),
			'CONTENT'=>$content
		));

		$tags=do_lorem_template('TAGS', array(
			'TAGS'=>placeholder_array(),
			'TYPE'=>NULL,
			'LINK_FULLSCOPE'=>placeholder_url(),
			'TAG'=>lorem_word()
		));
		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN', array(
				'ID'=>placeholder_id(),
				'ADD_DATE_RAW'=>placeholder_time(),
				'TITLE'=>lorem_title(),
				'_TITLE'=>lorem_phrase(),
				'TAGS'=>$tags,
				'CATALOGUE'=>lorem_word_2(),
				'ADD_ENTRY_URL'=>placeholder_url(),
				'ADD_CAT_URL'=>placeholder_url(),
				'EDIT_CAT_URL'=>placeholder_url(),
				'EDIT_CATALOGUE_URL'=>placeholder_url(),
				'ENTRIES'=>$entries,
				'SUBCATEGORIES'=>'',
				'DESCRIPTION'=>lorem_sentence(),
				'CART_LINK'=>placeholder_link(),
				'TREE'=>lorem_phrase(),
				'DISPLAY_TYPE'=>'0',
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__tabular_category_screen__links()
	{
		$subcategories=new ocp_tempcode();
		$tags=do_lorem_template('TAGS', array(
			'TAGS'=>placeholder_array(),
			'TYPE'=>NULL,
			'LINK_FULLSCOPE'=>placeholder_url(),
			'TAG'=>lorem_word()
		));

		$row=new ocp_tempcode();
		$entry_fields=new ocp_tempcode();
		$head=new ocp_tempcode();
		foreach (placeholder_array() as $v)
		{
			$head->attach(do_lorem_template('CATALOGUE_links_TABULAR_HEADCELL', array(
				'SORT_ASC_SELECTED'=>TRUE,
				'SORT_DESC_SELECTED'=>FALSE,
				'SORT_URL_ASC'=>placeholder_url(),
				'SORT_URL_DESC'=>placeholder_url(),
				'CATALOGUE'=>lorem_word(),
				'FIELDID'=>placeholder_random_id(),
				'_FIELDID'=>placeholder_random_id(),
				'FIELD'=>$v,
				'FIELDTYPE'=>'text'
			)));
			$entry_fields->attach(do_lorem_template('CATALOGUE_links_TABULAR_ENTRY_FIELD', array(
				'FIELDID'=>placeholder_random_id(),
				'ENTRYID'=>placeholder_random_id(),
				'VALUE'=>lorem_phrase()
			)));
		}
		$row->attach(do_lorem_template('CATALOGUE_links_TABULAR_ENTRY_WRAP', array(
			'FIELDS_TABULAR'=>$entry_fields,
			'VIEW_URL'=>placeholder_url(),
			'EDIT_URL'=>placeholder_url(),
			'FIELD_1_PLAIN'=>lorem_phrase()
		)));
		$content=do_lorem_template('CATALOGUE_links_TABULAR_WRAP', array(
			'CATALOGUE'=>lorem_word(),
			'HEAD'=>$head,
			'CONTENT'=>$row,
			'FIELD_COUNT'=>"3"
		));
		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN', array(
				'ID'=>placeholder_id(),
				'ADD_DATE_RAW'=>placeholder_time(),
				'TITLE'=>lorem_title(),
				'_TITLE'=>lorem_phrase(),
				'TAGS'=>$tags,
				'CATALOGUE'=>lorem_word_2(),
				'ADD_ENTRY_URL'=>placeholder_url(),
				'ADD_CAT_URL'=>placeholder_url(),
				'EDIT_CAT_URL'=>placeholder_url(),
				'EDIT_CATALOGUE_URL'=>placeholder_url(),
				'ENTRIES'=>$content,
				'SUBCATEGORIES'=>$subcategories,
				'DESCRIPTION'=>lorem_sentence(),
				'CART_LINK'=>placeholder_link(),
				'TREE'=>lorem_phrase(),
				'DISPLAY_TYPE'=>'0',
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__tabular_category_screen()
	{
		$subcategories=new ocp_tempcode();
		$tags=do_lorem_template('TAGS', array(
			'TAGS'=>placeholder_array(),
			'TYPE'=>NULL,
			'LINK_FULLSCOPE'=>placeholder_url(),
			'TAG'=>lorem_word()
		));

		$entries=new ocp_tempcode();
		$head=do_lorem_template('CATALOGUE_DEFAULT_TABULAR_HEADCELL', array(
			'SORT_ASC_SELECTED'=>TRUE,
			'SORT_DESC_SELECTED'=>FALSE,
			'SORT_URL_ASC'=>placeholder_url(),
			'SORT_URL_DESC'=>placeholder_url(),
			'CATALOGUE'=>lorem_word(),
			'FIELDID'=>placeholder_id(),
			'_FIELDID'=>placeholder_id(),
			'FIELD'=>lorem_word(),
			'FIELDTYPE'=>'text'
		));
		$fields=new ocp_tempcode();
		$fields->attach(do_lorem_template('CATALOGUE_DEFAULT_TABULAR_ENTRY_FIELD', array(
			'FIELDID'=>placeholder_id(),
			'ENTRYID'=>placeholder_id(),
			'VALUE'=>lorem_phrase()
		)));
		$entries->attach(do_lorem_template('CATALOGUE_DEFAULT_TABULAR_ENTRY_WRAP', array(
			'FIELDS_TABULAR'=>$fields,
			'EDIT_URL'=>placeholder_url(),
			'VIEW_URL'=>placeholder_url()
		)));
		$content=do_lorem_template('CATALOGUE_DEFAULT_TABULAR_WRAP', array(
			'CATALOGUE'=>lorem_word(),
			'HEAD'=>$head,
			'CONTENT'=>$entries,
			'FIELD_COUNT'=>"1"
		));
		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN', array(
				'ID'=>placeholder_id(),
				'ADD_DATE_RAW'=>placeholder_time(),
				'TITLE'=>lorem_title(),
				'_TITLE'=>lorem_phrase(),
				'TAGS'=>$tags,
				'CATALOGUE'=>lorem_word_2(),
				'ADD_ENTRY_URL'=>placeholder_url(),
				'ADD_CAT_URL'=>placeholder_url(),
				'EDIT_CAT_URL'=>placeholder_url(),
				'EDIT_CATALOGUE_URL'=>placeholder_url(),
				'ENTRIES'=>$content,
				'SUBCATEGORIES'=>$subcategories,
				'DESCRIPTION'=>lorem_sentence(),
				'CART_LINK'=>placeholder_link(),
				'TREE'=>lorem_phrase(),
				'DISPLAY_TYPE'=>'0',
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__entry_screen()
	{
		$tags=do_lorem_template('TAGS', array(
			'TAGS'=>placeholder_array(),
			'TYPE'=>NULL,
			'LINK_FULLSCOPE'=>placeholder_url(),
			'TAG'=>lorem_word()
		));

		$fields=new ocp_tempcode();
		foreach (placeholder_array() as $v)
			$fields->attach(do_lorem_template('CATALOGUE_DEFAULT_FIELDMAP_ENTRY_FIELD', array(
				'ENTRYID'=>placeholder_id(),
				'CATALOGUE'=>lorem_phrase(),
				'TYPE'=>lorem_word(),
				'FIELD'=>lorem_word(),
				'FIELDID'=>placeholder_id(),
				'_FIELDID'=>placeholder_id(),
				'FIELDTYPE'=>lorem_word(),
				'VALUE_PLAIN'=>lorem_phrase(),
				'VALUE'=>lorem_phrase()
			)));

		$entry=do_lorem_template('CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP', array(
			'ID'=>placeholder_id(),
			'FIELDS'=>$fields,
			'VIEW_URL'=>placeholder_url(),
			'FIELD_0'=>lorem_word(),
			'ENTRY_SCREEN'=>true,
			'GIVE_CONTEXT'=>false,
		));

		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_ENTRY_SCREEN', array(
				'TITLE'=>lorem_title(),
				'WARNINGS'=>'',
				'ENTRY'=>$entry,
				'EDIT_URL'=>placeholder_url(),
				'_EDIT_LINK'=>placeholder_link(),
				'TRACKBACK_DETAILS'=>lorem_phrase(),
				'RATING_DETAILS'=>lorem_phrase(),
				'COMMENT_DETAILS'=>lorem_phrase(),
				'ADD_DATE'=>placeholder_time(),
				'ADD_DATE_RAW'=>placeholder_date_raw(),
				'EDIT_DATE_RAW'=>placeholder_date_raw(),
				'VIEWS'=>placeholder_number(),
				'TAGS'=>$tags,
				'SUBMITTER'=>placeholder_id(),
				'FIELD_1'=>lorem_word()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__catalogue_adding_screen()
	{
		require_javascript('javascript_validation');

		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_ADDING_SCREEN', array(
				'HIDDEN'=>'',
				'TITLE'=>lorem_title(),
				'TEXT'=>lorem_sentence_html(),
				'URL'=>placeholder_url(),
				'FIELDS'=>placeholder_fields(),
				'FIELDS_NEW'=>placeholder_form(),
				'SUBMIT_NAME'=>lorem_word()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__catalogue_editing_screen()
	{
		require_javascript('javascript_validation');

		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_EDITING_SCREEN', array(
				'HIDDEN'=>'',
				'TITLE'=>lorem_title(),
				'TEXT'=>lorem_sentence_html(),
				'URL'=>placeholder_url(),
				'FIELDS'=>placeholder_fields(),
				'FIELDS_EXISTING'=>placeholder_form(),
				'FIELDS_NEW'=>placeholder_form(),
				'SUBMIT_NAME'=>lorem_word()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__catalogue_entries_list_line()
	{
		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_ENTRIES_LIST_LINE', array(
				'BREADCRUMBS'=>lorem_phrase(),
				'NAME'=>lorem_word()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__catalogue_categories_list_line()
	{
		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_CATEGORIES_LIST_LINE', array(
				'BREADCRUMBS'=>lorem_phrase(),
				'COUNT'=>placeholder_number()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__search_result_catalogue_entries()
	{
		return array(
			lorem_globalise(do_lorem_template('SEARCH_RESULT_CATALOGUE_ENTRIES', array(
				'BUILDUP'=>lorem_phrase(),
				'NAME'=>lorem_word_html(),
				'TITLE'=>lorem_word()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__fieldmap_catalogue_embed_screen()
	{
		$entries=new ocp_tempcode();

		$entries->attach(do_lorem_template('CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP', array(
			'ID'=>placeholder_id(),
			'FIELDS'=>placeholder_fields(),
			'VIEW_URL'=>placeholder_url(),
			'FIELD_0'=>lorem_word(),
			'GIVE_CONTEXT'=>false,
		)));

		return array(
			lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_EMBED', array(
				'ENTRIES'=>$entries,
				'DISPLAY_TYPE'=>'',
				'ROOT'=>placeholder_id(),
				'BLOCK_PARAMS'=>'',
			)), NULL, '', true)
		);
	}
}
