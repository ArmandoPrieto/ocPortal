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
function init__files()
{
	global $DOWNLOAD_LEVEL;
	$DOWNLOAD_LEVEL=0;

	if (!defined('IGNORE_DEFAULTS'))
	{
		define('IGNORE_DEFAULTS',0);
		// -
		define('IGNORE_ACCESS_CONTROLLERS',1);
		define('IGNORE_CUSTOM_DIR_CONTENTS',2);
		define('IGNORE_HIDDEN_FILES',4);
		define('IGNORE_EDITFROM_FILES',8);
		define('IGNORE_REVISION_FILES',16);
		define('IGNORE_CUSTOM_ZONES',32);
		define('IGNORE_CUSTOM_THEMES',64);
		define('IGNORE_NON_REGISTERED',128);
		define('IGNORE_USER_CUSTOMISE',256);
		define('IGNORE_NONBUNDLED_SCATTERED',512);
		define('IGNORE_BUNDLED_VOLATILE',1024);
		define('IGNORE_BUNDLED_UNSHIPPED_VOLATILE',2048);
		define('IGNORE_NON_EN_SCATTERED_LANGS',4096);
	}
}

/**
 * Find whether we can get away with natural file access, not messing with AFMs, world-writability, etc.
 *
 * @return boolean		Whether we have this
 */
function is_suexec_like()
{
	return (((function_exists('posix_getuid')) && (strpos(@ini_get('disable_functions'),'posix_getuid')===false) && (!isset($_SERVER['HTTP_X_MOSSO_DT'])) && (is_integer(@posix_getuid())) && (@posix_getuid()==@fileowner(get_file_base().'/'.(running_script('install')?'install.php':'index.php'))))
	|| (is_writable_wrap(get_file_base().'/'.(running_script('index')?'index.php':'install.php'))));
}

/**
 * Get the number of bytes for a PHP config option. Code taken from the PHP manual.
 *
 * @param  string			PHP config option value.
 * @return integer		Number of bytes.
 */
function php_return_bytes($val)
{
	$val=trim($val);
	if ($val=='') return 0;
	$last=strtolower($val[strlen($val)-1]);
	$_val=intval($val);
	switch($last)
	{
		// The 'G' modifier is available since PHP 5.1.0
		case 'g':
			$_val*=1024;
		case 'm':
			$_val*=1024;
		case 'k':
			$_val*=1024;
	}

	return $_val;
}

/**
 * Get a formatted-string filesize for the specified file. It is formatted as such: x Mb/Kb/Bytes (or unknown). It is assumed that the file exists.
 *
 * @param  URLPATH		The URL that the file size of is being worked out for. Should be local.
 * @return string			The formatted-string file size
 */
function get_file_size($url)
{
	if (substr($url,0,strlen(get_base_url()))==get_base_url())
		$url=substr($url,strlen(get_base_url()));

	if (!url_is_local($url)) return do_lang('UNKNOWN');

	$_full=rawurldecode($url);
	$_full=get_file_base().'/'.$_full;
	$file_size_bytes=filesize($_full);

	return clean_file_size($file_size_bytes);
}

/**
 * Format the specified filesize.
 *
 * @param  integer		The number of bytes the file has
 * @return string			The formatted-string file size
 */
function clean_file_size($bytes)
{
	if ($bytes<0) return '-'.clean_file_size(-$bytes);

	if (is_null($bytes)) return do_lang('UNKNOWN').' bytes';
	if (floatval($bytes)>2.0*1024.0*1024.0*1024.0) return strval(intval(round(floatval($bytes)/1024.0/1024.0/1024.0))).' Gb';
	if (floatval($bytes)>1024.0*1024.0*1024.0) return float_format(round(floatval($bytes)/1024.0/1024.0/1024.0,2)).' Gb';
	if (floatval($bytes)>2.0*1024.0*1024.0) return strval(intval(round(floatval($bytes)/1024.0/1024.0))).' Mb';
	if (floatval($bytes)>1024.0*1024.0) return float_format(round(floatval($bytes)/1024.0/1024.0,2)).' Mb';
	if (floatval($bytes)>2.0*1024.0) return strval(intval(round(floatval($bytes)/1024.0))).' Kb';
	if (floatval($bytes)>1024.0) return float_format(round(floatval($bytes)/1024.0,2)).' Kb';
	return strval($bytes).' Bytes';
}

/**
 * Get the file extension of the specified file. It returns without a dot.
 *
 * @param  string			The filename
 * @return string			The filename extension (no dot)
 */
function get_file_extension($name)
{
	$dot_pos=strrpos($name,'.');
	if ($dot_pos===false) return '';
	return strtolower(substr($name,$dot_pos+1));
}

/**
 * Parse the specified INI file, and get an array of what it found.
 *
 * @param  ?PATH			The path to the ini file to open (NULL: given contents in $file instead)
 * @param  ?string		The contents of the file (NULL: the file needs opening)
 * @return array			A map of the contents of the ini files
 */
function better_parse_ini_file($filename,$file=NULL)
{
	// NB: 'file()' function not used due to slowness compared to file_get_contents then explode

	if (is_null($file))
	{
		global $FILE_ARRAY;
		if (@is_array($FILE_ARRAY)) $file=file_array_get($filename);
		else $file=file_get_contents($filename);
	}

	$ini_array=array();
	$lines=explode(chr(10),$file);
	foreach ($lines as $line)
	{
		$line=rtrim($line);

		if ($line=='') continue;
		if ($line[0]=='#') continue;

		$bits=explode('=',$line,2);
		if (isset($bits[1]))
		{
			list($property,$value)=$bits;
			$value=trim($value,'"');
			$ini_array[$property]=$value;
		}
	}

	return $ini_array;
}

/**
 * Find whether a file is known to be something that should/could be there but isn't an ocPortal distribution file, or for some other reason should be ignored.
 *
 * @param  string			File path (relative to ocPortal base directory)
 * @param  integer		Bitmask of extra stuff to ignore (see IGNORE_* constants)
 * @param  integer		Set this to 0 if you don't want the default IGNORE_* constants to carry through
 * @return boolean		Whether it should be ignored
 */
function should_ignore_file($filepath,$bitmask=0,$bitmask_defaults=0)
{
	$bitmask=$bitmask | $bitmask_defaults;

	$is_dir=@is_dir(get_file_base().'/'.$filepath);
	$is_file=@is_file(get_file_base().'/'.$filepath);

	// Normalise
	if (strpos($filepath,'/')!==false)
	{
		$dir=dirname($filepath);
		$filename=basename($filepath);
	} else
	{
		$dir='';
		$filename=$filepath;
	}

	$ignore_filenames_and_dir_names=array( // Case insensitive, define in lower case
		'.'=>'.*',
		'..'=>'.*',

		// Files other stuff makes
		'__macosx'=>'.*',
		'.bash_history'=>'.*',
		'error_log'=>'.*',
		'thumbs.db:encryptable'=>'.*',
		'thumbs.db'=>'.*',
		'.ds_store'=>'.*',

		// Source code control systems
		'.svn'=>'.*',
		'.git'=>'',
		'.gitattributes'=>'',
		'.gitignore'=>'',
		'cvs'=>'.*',
		'phpdoc.dist.xml'=>'',

		// Web server extensions / leave-behinds
		'web-inf'=>'.*',
		'www.pid'=>'',

		// Specially-recognised naming conventions
		'_old'=>'.*',

		// From NEXT version (v10), ignored in this one
		'_config.php'=>'',

		// Syntax's used during ocPortal testing
		'gibb'=>'.*',
		'gibberish'=>'.*',

		// Compiled documentation
		'api'=>'docs',

		// Files you are sometimes expected to leave around, but outside ocPortal's direct remit
		'bingsiteauth.xml'=>'',
		'php.ini'=>'.*',
		'.htpasswd'=>'.*',
		'iirf.ini'=>'',
		'robots.txt'=>'',
		'400.shtml'=>'',
		'500.shtml'=>'',
		'404.shtml'=>'',
		'403.shtml'=>'',
		'.htaccess'=>'',

		// Installer files
		'install.php'=>'',
		'info.php.template'=>'',
		'data.ocp'=>'',
		'install.sql'=>'',
		'install1.sql'=>'',
		'install2.sql'=>'',
		'install3.sql'=>'',
		'install4.sql'=>'',
		'user.sql'=>'',
		'postinstall.sql'=>'',
		'restore.php'=>'',
		'parameters.xml'=>'',
		'manifest.xml'=>'',

		// IDE projects
		'nbproject'=>'',
		'.project'=>'',

		// ocPortal control files
		'closed.html'=>'',
		'closed.html.old'=>'',
		'install_ok'=>'',
		'install_locked'=>'',
		'if_hosted_service.txt'=>'',

		// MyOCP
		'sites'=>'',

		// PHP compiler temporary files
		'subs.inc'=>'',
		'hphp-static-cache'=>'',
		'hphp.files.list'=>'',
		'hphp'=>'',
	);

	$ignore_extensions=array( // Case insensitive, define in lower case
		// Exports (effectively these are like temporary files - only intended for file transmission)
		'tar'=>'(imports|exports)/.*',
		'gz'=>'(imports|exports)/.*',
		'txt'=>'(imports|exports)/.*',

		// Cache files
		'lcd'=>'(lang_cached|caches)(/.*)?', // TODO Future proof (v10)
		'gcd'=>'persistent_cache|persistant_cache|caches/.*', // LEGACY (persistant_cache) TODO Future proof (v10)
		'tcp'=>'themes/[^/]*/templates_cached/.*',
		'tcd'=>'themes/[^/]*/templates_cached/.*',
		'css'=>'themes/[^/]*/templates_cached/.*',
		'js'=>'themes/[^/]*/templates_cached/.*',

		// Logs
		'log'=>'.*',

		// Temporary files
		'tmp'=>'.*',

		// IDE projects
		'clpprj'=>'',
		'tmproj'=>'',
		'zpj'=>'',

		// PHP compiler temporary files
		'o'=>'',
		'scm'=>'',
		'heap'=>'',
		'sch'=>'',
		'dll'=>'',

		// CGI files
		'fcgi'=>'',
	);

	$ignore_filename_and_dir_name_patterns=array( // Case insensitive
		array('\..*\.(png|gif|jpeg|jpg)','.*'), // Image meta data file, e.g. ".example.png"
		array('\_vti\_.*','.*'), // Frontpage
		array('\.\_.*','.*'), // MacOS extended attributes
		array('tmpfile__.*','.*'), // ocp_tempnam produced temporarily files (unfortunately we can't specify a .tmp suffix)
		array('.*\.\d+','exports/file_backups'), // File backups (saved as revisions)
	);
	$ignore_filename_patterns=array( // Case insensitive; we'll use this only when we *need* directories that would match to be valid
	);

	if (($bitmask & IGNORE_NON_REGISTERED)!=0) // These aren't registered in any addon_registry hook, yet are bundled and in non-custom directories
	{
		$ignore_filenames_and_dir_names+=array(
			//'files.dat'=>'data', Actually is now (core.php)
			//'files_previous.dat'=>'data', Actually is now (core.php)
		);
	}

	if (($bitmask & IGNORE_BUNDLED_VOLATILE)!=0)
	{
		$ignore_filenames_and_dir_names+=array(
			// Bundled stuff that is not necessarily in a *_custom dir yet is volatile
			'info.php'=>'',
			'map.ini'=>'themes',
			'functions.dat'=>'data_custom',
			'download_tree_made.htm'=>'pages/html_custom/EN',
			'cedi_tree_made.htm'=>'site/pages/html_custom/EN',
			'ocp_sitemap.xml'=>'',
			'errorlog.php'=>'data_custom',
			'execute_temp.php'=>'data_custom',
			// These two too, although in git we don't change these as builds will not rebuild them
			'breadcrumbs.xml'=>'data_custom',
			'fields.xml'=>'data_custom',
		);
	}

	if ((($bitmask & IGNORE_BUNDLED_VOLATILE)!=0) || (($bitmask & IGNORE_BUNDLED_UNSHIPPED_VOLATILE)!=0))
	{
		$ignore_filenames_and_dir_names+=array(
			// Bundled stuff that is not necessarily in a *_custom dir yet is volatile and should not be included in shipped builds
			'chat_last_full_check.dat'=>'data_custom/modules/chat',
			'chat_last_msg.dat'=>'data_custom/modules/chat',
			'permissioncheckslog.php'=>'data_custom',
		);
	}

	if (($bitmask & IGNORE_ACCESS_CONTROLLERS)!=0)
	{
		$ignore_filenames_and_dir_names=array(
			'.htaccess'=>'.*',
			'index.html'=>'.*',
		)+$ignore_filenames_and_dir_names; // Done in this order as we are overriding .htaccess to block everywhere (by default blocks root only). PHP has weird array merge precedence rules.
	}

	if (($bitmask & IGNORE_USER_CUSTOMISE)!=0) // Ignores directories that user override files go in, not code or uploads (which IGNORE_CUSTOM_DIR_CONTENTS would cover): stuff edited through frontend to override bundled files
	{
		$ignore_filenames_and_dir_names+=array(
			'comcode_custom'=>'.*',
			'html_custom'=>'.*',
			'css_custom'=>'.*',
			'templates_custom'=>'.*',
			'images_custom'=>'.*',
			'lang_custom'=>'.*',
			'file_backups'=>'exports',
			'text_custom'=>'',
			'theme.ini'=>'themes/[^/]*',
		);
	}

	if (($bitmask & IGNORE_EDITFROM_FILES)!=0)
	{
		$ignore_extensions+=array(
			'editfrom'=>'.*',
		);
	}

	if (($bitmask & IGNORE_CUSTOM_DIR_CONTENTS)!=0) // Ignore all override directories, for both users and addons
	{
		if (($dir=='data_custom') && (in_array($filename,array('breadcrumbs.xml','fields.xml','errorlog.php','execute_temp.php','functions.dat'))))
		{
			// These are allowed, as they are volatile yet bundled. Use IGNORE_BUNDLED_VOLATILE if you don't want them.
		} else
		{
			$ignore_filename_patterns=array_merge($ignore_filename_and_dir_name_patterns,array(
				array('(?!cedi_tree_made\.htm$)(?!download_tree_made\.htm$)(?!index\.html$)(?!\.htaccess$).*','.*_custom(/.*)?'), // Stuff under custom folders; cedi_tree_made/download_tree_made is defined as an exception - it allows setting fewer permissions on the html_custom directory if wanted (ideally we would do this in a more modular way, but not worth the overhead)
			));
			$ignore_filename_and_dir_name_patterns=array_merge($ignore_filename_and_dir_name_patterns,array(
				//'.*\_custom'=>'.*', Let it find them, but work on the contents
				array('(?!index\.html$)(?!\.htaccess$).*','sources_custom/hooks/[^/]*'), // We don't want deep sources_custom hook directories either
				array('(?!index\.html$)(?!\.htaccess$).*','themes/default/images_custom'), // We don't want deep images_custom directories either
				array('(?!index\.html$)(?!\.htaccess$).*','data/areaedit/plugins/SpellChecker/aspell'), // We don't supply aspell outside git, too much space taken
				array('(?!index\.html$)(?!\.htaccess$).*','data_custom/modules/admin_stats'), // Various temporary XML files get created under here, for SVG graphs
				array('(?!pre_transcoding$)(?!index.html$)(?!\.htaccess$).*','uploads/.*'), // Uploads
			));
		}
	}

	if (($bitmask & IGNORE_HIDDEN_FILES)!=0)
	{
		$ignore_filename_and_dir_name_patterns=array_merge($ignore_filename_and_dir_name_patterns,array(
			array('\..*','.*'),
		));
	}

	if (($bitmask & IGNORE_REVISION_FILES)!=0) // E.g. global.css.<timestamp>
	{
		$ignore_filename_and_dir_name_patterns=array_merge($ignore_filename_and_dir_name_patterns,array(
			array('.*\.\d+','.*'),
		));
	}

	if (isset($ignore_filenames_and_dir_names[strtolower($filename)]))
	{
		if (preg_match('#^'.$ignore_filenames_and_dir_names[strtolower($filename)].'$#i',$dir)!=0) return true; // Check dir context
	}

	$extension=get_file_extension($filename);
	if (isset($ignore_extensions[strtolower($extension)]))
	{
		if (preg_match('#^'.$ignore_extensions[strtolower($extension)].'$#i',$dir)!=0) return true; // Check dir context
	}
	foreach (array_merge($is_file?$ignore_filename_patterns:array(),$ignore_filename_and_dir_name_patterns) as $pattern)
	{
		list($filename_pattern,$dir_pattern)=$pattern;
		if (preg_match('#^'.$filename_pattern.'$#i',$filename)!=0)
		{
			if (preg_match('#^'.$dir_pattern.'$#i',$dir)!=0) // Check dir context
			{
				return true;
			}
		}
	}

	if (($dir!='') && (is_dir(get_file_base().'/'.$filepath)) && (file_exists(get_file_base().'/'.$filepath.'/sources_custom'))) // ocPortal dupe (e.g. backup) install
	{
		return true;
	}

	if (($bitmask & IGNORE_CUSTOM_THEMES)!=0)
	{
		if ((preg_match('#^themes($|/)#i',$dir)!=0) && (substr($filepath,0,strlen('themes/default/'))!='themes/default/') && (!in_array(strtolower($filepath),array('themes/default','themes/index.html','themes/map.ini')))) return true;
	}

	if (($bitmask & IGNORE_CUSTOM_ZONES)!=0)
	{
		if ((file_exists(get_file_base().'/'.$filepath.'/index.php')) && (file_exists(get_file_base().'/'.$filepath.'/pages')) && (!in_array(strtolower($filename),array('adminzone','collaboration','cms','forum','site'))))
			return true;
	}

	if (($bitmask & IGNORE_NONBUNDLED_SCATTERED)!=0)
	{
		if (strtolower($filepath)=='data_custom/addon_screenshots') return true; // Relating to addon build, but not defined in addons
		if (strtolower($filepath)=='exports/static') return true; // Empty directory, so has to be a special exception
		if (strtolower($filepath)=='exports/builds') return true; // Needed to stop build recursion
		if (file_exists(get_custom_file_base().'/data_custom/addon_files.txt'))
		{
			static $addon_list=NULL;
			if ($addon_list===NULL) $addon_list=strtolower(file_get_contents(unixify_line_format(get_custom_file_base().'/data_custom/addon_files.txt')));
			if (strpos($addon_list,' - '.strtolower($filepath).chr(10))!==false)
			{
				return true;
			}
		} else
		{
			static $addon_files=NULL;
			if ($addon_files===NULL) $addon_files=array_map('strtolower',collapse_1d_complexity('filename',$GLOBALS['SITE_DB']->query_select('addons_files',array('filename'))));
			if (in_array(strtolower($filepath),$addon_files)) return true;
		}
		// Note that we have no support for identifying directories related to addons, only files inside. Code using this function should detect directories with no usable files in as relating to addons.
	}

	if (($bitmask & IGNORE_NON_EN_SCATTERED_LANGS)!=0)
	{
		// Wrong lang packs
		if (((strlen($filename)==2) && (strtoupper($filename)==$filename) && (strtolower($filename)!=$filename) && ($filename!='EN')) || ($filename=='EN_us') || ($filename=='ZH-TW') || ($filename=='ZH-CN'))
		{
			return true;
		}
	}

	return false;
}

/**
 * Delete all the contents of a directory, and any subdirectories of that specified directory (recursively).
 *
 * @param  PATH			The pathname to the directory to delete
 * @param  boolean		Whether to preserve files there by default
 * @param  boolean		Whether to just delete files
 */
function deldir_contents($dir,$default_preserve=false,$just_files=false)
{
	require_code('files2');
	_deldir_contents($dir,$default_preserve,$just_files);
}

/**
 * Ensure that the specified file/folder is writeable for the FTP user (so that it can be deleted by the system), and should be called whenever a file is uploaded/created, or a folder is made. We call this function assuming we are giving world permissions
 *
 * @param  PATH			The full pathname to the file/directory
 * @param  integer		The permissions to make (not the permissions are reduced if the function finds that the file is owned by the web user [doesn't need world permissions then])
 */
function fix_permissions($path,$perms=0666) // We call this function assuming we are giving world permissions
{
	// If the file user is different to the FTP user, we need to make it world writeable
	if ((!is_suexec_like()) || (ocp_srv('REQUEST_METHOD')==''))
	{
		@chmod($path,$perms);
	} else // Otherwise we do not
	{
		if ($perms==0666) @chmod($path,0644);
		elseif ($perms==0777) @chmod($path,0755);
		else @chmod($path,$perms);
	}

	global $_CREATED_FILES; // From ocProducts PHP version, for development testing
	if (isset($_CREATED_FILES))
		foreach ($_CREATED_FILES as $i=>$x)
			if ($x==$path) unset($_CREATED_FILES[$i]);
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
function http_download_file($url,$byte_limit=NULL,$trigger_error=true,$no_redirect=false,$ua='ocPortal',$post_params=NULL,$cookies=NULL,$accept=NULL,$accept_charset=NULL,$accept_language=NULL,$write_to_file=NULL,$referer=NULL,$auth=NULL,$timeout=6.0,$is_xml=false,$files=NULL)
{
	require_code('files2');
	return _http_download_file($url,$byte_limit,$trigger_error,$no_redirect,$ua,$post_params,$cookies,$accept,$accept_charset,$accept_language,$write_to_file,$referer,$auth,$timeout,$is_xml,$files);
}

