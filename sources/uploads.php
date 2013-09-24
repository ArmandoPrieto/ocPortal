<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

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
 * Standard code module initialisation function.
 */
function init__uploads()
{
	if (function_exists('set_time_limit')) @set_time_limit(0); // On some server setups, slow uploads can trigger the time-out

	if (!defined('OCP_UPLOAD_ANYTHING'))
	{
		define('OCP_UPLOAD_IMAGE',1);
		define('OCP_UPLOAD_VIDEO',2);
		define('OCP_UPLOAD_AUDIO',4);
		define('OCP_UPLOAD_SWF',8); // Banners
		define('OCP_UPLOAD_ANYTHING',15);
	}
}

/**
 * Find whether an swfupload upload has just happened, and optionally simulate as if it were a normal upload (although 'is_uploaded_file'/'move_uploaded_file' would not work).
 *
 * @param  boolean		Simulate population of the $_FILES array.
 * @return boolean		Whether an swfupload upload has just happened.
 */
function is_swf_upload($fake_prepopulation=false)
{
	static $done_fake_prepopulation=false;

	//check whatever is used the swfuploader
	$swfupload=false;
	$rolling_offset=0;
	foreach ($_POST as $key=>$value)
	{
		if (!is_string($value)) continue;	

		if ((preg_match('#^hidFileID\_#i',$key)!=0) && ($value!='-1'))
		{
			// Get the incoming uploads appropiate database table row
			if (substr($value,-4)=='.dat') // By .dat name
			{
				$filename=post_param(str_replace('hidFileID','hidFileName',$key),'');
				if ($filename=='') continue; // Was cancelled during plupload, but plupload can't cancel so was allowed to finish. So we have hidFileID but not hidFileName.

				$path='uploads/incoming/'.filter_naughty($value);
				if (file_exists(get_custom_file_base().'/'.$path))
				{
					$swfupload=true;
					if ($fake_prepopulation)
					{
						$_FILES[substr($key,10)]=array(
							'type'=>'swfupload',
							'name'=>$filename,
							'tmp_name'=>get_custom_file_base().'/'.$path,
							'size'=>filesize(get_custom_file_base().'/'.$path)
						);
					}
				}
			} else // By incoming upload ID
			{
				$rolling_offset=0; // We do assume that if we have multiple multi-file fields in the same space that they are spaced with a large enough gap; so we don't maintain a rolling offset between fields
				foreach (array_map('intval',explode(':',$value)) as $i=>$incoming_uploads_id) // Some uploaders may delimite with ":" within a single POST field (plupload); others may give multiple POST fields (swfupload, native)
				{
					$incoming_uploads_row=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'incoming_uploads WHERE (i_submitter='.strval(get_member()).' OR i_submitter='.strval($GLOBALS['FORUM_DRIVER']->get_guest_id()).') AND id='.strval($incoming_uploads_id),1);
					if (array_key_exists(0,$incoming_uploads_row))
					{
						if (file_exists(get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url']))
						{
							$swfupload=true;
							if ($fake_prepopulation)
							{
								if (!$done_fake_prepopulation)
								{
									$new_key=$key;
									$matches=array();
									if (preg_match('#^hidFileID\_(.*)(\d+)$#',$key,$matches)!=0)
									{
										$new_key=$matches[1].strval(intval($matches[2])+$rolling_offset);
									} else $new_key=substr($key,10);
									$_FILES[$new_key]=array(
										'type'=>'swfupload',
										'name'=>$incoming_uploads_row[0]['i_orig_filename'],
										'tmp_name'=>get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url'],
										'size'=>filesize(get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url'])
									);
									$_POST['hidFileID_'.$new_key]=strval($incoming_uploads_id);

									$rolling_offset++;
								}
							}
						}
					}
				}
			}
		}
	}

	if ($swfupload)
	{
		// Filter out vestigial files (been reported as an issue)
		foreach (array_keys($_FILES) as $attach_name)
		{
			if ((array_key_exists($attach_name,$_FILES)) && (array_key_exists('error',$_FILES[$attach_name])))
			{
				if ($_FILES[$attach_name]['error']==3)
				{
					unset($_FILES[$attach_name]);
				}
			}
		}
	}

	if ($fake_prepopulation) $done_fake_prepopulation=true;

	return $swfupload;
}

/**
 * Get URLs generated according to the specified information. It can also generate a thumbnail if required. It first tries attached upload, then URL, then fails.
 *
 * @param  ID_TEXT		The name of the POST parameter storing the URL (if '', then no POST parameter). Parameter value may be blank.
 * @param  ID_TEXT		The name of the HTTP file parameter storing the upload (if '', then no HTTP file parameter). No file necessarily is uploaded under this.
 * @param  ID_TEXT		The folder name in uploads/ where we will put this upload
 * @param  integer		Whether to obfuscate file names so the URLs can not be guessed/derived (0=do not, 1=do, 2=make extension .dat as well, 3=only obfuscate if we need to)
 * @set    0 1 2 3
 * @param  integer		The type of upload it is (bitmask, from OCP_UPLOAD_* constants)
 * @param  boolean		Make a thumbnail (this only makes sense, if it is an image)
 * @param  ID_TEXT		The name of the POST parameter storing the thumb URL. As before
 * @param  ID_TEXT		The name of the HTTP file parameter storing the thumb upload. As before
 * @param  boolean		Whether to copy a URL (if a URL) to the server, and return a local reference
 * @param  boolean		Whether to accept upload errors
 * @param  boolean		Whether to give a (deferred?) error if no file was given at all
 * @param  boolean		Whether to apply a 'never make the image bigger' rule for thumbnail creation (would affect very small images)
 * @param  ?MEMBER		Member ID to run permissions with (NULL: current member)
 * @return array			An array of 4 URL bits (URL, thumb URL, URL original filename, thumb original filename)
 */
function get_url($specify_name,$attach_name,$upload_folder,$obfuscate=0,$enforce_type=15,$make_thumbnail=false,$thumb_specify_name='',$thumb_attach_name='',$copy_to_server=false,$accept_errors=false,$should_get_something=false,$only_make_smaller=false,$member_id=NULL)
{
	require_code('files2');

	if (is_null($member_id)) $member_id=get_member();

	$upload_folder=filter_naughty($upload_folder);
	$out=array();
	$thumb=NULL;

	$swf_uploaded=false;
	$swf_uploaded_thumb=false;
	foreach (array($attach_name,$thumb_attach_name) as $i=>$_attach_name)
	{
		if ($_attach_name=='') continue;

		// Check if it is an incoming upload
		$row_id_file='hidFileID_'.$_attach_name;
		$row_id_file_value=post_param($row_id_file,NULL);
		if ($row_id_file_value=='-1') $row_id_file_value=NULL;

		// ID of the upload from the incoming uploads database table
		if (!is_null($row_id_file_value)) // SwfUploader was used
		{
			// Get the incoming upload's appropiate DB table row
			if ((substr($row_id_file_value,-4)=='.dat') && (strpos($row_id_file_value,':')===false))
			{
				$path='uploads/incoming/'.filter_naughty($row_id_file_value);
				if (file_exists(get_custom_file_base().'/'.$path))
				{
					$_FILES[$_attach_name]=array('type'=>'swfupload','name'=>post_param(str_replace('hidFileID','hidFileName',$row_id_file)),'tmp_name'=>get_custom_file_base().'/'.$path,'size'=>filesize(get_custom_file_base().'/'.$path));
					if ($i==0)
					{
						$swf_uploaded=true;
					} else
					{
						$swf_uploaded_thumb=true;
					}
				}
			} else
			{
				$incoming_uploads_id=intval(preg_replace('#:.*$#','',$row_id_file_value));
				$incoming_uploads_row=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'incoming_uploads WHERE (i_submitter='.strval(get_member()).' OR i_submitter='.strval($GLOBALS['FORUM_DRIVER']->get_guest_id()).') AND id='.strval($incoming_uploads_id),1);
				// If there is a DB record, proceed
				if (array_key_exists(0,$incoming_uploads_row))
				{
					if (file_exists(get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url']))
					{
						$_FILES[$_attach_name]=array('type'=>'swfupload','name'=>$incoming_uploads_row[0]['i_orig_filename'],'tmp_name'=>get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url'],'size'=>filesize(get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url']));
						if ($i==0)
						{
							$swf_uploaded=true;
						} else
						{
							$swf_uploaded_thumb=true;
						}
					}
				}
			}
		}
	}

	if ($obfuscate==3) $accept_errors=true;

	$thumb_folder=(strpos($upload_folder,'uploads/galleries')!==false)?str_replace('uploads/galleries','uploads/galleries_thumbs',$upload_folder):($upload_folder.'_thumbs');

	if (!file_exists(get_custom_file_base().'/'.$upload_folder))
	{
		$success=@mkdir(get_custom_file_base().'/'.$upload_folder,0777);
		if ($success===false) warn_exit(@strval($php_errormsg));
		fix_permissions(get_custom_file_base().'/'.$upload_folder,0777);
		sync_file($upload_folder);
	}
	if ((!file_exists(get_custom_file_base().'/'.$thumb_folder)) && ($make_thumbnail))
	{
		$success=@mkdir(get_custom_file_base().'/'.$thumb_folder,0777);
		if ($success===false) warn_exit(@strval($php_errormsg));
		fix_permissions(get_custom_file_base().'/'.$thumb_folder,0777);
		sync_file($thumb_folder);
	}

	// Find URL
	require_code('images');
	if ((($enforce_type & OCP_UPLOAD_VIDEO)!=0) || (($enforce_type & OCP_UPLOAD_AUDIO)!=0))
	{
		require_code('files2');
		$max_size=get_max_file_size();
	} else
	{
		$max_size=get_max_image_size();
	}
	if (($attach_name!='') && (array_key_exists($attach_name,$_FILES)) && ((is_uploaded_file($_FILES[$attach_name]['tmp_name'])) || ($swf_uploaded))) // If we uploaded
	{
		if (!has_privilege($member_id,'exceed_filesize_limit'))
		{
			if ($_FILES[$attach_name]['size']>$max_size)
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)));
				}
			}
		}

		$url=_get_upload_url($member_id,$attach_name,$upload_folder,$enforce_type,$obfuscate,$accept_errors);
		if ($url==array('','')) return array('','','','');

		$is_image=is_image($_FILES[$attach_name]['name']);
	}
	elseif (post_param($specify_name,'')!='') // If we specified
	{
		$url=_get_specify_url($member_id,$specify_name,$upload_folder,$enforce_type,$accept_errors);
		$is_image=is_image($url[0]);
		if ($url[0]!='')
		{
			if ($enforce_type==OCP_UPLOAD_IMAGE) $is_image=true; // Must be an image if it got to here. Maybe came from oEmbed and not having an image extension.
		}
		if ($url==array('','')) return array('','','','');
		if (($copy_to_server) && (!url_is_local($url[0])))
		{
			$path2=ocp_tempnam('ocpfc');
			$tmpfile=fopen($path2,'wb');

			$file=http_download_file($url[0],$max_size,true,false,'ocPortal',NULL,NULL,NULL,NULL,NULL,$tmpfile);
			fclose($tmpfile);
			if (is_null($file))
			{
				@unlink($path2);
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('CANNOT_COPY_TO_SERVER'),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('CANNOT_COPY_TO_SERVER'));
				}
			}
			global $HTTP_FILENAME;
			if (is_null($HTTP_FILENAME)) $HTTP_FILENAME=$url[1];

			if (!check_extension($HTTP_FILENAME,$obfuscate==2,$path2,$accept_errors))
			{
				if ($obfuscate==3) // We'll try again, with obfuscation to see if this would get through
				{
					$obfuscate=2;
					if (!check_extension($HTTP_FILENAME,$obfuscate==2,$path2,$accept_errors))
					{
						return array('','','','');
					}
				} else
				{
					return array('','','','');
				}
			}

			if (url_is_local($url[0]))
			{
				unlink($path2);
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('CANNOT_COPY_TO_SERVER'),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('CANNOT_COPY_TO_SERVER'));
				}
			}
			if (($obfuscate!=0) && ($obfuscate!=3))
			{
				$ext=(($obfuscate==2) && (!is_image($HTTP_FILENAME)))?'dat':get_file_extension($HTTP_FILENAME);

				$_file=preg_replace('#\..*\.#','.',$HTTP_FILENAME).((substr($HTTP_FILENAME,-strlen($ext)-1)=='.'.$ext)?'':('.'.$ext));
				$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
				while (file_exists($place))
				{
					$_file=uniqid('',true).'.'.$ext;
					$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
				}
			} else
			{
				$_file=$HTTP_FILENAME;
				$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
			}
			if (!has_privilege($member_id,'exceed_filesize_limit'))
			{
				$max_size=intval(get_option('max_download_size'))*1024;
				if (strlen($file)>$max_size)
				{
					if ($accept_errors)
					{
						attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)),'warn');
						return array('','','','');
					} else
					{
						warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)));
					}
				}
			}
			$result=@rename($path2,$place);
			global $HTTP_DOWNLOAD_MTIME;
			if (!is_null($HTTP_DOWNLOAD_MTIME)) @touch($place,$HTTP_DOWNLOAD_MTIME);
			if (!$result)
			{
				unlink($path2);
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('WRITE_ERROR',escape_html($upload_folder)),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('WRITE_ERROR',escape_html($upload_folder)));
				}
			}
			fix_permissions($place);
			sync_file($place);

			$url[0]=$upload_folder.'/'.$_file;
			if (strpos($HTTP_FILENAME,'/')===false) $url[1]=$HTTP_FILENAME;
		}
	} else // Uh oh
	{
		if ((array_key_exists($attach_name,$_FILES)) && (array_key_exists('error',$_FILES[$attach_name])) && (($_FILES[$attach_name]['error']!=4) || ($should_get_something)) && ($_FILES[$attach_name]['error']!=0)) // If we uploaded
		{
			if ($_FILES[$attach_name]['error']==1)
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)));
				}
			}
			elseif ($_FILES[$attach_name]['error']==2)
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('FILE_TOO_BIG_QUOTA',integer_format($max_size)),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('FILE_TOO_BIG_QUOTA',integer_format($max_size)));
				}
			}
			elseif (($_FILES[$attach_name]['error']==3) || ($_FILES[$attach_name]['error']==4) || ($_FILES[$attach_name]['error']==6) || ($_FILES[$attach_name]['error']==7))
			{
				attach_message(do_lang_tempcode('ERROR_UPLOADING_'.strval($_FILES[$attach_name]['error'])),'warn');
				return array('','','','');
			} else
			{
				warn_exit(do_lang_tempcode('ERROR_UPLOADING_'.strval($_FILES[$attach_name]['error'])));
			}
		}

		$url[0]='';
		$url[1]='';
		$is_image=false;
	}

	$out[0]=$url[0];
	$out[2]=$url[1];

	// Generate thumbnail if needed
	if (($make_thumbnail) && ($url[0]!='') && ($is_image))
	{
		if ((array_key_exists($thumb_attach_name,$_FILES)) && ((is_uploaded_file($_FILES[$thumb_attach_name]['tmp_name'])) || ($swf_uploaded_thumb))) // If we uploaded
		{
			if ($_FILES[$thumb_attach_name]['size']>get_max_image_size())
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format(get_max_image_size())),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format(get_max_image_size())));
				}
			}

			$_thumb=_get_upload_url($member_id,$thumb_attach_name,$thumb_folder,OCP_UPLOAD_IMAGE,0,$accept_errors);
			$thumb=$_thumb[0];
		}
		elseif (array_key_exists($thumb_specify_name,$_POST)) // If we specified
		{
			$_thumb=_get_specify_url($member_id,$thumb_specify_name,$thumb_folder,OCP_UPLOAD_IMAGE,$accept_errors);
			$thumb=$_thumb[0];
		} else
		{
			$gd=((get_option('is_on_gd')=='1') && (function_exists('imagetypes')));

			if ($gd)
			{
				if ((!is_saveable_image($url[0])) && (get_file_extension($url[0])!='svg')) $ext='.png'; else $ext='';
				$file=basename($url[0]);
				$_file=$file;
				$place=get_custom_file_base().'/'.$thumb_folder.'/'.$_file.$ext;
				$i=2;
				while (file_exists($place))
				{
					$_file=strval($i).$file;
					$place=get_custom_file_base().'/'.$thumb_folder.'/'.$_file.$ext;
					$i++;
				}
				$url_full=url_is_local($url[0])?get_custom_base_url().'/'.$url[0]:$url[0];

				convert_image($url_full,$place,-1,-1,intval(get_option('thumb_width')),true,NULL,false,$only_make_smaller);

				$thumb=$thumb_folder.'/'.rawurlencode($_file).$ext;
			} else
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('GD_THUMB_ERROR'),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('GD_THUMB_ERROR'));
				}
			}
		}

		$out[1]=$thumb;
	}
	elseif ($make_thumbnail)
	{
		if ((array_key_exists($thumb_attach_name,$_FILES)) && ((is_uploaded_file($_FILES[$thumb_attach_name]['tmp_name'])) || ($swf_uploaded_thumb))) // If we uploaded
		{
			if ($_FILES[$thumb_attach_name]['size']>get_max_image_size())
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format(get_max_image_size())),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format(get_max_image_size())));
				}
			}

			$_thumb=_get_upload_url($member_id,$thumb_attach_name,$thumb_folder,OCP_UPLOAD_IMAGE,0,$accept_errors);
			$thumb=$_thumb[0];
		}
		elseif (array_key_exists($thumb_specify_name,$_POST))
		{
			$_thumb=_get_specify_url($member_id,$thumb_specify_name,$thumb_folder,OCP_UPLOAD_IMAGE,$accept_errors);
			$thumb=$_thumb[0];
		}
		if (!is_null($thumb))
			$out[1]=$thumb;
		else $out[1]='';
	}

	// For reentrance of previews
	if ($specify_name!='') $_POST[$specify_name]=array_key_exists(0,$out)?$out[0]:'';
	if ($thumb_specify_name!='') $_POST[$thumb_specify_name]=array_key_exists(1,$out)?$out[1]:'';

	return $out;
}

/**
 * Filters specified URLs to make sure we're really allowed to access them.
 *
 * @param  MEMBER			Member ID to check permissions with.
 * @param  ID_TEXT		The name of the POST parameter storing the URL (if '', then no POST parameter). Parameter value may be blank.
 * @param  ID_TEXT		The folder name in uploads/ where we will put this upload
 * @param  integer		The type of upload it is (bitmask, from OCP_UPLOAD_* constants)
 * @param  boolean		Whether to accept upload errors
 * @return array			A pair: the URL and the filename
 */
function _get_specify_url($member_id,$specify_name,$upload_folder,$enforce_type=15,$accept_errors=false)
{
	// Security check against naughty url's
	$url=array();
	$url[0]=/*filter_naughty*/(post_param($specify_name));
	$url[1]=rawurldecode(basename($url[0]));

	// If this is a relative URL then it may be downloaded through a PHP script.
	//  So lets check we are allowed to download it!
	if (($url[0]!='') && (url_is_local($url[0])))
	{
		$missing_ok=false;

		// Its not in the upload folder, so maybe we aren't allowed to download it
		if (((substr($url[0],0,strlen($upload_folder)+1)!=$upload_folder.'/') && (substr($url[0],0,strlen('data/images/')+1)!='data/images/')) || (strpos($url[0],'..')!==false))
		{
			$myfile=@fopen(get_custom_file_base().'/'.rawurldecode($url[0]),'rb');
			if ($myfile!==false)
			{
				$shouldbe=fread($myfile,8000);
				fclose($myfile);
			} else $shouldbe=NULL;
			global $HTTP_MESSAGE;
			$actuallyis=http_download_file(get_custom_base_url().'/'.$url[0],8000,false);

			if (($HTTP_MESSAGE=='200') && (is_null($shouldbe)))
			{
				// No error downloading, but error using file system - therefore file exists and we'll use URL to download. Hence no security check.
				$missing_ok=true;
			} else
			{
				if (@strcmp(substr($shouldbe,0,8000),substr($actuallyis,0,8000))!=0)
				{
					log_hack_attack_and_exit('TRY_TO_DOWNLOAD_SCRIPT');
				}
			}
		}

		// Check the file exists
		if ((!file_exists(get_custom_file_base().'/'.rawurldecode($url[0]))) && (!$missing_ok))
		{
			if ($accept_errors)
			{
				attach_message(do_lang_tempcode('MISSING_FILE'),'warn');
				return array('','');
			} else
			{
				warn_exit(do_lang_tempcode('MISSING_FILE'));
			}
		}
	}

	if ($url[0]!='')
	{
		// oEmbed etc
		if (($enforce_type!=OCP_UPLOAD_ANYTHING) && (($enforce_type & OCP_UPLOAD_IMAGE)!=0) && (!is_image($url[0])) && ((($enforce_type & OCP_UPLOAD_SWF)==0) || (get_file_extension($url[0])!='swf')))
		{
			require_code('media_renderer');
			require_code('files2');
			$meta_details=get_webpage_meta_details($url[0]);
			require_code('hooks/systems/media_rendering/oembed');
			$oembed_ob=object_factory('Hook_media_rendering_oembed');
			if ($oembed_ob->recognises_mime_type($meta_details['t_mime_type'],$meta_details) || $oembed_ob->recognises_url($url[0]))
			{
				$oembed=$oembed_ob->get_oembed_data_result($url[0],array('width'=>'1280','height'=>'1024'));
				if (($oembed!==NULL) && ($oembed['type']=='photo'))
				{
					$url[0]=preg_replace('#.*(https?://)#','${1}',$oembed['thumbnail_url']); // Get thumbnail, but strip noembed.com (for example) resizer-proxy prefix if there
					$url[1]=basename(urldecode($url[0]));
					return $url;
				}
			}
			if (substr($meta_details['t_mime_type'],0,6)=='image/')
			{
				return $url;
			}
			if ($meta_details['t_image_url']!='')
			{
				$url[0]=$meta_details['t_image_url'];
				$url[1]=basename(urldecode($url[0]));
				return $url;
			}
		}

		if (!_check_enforcement_of_type($member_id,$url[0],$enforce_type,$accept_errors)) return array('','');
	}

	return $url;
}

/**
 * Ensures a given filename is of the right file extension for the desired file type.
 *
 * @param  MEMBER			Member ID to check permissions with.
 * @param  string			The filename.
 * @param  integer		The type of upload it is (bitmask, from OCP_UPLOAD_* constants)
 * @param  boolean		Whether to accept upload errors
 * @return boolean		Success status
 */
function _check_enforcement_of_type($member_id,$file,$enforce_type,$accept_errors=false)
{
	if (($enforce_type & OCP_UPLOAD_ANYTHING)!=0) return true;

	require_code('images');
	$ok=false;
	if (($enforce_type & OCP_UPLOAD_SWF)!=0)
	{
		if (get_file_extension($file)!='swf')
		{
			if ($enforce_type==OCP_UPLOAD_SWF)
			{
				if ($accept_errors)
					attach_message(do_lang_tempcode('NOT_IMAGE'),'warn');
				else
					warn_exit(do_lang_tempcode('NOT_IMAGE'));
				return false;
			}
		} else
		{
			$ok=true;
		}
	}
	if (($enforce_type & OCP_UPLOAD_IMAGE)!=0)
	{
		if (!is_image($file))
		{
			if ($enforce_type==OCP_UPLOAD_IMAGE)
			{
				if ($accept_errors)
					attach_message(do_lang_tempcode('NOT_IMAGE'),'warn');
				else
					warn_exit(do_lang_tempcode('NOT_IMAGE'));
				return false;
			}
		} else
		{
			$ok=true;
		}
	}
	if (($enforce_type & OCP_UPLOAD_VIDEO)!=0)
	{
		if (!is_video($file,has_privilege($member_id,'comcode_dangerous'),false))
		{
			if ($enforce_type==OCP_UPLOAD_VIDEO)
			{
				if ($accept_errors)
					attach_message(do_lang_tempcode('NOT_VIDEO'),'warn');
				else
					warn_exit(do_lang_tempcode('NOT_VIDEO'));
				return false;
			}
		} else
		{
			$ok=true;
		}
	}
	if (($enforce_type & OCP_UPLOAD_AUDIO)!=0)
	{
		if (!is_audio($file,has_privilege($member_id,'comcode_dangerous')))
		{
			if ($enforce_type==OCP_UPLOAD_AUDIO)
			{
				if ($accept_errors)
					attach_message(do_lang_tempcode('NOT_AUDIO'),'warn');
				else
					warn_exit(do_lang_tempcode('NOT_AUDIO'));
				return false;
			}
		} else
		{
			$ok=true;
		}
	}
	if (!$ok)
	{
		if ($accept_errors)
			attach_message(do_lang_tempcode('_NOT_FILE_TYPE'),'warn');
		else
			warn_exit(do_lang_tempcode('_NOT_FILE_TYPE'));
		return false;
	}
	return true;
}

/**
 * Converts an uploaded file into a URL, by moving it to an appropriate place.
 *
 * @param  MEMBER			Member ID to check permissions with.
 * @param  ID_TEXT		The name of the HTTP file parameter storing the upload (if '', then no HTTP file parameter). No file necessarily is uploaded under this.
 * @param  ID_TEXT		The folder name in uploads/ where we will put this upload
 * @param  integer		The type of upload it is (bitmask, from OCP_UPLOAD_* constants)
 * @param  integer		Whether to obfuscate file names so the URLs can not be guessed/derived (0=do not, 1=do, 2=make extension .dat as well)
 * @set    0 1 2
 * @param  boolean		Whether to accept upload errors
 * @return array			A pair: the URL and the filename
 */
function _get_upload_url($member_id,$attach_name,$upload_folder,$enforce_type=15,$obfuscate=0,$accept_errors=false)
{
	$file=$_FILES[$attach_name]['name'];
	if (get_magic_quotes_gpc()) $file=stripslashes($file);

	if (!check_extension($file,$obfuscate==2,NULL,$accept_errors))
	{
		if ($obfuscate==3) // We'll try again, with obfuscation to see if this would get through
		{
			$obfuscate=2;
			if (!check_extension($file,$obfuscate==2,NULL,$accept_errors))
			{
				return array('','','','');
			}
		} else
		{
			return array('','','','');
		}
	}

	if (!_check_enforcement_of_type($member_id,$file,$enforce_type,$accept_errors)) return array('','');

	// If we are not obfuscating then we will need to search for an available filename
	if (($obfuscate==0) || ($obfuscate==3))
	{
		$_file=preg_replace('#\..*\.#','.',$file);
		$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
		$i=2;
		// Hunt with sensible names until we don't get a conflict
		while (file_exists($place))
		{
			$_file=strval($i).preg_replace('#\..*\.#','.',$file);
			$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
			$i++;
		}
	}
	else // A result of some randomness
	{
		$ext=get_file_extension($file);
		$ext=(($obfuscate==2) && (!is_image($file)))?'dat':get_file_extension($file);

		$_file=uniqid('',true).'.'.$ext;
		$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
		while (file_exists($place))
		{
			$_file=uniqid('',true).'.'.$ext;
			$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
		}
	}

	check_shared_space_usage($_FILES[$attach_name]['size']);

	// Copy there, and return our URL
	if ($_FILES[$attach_name]['type']!='swfupload')
	{
		$test=@move_uploaded_file($_FILES[$attach_name]['tmp_name'],$place);
	} else
	{
		$test=@copy($_FILES[$attach_name]['tmp_name'],$place); // We could rename, but it would hurt integrity of refreshes
	}
	if ($test===false)
	{
		if ($accept_errors)
		{
			$df=do_lang_tempcode('FILE_MOVE_ERROR',escape_html($file),escape_html($place));
			attach_message($df,'warn');
			return array('','');
		} else
		{
			warn_exit(do_lang_tempcode('FILE_MOVE_ERROR',escape_html($file),escape_html($place)));
		}
	}
	fix_permissions($place);
	sync_file($place);

	// Special code to re-orientate JPEG images if required (browsers cannot do this)
	if ((($enforce_type & OCP_UPLOAD_ANYTHING)==0) && (($enforce_type & OCP_UPLOAD_IMAGE)!=0) && (is_image($place)))
	{
		require_code('images');
		convert_image($place,$place,-1,-1,100000/*Impossibly large size, so no resizing happens*/,false,NULL,true,true);
	}

	$url=array();
	$url[0]=$upload_folder.'/'.rawurlencode($_file);
	$url[1]=$file;
	return $url;
}


