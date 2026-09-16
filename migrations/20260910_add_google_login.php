<?php

//Add imas_google table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_google` (
  user_id       INT NOT NULL PRIMARY KEY,
  google_sub    VARCHAR(255) NOT NULL,
  google_email  VARCHAR(255) NOT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY google_sub (google_sub)
) CHARACTER SET UTF8 COLLATE utf8_general_ci ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';

$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ add table imas_google</p>';

return true;
