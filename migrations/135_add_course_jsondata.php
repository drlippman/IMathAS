<?php

//Add jsondata field to imas_users
$DBH->beginTransaction();

 $query = "ALTER TABLE  `imas_courses` ADD `jsondata` MEDIUMTEXT NOT NULL";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added jsondata field to imas_courses</p>";

return true;
