<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

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
function init__mail()
{
	require_lang('mail');

	global $SENDING_MAIL,$EMAIL_ATTACHMENTS;
	$SENDING_MAIL=false;
	$EMAIL_ATTACHMENTS=array();
}

/**
 * Replace an HTML img tag such that it is cid'd. Callback for preg_replace_callback.
 *
 * @param  array			Matches
 * @return string			Replacement
 */
function _mail_img_rep_callback($matches)
{
	global $CID_IMG_ATTACHMENT;
	$cid=uniqid('').'@'.get_domain();
	$CID_IMG_ATTACHMENT[$cid]=$matches[2];
	return '<img '.$matches[1].'src="cid:'.$cid.'"';
}

/**
 * Replace CSS image references such that it is cid'd. Callback for preg_replace_callback.
 *
 * @param  array			Matches
 * @return string			Replacement
 */
function _mail_css_rep_callback($matches)
{
	global $CID_IMG_ATTACHMENT;
	$cid=uniqid('').'@'.get_domain();
	if ((basename($matches[1])!='keyboard.png') && (basename($matches[1])!='email_link.png') && (basename($matches[1])!='external_link.png'))
		$CID_IMG_ATTACHMENT[$cid]=$matches[1];
	return 'url(\'cid:'.$cid.'\')';
}

/**
 * Indent text lines. Callback for preg_replace_callback.
 *
 * @param  array			Matches
 * @return string			Replacement
 */
function _indent_callback($matches)
{
	return '      '.str_replace(chr(10),chr(10).'      ',$matches[1]);
}

/**
 * Make titles readable. Callback for preg_replace_callback.
 *
 * @param  array			Matches
 * @return string			Replacement
 */
function _title_callback($matches)
{
	$symbol='-';
	if (strpos($matches[1],'1')!==false) $symbol='=';
	return $matches[2].chr(10).str_repeat($symbol,strlen($matches[2]));
}

/**
 * Make boxes readable. Callback for preg_replace_callback.
 *
 * @param  array			Matches
 * @return string			Replacement
 */
function _box_callback($matches)
{
	return $matches[1].chr(10).str_repeat('-',strlen($matches[1])).chr(10).$matches[2];
}

/**
 * Make some Comcode more readable.
 *
 * @param  string			Comcode text to change
 * @return string			Clean text
 */
function comcode_to_clean_text($message_plain)
{
	//$message_plain=str_replace("\n",'',$message_plain);

	if ((strpos($message_plain,'[')===false) && (strpos($message_plain,'{')===false)) return $message_plain;

	require_code('tempcode_compiler');
	if ((strpos($message_plain,'[code')===false) && (strpos($message_plain,'[no_parse')===false) && (strpos($message_plain,'[tt')===false))
	{
		// Remove directives etc
		do
		{
			$before=$message_plain;
			$message_plain=preg_replace('#\{([^\}\{]*)\}#','',$message_plain);
		}
		while ($message_plain!=$before);

		$message_plain=static_evaluate_tempcode(template_to_tempcode($message_plain));
	}

	$match=array();
	preg_match("#\[semihtml\](.*)\[\/semihtml\]#Us",$message_plain,$match);
	$message_plain=array_key_exists(1,$match) ? $match[1] : $message_plain;
	preg_match("#\[html\](.*)\[/html\]#Us",$message_plain,$match);
	$message_plain=array_key_exists(1,$match) ? $match[1] : $message_plain;

	$message_plain=preg_replace("#\[url=\"([^\"]*)\"(.*)\]([^\[\]]*)\[/url\]#",'${1}',$message_plain);
	
	$message_plain=preg_replace("#\[img(.*)\]([^\[\]]*)\[/img\]#",'',$message_plain);

	$message_plain=@html_entity_decode(strip_tags($message_plain),ENT_QUOTES,get_charset());
		
	$message_plain=str_replace(']http',']'.chr(10).'http',str_replace('[/url]',chr(10).'[/url]',$message_plain));
	$message_plain=preg_replace('#\[random [^=]*="([^"]*)"[^\]]*\].*\[/random\]#Us','${1}',$message_plain);
	$message_plain=preg_replace('#\[abbr="([^"]*)"[^\]]*\].*\[/abbr\]#Us','${1}',$message_plain);
	$message_plain=preg_replace_callback('#\[indent[^\]]*\](.*)\[/indent\]#Us','_indent_callback',$message_plain);
	$message_plain=preg_replace_callback('#\[title([^\]])*\](.*)\[/title\]#Us','_title_callback',$message_plain);
	$message_plain=preg_replace_callback('#\[box="([^"]*)"[^\]]*\](.*)\[/box\]#Us','_box_callback',$message_plain);
	$tags_to_strip_inards=array('if_in_group','snapback','post','thread','topic','include','staff_note','attachment','attachment2','attachment_safe','contents','block','random');
	foreach ($tags_to_strip_inards as $s)
	{
		$message_plain=preg_replace('#\['.$s.'[^\]]*\].*\[/'.$s.'\]#Us','',$message_plain);
	}
	$message_plain=preg_replace('#\[surround="accessibility_hidden"\].*\[/surround\]#Us','',$message_plain);
	$tags_to_strip=array('ticker','jumping','right','center','left','align','list','concepts','html','semihtml','concept','size','color','font','tt','address','sup','sub','box');
	foreach ($tags_to_strip as $s)
	{
		$message_plain=preg_replace('#\['.$s.'[^\]]*\](.*)\[/'.$s.'\]#U','${1}',$message_plain);
	}
	$message_plain=str_replace(
		array('[/*]','[*]','[list]'.chr(10),chr(10).'[/list]','[list]','[/list]','[b]','[/b]','[i]','[/i]','[u]','[/u]','[highlight]','[/highlight]'),
		array('',' - ','','','','','**','**','*','*','__','__','***','***'),
		$message_plain);
	$message_plain=preg_replace('#\[list[^\[\]]*\]#','',$message_plain);
	
	$message_plain=preg_replace('#\{\$,[^\{\}]*\}#','',$message_plain);

	return trim($message_plain);
}

/**
 * Attempt to send an e-mail to the specified recipient. The mail will be forwarding to the CC address specified in the options (if there is one, and if not specified not to cc).
 * The mail will be sent in dual HTML/text format, where the text is the unconverted comcode source: if a member does not read HTML mail, they may wish to fallback to reading that.
 *
 * @param  string			The subject of the mail in plain text
 * @param  LONG_TEXT		The message, as Comcode
 * @param  ?array			The destination (recipient) e-mail addresses [array of strings] (NULL: site staff address)
 * @param  ?mixed			The recipient name. Array or string. (NULL: site name)
 * @param  EMAIL			The from address (blank: site staff address)
 * @param  string			The from name (blank: site name)
 * @param  integer		The message priority (1=urgent, 3=normal, 5=low)
 * @range  1 5
 * @param  ?array			An list of attachments (each attachment being a map, path=>filename) (NULL: none)
 * @param  boolean		Whether to NOT CC to the CC address
 * @param  ?MEMBER		Convert comcode->tempcode as this member (a privilege thing: we don't want people being able to use admin rights by default!) (NULL: guest)
 * @param  boolean		Replace above with arbitrary admin
 * @param  boolean		HTML-only
 * @param  boolean		Whether to bypass queueing, because this code is running as a part of the queue management tools
 * @return ?tempcode		A full page (not complete XHTML) piece of tempcode to output (NULL: it worked so no tempcode message)
 */
function mail_wrap($subject_tag,$message_raw,$to_email=NULL,$to_name=NULL,$from_email='',$from_name='',$priority=3,$attachments=NULL,$no_cc=false,$as=NULL,$as_admin=false,$in_html=false,$coming_out_of_queue=false)
{
	if (running_script('stress_test_loader')) return NULL;

	global $EMAIL_ATTACHMENTS;
	$EMAIL_ATTACHMENTS=array();

	require_code('site');
	require_code('mime_types');

	if (is_null($as)) $as=$GLOBALS['FORUM_DRIVER']->get_guest_id();

	if (!$coming_out_of_queue)
	{
		$GLOBALS['SITE_DB']->query('DELETE FROM '.get_table_prefix().'logged_mail_messages WHERE m_date_and_time<'.strval(time()-60*60*24*14).' AND m_queued=0'); // Log it all for 2 weeks, then delete

		$GLOBALS['SITE_DB']->query_insert('logged_mail_messages',array(
			'm_subject'=>substr($subject_tag,0,255),
			'm_message'=>$message_raw,
			'm_to_email'=>serialize($to_email),
			'm_to_name'=>serialize($to_name),
			'm_from_email'=>$from_email,
			'm_from_name'=>$from_name,
			'm_priority'=>3,
			'm_attachments'=>serialize($attachments),
			'm_no_cc'=>$no_cc?1:0,
			'm_as'=>$as,
			'm_as_admin'=>$as_admin?1:0,
			'm_in_html'=>$in_html?1:0,
			'm_date_and_time'=>time(),
			'm_member_id'=>get_member(),
			'm_url'=>get_self_url(true),
			'm_queued'=>(get_value('mail_queue')==='1')?1:0,
		));
		
		if (get_value('mail_queue')==='1') return;
	}

	if (count($attachments)==0) $attachments=NULL;

	global $SENDING_MAIL;
	if ($SENDING_MAIL) return NULL;
	$SENDING_MAIL=true;

	// To and from, and language
	$staff_address=get_option('staff_address');
	if (is_null($to_email)) $to_email=array($staff_address);
	$to_email_new=array();
	foreach ($to_email as $test_address)
	{
		if ($test_address!='') $to_email_new[]=$test_address;
	}
	$to_email=$to_email_new;
	if ($to_email==array())
	{
		$SENDING_MAIL=false;
		return NULL;
	}
	if ($to_email[0]==$staff_address)
	{
		$lang=get_site_default_lang();
	} else
	{
		$lang=user_lang();
		if (method_exists($GLOBALS['FORUM_DRIVER'],'get_member_from_email_address'))
		{
			$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_email_address($to_email[0]);
			if (!is_null($member_id))
			{
				$lang=get_lang($member_id);
			}
		}
	}
	if (is_null($to_name)) $to_name=get_site_name();
	if ($from_email=='') $from_email=get_option('staff_address');
	if ($from_name=='') $from_name=get_site_name();
	$from_email=str_replace("\r",'',$from_email);
	$from_email=str_replace("\n",'',$from_email);
	$from_name=str_replace("\r",'',$from_name);
	$from_name=str_replace("\n",'',$from_name);

	$theme=method_exists($GLOBALS['FORUM_DRIVER'],'get_theme')?$GLOBALS['FORUM_DRIVER']->get_theme():'default';
	if ($theme=='default') // Sucks, probably due to sending from Admin Zone...
	{
		$theme=$GLOBALS['FORUM_DRIVER']->get_theme(''); // ... So get theme of welcome zone
	}

	// Line termination is fiddly. It is safer to rely on sendmail supporting \n than undetectable-qmail/postfix-masquerading-as-sendmail not supporting the correct \r\n
	/*$sendmail_path=ini_get('sendmail_path');
	if ((strpos($sendmail_path,'qmail')!==false) || (strpos($sendmail_path,'sendmail')!==false))
		$line_term="\n";
	else
		$line_term="\r\n";
	*/
	if ((strtoupper(substr(PHP_OS,0,3))=='WIN') || (get_option('smtp_sockets_use')=='1'))
	{
		$line_term="\r\n";
	/*} elseif (strtoupper(substr(PHP_OS,0,3))=='MAC')
	{
		$line_term="\r";*/
	} else
	{
		$line_term="\n";
	}

	// We use the boundary to seperate message parts
	$_boundary=uniqid('ocPortal');
	$boundary=$_boundary.'_1';
	$boundary2=$_boundary.'_2';
	$boundary3=$_boundary.'_3';

	// Our subject
	$subject=do_template('MAIL_SUBJECT',array('_GUID'=>'44a57c666bb00f96723256e26aade9e5','SUBJECT_TAG'=>$subject_tag),$lang,false,NULL,'.tpl','templates',$theme);
	$tightened_subject=$subject->evaluate($lang); // Note that this is slightly against spec, because characters aren't forced to be printable us-ascii. But it's better we allow this (which works in practice) than risk incompatibility via charset-base64 encoding.
	$tightened_subject=str_replace(chr(10),'',$tightened_subject);
	$tightened_subject=str_replace(chr(13),'',$tightened_subject);

	$regexp='#^[\x'.dechex(32).'-\x'.dechex(126).']*$#';
	if (preg_match($regexp,$tightened_subject)==0) $tightened_subject='=?'.do_lang('charset',NULL,NULL,NULL,$lang).'?B?'.base64_encode($tightened_subject)."?=";
	if (preg_match($regexp,$from_name)==0) $from_name='=?'.do_lang('charset',NULL,NULL,NULL,$lang).'?B?'.base64_encode($from_name)."?=";
	if (is_array($to_name))
	{
		foreach ($to_name as $i=>$_to_name)
		{
			if (preg_match($regexp,$_to_name)==0) $to_name[$i]='=?'.do_lang('charset',NULL,NULL,NULL,$lang).'?B?'.base64_encode($_to_name)."?=";
		}
	} else
	{
		if (preg_match($regexp,$to_name)==0) $to_name='=?'.do_lang('charset',NULL,NULL,NULL,$lang).'?B?'.base64_encode($to_name)."?=";
	}

	$simplify_when_can=true; // Used for testing. Not actually needed

	// Evaluate message. Needs doing early so we know if we have any headers
	$GLOBALS['NO_LINK_TITLES']=true;
	global $LAX_COMCODE;
	$temp=$LAX_COMCODE;
	$LAX_COMCODE=true;
	$html_content=comcode_to_tempcode($message_raw,$as,$as_admin);
	$LAX_COMCODE=$temp;
	$GLOBALS['NO_LINK_TITLES']=false;
	$attachments=array_merge(is_null($attachments)?array():$attachments,$EMAIL_ATTACHMENTS);

	// Headers
	$website_email=get_option('website_email');
	if ($website_email=='') $website_email=$from_email;
	$headers='From: "'.$from_name.'" <'.$website_email.'>'.$line_term;
	$headers.='Reply-To: <'.$from_email.'>'.$line_term;
	$headers.='Return-Path: <'.$website_email.'>'.$line_term;
	$headers.='X-Sender: <'.$website_email.'>'.$line_term;
	$cc_address=$no_cc?'':get_option("cc_address");
	if (($cc_address!='') && (!in_array($cc_address,$to_email))) $headers.=((get_option('bcc')=='1')?'Bcc: <':'Cc: <').$cc_address.'>'.$line_term;
	$headers.='Message-ID: <'.$_boundary.'@'.get_domain().'>'.$line_term;
	$headers.='X-Priority: '.strval($priority).$line_term;
	$brand_name=get_value('rebrand_name');
	if (is_null($brand_name)) $brand_name='ocPortal';
	$headers.='X-Mailer: '.$brand_name.$line_term;
	$headers.='MIME-Version: 1.0'.$line_term;
	if ((!is_null($attachments)) || (!$simplify_when_can))
	{
		$headers.='Content-Type: multipart/mixed;'."\n\t".'boundary="'.$boundary.'"';
	} else
	{
		$headers.='Content-Type: multipart/alternative;'."\n\t".'boundary="'.$boundary2.'"';
	}
	$sending_message='';
	$sending_message.='This is a multi-part message in MIME format.'.$line_term.$line_term;
	if ((!is_null($attachments)) || (!$simplify_when_can))
	{
		$sending_message.='--'.$boundary.$line_term;
		$sending_message.='Content-Type: multipart/alternative;'."\n\t".'boundary="'.$boundary2.'"'.$line_term.$line_term.$line_term;
	}

	global $CID_IMG_ATTACHMENT;
	$CID_IMG_ATTACHMENT=array();

	// Message starts (actually: it is kind of in header form also as it uses mime multi-part)
	if (!$in_html)
	{
		$_html_content=$html_content->evaluate($lang);
		$_html_content=preg_replace('#(keep|for)_session=[\d\w]*#','filtered=1',$_html_content);
		$message_html=(strpos($_html_content,'<html')!==false)?make_string_tempcode($_html_content):do_template('MAIL',array('_GUID'=>'b23069c20202aa59b7450ebf8d49cde1','CSS'=>'{CSS}','LOGOURL'=>get_logo_url(''),/*'LOGOMAP'=>get_option('logo_map'),*/'LANG'=>$lang,'TITLE'=>$subject,'CONTENT'=>$_html_content),$lang,false,NULL,'.tpl','templates',$theme);
		$css=css_tempcode(true,true,$message_html->evaluate($lang),$theme);
		$_css=$css->evaluate($lang);
		if (get_option('allow_ext_images')!='1')
		{
			$_css=preg_replace_callback('#url\(["\']?(http://[^"]*)["\']?\)#U','_mail_css_rep_callback',$_css);
		}
		$html_evaluated=$message_html->evaluate($lang);
		$html_evaluated=str_replace('{CSS}',$_css,$html_evaluated);

		// Cleanup the Comcode a bit
		$message_plain=comcode_to_clean_text($message_raw);
	} else
	{
		$html_evaluated=$message_raw;
	}


	$base64_encode=(get_value('base64_emails')==='1'); // More robust, but more likely to be spam-blocked, and some servers can scramble it.

	// Plain version
	if (!$in_html)
	{
		$sending_message.='--'.$boundary2.$line_term;
		$sending_message.='Content-Type: text/plain; charset='.((preg_match($regexp,$message_plain)==0)?do_lang('charset',NULL,NULL,NULL,$lang):'us-ascii').$line_term; // '; name="message.txt"'.	Outlook doesn't like: makes it think it's an attachment
		if ($base64_encode)
		{
			$sending_message.='Content-Transfer-Encoding: base64'.$line_term.$line_term;
			$sending_message.=chunk_split(base64_encode(unixify_line_format($message_plain)).$line_term,76,$line_term);
		} else
		{
			$sending_message.='Content-Transfer-Encoding: 8bit'.$line_term.$line_term;
			$sending_message.=wordwrap(str_replace(chr(10),$line_term,unixify_line_format($message_plain)).$line_term,998,$line_term);
		}
	}

	// HTML version
	$sending_message.='--'.$boundary2.$line_term;
	$sending_message.='Content-Type: multipart/related;'."\n\t".'type="text/html";'."\n\t".'boundary="'.$boundary3.'"'.$line_term.$line_term.$line_term;
	$sending_message.='--'.$boundary3.$line_term;
	$sending_message.='Content-Type: text/html; charset='.((preg_match($regexp,$html_evaluated)==0)?do_lang('charset',NULL,NULL,NULL,$lang):'us-ascii').$line_term; // .'; name="message.html"'.	Outlook doesn't like: makes it think it's an attachment
	if (get_option('allow_ext_images')!='1')
	{
		$html_evaluated=preg_replace_callback('#<img\s([^>]*)src="(http://[^"]*)"#U','_mail_img_rep_callback',$html_evaluated);
	}

	if ($base64_encode)
	{
		$sending_message.='Content-Transfer-Encoding: base64'.$line_term.$line_term;
		$sending_message.=chunk_split(base64_encode(unixify_line_format($html_evaluated)).$line_term,76,$line_term);
	} else
	{
		$sending_message.='Content-Transfer-Encoding: 8bit'.$line_term.$line_term; // Requires RFC 1652
		$sending_message.=wordwrap(str_replace(chr(10),$line_term,unixify_line_format($html_evaluated)).$line_term,998,$line_term);
	}
	foreach ($CID_IMG_ATTACHMENT as $id=>$img)
	{
		$sending_message.='--'.$boundary3.$line_term;
		$file_path_stub=convert_url_to_path($img);
		$mime_type=get_mime_type(get_file_extension($img));
		$filename=basename($img);
		if (!is_null($file_path_stub))
		{
			$file_contents=@file_get_contents($file_path_stub);
		} else
		{
			$file_contents=http_download_file($img,NULL,false);
			if (!is_null($GLOBALS['HTTP_DOWNLOAD_MIME_TYPE'])) $mime_type=$GLOBALS['HTTP_DOWNLOAD_MIME_TYPE'];
			if (!is_null($GLOBALS['HTTP_FILENAME'])) $filename=$GLOBALS['HTTP_FILENAME'];
		}
		$sending_message.='Content-Type: '.str_replace("\r",'',str_replace("\n",'',$mime_type)).$line_term;
		$sending_message.='Content-ID: <'.$id.'>'.$line_term;
		$sending_message.='Content-Disposition: inline; filename="'.str_replace("\r",'',str_replace("\n",'',$filename)).'"'.$line_term;
		$sending_message.='Content-Transfer-Encoding: base64'.$line_term.$line_term;
		if (is_string($file_contents))
			$sending_message.=chunk_split(base64_encode($file_contents),76,$line_term);
	}
	$sending_message.=$line_term.'--'.$boundary3.'--'.$line_term.$line_term;

	$sending_message.=$line_term.'--'.$boundary2.'--'.$line_term.$line_term;

	// Attachments
	if (!is_null($attachments))
	{
		foreach ($attachments as $path=>$filename)
		{
			$sending_message.='--'.$boundary.$line_term;
			$sending_message.='Content-Type: '.get_mime_type(get_file_extension($filename)).$line_term; // .'; name="'.str_replace("\r",'',str_replace("\n",'',$filename)).'"'   http://www.imc.org/ietf-822/old-archive2/msg02121.html
			$sending_message.='Content-Transfer-Encoding: base64'.$line_term;
			$sending_message.='Content-Disposition: attachment; filename="'.str_replace("\r",'',str_replace("\n",'',$filename)).'"'.$line_term.$line_term;

			if (strpos($path,'://')===false)
			{
				$sending_message.=chunk_split(base64_encode(file_get_contents($path)),76,$line_term);
			} else
			{
				require_code('files');
				$sending_message.=chunk_split(base64_encode(http_download_file($path)),76,$line_term);
			}
		}

		$sending_message.=$line_term.'--'.$boundary.'--'.$line_term;
	}

	// Support for SMTP sockets rather than PHP mail()
	$error=NULL;
	if (get_option('smtp_sockets_use')=='1')
	{
		$worked=false;

		$host=get_option('smtp_sockets_host');
		$port=intval(get_option('smtp_sockets_port'));

		$errno=0;
		$errstr='';
		foreach ($to_email as $i=>$to)
		{
			$socket=@fsockopen($host,$port,$errno,$errstr,30.0);
			if ($socket!==false)
			{
				$rcv=fgets($socket,1024);
				$base_url=parse_url(get_base_url());
				$domain=$base_url['host'];
				fwrite($socket,'HELO '.$domain."\r\n");
				$rcv=fgets($socket,1024);

				// Login if necessary
				$username=get_option('smtp_sockets_username');
				$password=get_option('smtp_sockets_password');
				if ($username!='')
				{
					fwrite($socket,"AUTH LOGIN\r\n");
					$rcv=fgets($socket,1024);
					if (strtolower(substr($rcv,0,3))=='334')
					{
						fwrite($socket,base64_encode($username)."\r\n");
						$rcv=fgets($socket,1024);
						if ((strtolower(substr($rcv,0,3))=='235') || (strtolower(substr($rcv,0,3))=='334'))
						{
							fwrite($socket,base64_encode($password)."\r\n");
							$rcv=fgets($socket,1024);
							if (strtolower(substr($rcv,0,3))=='235')
							{
							} else $error=do_lang('MAIL_ERROR_CONNECT_PASSWORD').' ('.str_replace($password,'*',$rcv).')';
						} else $error=do_lang('MAIL_ERROR_CONNECT_USERNAME').' ('.$rcv.')';
					} else $error=do_lang('MAIL_ERROR_CONNECT_AUTH').' ('.$rcv.')';
				}

				if (is_null($error))
				{
					$smtp_from_address=get_option('smtp_from_address');
					if ($smtp_from_address=='') $smtp_from_address=$from_email;
					fwrite($socket,'MAIL FROM:<'.$website_email.">\r\n");
					$rcv=fgets($socket,1024);
					if ((strtolower(substr($rcv,0,3))=='250') || (strtolower(substr($rcv,0,3))=='251'))
					{
						$sent_one=false;
						fwrite($socket,"RCPT TO:<".$to_email[$i].">\r\n");
						$rcv=fgets($socket,1024);
						if ((strtolower(substr($rcv,0,3))!='250') && (strtolower(substr($rcv,0,3))!='251'))
							$error=do_lang('MAIL_ERROR_TO').' ('.$rcv.')'.' '.$to_email[$i];
						else $sent_one=true;
						if ($sent_one)
						{
							fwrite($socket,"DATA\r\n");
							$rcv=fgets($socket,1024);
							if (strtolower(substr($rcv,0,3))=='354')
							{
								$attractive_date=strftime('%d %B %Y  %H:%M:%S',time());

								if (count($to_email)==1)
								{
									fwrite($socket,'To: '.(is_array($to_name)?$to_name[$i]:$to_name).' <'.$to_email[$i].'>'."\r\n");
								} else
								{
									fwrite($socket,'To: '.(is_array($to_name)?$to_name[$i]:$to_name)."\r\n");
								}
								fwrite($socket,'Subject: '.$tightened_subject."\r\n");
								fwrite($socket,'Date: '.$attractive_date."\r\n");
								fwrite($socket,$headers."\r\n");
								fwrite($socket,$sending_message);
								fwrite($socket,"\r\n.\r\n");
								$rcv=fgets($socket,1024);
								fwrite($socket,"QUIT\r\n");
								$rcv=fgets($socket,1024);
							} else $error=do_lang('MAIL_ERROR_DATA').' ('.$rcv.')';
						}
					} else $error=do_lang('MAIL_ERROR_FROM').' ('.$rcv.')';

					if (@fwrite($socket,"RSET\r\n")===false) // Cut out. At least one server does this
					{
						@fclose($socket);
						$socket=NULL;
					} else $rcv=fgets($socket,1024);
				}

				if (!is_null($socket)) fclose($socket);
				if (is_null($error)) $worked=true;
			} else
			{
				$error=do_lang('MAIL_ERROR_CONNECT',$host,strval($port));
			}
		}
	} else
	{
		$worked=false;
		foreach ($to_email as $i=>$to)
		{
			//exit($headers.chr(10).$sending_message);
			$GLOBALS['SUPRESS_ERROR_DEATH']=true;

			$additional='';
			if (get_option('enveloper_override')=='1') $additional='-f '.$website_email;
			if (ini_get('safe_mode')=='1')
			{
				$worked=mail('"'.(is_array($to_name)?$to_name[$i]:$to_name).'" <'.$to.'>',$tightened_subject,$sending_message,$headers);
			} else
			{
				$worked=mail('"'.(is_array($to_name)?$to_name[$i]:$to_name).'" <'.$to.'>',$tightened_subject,$sending_message,$headers,$additional);
			}
			$GLOBALS['SUPRESS_ERROR_DEATH']=false;
		}
	}

	if (!$worked)
	{
		$SENDING_MAIL=false;
		if (get_param_integer('keep_hide_mail_failure',0)==0)
		{
			require_code('site');
			attach_message(!is_null($error)?make_string_tempcode($error):do_lang_tempcode('MAIL_FAIL',escape_html(get_option('staff_address'))),'warn');
		} else
		{
			return warn_screen(get_page_title('ERROR_OCCURED'),do_lang_tempcode('MAIL_FAIL',escape_html(get_option('staff_address'))));
		}
	}

	$SENDING_MAIL=false;
	return NULL;
}

/**
 * Filter out any CSS selector blocks from the given CSS if they definitely do not affect the given (X)HTML.
 * Whilst this is a clever algorithm, it isn't so clever as to actually try and match each selector against a DOM tree. If any segment of a compound selector matches, match is assumed.
 *
 * @param  string			CSS
 * @param  string			(X)HTML context under which CSS is filtered
 * @return string			Filtered CSS
 */
function filter_css($css,$context)
{
	// Find out all our IDs
	$ids=array();
	$matches=array();
	$count=preg_match_all('#\sid=["\']([^"\']*)["\']#',$context,$matches);
	for ($i=0;$i<$count;$i++)
	{
		$ids[]=$matches[1][$i];
	}

	// Find out all our classes
	$classes=array();
	$count=preg_match_all('#\sclass=["\']([^"\']*)["\']#',$context,$matches);
	for ($i=0;$i<$count;$i++)
	{
		if ($matches[1][$i]=='') continue;
		$classes=array_merge($classes,preg_split('#\s+#',$matches[1][$i],-1,PREG_SPLIT_NO_EMPTY));
	}

	// Strip comments from CSS. This optimises, and also avoids us needing to do a sophisticated parse
	$css=preg_replace('#/\*.*\*/#Us','',$css);

	// Find and process each CSS selector block
	$stack=array();
	$css_new='';
	$last_pos=0;
	do
	{
		$pos1=strpos($css,'{',$last_pos);
		$pos2=strpos($css,'}',$last_pos);
		if (($pos1===false) && ($pos2===false)) break;

		if (($pos1===false) || (($pos2!==false) && ($pos2<$pos1)))
		{
			if (count($stack)!=0)
			{
				$start=array_pop($stack);
				if (count($stack)==0) // We've finished a top-level section
				{
					$real_start=strrpos(substr($css,0,$start),'}');
					$real_start=($real_start===false)?0:($real_start+1);
					$selectors=explode(',',trim(substr($css,$real_start,$start-$real_start)));
					$applies=false;
					foreach ($selectors as $selector)
					{
						$selector=trim($selector);
						
						if (strpos($selector,'@media print')!==false) break;

						// We let all tag-name selectors through if the tag exists in the document, unless they contain a class/ID specifier -- in which case we toe to the presence of that class/ID
						if ((strpos($selector,'.')===false) && (strpos($selector,'#')===false) && (preg_match('#(^|\s)(\w+)([\[\#\.:\s]|$)#',$selector,$matches)!=0))
						{
							//if (($matches[2]=='html') || ($matches[2]=='body') || ($matches[2]=='div') || ($matches[2]=='a') || (strpos($context,'<'.$matches[2])!==false))
							{
								$applies=true;
								break;
							}
						}

						// ID selectors
						foreach ($ids as $id)
						{
							if (preg_match('#\#'.str_replace('#','\#',preg_quote($id)).'([\[\.:\s]|$)#',$selector)!=0)
							{
								$applies=true;
								break;
							}
						}

						// Class name selectors
						foreach ($classes as $class)
						{
							if (preg_match('#\.'.str_replace('#','\#',preg_quote($class)).'([\[\#:\s]|$)#',$selector)!=0)
							{
								$applies=true;
								break;
							}
						}
					}
					if ($applies)
					{
						$css_new.=trim(substr($css,$real_start,$pos2-$real_start+1)).chr(10).chr(10); // Append section
					}
				}
			} else
			{
				//return $css; // Parsing error, extra close
				// But actually it's best we let it continue on
			}
			$last_pos=$pos2+1;
		} else
		{
			array_push($stack,$pos1);
			$last_pos=$pos1+1;
		}
	}
	while (true);

	return $css_new;
}

/**
 * Attempt to send an SMS.
 *
 * @param  string			The message
 * @param  array			The member IDs of those tracking this
 */
function sms_wrap($message,$to_sms)
{
	// 140 byte limit for single packet
	// 134*255 byte limit for multiple packets (but there's cost for each additional 134 byte segment)

	if (count($to_sms)==0) return;

	$is_super_admin=$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member());

	$api_id=xmlentities(get_option('sms_api_id'));
	$username=xmlentities(get_option('sms_username'));
	$password=xmlentities(get_option('sms_password'));
	$site_name=xmlentities(substr(get_site_name(),0,11));
	if ((strtolower(get_charset())!='utf-8') && (strtolower(get_charset())!='utf8')) $site_name=utf8_encode($site_name);
	//$callback=xmlentities(find_script('sms')); --- set on clickatell's site
	$callback='0'; /* return nothing (for the moment); TODO: change to 3 (return all message statuses) */

	$threshold=mktime(0,0,0,intval(date('m')),0,intval(date('Y')));

	$triggered=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.get_table_prefix().'sms_log WHERE s_time>'.strval(time()-60*60*24*31).' AND '.db_string_equal_to('s_trigger_ip',get_ip_address(2)));
	$trigger_limit=intval(get_option('sms_'.(has_specific_permission(get_member(),'sms_higher_trigger_limit')?'high':'low').'_trigger_limit'));
	if ($triggered+count($to_sms)>$trigger_limit) return;

	// TODO: $confirmed_numbers=collapse_2d_complexity('m_phone_number','m_member_id',$GLOBALS['SITE_DB']->query_select('confirmed_mobiles',array('m_phone_number','m_member_id'),array('m_confirm_code'=>'')));

	foreach ($to_sms as $to_member)
	{
		if (!has_specific_permission($to_member,'use_sms')) continue;

		// Check that not over quota
		$sent_in_month=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'sms_log WHERE s_member_id='.strval(intval($to_member)).' AND s_time>'.strval(intval($threshold)));
		$limit=intval(get_option('sms_'.(has_specific_permission($to_member,'sms_higher_limit')?'high':'low').'_limit'));
		if ($sent_in_month+1>$limit) continue;

		$_message=($sent_in_month+1==$limit)?do_lang('OVER_SMS_LIMIT'):xmlentities($message);
		if ((strtolower(get_charset())!='utf-8') && (strtolower(get_charset())!='utf8')) $_message=utf8_encode($_message);

		// Let the super-admin trigger or receive longer messages
		$is_this_super_admin=$GLOBALS['FORUM_DRIVER']->is_super_admin($to_member);
		$concat=($is_super_admin || $is_this_super_admin)?'3':'1';

		$cpf_values=$GLOBALS['FORUM_DRIVER']->get_custom_fields($to_member);
		if (!array_key_exists('mobile_phone_number',$cpf_values)) return; // :S  -- should be there
		$to=str_replace('-','',str_replace('(','',str_replace(')','',str_replace('+','',str_replace(' ','',$cpf_values['mobile_phone_number'])))));
		if ($to=='') continue;
		// TODO: if (!array_key_exists($to,$confirmed_numbers)) continue;
		$to=xmlentities($to);

		$xml=<<<END
<clickAPI>
	<sendMsg>
		<api_id>{$api_id}</api_id>
		<user>{$username}</user>
		<password>{$password}</password>
		<to>{$to}</to>
		<text>{$_message}</text>
		<from>{$site_name}</from>
		<callback>{$callback}</callback>
		<max_credits>2.5</max_credits>
		<concat>{$concat}</concat>
	</sendMsg>
</clickAPI>
END;

		$result=http_download_file('http://api.clickatell.com/xml/xml',NULL,false,false,'ocPortal',array('data'=>$xml));
		if (strpos($result,'fault')!==false) warn_exit($result);

		$GLOBALS['SITE_DB']->query_insert('sms_log',array('s_trigger_ip'=>get_ip_address(2),'s_member_id'=>$to_member,'s_time'=>time()));
	}
}

/**
 * Handle maintenance of SMS numbers (block numbers if they prove unreliable).
 */
function sms_callback_script()
{
	// Currently does nothing. Would receive messages in the form below, via the "data" GET parameter
	/*
	< ?xml version="1.0"? >
	<callback>
		<apiMsgId>996411ad91fa211e7d17bc873aa4a41d</apiMsgId>
		<cliMsgId></cliMsgId>
		<timestamp>1218008129</timestamp>
		<to>279995631564</to>
		<from>27833001171</from>
		<charge>0.300000</charge>
		<status>004</status>
	</callback>
	*/
}

/**
 * Entry script to process a form that needs to be emailed.
 */
function form_to_email_entry_script()
{
	require_lang('mail');
	form_to_email();
	
	global $PAGE_NAME_CACHE;
	$PAGE_NAME_CACHE='_form_to_email';
	$title=get_page_title('MAIL_SENT');
	$text=do_lang_tempcode('MAIL_SENT_TEXT',escape_html(post_param('to_written_name',get_site_name())));
	$redirect=get_param('redirect',NULL);
	if (!is_null($redirect))
	{
		require_code('site2');
		$GLOBALS['NON_PAGE_SCRIPT']=0;
		$tpl=redirect_screen($title,$redirect,$text);
	} else
	{
		$tpl=do_template('INFORM_SCREEN',array('_GUID'=>'e577a4df79eefd9064c14240cc99e947','TITLE'=>$title,'TEXT'=>$text));
	}
	$echo=globalise($tpl,NULL,'',true);
	$echo->evaluate_echo();
}

/**
 * Send the posted form over email to the staff address.
 *
 * @param  ?string	The subject of the email (NULL: from posted subject parameter).
 * @param  string		The intro text to the mail.
 * @param  ?array		A map of fields to field titles to transmit. (NULL: all posted fields, except subject and email)
 */
function form_to_email($subject=NULL,$intro='',$fields=NULL)
{
	if (is_null($subject)) $subject=post_param('subject',get_site_name());
	if (is_null($fields))
	{
		$fields=array();
		foreach (array_diff(array_keys($_POST),array('x','y','name','subject','email','to_members_email','to_written_name','redirect','http_referer')) as $key)
		{
			if (substr($key,0,1)!='_')
				$fields[$key]=ucwords(str_replace('_',' ',$key));
		}
	}

	$message_raw=$intro;
	if ($message_raw!='') $message_raw.="\n\n------------\n\n";
	foreach ($fields as $field=>$field_title)
	{
		$field_val=post_param($field,NULL);
		if (!is_null($field_val))
			$message_raw.=$field_title.': '.$field_val."\n\n";
	}
	$from_email=trim(post_param('email',''));

	$from_name=post_param('name','');
	$to=post_param_integer('to_members_email',NULL);
	$to_email=NULL;
	$to_name='';
	if (!is_null($to))
	{
		$to_email=array($GLOBALS['FORUM_DRIVER']->get_member_email_address($to));
		$to_name=$GLOBALS['FORUM_DRIVER']->get_username($to);
	}

	$attachments=array();
	foreach ($_FILES as $file)
	{
		if (is_uploaded_file($file['tmp_name']))
		{
			$attachments[$file['tmp_name']]=$file['name'];
		}
	}
	
	if (addon_installed('captcha'))
	{
		if (post_param_integer('_security',0)==1)
		{
			require_code('captcha');
			enforce_captcha();
		}
	}

	mail_wrap($subject,$message_raw,$to_email,$to_name,$from_email,$from_name,3,$attachments);
}

