<?php
//IMathAS: Pull Student responses on an assessment
//(c) 2009 David Lippman

require("../init.php");

$isteacher = isset($teacherid);
$cid = Sanitize::courseId($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if (!$isteacher) {
	echo "This page not available to students";
	exit;
}

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

function evalqsandbox($seed,$qqqcontrol,$qqqanswer) {
	$sa = '';
	global $RND;
	$RND->srand($seed);
	eval($qqqcontrol);
	$RND->srand($seed+1);
	eval($qqqanswer);

	if (isset($anstypes) && !is_array($anstypes)) {
		$anstypes = explode(",",$anstypes);
	}
	if (isset($anstypes)) { //is multipart
		if (isset($showanswer) && !is_array($showanswer)) {
			$sa = $showanswer;
		} else {
			$sapts =array();
			for ($i=0; $i<count($anstypes); $i++) {
				if (isset($showanswer[$i])) {
					$sapts[] = $showanswer[$i];
				} else if (isset($answer[$i])) {
					$sapts[] = $answer[$i];
				} else if (isset($answers[$i])) {
					$sapts[] = $answers[$i];
				}
			}
			$sa = implode('&',$sapts);
		}
	} else {
		if (isset($showanswer)) {
			$sa = $showanswer;
		} else if (isset($answer)) {
			$sa = $answer;
		} else if (isset($answers)) {
			$sa = $answers;
		}
	}
	return $sa;
}

if (isset($_POST['options'])) {
	//ready to output
	$outcol = 0;
	if (isset($_POST['pts'])) { $dopts = true; $outcol++;}
	if (isset($_POST['ptpts'])) { $doptpts = true; $outcol++;}
	if (isset($_POST['ba'])) { $doba = true; $outcol++;}
	if (isset($_POST['bca'])) { $dobca = true; $outcol++;}
	if (isset($_POST['la'])) { $dola = true; $outcol++;}

	//get assessment info
	//DB $query = "SELECT defpoints,name,itemorder FROM imas_assessments WHERE id='$aid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("SELECT defpoints,name,itemorder FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($defpoints, $assessname, $itemorder) = $stm->fetch(PDO::FETCH_NUM);
	//DB $defpoints = mysql_result($result,0,0);
	//DB $assessname = mysql_result($result,0,1);
	//DB $itemorder = mysql_result($result,0,2);
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
	//get question info
	$qpts = array();
	$qsetids = array();
	//DB $query = "SELECT id,points,questionsetid FROM imas_questions WHERE assessmentid='$aid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT id,points,questionsetid FROM imas_questions WHERE assessmentid=:assessmentid");
	$stm->execute(array(':assessmentid'=>$aid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[1]==9999) {
			$qpts[$row[0]] = $defpoints;
		} else {
			$qpts[$row[0]] = $row[1];
		}
		$qsetids[$row[0]] = $row[2];
	}
	if ($dobca) {
		$qcontrols = array();
		$qanswers = array();
		$mathfuncs = array("sin","cos","tan","sinh","cosh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
		$allowedmacros = $mathfuncs;
		require_once("../assessment/mathphp2.php");
		require("../assessment/interpret5.php");
		require("../assessment/macros.php");
		//DB $query = "SELECT id,qtype,control,answer FROM imas_questionset WHERE id IN ($qsetidlist)";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {

		$query_placeholders = Sanitize::generateQueryPlaceholders(array_values($qsetids));
		$stm = $DBH->prepare("SELECT id,qtype,control,answer FROM imas_questionset WHERE id IN ($query_placeholders)"); //INT vals from DB
    	$stm->execute(array_values($qsetids));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$qcontrols[$row[0]] = interpret('control',$row[1],$row[2]);
			$qanswers[$row[0]] = interpret('answer',$row[1],$row[3]);
		}
	}
	$query = "SELECT COUNT(imas_users.id) FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid ";
	$query .= "AND imas_students.courseid=:courseid AND imas_students.section IS NOT NULL";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	if ($stm->fetchColumn(0)>0) {
		$hassection = true;
	} else {
		$hassection = false;
	}

	$gb = array();
	//create headers
	$gb[0][0] = "Name";
	$gb[1][0] = "";
	if ($hassection) {
		$gb[0][1] = "Section";
		$gb[1][1] = "";
		$initoffset = 2;
	} else {
		$initoffset = 1;
	}
	$qcol = array();
	foreach ($itemarr as $k=>$q) {
		$qcol[$q] = $initoffset + $outcol*$k;
		$offset = 0;
		if ($dopts) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Points (".$qpts[$q]." possible)";
			$offset++;
		}
		if ($doptpts) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Part Points (".$qpts[$q]." possible)";
			$offset++;
		}
		if ($doba) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Scored Answer";
			$offset++;
		}
		if ($dobca) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Scored Correct Answer";
			$offset++;
		}
		if ($dola) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Last Answer";
			$offset++;
		}
	}

	//create row headers
	//DB $query = "SELECT iu.id,iu.FirstName,iu.LastName FROM imas_users AS iu JOIN ";
	//DB $query .= "imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid='$cid' ";
	//DB $query .= "ORDER BY iu.LastName, iu.FirstName";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$query = "SELECT iu.id,iu.FirstName,iu.LastName,imas_students.section FROM imas_users AS iu JOIN ";
	$query .= "imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid=:courseid ";
	if ($hassection) {
		$query .= "ORDER BY imas_students.section,iu.LastName, iu.FirstName";
	} else {
		$query .= "ORDER BY iu.LastName, iu.FirstName";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	$r = 2;
	$sturow = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$gb[$r] = array_fill(0,count($gb[0]),'');
		$gb[$r][0] = $row[2].', '.$row[1];
		if ($hassection) {
			$gb[$r][1] = $row[3];
		}
		$sturow[$row[0]] = $r;
		$r++;
	}

	//pull assessment data
	//DB $query = "SELECT ias.questions,ias.bestscores,ias.bestseeds,ias.bestattempts,ias.bestlastanswers,ias.lastanswers,ias.userid FROM imas_assessment_sessions AS ias,imas_students ";
	//DB $query .= "WHERE ias.userid=imas_students.userid AND imas_students.courseid='$cid' AND ias.assessmentid='$aid'";
	//DB $result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
	//DB while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
	$query = "SELECT ias.questions,ias.bestscores,ias.bestseeds,ias.bestattempts,ias.bestlastanswers,ias.lastanswers,ias.userid FROM imas_assessment_sessions AS ias,imas_students ";
	$query .= "WHERE ias.userid=imas_students.userid AND imas_students.courseid=:courseid AND ias.assessmentid=:assessmentid";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid));
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		if (strpos($line['questions'],';')===false) {
			$questions = explode(",",$line['questions']);
			$bestquestions = $questions;
		} else {
			list($questions,$bestquestions) = explode(";",$line['questions']);
			$questions = explode(",",$bestquestions);
		}
		$sp = explode(';', $line['bestscores']);
		$scores = explode(',',$sp[0]);
		$seeds = explode(',',$line['bestseeds']);
		$bla = explode('~',$line['bestlastanswers']);
		$la =  explode('~',$line['lastanswers']);
		if (!isset($sturow[$line['userid']])) {
			continue;
		}
		$r = $sturow[$line['userid']];
		foreach ($questions as $k=>$ques) {

			$c = $qcol[$ques];
			$offset = 0;
			if ($dopts) {
				$gb[$r][$c+$offset] = getpts($scores[$k]);
				$offset++;
			}
			if ($doptpts) {
				$gb[$r][$c+$offset] = $scores[$k];
				$offset++;
			}
			if ($doba) {
				$laarr = explode('##',$bla[$k]);
				$gb[$r][$c+$offset] = $laarr[count($laarr)-1];
				if (strpos($gb[$r][$c+$offset],'$f$')) {
					if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
						$laparr = explode('&',$gb[$r][$c+$offset]);
						foreach ($laparr as $lk=>$v) {
							if (strpos($v,'$f$')) {
								$tmp = explode('$f$',$v);
								$laparr[$lk] = $tmp[0];
							}
						}
						$gb[$r][$c+$offset] = implode('&',$laparr);
					} else {
						$tmp = explode('$f$',$gb[$r][$c+$offset]);
						$gb[$r][$c+$offset] = $tmp[0];
					}
				}
				if (strpos($gb[$r][$c+$offset],'$!$')) {
					if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
						$laparr = explode('&',$gb[$r][$c+$offset]);
						foreach ($laparr as $lk=>$v) {
							if (strpos($v,'$!$')) {
								$tmp = explode('$!$',$v);
								$laparr[$lk] = $tmp[1];
							}
						}
						$gb[$r][$c+$offset] = implode('&',$laparr);
					} else {
						$tmp = explode('$!$',$gb[$r][$c+$offset]);
						$gb[$r][$c+$offset] = $tmp[1];
					}
				}
				if (strpos($gb[$r][$c+$offset],'$#$')) {
					if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
						$laparr = explode('&',$gb[$r][$c+$offset]);
						foreach ($laparr as $lk=>$v) {
							if (strpos($v,'$#$')) {
								$tmp = explode('$#$',$v);
								$laparr[$lk] = $tmp[0];
							}
						}
						$gb[$r][$c+$offset] = implode('&',$laparr);
					} else {
						$tmp = explode('$#$',$gb[$r][$c+$offset]);
						$gb[$r][$c+$offset] = $tmp[0];
					}
				}
				$offset++;
			}
			if ($dobca) {
				$gb[$r][$c+$offset] = evalqsandbox($seeds[$k],$qcontrols[$qsetids[$ques]],$qanswers[$qsetids[$ques]]);
			}
			if ($dola) {
				$laarr = explode('##',$la[$k]);
				$gb[$r][$c+$offset] = $laarr[count($laarr)-1];
				if (strpos($gb[$r][$c+$offset],'$f$')) {
					if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
						$laparr = explode('&',$gb[$r][$c+$offset]);
						foreach ($laparr as $lk=>$v) {
							if (strpos($v,'$f$')) {
								$tmp = explode('$f$',$v);
								$laparr[$lk] = $tmp[0];
							}
						}
						$gb[$r][$c+$offset] = implode('&',$laparr);
					} else {
						$tmp = explode('$f$',$gb[$r][$c+$offset]);
						$gb[$r][$c+$offset] = $tmp[0];
					}
				}
				if (strpos($gb[$r][$c+$offset],'$!$')) {
					if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
						$laparr = explode('&',$gb[$r][$c+$offset]);
						foreach ($laparr as $lk=>$v) {
							if (strpos($v,'$!$')) {
								$tmp = explode('$!$',$v);
								$laparr[$lk] = $tmp[1];
							}
						}
						$gb[$r][$c+$offset] = implode('&',$laparr);
					} else {
						$tmp = explode('$!$',$gb[$r][$c+$offset]);
						$gb[$r][$c+$offset] = $tmp[1];
					}
				}
				if (strpos($gb[$r][$c+$offset],'$#$')) {
					if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
						$laparr = explode('&',$gb[$r][$c+$offset]);
						foreach ($laparr as $lk=>$v) {
							if (strpos($v,'$#$')) {
								$tmp = explode('$#$',$v);
								$laparr[$lk] = $tmp[0];
							}
						}
						$gb[$r][$c+$offset] = implode('&',$laparr);
					} else {
						$tmp = explode('$#$',$gb[$r][$c+$offset]);
						$gb[$r][$c+$offset] = $tmp[0];
					}
				}
				$offset++;
			}
		}
	}
	header('Content-type: text/csv');
	header("Content-Disposition: attachment; filename=\"aexport-$aid.csv\"");
	foreach ($gb as $gbline) {
		$line = '';
		foreach ($gbline as $val) {
			 # remove any windows new lines, as they interfere with the parsing at the other end
			  $val = str_replace("\r\n", "\n", $val);
			  $val = str_replace("\n", " ", $val);
			  $val = str_replace(array("<BR>",'<br>','<br/>'), ' ',$val);
			  $val = str_replace("&nbsp;"," ",$val);

			  # if a deliminator char, a double quote char or a newline are in the field, add quotes
			  if(preg_match("/[\,\"\n\r]/", $val)) {
				  $val = '"'.str_replace('"', '""', $val).'"';
			  }
			  $line .= Sanitize::outgoingHtml($val).',';
		}
		# strip the last deliminator
		$line = substr($line, 0, -1);
		$line .= "\n";
		echo Sanitize::outgoingHtml($line);
	}
	exit;
} else {
	//ask for options
	$pagetitle = "Assessment Export";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; <a href=\"gb-itemanalysis.php?aid=$aid&cid=$cid\">Item Analysis</a> ";
	echo '&gt; Assessment Export</div>';
	echo '<div id="headergb-aidexport" class="pagetitle"><h2>Assessment Results Export</h2></div>';

	echo "<form method=\"post\" action=\"gb-aidexport.php?aid=$aid&cid=$cid\">";
	echo 'What do you want to include in the export:<br/>';
	echo '<input type="checkbox" name="pts" value="1"/> Points earned<br/>';
	echo '<input type="checkbox" name="ptpts" value="1"/> Multipart broken-down Points earned<br/>';
	echo '<input type="checkbox" name="ba" value="1"/> Scored Attempt<br/>';
	echo '<input type="checkbox" name="bca" value="1"/> Correct Answers for Scored Attempt<br/>';
	echo '<input type="checkbox" name="la" value="1"/> Last Attempt<br/>';
	echo '<input type="submit" name="options" value="Export" />';
	echo '<p>Export will be a commas separated values (.CSV) file, which can be opened in Excel</p>';
	//echo '<p class="red"><b>Note</b>: Attempt information from shuffled multiple choice, multiple answer, and matching questions will NOT be correct</p>';
	echo '</form>';
	require("../footer.php");

}
?>
