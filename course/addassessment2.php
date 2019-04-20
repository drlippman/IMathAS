<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2019 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");


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
        $assessmentId = Sanitize::onlyInt($_GET['id']);
        $cid = Sanitize::courseId($_GET['cid']);
        $block = $_GET['block'];
        $assessName = Sanitize::stripHtmlTags($_POST['name']);
        if ($assessName == '') {
        	$assessName = _('Unnamed Assessment');
        }
        $stm = $DBH->prepare("SELECT dates_by_lti FROM imas_courses WHERE id=?");
        $stm->execute(array($cid));
        $dates_by_lti = $stm->fetchColumn(0);
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

        if (isset($_REQUEST['clearattempts'])) { //FORM POSTED WITH CLEAR ATTEMPTS FLAG
            if (isset($_POST['clearattempts']) && $_POST['clearattempts']=="confirmed") {
            	$DBH->beginTransaction();
                require_once('../includes/filehandler.php');
                deleteallaidfiles($assessmentId);
                $stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
                $stm->execute(array(':assessmentid'=>$assessmentId));
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

            $timelimit = Sanitize::onlyInt($_POST['timelimit'])*60;
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
			$stm = $DBH->prepare("SELECT isgroup,intro,itemorder,deffeedbacktext FROM imas_assessments WHERE id=:id");
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

			//update ptsposs field
			if ($stm->rowCount()>0 && isset($_POST['defpoints'])) {
				require_once("../includes/updateptsposs.php");
				updatePointsPossible($_GET['id'], $curassess['itemorder'], $_POST['defpoints']);
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
				header(sprintf('Location: %s/course/course.php?cid=%s&r=%s', $GLOBALS['basesiteurl'], $cid, $rqp));
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
                $query = "SELECT COUNT(ias.id) FROM imas_assessment_recoreds AS iar,imas_students WHERE ";
                $query .= "iar.assessmentid=:assessmentid AND iar.userid=imas_students.userid AND imas_students.courseid=:courseid";
                $stm = $DBH->prepare($query);
                $stm->execute(array(':assessmentid'=>$assessmentId, ':courseid'=>$cid));
                $taken = ($stm->fetchColumn(0)>0);
                $stm = $DBH->prepare("SELECT * FROM imas_assessments WHERE id=:id");
                $stm->execute(array(':id'=>$assessmentId));
                $line = $stm->fetch(PDO::FETCH_ASSOC);
                $startdate = $line['startdate'];
                $enddate = $line['enddate'];
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
								$line['defattempts'] = isset($CFG['AMS2']['defattempts'])?$CFG['AMS2']['defattempts']:3;
								$line['defpenalty'] = isset($CFG['AMS']['defpenalty'])?$CFG['AMS']['defpenalty']:0;
								$line['showscores'] = isset($CFG['AMS2']['showscores'])?$CFG['AMS2']['showscores']:'during';
								$line['showans'] = isset($CFG['AMS2']['showans'])?$CFG['AMS2']['showans']:'after_lastattempt';
								$line['viewingb'] = isset($CFG['AMS2']['viewingb'])?$CFG['AMS2']['viewingb']:'immediately';
								$line['scoresingb'] = isset($CFG['AMS2']['scoresingb'])?$CFG['AMS2']['scoresingb']:'in_gb';
								$line['ansingb'] = isset($CFG['AMS2']['ansingb'])?$CFG['AMS2']['ansingb']:'after_due';
								$line['gbcategory'] = 0;
								$line['caltag'] = isset($CFG['AMS']['caltag'])?$CFG['AMS']['caltag']:'?';
								$line['shuffle'] = isset($CFG['AMS']['shuffle'])?$CFG['AMS']['shuffle']:0;
								$line['noprint'] = isset($CFG['AMS']['noprint'])?$CFG['AMS']['noprint']:0;
                $line['istutorial'] = 0;
								$line['allowlate'] = isset($CFG['AMS']['allowlate'])?$CFG['AMS']['allowlate']:11;
                $line['LPcutoff'] = 0;
                $timelimit = 0;
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
						if ($line['defpenalty']{0}==='L') {
                $line['defpenalty'] = substr($line['defpenalty'],1);
                $skippenalty=10;
            } else
						*/
						if ($line['defpenalty']{0}==='S') {
							$defattemptpenalty = substr($line['defpenalty'],2);
							$defattemptpenalty_aftern = $line['defpenalty']{1};
            } else {
              $defattemptpenalty = $line['defpenalty'];
							$defattemptpenalty_aftern = 1;
            }
						if ($line['defpenalty']{0}==='S') {
							$defregenpenalty = substr($line['defregenpenalty'],2);
							$defregenpenalty_aftern = $line['defregenpenalty']{1};
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
                $page_isTakenMsg .= "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='addassessment.php?cid=$cid&id=".Sanitize::onlyInt($_GET['id'])."&clearattempts=ask'\"></p>\n";
            } else {
                $page_isTakenMsg = "<p>&nbsp;</p>";
            }

            if (isset($_GET['id'])) {
							$pageh1 = _('Modify Assessment');
						} else {
							$pageh1 = _('Add Assessment');
						}

            $page_formActionTag = sprintf("addassessment.php?block=%s&cid=%s", Sanitize::encodeUrlParam($block), $cid);
            if (isset($_GET['id'])) {
                $page_formActionTag .= "&id=" . Sanitize::onlyInt($_GET['id']);
            }
            $page_formActionTag .= sprintf("&folder=%s&from=%s", Sanitize::encodeUrlParam($_GET['folder']), Sanitize::encodeUrlParam($_GET['from']));
            $page_formActionTag .= "&tb=" . Sanitize::encodeUrlParam($totb);

						$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
            $stm->execute(array(':courseid'=>$cid));
            $otherAssessments = array();
            $i=0;
            if ($stm->rowCount()>0) {
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
            $i=0;
            if ($stm->rowCount()>0) {
                while ($row = $stm->fetch(PDO::FETCH_NUM)) {
									$gbcats[] = array(
										'value' => $row[0],
										'text' => $row[1]
									);
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

						$outcomeOptions = array();
            if ($i>0) {//there were outcomes
                $stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
                $stm->execute(array(':id'=>$cid));
                $outcomearr = unserialize($stm->fetchColumn(0));

                function flattenarr($ar) {
                    global $page_outcomes, $outcomeOptions;
                    foreach ($ar as $v) {
                        if (is_array($v)) { //outcome group
													$outcomeOptions[] = array(
														'value' => '',
														'label' => $v['name'],
														'isgroup' => true  //hacky
													);
                          flattenarr($v['outcomes']);
                        } else {
													$outcomeOptions[] = array(
														'value' => $v,
														'label' => $page_outcomes[$v],
														'isgroup' => true  //hacky
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
 $placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js?v=080818\"></script>";
// $placeinhead .= '<script src="https://cdn.jsdelivr.net/npm/vue"></script>';
$placeinhead .= '<script src="https://cdn.jsdelivr.net/npm/vue@2.6.10/dist/vue.js"></script>';

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
<?php
}
	require("../footer.php");
?>
