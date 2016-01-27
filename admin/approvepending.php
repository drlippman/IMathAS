<?php

require("../validate.php");
if ($myrights<100) {exit;}

if (isset($_GET['skipn'])) {
	$offset = intval($_GET['skipn']);
} else {
	$offset = 0;
}

if (isset($_GET['go'])) {
	if (isset($_POST['skip'])) {
		$offset++;
	} else 	if (isset($_POST['deny'])) {
		$query = "UPDATE imas_users SET rights=10 WHERE id='{$_POST['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		if (isset($CFG['GEN']['enrollonnewinstructor'])) {
			require("../includes/unenroll.php");
			foreach ($CFG['GEN']['enrollonnewinstructor'] as $rcid) {
				unenrollstu($rcid, array(intval($_POST['id'])));
			}
		}
	} else 	if (isset($_POST['approve'])) {
		if ($_POST['group']>-1) {
			$group = intval($_POST['group']);
		} else if (trim($_POST['newgroup'])!='') {
			$query = "INSERT INTO imas_groups (name) VALUES ('{$_POST['newgroup']}')";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$group = mysql_insert_id();
		} else {
			$group = 0;
		}
		$query = "UPDATE imas_users SET rights=40,groupid=$group WHERE id='{$_POST['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		$query = "SELECT FirstName,SID,email FROM imas_users WHERE id='{$_POST['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= "From: $accountapproval\r\n";
		$message = '<style type="text/css">p {margin:0 0 1em 0} </style><p>Hi '.$row[0].'</p>';
		$message .= '<p>Welcome to '.$installname.'.  Your account has been activated, and you\'re all set to log in as an instructor using the username <b>'.$row[1].'</b> and the password you provided.</p>';
			
		if (isset($CFG['GEN']['useSESmail'])) {
			SESmail($row[2], $accountapproval, $installname . ' Account Approval', $message);
		} else {
			mail($row[2],$installname . ' Account Approval',$message,$headers);
		}
	}
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/admin/approvepending.php?skipn=$offset");
	exit;
}

require("../header.php");
$query = "SELECT id,SID,LastName,FirstName,email FROM imas_users WHERE rights=0 OR rights=12 LIMIT 1 OFFSET $offset";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) {
	echo 'No one to approve';
} else {
	$row = mysql_fetch_row($result);
	
	$query = "SELECT log FROM imas_log WHERE log LIKE 'New Instructor Request: {$row[0]}::%'";
	$res = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($res)>0) {
		$log = explode('::', mysql_result($res,0,0));
		$details = $log[1];
	} else {
		$details = '';
	}
	
	echo '<h2>Account Approval</h2>';
	echo '<form method="post" action="approvepending.php?go=true&amp;skipn='.$offset.'">';
	echo '<input type="hidden" name="id" value="'.$row[0].'"/>';
	echo '<input type="hidden" name="email" value="'.$row[4].'"/>';
	echo '<p>Username: '.$row[1].'<br/>Name: '.$row[2].', '.$row[3].' ('.$row[4].')</p>';
	if ($details != '') {
		echo "<p>$details</p>";
		if (preg_match('/School:(.*?)<br/',$details,$matches)) {
			echo '<p><a target="checkver" href="https://www.google.com/search?q='.urlencode($row[3].' '.$row[2].' '.$matches[1]).'">Search</a></p>';
		}
	}
	echo '<p>Group: <select name="group"><option value="-1">New Group</option>';
	$query = "SELECT id,name FROM imas_groups ORDER BY name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo '<option value="'.$row[0].'">'.$row[1].'</option>';
	}
	echo '</select> New group: <input type="text" name="newgroup" size="20" /></p>';
	echo '<p><input type="submit" name="approve" value="Approve" /> <input type="submit" name="deny" value="Deny" /> <input type="submit" name="skip" value="Skip" /></p>';
	echo '</form>';
}
require("../footer.php");
?>
		

