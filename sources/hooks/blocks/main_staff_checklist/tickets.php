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
 * @package		tickets
 */

class Hook_checklist_tickets
{

	/**
	 * Standard modular run function.
	 *
	 * @return array		An array of tuples: The task row to show, the number of seconds until it is due (or NULL if not on a timer), the number of things to sort out (or NULL if not on a queue), The name of the config option that controls the schedule (or NULL if no option).
	 */
	function run()
	{
		if (!addon_installed('tickets')) return array();

		require_lang('tickets');
		require_code('tickets');
		require_code('tickets2');

		$outstanding=0;

		$tickets=get_tickets(get_member(),NULL,false,true);
		if (!is_null($tickets))
		{
			foreach ($tickets as $topic)
			{
				if ($topic['closed']==0) $outstanding++;
			}
		}

		if($outstanding>0)
		{
			$img='not_completed';			
		}
		else
		{	
			$img='completed';
		}

		$status=do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_0',array('_GUID'=>'6e1ac1c0310b944a07da55b9ed907ba9','ORDER_STATUS'=>$img));

		$url=build_url(array('page'=>'tickets','type'=>'misc'),get_module_zone('tickets'));

		$tpl=do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM',array('_GUID'=>'8202af47a2f1d24675acbe4c6d20c8b4','URL'=>$url,'STATUS'=>$status,'TASK'=>do_lang_tempcode('SUPPORT_TICKETS'),'INFO'=>do_lang_tempcode('NUM_QUEUE',escape_html(integer_format($outstanding)))));
		return array(array($tpl,NULL,$outstanding,NULL));
	}

}


