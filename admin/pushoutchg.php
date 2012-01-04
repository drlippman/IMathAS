<?php
require("../validate.php");
require("../header.php");
@set_time_limit(0);
ini_set("max_execution_time", "900");

if ($myrights==100) {
   if (isset($_POST['submit'])) {
   	   $cid = intval($_POST['cid']);
   	   if ($cid==0) {
   	   	   echo 'Invalid course id';
   	   }
   	   if (isset($_POST['quesset'])) {
   	   	   $n = 0;
   	   	   $query = "SELECT iq.* FROM imas_questions as iq JOIN imas_assessments AS ia ";
   	   	   $query .= "ON iq.assessmentid=ia.id WHERE ia.courseid='$cid'";
   	   	   $result = mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   $qset = array();
   	   	   $toset = explode(',','points,attempts');
   	   	   $nq = 0;
   	   	   while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
   	   	   	   $sets = array();
   	   	   	   foreach ($toset as $it) {
   	   	   	   	   $sets[] = $it."='".addslashes($line[$it])."'";
   	   	   	   }
   	   	   	   $setst = implode(',',$sets);
   	   	   	   $query = "UPDATE imas_questions SET $setst WHERE questionsetid={$line['questionsetid']} AND points=9999 AND attempts=9999";
   	   	   	   mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   	   $n += mysql_affected_rows();
   	   	   	   $nq++;
   	   	   }
   	   	   echo "<p>Pushed out Question settings for $nq questions.  $n total changes made.</p>";
   	   }
   	   if (isset($_POST['instr'])) {
   	   	   $query = "SELECT name,intro FROM imas_assessments WHERE courseid='$cid'";
   	   	   $result = mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   $n = 0;
   	   	   $na = 0;
   	   	   while ($row = mysql_fetch_array($result)) {
   	   	   	   $query = "UPDATE imas_assessments SET intro='".addslashes($row[1])."' WHERE name='{$row[0]}'";
   	   	   	   mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   	   $n += mysql_affected_rows();
   	   	   	   $na++;
   	   	   }
   	   	   echo "<p>Pushed out Intro/Instructions for $na assessments.  $n total changes made.</p>";
   	   }
	   if (isset($_POST['caltag'])) {
   	   	   $query = "SELECT name,caltag,calrtag FROM imas_assessments WHERE courseid='$cid'";
   	   	   $result = mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   $n = 0;
   	   	   $na = 0;
   	   	   while ($row = mysql_fetch_array($result)) {
   	   	   	   $query = "UPDATE imas_assessments SET caltag='".addslashes($row[1])."',calrtag='".addslashes($row[2])."' WHERE name='{$row[0]}'";
   	   	   	   mysql_query($query) or die("Query failed : " . mysql_error());
   	   	   	   $n += mysql_affected_rows();
   	   	   	   $na++;
   	   	   }
   	   	   echo "<p>Pushed out Calendar Tags for $na assessments.  $n total changes made.</p>";
   	   }
   	   echo '<a href="../index.php">Back to home page</a>';
   } else {
   	   $pagetitle = "Push out Changes";
   	   echo '<h2>Push out Changes</h2>';
   	   echo '<form method="post" action="pushoutchg.php">';
   	   echo '<p>Select the course to push out from: <select name="cid">';
   	   $query = "SELECT ic.id,ic.name FROM imas_courses as ic JOIN imas_teachers ON imas_teachers.courseid=ic.id WHERE imas_teachers.userid='$userid' ORDER BY ic.name";
   	   $result = mysql_query($query) or die("Query failed : " . mysql_error());
   	   while ($row = mysql_fetch_array($result)) {
   	   	   echo '<option value="'.$row[0].'">'.$row[1].'</option>';
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
   	   
   	   
