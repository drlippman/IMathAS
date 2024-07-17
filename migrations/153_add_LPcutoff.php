<?php

//Add course start and end dates
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_assessments` ADD `LPcutoff` INT(10) UNSIGNED NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
 $query = "UPDATE `imas_assessments` SET reviewdate=2000000000 WHERE reviewdate>0";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added LPcutoff to imas_courses</p>";

return true;
