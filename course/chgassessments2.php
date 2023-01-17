<?php
//IMathAS:  Mass change assessments page, for assess2 format
//(c) 2019 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require("../includes/copyiteminc.php");
require("../includes/loaditemshowdata.php");
require_once("../includes/TeacherAuditLog.php");

/*** pre-html data manipulation, including function code *******/

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Mass Change Assessment Settings";
$cid = Sanitize::courseId($_GET['cid']);
$curBreadcrumb = $breadcrumbbase;
if (empty($_COOKIE['fromltimenu'])) {
    $curBreadcrumb .= " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
}
$curBreadcrumb .= _('Mass Change Assessment Settings');

	// SECURITY CHECK DATA PROCESSING
if (!(isset($teacherid))) {
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {
	$cid = Sanitize::courseId($_GET['cid']);

	if (isset($_POST['checked'])) { //if the form has been submitted
		$checked = array();
		foreach ($_POST['checked'] as $id) {
			$id = Sanitize::onlyInt($id);
			if ($id != 0) {
				$checked[] = $id;
			}
		}
		$checkedlist = implode(',',$checked); //sanitized

		// verify checked list assessments are all in the course
		$stm = $DBH->prepare("SELECT id,submitby FROM imas_assessments WHERE id IN ($checkedlist) AND courseid=?");
		$stm->execute(array($cid));
		$checked = array();
		$cursubmitby = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$checked[] = $row['id'];
			$cursubmitby[$row['id']] = $row['submitby'];
		}
		$checkedlist = implode(',', $checked);

		$sets = array();
		$qarr = array();
        $coreOK = true;
		if ($_POST['copyopts'] != 'DNC') {
            $copyreqscore = !empty($_POST['copyreqscore']);
			$tocopy = 'displaymethod,submitby,defregens,defregenpenalty,keepscore,defattempts,defpenalty,showscores,showans,viewingb,scoresingb,ansingb,gbcategory,caltag,shuffle,showwork,noprint,istutorial,showcat,allowlate,timelimit,password,reqscoretype,reqscore,reqscoreaid,showhints,msgtoinstr,posttoforum,extrefs,showtips,cntingb,minscore,deffeedbacktext,tutoredit,exceptionpenalty,defoutcome';
			$stm = $DBH->prepare("SELECT $tocopy FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>Sanitize::onlyInt($_POST['copyopts'])));
			$qarr = $stm->fetch(PDO::FETCH_ASSOC);
			$tocopyarr = explode(',',$tocopy);
			foreach ($tocopyarr as $k=>$item) {
                if (($item == 'reqscoreaid' || $item == 'reqscore') && !$copyreqscore) {
                    unset($qarr[$item]);
                } else if ($item == 'reqscoretype' && !$copyreqscore) {
                    if (($qarr[$item]&1)==0) {
                        $sets[] = 'reqscore=ABS(reqscore)';
                        $sets[] = 'reqscoretype=(reqscoretype & ~1)';
                    } else {
                        $sets[] = 'reqscoretype=(reqscoretype | 1)';
                    }
                    unset($qarr['reqscoretype']);
                } else {
                    $sets[] = "$item=:$item";
                }
            }
            $submitby = $qarr['submitby'];
		} else {
			$turnonshuffle = 0;
			$turnoffshuffle = 0;
			if ($_POST['shuffle'] !== 'DNC') {
				if ($_POST['shuffle']==1) {
					$turnonshuffle += 1;
					$turnoffshuffle += 16;
				} else if ($_POST['shuffle']==16) {
					$turnonshuffle += 16;
					$turnoffshuffle += 33;
				} else if ($_POST['shuffle']==32) {
					$turnonshuffle += 32;
					$turnoffshuffle += 17;
				} else if ($_POST['shuffle']==48) {
					$turnonshuffle += 16;
					$turnoffshuffle += 1;
				} else {
					$turnoffshuffle += 49;
				}
			}
			if ($_POST['samever'] !== 'DNC') {
				if ($_POST['samever'] == 1) {
					$turnonshuffle += 4;
				} else {
					$turnoffshuffle +=4;
				}
			}

			if ($_POST['showwork'] !== 'DNC') {
				$sets[] = "showwork=:showwork";
				$qarr[':showwork'] = Sanitize::onlyInt($_POST['showwork']) + Sanitize::onlyInt($_POST['showworktype']);
			}

			if ($_POST['displaymethod'] !== 'DNC') {
				$sets[] = "displaymethod=:displaymethod";
				$qarr[':displaymethod'] = Sanitize::simpleASCII($_POST['displaymethod']);
			}

			if ($_POST['defpoints'] !== '') {
				$sets[] = "defpoints=:defpoints";
				$qarr[':defpoints'] = Sanitize::onlyInt($_POST['defpoints']);
			}

			// check the core settings for consistency
			if ($_POST['subtype'] === 'DNC') {
				$coreOK = false;
			} else {
				$submitby = Sanitize::simpleASCII($_POST['subtype']);
			}
			if ($_POST['defregens'] === '') {
				$coreOK = false;
			} else {
				$defregens = Sanitize::onlyInt($_POST['defregens']);
				if ($defregens > 1) {
					if ($_POST['defregenpenalty'] === '') {
						$coreOK = false;
					} else {
						$defregenpenalty = Sanitize::onlyInt($_POST['defregenpenalty']);
					}
					if ($defregenpenalty > 0) {
						if ($_POST['defregenpenaltyaftern'] === '') {
							$coreOK = false;
						} else {
							$defregenpenalty_aftern = Sanitize::onlyInt($_POST['defregenpenaltyaftern']);
							if ($defregenpenalty_aftern > 1) {
								$defregenpenalty = 'S' . $defregenpenalty_aftern . $defregenpenalty;
							}
						}
					}
				} else {
					$defregenpenalty = 0;
				}
			}
			if ($coreOK && $submitby == 'by_assessment' && $defregens > 1) {
				if ($_POST['keepscore'] === 'DNC') {
					$coreOK = false;
				} else {
					$keepscore = Sanitize::simpleASCII($_POST['keepscore']);
				}
			} else {
				$keepscore = 'best';
			}
			if ($_POST['defattempts'] === '') {
				$coreOK = false;
			} else {
				$defattempts = Sanitize::onlyInt($_POST['defattempts']);
				if ($defattempts > 1) {
					if ($_POST['defattemptpenalty'] === '') {
						$coreOK = false;
					} else {
						$defattemptpenalty = Sanitize::onlyInt($_POST['defattemptpenalty']);
					}
					if ($defattemptpenalty > 0) {
						if ($_POST['defattemptpenaltyaftern'] === '') {
							$coreOK = false;
						} else {
							$defattemptpenalty_aftern = Sanitize::onlyInt($_POST['defattemptpenaltyaftern']);
							if ($defattemptpenalty_aftern > 1) {
								$defattemptpenalty = 'S' . $defattemptpenalty_aftern . $defattemptpenalty;
							}
						}
					}
				} else {
					$defattemptpenalty = 0;
				}
			}
			$showscores = Sanitize::simpleASCII($_POST['showscores']);
			if (!isset($_POST['showans'])) {
				$showans = 'never';
			} else {
				$showans = Sanitize::simpleASCII($_POST['showans']);
			}
			$viewingb = Sanitize::simpleASCII($_POST['viewingb']);
			if (!isset($_POST['scoresingb'])) {
				$scoresingb = 'never';
			} else {
				$scoresingb = Sanitize::simpleASCII($_POST['scoresingb']);
			}
			if (!isset($_POST['ansingb'])) {
				$ansingb = 'never';
			} else {
				$ansingb = Sanitize::simpleASCII($_POST['ansingb']);
			}
			if ($showscores === 'DNC' || $showans === 'DNC' || $viewingb === 'DNC' ||
				$scoresingb === 'DNC' || $ansingb === 'DNC'
			) {
				$coreOK = false;
			}
			if ($coreOK) {
				// now we can record these!
				$sets[] = "submitby=:submitby";
				$qarr[':submitby'] = $submitby;
				$sets[] = "keepscore=:keepscore";
				$qarr[':keepscore'] = $keepscore;
				$sets[] = "defregens=:defregens";
				$qarr[':defregens'] = $defregens;
				$sets[] = "defregenpenalty=:defregenpenalty";
				$qarr[':defregenpenalty'] = $defregenpenalty ;
				$sets[] = "defattempts=:defattempts";
				$qarr[':defattempts'] = $defattempts;
				$sets[] = "defpenalty=:defpenalty";
				$qarr[':defpenalty'] = $defattemptpenalty;
				$sets[] = "showscores=:showscores";
				$qarr[':showscores'] = $showscores;
				$sets[] = "showans=:showans";
				$qarr[':showans'] = $showans;
				$sets[] = "viewingb=:viewingb";
				$qarr[':viewingb'] = $viewingb;
				$sets[] = "scoresingb=:scoresingb";
				$qarr[':scoresingb'] = $scoresingb;
				$sets[] = "ansingb=:ansingb";
				$qarr[':ansingb'] = $ansingb ;
			}

			if ($_POST['gbcategory'] !== 'DNC') {
				$sets[] = "gbcategory=:gbcategory";
				$qarr[':gbcategory'] = Sanitize::onlyInt($_POST['gbcategory']);
			}

			if ($_POST['caltag'] !== '') {
				$sets[] = "caltag=:caltag";
				$qarr[':caltag'] = Sanitize::stripHtmlTags($_POST['caltag']);
			}

			if ($_POST['noprint'] !== 'DNC') {
				$sets[] = "noprint=:noprint";
				$qarr[':noprint'] = Sanitize::onlyInt($_POST['noprint']);
			}

			if ($_POST['istutorial'] !== 'DNC') {
				$sets[] = "istutorial=:istutorial";
				$qarr[':istutorial'] = Sanitize::onlyInt($_POST['istutorial']);
			}
			if ($_POST['showcat'] !== 'DNC') {
				$sets[] = "showcat=:showcat";
				$qarr[':showcat'] = Sanitize::onlyInt($_POST['showcat']);
			}

			if ($_POST['allowlate'] !== 'DNC') {
				$allowlate = Sanitize::onlyInt($_POST['allowlate']);
				if (isset($_POST['latepassafterdue']) && $allowlate>0) {
					$allowlate += 10;
				}
				$sets[] = "allowlate=:allowlate";
				$qarr[':allowlate'] = $allowlate;
			}

			if ($_POST['timelimit'] !== '') {
				$sets[] = "overtime_grace=:overtimegrace";
				$sets[] = "timelimit=:timelimit";
				$sets[] = "overtime_penalty=:overtimepenalty";
				$timelimit = -1*round(Sanitize::onlyFloat($_POST['timelimit'])*60);
				if (isset($_POST['allowovertime']) && $_POST['overtimegrace'] > 0) {
					$timelimit = -1*$timelimit;
					$qarr[':overtimegrace'] = round(Sanitize::onlyFloat($_POST['overtimegrace'])*60);
					$qarr[':overtimepenalty'] = Sanitize::onlyInt($_POST['overtimepenalty']);
				} else {
					$qarr[':overtimegrace'] = 0;
					$qarr[':overtimepenalty'] = 0;
				}
				$qarr[':timelimit'] = $timelimit;
			}

			if (isset($_POST['dochgpassword'])) {
				$sets[] = "password=:password";
				$qarr[':password'] = Sanitize::stripHtmlTags($_POST['assmpassword']);
			}

			if ($_POST['reqscoreaid'] !== 'DNC') {
				$sets[] = "reqscore=:reqscore";
				if ($_POST['reqscoreaid'] > 0) {
					$qarr[':reqscore'] = Sanitize::onlyInt($_POST['reqscore']);
				} else {
					$qarr[':reqscore'] = 0;
				}
				$sets[] = "reqscoreaid=:reqscoreaid";
				$qarr[':reqscoreaid'] = Sanitize::onlyInt($_POST['reqscoreaid']);
				if (!empty($_POST['reqscorecalctype'])) {
					$sets[] = "reqscoretype=(reqscoretype | 2)";
				} else {
					$sets[] = "reqscoretype=(reqscoretype & ~2)";
				}
			}
			if ($_POST['reqscoreshowtype'] !== 'DNC') {
				if ($_POST['reqscoreshowtype']==0) {
					$sets[] = 'reqscore=ABS(reqscore)';
					$sets[] = 'reqscoretype=(reqscoretype & ~1)';
				} else {
					$sets[] = 'reqscoretype=(reqscoretype | 1)';
				}
			}

			if (isset($_POST['dochgshowhints'])) {
				$sets[] = "showhints=:showhints";
				$qarr[':showhints'] = empty($_POST['showhints']) ? 0 : 1;
                $qarr[':showhints'] |= empty($_POST['showextrefs']) ? 0 : 2;
                $qarr[':showhints'] |= empty($_POST['showwrittenex']) ? 0 : 4;
			}

			if ($_POST['msgtoinstr'] !== 'DNC') {
				$sets[] = "msgtoinstr=:msgtoinstr";
				$qarr[':msgtoinstr'] = Sanitize::onlyInt($_POST['msgtoinstr']);
			}
			if ($_POST['posttoforum'] !== 'DNC') {
				$sets[] = "posttoforum=:posttoforum";
				$qarr[':posttoforum'] = Sanitize::onlyInt($_POST['posttoforum']);
			}

			if (isset($_POST['dochgextref'])) {
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
				$sets[] = "extrefs=:extrefs";
				$qarr[':extrefs'] = json_encode($extrefs, JSON_INVALID_UTF8_IGNORE);
			}

			if ($_POST['showtips'] !== 'DNC') {
				$sets[] = "showtips=:showtips";
				$qarr[':showtips'] = Sanitize::onlyInt($_POST['showtips']);
			}

			if ($_POST['cntingb'] !== 'DNC') {
				$sets[] = "cntingb=:cntingb";
				$qarr[':cntingb'] = Sanitize::onlyInt($_POST['cntingb']);
			}

			if ($_POST['minscore'] !== '') {
				if ($_POST['minscoretype']==1 && trim($_POST['minscore'])!='' && $_POST['minscore']>0) {
					$_POST['minscore'] = intval($_POST['minscore'])+10000;
				}
				$sets[] = "minscore=:minscore";
				$qarr[':minscore'] = Sanitize::onlyInt($_POST['minscore']);
			}

			if ($_POST['usedeffb'] !== 'DNC') {
				if ($_POST['usedeffb'] == 1) {
					$sets[] = "deffeedbacktext=:deffeedbacktext";
					$qarr[':deffeedbacktext'] = Sanitize::incomingHtml($_POST['deffb']);
				} else {
					$sets[] = "deffeedbacktext=''";
				}
			}

			if ($_POST['tutoredit'] !== 'DNC') {
				$sets[] = "tutoredit=:tutoredit";
				$qarr[':tutoredit'] = Sanitize::onlyInt($_POST['tutoredit']);
			}

			if ($_POST['exceptionpenalty'] !== '') {
				$sets[] = "exceptionpenalty=:exceptionpenalty";
				$qarr[':exceptionpenalty'] = Sanitize::onlyInt($_POST['exceptionpenalty']);
			}

			if (isset($_POST['defoutcome']) && $_POST['defoutcome'] !== 'DNC') {
				$sets[] = "defoutcome=:defoutcome";
				$qarr[':defoutcome'] = Sanitize::onlyInt($_POST['defoutcome']);
			}

			if ($turnonshuffle!=0 || $turnoffshuffle!=0) {
				$shuff = "shuffle = ((shuffle";
				if ($turnoffshuffle>0) {
					$shuff .= " & ~$turnoffshuffle)";
				} else {
					$shuff .= ")";
				}
				if ($turnonshuffle>0) {
					$shuff .= " | $turnonshuffle";
				}
				$shuff .= ")";
				$sets[] = $shuff;
			}
		}

		// handle general options
		if ($_POST['avail'] !== 'DNC') {
			$sets[] = "avail=:avail";
			$qarr[':avail'] = Sanitize::onlyInt($_POST['avail']);
		}
		if ($_POST['review'] !== 'DNC') {
			$sets[] = "reviewdate=:reviewdate";
			$qarr[':reviewdate'] = ($_POST['review']==1)?2000000000:0;
		}

		if ($_POST['summary'] !== 'DNC') {
			$stm = $DBH->prepare("SELECT summary FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>Sanitize::onlyInt($_POST['summary'])));
			$sets[] = "summary=:summary";
			$qarr[':summary'] = $stm->fetchColumn(0);
		}
		if ($_POST['dates'] !== 'DNC') {
			$stm = $DBH->prepare("SELECT startdate,enddate,reviewdate,LPcutoff FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>Sanitize::onlyInt($_POST['dates'])));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$sets[] = "startdate=:startdate";
			$qarr[':startdate'] = $row[0];
			$sets[] = "enddate=:enddate";
			$qarr[':enddate'] = $row[1];
			if ($_POST['review'] === 'DNC') {
				$sets[] = "reviewdate=:reviewdate";
				$qarr[':reviewdate'] = $row[2];
			}
			$sets[] = "LPcutoff=:LPcutoff";
			$qarr[':LPcutoff'] = $row[3];
		}
		if ($_POST['copyendmsg'] !== 'DNC') {
			$stm = $DBH->prepare("SELECT endmsg FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>Sanitize::onlyInt($_POST['copyendmsg'])));
			$sets[] = "endmsg=:endmsg";
			$qarr[':endmsg'] = $stm->fetchColumn(0);
		}

		$metadata = array('assessments'=>$checkedlist);
		if (count($sets)>0) {
			$setslist = implode(',',$sets);
			$qarr[':cid'] = $cid;
			$stm = $DBH->prepare("UPDATE imas_assessments SET $setslist WHERE id IN ($checkedlist) AND courseid=:cid");
            $stm->execute($qarr);
			if ($stm->rowCount()>0) {
				$updated_settings = true;
				$metadata = $metadata + $qarr;
				unset($metadata[':cid']);
			}
		}
		if ($_POST['intro'] !== 'DNC') {
			$stm = $DBH->prepare("SELECT intro FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>Sanitize::onlyInt($_POST['intro'])));
			$cpintro = $stm->fetchColumn(0);
			if (($introjson=json_decode($cpintro))!==null) { //is json intro
				$newintro = $introjson[0];
			} else {
				$newintro = $cpintro;
			}
			$metadata['intro'] = $newintro;
			$stm = $DBH->query("SELECT id,intro FROM imas_assessments WHERE id IN ($checkedlist)");
			$stmupd = $DBH->prepare("UPDATE imas_assessments SET intro=:intro WHERE id=:id");
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				if (($introjson=json_decode($row['intro']))!==null) { //is json intro
					$introjson[0] = $newintro;
					$outintro = json_encode($introjson, JSON_INVALID_UTF8_IGNORE);
				} else {
					$outintro = $newintro;
				}
				$stmupd->execute(array(':id'=>$row['id'], ':intro'=>$outintro));
				if ($stmupd->rowCount()>0) {
					$updated_settings = true;
				}
			}
		}

		if (isset($_POST['removeperq'])) {
			$stm = $DBH->query("UPDATE imas_questions SET points=9999,attempts=9999,penalty=9999,regen=0,showans=0,showhints=-1,fixedseeds=NULL WHERE assessmentid IN ($checkedlist)");
			$metadata['perq'] = "Removed per-question settings";
			$updated_settings = true;
		}
		if (!empty($updated_settings)) {
			TeacherAuditLog::addTracking(
				$cid,
				"Mass Assessment Settings Change",
				null,
				$metadata
			);
		}
        if ($_POST['copyopts'] != 'DNC' || $_POST['defpoints'] !== '' || 
            isset($_POST['removeperq']) || $_POST['exceptionpenalty'] !== '' ||
            $_POST['subtype'] !== 'DNC'
        ) {
            require_once("../includes/updateptsposs.php");
            require_once("../assess2/AssessHelpers.php");
			foreach ($checked as $aid) {
                //update points possible
                updatePointsPossible($aid);
                // re-total existing assessment attempts to adjust scores
                if ($coreOK && $submitby!==$cursubmitby[$aid]) {
					// convert data format
                    AssessHelpers::retotalAll($cid, $aid, true, false, $submitby);
				} else {
                    AssessHelpers::retotalAll($cid, $aid);
                }
			}
        }
        if ($_POST['showwork'] != 'DNC') {
            require_once("../assess2/AssessHelpers.php");
            // update "show work after" status flags
            foreach ($checked as $aid) {
                $thissubby = $coreOK ? $submitby : $cursubmitby[$aid];
                AssessHelpers::updateShowWorkStatus($aid, $_POST['showwork'], $thissubby);
            }
        }
		if (isset($_POST['chgendmsg'])) {
			include("assessendmsg.php");
		} else {
			$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=" . Sanitize::courseId($_GET['cid']) .$btf. "&r=" . Sanitize::randomQueryStringParam());
		}
		exit;

	} else { //DATA MANIPULATION FOR INITIAL LOAD
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));

		$agbcats = array();
		$itemshowdata = loadItemShowData($items,false,true,false,false,'Assessment',true);

		$stm = $DBH->prepare("SELECT id,name,gbcategory FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
		$stm->execute(array(':courseid'=>$cid));
        $page_assessSelect = array();
		if ($stm->rowCount()==0) {
			$page_assessListMsg = "<li>No Assessments to change</li>\n";
		} else {
			$page_assessListMsg = "";
			$i=0;
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$page_assessSelect[] = array(
					'val' => $row[0],
					'label' => $row[1]
				);
				$agbcats[$row[0]] = $row[2];
				$i++;
			}
		}
		$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbcats = array(0 => _('Default'));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$gbcats[$row[0]] = $row[1];
		}

		$forums = array(array(
			'value' => 'DNC',
			'text' => 'Do not change'
		), array(
			'value' => 0,
			'text' => 'Do not show "Post question to forum" links'
		));
		$stm = $DBH->prepare("SELECT id,name FROM imas_forums WHERE courseid=:courseid ORDER BY name");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$forums[] = array(
				'value' => $row[0],
				'text' => 'To forum: ' . $row[1]
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

		function getNestedList($items, $parent) {
			global $itemshowdata, $agbcats, $staticroot;
			$out = '';
			foreach($items as $k=>$item) {
				if (is_array($item)) {
					if (count($item['items'])>0) {
						$sub = getNestedList($item['items'], $parent.'-'.($k+1));
						if ($sub !== '') {
							$out .= '<li>';
							$out .= '<label> <img src="'.$staticroot.'/img/folder_tiny.png"/> ';
							$out .= '<input type=checkbox name="checked[]" value="0" id="'.$parent.'-'.($k+1).'" ';
							$out .= 'onClick="chkgrp(this.form, \''.$parent.'-'.($k+1).'\', this.checked);" checked=checked /> ';

							$out .= '<i>'.$item['name'].'</i></label>';
							$out .= '<ul class="assessnest">'.$sub.'</ul></li>';
						}
					}
				} else if (isset($itemshowdata[$item]['itemtype']) && $itemshowdata[$item]['itemtype'] == 'Assessment') {
					$aid = $itemshowdata[$item]['id'];
					$out .= '<li>';
					$out .= '<label><img src="'.$staticroot.'/img/assess_tiny.png"/> ';
					$out .= '<input type=checkbox name="checked[]" value="'.$aid.'" onclick="updgrp(\''.$parent.'\')" ';
					$out .= 'id="' . $parent . "." . $item . ":" . $agbcats[$aid] . '" checked=checked /> ';

					$out .= $itemshowdata[$item]['name'].'</label></li>';
				}
			}
			return $out;
		}
		$assessNestedList = getNestedList($items, '0');
	}
}

/******* begin html output ********/
$placeinhead = '<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.10/vue.min.js"></script>';

 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>
<style type="text/css">
span.hidden {
	display: none;
}
span.show {
	display: inline;
}
table td {
	border-bottom: 1px solid #ccf;
}
.doubledivider td {
	border-bottom: 3px double #000;
}
div.highlight {
	padding-top: 4px;
	background-color: #e1f5ec;
	border-left: 2px solid #19925b;
	margin-left: -2px;
	margin-bottom: 2px;
}
div.warn {
	padding-top: 4px;
	background-color: #f5e1e5;
	border-left: 2px solid #fcc;
	margin-left: -2px;
	margin-bottom: 2px;
}
ul.assessnest {
	list-style-type: none;
	padding-left: 0;
}
ul.assessnest ul.assessnest {
	padding-left: 20px;
}
</style>
<script type="text/javascript">

function chkgrp(frm, arr, mark) {
	  var els = frm.getElementsByTagName("input");
	  for (var i = 0; i < els.length; i++) {
		  var el = els[i];
		  if (el.type=='checkbox' && (el.id.indexOf(arr+'.')==0 || el.id.indexOf(arr+'-')==0 || el.id==arr)) {
	     	       el.checked = mark;
		  }
	  }
		updgrp(arr);
}
function updgrp(parent) {
	var tot = $("#"+parent).closest('li').find("ul input[id^="+parent+"]").length;
	var chked = $("#"+parent).closest('li').find("ul input[id^="+parent+"]:checked").length;
	if (chked == 0) {
		$("#"+parent).prop('checked', false).prop('indeterminate', false);
	} else if (chked < tot) {
		$("#"+parent).prop('checked', false).prop('indeterminate', true);
	} else {
		$("#"+parent).prop('checked', true).prop('indeterminate', false);
	}
	parent = parent.replace(/-\d+$/,'');
	if (parent.indexOf('-') !== -1) {
		updgrp(parent);
	}
}

function chkgbcat(cat) {
	chkAllNone('qform','checked[]',false);
	var els = document.getElementById("alistul").getElementsByTagName("input");
	var regExp = new RegExp(":"+cat+"$");
	for (var i = 0; i < els.length; i++) {
		  var el = els[i];
		  if (el.type=='checkbox' && el.id.match(regExp)) {
	     	el.checked = true;
				updgrp(el.id.split(/\./)[0]);
		  }
	}
}

function valform() {
	if ($("#qform input:checkbox[name='checked[]']:checked").length == 0) {
		if (!confirm("No assessments are selected to be changed. Cancel to go back and select some assessments, or click OK to make no changes")) {
			return false;
		}
	}
	if ($("#oktocopy").val() != 1) {
		if (!confirm("The core settings must all be set for consistency. If you submit now, none of these options will be changed. Click Cancel to go back, or click OK to submit anyway.")) {
			return false;
		}
	}
	if ($(".highlight").length == 0) {
		if (!confirm("No settings have been selected to be changed. Click Cancel to go back and select some settings to change, or click OK to make no changes")) {
			return false;
		}
	}
	return true;
}
function tabToSettings() {
	$('#chgassesstab_chg').click();
	var pos = $('#chgassesstab_chg')[0].getBoundingClientRect();
	if (pos.y < 0) {
		$(document).scrollTop(0);
	}
}
</script>

	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerchgassessments" class="pagetitle"><h1>Mass Change Assessment Settings
		<img src="<?php echo $staticroot; ?>/img/help.gif" alt="Help" onClick="window.open('<?php echo $imasroot ?>/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/>
	</h1></div>

	<div class="cpmid">
	<a href="masschgprereqs.php?cid=<?php echo $cid;?>"><?php echo _('Mass Change Prereqs'); ?></a>
	</div>

	<p>This form will allow you to change the assessment settings for several or all assessments at once.</p>
	<p><b>Be aware</b> that changing some settings after an assessment has been
	 taken will change the student's data.</p>

	<form id="qform" method=post action="chgassessments2.php?cid=<?php echo $cid; ?>" onsubmit="return valform();" class="tabwrap">
		<ul class="tablist" role="tablist">
			<li class="active">
				<a href="#" role="tab" id="chgassesstab_sel" aria-controls="chgassess_sel" aria-selected="true"
					onclick="setActiveTab(this);return false;"
				>1: Select Assessments</a>
			</li>
			<li>
				<a href="#" role="tab" id="chgassesstab_chg" aria-controls="chgassess_chg" aria-selected="true"
					onclick="setActiveTab(this);return false;"
				>2: Change Settings</a>
			</li>
		</ul>
		<div class="tabpanel" id="chgassess_sel" aria-labelledby="chgassesstab_sel"
			aria-hidden="false"
		>
		<h2>Assessments to Change</h2>

		Check: <a href="#" onclick="document.getElementById('selbygbcat').selectedIndex=0;return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="document.getElementById('selbygbcat').selectedIndex=0;return chkAllNone('qform','checked[]',false)">None</a>
		Check by gradebook category:
		<?php
		writeHtmlSelect ("selbygbcat",array_keys($gbcats),array_values($gbcats),null,"Select...",-1,' onchange="chkgbcat(this.value);" id="selbygbcat" ');
		?>

		<ul id="alistul" class="assessnest">
		<?php echo $assessNestedList; ?>
		</ul>

		<p>
			Continue to <a href="#" role="tab" id="chgassesstab_chg" aria-controls="chgassess_chg" aria-selected="true"
				onclick="tabToSettings();return false;"
			>Change Settings</a>.
		</p>
	</div>
	<div class="tabpanel" id="chgassess_chg" aria-labelledby="chgassesstab_chg"
		aria-hidden="true" style="display:none"
	>
	<h2>Change Settings</h2>

<?php
	require('chgassessments2form.php');
?>


	<div class=submit><input type=submit class="primary" value="<?php echo _('Apply Changes')?>"></div>
</div>
	</form>
<?php
}
require("../footer.php");
?>
