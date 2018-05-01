<?php

require("../init.php");
if (isset($sessiondata['emulateuseroriginaluser']) && isset($_GET['unemulateuser'])) {
	$stm = $DBH->prepare("UPDATE imas_sessions SET userid=:userid WHERE sessionid=:sessionid");
	$stm->execute(array(':userid'=>$sessiondata['emulateuseroriginaluser'], ':sessionid'=>$sessionid));
	unset($sessiondata['emulateuseroriginaluser']);
	writesessiondata();
	header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php?r=" .Sanitize::randomQueryStringParam());
	exit;
}

if ($myrights >= 75 && isset($_GET['emulateuser'])) {
    $emu_id = Sanitize::onlyInt($_GET['emulateuser']);
	if ($myrights<100) {
		$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=?");
		$stm->execute(array($emu_id));
		if ($stm->fetchColumn(0) != $groupid) {
			echo "You can only emulate teachers from your own group";
			exit;
		}
	}
	$sessiondata['emulateuseroriginaluser'] = $userid;
	writesessiondata();
	$stm = $DBH->prepare("UPDATE imas_sessions SET userid=:userid WHERE sessionid=:sessionid");
	$stm->execute(array(':userid'=>$emu_id, ':sessionid'=>$sessionid));
	header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php?r=" .Sanitize::randomQueryStringParam());
	exit;
}
if ($myrights<100) {
	echo "You are not authorized to view this page";
	exit;
}

$curBreadcrumb = "$breadcrumbbase <a href=\"$imasroot/admin/admin2.php\">Admin</a>\n";

if (isset($_GET['removelti'])) {
	$id = intval($_GET['removelti']);
	//DB $query = "DELETE FROM imas_ltiusers WHERE id=$id";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("DELETE FROM imas_ltiusers WHERE id=:id");
	$stm->execute(array(':id'=>$id));
}
if (isset($_GET['removecourselti'])) {
	$id = intval($_GET['removecourselti']);
	//DB $query = "DELETE FROM imas_lti_courses WHERE id=$id";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("DELETE FROM imas_lti_courses WHERE id=:id");
	$stm->execute(array(':id'=>$id));
}
if (isset($_GET['fixorphanqs'])) {
	//first, try to undelete the unassigned library item for any question with no undeleted library items
	$query = "UPDATE imas_library_items AS ili JOIN (SELECT qsetid FROM imas_library_items GROUP BY qsetid HAVING min(deleted)=1) AS tofix ON ili.qsetid=tofix.qsetid ";
	$query .= "JOIN imas_questionset AS iq ON ili.qsetid=iq.id ";
	$query .= "SET ili.deleted=0 WHERE ili.libid=0 AND iq.deleted=0";
	$stm = $DBH->query($query);
	$n1 = $stm->rowCount();
	
	//if any still have no undeleted library items, then they must not have an unassigned entry to undelete, so add it
	$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid,junkflag,deleted,lastmoddate) ";
	$query .= "(SELECT 0,ili.qsetid,iq.ownerid,0,0,iq.lastmoddate FROM imas_library_items AS ili JOIN imas_questionset AS iq ON iq.id=ili.qsetid WHERE iq.deleted=0 GROUP BY ili.qsetid HAVING min(ili.deleted)=1)";
	$stm = $DBH->query($query);
	$n2 = $stm->rowCount();
	
	//if there are any questions with NO library items, add an unassigned one
	$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid,junkflag,deleted,lastmoddate) ";
	$query .= "(SELECT 0,iq.id,iq.ownerid,0,iq.deleted,iq.lastmoddate FROM imas_questionset AS iq LEFT JOIN imas_library_items AS ili ON iq.id=ili.qsetid WHERE ili.id IS NULL)";
	$stm = $DBH->query($query);
	$n3 = $stm->rowCount();
	
	//make unassigned deleted if there's also an undeleted other library
	$query = "UPDATE imas_library_items AS A JOIN imas_library_items AS B ON A.qsetid=B.qsetid AND A.deleted=0 AND B.deleted=0 ";
	$query .= "SET A.deleted=1 WHERE A.libid=0 AND B.libid>0";
	$stm = $DBH->query($query);
	
	echo '<p>'.($n1+$n2+$n3). ' questions with no libraries fixed</p>';
	echo '<p><a href="utils.php">Utils</a></p>';
	exit;
}
if (isset($_POST['updatecaption'])) {
	$vidid = trim($_POST['updatecaption']);
	if (strlen($vidid)!=11 || preg_match('/[^A-Za-z0-9_\-]/',$vidid)) {
		echo 'Invalid video ID';
		exit;
	}
	$ctx = stream_context_create(array('http'=>
	    array(
		'timeout' => 1
	    )
	));
	$t = @file_get_contents('https://www.youtube.com/api/timedtext?type=list&v='.$vidid, false, $ctx);
	$captioned = (strpos($t, '<track')===false)?0:1;
	if ($captioned==1) {
		$upd = $DBH->prepare("UPDATE imas_questionset SET extref=? WHERE id=?");
		$stm = $DBH->prepare("SELECT id,extref FROM imas_questionset WHERE extref REGEXP ?");
		$stm->execute(array('[[:<:]]'.$vidid.'[[:>:]]'));
		$chg = 0;
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$parts = explode('~~', $row[1]);
			foreach ($parts as $k=>$v) {
				if (preg_match('/\b'.$vidid.'\b/', $v)) {
					$parts[$k] = preg_replace('/!!0$/', '!!1', $v);
				}
			}
			$newextref = implode('~~', $parts);
			$upd->execute(array($newextref, $row[0]));
			$chg += $upd->rowCount();
		}
	}
	echo '<p>Updated '.$chg.' records.</p><p><a href="utils.php">Utils</a></p>';
	exit;
}
	
if (isset($_GET['fixdupgrades'])) {
	$query = 'DELETE imas_grades FROM imas_grades JOIN ';
	$query .= "(SELECT min(id) as minid,refid FROM imas_grades WHERE gradetype='forum' AND refid>0 GROUP BY refid having count(id)>1) AS duplic ";
	$query .= "ON imas_grades.refid=duplic.refid AND imas_grades.gradetype='forum' WHERE imas_grades.id > duplic.minid";
	$stm = $DBH->query($query);
	echo "Removed ".($stm->rowCount())." duplicate forum grade records.<br/>";
	echo '<p><a href="utils.php">Utils</a></p>';
	exit;
}
if (isset($_POST['action']) && $_POST['action']=='jumptoitem') {
	if (!empty($_POST['cid'])) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_POST['cid'])."&r=".Sanitize::randomQueryStringParam());
	} else if (!empty($_POST['aid'])) {
		$aid = Sanitize::onlyInt($_GET['aid']);
		$stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=?");
		$stm->execute(array($aid));
		$destcid = $stm->fetchColumn(0);
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addassessment.php?cid=".Sanitize::onlyInt($destcid)."&id=".$aid."&r=".Sanitize::randomQueryStringParam());
	} else if (!empty($_POST['pqid'])) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/testquestion.php?qsetid=".Sanitize::onlyInt($_POST['pqid'])."&r=".Sanitize::randomQueryStringParam());
	} else if (!empty($_POST['eqid'])) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/moddataset.php?cid=admin&id=".Sanitize::onlyInt($_POST['eqid'])."&r=".Sanitize::randomQueryStringParam());
	}
	exit;
}

if (isset($_GET['form'])) {
	$curBreadcrumb = $curBreadcrumb . " &gt; <a href=\"$imasroot/util/utils.php\">Utils</a> \n";

	if ($_GET['form']=='emu') {
		require("../header.php");
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Emulate User</div>';
		echo '<form method="post" action="'.$imasroot.'/admin/actions.php">';
		echo '<input type=hidden name=action value="emulateuser" />';
		echo 'Emulate user with userid: <input type="text" size="5" name="uid"/>';
		echo '<input type="submit" value="Go"/>';
		echo '</form>';
		require("../footer.php");
	} else if ($_GET['form']=='jumptoitem') {
		require("../header.php");
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Jump to Item</div>';
		echo '<form method="post" action="'.$imasroot.'/util/utils.php">';
		echo '<input type=hidden name=action value="jumptoitem" />';
		echo '<p>Jump to:<br/>';
		echo 'Course ID: <input type="text" size="8" name="cid"/><br/>';
		echo 'Assessment ID: <input type="text" size="8" name="aid"/><br/>';
		echo 'Preview Question ID: <input type="text" size="8" name="pqid"/><br/>';
		echo 'Edit Question ID: <input type="text" size="8" name="eqid"/><br/>';
		echo '<input type="submit" value="Go"/>';
		echo '</form>';
		require("../footer.php");

	} else if ($_GET['form']=='rescue') {
		require("../header.php");
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Recover Items</div>';
		echo '<form method="post" action="'.$imasroot.'/util/rescuecourse.php">';
		echo 'Recover lost items in course ID: <input type="text" size="5" name="cid"/>';
		echo '<input type="submit" value="Go"/>';
		echo '</form>';
		require("../footer.php");
	} else if ($_GET['form']=='updatecaption') {
		require("../header.php");
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Update Caption Data</div>';
		echo '<form method="post" action="'.$imasroot.'/util/utils.php">';
		echo 'YouTube video ID: <input type="text" size="11" name="updatecaption"/>';
		echo '<input type="submit" value="Go"/>';
		echo '</form>';
		require("../footer.php");
	} else if ($_GET['form']=='lookup') {
		require("../header.php");
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; User Lookup</div>';

		if (!empty($_POST['FirstName']) || !empty($_POST['LastName']) || !empty($_POST['SID']) || !empty($_POST['email'])) {
			$qarr = array();
			if (!empty($_POST['SID'])) {
				$query = "SELECT imas_users.*,imas_groups.name,imas_groups.grouptype,imas_groups.parent FROM imas_users LEFT JOIN imas_groups ON imas_users.groupid=imas_groups.id WHERE imas_users.SID=:SID";
				$qarr[':SID']=$_POST['SID'];
			} else if (!empty($_POST['email'])) {
				$query = "SELECT imas_users.*,imas_groups.name,imas_groups.grouptype,imas_groups.parent FROM imas_users LEFT JOIN imas_groups ON imas_users.groupid=imas_groups.id WHERE imas_users.email=:email";
				$qarr[':email']=$_POST['email'];
			} else  {
				$query = "SELECT imas_users.*,imas_groups.name,imas_groups.grouptype,imas_groups.parent FROM imas_users LEFT JOIN imas_groups ON imas_users.groupid=imas_groups.id WHERE ";
				if (!empty($_POST['LastName'])) {
					$query .= "imas_users.LastName LIKE :lastname ";
					$qarr[':lastname']=$_POST['LastName'].'%';
					if (!empty($_POST['FirstName'])) {
						$query .= "AND ";
					}
				}
				if (!empty($_POST['FirstName'])) {
					$query .= "imas_users.FirstName LIKE :firstname ";
					$qarr[':firstname']=$_POST['FirstName'].'%';
				}
				$query .= "ORDER BY imas_users.LastName,imas_users.FirstName LIMIT 100";
			}
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare($query);
			$stm->execute($qarr);
			//DB if (mysql_num_rows($result)==0) {
			if ($stm->rowCount()==0) {
				echo "No results found";
			} else {
				$group_stm = $DBH->prepare('SELECT name FROM imas_groups WHERE id=:id');
				$stu_stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_students AS istu ON istu.courseid=ic.id AND istu.userid=:userid");
				$tutor_stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_tutors AS istu ON istu.courseid=ic.id AND istu.userid=:userid");
				$teach_stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS istu ON istu.courseid=ic.id AND istu.userid=:userid");
				$lti_stm = $DBH->prepare("SELECT org,id,ltiuserid FROM imas_ltiusers WHERE userid=:userid");

				//DB while ($row = mysql_fetch_assoc($result)) {
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					echo '<p><b>'.Sanitize::encodeStringForDisplay($row['LastName']).', '.Sanitize::encodeStringForDisplay($row['FirstName']).'</b></p>';
					echo '<form method="post" action="../admin/actions.php?id='.Sanitize::encodeUrlParam($row['id']).'">';
					echo '<input type=hidden name=action value="resetpwd" />';
					echo '<ul><li>Username: <a href="../admin/admin2.php?showcourses='.Sanitize::encodeUrlParam($row['id']).'">'.Sanitize::encodeStringForDisplay($row['SID']).'</a></li>';
					echo '<li>ID: '.$row['id'].'</li>';
					if ($row['name']!=null) {
						echo '<li>Group: '.Sanitize::encodeStringForDisplay($row['name']).'</li>';
						if ($row['parent']>0) {
							//DB $query = 'SELECT name FROM imas_groups WHERE id='.$row['parent'];
							//DB $res2 = mysql_query($query) or die("Query failed : " . mysql_error());
							//DB $r = mysql_fetch_row($res2);
							$group_stm->execute(array(':id'=>$row['parent']));
							$r = $group_stm->fetch(PDO::FETCH_NUM);
							echo '<li>Parent Group: '.Sanitize::encodeStringForDisplay($r[0]).'</li>';
						}
					}
					echo '<li><a href="utils.php?emulateuser='.Sanitize::encodeUrlParam($row['id']).'">Emulate User</li>';
					echo '<li>Email: '.Sanitize::encodeStringForDisplay($row['email']).'</li>';
					echo '<li>Last Login: '.tzdate("n/j/y g:ia", $row['lastaccess']).'</li>';
					echo '<li>Rights: '.Sanitize::encodeStringForDisplay($row['rights']).' <a href="'.$imasroot.'/admin/forms.php?action=chgrights&id='.Sanitize::encodeUrlParam($row['id']).'">[edit]</a></li>';
					echo '<li>Reset Password to <input type="text" name="newpw"/> <input type="submit" value="'._('Go').'"/></li>';
					//DB $query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_students AS istu ON istu.courseid=ic.id AND istu.userid=".$row['id'];
					//DB $res2 = mysql_query($query) or die("Query failed : " . mysql_error());
					//DB if (mysql_num_rows($res2)>0) {
					$stu_stm->execute(array(':userid'=>$row['id']));
					if ($stu_stm->rowCount()>0) {
						echo '<li>Enrolled as student in: <ul>';
						//DB while ($r = mysql_fetch_row($res2)) {
						while ($r = $stu_stm->fetch(PDO::FETCH_NUM)) {
							echo '<li><a target="_blank" href="../course/course.php?cid='.Sanitize::encodeUrlParam($r[0]).'">'.Sanitize::encodeStringForDisplay($r[1]).' (ID '.Sanitize::encodeStringForDisplay($r[0]).')</a></li>';
						}
						echo '</ul></li>';
					}
					//DB $query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_tutors AS istu ON istu.courseid=ic.id AND istu.userid=".$row['id'];
					//DB $res2 = mysql_query($query) or die("Query failed : " . mysql_error());
					//DB if (mysql_num_rows($res2)>0) {
					$tutor_stm->execute(array(':userid'=>$row['id']));
					if ($tutor_stm->rowCount()>0) {
						echo '<li>Tutor in: <ul>';
						//DB while ($r = mysql_fetch_row($res2)) {
						while ($r = $tutor_stm->fetch(PDO::FETCH_NUM)) {
							echo '<li><a target="_blank" href="../course/course.php?cid='.Sanitize::encodeUrlParam($r[0]).'">'.Sanitize::encodeStringForDisplay($r[1]).' (ID '.Sanitize::encodeStringForDisplay($r[0]).')</a></li>';
						}
						echo '</ul></li>';
					}
					$teachercourses = array();
					//DB $query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS istu ON istu.courseid=ic.id AND istu.userid=".$row['id'];
					//DB $res2 = mysql_query($query) or die("Query failed : " . mysql_error());
					//DB if (mysql_num_rows($res2)>0) {
					$teach_stm->execute(array(':userid'=>$row['id']));
					if ($teach_stm->rowCount()>0) {
						echo '<li>Teacher in: <ul>';
						//DB while ($r = mysql_fetch_row($res2)) {
						while ($r = $teach_stm->fetch(PDO::FETCH_NUM)) {
							echo '<li><a target="_blank" href="../course/course.php?cid='.Sanitize::encodeUrlParam($r[0]).'">'.Sanitize::encodeStringForDisplay($r[1]).' (ID '.Sanitize::encodeStringForDisplay($r[0]).')</a></li>';
							$teachercourses[] = $r[0];
						}
						echo '</ul></li>';
					}
					//DB $query = "SELECT org,id,ltiuserid FROM imas_ltiusers WHERE userid=".$row['id'];
					//DB $res2 = mysql_query($query) or die("Query failed : " . mysql_error());
					//DB if (mysql_num_rows($res2)>0) {
					$lti_stm->execute(array(':userid'=>$row['id']));
					if ($lti_stm->rowCount()>0) {
						echo '<li>LTI user connections: <ul>';
						//DB while ($r = mysql_fetch_row($res2)) {
						while ($r = $lti_stm->fetch(PDO::FETCH_NUM)) {
							echo '<li>key:'.Sanitize::encodeStringForDisplay(substr($r[0],0,strpos($r[0],':'))).', remote userid:'.Sanitize::encodeStringForDisplay($r[2]).' <a href="utils.php?removelti='.Sanitize::encodeUrlParam($r[1]).'">Remove connection</a></li>';
						}
						echo '</ul></li>';
					}
					if (count($teachercourses)>0) {
						$query = "SELECT org,id,courseid,contextid FROM imas_lti_courses WHERE courseid IN (".implode(",",$teachercourses).")";
						//DB $res2 = mysql_query($query) or die("Query failed : " . mysql_error());
						$lti_c_stm = $DBH->query($query);
						//DB if (mysql_num_rows($res2)>0) {
						if ($lti_c_stm->rowCount()>0) {
							echo '<li>LTI course connections: <ul>';
							//DB while ($r = mysql_fetch_row($res2)) {
							while ($r = $lti_c_stm->fetch(PDO::FETCH_NUM)) {
								echo '<li>Course: '.Sanitize::encodeStringForDisplay($r[2]).', key:'.Sanitize::encodeStringForDisplay(substr($r[0],0,strpos($r[0],':'))).', context:'.Sanitize::encodeStringForDisplay($r[3]).' <a href="utils.php?removecourselti='.Sanitize::encodeUrlParam($r[1]).'">Remove connection</a></li>';
							}
							echo '</ul></li>';
						}
					}
					echo '</ul>';
					echo '</form>';
				}
			}

		} else {
			echo '<form method="post" action="utils.php?form=lookup">';
			echo 'Look up user:  LastName: <input type="text" name="LastName" />, FirstName: <input type="text" name="FirstName" />, or username: <input type="text" name="SID"/>, or email: <input type="text" name="email"/>';
			echo '<input type="submit" value="Go"/>';
			echo '</form>';
		}
		require("../footer.php");

	}


} else {
	//listing of utilities
	require("../header.php");
	echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Utilities</div>';
	echo '<h3>Admin Utilities </h3>';
	if (isset($_GET['debug'])) {
		echo '<p>Debug Mode Enabled - Error reporting is now turned on.</p>';
	}
	echo '<a href="utils.php?form=lookup">User lookup</a><br/>';
	echo '<a href="'.$imasroot.'/admin/approvepending2.php">Approve Pending Instructor Accounts</a><br/>';
	echo '<a href="'.$imasroot.'/admin/approvepending.php">Approve Pending Instructor Accounts (old version)</a><br/>';
	echo '<a href="utils.php?form=jumptoitem">Jump to Item</a><br/>';
	echo '<a href="batchcreateinstr.php">Batch create Instructor Accounts</a><br/>';
	echo '<a href="getstucnt.php">Get Student Count</a><br/>';
	echo '<a href="getstucntdet.php">Get Detailed Student Count</a><br/>';
	echo '<a href="utils.php?debug=true">Enable Debug Mode</a><br/>';
	echo '<a href="replacevids.php">Replace YouTube videos</a><br/>';
	echo '<a href="utils.php?form=updatecaption">Update YouTube video caption data</a><br/>';
	echo '<a href="replaceurls.php">Replace URLS</a><br/>';
	echo '<a href="utils.php?form=rescue">Recover lost items</a><br/>';
	echo '<a href="utils.php?fixorphanqs=true">Fix orphaned questions</a><br/>';
	echo '<a href="utils.php?form=emu">Emulate User</a><br/>';
	echo '<a href="listextref.php">List ExtRefs</a><br/>';
	echo '<a href="updateextref.php">Update ExtRefs</a><br/>';
	echo '<a href="delwronglibs.php">Delete Questions with WrongLib Flag</a><br/>';
	echo '<a href="listwronglibs.php">List WrongLibFlags</a><br/>';
	echo '<a href="updatewronglibs.php">Update WrongLibFlags</a><br/>';
	echo '<a href="blocksearch.php">Search Block titles</a><br/>';
	echo '<a href="itemsearch.php">Search inline/linked items</a>';
	require("../footer.php");
}
?>
