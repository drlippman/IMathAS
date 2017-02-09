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
		//DB $query = "UPDATE imas_users SET rights=10 WHERE id='{$_POST['id']}'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_users SET rights=10 WHERE id=:id");
		$stm->execute(array(':id'=>$_POST['id']));
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
			//DB $query = "INSERT INTO imas_groups (name) VALUES ('{$_POST['newgroup']}')";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $group = mysql_insert_id();
			$stm = $DBH->prepare("INSERT INTO imas_groups (name) VALUES (:name)");
			$stm->execute(array(':name'=>$_POST['newgroup']));
			$group = $DBH->lastInsertId();
		} else {
			$group = 0;
		}
		//DB $query = "UPDATE imas_users SET rights=40,groupid=$group WHERE id='{$_POST['id']}'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_users SET rights=40,groupid=:groupid WHERE id=:id");
		$stm->execute(array(':groupid'=>$group, ':id'=>$_POST['id']));

		//DB $query = "SELECT FirstName,SID,email FROM imas_users WHERE id='{$_POST['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT FirstName,SID,email FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$_POST['id']));
		$row = $stm->fetch(PDO::FETCH_NUM);

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
//DB $query = "SELECT id,SID,LastName,FirstName,email FROM imas_users WHERE rights=0 OR rights=12 LIMIT 1 OFFSET $offset";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$stm = $DBH->query("SELECT id,SID,LastName,FirstName,email FROM imas_users WHERE rights=0 OR rights=12 LIMIT 1 OFFSET $offset"); //sanitized above
if ($stm->rowCount()==0) {
	echo 'No one to approve';
} else {
	//DB $row = mysql_fetch_row($result);
	$row = $stm->fetch(PDO::FETCH_NUM);

	//DB $query = "SELECT log FROM imas_log WHERE log LIKE 'New Instructor Request: {$row[0]}::%'";
	//DB $res = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($res)>0) {
		//DB $log = explode('::', mysql_result($res,0,0));
	$stm = $DBH->prepare("SELECT time,log FROM imas_log WHERE log LIKE :log");
	$stm->execute(array(':log'=>"New Instructor Request: {$row[0]}::%"));
	if ($stm->rowCount()>0) {
		$reqdata = $stm->fetch(PDO::FETCH_NUM);
		$reqdate = tzdate("D n/j/y, g:i a", $reqdata[0]); 
		$log = explode('::', $reqdata[1]);
		$details = $log[1];
	} else {
		$details = '';
	}

	echo '<h2>Account Approval</h2>';
	echo '<form method="post" action="approvepending.php?go=true&amp;skipn='.$offset.'">';
	echo '<input type="hidden" name="id" value="'.$row[0].'"/>';
	echo '<input type="hidden" name="email" value="'.$row[4].'"/>';
	echo '<p>Username: '.$row[1].'<br/>Name: '.$row[2].', '.$row[3].' ('.$row[4].')</p>';
	echo '<p>Request made: '.$reqdate.'</p>';
	if ($details != '') {
		echo "<p>$details</p>";
		if (preg_match('/School:(.*?)<br/',$details,$matches)) {
			echo '<p><a target="checkver" href="https://www.google.com/search?q='.urlencode($row[3].' '.$row[2].' '.$matches[1]).'">Search</a></p>';
		}
	}
	echo '<p>Group: <select name="group"><option value="-1">New Group</option>';
	//DB $query = "SELECT id,name FROM imas_groups ORDER BY name";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo '<option value="'.$row[0].'">'.$row[1].'</option>';
	}
	echo '</select> New group: <input type="text" name="newgroup" size="20" /></p>';
	echo '<p><input type="submit" name="approve" value="Approve" /> <input type="submit" name="deny" value="Deny" /> <input type="submit" name="skip" value="Skip" /></p>';
	echo '</form>';
}
require("../footer.php");
?>
