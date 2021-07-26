<?php
require_once(__DIR__.'/OAuth.php');
require_once(__DIR__.'/updateptsposs.php');
require_once(__DIR__.'/../lti/LTI_Grade_Update.php');

/**
 * Add a grade update to the LTI queue. This only can send updates, not deletes
 * @param string  $sourcedid  a :|: separated string that contains the lti_sourcedid
 *  as supplied by the LMS, the return URL, the key, and keytype
 * @param string  $key       a unique key for the user/item.
 *                        Assessments use "assessmentid-userid", so other
 *                        types should use something different. Max 32 char.
 * @param float  $grade     The grade to send, between 0 and 1
 * @param boolean $sendnow   true to send in the next update, false (default)
 *                          to send after the $CFG-set queuedelay.
 */
function addToLTIQueue($sourcedid, $key, $grade, $sendnow=false) {
	global $DBH, $CFG;

	$LTIdelay = 60*(isset($CFG['LTI']['queuedelay'])?$CFG['LTI']['queuedelay']:5);

	$query = 'INSERT INTO imas_ltiqueue (hash, sourcedid, grade, failures, sendon) ';
	$query .= 'VALUES (:hash, :sourcedid, :grade, 0, :sendon) ON DUPLICATE KEY UPDATE ';
	$query .= 'grade=VALUES(grade),sendon=VALUES(sendon),sourcedid=VALUES(sourcedid),failures=0 ';

	$stm = $DBH->prepare($query);
	$stm->execute(array(
		':hash' => $key,
		':sourcedid' => $sourcedid,
		':grade' => $grade,
		':sendon' => (time() + ($sendnow?0:$LTIdelay))
	));

	return ($stm->rowCount()>0);
}

$aidtotalpossible = array();
//use this if we don't know the total possible
function calcandupdateLTIgrade($sourcedid,$aid,$uid,$scores,$sendnow=false,$aidposs=-1) {
	global $DBH, $aidtotalpossible;
  if ($aidposs == -1) {
    if (isset($aidtotalpossible[$aid])) {
      $aidposs = $aidtotalpossible[$aid];
    } else {
  		$stm = $DBH->prepare("SELECT ptsposs,itemorder,defpoints FROM imas_assessments WHERE id=:id");
  		$stm->execute(array(':id'=>$aid));
  		$line = $stm->fetch(PDO::FETCH_ASSOC);
  		if ($line['ptsposs']==-1) {
  			$line['ptsposs'] = updatePointsPossible($aid, $line['itemorder'], $line['defpoints']);
  		}
  		$aidposs = $line['ptsposs'];
  	}
  }
	$allans = true;
  if (is_array($scores)) {
    // old assesses
    $total = 0;
  	for ($i =0; $i < count($scores);$i++) {
  		if ($allans && strpos($scores[$i],'-1')!==FALSE) {
  			$allans = false;
  		}
  		if (getpts($scores[$i])>0) { $total += getpts($scores[$i]);}
  	}
  } else {
    // new assesses
    $total = $scores;
  }
	$grade = min(1, max(0,$total/$aidposs));
	$grade = number_format($grade,8);
	return updateLTIgrade('update',$sourcedid,$aid,$uid,$grade,$allans||$sendnow);
}

//use this if we know the grade, or want to delete
function updateLTIgrade($action,$sourcedid,$aid,$uid,$grade=0,$sendnow=false) {
	global $CFG;

	if (isset($CFG['LTI']['logupdate']) && $action=='update') {
		$logfilename = __DIR__ . '/../admin/import/ltiupdate.log';
		if (file_exists($logfilename) && filesize($logfilename)>100000) { //restart log if over 100k
			$logFile = fopen($logfilename, "w+");
		} else {
			$logFile = fopen($logfilename, "a+");
		}
		fwrite($logFile, date("j-m-y,H:i:s",time()) . ",$aid,$uid,$grade,$sourcedid\n");
		fclose($logFile);
	}
	//if we're using the LTI message queue, and it's an update, queue it
	if (isset($CFG['LTI']['usequeue']) && $action=='update') {
		return addToLTIQueue($sourcedid, $aid.'-'.$uid, $grade, $sendnow);
	}

  $sourcedidparts = explode(':|:',$sourcedid);
  if (substr($sourcedid,0,6) == 'LTI1.3') {
    // is an LTI 1.3 grade item
    $updater = new LTI_Grade_Update($GLOBALS['DBH']);
    $ltiparts = explode(':|:',$sourcedid);
    $token = $updater->get_access_token($ltiparts[3]);
    if ($token === false ) { return false; }
    return $updater->send_update($token,
      $ltiparts[2], // lineitemurl
      $action == 'delete' ? 0 : $grade, // score
      $ltiparts[1], // ltiuserid
      $action == 'delete' ? 'Initialized' : 'Submitted', // activityProgress
      $action == 'delete' ? 'NotReady' : 'FullyGraded' // gradingProgress
    );
  } else {
    updateLTI1p1grade($action,$sourcedid,$aid,$uid,$grade,$sendnow);
  }
}

function updateLTI1p1grade($action,$sourcedid,$aid,$uid,$grade=0,$sendnow=false) {
  global $DBH,$testsettings,$cid,$CFG,$userid;

	//otherwise, send now
	list($lti_sourcedid,$ltiurl,$ltikey,$keytype) = explode(':|:',$sourcedid);

	if (strlen($lti_sourcedid)>1 && strlen($ltiurl)>1 && strlen($ltikey)>1) {
		if (isset($_SESSION[$ltikey.'-'.$aid.'-secret'])) {
			$secret = $_SESSION[$ltikey.'-'.$aid.'-secret'];
		} else {
			if ($keytype=='a') {
				if (isset($testsettings) && isset($testsettings['ltisecret'])) {
					$secret = $testsettings['ltisecret'];
				} else {
					$stm = $DBH->prepare("SELECT ltisecret FROM imas_assessments WHERE id=:id");
					$stm->execute(array(':id'=>$aid));
					if ($stm->rowCount()>0) {
						$secret = $stm->fetchColumn(0);
						$_SESSION[$ltikey.'-'.$aid.'-secret'] = $secret;
					} else {
						$secret = '';
					}
				}
			} else if ($keytype=='c') {
				/*if (!isset($testsettings)) {
					$qr = "SELECT ltisecret FROM imas_courses WHERE id='$cid'"; //if from gb-viewasid
				} else {
					$qr = "SELECT ltisecret FROM imas_courses WHERE id='{$testsettings['courseid']}'";
				}*/
				//change to use launched key rather than key from course in case someone uses material
				//from multiple imathas courses in one LMS course.
				$keyparts = explode('_',$ltikey);
				$stm = $DBH->prepare("SELECT ltisecret FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$keyparts[1]));
				if ($stm->rowCount()>0) {
					$secret = $stm->fetchColumn(0);
					$_SESSION[$ltikey.'-'.$aid.'-secret'] = $secret;
				} else {
					$secret = '';
				}
			} else {
				if (isset($_SESSION['lti_origkey'])) {
					$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID AND (rights=11 OR rights=76 OR rights=77)");
					$stm->execute(array(':SID'=>$_SESSION['lti_origkey']));
				} else {
					$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID AND (rights=11 OR rights=76 OR rights=77)");
					$stm->execute(array(':SID'=>$ltikey));
				}
				if ($stm->rowCount()>0) {
					$secret = $stm->fetchColumn(0);
					$_SESSION[$ltikey.'-'.$aid.'-secret'] = $secret;
				} else {
					$secret = '';
				}
			}
		}
		if ($secret != '') {
			if ($action=='update') {
				$grade = min(1, max(0,$grade));
				return sendLTIOutcome('update',$ltikey,$secret,$ltiurl,$lti_sourcedid,$grade);
			} else if ($action=='delete') {
				return sendLTIOutcome('delete',$ltikey,$secret,$ltiurl,$lti_sourcedid);
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}


// code is adapted from IMS-DEV sample code
// on code.google.com/p/ims-dev

/*
function sendOAuthBodyPOST($method, $endpoint, $oauth_consumer_key, $oauth_consumer_secret, $content_type, $body)
{
    $hash = base64_encode(sha1($body, TRUE));

    $parms = array('oauth_body_hash' => $hash);

    $test_token = '';
    $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
    $test_consumer = new OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL,11);

    $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, $method, $endpoint, $parms);
    $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

    // Pass this back up "out of band" for debugging
    global $LastOAuthBodyBaseString;
    $LastOAuthBodyBaseString = $acc_req->get_signature_base_string();
    // echo($LastOAuthBodyBaseString."\m");

    $header = $acc_req->to_header();
    $header = $header . "\r\nContent-type: " . $content_type . "\r\n";

    $params = array('http' => array(
        'method' => 'POST',
        'content' => $body,
        'header' => $header
        ));
    $ctx = stream_context_create($params);
    $fp = @fopen($endpoint, 'rb', false, $ctx);
    if (!$fp) {
    	echo "Error setting score in LMS (can't connect)";
    	return false;
        //throw new Exception("Problem with $endpoint, $php_errormsg");
    }
    $response = @stream_get_contents($fp);
    if ($response === false) {
    	echo "Error setting score in LMS";
    	return false;
        //throw new Exception("Problem reading data from $endpoint, $php_errormsg");
    }
    return $response;
}
*/
// From: http://php.net/manual/en/function.file-get-contents.php
function post_socket_xml($endpoint, $data, $moreheaders=false) {
    $url = parse_url($endpoint);

    if (!isset($url['port'])) {
      if ($url['scheme'] == 'http') { $url['port']=80; }
      elseif ($url['scheme'] == 'https') { $url['port']=443; }
    }

    $url['query']=isset($url['query'])?$url['query']:'';

    $hostport = ':'.$url['port'];
    if ($url['scheme'] == 'http' && $hostport == ':80' ) $hostport = '';
    if ($url['scheme'] == 'https' && $hostport == ':443' ) $hostport = '';

    $url['protocol']=$url['scheme'].'://';
    $eol="\r\n";

  $uri = "/";
  if ( isset($url['path'])) $uri = $url['path'];
  if ( strlen($url['query']) > 0 ) $uri .= '?'.$url['query'];
  if ( strlen($url['fragment']) > 0 ) $uri .= '#'.$url['fragment'];

    $headers =  "POST ".$uri." HTTP/1.0".$eol.
                "Host: ".$url['host'].$hostport.$eol.
                "Referer: ".$url['protocol'].$url['host'].$url['path'].$eol.
                "Content-Length: ".strlen($data).$eol;
  if ( is_string($moreheaders) ) $headers .= $moreheaders;
  $len = strlen($headers);
  if ( substr($headers,$len-2) != $eol ) {
        $headers .= $eol;
  }
    $headers .= $eol.$data;
  // echo("\n"); echo($headers); echo("\n");
    // echo("PORT=".$url['port']);
    try {
      $fp = fsockopen((($url['scheme'] == 'https') ? 'ssl://':'').$url['host'], $url['port'], $errno, $errstr, 30);
      if($fp) {
        fputs($fp, $headers);
        $result = '';
        while(!feof($fp)) { $result .= fgets($fp, 128); }
        fclose($fp);
        //removes headers
        $pattern="/^.*\r\n\r\n/s";
        $result=preg_replace($pattern,'',$result);
        return $result;
      }
  } catch(Exception $e) {
    return false;
  }
  return false;
}

function sendOAuthBodyPOST($method, $endpoint, $oauth_consumer_key, $oauth_consumer_secret, $content_type, $body, $checkResponse=false)
{
    $hash = base64_encode(sha1($body, TRUE));

    $parms = array('oauth_body_hash' => $hash);

    $test_token = '';
    $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
    $test_consumer = new OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL, 11);

    $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, $method, $endpoint, $parms);
    $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

    // Pass this back up "out of band" for debugging
    global $LastOAuthBodyBaseString;
    $LastOAuthBodyBaseString = $acc_req->get_signature_base_string();
    // echo($LastOAuthBodyBaseString."\n");

    $header = $acc_req->to_header();
    $header = $header . "\r\nContent-Type: " . $content_type . "\r\n";

    if ($checkResponse) { //use a send that waits for response
    	return newXMLoverPost($endpoint, $body, $header, $method);
    }
    //try to spawn a curl call so we don't have to wait for a response
    $disabled = explode(', ', ini_get('disable_functions'));
    if (function_exists('exec') && !in_array('exec', $disabled)) {
	    try {
		$cmd = "curl -X POST";
		$headers = explode("\r\n",$header);
		foreach ($headers as $hdr) {
			if (strlen($hdr)<2) {continue;}
			//$cmd .= " -H '".str_replace("'","\\'",$hdr)."'";
			$cmd .= " -H " . escapeshellarg($hdr);
		}
		//$cmd .= " -d '" . str_replace("'","\\'",$body) . "' " . "'" . str_replace("'","\\'",$endpoint) . "'";
		$cmd .= " -d " . escapeshellarg($body) . ' ' . escapeshellarg($endpoint);
		$cmd .= " > /dev/null 2>&1 &";
		@exec($cmd, $output, $exit);
		return ($exit == 0);
	    } catch (Exception $e) {
		//continue below
	    }
    }

    //try other methods
    $response = post_socket_xml($endpoint,$body,$header);
    if ( $response !== false && strlen($response) > 0) return $response;

    $params = array('http' => array(
        'method' => 'POST',
        'content' => $body,
        'header' => $header
        ));

    $ctx = stream_context_create($params);
  try {
    $fp = @fopen($endpoint, 'r', false, $ctx);
    } catch (Exception $e) {
        $fp = false;
    }
    if ($fp) {
        $response = @stream_get_contents($fp);
    } else {  // Try CURL
        $headers = explode("\r\n",$header);
        $response = sendXmlOverPost($endpoint, $body, $headers);
    }

    if ($response === false) {
    	if ($_SESSION['debugmode']==true) {
    		throw new Exception("Problem reading data from $endpoint, $php_errormsg");
    	} else {
    		//echo "Unable to update score via LTI.";
    	}
    }
    return $response;
}

function sendXmlOverPost($url, $xml, $header) {
  if ( ! function_exists('curl_init') ) return false;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);

  // For xml, change the content-type.
  curl_setopt ($ch, CURLOPT_HTTPHEADER, $header);

  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ask for results to be returned
/*
  if(CurlHelper::checkHttpsURL($url)) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  }
*/

  // Send to remote and return data to caller.
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}

function newXMLoverPost($url, $request, $requestHeaders, $method = 'POST') {
	//From https://github.com/IMSGlobal/LTI-Tool-Provider-Library-PHP/blob/master/src/HTTPMessage.php
	//Stephen P Vickers <svickers@imsglobal.org> copyright IMS Global Learning Consortium Inc
	//License: http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0

	$ok = false; $resp = '';

	// Try using curl if available
	if (function_exists('curl_init')) {
		$resp = '';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		$requestHeaders = explode("\r\n", trim($requestHeaders));
		if (!empty($requestHeaders)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		} else {
			curl_setopt($ch, CURLOPT_HEADER, 0);
		}
		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		} else if ($method !== 'GET') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			if (!is_null($request)) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
			}
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); //time to wait to connect
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); //time to wait for response
		$chResp = curl_exec($ch);

		$ok = $chResp !== false;
		if ($ok) {
			$chResp = str_replace("\r\n", "\n", $chResp);
			$chRespSplit = explode("\n\n", $chResp, 2);
			if ((count($chRespSplit) > 1) && (substr($chRespSplit[1], 0, 5) === 'HTTP/')) {
				$chRespSplit = explode("\n\n", $chRespSplit[1], 2);
			}
			$responseHeaders = $chRespSplit[0];
			$resp = $chRespSplit[1];
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$ok = $status < 400;
			/*if (!$ok) {
				echo "error: ";
				echo htmlentities(curl_error());
			}*/
		} else {
      if (curl_errno($ch)) {
        $resp = curl_error($ch);
      }
    }
		curl_close($ch);
	} else {
		// Try using fopen if curl was not available
		$opts = array('method' => $method,
				'content' => $request,
				'timeout' => 10
					 );
		if (!empty($requestHeaders)) {
			$opts['header'] = $requestHeaders;
		}
		try {
			$ctx = stream_context_create(array('http' => $opts));
			$fp = @fopen($url, 'rb', false, $ctx);
			if ($fp) {
				$resp = @stream_get_contents($fp);
				$ok = $resp !== false;
			}
		} catch (\Exception $e) {
			$ok = false;
		}
	}
	//echo "Response: ".htmlentities($resp);
	return array($ok,$resp);
}

if (!function_exists("getpts")) {
	function getpts($sc) {
		if (strpos($sc,'~')===false) {
			if ($sc>0) {
				return $sc;
			} else {
				return 0;
			}
		} else {
			$sc = explode('~',$sc);
			$tot = 0;
			foreach ($sc as $s) {
				if ($s>0) {
					$tot+=$s;
				}
			}
			return round($tot,1);
		}
	}
}


function sendLTIOutcome($action,$key,$secret,$url,$sourcedid,$grade=0,$checkResponse=false) {

	$method="POST";
	$content_type = "application/xml";

	$body = '<?xml version = "1.0" encoding = "UTF-8"?>
	<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
		<imsx_POXHeader>
			<imsx_POXRequestHeaderInfo>
				<imsx_version>V1.0</imsx_version>
				<imsx_messageIdentifier>MESSAGE</imsx_messageIdentifier>
			</imsx_POXRequestHeaderInfo>
		</imsx_POXHeader>
		<imsx_POXBody>
			<OPERATION>
				<resultRecord>
					<sourcedGUID>
						<sourcedId>SOURCEDID</sourcedId>
					</sourcedGUID>
					<result>
						<resultScore>
							<language>en-us</language>
							<textString>GRADE</textString>
						</resultScore>
					</result>
				</resultRecord>
			</OPERATION>
		</imsx_POXBody>
	</imsx_POXEnvelopeRequest>';

	$shortBody = '<?xml version = "1.0" encoding = "UTF-8"?>
	<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
		<imsx_POXHeader>
			<imsx_POXRequestHeaderInfo>
				<imsx_version>V1.0</imsx_version>
				<imsx_messageIdentifier>MESSAGE</imsx_messageIdentifier>
			</imsx_POXRequestHeaderInfo>
		</imsx_POXHeader>
		<imsx_POXBody>
			<OPERATION>
				<resultRecord>
					<sourcedGUID>
						<sourcedId>SOURCEDID</sourcedId>
					</sourcedGUID>
				</resultRecord>
			</OPERATION>
		</imsx_POXBody>
	</imsx_POXEnvelopeRequest>';

	if ($action=='update') {
	    $operation = 'replaceResultRequest';
	    $postBody = str_replace(
		array('SOURCEDID', 'GRADE', 'OPERATION','MESSAGE'),
		array($sourcedid, $grade, $operation, uniqid()),
		$body);
	} else if ($action=='read') {
	    $operation = 'readResultRequest';
	    $postBody = str_replace(
		array('SOURCEDID', 'OPERATION','MESSAGE'),
		array($sourcedid, $operation, uniqid()),
		$shortBody);
	} else if ($action=='delete') {
	    $operation = 'deleteResultRequest';
	    $postBody = str_replace(
		array('SOURCEDID', 'OPERATION','MESSAGE'),
		array($sourcedid, $operation, uniqid()),
		$shortBody);
	} else {
	    return false;
	}

	$response = sendOAuthBodyPOST($method, $url, $key, $secret, $content_type, $postBody, $checkResponse);
	return $response;
}


function prepLTIOutcomePost($action,$key,$secret,$url,$sourcedid,$grade=0) {
	$body = '<?xml version = "1.0" encoding = "UTF-8"?>
	<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
		<imsx_POXHeader>
			<imsx_POXRequestHeaderInfo>
				<imsx_version>V1.0</imsx_version>
				<imsx_messageIdentifier>MESSAGE</imsx_messageIdentifier>
			</imsx_POXRequestHeaderInfo>
		</imsx_POXHeader>
		<imsx_POXBody>
			<OPERATION>
				<resultRecord>
					<sourcedGUID>
						<sourcedId>SOURCEDID</sourcedId>
					</sourcedGUID>
					<result>
						<resultScore>
							<language>en-us</language>
							<textString>GRADE</textString>
						</resultScore>
					</result>
				</resultRecord>
			</OPERATION>
		</imsx_POXBody>
	</imsx_POXEnvelopeRequest>';

	$shortBody = '<?xml version = "1.0" encoding = "UTF-8"?>
	<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
		<imsx_POXHeader>
			<imsx_POXRequestHeaderInfo>
				<imsx_version>V1.0</imsx_version>
				<imsx_messageIdentifier>MESSAGE</imsx_messageIdentifier>
			</imsx_POXRequestHeaderInfo>
		</imsx_POXHeader>
		<imsx_POXBody>
			<OPERATION>
				<resultRecord>
					<sourcedGUID>
						<sourcedId>SOURCEDID</sourcedId>
					</sourcedGUID>
				</resultRecord>
			</OPERATION>
		</imsx_POXBody>
	</imsx_POXEnvelopeRequest>';

	if ($action=='update') {
	    $operation = 'replaceResultRequest';
	    $postBody = str_replace(
		array('SOURCEDID', 'GRADE', 'OPERATION','MESSAGE'),
		array($sourcedid, $grade, $operation, uniqid()),
		$body);
	} else if ($action=='read') {
	    $operation = 'readResultRequest';
	    $postBody = str_replace(
		array('SOURCEDID', 'OPERATION','MESSAGE'),
		array($sourcedid, $operation, uniqid()),
		$shortBody);
	} else if ($action=='delete') {
	    $operation = 'deleteResultRequest';
	    $postBody = str_replace(
		array('SOURCEDID', 'OPERATION','MESSAGE'),
		array($sourcedid, $operation, uniqid()),
		$shortBody);
	} else {
	    return false;
	}

	$hash = base64_encode(sha1($postBody, TRUE));

    $parms = array('oauth_body_hash' => $hash);

    $test_token = '';
    $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
    $test_consumer = new OAuthConsumer($key, $secret, NULL, 11);

    $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, "POST", $url, $parms);
    $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

    // Pass this back up "out of band" for debugging
    global $LastOAuthBodyBaseString;
    $LastOAuthBodyBaseString = $acc_req->get_signature_base_string();
    // echo($LastOAuthBodyBaseString."\n");

    $header = array($acc_req->to_header());
    $header[] = "Content-Type: application/xml";

    return array('body'=>$postBody, 'header'=>$header);
}



?>
