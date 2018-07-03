<?php
//IMathAS.  Records broken question flag
//(c) 2010 David Lippman

require("../init.php");

if (!isset($_GET['qsetid']) || $myrights<20) {
	exit;
}
$qsetid = Sanitize::onlyInt($_GET['qsetid']);
$ischanged = false;

//DB $query = "UPDATE imas_questionset SET broken='{$_GET['flag']}' WHERE id='{$_GET['qsetid']}'";
//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
//DB if (mysql_affected_rows()>0) {
$stm = $DBH->prepare("UPDATE imas_questionset SET broken=:broken WHERE id=:id");
$stm->execute(array(':broken'=>Sanitize::onlyInt($_GET['flag']), ':id'=>$qsetid));
if ($stm->rowCount()>0) {
	$ischanged = true;
	if ($_GET['flag']==1) {
		$now = time();
		//DB $msg = addslashes('Question '.$_GET['qsetid'].' marked broken by '.$userfullname);
		$msg = 'Question '.$qsetid.' marked broken by '.$userfullname;
		//DB $query = "INSERT INTO imas_log (time,log) VALUES($now,'$msg')";
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES(:time, :log)");
		$stm->execute(array(':time'=>$now, ':log'=>$msg));
		if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
			//DB $query = "SELECT ownerid FROM imas_questionset WHERE id='{$_GET['qsetid']}'";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT ownerid FROM imas_questionset WHERE id=:id");
			$stm->execute(array(':id'=>$qsetid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$to = $row[0];
			//DB $query = "INSERT INTO imas_msgs (courseid,title,message,msgto,msgfrom,senddate) VALUES ";
			//DB $query .= "(".$CFG['GEN']['sendquestionproblemsthroughcourse'].",'Question #".$_GET['qsetid']." marked as broken',";
			//DB $query .= "'<p>This is an automated message.  $userfullname has marked question #".$_GET['qsetid']." as broken. Hopefully they follow up with you about what they think is wrong with it.</p>',";
			//DB $query .= "$to,'$userid',$now)";
			//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			$query = "INSERT INTO imas_msgs (courseid,title,message,msgto,msgfrom,senddate) VALUES ";
			$query .= "(:courseid,:title,:message,:to,:from,:senddate)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$CFG['GEN']['sendquestionproblemsthroughcourse'], ':title'=>'Question #'.$qsetid.' marked as broken',
				':message'=>"<p>This is an automated message.  $userfullname has marked question #".$qsetid." as broken. Hopefully they follow up with you about what they think is wrong with it.</p>",
				':to'=>$to, ':from'=>$userid, ':senddate'=>$now));
		}
	}
}

if ($ischanged) {
	echo "OK";
} else {
	echo "Error";
}


?>
