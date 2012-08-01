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
 * Standard code module initialisation function.
 */
function init__files2()
{
	global $HTTP_DOWNLOAD_MIME_TYPE,$HTTP_DOWNLOAD_SIZE,$HTTP_DOWNLOAD_URL,$HTTP_MESSAGE,$HTTP_MESSAGE_B,$HTTP_NEW_COOKIES,$HTTP_FILENAME,$HTTP_CHARSET;
	$HTTP_DOWNLOAD_MIME_TYPE=NULL;
	$HTTP_DOWNLOAD_SIZE=NULL;
	$HTTP_DOWNLOAD_URL=NULL;
	$HTTP_MESSAGE=NULL;
	$HTTP_MESSAGE_B=NULL;
	$HTTP_NEW_COOKIES=NULL;
	$HTTP_FILENAME=NULL;
	$HTTP_CHARSET=NULL;
}

/**
 * Provides a hook for file-move synchronisation between mirrored servers. Called after any rename or move action.
 *
 * @param  PATH				File/directory name to move from (may be full or relative path)
 * @param  PATH				File/directory name to move to (may be full or relative path)
 */
function _sync_file_move($old,$new)
{
	global $FILE_BASE;
	if (file_exists($FILE_BASE.'/data_custom/sync_script.php'))
	{
		require_once($FILE_BASE.'/data_custom/sync_script.php');
		if (substr($old,0,strlen($FILE_BASE))==$FILE_BASE)
		{
			$old=substr($old,strlen($FILE_BASE));
		}
		if (substr($new,0,strlen($FILE_BASE))==$FILE_BASE)
		{
			$new=substr($new,strlen($FILE_BASE));
		}
		if (function_exists('master__sync_file_move')) master__sync_file_move($old,$new);
	}
}

/**
 * Delete all the contents of a directory, and any subdirectories of that specified directory (recursively).
 *
 * @param  PATH			The pathname to the directory to delete
 * @param  boolean		Whether to preserve files there by default
 * @param  boolean		Whether to just delete files
 */
function _deldir_contents($dir,$default_preserve=false,$just_files=false)
{
	$current_dir=@opendir($dir);
	if ($current_dir!==false)
	{
		while (false!==($entryname=readdir($current_dir)))
		{
			if ($default_preserve)
			{
				if ($entryname=='index.html') continue;
				if ($entryname[0]=='.') continue;
				if (in_array(str_replace(get_file_base().'/','',$dir).'/'.$entryname,array('uploads/banners/advertise_here.png','uploads/banners/donate.png','themes/map.ini','themes/default')))
					continue;
			}
			if ((is_dir($dir.'/'.$entryname)) && ($entryname!='.') && ($entryname!='..'))
			{
				deldir_contents($dir.'/'.$entryname,$default_preserve,$just_files);
				if (!$just_files)
				{
					$test=@rmdir($dir.'/'.$entryname);
					if (($test===false) && (!$just_files/*tolerate weird locked dirs if we only need to delete files anyways*/)) warn_exit(do_lang_tempcode('WRITE_ERROR',escape_html($dir.'/'.$entryname)));
				}
			}
			elseif (($entryname!='.') && ($entryname!='..') /*&& ($entryname!='index.html') && ($entryname!='.htaccess')*/)
			{
				$test=@unlink($dir.'/'.$entryname);
				if ($test===false) intelligent_write_error($dir.'/'.$entryname);
			}
			sync_file($dir.'/'.$entryname);
		}
		closedir($current_dir);
	}
}

/**
 * Output data to a CSV file.
 *
 * @param  array			List of maps, each map representing a row
 * @param  ID_TEXT		Filename to output
 * @param  boolean		Whether to output CSV headers
 * @param  boolean		Whether to output/exit when we're done instead of return
 * @return string			CSV data (we might not return though, depending on $exit)
 */
function make_csv($data,$filename='data.csv',$headers=true,$output_and_exit=true)
{
	if ($headers)
	{
		header('Content-type: text/csv');
		if (strstr(ocp_srv('HTTP_USER_AGENT'),'MSIE')!==false)
			header('Content-Disposition: filename="'.str_replace(chr(13),'',str_replace(chr(10),'',addslashes($filename))).'"');
		else
			header('Content-Disposition: attachment; filename="'.str_replace(chr(13),'',str_replace(chr(10),'',addslashes($filename))).'"');
	}

	$out='';
	foreach ($data as $i=>$line)
	{
		if ($i==0) // Header
		{
			foreach (array_keys($line) as $j=>$val)
			{
				if ($j!=0) $out.=',';
				$out.='"'.str_replace('"','""',$val).'"';
			}
			$out.=chr(10);
		}

		// Main data
		$j=0;
		foreach ($line as $val)
		{
			if (is_null($val)) $val='';
			elseif (!is_string($val)) $val=strval($val);
			if ($j!=0) $out.=',';
			$out.='"'.str_replace('"','""',$val).'"';
			$j++;
		}
		$out.=chr(10);
	}

	if ($output_and_exit)
	{
		$GLOBALS['SCREEN_TEMPLATE_CALLED']='';

		@ini_set('ocproducts.xss_detect','0');
		exit($out);
	}
	return $out;
}

/**
 * Find path to the PHP executable.
 *
 * @return PATH			Path to PHP
 */
function find_php_path()
{
	$search_dirs=array(
		'/bin',
		'/usr/bin',
		'/usr/local/bin',
		'/usr/php/bin',
		'/usr/php/sbin',
		'/usr/php5/bin',
		'/usr/php5/sbin',
		'/usr/php6/bin',
		'/usr/php6/sbin',
		'c:\\php',
		'c:\\php5',
		'c:\\php6',
		'c:\\progra~1\\php',
		'c:\\progra~1\\php5',
		'c:\\progra~1\\php6',
	);
	$filenames=array(
		'php.dSYM',
		'php-cli',
		'php5-cli',
		'php6-cli',
		'php',
		'php5',
		'php6',
		'php-cgi',
		'php5-cgi',
		'php6-cgi',
	);
	foreach ($search_dirs as $dir)
	{
		foreach ($filenames as $file)
		{
			if (@file_exists($dir.'/'.$file)) break 2;
		}
	}
	if (!@file_exists($dir.'/'.$file))
	{
		$php_path='php';
	} else
	{
		$php_path=$dir.'/'.$file;
	}
	return $php_path;
}

/**
 * Get the contents of a directory, recursively. It is assumed that the directory exists.
 *
 * @param  PATH			The path to search
 * @param  PATH			The path we prepend to everything we find (intended to be used inside the recursion)
 * @param  boolean		Whether to also get special files
 * @param  boolean		Whether to recurse (if not, will return directories as files)
 * @return array			The contents of the directory
 */
function get_directory_contents($path,$rel_path='',$special_too=false,$recurse=true)
{
	$out=array();

	$d=opendir($path);
	while (($file=readdir($d))!==false)
	{
		if (!$special_too)
		{
			if (should_ignore_file($rel_path.(($rel_path=='')?'':'/').$file,IGNORE_ACCESS_CONTROLLERS)) continue;
		} elseif (($file=='.') || ($file=='..')) continue;

		if ((is_file($path.'/'.$file)) || (!$recurse))
		{
			$out[]=$rel_path.(($rel_path=='')?'':'/').$file;
		} elseif (is_dir($path.'/'.$file))
		{
			$out=array_merge($out,get_directory_contents($path.'/'.$file,$rel_path.(($rel_path=='')?'':'/').$file,$special_too,$recurse));
		}
	}
	closedir($d);

	return $out;
}

/**
 * Get the size in bytes of a directory. It is assumed that the directory exists.
 *
 * @param  PATH			The path to search
 * @return integer		The extra space requested
 */
function get_directory_size($path)
{
	$size=0;

	$d=opendir($path);
	while (($e=readdir($d))!==false)
	{
		if (($e=='.') || ($e=='..')) continue;

		if (is_file($path.'/'.$e))
		{
			$size+=filesize($path.'/'.$e);
		} else
		{
			$size+=get_directory_size($path.'/'.$e);
		}
	}

	return $size;
}

/**
 * Get the URL to the config option group for editing limits
 *
 * @return ?URLPATH		The URL to the config option group for editing limits (NULL: no access)
 */
function get_upload_limit_config_url()
{
	$config_url=NULL;
	if (has_actual_page_access(get_member(),'admin_config'))
	{
		$_config_url=build_url(array('page'=>'admin_config','type'=>'category','id'=>'SITE'),get_module_zone('admin_config'));
		$config_url=$_config_url->evaluate();
		$config_url.='#group_UPLOAD';
	}
	return $config_url;
}

/**
 * Get the maximum allowed upload filesize, as specified in the configuration
 *
 * @param  ?MEMBER		Member we consider quota for (NULL: do not consider quota)
 * @param  ?object		Database connection to get quota from (NULL: site DB)
 * @return integer		The maximum allowed upload filesize, in bytes
 */
function get_max_file_size($source_member=NULL,$connection=NULL)
{
	$possibilities=array();

	$a=php_return_bytes(ini_get('upload_max_filesize'));
	$b=php_return_bytes(ini_get('post_max_size'));
	$c=intval(get_option('max_download_size'))*1024;
	if (has_specific_permission(get_member(),'exceed_filesize_limit')) $c=0;

	$d=0;
	if ((!is_null($source_member)) && (!has_specific_permission(get_member(),'exceed_filesize_limit'))) // We'll be considering quota also
	{
		if (get_forum_type()=='ocf')
		{
			require_code('ocf_groups');
			$daily_quota=ocf_get_member_best_group_property($source_member,'max_daily_upload_mb');
		} else
		{
			$daily_quota=5; // 5 is a hard coded default for non-OCF forums
		}
		if (is_null($connection)) $connection=$GLOBALS['SITE_DB'];
		$_size_uploaded_today=$connection->query('SELECT SUM(a_file_size) AS the_answer FROM '.$connection->get_table_prefix().'attachments WHERE a_member_id='.strval((integer)$source_member).' AND a_add_time>'.strval(time()-60*60*24));
		$size_uploaded_today=intval($_size_uploaded_today[0]['the_answer']);
		$d=$daily_quota*1024*1024-$size_uploaded_today;
	}

	if ($a!=0) $possibilities[]=$a;
	if ($b!=0) $possibilities[]=$b;
	if ($c!=0) $possibilities[]=$c;
	if ($d!=0) $possibilities[]=$d;

	return min($possibilities);
}

/**
 * Check uploaded file extensions for possible malicious intent, and if some is found, an error is put out, and the hackattack logged.
 *
 * @param  string			The filename
 * @param  boolean		Whether to skip the server side security check
 * @param  ?string		Delete this file if we have to exit (NULL: no file to delete)
 * @param  boolean		Whether to allow errors without dying
 * @return boolean		Success status
 */
function check_extension($name,$skip_server_side_security_check=false,$file_to_delete=NULL,$accept_errors=false)
{
	$ext=get_file_extension($name);
	$_types=get_option('valid_types');
	$types=array_flip(explode(',',$_types));
	$_types='';
	ksort($types);
	if (!$skip_server_side_security_check)
	{
		if (!has_specific_permission(get_member(),'use_very_dangerous_comcode'))
		{
			unset($types['js']);
			unset($types['swf']);
			unset($types['html']);
			unset($types['htm']);
			unset($types['shtml']);
			unset($types['svg']);
			unset($types['xml']);
		}
	}
	foreach (array_flip($types) as $val)
		$_types.=$val.',';
	$_types=substr($_types,0,strlen($_types)-1);
	if (!$skip_server_side_security_check)
	{
		if (($ext=='py') || ($ext=='fcgi') || ($ext=='yaws') || ($ext=='dll') || ($ext=='cgi') || ($ext=='cfm') || ($ext=='vbs') || ($ext=='rhtml') || ($ext=='rb') || ($ext=='pl') || ($ext=='phtml') || ($ext=='php') || ($ext=='php3') || ($ext=='php4') || ($ext=='php5') || ($ext=='php6') || ($ext=='phtml') || ($ext=='aspx') || ($ext=='ashx') || ($ext=='asmx') || ($ext=='asx') || ($ext=='axd') || ($ext=='asp') || ($ext=='aspx') || ($ext=='jsp') || ($ext=='sh') || ($ext=='cgi') || (strtolower($name)=='.htaccess'))
		{
			if (!is_null($file_to_delete)) unlink($file_to_delete);
			if ($accept_errors) return false;
			log_hack_attack_and_exit('SCRIPT_UPLOAD_HACK');
		}
	}
	if ($_types!='')
	{
		$types=explode(',',$_types);
		foreach ($types as $val)
			if (strtolower(trim($val))==$ext) return true;
		if (!is_null($file_to_delete)) unlink($file_to_delete);
		$message=do_lang_tempcode('INVALID_FILE_TYPE',escape_html($ext),escape_html(str_replace(',',', ',$_types)));
		if (has_actual_page_access(get_member(),'admin_config'))
		{
			$_link=build_url(array('page'=>'admin_config','type'=>'category','id'=>'SECURITY'),get_module_zone('admin_config'));
			$link=$_link->evaluate();
			$link.='#group_UPLOAD';
			$message=do_lang_tempcode('INVALID_FILE_TYPE_ADMIN',escape_html($ext),escape_html(str_replace(',',', ',$_types)),escape_html($link));
		}
		if ($accept_errors)
		{
			require_code('site');
			attach_message($message,'warn');
			return false;
		} else
		{
			warn_exit($message);
		}
	}

	return true;
}

/**
 * Delete an uploaded file from disk, if it's URL has changed (i.e. it's been replaced, leaving a redundant disk file).
 *
 * @param  string		The path to the upload directory
 * @param  ID_TEXT	The table name
 * @param  ID_TEXT	The table field name
 * @param  mixed		The table ID field name, or a map array
 * @param  mixed		The table ID
 * @param  ?string	The new URL to use (NULL: deleting without replacing: no change check)
 */
function delete_upload($upload_path,$table,$field,$id_field,$id,$new_url=NULL)
{
	// Try and delete the file
	if ($GLOBALS['FORUM_DRIVER']->is_staff(get_member())) // This isn't really a permission - more a failsafe in case there is a security hole. Staff can cleanup leftover files from the Cleanup module anyway
	{
		$where=is_array($id_field)?$id_field:array($id_field=>$id);
		$url=$GLOBALS['SITE_DB']->query_value($table,$field,$where);
		if ($url=='') return;

		if ((is_null($new_url)) || (($url!=$new_url) && ($new_url!=STRING_MAGIC_NULL)))
		{
			if ((url_is_local($url)) && (substr($url,0,strlen($upload_path)+1)==$upload_path.'/'))
			{
				$count=$GLOBALS['SITE_DB']->query_value($table,'COUNT(*)',array($field=>$url));

				if ($count<=1)
				{
					@unlink(get_custom_file_base().'/'.rawurldecode($url));
					sync_file(rawurldecode($url));
				}
			}
		}
	}
}

/**
 * Check bandwidth usage against page view ratio for shared hosting.
 *
 * @param  integer		The extra bandwidth requested
 */
function check_shared_bandwidth_usage($extra)
{
	global $SITE_INFO;
	if (array_key_exists('throttle_bandwidth_registered',$SITE_INFO))
	{
		$views_till_now=intval(get_value('page_views'));
		$bandwidth_allowed=$SITE_INFO['throttle_bandwidth_registered'];
		$total_bandwidth=intval(get_value('download_bandwidth'));
		if ($bandwidth_allowed*1024*1024>=$total_bandwidth+$extra) return;
	}
	if (array_key_exists('throttle_bandwidth_complementary',$SITE_INFO))
	{
//		$timestamp_start=$SITE_INFO['custom_user_'].current_share_user();
//		$days_till_now=(time()-$timestamp_start)/(24*60*60);
		$views_till_now=intval(get_value('page_views'));
		$bandwidth_allowed=$SITE_INFO['throttle_bandwidth_complementary']+$SITE_INFO['throttle_bandwidth_views_per_meg']*$views_till_now;
		$total_bandwidth=intval(get_value('download_bandwidth'));
		if ($bandwidth_allowed*1024*1024<$total_bandwidth+$extra)
			critical_error('RELAY','The hosted user has exceeded their shared-hosting "bandwidth-limit to page-view" ratio. More pages must be viewed before this may be downloaded.');
	}
}

/**
 * Check disk space usage against page view ratio for shared hosting.
 *
 * @param  integer		The extra space in bytes requested
 */
function check_shared_space_usage($extra)
{
	global $SITE_INFO;
	if (array_key_exists('throttle_space_registered',$SITE_INFO))
	{
		$views_till_now=intval(get_value('page_views'));
		$bandwidth_allowed=$SITE_INFO['throttle_space_registered'];
		$total_space=get_directory_size(get_custom_file_base().'/uploads');
		if ($bandwidth_allowed*1024*1024>=$total_space+$extra) return;
	}
	if (array_key_exists('throttle_space_complementary',$SITE_INFO))
	{
//		$timestamp_start=$SITE_INFO['custom_user_'].current_share_user();
//		$days_till_now=(time()-$timestamp_start)/(24*60*60);
		$views_till_now=intval(get_value('page_views'));
		$space_allowed=$SITE_INFO['throttle_space_complementary']+$SITE_INFO['throttle_space_views_per_meg']*$views_till_now;
		$total_space=get_directory_size(get_custom_file_base().'/uploads');
		if ($space_allowed*1024*1024<$total_space+$extra)
			critical_error('RELAY','The hosted user has exceeded their shared-hosting "disk-space to page-view" ratio. More pages must be viewed before this may be uploaded.');
	}
}

/**
 * Return the file in the URL by downloading it over HTTP. If a byte limit is given, it will only download that many bytes. It outputs warnings, returning NULL, on error.
 *
 * @param  URLPATH		The URL to download
 * @param  ?integer		The number of bytes to download. This is not a guarantee, it is a minimum (NULL: all bytes)
 * @range  1 max
 * @param  boolean		Whether to throw an ocPortal error, on error
 * @param  boolean		Whether to block redirects (returns NULL when found)
 * @param  string			The user-agent to identify as
 * @param  ?array			An optional array of POST parameters to send; if this is NULL, a GET request is used (NULL: none)
 * @param  ?array			An optional array of cookies to send (NULL: none)
 * @param  ?string		'accept' header value (NULL: don't pass one)
 * @param  ?string		'accept-charset' header value (NULL: don't pass one)
 * @param  ?string		'accept-language' header value (NULL: don't pass one)
 * @param  ?resource		File handle to write to (NULL: do not do that)
 * @param  ?string		The HTTP referer (NULL: none)
 * @param  ?array			A pair: authentication username and password (NULL: none)
 * @param  float			The timeout
 * @param  boolean		Whether to treat the POST parameters as a raw POST (rather than using MIME)
 * @param  ?array			Files to send. Map between field to file path (NULL: none)
 * @return ?string		The data downloaded (NULL: error)
 */
function _http_download_file($url,$byte_limit=NULL,$trigger_error=true,$no_redirect=false,$ua='ocPortal',$post_params=NULL,$cookies=NULL,$accept=NULL,$accept_charset=NULL,$accept_language=NULL,$write_to_file=NULL,$referer=NULL,$auth=NULL,$timeout=6.0,$is_xml=false,$files=NULL)
{
	$url=str_replace(' ','%20',$url);

	// Prevent DOS loop attack
	if (ocp_srv('HTTP_USER_AGENT')==$ua) $ua='ocP-recurse';
	if (ocp_srv('HTTP_USER_AGENT')=='ocP-recurse') return NULL;

	require_code('urls');
	if (url_is_local($url)) $url=get_custom_base_url().'/'.$url;

	if ((strpos($url,'/')!==false) && (strrpos($url,'/')<7)) $url.='/';

	global $DOWNLOAD_LEVEL;
	$DOWNLOAD_LEVEL++;
	global $HTTP_DOWNLOAD_MIME_TYPE;
	$HTTP_DOWNLOAD_MIME_TYPE=NULL;
	global $HTTP_CHARSET;
	$HTTP_CHARSET=NULL;
	global $HTTP_DOWNLOAD_SIZE;
	$HTTP_DOWNLOAD_SIZE=0;
	global $HTTP_DOWNLOAD_URL;
	$HTTP_DOWNLOAD_URL=$url;
	global $HTTP_MESSAGE;
	$HTTP_MESSAGE=NULL;
	global $HTTP_MESSAGE_B;
	$HTTP_MESSAGE_B=NULL;
	global $HTTP_NEW_COOKIES;
	if ($DOWNLOAD_LEVEL==0) $HTTP_NEW_COOKIES=array();
	global $HTTP_FILENAME;
	$HTTP_FILENAME=NULL;

	if ($DOWNLOAD_LEVEL==8) return '';//critical_error('FILE_DOS',$url); // Prevent possible DOS attack

	$url_parts=@parse_url($url);
	if ($url_parts===false)
	{
		if ($trigger_error)
			warn_exit(do_lang_tempcode('HTTP_DOWNLOAD_BAD_URL',escape_html($url)));
		else $HTTP_MESSAGE_B=do_lang_tempcode('HTTP_DOWNLOAD_BAD_URL',escape_html($url));
		$DOWNLOAD_LEVEL--;
		$HTTP_MESSAGE='malconstructed-URL';
		return NULL;
	}
	if (!array_key_exists('scheme',$url_parts)) $url_parts['scheme']='http';

	$use_curl=(($url_parts['scheme']!='http') && (function_exists('curl_version'))) || ((function_exists('get_value')) && (get_value('prefer_curl')==='1'));

	// Prep cookies and post data
	if (!is_null($post_params))
	{
		if ($is_xml)
		{
			$_postdetails_params=$post_params[0];
		} else
		{
			$_postdetails_params='';//$url_parts['scheme'].'://'.$url_parts['host'].$url2.'?';
			$first=true;
			if (array_keys($post_params)==array('_'))
			{
				$_postdetails_params=$post_params['_'];
			} else
			{
				foreach ($post_params as $param_key=>$param_value)
				{
					if ($use_curl)
					{
						if (substr($param_value,0,1)=='@') $param_value=' @'.substr($param_value,1);
					}
					$_postdetails_params.=((array_key_exists('query',$url_parts)) || (!$first))?('&'.$param_key.'='.rawurlencode($param_value)):($param_key.'='.rawurlencode($param_value));
					$first=false;
				}
			}
		}
	} else $_postdetails_params='';
	if ((!is_null($cookies)) && (count($cookies)!=0))
	{
		$_cookies='';
		$done_one_cookie=false;
		foreach ($cookies as $key=>$val)
		{
			if ($done_one_cookie) $_cookies.='; ';
			if (is_array($val))
			{
				foreach ($val as $key2=>$val2)
				{
					if (!is_string($key2)) $key2=strval($key2);
					if ($done_one_cookie) $_cookies.='; ';
					$_cookies.=$key.'['.$key2.']='.rawurlencode($val2);
					$done_one_cookie=true;
				}
			} else
			{
				$_cookies.=$key.'='.rawurlencode($val);
			}
			$done_one_cookie=true;
		}
	}

	if ($use_curl) // We'll have to try to use CURL
	{
		if (!is_null($files))
		{
			if (is_null($post_params)) $post_params=array();
			foreach ($files as $upload_field=>$file_path)
			{
				$post_params[$upload_field]='@'.$file_path;
			}
		}

		// CURL
		if (function_exists('curl_version'))
		if (function_exists('curl_init'))
		if (function_exists('curl_setopt'))
		if (function_exists('curl_exec'))
		if (function_exists('curl_error'))
		if (function_exists('curl_close'))
		if (function_exists('curl_getinfo'))
		if (($url_parts['scheme']=='https') || ($url_parts['scheme']=='http'))
		{
			$curl_version=curl_version();
			if (((is_string($curl_version)) && (strpos($curl_version,'OpenSSL')!==false)) || ((is_array($curl_version)) && (array_key_exists('ssl_version',$curl_version))))
			{
				$ch=curl_init($url);
				if (!is_null($post_params))
				{
					curl_setopt($ch,CURLOPT_POST,true);
					if (is_null($files))
					{
						curl_setopt($ch,CURLOPT_POSTFIELDS,$_postdetails_params);
					} else
					{
						curl_setopt($ch,CURLOPT_POSTFIELDS,$post_params);
					}
				}
				if ((!is_null($cookies)) && (count($cookies)!=0)) curl_setopt($ch,CURLOPT_COOKIE,$_cookies);
				$crt_path=get_file_base().'/data/curl-ca-bundle.crt';
				curl_setopt($ch,CURLOPT_CAINFO,$crt_path);
				curl_setopt($ch,CURLOPT_CAPATH,$crt_path);
				//if (!$no_redirect) @curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true); // May fail with safe mode, meaning we can't follow Location headers. But we can do better ourselves anyway and protect against file:// exploits.
				curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,intval($timeout));
				curl_setopt($ch,CURLOPT_TIMEOUT,intval($timeout));
				curl_setopt($ch,CURLOPT_USERAGENT,$ua);
				$headers=array();
				if ($is_xml) $headers[]='Content-Type: application/xml';
				if (!is_null($accept)) $headers[]='Accept: '.rawurlencode($accept);
				if (!is_null($accept_charset)) $headers[]='Accept-Charset: '.rawurlencode($accept_charset);
				if (!is_null($accept_language)) $headers[]='Accept-Language: '.rawurlencode($accept_language);
				if (is_null($files)) // Breaks file uploads for some reason
					curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
				curl_setopt($ch,CURLOPT_HEADER,true);
				if (!is_null($auth)) curl_setopt($ch,CURLOPT_USERPWD,implode(':',$auth));
				if (!is_null($referer))
					curl_setopt($ch,CURLOPT_REFERER,$referer);
				$proxy=get_value('proxy',NULL,true);
				if ($proxy=='') $proxy=NULL;
				if ((!is_null($proxy)) && ($url_parts['host']!='localhost') && ($url_parts['host']!='127.0.0.1'))
				{
					$port=get_value('proxy_port',NULL,true);
					if (is_null($port)) $port='8080';
					curl_setopt($ch, CURLOPT_PROXY,$proxy.':'.$port);
					$proxy_user=get_value('proxy_user',NULL,true);
					if (!is_null($proxy_user))
					{
						$proxy_password=get_value('proxy_password',NULL,true);
						curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_user.':'.$proxy_password);
					}
				}
				if (!is_null($byte_limit)) curl_setopt($ch,CURLOPT_RANGE,'0-'.strval(($byte_limit==0)?0:($byte_limit-1)));
				$line=curl_exec($ch);
				if (substr($line,0,25)=="HTTP/1.1 100 Continue\r\n\r\n") $line=substr($line,25);
				if (substr($line,0,25)=="HTTP/1.0 100 Continue\r\n\r\n") $line=substr($line,25);
				if ($line===false)
				{
					$error=curl_error($ch);
					curl_close($ch);
					if ($trigger_error)
						warn_exit($error);
					return NULL;
				}
				$HTTP_DOWNLOAD_MIME_TYPE=curl_getinfo($ch,CURLINFO_CONTENT_TYPE);
				$HTTP_DOWNLOAD_SIZE=curl_getinfo($ch,CURLINFO_CONTENT_LENGTH_DOWNLOAD);
				$HTTP_DOWNLOAD_URL=curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
				$HTTP_MESSAGE=strval(curl_getinfo($ch,CURLINFO_HTTP_CODE));
				if ($HTTP_MESSAGE=='206') $HTTP_MESSAGE='200'; // We don't care about partial-content return code, as ocP implementation gets ranges differently and we check '200' as a return result
				if (strpos($HTTP_DOWNLOAD_MIME_TYPE,';')!==false)
				{
					$HTTP_CHARSET=substr($HTTP_DOWNLOAD_MIME_TYPE,8+strpos($HTTP_DOWNLOAD_MIME_TYPE,'charset='));
					$HTTP_DOWNLOAD_MIME_TYPE=substr($HTTP_DOWNLOAD_MIME_TYPE,0,strpos($HTTP_DOWNLOAD_MIME_TYPE,';'));
				}
				curl_close($ch);
				if (substr($line,0,strlen('HTTP/1.0 200 Connection Established'))=='HTTP/1.0 200 Connection Established') $line=substr($line,strpos($line,"\r\n\r\n")+4);
				$pos=strpos($line,"\r\n\r\n");

				if (substr($line,0,strlen('HTTP/1.1 100 '))=='HTTP/1.1 100 ' || substr($line,0,strlen('HTTP/1.0 100 '))=='HTTP/1.0 100 ') $pos=strpos($line,"\r\n\r\n",$pos+4);
				if ($pos===false) $pos=strlen($line); else $pos+=4;
				$lines=explode("\r\n",substr($line,0,$pos));
				foreach ($lines as $lno=>$_line)
				{
					$_line.="\r\n";
					$matches=array();

					if (preg_match('#^Content-Disposition: [^;]*;\s*filename="([^"]*)"#i',$_line,$matches)!=0)
					{
						$HTTP_FILENAME=$matches[1];
					}
					if (preg_match("#^Set-Cookie: ([^\r\n=]*)=([^\r\n]*)\r\n#i",$_line,$matches)!=0)
					{
						$HTTP_NEW_COOKIES[trim(rawurldecode($matches[1]))]=trim($matches[2]);
					}
					if (preg_match("#^Location: (.*)\r\n#i",$_line,$matches)!=0)
					{
						if (is_null($HTTP_FILENAME)) $HTTP_FILENAME=basename($matches[1]);

						if (strpos($matches[1],'://')===false) $matches[1]=qualify_url($matches[1],$url);
						if ($matches[1]!=$url)
						{
							$bak=$HTTP_FILENAME;
							$text=$no_redirect?NULL:_http_download_file($matches[1],$byte_limit,$trigger_error,false,$ua,NULL,$cookies,$accept,$accept_charset,$accept_language,$write_to_file);
							if (is_null($HTTP_FILENAME)) $HTTP_FILENAME=$bak;
							$DOWNLOAD_LEVEL--;
							return $text;
						}
					}
				}

				$DOWNLOAD_LEVEL--;
				return substr($line,$pos);
			}
		}

		if ($trigger_error)
			warn_exit(do_lang_tempcode('HTTP_DOWNLOAD_BAD_URL',escape_html($url)));
		else $HTTP_MESSAGE_B=do_lang_tempcode('HTTP_DOWNLOAD_BAD_URL',escape_html($url));
		$DOWNLOAD_LEVEL--;
		$HTTP_MESSAGE='non-HTTP';
		return NULL;
	}

	$errno=0;
	$errstr='';
	if ($url_parts['scheme']=='http')
	{
		if (!array_key_exists('host',$url_parts)) $url_parts['host']='127.0.0.1';
		$connect_to=$url_parts['host'];
		$base_url_parsed=parse_url(get_base_url());
		if (!array_key_exists('host',$base_url_parsed)) $base_url_parsed['host']='127.0.0.1';
		if (($base_url_parsed['host']==$connect_to) && (function_exists('get_option')) && (get_option('ip_forwarding')=='1')) // For cases where we have IP-forwarding, and a strong firewall (i.e. blocked to our own domain's IP by default)
		{
			$connect_to='127.0.0.1'; // Localhost can fail due to IP6
		} elseif (preg_match('#(\s|,|^)gethostbyname(\s|$|,)#i',@ini_get('disable_functions'))==0) $connect_to=gethostbyname($connect_to); // for DNS cacheing
		$proxy=function_exists('get_value')?get_value('proxy',NULL,true):NULL;
		if ($proxy=='') $proxy=NULL;
		if ((!is_null($proxy)) && ($connect_to!='localhost') && ($connect_to!='127.0.0.1'))
		{
			$proxy_port=get_value('proxy_port',NULL,true);
			if (is_null($proxy_port)) $proxy_port='8080';
			$mysock=@fsockopen($proxy,intval($proxy_port),$errno,$errstr,$timeout);
		} else
		{
			$mysock=@fsockopen($connect_to,array_key_exists('port',$url_parts)?$url_parts['port']:80,$errno,$errstr,$timeout);
		}
		if (is_null($mysock)) $mysock=false; // For Quercus #4549
	} else $mysock=false;
	if ($mysock!==false)
	{
		if (function_exists('stream_set_timeout'))
		{
			if (@stream_set_timeout($mysock,intval($timeout))===false) $mysock=false;
		} elseif (function_exists('socket_set_timeout'))
		{
			if (@socket_set_timeout($mysock,intval($timeout))===false) $mysock=false;
		}
	}
	if ($mysock!==false)
	{
		$url2=array_key_exists('path',$url_parts)?$url_parts['path']:'/';
		if (array_key_exists('query',$url_parts)) $url2.='?'.$url_parts['query'];

		if ((!is_null($proxy)) && ($connect_to!='localhost') && ($connect_to!='127.0.0.1'))
		{
			$out='';
			$out.=((is_null($post_params))?(($byte_limit===0)?'HEAD ':'GET '):'POST ').str_replace("\r",'',str_replace(chr(10),'',$url))." HTTP/1.1\r\n";
			$proxy_user=get_value('proxy_user',NULL,true);
			if (!is_null($proxy_user))
			{
				$proxy_password=get_value('proxy_password',NULL,true);
				$out.='Proxy-Authorization: Basic '.base64_encode($proxy_user.':'.$proxy_password)."\r\n";
			}
		} else
		{
			$out=((is_null($post_params))?(($byte_limit===0)?'HEAD ':'GET '):'POST ').str_replace("\r",'',str_replace(chr(10),'',$url2))." HTTP/1.1\r\n";
		}
		$out.="Host: ".$url_parts['host']."\r\n";
		if ((!is_null($cookies)) && (count($cookies)!=0)) $out.='Cookie: '.$_cookies."\r\n";
		$out.="User-Agent: ".rawurlencode($ua)."\r\n";
		if (!is_null($auth))
		{
			$out.="Authorization: Basic ".base64_encode(implode(':',$auth))."==\r\n";
		}
		if (!is_null($accept))
		{
			$out.="Accept: ".rawurlencode($accept)."\r\n";
		} else
		{
			$out.="Accept: */*(\r\n"; // There's a mod_security rule that checks for this
		}
		if (!is_null($accept_charset))
		{
			$out.="Accept-Charset: ".rawurlencode($accept_charset)."\r\n";
		}
		if (!is_null($accept_language))
		{
			$out.="Accept-Language: ".rawurlencode($accept_language)."\r\n";
		}
		if (!is_null($referer))
			$out.="Referer: ".rawurlencode($referer)."\r\n";
		if ($_postdetails_params!='')
		{
			if ($is_xml)
			{
				$out.="Content-Type: application/xml\r\n";
				$out.='Content-length: '.strval(strlen($_postdetails_params))."\r\n";
				$out.="\r\n".$_postdetails_params."\r\n\r\n";
			} else
			{
				if (is_null($files))
				{
					$out.='Content-type: application/x-www-form-urlencoded; charset='.get_charset()."\r\n";
					$out.='Content-length: '.strval(strlen($_postdetails_params))."\r\n";
					$out.="\r\n".$_postdetails_params."\r\n\r\n";
				} else
				{
					$divider=uniqid('');
					$out2='';
					$out.='Content-type: multipart/form-data; boundary="--ocp'.$divider.'"; charset='.get_charset()."\r\n";
					foreach ($post_params as $key=>$val)
					{
						$out2.='----ocp'.$divider."\r\n";
						$out2.='Content-Disposition: form-data; name="'.urlencode($key).'"'."\r\n\r\n";
						$out2.=$val."\r\n";
					}
					if (!is_null($files))
					{
						foreach ($files as $upload_field=>$file_path)
						{
							$out2.='----ocp'.$divider."\r\n";
							$out2.='Content-Disposition: form-data; name="'.urlencode($upload_field).'"; filename="'.urlencode(basename($file_path)).'"'."\r\n";
							$out2.='Content-Type: application/octet-stream'."\r\n\r\n";
							$out2.=file_get_contents($file_path)."\r\n";
						}
					}
					$out2.='----ocp'.$divider."--\r\n";
					$out.='Content-length: '.strval(strlen($out2))."\r\n";
					$out.="\r\n".$out2;
				}
			}
		}
		$out.="Connection: Close\r\n\r\n";

		@fwrite($mysock,$out);
		$data_started=false;
		$input='';
		$input_len=0;
		$first_fail_time=mixed();
		$chunked=false;
		$buffer_unprocessed='';
		while (($chunked) || (!@feof($mysock))) // @'d because socket might have died. If so fread will will return false and hence we'll break
		{
			$line=@fread($mysock,(($chunked) && (strlen($buffer_unprocessed)>10))?10:1024);

			if ($line===false)
			{
				if ((!$chunked) || ($buffer_unprocessed=='')) break;
				$line='';
			}
			if ($line=='')
			{
				if (!is_null($first_fail_time))
				{
					if ($first_fail_time<time()-5) break;
				} else $first_fail_time=time();
			} else $first_fail_time=NULL;
			if ($data_started)
			{
				$line=$buffer_unprocessed.$line;
				$buffer_unprocessed='';

				if ($chunked)
				{
					$matches=array();
					if (preg_match('#^(\r\n)?([a-f\d]+) *(;[^\r\n]*)?\r\n(.*)$#is',$line,$matches)!=0)
					{
						$amount_wanted=hexdec($matches[2]);
						if (strlen($matches[4])<$amount_wanted) // Chunk was more than what we grabbed, so we need to iterate more to parse
						{
							$buffer_unprocessed=$line;
							continue;
						}
						$buffer_unprocessed=substr($matches[4],$amount_wanted); // May be some more extra read
						$line=substr($matches[4],0,$amount_wanted);
						if ($line=='')
						{
							break;
						}
					} else
					{
						// Should not happen :S
					}
				}

				if (is_null($write_to_file)) $input.=$line; else fwrite($write_to_file,$line);
				$input_len+=strlen($line);
				if ((!is_null($byte_limit)) && ($input_len>=$byte_limit))
				{
					$input=substr($input,0,$byte_limit);
					break;
				}
			}
			else
			{
				$old_line=$line;
				$lines=explode("\r\n",$line);

				$tally=0;
				foreach ($lines as $lno=>$line)
				{
					$line.="\r\n";

					$tally+=strlen($line);

					$matches=array();
					if (preg_match("#Transfer-Encoding: chunked\r\n#i",$line,$matches)!=0)
					{
						$chunked=true;
					}
					if (preg_match("#Content-Disposition: [^\r\n]*filename=\"([^;\r\n]*)\"\r\n#i",$line,$matches)!=0)
					{
						$HTTP_FILENAME=$matches[1];
					}
					if (preg_match("#^Set-Cookie: ([^\r\n=]*)=([^\r\n]*)\r\n#i",$line,$matches)!=0)
					{
						$HTTP_NEW_COOKIES[trim(rawurldecode($matches[1]))]=trim($matches[2]);
					}
					if (preg_match("#^Content-Length: ([^;\r\n]*)\r\n#i",$line,$matches)!=0)
					{
						$HTTP_DOWNLOAD_SIZE=intval($matches[1]);
					}
					if (preg_match("#^Content-Type: ([^;\r\n]*)(;[^\r\n]*)?\r\n#i",$line,$matches)!=0)
					{
						$HTTP_DOWNLOAD_MIME_TYPE=$matches[1];
						if (array_key_exists(2,$matches))
						{
							$_ct_more=explode(';',str_replace(' ','',trim($matches[2])));
							foreach ($_ct_more as $ct_more)
							{
								$ct_bits=explode('=',$ct_more,2);
								if ((count($ct_bits)==2) && (strtolower($ct_bits[0])=='charset'))
								{
									$HTTP_CHARSET=trim($ct_bits[1]);
								}
							}
						}
					}
					if (preg_match("#^Refresh: (\d*);(.*)\r\n#i",$line,$matches)!=0)
					{
						if (is_null($HTTP_FILENAME)) $HTTP_FILENAME=basename($matches[1]);

						@fclose($mysock);
						if (strpos($matches[1],'://')===false) $matches[1]=qualify_url($matches[1],$url);
						$bak=$HTTP_FILENAME;
						$text=$no_redirect?NULL:_http_download_file($matches[2],$byte_limit,$trigger_error,false,$ua,NULL,$cookies,$accept,$accept_charset,$accept_language,$write_to_file);
						if (is_null($HTTP_FILENAME)) $HTTP_FILENAME=$bak;
						$DOWNLOAD_LEVEL--;
						return $text;
					}
					if (preg_match("#^Location: (.*)\r\n#i",$line,$matches)!=0)
					{
						if (is_null($HTTP_FILENAME)) $HTTP_FILENAME=basename($matches[1]);

						@fclose($mysock);
						if (strpos($matches[1],'://')===false) $matches[1]=qualify_url($matches[1],$url);
						if ($matches[1]!=$url)
						{
							$bak=$HTTP_FILENAME;
							$text=$no_redirect?NULL:_http_download_file($matches[1],$byte_limit,$trigger_error,false,$ua,NULL,$cookies,$accept,$accept_charset,$accept_language,$write_to_file);
							if (is_null($HTTP_FILENAME)) $HTTP_FILENAME=$bak;
							$DOWNLOAD_LEVEL--;
							return $text;
						}
					}
					if (preg_match("#HTTP/(\d*\.\d*) (\d*) #",$line,$matches)!=0)
					{
						// 200 = Ok
						// 301/302 = Redirected: Not good, we should not be here
						// 401 = Unauthorized
						// 403 = Forbidden
						// 404 = Not found
						// 500 = Internal server error
						$HTTP_MESSAGE=$matches[2];
						switch ($matches[2])
						{
							case '302':
							case '301':
								// We'll expect a location header
								break;
							case '200':
								// Good
								break;
							case '401':
								if ($trigger_error)
									warn_exit(do_lang_tempcode('HTTP_DOWNLOAD_STATUS_UNAUTHORIZED',escape_html($url)));
								else $HTTP_MESSAGE_B=do_lang_tempcode('HTTP_DOWNLOAD_STATUS_UNAUTHORIZED',escape_html($url));
								@fclose($mysock);
								$HTTP_DOWNLOAD_MIME_TYPE='security';
								$DOWNLOAD_LEVEL--;
								return NULL;
							case '403':
								if ($trigger_error)
									warn_exit(do_lang_tempcode('HTTP_DOWNLOAD_STATUS_UNAUTHORIZED',escape_html($url)));
								else $HTTP_MESSAGE_B=do_lang_tempcode('HTTP_DOWNLOAD_STATUS_UNAUTHORIZED',escape_html($url));
								@fclose($mysock);
								$HTTP_DOWNLOAD_MIME_TYPE='security';
								$DOWNLOAD_LEVEL--;
								return NULL;
							case '404':
								if ($trigger_error)
									warn_exit(do_lang_tempcode('HTTP_DOWNLOAD_STATUS_NOT_FOUND',escape_html($url)));
								@fclose($mysock);
								$DOWNLOAD_LEVEL--;
								return NULL;
							case '500':
								if ($trigger_error)
									warn_exit(do_lang_tempcode('HTTP_DOWNLOAD_STATUS_SERVER_ERROR',escape_html($url)));
								@fclose($mysock);
								$DOWNLOAD_LEVEL--;
								return NULL;
							default:
								if ($trigger_error)
									warn_exit(do_lang_tempcode('HTTP_DOWNLOAD_STATUS_UNKNOWN',escape_html($url)));
						}
					}
					if ($line=="\r\n")
					{
						$data_started=true;
						$input_len+=max(0,strlen($old_line)-$tally);
						$buffer_unprocessed=substr($old_line,$tally);
						if ($buffer_unprocessed===false) $buffer_unprocessed='';
						break;
					}
				}
			}
		}

		// Process any non-chunked extra buffer (chunked would have been handled in main loop)
		if (!$chunked)
		{
			if ($buffer_unprocessed!='')
			{
				if (is_null($write_to_file)) $input.=$buffer_unprocessed; else fwrite($write_to_file,$buffer_unprocessed);
				$input_len+=strlen($line);
				if ((!is_null($byte_limit)) && ($input_len>=$byte_limit))
				{
					$input=substr($input,0,$byte_limit);
				}
			}
		}

		@fclose($mysock);
		if (!$data_started)
		{
			if ($trigger_error)
				warn_exit(do_lang_tempcode('HTTP_DOWNLOAD_NO_SERVER',escape_html($url)));
			else $HTTP_MESSAGE_B=do_lang_tempcode('HTTP_DOWNLOAD_NO_SERVER',escape_html($url));
			$DOWNLOAD_LEVEL--;
			$HTTP_MESSAGE='no-data';
			return NULL;
		}
		$size_expected=$HTTP_DOWNLOAD_SIZE;
		if (!is_null($byte_limit))
		{
			if ($byte_limit<$HTTP_DOWNLOAD_SIZE)
				$size_expected=$byte_limit;
		}
		if ($input_len<$size_expected)
		{
			if ($trigger_error)
				warn_exit(do_lang_tempcode('HTTP_DOWNLOAD_CUT_SHORT',escape_html($url),escape_html(integer_format($size_expected)),escape_html(integer_format($input_len))));
			else $HTTP_MESSAGE_B=do_lang_tempcode('HTTP_DOWNLOAD_CUT_SHORT',escape_html($url),escape_html(integer_format($size_expected)),escape_html(integer_format($input_len)));
			$DOWNLOAD_LEVEL--;
			$HTTP_MESSAGE='short-data';
			return $input;
		}

		$DOWNLOAD_LEVEL--;

		return $input;
	} else
	{
		if (($errno!=110) && (($errno!=10060) || (@ini_get('default_socket_timeout')=='1')) && (is_null($post_params)))
		{
			// Perhaps fsockopen is restricted... try fread/file_get_contents
			@ini_set('allow_url_fopen','1');
			$timeout_before=@ini_get('default_socket_timeout');
			@ini_set('default_socket_timeout',strval(intval($timeout)));
			if (is_null($byte_limit))
			{
				$read_file=@file_get_contents($url);
			} else
			{
				$_read_file=@fopen($url,'rb');
				if ($_read_file!==false)
				{
					$read_file='';
					while ((!feof($_read_file)) && (strlen($read_file)<$byte_limit)) $read_file.=fread($_read_file,1024);
					fclose($_read_file);
				} else $read_file=false;
			}
			@ini_set('allow_url_fopen','0');
			@ini_set('default_socket_timeout',$timeout_before);
			if ($read_file!==false)
			{
				$DOWNLOAD_LEVEL--;
				return $read_file;
			}
		}

		if ($trigger_error)
		{
			$error=do_lang_tempcode('_HTTP_DOWNLOAD_NO_SERVER',escape_html($url),escape_html($errstr));
			warn_exit($error);
		}
		else $HTTP_MESSAGE_B=do_lang_tempcode('HTTP_DOWNLOAD_NO_SERVER',escape_html($url));
		$DOWNLOAD_LEVEL--;
		$HTTP_MESSAGE='could not connect to host ('.$errstr.')';
		return NULL;
	}
}
