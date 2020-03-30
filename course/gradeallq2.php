<?php
//IMathAS:  Grade all of one question for an assessment, assess2 version
//(c) 2019 David Lippman

// Score boxes: ud-userid-qn-pn
// Feedbacks: fb-qn-userid

// TODO: rework one-stu-at-a-time to use userid as selector

	require("../init.php");
	require("../assess2/AssessInfo.php");
	require("../assess2/AssessRecord.php");

	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);
	if (!$isteacher && !$istutor) {
		require("../header.php");
		echo "You need to log in as a teacher or tutor to access this page";
		require("../footer.php");
		exit;
	}

	$cid = Sanitize::courseId($_GET['cid']);
	$stu = $_GET['stu'];
	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
	$gbmode = $_GET['gbmode'];
	} else if (isset($_SESSION[$cid.'gbmode'])) {
		$gbmode =  $_SESSION[$cid.'gbmode'];
	} else {
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
	if (isset($tutorsection) && $tutorsection!='') {
		$secfilter = $tutorsection;
	} else if (isset($_GET['secfilter'])) {
		$secfilter = $_GET['secfilter'];
		$_SESSION[$cid.'secfilter'] = $secfilter;
	} else if (isset($_SESSION[$cid.'secfilter'])) {
		$secfilter = $_SESSION[$cid.'secfilter'];
	} else {
		$secfilter = -1;
	}

	$stm = $DBH->prepare("SELECT name,defpoints,isgroup,groupsetid,deffeedbacktext,courseid,tutoredit,ver FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($aname,$defpoints,$isgroup,$groupsetid,$deffbtext,$assesscourseid,$tutoredit,$aver) = $stm->fetch(PDO::FETCH_NUM);
	if ($assesscourseid != $cid) {
		echo "Invalid assessment ID";
		exit;
	}
	if ($aver==1) {
		echo "Wrong page - use gradeallq.php for older assessments";
		exit;
	}
	if ($istutor && $tutoredit==2) {
		require("../header.php");
		echo "You not have access to view scores for this assessment";
		require("../footer.php");
		exit;
	} else if ($isteacher || ($istutor && $tutoredit==1)) {
		$canedit = 1;
	} else {
		$canedit = 0;
	}

	// Load new assess info class
	$assess_info = new AssessInfo($DBH, $aid, $cid, false);
	$assess_info->loadQuestionSettings('all');
	$ptsposs = $assess_info->getQuestionSetting($qid, 'points_possible');

	if (isset($_GET['update']) && $canedit) {
		// allscores and allfeedbacks are indexed by userid
		$allscores = array();
		$allfeedbacks = array();
		$grpscores = array();
		$grpfeedbacks = array();
		$locs = array();
		foreach ($_POST as $k=>$v) {
			if (strpos($k,'-')!==false) {
				$kp = explode('-',$k);
				if ($kp[0]=='ud') {
					//ud-userid-qn-pn
					$orig = $_POST['os-'.$kp[1].'-'.$kp[2].'-'.$kp[3]];
					if ($v != $orig) {
						if ($v=='N/A') {
							$allscores[$kp[1]][$kp[2]][$kp[3]] = -1;
						} else {
							$allscores[$kp[1]][$kp[2]][$kp[3]] = $v;
						}
					}
				} else if ($kp[0]=='fb') {
					//fb-qn-userid
					if ($v=='' || $v=='<p></p>') {
						$v = '';
					}
					$allfeedbacks[$kp[2]][$kp[1]] = $v;
				}
			}
		}
		if (isset($_POST['onepergroup']) && $_POST['onepergroup']==1) {
			foreach ($_POST['groupuid'] as $grp=>$uid) {
				$grpscores[$grp] = $allscores[$uid];
				$grpfeedbacks[$grp] = $allfeedbacks[$uid];
			}
			$onepergroup = true;
		} else {
			$onepergroup = false;
		}
		$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_records.* FROM imas_users,imas_assessment_records ";
		$query .= "WHERE imas_assessment_records.userid=imas_users.id AND imas_assessment_records.assessmentid=:assessmentid ";
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
		$updatedata = array();
		while($line=$stm->fetch(PDO::FETCH_ASSOC)) {

			$GLOBALS['assessver'] = $line['ver'];

			if ((!$onepergroup && (isset($allscores[$line['userid']]) || isset($allfeedbacks[$line['userid']]))) ||
				($onepergroup && (isset($grpscores[$line['agroupid']]) || isset($grpfeedbacks[$line['agroupid']])))
			) {
				$assess_record = new AssessRecord($DBH, $assess_info, false);
				$assess_record->setRecord($line);

				// AssessRecord functions expect score scores in form 'scored-qn-pn',
				// and feedbacks in form 'scored-qn'
				$scoresToSet = array();
				$feedbackToSet = array();
				$allQns = array();
				if ($onepergroup) {
					if ($line['agroupid']==0) { continue;}
					if (isset($grpscores[$line['agroupid']])) {
						foreach ($grpscores[$line['agroupid']] as $loc=>$sv) {
							$allQns[] = $loc;
							foreach($sv as $pn=>$spv) {
								$scoresToSet[$loc.'-'.$pn] = $spv;
							}
						}
					}
					if (isset($grpfeedback[$line['agroupid']])) {
						foreach ($grpfeedback[$line['agroupid']] as $loc=>$sv) {
							$allQns[] = $loc;
							$feedbackToSet[$loc] = $sv;
						}
					}
				} else {
					if (isset($allscores[$line['userid']])) {
						foreach ($allscores[$line['userid']] as $loc=>$sv) {
							$allQns[] = $loc;
							foreach($sv as $pn=>$spv) {
								$scoresToSet[$loc.'-'.$pn] = $spv;
							}
						}
					}
					if (isset($allfeedbacks[$line['userid']])) {
						foreach ($allfeedbacks[$line['userid']] as $loc=>$sv) {
							$allQns[] = $loc;
							$feedbackToSet[$loc] = $sv;
						}
					}
				}

				$adjustedScores = $assess_record->convertGbScoreOverrides($scoresToSet, $ptsposs);
				$adjustedFeedbacks = $assess_record->convertGbFeedbacks($feedbackToSet);
				$assess_record->setGbScoreOverrides($adjustedScores);
				$assess_record->setGbFeedbacks($adjustedFeedbacks);

				if (count($adjustedScores) > 0 || count($adjustedFeedbacks) > 0) {
					$assess_record->saveRecord();
				}

				// Normally it'd only make sense to update LTI scores that changed. But
				// this is a common trick to force score resends, so until another way
				// is added, this is removed:  count($adjustedScores) > 0 &&
				if (strlen($line['lti_sourcedid'])>1) {
					//update LTI score
					require_once("../includes/ltioutcomes.php");
					$gbscore = $assess_record->getGbScore();
					calcandupdateLTIgrade($line['lti_sourcedid'],$aid,$line['userid'],$gbscore['gbscore'],true);
				}
			}
		}

		if (isset($_GET['quick'])) {
			echo "saved";
		} else if ($page == -1 || isset($_POST['islaststu'])) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gb-itemanalysis2.php?"
				. Sanitize::generateQueryStringFromMap(array('stu' => $stu, 'cid' => $cid, 'aid' => $aid,
                    'r' => Sanitize::randomQueryStringParam(),)));
		} else {
			$page++;
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradeallq2.php?"
				. Sanitize::generateQueryStringFromMap(array('stu' => $stu, 'cid' => $cid, 'aid' => $aid,
					'qid' => $qid, 'page' => $page, 'r' => Sanitize::randomQueryStringParam(),)));
		}
		exit;
	}

	require("../includes/htmlutil.php");

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

	$stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE courseid=:courseid ORDER BY section");
	$stm->execute(array(':courseid'=>$cid));
	$sections = $stm->fetchAll(PDO::FETCH_COLUMN, 0);

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

	$lastupdate = '032320';

	$useeditor='review';
	$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric_min.js?v=071219"></script>';
	$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/gb-scoretools.js?v=032320"></script>';
	$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/index.css?v='.$lastupdate.'" />';
	$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/gbviewassess.css?v='.$lastupdate.'" />';
	$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/chunk-common.css?v='.$lastupdate.'" />';
	$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/print.css?v='.$lastupdate.'" media="print">';
	$placeinhead .= '<script src="'.$imasroot.'/javascript/AMhelpers2_min.js?v=070819" type="text/javascript"></script>';
	$placeinhead .= '<script src="'.$imasroot.'/javascript/eqntips_min.js" type="text/javascript"></script>';
	$placeinhead .= '<script src="'.$imasroot.'/javascript/drawing_min.js" type="text/javascript"></script>';
	$placeinhead .= '<script src="'.$imasroot.'/javascript/mathjs_min.js" type="text/javascript"></script>';
	$placeinhead .= '<script src="'.$imasroot.'/mathquill/AMtoMQ_min.js" type="text/javascript"></script>
	  <script src="'.$imasroot.'/mathquill/mathquill.min.js" type="text/javascript"></script>
	  <script src="'.$imasroot.'/mathquill/mqeditor_min.js" type="text/javascript"></script>
	  <script src="'.$imasroot.'/mathquill/mqedlayout_min.js" type="text/javascript"></script>
	  <link rel="stylesheet" type="text/css" href="'.$imasroot.'/mathquill/mathquill-basic.css">
	  <link rel="stylesheet" type="text/css" href="'.$imasroot.'/mathquill/mqeditor.css">';
	$placeinhead .= "<script type=\"text/javascript\">";
	$placeinhead .= 'function jumptostu() { ';
	$placeinhead .= '       var stun = document.getElementById("stusel").value; ';
	$address = $GLOBALS['basesiteurl'] . "/course/gradeallq2.php?";
	$address .= Sanitize::generateQueryStringFromMap(array('stu'=>$stu, 'cid'=>$cid, 'gbmode'=>$gbmode, 'aid'=>$aid, 'qid'=>$qid, 'ver'=>$ver));
	$placeinhead .= "       var toopen = '$address&page=' + stun;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	$placeinhead .= 'var GBdeffbtext ="'.Sanitize::encodeStringForDisplay($deffbtext).'";';
	$placeinhead .= 'function chgsecfilter() {
		var sec = document.getElementById("secfiltersel").value;
		var toopen = "'.$address.'&secfilter=" + encodeURIComponent(sec);
		window.location = toopen;
		}';
	$placeinhead .= '</script>';
	if ($_SESSION['useed']!=0) {
		$placeinhead .= '<script type="text/javascript"> initeditor("divs","fbbox",1,true);</script>';
	}
	$placeinhead .= '<style type="text/css"> .fixedbottomright {position: fixed; right: 10px; bottom: 10px; z-index:10;}</style>';
	require("../includes/rubric.php");
	$_SESSION['coursetheme'] = $coursetheme;
	require("../header.php");
	echo "<style type=\"text/css\">p.tips {	display: none;}\n .hideongradeall { display: none;} .pseudohidden {visibility:hidden;position:absolute;}</style>\n";
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	echo "&gt; <a href=\"gb-itemanalysis2.php?stu=" . Sanitize::encodeUrlParam($stu) . "&cid=$cid&aid=" . Sanitize::onlyInt($aid) . "\">Item Analysis</a> ";
	echo "&gt; Grading a Question</div>";
	echo "<div id=\"headergradeallq\" class=\"pagetitle\"><h1>Grading a Question in ".Sanitize::encodeStringForDisplay($aname)."</h1></div>";
	echo "<p><b>Warning</b>: This page may not work correctly if the question selected is part of a group of questions</p>";
	echo '<div class="cpmid">';
	if ($page==-1) {
		echo "<a href=\"gradeallq2.php?stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=$cid&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "&page=0\">Grade one student at a time</a> (Do not use for group assignments)";
	} else {
		echo "<a href=\"gradeallq2.php?stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=$cid&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "&page=-1\">Grade all students at once</a>";
	}
	if (count($sections)>1) {
		echo '<br/>';
		echo _('Limit to section').': ';
		writeHtmlSelect('secfiltersel', $sections, $sections, $secfilter, _('All'), '-1', 'onchange="chgsecfilter()"');
	}
	echo '</div>';
	echo "<p>Note: Feedback is for whole assessment, not the individual question.</p>";
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
		echo ' <button type="button" onclick="previewallfiles()">'._('Preview All Files').'</button>';
	}
	echo ' <input type="button" id="clrfeedback" value="Clear all feedback" onclick="clearfeedback()" />';
	if ($deffbtext != '') {
		echo ' <input type="button" id="clrfeedback" value="Clear default feedback" onclick="cleardeffeedback()" />';
	}
	if ($canedit) {
		echo '<p>All visible questions: <button type=button onclick="allvisfullcred();">'._('Full Credit').'</button> ';
		echo '<button type=button onclick="allvisnocred();">'._('No Credit').'</button></p>';
	}
	if ($page==-1 && $canedit) {
		echo '<div class="fixedbottomright">';
		echo '<button type="button" id="quicksavebtn" onclick="quicksave()">'._('Quick Save').'</button><br/>';
		echo '<span class="noticetext" id="quicksavenotice">&nbsp;</span>';
		echo '</div>';
	}
	echo "<form id=\"mainform\" method=post action=\"gradeallq2.php?stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "&page=" . Sanitize::encodeUrlParam($page) . "&update=true\">\n";
	if ($isgroup>0) {
		echo '<p><input type="checkbox" name="onepergroup" value="1" onclick="hidegroupdup(this)" /> Grade one per group</p>';
	}

	// TODO? Add support for 'last' version
	/*echo "<p>";
	if ($ver=='graded') {
		echo "<b>Showing Graded Attempts.</b>  ";
		echo "<a href=\"gradeallq2.php?stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "&ver=last\">Show Last Attempts</a>";
	} else if ($ver=='last') {
		echo "<a href=\"gradeallq2.php?stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::onlyInt($aid) . "&qid=" . Sanitize::onlyInt($qid) . "&ver=graded\">Show Graded Attempts</a>.  ";
		echo "<b>Showing Last Attempts.</b>  ";
		echo "<br/><b>Note:</b> Grades and number of attempts used are for the Graded Attempt.  Part points might be inaccurate.";
	}
	echo "</p>";
	*/

	if ($page!=-1) {
		$stulist = array();
		$qarr = array(':courseid'=>$cid, ':assessmentid'=>$aid);
		$query = "SELECT imas_users.LastName,imas_users.FirstName FROM imas_users,imas_assessment_records,imas_students ";
		$query .= "WHERE imas_assessment_records.userid=imas_users.id AND imas_students.userid=imas_users.id AND imas_students.courseid=:courseid AND imas_assessment_records.assessmentid=:assessmentid ";
		if ($hidelocked) {
			$query .= "AND imas_students.locked=0 ";
		}
		if ($secfilter != -1) {
			$query .= "AND imas_students.section=:section ";
			$qarr[':section'] = $secfilter;
		}
		$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$stulist[] = $row[0].', '.$row[1];
		}

		echo '<p>Jump to <select id="stusel" onchange="jumptostu()">';
		foreach ($stulist as $i=>$st) {
			echo '<option value="'.$i.'" ';
			if ($i==$page) {echo 'selected="selected"';}
			echo '>'.Sanitize::encodeStringForDisplay($st).'</option>';
		}
		echo '</select></p>';
	}

	$qarr = array(':courseid'=>$cid, ':assessmentid'=>$aid);
	$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_records.* FROM imas_users,imas_assessment_records,imas_students ";
	$query .= "WHERE imas_assessment_records.userid=imas_users.id AND imas_students.userid=imas_users.id AND imas_students.courseid=:courseid AND imas_assessment_records.assessmentid=:assessmentid ";
	if ($hidelocked) {
		$query .= "AND imas_students.locked=0 ";
	}
	if ($secfilter != -1) {
		$query .= "AND imas_students.section=:section ";
		$qarr[':section'] = $secfilter;
	}
	$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
	if ($page != -1) {
		$page = intval($page);
		$query .= " LIMIT $page,1";
	}

	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	$cnt = 0;
	$onepergroup = array();
	require_once("../includes/filehandler.php");
	if ($stm->rowCount()>0) {
	while($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		$assess_record = new AssessRecord($DBH, $assess_info, false);
		$assess_record->setRecord($line);
		$assess_record->setTeacherInGb(true);

		$GLOBALS['assessver'] = $line['ver'];

		if ($page != -1) {
			echo '<input type="hidden" name="userid" value="' . Sanitize::onlyInt($line['userid']) . '"/>';
		}

		$groupdup = false;
		if ($line['agroupid']>0) {
			$s3asid = 'grp'.$line['agroupid'].'/'.$aid;
			if (isset($onepergroup[$line['agroupid']])) {
				$groupdup = true;
			} else {
				echo "<input type=\"hidden\" name=\"groupuid[".Sanitize::onlyInt($line['agroupid'])."]\" value=\"".Sanitize::onlyInt($line['userid'])."\" />";
				$onepergroup[$line['agroupid']] = $line['userid'];
			}
		} else {
			if ($isgroup) {
				$groupdup = true;
			}
			$s3asid = $asid; // TODO: revisit this
		}

		// get an array of qn=>qid
		list($questions, $toloadquestions) = $assess_record->getQuestionIds('all', 'scored');

		// find this question id in list
		$lockeys = array_keys($questions,$qid);
		foreach ($lockeys as $loc) {
			$qdata = $assess_record->getGbQuestionVersionData($loc, true, 'scored', $cnt);
			$answeightTot = array_sum($qdata['answeights']);
			$qdata['answeights'] = array_map(function($v) use ($answeightTot) { return $v/$answeightTot;}, $qdata['answeights']);
			if ($groupdup) {
				echo '<div class="groupdup">';
			}
			echo "<div ";
			if ($qdata['gbrawscore']==1) {
				echo 'class="iscorrect bigquestionwrap"';
			} else if ($qdata['gbscore']>0) {
				echo 'class="isnonzero bigquestionwrap"';
			} else if ($qdata['status']=='unattempted') {
				echo 'class="notanswered bigquestionwrap"';
			} else {
				echo 'class="iswrong bigquestionwrap"';
			}
			echo '>';

			echo "<div class=headerpane><b>".Sanitize::encodeStringForDisplay($line['LastName'].', '.$line['FirstName']).'</b></div>';

			if (!$groupdup) {
				echo '<p class="group" style="display:none"><b>'.Sanitize::encodeStringForDisplay($groupnames[$line['agroupid']]);
				if (isset($groupmembers[$line['agroupid']]) && count($groupmembers[$line['agroupid']])>0) {
					echo '</b> ('.Sanitize::encodeStringForDisplay(implode(', ',$groupmembers[$line['agroupid']])).')</p>';
				} else {
					echo '</b> (empty)</p>';
				}
			}

			$teacherreview = $line['userid'];
			/*
			To re-enable, need to define before $qdata, but figure another way to
			get answeights/points.
			if ($qtype=='multipart') {
				$GLOBALS['questionscoreref'] = array("scorebox$cnt",$answeights);
			} else {
				$GLOBALS['questionscoreref'] = array("scorebox$cnt",$points);
			}
			*/
			echo '<div class="questionwrap">';
			echo $qdata['html'];
			echo '<script type="text/javascript">
				$(function() {
					imathasAssess.init('.json_encode($qdata['jsparams'], JSON_INVALID_UTF8_IGNORE).', false);
				});
				</script>';
			echo '</div>';

			if (!empty($qdata['work'])) {
				echo '<div class="questionpane">';
				echo '<button type="button" onclick="toggleWork(this)">'._('View Work').'</button>';
				echo '<div class="introtext" style="display:none;">' . $qdata['work'].'</div></div>';
			}

			echo "<div class=scoredetails>";
			echo '<span class="person">'.Sanitize::encodeStringForDisplay($line['LastName']).', '.Sanitize::encodeStringForDisplay($line['FirstName']).': </span>';
			if (!$groupdup) {
				echo '<span class="group" style="display:none">' . Sanitize::encodeStringForDisplay($groupnames[$line['agroupid']]) . ': </span>';
			}
			if ($isgroup) {

			}

			// loop over parts
			for ($pn = 0; $pn < count($qdata['answeights']); $pn++) {
				// get points on this part

				if (isset($qdata['scoreoverride']) && !is_array($qdata['scoreoverride'])) {
					$pts = round($qdata['scoreoverride'] * $qdata['points_possible'] * $qdata['answeights'][$pn], 3);
				} else if (isset($qdata['scoreoverride']) && isset($qdata['scoreoverride'][$pn])) {
					if (isset($qdata['parts'][$pn]['points_possible'])) {
						$pts = round($qdata['scoreoverride'][$pn] * $qdata['parts'][$pn]['points_possible'], 3);
					} else {
						$pts = round($qdata['scoreoverride'][$pn] * $qdata['points_possible'] * $qdata['answeights'][$pn], 3);
					}
				} else if (count($qdata['parts'])==1 && $qdata['parts'][0]['try']==0) {
					$pts = 'N/A';
				} else {
					$pts = $qdata['parts'][$pn]['score'];
				}

				// get possible on this part
				$ptposs = round($qdata['points_possible'] * $qdata['answeights'][$pn], 3);

				if ($canedit) {
					$boxid = (count($qdata['answeights'])>1) ? "$cnt-$pn" : $cnt;
					echo "<input type=text size=4 id=\"scorebox$boxid\" name=\"ud-" . Sanitize::onlyInt($line['userid']) . "-".Sanitize::onlyFloat($loc)."-$pn\" value=\"".Sanitize::encodeStringForDisplay($pts)."\">";
					echo "<input type=hidden name=\"os-" . Sanitize::onlyInt($line['userid']) . "-".Sanitize::onlyFloat($loc)."-$pn\" value=\"".Sanitize::encodeStringForDisplay($pts)."\">";
					if ($rubric != 0) {
						$fbref = (count($qdata['answeights'])>1) ? ($loc+1).' part '.($pn+1) : ($loc+1);
						echo printrubriclink($rubric,$qdata['points_possible'],"scorebox$boxid","fb-". $loc.'-'. Sanitize::onlyInt($line['userid']), $fbref);
					}
				} else {
					echo Sanitize::encodeStringForDisplay($pts);
				}
				echo '/'.Sanitize::encodeStringForDisplay($ptposs).' ';
			}

			if (count($qdata['answeights'])>1 && $canedit) {
				$togr = array();
				if (isset($qdata['parts'])) {
					foreach ($qdata['parts'] as $k=>$partinfo) {
						if (!empty($partinfo['req_manual'])) {
							$togr[] = Sanitize::onlyInt($k);
						}
					}
				}
				$fullscores = array();
				for ($pn = 0; $pn < count($qdata['answeights']); $pn++) {
					$fullscores[$pn] = round($qdata['points_possible'] * $qdata['answeights'][$pn], 3);
				}
				$fullscores = implode(',', $fullscores);

				echo '<br/>Quick grade: <a href="#" class="fullcredlink" onclick="quickgrade('.$cnt.',0,\'scorebox\','.count($qdata['answeights']).',['.$fullscores.']);return false;">Full credit all parts</a>';
				if (count($togr)>0) {
					$togr = implode(',',$togr);
					echo ' | <a href="#" onclick="quickgrade('.$cnt.',1,\'scorebox\',['.$togr.'],['.$fullscores.']);return false;">Full credit all manually-graded parts</a>';
				}
			} else if ($canedit) {
				echo '<br/>Quick grade: <a href="#" class="fullcredlink" onclick="quicksetscore(\'scorebox' . $cnt .'\','.Sanitize::onlyInt($qdata['points_possible']).',this);return false;">Full credit</a> <span class=quickfb></span>';
			}

			// TODO: Add Previous Tries display here

			echo "<br/>"._("Question Feedback").": ";
			if (!$canedit) {
				echo '<div>';
				echo Sanitize::outgoingHtml($qdata['feedback']);
				echo '</div>';
			} else if ($_SESSION['useed']==0) {
				echo '<br/><textarea cols="60" rows="2" class="fbbox" id="fb-'.$loc.'-'.Sanitize::onlyInt($line['userid']).'" name="fb-'.$loc.'-'.Sanitize::onlyInt($line['userid']).'">';
				echo Sanitize::encodeStringForDisplay($qdata['feedback'], true);
				echo '</textarea>';
			} else {
				echo '<div class="fbbox" id="fb-'.$loc.'-'.Sanitize::onlyInt($line['userid']).'">';
				echo Sanitize::outgoingHtml($qdata['feedback']);
				echo '</div>';
			}
			echo '<br/>Question #'.($loc+1);
			echo ". <a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?" . Sanitize::generateQueryStringFromMap(array(
					'cid' => $cid, 'add' => 'new', 'quoteq' => "{$loc}-{$qsetid}-{$qdata['seed']}-$aid-{$line['ver']}",
					'to' => $line['userid'])) . "\">Use in Msg</a>";
			echo "</div>\n"; //end review div
			echo '</div>'; //end wrapper div
			if ($groupdup) {
				echo '</div>';
			}
			$cnt++;
		}
	}
	if ($canedit) {
		echo "<input type=\"submit\" value=\"Save Changes\"/> ";
	}
	} else {
		echo '<p><b>'._('No submission to show').'</b></p>';
	}
	if ($page!=-1 && $page == count($stulist)-1) {
		echo '<input type=hidden name=islaststu value=1 />';
	}
	echo "</form>";
	echo '<p>&nbsp;</p>';
	$placeinfooter = '<div id="ehdd" class="ehdd">
    <span id="ehddtext"></span>
    <span onclick="showeh(curehdd);" style="cursor:pointer;">[more..]</span>
  	</div>
		<div id="eh" class="eh"></div>';
	$useeqnhelper = 0;
	require("../footer.php");
?>
