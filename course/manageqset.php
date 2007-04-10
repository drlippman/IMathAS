<?php
//IMathAS:  Manage questions (outside context of an assessment)
//(c) 2006 David Lippman
	require("../validate.php");
	if ($myrights<20) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$isadmin = false;
	$isgrpadmin = false;
	if (isset($_GET['cid']) && $_GET['cid']==="admin") {
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
	
	
	if (isset($_POST['remove'])) {
		if (isset($_GET['confirmed'])) {
			if ($_POST['remove']!='') {
				$remlist = "'".implode("','",explode(',',$_POST['remove']))."'";
				
				if ($isadmin) {
					$query = "DELETE FROM imas_library_items WHERE qsetid IN ($remlist)";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
					foreach (explode(',',$_POST['remove']) as $qid) {
						delqimgs($qid);
					}
				} else if ($isgrpadmin) {
					$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE ";
					$query .= "imas_questionset.id IN ($remlist) AND imas_questionset.ownerid=imas_users.id ";
					$query .= "AND imas_users.groupid='$groupid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "DELETE FROM imas_library_items WHERE qsetid='$row[0]'";
						mysql_query($query) or die("Query failed : $query" . mysql_error());
						delqimgs($row[0]);
					}
					
				} else {
					$query = "SELECT id FROM imas_questionset WHERE id IN ($remlist) AND ownerid='$userid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "DELETE FROM imas_library_items WHERE qsetid='$row[0]'";
						mysql_query($query) or die("Query failed : $query" . mysql_error());
						delqimgs($row[0]);
					}
				}
				
				if ($isgrpadmin) {
					$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($remlist) AND imas_users.groupid='$groupid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "DELETE FROM imas_questionset WHERE id='{$row[0]}'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					}
				} else {
					$query = "DELETE FROM imas_questionset WHERE id IN ($remlist)";
					if (!$isadmin) {
						$query .= " AND ownerid='$userid'";
					}
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
				
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			
			exit;
		} else {
			if (!isset($_POST['nchecked'])) {
				echo "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
				require("../footer.php");
				exit;
			}
			echo "<h3>Confirm</h3>\n";
			echo "Are you SURE you want to delete these question from the Question Set.  This will make them unavailable ";
			echo "to all users.  If any are currently being used in an assessment, it will mess up that assessment.\n";
			echo "<form method=post action=\"manageqset.php?cid=$cid&confirmed=true\">\n";
			$rlist = implode(",",$_POST['nchecked']);
			echo "<input type=hidden name=remove value=\"$rlist\">\n";
			echo "<p><input type=submit value=\"Really Delete\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='manageqset.php?cid=$cid'\"></p>\n";
		}
	} else if (isset($_POST['transfer'])) {
		if (isset($_POST['newowner'])) {
			if ($_POST['transfer']!='') {
				$translist = "'".implode("','",explode(',',$_POST['transfer']))."'";
				
				if ($isgrpadmin) {
					$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($translist) AND imas_users.groupid='$groupid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id='{$row[0]}'";
						mysql_query($query) or die("Query failed : $query " . mysql_error());
					}
					//$query = "UPDATE imas_questionset,imas_users SET imas_questionset.ownerid='{$_POST['newowner']}' ";
					//$query .= "WHERE imas_questionset.ownerid=imas_users.id AND ";
					//$query .= "imas_questionset.id IN ($translist) AND imas_users.groupid='$groupid'";
				} else {
					$query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id IN ($translist)";
					if (!$isadmin) {
						$query .= " AND ownerid='$userid'";
					}
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
		
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			
			exit;
		} else {
			echo "<h3>Transfer Ownership</h3>\n";
			echo "<form method=post action=\"manageqset.php?cid=$cid\">\n";
			if (!isset($_POST['nchecked'])) {
				echo "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
				require("../footer.php");
				exit;
			}
			$tlist = implode(",",$_POST['nchecked']);
			echo "<input type=hidden name=transfer value=\"$tlist\">\n";
			echo "Transfer to: <select name=newowner>\n";
			$query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				echo "<option value=\"$row[0]\">$row[2], $row[1]</option>\n";
			}
			echo "</select>\n";
			echo "<p><input type=submit value=\"Transfer\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='manageqset.php?cid=$cid'\"></p>\n";
			echo "</form>\n";
			
		}
	} else if (isset($_POST['chglib'])) {
		if (isset($_POST['qtochg'])) {
			if ($_POST['chglib']!='') {
				$newlibs = $_POST['libs']; //array is sanitized later
				if ($_POST['libs']=='') {
					$newlibs = array();
				} else {
					if ($newlibs[0]==0 && count($newlibs)>1) {
						array_shift($newlibs);
					}
				}
				
				$libarray = explode(',',$_POST['qtochg']); //qsetids to change
				if ($_POST['qtochg']=='') {
					$libarray = array();
				}
				$chglist = "'".implode("','",$libarray)."'";
				/*
				if (isset($libarray) && count($libarray)>0 && count($newlibs)>0) {
					if (isset($_POST['rem']) && $_POST['rem']=="rem" && count($qtochg)>0) {
						//$chglist = implode(",",$qtochg);
						$query = "DELETE FROM imas_library_items WHERE qsetid IN ($chglist)";
						if (!$isadmin) {
							$query .= "AND ownerid='$userid'";
						}
						mysql_query($query) or die("Query failed :$query " . mysql_error());
					}
					
					foreach ($libarray as $qid) {
						foreach($newlibs as $lib) {
							$query = "INSERT INTO imas_library_items (qsetid,libid,ownerid) VALUES ($qid,'$lib','$userid')";
							mysql_query($query) or die("Query failed :$query " . mysql_error());
							//$query = "DELETE FROM imas_library_items WHERE qsetid=$qid and libid=0";
							//mysql_query($query) or die("Query failed :$query " . mysql_error());
						}
					}
					$query = "DELETE FROM imas_library_items WHERE qsetid IN ($chglist) AND libid=0";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
				}
				*/
				if ($_POST['onlyadd']==1) { //only adding to newly check libs
					$query = "SELECT libid FROM imas_library_items WHERE qsetid IN ($chglist)";
					$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$checked[] = $row[0];	
					}
					
					$toadd = array_values(array_diff($newlibs,$checked));
					if (count($toadd)>0) { 
						foreach ($libarray as $qsetid) {
							foreach($toadd as $libid) { 
								$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('$libid','$qsetid','$userid')";
								mysql_query($query) or die("Query failed :$query " . mysql_error());
							} 
						}
					}
					
				} //making changes
				foreach ($libarray as $qsetid) {
					if ($isgrpadmin) {
						$query = "SELECT imas_library_items.libid FROM imas_library_items,imas_users WHERE ";
						$query .= "imas_library_items.ownerid=imas_users.id AND imas_users.groupid='$groupid' ";
						$query .= "AND imas_library_items.qsetid='$qsetid'";
					} else {
						$query = "SELECT libid FROM imas_library_items WHERE qsetid='$qsetid'";
						if (!$isadmin) {
							$query .= " AND (ownerid='$userid' OR libid=0)";
						}
					}
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					$existing = array();
					while($row = mysql_fetch_row($result)) { 
						$existing[] = $row[0]; 
					}  
					if ($_POST['onlyadd']==1) { //don't do per question adding here; already did new check adds
						$toadd = array();	
					} else {
						$toadd = array_values(array_diff($newlibs,$existing));
					}
					$toremove = array_values(array_diff($existing,$newlibs));
										
					while(count($toremove)>0 && count($toadd)>0) { 
						$tochange = array_shift($toremove); 
						$torep = array_shift($toadd); 
						$query = "UPDATE imas_library_items SET libid='$torep' WHERE qsetid='$qsetid' AND libid='$tochange'";
						mysql_query($query) or die("Query failed :$query " . mysql_error());
					} 
					if (count($toadd)>0) { 
						foreach($toadd as $libid) { 
							$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('$libid','$qsetid','$userid')";
							mysql_query($query) or die("Query failed :$query " . mysql_error());
						} 
					} else if (count($toremove)>0) { 
						foreach($toremove as $libid) { 
							$query = "DELETE FROM imas_library_items WHERE libid='$libid' AND qsetid='$qsetid'";
							mysql_query($query) or die("Query failed :$query " . mysql_error());
						} 
					} 
					if (count($newlibs)==0) {
						$query = "SELECT id FROM imas_library_items WHERE qsetid='$qsetid'";
						$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
						if (mysql_num_rows($result)==0) {
							$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES (0,'$qsetid','$userid')";
							mysql_query($query) or die("Query failed :$query " . mysql_error());
						}
					}
					
				}
				
				
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			
			exit;
		} else {
			require("../header.php");
			echo "<h3>Modify Library Assignments</h3>\n";
			echo "<form method=post action=\"manageqset.php?cid=$cid\">\n";
			if (!isset($_POST['nchecked'])) {
				echo "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
				require("../footer.php");
				exit;
			}
			$clist = implode(",",$_POST['nchecked']);
			echo "<input type=hidden name=chglib value=\"true\">\n";
			echo "<input type=hidden name=qtochg value=\"$clist\">\n";
			/*
			$query = "SELECT id,name FROM imas_libraries WHERE (ownerid='$userid' OR userights=2)";
			$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$libs[$row[0]] = $row[1];	
			}
			*/
			if (!$isadmin && !$isgrpadmin) {
				$query = "SELECT libid FROM imas_library_items WHERE qsetid IN ($clist) AND ownerid!='$userid'";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$locked[] = $row[0];	
				}
			} else if ($isgrpadmin) {
				$query = "SELECT ili.libid FROM imas_library_items AS ili,imas_users WHERE ";
				$query .= "ili.ownerid=imas_users.id AND imas_users.groupid!='$groupid' AND ili.qsetid IN ($clist)";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$locked[] = $row[0];	
				}
			}
			if ($isgrpadmin) {
				$query = "SELECT ili.libid FROM imas_library_items AS ili,imas_users WHERE ";
				$query .= "ili.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ili.qsetid IN ($clist)";
			} else {
				$query = "SELECT libid FROM imas_library_items WHERE qsetid IN ($clist)";
				if (!$isadmin) {
					$query .= " AND ownerid='$userid'";
				}
			}
			$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$checked[] = $row[0];	
			}
			echo "Add to libraries: <br>\n";
			
			include("libtree.php");
			/*if ($isadmin) {
				echo "<p><input type=checkbox name=rem value=\"rem\">Remove <b>all</b> other library assignments</p>\n";
			} else {
				echo "<p><input type=checkbox name=rem value=\"rem\">Remove my other library assignments</p>\n";
			}
			*/
			echo "<p>Add selected questions to: <input type=radio name=\"onlyadd\" value=\"1\" CHECKED>Only newly checked libraries, <input type=radio name=\"onlyadd\" value=\"0\">All checked libraries <br/>\n";
			echo "Question will be removed from any unchecked libraries</p>\n";
			echo "<p><input type=submit value=\"Update Libraries\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='manageqset.php?cid=$cid'\"></p>\n";
			echo "</form>\n";
			
		}		
		
		
	} else if (isset($_POST['template'])) {
		if (isset($_POST['qtochg'])) {
			if (!isset($_POST['libs'])) {
				echo "<html><body>No library selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a></body></html>\n";
				exit;
			}
			$lib = $_POST['libs'];
			$qtochg = explode(',',$_POST['qtochg']);
			$now = time();
			$query = "SELECT firstName,lastName FROM imas_users WHERE id='$userid'";
			$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
			$row = mysql_fetch_row($result);
			$myname = $row[1].','.$row[0];
			foreach ($qtochg as $k=>$qid) {
				$query = "SELECT description,userights,qtype,control,qcontrol,qtext,answer,hasimg FROM imas_questionset WHERE id='$qid'";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				$row = mysql_fetch_row($result);
				$mt = microtime();
				$uqid = substr($mt,11).substr($mt,2,3).$k;
				$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,author,description,userights,qtype,control,qcontrol,qtext,answer,hasimg) VALUES ";
				$query .= "('$uqid','$now','$now','$userid','$myname','".implode("','",addslashes_deep($row))."')";
				mysql_query($query) or die("Query failed : $query" . mysql_error());
				$nqid = mysql_insert_id();
				$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('$lib','$nqid','$userid')";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
				
				$query = "SELECT var,filename,alttext FROM imas_qimages WHERE qsetid='$qid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$query = "INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES ('$nqid','{$row[0]}','{$row[1]}','{$row[2]}')";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
				}
				
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			
			exit;
		} else {
			require("../header.php");
			if (!isset($_POST['nchecked'])) {
				echo "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
				require("../footer.php");
				exit;
			}
			echo "<h3>Template Questions</h3>";
			echo "<form method=post action=\"manageqset.php?cid=$cid\">";
			echo "<input type=hidden name=template value=\"true\">\n";
			
			echo "<p>This page will create new copies of these questions.  It is recommended that you place these new copies in a ";
			echo "different library that the questions are currently are in, so you can distinguish the new versions from the originals.</p>";
			echo "<p>Select the library into which to put the new copies:</p>";
			
			$clist = implode(",",$_POST['nchecked']);
			echo "<input type=hidden name=qtochg value=\"$clist\">\n";
			
			$selecttype = "radio";
			include("libtree.php");
			echo "<p><input type=submit value=\"Template Questions\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='manageqset.php?cid=$cid'\"></p>\n";
			echo "</form>\n";
		}
		
	} else if (isset($_GET['remove'])) {
		if (isset($_GET['confirmed'])) {
			if ($isgrpadmin) {
				$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE ";
				$query .= "imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ";
				$query .= "imas_questionset.id='{$_GET['remove']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$query = "DELETE FROM imas_questionset WHERE id='{$_GET['remove']}'";
				} else {
					header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
					exit;
				}
				//$query = "DELETE imas_questionset FROM imas_questionset,imas_users WHERE ";
				//$query .= "imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ";
				//$query .= "imas_questionset.id='{$_GET['remove']}'";
			} else {
				$query = "DELETE FROM imas_questionset WHERE id='{$_GET['remove']}'";
				if (!$isadmin) {
					$query .= " AND ownerid='$userid'";
				}
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_affected_rows($link)>0) {
				$query = "DELETE FROM imas_library_items WHERE qsetid='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				delqimgs($_GET['remove']);
			}
			
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			
			exit;
		} else {
			require("../header.php");
			echo "<h3>Confirm</h3>\n";
			echo "Are you SURE you want to delete this question from the Question Set.  This will make it unavailable ";
			echo "to all users.  If it is currently being used in an assessment, it will mess up that assessment.\n";
			echo "<p><input type=button onclick=\"window.location='manageqset.php?cid=$cid&remove={$_GET['remove']}&confirmed=true'\" value=\"Really Delete\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='manageqset.php?cid=$cid'\"></p>\n";
		}
	} else if (isset($_GET['transfer'])) {
		if (isset($_POST['newowner'])) {
			
			if ($isgrpadmin) {
				$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id='{$_GET['transfer']}' AND imas_users.groupid='$groupid'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id='{$_GET['transfer']}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
				//$query = "UPDATE imas_questionset,imas_users SET imas_questionset.ownerid='{$_POST['newowner']}' ";
				//$query .= "WHERE imas_questionset.ownerid=imas_users.id AND ";
				//$query .= "imas_questionset.id='{$_GET['transfer']}' AND imas_users.groupid='$groupid'";
			} else {
				$query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id='{$_GET['transfer']}'";
				if (!$isadmin) {
					$query .= " AND ownerid='$userid'";
				}
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			exit;
		} else {
			require("../header.php");
			echo "<h3>Transfer Ownership</h3>\n";
			echo "<form method=post action=\"manageqset.php?cid=$cid&transfer={$_GET['transfer']}\">\n";
			echo "Transfer to: <select name=newowner>\n";
			$query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				echo "<option value=\"$row[0]\">$row[2], $row[1]</option>\n";
			}
			echo "</select>\n";
			echo "<p><input type=submit value=\"Transfer\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='manageqset.php?cid=$cid'\"></p>\n";
			echo "</form>\n";
		}
	
	} else {
		$pagetitle = "Manage Question Set";
		require("../header.php");
		$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		echo <<<END
<script type="text/javascript">
function previewq(formn,loc,qn) {
	var addr = '$imasroot/course/testquestion.php?cid=$cid&checked=0&qsetid='+qn+'&loc=qo'+loc+'&formn='+formn;
	previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));
	previewpop.focus();
	//window.open(addr,'Testing','width=400,height=300,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
var baseaddr = '$address';
function doaction(todo,id) {
	var addrmod = baseaddr+'/moddataset.php?cid=$cid&id=';
	var addrtemp = baseaddr+'/moddataset.php?cid=$cid&template=true&id=';
	var addrmq = baseaddr+'/manageqset.php?cid=$cid';
	if (todo=="mod") {
		addr = addrmod+id;
	} else if (todo=="temp") {
		addr = addrtemp+id;
	} else if (todo=="del") {
		addr = addrmq+'&remove='+id;
	} else if (todo=="tr") {
		addr = addrmq+'&transfer='+id;
	}
	window.location = addr;
}

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
END;
		if ($isadmin || $isgrpadmin) {
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../admin/admin.php\">Admin</a> &gt; Manage Question Set</div>\n";
		} else if ($cid==0) {
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; Manage Question Set</div>\n";
		} else {
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> &gt; Manage Question Set</div>\n";
		}
		echo "<h3>Question Set Management <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=managequestionset','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h3>\n";
		if ($isadmin) {
			echo "You are in Admin mode, which means actions will apply to all questions, regardless of owner";
		} else if ($isgrpadmin) {
			echo "You are in Group Admin mode, which means actions will apply to all group's questions, regardless of owner";
		}
		//remember search
		if (isset($_POST['search'])) {
			$safesearch = $_POST['search'];
			$safesearch = str_replace(' and ', ' ',$safesearch);
			$search = stripslashes($safesearch);
			$search = str_replace('"','&quot;',$search);
			$sessiondata['lastsearch'.$cid] = str_replace(" ","+",$safesearch);
			if (isset($_POST['searchall'])) {
				$searchall = 1;
			} else {
				$searchall = 0;
			}
			$sessiondata['searchall'.$cid] = $searchall;
			if (isset($_POST['searchmine'])) {
				$searchmine = 1;
			} else {
				$searchmine = 0;
			}
			$sessiondata['searchmine'.$cid] = $searchmine;
			writesessiondata();
		} else if (isset($sessiondata['lastsearch'.$cid])) {
			$safesearch = str_replace("+"," ",$sessiondata['lastsearch'.$cid]);
			$search = stripslashes($safesearch);
			$search = str_replace('"','&quot;',$search);
			$searchall = $sessiondata['searchall'.$cid];
			$searchmine = $sessiondata['searchmine'.$cid];
		} else {
			$search = '';
			$searchall = 0;
			$searchmine = 0;
			$safesearch = '';
		}
		if (trim($safesearch)=='') {
			$searchlikes = '';
		} else {
			$searchterms = explode(" ",$safesearch);
			$searchlikes = "(imas_questionset.description LIKE '%".implode("%' AND imas_questionset.description LIKE '%",$searchterms)."%') AND ";
		}
		
		if (isset($_POST['libs'])) {
			if ($_POST['libs']=='') {
				$_POST['libs'] = '0';
			}
			$searchlibs = $_POST['libs'];
			//$sessiondata['lastsearchlibs'] = implode(",",$searchlibs);
			$sessiondata['lastsearchlibs'.$cid] = $searchlibs;
			writesessiondata();
		} else if (isset($_GET['listlib'])) {
			$searchlibs = $_GET['listlib'];
			$sessiondata['lastsearchlibs'.$cid] = $searchlibs;
			$searchall = 0;
			$sessiondata['searchall'.$cid] = $searchall;
			writesessiondata();
		}else if (isset($sessiondata['lastsearchlibs'.$cid])) {
			//$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
			$searchlibs = $sessiondata['lastsearchlibs'.$cid];
		} else {
			$searchlibs = '0';
		}
		
		$llist = "'".implode("','",explode(',',$searchlibs))."'";
		
		echo "<form method=post action=\"manageqset.php?cid=$cid\">\n";
		echo <<<END
<script>
var curlibs = '$searchlibs';
function libselect() {
	window.open('libtree2.php?cid=$cid&libtree=popup&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn;
}
function getnextprev(formn,loc) {
	var form = document.getElementById(formn);
	var prevq = 0; var nextq = 0; var found=false;
	var prevl = 0; var nextl = 0;
	for (var e = 0; e < form.elements.length; e++) {
		var el = form.elements[e];
		if (typeof el.type == "undefined") {
			continue;
		}
		if (el.type == 'checkbox' && el.name=='nchecked[]') {
			if (found) {
				nextq = el.value;
				nextl = el.id;
				break;
			} else if (el.id==loc) {
				found = true;
			} else {
				prevq = el.value;
				prevl = el.id;
			}
		}
	}
	return ([[prevl,prevq],[nextl,nextq]]);
}
</script>
END;
		if (substr($searchlibs,0,1)=="0") {
			$lnamesarr[0] = "Unassigned";
		}
		$query = "SELECT name,id FROM imas_libraries WHERE id IN ($llist)";
		$result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$lnamesarr[$row[1]] = $row[0];
		}
		if (count($lnamesarr)>0) {
			$lnames = implode(", ",$lnamesarr);
		} else {$lnames = '';}
		echo "In Libraries: <span id=\"libnames\">$lnames</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"$searchlibs\">\n";
		echo " <input type=button value=\"Select Libraries\" onClick=\"libselect()\"> <br>"; 
		
		echo "Search: <input type=text size=15 name=search value=\"$search\"> <input type=checkbox name=\"searchall\" value=\"1\" ";
		if ($searchall==1) {echo "checked=1";}
		echo "/>Search all libs <input type=checkbox name=\"searchmine\" value=\"1\" ";
		if ($searchmine==1) {echo "checked=1";}
		echo "/>Mine only ";
		
		echo "<input type=submit value=Search>\n";
		echo "<input type=button value=\"Add New Question\" onclick=\"window.location='moddataset.php?cid=$cid'\">\n";
		echo "</form>";
		
		if ($searchall==1 && trim($search)=='') {
			echo "Must provide a search term when searching all libraries";
			require("../footer.php");
			exit;
		}
		$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.ownerid,imas_questionset.description,imas_questionset.userights,imas_questionset.lastmoddate,";
		$query .= "imas_questionset.qtype,imas_users.firstName,imas_users.lastName,imas_users.groupid,imas_library_items.libid ";
		$query .= "FROM imas_questionset,imas_library_items,imas_users WHERE $searchlikes ";
		$query .= "imas_library_items.qsetid=imas_questionset.id AND imas_questionset.ownerid=imas_users.id ";
			
		if ($isadmin) {
			if ($searchall==0) {
				$query .= "AND imas_library_items.libid IN ($llist)";
			}
			if ($searchmine==1) {
				$query .= " AND imas_questionset.ownerid='$userid'";
			}
		} else if ($isgrpadmin) {
			$query .= "AND (imas_users.groupid='$groupid' OR imas_questionset.userights>0) "; 
			$query .= "AND (imas_library_items.libid > 0 OR imas_users.groupid='$groupid')";
			if ($searchall==0) {
				$query .= " AND imas_library_items.libid IN ($llist)";
			}
			if ($searchmine==1) {
				$query .= " AND imas_questionset.ownerid='$userid'";
			}
		} else {
			$query .= "AND (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0) "; 
			$query .= "AND (imas_library_items.libid > 0 OR imas_questionset.ownerid='$userid')";	
			if ($searchall==0) {
				$query .= " AND imas_library_items.libid IN ($llist)";
			}
			if ($searchmine==1) {
				$query .= " AND imas_questionset.ownerid='$userid'";
			}
		}
		$query.= " ORDER BY imas_library_items.libid,imas_questionset.id";
		
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
		echo "<form id=\"selform\" method=post action=\"manageqset.php?cid=$cid\">\n";
		echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'nchecked[]', this.checked)\">\n";	
		echo "With Selected: <input type=submit name=\"transfer\" value=\"Transfer\">\n";
		echo "<input type=submit name=\"remove\" value=\"Delete\">\n";
		echo "<input type=submit name=\"chglib\" value=\"Library Assignment\">\n";
		echo "<input type=submit name=\"template\" value=\"Template\">\n";
		if (!$isadmin && !$isgrpadmin) { 
			echo "<br/>(Delete and Transfer only applies to your questions)\n";
		} else if ($isgrpadmin) {
			echo "<br/>(Delete and Transfer only apply to group's questions)\n";
		}
		echo "<table id=myTable class=gb><thead>\n";
		echo "<tr><th>&nbsp;</th><th>Description</th><th>&nbsp;</th><th>Action</th><th>Type</th><th>Times Used</th><th>Last Mod</th>";
		if ($isadmin || $isgrpadmin) { echo "<th>Owner</th>";} else {echo "<th>Mine</th>";}
		if ($searchall==1) {
			echo "<th>Library</th>";
		}
		//echo "<th>Source</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th></tr>\n";
		echo "</tr>\n";
		echo "</thead><tbody>\n";
		$alt = 0;
		$lastlib = -1;
		$ln = 1;
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($lastlib!=$line['libid'] && isset($lnamesarr[$line['libid']])) {
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
				echo "<td></td><td><b>".$lnamesarr[$line['libid']]."</b></td>";
				for ($j=0;$j<9+$searchall;$j++) {
					echo "<td></td>";
				}
				echo "</tr>";
				$lastlib = $line['libid'];
			}
			$query = "SELECT COUNT(id) FROM imas_questions WHERE questionsetid='{$line['id']}'";
			$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
			$times = mysql_result($result2,0,0);
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
			echo "<td><input type=checkbox name='nchecked[]' id='qo$ln' value='{$line['id']}'></td>\n";
			echo "<td>{$line['description']}</td>";
			echo "<td><input type=button value=\"Preview\" onClick=\"previewq('selform',$ln,{$line['id']})\"/></td>\n";
			echo "<td><select onchange=\"doaction(this.value,{$line['id']})\"><option value=\"0\">Action..</option>";
			if ($isadmin || ($isgrpadmin && $line['groupid']==$groupid) || $line['ownerid']==$userid || $line['userights']>2) {
				echo '<option value="mod">Modify Code</option>';
			} else {
				echo '<option value="mod">View Code</option>';
			}
			echo '<option value="temp">Template</option>';
			if ($isadmin || ($isgrpadmin && $line['groupid']==$groupid) || $line['ownerid']==$userid) {
				echo '<option value="del">Delete</option>';
				echo '<option value="tr">Transfer</option>';
			} 
			echo '</select></td>';
			echo "<td>{$line['qtype']}</td><td class=c>$times</td>\n";
			$lastmod = date("m/d/y",$line['lastmoddate']);
			echo "<td>$lastmod</td>";
			if ($isadmin) {
				$ownername = $line['lastName'] . ',' . substr($line['firstName'],0,1);
				echo "<td class=c>$ownername</td>\n";
			} else {
				if ($line['ownerid']==$userid) { echo "<td>Yes</td>";} else {echo "<td></td>";}
			}
			if ($searchall==1) {
				echo "<td><a href=\"manageqset.php?cid=$cid&listlib={$line['libid']}\">List lib</a></td>";
			}
			/*
			if ($isadmin || ($isgrpadmin && $line['groupid']==$groupid) || $line['ownerid']==$userid || $line['userights']>2) {
				echo "<td><a href=\"moddataset.php?cid=$cid&id={$line['id']}\">Modify</a></td>\n";
			} else {
				echo "<td><a href=\"moddataset.php?cid=$cid&id={$line['id']}\">View</a></td>\n";
				//echo "<td><a href=\"viewsource.php?cid=$cid&id={$line['id']}\">View</a></td>\n";
			}
			echo "<td><a href=\"moddataset.php?cid=$cid&id={$line['id']}&template=true\">Template</a></td>\n";
			if ($isadmin || ($isgrpadmin && $line['groupid']==$groupid) || $line['ownerid']==$userid) {
				echo "<td><a href=\"manageqset.php?cid=$cid&remove={$line['id']}\">Delete</a></td>\n";
			} else {
				echo "<td></td>\n";
			}
			if ($isadmin || ($isgrpadmin && $line['groupid']==$groupid) || $line['ownerid']==$userid) {
				echo "<td><a href=\"manageqset.php?cid=$cid&transfer={$line['id']}\">Transfer</a></td>\n";
			} else {
				echo "<td></td>\n";
			}
			*/
			
			echo "</tr>\n";
			$ln++;
		}
		echo "</tbody></table>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "initSortTable('myTable',Array(false,'S',false,false,'S','N','D'";
		//if ($isadmin) { echo "'N',";} else {echo "'S',";}
		echo ",'S'";
		//echo "false,false,false,false),true);\n";
		echo "),true);\n";
		echo "</script>\n";
		echo "</form>\n";
		echo "<p></p>\n";
	}
	require("../footer.php");
	
	function delqimgs($qsid) {
		$query = "SELECT id,filename,var FROM imas_qimages WHERE qsetid='$qsid'";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$query = "SELECT id FROM imas_qimages WHERE filename='{$row[1]}'";
			$r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
			if (mysql_num_rows($r2)==1) { //don't delete if file is used in other questions
				unlink(rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/'.$row[1]);
			}
			$query = "DELETE FROM imas_qimages WHERE id='{$row[0]}'";
			mysql_query($query) or die("Query failed :$query " . mysql_error());
		}
	}
?>
