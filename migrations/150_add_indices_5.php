<?php

//Add additional indexes
$DBH->beginTransaction();

 $query = "ALTER TABLE  `imas_libraries` ADD INDEX ( `groupid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
$DBH->commit();

echo "<p style='color: green;'>✓ Added index for library groupid</p>";

return true;
