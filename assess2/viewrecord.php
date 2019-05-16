<?php

require("../init.php");
if ($myrights < 100) {
  exit;
}

if (isset($_GET['uid'])) {
  $stm = $DBH->prepare("SELECT scoreddata,practicedata FROM imas_assessment_records WHERE userid=? ORDER BY lastchange DESC LIMIT 1");
  $stm->execute(array($_GET['uid']));
} else {
  $stm = $DBH->query("SELECT scoreddata,practicedata FROM imas_assessment_records ORDER BY lastchange DESC LIMIT 1");
}
echo '<pre>';
$row = $stm->fetch(PDO::FETCH_ASSOC);
print_r(gzdecode($row['scoreddata']));
echo "\n";
print_r(json_decode(gzdecode($row['scoreddata']), true));
print_r(json_decode(gzdecode($row['practicedata']), true));
