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
 * @package		ocf_forum
 */

class Hook_admin_stats_ocf_posting_rates
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		if (get_forum_type()!='ocf') return NULL;

		require_lang('stats');

		return array(
			array('posting_rates'=>array('POSTING_RATES','menu/adminzone/audit/statistics/posting_rates'),),
			array('menu/adminzone/audit/statistics/posting_rates',array('_SELF',array('type'=>'posting_rates'),'_SELF'),do_lang('POSTING_RATES'),'DESCRIPTION_POSTING_RATES'),
		);
	}


	/**
	 * The UI to show OCF posting rates.
	 *
	 * @param  object			The stats module object
	 * @param  string			The screen type
	 * @return tempcode		The UI
	 */
	function posting_rates($ob,$type)
	{
		require_lang('ocf');

		//This will show a plain bar chart with all the downloads listed
		$title=get_screen_title('POSTING_RATES');

		// Handle time range
		if (get_param_integer('dated',0)==0)
		{
			$title=get_screen_title('POSTING_RATES');

			$extra_fields=new ocp_tempcode();
			require_code('form_templates');
			$extra_fields->attach(form_input_tick(do_lang_tempcode('HOURLY_BREAKDOWNS'),do_lang_tempcode('DESCRIPTION_HOURLY_BREAKDOWNS'),'hourly',false));

			return $ob->get_between($title,false,$extra_fields);
		}
		$time_start=get_input_date('time_start',true);
		$time_end=get_input_date('time_end',true);
		if (!is_null($time_end)) $time_end+=60*60*24-1; // So it is end of day not start

		if (is_null($time_start)) $time_start=0;
		if (is_null($time_end)) $time_end=time();

		$title=get_screen_title('SECTION_POSTING_RATES_RANGE',true,array(escape_html(get_timezoned_date($time_start,false)),escape_html(get_timezoned_date($time_end,false))));

		$poster_exception='';
		foreach (explode(',',get_param('poster_exception','')) as $e)
		{
			if (trim($e)=='') continue;

			$poster_exception.='p_poster<>'.strval(intval($e)).' AND ';
		}

		$csv=get_param_integer('csv',0)==1;
		if ($csv)
		{
			$time_start=0; $time_end=time(); $hourly=false;
		}

		$rows=$GLOBALS['FORUM_DB']->query('SELECT p_time FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE '.$poster_exception.'p_time>'.strval($time_start).' AND p_time<'.strval($time_end));

		if (count($rows)<1) return warn_screen($title,do_lang_tempcode('NO_DATA'));

		$hourly=get_param_integer('hourly',0)==1;//($time_end-$time_start)<=60*60*24*2;

		$iterate_months=((floatval($time_end-$time_start)/(60.0*60.0*24.0))>100.0);

		// Gather data
		$posting_rates=array();
		if ($hourly)
		{
			for ($i=0;$i<24;$i++)
			{
				$date=str_pad(strval($i),2,'0',STR_PAD_LEFT).':00';
				$posting_rates[$date]=0;
			}
		} else
		{
			if ($iterate_months)
			{
				$year=intval(date('Y',$time_start));
				$month=intval(date('m',$time_start));
				while (mktime(0,0,0,$month-1,0,$year)<$time_end)
				{
					$date=date('Y/m',mktime(0,0,0,$month,0,$year));
					$posting_rates[$date]=0;

					$month++;
					if ($month==13)
					{
						$month=1;
						$year++;
					}
				}
			} else
			{
				for ($i=$time_start-60*60*12;$i<=$time_end+60*60*12;$i+=60*60*24)
				{
					$date=date('Y/m/d',$i);
					$posting_rates[$date]=0;
				}
			}
		}
		foreach ($rows as $row)
		{
			if ($hourly)
			{
				$date=date('H',$row['p_time']).':00';
			} else
			{
				if ($iterate_months)
				{
					$date=date('Y/m',$row['p_time']);
				} else
				{
					$date=date('Y/m/d',$row['p_time']);
				}
			}
			$posting_rates[$date]++;
		}

		$start=0;
		$max=1000; // Little trick, as we want all to fit
		$sortables=array();

		require_code('templates_results_table');
		$fields_title=results_field_title(array(do_lang_tempcode('DATE'),do_lang_tempcode('COUNT_TOTAL')),$sortables);
		$fields=new ocp_tempcode();
		$real_data=array();
		$i=0;
		foreach ($posting_rates as $date=>$value)
		{
			$fields->attach(results_entry(array(escape_html($date),integer_format($value))));

			$real_data[]=array(
				'Date/Time'=>$date,
				'Tally'=>$value,
			);

			$i++;
		}
		$list=results_table(do_lang_tempcode('POSTING_RATES'),$start,'start',$max,'max',count($posting_rates),$fields_title,$fields,$sortables,'','','sort',new ocp_tempcode());
		if ($csv) make_csv($real_data,'posting_rates.csv');

		$output=create_bar_chart($posting_rates,do_lang('DATE'),do_lang('COUNT_TOTAL'),'','');
		$ob->save_graph('Global-Posting_rates',$output);

		$graph=do_template('STATS_GRAPH',array('_GUID'=>'8c6f81c928789e267c81b1d50544ca25','GRAPH'=>get_custom_base_url().'/data_custom/modules/admin_stats/Global-Posting_rates.xml','TITLE'=>do_lang_tempcode('POSTING_RATES'),'TEXT'=>do_lang_tempcode('DESCRIPTION_POSTING_RATES')));

		$tpl=do_template('STATS_SCREEN',array('_GUID'=>'2af485cee293bf89607066db9f667423','TITLE'=>$title,'GRAPH'=>$graph,'STATS'=>$list));

		require_code('templates_internalise_screen');
		return internalise_own_screen($tpl);
	}

}


