<?php

//Add course start and end dates
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_dbschema` ADD `details` TEXT NOT NULL";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added details to imas_dbschema</p>";

return true;
