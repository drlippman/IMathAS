<?php

//Add imas_onetime_pw table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_a11yreviews` (
  `qsetid` INT(10) UNSIGNED NOT NULL,
  `userid` INT(10) UNSIGNED NOT NULL,
  `review`  TINYINT(1) UNSIGNED NOT NULL,
  PRIMARY KEY (`qsetid`,`userid`)
) CHARACTER SET UTF8 COLLATE utf8_general_ci ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

$query = "ALTER TABLE `imas_questionset` 
 	ADD COLUMN a11ystatus TINYINT UNSIGNED NOT NULL DEFAULT 0";
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>add table imas_a11yreviews, column a11ystatus</p>';

return true;
