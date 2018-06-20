<?php
require("../init.php");
require("../header.php");
@set_time_limit(0);
ini_set("max_execution_time", "900");

if ($myrights==100) {
   if (isset($_POST['submit'])) {
   	   $cid = Sanitize::onlyInt($_POST['cid']);
   	   if ($cid==0) {
   	   	   echo 'Invalid course id';
   	   }
   	   if (isset($_POST['quesset'])) {
   	   	   $n = 0;
   	   	   //DB $query = "SELECT iq.* FROM imas_questions as iq JOIN imas_assessments AS ia ";
   	   	   //DB $query .= "ON iq.assessmentid=ia.id WHERE ia.courseid='$cid'";
   	   	   //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   $query = "SELECT iq.* FROM imas_questions as iq JOIN imas_assessments AS ia ";
   	   	   $query .= "ON iq.assessmentid=ia.id WHERE ia.courseid=:courseid";
   	   	   $stm = $DBH->prepare($query);
   	   	   $stm->execute(array(':courseid'=>$cid));
   	   	   $qset = array();
   	   	   //DB $toset = explode(',','points,attempts');
   	   	   $nq = 0;
   	   	   //DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
   	   	   while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
   	   	   	   //DB $sets = array();
   	   	   	   //DB foreach ($toset as $it) {
   	   	   	   //DB 	   $sets[] = $it."='".addslashes($line[$it])."'";
   	   	   	   //DB }
   	   	   	   //DB $setst = implode(',',$sets);
   	   	   	   //DB $query = "UPDATE imas_questions SET $setst WHERE questionsetid={$line['questionsetid']} AND points=9999 AND attempts=9999";
               //DB mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   	   //DB $n += mysql_affected_rows();
               $stm2 = $DBH->prepare("UPDATE imas_questions SET points=:points,attempts=:attempts WHERE questionsetid=:questionsetid AND points=9999 AND attempts=9999");
               $stm2->execute(array(':points'=>$line['points'], ':attempts'=>$line['attempts'], ':questionsetid'=>$line['questionsetid']));
   	   	   	   $n += $stm2->rowCount();
   	   	   	   $nq++;
   	   	   }
   	   	   echo "<p>Pushed out Question settings for $nq questions.  $n total changes made.</p>";
   	   }
   	   if (isset($_POST['instr'])) {
   	   	   //DB $query = "SELECT name,intro FROM imas_assessments WHERE courseid='$cid'";
   	   	   //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   $stm = $DBH->prepare("SELECT name,intro FROM imas_assessments WHERE courseid=:courseid");
   	   	   $stm->execute(array(':courseid'=>$cid));
   	   	   $n = 0;
   	   	   $na = 0;
   	   	   //DB while ($row = mysql_fetch_array($result)) {
   	   	   //DB while ($row = mysql_fetch_array($result)) {
   	   	   while ($row = $stm->fetch(PDO::FETCH_NUM)) {
   	   	   	   //DB $query = "UPDATE imas_assessments SET intro='".addslashes($row[1])."' WHERE name='{$row[0]}'";
   	   	   	   //DB mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   	   //DB $n += mysql_affected_rows();
   	   	   	   $stm2 = $DBH->prepare("UPDATE imas_assessments SET intro=:intro WHERE name=:name");
   	   	   	   $stm2->execute(array(':name'=>$row[0], ':intro'=>$row[1]));
   	   	   	   $n += $stm2->rowCount();
   	   	   	   $na++;
   	   	   }
   	   	   echo "<p>Pushed out Intro/Instructions for $na assessments.  $n total changes made.</p>";
   	   }
	   if (isset($_POST['caltag'])) {
   	   	   //DB $query = "SELECT name,caltag,calrtag FROM imas_assessments WHERE courseid='$cid'";
   	   	   //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   $stm = $DBH->prepare("SELECT name,caltag,calrtag FROM imas_assessments WHERE courseid=:courseid");
   	   	   $stm->execute(array(':courseid'=>$cid));
   	   	   $n = 0;
   	   	   $na = 0;
   	   	   //DB while ($row = mysql_fetch_array($result)) {
   	   	   while ($row = $stm->fetch(PDO::FETCH_NUM)) {
   	   	   	   //DB $query = "UPDATE imas_assessments SET caltag='".addslashes($row[1])."',calrtag='".addslashes($row[2])."' WHERE name='{$row[0]}'";
   	   	   	   //DB mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   	   //DB $n += mysql_affected_rows();
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
   	   //DB $query = "SELECT ic.id,ic.name FROM imas_courses as ic JOIN imas_teachers ON imas_teachers.courseid=ic.id WHERE imas_teachers.userid='$userid' ORDER BY ic.name";
   	   //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
   	   $stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses as ic JOIN imas_teachers ON imas_teachers.courseid=ic.id WHERE imas_teachers.userid=:userid ORDER BY ic.name");
   	   $stm->execute(array(':userid'=>$userid));
   	   //DB while ($row = mysql_fetch_array($result)) {
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
require("../footer.php");
