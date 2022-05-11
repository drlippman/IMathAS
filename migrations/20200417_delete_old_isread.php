<?php

$DBH->beginTransaction();

  // update any values that were missed
  $query = "UPDATE imas_msgs SET
    viewed = (isread&1),
    deleted = ROUND((isread&4)/4 + (isread&2)),
    tagged = ((isread&8)/8)
    WHERE isread>0 AND viewed=0 AND deleted=0 AND tagged=0";
  $res = $DBH->query($query);
  if ($res===false) {
     echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
  $DBH->rollBack();
  return false;
  }

  // drop old columns
  $query = "ALTER TABLE `imas_msgs` DROP COLUMN isread, DROP INDEX msgfrom";
  $res = $DBH->query($query);
  if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
  $DBH->rollBack();
  return false;
  }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Deleted old imas_msgs.isread column</p>";

return true;
