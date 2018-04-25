<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

//boost operation time
@set_time_limit(0);
ini_set("max_input_time", "900");
ini_set("max_execution_time", "900");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

/*** master php includes *******/
require("../init.php");
require_once(__DIR__ . "/../includes/htmLawed.php");


/*** pre-html data manipulation, including function code *******/
function getsubinfo($items,$parent,$pre) {
	global $ids,$types,$names,$item,$parents;
	foreach($items as $k=>$anitem) {
		if (is_array($anitem)) {
			$ids[] = $parent.'-'.($k+1);
			$types[] = $pre."Block";
			//DB $names[] = stripslashes($anitem['name']);
			$names[] = $anitem['name'];
			$parents[] = $parent;
			getsubinfo($anitem['items'],$parent.'-'.($k+1),$pre.'--');
		} else {
			$ids[] = $anitem;
			$parents[] = $parent;
			$types[] = $pre.$item[$anitem]['type'];
			if (isset($item[$anitem]['name'])) {
				$names[] = $item[$anitem]['name'];
			} else {
				$names[] = $item[$anitem]['title'];
			}
		}
	}
}

$newqcnt = 0;
$updateqcnt = 0;
function additem($itemtoadd,$item,$questions,$qset) {

	global $DBH,$newlibs;
	global $userid, $userights, $cid, $missingfiles, $newqcnt, $updateqcnt, $sourceinstall;
	$mt = microtime();
	if ($item[$itemtoadd]['type'] == "Assessment") {
		//add assessment.  set $typeid
		$settings = explode("\n",$item[$itemtoadd]['settings']);
		foreach ($settings as $set) {
			$pair = explode('=',$set);
			$item[$itemtoadd][$pair[0]] = $pair[1];
		}
		$setstoadd = explode(',','name,summary,intro,avail,startdate,enddate,reviewdate,timelimit,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,password,cntingb,minscore,showcat,showhints,isgroup,allowlate,exceptionpenalty,noprint,groupmax,endmsg,eqnhelper,caltag,calrtag,showtips,deffeedbacktext,istutorial,viddata');
		$qarr = array();
		$valsets = ":courseid";
		$tosets = 'courseid';
		$qarr[':courseid'] = $cid;

		// Sanitize summary content.
		$item[$itemtoadd]['summary'] = Sanitize::incomingHtml($item[$itemtoadd]['summary']);

		// Sanitize endmsg content.
		if (isset($item[$itemtoadd]['endmsg'])) {
		    $data = unserialize($item[$itemtoadd]['endmsg']);
		    $data['commonmsg'] = Sanitize::incomingHtml($data['commonmsg']);
		    $data['def'] = Sanitize::incomingHtml($data['def']);
		    foreach (array_keys($data['msgs']) as $k) {
			$data['msgs'][$k] = Sanitize::incomingHtml($data['msgs'][$k]);
		    }
		    $item[$itemtoadd]['endmsg'] = serialize($data);
		}


		// Sanitize intro content.
		if (isset($item[$itemtoadd]['intro'])) {
		    $json = json_decode($item[$itemtoadd]['intro'], true);
		    if (null !== $json) {
			$json[0] = Sanitize::incomingHtml($json[0]);
			for ($i = 1; $i < count($json); $i++) {
			    $json[$i]['text'] = Sanitize::incomingHtml($json[$i]['text']);
			}
			$item[$itemtoadd]['intro'] = json_encode($json);
		    } else {
		        $item[$itemtoadd]['intro'] = Sanitize::incomingHtml($item[$itemtoadd]['intro']);
		    }
		}

		foreach ($setstoadd as $set) {
			if (isset($item[$itemtoadd][$set])) {
				$tosets .= ','.$set;
				//DB $valsets .= ',\''.$item[$itemtoadd][$set].'\'';
				$valsets .= ',:'.$set;
				$qarr[':'.$set] = $item[$itemtoadd][$set];
			}
		}
		$query = "INSERT INTO imas_assessments ($tosets) VALUES ($valsets)";
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);
		/*'{$item[$itemtoadd]['name']}','{$item[$itemtoadd]['summary']}','{$item[$itemtoadd]['intro']}',";
		$query .= "'{$item[$itemtoadd]['avail']}','{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}','{$item[$itemtoadd]['reviewdate']}','{$item[$itemtoadd]['timelimit']}',";
		$query .= "'{$item[$itemtoadd]['displaymethod']}','{$item[$itemtoadd]['defpoints']}','{$item[$itemtoadd]['defattempts']}',";
		$query .= "'{$item[$itemtoadd]['deffeedback']}','{$item[$itemtoadd]['defpenalty']}','{$item[$itemtoadd]['shuffle']}','{$item[$itemtoadd]['password']}','{$item[$itemtoadd]['cntingb']}',";
		*/
		//DB mysql_query($query) or die("error on: $query: " . mysql_error());
		//DB $typeid = mysql_insert_id();
		$typeid = $DBH->lastInsertId();

		//determine question to be added
		//$qtoadd = explode(',',$item[$itemtoadd]['questions']);  //FIX!!! can be ~ separated as well
		//FIX!!: check on format issues for grouped assessments.
		if (trim($item[$itemtoadd]['questions'])=='') {
			$qtoadd = array();
		} else {
			$qtoadd = preg_split('/[,~]/',$item[$itemtoadd]['questions']);
		}
		$allqids = array();
		foreach ($qtoadd as $qid) {
			if (strpos($qid,'|')!==FALSE) {continue;}
			//add question or get system id.
			//DB $query = "SELECT id,adddate FROM imas_questionset WHERE uniqueid='{$questions[$qid]['uqid']}' AND deleted=0";
			//DB $result = mysql_query($query) or die("error on: $query: " . mysql_error());
			//DB $questionexists = (mysql_num_rows($result)>0);
			$stm = $DBH->prepare("SELECT id,adddate,lastmoddate,deleted FROM imas_questionset WHERE uniqueid=:uniqueid");
			$stm->execute(array(':uniqueid'=>$questions[$qid]['uqid']));
			$questionexists = ($stm->rowCount()>0);
			//echo "Question ID ".$questions[$qid]['uqid'].($questionexists?" exists":" not found");
			if ($questionexists) {
				list($thisqsetid, $qadddate, $qlastmoddate, $qdeleted) = $stm->fetch(PDO::FETCH_NUM);
			}
			if ($questionexists && ($qdeleted==1 || $_POST['merge']==1 || $_POST['merge']==2)) {
				//DB $questions[$qid]['qsetid'] = mysql_result($result,0,0);
				$questions[$qid]['qsetid'] = $thisqsetid;
				$n = array_search($questions[$qid]['uqid'],$qset['uniqueid']);
				//DB if ($qset['lastmod'][$n]>mysql_result($result,0,1) || $_POST['merge']==2) {
				if (($qset['lastmod'][$n]>$qadddate && $qadddate>=$qlastmoddate) || $qdeleted==1 || $_POST['merge']==2) {
					$now = time();
					if (!empty($qset['qimgs'][$n])) {
						$hasimg = 1;
					} else {
						$hasimg = 0;
					}
					$qarr = array(':description'=>$qset['description'][$n], ':author'=>$qset['author'][$n], ':qtype'=>$qset['qtype'][$n],
						':control'=>$qset['control'][$n], ':qcontrol'=>$qset['qcontrol'][$n], ':qtext'=>$qset['qtext'][$n], ':answer'=>$qset['answer'][$n],
						':solution'=>$qset['solution'][$n], ':solutionopts'=>$qset['solutionopts'][$n], ':license'=>$qset['license'][$n],
						':ancestorauthors'=>$qset['ancestorauthors'][$n], ':otherattribution'=>$qset['otherattribution'][$n], ':extref'=>$qset['extref'][$n],
						':lastmoddate'=>$now, ':adddate'=>$now, ':hasimg'=>$hasimg, ':id'=>$questions[$qid]['qsetid']);

					$query = "UPDATE imas_questionset SET description=:description,";
					$query .= "author=:author,qtype=:qtype,";
					$query .= "control=:control,qcontrol=:qcontrol,";
					$query .= "qtext=:qtext,answer=:answer,";
					$query .= "solution=:solution,solutionopts=:solutionopts,";
					$query .= "license=:license,ancestorauthors=:ancestorauthors,otherattribution=:otherattribution,";
					$query .= "extref=:extref,lastmoddate=:lastmoddate,adddate=:adddate,hasimg=:hasimg";
					if ($qdeleted==1) {
						$query .= ",ownerid=:ownerid,deleted=0";
						$qarr[':ownerid'] = $userid;
					}
					$query .= " WHERE id=:id ";
					if (($_POST['merge']!=2 || $myrights<100) && $qdeleted==0) {
						$query .= "AND (ownerid=:ownerid OR userights>3)";
						$qarr[':ownerid'] = $userid;
					}
					$stm = $DBH->prepare($query);
					$stm->execute($qarr);
					if ($stm->rowCount()>0 && $hasimg==1) {
						//not efficient, but sufficient :)
						$stm = $DBH->prepare("DELETE FROM imas_qimages WHERE qsetid=:qsetid");
						$stm->execute(array(':qsetid'=>$questions[$qid]['qsetid']));
						$qimgs = explode("\n",trim($qset['qimgs'][$n]));
						foreach($qimgs as $qimg) {
							$p = explode(',',$qimg);
							if (count($p)<2) {continue;}
							if (count($p)<3) {
								$alttext = '';
							} else if (count($p)>3) {
								$alttext = implode(',', array_slice($p, 2));
							} else {
								$alttext = $p[2];
							}

							if (strpos($qset['qtext'][$n],'$'.$p[0])===false && strpos($qset['control'][$n],'$'.$p[0])===false) {
								//skip if not actually used in question
								continue;
							}
							$p[1] = filter_var($p[1], FILTER_SANITIZE_URL);
							$stm = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES (:qsetid, :var, :filename, :alt)");
							$stm->execute(array(':qsetid'=>Sanitize::onlyInt($questions[$qid]['qsetid']), ':var'=>Sanitize::stripHtmlTags($p[0]), ':filename'=>Sanitize::stripHtmlTags($p[1]), ':alt'=>Sanitize::stripHtmlTags($alttext)));
						}
					}
					if ($qdeleted==1) { //was deleted; need to add library items
						//try to undelete first
						$liblist = implode(',', array_map('intval', $newlibs));
						$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=0,lastmoddate=:now WHERE qsetid=:qsetid AND libid IN ($liblist)");
						$stm->execute(array(':qsetid'=>$questions[$qid]['qsetid'], ':now'=>$now));
						if ($stm->rowCount()==0) { //if none to undelete, add new
							foreach ($newlibs as $lib) {
								$stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,lastmoddate) VALUES (:libid, :qsetid, :ownerid, :now)");
								$stm->execute(array(':libid'=>$lib, ':qsetid'=>$questions[$qid]['qsetid'], ':ownerid'=>$userid, ':now'=>$now));
							}
						}
					}
					$updateqcnt++;
				}
			} else if ($questionexists) {
				//DB $questions[$qid]['qsetid'] = mysql_result($result,0,0);
				$questions[$qid]['qsetid'] = $thisqsetid;
			} else { //add question, and assign to default library
				$n = array_search($questions[$qid]['uqid'],$qset['uniqueid']);
				$importuid = '';
				$now = time();
				if (!empty($qset['qimgs'][$n])) {
					$hasimg = 1;
				} else {
					$hasimg = 0;
				}
				if (isset($qset['userights'][$n]) && isset($_POST['reuseqrights'])) {
					$thisqrights = $qset['userights'][$n];
				} else {
					$thisqrights = $userights;
				}
				if (isset($GLOBALS['mapusers']) && isset($GLOBALS['mapusers'][$sourceinstall][$qset['ownerid'][$n]])) {
					$thisownerid = $GLOBALS['mapusers'][$sourceinstall][$qset['ownerid'][$n]]['id'];
				} else {
					$thisownerid = $userid;
				}
				$query = "INSERT INTO imas_questionset (adddate,lastmoddate,uniqueid,ownerid,author,userights,description,qtype,control,qcontrol,qtext,answer,solution,solutionopts,extref,license,ancestorauthors,otherattribution,hasimg,importuid) ";
				$query .= "VALUES (:adddate, :lastmoddate, :uniqueid, :ownerid, :author, :userights, :description, :qtype, :control, :qcontrol, :qtext, :answer, :solution, :solutionopts, :extref, :license, :ancestorauthors, :otherattribution, :hasimg, :importuid)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':adddate'=>$now, ':lastmoddate'=>$qset['lastmod'][$n], ':uniqueid'=>$qset['uniqueid'][$n], ':ownerid'=>$thisownerid,
					':author'=>$qset['author'][$n], ':userights'=>$thisqrights, ':description'=>$qset['description'][$n], ':qtype'=>$qset['qtype'][$n],
					':control'=>$qset['control'][$n], ':qcontrol'=>$qset['qcontrol'][$n], ':qtext'=>$qset['qtext'][$n], ':answer'=>$qset['answer'][$n],
					':solution'=>$qset['solution'][$n], ':solutionopts'=>$qset['solutionopts'][$n], ':extref'=>$qset['extref'][$n], ':license'=>$qset['license'][$n],
					':ancestorauthors'=>$qset['ancestorauthors'][$n], ':otherattribution'=>$qset['otherattribution'][$n], ':hasimg'=>$hasimg, ':importuid'=>$importuid));
				$questions[$qid]['qsetid'] = $DBH->lastInsertId();
				if ($hasimg==1) {
					$qimgs = explode("\n",$qset['qimgs'][$n]);
					foreach($qimgs as $qimg) {
						$p = explode(',',$qimg);
						//DB $query = "INSERT INTO imas_qimages (qsetid,var,filename) VALUES ({$questions[$qid]['qsetid']},'{$p[0]}','{$p[1]}')";
						//DB mysql_query($query) or die("Import failed on $query: " . mysql_error());
						$stm = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename) VALUES (:qsetid, :var, :filename)");
						$stm->execute(array(':qsetid'=>$questions[$qid]['qsetid'], ':var'=>Sanitize::stripHtmlTags($p[0]), ':filename'=>Sanitize::stripHtmlTags($p[1])));
					}
				}
				foreach ($newlibs as $lib) {
					$stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,lastmoddate) VALUES (:libid, :qsetid, :ownerid, :now)");
					$stm->execute(array(':libid'=>$lib, ':qsetid'=>$questions[$qid]['qsetid'], ':ownerid'=>$userid, ':now'=>$now));
				}
				$newqcnt++;
			}
			$allqids[] = $questions[$qid]['qsetid'];

			//add question $questions[$qid].  assessmentid is $typeid
			//DB $query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category,regen,showans)";
			//DB $query .= "VALUES ($typeid,'{$questions[$qid]['qsetid']}','{$questions[$qid]['points']}','{$questions[$qid]['attempts']}','{$questions[$qid]['penalty']}','{$questions[$qid]['category']}','{$questions[$qid]['regen']}','{$questions[$qid]['showans']}')";
			//DB mysql_query($query) or die("error on: $query: " . mysql_error());
			//DB $questions[$qid]['systemid'] = mysql_insert_id();
			$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category,regen,showans) ";
			$query .= "VALUES (:assessmentid, :questionsetid, :points, :attempts, :penalty, :category, :regen, :showans)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':assessmentid'=>$typeid, ':questionsetid'=>$questions[$qid]['qsetid'], ':points'=>$questions[$qid]['points'],
				':attempts'=>$questions[$qid]['attempts'], ':penalty'=>$questions[$qid]['penalty'], ':category'=>$questions[$qid]['category'],
				':regen'=>$questions[$qid]['regen'], ':showans'=>$questions[$qid]['showans']));
			$questions[$qid]['systemid'] = $DBH->lastInsertId();
		}

		//resolve any includecodefrom links
		$qidstoupdate = array();
		if (count($allqids)>0) {
			$qidstocheck = implode(',', array_map('intval', $allqids));
			//look up any refs to UIDs
			//DB $query = "SELECT id,control,qtext FROM imas_questionset WHERE id IN ($qidstocheck) AND (control LIKE '%includecodefrom(UID%' OR qtext LIKE '%includeqtextfrom(UID%')";
			//DB $result = mysql_query($query) or die("error on: $query: " . mysql_error());

			$stm = $DBH->query("SELECT id,control,qtext FROM imas_questionset WHERE id IN ($qidstocheck) AND (control LIKE '%includecodefrom(UID%' OR qtext LIKE '%includeqtextfrom(UID%')");
			$includedqs = array();
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$qidstoupdate[] = $row[0];
				if (preg_match_all('/includecodefrom\(UID(\d+)\)/',$row[1],$matches,PREG_PATTERN_ORDER) >0) {
					$includedqs = array_merge($includedqs,$matches[1]);
				}
				if (preg_match_all('/includeqtextfrom\(UID(\d+)\)/',$row[2],$matches,PREG_PATTERN_ORDER) >0) {
					$includedqs = array_merge($includedqs,$matches[1]);
				}
			}
		}
		if (count($qidstoupdate)>0) {
			//lookup backrefs
			$includedbackref = array();
			if (count($includedqs)>0) {
				$includedlist = implode(',', array_map('intval',$includedqs));  //known decimal values from above
				//DB $query = "SELECT id,uniqueid FROM imas_questionset WHERE uniqueid IN ($includedlist)";
				//DB $result = mysql_query($query) or die("Query failed : $query"  . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$stm = $DBH->query("SELECT id,uniqueid FROM imas_questionset WHERE uniqueid IN ($includedlist)");
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$includedbackref[$row[1]] = $row[0];
				}
			}
			$updatelist = implode(',', array_map('intval', $qidstoupdate));
			//DB $query = "SELECT id,control,qtext FROM imas_questionset WHERE id IN ($updatelist)";
			//DB $result = mysql_query($query) or die("error on: $query: " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->query("SELECT id,control,qtext FROM imas_questionset WHERE id IN ($updatelist)");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				//DB $control = addslashes(preg_replace('/includecodefrom\(UID(\d+)\)/e','"includecodefrom(".$includedbackref["\\1"].")"',$row[1]));
				//DB $qtext = addslashes(preg_replace('/includeqtextfrom\(UID(\d+)\)/e','"includeqtextfrom(".$includedbackref["\\1"].")"',$row[2]));
				$control = preg_replace_callback('/includecodefrom\(UID(\d+)\)/', function($matches) use ($includedbackref) {
						return "includecodefrom(".$includedbackref[$matches[1]].")";
					}, $row[1]);
				$qtext = preg_replace_callback('/includeqtextfrom\(UID(\d+)\)/', function($matches) use ($includedbackref) {
						return "includeqtextfrom(".$includedbackref[$matches[1]].")";
					}, $row[2]);
				//DB $query = "UPDATE imas_questionset SET control='$control',qtext='$qtext' WHERE id={$row[0]}";
				//DB mysql_query($query) or die("error on: $query: " . mysql_error());
				$stm2 = $DBH->prepare("UPDATE imas_questionset SET control=:control,qtext=:qtext WHERE id=:id");
				$stm2->execute(array(':control'=>$control, ':qtext'=>$qtext, ':id'=>Sanitize::onlyInt($row[0])));
			}
		}

		//clean up any unassigned library items that are now assigned
		$stm = $DBH->prepare("UPDATE imas_library_items as A JOIN imas_library_items as B on A.qsetid=B.qsetid SET A.deleted=1,A.lastmoddate=:now WHERE A.libid=0 AND A.deleted=0 AND B.libid>0 AND B.deleted=0");
		$stm->execute(array(':now'=>$now));

		//recreate itemorder
		//$item[$itemtoadd]['questions'] = preg_replace("/(\d+)/e",'$questions[\\1]["systemid"]',$item[$itemtoadd]['questions']);
		if (trim($item[$itemtoadd]['questions'])=='') {
			$qs = array();
		} else {
			$qs = explode(',',$item[$itemtoadd]['questions']);
		}
		$newqorder = array();
		foreach ($qs as $q) {
			if (strpos($q,'~')===FALSE) {
				$newqorder[] = $questions[$q]["systemid"];
			} else {
				$newsub = array();
				$subs = explode('~',$q);
				if (strpos($subs[0],'|')!==false) {
					$newsub[] = $subs[0];
					array_shift($subs);
				}
				foreach($subs as $subq) {
					$newsub[] = $questions[$subq]["systemid"];
				}
				$newqorder[] = implode('~',$newsub);
			}
		}
		//DB $itemorder = addslashes(implode(',',$newqorder));
		$itemorder = implode(',',$newqorder);
		//write itemorder to db
		//DB $query = "UPDATE imas_assessments SET itemorder='$itemorder' WHERE id=$typeid";
		//DB mysql_query($query) or die("error on: $query: " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$typeid));
	} else if ($item[$itemtoadd]['type'] == "Forum") {
		$settings = explode("\n",$item[$itemtoadd]['settings']);

		// Sanitize description content.
		if (isset($item[$itemtoadd]['description'])) {
			$item[$itemtoadd]['description'] = Sanitize::incomingHtml($item[$itemtoadd]['description']);
		}

		// Sanitize postinstr content.
		if (isset($item[$itemtoadd]['postinstr'])) {
			$item[$itemtoadd]['postinstr'] = Sanitize::incomingHtml($item[$itemtoadd]['postinstr']);
		}

		// Sanitize replyinstr content.
		if (isset($item[$itemtoadd]['replyinstr'])) {
			$item[$itemtoadd]['replyinstr'] = Sanitize::incomingHtml($item[$itemtoadd]['replyinstr']);
        }

		foreach ($settings as $set) {
			$pair = explode('=',$set);
			$item[$itemtoadd][$pair[0]] = $pair[1];
		}
		//DB $query = "INSERT INTO imas_forums (name,description,courseid,avail,startdate,enddate,postby,replyby,defdisplay,points,cntingb,settings)";
		//DB $query .= "VALUES ('{$item[$itemtoadd]['name']}','{$item[$itemtoadd]['summary']}','$cid','{$item[$itemtoadd]['avail']}','{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}','{$item[$itemtoadd]['postby']}','{$item[$itemtoadd]['replyby']}','{$item[$itemtoadd]['defdisplay']}','{$item[$itemtoadd]['points']}','{$item[$itemtoadd]['cntingb']}','{$item[$itemtoadd]['settings']}')";
		//DB mysql_query($query) or die("error on: $query: " . mysql_error());
		//DB $typeid = mysql_insert_id();
		$query = "INSERT INTO imas_forums (name,description,courseid,avail,startdate,enddate,postby,replyby,defdisplay,points,cntingb,settings) ";
		$query .= "VALUES (:name, :description, :courseid, :avail, :startdate, :enddate, :postby, :replyby, :defdisplay, :points, :cntingb, :settings)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':name'=>$item[$itemtoadd]['name'], ':description'=>$item[$itemtoadd]['summary'], ':courseid'=>$cid,
			':avail'=>$item[$itemtoadd]['avail'], ':startdate'=>$item[$itemtoadd]['startdate'], ':enddate'=>$item[$itemtoadd]['enddate'],
			':postby'=>$item[$itemtoadd]['postby'], ':replyby'=>$item[$itemtoadd]['replyby'], ':defdisplay'=>$item[$itemtoadd]['defdisplay'],
			':points'=>$item[$itemtoadd]['points'], ':cntingb'=>$item[$itemtoadd]['cntingb'], ':settings'=>$item[$itemtoadd]['settings']));
		$typeid = $DBH->lastInsertId();
	} else if ($item[$itemtoadd]['type'] == "InlineText") {
		// Sanitize text content.
		if (isset($item[$itemtoadd]['text'])) {
			$item[$itemtoadd]['text'] = Sanitize::incomingHtml($item[$itemtoadd]['text']);
		}

		//DB $query = "INSERT INTO imas_inlinetext (courseid,title,text,avail,startdate,enddate,oncal,caltag)";
		//DB $query .= "VALUES ('$cid','{$item[$itemtoadd]['title']}','{$item[$itemtoadd]['text']}','{$item[$itemtoadd]['avail']}','{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}','{$item[$itemtoadd]['oncal']}','{$item[$itemtoadd]['caltag']}')";
		//DB mysql_query($query) or die("error on: $query: " . mysql_error());
		//DB $typeid = mysql_insert_id();
		$query = "INSERT INTO imas_inlinetext (courseid,title,text,avail,startdate,enddate,oncal,caltag) ";
		$query .= "VALUES (:courseid, :title, :text, :avail, :startdate, :enddate, :oncal, :caltag)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid, ':title'=>$item[$itemtoadd]['title'], ':text'=>$item[$itemtoadd]['text'],
			':avail'=>$item[$itemtoadd]['avail'], ':startdate'=>$item[$itemtoadd]['startdate'], ':enddate'=>$item[$itemtoadd]['enddate'],
			':oncal'=>$item[$itemtoadd]['oncal'], ':caltag'=>$item[$itemtoadd]['caltag']));
		$typeid = $DBH->lastInsertId();
		if (isset($item[$itemtoadd]['instrfiles']) && trim($item[$itemtoadd]['instrfiles'])!='') {
			$item[$itemtoadd]['instrfiles'] = explode("\n",$item[$itemtoadd]['instrfiles']);
			$fileorder = array();
			foreach ($item[$itemtoadd]['instrfiles'] as $fileinfo) {
				if (trim($fileinfo)==':::') {continue;} //bad file info
				//DB list($filename,$filedescr) = explode(':::',addslashes($fileinfo));
				list($filename,$filedescr) = explode(':::',$fileinfo);
				if (substr($filename,0,4)=='http') {
					$filename = filter_var($filename, FILTER_SANITIZE_URL);
				} else if (!file_exists("../course/files/$filename")) {
					$missingfiles[] = $filename;
				}

				//DB $query = "INSERT INTO imas_instr_files (description,filename,itemid) VALUES ";
				//DB $query .= "('$filedescr','$filename',$typeid)";
				//DB mysql_query($query) or die("error on: $query: " . mysql_error());
				//DB $fileorder[] = mysql_insert_id();
				$query = "INSERT INTO imas_instr_files (description,filename,itemid) VALUES ";
				$query .= "(:description, :filename, :itemid)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':description'=>$filedescr, ':filename'=>$filename, ':itemid'=>$typeid));
				$fileorder[] = $DBH->lastInsertId();
			}
			//DB $query = "UPDATE imas_inlinetext SET fileorder='".implode(',',$fileorder)."' WHERE id=$typeid";
			//DB mysql_query($query) or die("error on: $query: " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_inlinetext SET fileorder=:fileorder WHERE id=:id");
			$stm->execute(array(':id'=>$typeid, ':fileorder'=>implode(',',$fileorder)));
		}
	} else if ($item[$itemtoadd]['type'] == "LinkedText") {
		// Sanitize text content.
		if (isset($item[$itemtoadd]['text'])) {
			$item[$itemtoadd]['text'] = Sanitize::incomingHtml($item[$itemtoadd]['text']);
		}

		// Sanitize summary content.
		if (isset($item[$itemtoadd]['summary'])) {
			$item[$itemtoadd]['summary'] = Sanitize::incomingHtml($item[$itemtoadd]['summary']);
		}

		//DB $query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,avail,startdate,enddate,oncal,caltag,target)";
		//DB $query .= "VALUES ('$cid','{$item[$itemtoadd]['title']}','{$item[$itemtoadd]['summary']}','{$item[$itemtoadd]['text']}','{$item[$itemtoadd]['avail']}','{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}','{$item[$itemtoadd]['oncal']}','{$item[$itemtoadd]['caltag']}','{$item[$itemtoadd]['target']}')";
		//DB mysql_query($query) or die("error on: $query: " . mysql_error());
		//DB $typeid = mysql_insert_id();
		$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,avail,startdate,enddate,oncal,caltag,target) ";
		$query .= "VALUES (:courseid, :title, :summary, :text, :avail, :startdate, :enddate, :oncal, :caltag, :target)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid, ':title'=>$item[$itemtoadd]['title'], ':summary'=>$item[$itemtoadd]['summary'],
			':text'=>$item[$itemtoadd]['text'], ':avail'=>$item[$itemtoadd]['avail'], ':startdate'=>$item[$itemtoadd]['startdate'],
			':enddate'=>$item[$itemtoadd]['enddate'], ':oncal'=>$item[$itemtoadd]['oncal'], ':caltag'=>$item[$itemtoadd]['caltag'],
			':target'=>$item[$itemtoadd]['target']));
		$typeid = $DBH->lastInsertId();
	} else {
		return false;
	}

	//add item, set
	//DB $query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
	//DB $query .= "VALUES ('$cid','{$item[$itemtoadd]['type']}',$typeid)";
	//DB mysql_query($query) or die("error on: $query: " . mysql_error());
	//DB $item[$itemtoadd]['systemid'] = mysql_insert_id();
	$query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
	$query .= "VALUES (:courseid, :itemtype, :typeid)";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':itemtype'=>$item[$itemtoadd]['type'], ':typeid'=>$typeid));
	$item[$itemtoadd]['systemid'] = $DBH->lastInsertId();

	return ($item[$itemtoadd]['systemid']);
}

function parsefile($file) {
	$handle = fopen($file,"r");
	if (!$handle) {
		echo "eek!  can't open file";
		exit;
	}
	$itemcnt = -1;
	$qcnt = -1;
	$qscnt = -1;
	$initem = false;
	$text = '';
	$line = '';
	$item = array();
	$questions = array();
	$qset = array();

	while (!feof($handle)) {
		$line = rtrim(fgets($handle, 4096));
		switch ((string)$line) {
			case  "EXPORT DESCRIPTION":
				$desc = rtrim(fgets($handle, 4096));
				break;
			case  "INSTALLNAME":
				$sourceinstall = rtrim(fgets($handle, 4096));
				break;
			case  "EXPORT OWNERID":
				$ownerid = rtrim(fgets($handle, 4096));
				break;
			case  "ITEM LIST":
				$itemlist = rtrim(fgets($handle, 44096));
				break;
			case  "BEGIN ITEM":
				$itemcnt++;
				$initem = true;
				unset($part);
				break;
			case  "END ITEM":
				if (isset($part)) {
					$item[$curid][$part] = rtrim($text);
				}
				$initem = false;
				$text = '';
				unset($part);
				unset($curid);
				break;
			case  "ID":
				$curid = rtrim(fgets($handle, 4096));
				break;
			case  "TYPE":
			case  "TITLE":
			case  "NAME":
			case  "TEXT":
			case  "SUMMARY":  //note: use this for forum description
			case  "INTRO":
			case  "QUESTIONS":
			case  "STARTDATE":
			case  "ENDDATE":
			case  "POSTBY":
			case  "REPLYBY":
			case  "AVAIL":
			case  "REVIEWDATE":
			case  "INSTRFILES";
			case  "ONCAL":
			case  "CALTAG":
			case  "TARGET":
			case  "SETTINGS":
				if (isset($part)) {
					$item[$curid][$part] = rtrim($text);
				}
				$text = '';
				$part = strtolower($line);
				break;
			case  "BEGIN QUESTION":
				$qcnt++;
				$initem = true;
				unset($part);
				break;
			case  "END QUESTION":
				if (isset($part)) {
					$questions[$curqid][$part] = rtrim($text);
				}
				$initem = false;
				$text = '';
				unset($part);
				unset($curqid);
				break;
			case  "QID":
				$curqid = rtrim(fgets($handle, 4096));
				break;
			case  "UQID":
			case  "POINTS":
			case  "PENALTY":
			case  "ATTEMPTS":
			case  "REGEN":
			case  "SHOWANS":
			case  "CATEGORY":
				if (isset($part)) {
					$questions[$curqid][$part] = rtrim($text);
				}
				$text = '';
				$part = strtolower($line);
				break;
			case  "BEGIN QSET":
				$qscnt++;
				$initem = true;
				unset($part);
				break;
			case  "END QSET":
				if (isset($part)) {
					$qset[$part][$qscnt] = rtrim($text);
				}
				$initem = false;
				$text = '';
				unset($part);
				break;
			case  "DESCRIPTION":
			case  "UNIQUEID":
			case  "LASTMOD":
			case  "AUTHOR":
			case  'OWNERID':
			case  'USERIGHTS':
			case  "CONTROL":
			case  "QCONTROL":
			case  "QTEXT":
			case  "QTYPE":
			case  "SOLUTION":
			case  "SOLUTIONOPTS":
			case  "EXTREF":
			case  "LICENSE":
			case  "ANCESTORAUTHORS":
			case  "OTHERATTRIBUTION":
			case  "QIMGS":
			case  "ANSWER":
				if (isset($part)) {
					$qset[$part][$qscnt] = rtrim($text);
				}
				$text = '';
				$part = strtolower($line);
				break;
			default:
				if (isset($part) && $initem) {
					$text .= $line . "\n";
				}
				break;
		}
	}

	return array($desc,$itemlist,$item,$questions,$qset,$sourceinstall,$ownerid);
}

function copysub($items,$parent,&$addtoarr) {
	global $checked,$blockcnt,$item,$questions,$qset;
	foreach ($items as $k=>$anitem) {
		if (is_array($anitem)) {
			if (array_search($parent.'-'.($k+1),$checked)!==FALSE) { //copy block
				$newblock = array();
				$newblock['name'] = $anitem['name'];
				$newblock['id'] = $blockcnt;
				$blockcnt++;
				$newblock['startdate'] = $anitem['startdate'];
				$newblock['enddate'] = $anitem['enddate'];
				$newblock['avail'] = $anitem['avail'];
				$newblock['SH'] = $anitem['SH'];
				$newblock['colors'] = $anitem['colors'];
				$newblock['public'] = $anitem['public'];
				$newblock['fixedheight'] = $anitem['fixedheight'];
				$newblock['items'] = array();
				copysub($anitem['items'],$parent.'-'.($k+1),$newblock['items']);
				$addtoarr[] = $newblock;
			} else {
				copysub($anitem['items'],$parent.'-'.($k+1),$addtoarr);
			}
		} else {
			if (array_search($anitem,$checked)!==FALSE) {
				$addtoarr[] = additem($anitem,$item,$questions,$qset);
			}
		}
	}
}


 //set some page specific variables and counters
$cid = Sanitize::courseId($_GET['cid']);
$overwriteBody = 0;
$body = "";
$pagetitle = $installname . " Import Course Items";
$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Import Course Items</div>\n";

//data manipulation here

	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
 	$overwriteBody = 1;
	$body = "You need to access this page from a menu link";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION




	//FORM HAS BEEN POSTED, STEP 3 DATA MANIPULATION
	if (isset($_POST['process'])) {
		$filename = rtrim(dirname(__FILE__), '/\\') .'/import/' . Sanitize::sanitizeFilenameAndCheckBlacklist($_POST['filename']);
		list ($desc,$itemlist,$item,$questions,$qset,$sourceinstall,$ownerid) = parsefile($filename);

		$userights = $_POST['userights'];
		$newlibs = explode(",",array_map('intval',$_POST['libs']));
		//DB $item = array_map('addslashes_deep', $item);
		//DB $questions = array_map('addslashes_deep', $questions);
		//DB $qset = array_map('addslashes_deep', $qset);

		$checked = $_POST['checked'];
		//DB $query = "SELECT blockcnt,itemorder FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $blockcnt = mysql_result($result,0,0);
		//DB $ciditemorder = unserialize(mysql_result($result,0,1));
		$stm = $DBH->prepare("SELECT blockcnt,itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));

		list($blockcnt,$itemorder) = $stm->fetch(PDO::FETCH_NUM);
		$ciditemorder = unserialize($itemorder);
		$items = unserialize($itemlist);
		$newitems = array();
		$missingfiles = array();

		//DB mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
		$DBH->beginTransaction();

		copysub($items,'0',$newitems);

		array_splice($ciditemorder,count($ciditemorder),0,$newitems);
		//DB $itemorder = addslashes(serialize($ciditemorder));
		$itemorder = serialize($ciditemorder);
		//DB $query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
		//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':id'=>$cid));

		//DB mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
		$DBH->commit();
		$rqp = Sanitize::randomQueryStringParam();
		if (count($missingfiles)>0) {
			echo "These files pointed to by inline text items were not found and will need to be reuploaded:<br/>";
			foreach ($missingfiles as $file) {
				echo "$file <br/>";
			}

			echo "<p><a href=\"$imasroot/course/course.php?cid=$cid\" >Done</a></p>";
		} else if ($myrights==100) {
			echo "<p>$updateqcnt questions updated, $newqcnt questions added.</p>";

			echo "<p><a href=\"$imasroot/course/course.php?cid=$cid\" >Done</a></p>";
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid&r=$rqp");
		}
		exit;
	} elseif ($_FILES['userfile']['name']!='') { //STEP 2 DATA MANIPULATION
		$page_fileErrorMsg = "";
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . Sanitize::sanitizeFilenameAndCheckBlacklist($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			$page_fileHiddenInput = "<input type=hidden name=\"filename\" value=\"".Sanitize::encodeStringForDisplay(basename($uploadfile))."\" />\n";
		} else {
			echo "<p>Error uploading file!</p>\n";
			echo Sanitize::encodeStringForDisplay($_FILES["userfile"]['error']);
			exit;
		}
		list ($desc,$itemlist,$item,$questions,$qset,$sourceinstall,$ownerid) = parsefile($uploadfile);
		if (!isset($desc)) {
			$page_fileErrorMsg .=  "This does not appear to be a course items file.  It may be ";
			$page_fileErrorMsg .=  "a question or library export.\n";
		}

		$items = unserialize($itemlist);
		$ids = array();
		$types = array();
		$names = array();
		$parents = array();
		getsubinfo($items,'0','');

	}
}

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>
<script type="text/javascript">

var curlibs = '0';
function libselect() {
	window.open('../course/libtree.php?libtree=popup&selectrights=1&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
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
function chkgrp(frm, arr, mark) {
	  var els = frm.getElementsByTagName("input");
	  for (var i = 0; i < els.length; i++) {
		  var el = els[i];
		  if (el.type=='checkbox' && (el.id.indexOf(arr+'.')==0 || el.id.indexOf(arr+'-')==0 || el.id==arr)) {
	     	       el.checked = mark;
		  }
	  }
	}
</script>

<?php echo $curBreadcrumb; ?>
	<div id="headerimportitems" class="pagetitle"><h2>Import Course Items</h2></div>
	<form id="qform" enctype="multipart/form-data" method=post action="importitems.php?cid=<?php echo $cid ?>">

<?php
	if ($_FILES['userfile']['name']=='') {
?>
		<p>This page will allow you to import course items previously exported from
		this site or another site running this software.</p>

		<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
		<span class=form>Import file: </span>
		<span class=formright><input name="userfile" type="file" /></span><br class=form>
		<div class=submit><input type=submit value="Submit"></div>

<?php
	} else {

		if (strlen($page_fileErrorMsg)>1) {
			echo $page_fileErrorMsg;
		} else {
			echo $page_fileHiddenInput;
?>
		<h3>Package Description</h3>
		<?php echo Sanitize::encodeStringForDisplay($desc); ?>


		<p>Some questions (possibly older or different versions) may already exist on the system.
		With these questions, do you want to:<br/>
			<input type=radio name=merge value="1" CHECKED>Update existing questions (if allowed),
			<input type=radio name=merge value="-1">Keep existing questions
			<?php if ($myrights==100) {
				echo '<input type=radio name=merge value="2">Force update';
			}?>
		</p>
		<p>
			For Added Questions, Set Question Use Rights to
			<select name=userights>
				<option value="0">Private</option>
				<option value="2" SELECTED>Allow use, use as template, no modifications</option>
				<option value="3">Allow use by all and modifications by group</option>
				<option value="4">Allow use and modifications by all</option>
			</select>
			<br/><input type="checkbox" name="reuseqrights" checked /> Use rights in import, if available.
			
		</p>
		<p>

		Assign Added Questions to library:
		<span id="libnames">Unassigned</span>
		<input type=hidden name="libs" id="libs"  value="0">
		<input type=button value="Select Libraries" onClick="libselect()"><br>

		Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>


<?php
			if (count($ids)>0) {
?>
		<table cellpadding=5 class=gb>
		<thead>
			<tr><th></th><th>Type</th><th>Title</th></tr>
		</thead>
		<tbody>
<?php
				$alt=0;
				for ($i = 0 ; $i<(count($ids)); $i++) {
					if ($alt==0) {echo "		<tr class=even>"; $alt=1;} else {echo "		<tr class=odd>"; $alt=0;}
			echo '<td>';
			if (strpos($types[$i],'Block')!==false) {
				echo "<input type=checkbox name='checked[]' value='".Sanitize::encodeStringForDisplay($ids[$i])."' id='{$parents[$i]}' checked=checked ";
				echo "onClick=\"chkgrp(this.form, '".Sanitize::encodeStringForJavascript($ids[$i])."', this.checked);\" ";
				echo '/>';
			} else {
				$boxid = $parents[$i].'.'.$ids[$i];
				echo "<input type=checkbox name='checked[]' value='".Sanitize::encodeStringForDisplay($ids[$i])."' id='" . Sanitize::encodeStringForDisplay($boxid). "' checked=checked ";
				echo '/>';
			}
?>
				</td>
				<td><?php echo Sanitize::encodeStringForDisplay($types[$i]); ?></td>
				<td><?php echo Sanitize::encodeStringForDisplay($names[$i]); ?></td>
			</tr>

<?php
				}
?>
		</tbody>
		</table>
		<p><input type=submit name="process" value="Import Items"></p>
<?php
			}
		}
?>

<?php
	}
	echo "</form>\n";
}
require("../footer.php");

?>
