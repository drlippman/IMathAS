<?php

//IMathAS: pull question caption data to imas_captiondata
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

$now = time();

//get last ID checked
$res = $DBH->query("SELECT ver FROM imas_dbschema WHERE id=7");
$lastqsetid = $res->fetchColumn(0);

$mt = microtime(true);

// we're just going to pull and record, not try to fix any mislabeled question videos for now
$captionstore = [];
$stm = $DBH->prepare("SELECT id,extref FROM imas_questionset WHERE id>? and extref<>'' ORDER BY id LIMIT 100000");
$stm->execute([$lastqsetid]);
$cnt = 0;
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $cnt++;
    $extrefs = explode('~~', $row['extref']);
    foreach ($extrefs as $extref) {
        $pts = explode('!!', $extref);
        if (count($pts)<3) { continue; }
        $vidid = getvideoid($pts[1]);
        if ($vidid !== '') {
            // if store is 0 or undefined, define it; won't replace a 1 with a 0
            if (empty($captionstore[$vidid])) {
                $captionstore[$vidid] = intval($pts[2]);
            }
            
        }
    }
    $lastqsetid = $row['id'];
}

echo "Processed $cnt questions. ";
if ($cnt == 0) {
    exit;
}
$m2 = microtime(true);
echo 'Pull from imas_questionset time: ' . ($m2 - $mt);

$qarr = [];
foreach ($captionstore as $vidid=>$capdata) {
    array_push($qarr, $vidid, $capdata, 1, $now);
}
$ph = Sanitize::generateQueryPlaceholdersGrouped($qarr, 4);
$query = "INSERT INTO imas_captiondata (vidid, captioned, status, lastchg) VALUES $ph ";
$query .= "ON DUPLICATE KEY UPDATE status=IF(VALUES(captioned)>captioned,VALUES(status),status),";
$query .= "lastchg=IF(VALUES(captioned)>captioned,VALUES(lastchg),lastchg),";
$query .= "captioned=IF(VALUES(captioned)>captioned,VALUES(captioned),captioned)";
$stm = $DBH->prepare($query);
$stm->execute($qarr);

$m3 = microtime(true);
echo '. Update imas_questionset time: ' . ($m3 - $m2);

// update last qsetid
$stm = $DBH->prepare("UPDATE imas_dbschema SET ver=? WHERE id=7");
$stm->execute([$lastqsetid]);
echo ". Last ID processed: $lastqsetid";