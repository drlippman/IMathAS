<?php

//IMathAS: process queue of caption data to imas_captiondata
//(c) 2025 David Lippman

//boost operation time
@set_time_limit(300);
ini_set("max_execution_time", "300");

require_once "../init_without_validate.php";
require_once "../includes/AWSSNSutil.php";

if (php_sapi_name() == "cli") { 
	//running command line - no need for auth code
} else if (!isset($CFG['video']['authcode'])) {
	echo 'You need to set $CFG[\'video\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['video']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

if (isset($_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE'])) {
	respondOK(); //send 200 response now
}

require_once('../includes/videodata.php');

$maxtopull = $CFG['video']['maxpull'] ?? 100;

$now = time();


$stm = $DBH->query("SELECT vidid FROM imas_captiondata WHERE status=0 ORDER BY lastchg LIMIT $maxtopull");
$novid = $DBH->prepare("UPDATE imas_captiondata SET status=3,lastchg=? WHERE vidid=?");

$cnt = 0;
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    // updates database after check
    $captioned = getCaptionDataByVidId($row['vidid']);
    if ($captioned === '404') {
        // video doesn't exist
        continue;
    } else if ($captioned === false) {
        // failed to pull captions; may have run out of quota. quit;
        echo "Got a failure. ";
        break;
    }
    $cnt++;
}

echo "Updated $cnt records.";