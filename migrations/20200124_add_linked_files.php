<?php

//Add course start and end dates
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_linked_files` (
   `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
   `filename` VARCHAR(254) NOT NULL
 ) ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
 $res = $DBH->query($query);
 if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
 }
 $query = "ALTER TABLE `imas_linkedtext` ADD `fileid` INT(10) NOT NULL DEFAULT 0";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE  `imas_linkedtext` ADD INDEX ( `fileid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added imas_linked_files</p>";

return true;
