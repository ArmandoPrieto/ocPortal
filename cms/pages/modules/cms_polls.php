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
 * @package		polls
 */

require_code('aed_module');

/**
 * Module page class.
 */
class Module_cms_polls extends standard_aed_module
{
	var $lang_type='POLL';
	var $archive_entry_point='_SEARCH:polls:type=misc';
	var $view_entry_point='_SEARCH:polls:type=view:id=_ID';
	var $user_facing=true;
	var $send_validation_request=false;
	var $permissions_require='mid';
	var $select_name='QUESTION';
	var $select_name_description='DESCRIPTION_QUESTION';
	var $menu_label='POLLS';
	var $table='poll';
	var $title_is_multi_lang=true;
	var $award_type='poll';

	/**
	 * Standard aed_module run_start.
	 *
	 * @param  ID_TEXT		The type of module execution
	 * @return tempcode		The output of the run
	 */
	function run_start($type)
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/polls';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_feedback';

		require_code('polls');
		require_lang('polls');
		require_css('polls');

		$this->add_one_label=do_lang_tempcode('ADD_POLL');
		$this->edit_this_label=do_lang_tempcode('EDIT_THIS_POLL');
		$this->edit_one_label=do_lang_tempcode('EDIT_POLL');

		if ($type=='misc') return $this->misc();

		return new ocp_tempcode();
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array_merge(array('misc'=>'MANAGE_POLLS'),parent::get_entry_points());
	}

	/**
	 * Standard modular privilege-overide finder function.
	 *
	 * @return array	A map of privileges that are overridable; sp to 0 or 1. 0 means "not category overridable". 1 means "category overridable".
	 */
	function get_sp_overrides()
	{
		require_lang('polls');
		return array('submit_midrange_content'=>array(0,'ADD_POLL'),'bypass_validation_midrange_content'=>array(0,'BYPASS_VALIDATION_POLL'),'edit_own_midrange_content'=>array(0,'EDIT_OWN_POLL'),'edit_midrange_content'=>array(0,'EDIT_POLL'),'delete_own_midrange_content'=>array(0,'DELETE_OWN_POLL'),'delete_midrange_content'=>array(0,'DELETE_POLL'),'edit_own_highrange_content'=>array(0,'EDIT_OWN_LIVE_POLL'),'edit_highrange_content'=>array(0,'EDIT_LIVE_POLL'),'delete_own_highrange_content'=>array(0,'DELETE_OWN_LIVE_POLL'),'delete_highrange_content'=>array(0,'DELETE_LIVE_POLL'),'vote_in_polls'=>0);
	}

	/**
	 * The do-next manager for before content management.
	 *
	 * @return tempcode		The UI
	 */
	function misc()
	{
		require_code('templates_donext');
		require_code('fields');
		return do_next_manager(get_page_title('MANAGE_POLLS'),comcode_lang_string('DOC_POLLS'),
					array_merge(array(
						/*	 type							  page	 params													 zone	  */
						has_specific_permission(get_member(),'submit_midrange_content','cms_polls')?array('add_one',array('_SELF',array('type'=>'ad'),'_SELF'),do_lang('ADD_POLL')):NULL,
						has_specific_permission(get_member(),'edit_own_midrange_content','cms_polls')?array('edit_one',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('EDIT_OR_CHOOSE_POLL')):NULL,
					),manage_custom_fields_donext_link('poll')),
					do_lang('MANAGE_POLLS')
		);
	}

	/**
	 * Standard aed_module table function.
	 *
	 * @param  array			Details to go to build_url for link to the next screen.
	 * @return array			A quartet: The choose table, Whether re-ordering is supported from this screen, Search URL, Archive URL.
	 */
	function nice_get_choose_table($url_map)
	{
		$table=new ocp_tempcode();
		
		require_code('templates_results_table');
		
		$default_order='is_current DESC,add_time DESC';
		$current_ordering=get_param('sort',$default_order);
		if ($current_ordering=='is_current DESC,add_time DESC')
		{
			list($sortable,$sort_order)=array('is_current DESC,add_time','DESC');
		}
		elseif (($current_ordering=='is_current ASC,add_time ASC') || ($current_ordering=='is_current DESC,add_time ASC'))
		{
			list($sortable,$sort_order)=array('is_current ASC,add_time','ASC');
		} else
		{
			list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		}
		$sortables=array(
			'question'=>do_lang_tempcode('QUESTION'),
			'add_time'=>do_lang_tempcode('_ADDED'),
			'is_current DESC,add_time'=>do_lang_tempcode('CURRENT'),
			'submitter'=>do_lang_tempcode('OWNER'),
			'poll_views'=>do_lang_tempcode('_VIEWS'),
			'votes1+votes2+votes3+votes4+votes5+votes6+votes7+votes8+votes9+votes10'=>do_lang_tempcode('COUNT_TOTAL'),
		);
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';

		$header_row=results_field_title(array(
			do_lang_tempcode('QUESTION'),
			do_lang_tempcode('_ADDED'),
			do_lang_tempcode('CURRENT'),
			do_lang_tempcode('USED_PREVIOUSLY'),
			do_lang_tempcode('OWNER'),
			do_lang_tempcode('_VIEWS'),
			do_lang_tempcode('COUNT_TOTAL'),
			do_lang_tempcode('ACTIONS'),
		),$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		$only_owned=has_specific_permission(get_member(),'edit_midrange_content','cms_polls')?NULL:get_member();
		list($rows,$max_rows)=$this->get_entry_rows(false,$current_ordering,(is_null($only_owned)?array():array('submitter'=>$only_owned)));
		require_code('form_templates');
		foreach ($rows as $row)
		{
			$edit_link=build_url($url_map+array('id'=>$row['id']),'_SELF');

			$username=protect_from_escaping($GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['submitter']));

			$total_votes=$row['votes1']+$row['votes2']+$row['votes3']+$row['votes4']+$row['votes5']+$row['votes6']+$row['votes7']+$row['votes8']+$row['votes9']+$row['votes10'];
			$used=($total_votes!=0);
			$current=($row['is_current']==1);

			$fields->attach(results_entry(array(protect_from_escaping(hyperlink(build_url(array('page'=>'polls','type'=>'view','id'=>$row['id']),get_module_zone('polls')),get_translated_text($row['question']))),get_timezoned_date($row['add_time']),$current?do_lang_tempcode('YES'):do_lang_tempcode('NO'),($used || $current)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),$username,integer_format($row['poll_views']),do_lang_tempcode('VOTES',escape_html(integer_format($total_votes))),protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#'.strval($row['id']))))),true);
		}
		
		$search_url=build_url(array('page'=>'search','id'=>'polls'),get_module_zone('search'));
		$archive_url=build_url(array('page'=>'polls'),get_module_zone('polls'));

		return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',get_param_integer('max',300),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order),false,$search_url,$archive_url);
	}

	/**
	 * Standard aed_module list function.
	 *
	 * @return tempcode		The selection list
	 */
	function nice_get_entries()
	{
		$only_owned=has_specific_permission(get_member(),'edit_midrange_content','cms_polls')?NULL:get_member();
		$poll_list=nice_get_polls(NULL,$only_owned);
		return $poll_list;
	}
	
	/**
	 * Get tempcode for a poll adding/editing form.
	 *
	 * @param  SHORT_TEXT		The question
	 * @param  SHORT_TEXT		The first answer
	 * @param  SHORT_TEXT		The second answer
	 * @param  SHORT_TEXT		The third answer
	 * @param  SHORT_TEXT		The fourth answer
	 * @param  SHORT_TEXT		The fifth answer
	 * @param  SHORT_TEXT		The sixth answer
	 * @param  SHORT_TEXT		The seventh answer
	 * @param  SHORT_TEXT		The eigth answer
	 * @param  SHORT_TEXT		The ninth answer
	 * @param  SHORT_TEXT		The tenth answer
	 * @param  boolean			Whether the poll is/will-be currently active
	 * @param  ?BINARY			Whether rating is allowed (NULL: decide statistically, based on existing choices)
	 * @param  ?SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style) (NULL: decide statistically, based on existing choices)
	 * @param  ?BINARY			Whether trackbacks are allowed (NULL: decide statistically, based on existing choices)
	 * @param  LONG_TEXT			Notes for the poll
	 * @return tempcode			The tempcode for the visible fields
	 */
	function get_form_fields($question='',$a1='',$a2='',$a3='',$a4='',$a5='',$a6='',$a7='',$a8='',$a9='',$a10='',$current=false,$allow_rating=1,$allow_comments=1,$allow_trackbacks=1,$notes='')
	{
		list($allow_rating,$allow_comments,$allow_trackbacks)=$this->choose_feedback_fields_statistically($allow_rating,$allow_comments,$allow_trackbacks);

		$fields=new ocp_tempcode();
		require_code('form_templates');
		$fields->attach(form_input_line_comcode(do_lang_tempcode('QUESTION'),do_lang_tempcode('DESCRIPTION_QUESTION'),'question',$question,true));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('ANSWER_X',integer_format(1)),do_lang_tempcode('DESCRIPTION_ANSWER'),'option1',$a1,true));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('ANSWER_X',integer_format(2)),do_lang_tempcode('DESCRIPTION_ANSWER'),'option2',$a2,true));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('ANSWER_X',integer_format(3)),do_lang_tempcode('DESCRIPTION_ANSWER'),'option3',$a3,false));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('ANSWER_X',integer_format(4)),do_lang_tempcode('DESCRIPTION_ANSWER'),'option4',$a4,false));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('ANSWER_X',integer_format(5)),do_lang_tempcode('DESCRIPTION_ANSWER'),'option5',$a5,false));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('ANSWER_X',integer_format(6)),do_lang_tempcode('DESCRIPTION_ANSWER'),'option6',$a6,false));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('ANSWER_X',integer_format(7)),do_lang_tempcode('DESCRIPTION_ANSWER'),'option7',$a7,false));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('ANSWER_X',integer_format(8)),do_lang_tempcode('DESCRIPTION_ANSWER'),'option8',$a8,false));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('ANSWER_X',integer_format(9)),do_lang_tempcode('DESCRIPTION_ANSWER'),'option9',$a9,false));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('ANSWER_X',integer_format(10)),do_lang_tempcode('DESCRIPTION_ANSWER'),'option10',$a10,false));
		if (has_specific_permission(get_member(),'choose_poll'))
		{
			if ($question=='')
			{
				$test=$GLOBALS['SITE_DB']->query_value_null_ok('poll','is_current',array('is_current'=>1));
				if (is_null($test)) $current=true;
			}
			$fields->attach(form_input_tick(do_lang_tempcode('IMMEDIATE_USE'),do_lang_tempcode('DESCRIPTION_IMMEDIATE_USE'),'validated',$current));
		}

		require_code('feedback2');
		$fields->attach(feedback_fields($allow_rating==1,$allow_comments==1,$allow_trackbacks==1,false,$notes,$allow_comments==2));

		return $fields;
	}

	/**
	 * Standard aed_module submitter getter.
	 *
	 * @param  ID_TEXT		The entry for which the submitter is sought
	 * @return array			The submitter, and the time of submission (null submission time implies no known submission time)
	 */
	function get_submitter($id)
	{
		$rows=$GLOBALS['SITE_DB']->query_select('poll',array('submitter','date_and_time'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$rows)) return array(NULL,NULL);
		return array($rows[0]['submitter'],$rows[0]['date_and_time']);
	}

	/**
	 * Standard aed_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return array			A quartet: fields, hidden, delete-fields, text
	 */
	function fill_in_edit_form($id)
	{
		$rows=$GLOBALS['SITE_DB']->query_select('poll',array('*'),array('id'=>intval($id)));
		if (!array_key_exists(0,$rows))
		{
			warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		}
		$myrow=$rows[0];
	
		$fields=$this->get_form_fields(get_translated_text($myrow['question']),get_translated_text($myrow['option1']),get_translated_text($myrow['option2']),get_translated_text($myrow['option3']),get_translated_text($myrow['option4']),get_translated_text($myrow['option5']),get_translated_text($myrow['option6']),get_translated_text($myrow['option7']),get_translated_text($myrow['option8']),get_translated_text($myrow['option9']),get_translated_text($myrow['option10']),$myrow['is_current'],$myrow['allow_rating'],$myrow['allow_comments'],$myrow['allow_trackbacks'],$myrow['notes'],$myrow['allow_rating'],$myrow['allow_comments'],$myrow['notes']);

		return $fields;
	}

	/**
	 * Standard aed_module add actualiser.
	 *
	 * @return ID_TEXT		The entry added
	 */
	function add_actualisation()
	{
		$question=post_param('question');
		$option1=post_param('option1');
		$option2=post_param('option2');
		$option3=post_param('option3');
		$option4=post_param('option4');
		$option5=post_param('option5');
		$option6=post_param('option6');
		$option7=post_param('option7');
		$option8=post_param('option8');
		$option9=post_param('option9');
		$option10=post_param('option10');
		$allow_rating=post_param_integer('allow_rating',0);
		$allow_comments=post_param_integer('allow_comments',0);
		$allow_trackbacks=post_param_integer('allow_trackbacks',0);
		$notes=post_param('notes','');
		$num_options=10;
		if ($option10=='') $num_options=9;
		if ($option9=='') $num_options=8;
		if ($option8=='') $num_options=7;
		if ($option7=='') $num_options=6;
		if ($option6=='') $num_options=5;
		if ($option5=='') $num_options=4;
		if ($option4=='') $num_options=3;
		if ($option3=='') $num_options=2;
		if ($option2=='') $num_options=1;

		$id=add_poll($question,$option1,$option2,$option3,$option4,$option5,$option6,$option7,$option8,$option9,$option10,$num_options,post_param_integer('validated',0),$allow_rating,$allow_comments,$allow_trackbacks,$notes);
		$current=post_param_integer('validated',0);
		if ($current==1)
		{
			if (!has_specific_permission(get_member(),'choose_poll'))
				log_hack_attack_and_exit('BYPASS_VALIDATION_HACK');
			set_poll($id);
		}
		
		if ($current==1)
		{
			if (has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'polls'))
				syndicate_described_activity('polls:ADD_POLL',$question,'','','_SEARCH:polls:view:'.strval($id),'','','polls');
		}

		return strval($id);
	}

	/**
	 * Standard aed_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 */
	function edit_actualisation($id)
	{
		$rows=$GLOBALS['SITE_DB']->query_select('poll',array('is_current','submitter','num_options'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$is_current=$rows[0]['is_current'];
		$submitter=$rows[0]['submitter'];

		check_edit_permission(($is_current==1)?'high':'mid',$submitter);

		$question=post_param('question',STRING_MAGIC_NULL);
		$option1=post_param('option1',STRING_MAGIC_NULL);
		$option2=post_param('option2',STRING_MAGIC_NULL);
		$option3=post_param('option3',STRING_MAGIC_NULL);
		$option4=post_param('option4',STRING_MAGIC_NULL);
		$option5=post_param('option5',STRING_MAGIC_NULL);
		$option6=post_param('option6',STRING_MAGIC_NULL);
		$option7=post_param('option7',STRING_MAGIC_NULL);
		$option8=post_param('option8',STRING_MAGIC_NULL);
		$option9=post_param('option9',STRING_MAGIC_NULL);
		$option10=post_param('option10',STRING_MAGIC_NULL);
		$allow_rating=post_param_integer('allow_rating',fractional_edit()?INTEGER_MAGIC_NULL:0);
		$allow_comments=post_param_integer('allow_comments',fractional_edit()?INTEGER_MAGIC_NULL:0);
		$allow_trackbacks=post_param_integer('allow_trackbacks',fractional_edit()?INTEGER_MAGIC_NULL:0);
		$notes=post_param('notes',STRING_MAGIC_NULL);
		if (fractional_edit())
		{
			$num_options=$rows[0]['num_options'];
		} else
		{
			$num_options=10;
			if ($option10=='') $num_options=9;
			if ($option9=='') $num_options=8;
			if ($option8=='') $num_options=7;
			if ($option7=='') $num_options=6;
			if ($option6=='') $num_options=5;
			if ($option5=='') $num_options=4;
			if ($option4=='') $num_options=3;
			if ($option3=='') $num_options=2;
			if ($option2=='') $num_options=1;
		}

		$current=post_param_integer('validated',0);

		if (($current==1) && ($GLOBALS['SITE_DB']->query_value('poll','is_current',array('id'=>$id))==0)) // Just became validated, syndicate as just added
		{
			if (has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'polls'))
				syndicate_described_activity('polls:ADD_POLL',$question,'','','_SEARCH:polls:view:'.strval($id),'','','polls');
		}

		edit_poll(intval($id),$question,$option1,$option2,$option3,$option4,$option5,$option6,$option7,$option8,$option9,$option10,$num_options,$allow_rating,$allow_comments,$allow_trackbacks,$notes);

		if (!fractional_edit())
		{
			if ($current==1)
			{
				if ($is_current==0)
				{
					if (!has_specific_permission(get_member(),'choose_poll'))
						log_hack_attack_and_exit('BYPASS_VALIDATION_HACK');
	
					set_poll(intval($id));
				}
			}
		}
	}

	/**
	 * Standard aed_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($id)
	{
		$rows=$GLOBALS['SITE_DB']->query_select('poll',array('is_current','submitter'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$is_current=$rows[0]['is_current'];
		$submitter=$rows[0]['submitter'];

		check_delete_permission(($is_current==1)?'high':'mid',$submitter);

		delete_poll(intval($id));
	}
}


