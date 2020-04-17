<?php

//Add better meantime, meanscore columns
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_msgs` DROP COLUMN isread, DROP INDEX msgfrom";
 $res = $DBH->query($query);
 if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
 }


$DBH->commit();

echo "<p style='color: green;'>✓ Deleted old imas_msgs.isread column</p>";

return true;
