<?php
//IMathAS:  Utility script to update question average times
//(c) 2014 David Lippman
//Is not currently part of the GUI

require("init.php");
if ($myrights<100) {
	exit;
}
ini_set('display_errors',1);
error_reporting(E_ALL);
@set_time_limit(0);
ini_set("max_input_time", "3600");
ini_set("max_execution_time", "3600");
ini_set("memory_limit", "712857600");

$start = microtime(true);
//get last updated time
//DB $query = "SELECT id,ver FROM imas_dbschema WHERE id=3 OR id=4";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$stm = $DBH->query("SELECT id,ver FROM imas_dbschema WHERE id=3 OR id=4");
if ($stm->rowCount()==0) {
	$lastupdate = 0;
	$lastfirstupdate = 0;
} else {
	//DB while ($r = mysql_fetch_row($result)) {
	while ($r = $stm->fetch(PDO::FETCH_NUM)) {
		if ($r[0]==3) {
			$lastupdate = $r[1];
		} else {
			$lastfirstupdate = $r[1];
		}
	}
}

//will calculate a trimmed mean of times.  What percent to trim?
$trim = .2;  //20%

//The average time per attempt over all attempts calculation is REALLY
//slow and requires a ton of memory.  Not recommended unless you really really
//care
$doslowmethod = false;

if ($doslowmethod) {
	$qtimes = array();

	//DB $query = "SELECT questions,timeontask FROM imas_assessment_sessions WHERE timeontask<>'' AND endtime>$lastupdate";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT questions,timeontask FROM imas_assessment_sessions WHERE timeontask<>'' AND endtime>:lastupdate");
	$stm->execute(array(':lastupdate'=>$lastupdate));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
	//DB $query = "SELECT id,questionsetid FROM imas_questions WHERE 1";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->query("SELECT id,questionsetid FROM imas_questions WHERE 1");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if (isset($qtimes[$row[0]])) {
			if (isset($qstimes[$row[1]])) {
				$qstimes[$row[1]] .= '~'.$qtimes[$row[0]];
			} else {
				$qstimes[$row[1]] = $qtimes[$row[0]];
			}
		}
	}
	unset($qtimes);
	$avgtime = array();

	foreach ($qstimes as $qsid=>$tv) {
		$times = explode('~',$tv);
		sort($times, SORT_NUMERIC);
		$trimn = floor($trim*count($times));
		$times = array_slice($times,$trimn,count($times)-2*$trimn);
		$avgtime[$qsid] = round(array_sum($times)/count($times));
	}
}

$avgfirsttime = array();
$avgfirstscore = array();
$n = array();

$thistimes = array();
$thisscores = array();
$lastq = -1;
//DB $query = "SELECT qsetid,score,timespent FROM imas_firstscores WHERE timespent>0 AND timespent<1200 AND id>$lastfirstupdate ORDER BY qsetid";
//DB $result = mysql_unbuffered_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT qsetid,score,timespent FROM imas_firstscores WHERE timespent>0 AND timespent<1200 AND id>:lastfirstupdate ORDER BY qsetid");
$stm->execute(array(':lastfirstupdate'=>$lastfirstupdate));
//DB $result = mysql_unbuffered_query($query) or die("Query failed : " . mysql_error());
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	if ($row[0] != $lastq && $lastq>0) {
		$n[$lastq] = count($thisscores);
		sort($thistimes, SORT_NUMERIC);
		$trimn = floor($trim*count($thistimes));
		$thistimes = array_slice($thistimes,$trimn,count($thistimes)-2*$trimn);
		$avgfirsttime[$lastq] = round(array_sum($thistimes)/count($thistimes));
		$avgfirstscore[$lastq] = round(array_sum($thisscores)/count($thisscores));
		$thistimes = array();
		$thisscores = array();
	}
	$thistimes[] = $row[2];
	$thisscores[] = $row[1];
	if ($row[0] != $lastq) {
		$lastq = $row[0];
	}
}
if (count($thistimes)>0) {
	$n[$lastq] = count($thisscores);
	sort($thistimes, SORT_NUMERIC);
	$trimn = floor($trim*count($thistimes));
	$thistimes = array_slice($thistimes,$trimn,count($thistimes)-2*$trimn);
	$avgfirsttime[$lastq] = round(array_sum($thistimes)/count($thistimes));
	$avgfirstscore[$lastq] = round(array_sum($thisscores)/count($thisscores));
}

$nq = count($n);
$totn = array_sum($n);

if ($lastfirstupdate==0) {
	foreach ($n as $qsid=>$nval) {
		if ($doslowmethod) {
			//DB $avg = addslashes($avgtime[$qsid].','.$avgfirsttime[$qsid].','.$avgfirstscore[$qsid].','.$n[$qsid]);
			$avg = $avgtime[$qsid].','.$avgfirsttime[$qsid].','.$avgfirstscore[$qsid].','.$n[$qsid];
		} else {
			//DB $avg = addslashes('0,'.$avgfirsttime[$qsid].','.$avgfirstscore[$qsid].','.$n[$qsid]);
			$avg = '0,'.$avgfirsttime[$qsid].','.$avgfirstscore[$qsid].','.$n[$qsid];
		}
		//DB $query = "UPDATE imas_questionset SET avgtime='$avg' WHERE id=$qsid";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_questionset SET avgtime=:avgtime WHERE id=:id");
		$stm->execute(array(':avgtime'=>$avg, ':id'=>$qsid));
	}
} else {
	//DB $query = "SELECT id,avgtime FROM imas_questionset";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT id,avgtime FROM imas_questionset");
	$stm->execute(array());
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$qsid = $row[0];
		if (!isset($avgfirsttime[$qsid]) || $n[$qsid]==0) {continue;}

		if (strpos($row[1],',')!==false) {
			list($oldavgtime,$oldfirsttime,$oldfirstscore,$oldn) = explode(',',$row[1]);
			if ($doslowmethod) {
				$avgtime[$qsid] = round(($avgtime[$qsid]*$n[$qsid] + $oldavgtime*$oldn)/($n[$qsid]+$oldn));
			}
			$avgfirsttime[$qsid] = round(($avgfirsttime[$qsid]*$n[$qsid] + $oldfirsttime*$oldn)/($n[$qsid]+$oldn));
			$avgfirstscore[$qsid] = round(($avgfirstscore[$qsid]*$n[$qsid] + $oldfirstscore*$oldn)/($n[$qsid]+$oldn));
			$n[$qsid] += $oldn;
		}
		if ($doslowmethod) {
			//DB $avg = addslashes($avgtime[$qsid].','.$avgfirsttime[$qsid].','.$avgfirstscore[$qsid].','.$n[$qsid]);
			$avg = $avgtime[$qsid].','.$avgfirsttime[$qsid].','.$avgfirstscore[$qsid].','.$n[$qsid];
		} else {
			//DB $avg = addslashes('0,'.$avgfirsttime[$qsid].','.$avgfirstscore[$qsid].','.$n[$qsid]);
			$avg = '0,'.$avgfirsttime[$qsid].','.$avgfirstscore[$qsid].','.$n[$qsid];
		}
		//DB $query = "UPDATE imas_questionset SET avgtime='$avg' WHERE id=$qsid";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm2 = $DBH->prepare("UPDATE imas_questionset SET avgtime=:avgtime WHERE id=:id");
		$stm2->execute(array(':avgtime'=>$avg, ':id'=>$qsid));
	}
}

//DB $query = "SELECT max(id) FROM imas_firstscores";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB $row = mysql_fetch_row($result);
$stm = $DBH->query("SELECT max(id) FROM imas_firstscores");
$row = $stm->fetch(PDO::FETCH_NUM);
if ($lastfirstupdate == 0) {
	$lastfirstupdate = $row[0];
	if ($doslowmethod) {
		$lastupdate = time();
	}
	//DB $query = "INSERT INTO imas_dbschema (id,ver) VALUES (3,$lastupdate),(4,$lastfirstupdate)";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("INSERT INTO imas_dbschema (id,ver) VALUES (:id, :ver),(:idB, :verB)");
	$stm->execute(array(':id'=>3, ':ver'=>$lastupdate, ':idB'=>4, ':verB'=>$lastfirstupdate));
} else {
	$lastfirstupdate = $row[0];
	//DB $query = "UPDATE imas_dbschema SET ver=$lastfirstupdate WHERE id=4";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_dbschema SET ver=:ver WHERE id=4");
	$stm->execute(array(':ver'=>$lastfirstupdate));
	if ($doslowmethod) {
		$lastupdate = time();
		//DB $query = "UPDATE imas_dbschema SET ver=$lastupdate WHERE id=3";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_dbschema SET ver=:ver WHERE id=3");
		$stm->execute(array(':ver'=>$lastupdate));
	}
}

echo "Done: updated $nq questions with a total of $totn new datapoints";
echo '<br/>Max memory: '.memory_get_peak_usage().', '.memory_get_peak_usage(true);
echo '<br/>Time: '.(microtime(true) - $start);
?>
