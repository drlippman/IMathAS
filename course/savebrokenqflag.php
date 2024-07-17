<?php
//IMathAS.  Records broken question flag
//(c) 2010 David Lippman

require_once "../init.php";

if (!isset($_GET['qsetid']) || $myrights<20) {
	exit;
}
$qsetid = Sanitize::onlyInt($_GET['qsetid']);
$ischanged = false;
$stm = $DBH->prepare("UPDATE imas_questionset SET broken=:broken WHERE id=:id");
$stm->execute(array(':broken'=>Sanitize::onlyInt($_GET['flag']), ':id'=>$qsetid));
if ($stm->rowCount()>0) {
	$ischanged = true;
	if ($_GET['flag']==1) {
		$now = time();
		$msg = 'Question '.$qsetid.' marked broken by '.$userfullname;
		$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES(:time, :log)");
		$stm->execute(array(':time'=>$now, ':log'=>$msg));
		if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
			$stm = $DBH->prepare("SELECT ownerid FROM imas_questionset WHERE id=:id");
			$stm->execute(array(':id'=>$qsetid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$to = $row[0];
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
