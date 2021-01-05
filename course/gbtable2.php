<?php
//IMathAS: gradebook table generating function
//(c) 2007 David Lippman


require_once("../includes/exceptionfuncs.php");

if ($GLOBALS['canviewall']) {
	$GLOBALS['exceptionfuncs'] = new ExceptionFuncs($userid, $cid, false);
} else {
	$GLOBALS['exceptionfuncs'] = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
}

require_once("../includes/sanitize.php");

//used by gbtable
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

//determine if diagnostic - used in gradebook too
$isdiag = false;
if ($canviewall) {
	$stm = $DBH->prepare("SELECT sel1name,sel2name FROM imas_diags WHERE cid=:cid");
	$stm->execute(array(':cid'=>$cid));
	if ($stm->rowCount()>0) {
		$isdiag = true;
		list($sel1name, $sel2name) = $stm->fetch(PDO::FETCH_NUM);
		if ($sel1name[0]=='!') {
			$sel1name = substr($sel1name,1);
		}
		if ($sel2name[0]=='!') {
			$sel2name = substr($sel2name,1);
		}
	}
}

/****
The super-nasty gradebook function!
gbtable([userid])
Student: automatically limits to their userid
Teacher: gives all students unless userid is provided

Format of output:
row[0] header

row[0][0] biographical
row[0][0][0] = "Name"
row[0][0][1] = "SID"

row[0][1] scores
row[0][1][0] first score
row[0][1][0][0] = "Assessment name"
row[0][1][0][1] = category color #  (or gbcat ID if $includecategoryID is set)
row[0][1][0][2] = points possible
row[0][1][0][3] = 0 past, 1 current, 2 future
row[0][1][0][4] = 0 no count and hide, 1 count, 2 EC, 3 no count
row[0][1][0][5] = 0 regular, 1 practice test
row[0][1][0][6] = 0 online, 1 offline, 2 discussion, 3 exttool
row[0][1][0][7] = assessmentid, gbitems.id, forumid, linkedtext.id
row[0][1][0][8] = tutoredit: 0 no, 1 yes
row[0][1][0][9] = 5 number summary, if not limuser-ed
row[0][1][0][10] = 0 regular, 1 group
row[0][1][0][11] = due date (if $includeduedate is set)
row[0][1][0][12] = allowlate (in general)
row[1][1][0][13] = timelimit if requested through $includetimelimit
row[0][1][0][14] = LP cutoff
row[0][1][0][15] = assess UI version (online only)
row[0][1][0][16] = accepts work after assess
row[0][1][0][17] = section limit, if any 

row[0][2] category totals
row[0][2][0][0] = "Category Name"
row[0][2][0][1] = Category color #
row[0][2][0][2] = 0 if any scores in past, 1 if any scores in past/current, 2 if all scores in future
		  3 no items at all
row[0][2][0][3] = total possible for past
row[0][2][0][4] = total possible for past/current
row[0][2][0][5] = total possible for all
row[0][2][0][6-9] = 5 number summary
row[0][2][0][10] = gbcat id
row[0][2][0][11] = category weight (if weighted grades)
row[0][2][0][12] = default show (0 expanded, 2 collapsed)
row[0][2][0][13] = calctype
row[0][2][0][14] = hasdrops

row[0][3][0] = total possible past
row[0][3][1] = total possible past&current
row[0][3][2] = total possible all
row[0][3][3-6] = 5 number summary

row[0][4][0] = useweights (0 points, 1 weighted)

row[1] first student data row
row[1][0] biographical
row[1][0][0] = "Name"

row[1][1] scores (all types - type is determined from header row)
row[1][1][0] first score - assessment
row[1][1][0][0] = score
row[1][1][0][1] = 0 no comment, 1 has comment - is comment in includecomments
row[1][1][0][2] = show gbviewasid link: 0 no, 1 yes,
row[1][1][0][3] = other info: 0 none, 1 NC, 2 IP, 3 OT, 4 PT, 5 UA  + 10 if still active
row[1][1][0][4] = asid, or 'new' (or userid for assess2)
row[1][1][0][5] = bitwise for dropped: 1 in past & 2 in cur & 4 in future & 8 attempted
row[1][1][0][6] = 1 if had exception, = 2 if was latepass
row[1][1][0][7] = time spent (minutes)
row[1][1][0][8] = time on task (time displayed)
row[1][1][0][9] = last change time (if $includelastchange is set)
row[1][1][0][10] = allow latepass use on this item
row[1][1][0][11] = endmsg if requested through $includeendmsg
row[1][1][0][13] = 1 if no reqscore or has been met, 0 if unmet reqscore; only in single stu view ($limuser>0)
row[1][1][0][14] = 1 if excused

row[1][1][1] = offline
row[1][1][1][0] = score
row[1][1][1][1] = 0 no comment, 1 has comment - is comment in stu view
row[1][1][1][2] = gradeid
row[1][1][1][14] = 1 if excused

row[1][1][2] - discussion
row[1][1][2][0] = score
row[1][1][2][14] = 1 if excused

row[1][2] category totals
row[1][2][0][0] = cat total past
row[1][2][0][1] = cat total past/current
row[1][2][0][2] = cat total future
row[1][2][0][3] = cat total attempted
   OLD: row[1][2][0][4] = cat poss attempted
row[1][2][0][4] = cat poss past
row[1][2][0][5] = cat poss past/current
row[1][2][0][6] = cat poss future
row[1][2][0][7] = cat poss attempted

row[1][3] total totals
row[1][3][0] = total earned past
row[1][3][1] = total earned past&current
row[1][3][2] = total earned all
row[1][3][3] = total earned attempted
row[1][3][4] = total poss past
row[1][3][5] = total poss past&current
row[1][3][6] = total poss all
row[1][3][7] = total poss attempted


row[1][4][0] = userid
row[1][4][1] = locked?
row[1][4][2] = hasuserimg
row[1][4][3] = has gradebook comment
row[1][4][4] = timelimitmult if requested through $includetimelimit

cats[i]:  0: name, 1: scale, 2: scaletype, 3: chop, 4: dropn, 5: weight, 6: hidden, 7: calctype

****/
function flattenitems($items,&$addto,&$itemidsection,$sec='') {
	global $canviewall,$secfilter,$studentinfo;

	$now = time();
	foreach ($items as $item) {
		if (is_array($item)) {
			if (!isset($item['avail'])) { //backwards compat
				$item['avail'] = 1;
            }
            $thissec = $sec;
            $ishidden = ($item['avail']==0 || (!$canviewall && $item['avail']==1 && $item['SH'][0]=='H' && $item['startdate']>$now));
            if (!empty($item['grouplimit'])) {
                $thissec = substr($item['grouplimit'][0],2); // trim off s-
                if ((!$canviewall && $studentinfo['section'] != $thissec) ||
                    ($canviewall && $secfilter != -1 && $secfilter != $thissec)
                ) {
                    // if a section limited block, and not in/showing that sec, hide
                    $ishidden = true;
                }
            } 
			if (!$ishidden) {
				flattenitems($item['items'], $addto, $itemidsection, $thissec);
			}
		} else {
            if ($sec != '') {
                $itemidsection[$item] = $sec;
            }
			$addto[] = $item;
		}
	}
}

function gbtable() {
	global $DBH,$cid,$isteacher,$istutor,$tutorid,$userid,$catfilter,$secfilter,$timefilter,$lnfilter,$isdiag;
	global $sel1name,$sel2name,$canviewall,$lastlogin,$logincnt,$hidelocked,$latepasshrs,$includeendmsg;
	global $hidesection,$hidecode,$exceptionfuncs,$courseenddate;

	if (!isset($hidesection)) {$hidesection = false;}
	if (!isset($hidecode)) {$hidecode= false;}
	if (!isset($courseenddate)) {$courseenddate=2000000000;}

	if ($canviewall && func_num_args()>0) {
		$limuser = func_get_arg(0);
	} else if (!$canviewall) {
		$limuser = $userid;
	} else {
		$limuser = 0;
	}
	if (!isset($lastlogin)) {
		$lastlogin = 0;
	}
	if (!isset($logincnt)) {
		$logincnt = 0;
	}

	$category = array();
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

	$gb[0] = [[],[],[],[],[]];
	//Build user ID headers
	$gb[0][0][0] = "Name";
	if ($isdiag) {
		$gb[0][0][1] = "ID";
		$gb[0][0][2] = "Term";
		$gb[0][0][3] = ucfirst($sel1name);
		$gb[0][0][4] = ucfirst($sel2name);
	} else {
		$gb[0][0][1] = "Username";
	}

	//store useweights
	$gb[0][4][0] = $useweights;

	$stm = $DBH->prepare("SELECT count(DISTINCT section),count(DISTINCT code) FROM imas_students WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$row = $stm->fetch(PDO::FETCH_NUM);
	$hassection = ($row[0]>0 && !$hidesection);
	$hascode = ($row[1]>0 && !$hidecode);

	if ($hassection && !$isdiag) {
		$gb[0][0][] = "Section";
	}
	if ($hascode) {
		$gb[0][0][] = "Code";
	}
	if ($lastlogin) {
		$gb[0][0][] = "Last Login";
	}
	if ($logincnt) {
		$gb[0][0][] = "Login Count";
	}



	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$courseitemorder = unserialize($stm->fetchColumn(0));
	$courseitemsimporder = array();
    $courseitemsassoc = array();
    $itemidsection = array();
    $itemsection = array();

	flattenitems($courseitemorder,$courseitemsimporder,$itemidsection);
	if (count($courseitemsimporder)>0) {
		$ph = Sanitize::generateQueryPlaceholders($courseitemsimporder);
		$stm = $DBH->prepare("SELECT id,itemtype,typeid FROM imas_items WHERE id IN ($ph)");
		$stm->execute($courseitemsimporder);

		$courseitemsimporder = array_flip($courseitemsimporder);

		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $courseitemsassoc[$row[1].$row[2]] = $courseitemsimporder[$row[0]];
            if (isset($itemidsection[$row[0]])) {
                $itemsection[$row[1].$row[2]] = $itemidsection[$row[0]];
            }
        }
        unset($itemidsection);
	}

	//Pull Assessment Info
	$now = time();
	$query = "SELECT id,name,ptsposs,defpoints,deffeedback,timelimit,minscore,startdate,enddate,LPcutoff,itemorder,gbcategory,cntingb,avail,groupsetid,allowlate,date_by_lti,ver,viewingb,scoresingb,deffeedbacktext";
	if ($limuser>0) {
		$query .= ',reqscoreaid,reqscore,reqscoretype';
	}
	if (isset($includeendmsg) && $includeendmsg) {
		$query .= ',endmsg';
	}
	$query .= " FROM imas_assessments WHERE courseid=:courseid AND avail>0 ";

	if (!$canviewall) {
		$query .= "AND cntingb>0 ";
	}
	if ($istutor) {
		$query .= "AND tutoredit<2 ";
	}
	if (!$isteacher) {
		//$query .= "AND startdate<$now ";
	}
	if ($catfilter>-1) {
		$query .= "AND gbcategory=:gbcategory ";
	}
	$query .= "ORDER BY enddate,name";
	$stm = $DBH->prepare($query);
	if ($catfilter>-1) {
		$stm->execute(array(':courseid'=>$cid, ':gbcategory'=>$catfilter));
	} else {
		$stm->execute(array(':courseid'=>$cid));
	}
	$overallpts = 0;
	$now = time();
	$kcnt = 0;
	$assessments = array();
	$grades = array();
	$discuss = array();
	$exttools = array();
	$timelimits = array();
	$minscores = array();
	$assessmenttype = array();
	$startdate = array();
	$enddate = array();
	$LPcutoff = array();
	$tutoredit = array();
	$isgroup = array();
	$avail = array();
	$availstu = array();
	$sa = array();
	$category = array();
	$name = array();
    $possible = array();
    $sectionlimit = array();
	$courseorder = array();
	$allowlate = array();
	$endmsgs = array();
	$reqscores = array();
	$uiver = array();
	$defFb = array();
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($courseitemsassoc['Assessment'.$line['id']])) {
			continue; //assess is in hidden block - skip it
		}
		$assessments[$kcnt] = $line['id'];
		if (isset($courseitemsassoc)) {
			$courseorder[$kcnt] = $courseitemsassoc['Assessment'.$line['id']];
		}
		$timelimits[$kcnt] = $line['timelimit'];
		$minscores[$kcnt] = $line['minscore'];
		if ($line['ver'] > 1) {
			// For assess2,
			// $assessmenttype = viewingb setting
			// $sa = scoresingb setting
			$assessmenttype[$kcnt] = $line['viewingb'];
			$sa[$kcnt] = $line['scoresingb'];
		} else {
			$deffeedback = explode('-',$line['deffeedback']);
			$assessmenttype[$kcnt] = $deffeedback[0];
			$sa[$kcnt] = $deffeedback[1];
		}
		if ($line['avail']==2 || $line['date_by_lti']==1) {
			$line['startdate'] = 0;
			$line['enddate'] = 2000000000;
		}
		$enddate[$kcnt] = $line['enddate'];
		$startdate[$kcnt] = $line['startdate'];
		$LPcutoff[$kcnt] = $line['LPcutoff'];
		if ($now<$line['startdate'] || $line['date_by_lti']==1) {
			$avail[$kcnt] = 2;
		} else if ($now < $line['enddate']) {
			$avail[$kcnt] = 1;
		} else {
			$avail[$kcnt] = 0;
		}
		$category[$kcnt] = $line['gbcategory'];
		$isgroup[$kcnt] = ($line['groupsetid']!=0);
		$name[$kcnt] = $line['name'];
		$cntingb[$kcnt] = $line['cntingb']; //0: ignore, 1: count, 2: extra credit, 3: no count but show
		if ($deffeedback[0]=='Practice') { //set practice as no count in gb
			$cntingb[$kcnt] = 3;
        }
        
		$allowlate[$kcnt] = $line['allowlate'];

		if (isset($line['endmsg']) && $line['endmsg']!='') {
			$endmsgs[$kcnt] = unserialize($line['endmsg']);
		}
		if ($limuser>0) {
			$reqscores[$kcnt] = array('aid'=>$line['reqscoreaid'], 'score'=>abs($line['reqscore']), 'calctype'=>($line['reqscoretype']&2));
		}
		$defFb[$kcnt] = $line['deffeedbacktext'];

		$k = 0;

		if ($line['ptsposs']==-1) {
			require_once("../includes/updateptsposs.php");
			$line['ptsposs'] = updatePointsPossible($line['id'], $line['itemorder'], $line['defpoints']);
		}
		$possible[$kcnt] = $line['ptsposs'];
		$uiver[$kcnt] = $line['ver'];
		$kcnt++;
	}

	unset($questionpointdata);


	//Pull Offline Grade item info
	$query = "SELECT * from imas_gbitems WHERE courseid=:courseid ";
	if ($catfilter>-1) {
		$query .= "AND gbcategory=:gbcategory ";
	}
	if (!$canviewall) {
		$query .= "AND showdate<$now ";
	}
	if (!$canviewall) {
		$query .= "AND cntingb>0 ";
	}
	if ($istutor) {
		$query .= "AND tutoredit<2 ";
	}
	$query .= "ORDER BY showdate,name";
	$stm = $DBH->prepare($query);
	if ($catfilter>-1) {
		$stm->execute(array(':courseid'=>$cid, ':gbcategory'=>$catfilter));
	} else {
		$stm->execute(array(':courseid'=>$cid));
	}
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		$grades[$kcnt] = $line['id'];
		$assessmenttype[$kcnt] = "Offline";
		$category[$kcnt] = $line['gbcategory'];
		$enddate[$kcnt] = $line['showdate'];
		$startdate[$kcnt] = $line['showdate'];
		if ($now < $line['showdate']) {
			$avail[$kcnt] = 2;
		} else {
			$avail[$kcnt] = 0;
		}
		$possible[$kcnt] = $line['points'];
		$name[$kcnt] = $line['name'];
		$cntingb[$kcnt] = $line['cntingb'];
		$tutoredit[$kcnt] = $line['tutoredit'];
		if (isset($courseitemsassoc)) {
			$courseorder[$kcnt] = 2000+$kcnt;
		}
		$kcnt++;
	}

	//Pull Discussion Grade info
	$query = "SELECT id,name,gbcategory,startdate,enddate,replyby,postby,points,cntingb,avail FROM imas_forums WHERE courseid=:courseid AND points>0 AND avail>0 ";
	if ($catfilter>-1) {
		$query .= "AND gbcategory=:gbcategory ";
	}
	if (!$canviewall) {
		$query .= "AND startdate<$now ";
	}
	if ($istutor) {
		$query .= "AND tutoredit<2 ";
	}
	$query .= "ORDER BY enddate,postby,replyby,startdate";
	$stm = $DBH->prepare($query);
	if ($catfilter>-1) {
		$stm->execute(array(':courseid'=>$cid, ':gbcategory'=>$catfilter));
	} else {
		$stm->execute(array(':courseid'=>$cid));
	}
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($courseitemsassoc['Forum'.$line['id']])) {
			continue; //assess is in hidden block - skip it
		}
		$discuss[$kcnt] = $line['id'];
		$assessmenttype[$kcnt] = "Discussion";
		$category[$kcnt] = $line['gbcategory'];
		if ($line['avail']==2) {
			$line['startdate'] = 0;
			$line['enddate'] = 2000000000;
		}
		$enddate[$kcnt] = $line['enddate'];
		$startdate[$kcnt] = $line['startdate'];
		if ($now < $line['startdate']) {
			$avail[$kcnt] = 2;
		} else if ($now < $line['enddate']) {
			$avail[$kcnt] = 1;
			if ($line['replyby'] > 0 && $line['replyby'] < 2000000000) {
				if ($line['postby'] > 0 && $line['postby'] < 2000000000) {
					if ($now>$line['replyby'] && $now>$line['postby']) {
						$avail[$kcnt] = 0;
						$enddate[$kcnt] = max($line['replyby'], $line['postby']);
					}
				} else {
					if ($now>$line['replyby']) {
						$avail[$kcnt] = 0;
						$enddate[$kcnt] = $line['replyby'];
					}
				}
			} else if ($line['postby'] > 0 && $line['postby'] < 2000000000) {
				if ($now>$line['postby']) {
					$avail[$kcnt] = 0;
					$enddate[$kcnt] = $line['postby'];
				}
			}
		} else {
			$avail[$kcnt] = 0;
        }

		$possible[$kcnt] = $line['points'];
		$name[$kcnt] = $line['name'];
		$cntingb[$kcnt] = $line['cntingb'];
		if (isset($courseitemsassoc)) {
			$courseorder[$kcnt] = $courseitemsassoc['Forum'.$line['id']];
		}
		$kcnt++;
    }

	//Pull External Tools info
	$query = "SELECT id,title,text,startdate,enddate,points,avail FROM imas_linkedtext WHERE courseid=:courseid AND points>0 AND avail>0 ";
	if (!$canviewall) {
		$query .= "AND startdate<$now ";
	}
	/*if ($istutor) {
		$query .= "AND tutoredit<2 ";
	}
	if ($catfilter>-1) {
		$query .= "AND gbcategory='$catfilter' ";
	}*/
	$query .= "ORDER BY enddate,startdate";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($courseitemsassoc['LinkedText'.$line['id']])) {
			continue; //assess is in hidden block - skip it
		}
		if (substr($line['text'],0,8)!='exttool:') {
			continue;
		}
		$toolparts = explode('~~',substr($line['text'],8));
		if (isset($toolparts[3])) {
			$thisgbcat = $toolparts[3];
			$thiscntingb = $toolparts[4];
			$thistutoredit = $toolparts[5];
		} else {
			continue;
		}
		if ($istutor && $thistutoredit==2) { continue;}
		if ($catfilter>-1 && $thisgbcat != $catfilter) {continue;}

		$exttools[$kcnt] = $line['id'];
		$assessmenttype[$kcnt] = "External Tool";
		$category[$kcnt] = $thisgbcat;
		if ($line['avail']==2) {
			$line['startdate'] = 0;
			$line['enddate'] = 2000000000;
		}
		$enddate[$kcnt] = $line['enddate'];
		$startdate[$kcnt] = $line['startdate'];
		if ($now < $line['startdate']) {
			$avail[$kcnt] = 2;
		} else if ($now < $line['enddate']) {
			$avail[$kcnt] = 1;
		} else {
			$avail[$kcnt] = 0;
		}
		$possible[$kcnt] = $line['points'];
		$name[$kcnt] = $line['title'];
		$cntingb[$kcnt] = $thiscntingb;
		if (isset($courseitemsassoc)) {
			$courseorder[$kcnt] = $courseitemsassoc['LinkedText'.$line['id']];
		}
		$kcnt++;
	}


	$cats = array();
	$catcolcnt = 0;
	//Pull Categories:  Name, scale, scaletype, chop, drop, weight, calctype
	if (in_array(0,$category)) {  //define default category, if used
		$cats[0] = explode(',',$defaultcat);
		if (!isset($cats[0][6])) {
			$cats[0][6] = ($cats[4]==0)?0:1;
		}
		array_unshift($cats[0],"Default");
		array_push($cats[0],$catcolcnt);
		$catcolcnt++;

	}

	$query = "SELECT id,name,scale,scaletype,chop,dropn,weight,hidden,calctype FROM imas_gbcats WHERE courseid=:courseid ";
	$query .= "ORDER BY name";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if (in_array($row[0],$category)) { //define category if used
			if ($row[1]{0}>='1' && $row[1]{0}<='9') {
				$row[1] = substr($row[1],1);
			}
			$cats[$row[0]] = array_slice($row,1);
			array_push($cats[$row[0]],$catcolcnt);
			$catcolcnt++;
		}
	}
	//create item headers
	$pos = 0;
	$catposspast = array();
	$catposspastec = array();
	$catposscur = array();
	$catposscurec = array();
	$catpossfuture = array();
	$catpossfutureec = array();
	$cattotpast = array();
	$cattotpastec = array();
	$cattotcur = array();
	$cattotcurec = array();
	$cattotattempted = array();
	$cattotattemptedec = array();
	$cattotfuture = array();
	$cattotfutureec = array();
	$itemorder = array();
	$assesscol = array();
	$gradecol = array();
	$discusscol = array();
	$exttoolcol = array();
	$colref = array();
	if ($orderby==1) { //order $category by enddate
		//asort($enddate,SORT_NUMERIC);
		uksort($enddate, function($a,$b) use ($enddate,$name) {
			if ($enddate[$a]==$enddate[$b]) {
				return ($name[$a]>$name[$b]?1:-1);
			} else {
				return ($enddate[$a]>$enddate[$b]?1:-1);
			}
		  });
		$newcategory = array();
		foreach ($enddate as $k=>$v) {
			$newcategory[$k] = $category[$k];
		}
		$category = $newcategory;
	} else if ($orderby==5) { //order $category by enddate reverse
		//arsort($enddate,SORT_NUMERIC);
		uksort($enddate, function($a,$b) use ($enddate,$name) {
			if ($enddate[$a]==$enddate[$b]) {
				return ($name[$a]>$name[$b]?1:-1);
			} else {
				return ($enddate[$a]>$enddate[$b]?-1:1);
			}
		  });
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
	} else if ($orderby==11) { //order $category courseorder
		asort($courseorder,SORT_NUMERIC);
		$newcategory = array();
		foreach ($courseorder as $k=>$v) {
			$newcategory[$k] = $category[$k];
		}
		$category = $newcategory;
	} else if ($orderby==13) { //order $category courseorder rev
		arsort($courseorder,SORT_NUMERIC);
		$newcategory = array();
		foreach ($courseorder as $k=>$v) {
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
				$colref[$k] = $pos;
				$gb[0][1][$pos][0] = $name[$k]; //item name
				if (!empty($GLOBALS['includecategoryID'])) {
					$gb[0][1][$pos][1] = $cat;
				} else {
					$gb[0][1][$pos][1] = $cats[$cat][8]; //item category number
				}
				$gb[0][1][$pos][2] = $possible[$k]; //points possible
				$gb[0][1][$pos][3] = $avail[$k]; //0 past, 1 current, 2 future
				$gb[0][1][$pos][4] = $cntingb[$k]; //0 no count and hide, 1 count, 2 EC, 3 no count
				if ($assessmenttype[$k]=="Practice") {
					$gb[0][1][$pos][5] = 1;  //0 regular, 1 practice test
				} else {
					$gb[0][1][$pos][5] = 0;
				}
				if (isset($assessments[$k])) {
					$gb[0][1][$pos][6] = 0; //0 online, 1 offline
					$gb[0][1][$pos][7] = $assessments[$k];
					$gb[0][1][$pos][10] = $isgroup[$k];
					if (!empty($GLOBALS['includetimelimit'])) {
						$gb[0][1][$pos][13] = $timelimits[$k];
					}
                    $assesscol[$assessments[$k]] = $pos;
                    if (!empty($itemsection['Assessment'.$assessments[$k]])) {
                        $sectionlimit[$pos] = $itemsection['Assessment'.$assessments[$k]];
                        $gb[0][1][$pos][17] = $sectionlimit[$pos];
                    }
				} else if (isset($grades[$k])) {
					$gb[0][1][$pos][6] = 1; //0 online, 1 offline
					$gb[0][1][$pos][8] = $tutoredit[$k]; //tutoredit
					$gb[0][1][$pos][7] = $grades[$k];
					$gradecol[$grades[$k]] = $pos;
				} else if (isset($discuss[$k])) {
					$gb[0][1][$pos][6] = 2; //0 online, 1 offline, 2 discuss
					$gb[0][1][$pos][7] = $discuss[$k];
                    $discusscol[$discuss[$k]] = $pos;
                    if (!empty($itemsection['Forum'.$discuss[$k]])) {
                        $sectionlimit[$pos] = $itemsection['Forum'.$discuss[$k]];
                        $gb[0][1][$pos][17] = $sectionlimit[$pos];
                    }
				} else if (isset($exttools[$k])) {
					$gb[0][1][$pos][6] = 3; //0 online, 1 offline, 2 discuss, 3 exttool
					$gb[0][1][$pos][7] = $exttools[$k];
					$exttoolcol[$exttools[$k]] = $pos;
				}
				if ((isset($GLOBALS['includeduedate']) && $GLOBALS['includeduedate']==true) || $allowlate[$k]>0) {
					$gb[0][1][$pos][11] = $enddate[$k];
				}
				if ($allowlate[$k]>0) {
					$gb[0][1][$pos][12] = $allowlate[$k];
				}
				if (isset($LPcutoff[$k])) {
					$gb[0][1][$pos][14] = $LPcutoff[$k];
				}
				if (isset($uiver[$k])) {
					$gb[0][1][$pos][15] = $uiver[$k];
				}
				$pos++;
			}
		}
	}
	if (($orderby&1)==0) {//if not grouped by category
		if ($orderby==0) {   //enddate
			uksort($enddate, function($a,$b) use ($enddate,$name) {
				if ($enddate[$a]==$enddate[$b]) {
					return ($name[$a]>$name[$b]?1:-1);
				} else {
					return ($enddate[$a]>$enddate[$b]?1:-1);
				}
			  });
			//asort($enddate,SORT_NUMERIC);
			$itemorder = array_keys($enddate);
		} else if ($orderby==2) {  //alpha
			natcasesort($name);//asort($name);
			$itemorder = array_keys($name);
		} else if ($orderby==4) { //enddate reverse
			//arsort($enddate,SORT_NUMERIC);
			uksort($enddate, function($a,$b) use ($enddate,$name) {
				if ($enddate[$a]==$enddate[$b]) {
					return ($name[$a]>$name[$b]?1:-1);
				} else {
					return ($enddate[$a]>$enddate[$b]?-1:1);
				}
			  });
			$itemorder = array_keys($enddate);
		} else if ($orderby==6) { //startdate
			asort($startdate,SORT_NUMERIC);
			$itemorder = array_keys($startdate);
		} else if ($orderby==8) { //startdate reverse
			arsort($startdate,SORT_NUMERIC);
			$itemorder = array_keys($startdate);
		} else if ($orderby==10) { //courseorder
			asort($courseorder,SORT_NUMERIC);
			$itemorder = array_keys($courseorder);
		} else if ($orderby==12) { //courseorder rev
			arsort($courseorder,SORT_NUMERIC);
			$itemorder = array_keys($courseorder);
		}

		foreach ($itemorder as $k) {
			$colref[$k] = $pos;
			$gb[0][1][$pos][0] = $name[$k]; //item name
			if (!empty($GLOBALS['includecategoryID'])) {
				$gb[0][1][$pos][1] = $category[$k];
			} else {
				$gb[0][1][$pos][1] = $cats[$category[$k]][8]; //item category color #
			}
			$gb[0][1][$pos][2] = $possible[$k]; //points possible
			$gb[0][1][$pos][3] = $avail[$k]; //0 past, 1 current, 2 future
			$gb[0][1][$pos][4] = $cntingb[$k]; //0 no count and hide, 1 count, 2 EC, 3 no count
			$gb[0][1][$pos][5] = ($assessmenttype[$k]=="Practice");  //0 regular, 1 practice test
			if (isset($assessments[$k])) {
				$gb[0][1][$pos][6] = 0; //0 online, 1 offline
				$gb[0][1][$pos][7] = $assessments[$k];
				$gb[0][1][$pos][10] = $isgroup[$k];
				if (!empty($GLOBALS['includetimelimit'])) {
					$gb[0][1][$pos][13] = $timelimits[$k];
                }
                if (!empty($itemsection['Assessment'.$assessments[$k]])) {
                    $sectionlimit[$pos] = $itemsection['Assessment'.$assessments[$k]];
                    $gb[0][1][$pos][17] = $sectionlimit[$pos];
                }
				$assesscol[$assessments[$k]] = $pos;
			} else if (isset($grades[$k])) {
				$gb[0][1][$pos][6] = 1; //0 online, 1 offline
				$gb[0][1][$pos][8] = $tutoredit[$k]; //tutoredit
				$gb[0][1][$pos][7] = $grades[$k];
				$gradecol[$grades[$k]] = $pos;
			} else if (isset($discuss[$k])) {
				$gb[0][1][$pos][6] = 2; //0 online, 1 offline, 2 discuss
                $gb[0][1][$pos][7] = $discuss[$k];
                if (!empty($itemsection['Forum'.$discuss[$k]])) {
                    $sectionlimit[$pos] = $itemsection['Forum'.$discuss[$k]];
                    $gb[0][1][$pos][17] = $sectionlimit[$pos];
                }
				$discusscol[$discuss[$k]] = $pos;
			} else if (isset($exttools[$k])) {
				$gb[0][1][$pos][6] = 3; //0 online, 1 offline, 2 discuss, 3 exttool
				$gb[0][1][$pos][7] = $exttools[$k];
				$exttoolcol[$exttools[$k]] = $pos;
			}
			if (isset($GLOBALS['includeduedate']) && $GLOBALS['includeduedate']==true|| $allowlate[$k]>0) {
				$gb[0][1][$pos][11] = $enddate[$k];
			}
			if ($allowlate[$k]>0) {
				$gb[0][1][$pos][12] = $allowlate[$k];
			}
			if (isset($LPcutoff[$k])) {
				$gb[0][1][$pos][14] = $LPcutoff[$k];
			}
			if (isset($uiver[$k])) {
				$gb[0][1][$pos][15] = $uiver[$k];
			}
			$pos++;
		}
	}
	$totalspos = $pos;



	//Pull student data
	$ln = 1;
	$query = "SELECT imas_users.id,imas_users.SID,imas_users.FirstName,imas_users.LastName,imas_users.SID,imas_users.email,imas_students.section,imas_students.code,imas_students.locked,imas_students.timelimitmult,imas_students.lastaccess,imas_users.hasuserimg,imas_students.gbcomment ";
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
	if (isset($timefilter) && $timefilter>0) {
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
    $stusection = array();
	$timelimitmult = array();
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		if (isset($sturow[$line['id']])) {
			// this shouldn't happen, but could if there were two
			// imas_student records for the same user/course.
			continue;
		}
		unset($asid); unset($pts); unset($IP); unset($timeused);
		$cattotpast[$ln] = array();
		$cattotpastec[$ln] = array();
		$cattotcur[$ln] = array();
		$cattotfuture[$ln] = array();
		$cattotcurec[$ln] = array();
		$cattotfutureec[$ln] = array();
		$gb[$ln] = [[],[],[],[],[]];
		//Student ID info
		$gb[$ln][0][0] = sprintf("%s,&nbsp;%s", Sanitize::encodeStringForDisplay($line['LastName']),
			Sanitize::encodeStringForDisplay($line['FirstName']));
		$gb[$ln][4][0] = $line['id'];
		$gb[$ln][4][1] = $line['locked'];
		$gb[$ln][4][2] = $line['hasuserimg'];
		$gb[$ln][4][3] = !empty($line['gbcomment']);
		if (!empty($GLOBALS['includetimelimit'])) {
			$gb[$ln][4][4] = $line['timelimitmult'];
		}

		if ($isdiag) {
			$selparts = explode('~',$line['SID']);
			$gb[$ln][0][1] = $selparts[0];
			$gb[$ln][0][2] = $selparts[1];
			$selparts =  explode('@',$line['email']);
			$gb[$ln][0][3] = $selparts[0];
			$gb[$ln][0][4] = $selparts[1];
		} else {
			$gb[$ln][0][1] = $line['SID'];
		}
		if ($hassection && !$isdiag) {
			$gb[$ln][0][] = ($line['section']==null)?'':$line['section'];
        }
        if ($line['section'] !== null) {
            $stusection[$line['id']] = $line['section'];
        }
		if ($hascode) {
			$gb[$ln][0][] = $line['code'];
		}
		if ($lastlogin) {
			$gb[$ln][0][] = ($line['lastaccess']>0)?date("n/j/y",$line['lastaccess']):_('Never');
		}

		$sturow[$line['id']] = $ln;
		$timelimitmult[$line['id']] = $line['timelimitmult'];
		$ln++;
	}

	//pull logincnt if needed
	if ($logincnt==1) {
		$stm2 = $DBH->prepare("SELECT userid,count(*) FROM imas_login_log WHERE courseid=:courseid GROUP BY userid");
		$stm2->execute(array(':courseid'=>$cid));
		while ($r = $stm2->fetch(PDO::FETCH_NUM)) {
			$gb[$sturow[$r[0]]][0][] = $r[1];
		}
	}

	$assessidx = array_flip($assessments);

	//pull exceptions
	$exceptions = array();
	$canuseexception = array();
	$forumexceptions = array();
	$query = "SELECT ie.assessmentid as typeid,ie.userid,ie.startdate AS exceptionstartdate,ie.enddate AS exceptionenddate,ie.islatepass,ie.itemtype,imas_assessments.enddate,imas_assessments.startdate,imas_assessments.LPcutoff,ie.timeext FROM imas_exceptions AS ie,imas_assessments WHERE ";
	$query .= "ie.itemtype='A' AND ie.assessmentid=imas_assessments.id AND imas_assessments.courseid=:courseid ";
	$query .= "UNION SELECT ie.assessmentid as typeid,ie.userid,ie.startdate AS exceptionstartdate,ie.enddate AS exceptionenddate,ie.islatepass,ie.itemtype,imas_forums.enddate,imas_forums.startdate,0 AS LPcutoff,ie.timeext FROM imas_exceptions AS ie,imas_forums WHERE ";
	$query .= "(ie.itemtype='F' OR ie.itemtype='R' OR ie.itemtype='P') AND ie.assessmentid=imas_forums.id AND imas_forums.courseid=:courseid2";
	$stm2 = $DBH->prepare($query);
	$stm2->execute(array(':courseid'=>$cid, ':courseid2'=>$cid));
	while ($r = $stm2->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($sturow[$r['userid']])) { continue;}
		if ($r['itemtype']=='A') {
			if (!isset($assesscol[$r['typeid']])) {
				continue; //assessment is hidden
			}
			if ($r['exceptionenddate'] > $courseenddate && $now > $courseenddate) { //for grading purposes, cutoff exceptions at courseenddate
				$r['exceptionenddate'] = $courseenddate;
			}
			$exceptions[$r['typeid']][$r['userid']] = array($r['exceptionstartdate'],$r['exceptionenddate'],$r['islatepass'],$r['timeext']);
			if ($limuser>0) {
				$useexception = $exceptionfuncs->getCanUseAssessException($exceptions[$r['typeid']][$r['userid']], $r, true);
				if ($useexception) {
					$gb[0][1][$assesscol[$r['typeid']]][11] = $r['exceptionenddate']; //override due date header if one stu display
					//change $avail past/cur/future based on exception
                    if ($now<$r['exceptionstartdate']) {
                        $avail[$assessidx[$r['typeid']]] = 2;
                    } else if ($now<$r['exceptionenddate']) {
						$avail[$assessidx[$r['typeid']]] = 1;
					} else {
						$avail[$assessidx[$r['typeid']]] = 0;
					}
					$gb[0][1][$assesscol[$r['typeid']]][3] = $avail[$assessidx[$r['typeid']]];
				}
			} else { //main view; possible by-stu avail override
				$useexception = $exceptionfuncs->getCanUseAssessException($exceptions[$r['typeid']][$r['userid']], $r, true);
				if ($useexception) {
					if (!isset($availstu[$sturow[$r['userid']]])) {
						$availstu[$sturow[$r['userid']]] = array();
					}
					if ($now<$r['exceptionstartdate']) {
						$availstu[$sturow[$r['userid']]][$r['typeid']] = 2;
					} else if ($now<$r['exceptionenddate']) {
						$availstu[$sturow[$r['userid']]][$r['typeid']] = 1;
					} else {
						$availstu[$sturow[$r['userid']]][$r['typeid']] = 0;
					}
				}
			}
			$gb[$sturow[$r['userid']]][1][$assesscol[$r['typeid']]][6] = ($r['islatepass']>0)?(1+$r['islatepass']):1;
			$gb[$sturow[$r['userid']]][1][$assesscol[$r['typeid']]][3] = 10; //will get overwritten later if assessment session exists
		} else if ($r['itemtype']=='F' || $r['itemtype']=='P' || $r['itemtype']=='R') {
			if (!isset($discusscol[$r['typeid']])) {
				continue; //assessment is hidden
			}
			$forumexceptions[$r['typeid']][$r['userid']] = array($r['exceptionstartdate'],$r['exceptionenddate'],$r['islatepass']);
			if ($limuser>0) {
				$gb[0][1][$discusscol[$r['typeid']]][11] = max($r['exceptionstartdate'],$r['exceptionenddate']);
			}
			$gb[$sturow[$r['userid']]][1][$discusscol[$r['typeid']]][6] = ($r['islatepass']>0)?(1+$r['islatepass']):1;
			//$gb[$sturow[$r['userid']]][1][$discusscol[$r['typeid']]][3] = 10; //will get overwritten later if assessment session exists
		}
    }

	//Get assessment scores
	$query = "SELECT ias.id,ias.assessmentid,ias.bestscores,ias.starttime,ias.endtime,ias.timeontask,ias.feedback,ias.userid FROM imas_assessment_sessions AS ias,imas_assessments AS ia ";
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

		//if two asids for same stu/assess, skip or overright one with higher ID. Shouldn't happen
		if (isset($gb[$row][1][$col][4]) && $gb[$row][1][$col][4]<$l['id']) { continue;}

		$gb[$row][1][$col][4] = $l['id'];; //assessment session id

		$sp = explode(';',$l['bestscores']);
		$scores = explode(',',$sp[0]);
		$pts = 0;
		for ($j=0;$j<count($scores);$j++) {
			$pts += getpts($scores[$j]);
			//if ($scores[$i]>0) {$total += $scores[$i];}
		}
		$timeused = $l['endtime']-$l['starttime'];
		if ($l['endtime']==0 || $l['starttime']==0) {
			$gb[$row][1][$col][7] = -1;
		} else {
			$gb[$row][1][$col][7] = round($timeused/60);
		}

		$timeontask = array_sum(explode(',',str_replace('~',',',$l['timeontask'])));
		if ($timeontask==0) {
			$gb[$row][1][$col][8] = "N/A";
		} else {
			$gb[$row][1][$col][8] = round($timeontask/60,1);
		}
		if (isset($GLOBALS['includelastchange']) && $GLOBALS['includelastchange']==true) {
			$gb[$row][1][$col][9] =	$l['endtime'];
		}

		/*
		Moved up to exception finding so LP mark will show on unstarted assessments
		if (isset($exceptions[$l['assessmentid']][$l['userid']])) {
			$gb[$row][1][$col][6] = ($exceptions[$l['assessmentid']][$l['userid']][1]>0)?2:1; //had exception
		}
		*/
		$useexception = false; $canuselatepass = false;
		if (isset($exceptions[$l['assessmentid']][$l['userid']])) {
			list($useexception, $canundolatepass, $canuselatepass) = $exceptionfuncs->getCanUseAssessException($exceptions[$l['assessmentid']][$l['userid']], array('startdate'=>$startdate[$i], 'enddate'=>$enddate[$i], 'allowlate'=>$allowlate[$i], 'id'=>$l['assessmentid'], 'LPcutoff'=>$LPcutoff[$i]));
		} else {
			$canuselatepass = $exceptionfuncs->getCanUseAssessLatePass(array('startdate'=>$startdate[$i], 'enddate'=>$enddate[$i], 'allowlate'=>$allowlate[$i], 'id'=>$l['assessmentid'], 'LPcutoff'=>$LPcutoff[$i]));
		}
		//if (isset($exceptions[$l['assessmentid']][$l['userid']])) {// && $now>$enddate[$i] && $now<$exceptions[$l['assessmentid']][$l['userid']]) {

		if ($useexception) {
			//TODO:  Does not change due date display in individual user gradebook view when no asid
			if ($enddate[$i]>$exceptions[$l['assessmentid']][$l['userid']][1] && $assessmenttype[$i]=="NoScores") {
				//if exception set for earlier, and NoScores is set, use later date to hide score until later
				$thised = $enddate[$i];
			} else {
				$thised = $exceptions[$l['assessmentid']][$l['userid']][1];
				if ($limuser>0) {  //change $avail past/cur/future
					if ($now<$thised) {
						$avail[$assessidx[$l['assessmentid']]] = 1;
					} else {
						$avail[$assessidx[$l['assessmentid']]] = 0;
					}
					$gb[0][1][$col][3] = $avail[$assessidx[$l['assessmentid']]];
				}
			}
			if ($limuser>0) { //override due date header if one stu display
				$gb[0][1][$col][11] = $thised;
			}
			$inexception = true;
		} else {
			$thised = $enddate[$i];
			$inexception = false;
		}
		$gb[$row][1][$col][10] = $canuselatepass;

		if (in_array(-1,$scores) && ($thised>$now || !empty($GLOBALS['alwaysshowIP'])) && (($timelimits[$i]==0) || ($timeused < $timelimits[$i]*$timelimitmult[$l['userid']]))) {
			$IP=1;
		} else {
			$IP=0;
		}

		if ($canviewall || ($sa[$i]=="I" && ($pts>0 || $IP==0)) || ($sa[$i]!="N" && $now>$thised)) { //|| $assessmenttype[$i]=="Practice"
			$gb[$row][1][$col][2] = 1; //show link
		} /*else if ($l['timelimit']<0 && (($now - $l['starttime'])>abs($l['timelimit'])) && $sa[$i]!='N' && ($assessmenttype[$k]=='EachAtEnd' || $assessmenttype[$k]=='EndReview' || $assessmenttype[$k]=='AsGo' || $assessmenttype[$k]=='Homework'))  ) {
			//has "kickout after time limit" set, time limit has passed, and is set for showing each score
			$gb[$row][1][$col][2] = 1; //show link
		} */else {
			$gb[$row][1][$col][2] = 0; //don't show link
		}

		$countthisone = false;
		if ($assessmenttype[$i]=="NoScores" && $sa[$i]!="I" && $now<$thised && !$canviewall) {
			$gb[$row][1][$col][0] = 'N/A'; //score is not available
			$gb[$row][1][$col][3] = 0;  //no other info
		} else if (($minscores[$i]<10000 && $pts<$minscores[$i]) || ($minscores[$i]>10000 && $pts<($minscores[$i]-10000)/100*$possible[$i])) {
		//else if ($pts<$minscores[$i]) {
			if ($canviewall) {
				$gb[$row][1][$col][0] = $pts; //the score
				$gb[$row][1][$col][3] = 1;  //no credit
			} else {
				$gb[$row][1][$col][0] = 'NC'; //score is No credit
				$gb[$row][1][$col][3] = 1;  //no credit
				$pts = 0;
			}
		} else 	if ($IP==1) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 2;  //in progress
			$countthisone =true;
		} else	if (($timelimits[$i]>0) && ($timeused > $timelimits[$i]*$timelimitmult[$l['userid']]+10)) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 3;  //over time
		} else if ($assessmenttype[$i]=="Practice") {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 4;  //practice test
		} else { //regular score available to students
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 0;  //no other info
			$countthisone =true;
		}
		//do endmsg if appropriate
		if (isset($endmsgs[$i])) {
			$outmsg = '';
			foreach ($endmsgs[$i]['msgs'] as $sc=>$msg) { //array must be reverse sorted
				if (($endmsgs[$i]['type']==0 && $pts>=$sc) || ($endmsgs[$i]['type']==1 && 100*$pts/$possible[$i]>=$sc)) {
					$outmsg = $msg;
					break;
				}
			}
			if ($outmsg=='') {
				$outmsg = $endmsgs[$i]['def'];
			}
			if (strpos($outmsg,'redirectto:')!==false) {
				$outmsg = '';
			}
			$gb[$row][1][$col][11] = $outmsg;
		}
		if ($now < $thised) { //still active
			$gb[$row][1][$col][3] += 10;
        }
        if (!empty($sectionlimit[$col]) && 
            (empty($stusection[$l['userid']]) || $stusection[$l['userid']] != $sectionlimit[$col])
        ) {
            // assess is not in this stu's section, so we won't count it, 
            $countthisone = false;
            $gb[$row][1][$col][0] = ' ';
        }
		if ($countthisone) {
			if ($cntingb[$i] == 1) {
				if (isset($availstu[$row][$l['assessmentid']])) { //has per-stu avail override
					if ($availstu[$row][$l['assessmentid']]<1) { //past
						$cattotpast[$row][$category[$i]][$col] = $pts;
					}
					if ($availstu[$row][$l['assessmentid']]<2) { //past or cur
						$cattotcur[$row][$category[$i]][$col] = $pts;
					}
				} else {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpast[$row][$category[$i]][$col] = $pts;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcur[$row][$category[$i]][$col] = $pts;
					}
				}
				$cattotfuture[$row][$category[$i]][$col] = $pts;
			} else if ($cntingb[$i] == 2) {
				if (isset($availstu[$row][$l['assessmentid']])) { //has per-stu avail override
					if ($availstu[$row][$l['assessmentid']]<1) { //past
						$cattotpastec[$row][$category[$i]][$col] = $pts;
					}
					if ($availstu[$row][$l['assessmentid']]<2) { //past or cur
						$cattotcurec[$row][$category[$i]][$col] = $pts;
					}
				} else {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpastec[$row][$category[$i]][$col] = $pts;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcurec[$row][$category[$i]][$col] = $pts;
					}
				}
				$cattotfutureec[$row][$category[$i]][$col] = $pts;
			}
		}
		if (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments']) {
			$fbarr = json_decode($l['feedback'], true);
			if ($fbarr === null) {
				$gb[$row][1][$col][1] = $l['feedback']; //the feedback
			} else {
				$fbtxt = '';
				foreach ($fbarr as $k=>$v) {
					if ($v=='') {continue;}
					if ($k=='Z') {
						$fbtxt .= 'Overall feedback: '.$v.'<br>';
					} else {
						$q = substr($k,1);
						$fbtxt .= 'Feedback on Question '.($q+1).': '.$v.'<br>';
					}
				}
				$gb[$row][1][$col][1] = $fbtxt;
			}
		} else if ($l['feedback']!='') {
			$gb[$row][1][$col][1] = 1; //has comment
		} else {
			$gb[$row][1][$col][1] = 0; //no comment
		}
	}

	//Get assessment2 scores,
	$query = "SELECT iar.assessmentid,iar.score,iar.starttime,iar.lastchange,iar.timeontask,iar.status,iar.userid,iar.timelimitexp";
	if (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments']) {
		$query .= ',iar.scoreddata';
	}
	$query .= " FROM imas_assessment_records AS iar ";
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

		//if two asids for same stu/assess, skip or overright one with higher ID. Shouldn't happen
		if (isset($gb[$row][1][$col][4]) && $gb[$row][1][$col][4]<$l['id']) { continue;}

		$gb[$row][1][$col][4] = $l['userid'];; //assessment session userid

		$pts = (float) $l['score'];
		$timeused = $l['lastchange']-$l['starttime'];
		if ($l['lastchange']==0 || $l['starttime']==0) {
			$gb[$row][1][$col][7] = -1;
		} else {
			$gb[$row][1][$col][7] = round($timeused/60);
		}

		$timeontask = $l['timeontask'];
		if ($timeontask==0) {
			$gb[$row][1][$col][8] = "N/A";
		} else {
			$gb[$row][1][$col][8] = round($timeontask/60,1);
		}
		if (isset($GLOBALS['includelastchange']) && $GLOBALS['includelastchange']==true) {
			$gb[$row][1][$col][9] =	$l['lastchange'];
		}

		$useexception = false; $canuselatepass = false; $hastimeext = false;
		if (isset($exceptions[$l['assessmentid']][$l['userid']])) {
            list($useexception, $canundolatepass, $canuselatepass) = $exceptionfuncs->getCanUseAssessException($exceptions[$l['assessmentid']][$l['userid']], array('startdate'=>$startdate[$i], 'enddate'=>$enddate[$i], 'allowlate'=>$allowlate[$i], 'id'=>$l['assessmentid'], 'LPcutoff'=>$LPcutoff[$i]));
            $hastimeext = ($exceptions[$l['assessmentid']][$l['userid']][3] > 0);
		} else {
			$canuselatepass = $exceptionfuncs->getCanUseAssessLatePass(array('startdate'=>$startdate[$i], 'enddate'=>$enddate[$i], 'allowlate'=>$allowlate[$i], 'id'=>$l['assessmentid'], 'LPcutoff'=>$LPcutoff[$i]));
		}
		if (($l['status']&32)==32) { // out of attempts
			$canuselatepass = 0;
		}
		//if (isset($exceptions[$l['assessmentid']][$l['userid']])) {// && $now>$enddate[$i] && $now<$exceptions[$l['assessmentid']][$l['userid']]) {

		if ($useexception) {
			//TODO:  Does not change due date display in individual user gradebook view when no asid
			if ($enddate[$i]>$exceptions[$l['assessmentid']][$l['userid']][1] && $assessmenttype[$i]=="never") { //TODO
				//if exception set for earlier, and NoScores is set, use later date to hide score until later
				$thised = $enddate[$i];
			} else {
				$thised = $exceptions[$l['assessmentid']][$l['userid']][1];
				if ($limuser>0) {  //change $avail past/cur/future
					if ($now<$thised) {
						$avail[$assessidx[$l['assessmentid']]] = 1;
					} else {
						$avail[$assessidx[$l['assessmentid']]] = 0;
					}
					$gb[0][1][$col][3] = $avail[$assessidx[$l['assessmentid']]];
				}
			}
			if ($limuser>0) { //override due date header if one stu display
				$gb[0][1][$col][11] = $thised;
			}
			$inexception = true;
		} else {
			$thised = $enddate[$i];
			$inexception = false;
		}
		$gb[$row][1][$col][10] = $canuselatepass;

        if (($l['status']&1)>0 && ($thised<$now ||  //unsubmitted by-assess, and due date passed
            ($l['timelimitexp']>0 && $l['timelimitexp']<$now && !$hastimext)) // or time limit expired on last att
        ) {
			$IP=0;
			$UA=1;
		} else if (($l['status']&3)>0 && // unsubmitted attempt any mode
            ($thised>$now || !empty($GLOBALS['alwaysshowIP'])) && // and before due date
            ($l['timelimitexp']==0 || $l['timelimitexp']>$now || $hastimext) // and time limit not expired
        ) {
			$IP=1;
			$UA=0;
		} else if ($hastimeext && $thised>$now) { // has time extension and before due date
            $IP=1;
			$UA=0;
        } else {
			$IP=0;
			$UA=0;
		}

		$hasSubmittedTake = ($l['status']&64)>0;

		if ($canviewall ||
			$assessmenttype[$i] == 'immediately' || //viewingb
			($assessmenttype[$i] == 'after_take' && $hasSubmittedTake && !$hastimeext) ||
			($assessmenttype[$i] == 'after_due' && $now > $thised)
		) {
			$gb[$row][1][$col][2] = 1; //show link
		} else {
			$gb[$row][1][$col][2] = 0; //don't show link
		}

		$countthisone = false;

		if (!$canviewall && (
			($sa[$i]=="never") ||
		 	($sa[$i]=='after_due' && $now < $thised) ||
			($sa[$i]=='after_take' && !$hasSubmittedTake && ($l['status']&1) == 1) // does not have a submitted attempt, but does have an unsubmitted attempt
		)) {
			$gb[$row][1][$col][0] = 'N/A'; //score is not available
			$gb[$row][1][$col][3] = 0;  //no other info
		} else if (($minscores[$i]<10000 && $pts<$minscores[$i]) || ($minscores[$i]>10000 && $pts<($minscores[$i]-10000)/100*$possible[$i])) {
		//else if ($pts<$minscores[$i]) {
			if ($canviewall) {
				$gb[$row][1][$col][0] = $pts; //the score
				$gb[$row][1][$col][3] = 1;  //no credit
			} else {
				$gb[$row][1][$col][0] = 'NC'; //score is No credit
				$gb[$row][1][$col][3] = 1;  //no credit
				$pts = 0;
			}
		} else if ($IP==1) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 2;  //in progress
			$countthisone =true;
		} else if ($UA==1) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 5;  //unsubmitted attempt
			$countthisone =true;
		} else if (($l['status']&4)>0) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 3;  //over time
			$countthisone =true;
		} else { //regular score available to students
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 0;  //no other info
			$countthisone =true;
		}
		//do endmsg if appropriate
		if (isset($endmsgs[$i])) {
			$outmsg = '';
			foreach ($endmsgs[$i]['msgs'] as $sc=>$msg) { //array must be reverse sorted
				if (($endmsgs[$i]['type']==0 && $pts>=$sc) || ($endmsgs[$i]['type']==1 && 100*$pts/$possible[$i]>=$sc)) {
					$outmsg = $msg;
					break;
				}
			}
			if ($outmsg=='') {
				$outmsg = $endmsgs[$i]['def'];
			}
			if (strpos($outmsg,'redirectto:')!==false) {
				$outmsg = '';
			}
			$gb[$row][1][$col][11] = $outmsg;
		}
		if ($now < $thised) { //still active
			$gb[$row][1][$col][3] += 10;
        }
        if (!empty($sectionlimit[$col]) && 
            (empty($stusection[$l['userid']]) || $stusection[$l['userid']] != $sectionlimit[$col])
        ) {
            // assess is not in this stu's section, so we won't count it, 
            $countthisone = false;
            $gb[$row][1][$col][0] = ' ';
        }
		if ($countthisone) {
			if ($cntingb[$i] == 1) {
				if (isset($availstu[$row][$l['assessmentid']])) { //has per-stu avail override
					if ($availstu[$row][$l['assessmentid']]<1) { //past
						$cattotpast[$row][$category[$i]][$col] = $pts;
					}
					if ($availstu[$row][$l['assessmentid']]<2) { //past or cur
						$cattotcur[$row][$category[$i]][$col] = $pts;
					}
				} else {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpast[$row][$category[$i]][$col] = $pts;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcur[$row][$category[$i]][$col] = $pts;
					}
				}
				$cattotfuture[$row][$category[$i]][$col] = $pts;
			} else if ($cntingb[$i] == 2) {
				if (isset($availstu[$row][$l['assessmentid']])) { //has per-stu avail override
					if ($availstu[$row][$l['assessmentid']]<1) { //past
						$cattotpastec[$row][$category[$i]][$col] = $pts;
					}
					if ($availstu[$row][$l['assessmentid']]<2) { //past or cur
						$cattotcurec[$row][$category[$i]][$col] = $pts;
					}
				} else {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpastec[$row][$category[$i]][$col] = $pts;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcurec[$row][$category[$i]][$col] = $pts;
					}
				}
				$cattotfutureec[$row][$category[$i]][$col] = $pts;
			}
		}

		if (($l['status']&128)>0) { // accepting showwork after assess
			$gb[$row][1][$col][16] = 1;
		}
		if (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments']) {
			$gb[$row][1][$col][1] = buildFeedback2($l['scoreddata']);
			if ($gb[$row][1][$col][1] == '') {
				$gb[$row][1][$col][1] = $defFb[$i];
			}
		} else if (($l['status']&8)>0 || $defFb[$i] !== '') {
			$gb[$row][1][$col][1] = 1; //has comment
		} else {
			$gb[$row][1][$col][1] = 0; //no comment
		}
	}

	//Get other grades
	$gradeidx = array_flip($grades);
	unset($gradeid); unset($opts);
	unset($discusspts);
	$discussidx = array_flip($discuss);
	$exttoolidx = array_flip($exttools);
	$gradetypeselects = array();
	if (count($grades)>0) {
		$gradeidlist = implode(',',$grades); //values from DB
		$gradetypeselects[] = "(gradetype='offline' AND gradetypeid IN ($gradeidlist))";
	}
	if (count($discuss)>0) {
		$forumidlist = implode(',',$discuss); //values from DB
		$gradetypeselects[] = "(gradetype='forum' AND gradetypeid IN ($forumidlist))";
	}
	if (count($exttools)>0) {
		$linkedlist = implode(',',$exttools); //values from DB
		$gradetypeselects[] = "(gradetype='exttool' AND gradetypeid IN ($linkedlist))";
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

				$gb[$row][1][$col][2] = $l['id'];
				if ($l['score']!=null) {
					$gb[$row][1][$col][0] = 1*$l['score'];
				}
				if (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments']) {
					$gb[$row][1][$col][1] =  $l['feedback']; //the feedback (for students)
				} else if ($l['feedback']!='') { //feedback
					$gb[$row][1][$col][1] = 1; //yes it has it (for teachers)
				} else {
					$gb[$row][1][$col][1] = 0; //no feedback
				}

				if ($cntingb[$i] == 1) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpast[$row][$category[$i]][$col] = 1*$l['score'];
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcur[$row][$category[$i]][$col] = 1*$l['score'];
					}
					$cattotfuture[$row][$category[$i]][$col] = 1*$l['score'];
				} else if ($cntingb[$i]==2) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpastec[$row][$category[$i]][$col] = 1*$l['score'];
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcurec[$row][$category[$i]][$col] = 1*$l['score'];
					}
					$cattotfutureec[$row][$category[$i]][$col] = 1*$l['score'];
				}
			} else if ($l['gradetype']=='forum') {
				if (!isset($discussidx[$l['gradetypeid']]) || !isset($sturow[$l['userid']]) || !isset($discusscol[$l['gradetypeid']])) {
					continue;
				}
				$i = $discussidx[$l['gradetypeid']];
				$row = $sturow[$l['userid']];
				$col = $discusscol[$l['gradetypeid']];

				if (isset($forumexceptions[$l['gradetypeid']][$l['userid']])) {
					$thised = max($forumexceptions[$l['gradetypeid']][$l['userid']][0], $forumexceptions[$l['gradetypeid']][$l['userid']][1]);
					if ($limuser>0 && $gb[0][1][$col][3]==2) {  //change $avail past/cur/future
						if ($now<$thised) {
							$gb[0][1][$col][3] = 1;
						} else {
							$gb[0][1][$col][3] = 0;
						}
					}
					//TODO:  show latepass link in gradebook?
				}

				if ($l['score']!=null) {
					if (isset($gb[$row][1][$col][0])) {
						$gb[$row][1][$col][0] += 1*$l['score']; //adding up all forum scores
					} else {
						$gb[$row][1][$col][0] = 1*$l['score'];
					}
				}
				if (!isset($gb[$row][1][$col][1])) {
					if (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments']) {
						$gb[$row][1][$col][1] = '';
					} else {
						$gb[$row][1][$col][1] = 0; //no feedback
					}
				}
				if (trim($l['feedback'])!='') {
					if (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments']) {
						$gb[$row][1][$col][1] .= 'Post Feedback: <br/>'.$l['feedback'];
						//the feedback (for students)
					} else { //feedback
						$gb[$row][1][$col][1] = 1; //yes it has it (for teachers)
					}
				}
				$gb[$row][1][$col][2] = 1; //show link
                $gb[$row][1][$col][3] = 0; //is counted
                
                if (!empty($sectionlimit[$col]) && 
                    (empty($stusection[$l['userid']]) || $stusection[$l['userid']] != $sectionlimit[$col])
                ) {
                    // is not in this stu's section, so we won't count it, 
                    $gb[$row][1][$col][0] = ' ';
                } else if ($cntingb[$i] == 1) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpast[$row][$category[$i]][$col] = $gb[$row][1][$col][0];
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcur[$row][$category[$i]][$col] = $gb[$row][1][$col][0];
					}
					$cattotfuture[$row][$category[$i]][$col] = $gb[$row][1][$col][0];
				} else if ($cntingb[$i]==2) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpastec[$row][$category[$i]][$col] = $gb[$row][1][$col][0];
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcurec[$row][$category[$i]][$col] = $gb[$row][1][$col][0];
					}
					$cattotfutureec[$row][$category[$i]][$col] = $gb[$row][1][$col][0];
				}

			} else if ($l['gradetype']=='exttool') {
				if (!isset($exttoolidx[$l['gradetypeid']]) || !isset($sturow[$l['userid']]) || !isset($exttoolcol[$l['gradetypeid']])) {
					continue;
				}
				$i = $exttoolidx[$l['gradetypeid']];
				$row = $sturow[$l['userid']];
				$col = $exttoolcol[$l['gradetypeid']];

				$gb[$row][1][$col][2] = $l['id'];
				if ($l['score']!=null) {
					$gb[$row][1][$col][0] = 1*$l['score'];
				}
				if (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments']) {
					$gb[$row][1][$col][1] =  $l['feedback']; //the feedback (for students)
				} else if ($l['feedback']!='') { //feedback
					$gb[$row][1][$col][1] = 1; //yes it has it (for teachers)
				} else {
					$gb[$row][1][$col][1] = 0; //no feedback
				}

				if ($cntingb[$i] == 1) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpast[$row][$category[$i]][$col] = 1*$l['score'];
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcur[$row][$category[$i]][$col] = 1*$l['score'];
					}
					$cattotfuture[$row][$category[$i]][$col] = 1*$l['score'];
				} else if ($cntingb[$i]==2) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpastec[$row][$category[$i]][$col] = 1*$l['score'];
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcurec[$row][$category[$i]][$col] = 1*$l['score'];
					}
					$cattotfutureec[$row][$category[$i]][$col] = 1*$l['score'];
				}
			}
		}
	}

	//pull excusals
	$excused = array();
	$query = "SELECT userid,type,typeid FROM imas_excused WHERE courseid=?";
	if ($limuser>0) {
		$query .= " AND userid=?";
		$stm2 = $DBH->prepare($query);
		$stm2->execute(array($cid, $limuser));
	} else {
		$stm2 = $DBH->prepare($query);
		$stm2->execute(array($cid));
	}
	while ($r = $stm2->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($sturow[$r['userid']])) {
			continue;
		}
		if ($r['type']=='A') {
			$gb[$sturow[$r['userid']]][1][$assesscol[$r['typeid']]][14] = 1;
		} else if ($r['type']=='O') {
			$gb[$sturow[$r['userid']]][1][$gradecol[$r['typeid']]][14] = 1;
		} else if ($r['type']=='E') {
			$gb[$sturow[$r['userid']]][1][$exttoolcol[$r['typeid']]][14] = 1;
		} else if ($r['type']=='F') {
			$gb[$sturow[$r['userid']]][1][$discusscol[$r['typeid']]][14] = 1;
		}
	}

	//fill out cattot's with zeros && remove excused from tots
	for ($ln=1; $ln<count($sturow)+1; $ln++) {

		$cattotattempted[$ln] = $cattotcur[$ln];  //copy current to attempted - we will fill in zeros for past due stuff
		$cattotattemptedec[$ln] = $cattotcurec[$ln];
		foreach($assessidx as $aid=>$i) {
			$col = $assesscol[$aid];
			if (!empty($gb[$ln][1][$col][14]) && $cntingb[$i] == 1) {
				unset($cattotpast[$ln][$category[$i]][$col]);
				unset($cattotattempted[$ln][$category[$i]][$col]);
				unset($cattotcur[$ln][$category[$i]][$col]);
				unset($cattotfuture[$ln][$category[$i]][$col]);
			} else if (!isset($gb[$ln][1][$col][0]) || $gb[$ln][1][$col][3]%10==1) {
				if ($cntingb[$i] == 1) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpast[$ln][$category[$i]][$col] = 0;
						$cattotattempted[$ln][$category[$i]][$col] = 0;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcur[$ln][$category[$i]][$col] = 0;
					}
					$cattotfuture[$ln][$category[$i]][$col] = 0;
				} else if ($cntingb[$i]==2) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpastec[$ln][$category[$i]][$col] = 0;
						$cattotattemptedec[$ln][$category[$i]][$col] = 0;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcurec[$ln][$category[$i]][$col] = 0;
					}
					$cattotfutureec[$ln][$category[$i]][$col] = 0;
				}
			}
		}
		foreach($gradeidx as $aid=>$i) {
			$col = $gradecol[$aid];
			if (!empty($gb[$ln][1][$col][14]) && $cntingb[$i] == 1){
				unset($cattotpast[$ln][$category[$i]][$col]);
				unset($cattotattempted[$ln][$category[$i]][$col]);
				unset($cattotcur[$ln][$category[$i]][$col]);
				unset($cattotfuture[$ln][$category[$i]][$col]);
			} else if (!isset($gb[$ln][1][$col][0])) {
				if ($cntingb[$i] == 1) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpast[$ln][$category[$i]][$col] = 0;
						$cattotattempted[$ln][$category[$i]][$col] = 0;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcur[$ln][$category[$i]][$col] = 0;
					}
					$cattotfuture[$ln][$category[$i]][$col] = 0;
				} else if ($cntingb[$i]==2) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpastec[$ln][$category[$i]][$col] = 0;
						$cattotattemptedec[$ln][$category[$i]][$col] = 0;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcurec[$ln][$category[$i]][$col] = 0;
					}
					$cattotfutureec[$ln][$category[$i]][$col] = 0;
				}
			}
		}
		foreach($exttoolidx as $aid=>$i) {
			$col = $exttoolcol[$aid];
			if (!empty($gb[$ln][1][$col][14]) && $cntingb[$i] == 1) {
				unset($cattotpast[$ln][$category[$i]][$col]);
				unset($cattotattempted[$ln][$category[$i]][$col]);
				unset($cattotcur[$ln][$category[$i]][$col]);
				unset($cattotfuture[$ln][$category[$i]][$col]);
			} else if (!isset($gb[$ln][1][$col][0])) {
				if ($cntingb[$i] == 1) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpast[$ln][$category[$i]][$col] = 0;
						$cattotattempted[$ln][$category[$i]][$col] = 0;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcur[$ln][$category[$i]][$col] = 0;
					}
					$cattotfuture[$ln][$category[$i]][$col] = 0;
				} else if ($cntingb[$i]==2) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpastec[$ln][$category[$i]][$col] = 0;
						$cattotattemptedec[$ln][$category[$i]][$col] = 0;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcurec[$ln][$category[$i]][$col] = 0;
					}
					$cattotfutureec[$ln][$category[$i]][$col] = 0;
				}
			}
		}
		foreach($discussidx as $aid=>$i) {
			$col = $discusscol[$aid];
			if (!empty($gb[$ln][1][$col][14]) && $cntingb[$i] == 1) {
				unset($cattotpast[$ln][$category[$i]][$col]);
				unset($cattotattempted[$ln][$category[$i]][$col]);
				unset($cattotcur[$ln][$category[$i]][$col]);
				unset($cattotfuture[$ln][$category[$i]][$col]);
			} else if (!isset($gb[$ln][1][$col][0])) {
				if ($cntingb[$i] == 1) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpast[$ln][$category[$i]][$col] = 0;
						$cattotattempted[$ln][$category[$i]][$col] = 0;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcur[$ln][$category[$i]][$col] = 0;
					}
					$cattotfuture[$ln][$category[$i]][$col] = 0;
				} else if ($cntingb[$i]==2) {
					if ($gb[0][1][$col][3]<1) { //past
						$cattotpastec[$ln][$category[$i]][$col] = 0;
						$cattotattemptedec[$ln][$category[$i]][$col] = 0;
					}
					if ($gb[0][1][$col][3]<2) { //past or cur
						$cattotcurec[$ln][$category[$i]][$col] = 0;
					}
					$cattotfutureec[$ln][$category[$i]][$col] = 0;
				}
			}
		}
	}

	//create category possibles
	foreach(array_keys($cats) as $cat) {//foreach category
		$catposspast[$cat] = array();
		$catposscur[$cat] =array();
		$catpossfuture[$cat] = array();
		$catposspastec[$cat] = array();
		$catposscurec[$cat] =array();
		$catpossfutureec[$cat] = array();
		$catkeys = array_keys($category,$cat);
		foreach ($catkeys as $k) {
			if ($avail[$k]<1) { //is past
				if ($assessmenttype[$k]!="Practice" && $cntingb[$k]==1) {
					$catposspast[$cat][$colref[$k]] = $possible[$k]; //create category totals
				} else if ($cntingb[$k]==2) {
					$catposspastec[$cat][$colref[$k]] = 0;
				}
			}
			if ($avail[$k]<2) { //is past or current
				if ($assessmenttype[$k]!="Practice" && $cntingb[$k]==1) {
					$catposscur[$cat][$colref[$k]] = $possible[$k]; //create category totals
				} else if ($cntingb[$k]==2) {
					$catposscurec[$cat][$colref[$k]] = 0;
				}
			}
			//is anytime
			if ($assessmenttype[$k]!="Practice" && $cntingb[$k]==1) {
				$catpossfuture[$cat][$colref[$k]] = $possible[$k]; //create category totals
			} else if ($cntingb[$k]==2) {
				$catpossfutureec[$cat][$colref[$k]] = 0;
			}
		}
	}

	//create category headers

	$catorder = array_keys($cats);
	$overallptspast = 0;
	$overallptscur = 0;
	$overallptsfuture = 0;
	$overallptsattempted = 0;
	$cattotweightpast = 0;
	$cattotweightcur = 0;
	$cattotweightfuture = 0;
	$pos = 0;
	$catpossattempted = array();
	$catpossattemptedec = array();
	foreach($catorder as $cat) {//foreach category

		//cats: name,scale,scaletype,chop,drop,weight
		$catitemcntpast[$cat] = count($catposspast[$cat]);// + count($catposspastec[$cat]);
		$catitemcntcur[$cat] = count($catposscur[$cat]);// + count($catposscurec[$cat]);
		$catitemcntfuture[$cat] = count($catpossfuture[$cat]);// + count($catpossfutureec[$cat]);
		$catpossattempted[$cat] = $catposscur[$cat];  //a copy of the current for later use with attempted
		$catpossattemptedec[$cat] = $catposscurec[$cat];

		if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($catposspast[$cat])) { //if drop is set and have enough items
			asort($catposspast[$cat],SORT_NUMERIC);
			$gb[0][2][$pos][3] = array_sum(array_slice($catposspast[$cat],$cats[$cat][4]));
		} else {
			$gb[0][2][$pos][3] = array_sum($catposspast[$cat]);
		}
		if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($catposscur[$cat])) { //same for past&current
			asort($catposscur[$cat],SORT_NUMERIC);
			$gb[0][2][$pos][4] = array_sum(array_slice($catposscur[$cat],$cats[$cat][4]));
		} else {
			$gb[0][2][$pos][4] = array_sum($catposscur[$cat]);
		}
		if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($catpossfuture[$cat])) { //same for all items
			asort($catpossfuture[$cat],SORT_NUMERIC);
			$gb[0][2][$pos][5] = array_sum(array_slice($catpossfuture[$cat],$cats[$cat][4]));
		} else {
			$gb[0][2][$pos][5] = array_sum($catpossfuture[$cat]);
		}
		$gb[0][2][$pos][0] = $cats[$cat][0];
		$gb[0][2][$pos][1] = $cats[$cat][8];
		$gb[0][2][$pos][10] = $cat;
		$gb[0][2][$pos][12] = $cats[$cat][6];
		$gb[0][2][$pos][13] = $cats[$cat][7];
		$gb[0][2][$pos][14] = ($cats[$cat][4]!=0);

		if (count($catposspast[$cat])>0 || count($catposspastec[$cat])>0) {
			$gb[0][2][$pos][2] = 0; //scores in past
			$cattotweightpast += $cats[$cat][5];
			$cattotweightcur += $cats[$cat][5];
			$cattotweightfuture += $cats[$cat][5];
		} else if (count($catposscur[$cat])>0 || count($catposscurec[$cat])>0) {
			$gb[0][2][$pos][2] = 1; //scores in cur
			$cattotweightcur += $cats[$cat][5];
			$cattotweightfuture += $cats[$cat][5];
		} else if (count($catpossfuture[$cat])>0 || count($catpossfutureec[$cat])>0) {
			$gb[0][2][$pos][2] = 2; //scores in future
			$cattotweightfuture += $cats[$cat][5];
		} else {
			$gb[0][2][$pos][2] = 3; //no items
		}

		if ($useweights==0 && $cats[$cat][5]>-1) { //if scaling cat total to point value
			if ($catposspast[$cat]>0) {
				$gb[0][2][$pos][3] = $cats[$cat][5]; //score for past
			} else {
				$gb[0][2][$pos][3] = 0; //fix to 0 if no scores in past yet
			}
			if ($catposscur[$cat]>0) {
				$gb[0][2][$pos][4] = $cats[$cat][5]; //score for cur
			} else {
				$gb[0][2][$pos][4] = 0; //fix to 0 if no scores in cur/past yet
			}
			if ($catpossfuture[$cat]>0) {
				$gb[0][2][$pos][5] = $cats[$cat][5]; //score for future
			} else {
				$gb[0][2][$pos][5] = 0; //fix to 0 if no scores in future yet
			}
		}
		if ($useweights==1) {
			$gb[0][2][$pos][11] = $cats[$cat][5];
		}
		/*if ($cats[$cat][0]=='HW') {
			echo $gb[0][2][$pos][2];
		}*/

		$overallptspast += $gb[0][2][$pos][3];
		$overallptscur += $gb[0][2][$pos][4];
		$overallptsfuture += $gb[0][2][$pos][5];
		$pos++;
	}

	//find total possible points
	if ($useweights==0) { //use points grading method
		$gb[0][3][0] = $overallptspast;
		$gb[0][3][1] = $overallptscur;
		$gb[0][3][2] = $overallptsfuture;
	}

	$catposs = array();
	$catposs[0] = $catposspast;
	$catposs[1] = $catposscur;
	$catposs[2] = $catpossfuture;

	//create category totals
	for ($ln = 1; $ln<count($sturow)+1;$ln++) { //foreach student calculate category totals and total totals

		$pos = 0; //reset position for category totals

		//update attempted for this student
		unset($catpossstu);
		unset($cattotstu);
		unset($catpossstuec);
		unset($cattotstuec);
		$catpossstu[0] = $catposspast;
		$catpossstu[1] = $catposscur;
		$catpossstu[2] = $catpossfuture;
		$catpossstu[3] = $catpossattempted;
		$catpossstuec[0] = $catposspastec;
		$catpossstuec[1] = $catposscurec;
		$catpossstuec[2] = $catpossfutureec;
		$catpossstuec[3] = $catpossattemptedec;
		$cattotstu[0] = $cattotpast[$ln];
		$cattotstu[1] = $cattotcur[$ln];
		$cattotstu[2] = $cattotfuture[$ln];
		$cattotstu[3] = $cattotattempted[$ln];
		$cattotstuec[0] = $cattotpastec[$ln];
		$cattotstuec[1] = $cattotcurec[$ln];
		$cattotstuec[2] = $cattotfutureec[$ln];
		$cattotstuec[3] = $cattotattemptedec[$ln];
		$cattotweightstu = array(0,0,0,0);
		$totstu = array(0,0,0,0);

		//remove excused and non-attempted
		foreach($assessidx as $aid=>$i) {
            $col = $assesscol[$aid];
            if (!empty($sectionlimit[$col]) && 
                (empty($stusection[$gb[$ln][4][0]]) || $stusection[$gb[$ln][4][0]] != $sectionlimit[$col])
            ) {
                // assess is for diff sec; remove as avail
                $availstu[$ln][$aid] = 3;
            }
			if (isset($availstu[$ln][$aid]) && $availstu[$ln][$aid]!=$gb[0][1][$col][3]) {
				//if we have a per-stu override of avail
				//add to correct ones, when availstu < original
				for ($k=$availstu[$ln][$aid]; $k<$gb[0][1][$col][3]; $k++) {
					if ($gb[0][1][$col][4]==1) {
						$catpossstu[$k][$category[$i]][$col] = $catpossstu[$gb[0][1][$col][3]][$category[$i]][$col];
					} else if ($gb[0][1][$col][4]==2) {
						$catpossstuec[$k][$category[$i]][$col] = $catpossstuec[$gb[0][1][$col][3]][$category[$i]][$col];
					}
				}
				if ($availstu[$ln][$aid]==1) { //also copy into attempted if now cur
					if ($gb[0][1][$col][4]==1) {
						$catpossstu[3][$category[$i]][$col] = $catpossstu[$gb[0][1][$col][3]][$category[$i]][$col];
					} else if ($gb[0][1][$col][4]==2) {
						$catpossstuec[3][$category[$i]][$col] = $catpossstuec[$gb[0][1][$col][3]][$category[$i]][$col];
					}
				}

				//remove from originals if needed, when availstu > original
				for ($k=$gb[0][1][$col][3]; $k<$availstu[$ln][$aid]; $k++) {
					if ($gb[0][1][$col][4]==1) {
						unset($catpossstu[$k][$category[$i]][$col]);
					} else if ($gb[0][1][$col][4]==2) {
						unset($catpossstuec[$k][$category[$i]][$col]);
					}
				}
				if ($gb[0][1][$col][3]==1) { //need extra unset for attempted if was cur
					if ($gb[0][1][$col][4]==1) {
						unset($catpossstu[3][$category[$i]][$col]);
					} else if ($gb[0][1][$col][4]==2) {
						unset($catpossstuec[3][$category[$i]][$col]);
					}
				}
			}
			if (!isset($gb[$ln][1][$col][0]) &&
				((isset($availstu[$ln][$aid]) && $availstu[$ln][$aid]==1) ||
				(!isset($availstu[$ln][$aid]) && $gb[0][1][$col][3]==1))) { //if cur , clear out of cattotattempted
				if ($gb[0][1][$col][4]==1) {
					unset($catpossstu[3][$category[$i]][$col]);
				} else if ($gb[0][1][$col][4]==2) {
					unset($catpossstuec[3][$category[$i]][$col]);
				}
			}
			if (!empty($gb[$ln][1][$col][14]) && $gb[0][1][$col][4]==1) { //excused; remove from poss
				for ($j=0;$j<4;$j++) {
					unset($catpossstu[$j][$category[$i]][$col]);
				}
			}
		}
		foreach($gradeidx as $aid=>$i) {
			$col = $gradecol[$aid];
			if (!empty($gb[$ln][1][$col][14]) && $gb[0][1][$col][4]==1) {
				for ($j=0;$j<4;$j++) {
					unset($catpossstu[$j][$category[$i]][$col]);
				}
			}
		}
		foreach($exttoolidx as $aid=>$i) {
			$col = $exttoolcol[$aid];
			//remove excused
			if (!empty($gb[$ln][1][$col][14]) && $gb[0][1][$col][4]==1) {
				for ($j=0;$j<4;$j++) {
					unset($catpossstu[$j][$category[$i]][$col]);
				}
			}
			// remove from attempted if cur but no stu score
			if (!isset($gb[$ln][1][$col][0]) && $gb[0][1][$col][3]==1) {
				if ($gb[0][1][$col][4]==1) {
					unset($catpossstu[3][$category[$i]][$col]);
				} else if ($gb[0][1][$col][4]==2) {
					unset($catpossstuec[3][$category[$i]][$col]);
				}
			}
		}
		foreach($discussidx as $aid=>$i) {
            $col = $discusscol[$aid];
            if (!empty($sectionlimit[$col]) && 
                (empty($stusection[$gb[$ln][4][0]]) || $stusection[$gb[$ln][4][0]] != $sectionlimit[$col])
            ) {
                // assess is for diff sec; remove from poss
                for ($j=0;$j<4;$j++) {
                    unset($catpossstu[$j][$category[$i]][$col]);
                    unset($catpossstuec[$j][$category[$i]][$col]);
				}
            }
			if (!empty($gb[$ln][1][$col][14]) && $gb[0][1][$col][4]==1) {
				for ($j=0;$j<4;$j++) {
					unset($catpossstu[$j][$category[$i]][$col]);
				}
			}
			// remove from attempted if cur but no stu score
			if (!isset($gb[$ln][1][$col][0]) && $gb[0][1][$col][3]==1) {
				if ($gb[0][1][$col][4]==1) {
					unset($catpossstu[3][$category[$i]][$col]);
				} else if ($gb[0][1][$col][4]==2) {
					unset($catpossstuec[3][$category[$i]][$col]);
				}
			}
		}

		foreach($catorder as $cat) {//foreach category
			for ($stype=0;$stype<4;$stype++) {  //for each of past, cur, future, and attempted
				if (isset($cattotstu[$stype][$cat])) {
					//cats: name,scale,scaletype,chop,drop,weight
					if ($cats[$cat][7]==1) { //if using percent-based drops
						foreach($cattotstu[$stype][$cat] as $col=>$v) {
							if ($gb[0][1][$col][2] == 0) {
								$cattotstu[$stype][$cat][$col] = 0;
							} else {
								$cattotstu[$stype][$cat][$col] = $v/$gb[0][1][$col][2];	//convert to percents
							}
						}
						if (isset($cattotstuec[$stype][$cat])) {
							foreach($cattotstuec[$stype][$cat] as $col=>$v) {
								if ($gb[0][1][$col][2] == 0) {
									$cattotstuec[$stype][$cat][$col] = 0;
								} else {
									$cattotstuec[$stype][$cat][$col] = $v/$gb[0][1][$col][2];	//convert to percents
								}
							}
						}
						if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattotstu[$stype][$cat])) { //if drop is set and have enough items
							asort($cattotstu[$stype][$cat],SORT_NUMERIC);

							if ($cats[$cat][4]<0) {  //doing keep n
								$ntodrop = count($cattotstu[$stype][$cat])+$cats[$cat][4];
							} else {  //doing drop n
								$ntodrop = $cats[$cat][4];// - ($catitemcntattempted[$cat]-count($cattotstu[$stype][$cat]));
							}

							if ($ntodrop>0) {
								$ndropcnt = 0;
								foreach ($cattotstu[$stype][$cat] as $col=>$v) {
									$gb[$ln][1][$col][5] += pow(2,$stype);// 8; //mark as dropped
									unset($catpossstu[$stype][$cat][$col]); //remove from category possible
									$ndropcnt++;
									if ($ndropcnt==$ntodrop) { break;}
								}
							}

							while (count($cattotstu[$stype][$cat])<count($catpossstu[$stype][$cat])) {
								array_unshift($cattotstu[$stype][$cat],0);
							}
							$cattotstu[$stype][$cat] = array_slice($cattotstu[$stype][$cat],$cats[$cat][4]);
							//$tokeep = ($cats[$cat][4]<0)? abs($cats[$cat][4]) : ($catitemcntattempted[$cat] - $cats[$cat][4]);
							$tokeep = count($catpossstu[$stype][$cat]);

						} else {
							$tokeep = count($cattotstu[$stype][$cat]);
						}
						if ($tokeep > 0) {
							$cattotstu[$stype][$cat] = array_sum($catpossstu[$stype][$cat])*array_sum($cattotstu[$stype][$cat])/($tokeep);
						} else {
							$cattotstu[$stype][$cat] = 0;
						}
						if (isset($cattotstuec[$stype][$cat])) {
							$cattotstuec[$stype][$cat] = array_sum($catpossstu[$stype][$cat])*array_sum($cattotstuec[$stype][$cat])/$tokeep;
						}
					} else {
						if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattotstu[$stype][$cat])) { //if drop is set and have enough items
							if ($cats[$cat][4]<0) {  //doing keep n
								$ntodrop = count($cattotstu[$stype][$cat])+$cats[$cat][4];
							} else {  //doing drop n
								$ntodrop = $cats[$cat][4];// - ($catitemcntattempted[$cat]-count($cattotstu[$stype][$cat]));
							}
							//adjust for missing assignments - shouldn't happen since zero'ed out?
							if (count($cattotstu[$stype][$cat])<count($catpossstu[$stype][$cat])) {
								$ntodrop -= (count($catpossstu[$stype][$cat]) - count($cattotstu[$stype][$cat]));
							}

							if ($ntodrop>0) {
								//this will figure out how much dropping each score will help the grade
								//then drop the one(s) that help the most
								$dropbenefit = array();
								$predroptot = array_sum($cattotstu[$stype][$cat]);
								$predropposs = array_sum($catpossstu[$stype][$cat]);
								foreach ($cattotstu[$stype][$cat] as $col=>$v) {
									if ($catpossstu[$stype][$cat][$col] < $predropposs) {
										$dropbenefit[$col] = ($predroptot*$catpossstu[$stype][$cat][$col] - $predropposs*$v)/($predropposs*($predropposs-$catpossstu[$stype][$cat][$col]));
									} else {
										$dropbenefit[$col] = 0;
									}
								}
								arsort($dropbenefit, SORT_NUMERIC);
								$ndropcnt = 0;
								foreach ($dropbenefit as $col=>$v) {
									$gb[$ln][1][$col][5] += pow(2,$stype); //mark as dropped
									unset($catpossstu[$stype][$cat][$col]); //remove from category possible
									unset($cattotstu[$stype][$cat][$col]); //remove from category total
									$ndropcnt++;
									if ($ndropcnt==$ntodrop) { break;}
								}
							}
						}
						$cattotstu[$stype][$cat] = array_sum($cattotstu[$stype][$cat]);
						if (isset($cattotstuec[$stype][$cat])) {
							$cattotstuec[$stype][$cat] = array_sum($cattotstuec[$stype][$cat]);
						}
					}
					$catpossstu[$stype][$cat] = array_sum($catpossstu[$stype][$cat]);

					if ($cats[$cat][1]!=0) { //scale is set
						if ($cats[$cat][2]==0) { //pts scale
							$cattotstu[$stype][$cat] = $catpossstu[$stype][$cat]*($cattotstu[$stype][$cat]/$cats[$cat][1]);
						} else if ($cats[$cat][2]==1) { //percent scale
							$cattotstu[$stype][$cat] = $cattotstu[$stype][$cat]*(100/($cats[$cat][1]));
						}
					}
					if (isset($cattotstuec[$stype][$cat])) { //add in EC
						$cattotstu[$stype][$cat] += $cattotstuec[$stype][$cat];
					}
					if ($useweights==0 && $cats[$cat][5]>-1) {//use fixed pt value for cat
						$cattotstu[$stype][$cat] = ($catpossstu[$stype][$cat]==0)?0:$cats[$cat][5]*($cattotstu[$stype][$cat]/$catpossstu[$stype][$cat]);
						$catpossstu[$stype][$cat] = ($catpossstu[$stype][$cat]==0)?0:$cats[$cat][5];
					}

					if ($cats[$cat][3]>0) { //chop score - no over 100%
						if ($useweights==0  && $cats[$cat][5]>-1) { //set cat pts
							$cattotstu[$stype][$cat] = min($cats[$cat][5]*$cats[$cat][3],$cattotstu[$stype][$cat]);
						} else {
							$cattotstu[$stype][$cat] = min($catpossstu[$stype][$cat]*$cats[$cat][3],$cattotstu[$stype][$cat]);
						}
					}

					//tot for cat
					$gb[$ln][2][$pos][$stype] = round($cattotstu[$stype][$cat],1);

					//cat poss
					$gb[$ln][2][$pos][4+$stype] = round($catpossstu[$stype][$cat],1);
				} else if (isset($cattotstuec[$stype][$cat])) {
					$cattotstu[$stype][$cat] = array_sum($cattotstuec[$stype][$cat]);
					$catpossstu[$stype][$cat] = 0;
					$gb[$ln][2][$pos][$stype] = round($cattotstu[$stype][$cat],1);
					$gb[$ln][2][$pos][4+$stype] = 0;
				} else { //no items in category yet?
					$gb[$ln][2][$pos][$stype] = 0;
					$gb[$ln][2][$pos][4+$stype] = 0;
					$catpossstu[$stype][$cat] =0;
				}
				if ($catpossstu[$stype][$cat]>0 || count($catpossstuec[$stype][$cat])>0) {
					$cattotweightstu[$stype] += $cats[$cat][5];
				}
			}
			$pos++;

		}

		//calculate totals
		if ($useweights==1) {  //weighted grades
			for ($stype=0;$stype<4;$stype++) {  //for each of past, cur, future, and attempted
				$gb[$ln][3][4+$stype] = 0;
				$gb[$ln][3][$stype] = 0;
				if (isset($cattotstu[$stype]) && is_array($cattotstu[$stype])) {
					foreach ($cattotstu[$stype] as $cat=>$v) {
						if ($catpossstu[$stype][$cat] > 0) {
							$gb[$ln][3][$stype] += $cats[$cat][5]*$v/$catpossstu[$stype][$cat];
						}
					}
					$gb[$ln][3][$stype] = round($gb[$ln][3][$stype], 3);
				}
				if (isset($catpossstu[$stype]) && is_array($catpossstu[$stype])) {
					foreach ($catpossstu[$stype] as $cat=>$v) {
						if ($v>0) {
							$gb[$ln][3][4+$stype] += $cats[$cat][5];
						}
					}
					$gb[$ln][3][4+$stype] = round($gb[$ln][3][4+$stype], 3);
				}
			}
		} else {  //points possible grades
			for ($stype=0;$stype<4;$stype++) {  //for each of past, cur, future, and attempted
				if (!isset($cattotstu[$stype]) || !is_array($cattotstu[$stype])) {
					$gb[$ln][3][$stype] = 0;
				} else {
					$gb[$ln][3][$stype] = round(array_sum($cattotstu[$stype]), 1);
				}
				if (!isset($catpossstu[$stype]) || !is_array($catpossstu[$stype])) {
					$gb[$ln][3][4+$stype] = 0;
				} else {
					$gb[$ln][3][4+$stype] = round(array_sum($catpossstu[$stype]),1);
				}
			}
		}
	}

	if ($limuser<1) {
		//create averages
		$gb[$ln][0][0] = "Averages";

		for ($j=0;$j<count($gb[0][1]);$j++) { //foreach assessment
			$avgs = array();
			$avgtime = array();
			$avgtimedisp = array();

			for ($i=1;$i<$ln;$i++) { //foreach student
				if (isset($gb[$i][1][$j][0]) && $gb[$i][4][1]==0) { //score exists and student is not locked
					if ($gb[$i][1][$j][3]%10==0 && is_numeric($gb[$i][1][$j][0])) {
						$avgs[] = $gb[$i][1][$j][0];
						if ($limuser==-1 && $gb[0][1][$j][6]==0) { //for online, if showning avgs
							$avgtime[] = $gb[$i][1][$j][7];
							$avgtimedisp[] = $gb[$i][1][$j][8];
						}
					}
				}
			}

			if (count($avgs)>0) {
				sort($avgs, SORT_NUMERIC);
				$fivenum = array();
				for ($k=0; $k<5; $k++) {
					$fivenum[] = gbpercentile($avgs,$k*25);
				}
				$fivenumsum = 'n = '.count($avgs).'<br/>';
				$fivenumsum .= implode(',&nbsp;',$fivenum);
				if ($gb[0][1][$j][2]>0) {
					for ($k=0; $k<5; $k++) {
						$fivenum[$k] = round(100*$fivenum[$k]/$gb[0][1][$j][2],1);
					}
					$fivenumsum .= '<br/>'.implode('%,&nbsp;',$fivenum).'%';
				}
				if ($limuser==-1 && count($avgtime)>0) {
					$gb[$ln][1][$j][7] = round(array_sum($avgtime)/count($avgtime),1);
					$gb[$ln][1][$j][8] = round(array_sum($avgtimedisp)/count($avgtimedisp),1);
				}
				$gb[$ln][1][$j][0] = round(array_sum($avgs)/count($avgs),1);
				$gb[$ln][1][$j][4] = 'average';
			} else {
				$fivenumsum = '';
			}
			$gb[0][1][$j][9] = $fivenumsum;
			//$gb[0][1][$j][9] = gbpercentile($avgs[$j],0).',&nbsp;'.gbpercentile($avgs[$j],25).',&nbsp;'.gbpercentile($avgs[$j],50).',&nbsp;'.gbpercentile($avgs[$j],75).',&nbsp;'.gbpercentile($avgs[$j],100);

		}

		//cat avgs
		$catavgs = array();
		for ($j=0;$j<count($gb[0][2]);$j++) { //category headers
			$catavgs[0] = array();
			$catavgs[1] = array();
			$catavgs[2] = array();
			$catavgs[3] = array();
			for ($i=1;$i<$ln;$i++) { //foreach student
				if ($gb[$i][4][1]==0) { //if not locked
					for ($k=0;$k<4;$k++) {
						if ($k<3 && $gb[0][2][$j][13]==0 && $gb[0][2][$j][14]==0) {
							//if not averaged percents, and no drops
							$catavgs[$k][] = $gb[$i][2][$j][$k];
						} else if ($gb[$i][2][$j][4+$k]>0) {
							$catavgs[$k][] = round(100*$gb[$i][2][$j][$k]/$gb[$i][2][$j][4+$k],1);
						}
					}
				}
			}
			for ($i=0; $i<4; $i++) {
				if (count($catavgs[$i])>0) {
					sort($catavgs[$i], SORT_NUMERIC);
					$fivenum = array();
					$fivenumsum = '';
					for ($k=0; $k<5; $k++) {
						$fivenum[] = gbpercentile($catavgs[$i],$k*25);
					}

					if ($i<3 && $gb[0][2][$j][13]==0 && $gb[0][2][$j][14]==0) {
						//if not attempted, and not using averaged percents, and no drops
						//then we'll show scores in 5-num. Otherwise just show percents
						if ($useweights==0) {
							$fivenumsum = implode(',&nbsp;',$fivenum);
							if ($gb[0][2][$j][3+$i]>0) {
								$fivenumsum .= '<br/>';
							}
						}
						if ($i<3 && $gb[0][2][$j][3+$i]>0) {
							for ($k=0; $k<5; $k++) {
								$fivenum[$k] = round(100*$fivenum[$k]/$gb[0][2][$j][3+$i],1);
							}
							$fivenumsum .= implode('%,&nbsp;',$fivenum).'%';
						}
					} else {
						$fivenumsum = implode('%,&nbsp;',$fivenum).'%';
					}
				} else {
					$fivenumsum = '';
				}
				$gb[0][2][$j][6+$i] = $fivenumsum;

				//averages row
				if (count($catavgs[$i])>0) {
					$gb[$ln][2][$j][$i] = round(array_sum($catavgs[$i])/count($catavgs[$i]),1);
				} else {
					$gb[$ln][2][$j][$i] = 0;
				}
				if ($i<3 && $gb[0][2][$j][13]==0 && $gb[0][2][$j][14]==0) {
					//average is points - grab points possible
					$gb[$ln][2][$j][4+$i] = $gb[0][2][$j][3+$i];
				} else {
					//average is percent
					$gb[$ln][2][$j][4+$i] = 0;
				}
			}
		}

		//tot avgs
		for ($j=0;$j<4;$j++) {
			if ($gb[1][3][$j]===null) {continue;}
			$totavgs = array();
			for ($i=1;$i<$ln;$i++) { //foreach student
				if ($gb[$i][4][1]==0) { //if not locked
					if ($gb[$i][3][4+$j]>0) {
						$totavgs[] = round(100*$gb[$i][3][$j]/$gb[$i][3][4+$j],1);
					} else {
						$totavgs[] = 0;
					}
				}
			}
			$fivenumsum = '';
			if (count($totavgs)>0) {
				sort($totavgs, SORT_NUMERIC);
				$fivenum = array();

				for ($k=0; $k<5; $k++) {
					$fivenum[] = gbpercentile($totavgs,$k*25);
				}
				$fivenumsum .= implode('%,&nbsp;',$fivenum).'%';
			}
			$gb[0][3][3+$j] = $fivenumsum;

			//averages row
			if (count($totavgs)>0) {
				$gb[$ln][3][$j] = round(array_sum($totavgs)/count($totavgs),1);
			} else {
				$gb[$ln][3][$j] = 0;
			}
			$gb[$ln][3][4+$j] = 100;
		}

		$gb[$ln][4][0] = -1;
	}
	if ($limuser>0) { //mark reqscoreaid
		foreach ($gb[0][1] as $col=>$gbitem) {
			if ($gbitem[6]==0) {
				$k = $assessidx[$gbitem[7]];
				$gb[1][1][$col][13] = 1;
				if (isset($reqscores[$k]) && $reqscores[$k]['aid']>0) {
					$colofprereq = $assesscol[$reqscores[$k]['aid']];
					if (!isset($gb[1][1][$colofprereq][0]) ||
					   ($reqscores[$k]['calctype']==0 && $gb[1][1][$colofprereq][0] < $reqscores[$k]['score']) ||
					   ($reqscores[$k]['calctype']==2 && 100*$gb[1][1][$colofprereq][0]/$gb[0][1][$colofprereq][2]+1e-4 < $reqscores[$k]['score'])) {
						$gb[1][1][$col][13] = 0;
					}
				}
			}
		}
	}
	if ($limuser==-1) {
		$gb[1] = $gb[$ln];
	}
	return $gb;
}
function gbpercentile($a,$p) {
	if ($p==0) {
		return $a[0];
	} else if ($p==100) {
		return $a[count($a)-1];
	}

	$l = $p*count($a)/100;
	if (floor($l)==$l) {
		return (($a[$l-1]+$a[$l])/2);
	} else {
		return ($a[ceil($l)-1]);
	}
}

function buildFeedback2($scoreddata) {
	$scoreddata = json_decode(gzdecode($scoreddata), true);
	$out = '';

	foreach ($scoreddata['assess_versions'] as $av => $aver) {
		$fbdisp = '';
		foreach ($aver['questions'] as $qn => $qdata) {
			$qfb = '';
			foreach ($qdata['question_versions'] as $qv => $qver) {
				if (!empty($qver['feedback'])) {
					if (count($qdata['question_versions'])>1) {
						$qfb .= '<p>'.sprintf(_('Feedback on Version %d:'), $qv+1).'</p>';
					}
					$qfb .= '<div class="fbbox">'.Sanitize::outgoingHtml($qver['feedback']).'</div>';
				}
			}
			if ($qfb != '') {
				$fbdisp .= '<p>'.sprintf(_('Feedback on Question %d:'), Sanitize::onlyInt($qn+1)).'</p>';
				$fbdisp .= $qfb;
			}
		}
		if (!empty($aver['feedback'])) {
			if (count($scoreddata['assess_versions'])>1) {
				$fbdisp .= '<p>'._('Overall feedback on this attempt:').'</p>';
			} else {
				$fbdisp .= '<p>'._('Overall feedback:').'</p>';
			}
			$fbdisp .= '<div class="fbbox">'.Sanitize::outgoingHtml($aver['feedback']).'</div>';
		}
		if ($fbdisp != '') {
			if (count($scoreddata['assess_versions'])>1) {
				$out .= '<p><b>'.sprintf(_('Feedback on attempt %d'), Sanitize::onlyInt($av)+1).'</b></p>';
			}
			$out .= $fbdisp;
		}
	}
	return $out;
}
?>
