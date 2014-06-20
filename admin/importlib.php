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
require("../validate.php");

/*** pre-html data manipulation, including function code *******/
function printlist($parent) {
	global $parents,$names;
	$children = array_keys($parents,$parent);
	foreach ($children as $child) {
		if (!in_array($child,$parents)) { //if no children
			echo "<li><span class=dd>-</span><input type=checkbox name=\"libs[]\" value=\"$child\" CHECKED>{$names[$child]}</li>";
		} else { // if children
			echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle($child)\"><span class=btn id=\"b$child\">+</span> ";
			echo "</span><input type=checkbox name=\"libs[]\" value=$child CHECKED>";
			echo "<span class=hdr onClick=\"toggle($child)\">{$names[$child]}</span>";
			echo "<ul class=hide id=$child>\n";
			printlist($child);
			echo "</ul></li>\n";
		}
	}
}

function parseqs($file,$touse,$rights) {
	function writeq($qd,$rights,$qn) {
		global $userid,$isadmin,$updateq,$newq,$isgrpadmin;
		$now = time();
		$qd = array_map('addslashes_deep', $qd);
		$query = "SELECT id,adddate,lastmoddate FROM imas_questionset WHERE uniqueid='{$qd['uqid']}'";
		$result = mysql_query($query) or die("Error: $query: " . mysql_error());
		if (mysql_num_rows($result)>0) {
			$qsetid = mysql_result($result,0,0);
			$adddate = mysql_result($result,0,1);
			$lastmoddate = mysql_result($result,0,2);
			$exists = true;
		} else {
			$exists = false;
		}
		
		if ($exists && $_POST['merge']==1) {
			if ($qd['lastmod']>$adddate) { //only update if changed
				if (!empty($qd['qimgs'])) {
					$hasimg = 1;
				} else {
					$hasimg = 0;
				}
				if ($isgrpadmin) {
					//$query = "UPDATE imas_questionset,imas_users SET imas_questionset.description='{$qd['description']}',imas_questionset.author='{$qd['author']}',";
					//$query .= "imas_questionset.qtype='{$qd['qtype']}',imas_questionset.control='{$qd['control']}',imas_questionset.qcontrol='{$qd['qcontrol']}',imas_questionset.qtext='{$qd['qtext']}',";
					//$query .= "imas_questionset.answer='{$qd['answer']}',imas_questionset.lastmoddate=$now,imas_questionset.adddate=$now WHERE imas_questionset.id='$qsetid'";
					//$query .= " AND imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
					$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE WHERE imas_questionset.id='$qsetid' AND imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($result)>0) {
						$query = "UPDATE imas_questionset SET description='{$qd['description']}',author='{$qd['author']}',";
						$query .= "qtype='{$qd['qtype']}',control='{$qd['control']}',qcontrol='{$qd['qcontrol']}',qtext='{$qd['qtext']}',";
						$query .= "answer='{$qd['answer']}',extref='{$qd['extref']}',license='{$qd['license']}',ancestorauthors='{$qd['ancestorauthors']}',otherattribution='{$qd['otherattribution']}',";
						$query .= "solution='{$qd['solution']}',solutionopts='{$qd['solutionopts']}',";
						$query .= "adddate=$now,lastmoddate=$now,hasimg=$hasimg WHERE id='$qsetid'";
					} else {
						return $qsetid;
					}
				} else {
					$query = "UPDATE imas_questionset SET description='{$qd['description']}',author='{$qd['author']}',";
					$query .= "qtype='{$qd['qtype']}',control='{$qd['control']}',qcontrol='{$qd['qcontrol']}',qtext='{$qd['qtext']}',";
					$query .= "answer='{$qd['answer']}',extref='{$qd['extref']}',license='{$qd['license']}',ancestorauthors='{$qd['ancestorauthors']}',otherattribution='{$qd['otherattribution']}',";
					$query .= "solution='{$qd['solution']}',solutionopts='{$qd['solutionopts']}',adddate=$now,lastmoddate=$now,hasimg=$hasimg WHERE id='$qsetid'";
					if (!$isadmin) {
						$query .= " AND ownerid=$userid";
					}
				}
				mysql_query($query) or die("error on: $query: " . mysql_error());
				if (mysql_affected_rows()>0) {
					$updateq++;
					if (!empty($qd['qimgs'])) {
						//not efficient, but sufficient :)
						$query = "DELETE FROM imas_qimages WHERE qsetid='$qsetid'";
						mysql_query($query) or die("Import failed on $query: " . mysql_error());
						$qimgs = explode("\n",$qd['qimgs']);
						foreach($qimgs as $qimg) {
							$p = explode(',',$qimg);
							$query = "INSERT INTO imas_qimages (qsetid,var,filename) VALUES ($qsetid,'{$p[0]}','{$p[1]}')";
							mysql_query($query) or die("Import failed on $query: " . mysql_error());
						}
					}
				}
			} 
			return $qsetid;
		} else if ($exists && $_POST['merge']==-1) {
			return $qsetid;	
		} else {
			$importuidstr = '';
			$importuidval = '';
			if ($qd['uqid']=='0' || ($exists && $_POST['merge']==0)) {
				$importuidstr = ',importuid';
				$importuidval = ','.$qd['uqid'];
				$mt = microtime();
				$qd['uqid'] = substr($mt,11).substr($mt,2,2).$qn;
			}
			if (!empty($qd['qimgs'])) {
				$hasimg = 1;
			} else {
				$hasimg = 0;
			}
			$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,userights,description,author,qtype,control,qcontrol,qtext,answer,solution,solutionopts,extref,license,ancestorauthors,otherattribution,hasimg$importuidstr) VALUES ";
			$query .= "('{$qd['uqid']}',$now,$now,'$userid','$rights','{$qd['description']}','{$qd['author']}','{$qd['qtype']}','{$qd['control']}','{$qd['qcontrol']}',";
			$query .= "'{$qd['qtext']}','{$qd['answer']}','{$qd['solution']}','{$qd['solutionopts']}','{$qd['extref']}','{$qd['license']}','{$qd['ancestorauthors']}','{$qd['otherattribution']}',$hasimg$importuidval)";
			mysql_query($query) or die("Import failed on $query: " . mysql_error());
			$newq++;
			$qsetid = mysql_insert_id();
			if (!empty($qd['qimgs'])) {
				$qimgs = explode("\n",$qd['qimgs']);
				foreach($qimgs as $qimg) {
					$p = explode(',',$qimg);
					$query = "INSERT INTO imas_qimages (qsetid,var,filename) VALUES ($qsetid,'{$p[0]}','{$p[1]}')";
					mysql_query($query) or die("Import failed on $query: " . mysql_error());
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
		} else if ($line=="START LIBRARY") {
			$dopackd = false;
			$libid = -1;
		} else if ($line=="ID") {
			$libid = rtrim(fgets($handle, 4096));
		} else if ($line=="UID") {
			$unique[$libid] = rtrim(fgets($handle, 4096));
		} else if ($line=="LASTMODDATE") {
			$lastmoddate[$libid] = rtrim(fgets($handle, 4096));
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
	return array($packname,$names,$parents,$libitems,$unique,$lastmoddate);
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
	
	$cid = (isset($_GET['cid'])) ? $_GET['cid'] : "admin" ;

	if ($myrights < 100) {
		$isgrpadmin = true;
	} else if ($myrights == 100) {
		$isadmin = true;
	}

	if ($isadmin || $isgrpadmin) {
		$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"admin.php\">Admin</a> &gt; Import Libraries</div>\n";
	} else {
		$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Import Libraries</div>\n";
	}
	
	//FORM HAS BEEN POSTED, STEP 3 DATA MANIPULATION
	if (isset($_POST['process'])) {
		$filename = rtrim(dirname(__FILE__), '/\\') .'/import/' . $_POST['filename'];
		
		$libstoadd = $_POST['libs'];
		
		list($packname,$names,$parents,$libitems,$unique,$lastmoddate) = parselibs($filename);
		//need to addslashes before SQL insert
		$names = array_map('addslashes_deep', $names);
		$parents = array_map('addslashes_deep', $parents);
		$libitems = array_map('addslashes_deep', $libitems);
		$unique = array_map('addslashes_deep', $unique);
		$lastmoddate = array_map('addslashes_deep', $lastmoddate);
		
		$root = $_POST['parent'];
		$librights = $_POST['librights'];
		$qrights = $_POST['qrights'];
		$touse = '';
		//write libraries
		$lookup = implode("','",$unique);
		$query = "SELECT id,uniqueid,adddate,lastmoddate FROM imas_libraries WHERE uniqueid IN ('$lookup')";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$exists[$row[1]] = $row[0];
			$adddate[$row[0]] = $row[2];
			$lastmod[$row[0]] = $row[3];
		}

		$mt = microtime();
		$updatel = 0;
		$newl = 0;
		$newli = 0;
		$updateq = 0;
		$newq = 0;
		
		mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
		
		foreach ($libstoadd as $libid) {
			if ($parents[$libid]==0) {  //use given root parent
				$parent = $root;
			} else if (isset($libs[$parents[$libid]])) { //if parent has been set
				$parent = $libs[$parents[$libid]];
			} else { //otherwise, skip this library (skip children if parent not added)
				continue;
			}
			$now = time();
			if (isset($exists[$unique[$libid]]) && $_POST['merge']==1) {
				if ($lastmoddate[$libid]>$adddate[$exists[$unique[$libid]]]) { //if library has changed
					$query = "UPDATE imas_libraries SET name='{$names[$libid]}',adddate=$now,lastmoddate=$now WHERE id={$exists[$unique[$libid]]}";
					if ($isgrpadmin) {
						$query .= " AND groupid='$groupid'";
					} else if (!$isadmin) {
						$query .= " AND (ownerid='$userid' or userights>1)";
					}
					mysql_query($query) or die("error on: $query: " . mysql_error());
					if (mysql_affected_rows()>0) {
						$updatel++;
					}
				} 
				$libs[$libid] = $exists[$unique[$libid]];
			} else if (isset($exists[$unique[$libid]]) && $_POST['merge']==-1 ) {
				$libs[$libid] = $exists[$unique[$libid]];
			} else {
				if ($unique[$libid]==0 || (isset($exists[$unique[$libid]]) && $_POST['merge']==0)) {
					$unique[$libid] = substr($mt,11).substr($mt,2,2).$libid;
				}
				$query = "INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,parent,groupid) VALUES ";
				$query .= "('{$unique[$libid]}',$now,$now,'{$names[$libid]}','$userid','$librights','$parent','$groupid')";
				mysql_query($query) or die("error on: $query: " . mysql_error());
				$libs[$libid] = mysql_insert_id();
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
			$qidstocheck = implode(',',$qids);
			$qidstoupdate = array();
			//look up any refs to UIDs
			$query = "SELECT id,control,qtext FROM imas_questionset WHERE id IN ($qidstocheck) AND (control LIKE '%includecodefrom(UID%' OR qtext LIKE '%includeqtextfrom(UID%')";
			$result = mysql_query($query) or die("error on: $query: " . mysql_error());
			$includedqs = array();
			while ($row = mysql_fetch_row($result)) {
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
					$includedlist = implode(',',$includedqs);
					$query = "SELECT id,uniqueid FROM imas_questionset WHERE uniqueid IN ($includedlist)";
					$result = mysql_query($query) or die("Query failed : $query"  . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$includedbackref[$row[1]] = $row[0];		
					}
				}
				$updatelist = implode(',',$qidstoupdate);
				$query = "SELECT id,control,qtext FROM imas_questionset WHERE id IN ($updatelist)";
				$result = mysql_query($query) or die("error on: $query: " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$control = addslashes(preg_replace('/includecodefrom\(UID(\d+)\)/e','"includecodefrom(".$includedbackref["\\1"].")"',$row[1]));
					$qtext = addslashes(preg_replace('/includeqtextfrom\(UID(\d+)\)/e','"includeqtextfrom(".$includedbackref["\\1"].")"',$row[2]));
					$query = "UPDATE imas_questionset SET control='$control',qtext='$qtext' WHERE id={$row[0]}";
					mysql_query($query) or die("error on: $query: " . mysql_error());
				}
			}
				
		
			//write imas library items, connecting libraries to items
			foreach ($libstoadd as $libid) {
				if (!isset($libs[$libid])) { $libs[$libid]=0;} //assign questions to unassigned if library is closed.  Shouldn't ever trigger
				$query = "SELECT qsetid FROM imas_library_items WHERE libid={$libs[$libid]}";
				$result = mysql_query($query) or die("error on: $query: " . mysql_error());
				$existingli = array();
				while ($row = mysql_fetch_row($result)) { //don't add new LI if exists
					$existingli[] = $row[0]; 	
				}
				$qidlist = explode(',',$libitems[$libid]);
				foreach ($qidlist as $qid) {
					if (isset($qids[$qid]) && (array_search($qids[$qid],$existingli)===false)) {
						$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('{$libs[$libid]}','{$qids[$qid]}','$userid')";
						mysql_query($query) or die("Import failed on $query: " . mysql_error());
						$newli++;
					}
				}
				unset($existingli);
			}
		}
		
		mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
		
		unlink($filename);
		$page_uploadSuccessMsg = "Import Successful.<br>\n";
		$page_uploadSuccessMsg .= "New Libraries: $newl.<br>";
		$page_uploadSuccessMsg .= "New Questions: $newq.<br>";
		$page_uploadSuccessMsg .= "Updated Libraries: $updatel.<br>";
		$page_uploadSuccessMsg .= "Updated Questions: $updateq.<br>";
		$page_uploadSuccessMsg .= "New Library items: $newli.<br>";
		if ($isadmin || $isgrpadmin) {
			$page_uploadSuccessMsg .=  "<a href=\"".$urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php\">Return to Admin page</a>";
		} else {
			$page_uploadSuccessMsg .= "<a href=\"". $urlmode . $_SERVER['HTTP_HOST']  . $imasroot . "/course/course.php?cid=$cid\">Return to Course page</a>";
		}

	} elseif ($_FILES['userfile']['name']!='') { // STEP 2 DATA MANIPULATION
		$page_fileErrorMsg = "";
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			$page_fileHiddenInput = "<input type=hidden name=\"filename\" value=\"".basename($uploadfile)."\" />\n";
		} else {
			$page_fileErrorMsg .= "<p>Error uploading file!</p>\n";
		}
		
		list($packname,$names,$parents,$libitems,$unique,$lastmoddate) = parselibs($uploadfile);
		
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

<?php echo $packname; ?>
				
			<h3>Select Libraries to import</h3>
			<p>Note:  If a parent library is not selected, NONE of the children libraries will be added,
			regardless of whether they're checked or not
			</p>
				
			<p>
			Set Question Use Rights to: 
			<select name=qrights>
				<option value="0">Private</option>
				<option value="2" SELECTED>Allow use, use as template, no modifications</option>
				<option value="3">Allow use and modifications</option>
			</select>
			</p>
			<p>
			Set Library Use Rights to: 
			<select name="librights">
				<option value="0">Private</option>
				<option value="1">Closed to group, private to others</option>
				<option value="2" SELECTED>Open to group, private to others</option>
<?php				
			if ($isadmin || $isgrpadmin || $allownongrouplibs) {
?>
				<option value="4">Closed to all</option>
				<option value="5">Open to group, closed to others</option>
				<option value="8">Open to all</option>
<?php				
			}
?>					
				
			</select>
			</p>
				
			<p>Parent library: 
				<span id="libnames">Root</span>
				<input type=hidden name="parent" id="parent"  value="0">
				<input type=button value="Select Parent" onClick="libselect()">
			</p>
				
			<p>If a library or question already exists on this system, do you want to:<br/>
				<input type=radio name=merge value="1" CHECKED>Update existing, 
				<input type=radio name=merge value="0">import as new, or 
				<input type=radio name=merge value="-1">Keep existing<br/>
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
	
