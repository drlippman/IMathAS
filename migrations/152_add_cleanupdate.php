<?php

//Add course start and end dates
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_courses` ADD `cleanupdate` INT(10) NOT NULL DEFAULT '0'";
 $query .= ", ADD INDEX(`cleanupdate`)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added cleanupdate to imas_courses. See util/runcoursecleanup.php for setup and config.</p>";

return true;
