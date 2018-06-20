<?php
// code is adapted from IMS-DEV sample code
// on code.google.com/p/ims-dev
require_once(__DIR__.'/OAuth.php');
require_once(__DIR__.'/updateptsposs.php');
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

function sendOAuthBodyPOST($method, $endpoint, $oauth_consumer_key, $oauth_consumer_secret, $content_type, $body)
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
    	global $sessiondata;
    	if ($sessiondata['debugmode']==true) {
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


$aidtotalpossible = array();
//use this if we don't know the total possible
function calcandupdateLTIgrade($sourcedid,$aid,$scores) {
	global $DBH, $aidtotalpossible;
	if (!isset($aidtotalpossible[$aid])) {
		//DB $query = "SELECT itemorder,defpoints FROM imas_assessments WHERE id='$aid'";
		//DB $res= mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $aitems = explode(',',mysql_result($res,0,0));
		//DB $defpoints = mysql_result($res,0,1);
		$stm = $DBH->prepare("SELECT ptsposs,itemorder,defpoints FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$aid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		if ($line['ptsposs']==-1) {
			$line['ptsposs'] = updatePointsPossible($aid, $line['itemorder'], $line['defpoints']);
		}
		$aidtotalpossible[$aid] = $line['ptsposs'];
	}
	$total = 0;
	for ($i =0; $i < count($scores);$i++) {
		if (getpts($scores[$i])>0) { $total += getpts($scores[$i]);}
	}
	$grade = min(1, max(0,$total/$aidtotalpossible[$aid]));
	$grade = number_format($grade,4);
	return updateLTIgrade('update',$sourcedid,$aid,$grade);
}

//use this if we know the grade, or want to delete
function updateLTIgrade($action,$sourcedid,$aid,$grade=0) {
	global $DBH,$sessiondata,$testsettings,$cid;

	list($lti_sourcedid,$ltiurl,$ltikey,$keytype) = explode(':|:',$sourcedid);

	if (strlen($lti_sourcedid)>1 && strlen($ltiurl)>1 && strlen($ltikey)>1) {
		if (isset($sessiondata[$ltikey.'-'.$aid.'-secret'])) {
			$secret = $sessiondata[$ltikey.'-'.$aid.'-secret'];
		} else {
			if ($keytype=='a') {
				if (isset($testsettings) && isset($testsettings['ltisecret'])) {
					$secret = $testsettings['ltisecret'];
				} else {
					//DB $query = "SELECT ltisecret FROM imas_assessments WHERE id='$aid'";
					//DB $res= mysql_query($query) or die("Query failed : $qr" . mysql_error());
					//DB if (mysql_num_rows($res)>0) {
						//DB $secret = mysql_result($res,0,0);
					$stm = $DBH->prepare("SELECT ltisecret FROM imas_assessments WHERE id=:id");
					$stm->execute(array(':id'=>$aid));
					if ($stm->rowCount()>0) {
						$secret = $stm->fetchColumn(0);
						$sessiondata[$ltikey.'-'.$aid.'-secret'] = $secret;
						writesessiondata();
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
				//DB $query = "SELECT ltisecret FROM imas_courses WHERE id=".intval($keyparts[1]);
				//DB $res= mysql_query($query) or die("Query failed : $qr" . mysql_error());
				//DB if (mysql_num_rows($res)>0) {
					//DB $secret = mysql_result($res,0,0);
				$stm = $DBH->prepare("SELECT ltisecret FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$keyparts[1]));
				if ($stm->rowCount()>0) {
					$secret = $stm->fetchColumn(0);
					$sessiondata[$ltikey.'-'.$aid.'-secret'] = $secret;
					writesessiondata();
				} else {
					$secret = '';
				}
			} else {
				if (isset($sessiondata['lti_origkey'])) {
					//DB $query = "SELECT password FROM imas_users WHERE SID='{$sessiondata['lti_origkey']}' AND (rights=11 OR rights=76 OR rights=77)";
          //DB $res= mysql_query($query) or die("Query failed : $qr" . mysql_error());
					$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID AND (rights=11 OR rights=76 OR rights=77)");
					$stm->execute(array(':SID'=>$sessiondata['lti_origkey']));
				} else {
					//DB $query = "SELECT password FROM imas_users WHERE SID='".addslashes($ltikey)."' AND (rights=11 OR rights=76 OR rights=77)";
          //DB $res= mysql_query($query) or die("Query failed : $qr" . mysql_error());
					$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID AND (rights=11 OR rights=76 OR rights=77)");
					$stm->execute(array(':SID'=>$ltikey));
				}

				//DB if (mysql_num_rows($res)>0) {
					//DB $secret = mysql_result($res,0,0);
				if ($stm->rowCount()>0) {
					$secret = $stm->fetchColumn(0);
					$sessiondata[$ltikey.'-'.$aid.'-secret'] = $secret;
					writesessiondata();
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

function sendLTIOutcome($action,$key,$secret,$url,$sourcedid,$grade=0) {

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

	$response = sendOAuthBodyPOST($method, $url, $key, $secret, $content_type, $postBody);
	return $response;
}

?>
