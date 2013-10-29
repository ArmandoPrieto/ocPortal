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
 * @package		galleries
 */

class Hook_block_ui_renderers_galleries
{

	/**
	 * See if a particular block parameter's UI input can be rendered by this.
	 *
	 * @param  ID_TEXT		The block
	 * @param  ID_TEXT		The parameter of the block
	 * @param  boolean		Whether there is a default value for the field, due to this being an edit
	 * @param  string			Default value for field
	 * @param  tempcode		Field description
	 * @return ?tempcode		Rendered field (NULL: not handled).
	 */
	function render_block_ui($block,$parameter,$has_default,$default,$description)
	{
		if ((($default=='') || (preg_match('#^\w+$#',$default)!=0)) && ($parameter=='param') && (in_array($block,array('side_galleries','main_gallery_embed','main_image_fader')))) // gallery list
		{
			require_code('galleries');
			$list=create_selection_list_gallery_tree($default);
			return form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false);
		}
		return NULL;
	}

}
