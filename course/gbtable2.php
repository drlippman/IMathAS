<?php

require("../validate.php");
$cid = 264;
$isteacher = true;
$gbt = gbtable();
print_r($gbt);


//this function is used by gbtable - currently already in gradebook.php
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

/****
The super-nasty gradebook function!  
gbtable([userid])
Student: automatically limits to their userid
Teacher: gives all students unless userid is provided
No averages are calculated by this function

Format of output:
row[0] header

row[0][0] biographical
row[0][0][0] = "Name"
row[0][0][1] = "SID"

row[0][1] scores
row[0][1][0] first score
row[0][1][0][0] = "Assessment name"
row[0][1][0][1] = "Category"
row[0][1][0][2] = points possible
row[0][1][0][3] = 0 past, 1 current, 2 future
row[0][1][0][4] = 0 no count and hide, 1 count, 2 EC, 3 no count
row[0][1][0][5] = 0 regular, 1 practice test
row[0][1][0][6] = 0 online, 1 offline, 2 discussion
row[0][1][0][7] = assessmentid, gbitemid, forumid

row[0][2] category totals
row[0][2][0][0] = "Category Name"
row[0][2][0][1] = "Category"
row[0][2][0][2] = 0 if any scores in past, 1 if any scores in past/current, 2 if all scores in future
		  3 no items at all
row[0][2][0][3] = total possible for past
row[0][2][0][4] = total possible for past/current
row[0][2][0][5] = total possible for all

row[0][3][0] = total possible past
row[0][3][1] = total possible past&current
row[0][3][2] = total possible all

row[1] first student data row
row[1][0] biographical
row[1][0][0] = "Name"

row[1][1] scores
row[1][1][0] first score - assessment
row[1][1][0][0] = score
row[1][1][0][1] = 0 no comment, 1 has comment - is comment in stu view
row[1][1][0][2] = show link: 0 no, 1 yes
row[1][1][0][3] = other info: 0 none, 1 NC, 2 IP, 3 OT, 4 PT
row[1][1][0][4] = asid, or 'new'

row[1][1][1] = offline
row[1][1][1][0] = score
row[1][1][1][1] = 0 no comment, 1 has comment - is comment in stu view
row[1][1][2] - discussion
row[1][1][2][0] = score

row[1][2] category totals
row[1][2][0][0] = cat total past
row[1][2][0][0] = cat total past/current
row[1][2][0][0] = cat total future

row[1][3] total totals
row[1][3][0] = total possible past
row[1][3][1] = total possible past&current
row[1][3][2] = total possible all
row[1][3][3] = % past - null if weighted graded
row[1][3][4] = % past&current
row[1][3][5] = % all

row[1][4][0] = userid

****/

function gbtable() {
	global $cid,$isteacher,$istutor,$tutorid,$userid,$catfilter,$secfilter;
	if ($isteacher && func_num_args()>1) {
		$limuser = func_get_arg(1);
	} else if (!$isteacher && !$istutor) {
		$limuser = $userid;
	} else {
		$limuser = 0;
	}
	
	$isdiag = false;
	$category = array();
	if ($isteacher || $istutor) {
		$query = "SELECT sel1name,sel2name FROM imas_diags WHERE cid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			$isdiag = true;
			$sel1name = mysql_result($result,0,0);
			$sel2name = mysql_result($result,0,1);
		}
	}
	$gb = array();
	
	$ln = 0;
	
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
	$query = "SELECT count(id) FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.section IS NOT NULL";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_result($result,0,0)>0) {
		$hassection = true;
	} else {
		$hassection = false;
	}
	$query = "SELECT count(id) FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.code IS NOT NULL";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_result($result,0,0)>0) {
		$hascode = true;
	} else {
		$hascode = false;
	}
	if ($hassection) {
		$gb[0][0][] = "Section";
	}
	if ($hascode) {
		$gb[0][0][] = "Code";
	}
	//Pull Assessment Info
	$now = time();
	$query = "SELECT id,name,defpoints,deffeedback,timelimit,minscore,startdate,enddate,itemorder,gbcategory,cntingb FROM imas_assessments WHERE courseid='$cid' ";
	if (!$isteacher) {
		$query .= "AND cntingb>0 ";
	}
	if (!$isteacher) {
		$query .= "AND startdate<$now ";
	}
	if ($catfilter>-1) {
		$query .= "AND gbcategory='$catfilter' ";
	}
	$query .= "ORDER BY enddate";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$overallpts = 0;
	$now = time();
	$kcnt = 0;
	$assessments = array();
	$grades = array();
	$timelimits = array();
	$minscores = array();
	$assessmenttype = array();
	$enddate = array();
	$avail = array();
	$sa = array();
	$category = array();
	$name = array();
	$possible = array();
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		$assessments[$kcnt] = $line['id'];
		$timelimits[$kcnt] = $line['timelimit'];
		$minscores[$kcnt] = $line['minscore'];
		$deffeedback = explode('-',$line['deffeedback']);
		$assessmenttype[$kcnt] = $deffeedback[0];
		$sa[$kcnt] = $deffeedback[1];
		$enddate[$kcnt] = $line['enddate'];
		if ($now<$line['startdate']) {
			$avail[$kcnt] = 2;
		} else if ($now < $line['enddate']) {
			$avail[$kcnt] = 1;
		} else {
			$avail[$kcnt] = 0;
		}
		$category[$kcnt] = $line['gbcategory'];
		$name[$kcnt] = $line['name'];
		$cntingb[$kcnt] = $line['cntingb']; //0: ignore, 1: count, 2: extra credit, 3: no count but show
		
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
		
		$query = "SELECT points,id FROM imas_questions WHERE assessmentid='{$line['id']}'";
		$result2 = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$totalpossible = 0;
		while ($r = mysql_fetch_row($result2)) {
			if (in_array($r[1],$aitems)) { //only use first item from grouped questions for total pts
				if ($r[0]==9999) {
					$totalpossible += $aitemcnt[$k]*$line['defpoints']; //use defpoints
				} else {
					$totalpossible += $aitemcnt[$k]*$r[0]; //use points from question
				}
			}
		}
		$possible[$kcnt] = $totalpossible;
		$kcnt++;
	}
	
	//Pull Offline Grade item info
	$query = "SELECT * from imas_gbitems WHERE courseid='$cid' ";
	if (!$isteacher) {
		$query .= "AND showdate<$now ";
	}
	if (!$isteacher) {
		$query .= "AND cntingb>0 ";
	}
	if ($catfilter>-1) {
		$query .= "AND gbcategory='$catfilter' ";
	}
	$query .= "ORDER BY showdate";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		$grades[$kcnt] = $line['id'];
		$assessmenttype[$kcnt] = "Offline";
		$category[$kcnt] = $line['gbcategory'];
		$enddate[$kcnt] = $line['showdate'];
		if ($now < $line['showdate']) {
			$avail[$kcnt] = 2;
		} else {
			$avail[$kcnt] = 0;
		}
		$possible[$kcnt] = $line['points'];
		$name[$kcnt] = $line['name'];
		$cntingb[$kcnt] = $line['cntingb'];
		$kcnt++;
	}
	
	//Pull Discussion Grade info
	$query = "SELECT id,name,gbcategory,enddate,points FROM imas_forums WHERE courseid='$cid' AND points>0 ";
	if (!$isteacher) {
		$query .= "AND startdate<$now ";
	}
	if ($catfilter>-1) {
		$query .= "AND gbcategory='$catfilter' ";
	}
	$query .= "ORDER BY enddate";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		$discuss[$kcnt] = $line['id'];
		$assessmenttype[$kcnt] = "Discussion";
		$category[$kcnt] = $line['gbcategory'];
		$enddate[$kcnt] = $line['enddate'];
		if ($now < $line['showdate']) {
			$avail[$kcnt] = 2;
		} else {
			$avail[$kcnt] = 0;
		}
		$possible[$kcnt] = $line['points'];
		$name[$kcnt] = $line['name'];
		$cntingb[$kcnt] = 1;
		$kcnt++;
	}
	
	//Pull Gradebook Scheme info
	$query = "SELECT useweights,orderby,defaultcat,usersort FROM imas_gbscheme WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	list($useweights,$orderby,$defaultcat,$usersort) = mysql_fetch_row($result);
	
	$cats = array();
	$catcolcnt = 0;
	//Pull Categories:  Name, scale, scaletype, chop, drop, weight
	if (in_array(0,$category)) {  //define default category, if used
		$cats[0] = explode(',',$defaultcat); 
		array_unshift($cats[0],"Default");
		array_push($cats[0],$catcolcnt);
		$catcolcnt++;
		
	}
	
	$query = "SELECT id,name,scale,scaletype,chop,dropn,weight FROM imas_gbcats WHERE courseid='$cid' ";
	$query .= "ORDER BY name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		if (in_array($row[0],$category)) { //define category if used
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
	} else if ($orderby==3) { //order $category alpha
		asort($name);
		$newcategory = array();
		foreach ($name as $k=>$v) {
			$newcategory[$k] = $category[$k];
		}
		$category = $newcategory;
	}
	foreach(array_keys($cats) as $cat) {//foreach category
		$catposs[$cat] = array();
		$catpossfuture[$cat] = array();
		$catkeys = array_keys($category,$cat); //pull items in that category
		if (($orderby&1)==1) { //order by category
			array_splice($itemorder,count($itemorder),0,$catkeys);
		}
		foreach ($catkeys as $k) {
			if ($avail[$k]<1) { //is past
				if ($assessmenttype[$k]!="Practice" && $cntingb[$k]==1) {
					$catposspast[$cat][] = $possible[$k]; //create category totals
				} else if ($cntingb[$k]==2) {
					$catposspastec[$cat][] = 0;
				}
			}
			if ($avail[$k]<2) { //is past or current
				if ($assessmenttype[$k]!="Practice" && $cntingb[$k]==1) {
					$catposscur[$cat][] = $possible[$k]; //create category totals
				} else if ($cntingb[$k]==2) {
					$catposscurec[$cat][] = 0;
				}
			}
			//is anytime
			if ($assessmenttype[$k]!="Practice" && $cntingb[$k]==1) {
				$catpossfuture[$cat][] = $possible[$k]; //create category totals
			} else if ($cntingb[$k]==2) {
				$catpossfutureec[$cat][] = 0;
			}
			
			if (($orderby&1)==1) {  //display item header if displaying by category
				//$cathdr[$pos] = $cats[$cat][6];
				$gb[0][1][$pos][0] = $name[$k]; //item name
				$gb[0][1][$pos][1] = $cats[$cat][0]; //item category name
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
					$assesscol[$assessments[$k]] = $pos;
				} else if (isset($grades[$k])) {
					$gb[0][1][$pos][6] = 1; //0 online, 1 offline
					$gb[0][1][$pos][7] = $grades[$k];
					$gradecol[$grades[$k]] = $pos;
				} else if (isset($discuss[$k])) {
					$gb[0][1][$pos][6] = 2; //0 online, 1 offline, 2 discuss
					$gb[0][1][$pos][7] = $discuss[$k];
					$discusscol[$discuss[$k]] = $pos;
				}
					
				
				$pos++;
			}
		}
	}
	if (($orderby&1)==0) {//if not grouped by category
		if ($orderby==0) {
			asort($enddate,SORT_NUMERIC);
			$itemorder = array_keys($enddate);
		} else if ($orderby==2) {
			asort($name);
			$itemorder = array_keys($name);
		}
		foreach ($itemorder as $k) {
			$gb[0][1][$pos][0] = $name[$k]; //item name
			$gb[0][1][$pos][1] = $cats[$cat][0]; //item category name
			$gb[0][1][$pos][2] = $possible[$k]; //points possible
			$gb[0][1][$pos][3] = $avail[$k]; //0 past, 1 current, 2 future
			$gb[0][1][$pos][4] = $cntingb[$k]; //0 no count and hide, 1 count, 2 EC, 3 no count
			$gb[0][1][$pos][5] = ($assessmenttype[$k]=="Practice");  //0 regular, 1 practice test
			if (isset($assessments[$k])) {
				$gb[0][1][$pos][6] = 0; //0 online, 1 offline
				$gb[0][1][$pos][7] = $assessments[$k];
				$assesscol[$assessments[$k]] = $pos;
			} else if (isset($grades[$k])) {
				$gb[0][1][$pos][6] = 1; //0 online, 1 offline
				$gb[0][1][$pos][7] = $grades[$k];
				$gradecol[$grades[$k]] = $pos;
			} else if (isset($discuss[$k])) {
				$gb[0][1][$pos][6] = 2; //0 online, 1 offline, 2 discuss
				$gb[0][1][$pos][7] = $discuss[$k];
				$discusscol[$discuss[$k]] = $pos;
			}
			
			$pos++;
		}
	} 
	$totalspos = $pos;
	//create category headers
	
	$catorder = array_keys($cats);
	$overallptspast = 0;
	$overallptscur = 0;
	$overallptsfuture = 0;
	$pos = 0;
	foreach($catorder as $cat) {//foreach category
		
		//cats: name,scale,scaletype,chop,drop,weight
		$catitemcntpast[$cat] = count($catposspast[$cat]) + count($catposspastec[$cat]);
		$catitemcntcur[$cat] = count($catposscur[$cat]) + count($catposscurec[$cat]);
		$catitemcntfuture[$cat] = count($catpossfuture[$cat]) + count($catpossfutureec[$cat]);
		if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($catposspast[$cat])) { //if drop is set and have enough items
			asort($catposspast,SORT_NUMERIC);
			$catposspast[$cat] = array_slice($catposspast[$cat],$cats[$cat][4]);
		}
		if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($catposscur[$cat])) { //same for past&current
			asort($catposscur,SORT_NUMERIC);
			$catposscur[$cat] = array_slice($catposscur[$cat],$cats[$cat][4]);
		}
		if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($catpossfuture[$cat])) { //same for all items
			asort($catpossfuture,SORT_NUMERIC);
			$catpossfuture[$cat] = array_slice($catpossfuture[$cat],$cats[$cat][4]);
		}
		$catposspast[$cat] = array_sum($catposspast[$cat]);
		$catposscur[$cat] = array_sum($catposscur[$cat]);
		$catpossfuture[$cat] = array_sum($catpossfuture[$cat]);
		
		
		$gb[0][2][$pos][0] = $cats[$cat][0];
		$gb[0][2][$pos][1] = $cats[$cat][0];
		if ($catposspast[$cat]>0) {
			$gb[0][2][$pos][2] = 0; //scores in past
		} else if ($catposscur[$cat]>0) {
			$gb[0][2][$pos][2] = 1; //scores past/cur
		} else if ($catpossfuture[$cat]>0) {
			$gb[0][2][$pos][2] = 2; //scores online in future
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
		} else {
			$gb[0][2][$pos][3] = $catposspast[$cat];
			$gb[0][2][$pos][4] = $catposscur[$cat];
			$gb[0][2][$pos][5] = $catpossfuture[$cat];
		}
			
		
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
	
	
	//Pull student data
	$ln = 1;
	$query = "SELECT imas_users.id,imas_users.SID,imas_users.FirstName,imas_users.LastName,imas_users.SID,imas_users.email,imas_students.section,imas_students.code ";
	$query .= "FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid AND imas_students.courseid='$cid' ";
	//$query .= "FROM imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND imas_teachers.courseid='$cid' ";
	//if (!$isteacher && !isset($tutorid)) {$query .= "AND imas_users.id='$userid' ";}
	if ($limuser>0) { $query .= "AND imas_users.id='$limuser' ";}
	if ($isdiag) {
		$query .= "ORDER BY imas_users.email,imas_users.LastName,imas_users.FirstName";
	} else if ($hassection && $usersort==0) {
		$query .= "ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
	} else {
		$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
	}
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$alt = 0;
	$sturow = array();
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) { //foreach student
		unset($asid); unset($pts); unset($IP); unset($timeused);
		//Student ID info
		$gb[$ln][0][0] = "{$line['LastName']},&nbsp;{$line['FirstName']}";
		$gb[$ln][4][0] = $line['id'];
		
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
		if ($hassection) {
			$gb[$ln][0][] = $line['section'];
		}
		if ($hascode) {
			$gb[$ln][0][] = $line['code'];
		}
		$sturow[$line['id']] = $ln;
		$ln++;
	}
	
	//Get assessment scores
	$assessidx = array_flip($assessments);
	$query = "SELECT ias.id,ias.assessmentid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback,ias.userid FROM imas_assessment_sessions AS ias,imas_assessments AS ia ";
	$query .= "WHERE ia.id=ias.assessmentid AND ia.courseid='$cid'";
	$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($l = mysql_fetch_array($result2, MYSQL_ASSOC)) {
		if (!isset($assessidx[$l['assessmentid']]) || !isset($sturow[$l['userid']]) || !isset($assesscol[$l['assessmentid']])) {
			continue;
		}
		$i = $assessidx[$l['assessmentid']];
		$row = $sturow[$l['userid']];
		$col = $assesscol[$l['assessmentid']];
		
		$gb[$row][1][$col][4] = $l['id'];; //assessment session id
		
		
		$scores = explode(",",$l['bestscores']);
		$pts = 0;
		for ($i=0;$i<count($scores);$i++) {
			$pts += getpts($scores[$i]);
			//if ($scores[$i]>0) {$total += $scores[$i];}
		}
		$timeused = $l['endtime']-$l['starttime'];
		if (in_array(-1,$scores)) { $IP=1;}
		
		$thised = $enddate[$i];
		//GET EXCEPTIONS
		
		if ($isteacher || $assessmenttype[$i]=="Practice" || $sa[$i]=="I" || ($sa[$i]!="N" && $now>$thised)) {
			$gb[$row][1][$col][2] = 1; //show link
		} else {
			$gb[$row][1][$col][2] = 0; //don't show link
		}
		if ($assessmenttype[$i]=="NoScores" && $sa[$i]!="I" && $now<$thised && !$isteacher) {
			$gb[$row][1][$col][0] = 'N/A'; //score is not available
			$gb[$row][1][$col][3] = 0;  //no other info
		} else if ($pts<$minscores[$i]) {
			if ($isteacher) {
				$gb[$row][1][$col][0] = $pts; //the score
				$gb[$row][1][$col][3] = 1;  //no credit
			} else {
				$gb[$row][1][$col][0] = 'NC'; //score is No credit
				$gb[$row][1][$col][3] = 1;  //no credit
			}
		} else 	if ($IP==1 && $thised>$now) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 2;  //in progress
		} else	if (($timelimits[$i]>0) &&($timeused > $timelimits[$i])) {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 3;  //over time
		} else if ($assessmenttype[$i]=="Practice") {
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 4;  //practice test
		} else { //regular score available to students
			$gb[$row][1][$col][0] = $pts; //the score
			$gb[$row][1][$col][3] = 0;  //no other info
			if ($cntingb[$i] == 1 || $cntingb[$i]==2) {
				if ($gb[0][1][$col][3]<1) { //past
					$cattotpast[$row][$category[$i]][] = $pts;
				} else if ($gb[0][1][$col][3]<2) { //past or cur
					$cattotcur[$row][$category[$i]][] = $pts;
				}
				$cattotfuture[$row][$category[$i]][] = $pts;
				
			}
		}
		if ($isteacher && $l['feedback']!='') {
			$gb[$row][1][$col][1] = 1; //has comment
		} else if ($limuser>0) {
			$gb[$row][1][$col][1] = $l['feedback']; //the feedback
		} else {
			$gb[$row][1][$col][1] = 0; //no comment
		}
	}
	
	//Get other grades
	$gradeidx = array_flip($grades);
	unset($gradeid); unset($opts);
	$query = "SELECT imas_grades.gbitemid,imas_grades.id,imas_grades.score,imas_grades.feedback,imas_grades.userid FROM imas_grades,imas_gbitems WHERE ";
	$query .= "imas_grades.gbitemid=imas_gbitems.id AND imas_gbitems.courseid='$cid'";
	//##ADD LIMUSER RESTRICTIONS TO QUERIES
	$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($l = mysql_fetch_array($result2, MYSQL_ASSOC)) {
		if (!isset($gradeidx[$l['gbitemid']]) || !isset($sturow[$l['userid']]) || !isset($gradecol[$l['gbitemid']])) {
			continue;
		}
		$i = $gradeidx[$l['gbitemid']];
		$row = $sturow[$l['userid']];
		$col = $gradecol[$l['gbitemid']];
		
		$gb[$row][1][$col][2] = $l['id'];
		
		$gb[$row][1][$col][0] = 1*$l['score'];
		if ($isteacher && $l['feedback']!='') { //feedback
			$gb[$row][1][$col][1] = 1; //yes it has it (for teachers)
		} else if ($limuser>0) {
			$gb[$row][1][$col][1] =  $l['feedback']; //the feedback (for students)
		} else {
			$gb[$row][1][$col][1] = 0; //no feedback
		}
		
		if ($cntingb[$i] == 1 || $cntingb[$i]==2) {
			if ($gb[0][1][$col][3]<1) { //past
				$cattotpast[$row][$category[$i]][] = 1*$l['score'];
			} else if ($gb[0][1][$col][3]<2) { //past or cur
				$cattotcur[$row][$category[$i]][] = 1*$l['score'];
			}
			$cattotfuture[$row][$category[$i]][] = 1*$l['score'];		
		}
	}
	
	//Get discussion grades
	unset($discusspts);
	$discussidx = array_flip($discuss);
	$query = "SELECT imas_forum_posts.userid,imas_forum_posts.forumid,SUM(imas_forum_posts.points) FROM imas_forum_posts,imas_forums WHERE imas_forum_posts.forumid=imas_forums.id AND imas_forums.courseid='$cid' ";
	$query .= "GROUP BY imas_forum_posts.forumid,imas_forum_posts.userid ";
	$result2 = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($r = mysql_fetch_row($result2)) {
		if (!isset($discussidx[$r[1]]) || !isset($sturow[$r[0]]) || !isset($discusscol[$r[1]])) {
			continue;
		}
		$i = $discussidx[$r[1]];
		$row = $sturow[$r[0]];
		$col = $discusscol[$r[1]];
		$gb[$row][1][$col][0] = $r[2];
	}
	
	//create category totals
	
	for ($ln = 1; $ln<count($sturow)+1;$ln++) { //foreach student calculate category totals and total totals
		$totpast = 0;
		$totcur = 0;
		$totfuture = 0;
		$pos = 0; //reset position for category totals
		foreach($catorder as $cat) {//foreach category
			if (isset($cattotpast[$ln][$cat])) {  //past items
				//cats: name,scale,scaletype,chop,drop,weight
				if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattotpast[$ln][$cat])) { //if drop is set and have enough items
					asort($cattotpast[$ln][$cat],SORT_NUMERIC);
					while (count($cattotpast[$ln][$cat])<$catitemcnt[$cat]) {
						array_unshift($cattotpast[$ln][$cat],0);
					}
					$cattotpast[$ln][$cat] = array_slice($cattotpast[$ln][$cat],$cats[$cat][4]);
				}
				$cattotpast[$ln][$cat] = array_sum($cattotpast[$ln][$cat]); 
				if ($cats[$cat][1]!=0) { //scale is set
					if ($cats[$cat][2]==0) { //pts scale
						$cattotpast[$ln][$cat] = round($catposspast[$cat]*($cattotpast[$ln][$cat]/$cats[$cat][1]),1);
					} else if ($cats[$cat][2]==1) { //percent scale
						$cattotpast[$ln][$cat] = round($cattotpast[$ln][$cat]*(100/($cats[$cat][1])),1);
					}
				}
				if ($useweights==0 && $cats[$cat][5]>-1) {//use fixed pt value for cat
					$cattotpast[$ln][$cat] = round($cats[$cat][5]*($cattotpast[$ln][$cat]/$catposspast[$cat]),1);
				}
				if ($cats[$cat][3]==1) {
					if ($useweights==0  && $cats[$cat][5]>-1) { //set cat pts
						$cattotpast[$ln][$cat] = min($cats[$cat][5],$cattotpast[$ln][$cat]);
					} else {
						$cattotpast[$ln][$cat] = min($catposspast[$cat],$cattotpast[$ln][$cat]);
					}
				}
				
				$gb[$ln][2][$pos][0] = $cattotpast[$ln][$cat];
				
				if ($useweights==1) {
					if ($cattotpast[$ln][$cat]>0) {
						$totpast += ($cattotpast[$ln][$cat]*$cats[$cat][5])/(100*$catposspast[$cat]); //weight total
					}
				}
			} else { //no items in category yet?
				$gb[$ln][2][$pos][0] = 0;
			}
			if (isset($cattotcur[$ln][$cat])) {  //cur items
				//cats: name,scale,scaletype,chop,drop,weight
				if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattotcur[$ln][$cat])) { //if drop is set and have enough items
					asort($cattotcur[$ln][$cat],SORT_NUMERIC);
					while (count($cattotcur[$ln][$cat])<$catitemcnt[$cat]) {
						array_unshift($cattotcur[$ln][$cat],0);
					}
					$cattotcur[$ln][$cat] = array_slice($cattotcur[$ln][$cat],$cats[$cat][4]);
				}
				$cattotcur[$ln][$cat] = array_sum($cattotcur[$ln][$cat]); 
				if ($cats[$cat][1]!=0) { //scale is set
					if ($cats[$cat][2]==0) { //pts scale
						$cattotcur[$ln][$cat] = round($catposscur[$cat]*($cattotcur[$ln][$cat]/$cats[$cat][1]),1);
					} else if ($cats[$cat][2]==1) { //percent scale
						$cattotcur[$ln][$cat] = round($cattotcur[$ln][$cat]*(100/($cats[$cat][1])),1);
					}
				}
				if ($useweights==0 && $cats[$cat][5]>-1) {//use fixed pt value for cat
					$cattotcur[$ln][$cat] = round($cats[$cat][5]*($cattotcur[$ln][$cat]/$catposscur[$cat]),1);
				}
				if ($cats[$cat][3]==1) {
					if ($useweights==0  && $cats[$cat][5]>-1) { //set cat pts
						$cattotcur[$ln][$cat] = min($cats[$cat][5],$cattotcur[$ln][$cat]);
					} else {
						$cattotcur[$ln][$cat] = min($catposscur[$cat],$cattotcur[$ln][$cat]);
					}
				}
				
				$gb[$ln][2][$pos][1] = $cattotcur[$ln][$cat];
				
				if ($useweights==1) {
					if ($cattotcur[$ln][$cat]>0) {
						$totcur += ($cattotcur[$ln][$cat]*$cats[$cat][5])/(100*$catposscur[$cat]); //weight total
					}
				}
			} else { //no items in category yet?
				$gb[$ln][2][$pos][1] = 0;
			}
			if (isset($cattotfuture[$ln][$cat])) {  //future items
				//cats: name,scale,scaletype,chop,drop,weight
				if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattotfuture[$ln][$cat])) { //if drop is set and have enough items
					asort($cattotfuture[$ln][$cat],SORT_NUMERIC);
					while (count($cattotfuture[$ln][$cat])<$catitemcnt[$cat]) {
						array_unshift($cattotfuture[$ln][$cat],0);
					}
					$cattotfuture[$ln][$cat] = array_slice($cattotfuture[$ln][$cat],$cats[$cat][4]);
				}
				$cattotfuture[$ln][$cat] = array_sum($cattotfuture[$ln][$cat]); 
				if ($cats[$cat][1]!=0) { //scale is set
					if ($cats[$cat][2]==0) { //pts scale
						$cattotfuture[$ln][$cat] = round($catpossfuture[$cat]*($cattotfuture[$ln][$cat]/$cats[$cat][1]),1);
					} else if ($cats[$cat][2]==1) { //percent scale
						$cattotfuture[$ln][$cat] = round($cattotfuture[$ln][$cat]*(100/($cats[$cat][1])),1);
					}
				}
				if ($useweights==0 && $cats[$cat][5]>-1) {//use fixed pt value for cat
					$cattotfuture[$ln][$cat] = round($cats[$cat][5]*($cattotfuture[$ln][$cat]/$catpossfuture[$cat]),1);
				}
				if ($cats[$cat][3]==1) {
					if ($useweights==0  && $cats[$cat][5]>-1) { //set cat pts
						$cattotfuture[$ln][$cat] = min($cats[$cat][5],$cattotfuture[$ln][$cat]);
					} else {
						$cattotfuture[$ln][$cat] = min($catpossfuture[$cat],$cattotfuture[$ln][$cat]);
					}
				}
				
				$gb[$ln][2][$pos][2] = $cattotfuture[$ln][$cat];
				
				if ($useweights==1) {
					if ($cattotfuture[$ln][$cat]>0) {
						$totfuture += ($cattotfuture[$ln][$cat]*$cats[$cat][5])/(100*$catpossfuture[$cat]); //weight total
					}
				}
			} else { //no items in category yet?
				$gb[$ln][2][$pos][2] = 0;
			}
			$pos++;
			
		}
		
		if ($useweights==0) { //use points grading method
			if (!isset($cattotpast)) {
				$totpast = 0;
			} else {
				$totpast = array_sum($cattotpast[$ln]);
			}
			if (!isset($cattotcur)) {
				$totcur = 0;
			} else {
				$totcur = array_sum($cattotcur[$ln]);
			}
			if (!isset($cattotfuture)) {
				$totfuture = 0;
			} else {
				$totfuture = array_sum($cattotfuture[$ln]);
			}
			$gb[$ln][3][0] = $totpast;
			$gb[$ln][3][1] = $totcur;
			$gb[$ln][3][2] = $totfuture;
			if ($overallptspast>0) {
				$gb[$ln][3][3] = round(100*$totpast/$overallptspast,1).'%';
			} else {
				$gb[$ln][3][3] = '0%';
			}
			if ($overallptscur>0) {
				$gb[$ln][3][4] = round(100*$totcur/$overallptscur,1).'%';
			} else {
				$gb[$ln][3][4] = '0%';
			}
			if ($overallptsfuture>0) {
				$gb[$ln][3][5] = round(100*$totfuture/$overallptsfuture,1).'%';
			} else {
				$gb[$ln][3][5] = '0%';
			}
		} else if ($useweights==1) { //use weights (%) grading method
			//already calculated $tot
			if ($overallptspast>0) {
				$totpast = 100*($totpast/$overallptspast);
			} else {
				$totpast = 0;
			}
			$gb[$ln][3][0] = round(100*$totpast,1);
			$gb[$ln][3][3] = null;
			
			if ($overallptscur>0) {
				$totcur = 100*($totcur/$overallptscur);
			} else {
				$totcur = 0;
			}
			$gb[$ln][3][1] = round(100*$totcur,1);
			$gb[$ln][3][4] = null;
			
			if ($overallptsfuture>0) {
				$totfuture = 100*($totfuture/$overallptsfuture);
			} else {
				$totfuture = 0;
			}
			$gb[$ln][3][2] = round(100*$totfuture,1);
			$gb[$ln][3][5] = null;
			
			
			
		}
	}
		
		
	
	return $gb;
}
?>
