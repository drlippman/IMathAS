<?php
//IMathAS: Excuse Assignment
//(c) 2018 David Lippman

if (!isset($imasroot)) {
	echo "This file cannot be called directly";
	exit;
}

$now = time();
if (!isset($_POST['assesschk'])) { $_POST['assesschk'] = array();}
if (!isset($_POST['offlinechk'])) { $_POST['offlinechk'] = array();}
if (!isset($_POST['discusschk'])) { $_POST['discusschk'] = array();}
if (!isset($_POST['exttoolchk'])) { $_POST['exttoolchk'] = array();}

if ($calledfrom=='gb' && $_POST['posted']==_("Excuse Grade") && $stu>0) {
	$vals = array();
	foreach($_POST['assesschk'] as $aid) {
		array_push($vals, $stu, $cid, 'A', $aid, $now);
	}
	foreach($_POST['offlinechk'] as $oid) {
		array_push($vals, $stu, $cid, 'O', $oid, $now);
	}
	foreach($_POST['discusschk'] as $fid) {
		array_push($vals, $stu, $cid, 'F', $fid, $now);
	}
	foreach($_POST['exttoolchk'] as $lid) {
		array_push($vals, $stu, $cid, 'E', $lid, $now);
	}
	if (count($vals)>0) {
		$ph = Sanitize::generateQueryPlaceholdersGrouped($vals, 5);
		$stm = $DBH->prepare("REPLACE INTO imas_excused (userid, courseid, type, typeid, dateset) VALUES $ph");
		$stm->execute($vals);
	}
} else if ($calledfrom=='gb' && $_POST['posted']==_("Un-excuse Grade") && $stu>0) {
	$delstm = $DBH->prepare("DELETE FROM imas_excused WHERE userid=? AND type=? AND typeid=?");
	foreach($_POST['assesschk'] as $aid) {
		$delstm->execute(array($stu, 'A', $aid));
	}
	foreach($_POST['offlinechk'] as $oid) {
		$delstm->execute(array($stu, 'O', $oid));
	}
	foreach($_POST['discusschk'] as $fid) {
		$delstm->execute(array($stu, 'F', $fid));
	}
	foreach($_POST['exttoolchk'] as $lid) {
		$delstm->execute(array($stu, 'E', $lid));
	}
} else if ($calledfrom=='isolateassess' && $_POST['posted']==_("Excuse Grade")) {
	$vals = array();
	foreach($_POST['stus'] as $stu) {
		array_push($vals, $stu, $cid, 'A', $aid, $now);
	}
	if (count($vals)>0) {
		$ph = Sanitize::generateQueryPlaceholdersGrouped($vals, 5);
		$stm = $DBH->prepare("REPLACE INTO imas_excused (userid, courseid, type, typeid, dateset) VALUES $ph");
		$stm->execute($vals);
	}
} else if ($calledfrom=='isolateassess' && $_POST['posted']==_("Un-excuse Grade")) {
	$delstm = $DBH->prepare("DELETE FROM imas_excused WHERE userid=? AND type=? AND typeid=?");
	foreach($_POST['stus'] as $stu) {
		$delstm->execute(array($stu, 'A', $aid));
	}
}

if ($calledfrom=='gb') {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?cid=".Sanitize::courseId($cid) . "&stu=".Sanitize::onlyInt($stu) . "&r=" . Sanitize::randomQueryStringParam());
	exit;
} else if ($calledfrom=='isolateassess') {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/isolateassessgrade.php?cid=".Sanitize::courseId($cid) . "&aid=".Sanitize::onlyInt($aid) . "&r=" . Sanitize::randomQueryStringParam());
	exit;
}