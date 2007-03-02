<?php
//IMathAS:  Question library import
//(c) 2006 David Lippman
	require("../validate.php");
	if (!(isset($teacherid)) && $myrights<75) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$isadmin = false;
	$isgrpadmin = false;
	if (isset($_GET['cid']) && $_GET['cid']=="admin") {
		if ($myrights <75) {
			require("../header.php");
			echo "You need to log in as an admin to access this page";
			require("../footer.php");
			exit;
		} else if ($myrights < 100) {
			$isgrpadmin = true;
		} else if ($myrights == 100) {
			$isadmin = true;
		}
	} 
	
	$cid = $_GET['cid'];
	
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
		foreach ($checked as $qn) {
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
							$query .= "answer='{$qdata[$qn]['answer']}',adddate=$now,lastmodddate=$now WHERE id='$qsetid'";
						} else {
							continue;
						}
					} else {
					
						$query = "UPDATE imas_questionset SET description='{$qdata[$qn]['description']}',author='{$qdata[$qn]['author']}',";
						$query .= "qtype='{$qdata[$qn]['qtype']}',control='{$qdata[$qn]['control']}',qcontrol='{$qdata[$qn]['qcontrol']}',qtext='{$qdata[$qn]['qtext']}',";
						$query .= "answer='{$qdata[$qn]['answer']}',adddate=$now,lastmodddate=$now WHERE id='$qsetid'";
						if (!$isadmin) {
							$query .= " AND (ownerid='$userid' OR userights>2)";
						}
					}
					mysql_query($query) or die("Import failed on {$qdata['description']}: " . mysql_error());
					if (mysql_affected_rows()>0) {
						$updateq++;
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
				$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,userights,description,author,qtype,control,qcontrol,qtext,answer) VALUES ";
				$query .= "('{$qdata[$qn]['uqid']}',$now,$now,'$userid','$rights','{$qdata[$qn]['description']}','{$qdata[$qn]['author']}','{$qdata[$qn]['qtype']}','{$qdata[$qn]['control']}','{$qdata[$qn]['qcontrol']}',";
				$query .= "'{$qdata[$qn]['qtext']}','{$qdata[$qn]['answer']}')";
				mysql_query($query) or die("Import failed on {$qdata['description']}: " . mysql_error());
				$qsetid = mysql_insert_id();
				$newq++;
			}
			
			foreach ($newlibs as $lib) {
				if (!(isset($dontadd[$lib]) && $dontadd[$lib]==$qsetid)) {
					$query = "INSERT INTO imas_library_items (qsetid,libid,ownerid) VALUES ('$qsetid','$lib','$userid')";
					mysql_query($query) or die("Couldnt add to library $lib qsetid $qsetid: " . mysql_error());
					$newli++;
				}
			}
		}
		unlink($filename);
		echo "Import Successful.<br>";
		echo "New Questions: $newq.<br>";
		echo "Updated Questions: $updateq.<br>";
		echo "New Library items: $newli.<br>";
		if ($isadmin || $isgrpadmin) {
			echo "<a href=\"http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php\">Return to Admin page</a>";
			//header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php");
		} else {
			echo "<a href=\" http://" . $_SERVER['HTTP_HOST']  . $imasroot . "/course/course.php?cid=$cid\">Return to Course page</a>";
			//header("Location: http://" . $_SERVER['HTTP_HOST']  . $imasroot . "/course/course.php?cid=$cid");
		}
		exit;
	}
	
	require("../header.php");
?>
<script type="text/javascript">
function chkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }
}
</script>
<?php
	if ($isadmin || $isgrpadmin) {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"admin.php\">Admin</a> &gt; Import Question Set</div>\n";
	} else {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Import Question Set</div>\n";
	}
	echo "<h3>Import Question Set</h3>\n";
	echo "<form enctype=\"multipart/form-data\" method=post action=\"import.php?cid=$cid\">\n";
	
	if ($_FILES['userfile']['name']=='') {
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"3000000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
	} else {
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			echo "<input type=hidden name=\"filename\" value=\"".basename($uploadfile)."\" />\n";
		} else {
			echo "<p>Error uploading file!</p>\n";
			echo "</form>\n";
			require("../footer.php");
			exit;
		}
		$qdata = parsefile($uploadfile);

		if (!isset($qdata['pack']) && !isset($qdata['libdesc'])) {
			echo "This does not appear to be a valid IMathAS file. <a href=\"import.php?cid=$cid\">Try Again</a>";
			require("../footer.php");
			exit;
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
		} else {
			$existing = false;
		}
		
		if (isset($qdata['pack'])) {
			echo "<p>This file contains a library structure as well as questions.  Continue to use this form ";
			echo "if you with to import individual questions.  Use the <a href=\"importlib.php?cid=$cid\">Import Libraries</a> ";
			echo "page to import the libraries with structure</p>\n";
		}
		echo $qdata['libdesc'];
		
		if ($existing) {
			echo "<p>This file contains questions with uniqueids that already exist on this system.  With these questions, do you want to:<br/>\n";
			echo "<input type=radio name=merge value=\"1\" CHECKED>Update existing questions, <input type=radio name=merge value=\"0\">Add as new question</p>, or <input type=radio name=merge value=\"-1\">Keep existing question</p>\n";
		}
		
		echo "<h3>Select Questions to import</h3>\n";
		
		echo "<p>\n";
		echo "Set Question Use Rights to <select name=userights>\n";
		echo "<option value=\"0\">Private</option>\n";
		echo "<option value=\"2\" SELECTED>Allow use, use as template, no modifications</option>\n";
		echo "<option value=\"3\">Allow use and modifications</option>\n";
		echo "</select>\n";
		echo "</p><p>\n";
		
		echo <<<END
<script>
var curlibs = '0';
function libselect() {
	window.open('../course/libtree.php?cid=$cid&libtree=popup&selectrights=1&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
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
END;
		
		echo "Assign to library: <span id=\"libnames\">Unassigned</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"0\">\n";
		echo "<input type=button value=\"Select Libraries\" onClick=\"libselect()\"><br> "; 
		
		echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca\" value=\"1\" onClick=\"chkAll(this.form, 'checked[]', this.checked)\" checked=checked>\n";
		
		echo "<table cellpadding=5 class=gb>\n";
		echo "<thead><tr><th></th><th>Description</th><th>Type</th></tr></thead><tbody>\n";
		$alt=0;
		for ($i = 0 ; $i<(count($qdata)-1); $i++) {
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
			echo "<td>";
			echo "<input type=checkbox name='checked[]' value='$i' checked=checked>";
			echo "</td><td>{$qdata[$i]['description']}</td><td>{$qdata[$i]['qtype']}</td>\n";
			echo "</tr>\n";
		}
		echo "</tbody></table>\n";
		echo "<BR><input type=submit name=\"process\" value=\"Import Questions\">\n";
	}
	
	echo "</form>\n";
	require("../footer.php");
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
			} else {
				if ($part=="libdesc") {
					$qdata['libdesc'] .= $line . "\n";
				} else if ($part=="qtype") {
					if ($qnum>-1) {
						$qdata[$qnum]['qtype'] .= $line;
					}
				} else {
					if ($qnum>-1) {
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
?>
	
