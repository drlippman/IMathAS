<?php

require_once "../init.php";
require_once '../includes/videodata.php';

if (isset($_SESSION['emulateuseroriginaluser']) && isset($_GET['unemulateuser'])) {
	$_SESSION['userid'] = $_SESSION['emulateuseroriginaluser'];
    $userid = $_SESSION['userid'];
	unset($_SESSION['emulateuseroriginaluser']);
    //reload prefs for original user
    require_once "../includes/userprefs.php";
    generateuserprefs($userid);
    if (isset($_POST['tzname'])) {
        $_SESSION['tzname'] = $_POST['tzname'];
    }
	header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php?r=" .Sanitize::randomQueryStringParam());
	exit;
}

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['util/utils'])) {
	require_once $CFG['hooks']['util/utils'];
}

if ($myrights >= 75 && isset($_GET['emulateuser'])) {
    $emu_id = Sanitize::onlyInt($_GET['emulateuser']);
	if ($myrights<100) {
		$stm = $DBH->prepare("SELECT groupid,rights FROM imas_users WHERE id=?");
		$stm->execute(array($emu_id));
        $row = $stm->fetch(PDO::FETCH_ASSOC);
		if ($row['groupid'] != $groupid) {
			echo "You can only emulate teachers from your own group";
			exit;
		} else if ($row['rights']>$myrights) {
            echo "Cannot emulate a user of higher rights";
            exit;
        }
	}
	$_SESSION['emulateuseroriginaluser'] = $userid;
	$_SESSION['userid'] = $emu_id;
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
	$stm = $DBH->prepare("DELETE FROM imas_ltiusers WHERE id=:id");
	$stm->execute(array(':id'=>$id));
}
if (isset($_GET['removecourselti'])) {
	$id = intval($_GET['removecourselti']);
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
if (isset($_POST['setupfcm'])) {
    $data = json_decode($_POST['setupfcm'], true);
    if ($data === null || !isset($data['client_email']) || !isset($data['private_key'])) {
        echo '<p>Error: invalid data</p>';
    } else {
        $stm = $DBH->query("SELECT kid,privatekey FROM imas_lti_keys WHERE key_set_url='https://oauth2.googleapis.com/token'");
        if ($stm->rowCount() > 0) {
            $stm = $DBH->prepare("UPDATE imas_lti_keys set kid=?,privatekey=? WHERE 'https://oauth2.googleapis.com/token'");
            $stm->execute([$data['client_email'], $data['private_key']]);
        } else {
            $stm = $DBH->prepare("INSERT INTO imas_lti_keys (key_set_url,kid,privatekey) VALUES (?,?,?)");
            $stm->execute(['https://oauth2.googleapis.com/token', $data['client_email'], $data['private_key']]);
        }
        echo '<p>Key information stored.</p>';
    }
    echo '<p><a href="utils.php">Utils</a></p>';
	exit;
}
if (isset($_POST['updatecaption'])) {
	$vidid = trim($_POST['updatecaption']);
	if (strlen($vidid)!=11 || preg_match('/[^A-Za-z0-9_\-]/',$vidid)) {
		echo 'Invalid video ID';
		exit;
	}
    $vidid = Sanitize::simpleASCII($vidid);

	$captioned = getCaptionDataByVidId($vidid);

	if ($captioned==1) {
		$upd = $DBH->prepare("UPDATE imas_questionset SET extref=? WHERE id=?");
		$stm = $DBH->prepare("SELECT id,extref FROM imas_questionset WHERE extref REGEXP ?");
		$stm->execute(array(MYSQL_LEFT_WRDBND.$vidid.MYSQL_RIGHT_WRDBND));
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
if (isset($_POST['updatecaptionbyqids'])) {
	$qids = array_map('intval', explode(',', $_POST['updatecaptionbyqids']));
	if (count($qids) == 0) {
		echo "Must provide question IDs";
		exit;
	}
	$upd = $DBH->prepare('UPDATE imas_questionset SET extref=? WHERE id=?');
	$ph = Sanitize::generateQueryPlaceholders($qids);
	$stm = $DBH->prepare("SELECT id,extref FROM imas_questionset WHERE id IN ($ph)");
	$stm->execute($qids);
	$updatedcnt = 0;
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$changed = false;
		$extrefs = explode('~~', $row['extref']);
		foreach ($extrefs as $k=>$v) {
			$parts = explode('!!', $v);
			if ($parts[2] == 0) { // not captioned. if it's a video getvideoid will tell
				$vidid = getvideoid($parts[1]);
				if ($vidid !== '') {
					$captioned = getCaptionDataByVidId($vidid);
					if ($captioned == 1) {
						$parts[2] = 1;
						$extrefs[$k] = implode('!!', $parts);
						$changed = true;
					}
				}
			}
		}
		if ($changed) {
			$updatedcnt++;
			$upd->execute([implode('~~', $extrefs), $row['id']]);
		}
	}
	
	echo '<p>Updated '.$updatedcnt.' records.</p><p><a href="utils.php">Utils</a></p>';
	exit;
}
if (isset($_POST['exportcaptions'])) {
	$from = intval($_POST['exportcaptions']);
	$stm = $DBH->prepare("SELECT * FROM imas_captiondata WHERE lastchg>?");
	$stm->execute([$from]);
	$out = $stm->fetchAll(PDO::FETCH_ASSOC);
	header('Content-Type: application/json'); 
	header('Content-Disposition: attachment; filename="captiondata.json"'); 
	echo json_encode($out);
	exit;
}


if (isset($_FILES['importcaptions'])) {
	if (is_uploaded_file($_FILES['importcaptions']['tmp_name'])) {
		$json = json_decode(file_get_contents($_FILES['importcaptions']['tmp_name']), true);
		$qarr = [];
		foreach ($json as $data) {
			array_push($qarr, $data['vidid'], $data['captioned'], $data['status'], $data['lastchg']);
		}
		$ph = Sanitize::generateQueryPlaceholdersGrouped($qarr, 4);
		$query = "INSERT INTO imas_captiondata (vidid, captioned, status, lastchg) VALUES $ph ";
        $query .= "ON DUPLICATE KEY UPDATE status=IF(VALUES(captioned)>captioned OR status=0 OR status=3,VALUES(status),status),";
        $query .= "lastchg=IF(VALUES(captioned)>captioned OR status=0 OR status=3,VALUES(lastchg),lastchg),";
        $query .= "captioned=IF(VALUES(captioned)>captioned OR status=0 OR status=3,VALUES(captioned),captioned)";
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);
		echo count($json) . ' records read. ';
		echo $stm->rowCount() . ' rows updated.';
	}
	exit;
}

if (isset($_GET['fixdupgrades'])) {
	$query = 'DELETE imas_grades FROM imas_grades JOIN ';
	$query .= "(SELECT min(id) as minid,refid FROM imas_grades WHERE gradetype='forum' AND refid>0 GROUP BY refid having count(id)>1) AS duplic ";
	$query .= "ON imas_grades.refid=duplic.refid AND imas_grades.gradetype='forum' WHERE imas_grades.id > duplic.minid";
	$stm = $DBH->query($query);
	echo "Removed ".($stm->rowCount())." duplicate forum grade records.<br/>";

	$query = 'DELETE imas_grades FROM imas_grades JOIN ';
	$query .= "(SELECT min(id) as minid,gradetypeid,userid FROM imas_grades WHERE gradetype='offline' GROUP BY gradetypeid,userid having count(id)>1) AS duplic ";
	$query .= "ON imas_grades.gradetypeid=duplic.gradetypeid AND imas_grades.userid=duplic.userid AND imas_grades.gradetype='offline' WHERE imas_grades.id > duplic.minid";
	$stm = $DBH->query($query);
	echo "Removed ".($stm->rowCount())." duplicate offline grade records.<br/>";

	$stm = $DBH->query("DELETE imas_grades FROM imas_grades LEFT JOIN imas_forum_posts ON imas_grades.refid=imas_forum_posts.id WHERE imas_grades.gradetype='forum' AND imas_forum_posts.userid IS NULL");
	echo "Removed ".($stm->rowCount())." orphaned forum grade records without a corresponding post.<br/>";

	echo '<p><a href="utils.php">Utils</a></p>';
	exit;
}
if (isset($_POST['action']) && $_POST['action']=='jumptoitem') {
	if (!empty($_POST['cid'])) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_POST['cid'])."&r=".Sanitize::randomQueryStringParam());
	} else if (!empty($_POST['aid'])) {
		$aid = Sanitize::onlyInt($_POST['aid']);
		$stm = $DBH->prepare("SELECT courseid,ver FROM imas_assessments WHERE id=?");
		$stm->execute(array($aid));
		list($destcid,$aver) = $stm->fetch(PDO::FETCH_NUM);
        if ($aver > 1) {
		    header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addassessment2.php?cid=".Sanitize::onlyInt($destcid)."&id=".$aid."&r=".Sanitize::randomQueryStringParam());
        } else {
		    header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addassessment.php?cid=".Sanitize::onlyInt($destcid)."&id=".$aid."&r=".Sanitize::randomQueryStringParam());
        }
	} else if (!empty($_POST['pqid'])) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/testquestion2.php?qsetid=".Sanitize::onlyInt($_POST['pqid'])."&r=".Sanitize::randomQueryStringParam());
	} else if (!empty($_POST['eqid'])) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/moddataset.php?cid=admin&id=".Sanitize::onlyInt($_POST['eqid'])."&r=".Sanitize::randomQueryStringParam());
	}
	exit;
}
if (isset($_GET['listadmins'])) {
	$curBreadcrumb = $curBreadcrumb . " &gt; <a href=\"$imasroot/util/utils.php\">Utils</a>\n";
	$pagetitle = _('Admin List');
	require_once "../header.php";
	echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Admin List</div>';
	echo '<h1>Admin List</h1>';
	$query = 'SELECT iu.FirstName,iu.LastName,ig.name FROM imas_users AS iu JOIN imas_groups AS ig ON iu.groupid=ig.id ';
	$stm = $DBH->query($query.' WHERE iu.rights=100 ORDER BY LastName,FirstName');
	echo '<h2>Full Admins</h2><ul>';
	while ($user = $stm->fetch(PDO::FETCH_ASSOC)) {
		echo '<li><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($user['LastName'].', '.$user['FirstName'].' ('.$user['name'].')').'</span></li>';
	}
	echo '</ul>';
	echo '<h2>Group Admins</h2><ul>';
	$stm = $DBH->query($query.' WHERE iu.rights=75 ORDER BY LastName,FirstName');
	while ($user = $stm->fetch(PDO::FETCH_ASSOC)) {
		echo '<li><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($user['LastName'].', '.$user['FirstName'].' ('.$user['name'].')').'</span></li>';
	}
	echo '</ul>';
	echo '<h2>Global Account Approvers</h2><ul>';
	$stm = $DBH->query($query.' WHERE iu.rights>39 AND (iu.specialrights&64)=64 ORDER BY LastName,FirstName');
	while ($user = $stm->fetch(PDO::FETCH_ASSOC)) {
		echo '<li><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($user['LastName'].', '.$user['FirstName'].' ('.$user['name'].')').'</span></li>';
	}
	echo '</ul>';
	require_once "../footer.php";
	exit;
}
if (isset($_GET['form'])) {
	$curBreadcrumb = $curBreadcrumb . " &gt; <a href=\"$imasroot/util/utils.php\">Utils</a> \n";

	if ($_GET['form']=='emu') {
		$pagetitle = _('Emulate User');
		require_once "../header.php";
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Emulate User</div>';
		echo '<form method="post" action="'.$imasroot.'/admin/actions.php">';
		echo '<input type=hidden name=action value="emulateuser" />';
		echo 'Emulate user with userid: <input type="text" size="5" name="uid"/>';
		echo '<input type="submit" value="Go"/>';
		echo '</form>';
		require_once "../footer.php";
	} else if ($_GET['form']=='jumptoitem') {
		$pagetitle = _('Jump to Item');
		require_once "../header.php";
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
		require_once "../footer.php";

	} else if ($_GET['form']=='rescue') {
		$pagetitle = _('Recover Items');
		require_once "../header.php";
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Recover Items</div>';
		echo '<form method="post" action="'.$imasroot.'/util/rescuecourse.php">';
		echo 'Recover lost items in course ID: <input type="text" size="5" name="cid"/>';
		echo '<input type="submit" value="Go"/>';
		echo '</form>';
		require_once "../footer.php";
	} else if ($_GET['form']=='updatecaption') {
		$pagetitle = _('Update Caption Data');
		require_once "../header.php";
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Update Caption Data</div>';
		echo '<form method="post" action="'.$imasroot.'/util/utils.php">';
		echo 'YouTube video ID: <input type="text" size="11" name="updatecaption"/>';
		echo '<input type="submit" value="Go"/>';
		echo '</form>';
		require_once "../footer.php";
	} else if ($_GET['form']=='updatecaptionbyqids') {
		$pagetitle = _('Update Caption Data');
		require_once "../header.php";
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Update Caption Data</div>';
		echo '<form method="post" action="'.$imasroot.'/util/utils.php">';
		echo 'Question IDs to rescan videos on (comma separated):<br><input type="text" size="80" name="updatecaptionbyqids"/>';
		echo '<input type="submit" value="Go"/>';
		echo '</form>';
		require_once "../footer.php";
	} else if ($_GET['form']=='exportcaptions') {
		$pagetitle = _('Export Caption Data');
		require_once "../header.php";
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Export Caption Data</div>';
		echo '<form method="post" action="'.$imasroot.'/util/utils.php">';
		echo 'Export changes since timestamp: <input type="text" size="11" name="exportcaptions" value="0"/>';
		echo '<input type="submit" value="Go"/>';
		echo '</form>';
		require_once "../footer.php";
	} else if ($_GET['form']=='importcaptions') {
		$pagetitle = _('Import Caption Data');
		require_once "../header.php";
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Import Caption Data</div>';
		echo '<form method="post" enctype="multipart/form-data" action="'.$imasroot.'/util/utils.php">';
		echo 'Import JSON file: <input type="file" name="importcaptions" />';
		echo '<input type="submit" value="Go"/>';
		echo '</form>';
		require_once "../footer.php";
	} else if ($_GET['form']=='setupfcm') {
		$pagetitle = _('Setup FCM');
		require_once "../header.php";
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Setup FCM</div>';
		echo '<form method="post" action="'.$imasroot.'/util/utils.php">';
        echo '<p>To enabled Firebase Cloud Messaging for push notifications, you need to 
            set up an app with Firebase, enable the FireBase Cloud Messaging API, and
            generate a private key, which will be downloaded as a .json file.  You will 
            need to add the FCM project id to your config.php by setting 
            <code>$CFG[\'FCM\'][\'project_id\']</code>.  Then copy the contents of the
            .json file into the box below.</p>';
        $stm = $DBH->query("SELECT kid,privatekey FROM imas_lti_keys WHERE key_set_url='https://oauth2.googleapis.com/token'");
        if ($stm->rowCount() > 0) {
            echo '<p><b>NOTE</b>: it appears you already have a configuration loaded. You can load a new one if you need to overwrite the existing.</p>';
        }
        echo '<textarea name=setupfcm id=setupfcm rows=30 style="width:100%"></textarea>';
		echo '<input type="submit" value="Save"/>';
		echo '</form>';
		require_once "../footer.php";
	} else if ($_GET['form']=='lookup') {
		$pagetitle = _('User Lookup');
		require_once "../header.php";
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
			$stm = $DBH->prepare($query);
			$stm->execute($qarr);
			if ($stm->rowCount()==0) {
				echo "No results found";
			} else {
				$group_stm = $DBH->prepare('SELECT name FROM imas_groups WHERE id=:id');
				$stu_stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_students AS istu ON istu.courseid=ic.id AND istu.userid=:userid");
				$tutor_stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_tutors AS istu ON istu.courseid=ic.id AND istu.userid=:userid");
				$teach_stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS istu ON istu.courseid=ic.id AND istu.userid=:userid");
				$lti_stm = $DBH->prepare("SELECT org,id,ltiuserid FROM imas_ltiusers WHERE userid=:userid");
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					echo '<p><b><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($row['LastName']).', '.Sanitize::encodeStringForDisplay($row['FirstName']).'</span></b></p>';
					echo '<form method="post" action="../admin/actions.php?id='.Sanitize::encodeUrlParam($row['id']).'">';
					echo '<input type=hidden name=action value="resetpwd" />';
					echo '<ul><li>Username: <a href="../admin/admin2.php?showcourses='.Sanitize::encodeUrlParam($row['id']).'"><span class="pii-username">'.Sanitize::encodeStringForDisplay($row['SID']).'</span></a></li>';
					echo '<li>ID: '.$row['id'].'</li>';
					if ($row['name']!=null) {
						echo '<li>Group: '.Sanitize::encodeStringForDisplay($row['name']);
						//call hook, if defined
						if (function_exists('onUserLookup')) {
							echo onUserLookup($row['grouptype']);
						}
						echo '</li>';
						if ($row['parent']>0) {
							$group_stm->execute(array(':id'=>$row['parent']));
							$r = $group_stm->fetch(PDO::FETCH_NUM);
							echo '<li>Parent Group: '.Sanitize::encodeStringForDisplay($r[0]).'</li>';
						}
					}
					echo '<li><a href="utils.php?emulateuser='.Sanitize::encodeUrlParam($row['id']).'">Emulate User</li>';
					echo '<li>Email: <span class="pii-email">'.Sanitize::encodeStringForDisplay($row['email']).'</span></li>';
					echo '<li>Last Login: '.tzdate("n/j/y g:ia", $row['lastaccess']).'</li>';
					echo '<li>Rights: '.Sanitize::encodeStringForDisplay($row['rights']).' <a href="'.$imasroot.'/admin/forms.php?action=chgrights&id='.Sanitize::encodeUrlParam($row['id']).'">[edit]</a></li>';
					echo '<li>Reset Password to <input type="text" name="newpw"/> <input type="submit" value="'._('Go').'"/></li>';
					$stu_stm->execute(array(':userid'=>$row['id']));
					if ($stu_stm->rowCount()>0) {
						echo '<li>Enrolled as student in: <ul>';
						while ($r = $stu_stm->fetch(PDO::FETCH_NUM)) {
							echo '<li><a target="_blank" href="../course/course.php?cid='.Sanitize::encodeUrlParam($r[0]).'">'.Sanitize::encodeStringForDisplay($r[1]).' (ID '.Sanitize::encodeStringForDisplay($r[0]).')</a></li>';
						}
						echo '</ul></li>';
					}
					$tutor_stm->execute(array(':userid'=>$row['id']));
					if ($tutor_stm->rowCount()>0) {
						echo '<li>Tutor in: <ul>';
						while ($r = $tutor_stm->fetch(PDO::FETCH_NUM)) {
							echo '<li><a target="_blank" href="../course/course.php?cid='.Sanitize::encodeUrlParam($r[0]).'">'.Sanitize::encodeStringForDisplay($r[1]).' (ID '.Sanitize::encodeStringForDisplay($r[0]).')</a></li>';
						}
						echo '</ul></li>';
					}
					$teachercourses = array();
					$teach_stm->execute(array(':userid'=>$row['id']));
					if ($teach_stm->rowCount()>0) {
						echo '<li>Teacher in: <ul>';
						while ($r = $teach_stm->fetch(PDO::FETCH_NUM)) {
							echo '<li><a target="_blank" href="../course/course.php?cid='.Sanitize::encodeUrlParam($r[0]).'">'.Sanitize::encodeStringForDisplay($r[1]).' (ID '.Sanitize::encodeStringForDisplay($r[0]).')</a></li>';
							$teachercourses[] = $r[0];
						}
						echo '</ul></li>';
					}
					$lti_stm->execute(array(':userid'=>$row['id']));
					if ($lti_stm->rowCount()>0) {
						echo '<li>LTI user connections: <ul>';
						while ($r = $lti_stm->fetch(PDO::FETCH_NUM)) {
							echo '<li>key:'.Sanitize::encodeStringForDisplay(substr($r[0],0,strpos($r[0],':'))).', remote userid:'.Sanitize::encodeStringForDisplay($r[2]).' <a href="utils.php?removelti='.Sanitize::encodeUrlParam($r[1]).'">Remove connection</a></li>';
						}
						echo '</ul></li>';
					}
					if (count($teachercourses)>0) {
						$query = "SELECT org,id,courseid,contextid FROM imas_lti_courses WHERE courseid IN (".implode(",",$teachercourses).")";
						$lti_c_stm = $DBH->query($query);
						if ($lti_c_stm->rowCount()>0) {
							echo '<li>LTI course connections: <ul>';
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
			echo 'Look up user:  LastName: <input type="text" class="pii-last-name" name="LastName" />, FirstName: <input type="text" class="pii-first-name" name="FirstName" />, or username: <input type="text" class="pii-username" name="SID"/>, or email: <input type="text" class="pii-email" name="email"/>';
			echo '<input type="submit" value="Go"/>';
			echo '</form>';
		}
		require_once "../footer.php";

	}


} else {
	//listing of utilities
	$pagetitle = _('Admin Utilities');
	require_once "../header.php";
	echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Utilities</div>';
	echo '<h2>Admin Utilities </h2>';
	if (isset($_GET['debug'])) {
		echo '<p>Debug Mode Enabled - Error reporting is now turned on.</p>';
	}
	echo '<a href="utils.php?form=lookup">User lookup</a><br/>';
	echo '<a href="'.$imasroot.'/admin/approvepending2.php">Approve Pending Instructor Accounts</a><br/>';
	echo '<a href="utils.php?form=jumptoitem">Jump to Item</a><br/>';
	echo '<a href="batchcreateinstr.php">Batch Create Instructor Accounts</a><br/>';
	echo '<a href="batchanon.php">Batch Anonymize Old Accounts</a><br/>';
	echo '<a href="getstucnt.php">Get Student Count</a><br/>';
	echo '<a href="crosscoursedata.php">Cross-Course Grade Comparison</a><br/>';
	echo '<a href="crosscoursedatadetail.php">Cross-Course Question-Level Comparison</a><br/>';
	echo '<a href="getstucntdet.php">Get Detailed Student Count</a><br/>';
	echo '<a href="utils.php?debug=true">Enable Debug Mode</a><br/>';
	echo '<a href="replacevids.php">Replace YouTube videos</a><br/>';
	echo '<a href="utils.php?form=updatecaption">Update YouTube video caption data by video ID</a><br/>';
	echo '<a href="utils.php?form=updatecaptionbyqids">Update YouTube video caption data by Question IDs</a><br/>';
	echo '<a href="utils.php?form=exportcaptions">Export Captions database</a><br/>';
	echo '<a href="utils.php?form=importcaptions">Import to Captions database</a><br/>';
	echo '<a href="replaceurls.php">Replace URLS</a><br/>';
	echo '<a href="utils.php?form=rescue">Recover lost items</a><br/>';
	echo '<a href="utils.php?fixorphanqs=true">Fix orphaned questions</a><br/>';
	echo '<a href="utils.php?fixdupgrades=true">Fix duplicate forum grades</a><br/>';
	echo '<a href="utils.php?form=emu">Emulate User</a><br/>';
	echo '<a href="utils.php?listadmins=true">List Admins</a><br/>';
	echo '<a href="listextref.php">List ExtRefs</a><br/>';
	echo '<a href="updateextref.php">Update ExtRefs</a><br/>';
	echo '<a href="delwronglibs.php">Delete Questions with WrongLib Flag</a><br/>';
	echo '<a href="listwronglibs.php">List WrongLibFlags</a><br/>';
	echo '<a href="updatewronglibs.php">Update WrongLibFlags</a><br/>';
	echo '<a href="blocksearch.php">Search Block titles</a><br/>';
	echo '<a href="itemsearch.php">Search inline/linked items</a><br/>';
    echo '<a href="utils.php?form=setupfcm">Set up FCM for push notifications</a><br/>';
	require_once "../footer.php";
}
?>
