<?php

//Add imas_teacher_audit_log table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_questionerrors` (
  `qsetid` INT(10) UNSIGNED NOT NULL,
  `seed` MEDIUMINT(8) UNSIGNED NOT NULL,
  `scored` TINYINT(1) UNSIGNED NOT NULL,
  `etime` INT(10) UNSIGNED NOT NULL,
  `error` TEXT,
  PRIMARY KEY `edata` (`qsetid`,`seed`,`scored`)
) CHARACTER SET UTF8 COLLATE utf8_general_ci ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>table imas_questionerrors created</p>';

return true;
