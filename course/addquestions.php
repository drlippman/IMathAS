<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
include("../includes/htmlutil.php");
require_once("../includes/TeacherAuditLog.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = _("Add/Remove Questions");

$curBreadcrumb = $breadcrumbbase;
if (empty($_COOKIE['fromltimenu'])) {
    $curBreadcrumb .= " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
}
if (isset($_GET['clearattempts']) || isset($_GET['clearqattempts']) || isset($_GET['withdraw'])) {
	$curBreadcrumb .= " <a href=\"addquestions.php?cid=" . Sanitize::courseId($_GET['cid']) . "&aid=" . Sanitize::onlyInt($_GET['aid']) . "\">"._("Add/Remove Questions")."</a> &gt; Confirm\n";
	//$pagetitle = "Modify Inline Text";
} else {
	$curBreadcrumb .= _("Add/Remove Questions")."\n";
	//$pagetitle = "Add Inline Text";
}

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = _("You need to log in as a teacher to access this page");
} elseif (!(isset($_GET['cid'])) || !(isset($_GET['aid']))) {
	$overwriteBody=1;
	$body = _("You need to access this page from the course page menu");
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING

	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);
	$stm = $DBH->prepare("SELECT courseid,ver,submitby FROM imas_assessments WHERE id=?");
	$stm->execute(array($aid));
	$row = $stm->fetch(PDO::FETCH_ASSOC);
	if ($row === null || $row['courseid'] != $cid) {
		echo _("Invalid ID");
		exit;
	} else if ($row['ver'] > 1) {
		$addassess = 'addassessment2.php';
	} else {
		$addassess = 'addassessment.php';
	}
    $aver = $row['ver'];
    $submitby = $row['submitby'];
    $modquestion = ($aver > 1) ? 'modquestion2' : 'modquestion';
    
    if ($aver > 1) {
		$query = "SELECT iar.userid FROM imas_assessment_records AS iar,imas_students WHERE ";
		$query .= "iar.assessmentid=:assessmentid AND iar.userid=imas_students.userid AND imas_students.courseid=:courseid LIMIT 1";
	} else {
		$query = "SELECT ias.id FROM imas_assessment_sessions AS ias,imas_students WHERE ";
		$query .= "ias.assessmentid=:assessmentid AND ias.userid=imas_students.userid AND imas_students.courseid=:courseid LIMIT 1";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':assessmentid'=>$aid, ':courseid'=>$cid));
	if ($stm->rowCount() > 0) {
		$beentaken = true;
	} else {
		$beentaken = false;
	}

	if (isset($_GET['grp'])) { $_SESSION['groupopt'.$aid] = Sanitize::onlyInt($_GET['grp']);}
	if (isset($_GET['selfrom'])) {
		$_SESSION['selfrom'.$aid] = Sanitize::stripHtmlTags($_GET['selfrom']);
	} else {
		if (!isset($_SESSION['selfrom'.$aid])) {
			$_SESSION['selfrom'.$aid] = 'lib';
		}
	}

	if (isset($teacherid) && isset($_GET['addset']) && !$beentaken) {
		if (!isset($_POST['nchecked']) && !isset($_POST['qsetids'])) {
			$overwriteBody = 1;
			$body = _("No questions selected").".  <a href=\"addquestions.php?cid=$cid&aid=$aid\">"._("Go back")."</a>\n";
		} else if (isset($_POST['add'])) {
			if ($aver > 1) {
				include("modquestiongrid2.php");
			} else {
				include("modquestiongrid.php");
			}
			if (isset($_GET['process'])) {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
				exit;
			}
		} else {
			$checked = $_POST['nchecked'];
			foreach ($checked as $qsetid) {
				$query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,questionsetid,showhints) ";
				$query .= "VALUES (:assessmentid, :points, :attempts, :penalty, :questionsetid, :showhints);";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':assessmentid'=>$aid, ':points'=>9999, ':attempts'=>9999,
					':penalty'=>9999, ':questionsetid'=>$qsetid,
					':showhints' => ($aver > 1) ? -1 : 0
				));
				$qids[] = $DBH->lastInsertId();
			}
			//add to itemorder
			$stm = $DBH->prepare("SELECT itemorder,viddata,defpoints FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			if ($row[0]=='') {
				$itemorder = implode(",",$qids);
			} else {
				$itemorder  = $row[0] . "," . implode(",",$qids);
			}
			$viddata = $row[1];
			if ($viddata != '') {
				$nextnum = 0;
				if ($row[0]!='') {
					foreach (explode(',', $row[0]) as $iv) {
						if (strpos($iv,'|')!==false) {
							$choose = explode('|', $iv);
							$nextnum += $choose[0];
						} else {
							$nextnum++;
						}
					}
				}
				$numnew= count($checked);
				$viddata = unserialize($viddata);
				if (!isset($viddata[count($viddata)-1][1])) {
					$finalseg = array_pop($viddata);
				} else {
					$finalseg = '';
				}
				for ($i=$nextnum;$i<$nextnum+$numnew;$i++) {
					$viddata[] = array('','',$i);
				}
				if ($finalseg != '') {
					$viddata[] = $finalseg;
				}
				$viddata = serialize($viddata);
			}
			$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder,viddata=:viddata WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':viddata'=>$viddata, ':id'=>$aid));

			require_once("../includes/updateptsposs.php");
			updatePointsPossible($aid, $itemorder, $row['defpoints']);

			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
			exit;
		}
	}
	if (isset($_GET['modqs'])) {
		if (!isset($_POST['checked']) && !isset($_POST['qids'])) {
			$overwriteBody = 1;
			$body = _("No questions selected").".  <a href=\"addquestions.php?cid=$cid&aid=$aid\">"._("Go back")."</a>\n";
		} else {
			if ($aver > 1) {
				include("modquestiongrid2.php");
			} else {
				include("modquestiongrid.php");
			}
			if (isset($_GET['process'])) {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
				exit;
			}
		}
	}
	if (isset($_REQUEST['clearattempts'])) {
		if (isset($_POST['clearattempts']) && $_POST['clearattempts']=="confirmed") {
			require_once('../includes/filehandler.php');
			deleteallaidfiles($aid);
			$grades = array();
			if ($aver > 1) {
				$stm = $DBH->prepare("SELECT userid,score FROM imas_assessment_records WHERE assessmentid=:assessmentid");
				$stm->execute(array(':assessmentid'=>$aid));
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			        $grades[$row['userid']]=$row["score"];
                }
                // clear out time limit extensions
                $stm = $DBH->prepare("UPDATE imas_exceptions SET timeext=0 WHERE timeext<>0 AND assessmentid=? AND itemtype='A'");
                $stm->execute(array($aid));
                
                $stm = $DBH->prepare("DELETE FROM imas_assessment_records WHERE assessmentid=:assessmentid");

			} else {
				$stm = $DBH->prepare("SELECT userid,bestscores FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
        $stm->execute(array(':assessmentid'=>$aid));
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
          $sp = explode(';', $row['bestscores']);
          $as = str_replace(array('-1','-2','~'), array('0','0',','), $sp[0]);
          $total = array_sum(explode(',', $as));
          $grades[$row['userid']] = $total;
        }
				$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
			}
			$stm->execute(array(':assessmentid'=>$aid));
			if ($stm->rowCount()>0) {
        TeacherAuditLog::addTracking(
          $cid,
          "Clear Attempts",
          $aid,
          array('grades'=>$grades)
        );
      }
			$stm = $DBH->prepare("DELETE FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
			$stm->execute(array(':assessmentid'=>$aid));
			$stm = $DBH->prepare("UPDATE imas_questions SET withdrawn=0 WHERE assessmentid=:assessmentid");
			$stm->execute(array(':assessmentid'=>$aid));
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
			exit;
		} else {
			$overwriteBody = 1;
			$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			$assessmentname = $stm->fetchColumn(0);
			$body = "<div class=breadcrumb>$curBreadcrumb</div>\n";
			$body .= "<h2>".Sanitize::encodeStringForDisplay($assessmentname)."</h2>";
			$body .= "<p>"._("Are you SURE you want to delete all attempts (grades) for this assessment?")."</p>";
			$body .= '<form method="POST" action="'.sprintf('addquestions.php?cid=%s&aid=%d',$cid, $aid).'">';
			$body .= '<p><button type=submit name=clearattempts value=confirmed>'._('Yes, Clear').'</button>';
			$body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid';\"></p>\n";
			$body .= '</form>';
		}
	}
	/*
	9/25/14: Doesn't appear to get referenced anywhere
	if (isset($_GET['clearqattempts'])) {
		if (isset($_GET['confirmed'])) {
			$clearid = $_GET['clearqattempts'];
			if ($clearid!=='' && is_numeric($clearid)) {
				$query = "SELECT id,questions,scores,attempts,lastanswers,bestscores,bestattempts,bestlastanswers ";
				$query .= "FROM imas_assessment_sessions WHERE assessmentid='$aid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					if (strpos($line['questions'],';')===false) {
						$questions = explode(",",$line['questions']);
						$bestquestions = $questions;
					} else {
						list($questions,$bestquestions) = explode(";",$line['questions']);
						$questions = explode(",",$questions);
						$bestquestions = explode(",",$bestquestions);
					}
					$qloc = array_search($clearid,$questions);
					if ($qloc!==false) {
						$attempts = explode(',',$line['attempts']);
						$lastanswers = explode('~',$line['lastanswers']);
						$bestattempts = explode(',',$line['bestattempts']);
						$bestlastanswers = explode('~',$line['bestlastanswers']);

						if (strpos($line['scores'],';')===false) {
							//old format
							$scores = explode(',',$line['scores']);
							$bestscores = explode(',',$line['bestscores']);
							$scores[$qloc] = -1;
							$bestscores[$qloc] = -1;
							$scorelist = implode(',',$scores);
							$bestscorelist = implode(',',$scores);
						} else {
							//has raw
							list($scorelist,$rawscorelist) = explode(';',$line['scores']);
							$scores = explode(',', $scorelist);
							$rawscores = explode(',', $rawscorelist);
							$scores[$qloc] = -1;
							$rawscores[$qloc] = -1;
							$scorelist = implode(',',$scores).';'.implode(',',$rawscores);
							list($bestscorelist,$bestrawscorelist,$firstscorelist) = explode(';',$line['bestscores']);
							$bestscores = explode(',', $bestscorelist);
							$bestrawscores = explode(',', $bestrawscorelist);
							$firstscores = explode(',', $firstscorelist);
							$bestscores[$qloc] = -1;
							$bestrawscores[$qloc] = -1;
							$firstscores[$qloc] = -1;
							$bestscorelist = implode(',',$bestscores).';'.implode(',',$bestrawscores).';'.implode(',',$firstscores);
						}



						$attempts[$qloc] = 0;
						$lastanswers[$qloc] = '';
						$bestattempts[$qloc] = 0;
						$bestlastanswers[$qloc] = '';
						$attemptslist = implode(',',$attempts);
						$lalist = addslashes(implode('~',$lastanswers));
						$bestattemptslist = implode(',',$attempts);
						$bestlalist = addslashes(implode('~',$lastanswers));

						$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',lastanswers='$lalist',";
						$query .= "bestscores='$bestscorelist',bestattempts='$bestattemptslist',bestlastanswers='$bestlalist' ";
						$query .= "WHERE id='{$line['id']}'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					}
				}
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
				exit;
			} else {
				$overwriteBody = 1;
				$body = "<p>Error with question id.  Try again.</p>";
			}
		} else {
			$overwriteBody = 1;
			$body = "<div class=breadcrumb>$curBreadcrumb</div>\n";
			$body .= "<p>Are you SURE you want to delete all attempts (grades) for this question?</p>";
			$body .= "<p>This will allow you to safely change points and penalty for a question, or give students another attempt ";
			$body .= "on a question that needed fixing.  This will NOT allow you to remove the question from the assessment.</p>";
			$body .= "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid&clearqattempts={$_GET['clearqattempts']}&confirmed=1'\">\n";
			$body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid'\"></p>\n";
		}
	}
	*/
	if (isset($_GET['withdraw'])) {
		if (isset($_POST['withdrawtype'])) {
			if (strpos($_GET['withdraw'],'-')!==false) {
				$isingroup = true;
				$loc = explode('-',$_GET['withdraw']);
				$toremove = $loc[0];
			} else {
				$isingroup = false;
				$toremove = $_GET['withdraw'];
			}
			$stm = $DBH->prepare("SELECT itemorder,defpoints FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			list($itemorder, $defpoints) = $stm->fetch(PDO::FETCH_NUM);
			$itemorder = explode(',', $itemorder);

			$qids = array();
			if ($isingroup && $_POST['withdrawtype']!='full') { //is group remove
				$qids = explode('~',$itemorder[$toremove]);
				if (strpos($qids[0],'|')!==false) { //pop off nCr
					array_shift($qids);
				}
			} else if ($isingroup) { //is single remove from group
				$sub = explode('~',$itemorder[$toremove]);
				if (strpos($sub[0],'|')!==false) { //pop off nCr
					array_shift($sub);
				}
				$qids = array($sub[$loc[1]]);
			} else { //is regular item remove
				$qids = array($itemorder[$toremove]);
			}
			$qidlist = implode(',',array_map('intval',$qids));
			//withdraw question
			$query = "UPDATE imas_questions SET withdrawn=1";
			if ($_POST['withdrawtype']=='zero' || $_POST['withdrawtype']=='groupzero') {
				$query .= ',points=0';
			}
			$query .= " WHERE id IN ($qidlist)";
			$stm = $DBH->query($query);

			//get possible points if needed
			if ($_POST['withdrawtype']=='full' || $_POST['withdrawtype']=='groupfull') {
				$poss = array();
				$query = "SELECT id,points FROM imas_questions WHERE id IN ($qidlist)";
				$stm = $DBH->query($query);
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					if ($row[1]==9999) {
						$poss[$row[0]] = $defpoints;
					} else {
						$poss[$row[0]] = $row[1];
					}
				}
			}

			if ($_POST['withdrawtype']=='zero' || $_POST['withdrawtype']=='groupzero') {
				//update points possible
				require_once("../includes/updateptsposs.php");
				updatePointsPossible($aid, $itemorder, $defpoints);
			}

			//update assessment sessions
			if ($aver > 1) {
				//If settings scores to zero, need to define $poss array
				if ($_POST['withdrawtype']=='zero' || $_POST['withdrawtype']=='groupzero') {
					$poss = array();
					foreach ($qids as $qid) {
						$poss[$qid] = 0;
					}
				}
				//need to re-score assessment attempts based on withdrawal
				require_once('../assess2/AssessInfo.php');
				require_once('../assess2/AssessRecord.php');
				$assess_info = new AssessInfo($DBH, $aid, $cid, false);
				$assess_info->loadQuestionSettings('all', false, false);
				$DBH->beginTransaction();
				$stm = $DBH->prepare("SELECT * FROM imas_assessment_records WHERE assessmentid=? FOR UPDATE");
		        $stm->execute(array($aid));
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					$assess_record = new AssessRecord($DBH, $assess_info, false);
					$assess_record->setRecord($row);
					$updatedScore = $assess_record->withdrawQuestions($poss);
					$assess_record->saveRecordIfNeeded();
					// also want to adjust practice attempts
					// this is sloppy, but not used often, so oh well
					$assess_record->setInPractice(true);
					$assess_record->withdrawQuestions($poss);
					$assess_record->saveRecordIfNeeded();

					if (strlen($row['lti_sourcedid'])>1) {
						//update LTI score
						require_once("../includes/ltioutcomes.php");
						calcandupdateLTIgrade($row['lti_sourcedid'], $aid, $row['userid'], $updatedScore, true);
					}
				}
				$DBH->commit();
			} else {
				$stm = $DBH->prepare("SELECT id,questions,bestscores,lti_sourcedid,userid FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
				$stm->execute(array(':assessmentid'=>$aid));
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					if (strpos($row['questions'],';')===false) {
						$qarr = explode(",",$row['questions']);
					} else {
						list($questions,$bestquestions) = explode(";",$row['questions']);
						$qarr = explode(",",$bestquestions);
					}
					if (strpos($row['bestscores'],';')===false) {
						$bestscores = explode(',',$row['bestscores']);
						$doraw = false;
					} else {
						list($bestscorelist,$bestrawscorelist,$firstscorelist) = explode(';',$row['bestscores']);
						$bestscores = explode(',', $bestscorelist);
						$bestrawscores = explode(',', $bestrawscorelist);
						$firstscores = explode(',', $firstscorelist);
						$doraw = true;
					}
					for ($i=0; $i<count($qarr); $i++) {
						if (in_array($qarr[$i],$qids)) {
							if ($_POST['withdrawtype']=='zero' || $_POST['withdrawtype']=='groupzero') {
								$bestscores[$i] = 0;
							} else if ($_POST['withdrawtype']=='full' || $_POST['withdrawtype']=='groupfull') {
								$bestscores[$i] = $poss[$qarr[$i]];
							}
						}
					}
					if ($doraw) {
						$slist = implode(',',$bestscores).';'.implode(',',$bestrawscores).';'.implode(',',$firstscores);
					} else {
						$slist = implode(',',$bestscores );
					}
					$stm2 = $DBH->prepare("UPDATE imas_assessment_sessions SET bestscores=:bestscores WHERE id=:id");
					$stm2->execute(array(':bestscores'=>$slist, ':id'=>$row['id']));

					if (strlen($row['lti_sourcedid'])>1) {
						//update LTI score
						require_once("../includes/ltioutcomes.php");
						calcandupdateLTIgrade($row['lti_sourcedid'], $aid, $row['userid'], $bestscores, true);
					}
				}
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
			exit;

		} else {
			if (strpos($_GET['withdraw'],'-')!==false) {
				$isingroup = true;
			} else {
				$isingroup = false;
			}
			$overwriteBody = 1;
			$body = "<div class=breadcrumb>$curBreadcrumb</div>\n";
			$body .= "<h2>"._("Withdraw Question")."</h2>";
			$body .= "<form method=post action=\"addquestions.php?cid=$cid&aid=$aid&withdraw=".Sanitize::encodeStringForDisplay($_GET['withdraw'])."\">";
			if ($isingroup) {
				$body .= '<p><b>'._('This question is part of a group of questions').'</b>.  </p>';
				$body .= '<input type=radio name="withdrawtype" value="groupzero" > '._('Set points possible and all student scores to zero <b>for all questions in group</b>').'<br/>';
				$body .= '<input type=radio name="withdrawtype" value="groupfull" checked="1"> '._('Set all student scores to points possible <b>for all questions in group</b>').'<br/>';
				$body .= '<input type=radio name="withdrawtype" value="full" > '._('Set all student scores to points possible <b>for this question only</b>');
			} else {
				$body .= '<input type=radio name="withdrawtype" value="zero" > '._('Set points possible and all student scores to zero').'<br/>';
				$body .= '<input type=radio name="withdrawtype" value="full" checked="1"> '._('Set all student scores to points possible');
			}
			$body .= '<p>'._('This action can <b>not</b> be undone').'.</p>';
			$body .= '<p><input type=submit value="Withdraw Question">';
			$body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid'\"></p>\n";

			$body .= '</form>';
		}

	}

	$address = $GLOBALS['basesiteurl'] . "/course/$addassess?cid=$cid&aid=$aid";
	$testqpage = ($courseUIver>1) ? 'testquestion2.php' : 'testquestion.php';
	$placeinhead = "<script type=\"text/javascript\">
		var previewqaddr = '$imasroot/course/$testqpage?cid=$cid';
		var addqaddr = '$address';
		var assessver = '$aver';
		</script>";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/addquestions.js?v=042220\"></script>";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/addqsort.js?v=042821\"></script>";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/junkflag.js\"></script>";
	$placeinhead .= "<script type=\"text/javascript\">var JunkFlagsaveurl = '". $GLOBALS['basesiteurl'] . "/course/savelibassignflag.php';</script>";
	$placeinhead .= "<link rel=\"stylesheet\" href=\"$staticroot/course/addquestions.css?v=100517\" type=\"text/css\" />";
	$loadiconfont = true;
	$useeditor = "noinit";

	//DEFAULT LOAD PROCESSING GOES HERE
	//load filter.  Need earlier than usual header.php load
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require_once("$curdir/../filter/filter.php");
	
	$stm = $DBH->prepare("SELECT itemorder,name,defpoints,displaymethod,showhints,showwork,intro FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($itemorder,$page_assessmentName,$defpoints,$displaymethod,$showhintsdef,$showworkdef,$assessintro) = $stm->fetch(PDO::FETCH_NUM);
	$ln = 1;

	// Format of imas_assessments.intro is a JSON representation like
	// [ "original (main) intro text",
	//  { displayBefore:  question number to display before,
	//    displayUntil:  last question number to display it for
	//    text:  the actual text to show
	//    ispage: is this is a page break (0 or 1)
	//    pagetitle: page title text
	//  },
  	//  ...
	// ]
	$text_segments = array();
	if (($introjson=json_decode($assessintro,true))!==null) { //is json intro
		//$text_segments = array_slice($introjson,1); //remove initial Intro text
		for ($i=0;$i<count($introjson);$i++) {
			if (isset($introjson[$i]['displayBefore'])) {
				if (!isset($text_segments[$introjson[$i]['displayBefore']])) {
					$text_segments[$introjson[$i]['displayBefore']] = array();
				}
				$text_segments[$introjson[$i]['displayBefore']][] = $introjson[$i];
			}
		}
	} else {
		if (strpos($assessintro, '[Q ')!==false || strpos($assessintro, '[QUESTION ')!==false) {
			$introconvertmsg = '<p>'.sprintf(_('It appears this assessment is using an older [Q #] or [QUESTION #] tag. You can %sconvert that into a new format%s if you would like.'), '<a href="convertintro.php?cid='.$cid.'&aid='.$aid.'">','</a>').'</p>';
		}
	}

	$grp0Selected = "";
	if (isset($_SESSION['groupopt'.$aid])) {
		$grp = $_SESSION['groupopt'.$aid];
		$grp1Selected = ($grp==1) ? " selected" : "";
	} else {
		$grp = 0;
		$grp0Selected = " selected";
	}

	$questionjsarr = array();
	$existingq = array();
	$query = "SELECT iq.id,iq.questionsetid,iqs.description,iqs.userights,iqs.ownerid,";
	$query .= "iqs.qtype,iq.points,iq.withdrawn,iqs.extref,imas_users.groupid,iq.showhints,";
    $query .= "iq.showwork,iq.rubric,iqs.solution,iqs.solutionopts,iqs.meantime,iqs.meanscore,";
    $query .= "iqs.meantimen FROM imas_questions AS iq ";
	$query .= "JOIN imas_questionset AS iqs ON iqs.id=iq.questionsetid JOIN imas_users ON iqs.ownerid=imas_users.id ";
	$query .= "WHERE iq.assessmentid=:aid";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':aid'=>$aid));
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($line===false) { continue; } //this should never happen, but avoid issues if it does
		$existingq[] = $line['questionsetid'];
		//output item array
		if ($line['userights']>3 || ($line['userights']==3 && $line['groupid']==$groupid) || $line['ownerid']==$userid || $adminasteacher) { //can edit without template?
			$canedit = 1;
		} else {
			$canedit = 0;
		}
		$extrefval = 0;
		if ($aver > 1) {
			if (($line['showhints']==-1 && ($showhintsdef&2)==2) ||
				($line['showhints'] > -1 && ($line['showhints']&2)==2)
			) {
				$extrefval += 1;
			}
		} else {
			if (($line['showhints']==0 && $showhintsdef==1) || $line['showhints']==2) {
				$extrefval += 1;
			}
		}
		if ($line['extref']!='') {
			$extref = explode('~~',$line['extref']);
			$hasvid = false;  $hasother = false;  $hascap = false;
			foreach ($extref as $v) {
				if (strtolower(substr($v,0,5))=="video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
					$hasvid = true;
					if (strpos($v,'!!1')!==false) {
						$hascap = true;
					}
				} else {
					$hasother = true;
				}
			}
			//$page_questionTable[$i]['extref'] = '';
			if ($hasvid) {
				$extrefval += 4;
			}
			if ($hasother) {
				$extrefval += 2;
			}
			if ($hascap) {
				$extrefval += 16;
			}
		}
		if ($line['solution']!='' && ($line['solutionopts']&2)==2) {
			$extrefval += 8;
        }
        if (($line['showwork'] == -1 && $showworkdef > 0) || $line['showwork'] > 0) {
            $extrefval += 32;
        }
        if ($line['rubric'] > 0) {
            $extrefval += 64;
        }

		$timeout = array();
		$timeout[0] = round($line['meantime']/60, 1);
		$timeout[1] = round($line['meanscore'], 1);
		$timeout[2] = round($line['meantime']/60, 1);
		$timeout[3] = intval($line['meantimen']);

		$questionjsarr[$line['id']] = array((int)$line['id'],
			(int)$line['questionsetid'],
			Sanitize::encodeStringForDisplay($line['description']),
			Sanitize::encodeStringForDisplay($line['qtype']),
			(int)Sanitize::onlyInt($line['points']),
			(int)$canedit,
			(int)Sanitize::onlyInt($line['withdrawn']),
			(int)$extrefval,
            $timeout
        );

	}

	$apointstot = 0;
	$qncnt = 0;

	$jsarr = array();
	if ($itemorder != '') {
		$items = explode(",",$itemorder);
	} else {
		$items = array();
    }
    $alt = 0;
	for ($i = 0; $i < count($items); $i++) {
		if (isset($text_segments[$qncnt])) {
			foreach ($text_segments[$qncnt] as $text_seg) {
				//stupid hack: putting a couple extra unused entries in array so length>=5
				$jsarr[] = array("text", $text_seg['text'],
					Sanitize::onlyInt($text_seg['displayUntil']-$text_seg['displayBefore']+1),
					Sanitize::onlyInt($text_seg['ispage']),
					$text_seg['pagetitle'],
					isset($text_seg['forntype'])?$text_seg['forntype']:0);
			}
		}
		if (strpos($items[$i],'~')!==false) {
			$subs = explode('~',$items[$i]);
			if (isset($_COOKIE['closeqgrp-'.$aid]) && in_array("$i",explode(',',$_COOKIE['closeqgrp-'.$aid]))) {
				$closegrp = 0;
			} else {
				$closegrp = 1;
			}
			$qsdata = array();
			for ($j=(strpos($subs[0],'|')===false)?0:1;$j<count($subs);$j++) {
				if (!isset($questionjsarr[$subs[$j]])) {continue;} //should never happen
				$qsdata[] = $questionjsarr[$subs[$j]];
			}
			if (count($qsdata)==0) { continue; } //should never happen
			if (strpos($subs[0],'|')===false) { //for backwards compat
				$jsarr[] = array(1,0,$qsdata,$closegrp);
				$qncnt++;
			} else {
				$grpparts = explode('|',$subs[0]);
				$jsarr[] = array((int)Sanitize::onlyInt($grpparts[0]),
					(int)Sanitize::onlyInt($grpparts[1]),
					$qsdata,
					(int)$closegrp);
				$qncnt += $grpparts[0];
			}
		} else {
			if (!isset($questionjsarr[$items[$i]])) {continue;} //should never happen
			$jsarr[] = $questionjsarr[$items[$i]];
			$qncnt++;
		}

		$alt = 1-$alt;
	}
	if (isset($text_segments[$qncnt])) {
		foreach ($text_segments[$qncnt] as $j=>$text_seg) {
			//stupid hack: putting a couple extra unused entries in array so length>=5
			$jsarr[] = array("text", $text_seg['text'],
				Sanitize::onlyInt($text_seg['displayUntil']-$text_seg['displayBefore']+1),
				Sanitize::onlyInt($text_seg['ispage']),
				$text_seg['pagetitle'], 1);
		}
	}

	unset($questionjsarr);

	//DATA MANIPULATION FOR POTENTIAL QUESTIONS
	if ($_SESSION['selfrom'.$aid]=='lib') { //selecting from libraries

		//remember search
		if (isset($_POST['search'])) {
			$safesearch = trim($_POST['search']);
			$safesearch = str_replace(' and ', ' ',$safesearch);
			$search = $safesearch;
			$search = str_replace('"','&quot;',$search);
			$_SESSION['lastsearch'.$cid] = $safesearch; ///str_replace(" ","+",$safesearch);
			if (isset($_POST['searchall'])) {
				$searchall = 1;
			} else {
				$searchall = 0;
			}
			$_SESSION['searchall'.$cid] = $searchall;
			if (isset($_POST['searchmine'])) {
				$searchmine = 1;
			} else {
				$searchmine = 0;
			}
			$_SESSION['searchmine'.$cid] = $searchmine;
			if (isset($_POST['newonly'])) {
				$newonly = 1;
			} else {
				$newonly = 0;
			}
			$_SESSION['searchnewonly'.$cid] = $newonly;
		} else if (isset($_SESSION['lastsearch'.$cid])) {
			$safesearch = trim($_SESSION['lastsearch'.$cid]); //str_replace("+"," ",$_SESSION['lastsearch'.$cid]);
			$search = $safesearch;
			$search = str_replace('"','&quot;',$search);
			$searchall = $_SESSION['searchall'.$cid] ?? 0;
			$searchmine = $_SESSION['searchmine'.$cid] ?? 0;
			$newonly = $_SESSION['searchnewonly'.$cid] ?? 0;
		} else {
			$search = '';
			$searchall = 0;
			$searchmine = 0;
			$safesearch = '';
			$newonly = 0;
		}
		$searchlikevals = array();
		$isIDsearch = 0;
		if (trim($safesearch)=='') {
			$searchlikes = '';
		} else {
			if (substr($safesearch,0,6)=='regex:') {
				$safesearch = substr($safesearch,6);
				$searchlikes = "imas_questionset.description REGEXP ? AND ";
				$searchlikevals[] = $safesearch;
			} else if (substr($safesearch,0,3)=='id=') {
				$searchlikes = "imas_questionset.id=? AND ";
				$searchlikevals = array(substr($safesearch,3));
				$isIDsearch = substr($safesearch,3);
			} else {
				$searchterms = explode(" ",$safesearch);
				$searchlikes = '';
				foreach ($searchterms as $k=>$v) {
					if (substr($v,0,5) == 'type=') {
						$searchlikes .= "imas_questionset.qtype=? AND ";
						$searchlikevals[] = substr($v,5);
						unset($searchterms[$k]);
					}
				}
				$wholewords = array();
				foreach ($searchterms as $k=>$v) {
					if (ctype_alnum($v) && strlen($v)>3) {
						$wholewords[] = '+'.$v.'*';
						unset($searchterms[$k]);
					}
				}
				if (count($wholewords)>0 || count($searchterms)>0) {
					$searchlikes .= '(';
					if (count($wholewords)>0) {
						$searchlikes .= 'MATCH(imas_questionset.description) AGAINST(? IN BOOLEAN MODE) ';
						$searchlikevals[] = implode(' ', $wholewords);
					}
					if (count($searchterms)>0) {
						if (count($wholewords)>0) {
							$searchlikes .= 'AND ';
						}
						$searchlikes .= "(imas_questionset.description LIKE ?".str_repeat(" AND imas_questionset.description LIKE ?",count($searchterms)-1).") ";
						foreach ($searchterms as $t) {
							$searchlikevals[] = "%$t%";
						}
					}
					if (ctype_digit($safesearch)) {
						$searchlikes .= "OR imas_questionset.id=?) AND ";
						$searchlikevals[] = $safesearch;
						$isIDsearch = $safesearch;
					} else {
						$searchlikes .= ") AND";
					}
				}
			}
		}

		if (isset($_POST['libs'])) {
			if ($_POST['libs']=='') {
				$_POST['libs'] = $userdeflib;
			}
			$searchlibs = $_POST['libs'];
			//$_SESSION['lastsearchlibs'] = implode(",",$searchlibs);
			$_SESSION['lastsearchlibs'.$aid] = $searchlibs;
		} else if (isset($_GET['listlib'])) {
			$searchlibs = $_GET['listlib'];
			$_SESSION['lastsearchlibs'.$aid] = $searchlibs;
			$searchall = 0;
			$_SESSION['searchall'.$aid] = $searchall;
			$_SESSION['lastsearch'.$aid] = '';
			$searchlikes = '';
			$searchlikevals = array();
			$search = '';
			$safesearch = '';
		}else if (isset($_SESSION['lastsearchlibs'.$aid])) {
			//$searchlibs = explode(",",$_SESSION['lastsearchlibs']);
			$searchlibs = $_SESSION['lastsearchlibs'.$aid];
		} else {
			if (isset($CFG['AMS']['guesslib']) && count($existingq)>0) {
				$maj = count($existingq)/2;
				$existingqlist = implode(',', $existingq);  //pulled from database, so no quotes needed
				$stm = $DBH->query("SELECT libid,COUNT(qsetid) FROM imas_library_items WHERE qsetid IN ($existingqlist) AND deleted=0 GROUP BY libid");
				$foundmaj = false;
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					if ($row[1]>=$maj) {
						$searchlibs = $row[0];
						$foundmaj = true;
						break;
					}
				}
				if (!$foundmaj) {
					//echo "No maj found";
					$searchlibs = $userdeflib;
				}
			} else {
				$searchlibs = $userdeflib;
			}
		}
		$llist = implode(',',array_map('intval', explode(',',$searchlibs)));

		if (!$beentaken) {
			//potential questions
			$lnamesarr = array();
			$libsortorder = array();
			if (substr($searchlibs,0,1)=="0") {
				$lnamesarr[0] = "Unassigned";
				$libsortorder[0] = 0;
			}
			$stm = $DBH->query("SELECT name,id,sortorder FROM imas_libraries WHERE id IN ($llist) ORDER BY FIELD(id,$llist)");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$lnamesarr[$row[1]] = $row[0];
				$libsortorder[$row[1]] = $row[2];
			}
			$lnames = implode(", ",$lnamesarr);

			$page_libRowHeader = ($searchall==1) ? "<th>Library</th>" : "";

			if (isset($search) && ($searchall==0 || $searchlikes!='' || $searchmine==1)) {
				$qarr = $searchlikevals;
				$query = "SELECT imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_questionset.extref,imas_library_items.libid,imas_questionset.ownerid,imas_questionset.meantime,imas_questionset.meanscore,imas_questionset.meantimen,imas_questionset.solution,imas_questionset.solutionopts,imas_library_items.junkflag, imas_questionset.broken, imas_library_items.id AS libitemid,imas_users.groupid ";
				$query .= "FROM imas_questionset JOIN imas_library_items ON imas_library_items.qsetid=imas_questionset.id AND imas_library_items.deleted=0 ";
				$query .= "JOIN imas_users ON imas_questionset.ownerid=imas_users.id WHERE imas_questionset.deleted=0 AND imas_questionset.replaceby=0 AND $searchlikes ";
				$query .= " (imas_questionset.ownerid=? OR imas_questionset.userights>0)";
				$qarr[] = $userid;

				if ($searchall==0) {
					$query .= "AND imas_library_items.libid IN ($llist)"; //pre-sanitized
				}
				if ($searchmine==1) {
					$query .= " AND imas_questionset.ownerid=?";
					$qarr[] = $userid;
				} else {
					if ($isIDsearch>0) {
						$query .= " AND (imas_library_items.libid > 0 OR imas_questionset.ownerid=? OR imas_questionset.id=?) ";
						$qarr[] = $userid;
						$qarr[] = $isIDsearch;
					} else {
						$query .= " AND (imas_library_items.libid > 0 OR imas_questionset.ownerid=?) ";
						$qarr[] = $userid;
					}

				}
				if ($searchall==1) {
					$query .= " LIMIT 300";
					$offset = 0;
					if (isset($_REQUEST['offset'])) {
						$offset = Sanitize::onlyInt($_REQUEST['offset']);
						if ($offset>0 && $offset < 1000000000) {
							$query .= " OFFSET $offset";
						}
					}
				}

				if ($search=='recommend' && count($existingq)>0) {
					$existingqlist = implode(',',$existingq);  //pulled from database, so no quotes needed
					$stm = $DBH->prepare("SELECT a.questionsetid, count( DISTINCT a.assessmentid ) as qcnt,
						imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_questionset.ownerid
						FROM imas_questions AS a
						JOIN imas_questions AS b ON a.assessmentid = b.assessmentid
						JOIN imas_questions AS c ON b.questionsetid = c.questionsetid
						AND c.assessmentid = :assessmentid
						JOIN imas_questionset  ON a.questionsetid=imas_questionset.id
						AND (imas_questionset.ownerid=:ownerid OR imas_questionset.userights>0)
						AND imas_questionset.deleted=0
						AND imas_questionset.replaceby=0
						WHERE a.questionsetid NOT IN ($existingqlist)
						GROUP BY a.questionsetid ORDER BY qcnt DESC LIMIT 100");
					$stm->execute(array(':assessmentid'=>$aid, ':ownerid'=>$userid));
				} else {
					$stm = $DBH->prepare($query);
					$stm->execute($qarr);
					//echo preg_replace(array_fill(0,count($qarr),'/[?]/'), $qarr, $query, 1);
				}
				if ($stm->rowCount()==0) {
					$noSearchResults = true;
				} else {
					$searchlimited = ($stm->rowCount()==300);

					$alt=0;
					$i=0;
					$page_questionTable = array();
					$page_libstouse = array();
					$page_libqids = array();
					$page_useavgtimes = false;
					while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
						if ($newonly && in_array($line['id'],$existingq)) {
							continue;
						}
						if (isset($page_questionTable[$line['id']])) {
							continue;
						}
						if (!isset($page_libqids[$line['libid']]) && isset($lnamesarr[$line['libid']])) {
							$page_libstouse[] = $line['libid'];
							$page_libqids[$line['libid']] = array();
						}

						$page_libqids[$line['libid']][] = $line['id'];

						$i = $line['id'];
						$page_questionTable[$i]['checkbox'] = "<input type=checkbox name='nchecked[]' value='" . Sanitize::onlyInt($line['id']) . "' id='qo$ln'>";
						if ($line['broken'] > 0) {
							$line['description'] = '('._('Reported Broken').') '.$line['description'];
						}
						if (in_array($i,$existingq)) {
							$page_questionTable[$i]['desc'] = '<span style="color: #999">'.filter(Sanitize::encodeStringForDisplay($line['description'])).'</span>';
						} else if ($line['broken'] > 0) {
							$page_questionTable[$i]['desc'] = '<span style="color: #f66"><i>'.filter(Sanitize::encodeStringForDisplay($line['description'])).'</i></span>';
						} else {
							$page_questionTable[$i]['desc'] = filter(Sanitize::encodeStringForDisplay($line['description']));
						}
						$page_questionTable[$i]['preview'] = "<button type=\"button\" onClick=\"previewq('selq','qo$ln',".Sanitize::onlyInt($line['id']).",true,false)\">"._("Preview")."</button>";
						$page_questionTable[$i]['type'] = $line['qtype'];
						//avgtime, avgtimefirst, avgscorefirst, ndatapoints
						//initial avgtime might be 0 if not populated
						if ($line['meantimen'] > 100) {
							$page_useavgtimes = true;
							$page_questionTable[$i]['meantime'] = round($line['meantime']/60,1);
							$page_questionTable[$i]['qdata'] = array($line['meanscore'],$line['meantime'],$line['meantimen']);
						}

						$page_questionTable[$i]['broken'] = intval($line['broken']);

						if ($searchall==1) {
							$page_questionTable[$i]['lib'] = "<a href=\"addquestions.php?cid=$cid&aid=$aid&listlib=".Sanitize::encodeUrlParam($line['libid'])."\">"._("List lib")."</a>";
						} else {
							$page_questionTable[$i]['libitemid'] = Sanitize::encodeStringForDisplay($line['libitemid']);
							$page_questionTable[$i]['junkflag'] = Sanitize::encodeStringForDisplay($line['junkflag']);
						}
						$page_questionTable[$i]['extref'] = '';
						$page_questionTable[$i]['cap'] = 0;
						if ($line['extref']!='') {
							$extref = explode('~~',$line['extref']);
							$hasvid = false;  $hasother = false; $hascap = false;
							foreach ($extref as $v) {
								if (substr($v,0,5)=="Video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
									$hasvid = true;
									if (strpos($v,'!!1')!==false) {
										$page_questionTable[$i]['cap'] = 1;
										$altcap = "Captioned ";
									} else {
										$altcap = "";
									}
								} else {
									$hasother = true;
								}
							}
							if ($hasvid) {
								$page_questionTable[$i]['extref'] .= "<img src=\"$staticroot/img/video_tiny.png\" alt=\"{$altcap}"._("Video")."\"/>";
							}
							if ($hasother) {
								$page_questionTable[$i]['extref'] .= "<img src=\"$staticroot/img/html_tiny.png\" alt=\""._("Help Resource")."\"/>";
							}
						}
						if ($line['solution']!='' && ($line['solutionopts']&2)==2) {
							$page_questionTable[$i]['extref'] .= "<img src=\"$staticroot/img/assess_tiny.png\" alt=\""._("Detailed Solution")."\"/>";
						}
						/*$query = "SELECT COUNT(id) FROM imas_questions WHERE questionsetid='{$line['id']}'";
						$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
						$times = mysql_result($result2,0,0);
						$page_questionTable[$i]['times'] = $times;
						*/
						$page_questionTable[$i]['times'] = 0;

						if ($line['ownerid']==$userid) {
							if ($line['userights']==0) {
								$page_questionTable[$i]['mine'] = _("Private");
							} else {
								$page_questionTable[$i]['mine'] = _("Yes");
							}
						} else {
							$page_questionTable[$i]['mine'] = "";
						}


						$page_questionTable[$i]['add'] = "<a href=\"$modquestion.php?qsetid=".Sanitize::onlyInt($line['id'])."&aid=$aid&cid=$cid\">"._("Add")."</a>";

						if ($line['userights']>3 || ($line['userights']==3 && $line['groupid']==$groupid) || $line['ownerid']==$userid) {
							$page_questionTable[$i]['src'] = "<a href=\"moddataset.php?id=".Sanitize::onlyInt($line['id'])."&aid=$aid&cid=$cid&frompot=1\">"._("Edit")."</a>";
						} else {
							$page_questionTable[$i]['src'] = "<a href=\"moddataset.php?id=".Sanitize::onlyInt($line['id'])."&aid=$aid&cid=$cid\">"._("View Code")."</a>";
						}

						$page_questionTable[$i]['templ'] = "<a href=\"moddataset.php?id=".Sanitize::onlyInt($line['id'])."&aid=$aid&cid=$cid&template=true\">"._("Template")."</a>";
						//$i++;
						$ln++;

					} //end while

					//pull question useage data
					if (count($page_questionTable)>0) {
						$allusedqids = implode(',', array_keys($page_questionTable));
						$stm = $DBH->query("SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allusedqids) GROUP BY questionsetid");
						while ($row = $stm->fetch(PDO::FETCH_NUM)) {
							$page_questionTable[$row[0]]['times'] = $row[1];
						}
					}


					if ($searchall==1) { // consolidate all
						uksort($page_questionTable, function($qA,$qB) use ($page_questionTable) {
							if ($page_questionTable[$qA]['broken'] != $page_questionTable[$qB]['broken']) {
								return $page_questionTable[$qA]['broken'] - $page_questionTable[$qB]['broken'];
							} else {
								return $qA - $qB;
							}
						});
						$page_libstouse = array(0);
						$page_libqids = array(0=>array_keys($page_questionTable));
					} else {
						//sort alpha sorted libraries
						foreach ($page_libstouse as $libid) {
							usort($page_libqids[$libid], function($qA,$qB) use ($libsortorder,$page_questionTable,$page_libqids,$libid) {
								if ($page_questionTable[$qA]['broken'] != $page_questionTable[$qB]['broken']) {
									return $page_questionTable[$qA]['broken'] - $page_questionTable[$qB]['broken'];
								} else if ($page_questionTable[$qA]['junkflag'] != $page_questionTable[$qB]['junkflag']) {
									return $page_questionTable[$qA]['junkflag'] - $page_questionTable[$qB]['junkflag'];
								} else if ($libsortorder[$libid]==1) {
									return strnatcasecmp($page_questionTable[$qA]['desc'], $page_questionTable[$qB]['desc']);
								} else {
									return $qA - $qB;
								}
							});
						}
					}


				}
			}

		}

	} else if ($_SESSION['selfrom'.$aid]=='assm') { //select from assessments

		if (isset($_GET['clearassmt'])) {
			unset($_SESSION['aidstolist'.$aid]);
		}
		if (isset($_POST['achecked'])) {
			if (count($_POST['achecked'])!=0) {
				$aidstolist = $_POST['achecked'];
				$_SESSION['aidstolist'.$aid] = $aidstolist;
			}
		}
		if (isset($_SESSION['aidstolist'.$aid])) { //list questions
			$aidlist = implode(',', array_map('intval', $_SESSION['aidstolist'.$aid]));
			$stm = $DBH->query("SELECT id,name,itemorder FROM imas_assessments WHERE id IN ($aidlist)");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$aidnames[$row[0]] = $row[1];
				$items = str_replace('~',',',$row[2]);
				if ($items=='') {
					$aiditems[$row[0]] = array();
				} else {
					$aiditems[$row[0]] = explode(',',$items);
				}
			}
			$x=0;
			$page_assessmentQuestions = array('aiddesc' => []);
			foreach ($_SESSION['aidstolist'.$aid] as $aidq) {
				$query = "SELECT imas_questions.id,imas_questionset.id,imas_questionset.description,imas_questionset.qtype,imas_questionset.ownerid,imas_questionset.userights,imas_questionset.extref,imas_users.groupid FROM imas_questionset,imas_questions,imas_users";
				$query .= " WHERE imas_questionset.id=imas_questions.questionsetid AND imas_questionset.ownerid=imas_users.id AND imas_questions.assessmentid=:assessmentid";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':assessmentid'=>$aidq));
				if ($stm->rowCount()==0) {
					continue;
				}
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$qsetid[$row[0]] = $row[1];
					$descr[$row[0]] = $row[2];
					$qtypes[$row[0]] = $row[3];
					$owner[$row[0]] = $row[4];
					$userights[$row[0]] = $row[5];
					$extref[$row[0]] = $row[6];
					$qgroupid[$row[0]] = $row[7];
				}
				//pull question useage data
				if (count($qsetid)>0) {
					$allusedqids = implode(',', array_unique($qsetid));
					$stm = $DBH->query("SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allusedqids) GROUP BY questionsetid");
					$qsetusecnts = array();
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$qsetusecnts[$row[0]] = $row[1];
					}
				}

				$page_assessmentQuestions['aiddesc'][] = $aidnames[$aidq];
				$y=0;
				foreach($aiditems[$aidq] as $qid) {
					if (strpos($qid,'|')!==false) { continue;}
					$page_assessmentQuestions[$x]['checkbox'][$y] = "<input type=checkbox name='nchecked[]' id='qo$ln' value='" . Sanitize::onlyFloat($qsetid[$qid]) . "'>";
					if (in_array($qsetid[$qid],$existingq)) {
						$page_assessmentQuestions[$x]['desc'][$y] = '<span style="color: #999">'.Sanitize::encodeStringForDisplay(filter($descr[$qid])).'</span>';
					} else {
						$page_assessmentQuestions[$x]['desc'][$y] = Sanitize::encodeStringForDisplay(filter($descr[$qid]));
					}
					//$page_assessmentQuestions[$x]['desc'][$y] = $descr[$qid];
					$page_assessmentQuestions[$x]['qsetid'][$y] = $qsetid[$qid];
					$page_assessmentQuestions[$x]['preview'][$y] = "<button type=\"button\" onClick=\"previewq('selq','qo$ln',".Sanitize::onlyFloat($qsetid[$qid]).",true)\"/>"._("Preview")."</button>";
					$page_assessmentQuestions[$x]['type'][$y] = $qtypes[$qid];
					$page_assessmentQuestions[$x]['times'][$y] = $qsetusecnts[$qsetid[$qid]];
					$page_assessmentQuestions[$x]['mine'][$y] = ($owner[$qid]==$userid) ? "Yes" : "" ;
					$page_assessmentQuestions[$x]['add'][$y] = "<a href=\"$modquestion.php?qsetid=".Sanitize::onlyFloat($qsetid[$qid])."&aid=$aid&cid=$cid\">Add</a>";
					$page_assessmentQuestions[$x]['src'][$y] = ($userights[$qid]>3 || ($userights[$qid]==3 && $qgroupid[$qid]==$groupid) || $owner[$qid]==$userid) ? "<a href=\"moddataset.php?id=".Sanitize::onlyFloat($qsetid[$qid])."&aid=$aid&cid=$cid&frompot=1\">Edit</a>" : "<a href=\"moddataset.php?id=".Sanitize::onlyFloat($qsetid[$qid])."&aid=$aid&cid=$cid\">"._("View Code")."</a>" ;
					$page_assessmentQuestions[$x]['templ'][$y] = "<a href=\"moddataset.php?id=".Sanitize::onlyFloat($qsetid[$qid])."&aid=$aid&cid=$cid&template=true\">"._("Template")."</a>";
					$page_assessmentQuestions[$x]['extref'][$y] = '';
					$page_assessmentQuestions[$x]['cap'][$y] = 0;
					if ($extref[$qid]!='') {
						$extrefarr = explode('~~',$extref[$qid]);
						$hasvid = false;  $hasother = false;
						foreach ($extrefarr as $v) {
							if (substr($v,0,5)=="Video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
								$hasvid = true;
								if (strpos($v,'!!1')!==false) {
									$page_assessmentQuestions[$x]['cap'][$y] = 1;
									$altcap = "Captioned ";
								} else {
									$altcap = '';
								}
							} else {
								$hasother = true;
							}
						}
						if ($hasvid) {
							$page_assessmentQuestions[$x]['extref'][$y] .= "<img src=\"$staticroot/img/video_tiny.png\" alt=\"{$altcap}Video\"/>";
						}
						if ($hasother) {
							$page_assessmentQuestions[$x]['extref'][$y] .= "<img src=\"$staticroot/img/html_tiny.png\" alt=\"Help Resource\"/>";
						}
					}

					$ln++;
					$y++;
				}
				$x++;
			}
		} else {  //choose assessments
			$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$cid));
			$items = unserialize($stm->fetchColumn(0));

			$itemassoc = array();
			$query = "SELECT ii.id AS itemid,ia.id,ia.name,ia.summary FROM imas_items AS ii JOIN imas_assessments AS ia ";
			$query .= "ON ii.typeid=ia.id AND ii.itemtype='Assessment' WHERE ii.courseid=:courseid AND ia.id<>:aid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid, ':aid'=>$aid));
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				$itemassoc[$row['itemid']] = $row;
			}

			$i=0;
			$page_assessmentList = array();
			function addtoassessmentlist($items) {
				global $page_assessmentList, $itemassoc, $i;
				foreach ($items as $item) {
					if (is_array($item)) {
						addtoassessmentlist($item['items']);
					} else if (isset($itemassoc[$item])) {
						$page_assessmentList[$i]['id'] = $itemassoc[$item]['id'];
						$page_assessmentList[$i]['name'] = $itemassoc[$item]['name'];
						$itemassoc[$item]['summary'] = strip_tags($itemassoc[$item]['summary']);
						if (strlen($itemassoc[$item]['summary'])>100) {
							$itemassoc[$item]['summary'] = substr($itemassoc[$item]['summary'],0,97).'...';
						}
						$page_assessmentList[$i]['summary'] = $itemassoc[$item]['summary'];
						$i++;
					}
				}
			}
			addtoassessmentlist($items);
		}
	}
}


/******* begin html output ********/
//hack to prevent the page breaking on accessible mode
$_SESSION['useed'] = 1;
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {

//var_dump($jsarr);
?>
	<script type="text/javascript">
		var curcid = <?php echo $cid ?>;
		var curaid = <?php echo $aid ?>;
		var defpoints = <?php echo (int) Sanitize::onlyInt($defpoints); ?>;
		var AHAHsaveurl = '<?php echo $GLOBALS['basesiteurl'] ?>/course/addquestionssave.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>';
		var curlibs = '<?php echo Sanitize::encodeStringForJavascript($searchlibs ?? ''); ?>';
	</script>
	<script type="text/javascript" src="<?php echo $staticroot ?>/javascript/tablesorter.js"></script>

	<div class="breadcrumb"><?php echo $curBreadcrumb ?></div>

	<div id="headeraddquestions" class="pagetitle"><h1><?php echo _('Add/Remove Questions'); ?>
		<img src="<?php echo $staticroot ?>/img/help.gif" alt="Help" onClick="window.open('<?php echo $imasroot ?>/help.php?section=addingquestionstoanassessment','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/>
    </h1></div>
    <div class="cp">
        <span class="column">
<?php
    echo '<a href="'.$addassess.'?id='.$aid.'&amp;cid='.$cid.'">'._('Assessment Settings').'</a>';
    echo '<br><a href="categorize.php?aid='.$aid.'&amp;cid='.$cid.'">'._('Categorize Questions').'</a>';
    echo '<br><a href="';
    if (isset($CFG['GEN']['pandocserver'])) {
        echo 'printlayoutword.php?cid='.$cid.'&aid='.$aid;
    } else {
        echo 'printlayoutbare.php?cid='.$cid.'&aid='.$aid;
    }
    echo '">'._('Create Print Version').'</a>';
    echo '</span><span class="column">';
    echo '<a href="assessendmsg.php?aid='.$aid.'&amp;cid='.$cid.'">'._('Define End Messages').'</a>';
    if ($aver > 1 && $submitby == 'by_assessment') {
        echo '<br><a href="autoexcuse.php?aid='.$aid.'&amp;cid='.$cid.'">'._('Define Auto-Excuse').'</a>';
    }
    if ($aver > 1) {
        echo '<br><a href="addquestions2.php?aid='.$aid.'&amp;cid='.$cid.'">'._('Try New Add/Remove (Beta)').'</a>';
    }
    echo '</span><br class=clear /></div>';
	if ($beentaken) {
?>
	<h2><?php echo _("Warning") ?></h2>
	<p><?php echo _("This assessment has already been taken.  Adding or removing questions, or changing a	question's settings (point value, penalty, attempts) now would majorly mess things up. If you want to make these changes, you need to clear all existing assessment attempts") ?>
	</p>
	<p><input type=button value="<?php echo _("Clear Assessment Attempts"); ?>" onclick="window.location='addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&clearattempts=ask'">
	</p>
<?php
	}
?>
	<h2><?php echo _('Questions in Assessment'),' - ',Sanitize::encodeStringForDisplay($page_assessmentName); ?></h2>

<?php
	if ($itemorder == '') {
		echo "<p>"._("No Questions currently in assessment")."</p>\n";

		echo '<a href="#" onclick="this.style.display=\'none\';document.getElementById(\'helpwithadding\').style.display=\'block\';return false;">';
		echo "<img src=\"$staticroot/img/help.gif\" alt=\"Help\"/> ";
		echo _('How do I find questions to add?'),'</a>';
		echo '<div id="helpwithadding" style="display:none">';
		if ($_SESSION['selfrom'.$aid]=='lib') {
			echo "<p>",_("You are currently set to select questions from the question libraries.  If you would like to select questions from assessments you've already created, click the <b>Select From Assessments</b> button below"),"</p>";
			echo "<p>",_("To find questions to add from the question libraries:");
			echo "<ol><li>",_("Click the <b>Select Libraries</b> button below to pop open the library selector"),"</li>";
			echo " <li>",_("In the library selector, open up the topics of interest, and click the checkbox to select libraries to use"),"</li>";
			echo " <li>",_("Scroll down in the library selector, and click the <b>Use Libraries</b> button"),"</li> ";
			echo " <li>",_("On this page, click the <b>Search</b> button to list the questions in the libraries selected.<br/>  You can limit the listing by entering a sepecific search term in the box provided first, or leave it blank to view all questions in the chosen libraries"),"</li>";
			echo "</ol>";
		} else if ($_SESSION['selfrom'.$aid]=='assm') {
			echo "<p>",_("You are currently set to select questions existing assessments.  If you would like to select questions from the question libraries, click the <b>Select From Libraries</b> button below"),"</p>";
			echo "<p>",_("To find questions to add from existing assessments:");
			echo "<ol><li>",_("Use the checkboxes to select the assessments you want to pull questions from"),"</li>";
			echo " <li>",_("Click <b>Use these Assessments</b> button to list the questions in the assessments selected"),"</li>";
			echo "</ol>";
		}
		echo "<p>",_("To select questions and add them:"),"</p><ul>";
		echo " <li>",_("Click the <b>Preview</b> button after the question description to view an example of the question"),"</li>";
		echo " <li>",_("Use the checkboxes to mark the questions you want to use"),"</li>";
		echo " <li>",_("Click the <b>Add</b> button above the question list to add the questions to your assessment"),"</li> ";
		echo "  </ul>";
		echo '</div>';

	} else {
?>
	<form id="curqform" method="post" action="addquestions.php?modqs=true&aid=<?php echo $aid ?>&cid=<?php echo $cid ?>">
<?php
		if (!$beentaken) {
			/*
			Use select boxes to
		<select name=group id=group>
			<option value="0"<?php echo $grp0Selected ?>>Rearrange questions</option>
			<option value="1"<?php echo $grp1Selected ?>>Group questions</option>
		</select>
		<br/>
		*/
?>

		<?php echo _('Check:') ?> <a href="#" onclick="return chkAllNone('curqform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('curqform','checked[]',false)"><?php echo _('None') ?></a>

		<?php echo _('With Selected:') ?> <button type="button" onclick="removeSelected()"><?php echo _('Remove'); ?></button>
				<button type="button" onclick="groupSelected()" ><?php echo _('Group'); ?></button>
				<button type="submit" onclick="return confirm_textseg_dirty()"><?php echo _("Change Settings"); ?></button>

<?php
		}
?>
		<span id="submitnotice" class=noticetext></span>
		<div id="curqtbl"></div>

	</form>
	<p><?php echo _('Assessment points total:') ?> <span id="pttotal"></span></p>
	<?php if (isset($introconvertmsg)) {echo $introconvertmsg;}?>
	<script>
		var itemarray = <?php echo json_encode($jsarr, JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_INVALID_UTF8_IGNORE); ?>;
		var beentaken = <?php echo ($beentaken) ? 1:0; ?>;
        var displaymethod = "<?php echo Sanitize::encodeStringForDisplay($displaymethod); ?>";
        var lastitemhash = "<?php echo md5($itemorder); ?>";
		//$(refreshTable);
		refreshTable();
	</script>
<?php
	}
	if ($displaymethod=='VideoCue' || $displaymethod == 'video_cued') {
		echo '<p><input type=button value="'._('Define Video Cues').'" onClick="window.location=\'addvideotimes.php?cid='.$cid.'&aid='.$aid.'\'"/></p>';
	} else if ($displaymethod == 'full') {
		echo '<p>'._('You can break your assessment into pages by using the +Text button and selecting the New Page option.').'</p>';
	}
?>
	<p>
		<a class="abutton" href="course.php?cid=<?php echo $cid ?>" onclick="return prePageChange()"><?php echo _("Done"); ?></a>
		<button type="button" title=<?php echo '"'._("Preview this assessment").'"'; ?> onClick="window.open('<?php
			if ($aver > 1) {
				echo $imasroot . '/assess2/?cid=' . $cid . '&aid=' . $aid;
			} else {
				echo $imasroot . '/assessment/showtest.php?cid=' . $cid . '&id=' . $aid;
			}
		?>','Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20))"><?php echo _("Preview"); ?></button>
	</p>

<?php
//<input type=button value="Select Libraries" onClick="libselect()">

	//POTENTIAL QUESTIONS
	if ($_SESSION['selfrom'.$aid]=='lib') { //selecting from libraries
		if (!$beentaken) {
?>

	<h2><?php echo _('Potential Questions') ?></h2>
	<form method=post action="addquestions.php?aid=<?php echo $aid ?>&cid=<?php echo $cid ?>">

		<?php echo _('In Libraries'); ?>:
		<span id="libnames"><?php echo Sanitize::encodeStringForDisplay($lnames); ?></span>
		<input type=hidden name="libs" id="libs"  value="<?php echo Sanitize::encodeStringForDisplay($searchlibs); ?>">
		<button type="button" onClick="GB_show('Library Select','libtree2.php?libtree=popup&libs='+curlibs,500,500)" ><?php echo _("Select Libraries"); ?></button>
		<?php echo _("or"); ?> <button type="button" onClick="window.location='addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&selfrom=assm'"><?php echo _("Select From Assessments"); ?></button>
		<br>
		<?php echo _('Search') ?>:
		<input type=text size=15 name=search value="<?php echo $search ?>">
		<span tabindex="0" data-tip="Search all libraries, not just selected ones" onmouseenter="tipshow(this)" onfocus="tipshow(this)" onmouseleave="tipout()" onblur="tipout()">
		<input type=checkbox name="searchall" value="1" <?php writeHtmlChecked($searchall,1,0) ?> />
		<?php echo _('Search all libs'); ?></span>
		<span tabindex="0" data-tip="List only questions I own" onmouseenter="tipshow(this)" onfocus="tipshow(this)" onmouseleave="tipout()" onblur="tipout()">
		<input type=checkbox name="searchmine" value="1" <?php writeHtmlChecked($searchmine,1,0) ?> />
		<?php echo _('Mine only'); ?></span>
		<span tabindex="0" data-tip="Exclude questions already in assessment" onmouseenter="tipshow(this)" onfocus="tipshow(this)" onmouseleave="tipout()" onblur="tipout()">
		<input type=checkbox name="newonly" value="1" <?php writeHtmlChecked($newonly,1,0) ?> />
		<?php echo _('Exclude added'); ?></span>
		<button type="submit"><?php echo _("Search"); ?></button>
		<button type="button" onclick="window.location='moddataset.php?aid=<?php echo $aid ?>&cid=<?php echo $cid ?>'"><?php echo _("Add New Question") ?></button>

	</form>
<?php
			if ($searchall==1 && trim($search)=='' && $searchmine==0) {
				echo _("Must provide a search term when searching all libraries");
			} elseif (isset($search)) {
				if (!empty($noSearchResults)) {
					echo "<p>",_("No Questions matched search"),"</p>\n";
				} else {
?>
		<form id="selq" method=post action="addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&addset=true">

		<?php echo _('Check:'); ?> <a href="#" onclick="return chkAllNone('selq','nchecked[]',true)"><?php echo _('All'); ?></a> <a href="#" onclick="return chkAllNone('selq','nchecked[]',false)"><?php echo _('None'); ?></a>
		<button name="add" type=submit ><?php echo _("Add") ?></button>
		<button name="addquick" type=submit ><?php echo _("Add (using defaults)") ?></button>
		<button type="button" onclick="previewsel('selq')" ><?php echo _("Preview Selected" ); ?></button>

		<table cellpadding="5" id="myTable" class="gb" style="clear:both; position:relative;">
			<thead>
				<tr><th>&nbsp;</th><th><?php echo _('Description'); ?></th><th>&nbsp;</th><th>ID</th><th><?php echo _('Preview'); ?></th><th><?php echo _('Type'); ?></th>
					<?php echo $page_libRowHeader ?>
					<th><?php echo _('Times Used'); ?></th>
					<?php if ($page_useavgtimes) {?><th><span onmouseenter=<?php echo "\"tipshow(this,'". _('Average time, in minutes, this question has taken students')."')\""; ?> onmouseleave="tipout()"><?php echo _('Avg Time'); ?></span></th><?php } ?>
					<th><?php echo _('Mine'); ?></th><th><?php echo _('Actions'); ?></th>
					<?php if ($searchall==0) { echo '<th><span onmouseenter="tipshow(this,\''._('Flag a question if it is in the wrong library').'\')" onmouseleave="tipout()">'._('Wrong Lib').'</span></th>';} ?>
				</tr>
			</thead>
			<tbody>
<?php
				$alt=0;
				for ($j=0; $j<count($page_libstouse); $j++) {

					if ($searchall==0) {
						if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
						echo '<td></td>';
						echo '<td>';
						echo '<b>' . Sanitize::encodeStringForDisplay($lnamesarr[$page_libstouse[$j]]) . '</b>';
						echo '</td>';
						for ($k=0;$k<9;$k++) {echo '<td></td>';}
						echo '</tr>';
					}

					for ($i=0;$i<count($page_libqids[$page_libstouse[$j]]); $i++) {
						$qid =$page_libqids[$page_libstouse[$j]][$i];
						if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>

					<td><?php echo $page_questionTable[$qid]['checkbox']; ?></td>
					<td><?php echo $page_questionTable[$qid]['desc']; ?></td>
					<td class="nowrap">
					   <div <?php if ($page_questionTable[$qid]['cap']) {echo 'class="ccvid"';}?>><?php echo $page_questionTable[$qid]['extref'] ?></div>
					</td>
					<td><?php echo Sanitize::encodeStringForDisplay($qid); ?></td>
					<td><?php echo $page_questionTable[$qid]['preview']; ?></td>
					<td><?php echo Sanitize::encodeStringForDisplay($page_questionTable[$qid]['type']); ?></td>
<?php
						if ($searchall==1) {
?>
					<td><?php echo $page_questionTable[$qid]['lib'] ?></td>
<?php
						}
?>
					<td class=c><?php
					echo Sanitize::encodeStringForDisplay($page_questionTable[$qid]['times']); ?>
					</td>
					<?php if ($page_useavgtimes) {?><td class="c"><?php
					if (isset($page_questionTable[$qid]['qdata'])) {
						echo '<span onmouseenter="tipshow(this,\''._('Avg score on first try: ').round($page_questionTable[$qid]['qdata'][0]).'%';
						echo '<br/>'._('Avg time on first try: ').round($page_questionTable[$qid]['qdata'][1]/60,1).' min<br/>N='.$page_questionTable[$qid]['qdata'][2].'\')" onmouseleave="tipout()">';
					} else {
						echo '<span>';
					}
					echo $page_questionTable[$qid]['meantime'].'</span>'; ?></td> <?php }?>
					<td><?php echo $page_questionTable[$qid]['mine'] ?></td>
					<td><div class="dropdown">
					  <a role="button" tabindex=0 class="dropdown-toggle arrow-down" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					    <?php echo _('Action'); ?></a>
					  <ul role="menu" class="dropdown-menu dropdown-menu-right">
					   <li><?php echo $page_questionTable[$qid]['add']; ?></li>
					   <li><?php echo $page_questionTable[$qid]['src']; ?></li>
					   <li><?php echo $page_questionTable[$qid]['templ']; ?></li>
					  </ul>
					</td>
					<?php if ($searchall==0) {
						if ($page_questionTable[$qid]['junkflag']==1) {
							echo "<td class=c><img class=\"pointer\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$staticroot/img/flagfilled.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" alt=\"Flagged\" /></td>";
						} else {
							echo "<td class=c><img class=\"pointer\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$staticroot/img/flagempty.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" alt=\"Not flagged\" /></td>";
						}
					} ?>
				</tr>
<?php
					}
				}
				if ($searchall==1 && ($searchlimited || $offset>0)) {
					echo '<tr><td></td><td><i>'._('Search cut off at 300 results');
					echo '<br>'._('Showing ').($offset+1).'-'.($offset + 300).'. ';
					if ($offset>0) {
						$prevoffset = max($offset-300, 0);
						echo "<a href=\"addquestions.php?cid=$cid&aid=$aid&offset=$prevoffset\" onclick=\"return prePageChange()\">"._('Previous').'</a> ';
					}
					if ($searchlimited) {
						$nextoffset = $offset+300;
						echo "<a href=\"addquestions.php?cid=$cid&aid=$aid&offset=$nextoffset\" onclick=\"return prePageChange()\">"._('Next').'</a> ';
					}
					echo '</i></td></tr>';
				}
?>
			</tbody>
		</table>
		<p><?php echo sprintf(_('Questions %s in gray %s have been added to the assessment.'),'<span style="color:#999">','</span>'); ?></p>
		<script type="text/javascript">
			initSortTable('myTable',[false,'S','S','N',false,'S',<?php echo ($searchall==1) ? "false, " : ""; ?>'N','N','S',false<?php echo ($searchall==0) ? ",false" : ""; ?>],true);
		    $(".dropdown-toggle").dropdown();
		</script>
	</form>

<?php
				}
			}
		}

	} else if ($_SESSION['selfrom'.$aid]=='assm') { //select from assessments
?>

	<h2><?php echo _('Potential Questions'); ?></h2>

<?php
		if (isset($_POST['achecked']) && (count($_POST['achecked'])==0)) {
			echo "<p>",_("No Assessments Selected.  Select at least one assessment."),"</p>";
		} elseif (isset($_SESSION['aidstolist'.$aid])) { //list questions
?>
	<form id="selq" method=post action="addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&addset=true">

		<button type="button" onClick="window.location='addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&clearassmt=1'"><?php echo _("Select Assessments"); ?></button>
		<?php echo _("or") ?> <button type="button" onClick="window.location='addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&selfrom=lib'"><?php echo _("Select From Libraries"); ?></button>
		<br/>

		<?php echo _('Check'); ?>: <a href="#" onclick="return chkAllNone('selq','nchecked[]',true)"><?php echo _('All'); ?></a> <a href="#" onclick="return chkAllNone('selq','nchecked[]',false)"><?php echo _('None'); ?></a>
		<button name="add" type="submit" /><?php echo _("Add") ?></button>
		<button name="addquick" type="submit" ><?php echo _("Add Selected (using defaults)"); ?></button>
		<button type="button" onclick="previewsel('selq')" ><?php echo _("Preview Selected") ?></button>

		<table cellpadding=5 id=myTable class=gb>
			<thead>
				<tr>
					<th> </th><th><?php echo _('Description'); ?></th><th></th><th>ID</td><th><?php echo _('Preview'); ?></th><th><?php echo _('Type'); ?></th><th><?php echo _('Times Used'); ?></th><th><?php echo _('Mine'); ?></th><th><?php echo _('Add'); ?></th><th><?php echo _('Source'); ?></th><th><?php echo _('Use as Template'); ?></th>
				</tr>
			</thead>
			<tbody>
<?php
			$alt=0;
			for ($i=0; $i<count($page_assessmentQuestions['aiddesc']);$i++) {
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>
				<td></td>
				<td><b><?php echo Sanitize::encodeStringForDisplay($page_assessmentQuestions['aiddesc'][$i]); ?></b></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
<?php
				for ($x=0;$x<count($page_assessmentQuestions[$i]['desc']);$x++) {
					if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>
				<td><?php echo $page_assessmentQuestions[$i]['checkbox'][$x]; ?></td>
				<td><?php echo $page_assessmentQuestions[$i]['desc'][$x]; ?></td>
				<td class="nowrap">
				  <div <?php if ($page_assessmentQuestions[$i]['cap'][$x]) {echo 'class="ccvid"';}?>><?php echo $page_assessmentQuestions[$i]['extref'][$x]; ?></div>
				</td>
				<td><?php echo Sanitize::onlyInt($page_assessmentQuestions[$i]['qsetid'][$x]); ?></td>
				<td><?php echo $page_assessmentQuestions[$i]['preview'][$x]; ?></td>
				<td><?php echo Sanitize::encodeStringForDisplay($page_assessmentQuestions[$i]['type'][$x]); ?></td>
				<td class=c><?php echo Sanitize::onlyInt($page_assessmentQuestions[$i]['times'][$x]); ?></td>
				<td><?php echo $page_assessmentQuestions[$i]['mine'][$x]; ?></td>
				<td class=c><?php echo $page_assessmentQuestions[$i]['add'][$x]; ?></td>
				<td class=c><?php echo $page_assessmentQuestions[$i]['src'][$x]; ?></td>
				<td class=c><?php echo $page_assessmentQuestions[$i]['templ'][$x]; ?></td>
			</tr>

<?php
				}
			}
?>
			</tbody>
		</table>

		<script type="text/javascript">
			initSortTable('myTable',Array(false,'S','N',false,'S','N','S',false,false,false),true);
		</script>
		</form>

<?php
		} else {  //choose assessments
?>
		<h3><?php echo _('Choose assessments to take questions from'); ?></h3>
		<form id="sela" method=post action="addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>">
		<?php echo _('Check'); ?>: <a href="#" onclick="return chkAllNone('sela','achecked[]',true)"><?php echo _('All'); ?></a> <a href="#" onclick="return chkAllNone('sela','achecked[]',false)"><?php echo _('None'); ?></a>
		<input type=submit value="<?php echo _("Use these Assessments") ?>" /> <?php echo _('or'); ?>
		<input type=button value="<?php echo _("Select From Libraries") ?>" onClick="window.location='addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&selfrom=lib'">

		<table cellpadding=5 id=myTable class=gb>
			<thead>
			<tr><th></th><th><?php echo _('Assessment'); ?></th><th><?php echo _('Summary'); ?></th></tr>
			</thead>
			<tbody>
<?php

			$alt=0;
			for ($i=0;$i<count($page_assessmentList);$i++) {
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>

				<td><input type=checkbox name='achecked[]' value='<?php echo $page_assessmentList[$i]['id'] ?>'></td>
				<td><?php echo $page_assessmentList[$i]['name'] ?></td>
				<td><?php echo $page_assessmentList[$i]['summary'] ?></td>
			</tr>
<?php
			}
?>

			</tbody>
		</table>
		<script type="text/javascript">
			initSortTable('myTable',Array(false,'S','S',false,false,false),true);
		</script>
	</form>

<?php
		}

	}

}

require("../footer.php");
?>
