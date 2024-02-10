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

function sendFCM($token,$title,$body,$url='') {
	global $CFG;
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
	$access_token = get_FCM_token();

	if ($access_token !== false) {
		$apiurl = 'https://fcm.googleapis.com/v1/projects/your-project-id/messages:send';   //replace "your-project-id" with...your project ID

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
					'click_action'=>$url,
					'icon'=>$CFG['FCM']['icon']
				],
			],
	  	];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiurl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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
		if ($row !== false && $row['expires']<$now - 10) {
			// have valid token - return it
			return $row['token'];
		}
	}

	// need to get token

	// !!! TODO !!!
	$keyBody = []; // this is from the FCM .json

	$payload = array(
		'iss' => $keyBody['client_email'],
		'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
		'aud' => 'https://www.googleapis.com/oauth2/v4/token',
		'iat' => $now - 30,
		'exp' => $now + 3600,
		'sub' => null
	);
	$signedJWT = JWT ::encode($payload, $keyBody['private_key'], 'RS256');

	$requestBody = array(
		'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
		'assertion' => $signedJWT
	);

	// Make request to get auth token
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $this->registration->get_auth_token_url());
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($auth_request));
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
	$stm->execute([0, 'FCM', $token_data['expires_in'], $token_data['access_token']]);

	return $token_data['access_token'];
}
?>
