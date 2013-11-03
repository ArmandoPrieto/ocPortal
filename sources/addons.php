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
 * @package		core_addon_management
 */

/**
 * Find detail addon details.
 *
 * @return array		Map of default addon details
 */
function get_default_addon_details()
{
	return array(
		'name'=>'',
		'author'=>'',
		'organisation'=>'',
		'version'=>'1.0',
		'category'=>'Uncategorised/Unstable',
		'copyright_attribution'=>array(),
		'licence'=>'(Unstated)',
		'description'=>'',
		'install_time'=>time(),
		'files'=>array(),
		'dependencies'=>array(),
	);
}

/**
 * Get info about an addon, simulating an extended version of the traditional ocPortal-addon database row.
 *
 * @param  string		The name of the addon
 * @param  boolean	Whether to search for dependencies on this
 * @param  ?array		Database row (NULL: lookup via a new query)
 * @param  ?array		.ini-format info (needs processing) (NULL: unknown / N/A)
 * @return array		The map of details
 */
function read_addon_info($addon,$get_dependencies_on_this=false,$row=NULL,$ini_info=NULL)
{
	// Hook file has highest priority...

	$is_orig=false;
	$path=get_file_base().'/sources_custom/hooks/systems/addon_registry/'.filter_naughty_harsh($addon).'.php';
	if (!file_exists($path))
	{
		$is_orig=true;
		$path=get_file_base().'/sources/hooks/systems/addon_registry/'.filter_naughty_harsh($addon).'.php';
	}

	if (file_exists($path))
	{
		$_hook_bits=extract_module_functions($path,array('get_dependencies','get_version','get_category','copyright_attribution','get_licence','get_description','get_author','get_organisation','get_file_list'));
		if (is_null($_hook_bits[0]))
		{
			$dep=array();
		} else
		{
			$dep=is_array($_hook_bits[0])?call_user_func_array($_hook_bits[0][0],$_hook_bits[0][1]):@eval($_hook_bits[0]);
		}
		$defaults=get_default_addon_details();
		if (!is_null($_hook_bits[1]))
		{
			$version=is_array($_hook_bits[1])?call_user_func_array($_hook_bits[1][0],$_hook_bits[1][1]):@eval($_hook_bits[1]);
		} else
		{
			$version=$defaults['version'];
		}
		if (!is_null($_hook_bits[2]))
		{
			$category=is_array($_hook_bits[2])?call_user_func_array($_hook_bits[2][0],$_hook_bits[2][1]):@eval($_hook_bits[2]);
		} else
		{
			$category=$defaults['category'];
		}
		if (!is_null($_hook_bits[3]))
		{
			$copyright_attribution=is_array($_hook_bits[3])?call_user_func_array($_hook_bits[3][0],$_hook_bits[3][1]):@eval($_hook_bits[3]);
		} else
		{
			$copyright_attribution=$defaults['copyright_attribution'];
		}
		if (!is_null($_hook_bits[4]))
		{
			$licence=is_array($_hook_bits[4])?call_user_func_array($_hook_bits[4][0],$_hook_bits[4][1]):@eval($_hook_bits[4]);
		} else
		{
			$licence=$defaults['licence'];
		}
		$description=is_array($_hook_bits[5])?call_user_func_array($_hook_bits[5][0],$_hook_bits[5][1]):@eval($_hook_bits[5]);
		if (!is_null($_hook_bits[6]))
		{
			$author=is_array($_hook_bits[6])?call_user_func_array($_hook_bits[6][0],$_hook_bits[6][1]):@eval($_hook_bits[6]);
		} else
		{
			$author=$is_orig?'Core Team':$defaults['author'];
		}
		if (!is_null($_hook_bits[7]))
		{
			$organisation=is_array($_hook_bits[7])?call_user_func_array($_hook_bits[7][0],$_hook_bits[7][1]):@eval($_hook_bits[7]);
		} else
		{
			$organisation=$is_orig?'ocProducts':$defaults['organisation'];
		}
		if (is_null($_hook_bits[8]))
		{
			$file_list=array();
		} else
		{
			$file_list=is_array($_hook_bits[8])?call_user_func_array($_hook_bits[8][0],$_hook_bits[8][1]):@eval($_hook_bits[8]);
		}

		$addon_info=array(
			'name'=>$addon,
			'author'=>$author,
			'organisation'=>$organisation,
			'version'=>float_to_raw_string($version,2,true),
			'category'=>$category,
			'copyright_attribution'=>$copyright_attribution,
			'licence'=>$licence,
			'description'=>$description,
			'install_time'=>filemtime($path),
			'files'=>$file_list,
			'dependencies'=>$dep['requires'],
			'incompatibilities'=>$dep['conflicts_with'],
		);
		if ($get_dependencies_on_this)
		{
			$addon_info['dependencies_on_this']=find_addon_dependencies_on($addon);
		}

		return $addon_info;
	}

	// Next try .ini file

	if (!is_null($ini_info))
	{
		$version=$ini_info['version'];
		if ($version=='(version-synched)') $version=float_to_raw_string(ocp_version_number(),2,true);

		$dependencies=array_key_exists('dependencies',$ini_info)?explode(',',$ini_info['dependencies']):array();
		$incompatibilities=array_key_exists('incompatibilities',$ini_info)?explode(',',$ini_info['incompatibilities']):array();

		$addon_info=array(
			'name'=>$ini_info['name'],
			'author'=>$ini_info['author'],
			'organisation'=>$ini_info['organisation'],
			'version'=>$version,
			'category'=>$ini_info['category'],
			'copyright_attribution'=>explode("\n",$ini_info['addon_copyright_attribution']),
			'licence'=>$ini_info['licence'],
			'description'=>$ini_info['description'],
			'install_time'=>time(),
			'files'=>$ini_info['files'],
			'dependencies'=>$dependencies,
			'incompatibilities'=>$incompatibilities,
		);
		if ($get_dependencies_on_this)
		{
			$addon_info['dependencies_on_this']=find_addon_dependencies_on($addon);
		}

		return $addon_info;
	}

	// Next try what is in the database...

	if (is_null($row))
	{
		$addon_rows=$GLOBALS['SITE_DB']->query_select('addons',array('*'),array('addon_name'=>$addon));
		if (array_key_exists(0,$addon_rows))
		{
			$row=$addon_rows[0];
		}
	}

	if (!is_null($row))
	{
		$addon_info=array(
			'name'=>$row['addon_name'],
			'author'=>$row['addon_author'],
			'organisation'=>$row['addon_organisation'],
			'version'=>$row['addon_version'],
			'category'=>$row['addon_category'],
			'copyright_attribution'=>explode("\n",$row['addon_copyright_attribution']),
			'licence'=>$row['addon_licence'],
			'description'=>$row['addon_description'],
			'install_time'=>$row['addon_install_time'],
		);

		$addon_info['files']=array_unique(collapse_1d_complexity('filename',$GLOBALS['SITE_DB']->query_select('addons_files',array('filename'),array('addon_name'=>$addon))));
		$addon_info['dependencies']=collapse_1d_complexity('addon_name_dependant_upon',$GLOBALS['SITE_DB']->query_select('addons_dependencies',array('addon_name_dependant_upon'),array('addon_name'=>$addon,'addon_name_incompatibility'=>0)));
		$addon_info['incompatibilities']=collapse_1d_complexity('addon_name_dependant_upon',$GLOBALS['SITE_DB']->query_select('addons_dependencies',array('addon_name_dependant_upon'),array('addon_name'=>$addon,'addon_name_incompatibility'=>1)));
		if ($get_dependencies_on_this)
		{
			$addon_info['dependencies_on_this']=find_addon_dependencies_on($addon);
		}
		return $addon_info;
	}

	warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
}

/**
 * Find the icon for an addon.
 *
 * @param  ID_TEXT	Addon name
 * @param  boolean	Whether to use a default icon
 * @param  ?PATH		Path to tar file (NULL: don't look inside a TAR / it's installed already)
 * @return string		Theme image URL (may be a "data:" URL rather than a normal URLPATH)
 */
function find_addon_icon($addon_name,$pick_default=true,$tar_path=NULL)
{
	$matches=array();

	if (!is_null($tar_path))
	{
		require_code('tar');
		$tar_file=tar_open($tar_path,'rb');
		$directory=tar_get_directory($tar_file,true);
		if (!is_null($directory))
		{
			foreach ($directory as $d)
			{
				$file=$d['path'];
				if ((preg_match('themes/default/(images|images_custom)/icons/48x48/(.*)\.(png|jpg|jpeg|gif)',$file,$matches)!-0) && (!array_key_exists($addon_name,$addon_icons)))
				{
					require_code('mime_types');
					$data=tar_get_file($tar_file,$file);
					return 'data:'.get_mime_type(get_file_extension($file),true).';base64,'.base64_encode($data['data']);
				}
			}
		}
	} else
	{
		$addon_info=read_addon_info($addon_name);
		$addon_files=$addon_info['files'];
		foreach ($addon_files as $file)
		{
			if ((preg_match('themes/default/(images|images_custom)/icons/48x48/(.*)\.(png|jpg|jpeg|gif)',$file,$matches)!-0) && (!array_key_exists($addon_name,$addon_icons)))
			{
				return find_theme_image('icons/48x48/'.$matches[2]));
			}
		}
	}

	// Default, as not found
	return $pick_default?find_theme_image('icons/48x48/menu/_generic_admin/component'):NULL;
}
