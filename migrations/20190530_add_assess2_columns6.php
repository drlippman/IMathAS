<?php

//Add new imas_assessment columns for the new assessment player design
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_assessments`
 	MODIFY COLUMN scoresingb ENUM('immediately', 'after_take', 'after_due', 'never') NOT NULL DEFAULT 'immediately'";
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

$DBH->commit();

echo "<p style='color: green;'>✓ Adjust scoresingb options</p>";

return true;
