<?php
//Need to revamp managelibs, manageqset DELETE again.

//Add Federation tables
$DBH->beginTransaction();


 $query = "UPDATE imas_users SET specialrights = specialrights | 16 WHERE rights > 74";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
  $query = "UPDATE imas_users SET specialrights = specialrights | 112 WHERE rights = 100";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Updated acct creation specialrights for admins</p>";

return true;
