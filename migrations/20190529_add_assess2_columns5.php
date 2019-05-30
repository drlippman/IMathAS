<?php

//Add new imas_assessment columns for the new assessment player design
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_assessment_records`
 	ADD COLUMN timelimitexp INT(10) UNSIGNED NOT NULL DEFAULT 0";
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

$DBH->commit();

echo "<p style='color: green;'>✓ Added timelimitexp for new assessplayer to imas_assessment_records</p>";

return true;
