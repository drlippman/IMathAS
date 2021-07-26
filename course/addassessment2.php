<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2019 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require_once("../includes/TeacherAuditLog.php");

if ($courseUIver == 1) {
	if (isset($_GET['id'])) {
		header(sprintf('Location: %s/course/addassessment.php?cid=%s&id=%d&r=' .Sanitize::randomQueryStringParam() ,
			$GLOBALS['basesiteurl'], $cid, $_GET['id']));
	} else {
		header(sprintf('Location: %s/course/addassessment.php?cid=%s&r=' .Sanitize::randomQueryStringParam() ,
			$GLOBALS['basesiteurl'], $cid));
	}
}

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "summary,intro";
$pagetitle = "Assessment Settings";
$cid = Sanitize::courseId($_GET['cid']);

if (isset($_GET['from'])) {
	$from = $_GET['from'];
} else {
	$from = 'cp';
}
if (isset($_GET['tb'])) {
	$totb = $_GET['tb'];
} else {
	$totb = 'b';
}

$curBreadcrumb = $breadcrumbbase;
if (empty($_COOKIE['fromltimenu'])) {
    $curBreadcrumb .= " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
}
if ($from=='gb') {
	$curBreadcrumb .= "<a href=\"gradebook.php?cid=$cid\">Gradebook</a> &gt; ";
} else if ($from=='mcd') {
	$curBreadcrumb .= "&gt; <a href=\"masschgdates.php?cid=$cid\">Mass Change Dates</a> &gt; ";
}

if (isset($_GET['id'])) {
	$curBreadcrumb .= "Modify Assessment\n";
} else {
	$curBreadcrumb .= "Add Assessment\n";
}

if (isset($_GET['id'])) {
	$stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=?");
	$stm->execute(array(intval($_GET['id'])));
	if ($stm->rowCount()==0 || $stm->fetchColumn(0) != $_GET['cid']) {
		echo "Invalid ID";
		exit;
	}
}

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
  $cid = Sanitize::courseId($_GET['cid']);
  $block = $_GET['block'] ?? '';

    if (isset($_GET['id'])) {  //INITIAL LOAD IN MODIFY MODE
        $assessmentId = Sanitize::onlyInt($_GET['id']);
		$query = "SELECT COUNT(iar.userid) FROM imas_assessment_records AS iar,imas_students WHERE ";
		$query .= "iar.assessmentid=:assessmentid AND iar.userid=imas_students.userid AND imas_students.courseid=:courseid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':assessmentid'=>$assessmentId, ':courseid'=>$cid));
		$taken = ($stm->fetchColumn(0)>0);
	} else {
		$taken = false;
	}

  $stm = $DBH->prepare("SELECT dates_by_lti FROM imas_courses WHERE id=?");
  $stm->execute(array($cid));
  $dates_by_lti = $stm->fetchColumn(0);

  if (isset($_REQUEST['clearattempts'])) { //FORM POSTED WITH CLEAR ATTEMPTS FLAG
      if (isset($_POST['clearattempts']) && $_POST['clearattempts'] == "confirmed") {
        $DBH->beginTransaction();
        require_once '../includes/filehandler.php';
        deleteallaidfiles($assessmentId);
        $grades = array();
        $stm = $DBH->prepare("SELECT userid,score FROM imas_assessment_records WHERE assessmentid=:assessmentid");
        $stm->execute(array(':assessmentid' => $assessmentId));
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $grades[$row['userid']] = $row["score"];
        }

        $stm = $DBH->prepare("DELETE FROM imas_assessment_records WHERE assessmentid=:assessmentid");
        $stm->execute(array(':assessmentid' => $assessmentId));
        if ($stm->rowCount() > 0) {
            TeacherAuditLog::addTracking(
                $cid,
                "Clear Attempts",
                $assessmentId,
                array('grades' => $grades)
            );
        }

        $stm = $DBH->prepare("DELETE FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
        $stm->execute(array(':assessmentid' => $assessmentId));
        $stm = $DBH->prepare("UPDATE imas_questions SET withdrawn=0 WHERE assessmentid=:assessmentid");
        $stm->execute(array(':assessmentid' => $assessmentId));
        // clear out time limit extensions
        $stm = $DBH->prepare("UPDATE imas_exceptions SET timeext=0 WHERE timeext<>0 AND assessmentid=? AND itemtype='A'");
        $stm->execute(array($assessmentId));
        
        $DBH->commit();
        header(sprintf('Location: %s/course/addassessment2.php?cid=%s&id=%d&r=' . Sanitize::randomQueryStringParam(), $GLOBALS['basesiteurl'],
            $cid, $assessmentId));
        exit;
      } else {
          $overwriteBody = 1;
          $stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
          $stm->execute(array(':id'=>$assessmentId));
          $assessmentname = $stm->fetchColumn(0);
          $body = '<div class=breadcrumb>'.$breadcrumbbase;
          if (empty($_COOKIE['fromltimenu'])) {
            $body .= sprintf(" <a href=\"course.php?cid=%s\">%s</a> &gt; ",
                $cid, Sanitize::encodeStringForDisplay($coursename));
          }
          $body .= sprintf(" <a href=\"addassessment2.php?cid=%s&id=%d\">Modify Assessment</a> &gt; Clear Attempts</div>\n",
              $cid, $assessmentId);
		  $body .= sprintf("<h2>%s</h2>", Sanitize::encodeStringForDisplay($assessmentname));
          $body .= "<p>Are you SURE you want to delete all attempts (grades) for this assessment?</p>";
          $body .= '<form method="POST" action="'.sprintf('addassessment2.php?cid=%s&id=%d',$cid, $assessmentId).'">';
          $body .= '<p><button type=submit name=clearattempts value=confirmed>'._('Yes, Clear').'</button>';
          $body .= sprintf("<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='addassessment2.php?cid=%s&id=%d'\"></p>\n",
              $cid, $assessmentId);
          $body .= '</form>';
      }
  } elseif (!empty($_POST['name'])) { //if the form has been submitted

		$DBH->beginTransaction();
		$toset = array();
		// Name and dates
		$toset['name'] = Sanitize::stripHtmlTags($_POST['name']);
    if ($toset['name'] == '') {
    	$toset['name'] = _('Unnamed Assessment');
    }
        $_POST['summary'] = Sanitize::trimEmptyPara($_POST['summary']);
		if ($_POST['summary']=='<p>Enter summary here (shows on course page)</p>' || $_POST['summary']=='<p></p>') {
			$toset['summary'] = '';
		} else {
			$toset['summary'] = Sanitize::incomingHtml($_POST['summary']);
        }
        $_POST['intro'] = Sanitize::trimEmptyPara($_POST['intro']);
		if ($_POST['intro']=='<p>Enter intro/instructions</p>' || $_POST['intro']=='<p></p>') {
			$toset['intro']= '';
		} else {
			$toset['intro'] = Sanitize::incomingHtml($_POST['intro']);
		}
    require_once("../includes/parsedatetime.php");
		$toset['avail'] = Sanitize::onlyInt($_POST['avail']);

    if ($_POST['sdatetype']=='0') {
      $toset['startdate'] = 0;
    } else {
      $toset['startdate'] = parsedatetime($_POST['sdate'],$_POST['stime'],0);
    }
    if ($_POST['edatetype']=='2000000000') {
      $toset['enddate'] = 2000000000;
    } else {
      $toset['enddate'] = parsedatetime($_POST['edate'],$_POST['etime'],2000000000);
    }

    if (empty($_POST['allowpractice']) || $toset['enddate'] == 2000000000) {
      $toset['reviewdate'] = 0;
    } else {
      $toset['reviewdate'] = 2000000000;
    }

		// Core options
		if ($_POST['copyfrom'] > 0) { // copy options from another assessment
			$fields = array('displaymethod','submitby','defregens','defregenpenalty',
									'keepscore','defattempts','defpenalty','showscores','showans',
									'viewingb','scoresingb','ansingb','gbcategory','caltag','shuffle',
									'istutorial','noprint','showcat','allowlate','LPcutoff',
									'timelimit','overtime_grace','overtime_penalty','password',
									'reqscore','reqscoretype','reqscoreaid','showhints',
									'msgtoinstr','eqnhelper','posttoforum','extrefs','showtips',
									'cntingb','minscore','deffeedbacktext','tutoredit','exceptionpenalty',
									'defoutcome','isgroup','groupsetid','groupmax','showwork');
			$fieldlist = implode(',', $fields);
			$stm = $DBH->prepare("SELECT $fieldlist FROM imas_assessments WHERE id=:id AND courseid=:cid");
			$stm->execute(array(':id'=>intval($_POST['copyfrom']), ':cid'=>$cid));
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			if ($row !== false) {
				foreach ($row as $k=>$v) {
					$toset[$k] = $v;
				}
			}
			if (isset($_POST['copysummary']) || isset($_POST['copyinstr']) ||
				isset($_POST['copydates']) || isset($_POST['copyendmsg'])
			) {
				$stm = $DBH->prepare("SELECT summary,intro,startdate,enddate,reviewdate,avail,endmsg FROM imas_assessments WHERE id=:id AND courseid=:cid");
				$stm->execute(array(':id'=>intval($_POST['copyfrom']), ':cid'=>$cid));
				$row = $stm->fetch(PDO::FETCH_ASSOC);
				if (isset($_POST['copysummary'])) {
					$toset['summary'] = $row['summary'];
				}
				if (isset($_POST['copyinstr'])) {
					if (($introjson=json_decode($row['intro']))!==null) { //is json intro
							$toset['intro'] = $introjson[0];
					} else {
							$toset['intro'] = $row['intro'];
					}
				}
				if (isset($_POST['copydates'])) {
					$toset['startdate'] = $row['startdate'];
					$toset['enddate'] = $row['enddate'];
					$toset['reviewdate'] = $row['reviewdate'];
				}
				if (isset($_POST['copyendmsg'])) {
					$toset['endmsg'] = $row['endmsg'];
				}
			}
		} else { // set using values selected
			$toset['displaymethod'] = Sanitize::stripHtmlTags($_POST['displaymethod']);

			$toset['submitby'] = Sanitize::stripHtmlTags($_POST['subtype']);
			$toset['defregens'] = Sanitize::onlyInt($_POST['defregens']);
			$defregenpenalty_aftern = Sanitize::onlyInt($_POST['defregenpenaltyaftern']);
			if ($toset['defregens'] == 1) {
				$toset['defregenpenalty'] = 0;
			} else if ($defregenpenalty_aftern > 1 && $_POST['defregenpenalty'] > 0) {
				$toset['defregenpenalty'] = 'S' . $defregenpenalty_aftern . Sanitize::onlyInt($_POST['defregenpenalty']);
			} else {
				$toset['defregenpenalty'] = Sanitize::onlyInt($_POST['defregenpenalty']);
			}
			if (isset($_POST['keepscore'])) {
				$toset['keepscore'] = Sanitize::simpleString($_POST['keepscore']);
			}


			$toset['defattempts'] = Sanitize::onlyInt($_POST['defattempts']);
			$defattemptpenalty_aftern = Sanitize::onlyInt($_POST['defattemptpenaltyaftern']);
			if ($toset['defattempts'] == 1) {
				$toset['defpenalty'] = 0;
			} else if ($defattemptpenalty_aftern > 1 && $_POST['defattemptpenalty'] > 0) {
				$toset['defpenalty'] = 'S' . $defattemptpenalty_aftern . Sanitize::onlyInt($_POST['defattemptpenalty']);
			} else {
				$toset['defpenalty'] = Sanitize::onlyInt($_POST['defattemptpenalty']);
			}

			$toset['showscores'] = Sanitize::simpleString($_POST['showscores']);
			if ($toset['showscores'] == 'none') {
				$toset['showans'] = 'never';
			} else {
				$toset['showans'] = Sanitize::simpleString($_POST['showans']);
			}
			$toset['viewingb'] = Sanitize::simpleString($_POST['viewingb']);
			$toset['scoresingb'] = Sanitize::simpleString($_POST['scoresingb']);
			if ($toset['viewingb'] == 'never') {
				$toset['ansingb'] = 'never';
			} else {
				$toset['ansingb'] = Sanitize::simpleString($_POST['ansingb']);
			}
			$toset['gbcategory'] = Sanitize::onlyInt($_POST['gbcategory']);

			// additional display options
			$toset['caltag'] = Sanitize::stripHtmlTags($_POST['caltag']);
			$toset['shuffle'] = Sanitize::onlyInt($_POST['shuffle']);
			if (isset($_POST['sameseed'])) { $toset['shuffle'] += 2;}
			if (isset($_POST['samever'])) { $toset['shuffle'] += 4;}
			$toset['istutorial'] = empty($_POST['istutorial']) ? 0 : 1;
			$toset['noprint'] = empty($_POST['noprint']) ? 0 : 1;
			$toset['showcat'] = empty($_POST['showcat']) ? 0 : 1;
			$toset['showwork'] = Sanitize::onlyInt($_POST['showwork']) + Sanitize::onlyInt($_POST['showworktype']);

			// time limit and access control
			$toset['allowlate'] = Sanitize::onlyInt($_POST['allowlate']);
	    if (isset($_POST['latepassafterdue']) && $toset['allowlate']>0) {
	      $toset['allowlate'] += 10;
	    }

	    if (isset($_POST['dolpcutoff']) && trim($_POST['lpdate']) != '' && trim($_POST['lptime']) != '') {
	    	$toset['LPcutoff'] = parsedatetime($_POST['lpdate'],$_POST['lptime'],0);
	    	if (tzdate("m/d/Y",$GLOBALS['courseenddate']) == tzdate("m/d/Y", $toset['LPcutoff']) ||
					$toset['LPcutoff']<$enddate
				) {
	    		$toset['LPcutoff'] = 0; //don't really set if it matches course end date or is before
	    	}
	    } else {
	    	$toset['LPcutoff'] = 0;
	    }

			$toset['timelimit'] = -1*round(Sanitize::onlyFloat($_POST['timelimit'])*60);
			$toset['overtime_grace'] = 0;
			$toset['overtime_penalty'] = 0;
	    if (isset($_POST['allowovertime']) && $_POST['overtimegrace'] > 0) {
	        $toset['timelimit'] = -1*$toset['timelimit'];
					$toset['overtime_grace'] = round(Sanitize::onlyFloat($_POST['overtimegrace'])*60);
					$toset['overtime_penalty'] = Sanitize::onlyInt($_POST['overtimepenalty']);
	    }

			$toset['password'] = trim(Sanitize::stripHtmlTags($_POST['assmpassword']));

			$toset['reqscore'] = Sanitize::onlyInt($_POST['reqscore']);
			if ($_POST['reqscoreshowtype']==-1 || $toset['reqscore']==0) {
				$toset['reqscore'] = 0;
				$toset['reqscoretype'] = 0;
				$toset['reqscoreaid'] = 0;
			} else {
				$toset['reqscoreaid'] = Sanitize::onlyInt($_POST['reqscoreaid']);
				$toset['reqscoretype'] = 0;
				if ($_POST['reqscoreshowtype']==1) {
					$toset['reqscoretype'] |= 1;
				}
				if ($_POST['reqscorecalctype']==1) {
					$toset['reqscoretype'] |= 2;
				}
			}

			// help and hints
			$toset['showhints'] = empty($_POST['showhints']) ? 0 : 1;
			$toset['showhints'] |= empty($_POST['showextrefs']) ? 0 : 2;

			$toset['msgtoinstr'] = empty($_POST['msgtoinstr']) ? 0 : 1;

			$toset['eqnhelper'] = 2;

			if (!isset($_POST['doposttoforum'])) {
	      $toset['posttoforum'] = 0;
	    } else {
				$toset['posttoforum'] = Sanitize::onlyInt($_POST['posttoforum']);
			}

			$extrefs = array();
				if (isset($_POST['extreflabels'])) {
				foreach ($_POST['extreflabels'] as $k=>$label) {
					$label = trim(Sanitize::stripHtmlTags($label));
		    	$link = trim(Sanitize::url($_POST['extreflinks'][$k]));
		    	if ($label != '' && $link != '') {
		    		$extrefs[] = array(
		    			'label' => $label,
		    			'link' => $link
		    		);
		    	}
				}
			}
			$toset['extrefs'] = json_encode($extrefs);

			$toset['showtips'] = Sanitize::onlyInt($_POST['showtips']);

			// grading and feedback
			$toset['cntingb'] = Sanitize::onlyInt($_POST['cntingb']);

			$toset['minscore'] = Sanitize::onlyInt($_POST['minscore']);
	    if ($_POST['minscoretype']==1 && trim($_POST['minscore'])!='' && $toset['minscore']>0) {
	      $toset['minscore'] += 10000;
	    }

			if (isset($_POST['usedeffb'])) {
	      $toset['deffeedbacktext'] = Sanitize::incomingHtml($_POST['deffb']);
	    } else {
	      $toset['deffeedbacktext'] = '';
	    }

			$toset['tutoredit'] = Sanitize::onlyInt($_POST['tutoredit']);
			$toset['exceptionpenalty'] = Sanitize::onlyInt($_POST['exceptionpenalty']);
			$toset['defoutcome'] = Sanitize::onlyInt($_POST['defoutcome']);

			// group assessmentid
	    $toset['isgroup'] = Sanitize::onlyInt($_POST['isgroup']);
			if ($toset['isgroup'] > 0) {
				$toset['groupsetid'] = Sanitize::onlyInt($_POST['groupsetid']);
				$toset['groupmax'] = Sanitize::onlyInt($_POST['groupmax']);
			} else {
				$toset['groupsetid'] = 0;
				$toset['groupmax'] = isset($CFG['AMS']['groupmax'])?$CFG['AMS']['groupmax']:6;
			}
		}




    //is updating, switching from nongroup to group, and not creating new groupset, check if groups and asids already exist
    //if so, cannot handle
    $updategroupset='';
    if (isset($_GET['id']) && $toset['isgroup']>0 && $toset['groupsetid']>0) {
      $isok = true;
      $stm = $DBH->prepare("SELECT isgroup FROM imas_assessments WHERE id=:id");
      $stm->execute(array(':id'=>$assessmentId));
      if ($stm->fetchColumn(0)==0) {
        //check to see if students have already started assessment
        //don't really care if groups exist - just whether asids exist
        $query = "SELECT COUNT(iar.userid) FROM imas_assessment_records AS iar,imas_students WHERE ";
        $query .= "iar.assessmentid=:assessmentid AND iar.userid=imas_students.userid AND imas_students.courseid=:courseid";
        $stm = $DBH->prepare($query);
        $stm->execute(array(':assessmentid'=>$assessmentId, ':courseid'=>$cid));
        if ($stm->fetchColumn(0)>0) {
            echo "Sorry, cannot switch to use pre-defined groups after students have already started the assessment";
            exit;
        }
      }
      $updategroupset = $toset['groupsetid'];
    }

    if ($toset['isgroup']>0 && isset($_POST['groupsetid']) && $toset['groupsetid']==0) {
        //create new groupset
        $stm = $DBH->prepare("INSERT INTO imas_stugroupset (courseid,name) VALUES (:courseid, :name)");
        $stm->execute(array(':courseid'=>$cid, ':name'=>'Group set for '.$toset['name']));
      	$toset['groupsetid'] = $DBH->lastInsertId();
        $updategroupset = $toset['groupsetid'];
    }

		if (isset($_GET['id'])) {  //already have id; update
			$stm = $DBH->prepare("SELECT * FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			$curassess = $stm->fetch(PDO::FETCH_ASSOC);

      if ($toset['isgroup']==0) { //set agroupid=0 if switching from groups to not groups
        if ($curassess['isgroup']>0) {
          $stm = $DBH->prepare("UPDATE imas_assessment_records SET agroupid=0 WHERE assessmentid=:assessmentid");
          $stm->execute(array(':assessmentid'=>$assessmentId));
        }
      } else { //if switching from nogroup to groups and groups already exist, need set agroupids if asids exist already
          //NOT ALLOWED CURRENTLY
      }
      if (($introjson=json_decode($curassess['intro']))!==null) { //is json intro
        $introjson[0] = $toset['intro'];
        $toset['intro'] = json_encode($introjson);
      }

			if ($updategroupset == '') { // don't change group
				unset($toset['groupsetid']);
			}
			if ($dates_by_lti>0) { // don't change dates
				unset($toset['startdate']);
				unset($toset['enddate']);
			}

			$qarr = array();
			$setstr = array();
			foreach ($toset as $k=>$v) {
				$setstr[] = $k . '=:' . $k;
				$qarr[':'.$k] = $v;
			}
			$query = 'UPDATE imas_assessments SET ' . implode(',', $setstr);
			$query .= ' WHERE id=:id AND courseid=:cid';

      $qarr[':id'] = $assessmentId;
      $qarr[':cid'] = $cid;
			$stm = $DBH->prepare($query);
			$stm->execute($qarr);

			if ($taken && $stm->rowCount()>0) {
				$metadata = array();
				foreach ($curassess as $k=>$v) {
					if (isset($toset[$k]) && $toset[$k] != $v) {
						$metadata[$k] = ['old'=>$v, 'new'=>$toset[$k]];
					}
				}
				$result = TeacherAuditLog::addTracking(
				    $cid,
				    "Assessment Settings Change",
				    $assessmentId,
				    $metadata
				);
			}

			/*  TODO: make this work in new model
			if ($toset['deffb']!=$curassess['deffeedbacktext']) {
				//removed default feedback text; remove it from existing attempts
				$updatefb = $DBH->prepare("UPDATE imas_assessment_records SET feedback=? WHERE id=?");
				$stm = $DBH->prepare("SELECT id,feedback FROM imas_assessment_records WHERE assessmentid=?");
				$stm->execute(array($_GET['id']));
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					$fbjson = json_decode($row['feedback'], true);
					if ($fbjson === null) {
						//old format
						if ($row['feedback']==$curassess['deffeedbacktext'] ||
						   ($row['feedback']=='' && $curassess['deffeedbacktext']=='' && $deffb!='')) {
							if ($deffb=='') {
								$updatefb->execute(array('', $row['id']));
							} else {
								$updatefb->execute(array(json_encode(array('Z'=>$deffb)), $row['id']));
							}
						}
					} else if (isset($fbjson['Z'])) {
						if (strip_tags(str_replace(' ','',$fbjson['Z']))==strip_tags(str_replace(' ','',$curassess['deffeedbacktext']))) {
							if ($deffb=='') {
								unset($fbjson['Z']);
							} else {
								$fbjson['Z']=$deffb;
							}
							$updatefb->execute(array(json_encode($fbjson), $row['id']));
						}
					} else if ($deffb!='') {
						$fbjson['Z']=$deffb;
						$updatefb->execute(array(json_encode($fbjson), $row['id']));
					}
				}
			}
			*/
			// Delete any teacher or tutor attempts on this assessment
			$query = 'DELETE iar FROM imas_assessment_records AS iar JOIN
				imas_teachers AS usr ON usr.userid=iar.userid AND usr.courseid=?
				WHERE iar.assessmentid=?';
			$stm = $DBH->prepare($query);
			$stm->execute(array($cid, $assessmentId));
			$query = 'DELETE iar FROM imas_assessment_records AS iar JOIN
				imas_tutors AS usr ON usr.userid=iar.userid AND usr.courseid=?
				WHERE iar.assessmentid=?';
			$stm = $DBH->prepare($query);
			$stm->execute(array($cid, $assessmentId));

			// Re-total any student attempts on this assessment
            //need to re-score assessment attempts based on withdrawal
            require_once('../assess2/AssessHelpers.php');
            AssessHelpers::retotalAll($cid, $assessmentId, true, false, 
                ($toset['submitby']==$curassess['submitby']) ? '' : $toset['submitby'], false);

            // update "show work after" status flags
            AssessHelpers::updateShowWorkStatus($assessmentId, $toset['showwork'], $toset['submitby']);
            
			$DBH->commit();
			$rqp = Sanitize::randomQueryStringParam();
			if ($from=='gb') {
				header(sprintf('Location: %s/course/gradebook.php?cid=%s&r=%s', $GLOBALS['basesiteurl'], $cid, $rqp));
			} else if ($from=='mcd') {
				header(sprintf('Location: %s/course/masschgdates.php?cid=%s&r=%s', $GLOBALS['basesiteurl'], $cid, $rqp));
			} else if ($from=='lti') {
				header(sprintf('Location: %s/ltihome.php?showhome=true', $GLOBALS['basesiteurl']));
			} else {
				$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
				header(sprintf('Location: %s/course/course.php?cid=%s&r=%s', $GLOBALS['basesiteurl'], $cid.$btf, $rqp));
			}
			exit;
		} else { //add new
			if ($dates_by_lti>0) {
				$toset['date_by_lti'] = 1;
			} else {
				$toset['date_by_lti'] = 0;
			}
			$toset['defpoints'] = isset($CFG['AMS']['defpoints'])?$CFG['AMS']['defpoints']:10;

			$qarr = array(':cid'=>$cid, ':ptsposs'=>0, ':ver'=>2);
			$fieldarr = array('courseid', 'ptsposs', 'ver');
			$valarr = array(':cid', ':ptsposs', ':ver');
			foreach ($toset as $k=>$v) {
				$fieldarr[] = $k;
				$valarr[] = ':'.$k;
				$qarr[':'.$k] = $v;
			}

			$query = 'INSERT INTO imas_assessments (' . implode(',', $fieldarr) . ') ';
			$query .= 'VALUES (' . implode(',', $valarr) . ')';
			$stm = $DBH->prepare($query);
			$stm->execute($qarr);
			$newaid = $DBH->lastInsertId();
      $query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
      $query .= "(:courseid, :itemtype, :typeid);";
      $stm = $DBH->prepare($query);
      $stm->execute(array(':courseid'=>$cid, ':itemtype'=>'Assessment', ':typeid'=>$newaid));
      $itemid = $DBH->lastInsertId();
      $stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
      $stm->execute(array(':id'=>$cid));
      $line = $stm->fetch(PDO::FETCH_ASSOC);
      $items = unserialize($line['itemorder']);

      $blocktree = explode('-',$block);
      $sub =& $items;
      for ($i=1;$i<count($blocktree);$i++) {
        $sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
      }
      if ($totb=='b') {
        $sub[] = $itemid;
      } else if ($totb=='t') {
        array_unshift($sub,$itemid);
      }
      $itemorder = serialize($items);
      $stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
      $stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
      $DBH->commit();
      header(sprintf('Location: %s/course/addquestions.php?cid=%s&aid=%d', $GLOBALS['basesiteurl'], $cid, $newaid));
      exit;
		}

  } else { //INITIAL LOAD
      if (isset($_GET['id'])) {  //INITIAL LOAD IN MODIFY MODE
          $stm = $DBH->prepare("SELECT * FROM imas_assessments WHERE id=:id");
          $stm->execute(array(':id'=>$assessmentId));
          $line = $stm->fetch(PDO::FETCH_ASSOC);
          $startdate = $line['startdate'];
          $enddate = $line['enddate'];
          $timelimit = round(abs($line['timelimit'])/60, 3);
          if ($line['isgroup']==0) {
              $line['groupsetid']=0;
              $line['groupmax']=6;
          }
          if ($line['deffeedbacktext']=='') {
              $usedeffb = false;
              $deffb = _("This assessment contains items that are not automatically graded.  Your grade may be inaccurate until your instructor grades these items.");
          } else {
              $usedeffb = true;
              $deffb = $line['deffeedbacktext'];
          }
          $savetitle = _("Save Changes");
          $extrefs = json_decode($line['extrefs'], true);
          if ($extrefs === null) {
          	$extrefs = array();
          }
      } else {  //INITIAL LOAD IN ADD MODE
          //set defaults
          $line['name'] = "";
          $line['summary'] = "";
          $line['intro'] = "";
					$line['avail'] = 1;
          $startdate = time()+60*60;
          $enddate = time() + 7*24*60*60;
          $line['startdate'] = $startdate;
          $line['enddate'] = $enddate;
					$line['date_by_lti'] = ($dates_by_lti==0)?0:1;
          $line['reviewdate'] = 0;
					$line['displaymethod']= isset($CFG['AMS2']['displaymethod'])?$CFG['AMS2']['displaymethod']:"skip";
					$line['submitby']= isset($CFG['AMS2']['submitby'])?$CFG['AMS2']['submitby']:"by_question";
          $line['defpoints'] = isset($CFG['AMS']['defpoints'])?$CFG['AMS']['defpoints']:10;
					$line['defregens'] = isset($CFG['AMS2']['defregens'])?$CFG['AMS2']['defregens']:20;
					$line['defregenpenalty'] = isset($CFG['AMS2']['defregenpenalty'])?$CFG['AMS2']['defregenpenalty']:0;
					$line['keepscore'] = isset($CFG['AMS2']['keepscore'])?$CFG['AMS2']['keepscore']:'best';
					$line['defattempts'] = isset($CFG['AMS2']['defattempts'])?$CFG['AMS2']['defattempts']:3;
					$line['defpenalty'] = isset($CFG['AMS']['defpenalty'])?$CFG['AMS']['defpenalty']:0;
					$line['showscores'] = isset($CFG['AMS2']['showscores'])?$CFG['AMS2']['showscores']:'during';
					$line['showans'] = isset($CFG['AMS2']['showans'])?$CFG['AMS2']['showans']:'after_lastattempt';
					$line['viewingb'] = isset($CFG['AMS2']['viewingb'])?$CFG['AMS2']['viewingb']:'immediately';
					$line['scoresingb'] = isset($CFG['AMS2']['scoresingb'])?$CFG['AMS2']['scoresingb']:'immediately';
					$line['ansingb'] = isset($CFG['AMS2']['ansingb'])?$CFG['AMS2']['ansingb']:'after_due';
					$line['gbcategory'] = 0;
					$line['caltag'] = isset($CFG['AMS']['caltag'])?$CFG['AMS']['caltag']:'?';
					$line['shuffle'] = isset($CFG['AMS']['shuffle'])?$CFG['AMS']['shuffle']:0;
					$line['noprint'] = isset($CFG['AMS']['noprint'])?$CFG['AMS']['noprint']:0;
					$line['showwork'] = isset($CFG['AMS']['showwork'])?$CFG['AMS']['showwork']:0;
          $line['istutorial'] = 0;
					$line['allowlate'] = isset($CFG['AMS']['allowlate'])?$CFG['AMS']['allowlate']:11;
          $line['LPcutoff'] = 0;
          $timelimit = 0;
					$line['overtime_grace'] = 0;
					$line['overtime_penalty'] = 0;
          $line['password'] = '';
					$line['showhints']=isset($CFG['AMS2']['showhints'])?$CFG['AMS2']['showhints']:3;
					$line['msgtoinstr'] = isset($CFG['AMS']['msgtoinstr'])?$CFG['AMS']['msgtoinstr']:0;
					$line['posttoforum'] = 0;
          $extrefs = array();
					$line['showtips'] = isset($CFG['AMS2']['showtips'])?$CFG['AMS2']['showtips']:2;
          $line['cntingb'] = 1;
					$line['minscore'] = isset($CFG['AMS']['minscore'])?$CFG['AMS']['minscore']:0;
					$usedeffb = false;
          $deffb = _("This assessment contains items that are not automatically graded.  Your grade may be inaccurate until your instructor grades these items.");
					$line['tutoredit'] = isset($CFG['AMS']['tutoredit'])?$CFG['AMS']['tutoredit']:0;
					$line['exceptionpenalty'] = isset($CFG['AMS']['exceptionpenalty'])?$CFG['AMS']['exceptionpenalty']:0;
					$line['defoutcome'] = 0;
					$line['isgroup'] = isset($CFG['AMS']['isgroup'])?$CFG['AMS']['isgroup']:0;
					$line['groupmax'] = isset($CFG['AMS']['groupmax'])?$CFG['AMS']['groupmax']:6;
					$line['groupsetid'] = 0;
					$line['reqscore'] = 0;
          $line['reqscoreaid'] = 0;
                    $line['reqscoretype'] = 0;
                    $line['showcat'] = 0;
                    $line['timelimit'] = 0;
					$taken = false;
          $savetitle = _("Create Assessment");

      }
      if (($introjson=json_decode($line['intro']))!==null) { //is json intro
          $line['intro'] = $introjson[0];
      } else {
          if (strpos($line['intro'], '[Q ')!==false || strpos($line['intro'], '[QUESTION ')!==false) {
              $introconvertmsg = sprintf(_('It appears this assessment is using an older [Q #] or [QUESTION #] tag. You can %sconvert that into a new format%s if you would like.'), '<a href="convertintro.php?cid='.$cid.'&aid='.Sanitize::onlyInt($_GET['id']).'">','</a>').'<br/>';
          }
      }
      if ($line['minscore']>10000) {
          $line['minscore'] -= 10000;
          $minscoretype = 1; //pct;
      } else {
          $minscoretype = 0; //points;
      }

      $hr = floor($coursedeftime/60)%12;
      $min = $coursedeftime%60;
      $am = ($coursedeftime<12*60)?'am':'pm';
      $deftime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
      $hr = floor($coursedefstime/60)%12;
      $min = $coursedefstime%60;
      $am = ($coursedefstime<12*60)?'am':'pm';
      $defstime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;

      // ALL BELOW IS COMMON TO MODIFY OR ADD MODE
      if ($startdate!=0) {
          $sdate = tzdate("m/d/Y",$startdate);
          $stime = tzdate("g:i a",$startdate);
      } else {
          $sdate = tzdate("m/d/Y",time());
          $stime = $defstime; //$stime = tzdate("g:i a",time());
      }
      if ($enddate!=2000000000) {
          $edate = tzdate("m/d/Y",$enddate);
          $etime = tzdate("g:i a",$enddate);
      } else {
          $edate = tzdate("m/d/Y",time()+7*24*60*60);
          $etime = $deftime; //tzdate("g:i a",time()+7*24*60*60);
      }
      if ($line['LPcutoff']==0) {
      	    if ($GLOBALS['courseenddate']<2000000000) { //default to course enddate, if set
      	    	    $lpdate = tzdate("m/d/Y",$GLOBALS['courseenddate']);
      	    } else {
      	    	    $lpdate = $edate;
      	    }
      	    $lptime = $etime;
      } else {
      	$lpdate = tzdate("m/d/Y", $line['LPcutoff']);
        $lptime = tzdate("g:i a", $line['LPcutoff']);
      }

      if (!isset($_GET['id'])) {
          $stime = $defstime;
          $etime = $deftime;
          $lptime = $deftime;
      }

      /*
			TODO: Do we need to keep supporting this?
			if ($line['defpenalty'][0]==='L') {
          $line['defpenalty'] = substr($line['defpenalty'],1);
          $skippenalty=10;
      } else
			*/
			if (is_string($line['defpenalty']) && $line['defpenalty'][0]==='S') {
				$defattemptpenalty = substr($line['defpenalty'],2);
				$defattemptpenalty_aftern = $line['defpenalty'][1];
      } else {
        $defattemptpenalty = $line['defpenalty'];
				$defattemptpenalty_aftern = 1;
      }
			if (is_string($line['defpenalty']) &&$line['defregenpenalty'][0]==='S') {
				$defregenpenalty = substr($line['defregenpenalty'],2);
				$defregenpenalty_aftern = $line['defregenpenalty'][1];
      } else {
        $defregenpenalty = $line['defregenpenalty'];
				$defregenpenalty_aftern = 1;
      }
      if ($line['reqscoreaid']==0) {
      	$reqscoredisptype=-1;
      } else if ($line['reqscore']<0 || $line['reqscoretype']&1) {
      	$reqscoredisptype=1;
      } else {
      	$reqscoredisptype=0;
      }
      if ($taken) {
          $page_isTakenMsg = "<p>This assessment has already been taken.  Modifying some settings will mess up those assessment attempts, and those inputs ";
          $page_isTakenMsg .=  "have been disabled.  If you want to change these settings, you should clear all existing assessment attempts</p>\n";
          $page_isTakenMsg .= "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='addassessment2.php?cid=$cid&id=".Sanitize::onlyInt($_GET['id'])."&clearattempts=ask'\"></p>\n";
      } else {
          $page_isTakenMsg = "<p>&nbsp;</p>";
      }

      if (isset($_GET['id'])) {
				$pageh1 = _('Modify Assessment');
			} else {
				$pageh1 = _('Add Assessment');
			}

      $page_formActionTag = sprintf("addassessment2.php?block=%s&cid=%s", Sanitize::encodeUrlParam($block), $cid);
      if (isset($_GET['id'])) {
          $page_formActionTag .= "&id=" . Sanitize::onlyInt($_GET['id']);
      }
      $page_formActionTag .= sprintf("&folder=%s&from=%s", Sanitize::encodeUrlParam($_GET['folder'] ?? '0'), Sanitize::encodeUrlParam($_GET['from'] ?? ''));
      $page_formActionTag .= "&tb=" . Sanitize::encodeUrlParam($totb);

			$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
      $stm->execute(array(':courseid'=>$cid));
      $otherAssessments = array();
      while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if (isset($_GET['id']) && $row[0]==$_GET['id']) { continue; }
				$otherAssessments[] = array(
					'value' => $row[0],
					'text' => $row[1]
				);
      }

      $stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
      $stm->execute(array(':courseid'=>$cid));
      $gbcats = array(array(
				'value' => 0,
				'text' => _('Default')
			));
      while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$gbcats[] = array(
					'value' => $row[0],
					'text' => $row[1]
				);
      }

      $stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
      $stm->execute(array(':courseid'=>$cid));
      $page_outcomes = array();
      while ($row = $stm->fetch(PDO::FETCH_NUM)) {
          $page_outcomes[$row[0]] = $row[1];
      }

			$outcomeOptions = array();
      if (count($page_outcomes)>0) {//there were outcomes
          $stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
          $stm->execute(array(':id'=>$cid));
          $outcomearr = unserialize($stm->fetchColumn(0));

          function flattenarr($ar) {
              global $page_outcomes, $outcomeOptions;
              foreach ($ar as $v) {
                  if (is_array($v)) { //outcome group
										$outcomeOptions[] = array(
											'value' => '',
											'text' => $v['name'],
											'isgroup' => true
										);
                    flattenarr($v['outcomes']);
                  } else {
										$outcomeOptions[] = array(
											'value' => $v,
											'text' => $page_outcomes[$v],
											'isgroup' => false
										);
                  }
              }
          }
          if ($outcomearr !== false) {
          	flattenarr($outcomearr);
          }
      }

      $page_groupsets = array();
      if ($taken && $line['isgroup']==0) {
          $query = "SELECT imas_stugroupset.id,imas_stugroupset.name FROM imas_stugroupset LEFT JOIN imas_stugroups ON imas_stugroups.groupsetid=imas_stugroupset.id ";
          $query .= "LEFT JOIN imas_stugroupmembers ON imas_stugroups.id=imas_stugroupmembers.stugroupid WHERE imas_stugroupset.courseid=:courseid ";
          $query .= "GROUP BY imas_stugroupset.id HAVING count(imas_stugroupmembers.id)=0";
          $stm = $DBH->prepare($query);
          $stm->execute(array(':courseid'=>$cid));
      } else {
          $stm = $DBH->prepare("SELECT id,name FROM imas_stugroupset WHERE courseid=:courseid");
          $stm->execute(array(':courseid'=>$cid));
      }
			$groupOptions = array(array(
				'value' => 0,
				'text' => _('Create new set of groups')
			));
      while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                if ($row[1] == '##autobysection##') { continue; }
				$groupOptions[] = array(
					'value' => $row[0],
					'text' => $row[1]
				);
      }

      $forums = array();
      $stm = $DBH->prepare("SELECT id,name FROM imas_forums WHERE courseid=:courseid ORDER BY name");
      $stm->execute(array(':courseid'=>$cid));
      while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$forums[] = array(
					'value' => $row[0],
					'text' => $row[1]
				);
      }

  } //END INITIAL LOAD BLOCK

}


//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js?v=080818\"></script>";
// $placeinhead .= '<script src="https://cdn.jsdelivr.net/npm/vue"></script>';
$placeinhead .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.10/vue.min.js"></script>';

 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  //ONLY INITIAL LOAD HAS DISPLAY

?>

	<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
	<div id="headeraddassessment" class="pagetitle">
		<h1><?php echo $pageh1; ?></h1>
	</div>

	<?php
	if (isset($_GET['id'])) {
		printf('<div class="cp"><a href="addquestions.php?aid=%d&amp;cid=%s" onclick="return confirm(\''
            . _('This will discard any changes you have made on this page').'\');">'
            . _('Add/Remove Questions').'</a></div>', Sanitize::onlyInt($_GET['id']), $cid);
	}
	?>
	<?php echo $page_isTakenMsg ?>

	<form method=post action="<?php echo $page_formActionTag ?>">

	<?php
		require("addassessment2form.php");
	?>

	<div class=submit><input type=submit value="<?php echo $savetitle;?>"></div>
	</form>
	<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
<?php
}
	require("../footer.php");
?>
