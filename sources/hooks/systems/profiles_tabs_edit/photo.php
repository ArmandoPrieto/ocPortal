<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ocf_member_photos
 */

class Hook_Profiles_Tabs_Edit_photo
{

	/**
	 * Find whether this hook is active.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return boolean		Whether this hook is active
	 */
	function is_active($member_id_of,$member_id_viewing)
	{
		return (($member_id_of==$member_id_viewing) || (has_specific_permission($member_id_viewing,'assume_any_member')) || (has_specific_permission($member_id_viewing,'member_maintenance')));
	}

	/**
	 * Standard modular render function for profile tabs edit hooks.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return array			A tuple: The tab title, the tab body text (may be blank), the tab fields, extra Javascript (may be blank) the suggested tab order
	 */
	function render_tab($member_id_of,$member_id_viewing)
	{
		$title=do_lang_tempcode('PHOTO');

		$order=30;

		// Actualiser
		if (count($_POST)!=0)
		{
			require_code('ocf_members_action');
			require_code('ocf_members_action2');
			ocf_member_choose_photo('photo_url','photo_file',$member_id_of);

			attach_message(do_lang_tempcode('SUCCESS_SAVE'),'inform');
		}

		$photo_url=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of,'m_photo_url');
		$thumb_url=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of,'m_photo_thumb_url');

		// UI fields
		$fields=new ocp_tempcode();
		require_code('form_templates');
		$fields->attach(form_input_upload(do_lang_tempcode('UPLOAD'),do_lang_tempcode('DESCRIPTION_UPLOAD'),'photo_file',false,NULL,NULL,true,str_replace(' ','',get_option('valid_images'))));
		$fields->attach(form_input_line(do_lang_tempcode('ALT_FIELD',do_lang_tempcode('URL')),do_lang_tempcode('DESCRIPTION_ALTERNATE_URL'),'photo_url',$photo_url,false));
		if (get_option('is_on_gd')=='0')
		{
			$thumb_width=get_option('thumb_width');
			$fields->attach(form_input_upload(do_lang_tempcode('THUMBNAIL'),do_lang_tempcode('DESCRIPTION_THUMBNAIL',escape_html($thumb_width)),'photo_file2',false,NULL,NULL,true,str_replace(' ','',get_option('valid_images'))));
			$fields->attach(form_input_line(do_lang_tempcode('ALT_FIELD',do_lang_tempcode('URL')),do_lang_tempcode('DESCRIPTION_ALTERNATE_URL'),'photo_thumb_url',$thumb_url,false));
		}
		$hidden=new ocp_tempcode();
		handle_max_file_size($hidden,'image');

		$text=new ocp_tempcode();
		require_code('images');
		$max=floatval(get_max_image_size())/floatval(1024*1024);
		if ($max<3.0)
		{
			require_code('files2');
			$config_url=get_upload_limit_config_url();
			$text->attach(paragraph(do_lang_tempcode(is_null($config_url)?'MAXIMUM_UPLOAD':'MAXIMUM_UPLOAD_STAFF',escape_html(($max>10.0)?integer_format(intval($max)):float_format($max)),escape_html($config_url))));
		}

		$text=do_template('OCF_EDIT_PHOTO_TAB',array('TEXT'=>$text,'MEMBER_ID'=>strval($member_id_of),'USERNAME'=>$GLOBALS['FORUM_DRIVER']->get_username($member_id_of),'PHOTO'=>$GLOBALS['FORUM_DRIVER']->get_member_photo_url($member_id_of)));

		$javascript='standardAlternateFields(\'photo_file\',\'photo_url\'); standardAlternateFields(\'photo_file2\',\'photo_thumb_url\');';

		return array($title,$fields,$text,$javascript,$order);
	}

}


