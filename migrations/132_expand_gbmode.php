<?php
//Need to revamp managelibs, manageqset DELETE again.

//Add Federation tables
$DBH->beginTransaction();


$query = "ALTER TABLE `imas_gbscheme` CHANGE `defgbmode` `defgbmode` INT(10) UNSIGNED NOT NULL DEFAULT '21'";
$res = $DBH->query($query);
if ($res===false) {
  echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
  $DBH->rollBack();
  return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ gbmode column expanded</p>";

return true;
