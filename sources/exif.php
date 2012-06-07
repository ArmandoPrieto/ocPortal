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
 * @package		core
 */

/**
 * Get meta data from an image, using EXIF primarily, but also XMP and IPTC to get image descriptions.
 * Also gets GPS data and canonicalises in decimal as Latitude and Longitude.
 *
 * @param  PATH		This is the path of the photo which may contain metadata
 * @param  ?string	This is the original filename of the photo which may contain metadata (NULL: derive from path)
 * @return array		Map of meta data, using standard EXIF naming
 */
function get_exif_data($path,$filename=NULL)
{
	$out=array();

	if (is_null($filename)) $filename=rawurldecode(basename($path));

	if (function_exists('exif_read_data'))
	{
		$meta_data=@exif_read_data($path,'ANY_TAG');

		if ($meta_data!==false)
		{
			$out+=cleanup_exif($meta_data);
		}
	}

	$caption=get_exif_image_caption($path,$filename);
	$out['UserComment']=$caption;

	$out+=_get_simple_gps($out);

	return $out;
}

/**
 * Work out canonical Latitude/Longitude details from complex EXIF bits.
 *
 * @param  array		EXIF data
 * @return array		Extra derived EXIF data
 */
function _get_simple_gps($exif)
{
	// Based on http://stackoverflow.com/questions/2526304/php-extract-gps-exif-data

	$result=array();

	if (!isset($exif['GPSLatitude'])) return array();
	if (!isset($exif['GPSLongitude'])) return array();

	// get the Hemisphere multiplier
	$lat_m=1; $long_m=1;
	if($exif['GPSLatitudeRef']=='S')
	{
		$lat_m=-1;
	}
	if($exif['GPSLongitudeRef']=='W')
	{
		$long_m=-1;
	}

	// get the GPS data
	$gps=array();
	if (!is_array($exif['GPSLatitude']))
	{
		$result['Latitude']=$exif['GPSLatitude'];
		$result['Latitude']=$exif['GPSLatitude'];
		return $result;
	}
	$gps['LatDegree']=$exif['GPSLatitude'][0];
	$gps['LatMinute']=$exif['GPSLatitude'][1];
	$gps['LatgSeconds']=isset($exif['GPSLatitude'][2])?$exif['GPSLatitude'][2]:0;
	$gps['LongDegree']=$exif['GPSLongitude'][0];
	$gps['LongMinute']=$exif['GPSLongitude'][1];
	$gps['LongSeconds']=isset($exif['GPSLongitude'][2])?$exif['GPSLongitude'][2]:0;

	// calculate the decimal degree
	$result['Latitude']=float_to_raw_string(floatval($lat_m) * ($gps['LatDegree'] + ($gps['LatMinute'] / 60.0) + ($gps['LatgSeconds'] / 3600.0)));
	$result['Longitude']=float_to_raw_string(floatval($long_m) * ($gps['LongDegree'] + ($gps['LongMinute'] / 60.0) + ($gps['LongSeconds'] / 3600.0)));

	return $result;
}

/**
 * Attempt to retrieve a caption from photos seeking XMP, then EXIF, then IPTC binary last.
 * Check this file is a valid image file before passing to this function as an empty string often annoys.
 *
 * @param  PATH		This is the path of the photo which may contain metadata
 * @param  string		This is the original filename of the photo which may contain metadata
 * @return string		Whichever caption is found
 */
function get_exif_image_caption($path,$filename)
{
	$comments='';

	// Try CSV file
	$csv_path=get_custom_file_base().'/uploads/galleries/descriptions.csv';
	if (file_exists($csv_path))
	{
		$del=',';

		$csv_file_handle=fopen($csv_path,'rb');
		$csv_test_line=fgetcsv($csv_file_handle,10240,$del);
		if ((count($csv_test_line)==1) && (strpos($csv_test_line[0],';')!==false))
			$del=';';
		rewind($csv_file_handle);
		while (($csv_line=fgetcsv($csv_file_handle,10240,$del))!==false)
		{
			if (preg_match('#(^|/|\\\\)'.str_replace('#','\#',preg_quote(trim($csv_line[0]))).'#',$filename)!=0)
			{
				$comments=trim($csv_line[1]);
				break;
			}
		}
		fclose($csv_file_handle);
	}

	$file_pointer=fopen($path,'rb');

	if (($comments=='') && ($file_pointer!==false)) //Attempt XMP
	{
		$file_cap100=fread($file_pointer,102400); //Read first 100k

		$x_start=strpos($file_cap100,'<x:xmpmeta');
		$x_end=strpos($file_cap100,'</x:xmpmeta');
		if (($x_start!==false) && ($x_end!==false))
			$file_cap=substr($file_cap100, $x_start, ($x_end+12)-$x_start);
		else
			$file_cap=substr($file_cap100, $x_start);

		if (isset($file_cap))
		{
			$get_result=array();

			preg_match('/<photoshop:Headline>(.*)<\/photoshop:Headline>/',$file_cap,$get_result); //Headline
			if (array_key_exists(1,$get_result))
				$comments=$get_result[1];
			else
			{
				preg_match('/<dc:title[^>]*>\s*<rdf:Alt[^>]*>\s*<rdf:li[^>]*>(.*)<\/rdf:li>\s*<\/rdf:Alt>\s*<\/dc:title>/',$file_cap,$get_result); //Title
				if (array_key_exists(1,$get_result))
				{
					$comments=$get_result[1];
				} else
				{
					preg_match('/<dc:description[^>]*>\s*<rdf:Alt[^>]*>\s*<rdf:li[^>]*>(.*)<\/rdf:li>\s*<\/rdf:Alt>\s*<\/dc:description>/',$file_cap,$get_result); //Description
					if (array_key_exists(1,$get_result))
						$comments=$get_result[1];
				}
			}
		}

		fclose($file_pointer);
	}
	if ((function_exists('exif_read_data')) && ($comments=='')) //If XMP fails, attempt EXIF
	{
		$meta_data=@exif_read_data($path);

		if ($meta_data!==false)
		{
			$meta_data=cleanup_exif($meta_data);

			$comments=isset($meta_data['ImageDescription'])?$meta_data['ImageDescription']:'';
			if ($comments=='')
			{
				$comments=isset($meta_data['Comments'])?$meta_data['Comments']:'';
			}
			if ($comments=='')
			{
				$comments=isset($meta_data['Title'])?$meta_data['Title']:'';
			}
			if ($comments=='')
			{
				$comments=isset($meta_data['COMPUTED']['UserComment'])?$meta_data['COMPUTED']['UserComment']:'';
			}
		}
	}
	if ($comments=='') //IF XMP and EXIF fail, attempt IPTC binary
	{
		if ((function_exists('iptcparse')) && (function_exists('getimagesize')))
		{
			$meta_data2=array();
			getimagesize($path,$meta_data2);
			if (isset($meta_data2['APP13']))
			{
				$meta_data2=iptcparse($meta_data2['APP13']);

				if (is_array($meta_data2))
				{
					if (array_key_exists('2#105',$meta_data2)) //Headline 256 bytes
					{
						if (array_key_exists(0,$meta_data2['2#105']))
							$comments=$meta_data2['2#105'][0];
					}
					elseif (array_key_exists('2#121',$meta_data2)) //Local-Caption 256 bytes
					{
						if (array_key_exists(0,$meta_data2['2#121']))
							$comments=$meta_data2['2#121'][0];
					}
					elseif (array_key_exists('2#120',$meta_data2)) //Caption-Abstract (AKA description) 2000 bytes
					{
						if (array_key_exists(0,$meta_data2['2#120']))
							$comments=$meta_data2['2#120'][0];
					}
				}
			}
		}
	}

	// Remove pointless camera names that some vendors put in
	if (strpos($comments,'SONY')!==false) $comments='';
	if (strpos($comments,'CANON')!==false) $comments='';
	if (strpos($comments,'NIKON')!==false) $comments='';
	if (strpos($comments,'OLYMPUS')!==false) $comments='';

	return $comments;
}

/**
 * Save meta data into content type's custom fields, by looking for fields named after the EXIF/EXIF-emulated meta data (specifically in English).
 * Spaces may be added to the names to make them prettier, but otherwise they must be the same.
 * Designed to be used by headless-importers, e.g. bulk importing of media files, to make the process a bit smarter.
 *
 * @param  ID_TEXT	The content type
 * @param  ID_TEXT	The content ID
 * @param  array		The EXIF data
 * @param  ?array		Extra meta data to store, against explicit field IDs (NULL: none)
 */
function store_exif($content_type,$content_id,$exif,$map=NULL)
{
	require_code('fields');

	if (!has_tied_catalogue($content_type)) return;

	// Get field values
	$fields=$GLOBALS['SITE_DB']->query_select('catalogue_fields',array('id','cf_name'),array('c_name'=>'_'.$content_type),'ORDER BY cf_order');
	if (is_null($map)) $map=array();
	foreach ($fields as $field)
	{
		$name=get_translated_text($field['cf_name'],NULL,'EN');

		if (isset($exif[$name]))
			$map[$field['id']]=$exif[$name];
		elseif (isset($exif[str_replace(' ','',$name)]))
			$map[$field['id']]=$exif[str_replace(' ','',$name)];
		elseif (!isset($map[$field['id']])) $map[$field['id']]='';
	}
	if (count($map)==0) return;

	$first_cat=$GLOBALS['SITE_DB']->query_value_null_ok('catalogue_categories','MIN(id)',array('c_name'=>'_'.$content_type));

	require_code('catalogues2');

	$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogue_entry_linkage','catalogue_entry_id',array(
		'content_type'=>$content_type,
		'content_id'=>$content_id,
	));
	if (is_null($test))
	{
		$catalogue_entry_id=actual_add_catalogue_entry($first_cat,1,'',0,0,0,$map);

		$GLOBALS['SITE_DB']->query_insert('catalogue_entry_linkage',array(
			'catalogue_entry_id'=>$catalogue_entry_id,
			'content_type'=>$content_type,
			'content_id'=>$content_id,
		));
	} else
	{
		// Cannot handle this
	}
}

/**
 * Cleanup some EXIF, to the correct character set.
 *
 * @param  array		The EXIF data
 * @return array		Cleaned up EXIF data
 */
function cleanup_exif($meta_data)
{
	require_code('character_sets');
	$val=mixed();
	foreach ($meta_data as $key=>$val)
	{
		// Cleanup fractions
		if (is_string($val))
		{
			if (preg_match('#^[\d.]+/[\d.]+$#',$val)!=0)
			{
				$temp=explode('/',$val);
				if ((is_numeric($temp[0])) && (is_numeric($temp[1])))
				{
					if (floatval($temp[1])==0.0)
					{
						$val=floatval($temp[0]);
					} else
					{
						$val=floatval($temp[0])/floatval($temp[1]);
					}
					if ($key=='FocalLength') $val.='mm';
				}
			}
		}

		// Fix character sets
		if (is_string($val))
		{
			$val=preg_replace('#[[:cntrl:]]#','',$val);
			$val=convert_to_internal_encoding($val,'ISO-8859-1'/*EXIF uses this, is not really internationalised*/);
		} elseif (is_array($val))
		{
			$val=cleanup_exif($val);
		}

		$meta_data[$key]=$val;
	}
	return $meta_data;
}
