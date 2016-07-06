<?php

require("../validate.php");
//$cid = 15;
//$isteacher = true;
//$gbt = gbtable();
//print_r($gbt);


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
//$row[0] header

//$row[0][0] biographical
$row[0][0][0] = "Name";
$row[0][0][1] = "ID";
$row[0][0][2] = "Term";
//etc.

//$row[0][1] scores 
//$row[0][1][0] first score
$row[0][1][0][0] = "Assessment name";
$row[0][1][0][1] = "Category";
$row[0][1][0][2] = 8;  //points possible
$row[0][1][0][3] = 0;  //0 past, 1 current, 2 future
$row[0][1][0][4] = 1; // 0 no count and hide, 1 count, 2 EC, 3 no count
$row[0][1][0][5] = 0;  //0 regular, 1 practice test
$row[0][1][0][6] = 0;  //type: 0 online, 1 offline, 2 discussion
$row[0][1][0][7] = 11; //course id
$row[0][1][0][8] = 234; //assessmentid or gbitem

//$row[0][2] category totals
$row[0][2][0][0] = "Category Name";
$row[0][2][0][1] = "Category"
$row[0][2][0][2] = 0;  //0 if any scores in past/current			***check
			 1 if all scores in future
			 2 no items at all
$row[0][2][0][3] = 200; //total possible for past&current
$row[0][2][0][4] = 250; //total possible for all items
			
			
//$row[0][3] total totals; may be blank if using weighted grading
$row[0][3][0] = 200; //total possible points past&current
$row[0][3][1] = 300;  //total possible points all


//$row[1] first data row
//$row[1][0] biographical
$row[1][0][0] = "David";
$row[1][0][1] = "dmoore";
$row[1][0][2] = "F07";
//etc.

//$row[1][1] scores
//$row[1][1][0] first score - assessment
$row[1][1][0][0] = 6; //the score
$row[1][1][0][1] = "Category";
$row[1][1][0][2] = 0; //0 assessment, 1 offline, 2 discussion
$row[1][1][0][3] = 0;  //0 past, 1 current, 2 future				***fix
$row[1][1][0][4] = 0;  //0 has instructor comment, 1 no comment - is comment if student view
$row[1][1][0][5] = 1;  // show link: 0 no, 1 yes
$row[1][1][0][6] = 0; //other info: 0 none, 1 no credit, 2 InProgress, 3 Overtime, 4 practice test
$row[1][1][0][7] = 1620; //aid
$row[1][1][0][8] = 17474; //asid, or 'new' or null
$row[1][1][0][9] = 312; //user id

//$row[1][1][1] score- offline
$row[1][1][1][0] = 6; //the score
$row[1][1][1][1] = "Category";
$row[1][1][1][2] = 1; //0 assessment, 1 offline, 2 discussion
$row[1][1][1][3] = 0;  //0 past, 1 current, 2 future
$row[1][1][1][4] = 0;  //0 has instructor comment, 1 no comment - is comment if student view
$row[1][1][1][5] = 23; //gradetypeid (gbitems.id)
$row[1][1][1][6] = 312; //userid

//$row[1][1][2] score-discussion
$row[1][1][2][0] = 5; //the score
$row[1][1][2][1] = "Category";
$row[1][1][1][2] = 1; //0 assessment, 1 offline, 2 discussion
$row[1][1][1][3] = 0;  //0 past, 1 current, 2 future
$row[1][1][1][4] = 23; //forumid
$row[1][1][1][5] = 312; //userid

//$row[1][2] category totals							***missed a cat?
$row[1][2][0][0] = 60; //category total past/current
$row[1][2][0][1] = "Category"
$row[1][2][0][2] = 80; //category total all items
$row[1][2][0][3] = 0;  //0 if any scores in past/current
			 1 if all scores in future
			 2 no items at all
			

//$row[1][3] total totals
$row[1][3][0] = 200; //total possible past/current
$row[1][3][1] = 75; //percent past/current.  Will be null if weighted grading
$row[1][3][2] = 300; //total possible all
$row[1][3][3] = 75; //percent all.  Will be null if weighted grading

//$row[1][4] id info
$row[1][4][0] = 1312; //userid
$row[1][4][1] = 142; //courseid


****/

function gbtable() {
	global $cid,$isteacher,$istutor,$tutorid,$userid;
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
	$catposs = array();
	$catpossec = array();
	$catpossfuture = array();
	$catpossfutureec = array();
	$itemorder = array();
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
			if ($avail[$k]<2) {
				if ($assessmenttype[$k]!="Practice" && $cntingb[$k]==1) {
					$catposs[$cat][] = $possible[$k]; //create category totals
				} else if ($cntingb[$k]==2) {
					$catpossec[$cat][] = 0;
				}
			} else {
				if ($assessmenttype[$k]!="Practice" && $cntingb[$k]==1) {
					$catpossfuture[$cat][] = $possible[$k]; //create category totals
				} else if ($cntingb[$k]==2) {
					$catpossfutureec[$cat][] = 0;
				}
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
					$gb[0][1][$pos][7] = $cid; //courseid
					$gb[0][1][$pos][8] = $assessments[$k];
				} else if (isset($grades[$k])) {
					$gb[0][1][$pos][6] = 1; //0 online, 1 offline
					$gb[0][1][$pos][7] = $cid; //courseid
					$gb[0][1][$pos][8] = $grades[$k];
				} else if (isset($discuss[$k])) {
					$gb[0][1][$pos][6] = 2; //0 online, 1 offline, 2 discuss
					$gb[0][1][$pos][7] = $cid; //courseid
					$gb[0][1][$pos][8] = $discuss[$k];
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
				$gb[0][1][$pos][7] = $cid; //courseid
				$gb[0][1][$pos][8] = $assessments[$k];
			} else if (isset($grades[$k])) {
				$gb[0][1][$pos][6] = 1; //0 online, 1 offline
				$gb[0][1][$pos][7] = $cid; //courseid
				$gb[0][1][$pos][8] = $grades[$k];
			} else if (isset($discuss[$k])) {
				$gb[0][1][$pos][6] = 2; //0 online, 1 offline, 2 discuss
				$gb[0][1][$pos][7] = $cid; //courseid
				$gb[0][1][$pos][8] = $discuss[$k];
			}
			
			$pos++;
		}
	} 
	$totalspos = $pos;
	//create category headers
	
	$catorder = array_keys($cats);
	$overallpts = 0;
	$overallptsfuture = 0;
	$pos = 0;
	foreach($catorder as $cat) {//foreach category
		
		//cats: name,scale,scaletype,chop,drop,weight
		$catitemcnt[$cat] = count($catposs[$cat]) + count($catpossec[$cat]);
		$catitemcntfuture[$cat] = count($catpossfuture[$cat]) + count($catpossfutureec[$cat]);
		if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($catposs[$cat])) { //if drop is set and have enough items
			asort($catposs,SORT_NUMERIC);
			$catposs[$cat] = array_slice($catposs[$cat],$cats[$cat][4]);
		}
		if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($catpossfuture[$cat])) { //same for all items
			asort($catpossfuture,SORT_NUMERIC);
			$catpossfuture[$cat] = array_slice($catpossfuture[$cat],$cats[$cat][4]);
		}
		$catposs[$cat] = array_sum($catposs[$cat]);
		$catpossfuture[$cat] = array_sum($catpossfuture[$cat]) + $catposs[$cat];
		
		$gb[0][2][$pos][0] = $cats[$cat][0];
		$gb[0][2][$pos][1] = $cats[$cat][0];
		if ($catposs[$cat]>0) {
			$gb[0][2][$pos][2] = 0; //scores in past
		} else if ($catposs[$cat]>0) {
			$gb[0][2][$pos][2] = 1; //scores only in future
		} else {
			$gb[0][2][$pos][2] = 2; //no items
		}
		if ($useweights==0 && $cats[$cat][5]>-1) { //if scaling cat total to point value
			if ($catposs[$cat]>0) {
				$gb[0][2][$pos][3] = $cats[$cat][5]; //score for past
			} else {
				$gb[0][2][$pos][3] = 0; //fix to 0 if no scores in past yet
			}
			$gb[0][2][$pos][4] = $cats[$cat][5]; //score for future
		} else {
			$gb[0][2][$pos][3] = $catposs[$cat];
			$gb[0][2][$pos][4] = $catpossfuture[$cat];
		}
			
		
		$overallpts += $gb[0][2][$pos][3];
		$overallptsfuture += $gb[0][2][$pos][4];
		$pos++;
	}
	
	
	//find total possible points
	if ($useweights==0) { //use points grading method
		$gb[0][3][0] = $overallpts;
		$gb[0][3][1] = $overallptsfuture;
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
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) { //foreach student
		unset($asid); unset($pts); unset($IP); unset($timeused);
		//Student ID info
		$gb[$ln][0][0] = "{$line['LastName']},&nbsp;{$line['FirstName']}";
		$gb[$ln][4][0] = $line['id'];
		$gb[$ln][4][1] = $cid;
		
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
		
		//Get assessment scores
		$query = "SELECT id,assessmentid,bestscores,starttime,endtime,feedback FROM imas_assessment_sessions WHERE userid='{$line['id']}'";
		$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($l = mysql_fetch_array($result2, MYSQL_ASSOC)) {
			$asid[$l['assessmentid']] = $l['id'];
			$sp = explode(';',$l['bestscores']);
			$scores = explode(',',$sp[0]);
			$total = 0;
			for ($i=0;$i<count($scores);$i++) {
				$total += getpts($scores[$i]);
				//if ($scores[$i]>0) {$total += $scores[$i];}
			}
			$timeused[$l['assessmentid']] = $l['endtime']-$l['starttime'];
			$afeedback[$l['assessmentid']] = $l['feedback'];
			if (in_array(-1,$scores)) { $IP[$l['assessmentid']]=1;}
			$pts[$l['assessmentid']] = $total;
		}
		//Get other grades
		unset($gradeid); unset($opts);
		$query = "SELECT imas_gbitems.id,imas_grades.id,imas_grades.score,imas_grades.feedback FROM imas_grades,imas_gbitems WHERE ";
		$query .= "imas_grades.gradetypeid=imas_gbitems.id AND imas_grades.gradetype='offline' AND imas_grades.userid='{$line['id']}'";
		$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($r = mysql_fetch_row($result2)) {
			$gradeid[$r[0]] = $r[1];
			$opts[$r[0]] = $r[2];
			$gfeedback[$r[0]] = $r[3];
		}
		
		//Get discussion grades
		unset($discusspts);
		$query = "SELECT forumid,SUM(points) FROM imas_forum_posts WHERE userid='{$line['id']}' GROUP BY forumid ";
		$result2 = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($r = mysql_fetch_row($result2)) {
			$discusspts[$r[0]] = $r[1];
		}
		
		//Create student GB row
		unset($cattot); unset($cattotfuture);
		$pos = 0;
		foreach ($itemorder as $i) { 
			if ($assessmenttype[$i]=='Offline') { //is other grade
				
				if (isset($gradeid[$grades[$i]])) {
					$gb[$ln][1][$pos][0] = 1*$opts[$grades[$i]]; //score
					if ($isteacher && $gfeedback[$grades[$i]]!='') { //feedback
						$gb[$ln][1][$pos][4] = 1; //yes it has it (for teachers)
					} else if ($limuser>0) {
						$gb[$ln][1][$pos][4] = $gfeedback[$grades[$i]]; //the feedback (for students)
					} else {
						$gb[$ln][1][$pos][4] = 0; //no feedback
					}
					
					if ($cntingb[$i] == 1 || $cntingb[$i]==2) {
						if ($gb[0][1][$pos][3]<2) {
							$cattot[$category[$i]][] = $opts[$grades[$i]];
						} 
						$cattotfuture[$category[$i]][] = $opts[$grades[$i]];
							
					}
				} else {
					$gb[$ln][1][$pos][0] = '-';
					$gb[$ln][1][$pos][4] = 0; //no feedback
				}
				$gb[$ln][1][$pos][1] = $gb[0][1][$pos][1]; //copy category
				$gb[$ln][1][$pos][2] = 1; //1 offline
				$gb[$ln][1][$pos][3] = $gb[0][1][$pos][3]; //copy past/future
				$gb[$ln][1][$pos][5] = $grades[$i]; //gbitems.id
				$gb[$ln][1][$pos][6] = $line['id']; //userid
			} else if ($assessmenttype[$i]=='Discussion') {
				if (isset($discusspts[$discuss[$i]])) {
					$gb[$ln][1][$pos][0] = $discusspts[$discuss[$i]];
					$atots[$pos][] = $discusspts[$discuss[$i]];
					$cattot[$category[$i]][] = $discusspts[$discuss[$i]];
				} else {
					$gb[$ln][1][$pos][0];
				}
				$gb[$ln][1][$pos][1] = $gb[0][1][$pos][1]; //copy category
				$gb[$ln][1][$pos][2] = 2; //2 discuss
				$gb[$ln][1][$pos][3] = $gb[0][1][$pos][3]; //copy past/future
				$gb[$ln][1][$pos][4] = $discuss[$i]; //forumid
				$gb[$ln][1][$pos][5] = $line['id']; //userid
			} else if (isset($asid[$assessments[$i]])) {
				$thised = $enddate[$i];
				if (!$isteacher) {
					$query = "SELECT enddate FROM imas_exceptions WHERE userid='{$line['id']}' AND assessmentid='{$assessments[$i]}' AND itemtype='A'";
					$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($r2)>0) {
						$exped = mysql_result($r2,0,0);
						if ($exped>$enddate[$i]) {
							$thised = $exped;
						}
					}
				}
				if ($isdisp && ($isteacher || $assessmenttype[$i]=="Practice" || $sa[$i]=="I" || ($sa[$i]!="N" && $now>$thised))) {
					$gb[$ln][1][$pos][5] = 1; //show link
				} else {
					$gb[$ln][1][$pos][5] = 0; //don't show link
				}
				if ($assessmenttype[$i]=="NoScores" && $sa[$i]!="I" && $now<$thised && !$isteacher) {
					$gb[$ln][1][$pos][0] = 'N/A'; //score is not available
					$gb[$ln][1][$pos][6] = 0;  //no other info
				} else if ($pts[$assessments[$i]]<$minscores[$i]) {
					if ($isteacher) {
						$gb[$ln][1][$pos][0] = $pts[$assessments[$i]]; //the score
						$gb[$ln][1][$pos][6] = 1;  //no credit
					} else {
						$gb[$ln][1][$pos][0] = 'NC'; //score is No credit
						$gb[$ln][1][$pos][6] = 1;  //no credit
					}
				} else 	if ($IP[$assessments[$i]]==1 && $thised>$now) {
					$gb[$ln][1][$pos][0] = $pts[$assessments[$i]]; //the score
					$gb[$ln][1][$pos][6] = 2;  //in progress
				} else	if (($timelimits[$i]>0) &&($timeused[$assessments[$i]] > $timelimits[$i])) {
					$gb[$ln][1][$pos][0] = $pts[$assessments[$i]]; //the score
					$gb[$ln][1][$pos][6] = 3;  //over time
				} else if ($assessmenttype[$i]=="Practice") {
					$gb[$ln][1][$pos][0] = $pts[$assessments[$i]]; //the score
					$gb[$ln][1][$pos][6] = 4;  //practice test
				} else { //regular score available to students
					$gb[$ln][1][$pos][0] = $pts[$assessments[$i]]; //the score
					$gb[$ln][1][$pos][6] = 0;  //no other info
					if ($cntingb[$i] == 1 || $cntingb[$i]==2) {
						if ($gb[0][1][$pos][3]<2) { //curent or past
							$cattot[$category[$i]][] = $pts[$assessments[$i]];
						} 
						$cattotfuture[$category[$i]][] = $pts[$assessments[$i]];
						
					}
				}
				if ($isteacher && $afeedback[$assessments[$i]]!='') {
					$gb[$ln][1][$pos][4] = 1; //has comment
				} else if ($limuser>0) {
					$gb[$ln][1][$pos][4] = $afeedback[$assessments[$i]];
				} else {
					$gb[$ln][1][$pos][4] = 0; //no comment
				}
				$gb[$ln][1][$pos][7] = $assessments[$i]; //assessment id
				$gb[$ln][1][$pos][8] = $asid[$assessments[$i]]; //assessment session id
			} else {
				$gb[$ln][1][$pos][0] = '-';
				$gb[$ln][1][$pos][7] = $assessments[$i]; //assessment id
				$gb[$ln][1][$pos][8] = 'new'; //assessment session id - don't have one
			}
			$gb[$ln][1][$pos][1] = $gb[0][1][$pos][1]; //copy category
			$gb[$ln][1][$pos][2] = 0; //0 online assessment
			$gb[$ln][1][$pos][3] = $gb[0][1][$pos][3]; //copy past/future
			$gb[$ln][1][$pos][9] = $line['id']; //userid
			$pos++;
		
		}
		$tot = 0;
		$totfuture = 0;
		//create category totals
		$pos = 0; //reset position for category totals
		
		foreach($catorder as $cat) {//foreach category
			$gb[$ln][2][$pos][1] = $gb[0][2][$pos][0]; //copy category name
			if (isset($cattot[$cat])) {
				$gb[$ln][2][$pos][3] = 0; //there are past/current items
			} if (isset($cattotfuture[$cat])) {
				$gb[$ln][2][$pos][3] = 1; //only future items
			} else {
				$gb[$ln][2][$pos][3] = 0; //no items at all
				$gb[$ln][2][$pos][0] = 0; 
				$gb[$ln][2][$pos][2] = 0;
				$pos++;
				continue;
			}
			if (isset($cattot[$cat])) {
				//cats: name,scale,scaletype,chop,drop,weight
				if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattot[$cat])) { //if drop is set and have enough items
					asort($cattot[$cat],SORT_NUMERIC);
					while (count($cattot[$cat])<$catitemcnt[$cat]) {
						array_unshift($cattot[$cat],0);
					}
					$cattot[$cat] = array_slice($cattot[$cat],$cats[$cat][4]);
				}
				$cattot[$cat] = array_sum($cattot[$cat]); //**adjust for drop, scale, etc
				if ($cats[$cat][1]!=0) { //scale is set
					if ($cats[$cat][2]==0) { //pts scale
						$cattot[$cat] = round($catposs[$cat]*($cattot[$cat]/$cats[$cat][1]),1);
					} else if ($cats[$cat][2]==1) { //percent scale
						$cattot[$cat] = round($cattot[$cat]*(100/($cats[$cat][1])),1);
					}
				}
				if ($useweights==0 && $cats[$cat][5]>-1) {//use fixed pt value for cat
					$cattot[$cat] = round($cats[$cat][5]*($cattot[$cat]/$catposs[$cat]),1);
				}
				if ($cats[$cat][3]==1) {
					if ($useweights==0  && $cats[$cat][5]>-1) { //set cat pts
						$cattot[$cat] = min($cats[$cat][5],$cattot[$cat]);
					} else {
						$cattot[$cat] = min($catposs[$cat],$cattot[$cat]);
					}
				}
				
				$gb[$ln][2][$pos][0] = $cattot[$cat];
				
				if ($useweights==1) {
					if ($catposs[$cat]>0) {
						$tot += ($cattot[$cat]*$cats[$cat][5])/(100*$catposs[$cat]); //weight total
					}
				}
			} else { //no items in category yet?
				$gb[$ln][2][$pos][0] = 0;
			}
			if (isset($cattotfuture[$cat])) {
				//cats: name,scale,scaletype,chop,drop,weight
				if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattotfuture[$cat])) { //if drop is set and have enough items
					asort($cattotfuture[$cat],SORT_NUMERIC);
					while (count($cattotfuture[$cat])<$catitemcntfuture[$cat]) {
						array_unshift($cattotfuture[$cat],0);
					}
					$cattotfuture[$cat] = array_slice($cattotfuture[$cat],$cats[$cat][4]);
				}
				$cattotfuture[$cat] = array_sum($cattotfuture[$cat]); //**adjust for drop, scale, etc
				if ($cats[$cat][1]!=0) { //scale is set
					if ($cats[$cat][2]==0) { //pts scale
						$cattotfuture[$cat] = round($catpossfuture[$cat]*($cattotfuture[$cat]/$cats[$cat][1]),1);
					} else if ($cats[$cat][2]==1) { //percent scale
						$cattotfuture[$cat] = round($cattotfuture[$cat]*(100/($cats[$cat][1])),1);
					}
				}
				if ($useweights==0 && $cats[$cat][5]>-1) {//use fixed pt value for cat
					$cattotfuture[$cat] = round($cats[$cat][5]*($cattotfuture[$cat]/$catpossfuture[$cat]),1);
				}
				if ($cats[$cat][3]==1) {
					if ($useweights==0  && $cats[$cat][5]>-1) { //set cat pts
						$cattotfuture[$cat] = min($cats[$cat][5],$cattotfuture[$cat]);
					} else {
						$cattotfuture[$cat] = min($catpossfuture[$cat],$cattotfuture[$cat]);
					}
				}
				
				$gb[$ln][2][$pos][2] = $cattotfuture[$cat];
				
				if ($useweights==1) {
					if ($catpossfuture[$cat]>0) {
						$totfuture += ($cattotfuture[$cat]*$cats[$cat][5])/(100*$catpossfuture[$cat]); //weight total
					}
				}
			} else { //no items in category yet?
				$gb[$ln][2][$pos][2] = 0;
			}
			$pos++;
			
		}
		
		if ($useweights==0) { //use points grading method
			if (!isset($cattot)) {
				$tot = 0;
			} else {
				$tot = array_sum($cattot);
			}
			$gb[$ln][3][0] = $tot;
			if ($overallpts>0) {
				$gb[$ln][3][1] = round(100*$tot/$overallpts,1).'%';
			} else {
				$gb[$ln][3][1] = '0%';
			}
			if (!isset($cattotfuture)) {
				$totfuture = 0;
			} else {
				$totfuture = array_sum($cattotfuture);
			}
			$gb[$ln][3][2] = $totfuture;
			if ($overallptsfuture>0) {
				$gb[$ln][3][3] = round(100*$totfuture/$overallptsfuture,1).'%';
			} else {
				$gb[$ln][3][3] = '0%';
			}
		} else if ($useweights==1) { //use weights (%) grading method
			//already calculated $tot
			if ($overallpts>0) {
				$tot = 100*($tot/$overallpts);
			} else {
				$tot = 0;
			}
			$gb[$ln][3][0] = round(100*$tot,1);
			$gb[$ln][3][1] = null;
			
			if ($overallptsfuture>0) {
				$totfuture = 100*($totfuture/$overallptsfuture);
			} else {
				$totfuture = 0;
			}
			$gb[$ln][3][2] = round(100*$totfuture,1);
			$gb[$ln][3][3] = null;
			
		}
		
		$ln++;
	}	
	
	return $gb;
}
?>
