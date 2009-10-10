<?php
//IMathAS:  Item Analysis (averages)
//(c) 2007 David Lippman
	require("../validate.php");
	$isteacher = isset($teacherid);
	$cid = $_GET['cid'];
	if (!$isteacher) {
		echo "This page not available to students";
		exit;
	}
	if (isset($sessiondata[$cid.'gbmode'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		$query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$gbmode = mysql_result($result,0,0);
	}
	if (isset($_GET['stu']) && $_GET['stu']!='') {
		$stu = $_GET['stu'];
	} else {
		$stu = 0;
	}
	
	$catfilter = -1;
	$secfilter = -1;
	
	//Gbmode : Links NC Dates
	$totonleft = floor($gbmode/1000)%10 ; //0 right, 1 left
	$links = floor($gbmode/100)%10; //0: view/edit, 1 q breakdown
	$hidenc = floor($gbmode/10)%10; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
	$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all
	
	$pagetitle = "Gradebook";
	$placeinhead = '<script type="text/javascript">';
	$placeinhead .= 'function previewq(qn) {';
	$placeinhead .= "var addr = '$imasroot/course/testquestion.php?cid=$cid&qsetid='+qn;";
	$placeinhead .= "window.open(addr,'Testing','width=400,height=300,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));";
	$placeinhead .= "}\n</script>";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	if ($stu==-1) {
		echo "&gt; <a href=\"gradebook.php?stu=$stu&cid=$cid\">Averages</a> ";
	}
	echo "&gt; Item Analysis</div>";
	echo "<h2>Item Analysis: \n";
	$aid = $_GET['aid'];
	$qtotal = array();
	$qcnt = array();
	$qincomplete = array();
	$timetaken = array();
	$attempts = array();
	$regens = array();
	
	$query = "SELECT defpoints,name,itemorder FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$defpoints = mysql_result($result,0,0);
	echo mysql_result($result,0,1).'</h2>';
	$itemorder = mysql_result($result,0,2);
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
	
	$query = "SELECT ias.questions,ias.bestscores,ias.bestattempts,ias.bestlastanswers,ias.starttime,ias.endtime FROM imas_assessment_sessions AS ias,imas_students ";
	$query .= "WHERE ias.userid=imas_students.userid AND imas_students.courseid='$cid' AND ias.assessmentid='$aid'";
	$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		$questions = explode(',',$line['questions']);
		$scores = explode(',',$line['bestscores']);
		$attp = explode(',',$line['bestattempts']);
		$bla = explode('~',$line['bestlastanswers']);
		foreach ($questions as $k=>$ques) {

			if (!isset($qincomplete[$ques])) { $qincomplete[$ques]=0;}
			if (!isset($qtotal[$ques])) { $qtotal[$ques]=0;}
			if (!isset($qcnt[$ques])) { $qcnt[$ques]=0;}
			if (!isset($attempts[$ques])) { $attempts[$ques]=0;}
			if (!isset($regens[$ques])) { $regens[$ques]=0;}
			if (strpos($scores[$k],'-1')!==false) {
				$qincomplete[$ques] += 1;
			}
			$qtotal[$ques] += getpts($scores[$k]);
			$attempts[$ques] += $attp[$k];
			$regens[$ques] += substr_count($bla[$k],'ReGen');
			$qcnt[$ques] += 1;
		}
		if ($line['endtime'] >0 && $line['starttime'] > 0) {
			$timetaken[] = $line['endtime']-$line['starttime'];
		}
	}
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	echo "<table class=gb id=myTable><thead>"; //<tr><td>Name</td>\n";
	echo "<tr><th>#</th><th scope=\"col\">Question</th><th>Grade</th><th scope=\"col\">Average Score<br/>All</th>";
	echo "<th scope=\"col\">Average Score<br/>Attempted</th><th scope=\"col\">Average Attempts<br/>(Regens)</th><th scope=\"col\">% Incomplete</th><th scope=\"col\">Preview</th></tr></thead>\n";
	echo "<tbody>";
	if (count($qtotal)>0) {
		$i = 1;
		//$qs = array_keys($qtotal);
		$qslist = implode(',',$itemarr);
		$query = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questionset.id,imas_questions.withdrawn ";
		$query .= "FROM imas_questionset,imas_questions WHERE imas_questionset.id=imas_questions.questionsetid";
		$query .= " AND imas_questions.id IN ($qslist)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$descrips = array();
		$points = array();
		$withdrawn = array();
		$qsetids = array();
		while ($row = mysql_fetch_row($result)) {
			$descrips[$row[1]] = $row[0];
			$points[$row[1]] = $row[2];
			$qsetids[$row[1]] = $row[3];
			$withdrawn[$row[1]] = $row[4];
		}
		
		$avgscore = array();
		$qs = array();
		
		foreach ($itemarr as $qid) {
			if ($i%2!=0) {echo "<tr class=even>"; } else {echo "<tr class=odd>";}
			$pts = $points[$qid];
			if ($pts==9999) {
				$pts = $defpoints;
			}
			if ($qcnt[$qid]>0) {
				$avg = round($qtotal[$qid]/$qcnt[$qid],2);
				if ($qcnt[$qid] - $qincomplete[$qid]>0) {
					$avg2 = round($qtotal[$qid]/($qcnt[$qid] - $qincomplete[$qid]),2); //avg adjusted for not attempted
				} else {
					$avg2 = 0;
				}
				$avgscore[$i-1] = $avg;
				$qs[$i-1] = $qid;
				
				if ($pts>0) {
					$pc = round(100*$avg/$pts);
					$pc2 = round(100*$avg2/$pts);
				} else {
					$pc = 'N/A';
					$pc2 = 'N/A';
				}
				$pi = round(100*$qincomplete[$qid]/$qcnt[$qid],1);
				
				if ($qcnt[$qid] - $qincomplete[$qid]>0) {
					$avgatt = round($attempts[$qid]/($qcnt[$qid] - $qincomplete[$qid]),2);
					$avgreg = round($regens[$qid]/($qcnt[$qid] - $qincomplete[$qid]),2);
				} else {
					$avgatt = 0;
					$avgreg = 0;
				}
			} else {
				$avg = "NA";
				$avg2 = "NA";
				$avgatt = "NA";
				$avgreg = "NA";
				$pc = 0; $pc2 = 0; $pi = "NA";
			}
				
			echo "<td>{$itemnum[$qid]}</td><td>";
			if ($withdrawn[$qid]==1) {
				echo '<span class="red">Withdrawn</span> ';
			}
			echo "{$descrips[$qid]}</td>";
			echo "<td><a href=\"gradeallq.php?stu=$stu&cid=$cid&asid=average&aid=$aid&qid=$qid\">Grade</a></td>";
			echo "<td>$avg/$pts ($pc%)</td><td>$avg2/$pts ($pc2%)</td><td>$avgatt ($avgreg)</td><td>$pi</td>";
			echo "<td><input type=button value=\"Preview\" onClick=\"previewq({$qsetids[$qid]})\"/></td>\n";
			
			echo "</tr>\n";
			$i++;
		}
	
		echo "</tbody></table>\n";
		echo "<script type=\"text/javascript\">\n";		
		echo "initSortTable('myTable',Array('S','N','N'),true);\n";
		echo "</script>\n";
		echo "<p>Average time taken on this assessment: ";
		echo round(array_sum($timetaken)/count($timetaken)/60,1);
		echo " minutes</p>\n";
	} else {
		echo '</tbody></table>';
	}
	echo "<p><a href=\"gradebook.php?stu=$stu&cid=$cid\">Return to GradeBook</a></p>\n";
	
	echo "<p>Note: Average Attempts and Regens only counts those who attempted the problem</p>";
	
	$query = "SELECT COUNT(id) from imas_questions WHERE assessmentid='$aid' AND category<>'0'";
	$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
	if (mysql_result($result,0,0)>0) {
		include("../assessment/catscores.php");
		catscores($qs,$avgscore,$defpoints);
	}
	echo "<p><a href=\"gb-aidexport.php?cid=$cid&amp;aid=$aid\">Export assessment results</a></p>";
	require("../footer.php");
	
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
	
?>



