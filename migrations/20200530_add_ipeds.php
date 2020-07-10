<?php

//Add imas_teacher_audit_log table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_ipeds` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` char(1) NOT NULL,
  `id` CHAR(8) NOT NULL,
  `school` varchar(255) NOT NULL DEFAULT "",
  `agency` varchar(255) NOT NULL DEFAULT "",
  `country` char(2) DEFAULT "US",
  `state` char(2) DEFAULT "",
  `zip` int(5),
  UNIQUE INDEX (`type`,`id`),
  FULLTEXT `school` (`school`)
  FULLTEXT `agency` (`agency`)
  INDEX `zip` (`zip`),
  INDEX `loc` (`country`,`state`)
) ENGINE=InnoDB;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

$query = 'CREATE TABLE `imas_ipeds_group` (
  `type` char(1) NOT NULL,
  `ipedsid` CHAR(8) NOT NULL,
  `groupid` INT(10) NOT NULL,
  PRIMARY KEY `link` (`type`,`ipedsid`,`groupid`),
  INDEX `groupid` (`groupid`)
) ENGINE=InnoDB;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

$DBH->commit();
echo '<p>tables imas_ipeds, imas_ipeds_group created</p>';

return true;
