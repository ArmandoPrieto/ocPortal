<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		oc_browser_bookmarks
 */

class Hook_addon_registry_oc_browser_bookmarks
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
	 * Get the addon category
	 *
	 * @return string			The category
	 */
	function get_category()
	{
		return 'Information Display';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'Chris Graham';
	}

	/**
	 * Find other authors
	 *
	 * @return array			A list of co-authors that should be attributed
	 */
	function get_copyright_attribution()
	{
		return array();
	}

	/**
	 * Get the addon licence (one-line summary only)
	 *
	 * @return string			The licence
	 */
	function get_licence()
	{
		return 'Licensed on the same terms as ocPortal';
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Export the site-map as browser bookmarks.
		
Ever wished it was quicker to navigate around your site? If you\'re anything like us you get tired of having to move the mouse all around screens, and wait for half a dozen page loads, to get where you\'re going. There is an admin menu and sitemap in ocPortal, but that\'s still not as fast as a native desktop interface. Coming to the rescue is this simple addon that will export your whole sitemap to your web browsers bookmarks, so you can easily access anything on it. All you need to do is:
1) install the addon
2) call up /adminzone/?page=admin_generate_bookmarks
3) save the HTML file to your desktop
4) tell your web browser to import the HTML file as bookmarks/favorites (it\'s a Netscape/Mozilla/Firefox format bookmarks file)
5) And that\'s it- you\'ll be able to access all your links from a submenu of your regular browser bookmarks. (Note that there is no magic syncing here, so you might want to delete and reimport from time to time.)';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
		);
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(
			),
			'recommends'=>array(
			),
			'conflicts_with'=>array(
			)
		);
	}

	/**
	 * Explicitly say which icon should be used
	 *
	 * @return URLPATH		Icon
	 */
	function get_default_icon()
	{
		return 'themes/default/images/icons/48x48/tool_buttons/sitemap.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'sources_custom/hooks/systems/addon_registry/oc_browser_bookmarks.php',
			'adminzone/pages/minimodules_custom/admin_generate_bookmarks.php',
			'sources_custom/hooks/systems/page_groupings/ocbrowserbookmarks.php',
		);
	}
}