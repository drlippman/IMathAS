<?php

require_once "../init.php";

header('Content-Type: application/json');

if ($myrights<=10) {
	echo "[]";
	exit;
} else {
	$stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>$userid));

	$jsondata = json_decode($stm->fetchColumn(0), true);
	if ($jsondata===null) {
		$jsondata = array();
	}
	if (isset($jsondata['snippets'])) {
		$snippets = $jsondata['snippets'];
	} else {
		$snippets = array();
	}
	echo json_encode($snippets, JSON_INVALID_UTF8_IGNORE);
}
