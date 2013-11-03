<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		workflows
 */

class Hook_addon_registry_workflows
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
		return 'New Features';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'Chris Warburton';
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
		return 'Extend the simple yes/no validation system of ocPortal to allows user-defined \"workflows\". A workflow contains an ordered list of \"approval levels\", such as \'design\' or \'spelling\', and each of these has a list of usergroups which have permission to approve it.

New content enters the default workflow (unless another is specified) and notifications are sent to those users with permission to approve the next level. This continues until all of the levels are approved, at which point the content goes live.

Note that this addon only affects galleries at the moment, and it requires the \"unvalidated\" system to be installed (this comes with ocPortal but may have been uninstalled). Other content types can be added by a programmer as this addon has been implemented in a modular way.';
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
				'unvalidated',
			),
			'recommends'=>array(
			),
			'conflicts_with'=>array(
			)
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
			'themes/default/images_custom/icons/24x24/menu/workflows.png',
			'themes/default/images_custom/icons/48x48/menu/workflows.png',
			'sources_custom/hooks/systems/addon_registry/workflows.php',
			'sources_custom/hooks/systems/notifications/workflow_step.php',
			'lang_custom/EN/workflows.ini',
			'cms/pages/modules_custom/cms_galleries.php',
			'adminzone/pages/modules_custom/admin_workflow.php',
			'sources_custom/workflows.php',
			'sources_custom/galleries2.php',
			'sources_custom/form_templates.php',
			'sources_custom/hooks/systems/do_next_menus/workflows.php',
			'sources_custom/hooks/modules/admin_unvalidated/images.php',
			'sources_custom/hooks/modules/admin_unvalidated/videos.php',
			'themes/default/templates_custom/FORM_SCREEN_INPUT_VARIOUS_TICKS.tpl',
			'site/pages/modules_custom/galleries.php',
			'adminzone/pages/modules_custom/admin_unvalidated.php',
			'themes/default/templates_custom/WORKFLOW_BOX.tpl',
		);
	}
}