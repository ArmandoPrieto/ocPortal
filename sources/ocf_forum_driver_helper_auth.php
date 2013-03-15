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
 * @package		core_ocf
 */

/**
 * Find if the given member id and password is valid. If username is NULL, then the member id is used instead.
 * All authorisation, cookies, and form-logins, are passed through this function.
 * Some forums do cookie logins differently, so a Boolean is passed in to indicate whether it is a cookie login.
 *
 * @param  object			Link to the real forum driver
 * @param  ?SHORT_TEXT	The member username (NULL: don't use this in the authentication - but look it up using the ID if needed)
 * @param  ?MEMBER		The member id (NULL: use member name)
 * @param  MD5				The md5-hashed password
 * @param  string			The raw password
 * @param  boolean		Whether this is a cookie login, determines how the hashed password is treated for the value passed in
 * @return array			A map of 'id' and 'error'. If 'id' is NULL, an error occurred and 'error' is set
 */
function _forum_authorise_login($this_ref,$username,$userid,$password_hashed,$password_raw,$cookie_login=false)
{
	require_code('ocf_forum_driver_helper_auth');

	$out=array();
	$out['id']=NULL;

	require_code('ocf_members');
	require_code('ocf_groups');
	if (!function_exists('require_lang')) require_code('lang');
	if (!function_exists('do_lang_tempcode')) require_code('tempcode');
	if (!function_exists('require_lang')) return $out;
	require_lang('ocf');
	require_code('mail');

	$skip_auth=false;

	if ($userid===NULL)
	{
		$rows=$this_ref->connection->query_select('f_members',array('*'),array('m_username'=>$username),'',1);
		if ((!array_key_exists(0,$rows)) && (get_option('one_per_email_address')=='1'))
		{
			$rows=$this_ref->connection->query_select('f_members',array('*'),array('m_email_address'=>$username),'ORDER BY id ASC',1);
		}
		if (array_key_exists(0,$rows))
		{
			$this_ref->MEMBER_ROWS_CACHED[$rows[0]['id']]=$rows[0];
			$userid=$rows[0]['id'];
		}
	} else
	{
		$rows[0]=$this_ref->get_member_row($userid);
	}

	// LDAP to the rescue if we couldn't get a row
	global $LDAP_CONNECTION;
	if ((!array_key_exists(0,$rows)) && ($LDAP_CONNECTION!==NULL) && ($userid===NULL))
	{
		// See if LDAP has it -- if so, we can add
		$test=ocf_is_on_ldap($username);
		if (!$test)
		{
			$out['error']=(do_lang_tempcode('_MEMBER_NO_EXIST',escape_html($username)));
			return $out;
		}

		$test_auth=ocf_ldap_authorise_login($username,$password_raw);
		if ($test_auth['m_pass_hash_salted']=='!!!')
		{
			$out['error']=(do_lang_tempcode('MEMBER_BAD_PASSWORD'));
			return $out;
		}

		if ($test)
		{
			require_code('ocf_members_action');
			require_code('ocf_members_action2');
			$completion_form_submitted=(trim(post_param('email_address',''))!='');
			if ((!$completion_form_submitted) && (get_value('no_finish_profile')!=='1')) // UI
			{
				@ob_end_clean();
				if (!function_exists('do_header')) require_code('site');
				$middle=ocf_member_external_linker_ask($username,'ldap',ocf_ldap_guess_email($username));
				$tpl=globalise($middle,NULL,'',true);
				$tpl->evaluate_echo();
				exit();
			} else
			{
				$userid=ocf_member_external_linker($username,uniqid(''),'ldap');
				$row=$this_ref->get_member_row($userid);
			}
		}
	}

	if ((!array_key_exists(0,$rows)) || ($rows[0]===NULL)) // All hands to lifeboats
	{
		$out['error']=(do_lang_tempcode('_MEMBER_NO_EXIST',escape_html($username)));
		return $out;
	}
	$row=$rows[0];

	// Now LDAP can kick in and get the correct hash
	if (ocf_is_ldap_member($userid))
	{
		//$rows[0]['m_pass_hash_salted']=ocf_get_ldap_hash($userid);

		// Doesn't exist any more? This is a special case - the 'LDAP member' exists in our DB, but not LDAP. It has been deleted from LDAP or LDAP server has jumped
		/*if (is_null($rows[0]['m_pass_hash_salted']))
		{
			$out['error']=(do_lang_tempcode('_MEMBER_NO_EXIST',$username));
			return $out;
		} No longer appropriate with new authentication mode - instead we just have to give an invalid password message  */

		$row=array_merge($row,ocf_ldap_authorise_login($username,$password_hashed));
	}

	if (addon_installed('unvalidated'))
	{
		if ($row['m_validated']==0)
		{
			$out['error']=(do_lang_tempcode('MEMBER_NOT_VALIDATED_STAFF'));
			return $out;
		}
	}
	if ($row['m_validated_email_confirm_code']!='')
	{
		$out['error']=(do_lang_tempcode('MEMBER_NOT_VALIDATED_EMAIL'));
		return $out;
	}
	if ($this_ref->is_banned($row['id'])) // All hands to the guns
	{
		$out['error']=(do_lang_tempcode('MEMBER_BANNED'));
		return $out;
	}

	// Check password
	if (!$skip_auth)
	{
		// Choose a compatibility screen.
		// Note that almost all cookie logins are the same. This is because the cookie logins use OCF cookies, regardless of compatibility scheme.
		$password_compatibility_scheme=$row['m_password_compat_scheme'];
		switch ($password_compatibility_scheme)
		{
			case '': // ocPortal style salted MD5 algorithm
			case 'temporary': // as above, but forced temporary password
				if ($cookie_login)
				{
					if ($password_hashed!=$row['m_pass_hash_salted'])
					{
						require_code('tempcode'); // This can be incidental even in fast AJAX scripts, if an old invalid cookie is present, so we need tempcode for do_lang_tempcode
						$out['error']=(do_lang_tempcode('MEMBER_BAD_PASSWORD'));
						return $out;
					}
				} else
				{
					if (md5($row['m_pass_salt'].$password_hashed)!=$row['m_pass_hash_salted'])
					{
						$out['error']=(do_lang_tempcode('MEMBER_BAD_PASSWORD'));
						return $out;
					}
				}
				break;
			case 'plain':
				if ($password_hashed!=md5($row['m_pass_hash_salted']))
				{
					$out['error']=(do_lang_tempcode('MEMBER_BAD_PASSWORD'));
					return $out;
				}
				break;
			case 'md5': // Old style plain md5		(also works if both are unhashed: used for LDAP)
				if (($password_hashed!=$row['m_pass_hash_salted']) && ($password_hashed!='!!!')) // The !!! bit would never be in a hash, but for plain text checks using this same code, we sometimes use '!!!' to mean 'Error'.
				{
					$out['error']=(do_lang_tempcode('MEMBER_BAD_PASSWORD'));
					return $out;
				}
				break;
	/*		case 'httpauth':
				// This is handled in get_member()  */
				break;
			case 'ldap':
				if ($password_hashed!=$row['m_pass_hash_salted'])
				{
					$out['error']=(do_lang_tempcode('MEMBER_BAD_PASSWORD'));
					return $out;
				}
				break;
			default:
				$path=get_file_base().'/sources_custom/hooks/systems/ocf_auth/'.$password_compatibility_scheme.'.php';
				if (!file_exists($path)) $path=get_file_base().'/sources/hooks/systems/ocf_auth/'.$password_compatibility_scheme.'.php';
				if (!file_exists($path))
				{
					$out['error']=(do_lang_tempcode('UNKNOWN_AUTH_SCHEME_IN_DB'));
					return $out;
				}
				require_code('hooks/systems/ocf_auth/'.$password_compatibility_scheme);
				$ob=object_factory('Hook_ocf_auth_'.$password_compatibility_scheme);
				$error=$ob->auth($username,$userid,$password_hashed,$password_raw,$cookie_login,$row);
				if (!is_null($error))
				{
					$out['error']=$error;
					return $out;
				}
				break;
		}
	}

	// Ok, authorised basically, but we need to see if this is a valid login IP
	if ((ocf_get_best_group_property($this_ref->get_members_groups($row['id']),'enquire_on_new_ips')==1)) // High security usergroup membership
	{
		global $SENT_OUT_VALIDATE_NOTICE;
		$ip=get_ip_address(3);
		$test2=$this_ref->connection->query_select_value_if_there('f_member_known_login_ips','i_val_code',array('i_member_id'=>$row['id'],'i_ip'=>$ip));
		if (((is_null($test2)) || ($test2!='')) && (!compare_ip_address($ip,$row['m_ip_address'])))
		{
			if (!$SENT_OUT_VALIDATE_NOTICE)
			{
				if (!is_null($test2)) // Tidy up
				{
					$this_ref->connection->query_delete('f_member_known_login_ips',array('i_member_id'=>$row['id'],'i_ip'=>$ip),'',1);
				}

				$code=!is_null($test2)?$test2:uniqid('');
				$this_ref->connection->query_insert('f_member_known_login_ips',array('i_val_code'=>$code,'i_member_id'=>$row['id'],'i_ip'=>$ip));
				$url=find_script('validateip').'?code='.$code;
				$url_simple=find_script('validateip');
				$mail=do_lang('IP_VERIFY_MAIL',comcode_escape($url),comcode_escape(get_ip_address()),array($url_simple,$code),get_lang($row['id']));
				$email_address=$row['m_email_address'];
				if ($email_address=='') $email_address=get_option('staff_address');
				if ((running_script('index')) || (running_script('iframe')))
					mail_wrap(do_lang('IP_VERIFY_MAIL_SUBJECT',NULL,NULL,NULL,get_lang($row['id'])),$mail,array($email_address),$row['m_username'],'','',1);

				$SENT_OUT_VALIDATE_NOTICE=true;
			}

			$out['error']=do_lang_tempcode('REQUIRES_IP_VALIDATION');
			return $out;
		}
	}

	$this_ref->ocf_flood_control($row['id']);

	$out['id']=$row['id'];
	return $out;
}

