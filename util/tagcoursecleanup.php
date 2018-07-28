<?php
//IMathAS: tag old courses needing course emptying
//(c) 2018 David Lippman

/*
  See runcoursecleanup.php for configuration setup
*/

//boost operation time
@set_time_limit(300);
ini_set("max_input_time", "300");
ini_set("max_execution_time", "300");
ini_set("memory_limit", "104857600");

require("../init_without_validate.php");
require("../includes/AWSSNSutil.php");

if (php_sapi_name() == "cli") { 
	//running command line - no need for auth code
} else if (!isset($CFG['cleanup']['authcode'])) {
	echo 'You need to set $CFG[\'cleanup\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['cleanup']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

if (php_sapi_name() != "cli") {
	respondOK(); //send 200 response now
}
$now = time();
$old = 24*60*60*(isset($CFG['cleanup']['old'])?$CFG['cleanup']['old']:610);

$DBH->beginTransaction();

//lookup the last date search was completed for
$stm = $DBH->query("SELECT ver FROM imas_dbschema WHERE id=5");
$lastrun = $stm->fetchColumn(0);
if ($lastrun === false) {  //run for first time
	$stm = $DBH->query("INSERT INTO imas_dbschema (id,ver) VALUES (5,0)");
	$lastrun = 0;
}

/*
  First run is going to have too many courses to notify at once.
  And, selection query is slow, so we don't want to be running it constantly
*/
$runGroups = array();
if (isset($CFG['cleanup']['groups'])) {
	$query = "UPDATE imas_courses SET cleanupdate=1 WHERE id IN ( SELECT tempic.id FROM (";
	$query .= " SELECT ic.id FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id ";
	$query .= " WHERE iu.groupid=? AND ic.cleanupdate=0 AND ic.id IN (";
	$query .= "  SELECT id FROM imas_courses WHERE cleanupdate=0 AND ((enddate>? AND enddate<=?) OR  ";
	$query .= "    (enddate=2000000000 AND id IN (";
	$query .= "      SELECT courseid FROM imas_students GROUP BY courseid HAVING ";
	$query .= "      MAX(lastaccess)>? AND MAX(lastaccess)<=?))) ";
	$query .= ")) AS tempic)";  
	foreach ($CFG['cleanup']['groups'] as $grp=>$grpdet) {
		$grpold = 24*60*60*$grpdet['old'];
		$stm->execute(array($grp, $lastrun-$grpold, $now-$grpold, $lastrun-$grpold, $now-$grpold));
		$runGroups[] = $grp;
	}
}


$query = "UPDATE imas_courses SET cleanupdate=1 WHERE id IN ( SELECT tempic.id FROM (";
$query .= " SELECT ic.id FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE";
if (count($runGroups)>0) {
	$grplist = implode(',', array_map('Sanitize::onlyInt', $runGroups));
	$query .= " iu.groupid NOT IN ($grplist) AND ";
}
$query .= " ic.cleanupdate=0 AND ic.id IN (";
$query .= "  SELECT id FROM imas_courses WHERE cleanupdate=0 AND ((enddate>? AND enddate<=?) OR  ";
$query .= "    (enddate=2000000000 AND id IN (";
$query .= "      SELECT courseid FROM imas_students GROUP BY courseid HAVING ";
$query .= "      MAX(lastaccess)>? AND MAX(lastaccess)<=?))) ";
$query .= ")) AS tempic)"; 
$stm = $DBH->prepare($query);
$stm->execute(array($lastrun-$old, $now - $old, $lastrun-$old, $now - $old));

$stm = $DBH->prepare("UPDATE imas_dbschema SET ver=? WHERE id=5");
$stm->execute(array($now));
$DBH->commit();
