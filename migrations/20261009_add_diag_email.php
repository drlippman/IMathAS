<?php

//add column for emailing results on diagnostic completion
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_diags`
 	ADD COLUMN `resultsemail` VARCHAR(254) NOT NULL DEFAULT ''";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added resultsemail column to imas_diags</p>";

return true;
