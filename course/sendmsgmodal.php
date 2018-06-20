<?php
//IMathAS:  A greybox modal for sending a single message or email
//(c) 2014 David Lippman for Lumen Learning

require("../init.php");

$flexwidth = true;
$nologo = true;

if (isset($_POST['message'])) {
	
	$message = Sanitize::incomingHtml($_POST['message']);
	$subject = Sanitize::stripHtmlTags($_POST['subject']);
	$msgto = Sanitize::onlyInt($_POST['sendto']);
	$error = '';
	if ($_POST['sendtype']=='msg') {
		$now = time();
		//DB $query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
		//DB $query .= "('{$_POST['subject']}','{$_POST['message']}','$msgto','$userid',$now,0,'$cid')";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
		$query .= "(:title, :message, :msgto, :msgfrom, :senddate, :isread, :courseid)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':title'=>$subject, ':message'=>$message, ':msgto'=>$msgto, ':msgfrom'=>$userid,
			':senddate'=>$now, ':isread'=>0, ':courseid'=>$cid));
		$success = _('Message sent');
	} else if ($_POST['sendtype']=='email') {
		//DB $query = "SELECT FirstName,LastName,email FROM imas_users WHERE id=$msgto";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT FirstName,LastName,email FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$msgto));
		$row = $stm->fetch(PDO::FETCH_NUM);
		$row[2] = trim($row[2]);
		if ($row[2]!='' && $row[2]!='none@none.com') {
			$addy = Sanitize::encodeStringForDisplay($row[0])." ".Sanitize::encodeStringForDisplay($row[1])." <".Sanitize::emailAddress($row[2]).">";
			//DB $subject = stripslashes($_POST['subject']);
			//DB $message = stripslashes($_POST['message']);
			$sessiondata['mathdisp']=2;
			$sessiondata['graphdisp']=2;
			require("../filter/filter.php");
			$message = filter($message);
			$message = preg_replace('/<img([^>])*src="\//','<img $1 src="' . $GLOBALS['basesiteurl'] . '/',$message);
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
			//DB $query = "SELECT FirstName,LastName,email FROM imas_users WHERE id='$userid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT FirstName,LastName,email FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$userid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$self = Sanitize::encodeStringForDisplay($row[0])." ".Sanitize::encodeStringForDisplay($row[1]) ."<". Sanitize::emailAddress($row[2]).">";
			$headers .= "From: $self\r\n";
			mail($addy,$subject,$message,$headers);
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
	//DB $query = "SELECT FirstName,LastName,email FROM imas_users WHERE id=$msgto";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB list($firstname, $lastname, $email) = mysql_fetch_row($result);
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
			//DB $query = "SELECT name FROM imas_assessments WHERE id='".intval($parts[3])."'";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>Sanitize::onlyInt($parts[3])));
			//DB $title = 'Question about #'.($parts[0]+1).' in '.str_replace('"','&quot;',mysql_result($result,0,0));
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
