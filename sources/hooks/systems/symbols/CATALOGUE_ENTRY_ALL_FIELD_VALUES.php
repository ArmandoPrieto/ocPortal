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
 * @package		catalogues
 */

class Hook_symbol_CATALOGUE_ENTRY_ALL_FIELD_VALUES
{

	/**
	 * Standard modular run function for symbol hooks. Searches for tasks to perform.
    *
    * @param  array		Symbol parameters
    * @return string		Result
	 */
	function run($param)
	{
		$value='';
		if (array_key_exists(0,$param))
		{
			$rows=$GLOBALS['SITE_DB']->query_select('catalogue_entries',array('*'),array('id'=>intval($param[0])),'',1);
			if (array_key_exists(0,$rows))
			{
				require_code('catalogues');
				$catalogue_name=$rows[0]['c_name'];
				$tpl_set=$catalogue_name;
				$display=get_catalogue_entry_map($rows[0],array('c_name'=>$catalogue_name,'c_display_type'=>C_DT_MAPS),'PAGE',$tpl_set,-1);
				if ((array_key_exists(1,$param)) && ($param[1]=='1'))
				{
					$value=$display['FIELDS']->evaluate();
				} else
				{
					$_value=do_template('CATALOGUE_'.$tpl_set.'_ENTRY',$display,NULL,false,'CATALOGUE_DEFAULT_ENTRY');
					$value=$_value->evaluate();
				}
			}
		}
		return $value;
	}

}
