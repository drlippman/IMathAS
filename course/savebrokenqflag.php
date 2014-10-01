<?php
//IMathAS.  Records broken question flag
//(c) 2010 David Lippman

require("../validate.php");

if (!isset($_GET['qsetid']) || $myrights<20) {
	exit;
}
$_GET['qsetid'] = intval($_GET['qsetid']);
$ischanged = false;

$query = "UPDATE imas_questionset SET broken='{$_GET['flag']}' WHERE id='{$_GET['qsetid']}'";
mysql_query($query) or die("Query failed : $query " . mysql_error());
if (mysql_affected_rows()>0) {
	$ischanged = true;
	if ($_GET['flag']==1) {
		$now = time();
		$msg = addslashes('Question '.$_GET['qsetid'].' marked broken by '.$userfullname);
		$query = "INSERT INTO imas_log (time,log) VALUES($now,'$msg')";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
			$query = "SELECT ownerid FROM imas_questionset WHERE id='{$_GET['qsetid']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$row = mysql_fetch_row($result);
			$to = $row[0];
			$query = "INSERT INTO imas_msgs (courseid,title,message,msgto,msgfrom,senddate) VALUES ";
			$query .= "(".$CFG['GEN']['sendquestionproblemsthroughcourse'].",'Question #".$_GET['qsetid']." marked as broken',";
			$query .= "'<p>This is an automated message.  $userfullname has marked question #".$_GET['qsetid']." as broken. Hopefully they follow up with you about what they think is wrong with it.</p>',";
			$query .= "$to,'$userid',$now)";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
		}
	}
}

if ($ischanged) {
	echo "OK";
} else {
	echo "Error";
}


?>
