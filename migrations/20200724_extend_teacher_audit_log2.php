<?php

//Add imas_teacher_audit_log table
$DBH->beginTransaction();

$query = 'ALTER TABLE `imas_teacher_audit_log` MODIFY COLUMN `action`
  ENUM("Assessment Settings Change",
    "Mass Assessment Settings Change",
    "Mass Date Change",
    "Question Settings Change",
    "Clear Attempts",
    "Clear Scores",
    "Delete Item",
    "Unenroll",
    "Change Grades",
    "Course Settings Change",
    "Inlinetext Settings Change",
    "Link Settings Change",
    "Forum Settings Change",
    "Mass Forum Settings Change",
    "Block Settings Change",
    "Mass Block Settings Change",
    "Wiki Settings Change",
    "Drill Settings Change",
    "Gradebook Settings Change",
    "Roster Action",
    "Exception Change",
    "Delete Post",
    "Offline Grade Settings Change",
    "Change Offline Grades",
    "Change Forum Grades",
    "Change External Tool Grades",
    "Custom1 Settings Change",
    "Mass Custom1 Settings Change",
    "Custom2 Settings Change",
    "Mass Custom2 Settings Change",
    "Custom3 Settings Change",
    "Mass Custom3 Settings Change"
  ) NULL DEFAULT NULL';

$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>add custom enum options to imas_teacher_audit_log</p>';

return true;
