<?php
//IMathAS:  A greybox modal for sending a single message or email
//(c) 2014 David Lippman for Lumen Learning

require("../init.php");

$flexwidth = true;
$nologo = true;

if (isset($_POST['message'])) {
	require_once("../includes/email.php");
	
	$message = Sanitize::incomingHtml($_POST['message']);
	$subject = Sanitize::stripHtmlTags($_POST['subject']);
	if (trim($subject)=='') {
		$subject = '('._('none').')';
	}
	$msgto = Sanitize::onlyInt($_POST['sendto']);
	$error = '';
	if ($_POST['sendtype']=='msg') {
		$now = time();
		$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
		$query .= "(:title, :message, :msgto, :msgfrom, :senddate, :isread, :courseid)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':title'=>$subject, ':message'=>$message, ':msgto'=>$msgto, ':msgfrom'=>$userid,
			':senddate'=>$now, ':isread'=>0, ':courseid'=>$cid));
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
		
		$success = _('Message sent');
	} else if ($_POST['sendtype']=='email') {
		$stm = $DBH->prepare("SELECT FirstName,LastName,email FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$msgto));
		$row = $stm->fetch(PDO::FETCH_NUM);
		$row[2] = trim($row[2]);
		if ($row[2]!='' && $row[2]!='none@none.com') {
			$addy = Sanitize::simpleASCII("{$row[0]} {$row[1]}")." <".Sanitize::emailAddress($row[2]).">";
			$sessiondata['mathdisp']=2;
			$sessiondata['graphdisp']=2;
			require("../filter/filter.php");
			$message = filter($message);
			$message = preg_replace('/<img([^>])*src="\//','<img $1 src="' . $GLOBALS['basesiteurl'] . '/',$message);
			$stm = $DBH->prepare("SELECT FirstName,LastName,email FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$userid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$self = Sanitize::simpleASCII("{$row[0]} {$row[1]}") ." <". Sanitize::emailAddress($row[2]).">";
			
			send_email($addy, $sendfrom, $subject, $message, array($self), array(), 5); 
			
			$success = _('Email sent');
		} else {
			$error = _('Unable to send: Invalid email address');
		}
	}
	require("../header.php");
	if ($error=='') {
		echo $success;
	} else {
		echo $error;
	}
	echo '. <input type="button" onclick="top.GB_hide()" value="Done" />';
	require("../footer.php");
	exit;
} else {
	$msgto = Sanitize::onlyInt($_GET['to']);
	$stm = $DBH->prepare("SELECT FirstName,LastName,email FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>$msgto));
	list($firstname, $lastname, $email) = $stm->fetch(PDO::FETCH_NUM);
	$useeditor = "message";
	require("../header.php");
	if ($_GET['sendtype']=='msg') {
		echo '<h1>New Message</h1>';
		$to = Sanitize::stripHtmlTags("$lastname, $firstname");
	} else if ($_GET['sendtype']=='email') {
		echo '<h1>New Email</h1>';
		$to = Sanitize::stripHtmlTags("$lastname, $firstname ($email)");
	}

	if (isset($_GET['quoteq'])) {
		$quoteq = Sanitize::stripHtmlTags($_GET['quoteq']);
		require("../assessment/displayq2.php");
		$parts = explode('-',$quoteq);
		$GLOBALS['assessver'] = $parts[4];
		$message = displayq($parts[0],$parts[1],$parts[2],false,false,0,true);
		$message = printfilter(forcefiltergraph($message));
		$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);

		$message = '<p> </p><br/><hr/>'.$message;
		$courseid = $cid;
		if (isset($parts[3])) {  //sending to instructor
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


	echo '<form method="post" action="sendmsgmodal.php?cid='.$cid.'">';
	echo '<input type="hidden" name="sendto" value="'.$msgto.'"/>';
	echo '<input type="hidden" name="sendtype" value="'.Sanitize::encodeStringForDisplay($_GET['sendtype']).'"/>';
	echo "To: $to<br/>\n";
	echo "Subject: <input type=text size=50 name=subject id=subject value=\"".Sanitize::encodeStringForDisplay($title)."\"><br/>\n";
	echo "Message: <div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>";
	echo htmlentities($message);
	echo "</textarea></div><br/>\n";
	if ($_GET['sendtype']=='msg') {
		echo '<div class="submit"><input type="submit" value="'._('Send Message').'"></div>';
	} else if ($_GET['sendtype']=='email') {
		echo '<div class="submit"><input type="submit" value="'._('Send Email').'"></div>';
	}
	echo '</form>';
	require("../footer.php");
	exit;
}
