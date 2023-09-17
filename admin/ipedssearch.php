<?php

require_once '../init_without_validate.php';
header('Content-Type: application/json; charset=utf-8');
$skip = ['high','middle','junior','elementary','school'];
$out = array();
if (isset($_POST['search'])) {
  $words = array_map('trim', explode(' ', Sanitize::stripHtmlTags($_POST['search'])));
  $qarr = array();
  $searchors = array();
  $wholewords = array();
  $wordands = array();
  $zip = '';
  $state = '';
  foreach ($words as $k=>$v) {
      $lower = strtolower($v);
    if (in_array($lower, $skip)) {
        continue; // skip un-useful words
    } else if (ctype_digit($v) && strlen($v)==5) { //zip
      $zip = $v;
    } else if (ctype_alnum($v) && strlen($v)>3) {
      $wholewords[] = '+'.$v.'*';
    }
  }
  if (empty($zip) && empty($wholewords)) {
    echo '[]';
    exit;
  }
  $query = 'SELECT * FROM imas_ipeds WHERE (';
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
  if (!empty($state)) {
    if (count($qarr)>0) {
      $query .= 'OR ';
    }
    $query .= 'state=?';
    $qarr[] = $state;
  }
  $query .= ')';
  if (isset($_POST['state'])) {
    $query .= " AND state=?";
    $qarr[] = $_POST['state'];
  }
  if (isset($_POST['type'])) {
      if ($_POST['type'] == 'coll') {
          $query .= " AND type='I'";
      } else if ($_POST['type'] == 'pubk12') {
        $query .= " AND type='A'";
      } else if ($_POST['type'] == 'privk12') {
        $query .= " AND type='S'";
      }
  }
  $query .= ' ORDER BY country,state,school';
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
} else if (isset($_POST['country'])) {
    $query = 'SELECT * FROM imas_ipeds WHERE country=? ';
    if (isset($_POST['type'])) {
        if ($_POST['type'] == 'coll') {
            $query .= "AND type='W' ";
        } else if ($_POST['type'] == 'pubk12' || $_POST['type'] == 'privk12') {
          $query .= "AND type='U' ";
        } 
    }
    $query .= 'ORDER BY school';
    $stm = $DBH->prepare($query);
    $stm->execute(array(Sanitize::simpleString($_POST['country'])));
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $name = $row['school'];
        $out[] = ['id'=>$row['type'].'-'.$row['ipedsid'], 'name'=>$name];
    }
}
echo json_encode($out, JSON_HEX_TAG|JSON_INVALID_UTF8_IGNORE);
