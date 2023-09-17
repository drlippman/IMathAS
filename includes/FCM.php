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
?>
