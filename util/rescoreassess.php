<?php

require_once "../init.php";
require_once "../assessment/testutil.php";

if ($myrights<100) {
	exit;
}

$aid = Sanitize::onlyInt($_GET['aid']);
if ($aid==0) {exit;}
$stm = $DBH->prepare("SELECT itemorder,defpoints,defpenalty,defattempts FROM imas_assessments WHERE id=:id");
$stm->execute(array(':id'=>$aid));
$adata = $stm->fetch(PDO::FETCH_ASSOC);

$questions = explode(',',str_replace('~',',',preg_replace('/(^|,)\d+(\|\d+)?~/','$1',$adata['itemorder'])));
$goodqs = array();
foreach ($questions as $k=>$v) {
	if (is_numeric($v)) {
		$goodqs[] = intval($v);
	}
}
$questions = implode(',', $goodqs);

$qdata = array();
$query = "SELECT id,points,penalty,attempts FROM imas_questions WHERE id IN ($questions)";
$stm = $DBH->query($query);
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	if ($row['points']==9999) {
		$row['points'] = $adata['defpoints'];
	}
	if ($row['attempts']==9999) {
		$row['attempts'] = $adata['attempts'];
	}
	if ($row['penalty']==9999) {
		$row['penalty'] = $adata['penalty'];
	}
	$qdata[$row['id']] = $row;
}

$testsettings['exceptionpenalty'] = 0;
$inexception = false;

$n = 0;
$upd_stm = $DBH->prepare("UPDATE imas_assessment_sessions SET bestscores=:bestscores,scores=:scores WHERE id=:id");
$stm = $DBH->prepare("SELECT id,questions,bestscores,bestattempts,scores,attempts FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
$stm->execute(array(':assessmentid'=>$aid));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$qpts = explode(';', $row['questions']);
	if (count($qpts)==1) {
		$bq = explode(',', $qpts[0]);
		$q = explode(',', $qpts[0]);
	} else {
		$bq = explode(',', $qpts[1]);
		$q = explode(',', $qpts[0]);
	}
	$scparts = explode(';', $row['scores']);
	$rawscores = explode(',', $scparts[1]);
	$attempts = explode(',', $row['attempts']);
	$scores = array();

	$bscparts = explode(';', $row['bestscores']);
	$brawscores = explode(',', $bscparts[1]);
	$battempts = explode(',', $row['bestattempts']);
	$bscores = array();
	foreach ($bq as $i=>$qid) {
		$bscores[$i] = calcpointsafterpenalty($brawscores[$i], $qdata[$qid], $testsettings, $battempts[$i]);
	}
	foreach ($q as $i=>$qid) {
		$scores[$i] = calcpointsafterpenalty($rawscores[$i], $qdata[$qid], $testsettings, $attempts[$i]);
	}
	$newbest = implode(',', $bscores).';'.$bscparts[1].';'.$bscparts[2];
	$newsc = implode(',', $scores).';'.$scparts[1];
	$upd_stm->execute(array(':bestscores'=>$newbest, ':scores'=>$newsc, ':id'=>$row['id']));
	$n++;
}
echo "Updated $n assessment records";

?>
