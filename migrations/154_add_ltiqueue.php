<?php
//Table for LTI message queue


$DBH->beginTransaction();

//Add tables and columns for library federation
$query = 'CREATE TABLE `imas_ltiqueue` (
	  `hash` CHAR(32) NOT NULL PRIMARY KEY,
	  `sourcedid` TEXT NOT NULL,
	  `grade` FLOAT UNSIGNED NOT NULL,
	  `failures` TINYINT(1) UNSIGNED NOT NULL,
	  `sendon` INT(10) UNSIGNED NOT NULL,
	  INDEX (`sendon`),
	  INDEX (`failures`)
	) ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
	 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ table imas_ltiqueue created.  See /admin/processltiqueue.php for config options to enable.</p>";		

return true;

