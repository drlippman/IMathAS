<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");


 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Review Library";
$page_updatedMsg = "";

	//CHECK PERMISSIONS AND SET FLAGS
if ($myrights<20) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {
	//data manipulation here

	$isadmin = false;
	$isgrpadmin = false;

	$cid = Sanitize::courseId($_GET['cid']);
	if (isset($_GET['source'])) {
		$source = Sanitize::onlyInt($_GET['source']);
	} else {
		$source = 0;
	}

	if ($_GET['cid']==="admin") {
		$curBreadcrumb = "$breadcrumbbase <a href=\"../admin/admin2.php\">Admin</a>";
		$curBreadcrumb .= "&gt; <a href=\"managelibs.php?cid=admin\">Manage Libraries</a> &gt; Review Library";
		if ($myrights == 100) {
			$isadmin = true;
		} else if ($myrights==75) {
			$isgrpadmin = true;
		}
	} else if ($_GET['cid']==0) {
		$curBreadcrumb = "<a href=\"../index.php\">Home</a> ";
		$curBreadcrumb .= "&gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Review Library";
	} else {
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a>";
		$curBreadcrumb .= "&gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Review Library";
	}

	if (!isset($_REQUEST['lib'])) {

		if (isset($sessiondata['lastsearchlibs'])) {
			//$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
			$inlibs = $sessiondata['lastsearchlibs'];
		} else {
			$inlibs = '0';
		}
		if (substr($inlibs,0,1)=='0') {
			$lnames[] = "Unassigned";
		}
		//DB $inlibssafe = "'".implode("','",explode(',',$inlibs))."'";
		$inlibssafe = implode(',', array_map('intval', explode(',',$inlibs)));
		//DB $query = "SELECT name FROM imas_libraries WHERE id IN ($inlibssafe)";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT name FROM imas_libraries WHERE id IN ($inlibssafe)");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$lnames[] = $row[0];
		}
		$lnames = implode(", ",$lnames);

	} else {

		$lib = Sanitize::onlyInt($_REQUEST['lib']);
		if (isset($_GET['offset'])) {
			$offset = Sanitize::onlyInt($_GET['offset']);
		} else {
			$offset = 0;
		}

		//DB $query = "SELECT count(qsetid) FROM imas_library_items WHERE libid='$lib'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $cnt = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT count(qsetid) FROM imas_library_items WHERE libid=:libid AND deleted=0");
		$stm->execute(array(':libid'=>$lib));
		$cnt = $stm->fetchColumn(0);
		if ($cnt==0) {
			$overwriteBody = 1;
			$body = "Library empty";
		}

		//DB $query = "SELECT qsetid FROM imas_library_items WHERE libid='$lib' LIMIT $offset,1";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $qsetid = mysql_result($result,0,0);
		$offset = intval($offset);
		$stm = $DBH->prepare("SELECT qsetid FROM imas_library_items WHERE libid=:libid AND deleted=0 LIMIT $offset,1");
		$stm->execute(array(':libid'=>$lib));
		$qsetid = $stm->fetchColumn(0);


		if (isset($_POST['remove']) || isset($_POST['delete'])) {
			if (!isset($_POST['confirm'])) {

				if (isset($_POST['remove'])) {
					$page_ConfirmMsg = "<p>Are you SURE you want to remove this question from this library?</p><input type=hidden name=remove value=1>";
				}
				if (isset($_POST['delete'])) {
					$page_ConfirmMsg = "<p>Are you SURE you want to delete this question?  Question will be removed from ALL libraries.</p><input type=hidden name=delete value=1>";
				}

			} else {
				if (isset($_POST['delete'])) {
					if ($isgrpadmin) {
						//DB $query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE ";
						//DB $query .= "imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ";
						//DB $query .= "imas_questionset.id='$qsetid'";
						//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
						//DB if (mysql_num_rows($result)>0) {
						$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE ";
						$query .= "imas_questionset.ownerid=imas_users.id AND imas_users.groupid=:groupid AND ";
						$query .= "imas_questionset.id=:id";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':groupid'=>$groupid, ':id'=>$qsetid));
						if ($stm->rowCount()>0) {
							//$query = "DELETE FROM imas_questionset WHERE id='$qsetid'";
							//DB $query = "UPDATE imas_questionset SET deleted=1 WHERE id='$qsetid'";
							//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
							//DB if (mysql_affected_rows()>0) {
							$stm = $DBH->prepare("UPDATE imas_questionset SET deleted=1 WHERE id=:id");
							$stm->execute(array(':id'=>$qsetid));
							if ($stm->rowCount()>0) {
								//DB $query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid'";
								//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
								$stm = $DBH->prepare("DELETE FROM imas_library_items WHERE qsetid=:qsetid");
								$stm->execute(array(':qsetid'=>$qsetid));
								//delqimgs($qsetid);
								$cnt--;
							}
						}
					} else {
						//$query = "DELETE FROM imas_questionset WHERE id='$qsetid'";

						if (!$isadmin) {
							//DB $query = "UPDATE imas_questionset SET deleted=1 WHERE id='$qsetid' AND ownerid='$userid'";
							$stm = $DBH->prepare("UPDATE imas_questionset SET deleted=1 WHERE id=:id AND ownerid=:ownerid");
							$stm->execute(array(':id'=>$qsetid, ':ownerid'=>$userid));
						} else {
							//DB $query = "UPDATE imas_questionset SET deleted=1 WHERE id='$qsetid'";
							$stm = $DBH->prepare("UPDATE imas_questionset SET deleted=1 WHERE id=:id");
							$stm->execute(array(':id'=>$qsetid));
						}
						//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						//DB if (mysql_affected_rows()>0) {
						if ($stm->rowCount()>0) {
							//DB $query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid'";
							//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
							$stm = $DBH->prepare("DELETE FROM imas_library_items WHERE qsetid=:qsetid");
							$stm->execute(array(':qsetid'=>$qsetid));
							//delqimgs($qsetid);
							$cnt--;
						}
					}
					//$query = "DELETE FROM imas_questionset WHERE id='$qsetid'";

				}
				if (isset($_POST['remove'])) {
					$madechange = false;
					if ($isgrpadmin) {
						//$query = "DELETE imas_library_items FROM imas_library_items,imas_users WHERE ";
						//$query .= "imas_library_items.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ";
						//$query .= "imas_library_items.qsetid='$qsetid' AND imas_library_items.libid='$libid'";
						//DB $query = "SELECT imas_library_items FROM imas_library_items,imas_users WHERE ";
						//DB $query .= "imas_library_items.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ";
						//DB $query .= "imas_library_items.qsetid='$qsetid' AND imas_library_items.libid='$lib'";
						//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						//DB if (mysql_num_rows($result)>0) {
						$query = "SELECT imas_library_items FROM imas_library_items,imas_users WHERE ";
						$query .= "imas_library_items.ownerid=imas_users.id AND imas_users.groupid=:groupid AND ";
						$query .= "imas_library_items.qsetid=:qsetid AND imas_library_items.libid=:libid ";
						$query .= "AND imas_library_items.deleted=0";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':groupid'=>$groupid, ':qsetid'=>$qsetid, ':libid'=>$lib));
						if ($stm->rowCount()>0) {
							//DB $query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid' AND libid='$lib'";
							//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
							//DB if (mysql_affected_rows()>0) {
							$stm = $DBH->prepare("DELETE FROM imas_library_items WHERE qsetid=:qsetid AND libid=:libid");
							$stm->execute(array(':qsetid'=>$qsetid, ':libid'=>$lib));
							if ($stm->rowCount()>0) {
								$madechange = true;
							}
						}

					} else {

						if (!$isadmin) {
							//DB $query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid' AND libid='$lib' AND ownerid='$userid'";
							$stm = $DBH->prepare("DELETE FROM imas_library_items WHERE qsetid=:qsetid AND libid=:libid AND ownerid=:ownerid");
							$stm->execute(array(':qsetid'=>$qsetid, ':libid'=>$lib, ':ownerid'=>$userid));
						} else {
							//DB $query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid' AND libid='$lib'";
							$stm = $DBH->prepare("DELETE FROM imas_library_items WHERE qsetid=:qsetid AND libid=:libid");
							$stm->execute(array(':qsetid'=>$qsetid, ':libid'=>$lib));
						}
						//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						//DB if (mysql_affected_rows()>0) {
						if ($stm->rowCount()>0) {
							$madechange = true;
						}
					}
					if ($madechange) {
						//DB $query = "SELECT id FROM imas_library_items WHERE qsetid='$qsetid'";
						//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						//DB if (mysql_num_rows($result)==0) {
						$stm = $DBH->prepare("SELECT id FROM imas_library_items WHERE qsetid=:qsetid AND deleted=0");
						$stm->execute(array(':qsetid'=>$qsetid));
						if ($stm->rowCount()==0) {
							//DB $query = "INSERT INTO imas_library_items (qsetid,libid,ownerid) VALUES ('$qsetid',0,$userid)";
							//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
							$stm = $DBH->prepare("INSERT INTO imas_library_items (qsetid,libid,ownerid) VALUES (:qsetid, :libid, :ownerid)");
							$stm->execute(array(':qsetid'=>$qsetid, ':libid'=>0, ':ownerid'=>$userid));
						}
						$cnt--;
					}
				}

				if ($offset==$cnt) { //Just deleted last problem in library
					if ($offset == 0) { //if already on first question
						$overwriteBody = 1;
						$body = "Library empty";
					} else {  //go back to last question
						$offset--;
					}
				}
				//DB $query = "SELECT qsetid FROM imas_library_items WHERE libid='$lib' LIMIT $offset,1";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB $qsetid = mysql_result($result,0,0);
				$offset = intval($offset);
				$stm = $DBH->prepare("SELECT qsetid FROM imas_library_items WHERE libid=:libid AND deleted=0 LIMIT $offset,1");
				$stm->execute(array(':libid'=>$lib));
				$qsetid = $stm->fetchColumn(0);
			}
		} elseif (isset($_POST['update'])) {
			$_POST['qtext'] = preg_replace('/<([^<>]+?)>/',"&&&L$1&&&G",$_POST['qtext']);
			$_POST['qtext'] = str_replace(array("<",">"),array("&lt;","&gt;"),$_POST['qtext']);
			$_POST['qtext'] = str_replace(array("&&&L","&&&G"),array("<",">"),$_POST['qtext']);
			$_POST['description'] = str_replace(array("<",">"),array("&lt;","&gt;"),$_POST['description']);
			$now = time();

			if ($isgrpadmin) {
				//DB $query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
				//DB $query .= "WHERE iq.id='$qsetid' AND iq.ownerid=imas_users.id AND (imas_users.groupid='$groupid' OR iq.userights>3)";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB if (mysql_num_rows($result)>0) {
				$query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
				$query .= "WHERE iq.id=:id AND iq.ownerid=imas_users.id AND (imas_users.groupid=:groupid OR iq.userights>3)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':id'=>$qsetid, ':groupid'=>$groupid));
				if ($stm->rowCount()>0) {
					//DB $query = "UPDATE imas_questionset SET description='{$_POST['description']}',";
					//DB $query .= "qtype='{$_POST['qtype']}',control='{$_POST['control']}',qcontrol='{$_POST['qcontrol']}',";
					//DB $query .= "qtext='{$_POST['qtext']}',answer='{$_POST['answer']}',lastmoddate=$now ";
					//DB $query .= "WHERE id='$qsetid'";
					//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					$query = "UPDATE imas_questionset SET description=:description,";
					$query .= "qtype=:qtype,control=:control,qcontrol=:qcontrol,";
					$query .= "qtext=:qtext,answer=:answer,lastmoddate=:lastmoddate ";
					$query .= "WHERE id=:id";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':description'=>$_POST['description'], ':qtype'=>$_POST['qtype'], ':control'=>$_POST['control'],
						':qcontrol'=>$_POST['qcontrol'], ':qtext'=>$_POST['qtext'], ':answer'=>$_POST['answer'], ':lastmoddate'=>$now, ':id'=>$qsetid));
				}

			} else {
				//DB $query = "UPDATE imas_questionset SET description='{$_POST['description']}',";
				//DB $query .= "qtype='{$_POST['qtype']}',control='{$_POST['control']}',qcontrol='{$_POST['qcontrol']}',";
				//DB $query .= "qtext='{$_POST['qtext']}',answer='{$_POST['answer']}',lastmoddate=$now ";
				//DB $query .= "WHERE id='$qsetid'";
				$query = "UPDATE imas_questionset SET description=:description,";
				$query .= "qtype=:qtype,control=:control,qcontrol=:qcontrol,";
				$query .= "qtext=:qtext,answer=:answer,lastmoddate=:lastmoddate ";
				$query .= "WHERE id=:id";
				$qarr = array(':description'=>$_POST['description'], ':qtype'=>$_POST['qtype'], ':control'=>$_POST['control'], ':qcontrol'=>$_POST['qcontrol'],
					':qtext'=>$_POST['qtext'], ':answer'=>$_POST['answer'], ':lastmoddate'=>$now, ':id'=>$qsetid);
				if (!$isadmin) {
					//DB $query .= " AND (ownerid='$userid' OR userights>3);";
					$query .= " AND (ownerid=:ownerid OR userights>3);";
					$qarr[':ownerid']=$userid;
				}
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			}

			$page_updatedMsg = "Question Updated. ";

		}

		//DEFAULT DISPLAY
		if ($offset>0) {
			$last = $offset -1;
			$page_lastLink =  "<a href=\"reviewlibrary.php?cid=$cid&source=$source&offset=$last&lib=$lib\">Last</a> ";
		} else {
			$page_lastLink = "Last ";
		}

		if ($offset<$cnt-1) {
			$next = $offset +1;
			$page_nextLink = "<a href=\"reviewlibrary.php?cid=$cid&source=$source&offset=$next&lib=$lib\">Next</a>";
		} else {
			$page_nextLink = "Next";
		}


		//$query = "SELECT * FROM imas_questionset WHERE id=$qsetid";
		//DB $query = "SELECT imas_library_items.ownerid,imas_users.groupid FROM imas_library_items,imas_users WHERE ";
		//DB $query .= "imas_library_items.ownerid=imas_users.id AND imas_library_items.libid='$lib' AND imas_library_items.qsetid='$qsetid'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$query = "SELECT imas_library_items.ownerid,imas_users.groupid FROM imas_library_items,imas_users WHERE ";
		$query .= "imas_library_items.ownerid=imas_users.id AND imas_library_items.libid=:libid AND imas_library_items.qsetid=:qsetid ";
		$query .= "AND imas_library_items.deleted=0";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':libid'=>$lib, ':qsetid'=>$qsetid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		$myli = (intval($row[0])==$userid);
		if ($isadmin || ($isgrpadmin && intval($row[1])==$groupid)) {
			$myli = true;
		}

		//DB $query = "SELECT imas_questionset.*,imas_users.groupid FROM imas_questionset,imas_users WHERE ";
		//DB $query .= "imas_questionset.ownerid=imas_users.id AND imas_questionset.id='$qsetid'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $lineQSet = mysql_fetch_array($result, MYSQL_ASSOC);
		$query = "SELECT imas_questionset.*,imas_users.groupid FROM imas_questionset,imas_users WHERE ";
		$query .= "imas_questionset.ownerid=imas_users.id AND imas_questionset.id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$qsetid));
		$lineQSet = $stm->fetch(PDO::FETCH_ASSOC);
		$myq = (intval($lineQSet['ownerid'])==$userid);
		if ($isadmin || ($isgrpadmin && intval($lineQSet['groupid'])==$groupid) || $lineQSet['userights']==4) {
			$myq = true;
		}

		$page_deleteForm = "";
		if ($myq || $myli) {
			$page_deleteForm .= "<form method=post action=\"reviewlibrary.php?cid=$cid&source=$source&offset=$offset&lib=$lib\">\n";
			if ($myq) {$page_deleteForm .=  "<input type=submit name=delete value=\"Delete\">\n";}
			if ($myli) {$page_deleteForm .=  "<input type=submit name=remove value=\"Remove from Library\">\n";}
			$page_deleteForm .=  "</form>\n";
		}

		$seed = rand(0,10000);
		require("../assessment/displayq2.php");
		if (isset($_POST['seed'])) {
			list($score,$rawscores) = scoreq(0,$qsetid,$_POST['seed'],$_POST['qn0']);
			$page_lastScore = "<p>Score on last answer: ".Sanitize::onlyFloat($score)."/1</p>\n";
		}

		$twobx = ($lineQSet['qcontrol']=='' && $lineQSet['answer']=='');

		if (!$myq) {
			$page_canModifyMsg = "<p>This question is not set to allow you to modify the code.  You can only view the code and make additional library assignments</p>";
		}
		if (isset($CFG['AMS']['showtips'])) {
			$showtips = $CFG['AMS']['showtips'];
		} else {
			$showtips = 1;
		}
	}
}

/******* begin html output ********/
$sessiondata['coursetheme'] = $coursetheme;
if ($showtips==2) {
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/eqntips.js?v=012810\"></script>";
}
require("../assessment/header.php");

if ($overwriteBody==1) {
	echo $body;
} else { //DISPLAY BLOCK HERE
?>

	<div class="breadcrumb"><?php echo $curBreadcrumb; ?></div>
	<div id="headerreviewlibrary" class="pagetitle"><h1>Review Library</h1></div>
<?php
	if (!isset($_REQUEST['lib'])) {
?>
	<script>
		var curlibs = '<?php echo $inlibs ?>';
		function libselect() {
			window.open('libtree.php?libtree=popup&type=radio&selectrights=1&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
		}
		function setlib(libs) {
			if (libs.charAt(0)=='0' && libs.indexOf(',')>-1) {
				libs = libs.substring(2);
			}
			document.getElementById("lib").value = libs;
			curlibs = libs;
		}
		function setlibnames(libn) {
			if (libn.indexOf('Unassigned')>-1 && libn.indexOf(',')>-1) {
				libn = libn.substring(11);
			}
			document.getElementById("libnames").innerHTML = libn;
		}
	</script>

	<form method=post action="reviewlibrary.php?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=0">
		Library to review:
		<span id="libnames"><?php echo Sanitize::encodeStringForDisplay($lnames) ?></span>
		<input type=hidden name="lib" id="lib" size="10" value="<?php echo $inlibs ?>">
		<input type=button value="Select Libraries" onClick="libselect()"><br/>
		<input type=submit value=Submit>
	</form>

<?php

	} elseif ((isset($_POST['remove']) || isset($_POST['delete'])) && !isset($_POST['confirm']))  {


?>
	<form method=post action="reviewlibrary.php?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>">
		<?php echo $page_ConfirmMsg; ?>
		<p><input type=submit name="confirm" value="Yes, I'm Sure">
		<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='reviewlibrary.php?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>'"></p>
	</form>

<?php


	} else { //DEFAULT DISPLAY HERE
?>

	<p style="color: red;"><?php echo $page_updatedMsg; ?></p>
	<p><?php echo $page_lastLink . " | " . $page_nextLink; ?></p>

	<h3><?php echo Sanitize::onlyInt($qsetid) ?>: <?php echo Sanitize::encodeStringForDisplay($lineQSet['description'])?></h3>

	<div><?php echo  $page_deleteForm; ?></div>
	<div><?php echo  $page_lastScore; ?></div>

	<form method=post action="reviewlibrary.php?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>" onsubmit="doonsubmit(this,true,true)">
		<input type=hidden name=seed value="<?php echo $seed ?>">

<?php
		unset($lastanswers);
		displayq(0,$qsetid,$seed,true,true,0);
?>
		<input type=submit value="Submit">
	</form>
<?php
		if ($source==0) {
			echo "	<p><a href=\"reviewlibrary.php?cid=$cid&offset=$offset&lib=$lib&source=1\">View/Modify Question Code</a></p>\n";
		} else {
?>
	<p>
		<a href="reviewlibrary.php?cid=<?php echo $cid ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>&source=0">
			Don't show Question Code
		</a>
	</p>
	<form method=post action="reviewlibrary.php?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>">
		<div><?php echo $page_canModifyMsg; ?></div>


		<script>
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
				document.getElementById(box).rows += 1;
			}
			function decboxsize(box) {
				if (document.getElementById(box).rows > 1)
					document.getElementById(box).rows -= 1;
			}
		</script>

		<input type=submit name="update" value="Update"><br/>

		Description:<BR>
		<textarea cols=60 rows=4 name=description <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo Sanitize::encodeStringForDisplay($lineQSet['description']);?></textarea>

		<p>
			Question type:
			<select name=qtype <?php if (!$myq) echo "disabled=\"disabled\"";?>>
				<option value="number" <?php if ($lineQSet['qtype']=="number") {echo "SELECTED";} ?>>Number</option>
				<option value="calculated" <?php if ($lineQSet['qtype']=="calculated") {echo "SELECTED";} ?>>Calculated Number</option>
				<option value="choices" <?php if ($lineQSet['qtype']=="choices") {echo "SELECTED";} ?>>Multiple-Choice</option>
				<option value="multans" <?php if ($lineQSet['qtype']=="multans") {echo "SELECTED";} ?>>Multiple-Answer</option>
				<option value="matching" <?php if ($lineQSet['qtype']=="matching") {echo "SELECTED";} ?>>Matching</option>
				<option value="numfunc" <?php if ($lineQSet['qtype']=="numfunc") {echo "SELECTED";} ?>>Function</option>
				<option value="string" <?php if ($lineQSet['qtype']=="string") {echo "SELECTED";} ?>>String</option>
				<option value="essay" <?php if ($lineQSet['qtype']=="essay") {echo "SELECTED";} ?>>Essay</option>
				<option value="draw" <?php if ($lineQSet['qtype']=="draw") {echo "SELECTED";} ?>>Drawing</option>
				<option value="ntuple" <?php if ($lineQSet['qtype']=="ntuple") {echo "SELECTED";} ?>>N-Tuple</option>
				<option value="calcntuple" <?php if ($lineQSet['qtype']=="calcntuple") {echo "SELECTED";} ?>>Calculated N-Tuple</option>
				<option value="matrix" <?php if ($lineQSet['qtype']=="matrix") {echo "SELECTED";} ?>>Numerical Matrix</option>
				<option value="calcmatrix" <?php if ($lineQSet['qtype']=="calcmatrix") {echo "SELECTED";} ?>>Calculated Matrix</option>
				<option value="interval" <?php if ($lineQSet['qtype']=="interval") {echo "SELECTED";} ?>>Interval</option>
				<option value="calcinterval" <?php if ($lineQSet['qtype']=="calcinterval") {echo "SELECTED";} ?>>Calculated Interval</option>
				<option value="complex" <?php if ($lineQSet['qtype']=="complex") {echo "SELECTED";} ?>>Complex</option>
				<option value="calccomplex" <?php if ($lineQSet['qtype']=="calccomplex") {echo "SELECTED";} ?>>Calculated Complex</option>
				<option value="file" <?php if ($lineQSet['qtype']=="file") {echo "SELECTED";} ?>>File Upload</option>
				<option value="multipart" <?php if ($lineQSet['qtype']=="multipart") {echo "SELECTED";} ?>>Multipart</option>
			</select>
		</p>

		<p>
			<a href="#" onclick="window.open('<?php echo $imasroot;?>/help.php?section=writingquestions','Help','width=400,height=300,toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420))">Writing Questions Help</a>
			<a href="#" onclick="window.open('<?php echo $imasroot;?>/assessment/libs/libhelp.php','Help','width=400,height=300,toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420))">Macro Library Help</a><BR>
			Switch to:
			<input type=button id=entrymode value="<?php if ($twobx) {echo "4-box entry";} else {echo "2-box entry";}?>" onclick="swapentrymode()" <?php if ($lineQSet['qcontrol']!='' || $lineQSet['answer']!='') echo "DISABLED"; ?>/>

		</p>
		<div id=ccbox>
			Common Control:
			<span class=pointer onclick="incboxsize('control')">[+]</span>
			<span class=pointer onclick="decboxsize('control')">[-]</span><BR>
			<textarea cols=60 rows=<?php if ($twobx) {echo "20";} else {echo "10";}?> id=control name=control <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo Sanitize::encodeStringForDisplay($lineQSet['control']);?></textarea>
		</div>
		<div id=qcbox <?php if ($twobx) {echo "style=\"display: none;\"";}?>>
			Question Control:
			<span class=pointer onclick="incboxsize('qcontrol')">[+]</span>
			<span class=pointer onclick="decboxsize('qcontrol')">[-]</span><BR>
			<textarea cols=60 rows=10 id=qcontrol name=qcontrol <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo Sanitize::encodeStringForDisplay($lineQSet['qcontrol']);?></textarea>
		</div>
		<div id=qtbox>
			Question Text:
			<span class=pointer onclick="incboxsize('qtext')">[+]</span>
			<span class=pointer onclick="decboxsize('qtext')">[-]</span><BR>
			<textarea cols=60 rows=10 id=qtext name=qtext <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo Sanitize::encodeStringForDisplay($lineQSet['qtext']);?></textarea>
		</div>
		<div id=abox <?php if ($twobx) {echo "style=\"display: none;\"";}?>>
			Answer:
			<span class=pointer onclick="incboxsize('answer')">[+]</span>
			<span class=pointer onclick="decboxsize('answer')">[-]</span><BR>
			<textarea cols=60 rows=10 id=answer name=answer <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo Sanitize::encodeStringForDisplay($lineQSet['answer']);?></textarea>
		</div>
		<input type=submit name="update" value="Update">
	</form>

<?php
		}
	}
}

require("../footer.php");



function delqimgs($qsid) {
	global $DBH;
	//DB $query = "SELECT id,filename,var FROM imas_qimages WHERE qsetid='$qsid'";
	//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT id,filename,var FROM imas_qimages WHERE qsetid=:qsetid");
	$stm->execute(array(':qsetid'=>$qsid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		//DB $query = "SELECT id FROM imas_qimages WHERE filename='{$row[1]}'";
		//DB $r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
		//DB if (mysql_num_rows($r2)==1) {
		if (substr($row[1],0,4)!='http') {
			$stm2 = $DBH->prepare("SELECT id FROM imas_qimages WHERE filename=:filename");
			$stm2->execute(array(':filename'=>$row[1]));
			if ($stm2->rowCount()==1) {
				unlink(rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/'.$row[1]);
			}
		}
		//DB $query = "DELETE FROM imas_qimages WHERE id='{$row[0]}'";
		//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
		$stm2 = $DBH->prepare("DELETE FROM imas_qimages WHERE id=:id");
		$stm2->execute(array(':id'=>$row[0]));
	}
}
?>
