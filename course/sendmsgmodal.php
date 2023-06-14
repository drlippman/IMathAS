<?php
//IMathAS:  A greybox modal for sending a single message or email
//(c) 2014 David Lippman for Lumen Learning

require("../init.php");

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($instrPreviewId)) {
	 require("../header.php");
	 echo sprintf(_("You are not enrolled in this course.  Please return to the %s Home Page%s and enroll"),"<a href=\"../index.php\">","</a>")."\n";
	 require("../footer.php");
	 exit;
}

$flexwidth = true;
$nologo = true;

if (isset($_POST['message'])) {
	require_once("../includes/email.php");

	$origmessage = Sanitize::incomingHtml($_POST['message']);
	$subject = Sanitize::stripHtmlTags($_POST['subject']);
	if (trim($subject)=='') {
		$subject = '('._('none').')';
	}
	if ($myrights>10 && isset($_POST['markbroken'])) {
		$subject .= ' - '._('Marked Broken');
	}
	$sendlist = array(array('to'=>$_POST['sendto'], 'sendtype'=>$_POST['sendtype']));

	//if it's an error report, and we've said we want a copy elsewhere, add that to the send list
	if (isset($_POST['iserrreport']) && isset($CFG['GEN']['qerrorsendto']) && !empty($CFG['GEN']['qerrorsendto'][3])) {
		$sendlist[] = array('to'=>$CFG['GEN']['qerrorsendto'][0], 'sendtype'=>$CFG['GEN']['qerrorsendto'][1]);
	}
	$error = '';
	foreach ($sendlist as $sendcnt=>$sendinfo) {
		$msgto = Sanitize::onlyInt($sendinfo['to']);

		if (isset($_POST['iserrreport']) && $sendcnt>0) { //copy going to specified
			$message = '<p><b>'._('This message was also sent to the question owner.').'</b></p>'.$origmessage;
		} else {
			$message = $origmessage;
		}
		if ($sendinfo['sendtype']=='msg') {
			$now = time();
			$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,courseid) VALUES ";
			$query .= "(:title, :message, :msgto, :msgfrom, :senddate, :courseid)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':title'=>$subject, ':message'=>$message, ':msgto'=>$msgto, ':msgfrom'=>$userid,
				':senddate'=>$now, ':courseid'=>$cid));
			$msgid = $DBH->lastInsertId();

			$stm = $DBH->prepare("SELECT msgnotify,email,FCMtoken FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$msgto));
			list($msgnotify, $email, $FCMtokenTo) = $stm->fetch(PDO::FETCH_NUM);
			if ($msgnotify==1) {
				send_msg_notification(Sanitize::emailAddress($email), $userfullname, $subject, $cid, $coursename, $msgid);
			}
			if ($FCMtokenTo != '') {
				require_once("../includes/FCM.php");
				$url = $GLOBALS['basesiteurl'] . "/msgs/viewmsg.php?cid=".Sanitize::courseId($cid)."&msgid=$msgid";
				sendFCM($FCMtokenTo,_("Msg from:").' '.Sanitize::encodeStringForDisplay($userfullname),
						Sanitize::encodeStringForDisplay($subject), $url);
			}
			if ($sendcnt == 0) {
				$success = _('Message sent');
			}
		} else if ($sendinfo['sendtype']=='email') {
			$stm = $DBH->prepare("SELECT FirstName,LastName,email FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$msgto));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$row[2] = trim($row[2]);
			if ($row[2]!='' && $row[2]!='none@none.com') {
				$addy = Sanitize::simpleASCII("{$row[0]} {$row[1]}")." <".Sanitize::emailAddress($row[2]).">";

				$origmathdisp = $_SESSION['mathdisp'];
				$origgraphdisp = $_SESSION['graphdisp'];
				$_SESSION['mathdisp']=2;
				$_SESSION['graphdisp']=2;
				require("../filter/filter.php");
				$message = filter($message);
				$message = preg_replace('/<img([^>])*src="\//','<img $1 src="' . $GLOBALS['basesiteurl'] . '/',$message);
				$stm = $DBH->prepare("SELECT FirstName,LastName,email FROM imas_users WHERE id=:id");
				$stm->execute(array(':id'=>$userid));
				$row = $stm->fetch(PDO::FETCH_NUM);
				$self = Sanitize::simpleASCII("{$row[0]} {$row[1]}") ." <". Sanitize::emailAddress($row[2]).">";

				send_email($addy, $sendfrom, $subject, $message, array($self), array(), 5);

				$_SESSION['mathdisp'] = $origmathdisp;
				$_SESSION['graphdisp'] = $origgraphdisp;

				if ($sendcnt == 0) {
					$success = _('Email sent');
				}
			} else if ($sendcnt == 0) {
				$error = _('Unable to send: Invalid email address');
			}
		}
	}
	if ($error == '' && $myrights>10 && isset($_POST['markbroken'])) {
		$stm = $DBH->prepare("UPDATE imas_questionset SET broken=1 WHERE id=?");
		$stm->execute(array(Sanitize::onlyInt($_POST['markbroken'])));
		$success .= '<script>$(function(){window.parent.$("#brokenmsgbad").show();});</script>';
	}
	require("../header.php");
	if ($error=='') {
		echo $success;
	} else {
		echo $error;
	}
	echo '. <input type="button" onclick="top.GB_hide()" value="'._('Done').'" />';
	require("../footer.php");
	exit;
} else {
	$useeditor = "message";
    $placeinhead = '<script>function sendmsg() { 
        $("form").submit(); 
        parent.$("#GB_footer button.primary").hide();
    }</script>';
	require("../header.php");

	$iserrreport = false;

	if (isset($_GET['quoteq'])) {
		$quoteq = Sanitize::stripHtmlTags($_GET['quoteq']);
		$parts = explode('-',$quoteq);
        $GLOBALS['assessver'] = $parts[4];
        if ($courseUIver > 1) {
            include('../assess2/AssessStandalone.php');
            $a2 = new AssessStandalone($DBH);
            $state = array(
                'seeds' => array($parts[0] => $parts[2]),
                'qsid' => array($parts[0] => $parts[1])
            );
            $a2->setState($state);
            $a2->loadQuestionData();
            $res = $a2->displayQuestion($parts[0], ['showhints'=>false]);
            $message = $res['html'];
            $message = preg_replace('/<div class="question"[^>]*>/','<div>', $message);
        } else {
            require("../assessment/displayq2.php");
            $message = displayq($parts[0],$parts[1],$parts[2],false,false,0,true);
        }
		$message = printfilter(forcefiltergraph($message));
		if (isset($CFG['GEN']['AWSforcoursefiles']) && $CFG['GEN']['AWSforcoursefiles'] == true) {
			require_once("../includes/filehandler.php");
			$message = preg_replace_callback('|'.$imasroot.'/filter/graph/imgs/([^\.]*?\.png)|', function ($matches) {
				$curdir = rtrim(dirname(__FILE__), '/\\');
				return relocatefileifneeded($curdir.'/../filter/graph/imgs/'.$matches[1], 'gimgs/'.$matches[1]);
				}, $message);
		}
		$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);

		$qinfo = 'Question ID '.Sanitize::onlyInt($parts[1]).', seed '.Sanitize::onlyInt($parts[2]);
		$message = '<p> </p><br/><hr/>'.$qinfo.'<br/><br/>'.$message;
		$courseid = $cid;
		if (isset($parts[3]) && $parts[3] === 'reperr') {
			$title = _("Problem with question ID ").Sanitize::onlyInt($parts[1]);
			$iserrreport = true;
			$_GET['to'] = 0;
			if (isset($CFG['GEN']['qerrorsendto'])) {
				if (is_array($CFG['GEN']['qerrorsendto'])) {
					if (empty($CFG['GEN']['qerrorsendto'][3])) { //if not also sending to owner
						$_GET['to'] = $CFG['GEN']['qerrorsendto'][0];
						$sendtype = $CFG['GEN']['qerrorsendto'][1];
					}
				} else {
					$_GET['to'] = $CFG['GEN']['qerrorsendto'];
				}
			}
			if ($_GET['to'] == 0) {
                $query = 'SELECT iqs.ownerid,iu.lastaccess FROM imas_questionset AS iqs
                    JOIN imas_users AS iu ON iqs.ownerid=iu.id WHERE iqs.id=:id';
				$stm = $DBH->prepare($query);
				$stm->execute(array(':id'=>$parts[1]));
                $r = $stm->fetch(PDO::FETCH_ASSOC);
                $_GET['to'] = $r['ownerid'];
                if (!empty($CFG['GEN']['qerroronold']) && $r['lastaccess'] < time() - 60*60*24*$CFG['GEN']['qerroronold'][0]) {
                    $_GET['to'] = $CFG['GEN']['qerroronold'][1];
                }
			}
		} else if (isset($parts[3])) {  //sending to instructor
			$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>Sanitize::onlyInt($parts[3])));
			$title = 'Question about #'.($parts[0]+1).' in '.str_replace('"','&quot;',$stm->fetchColumn(0));
			if ($_GET['to']=='instr') {
				unset($_GET['to']);
				$msgset = 1; //force instructor only list
			}
		} else {
			$title = '';
		}
	} else if (isset($_GET['title'])) {
		$title = $_GET['title'];
		$message = '';
		$courseid=$cid;
	} else {
		$title = '';
		$message = '';
		$courseid=$cid;
	}

	$msgto = Sanitize::onlyInt($_GET['to']);
	$stm = $DBH->prepare("SELECT FirstName,LastName,email FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>$msgto));
	list($firstname, $lastname, $email) = $stm->fetch(PDO::FETCH_NUM);

	if ($_GET['sendtype']=='msg') {
		echo '<h1>'._('New Message').'</h1>';
		$to = Sanitize::stripHtmlTags("$lastname, $firstname");
	} else if ($_GET['sendtype']=='email') {
		echo '<h1>'._('New Email').'</h1>';
		$to = Sanitize::stripHtmlTags("$lastname, $firstname ($email)");
	}

	echo '<form method="post" action="sendmsgmodal.php?cid='.$cid.'">';
	echo '<input type="hidden" name="sendto" value="'.$msgto.'"/>';
	echo '<input type="hidden" name="sendtype" value="'.Sanitize::encodeStringForDisplay($_GET['sendtype']).'"/>';
	echo _("To:")." <span class='pii-mixed'>$to</span><br/>\n";
	echo _("Subject:")." <input type=text size=50 name=subject id=subject value=\"".Sanitize::encodeStringForDisplay($title)."\"><br/>\n";
	echo _("Message:")." <div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>";
	echo htmlentities($message);
	echo "</textarea></div><br/>\n";
	if ($iserrreport) {
		echo '<input type=hidden name=iserrreport value=1 />';
		echo '<label><input type=checkbox name=markbroken value="'.Sanitize::onlyInt($parts[1]).'"> ';
		echo _("Mark question as broken. Only do this if there is a serious issue in the display or scoring of the question.").' ';
		echo _("If you are reporting a typo, suggestion for a change, or an issue that only rarely occurs, please leave this un-checked.");
		echo '</label><br/>';
	}
	if ($_GET['sendtype']=='msg') {
		echo '<div class="submit"><input type="submit" value="'._('Send Message').'"></div>';
	} else if ($_GET['sendtype']=='email') {
		echo '<div class="submit"><input type="submit" value="'._('Send Email').'"></div>';
	}
	echo '</form>';
	require("../footer.php");
	exit;
}
