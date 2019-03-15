<?php

require("../init.php");
if ($myrights < 100) {
  exit;
}

$stm = $DBH->query("SELECT scoreddata FROM imas_assessment_records ORDER BY lastchange DESC LIMIT 1");
echo '<pre>';
print_r(json_decode(gzdecode($stm->fetchColumn(0)), true));
