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
 * @package		calendar
 */

class Hook_rss_calendar
{

	/**
	 * Standard modular run function for RSS hooks.
	 *
	 * @param  string			A list of categories we accept from
	 * @param  TIME			Cutoff time, before which we do not show results from
	 * @param  string			Prefix that represents the template set we use
	 * @set    RSS_ ATOM_
	 * @param  string			The standard format of date to use for the syndication type represented in the prefix
	 * @param  integer		The maximum number of entries to return, ordering by date
	 * @return ?array			A pair: The main syndication section, and a title (NULL: error)
	 */
	function run($_filters,$cutoff,$prefix,$date_string,$max)
	{
		if (!addon_installed('calendar')) return NULL;

		if (!has_actual_page_access(get_member(),'calendar')) return NULL;

		$filters=ocfilter_to_sqlfragment($_filters,'c.id','calendar_types',NULL,'e_type','id');

		$content=new ocp_tempcode();
		$_categories=$GLOBALS['SITE_DB']->query('SELECT c.id,c.t_title FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'calendar_types c WHERE '.$filters,NULL,NULL,false,false,array('t_title'));
		foreach ($_categories as $i=>$_category)
		{
			$_categories[$i]['_title']=get_translated_text($_category['t_title']);
		}
		$categories=collapse_2d_complexity('id','_title',$_categories);
		//$rows=$GLOBALS['SITE_DB']->query('SELECT * FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'calendar_events WHERE e_add_date>'.strval((integer)$cutoff).' ORDER BY e_add_date DESC',$max);
		$period_start=utctime_to_usertime($cutoff);
		$period_end=utctime_to_usertime(time()*2-$cutoff);
		if (is_float($period_end))	$period_end=intval($period_end);
		require_code('calendar');
		$rows=calendar_matches(get_member(),true,$period_start,$period_end,NULL,false);
		$rows=array_reverse($rows);
		foreach ($rows as $i=>$_row)
		{
			if ($i==$max) break;

			$row=$_row[1];

			if (!array_key_exists('id',$row)) continue; // RSS event

			$id=strval($row['id']);
			$author='';

			// The "add" date'll be actually used for the event time
			//$_news_date=mktime($row['e_start_hour'],$row['e_start_minute'],0,$row['e_start_month'],$row['e_start_day'],$row['e_start_year']);
			$_news_date=$_row[2];
			$news_date=date($date_string,usertime_to_utctime($_news_date));

			// The edit date'll be the latest of add/edit
			$edit_date=is_null($row['e_edit_date'])?date($date_string,$row['e_add_date']):date($date_string,$row['e_edit_date']);

			$news_title=xmlentities(escape_html(get_translated_text($row['e_title'])));
			$_summary=get_translated_tempcode($row,'e_content');
			$summary=xmlentities($_summary->evaluate());
			$news='';

			$category=array_key_exists($row['e_type'],$categories)?$categories[$row['e_type']]:'';
			$category_raw=strval($row['e_type']);

			$view_url=build_url(array('page'=>'calendar','type'=>'view','id'=>$row['id']),get_module_zone('calendar'),NULL,false,false,true);

			if (!array_key_exists('allow_comments',$row)) $row['allow_comments']=1;
			if (($prefix=='RSS_') && (get_option('is_on_comments')=='1') && ($row['allow_comments']>=1))
			{
				$if_comments=do_template('RSS_ENTRY_COMMENTS',array('_GUID'=>'202a32693ce54d9ce960b72e66714df0','COMMENT_URL'=>$view_url,'ID'=>strval($row['id'])));
			} else $if_comments=new ocp_tempcode();

			$content->attach(do_template($prefix.'ENTRY',array('VIEW_URL'=>$view_url,'SUMMARY'=>$summary,'EDIT_DATE'=>$edit_date,'IF_COMMENTS'=>$if_comments,'TITLE'=>$news_title,'CATEGORY_RAW'=>$category_raw,'CATEGORY'=>$category,'AUTHOR'=>$author,'ID'=>$id,'NEWS'=>$news,'DATE'=>$news_date)));
		}

		require_lang('calendar');
		return array($content,do_lang('CALENDAR'));
	}

}


