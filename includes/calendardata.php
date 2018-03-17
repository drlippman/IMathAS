<?php

require_once(__DIR__.'/exceptionfuncs.php');

//light calendar data collection function

function getCalendarEventData($cid, $userid, $stuview = false) {
	global $DBH;

	$studentinfo = array();
	$stm = $DBH->prepare("SELECT id,section,latepass FROM imas_students WHERE userid=:userid AND courseid=:courseid");
	$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	if ($line != null) {
		$studentid = $line['id'];
		$studentinfo['timelimitmult'] = $line['timelimitmult'];
		$studentinfo['section'] = $line['section'];
		$latepasses = $line['latepass'];
	} else {
		$latepasses = 0;
		$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		if ($line != null) {
			$teacherid = $line['id'];
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
	if ($stuview) {
		$studentinfo['timelimitmult'] = 1;
		$studentinfo['section'] = "not a real section name";
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

	$stm = $DBH->prepare("SELECT name,itemorder,latepasshrs FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$row = $stm->fetch(PDO::FETCH_NUM);
	$coursename = trim($row[0]);
	$itemorder = unserialize($row[1]);
	$itemsimporder = array();

	$exceptionfuncs = new ExceptionFuncs($userid, $cid, !empty($studentid), $latepasses, $row[2]);

	flattenitems($itemorder,$itemsimporder,(isset($teacherid)||isset($tutorid))&&!$stuview, $studentinfo);

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
		$stm = $DBH->query("SELECT id,name,startdate,enddate,reviewdate,reqscore,reqscoreaid,reqscoretype,ptsposs FROM imas_assessments WHERE avail=1 AND date_by_lti<>1 AND id IN ($typeids) AND enddate<2000000000 ORDER BY name");
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			require_once("../includes/exceptionfuncs.php");
			if (isset($exceptions[$row['id']])) {
				$useexception = $exceptionfuncs->getCanUseAssessException($exceptions[$row['id']], $row, true);
				if ($useexception) {
					$row['startdate'] = $exceptions[$row['id']][0];
					$row['enddate'] = $exceptions[$row['id']][1];
				}
			}
			//if startdate is past now, skip
			if ($row['startdate']>$now && (!isset($teacherid) || $stuview)) {
				continue;
			}

			$showgrayedout = false;
			if (!isset($teacherid) && abs($row['reqscore'])>0 && $row['reqscoreaid']>0 && (!isset($exceptions[$row['id']]) || $exceptions[$row['id']][3]==0)) {
				if ($bestscores_stm===null) { //only prepare once
					$query = "SELECT ias.bestscores,ia.ptsposs FROM imas_assessment_sessions AS ias ";
					$query .= "JOIN imas_assessments AS ia ON ias.assessmentid=ia.id ";
					$query .= "WHERE assessmentid=:assessmentid AND userid=:userid";
					$bestscores_stm = $DBH->prepare($query);
				}
				$bestscores_stm->execute(array(':assessmentid'=>$row['reqscoreaid'], ':userid'=>$userid));
				if ($bestscores_stm->rowCount()==0) {
					if ($row['reqscore']<0 || $row['reqscoretype']&1) {
						$showgrayedout = true;
					} else {
						continue;
					}
				} else {
					list($scores,$reqscoreptsposs) = $bestscores_stm->fetch(PDO::FETCH_NUM);
					$scores = explode(';', $scores);
					if ($row['reqscoretype']&2) { //using percent-based
						if ($reqscoreptsposs==-1) {
							require("../includes/updateptsposs.php");
							$reqscoreptsposs = updatePointsPossible($row['reqscoreaid']);
						}
						if (round(100*getpts($scores[0])/$reqscoreptsposs,1)+.02<abs($row['reqscore'])) {
							if ($row['reqscore']<0 || $row['reqscoretype']&1) {
								$showgrayedout = true;
							} else {
								continue;
							}
						}
					} else { //points based
						if (round(getpts($scores[0]),1)+.02<abs($row['reqscore'])) {
							if ($row['reqscore']<0 || $row['reqscoretype']&1) {
								$showgrayedout = true;
							} else {
								continue;
							}
						}
					}
				}
			} else if ($stuview && abs($row['reqscore'])>0 && $row['reqscoreaid']>0) {
				if ($row['reqscore']<0) {
					$showgrayedout = true;
				} else {
					continue;
				}
			}
			if ($row['reviewdate']>0 && $row['reviewdate']<2000000000 && $now>$row['enddate']) { //has review, and we're past enddate
				//do we care about review dates?
			}
			if ($row['enddate']>0 && $row['enddate']<2000000000) {
				$calevents[] = array('A'.$row['id'], $row['enddate'], $row['name'], _('Assignment Due: ').$row['name']);
			}
		}
	}

	if (isset($itemlist['InlineText'])) {
		$typeids = implode(',', array_keys($itemlist['InlineText']));
		if (isset($teacherid) && !$stuview) {
			$stm = $DBH->prepare("SELECT id,title,enddate,startdate,oncal,text FROM imas_inlinetext WHERE ((oncal=2 AND enddate>0 AND enddate<2000000000) OR (oncal=1 AND startdate<2000000000 AND startdate>0)) AND (avail=1 OR (avail=2 AND startdate>0)) AND id IN ($typeids)");
			$stm->execute(array(':courseid'=>$cid));
		} else {
			$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail,text FROM imas_inlinetext WHERE ";
			$query .= "((avail=1 AND ((oncal=2 AND enddate>0 AND enddate<2000000000 AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>0))) OR ";
			$query .= "(avail=2 AND oncal=1 AND startdate<2000000000 AND startdate>0)) AND id IN ($typeids)";
			$stm = $DBH->query($query);
		}
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['title']=='##hidden##') {
				$row['title'] = preg_replace('/\s+/',' ',strip_tags($row['text']));
				if (strlen($row['title'])>25) {
					$row['title'] = substr($row['title'],0,25).'...';
				}
			}
			if ($row['oncal']==1) {
				$date = $row['startdate'];
			} else {
				$date = $row['enddate'];
			}
			$calevents[] = array('I'.$row['id'],$date, $row['title'],'');
		}
	}

	if (isset($itemlist['LinkedText'])) {
		$typeids = implode(',', array_keys($itemlist['LinkedText']));
		if (isset($teacherid) && !$stuview) {
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
			$calevents[] = array('L'.$row['id'],$date, $row['title'],'');
		}
	}

	if (isset($itemlist['Drill'])) {
		$typeids = implode(',', array_keys($itemlist['Drill']));
		if (isset($teacherid) && !$stuview) {
			$query = "SELECT id,name,enddate,startdate,caltag,avail FROM imas_drillassess WHERE (enddate>0 AND enddate<2000000000) AND avail=1 AND id IN ($typeids) ORDER BY name";
		} else {
			$query = "SELECT id ,name,enddate,startdate,caltag,avail FROM imas_drillassess WHERE ";
			$query .= "avail=1 AND (enddate>0 AND enddate<2000000000 AND startdate<$now) ";
			$query .= "AND id IN ($typeids) ORDER BY name";
		}
		$stm = $DBH->query($query);
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$calevents[] = array('D'.$row['id'],$row['enddate'], $row['name'], _('Drill Due: ').$row['name']);
		}
	}

	if (isset($itemlist['Forum'])) {
		$typeids = implode(',', array_keys($itemlist['Forum']));
		$query = "SELECT id,name,postby,replyby,startdate,enddate FROM imas_forums WHERE enddate>0 AND ((postby>0 AND postby<2000000000) OR (replyby>0 AND replyby<2000000000)) AND avail>0 AND id IN ($typeids) ORDER BY name";
		$stm = $DBH->query($query);
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['startdate']>$now && (!isset($teacherid) || $stuview)) {
				continue;
			}
			require_once("../includes/exceptionfuncs.php");
			list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepassP, $canuselatepassR, $row['postby'], $row['replyby'], $row['enddate']) = $exceptionfuncs->getCanUseLatePassForums(isset($forumexceptions[$row['id']])?$forumexceptions[$row['id']]:null, $row);

			if ($row['postby']!=2000000000) {
				$calevents[] = array('FP'.$row['id'],$row['postby'], $row['name'], _('Posts Due: ').$row['name']);
			}
			if ($row['replyby']!=2000000000) {
				$calevents[] = array('FR'.$row['id'],$row['postby'], $row['name'], _('Replies Due: ').$row['name']);
			}
		}
	}

	$stm = $DBH->prepare("SELECT title,tag,date,id FROM imas_calitems WHERE date>0 AND date<2000000000 and courseid=:courseid ORDER BY title");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$calevents[] = array('C'.$row['id'],$row['date'], $row['tag'], $row['title']);
	}

	return array($coursename,$calevents);
}

function flattenitems($items,&$addto,$viewall, $studentinfo) {
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
				flattenitems($item['items'],$addto,$viewall, $studentinfo);
			}
		} else {
			$addto[] = $item;
		}
	}
}
