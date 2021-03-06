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
 * @package		sms
 */

class Hook_addon_registry_sms
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
		return 'Provides an option for the software to send SMS messages, via the commercial Clickatell web service.';
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

			'sources/hooks/systems/addon_registry/sms.php',
			'sources/sms.php',
			'lang/EN/sms.ini',
			'sources/hooks/systems/config_default/sms_password.php',
			'sources/hooks/systems/config_default/sms_username.php',
			'sources/hooks/systems/config_default/sms_low_limit.php',
			'sources/hooks/systems/config_default/sms_high_limit.php',
			'sources/hooks/systems/config_default/sms_low_trigger_limit.php',
			'sources/hooks/systems/config_default/sms_high_trigger_limit.php',
			'sources/hooks/systems/config_default/sms_api_id.php',
			'sources/hooks/systems/ocf_cpf_filter/sms.php',
			'data/sms.php',
		);
	}

}
