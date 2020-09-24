<?php

require_once(__DIR__."/updateptsposs.php");
require_once(__DIR__."/migratesettings.php");
//boost operation time

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['includes/copyiteminc'])) {
	require($CFG['hooks']['includes/copyiteminc']);
}

ini_set("max_execution_time", "900");


//IMathAS:  Copy Items utility functions
//(c) 2008 David Lippman
$reqscoretrack = array();
$categoryassessmenttrack = array();
$posttoforumtrack = array();
$forumtrack = array();
$qrubrictrack = array();
$frubrictrack = array();
$assessnewid = array();
$exttooltrack = array();
$itemtypemap = array();
$autoexcusetrack = array();
if (!isset($replacebyarr)) {
	$replacebyarr = array();
}
if (isset($removewithdrawn) && $removewithdrawn) {
	$removewithdrawn = true;
} else {
	$removewithdrawn = false;
}



function copyitem($itemid,$gbcats=false,$sethidden=false) {
	global $DBH;
	global $cid, $sourcecid, $reqscoretrack, $categoryassessmenttrack, $assessnewid, $qrubrictrack, $frubrictrack, $copystickyposts,$userid, $exttooltrack, $outcomes, $removewithdrawn, $replacebyarr;
	global $posttoforumtrack, $forumtrack, $itemtypemap, $datesbylti, $convertAssessVer, $autoexcusetrack;
	if (!isset($copystickyposts)) { $copystickyposts = false;}
	if ($gbcats===false) {
		$gbcats = array();
	}
	if (!isset($outcomes)) {
		$outcomes = array();
	}
	if (strlen($_POST['append'])>0 && $_POST['append']{0}!=' ') {
		$_POST['append'] = ' '.$_POST['append'];
	}
	$now = time();
	$stm = $DBH->prepare("SELECT itemtype,typeid FROM imas_items WHERE id=:id");
	$stm->execute(array(':id'=>$itemid));
	if ($stm->rowCount()==0) {return false;}
	list($itemtype,$typeid) = $stm->fetch(PDO::FETCH_NUM);
	if ($itemtype == "InlineText") {
		//$query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate) ";
		//$query .= "SELECT '$cid',title,text,startdate,enddate FROM imas_inlinetext WHERE id='$typeid'";
		//mysql_query($query) or die("Query failed :$query " . mysql_error());
		$stm = $DBH->prepare("SELECT title,text,startdate,enddate,avail,oncal,caltag,isplaylist,fileorder FROM imas_inlinetext WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		if ($sethidden) {$row['avail'] = 0;}
		$row['title'] .= $_POST['append'];
		$fileorder = $row['fileorder'];
		$query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate,avail,oncal,caltag,isplaylist) ";
		$query .= "VALUES (:courseid,:title,:text,:startdate,:enddate,:avail,:oncal,:caltag,:isplaylist)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid, ':title'=>$row['title'], ':text'=>$row['text'], ':startdate'=>$row['startdate'],
		   ':enddate'=>$row['enddate'], ':avail'=>$row['avail'], ':oncal'=>$row['oncal'], ':caltag'=>$row['caltag'], ':isplaylist'=>$row['isplaylist']));
		$newtypeid = $DBH->lastInsertId();

		$addedfiles = array();
		$intr_file_stm = null;
		$stm = $DBH->prepare("SELECT description,filename,id FROM imas_instr_files WHERE itemid=:itemid");
		$stm->execute(array(':itemid'=>$typeid));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$curid = $row['id'];
			if ($intr_file_stm === null) { //prepare once
				$intr_file_stm = $DBH->prepare("INSERT INTO imas_instr_files (description,filename,itemid) VALUES (:description, :filename, :itemid)");
			}
			$intr_file_stm->execute(array(':description'=>$row['description'], ':filename'=>$row['filename'], ':itemid'=>$newtypeid));
			$addedfiles[$curid] = $DBH->lastInsertId();
		}
		if (count($addedfiles)>0) {
			$addedfilelist = array();
			foreach (explode(',',$fileorder) as $fid) {
				$addedfilelist[] = $addedfiles[$fid];
			}
			$addedfilelist = implode(',',$addedfilelist);
			$stm = $DBH->prepare("UPDATE imas_inlinetext SET fileorder=:fileorder WHERE id=:id");
			$stm->execute(array(':fileorder'=>$addedfilelist, ':id'=>$newtypeid));
		}

	} else if ($itemtype == "LinkedText") {
		//$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate) ";
		//$query .= "SELECT '$cid',title,summary,text,startdate,enddate FROM imas_linkedtext WHERE id='$typeid'";
		//mysql_query($query) or die("Query failed :$query " . mysql_error());
		$stm = $DBH->prepare("SELECT title,summary,text,startdate,enddate,avail,oncal,caltag,target,outcomes,points,fileid FROM imas_linkedtext WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		$istool = (substr($row['text'],0,8)=='exttool:');
		if ($istool) {
			$tool = explode('~~',substr($row['text'],8));
			if (isset($tool[3]) && isset($gbcats[$tool[3]])) {
				$tool[3] = $gbcats[$tool[3]];
			} else if ($_POST['ctc']!=$cid) {
				$tool[3] = 0;
			}
			$row['text'] = 'exttool:'.implode('~~',$tool);
		}
		if ($sethidden) {$row['avail'] = 0;}
		$row['title'] .= $_POST['append'];
		if ($row['outcomes']!='') {
			$curoutcomes = explode(',',$row['outcomes']);
			$newoutcomes = array();
			foreach ($curoutcomes as $o) {
				if (isset($outcomes[$o])) {
					$newoutcomes[] = $outcomes[$o];
				}
			}
			$row['outcomes'] = implode(',',$newoutcomes);
		}
		$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate,avail,oncal,caltag,target,outcomes,points,fileid) ";
		$query .= "VALUES (:courseid,:title,:summary,:text,:startdate,:enddate,:avail,:oncal,:caltag,:target,:outcomes,:points,:fileid) ";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid, ':title'=>$row['title'], ':summary'=>$row['summary'], ':text'=>$row['text'],
		   ':startdate'=>$row['startdate'], ':enddate'=>$row['enddate'], ':avail'=>$row['avail'], ':oncal'=>$row['oncal'], ':caltag'=>$row['caltag'],
			 ':target'=>$row['target'], ':outcomes'=>$row['outcomes'], ':points'=>$row['points'], ':fileid'=>$row['fileid']));
		$newtypeid = $DBH->lastInsertId();
		if ($istool) {
			$exttooltrack[$newtypeid] = intval($tool[0]);
		}
	} else if ($itemtype == "Forum") {
		$stm = $DBH->prepare("SELECT name,description,postinstr,replyinstr,startdate,enddate,settings,defdisplay,replyby,postby,avail,points,cntingb,gbcategory,forumtype,taglist,outcomes,caltag,allowlate,rubric,groupsetid,tutoredit,sortby,autoscore FROM imas_forums WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		if ($sethidden) {$row['avail'] = 0;}
		if (isset($gbcats[$row['gbcategory']])) {
			$row['gbcategory'] = $gbcats[$row['gbcategory']];
		} else if ($_POST['ctc']!=$cid) {
			$row['gbcategory'] = 0;
		}
		if ($_POST['ctc'] != $cid) {
			$row['groupsetid'] = 0;
		}
		$rubric = $row['rubric']; //array_pop($row);
		$row['name'] .= $_POST['append'];
		if ($row['outcomes']!='') {
			$curoutcomes = explode(',',$row['outcomes']);
			$newoutcomes = array();
			foreach ($curoutcomes as $o) {
				if (isset($outcomes[$o])) {
					$newoutcomes[] = $outcomes[$o];
				}
			}
			$row['outcomes'] = implode(',',$newoutcomes);
		}
		$query = "INSERT INTO imas_forums (courseid,name,description,postinstr,replyinstr,startdate,enddate,settings,defdisplay,replyby,postby,avail,points,cntingb,gbcategory,forumtype,taglist,outcomes,caltag,allowlate,groupsetid,tutoredit,sortby,autoscore) ";
		$query .= "VALUES (:courseid,:name,:description,:postinstr,:replyinstr,:startdate,:enddate,:settings,:defdisplay,:replyby,:postby,:avail,:points,:cntingb,:gbcategory,:forumtype,:taglist,:outcomes,:caltag,:allowlate,:groupsetid,:tutoredit,:sortby,:autoscore)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid, ':name'=>$row['name'], ':description'=>$row['description'], ':postinstr'=>$row['postinstr'],
		  ':replyinstr'=>$row['replyinstr'], ':startdate'=>$row['startdate'], ':enddate'=>$row['enddate'], ':settings'=>$row['settings'],
			':defdisplay'=>$row['defdisplay'], ':replyby'=>$row['replyby'], ':postby'=>$row['postby'], ':avail'=>$row['avail'], ':points'=>$row['points'],
			':cntingb'=>$row['cntingb'], ':gbcategory'=>$row['gbcategory'], ':forumtype'=>$row['forumtype'], ':taglist'=>$row['taglist'],
			':outcomes'=>$row['outcomes'], ':caltag'=>$row['caltag'], ':allowlate'=>$row['allowlate'],
			':groupsetid'=>$row['groupsetid'],':tutoredit'=>$row['tutoredit'],
			':sortby'=>$row['sortby'],':autoscore'=>$row['autoscore']));
		$newtypeid = $DBH->lastInsertId();
		if ($_POST['ctc']!=$cid) {
			$forumtrack[$typeid] = $newtypeid;
		}
		if ($rubric != 0) {
			$frubrictrack[$newtypeid] = $rubric;
		}
		if ($copystickyposts) {
			//copy instructor sticky posts
			$postcopy_stm = null; $update_threadid_stm = null;
			$stm = $DBH->prepare("SELECT subject,message,posttype,isanon,replyby FROM imas_forum_posts WHERE forumid=:forumid AND posttype>0");
			$stm->execute(array(':forumid'=>$typeid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if ($postcopy_stm===null) {
					$query = "INSERT INTO imas_forum_posts (forumid,userid,parent,postdate,subject,message,posttype,isanon,replyby) VALUES ";
					$query .= "(:forumid, :userid, :parent, :postdate, :subject, :message, :posttype, :isanon, :replyby)";
					$postcopy_stm = $DBH->prepare($query);
				}
				if (is_null($row[4]) || trim($row[4])=='') {
					$postcopy_stm->execute(array(':forumid'=>$newtypeid, ':userid'=>$userid, ':parent'=>0, ':postdate'=>$now, ':subject'=>$row[0], ':message'=>$row[1], ':posttype'=>$row[2], ':isanon'=>$row[3], ':replyby'=>NULL));
				} else {
					$postcopy_stm->execute(array(':forumid'=>$newtypeid, ':userid'=>$userid, ':parent'=>0, ':postdate'=>$now, ':subject'=>$row[0], ':message'=>$row[1], ':posttype'=>$row[2], ':isanon'=>$row[3], ':replyby'=>$row[4]));
				}
				$threadid = $DBH->lastInsertId();
				if ($update_threadid_stm===null) {
					$update_threadid_stm = $DBH->prepare("UPDATE imas_forum_posts SET threadid=:threadid WHERE id=:id");
					$newthread_stm = $DBH->prepare("INSERT INTO imas_forum_threads (id,forumid,lastposttime,lastpostuser) VALUES (:id, :forumid, :lastposttime, :lastpostuser)");
					$forumview_stm = $DBH->prepare("INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES (:userid, :threadid, :lastview)");
				}
				$update_threadid_stm->execute(array(':threadid'=>$threadid, ':id'=>$threadid));
				$newthread_stm->execute(array(':id'=>$threadid, ':forumid'=>$newtypeid, ':lastposttime'=>$now, ':lastpostuser'=>$userid));
				$forumview_stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid, ':lastview'=>$now));
			}
		}
	} else if ($itemtype == "Wiki") {
		$stm = $DBH->prepare("SELECT name,description,startdate,enddate,editbydate,avail,settings,groupsetid FROM imas_wikis WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		if ($sethidden) {$row['avail'] = 0;}
		$row['name'] .= $_POST['append'];
		$query = "INSERT INTO imas_wikis (courseid,name,description,startdate,enddate,editbydate,avail,settings,groupsetid) ";
		$query .= "VALUES (:courseid,:name,:description,:startdate,:enddate,:editbydate,:avail,:settings,:groupsetid)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid, ':name'=>$row['name'], ':description'=>$row['description'],
		  ':startdate'=>$row['startdate'], ':enddate'=>$row['enddate'], ':editbydate'=>$row['editbydate'], ':avail'=>$row['avail'],
			':settings'=>$row['settings'], ':groupsetid'=>$row['groupsetid']));
		$newtypeid = $DBH->lastInsertId();
	} else if ($itemtype == "Drill") {
		$stm = $DBH->prepare("SELECT name,summary,startdate,enddate,avail,caltag,itemdescr,itemids,scoretype,showtype,n,classbests,showtostu FROM imas_drillassess WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		if ($sethidden) {$row['avail'] = 0;}
		$row['name'] .= $_POST['append'];
		$query = "INSERT INTO imas_drillassess (courseid,name,summary,startdate,enddate,avail,caltag,itemdescr,itemids,scoretype,showtype,n,classbests,showtostu) ";
		$query .= "VALUES (:courseid,:name,:summary,:startdate,:enddate,:avail,:caltag,:itemdescr,:itemids,:scoretype,:showtype,:n,:classbests,:showtostu)";
		$stm = $DBH->prepare($query);
		$row['courseid'] = $cid;
		$stm->execute(array(':courseid'=>$cid, ':name'=>$row['name'], ':summary'=>$row['summary'], ':startdate'=>$row['startdate'],
		  ':enddate'=>$row['enddate'], ':avail'=>$row['avail'], ':caltag'=>$row['caltag'], ':itemdescr'=>$row['itemdescr'], ':itemids'=>$row['itemids'],
			':scoretype'=>$row['scoretype'], ':showtype'=>$row['showtype'], ':n'=>$row['n'], ':classbests'=>$row['classbests'], ':showtostu'=>$row['showtostu']));
		$newtypeid = $DBH->lastInsertId();
	} else if ($itemtype == "Assessment") {
		$query = "SELECT name,summary,intro,startdate,enddate,reviewdate,LPcutoff,
			timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,
			defpenalty,itemorder,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,
			allowlate,exceptionpenalty,noprint,avail,groupmax,isgroup,groupsetid,endmsg,
			deffeedbacktext,eqnhelper,caltag,calrtag,tutoredit,posttoforum,msgtoinstr,
			istutorial,viddata,reqscore,reqscoreaid,reqscoretype,ancestors,defoutcome,
			posttoforum,ptsposs,extrefs,submitby,showscores,showans,viewingb,scoresingb,
			ansingb,defregens,defregenpenalty,ver,keepscore,overtime_grace,overtime_penalty,
			showwork,autoexcuse
			FROM imas_assessments WHERE id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$typeid));
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		if ($row['ptsposs']==-1) {
			$row['ptsposs'] = updatePointsPossible($typeid, $row['itemorder'], $row['defpoints']);
		}
		$srcdefpoints = $row['defpoints'];
		if ($sethidden) {$row['avail'] = 0;}
		if (isset($gbcats[$row['gbcategory']])) {
			$row['gbcategory'] = $gbcats[$row['gbcategory']];
		} else if ($_POST['ctc']!=$cid) {
			$row['gbcategory'] = 0;
		}
		if (isset($outcomes[$row['defoutcome']])) {
			$row['defoutcome'] = $outcomes[$row['defoutcome']];
		} else {
			$row['defoutcome'] = 0;
		}
		if (!empty($sourcecid)) {
			$newancestor = intval($sourcecid).':'.$typeid;
		} else {
			$newancestor = $typeid;
		}
		if ($row['ancestors']=='') {
			$row['ancestors'] = $newancestor;
		} else {
			$row['ancestors'] = $newancestor.','.$row['ancestors'];
		}
		if ($_POST['ctc']!=$cid) {
			$forumtopostto = $row['posttoforum'];
			unset($row['posttoforum']);
			if ($row['isgroup']>0) {
				//assessment was a group assessment, but we're copying into another
				//course.  Either need to create a new groupset, or make it not groups
				//we'll create a new group if the existing one was an auto-created group
				$stm2 = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=?");
				$stm2->execute(array($row['groupsetid']));
				$existingGroupsetName = $stm2->fetchColumn(0);
				if ($existingGroupsetName != _('Group set for').' '.$row['name']) {
					//not an autocreated group - make it not group so teacher can attach
					//a group later
					$row['isgroup'] = 0;
					$row['groupsetid'] = 0;
				} else {
					//create a new groupset
					$stm2 = $DBH->prepare("INSERT INTO imas_stugroupset (courseid,name) VALUES (:courseid, :name)");
					$stm2->execute(array(':courseid'=>$cid, ':name'=>$existingGroupsetName));
					$row['groupsetid'] = $DBH->lastInsertId();
				}
			}
		}

		$reqscoreaid = $row['reqscoreaid'];
		if ($cid != $sourcecid) { // if same course, can keep this
			unset($row['reqscoreaid']);
        }
        $autoexcuse = $row['autoexcuse'];
        if ($cid != $sourcecid) { // if same course, can keep this
			unset($row['autoexcuse']);
        }
		$row['name'] .= $_POST['append'];

		$row['courseid'] = $cid;

		if (isset($datesbylti) && $datesbylti==true) {
			$row['date_by_lti'] = 1;
		} else {
			$row['date_by_lti'] = 0;
		}

		$itemorder = $row['itemorder'];
		unset($row['itemorder']);

		$aver = $row['ver'];
		if (isset($convertAssessVer) && $convertAssessVer > $aver) {
			$row = migrateAssessSettings($row, $aver, $convertAssessVer);
			$questionDefaults = array('defattempts' => $row['defattempts']);
		}

		$fields = implode(",", array_keys($row));
		//$vals = "'".implode("','",addslashes_deep(array_values($row)))."'";
		$fieldplaceholders = ':'.implode(',:', array_keys($row));
		$stm = $DBH->prepare("INSERT INTO imas_assessments ($fields) VALUES ($fieldplaceholders)");
		$queryarr = array();
		foreach ($row as $k=>$v) {
			$queryarr[":$k"] = $v;
		}
		$stm->execute($queryarr);
		$newtypeid = $DBH->lastInsertId();
		if ($reqscoreaid>0) {
			$reqscoretrack[$newtypeid] = $reqscoreaid;
		}
		if ($_POST['ctc']!=$cid && $forumtopostto>0) {
			$posttoforumtrack[$newtypeid] = $forumtopostto;
        }
        if ($autoexcuse !== null && $autoexcuse !== '' && $cid != $sourcecid) {
            $autoexcusetrack[$newtypeid] = $autoexcuse;
        }
		$assessnewid[$typeid] = $newtypeid;
		$thiswithdrawn = array();
		$needToUpdatePtsPoss = false;

		// remap itemorder
		if (trim($itemorder)!='') {
			$flat = preg_replace('/\d+\|\d+~/','',$itemorder);
			$flat = str_replace('~',',',$itemorder);
			$itemorderarr = explode(',', $flat);
			$goodqs = array();
			foreach ($itemorderarr as $v) {
				if (is_numeric($v)) {
					$goodqs[] = $v;
				}
			}
			$flat = implode(',', $goodqs);
			//$flat is santized above
			$query = "SELECT id,questionsetid,points,attempts,penalty,category,regen,
				showans,showhints,rubric,withdrawn,fixedseeds,showwork FROM imas_questions
				WHERE id IN ($flat)";
			$stm = $DBH->query($query);
			$inssph = array(); $inss = array();
			$insorder = array();

			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				if (isset($convertAssessVer) && $convertAssessVer > $aver) {
					$row = migrateQuestionSettings($row, $questionDefaults, $aver, $convertAssessVer);
				}
				if ($row['withdrawn']>0) {
					$needToUpdatePtsPoss = true;
				}
				if ($row['withdrawn']>0 && $removewithdrawn) {
					$thiswithdrawn[$row['id']] = 1;
					continue;
				}
				if (isset($replacebyarr[$row['questionsetid']])) {
					$row['questionsetid'] = $replacebyarr[$row['questionsetid']];
				}
				if (is_numeric($row['category'])) {
					if (isset($outcomes[$row['category']])) {
						$row['category'] = $outcomes[$row['category']];
					} else {
						$row['category'] = 0;
					}
				}
				$inssph[] = "(?,?,?,?,?,?,?,?,?,?,?)";
				array_push($inss, $newtypeid, $row['questionsetid'],$row['points'],$row['attempts'],$row['penalty'],$row['category'],$row['regen'],$row['showans'],$row['showhints'],$row['fixedseeds'],$row['showwork']);
				$rubric[$row['id']] = $row['rubric'];
				//check for a category that's set to an assessment e.g. AID-1234
				if (0==strncmp($row['category'],"AID-",4)) {
					//temporarily save the old assessment id
					$categoryassessmentold[$row['id']]=substr($row['category'],4);
				}
				$insorder[] = $row['id'];
			}
			$idtoorder = array_flip($insorder);

			if (count($inss)>0) {
				$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category,regen,showans,showhints,fixedseeds,showwork) ";
				$query .= "VALUES ".implode(',',$inssph);
				$stm = $DBH->prepare($query);
				$stm->execute($inss);
				$firstnewid = $DBH->lastInsertId();

				$aitems = explode(',',$itemorder);
				$newaitems = array();
				foreach ($aitems as $k=>$aitem) {
					if (strpos($aitem,'~')===FALSE) {
						if (isset($thiswithdrawn[$aitem])) { continue;}
						if ($rubric[$aitem]!=0) {
							$qrubrictrack[$firstnewid+$idtoorder[$aitem]] = $rubric[$aitem];
						}
						//check for a category that's set to an assessment
						if (isset($categoryassessmentold[$aitem])) {
							//track by new questionid but still using old assessmentid
							$categoryassessmenttrack[$firstnewid+$idtoorder[$aitem]] = $categoryassessmentold[$aitem];
						}
						$newaitems[] = $firstnewid+$idtoorder[$aitem];
					} else {
						$sub = explode('~',$aitem);
						$newsub = array();
						$front = 0;
						if (strpos($sub[0],'|')!==false) { //true except for bwards compat
							$newsub[] = array_shift($sub);
							$front = 1;
						}
						foreach ($sub as $subi) {
							if (isset($thiswithdrawn[$subi])) { continue;}
							if ($rubric[$subi]!=0) {
								$qrubrictrack[$firstnewid+$idtoorder[$subi]] = $rubric[$subi];
							}
							//check for a category that's set to an assessment
							if (isset($categoryassessmentold[$subi])) {
								//track by new questionid but still using old assessmentid
								$categoryassessmenttrack[$firstnewid+$idtoorder[$subi]] = $categoryassessmentold[$subi];
							}
							$newsub[] = $firstnewid+$idtoorder[$subi];
						}
						if (count($newsub)==$front) {

						} else if (count($newsub)==$front+1) {
							$newaitems[] = $newsub[$front];
						} else {
							$newaitems[] = implode('~',$newsub);
						}
					}
				}
				$newitemorder = implode(',',$newaitems);
				$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder WHERE id=:id");
				$stm->execute(array(':itemorder'=>$newitemorder, ':id'=>$newtypeid));
				//Temporary: force recalculation of points possible on copying,
				// to fix any lingering buggy ptsposs values
				//if ($needToUpdatePtsPoss) {
					$newptsposs = updatePointsPossible($newtypeid, $newitemorder, $srcdefpoints);
				//}
			}
		}
	} else if ($itemtype == "Calendar") {
		$newtypeid = 0;
    } else if (function_exists('copyitem_can_handle_type') && 
        copyitem_can_handle_type($itemtype)
    ) {
        $newtypeid = copyitem_copy_item($itemtype, $typeid);
    }
	$itemtypemap[$itemtype.$typeid] = $newtypeid;
	$query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
	$query .= "VALUES (:courseid, :itemtype, :typeid)";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':itemtype'=>$itemtype, ':typeid'=>$newtypeid));
	return ($DBH->lastInsertId());
}

function copysub($items,$parent,&$addtoarr,$gbcats=false,$sethidden=false) {
	global $checked,$blockcnt;
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			if (array_search($parent.'-'.($k+1),$checked)!==FALSE) { //copy block
				$newblock = array();
				$newblock['name'] = $item['name'].$_POST['append'];
				$newblock['id'] = $blockcnt;
				$blockcnt++;
				$newblock['startdate'] = $item['startdate'];
				$newblock['enddate'] = $item['enddate'];
				$newblock['avail'] = $sethidden?0:$item['avail'];
				$newblock['SH'] = $item['SH'];
				$newblock['colors'] = $item['colors'];
				$newblock['public'] = $item['public'];
				$newblock['fixedheight'] = $item['fixedheight'];
				$newblock['grouplimit'] = $item['grouplimit'];
				$newblock['items'] = array();
				if (count($item['items'])>0) {
					copysub($item['items'],$parent.'-'.($k+1),$newblock['items'],$gbcats,$sethidden);
				}
				$addtoarr[] = $newblock;
			} else {
				if (count($item['items'])>0) {
					copysub($item['items'],$parent.'-'.($k+1),$addtoarr,$gbcats,$sethidden);
				}
			}
		} else {
			if (array_search($item,$checked)!==FALSE) {
				$newitem = copyitem($item,$gbcats,$sethidden);
				if ($newitem!==false) {
					$addtoarr[] = $newitem;
			}
		}
	}
	}

}

function doaftercopy($sourcecid, &$newitems) {
	global $DBH;
    global $cid,$reqscoretrack,$categoryassessmenttrack,$assessnewid,$forumtrack;
    global $posttoforumtrack,$autoexcusetrack,$outcomes;
	if (intval($cid)==intval($sourcecid)) {
		$samecourse = true;
	} else {
		$samecourse = false;
	}
	//update reqscoreaids if possible.
	if (count($reqscoretrack)>0) {
		$stmA = $DBH->prepare("UPDATE imas_assessments SET reqscoreaid=:reqscoreaid WHERE id=:id");
		$stmB = $DBH->prepare("UPDATE imas_assessments SET reqscore=0 WHERE id=:id");
		foreach ($reqscoretrack as $newid=>$oldreqaid) {
			//is old reqscoreaid in copied list?
			if (isset($assessnewid[$oldreqaid])) {
				$stmA->execute(array(':reqscoreaid'=>$assessnewid[$oldreqaid], ':id'=>$newid));
			} else if (!$samecourse) {
				$stmB->execute(array(':id'=>$newid));
			}
		}
	}
	//update any assessment ids in categories
	if (count($categoryassessmenttrack)>0) {
		$stmA = $DBH->prepare("UPDATE imas_questions SET category=:category WHERE id=:id");
		$stmB = $DBH->prepare("UPDATE imas_questions SET category=0 WHERE id=:id");
		foreach ($categoryassessmenttrack as $newqid=>$oldcategoryaid) {
			//is oldcategoryaid in copied list?
			if (isset($assessnewid[$oldcategoryaid])) {
				$stmA->execute(array(':id'=>$newqid, ':category'=>"AID-".$assessnewid[$oldcategoryaid]));
			} else if (!$samecourse) { //since that assessment isn't being copied, unclear what category should be
				$stmB->execute(array(':id'=>$newqid));
			}
		}
    }
    // update autoexcuse on copy; only happens on copy into new course
    if (count($autoexcusetrack) > 0) {
        $stmA = $DBH->prepare("UPDATE imas_assessments SET autoexcuse=:autoexcuse WHERE id=:id");
        foreach ($autoexcusetrack as $newaid=>$autoexc) {
            $catcnt = 1;
            $autoexcuse = json_decode($autoexc, true);
            foreach ($autoexcuse as $k=>$v) {
                if (isset($assessnewid[$v['aid']])) {
                    $autoexcuse[$k]['aid'] = $assessnewid[$v['aid']];
                } else {
                    unset($autoexcuse[$k]);
                    continue;
                }
                if (is_numeric($v['cat'])) {  // an outcome
                    if (isset($outcomes[$v['cat']])) {
                        $autoexcuse[$k]['cat'] = $outcomes[$v['cat']];
                    } else {
                        $autoexcuse[$k]['cat'] = _('Category').' '.$catcnt;
                        $cntcnt++;
                    }
                } else if (substr($v['cat'],0,4)=='AID-') {
                    if (isset($assessnewid[substr($v['cat'],4)])) {
                        $autoexcuse[$k]['cat'] = 'AID-' . $assessnewid[substr($v['cat'],4)];
                    } else {
                        $autoexcuse[$k]['cat'] = _('Category').' '.$catcnt;
                        $cntcnt++;
                    }
                }
            }
            $autoexcuse = array_values($autoexcuse);
            if (count($autoexcuse) > 0) {
                $stmA->execute(array(':autoexcuse'=>json_encode($autoexcuse), ':id'=>$newaid));
            } else {
                $stmA->execute(array(':autoexcuse'=>null, ':id'=>$newaid));
            }
        }
    }

	if (count($posttoforumtrack)>0) {
		$stmA = $DBH->prepare("UPDATE imas_assessments SET posttoforum=:posttoforum WHERE id=:id");
		$stmB = $DBH->prepare("UPDATE imas_assessments SET posttoforum=0 WHERE id=:id");
		foreach ($posttoforumtrack as $newaid=>$oldforumid) {
			if (isset($forumtrack[$oldforumid])) {
				$stmA->execute(array(':posttoforum'=>$forumtrack[$oldforumid], ':id'=>$newaid));
			} else {
				$stmB->execute(array(':id'=>$newaid));
			}
		}
	}
	if (!$samecourse) {
		handleextoolcopy($sourcecid);
		removeGrouplimits($newitems);
	}
}

function removeGrouplimits(&$items) {
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$item['grouplimit'] = array();
			removeGrouplimits($items[$k]['items']);
		}
	}
}

function copyallsub($items,$parent,&$addtoarr,$gbcats=false,$sethidden=false) {
	global $blockcnt,$reqscoretrack,$assessnewid;;
	if (strlen($_POST['append'])>0 && $_POST['append']{0}!=' ') {
		$_POST['append'] = ' '.$_POST['append'];
	}
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$newblock = array();
			$newblock['name'] = $item['name'].$_POST['append'];
			$newblock['id'] = $blockcnt;
			$blockcnt++;
			$newblock['startdate'] = $item['startdate'];
			$newblock['enddate'] = $item['enddate'];
			$newblock['avail'] = $sethidden?0:$item['avail'];
			$newblock['SH'] = $item['SH'];
			$newblock['colors'] = $item['colors'];
			$newblock['public'] = $item['public'];
			$newblock['fixedheight'] = $item['fixedheight'];
			$newblock['grouplimit'] = $item['grouplimit'];
			$newblock['items'] = array();
			if (count($item['items'])>0) {
				copyallsub($item['items'],$parent.'-'.($k+1),$newblock['items'],$gbcats,$sethidden);
			}
			$addtoarr[] = $newblock;
		} else {
			if ($item != null && $item != 0) {
				$newitem = copyitem($item,$gbcats,$sethidden);
				if ($newitem!==false) {
					$addtoarr[] = $newitem;
			}
		}
	}
	}

}


function getiteminfo($itemid) {
	global $DBH;
	$stm = $DBH->prepare("SELECT itemtype,typeid FROM imas_items WHERE id=:id");
	$stm->execute(array(':id'=>$itemid));
	if ($stm->rowCount()==0) {
		echo "Uh oh, item #".Sanitize::onlyInt($itemid)." doesn't appear to exist";
		return array(false,false,false,false);
	}
	list($itemtype, $typeid) = $stm->fetch(PDO::FETCH_NUM);
	if ($itemtype==='Calendar') {
		return array($itemtype,'Calendar','');
	}
	switch($itemtype) {
		case ($itemtype==="InlineText"):
			$stm = $DBH->prepare("SELECT title,text FROM imas_inlinetext WHERE id=:id");
			break;
		case ($itemtype==="LinkedText"):
			$stm = $DBH->prepare("SELECT title,summary FROM imas_linkedtext WHERE id=:id");
			break;
		case ($itemtype==="Forum"):
			$stm = $DBH->prepare("SELECT name,description FROM imas_forums WHERE id=:id");
			break;
		case ($itemtype==="Assessment"):
			$stm = $DBH->prepare("SELECT name,summary FROM imas_assessments WHERE id=:id");
			break;
		case ($itemtype==="Wiki"):
			$stm = $DBH->prepare("SELECT name,description FROM imas_wikis WHERE id=:id");
			break;
		case ($itemtype==="Drill"):
			$stm = $DBH->prepare("SELECT name,summary FROM imas_drillassess WHERE id=:id");
			break;
	}
	$stm->execute(array(':id'=>$typeid));
	list($name, $summary) = $stm->fetch(PDO::FETCH_NUM);
	return array($itemtype,$name,$summary,$typeid);
}

function getsubinfo($items,$parent,$pre,$itemtypelimit=false,$spacer='|&nbsp;&nbsp;') {
	global $ids,$types,$names,$sums,$parents,$gitypeids,$prespace,$CFG,$itemshowdata;
	if (!isset($gitypeids)) {
		$gitypeids = array();
	}

	foreach($items as $k=>$item) {
		if (is_array($item)) {
			$ids[] = $parent.'-'.($k+1);
			$types[] = "Block";
			$names[] = $item['name'];
			$prespace[] = $pre;
			$parents[] = $parent;
			$gitypeids[] = '';
			$sums[] = '';
			if (count($item['items'])>0) {
				getsubinfo($item['items'],$parent.'-'.($k+1),$pre.$spacer,$itemtypelimit,$spacer);
			}
		} else {
			if ($item==null || $item=='') {
				continue;
			}
			if (!empty($itemshowdata)) {
				array($itemtype,$name,$summary,$typeid);
				if (isset($itemshowdata[$item]['name'])) {
					$name = $itemshowdata[$item]['name'];
				} else {
					$name = $itemshowdata[$item]['title'];
				}
				if (isset($itemshowdata[$item]['summary'])) {
					$summary = $itemshowdata[$item]['summary'];
				} else if (isset($itemshowdata[$item]['text'])) {
					$summary = $itemshowdata[$item]['text'];
				} else {
					$summary = $itemshowdata[$item]['description'];
				}
				$arr = array($itemshowdata[$item]['itemtype'], $name, $summary, $itemshowdata[$item]['id']);
			} else {
				$arr = getiteminfo($item);
			}
			if ($arr[0]===false || ($itemtypelimit!==false && $arr[0]!=$itemtypelimit)) {
				continue;
			}
			$ids[] = $item;
			$parents[] = $parent;
			$types[] = $arr[0];
			$names[] = $arr[1];
			$prespace[] = $pre;
			$gitypeids[] = $arr[3];
			$arr[2] = strip_tags($arr[2]);
			if (strlen($arr[2])>100) {
				$arr[2] = substr($arr[2],0,97).'...';
			}
			$sums[] = $arr[2];
		}
	}
}

function buildexistblocks($items,$parent,$pre='') {
	global $existblocks;
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$existblocks[$parent.'-'.($k+1)] = $pre.$item['name'];
			if (count($item['items'])>0) {
				buildexistblocks($item['items'],$parent.'-'.($k+1),$pre.'&nbsp;&nbsp;');
			}
		}
	}
}

function copyrubrics($offlinerubrics=array()) {
	global $DBH,$userid,$groupid,$qrubrictrack,$frubrictrack;
	if (count($qrubrictrack)==0 && count($frubrictrack)==0 && count($offlinerubrics)==0) { return;}
	$list = implode(',',array_map('intval',array_merge($qrubrictrack,$frubrictrack,$offlinerubrics)));

	//handle rubrics which I already have access to
	$iqstm = null; $igstm = null; $ifstm = null;
	$stm = $DBH->prepare("SELECT id FROM imas_rubrics WHERE id IN ($list) AND (ownerid=:ownerid OR groupid=:groupid)"); //$list sanitized above
	$stm->execute(array(':ownerid'=>$userid, ':groupid'=>$groupid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$qfound = array_keys($qrubrictrack,$row[0]);
		if (count($qfound)>0) {
			foreach ($qfound as $qid) {
				if ($iqstm===null) { //prepare once
					$iqstm = $DBH->prepare("UPDATE imas_questions SET rubric=:rubric WHERE id=:id");
				}
				$iqstm->execute(array(':rubric'=>$row[0], ':id'=>$qid));
			}
		}
		$ofound = array_keys($offlinerubrics,$row[0]);
		if (count($ofound)>0) {
			foreach ($ofound as $oid) {
				if ($igstm===null) { //prepare once
					$igstm = $DBH->prepare("UPDATE imas_gbitems SET rubric=:rubric WHERE id=:id");
				}
				$igstm->execute(array(':rubric'=>$row[0], ':id'=>$oid));
			}
		}
		$ffound = array_keys($frubrictrack,$row[0]);
		if (count($ffound)>0) {
			foreach ($ffound as $fid) {
				if ($ifstm===null) { //prepare once
					$ifstm = $DBH->prepare("UPDATE imas_forums SET rubric=:rubric WHERE id=:id");
				}
				$ifstm->execute(array(':rubric'=>$row[0], ':id'=>$fid));
			}
		}
	}

	//handle rubrics which I don't already have access to - need to copy them
	$rub_search_stm = $DBH->prepare("SELECT id FROM imas_rubrics WHERE rubric=:rubric AND (ownerid=:ownerid OR groupid=:groupid)");
	$rub_ins_stm = $DBH->prepare("INSERT INTO imas_rubrics (ownerid,groupid,name,rubrictype,rubric) VALUES (:ownerid,-1,:name,:rubrictype,:rubric)");
	$iqins = null; $ifupd = null; $igupd=null;

	$stm = $DBH->prepare("SELECT id,name,rubrictype,rubric FROM imas_rubrics WHERE id IN ($list) AND NOT (ownerid=:ownerid OR groupid=:groupid)"); //$list sanitized above
	$stm->execute(array(':ownerid'=>$userid, ':groupid'=>$groupid));
	while ($srcrub = $stm->fetch(PDO::FETCH_ASSOC)) {
		//echo "handing {$row[0]} which I don't have access to<br/>";
		//$stm2 = $DBH->prepare("SELECT name,rubrictype,rubric FROM imas_rubrics WHERE id=:id");
		//$stm2->execute(array(':id'=>$row[0]));
		//$rubrow = addslashes_deep($stm->fetch(PDO::FETCH_NUM));
		//$srcrub = $stm2->fetch(PDO::FETCH_ASSOC);
		$rub_search_stm->execute(array(':rubric'=>$srcrub['rubric'], ':ownerid'=>$userid, ':groupid'=>$groupid));
		if ($rub_search_stm->rowCount()>0) {
			$newid = $rub_search_stm->fetchColumn(0);
			//echo "found existing of mine, $newid<br/>";
		} else {
			$rub_ins_stm->execute(array(':ownerid'=>$userid, ':name'=>$srcrub['name'], ':rubrictype'=>$srcrub['rubrictype'], ':rubric'=>$srcrub['rubric']));
			$newid = $DBH->lastInsertId();
			//echo "created $newid<br/>";
		}

		$qfound = array_keys($qrubrictrack,$srcrub['id']);
		if (count($qfound)>0) {
			if ($iqupd===null) {
				$iqupd = $DBH->prepare("UPDATE imas_questions SET rubric=:rubric WHERE id=:id");
			}
			foreach ($qfound as $qid) {
				$iqupd->execute(array(':rubric'=>$newid, ':id'=>$qid));
				//echo "updating imas_questions on qid $qid<br/>";

			}
		}
		$ffound = array_keys($frubrictrack,$srcrub['id']);
		if (count($ffound)>0) {
			if ($ifupd===null) {
				$ifupd = $DBH->prepare("UPDATE imas_forums SET rubric=:rubric WHERE id=:id");
			}
			foreach ($ffound as $fid) {
				$ifupd->execute(array(':rubric'=>$newid, ':id'=>$fid));
			}
		}
		$ofound = array_keys($offlinerubrics,$srcrub['id']);
		if (count($ofound)>0) {
			if ($igupd===null) {
				$igupd = $DBH->prepare("UPDATE imas_gbitems SET rubric=:rubric WHERE id=:id");
			}
			foreach ($ofound as $oid) {
				$igupd->execute(array(':rubric'=>$newid, ':id'=>$oid));
			}
		}
	}
}

function handleextoolcopy($sourcecid) {
	//assumes this is a copy into a different course
	global $DBH,$cid,$userid,$groupid,$exttooltrack;
	if (count($exttooltrack)==0) {return;}
	//$exttooltrack is linked text id => tool id
	$toolmap = array();
	$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
	$stm->execute(array(':courseid'=>$sourcecid, ':userid'=>$userid));
	if ($stm->rowCount()>0) {
		$oktocopycoursetools = true;
	}
	$toolidlist = implode(',',array_map('intval',$exttooltrack));
	$ext_search_stm = $DBH->prepare("SELECT id FROM imas_external_tools WHERE url=:url AND courseid=:courseid");
	$query = "INSERT INTO imas_external_tools (courseid,groupid,name,url,ltikey,secret,custom,privacy) ";
	$query .= "VALUES (:courseid,:groupid,:name,:url,:ltikey,:secret,:custom,:privacy)";
	$ext_insert_stm = $DBH->prepare($query);
	$ext_remap_stm = null;

	$query = "SELECT id,courseid,groupid,name,url,ltikey,secret,custom,privacy FROM imas_external_tools ";
	$query .= "WHERE id IN ($toolidlist)";
	$stm = $DBH->query($query); //toolidlist sanitized above
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$doremap = false;
		if (!isset($toolmap[$row['id']])) {
			//try url matching of existing tools in the destination course
			$ext_search_stm->execute(array(':courseid'=>$cid, ':url'=>$row['url']));
			if ($ext_search_stm->rowCount()>0) {
				$toolmap[$row['id']] = $ext_search_stm->fetchColumn(0);
			}
		}
		if (isset($toolmap[$row['id']])) {
			//already have remapped this tool - need to update linkedtext item
			$doremap = true;
		} else if ($row['courseid']>0 && $oktocopycoursetools) {
			//do copy
			$ext_insert_stm->execute(array(':courseid'=>$cid, ':groupid'=>$groupid, ':name'=>$row['name'], ':url'=>$row['url'],
				':ltikey'=>$row['ltikey'], ':secret'=>$row['secret'], ':custom'=>$row['custom'], ':privacy'=>$row['privacy']));
			$toolmap[$row['id']] = $DBH->lastInsertId();
			$doremap = true;
		} else if ($row['courseid']==0 && ($row['groupid']==0 || $row['groupid']==$groupid)) {
			//no need to copy anything - tool will just work
		} else {
			//not OK to copy; must disable tool in linked text item
			$toupdate = implode(",",array_map('intval',array_keys($exttooltrack, $row['id'])));
			$DBH->query("UPDATE imas_linkedtext SET text='<p>Unable to copy tool</p>' WHERE id IN ($toupdate)"); //sanitized above
		}
		if ($doremap) {
			//update the linkedtext item with the new tool id
			$toupdate = implode(",",array_map('intval',array_keys($exttooltrack, $row['id'])));
			$stm2 = $DBH->query("SELECT id,text FROM imas_linkedtext WHERE id IN ($toupdate)");
			while ($r = $stm2->fetch(PDO::FETCH_ASSOC)) {
				$text = str_replace('exttool:'.$row['id'].'~~','exttool:'.$toolmap[$row['id']].'~~',$r['text']);
				if ($ext_remap_stm===null) {
					$ext_remap_stm = $DBH->prepare("UPDATE imas_linkedtext SET text=:text WHERE id=:id");
				}
				$ext_remap_stm->execute(array(':id'=>$r['id'], ':text'=>$text));
			}
		}
	}
}

function copyallcalitems($sourcecid,$destcid) {
	global $DBH;
	$stm = $DBH->prepare("SELECT date,tag,title FROM imas_calitems WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$sourcecid));
	$insarr = array();
	$qarr = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$insarr[] = "(?,?,?,?)";
		array_push($qarr, $destcid, $row[0], $row[1], $row[2]);
	}
	if (count($qarr)>0) {
		$query = "INSERT INTO imas_calitems (courseid,date,tag,title) VALUES ";
		$query .= implode(',',$insarr);
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);
	}
}

?>
