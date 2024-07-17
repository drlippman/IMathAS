<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_students` ADD COLUMN lockaid INT(10) UNSIGNED NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ add imas_students lockaid column</p>";

return true;
