<?php

//Add imas_teacher_audit_log table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_ipeds` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` char(1) NOT NULL,
  `ipedsid` VARCHAR(32) NOT NULL,
  `school` varchar(255) NOT NULL DEFAULT "",
  `agency` varchar(255) NOT NULL DEFAULT "",
  `country` char(2) NOT NULL DEFAULT "US",
  `state` char(2) NOT NULL DEFAULT "",
  `zip` int(5),
  INDEX (`type`,`ipedsid`),
  FULLTEXT `school` (`school`),
  FULLTEXT `agency` (`agency`),
  INDEX `zip` (`zip`),
  INDEX `loc` (`country`,`state`)
) CHARACTER SET UTF8 COLLATE utf8_general_ci ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

$query = 'CREATE TABLE `imas_ipeds_group` (
  `type` char(1) NOT NULL,
  `ipedsid` VARCHAR(32) NOT NULL,
  `groupid` INT(10) NOT NULL,
  PRIMARY KEY `link` (`type`,`ipedsid`,`groupid`),
  INDEX `groupid` (`groupid`)
) CHARACTER SET UTF8 COLLATE utf8_general_ci ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>tables imas_ipeds, imas_ipeds_group created</p>';

return true;
