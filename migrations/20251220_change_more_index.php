<?php

$DBH->beginTransaction();

$query = 'ALTER TABLE imas_assessment_sessions DROP INDEX `userid`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_exceptions DROP INDEX `userid`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_students DROP INDEX `userid`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_teachers DROP INDEX `userid`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_tutors DROP INDEX `userid`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_lti_courses DROP INDEX `org`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ drop some unused indexes</p>';

return true;
