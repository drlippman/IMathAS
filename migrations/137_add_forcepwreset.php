<?php

//Add jsondata field to imas_users
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_users` ADD `forcepwreset` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added forcepwreset field to imas_users</p>";

return true;
