<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/
/*EXTRA FUNCTIONS: dbx\_.+*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_database_drivers
 */

require_code('database/shared/mysql');

/**
 * Standard code module initialisation function.
 */
function init__database__mysql_dbx()
{
	global $CACHE_DB;
	$CACHE_DB=array();
	global $LAST_SELECT_DB;
	$LAST_SELECT_DB=NULL;
}

class Database_Static_mysql_dbx extends Database_super_mysql
{

	/**
	 * Get a database connection. This function shouldn't be used by you, as a connection to the database is established automatically.
	 *
	 * @param  boolean		Whether to create a persistant connection
	 * @param  string			The database name
	 * @param  string			The database host (the server)
	 * @param  string			The database connection username
	 * @param  string			The database connection password
	 * @param  boolean		Whether to on error echo an error and return with a NULL, rather than giving a critical error
	 * @return ?array			A database connection (note for mySQL, it's actually a pair, containing the database name too: because we need to select the name before each query on the connection) (NULL: error)
	 */
	function db_get_connection($persistent,$db_name,$db_host,$db_user,$db_password,$fail_ok=false)
	{
		if (!function_exists('dbx_connect'))
		{
			$error='dbx not on server (anymore?). Try using the \'mysql\' database driver. To use it, edit the info.php config file.';
			if ($fail_ok)
			{
				echo $error;
				return NULL;
			}
			critical_error('PASSON',$error);
		}
	
		// Potential cacheing
		global $CACHE_DB;
		$x=serialize(array($db_name,$db_host));
		if (array_key_exists($x,$CACHE_DB))
		{
			return array($x,$db_name);
		}
	
		$db=@dbx_connect('mysql',$db_host,$db_name,$db_user,$db_password,$persistent?1:0);
		if (($db===false) || (is_null($db)))
		{
			$error='Could not connect to database/database-server';
			if ($fail_ok)
			{
				echo $error;
				return NULL;
			}
			critical_error('PASSON',$error); //warn_exit(do_lang_tempcode('CONNECT_DB_ERROR')); // purposely not ===false
		}
		global $LAST_SELECT_DB;
		$LAST_SELECT_DB=$db;
	
		global $SITE_INFO;
		if (!array_key_exists('database_charset',$SITE_INFO)) $SITE_INFO['database_charset']=(strtolower(get_charset())=='utf-8')?'utf8':'latin1';
		@dbx_query($db,'SET NAMES "'.addslashes($SITE_INFO['database_charset']).'"');
		@dbx_query($db,'SET SQL_BIG_SELECTS=1');
		if (get_forum_type()=='ocf') @dbx_query($db,'SET sql_mode=STRICT_ALL_TABLES');

		return array($db,$db_name);
	}
	
	/**
	 * Escape a string so it may be inserted into a query. If SQL statements are being built up and passed using db_query then it is essential that this is used for security reasons. Otherwise, the abstraction layer deals with the situation.
	 *
	 * @param  string			The string
	 * @return string			The escaped string
	 */
	function db_escape_string($string)
	{
		global $LAST_SELECT_DB;
		if (is_null($LAST_SELECT_DB)) return addslashes($string);
		return dbx_escape_string($LAST_SELECT_DB,$string);
	}
	
	/**
	 * Find whether full-text-search is present
	 *
	 * @param  array			A DB connection
	 * @return boolean		Whether it is
	 */
	function db_has_full_text($db)
	{
		if ($this->using_innodb()) return false;
		unset($db);
		return true; // Assumes yes, because we are in a limited environment to actually check
	}
	
	/**
	 * This function is a very basic query executor. It shouldn't usually be used by you, as there are abstracted versions available.
	 *
	 * @param  string			The complete SQL query
	 * @param  array			A DB connection
	 * @param  ?integer		The maximum number of rows to affect (NULL: no limit)
	 * @param  ?integer		The start row to affect (NULL: no specification)
	 * @param  boolean		Whether to output an error on failure
	 * @param  boolean		Whether to get the autoincrement ID created for an insert query
	 * @return ?mixed			The results (NULL: no results), or the insert ID
	 */
	function db_query($query,$db_parts,$max=NULL,$start=NULL,$fail_ok=false,$get_insert_id=false)
	{
		list($db,)=$db_parts;
	
		if (strlen($query)>500000) // Let's hope we can fail on this, because it's a huge query. We can only allow it if mySQL can.
		{
			$test_result=$this->db_query('SHOW VARIABLES LIKE \'max_allowed_packet\'',$db_parts,NULL,NULL,true);

			if (!is_array($test_result)) return NULL;
			if (intval($test_result[0]['Value'])<intval(strlen($query)*1.2))
			{
				if ($get_insert_id) fatal_exit(do_lang_tempcode('QUERY_FAILED_TOO_BIG',escape_html($query)));

				return NULL;
			}
		}

		if ((!is_null($max)) && (!is_null($start))) $query.=' LIMIT '.strval((integer)$start).','.strval((integer)$max);
		elseif (!is_null($max)) $query.=' LIMIT '.strval((integer)$max);
		elseif (!is_null($start)) $query.=' LIMIT '.strval((integer)$start).',30000000';
	
		$results=@dbx_query($db,$query,DBX_RESULT_INFO);
		if (($results===0) && ((!$fail_ok) || (strpos(dbx_error($db),'is marked as crashed and should be repaired')!==false)))
		{
			$err=dbx_error($db);
			if (function_exists('ocp_mark_as_escaped')) ocp_mark_as_escaped($err);
			if ((!running_script('upgrader')) && (get_page_name()!='admin_import'))
			{
				if (!function_exists('do_lang') || is_null(do_lang('QUERY_FAILED',NULL,NULL,NULL,NULL,false))) fatal_exit(htmlentities('Query failed: '.$query.' : '.$err));

				fatal_exit(do_lang_tempcode('QUERY_FAILED',escape_html($query),($err)));
			} else
			{
				echo htmlentities('Database query failed: '.$query.' [').($err).htmlentities(']'.'<br />'.chr(10));
				return NULL;
			}
		}
	
		if ((is_object($results)) && ((strtoupper(substr($query,0,7))=='SELECT ') || (strtoupper(substr($query,0,9))=='DESCRIBE ') || (strtoupper(substr($query,0,5))=='SHOW ')))
		{
			return $this->db_get_query_rows($results);
		}
	
		if ($get_insert_id)
		{
			if (strtoupper(substr($query,0,7))=='UPDATE ')
			{
				if (function_exists('mysql_affected_rows'))
					return mysql_affected_rows($db->handle);
				else return (-1);
			}
	
			//return mysql_insert_id($db->handle);
			if (strtoupper(substr($query,0,12))=='INSERT INTO ')
			{
				$table=substr($query,12,strpos($query,' ',12)-12);
				$rows=$this->db_query('SELECT MAX(id) AS x FROM '.$table,$db_parts,1,0,false,false);
				return $rows[0]['x'];
			}
		}
	
		return NULL;
	}
	
	/**
	 * Get the rows returned from a SELECT query.
	 *
	 * @param  object			The query result pointer
	 * @return array			A list of row maps
	 */
	function db_get_query_rows($results)
	{
		$num_fields=$results->cols;
		$names=array();
		$types=array();
		for ($x=0;$x<$num_fields;$x++)
		{
			$names[$x]=$results->info['name'][$x];
			$types[$x]=$results->info['type'][$x];
		}
	
		$out=array();
		$newrow=array();
		foreach ($results->data as $row)
		{
			$j=0;
			foreach ($row as $v)
			{
				$name=$names[$j];
				$type=$types[$j];
	
				if (($type=='int') || ($type=='integer'))
				{
					if ((is_null($v)) || ($v==='')) // Roadsend returns empty string instead of NULL
					{
						$newrow[$name]=NULL;
					} else
					{
						$_v=intval($v);
						if (strval($_v)!=$v)
						{
							$newrow[$name]=floatval($v);
						} else
						{
							$newrow[$name]=$_v;
						}
					}
				}
				elseif (($type=='unknown') && (is_string($v)))
				{
					if ((strlen($v)==1) && (($v[0]==chr(0)) || ($v[0]==chr(0))))
					{
						if (!is_null($v)) $newrow[$name]=ord($v); else $newrow[$name]=NULL; // 0/1 char for BIT field
					} else
					{
						$newrow[$name]=intval($v);
					}
				} else $newrow[$name]=$v;
	
				$j++;
			}
	
			$out[]=$newrow;
		}
		return $out;
	}

}


