<?php

//Add imas_onetime_pw table
$DBH->beginTransaction();

$query = 'ALTER TABLE imas_forums DROP INDEX `grpaid`, 
    DROP INDEX `avail`,
    DROP INDEX `startdate`,
    DROP INDEX `enddate`,
    DROP INDEX `replyby`,
    DROP INDEX `postby`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

 $query = 'ALTER TABLE imas_forums DROP INDEX `points`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_wikis DROP INDEX `editbydate`, 
    DROP INDEX `avail`,
    DROP INDEX `startdate`,
    DROP INDEX `enddate`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

 $query = 'ALTER TABLE imas_wiki_revisions DROP INDEX `time`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

 
$query = 'ALTER TABLE imas_students DROP INDEX `code`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_lti_platforms DROP INDEX `created_by`,
    DROP INDEX `uniqid`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_courses DROP INDEX `name`,
    DROP INDEX `startdate`,
    DROP INDEX `UIver`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_forum_posts DROP INDEX `postdate`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_lti_tokens DROP INDEX `expires`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_assessments DROP INDEX `submitby`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_lti_lineitems DROP INDEX `lticourseid`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_external_tools DROP INDEX `url`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_gbitems DROP INDEX `showdate`,
    DROP INDEX `courseid`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

 $query = 'ALTER TABLE imas_gbitems ADD INDEX `course_date` (`courseid`, `showdate`)';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_wiki_views DROP INDEX `stugroupid`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

$query = 'ALTER TABLE imas_drillassess DROP INDEX `startdate`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . ". Continuing anyway.</p>";
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ delete unused indexes</p>';

return true;
