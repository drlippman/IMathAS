<?php
//IMathAS: run notifications and course cleanup
//(c) 2018 David Lippman

/*
To use this, you'll need to set up a cron job or scheduled web call to run:
  util/tagcoursecleanup.php    Run once a day should be fine
  util/runcoursecleanup.php    Run about every 10 min

To use this, define in config.php:
$CFG['cleanup']['authcode']:  a string to be passed in query string as authcode=
   unless you plan to run via command line / cron
$CFG['cleanup']['old']:
   a number of days after which a course is tagged for deletion (def: 610)
$CFG['cleanup']['delay']:
   a number of days to delay after notifying before emptying the course (def: 120)
$CFG['cleanup']['msgfrom']
   the userid to send notification message from (def: 0)
$CFG['cleanup']['keepsent']
   set =0 to keep a copy of sent notifications in sent list
$CFG['cleanup']['allowoptout']:
   (default: true) set to false to prevent teachers opting out
$CFG['cleanup']['deloldstus']:
   (default: true) delete old student accounts that are no longer enrolled in
   any courses and lastaccess is more than
   $CFG['cleanup']['old']+$CFG['cleanup']['delay'] days ago.
$CFG['cleanup']['clearoldpw']:
   a number of days since lastaccess that a users's password should be cleared
   forcing a reset.  Set =0 to not use. (def: 365)

You can specify different old/delay values for different groups by defining
$CFG['cleanup']['groups'] = array(groupid => array('old'=>days, 'delay'=>days));
*/

//boost operation time
@set_time_limit(300);

ini_set("max_execution_time", "300");


require("../init_without_validate.php");
require("../includes/AWSSNSutil.php");
require("../includes/unenroll.php");
require("../includes/delcourse.php");

if (php_sapi_name() == "cli") {
	//running command line - no need for auth code
} else if (!isset($CFG['cleanup']['authcode'])) {
	echo 'You need to set $CFG[\'cleanup\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['cleanup']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

$userid = 0; // need userid for TeacherAuditLog

if (isset($_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE'])) {
	respondOK(); //send 200 response now
}

$now = time();
$old = 24*60*60*(isset($CFG['cleanup']['old'])?$CFG['cleanup']['old']:610);
$delay = 24*60*60*(isset($CFG['cleanup']['delay'])?$CFG['cleanup']['delay']:120);
$msgfrom = isset($CFG['cleanup']['msgfrom'])?$CFG['cleanup']['msgfrom']:0;
$keepsent = isset($CFG['cleanup']['keepsent'])?$CFG['cleanup']['keepsent']:1;
$clearpw = 24*60*60*(isset($CFG['cleanup']['clearoldpw'])?$CFG['cleanup']['clearoldpw']:365);

//run notifications 10 in a batch

$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,deleted,courseid) VALUES ";
$query .= "(:title, :message, :msgto, :msgfrom, :senddate, :deleted, :courseid)";
$msgins = $DBH->prepare($query);

$updcrs = $DBH->prepare("UPDATE imas_courses SET cleanupdate=? WHERE id=?");
$stuchk = $DBH->prepare("SELECT max(lastaccess) FROM imas_students WHERE courseid=?");

$query = "SELECT ic.id,ic.name,ic.ownerid,iu.FirstName,iu.LastName,iu.email,iu.msgnotify,iu.groupid,ic.enddate,ic.available ";
$query .= "FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id ";
$query .= "WHERE ic.cleanupdate=1 ORDER BY ic.id LIMIT 10";
$stm = $DBH->query($query);
$num = 0;
$didDelete = false;
$allowoptout = (!isset($CFG['cleanup']['allowoptout']) || $CFG['cleanup']['allowoptout']==true);
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	if ($row['available']==4) { //soft-deleted course; now we'll hard delete it
		if (!$didDelete) { //one per run
			deleteCourse($row['id']);
			$didDelete = true;
		}
		continue;
	}
	$thisold = $old;
	if (isset($CFG['cleanup']['groups'][$row['groupid']])) {
		$thisold = 24*60*60*$CFG['cleanup']['groups'][$row['groupid']]['old'];
	}
	if ($row['enddate']<2000000000) { //check to see if enddate reset
		if ($row['enddate'] > $now - $thisold) {
			// enddate has been updated - remove from cleaning plan
			$updcrs->execute(array(0, $row['id']));
			continue;
		}
	}
	// check to see if students have become active or course already emptied
	$stuchk->execute(array($row['id']));
	$stulast = $stuchk->fetchColumn(0);
	if ($stulast === null || $stulast > $now - $thisold) {
		// course is already empty, or new student activity - remove from cleanup
		$updcrs->execute(array(0, $row['id']));
		continue;
	}

	if (isset($CFG['cleanup']['groups']) && isset($CFG['cleanup']['groups'][$row['groupid']])) {
		$grpdet = $CFG['cleanup']['groups'][$row['groupid']];
		$thisdelay = 24*60*60*$grpdet['delay'];
		$thisallowoptout = isset($grpdet['allowoptout'])?$grpdet['allowoptout']:$allowoptout;
	} else {
		$thisdelay = $delay;
		$thisallowoptout = $allowoptout;
	}
	$cleanupdate = $now + $thisdelay;
	$dispdate = date('F j, Y', $cleanupdate);

	$msg = '<p>'.sprintf(_('Your course, <b>%s</b> (ID %d) has been scheduled for cleanup on %s.'),
			Sanitize::encodeStringForDisplay($row['name']), $row['id'], $dispdate).'</p>';
	$msg .= '<p>'._('On that date, all student data from this courses will be deleted.').'</p>';
	$msg .= '<p>'._('If you need a copy of course grades for your records, it is recommened you export the gradebook before that date.').'</p>';
	if ($thisallowoptout) {
		$msg .= '<p>'._('If there is a strong reason you need to retain your detailed student data longer, you can disable this cleanup on your course settings page').'</p>';
	}

	$msgins->execute(array(
		':title' => _('Course Cleanup Notification'),
		':message' => $msg,
		':msgto' => $row['ownerid'],
		':msgfrom' => $msgfrom,
		':senddate' => $now,
		':deleted' => $keepsent,
		':courseid' => $row['id']
	));

	require_once("../includes/email.php");

	if ($row['msgnotify'] == 1 && $row['email'] != 'none@none.com' && $row['email'] != '') { //send email notification
		$message  = "<h3>This is an automated message.  Do not respond to this email</h3>\r\n";
		$message .= $msg;
		send_email($row['email'], $sendfrom, _('New message notification'), $message, array(), array(), 10);
	}
	$updcrs->execute(array($cleanupdate, $row['id']));
	$num++;
}

//run cleanup operation, 1 in a batch
$stm = $DBH->prepare("SELECT id,enddate FROM imas_courses WHERE cleanupdate>1 AND cleanupdate<? ORDER BY cleanupdate LIMIT 1");
$stm->execute(array($now));
list($cidtoclean,$enddate) = $stm->fetch(PDO::FETCH_NUM);
$skip = false;
if ($enddate<2000000000) { //check to see if enddate reset
	if ($enddate > $now - $old) {
		// enddate has been updated - remove from cleaning plan
		$skip = true;
	}
}
// check to see if students have become active or course already emptied
$stuchk->execute(array($cidtoclean));
$stulast = $stuchk->fetchColumn(0);
if ($stulast === null || $stulast > $now - $old) {
	// course is already empty, or new student activity - remove from cleanup
	$skip = true;
}

if (!$skip) {
	$stm = $DBH->prepare("SELECT userid FROM imas_students WHERE courseid=?");
	$stm->execute(array($cidtoclean));
	$stus = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$stus[] = $row['userid'];
	}
	// not including in transaction to prevent cleanup from stalling on a
	// weird course
	$stm = $DBH->prepare("UPDATE imas_courses SET cleanupdate=0 WHERE id=?");
	$stm->execute(array($cidtoclean));

	if (count($stus)>0) {
		$DBH->beginTransaction();
		unenrollstu($cidtoclean, $stus, true, false, true, 2);
        $stm = $DBH->prepare("DELETE FROM imas_tutors WHERE courseid=?");
	    $stm->execute(array($cidtoclean));
		$DBH->commit();
	}
} else {
	$updcrs->execute(array(0, $cidtoclean));
	$stm = $DBH->prepare("UPDATE imas_courses SET cleanupdate=0 WHERE id=?");
	$stm->execute(array($cidtoclean));
}

//delete old students
/* this appears to be broken :(
if (!isset($CFG['cleanup']['deloldstus']) || $CFG['cleanup']['deloldstus']==true) {
	$query = 'DELETE imas_users FROM imas_users ';
	$query .= 'LEFT JOIN imas_students ON imas_users.id=imas_students.userid ';
	$query .= 'WHERE imas_users.rights<11 AND imas_students.id IS NULL AND ';
	$query .= 'imas_users.lastaccess<?';
	$stm = $DBH->prepare($query);
	$stm->execute(array($now-$old-$delay));
}
*/

//clear out any old pw
if ($clearpw>0) {
	/*
	As is, this will disable newly created accounts if they're not enrolled in anything,
	which probably isn't ideal

	$query = "UPDATE imas_users SET password=CONCAT('cleared_',MD5(CONCAT(SID, UUID()))) ";
	$query .= "WHERE lastaccess<? AND rights<>11 AND rights<>76 AND rights<>77";
	$stm = $DBH->prepare($query);
	$stm->execute(array($now - $clearpw));
	*/
}
