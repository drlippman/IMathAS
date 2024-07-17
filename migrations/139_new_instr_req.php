<?php
//Tables for holding new instructor account requests, to replace the existing
//use of imas_logs

/*
Status field:
  0: New request
  1: Needs investigation
  2: Waiting for confirmation
  3: Probably should be denied, but not totally sure
  10: Denied / demoted to student
  11: Approved
*/

$DBH->beginTransaction();

//Add tables and columns for library federation
$query = 'CREATE TABLE `imas_instr_acct_reqs` (
	  `userid` INT(10) UNSIGNED NOT NULL PRIMARY KEY,
	  `status` TINYINT(2) NOT NULL,
	  `reqdate` INT(10) UNSIGNED NOT NULL,
	  `reqdata` TEXT NOT NULL,
	  INDEX (`status`)
	) ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
	 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ table imas_instr_acct_reqs created.  You may want to update your newinstructor.php script.</p>";		

return true;

