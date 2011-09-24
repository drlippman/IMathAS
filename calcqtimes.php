<?php
//IMathAS:  Utility script to update question average times
//(c) 2011 David Lippman
//Is not currently part of the GUI

require("validate.php");
if ($myrights<100) {
	exit;
}
//will calculate a trimmed mean of times.  What percent to trim?
$trim = .2;  //20%
$qtimes = array();

$query = "SELECT questions,timeontask FROM imas_assessment_sessions WHERE timeontask<>''";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$q = explode(',',$row[0]);
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
foreach ($qstimes as $qsid=>$tv) {
	$times = explode('~',$tv);
	sort($times, SORT_NUMERIC);
	$trimn = floor($trim*count($times));
	$times = array_slice($times,$trimn,count($times)-2*$trimn);
	$avg = round(array_sum($times)/count($times));
	
	//would need to add this database field
	$query = "UPDATE imas_questionset SET avgtime=$avg WHERE id=$qsid";
	mysql_query($query) or die("Query failed : " . mysql_error());
}
echo "Done";
?>
	
	
