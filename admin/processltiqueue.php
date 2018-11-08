<?php
//IMathAS: process LTI message queue
//(c) 2018 David Lippman

/*
  To use the LTI queue, you'll need to either set up a cron job to call this
  script, or call it using a scheduled web call with the authcode option.
  It should be called every minute.
  
  Config options (in config.php):
  To enable using LTI queue:
     $CFG['LTI']['usequeue'] = true; 
     
  The delay between getting an update request and sending it on (def: 5min)
     $CFG['LTI']['queuedelay'] = (# of minutes);
  
  Authcode to pass in query string if calling as scheduled web service;
  Call processltiqueue.php?authcode=thiscode
     $CFG['LTI']['authcode'] = "thecode";
*/

require("../init_without_validate.php");

if (php_sapi_name() == "cli") { 
	//running command line - no need for auth code
} else if (!isset($CFG['LTI']['authcode'])) {
	echo 'You need to set $CFG[\'LTI\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['LTI']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

//limit run to not run longer than 50 sec
ini_set("max_execution_time", "50");
//since set_time_limit doesn't count time doing stream/socket calls, we'll
//measure execution time ourselves too
$scriptStartTime = time();

//if called via AWS SNS, we need to return an OK quickly so it won't retry
if (isset($_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE'])) {
	require("../includes/AWSSNSutil.php");
	respondOK();
}

require_once('../includes/ltioutcomes.php');

/*
  table imas_ltiqueue
     hash, sourcedid, grade, sendon
     index sendon
     
  Pull all the items from the queue with sendon < now
*/

//we'll call this when send is successful
$delfromqueue = $DBH->prepare('DELETE FROM imas_ltiqueue WHERE hash=? AND sendon=?');
//on call failure, we'll update failure count and push back sendon
$setfailed = $DBH->prepare('UPDATE imas_ltiqueue SET sendon=sendon+(failures+1)*600,failures=failures+1 WHERE hash=?');

//pull all lti queue items ready to send; we'll process until we're done or timeout
$stm = $DBH->prepare('SELECT * FROM imas_ltiqueue WHERE sendon<? AND failures<3 ORDER BY sendon');
$stm->execute(array(time()));
$LTIsecrets = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	//echo "reading record ".$row['hash'].'<br/>';
	list($lti_sourcedid,$ltiurl,$ltikey,$keytype) = explode(':|:', $row['sourcedid']);
	$secret = '';
	if (strlen($lti_sourcedid)>1 && strlen($ltiurl)>1 && strlen($ltikey)>1) {
		if (isset($LTIsecrets[$ltikey])) {
			$secret = $LTIsecrets[$ltikey];
		} else if ($keytype=='c') {
			$keyparts = explode('_',$ltikey);
			$stm = $DBH->prepare("SELECT ltisecret FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$keyparts[1]));
			if ($stm->rowCount()>0) {
				$secret = $stm->fetchColumn(0);
				$LTIsecrets[$ltikey] = $secret;
			}
		} else {
			$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID AND (rights=11 OR rights=76 OR rights=77)");
			$stm->execute(array(':SID'=>$ltikey));
			if ($stm->rowCount()>0) {
					$secret = $stm->fetchColumn(0);
					$LTIsecrets[$ltikey] = $secret;
			}
		}
	}
	if ($secret != '') {
		$grade = min(1, max(0, $row['grade']));
		//send grade and wait for response
		list($ok, $response) = sendLTIOutcome('update',$ltikey,$secret,$ltiurl,$lti_sourcedid,$grade, true);
		if ($ok && strpos($response, 'success')!==false) {
			//echo "Processed ".$row['hash'].'<br/>';
			$delfromqueue->execute(array($row['hash'], $row['sendon']));
		} else {
			//echo "Failure on ".$row['hash'].'<br/>';
			$setfailed->execute(array($row['hash']));
		}
	}
	//stop execution if we've been running for 50 seconds
	if (time() - $scriptStartTime > 50) {
		exit;
	}
}