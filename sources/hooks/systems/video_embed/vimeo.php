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
 * @package		galleries
 */

class Hook_video_embed_vimeo
{

	/**
	 * If we can handle this URL, get the render template and ID for it.
	 *
	 * @param  URLPATH		Video URL
	 * @return ?array			A pair: the template, and ID (NULL: no match)
	 */
	function get_template_name_and_id($url)
	{
		$matches=array();
		if (preg_match('#^https?://vimeo\.com/(\d+)#',$url,$matches)!=0)
		{
			$id=rawurldecode($matches[1]);
			return array('GALLERY_VIDEO_VIMEO',$id);
		}
		return NULL;
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
		if (preg_match('#^https?://vimeo\.com/(\d+)#',$src_url,$matches)!=0)
		{
			$test=get_long_value('vimeo_thumb_for__'.$matches[1]);
			if ($test!==NULL) return $test;

			// Vimeo API method
			if (is_file(get_file_base().'/sources_custom/gallery_syndication.php'))
			{
				require_code('hooks/modules/video_syndication/vimeo');
				$ob=object_factory('video_syndication_vimeo');
				if ($ob->is_active())
				{
					$result=$ob->get_remote_videos(NULL,$matches[1]);
					if (count($result)!=0)
					{
						foreach ($result as $r)
						{
							return $r['thumb_url'];
						}
					}
					return NULL;
				}
			}

			// Lame method (not so reliable)
			$html=http_download_file($src_url,NULL,false);
			if (is_null($html)) return NULL;
			$matches2=array();
			if (preg_match('#<meta property="og:image" content="([^"]+)"#',$html,$matches2)!=0)
			{
				//set_long_value('vimeo_thumb_for__'.$matches[1],$matches2[1]);		Actually this only happens occasionally (on add/edit), so not needed. Caching would bung up DB and make editing a pain.
				return $matches[1];
			}
		}
		return NULL;
	}

}

