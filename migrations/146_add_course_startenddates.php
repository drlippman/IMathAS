<?php

//Add course start and end dates
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_courses` ADD `startdate` INT(10) UNSIGNED NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE `imas_courses` ADD `enddate` INT(10) UNSIGNED NOT NULL DEFAULT '2000000000'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = 'UPDATE imas_courses JOIN (SELECT courseid,MAX(lastaccess) as last FROM imas_students GROUP BY courseid HAVING MAX(lastaccess)<UNIX_TIMESTAMP()-365*24*60*60) AS istu ';
 $query .= 'ON imas_courses.id=istu.courseid ';
 $query .= 'SET imas_courses.enddate = istu.last WHERE istu.last>0 AND imas_courses.enddate=2000000000 ';
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
$DBH->commit();

echo "<p style='color: green;'>✓ Added startdate and enddate fields to imas_courses</p>";

return true;
