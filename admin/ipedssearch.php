<?php

require('../init_without_validate.php');
header('Content-Type: application/json; charset=utf-8');
if (isset($_POST['search'])) {
  $words = array_map('trim', explode(' ', Sanitize::stripHtmlTags($_POST['search'])));
  $qarr = array();
  $searchors = array();
  $wholewords = array();
  $wordands = array();
  $zip = '';
  foreach ($words as $k=>$v) {
    if (ctype_digit($v) && strlen($v)==5) { //zip
      $zip = $v;
    } else if (ctype_alnum($v) && strlen($v)>3) {
      $wholewords[] = '+'.$v.'*';
    }
  }
  if (empty($zip) && empty($wholewords)) {
    echo '[]';
    exit;
  }
  $query = 'SELECT * FROM imas_ipeds WHERE ';
  if (count($wholewords)>0) {
    $query .= 'MATCH(school) AGAINST(? IN BOOLEAN MODE) ';
    $qarr[] = implode(' ', $wholewords);
    $query .= 'OR MATCH(agency) AGAINST(? IN BOOLEAN MODE) ';
    $qarr[] = implode(' ', $wholewords);
  }
  if (!empty($zip)) {
    if (count($qarr)>0) {
      $query .= 'OR ';
    }
    $query .= 'zip=?';
    $qarr[] = $zip;
  }
  $query .= ' ORDER BY school';
  $stm = $DBH->prepare($query);
  $stm->execute($qarr);
  $out = array();
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if ($row['type'] == 'A') {
      $name = $row['school'] . ' ('.$row['agency'].')';
    } else {
      $name = $row['school'];
    }
    if ($row['type']=='S' || $row['type']=='A') {
      $name .= ' K12';
    }
    if ($row['state'] != '') {
      $name .= ' '.$row['state'] . ', ';
    }
    $name .= $row['country'];
    $out[] = ['id'=>$row['type'].'-'.$row['ipedsid'], 'name'=>$name];
  }
  echo json_encode($out, JSON_HEX_TAG|JSON_INVALID_UTF8_IGNORE);
	exit;
}
