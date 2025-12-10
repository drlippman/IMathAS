<?php

//Add imas_onetime_pw table
$DBH->beginTransaction();

$query = 'ALTER TABLE imas_forum_threads DROP INDEX `lastposttime`, ADD INDEX `forum_time` (`forumid`, `lastposttime`);';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>adjust index on imas_forum_threads</p>';

return true;
