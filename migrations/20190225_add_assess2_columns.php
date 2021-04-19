<?php

//Add new imas_assessment columns for the new assessment player design
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_assessments`
 	ADD COLUMN submitby ENUM('by_question', 'by_assessment') NOT NULL DEFAULT 'by_question',
  ADD COLUMN keepscore ENUM('last', 'best', 'average') NOT NULL DEFAULT 'best',
 	ADD COLUMN showscores ENUM('during', 'at_end', 'total', 'none') NOT NULL DEFAULT 'during',
 	ADD COLUMN showans ENUM('never','after_lastattempt','jump_to_answer','after_take','with_score','after_1','after_2','after_3','after_4','after_5','after_6','after_7','after_8','after_9') NOT NULL DEFAULT 'after_take',
 	ADD COLUMN viewingb ENUM('immediately', 'after_take', 'after_due', 'never') NOT NULL DEFAULT 'after_take',
 	ADD COLUMN scoresingb ENUM('immediately', 'after_take', 'after_due', 'never') NOT NULL DEFAULT 'immediately',
 	ADD COLUMN ansingb ENUM('after_take', 'after_due', 'never') NOT NULL DEFAULT 'after_due',
 	ADD COLUMN scorebypart TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
 	ADD COLUMN defregens SMALLINT(4) UNSIGNED NOT NULL DEFAULT '0',
 	ADD COLUMN defregenpenalty VARCHAR(6) NOT NULL DEFAULT '0',
  ADD COLUMN overtime_grace MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
  ADD COLUMN overtime_penalty TINYINT UNSIGNED NOT NULL DEFAULT '0',
 	ADD COLUMN ver TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'";
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added columns for new assessplayer to imas_assessments</p>";

return true;
