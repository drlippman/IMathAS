<?php

require("../init.php");
if ($myrights < 100) {
  exit;
}

if (!isset($_GET['cid'])) {
  echo "Need to specify cid";
  exit;
}
if (!isset($_FILES['userfile'])) {
  require("../header.php");
  echo '<form id="qform" enctype="multipart/form-data" method=post action="restoreblock.php?cid='.$cid.'">';
  echo '<p>WARNING: Running this is dangerous. Only do it if you are loading a dump file from a same-ID course generated from a backup</p>';
  echo '<p>Dump file: <input name="userfile" type="file" />';
  echo '<input type=submit value="Submit"></p></form>';
  require("../footer.php");
  exit;
}

$data = json_decode(file_get_contents($_FILES['userfile']['tmp_name']), true);

function loadData($table, $fields, $valuearr) {
  global $DBH;
  $ph = Sanitize::generateQueryPlaceholders($fields);
  $stm = $DBH->prepare("INSERT IGNORE INTO $table (" .implode(',', $fields) . ") VALUES ($ph)");
  foreach ($valuearr as $values) {
    $stm->execute($values);
  }
}

$toload = ['items','assessments','questions','assessment_sessions','exceptions',
  'inlinetext','linkedtext','forums','forum_posts','forum_threads','forum_views',
  'wikis','drillassess','drillassess_sessions'];
foreach ($data as $k=>$v) {
  if (in_array($k, $toload)) {
    loadData("imas_$k", $v['fields'], $v['values']);
  }
}

$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$items = unserialize($stm->fetchColumn(0));
if (isset($data['blockobject'])) {
  $items[] = $data['blockobject'];
}
$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
$stm->execute(array(':id'=>$cid, ':itemorder'=>serialize($items)));

echo "DONE";
