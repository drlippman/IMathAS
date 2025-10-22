<?php

//Add imas_onetime_pw table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_onetime_pw` (
  `assessmentid` INT(10) UNSIGNED NOT NULL,
  `code` CHAR(6) NOT NULL COLLATE utf8_bin,
  `createdon` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`assessmentid`,`code`),
  INDEX (`createdon`)
) CHARACTER SET UTF8 COLLATE utf8_general_ci ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>table imas_onetime_pw.</p>';

return true;
