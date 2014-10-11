<?php
//IMathAS:  Utility script to update question average times
//(c) 2014 David Lippman
//Is not currently part of the GUI
@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");
ini_set("memory_limit", "512857600");

require("validate.php");
if ($myrights<100) {
	exit;
}

//get last updated time
$query = "SELECT id,ver FROM imas_dbschema WHERE id=3 OR id=4";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) {
	$lastupdate = 0;
	$lastfirstupdate = 0;
} else {
	while ($r = mysql_fetch_row($result)) {
		if ($r[0]==3) {
			$lastupdate = $r[1];
		} else {
			$lastfirstupdate = $r[1];
		}
	}
}

//will calculate a trimmed mean of times.  What percent to trim?
$trim = .2;  //20%
$qtimes = array();

$query = "SELECT questions,timeontask FROM imas_assessment_sessions WHERE timeontask<>'' AND endtime>$lastupdate";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	//$q = explode(',',$row[0]);
	if (strpos($row[0],';')===false) {
		$q = explode(",",$row[0]);
	} else {
		list($questions,$bestquestions) = explode(";",$row[0]);
		$q = explode(",",$bestquestions);
	}

	$t = explode(',',$row[1]);
	foreach ($q as $k=>$qn) {
		if ($t[$k]=='') {continue;}
		if (isset($qtimes[$qn])) {
			$qtimes[$qn] .= '~'.$t[$k];
		} else {
			$qtimes[$qn] = $t[$k];
		}
	}
}
$qstimes = array();
$qsfirsttimes = array();
$qsfirstscores = array();
$query = "SELECT id,questionsetid FROM imas_questions WHERE 1";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if (isset($qtimes[$row[0]])) {
		if (isset($qstimes[$row[1]])) {
			$qstimes[$row[1]] .= '~'.$qtimes[$row[0]];
		} else {
			$qstimes[$row[1]] = $qtimes[$row[0]];
		}
	}
}
unset($qtimes);

$query = "SELECT qsetid,score,timespent FROM imas_firstscores WHERE timespent>0 AND timespent<1200 AND id>$lastfirstupdate";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if (isset($qsfirsttimes[$row[0]])) {
		$qsfirsttimes[$row[0]][] = $row[2];
	} else {
		$qsfirsttimes[$row[0]] = array($row[2]);
	}
	if (isset($qsfirstscores[$row[0]])) {
		$qsfirstscores[$row[0]][] = $row[1];
	} else {
		$qsfirstscores[$row[0]] = array($row[1]);
	}
}
$avgtime = array();
$avgfirsttime = array();
$avgfirstscore = array();
$n = array();
foreach ($qstimes as $qsid=>$tv) {
	$times = explode('~',$tv);
	sort($times, SORT_NUMERIC);
	$trimn = floor($trim*count($times));
	$times = array_slice($times,$trimn,count($times)-2*$trimn);
	$avgtime[$qsid] = round(array_sum($times)/count($times));	
}

foreach ($qsfirsttimes as $qsid=>$times) {
	sort($times, SORT_NUMERIC);
	$trimn = floor($trim*count($times));
	$times = array_slice($times,$trimn,count($times)-2*$trimn);
	$avgfirsttime[$qsid] = round(array_sum($times)/count($times));
}
foreach ($qsfirstscores as $qsid=>$scores) {
	$n[$qsid] = count($scores);
	/*skip trimmed mean for scores
	sort($scores, SORT_NUMERIC);
	$trimn = floor($trim*count($scores));
	$scores = array_slice($scores,$trimn,count($scores)-2*$trimn);
	*/
	$avgfirstscore[$qsid] = round(array_sum($scores)/count($scores));
}

$nq = count($n);
$totn = array_sum($n);

$query = "SELECT id,avgtime FROM imas_questionset";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$qsid = $row[0];
	if (!isset($avgtime[$qsid]) || $n[$qsid]==0) {continue;}
	
	if (strpos($row[1],',')!==false) {
		list($oldavgtime,$oldfirsttime,$oldfirstscore,$oldn) = explode(',',$row[1]);
		$avgtime[$qsid] = round(($avgtime[$qsid]*$n[$qsid] + $oldavgtime*$oldn)/($n[$qsid]+$oldn));
		$avgfirsttime[$qsid] = round(($avgfirsttime[$qsid]*$n[$qsid] + $oldfirsttime*$oldn)/($n[$qsid]+$oldn));
		$avgfirstscore[$qsid] = round(($avgfirstscore[$qsid]*$n[$qsid] + $oldfirstscore*$oldn)/($n[$qsid]+$oldn));
		$n[$qsid] += $oldn;
	}
	$avg = addslashes($avgtime[$qsid].','.$avgfirsttime[$qsid].','.$avgfirstscore[$qsid].','.$n[$qsid]);
	$query = "UPDATE imas_questionset SET avgtime='$avg' WHERE id=$qsid";
	mysql_query($query) or die("Query failed : " . mysql_error());
}

$query = "SELECT max(id) FROM imas_firstscores";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$row = mysql_fetch_row($result);
if ($lastupdate == 0) {
	$lastfirstupdate = $row[0];
	$lastupdate = time();
	$query = "INSERT INTO imas_dbschema (id,ver) VALUES (3,$lastupdate),(4,$lastfirstupdate)";
	mysql_query($query) or die("Query failed : " . mysql_error());
} else {
	$lastfirstupdate = $row[0];
	$lastupdate = time();
	$query = "UPDATE imas_dbschema SET ver=$lastupdate WHERE id=3";
	mysql_query($query) or die("Query failed : " . mysql_error());
	$query = "UPDATE imas_dbschema SET ver=$lastfirstupdate WHERE id=4";
	mysql_query($query) or die("Query failed : " . mysql_error());
}

echo "Done: updated $nq questions with a total of $totn new datapoints";
?>
	
	
