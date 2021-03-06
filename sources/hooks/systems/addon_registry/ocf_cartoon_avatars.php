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
 * @package		ocf_cartoon_avatars
 */

class Hook_addon_registry_ocf_cartoon_avatars
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
		return 'A selection of avatars for OCF (sketched characters)';
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array('ocf_member_avatars'),
			'recommends'=>array(),
			'conflicts_with'=>array(),
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

			'sources/hooks/systems/addon_registry/ocf_cartoon_avatars.php',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/caveman.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/crazy.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/dance.gif',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/emo.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/footy.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/half-life.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/index.html',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/matrix.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/ninja.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/plane.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/posh.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/rabbit.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/snorkler.jpg',
			'themes/default/images/ocf_default_avatars/default_set/cartoons/western.jpg',
		);
	}

}
