<?php
/*  For testing
require_once "../init.php";
if (isset($_POST['title'])) {
	echo sendFCM(2,$_POST['title'],$_POST['body'],'');
}
?>
<form method="POST" action="testFCM.php">
title: <input name="title"><br/>
body: <input name="body"><br/>
<input type="submit">
</form>
*/
if (isset($CFG['FCM']['project_id'])) {
    require_once __DIR__ . '/JWT.php';
}

function sendFCM($token,$title,$body,$url='') {
	global $CFG;

    if (isset($CFG['FCM']['project_id'])) {
        // setup to use new api
        return sendFCM2($token,$title,$body,$url);
    } else if (!isset($CFG['FCM']['serverApiKey'])) {
        return; // don't have info even for old one
    }
    // try to use old api; will work until mid-June 2024

	if ($token != '') {

		$FCMurl = 'https://fcm.googleapis.com/fcm/send';
		$apiKey = $CFG['FCM']['serverApiKey'];


		$fields = array(
			'to'=> $token,
			'notification' => array(
				'title'=>$title,
				'body'=>$body,
				'click_action'=>$url,
				'icon'=>$CFG['FCM']['icon']
			)
		);
		
		$headers = array(
    	'Authorization:key=' . $apiKey,
    	'Content-Type:application/json'
    );
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $FCMurl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		$result = curl_exec ($ch);
		curl_close ($ch);
		return $result;
	} else {
		return 'error: no token';
	}
}

function sendFCM2($token,$title,$body,$url='') {
    global $CFG;
    
	$access_token = get_FCM_token();

	if ($access_token !== false) {
		$apiurl = 'https://fcm.googleapis.com/v1/projects/' . $CFG['FCM']['project_id'] . '/messages:send';

		$headers = [
				'Authorization: Bearer ' . $access_token,
				'Content-Type: application/json'
		];
		$message = [
			'message' => [
				'token' => $token,
				'notification' => [
					'title'=>$title,
					'body'=>$body,
				],
                'webpush' => [
                    'fcm_options' => [
                        'link' => $url
                    ]
                ],
			],
	  	];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiurl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($GLOBALS['CFG']['LTI']['skipsslverify'])) {
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
	
		$result = curl_exec($ch);
        curl_close($ch);

        if ($result !== FALSE) {
			return true;
		}
	}
	return false;
}

function get_FCM_token() {
	global $DBH;

	$row = false;
	$now = time();
	$stm = $DBH->query("SELECT token,expires FROM imas_lti_tokens WHERE platformid=0 AND scopes='FCM'");
	if ($stm !== false) {
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		if ($row !== false && $row['expires'] > $now - 10) {
			// have valid token - return it
			return $row['token'];
		}
	}

	// need to get token

    // look up key info
    $stm = $DBH->query("SELECT kid,privatekey FROM imas_lti_keys WHERE key_set_url='https://oauth2.googleapis.com/token'");
    $data = $stm->fetch(PDO::FETCH_ASSOC);
    // kid is client_email from FCM json
    // privatekey is private_key from FCM json
    if ($data === false) {
        return false; // no privatekey stored in DB
    } 

	$payload = array(
		'iss' => $data['kid'],
		'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
		'aud' => 'https://www.googleapis.com/oauth2/v4/token',
		'iat' => $now - 30,
		'exp' => $now + 3600,
		'sub' => null
	);
	$signedJWT = JWT::encode($payload, $data['privatekey'], 'RS256');

	$requestBody = array(
		'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
		'assertion' => $signedJWT
	);

	// Make request to get auth token
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v4/token');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestBody));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	if (!empty($GLOBALS['CFG']['LTI']['skipsslverify'])) {
	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}
	$resp = curl_exec($ch);
	if ($resp === false) {
		return false;
	}
	$token_data = json_decode($resp, true);
	curl_close ($ch);

	if ($token_data === null || !isset($token_data['access_token'])) {
		return $false;
	}

	$stm = $DBH->prepare("REPLACE INTO imas_lti_tokens (platformid,scopes,expires,token) VALUES (?,?,?,?)");
	$stm->execute([0, 'FCM', time() + $token_data['expires_in'] - 1, $token_data['access_token']]);

	return $token_data['access_token'];
}
?>
