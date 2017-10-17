<?php

require("../init_without_validate.php");
require("../i18n/i18n.php");
//replace later with some sort of access code
require("../includes/JWT.php");
if (!isset($_REQUEST['t'])) {
	echo 'Token required';
	exit;
}
//check token.  It was signed with user's password, so runs with their authority
try {
	$JWTsess = JWT::decode($_REQUEST['t']);
} catch (Exception $e) {
	echo "Error:", $e->getMessage();
	exit;
}
if (!isset($JWTsess->uid)) {
	echo 'Invalid token parameters';
	exit;
}
$userid = intval($JWTsess->uid);
$cid = intval($JWTsess->cid);
$alarms = array('T'=>'', 'A'=>'', 'F'=>'', 'C'=>'');
function toalarmformat($str) {
	$type = $str{0};
	$time = intval(substr($str,1));
	if (($type!=='D' && $type!=='H' && $type!=='M') || $time==0) {
		return false;
	} else {
		return '-PT'.$time.$type;
	}
}
if (isset($JWTsess->T) && $a = toalarmformat($JWTsess->T)) {
	$alarms['T'] = $a;
}
if (isset($JWTsess->A) && $a = toalarmformat($JWTsess->A)) {
	$alarms['A'] = $a;
}
if (isset($JWTsess->F) && $a = toalarmformat($JWTsess->F)) {
	$alarms['F'] = $a;
}
if (isset($JWTsess->T) && $a = toalarmformat($JWTsess->C)) {
	$alarms['C'] = $a;
}
	
require("../includes/calendardata.php");

list($coursename,$calevents) = getCalendarEventData($cid, $userid);

//add alarms
foreach ($calevents as $k=>$v) {
	$type = $v[0]{0};
	switch ($type) {
		case 'A':
		case 'D':
			$calevents[$k][] = $alarms['A'];
			break;
		case 'I':
		case 'L':
			$calevents[$k][] = $alarms['T'];
			break;
		case 'F':
			$calevents[$k][] = $alarms['F'];
			break;
		case 'C':
			$calevents[$k][] = $alarms['C'];
			break;
	}
}
date_default_timezone_set('UTC');

header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=calfeed.ics');

$EOL = "\r\n";

function calencode($v) {
	$v = html_entity_decode($v);
	return preg_replace('/([\,;])/','\\\$1', $v);
	
}

echo 'BEGIN:VCALENDAR'.$EOL;
echo 'PRODID:-//IMathAS//'.$installname.'//EN'.$EOL;
echo 'VERSION:2.0'.$EOL;
echo 'NAME:'.calencode($coursename).$EOL;
echo 'DESCRIPTION:'.calencode($installname.': '.$coursename).$EOL;
echo 'X-WR-CALNAME:'.calencode($coursename).$EOL;
echo 'X-WR-CALDESC:'.calencode($installname.': '.$coursename).$EOL;

foreach ($calevents as $event) {
	echo 'BEGIN:VEVENT'.$EOL;
	echo 'UID:'.$event[0].'@'.$installname.'.imathas.com'.$EOL;
	echo 'DTSTAMP:'.date('Ymd\THis\Z', $now).$EOL;
	echo 'DTSTART:'.date('Ymd\THis\Z', $event[1]).$EOL;
	echo 'DTEND:'.date('Ymd\THis\Z', $event[1]).$EOL;
	echo 'SUMMARY:'.calencode($event[2]).$EOL;
	if ($event[3] != '') {
		echo 'DESCRIPTION:'.calencode($event[3]).$EOL;
	}
	if ($event[4] != '') { //alarm
		echo 'BEGIN:VALARM'.$EOL;
		echo 'TRIGGER:'.$event[4].$EOL;
		echo 'ACTION:DISPLAY'.$EOL;
		echo 'DESCRIPTION:'.calencode(($event[3]!=''?$event[3]:$event[2])).$EOL;
		echo 'END:VALARM'.$EOL;
	}
	echo 'END:VEVENT'.$EOL;
}
echo 'END:VCALENDAR'.$EOL;



function getpts($scs) {
	$tot = 0;
  	foreach(explode(',',$scs) as $sc) {
		$qtot = 0;
		if (strpos($sc,'~')===false) {
			if ($sc>0) {
				$qtot = $sc;
			}
		} else {
			$sc = explode('~',$sc);
			foreach ($sc as $s) {
				if ($s>0) {
					$qtot+=$s;
				}
			}
		}
		$tot += round($qtot,1);
	}
	return $tot;
}

?>
