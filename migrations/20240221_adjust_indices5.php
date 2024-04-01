<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_content_track` DROP INDEX `type`";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Removed unused index from imas_content_track</p>";

return true;
