<?php

//Add autoexecuse
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_assessments` ADD `autoexcuse` TEXT NULL DEFAULT NULL";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added autoexcuse to imas_assessments</p>";

return true;
