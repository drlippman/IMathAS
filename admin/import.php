<?php
//IMathAS:  Questionset import
//(c) 2006 David Lippman

@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

/*** master php includes *******/
require("../validate.php");

/*** pre-html data manipulation, including function code *******/
function parsefile($file) {
	//$lines = file($file);
	$handle = fopen($file,"r");

	if (!$handle) {
	    print("Unable to open import file. Check server logs.");
	    error_log(sprintf('Unable to open file: "%s%', $file));
	    exit;
    }

	$qnum = -1;
	$part = '';
	while (!feof($handle)) {
	//foreach ($lines as $line) {
		//$line = rtrim($line);
		$line = rtrim(fgets($handle, 4096));
		if ($line == "LIBRARY DESCRIPTION") {
			$part = "libdesc";
			continue;
		} else if ($line=="PACKAGE DESCRIPTION") {
			$qdata['pack'] = 'set';
			continue;
		} else if ($line == "START QUESTION") {
			$part = '';
			if ($qnum>-1) {
				foreach($qdata[$qnum] as $k=>$val) {
					$qdata[$qnum][$k] = rtrim($val);
				}
			}
			$qnum++;
			continue;
		} else if ($line == "UQID") {
			$part = 'uqid';
			continue;
		} else if ($line == "LASTMOD") {
			$part = 'lastmod';
			continue;
		} else if ($line == "DESCRIPTION") {
			$part = 'description';
			continue;
		} else if ($line == "AUTHOR") {
			$part = 'author';
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
			if ($part=="libdesc") {
				$qdata['libdesc'] .= $line . "\n";
			} else if ($part=="qtype") {
				if ($qnum>-1) {
					$qdata[$qnum]['qtype'] .= $line;
				}
			} else {
				if ($qnum>-1 && $part!='') {
					$qdata[$qnum][$part] .= $line . "\n";
				}
			}
		}
	}
	fclose($handle);
	if ($qnum > -1) {
		foreach($qdata[$qnum] as $k=>$val) {
			$qdata[$qnum][$k] = rtrim($val);
		}
	}
	return $qdata;
}

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = $installname . " Import Questions";


//data manipulation here
$isadmin = false;
$isgrpadmin = false;

	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid)) && $myrights<75) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_GET['cid']) && $_GET['cid']=="admin" && $myrights <75) {
 	$overwriteBody = 1;
	$body = "You need to log in as an admin to access this page";
} elseif (!(isset($_GET['cid'])) && $myrights < 75) {
 	$overwriteBody = 1;
	$body = "Please access this page from the menu links only.";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

	$cid = (isset($_GET['cid'])) ? Sanitize::courseId($_GET['cid']) : "admin" ;

	if ($myrights < 100) {
		$isgrpadmin = true;
	} else if ($myrights == 100) {
		$isadmin = true;
	}

	if ($isadmin || $isgrpadmin) {
		$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"admin.php\">Admin</a> &gt; Import Question Set</div>\n";
	} else {
		$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Import Question Set</div>\n";
	}

	//FORM HAS BEEN POSTED, STEP 3 DATA MANIPULATION
	if (isset($_POST['process'])) {
		$filename = rtrim(dirname(__FILE__), '/\\') .'/import/' . Sanitize::sanitizeFilenameAndCheckBlacklist($_POST['filename']);
		$qdata = parsefile($filename);

		//need to addslashes before SQL insert
		//DB $qdata = array_map('addslashes_deep', $qdata);
		//$newlibs = $_POST['libs'];
		$newlibs = explode(",",$_POST['libs']);

		if (in_array('0',$newlibs) && count($newlibs)>1) {
			array_shift($newlibs);
		}

		$checked = $_POST['checked'];
		$rights = $_POST['userights'];
		foreach ($checked as $qn) {
			if (is_numeric($qdata[$qn]['uqid'])) {
				$lookup[] = $qdata[$qn]['uqid'];
			}
		}
		//DB $lookup = implode("','",$lookup);
		foreach ($lookup as $k=>$v) {
			$lookup[$k] = preg_replace('/[^0-9\.]/','',$v);
		}
		$lookup = implode(',', $lookup);
		//intval bad on bigints
		//$lookup = implode(',', array_map('intval', $lookup));

		//DB $query = "SELECT id,uniqueid,adddate,lastmoddate FROM imas_questionset WHERE uniqueid IN ('$lookup')";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,uniqueid,adddate,lastmoddate FROM imas_questionset WHERE uniqueid IN ($lookup)");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$exists[$row[1]] = $row[0];
			$adddate[$row[0]] = $row[2];
			$lastmod[$row[0]] = $row[3];
		}

		if (count($exists)>0) {
			$checkli = implode(',', array_map('intval', $exists));
			//DB $query = "SELECT libid,qsetid FROM imas_library_items WHERE qsetid IN ($checkli)";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->query("SELECT libid,qsetid FROM imas_library_items WHERE qsetid IN ($checkli)");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$dontaddli[$row[0]] = $row[1]; //prevent adding library items for existing pairs
			}
		}
		$mt = microtime();
		$newq = 0;
		$updateq = 0;
		$newli = 0;
		$now = time();
		$allqids = array();

		//DB mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
		$DBH->beginTransaction();

		foreach ($checked as $qn) {
			if (!empty($qdata[$qn]['qimgs'])) {
				$hasimg = 1;
			} else {
				$hasimg = 0;
			}
			if (isset($exists[$qdata[$qn]['uqid']]) && $_POST['merge']==1) {
				$qsetid = $exists[$qdata[$qn]['uqid']];
				if ($qdata[$qn]['lastmod']>$adddate[$qsetid]) { //only update modified questions - should add check for different lastmoddates
					if ($isgrpadmin) {
						//DB $query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE WHERE imas_questionset.id='$qsetid' AND imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
						//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
						//DB if (mysql_num_rows($result)>0) {
						$stm = $DBH->prepare("SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE WHERE imas_questionset.id=:id AND imas_questionset.ownerid=imas_users.id AND imas_users.groupid=:groupid");
						$stm->execute(array(':id'=>$qsetid, ':groupid'=>$groupid));
						if ($stm->rowCount()>0) {
						//$query = "UPDATE imas_questionset,imas_users SET imas_questionset.description='{$qdata[$qn]['description']}',imas_questionset.author='{$qdata[$qn]['author']}',";
						//$query .= "imas_questionset.qtype='{$qdata[$qn]['qtype']}',imas_questionset.control='{$qdata[$qn]['control']}',imas_questionset.qcontrol='{$qdata[$qn]['qcontrol']}',imas_questionset.qtext='{$qdata[$qn]['qtext']}',";
						//$query .= "imas_questionset.answer='{$qdata[$qn]['answer']}',imas_questionset.adddate=$now,imas_questionset.lastmodddate=$now WHERE imas_questionset.id='$qsetid'";
						//$query .= " AND imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
							//DB $query = "UPDATE imas_questionset SET description='{$qdata[$qn]['description']}',author='{$qdata[$qn]['author']}',";
							//DB $query .= "qtype='{$qdata[$qn]['qtype']}',control='{$qdata[$qn]['control']}',qcontrol='{$qdata[$qn]['qcontrol']}',qtext='{$qdata[$qn]['qtext']}',";
							//DB $query .= "answer='{$qdata[$qn]['answer']}',extref='{$qdata[$qn]['extref']}',license='{$qdata[$qn]['license']}',ancestorauthors='{$qdata[$qn]['ancestorauthors']}',otherattribution='{$qdata[$qn]['otherattribution']}',";
							//DB $query .= "solution='{$qdata[$qn]['solution']}',solutionopts='{$qdata[$qn]['solutionopts']}',";
							//DB $query .= "adddate=$now,lastmoddate=$now,hasimg=$hasimg WHERE id='$qsetid'";
							$query = "UPDATE imas_questionset SET description=:description,author=:author,";
							$query .= "qtype=:qtype,control=:control,qcontrol=:qcontrol,qtext=:qtext,";
							$query .= "answer=:answer,extref=:extref,license=:license,ancestorauthors=:ancestorauthors,otherattribution=:otherattribution,";
							$query .= "solution=:solution,solutionopts=:solutionopts,";
							$query .= "adddate=:adddate,lastmoddate=:lastmoddate,hasimg=:hasimg WHERE id=:id";
							$stm = $DBH->prepare($query);
							$stm->execute(array(':description'=>$qdata[$qn]['description'], ':author'=>$qdata[$qn]['author'], ':qtype'=>$qdata[$qn]['qtype'],
								':control'=>$qdata[$qn]['control'], ':qcontrol'=>$qdata[$qn]['qcontrol'], ':qtext'=>$qdata[$qn]['qtext'],
								':answer'=>$qdata[$qn]['answer'], ':extref'=>$qdata[$qn]['extref'], ':license'=>$qdata[$qn]['license'],
								':ancestorauthors'=>$qdata[$qn]['ancestorauthors'], ':otherattribution'=>$qdata[$qn]['otherattribution'],
								':solution'=>$qdata[$qn]['solution'], ':solutionopts'=>$qdata[$qn]['solutionopts'], ':adddate'=>$now, ':lastmoddate'=>$now,
								':hasimg'=>$hasimg, ':id'=>$qsetid));
						} else {
							continue;
						}
					} else {
						//DB $query = "UPDATE imas_questionset SET description='{$qdata[$qn]['description']}',author='{$qdata[$qn]['author']}',";
						//DB $query .= "qtype='{$qdata[$qn]['qtype']}',control='{$qdata[$qn]['control']}',qcontrol='{$qdata[$qn]['qcontrol']}',qtext='{$qdata[$qn]['qtext']}',";
						//DB $query .= "answer='{$qdata[$qn]['answer']}',extref='{$qdata[$qn]['extref']}',license='{$qdata[$qn]['license']}',ancestorauthors='{$qdata[$qn]['ancestorauthors']}',otherattribution='{$qdata[$qn]['otherattribution']}',";
						//DB $query .= "solution='{$qdata[$qn]['solution']}',solutionopts='{$qdata[$qn]['solutionopts']}',";
						//DB $query .= "adddate=$now,lastmoddate=$now,hasimg=$hasimg WHERE id='$qsetid'";
						$query = "UPDATE imas_questionset SET description=:description,author=:author,";
						$query .= "qtype=:qtype,control=:control,qcontrol=:qcontrol,qtext=:qtext,";
						$query .= "answer=:answer,extref=:extref,license=:license,ancestorauthors=:ancestorauthors,otherattribution=:otherattribution,";
						$query .= "solution=:solution,solutionopts=:solutionopts,";
						$query .= "adddate=:adddate,lastmoddate=:lastmoddate,hasimg=:hasimg WHERE id=:id";

						$qarr = array(':description'=>$qdata[$qn]['description'], ':author'=>$qdata[$qn]['author'], ':qtype'=>$qdata[$qn]['qtype'],
							':control'=>$qdata[$qn]['control'], ':qcontrol'=>$qdata[$qn]['qcontrol'], ':qtext'=>$qdata[$qn]['qtext'],
							':answer'=>$qdata[$qn]['answer'], ':extref'=>$qdata[$qn]['extref'], ':license'=>$qdata[$qn]['license'],
							':ancestorauthors'=>$qdata[$qn]['ancestorauthors'], ':otherattribution'=>$qdata[$qn]['otherattribution'],
							':solution'=>$qdata[$qn]['solution'], ':solutionopts'=>$qdata[$qn]['solutionopts'], ':adddate'=>$now, ':lastmoddate'=>$now,
							':hasimg'=>$hasimg, ':id'=>$qsetid);
						if (!$isadmin) {
							//DB $query .= " AND (ownerid='$userid' OR userights>3)";
							$query .= " AND (ownerid=:ownerid OR userights>3)";
							$qarr[':ownerid'] = $userid;
						}
						$stm = $DBH->prepare($query);
						$stm->execute($qarr);
					}
					//DB mysql_query($query) or die("Import failed on {$qdata['description']}: $query: " . mysql_error());
					//DB if (mysql_affected_rows()>0) {
					if ($stm->rowCount()>0) {
						$updateq++;
						if (!empty($qdata[$qn]['qimgs'])) {
							//not efficient, but sufficient :)
							//DB $query = "DELETE FROM imas_qimages WHERE qsetid='$qsetid'";
							//DB mysql_query($query) or die("Import failed on $query: " . mysql_error());
							$stm = $DBH->prepare("DELETE FROM imas_qimages WHERE qsetid=:qsetid");
							$stm->execute(array(':qsetid'=>$qsetid));
							$qimgs = explode("\n",trim($qdata[$qn]['qimgs']));
							foreach($qimgs as $qimg) {
								$p = explode(',',$qimg);
								//DB $query = "INSERT INTO imas_qimages (qsetid,var,filename) VALUES ($qsetid,'{$p[0]}','{$p[1]}')";
								//DB mysql_query($query) or die("Import failed on $query: " . mysql_error());
								$stm = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename) VALUES (:qsetid, :var, :filename)");
								$stm->execute(array(':qsetid'=>$qsetid, ':var'=>$p[0], ':filename'=>$p[1]));
							}
						}
					} else {
						continue;
					}
				}
			} else if (isset($exists[$qdata[$qn]['uqid']]) && $_POST['merge']==-1) {
				$qsetid = $exists[$qdata[$qn]['uqid']];
			} else {
				$importuid = '';
				if ($qdata[$qn]['uqid']=='0' || (isset($exists[$qdata[$qn]['uqid']]) && $_POST['merge']==0)) {
					$importuid = $qdata[$qn]['uqid'];
					$qdata[$qn]['uqid'] = substr($mt,11).substr($mt,2,2).$qn;
				}
				//DB $query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,userights,description,author,qtype,control,qcontrol,qtext,answer,solution,solutionopts,extref,license,ancestorauthors,otherattribution,hasimg,importuid) VALUES ";
				//DB $query .= "('{$qdata[$qn]['uqid']}',$now,$now,'$userid','$rights','{$qdata[$qn]['description']}','{$qdata[$qn]['author']}','{$qdata[$qn]['qtype']}','{$qdata[$qn]['control']}','{$qdata[$qn]['qcontrol']}','{$qdata[$qn]['qtext']}','{$qdata[$qn]['answer']}','{$qdata[$qn]['solution']}','{$qdata[$qn]['solutionopts']}','{$qdata[$qn]['extref']}','{$qdata[$qn]['license']}','{$qdata[$qn]['ancestorauthors']}','{$qdata[$qn]['otherattribution']}',$hasimg,$importuid)";
				//DB mysql_query($query) or die("Import failed on {$qdata[$qn]['description']}: $query:" . mysql_error());
				//DB $qsetid = mysql_insert_id();
				$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,userights,description,author,qtype,control,qcontrol,qtext,answer,solution,solutionopts,extref,license,ancestorauthors,otherattribution,hasimg,importuid) VALUES ";
				$query .= "(:uniqueid, :adddate, :lastmoddate, :ownerid, :userights, :description, :author, :qtype, :control, :qcontrol, :qtext, :answer, :solution, :solutionopts, :extref, :license, :ancestorauthors, :otherattribution, :hasimg, :importuid)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':uniqueid'=>$qdata[$qn]['uqid'], ':adddate'=>$now, ':lastmoddate'=>$now, ':ownerid'=>$userid, ':userights'=>$rights,
					':description'=>$qdata[$qn]['description'], ':author'=>$qdata[$qn]['author'], ':qtype'=>$qdata[$qn]['qtype'],
					':control'=>$qdata[$qn]['control'], ':qcontrol'=>$qdata[$qn]['qcontrol'], ':qtext'=>$qdata[$qn]['qtext'],
					':answer'=>$qdata[$qn]['answer'], ':solution'=>$qdata[$qn]['solution'], ':solutionopts'=>$qdata[$qn]['solutionopts'],
					':extref'=>$qdata[$qn]['extref'], ':license'=>$qdata[$qn]['license'], ':ancestorauthors'=>$qdata[$qn]['ancestorauthors'],
					':otherattribution'=>$qdata[$qn]['otherattribution'], ':hasimg'=>$hasimg, ':importuid'=>$importuid));
				$qsetid = $DBH->lastInsertId();
				if (!empty($qdata[$qn]['qimgs'])) {
					$qimgs = explode("\n",$qdata[$qn]['qimgs']);
					foreach($qimgs as $qimg) {
						$p = explode(',',$qimg);
						//DB $query = "INSERT INTO imas_qimages (qsetid,var,filename) VALUES ($qsetid,'{$p[0]}','{$p[1]}')";
						//DB mysql_query($query) or die("Import failed on $query: " . mysql_error());
						$stm = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename) VALUES (:qsetid, :var, :filename)");
						$stm->execute(array(':qsetid'=>$qsetid, ':var'=>$p[0], ':filename'=>$p[1]));
					}
				}
				$newq++;
			}

			foreach ($newlibs as $lib) {
				if (!(isset($dontadd[$lib]) && $dontadd[$lib]==$qsetid)) {
					//DB $query = "INSERT INTO imas_library_items (qsetid,libid,ownerid) VALUES ('$qsetid','$lib','$userid')";
					//DB mysql_query($query) or die("Couldnt add to library $lib qsetid $qsetid: " . mysql_error());
					$stm = $DBH->prepare("INSERT INTO imas_library_items (qsetid,libid,ownerid) VALUES (:qsetid, :libid, :ownerid)");
					$stm->execute(array(':qsetid'=>$qsetid, ':libid'=>$lib, ':ownerid'=>$userid));
					$newli++;
				}
			}
			$allqids[] = $qsetid;
		}
		unlink($filename);
		//resolve any includecodefrom links
		$qidstoupdate = array();
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
		if (count($qidstoupdate)>0) {
			//lookup backrefs
			$includedbackref = array();
			if (count($includedqs)>0) {
				$includedlist = implode(',', $includedqs);  //known decimal values from above
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
				$stm2->execute(array(':control'=>$control, ':qtext'=>$qtext, ':id'=>$row[0]));
			}
		}

		//DB mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
		$DBH->commit();

		if ($isadmin || $isgrpadmin) {
			$page_importSuccessMsg = "<a href=\"" . $GLOBALS['basesiteurl'] . "/admin/admin.php\">Return to Admin page</a>";
		} else {
			$page_importSuccessMsg = "<a href=\"" . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid\">Return to Course page</a>";
		}
	} elseif ($_FILES['userfile']['name']!='') { //FILE POSTED, STEP 2 DATA MANIPULATION

		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . Sanitize::sanitizeFilenameAndCheckBlacklist($_FILES['userfile']['name']);
		$page_fileErrorMsg = "";
		$page_fileNoticeMsg = "";

		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {

			$uploadfilebasename = basename($uploadfile);
			$page_fileHiddenInput = "<input type=hidden name=\"filename\" value=\"".Sanitize::sanitizeFilenameAndCheckBlacklist($uploadfilebasename)."\" />\n";

		} else {
			$page_fileErrorMsg .= "<p>Error uploading file!</p>\n";
		}
		$qdata = parsefile($uploadfile);

		if (!isset($qdata['pack']) && !isset($qdata['libdesc'])) {
			$page_fileErrorMsg .= "This does not appear to be a valid IMathAS file. <a href=\"import.php?cid=$cid\">Try Again</a>";
		}
		foreach ($qdata as $qnd) {
			if (is_numeric($qnd['uqid'])) {
				$lookup[] = $qnd['uqid'];
			}
		}
		$lookup = implode(',', array_map('intval', $lookup));
		//DB $query = "SELECT id,uniqueid FROM imas_questionset WHERE uniqueid IN ('$lookup')";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
		$stm = $DBH->query("SELECT id,uniqueid FROM imas_questionset WHERE uniqueid IN ($lookup)");
		if ($stm->rowCount()>0) {
			$existing = true;
			$page_existingMsg = "<p>This file contains questions with uniqueids that already exist on this system.  With these questions, do you want to:<br/>\n";
			$page_existingMsg .= "<input type=radio name=merge value=\"1\" CHECKED>Update existing questions, <input type=radio name=merge value=\"0\">Add as new question, or <input type=radio name=merge value=\"-1\">Keep existing question</p>\n";
		} else {
			$existing = false;
			$page_existingMsg = "";
		}

		if (isset($qdata['pack'])) {
			$page_fileNoticeMsg .=  "<p>This file contains a library structure as well as questions.  Continue to use this form ";
			$page_fileNoticeMsg .=  "if you with to import individual questions.<br />  Use the <a href=\"importlib.php?cid=$cid\">Import Libraries</a> ";
			$page_fileNoticeMsg .=  "page to import the libraries with structure</p>\n";
		}

	} else {
		//STEP 1 DATA MANIPULATION
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
		window.open('../course/libtree.php?cid=<?php echo $cid ?>&libtree=popup&selectrights=1&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
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



<?php
	echo $curBreadcrumb;

	//FORM HAS BEEN POSTED WITH GOOD FILE, STEP 3 DISPLAY
	if (isset($_POST['process'])) {
?>
		Import Successful.<br>
		New Questions: <?php echo $newq ?>.<br>
		Updated Questions: <?php echo $updateq ?>.<br>
		New Library items: <?php echo $newli ?>.<br>
		<?php echo $page_importSuccessMsg; ?>

<?php
	} else {
		echo $page_fileNoticeMsg;
?>

		<div id="headerimport" class="pagetitle"><h2>Import Question Set</h2></div>
		<form id="qform" enctype="multipart/form-data" method=post action="import.php?cid=<?php echo $cid ?>">

<?php
		if ($_FILES['userfile']['name']=='') { //INITIAL LOAD, STEP 1 DISPLAY
?>
			<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
			<span class=form>Import file: </span>
			<span class=formright><input name="userfile" type="file" /></span><br class=form>
			<div class=submit><input type=submit value="Submit"></div>

<?php
		} else { //FORM POSTED WITH LOCAL FILE, STEP 2 DISPLAY

			if (strlen($page_fileErrorMsg)>1) { //If there was an upload or parse error display message
				echo $page_fileErrorMsg;
			} else { //file uploaded OK, proceed with import details
				echo $page_fileHiddenInput;
				echo $qdata['libdesc'];
				echo $page_existingMsg;
?>
				<h3>Select Questions to import</h3>

				<p>
				Set Question Use Rights to <select name=userights>
				<option value="0">Private</option>
				<option value="2" SELECTED>Allow use, use as template, no modifications</option>
				<option value="3">Allow use by all and modifications by group</option>
				<option value="4">Allow use and modifications by all</option>
				</select>
				</p>

				<p>
					Assign to library: <span id="libnames">Unassigned</span>
					<input type=hidden name="libs" id="libs"  value="0">
					<input type=button value="Select Libraries" onClick="libselect()"><br>
					Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>

				</p>

				<table cellpadding=5 class=gb>
					<thead>
					<tr><th></th><th>Description</th><th>Type</th></tr>
					</thead>
					<tbody>
<?php
				$alt=0;
				for ($i = 0 ; $i<(count($qdata)-1); $i++) {
					if ($alt==0) {echo "						<tr class=even>"; $alt=1;} else {echo "						<tr class=odd>"; $alt=0;}
?>
							<td>
								<input type=checkbox name='checked[]' value='<?php echo $i ?>' checked=checked>
							</td>
							<td><?php echo $qdata[$i]['description'] ?></td>
							<td><?php echo $qdata[$i]['qtype'] ?></td>
						</tr>
<?php
				}
?>
					</tbody>
				</table><BR>
				<input type=submit name="process" value="Import Questions">
<?php
			}
		}
?>
			</form>
<?php
	}
}
require("../footer.php");
?>
