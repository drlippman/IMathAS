<?php

include("../init_without_validate.php");
include('AssessInfo.php');

$t = new AssessInfo($DBH, 527357, 'all');

echo '<pre>';

$t->dumpSettings();

$a = $t->assignQuestionsAndSeeds();
print_r($a);

$b = $t->assignQuestionsAndSeeds(false, 1, $a[0], $a[1]);
print_r($b);

echo '</pre>';
