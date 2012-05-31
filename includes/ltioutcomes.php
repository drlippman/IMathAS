<?php
// code is adapted from IMS-DEV sample code
// on code.google.com/p/ims-dev
require_once  'OAuth.php';

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
        throw new Exception("Problem with $endpoint, $php_errormsg");
    }
    $response = @stream_get_contents($fp);
    if ($response === false) {
        throw new Exception("Problem reading data from $endpoint, $php_errormsg");
    }
    return $response;
}

$aidtotalpossible = array();
//use this if we don't know the total possible
function calcandupdateLTIgrade($sourcedid,$aid,$scores) {
	global $aidtotalpossible;
	if (!isset($aidtotalpossible[$aid])) {
		$query = "SELECT itemorder,defpoints FROM imas_assessments WHERE id='$aid'";
		$res= mysql_query($query) or die("Query failed : $query" . mysql_error());
		$aitems = explode(',',mysql_result($res,0,0));
		$defpoints = mysql_result($res,0,1);
		foreach ($aitems as $k=>$v) {
			if (strpos($v,'~')!==FALSE) {
				$sub = explode('~',$v);
				if (strpos($sub[0],'|')===false) { //backwards compat
					$aitems[$k] = $sub[0];
					$aitemcnt[$k] = 1;
				} else {
					$grpparts = explode('|',$sub[0]);
					$aitems[$k] = $sub[1];
					$aitemcnt[$k] = $grpparts[0];
				}
			} else {
				$aitemcnt[$k] = 1;
			}
		}
		
		$query = "SELECT points,id FROM imas_questions WHERE assessmentid='$aid'";
		$result2 = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$totalpossible = 0;
		while ($r = mysql_fetch_row($result2)) {
			if (in_array($r[1],$aitems)) { //only use first item from grouped questions for total pts
				if ($r[0]==9999) {
					$totalpossible += $aitemcnt[$k]*$defpoints; //use defpoints
				} else {
					$totalpossible += $aitemcnt[$k]*$r[0]; //use points from question
				}
			}
		}
		$aidtotalpossible[$aid] = $totalpossible;
	}
	$total = 0;
	for ($i =0; $i < count($scores);$i++) {
		if (getpts($scores[$i])>0) { $total += getpts($scores[$i]);}
	}
	$grade = round($total/$aidtotalpossible[$aid],4);
	return updateLTIgrade('update',$sourcedid,$aid,$grade);
}

//use this if we know the grade, or want to delete
function updateLTIgrade($action,$sourcedid,$aid,$grade=0) {
	global $sessiondata,$testsettings;
	
	list($lti_sourcedid,$ltiurl,$ltikey,$keytype) = explode(':|:',$sourcedid);
	if (strlen($lti_sourcedid)>1 && strlen($ltiurl)>1 && strlen($ltikey)>1) {
		if (isset($sessiondata[$ltikey.'-'.$aid.'-secret'])) {
			$secret = $sessiondata[$ltikey.'-'.$aid.'-secret'];
		} else {
			if ($keytype=='a') {
				if (isset($testsettings) && isset($testsettings['ltisecret'])) {
					$secret = $testsettings['ltisecret'];
				} else {
					$query = "SELECT ltisecret FROM imas_assessments WHERE id='$aid'";
					$res= mysql_query($qr) or die("Query failed : $qr" . mysql_error());
					if (mysql_num_rows($res)>0) {
						$secret = mysql_result($res,0,0);
						$sessiondata[$ltikey.'-'.$aid.'-secret'] = $secret;
						writesessiondata();
					} else {
						$secret = '';
					}
				}
			} else if ($keytype=='c') {
				$query = "SELECT ltisecret FROM imas_courses WHERE id='{$testsettings['courseid']}'";
				$res= mysql_query($qr) or die("Query failed : $qr" . mysql_error());
				if (mysql_num_rows($res)>0) {
					$secret = mysql_result($res,0,0);
					$sessiondata[$ltikey.'-'.$aid.'-secret'] = $secret;
					writesessiondata();
				} else {
					$secret = '';
				}
			} else {
				$qr = "SELECT password FROM imas_users WHERE SID='{$sessiondata['lti_origkey']}' AND (rights=11 OR rights=76)";
				$res= mysql_query($qr) or die("Query failed : $qr" . mysql_error());
				if (mysql_num_rows($res)>0) {
					$secret = mysql_result($res,0,0);
					$sessiondata[$ltikey.'-'.$aid.'-secret'] = $secret;
					writesessiondata();
				} else {
					$secret = '';
				}
			}
		}
		if ($secret != '') {
			if ($action=='update') {
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
