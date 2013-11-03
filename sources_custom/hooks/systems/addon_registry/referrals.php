<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		referrals
 */

class Hook_addon_registry_referrals
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
		return 'Chris Graham';
	}

	/**
	 * Find other authors
	 *
	 * @return array			A list of co-authors that should be attributed
	 */
	function get_copyright_attribution()
	{
		return array(
			'Icon by Titan Creations',
		);
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
		return 'A referrals package.

Allows people to specify who referred them when they join your site or other configurable triggers in the system, and defines award levels people can reach. Note that tracking of referrals and award of point is a default part of ocPortal, but referrals are only picked up if made via the recommend module and the new member uses the same address they were recommended to. This addon will allow referrals to be specified explicitly via the URL or typed in manually.

1) Edit the settings in text_custom/referrals.txt (there is an editing link for this on the setup menu)

2) Edit the messages in the referrals.ini language file as required.

3) Probably set up a page on your site explaining the awards you give.';
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
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images_custom/icons/24x24/menu/referrals.png',
			'themes/default/images_custom/icons/48x48/menu/referrals.png',
			'sources_custom/hooks/systems/addon_registry/referrals.php',
			'sources_custom/hooks/systems/referrals/.htaccess',
			'sources_custom/hooks/systems/notifications/referral.php',
			'sources_custom/hooks/systems/notifications/referral_staff.php',
			'text_custom/referrals.txt',
			'data_custom/referrer_report.php',
			'lang_custom/EN/referrals.ini',
			'sources_custom/ocf_join.php',
			'sources_custom/referrals.php',
			'sources_custom/hooks/systems/ecommerce/usergroup.php',
			'sources_custom/hooks/systems/ecommerce/cart_orders.php',
			'sources_custom/hooks/systems/referrals/index.html',
			'sources_custom/hooks/systems/do_next_menus/referrals.php',
			'sources_custom/hooks/modules/members/referrals.php',
			'adminzone/pages/comcode_custom/EN/referrals.txt',
			'adminzone/pages/modules_custom/admin_referrals.php',
			'sources_custom/hooks/systems/startup/referrals.php',
		);
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('referrer_override');
		$GLOBALS['SITE_DB']->drop_table_if_exists('referrals_qualified_for');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 */
	function install($upgrade_from=NULL)
	{
		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('referrer_override',array(
				'o_referrer'=>'*MEMBER',
				'o_scheme_name'=>'*ID_TEXT',
				'o_referrals_dif'=>'INTEGER',
				'o_is_qualified'=>'?BINARY',
			));

			$GLOBALS['SITE_DB']->create_table('referees_qualified_for',array(
				'id'=>'*AUTO',
				'q_referee'=>'MEMBER',
				'q_referrer'=>'MEMBER',
				'q_scheme_name'=>'ID_TEXT',
				'q_email_address'=>'SHORT_TEXT',
				'q_time'=>'TIME',
				'q_action'=>'ID_TEXT',
			));

			// Populate from current invites
			$rows=$GLOBALS['FORUM_DB']->query_select('f_invites',array('i_email_address','i_time','i_inviter'),array('i_taken'=>1));
			foreach ($rows as $row)
			{
				$member_id=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_members','id',array('m_email_address'=>$row['i_email_address']));
				if (!is_null($member_id))
				{
					$ini_file=parse_ini_file(get_custom_file_base().'/text_custom/referrals.txt',true);

					foreach (array_keys($ini_file) as $scheme_name)
					{
						$GLOBALS['SITE_DB']->query_insert('referees_qualified_for',array(
							'q_referee'=>$member_id,
							'q_referrer'=>$row['i_inviter'],
							'q_scheme_name'=>$scheme_name,
							'q_email_address'=>$row['i_email_address'],
							'q_time'=>$row['i_time'],
							'q_action'=>'',
						));
					}
				}
			}
		}
	}
}