<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: imap\_.+|proc\_.+|stream_set_blocking|stream_get_contents|stream_set_timeout*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * Get an IMAP/POP3 connection string.
 *
 * @param  string                       The server hostname.
 * @param  integer                      The port.
 * @param  string                       The protocol (NULL: autodetect).
 * @set pop3 pop3s imap imaps
 * @return string                       Connection string.
 */
function _imap_server_spec($server, $port, $type = null)
{
    if ($type === 'pop3' || $type === 'pop3s') {
        $is_pop3 = true;
    }
    elseif ($type === 'imap' || $type === 'imaps') {
        $is_pop3 = false;
    } else {
        $is_pop3 = (strpos($server, 'pop') !== false || $port == 110 || $port == 995);
    }

    if ($is_pop3) {
        if (is_null($type)) {
            $ssl = ($port == 995);
        } else {
            $ssl = ($type == 'pop3s');
        }
        $server_special_details = $ssl ? '/pop3/ssl/novalidate-cert' : '/pop3/novalidate-cert';
        $server_spec = '{' . $server . ':' . strval($port) . '' . $server_special_details . '}';
    } else {
        if (is_null($type)) {
            $ssl = ($port == 993);
        } else {
            $ssl = ($type == 'imaps');
        }
        $server_special_details = $ssl ? '/ssl/novalidate-cert' : '/novalidate-cert';
        $server_spec = '{' . $server . ':' . strval($port) . '/imap/readonly' . $server_special_details . '}';
    }
    return $server_spec;
}

/**
 * Find IMAP folders.
 *
 * @param  string                       The IMAP server hostname.
 * @param  integer                      The IMAP port.
 * @param  string                       The IMAP username.
 * @param  string                       The IMAP password.
 * @return array                        Map of folders (codenames to display labels).
 */
function find_mail_folders($server, $port, $username, $password)
{
    if (!function_exists('imap_open')) {
        warn_exit(do_lang_tempcode('IMAP_NEEDED'));
    }

    $server_spec = _imap_server_spec($server, $port);
    $mbox = @imap_open($server_spec . 'INBOX', $username, $password);
    if ($mbox === false) {
        warn_exit(do_lang_tempcode('IMAP_ERROR', imap_last_error()));
    }
    $_folders = imap_list($mbox, $server_spec, '*');

    $folders = array();
    foreach ($_folders as $folder) {
        $folder = preg_replace('#\{[^{}]+\}#', '', $folder);
        $label = preg_replace('#@.*$#', '', $folder);
        $folders[$folder] = $label;
    }

    return $folders;
}

/**
 * Find if a member can be e-mailed.
 *
 * @param  MEMBER                       The member ID.
 * @param  ?string                      The IMAP server hostname (NULL: use configured).
 * @param  ?integer                     The IMAP port (NULL: use configured).
 * @param  ?string                      The IMAP inbox identifier (NULL: use configured).
 * @param  ?string                      The IMAP username (NULL: use configured).
 * @param  ?string                      The IMAP password (NULL: use configured).
 * @param  ?TIME                        Last bounce time (NULL: not bounced).
 */
function can_email_member($member_id, $server = null, $port = null, $folder = null, $username = null, $password = null)
{
    $email = $GLOBALS['FORUM_DRIVER']->get_member_email_address($member_id);
    if ($email == '') {
        return false;
    }

    if (is_mail_bounced($email, $server, $port, $folder, $username, $password)) {
        return false;
    }

    return true;
}

/**
 * Find if an e-mail address is bounced.
 *
 * @param  EMAIL                        The email address.
 * @param  ?string                      The IMAP server hostname (NULL: use configured).
 * @param  ?integer                     The IMAP port (NULL: use configured).
 * @param  ?string                      The IMAP inbox identifier (NULL: use configured).
 * @param  ?string                      The IMAP username (NULL: use configured).
 * @param  ?string                      The IMAP password (NULL: use configured).
 * @param  ?TIME                        Last bounce time (NULL: not bounced).
 */
function is_mail_bounced($email, $server = null, $port = null, $folder = null, $username = null, $password = null)
{
    if ($email == '') {
        return null;
    }

    if (is_null($server)) {
        $server = get_option('imap_server');
        $port = intval(get_option('imap_port'));
        $folder = get_option('imap_folder');
        $username = get_option('imap_username');
        $password = get_option('imap_password');
    }

    if ($password == '' || !function_exists('imap_open')) {
        return false; // Not configured, so cannot proceed
    }

    $update_since = $GLOBALS['SITE_DB']->query_select_value_if_there('email_bounces', 'MAX(b_time)');
    update_bounce_storage($server, $port, $folder, $username, $password, $update_since);

    return $GLOBALS['SITE_DB']->query_select_value_if_there('email_bounces', 'MAX(b_time)', array('b_email_address' => $email));
}

/**
 * Update the details in our bounce storage table, by looking at received bounces.
 *
 * @param  string                       The IMAP server hostname.
 * @param  integer                      The IMAP port.
 * @param  string                       The IMAP inbox identifier.
 * @param  string                       The IMAP username.
 * @param  string                       The IMAP password.
 * @param  ?TIME                        Only find bounces since this date (NULL: 8 weeks ago). This is approximate, we will actually look from a bit further back to compensate for possible timezone differences.
 */
function update_bounce_storage($server, $port, $folder, $username, $password, $since = null)
{
    if (is_null($since)) {
        $since = time() - 60 * 60 * 24 * 7 * 8;
    }

    $bounces = _find_mail_bounces($server, $port, $folder, $username, $password, true, $since);
    foreach ($bounces as $email => $_details) {
        list($subject, $is_bounce, $time, $body) = $_details;

        $GLOBALS['SITE_DB']->query_delete('email_bounces', array(
            'b_email_address' => $email,
            'b_time' => $time,
            'b_subject' => $subject,
            'b_body' => $body,
        ), '', 1);
        $GLOBALS['SITE_DB']->query_insert('email_bounces', array(
            'b_email_address' => $email,
            'b_time' => $time,
            'b_subject' => $subject,
            'b_body' => $body,
        ));
    }
}

/**
 * Find bounces in an IMAP folder, with DB caching.
 *
 * @param  string                       The IMAP server hostname.
 * @param  integer                      The IMAP port.
 * @param  string                       The IMAP inbox identifier.
 * @param  string                       The IMAP username.
 * @param  string                       The IMAP password.
 * @param  ?TIME                        Only find bounces since this date (NULL: 8 weeks ago). This is approximate, we will actually look from a bit further back to compensate for possible timezone differences.
 * @return array                        Bounces (a map between email address and details of the bounce).
 */
function find_mail_bounces($server, $port, $folder, $username, $password, $since = null)
{
    if (is_null($since)) {
        $since = time() - 60 * 60 * 24 * 7 * 8;
    }
    $update_since = $GLOBALS['SITE_DB']->query_select_value_if_there('email_bounces', 'MAX(b_time)');
    if (is_null($update_since)) {
        $update_since = 0;
    }
    $_since = max($since, $update_since);
    if ($_since == 0) {
        $_since = null;
    }

    update_bounce_storage($server, $port, $folder, $username, $password, $_since);

    $_ret = $GLOBALS['SITE_DB']->query_select('email_bounces', array('b_email_address', 'b_subject', 'b_time', 'b_body'), null, 'ORDER BY b_time');
    $ret = array();
    foreach ($_ret as $r) {
        $ret[$r['b_email_address']] = array($r['b_subject'], true, $r['b_time'], $r['b_body']);
    }
    return $ret;
}

/**
 * Find bounces in an IMAP folder.
 *
 * @param  string                       The IMAP server hostname.
 * @param  integer                      The IMAP port.
 * @param  string                       The IMAP inbox identifier.
 * @param  string                       The IMAP username.
 * @param  string                       The IMAP password.
 * @param  ?TIME                        Only find bounces since this date (NULL: no limit). This is approximate, we will actually look from a bit further back to compensate for possible timezone differences.
 * @return array                        Bounces (a map between email address and details of the bounce).
 */
function _find_mail_bounces($server, $port, $folder, $username, $password, $bounces_only = true, $since = null)
{
    if (!function_exists('imap_open')) {
        warn_exit(do_lang_tempcode('IMAP_NEEDED'));
    }

    require_code('type_validation');

    disable_php_memory_limit(); // In case of a huge number

    $server_spec = _imap_server_spec($server, $port);
    $mbox = @imap_open($server_spec . $folder, $username, $password);
    if ($mbox === false) {
        warn_exit(do_lang_tempcode('IMAP_ERROR', imap_last_error()));
    }

    $out = array();

    $filter = 'UNDELETED';
    if (!is_null($since)) {
        $filter .= ' SINCE "' . gmdate('j-M-Y', $since - 60 * 60 * 24) . '"';
    }
    $messages = imap_search($mbox, $filter);
    if ($messages === false) {
        $messages = array();
    }
    sort($messages); // Date order, approximately
    $num = 0;
    foreach ($messages as $val) {
        $body = imap_body($mbox, $val);
        $header = imap_fetchheader($mbox, $val);

        $is_bounce = 
            // Proper failure header
            (strpos($header, 'X-Failed-Recipients') !== false)

            // Failure message coming from our end
            || (strpos($body, 'Delivery to the following recipient failed permanently') !== false)

            // SMTP error codes (http://www.greenend.org.uk/rjk/tech/smtpreplies.html)
            || (preg_match('#421 .* Service not available#', $body) != 0)
            || (strpos($body, '450 Requested mail action not taken') !== false)
            || (strpos($body, '451 Requested action aborted') !== false)
            || (strpos($body, '452 Requested action not taken') !== false)
            || (preg_match('#521 .* does not accept mail#', $body) != 0)
            || (strpos($body, '530 Access denied') !== false)
            || (strpos($body, '550 Requested action not taken') !== false)
            || (strpos($body, '551 User not local') !== false)
            || (strpos($body, '552 Requested mail action aborted') !== false)
            || (strpos($body, '553 Requested action not taken') !== false)
            || (strpos($body, '554 Transaction failed') !== false)

            // Enhanced Mail System Status Codes (http://tools.ietf.org/html/rfc3463 / http://www.iana.org/assignments/smtp-enhanced-status-codes/smtp-enhanced-status-codes.xhtml)
            || (preg_match('#\s(4|5)\.\d+\.\d+\s#', $body) != 0);


        if ($is_bounce || !$bounces_only) {
            if (strpos($header, 'X-Failed-Recipients') !== false) { // Best way
                $overview = imap_headerinfo($mbox, $val);

                $matches2 = array();
                preg_match('#X-Failed-Recipients:\s*([^\"\n<>@]+@[^\n<>@]+)#', $header, $matches2);
                $email = str_replace('@localhost.localdomain', '', $matches2[1]);
                if (($email != get_option('staff_address')) && ($email != get_option('website_email')) && (is_valid_email_address($email)) && ((!isset($out[$email])) || (!$out[$email][1]))) {
                    $out[$email] = array($overview->subject, $is_bounce, strtotime($overview->date), $body);
                }
            } else {
                $overview = imap_headerinfo($mbox, $val);

                $matches = array();

                // Find e-mail addresses in body
                // (message/content IDs look similar, avoid those, also avoid routine headers)
                $_body = preg_replace('#"[^"]*" #', '', $body); // Strip out quoted name before e-mail address, to put email address right after header so that our backreference assertions work
                $_body = preg_replace('#: .* <([^"\n<>@]+@[^\n<>@]+)>#', ': <$1>', $_body); // Also strip unquoted names
                $num_matches = preg_match_all('#(?<!(Message-ID): )(?<!(Content-ID): )(?<!(Return-Path): )(?<!(From): )(?<!(Reply-To): )(?<!(X-Sender): )(?<!(X-Google-Original-From): )<([^"\n<>@]+@[^\n<>@]+)>#i', $_body, $matches);

                for ($i = 0; $i < $num_matches; $i++) {
                    $email = str_replace('@localhost.localdomain', '', $matches[8][$i]);

                    if (($email != get_option('staff_address')) && ($email != get_option('website_email')) && (is_valid_email_address($email)) && ((!isset($out[$email])) || (!$out[$email][1]))) {
                        $out[$email] = array($overview->subject, $is_bounce, strtotime($overview->date), $body);
                    }
                }
            }
        }
    }
    imap_close($mbox);

    return $out;
}

/**
 * Send an e-mail.
 *
 * @param  string                       The TO address.
 * @param  string                       The subject.
 * @param  string                       The message.
 * @param  string                       Additional headers.
 * @param  string                       Additional stuff to send to sendmail executable (if appropriate, only works when safe mode is off).
 * @return boolean                      Success status.
 */
function manualproc_mail($to, $subject, $message, $additional_headers, $additional_flags = '')
{
    $descriptorspec = array(
        0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
        1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
        2 => array('pipe', 'w') // stderr is a file to write to
    );
    $pipes = array();
    if (substr($additional_flags, 0, 1) != ' ') {
        $additional_flags = ' ' . $additional_flags;
    }
    //$additional_flags.=' -v';     mini_sendmail puts everything onto stderr if using this https://github.com/mattrude/mini_sendmail/blob/master/mini_sendmail.c
    $command = ini_get('sendmail_path') . $additional_flags;
    $handle = proc_open($command, $descriptorspec, $pipes);
    if ($handle !== false) {
        fprintf($pipes[0], "To: %s\n", $to);
        fprintf($pipes[0], "Subject: %s\n", $subject);
        fprintf($pipes[0], "%s\n", $additional_headers);
        fprintf($pipes[0], "\n%s\n", $message);
        fclose($pipes[0]);

        $test = proc_get_status($handle);

        $retmsg = '';
        $stdout = stream_get_contents($pipes[1]);
        $retmsg .= $stdout;
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        $retmsg .= $stderr;
        fclose($pipes[2]);

        if (!$test['running']) {
            $retcode = $test['exitcode'];
        } else {
            $retcode = proc_close($handle);
        }
        if (($retcode == -1) && ($stderr == '')) {
            $retcode = 0;
        } // https://bugs.php.net/bug.php?id=29123

        if ($retcode != 0) {
            trigger_error('Sendmail error code: ' . strval($retcode) . ' [' . $retmsg . ']', E_USER_WARNING);
            return false;
        }
    } else {
        trigger_error('Could not connect to sendmail process', E_USER_WARNING);
        return false;
    }
    return true;
}
