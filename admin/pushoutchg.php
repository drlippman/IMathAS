<?php
require_once "../init.php";
require_once "../header.php";

ini_set("max_execution_time", "900");

if ($myrights==100) {
   if (isset($_POST['submit'])) {
   	   $cid = Sanitize::onlyInt($_POST['cid']);
   	   if ($cid==0) {
   	   	   echo 'Invalid course id';
   	   }
   	   if (isset($_POST['quesset'])) {
   	   	   $n = 0;
   	   	   $query = "SELECT iq.* FROM imas_questions as iq JOIN imas_assessments AS ia ";
   	   	   $query .= "ON iq.assessmentid=ia.id WHERE ia.courseid=:courseid";
   	   	   $stm = $DBH->prepare($query);
   	   	   $stm->execute(array(':courseid'=>$cid));
   	   	   $qset = array();
   	   	   $nq = 0;
   	   	   while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
               $stm2 = $DBH->prepare("UPDATE imas_questions SET points=:points,attempts=:attempts WHERE questionsetid=:questionsetid AND points=9999 AND attempts=9999");
               $stm2->execute(array(':points'=>$line['points'], ':attempts'=>$line['attempts'], ':questionsetid'=>$line['questionsetid']));
   	   	   	   $n += $stm2->rowCount();
   	   	   	   $nq++;
   	   	   }
   	   	   echo "<p>Pushed out Question settings for $nq questions.  $n total changes made.</p>";
   	   }
   	   if (isset($_POST['instr'])) {
   	   	   $stm = $DBH->prepare("SELECT name,intro FROM imas_assessments WHERE courseid=:courseid");
   	   	   $stm->execute(array(':courseid'=>$cid));
   	   	   $n = 0;
   	   	   $na = 0;
   	   	   while ($row = $stm->fetch(PDO::FETCH_NUM)) {
   	   	   	   $stm2 = $DBH->prepare("UPDATE imas_assessments SET intro=:intro WHERE name=:name");
   	   	   	   $stm2->execute(array(':name'=>$row[0], ':intro'=>$row[1]));
   	   	   	   $n += $stm2->rowCount();
   	   	   	   $na++;
   	   	   }
   	   	   echo "<p>Pushed out Intro/Instructions for $na assessments.  $n total changes made.</p>";
   	   }
	   if (isset($_POST['caltag'])) {
   	   	   $stm = $DBH->prepare("SELECT name,caltag,calrtag FROM imas_assessments WHERE courseid=:courseid");
   	   	   $stm->execute(array(':courseid'=>$cid));
   	   	   $n = 0;
   	   	   $na = 0;
   	   	   while ($row = $stm->fetch(PDO::FETCH_NUM)) {
   	   	   	   $stm2 = $DBH->prepare("UPDATE imas_assessments SET caltag=:caltag,calrtag=:calrtag WHERE name=:name");
   	   	   	   $stm2->execute(array(':name'=>$row[0], ':caltag'=>$row[1], ':calrtag'=>$row[2]));
   	   	   	   $n += $stm2->rowCount();
   	   	   	   $na++;
   	   	   }
   	   	   echo "<p>Pushed out Calendar Tags for $na assessments.  $n total changes made.</p>";
   	   }
   	   echo '<a href="../index.php">Back to home page</a>';
   } else {
   	   $pagetitle = "Push out Changes";
   	   echo '<h1>Push out Changes</h1>';
   	   echo '<form method="post" action="pushoutchg.php">';
   	   echo '<p>Select the course to push out from: <select name="cid">';
   	   $stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses as ic JOIN imas_teachers ON imas_teachers.courseid=ic.id WHERE imas_teachers.userid=:userid ORDER BY ic.name");
   	   $stm->execute(array(':userid'=>$userid));
   	   while ($row = $stm->fetch(PDO::FETCH_NUM)) {
   	   	   echo '<option value="'.Sanitize::onlyInt($row[0]).'">'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
   	   }
   	   echo '</select></p>';
   	   echo '<p><input type="checkbox" name="quesset" value="1"/> Push out individual question settings (points and attempts).  Does not affect defaults, or overwrite questions that already have per-question points or attempts set.</p>';
   	   echo '<p><input type="checkbox" name="instr" value="1"/> Push out assessment intro/instructions</p>';
	   echo '<p><input type="checkbox" name="caltag" value="1"/> Push out calendar tags</p>';
   	   echo '<p><input type="submit" name="submit" value="Submit"/></p>';
   	   echo '</form>';
   }
} else {
	echo 'Not allowed';
}
require_once "../footer.php";
