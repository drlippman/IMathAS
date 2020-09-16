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

  The number of simultaneous curl calls to make (def: 10)
  	 $CFG['LTI']['queuebatch'] = (# of calls);

  Authcode to pass in query string if calling as scheduled web service;
  Call processltiqueue.php?authcode=thiscode
     $CFG['LTI']['authcode'] = "thecode";

  To log results in /admin/import/ltiqueue.log:
     $CFG['LTI']['logltiqueue'] = true;
*/

require("../init_without_validate.php");
require("../includes/rollingcurl.php");
require_once('../includes/ltioutcomes.php');

if (php_sapi_name() == "cli") {
	//running command line - no need for auth code
} else if (!isset($CFG['LTI']['authcode'])) {
	echo 'You need to set $CFG[\'LTI\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['LTI']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

//limit run to not run longer than 55 sec
ini_set("max_execution_time", "55");
//since set_time_limit doesn't count time doing stream/socket calls, we'll
//measure execution time ourselves too
$scriptStartTime = time();

//if called via AWS SNS, we need to return an OK quickly so it won't retry
if (isset($_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE'])) {
	require("../includes/AWSSNSutil.php");
	respondOK();
}



/*
  table imas_ltiqueue
     hash, sourcedid, grade, sendon
     index sendon

  Pull all the items from the queue with sendon < now
*/

$batchsize = isset($CFG['LTI']['queuebatch'])?$CFG['LTI']['queuebatch']:10;
$RCX = new RollingCurlX($batchsize);
$RCX->setTimeout(5000); //5 second timeout on each request
$RCX->setStopAddingTime(45); //stop adding new request after 45 seconds
$RCX->setCallback('LTIqueueCallback'); //callback after response
$RCX->setPostdataCallback('LTIqueuePostdataCallback'); //pre-send callback
//  sometimes needed on localhost
if (strpos($_SERVER['HTTP_HOST'],'localhost')!==false) {
	$RCX->setOptions(array(
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_SSL_VERIFYHOST => 0
	));
}

//pull all lti queue items ready to send; we'll process until we're done or timeout
$stm = $DBH->prepare('SELECT * FROM imas_ltiqueue WHERE sendon<? AND failures<7 ORDER BY sendon');
$stm->execute(array(time()));
$LTIsecrets = array();
$cntsuccess = 0;
$cntfailure = 0;
$cntgiveup = 0;
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	//echo "reading record ".$row['hash'].'<br/>';
	list($lti_sourcedid,$ltiurl,$ltikey,$keytype) = explode(':|:', $row['sourcedid']);
	$secret = '';
	if (strlen($lti_sourcedid)>1 && strlen($ltiurl)>1 && strlen($ltikey)>1) {
		$grade = min(1, max(0, $row['grade']));
		$RCX->addRequest(
			$ltiurl,  //url to request
			array( 		//post data; will get transformed before send
				'action' => 'update',
				'key' => $ltikey,
				'keytype' => $keytype,
				'url' => $ltiurl,
				'sourcedid' => $lti_sourcedid,
				'grade' => $grade
			),
			null, //no special callback
			array( 	  //user-data; will get passed to response
				'hash' => $row['hash'],
				'sendon' => $row['sendon'],
				'lasttry' => ($row['failures']>=6)
			)
		);
	}
}

$deletequeue = array();
$RCX->execute();
if (count($deletequeue) > 0) {
    LTIDeleteQueue();
}
echo "Done in ".(time() - $scriptStartTime);

if (!empty($CFG['LTI']['logltiqueue'])) {
	$logfilename = __DIR__ . '/import/ltiqueue.log';
	if (file_exists($logfilename) && filesize($logfilename)>100000) { //restart log if over 100k
		$logFile = fopen($logfilename, "w+");
	} else {
		$logFile = fopen($logfilename, "a+");
	}
	$timespent = time() - $scriptStartTime;
	fwrite($logFile, date("j-m-y,H:i:s",time()). ". $cntsuccess succ, $cntfailure fail, $cntgiveup giveup, in $timespent\n");
	fclose($logFile);
}

function LTIqueuePostdataCallback($data) {
	global $DBH, $LTIsecrets;

	$secret = '';
	if (isset($LTIsecrets[$data['key']])) {
		$secret = $LTIsecrets[$data['key']];
	} else if ($data['keytype']=='c') {
		$keyparts = explode('_',$data['key']);
		$stm = $DBH->prepare("SELECT ltisecret FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$keyparts[1]));
		if ($stm->rowCount()>0) {
			$secret = $stm->fetchColumn(0);
			$LTIsecrets[$data['key']] = $secret;
		}
	} else {
		$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID AND (rights=11 OR rights=76 OR rights=77)");
		$stm->execute(array(':SID'=>$data['key']));
		if ($stm->rowCount()>0) {
				$secret = $stm->fetchColumn(0);
				$LTIsecrets[$data['key']] = $secret;
		}
	}
	//echo 'prepping w grade '.$data['grade'].'<br/>';
	return prepLTIOutcomePost(
		$data['action'],
		$data['key'],
		$secret,
		$data['url'],
		$data['sourcedid'],
		$data['grade']
	);
}

function LTIqueueCallback($response, $url, $request_info, $user_data, $time) {
	global $DBH,$cntsuccess,$cntfailure,$cntgiveup,$deletequeue;

	//echo 'got response with hash'.$user_data['hash'].'<br/>';
	//echo htmlentities($response);
	//var_dump($request_info);
	if ($response === false || strpos($response, 'success')===false) { //failed
		//on call failure, we'll update failure count and push back sendon
		$setfailed = $DBH->prepare('UPDATE imas_ltiqueue SET sendon=sendon+(failures+1)*(failures+1)*300,failures=failures+1 WHERE hash=?');
		$setfailed->execute(array($user_data['hash']));
		if ($user_data['lasttry']===true) {
			$cntgiveup++;
			// Get the LTI request data for debugging.
			$post_data = $request_info['post_data'];
			// Don't log LTI secrets!
			unset($post_data['key']);
			unset($post_data['keytype']);
			
			error_log("LTI update giving up:\n"
			. "POST data\n"
			. "---------\n"
			. print_r($post_data, true) . "\n"
			. "---------\n"
			. "user_data \n"
			. "---------\n"
			. print_r($user_data, true) . "\n"
			. "-----------------\n"
			. "Response metadata\n"
			. "-----------------\n"
			. "http_code: " . $request_info['http_code'] . "\n"
			. "content_type: " . $request_info['content_type'] . "\n"
			. "ssl_verify_result: " . $request_info['ssl_verify_result'] . "\n"
			. "total_time: " . $request_info['total_time'] . "\n"
			. "redirect_url: " . $request_info['redirect_url'] . "\n"
			. "--------\n"
			. "Response \n"
			. "--------\n"
			. $response);
		} else {
			$cntfailure++;
		}
	} else { //success
		//we'll call this when send is successful
		//$delfromqueue = $DBH->prepare('DELETE FROM imas_ltiqueue WHERE hash=? AND sendon=?');
        //$delfromqueue->execute(array($user_data['hash'], $user_data['sendon']));
        $deletequeue[] = $user_data['hash'];
        $deletequeue[] = $user_data['sendon'];
        if (count($deletequeue)>100) {
            LTIDeleteQueue();
        }
		$cntsuccess++;
	}
}

function LTIDeleteQueue() {
    global $DBH,$deletequeue;

    $ph = Sanitize::generateQueryPlaceholdersGrouped($deletequeue, 2);
    $delfromqueue = $DBH->prepare("DELETE FROM imas_ltiqueue WHERE (hash,sendon) IN ($ph)");
    $delfromqueue->execute($deletequeue);
    $deletequeue = [];
}
