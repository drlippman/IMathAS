<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_questionset` 
   ADD COLUMN a11yalt INT UNSIGNED NOT NULL DEFAULT 0,
   ADD COLUMN a11yalttype TINYINT(1) UNSIGNED NOT NULL DEFAULT 0";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ add a11yalt columns to imas_questionset</p>";

return true;
