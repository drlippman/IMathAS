<?php
//IMathAS: handle email bounces from SES
//(c) 2019 David Lippman

/*
  Note:  Per comment at https://stackoverflow.com/questions/15368095/php-json-decode-amazon-sns,
  in SNS leave "Include Original Headers" disabled
*/

require("../init_without_validate.php");
require("../includes/AWSSNSutil.php");

if (php_sapi_name() == "cli") { 
	//running command line - no need for auth code
} else if (!isset($CFG['cleanup']['authcode'])) {
	echo 'You need to set $CFG[\'cleanup\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['cleanup']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

if (isset($_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE'])) {
	respondOK(); //send 200 response now
}

function disableEmail($email) {
	global $DBH;
	$stm = $DBH->prepare("UPDATE imas_users SET email=CONCAT('BOUNCED', email) WHERE email=?");
	$stm->execute($email);
}


// Fetch the raw POST body containing the message
$postBody = file_get_contents('php://input');

// JSON decode the body to an array of message data
$message = json_decode($postBody, true);
if ($message) {
	if ($message['notificationType'] == 'Bounce' && 
		$message['bounce']['bounceType'] == 'Permanent'
	) {
		foreach ($message['bounce']['bouncedRecipients'] as $bouncers) {
			if ($bouncers['action'] == 'failed') {
				disableEmail($bouncers['emailAddress']);
			}
		}
	}
	if ($message['notificationType'] == 'Complaint') {
		foreach ($message['complaint']['complainedRecipients'] as $bouncers) {
			disableEmail($bouncers['emailAddress']);
		}
	}
}

echo "Done";
