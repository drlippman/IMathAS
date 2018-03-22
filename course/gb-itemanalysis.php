<?php
//IMathAS:  Item Analysis (averages)
//(c) 2007 David Lippman
	require("../init.php");

	$isteacher = isset($teacherid);
	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);
	if (!$isteacher) {
		echo "This page not available to students";
		exit;
	}
	if (isset($sessiondata[$cid.'gbmode'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		//DB $query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $gbmode = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbmode = $stm->fetchColumn(0);
	}
	if (isset($_GET['stu']) && $_GET['stu']!='') {
		$stu = Sanitize::onlyInt($_GET['stu']);
	} else {
		$stu = 0;
	}
	if (isset($_GET['from'])) {
		$from = $_GET['from'];
	} else {
		$from = 'gb';
	}

	$catfilter = -1;
	if (isset($tutorsection) && $tutorsection!='') {
		$secfilter = $tutorsection;
	} else {
		if (isset($_GET['secfilter'])) {
			$secfilter = $_GET['secfilter'];
			$sessiondata[$cid.'secfilter'] = $secfilter;
			writesessiondata();
		} else if (isset($sessiondata[$cid.'secfilter'])) {
			$secfilter = $sessiondata[$cid.'secfilter'];
		} else {
			$secfilter = -1;
		}
	}

	//Gbmode : Links NC Dates
	$totonleft = floor($gbmode/1000)%10 ; //0 right, 1 left
	$links = floor($gbmode/100)%10; //0: view/edit, 1 q breakdown
	$hidenc = (floor($gbmode/10)%10)%4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
	$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all

	$pagetitle = "Gradebook";
	$placeinhead = '<script type="text/javascript">';
	$placeinhead .= '$(function() {$("a[href*=\'gradeallq\']").attr("title","'._('Grade this question for all students').'");});';
	$placeinhead .= 'function previewq(qn) {';
	$placeinhead .= "var addr = '$imasroot/course/testquestion.php?cid=$cid&qsetid='+qn;";
	$placeinhead .= "window.open(addr,'Testing','width=400,height=300,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));";
	$placeinhead .= "}\n</script>";
	$placeinhead .= '<style type="text/css"> .manualgrade { background: #ff6;} td.pointer:hover {text-decoration: underline;}</style>';
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	if ($stu==-1) {
		echo "&gt; <a href=\"gradebook.php?stu=$stu&cid=$cid\">Averages</a> ";
	} else if ($from=='isolate') {
		echo "&gt; <a href=\"isolateassessgrade.php?cid=$cid&aid=$aid\">View Scores</a> ";
	} else if ($from=='gisolate') {
		echo "&gt; <a href=\"isolateassessbygroup.php?cid=$cid&aid=$aid\">View Group Scores</a> ";
	}
	echo "&gt; Item Analysis</div>";

	echo '<div class="cpmid"><a href="isolateassessgrade.php?cid='.$cid.'&amp;aid='.$aid.'">View Score List</a></div>';

	echo '<div id="headergb-itemanalysis" class="pagetitle"><h2>Item Analysis: ';

	$qtotal = array();
	$qcnt = array();
	$tcnt = array();
	$qincomplete = array();
	$timetaken = array();
	$timeontaskbystu = array();
	$timeontask = array();
	$attempts = array();
	$regens = array();

	//DB $query = "SELECT defpoints,name,itemorder,defoutcome,showhints FROM imas_assessments WHERE id='$aid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB list($defpoints, $aname, $itemorder, $defoutcome, $showhints) = mysql_fetch_row($result);
	$stm = $DBH->prepare("SELECT defpoints,name,itemorder,defoutcome,showhints FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($defpoints, $aname, $itemorder, $defoutcome, $showhints) = $stm->fetch(PDO::FETCH_NUM);
	echo Sanitize::encodeStringForDisplay($aname) . '</h2></div>';


	$itemarr = array();
	$itemnum = array();
	$curqnum = 1;
	foreach (explode(',',$itemorder) as $k=>$itel) {
		if (strpos($itel,'~')!==false) {
			$sub = explode('~',$itel);
			if (strpos($sub[0],'|')!==false) {
				$grppts = explode('|', array_shift($sub));
			} else {
				$grppts = array(1);
			}
			foreach ($sub as $j=>$itsub) {
				$itemarr[] = $itsub;
				$itemnum[$itsub] = $curqnum.'-'.($j+1);
			}
			$curqnum += $grppts[0];
		} else {
			$itemarr[] = $itel;
			$itemnum[$itel] = $curqnum;
			$curqnum++;
		}
	}

	//DB $query = "SELECT count(id) FROM imas_students WHERE courseid='$cid' AND locked=0 ";
	//DB if ($secfilter!=-1) {
		//DB $query .= " AND imas_students.section='$secfilter' ";
	//DB }
	//DB $result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
	//DB $totstucnt = mysql_result($result,0,0);
	$query = "SELECT count(id) FROM imas_students WHERE courseid=:courseid AND locked=0 ";
	if ($secfilter!=-1) {
		$query .= " AND imas_students.section=:section ";
	}
	$stm = $DBH->prepare($query);
	if ($secfilter!=-1) {
		$stm->execute(array(':courseid'=>$cid, ':section'=>$secfilter));
	} else {
		$stm->execute(array(':courseid'=>$cid));
	}
	$totstucnt = $stm->fetchColumn(0);

	//DB $query = "SELECT ias.questions,ias.bestscores,ias.bestattempts,ias.bestlastanswers,ias.starttime,ias.endtime,ias.timeontask FROM imas_assessment_sessions AS ias,imas_students ";
	//DB $query .= "WHERE ias.userid=imas_students.userid AND imas_students.courseid='$cid' AND ias.assessmentid='$aid' AND imas_students.locked=0 ";
	//DB if ($secfilter!=-1) {
		//DB $query .= " AND imas_students.section='$secfilter' ";
	//DB }
	//DB $result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
	//DB while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
	$query = "SELECT ias.questions,ias.bestscores,ias.bestattempts,ias.bestlastanswers,ias.starttime,ias.endtime,ias.timeontask FROM imas_assessment_sessions AS ias,imas_students ";
	$query .= "WHERE ias.userid=imas_students.userid AND imas_students.courseid=:courseid AND ias.assessmentid=:assessmentid AND imas_students.locked=0 ";
	if ($secfilter!=-1) {
		$query .= " AND imas_students.section=:section ";
	}
	$stm = $DBH->prepare($query);
	if ($secfilter!=-1) {
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid, ':section'=>$secfilter));
	} else {
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid));
	}
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		if (strpos($line['questions'],';')===false) {
			$questions = explode(",",$line['questions']);
		} else {
			list($questions,$bestquestions) = explode(";",$line['questions']);
			$questions = explode(",",$bestquestions);
		}
		$sp = explode(';', $line['bestscores']);
		$scores = explode(',', $sp[0]);
		$attp = explode(',',$line['bestattempts']);
		$bla = explode('~',$line['bestlastanswers']);
		$timeot = explode(',',$line['timeontask']);
		$timeotthisstu = 0;
		foreach ($questions as $k=>$ques) {
			if (trim($ques)=='') {continue;}

			if (!isset($qincomplete[$ques])) { $qincomplete[$ques]=0;}
			if (!isset($qtotal[$ques])) { $qtotal[$ques]=0;}
			if (!isset($qcnt[$ques])) { $qcnt[$ques]=0;}
			if (!isset($tcnt[$ques])) { $tcnt[$ques]=0;}
			if (!isset($attempts[$ques])) { $attempts[$ques]=0;}
			if (!isset($regens[$ques])) { $regens[$ques]=0;}
			if (!isset($timeontask[$ques])) { $timeontask[$ques]=0;}
			if (strpos($scores[$k],'-1')!==false) {
				$qincomplete[$ques] += 1;
			}
			$qtotal[$ques] += getpts($scores[$k]);
			$attempts[$ques] += $attp[$k];
			$regens[$ques] += substr_count($bla[$k],'ReGen');
			$qcnt[$ques] += 1;
			$timeot[$k] = explode('~',$timeot[$k]);
			$tcnt[$ques] += count($timeot[$k]);
			$totsum = array_sum($timeot[$k]);
			$timeontask[$ques] += $totsum;
			$timeotthisstu += $totsum;

		}
		if ($line['endtime'] >0 && $line['starttime'] > 0) {
			$timetaken[] = $line['endtime']-$line['starttime'];
		} else {
			$timetaken[] = 0;
		}
		$timeontaskbystu[] = $timeotthisstu;
	}

	$vidcnt = array();
	if (count($qcnt)>0) {
		$qlist = implode(',', array_map('intval', array_keys($qcnt)));
		//DB $query = "SELECT ict.typeid,COUNT(DISTINCT ict.userid) FROM imas_content_track AS ict JOIN imas_students AS ims ON ict.userid=ims.userid WHERE ims.courseid='$cid' AND ict.courseid='$cid' AND ict.type='extref' AND ict.typeid IN ($qlist)";
		//DB if ($secfilter!=-1) {
			//DB $query .= " AND ims.section='$secfilter' ";
		//DB }
		//DB $query .= " GROUP BY ict.typeid";
		//DB $result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$query = "SELECT ict.typeid,COUNT(DISTINCT ict.userid) FROM imas_content_track AS ict JOIN imas_students AS ims ON ict.userid=ims.userid WHERE ims.courseid=:courseid AND ict.courseid=:courseid2 AND ict.type='extref' AND ict.typeid IN ($qlist)";
		if ($secfilter!=-1) {
			$query .= " AND ims.section=:section ";
		}
		$query .= " GROUP BY ict.typeid";
		$stm = $DBH->prepare($query);
		if ($secfilter!=-1) {
			$stm->execute(array(':courseid'=>$cid, ':courseid2'=>$cid, ':section'=>$secfilter));
		} else {
			$stm->execute(array(':courseid'=>$cid, ':courseid2'=>$cid));
		}
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$vidcnt[$row[0]]=$row[1];
		}
	}

	$notstarted = ($totstucnt - count($timetaken));
	$nonstartedper = round(100*$notstarted/$totstucnt,1);
	if ($notstarted==0) {
		echo '<p>All students have started this assessment. ';
	} else {
		echo "<p><a href=\"#\" onclick=\"GB_show('Not Started','gb-itemanalysisdetail.php?cid=$cid&aid=$aid&qid=$qid&type=notstart',500,300);return false;\">$notstarted student".($notstarted>1?'s':'')."</a> ($nonstartedper%) ".($notstarted>1?'have':'has')." not started this assessment.  They are not included in the numbers below. ";
	}
	echo '</p>';
	//echo '<a href="isolateassessgrade.php?cid='.$cid.'&aid='.$aid.'">View Score List</a>.</p>';
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js?v=060417\"></script>\n";
	echo "<table class=gb id=myTable><thead>"; //<tr><td>Name</td>\n";
	echo "<tr><th>#</th><th scope=\"col\">Question</th><th>Grade</th>";
	//echo "<th scope=\"col\">Average Score<br/>All</th>";
	echo "<th scope=\"col\" title=\"Average score for all students who attempted this question\">Average Score<br/>Attempted</th><th title=\"Average number of attempts and regens (new versions)\" scope=\"col\">Average Attempts<br/>(Regens)</th><th scope=\"col\" title=\"Percentage of students who have not started this question yet\">% Incomplete</th>";
	echo "<th scope=\"col\" title=\"Average time a student worked on this question, and average time per attempt on this question\">Time per student<br/> (per attempt)</th>";
	if ($showhints==1) {
		echo '<th scope="col" title="Percentage of students who clicked on help resources in the question, if available">Clicked on Help</th>';
	}
	echo "<th scope=\"col\">Preview</th></tr></thead>\n";
	echo "<tbody>";
	if (count($qtotal)>0) {
		$i = 1;
		//$qs = array_keys($qtotal);
		$qslist = array_map('Sanitize::onlyInt',$itemarr);
		//DB $query = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questionset.id,imas_questions.withdrawn,imas_questionset.qtype,imas_questionset.control,imas_questions.showhints,imas_questionset.extref ";
		//DB $query .= "FROM imas_questionset,imas_questions WHERE imas_questionset.id=imas_questions.questionsetid";
		//DB $query .= " AND imas_questions.id IN ($qslist)";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$query_placeholders = Sanitize::generateQueryPlaceholders($qslist);
		$query = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questionset.id,imas_questions.withdrawn,imas_questionset.qtype,imas_questionset.control,imas_questions.showhints,imas_questionset.extref ";
		$query .= "FROM imas_questionset,imas_questions WHERE imas_questionset.id=imas_questions.questionsetid";
		$query .= " AND imas_questions.id IN ($query_placeholders)";
		$stm = $DBH->prepare($query);
		$stm->execute($qslist);
		$descrips = array();
		$points = array();
		$withdrawn = array();
		$qsetids = array();
		$needmanualgrade = array();
		$showextref = array();
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$descrips[$row[1]] = $row[0];
			$points[$row[1]] = $row[2];
			$qsetids[$row[1]] = $row[3];
			$withdrawn[$row[1]] = $row[4];
			if ($row[5]=='essay' || $row[5]=='file') {
				$needmanualgrade[$row[1]] = true;
			} else if ($row[5]=='multipart') {
				if (preg_match('/anstypes.*?(".*?"|array\(.*?\))/',$row[6],$matches)) {
					if (strpos($matches[1],'essay')!==false || strpos($matches[1],'file')!==false) {
						$needmanualgrade[$row[1]] = true;
					}
				}
			}
			if ($row[8]!='' && ($row[7]==2 || ($row[7]==0 && $showhints==1))) {
				$showextref[$row[1]] = true;
			} else {
				$showextref[$row[1]] = false;
			}
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
					$avgtot = round($timeontask[$qid]/($qcnt[$qid] - $qincomplete[$qid]),2);
					$avgtota = round($timeontask[$qid]/($tcnt[$qid]),2);
					if ($avgtot==0) {
						$avgtot = 'N/A';
					} else if ($avgtot<60) {
						$avgtot .= ' sec';
					} else {
						$avgtot = round($avgtot/60,2) . ' min';
					}
					if ($avgtota==0) {
						$avgtot = 'N/A';
					} else if ($avgtota<60) {
						$avgtota .= ' sec';
					} else {
						$avgtota = round($avgtota/60,2) . ' min';
					}
				} else {
					$avgatt = 0;
					$avgreg = 0;
					$avgtot = 0;
				}
			} else {
				$avg = "NA";
				$avg2 = "NA";
				$avgatt = "NA";
				$avgreg = "NA";
				$pc = 0; $pc2 = 0; $pi = "NA";
			}

			echo "<td>" . Sanitize::onlyInt($itemnum[$qid]) . "</td><td>";
			if ($withdrawn[$qid]==1) {
				echo '<span class="noticetext">Withdrawn</span> ';
			}
			echo Sanitize::encodeStringForDisplay($descrips[$qid]) . "</td>";
			echo "<td><a href=\"gradeallq.php?stu=" . Sanitize::encodeUrlParam($stu) . "&cid=$cid&asid=average&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "\" ";
			if (isset($needmanualgrade[$qid])) {
				echo 'class="manualgrade" ';
			}
			echo ">Grade</a></td>";
			//echo "<td>$avg/$pts ($pc%)</td>";
			echo sprintf("<td class=\"pointer c\" onclick=\"GB_show('Low Scores','gb-itemanalysisdetail.php?cid=%s&aid=%d&qid=%d&type=score',500,500);return false;\"><b>%.0f%%</b></td>",
                $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), $pc2);
			echo sprintf("<td class=\"pointer\" onclick=\"GB_show('Most Attempts and Regens','gb-itemanalysisdetail.php?cid=%s&aid=%d&qid=%d&type=att',500,500);return false;\">%s (%s)</td>",
                $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), Sanitize::encodeStringForDisplay($avgatt), Sanitize::encodeStringForDisplay($avgreg));
			echo sprintf("<td class=\"pointer c\" onclick=\"GB_show('Incomplete','gb-itemanalysisdetail.php?cid=%s&aid=%d&qid=%d&type=incomp',500,500);return false;\">%s%%</td>",
                $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), Sanitize::encodeStringForDisplay($pi));
			echo sprintf("<td class=\"pointer\" onclick=\"GB_show('Most Time','gb-itemanalysisdetail.php?cid=%s&aid=%d&qid=%d&type=time',500,500);return false;\">%s (%s)</td>",
                $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), Sanitize::encodeStringForDisplay($avgtot), Sanitize::encodeStringForDisplay($avgtota));
			if ($showhints==1) {
				if ($showextref[$qid] && $qcnt[$qid]!=$qincomplete[$qid]) {
					echo sprintf("<td class=\"pointer c\" onclick=\"GB_show('Got Help','gb-itemanalysisdetail.php?cid=%s&aid=%d&qid=%d&type=help',500,500);return false;\">%.0f%%</td>",
                        $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), round(100*$vidcnt[$qid]/($qcnt[$qid] - $qincomplete[$qid])));
				} else {
					echo '<td class="c">N/A</td>';
				}
			}
			echo sprintf("<td><input type=button value=\"Preview\" onClick=\"previewq(%d)\"/></td>\n", Sanitize::onlyInt($qsetids[$qid]));

			echo "</tr>\n";
			$i++;
		}

		echo "</tbody></table>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "initSortTable('myTable',Array('N','S',false,'N','N','N','N','N',false),true);\n";
		echo "</script>\n";
		echo "<p>Average time taken on this assessment: ";
		if (count($timetaken)>0) {
			echo round(array_sum($timetaken)/count($timetaken)/60,1);
		} else {
			echo 0;
		}
		echo " minutes<br/>\n";
		echo 'Average time in questions: ';
		if (count($timeontaskbystu)>0) {
			echo round(array_sum($timeontaskbystu)/count($timeontaskbystu)/60,1);
		} else {
			echo 0;
		}
		echo ' minutes</p>';

	} else {
		echo '</tbody></table>';
	}
	//echo "<p><a href=\"gradebook.php?stu=$stu&cid=$cid\">Return to GradeBook</a></p>\n";

	echo '<p>Items with grade link <span class="manualgrade">highlighted</span> require manual grading.<br/>';
	echo "Note: Average Attempts, Regens, and Time only counts those who attempted the problem<br/>";
	echo 'All averages only include those who have started the assessment</p>';

	//DB $query = "SELECT COUNT(id) from imas_questions WHERE assessmentid='$aid' AND category<>'0'";
	//DB $result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
	//DB if (mysql_result($result,0,0)>0) {
	$stm = $DBH->prepare("SELECT COUNT(id) from imas_questions WHERE assessmentid=:assessmentid AND category<>'0'");
	$stm->execute(array(':assessmentid'=>$aid));
	if ($stm->fetchColumn(0)>0) {
		include("../assessment/catscores.php");
		catscores($qs,$avgscore,$defpoints,$defoutcome,$cid);
	}
	echo '<div class="cpmid">Experimental:<br/>';
	echo "<a href=\"gb-itemresults.php?cid=$cid&amp;aid=$aid\">Summary of assessment results</a> (only meaningful for non-randomized questions)<br/>";

	echo "<a href=\"gb-aidexport.php?cid=$cid&amp;aid=$aid\">Export student answer details</a></div>";
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
