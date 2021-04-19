<?php
//Need to revamp managelibs, manageqset DELETE again.

//Add Federation tables
$DBH->beginTransaction();

//Add tables and columns for library federation
$query = 'CREATE TABLE `imas_federation_peers` (
	  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  `peername` VARCHAR(32) NOT NULL,
	  `peerdescription` VARCHAR(254) NOT NULL,
	  `secret` VARCHAR(32) NOT NULL,
	  `url` VARCHAR(254) NOT NULL,
	  `lastpull` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
	) ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 echo '<p>table imas_federation_peers created</p>';
 
 $query = 'CREATE TABLE `imas_federation_pulls` (
	  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  `peerid` INT(10) UNSIGNED NOT NULL,
	  `pulltime` INT(10) UNSIGNED NOT NULL,
	  `step` TINYINT(2) NOT NULL,
	  `fileurl` VARCHAR(254) NOT NULL,
	  `record` TEXT NOT NULL
	) ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 echo '<p>table imas_federation_pulls created</p>';
 
 $query = "ALTER TABLE  `imas_libraries` ADD `federationlevel` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "ALTER TABLE  `imas_libraries` ADD `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE  `imas_libraries` ADD INDEX ( `deleted` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE  `imas_libraries` ADD INDEX ( `lastmoddate` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
 $query = "ALTER TABLE  `imas_library_items` ADD `lastmoddate` INT(10) UNSIGNED NOT NULL DEFAULT '0';";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE  `imas_library_items` ADD `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE  `imas_library_items` ADD INDEX ( `deleted` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE  `imas_library_items` ADD INDEX ( `lastmoddate` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
 $query = "ALTER TABLE  `imas_questionset` ADD `sourceinstall` VARCHAR(32) NOT NULL DEFAULT '';";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE  `imas_questionset` ADD INDEX ( `lastmoddate` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
 $query = "ALTER TABLE  `imas_qimages` CHANGE  `filename`  `filename` VARCHAR(254) NOT NULL DEFAULT '';";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
$query = "ALTER TABLE  `imas_instr_files` CHANGE  `filename`  `filename` VARCHAR(254) NOT NULL DEFAULT '';";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
			 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Federation tables added.</p>";		

return true;

