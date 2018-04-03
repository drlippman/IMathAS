<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

//boost operation time
@set_time_limit(0);
ini_set("max_input_time", "3600");
ini_set("max_execution_time", "3600");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

/*** master php includes *******/
require("../init.php");
require_once("../includes/filehandler.php");


/*** pre-html data manipulation, including function code *******/
function printlist($parent) {
	global $parents,$names;
	$children = array_keys($parents,$parent);
	foreach ($children as $child) {
		if (!in_array($child,$parents)) { //if no children
			echo "<li><span class=dd>-</span><input type=checkbox name=\"libs[]\" value=\"".Sanitize::encodeStringForDisplay($child)."\" CHECKED>".Sanitize::encodeStringForDisplay($names[$child])."</li>";
		} else { // if children
			echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle(".Sanitize::encodeStringForJavascript($child).")\"><span class=btn id=\"b$child\">+</span> ";
			echo "</span><input type=checkbox name=\"libs[]\" value=\"".Sanitize::encodeStringForDisplay($child)."\" CHECKED>";
			echo "<span class=hdr onClick=\"toggle(".Sanitize::encodeStringForJavascript($child).")\">".Sanitize::encodeStringForDisplay($names[$child])."</span>";
			echo "<ul class=hide id=$child>\n";
			printlist($child);
			echo "</ul></li>\n";
		}
	}
}

function parseqs($file,$touse,$rights) {
	function writeq($qd,$rights,$qn) {
		global $DBH,$userid,$isadmin,$updateq,$newq,$isgrpadmin;
		$now = time();
		$toundel = array();
		$stm = $DBH->prepare("SELECT id,adddate,lastmoddate,deleted FROM imas_questionset WHERE uniqueid=:uniqueid");
		$stm->execute(array(':uniqueid'=>$qd['uqid']));
		if ($stm->rowCount()>0) {
			list($qsetid, $adddate, $lastmoddate, $deleted) = $stm->fetch(PDO::FETCH_NUM);
			$exists = true;
		} else {
			$exists = false;
		}

		if ($exists && ($deleted==1 || $_POST['merge']==1 || $_POST['merge']==2)) {
			if (($qd['lastmod']>$adddate && $lastmoddate<=$adddate) || $deleted==1 || $_POST['merge']==2) { //only update if changed unless forced
				if (!empty($qd['qimgs'])) {
					$hasimg = 1;
				} else {
					$hasimg = 0;
				}

				$query = "UPDATE imas_questionset SET description=:description,author=:author,";
				$query .= "qtype=:qtype,control=:control,qcontrol=:qcontrol,qtext=:qtext,";
				$query .= "answer=:answer,extref=:extref,license=:license,ancestorauthors=:ancestorauthors,otherattribution=:otherattribution,";
				$query .= "solution=:solution,solutionopts=:solutionopts,adddate=:adddate,lastmoddate=:lastmoddate,hasimg=:hasimg,deleted=0 WHERE id=:id";
				$qarr = array(':description'=>$qd['description'], ':author'=>$qd['author'], ':qtype'=>$qd['qtype'],
					':control'=>$qd['control'], ':qcontrol'=>$qd['qcontrol'], ':qtext'=>$qd['qtext'], ':answer'=>$qd['answer'],
					':extref'=>$qd['extref'], ':license'=>$qd['license'], ':ancestorauthors'=>$qd['ancestorauthors'],
					':otherattribution'=>$qd['otherattribution'], ':solution'=>$qd['solution'], ':solutionopts'=>$qd['solutionopts'],
					':adddate'=>$now, ':lastmoddate'=>$now, ':hasimg'=>$hasimg, ':id'=>$qsetid);
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);

				if ($stm->rowCount()>0) {
					$updateq++;
					if (!empty($qd['qimgs'])) {
						//not efficient, but sufficient :)
						//DB $query = "DELETE FROM imas_qimages WHERE qsetid='$qsetid'";
						//DB mysql_query($query) or die("Import failed on $query: " . mysql_error());
						$stm = $DBH->prepare("DELETE FROM imas_qimages WHERE qsetid=:qsetid");
						$stm->execute(array(':qsetid'=>$qsetid));
						$qimgs = explode("\n",trim($qd['qimgs']));
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

							if (strpos($qd['qtext'],'$'.$p[0])===false && strpos($qd['qcontrol'],'$'.$p[0])===false) {
								//skip if not actually used in question
								continue;
							}
							//rehost image
							$newfn = rehostfile($p[1], 'qimages', $qsetid.'-');
							if ($newfn!==false) {
								$stm = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES (:qsetid, :var, :filename, :alt)");
								$stm->execute(array(':qsetid'=>$qsetid, ':var'=>$p[0], ':filename'=>$newfn, ':alt'=>$alttext));
							}
						}
					}
				}
			}
			return $qsetid;
		} else if ($exists) {
			return $qsetid;
		} else {
			$importuid = '';
			if ($qd['uqid']=='0') {
				$importuid = $qd['uqid'];
				$mt = microtime();
				$qd['uqid'] = substr($mt,11).substr($mt,2,1).str_pad($qn,5,0,STR_PAD_LEFT);
			}
			if (!empty($qd['qimgs'])) {
				$hasimg = 1;
			} else {
				$hasimg = 0;
			}
			if (isset($qd['userights']) && isset($_POST['reuseqrights'])) {
				$thisqrights = $qd['userights'];
			} else {
				$thisqrights = $rights;
			}
			if (isset($GLOBALS['mapusers']) && isset($GLOBALS['mapusers'][$sourceinstall][$qd['ownerid']])) {
				$thisownerid = $GLOBALS['mapusers'][$sourceinstall][$qd['ownerid']]['id'];
			} else {
				$thisownerid = $userid;
			}
			//DB $query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,userights,description,author,qtype,control,qcontrol,qtext,answer,solution,solutionopts,extref,license,ancestorauthors,otherattribution,hasimg,importuid) VALUES ";
			//DB $query .= "('{$qd['uqid']}',$now,$now,'$userid','$rights','{$qd['description']}','{$qd['author']}','{$qd['qtype']}','{$qd['control']}','{$qd['qcontrol']}','{$qd['qtext']}','{$qd['answer']}','{$qd['solution']}','{$qd['solutionopts']}','{$qd['extref']}','{$qd['license']}','{$qd['ancestorauthors']}','{$qd['otherattribution']}',$hasimg,$importuid)";
			//DB mysql_query($query) or die("Import failed on $query: " . mysql_error());
			$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,userights,description,author,qtype,control,qcontrol,qtext,answer,solution,solutionopts,extref,license,ancestorauthors,otherattribution,hasimg,importuid) VALUES ";
			$query .= "(:uniqueid, :adddate, :lastmoddate, :ownerid, :userights, :description, :author, :qtype, :control, :qcontrol, :qtext, :answer, :solution, :solutionopts, :extref, :license, :ancestorauthors, :otherattribution, :hasimg, :importuid)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':uniqueid'=>$qd['uqid'], ':adddate'=>$now, ':lastmoddate'=>$now, ':ownerid'=>$userid, ':userights'=>$thisqrights,
				':description'=>$qd['description'], ':author'=>$qd['author'], ':qtype'=>$qd['qtype'], ':control'=>$qd['control'], ':qcontrol'=>$qd['qcontrol'],
				':qtext'=>$qd['qtext'], ':answer'=>$qd['answer'], ':solution'=>$qd['solution'], ':solutionopts'=>$qd['solutionopts'], ':extref'=>$qd['extref'],
				':license'=>$qd['license'], ':ancestorauthors'=>$qd['ancestorauthors'], ':otherattribution'=>$qd['otherattribution'], ':hasimg'=>$hasimg, ':importuid'=>$importuid));
			$newq++;
			//DB $qsetid = mysql_insert_id();
			$qsetid = $DBH->lastInsertId();
			if (!empty($qd['qimgs'])) {
				$qimgs = explode("\n",$qd['qimgs']);
				foreach($qimgs as $qimg) {
					$p = explode(',',$qimg);
					//DB $query = "INSERT INTO imas_qimages (qsetid,var,filename) VALUES ($qsetid,'{$p[0]}','{$p[1]}')";
					//DB mysql_query($query) or die("Import failed on $query: " . mysql_error());
					$stm = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename) VALUES (:qsetid, :var, :filename)");
					$stm->execute(array(':qsetid'=>$qsetid, ':var'=>$p[0], ':filename'=>$p[1]));
				}
			}
			return $qsetid;
		}
	}
	$touse = explode(',',$touse);
	$qnum = -1;
	$part = '';
	if (!function_exists('gzopen')) {
		$handle = fopen($file,"r");
		$nogz = true;
	} else {
		$nogz = false;
		$handle = gzopen($file,"r");
	}
	$line = '';
	while ((!$nogz || !feof($handle)) && ($nogz || !gzeof($handle))) {
		if ($nogz) {
			$line = rtrim(fgets($handle, 4096));
		} else {
			$line = rtrim(gzgets($handle, 4096));
		}
		if ($line == "START QUESTION") {
			$part = '';
			if ($qnum>-1) {
				foreach($qdata as $k=>$val) {
					$qdata[$k] = rtrim($val);
				}
				if (in_array($qdata['qid'],$touse)) {
					$qid = writeq($qdata,$rights,$qnum);
					if ($qid!==false) {
						$qids[$qdata['qid']] = $qid;
					}
				}
				unset($qdata);
			}
			$qnum++;
			continue;
		} else if ($line == "DESCRIPTION") {
			$part = 'description';
			continue;
		} else if ($line == "QID") {
			$part = 'qid';
			continue;
		} else if ($line == "UQID") {
			$part = 'uqid';
			continue;
		} else if ($line == "LASTMOD") {
			$part = 'lastmod';
			continue;
		} else if ($line == "AUTHOR") {
			$part = 'author';
			continue;
		} else if ($line == "OWNERID") {
			$part = 'ownerid';
			continue;
		} else if ($line == "USERIGHTS") {
			$part = 'userights';
			continue;
		} else if ($line == "CONTROL") {
			$part = 'control';
			continue;
		} else if ($line == "QCONTROL") {
			$part = 'qcontrol';
			continue;
		} else if ($line == "QTEXT") {
			$part = 'qtext';
			continue;
		} else if ($line == "QTYPE") {
			$part = 'qtype';
			continue;
		} else if ($line == "ANSWER") {
			$part = 'answer';
			continue;
		} else if ($line == "SOLUTION") {
			$part = 'solution';
			continue;
		} else if ($line == "SOLUTIONOPTS") {
			$part = 'solutionopts';
			continue;
		} else if ($line == "EXTREF") {
			$part = 'extref';
			continue;
		} else if ($line == "LICENSE") {
			$part = 'license';
			continue;
		} else if ($line == "ANCESTORAUTHORS") {
			$part = 'ancestorauthors';
			continue;
		} else if ($line == "OTHERATTRIBUTION") {
			$part = 'otherattribution';
			continue;
		} else if ($line == "QIMGS") {
			$part = 'qimgs';
			continue;
		} else {
			if ($part=="qtype") {
				$qdata['qtype'] .= $line;
			} else if ($qnum>-1) {
				$qdata[$part] .= $line . "\n";
			}
		}
	}
	if ($nogz) {
		fclose($handle);
	} else {
		gzclose($handle);
	}
	foreach($qdata as $k=>$val) {
		$qdata[$k] = rtrim($val);
	}
	if (in_array($qdata['qid'],$touse)) {
		$qid = writeq($qdata,$rights,$qnum);
		if ($qid!==false) {
			$qids[$qdata['qid']] = $qid;
		}
	}
	return $qids;
}

function parselibs($file) {
	if (!function_exists('gzopen')) {
		$handle = fopen($file,"r");
		$nogz = true;
	} else {
		$nogz = false;
		$handle = gzopen($file,"r");
	}
	if (!$handle) {
		echo "eek!  handle doesn't exist";
		exit;
	}
	$line = '';
	while (((!$nogz || !feof($handle)) && ($nogz || !gzeof($handle))) && $line!="START QUESTION") {
		if ($nogz) {
			$line = rtrim(fgets($handle, 4096));
		} else {
			$line = rtrim(gzgets($handle, 4096));
		}
		if ($line=="PACKAGE DESCRIPTION") {
			$dopackd = true;
			$packname = rtrim(fgets($handle, 4096));
		} else if ($line=="INSTALLNAME") {
			$dopackd = false;
			$sourceinstall = rtrim(fgets($handle, 4096));
		} else if ($line=="START LIBRARY") {
			$dopackd = false;
			$libid = -1;
		} else if ($line=="ID") {
			$libid = rtrim(fgets($handle, 4096));
		} else if ($line=="UID") {
			$unique[$libid] = rtrim(fgets($handle, 4096));
		} else if ($line=="LASTMODDATE") {
			$lastmoddate[$libid] = rtrim(fgets($handle, 4096));
		} else if ($line=="OWNERID") {
			$ownerid[$libid] = rtrim(fgets($handle, 4096));
		} else if ($line=="USERIGHTS") {
			$userights[$libid] = rtrim(fgets($handle, 4096));
		} else if ($line=="NAME") {
			if ($libid != -1) {
				$names[$libid] = rtrim(fgets($handle, 4096));
			}
		} else if ($line=="PARENT") {
			if ($libid != -1) {
				$parents[$libid]= rtrim(fgets($handle, 4096));
			}
		} else if ($line=="START LIBRARY ITEMS") {
			$libitemid = -1;
		} else if ($line=="LIBID") {
			$libitemid = rtrim(fgets($handle, 4096));
		} else if ($line=="QSETIDS") {
			if ($libitemid!=-1) {
				$libitems[$libitemid] = rtrim(fgets($handle, 4096));
			}
		} else if ($dopackd ==true) {
			$packname .= rtrim($line);
		}
	}
	if ($nogz) {
		fclose($handle);
	} else {
		gzclose($handle);
	}
	return array($packname,$names,$parents,$libitems,$unique,$lastmoddate,$ownerid,$userights,$sourceinstall);
}

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = $installname . " Import Questions";

	//CHECK PERMISSIONS AND SET FLAGS
if ($myrights < 100) {
 	$overwriteBody = 1;
	$body = "This page is only accessible to admins.";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
	$cid = (isset($_GET['cid'])) ? Sanitize::courseId($_GET['cid']) : "admin" ;
	$isadmin = true;
	$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"admin2.php\">Admin</a> &gt; Import Libraries</div>\n";

	//FORM HAS BEEN POSTED, STEP 3 DATA MANIPULATION
	if (isset($_POST['process'])) {
		$filename = rtrim(dirname(__FILE__), '/\\') .'/import/' . Sanitize::sanitizeFilenameAndCheckBlacklist($_POST['filename']);

		$libstoadd = array_map('intval',$_POST['libs']);

		list($packname,$names,$parents,$libitems,$unique,$lastmoddate,$ownerid,$userights,$sourceinstall) = parselibs($filename);
		//DB //need to addslashes before SQL insert
		//DB $names = array_map('addslashes_deep', $names);
		//DB $parents = array_map('addslashes_deep', $parents);
		//DB $libitems = array_map('addslashes_deep', $libitems);
		//DB $unique = array_map('addslashes_deep', $unique);
		//DB $lastmoddate = array_map('addslashes_deep', $lastmoddate);

		$root = Sanitize::onlyInt(trim($_POST['parent']));
        $librights = Sanitize::onlyInt(trim($_POST['librights']));
		$qrights = $_POST['qrights'];
		$touse = '';
		//write libraries
		foreach ($unique as $k=>$v) {
			$unique[$k] = preg_replace('/[^0-9\.]/','',$v);
		}
		$lookup = implode(',', $unique);
		// intval doesn't work on uniqueid since they're bigint
		// $lookup = implode(',', array_map('intval', $unique));

		//DB $query = "SELECT id,uniqueid,adddate,lastmoddate FROM imas_libraries WHERE uniqueid IN ($lookup)";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,uniqueid,adddate,lastmoddate,deleted FROM imas_libraries WHERE uniqueid IN ($lookup)");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$exists[$row[1]] = $row[0];
			$adddate[$row[0]] = $row[2];
			$lastmod[$row[0]] = $row[3];
			$deleted[$row[0]] = $row[4];
		}

		$mt = microtime();
		$updatel = 0;
		$newl = 0;
		$newli = 0;
		$updateq = 0;
		$newq = 0;

		//DB mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
		$DBH->beginTransaction();

		foreach ($libstoadd as $libid) {

			if ($parents[$libid]==0) {  //use given root parent
				$parent = $root;
			} else if (isset($libs[$parents[$libid]])) { //if parent has been set
				$parent = $libs[$parents[$libid]];
			} else { //otherwise, skip this library (skip children if parent not added)
				continue;
			}
			$now = time();

			if (isset($exists[$unique[$libid]]) && $deleted[$exists[$unique[$libid]]]==1) {
				//if lib is deleted, undelete it
				$stm = $DBH->prepare("UPDATE imas_libraries SET deleted=0,name=:name,adddate=:adddate,lastmoddate=:lastmoddate WHERE id=:id");
				$stm->execute(array(':name'=>$names[$libid], ':adddate'=>$now, ':lastmoddate'=>$now, ':id'=>$exists[$unique[$libid]]));
				if ($stm->rowCount()>0) {
					$newl++;
				}
			} else if (isset($exists[$unique[$libid]]) && $_POST['merge']==1) {
				//lib exists and we're updating it (if possible)
				if ($lastmoddate[$libid]>$adddate[$exists[$unique[$libid]]]) { //if library has changed
					$stm = $DBH->prepare("UPDATE imas_libraries SET name=:name,adddate=:adddate,lastmoddate=:lastmoddate WHERE id=:id");
					$stm->execute(array(':name'=>$names[$libid], ':adddate'=>$now, ':lastmoddate'=>$now, ':id'=>$exists[$unique[$libid]]));

					if ($stm->rowCount()>0) {
						$updatel++;
					}
				}
				$libs[$libid] = $exists[$unique[$libid]];
			} else if (isset($exists[$unique[$libid]])) {
				//if lib exists, use it
				$libs[$libid] = $exists[$unique[$libid]];
			} else {
				//if lib does not exist, add it
				if ($unique[$libid]==0 || (isset($exists[$unique[$libid]]) && $_POST['merge']==0)) {
					$unique[$libid] = substr($mt,11).substr($mt,2,2).str_pad($libid,4,0,STR_PAD_LEFT);
				}
				if (isset($userights[$libid]) && isset($_POST['reuselibrights'])) {
					$thislibrights = $userights[$libid];
				} else {
					$thislibrights = $librights;
				}
				if (isset($GLOBALS['mapusers']) && isset($GLOBALS['mapusers'][$sourceinstall][$ownerid[$libid]])) {
					$thisownerid = $GLOBALS['mapusers'][$sourceinstall][$ownerid[$libid]]['id'];
					$thisgroupid = $GLOBALS['mapusers'][$sourceinstall][$ownerid[$libid]]['groupid'];
				} else {
					$thisownerid = $userid;
					$thisgroupid = $groupid;
				}
				//DB $query = "INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,parent,groupid) VALUES ";
				//DB $query .= "('{$unique[$libid]}',$now,$now,'{$names[$libid]}','$userid','$librights','$parent','$groupid')";
				//DB mysql_query($query) or die("error on: $query: " . mysql_error());
				//DB $libs[$libid] = mysql_insert_id();
				$query = "INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,parent,groupid) VALUES ";
				$query .= "(:uniqueid, :adddate, :lastmoddate, :name, :ownerid, :userights, :parent, :groupid)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':uniqueid'=>$unique[$libid], ':adddate'=>$now, ':lastmoddate'=>$now, ':name'=>$names[$libid], ':ownerid'=>$thisownerid,
					':userights'=>$thislibrights, ':parent'=>$parent, ':groupid'=>$thisgroupid));
				$libs[$libid] = $DBH->lastInsertId();
				$newl++;
			}
			if (isset($libs[$libid])) {
				if ($touse=='') {$touse = $libitems[$libid];} else if (isset($libitems[$libid])) {$touse .= ','.$libitems[$libid];}
			}
		}

		//write questions, get qsetids
		$qids = parseqs($filename,$touse,$qrights);
		if (count($qids)>0) {
			//resolve any includecodefrom links
			$qidstocheck = implode(',', array_map('intval', $qids));
			$qidstoupdate = array();
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
			if (count($qidstoupdate)>0) {
				//lookup backrefs
				$includedbackref = array();
				$toundel = array();
				if (count($includedqs)>0) {
					$includedlist = implode(',', array_map('Sanitize::onlyInt', $includedqs));  //known decimal values from above
					//DB $query = "SELECT id,uniqueid FROM imas_questionset WHERE uniqueid IN ($includedlist)";
					//DB $result = mysql_query($query) or die("Query failed : $query"  . mysql_error());
					//DB while ($row = mysql_fetch_row($result)) {
					$stm = $DBH->query("SELECT id,uniqueid,deleted FROM imas_questionset WHERE uniqueid IN ($includedlist)");
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$includedbackref[$row[1]] = $row[0];
						if ($row[2]==1) {
							$toundel[] = $row[0];
						}
					}
				}
				if (count($toundel)>0) {
					$undellist = implode(',', $toundel);
					$stm = $DBH->query("UPDATE imas_questionset SET deleted=0 WHERE id IN ($undellist)");
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
					$stm2->execute(array(':control'=>$control, ':qtext'=>$qtext, ':id'=>$row[0]));
				}
			}


			//write imas library items, connecting libraries to items
			foreach ($libstoadd as $libid) {
				if (!isset($libs[$libid])) { $libs[$libid]=0;} //assign questions to unassigned if library is closed.  Shouldn't ever trigger
				$stm = $DBH->prepare("SELECT qsetid,deleted FROM imas_library_items WHERE libid=:libid");
				$stm->execute(array(':libid'=>$libs[$libid]));
				$existingli = array();
				$deletedli = array();
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					if ($row[1]==1) {
						$deletedli[] = $row[0];
					} else {
						$existingli[] = $row[0];
					}
				}

				$qidlist = explode(',',$libitems[$libid]);
				foreach ($qidlist as $qid) {
					if (isset($qids[$qid]) && (array_search($qids[$qid],$deletedli)!==false)) {
						$stm = $DBH->prepare("UPDATE imas_library_items SET ownerid=:ownerid,lastmoddate=:now,deleted=0 WHERE libid=:libid AND qsetid=:qsetid");
						$stm->execute(array(':libid'=>$libs[$libid], ':qsetid'=>$qids[$qid], ':ownerid'=>$userid, ':now'=>$now));
						$newli += count($deletedli);
					} else if (isset($qids[$qid]) && (array_search($qids[$qid],$existingli)===false)) {
						$stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,lastmoddate) VALUES (:libid, :qsetid, :ownerid, :now)");
						$stm->execute(array(':libid'=>$libs[$libid], ':qsetid'=>$qids[$qid], ':ownerid'=>$userid, ':now'=>$now));
						$newli++;
					}
				}
				unset($existingli);
			}
			//clean up any unassigned library items that are now assigned
			$stm = $DBH->prepare("UPDATE imas_library_items as A JOIN imas_library_items as B on A.qsetid=B.qsetid SET A.deleted=1,A.lastmoddate=:now WHERE A.libid=0 AND A.deleted=0 AND B.libid>0 AND B.deleted=0");
			$stm->execute(array(':now'=>$now));
		}

		//DB mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
		$DBH->commit();

		unlink($filename);
		$page_uploadSuccessMsg = "Import Successful.<br>\n";
		$page_uploadSuccessMsg .= "New Libraries: $newl.<br>";
		$page_uploadSuccessMsg .= "New Questions: $newq.<br>";
		$page_uploadSuccessMsg .= "Updated Libraries: $updatel.<br>";
		$page_uploadSuccessMsg .= "Updated Questions: $updateq.<br>";
		$page_uploadSuccessMsg .= "New Library items: $newli.<br>";
			$page_uploadSuccessMsg .=  "<a href=\"" . $GLOBALS['basesiteurl'] . "/admin/admin2.php\">Return to Admin page</a>";

	} elseif ($_FILES['userfile']['name']!='') { // STEP 2 DATA MANIPULATION
		$page_fileErrorMsg = "";
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . Sanitize::sanitizeFilenameAndCheckBlacklist($_FILES['userfile']['name']);

		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			$page_fileHiddenInput = "<input type=hidden name=\"filename\" value=\"".Sanitize::encodeStringForDisplay(basename($uploadfile))."\" />\n";
		} else {
			$page_fileErrorMsg .= "<p>Error uploading file!</p>\n";
		}

		list($packname,$names,$parents,$libitems,$unique,$lastmoddate,$ownerid,$userights,$sourceinstall) = parselibs($uploadfile);

		if (!isset($parents)) {
			$page_fileErrorMsg .=  "<p>This file does not appear to contain a library structure.  It may be a question set export. ";
			$page_fileErrorMsg .=  "Try the <a href=\"import.php?cid=$cid\">Import Question Set</a> page</p>\n";
		}

	}
}

$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/course/libtree.css\" type=\"text/css\" />";

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>

	<script type="text/javascript">
	var curlibs = '0';
	function libselect() {
		window.open('../course/libtree.php?libtree=popup&cid=<?php echo $cid ?>&selectrights=1&select=parent&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
	}
	function setlib(libs) {
		document.getElementById("parent").value = libs;
		curlibs = libs;
	}
	function setlibnames(libn) {
		document.getElementById("libnames").innerHTML = libn;
	}

	function toggle(id) {
		node = document.getElementById(id);
		button = document.getElementById('b'+id);
		if (node.className == "show") {
			node.className = "hide";
			button.innerHTML = "+";
		} else {
			node.className = "show";
			button.innerHTML = "-";
		}
	}
	</script>

<?php
	echo $curBreadcrumb;

	if (isset($_POST['process'])) { //STEP 3 DISPLAY
		echo $page_uploadSuccessMsg;
	} else { //STEP 1 or 2
?>
	<div id="headerimportlib" class="pagetitle"><h2>Import Question Libraries</h2></div>
	<form enctype="multipart/form-data" method=post action="importlib.php?cid=<?php echo $cid ?>">

<?php
		if ($_FILES['userfile']['name']=='') { //STEP 1 DISPLAY
?>
			<input type="hidden" name="MAX_FILE_SIZE" value="9000000" />
			<span class=form>Import file: </span>
			<span class=formright><input name="userfile" type="file" /></span><br class=form>
			<div class=submit><input type=submit value="Submit"></div>
<?php
		} else {  //STEP 2 DISPLAY

			if (strlen($page_fileErrorMsg)>1) {
				echo $page_fileErrorMsg;
			} else {
				echo $page_fileHiddenInput;
?>
			<p>This page will import entire questions libraries with heirarchy structure.
			To import specific questions into existing libraries, use the
			<a href="import.php?cid=<?php echo $cid ?>">Question Import</a> page
			</p>

<?php echo Sanitize::encodeStringForDisplay($packname); ?>

			<h3>Select Libraries to import</h3>
			<p>Note:  If a parent library is not selected, NONE of the children libraries will be added,
			regardless of whether they're checked or not
			</p>

			<p>
			For Added Questions, Set Question Use Rights to:
			<select name=qrights>
				<option value="0">Private</option>
				<option value="2" SELECTED>Allow use, use as template, no modifications</option>
				<option value="3">Allow use and modifications</option>
			</select>
			<br/><input type="checkbox" name="reuseqrights" checked /> Use rights in import, if available.
			</p>
			<p>
			For Added Libraries, Set Library Use Rights to:
			<select name="librights">
				<option value="0">Private</option>
				<option value="1">Closed to group, private to others</option>
				<option value="2" SELECTED>Open to group, private to others</option>
				<option value="4">Closed to all</option>
				<option value="5">Open to group, closed to others</option>
				<option value="8">Open to all</option>
			</select>
			<br/><input type="checkbox" name="reuselibrights" checked /> Use rights in import, if available.
			</p>

			<p>Parent library:
				<span id="libnames">Root</span>
				<input type=hidden name="parent" id="parent"  value="0">
				<input type=button value="Select Parent" onClick="libselect()">
			</p>

			<p>If a library or question already exists on this system, do you want to:<br/>
				<input type=radio name=merge value="1" CHECKED>Update existing if not edited locally,<br/>
				<input type=radio name=merge value="-1">Keep existing, or <br/>
				<input type=radio name=merge value="2">Force update
				<br/>
				Note that updating existing libraries will not place those imported libraries
				in the parent selected above.
			</p>

			Base
			<ul class=base>
<?php printlist(0); ?>
			</ul>

			<p><input type=submit name="process" value="Import Libraries"></p>
<?php
			}
		}
		echo "</form>\n";
	}
}
require("../footer.php");
?>
