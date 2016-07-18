<?php
//IMathAS:  A greybox modal for sending a single message or email
//(c) 2014 David Lippman for Lumen Learning

require("../validate.php");
$flexwidth = true;
$nologo = true;

if (isset($_POST['message'])) {
	require_once("../includes/htmLawed.php");
	$_POST['message'] = addslashes(myhtmLawed(stripslashes($_POST['message'])));
	$_POST['subject'] = addslashes(strip_tags(stripslashes($_POST['subject'])));
	$msgto = intval($_POST['sendto']);
	$error = '';
	if ($_POST['sendtype']=='msg') {
		$now = time();
		$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
		$query .= "('{$_POST['subject']}','{$_POST['message']}','$msgto','$userid',$now,0,'$cid')";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$success = _('Message sent');
	} else if ($_POST['sendtype']=='email') {
		$query = "SELECT FirstName,LastName,email FROM imas_users WHERE id=$msgto";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		$row[2] = trim($row[2]);
		if ($row[2]!='' && $row[2]!='none@none.com') {
			$addy = "{$row[0]} {$row[1]} <{$row[2]}>";
			$subject = stripslashes($_POST['subject']);
			$message = stripslashes($_POST['message']);
			$sessiondata['mathdisp']=2;
			$sessiondata['graphdisp']=2;
			require("../filter/filter.php");
			$message = filter($message);
			$message = preg_replace('/<img([^>])*src="\//','<img $1 src="'.$urlmode  . $_SERVER['HTTP_HOST'].'/',$message);
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
			$query = "SELECT FirstName,LastName,email FROM imas_users WHERE id='$userid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			$self = "{$row[0]} {$row[1]} <{$row[2]}>";
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
	$msgto = intval($_GET['to']);
	$query = "SELECT FirstName,LastName,email FROM imas_users WHERE id=$msgto";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	list($firstname, $lastname, $email) = mysql_fetch_row($result);
	$useeditor = "message";
	require("../header.php");
	if ($_GET['sendtype']=='msg') {
		echo '<h2>New Message</h2>';
		$to = "$lastname, $firstname";
	} else if ($_GET['sendtype']=='email') {
		echo '<h2>New Email</h2>';
		$to = "$lastname, $firstname ($email)";
	}
	
	if (isset($_GET['quoteq'])) {
		require("../assessment/displayq2.php");
		$parts = explode('-',$_GET['quoteq']);
		$message = displayq($parts[0],$parts[1],$parts[2],false,false,0,true);
		$message = printfilter(forcefiltergraph($message));
		$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
		
		$message = '<p> </p><br/><hr/>'.$message;
		$courseid = $cid;
		if (isset($parts[3])) {  //sending to instructor
			$query = "SELECT name FROM imas_assessments WHERE id='".intval($parts[3])."'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$title = 'Question about #'.($parts[0]+1).' in '.str_replace('"','&quot;',mysql_result($result,0,0));
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
	echo '<input type="hidden" name="sendtype" value="'.$_GET['sendtype'].'"/>';
	echo "To: $to<br/>\n";
	echo "Subject: <input type=text size=50 name=subject id=subject value=\"$title\"><br/>\n";
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
