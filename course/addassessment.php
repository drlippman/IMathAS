<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require_once("../includes/TeacherAuditLog.php");

if ($courseUIver > 1) {
	if (!isset($_GET['id'])) {
		header(sprintf('Location: %s/course/addassessment2.php?cid=%s&r=' .Sanitize::randomQueryStringParam() ,
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

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
if ($from=='gb') {
	$curBreadcrumb .= "&gt; <a href=\"gradebook.php?cid=$cid\">Gradebook</a> ";
} else if ($from=='mcd') {
	$curBreadcrumb .= "&gt; <a href=\"masschgdates.php?cid=$cid\">Mass Change Dates</a> ";
}

if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; Modify Assessment\n";
} else {
	$curBreadcrumb .= "&gt; Add Assessment\n";
}

if (isset($_GET['id'])) {
	$stm = $DBH->prepare("SELECT courseid,ver FROM imas_assessments WHERE id=?");
	$stm->execute(array(intval($_GET['id'])));
	$row = $stm->fetch(PDO::FETCH_ASSOC);
	if ($row === null || $row['courseid'] != $_GET['cid']) {
		echo "Invalid ID";
		exit;
	} else if ($row['ver']>1) {
		if ($courseUIver==1) {
			echo "Uh oh - new version assessment in an old version course";
			exit;
		}
		header(sprintf('Location: %s/course/addassessment2.php?cid=%s&id=%d&r=' .Sanitize::randomQueryStringParam() ,
			$GLOBALS['basesiteurl'], $cid, $_GET['id']));
	}
}

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
        $assessmentId = Sanitize::onlyInt($_GET['id']);
        $cid = Sanitize::courseId($_GET['cid']);
        $block = $_GET['block'];

				if (isset($_GET['id'])) {
					$query = "SELECT COUNT(ias.id) FROM imas_assessment_sessions AS ias,imas_students WHERE ";
					$query .= "ias.assessmentid=:assessmentid AND ias.userid=imas_students.userid AND imas_students.courseid=:courseid";
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
            if (isset($_POST['clearattempts']) && $_POST['clearattempts']=="confirmed") {
            	$DBH->beginTransaction();
                require_once('../includes/filehandler.php');
                deleteallaidfiles($assessmentId);
								$grades = array();
								$stm = $DBH->prepare("SELECT userid,bestscores FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
				        $stm->execute(array(':assessmentid'=>$assessmentId));
				        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				          $sp = explode(';', $row['bestscores']);
				          $as = str_replace(array('-1','-2','~'), array('0','0',','), $sp[0]);
				          $total = array_sum(explode(',', $as));
				          $grades[$row['userid']] = $total;
				        }
                $stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
                $stm->execute(array(':assessmentid'=>$assessmentId));
								if ($stm->rowCount()>0) {
					        TeacherAuditLog::addTracking(
					          $cid,
					          "Clear Attempts",
					          $assessmentId,
					          array('grades'=>$grades)
					        );
					      }

								$stm = $DBH->prepare("DELETE FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
                $stm->execute(array(':assessmentid'=>$assessmentId));
                $stm = $DBH->prepare("UPDATE imas_questions SET withdrawn=0 WHERE assessmentid=:assessmentid");
                $stm->execute(array(':assessmentid'=>$assessmentId));
                $DBH->commit();
                header(sprintf('Location: %s/course/addassessment.php?cid=%s&id=%d&r=' .Sanitize::randomQueryStringParam() , $GLOBALS['basesiteurl'],
                        $cid, $assessmentId));
                exit;
            } else {
                $overwriteBody = 1;
                $stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
                $stm->execute(array(':id'=>$assessmentId));
                $assessmentname = $stm->fetchColumn(0);
                $body = sprintf("<div class=breadcrumb>%s <a href=\"course.php?cid=%s\">%s</a> ", $breadcrumbbase,
                    $cid, Sanitize::encodeStringForDisplay($coursename));
                $body .= sprintf("&gt; <a href=\"addassessment.php?cid=%s&id=%d\">Modify Assessment</a> &gt; Clear Attempts</div>\n",
                    $cid, $assessmentId);
			$body .= sprintf("<h2>%s</h2>", Sanitize::encodeStringForDisplay($assessmentname));
                $body .= "<p>Are you SURE you want to delete all attempts (grades) for this assessment?</p>";
                $body .= '<form method="POST" action="'.sprintf('addassessment.php?cid=%s&id=%d',$cid, $assessmentId).'">';
                $body .= '<p><button type=submit name=clearattempts value=confirmed>'._('Yes, Clear').'</button>';
                $body .= sprintf("<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='addassessment.php?cid=%s&id=%d'\"></p>\n",
                    $cid, $assessmentId);
                $body .= '</form>';
            }
        } elseif (!empty($_POST['name'])) { //if the form has been submitted
					$assessName = Sanitize::stripHtmlTags($_POST['name']);
	        if ($assessName == '') {
	        	$assessName = _('Unnamed Assessment');
	        }
	        $displayMethod = Sanitize::stripHtmlTags($_POST['displaymethod']);
	        $defpoints = Sanitize::onlyInt($_POST['defpoints']);
	        $cntingb_int = Sanitize::onlyInt($_POST['cntingb']);
	        $assmpassword = Sanitize::stripHtmlTags($_POST['assmpassword']);
	        $grdebkcat = Sanitize::onlyInt($_POST['gbcat']);
	        $grpmax = Sanitize::onlyInt($_POST['groupmax']);
	        $shwqcat = Sanitize::onlyInt($_POST['showqcat']);
	        $eqnhelper = Sanitize::onlyInt($_POST['eqnhelper']);
	        $showtips = Sanitize::onlyInt($_POST['showtips']);
	        $grpsetid = Sanitize::onlyInt($_POST['groupsetid']);
	        $reqscore = Sanitize::onlyInt($_POST['reqscore']);
	        $allowlate = Sanitize::onlyInt($_POST['allowlate']);
	        $exceptpenalty = Sanitize::onlyInt($_POST['exceptionpenalty']);
	        $ltisecret = Sanitize::stripHtmlTags($_POST['ltisecret']);
	        $posttoforum = Sanitize::onlyInt($_POST['posttoforum']);
	        $defoutcome = Sanitize::onlyInt($_POST['defoutcome']);

        	$DBH->beginTransaction();
            require_once("../includes/parsedatetime.php");
            if ($_POST['avail']==1) {
                if ($_POST['sdatetype']=='0') {
                    $startdate = 0;
                } else {
                    $startdate = parsedatetime($_POST['sdate'],$_POST['stime'],0);
                }
                if ($_POST['edatetype']=='2000000000') {
                    $enddate = 2000000000;
                } else {
                    $enddate = parsedatetime($_POST['edate'],$_POST['etime'],2000000000);
                }

                if (empty($_POST['doreview'])) {
                    $reviewdate = 0;
                } else if ($_POST['doreview']=='2000000000') {
                    $reviewdate = 2000000000;
                }
            } else {
                $startdate = 0;
                $enddate = 2000000000;
                $reviewdate = 0;
            }
            if (isset($_POST['dolpcutoff']) && trim($_POST['lpdate']) != '' && trim($_POST['lptime']) != '') {
            	$LPcutoff = parsedatetime($_POST['lpdate'],$_POST['lptime'],0);
            	if (tzdate("m/d/Y",$GLOBALS['courseenddate']) == tzdate("m/d/Y", $LPcutoff) || $LPcutoff<$enddate) {
            		$LPcutoff = 0; //don't really set if it matches course end date or is before
            	}
            } else {
            	$LPcutoff = 0;
            }

            $shuffle = Sanitize::onlyInt($_POST['shuffle']);
            if (isset($_POST['sameseed'])) { $shuffle += 2;}
            if (isset($_POST['samever'])) { $shuffle += 4;}
            if (isset($_POST['reattemptsdiffver']) && $_POST['deffeedback']!="Practice" && $_POST['deffeedback']!="Homework") {
                $shuffle += 8;
            }

            if ($_POST['minscoretype']==1 && trim($_POST['minscore'])!='' && $_POST['minscore']>0) {
                $_POST['minscore'] = intval($_POST['minscore'])+10000;
            }

            $isgroup = Sanitize::onlyInt($_POST['isgroup']);

            if (isset($_POST['showhints'])) {
                $showhints = 1;
            } else {
                $showhints = 0;
            }

            if (isset($_POST['istutorial'])) {
                $istutorial = 1;
            } else {
                $istutorial = 0;
            }

            $tutoredit = Sanitize::onlyInt($_POST['tutoredit']);

            $allowlate = intval($allowlate);
            if (isset($_POST['latepassafterdue']) && $allowlate>0) {
                $allowlate += 10;
            }

            $timelimit = round(Sanitize::onlyFloat($_POST['timelimit'])*60);
            if (isset($_POST['timelimitkickout'])) {
                $timelimit = -1*$timelimit;
            }

            if (isset($_POST['usedeffb'])) {
                $deffb = Sanitize::incomingHtml($_POST['deffb']);
            } else {
                $deffb = '';
            }

            if ($_POST['deffeedback']=="Practice" || $_POST['deffeedback']=="Homework") {
                $deffeedback = Sanitize::simpleString($_POST['deffeedback']).'-'.Sanitize::simpleString($_POST['showansprac']);
            } else {
                $deffeedback = Sanitize::simpleString($_POST['deffeedback']).'-'.Sanitize::simpleString($_POST['showans']);
            }
            if (!isset($_POST['doposttoforum'])) {
                $posttoforum = 0;
            }
            if (isset($_POST['msgtoinstr'])) {
                $msgtoinstr = 1;
            } else {
                $msgtoinstr = 0;
            }
            $defpenalty = Sanitize::onlyFloat($_POST['defpenalty']);
            $skippenalty_post = Sanitize::onlyInt($_POST['skippenalty']);
            if ($skippenalty_post==10) {
                $defpenalty = 'L'.$defpenalty;
            } else if ($skippenalty_post>0) {
                $defpenalty = 'S'.$skippenalty_post.$defpenalty;
            }

            $extrefs = array();
            $labelkeys = preg_grep('/extreflabel/', array_keys($_POST));
            foreach ($labelkeys as $extkey) {
            	$linkkey = str_replace('label','link',$extkey);
            	$_POST[$extkey] = trim(Sanitize::stripHtmlTags($_POST[$extkey]));
            	$_POST[$linkkey] = trim(Sanitize::url($_POST[$linkkey]));
            	if ($_POST[$extkey] != '' && $_POST[$linkkey] != '') {
            		$extrefs[] = array(
            			'label' => $_POST[$extkey],
            			'link' => $_POST[$linkkey]
            		);
            	}
            }
            $extrefencoded = json_encode($extrefs);

				if ($_POST['reqscoreshowtype']==-1 || $reqscore==0) {
					$reqscore = 0;
					$reqscoretype = 0;
					$_POST['reqscoreaid'] = 0;
				} else {
					$reqscoretype = 0;
					if ($_POST['reqscoreshowtype']==1) {
						$reqscoretype |= 1;
					}
					if ($_POST['reqscorecalctype']==1) {
						$reqscoretype |= 2;
					}
				}

        $defattempts = Sanitize::onlyFloat($_POST['defattempts']);
        $copyFromId = Sanitize::onlyInt($_POST['copyfrom']);
        if (!empty($copyFromId)) {
                $stm = $DBH->prepare("SELECT timelimit,minscore,displaymethod,defpoints,defattempts,defpenalty,deffeedback,shuffle,gbcategory,password,cntingb,tutoredit,showcat,intro,summary,startdate,enddate,reviewdate,isgroup,groupmax,groupsetid,showhints,reqscore,reqscoreaid,reqscoretype,noprint,allowlate,eqnhelper,endmsg,caltag,calrtag,deffeedbacktext,showtips,exceptionpenalty,ltisecret,msgtoinstr,posttoforum,istutorial,defoutcome,extrefs FROM imas_assessments WHERE id=:id");
                $stm->execute(array(':id'=>$copyFromId));

                list($timelimit,$_POST['minscore'],$displayMethod,$defpoints,$defattempts,$defpenalty,$deffeedback,$shuffle,$grdebkcat,$assmpassword,$cntingb_int,$tutoredit,$shwqcat,$cpintro,$cpsummary,$cpstartdate,$cpenddate,$cpreviewdate,$isgroup,$grpmax,$grpsetid,$showhints,$reqscore,$_POST['reqscoreaid'],$reqscoretype,$_POST['noprint'],$allowlate,$eqnhelper,$endmsg,$_POST['caltagact'],$_POST['caltagrev'],$deffb,$showtips,$exceptpenalty,$ltisecret,$msgtoinstr,$posttoforum,$istutorial,$defoutcome,$extrefencoded) = $stm->fetch(PDO::FETCH_NUM);
                if (isset($_POST['copyinstr'])) {
                    if (($introjson=json_decode($cpintro))!==null) { //is json intro
                        $_POST['intro'] = $introjson[0];
                    } else {
                        $_POST['intro'] = $cpintro;
                    }
                }
                if (isset($_POST['copysummary'])) {
                    $_POST['summary'] = $cpsummary;
                }
                if (isset($_POST['copydates'])) {
                    $startdate = $cpstartdate;
                    $enddate = $cpenddate;
                    $reviewdate = $cpreviewdate;
                }
                if (isset($_POST['removeperq'])) {
                    $stm = $DBH->prepare("UPDATE imas_questions SET points=9999,attempts=9999,penalty=9999,regen=0,showans=0 WHERE assessmentid=:assessmentid");
                    $stm->execute(array(':assessmentid'=>$assessmentId));
                }
            }
            if ($deffeedback=="Practice") {
                $cntingb_int = Sanitize::onlyInt($_POST['pcntingb']);
            }
            if (isset($ltisecret)) {
                $ltisecret = trim($ltisecret);
            } else {
                $ltisecret = '';
            }

            //is updating, switching from nongroup to group, and not creating new groupset, check if groups and asids already exist
            //if so, cannot handle
            $updategroupset='';
            if (isset($_GET['id']) && $_POST['isgroup']>0 && $grpsetid>0) {
                $isok = true;
                $stm = $DBH->prepare("SELECT isgroup FROM imas_assessments WHERE id=:id");
                $stm->execute(array(':id'=>$assessmentId));
                if ($stm->fetchColumn(0)==0) {
                    //check to see if students have already started assessment
                    //don't really care if groups exist - just whether asids exist
                    //$query = "SELECT id FROM imas_stugroups WHERE groupsetid='{$_POST['groupsetid']}'";
                    $query = "SELECT COUNT(ias.id) FROM imas_assessment_sessions AS ias,imas_students WHERE ";
                    $query .= "ias.assessmentid=:assessmentid AND ias.userid=imas_students.userid AND imas_students.courseid=:courseid";
                    $stm = $DBH->prepare($query);
                    $stm->execute(array(':assessmentid'=>$assessmentId, ':courseid'=>$cid));
                    if ($stm->fetchColumn(0)>0) {
                        echo "Sorry, cannot switch to use pre-defined groups after students have already started the assessment";
                        exit;
                    }
                }
                $updategroupset = Sanitize::onlyInt($grpsetid);

            }

            if ($_POST['isgroup']>0 && isset($_POST['groupsetid']) && $grpsetid==0) {
                //create new groupset
                $stm = $DBH->prepare("INSERT INTO imas_stugroupset (courseid,name) VALUES (:courseid, :name)");
                $stm->execute(array(':courseid'=>$cid, ':name'=>'Group set for '.$assessName));
                $grpsetid = $DBH->lastInsertId();
                $updategroupset = $grpsetid;
            }


            $caltag = Sanitize::stripHtmlTags($_POST['caltagact']);
            $calrtag = 'R'; //not used anymore Sanitize::stripHtmlTags($_POST['caltagrev']);

						if ($_POST['summary']=='<p>Enter summary here (shows on course page)</p>' || $_POST['summary']=='<p></p>') {
							$_POST['summary'] = '';
						} else {
							$_POST['summary'] = Sanitize::incomingHtml($_POST['summary']);
						}
						if ($_POST['intro']=='<p>Enter intro/instructions</p>' || $_POST['intro']=='<p></p>') {
							$_POST['intro'] = '';
						} else {
							$_POST['intro'] = Sanitize::incomingHtml($_POST['intro']);
						}

						if (isset($_GET['id'])) {  //already have id; update
							$stm = $DBH->prepare("SELECT * FROM imas_assessments WHERE id=:id");
							$stm->execute(array(':id'=>$_GET['id']));
							$curassess = $stm->fetch(PDO::FETCH_ASSOC);

                if ($isgroup==0) { //set agroupid=0 if switching from groups to not groups
                    if ($curassess['isgroup']>0) {
                        $stm = $DBH->prepare("UPDATE imas_assessment_sessions SET agroupid=0 WHERE assessmentid=:assessmentid");
                        $stm->execute(array(':assessmentid'=>$assessmentId));
                    }
                } else { //if switching from nogroup to groups and groups already exist, need set agroupids if asids exist already
                    //NOT ALLOWED CURRENTLY
                }
                if (($introjson=json_decode($curassess['intro']))!==null) { //is json intro
                    $introjson[0] = $_POST['intro'];
                    $_POST['intro'] = json_encode($introjson);
                }
                $query = "UPDATE imas_assessments SET name=:name,summary=:summary,intro=:intro,timelimit=:timelimit,minscore=:minscore,isgroup=:isgroup,showhints=:showhints,tutoredit=:tutoredit,eqnhelper=:eqnhelper,showtips=:showtips,";
                $query .= "displaymethod=:displaymethod,defattempts=:defattempts,deffeedback=:deffeedback,shuffle=:shuffle,gbcategory=:gbcategory,password=:password,cntingb=:cntingb,showcat=:showcat,caltag=:caltag,calrtag=:calrtag,";
                $query .= "reqscore=:reqscore,reqscoreaid=:reqscoreaid,reqscoretype=:reqscoretype,noprint=:noprint,avail=:avail,groupmax=:groupmax,allowlate=:allowlate,exceptionpenalty=:exceptionpenalty,ltisecret=:ltisecret,deffeedbacktext=:deffeedbacktext,";
                $query .= "msgtoinstr=:msgtoinstr,posttoforum=:posttoforum,istutorial=:istutorial,defoutcome=:defoutcome,LPcutoff=:LPcutoff,extrefs=:extrefs";
                $qarr = array(':name'=>$assessName, ':summary'=>$_POST['summary'], ':intro'=>$_POST['intro'], ':timelimit'=>$timelimit,
                    ':minscore'=>$_POST['minscore'], ':isgroup'=>$isgroup, ':showhints'=>$showhints, ':tutoredit'=>$tutoredit,
                    ':eqnhelper'=>$eqnhelper, ':showtips'=>$showtips, ':displaymethod'=>$displayMethod,
                    ':defattempts'=>$defattempts, ':deffeedback'=>$deffeedback, ':shuffle'=>$shuffle, ':gbcategory'=>$grdebkcat,
                    ':password'=>$assmpassword, ':cntingb'=>$cntingb_int, ':showcat'=>$shwqcat, ':caltag'=>$caltag,
                    ':calrtag'=>$calrtag, ':reqscore'=>$reqscore, ':reqscoreaid'=>$_POST['reqscoreaid'], ':reqscoretype'=>$reqscoretype, ':noprint'=>$_POST['noprint'],
                    ':avail'=>$_POST['avail'], ':groupmax'=>$grpmax, ':allowlate'=>$allowlate,
                    ':exceptionpenalty'=>$exceptpenalty, ':ltisecret'=>$ltisecret, ':deffeedbacktext'=>$deffb,
                    ':msgtoinstr'=>$msgtoinstr, ':posttoforum'=>$posttoforum, ':istutorial'=>$istutorial,
                    ':defoutcome'=>$defoutcome, ':LPcutoff'=>$LPcutoff, ':extrefs'=>$extrefencoded);

                if ($updategroupset!='') {
                    $query .= ",groupsetid=:groupsetid";
                    $qarr[':groupsetid'] = $updategroupset;
                }
                if (isset($_POST['defpenalty'])) {
                    $query .= ",defpenalty=:defpenalty";
                    $qarr[':defpenalty'] = $defpenalty;
                }
                if (isset($_POST['defpoints']) && $defpoints>0) {
                    $query .= ",defpoints=:defpoints";
                    $qarr[':defpoints'] = $defpoints;
                }
                if (isset($_POST['copyendmsg'])) {
                    $query .= ",endmsg=:endmsg";
                    $qarr[':endmsg'] = $endmsg;
                }
                if ($_POST['avail']==1) {
                    if ($dates_by_lti==0) {
                        $query .= ",startdate=:startdate,enddate=:enddate,reviewdate=:reviewdate";
                        $qarr[':startdate'] = $startdate;
                        $qarr[':enddate'] = $enddate;
                    } else {
                        $query .= ",reviewdate=:reviewdate";
                    }
                    $qarr[':reviewdate'] = $reviewdate;
                }
                $query .= " WHERE id=:id AND courseid=:cid";
                $qarr[':id'] = $assessmentId;
                $qarr[':cid'] = $cid;
			$stm = $DBH->prepare($query);
			$stm->execute($qarr);

			if ($taken && $stm->rowCount()>0) {
				$metadata = array();
				foreach ($curassess as $k=>$v) {
					if (isset($qarr[':'.$k]) && $qarr[':'.$k] != $v) {
						$metadata[$k] = ['old'=>$v, 'new'=>$qarr[':'.$k]];
					}
				}
				$result = TeacherAuditLog::addTracking(
				    $cid,
				    "Assessment Settings Change",
				    $assessmentId,
				    $metadata
				);

				//update ptsposs field
				if (isset($_POST['defpoints'])) {
					require_once("../includes/updateptsposs.php");
					updatePointsPossible($_GET['id'], $curassess['itemorder'], $_POST['defpoints']);
				}
			}

			if ($deffb!=$curassess['deffeedbacktext']) {
				//removed default feedback text; remove it from existing attempts
				$updatefb = $DBH->prepare("UPDATE imas_assessment_sessions SET feedback=? WHERE id=?");
				$stm = $DBH->prepare("SELECT id,feedback FROM imas_assessment_sessions WHERE assessmentid=?");
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
				header(sprintf('Location: %s/course/course.php?cid=%s&r=%s', $GLOBALS['basesiteurl'], $cid, $rqp.$btf));
			}
			exit;
		} else { //add new
			if (!isset($_POST['copyendmsg'])) {
				$endmsg = '';
			}
			if ($dates_by_lti>0) {
				$datebylti = 1;
			} else {
				$datebylti = 0;
			}
			if (empty($defpoints)) {
				$defpoints = isset($CFG['AMS']['defpoints'])?$CFG['AMS']['defpoints']:10;
			}

                $query = "INSERT INTO imas_assessments (courseid,name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,";
                $query .= "displaymethod,defpoints,defattempts,defpenalty,deffeedback,shuffle,gbcategory,password,cntingb,tutoredit,showcat,";
		$query .= "eqnhelper,showtips,caltag,calrtag,isgroup,groupmax,groupsetid,showhints,reqscore,reqscoreaid,reqscoretype,noprint,avail,allowlate,";
                $query .= "LPcutoff,exceptionpenalty,ltisecret,endmsg,deffeedbacktext,msgtoinstr,posttoforum,istutorial,defoutcome,extrefs,ptsposs,date_by_lti) VALUES ";
                $query .= "(:courseid, :name, :summary, :intro, :startdate, :enddate, :reviewdate, :timelimit, :minscore, :displaymethod, ";
                $query .= ":defpoints, :defattempts, :defpenalty, :deffeedback, :shuffle, :gbcategory, :password, :cntingb, :tutoredit, ";
                $query .= ":showcat, :eqnhelper, :showtips, :caltag, :calrtag, :isgroup, :groupmax, :groupsetid, :showhints, :reqscore, ";
			$query .= ":reqscoreaid, :reqscoretype, :noprint, :avail, :allowlate, :LPcutoff, :exceptionpenalty, :ltisecret, :endmsg, :deffeedbacktext, :msgtoinstr, ";
                $query .= ":posttoforum, :istutorial, :defoutcome, :extrefs, 0, :datebylti)";
                $stm = $DBH->prepare($query);
                $stm->execute(array(':courseid'=>$cid, ':name'=>$assessName, ':summary'=>$_POST['summary'], ':intro'=>$_POST['intro'],
                    ':startdate'=>$startdate, ':enddate'=>$enddate, ':reviewdate'=>$reviewdate, ':timelimit'=>$timelimit,
                    ':minscore'=>$_POST['minscore'], ':displaymethod'=>$displayMethod, ':defpoints'=>$defpoints,
                    ':defattempts'=>$defattempts, ':defpenalty'=>$defpenalty, ':deffeedback'=>$deffeedback,
                    ':shuffle'=>$shuffle, ':gbcategory'=>$grdebkcat, ':password'=>$assmpassword, ':cntingb'=>$cntingb_int,
                    ':tutoredit'=>$tutoredit, ':showcat'=>$shwqcat, ':eqnhelper'=>$eqnhelper, ':showtips'=>$showtips,
                    ':caltag'=>$caltag, ':calrtag'=>$calrtag, ':isgroup'=>$isgroup, ':groupmax'=>$grpmax,
                    ':groupsetid'=>$grpsetid, ':showhints'=>$showhints, ':reqscore'=>$reqscore,
                    ':reqscoreaid'=>$_POST['reqscoreaid'], ':reqscoretype'=>$reqscoretype, ':noprint'=>$_POST['noprint'], ':avail'=>$_POST['avail'],
                    ':allowlate'=>$allowlate, ':LPcutoff'=>$LPcutoff, ':exceptionpenalty'=>$exceptpenalty, ':ltisecret'=>$ltisecret,
                    ':endmsg'=>$endmsg, ':deffeedbacktext'=>$deffb, ':msgtoinstr'=>$msgtoinstr, ':posttoforum'=>$posttoforum,
                    ':istutorial'=>$istutorial, ':defoutcome'=>$defoutcome, ':extrefs'=>$extrefencoded, ':datebylti'=>$datebylti));
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
                list($testtype,$showans) = explode('-',$line['deffeedback']);
                $startdate = $line['startdate'];
                $enddate = $line['enddate'];
                $gbcat = $line['gbcategory'];
                if ($testtype=='Practice') {
                    $pcntingb = $line['cntingb'];
                    $cntingb = 1;
                } else {
                    $cntingb = $line['cntingb'];
                    $pcntingb = 3;
                }
                $showqcat = $line['showcat'];
                $timelimit = $line['timelimit']/60;
                if ($line['isgroup']==0) {
                    $line['groupsetid']=0;
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
                $startdate = time()+60*60;
                $enddate = time() + 7*24*60*60;
                $line['startdate'] = $startdate;
                $line['enddate'] = $enddate;
                $line['LPcutoff'] = 0;
                $line['avail'] = 1;
                $line['reviewdate'] = 0;
                $timelimit = 0;
                $line['displaymethod']= isset($CFG['AMS']['displaymethod'])?$CFG['AMS']['displaymethod']:"SkipAround";
                $line['defpoints'] = isset($CFG['AMS']['defpoints'])?$CFG['AMS']['defpoints']:10;
                $line['defattempts'] = isset($CFG['AMS']['defattempts'])?$CFG['AMS']['defattempts']:1;
                $line['password'] = '';
                //$line['deffeedback'] = "AsGo";
                $testtype = isset($CFG['AMS']['testtype'])?$CFG['AMS']['testtype']:"AsGo";
                $showans = isset($CFG['AMS']['showans'])?$CFG['AMS']['showans']:"A";
                $line['defpenalty'] = isset($CFG['AMS']['defpenalty'])?$CFG['AMS']['defpenalty']:10;
                $line['shuffle'] = isset($CFG['AMS']['shuffle'])?$CFG['AMS']['shuffle']:0;
                $line['minscore'] = isset($CFG['AMS']['minscore'])?$CFG['AMS']['minscore']:0;
                $line['isgroup'] = isset($CFG['AMS']['isgroup'])?$CFG['AMS']['isgroup']:0;
                $line['showhints']=isset($CFG['AMS']['showhints'])?$CFG['AMS']['showhints']:1;
                $line['reqscore'] = 0;
                $line['reqscoreaid'] = 0;
                $line['groupsetid'] = 0;
                $line['noprint'] = isset($CFG['AMS']['noprint'])?$CFG['AMS']['noprint']:0;
                $line['groupmax'] = isset($CFG['AMS']['groupmax'])?$CFG['AMS']['groupmax']:6;
                $line['allowlate'] = isset($CFG['AMS']['allowlate'])?$CFG['AMS']['allowlate']:11;
                $line['exceptionpenalty'] = isset($CFG['AMS']['exceptionpenalty'])?$CFG['AMS']['exceptionpenalty']:0;
                $line['tutoredit'] = isset($CFG['AMS']['tutoredit'])?$CFG['AMS']['tutoredit']:0;
                $line['eqnhelper'] = isset($CFG['AMS']['eqnhelper'])?$CFG['AMS']['eqnhelper']:0;
                $line['ltisecret'] = '';
                $line['caltag'] = isset($CFG['AMS']['caltag'])?$CFG['AMS']['caltag']:'?';
                $line['showtips'] = isset($CFG['AMS']['showtips'])?$CFG['AMS']['showtips']:2;
                $usedeffb = false;
                $deffb = _("This assessment contains items that are not automatically graded.  Your grade may be inaccurate until your instructor grades these items.");
                $gbcat = 0;
                $cntingb = 1;
                $pcntingb = 3;
                $showqcat = 0;
                $line['posttoforum'] = 0;
                $line['msgtoinstr'] = isset($CFG['AMS']['msgtoinstr'])?$CFG['AMS']['msgtoinstr']:0;
                $line['defoutcome'] = 0;
                $taken = false;
                $line['reqscoretype'] = 0;
                $line['date_by_lti'] = ($dates_by_lti==0)?0:1;
                $savetitle = _("Create Assessment");
                $extrefs = array();
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

            if ($line['defpenalty']{0}==='L') {
                $line['defpenalty'] = substr($line['defpenalty'],1);
                $skippenalty=10;
            } else if ($line['defpenalty']{0}==='S') {
                $skippenalty = $line['defpenalty']{1};
                $line['defpenalty'] = substr($line['defpenalty'],2);
            } else {
                $skippenalty = 0;
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
                $page_isTakenMsg .= "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='addassessment.php?cid=$cid&id=".Sanitize::onlyInt($_GET['id'])."&clearattempts=ask'\"></p>\n";
            } else {
                $page_isTakenMsg = "<p>&nbsp;</p>";
            }

            if (isset($_GET['id'])) {
			$formTitle = "<div id=\"headeraddassessment\" class=\"pagetitle\"><h1>Modify Assessment <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h1></div>\n";
            } else {
			$formTitle = "<div id=\"headeraddassessment\" class=\"pagetitle\"><h1>Add Assessment <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h1></div>\n";
            }

            $page_formActionTag = sprintf("addassessment.php?block=%s&cid=%s", Sanitize::encodeUrlParam($block), $cid);
            if (isset($_GET['id'])) {
                $page_formActionTag .= "&id=" . Sanitize::onlyInt($_GET['id']);
            }
            $page_formActionTag .= sprintf("&folder=%s&from=%s", Sanitize::encodeUrlParam($_GET['folder']), Sanitize::encodeUrlParam($_GET['from']));
            $page_formActionTag .= "&tb=" . Sanitize::encodeUrlParam($totb);
            $stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
            $stm->execute(array(':courseid'=>$cid));
            $page_copyFromSelect = array('val'=>array(), 'label'=>array());
            $i=0;
            if ($stm->rowCount()>0) {
                while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                    $page_copyFromSelect['val'][$i] = $row[0];
                    $page_copyFromSelect['label'][$i] = $row[1];
                    $i++;
                }
            }
            $stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
            $stm->execute(array(':courseid'=>$cid));
            $page_gbcatSelect = array();
            $i=0;
            if ($stm->rowCount()>0) {
                while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                    $page_gbcatSelect['val'][$i] = $row[0];
                    $page_gbcatSelect['label'][$i] = $row[1];
                    $i++;
                }
            }
            $stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
            $stm->execute(array(':courseid'=>$cid));
            $page_outcomes = array();
            $i=0;
            if ($stm->rowCount()>0) {
                while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                    $page_outcomes[$row[0]] = $row[1];
                    $i++;
                }
            }
            $page_outcomes[0] = 'No default outcome selected';

            $page_outcomeslist = array(array(0,0));
            if ($i>0) {//there were outcomes
                $stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
                $stm->execute(array(':id'=>$cid));
                $outcomearr = unserialize($stm->fetchColumn(0));

                function flattenarr($ar) {
                    global $page_outcomeslist;
                    foreach ($ar as $v) {
                        if (is_array($v)) { //outcome group
                            $page_outcomeslist[] = array($v['name'], 1);
                            flattenarr($v['outcomes']);
                        } else {
                            $page_outcomeslist[] = array($v, 0);
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

            $page_groupsets['val'][0] = 0;
            $page_groupsets['label'][0] = 'Create new set of groups';
            $i=1;
            while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                $page_groupsets['val'][$i] = $row[0];
                $page_groupsets['label'][$i] = $row[1];
                $i++;
            }

            $page_tutorSelect['label'] = array("No access","View Scores","View and Edit Scores");
            $page_tutorSelect['val'] = array(2,0,1);

            $page_forumSelect = array();
            $stm = $DBH->prepare("SELECT id,name FROM imas_forums WHERE courseid=:courseid ORDER BY name");
            $stm->execute(array(':courseid'=>$cid));
            $page_forumSelect['val'][0] = 0;
            $page_forumSelect['label'][0] = "None";
            while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                $page_forumSelect['val'][] = $row[0];
                $page_forumSelect['label'][] = $row[1];
            }

            $page_allowlateSelect = array();
            $page_allowlateSelect['val'][0] = 0;
            $page_allowlateSelect['label'][0] = "None";
            $page_allowlateSelect['val'][1] = 1;
            $page_allowlateSelect['label'][1] = "Unlimited";
            for ($k=1;$k<9;$k++) {
                $page_allowlateSelect['val'][] = $k+1;
                $page_allowlateSelect['label'][] = "Up to $k";
            }

        } //END INITIAL LOAD BLOCK

    }


//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
 $placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js?v=080818\"></script>";
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  //ONLY INITIAL LOAD HAS DISPLAY

?>
	<style type="text/css">
	span.hidden {
		display: none;
	}
	span.show {
		display: inline;
	}
	</style>

	<script>
	function chgfb() {
		if (document.getElementById("deffeedback").value=="Practice" || document.getElementById("deffeedback").value=="Homework") {
			document.getElementById("showanspracspan").className = "show";
			document.getElementById("showansspan").className = "hidden";
			document.getElementById("showreattdiffver").className = "hidden";
		} else {
			document.getElementById("showanspracspan").className = "hidden";
			document.getElementById("showansspan").className = "show";
			document.getElementById("showreattdiffver").className = "show";
		}
		if (document.getElementById("deffeedback").value=="Practice") {
			document.getElementById("stdcntingb").className = "hidden";
			document.getElementById("praccntingb").className = "formright";
		} else {
			document.getElementById("stdcntingb").className = "formright";
			document.getElementById("praccntingb").className = "hidden";
		}
	}
	function chgcopyfrom() {
		if (document.getElementById('copyfrom').value==0) {
			document.getElementById('customoptions').className="show";
			document.getElementById('copyfromoptions').className="hidden";
		} else {
			document.getElementById('customoptions').className="hidden";
			document.getElementById('copyfromoptions').className="show";
		}
	}
	function apwshowhide(s) {
		var el = document.getElementById("assmpassword");
		if (el.type == "password") {
			el.type = "text";
			s.innerHTML = "Hide";
		} else {
			el.type = "password";
			s.innerHTML = "Show";
		}
	}
	var newextrefcnt = 0;
	function addextref(el) {
		var html = '<span class="aextref">';
		html += '<label for=newextreflabel'+newextrefcnt+'>Label:</label>';
		html += '<input id=newextreflabel'+newextrefcnt+' name=newextreflabel'+newextrefcnt+' size=10 /> ';
		html += '<label for=newextreflink'+newextrefcnt+'>Link:</label>';
		html += '<input id=newextreflink'+newextrefcnt+' name=newextreflink'+newextrefcnt+' size=30 />';
		html += '<button type=button onclick="removeextref(this)">Remove</button><br/></span>';
		newextrefcnt++;
		$(el).before(html);
	}
	function removeextref(el) {
		$(el).closest(".aextref").remove();
	}
	$(function() {
		$("input[name=dolpcutoff]").on("click", function() {
			var chk = $(this).is(":checked");
			$("#lpcutoffwrap").toggle(chk);
			$(this).attr("aria-expanded", chk);
		});
		$("#reqscoreshowtype").attr("aria-controls", "reqscorewrap")
			.attr("aria-expanded", $("#reqscoreshowtype").val()>-1)
			.on("change", function() {
				var rqshow = ($(this).val()>-1);
				$("#reqscorewrap").toggle(rqshow);
				$(this).attr("aria-expanded", rqshow);
		});
		// bind to caltagradio controls
		$('input[type=radio][name=caltagradio]').change(function() {
			if (this.value == 'usename') {
				$('input[type=text][name=caltagact]')
                    .attr('data-prev', function() {return this.value;})
                    .prop('readonly', true)
                    .css({'color':'#FFFFFF', 'opacity':'0.6'})
                    .val('use_name');
			}
			else if (this.value == 'usetext') {
				$('input[type=text][name=caltagact]')
                    .prop('readonly', false)
                    .css({'color':'inherit', 'opacity':'1.0'})
                    .val(function() {
                        return this.getAttribute('data-prev') || '?';
                    });
			}
		});
	})
	</script>

	<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
	<?php echo $formTitle ?>
	<?php
	if (isset($_GET['id'])) {
		printf('<div class="cp"><a href="addquestions.php?aid=%d&amp;cid=%s" onclick="return confirm(\''
            . _('This will discard any changes you have made on this page').'\');">'
            . _('Add/Remove Questions').'</a></div>', Sanitize::onlyInt($_GET['id']), $cid);
	}
	?>
	<?php echo $page_isTakenMsg ?>

	<form method=post action="<?php echo $page_formActionTag ?>">
		<span class=form>Assessment Name:</span>
        <span class=formright><input type=text size=30 name=name value="<?php echo Sanitize::encodeStringForDisplay($line['name']); ?>" required></span><BR class=form>

		Summary: (shows on course page)<BR>
		<div class=editor>
			<textarea cols=50 rows=15 id=summary name=summary style="width: 100%"><?php echo Sanitize::encodeStringForDisplay($line['summary'], true); ?></textarea>
		</div><BR>
		Intro/Instructions:<BR>
		<?php if (isset($introconvertmsg)) {echo $introconvertmsg;} ?>
		<div class=editor>
			<textarea cols=50 rows=20 id=intro name=intro style="width: 100%"><?php echo Sanitize::encodeStringForDisplay($line['intro'], true); ?></textarea>
		</div><BR>

<?php
	if ($dates_by_lti==0) {
?>
		<span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($line['avail'],0);?> onclick="$('#datediv').slideUp(100)"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($line['avail'],1);?> onclick="$('#datediv').slideDown(100);"/>Show by Dates<br/>
		</span><br class="form"/>

		<div id="datediv" style="display:<?php echo ($line['avail']==1)?"block":"none"; ?>">

		<span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,"0",0); ?>/>
			Always until end date<br/>
			<input type=radio name="sdatetype" value="sdate" <?php writeHtmlChecked($startdate,"0",1); ?>/>
			<input type=text size=10 name="sdate" value="<?php echo $sdate;?>">
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=8 name=stime value="<?php echo $stime;?>">
		</span><BR class=form>

		<span class=form>Available Until:</span>
		<span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,"2000000000",0); ?>/>
			 Always after start date
			 <?php if ($courseenddate<2000000000) {
			 	 echo 'until the course end date, '.tzdate("n/j/Y", $courseenddate);
			 }?><br/>
			<input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,"2000000000",1); ?>/>
			<input type=text size=10 name="edate" value="<?php echo $edate;?>">
			<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=8 name=etime value="<?php echo $etime;?>">
		</span><BR class=form>
<?php
	} else { //dates_by_lti is on
?>
		<span class=form>Availability:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($line['avail'],0);?> onclick="$('#datediv').slideUp(100);"/>Prevent access<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($line['avail'],1);?> onclick="$('#datediv').slideDown(100);"/>Allow access<br/>
		</span><br class="form"/>

		<div id="datediv" style="display:<?php echo ($line['avail']==1)?"block":"none"; ?>">

		<span class=form>Due date</span>
		<span class=formright>
			The course setting is enabled for dates to be set via LTI.<br/>
			<?php
			if ($line['date_by_lti']==1) {
				echo 'Waiting for the LMS to send a date';
			} else {
				if ($enddate==2000000000) {
					echo 'Default due date set by LMS: No due date (individual student due dates may vary)';
				} else {
					echo 'Default due date set by LMS: '.$edate.' '.$etime.' (individual student due dates may vary).';
				}
			}
			?>
		</span><br class=form />

<?php
	}
?>
		<span class=form>Review mode:</span>
		<span class=formright>
			<input type=checkbox name="doreview" value="2000000000" <?php if ($line['reviewdate']>0) {echo 'checked';} ?>> Keep open for un-graded practice after the due date
		</span><BR class=form>
		</div>

		<span class=form></span>
		<span class=formright>
			<input type=submit value="<?php echo Sanitize::encodeStringForDisplay($savetitle); ?>"> now or continue below for Assessment Options
		</span><br class=form>

		<fieldset><legend>Assessment Options</legend>
<?php
	if (count($page_copyFromSelect['val'])>0) {
?>
		<span class=form>Copy Options from:</span>
		<span class=formright>

<?php
		writeHtmlSelect ("copyfrom",$page_copyFromSelect['val'],$page_copyFromSelect['label'],0,"None - use settings below",0," onChange=\"chgcopyfrom()\"");
?>
		</span><br class=form>
<?php
	}
?>

		<div id="copyfromoptions" class="hidden">
		<span class=form>Also copy:</span>
		<span class=formright>
			<input type=checkbox name="copysummary" value=1 /> Summary<br/>
			<input type=checkbox name="copyinstr" value=1 /> Instructions<br/>
			<input type=checkbox name="copydates" value=1 /> Dates <br/>
			<input type=checkbox name="copyendmsg" value=1 /> End of Assessment Messages
		</span><br class=form />
		<span class=form>Remove any existing per-question settings?</span>
		<span class=formright>
			<input type=checkbox name="removeperq" />
		</span><br class=form />

		</div>
		<div id="customoptions" class="show">
			<hr/>
			<span class=form>Display method: </span>
			<span class=formright>
				<select name="displaymethod">
					<option value="AllAtOnce" <?php writeHtmlSelected($line['displaymethod'],"AllAtOnce",0) ?>>Full test at once</option>
					<option value="OneByOne" <?php writeHtmlSelected($line['displaymethod'],"OneByOne",0) ?>>One question at a time</option>
					<option value="Seq" <?php writeHtmlSelected($line['displaymethod'],"Seq",0) ?>>Full test, submit one at time</option>
					<option value="SkipAround" <?php writeHtmlSelected($line['displaymethod'],"SkipAround",0) ?>>Skip Around</option>
					<option value="Embed" <?php writeHtmlSelected($line['displaymethod'],"Embed",0) ?>>Embedded</option>
					<option value="VideoCue" <?php writeHtmlSelected($line['displaymethod'],"VideoCue",0) ?>>Video Cued</option>
					<?php if (isset($CFG['GEN']['livepollserver'])) {
						echo '<option value="LivePoll" ';
						writeHtmlSelected($line['displaymethod'],"LivePoll",0);
						echo '>Live Poll (experimental)</option>';
					}?>
				</select>
			</span><BR class=form>

			<span class=form>Feedback method: </span>
			<span class=formright>
				<select id="deffeedback" name="deffeedback" onChange="chgfb()" >
					<option value="NoScores" <?php if ($testtype=="NoScores") {echo "SELECTED";} ?>>No scores shown (last attempt is scored)</option>
					<option value="EndScore" <?php if ($testtype=="EndScore") {echo "SELECTED";} ?>>Just show final score (total points &amp; average) - only whole test can be reattempted</option>
					<option value="EachAtEnd" <?php if ($testtype=="EachAtEnd") {echo "SELECTED";} ?>>Show score on each question at the end of the test </option>
					<option value="EndReview" <?php if ($testtype=="EndReview") {echo "SELECTED";} ?>>Reshow question with score at the end of the test </option>
					<option value="EndReviewWholeTest" <?php if ($testtype=="EndReviewWholeTest") {echo "SELECTED";} ?>>Reshow question with score at the end of the test  - only whole test can be reattempted </option>

					<option value="AsGo" <?php if ($testtype=="AsGo") {echo "SELECTED";} ?>>Show score on each question as it's submitted (does not apply to Full test at once display)</option>
					<option value="Practice" <?php if ($testtype=="Practice") {echo "SELECTED";} ?>>Practice test: Show score on each question as it's submitted &amp; can restart test; scores not saved</option>
					<option value="Homework" <?php if ($testtype=="Homework") {echo "SELECTED";} ?>>Homework: Show score on each question as it's submitted &amp; allow similar question to replace missed question</option>
				</select>
			</span><BR class=form>

			<span class=form>Default attempts per problem (0 for unlimited): </span>
			<span class=formright>
				<input type=text size=4 name=defattempts value="<?php echo Sanitize::encodeStringForDisplay($line['defattempts']); ?>" >
				<span id="showreattdiffver" class="<?php if ($testtype!="Practice" && $testtype!="Homework") {echo "show";} else {echo "hidden";} ?>">
	 			<input type=checkbox name="reattemptsdiffver" <?php writeHtmlChecked($line['shuffle']&8,8); ?> />
	 			Reattempts different versions</span>
	 		</span><BR class=form>

			<span class=form>Default penalty:</span>
			<span class=formright>
				<input type=text size=4 name=defpenalty value="<?php echo Sanitize::encodeStringForDisplay($line['defpenalty']); ?>" <?php if ($taken) {echo 'disabled=disabled';}?>>%
			   	<select name="skippenalty" <?php if ($taken) {echo 'disabled=disabled';}?>>
			    	<option value="0" <?php if ($skippenalty==0) {echo "selected=1";} ?>>per missed attempt</option>
					<option value="1" <?php if ($skippenalty==1) {echo "selected=1";} ?>>per missed attempt, after 1</option>
					<option value="2" <?php if ($skippenalty==2) {echo "selected=1";} ?>>per missed attempt, after 2</option>
					<option value="3" <?php if ($skippenalty==3) {echo "selected=1";} ?>>per missed attempt, after 3</option>
					<option value="4" <?php if ($skippenalty==4) {echo "selected=1";} ?>>per missed attempt, after 4</option>
					<option value="5" <?php if ($skippenalty==5) {echo "selected=1";} ?>>per missed attempt, after 5</option>
					<option value="6" <?php if ($skippenalty==6) {echo "selected=1";} ?>>per missed attempt, after 6</option>
					<option value="10" <?php if ($skippenalty==10) {echo "selected=1";} ?>>on last possible attempt only</option>
				</select>
			</span><BR class=form>


			<span class=form>Show Answers: </span>
			<span class=formright>
				<span id="showanspracspan" class="<?php if ($testtype=="Practice" || $testtype=="Homework") {echo "show";} else {echo "hidden";} ?>">
				<select name="showansprac">
					<option value="V" <?php if ($showans=="V") {echo "SELECTED";} ?>>Never, but allow students to review their own answers</option>
					<option value="N" <?php if ($showans=="N") {echo "SELECTED";} ?>>Never, and don't allow students to review their own answers</option>
					<option value="F" <?php if ($showans=="F") {echo "SELECTED";} ?>>After last attempt (Skip Around only)</option>
					<option value="J" <?php if ($showans=="J") {echo "SELECTED";} ?>>After last attempt or Jump to Ans button (Skip Around only)</option>
					<option value="0" <?php if ($showans=="0") {echo "SELECTED";} ?>>Always</option>
					<option value="1" <?php if ($showans=="1") {echo "SELECTED";} ?>>After 1 attempt</option>
					<option value="2" <?php if ($showans=="2") {echo "SELECTED";} ?>>After 2 attempts</option>
					<option value="3" <?php if ($showans=="3") {echo "SELECTED";} ?>>After 3 attempts</option>
					<option value="4" <?php if ($showans=="4") {echo "SELECTED";} ?>>After 4 attempts</option>
					<option value="5" <?php if ($showans=="5") {echo "SELECTED";} ?>>After 5 attempts</option>
				</select>
				</span>
				<span id="showansspan" class="<?php if ($testtype!="Practice" && $testtype!="Homework") {echo "show";} else {echo "hidden";} ?>">
				<select name="showans">
					<option value="V" <?php if ($showans=="V") {echo "SELECTED";} ?>>Never, but allow students to review their own answers</option>
					<option value="N" <?php if ($showans=="N") {echo "SELECTED";} ?>>Never, and don't allow students to review their own answers</option>
					<option value="I" <?php if ($showans=="I") {echo "SELECTED";} ?>>Immediately (in gradebook) - don't use if allowing multiple attempts per problem</option>
					<option value="F" <?php if ($showans=="F") {echo "SELECTED";} ?>>After last attempt (Skip Around only)</option>
					<option value="R" <?php if ($showans=="R") {echo "SELECTED";} ?>>After last attempt on a version</option>
					<option value="A" <?php if ($showans=="A") {echo "SELECTED";} ?>>After due date (in gradebook)</option>
					<option value="1" <?php if ($showans=="1") {echo "SELECTED";} ?>>After 1 attempt</option>
					<option value="2" <?php if ($showans=="2") {echo "SELECTED";} ?>>After 2 attempts</option>
					<option value="3" <?php if ($showans=="3") {echo "SELECTED";} ?>>After 3 attempts</option>
					<option value="4" <?php if ($showans=="4") {echo "SELECTED";} ?>>After 4 attempts</option>
					<option value="5" <?php if ($showans=="5") {echo "SELECTED";} ?>>After 5 attempts</option>
				</select>
				</span>
			</span><br class=form>

			<span class=form>Gradebook Category:</span>
			<span class=formright>

<?php
	writeHtmlSelect("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],$gbcat,"Default",0);
?>
			</span><br class=form>

		 <div><a href="#" onclick="groupToggleAll(1);return false;">Expand All</a>
		<a href="#" onclick="groupToggleAll(0);return false;">Collapse All</a></div>
		 <div class="block grouptoggle">
		   <img class="mida" src="../img/expand.gif" />
		   Additional Display Options
		 </div>
		 <div class="blockitems hidden">

			<span class="form">Calendar icon:</span>
			<span class="formright">
                <label><input name="caltagradio" type="radio" value="usetext" <?php writeHtmlChecked($line['caltag'],"use_name",1); ?>>Use Text:</label>
                  <input aria-label="Calendar icon text" name="caltagact" type=text size=8 value="<?php echo Sanitize::encodeStringForDisplay($line['caltag']); ?>" <?php echo ($line['caltag'] == 'use_name') ? 'style="color:#FFFFFF;opacity:0.6;" readonly' : null ?> /><br />
                <label><input name="caltagradio" type="radio" value="usename" <?php writeHtmlChecked($line['caltag'],"use_name"); ?>>Use Assessment Name</label>
			</span><br class="form" />

			<span class=form>Shuffle item order: </span>
			<span class=formright>
				<select name="shuffle">
					<option value="0" <?php writeHtmlSelected($line['shuffle']&(1+16),0) ?>>No</option>
					<option value="1" <?php writeHtmlSelected($line['shuffle']&1,1) ?>>All</option>
					<option value="16" <?php writeHtmlSelected($line['shuffle']&16,16) ?>>All but first</option>
				</select>
			</span><BR class=form>

			<span class=form>Show question categories:</span>
			<span class=formright>
				<input name="showqcat" type="radio" value="0" <?php writeHtmlChecked($showqcat,"0"); ?>>No <br />
				<input name="showqcat" type="radio" value="1" <?php writeHtmlChecked($showqcat,"1"); ?>>In Points Possible bar <br />
				<input name="showqcat" type="radio" value="2" <?php writeHtmlChecked($showqcat,"2"); ?>>In navigation bar (Skip-Around only)
			</span><br class="form" />

			<span class=form>Make hard to print?</span>
			<span class=formright>
				<input type="radio" value="0" name="noprint" <?php writeHtmlChecked($line['noprint'],0); ?>/> No <input type="radio" value="1" name="noprint" <?php writeHtmlChecked($line['noprint'],1); ?>/> Yes
			</span><br class=form>

			<span class=form>All items same random seed: </span>
			<span class=formright>
				<input type="checkbox" name="sameseed" <?php writeHtmlChecked($line['shuffle']&2,2); ?>>
				<i>Don't use "Homework" mode or "Reattempts different versions" if you use this setting</i>
			</span><BR class=form>

			<span class=form>All students same version of questions: </span>
			<span class=formright>
				<input type="checkbox" name="samever" <?php writeHtmlChecked($line['shuffle']&4,4); ?>>
			</span><BR class=form>

			<span class=form>Display for tutorial-style questions: </span>
			<span class=formright>
				<input type="checkbox" name="istutorial" <?php writeHtmlChecked($line['istutorial'],1); ?>>
			</span><BR class=form>
		 </div>

		 <div class="block grouptoggle">
		   <img class="mida" src="../img/expand.gif" />
		   Time Limit and Access Control
		 </div>
		 <div class="blockitems hidden">

			<span class=form>Allow use of LatePasses?: </span>
			<span class=formright>
				<?php
				writeHtmlSelect("allowlate",$page_allowlateSelect['val'],$page_allowlateSelect['label'],$line['allowlate']%10);
				?>
				<label><input type="checkbox" name="latepassafterdue" <?php writeHtmlChecked($line['allowlate']>10,true); ?>>
					Allow LatePasses after due date</label>
				<br/>
				<label><input type="checkbox" name="dolpcutoff" <?php writeHtmlChecked($line['LPcutoff']>0,true); ?>
					aria-controls="lpcutoffwrap" aria-expanded="<?php echo ($line['LPcutoff']==0)?'false':'true';?>"/>
					Restrict by date.
				</label>
				<span id=lpcutoffwrap <?php if ($line['LPcutoff']==0) {echo 'style="display: none;"';}?>>
				<label for=lpcutoff>No extensions past</label>
				<input type=text size=10 name="lpdate" value="<?php echo $lpdate;?>">
				<a href="#" onClick="displayDatePicker('lpdate', this, 'edate', 'due date'); return false">
				<img src="../img/cal.gif" alt="Calendar"/></a>
				at <input type=text size=8 name=lptime value="<?php echo $lptime;?>">
				</span>
			</span><BR class=form>

			<span class=form>Time Limit (minutes, 0 for no time limit): </span>
			<span class=formright><input type=text size=4 name=timelimit value="<?php echo Sanitize::onlyFloat(abs($timelimit));?>">
				<input type="checkbox" name="timelimitkickout" <?php if ($timelimit<0) echo 'checked="checked"';?> /> Kick student out at timelimit</span><BR class=form>

			<span class=form>Require Password (blank for none):</span>
			<span class=formright><input type="password" name="assmpassword" id="assmpassword" value="<?php echo Sanitize::encodeStringForDisplay($line['password']); ?>" autocomplete="new-password"> <a href="#" onclick="apwshowhide(this);return false;">Show</a></span><br class=form />

			<span class=form>Show based on another assessment: </span>
			<span class=formright>
<?php
	writeHtmlSelect("reqscoreshowtype", array(-1,0,1), array(_('No prerequisite'),_('Show only after'), _('Show greyed until')), $reqscoredisptype);
			echo '<span id="reqscorewrap" ';
			if ($reqscoredisptype==-1) {
				echo 'style="display:none;"';
			}
			echo '>';
?>
			 a score of
				<input type=text size=4 name=reqscore value="<?php echo abs($line['reqscore']);?>">
<?php
	writeHtmlSelect("reqscorecalctype", array(0,1), array(_('points'), _('percent')), ($line['reqscoretype']&2)?1:0);
?>
			is obtained on
<?php
	writeHtmlSelect ("reqscoreaid",$page_copyFromSelect['val'],$page_copyFromSelect['label'],$line['reqscoreaid']);
?>
			</span></span><br class=form>
		 </div>

		 <div class="block grouptoggle">
		   <img class="mida" src="../img/expand.gif" />
		   Help and Hints
		 </div>
		 <div class="blockitems hidden">

			<span class=form>Show hints and video/text buttons when available?</span>
			<span class=formright>
				<input type="checkbox" name="showhints" <?php writeHtmlChecked($line['showhints'],1); ?>>
			</span><br class=form>

			<span class=form>Show "ask question" links?</span>
			<span class=formright>
				<input type="checkbox" name="msgtoinstr" <?php writeHtmlChecked($line['msgtoinstr'],1); ?>/> Show "Message instructor about this question" links<br/>
				<input type="checkbox" name="doposttoforum" <?php writeHtmlChecked($line['posttoforum'],0,true); ?>/> Show "Post this question to forum" links, to forum <?php writeHtmlSelect("posttoforum",$page_forumSelect['val'],$page_forumSelect['label'],$line['posttoforum']); ?>
			</span><br class=form>

			<span class=form>Assessment resource links</span>
			<span class=formright>
<?php
			foreach ($extrefs as $k=>$extref) {
				echo '<span class="aextref">';
				echo '<label for="extreflabel'.$k.'">Label:</label>';
				echo '<input id="extreflabel'.$k.'" name="extreflabel'.$k.'" size=10 value="'.Sanitize::encodeStringForDisplay($extref['label']).'" /> ';
				echo '<label for="extreflink'.$k.'">Link:</label>';
				echo '<input id="extreflink'.$k.'" name="extreflink'.$k.'" size=30 value="'.Sanitize::encodeStringForDisplay($extref['link']).'" />';
				echo '<button type=button onclick="removeextref(this)">Remove</button>';
				echo '<br/></span>';
			}
?>
			<button type=button onclick="addextref(this)">Add Resource</button>
			</span><br class=form>

			<span class="form">Use equation helper?</span>
			<span class="formright">
				<select name="eqnhelper">
					<option value="0" <?php writeHtmlSelected($line['eqnhelper'],0) ?>>No</option>
				<?php
					//start phasing these out; don't show as option if not used.
					if ($line['eqnhelper']==1 || $line['eqnhelper']==2) {
				?>
					<option value="1" <?php writeHtmlSelected($line['eqnhelper'],1) ?>>Yes, simple form (no logs or trig)</option>
					<option value="2" <?php writeHtmlSelected($line['eqnhelper'],2) ?>>Yes, advanced form</option>
				<?php
					}
				?>
					<option value="3" <?php writeHtmlSelected($line['eqnhelper'],3) ?>>MathQuill, simple form</option>
					<option value="4" <?php writeHtmlSelected($line['eqnhelper'],4) ?>>MathQuill, advanced form</option>
				</select>
			</span><br class="form" />

			<span class=form>Show answer entry tips?</span>
			<span class=formright>
				<select name="showtips">
					<option value="0" <?php writeHtmlSelected($line['showtips'],0) ?>>No</option>
					<option value="1" <?php writeHtmlSelected($line['showtips'],1) ?>>Yes, after question</option>
					<option value="2" <?php writeHtmlSelected($line['showtips'],2) ?>>Yes, under answerbox</option>
				</select>
			</span><br class=form>
		 </div>

		 <div class="block grouptoggle">
		   <img class="mida" src="../img/expand.gif" />
		   Grading and Feedback
		 </div>
		 <div class="blockitems hidden">

			<span class=form>Count: </span>
			<span <?php if ($testtype=="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="stdcntingb">
				<select name="cntingb">
				<option value="1" <?php writeHtmlSelected($cntingb,1,0); ?>> Count in Gradebook</option>
				<option value="0" <?php writeHtmlSelected($cntingb,0,0); ?>> Don't count in grade total and hide from students</option>
				<option value="3" <?php writeHtmlSelected($cntingb,3,0); ?>> Don't count in grade total</option>
				<option value="2" <?php writeHtmlSelected($cntingb,2,0); ?>> Count as Extra Credit</option>
				</select>
			</span>
			<span <?php if ($testtype!="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="praccntingb">
				<input type=radio name="pcntingb" value="0" <?php writeHtmlChecked($pcntingb,0,0); ?> /> Don't count in grade total and hide from students<br/>
				<input type=radio name="pcntingb" value="3" <?php writeHtmlChecked($pcntingb,3,0); ?> /> Don't count in grade total<br/>
			</span><br class=form />

			<span class=form>Minimum score to receive credit: </span>
			<span class=formright>
				<input type=text size=4 name=minscore value="<?php echo Sanitize::encodeStringForDisplay($line['minscore']); ?>">
				<input type="radio" name="minscoretype" value="0" <?php writeHtmlChecked($minscoretype,0);?>> Points
				<input type="radio" name="minscoretype" value="1" <?php writeHtmlChecked($minscoretype,1);?>> Percent
			</span><BR class=form>

			<span class="form">Default Feedback Text:</span>
			<span class="formright">
				Use? <input type="checkbox" name="usedeffb" <?php writeHtmlChecked($usedeffb,true); ?>><br/>
				Text: <input type="text" size="60" name="deffb" value="<?php echo Sanitize::encodeStringForDisplay($deffb); ?>" />
			</span><br class="form" />

<?php
		if (!isset($CFG['GEN']['allowinstraddtutors']) || $CFG['GEN']['allowinstraddtutors']==true) {
?>
			<span class="form">Tutor Access:</span>
			<span class="formright">
<?php
	writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],$line['tutoredit']);
?>
			</span><br class="form" />
<?php
		}
?>

			<span class=form>Penalty for questions done while in exception/LatePass: </span>
			<span class=formright>
				<input type=text size=4 name="exceptionpenalty" value="<?php echo Sanitize::encodeStringForDisplay($line['exceptionpenalty']); ?>">%
			</span><BR class=form>

			<span class="form">Default Outcome:</span>
			<span class="formright"><select name="defoutcome">
				<?php
				$ingrp = false;
				$issel = false;
				foreach ($page_outcomeslist as $oc) {
					if ($oc[1]==1) {//is group
						if ($ingrp) { echo '</optgroup>';}
						echo '<optgroup label="'.Sanitize::encodeStringForDisplay($oc[0]).'">';
						$ingrp = true;
					} else {
						echo '<option value="'.Sanitize::encodeStringForDisplay($oc[0]).'" ';
						if ($line['defoutcome'] == $oc[0]) { echo 'selected="selected"'; $issel = true;}
						echo '>'.Sanitize::encodeStringForDisplay($page_outcomes[$oc[0]]).'</option>';
					}
				}
				if ($ingrp) { echo '</optgroup>';}
				?>
				</select>
			</span><br class="form" />
		 </div>

		 <div class="block grouptoggle">
		   <img class="mida" src="../img/expand.gif" />
		   Group Assessment
		 </div>
		 <div class="blockitems hidden">

			<span class=form>Group assessment: </span>
			<span class=formright>
				<input type="radio" name="isgroup" value="0" <?php writeHtmlChecked($line['isgroup'],0); ?> />Not a group assessment<br/>
				<input type="radio" name="isgroup" value="1" <?php  writeHtmlChecked($line['isgroup'],1); ?> />Students can add members with login passwords<br/>
				<input type="radio" name="isgroup" value="2" <?php  writeHtmlChecked($line['isgroup'],2); ?> />Students can add members without passwords<br/>
				<input type="radio" name="isgroup" value="3" <?php  writeHtmlChecked($line['isgroup'],3); ?> />Students cannot add members, and can't start the assessment until you add them to a group
			</span><br class="form" />

			<span class=form>Max group members (if group assessment): </span>
			<span class=formright>
				<input type="text" name="groupmax" value="<?php echo Sanitize::encodeStringForDisplay($line['groupmax']); ?>" />
			</span><br class="form" />

			<span class="form">Use group set:<?php
				if ($taken) {
					if ($line['isgroup']==0) {
						echo '<br/>Only empty group sets can be used after the assessment has started';
					} else {
						echo '<br/>Cannot change group set after the assessment has started';
					}
				}?></span>
			<span class="formright">
				<?php writeHtmlSelect('groupsetid',$page_groupsets['val'],$page_groupsets['label'],$line['groupsetid'],null,null,($taken && $line['isgroup']>0)?'disabled="disabled"':''); ?>
			</span><br class="form" />
		 </div>
		</div>
	</fieldset>
	<div class=submit><input type=submit value="<?php echo $savetitle;?>"></div>
	</form>
<?php
}
	require("../footer.php");
?>
