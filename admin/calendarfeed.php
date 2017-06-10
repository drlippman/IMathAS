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
	

$studentinfo = array();
$stm = $DBH->prepare("SELECT id,section FROM imas_students WHERE userid=:userid AND courseid=:courseid");
$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
$line = $stm->fetch(PDO::FETCH_ASSOC);
if ($line != null) {
	$studentid = $line['id'];
	$studentinfo['timelimitmult'] = $line['timelimitmult'];
	$studentinfo['section'] = $line['section'];
} else {
	$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
	$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	if ($line != null) {
		if ($myrights>19) {
			$teacherid = $line['id'];
		} else {
			$tutorid = $line['id'];
		}
	} else {
		$stm = $DBH->prepare("SELECT id,section FROM imas_tutors WHERE userid=:userid AND courseid=:courseid");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		if ($line != null) {
			$tutorid = $line['id'];
			$tutorsection = trim($line['section']);
		}
	}
}

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	echo 'Invalid token: invalid user credentials';
	exit;
}
			
$now= time();

$exceptions = array();
$forumexceptions = array();
if (isset($studentid)) {
	$stm = $DBH->prepare("SELECT assessmentid,startdate,enddate,islatepass,waivereqscore,itemtype FROM imas_exceptions WHERE userid=:userid");
	$stm->execute(array(':userid'=>$userid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[5]=='A') {
			$exceptions[$row[0]] = array($row[1],$row[2],$row[3],$row[4]);
		} else if ($row[5]=='F' || $row[5]=='P' || $row[5]=='R') {
			$forumexceptions[$row[0]] = array($row[1],$row[2],$row[3],$row[4],$row[5]);
		}
	}
}

function flattenitems($items,&$addto,$viewall) {
	global $studentinfo;
	$now = time();
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			if (!isset($item['avail'])) { //backwards compat
				$item['avail'] = 1;
			}
			if (!$viewall && isset($item['grouplimit']) && count($item['grouplimit'])>0) {
				 if (!in_array('s-'.$studentinfo['section'],$item['grouplimit'])) {
					 continue;
				 }
			}
			if (($item['avail']==2 || ($item['avail']==1 && $item['startdate']<$now && $item['enddate']>$now)) ||
				($viewall || ($item['SH'][0]=='S' && $item['avail']>0))) {
				flattenitems($item['items'],$addto,$viewall);
			}
		} else {
			$addto[] = $item;
		}
	}
}

$stm = $DBH->prepare("SELECT name,itemorder FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$row = $stm->fetch(PDO::FETCH_NUM);
$coursename = trim($row[0]);
$itemorder = unserialize($row[1]);
$itemsimporder = array();

flattenitems($itemorder,$itemsimporder,(isset($teacherid)||isset($tutorid)));

$tolookup = implode(',', array_map('intval', $itemsimporder));
$itemlist = array();
if (count($itemsimporder)>0) {
	$stm = $DBH->query("SELECT id,itemtype,typeid FROM imas_items WHERE id IN ($tolookup)");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if (!isset($itemlist[$row[1]])) {
			$itemlist[$row[1]] = array();
		}
		$itemlist[$row[1]][$row[2]] = $row[0];
	}
}


$calevents = array();

$bestscores_stm = null;

if (isset($itemlist['Assessment'])) {
	$typeids = implode(',', array_keys($itemlist['Assessment']));
	$stm = $DBH->query("SELECT id,name,startdate,enddate,reviewdate,reqscore,reqscoreaid FROM imas_assessments WHERE avail=1 AND id IN ($typeids) AND enddate<2000000000 ORDER BY name");
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		require_once("../includes/exceptionfuncs.php");
		if (isset($exceptions[$row['id']])) {
			$useexception = getCanUseAssessException($exceptions[$row['id']], $row, true); 
			if ($useexception) {
				$row['startdate'] = $exceptions[$row['id']][0];
				$row['enddate'] = $exceptions[$row['id']][1];
			}
		} 
		//if startdate is past now, skip
		if ($row['startdate']>$now && !isset($teacherid)) {
			continue;
		}
		
		$showgrayedout = false;
		if (!isset($teacherid) && abs($row['reqscore'])>0 && $row['reqscoreaid']>0 && (!isset($exceptions[$row['id']]) || $exceptions[$row['id']][3]==0)) {
			if ($bestscores_stm===null) { //only prepare once
				$bestscores_stm = $DBH->prepare("SELECT bestscores FROM imas_assessment_sessions WHERE assessmentid=:assessmentid AND userid=:userid");
			}
			$bestscores_stm->execute(array(':assessmentid'=>$row['reqscoreaid'], ':userid'=>$userid));
			if ($bestscores_stm->rowCount()==0) {
				if ($row['reqscore']<0) {
					$showgrayedout = true;
				} else {
					continue;
				}
			} else {
				$scores = explode(';',$bestscores_stm->fetchColumn(0));
				if (round(getpts($scores[0]),1)+.02<abs($row['reqscore'])) {
					if ($row['reqscore']<0) {
						$showgrayedout = true;
					} else {
						continue;
					}
				}
			}
		}
		if ($row['reviewdate']>0 && $row['reviewdate']<2000000000 && $now>$row['enddate']) { //has review, and we're past enddate
			//do we care about review dates?
		}
		if ($row['enddate']>0 && $row['enddate']<2000000000) {
			$calevents[] = array('A'.$row['id'], $row['enddate'], $row['name'], _('Assignment Due: ').$row['name'], $alarms['A']);
		}
	}
}

if (isset($itemlist['InlineText'])) {
	$typeids = implode(',', array_keys($itemlist['InlineText']));
	if (isset($teacherid)) {
		$stm = $DBH->prepare("SELECT id,title,enddate,startdate,oncal FROM imas_inlinetext WHERE ((oncal=2 AND enddate>0 AND enddate<2000000000) OR (oncal=1 AND startdate<2000000000 AND startdate>0)) AND (avail=1 OR (avail=2 AND startdate>0)) AND id IN ($typeids)");
		$stm->execute(array(':courseid'=>$cid));
	} else {
		$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_inlinetext WHERE ";
		$query .= "((avail=1 AND ((oncal=2 AND enddate>0 AND enddate<2000000000 AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>0))) OR ";
		$query .= "(avail=2 AND oncal=1 AND startdate<2000000000 AND startdate>0)) AND id IN ($typeids)";
		$stm = $DBH->query($query); 
	}
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($row['oncal']==1) {
			$date = $row['startdate'];
		} else {
			$date = $row['enddate'];
		}
		$calevents[] = array('I'.$row['id'],$date, $row['title'],'', $alarms['T']);
	}
}

if (isset($itemlist['LinkedText'])) {
	$typeids = implode(',', array_keys($itemlist['LinkedText']));
	if (isset($teacherid)) {
		$query = "SELECT id,title,enddate,startdate,oncal FROM imas_linkedtext WHERE ((oncal=2 AND enddate>0 AND enddate<2000000000) OR (oncal=1 AND startdate<2000000000 AND startdate>0)) AND (avail=1 OR (avail=2 AND startdate>0)) AND id IN ($typeids) ORDER BY title";
	} else {
		$query = "SELECT id,title,enddate,startdate,oncal FROM imas_linkedtext WHERE ";
		$query .= "((avail=1 AND ((oncal=2 AND enddate>0 AND enddate<2000000000 AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>0))) OR ";
		$query .= "(avail=2 AND oncal=1 AND startdate<2000000000 AND startdate>0)) AND id IN ($typeids) ORDER BY title";
	}
	$stm = $DBH->query($query); 
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($row['oncal']==1) {
			$date = $row['startdate'];
		} else {
			$date = $row['enddate'];
		}
		$calevents[] = array('L'.$row['id'],$date, $row['title'],'', $alarms['T']);
	}
}

if (isset($itemlist['Drill'])) {
	$typeids = implode(',', array_keys($itemlist['Drill']));
	if (isset($teacherid)) {
		$query = "SELECT id,name,enddate,startdate,caltag,avail FROM imas_drillassess WHERE (enddate>0 AND enddate<2000000000) AND avail=1 AND id IN ($typeids) ORDER BY name";
	} else {
		$query = "SELECT id ,name,enddate,startdate,caltag,avail FROM imas_drillassess WHERE ";
		$query .= "avail=1 AND (enddate>0 AND enddate<2000000000 AND startdate<$now) ";
		$query .= "AND id IN ($typeids) ORDER BY name";
	}
	$stm = $DBH->query($query); 
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$calevents[] = array('D'.$row['id'],$row['enddate'], $row['name'], _('Drill Due: ').$row['name'], $alarms['A']);
	}	
}

if (isset($itemlist['Forum'])) {
	$typeids = implode(',', array_keys($itemlist['Forum']));
	$query = "SELECT id,name,postby,replyby,startdate,enddate FROM imas_forums WHERE enddate>0 AND ((postby>0 AND postby<2000000000) OR (replyby>0 AND replyby<2000000000)) AND avail>0 AND id IN ($typeids) ORDER BY name";
	$stm = $DBH->query($query); 
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (($row['startdate']>$now && !isset($teacherid))) {
			continue;
		}
		require_once("../includes/exceptionfuncs.php");
		list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepassP, $canuselatepassR, $row['postby'], $row['replyby'], $row['enddate']) = getCanUseLatePassForums(isset($forumexceptions[$row['id']])?$forumexceptions[$row['id']]:null, $row);
		
		if ($row['postby']!=2000000000) {
			$calevents[] = array('FP'.$row['id'],$row['postby'], $row['name'], _('Posts Due: ').$row['name'], $alarms['F']);
		}
		if ($row['replyby']!=2000000000) {
			$calevents[] = array('FR'.$row['id'],$row['postby'], $row['name'], _('Replies Due: ').$row['name'], $alarms['F']);
		}
	}
}

$stm = $DBH->prepare("SELECT title,tag,date,id FROM imas_calitems WHERE date>0 AND date<2000000000 and courseid=:courseid ORDER BY title");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$calevents[] = array('C'.$row['id'],$row['date'], $row['tag'], $row['title'], $alarms['C']);
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
