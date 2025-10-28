<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_assessments` 
 	CHANGE `scoresingb` `scoresingb` ENUM('immediately', 'after_take', 'after_due', 'never', 'after_lp', 'manual') NOT NULL DEFAULT 'immediately'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "ALTER TABLE `imas_assessment_records` 
 	ADD COLUMN status2 TINYINT NOT NULL DEFAULT 0";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Adjusted ENUM on imas_assessments for manual, add status2 column on imas_assessment_records</p>";

return true;
