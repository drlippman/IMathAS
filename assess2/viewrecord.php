<?php

require_once "../init.php";
if ($myrights < 100) {
  exit;
}

echo '<pre>';
if (isset($_GET['retotal'])) {
  require_once 'AssessInfo.php';
  require_once 'AssessRecord.php';
  $aid = intval($_GET['aid']);
  $uid = intval($_GET['uid']);
  $assess_info = new AssessInfo($DBH, $aid, $cid, true);
  $assess_record = new AssessRecord($DBH, $assess_info, false);
  $assess_record->loadRecord($uid);
  $assess_record->reTotalAssess();
  $assess_record->saveRecord();
  echo "retotaled";
}
if (isset($_GET['uid']) && isset($_GET['aid'])) {
  $stm = $DBH->prepare("SELECT scoreddata,practicedata FROM imas_assessment_records WHERE userid=? AND assessmentid=? ORDER BY lastchange DESC LIMIT 1");
  $stm->execute(array($_GET['uid'], $_GET['aid']));
} else if (isset($_GET['uid'])) {
  $stm = $DBH->prepare("SELECT scoreddata,practicedata FROM imas_assessment_records WHERE userid=? ORDER BY lastchange DESC LIMIT 1");
  $stm->execute(array($_GET['uid']));
} else {
  $stm = $DBH->query("SELECT scoreddata,practicedata FROM imas_assessment_records ORDER BY lastchange DESC LIMIT 1");
}

$row = $stm->fetch(PDO::FETCH_ASSOC);
print_r(Sanitize::gzexpand($row['scoreddata']));
echo "\n";
print_r(json_decode(Sanitize::gzexpand($row['scoreddata']), true));
print_r(json_decode(Sanitize::gzexpand($row['practicedata']), true));
