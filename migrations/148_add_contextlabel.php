<?php

//Add course start and end dates
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_lti_courses` ADD `contextlabel` VARCHAR(254) NOT NULL DEFAULT ''";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
$DBH->commit();

echo "<p style='color: green;'>✓ Added contextlabel to imas_lti_courses</p>";

return true;
