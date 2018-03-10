<?php

//Add ptsposs field to imas_assessments
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_assessments` ADD `ptsposs` SMALLINT(1) NOT NULL DEFAULT '-1'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

$DBH->commit();

echo "<p style='color: green;'>✓ Added ptsposs field to imas_assessments</p>";

return true;
