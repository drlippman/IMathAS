<?php

//readd userid index. Removed in 20240212_adjust_indices2, but needed for deloldstus, deladmin 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_content_track` ADD INDEX `userid` (`userid`)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Readd userid index to imas_content_track</p>";

return true;
