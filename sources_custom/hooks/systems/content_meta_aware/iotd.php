<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		iotds
 */

class Hook_content_meta_aware_iotd
{

	/**
	 * Standard modular info function for content hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @param  ?ID_TEXT	The zone to link through to (NULL: autodetect).
	 * @return ?array		Map of award content-type info (NULL: disabled).
	 */
	function info($zone=NULL)
	{
		return array(
			'supports_custom_fields'=>true,

			'content_type_label'=>'iotds:IOTD',

			'connection'=>$GLOBALS['SITE_DB'],
			'table'=>'iotd',
			'id_field'=>'id',
			'id_field_numeric'=>true,
			'parent_category_field'=>NULL,
			'parent_category_meta_aware_type'=>NULL,
			'is_category'=>false,
			'is_entry'=>true,
			'category_field'=>NULL, // For category permissions
			'category_type'=>NULL, // For category permissions
			'parent_spec__table_name'=>NULL,
			'parent_spec__parent_name'=>NULL,
			'parent_spec__field_name'=>NULL,
			'category_is_string'=>true,

			'title_field'=>'i_title',
			'title_field_dereference'=>true,
			'title_field_supports_comcode'=>true,
			'description_field'=>'caption',
			'thumb_field'=>'thumb_url',

			'view_pagelink_pattern'=>'_SEARCH:iotds:view:_WILD',
			'edit_pagelink_pattern'=>'_SEARCH:cms_iotds:_ed:_WILD',
			'view_category_pagelink_pattern'=>NULL,
			'add_url'=>(function_exists('has_submit_permission') && has_submit_permission('mid',get_member(),get_ip_address(),'cms_iotds'))?(get_module_zone('cms_iotds').':cms_iotds:ad'):NULL,
			'archive_url'=>((!is_null($zone))?$zone:get_module_zone('iotds')).':iotds',

			'support_url_monikers'=>true,

			'views_field'=>'iotd_views',
			'submitter_field'=>'submitter',
			'add_time_field'=>'add_date',
			'edit_time_field'=>'edit_date',
			'date_field'=>'date_and_time', // add_date is the technical add date, but date_and_time is when it went live
			'validated_field'=>NULL,

			'seo_type_code'=>NULL,

			'feedback_type_code'=>'iotds',

			'permissions_type_code'=>NULL, // NULL if has no permissions

			'search_hook'=>'iotd',

			'addon_name'=>'iotds',

			'cms_page'=>'cms_iotds',
			'module'=>'iotd',

			'occle_filesystem_hook'=>'iotds',
			'occle_filesystem__is_folder'=>false,

			'rss_hook'=>'iotds',

			'actionlog_regexp'=>'\w+_IOTD',
		);
	}

	/**
	 * Standard modular run function for content hooks. Renders a content box for an award/randomisation.
	 *
	 * @param  array		The database row for the content
	 * @param  ID_TEXT	The zone to display in
	 * @param  boolean	Whether to include context (i.e. say WHAT this is, not just show the actual content)
	 * @param  boolean	Whether to include breadcrumbs (if there are any)
	 * @param  ?ID_TEXT	Virtual root to use (NULL: none)
	 * @param  boolean	Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
	 * @param  ID_TEXT	Overridden GUID to send to templates (blank: none)
	 * @return tempcode	Results
	 */
	function run($row,$zone,$give_context=true,$include_breadcrumbs=true,$root=NULL,$attach_to_url_filter=false,$guid='')
	{
		require_code('iotds');

		return render_iotd_box($row,$zone,false,$give_context,$guid);
	}

}
