<?php

//Add imas_captiondata table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_captiondata` (
  `vidid` CHAR(11) NOT NULL PRIMARY KEY,
  `captioned` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `lastchg` INT(10) UNSIGNED NOT NULL,
  INDEX (`status`,`lastchg`)
) CHARACTER SET UTF8 COLLATE utf8_general_ci ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

$DBH->query('INSERT INTO imas_dbschema (id,ver,details) VALUES (7,0,"Last qsetid scanned to captiondata")');

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>table imas_captiondata created. If upgrading, run /util/pullcaptiondata.php several times to populate caption database from question caption data.</p>';

return true;
