<?php
//IMathAS: tag old courses needing course emptying
//(c) 2018 David Lippman

/*
  See runcoursecleanup.php for configuration setup
*/

//boost operation time
@set_time_limit(300);

ini_set("max_execution_time", "300");


require_once "../init_without_validate.php";
require_once "../includes/AWSSNSutil.php";

if (php_sapi_name() == "cli") { 
	//running command line - no need for auth code
} else if (!isset($CFG['cleanup']['authcode'])) {
	echo 'You need to set $CFG[\'cleanup\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['cleanup']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

if (isset($_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE'])) {
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
	$query = 'UPDATE imas_courses SET cleanupdate=1 WHERE id IN ( SELECT tempic.id FROM (
		SELECT ic.id FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id 
		JOIN imas_students AS istu ON istu.courseid=ic.id WHERE
		  ic.cleanupdate=0 AND ic.enddate=2000000000 AND iu.groupid=?
		  GROUP BY istu.courseid
		  HAVING MAX(istu.lastaccess)>? AND MAX(istu.lastaccess)<=?
		UNION
		SELECT ic.id FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE
		  ic.cleanupdate=0 AND iu.groupid=? 
		  AND ic.enddate>? AND ic.enddate<=?) as tempic)';
	foreach ($CFG['cleanup']['groups'] as $grp=>$grpdet) {
		$grpold = 24*60*60*$grpdet['old'];
		$stm->execute(array($grp, $lastrun-$grpold, $now-$grpold, $grp, $lastrun-$grpold, $now-$grpold));
		$runGroups[] = $grp;
	}
}



$query = 'UPDATE imas_courses SET cleanupdate=1 WHERE id IN ( SELECT tempic.id FROM (
	SELECT ic.id FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id 
	JOIN imas_students AS istu ON istu.courseid=ic.id WHERE
	  ic.cleanupdate=0 AND ic.enddate=2000000000 ';
if (count($runGroups)>0) {
	$grplist = implode(',', array_map('Sanitize::onlyInt', $runGroups));
	$query .= " AND iu.groupid NOT IN ($grplist) ";
}
$query .= ' GROUP BY istu.courseid
	  HAVING MAX(istu.lastaccess)>? AND MAX(istu.lastaccess)<=?
	UNION
	SELECT ic.id FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE
	  ic.cleanupdate=0 ';
if (count($runGroups)>0) {
	$query .= " AND iu.groupid NOT IN ($grplist) ";
}	  
$query .= 'AND ic.enddate>? AND ic.enddate<=?) as tempic)';
$stm = $DBH->prepare($query);
$stm->execute(array($lastrun-$old, $now - $old, $lastrun-$old, $now - $old));

$stm = $DBH->prepare("UPDATE imas_dbschema SET ver=? WHERE id=5");
$stm->execute(array($now));
$DBH->commit();
echo "Done";
