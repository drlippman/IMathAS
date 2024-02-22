<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE  `imas_library_items` DROP INDEX `libid`,
   ADD INDEX `libid` ( `libid`, `deleted` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Adjusted indexes imas_library_items</p>";

return true;
