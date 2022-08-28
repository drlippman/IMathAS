<?php
//IMathAS: handle email bounces from SES
//(c) 2019 David Lippman

/*
  Note:  Per comment at https://stackoverflow.com/questions/15368095/php-json-decode-amazon-sns,
  in SNS leave "Include Original Headers" disabled

	Need to set $CFG['email']['authcode'] in config
*/

require("../init_without_validate.php");
require("../includes/AWSSNSutil.php");

if (php_sapi_name() == "cli") {
	//running command line - no need for auth code
} else if (!isset($CFG['email']['authcode'])) {
	echo 'You need to set $CFG[\'email\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['email']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

if (isset($_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE'])) {
	respondOK(); //send 200 response now
}

// Fetch the raw POST body containing the message
$postBody = file_get_contents('php://input');

// JSON decode the body to an array of message data
$sns = json_decode($postBody, true);

if ($sns['Type'] == 'Notification') {
	$message = json_decode($sns['Message'], true);
} else {
	exit;
}

$emails = array();
if ($message) {
	if ($message['notificationType'] == 'Bounce' &&
		($message['bounce']['bounceType'] == 'Permanent' ||
		 $message['bounce']['bounceType'] == 'Undetermined')
	) {
		foreach ($message['bounce']['bouncedRecipients'] as $bouncers) {
			if ($bouncers['action'] == 'failed') {
				$emails[] = $bouncers['emailAddress'];
			}
		}
	}
	if ($message['notificationType'] == 'Complaint') {
		foreach ($message['complaint']['complainedRecipients'] as $bouncers) {
			$emails[] = $bouncers['emailAddress'];
		}
	}
}

if (count($emails) > 0) {
	$ph = Sanitize::generateQueryPlaceholders($emails);
	$stm = $DBH->prepare("UPDATE imas_users SET email=CONCAT('BOUNCED', email) WHERE email IN ($ph)");
	$stm->execute($emails);
}

echo "Done";
