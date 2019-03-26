<?php

require("../init.php");
if ($myrights < 100) {
  exit;
}

$stm = $DBH->query("SELECT scoreddata,practicedata FROM imas_assessment_records ORDER BY lastchange DESC LIMIT 1");
echo '<pre>';
$row = $stm->fetch(PDO::FETCH_ASSOC);
print_r(json_decode(gzdecode($row['scoreddata']), true));
print_r(json_decode(gzdecode($row['practicedata']), true));
