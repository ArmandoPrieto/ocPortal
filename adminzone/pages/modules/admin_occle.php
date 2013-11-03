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
 * @package		occle
 */

/**
 * Module page class.
 */
class Module_admin_occle
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Philip Withnall';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code or type-code=>[language-code, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('!'=>'OCCLE');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if (is_null($upgrade_from))
		{
			$usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
			foreach (array_keys($usergroups) as $id)
			{
				$GLOBALS['SITE_DB']->query_insert('group_page_access',array('page_name'=>'admin_occle','zone_name'=>'adminzone','group_id'=>$id)); // OcCLE very dangerous
			}

			$GLOBALS['SITE_DB']->create_table('occlechat',array(
				'id'=>'*AUTO',
				'c_message'=>'LONG_TEXT',
				'c_url'=>'URLPATH',
				'c_incoming'=>'BINARY',
				'c_timestamp'=>'TIME'
			));
		}
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('occlechat');

		delete_value('last_occle_command');
	}

	var $title;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('occle');

		set_helper_panel_tutorial('tut_occle');
		set_helper_panel_text(comcode_lang_string('DOC_OCCLE'));

		$this->title=get_screen_title('OCCLE');

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_code('occle');
		require_javascript('javascript_ajax');
		require_javascript('javascript_more');
		require_javascript('javascript_occle');
		require_css('occle');

		return $this->main_gui();
	}

	/**
	 * The main OcCLE GUI.
	 *
	 * @return tempcode	The UI
	 */
	function main_gui()
	{
		if (!is_null($GLOBALS['CURRENT_SHARE_USER'])) warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));

		$command=post_param('occle_command','');
		if ($command!='')
		{
			//We've had a normal form submission
			$temp=new virtual_bash($command);
			$commands=$temp->output_html();
		} else $commands=new ocp_tempcode();

		$content=do_template('OCCLE_MAIN',array(
			'_GUID'=>'05c1e7efacc3839babfe58fe624caa61',
			'SUBMIT_URL'=>build_url(array('page'=>'_SELF'),'_SELF'),
			'PROMPT'=>do_lang_tempcode('COMMAND_PROMPT',escape_html($GLOBALS['FORUM_DRIVER']->get_username(get_member()))),
			'COMMANDS'=>$commands,
		));

		return do_template('OCCLE_MAIN_SCREEN',array(
			'_GUID'=>'d71ef9fa2cdaf419fee64cf3d7555225',
			'TITLE'=>$this->title,
			'CONTENT'=>$content,
		));
	}
}

