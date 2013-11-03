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
 * @package		core_cleanup_tools
 */

/**
 * Module page class.
 */
class Module_admin_cleanup
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=3;
		$info['locked']=false;
		$info['update_require_upgrade']=1;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code or type-code=>[language-code, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'CLEANUP_TOOLS');
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

		require_lang('cleanup');

		set_helper_panel_tutorial('tut_cleanup');

		if ($type=='rebuild')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('CLEANUP_TOOLS'))));
			breadcrumb_set_self(do_lang_tempcode('DONE'));
		}

		$this->title=get_screen_title('CLEANUP_TOOLS');

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_code('caches3');

		// Decide what we're doing
		$type=get_param('type','misc');

		if ($type=='misc') return $this->choose_cache_type();
		if ($type=='rebuild') return $this->do_rebuild();

		return new ocp_tempcode();
	}

	/**
	 * The UI for choosing caches to empty.
	 *
	 * @return tempcode		The UI
	 */
	function choose_cache_type()
	{
		$hooks=find_all_hooks('systems','cleanup');

		$url=build_url(array('page'=>'_SELF','type'=>'rebuild'),'_SELF');

		require_code('form_templates');

		$fields_cache=new ocp_tempcode();
		$fields_optimise=new ocp_tempcode();
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/cleanup/'.filter_naughty_harsh($hook));
			$object=object_factory('Hook_'.filter_naughty_harsh($hook),true);
			if (is_null($object)) continue;
			$output=$object->info();
			if (!is_null($output))
			{
				$tick=form_input_tick($output['title'],$output['description'],$hook,false);
				if ($output['type']=='cache')
				{
					$fields_cache->attach($tick);
				} else
				{
					$fields_optimise->attach($tick);
				}
			}
		}

		$fields=new ocp_tempcode();
		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'4a9d6e722f246887160c444a062a9d00','SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('CACHES_PAGE_EXP_OPTIMISERS'),'HELP'=>'')));
		$fields->attach($fields_optimise);
		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'3ddb387dba8c42ac4ef7b85621052e11','TITLE'=>do_lang_tempcode('CACHES_PAGE_EXP_CACHES'),'HELP'=>do_lang_tempcode('CACHES_PAGE_CACHES'))));
		$fields->attach($fields_cache);

		return do_template('FORM_SCREEN',array('_GUID'=>'85bfdf171484604594a157aa8983f920','SKIP_VALIDATION'=>true,'TEXT'=>do_lang_tempcode('CACHES_PAGE'),'SUBMIT_NAME'=>do_lang_tempcode('PROCEED'),'HIDDEN'=>'','TITLE'=>$this->title,'FIELDS'=>$fields,'URL'=>$url));
	}

	/**
	 * The actualiser for emptying caches.
	 *
	 * @return tempcode		The UI
	 */
	function do_rebuild()
	{
		$hooks=find_all_hooks('systems','cleanup');

		// Fiddle the order a bit
		if (array_key_exists('ocf_topics',$hooks))
		{
			unset($hooks['ocf_topics']);
			$hooks['ocf_topics']='sources_custom';
		}
		if (array_key_exists('ocf',$hooks))
		{
			unset($hooks['ocf']);
			$hooks['ocf']='sources_custom';
		}
		if (array_key_exists('ocf_members',$hooks))
		{
			unset($hooks['ocf_members']);
			$hooks['ocf_members']='sources_custom';
		}

		$todo=array();
		foreach (array_keys($hooks) as $hook)
		{
			if (post_param_integer($hook,0)==1)
				$todo[]=$hook;
		}
		$messages=ocportal_cleanup($todo);
		$messages->attach(paragraph(do_lang_tempcode('SUCCESS')));

		return do_template('CLEANUP_COMPLETED_SCREEN',array('_GUID'=>'598510a9ad9f01f3c0806319b32b5033','TITLE'=>$this->title,'MESSAGES'=>$messages));
	}

}


