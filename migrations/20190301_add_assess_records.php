<?php
// Table for assessment ver 2 records

$DBH->beginTransaction();

//Add tables and columns for new assessment player records
$query = "CREATE TABLE `imas_assessment_records` (
	  `assessmentid` INT(10) UNSIGNED NOT NULL,
	  `userid` INT(10) UNSIGNED NOT NULL,
	  `agroupid` INT(10) UNSIGNED NOT NULL DEFAULT '0',
		`lti_sourcedid` TEXT NOT NULL,
		`ver` TINYINT(1) UNSIGNED NOT NULL DEFAULT '2',
		`timeontask` INT(10) UNSIGNED NOT NULL DEFAULT '0',
		`starttime` INT(10) UNSIGNED NOT NULL DEFAULT '0',
		`lastchange` INT(10) UNSIGNED NOT NULL DEFAULT '0',
		`score` DECIMAL(9,2) UNSIGNED NOT NULL DEFAULT '0',
		`status` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
		`timelimitexp` INT(10) UNSIGNED NOT NULL DEFAULT '0',
		`scoreddata` MEDIUMBLOB NOT NULL,
		`practicedata` MEDIUMBLOB NOT NULL,

		PRIMARY KEY (`assessmentid`, `userid`),
	  INDEX (`userid`),
		INDEX (`agroupid`)
	) ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ table imas_assessment_records created.</p>";

return true;
