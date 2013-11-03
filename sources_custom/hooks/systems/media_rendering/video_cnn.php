<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_rich_media
 */

class Hook_media_rendering_video_cnn
{
	/**
	 * Get the label for this media rendering type.
	 *
	 * @return string		The label
	 */
	function get_type_label()
	{
		require_lang('video_cnn');
		return do_lang('MEDIA_TYPE_'.preg_replace('#^Hook_media_rendering_#','',__CLASS__));
	}

	/**
	 * Find the media types this hook serves.
	 *
	 * @return integer	The media type(s), as a bitmask
	 */
	function get_media_type()
	{
		return MEDIA_TYPE_VIDEO;
	}

	/**
	 * See if we can recognise this mime type.
	 *
	 * @param  ID_TEXT	The mime type
	 * @return integer	Recognition precedence
	 */
	function recognises_mime_type($mime_type)
	{
		return MEDIA_RECOG_PRECEDENCE_NONE;
	}

	/**
	 * See if we can recognise this URL pattern.
	 *
	 * @param  URLPATH	URL to pattern match
	 * @return integer	Recognition precedence
	 */
	function recognises_url($url)
	{
		if (preg_match('#^https?://(edition\.)?cnn\.com/.*/video/(.*)\.html#',$url)!=0) return MEDIA_RECOG_PRECEDENCE_HIGH;
		return MEDIA_RECOG_PRECEDENCE_NONE;
	}

	/**
	 * If we can handle this URL, get the thumbnail URL.
	 *
	 * @param  URLPATH		Video URL
	 * @return ?string		The thumbnail URL (NULL: no match).
	 */
	function get_video_thumbnail($src_url)
	{
		$matches=array();
		if (preg_match('#^https?://(edition\.)?cnn\.com/.*/video/(.*)\.html#',$src_url,$matches)!=0)
		{
			return 'http://i.cdn.turner.com/cnn/video/'.$matches[3].'.214x122.jpg';
		}
		return NULL;
	}

	/**
	 * Provide code to display what is at the URL, in the most appropriate way.
	 *
	 * @param  mixed		URL to render
	 * @param  mixed		URL to render (no sessions etc)
	 * @param  array		Attributes (e.g. width, height, length)
	 * @param  boolean	Whether there are admin privileges, to render dangerous media types
	 * @param  ?MEMBER	Member to run as (NULL: current member)
	 * @return tempcode	Rendered version
	 */
	function render($url,$url_safe,$attributes,$as_admin=false,$source_member=NULL)
	{
		if (is_object($url)) $url=$url->evaluate();
		$attributes['remote_id']==preg_replace('#^https?://(edition\.)?cnn\.com/.*/video/(.*)\.html#','${2}',$url);
		return do_template('MEDIA_VIDEO_CNN',array('_GUID'=>'9b6a695ff7556a955a17a07fc4b77bf6','HOOK'=>'video_cnn')+_create_media_template_parameters($url,$attributes,$as_admin,$source_member));
	}

}
