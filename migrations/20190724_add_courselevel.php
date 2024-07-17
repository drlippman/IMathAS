<?php

//Add new imas_assessment columns for the new assessment player design
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_courses` ADD COLUMN level VARCHAR(254) NOT NULL DEFAULT ''";
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added level column to imas_courses</p>";

return true;
