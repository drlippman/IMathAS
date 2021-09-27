<?php
//Table for LTI message queue


$DBH->beginTransaction();

//Add tables and columns for library federation
$query = 'CREATE TABLE `imas_excused` (
	  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  `userid` INT(10) UNSIGNED NOT NULL,
	  `courseid` INT(10) UNSIGNED NOT NULL,
	  `type` CHAR(1) NOT NULL,
	  `typeid`INT(10) UNSIGNED NOT NULL,
	  `dateset` INT(10) UNSIGNED NOT NULL,
	  INDEX (`userid`),
	  INDEX (`courseid`),
	  INDEX (`type`, `typeid`),
	  UNIQUE (`userid`, `type`, `typeid`)
	) ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
	 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ table imas_excused created.</p>";		

return true;

