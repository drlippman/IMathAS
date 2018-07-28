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
   
You can specify different old/delay values for different groups by defining
$CFG['cleanup']['groups'] = array(groupid => array('old'=>days, 'delay'=>days));
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
$delay = 24*60*60*(isset($CFG['cleanup']['delay'])?$CFG['cleanup']['delay']:120);
$msgfrom = isset($CFG['cleanup']['msgfrom'])?$CFG['cleanup']['msgfrom']:0;
$keepsent = isset($CFG['cleanup']['keepsent'])?$CFG['cleanup']['keepsent']:4;

//run notifications 10 in a batch

$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= "From: $sendfrom\r\n";

$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
$query .= "(:title, :message, :msgto, :msgfrom, :senddate, :isread, :courseid)";
$msgins = $DBH->prepare($query);

$updcrs = $DBH->prepare("UPDATE imas_courses SET cleanupdate=? WHERE id=?");
	
$query = "SELECT ic.id,ic.name,ic.ownerid,iu.FirstName,iu.LastName,iu.email,iu.msgnotify,iu.groupid ";
$query .= "FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id ";
$query .= "WHERE ic.cleanupdate=1 ORDER BY ic.id LIMIT 10";
$stm = $DBH->query($query);
$num = 0;
$allowoptout = (!isset($CFG['cleanup']['allowoptout']) || $CFG['cleanup']['allowoptout']==true);
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
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
		':isread' => $keepsent,
		':courseid' => $row['id']
	));
	
	if ($row['msgnotify'] == 1 && $row['email'] != 'none@none.com' && $row['email'] != '') { //send email notification
		$message  = "<h3>This is an automated message.  Do not respond to this email</h3>\r\n";
		$message .= $msg;
		mail($row['email'], _('New message notification'), $message, $headers);
	}	
	$updcrs->execute(array($cleanupdate, $row['id']));
	$num++;
}

//run cleanup operation, 1 in a batch
$query = "SELECT userid,courseid FROM imas_students WHERE courseid=";
$query .= "(SELECT id FROM imas_courses WHERE cleanupdate>1 AND cleanupdate<? ORDER BY cleanupdate LIMIT 1)";
$stm = $DBH->prepare($query);
$stm->execute(array($now));
$stus = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$cidtoclean = $row['courseid'];
	$stus[] = $row['userid'];
}
if (count($stus)>0) {
	require("../includes/unenroll.php");
	$DBH->beginTransaction();
	unenrollstu($cidtoclean, $stus, true, false, true, 2);
	$stm = $DBH->prepare("UPDATE imas_courses SET cleanupdate=0 WHERE id=?");
	$stm->execute(array($cidtoclean));
	$DBH->commit();
}