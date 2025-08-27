<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE  `imas_students` ADD INDEX `usercourse` ( `userid`, `courseid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "ALTER TABLE  `imas_tutors` ADD INDEX `usercourse` ( `userid`, `courseid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "ALTER TABLE  `imas_teachers` ADD INDEX `usercourse` ( `userid`, `courseid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "ALTER TABLE  `imas_exceptions` ADD INDEX `useritem` ( `userid`, `assessmentid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "ALTER TABLE  `imas_lti_courses` ADD INDEX `contextorg` ( `contextid`, `org` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }


 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Add joint indexes on imas_students, imas_teachers, imas_tutors, imas_exceptions, imas_lti_courses.</p>";

return true;
