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
		} else if ($line == "EXTREF") {
			$part = 'extref';
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
	
	$cid = (isset($_GET['cid'])) ? $_GET['cid'] : "admin" ;

	if ($myrights < 100) {
		$isgrpadmin = true;
	} else if ($myrights == 100) {
		$isadmin = true;
	}

	if ($isadmin || $isgrpadmin) {
		$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"admin.php\">Admin</a> &gt; Import Question Set</div>\n";
	} else {
		$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Import Question Set</div>\n";
	}
	
	//FORM HAS BEEN POSTED, STEP 3 DATA MANIPULATION
	if (isset($_POST['process'])) {
		$filename = rtrim(dirname(__FILE__), '/\\') .'/import/' . $_POST['filename'];
		$qdata = parsefile($filename);
		
		//need to addslashes before SQL insert
		$qdata = array_map('addslashes_deep', $qdata);
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
		$lookup = implode("','",$lookup);
		$query = "SELECT id,uniqueid,adddate,lastmoddate FROM imas_questionset WHERE uniqueid IN ('$lookup')";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$exists[$row[1]] = $row[0];
			$adddate[$row[0]] = $row[2];
			$lastmod[$row[0]] = $row[3];
		}
		
		if (count($exists)>0) {
			$checkli = implode(',',$exists);
			$query = "SELECT libid,qsetid FROM imas_library_items WHERE qsetid IN ($checkli)";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$dontaddli[$row[0]] = $row[1]; //prevent adding library items for existing pairs
			}
		}
		$mt = microtime();
		$newq = 0;
		$updateq = 0;
		$newli = 0;
		$now = time();
		$allqids = array();
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
						$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE WHERE imas_questionset.id='$qsetid' AND imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
						$result = mysql_query($query) or die("Query failed : " . mysql_error());
						if (mysql_num_rows($result)>0) {
						//$query = "UPDATE imas_questionset,imas_users SET imas_questionset.description='{$qdata[$qn]['description']}',imas_questionset.author='{$qdata[$qn]['author']}',";
						//$query .= "imas_questionset.qtype='{$qdata[$qn]['qtype']}',imas_questionset.control='{$qdata[$qn]['control']}',imas_questionset.qcontrol='{$qdata[$qn]['qcontrol']}',imas_questionset.qtext='{$qdata[$qn]['qtext']}',";
						//$query .= "imas_questionset.answer='{$qdata[$qn]['answer']}',imas_questionset.adddate=$now,imas_questionset.lastmodddate=$now WHERE imas_questionset.id='$qsetid'";
						//$query .= " AND imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
							$query = "UPDATE imas_questionset SET description='{$qdata[$qn]['description']}',author='{$qdata[$qn]['author']}',";
							$query .= "qtype='{$qdata[$qn]['qtype']}',control='{$qdata[$qn]['control']}',qcontrol='{$qdata[$qn]['qcontrol']}',qtext='{$qdata[$qn]['qtext']}',";
							$query .= "answer='{$qdata[$qn]['answer']}',extref='{$qdata[$qn]['extref']}',adddate=$now,lastmodddate=$now,hasimg=$hasimg WHERE id='$qsetid'";
						} else {
							continue;
						}
					} else {
					
						$query = "UPDATE imas_questionset SET description='{$qdata[$qn]['description']}',author='{$qdata[$qn]['author']}',";
						$query .= "qtype='{$qdata[$qn]['qtype']}',control='{$qdata[$qn]['control']}',qcontrol='{$qdata[$qn]['qcontrol']}',qtext='{$qdata[$qn]['qtext']}',";
						$query .= "answer='{$qdata[$qn]['answer']}',extref='{$qdata[$qn]['extref']}',lastmoddate=$now,adddate=$now,hasimg=$hasimg WHERE id='$qsetid'";
						if (!$isadmin) {
							$query .= " AND (ownerid='$userid' OR userights>3)";
						}
					}
					mysql_query($query) or die("Import failed on {$qdata['description']}: $query: " . mysql_error());
					if (mysql_affected_rows()>0) {
						$updateq++;
						if (!empty($qdata[$qn]['qimgs'])) {
							//not efficient, but sufficient :)
							$query = "DELETE FROM imas_qimages WHERE qsetid='$qsetid'";
							mysql_query($query) or die("Import failed on $query: " . mysql_error());
							$qimgs = explode("\n",$qdata[$qn]['qimgs']);
							foreach($qimgs as $qimg) {
								$p = explode(',',$qimg);
								$query = "INSERT INTO imas_qimages (qsetid,var,filename) VALUES ($qsetid,'{$p[0]}','{$p[1]}')";
								mysql_query($query) or die("Import failed on $query: " . mysql_error());
							}
						}
					} else {
						continue;
					}
				}
			} else if (isset($exists[$qdata[$qn]['uqid']]) && $_POST['merge']==-1) {
				$qsetid = $exists[$qdata[$qn]['uqid']];
			} else {
				if ($qdata[$qn]['uqid']==0 || (isset($exists[$qdata[$qn]['uqid']]) && $_POST['merge']==0)) {
					$qdata[$qn]['uqid'] = substr($mt,11).substr($mt,2,2).$qn;
				}
				$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,userights,description,author,qtype,control,qcontrol,qtext,answer,extref,hasimg) VALUES ";
				$query .= "('{$qdata[$qn]['uqid']}',$now,$now,'$userid','$rights','{$qdata[$qn]['description']}','{$qdata[$qn]['author']}','{$qdata[$qn]['qtype']}','{$qdata[$qn]['control']}','{$qdata[$qn]['qcontrol']}',";
				$query .= "'{$qdata[$qn]['qtext']}','{$qdata[$qn]['answer']}','{$qdata[$qn]['extref']}',$hasimg)";
				mysql_query($query) or die("Import failed on {$qdata[$qn]['description']}: $query:" . mysql_error());
				$qsetid = mysql_insert_id();
				if (!empty($qdata[$qn]['qimgs'])) {
					$qimgs = explode("\n",$qdata[$qn]['qimgs']);
					foreach($qimgs as $qimg) {
						$p = explode(',',$qimg);
						$query = "INSERT INTO imas_qimages (qsetid,var,filename) VALUES ($qsetid,'{$p[0]}','{$p[1]}')";
						mysql_query($query) or die("Import failed on $query: " . mysql_error());
					}
				}
				$newq++;
			}
			
			foreach ($newlibs as $lib) {
				if (!(isset($dontadd[$lib]) && $dontadd[$lib]==$qsetid)) {
					$query = "INSERT INTO imas_library_items (qsetid,libid,ownerid) VALUES ('$qsetid','$lib','$userid')";
					mysql_query($query) or die("Couldnt add to library $lib qsetid $qsetid: " . mysql_error());
					$newli++;
				}
			}
			$allqids[] = $qsetid;
		}
		unlink($filename);
		//resolve any includecodefrom links
		$qidstoupdate = array();
		$qidstocheck = implode(',',$allqids);
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

		if ($isadmin || $isgrpadmin) {
			$page_importSuccessMsg = "<a href=\"".$urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php\">Return to Admin page</a>";
		} else {
			$page_importSuccessMsg = "<a href=\"".$urlmode . $_SERVER['HTTP_HOST']  . $imasroot . "/course/course.php?cid=$cid\">Return to Course page</a>";
		}
	} elseif ($_FILES['userfile']['name']!='') { //FILE POSTED, STEP 2 DATA MANIPULATION
		
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		$page_fileErrorMsg = "";
		$page_fileNoticeMsg = "";
		
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			$page_fileHiddenInput = "<input type=hidden name=\"filename\" value=\"".basename($uploadfile)."\" />\n";
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
		$lookup = implode("','",$lookup);
		$query = "SELECT id,uniqueid FROM imas_questionset WHERE uniqueid IN ('$lookup')";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
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
	
