<?php

//change 
$DBH->beginTransaction();


 $query = "ALTER TABLE  `imas_content_track` DROP INDEX `courseid`, DROP INDEX `userid`,
   ADD INDEX `course_user` ( `courseid`, `userid` ),
   ADD INDEX `type` (`type`)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Adjusted indexes imas_content_track</p>";

return true;
