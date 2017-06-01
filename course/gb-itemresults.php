<?php
//IMathAS: Display a summary of student results on a particular question
//(c) 2011 David Lippman

// Currently works for choices, multans, and non-randomized free response questions
// and multipart containing those.

// does NOT work for randomized questions or matching.

require("../validate.php");

if (!isset($teacherid) && !isset($tutorid)) {
	require("../header.php");
	echo "You need to log in as a teacher or tutor to access this page";
	require("../footer.php");
	exit;
}

$cid = intval($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']); //imas_assessments id
$att = $_GET['att'];



//pull questionset ids
$qsids = array();
//DB $query = "SELECT id,questionsetid FROM imas_questions WHERE assessmentid='$aid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT id,questionsetid FROM imas_questions WHERE assessmentid=:assessmentid");
$stm->execute(array(':assessmentid'=>$aid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$qsids[$row[0]] = $row[1];
}

//pull question data
$qsdata = array();
//DB $query = "SELECT id,qtype,control,description FROM imas_questionset WHERE id IN (".implode(',',$qsids).")";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$query_placeholders = Sanitize::generateQueryPlaceholders($qsids);
$stm = $DBH->prepare("SELECT id,qtype,control,description FROM imas_questionset WHERE id IN ($query_placeholders)");
$stm->execute(array_values($qsids)); //INT from DB
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$qsdata[$row[0]] = array($row[1],$row[2],$row[3]);
}

//pull assessment_sessions data
//look for this question in the itemorder (may be multiple times)
//get the answer they gave on the (first or last) attempt
//if multiple choice, multiple answer, or matching, use the question code and seed
//   to backtrack to original option
//tally results, grouping by result
//output results.  For numeric/function, sort by frequency

//DB $query = "SELECT questions,seeds,lastanswers,scores FROM imas_assessment_sessions ";
//DB $query .= "WHERE assessmentid='$aid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
$query = "SELECT questions,seeds,lastanswers,scores,ver FROM imas_assessment_sessions ";
$query .= "WHERE assessmentid=:assessmentid";
$stm = $DBH->prepare($query);
$stm->execute(array(':assessmentid'=>$aid));
$sessioncnt = 0;
$qdata = array();
//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$GLOBALS['assessver'] = $row[4];
	if (strpos($row[0],';')===false) {
		$questions = explode(",",$row[0]);
		//$bestquestions = $questions;
	} else {
		list($questions,$bestquestions) = explode(";",$row[0]);
		$questions = explode(",",$questions);
		//$bestquestions = explode(",",$bestquestions);
	}
	$scores = explode(',',$row[3]);
	$seeds = explode(',',$row[1]);
	$attempts = explode('~',$row[2]);
	$sessioncnt++;
	foreach($questions as $k=>$q) {
		if (!isset($qdata[$q])) {
			$qdata[$q] = array();
		}
		$qatt = explode('##',$attempts[$k]);
		if ($att=='first') { //doesn't work with scores yet
			$i=0;
			while($qatt[$i]=='ReGen') {
				$i++;
			}
			$qatt = $qatt[$i];
		} else {
			$qatt = $qatt[count($qatt)-1];
		}
		$qatt = explode('&',$qatt);
		$qscore = explode('~',$scores[$k]);
		foreach ($qatt as $kp=>$lav) {
			if (strpos($lav,'$f$')!==false) {
				$tmp = explode('$f$',$lav);
				$qatt[$kp] = $tmp[0];
				$lav = $tmp[0];
			}
			if (strpos($lav,'$!$')!==false) {
				$tmp = explode('$!$',$lav);
				$qatt[$kp] = $tmp[1];
			}
			if (strpos($lav,'$#$')!==false) {
				$tmp = explode('$#$',$lav);
				$qatt[$kp] = $tmp[0];
			}
		}
		if (count($qatt)==1) {
			$qatt = $qatt[0];
			$qscore = $qscore[0];
		}
		$qtype = $qsdata[$qsids[$q]][0];
		$qdata[$q][] = array($qatt,$qscore);
	}
}
$scorebarwidth = 60;
$placeinhead = ' <style type="text/css">

.scorebarinner {
	height:10px;
	font-size:1px;
	display:-moz-inline-box;
	display:inline-block;
	position:relative;
	left:0px;
	top:0px;

}
</style>';
require("../assessment/header.php");
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
echo "&gt; Item Results</div>";
echo '<div id="headergb-itemanalysis" class="pagetitle"><h2>Item Results: ';

//DB $query = "SELECT defpoints,name,itemorder FROM imas_assessments WHERE id='$aid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
$stm = $DBH->prepare("SELECT defpoints,name,itemorder FROM imas_assessments WHERE id=:id");
$stm->execute(array(':id'=>$aid));
//DB $defpoints = mysql_result($result,0,0);
//DB echo mysql_result($result,0,1).'</h2></div>';
//DB $itemorder = mysql_result($result,0,2);
list ($defpoints, $aname, $itemorder) = $stm->fetch(PDO::FETCH_NUM);
echo $aname.'</h2></div>';
$itemarr = array();
$itemnum = array();
foreach (explode(',',$itemorder) as $k=>$itel) {
	if (strpos($itel,'~')!==false) {
		$sub = explode('~',$itel);
		if (strpos($sub[0],'|')!==false) {
			array_shift($sub);
		}
		foreach ($sub as $j=>$itsub) {
			$itemarr[] = $itsub;
			$itemnum[$itsub] = ($k+1).'-'.($j+1);
		}
	} else {
		$itemarr[] = $itel;
		$itemnum[$itel] = ($k+1);
	}
}
echo '<p style="color:#f00;">Warning: Results are not accurate or meaningful for randomized questions</p>';

require("../assessment/displayq2.php");
$questions = array_keys($qdata);
foreach ($itemarr as $k=>$q) {
	echo '<div style="border:1px solid #000;padding:10px;margin-bottom:10px;clear:left;">';
	echo '<p><span style="float:right">(Question ID '.Sanitize::onlyInt($qsids[$q]).')</span><b>'.Sanitize::encodeStringForDisplay($qsdata[$qsids[$q]][2]).'</b></p>';
	echo '<br class="clear"/>';
	echo '<div style="float:left;width:35%;">';
	showresults($q,$qsdata[$qsids[$q]][0]);
	echo '</div>';
	echo '<div style="float:left;width:60%;margin-left:10px;">';
	displayq($k,$qsids[$q],0,0,0,0);
	echo '</div>';
	echo '<br class="clear"/>';
	echo '</div>';
}
require("../footer.php");

function showresults($q,$qtype) {
	global $qdata,$qsids,$qsdata;
	eval(interpret('control',$qtype,$qsdata[$qsids[$q]][1]));
	if ($qtype=='choices' || $qtype=='multans' || $qtype=='multipart') {
		if (isset($choices) && !isset($questions)) {
			$questions =& $choices;
		}
		if ($qtype=='multipart') {
			if (!is_array($anstypes)) {
				$anstypes = explode(',',$anstypes);
			}
			foreach ($anstypes as $i=>$type) {
				if ($type=='choices' || $type=='multans') {
					if (isset($questions[$i])) {
						$ql = $questions[$i];
					} else {
						$ql = $questions;
					}
					if ($type=='multans') {
						if (is_array($answers)) {
							$al = $answers[$i];
						} else {
							$al = $answers;
						}
					} else if ($type=='choices') {
						if (is_array($answer)) {
							$al = $answer[$i];
						} else {
							$al = $answer;
						}
					}
					disp($q,$type,$i,$al,$ql);
				} else {
					if (is_array($answer)) {
						$al = $answer[$i];
					} else {
						$al = $answer;
					}
					disp($q,$type,$i,$al);
				}

			}
		} else {
			if ($qtype=='multans') {
				$al = $answers;
			} else if ($qtype=='choices') {
				$al = $answer;
			}
			disp($q,$qtype,-1,$al,$questions);
		}
	} else {
		disp($q,$qtype,-1,$answer);
	}
}

function disp($q,$qtype,$part=-1,$answer,$questions=array()) {
	global $qdata,$qsdata,$qsids,$scorebarwidth;
	$res = array();
	$correct = array();
	$answer = explode(',',$answer);
	foreach ($qdata[$q] as $varr) {
		if ($part>-1) {
			$v = $varr[0][$part];
		} else {
			$v = $varr[0];
		}
		$v = explode('|',$v); //sufficient for choices and multans
		foreach ($v as $vp) {
			if ($part>-1) {
				if ($varr[1][$part]>0) {
					$correct[] = $vp;
				}
			} else {
				if ($varr[1]>0) {
					$correct[] = $vp;
				}
			}
			if ($vp!=='') {
				$res[] = $vp;
			}
		}
	}
	$res = array_count_values($res);
	$restot = max($res);
	if ($part>-1) {echo "Part ".($part+1);}
	echo '<table class="gridded">';
	echo '<thead>';
	echo '<tr><td>Answer</td><td>Count of students</td></tr>';
	echo '</thead><tbody>';
	if ($qtype=='choices' || $qtype=='multans') {
		for ($k=0;$k<count($questions);$k++) {
			if (!isset($res[$k])) {
				continue;
			}
			echo '<tr><td>'.$questions[$k].'</td><td>'.$res[$k];
			echo ' <span class="scorebarinner" style="';
			if (in_array($k,$answer)) {
				echo 'background:#9f9;';
			} else {
				echo 'background:#f99;';
			}
			echo 'width:'.round($scorebarwidth*$res[$k]/$restot).'px;"';
			echo '>&nbsp;</span>';
			echo '</td></tr>';
		}

	} else {
		arsort($res);
		foreach ($res as $ans=>$cnt) {
			echo '<tr><td>'.$ans.'</td><td>'.$cnt;
			echo ' <span class="scorebarinner" style="';

			if (in_array($ans,$correct)) {
				echo 'background:#9f9;';
			} else {
				echo 'background:#f99;';
			}
			echo 'width:'.round($scorebarwidth*$cnt/$restot).'px;"';
			echo '>&nbsp;</span>';
			echo '</td></tr>';
		}

	}
	echo '</tbody></table>';
}



?>
