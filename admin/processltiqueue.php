<?php
//IMathAS: process LTI message queue
//(c) 2018 David Lippman

/*
  To use the LTI queue, you'll need to either set up a cron job to call this
  script, or call it using a scheduled web call with the authcode option.
  When called in cron job, the base address like https://www.mysite.com should 
  be passed as an argument.
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
if (php_sapi_name() == "cli") {
    if (empty($argv[1])) {
        echo 'You need to provide the domain name as an argument';
        exit;
    }
    $_SERVER['HTTP_HOST'] = explode('//',$argv[1])[1];
}

require("../init_without_validate.php");
require("../includes/rollingcurl.php");
require_once('../includes/ltioutcomes.php');

function debuglog($str) {
	if (!empty($GLOBALS['CFG']['LTI']['noisydebuglog'])) {
		$fh = fopen(__DIR__.'/../lti/ltidebug.txt', 'a');
		fwrite($fh, $str."\n");
		fclose($fh);
	}
}


if (php_sapi_name() == "cli") {
    //running command line - no need for auth code
    $GLOBALS['basesiteurl'] = $argv[1] . $imasroot;
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

$updater1p3 = new LTI_Grade_Update($DBH);

$updateStart = time();
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
$stm = $DBH->prepare('SELECT * FROM imas_ltiqueue WHERE sendon<? AND failures<7 ORDER BY sendon LIMIT 2000');
$stm->execute(array(time()));
$LTIsecrets = array();
$cntsuccess = 0;
$cntfailure = 0;
$cntgiveup = 0;
$tokensQueued = [];
$round2 = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	//echo "reading record ".$row['hash'].'<br/>';
	if (substr($row['sourcedid'],0,6)=='LTI1.3') {
		// LTI 1.3 update
		list($ltiver,$ltiuserid,$score_url,$platformid) = explode(':|:', $row['sourcedid']);
		if ($updater1p3->have_token($platformid)) {
			if ($updater1p3->token_valid($platformid)) {
				debuglog('queing request with token for '.$row['hash']);
				// we have a token, so add an update request
				$pos = strpos($score_url, '?');
				$score_url = $pos === false ? $score_url . '/scores' : substr_replace($score_url, '/scores', $pos, 0);
				$RCX->addRequest(
					$score_url,  //url to request
					array( 		//post data; will get transformed before send
						'ver' => 'LTI1.3',
						'action' => 'update',
						'ltiuserid' => $ltiuserid,
						'platformid' => $platformid,
						'grade' => max(0, $row['grade']),
                        'isstu' => $row['isstu'],
                        'addedon' => $row['addedon']
					),
					null, //no special callback
					array( 	  //user-data; will get passed to response
						'hash' => $row['hash'],
						'sendon' => $row['sendon'],
						'lasttry' => ($row['failures']>=6)
					)
				);
			} else {
				$updater1p3->update_sendon($row['hash'], $platformid);
			}
		} else {
			if (!in_array($platformid, $tokensQueued)) { // only request token once per platform
				debuglog('queing token request for '.$row['hash']. ' on platform '.$platformid);
				// we need to get a token, so add a token request
				$platforminfo = $updater1p3->get_platform_info($platformid);
				$RCX->addRequest(
					$platforminfo['auth_token_url'],  //url to request
					array( 		//post data; will get transformed before send
						'ver' => 'LTI1.3',
						'action' => 'gettoken',
						'platformid' => $platformid,
						'platforminfo' => $platforminfo
					),
					null, //no special callback
					array( 	  //user-data; will get passed to response
						'action' => 'gettoken',
						'platformid' => $platformid
					)
				);
				$tokensQueued[] = $platformid;
			}
			// add original ltiqueue to round 2, to process after first round is done
			$round2[] = $row;
		}
	} else {
		// LTI 1.1 update
        $sourcedid_parts = explode(':|:', $row['sourcedid']);
        if (count($sourcedid_parts)==4) {
		    list($lti_sourcedid,$ltiurl,$ltikey,$keytype) = $sourcedid_parts;
            $secret = '';
            if (strlen($lti_sourcedid)>1 && strlen($ltiurl)>1 && strlen($ltikey)>1) {
                debuglog('queing 1.1 request for '.$row['hash']);
                $grade = min(1, max(0, $row['grade']));
                $RCX->addRequest(
                    $ltiurl,  //url to request
                    array( 		//post data; will get transformed before send
                        'ver' => 'LTI1.1',
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
	}
}

$deletequeue = array();
$RCX->execute();
if (count($deletequeue) > 0) {
    LTIDeleteQueue();
}
$timeused = time() - $updateStart;

if (count($round2)>0 &&  $timeused < 40) {
	// this is measured relative to start of exec
	$RCX->setStopAddingTime(45 - $timeused);
	// this would only be LTI1.3 updates that didn't have a token the first time
	foreach ($round2 as $row) {
		list($ltiver,$ltiuserid,$score_url,$platformid) = explode(':|:', $row['sourcedid']);
		if ($updater1p3->have_token($platformid)) {
			if ($updater1p3->token_valid($platformid)) {
				debuglog('queing round2 request for '.$row['hash']);
				// we have a token, so add an update request
				$pos = strpos($score_url, '?');
				$score_url = $pos === false ? $score_url . '/scores' : substr_replace($score_url, '/scores', $pos, 0);
				$RCX->addRequest(
					$score_url,  //url to request
					array( 		//post data; will get transformed before send
						'ver' => 'LTI1.3',
						'action' => 'update',
						'ltiuserid' => $ltiuserid,
						'platformid' => $platformid,
						'grade' => max(0, $row['grade']),
                        'isstu' => $row['isstu'],
                        'addedon' => $row['addedon']
					),
					null, //no special callback
					array( 	  //user-data; will get passed to response
						'hash' => $row['hash'],
						'sendon' => $row['sendon'],
						'lasttry' => ($row['failures']>=6)
					)
				);
			} else {
				$updater1p3->update_sendon($row['hash'], $platformid);
			}
		}
	}

    $RCX->execute();
    if (count($deletequeue) > 0) {
        LTIDeleteQueue();
    }
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
	global $DBH, $LTIsecrets, $updater1p3;

	if ($data['ver'] == 'LTI1.3') {
		// it's an LTI 1.3 request
		if ($data['action'] == 'gettoken') {
			$platforminfo = $data['platforminfo'];
			return [
				'body' => $updater1p3->get_token_request_post($data['platformid'],
										$platforminfo['client_id'],
										$platforminfo['auth_token_url'],
										$platforminfo['auth_server']
									),
				'header' => array()
			];
		} else if ($data['action'] == 'update') {
            if ($updater1p3->have_token($data['platformid']) &&
                $updater1p3->token_valid($data['platformid'])
            ) { // double check we have a valid token
				$token = $updater1p3->get_access_token($data['platformid']);
				return $updater1p3->get_update_body($token, $data['grade'], $data['ltiuserid'], $data['isstu'], $data['addedon']);
			} else {
				return false;
			}
		}
	}
	// it's an LTI 1.1 request

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
	global $DBH,$cntsuccess,$cntfailure,$cntgiveup,$updater1p3,$deletequeue;
	$post_data = $request_info['post_data'];
	$success = true;
	debuglog('callback request_info:'.json_encode($post_data));
	debuglog('callback user_data:'.json_encode($user_data));
	if ($post_data['ver'] == 'LTI1.3') {
		if ($post_data['action'] == 'gettoken') {
			// was a token request
			if ($response === false) {
				// record failure. in round 2 token will be read as not valid
				$updater1p3->token_request_failure($user_data['platformid']);
				debuglog('token request failure t1 '.$user_data['platformid']);
                return;
			}
			$token_data = json_decode($response, true);
			if (isset($token_data['access_token'])) {
				$updater1p3->store_access_token($user_data['platformid'], $token_data);
				debuglog('got token for '.$user_data['platformid']);
			} else {
                // record failure. in round 2 token will be read as not valid
				$updater1p3->token_request_failure($user_data['platformid']);
				debuglog('token request failure t2 '.$response);
			}
			return; // doesn't effect ltiqueue, so return now
		} else if ($post_data['action'] == 'update') {
			debuglog('got update response '.$response);
			if ($response === false) {
				$success = false;
			}
		}
	} else {
		// LTI 1.1
		if ($response === false || strpos($response, 'success')===false) { //failed
			$success = false;
		}
	}

	if (!$success) {
		//on call failure, we'll update failure count and push back sendon
		debuglog('update failure for '.$user_data['hash']);
		$setfailed = $DBH->prepare('UPDATE imas_ltiqueue SET sendon=sendon+(failures+1)*(failures+1)*300,failures=failures+1 WHERE hash=?');
		$setfailed->execute(array($user_data['hash']));
		if ($user_data['lasttry']===true) {
			$cntgiveup++;
			// Get the LTI request data for debugging.
			$post_data = $request_info['post_data'];
			// Don't log LTI secrets!
			unset($post_data['key']);
			unset($post_data['keytype']);

			$logdata = "LTI update giving up:\n"
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
			. $response
            . $request_info['response_text'];
            $logstm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (?,?)");
            $logstm->execute([time(), $logdata]);
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
