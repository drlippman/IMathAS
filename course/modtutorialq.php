<?php
require("../init.php");
require("../includes/htmlutil.php");


if ($myrights<20) {
	require("../header.php");
	echo "You need to log in as a teacher to access this page";
	require("../footer.php");
	exit;
}

function stripsmartquotes($text) {
		$text = str_replace(
			array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
			array("'", "'", '"', '"', '-', '--', '...'),
			$text);
		// Next, replace their Windows-1252 equivalents.
		$text = str_replace(
			array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
			array("'", "'", '"', '"', '-', '--', '...'),
			$text);
		return $text;
 	}

$isadmin = false;
$isgrpadmin = false;
if (!isset($_GET['aid'])) {
	if ($_GET['cid']=="admin") {
		if ($myrights == 100) {
			$isadmin = true;
			$cid = 'admin';
		} else if ($myrights==75) {
			$isgrpadmin = true;
		}
	}
}
$now = time();
$editmsg = '';

if (isset($_POST['text'])) {
	if (!isset($_GET['id'])) {
		$id = 'new';
	} else {
		$id = Sanitize::onlyInt($_GET['id']);
	}
	//DB $_POST = stripslashes_deep($_POST);
	$qtext = stripsmartquotes($_POST['text']);
	$nparts = intval($_POST['nparts']);
	$qtypes = array();
	$qparts = array();
	$questions = array();
	$feedback = array();
	$feedbacktxtdef = array();
	$feedbacktxtessay = array();
	$answerboxsize = array();
	$variables = array();
	$scoremethod = array();
	$useeditor = array();
	$answer = array();
	$partial = array();
	$qtol = array();
	for ($n=0;$n<$nparts;$n++) {
		$qtypes[$n] = $_POST['qtype'.$n];
		$feedback[$n] = array();
		if ($qtypes[$n] == 'choices') {
			$questions[$n] = array();
			$answer[$n] = $_POST['ans'.$n];
		} else if ($qtypes[$n] == 'number') {
			$partialans[$n] = array();
			$qtol[$n] = (($_POST['qtol'.$n]=='abs')?'|':'') . $_POST['tol'.$n];
			$feedbacktxtdef[$n] = $_POST['fb'.$n.'-def'];
			$answer[$n] = $_POST['txt'.$n.'-'.$_POST['ans'.$n]];
			$_POST['pc'.$n.'-'.$_POST['ans'.$n]] = 1;
			$answerboxsize[$n] = intval($_POST['numboxsize'.$n]);
		} else if ($qtypes[$n] == 'calculated') {
			$partialans[$n] = array();
			$qtol[$n] = (($_POST['qtol'.$n]=='abs')?'|':'') . $_POST['tol'.$n];
			$feedbacktxtdef[$n] = $_POST['fb'.$n.'-def'];
			$answer[$n] = '"'.$_POST['txt'.$n.'-'.$_POST['ans'.$n]].'"';
			$_POST['pc'.$n.'-'.$_POST['ans'.$n]] = 1;
			$answerboxsize[$n] = intval($_POST['numboxsize'.$n]);
			$answerformat[$n] = $_POST['answerformat'.$n].(trim($_POST['answerformat'.$n])!=''?",":"")."noval";
		} else if ($qtypes[$n] == 'numfunc') {
			$partialans[$n] = array();
			$qtol[$n] = (($_POST['funcqtol'.$n]=='abs')?'|':'') . $_POST['functol'.$n];
			$feedbacktxtdef[$n] = $_POST['fb'.$n.'-def'];
			$answer[$n] = '"'.$_POST['txt'.$n.'-'.$_POST['ans'.$n]].'"';
			$_POST['pc'.$n.'-'.$_POST['ans'.$n]] = 1;
			$answerboxsize[$n] = intval($_POST['funcboxsize'.$n]);
			$variables[$n] = $_POST['variables'.$n];
		} else if ($qtypes[$n] == 'essay') {
			$answer[$n] = '"'.str_replace('"','\\"',$_POST['essay'.$n.'-fb']).'"';
			if (isset($_POST['useeditor'.$n])) {
				$useeditor[$n] = true;
			}
			if (isset($_POST['takeanything'.$n])) {
				$scoremethod[$n] = 'takeanything';
			}
			$answerboxsize[$n] = intval($_POST['essayrows'.$n]);
		}
		if ($qtypes[$n] == 'choices' || $qtypes[$n] == 'number' || $qtypes[$n] == 'calculated' || $qtypes[$n] == 'numfunc') {
			$qparts[$n] = intval($_POST['qparts'.$n]);
			$questions[$n] = array();
			$partialans[$n] = array();
			$feedbacktxt[$n] = array();
			$partial[$n] = array();
			for ($i=0;$i<$qparts[$n];$i++) {
				if (trim($_POST['txt'.$n.'-'.$i])=='') {continue;}
				if ($qtypes[$n] == 'choices') {
					$questions[$n][] = $_POST['txt'.$n.'-'.$i];
				} else if ($qtypes[$n] == 'number' || $qtypes[$n] == 'calculated' || $qtypes[$n] == 'numfunc') {
					$partialans[$n][] = $_POST['txt'.$n.'-'.$i];
				}
				$feedbacktxt[$n][] = $_POST['fb'.$n.'-'.$i];
				$partial[$n][] = floatval($_POST['pc'.$n.'-'.$i]);
			}
			$qparts[$n] = count($feedbacktxt[$n]);
		} else if ($qtypes[$n] == 'essay') {
			$qparts[$n] = 0;
			$feedbacktxtessay[$n] = $_POST['essay'.$n.'-fb'];
		}
	}
	$nhints = intval($_POST['nhints']);
	$hinttext = array();
	for ($n=0;$n<$nhints;$n++) {
		if (!empty($_POST['hint'.$n])) {
			$hinttext[] = $_POST['hint'.$n];
		}
	}
	$nhints = count($hinttext);

	//generate question code
	//this part stores the values in the question code, in form that makes
	//them easy to recover later.
	$code = "//start randomization code - Tutorial Style question\n\n";
	$code .= $_POST['randvars'];
	$code .= "\n\n//end randomization code - Tutorial Style question\n\n";
	if ($nparts==1) {
		$qtype = $qtypes[0];

		$partialout = array();
		for ($i=0;$i<$qparts[0];$i++) {
			if ($qtypes[0]=='choices') {
				$code .= '$questions['.$i.'] = "'.str_replace('"','\\"',$questions[0][$i]).'"'."\n";
			}
			$code .= '$feedbacktxt['.$i.'] = "'.str_replace('"','\\"',$feedbacktxt[0][$i]).'"'."\n";
			if ($partial[0][$i]!=0 || $qtypes[0]=='number' || $qtypes[0] == 'numfunc' || $qtypes[0] == 'calculated') {
				if ($qtypes[0]=='choices') {
					$partialout[] = $i;
				} else if ($qtypes[0]=='number') {
					$partialout[] = $partialans[0][$i];
				} else if ($qtypes[0] == 'numfunc' || $qtypes[0] == 'calculated') {
					$partialout[] = '"'.$partialans[0][$i].'"';
				}
				$partialout[] = $partial[0][$i];
			}
		}
		if (count($partialout)>0) {
			$code .= '$partialcredit = array('.implode(',',$partialout).')'."\n";
		}
		if ($qtypes[0]=='choices') {
			$code .= '$displayformat = "'.$_POST['qdisp0'].'"'."\n";
			$code .= '$noshuffle = "'.$_POST['qshuffle0'].'"'."\n";
		} else if ($qtypes[0]=='number' || $qtypes[0]=='calculated' || $qtypes[0] == 'numfunc') {
			$code .= '$feedbacktxtdef = "'.str_replace('"','\\"',$feedbacktxtdef[0]).'"'."\n";
			$code .= '$answerboxsize = '.$answerboxsize[0]."\n";
			$code .= (($_POST['qtol0']=='abs')?'$abstolerance':'$reltolerance').' = '.$_POST['tol0']."\n";
			if ($qtypes[0] == 'numfunc') {
				$code .= '$variables = "'.$variables[0].'"'."\n";
				$code .= '$requiretimes = ""'."\n";
				if (strpos($answer[0],'=')!==false) {//is an equation answer
					$code .= '$answerformat = "equation"'."\n";
				}
			} else if ($qtypes[0] == 'calculated') {
				$code .= '$requiretimes = ""'."\n";
				$code .= '$answerformat = "'.$answerformat[0].'"'."\n";
			}
		} else if ($qtypes[0]=='essay') {
			$code .= '$feedbacktxtessay = "'.str_replace('"','\\"',$feedbacktxtessay[0]).'"'."\n";
			$code .= '$answerboxsize = '.$answerboxsize[0]."\n";
			if (isset($useeditor[0])) {
				$code .= '$displayformat = "editor"'."\n";
			}
			if (isset($scoremethod[0])) {
				$code .= '$scoremethod = "'.$scoremethod[0].'"'."\n";
			}
		}
		$code .= '$answer = '.$answer[0]."\n\n";
	} else {
		$qtype = 'multipart';
		$code .= '$anstypes = "'.implode(',',$qtypes).'"'."\n\n";
		for ($n=0;$n<$nparts;$n++) {
			$partialout = array();
			for ($i=0;$i<$qparts[$n];$i++) {
				if ($qtypes[$n]=='choices') {
					$code .= '$questions['.$n.']['.$i.'] = "'.str_replace('"','\\"',$questions[$n][$i]).'"'."\n";
				}

				$code .= '$feedbacktxt['.$n.']['.$i.'] = "'.str_replace('"','\\"',$feedbacktxt[$n][$i]).'"'."\n";
				if ($partial[$n][$i]!=0 || $qtypes[$n]=='number' || $qtypes[$n] == 'numfunc' || $qtypes[$n] == 'calculated') {
					if ($qtypes[$n]=='choices') {
						$partialout[] = $i;
					} else if ($qtypes[$n]=='number') {
						$partialout[] = $partialans[$n][$i];
					} else if ($qtypes[$n] == 'numfunc' || $qtypes[$n] == 'calculated') {
						$partialout[] = '"'.$partialans[$n][$i].'"';
					}
					$partialout[] = $partial[$n][$i];
				}
			}
			if (count($partialout)>0) {
				$code .= '$partialcredit['.$n.'] = array('.implode(',',$partialout).')'."\n";
			}
			if ($qtypes[$n]=='choices') {
				$code .= '$displayformat['.$n.'] = "'.$_POST['qdisp'.$n].'"'."\n";
				$code .= '$noshuffle['.$n.'] = "'.$_POST['qshuffle'.$n].'"'."\n";
			} else if ($qtypes[$n]=='number' || $qtypes[$n] == 'numfunc' || $qtypes[$n] == 'calculated') {
				$code .= '$feedbacktxtdef['.$n.'] = "'.str_replace('"','\\"',$feedbacktxtdef[$n]).'"'."\n";
				$code .= '$answerboxsize['.$n.'] = '.$answerboxsize[$n]."\n";
				$code .= (($_POST['qtol'.$n]=='abs')?'$abstolerance[':'$reltolerance[').$n.'] = '.$_POST['tol'.$n]."\n";
				if ($qtypes[$n] == 'numfunc') {
					$code .= '$variables['.$n.'] = "'.$variables[$n].'"'."\n";
					$code .= '$requiretimes['.$n.'] = ""'."\n";
					if (strpos($answer[$n],'=')!==false) {//is an equation answer
						$code .= '$answerformat['.$n.'] = "equation"'."\n";
					}
				} else if ($qtypes[$n] == 'calculated') {
					$code .= '$requiretimes['.$n.'] = ""'."\n";
					$code .= '$answerformat['.$n.'] = "'.$answerformat[$n].'"'."\n";
				}
			} else if ($qtypes[$n]=='essay') {
				$code .= '$feedbacktxtessay['.$n.'] = "'.str_replace('"','\\"',$feedbacktxtessay[$n]).'"'."\n";
				$code .= '$answerboxsize['.$n.'] = '.$answerboxsize[$n]."\n";
				if (isset($useeditor[$n])) {
					$code .= '$displayformat['.$n.'] = "editor"'."\n";
				}
				if (isset($scoremethod[$n])) {
					$code .= '$scoremethod['.$n.'] = "'.$scoremethod[$n].'"'."\n";
				}
			}
			$code .= '$answer['.$n.'] = '.$answer[$n]."\n\n";
		}
	}
	for ($i=0;$i<$nhints;$i++) {
		$code .= '$hinttext['.$i.'] = "'.str_replace('"','\\"',$hinttext[$i]).'"'."\n";
	}

	$code .= "\n//end stored values - Tutorial Style question\n\n";
	$code .= $_POST['keepcode']."\n";
	$code .= "\n//end retained code - Tutorial Style question\n\n";
	//$code .= '$noshuffle = "all"'."\n";

	//now we convert as needed
	$qtextpre = '';

	//form hoverovers for hints
	if ($nhints>0) {
		$qtextpre .= '<p style="text-align: right">';
		for ($i=0;$i<$nhints;$i++) {
			$code .= '$hintlink['.$i.'] = formhoverover("Hint '.($i+1).'",$hinttext['.$i.'])'."\n";
			$qtextpre .= '$hintlink['.$i.'] ';
		}
		$qtextpre .= '</p>';
	}
	$code .= "\n";

	//form feedback text
	if ($nparts==1) {
		if ($qtypes[0]=='choices') {
			$code .= '$feedback = getfeedbacktxt($stuanswers[$thisq], $feedbacktxt, $answer)'."\n";
		} else if ($qtypes[0]=='number') {
			$code .= '$feedback = getfeedbacktxtnumber($stuanswers[$thisq], $partialcredit, $feedbacktxt, $feedbacktxtdef, "'.$qtol[0].'")'."\n";
		} else if ($qtypes[0]=='calculated') {
			$code .= '$feedback = getfeedbacktxtcalculated($stuanswers[$thisq], $stuanswersval[$thisq], $partialcredit, $feedbacktxt, $feedbacktxtdef, $answerformat, $requiretimes, "'.$qtol[0].'")'."\n";
		} else if ($qtypes[0]=='numfunc') {
			$code .= '$feedback = getfeedbacktxtnumfunc($stuanswers[$thisq], $partialcredit, $feedbacktxt, $feedbacktxtdef, $variables, $requiretimes, "'.$qtol[0].'")'."\n";
		} else if ($qtypes[0]=='essay') {
			$code .= '$feedback = getfeedbacktxtessay($stuanswers[$thisq], $feedbacktxtessay)'."\n";
		}
	} else {
		for ($n=0;$n<$nparts;$n++) {
			if ($qtypes[$n]=='choices') {
				$code .= '$feedback['.$n.'] = getfeedbacktxt($stuanswers[$thisq]['.$n.'], $feedbacktxt['.$n.'], $answer['.$n.'])'."\n";
			} else if ($qtypes[$n]=='number') {
				$code .= '$feedback['.$n.'] = getfeedbacktxtnumber($stuanswers[$thisq]['.$n.'], $partialcredit['.$n.'], $feedbacktxt['.$n.'], $feedbacktxtdef['.$n.'], "'.$qtol[$n].'")'."\n";
			} else if ($qtypes[$n]=='calculated') {
				$code .= '$feedback['.$n.'] = getfeedbacktxtcalculated($stuanswers[$thisq]['.$n.'], $stuanswersval[$thisq]['.$n.'], $partialcredit['.$n.'], $feedbacktxt['.$n.'], $feedbacktxtdef['.$n.'] , $answerformat['.$n.'] , $requiretimes['.$n.'], "'.$qtol[$n].'")'."\n";
			} else if ($qtypes[$n]=='numfunc') {
				$code .= '$feedback['.$n.'] = getfeedbacktxtnumfunc($stuanswers[$thisq]['.$n.'], $partialcredit['.$n.'], $feedbacktxt['.$n.'], $feedbacktxtdef['.$n.'], $variables['.$n.'], $requiretimes['.$n.'],"'.$qtol[$n].'")'."\n";
			} else if ($qtypes[$n]=='essay') {
				$code .= '$feedback['.$n.'] = getfeedbacktxtessay($stuanswers[$thisq]['.$n.'], $feedbacktxtessay['.$n.'])'."\n";
			}
		}
	}
	$qtext = $qtextpre . $qtext;
	//DB $code = addslashes($code);
	//DB $qtext = addslashes($qtext);

	if ($id=='new') {
		$mt = microtime();
		$uqid = substr($mt,11).substr($mt,2,6);
		$ancestors = '';
		if (isset($_GET['templateid'])) {
			//DB $query = "SELECT ancestors FROM imas_questionset WHERE id='{$_GET['templateid']}'";
			//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			//DB $ancestors = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT ancestors FROM imas_questionset WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['templateid']));
			$ancestors = $stm->fetchColumn(0);
			if ($ancestors!='') {
				$ancestors = $_GET['templateid'] . ','. $ancestors;
			} else {
				$ancestors = $_GET['templateid'];
			}
		}
		//DB $query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,description,ownerid,author,userights,qtype,control,qtext,ancestors) VALUES ";
		//DB $query .= "($uqid,$now,$now,'{$_POST['description']}','$userid','{$_POST['author']}','{$_POST['userights']}','$qtype','$code','$qtext','$ancestors');";
		//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		//DB $id = mysql_insert_id();
		$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,description,ownerid,author,userights,qtype,control,qtext,ancestors) VALUES ";
		$query .= "(:uniqueid, :adddate, :lastmoddate, :description, :ownerid, :author, :userights, :qtype, :control, :qtext, :ancestors);";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':uniqueid'=>$uqid, ':adddate'=>$now, ':lastmoddate'=>$now, ':description'=>$_POST['description'], ':ownerid'=>$userid,
			':author'=>$_POST['author'], ':userights'=>$_POST['userights'], ':qtype'=>$qtype, ':control'=>$code, ':qtext'=>$qtext, ':ancestors'=>$ancestors));
		$id = $DBH->lastInsertId();
		$_GET['id'] = $id;
		if (isset($_GET['makelocal'])) {
			//DB $query = "UPDATE imas_questions SET questionsetid='$qsetid' WHERE id='{$_GET['makelocal']}'";
			//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_questions SET questionsetid=:questionsetid WHERE id=:id");
			$stm->execute(array(':questionsetid'=>$id, ':id'=>$_GET['makelocal']));
			$editmsg .= " Local copy of Question Created ";
			$frompot = 0;
		} else {
			$editmsg .= " Question Added to QuestionSet. ";
			$frompot = 1;
		}
	} else {
		$isok = true;
		if ($isgrpadmin) {
			//DB $query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
			//DB $query .= "WHERE iq.id='{$_GET['id']}' AND iq.ownerid=imas_users.id AND (imas_users.groupid='$groupid' OR iq.userights>2)";
			//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			//DB if (mysql_num_rows($result)==0) {
			$query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
			$query .= "WHERE iq.id=:id AND iq.ownerid=imas_users.id AND (imas_users.groupid=:groupid OR iq.userights>2)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':id'=>$_GET['id'], ':groupid'=>$groupid));
			if ($stm->rowCount()==0) {
				$isok = false;
			}
			//$query = "UPDATE imas_questionset AS iq,imas_users SET iq.description='{$_POST['description']}',iq.author='{$_POST['author']}',iq.userights='{$_POST['userights']}',";
			//$query .= "iq.qtype='{$_POST['qtype']}',iq.control='{$_POST['control']}',iq.qcontrol='{$_POST['qcontrol']}',";
			//$query .= "iq.qtext='{$_POST['qtext']}',iq.answer='{$_POST['answer']}',iq.lastmoddate=$now ";
			//$query .= "WHERE iq.id='{$_GET['id']}' AND iq.ownerid=imas_users.id AND (imas_users.groupid='$groupid' OR iq.userights>2)";
		}
		if (!$isadmin && !$isgrpadmin) {  //check is owner or is allowed to modify
			//DB $query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
			//DB $query .= "WHERE iq.id='{$_GET['id']}' AND iq.ownerid=imas_users.id AND (iq.ownerid='$userid' OR (iq.userights=3 AND imas_users.groupid='$groupid') OR iq.userights>3)";
			//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			//DB if (mysql_num_rows($result)==0) {
			$query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
			$query .= "WHERE iq.id=:id AND iq.ownerid=imas_users.id AND (iq.ownerid=:ownerid OR (iq.userights=3 AND imas_users.groupid=:groupid) OR iq.userights>3)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':id'=>$_GET['id'], ':ownerid'=>$userid, ':groupid'=>$groupid));
			if ($stm->rowCount()==0) {
				$isok = false;
			}
		}
		if ($isok) {
			//DB $_POST = addslashes_deep($_POST);

			//DB $query = "UPDATE imas_questionset SET description='{$_POST['description']}',author='{$_POST['author']}',userights='{$_POST['userights']}',";
			//DB $query .= "qtype='$qtype',control='$code',qtext='$qtext',lastmoddate=$now WHERE id='$id'";
			//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
			$query = "UPDATE imas_questionset SET description=:description,author=:author,userights=:userights,";
			$query .= "qtype=:qtype,control=:control,qtext=:qtext,lastmoddate=:lastmoddate WHERE id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':description'=>$_POST['description'], ':author'=>$_POST['author'], ':userights'=>$_POST['userights'], ':qtype'=>$qtype,
				':control'=>$code, ':qtext'=>$qtext, ':lastmoddate'=>$now, ':id'=>$id));
		}
	}
	if (!isset($_GET['aid'])) {
		$editmsg .=  "<a href=\"manageqset.php?cid=$cid\">Return to Question Set Management</a>\n";
	} else {
		if ($frompot==1) {
			$editmsg .=  "<a href=\"modquestion.php?qsetid=$id&cid=$cid&aid=".Sanitize::onlyInt($_GET['aid'])."&process=true&usedef=true\">Add Question to Assessment using Defaults</a> | \n";
			$editmsg .=  "<a href=\"modquestion.php?qsetid=$id&cid=$cid&aid=".Sanitize::onlyInt($_GET['aid'])."\">Add Question to Assessment</a> | \n";
		}
		$editmsg .=  "<a href=\"addquestions.php?cid=$cid&aid=".Sanitize::onlyInt($_GET['aid'])."\">Return to Assessment</a>\n";
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
	$stm->execute(array(':qsetid'=>$id));
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
			$stm->execute(array(':groupid'=>$groupid, ':qsetid'=>$id));
		} else {
			//unassigned, or owner and lib not closed or mine
			$query = "SELECT ili.libid FROM imas_library_items AS ili JOIN imas_libraries AS il ON ";
			$query .= "(ili.libid=il.id OR ili.libid=0) AND il.deleted=0 WHERE ili.qsetid=:qsetid AND ili.deleted=0 ";
			$query .= " AND ((ili.ownerid=:ownerid AND (il.ownerid=:ownerid2 OR il.userights%3<>1)) OR ili.libid=0)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':qsetid'=>$id, ':ownerid'=>$userid, ':ownerid2'=>$userid));
		}
		//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$haverightslibs = array();
		//DB while($row = mysql_fetch_row($result)) {
		while($row = $stm->fetch(PDO::FETCH_NUM)) {
			$haverightslibs[] = $row[0];
		}
	}

	if (count($newlibs)==0 && $allcurrentlibs[0]!=0 && count($haverightslibs)==count($allcurrentlibs)) {
		//if we have no selected libs,
		// and not currently unassigned
		// and we have rights to remove all current items
		// then undelete or add Unassigned
		$newlibs[] = 0;
	}

	//remove any that we have the rights to but are not in newlibs
	$toremove = array_values(array_diff($haverightslibs,$newlibs));
	//undelete any libs that are new and in deleted libs
	$toundelete = array_values(array_intersect($newlibs,$alldeletedlibs));
	//add any new librarys that are not current and aren't being undeleted
	$toadd = array_values(array_diff($newlibs,$allcurrentlibs,$toundelete));


	$now = time();
	if (count($toundelete)>0) {
		foreach($toundelete as $libid) {
			$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=0,lastmoddate=:now,ownerid=:ownerid WHERE qsetid=:qsetid AND libid=:libid");
			$stm->execute(array(':libid'=>$libid, ':qsetid'=>$id, ':now'=>$now, ':ownerid'=>$userid));
		}
	}
	if (count($toadd)>0) {
		foreach($toadd as $libid) {
			$stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,lastmoddate) VALUES (:libid, :qsetid, :ownerid, :now)");
			$stm->execute(array(':libid'=>$libid, ':qsetid'=>$id, ':ownerid'=>$userid, ':now'=>$now));
		}
	}
	if (count($toremove)>0) {
		foreach($toremove as $libid) {
			$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=:now WHERE libid=:libid AND qsetid=:qsetid");
			$stm->execute(array(':libid'=>$libid, ':qsetid'=>$id, ':now'=>$now));
		}
	}

	$editmsg .= "<script>addr = '$imasroot/course/testquestion.php?cid=$cid&qsetid=" . Sanitize::onlyInt($id) . "';";
			//echo "function previewit() {";
	$editmsg .= "previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));\n";
	$editmsg .=  "previewpop.focus();";
	$editmsg .=  "</script>";

}

//return array (nparts, qparts, nhints, qdisp, questions, feedbacktxt, answer, hinttext)
function getqvalues($code,$type) {
	$partialcredit = array();
	$feedbacktxtdef = array();

	if (strpos($code,'//end retained') !== false) {
		$keepcode = substr($code, strpos($code,'//end stored'), strpos($code,'//end retained')-strpos($code,'//end stored'));
		$keepcode = trim(substr($keepcode, strpos($keepcode,"\n")));
	} else {
		$keepcode = '';
	}
	if (strpos($code,'//end randomization') !== false) {
		$randvars = substr($code, strpos($code,'//start randomization'), strpos($code,'//end randomization')-strpos($code,'//start randomization'));
		$randvars = trim(substr($randvars, strpos($randvars,"\n")));
	} else {
		$randvars = '';
	}

	if (strpos($code,'//end randomization') !== false) {
		$toparse = substr($code, strpos($code,'//end randomization'), strpos($code,'//end stored')-strpos($code,'//end randomization'));
		$toparse = trim(substr($toparse, strpos($toparse,"\n")));
	} else {
		$toparse = substr($code, 0, strpos($code,'//end stored'));
	}
	$hinttext = array();
	preg_match_all('/\$hinttext\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
	foreach ($matches as $m) {
		$hinttext[$m[1]] = $m[2];
	}
	$nhints = count($hinttext);

	if (preg_match('/anstypes/',$toparse)) {
		preg_match('/\$anstypes\s*=\s*"(.*)"/', $toparse, $matches);
		$qtypes = explode(',', $matches[1]);
		$nparts = count($qtypes);
		$qtol = array();
		$qtold = array();
		preg_match_all('/\$reltolerance\[(\d+)\]\s*=\s*(.*)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$qtol[$m[1]] = 'rel';
			$qtold[$m[1]] = $m[2];
		}
		preg_match_all('/\$abstolerance\[(\d+)\]\s*=\s*(.*)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$qtol[$m[1]] = 'abs';
			$qtold[$m[1]] = $m[2];
		}
		$partialcredit = array();
		preg_match_all('/\$partialcredit\[(\d+)\]\s*=\s*array\((.*)\)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$partialcredit[$m[1]] = explode(',', str_replace('"','',$m[2]));
		}
		$questions = array();
		preg_match_all('/\$questions\[(\d+)\]\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$questions[$m[1]][$m[2]] = $m[3];
		}
		$feedbacktxt = array();
		preg_match_all('/\$feedbacktxt\[(\d+)\]\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$feedbacktxt[$m[1]][$m[2]] = $m[3];
		}
		foreach ($partialcredit as $k=>$v) {
			$qparts[$k] = count($v)/2;
		}
		foreach ($questions as $k=>$v) {
			$qparts[$k] = count($v);
		}
		$displayformat = array();
		preg_match_all('/\$displayformat\[(\d+)\]\s*=\s*(.*)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$displayformat[$m[1]] = $m[2];
		}
		$feedbacktxtdef = array();
		preg_match_all('/\$feedbacktxtdef\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$feedbacktxtdef[$m[1]] = $m[2];
		}
		$feedbacktxtessay = array();
		preg_match_all('/\$feedbacktxtessay\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$feedbacktxtessay[$m[1]] = $m[2];
		}
		$answer = array();
		preg_match_all('/\$answer\[(\d+)\]\s*=\s*(.*)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$answer[$m[1]] = str_replace('"','',$m[2]);
		}
		$answerboxsize = array();
		preg_match_all('/\$answerboxsize\[(\d+)\]\s*=\s*(.*)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$answerboxsize[$m[1]] = $m[2];
		}
		$displayformat = array();
		preg_match_all('/\$displayformat\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$displayformat[$m[1]] = $m[2];
		}
		$answerformat = array();
		preg_match_all('/\$answerformat\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$answerformat[$m[1]] = str_replace(array(',noval','noval'),'',$m[2]);
		}
		$noshuffle = array();
		preg_match_all('/\$noshuffle\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$noshuffle[$m[1]] = $m[2];
		}
		$scoremethod = array();
		preg_match_all('/\$scoremethod\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$scoremethod[$m[1]] = $m[2];
		}
		$variables = array();
		preg_match_all('/\$variables\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$variables[$m[1]] = $m[2];
		}
		//print_r(array($nparts, $qtypes, $qparts, $nhints, $displayformat, $questions, $feedbacktxt, $feedbacktxtdef, $feedbacktxtessay, $answer, $hinttext, $partialcredit, $qtol, $qtold, $answerboxsize, $displayformat, $scoremethod, $noshuffle, $keepcode, $randvars));
		return array($nparts, $qtypes, $qparts, $nhints, $displayformat, $questions, $feedbacktxt, $feedbacktxtdef, $feedbacktxtessay, $answer, $hinttext, $partialcredit, $qtol, $qtold, $answerboxsize, $displayformat, $answerformat, $scoremethod, $noshuffle, $variables, $keepcode, $randvars);
	} else {
		$qtol = '';
		$qtold = '';
		preg_match_all('/\$reltolerance\s*=\s*(.*)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$qtol = 'rel';
			$qtold = $m[1];
		}
		preg_match_all('/\$abstolerance\s*=\s*(.*)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$qtol = 'abs';
			$qtold = $m[1];
		}
		$partialcredit = array();
		preg_match_all('/\$partialcredit\s*=\s*array\((.*)\)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$partialcredit = explode(',',str_replace('"','',$m[1]));
		}
		$questions = array();
		preg_match_all('/\$questions\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$questions[$m[1]] = $m[2];
		}
		if (count($questions)>0) {
			$qparts = array(count($questions));
		} else if (count($partialcredit)>0) {
			$qparts = array(count($partialcredit)/2);
		} else {
			$qparts = array(0);
		}

		$feedbacktxt = array();
		preg_match_all('/\$feedbacktxt\[(\d+)\]\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$feedbacktxt[$m[1]] = $m[2];
		}

		$displayformat = '';
		preg_match_all('/\$displayformat\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$displayformat = $m[1];
		}
		$feedbacktxtdef = '';
		preg_match_all('/\$feedbacktxtdef\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$feedbacktxtdef = $m[1];
		}
		$feedbacktxtessay = '';
		preg_match_all('/\$feedbacktxtessay\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$feedbacktxtessay = $m[1];
		}
		$answer = '';
		preg_match_all('/\$answer\s*=\s*(.*)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$answer = str_replace('"','',$m[1]);
		}
		$answerboxsize = '';
		preg_match_all('/\$answerboxsize\s*=\s*(.*)/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$answerboxsize = $m[1];
		}
		$displayformat = '';
		preg_match_all('/\$displayformat\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$displayformat = $m[1];
		}
		$answerformat = '';
		preg_match_all('/\$answerformat\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$answerformat = str_replace(array(',noval','noval'),'',$m[1]);
		}
		$noshuffle = '';
		preg_match_all('/\$noshuffle\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$noshuffle = $m[1];
		}
		$scoremethod = '';
		preg_match_all('/\$scoremethod\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$scoremethod = $m[1];
		}
		$variables = '';
		preg_match_all('/\$variables\s*=\s*"(.*)"/', $toparse, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$variables = $m[1];
		}
		//print_r(array(1, array($type), $qparts, $nhints, array($displayformat), array($questions), array($feedbacktxt), array($feedbacktxtdef), array($feedbacktxtessay), array($answer), $hinttext, array($partialcredit), $qtol, $qtold, array($answerboxsize), array($displayformat), array($scoremethod), array($noshuffle), $keepcode, $randvars));

		return array(1, array($type), $qparts, $nhints, array($displayformat), array($questions), array($feedbacktxt), array($feedbacktxtdef), array($feedbacktxtessay), array($answer), $hinttext, array($partialcredit), array($qtol), array($qtold), array($answerboxsize), array($displayformat), array($answerformat), array($scoremethod), array($noshuffle), array($variables), $keepcode, $randvars);

	}

	/*
	$code = substr($code, 0, strpos($code,'//end stored'));
	eval(interpret('control',$type,$code));

	if (!isset($hinttext)) {
		$nhints = 0;
	} else {
		$nhints = count($hinttext);
	}

	if ($type=='multipart') {
		$qtypes = explode(',',$anstypes);
		$nparts = count($qtypes);
		$qparts = array();
		for ($n=0;$n<$nparts;$n++) {
			if ($qtypes[$n]=='number') {
				if (isset($reltolerance[$n])) {
					$qtol[$n] = 'rel';
					$qtold[$n] = $reltolerance[$n];
				}  else if (isset($abstolerance[$n])) {
					$qtol[$n] = 'abs';
					$qtold[$n] = $abstolerance[$n];
				}
				$qparts[$n] = count($partialcredit[$n])/2;
			} else if ($qtypes[$n]=='choices') {
				$qparts[$n] = count($questions[$n]);
			}
		}

		return array($nparts, $qtypes, $qparts, $nhints, $displayformat, $questions, $feedbacktxt, $feedbacktxtdef, $feedbacktxtessay, $answer, $hinttext, $partialcredit, $qtol, $qtold, $answerboxsize, $displayformat, $scoremethod, $noshuffle, $keepcode, $randvars);
	} else {
		if ($type=='number') {
			if (isset($reltolerance)) {
				$qtol[0] = 'rel';
				$qtold[0] = $reltolerance;
			}  else if (isset($abstolerance)) {
				$qtol[0] = 'abs';
				$qtold[0] = $abstolerance;
			}
			$qparts = array(count($partialcredit)/2);
		}else if ($type=='choices') {
			$qparts = array(count($questions));
		}else if ($type=='essay') {
			$qparts = array(0);
		}
		return array(1, array($type), $qparts, $nhints, array($displayformat), array($questions), array($feedbacktxt), array($feedbacktxtdef), array($feedbacktxtessay), array($answer), $hinttext, array($partialcredit), $qtol, $qtold, array($answerboxsize), array($displayformat), array($scoremethod), array($noshuffle), $keepcode, $randvars);
	}
	*/
}

//DB $query = "SELECT firstName,lastName FROM imas_users WHERE id='$userid'";
//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
//DB $row = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT firstName,lastName FROM imas_users WHERE id=:id");
$stm->execute(array(':id'=>$userid));
$row = $stm->fetch(PDO::FETCH_NUM);
$myname = $row[1].','.$row[0];

if (isset($_GET['id']) && $_GET['id']!='new') {
	$id = intval($_GET['id']);

	//DB $query = "SELECT imas_questionset.*,imas_users.groupid FROM imas_questionset,imas_users WHERE ";
	//DB $query .= "imas_questionset.ownerid=imas_users.id AND imas_questionset.id='{$_GET['id']}'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
	$query = "SELECT imas_questionset.*,imas_users.groupid FROM imas_questionset,imas_users WHERE ";
	$query .= "imas_questionset.ownerid=imas_users.id AND imas_questionset.id=:id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':id'=>$_GET['id']));
	$line = $stm->fetch(PDO::FETCH_ASSOC);

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
	foreach ($line as $k=>$v) {
		$line[$k] = str_replace('&','&amp;',$v);
	}

	$inlibs = array();

	if (isset($_GET['template'])) {
		//DB $query = "SELECT deflib,usedeflib FROM imas_users WHERE id='$userid'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB list($deflib,$usedeflib) = mysql_fetch_row($result);
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
				//DB $query = "SELECT imas_libraries.id,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.groupid ";
				//DB $query .= "FROM imas_libraries,imas_library_items WHERE imas_library_items.libid=imas_libraries.id ";
				//DB $query .= "AND imas_library_items.qsetid='{$_GET['id']}'";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
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
		$addmod = "Add";

		//DB $query = "SELECT qrightsdef FROM imas_users WHERE id='$userid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line['userights'] = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT qrightsdef FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$userid));
		$line['userights'] = $stm->fetchColumn(0);

	} else {
		if ($isadmin) {
			//DB $query = "SELECT DISTINCT libid FROM imas_library_items WHERE qsetid='{$_GET['id']}'";
			$stm = $DBH->prepare("SELECT DISTINCT libid FROM imas_library_items WHERE qsetid=:qsetid AND imas_library_items.deleted=0");
			$stm->execute(array(':qsetid'=>$_GET['id']));
		} else if ($isgrpadmin) {
			//DB $query = "SELECT DISTINCT ili.libid FROM imas_library_items AS ili,imas_users WHERE ili.ownerid=imas_users.id ";
			//DB $query .= "AND imas_users.groupid='$groupid' AND ili.qsetid='{$_GET['id']}'";
			$query = "SELECT DISTINCT ili.libid FROM imas_library_items AS ili,imas_users WHERE ili.ownerid=imas_users.id ";
			$query .= "AND imas_users.groupid=:groupid AND ili.qsetid=:qsetid AND ili.deleted=0";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':groupid'=>$groupid, ':qsetid'=>$_GET['id']));
		} else {
			//DB $query = "SELECT DISTINCT libid FROM imas_library_items WHERE qsetid='{$_GET['id']}' AND ownerid='$userid'";
			$stm = $DBH->prepare("SELECT DISTINCT libid FROM imas_library_items WHERE qsetid=:qsetid AND ownerid=:ownerid AND deleted=0");
			$stm->execute(array(':qsetid'=>$_GET['id'], ':ownerid'=>$userid));
		}
		//$query = "SELECT libid FROM imas_library_items WHERE qsetid='{$_GET['id']}' AND imas_library_items.ownerid='$userid'";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$inlibs[] = $row[0];
		}

		$locklibs = array();
		if (!$isadmin) {
			if ($isgrpadmin) {
				//DB $query = "SELECT ili.libid FROM imas_library_items AS ili,imas_users WHERE ili.ownerid=imas_users.id ";
				//DB $query .= "AND imas_users.groupid!='$groupid' AND ili.qsetid='{$_GET['id']}'";
				$query = "SELECT ili.libid FROM imas_library_items AS ili,imas_users WHERE ili.ownerid=imas_users.id ";
				$query .= "AND imas_users.groupid!=:groupid AND ili.qsetid=:qsetid AND ili.deleted=0";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':qsetid'=>$_GET['id'], ':groupid'=>$groupid));
			} else if (!$isadmin) {
				//DB $query = "SELECT libid FROM imas_library_items WHERE qsetid='{$_GET['id']}' AND imas_library_items.ownerid!='$userid'";
				$stm = $DBH->prepare("SELECT libid FROM imas_library_items WHERE qsetid=:qsetid AND imas_library_items.ownerid!=:userid AND deleted=0");
				$stm->execute(array(':qsetid'=>$_GET['id'], ':userid'=>$userid));
			}
			//$query = "SELECT libid FROM imas_library_items WHERE qsetid='{$_GET['id']}' AND imas_library_items.ownerid!='$userid'";
			//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$locklibs[] = $row[0];
			}
		}
		$addmod = "Modify";

		//DB $query = "SELECT count(imas_questions.id) FROM imas_questions,imas_assessments,imas_courses WHERE imas_assessments.id=imas_questions.assessmentid ";
		//DB $query .= "AND imas_assessments.courseid=imas_courses.id AND imas_questions.questionsetid='{$_GET['id']}' AND imas_courses.ownerid<>'$userid'";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $inusecnt = mysql_result($result,0,0);
		$query = "SELECT count(imas_questions.id) FROM imas_questions,imas_assessments,imas_courses WHERE imas_assessments.id=imas_questions.assessmentid ";
		$query .= "AND imas_assessments.courseid=imas_courses.id AND imas_questions.questionsetid=:questionsetid AND imas_courses.ownerid<>:userid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':questionsetid'=>$_GET['id'], ':userid'=>$userid));
		$inusecnt = $stm->fetchColumn(0);
	}

	if (count($inlibs)==0 && count($locklibs)==0) {
		$inlibs = array(0);
	}
	$inlibs = implode(",",$inlibs);
	$locklibs = implode(",",$locklibs);



	$code = $line['control'];
	$type = $line['qtype'];
	$qtext = $line['qtext'];

	if (strpos($code,'//end stored')===false) {
		echo 'This question is not formatted in a way that allows it to be editted with this tool.';
		exit;
	}

	$mathfuncs = array("sin","cos","tan","sinh","cosh","tanh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
	$allowedmacros = $mathfuncs;
	require_once("../assessment/interpret5.php");
	list($nparts, $qtype, $qparts, $nhints, $qdisp, $questions, $feedbacktxt, $feedbacktxtdef, $feedbacktxtessay, $answer, $hinttext, $partialcredit, $qtol, $qtold, $answerboxsize, $displayformat, $answerformat, $scoremethod, $qshuffle, $variables, $keepcode, $randvars) = getqvalues($code,$type);
	$partial = array();
	for ($n=0;$n<$nparts;$n++) {
		$partial[$n] = array();
		for ($i=0;$i<count($partialcredit[$n]);$i+=2) {
			if ($qtype[$n]=="number" || $qtype[$n]=="calculated" || $qtype[$n]=="numfunc") {
				$questions[$n][floor($i/2)] = $partialcredit[$n][$i];
				if ($partialcredit[$n][$i]==$answer[$n]) {
					$answerloc[$n] = floor($i/2);
				}
				$partial[$n][floor($i/2)] = $partialcredit[$n][$i+1];
			} else if ($qtype[$n]=="choices") {
				$partial[$n][$partialcredit[$n][$i]] = $partialcredit[$n][$i+1];
			}
		}
		if ($qtype[$n]=="number" || $qtype[$n]=="calculated" || $qtype[$n]=="numfunc") {
			$answer[$n] = $answerloc[$n];
		}
	}
	if ($nhints>0) { //strip out hints para
		$qtext = substr($qtext, strpos($qtext,'</p>')+4);
	}
} else {
	$myq = true;
	$id = 'new';
	//new question
	$nparts = 1;
	$qparts = array(4,4,4,4,4,4,4,4,4,4);
	$answer = array(0,0,0,0,0,0,0,0,0,0);
	$qdisp = array("vert","vert","vert","vert","vert","vert","vert","vert","vert","vert");
	$qshuffle = array("all","all","all","all","all","all","all","all","all","all");
	$qtype = array_fill(0,10,"choices");
	$displayformat = array();
	$answerformat = array();
	$scoremethod = array();
	$answerboxsize = array();
	$nhints = 1;
	$questions = array();
	$feedbacktxt = array();
	$feedbacktxtdef = array_fill(0,10,"Incorrect");
	$hinttext = array();
	$qtol = array_fill(0,1,"abs");
	$qtext = "";
	$keepcode = '';
	$randvars = '';

	$line['description'] = "Enter description here";
	//DB $query = "SELECT qrightsdef FROM imas_users WHERE id='$userid'";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB $line['userights'] = mysql_result($result,0,0);
	$stm = $DBH->prepare("SELECT qrightsdef FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>$userid));
	$line['userights'] = $stm->fetchColumn(0);
	$line['author'] = $myname;
	$line['deleted'] = 0;
	if (isset($_GET['aid']) && isset($sessiondata['lastsearchlibs'.$_GET['aid']])) {
		$inlibs = $sessiondata['lastsearchlibs'.Sanitize::onlyInt($_GET['aid'])];
	} else if (isset($sessiondata['lastsearchlibs'.$cid])) {
		//$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
		$inlibs = $sessiondata['lastsearchlibs'.$cid];
	} else {
		$inlibs = $userdeflib;
	}
	$locklibs='';

	$author = $myname;

	//DB $inlibssafe = "'".implode("','",explode(',',$inlibs))."'";
	$inlibssafe = implode(',', array_map('intval', explode(',',$inlibs)));
	if (!isset($_GET['id']) || isset($_GET['template'])) {
		//DB $query = "SELECT id,ownerid,userights,groupid FROM imas_libraries WHERE id IN ($inlibssafe)";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
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

	$addmod = "Add";

}
//DB $inlibssafe = "'".implode("','",explode(',',$inlibs))."'";
$inlibssafe = implode(',', array_map('intval', explode(',',$inlibs)));

$lnames = array();
if (substr($inlibs,0,1)==='0') {
	$lnames[] = "Unassigned";
}
//DB $query = "SELECT name FROM imas_libraries WHERE id IN ($inlibssafe)";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->query("SELECT name FROM imas_libraries WHERE id IN ($inlibssafe)");
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$lnames[] = $row[0];
}
$lnames = implode(", ",$lnames);



function prepd($v) {
	$v = str_replace('\\"','"',$v);
	return htmlentities($v, ENT_COMPAT | ENT_HTML401,"UTF-8", false );
}
$dispval = array("vert","horiz","select","inline","2column");
$displbl = array("Vertical list", "Horizontal list", "Pull-down", "Inline with text", "2 column");

$qtypeval = array("choices","number","calculated","numfunc","essay");
$qtypelbl = array("Multiple-choice","Numeric (integer/decimal)","Numeric (expression)","Algebraic","Essay");

$qtolval = array("abs","rel");
$qtollbl = array("absolute","relative");

$shuffleval = array("all","last","none");
$shufflelbl = array("no shuffle","shuffle all but last","shuffle all");

$ansfmtval = array("","fraction","reducedfraction","fracordec","mixednumber","mixednumberorimproper","scinot","nodecimal");
$ansfmtlbl = array("none","any fraction","reduced fraction","fraction or decimal","mixed number","mixed number or fraction","scientific notation","no decimal values");

$useeditor = "text,popuptxt";

$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/codemirror/codemirror-compressed.js"></script>';
$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/codemirror/imathas.js"></script>';
$placeinhead .= '<link rel="stylesheet" href="'.$imasroot.'/javascript/codemirror/codemirror_min.css">';
$placeinhead .= '<style type="text/css">
  .txted {
    padding-left: 1px;
    padding-right: 1px;
    margin-left: 0px;
    }
    .choicetbl {
    white-space: nowrap;
    }
   .CodeMirror {font-size: medium;border: 1px solid #ccc; height: auto;}
	.CodeMirror-scroll {min-height:70px; max-height:600px;}
	.CodeMirror-selectedtext {color: #ffffff !important;background-color: #3366AA;}
	.CodeMirror-focused .CodeMirror-selected {background: #3366AA;}
	.CodeMirror-selected {background: #666666;}
 </style>';

$flexwidth = true;
require("../header.php");

if (isset($_GET['aid'])) {
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"addquestions.php?aid=".Sanitize::onlyInt($_GET['aid'])."&cid=$cid\">Add/Remove Questions</a> &gt; Modify Questions</div>";

} else if (isset($_GET['daid'])) {
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"adddrillassess.php?daid=".Sanitize::encodeUrlParam($_GET['daid'])."&cid=$cid\">Add Drill Assessment</a> &gt; Modify Questions</div>";
} else {
	if ($_GET['cid']=="admin") {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../admin/admin2.php\">Admin</a>";
		echo "&gt; <a href=\"manageqset.php?cid=admin\">Manage Question Set</a> &gt; Modify Question</div>\n";
	} else {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> ";
		if ($cid>0) {
			echo "&gt; <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a>";
		}
		echo " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set</a> &gt; Modify Question</div>\n";
	}

}

echo '<div id="headermoddataset" class="pagetitle">';
echo "<h2>$addmod Tutorial Question</h2>\n";
echo '</div>';

if ($editmsg != '' || $_GET['id']!='new') {
	echo '<p>'.$editmsg;
	if ($id!='new') {
		echo ' <a href="moddataset.php?cid='.$cid.'&id='.$id.'">Open in the regular question editor</a>';
	} else {
		echo ' <a href="moddataset.php?cid='.$cid.'">Open in the regular question editor</a>';
	}
	echo '</p>';
}
if ($line['deleted']==1) {
	echo '<p class=noticetext>This question has been marked for deletion.  This might indicate there is an error in the question. ';
	echo 'It is recommended you discontinue use of this question when possible</p>';
}

if (isset($inusecnt) && $inusecnt>0) {
	echo '<p class=noticetext>This question is currently being used in ';
	if ($inusecnt>1) {
		echo Sanitize::encodeStringForDisplay($inusecnt).' assessments that are not yours.  ';
	} else {
		echo 'one assessment that is not yours.  ';
	}
	echo 'In consideration of the other users, if you want to make changes other than minor fixes to this question, consider creating a new version of this question instead.  </p>';

}
if (isset($_GET['qid'])) {
	echo "<p><a href=\"moddataset.php?id=".Sanitize::encodeUrlParam($_GET['id'])."&cid=$cid&aid=".Sanitize::onlyInt($_GET['aid'])."&template=true&makelocal=".Sanitize::encodeUrlParam($_GET['qid'])."\">Template this question</a> for use in this assessment.  ";
	echo "This will let you modify the question for this assessment only without affecting the library version being used in other assessments.</p>";
}
if (!$myq) {
	echo "<p>This question is not set to allow you to modify the code.  You can only view the code and make additional library assignments</p>";
}

?>

<script type="text/javascript">
function changenparts(el) {
	var np = el.value;
	for (var i=0;i<10;i++) {
		if (i<np) {
			document.getElementById("partwrapper"+i).style.display="";
		} else {
			document.getElementById("partwrapper"+i).style.display="none";
		}
	}
	if (np==1) {
		document.getElementById("anstipsingle").style.display="";
		document.getElementById("anstipmult").style.display="none";
	} else {
		document.getElementById("anstipsingle").style.display="none";
		document.getElementById("anstipmult").style.display="";
	}
}
function changeqparts(n,el) {
	var np = el.value;
	for (var i=0;i<6;i++) {
		if (i<np) {
			document.getElementById("qc"+n+"-"+i).style.display="";
		} else {
			document.getElementById("qc"+n+"-"+i).style.display="none";
		}
	}
}
function changehparts(el) {
	var np = el.value;
	for (var i=0;i<4;i++) {
		if (i<np) {
			document.getElementById("hintwrapper"+i).style.display="";
		} else {
			document.getElementById("hintwrapper"+i).style.display="none";
		}
	}
}

function changeqtype(n,el) {
	var qt = el.value;
	document.getElementById("qti"+n+"mc").style.display="none";
	document.getElementById("qti"+n+"num").style.display="none";
	document.getElementById("qti"+n+"calc").style.display="none";
	document.getElementById("qti"+n+"func").style.display="none";
	document.getElementById("qc"+n+"-def").style.display="none";
	$('#essayopts'+n).hide();
	//document.getElementById("qti"+n+"mc").style.display="";
	if (qt=='choices') {
		$('#essay'+n+'wrap').hide();
		$('.hasparts'+n).show();
		document.getElementById("qti"+n+"mc").style.display="";
		document.getElementById("choicelbl"+n).innerHTML = "Choice";
	} else if (qt=='number') {
		$('#essay'+n+'wrap').hide();
		$('.hasparts'+n).show();
		document.getElementById("qti"+n+"num").style.display="";
		document.getElementById("qc"+n+"-def").style.display="";
		document.getElementById("choicelbl"+n).innerHTML = "Answer";
	} else if (qt=='calculated') {
		$('#essay'+n+'wrap').hide();
		$('.hasparts'+n).show();
		document.getElementById("qti"+n+"calc").style.display="";
		document.getElementById("qc"+n+"-def").style.display="";
		document.getElementById("choicelbl"+n).innerHTML = "Answer";
	} else if (qt=='numfunc') {
		$('#essay'+n+'wrap').hide();
		$('.hasparts'+n).show();
		document.getElementById("qti"+n+"func").style.display="";
		document.getElementById("qc"+n+"-def").style.display="";
		document.getElementById("choicelbl"+n).innerHTML = "Answer";
	} else if (qt=='essay') {
		$('#essay'+n+'wrap').show();
		$('.hasparts'+n).hide();
		$('#essayopts'+n).show();
	}

}

function popuptxtsave() {
	var txt = tinyMCE.get('popuptxt').getContent();
	if (txt.substring(0,3)=='<p>' && txt.slice(-4)=='</p>' && txt.match(/<p>/g).length==1) {
		txt = txt.substring(3,txt.length - 4);
	}
	$('#'+popupedid).val(txt);
	GB_hide();
}
var rubricbase, lastrubricpos=null, popupedid;
function popupeditor(elid) {
	var width = 900;
	popupedid = elid;
	$('#GB_window').show();
	tinyMCE.get('popuptxt').setContent($('#'+elid).val());
	$('#GB_caption').mousedown(function(evt) {
		rubricbase = {left:evt.pageX, top: evt.pageY};
		$("body").bind('mousemove',rubricmousemove);
		$("body").mouseup(function(event) {
			var p = $('#GB_window').position();
			lastrubricpos.left = p.left;
			lastrubricpos.top = p.top;
			$("body").unbind('mousemove',rubricmousemove);
			$(this).unbind(event);
		});
	});
	var de = document.documentElement;
	var w = self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
	var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
	document.getElementById("GB_window").style.width = width + "px";

	if ($("#GB_window").outerHeight() > h - 30) {
		document.getElementById("GB_window").style.height = (h-30) + "px";
	}
	document.getElementById("GB_window").style.left = ((w - width)/2)+"px";
	lastrubricpos = {
		left: ($(window).width() - $("#GB_window").outerWidth())/2,
		top: $(window).scrollTop() + ((window.innerHeight ? window.innerHeight : $(window).height()) - $("#GB_window").outerHeight())/2,
		scroll: $(window).scrollTop()
	};
	document.getElementById("GB_window").style.top = lastrubricpos.top+"px";

}
function rubricmousemove(evt) {
	$('#GB_window').css('left', (evt.pageX - rubricbase.left) + lastrubricpos.left)
	.css('top', (evt.pageY - rubricbase.top) + lastrubricpos.top);
	evt.preventDefault();
	return false;
}
function rubrictouchmove(evt) {
	var touch = evt.originalEvent.changedTouches[0] || evt.originalEvent.touches[0];

	$('#GB_window').css('left', (touch.pageX - rubricbase.left) + lastrubricpos.left)
	.css('top', (touch.pageY - rubricbase.top) + lastrubricpos.top);
	evt.preventDefault();

	return false;
}
var randvarEditor;
function setupRandvarEditor() {
  randvarEditor = CodeMirror.fromTextArea(document.getElementById("randvars"), {
	lineNumbers: true,
	matchBrackets: true,
	autoCloseBrackets: true,
	mode: "text/x-imathas",
	smartIndent: true,
	lineWrapping: true,
	indentUnit: 2,
	tabSize: 2,
	styleSelectedText:true
      });
};
var keepcodeEditor;
function setupKeepcodeEditor() {
  keepcodeEditor = CodeMirror.fromTextArea(document.getElementById("keepcode"), {
	lineNumbers: true,
	matchBrackets: true,
	autoCloseBrackets: true,
	mode: "text/x-imathas",
	smartIndent: true,
	lineWrapping: true,
	indentUnit: 2,
	tabSize: 2,
	styleSelectedText:true
      });
};
</script>

<form enctype="multipart/form-data" method=post action="modtutorialq.php?process=true<?php
	if (isset($cid)) {
		echo "&cid=$cid";
	}
	if (isset($_GET['aid'])) {
		echo "&aid=".Sanitize::onlyInt($_GET['aid']);
	}
	if (isset($_GET['id']) && !isset($_GET['template'])) {
		echo "&id=" . Sanitize::onlyInt($_GET['id']);
	}
	if (isset($_GET['template'])) {
		echo "&templateid=" . Sanitize::onlyInt($_GET['id']);
	}
	if (isset($_GET['makelocal'])) {
		echo "&makelocal=" . Sanitize::encodeUrlParam($_GET['makelocal']);
	}
	if ($frompot==1) {
		echo "&frompot=1";
	}
?>">

<p>
Description:<BR>
<textarea cols=60 rows=4 name=description <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo Sanitize::encodeStringForDisplay($line['description']);?></textarea>
</p>
<p>
Author: <?php echo Sanitize::encodeStringForDisplay($line['author']); ?> <input type="hidden" name="author" value="<?php echo Sanitize::encodeStringForDisplay($author); ?>">
</p>
<p>
<?php
if (!isset($line['ownerid']) || isset($_GET['template']) || $line['ownerid']==$userid || ($line['userights']==3 && $line['groupid']==$groupid) || $isadmin || ($isgrpadmin && $line['groupid']==$groupid)) {
	echo "Use Rights <select name=userights>\n";
	echo "<option value=\"0\" ";
	if ($line['userights']==0) {echo "SELECTED";}
	echo ">Private</option>\n";
	echo "<option value=\"2\" ";
	if ($line['userights']==2) {echo "SELECTED";}
	echo ">Allow use, use as template, no modifications</option>\n";
	echo "<option value=\"3\" ";
	if ($line['userights']==3) {echo "SELECTED";}
	echo ">Allow use by all and modifications by group</option>\n";
	echo "<option value=\"4\" ";
	if ($line['userights']==4) {echo "SELECTED";}
	echo ">Allow use and modifications by all</option>\n";
}
?>
</select>
</p>
<script>
var curlibs = '<?php echo Sanitize::encodeStringForJavascript($inlibs);?>';
var locklibs = '<?php echo Sanitize::encodeStringForJavascript($locklibs);?>';
function libselect() {
	window.open('libtree.php?libtree=popup&cid=<?php echo $cid;?>&selectrights=1&libs='+curlibs+'&locklibs='+locklibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
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
	document.getElementById("libnames").innerHTML = libn;
}
</script>
<p>
My library assignments: <span id="libnames"><?php echo Sanitize::encodeStringForDisplay($lnames);?></span><input type=hidden name="libs" id="libs" size="10" value="<?php echo Sanitize::encodeStringForDisplay($inlibs);?>">
<input type=button value="Select Libraries" onClick="libselect()">
</p>

<?php
if (trim($randvars)=='') {
	echo '<p><a href="#" onclick="$(this).parent().hide();$(\'#randvarswrapper\').show();setupRandvarEditor();return false;">Add random variables</a></p>';
	echo '<div id="randvarswrapper" style="display:none;">';
} else {
	echo '<div id="randvarswrapper">';
	echo '<script type="text/javascript">$(function() {setupRandvarEditor();});</script>';
}
?>
<p>Define here any random variables you want to use in the answers, feedback, or question text below. This is also the place to do any
calculations you'll need to display simplified values below. <a href="#" onclick="$('#randvarsexamples').show();return false;">Example</a></p>
<div id="randvarsexamples" style="display:none;">
<p>Example: Suppose we wanted to ask a Numeric expression randomized question like: Add 2/3 + 3/5</p>
<table>
<tr><td><code>$d1,$d2 = diffrands(3,7,2)</code></td><td> Pick two different random values for the denominators</br>
<tr><td><code>$n1 = rand(1,$d1-1) where (gcd($n1,$d1)==1)</code></td><td>Pick a numerator that is relatively prime with the denominator</br>
<tr><td><code>$n2 = rand(1,$d2-1) where (gcd($n2,$d2)==1)</code></td><td>ditto</br>
<tr><td><code>$fans = makereducedfraction($n1*$d2 + $n2*$d1, $d1*$d2)</code></td><td> Create a simplified fraction for the answer, which we can use below in the Answer spot</td></tr>
<tr><td><code>$f2 = makereducedfraction($n1+$n2, $d1+$d2)</code></td><td> We can create this simplified fraction for a misconception, but it's not necessary since students will never
  see this. You could just put ($n1+$n2)/($d1+$d2) in the Answer spot below.</td></tr>
</table>

</div>
<p><a href="#" onclick="window.open('<?php echo $imasroot;?>/help.php?section=writingquestions','Help','width='+(.35*screen.width)+',height='+(.7*screen.height)+',toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width*.6))">Function Reference</a> |
<a href="#" onclick="window.open('<?php echo $imasroot;?>/assessment/libs/libhelp.php','Help','width='+(.35*screen.width)+',height='+(.7*screen.height)+',toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width*.6))">Addon Macro Libraries Reference</a></p>

<textarea name="randvars" id="randvars" style="width: 100%"><?php echo Sanitize::encodeStringForDisplay($randvars);?></textarea>
</div>

<p>This question has
<?php
	writeHtmlSelect("nparts",range(1,10),range(1,10), $nparts,null,null,'onchange="changenparts(this)"');
?>
parts.</p>

<?php
for ($n=0;$n<10;$n++) {
	if (!isset($qparts[$n])) { $qparts[$n] = 4;}
	echo '<div id="partwrapper'.$n.'"';
	if ($n>=$nparts) {echo ' style="display:none;"';};
	echo '>';

	echo '<h4>Part '.($n).' Question</h4>';
	echo '<p>This part is ';
	writeHtmlSelect("qtype$n",$qtypeval,$qtypelbl, $qtype[$n], null, null, 'onchange="changeqtype('.$n.',this)"');

	echo ' with ';

	if ($qtype[$n]!='essay') { // if it has question parts
		echo '<span class="hasparts'.$n.'">';
	} else {
		echo '<span class="hasparts'.$n.'" style="display:none;">';
	}

	writeHtmlSelect("qparts$n",range(1,6),range(1,6), $qparts[$n],null,null,'onchange="changeqparts('.$n.',this)"');

	//choices
	echo '<span id="qti'.$n.'mc" ';
	if (isset($qtype[$n]) && $qtype[$n]!='choices') {echo ' style="display:none;"';};
	echo '> choices.  Display those ';
	writeHtmlSelect("qdisp$n",$dispval,$displbl, $qdisp[$n]);
	echo '. Shuffle: ';
	writeHtmlSelect("qshuffle$n",$shuffleval,$shufflelbl, $qshuffle[$n]);
	echo '</span>';
	//numeric
	echo '<span id="qti'.$n.'num" ';
	if ($qtype[$n]!='number') {echo ' style="display:none;"';};
	echo '> values that will receive feedback. Use a(n) ';
	writeHtmlSelect("qtol$n",$qtolval,$qtollbl, $qtol[$n]);

	echo ' tolerance of <input autocomplete="off" name="tol'.$n.'" type="text" size="5" value="'.((isset($qtold[$n]) && trim($qtold[$n])!='')?Sanitize::encodeStringForDisplay($qtold[$n]):0.001).'"/>.';
	echo ' Box size: <input autocomplete="off" name="numboxsize'.$n.'" type="text" size="2" value="'.(isset($answerboxsize[$n])?Sanitize::encodeStringForDisplay($answerboxsize[$n]):5).'"/>.';
	echo '</span>';

	//calc
	echo '<span id="qti'.$n.'calc" ';
	if ($qtype[$n]!='calculated') {echo ' style="display:none;"';};
	echo '> numeric expressions that will receive feedback. Use a(n) ';
	writeHtmlSelect("funcqtol$n",$qtolval,$qtollbl, $qtol[$n]);
	echo ' tolerance of <input autocomplete="off" name="functol'.$n.'" type="text" size="5" value="'.((isset($qtold[$n]) && trim($qtold[$n])!='')?Sanitize::encodeStringForDisplay($qtold[$n]):0.001).'"/>.';
	echo ' Box size: <input autocomplete="off" name="funcboxsize'.$n.'" type="text" size="2" value="'.(isset($answerboxsize[$n])?Sanitize::encodeStringForDisplay($answerboxsize[$n]):20).'"/>.';
	echo ' Answer format: ';// <select name="answerformat'.$n.'" type="text" size="5" value="'.(isset($variables[$n])?$variables[$n]:'x').'"/>.';
	writeHtmlSelect("answerformat$n",$ansfmtval,$ansfmtlbl, ($qtype[$n]=='calculated'?$answerformat[$n]:""));
	echo '</span>';

	//func
	echo '<span id="qti'.$n.'func" ';
	if ($qtype[$n]!='numfunc') {echo ' style="display:none;"';};
	echo '> algebraic expressions that will receive feedback. Use a(n) ';
	writeHtmlSelect("funcqtol$n",$qtolval,$qtollbl, $qtol[$n]);
	echo ' tolerance of <input autocomplete="off" name="functol'.$n.'" type="text" size="5" value="'.((isset($qtold[$n]) && trim($qtold[$n])!='')?Sanitize::encodeStringForDisplay($qtold[$n]):0.001).'"/>.';
	echo ' Box size: <input autocomplete="off" name="funcboxsize'.$n.'" type="text" size="2" value="'.(isset($answerboxsize[$n])?Sanitize::encodeStringForDisplay($answerboxsize[$n]):20).'"/>.';
	echo ' Variables: <input autocomplete="off" name="variables'.$n.'" type="text" size="5" value="'.(isset($variables[$n])?Sanitize::encodeStringForDisplay($variables[$n]):'x').'"/>.';
	echo '</span>';

	echo '</span>'; // end question parts span
	//TODO:  Add essay question options

	echo '<span id="essayopts'.$n.'" ';
	if ($qtype[$n]!='essay') {echo ' style="display:none;"';};
	echo '> <input autocomplete="off" name="essayrows'.$n.'" type="text" size="2" value="'.(isset($answerboxsize[$n])?Sanitize::encodeStringForDisplay($answerboxsize[$n]):3).'"/> rows. ';
	echo '<input type="checkbox" name="useeditor'.$n.'" ';
	if (isset($displayformat[$n]) && $displayformat[$n]=='editor') {
		echo 'checked="checked"';
	}
	echo '/> Use editor.  ';
	echo '<input type="checkbox" name="takeanything'.$n.'" ';
	if (isset($scoremethod[$n]) && $scoremethod[$n]=='takeanything') {
		echo 'checked="checked"';
	}
	echo '/> Give credit for any answer.  ';


	echo '</span>';
	echo '</p>';

	if ($qtype[$n]!='essay') { // if it has question parts
		echo '<div class="hasparts'.$n.'">';
	} else {
		echo '<div class="hasparts'.$n.'" style="display:none;">';
	}
	echo '<table class="choicetbl"><thead><tr><th>Correct</th><th id="choicelbl'.$n.'">'.(($qtype[$n]=='choices')?"Choice":"Answer").'</th><th>Feedback</th><th>Partial Credit<br/>(0-1)</th></tr></thead><tbody>';
	for ($i=0;$i<6;$i++) {
		echo '<tr id="qc'.$n.'-'.$i.'" ';
		if ($i>=$qparts[$n]) {echo ' style="display:none;"';};
		echo '><td><input type="radio" name="ans'.$n.'" value="'.$i.'" ';
		if (($qtype[$n]=='choices' && $i==$answer[$n]) || ($qtype[$n]!='choices' && isset($partial[$n][$i]) && $partial[$n][$i]==1)) {echo 'checked="checked"';}
		echo '/></td>';
		echo '<td><input autocomplete="off" id="txt'.$n.'-'.$i.'" name="txt'.$n.'-'.$i.'" type="text" size="60" value="'.(isset($questions[$n][$i])?Sanitize::encodeStringForDisplay($questions[$n][$i]):"").'"/><input type="button" class="txted" value="E" onclick="popupeditor(\'txt'.$n.'-'.$i.'\')"/></td>';
		echo '<td><input autocomplete="off" id="fb'.$n.'-'.$i.'" name="fb'.$n.'-'.$i.'" type="text" size="60" value="'.(isset($feedbacktxt[$n][$i])?Sanitize::encodeStringForDisplay($feedbacktxt[$n][$i]):"").'"/><input type="button" class="txted" value="E" onclick="popupeditor(\'fb'.$n.'-'.$i.'\')"/></td>';
		echo '<td><input autocomplete="off" id="pc'.$n.'-'.$i.'" name="pc'.$n.'-'.$i.'" type="text" size="3" value="'.(isset($partial[$n][$i])?Sanitize::encodeStringForDisplay($partial[$n][$i]):"").'"/></td>';

		echo '</tr>';
	}
	echo '<tr id="qc'.$n.'-def" ';
	if ($qtype[$n]!="number" && $qtype[$n]!="numfunc" && $qtype[$n]!="calculated") {echo ' style="display:none;"';};
	echo '><td colspan="4">Default feedback for incorrect answers: ';
	echo '<input autocomplete="off" id="fb'.$n.'-def" name="fb'.$n.'-def" type="text" size="60" value="'.(isset($feedbacktxtdef[$n])?Sanitize::encodeStringForDisplay($feedbacktxtdef[$n]):"").'"/><input type="button" class="txted" value="E" onclick="popupeditor(\'fb'.$n.'-def\')"/></td></tr>';
	echo '</tbody></table>';
	echo '</div>'; //end hasparts holder div
	echo '<div id="essay'.$n.'wrap" ';
	if ($qtype[$n]!='essay') {echo ' style="display:none;"';};
	echo '> Feedback to show: <br/>';
	echo '<textarea id="essay'.$n.'-fb" name="essay'.$n.'-fb" cols="80" rows="4">';
	if (isset($feedbacktxtessay[$n])) { echo Sanitize::encodeStringForDisplay($feedbacktxtessay[$n]);}
	echo '</textarea><input type="button" class="txted" value="E" onclick="popupeditor(\'essay'.$n.'-fb\')"/>';
	echo '</div>'; //end essaywrap div
	echo '</div>'; //end partwrapper div
}

echo '<h4>Hints</h4>';
echo '<p>This question has ';
writeHtmlSelect("nhints",range(0,4),range(0,4), $nhints,null,null,'onchange="changehparts(this)"');
echo 'hints.</p>';
for ($n=0;$n<4;$n++) {
	echo '<p id="hintwrapper'.$n.'"';
	if ($n>=$nhints) {echo ' style="display:none;"';};
	echo '>Hint '.($n+1).':';
	echo '<input autocomplete="off" id="hint'.$n.'" name="hint'.$n.'" type="text" size="80" value="'.(isset($hinttext[$n])?Sanitize::encodeStringForDisplay($hinttext[$n]):"").'"/><input type="button" class="txted" value="E" onclick="popupeditor(\'hint'.$n.'\')"/></p>';
}

if (trim($keepcode)=='') {
	echo '<p><a href="#" onclick="$(this).parent().hide();$(\'#keepcodewrapper\').show();setupKeepcodeEditor();return false;">Add additional code</a></p>';
	echo '<div id="keepcodewrapper" style="display:none;">';
} else {
	echo '<div id="keepcodewrapper">';
	echo '<script type="text/javascript">$(function() {setupKeepcodeEditor();});</script>';
}
?>
<p>Here you can override or extend the default $requiretimes or $answerformat values, or define additional code needed for
the question text, like creating graphs.</p>
<textarea name="keepcode" id="keepcode" style="width: 100%"><?php echo Sanitize::encodeStringForDisplay($keepcode);?></textarea>
</div>
<?php

echo '<h4>Question Text</h4>';
echo '<p>In the question text, enter <span id="anstipsingle" ';
if ($nparts!=1) {echo 'style="display:none;" ';}
echo '><b>$answerbox</b> to place the question list into the question.  Enter <b>$feedback</b> to indicate where the feedback should be displayed.</span> <span id="anstipmult" ';
if ($nparts==1) {echo 'style="display:none;" ';}
echo '><b>$answerbox[0]</b> to place the question list for Part 0, <b>$answerbox[1]</b> to place the question list for Part 1, and so on.  Similarly, ';
echo 'enter <b>$feedback[0]</b> to indicate where the feedback for Part 0 should be displayed, and so on.</span></p>';

?>

<div class=editor>
	<textarea cols="60" rows="20" id="text" name="text" style="width: 100%"><?php echo Sanitize::encodeStringForDisplay($qtext);?></textarea>
</div>

<div class="editor" id="GB_window" style="display:none; position: absolute; height: auto;">
<div id="GB_caption" style="cursor:move;";><span style="float:right;"><span class="pointer clickable" onclick="GB_hide()">[X]</span></span> Edit Text</div>
<textarea cols="60" rows="6" id="popuptxt" name="popuptxt" style="width: 100%"></textarea>
<input type="button" value="Save" onclick="popuptxtsave()"/>
</div>
<p><input type="submit" value="Save and Test"/></p>
<p>&nbsp;</p>

</form>
<?php
	require("../footer.php");
?>
