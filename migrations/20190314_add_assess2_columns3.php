<?php

//Add new imas_assessment columns for the new assessment player design
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_assessments`
 	ADD COLUMN keepscore ENUM('last', 'best', 'average') NOT NULL DEFAULT 'best'";
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

$DBH->commit();

echo "<p style='color: green;'>✓ Added more columns for new assessplayer to imas_assessments</p>";

return true;
