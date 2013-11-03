<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		unit_testing
 */

/**
 * ocPortal test case class (unit testing).
 */
class override_notes_consistency_test_set extends ocp_test_case
{
	function testUnusedGlobals()
	{
		require_code('files');
		require_code('files2');
		$files=get_directory_contents(get_file_base(),'',true);
		foreach ($files as $file)
		{
			if (should_ignore_file($file,IGNORE_NONBUNDLED_SCATTERED)) continue;

			if (substr($file,-4)=='.php')
			{
				$contents=file_get_contents(get_file_base().'/'.$file);
				if (strpos($file,'_custom')===false)
				{
					$this->assertTrue(strpos($contents,'NOTE TO PROGRAMMERS:')!==false,'Missing "NOTE TO PROGRAMMERS:" in '.$file);
				} else
				{
					$this->assertFalse(strpos($contents,'NOTE TO PROGRAMMERS:')!==false,'Undesirable "NOTE TO PROGRAMMERS:" in '.$file);
				}
			}
		}
	}
}
