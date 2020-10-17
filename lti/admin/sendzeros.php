<?php
//IMathAS: sends 0's to LMS for incomplete assignments after the due date
//(c) 2020 David Lippman

// Call as a scheduled task, either using cron or
// as a scheduled web call with authcode option.
// For that, use $CFG['LTI']['authcode'] = "thecode";

/*
    TODO:  Adjust to look for course setting enabling send zeros
        Add migration for indices:

    ALTER TABLE imas_courses ADD INDEX (UIver);		>30s
    ALTER TABLE imas_exceptions ADD INDEX (enddate);	16s
    ALTER TABLE imas_exceptions ADD INDEX (is_lti);		4s
    ALTER TABLE imas_ltiusers ADD INDEX (userid);		16s
    ALTER TABLE imas_assessments ADD INDEX (submitby);
    maybe:
    ALTER TABLE imas_assessment_records ADD INDEX lastchange;
    ALTER TABLE imas_assessment_records ADD INDEX status;
*/

require("../../init_without_validate.php");
ini_set("max_execution_time", "180");

if (php_sapi_name() == "cli") {
	//running command line - no need for auth code
} else if (!isset($CFG['LTI']['authcode'])) {
	echo 'You need to set $CFG[\'LTI\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['LTI']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

function addSendsToLTIQueue($values) {
    $ph = Sanitize::generateQueryPlaceholdersGrouped($values, 5);
	$query = 'INSERT IGNORE INTO imas_ltiqueue ';
	$query .= "(hash, sourcedid, grade, failures, sendon) VALUES $ph";

	$stm = $DBH->prepare($query);
	$stm->execute($vals);
}

// get last run
$stm = $DBH->query('SELECT ver FROM imas_dbschema WHERE id=6');
$lastrun = $stm->fetchColumn(0);
if ($lastrun === null || $lastrun == 0) {
    $lastrun = time() - 24*60*60;
}

$now = time();

// get zeros to send, default due dates:
$query = "SELECT ia.id,ilu.ltiuserid,ilu.org,ili.lineitem,iar.status,iar.score,
    istu.userid
    FROM imas_assessments AS ia
    JOIN imas_courses AS ic ON ia.courseid=ic.id AND ic.UIver=2 AND ic.ltisendzeros=1
    JOIN imas_lti_courses AS ilc ON ilc.courseid=ic.id
    JOIN imas_lti_lineitems AS ili ON ili.itemtype=0 AND ili.typeid=ia.id AND ili.lticourseid=ilc.id
    JOIN imas_students AS istu ON ic.id=istu.courseid
    JOIN imas_ltiusers AS ilu ON ilu.userid=istu.userid AND ilu.org=ilc.org
    LEFT JOIN imas_exceptions AS ie ON ie.userid=istu.userid AND
        ie.assessmentid=ia.id AND ie.itemtype='A'
    LEFT JOIN imas_assessment_records AS iar ON iar.userid=istu.userid AND
        iar.assessmentid=ia.id
    WHERE ia.enddate < :now AND ia.enddate > :lastrun AND
        (ie.enddate IS NULL OR ie.is_lti=1 OR ie.enddate<ia.enddate)
    AND (iar.userid IS NULL OR iar.lastchange=0 OR 
        (ia.submitby='by_assessment' AND (iar.status&64)=0))";

$stm = $DBH->prepare($query);
$stm->execute(array(':now'=>$now, ':lastrun'=>$lastrun));
$vals = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $platform_id = substr($row['org'], 6);
    $sourcedid = 'LTI1.3:|:' . $row['ltiuserid'] . 
            ':|:' . $row['lineitem'] . ':|:' . $platform_id;
    // hash/key is aid-userid
    $key = $row['id'].'-'.$row['userid'];
    array_push($vals, $key, $sourcedid, 0, 0, $now);
    if (count($vals)>500) {
        addSendsToLTIQueue($vals);
        $vals = array();
    }
}

// get zeros with exception due dates

$query = "SELECT ia.id,ilu.ltiuserid,ilu.org,ili.lineitem,iar.status,iar.score,
    istu.userid
    FROM imas_assessments AS ia
    JOIN imas_courses AS ic ON ia.courseid=ic.id AND ic.UIver=2 AND ic.ltisendzeros=1
    JOIN imas_lti_courses AS ilc ON ilc.courseid=ic.id
    JOIN imas_lti_lineitems AS ili ON ili.itemtype=0 AND ili.typeid=ia.id AND ili.lticourseid=ilc.id
    JOIN imas_students AS istu ON ia.courseid=istu.courseid
    JOIN imas_ltiusers AS ilu ON ilu.userid=istu.userid AND ilu.org=ilc.org
    JOIN imas_exceptions AS ie ON ie.userid=istu.userid AND
        ie.assessmentid=ia.id AND ie.itemtype='A'
    LEFT JOIN imas_assessment_records AS iar ON iar.userid=istu.userid AND
        iar.assessmentid=ia.id
    WHERE ie.enddate < :now AND ie.enddate > :lastrun AND 
        ie.is_lti=0 AND ie.enddate>ia.enddate
    AND (iar.userid IS NULL OR iar.lastchange=0 OR 
            (ia.submitby='by_assessment' AND (iar.status&64)=0))";

$stm = $DBH->prepare($query);
$stm->execute(array(':now'=>$now, ':lastrun'=>$lastrun));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $platform_id = substr($row['org'], 6);
    $sourcedid = 'LTI1.3:|:' . $row['ltiuserid'] . 
            ':|:' . $row['lineitem'] . ':|:' . $platform_id;
    // hash/key is aid-userid
    $key = $row['id'].'-'.$row['userid'];
    array_push($vals, $key, $sourcedid, 0, 0, $now);
    if (count($vals)>500) {
        addSendsToLTIQueue($vals);
        $vals = array();
    }
}

if (count($vals) > 0) {
    addSendsToLTIQueue($vals);
}


// update lastrun
$stm = $DBH->prepare('UPDATE imas_dbschema SET ver=? WHERE id=6');
$stm->execute(array($now));
