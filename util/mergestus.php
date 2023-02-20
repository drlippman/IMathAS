<?php

require("../init.php");

if ($myrights<100 || empty($_GET['from']) || empty($_GET['to'])) {
	exit;
}

$from = $_GET['from'];
$to = $_GET['to'];

$ids = array();
$stm = $DBH->prepare("SELECT assessmentid FROM imas_assessment_sessions WHERE userid=:userid");
$stm->execute(array(':userid'=>$to));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$ids[] = $row[0];
}
$idlist = implode(',',$ids);
$query = "UPDATE imas_assessment_sessions SET userid=:to WHERE userid=:from AND ";
$query .= "assessmentid IN (SELECT id FROM imas_assessments WHERE courseid=:courseid) ";
if (count($ids)>0) {
	$query .= "AND assessmentid NOT IN ($idlist)";
}
$stm = $DBH->prepare($query);
$stm->execute(array(':to'=>$to, ':from'=>$from, ':courseid'=>$cid));
echo $stm->rowCount().' assessment sessions moved<br/><br/>';

$ids = array();
$stm = $DBH->prepare("SELECT assessmentid FROM imas_assessment_records WHERE userid=:userid");
$stm->execute(array(':userid'=>$to));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$ids[] = $row[0];
}
$idlist = implode(',',$ids);
$query = "UPDATE imas_assessment_records SET userid=:to WHERE userid=:from AND ";
$query .= "assessmentid IN (SELECT id FROM imas_assessments WHERE courseid=:courseid) ";
if (count($ids)>0) {
	$query .= "AND assessmentid NOT IN ($idlist)";
}
$stm = $DBH->prepare($query);
$stm->execute(array(':to'=>$to, ':from'=>$from, ':courseid'=>$cid));
echo $stm->rowCount().' assessment records moved<br/><br/>';

$ids = array();
$stm = $DBH->prepare("SELECT gradetypeid FROM imas_grades WHERE userid=:userid AND gradetype='offline' AND score IS NOT NULL");
$stm->execute(array(':userid'=>$to));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$ids[] = $row[0];
}
$idlist = implode(',',$ids);
$query = "UPDATE imas_grades SET userid=:to WHERE userid=:from AND ";
$query .= "gradetype='offline' AND gradetypeid IN (SELECT id FROM imas_gbitems WHERE courseid=:courseid) ";
if (count($ids)>0) {
	$query .= "AND gradetypeid NOT IN ($idlist)";
}
$stm = $DBH->prepare($query);
$stm->execute(array(':to'=>$to, ':from'=>$from, ':courseid'=>$cid));
echo $stm->rowCount().' offline grades moved<br/><br/>';

?>
