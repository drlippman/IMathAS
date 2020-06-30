<?php

//Add imas_teacher_audit_log table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_lti_lineitems` (
  `itemtype` TINYINT(1) UNSIGNED NOT NULL,
  `typeid` INT(10) UNSIGNED NOT NULL,
  `platformid` INT(10) UNSIGNED NOT NULL,
  `courseid` INT(10) UNSIGNED NOT NULL,
  `lineitem` VARCHAR(2000) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`platformid`,`itemtype`,`typeid`),
  INDEX (`courseid`)
) ENGINE=InnoDB;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

$DBH->commit();
echo '<p>Round 2 of LTI 1.3 tables created</p>';

return true;
