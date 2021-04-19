<?php

//Add additional indexes
$DBH->beginTransaction();

 $query = "ALTER TABLE  `imas_courses` ADD INDEX ( `enddate` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added course enddate index</p>";

return true;
