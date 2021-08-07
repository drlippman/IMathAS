<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_assessments` MODIFY password VARCHAR(254) NOT NULL";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Expanded assessment password field to 254 chars</p>";

return true;
