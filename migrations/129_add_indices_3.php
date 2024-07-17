<?php
//Need to revamp managelibs, manageqset DELETE again.

//Add Federation tables
$DBH->beginTransaction();


 $query = "ALTER TABLE  `imas_login_log` ADD INDEX ( `logintime` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
  $query = "ALTER TABLE  `imas_students` ADD INDEX ( `lastaccess` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added indices</p>";

return true;
