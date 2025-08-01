<?php
//IMathAS:  Modify a question's code
//(c) 2006 David Lippman
//
//license options:
//0 - contains copywrited content
//1 - IMathAS community license (GPL + CC-BY)
//2 - Public domain
//3 - CC-BY-SA-NC
//4 - CC-BY-SA

	require_once "../init.php";
	require_once '../includes/videodata.php';
	require_once '../includes/htmlutil.php';

	if ($myrights<20) {
		require_once "../header.php";
		echo _("You need to log in as a teacher to access this page");
		require_once "../footer.php";
		exit;
	}

	// Determine if this is an AJAX quicksave call
	$quicksave = isset($_GET['quick']) ? true : false;

 	$cid = Sanitize::courseId($_GET['cid'] ?? '0');
	$isadmin = false;
	$isgrpadmin = false;
	if ($cid==='admin') {
		if ($myrights==100) {
			$isadmin = true;
		} else if ($myrights==75) {
			$isgrpadmin = true;
		}
	}

	if (isset($adminasteacher) && $adminasteacher) {
		if ($myrights == 100) {
			$isadmin = true;
		} else if ($myrights==75) {
			$isgrpadmin = true;
		}
	}

	if (isset($_GET['frompot'])) {
		$frompot = 1;
	} else {
		$frompot = 0;
    }
    if (!empty($_GET['from']) && $_GET['from'] == 'addq2') {
        $addq = 'addquestions2';
        $from = 'addq2';
    } else {
        $addq = 'addquestions';
        $from = 'addq';
    }
    $viewonly = false;
    if (!empty($_GET['viewonly']) || !empty($_POST['viewonly']) ) {
        $viewonly = true;
    }

	$testqpage = ($cid == 0 || $courseUIver>1) ? 'testquestion2.php' : 'testquestion.php';

	$outputmsg = '';
	$errmsg = '';
	if (isset($_POST['qtext'])) {
		require_once "../includes/filehandler.php";
		$now = time();
		foreach (array('qcontrol','answer','solution') as $v) {
			if (!isset($_POST[$v])) {$_POST[$v] = '';}
		}
		$_POST['qtext'] = Sanitize::replaceSmartQuotes($_POST['qtext']);
		$_POST['control'] = Sanitize::replaceSmartQuotes($_POST['control'] ?? '');
		$_POST['qcontrol'] = Sanitize::replaceSmartQuotes($_POST['qcontrol'] ?? '');
		$_POST['answer'] = Sanitize::replaceSmartQuotes($_POST['answer'] ?? '');
		$_POST['solution'] = Sanitize::replaceSmartQuotes($_POST['solution'] ?? '');
		$_POST['qtext'] = preg_replace('/<span\s+class="AM"[^>]*>(.*?)<\/span>/sm','$1', $_POST['qtext']);
		$_POST['solution'] = preg_replace('/<span\s+class="AM"[^>]*>(.*?)<\/span>/sm','$1', $_POST['solution']);

		if (trim($_POST['solution'])=='<p></p>') {
			$_POST['solution'] = '';
		}

		if (strpos($_POST['qtext'],'data:image')!==false) {
			require_once "../includes/htmLawed.php";
			$_POST['qtext'] = convertdatauris($_POST['qtext']);
		}

		//handle help references
		if (isset($_GET['id']) || isset($_GET['templateid'])) {
			$stm = $DBH->prepare("SELECT extref FROM imas_questionset WHERE id=:id");
			if (isset($_GET['id'])) {
				$stm->execute(array(':id'=>$_GET['id']));
			} else {
				$stm->execute(array(':id'=>$_GET['templateid']));
			}
			$extref = $stm->fetchColumn(0);
			if ($extref=='') {
				$extref = array();
			} else {
				$extref = explode('~~',$extref);
			}

			$newextref = array();
			for ($i=0;$i<count($extref);$i++) {
				if (!isset($_POST["delhelp-$i"])) {
					$newextref[] = $extref[$i];
				}
			}
		} else {
			$newextref = array();
		}
		//DO we need to add a checkbox or something for updating this if captions are added later?
		if (isset($_POST['helpurl']) && $_POST['helpurl']!='') {
			$vidid = getvideoid($_POST['helpurl']);
			if ($vidid=='') {
				$captioned = 0;
			} else if (isset($CFG['YouTubeAPIKey'])) {
                $captioned = getCaptionDataByVidId($vidid);
				if ($captioned === false || $captioned === '404') {
					$captioned = 0;
				}
            }
            $helpdescr = str_replace(['!!','~~'],'',Sanitize::stripHtmlTags($_POST['helpdescr']));
			$newextref[] = $_POST['helptype'].'!!'.trim($_POST['helpurl']).'!!'.$captioned.'!!'.$helpdescr;
		}
		$extref = implode('~~',$newextref);
		if (isset($_POST['doreplaceby'])) {
			$replaceby = intval($_POST['replaceby']);
		} else {
			$replaceby = 0;
		}
		$solutionopts = 0;
		if (isset($_POST['usesrand'])) {
			$solutionopts += 1;
		}
		if (isset($_POST['useashelp'])) {
			$solutionopts += 2;
		}
		if (isset($_POST['usewithans'])) {
			$solutionopts += 4;
		}
		$_POST['qtext'] = str_replace(array('<!--','-->'),array('&&&L!--','--&&&G'),$_POST['qtext']);
        $_POST['qtext'] = preg_replace_callback('/(<script[^>]*>)(.*?)<\/script>/sm', function($matches) {
            return $matches[1] . str_replace(array("<",">"),array("&&&L","&&&G"), $matches[2]) . '</script>';
        }, $_POST['qtext']);
		$_POST['qtext'] = preg_replace('/<(\/?\w[^<>]*?)>/',"&&&L$1&&&G",$_POST['qtext']);
		$_POST['qtext'] = str_replace(array("<",">"),array("&lt;","&gt;"),$_POST['qtext']);
		$_POST['qtext'] = str_replace(array("&&&L","&&&G"),array("<",">"),$_POST['qtext']);
        $_POST['solution'] = str_replace(array('<!--','-->'),array('&&&L!--','--&&&G'),$_POST['solution']);
		$_POST['solution'] = preg_replace('/<([^<>]+?)>/',"&&&L$1&&&G",$_POST['solution']);
		$_POST['solution'] = str_replace(array("<",">"),array("&lt;","&gt;"),$_POST['solution']);
		$_POST['solution'] = str_replace(array("&&&L","&&&G"),array("<",">"),$_POST['solution']);

		if ($_POST['a11yalttype']>0 && is_numeric($_POST['a11yalt'])) {
			$a11yalttype = intval($_POST['a11yalttype']);
			$a11yalt = intval($_POST['a11yalt']);
		} else {
			$a11yalttype = 0;
			$a11yalt = 0;
		}

        $isrand = preg_match('/((shuffle|getprimes?|rand[a-zA-Z]*)\s*\(|randweights)/',$_POST['control']) ? 1 : 0;
        if (!$isrand) { // check any included code
            preg_match_all('/includecodefrom\(\s*(\d+)\s*\)/',$_POST['control'],$matches,PREG_PATTERN_ORDER);
            if (!empty($matches[1])) {
                $ph = Sanitize::generateQueryPlaceholders($matches[1]);
                $stm = $DBH->prepare("SELECT control FROM imas_questionset WHERE id IN ($ph)");
                $stm->execute($matches[1]);
                while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                    $isrand = preg_match('/(shuffle|getprimes?|rand[a-zA-Z]*)\(/',$row['control']) ? 1 : 0;
                    if ($isrand) { break; }
                }
            }
        }

		if (isset($_GET['id'])) { //modifying existing
			$qsetid = intval($_GET['id']);
			$isok = true;
			if ($isgrpadmin) {
				$query = "SELECT iq.id,iq.ownerid,iq.userights,iq.license,iq.otherattribution,imas_users.groupid FROM imas_questionset AS iq,imas_users ";
				$query .= "WHERE iq.id=:id AND iq.ownerid=imas_users.id AND (imas_users.groupid=:groupid OR iq.userights>2)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':id'=>$_GET['id'], ':groupid'=>$groupid));
				if ($stm->rowCount()==0) {
					$isok = false;
				} else {
					$row = $stm->fetch(PDO::FETCH_ASSOC);
					if ($row['userights'] > 3 && $row['groupid'] != $groupid) {
						// is a "allow mod by all" and not in group; cannot change userights or license or attrib
						$_POST['userights'] = $row['userights'];
						$_POST['license'] = $row['license'];
						$_POST['addattr'] = $row['otherattribution'];
					}
				}
				//$query = "UPDATE imas_questionset AS iq,imas_users SET iq.description='{$_POST['description']}',iq.author='{$_POST['author']}',iq.userights='{$_POST['userights']}',";
				//$query .= "iq.qtype='{$_POST['qtype']}',iq.control='{$_POST['control']}',iq.qcontrol='{$_POST['qcontrol']}',";
				//$query .= "iq.qtext='{$_POST['qtext']}',iq.answer='{$_POST['answer']}',iq.lastmoddate=$now ";
				//$query .= "WHERE iq.id='{$_GET['id']}' AND iq.ownerid=imas_users.id AND (imas_users.groupid='$groupid' OR iq.userights>2)";
			}
			if (!$isadmin && !$isgrpadmin) {  //check is owner or is allowed to modify
				$query = "SELECT iq.id,iq.ownerid,iq.userights,iq.license,iq.otherattribution,imas_users.groupid FROM imas_questionset AS iq,imas_users ";
				$query .= "WHERE iq.id=:id AND iq.ownerid=imas_users.id AND (iq.ownerid=:ownerid OR (iq.userights=3 AND imas_users.groupid=:groupid) OR iq.userights>3)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':id'=>$_GET['id'], ':ownerid'=>$userid, ':groupid'=>$groupid));
				if ($stm->rowCount()==0) {
					$isok = false;
				} else {
					$row = $stm->fetch(PDO::FETCH_ASSOC);
					if ($row['ownerid'] != $userid &&
						($row['userights'] != 3 || $row['groupid'] != $groupid)
					) {
						// cannot change userights or license or attrib
						$_POST['userights'] = $row['userights'];
						$_POST['license'] = $row['license'];
						$_POST['addattr'] = $row['otherattribution'];
					}
				}
			}
            if ($viewonly) { // view only: don't save edits
                $isok = false;
            }

			//checked separately above now
			//if (!$isadmin && !$isgrpadmin) { $query .= " AND (ownerid='$userid' OR userights>2);";}
			if ($isok && !isset($_POST['justupdatelibs'])) {
				$query = "UPDATE imas_questionset SET description=:description,author=:author,userights=:userights,license=:license,";
				$query .= "otherattribution=:otherattribution,qtype=:qtype,control=:control,qcontrol=:qcontrol,solution=:solution,isrand=:isrand,";
				$query .= "qtext=:qtext,answer=:answer,lastmoddate=:lastmoddate,extref=:extref,replaceby=:replaceby,solutionopts=:solutionopts,";
				$query .= "a11yalttype=:a11yalttype,a11yalt=:a11yalt";
				if (isset($_POST['undelete'])) {
					$query .= ',deleted=0';
				}
				$query .= " WHERE id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':description'=>$_POST['description'], ':author'=>$_POST['author'], ':userights'=>$_POST['userights'],
					':license'=>$_POST['license'], ':otherattribution'=>($_POST['addattr'] ?? ''), ':qtype'=>$_POST['qtype'], ':control'=>$_POST['control'],
					':qcontrol'=>$_POST['qcontrol'], ':solution'=>$_POST['solution'], ':isrand'=>$isrand, ':qtext'=>$_POST['qtext'], ':answer'=>$_POST['answer'],
					':lastmoddate'=>$now, ':extref'=>$extref, ':replaceby'=>$replaceby, ':solutionopts'=>$solutionopts, ':id'=>$_GET['id'],
					':a11yalttype'=>$a11yalttype, ':a11yalt'=>$a11yalt
				));

				if ($stm->rowCount()>0) {
					$outputmsg .= _("Question Updated.")." ";
				} else {
					$outputmsg .= _("Library Assignments Updated.")." ";
				}

				$stm = $DBH->prepare("SELECT id,filename,var,alttext FROM imas_qimages WHERE qsetid=:qsetid");
				$stm->execute(array(':qsetid'=>$_GET['id']));
				$imgcnt = $stm->rowCount();
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$_POST['imgvar-'.$row[0]] = preg_replace('/[^\w\[\]]/','', $_POST['imgvar-'.$row[0]]);
					if (isset($_POST['delimg-'.$row[0]])) {
						if (substr($row[1],0,4)!='http') {
							$stm2 = $DBH->prepare("SELECT id FROM imas_qimages WHERE filename=:filename");
							$stm2->execute(array(':filename'=>$row[1]));
							if ($stm2->rowCount()==1) {
								//unlink(rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/'.$row[1]);
								deleteqimage($row[1]);
							}
						}
						$stm2 = $DBH->prepare("DELETE FROM imas_qimages WHERE id=:id");
						$stm2->execute(array(':id'=>$row[0]));
						$imgcnt--;
						if ($imgcnt==0) {
							$stm2 = $DBH->prepare("UPDATE imas_questionset SET hasimg=0 WHERE id=:id");
							$stm2->execute(array(':id'=>$_GET['id']));
						}
					} else if ($row[2]!=$_POST['imgvar-'.$row[0]] || $row[3]!=$_POST['imgalt-'.$row[0]]) {
						$newvar = $_POST['imgvar-'.$row[0]];
						$newalt = $_POST['imgalt-'.$row[0]];
						$disallowedvar = array('link','qidx','qnidx','seed','qdata','toevalqtxt','la','laarr','shanspt','GLOBALS','laparts','anstype','kidx','iidx','tips','optionsPack','partla','partnum','score','disallowedvar','allowedmacros','wherecount','countcnt','myrights','myspecialrights');
						if (in_array($newvar,$disallowedvar)) {
							$errmsg .= "<p>".Sanitize::encodeStringForDisplay($newvar)." is not an allowed variable name</p>";
						} else {
							$stm2 = $DBH->prepare("UPDATE imas_qimages SET var=:var,alttext=:alttext WHERE id=:id");
							$stm2->execute(array(':var'=>$newvar, ':alttext'=>$newalt, ':id'=>$row[0]));
						}
					}
				}
				if ($replaceby!=0) {
					$query = 'UPDATE imas_questions JOIN imas_assessments ON imas_assessments.id=imas_questions.assessmentid ';
					$query .= 'LEFT JOIN imas_assessment_sessions ON imas_questions.assessmentid = imas_assessment_sessions.assessmentid ';
					$query .= 'SET imas_questions.questionsetid=:replaceby WHERE imas_assessments.ver=1 AND ';
					$query .= 'imas_assessment_sessions.id IS NULL AND imas_questions.questionsetid=:questionsetid';
					$stm = $DBH->prepare($query);
					$stm->execute(array(':replaceby'=>$replaceby, ':questionsetid'=>$qsetid));

					$query = 'UPDATE imas_questions JOIN imas_assessments ON imas_assessments.id=imas_questions.assessmentid ';
					$query .= 'LEFT JOIN imas_assessment_records ON imas_questions.assessmentid = imas_assessment_records.assessmentid ';
					$query .= 'SET imas_questions.questionsetid=:replaceby WHERE imas_assessments.ver>1 AND ';
					$query .= 'imas_assessment_records.userid IS NULL AND imas_questions.questionsetid=:questionsetid';
					$stm = $DBH->prepare($query);
					$stm->execute(array(':replaceby'=>$replaceby, ':questionsetid'=>$qsetid));

				}
			}

		} else { //adding new
			$mt = microtime();
			$uqid = substr($mt,11).substr($mt,2,6);
			$ancestors = ''; $ancestorauthors = '';
			if (isset($_GET['templateid'])) {
				$stm = $DBH->prepare("SELECT ancestors,author,ancestorauthors FROM imas_questionset WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['templateid']));
				list($ancestors,$lastauthor,$ancestorauthors) = $stm->fetch(PDO::FETCH_NUM);
				if ($ancestors!='') {
					$ancestors = intval($_GET['templateid']) . ','. $ancestors;
				} else {
					$ancestors = intval($_GET['templateid']);
				}
				if ($ancestorauthors!='') {
					$aaarr = explode('; ',$ancestorauthors);
					if (!in_array($lastauthor,$aaarr)) {
						$ancestorauthors = $lastauthor.'; '.$ancestorauthors;
					}
				} else if ($lastauthor != $_POST['author']) {
					$ancestorauthors = $lastauthor;
				}
			}
			$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,description,ownerid,author,userights,license,otherattribution,qtype,control,qcontrol,qtext,answer,hasimg,ancestors,ancestorauthors,extref,replaceby,solution,solutionopts,isrand,a11yalttype,a11yalt) VALUES ";
			$query .= "(:uniqueid, :adddate, :lastmoddate, :description, :ownerid, :author, :userights, :license, :otherattribution, :qtype, :control, :qcontrol, :qtext, :answer, :hasimg, :ancestors, :ancestorauthors, :extref, :replaceby, :solution, :solutionopts, :isrand, :a11yalttype, :a11yalt);";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':uniqueid'=>$uqid, ':adddate'=>$now, ':lastmoddate'=>$now, ':description'=>$_POST['description'], ':ownerid'=>$userid,
				':author'=>$_POST['author'], ':userights'=>$_POST['userights'], ':license'=>$_POST['license'], ':otherattribution'=>($_POST['addattr'] ?? ''),
				':qtype'=>$_POST['qtype'], ':control'=>$_POST['control'], ':qcontrol'=>$_POST['qcontrol'], ':qtext'=>$_POST['qtext'], ':answer'=>$_POST['answer'],
				':hasimg'=>$_POST['hasimg'], ':ancestors'=>$ancestors, ':ancestorauthors'=>$ancestorauthors, ':extref'=>$extref, ':replaceby'=>$replaceby,
				':solution'=>$_POST['solution'], ':solutionopts'=>$solutionopts, ':isrand'=>$isrand, ':a11yalttype'=>$a11yalttype, ':a11yalt'=>$a11yalt));
			$qsetid = $DBH->lastInsertId();
			$_GET['id'] = $qsetid;

			if (isset($_GET['templateid'])) {
				$stm = $DBH->prepare("SELECT var,filename,alttext,id FROM imas_qimages WHERE qsetid=:qsetid");
				$stm->execute(array(':qsetid'=>$_GET['templateid']));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					if (!isset($_POST['delimg-'.$row[3]])) {
						$_POST['imgvar-'.$row[3]] = preg_replace('/[^\w\[\]]/','', $_POST['imgvar-'.$row[3]]);
						if ($row[0]!=$_POST['imgvar-'.$row[3]] || $row[2]!=$_POST['imgalt-'.$row[3]]) {
							$newvar = $_POST['imgvar-'.$row[3]];
							$newalt = $_POST['imgalt-'.$row[3]];
							$disallowedvar = array('link','qidx','qnidx','seed','qdata','toevalqtxt','la','laarr','shanspt','GLOBALS','laparts','anstype','kidx','iidx','tips','optionsPack','partla','partnum','score','disallowedvar','allowedmacros','wherecount','countcnt','myrights','myspecialrights');
							if (!in_array($newvar,$disallowedvar)) {
								$row[0] = $newvar;
							}
							$row[2] = $newalt;
						}
						$stm2 = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES (:qsetid, :var, :filename, :alttext)");
						$stm2->execute(array(':qsetid'=>$qsetid, ':var'=>$row[0], ':filename'=>$row[1], ':alttext'=>$row[2]));
					}
				}
			}

			if (isset($_GET['makelocal'])) {
				$stm = $DBH->prepare("UPDATE imas_questions SET questionsetid=:questionsetid WHERE id=:id");
				$stm->execute(array(':questionsetid'=>$qsetid, ':id'=>$_GET['makelocal']));
				$outputmsg .= " "._("Local copy of Question Created")." ";
				$frompot = 0;
			} else {
				$outputmsg .= " "._("Question Added to QuestionSet.")." ";
				$frompot = 1;
			}
			$isok = true;
		}

		if ($isok) {
			//upload image files if attached
			if ($_FILES['imgfile']['name']!='') {
				$disallowedvar = array('link','qidx','qnidx','seed','qdata','toevalqtxt','la','GLOBALS','laparts','anstype','kidx','iidx','tips','options','partla','partnum','score');
				$_POST['newimgvar'] = preg_replace('/[^\w\[\]]/','', $_POST['newimgvar']);
				if (!is_uploaded_file($_FILES['imgfile']['tmp_name'])) {
					switch($_FILES['imgfile']['error']){
					case 1:
					case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
					  $errmsg .= _("The file you are trying to upload is too big.");
					  break;
					case 3: //uploaded file was only partially uploaded
					  $errmsg .= _("The file you are trying upload was only partially uploaded.");
					  break;
					default: //a default error, just in case!  :)
					  $errmsg .= _("There was a problem with your upload.");
					  break;
						}
				} else if (trim($_POST['newimgvar'])=='') {
					$errmsg .= "<p>"._("Need to specify variable for image to be referenced by")."</p>";
				} else if (in_array($_POST['newimgvar'],$disallowedvar)) {
					$errmsg .= "<p>".sprintf(_("%s is not an allowed variable name"),Sanitize::encodeStringForDisplay($newvar))."</p>";
				} else {
					$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/';
					//$filename = basename($_FILES['imgfile']['name']);
					$userfilename = preg_replace('/[^\w\.]/','',basename(str_replace('\\','/',$_FILES['imgfile']['name'])));
					$filename = $userfilename;

					//$uploadfile = $uploaddir . $filename;
					//$t=0;
					//while(file_exists($uploadfile)){
					//	$filename = substr($filename,0,strpos($userfilename,"."))."_$t".strstr($userfilename,".");
					//	$uploadfile=$uploaddir.$filename;
					//	$t++;
					//}
					$result_array = getimagesize($_FILES['imgfile']['tmp_name']);
					$extension = pathinfo($_FILES['imgfile']['name'], PATHINFO_EXTENSION);
					$mimetype = mime_content_type($_FILES['imgfile']['tmp_name']);
					$is_alt_img = (in_array($mimetype, ['image/svg+xml']) &&
						in_array($extension, ['svg']));
					if ($result_array === false && !$is_alt_img) {
						$errmsg .= "<p>"._("File is not image file")."</p>";
					} else {
						if (($filename=storeuploadedqimage('imgfile',$filename))!==false) {
						//if (move_uploaded_file($_FILES['imgfile']['tmp_name'], $uploadfile)) {
							//echo "<p>File is valid, and was successfully uploaded</p>\n";
							$stm = $DBH->prepare("INSERT INTO imas_qimages (var,qsetid,filename,alttext) VALUES (:var, :qsetid, :filename, :alttext)");
							$stm->execute(array(':var'=>$_POST['newimgvar'], ':qsetid'=>$qsetid, ':filename'=>$filename, ':alttext'=>$_POST['newimgalt']));
							$stm = $DBH->prepare("UPDATE imas_questionset SET hasimg=1 WHERE id=:id");
							$stm->execute(array(':id'=>$qsetid));
						} else {
							echo "<p>"._("Error uploading image file!")."</p>\n";
							exit;
						}
					}
				}
			}
		}

		//update libraries
		$newlibs = explode(",",$_POST['libs']);

		if (in_array('0',$newlibs)) { //we'll handle unassigned as a special case
			array_shift($newlibs);
		}

		if ($_POST['libs']=='') {
			$newlibs = array();
		}

		$allcurrentlibs = array();
		$alldeletedlibs = array();
		//$query = "SELECT ili.libid,ili.deleted FROM imas_library_items AS ili JOIN imas_libraries AS il ON ";
		//$query .= "ili.libid=il.id OR ili.libid=0 WHERE ili.qsetid=:qsetid";
		$query = "SELECT libid,deleted FROM imas_library_items WHERE qsetid=:qsetid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':qsetid'=>$qsetid));
		while($row = $stm->fetch(PDO::FETCH_NUM)) {
			if ($row[1]==0) {
				$allcurrentlibs[] = $row[0];
			} else {
				$alldeletedlibs[] = $row[0];
			}
		}
		if ($isadmin) {
			$haverightslibs = $allcurrentlibs;
		} else {
			if ($isgrpadmin) {
				$query = "SELECT ili.libid FROM imas_library_items AS ili,imas_users WHERE ili.ownerid=imas_users.id ";
				$query .= "AND (imas_users.groupid=:groupid OR ili.libid=0) AND ili.deleted=0 AND ili.qsetid=:qsetid";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':groupid'=>$groupid, ':qsetid'=>$qsetid));
			} else {
				//unassigned, or owner and lib not closed or mine
				$query = "SELECT DISTINCT ili.libid FROM imas_library_items AS ili LEFT JOIN imas_libraries AS il ON ";
				$query .= "ili.libid=il.id AND il.deleted=0 WHERE ili.qsetid=:qsetid AND ili.deleted=0 AND ";
				$query .= "(ili.libid=0 OR (ili.ownerid=:ownerid AND (il.ownerid=:ownerid2 OR il.userights%3<>1)))";

				//$query = "SELECT ili.libid FROM imas_library_items AS ili JOIN imas_libraries AS il ON ";
				//$query .= "(ili.libid=il.id OR ili.libid=0) AND il.deleted=0 WHERE ili.qsetid=:qsetid AND ili.deleted=0 ";
				//$query .= " AND ((ili.ownerid=:ownerid AND (il.ownerid=:ownerid2 OR il.userights%3<>1)) OR ili.libid=0)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':qsetid'=>$qsetid, ':ownerid'=>$userid, ':ownerid2'=>$userid));
			}
			$haverightslibs = array();
			while($row = $stm->fetch(PDO::FETCH_NUM)) {
				$haverightslibs[] = $row[0];
			}
		}

		//remove any that we have the rights to but are not in newlibs
		$toremove = array_values(array_diff($haverightslibs,$newlibs));
		//undelete any libs that are new and in deleted libs
		$toundelete = array_values(array_intersect($newlibs,$alldeletedlibs));
		//add any new librarys that are not current and aren't being undeleted
		$toadd = array_values(array_diff($newlibs,$allcurrentlibs,$toundelete));

		//no selected libs, we're removing all current libs (or none of either)
		// nothing to undelete, nothing to add.
		// Create unassigned
		if (count($newlibs)==0 && count($toremove)==count($allcurrentlibs) && count($toundelete)==0 && count($toadd)==0) {
			if (in_array(0, $alldeletedlibs)) {  //have unassigned to undelete
				$toundelete[] = 0;
			} else if (count($toremove)==1 && $toremove[0]==0) { //already have an unassigned - don't delete it
				array_shift($toremove);
			} else { //create new unassigned
				$toadd[] = 0;
			}
		}

		$now = time();
		if (count($toundelete)>0) {
			foreach($toundelete as $libid) {
				$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=0,lastmoddate=:now,ownerid=:ownerid WHERE qsetid=:qsetid AND libid=:libid");
				$stm->execute(array(':libid'=>$libid, ':qsetid'=>$qsetid, ':now'=>$now, ':ownerid'=>$userid));
			}
		}
		if (count($toadd)>0) {
			foreach($toadd as $libid) {
				$stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,lastmoddate) VALUES (:libid, :qsetid, :ownerid, :now)");
				$stm->execute(array(':libid'=>$libid, ':qsetid'=>$qsetid, ':ownerid'=>$userid, ':now'=>$now));
			}
		}
		if (count($toremove)>0) {
			foreach($toremove as $libid) {
				$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=:now WHERE libid=:libid AND qsetid=:qsetid");
				$stm->execute(array(':libid'=>$libid, ':qsetid'=>$qsetid, ':now'=>$now));
			}
		}

		if (isset($_GET['daid'])) {
			$outputmsg .=  "<a href=\"adddrillassess.php?cid=$cid&daid=".Sanitize::onlyInt($_GET['daid'])."\">"._("Return to Drill Assessment")."</a>\n";
		} else if (!isset($_GET['aid'])) {
			$outputmsg .= "<a href=\"manageqset.php?cid=$cid\">"._("Return to Question Set Management")."</a>\n";
		} else {
			if ($frompot==1) {
				$modquestion = (!empty($courseUIver) && $courseUIver > 1) ? 'modquestion2.php' : 'modquestion.php';
				$outputmsg .=  "<a href=\"$modquestion?qsetid=$qsetid&cid=$cid&aid=".Sanitize::onlyInt($_GET['aid'])."&from=$from&process=true&usedef=true\">"._("Add Question to Assessment using Defaults")."</a> | \n";
				$outputmsg .=  "<a href=\"$modquestion?qsetid=$qsetid&cid=$cid&aid=".Sanitize::onlyInt($_GET['aid'])."&from=$from\">"._("Add Question to Assessment")."</a> | \n";
			}
			$outputmsg .=  "<a href=\"$addq.php?cid=$cid&aid=".Sanitize::onlyInt($_GET['aid'])."\">"._("Return to Assessment")."</a>\n";
		}
		if (!empty($_POST['test']) && $_POST['test']=="Save and Test Question") {
			$outputmsg .= "<script>addr = '$imasroot/course/$testqpage?cid=$cid&qsetid=".Sanitize::encodeUrlParam($_GET['id'])."';";
			//echo "function previewit() {";
			$outputmsg .= "previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));\n";
			$outputmsg .= "previewpop.focus();";
			$outputmsg .= "</script>";
			//echo "}";
			//echo "window.onload = previewit;";
		} else if ($quicksave){
			// Don't echo or die if in quicksave mode.
		} else {
			if ($errmsg == '' && !empty($_GET['daid'])) {
				header('Location: ' . $GLOBALS['basesiteurl'] . '/course/adddrillassess.php?cid='.$cid.'&daid='.Sanitize::onlyInt($_GET['daid']).'&r='.Sanitize::randomQueryStringParam());
			} else if ($errmsg == '' && !isset($_GET['aid'])) {
				header('Location: ' . $GLOBALS['basesiteurl'] . '/course/manageqset.php?cid='.$cid.'&r='.Sanitize::randomQueryStringParam());
			} else if ($errmsg == '' && $frompot==0) {
				header('Location: ' . $GLOBALS['basesiteurl'] . '/course/'.$addq.'.php?cid='.$cid.'&aid='.Sanitize::onlyInt($_GET['aid']).'&r='.Sanitize::randomQueryStringParam());
			} else {
				require_once "../header.php";
				echo $errmsg;
				echo $outputmsg;
				require_once "../footer.php";
			}
			exit;
		}
	}
	$stm = $DBH->prepare("SELECT firstName,lastName FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>$userid));
	$row = $stm->fetch(PDO::FETCH_NUM);
	$myname = $row[1].','.$row[0];
	if (isset($_GET['id'])) {
			$query = "SELECT imas_questionset.*,imas_users.groupid FROM imas_questionset,imas_users WHERE ";
			$query .= "imas_questionset.ownerid=imas_users.id AND imas_questionset.id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':id'=>$_GET['id']));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
            if ($line === false) {
                echo _('Invalid question ID');
                exit;
            }

			$myq = ($line['ownerid']==$userid);
			if ($isadmin || ($isgrpadmin && $line['groupid']==$groupid) || ($line['userights']==3 && $line['groupid']==$groupid) || $line['userights']>3) {
				$myq = true;
			}
			$namelist = explode(", mb ",$line['author']);
			if ($myq && !in_array($myname,$namelist)) {
				$namelist[] = $myname;
			}
			if (isset($_GET['template'])) {
				$author = $myname;
				$myq = true;
			} else {
				$author = implode(", mb ",$namelist);
			}
			/* No longer needed with encodeStringForDisplay
			foreach ($line as $k=>$v) {
				$line[$k] = str_replace('&','&amp;',$v);
			}*/

			$inlibs = array();
			if($line['extref']!='') {
				$extref = explode('~~',$line['extref']);
			} else {
				$extref = array();
			}
			$images = array();
			$images['vars'] = array();
			$images['files'] = array();
			$images['alttext'] = array();
			if ($line['hasimg']>0) {
				$stm = $DBH->prepare("SELECT id,var,filename,alttext FROM imas_qimages WHERE qsetid=:qsetid");
				$stm->execute(array(':qsetid'=>$_GET['id']));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$images['vars'][$row[0]] = $row[1];
					$images['files'][$row[0]] = $row[2];
					$images['alttext'][$row[0]] = $row[3];
				}
			}
			if (isset($_GET['template'])) {
				$stm = $DBH->prepare("SELECT deflib,usedeflib FROM imas_users WHERE id=:id");
				$stm->execute(array(':id'=>$userid));
				list($deflib,$usedeflib) = $stm->fetch(PDO::FETCH_NUM);

				if (isset($_GET['makelocal'])) {
					$inlibs[] = $deflib;
					$line['description'] .= " (local for $userfullname)";
				} else {
					$line['description'] .= " (copy by $userfullname)";
					if ($usedeflib==1) {
						$inlibs[] = $deflib;
					} else {
						$query = "SELECT imas_libraries.id,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.groupid ";
						$query .= "FROM imas_libraries,imas_library_items WHERE imas_library_items.libid=imas_libraries.id ";
						$query .= "AND imas_library_items.qsetid=:qsetid AND imas_library_items.deleted=0";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':qsetid'=>$_GET['id']));
						while ($row = $stm->fetch(PDO::FETCH_NUM)) {
							if ($row[2] == 8 || ($row[3]==$groupid && ($row[2]%3==2)) || $row[1]==$userid) {
								$inlibs[] = $row[0];
							}
						}
					}
				}
				/*$query = "SELECT imas_library_items.libid FROM imas_library_items,imas_libraries WHERE ";
				$query .= "imas_library_items.libid=imas_libraries.id AND (imas_libraries.ownerid=$userid OR imas_libraries.userights=2) ";
				$query .= "AND qsetid='{$_GET['id']}'";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$inlibs[] = $row[0];
				}*/
				$locklibs = array();
				$addmod = _("Add");
				$stm = $DBH->prepare("SELECT qrightsdef FROM imas_users WHERE id=:id");
                $stm->execute(array(':id'=>$userid));
                $qrightsdef = $stm->fetchColumn(0);
				$line['userights'] = $CFG['GEN']['newQuestionUseRights'] ?? 0;

			} else {
				if ($isadmin) {
					$stm = $DBH->prepare("SELECT DISTINCT libid FROM imas_library_items WHERE qsetid=:qsetid AND imas_library_items.deleted=0");
					$stm->execute(array(':qsetid'=>$_GET['id']));
				} else if ($isgrpadmin) {
					$query = "SELECT DISTINCT ili.libid FROM imas_library_items AS ili,imas_users WHERE ili.ownerid=imas_users.id ";
					$query .= "AND imas_users.groupid=:groupid AND ili.qsetid=:qsetid AND ili.deleted=0";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':groupid'=>$groupid, ':qsetid'=>$_GET['id']));
				} else {
					$stm = $DBH->prepare("SELECT DISTINCT libid FROM imas_library_items WHERE qsetid=:qsetid AND ownerid=:ownerid AND deleted=0");
					$stm->execute(array(':qsetid'=>$_GET['id'], ':ownerid'=>$userid));
				}
				//$query = "SELECT libid FROM imas_library_items WHERE qsetid='{$_GET['id']}' AND imas_library_items.ownerid='$userid'";
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$inlibs[] = $row[0];
				}

				$locklibs = array();
				if (!$isadmin) {
					if ($isgrpadmin) {
						$query = "SELECT ili.libid FROM imas_library_items AS ili,imas_users WHERE ili.ownerid=imas_users.id ";
						$query .= "AND imas_users.groupid!=:groupid AND ili.qsetid=:qsetid AND ili.deleted=0";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':qsetid'=>$_GET['id'], ':groupid'=>$groupid));
					} else if (!$isadmin) {
						$stm = $DBH->prepare("SELECT libid FROM imas_library_items WHERE qsetid=:qsetid AND imas_library_items.ownerid!=:userid AND deleted=0");
						$stm->execute(array(':qsetid'=>$_GET['id'], ':userid'=>$userid));
					}
					//$query = "SELECT libid FROM imas_library_items WHERE qsetid='{$_GET['id']}' AND imas_library_items.ownerid!='$userid'";
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$locklibs[] = $row[0];
					}
				}
				$addmod = _("Modify");
				//$query = "SELECT count(imas_questions.id) FROM imas_questions,imas_assessments,imas_courses WHERE imas_assessments.id=imas_questions.assessmentid ";
				//$query .= "AND imas_assessments.courseid=imas_courses.id AND imas_questions.questionsetid=:questionsetid AND imas_courses.ownerid<>:userid";
				$query = "SELECT imas_questions.id FROM imas_questions,imas_assessments,imas_courses WHERE imas_assessments.id=imas_questions.assessmentid ";
				$query .= "AND imas_assessments.courseid=imas_courses.id AND imas_questions.questionsetid=:questionsetid AND imas_courses.ownerid<>:userid LIMIT 1";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':questionsetid'=>$_GET['id'], ':userid'=>$userid));
				$inusecnt = $stm->fetchColumn(0);
			}

			if (count($inlibs)==0 && count($locklibs)==0) {
				$inlibs = array(0);
			}
			$inlibs = implode(",",$inlibs);
			$locklibs = implode(",",$locklibs);

			if (trim($line['qcontrol'])!='') {
				$line['control'] .= "\n\n".$line['qcontrol'];
				$line['qcontrol'] = '';
			}
			if (trim($line['answer'])!='') {
				$line['control'] .= "\n\n".$line['answer'];
				$line['answer'] = '';
			}

			$line['qtext'] = preg_replace('/<span class="AM">(.*?)<\/span>/','$1',$line['qtext']);
	} else {
			$myq = true;
            $line = array();
			$line['description'] = _("Enter description here");
			$stm = $DBH->prepare("SELECT qrightsdef FROM imas_users WHERE id=:id");
            $stm->execute(array(':id'=>$userid));
            $qrightsdef = $stm->fetchColumn(0);
			$line['userights'] = $CFG['GEN']['newQuestionUseRights'] ?? 0;
            $line['author'] = '';
			$line['license'] = isset($CFG['GEN']['deflicense'])?$CFG['GEN']['deflicense']:1;
            $line['otherattribution'] = '';
			$line['qtype'] = "number";
			$line['control'] = '';
			$line['qcontrol'] = '';
			$line['qtext'] = '';
			$line['answer'] = '';
			$line['solution'] = '';
			$line['solutionopts'] = 6;
			$line['hasimg'] = 0;
			$line['deleted'] = 0;
			$line['replaceby'] = 0;
			$line['a11yalttype'] = 0;
			$line['a11yalt'] = '';
			if (isset($_GET['aid']) && isset($_SESSION['lastsearchlibs'.$_GET['aid']])) {
				$inlibs = $_SESSION['lastsearchlibs'.Sanitize::onlyInt($_GET['aid'])];
			} else if (isset($_SESSION['lastsearchlibs'.$cid])) {
				//$searchlibs = explode(",",$_SESSION['lastsearchlibs']);
				$inlibs = $_SESSION['lastsearchlibs'.$cid];
			} else {
				$inlibs = $userdeflib;
			}
			$locklibs='';
			$images = array();
			$extref = array();
			$author = $myname;
			$inlibssafe = implode(',', array_map('intval', explode(',',$inlibs)));
			if (!isset($_GET['id']) || isset($_GET['template'])) {
				$oklibs = array();
				$stm = $DBH->query("SELECT id,ownerid,userights,groupid FROM imas_libraries WHERE id IN ($inlibssafe)");
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					if ($row[2] == 8 || ($row[3]==$groupid && ($row[2]%3==2)) || $row[1]==$userid) {
						$oklibs[] = $row[0];
					}
				}
				if (count($oklibs)>0) {
					$inlibs = implode(",",$oklibs);
				} else {$inlibs = '0';}
			}

			$addmod = _("Add");
	}
    $canedit = $myq;
    if ($viewonly) {
        $canedit = false;
    }
	if (!$canedit) {
		$addmod = _('View');
	}

	$inlibssafe = implode(',', array_map('intval', explode(',',$inlibs)));

	$lnames = array();
	if (substr($inlibs,0,1)==='0') {
		$lnames[] = "Unassigned";
	}
	$stm = $DBH->query("SELECT name FROM imas_libraries WHERE id IN ($inlibssafe)");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$lnames[] = $row[0];
	}
	$lnames = implode(", ",$lnames);

    $olnames = '';
    if ($locklibs != '') {
        $locklibssafe = implode(',', array_map('intval', explode(',',$locklibs)));
        $olnames = [];
        $stm = $DBH->query("SELECT name FROM imas_libraries WHERE id IN ($locklibssafe)");
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $olnames[] = $row[0];
        }
        $olnames = implode(", ",$olnames);
    }

	// Build form action
	$formAction = "moddataset.php?process=true"
		. (isset($_GET['cid']) ? "&cid=$cid" : "")
		. (isset($_GET['daid']) ? "&daid=".Sanitize::encodeUrlParam($_GET['daid']) : "")
		. (isset($_GET['aid']) ? "&aid=".Sanitize::encodeUrlParam($_GET['aid'])."&from=$from" : "")
		. ((isset($_GET['id']) && !isset($_GET['template'])) ? "&id=".Sanitize::encodeUrlParam($_GET['id']) : "")
		. (isset($_GET['template']) ? "&templateid=".Sanitize::encodeUrlParam($_GET['id']) : "")
		. (isset($_GET['makelocal']) ? "&makelocal=".Sanitize::encodeUrlParam($_GET['makelocal']) : "")
        . ($frompot==1 ? "&frompot=1" : "");

	// If in quick-save mode, build return packet and exit here
	if ($quicksave) {
		// Build return packet
		$qsPacket = array();
		$qsPacket['formAction'] = $formAction; // Form action
		$qsPacket['images'] = $images; //  Images array
		$qsPacket['outputmsg'] = $outputmsg; // output message
		$qsPacket['errmsg'] = $errmsg;
		$extrefqs = array();
		for ($i=0;$i<count($extref);$i++) {
			$extrefpt = explode('!!',$extref[$i]);
			$type = ucfirst($extrefpt[0]);
			if ($extrefpt[0]=='video' && count($extrefpt)>2 && $extrefpt[2]==1) {
				$type .= ' (cc)';
			}
            $extrefqs[$i] = array($type,$extrefpt[1]);
            if (!empty($extrefpt[3])) {
                $extrefqs[$i][2] = Sanitize::encodeStringForDisplay($extrefpt[3]);
            }
		}
		$qsPacket['extref'] = $extrefqs;
		$qsPacket['id'] = isset($_GET['id']) ? $_GET['id'] : 0;
		// Build img base url
		if (isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true){
			$qsPacket['imgUrlBase'] = $urlmode."{$GLOBALS['AWSbucket']}.s3.amazonaws.com/qimages/";
		} else {
			$qsPacket['imgUrlBase'] = "$imasroot/assessment/qimages/";
		}
		// Return the packet
		exit(json_encode($qsPacket, JSON_INVALID_UTF8_IGNORE));
	}

	/// Start display ///
	$pagetitle = _("Question Editor");
	$placeinhead = '';
	/*if ($_SESSION['mathdisp']==1 || $_SESSION['mathdisp']==2 || $_SESSION['mathdisp']==3) {
		//these scripts are used by the editor to make image-based math work in the editor
		$placeinhead .= '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
		//if ($mathdarkbg) {$placeinhead .=  'var mathbg = "dark";';}
		$placeinhead .= '</script>';
		$placeinhead .= "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=082911\" type=\"text/javascript\"></script>\n";
		$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/tinymce4/tinymce.min.js?v=111612"></script>';
	}
	*/
	$useeditor = "noinit";
	$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/codemirror/codemirror-compressed.js?v=091522"></script>';
	$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/codemirror/imathas.js?v=012925"></script>';
	$placeinhead .= '<link rel="stylesheet" href="'.$staticroot.'/javascript/codemirror/codemirror_min.css?v=091522">';

	//$placeinhead .= '<script src="//sagecell.sagemath.org/embedded_sagecell.js"></script>'.PHP_EOL;
	$placeinhead .= '<script type="text/javascript">
	  var editoron = 0; var seditoron = 0;
	  var coursetheme = "'.$coursetheme.'";';
	if (!isset($CFG['GEN']['noFileBrowser'])) {
		$placeinhead .= 'var filePickerCallBackFunc = filePickerCallBack;';
	} else {
		$placeinhead .= 'var filePickerCallBackFunc = null;';
	}

	if (isset($_GET['id'])) {
		$placeinhead .= 'var originallicense = '.$line['license'].';';
	} else {
		$placeinhead .= 'var originallicense = -1;';
	}

	$placeinhead .= '
	   var controlEditor;
	   var qEditor = [];

	  function toggleeditor(el) {
	     var qtextbox =  document.getElementById(el);
	     if ((el=="qtext" && editoron==0) || (el=="solution" && seditoron==0)) {
	        if (typeof qEditor[el] != "undefined") {
	     		qEditor[el].toTextArea();
	     	}
	        qtextbox.rows += 3;
	        qtextbox.value = qtextbox.value.replace(/<span\s+class="AM"[^>]*>(.*?)<\\/span>/g,"$1");
	        qtextbox.value = qtextbox.value.replace(/`(.*?)`/g,\'<span class="AM" title="$1">`$1`</span>\');
	        qtextbox.value = qtextbox.value.replace(/\n\n/g,"<br/><br/>");

	        var toinit = [];
	        if ((el=="qtext" && editoron==0) || (el!="qtext" && editoron==1)) {
	        	toinit.push("qtext");
	        }
	        if ((el=="solution" && seditoron==0) || (el!="solution" && seditoron==1)) {
	        	toinit.push("solution");
	        }
	        initeditor("exact",toinit.join(","),1);
	     } else {
	     	tinymce.remove("#"+el);
	     	qtextbox.rows -= 3;
	     	qtextbox.value = qtextbox.value.replace(/<span\s+class="AM"[^>]*>(.*?)<\\/span>/g,"$1");
	     	setupQtextEditor(el);
	     }
	     if (el.match(/qtext/)) {
	     	editoron = 1 - editoron;
	     	//document.cookie = "qeditoron="+editoron;
	     } else if (el.match(/solution/)) {
	     	seditoron = 1 - seditoron;
	     	//document.cookie = "seditoron="+seditoron;
	     }
	   }
	   function initsolneditor() {
	   	/*
	   	if (document.cookie.match(/seditoron=1/)) {
	   		var val = document.getElementById("solution").value;
	   		if (val.length<3 || val.match(/<.*?>/)) {toggleeditor("solution");}
	   		else {setupQtextEditor("solution");}
	   	}else {setupQtextEditor("solution");}
	   	*/
	   }

	   addLoadEvent(function(){setupQtextEditor("qtext");setupQtextEditor("solution");});
	   /*
	   if (document.cookie.match(/qeditoron=1/)) {
	   	var val = document.getElementById("qtext").value;
	   	if (val.length<3 || val.match(/<.*?>/)) {toggleeditor("qtext");}
	   	else {setupQtextEditor("qtext");}
	   }else {setupQtextEditor("qtext");}});
	   */

	   function setupQtextEditor(id) {
	   	var qtextbox = document.getElementById(id);
	   	if (!qtextbox) { return; }
		qtextbox.value = qtextbox.value.replace(/\s*((<br\s*\/>\s*){1,}<br\s*\/>)\s*/g, "\n$1\n");
	   	qEditor[id] = CodeMirror.fromTextArea(qtextbox, {
			matchTags: true,
			mode: "imathasqtext",
			smartIndent: true,
			lineWrapping: true,
			indentUnit: 2,
			tabSize: 2,
            viewportMargin: 500,
			'.(!$canedit?'readOnly:true,':'').'
			styleSelectedText:true
		  });
		  for (var i=0;i<qEditor[id].lineCount();i++) { qEditor[id].indentLine(i); }
	   }

	   $(function() {
	   	controlEditor = CodeMirror.fromTextArea(document.getElementById("control"), {
			lineNumbers: true,
			matchBrackets: true,
			autoCloseBrackets: true,
			mode: "text/x-imathas",
			smartIndent: true,
			lineWrapping: true,
			indentUnit: 2,
			tabSize: 2,
            viewportMargin: 500,
			'.(!$canedit?'readOnly:true,':'').'
			styleSelectedText:true
		      });
		//controlEditor.setSize("100%",6+14*document.getElementById("control").rows);
	   });


	   function checklicense() {
	   	var lic = $("#license").val();
	   	var warn = "";
	   	if (originallicense>-1) {
	   		if (originallicense==0 && lic != 0) {
	   			warn = "'._('If the original question contained copyrighted material, you should not change the license unless you have removed all the copyrighted material').'";
	   		} else if ((originallicense == 1 ||  originallicense == 3 ||  originallicense == 4) && lic != originallicense) {
	   			warn = "'._('The original license REQUIRES that all derivative versions be kept under the same license. You should only be changing the license if you are the creator of this questions and all questions it was derived from').'";
	   		}
	   	}
	   	$("#licensewarn").html("<br/>"+warn);
	   }
	   function changea11ytype() {
	     let val = document.getElementById("a11yalttype").value;
		 if (val > 0) {
		 	$("#a11yaltwarn").show();
		 } else {
			$("#a11yaltwarn").hide();
		 }
       }
	   function incctrlboxsize() {
	   	$("#ccbox").find(".CodeMirror-scroll").css("min-height",0).css("max-height","none");
	   	controlEditor.setSize("100%",$(controlEditor.getWrapperElement()).height()+28);
	   }
	   function decctrlboxsize() {
	  	 $("#ccbox").find(".CodeMirror-scroll").css("min-height",0).css("max-height","none");
	   	controlEditor.setSize("100%",$(controlEditor.getWrapperElement()).height()-28);
	   }
	   function incqtboxsize(id) {
	   	if (!editoron) {
	   		$("#"+id).parent().find(".CodeMirror-scroll").css("min-height",0).css("max-height","none");
	   		qEditor[id].setSize("100%",$(qEditor[id].getWrapperElement()).height()+28);
	   		document.getElementById(id).rows += 2;
	   	}
	   }
	   function decqtboxsize(id) {
	   	if (!editoron) {
	   		$("#"+id).parent().find(".CodeMirror-scroll").css("min-height",0).css("max-height","none");
	   		qEditor[id].setSize("100%",$(qEditor[id].getWrapperElement()).height()-28);
	   		document.getElementById(id).rows -= 2;
	   	}
	   }
        $(function() {
          $("#qtypedd a[data-sn]").on("click", function(e) {
            $("#qtypedd dd-active").removeClass("dd-active");
            selectqtype(this.getAttribute("data-sn"));
            $("#qtype").val(this.getAttribute("data-sn"));
          });
        });
        function selectqtype(sn) {
            // close all second-levels
            $(".dropdown-submenu").removeClass("open");
            $("#qtypedd a").removeClass("dd-active").attr("aria-expanded",false);
            var selel = $("#qtypedd a[data-sn=" + sn + "]");
            selel.addClass("dd-active"); 
            selel.closest(".dropdown-submenu").addClass("open").children("a").addClass("dd-active").attr("aria-expanded",true);
            var longname = selel.text();
            if (selel.attr("data-ln")) {
                longname = selel.attr("data-ln");
            }
            $("#qtypedd > button.dropdown-toggle").html(longname + " <span class=\'arrow-down\'></span>");
        }
        $(function() { selectqtype($("#qtype").val()); });
	   </script>';
	$placeinhead .= "<script src=\"$staticroot/javascript/solver.js?ver=110621\" type=\"text/javascript\"></script>\n";
	$placeinhead .= '<style type="text/css">.CodeMirror {font-size: medium;border: 1px solid #ccc;}
		#ccbox .CodeMirror, #qtbox .CodeMirror {height: auto; clip-path: inset(0px);}
		#ccbox .CodeMirror-scroll {min-height:220px; max-height:600px;}
		#qtbox .CodeMirror-scroll {min-height:150px; max-height:600px;}
		.CodeMirror-selectedtext {color: #ffffff !important;background-color: #3366AA;}
		.CodeMirror-focused .CodeMirror-selected {background: #3366AA;}
		.CodeMirror-selected {background: #666666;}
        .dd-active {background-color: #eef;}
		</style>';
	$placeinhead .= "<link href=\"$staticroot/course/solver.css?ver=230616\" rel=\"stylesheet\">";
	$placeinhead .= "<style>.quickSaveButton {display:none;}</style>";

	require_once "../header.php";


	if (isset($_GET['aid'])) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"$addq.php?aid=".Sanitize::onlyInt($_GET['aid'])."&cid=$cid\">"._("Add/Remove Questions")."</a> &gt; "._("Modify Questions")."</div>";

	} else if (isset($_GET['daid'])) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"adddrillassess.php?daid=".Sanitize::encodeUrlParam($_GET['daid'])."&cid=$cid\">"._("Add Drill Assessment")."</a> &gt; "._("Modify Questions")."</div>";
	} else {
		if ($cid==="admin") {
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../admin/admin2.php\">Admin</a> ";
			echo "&gt; <a href=\"manageqset.php?cid=admin\">"._("Manage Question Set")."</a> &gt; "._("Modify Question")."</div>\n";
		} else {
			echo "<div class=breadcrumb><a href=\"../index.php\">"._("Home")."</a> ";
			if ($cid>0) {
				echo "&gt; <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a>";
			}
			echo " &gt; <a href=\"manageqset.php?cid=$cid\">"._("Manage Question Set")."</a> &gt; "._("Modify Question")."</div>\n";
		}

	}
	echo "<div id='errmsgContainer'>$errmsg</div>";
	echo "<div id='outputmsgContainer'>$outputmsg</div>";

	echo '<div id="headermoddataset" class="pagetitle">';
	echo "<h1>" . Sanitize::encodeStringForDisplay($addmod) . ": "._("QuestionSet Question"),"</h1>\n";
	echo '</div>';

	if (strpos($line['control'],'end stored values - Tutorial Style')!==false) {
		echo '<p>'._('This question appears to be a Tutorial Style question.').'  <a href="modtutorialq.php?'.Sanitize::encodeStringForDisplay($_SERVER['QUERY_STRING']).'">'._('Open in the tutorial question editor').'</a></p>';
	}

	if ($line['deleted']==1) {
		echo '<p class=noticetext>'._('This question has been marked for deletion.  This might indicate there is an error in the question. It is recommended you discontinue use of this question when possible').'</p>';
	}

	if (isset($inusecnt) && $inusecnt>0 && $canedit) {
		/*
		echo '<p class=noticetext>'._('This question is currently being used in ');
		if ($inusecnt>1) {
			echo Sanitize::onlyInt($inusecnt)._(' assessments that are not yours.  ');
		} else {
			echo _('one assessment that is not yours.  ');
		}
		*/
		echo '<p class=noticetext>'._('This question is currently being used in at least one assessment that is not yours. ');
		echo _('In consideration of the other users, if you want to make changes other than minor fixes to this question, consider creating a new version of this question instead.').'  </p>';
	}
	if (isset($_GET['qid'])) {
		echo "<p>".sprintf(_("%sTemplate this question%s for use in this assessment.  "),"<a href=\"moddataset.php?id=" . Sanitize::onlyInt($_GET['id']) . "&cid=$cid&aid=".Sanitize::onlyInt($_GET['aid'])."&template=true&makelocal=" . Sanitize::onlyInt($_GET['qid']) . "\">","</a>");
		echo _("This will let you modify the question for this assessment only without affecting the library version being used in other assessments.")."</p>";
	}
    if (!$myq && !$canedit) {
		echo "<p class=noticetext>"._("This question is not set to allow you to modify the code.  You can only view the code and make additional library assignments")."</p>";
	} else if ($viewonly) {
		echo "<p class=noticetext>"._("This view will not allow you to modify the code.  You can only view the code and make additional library assignments")."</p>";
    } 
?>
<form enctype="multipart/form-data" method=post action="<?php echo $formAction; // Sanitized near line 806 ?>">
<?php
if ($viewonly) {
    echo '<input type=hidden name=viewonly value=1 />';
}
?>
<input type="hidden" name="hasimg" value="<?php echo Sanitize::encodeStringForDisplay($line['hasimg']);?>"/>
<p>
<label for=description><?php echo _('Description');?></label>:<br/>
<textarea cols=60 rows=4 name=description id=description <?php if (!$canedit) echo "disabled";?>><?php echo Sanitize::encodeStringForDisplay($line['description'], true);?></textarea>
</p>
<p>
<?php echo _('Author');?>: <?php echo Sanitize::encodeStringForDisplay($line['author']); ?> <input type="hidden" name="author" value="<?php echo Sanitize::encodeStringForDisplay($author); ?>">
</p>
<?php
if (!isset($line['ownerid']) || isset($_GET['template']) || $line['ownerid']==$userid || ($line['userights']==3 && $line['groupid']==$groupid) || $isadmin || ($isgrpadmin && $line['groupid']==$groupid)) {
    if (isset($qrightsdef) && $qrightsdef > 0) {
        echo '<p class=noticetext>'._('Note: The "make questions public by default" setting has been removed. To make a question public you must now explicitly set the use rights.').'</p>';
    }
    echo '<p>';
	echo '<label for=userights>'._('Use Rights:').'</label> <select name="userights" id="userights">';
	echo "<option value=\"0\" ";
	if ($line['userights']==0) {echo "SELECTED";}
	echo ">"._("Private")."</option>\n";
	echo "<option value=\"2\" ";
	if ($line['userights']==2) {echo "SELECTED";}
	echo ">"._("Allow use by all")."</option>\n";
	echo "<option value=\"3\" ";
	if ($line['userights']==3) {echo "SELECTED";}
	echo ">"._("Allow use by all and modifications by group")."</option>\n";
	echo "<option value=\"4\" ";
	if ($line['userights']==4) {echo "SELECTED";}
	echo ">"._("Allow use by all and modifications by all")."</option>\n";
	echo '</select><br/>';
	echo '<label for=license>' . _('License:').'</label> <select name="license" id="license" onchange="checklicense()">';
	echo '<option value="0" '.($line['license']==0?'selected':'').'>Copyrighted</option>';
	echo '<option value="1" '.($line['license']==1?'selected':'').'>IMathAS / WAMAP / MyOpenMath Community License (GPL + CC-BY)</option>';
	echo '<option value="2" '.($line['license']==2?'selected':'').'>Public Domain</option>';
	echo '<option value="3" '.($line['license']==3?'selected':'').'>Creative Commons Attribution-NonCommercial-ShareAlike</option>';
	echo '<option value="4" '.($line['license']==4?'selected':'').'>Creative Commons Attribution-ShareAlike</option>';
	echo '</select><span id="licensewarn" class=noticetext style="font-size:80%;"></span>';
	if ($line['otherattribution']=='') {
		echo '<br/><a href="#" onclick="$(\'#addattrspan\').show();$(this).hide();return false;">'._('Add additional attribution').'</a>';
		echo '<span id="addattrspan" style="display:none;">';
	} else {
		echo '<br/><span id="addattrspan">';
	}
	echo '<label for=addattr>'._('Additional Attribution').'</label>: <input type="text" size="80" name="addattr" id="addattr" value="'.Sanitize::encodeStringForDisplay($line['otherattribution'], true).'"/>';
	if ($line['otherattribution']!='') {
		echo '<br/><span class=noticetext style="font-size:80%">'._('You should only modify the attribution if you are SURE you are removing all portions of the question that require the attribution').'</span>';
	}
	echo '</span>';
    echo '</p>';
}
?>
<script>
var curlibs = '<?php echo Sanitize::encodeStringForJavascript($inlibs);?>';
var locklibs = '<?php echo Sanitize::encodeStringForJavascript($locklibs);?>';
function libselect() {
	GB_show('<?php echo _('Library Select');?>','libtree3.php?cid=<?php echo $cid;?>&libtree=popup&selectrights=1&libs='+curlibs+'&locklibs='+locklibs,500,500);
}
function setlib(libs) {
	if (libs.charAt(0)=='0' && libs.indexOf(',')>-1) {
		libs = libs.substring(2);
	}
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	if (libn.indexOf('Unassigned')>-1 && libn.indexOf(',')>-1) {
		libn = libn.substring(11);
	}
	document.getElementById("libnames").textContent = libn;
	$("#libonlysubmit").show();
}
function swapentrymode() {
	var butn = document.getElementById("entrymode");
	if (butn.value=="2-box entry") {
		document.getElementById("qcbox").style.display = "none";
		document.getElementById("abox").style.display = "none";
		document.getElementById("control").rows = 20;
		butn.value = "4-box entry";
	} else {
		document.getElementById("qcbox").style.display = "block";
		document.getElementById("abox").style.display = "block";
		document.getElementById("control").rows = 10;
		butn.value = "2-box entry";
	}
}
function incboxsize(box) {
	document.getElementById(box).rows += 2;
}
function decboxsize(box) {
	if (document.getElementById(box).rows > 2)
		document.getElementById(box).rows -= 2;
}

</script>
<p>
<?php echo _('My library assignments:'); ?> <span id="libnames"><?php echo Sanitize::encodeStringForDisplay($lnames);?></span><input type=hidden name="libs" id="libs" size="10" value="<?php echo Sanitize::encodeStringForDisplay($inlibs);?>">
<button type="button" onClick="libselect()"><?php echo _("Select Libraries"); ?></button>
<?php
if (isset($_GET['id']) && $canedit) {
	echo '<span id=libonlysubmit style="display:none"><input type=submit name=justupdatelibs value="Save Library Change Only"/></span>';
} 
if ($olnames !== '') {
    echo '<br><span class="small">' . _("Other's library assignments: ") . $olnames. '</span>';
}
?>
</p>
<div class="pdiv">
<?php echo _('Question type:'); ?> 
<div style="display:inline-block" class="dropdown" id="qtypedd">
    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">Select Type</button>
    <ul class="dropdown-menu">
      <li class="dropdown-submenu">
        <a href="#">Number</a>
        <ul class="dropdown-menu">
          <li><a href="#" data-sn="number" data-ln="Number">Numeric</a></li>
          <li><a href="#" data-sn="calculated" data-ln="Calculated Number">Calculated</a></li>
          <li><a href="#" data-sn="complex" data-ln="Complex Number">Complex</a></li>
          <li><a href="#" data-sn="calccomplex" data-ln="Calculated Complex Number">Calculated Complex</a></li>
        </ul>
      </li>
      <li class="dropdown-submenu">
        <a href="#">Selecting from Options</a>
        <ul class="dropdown-menu">
          <li><a href="#" data-sn="choices">Multiple-choice</a></li>
          <li><a href="#" data-sn="multans">Multiple-answer</a></li>
          <li><a href="#" data-sn="matching">Matching</a></li>
        </ul>
      </li>
      <li><a href="#" data-sn="numfunc">Algebraic Expression (Function)</a></li>
      <li class="dropdown-submenu">
        <a href="#">Text Entry / Upload</a>
        <ul class="dropdown-menu">
          <li><a href="#" data-sn="string">String</a></li>
          <li><a href="#" data-sn="essay">Essay</a></li>
          <li><a href="#" data-sn="file">File Upload</a></li>
         </ul>
      </li>
      <li><a href="#" data-sn="draw">Drawing</a></li>
      <li class="dropdown-submenu">
        <a href="#">N-Tuple</a>
        <ul class="dropdown-menu">
          <li><a href="#" data-sn="ntuple" data-ln="N-Tuple">Numeric</a></li>
          <li><a href="#" data-sn="calcntuple" data-ln="Calculated N-Tuple">Calculated</a></li>
          <li><a href="#" data-sn="complexntuple" data-ln="Complex N-Tuple">Complex</a></li>
          <li><a href="#" data-sn="calccomplexntuple" data-ln="Calculated Complex N-Tuple">Calculated Complex</a></li>
          <li><a href="#" data-sn="algntuple" data-ln="Algebraic N-Tuple">Algebraic</a></li>
        </ul>
      </li>
      <li class="dropdown-submenu">
        <a href="#">Matrix</a>
        <ul class="dropdown-menu">
          <li><a href="#" data-sn="matrix" data-ln="Matrix">Numeric</a></li>
          <li><a href="#" data-sn="calcmatrix" data-ln="Calculated Matrix">Calculated</a></li>
          <li><a href="#" data-sn="complexmatrix" data-ln="Complex Matrix">Complex</a></li>
          <li><a href="#" data-sn="calccomplexmatrix" data-ln="Calculated Complex Matrix">Calculated Complex</a></li>
          <li><a href="#" data-sn="algmatrix" data-ln="Algebraic Matrix">Algebraic</a></li>
        </ul>
      </li>
      <li class="dropdown-submenu">
        <a href="#">Interval</a>
        <ul class="dropdown-menu">
          <li><a href="#" data-sn="interval" data-ln="Interval">Numeric</a></li>
          <li><a href="#" data-sn="calcinterval" data-ln="Calculated Interval">Calculated</a></li>
        </ul>
      </li>
      <li class="dropdown-submenu">
        <a href="#">Chemical</a>
        <ul class="dropdown-menu">
          <li><a href="#" data-sn="chemeqn" data-ln="Chemical Equation">Equation</a></li>
          <li><a href="#" data-sn="molecule" data-ln="Chemical Molecule">Molecule</a></li>
        </ul>
      </li>
      <li><a href="#" data-sn="multipart">Multipart</a></li>
      <li><a href="#" data-sn="conditional">Conditional</a></li>
    </ul>
</div>
<input type=hidden name=qtype id=qtype value="<?php echo Sanitize::encodeStringForDisplay($line['qtype']);?>" />
</div>
<p>
<a href="#" onclick="window.open('<?php echo $imasroot;?>/help.php?section=writingquestions','Help','width='+(.35*screen.width)+',height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width*.6));return false;"><?php echo _('Writing Questions Help'); ?></a> |
<a href="#" onclick="window.open('<?php echo $imasroot;?>/assessment/libs/libhelp.php','Help','width='+(.35*screen.width)+',height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width*.6));return false;"><?php echo _('Macro Library Help'); ?></a>
<?php if (!isset($_GET['id'])) {
	echo ' | <a href="modtutorialq.php?'.Sanitize::encodeStringForDisplay($_SERVER['QUERY_STRING']).'">'._('Tutorial Style editor').'</a>';
}?>
</p>
<div id=ccbox>
<label for=control><?php echo _('Common Control'); ?></label>: <span class="noselect"><span class=pointer onclick="incctrlboxsize('control')">[+]</span><span class=pointer onclick="decctrlboxsize('control')">[-]</span></span>
<input type=button id="solveropenbutton" value="Solver">
<button type="submit"><?php echo _("Save"); ?></button>
<input type=submit name=test value="Save and Test Question" class="saveandtest" />
<button type="button" class="quickSaveButton" onclick="quickSaveQuestion()"><?php echo _('Quick Save and Preview'); ?></button>
<span class="noticetext quickSaveNotice"></span>
<BR>
<textarea style="width: 100%" cols=60 rows=<?php echo min(35,max(20,substr_count($line['control'],"\n")+3));?> id=control name=control <?php if (!$canedit) echo _("disabled");?>><?php echo Sanitize::encodeStringForDisplay($line['control'], true);?></textarea>
</div>


<div id=qtbox>
<label for=qtext><?php echo _('Question Text'); ?></label>: <span class="noselect"><span class=pointer onclick="incqtboxsize('qtext')">[+]</span><span class=pointer onclick="decqtboxsize('qtext')">[-]</span></span>
<button type="button" onclick="toggleeditor('qtext')" ><?php echo _("Toggle Editor"); ?></button>
<button type="submit"><?php echo _("Save"); ?></button>
<input type=submit name=test value="Save and Test Question" class="saveandtest" />
<button type="button" class="quickSaveButton" onclick="quickSaveQuestion()"><?php echo _('Quick Save and Preview'); ?></button>
<span class="noticetext quickSaveNotice"></span>
<BR>
<textarea style="width: 100%" cols=60 rows=<?php echo min(35,max(10,substr_count($line['qtext'],"\n")+3));?> id="qtext" name="qtext" <?php if (!$canedit) echo "readonly=\"readonly\"";?>><?php echo Sanitize::encodeStringForDisplay($line['qtext'], true);?></textarea>
</div>

<?php
if ($line['solution']=='') {
	echo '<p><a href="#" onclick="$(this).parent().hide();$(\'#solutionwrapper\').show();initsolneditor();return false;">'._('Add a detailed solution').'</a></p>';
	echo '<div id="solutionwrapper" style="display:none;">';
} else {
	echo '<div id="solutionwrapper">';
}
?>
<label for="solution"><?php echo _('Detailed Solution:'); ?></label>
<span class="noselect"><span class=pointer onclick="incqtboxsize('solution')">[+]</span><span class=pointer onclick="decqtboxsize('solution')">[-]</span></span>
<button type="button" onclick="toggleeditor('solution')" ><?php echo _("Toggle Editor"); ?></button>
<button type="submit"><?php echo _("Save"); ?></button>
<input type=submit name=test value="Save and Test Question" class="saveandtest" />
<button type="button" class="quickSaveButton" onclick="quickSaveQuestion()"><?php echo _('Quick Save and Preview'); ?></button>
<br/>
<label><input type="checkbox" name="usesrand" value="1" <?php if (($line['solutionopts']&1)==1) {echo 'checked="checked"';};?>
   onclick="$('#userandnote').toggle()">
<?php echo _('Uses random variables from the question, or question is not randomized.'); ?></label>
 <span id="userandnote" <?php if (($line['solutionopts']&1)==1) {echo 'style="display:none;"';}?>>
   <i><?php echo _('Be sure to include the question you are solving in the text'); ?></i>
 </span><br/>
<label><input type="checkbox" name="useashelp" value="2" <?php if (($line['solutionopts']&2)==2) {echo 'checked="checked"';};?>>
<?php echo _('Use this as a "written example" help button'); ?></label><br/>
<label><input type="checkbox" name="usewithans" value="4" <?php if (($line['solutionopts']&4)==4) {echo 'checked="checked"';};?>>
<?php echo _('Display with the "Show Answer"'); ?></label><br/>
<textarea style="width: 100%" cols=60 rows=<?php echo min(35,max(10,substr_count($line['solution'],"\n")+1));?> id="solution" name="solution" <?php if (!$canedit) echo "readonly=\"readonly\"";?>><?php echo Sanitize::encodeStringForDisplay($line['solution'], true);?></textarea>
</div>
<div id=imgbox>
<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
<label for=imgfile><?php echo _('Image file:'); ?></label> 
	<input type="file" name="imgfile" id="imgfile" <?php if (!$canedit) {echo 'disabled';};?>/>
  <label for=newimgvar><?php echo _('assign to variable:'); ?></label> 
    <input type="text" name="newimgvar" id="newimgvar" size="6" <?php if (!$canedit) {echo 'disabled';};?>/>
  <label for="newimgalt"><?php echo _('Description:'); ?></label> 
    <textarea rows=1 cols=30 name="newimgalt" id="newimgalt" <?php if (!$canedit) {echo 'disabled';};?>></textarea><br/>
<div id="imgListContainer" style="display:<?php echo (isset($images['vars']) && count($images['vars'])>0) ? 'block' : 'none'; ?>">
	<?php echo _('Images:'); ?>
	<ul id='imgList'>
<?php
if (isset($images['vars']) && count($images['vars'])>0) {
	foreach ($images['vars'] as $id=>$var) {
		if (substr($images['files'][$id],0,4)=='http') {
			$urlimg = $images['files'][$id];
		} else if (isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
			$urlimg = $urlmode."{$GLOBALS['AWSbucket']}.s3.amazonaws.com/qimages/{$images['files'][$id]}";
		} else {
			$urlimg = "$imasroot/assessment/qimages/{$images['files'][$id]}";
		}
		echo "<li>";
		echo "<label for=\"imgvar-$id\">"._("Variable:")."</label> <input type=\"text\" name=\"imgvar-$id\" id=\"imgvar-$id\" value=\"\$".Sanitize::encodeStringForDisplay($var)."\" size=\"10\" ".($canedit?'':'disabled')."/> <a href=\"".Sanitize::url($urlimg)."\" target=\"_blank\">View</a> ";
		echo "<label for=\"imgalt-$id\">"._("Description:")."</label> <textarea rows=1 cols=30 name=\"imgalt-$id\" id=\"imgalt-$id\" ".($canedit?'':'disabled').">".Sanitize::encodeStringForDisplay($images['alttext'][$id])."</textarea> ";
		echo "<label><input type=checkbox name=\"delimg-$id\" ".($canedit?'':'disabled')."/> Delete?</label>";
		echo "</li>";
	}
}
?>
	</ul>
</div>
<label for=helptype><?php echo _('Help button: Type:'); ?></label> <select name="helptype" id="helptype" <?php if (!$canedit) {echo 'disabled';};?>>
 <option value="video"><?php echo _('Video'); ?></option>
 <option value="read"><?php echo _('Read'); ?></option>
 </select>
 <label for=helpurl><?php echo _('URL')?></label>: <input type="text" name="helpurl" id="helpurl" size="30" <?php if (!$canedit) {echo 'disabled';};?>/>
 <label for=helpdescr><?php echo _('Description')?></label>: <input type="text" name="helpdescr" id="helpdescr" size="30" <?php if (!$canedit) {echo 'disabled';};?>/>
 <br/>
<?php
echo '<div id="helpbtnwrap" ';
if (count($extref)==0) {
	echo 'class="hidden"';
}
echo ">"._("Help buttons:")."<br/>";
echo '<ul id="helpbtnlist">';
if (count($extref)>0) {
	for ($i=0;$i<count($extref);$i++) {
		$extrefpt = explode('!!',$extref[$i]);
		echo '<li>Type: '.Sanitize::encodeStringForDisplay(ucfirst($extrefpt[0]));
		if ($extrefpt[0]=='video' && count($extrefpt)>2 && $extrefpt[2]==1) {
			echo ' (cc)';
		}
    echo ', URL: <a href="'.Sanitize::url($extrefpt[1]).'">'.Sanitize::encodeStringForDisplay($extrefpt[1])."</a>. ";
    if (!empty($extrefpt[3])) {
        echo _('Description') . ': ' . Sanitize::encodeStringForDisplay($extrefpt[3]) . '. ';
    }
    echo "<label><input type=\"checkbox\" name=\"delhelp-$i\" ".($canedit?'':'disabled').'/> '._('Delete?').'</label></li>';
	}
}
echo '</ul></div>'; //helpbtnlist, helpbtnwrap

echo '<p><label for=a11yalttype>'._('Use accessible alternative').':</label> ';
$allylabels = [_('no'),_('visual alt'),_('mouse alt'),_('visual or mouse alt')];
writeHtmlSelect ('a11yalttype',[0,1,2,3],$allylabels,$line['a11yalttype'],null,null,'onchange="changea11ytype()"');
echo ' <label>'._('Accessible alt ID').' <input type=text size=10 name=a11yalt value="'.($line['a11yalt']==0?'':Sanitize::encodeStringForDisplay($line['a11yalt'])).'"/></label>';
echo '<span id=a11yaltwarn' . ($line['a11yalt'] > 0 ? '' : ' style="display:none"').'> ';
echo '<span class=noticetext><br>'._('It is likely you should not be using this option. Read the help for details.').'</span></span> ';
echo '<a href="../help.php?section=a11yalt" target="_blank">'._('Help').'</a>';
echo '</p>';

if ($myrights==100) {
	echo '<p><label>'._('Mark question as deprecated and suggest alternative?').' <input type="checkbox" name="doreplaceby" ';
	if ($line['replaceby']!=0) {
		echo 'checked="checked"';
	}
	echo '/></label> <label>'._('Suggested replacement ID:').' <input type="text" size="5" name="replaceby" value="';
	if ($line['replaceby']>0) {
		echo $line['replaceby'];
	}
	echo '"/></label>.  <i>'._('Do not use this unless you know what you\'re doing').'</i></p>';
}
if ($line['deleted']==1 && ($myrights==100 || $line['ownerid']==$userid)) {
	echo '<p>'._('This question is currently marked as deleted.').' <label><input type="checkbox" name="undelete"> '._('Un-delete question').'</label></p>';
}
?>
</div>
<p>
<button type="submit"><?php echo _("Save"); ?></button>
<input type=submit name=test value="Save and Test Question" class="saveandtest" />
<button type="button" class="quickSaveButton" onclick="quickSaveQuestion()"><?php echo _('Quick Save and Preview'); ?></button>
<span class="noticetext quickSaveNotice"></span>
</p>
</form>

<script type="text/javascript">
$("input[name=imgfile]").on("change", function(event) {
	var maxsize = $("input[name=MAX_FILE_SIZE]").val();
	if (this.files && this.files[0] && this.files[0].size>maxsize) {
		alert("Your image is too large. Size cannot exceed "+maxsize+" btyes");
		$(this).val("");
	}
});

if (FormData){ // Only allow quicksave if FormData object exists
	var quickSaveQuestion = function(){
		// Add text to notice areas
		$(".quickSaveNotice").html("Saving...");
		// Save codemirror and tinyMCE data
		try {
			if (controlEditor) controlEditor.save();
			if (window.tinyMCE) {
				if (editoron) {
					tinyMCE.get("qtext").save();
				} else {
					qEditor["qtext"].save();
				}
				if (seditoron) {
					tinyMCE.get("solution").save();
				} else {
					qEditor["solution"].save();
				}
			} else {
				for (i in qEditor) { qEditor[i].save(); }
			}
		} catch (err){
			quickSaveQuestion.errorFunc();
		}
		// Get form data
		var data = new FormData($("form")[0]);

		$.ajax({
			url: quickSaveQuestion.url + "&quick=1",
			type: 'POST',
			data: data,
			contentType: false,
			processData: false,
			success: function(res){
				// Parse out response string
				var res = JSON.parse(res);
				var formAction = res.formAction;
				var images = res.images;
				// Change form action url and testing address
				if (formAction.indexOf("moddataset.php") > -1) {
					quickSaveQuestion.url = formAction;
					quickSaveQuestion.testAddr = '<?php echo "$imasroot/course/$testqpage?cid=$cid&qsetid="; ?>' + res.id
				} else {
					quickSaveQuestion.errorFunc();
				}
				// Change form action and url in address bar
				$("form")[0].action = quickSaveQuestion.url;
				if (window.history.replaceState) window.history.replaceState({}, "qs", quickSaveQuestion.url);
				// Change outputmsg and errmsg
				$("#outputmsgContainer").html(res.outputmsg);
				$("#errmsgContainer").html(res.errmsg);
				// HANDLE IMAGES
				var imgUploaded = $("input[name='imgfile']")[0].files.length > 0 ? true : false; // Image uploaded
				var imgDeleted = $("input[name^='delimg-']:checked").length > 0 ? true : false; // Image deleted
				if (Object.keys(images.vars).length>0 || imgUploaded || imgDeleted) {
					// Clear image inputs
					var imgFile = $("input[name='imgfile']");
					imgFile.replaceWith( imgFile = imgFile.val('').clone(true));
					$("input[name='newimgvar'], input[name='newimgalt']").val('');

					// Update image list
					$("#imgList").empty();
					var imgCount = 0;
					for (id in images.vars){
						imgCount++;
						$("#imgList").append(
							"<li><label>Variable: <input type='text' name='imgvar-" + id + "' value='$" + images.vars[id] + "' size='10' /></label>" +
							" <a href='" + res.imgUrlBase + images.files[id] + "' target='_blank'>View</a>" +
							" <label>Description: <textarea rows=1 cols=30 name='imgalt-" + id + "'>" + images.alttext[id] + "</textarea></label>" +
							" <label><input type='checkbox' name='delimg-" + id + "'/> Delete?</label>" +
							"</li>"
						);
					}
				} else { // No uploads/deletes: still count number of images
					var imgCount = 0;
					for (i in images.vars) imgCount++;
				}
				// Hide image list if no images in question
				$("#imgListContainer").css("display", imgCount > 0 ? "block" : "none");

				//handle extref help buttons
				if (res.extref.length>0) {
					$("#helpbtnlist").html('');
					for (var i=0;i<res.extref.length;i++) {
						$("#helpbtnlist").append("<li>Type: "+res.extref[i][0] +
                            ", URL: <a href='"+res.extref[i][1]+"'>"+res.extref[i][1]+"</a>. " +
                            ((res.extref[i][2]) ? (_("Description")+": "+res.extref[i][2]+". "):"") +
							"<label><input type=\"checkbox\" name=\"delhelp-"+i+"\"/>" + _("Delete?") + "</label></li>");
					}
					$("#helpbtnwrap").removeClass("hidden");
				} else {
					$("#helpbtnwrap").addClass("hidden");
				}
				$("input[name=helpurl],input[name=helpdescr]").val('');

				// Empty notices
				$(".quickSaveNotice").empty();
				// Load preview page
				let leftpos = screen.left ?? screen.availLeft ?? 0;
    			let toppos = screen.top ?? screen.availTop ?? 0;

				var previewpop = window.open(quickSaveQuestion.testAddr, 'Testing', 'width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top='+(20+toppos)+',left='+(.6*screen.width-20+leftpos));
				previewpop.focus();
			},
			error: function(res){
				quickSaveQuestion.errorFunc();
			}
		});
	}
	quickSaveQuestion.url = "<?php echo $formAction; // Sanitized near line 806 ?>&quick=1";
	quickSaveQuestion.testAddr = '<?php echo "$imasroot/course/$testqpage?cid=$cid&qsetid=".Sanitize::encodeUrlParam($_GET['id'] ?? 0); ?>';
	// Method to handle errors...
	quickSaveQuestion.errorFunc = function(){
		$(".quickSaveNotice").html("Error with Quick Save: try again, or use the \"Save\" option.");
	}
	// Key-binding method
	quickSaveQuestion.keyBind = function(e){
		var key = e.which || e.keyCode;
		if (key == 83 && e.ctrlKey == true){
			e.preventDefault();
			e.stopPropagation();
			quickSaveQuestion();
			return false;
		}
	}
	// Bind key event
	$(document).on("keydown", quickSaveQuestion.keyBind);
	// A little trickier for tinyMCE due to race conditions
	var mceTry = setInterval(function(){
		try {
			tinymce.get('qtext').on('keydown', quickSaveQuestion.keyBind);
			clearInterval(mceTry);
		} catch (e) {}
	}, 1000);

	// Show Quick Save and Preview buttons
	$(function() {
		$(".quickSaveButton").css("display", "inline");
		$(".saveandtest").remove();
	});
} else { // No FormData object
	$(function() {
		$(".quickSaveButton, .quickSaveNotice").remove();
	});
}
</script>

<?php
$placeinfooter='
<div id="solverpopup" style="display: none" class="solverpopup">
	<div id="solvertopbar">
		<div id="solverclosebutton">X</div>
		<span>Solver</span>
	</div>
	<div id="solverinsides">
	<div id="operationselect">
	Select and drag or copy an expression from your question code.
	<img id="solverinputhelpicon" src="'.$staticroot.'/img/help.gif" alt="Help"><br/>
	<div id="solverinputhelp" style="display: none;">
	</div>
	<input id="imathastosage" type="text" size="30">
	<select id="solveroperation" name="solveroperation">
		<option id="solverchoose" value="">Choose</option>
		<option id="solversolve" value="solve">Solve</option>
		<option id="solversolve" value="simplify">Simplify</option>
		<option id="solverdiff" value="diff">Differentiate</option>
		<option id="solverint" value="integral">Integrate</option>
		<option id="solverplot" value="plot">Plot</option>
		</select>
	<button id="solvergobutton" type="button">Go</button>
	</div>
	<div id="sagemathcode" style="display: none;"></div>
	<div id="sagecellcontainer">
		<div id="sagecell"></div>
		<img id="solverhelpicon" src="'.$staticroot.'/img/help.gif" alt="Help"><br/>
	</div>
	<div id="solverhelpbody" style="display: none">
	</div>
	<div id="sagecelloutput"></div>
    <div id="sagetocontroldiv" style="display: none;" >
		Drag this to the Common Control box or use the buttons below.
	<img id="solveroutputhelpicon" src="'.$staticroot.'/img/help.gif" alt="Help"><br/>
	<div id="sagetocontrolresult">
		<p><span id="sagetocontrol" draggable="true"></span></p>
	</div>
	</div>
	<div id="solveroutputhelp" style="display: none;">
	</div>
	<input id="solverappendalone" type="button" value="Insert in Common Control">
	<input id="solverappend" type="button" value="Insert as $answer">
	</div>
</div>
';
?>

<?php
	require_once "../footer.php";
?>
