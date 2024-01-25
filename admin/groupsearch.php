<?php

require_once "../init.php";

if ($myrights < 20) {
  exit;
}

if (isset($_POST['grpsearch'])) {
	$findGroup = Sanitize::stripHtmlTags($_POST['grpsearch']);
  if (empty($_POST['exact'])) {
  	$words = preg_split('/\s+/', trim(preg_replace('/[^\w\s]/','',$findGroup)));
  	$likearr = array();
  	foreach ($words as $v) {
  		$likearr[] = '%'.$v.'%';
  	}
  	$likes = implode(' OR ', array_fill(0, count($words), 'name LIKE ?'));
  	$stm = $DBH->prepare("SELECT id,name FROM imas_groups WHERE $likes");
  	$stm->execute($likearr);
  } else {
    $stm = $DBH->prepare("SELECT id,name FROM imas_groups WHERE name=?");
    $stm->execute(array(trim($findGroup)));
    $words = array();
  }
	$possible_groups = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$row['priority'] = 0;
		foreach ($words as $v) {
			if (preg_match('/\b'.$v.'\b/i', $row['name'])) {
				$row['priority']+=2;
			} else if (preg_match('/\b'.$v.'/i', $row['name'])) {
				$row['priority']++;
			}
		}
		$possible_groups[] = $row;
	}
	//sort by priority
	usort($possible_groups, function($a,$b) {
		if ($a['priority']!=$b['priority']) {
			return $b['priority']-$a['priority'];
		} else {
			return strcmp($a['name'],$b['name']);
		}
	});
	echo json_encode($possible_groups, JSON_HEX_TAG|JSON_INVALID_UTF8_IGNORE);
	exit;
} else {
  echo [];
}
