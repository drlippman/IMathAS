<?php
//IMathAS: process email send queue
//(c) 2025 David Lippman

/*
  To use the email queue, you'll need to either set up a cron job to call this
  script, or call it using a scheduled web call with the authcode option.
  It should be called once a day.

  Config options (in config.php):
  To enable using LTI queue:
     $CFG['email']['usequeue'] = true;

  Authcode to pass in query string if calling as scheduled web service;
  Call processltiqueue.php?authcode=thiscode
     $CFG['email']['authcode'] = "thecode";
*/

require_once "../init_without_validate.php";
require_once "../includes/email.php";

if (php_sapi_name() == "cli") {
    //running command line - no need for auth code
} else if (!isset($CFG['email']['authcode'])) {
	echo 'You need to set $CFG[\'email\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['email']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

//limit run to not run longer than 55 sec
ini_set("max_execution_time", "55");

//if called via AWS SNS, we need to return an OK quickly so it won't retry
if (isset($_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE'])) {
	require_once "../includes/AWSSNSutil.php";
	respondOK();
}

$del = $DBH->prepare('DELETE FROM imas_emailqueue WHERE email=? AND subject=?');
$stm = $DBH->prepare('SELECT * FROM imas_emailqueue WHERE sendafter<?');
$stm->execute([time()]);
$cnt = 0;
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    send_email($row['email'], $row['emailfrom'], $row['subject'], $row['message'], [], [], $row['priority'], true, 1);
    $del->execute([$row['email'], $row['subject']]);
    $cnt++;
}

echo "$cnt emails sent";
