<?php

require("../validate.php");
require("../assessment/testutil.php");

if ($myrights<100) {
	exit;
}

$aid = intval($_GET['aid']);
if ($aid==0) {exit;}

$query = "SELECT itemorder,defpoints,defpenalty,defattempts FROM imas_assessments WHERE id='$aid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$adata = mysql_fetch_assoc($result);

$questions = str_replace('~',',',preg_replace('/(^|,)\d+(\|\d+)?~/','$1',$adata['itemorder']));

$qdata = array();
$query = "SELECT id,points,penalty,attempts FROM imas_questions WHERE id IN ($questions)";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_assoc($result)) {
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
$query = "SELECT id,questions,bestscores,bestattempts,scores,attempts FROM imas_assessment_sessions WHERE assessmentid='$aid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_assoc($result)) {
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
	$q = "UPDATE imas_assessment_sessions SET bestscores='$newbest',scores='$newsc' WHERE id={$row['id']}";
	mysql_query($q) or die("Query failed : " . mysql_error());
	$n++;
}
echo "Updated $n assessment records";

?>
