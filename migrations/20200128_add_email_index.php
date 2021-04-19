<?php

//Add additional indexes
$DBH->beginTransaction();

 $query = "ALTER TABLE  `imas_users` ADD INDEX ( `email` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added users email index</p>";

return true;
