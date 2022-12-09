<?php
//Time and date processing functions

global $allowedmacros;
array_push($allowedmacros, "timetominutes", "thisyear");

//timetominutes(string)
//takes a string of the form "3:45 pm" and converts it into a number of
//minutes past midnight.  Can also handle "3 pm", or "14:50"
function timetominutes($str) {
	$str = str_replace('.','',trim($str));
	preg_match('/(\d+)\s*:(\d+)\s*(\w*)/',$str,$tmatches);
	if (count($tmatches)==0) {
		preg_match('/(\d+)\s*([\w]*)/',$str,$tmatches);
		$tmatches[3] = $tmatches[2];
		$tmatches[2] = 0;
	}
	if ($tmatches[3]=='' && $tmatches[1]>11) {
		$tmatches[3] = "pm";
	} else if ($tmatches[3]=='') {
		$tmatches[3] = "am";
	}
	$tmatches[1] = $tmatches[1]%12;
	if($tmatches[3]=="pm") {$tmatches[1]+=12; }
	return (60*$tmatches[1] + $tmatches[2]);
}

//thisyear()
//returns a 4-digit representation of the current year. eg. 2013
function thisyear() {
	return date("Y",time());
}
