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

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=" . Sanitize::courseId($_GET['cid']) . "\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
if (isset($_GET['clearattempts']) || isset($_GET['clearqattempts']) || isset($_GET['withdraw'])) {
	$curBreadcrumb .= "&gt; <a href=\"addquestions2.php?cid=" . Sanitize::courseId($_GET['cid']) . "&aid=" . Sanitize::onlyInt($_GET['aid']) . "\">"._("Add/Remove Questions")."</a> &gt; Confirm\n";
	//$pagetitle = "Modify Inline Text";
} else {
	$curBreadcrumb .= "&gt; "._("Add/Remove Questions")."\n";
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
	$stm = $DBH->prepare("SELECT courseid,ver,submitby,defpoints,name,intro,showhints,showwork,itemorder,displaymethod FROM imas_assessments WHERE id=?");
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
    $defpoints = $row['defpoints'];
    $assessmentname = $row['name'];
    $displaymethod = $row['displaymethod'];
    $itemorder = $row['itemorder'];
    $row['showwork'] = ($row['showwork'] & 3);

	if (isset($_GET['grp'])) { $_SESSION['groupopt'.$aid] = Sanitize::onlyInt($_GET['grp']);}
	if (isset($_GET['selfrom'])) {
		$_SESSION['selfrom'.$aid] = Sanitize::stripHtmlTags($_GET['selfrom']);
	} else {
		if (!isset($_SESSION['selfrom'.$aid])) {
			$_SESSION['selfrom'.$aid] = 'lib';
		}
	}

	if (isset($teacherid) && isset($_GET['addset'])) {
		if (!isset($_POST['nchecked']) && !isset($_POST['qsetids'])) {
			$overwriteBody = 1;
			$body = _("No questions selected").".  <a href=\"addquestions2.php?cid=$cid&aid=$aid\">"._("Go back")."</a>\n";
		} else if (isset($_POST['add'])) {
			if ($aver > 1) {
				include("modquestiongrid2.php");
			} else {
				include("modquestiongrid.php");
			}
			if (isset($_GET['process'])) {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions2.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
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

			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions2.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
			exit;
		}
	}
	if (isset($_GET['modqs'])) {
		if (!isset($_POST['checked']) && !isset($_POST['qids'])) {
			$overwriteBody = 1;
			$body = _("No questions selected").".  <a href=\"addquestions2.php?cid=$cid&aid=$aid\">"._("Go back")."</a>\n";
		} else {
			if ($aver > 1) {
				include("modquestiongrid2.php");
			} else {
				include("modquestiongrid.php");
			}
			if (isset($_GET['process'])) {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions2.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
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
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions2.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
			exit;
		} else {
			$overwriteBody = 1;
			$body = "<div class=breadcrumb>$curBreadcrumb</div>\n";
			$body .= "<h2>".Sanitize::encodeStringForDisplay($assessmentname)."</h2>";
			$body .= "<p>"._("Are you SURE you want to delete all attempts (grades) for this assessment?")."</p>";
			$body .= '<form method="POST" action="'.sprintf('addquestions2.php?cid=%s&aid=%d',$cid, $aid).'">';
			$body .= '<p><button type=submit name=clearattempts value=confirmed>'._('Yes, Clear').'</button>';
			$body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='addquestions2.php?cid=$cid&aid=$aid';\"></p>\n";
			$body .= '</form>';
		}
	}
	
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
				$assess_info->loadQuestionSettings();
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
						calcandupdateLTIgrade($row['lti_sourcedid'], $aid, $row['userid'], $updatedScore, true, -1, false);
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
						calcandupdateLTIgrade($row['lti_sourcedid'], $aid, $row['userid'], $bestscores, true, -1, false);
					}
				}
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions2.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
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
			$body .= "<form method=post action=\"addquestions2.php?cid=$cid&aid=$aid&withdraw=".Sanitize::encodeStringForDisplay($_GET['withdraw'])."\">";
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
			$body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='addquestions2.php?cid=$cid&aid=$aid'\"></p>\n";

			$body .= '</form>';
		}

	}

	$address = $GLOBALS['basesiteurl'] . "/course/$addassess?cid=$cid&aid=$aid";
	$testqpage = ($courseUIver>1) ? 'testquestion2.php' : 'testquestion.php';
	$placeinhead = "<script type=\"text/javascript\">
        var previewqaddr = '$imasroot/course/$testqpage?cid=$cid';
        var qsearchaddr = '$imasroot/course/qsearch.php?cid=$cid&aid=$aid';
        var aselectaddr = '$imasroot/course/assessselect.php?cid=$cid&aid=$aid';
        var qsettingsaddr = '$imasroot/course/embedmodquestiongrid2.php?cid=$cid&aid=$aid';
		var addqaddr = '$address';
        var assessver = '$aver';
		</script>";
    $placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/addqsort2.js?v=062522\"></script>";
    $placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/qsearch.js?v=041422\"></script>";
    $placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/junkflag.js\"></script>";
    $placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js?v=080818\"></script>";
	$placeinhead .= "<script type=\"text/javascript\">var JunkFlagsaveurl = '". $GLOBALS['basesiteurl'] . "/course/savelibassignflag.php';</script>";
    $placeinhead .= "<link rel=\"stylesheet\" href=\"$staticroot/course/addquestions2.css?v=120121\" type=\"text/css\" />";
    $placeinhead .= '<script>
        $(function() {
            if (window.top != window.self) {
               $("#addbar").removeClass("footerbar").removeClass("sr-only"); 
            } 
        });
        </script>';
	$loadiconfont = true;
	$useeditor = "noinit";

	//DEFAULT LOAD PROCESSING GOES HERE
	//load filter.  Need earlier than usual header.php load
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require_once("$curdir/../filter/filter.php");
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
    
    require_once('../includes/addquestions2util.php');
    list($jsarr, $existingq, $introconvertmsg) = getQuestionsAsJSON($cid, $aid, $row);

    if (isset($_SESSION['searchtype'.$aid])) {
        $searchtype = $_SESSION['searchtype'.$aid];
    } else {
        $searchtype = 'libs';
    }

    if (isset($_SESSION['searchin'.$aid])) {
        $searchin = $_SESSION['searchin'.$aid];
    } else if ($searchtype == 'libs') {
        if (isset($CFG['AMS']['guesslib']) && count($existingq)>0) {
            $maj = count($existingq)/2;
            $existingqlist = implode(',', $existingq);  //pulled from database, so no quotes needed
            $stm = $DBH->query("SELECT libid,COUNT(qsetid) FROM imas_library_items WHERE qsetid IN ($existingqlist) AND deleted=0 GROUP BY libid");
            $foundmaj = false;
            while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                if ($row[1]>=$maj) {
                    $searchin = [$row[0]];
                    $foundmaj = true;
                    break;
                }
            }
            if (!$foundmaj) {
                //echo "No maj found";
                $searchin = [$userdeflib];
            }
        } else {
            $searchin = [$userdeflib];
        }
    } else {
        $searchin = [];
    }

    if (isset($_SESSION['lastsearch'.$aid])) {
        $searchterms = $_SESSION['lastsearch'.$aid];
    } else {
        $searchterms = '';
    }

    // do initial search
    require('../includes/questionsearch.php');
    $search_parsed = parseSearchString($searchterms);
    $search_results = searchQuestions($search_parsed, $userid, $searchtype, $searchin, [
        'existing' => $existingq
    ]);
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
        var curlibs = '<?php echo Sanitize::encodeStringForJavascript(implode(',',$searchin)); ?>';
        var cursearchtype = '<?php echo Sanitize::simpleString($searchtype); ?>';
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
    echo '<br><a href="categorize.php?aid='.$aid.'&amp;cid='.$cid.'&from=addq2">'._('Categorize Questions').'</a>';
    echo '<br><a href="';
    if (isset($CFG['GEN']['pandocserver'])) {
        echo 'printlayoutword.php?cid='.$cid.'&aid='.$aid;
    } else {
        echo 'printlayoutbare.php?cid='.$cid.'&aid='.$aid;
    }
    echo '&from=addq2">'._('Create Print Version').'</a>';
    echo '</span><span class="column">';
    echo '<a href="assessendmsg.php?aid='.$aid.'&amp;cid='.$cid.'&from=addq2">'._('Define End Messages').'</a>';
    if ($aver > 1 && $submitby == 'by_assessment') {
        echo '<br><a href="autoexcuse.php?aid='.$aid.'&amp;cid='.$cid.'&from=addq2">'._('Define Auto-Excuse').'</a>';
    }
    echo '<br><a href="findquestion.php?aid='.$aid.'&amp;cid='.$cid.'&amp;from=addq2">'._('Find Question in Course').'</a>';
    echo '<br><a href="addquestions.php?aid='.$aid.'&amp;cid='.$cid.'">'._('Use Classic Add/Remove').'</a>';

    echo '</span><br class=clear /></div>';
	if ($beentaken) {
?>
	<h2><?php echo _("Warning") ?></h2>
	<p><?php echo _("This assessment has already been taken.  Adding or removing questions, or changing a	question's settings (point value, penalty, attempts) now would majorly mess things up. If you want to make these changes, you need to clear all existing assessment attempts") ?>
	</p>
	<p><input type=button value="Clear Assessment Attempts" onclick="window.location='addquestions2.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&clearattempts=ask'">
	<a href="isolateassessgrade.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>" target="_blank">
		<?php echo _('View Scores');?>
	</a>
	</p>
<?php
	}
?>
	<h2><?php echo _('Questions in Assessment'),' - ',Sanitize::encodeStringForDisplay($assessmentname); ?></h2>

<?php
    echo '<div id="noqs"'. (count($jsarr)==0?'':' style="display:none;"') . '>';
    echo "<p>"._("No Questions currently in assessment")."</p>\n";

    echo '<a href="#" onclick="this.style.display=\'none\';document.getElementById(\'helpwithadding\').style.display=\'block\';return false;">';
    echo "<img src=\"$staticroot/img/help.gif\" alt=\"Help\"/> ";
    echo _('How do I find questions to add?'),'</a>';
    echo '<div id="helpwithadding" style="display:none">';
    echo '<p>',_('Under Potential Questions below, use the selector on the left to decide whether you want to select specific libraries, search all libraries, or select assessments to choose from.'),'</p>';
    
    echo '<ul><li>',_('If you choose <b>Select Libraries</b>, in the library selector, open up the topics of interest, and click the checkboxes to select libraries to use. ');
    echo _('Click the <b>Use Libraries</b> button to list the contents of those libraries. ');
    echo _('You can also use the search bar to search within the selected libraries'),'</li>';

    echo '<li>',_('If you choose <b>Select Assessments</b>, in the assessment selector, click the checkboxes to select the assessments to use. ');
    echo _('Click the <b>Use Assessments</b> button to list the contents of those assessments. ');
    echo _('You can also use the search bar to search within the selected assessments'),'</li>';

    echo '<li>',_('If you choose <b>All Libraries</b>, you must provide a search term then click Search. '),'</li></ul>';

    echo "<p>",_("To select questions and add them:"),"</p><ul>";
    echo " <li>",_("Click the eye-shaped <b>Preview</b> icon to view an example of the question"),"</li>";
    echo " <li>",_("Use the checkboxes to mark the questions you want to use"),"</li>";
    echo " <li>",_("Click the <b>Add</b> button to add the questions to your assessment"),"</li> ";
    echo "  </ul>";
    echo '</div>';
    echo '</div>';
        
?>
    <form id="curqform" method="post" action="addquestions2.php?modqs=true&aid=<?php echo $aid ?>&cid=<?php echo $cid ?>"
      <?php if (count($jsarr)==0) echo ' style="display:none;"'; ?>
    >
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
            <button type="button" onclick="if (confirm_textseg_dirty()) { modsettings();}"><?php echo _("Change Settings"); ?></button>

<?php
		}
?>
		<span id="submitnotice" class=noticetext></span>
		<div id="curqtbl"></div>

	</form>
	<p><?php echo _('Assessment points total:') ?> <span id="pttotal"></span></p>
	<?php if (!empty($introconvertmsg)) {echo $introconvertmsg;}?>
	<script>
		var itemarray = <?php echo json_encode($jsarr, JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_INVALID_UTF8_IGNORE); ?>;
		var beentaken = <?php echo ($beentaken) ? 1:0; ?>;
        var displaymethod = "<?php echo Sanitize::encodeStringForDisplay($displaymethod); ?>";
        var lastitemhash = "<?php echo md5($itemorder); ?>";
		//$(refreshTable);
		refreshTable();
	</script>
<?php
    
	if ($displaymethod=='VideoCue' || $displaymethod == 'video_cued') {
		echo '<p><input type=button value="'._('Define Video Cues').'" onClick="window.location=\'addvideotimes.php?cid='.$cid.'&from=addq2&aid='.$aid.'\'"/></p>';
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
        <?php if (!$beentaken) { ?>
        <a href="moddataset.php?aid=<?php echo $aid ?>&cid=<?php echo $cid ?>&from=addq2"><?php echo _("Add New Question") ?></a>
        <?php } ?>

	</p>

<?php
	if (!$beentaken) {
?>

<h2><?php echo _('Potential Questions') ?></h2>

<div id="fullqsearchwrap">
    <div id="searcherror" class="noticetext"></div>
<div id="qsearchbarswrap">
<div class="flexrow wrap dropdown searchbar">
    <div class="dropdown">
        <button id="cursearchtype" type="button" class="dropdown-toggle arrow-down" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?php
            if ($searchtype == 'all') {
                echo _('All Libraries');
            } else if ($searchtype == 'libs') {
                echo _('In Libraries');
            } else if ($searchtype == 'assess') {
                echo _('In Assessments');
            }
            ?>
        </button>
        <ul class="dropdown-menu" id="searchtypemenu">
            <li><a href="#" role="button" onclick="alllibs(); return false;">
                <?php echo _('All Libraries'); ?>
            </a></li>
            <li><a href="#" role="button" onclick="libselect(); return false;">
                <?php echo _('Select Libraries...'); ?>
            </a></li>
            <li><a href="#" role="button" onclick="assessselect(); return false;">
                <?php echo _('Select Assessments...'); ?>
            </a></li>
        </ul>
    </div>
    <div style="flex-grow:1" class="flexrow">
        <div id="searchwrap" <?php if ($searchterms !== '') { echo 'class="hastext"';} ?>>
            <input type=text name=search id=search  
                value="<?php echo Sanitize::encodeStringForDisplay($searchterms); ?>">
            <button type=button onclick="clearSearch()" 
                id="searchclear" aria-label="Clear Search">&times;</button>
        </div>
        <div class="dropdown splitbtn" id="searchbtngrp" >
            <button type="button" class="primary" onclick="startQuestionSearch()">
                <?php echo _('Search');?>
            </button><button type="button" id="advsearchbtn" class="primary dropdown-toggle arrow-down" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="sr-only"><?php echo _('Advanced Search'); ?></span>
            </button>

            <div class="dropdown-menu dropdown-menu-right advsearch">
                <form class="mform" id="advsearchform">
                    <div><label for="search-words"><?php echo _('Has words');?>:</label>
                        <input id="search-words"/></div>
                    <div><label for="search-exclude"><?php echo _('Doesn\'t have');?>:</label> 
                        <input id="search-exclude"/></div>
                    <div><label for="search-author"><?php echo _('Author');?>:</label> 
                        <input id="search-author"/></div>
                    <div><label for="search-id"><?php echo _('ID');?>:</label> 
                        <input id="search-id"></div>
                    <div><label for="search-type"><?php echo _('Type');?>:</label> 
                        <select id="search-type">
                            <option value=""><?php echo _('All');?></option>
                            <option value="number">Number</option>
                            <option value="calculated">Calculated Number</option>
                            <option value="choices">Multiple-Choice</option>
                            <option value="multans">Multiple-Answer</option>
                            <option value="matching">Matching</option>
                            <option value="numfunc">Function</option>
                            <option value="string">String</option>
                            <option value="essay">Essay</option>
                            <option value="draw">Drawing</option>
                            <option value="ntuple">N-Tuple</option>
                            <option value="calcntuple">Calculated N-Tuple</option>
                            <option value="matrix">Numerical Matrix</option>
                            <option value="calcmatrix">Calculated Matrix</option>
                            <option value="interval">Interval</option>
                            <option value="calcinterval">Calculated Interval</option>
                            <option value="complex">Complex</option>
                            <option value="calccomplex">Calculated Complex</option>
                            <option value="file">File Upload</option>
                            <option value="multipart">Multipart</option>
                            <option value="conditional">Conditional</option>
                        </select></div>
                    <div><label for="search-avgtime-min"><?php echo _('Avg Time');?>:</label> <div>
                        <input size=2 id="search-avgtime-min"> to <input size=2 id="search-avgtime-max">
                    </div></div>
                    <div><label for="search-avgscore-min"><?php echo _('Avg Score');?>:</label> <div>
                        <input size=2 id="search-avgscore-min">% to <input size=2 id="search-avgscore-max">%
                    </div></div>
                    <div><label for="search-lastmod-min"><?php echo _('Last Modified');?>:</label> <div>
                        <input size=8 id="search-lastmod-min" name="search-lastmod-min">
                        <a href="#" onClick="displayDatePicker('search-lastmod-min', this); return false">
			            <img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
                        to 
                        <input size=8 id="search-lastmod-max" name="search-lastmod-max">
                        <a href="#" onClick="displayDatePicker('search-lastmod-max', this); return false">
			            <img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
                    </div></div>
                    <p><label><input type=checkbox id="search-mine"><?php echo _('Mine Only');?></label> 
                        <label><input type=checkbox id="search-unused"><?php echo _('Exclude Added');?></label> 
                        <label><input type=checkbox id="search-newest"><?php echo _('Newest First');?></label> 
                    </p>
                    <p><?php echo _('Helps');?>: 
                        <label><input type=checkbox id="search-res-help" value="help"><?php echo _('Resource');?></label> 
                        <label><input type=checkbox id="search-res-cap" value="cap"><?php echo _('Captioned Video');?></label> 
                        <label><input type=checkbox id="search-res-WE" value="WE"><?php echo _('Written Example');?></label> 
                        <label><input type=checkbox id="search-res-soln" value="soln"><?php echo _('Detailed Solution');?></label>
                    </p>
                    <div>
                        <div style="flex-grow:1">
                        </div>
                        <button type="button" class="primary" onclick="doAdvSearch()">
                            <?php echo _('Search');?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="selectedlibs short" <?php if ($searchtype=='all') { echo 'style="display:none;"';}?>>
    <span id="libnames">
        <?php echo Sanitize::encodeStringForDisplay(implode(', ', $search_results['names'])); ?>
    </span>
    <button class="viewall" aria-hidden="true" onclick="this.style.display='none';this.parentNode.classList.remove('short');">
        <?php echo _('View all');?>
    </button>
</div>
</div>

<div id="searchspinner" style="display:none;"><?php echo _('Searching');?>...<br/><img src="../img/updating.gif"/></div>

<div id="addbar" class="footerbar sr-only">
    <div class="dropup inlinediv splitbtn">
        <button type="button" class="primary" onclick="addusingdefaults(false)">
            <?php echo _('Add'); ?>
        </button><button type="button" class="primary dropdown-toggle arrow-up" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="sr-only"><?php echo _('Options for adding'); ?></span>
        </button>
        <ul class="dropdown-menu">
            <li><a href="#" role="button" onclick="addusingdefaults(false); return false;">
                <?php echo _('Add using defaults'); ?>
            </a></li>
            <li><a href="#" role="button" onclick="addusingdefaults(true); return false;">
                <?php echo _('Add as group using defaults'); ?>
            </a></li>
            <li><a href="#" role="button" onclick="addwithsettings(); return false;">
                <?php echo _('Add and set options'); ?>
            </a></li>
        </ul>
    </div>
    <button type="button" class="secondary" onclick="previewsel('selq')">
        <?php echo _('Preview Selected'); ?>
    </button>
    <button type="button" class="plain" onclick="chkAllNone('selq','nchecked[]',false);$('#addbar.footerbar').addClass('sr-only');">
        <?php echo _('Clear Selection'); ?>
    </button>
    <button type="button" class="plain" onclick="return chkAllNone('selq','nchecked[]',true)">
        <?php echo _('Select All'); ?>
    </button>
    <div class="dropup inlinediv">
        <button type="button" class="plain dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        ⋮
        </button>
        <ul class="dropdown-menu">
            <li><a href="#" role="button" onclick="toggleWrongLibFlags(1); return false;">
                <?php echo _('Mark selected as in wrong library'); ?>
            </a></li>
            <li><a href="#" role="button" onclick="toggleWrongLibFlags(0); return false;">
                <?php echo _('Un-mark selected as in wrong library'); ?>
            </a></li>
        </ul>
    </div>
</div>
        
<form id="selq">
	<table cellpadding="5" id="myTable" class="gb zebra potential-question-list" style="clear:both; position:relative;" tabindex="-1">
    </table>
    <p><span id="searchnums"><?php echo _('Showing');?> <span id="searchnumvals"></span></span>
      <a href="#" id="searchprev" style="display:none"><?php echo _('Previous Results');?></a>
      <a href="#" id="searchnext" style="display:none"><?php echo _('More Results');?></a>
    </p>
</form>
<div style="height:200px"></div>
<script type="text/javascript">
    $(function() {
        displayQuestionList(<?php echo json_encode($search_results, JSON_INVALID_UTF8_IGNORE); ?>);
        setlibhistory();
    });
</script>
</div>
<?php
    }
}

require("../footer.php");
