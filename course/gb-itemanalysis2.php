<?php
//IMathAS:  Item Analysis (averages)
//(c) 2007 David Lippman
	require("../init.php");

	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);
	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);
	if (!$isteacher && !$istutor) {
		echo "This page not available to students";
		exit;
	}
	if (isset($_SESSION[$cid.'gbmode'])) {
		$gbmode =  $_SESSION[$cid.'gbmode'];
	} else {
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
			$_SESSION[$cid.'secfilter'] = $secfilter;
		} else if (isset($_SESSION[$cid.'secfilter'])) {
			$secfilter = $_SESSION[$cid.'secfilter'];
		} else {
			$secfilter = -1;
		}
	}

	//Gbmode : Links NC Dates
	$totonleft = floor($gbmode/1000)%10 ; //0 right, 1 left
	$links = floor($gbmode/100)%10; //0: view/edit, 1 q breakdown
	$hidenc = (floor($gbmode/10)%10)%4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
	$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all

	$stm = $DBH->prepare("SELECT defpoints,name,itemorder,defoutcome,showhints,courseid,tutoredit,submitby,showwork FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
    list($defpoints, $aname, $itemorder, $defoutcome, $showhints, $assesscourseid, $tutoredit, $submitby, $showworkdef) = $stm->fetch(PDO::FETCH_NUM);
    $showworkdef = ($showworkdef & 3);
	$showhints = (($showhints&2)==2);
	if ($assesscourseid != $cid) {
		echo "Invalid assessment ID";
		exit;
	}
	if ($istutor && $tutoredit==2) {
		require("../header.php");
		echo "You not have access to view scores for this assessment";
		require("../footer.php");
		exit;
	}

	$pagetitle = "Gradebook";
	$placeinhead = '<script type="text/javascript">';
	$placeinhead .= '$(function() {$("a[href*=\'gradeallq\']").attr("title","'._('Grade this question for all students').'");});';
	$placeinhead .= 'function previewq(qn) {';
	$placeinhead .= "var addr = '$imasroot/course/testquestion2.php?cid=$cid&qsetid='+qn;";
	$placeinhead .= "window.open(addr,'Testing','width=400,height=300,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));";
	$placeinhead .= "}\n</script>";
	$placeinhead .= '<style type="text/css"> .manualgrade { background: #ff6;} td.pointer:hover {text-decoration: underline;}</style>';
	require("../header.php");
    echo "<div class=breadcrumb>$breadcrumbbase ";
    if (empty($_COOKIE['fromltimenu'])) {
        echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
        echo "<a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; ";
    }
    if ($stu==-1) {
		echo "<a href=\"gradebook.php?stu=$stu&cid=$cid\">Averages</a> &gt; ";
	} else if ($from=='isolate') {
		echo "<a href=\"isolateassessgrade.php?cid=$cid&aid=$aid\">View Scores</a> &gt; ";
	} else if ($from=='gisolate') {
		echo "<a href=\"isolateassessbygroup.php?cid=$cid&aid=$aid\">View Group Scores</a> &gt; ";
	}
	echo "Item Analysis</div>";

	echo '<div class="cpmid"><a href="isolateassessgrade.php?cid='.$cid.'&amp;aid='.$aid.'">View Score List</a></div>';

	echo '<div id="headergb-itemanalysis" class="pagetitle"><h1>Item Analysis: ';

	$qtotal = array();
	$qcnt = array();
	$tcnt = array();
	$qincomplete = array();
	$timetaken = array();
	$timeontaskbystu = array();
	$timeontask = array();
	$attempts = array();
	$regens = array();
    $timeontaskperversion = array();
    $studentsStartedAssessment = 0;
	echo Sanitize::encodeStringForDisplay($aname) . '</h1></div>';


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


    $query = "SELECT scoreddata FROM imas_assessment_records AS iar
                JOIN imas_students ON imas_students.userid = iar.userid
              WHERE iar.assessmentid = :assessmentid
                AND imas_students.courseid = :courseid
                AND imas_students.locked = 0";
	if ($secfilter!=-1) {
		$query .= " AND imas_students.section=:section ";
	}
	$stm = $DBH->prepare($query);
	if ($secfilter!=-1) {
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid, ':section'=>$secfilter));
	} else {
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid));
	}

	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	    $scoredData = json_decode(gzdecode($row['scoreddata']), true);

	    $scoredAssessmentIndex = $scoredData['scored_version'];
	    $scoredAssessment = $scoredData['assess_versions'][$scoredAssessmentIndex];

        $studentsStartedAssessment += 1;

	    foreach ($scoredAssessment['questions'] as $questionIndex => $questionData) {
	        // Get the scored question version.
	        $scoredQuestionIndex = $questionData['scored_version'];
            $scoredQuestion = $questionData['question_versions'][$scoredQuestionIndex];
            // The imas_questions.id for this question.
            $questionId = $scoredQuestion['qid'];

            if (!isset($qincomplete[$questionId])) { $qincomplete[$questionId] = 0; }
            if (!isset($qcnt[$questionId])) { $qcnt[$questionId] = 0; }
            if (!isset($regens[$questionId])) { $regens[$questionId] = 0; }
            if (!isset($qtotal[$questionId])) { $qtotal[$questionId] = 0; }
            if (!isset($attempts[$questionId])) { $attempts[$questionId] = 0; }
            if (!isset($timeontask[$questionId])) { $timeontask[$questionId] = 0; }
            if (!isset($timeontaskperversion[$questionId])) { $timeontaskperversion[$questionId] = 0; }

            // How many times this question was displayed to all students.
            $qcnt[$questionId] += 1;

            // The number of tries on this question. Use max tries on any part.
            if (!empty($scoredQuestion['scored_try'])) {
                $scoredTries = array_map(function($n) { return ++$n; }, $scoredQuestion['scored_try']);
                $attempts[$questionId] += max($scoredTries);
                // Figure out if any part of the question is incomplete.
                // Skip if a score override is set.  TODO: actually look per-part
                $untried = array_keys($scoredQuestion['scored_try'], -1);
								if (!empty($scoredQuestion['scoreoverride']) && is_array($scoredQuestion['scoreoverride'])) {
									$overridden = array_keys($scoredQuestion['scoreoverride']);
									if (count(array_diff($untried, $overridden)) > 0) {
										$qincomplete[$questionId] += 1;
										continue;
									}
								} else if (count($untried) > 0) {
									$qincomplete[$questionId] += 1;
									continue;
								}
            } else {
							// not even tried yet
							$qincomplete[$questionId] += 1;
							continue;
						}

						// Total number of times this question was RE-generated for all students.
            // Reduce by one to exclude the first generated question.
            $regens[$questionId] += count($questionData['question_versions']) - 1;

            // The rawscore for this question.
            $qtotal[$questionId] += $questionData['rawscore'];

            // Time spent on all versions of this question.
            $timeontask[$questionId] += $questionData['time'];

            // Time spent per version.
            $timeontaskperversion[$questionId] += $questionData['time'] / ($regens[$questionId] + 1);
        }
	}

// FIXME: Delete this entire commented block after refactoring.
//
//    while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
//		foreach ($questions as $k=>$ques) {
//			if (trim($ques)=='') {continue;}
//
//			if (!isset($qincomplete[$ques])) { $qincomplete[$ques]=0;}
//			if (!isset($qtotal[$ques])) { $qtotal[$ques]=0;}
//			if (!isset($qcnt[$ques])) { $qcnt[$ques]=0;}
//			if (!isset($tcnt[$ques])) { $tcnt[$ques]=0;}
//			if (!isset($attempts[$ques])) { $attempts[$ques]=0;}
//			if (!isset($regens[$ques])) { $regens[$ques]=0;}
//			if (!isset($timeontask[$ques])) { $timeontask[$ques]=0;}
//			if (strpos($scores[$k],'-1')!==false) {
//				$qincomplete[$ques] += 1;
//			}
//			$qtotal[$ques] += getpts($scores[$k]);
//			$attempts[$ques] += $attp[$k];
//			$regens[$ques] += substr_count($bla[$k],'ReGen');
//			$qcnt[$ques] += 1;
//			$timeot[$k] = explode('~',$timeot[$k]);
//			$tcnt[$ques] += count($timeot[$k]);
//			$totsum = array_sum($timeot[$k]);
//			$timeontask[$ques] += $totsum;
//			$timeotthisstu += $totsum;
//
//		}
//		if ($line['endtime'] >0 && $line['starttime'] > 0) {
//			$timetaken[] = $line['endtime']-$line['starttime'];
//		} else {
//			$timetaken[] = 0;
//		}
//		$timeontaskbystu[] = $timeotthisstu;
//	}

	$vidcnt = array();
	if (count($qcnt)>0) {
		$qlist = implode(',', array_map('intval', array_keys($qcnt)));
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

	$notstarted = $totstucnt - $studentsStartedAssessment;
	$nonstartedper = round(100*$notstarted/$totstucnt,1);
	if ($notstarted==0) {
		echo '<p>All students have started this assessment. ';
	} else {
		echo "<p><a href=\"#\" onclick=\"GB_show('Not Started','gb-itemanalysisdetail2.php?cid=$cid&aid=$aid&type=notstart',500,300);return false;\">$notstarted student".($notstarted>1?'s':'')."</a> ($nonstartedper%) ".($notstarted>1?'have':'has')." not started this assessment.  They are not included in the numbers below. ";
	}
	echo '</p>';
	//echo '<a href="isolateassessgrade.php?cid='.$cid.'&aid='.$aid.'">View Score List</a>.</p>';
	echo "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js?v=060417\"></script>\n";
	echo "<table class=gb id=myTable><thead>"; //<tr><td>Name</td>\n";
	echo "<tr><th>#</th><th scope=\"col\">Question</th><th>Grade</th>";
	//echo "<th scope=\"col\">Average Score<br/>All</th>";
	echo "<th scope=\"col\" title=\"Average score for all students who attempted this question\">Average Score</th>";
	if ($submitby == 'by_question') {
		echo "<th title=\"Average number of tries and regens (new versions)\" scope=\"col\">Average Tries<br/>(Regens)</th>";
	} else {
		echo "<th title=\"Average number of tries\" scope=\"col\">Average Tries</th>";
	}
	echo "<th scope=\"col\" title=\"Percentage of students who have not started this question yet\">% Incomplete</th>";
	if ($submitby == 'by_question') {
		echo "<th scope=\"col\" title=\"Average time a student worked on this question, and average time per version of this question\">Average time per student<br/> (per version)</th>";
	} else {
		echo "<th scope=\"col\" title=\"Average time a student worked on this question, and average time per attempt on this question\">Average time per student</th>";
	}
	if ($showhints) {
		echo '<th scope="col" title="Percentage of students who clicked on help resources in the question, if available">Clicked on Help</th>';
	}
	echo "<th scope=\"col\">Preview</th></tr></thead>\n";
	echo "<tbody>";
	if (count($qtotal)>0) {
		$i = 1;
		//$qs = array_keys($qtotal);
		$qslist = array_map('Sanitize::onlyInt',$itemarr);
		$query_placeholders = Sanitize::generateQueryPlaceholders($qslist);
		$query = "SELECT imas_questionset.description,imas_questions.id AS qid,imas_questions.points,imas_questionset.id AS qsid,imas_questions.withdrawn,imas_questionset.qtype,imas_questionset.control,imas_questions.showhints,imas_questionset.extref,imas_questions.showwork ";
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
        $showwork = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$descrips[$row['qid']] = $row['description'];
			$points[$row['qid']] = $row['points'];
			$qsetids[$row['qid']] = $row['qsid'];
			$withdrawn[$row['qid']] = $row['withdrawn'];
			if ($row['qtype']=='essay' || $row['qtype']=='file') {
				$needmanualgrade[$row['qid']] = true;
			} else if ($row['qtype']=='multipart') {
				if (preg_match('/anstypes.*?(essay|file)/', $row['control'])) {
					$needmanualgrade[$row['qid']] = true;
				}
			}
			if ($row['extref']!='' && (($row['showhints']&2)==2 || ($row['showhints']==-1 && $showhints))) {
				$showextref[$row['qid']] = true;
			} else {
				$showextref[$row['qid']] = false;
            }
            $showwork[$row['qid']] = (($row['showwork'] == -1 && $showworkdef > 0) || $row['showwork'] > 0);
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
				$avg = $qtotal[$qid]/$qcnt[$qid];
				if ($qcnt[$qid] - $qincomplete[$qid]>0) {
					$avg2 = $qtotal[$qid]/($qcnt[$qid] - $qincomplete[$qid]); //avg adjusted for not attempted
				} else {
					$avg2 = 0;
				}
				$avgscore[$i-1] = round($avg*$pts,2);
				$qs[$i-1] = $qid;

				if ($pts>0) {
					$pc = round(100*$avg);
					$pc2 = round(100*$avg2);
				} else {
					$pc = 'N/A';
					$pc2 = 'N/A';
				}
				$pi = round(100*$qincomplete[$qid]/$qcnt[$qid],1);
				if ($qcnt[$qid] - $qincomplete[$qid]>0) {
					$avgatt = round($attempts[$qid]/($qcnt[$qid] - $qincomplete[$qid]),2);
					$avgreg = round($regens[$qid]/($qcnt[$qid] - $qincomplete[$qid]),2);
					$avgtot = round($timeontask[$qid]/($qcnt[$qid] - $qincomplete[$qid]),2);
          $avgtota = round($timeontaskperversion[$qid]/($qcnt[$qid] - $qincomplete[$qid]),2);
					if ($avgtot==0) {
						$avgtot = 'N/A';
					} else {
						$avgtot = round($avgtot/60,2) . ' min';
					}
					if ($avgtota==0) {
						$avgtot = 'N/A';
					} else {
						$avgtota = round($avgtota/60,2) . ' min';
					}
				} else {
					$avgatt = 0;
					$avgreg = 0;
					$avgtot = 0;
					$avgtota = 0;
				}
			} else {
				$avg = "NA";
				$avg2 = "NA";
				$avgatt = "NA";
				$avgreg = "NA";
				$pc = 0; $pc2 = 0; $pi = "NA";
			}

			echo "<td>" . Sanitize::encodeStringForDisplay($itemnum[$qid]) . "</td><td>";
			if ($withdrawn[$qid]==1) {
				echo '<span class="noticetext">Withdrawn</span> ';
			}
			echo Sanitize::encodeStringForDisplay($descrips[$qid]) . "</td>";
			echo "<td><a href=\"gradeallq2.php?stu=" . Sanitize::encodeUrlParam($stu) . "&cid=$cid&asid=average&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "\" ";
			if (isset($needmanualgrade[$qid])) {
				echo 'class="manualgrade" ';
			}
            echo ">Grade</a>";
            if ($showwork[$qid]) {
                echo ' <span title="' . _('Has Show Work enabled') . '" aria-label="' . _('Has Show Work enabled') . '">' .
                  '<svg viewBox="0 0 24 24" width="14" height="14" stroke="black" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg></span>';
            }
            echo "</td>";
			//echo "<td>$avg/$pts ($pc%)</td>";
			echo sprintf("<td class=\"pointer c\" onclick=\"GB_show('Low Scores','gb-itemanalysisdetail2.php?cid=%s&aid=%d&qid=%d&type=score',500,500);return false;\"><b>%.0f%%</b></td>",
                $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), $pc2);
			if ($submitby == 'by_question') {
				echo sprintf("<td class=\"pointer\" onclick=\"GB_show('Most Attempts and Regens','gb-itemanalysisdetail2.php?cid=%s&aid=%d&qid=%d&type=attr',500,500);return false;\">%s (%s)</td>",
                $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), Sanitize::encodeStringForDisplay($avgatt), Sanitize::encodeStringForDisplay($avgreg));
			} else {
				echo sprintf("<td class=\"pointer\" onclick=\"GB_show('Most Attempts','gb-itemanalysisdetail2.php?cid=%s&aid=%d&qid=%d&type=att',500,500);return false;\">%s</td>",
                $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), Sanitize::encodeStringForDisplay($avgatt));
			}
			echo sprintf("<td class=\"pointer c\" onclick=\"GB_show('Incomplete','gb-itemanalysisdetail2.php?cid=%s&aid=%d&qid=%d&type=incomp',500,500);return false;\">%s%%</td>",
                $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), Sanitize::encodeStringForDisplay($pi));
			if ($submitby == 'by_question') {
				echo sprintf("<td class=\"pointer\" onclick=\"GB_show('Most Time','gb-itemanalysisdetail2.php?cid=%s&aid=%d&qid=%d&type=time',500,500);return false;\">%s (%s)</td>",
                $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), Sanitize::encodeStringForDisplay($avgtot), Sanitize::encodeStringForDisplay($avgtota));
			} else {
				echo sprintf("<td class=\"pointer\" onclick=\"GB_show('Most Time','gb-itemanalysisdetail2.php?cid=%s&aid=%d&qid=%d&type=time',500,500);return false;\">%s</td>",
                $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), Sanitize::encodeStringForDisplay($avgtot));
			}
			if ($showhints) {
				if ($showextref[$qid] && $qcnt[$qid]!=$qincomplete[$qid]) {
					echo sprintf("<td class=\"pointer c\" onclick=\"GB_show('Got Help','gb-itemanalysisdetail2.php?cid=%s&aid=%d&qid=%d&type=help',500,500);return false;\">%.0f%%</td>",
                        $cid, Sanitize::onlyInt($aid), Sanitize::onlyInt($qid), round(100*($vidcnt[$qid] ?? 0)/($qcnt[$qid] - $qincomplete[$qid])));
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
		/*echo "<p>Average time taken on this assessment: ";
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
        */
	} else {
		echo '</tbody></table>';
	}
	//echo "<p><a href=\"gradebook.php?stu=$stu&cid=$cid\">Return to GradeBook</a></p>\n";

    echo '<p>Items with grade link <span class="manualgrade">highlighted</span> require manual grading. ';
    echo 'Those marked with <span title="' . _('Show Work') . '" aria-label="' . _('Show Work') . '">' .
        '<svg viewBox="0 0 24 24" width="14" height="14" stroke="black" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg></span>' . 
        ' have Show Work enabled.<br>';
	echo "Note: Average Score, Tries, Regens, and Times only counts those who completed the problem.<br/>";
	echo 'Average Score is based on raw score, before any penalties are applied.<br/>';
	echo 'All averages only include those who have started the assessment.</p>';
	if ($submitby == 'by_assessment') {
		echo '<p>Note: Average time only includes time on the scored assessment attempt.</p>';
	}
	$stm = $DBH->prepare("SELECT COUNT(id) from imas_questions WHERE assessmentid=:assessmentid AND category<>'0'");
	$stm->execute(array(':assessmentid'=>$aid));
	if ($stm->fetchColumn(0)>0) {
		include("../assessment/catscores.php");
		catscores($qs,$avgscore,$defpoints,$defoutcome,$cid);
	}
	if ($isteacher) {
		echo '<div class="cpmid">Experimental:<br/>';
		echo "<a href=\"gb-itemresults2.php?cid=$cid&amp;aid=$aid\">Summary of assessment results</a> (only meaningful for non-randomized questions)<br/>";

		echo "<a href=\"gb-aidexport2.php?cid=$cid&amp;aid=$aid\">Export student answer details</a></div>";
	}
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
