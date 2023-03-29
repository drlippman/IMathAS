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
		$ver = 'scored';
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
    $userprefUseMQ = (!isset($_SESSION['userprefs']['useeqed']) ||
        $_SESSION['userprefs']['useeqed'] == 1);

	$stm = $DBH->prepare("SELECT name,defpoints,isgroup,groupsetid,deffeedbacktext,courseid,tutoredit,submitby,ver,itemorder FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($aname,$defpoints,$isgroup,$groupsetid,$deffbtext,$assesscourseid,$tutoredit,$submitby,$aver,$itemorder) = $stm->fetch(PDO::FETCH_NUM);
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
	} else if ($isteacher || ($istutor && ($tutoredit&1)==1)) {
		$canedit = 1;
	} else {
		$canedit = 0;
	}
    $itemorder = explode(',',$itemorder);
    $prevqid = -1; $nextqid = -2;
    $curcnt = 1;
    foreach ($itemorder as $i=>$item) {
        $sub = explode('~',$item);
        if (count($sub)>1) {
            $subdets = explode('|', $sub[0]);
            if (count($subdets)>1) {
                array_shift($sub);
            }
        }
        foreach ($sub as $k=>$subitem) {
            if ($subitem == $qid) {
                if (count($sub)==1) {
                    $curqloc = $curcnt;
                } else {
                    $curqloc = $curcnt.'-'.($k+1);
                }
                $nextqid = -1;
            } else if ($nextqid == -1) {
                $nextqid = $subitem;
                break 2;
            } else {
                $prevqid = $subitem;
            }
        }
        if (count($sub)==1 || count($subdets)==1) {
            $curcnt++;
        } else {
            $curcnt+=$subdets[0];
        }
    }

	// Load new assess info class
	$assess_info = new AssessInfo($DBH, $aid, $cid, false);
	$assess_info->loadQuestionSettings($qid, true, false);
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
					//ud-userid-ver-qn-pn
					$orig = $_POST['os-'.$kp[1].'-'.$kp[2].'-'.$kp[3].'-'.$kp[4]];
					if ($v != $orig) {
						if ($v=='N/A') {
							$allscores[$kp[1]][$kp[2].'-'.$kp[3]][$kp[4]] = -1;
						} else {
							$allscores[$kp[1]][$kp[2].'-'.$kp[3]][$kp[4]] = floatval($v);
						}
					}
				} else if ($kp[0]=='fb') {
					//fb-ver-qn-userid
					if ($v=='' || $v=='<p></p>') {
						$v = '';
					}
					$allfeedbacks[$kp[3]][$kp[1].'-'.$kp[2]] = $v;
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
		$DBH->beginTransaction();
		$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_records.* FROM imas_users,imas_assessment_records ";
		$query .= "WHERE imas_assessment_records.userid=imas_users.id AND imas_assessment_records.assessmentid=:assessmentid ";
		if ($page != -1 && !$onepergroup && isset($_POST['userid'])) {
			$query .= " AND imas_users.id=:userid ";
		}
        $query .= "ORDER BY imas_users.LastName,imas_users.FirstName FOR UPDATE";
		
		$stm = $DBH->prepare($query);
		if ($page != -1 && !$onepergroup && isset($_POST['userid'])) {
			$stm->execute(array(':assessmentid'=>$aid, ':userid'=>$_POST['userid']));
		} else {
			$stm->execute(array(':assessmentid'=>$aid));
		}
		$cnt = 0;
		$updatedata = array();
		$changesToLog = array();

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
					if (isset($grpfeedbacks[$line['agroupid']])) {
						foreach ($grpfeedbacks[$line['agroupid']] as $loc=>$sv) {
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
				$changes = $assess_record->setGbScoreOverrides($adjustedScores);
				$assess_record->setGbFeedbacks($adjustedFeedbacks);

				if (count($adjustedScores) > 0 || count($adjustedFeedbacks) > 0) {
					$assess_record->saveRecord();
				}

				if (!empty($changes)) {
					$changesToLog[$line['userid']] = $changes;
				}

				// Normally it'd only make sense to update LTI scores that changed. But
				// this is a common trick to force score resends, so until another way
				// is added, this is removed:  count($adjustedScores) > 0 &&
				if (strlen($line['lti_sourcedid'])>1) {
					//update LTI score
					require_once("../includes/ltioutcomes.php");
					$gbscore = $assess_record->getGbScore();
					calcandupdateLTIgrade($line['lti_sourcedid'],$aid,$line['userid'],$gbscore['gbscore'],true, -1, false);
				}
			}
		}
        
		if (count($changesToLog)>0) {
			TeacherAuditLog::addTracking(
				$cid,
				"Change Grades",
				$aid,
				array(
					'qid'=>$qid,
					'changes'=>$changesToLog
				)
			);
		}
		$DBH->commit();

		if (isset($_GET['quick'])) {
			echo "saved";
		} else if ($page == -1 || isset($_POST['islaststu'])) {
            if ($page == -1 && !empty($_POST['prevqid'])) {
                header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradeallq2.php?"
                    . Sanitize::generateQueryStringFromMap(array('stu' => $stu, 'cid' => $cid, 'aid' => $aid,
                    'qid' => intval($_POST['prevqid']), 'r' => Sanitize::randomQueryStringParam(),)));
            } else if ($page == -1 && !empty($_POST['nextqid'])) {
                header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradeallq2.php?"
                    . Sanitize::generateQueryStringFromMap(array('stu' => $stu, 'cid' => $cid, 'aid' => $aid,
                    'qid' => intval($_POST['nextqid']), 'r' => Sanitize::randomQueryStringParam(),)));
            } else {
                header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gb-itemanalysis2.php?"
                    . Sanitize::generateQueryStringFromMap(array('stu' => $stu, 'cid' => $cid, 'aid' => $aid,
                        'r' => Sanitize::randomQueryStringParam(),)));
            }
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
    
    $points = $assess_info->getQuestionSetting($qid, 'points_possible');
    $rubric = $assess_info->getQuestionSetting($qid, 'rubric');
    $qsetid = $assess_info->getQuestionSetting($qid, 'questionsetid');
/*
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
*/
	$lastupdate = '030222';
	function formatTry($try,$cnt,$pn,$tn) {
		if (is_array($try) && $try[0] === 'draw') {
			$id = $cnt.'-'.$pn.'-'.$tn;
			if ($try[2][0]===null) {
				$try[2][0] = "";
			}
			echo '<canvas id="canvasGBR'.$id.'" ';
			echo 'width='.$try[2][6].' height='.$try[2][7].'></canvas>';
			echo '<input type=hidden id="qnGBR'.$id.'"/>';
			$la = explode(';;', str_replace(array('(',')'), array('[',']'), $try[1]));
			if ($la[0] !== '') {
				$la[0] = '[' . str_replace(';', '],[', $la[0]) . ']';
			}
			$la = '[[' . implode('],[', $la) . ']]';
			echo '<script>';
			array_unshift($try[2], 'GBR'.$id);
			echo 'canvases["GBR'.$id.'"] = ' . json_encode($try[2]) . ';';
			echo 'drawla["GBR'.$id.'"] = ' . json_encode(json_decode($la)) . ';';
			echo '</script>';
		} else {
			echo $try;
		}
	}


	$useeditor='review';
	$placeinhead = '<script type="text/javascript" src="'.$staticroot.'/javascript/rubric_min.js?v=022223"></script>';
	$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/gb-scoretools.js?v=020223"></script>';
	$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$staticroot.'/assess2/vue/css/index.css?v='.$lastupdate.'" />';
	$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$staticroot.'/assess2/vue/css/gbviewassess.css?v='.$lastupdate.'" />';
	$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$staticroot.'/assess2/vue/css/chunk-common.css?v='.$lastupdate.'" />';
	$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$staticroot.'/assess2/print.css?v='.$lastupdate.'" media="print">';
    if (!empty($CFG['assess2-use-vue-dev'])) {
        $placeinhead .= '<script src="'.$staticroot.'/mathquill/mathquill.js?v=022720" type="text/javascript"></script>';
        $placeinhead .= '<script src="'.$staticroot.'/javascript/drawing.js?v=041920" type="text/javascript"></script>';
        $placeinhead .= '<script src="'.$staticroot.'/javascript/AMhelpers2.js?v=052120" type="text/javascript"></script>';
        $placeinhead .= '<script src="'.$staticroot.'/javascript/eqntips.js?v=041920" type="text/javascript"></script>';
        $placeinhead .= '<script src="'.$staticroot.'/javascript/mathjs.js?v=041920" type="text/javascript"></script>';
        $placeinhead .= '<script src="'.$staticroot.'/mathquill/AMtoMQ.js?v=052120" type="text/javascript"></script>';
        $placeinhead .= '<script src="'.$staticroot.'/mathquill/mqeditor.js?v=041920" type="text/javascript"></script>';
        $placeinhead .= '<script src="'.$staticroot.'/mathquill/mqedlayout.js?v=041920" type="text/javascript"></script>';
    } else {
        $placeinhead .= '<script src="'.$staticroot.'/mathquill/mathquill.min.js?v=100220" type="text/javascript"></script>';
        $placeinhead .= '<script src="'.$staticroot.'/javascript/assess2_min.js?v=021123" type="text/javascript"></script>';
    }
    
	$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$staticroot.'/mathquill/mathquill-basic.css?v=021823">
	  <link rel="stylesheet" type="text/css" href="'.$staticroot.'/mathquill/mqeditor.css">';

	$placeinhead .= "<script type=\"text/javascript\">";
	$placeinhead .= 'function jumptostu() { ';
	$placeinhead .= '       var stun = document.getElementById("stusel").value; ';
	$address = $GLOBALS['basesiteurl'] . "/course/gradeallq2.php?";
	$address .= Sanitize::generateQueryStringFromMap(array('stu'=>$stu, 'cid'=>$cid, 'gbmode'=>$gbmode, 'aid'=>$aid, 'qid'=>$qid, 'ver'=>$ver));
	$placeinhead .= "       var toopen = '$address&page=' + stun;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	$placeinhead .= 'var GBdeffbtext ="'.Sanitize::encodeStringForJavascript($deffbtext).'";';
	$placeinhead .= 'function chgsecfilter() {
		var sec = document.getElementById("secfiltersel").value;
		var toopen = "'.$address.'&secfilter=" + encodeURIComponent(sec);
		window.location = toopen;
		}';
	$placeinhead .= 'function toggletryblock(type,n) {
		$("#"+type+n).toggle();
		if (!$("#"+type+n).hasClass("rendered")) {
			$("#"+type+n).find("canvas[id^=canvasGBR]").each(function(i,el) {
				window.imathasDraw.initCanvases(el.id.substr(6));
			});
			$("#"+type+n).addClass("rendered");
			window.drawPics(document.getElementById(type+n));
		}
	}
	$(function() {
		$(".viewworkwrap img").on("click", rotateimg);
	})
	';
	$placeinhead .= '</script>';
	if ($_SESSION['useed']!=0) {
		$placeinhead .= '<script type="text/javascript"> initeditor("divs","fbbox",1,true);</script>';
	}
	$placeinhead .= '<style type="text/css"> 
        .fixedbottomright {position: fixed; right: 10px; bottom: 10px; z-index:10;}
        .hoverbox { background-color: #fff; z-index: 9; box-shadow: 0px -3px 5px 0px rgb(0 0 0 / 75%);}
		</style>';
	require("../includes/rubric.php");
	$_SESSION['coursetheme'] = $coursetheme;
	require("../header.php");
	echo "<style type=\"text/css\">p.tips {	display: none;}\n .hideongradeall { display: none;} .pseudohidden {visibility:hidden;position:absolute;}</style>\n";
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	echo "&gt; <a href=\"gb-itemanalysis2.php?stu=" . Sanitize::encodeUrlParam($stu) . "&cid=$cid&aid=" . Sanitize::onlyInt($aid) . "\">Item Analysis</a> ";
	echo "&gt; Grading a Question</div>";
	echo "<div id=\"headergradeallq\" class=\"pagetitle\"><h1>";
    echo sprintf(_('Grading Question %s in %s'), $curqloc, Sanitize::encodeStringForDisplay($aname));
    echo "</h1></div>";

	echo '<div class="cpmid">';
    $qsmap = ['stu'=>$stu, 'gbmode'=>$gbmode, 'cid'=>$cid, 'aid'=>$aid, 'qid'=>$qid, 'page'=>$page, 'ver'=>$ver];
	if ($page==-1) {
        $qsmap['page'] = 0;
		echo "<a href=\"gradeallq2.php?" . Sanitize::generateQueryStringFromMap($qsmap) . "\">Grade one student at a time</a> (Do not use for group assignments)";
	} else {
        $qsmap['page'] = -1;
		echo "<a href=\"gradeallq2.php?" . Sanitize::generateQueryStringFromMap($qsmap) . "\">Grade all students at once</a>";
	}
    $qsmap['page'] = $page;
    echo '<br/>';
    if ($ver=='scored') {
		echo "<b>Showing Scored Attempts.</b>  ";
        $qsmap['ver'] = 'last';
		echo "<a href=\"gradeallq2.php?" . Sanitize::generateQueryStringFromMap($qsmap) . "\">Show Last Attempts.</a> ";
        $qsmap['ver'] = 'all';
        echo "<a href=\"gradeallq2.php?" . Sanitize::generateQueryStringFromMap($qsmap) . "\">Show All Attempts.</a> ";
	} else if ($ver=='last') {
        $qsmap['ver'] = 'scored';
        echo "<a href=\"gradeallq2.php?" . Sanitize::generateQueryStringFromMap($qsmap) . "\">Show Scored Attempts.</a> ";
        echo "<b>Showing Last Attempts.</b>  ";
        $qsmap['ver'] = 'all';
        echo "<a href=\"gradeallq2.php?" . Sanitize::generateQueryStringFromMap($qsmap) . "\">Show All Attempts.</a> ";
	} else {
        $qsmap['ver'] = 'scored';
        echo "<a href=\"gradeallq2.php?" . Sanitize::generateQueryStringFromMap($qsmap) . "\">Show Scored Attempts.</a>  ";
        $qsmap['ver'] = 'last';
		echo "<a href=\"gradeallq2.php?" . Sanitize::generateQueryStringFromMap($qsmap) . "\">Show Last Attempts.</a> ";
        echo "<b>Showing All Attempts.</b>  ";
    }
    if ($submitby == 'by_assessment') {
        echo 'Note: Only submitted attempts will show here.';
    }
	if (count($sections)>1) {
		echo '<br/>';
		echo _('Limit to section').': ';
		writeHtmlSelect('secfiltersel', $sections, $sections, $secfilter, _('All'), '-1', 'onchange="chgsecfilter()"');
	}
	echo '</div>';
	$query = "SELECT imas_rubrics.id,imas_rubrics.rubrictype,imas_rubrics.rubric FROM imas_rubrics JOIN imas_questions ";
	$query .= "ON imas_rubrics.id=imas_questions.rubric WHERE imas_questions.id=:id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':id'=>$qid));
	if ($stm->rowCount()>0) {
		echo printrubrics(array($stm->fetch(PDO::FETCH_NUM)));
    }
    echo '<button onclick="$(\'#filtersdiv\').slideToggle(100)">'._('Filters and Options').'</button>';
    echo '<div id="filtersdiv" style="display:none; margin-bottom: 10px" class="tabpanel">';
    echo '<p>';
	if ($page==-1) {
        echo _('Hide').':</p><ul style="list-style-type: none; margin:0; padding-left: 15px;">';
        echo '<li><label><input type=checkbox id="filter-unans" onchange="updatefilters()">'._('Unanswered Questions').'</label></li>';
        echo '<li><label><input type=checkbox id="filter-zero" onchange="updatefilters()">'._('Score = 0').'</label></li>';
        echo '<li><label><input type=checkbox id="filter-nonzero" onchange="updatefilters()">'._('0 &lt; score &lt 100% (before penalties)').'</label></li>';
        echo '<li><label><input type=checkbox id="filter-perfect" onchange="updatefilters()">'._('Score = 100% (before penalties)').'</label></li>';
        echo '<li><label><input type=checkbox id="filter-100" onchange="updatefilters()">'._('Score = 100% (after penalties)').'</label></li>';
        echo '<li><label><input type=checkbox id="filter-fb" onchange="updatefilters()">'._('Questions with Feedback').'</label></li>';
        echo '<li><label><input type=checkbox id="filter-nowork" onchange="updatefilters()">'._('Questions without Work').'</label></li>';
        echo '<li><label><input type=checkbox id="filter-work" onchange="updatefilters()">'._('Questions with Work').'</label></li>';
        echo '<li><label><input type=checkbox id="filter-names" onchange="updatefilters()">'._('Names').'</label></li>';
        echo '</ul>';
        echo '<p>';
		//echo ' <button type="button" id="preprint" onclick="preprint()">'._('Prepare for Printing (Slow)').'</button>';
    }
    echo ' <button type="button" id="showanstoggle" onclick="showallans()">'._('Show All Answers').'</button>';
    echo ' <button type="button" onclick="showallwork()">'._('Show All Work').'</button>';
    echo ' <button type="button" onclick="previewallfiles()">'._('Preview All Files').'</button>';
    echo ' <button type="button" onclick="sidebysidegrading()">'._('Side-by-Side').'</button>';
    echo ' <button type="button" onclick="toggleScrollingScoreboxes()">'._('Floating Scoreboxes').'</button>';
	echo ' <button type="button" id="clrfeedback" onclick="clearfeedback()">'._('Clear all feedback').'</button>';
	if ($deffbtext != '') {
		echo ' <button type="button" id="clrfeedback" onclick="cleardeffeedback()">'._('Clear default feedback').'</button>';
    }
    echo '</p>';
	if ($canedit) {
		echo '<p>All visible questions: <button type=button onclick="allvisfullcred();">'._('Full Credit').'</button> ';
		echo '<button type=button onclick="allvisnocred();">'._('No Credit').'</button></p>';
    }
    if ($page==-1) {
        echo '<p>'._('Sort by').': <button type=button onclick="sortByLastChange()">'._('Last Changed').'</button>';
        echo ' <button type=button onclick="sortByName()">'._('Name').'</button>';
        echo ' <button type=button onclick="sortByRand()">'._('Random').'</button>';
        echo '</p>';
    }
    echo '</div>'; // filtersdiv
	if ($page==-1 && $canedit) {
		echo '<div class="fixedbottomright">';
		echo '<button type="button" id="quicksavebtn" onclick="quicksave()">'._('Quick Save').'</button><br/>';
		echo '<span class="noticetext" id="quicksavenotice">&nbsp;</span>';
		echo '</div>';
	}
	echo "<form id=\"mainform\" method=post action=\"gradeallq2.php?stu=" . Sanitize::generateQueryStringFromMap($qsmap) . "&page=" . Sanitize::encodeUrlParam($page) . "&update=true\">\n";
	if ($isgroup>0) {
		echo '<p><input type="checkbox" name="onepergroup" value="1" onclick="hidegroupdup(this)" /> Grade one per group</p>';
	}

	

	if ($page!=-1) {
		$stulist = array();
		$qarr = array(':courseid'=>$cid, ':assessmentid'=>$aid);
		$query = "SELECT imas_users.LastName,imas_users.FirstName FROM imas_users,imas_assessment_records,imas_students ";
		$query .= "WHERE imas_assessment_records.userid=imas_users.id AND imas_students.userid=imas_users.id AND imas_students.courseid=:courseid AND imas_assessment_records.assessmentid=:assessmentid ";
		if ($hidelocked) {
			$query .= "AND imas_students.locked=0 ";
		}
        if ($submitby == 'by_assessment') {
            $query .= "AND (imas_assessment_records.status & 64)=64 ";
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
    if ($submitby == 'by_assessment') {
        $query .= "AND (imas_assessment_records.status & 64)=64 ";
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
    echo '<div id="qlistwrap">';
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
			//$s3asid = 'grp'.$line['agroupid'].'/'.$aid;
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
			//$s3asid = $asid; // TODO: revisit this
		}

        $locdata = $assess_record->getQuestionLocs($qid,$ver);

        foreach ($locdata as $vernum=>$lockeys) {
            foreach ($lockeys as $loc) {
                $teacherreview = $line['userid'];
                $qdata = $assess_record->getGbQuestionVersionData($loc, true, $vernum, $cnt);
                $answeightTot = array_sum($qdata['answeights']);
                $qdata['answeights'] = array_map(function($v) use ($answeightTot) { return $v/$answeightTot;}, $qdata['answeights']);
                
                $classes = '';
                if ($qdata['gbrawscore']==1) {
                    $classes = 'qfilter-perfect';
                } else if ($qdata['gbscore']>0) {
                    $classes = 'qfilter-nonzero';
                } else if ($qdata['status'] != 'unattempted') {
                    $classes = 'qfilter-zero';
                } else {
                    // it's possible only one part is unattempted
                    $unattempted = true;
                    foreach ($qdata['parts'] as $partdata) {
                        if ($partdata['try'] > 0) {
                            $unattempted = false;
                            break;
                        }
                    }
                    if ($unattempted) {
                        $classes = 'qfilter-unans';
                    } else {
                        $classes = 'qfilter-zero';
                    }
                }
                if (abs($qdata['score'] - $qdata['points_possible']) < .002) {
                    $classes .= ' qfilter-100';
                }
                if (trim($qdata['feedback']) !== '') {
                    $classes .= ' qfilter-fb';
                }
                if (empty($qdata['work'])) {
                    $classes .= ' qfilter-nowork';
                } else {
                    $classes .= ' qfilter-work';
                }
                if ($groupdup) {
                    $classes .= ' groupdup';
                }
                $lastchange = Sanitize::encodeStringForDisplay($qdata['lastchange'] ?? '');
                echo "<div class=\"$classes bigquestionwrap\" data-lastchange=\"$lastchange\">";
                
                echo "<div class=headerpane><b>".Sanitize::encodeStringForDisplay($line['LastName'].', '.$line['FirstName']).'</b></div>';

                if ($isgroup > 0 && !$groupdup) {
                    echo '<p class="group" style="display:none"><b>'.Sanitize::encodeStringForDisplay($groupnames[$line['agroupid']]);
                    if (isset($groupmembers[$line['agroupid']]) && count($groupmembers[$line['agroupid']])>0) {
                        echo '</b> ('.Sanitize::encodeStringForDisplay(implode(', ',$groupmembers[$line['agroupid']])).')</p>';
                    } else {
                        echo '</b> (empty)</p>';
                    }
                }

                /*
                To re-enable, need to define before $qdata, but figure another way to
                get answeights/points.
                if ($qtype=='multipart') {
                    $GLOBALS['questionscoreref'] = array("scorebox$cnt",$answeights);
                } else {
                    $GLOBALS['questionscoreref'] = array("scorebox$cnt",$points);
                }
                */
                echo '<div class=scrollpane>';
                echo '<div class="questionwrap questionpane">';
                echo '<div class="question" id="questionwrap'.$cnt.'">';
                echo $qdata['html'];
                echo '<script type="text/javascript">
                    $(function() {
                        var useMQ = ' . ((empty($qdata['jsparams']['noMQ']) && $userprefUseMQ) ? 'true' : 'false') . ';
                        imathasAssess.init('.json_encode($qdata['jsparams'], JSON_INVALID_UTF8_IGNORE).', useMQ, document.getElementById("questionwrap'.$cnt.'"));
                    });
                    </script>';
                echo '</div></div>';

                if (!empty($qdata['work'])) {
                    echo '<div class="questionpane viewworkwrap">';
                    echo '<button type="button" onclick="toggleWork(this)">'._('View Work').'</button>';
                    echo '<div class="introtext" style="display:none;">';
                    if ($qdata['worktime'] !== '0') {
                        echo '<div class="small">' . _('Last Changed').': '.$qdata['worktime'].'</div>';
                    }
                    echo  $qdata['work'].'</div></div>';
                }
                echo '</div>';
                echo "<div class=scoredetails>";
                echo '<span class="person">'.Sanitize::encodeStringForDisplay($line['LastName']).', '.Sanitize::encodeStringForDisplay($line['FirstName']).': </span>';
                if ($isgroup > 0 && !$groupdup) {
                    echo '<span class="group" style="display:none">' . Sanitize::encodeStringForDisplay($groupnames[$line['agroupid']]) . ': </span>';
                }
                if ($isgroup) {

                }

                if (!empty($qdata['singlescore'])) {
                    $qdata['answeights'] = [1];
                }
                $multiEntry = (count($qdata['answeights'])>1);
                // loop over parts
                for ($pn = 0; $pn < count($qdata['answeights']); $pn++) {
                    // get points on this part

                    if (!empty($qdata['singlescore'])) {
                        $pts = round($qdata['score'],3);
                    } else if (isset($qdata['scoreoverride']) && !is_array($qdata['scoreoverride'])) {
                        $pts = round($qdata['scoreoverride'] * $qdata['points_possible'] * $qdata['answeights'][$pn], 3);
                    } else if (isset($qdata['scoreoverride']) && isset($qdata['scoreoverride'][$pn])) {
                        if (isset($qdata['parts'][$pn]['points_possible'])) {
                            $pts = round($qdata['scoreoverride'][$pn] * $qdata['parts'][$pn]['points_possible'], 3);
                        } else {
                            $pts = round($qdata['scoreoverride'][$pn] * $qdata['points_possible'] * $qdata['answeights'][$pn], 3);
                        }
                    } else if (count($qdata['parts'])==1 && $qdata['parts'][0]['try']==0) {
                        $pts = 'N/A';
                    } else if (isset($qdata['parts'][$pn]['score'])) {
                        $pts = $qdata['parts'][$pn]['score'];
                    } else {
                        $pts = 0;
                    }

                    // get possible on this part
                    $ptposs = round($qdata['points_possible'] * $qdata['answeights'][$pn], 3);

                    if ($canedit) {
                        $boxid = ($multiEntry) ? "$cnt-$pn" : $cnt;
                        echo "<input type=text size=4 id=\"scorebox$boxid\" name=\"ud-" . Sanitize::onlyInt($line['userid']) . '-'.$vernum . "-".Sanitize::onlyFloat($loc)."-$pn\" value=\"".Sanitize::encodeStringForDisplay($pts)."\" pattern=\"N\/A|\d*\.?\d*\">";
                        echo "<input type=hidden name=\"os-" . Sanitize::onlyInt($line['userid']) . '-'.$vernum . "-".Sanitize::onlyFloat($loc)."-$pn\" value=\"".Sanitize::encodeStringForDisplay($pts)."\">";
                        if ($rubric != 0) {
                            $fbref = (count($qdata['answeights'])>1) ? ($loc+1).' part '.($pn+1) : ($loc+1);
                            echo printrubriclink($rubric, $ptposs,"scorebox$boxid","fb-". $vernum .'-'. $loc.'-'. Sanitize::onlyInt($line['userid']), $fbref);
                        }
                    } else {
                        echo Sanitize::encodeStringForDisplay($pts);
                    }
                    echo '/'.Sanitize::encodeStringForDisplay($ptposs).' ';
                }

                if ($multiEntry && $canedit) {
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

                if (!empty($qdata['other_tries'])) {
                    $maxtries = 0;
                    foreach ($qdata['other_tries'] as $pn=>$tries) {
                        if (count($tries)>1) {
                            $maxtries = count($tries);
                            break;
                        }
                    }
                    if ($maxtries > 0) {
                        echo ' &nbsp; <button type=button onclick="toggletryblock(\'alltries\','.$cnt.')">'._('Show all tries').'</button>';
                        echo '<div id="alltries'.$cnt.'" style="display:none;">';
                        foreach ($qdata['other_tries'] as $pn=>$tries) {
                            if (count($qdata['other_tries']) > 1) {
                                echo '<div><strong>'._('Part').' '.($pn+1).'</strong></div>';
                            }
                            foreach ($tries as $tn=>$try) {
                                echo '<div>'._('Try').' '.($tn+1).': ';
                                formatTry($try,$cnt,$pn,$tn);
                                echo '</div>';
                            }
                        }
                        echo '</div>';
                    }
                }

                if (!empty($qdata['autosaves'])) {
                    echo ' &nbsp; <button type=button onclick="toggletryblock(\'autosaves\','.$cnt.')">'._('Show autosaves').'</button>';
                    echo '<div id="autosaves'.$cnt.'" style="display:none;">';
                    echo '<p class="subdued">'._('Autosaves have been entered by the student but not submitted for grading, so are not included in the scoring.').'</p>';
                    foreach ($qdata['autosaves'] as $pn=>$tries) {
                        if (count($qdata['autosaves']) > 1) {
                            echo '<div><strong>'._('Part').' '.($pn+1).'</strong></div>';
                        }
                        foreach ($tries as $tn=>$try) {
                            formatTry($try,$cnt,$pn,$tn);
                        }
                    }
                    echo '</div>';
                }

                echo "<br/>"._("Question Feedback").": ";
                if (!$canedit) {
                    echo '<div>';
                    echo Sanitize::outgoingHtml($qdata['feedback']);
                    echo '</div>';
                } else if ($_SESSION['useed']==0) {
                    echo '<br/><textarea cols="60" rows="2" class="fbbox" id="fb-'. $vernum.'-'. $loc.'-'.Sanitize::onlyInt($line['userid']).'" name="fb-'.$loc.'-'.Sanitize::onlyInt($line['userid']).'">';
                    echo Sanitize::encodeStringForDisplay($qdata['feedback'], true);
                    echo '</textarea>';
                } else {
                    echo '<div class="fbbox skipmathrender" id="fb-'. $vernum.'-'.$loc.'-'.Sanitize::onlyInt($line['userid']).'">';
                    echo Sanitize::outgoingHtml($qdata['feedback']);
                    echo '</div>';
                }
                echo '<br/>' . _('Question').' #'.($loc+1);
                echo ', '._('version').' '.($qdata['ver']+1);
                echo ". <a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?" . Sanitize::generateQueryStringFromMap(array(
                        'cid' => $cid, 'add' => 'new', 'quoteq' => "{$loc}-{$qsetid}-{$qdata['seed']}-$aid-{$line['ver']}",
                        'to' => $line['userid'])) . "\">Use in Message</a>";
                echo ' <span class="subdued small">'._('Question ID ').$qsetid.'</span>';
                if (!empty($qdata['timeactive']['total']) || !empty($qdata['lastchange'])) {
                    echo '<br/>';
                    if (!empty($qdata['timeactive']['total'])) {
                        echo _('Time spent on this version').': ';
                        echo round($qdata['timeactive']['total']/60, 1)._(' minutes').'. ';
                    }
                    if (!empty($qdata['lastchange'])) {
                        echo _('Last Changed').' '.$qdata['lastchange'];
                    }
                }
                echo "</div>\n"; //end review div
                echo '</div>'; //end wrapper div

                $cnt++;
            }
        }
		$assess_record->saveRecordIfNeeded();
	}
    echo '</div>'; //qlistwrap
	if ($canedit) {
        echo '<p>'.sprintf(_('Grading Question %s in %s'), $curqloc, Sanitize::encodeStringForDisplay($aname)).'</p>';

		echo '<button type="submit">';
        if ($page == -1 || $page == count($stulist)-1) {
            echo _('Save Changes');
        } else {
            echo _('Save Changes and Next Student');
        }
        echo '</button> ';
        if ($page == -1 || $page == count($stulist)-1) {
            if ($prevqid > -1) {
                echo '<button type=submit name=prevqid value="'.Sanitize::onlyInt($prevqid).'">'._('Save and Prev Question').'</button>';
            }
            if ($nextqid > -1) {
                echo '<button type=submit name=nextqid value="'.Sanitize::onlyInt($nextqid).'">'._('Save and Next Question').'</button>';
            }
        }
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
