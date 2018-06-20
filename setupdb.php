<html>
<head>
<link rel="stylesheet" href="imas.css" type="text/css">
</head>
<body>
<?php

/*************

Don't edit this file.  This houses the initial database setup as of 3/17/17.
Starting at that date, all future database changes should be made by creating
a file in the /migrations/ directory, per the readme.md there.

Because of this, this file does NOT contain the full up-to-date database schema.

***************/

$dbsetup = true;
$use_local_sessions = true;
include("init_without_validate.php");

//DB $query = "SELECT ver FROM imas_dbschema WHERE id=1";
//DB $result = mysql_query($query);
//DB if ($result!==false) {
//DB $stm = $DBH->query("SELECT ver FROM imas_dbschema WHERE id=1");
$stm = $DBH->query("SHOW TABLES LIKE 'imas_dbschema'");
if ($stm->rowCount()>0) {
	echo "It appears the database setup has already been run.  Aborting.  If you need to ";
	echo "rerun the setup, clear out your database";
	echo "</body></html>";
	exit;
}
//IMathAS Database Setup
//(c) 2006 David Lippman
if (isset($_POST['dbsetup'])) { //called from install script
	echo "<h2>This step will set up the database required for IMathAS</h2>\n";
	echo "<form method=post action=\"setupdb.php\">\n";

	echo "<fieldset><legend>Initial IMathAS User Information</legend>\n";
	echo "<span class=form>First Name</span>";
	echo "<span class=formright><input type=type name=firstname value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Last Name</span>";
	echo "<span class=formright><input type=type name=lastname value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Username</span>";
	echo "<span class=formright><input type=type name=username value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Password</span>";
	echo "<span class=formright><input type=type name=password value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Email</span>";
	echo "<span class=formright><input type=type name=email value=\"root@yourserver.com\"></span><br class=form>\n";
	echo "</fieldset>\n";
	echo "<div class=submit><input type=submit value=\"Set up database\"></div>\n";
	echo "</form>\n";
	echo "</body></html>\n";
	exit;
} else if (!isset($_POST['username'])) {
	echo "<form method=post action=\"setupdb.php\">\n";
	echo "<h2>This script will set up the database required for IMathAS</h2>\n";
	echo "<p><b>Before submitting this form</b> be sure you have edited the config.php file to match the settings for your server, and the database has been created.</p>\n";
	echo "<fieldset><legend>Initial IMathAS User Information</legend>\n";
	echo "<span class=form>First Name</span>";
	echo "<span class=formright><input type=type name=firstname value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Last Name</span>";
	echo "<span class=formright><input type=type name=lastname value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Username</span>";
	echo "<span class=formright><input type=type name=username value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Password</span>";
	echo "<span class=formright><input type=type name=password value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Email</span>";
	echo "<span class=formright><input type=type name=email value=\"root@$dbserver\"></span><br class=form>\n";
	echo "</fieldset>\n";
	echo "<div class=submit><input type=submit value=\"Set up database\"></div>\n";
	echo "</form>\n";
	echo "<p><sup>*</sup>On some shared servers, your read/write MySQL user can only create new database users and databases ";
	echo "through the provided web administration tool or control panel.  In this case, you will need to create the database and ";
	echo "database users before running this script.  Be sure to modify the config.php file to reflect your chosen database and ";
	echo "database user name.</p>\n";
	echo "</body></html>\n";
	exit;
}

$username = $_POST['username'];
$password = $_POST['password'];
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$email = $_POST['email'];



$sql = 'CREATE TABLE `imas_users` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `SID` VARCHAR(50) NOT NULL, '
        . ' `password` VARCHAR(254) NOT NULL, '
	. ' `rights` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `FirstName` VARCHAR(20) NOT NULL, '
        . ' `LastName` VARCHAR(20) NOT NULL, '
        . ' `email` VARCHAR(100) NOT NULL, '
	. ' `lastaccess` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `groupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `msgnotify` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `qrightsdef` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `deflib` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `usedeflib` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `homelayout` VARCHAR(32)  NOT NULL DEFAULT \'|0,1,2||0,1\','
	. ' `hasuserimg` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `remoteaccess` VARCHAR(10) NOT NULL DEFAULT \'\', '
	. ' `theme` VARCHAR(32) NOT NULL DEFAULT \'\', '
	. ' `listperpage` TINYINT(3) UNSIGNED NOT NULL DEFAULT \'20\', '
	. ' `hideonpostswidget` TEXT NOT NULL, '
	. ' `specialrights` SMALLINT(5) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `FCMtoken` VARCHAR(512) NOT NULL DEFAULT \'\','
	. ' INDEX (`lastaccess`), INDEX (`rights`), INDEX (`groupid`),'
        . ' UNIQUE (`SID`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'User Information\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);


echo 'imas_users created<br/>';

$sql = 'CREATE TABLE `imas_students` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `courseid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `section` VARCHAR(40) NULL DEFAULT NULL, '
	. ' `code` VARCHAR( 32 ) NULL DEFAULT NULL, '
	. ' `gbcomment` TEXT NOT NULL, '
	. ' `gbinstrcomment` TEXT NOT NULL, '
	. ' `latepass` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `lastaccess` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `locked` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `hidefromcourselist` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `timelimitmult` DECIMAL(3,2) UNSIGNED NOT NULL DEFAULT \'1.0\', '
	. ' `stutype` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `custominfo` TEXT NOT NULL, '
        . ' INDEX (`userid`), INDEX (`courseid`), '
	. ' INDEX(`code`), INDEX(`section`), INDEX(`locked`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Which courses each student is enrolled in\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_students created<br/>';

$sql = 'CREATE TABLE `imas_teachers` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `courseid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
        . ' `hidefromcourselist` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`userid`), INDEX(`courseid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Which courses each teacher is teaching\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_teachers created<br/>';

$sql = 'CREATE TABLE `imas_tutors` ('
	. '`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. '`userid` INT(10) UNSIGNED NOT NULL, '
	. '`courseid` INT(10) UNSIGNED NOT NULL, '
	. '`section` VARCHAR(40) NOT NULL, '
	. '`hidefromcourselist` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. 'INDEX (`userid`), INDEX(`courseid`) '
	. ' ) ENGINE = InnoDB '
	. 'COMMENT = \'course tutors\'';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_tutors created<br/>';

$sql = 'CREATE TABLE `imas_courses` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. ' `ownerid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `name` VARCHAR(254) NOT NULL, '
        . ' `enrollkey` VARCHAR(100) NOT NULL, '
	. ' `itemorder` MEDIUMTEXT NOT NULL, '
	. ' `hideicons` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `allowunenroll` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `copyrights` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `blockcnt` INT(10) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `msgset` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `toolset` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `showlatepass` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `available` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `lockaid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `theme` VARCHAR(32) NOT NULL DEFAULT \'default.css\', '
	. ' `latepasshrs` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'24\', '
	. ' `picicons` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `newflag` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `istemplate` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `deflatepass` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `deftime` INT(10) UNSIGNED NOT NULL DEFAULT \'600\','
	. ' `termsurl` VARCHAR(254) NOT NULL DEFAULT \'\', '
	. ' `outcomes` TEXT NOT NULL, '
	. ' `ancestors` TEXT NOT NULL, '
	. ' `ltisecret` VARCHAR(10) NOT NULL, '
	. ' INDEX(`ownerid`), INDEX(`name`), INDEX(`available`), INDEX(`istemplate`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Course list\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_courses created<br/>';

$sql = 'CREATE TABLE `imas_assessments` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `name` VARCHAR(254) NOT NULL, '
	. ' `summary` TEXT NOT NULL, '
        . ' `intro` MEDIUMTEXT NOT NULL, '
        . ' `startdate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `enddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `reviewdate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `timelimit` INT(10) NOT NULL DEFAULT \'0\', '
        . ' `displaymethod` VARCHAR(20) NOT NULL, '
        . ' `defpoints` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'10\', '
        . ' `defattempts` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'1\', '
        . ' `deffeedback` VARCHAR(20) NOT NULL, '
        . ' `defpenalty` VARCHAR(6) NOT NULL DEFAULT \'0\', '
        . ' `deffeedbacktext` VARCHAR(512) NOT NULL DEFAULT \'\', '
	. ' `itemorder` TEXT NOT NULL, '
	. ' `shuffle` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `gbcategory` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `password` VARCHAR(15) NOT NULL, '
	. ' `cntingb` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `minscore` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `showcat` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `showhints` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `showtips` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `isgroup` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `groupsetid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `reqscoreaid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `reqscore` SMALLINT(4) NOT NULL DEFAULT \'0\','
	. ' `noprint` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\','
	. ' `groupmax` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'6\','
	. ' `allowlate` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\','
	. ' `eqnhelper` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `exceptionpenalty` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `posttoforum` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `msgtoinstr` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `istutorial` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `defoutcome` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `ltisecret` VARCHAR(10) NOT NULL, '
	. ' `endmsg` TEXT NOT NULL, '
	. ' `viddata` TEXT NOT NULL, '
	. ' `caltag` VARCHAR(254) NOT NULL DEFAULT \'?\', '
	. ' `calrtag` VARCHAR(254) NOT NULL DEFAULT \'R\', '
	. ' `tutoredit` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `ancestors` TEXT NOT NULL, '
        . ' INDEX (`courseid`), INDEX(`startdate`), INDEX(`enddate`),'
	. ' INDEX(`cntingb`), INDEX(`reviewdate`), INDEX(`avail`), INDEX(`ancestors`(10))'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Assessment info\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_assessments created<br/>';

$sql = 'CREATE TABLE `imas_questions` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `assessmentid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `questionsetid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `points` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'9999\', '
        . ' `attempts` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'9999\', '
        . ' `penalty` VARCHAR(6) NOT NULL DEFAULT \'9999\', '
	. ' `category` VARCHAR(254) NOT NULL DEFAULT \'0\','
	. ' `rubric` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `regen` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `showans` CHAR(1) NOT NULL DEFAULT \'0\','
	. ' `showhints` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `extracredit` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `withdrawn` CHAR(1) NOT NULL DEFAULT \'0\','
	. ' `fixedseeds` TEXT NULL DEFAULT NULL,'
        . ' INDEX (`assessmentid`), INDEX(`questionsetid`), INDEX(`category`) '
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Questions in an assessment\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_questions created<br/>';

$sql = 'CREATE TABLE `imas_questionset` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. ' `uniqueid` BIGINT(16) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `adddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `lastmoddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `ownerid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `author` TEXT NOT NULL, '
        . ' `userights` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'2\', '
        . ' `license` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
        . ' `description` VARCHAR(254) NULL DEFAULT \'\', '
        . ' `qtype` VARCHAR(20) NOT NULL DEFAULT \'\', '
        . ' `control` TEXT NOT NULL, '
        . ' `qcontrol` TEXT NOT NULL, '
        . ' `qtext` TEXT NOT NULL, '
        . ' `answer` TEXT NOT NULL, '
        . ' `solution` TEXT NOT NULL, '
	. ' `extref` TEXT NOT NULL, '
	. ' `hasimg` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `avgtime` VARCHAR(254) NOT NULL DEFAULT \'0\', '
	. ' `ancestors` TEXT NOT NULL, '
	. ' `ancestorauthors` TEXT NOT NULL, '
	. ' `otherattribution` TEXT NOT NULL, '
	. ' `importuid` VARCHAR(254) NULL DEFAULT \'\', '
	. ' `replaceby` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `broken` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `solutionopts` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' INDEX (`ownerid`), INDEX(`userights`), INDEX(`deleted`), INDEX(`replaceby`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Actual set of questions\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_questionset created<br/>';

$sql = 'CREATE TABLE `imas_qimages` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `qsetid` INT(10) UNSIGNED NOT NULL, '
        . ' `var` VARCHAR(50) NOT NULL, '
        . ' `filename` VARCHAR(100) NOT NULL, '
        . ' `alttext` VARCHAR(254) NOT NULL,'
        . ' INDEX (`qsetid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Static image ref for questionset\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_qimages created<br/>';

$sql = 'CREATE TABLE `imas_items` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `itemtype` VARCHAR(20) NOT NULL, '
        . ' `typeid` INT(10) UNSIGNED NOT NULL,'
        . ' INDEX (`courseid`), INDEX(`typeid`), INDEX(`itemtype`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Items within a course\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_items created<br/>';

$sql = 'CREATE TABLE `imas_assessment_sessions` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
	. ' `assessmentid` INT(10) UNSIGNED NOT NULL, '
	. ' `agroupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `lti_sourcedid` TEXT NOT NULL, '
        . ' `questions` TEXT NOT NULL, '
        . ' `seeds` TEXT NOT NULL, '
        . ' `scores` TEXT NOT NULL, '
	. ' `attempts` TEXT NOT NULL, '
	. ' `lastanswers` MEDIUMTEXT NOT NULL, '
	. ' `reattempting` VARCHAR(255) NOT NULL, '
        . ' `starttime` INT(10) NOT NULL, '
	. ' `endtime` INT(10) NOT NULL, '
	. ' `timeontask` TEXT NOT NULL, '
	. ' `bestseeds` TEXT NOT NULL, '
        . ' `bestscores` TEXT NOT NULL, '
	. ' `bestattempts` TEXT NOT NULL, '
	. ' `bestlastanswers` MEDIUMTEXT NOT NULL, '
	. ' `reviewseeds` TEXT NOT NULL, '
        . ' `reviewscores` TEXT NOT NULL, '
	. ' `reviewattempts` TEXT NOT NULL, '
	. ' `reviewlastanswers` MEDIUMTEXT NOT NULL, '
	. ' `reviewreattempting` VARCHAR(255) NOT NULL, '
	. ' `feedback` TEXT NOT NULL,'
	. ' `ver` TINYINT(3) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`userid`), INDEX(`assessmentid`), INDEX(`agroupid`), INDEX(`endtime`),'
        . ' UNIQUE INDEX (userid, assessmentid) '
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Assessment Sessions\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_assessment_sessions created<br/>';

$sql = 'CREATE TABLE `imas_firstscores` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`courseid` INT(10) UNSIGNED NOT NULL,
	`qsetid` INT(10) UNSIGNED NOT NULL,
	`score` TINYINT(3) UNSIGNED NOT NULL,
	`scoredet` TEXT NOT NULL,
	`timespent` SMALLINT(5) UNSIGNED NOT NULL,
	INDEX ( `courseid`), INDEX(`qsetid`)
	) ENGINE = InnoDB';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_firstscores created<br/>';

$sql = 'CREATE TABLE `imas_sessions` ('
        . ' `sessionid` VARCHAR(32) NOT NULL, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
        . ' `time` INT(10) UNSIGNED NOT NULL, '
	. ' `tzoffset` SMALLINT(4) NOT NULL DEFAULT \'0\', '
	. ' `tzname` VARCHAR(254) NOT NULL DEFAULT \'\', '
	. ' `sessiondata` TEXT NOT NULL, '
        . ' PRIMARY KEY (`sessionid`), INDEX(`time`), INDEX(`userid`) '
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Session data\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_sessions created<br/>';

$sql = 'CREATE TABLE `imas_inlinetext` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `title` VARCHAR(254) NOT NULL, '
        . ' `text` TEXT NOT NULL, '
	. ' `startdate` INT(10) UNSIGNED NOT NULL, '
        . ' `enddate` INT(10) UNSIGNED NOT NULL, '
	. ' `fileorder` TEXT NOT NULL, '
	. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `oncal` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `caltag` VARCHAR(254) NOT NULL DEFAULT \'!\', '
	. ' `isplaylist` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `outcomes` TEXT NOT NULL, '
        . ' INDEX (`courseid`), INDEX(`oncal`), INDEX(`avail`), INDEX(`startdate`), INDEX(`enddate`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Inline text items\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_inlinetext created<br/>';

$sql = 'CREATE TABLE `imas_instr_files` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `description` VARCHAR(254) NOT NULL, '
        . ' `filename` VARCHAR(100) NOT NULL, '
        . ' `itemid` INT(10) UNSIGNED NOT NULL,'
        . ' INDEX (`itemid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Inline text file attachments\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_instr_files created<br/>';

$sql = 'CREATE TABLE `imas_linkedtext` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `title` VARCHAR(254) NOT NULL, '
        . ' `summary` TEXT NOT NULL, '
        . ' `text` TEXT NOT NULL, '
        . ' `startdate` INT(10) UNSIGNED NOT NULL, '
        . ' `enddate` INT(10) UNSIGNED NOT NULL,'
	. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `oncal` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `target` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `caltag` VARCHAR(254) NOT NULL DEFAULT \'!\', '
	. ' `points` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `outcomes` TEXT NOT NULL, '
        . ' INDEX (`courseid`), INDEX(`oncal`), INDEX(`avail`), INDEX(`startdate`), INDEX(`enddate`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Linked Text Items\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_linkedtext created<br/>';

$sql = 'CREATE TABLE `imas_exceptions` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
        . ' `itemtype` CHAR(1) NOT NULL DEFAULT \'A\', '
        . ' `assessmentid` INT(10) UNSIGNED NOT NULL, '
        . ' `startdate` INT(10) UNSIGNED NOT NULL, '
        . ' `enddate` INT(10) UNSIGNED NOT NULL, '
	. ' `islatepass` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `waivereqscore` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `exceptionpenalty` TINYINT(1) UNSIGNED NULL DEFAULT NULL, '
        . ' INDEX (`userid`), INDEX(`assessmentid`), INDEX(`itemtype`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Per student exceptions to assessment start/end date\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_exceptions created<br/>';

$sql = 'CREATE TABLE `imas_libraries` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. ' `uniqueid` BIGINT(16) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `adddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `lastmoddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `name` VARCHAR(254) NOT NULL, '
        . ' `ownerid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `userights` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'8\', '
	. ' `sortorder` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `parent` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `groupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
        . ' INDEX (`ownerid`), INDEX(`userights`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'QuestionSet Libraries\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_libraries created<br/>';

$sql = 'CREATE TABLE `imas_library_items` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `libid` INT(10) UNSIGNED NOT NULL, '
        . ' `qsetid` INT(10) UNSIGNED NOT NULL, '
	. ' `ownerid` INT(10) UNSIGNED NOT NULL, '
	. ' `junkflag` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`libid`), INDEX(`qsetid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Library assignments\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_library_items created<br/>';

$sql = 'CREATE TABLE `imas_forums` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `name` VARCHAR(254) NOT NULL, '
        . ' `description` TEXT NOT NULL, '
        . ' `postinstr` TEXT NOT NULL, '
        . ' `replyinstr` TEXT NOT NULL, '
	. ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `startdate` INT(10) UNSIGNED NOT NULL, '
	. ' `enddate` INT(10) UNSIGNED NOT NULL, '
	. ' `settings` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `sortby` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `defdisplay` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `replyby` INT(10) UNSIGNED NOT NULL DEFAULT \'2000000000\', '
	. ' `postby` INT(10) UNSIGNED NOT NULL DEFAULT \'2000000000\', '
	. ' `grpaid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `groupsetid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `points` SMALLINT(5) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `cntingb` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `gbcategory` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `tutoredit` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `rubric` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `caltag` VARCHAR(254) NOT NULL DEFAULT \'FP--FR\', '
	. ' `forumtype` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `allowlate` TINYINT(3) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `taglist` TEXT NOT NULL, '
	. ' `outcomes` TEXT NOT NULL, '
        . ' INDEX (`courseid`), INDEX(`points`), INDEX(`grpaid`), '
	. ' INDEX(`avail`), INDEX(`startdate`), INDEX(`enddate`), INDEX(`replyby`), INDEX(`postby`) '
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Forums\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_forums created<br/>';

$sql = 'CREATE TABLE `imas_forum_threads` ('
	. '`id` INT(10) UNSIGNED NOT NULL, '
	. '`forumid` INT(10) UNSIGNED NOT NULL, '
	. '`stugroupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. '`lastposttime` INT(10) UNSIGNED NOT NULL, '
	. '`lastpostuser` INT(10) UNSIGNED NOT NULL, '
	. '`views` INT(10) UNSIGNED NOT NULL, '
	. ' PRIMARY KEY (`id`), INDEX (`forumid`), INDEX(`lastposttime`), INDEX(`stugroupid`) ) '
	. ' ENGINE = InnoDB '
	. ' COMMENT = \'Forum threads\'';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_forum_threads created<br/>';

$sql = 'CREATE TABLE `imas_forum_posts` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `forumid` INT(10) UNSIGNED NOT NULL, '
	. ' `threadid` INT(10) UNSIGNED NOT NULL, '
	. ' `userid` INT(10) UNSIGNED NOT NULL, '
	. ' `postdate` INT(10) UNSIGNED NOT NULL, '
	. ' `views` INT(10) UNSIGNED NOT NULL, '
	. ' `parent` INT(10) UNSIGNED NOT NULL, '
        . ' `posttype` TINYINT(1) UNSIGNED NOT NULL, '
	. ' `subject` VARCHAR(254) NOT NULL, '
	. ' `message` TEXT NOT NULL, '
	. ' `files` TEXT NOT NULL, '
	. ' `tag` VARCHAR(254) NOT NULL, '
	. ' `isanon` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `replyby` INT(10) UNSIGNED NULL,'
	. ' INDEX (`forumid`), INDEX(`threadid`), INDEX(`userid`), INDEX(`postdate`), INDEX(`tag`) '
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Forum Postings\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_forum_posts created<br/>';

$sql = 'CREATE TABLE `imas_forum_views` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
	. ' `threadid` INT(10) UNSIGNED NOT NULL, '
        . ' `lastview` INT(10) UNSIGNED NOT NULL,'
	. ' `tagged` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`userid`), INDEX(`threadid`), INDEX(`lastview`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Forum last viewings\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_forum_views created<br/>';

$sql = 'CREATE TABLE `imas_forum_likes` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`userid` INT(10) UNSIGNED NOT NULL,
	`threadid` INT(10) UNSIGNED NOT NULL,
	`postid` INT(10) UNSIGNED NOT NULL,
	`type` TINYINT(1) UNSIGNED NOT NULL,
	INDEX (`userid`), INDEX(`threadid`), INDEX(`postid`)
	) ENGINE = InnoDB';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_forum_likes created<br/>';

$sql = 'CREATE TABLE `imas_wikis` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `name` VARCHAR(254) NOT NULL, '
        . ' `description` TEXT NOT NULL, '
	. ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `startdate` INT(10) UNSIGNED NOT NULL, '
        . ' `editbydate` INT(10) UNSIGNED NOT NULL, '
	. ' `enddate` INT(10) UNSIGNED NOT NULL, '
	. ' `settings` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `groupsetid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\','
        . ' INDEX (`courseid`), '
	. ' INDEX(`avail`), INDEX(`startdate`), INDEX(`enddate`), INDEX(`editbydate`) '
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Wikis\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_wikis created<br/>';

$sql = 'CREATE TABLE `imas_wiki_revisions` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `wikiid` INT(10) UNSIGNED NOT NULL, '
        . ' `stugroupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `userid` INT(10) UNSIGNED NOT NULL, '
	. ' `time` INT(10) UNSIGNED NOT NULL,'
	. ' `revision` TEXT NOT NULL, '
        . ' INDEX (`wikiid`), INDEX(`stugroupid`), INDEX(`time`) '
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Wiki revisions\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_wiki_revisions created<br/>';

$sql = 'CREATE TABLE `imas_wiki_views` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
	. ' `wikiid` INT(10) UNSIGNED NOT NULL, '
        . ' `lastview` INT(10) UNSIGNED NOT NULL, '
        . ' `stugroupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	 . ' INDEX (`userid`), INDEX(`wikiid`), INDEX(`stugroupid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Wiki last viewings\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_wiki_views created<br/>';

$sql = 'CREATE TABLE `imas_groups` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `grouptype` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
        . ' `name` VARCHAR(255) NOT NULL,'
        . ' `parent` INT(10) UNSIGNED NOT NULL DEFAULT \'0\''
        . ' )'
        . ' ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_groups created<br/>';

$sql = 'CREATE TABLE `imas_rubrics` ('
	. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. ' `ownerid` INT(10) UNSIGNED NOT NULL, '
	. ' `groupid` INT(10) NOT NULL DEFAULT \'-1\', '
	. ' `name` VARCHAR(254) NOT NULL, '
	. ' `rubrictype` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `rubric` TEXT NOT NULL, '
	. ' INDEX(`ownerid`), INDEX(`groupid`)'
	. ' )'
	. ' ENGINE = InnoDB'
	. ' COMMENT = \'Rubrics\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_rubrics created<br/>';

$sql = 'CREATE TABLE `imas_diags` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `ownerid` INT(10) UNSIGNED NOT NULL, '
        . ' `name` VARCHAR(254) NOT NULL, '
	. ' `term` VARCHAR(10) NOT NULL, '
        . ' `public` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
        . ' `cid` INT(10) UNSIGNED NOT NULL, '
        . ' `idprompt` VARCHAR(254) NOT NULL, '
        . ' `ips` TEXT NOT NULL, '
        . ' `pws` TEXT NOT NULL, '
        . ' `sel1name` VARCHAR(254) NOT NULL, '
        . ' `sel1list` TEXT NOT NULL, '
	. ' `aidlist` TEXT NOT NULL, '
        . ' `sel2name` VARCHAR(254) NOT NULL, '
        . ' `sel2list` TEXT NOT NULL, '
	. ' `entryformat` CHAR(3) NOT NULL DEFAULT \'C0\', '
	. ' `forceregen` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `reentrytime` SMALLINT(5) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`ownerid`), INDEX(`public`), INDEX(`cid`)'
        . ' )'
        . ' ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_diags created<br/>';

$sql = 'CREATE TABLE `imas_diag_onetime` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `diag` INT(10) UNSIGNED NOT NULL, '
        . ' `time` INT(10) UNSIGNED NOT NULL, '
        . ' `code` VARCHAR(9) NOT NULL, '
	. ' `goodfor` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`diag`), INDEX(`time`), INDEX(`code`)'
        . ' )'
        . ' ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_diag_onetime created<br/>';

$sql = 'CREATE TABLE `imas_msgs` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `title` VARCHAR(254) NOT NULL, '
        . ' `message` TEXT NOT NULL, '
        . ' `msgto` INT(10) UNSIGNED NOT NULL, '
        . ' `msgfrom` INT(10) UNSIGNED NOT NULL, '
        . ' `senddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `isread` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `replied` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `parent` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `baseid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' INDEX (`msgto`), INDEX (`isread`), INDEX(`msgfrom`), INDEX(`baseid`), INDEX(`courseid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Internal messages\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_msgs created<br/>';

$sql = 'CREATE TABLE `imas_forum_subscriptions` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `forumid` INT(10) UNSIGNED NOT NULL, '
        . ' `userid` INT(10) UNSIGNED NOT NULL,'
        . ' INDEX (`forumid`), INDEX(`userid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Forum subscriptions\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_forum_subscriptions created<br/>';


$sql = 'CREATE TABLE `imas_gbscheme` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `useweights` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `orderby` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `defaultcat` VARCHAR(254) NOT NULL DEFAULT \'0,0,1,0,-1,0\', '
	. ' `defgbmode` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'21\','
	. ' `stugbmode` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'7\','
	. ' `usersort` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `colorize` VARCHAR(20) NOT NULL, '
	. ' INDEX(`courseid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Gradebook scheme\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_gbscheme created<br/>';

$sql = 'CREATE TABLE `imas_gbitems` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `name` VARCHAR(254) NOT NULL, '
        . ' `points` SMALLINT(4) NOT NULL DEFAULT \'0\', '
        . ' `showdate` INT(10) UNSIGNED NOT NULL, '
        . ' `gbcategory` INT(10) UNSIGNED NOT NULL, '
	. ' `rubric` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `cntingb` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `tutoredit` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `outcomes` TEXT NOT NULL, '
        . ' INDEX (`courseid`), INDEX(`showdate`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Gradebook offline items\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_gbitems created<br/>';

$sql = 'CREATE TABLE `imas_grades` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `gradetype` VARCHAR(15) NOT NULL DEFAULT \'offline\', '
        . ' `gradetypeid` INT(10) UNSIGNED NOT NULL, '
        . ' `refid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
        . ' `score` DECIMAL(6,1) UNSIGNED NULL DEFAULT \'0.0\', '
	. ' `feedback` TEXT NOT NULL, '
        . ' INDEX (`userid`), INDEX (`gradetype`), INDEX(`gradetypeid`), INDEX(`refid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Offline grades\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_grades created<br/>';

$sql = 'CREATE TABLE `imas_gbcats` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `name` VARCHAR(50) NOT NULL, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `calctype` TINYINT(1) NOT NULL DEFAULT \'0\', '
        . ' `scale` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `scaletype` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `chop` DECIMAL(3, 2) UNSIGNED NOT NULL DEFAULT \'1\', '
        . ' `dropn` TINYINT(2) NOT NULL DEFAULT \'0\', '
        . ' `weight` SMALLINT(4) NOT NULL DEFAULT \'-1\','
	. ' `hidden` TINYINT(1) NOT NULL DEFAULT \'0\', '
        . ' INDEX (`courseid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Gradebook Categories\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_gbcats created<br/>';

$sql = 'CREATE TABLE `imas_calitems` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `date` INT(10) UNSIGNED NOT NULL, '
        . ' `title` VARCHAR(254) NOT NULL, '
        . ' `tag` VARCHAR(254) NOT NULL,'
        . ' INDEX (`courseid`), INDEX(`date`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Calendar Items\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_calitems created<br/>';

$sql = 'CREATE TABLE `imas_stugroupset` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `name` VARCHAR(254) NOT NULL, '
	. ' `delempty` TINYINT(1) NOT NULL DEFAULT \'1\', '
        . ' INDEX (`courseid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Student Group Sets\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_stugroupset created<br/>';

$sql = 'CREATE TABLE `imas_stugroups` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `groupsetid` INT(10) UNSIGNED NOT NULL, '
        . ' `name` VARCHAR(254) NOT NULL, '
        . ' INDEX (`groupsetid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Student Groups\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_stugroups created<br/>';

$sql = 'CREATE TABLE `imas_stugroupmembers` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `stugroupid` INT(10) UNSIGNED NOT NULL, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
        . ' INDEX (`stugroupid`), INDEX (`userid`)'
        . ' )'
        . ' ENGINE = InnoDB'
        . ' COMMENT = \'Student Group Members\';';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_stugroupmembers created<br/>';

$sql = 'CREATE TABLE `imas_outcomes` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`courseid` INT(10) UNSIGNED NOT NULL,
	`name` VARCHAR(255) NOT NULL,
	`ancestors` TEXT NOT NULL,
	INDEX ( `courseid`)
	) ENGINE = InnoDB';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_outcomes created<br/>';

$sql = 'CREATE TABLE `imas_ltiusers` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `org` VARCHAR(255) NOT NULL, '
        . ' `ltiuserid` VARCHAR(255) NOT NULL, '
        . ' `userid` INT(10) NOT NULL, '
        . ' INDEX ( `ltiuserid`) '
        . ' )'
        . ' ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_ltiusers created<br/>';

$sql = 'CREATE TABLE `imas_ltinonces` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `nonce` TEXT NOT NULL, '
        . ' `time` INT(10) UNSIGNED NOT NULL'
        . ' )'
        . ' ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_ltinonces created<br/>';

$sql = 'CREATE TABLE `imas_lti_courses` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`org` VARCHAR( 255 ) NOT NULL ,
	`contextid` VARCHAR( 255 ) NOT NULL ,
	`courseid` INT( 10 ) UNSIGNED NOT NULL ,
	 INDEX(`org`,`contextid`)
	) ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_lti_courses created<br/>';

$sql = 'CREATE TABLE `imas_lti_placements` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`org` VARCHAR( 255 ) NOT NULL ,
	`contextid` VARCHAR( 255 ) NOT NULL ,
	`linkid` VARCHAR( 255 ) NOT NULL ,
	`typeid` INT( 10 ) UNSIGNED NOT NULL ,
	`placementtype` VARCHAR( 10 ) NOT NULL ,
	 INDEX(`org`, `contextid`, `linkid`)
	) ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_lti_placements created<br/>';

$sql = 'CREATE TABLE `mc_sessions` ('
        . ' `userid` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,'
        . ' `sessionid` VARCHAR( 32 ) NOT NULL ,'
        . ' `name` VARCHAR( 254 ) NOT NULL ,'
        . ' `room` INT( 10 ) NOT NULL ,'
        . ' `lastping` INT( 10 ) UNSIGNED NOT NULL,'
        . ' `mathdisp` TINYINT( 1 ) NOT NULL ,'
        . ' `graphdisp` TINYINT( 1 ) NOT NULL,'
        . ' INDEX ( `sessionid` ), INDEX( `room` ), INDEX( `lastping` )'
        . ' ) ENGINE = InnoDB;';

//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'mc_sessions created<br/>';

$sql = 'CREATE TABLE `mc_msgs` ('
        . ' `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,'
        . ' `userid` INT( 10 ) UNSIGNED NOT NULL ,'
        . ' `msg` TEXT NOT NULL ,'
        . ' `time` INT( 10 ) UNSIGNED NOT NULL ,'
        . ' INDEX ( `userid` ), INDEX ( `time` )'
        . ' ) ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'mc_msgs created<br/>';

$sql = 'CREATE TABLE `imas_drillassess` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`name` VARCHAR(254) NOT NULL,
	`summary` TEXT NOT NULL,
	`courseid` INT( 10 ) UNSIGNED NOT NULL ,
	`startdate` INT(10) UNSIGNED NOT NULL,
        `enddate` INT(10) UNSIGNED NOT NULL,
	`avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\',
	`caltag` VARCHAR(254) NOT NULL DEFAULT \'D\',
	`itemdescr` TEXT NOT NULL ,
	`itemids` TEXT NOT NULL ,
	`scoretype` CHAR( 3 ) NOT NULL ,
	`showtype` TINYINT( 1 ) UNSIGNED NOT NULL ,
	`n` SMALLINT( 5 ) UNSIGNED NOT NULL ,
	`classbests` TEXT NOT NULL ,
	`showtostu` TINYINT( 1 ) UNSIGNED NOT NULL ,
	INDEX ( `courseid` ), INDEX(`avail`), INDEX(`startdate`), INDEX(`enddate`)
	) ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_drillassess created<br/>';

$sql = 'CREATE TABLE `imas_drillassess_sessions` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`drillassessid` INT( 10 ) UNSIGNED NOT NULL ,
	`userid` INT( 10 ) UNSIGNED NOT NULL ,
	`curitem` TINYINT( 3 ) NOT NULL ,
	`seed` SMALLINT( 5 ) UNSIGNED NOT NULL ,
	`curscores` TEXT NOT NULL ,
	`starttime` INT( 10 ) UNSIGNED NOT NULL ,
	`scorerec` TEXT NOT NULL ,
	INDEX ( `drillassessid`), INDEX(`userid` )
	) ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_drillassess_sessions created<br/>';

$sql = 'CREATE TABLE `imas_login_log` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`userid` INT( 10 ) UNSIGNED NOT NULL ,
	`courseid` INT( 10 ) UNSIGNED NOT NULL ,
	`logintime` INT( 10 ) UNSIGNED NOT NULL ,
	`lastaction` INT( 10 ) UNSIGNED NOT NULL ,
	 INDEX(`userid` ), INDEX(`courseid`)
	) ENGINE = InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_login_log created<br/>';

$sql = 'CREATE TABLE `imas_log` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`time` INT( 10 ) UNSIGNED NOT NULL ,
	`log` TEXT NOT NULL
	) ENGINE = InnoDB';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_log created<br/>';

$sql = 'CREATE TABLE `imas_external_tools` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`name` VARCHAR( 255 ) NOT NULL ,
	`url` VARCHAR( 255 ) NOT NULL ,
	`ltikey` VARCHAR( 255 ) NOT NULL ,
	`secret` VARCHAR( 255 ) NOT NULL ,
	`custom` VARCHAR( 255 ) NOT NULL ,
	`privacy` TINYINT( 1 ) UNSIGNED NOT NULL ,
	`courseid` INT( 10 ) UNSIGNED NOT NULL ,
	`groupid` INT( 10 ) UNSIGNED NOT NULL ,
	INDEX ( `url` ), INDEX( `courseid` ), INDEX( `groupid` )
	) ENGINE = InnoDB COMMENT = \'LTI external tools\'';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_external_tools created<br/>';

$sql = 'CREATE TABLE `imas_badgesettings` (
	  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  `name` varchar(128) NOT NULL,
	  `badgetext` varchar(254) NOT NULL,
	  `description` varchar(128) NOT NULL,
	  `longdescription` text NOT NULL,
	  `courseid` int(10) unsigned NOT NULL,
	  `requirements` text NOT NULL,
	  INDEX(`courseid`)
	) ENGINE=InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_badgesettings created<br/>';

$sql = 'CREATE TABLE `imas_badgerecords` (
	  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  `userid` int(10) unsigned NOT NULL,
	  `badgeid` int(10) unsigned NOT NULL,
	  `data` text NOT NULL,
	  INDEX (`userid`), INDEX(`badgeid`)
	) ENGINE=InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_badgerecords created<br/>';

$sql = 'CREATE TABLE `imas_bookmarks` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`courseid` INT( 10 ) UNSIGNED NOT NULL ,
	`userid` INT( 10 ) UNSIGNED NOT NULL ,
	`name` VARCHAR( 128 ) NOT NULL ,
	`value` TEXT NOT NULL ,
	INDEX ( `courseid`) , INDEX( `userid`) , INDEX( `name` )
	) ENGINE = InnoDB';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_bookmarks created<br/>';

$sql = 'CREATE TABLE `imas_content_track` (
	`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`userid` INT(10) UNSIGNED NOT NULL,
	`courseid` INT(10) UNSIGNED NOT NULL,
	`type` VARCHAR(254) NOT NULL,
	`typeid` INT(10) UNSIGNED NOT NULL,
	`viewtime` INT(10) UNSIGNED NOT NULL,
	`info` VARCHAR(254) NOT NULL,
	INDEX ( `courseid`) , INDEX( `userid`),
	INDEX ( `typeid`)
	) ENGINE = InnoDB';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_content_track created<br/>';

$sql = 'CREATE TABLE `imas_livepoll_status` (
	  `assessmentid` INT(10) unsigned NOT NULL PRIMARY KEY,
	  `curquestion` TINYINT(2) unsigned NOT NULL,
	  `curstate` TINYINT(1) unsigned NOT NULL,
	  `seed` INT(10) unsigned NOT NULL,
	  `startt` BIGINT(13) unsigned NOT NULL
	) ENGINE=InnoDB;';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_livepoll_status created<br/>';

$sql = 'CREATE TABLE `imas_dbschema` (
	`id` INT( 10 ) UNSIGNED NOT NULL PRIMARY KEY ,
	`ver` INT( 10 ) UNSIGNED NOT NULL
	) ENGINE = InnoDB';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
$sql = 'INSERT INTO imas_dbschema (id,ver) VALUES (2,0)';  //initialize guest account counter
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'imas_dbschema created<br/>';

$sql = 'CREATE TABLE `php_sessions` (
	`id` VARCHAR(32) NOT NULL,
	`access` INT(10) unsigned DEFAULT NULL,
	`data` TEXT,
	PRIMARY KEY (`id`),
	INDEX (`access`)
	) ENGINE=InnoDB';
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$DBH->query($sql);
echo 'php_sessions created<br/>';

if (isset($CFG['GEN']['newpasswords'])) {
	require_once("includes/password.php");
	$md5pw = password_hash($password, PASSWORD_DEFAULT);
} else {
	$md5pw = md5($password);
}
$now = time();
//DB $query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email) VALUES ('$username','$md5pw',100,'$firstname','$lastname','$email')";
//DB mysql_query($sql) or die("Query failed : $sql " . mysql_error());
$stm = $DBH->prepare("INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email) VALUES (:SID, :password, :rights, :FirstName, :LastName, :email)");
$stm->execute(array(':SID'=>$username, ':password'=>$md5pw, ':rights'=>100, ':FirstName'=>$firstname, ':LastName'=>$lastname, ':email'=>$email));

echo "user " . Sanitize::encodeStringForDisplay($username) . " created<br/>";

//write upgradecounter
require("upgrade.php");

echo "<p><b>Database setup complete</b>.  <a href=\"index.php\">Go to IMathAS login page</a>, or <a href=\"installexamples.php\">install a library of example questions</a> (will ask you to log in)</p>";
?>
</body>
</html>
