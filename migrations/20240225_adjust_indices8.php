<?php

//change 
$DBH->beginTransaction();

// drop a bunch of unused indexes
// we're going to skip the usual rollback failed query checks,
// just in case an index has already been dropped
 $query = "ALTER TABLE  `imas_login_log` DROP INDEX `logintime`";
 $res = $DBH->query($query);

 $query = "ALTER TABLE  `imas_assessments` DROP INDEX `ancestors`,
    DROP INDEX `enddate`, DROP INDEX `startdate`, DROP INDEX `reviewdate`,
    DROP INDEX `avail`, DROP INDEX `date_by_lti`, DROP INDEX `cntingb`";
 $res = $DBH->query($query);

 $query = "ALTER TABLE  `imas_assessment_sessions` DROP INDEX `endtime`";
 $res = $DBH->query($query);

 $query = "ALTER TABLE  `imas_exceptions` DROP INDEX `enddate`,
    DROP INDEX `itemtype`, DROP INDEX `is_lti`";
 $res = $DBH->query($query);

 $query = "ALTER TABLE  `imas_teacher_audit_log` DROP INDEX `actionid`,
    DROP INDEX `created_at`";
 $res = $DBH->query($query);

 $query = "ALTER TABLE  `imas_inlinetext` DROP INDEX `startdate`,
    DROP INDEX `enddate`, DROP INDEX `avail`, DROP INDEX `oncal`";
 $res = $DBH->query($query);

 $query = "ALTER TABLE  `imas_linkedtext` DROP INDEX `startdate`,
    DROP INDEX `enddate`, DROP INDEX `avail`, DROP INDEX `oncal`";
 $res = $DBH->query($query);

 $query = "ALTER TABLE  `imas_students` DROP INDEX `lastaccess`";
 $res = $DBH->query($query);

 $query = "ALTER TABLE  `imas_msgs` DROP INDEX `deleted`";
 $res = $DBH->query($query);

 $query = "ALTER TABLE  `imas_grades` DROP INDEX `gradetype`";
 $res = $DBH->query($query);

 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Dropped unused indexes</p>";

return true;
