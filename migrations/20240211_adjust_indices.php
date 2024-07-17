<?php

//change 
$DBH->beginTransaction();

 $query = "DROP INDEX `category` ON `imas_questions`";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "ALTER TABLE  `imas_lti_placements` ADD INDEX ( `placementtype`, `typeid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Dropped old index, added lti_placement index</p>";

return true;
