<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_assessments` 
    CHANGE `ansingb` `ansingb` ENUM('after_take', 'after_due', 'never', 'after_lp') NOT NULL DEFAULT 'after_due',
    CHANGE `viewingb` `viewingb` ENUM('immediately', 'after_take', 'after_due', 'never', 'after_lp') NOT NULL DEFAULT 'after_take',
 	CHANGE `scoresingb` `scoresingb` ENUM('immediately', 'after_take', 'after_due', 'never', 'after_lp') NOT NULL DEFAULT 'immediately'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Adjusted ENUM on imas_assessments for after_lp</p>";

return true;
