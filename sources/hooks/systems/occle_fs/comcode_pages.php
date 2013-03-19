<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_comcode_pages
 */

require_code('content_fs');

class Hook_occle_fs_comcode_pages extends content_fs_base
{
	var $folder_content_type='zone';
	var $file_content_type='comcode_page';

	/**
	 * Standard modular add function for content hooks. Adds some content with the given title and properties.
	 *
	 * @param  SHORT_TEXT	Content title
	 * @param  ID_TEXT		Parent category (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ID_TEXT		The content ID
	 */
	function _file_add($title,$category,$properties)
	{
		// TODO
	}

	/**
	 * Standard modular delete function for content hooks. Deletes the content.
	 *
	 * @param  ID_TEXT	The content ID
	 */
	function _file_delete($content_id)
	{
		require_code('zones3');
		list($zone,$page)=explode(':',$content_id,2);
		delete_ocp_page($zone,$page);
	}
}