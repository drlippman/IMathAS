<?php

namespace app\components;


use app\models\Assessments;
use app\models\Exceptions;
use app\models\Questions;
use app\models\Course;
use Yii;
use yii\base\Component;
use yii\web\User;

class LtiOutcomesUtility extends Component
{

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

public  static function sendOAuthBodyPOST($method, $endpoint, $oauth_consumer_key, $oauth_consumer_secret, $content_type, $body)
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
                $cmd .= " -H '$hdr'";
            }
            $cmd .= " -d '" . str_replace("'","\\'",$body) . "' " . "'" . $endpoint . "'";
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
//use this if we don't know the total possible
public static function calcandupdateLTIgrade($sourcedid,$aid,$scores) {
    global $aidtotalpossible;
    if (!isset($aidtotalpossible[$aid])) {
        $assessment = Assessments::getByAssessmentId($aid);
        $aitems = explode(',',$assessment['itemorder']);
        $defpoints = $assessment['defpoints'];
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
        $questions = Questions::getByAssessmentId($aid);
        $totalpossible = 0;
        foreach($questions as $question){
            if (($k=array_search($question['id'],$aitems))!==false) { //only use first item from grouped questions for total pts
                if ($question['points']==9999) {
                    $totalpossible += $aitemcnt[$k]*$defpoints; //use defpoints
                } else {
                    $totalpossible += $aitemcnt[$k]*$question['points']; //use points from question
                }
            }
        }
        $aidtotalpossible[$aid] = $totalpossible;
    }
    $total = 0;
    for ($i =0; $i < count($scores);$i++) {
        if (getpts($scores[$i])>0) { $total += getpts($scores[$i]);}
    }
    $grade = number_format($total/$aidtotalpossible[$aid],4);
    return LtiOutcomesUtility::updateLTIgrade('update',$sourcedid,$aid,$grade);
}

//use this if we know the grade, or want to delete
public static function updateLTIgrade($action,$sourcedid,$aid,$grade=0) {
    global $sessionData,$testsettings,$sessionId;
    list($lti_sourcedid,$ltiurl,$ltikey,$keytype) = explode(':|:',$sourcedid);
    if (strlen($lti_sourcedid)>1 && strlen($ltiurl)>1 && strlen($ltikey)>1) {
        if (isset($sessionData[$ltikey.'-'.$aid.'-secret'])) {
            $secret = $sessionData[$ltikey.'-'.$aid.'-secret'];
        } else {
            if ($keytype=='a') {
                if (isset($testsettings) && isset($testsettings['ltisecret'])) {
                    $secret = $testsettings['ltisecret'];
                } else {
                    $qr = Assessments::getByAssessmentId($aid);
                    if ($qr) {
                        $secret = $qr['ltisecret'];
                        $sessionData[$ltikey.'-'.$aid.'-secret'] = $secret;
                        AppUtility::writesessiondata($sessionData,$sessionId);
                    } else {
                        $secret = '';
                    }
                }
            } else if ($keytype=='c') {
                $keyparts = explode('_',$ltikey);
                $qr = Course::getById(intval($keyparts[1]));
                if ($qr) {
                    $secret = $qr['ltisecret'];
                    $sessionData[$ltikey.'-'.$aid.'-secret'] = $secret;
                    AppUtility::writesessiondata($sessionData,$sessionId);
                } else {
                    $secret = '';
                }
            } else {
                if (isset($sessionData['lti_origkey'])) {
                    $qr = \app\models\User::getPasswordFromLtiUser($sessionData['lti_origkey']);
                } else {
                    $qr = \app\models\User::getPasswordFromLtiUser($ltikey);
                }
                $res= mysql_query($qr) or die("Query failed : $qr" . mysql_error());
                if ($qr) {
                    $secret = $qr['password'];
                    $sessionData[$ltikey.'-'.$aid.'-secret'] = $secret;
                    AppUtility::writesessiondata($sessionData,$sessionId);
                } else {
                    $secret = '';
                }
            }
        }
        if ($secret != '') {
            if ($action=='update') {
                return LtiOutcomesUtility::sendLTIOutcome('update',$ltikey,$secret,$ltiurl,$lti_sourcedid,$grade);
            } else if ($action=='delete') {
                return LtiOutcomesUtility::sendLTIOutcome('delete',$ltikey,$secret,$ltiurl,$lti_sourcedid);
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

public static function sendLTIOutcome($action,$key,$secret,$url,$sourcedid,$grade=0) {

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

    $response = LtiOutcomesUtility::sendOAuthBodyPOST($method, $url, $key, $secret, $content_type, $postBody);
    return $response;
}



}
