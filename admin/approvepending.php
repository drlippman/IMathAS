<?php

require("../init.php");

if ($myrights<100 && ($myspecialrights&64)!=64) {exit;}

if (isset($_GET['skipn'])) {
	$offset =  Sanitize::onlyInt(($_GET['skipn']));
} else {
	$offset = 0;
}
$uid = Sanitize::onlyInt($_POST['id']);
if (isset($_GET['go'])) {
	if (isset($_POST['skip'])) {
		$offset++;
	} else 	if (isset($_POST['deny'])) {
		//DB $query = "UPDATE imas_users SET rights=10 WHERE id='{$_POST['id']}'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_users SET rights=10 WHERE id=:id");
		$stm->execute(array(':id'=>$uid));
		if (isset($CFG['GEN']['enrollonnewinstructor'])) {
			require("../includes/unenroll.php");
			foreach ($CFG['GEN']['enrollonnewinstructor'] as $rcid) {
				unenrollstu($rcid, array($uid));
			}
		}
		$stm = $DBH->prepare("UPDATE imas_instr_acct_reqs SET status=10 WHERE userid=:id");
		$stm->execute(array(':id'=>$uid));
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
		$stm->execute(array(':groupid'=>$group, ':id'=>$uid));

		$stm = $DBH->prepare("UPDATE imas_instr_acct_reqs SET status=11 WHERE userid=:id");
		$stm->execute(array(':id'=>$uid));
		
		//DB $query = "SELECT FirstName,SID,email FROM imas_users WHERE id='{$_POST['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT FirstName,SID,email FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$uid));
		$row = $stm->fetch(PDO::FETCH_NUM);

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= "From: $accountapproval\r\n";
		$message = '<style type="text/css">p {margin:0 0 1em 0} </style><p>Hi '.Sanitize::encodeStringForDisplay($row[0]).'</p>';
		$message .= '<p>Welcome to '.$installname.'.  Your account has been activated, and you\'re all set to log in as an instructor using the username <b>'.Sanitize::encodeStringForDisplay($row[1]).'</b> and the password you provided.</p>';

		if (isset($CFG['GEN']['useSESmail'])) {
			SESmail($row[2], $accountapproval, $installname . ' Account Approval', $message);
		} else {
			mail($row[2],$installname . ' Account Approval',$message,$headers);
		}
	}
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/approvepending.php?skipn=$offset&r=".Sanitize::randomQueryStringParam());
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
	echo '<input type="hidden" name="email" value="' . Sanitize::encodeStringForDisplay($row[4]) . '"/>';
	echo '<input type="hidden" name="id" value="' . Sanitize::encodeStringForDisplay($row[0]) . '"/>';
	echo '<p>Username: ' . Sanitize::encodeStringForDisplay($row[1]) . '<br/>Name: ' . Sanitize::encodeStringForDisplay($row[2]) . ', ' . Sanitize::encodeStringForDisplay($row[3]) . ' (' . Sanitize::encodeStringForDisplay($row[4]) . ')</p>';
	echo '<p>Request made: '.Sanitize::encodeStringForDisplay($reqdate).'</p>';
	$school = '';
	if ($details != '') {
		$cleanDetails = sanitizeNewInstructorRequestLog($details);
		echo "<p>$cleanDetails</p>";
		if (preg_match('/School:(.*?)<br/',$details,$matches)) {
			$school = normalizeGroup($matches[1]);
			echo '<p><a target="checkver" href="https://www.google.com/search?q='.Sanitize::encodeUrlParam($row[3].' '.$row[2].' '.$matches[1]).'">Search</a></p>';
		}
	}
	$havesuggestion = false;
	$breakdist = min(5,.5*strlen($school));
	if (strlen($school)<4) {$breakdist = .5;}
	$emailparts = explode('@',$row[4]);
	$emailgroupsuggestions = array();
	if (preg_match('/(edu|us)$/', $emailparts[1])) {
		$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE rights>19 AND email LIKE :domain");
		$stm->execute(array(':domain'=>'%@'.$emailparts[1]));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if (isset($emailgroupsuggestions[$row[0]])) {
				$emailgroupsuggestions[$row[0]]++;
			} else {
				$emailgroupsuggestions[$row[0]] = 1;
			}
		}
		if (count($emailgroupsuggestions)>0) {
			arsort($emailgroupsuggestions);
			$havesuggestion = true;
		}
	}


	echo '<p>Group: <select name="group"><option value="-1">New Group</option>';
	//DB $query = "SELECT id,name FROM imas_groups ORDER BY name";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
	$opts = '';
	$groups = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$opts .= '<option value="'.Sanitize::onlyInt($row[0]).'">'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
		if ($school!='' && !$havesuggestion) {
			$groups[] = array(levenshtein($school,normalizeGroup($row[1])), $row[0], $row[1]);
		}
		if (isset($emailgroupsuggestions[$row[0]])) {
			$emailgroupsuggestions[$row[0]] = $row[1];
		}
	}
	if ($school!='') {
		usort($groups, function($a,$b) {
			if ($a[0]==$b[0]) {
				return strcmp($a[2],$b[2]);
			} else {
				return ($a[0]<$b[0])?-1:1;
			}
		});
	}
	if (($school != '' && $groups[0][0]<$breakdist) || $havesuggestion) {
		echo '<optgroup label="Suggestions">';
		$first = true;
		if ($havesuggestion) {
			foreach ($emailgroupsuggestions as $g=>$n) {
				echo '<option value="'.$g.'"'.($first?' selected':'').'>'.Sanitize::encodeStringForDisplay($n).'</option>';
				$first = false;
			}
		} else {
			foreach ($groups as $group) {
				if ($group[0]>=$breakdist) {break;}
				echo '<option value="'.Sanitize::onlyInt($group[1]).'"'.($first&&$group[0]<3?' selected':'').'>'.Sanitize::encodeStringForDisplay($group[2]).'</option>';
				$first = false;
			}
		}
		echo '</optgroup>';
		echo '<optgroup label="Others">';
		echo $opts;
		echo '</optgroup>';
	} else {
		echo $opts;
	}

	echo '</select> New group: <input type="text" name="newgroup" size="20" /></p>';
	echo '<p><input type="submit" name="approve" value="Approve" /> <input type="submit" name="deny" value="Deny" /> <input type="submit" name="skip" value="Skip" /></p>';
	echo '</form>';
}
require("../footer.php");

function normalizeGroup($g) {
	$g = preg_replace("/\b(sd|cc|su|of|hs|hsd|usd|isd|school|unified|public|county|district|college|community|university|univ|state|\.edu|www\.)\b/","",strtolower($g));
	$g = preg_replace('/\bmt(\.|\b)/','mount',$g);
	$g = preg_replace('/\bst(\.|\b)/','saint',$g);
	$g = trim($g);
	return $g;
}

/**
 * Sanitize a new instructor request log string.
 *
 * When a new instructor request is submitted, a row of text with the request details
 * is placed into the database in the "imas_log" table. The code for this can be found
 * in /newinstructor.php around line 80. Search for "imas_log".
 *
 * Note: If new log data is added via /newinstructor, this function will similarly need
 * to be updated.
 *
 * @param $logtext string The entire "log" column from the "imas_log" table.
 * @return string The entire string, sanitized.
 */
function sanitizeNewInstructorRequestLog($logtext) {
	$sanitizedLogText = '';

	$school = '';
	$verificationUrl = '';
	$phone = '';

	$parts = explode('<br/>', $logtext);

	// First, extract the user input we need to sanitize.
	foreach ($parts as $part) {
		if (preg_match("/School:\s?(.*)/", $part, $matches)) {
			$school = trim($matches[1]);
		} elseif (preg_match("/VerificationURL:\s?(.*)/", $part, $matches)) {
			$verificationUrl = trim($matches[1]);
		} elseif (preg_match("/Phone:\s?(.*)/", $part, $matches)) {
			$phone = trim($matches[1]);
		}
	}

	// Put it all back together and return a sanitized string.
	if (!empty($school)) {
		$sanitizedLogText .= "School: " . Sanitize::encodeStringForDisplay($school);
	}
	if (!empty($verificationUrl)) {
		if (!empty($sanitizedLogText)) $sanitizedLogText .= "<br/>";
		//$verificationUrl is html so dont sanitize
		$sanitizedLogText .= "VerificationURL: " . $verificationUrl;
	}
	if (!empty($phone)) {
		if (!empty($sanitizedLogText)) $sanitizedLogText .= "<br/>";
		$sanitizedLogText .= "Phone: " . Sanitize::encodeStringForDisplay($phone);
	}

	return $sanitizedLogText;
}
?>
