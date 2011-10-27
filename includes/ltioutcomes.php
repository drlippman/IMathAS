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
    $test_consumer = new OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL);

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

function sendLTIOutcome($action,$key,$secret,$url,$sourcedid,$grade=0) {
		
	$method="POST";
	$content_type = "application/xml";
	
	$body = '<?xml version = "1.0" encoding = "UTF-8"?>  
	<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/lis/oms1p0/pox">      
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
	<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/lis/oms1p0/pox">      
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

