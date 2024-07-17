<?php

//Add better meantime, meanscore columns
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_msgs`
  ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT '0',
  ADD COLUMN `tagged` TINYINT(1) NOT NULL DEFAULT '0',
  ADD COLUMN `viewed` TINYINT(1) NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
 }

 $query = "UPDATE imas_msgs SET
   viewed = (isread&1),
   deleted = ROUND((isread&4)/4 + (isread&2)),
   tagged = ((isread&8)/8)
   WHERE isread>0";
 $res = $DBH->query($query);
 if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
 }

 // most common query is to get unread messages, so need combo index for that
 $query = "CREATE INDEX tocombo ON imas_msgs(msgto,viewed,courseid)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "CREATE INDEX fromcombo ON imas_msgs(msgfrom,deleted,courseid)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "CREATE INDEX tagged ON imas_msgs(tagged)";
 $res = $DBH->query($query);
 if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
 }
 $query = "CREATE INDEX deleted ON imas_msgs(deleted)";
 $res = $DBH->query($query);
 if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added new imas_msgs columns to break up isread</p>";

return true;
