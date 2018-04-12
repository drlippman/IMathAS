<?php
//IMathAS:  Grade all of one question for an assessment
//(c) 2007 David Lippman
	require("../init.php");


	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}

	$cid = Sanitize::courseId($_GET['cid']);
	$stu = $_GET['stu'];
	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
	$gbmode = $_GET['gbmode'];
	} else if (isset($sessiondata[$cid.'gbmode'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		//DB $query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $gbmode = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbmode = $stm->fetchColumn(0);
	}
	$hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked
	$aid = Sanitize::onlyInt($_GET['aid']);
	$qid = Sanitize::onlyInt($_GET['qid']);
	if (isset($_GET['ver'])) {
		$ver = $_GET['ver'];
	} else {
		$ver = 'graded';
	}
	if (isset($_GET['page'])) {
		$page = intval($_GET['page']);
	} else {
		$page = -1;
	}

	if (isset($_GET['update'])) {
		$allscores = array();
		$allfeedbacks = array();
		$grpscores = array();
		$grpfeedbacks = array();
		$locs = array();
		foreach ($_POST as $k=>$v) {
			if (strpos($k,'-')!==false) {
				$kp = explode('-',$k);
				if ($kp[0]=='ud') {
					//$locs[$kp[1]] = $kp[2];
					if (count($kp)==3) {
						if ($v=='N/A') {
							$allscores[$kp[1]][$kp[2]] = -1;
						} else {
							$allscores[$kp[1]][$kp[2]] = $v;
						}
					} else {
						if ($v=='N/A') {
							$allscores[$kp[1]][$kp[2]][$kp[3]] = -1;
						} else {
							$allscores[$kp[1]][$kp[2]][$kp[3]] = $v;
						}
					}
				} else if ($kp[0]=='fb') {
					if ($v=='' || $v=='<p></p>') {
						$v = '';
					} else {
						$v = Sanitize::incomingHtml($v);
					}
					$allfeedbacks[$kp[2]][$kp[1]] = $v;
				}
			}
		}
		if (isset($_POST['onepergroup']) && $_POST['onepergroup']==1) {
			foreach ($_POST['groupasid'] as $grp=>$asid) {
				$grpscores[$grp] = $allscores[$asid];
				$grpfeedbacks[$grp] = $allfeedbacks[$asid];
			}
			$onepergroup = true;
		} else {
			$onepergroup = false;
		}

		//DB $query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions ";
		//DB $query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_assessment_sessions.assessmentid='$aid' ";
		//DB $query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		//DB if ($page != -1 && isset($_GET['userid'])) {
			//DB $query .= " AND userid='{$_POST['userid']}'";
		//DB }
		//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions ";
		$query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_assessment_sessions.assessmentid=:assessmentid ";
		$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		if ($page != -1 && isset($_GET['userid'])) {
			$query .= " AND userid=:userid";
		}
		$stm = $DBH->prepare($query);
		if ($page != -1 && isset($_GET['userid'])) {
			$stm->execute(array(':assessmentid'=>$aid, ':userid'=>$_POST['userid']));
		} else {
			$stm->execute(array(':assessmentid'=>$aid));
		}
		$cnt = 0;

		//DB while($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		$updatedata = array();
		while($line=$stm->fetch(PDO::FETCH_ASSOC)) {
			$GLOBALS['assessver'] = $line['ver'];
			$feedback = json_decode($line['feedback'], true);
			if ($feedback === null) {
				if ($line['feedback']=='') {
					$feedback = array();
				} else {
					$feedback = array('Z'=>$line['feedback']);
				}
			}
			if ((!$onepergroup && isset($allscores[$line['id']])) || ($onepergroup && isset($grpscores[$line['agroupid']]))) {//if (isset($locs[$line['id']])) {
				$sp = explode(';',$line['bestscores']);
				$scores = explode(",",$sp[0]);
				if ($onepergroup) {
					if ($line['agroupid']==0) { continue;}
					foreach ($grpscores[$line['agroupid']] as $loc=>$sv) {
						if (is_array($sv)) {
							$scores[$loc] = implode('~',$sv);
						} else {
							$scores[$loc] = $sv;
						}
					}
					foreach ($grpfeedback[$line['agroupid']] as $loc=>$sv) {
						if (trim(strip_tags($sv))=='') {
							unset($feedback["Q".$loc]);
						} else {
							$feedback["Q".$loc] = $sv;
						}
					}
				} else {
					foreach ($allscores[$line['id']] as $loc=>$sv) {
						if (is_array($sv)) {
							$scores[$loc] = implode('~',$sv);
						} else {
							$scores[$loc] = $sv;
						}
					}
					foreach ($allfeedbacks[$line['id']] as $loc=>$sv) {
						if (trim(strip_tags($sv))=='') {
							unset($feedback["Q".$loc]);
						} else {
							$feedback["Q".$loc] = $sv;
						}
					}
					//$feedback = $_POST['feedback-'.$line['id']];
				}
				$scorelist = implode(",",$scores);
				if (count($sp)>1) {
					$scorelist .= ';'.$sp[1].';'.$sp[2];
				}
				if (count($feedback)>0) {
					$feedbackout = json_encode($feedback);
				} else {
					$feedbackout = '';
				}
				//$stm2 = $DBH->prepare("UPDATE imas_assessment_sessions SET bestscores=:bestscores,feedback=:feedback WHERE id=:id");
				//$stm2->execute(array(':bestscores'=>$scorelist, ':feedback'=>$feedback, ':id'=>$line['id']));
				array_push($updatedata, $line['id'], $scorelist, $feedbackout);

				if (strlen($line['lti_sourcedid'])>1) {
					//update LTI score
					require_once("../includes/ltioutcomes.php");
					calcandupdateLTIgrade($line['lti_sourcedid'],$aid,$scores);
				}
			}
		}
		if (count($updatedata)>0) {
			$placeholders = Sanitize::generateQueryPlaceholdersGrouped($updatedata,3);
			$query = "INSERT INTO imas_assessment_sessions (id,bestscores,feedback) VALUES $placeholders ";
			$query .= "ON DUPLICATE KEY UPDATE bestscores=VALUES(bestscores),feedback=VALUES(feedback)";
			$stm = $DBH->prepare($query);
			$stm->execute($updatedata);
		}

		if (isset($_GET['quick'])) {
			echo "saved";
		} else if ($page == -1) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gb-itemanalysis.php?"
				. Sanitize::generateQueryStringFromMap(array('stu' => $stu, 'cid' => $cid, 'aid' => $aid,
                    'asid' => 'average', 'r' => Sanitize::randomQueryStringParam(),)));
		} else {
			$page++;
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradeallq.php?"
				. Sanitize::generateQueryStringFromMap(array('stu' => $stu, 'cid' => $cid, 'aid' => $aid,
					'qid' => $qid, 'page' => $page, 'r' => Sanitize::randomQueryStringParam(),)));
		}
		exit;
	}


	require("../assessment/displayq2.php");


	$stm = $DBH->prepare("SELECT name,defpoints,isgroup,groupsetid,deffeedbacktext FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($aname,$defpoints,$isgroup,$groupsetid,$deffbtext) = $stm->fetch(PDO::FETCH_NUM);

	if ($isgroup>0) {
		$groupnames = array();
		$groupmembers = array();
		$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid");
		$stm->execute(array(':groupsetid'=>$groupsetid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$groupnames[$row[0]] = $row[1];
		}
		if (count($groupnames)>0) {
			$grplist = array_keys($groupnames);
			$query_placeholders = Sanitize::generateQueryPlaceholders($grplist);
			$stm = $DBH->prepare("SELECT isg.stugroupid,iu.LastName,iu.FirstName FROM imas_stugroupmembers AS isg JOIN imas_users as iu ON isg.userid=iu.id WHERE isg.stugroupid IN ($query_placeholders) ORDER BY iu.LastName,iu.FirstName");
			$stm->execute($grplist);
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if (!isset($groupmembers[$row[0]])) {  $groupmembers[$row[0]] = array();}
				$groupmembers[$row[0]][] = $row[2].' '.$row[1];
			}
		} else {
			$isgroup = 0;  //disregard isgroup if no groups exist
		}

	}

	$query = "SELECT imas_questions.points,imas_questions.rubric,imas_questionset.* FROM imas_questions,imas_questionset ";
	$query .= "WHERE imas_questions.questionsetid=imas_questionset.id AND imas_questions.id=:id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':id'=>$qid));
	$qdatafordisplayq = $stm->fetch(PDO::FETCH_ASSOC);
	$points = Sanitize::onlyFloat($qdatafordisplayq['points']);
	$rubric = $qdatafordisplayq['rubric'];
	$qsetid = $qdatafordisplayq['id'];
	$qtype = $qdatafordisplayq['qtype'];
	$qcontrol = $qdatafordisplayq['control'];
	//list ($points, $qcontrol, $rubric, $qtype) = $stm->fetch(PDO::FETCH_NUM);
	if ($points==9999) {
		$points = $defpoints;
	}

	$useeditor='review';
	$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js?v=113016"></script>';
	$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/gb-scoretools.js?v=120617"></script>';
	$placeinhead .= "<script type=\"text/javascript\">";
	$placeinhead .= 'function jumptostu() { ';
	$placeinhead .= '       var stun = document.getElementById("stusel").value; ';
	$address = $GLOBALS['basesiteurl'] . "/course/gradeallq.php?stu=$stu&cid=$cid&gbmode=$gbmode&aid=$aid&qid=$qid&ver=$ver";
	$placeinhead .= "       var toopen = '$address&page=' + stun;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	$placeinhead .= 'var GBdeffbtext ="'.Sanitize::encodeStringForDisplay($deffbtext).'";';
	$placeinhead .= '</script>';
	if ($sessiondata['useed']!=0) {
		$placeinhead .= '<script type="text/javascript"> initeditor("divs","fbbox",null,true);</script>';
	}
	$placeinhead .= '<style type="text/css"> .fixedbottomright {position: fixed; right: 10px; bottom: 10px;}</style>';
	require("../includes/rubric.php");
	$sessiondata['coursetheme'] = $coursetheme;
	require("../assessment/header.php");
	echo "<style type=\"text/css\">p.tips {	display: none;}\n .hideongradeall { display: none;} .pseudohidden {visibility:hidden;position:absolute;}</style>\n";
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	echo "&gt; <a href=\"gb-itemanalysis.php?stu=" . Sanitize::encodeUrlParam($stu) . "&cid=$cid&aid=" . Sanitize::onlyInt($aid) . "\">Item Analysis</a> ";
	echo "&gt; Grading a Question</div>";
	echo "<div id=\"headergradeallq\" class=\"pagetitle\"><h2>Grading a Question in ".Sanitize::encodeStringForDisplay($aname)."</h2></div>";
	echo "<p><b>Warning</b>: This page may not work correctly if the question selected is part of a group of questions</p>";
	echo '<div class="cpmid">';
	if ($page==-1) {
		echo "<a href=\"gradeallq.php?stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=$cid&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "&page=0\">Grade one student at a time</a> (Do not use for group assignments)";
	} else {
		echo "<a href=\"gradeallq.php?stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=$cid&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "&page=-1\">Grade all students at once</a>";
	}
	echo '</div>';
	echo "<p>Note: Feedback is for whole assessment, not the individual question.</p>";

	//DB $query = "SELECT imas_rubrics.id,imas_rubrics.rubrictype,imas_rubrics.rubric FROM imas_rubrics JOIN imas_questions ";
	//DB $query .= "ON imas_rubrics.id=imas_questions.rubric WHERE imas_questions.id='$qid'";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB if (mysql_num_rows($result)>0) {
		//DB echo printrubrics(array(mysql_fetch_row($result)));
	$query = "SELECT imas_rubrics.id,imas_rubrics.rubrictype,imas_rubrics.rubric FROM imas_rubrics JOIN imas_questions ";
	$query .= "ON imas_rubrics.id=imas_questions.rubric WHERE imas_questions.id=:id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':id'=>$qid));
	if ($stm->rowCount()>0) {
		echo printrubrics(array($stm->fetch(PDO::FETCH_NUM)));
	}
	if ($page==-1) {
		echo '<button type=button id="hctoggle" onclick="hidecorrect()">'._('Hide Questions with Perfect Scores').'</button>';
		echo '<button type=button id="nztoggle" onclick="hidenonzero()">'._('Hide Nonzero Score Questions').'</button>';
		echo ' <button type=button id="hnatoggle" onclick="hideNA()">'._('Hide Unanswered Questions').'</button>';
		echo ' <button type="button" id="preprint" onclick="preprint()">'._('Prepare for Printing (Slow)').'</button>';
		echo ' <button type="button" id="showanstoggle" onclick="showallans()">'._('Show All Answers').'</button>';
	}
	echo ' <input type="button" id="clrfeedback" value="Clear all feedback" onclick="clearfeedback()" />';
	if ($deffbtext != '') {
		echo ' <input type="button" id="clrfeedback" value="Clear default feedback" onclick="cleardeffeedback()" />';
	}
	echo '<p>All visible questions: <button type=button onclick="allvisfullcred();">'._('Full Credit').'</button> ';
	echo '<button type=button onclick="allvisnocred();">'._('No Credit').'</button></p>';
	if ($page==-1) {
		echo '<div class="fixedbottomright">';
		echo '<button type="button" id="quicksavebtn" onclick="quicksave()">'._('Quick Save').'</button><br/>';
		echo '<span class="noticetext" id="quicksavenotice">&nbsp;</span>';
		echo '</div>';
	}
	echo "<form id=\"mainform\" method=post action=\"gradeallq.php?stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "&page=" . Sanitize::encodeUrlParam($page) . "&update=true\">\n";
	if ($isgroup>0) {
		echo '<p><input type="checkbox" name="onepergroup" value="1" onclick="hidegroupdup(this)" /> Grade one per group</p>';
	}

	echo "<p>";
	if ($ver=='graded') {
		echo "<b>Showing Graded Attempts.</b>  ";
		echo "<a href=\"gradeallq.php?stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "&ver=last\">Show Last Attempts</a>";
	} else if ($ver=='last') {
		echo "<a href=\"gradeallq.php?stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "&ver=graded\">Show Graded Attempts</a>.  ";
		echo "<b>Showing Last Attempts.</b>  ";
		echo "<br/><b>Note:</b> Grades and number of attempts used are for the Graded Attempt.  Part points might be inaccurate.";
	}
	echo "</p>";

	if ($page!=-1) {
		$stulist = array();
		//DB $query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions,imas_students ";
		//DB $query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_students.userid=imas_users.id AND imas_students.courseid='$cid' AND imas_assessment_sessions.assessmentid='$aid' ";
		//DB $query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions,imas_students ";
		$query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_students.userid=imas_users.id AND imas_students.courseid=:courseid AND imas_assessment_sessions.assessmentid=:assessmentid ";
		if ($hidelocked) {
			$query .= "AND imas_students.locked=0 ";
		}
		$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$stulist[] = $row[0].', '.$row[1];
		}
	}

	//DB $query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions,imas_students ";
	//DB $query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_students.userid=imas_users.id AND imas_students.courseid='$cid' AND imas_assessment_sessions.assessmentid='$aid' ";
	//DB $query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
	$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions,imas_students ";
	$query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_students.userid=imas_users.id AND imas_students.courseid=:courseid AND imas_assessment_sessions.assessmentid=:assessmentid ";
	if ($hidelocked) {
		$query .= "AND imas_students.locked=0 ";
	}
	$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
	if ($page != -1) {
		$page = intval($page);
		$query .= " LIMIT $page,1";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid));
	//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$cnt = 0;
	$onepergroup = array();
	require_once("../includes/filehandler.php");
	//DB if (mysql_num_rows($result)>0) {
	if ($stm->rowCount()>0) {

	//DB while($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
	while($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		$GLOBALS['assessver'] = $line['ver'];
		$feedback = json_decode($line['feedback'], true);
		if ($feedback === null) {
			$feedback = array('Z'=>$line['feedback']);
		}
		if ($page != -1) {
			echo '<input type="hidden" name="userid" value="' . Sanitize::onlyInt($line['userid']) . '"/>';
		}
		$asid = Sanitize::onlyInt($line['id']);
		$groupdup = false;
		if ($line['agroupid']>0) {
			$s3asid = 'grp'.$line['agroupid'].'/'.$aid;
			if (isset($onepergroup[$line['agroupid']])) {
				$groupdup = true;
			} else {
				echo "<input type=\"hidden\" name=\"groupasid[".Sanitize::onlyInt($line['agroupid'])."]\" value=\"".Sanitize::onlyInt($line['id'])."\" />";
				$onepergroup[$line['agroupid']] = $line['id'];
			}
		} else {
			if ($isgroup) {
				$groupdup = true;
			}
			$s3asid = $asid;
		}
		if (strpos($line['questions'],';')===false) {
			$questions = explode(",",$line['questions']);
			$bestquestions = $questions;
		} else {
			list($questions,$bestquestions) = explode(";",$line['questions']);
			$questions = explode(",",$questions);
			$bestquestions = explode(",",$bestquestions);
		}
		$sp = explode(';', $line['bestscores']);
		$scores = explode(",",$sp[0]);
		$attempts = explode(",",$line['bestattempts']);
		if ($ver=='graded') {
			$seeds = explode(",",$line['bestseeds']);
			$la = explode("~",$line['bestlastanswers']);
			$questions = $bestquestions;
		} else if ($ver=='last') {
			$seeds = explode(",",$line['seeds']);
			$la = explode("~",$line['lastanswers']);
		}
		//$loc = array_search($qid,$questions);
		$lockeys = array_keys($questions,$qid);
		foreach ($lockeys as $loc) {
			if ($groupdup) {
				echo '<div class="groupdup">';
			}
			echo "<div ";
			if (getpts($scores[$loc])==$points) {
				echo 'class="iscorrect"';
			} else if (getpts($scores[$loc])>0) {
				echo 'class="isnonzero"';
			} else if ($scores[$loc]==-1) {
				echo 'class="notanswered"';
			} else {
				echo 'class="iswrong"';
			}
			echo '>';

			echo "<p><span class=\"person\"><b>".Sanitize::encodeStringForDisplay($line['LastName']).', '.Sanitize::encodeStringForDisplay($line['FirstName']).'</b></span>';
			if ($page != -1) {
				echo '.  Jump to <select id="stusel" onchange="jumptostu()">';
				foreach ($stulist as $i=>$st) {
					echo '<option value="'.$i.'" ';
					if ($i==$page) {echo 'selected="selected"';}
					echo '>'.Sanitize::encodeStringForDisplay($st).'</option>';
				}
				echo '</select>';
			}
			echo '</p>';
			if (!$groupdup) {
				echo '<h4 class="group" style="display:none">'.Sanitize::encodeStringForDisplay($groupnames[$line['agroupid']]);
				if (isset($groupmembers[$line['agroupid']]) && count($groupmembers[$line['agroupid']])>0) {
					echo ' ('.Sanitize::encodeStringForDisplay(implode(', ',$groupmembers[$line['agroupid']])).')</h4>';
				} else {
					echo ' (empty)</h4>';
				}
			}

			$lastanswers[$cnt] = $la[$loc];
			$teacherreview = $line['userid'];

			if ($qtype=='multipart') {
				/*if (($p = strpos($qcontrol,'answeights'))!==false) {
					$p = strpos($qcontrol,"\n",$p);
					$answeights = getansweights($loc,substr($qcontrol,0,$p));
				} else {
					preg_match('/anstypes(.*)/',$qcontrol,$match);
					$n = substr_count($match[1],',')+1;
					if ($n>1) {
						$answeights = array_fill(0,$n-1,round(1/$n,3));
						$answeights[] = 1-array_sum($answeights);
					} else {
						$answeights = array(1);
					}
				}
				*/
				$answeights = getansweights($loc,$qcontrol);
				for ($i=0; $i<count($answeights)-1; $i++) {
					$answeights[$i] = round($answeights[$i]*$points,2);
				}
				//adjust for rounding
				$diff = $points - array_sum($answeights);
				$answeights[count($answeights)-1] += $diff;
			}

			if ($qtype=='multipart') {
				$GLOBALS['questionscoreref'] = array("scorebox$cnt",$answeights);
			} else {
				$GLOBALS['questionscoreref'] = array("scorebox$cnt",$points);
			}
			$qtypes = displayq($cnt,$qsetid,$seeds[$loc],true,false,$attempts[$loc]);


			echo "<div class=review>";
			echo '<span class="person">'.Sanitize::encodeStringForDisplay($line['LastName']).', '.Sanitize::encodeStringForDisplay($line['FirstName']).': </span>';
			if (!$groupdup) {
				echo '<span class="group" style="display:none">' . Sanitize::encodeStringForDisplay($groupnames[$line['agroupid']]) . ': </span>';
			}
			if ($isgroup) {

			}
			list($pt,$parts) = printscore($scores[$loc]);

			if ($parts=='') {
				if ($pt==-1) {
					$pt = 'N/A';
				}
				echo "<input type=text size=4 id=\"scorebox$cnt\" name=\"ud-" . Sanitize::onlyInt($line['id']) . "-".Sanitize::onlyFloat($loc)."\" value=\"".Sanitize::encodeStringForDisplay($pt)."\">";
				if ($rubric != 0) {
					echo printrubriclink($rubric,$points,"scorebox$cnt","fb-". $loc.'-'. Sanitize::onlyInt($line['id']),($loc+1));
				}
			}
			if ($parts!='') {
				echo " Parts: ";
				$prts = explode(', ',$parts);
				for ($j=0;$j<count($prts);$j++) {
					if ($prts[$j]==-1) {
						$prts[$j] = 'N/A';
					}
					echo "<input type=text size=2 id=\"scorebox$cnt-$j\" name=\"ud-" . Sanitize::onlyInt($line['id']) . "-".Sanitize::onlyFloat($loc)."-$j\" value=\"" . Sanitize::encodeStringForDisplay($prts[$j]) . "\">";
					if ($rubric != 0) {
						echo printrubriclink($rubric,$answeights[$j],"scorebox$cnt-$j","fb-". $loc.'-'. Sanitize::onlyInt($line['id']),($loc+1).' pt '.($j+1));
					}
					echo ' ';
				}

			}
			printf(" out of %d ", Sanitize::onlyInt($points));

			if ($parts!='') {
				$answeights = implode(', ',$answeights);
				echo "(parts: $answeights) ";
			}
			printf("in %s attempt(s)\n", Sanitize::encodeStringForDisplay($attempts[$loc]));
			if ($parts!='') {
				$togr = array();
				foreach ($qtypes as $k=>$t) {
					if ($t=='essay' || $t=='file') {
					  $togr[] = Sanitize::onlyInt($k);
					}
				}
				echo '<br/>Quick grade: <a href="#" class="fullcredlink" onclick="quickgrade('.$cnt.',0,\'scorebox\','.count($prts).',['.$answeights.']);return false;">Full credit all parts</a>';
				if (count($togr)>0) {
					$togr = implode(',',$togr);
					echo ' | <a href="#" onclick="quickgrade('.$cnt.',1,\'scorebox\',['.$togr.'],['.$answeights.']);return false;">Full credit all manually-graded parts</a>';
				}
			} else {
				echo '<br/>Quick grade: <a href="#" class="fullcredlink" onclick="quicksetscore(\'scorebox' . $cnt .'\','.Sanitize::onlyInt($points).');return false;">Full credit</a>';
			}
			$laarr = explode('##',$la[$loc]);
			if (count($laarr)>1) {
				echo "<br/>Previous Attempts:";
				$cntb =1;
				for ($k=0;$k<count($laarr)-1;$k++) {
					if ($laarr[$k]=="ReGen") {
						echo ' ReGen ';
					} else {
						echo "  <b>$cntb:</b> " ;
						if (preg_match('/@FILE:(.+?)@/',$laarr[$k],$match)) {
							$url = getasidfileurl($match[1]);
							echo "<a href=\"" . Sanitize::encodeUrlForHref($url) . "\" target=\"_new\">".Sanitize::encodeStringForDisplay(basename($match[1]))."</a>";
						} else {
							if (strpos($laarr[$k],'$f$')) {
								if (strpos($laarr[$k],'&')) { //is multipart q
									$laparr = explode('&',$laarr[$k]);
									foreach ($laparr as $lk=>$v) {
										if (strpos($v,'$f$')) {
											$tmp = explode('$f$',$v);
											$laparr[$lk] = $tmp[0];
										}
									}
									$laarr[$k] = implode('&',$laparr);
								} else {
									$tmp = explode('$f$',$laarr[$k]);
									$laarr[$k] = $tmp[0];
								}
							}
							if (strpos($laarr[$k],'$!$')) {
								if (strpos($laarr[$k],'&')) { //is multipart q
									$laparr = explode('&',$laarr[$k]);
									foreach ($laparr as $lk=>$v) {
										if (strpos($v,'$!$')) {
											$tmp = explode('$!$',$v);
											$laparr[$lk] = $tmp[0];
										}
									}
									$laarr[$k] = implode('&',$laparr);
								} else {
									$tmp = explode('$!$',$laarr[$k]);
									$laarr[$k] = $tmp[0];
								}
							}
							if (strpos($laarr[$k],'$#$')) {
								if (strpos($laarr[$k],'&')) { //is multipart q
									$laparr = explode('&',$laarr[$k]);
									foreach ($laparr as $lk=>$v) {
										if (strpos($v,'$#$')) {
											$tmp = explode('$#$',$v);
											$laparr[$lk] = $tmp[0];
										}
									}
									$laarr[$k] = implode('&',$laparr);
								} else {
									$tmp = explode('$#$',$laarr[$k]);
									$laarr[$k] = $tmp[0];
								}
							}
							echo Sanitize::encodeStringForDisplay(str_replace(array('&','%nbsp;','%%','<','>'),array('; ','&nbsp;','&','&lt;','&gt;'),$laarr[$k]));
						}
						$cntb++;
					}
				}
			}

			//echo " <a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?cid=$cid&add=new&quoteq=$i-$qsetid-{$seeds[$i]}&to={$_GET['uid']}\">Use in Msg</a>";
			//echo " &nbsp; <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$line['id']}&clearq=$i\">Clear Score</a>";
			echo "<br/>"._("Question Feedback").": ";
			//<textarea cols=50 rows=".($page==-1?1:3)." id=\"feedback-".Sanitize::onlyInt($line['id'])."\" name=\"feedback-".Sanitize::onlyInt($line['id'])."\">".Sanitize::encodeStringForDisplay($line['feedback'])."</textarea>";
			if ($sessiondata['useed']==0) {
				echo '<br/><textarea cols="60" rows="2" class="fbbox" id="fb-'.$loc.'-'.Sanitize::onlyInt($line['id']).'" name="fb-'.$loc.'-'.Sanitize::onlyInt($line['id']).'">';
				echo Sanitize::encodeStringForDisplay($feedback["Q$loc"]);
				echo '</textarea>';
			} else {
				echo '<div class="fbbox" id="fb-'.$loc.'-'.Sanitize::onlyInt($line['id']).'">';
				echo Sanitize::outgoingHtml($feedback["Q$loc"]);
				echo '</div>';
			}
			echo '<br/>Question #'.($loc+1);
			echo ". <a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?" . Sanitize::generateQueryStringFromMap(array(
					'cid' => $cid, 'add' => 'new', 'quoteq' => "{$loc}-{$qsetid}-{$seeds[$loc]}-$aid-{$line['ver']}",
					'to' => $line['userid'])) . "\">Use in Msg</a>";
			echo "</div>\n"; //end review div
			echo '</div>'; //end wrapper div
			if ($groupdup) {
				echo '</div>';
			}
			$cnt++;
		}
	}
	echo "<input type=\"submit\" value=\"Save Changes\"/> ";
	}

	echo "</form>";
	echo '<p>&nbsp;</p>';




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
	function printscore($sc) {
		if (strpos($sc,'~')===false) {

			return array($sc,'');
		} else {
			$pts = getpts($sc);
			$sc = str_replace('-1','N/A',$sc);
			$sc = str_replace('~',', ',$sc);
			return array($pts,$sc);
		}
	}
function getansweights($qi,$code) {
	global $seeds,$questions;
	if (preg_match('/scoremethod\s*=\s*"(singlescore|acct|allornothing)"/', $code)) {
		return array(1);
	}
	$i = array_search($qi,$questions);
	return sandboxgetweights($code,$seeds[$i]);
}

function sandboxgetweights($code,$seed) {
	srand($seed);
	$code = interpret('control','multipart',$code);
	if (($p=strrpos($code,'answeights'))!==false) {
		$np = strpos($code,"\n",$p);
		if ($np !== false) {
			$code = substr($code,0,$np).';if(isset($answeights)){return;};'.substr($code,$np);
		} else {
			$code .= ';if(isset($answeights)){return;};';
		}
		//$code = str_replace("\n",';if(isset($answeights)){return;};'."\n",$code);
	} else {
		$p=strrpos($code,'answeights');
		$np = strpos($code,"\n",$p);
		if ($np !== false) {
			$code = substr($code,0,$np).';if(isset($anstypes)){return;};'.substr($code,$np);
		} else {
			$code .= ';if(isset($answeights)){return;};';
		}
		//$code = str_replace("\n",';if(isset($anstypes)){return;};'."\n",$code);
	}
	eval($code);
	if (!isset($answeights)) {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$n = count($anstypes);
		if ($n>1) {
			$answeights = array_fill(0,$n-1,round(1/$n,3));
			$answeights[] = 1-array_sum($answeights);
		} else {
			$answeights = array(1);
		}
	} else if (!is_array($answeights)) {
		$answeights =  explode(',',$answeights);
	}
	$sum = array_sum($answeights);
	if ($sum==0) {$sum = 1;}
	foreach ($answeights as $k=>$v) {
		$answeights[$k] = $v/$sum;
	}
	return $answeights;
}
?>
