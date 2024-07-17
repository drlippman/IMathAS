<?php

//Add lastemail for email throttling
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_users` ADD `lastemail` INT(10) UNSIGNED NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added lastemail to imas_users</p>";

return true;
