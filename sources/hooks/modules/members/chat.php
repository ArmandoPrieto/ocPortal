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
 * @package		chat
 */

class Hook_members_chat
{

	/**
	 * Standard modular run function.
	 *
	 * @param  MEMBER		The ID of the member we are getting link hooks for
	 * @return array		List of tuples for results. Each tuple is: type,title,url
	 */
	function run($member_id)
	{
		if (!addon_installed('chat')) return array();
		
		$modules=array();
		if (has_actual_page_access(get_member(),'chat',get_page_zone('chat')))
		{
			if ((!is_guest()) && ($member_id!=get_member()))
			{
				require_lang('chat');
				require_code('chat');
				if (!$GLOBALS['FORUM_DRIVER']->is_staff($member_id))
				{
					if (!member_blocked($member_id))
					{
						$modules[]=array('contact',do_lang_tempcode('EXPLAINED_BLOCK_MEMBER'),build_url(array('page'=>'chat','type'=>'blocking_add','member_id'=>$member_id,'redirect'=>get_self_url(true)),get_module_zone('chat')));
						if (has_specific_permission(get_member(),'start_im'))
						{
							$modules[]=array('contact',do_lang_tempcode('START_IM'),build_url(array('page'=>'chat','type'=>'misc','enter_im'=>$member_id),get_module_zone('chat')));
						}
					} else
					{
						$modules[]=array('contact',do_lang_tempcode('EXPLAINED_UNBLOCK_MEMBER'),build_url(array('page'=>'chat','type'=>'blocking_remove','member_id'=>$member_id,'redirect'=>get_self_url(true)),get_module_zone('chat')));
					}
				}
				if (!member_befriended($member_id))
				{
					$modules[]=array('contact',do_lang_tempcode('MAKE_BUDDY'),build_url(array('page'=>'chat','type'=>'buddy_add','member_id'=>$member_id,'redirect'=>get_self_url(true)),get_module_zone('chat')));
				} else
				{
					$modules[]=array('contact',do_lang_tempcode('DUMP_BUDDY'),build_url(array('page'=>'chat','type'=>'buddy_remove','member_id'=>$member_id,'redirect'=>get_self_url(true)),get_module_zone('chat')));
				}
			}
		}
		return $modules;
	}

}


