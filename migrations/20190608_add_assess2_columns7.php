<?php

//Add new imas_assessment columns for the new assessment player design
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_assessments`
 	ADD COLUMN overtime_grace MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
  ADD COLUMN overtime_penalty TINYINT UNSIGNED NOT NULL DEFAULT '0'";
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

$DBH->commit();

echo "<p style='color: green;'>✓ Add overtime options</p>";

return true;
