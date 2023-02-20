<?php
//IMathAS: E-mail handlers
//(c) 2018 David Lippman

/* 
To use a custom email handler, define in config.php
    $CFG['email']['handler'] = array('filename.php', 'functionname');
where filename.php is a file in /includes/ that contains the email handling
function with the given functionname

That function must take the same arguments as send_email below, except priority
*/

/*
email:    email address or array of email address to send to
from:     a single email to send from
subject:  the subject of the email
message:  the body of the email
replyto:  (optional) email address or array of email addresses to use as the replyto
bccList:  (optional) array of email addresses to bcc the message to
priority: (optional) priority of the email from 1 (low) to 10 (high)
		  can be used with $CFG['email']['handlerpriority'] to set a breakpoint
		  above which the handler will be used.
		  New message notifications: priority 1
		  Password resets / new acct notices: priority 10
		  Emails sent as user: 5
		  Quickdrill results: 8
		  
*/

function send_email($email, $from, $subject, $message, $replyto=array(), $bccList=array(), $priority=10) {
	global $CFG;
	if (!is_array($email)) {
		$email = array($email);
	}
	if (!is_array($replyto)) {
		if ($replyto=='') {
			$replyto = array();
		} else {
			$replyto = array($replyto);
		}
	}
	foreach ($email as $k=>$v) {
		$email[$k] = Sanitize::fullEmailAddress(trim($v));
		if ($email[$k] == '' || $email[$k] == 'none@none.com' || strpos($email[$k], 'BOUNCED')!==false) {
			unset($email[$k]);
		}
	}
	if (count($email)==0) { //if no valid To addresses, bail
		return;
	}
	foreach ($replyto as $k=>$v) {
		$replyto[$k] = Sanitize::fullEmailAddress(trim($v));
		if ($replyto[$k] == '' || $replyto[$k] == 'none@none.com' || strpos($replyto[$k], 'BOUNCED')!==false) {
			unset($replyto[$k]);
		}
	}
	foreach ($bccList as $k=>$v) {
		$bccList[$k] = Sanitize::fullEmailAddress(trim($v));
		if ($bccList[$k] == '' || $bccList[$k] == 'none@none.com' || strpos($bccList[$k], 'BOUNCED')!==false) {
			unset($bccList[$k]);
		}
	}
	$subject = Sanitize::simpleASCII($subject);

	if (!isset($CFG['email']['handlerpriority'])) {
		$CFG['email']['handlerpriority'] = 0;
	}
	if (isset($CFG['email']['handler']) && $priority>$CFG['email']['handlerpriority']) {
		list($handlerscript, $sendfunc) = $CFG['email']['handler'];
		require_once(__DIR__ . '/' . $handlerscript);
		$sendfunc($email, $from, $subject, $message, $replyto, $bccList);
	} else if (!empty($CFG['GEN']['useSESmail']) && $priority>$CFG['email']['handlerpriority']) {
		require_once(__DIR__ . '/mailses.php');
		send_SESemail($email, $from, $subject, $message, $replyto, $bccList);
	} else {
		$tostr = implode(',', $email);
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= "From: $from\r\n";
		if (count($replyto)>0) {
			$headers .= "Reply-To: ".implode(',', $replyto)."\r\n";
		}
		if (count($bccList)>0) {
			$headers .= "Bcc: ".implode(',', $bccList)."\r\n";
		}
		mail($tostr, $subject, $message, $headers);
	}	
}

function send_msg_notification($emailto, $from, $subject, $courseid, $coursename, $msgid) {
	global $sendfrom;
	
	$linkurl = $GLOBALS['basesiteurl'] . "/msgs/viewmsg.php?cid=".Sanitize::courseId($courseid)."&msgid=".Sanitize::onlyInt($msgid);
		
	$message  = '<h3>'._('This is an automated message.  Do not respond to this email.')."</h3>\r\n";
	$message .= '<p>'._('You\'ve received a new message').'</p>';
	$message .= '<p>'._('From:').' '.Sanitize::encodeStringForDisplay($from).'<br />';
	$message .= _('Course:').' '.Sanitize::encodeStringForDisplay($coursename)."</p>\r\n";
	$message .= '<p>'._('Subject:') . ' ' . Sanitize::encodeStringForDisplay($subject)."</p>\r\n";
	$message .= '<p><a href="' . $linkurl . '">' . _('View Message') . "</a></p>\r\n";
	$message .= '<p>'.sprintf(_('If you do not wish to receive email notification of new messages, please <%s>click here to change your user preferences'), 'a href="' . $GLOBALS['basesiteurl'] . '/forms.php?action=chguserinfo"');
	$message .= "</a></p>\r\n";
	
	send_email($emailto, $sendfrom, _('New message notification'), $message, array(), array(), 1);			
}
