<?php

//Add uniqueid indices
//add federation table indices
$DBH->beginTransaction();

 $query = "ALTER TABLE  `imas_questionset` ADD INDEX ( `uniqueid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE  `imas_libraries` ADD INDEX ( `uniqueid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE  `imas_federation_pulls` ADD INDEX ( `pulltime` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE  `imas_federation_pulls` ADD INDEX ( `step` )";
 $res = $DBH->query($query);
 if ($res===false) {
   echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
  $DBH->rollBack();
  return false;
 }
$query = "ALTER TABLE  `imas_federation_pulls` ADD INDEX ( `peerid` )";
$res = $DBH->query($query);
if ($res===false) {
	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
 	return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added indices for federation</p>";

return true;
