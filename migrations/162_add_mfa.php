<?php

//Add MFA field to imas_users
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_users` ADD `mfa` TEXT NOT NULL";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
  
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added mfa column to imas_users</p>";

return true;
