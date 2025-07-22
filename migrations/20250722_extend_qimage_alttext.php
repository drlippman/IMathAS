<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_qimages` MODIFY COLUMN alttext VARCHAR(5000)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ extend qimges alttext length</p>";

return true;
