<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_users` MODIFY FirstName VARCHAR(50) NOT NULL";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

  $query = "ALTER TABLE `imas_users` MODIFY LastName VARCHAR(50) NOT NULL";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Expanded First and Last Name fields to 50 chars</p>";

return true;
