<?php

$DBH->beginTransaction();

$query = 'ALTER TABLE imas_assessments ADD COLUMN `reqscorejson` TEXT NOT NULL DEFAULT ""';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

$query = 'UPDATE imas_assessments SET 
  reqscoretype=reqscoretype | IF(reqscore < 0, 1, 0),
  reqscorejson=CONCAT("[",reqscoreaid,",",abs(reqscore),",",IF(reqscoretype&2 = 2, 1, 0),"]")
  WHERE reqscoreaid > 0 AND reqscore <> 0';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ add reqscorejson, migrate data</p>';

return true;