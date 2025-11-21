<?php

//Add imas_onetime_pw table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_emailqueue` (
  `email` VARCHAR(254) NOT NULL,
  `emailfrom` VARCHAR(254) NOT NULL,
  `subject`  VARCHAR(254) NOT NULL,
  `message` TEXT NOT NULL,
  `priority` TINYINT(1) UNSIGNED NOT NULL,
  `sendafter` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`email`,`subject`),
  INDEX (`sendafter`)
) CHARACTER SET UTF8 COLLATE utf8_general_ci ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>table imas_emailqueue.</p>';

return true;
