<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_students` MODIFY latepass SMALLINT UNSIGNED NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Expanded latepass field to smallint</p>";

return true;
