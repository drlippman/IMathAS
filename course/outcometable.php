<?php
//IMathAS:  Outcomes report array generator
//(c) 2013 David Lippman for Lumen Learning

require_once("../includes/exceptionfuncs.php");

/***
format of output

row[0] header

row[0][0] biographical
row[0][0][0] = "Name"

Will only included items that are counted in gradebook.  No EC. No PT
row[0][1] scores
row[0][1][#][0] = name
row[0][1][#][1] = category color number
row[0][1][#][2] = 0 past, 1 current, 2 future
row[0][1][#][3] = 1 count, 2 EC
row[0][1][#][4] = 0 online, 1 offline, 2 discussion
row[0][1][#][5] = assessmentid, gbitems.id, forumid

row[0][2] category totals
row[0][2][#][0] = "Category Name"
row[0][2][#][1] = category color number

row[1] first student data row
row[1][0] biographical
row[1][0][0] = "Name"
row[1][0][1] = userid
row[1][0][2] = locked?

row[1][1] scores (all types - type is determined from header row)

row[1][1][#][0][outc#] = score on outcome
row[1][1][#][1][outc#] = poss score on outcome
row[1][1][#][2] = other info: 0 none, 1 NC, 2 IP, 3 OT, 4 PT  + 10 if still active
row[1][1][#][3] = asid or 'new', gradeid, or blank for discussions

row[1][2] category totals
row[1][2][#][0][outc#] = cat total past on outcome
row[1][2][#][1][outc#] = cat poss past on outcome
row[1][2][#][2][outc#] = cat total attempted on outcome
row[1][2][#][3][outc#] = cat poss attempted on outcome

row[1][3] total totals
row[1][3][0][outc#] = % past on outcome
row[1][3][1][outc#] = % attempted on outcome


***/

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) {
			return $sc;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) {
				$tot+=$s;
			}
		}
		return round($tot,1);
	}
}

function outcometable() {
	global $DBH,$cid,$isteacher,$istutor,$tutorid,$userid,$catfilter,$secfilter;
	global $timefilter,$lnfilter,$isdiag,$sel1name,$sel2name,$canviewall,$hidelocked;
	if ($canviewall && func_num_args()>0) {
		$limuser = func_get_arg(0);
		$exceptionfuncs = new ExceptionFuncs($limuser, $cid, false);
	} else if (!$canviewall) {
		$limuser = $userid;
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true);
	} else {
		$limuser = 0;
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, false);
	}

	$category = array();
	$outc = array();
	$gb = array();
	$ln = 0;

	//Pull Gradebook Scheme info
	$stm = $DBH->prepare("SELECT useweights,orderby,defaultcat,usersort FROM imas_gbscheme WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	list($useweights,$orderby,$defaultcat,$usersort) = $stm->fetch(PDO::FETCH_NUM);
	if ($useweights==2) {$useweights = 0;} //use 0 mode for calculation of totals

	if (isset($GLOBALS['setorderby'])) {
		$orderby = $GLOBALS['setorderby'];
	}

	//Build user ID headers
	$gb[0][0][0] = "Name";
	$stm = $DBH->prepare("SELECT count(id) FROM imas_students WHERE imas_students.courseid=:courseid AND imas_students.section IS NOT NULL");
	$stm->execute(array(':courseid'=>$cid));
	if ($stm->fetchColumn(0)>0) {
		$hassection = true;
	} else {
		$hassection = false;
	}
	//Pull Assessment Info
	$now = time();
	$query = "SELECT id,name,defpoints,deffeedback,timelimit,minscore,startdate,enddate,LPcutoff,itemorder,gbcategory,cntingb,avail,groupsetid,defoutcome,allowlate,viewingb,scoresingb,ver FROM imas_assessments WHERE courseid=:courseid AND avail>0 ";
	$query .= "AND cntingb>0 AND cntingb<3 ";
	$qarr = array(':courseid'=>$cid);
	if ($istutor) {
		$query .= "AND tutoredit<>2 ";
	}
	if ($catfilter>-1) {
		$query .= "AND gbcategory=:gbcategory ";
		$qarr[':gbcategory']=$catfilter;
	}
	$query .= "ORDER BY enddate,name";
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);

	$overallpts = 0;
	$now = time();
	$kcnt = 0;
	$assessments = array();
	$grades = array();
	$discuss = array();
	$startdate = array();
	$enddate = array();
	$LPcutoff = array();
	$allowlate = array();
	$timelimits = array();
	$avail = array();
	$category = array();
	$name = array();
	$possible = array();
	$courseorder = array();
	$qposs = array();
	$qoutcome = array();
	$itemoutcome = array();
	$assessmenttype = array();
	$sa = array();
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		if (substr($line['deffeedback'],0,8)=='Practice') { continue;}
		if ($line['avail']==2) {
			$line['startdate'] = 0;
			$line['enddate'] = 2000000000;
		}
		if ($now<$line['startdate']) {
			continue; //we don't want future items
		} else if ($now < $line['enddate']) {
			$avail[$kcnt] = 1;
		} else {
			$avail[$kcnt] = 0;
		}
		$enddate[$kcnt] = $line['enddate'];
		$startdate[$kcnt] = $line['startdate'];
		$allowlate[$kcnt] = $line['allowlate'];
		$LPcutoff[$kcnt] = $line['LPcutoff'];

		$timelimits[$kcnt] = $line['timelimit'];

		$assessments[$kcnt] = $line['id'];

		$category[$kcnt] = $line['gbcategory'];
		$name[$kcnt] = $line['name'];
		$cntingb[$kcnt] = $line['cntingb']; //1: count, 2: extra credit
		$assessoutcomes[$kcnt] = array();

		if ($line['ver'] > 1) {
			// For assess2,
			// $assessmenttype = viewingb setting
			// $sa = scoresingb setting
			$assessmenttype[$kcnt] = $line['viewingb'];
			$sa[$kcnt] = $line['scoresingb'];
		} else {
			$deffeedback = explode('-',$line['deffeedback']);
			$assessmenttype[$kcnt] = $deffeedback[0];
			$sa[$kcnt] = $deffeedback[1] ?? 'N';
		}

		$aitems = explode(',',$line['itemorder']);
		foreach ($aitems as $k=>$v) {
			if (strpos($v,'~')!==FALSE) {
				$sub = explode('~',$v);
				if (strpos($sub[0],'|')===false) { //backwards compat
					$aitems[$k] = $sub[0];
					$aitemcnt[$k] = 1;

				} else {
					$grpparts = explode('|',$sub[0]);
					$aitems[$k] = $sub[1];
					$aitemcnt[$k] = $grpparts[0];
				}
			} else {
				$aitemcnt[$k] = 1;
			}
		}
		$stm2 = $DBH->prepare("SELECT points,id,category FROM imas_questions WHERE assessmentid=:assessmentid");
		$stm2->execute(array(':assessmentid'=>$line['id']));
		$totalpossible = 0;
		while ($r = $stm2->fetch(PDO::FETCH_NUM)) {
			if ($r[0]==9999) {
				$qposs[$r[1]] = $line['defpoints'];
			} else {
				$qposs[$r[1]] = $r[0];
			}
			if (is_numeric($r[2]) && $r[2]>0) {
				$qoutcome[$r[1]] = $r[2];
			} else if ($line['defoutcome']>0) {
				$qoutcome[$r[1]] = $line['defoutcome'];
			}
		}
		$possible[$kcnt] = array();
		foreach ($aitems as $k=>$q) {
			if (!isset($qoutcome[$q])){ continue;}
			if (!isset($possible[$kcnt][$qoutcome[$q]])) {
				$possible[$kcnt][$qoutcome[$q]] = 0;
			}
			$possible[$kcnt][$qoutcome[$q]] += $aitemcnt[$k]*$qposs[$q];
		}
		$kcnt++;
	}

	//Pull Offline Grade item info
	$query = "SELECT * from imas_gbitems WHERE courseid=:courseid AND outcomes<>'' ";
	$query .= "AND showdate<:now ";
	$query .= "AND cntingb>0 AND cntingb<3 ";
	$qarr = array(':courseid'=>$cid, ':now'=>$now);

	if ($istutor) {
		$query .= "AND tutoredit<>2 ";
	}
	if ($catfilter>-1) {
		$query .= "AND gbcategory=:gbcategory ";
		$qarr[':gbcategory']=$catfilter;
	}
	$query .= "ORDER BY showdate";
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		$avail[$kcnt] = 0;

		$grades[$kcnt] = $line['id'];
		$assessmenttype[$kcnt] = "Offline";
		$category[$kcnt] = $line['gbcategory'];
		$enddate[$kcnt] = $line['showdate'];
		$startdate[$kcnt] = $line['showdate'];
		$possible[$kcnt] = $line['points'];
		$name[$kcnt] = $line['name'];
		$cntingb[$kcnt] = $line['cntingb'];
		$itemoutcome[$kcnt] = explode(',',$line['outcomes']);
		$kcnt++;
	}

		//Pull Discussion Grade info
	$query = "SELECT id,name,gbcategory,startdate,enddate,replyby,postby,points,cntingb,avail FROM imas_forums WHERE courseid=:courseid AND points>0 AND avail>0 ";
	$query .= "AND startdate<:now AND outcomes<>'' ";
	$qarr = array(':courseid'=>$cid, ':now'=>$now);

	if ($catfilter>-1) {
		$query .= "AND gbcategory=:gbcategory ";
		$qarr[':gbcategory']=$catfilter;
	}
	$query .= "ORDER BY enddate,postby,replyby,startdate";
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		$discuss[$kcnt] = $line['id'];
		$assessmenttype[$kcnt] = "Discussion";
		$category[$kcnt] = $line['gbcategory'];
		if ($line['avail']==2) {
			$line['startdate'] = 0;
			$line['enddate'] = 2000000000;
		}
		$enddate[$kcnt] = $line['enddate'];
		$startdate[$kcnt] = $line['startdate'];
		if ($now < $line['enddate']) {
			$avail[$kcnt] = 1;
			if ($line['replyby'] > 0 && $line['replyby'] < 2000000000) {
				if ($line['postby'] > 0 && $line['postby'] < 2000000000) {
					if ($now>$line['replyby'] && $now>$line['postby']) {
						$avail[$kcnt] = 0;
					}
				} else {
					if ($now>$line['replyby']) {
						$avail[$kcnt] = 0;
					}
				}
			} else if ($line['postby'] > 0 && $line['postby'] < 2000000000) {
				if ($now>$line['postby']) {
					$avail[$kcnt] = 0;
				}
			}
		} else {
			$avail[$kcnt] = 0;
		}
		$possible[$kcnt] = $line['points'];
		$name[$kcnt] = $line['name'];
		$cntingb[$kcnt] = $line['cntingb'];
		$itemoutcome[$kcnt] = explode(',',$line['outcomes']);
		$kcnt++;
	}

	$cats = array();
	$catcolcnt = 0;
	//Pull Categories:  Name, scale, scaletype, chop, drop, weight
	if (in_array(0,$category)) {  //define default category, if used
		$cats[0] = explode(',',$defaultcat);
		array_unshift($cats[0],"Default");
		array_push($cats[0],$catcolcnt);
		$catcolcnt++;

	}
	$query = "SELECT id,name,scale,scaletype,chop,dropn,weight,hidden FROM imas_gbcats WHERE courseid=:courseid ";
	$query .= "ORDER BY name";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if (in_array($row[0],$category)) { //define category if used
			if ($row[1][0]>='1' && $row[1][0]<='9') {
				$row[1] = substr($row[1],1);
			}
			$cats[$row[0]] = array_slice($row,1);
			array_push($cats[$row[0]],$catcolcnt);
			$catcolcnt++;
		}
	}

	//create item headers
	$pos = 0;
	$itemorder = array();
	$assesscol = array();
	$gradecol = array();
	$discusscol = array();
	if ($orderby==1) { //order $category by enddate
		asort($enddate,SORT_NUMERIC);
		$newcategory = array();
		foreach ($enddate as $k=>$v) {
			$newcategory[$k] = $category[$k];
		}
		$category = $newcategory;
	} else if ($orderby==5) { //order $category by enddate reverse
		arsort($enddate,SORT_NUMERIC);
		$newcategory = array();
		foreach ($enddate as $k=>$v) {
			$newcategory[$k] = $category[$k];
		}
		$category = $newcategory;
	} else if ($orderby==7) { //order $category by startdate
		asort($startdate,SORT_NUMERIC);
		$newcategory = array();
		foreach ($startdate as $k=>$v) {
			$newcategory[$k] = $category[$k];
		}
		$category = $newcategory;
	} else if ($orderby==9) { //order $category by startdate reverse
		arsort($startdate,SORT_NUMERIC);
		$newcategory = array();
		foreach ($startdate as $k=>$v) {
			$newcategory[$k] = $category[$k];
		}
		$category = $newcategory;
	} else if ($orderby==3) { //order $category alpha
		natcasesort($name);//asort($name);
		$newcategory = array();
		foreach ($name as $k=>$v) {
			$newcategory[$k] = $category[$k];
		}
		$category = $newcategory;
	}
	foreach(array_keys($cats) as $cat) {//foreach category
		$catkeys = array_keys($category,$cat); //pull items in that category
		if (($orderby&1)==1) { //order by category
			array_splice($itemorder,count($itemorder),0,$catkeys);
		}
		foreach ($catkeys as $k) {
			if (isset($cats[$cat][6]) && $cats[$cat][6]==1) {//hidden
				$cntingb[$k] = 0;
			}

			if (($orderby&1)==1) {  //display item header if displaying by category
				//$cathdr[$pos] = $cats[$cat][6];
				$gb[0][1][$pos][0] = $name[$k]; //item name
				$gb[0][1][$pos][1] = $cats[$cat][7]; //item category number
				$gb[0][1][$pos][2] = $avail[$k]; //0 past, 1 current, 2 future
				$gb[0][1][$pos][3] = $cntingb[$k]; //0 no count and hide, 1 count, 2 EC, 3 no count

				if (isset($assessments[$k])) {
					$gb[0][1][$pos][4] = 0; //0 online, 1 offline
					$gb[0][1][$pos][5] = $assessments[$k];
					$assesscol[$assessments[$k]] = $pos;
				} else if (isset($grades[$k])) {
					$gb[0][1][$pos][4] = 1; //0 online, 1 offline
					$gb[0][1][$pos][5] = $grades[$k];
					$gradecol[$grades[$k]] = $pos;
				} else if (isset($discuss[$k])) {
					$gb[0][1][$pos][4] = 2; //0 online, 1 offline, 2 discuss
					$gb[0][1][$pos][5] = $discuss[$k];
					$discusscol[$discuss[$k]] = $pos;
				}
				$gb[0][1][$pos][6] = array();
				$pos++;
			}
		}
	}
	if (($orderby&1)==0) {//if not grouped by category
		if ($orderby==0) {   //enddate
			asort($enddate,SORT_NUMERIC);
			$itemorder = array_keys($enddate);
		} else if ($orderby==2) {  //alpha
			natcasesort($name);//asort($name);
			$itemorder = array_keys($name);
		} else if ($orderby==4) { //enddate reverse
			arsort($enddate,SORT_NUMERIC);
			$itemorder = array_keys($enddate);
		} else if ($orderby==6) { //startdate
			asort($startdate,SORT_NUMERIC);
			$itemorder = array_keys($startdate);
		} else if ($orderby==8) { //startdate reverse
			arsort($startdate,SORT_NUMERIC);
			$itemorder = array_keys($startdate);
		}

		foreach ($itemorder as $k) {
			$gb[0][1][$pos][0] = $name[$k]; //item name
			$gb[0][1][$pos][1] = $cats[$category[$k]][7]; //item category name
			$gb[0][1][$pos][2] = $avail[$k]; //0 past, 1 current, 2 future
			$gb[0][1][$pos][3] = $cntingb[$k]; //0 no count and hide, 1 count, 2 EC, 3 no count
			if (isset($assessments[$k])) {
				$gb[0][1][$pos][4] = 0; //0 online, 1 offline
				$gb[0][1][$pos][5] = $assessments[$k];
				$assesscol[$assessments[$k]] = $pos;
			} else if (isset($grades[$k])) {
				$gb[0][1][$pos][4] = 1; //0 online, 1 offline
				$gb[0][1][$pos][5] = $grades[$k];
				$gradecol[$grades[$k]] = $pos;
			} else if (isset($discuss[$k])) {
				$gb[0][1][$pos][4] = 2; //0 online, 1 offline, 2 discuss
				$gb[0][1][$pos][5] = $discuss[$k];
				$discusscol[$discuss[$k]] = $pos;
			}
			$pos++;
		}
	}

	//create category headers
	$pos = 0;
	$catorder = array_keys($cats);
	foreach($catorder as $cat) {//foreach category
		$gb[0][2][$pos][0] = $cats[$cat][0];
		$gb[0][2][$pos][1] = $cats[$cat][7];
		$pos++;
	}

	//Pull student data
	$ln = 1;
	$query = "SELECT imas_users.id,imas_users.SID,imas_users.FirstName,imas_users.LastName,imas_users.SID,imas_users.email,imas_students.section,imas_students.code,imas_students.locked,imas_students.timelimitmult,imas_students.lastaccess,imas_users.hasuserimg ";
	$query .= "FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid AND imas_students.courseid=:courseid ";
	$qarr = array(':courseid'=>$cid);
	//$query .= "FROM imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND imas_teachers.courseid='$cid' ";
	//if (!$isteacher && !isset($tutorid)) {$query .= "AND imas_users.id='$userid' ";}
	if ($limuser>0) {
		$query .= "AND imas_users.id=:userid ";
		$qarr[':userid'] = $limuser;
	}
	if ($secfilter!=-1 && $limuser<=0) {
		$query .= "AND imas_students.section=:section ";
		$qarr[':section'] = $secfilter;
	}
	if ($hidelocked && $limuser==0) {
		$query .= "AND imas_students.locked=0 ";
	}
	if (isset($timefilter)) {
		$tf = time() - 60*60*$timefilter;
		$query .= "AND imas_users.lastaccess>:tf ";
		$qarr[':tf'] = $tf;
	}
	if (isset($lnfilter) && $lnfilter!='') {
		$query .= "AND imas_users.LastName LIKE :lnfilter ";
		$qarr['lnfilter'] = "$lnfilter%";
	}
	if ($isdiag) {
		$query .= "ORDER BY imas_users.email,imas_users.LastName,imas_users.FirstName";
	} else if ($hassection && $usersort==0) {
		$query .= "ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
	} else {
		$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
	}
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	$alt = 0;
    $sturow = array();
    $timelimitmult = [];
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		unset($asid); unset($pts); unset($IP); unset($timeused);
		$cattotpast[$ln] = array();
		$cattotpastec[$ln] = array();
		$catposspast[$ln] = array();
		$cattotcur[$ln] = array();
		$cattotcurec[$ln] = array();
		$catposscur[$ln] = array();

		//Student ID info
		$gb[$ln][0][0] = sprintf("%s,&nbsp;%s", Sanitize::encodeStringForDisplay($line['LastName']),
			Sanitize::encodeStringForDisplay($line['FirstName']));
		$gb[$ln][0][1] = $line['id'];
		$gb[$ln][0][2] = $line['locked'];


        $sturow[$line['id']] = $ln;
        $timelimitmult[$line['id']] = $line['timelimitmult'];
		$ln++;
	}

	//pull exceptions
	$exceptions = array();
	$query = "SELECT imas_exceptions.assessmentid,imas_exceptions.userid,imas_exceptions.startdate,imas_exceptions.enddate,imas_exceptions.islatepass FROM imas_exceptions,imas_assessments WHERE ";
	$query .= "imas_exceptions.itemtype='A' AND imas_exceptions.assessmentid=imas_assessments.id AND imas_assessments.courseid=:courseid";
	$stm2 = $DBH->prepare($query);
	$stm2->execute(array(':courseid'=>$cid));
	while ($r = $stm2->fetch(PDO::FETCH_NUM)) {
		if (!isset($sturow[$r[1]])) { continue;}
        $exceptions[$r[0]][$r[1]] = array($r[2],$r[3],$r[4]);
        if (isset($assesscol[$r[0]]) && isset($sturow[$r[1]])) {
            $gb[$sturow[$r[1]]][1][$assesscol[$r[0]]][2] = 10; //will get overwritten later if assessment session exists
        }
	}

	//Get assessment scores
	$assessidx = array_flip($assessments);
	$query = "SELECT ias.id,ias.assessmentid,ias.questions,ias.bestscores,ias.starttime,ias.endtime,ias.timeontask,ias.feedback,ias.userid FROM imas_assessment_sessions AS ias,imas_assessments AS ia ";
	$query .= "WHERE ia.id=ias.assessmentid AND ia.courseid=:courseid ";
	if ($limuser>0) {
		$query .= " AND ias.userid=:userid ";
	}
	$stm2 = $DBH->prepare($query);
	if ($limuser>0) {
		$stm2->execute(array(':courseid'=>$cid, ':userid'=>$limuser));
	} else {
		$stm2->execute(array(':courseid'=>$cid));
	}
	while ($l = $stm2->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($assessidx[$l['assessmentid']]) || !isset($sturow[$l['userid']]) || !isset($assesscol[$l['assessmentid']])) {
			continue;
		}
		$i = $assessidx[$l['assessmentid']];
		$row = $sturow[$l['userid']];
		$col = $assesscol[$l['assessmentid']];

		$gb[$row][1][$col][3] = $l['id'];; //assessment session id

		if (strpos($l['questions'],';')===false) {
			$questions = explode(",",$l['questions']);
		} else {
			list($questions,$bestquestions) = explode(";",$l['questions']);
			$questions = explode(",",$bestquestions);
		}
		$sp = explode(';',$l['bestscores']);
		$scores = explode(",",$sp[0]);
		$pts = array();
		$ptsposs = array();
		for ($j=0;$j<count($scores);$j++) {
			if (!isset($qoutcome[$questions[$j]])) {continue; } //no outcome set - skip it
			if (!isset($pts[$qoutcome[$questions[$j]]])) {
				$pts[$qoutcome[$questions[$j]]] = 0;
			}
			if (!isset($ptsposs[$qoutcome[$questions[$j]]])) {
				$ptsposs[$qoutcome[$questions[$j]]] = 0;
			}
			$pts[$qoutcome[$questions[$j]]] += getpts($scores[$j]);
			$ptsposs[$qoutcome[$questions[$j]]] += $qposs[$questions[$j]];
		}

		if (in_array(-1,$scores)) {
			$IP=1;
		} else {
			$IP=0;
		}
		$useexception = false;
		if (isset($exceptions[$l['assessmentid']][$l['userid']])) {
			$useexception = $exceptionfuncs->getCanUseAssessException($exceptions[$l['assessmentid']][$l['userid']], array('startdate'=>$startdate[$i], 'enddate'=>$enddate[$i], 'allowlate'=>$allowlate[$i], 'LPcutoff'=>$LPcutoff[$i]), true);
		}

		if ($useexception) {
			if ($enddate[$i]>$exceptions[$l['assessmentid']][$l['userid']][1] && $assessmenttype[$i]=="NoScores") {
				//if exception set for earlier, and NoScores is set, use later date to hide score until later
				$thised = $enddate[$i];
			} else {
				$thised = $exceptions[$l['assessmentid']][$l['userid']][1];
				if ($limuser>0) {  //change $avail past/cur/future
					if ($now<$thised && $now>$exceptions[$l['assessmentid']][$l['userid']][0]) { //inside exception window
						$gb[0][1][$col][2] = 1;
					} else if ($now>$thised) { //past exception due date
						$gb[0][1][$col][2] = 0;
					} else {
						$gb[0][1][$col][2] = 2;
					}
				}
			}
			$inexception = true;
		} else {
			$thised = $enddate[$i];
			$inexception = false;
		}


		$countthisone = false;
		$gb[$row][1][$col][1] = $ptsposs;

		if ($assessmenttype[$i]=="NoScores" && $sa[$i]!="I" && $now<$thised && !$canviewall) {
			$gb[$row][1][$col][0] = 'N/A'; //score is not available
			$gb[$row][1][$col][2] = 0;  //no other info
		} /*else if (($minscores[$i]<10000 && $pts<$minscores[$i]) || ($minscores[$i]>10000 && $pts<($minscores[$i]-10000)/100*$possible[$i])) {
			if ($canviewall) {
				$gb[$row][1][$col][0] = $pts; //the score
				$gb[$row][1][$col][2] = 1;  //no credit
			} else {
				$gb[$row][1][$col][0] = 'NC'; //score is No credit
				$gb[$row][1][$col][2] = 1;  //no credit
			}
		} else if ($IP==1 && $thised>$now && (($timelimits[$i]==0) || ($timeused < $timelimits[$i]*$timelimitmult[$l['userid']]))) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][2] = 2;  //in progress
			$countthisone =true;
		} else if (($timelimits[$i]>0) && ($timeused > $timelimits[$i]*$timelimitmult[$l['userid']])) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][2] = 3;  //over time
		}*/ else if ($assessmenttype[$i]=="Practice") {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][2] = 4;  //practice test
		} else { //regular score available to students
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][2] = 0;  //no other info
			$countthisone =true;
		}
		if ($now < $thised) { //still active
			$gb[$row][1][$col][2] += 10;
		}
		if ($countthisone) {
			foreach ($pts as $oc=>$pv) {
				if ($cntingb[$i] == 1) {
					if ($gb[0][1][$col][2]<1) { //past
						$cattotpast[$row][$category[$i]][$oc][$col] = $pv;
						$catposspast[$row][$category[$i]][$oc][$col] = $ptsposs[$oc];
					}
					if ($gb[0][1][$col][2]<2) { //past or cur
						$cattotcur[$row][$category[$i]][$oc][$col] = $pv;
						$catposscur[$row][$category[$i]][$oc][$col] = $ptsposs[$oc];
					}

				} else if ($cntingb[$i] == 2) {
					if ($gb[0][1][$col][2]<1) { //past
						$cattotpastec[$row][$category[$i]][$oc][$col] = $pv;
					}
					if ($gb[0][1][$col][2]<2) { //past or cur
						$cattotcurec[$row][$category[$i]][$oc][$col] = $pv;
					}
				}
			}
		}
	}

	//Get assessment2 scores
	$query = "SELECT iar.assessmentid,iar.score,iar.starttime,iar.lastchange,iar.timeontask,iar.status,iar.userid,iar.scoreddata FROM imas_assessment_records AS iar ";
	$query .= "JOIN imas_assessments AS ia ON ia.id=iar.assessmentid WHERE ia.courseid=:courseid ";
	if ($limuser>0) {
		$query .= " AND iar.userid=:userid ";
	}
	$stm2 = $DBH->prepare($query);
	if ($limuser>0) {
		$stm2->execute(array(':courseid'=>$cid, ':userid'=>$limuser));
	} else {
		$stm2->execute(array(':courseid'=>$cid));
	}
	while ($l = $stm2->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($assessidx[$l['assessmentid']]) || !isset($sturow[$l['userid']]) || !isset($assesscol[$l['assessmentid']])) {
			continue;
		}
		$i = $assessidx[$l['assessmentid']];
		$row = $sturow[$l['userid']];
		$col = $assesscol[$l['assessmentid']];

		$gb[$row][1][$col][3] = $l['userid'];; //in place of assessment session id

		$scoreddata = json_decode(gzdecode($l['scoreddata']), true);
		$assessver = $scoreddata['assess_versions'][$scoreddata['scored_version']];
		$pts = array();
		$ptsposs = array();
		foreach ($assessver['questions'] as $qn => $qdata) {
			$qver = $qdata['question_versions'][$qdata['scored_version']];
			$qid = $qver['qid'];
			if (!isset($qoutcome[$qid])) { continue; } //no outcome set - skip it
			if (!isset($pts[$qoutcome[$qid]])) {
				$pts[$qoutcome[$qid]] = 0;
			}
			$pts[$qoutcome[$qid]] += $qdata['score'];
			if (!isset($ptsposs[$qoutcome[$qid]])) {
				$ptsposs[$qoutcome[$qid]] = 0;
			}
			$ptsposs[$qoutcome[$qid]] += $qposs[$qid];
		}

		$useexception = false;
		if (isset($exceptions[$l['assessmentid']][$l['userid']])) {
			$useexception = $exceptionfuncs->getCanUseAssessException($exceptions[$l['assessmentid']][$l['userid']], array('startdate'=>$startdate[$i], 'enddate'=>$enddate[$i], 'allowlate'=>$allowlate[$i], 'LPcutoff'=>$LPcutoff[$i]), true);
		}

		if ($useexception) {
			if ($enddate[$i]>$exceptions[$l['assessmentid']][$l['userid']][1] && $sa[$i]=="never") {
				//if exception set for earlier, and NoScores is set, use later date to hide score until later
				$thised = $enddate[$i];
			} else {
				$thised = $exceptions[$l['assessmentid']][$l['userid']][1];
				if ($limuser>0) {  //change $avail past/cur/future
					if ($now<$thised && $now>$exceptions[$l['assessmentid']][$l['userid']][0]) { //inside exception window
						$gb[0][1][$col][2] = 1;
					} else if ($now>$thised) { //past exception due date
						$gb[0][1][$col][2] = 0;
					} else {
						$gb[0][1][$col][2] = 2;
					}
				}
			}
			$inexception = true;
		} else {
			$thised = $enddate[$i];
			$inexception = false;
		}

		if (($l['status']&3)>0 && $thised>$now) {
			$IP=1;
		} else {
			$IP=0;
		}

		$countthisone = false;
		$gb[$row][1][$col][1] = $ptsposs;

		$hasSubmittedTake = ($l['status']&64)>0;

		if (!$canviewall && (
			($sa[$i]=="never") ||
		 	($sa[$i]=='after_due' && $now < $thised) ||
			($sa[$i]=='after_take' && !$hasSubmittedTake)
		)) {
			$gb[$row][1][$col][0] = 'N/A'; //score is not available
			$gb[$row][1][$col][2] = 0;  //no other info
		}/* else if (($minscores[$i]<10000 && $pts<$minscores[$i]) || ($minscores[$i]>10000 && $pts<($minscores[$i]-10000)/100*$possible[$i])) {
		//else if ($pts<$minscores[$i]) {
			if ($canviewall) {
				$gb[$row][1][$col][0] = $pts; //the score
				$gb[$row][1][$col][2] = 1;  //no credit
			} else {
				$gb[$row][1][$col][0] = 'NC'; //score is No credit
				$gb[$row][1][$col][2] = 1;  //no credit
			}
		}*/ else if ($IP==1) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][2] = 2;  //in progress
			$countthisone =true;
		} else if (($l['status']&4)>0) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][2] = 3;  //over time
		} else { //regular score available to students
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][2] = 0;  //no other info
			$countthisone =true;
		}
		if ($now < $thised) { //still active
			$gb[$row][1][$col][2] += 10;
		}
		if ($countthisone) {
			foreach ($pts as $oc=>$pv) {
				if ($cntingb[$i] == 1) {
					if ($gb[0][1][$col][2]<1) { //past
						$cattotpast[$row][$category[$i]][$oc][$col] = $pv;
						$catposspast[$row][$category[$i]][$oc][$col] = $ptsposs[$oc];
					}
					if ($gb[0][1][$col][2]<2) { //past or cur
						$cattotcur[$row][$category[$i]][$oc][$col] = $pv;
						$catposscur[$row][$category[$i]][$oc][$col] = $ptsposs[$oc];
					}

				} else if ($cntingb[$i] == 2) {
					if ($gb[0][1][$col][2]<1) { //past
						$cattotpastec[$row][$category[$i]][$oc][$col] = $pv;
					}
					if ($gb[0][1][$col][2]<2) { //past or cur
						$cattotcurec[$row][$category[$i]][$oc][$col] = $pv;
					}
				}
			}
		}
	}

	//Get other grades
	$gradeidx = array_flip($grades);
	unset($gradeid); unset($opts);
	unset($discusspts);
	$discussidx = array_flip($discuss);
	$gradetypeselects = array();
	if (count($grades)>0) {
		$gradeidlist = implode(',',$grades);
		$gradetypeselects[] = "(gradetype='offline' AND gradetypeid IN ($gradeidlist))";
	}
	if (count($discuss)>0) {
		$forumidlist = implode(',',$discuss);
		$gradetypeselects[] = "(gradetype='forum' AND gradetypeid IN ($forumidlist))";
	}
	if (count($gradetypeselects)>0) {
		$sel = implode(' OR ',$gradetypeselects);
		//$query = "SELECT imas_grades.gradetypeid,imas_grades.gradetype,imas_grades.refid,imas_grades.id,imas_grades.score,imas_grades.feedback,imas_grades.userid FROM imas_grades,imas_gbitems WHERE ";
		//$query .= "imas_grades.gradetypeid=imas_gbitems.id AND imas_gbitems.courseid='$cid'";
		if ($limuser>0) {
			$stm2 = $DBH->prepare("SELECT * FROM imas_grades WHERE ($sel) AND userid=:userid ");
			$stm2->execute(array(':userid'=>$limuser));
		} else {
			$stm2 = $DBH->query("SELECT * FROM imas_grades WHERE ($sel)");
		}
		while ($l = $stm2->fetch(PDO::FETCH_ASSOC)) {
			if ($l['gradetype']=='offline') {
				if (!isset($gradeidx[$l['gradetypeid']]) || !isset($sturow[$l['userid']]) || !isset($gradecol[$l['gradetypeid']])) {
					continue;
				}
				$i = $gradeidx[$l['gradetypeid']];
				$row = $sturow[$l['userid']];
				$col = $gradecol[$l['gradetypeid']];
				foreach ($itemoutcome[$i] as $oc) {

					$gb[$row][1][$col][3] = $l['id'];
					if ($l['score']!=null) {
						$gb[$row][1][$col][0][$oc] = 1*$l['score'];
						$gb[$row][1][$col][1][$oc] = $possible[$i];
					}

					if ($cntingb[$i] == 1) {
						if ($gb[0][1][$col][2]<1) { //past
							$cattotpast[$row][$category[$i]][$oc][$col] = 1*$l['score'];
							$catposspast[$row][$category[$i]][$oc][$col] = $possible[$i];
						}
						if ($gb[0][1][$col][2]<2) { //past or cur
							$cattotcur[$row][$category[$i]][$oc][$col] = 1*$l['score'];
							$catposscur[$row][$category[$i]][$oc][$col] = $possible[$i];
						}
					} else if ($cntingb[$i]==2) {
						if ($gb[0][1][$col][2]<1) { //past
							$cattotpastec[$row][$category[$i]][$oc][$col] = 1*$l['score'];
						}
						if ($gb[0][1][$col][2]<2) { //past or cur
							$cattotcurec[$row][$category[$i]][$oc][$col] = 1*$l['score'];
						}
					}
				}
			} else if ($l['gradetype']=='forum') {
				if (!isset($discussidx[$l['gradetypeid']]) || !isset($sturow[$l['userid']]) || !isset($discusscol[$l['gradetypeid']])) {
					continue;
				}
				$i = $discussidx[$l['gradetypeid']];
				$row = $sturow[$l['userid']];
				$col = $discusscol[$l['gradetypeid']];
				foreach ($itemoutcome[$i] as $oc) {

					if ($l['score']!=null) {
						if (isset($gb[$row][1][$col][0])) {
							$gb[$row][1][$col][0][$oc] += 1*$l['score']; //adding up all forum scores
						} else {
							$gb[$row][1][$col][0][$oc] = 1*$l['score'];
						}
					}

					if ($gb[0][1][$col][2]<1) { //past
						$cattotpast[$row][$category[$i]][$oc][$col] = $gb[$row][1][$col][0];
						$catposspast[$row][$category[$i]][$oc][$col] = $possible[$i];
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcur[$row][$category[$i]][$oc][$col] = $gb[$row][1][$col][0];
						$catposscur[$row][$category[$i]][$oc][$col] = $possible[$i];
					}
				}
			}
		}
	}

	//create category totals
	for ($ln = 1; $ln<count($sturow)+1;$ln++) { //foreach student calculate category totals and total totals

		//zero out past due items
		foreach($gb[0][1] as $col=>$inf) {
			if ($gb[0][1][$col][2]>0 || (isset($gb[$ln][1][$col][1]) && count($gb[$ln][1][$col][1])>0)) {continue;} //skip if current, or if already set
			if ($inf[4]==0 && count($possible[$assessidx[$inf[5]]])==0) {continue;} //assess has no outcomes

			$gb[$ln][1][$col] = array();
			$gb[$ln][1][$col][0] = array();
			$gb[$ln][1][$col][1] = array();
			if ($inf[4]==0) { //online item
				$i = $assessidx[$inf[5]];
				foreach ($possible[$i] as $oc=>$p) {
					$gb[$ln][1][$col][0][$oc] = 0;
					$gb[$ln][1][$col][1][$oc] = $p;
					$cattotpast[$ln][$category[$i]][$oc][$col] = 0;
					$catposspast[$ln][$category[$i]][$oc][$col] = $p;
					$cattotcur[$ln][$category[$i]][$oc][$col] = 0;
					$catposscur[$ln][$category[$i]][$oc][$col] = $p;
				}
				$gb[$ln][1][$col][3] = 0;
				$gb[$ln][1][$col][4] = 'new';
			} else { //offline or discussion
				if ($inf[4]==1) {
					$i = $gradeidx[$inf[5]];
				} else if ($inf[4]==2) {
					$i = $discussidx[$inf[5]];
				}
				foreach ($itemoutcome[$i] as $oc) {
					$gb[$ln][1][$col][0][$oc] = 0;
					$gb[$ln][1][$col][1][$oc] = $possible[$i];
					$cattotpast[$ln][$category[$i]][$oc][$col] = 0;
					$catposspast[$ln][$category[$i]][$oc][$col] = $possible[$i];
					$cattotcur[$ln][$category[$i]][$oc][$col] = 0;
					$catposscur[$ln][$category[$i]][$oc][$col] = $possible[$i];
				}
			}
		}

		$totpast = array();
		$totposspast = array();
		$totcur = array();
		$totposscur = array();
		$pos = 0; //reset position for category totals

		foreach($catorder as $cat) {//foreach category
			//add up scores for each outcome
			if (isset($cattotpast[$ln][$cat])) {
				foreach ($cattotpast[$ln][$cat] as $oc=>$scs) {
					$cattotpast[$ln][$cat][$oc] = array_sum($scs);
					if (isset($cattotpastec[$ln][$cat][$oc])) {
						$cattotpast[$ln][$cat][$oc] += array_sum($cattotpastec[$ln][$cat][$oc]);
					}
					$catposspast[$ln][$cat][$oc] = array_sum($catposspast[$ln][$cat][$oc]);

					$gb[$ln][2][$pos][0][$oc] = $cattotpast[$ln][$cat][$oc];
					$gb[$ln][2][$pos][1][$oc] = $catposspast[$ln][$cat][$oc];

					if (!isset($totpast[$oc])) {
						$totpast[$oc] = 0;
						$totposspast[$oc] = 0;
					}
					if ($useweights==1 && $catposspast[$ln][$cat][$oc]>0) {
						$totposspast[$oc] += $cats[$cat][5]/100;
						$totpast[$oc] += $cattotpast[$ln][$cat][$oc]*$cats[$cat][5]/(100*$catposspast[$ln][$cat][$oc]);
					} else if ($useweights==0) {
						$totposspast[$oc] += $catposspast[$ln][$cat][$oc];
						$totpast[$oc] += $cattotpast[$ln][$cat][$oc];
					}
				}
			}
			if (isset($cattotcur[$ln][$cat])) {
				foreach ($cattotcur[$ln][$cat] as $oc=>$scs) {
					$cattotcur[$ln][$cat][$oc] = array_sum($scs);
					if (isset($cattotcurec[$ln][$cat][$oc])) {
						$cattotcur[$ln][$cat][$oc] += array_sum($cattotcurec[$ln][$cat][$oc]);
					}

					$catposscur[$ln][$cat][$oc] = array_sum($catposscur[$ln][$cat][$oc]);

					$gb[$ln][2][$pos][2][$oc] = $cattotcur[$ln][$cat][$oc];
					$gb[$ln][2][$pos][3][$oc] = $catposscur[$ln][$cat][$oc];

					if (!isset($totcur[$oc])) {
						$totcur[$oc] = 0;
						$totposscur[$oc] = 0;
					}
					if ($useweights==1 && $catposscur[$ln][$cat][$oc]>0) {
						$totposscur[$oc] += $cats[$cat][5]/100;
						$totcur[$oc] += $cattotcur[$ln][$cat][$oc]*$cats[$cat][5]/(100*$catposscur[$ln][$cat][$oc]);
					} else if ($useweights==0) {
						$totposscur[$oc] += $catposscur[$ln][$cat][$oc];
						$totcur[$oc] += $cattotcur[$ln][$cat][$oc];
					}
				}
			}
			$pos++;
		}

		foreach ($totpast as $oc=>$v) {
			if ($totposspast[$oc]>0) {
				$gb[$ln][3][0][$oc] = $totpast[$oc]/$totposspast[$oc];
			}
		}
		foreach ($totcur as $oc=>$v) {
			if ($totposscur[$oc]>0) {
				$gb[$ln][3][1][$oc] = $totcur[$oc]/$totposscur[$oc];
			}
		}
	}
	if ($limuser<1) {
		$gb[$ln][0][0] = "Averages";
		$gb[$ln][0][1] = -1;
		foreach ($gb[0][1] as $i=>$inf) {
			$avg = array();  $avgposs = array();
			for ($j=1;$j<$ln;$j++) {
				if (isset($gb[$j][1][$i]) && isset($gb[$j][1][$i][0])) {
					foreach ($gb[$j][1][$i][0] as $oc=>$sc) {
						if (!isset($avg[$oc])) { $avg[$oc] = array(); $avgposs[$oc] = array();}
						$avg[$oc][] = $sc;
						$avgposs[$oc][] = $gb[$j][1][$i][1][$oc];
					}
				}
			}
			foreach ($avg as $oc=>$scs) {
				$gb[$ln][1][$i][0][$oc] = array_sum($avg[$oc])/count($avg[$oc]);
				$gb[$ln][1][$i][1][$oc] = array_sum($avgposs[$oc])/count($avg[$oc]);
			}
		}
		foreach ($gb[0][2] as $i=>$inf) {
			$avg = array();  $avgposs = array();
			$avgatt = array();  $avgattposs = array();
			for ($j=1;$j<$ln;$j++) {
				if (isset($gb[$j][2][$i]) && isset($gb[$j][2][$i][0])) {
					foreach ($gb[$j][2][$i][0] as $oc=>$sc) {
						if (!isset($avg[$oc])) { $avg[$oc] = array(); $avgposs[$oc] = array();}
						$avg[$oc][] = $sc;
						$avgposs[$oc][] = $gb[$j][2][$i][1][$oc];
					}
				}
				if (isset($gb[$j][2][$i]) && isset($gb[$j][2][$i][2])) {
					foreach ($gb[$j][2][$i][2] as $oc=>$sc) {
						if (!isset($avgatt[$oc])) { $avgatt[$oc] = array(); $avgpossatt[$oc] = array();}
						$avgatt[$oc][] = $sc;
						$avgattposs[$oc][] = $gb[$j][2][$i][3][$oc];
					}
				}
			}
			foreach ($avg as $oc=>$scs) {
				$gb[$ln][2][$i][0][$oc] = array_sum($avg[$oc])/count($avg[$oc]);
				$gb[$ln][2][$i][1][$oc] = array_sum($avgposs[$oc])/count($avg[$oc]);
			}
			foreach ($avgatt as $oc=>$scs) {
				$gb[$ln][2][$i][2][$oc] = array_sum($avgatt[$oc])/count($avgatt[$oc]);
				$gb[$ln][2][$i][3][$oc] = array_sum($avgattposs[$oc])/count($avgatt[$oc]);
			}
		}
		$avg = array();  $avgatt = array();
		for ($j=1;$j<$ln;$j++) {
			if (isset($gb[$j][3][0])) {
				foreach ($gb[$j][3][0] as $oc=>$sc) {
					if (!isset($avg[$oc])) { $avg[$oc] = array();}
					$avg[$oc][] = $sc;
				}
			}
			if (isset($gb[$j][3][1])) {
				foreach ($gb[$j][3][1] as $oc=>$sc) {
					if (!isset($avgatt[$oc])) { $avgatt[$oc] = array();}
					$avgatt[$oc][] = $sc;
				}
			}
		}
		foreach ($avg as $oc=>$scs) {
			$gb[$ln][3][0][$oc] = array_sum($avg[$oc])/count($avg[$oc]);
		}
		foreach ($avgatt as $oc=>$scs) {
			$gb[$ln][3][1][$oc] = array_sum($avgatt[$oc])/count($avgatt[$oc]);
		}
	}
	if ($limuser==-1) {
		$gb[1] = $gb[$ln];
	}
	return $gb;

}


?>
