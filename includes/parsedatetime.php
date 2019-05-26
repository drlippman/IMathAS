<?php
function parsedatetime($date,$time, $failureDefault=0) {
	global $tzoffset, $tzname;
	/*
	preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$date,$dmatches);
	preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$time,$tmatches);
	if (count($tmatches)==0) {
		preg_match('/(\d+)\s*([a-zA-Z]+)/',$time,$tmatches);
		$tmatches[3] = $tmatches[2];
		$tmatches[2] = 0;
	}
	$tmatches[1] = $tmatches[1]%12;
	if(strtolower($tmatches[3])=="pm") {$tmatches[1]+=12; }
	//$tmatches[2] += $tzoffset;
	//return gmmktime($tmatches[1],$tmatches[2],0,$dmatches[1],$dmatches[2],$dmatches[3]);
	if ($tzname=='') {
		$serveroffset = date('Z')/60 + $tzoffset;
		$tmatches[2] += $serveroffset;
	}
	return mktime($tmatches[1],$tmatches[2],0,$dmatches[1],$dmatches[2],$dmatches[3]);
	*/
	$t = strtotime($date.' '.$time);
	if ($tzname=='') {
		$serveroffset = date('Z') + $tzoffset*60;  //sec
		$t += $serveroffset;
	}
	if ($t === false) {
		$t = $failureDefault;
	}
	return $t;
}

function parsetime($time, $failureDefault=0) {
	preg_match('/(\d+)\s*:(\d+)\s*(\w*)/', $time, $tmatches);
	if (count($tmatches)==0) {
		preg_match('/(\d+)\s*([a-zA-Z]+)/', $time, $tmatches);
		if (count($tmatches)==0) {
			return $failureDefault;
		}
		$tmatches[3] = $tmatches[2];
		$tmatches[2] = 0;
	}
	if ($tmatches[3] != '') {
		$tmatches[1] = $tmatches[1]%12;
		if($tmatches[3]=="pm") {$tmatches[1]+=12; }
	}
	return $tmatches[1]*60 + $tmatches[2];
}
?>
