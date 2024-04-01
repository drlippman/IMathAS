<?php

//change 
$DBH->beginTransaction();

// delete any duplicates
 $query = "DELETE t1 FROM imas_forum_views t1 JOIN imas_forum_views t2
 WHERE (t1.lastview<t2.lastview OR (t1.lastview=t2.lastview AND t1.id<t2.id))
  AND t1.userid=t2.userid AND t1.threadid=t2.threadid";
 $res = $DBH->query($query);
 if ($res===false) {
     echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
   $DBH->rollBack();
   return false;
 }

 $query = "ALTER TABLE  `imas_forum_views` DROP INDEX `threadid`,
   DROP INDEX `lastview`,
   DROP COLUMN `id`,
   ADD PRIMARY KEY ( `threadid`, `userid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Adjusted indexes imas_forum_views</p>";

return true;
