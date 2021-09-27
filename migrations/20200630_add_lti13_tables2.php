<?php

//Add imas_teacher_audit_log table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_lti_lineitems` (
  `itemtype` TINYINT(1) UNSIGNED NOT NULL,
  `typeid` INT(10) UNSIGNED NOT NULL,
  `lticourseid` INT(10) UNSIGNED NOT NULL,
  `lineitem` VARCHAR(2000) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`itemtype`,`typeid`,`lticourseid`),
  INDEX (`lticourseid`)
) ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

$query = "ALTER TABLE `imas_students` ADD `lticourseid` INT(10) UNSIGNED NOT NULL DEFAULT '0'";
$res = $DBH->query($query);
if ($res===false) {
  echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>LTI 1.3 lineitem tables created</p>';

return true;
