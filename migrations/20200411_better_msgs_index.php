<?php

//Add additional indexes
$DBH->beginTransaction();

 $query = "ALTER TABLE imas_msgs DROP INDEX msgto, ADD INDEX (msgto,isread,courseid)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }


if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added better msgs index</p>";

return true;
