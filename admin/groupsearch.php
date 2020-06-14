<?php

require("../init.php");

if ($myrights < 20) {
  exit;
}

if (isset($_POST['grpsearch'])) {
	$findGroup = Sanitize::stripHtmlTags($_POST['grpsearch']);
	$words = preg_split('/\s+/', trim(preg_replace('/[^\w\s]/','',$findGroup)));
	$likearr = array();
	foreach ($words as $v) {
		$likearr[] = '%'.$v.'%';
	}
	$likes = implode(' OR ', array_fill(0, count($words), 'ig.name LIKE ?'));
	$stm = $DBH->prepare("SELECT ig.id,ig.name FROM imas_groups AS ig LEFT JOIN imas_users AS iu ON ig.id=iu.groupid WHERE $likes GROUP BY ig.id ORDER BY ig.name");
	$stm->execute($likearr);
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
