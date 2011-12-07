<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_ocf
 */

/**
 * Render a member profile.
 *
 * @param  MEMBER			The ID of the member who is being viewed
 * @param  ?MEMBER		The ID of the member who is doing the viewing (NULL: current member)
 * @param  ?ID_TEXT		The username of the member who is being viewed (NULL: work out from member_id_of)
 * @return tempcode		The rendered profile
 */
function render_profile_tabset($member_id_of,$member_id_viewing=NULL,$username=NULL)
{
	if (is_null($member_id_viewing)) $member_id_viewing=get_member();

	$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id_of);
	if ((is_null($username)) || (is_guest($member_id_of))) warn_exit(do_lang_tempcode('USER_NO_EXIST'));

	$tabs=array();

	$hooks=find_all_hooks('systems','profiles_tabs');
	if (isset($hooks['edit'])) // Editing must go first, so changes reflect in the renders of the tabs
	{
		$hooks=array('edit'=>$hooks['edit'])+$hooks;
	}
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/systems/profiles_tabs/'.$hook);
		$ob=object_factory('Hook_Profiles_Tabs_'.$hook);
		if ($ob->is_active($member_id_of,$member_id_viewing))
		{
			$tabs[$hook]=$ob->render_tab($member_id_of,$member_id_viewing);
		}
	}

	global $M_SORT_KEY;
	$M_SORT_KEY=2;
	uasort($tabs,'multi_sort');

	require_javascript('javascript_profile');

	load_up_all_self_page_permissions($member_id_viewing);

	if (addon_installed('awards'))
	{
		require_code('awards');
		$awards=find_awards_for('member',strval($member_id_of));
	} else $awards=array();

	$title=get_page_title('MEMBER_PROFILE',true,array(escape_html($username)),NULL,$awards);

	$_tabs=array();
	$i=0;
	foreach ($tabs as $hook=>$tab)
	{
		$_tabs[]=array('TAB_TITLE'=>$tab[0],'TAB_CODE'=>$hook,'TAB_CONTENT'=>$tab[1],'TAB_FIRST'=>$i==0,'TAB_LAST'=>!array_key_exists($i+1,$tabs));
		$i++;
	}

	return do_template('OCF_MEMBER_PROFILE_SCREEN',array('TITLE'=>$title,'TABS'=>$_tabs,'MEMBER_ID'=>strval($member_id_of)));
}
