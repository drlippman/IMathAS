<?php
//IMathAS:  Admin actions
//(c) 2006 David Lippman
require("../init.php");
require_once("../includes/password.php");
require_once("../includes/TeacherAuditLog.php");

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['admin/actions'])) {
	require($CFG['hooks']['admin/actions']);
}

$from = 'admin';
if (isset($_GET['from'])) {
	if ($_GET['from']=='home') {
		$from = 'home';
	} else if ($_GET['from']=='admin2') {
		$from = 'admin2';
	} else if ($_GET['from']=='userreports') {
		$from = 'userreports';
	} else if (substr($_GET['from'],0,2)=='ud') {
		$userdetailsuid = Sanitize::onlyInt(substr($_GET['from'],2));
		$from = 'ud'.$userdetailsuid;
		$backloc = 'userdetails.php?id='.$userdetailsuid;
	} else if (substr($_GET['from'],0,2)=='gd') {
		$groupdetailsgid = Sanitize::onlyInt(substr($_GET['from'],2));
		$from = 'gd'.$groupdetailsgid;
		$backloc = 'admin2.php?groupdetails='.Sanitize::encodeUrlParam($groupdetailsgid);
	}
}
if ($from=='admin') {
	$breadcrumbbase .= '<a href="admin.php">Admin</a> &gt; ';
} else if ($from == 'admin2') {
	$breadcrumbbase .= '<a href="admin2.php">Admin</a> &gt; ';
} else if ($from == 'userreports') {
	$breadcrumbbase .= '<a href="userreports.php">'._('User Reports').'</a> &gt; ';
} else if (substr($_GET['from'],0,2)=='ud') {
	$breadcrumbbase .= '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$backloc.'">'._('User Details').'</a> &gt; ';
} else if (substr($_GET['from'],0,2)=='gd') {
	$breadcrumbbase .= '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$backloc.'">'._('Group Details').'</a> &gt; ';
}

switch($_POST['action']) {
	case "emulateuser":
		if ($myrights < 100 ) { break;}
		$be = $_REQUEST['uid'];
		$_SESSION['userid'] = $be;
		break;
	case "chgrights":
		if ($myrights < 75 && ($myspecialrights&16)!=16 && ($myspecialrights&32)!=32) {
			echo _("You don't have the authority for this action");
			break;
		}
		if ($_POST['newrights']>$myrights) {
			$_POST['newrights'] = $myrights;
		}
		$stm = $DBH->prepare("SELECT rights,groupid,jsondata FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		list($oldrights,$oldgroupid,$oldjsondata) = $stm->fetch(PDO::FETCH_NUM);
		if ($row === false) {
			echo _("invalid id");
			exit;
		} else if ($myrights < 100 && ($myspecialrights&32)!=32 && $oldgroupid!=$groupid) {
			echo "You don't have the authority for this action";
			exit;
		}

		$stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
		$stm->execute(array(':SID'=>Sanitize::stripHtmlTags($_POST['SID'])));
		$row = $stm->fetch(PDO::FETCH_NUM);
		$chgSID = true;
		if ($row != null) {
			$chgSID = false;
		}

		$specialrights = 0;
		if (isset($_POST['specialrights1']) && ($myrights==100 || ($myrights>=75 && ($myspecialrights&1)==1))) {
			$specialrights += 1;
		}
		if (isset($_POST['specialrights2']) && $myrights==100) {
			$specialrights += 2;
		}
		if (isset($_POST['specialrights4']) && ($myrights==100 || ($myrights>=75 && ($myspecialrights&4)==4))) {
			$specialrights += 4;
		}
		if (isset($_POST['specialrights8']) && (($myrights==100 || ($myrights>=75 && ($myspecialrights&8)==8)) && !$allownongrouplibs)) {
			$specialrights += 8;
		}
		if ((isset($_POST['specialrights16']) && $myrights>=75) || $_POST['newrights']>=75) {
			$specialrights += 16;
		}
		if ((isset($_POST['specialrights32']) && $myrights==100) || $_POST['newrights']==100) {
			$specialrights += 32;
		}
		if ((isset($_POST['specialrights64']) && $myrights==100) || $_POST['newrights']==100) {
			$specialrights += 64;
		}
		if (isset($CFG['GEN']['newpasswords'])) {
			$hashpw = password_hash($_POST['newpassword'], PASSWORD_DEFAULT);
		} else {
			$hashpw = md5($_POST['newpassword']);
		}
		if ($_POST['newrights']>$myrights) { //checked above, but do it again
			$_POST['newrights'] = $myrights;
		}

		$arr = array(':rights'=>$_POST['newrights'], ':specialrights'=>$specialrights, ':id'=>$_GET['id'],
				':FirstName'=>Sanitize::stripHtmlTags($_POST['firstname']),
				':LastName'=>Sanitize::stripHtmlTags($_POST['lastname']),
				':email'=>Sanitize::stripHtmlTags($_POST['email']));
		if ($chgSID) {
			$arr[':SID'] = Sanitize::stripHtmlTags($_POST['SID']);
		}
		if (isset($_POST['doresetpw'])) {
			$arr[':password'] = $hashpw;
		}
        $chgJsondata = false;
        if (($myrights >= 75 || ($myspecialrights&48)>0) && isset($CFG['GEN']['COPPA'])) {
            $oldjsondata = json_decode($oldjsondata, true);
            if ($oldjsondata === null) {
                $oldjsondata = [];
            }
            if (empty($_POST['over13'])) {
                $oldjsondata['under13'] = 1;
            } else {
                unset($oldjsondata['under13']);
            }
            $chgJsondata = true;
            $arr[':jsondata'] = json_encode($oldjsondata);
        }

		if ($myrights == 100 || ($myspecialrights&32)==32) { //update library groupids
			if ($_POST['group']==-1) {
				if (trim($_POST['newgroupname'])!='') {
					//check for existing with same name
					$stm = $DBH->prepare("SELECT id FROM imas_groups WHERE name REGEXP ?");
					$stm->execute(array('^[[:space:]]*'.str_replace('.','[.]',preg_replace('/\s+/', '[[:space:]]+', trim($_POST['newgroupname']))).'[[:space:]]*$'));
					$newgroup = $stm->fetchColumn(0);
					if ($newgroup === false) {
						$defGrouptype = isset($CFG['GEN']['defGroupType'])?$CFG['GEN']['defGroupType']:0;
						$stm = $DBH->prepare("INSERT INTO imas_groups (name,grouptype) VALUES (:name,:grouptype)");
						$stm->execute(array(':name'=>Sanitize::stripHtmlTags(trim($_POST['newgroupname'])), ':grouptype'=>$defGrouptype));
						$newgroup = $DBH->lastInsertId();
					}
				} else {
					$newgroup = $oldgroupid;
				}
			} else {
				$newgroup = Sanitize::onlyInt($_POST['group']);
			}
			$arr[':groupid'] = $newgroup;

			$query = "UPDATE imas_users SET rights=:rights,specialrights=:specialrights,groupid=:groupid,FirstName=:FirstName,LastName=:LastName,email=:email";
			if ($chgSID) {
				$query .= ',SID=:SID';
			}
            if ($chgJsondata) {
                $query .= ',jsondata=:jsondata';
            }
			if (isset($_POST['doresetpw'])) {
				$query .= ',password=:password,forcepwreset=1';
			}
			$query .= " WHERE id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute($arr);
			$stm = $DBH->prepare("UPDATE imas_libraries SET groupid=:groupid WHERE ownerid=:ownerid");
			$stm->execute(array(':groupid'=>$_POST['group'], ':ownerid'=>$_GET['id']));
		} else {
			$newgroup = $groupid;
			$arr[':groupid'] = $groupid;

			$query = "UPDATE imas_users SET rights=:rights,specialrights=:specialrights,FirstName=:FirstName,LastName=:LastName,email=:email";
			if ($chgSID) {
				$query .= ',SID=:SID';
			}
            if ($chgJsondata) {
                $query .= ',jsondata=:jsondata';
            }
			if (isset($_POST['doresetpw'])) {
				$query .= ',password=:password';
			}
			$query .= " WHERE id=:id AND groupid=:groupid AND rights<100";
			$stm = $DBH->prepare($query);
			$stm->execute($arr);
		}

		//if student being promoted, enroll in teacher enroll courses
		if ($oldrights<=10 && $_POST['newrights']>=20) {
            if (isset($CFG['GEN']['enrollonnewinstructor'])) {
                $stm = $DBH->prepare("SELECT courseid FROM imas_students WHERE userid=?");
                $stm->execute([$_GET['id']]);
                $existingEnroll = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
                $toEnroll = array_diff($CFG['GEN']['enrollonnewinstructor'], $existingEnroll);
                if (count($toEnroll) > 0) {
                    $valbits = array();
                    $valvals = array();
                    foreach ($toEnroll as $ncid) {
                        $valbits[] = "(?,?)";
                        array_push($valvals, $_GET['id'], $ncid);
                    }
                    $stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid) VALUES ".implode(',',$valbits));
                    $stm->execute($valvals);
                }
            }


			//log new account
			$now = time();
			$stm = $DBH->prepare("INSERT INTO imas_log (time, log) VALUES (:now, :log)");
			$stm->execute(array(':now'=>$now, ':log'=>"New Instructor Request: ".Sanitize::onlyInt($_GET['id']).":: Group: $newgroup, upgraded by $userid"));

			$stm = $DBH->prepare("SELECT reqdata FROM imas_instr_acct_reqs WHERE userid=?");
			$stm->execute(array($_GET['id']));
			$reqdata = $stm->fetchColumn(0);

			if ($reqdata === false) {
				$reqdata = array('upgraded'=>$now, 'actions'=>array(array('on'=>$now, 'by'=>$userid, 'status'=>11, 'via'=>'chgrights')));
				$stm = $DBH->prepare("INSERT INTO imas_instr_acct_reqs (userid,status,reqdate,reqdata) VALUES (?,11,?,?)");
				$stm->execute(array($_GET['id'], $now, json_encode($reqdata)));
			} else {
				$reqdata = json_decode($reqdata, true);
				if (!isset($reqdata['actions'])) {
					$reqdata['actions'] = array();
				}
				$reqdata['actions'][] = array('on'=>$now, 'by'=>$userid, 'status'=>11, 'via'=>'chgrights');
				$stm = $DBH->prepare("UPDATE imas_instr_acct_reqs SET reqdata=? where userid=?");
				$stm->execute(array(json_encode($reqdata), $_GET['id']));
			}

		} else if ($oldrights>10 && $_POST['newrights']<=10 && isset($CFG['GEN']['enrollonnewinstructor'])) {
			require_once("../includes/unenroll.php");
			foreach ($CFG['GEN']['enrollonnewinstructor'] as $ncid) {
				unenrollstu($ncid, array($_GET['id']));
			}
		}

		if ($chgSID==false && $row[0]!=$_GET['id']) {
			echo "Username in use - left unchanged";
			exit;
		}

		break;
	case "resetpwd":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		if (isset($_POST['newpw'])) {
			if (isset($CFG['GEN']['newpasswords'])) {
				$md5pw = password_hash($_POST['newpw'], PASSWORD_DEFAULT);
			} else {
				$md5pw = md5($_POST['newpw']);
			}
		} else {
			if (isset($CFG['GEN']['newpasswords'])) {
				$md5pw = password_hash("password", PASSWORD_DEFAULT);
			} else {
				$md5pw =md5("password");
			}
		}
		if ($myrights < 100) {
			$stm = $DBH->prepare("UPDATE imas_users SET password=:password WHERE id=:id AND groupid=:groupid AND rights<100");
			$stm->execute(array(':password'=>$md5pw, ':id'=>$_GET['id'], ':groupid'=>$groupid));
		} else {
			$stm = $DBH->prepare("UPDATE imas_users SET password=:password WHERE id=:id");
			$stm->execute(array(':password'=>$md5pw, ':id'=>$_GET['id']));
		}
		break;
	case "anonuser":
		if ($myrights < 100) { echo "You don't have the authority for this action"; break;}
		$deluid = Sanitize::onlyInt($_GET['id']);
		$v = uniqid('anon');
		$newemail = Sanitize::emailAddress($_POST['anonemail']);
		if ($_POST['anontype']=='full') {
			$query = "UPDATE imas_users SET FirstName=?,LastName=?,email=?,SID=?,password=?,msgnotify=0 WHERE id=?";
			$qarr = array($v, $v, $newemail, $v, $v, $deluid);
		} else {
			$query = "UPDATE imas_users SET email=?,SID=?,password=?,msgnotify=0 WHERE id=?";
			$qarr = array($newemail, $v, $v, $deluid);
		}
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);
		break;
	case "deladmin":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		$deluid = Sanitize::onlyInt($_GET['id']);
		if ($myrights < 100) {
			$stm = $DBH->prepare("DELETE FROM imas_users WHERE id=:id AND groupid=:groupid AND rights<100");
			$stm->execute(array(':id'=>$deluid, ':groupid'=>$groupid));
		} else {
			$stm = $DBH->prepare("DELETE FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$deluid));
		}
		if ($stm->rowCount()==0) { break;}
		$toDelTable = array('user_prefs', 'students', 'teachers', 'tutors',
			'assessment_sessions', 'exceptions', 'bookmarks', 'content_track',
			'forum_views', 'forum_subscriptions', 'grades', 'ltiusers', 'stugroupmembers');
		foreach ($toDelTable as $table) {
			$stm = $DBH->prepare("DELETE FROM imas_$table WHERE userid=:userid");
			$stm->execute(array(':userid'=>$deluid));
		}

		$stm = $DBH->prepare("DELETE FROM imas_diags WHERE ownerid=:userid");
		$stm->execute(array(':userid'=>$deluid));
		$stm = $DBH->prepare("DELETE FROM imas_rubrics WHERE ownerid=:userid AND groupid=-1");
		$stm->execute(array(':userid'=>$deluid));
		//soft-delete courses
		$stm = $DBH->prepare("UPDATE imas_courses SET available=4 WHERE ownerid=:userid");
		$stm->execute(array(':userid'=>$deluid));

		//leave any forum posts and wiki revisions - don't want to break anything

		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE msgto=:msgto");
		$stm->execute(array(':msgto'=>$deluid));
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE msgfrom=:msgfrom AND deleted=2");
		$stm->execute(array(':msgfrom'=>$deluid));
		$stm = $DBH->prepare("UPDATE imas_msgs SET deleted=1 WHERE msgfrom=:msgfrom");
		$stm->execute(array(':msgfrom'=>$deluid));

		require_once("../includes/filehandler.php");
		//delete profile pics
		deletecoursefile('userimg_'.$deluid.'.jpg');
		deletecoursefile('userimg_sm'.$deluid.'.jpg');
		//delete all user uploads
		deletealluserfiles($deluid);
		//change owner of libraries and questions
		if (!empty($_POST['transferto'])) {
			$dest_uid = Sanitize::onlyInt($_POST['transferto']);
			$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=?");
			$stm->execute(array($dest_uid));
			$dest_groupid = $stm->fetchColumn(0);
			$stm = $DBH->prepare("UPDATE imas_questionset SET ownerid=? WHERE ownerid=?");
			$stm->execute(array($dest_uid, $deluid));
			$stm = $DBH->prepare("UPDATE imas_libraries SET ownerid=?,groupid=? WHERE ownerid=?");
			$stm->execute(array($dest_uid, $dest_groupid, $deluid));
			$stm = $DBH->prepare("UPDATE imas_library_items SET ownerid=? WHERE ownerid=?");
			$stm->execute(array($dest_uid, $deluid));
		}
		break;
	case "newadmin":
		if ($myrights < 75 && ($myspecialrights&16)!=16 && ($myspecialrights&32)!=32) { echo _("You don't have the authority for this action"); break;}
		if ($_POST['newrights']>$myrights) {
			$_POST['newrights'] = $myrights;
		}
		$stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
		$stm->execute(array(':SID'=>$_POST['SID']));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if ($row != null) {
			echo "<html><body>",_("Username is already used."),"\n";
			echo "<a href=\"forms.php?action=newadmin\">",_("Try Again"),"</a> ",_("or")," ";
			echo "<a href=\"forms.php?action=chgrights&id={$row[0]}\">",_("Change rights for existing user"),"</a></body></html>\n";
			exit;
		}
		if (isset($CFG['GEN']['newpasswords'])) {
			$md5pw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
		} else {
			$md5pw =md5($_POST['pw1']);
		}
		if ($myrights == 100 || ($myspecialrights&32)==32) {
			if ($_POST['group']==-1) {
				if (trim($_POST['newgroupname'])!='') {
					//check for existing with same name
					$stm = $DBH->prepare("SELECT id FROM imas_groups WHERE name REGEXP ?");
					$stm->execute(array('^[[:space:]]*'.str_replace('.','[.]',preg_replace('/\s+/', '[[:space:]]+', trim($_POST['newgroupname']))).'[[:space:]]*$'));
					$newgroup = $stm->fetchColumn(0);
					if ($newgroup === false) {
						$newGroupName = Sanitize::stripHtmlTags(trim($_POST['newgroupname']));
						$defGrouptype = isset($CFG['GEN']['defGroupType'])?$CFG['GEN']['defGroupType']:0;
						$stm = $DBH->prepare("INSERT INTO imas_groups (name,grouptype) VALUES (:name,:grouptype)");
						$stm->execute(array(':name'=>$newGroupName, ':grouptype'=>$defGrouptype));
						$newgroup = $DBH->lastInsertId();
					}
				} else {
					$newgroup = 0;
				}
			} else {
				$newgroup = Sanitize::onlyInt($_POST['group']);
			}
		} else {
			$newgroup = $groupid;
		}
		if (isset($CFG['GEN']['homelayout'])) {
			$homelayout = $CFG['GEN']['homelayout'];
		} else {
			$homelayout = '|0,1,2||0,1';
		}
		$specialrights = 0;
		if (isset($_POST['specialrights1']) && ($myrights==100 || ($myrights>=75 && ($myspecialrights&1)==1))) {
			$specialrights += 1;
		}
		if (isset($_POST['specialrights2']) && $myrights==100) {
			$specialrights += 2;
		}
		if (isset($_POST['specialrights4']) && ($myrights==100 || ($myrights>=75 && ($myspecialrights&4)==4))) {
			$specialrights += 4;
		}
		if (isset($_POST['specialrights8']) && (($myrights==100 || ($myrights>=75 && ($myspecialrights&8)==8)) && !$allownongrouplibs)) {
			$specialrights += 8;
		}
		if (isset($_POST['specialrights16']) && ($myrights==100 || ($myrights>=75 && ($myspecialrights&16)==16))) {
			$specialrights += 16;
		}
		if (isset($_POST['specialrights32']) && $myrights==100) {
			$specialrights += 32;
		}
		if (isset($_POST['specialrights64']) && $myrights==100) {
			$specialrights += 64;
		}
        $jsondata = [];
        if (($myrights >= 75 || ($myspecialrights&48)>0) && isset($CFG['GEN']['COPPA'])) {
            if (empty($_POST['over13'])) {
                $jsondata['under13'] = 1;
            }
        }
		$stm = $DBH->prepare("INSERT INTO imas_users (SID,password,FirstName,LastName,rights,email,groupid,homelayout,specialrights,jsondata) VALUES (:SID, :password, :FirstName, :LastName, :rights, :email, :groupid, :homelayout, :specialrights, :jsondata);");
		$stm->execute(array(':SID'=>$_POST['SID'],
			':password'=>$md5pw,
			':FirstName'=>Sanitize::stripHtmlTags($_POST['firstname']),
			':LastName'=>Sanitize::stripHtmlTags($_POST['lastname']),
			':rights'=>$_POST['newrights'],
			':email'=>Sanitize::emailAddress($_POST['email']),
			':groupid'=>$newgroup,
			':homelayout'=>$homelayout,
			':specialrights'=>$specialrights,
            ':jsondata'=>json_encode($jsondata)));
		$newuserid = $DBH->lastInsertId();
		if (isset($CFG['GEN']['enrollonnewinstructor']) && $_POST['newrights']>=20) {
			$valbits = array();
			$valvals = array();
			foreach ($CFG['GEN']['enrollonnewinstructor'] as $ncid) {
				$valbits[] = "(?,?)";
				array_push($valvals, $newuserid,$ncid);
			}
			$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid) VALUES ".implode(',',$valbits));
			$stm->execute($valvals);
		}
		if ($_POST['newrights']>=20) {
			//log new account
			$now = time();
			$stm = $DBH->prepare("INSERT INTO imas_log (time, log) VALUES (:now, :log)");
			$stm->execute(array(':now'=>$now, ':log'=>"New Instructor Request: $newuserid:: Group: $newgroup, manually added by $userid"));

			$reqdata = array('added'=>$now, 'actions'=>array(array('by'=>$userid, 'on'=>$now, 'status'=>11, 'via'=>'addadmin')));
			$stm = $DBH->prepare("INSERT INTO imas_instr_acct_reqs (userid,status,reqdate,reqdata) VALUES (?,11,?,?)");
			$stm->execute(array($newuserid, $now, json_encode($reqdata)));
		}
		if ($_POST['newrights']>=20 && !empty($_POST['addnewcourse'])) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/addcourse.php?for=".Sanitize::onlyInt($newuserid));
			exit;
		}
		break;
	case "logout":
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/', '',false ,true );

		}
		session_destroy();
		break;
	case "modify":
	case "addcourse":
		if ($myrights < 40) { echo _("You don't have the authority for this action"); break;}
		require_once("../includes/parsedatetime.php");

		if (isset($CFG['CPS']['templateoncreate']) && isset($_POST['usetemplate']) && $_POST['usetemplate']>0) {
			$coursetocheck = intval($_POST['usetemplate']);
			$stm = $DBH->prepare("SELECT termsurl FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$coursetocheck));
			$terms = $stm->fetch(PDO::FETCH_NUM);
			if ($terms[0]!='') {
				if (!isset($_POST['termsagree'])) {
					require("../header.php");
					echo '<p>',_('You must agree to the terms of use to copy this course.'),'</p>';
					require("../footer.php");
					exit;
				} else {
					$now = time();
					$userid = intval($userid);
					$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (:time, :log)");
					$stm->execute(array(':time'=>$now, ':log'=>"User $userid agreed to terms of use on course $coursetocheck"));
				}
			}
		}
		if (isset($_GET['id'])) {
			$stm = $DBH->prepare("SELECT * FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			$old_course_settings = $stm->fetch(PDO::FETCH_ASSOC);
			if (isset($CFG['cleanup']['groups'][$groupid]['allowoptout'])) {
				$allowoptout = $CFG['cleanup']['groups'][$groupid]['allowoptout'];
			} else {
				$allowoptout = (!isset($CFG['cleanup']['allowoptout']) || $CFG['cleanup']['allowoptout']==true);
			}
			if ($allowoptout && isset($_POST['cleanupoptout'])) {
				$old_course_settings['cleanupdate'] = 0;
			}
		} else {
			$old_course_settings['istemplate'] = 0;
		}
		if (isset($CFG['CPS']['theme']) && $CFG['CPS']['theme'][1]==0) {
			$theme = $CFG['CPS']['theme'][0];
		} else {
			$theme = $_POST['theme'];
		}

		if (isset($CFG['CPS']['copyrights']) && $CFG['CPS']['copyrights'][1]==0) {
			$copyrights = $CFG['CPS']['copyrights'][0];
		} else {
			$copyrights = $_POST['copyrights'];
		}

		if (isset($CFG['CPS']['msgset']) && $CFG['CPS']['msgset'][1]==0) {
			$msgset = $CFG['CPS']['msgset'][0];
		} else {
			$msgset = $_POST['msgset'];
			if (isset($_POST['msgmonitor'])) {
				$msgset += 5;
			}
			if (isset($_POST['msgonenroll'])) {
				$msgset += 5*2;
			}
		}

		if (isset($CFG['CPS']['deftime']) && $CFG['CPS']['deftime'][1]==0) {
			$deftime = $CFG['CPS']['deftime'][0];
		} else {
			$backuptime = isset($CFG['CPS']['deftime']) ? $CFG['CPS']['deftime'][0] : 600;

			$deftime = parsetime($_POST['deftime'], $backuptime);
			$defstime = parsetime($_POST['defstime'], $deftime);
			// the extra 100000000 is to ensure the value is >10000 when defstime = 0
			$deftime += 10000*$defstime + 100000000;
		}

		if (isset($CFG['CPS']['deflatepass']) && $CFG['CPS']['deflatepass'][1]==0) {
			$deflatepass = $CFG['CPS']['deflatepass'][0];
		} else {
			$deflatepass = intval($_POST['deflatepass']);
		}
		if (isset($CFG['CPS']['latepasshrs']) && $CFG['CPS']['latepasshrs'][1]==0) {
			$latepasshrs = $CFG['CPS']['latepasshrs'][0];
		} else {
			$latepasshrs = intval($_POST['latepasshrs']);
		}

		if (isset($CFG['CPS']['showlatepass']) && $CFG['CPS']['showlatepass'][1]==0) {
			$showlatepass = intval($CFG['CPS']['showlatepass'][0]);
		} else {
			if (isset($_POST['showlatepass'])) {
				$showlatepass = 1;
			} else {
				$showlatepass = 0;
			}
		}

		if (isset($CFG['CPS']['toolset']) && $CFG['CPS']['toolset'][1]==0) {
			$toolset = $CFG['CPS']['toolset'][0];
		} else {
			$toolset = 1*!isset($_POST['toolset-cal']) + 2*!isset($_POST['toolset-forum']) + 4*!isset($_POST['toolset-reord']);
		}

		$avail = 1 - $_POST['stuavail'];

		$istemplate = 0;
		if (($myspecialrights&1)==1 || $myrights==100) {
			if (isset($_POST['isgrptemplate'])) {
				$istemplate |= 2;
			}
		} else if (($old_course_settings['istemplate']&2)==2) {
			$istemplate |= 2;
		}
		if (($myspecialrights&2)==2 || $myrights==100) {
			if (isset($_POST['istemplate'])) {
				$istemplate |= 1;
			}
		} else if (($old_course_settings['istemplate']&1)==1) {
			$istemplate |= 1;
		}
		if (($myspecialrights&2)==2 || $myrights==100) {
			if (isset($_POST['issupergrptemplate'])) {
				$istemplate |= 32;
			}
		} else if (($old_course_settings['istemplate']&32)==32) {
			$istemplate |= 32;
		}
		if ($myrights==100) {
			if (isset($_POST['isselfenroll'])) {
				$istemplate |= 4;
			}
			if (isset($_POST['isguest'])) {
				$istemplate |= 8;
			}
		}

        $unenroll = 0;
        if ((isset($CFG['CPS']['unenroll']) && $CFG['CPS']['unenroll'][1]==1) ||
            ($myrights == 100 && ($istemplate&4)==4)
        ) {
            $unenroll = $_POST['allowunenroll'];
        } else if (isset($CFG['CPS']['unenroll'])) {
            $unenroll = $CFG['CPS']['unenroll'][0];
        }
        $unenroll += empty($_POST['allowenroll']) ? 2 : 0;

		if (!isset($CFG['coursebrowserRightsToPromote'])) {
			$CFG['coursebrowserRightsToPromote'] = 40;
		}
        $updateJsonData = false;
        if (isset($old_course_settings['jsondata'])) {
            $jsondata = json_decode($old_course_settings['jsondata'], true);
        } else {
            $jsondata = null;
        }
		if ($jsondata===null) {
			$jsondata = array();
		}
		if ($CFG['coursebrowserRightsToPromote']>$myrights && ($old_course_settings['istemplate']&16)==16) {
			$istemplate |= 16;
		} else if (isset($_POST['promote']) && isset($_GET['id']) && $CFG['coursebrowserRightsToPromote']<=$myrights) {
			$browserprops = json_decode(file_get_contents(__DIR__.'/../javascript/'.$CFG['coursebrowser'], false, null, 25), true);

			$isok = ($copyrights>1);

			$browserdata = array();
			foreach ($browserprops as $propname=>$propvals) {
				if (!empty($propvals['required']) && trim($_POST['browser'.$propname]) == '' &&
					!($propvals['required']==2 && ($istemplate&3)>0)) {
					$isok = false;
					break;
				}
				if (!empty($propvals['multi'])) { //multiple values
					if (isset($_POST['browser'.$propname])) {
						$browserdata[$propname] = array_map('Sanitize::simpleString', $_POST['browser'.$propname]);
					} else {
						$browserdata[$propname] = array();
					}
				} else { //single val
					$browserdata[$propname] = Sanitize::stripHtmlTags($_POST['browser'.$propname]);
				}
				if ($_POST['browser'.$propname]=='other') {
					$browserdata[$propname.'other'] = Sanitize::stripHtmlTags($_POST['browser'.$propname.'other']);
				}
			}

			if ($isok) {
				$istemplate |= 16;
				$jsondata['browser'] = $browserdata;
				$updateJsonData = true;
			}
		}
		if ($myrights>=75) {
			if (!empty($jsondata['blockLTICopyOfCopies']) && !isset($_POST['blocklticopies'])) { //un-checked
				$jsondata['blockLTICopyOfCopies'] = false;
				$updateJsonData = true;
			} else if (isset($_POST['blocklticopies'])) { //checking it
				$jsondata['blockLTICopyOfCopies'] = true;
				$updateJsonData = true;
			}
		}

		if (trim($_POST['sdate'])=='') {
			$startdate = 0;
		} else {
			$startdate = parsedatetime($_POST['sdate'],'12:01am',0);
		}
		if (trim($_POST['edate'])=='') {
			$enddate = 2000000000;
		} else {
			$enddate = parsedatetime($_POST['edate'],'11:59pm',2000000000);
		}
		$_POST['ltisecret'] = trim($_POST['ltisecret'] ?? '');
		if (isset($_POST['setdatesbylti']) && $_POST['setdatesbylti']==1) {
			$setdatesbylti = 1;
		} else {
			$setdatesbylti = 0;
		}
		if (trim($_POST['coursename'])=='') {
			$_POST['coursename'] = '(No name provided)';
		}
		if (isset($_POST['courselevel'])) {
			$_POST['courselevel'] = Sanitize::stripHtmlTags($_POST['courselevel']);
			if ($_POST['courselevel'] == 'other') {
				$_POST['courselevel'] .= Sanitize::stripHtmlTags($_POST['courselevelother']);
			}
		} else {
			$_POST['courselevel'] = '';
        }
        $ltisendzeros = !empty($_POST['ltisendzeros']) ? 1 : 0;

		if ($_POST['action']=='modify') {
			$query = "UPDATE imas_courses SET name=:name,enrollkey=:enrollkey,available=:available,lockaid=:lockaid,showlatepass=:showlatepass,";
			if ($updateJsonData) {
				$query .= "jsondata=:jsondata,";
			}
            $query .= "allowunenroll=:allowunenroll,copyrights=:copyrights,msgset=:msgset,
                toolset=:toolset,theme=:theme,ltisecret=:ltisecret,istemplate=:istemplate,
                deftime=:deftime,deflatepass=:deflatepass,latepasshrs=:latepasshrs,
                dates_by_lti=:ltidates,startdate=:startdate,enddate=:enddate,
                cleanupdate=:cleanupdate,level=:level,ltisendzeros=:ltisendzeros WHERE id=:id";
            $qarr = array(':name'=>$_POST['coursename'], ':enrollkey'=>$_POST['ekey'], 
                ':available'=>$avail, ':lockaid'=>$_POST['lockaid'],
                ':showlatepass'=>$showlatepass, ':allowunenroll'=>$unenroll, 
                ':copyrights'=>$copyrights, ':msgset'=>$msgset,	':toolset'=>$toolset, 
                ':theme'=>$theme, ':ltisecret'=>$_POST['ltisecret'], ':istemplate'=>$istemplate,
                ':deftime'=>$deftime, ':deflatepass'=>$deflatepass, ':ltidates'=>$setdatesbylti, 
                ':startdate'=>$startdate, ':enddate'=>$enddate,	':latepasshrs'=>$latepasshrs, 
                ':cleanupdate'=>$old_course_settings['cleanupdate'], ':level'=> $_POST['courselevel'], 
                ':ltisendzeros'=>$ltisendzeros, ':id'=>$_GET['id']);
			if ($myrights<75) {
				$query .= " AND ownerid=:ownerid";
				$qarr[':ownerid']=$userid;
			}
			if ($updateJsonData) {
				$qarr[':jsondata'] = json_encode($jsondata);
			}
			$stm = $DBH->prepare($query);
			$stm->execute($qarr);

			$changesToLog = array();
			foreach($old_course_settings as $k=>$v) {
				if (isset($qarr[':'.$k]) && $qarr[':'.$k] != $v) {
					$changesToLog[$k] = ['old'=>$v, 'new'=>$qarr[':'.$k]];
				}
			}
			if (!empty($changesToLog)) {
				TeacherAuditLog::addTracking(
						intval($_GET['id']),
						"Course Settings Change",
						null,
						$changesToLog
				);
			}

			//call hook, if defined
			if (function_exists('onModCourse')) {
				onModCourse($_GET['id'], $userid, $myrights, $groupid);
			}

			if ($stm->rowCount()>0) {
				if ($setdatesbylti==1) {
					$stm = $DBH->prepare("UPDATE imas_assessments SET date_by_lti=1 WHERE date_by_lti=0 AND courseid=:cid");
					$stm->execute(array(':cid'=>$_GET['id']));
				} else {
					//undo it - doesn't restore dates
					$stm = $DBH->prepare("UPDATE imas_assessments SET date_by_lti=0 WHERE date_by_lti>0 AND courseid=:cid");
					$stm->execute(array(':cid'=>$_GET['id']));
					//remove is_lti from exceptions with latepasses
					$query = "UPDATE imas_exceptions JOIN imas_assessments ";
					$query .= "ON imas_exceptions.assessmentid=imas_assessments.id ";
					$query .= "SET imas_exceptions.is_lti=0 ";
					$query .= "WHERE imas_exceptions.is_lti>0 AND imas_exceptions.islatepass>0 AND imas_assessments.courseid=:cid";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':cid'=>$_GET['id']));
					//delete any other is_lti exceptions
					$query = "DELETE imas_exceptions FROM imas_exceptions JOIN imas_assessments ";
					$query .= "ON imas_exceptions.assessmentid=imas_assessments.id ";
					$query .= "WHERE imas_exceptions.is_lti>0 AND imas_exceptions.islatepass=0 AND imas_assessments.courseid=:cid";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':cid'=>$_GET['id']));
				}
			}
		} else { //new course

			$destUIver = isset($_POST['newassessver']) ? 2 : 1;

			$blockcnt = 1;
			$itemorder = serialize(array());

			$chars = "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789";
			$ltisecret = '';
			for ($i=0;$i<8;$i++) {
				$ltisecret .= substr($chars,rand(0,56),1);
			}
			$courseownerid = $userid;
			if (($myrights >= 75 || ($myspecialrights&32)==32) && isset($_POST['for']) && $_POST['for']>0) {
				if ($myrights == 100 || ($myspecialrights&32)==32) {
					$courseownerid = Sanitize::onlyInt($_POST['for']);
				} else if ($myrights == 75) {
					$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=?");
					$stm->execute(array($_POST['for']));
					if ($groupid == $stm->fetchColumn(0)) {
						$courseownerid = Sanitize::onlyInt($_POST['for']);
					}
				}
			}

			if (isset($_POST['usetemplate']) && $_POST['usetemplate']>0) {
				//additional validation of permission to copy
				$query = "SELECT ic.name,ic.enrollkey,ic.copyrights,ic.ownerid,iu.groupid,ic.UIver FROM imas_courses AS ic JOIN imas_users AS iu ";
				$query .= "ON ic.ownerid=iu.id WHERE ic.id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':id'=>intval($_POST['usetemplate'])));
				$ctcinfo = $stm->fetch(PDO::FETCH_ASSOC);
				if (($ctcinfo['copyrights']==0 && $ctcinfo['ownerid'] != $courseownerid) ||
					($ctcinfo['copyrights']==1 && $ctcinfo['groupid']!=$groupid)) {
					if ($ctcinfo['enrollkey'] != '' && $ctcinfo['enrollkey'] != $_POST['ctcekey']) {
						//did not provide valid enrollment key
						$_POST['usetemplate'] = 0; //skip copying
					}
				}
				if ($_POST['usetemplate'] > 0 && $ctcinfo['UIver'] > 1) {
					$destUIver = $ctcinfo['UIver'];
				}
			}

			$DBH->beginTransaction();
			$query = "INSERT INTO imas_courses (name,ownerid,enrollkey,allowunenroll,copyrights,msgset,toolset,showlatepass,itemorder,available,startdate,enddate,istemplate,deftime,deflatepass,latepasshrs,theme,level,ltisecret,dates_by_lti,blockcnt,UIver,ltisendzeros) VALUES ";
			$query .= "(:name, :ownerid, :enrollkey, :allowunenroll, :copyrights, :msgset, :toolset, :showlatepass, :itemorder, :available, :startdate, :enddate, :istemplate, :deftime, :deflatepass, :latepasshrs, :theme, :level, :ltisecret, :ltidates, :blockcnt, :UIver, :ltisendzeros);";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':name'=>$_POST['coursename'], ':ownerid'=>$courseownerid, ':enrollkey'=>$_POST['ekey'],
				':allowunenroll'=>$unenroll, ':copyrights'=>$copyrights, ':msgset'=>$msgset, ':toolset'=>$toolset, ':showlatepass'=>$showlatepass,
				':itemorder'=>$itemorder, ':available'=>$avail, ':istemplate'=>$istemplate, ':deftime'=>$deftime, ':startdate'=>$startdate, ':enddate'=>$enddate,
				':deflatepass'=>$deflatepass, ':latepasshrs'=>$latepasshrs, ':theme'=>$theme, ':level'=>$_POST['courselevel'], ':ltisecret'=>$ltisecret, ':ltidates'=>$setdatesbylti, ':blockcnt'=>$blockcnt, ':UIver'=>$destUIver, ':ltisendzeros'=>$ltisendzeros));
			$cid = $DBH->lastInsertId();

			//call hook, if defined
			if (function_exists('onAddCourse')) {
				onAddCourse($cid, $userid, $myrights, $groupid);
			}

			//if ($myrights==40) {
				$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES (:userid, :courseid)");
				$stm->execute(array(':userid'=>$courseownerid, ':courseid'=>$cid));
			//}
			$useweights = intval(isset($CFG['GBS']['useweights'])?$CFG['GBS']['useweights']:0);
			$orderby = intval(isset($CFG['GBS']['orderby'])?$CFG['GBS']['orderby']:0);
			$defgbmode = intval(isset($CFG['GBS']['defgbmode'])?$CFG['GBS']['defgbmode']:21);
			$usersort = intval(isset($CFG['GBS']['usersort'])?$CFG['GBS']['usersort']:0);

			$stm = $DBH->prepare("INSERT INTO imas_gbscheme (courseid,useweights,orderby,defgbmode,usersort) VALUES (:courseid, :useweights, :orderby, :defgbmode, :usersort)");
			$stm->execute(array(':courseid'=>$cid, ':useweights'=>$useweights, ':orderby'=>$orderby, ':defgbmode'=>$defgbmode, ':usersort'=>$usersort));


			if (isset($_POST['usetemplate']) && $_POST['usetemplate']>0) {

				$stm = $DBH->prepare("SELECT useweights,orderby,defaultcat,defgbmode,stugbmode FROM imas_gbscheme WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$_POST['usetemplate']));
				$row = $stm->fetch(PDO::FETCH_NUM);
				$stm = $DBH->prepare("UPDATE imas_gbscheme SET useweights=:useweights,orderby=:orderby,defaultcat=:defaultcat,defgbmode=:defgbmode,stugbmode=:stugbmode WHERE courseid=:courseid");
				$stm->execute(array(':useweights'=>$row[0], ':orderby'=>$row[1], ':defaultcat'=>$row[2], ':defgbmode'=>$row[3], ':stugbmode'=>$row[4], ':courseid'=>$cid));

				$gbcats = array();
				$gb_cat_ins = null;
				$stm = $DBH->prepare("SELECT id,name,scale,scaletype,chop,dropn,weight,hidden,calctype FROM imas_gbcats WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$_POST['usetemplate']));
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					$frid = $row['id'];
					if ($gb_cat_ins===null) {
						$query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden,calctype) VALUES ";
						$query .= "(:courseid, :name, :scale, :scaletype, :chop, :dropn, :weight, :hidden, :calctype)";
						$gb_cat_ins = $DBH->prepare($query);
					}
					$gb_cat_ins->execute(array(':courseid'=>$cid, ':name'=>$row['name'], ':scale'=>$row['scale'], ':scaletype'=>$row['scaletype'],
						':chop'=>$row['chop'], ':dropn'=>$row['dropn'], ':weight'=>$row['weight'], ':hidden'=>$row['hidden'], ':calctype'=>$row['calctype']));
					$gbcats[$frid] = $DBH->lastInsertId();
				}
				$copystickyposts = !empty($_POST['copystickyposts']);
				$stm = $DBH->prepare("SELECT itemorder,ancestors,outcomes FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$_POST['usetemplate']));
				$r = $stm->fetch(PDO::FETCH_NUM);
				$items = unserialize($r[0]);
				$ancestors = $r[1];
				$outcomesarr = $r[2];
				if ($ancestors=='') {
					$ancestors = intval($_POST['usetemplate']);
				} else {
					$ancestors = intval($_POST['usetemplate']).','.$ancestors;
				}
				$outcomes = array();

				$replacebyarr = array();
				$query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
				$query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
				$query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
				$query .= "imas_assessments.courseid=:courseid AND imas_questionset.replaceby>0";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$_POST['usetemplate']));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$replacebyarr[$row[0]] = $row[1];
				}

				if ($outcomesarr!='' && !empty($_POST['copyoutcomes'])) {
					$stm = $DBH->prepare("SELECT id,name,ancestors FROM imas_outcomes WHERE courseid=:courseid");
					$stm->execute(array(':courseid'=>$_POST['usetemplate']));
					$out_ins_stm = null;
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						if ($row[2]=='') {
							$row[2] = $row[0];
						} else {
							$row[2] = $row[0].','.$row[2];
						}
						if ($out_ins_stm===null) {
							$query = "INSERT INTO imas_outcomes (courseid,name,ancestors) VALUES ";
							$query .= "(:courseid, :name, :ancestors)";
							$out_ins_stm = $DBH->prepare($query);
						}
						$out_ins_stm->execute(array(':courseid'=>$cid, ':name'=>$row[1], ':ancestors'=>$row[2]));
						$outcomes[$row[0]] = $DBH->lastInsertId();
					}
					function updateoutcomes(&$arr) {
						global $outcomes;
						foreach ($arr as $k=>$v) {
							if (is_array($v)) {
								updateoutcomes($arr[$k]['outcomes']);
							} else {
								$arr[$k] = $outcomes[$v];
							}
						}
					}
					$outcomesarr = unserialize($outcomesarr);
					updateoutcomes($outcomesarr);
					$newoutcomearr = serialize($outcomesarr);
				} else {
					$newoutcomearr = '';
				}
				$removewithdrawn = true;
				$usereplaceby = "all";
				$newitems = array();
				require("../includes/copyiteminc.php");
				$convertAssessVer = $destUIver;
				copyallsub($items,'0',$newitems,$gbcats);
				doaftercopy($_POST['usetemplate'], $newitems);
				$itemorder = serialize($newitems);
				$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt,ancestors=:ancestors,outcomes=:outcomes WHERE id=:id");
				$stm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':ancestors'=>$ancestors, ':outcomes'=>$newoutcomearr, ':id'=>$cid));
				//copy offline
				$offlinerubrics = array();
				if (!empty($_POST['copyoffline'])) {
					$stm = $DBH->prepare("SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric FROM imas_gbitems WHERE courseid=:courseid");
					$stm->execute(array(':courseid'=>$_POST['usetemplate']));
					$gbi_ins_stm = null;
					while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
						$rubric = $row['rubric'];
						unset($row['rubric']);
						if (isset($gbcats[$row['gbcategory']])) {
							$row['gbcategory'] = $gbcats[$row['gbcategory']];
						} else {
							$row['gbcategory'] = 0;
						}
						if ($gbi_ins_stm === null) {
							$query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit) VALUES ";
							$query .= "(:courseid,:name,:points,:showdate,:gbcategory,:cntingb,:tutoredit)";
							$gbi_ins_stm = $DBH->prepare($query);
						}
						$row[':courseid'] = $cid;
						$gbi_ins_stm->execute($row);
						if ($rubric>0) {
							$offlinerubrics[$DBH->lastInsertId()] = $rubric;
						}
					}
				}
				if (!empty($_POST['copyrubrics'])) {
					copyrubrics();
				}
				if (!empty($_POST['copyallcalitems'])) {
					copyallcalitems($_POST['usetemplate'], $cid);
				}
			}
			if ($setdatesbylti==1) {
				$stm = $DBH->prepare("UPDATE imas_assessments SET date_by_lti=1 WHERE date_by_lti=0 AND courseid=:cid");
				$stm->execute(array(':cid'=>$cid));
			}
			/*
			//add to top of course list (skip until we can do it consistently)
			$stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=?");
			$stm->execute(array($userid));
			$user_jsondata = json_decode($stm->fetchColumn(0), true);
			if ($user_jsondata !== null && isset($user_jsondata['courseListOrder']['teach'])) {
				array_unshift($user_jsondata['courseListOrder']['teach'], $cid);
				$stm = $DBH->prepare("UPDATE imas_users SET jsondata=? WHERE id=?");
				$stm->execute(array(json_encode($user_jsondata), $userid));
			}
			*/
			$DBH->commit();

			$stm = $DBH->prepare("SELECT id FROM imas_users WHERE (rights=11 OR rights=76 OR rights=77) AND groupid=?");
			$stm->execute(array($groupid));
			$hasGroupLTI = ($stm->fetchColumn() !== false);

			require("../header.php");
			echo '<div class="breadcrumb">'.$breadcrumbbase._(' Course Creation Confirmation').'</div>';
			echo '<h1>',_('Your course has been created'),'!</h1>';
			echo '<p>',_('For students to enroll in this course via direct login, you will need to provide them two things'),':<ol>';
			echo '<li>',_('The course ID'),': <b>'.$cid.'</b></li>';
			if (trim($_POST['ekey'])=='') {
				echo '<li>',sprintf(_('Tell them to leave the enrollment key blank, since you didn\'t specify one.  The enrollment key acts like a course password to prevent random strangers from enrolling in your course.  If you want to set an enrollment key, %s modify your course settings %s'),'<a href="forms.php?action=modify&id='.$cid.'">','</a>'),'</li>';
			} else {
				echo '<li>',_('The enrollment key'),': <b>'.$_POST['ekey'].'</b></li>';
			}
			echo '</ol></p>';

			if (empty($CFG['LTI']['noCourseLevel'])) {
				echo '<p>',_('If you plan to integrate this course with your school\'s Learning Management System (LMS), ');
				if ($hasGroupLTI) {
					echo _('it looks like your school may already have a school-wide LTI key and secret established - check with your LMS admin. ');
					echo _('If so, you will not need to set up a course-level configuration. ');
					echo _('If you do need to set up a course-level configuration for some reason, the key and secret can be found in your course settings'),'</p>';
				} else {
					echo _('here is the information you will need to set up a course-level configuration, since your school does not appear to have a school-wide LTI key and secret established.'),'</p>';
					echo '<ul class=nomark><li>Key: LTIkey_'.$cid.'_1</li>';
					echo '<li>Secret: '.Sanitize::encodeStringForDisplay($ltisecret).'</li></ul>';
					echo '<p>',_('If you forget these later, you can find them by viewing your course settings.'),'</p>';
				}
			}
			echo '<a href="../course/course.php?cid='.$cid.'">',_('Enter the Course'),'</a>';
			require("../footer.php");
			exit;
		}
		break;
	case "delete":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		if (isset($CFG['GEN']['doSafeCourseDelete']) && $CFG['GEN']['doSafeCourseDelete']==true) {
			$oktodel = false;
			if ($myrights < 75) {
				$stm = $DBH->prepare("SELECT id FROM imas_courses WHERE id=:id AND ownerid=:ownerid");
				$stm->execute(array(':id'=>$_GET['id'], ':ownerid'=>$userid));
				if ($stm->rowCount()>0) {
					$oktodel = true;
				}
			} else if ($myrights==100) {
				$oktodel = true;
			} else {
				$stm = $DBH->prepare("SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id=:id AND imas_courses.ownerid=imas_users.id AND imas_users.groupid=:groupid");
				$stm->execute(array(':id'=>$_GET['id'], ':groupid'=>$groupid));
				if ($stm->rowCount()>0) {
					$oktodel = true;
				}
			}
			if ($oktodel) {
				$stm = $DBH->prepare("UPDATE imas_courses SET available=4 WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['id']));
			}
			break;
		} else {
			require("../includes/delcourse.php");
			$stm = $DBH->prepare("SELECT ic.ownerid,iu.groupid FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ic.id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			$userdata = $stm->fetch(PDO::FETCH_ASSOC);
			$isOK = false;
			if ($userdata === false) {
				$isOK = false;
			} else if ($userdata['ownerid'] == $userid) {
				//own course
				$isOK = true;
			} else if ($myrights==75 && $userdata['groupid'] == $groupid) {
				//group course of group admin
				$isOK = true;
			} else if ($myrights==100) {
				//is full admin
				$isOK = true;
			} else {
				//no rights
				$isOK = false;
			}
			if ($isOK) {
				deleteCourse($_GET['id']);
			} else {
				break;
			}
		}
		break;
	case "removeself":
		if ($myrights < 20) {
			echo 'Error: Unauthorized';
			exit;
		}
		if ($_POST['uid']!== null && intval($_POST['uid'])>0) {
			$uid = Sanitize::onlyInt($_POST['uid']);
			if ($myrights < 75 && $uid != $userid) {
				echo 'Error: Unauthorized';
				exit;
			} else if ($myrights<100) {
				$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=?");
				$stm->execute(array($uid));
				if ($groupid != $stm->fetchColumn(0)) {
					echo 'Error: Unauthorized';
					exit;
				}
			}
		} else {
			$uid = $userid;
		}
		$stm = $DBH->prepare("SELECT ownerid FROM imas_courses WHERE id=?");
		$stm->execute(array($_POST['id']));
		$courseownerid = $stm->fetchColumn(0);
		if ($courseownerid==$uid) {
			echo 'Error: Can not remove yourself as a teacher from a course you own';
		} else {
			$stm = $DBH->prepare("DELETE FROM imas_teachers WHERE userid=? AND courseid=?");
			$stm->execute(array($uid, $_POST['id']));
			if ($stm->rowCount()>0) {
				echo 'OK';
			} else {
				echo 'Error: it does not appear you were a teacher on that course';
			}
		}
		exit;
	case "deloldusers":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$old = time() - 60*60*24*30*$_POST['months'];
		$who = $_POST['who'];
		require_once("../includes/filehandler.php");
		if ($who=="students") {
			$sstm = $DBH->prepare("SELECT id FROM imas_users WHERE  lastaccess<:old AND (rights=0 OR rights=10)");
			$sstm->execute(array(':old'=>$old));
			while ($row = $sstm->fetch(PDO::FETCH_NUM)) {
				$uid = $row[0];
				$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE userid=:userid");
				$stm->execute(array(':userid'=>$uid));
				$stm = $DBH->prepare("DELETE FROM imas_assessment_records WHERE userid=:userid");
				$stm->execute(array(':userid'=>$uid));
				$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE userid=:userid");
				$stm->execute(array(':userid'=>$uid));
				$stm = $DBH->prepare("DELETE FROM imas_grades WHERE userid=:userid");
				$stm->execute(array(':userid'=>$uid));
				$stm = $DBH->prepare("DELETE FROM imas_forum_views WHERE userid=:userid");
				$stm->execute(array(':userid'=>$uid));
				$stm = $DBH->prepare("DELETE FROM imas_students WHERE userid=:userid");
				$stm->execute(array(':userid'=>$uid));
				//these could break parent structure for forums!
				//$query = "DELETE FROM imas_forum_posts WHERE forumid='{$row[0]}' AND posttype=0";
				//mysql_query($query) or die("Query failed : " . mysql_error());
				deletealluserfiles($uid);
			}
			$stm = $DBH->prepare("DELETE FROM imas_users WHERE lastaccess<:old AND (rights=0 OR rights=10)");
			$stm->execute(array(':old'=>$old));
		} else if ($who=="all") {
			//TODO: Fix this so it deletes their stuff too
			$stm = $DBH->prepare("DELETE FROM imas_users WHERE lastaccess<:old AND rights<100");
			$stm->execute(array(':old'=>$old));
		}
		break;
	case "addgroup":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$stm = $DBH->prepare("SELECT id FROM imas_groups WHERE name=:name");
		$stm->execute(array(':name'=>$_POST['gpname']));
		if ($stm->rowCount()>0) {
			echo "<html><body>Group name already exists.  <a href=\"forms.php?action=listgroups\">Try again</a></body></html>\n";
			exit;
		}
		$newGroupName = Sanitize::stripHtmlTags(trim($_POST['gpname']));
		$defGrouptype = isset($CFG['GEN']['defGroupType'])?$CFG['GEN']['defGroupType']:0;
		$stm = $DBH->prepare("INSERT INTO imas_groups (name,grouptype) VALUES (:name,:grouptype)");
		$stm->execute(array(':name'=>$newGroupName, ':grouptype'=>$defGrouptype));
		break;
	case "modgroup":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$stm = $DBH->prepare("SELECT id FROM imas_groups WHERE name=:name AND id<>:id");
		$stm->execute(array(':name'=>$_POST['gpname'], ':id'=>$_GET['id']));
		if ($stm->rowCount()>0) {
			echo "<html><body>Group name already exists.  <a href=\"forms.php?action=modgroup&id=".Sanitize::encodeUrlParam($_GET['id'])."\">Try again</a></body></html>\n";
			exit;
		}
		$stm = $DBH->prepare("UPDATE imas_groups SET name=:name,parent=:parent WHERE id=:id");
		$stm->execute(array(':name'=>$_POST['gpname'], ':parent'=>$_POST['parentid'], ':id'=>$_GET['id']));

		//call hook, if defined
		if (function_exists('onModGroup')) {
			onModGroup($_GET['id'], $userid, $myrights, $groupid);
		}

		break;
	case "mergegroups":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		if (empty($_POST['group']) || empty($_GET['id'])) {
			echo 'Invalid group';
			exit;
		}
		$oldgroup = $_GET['id'];
		$newgroup = $_POST['group'];
		$stm = $DBH->prepare('UPDATE imas_libraries SET groupid=? WHERE groupid=?');
		$stm->execute(array($newgroup, $oldgroup));
		$stm = $DBH->prepare('UPDATE imas_users SET groupid=? WHERE groupid=?');
		$stm->execute(array($newgroup, $oldgroup));
		$stm = $DBH->prepare("DELETE FROM imas_groups WHERE id=:id");
        $stm->execute(array(':id'=>$oldgroup));
        // move over ipeds, skipping over duplicates
        $stm = $DBH->prepare('UPDATE IGNORE imas_ipeds_group SET groupid=? WHERE groupid=?');
        $stm->execute(array($newgroup, $oldgroup));
        // if update failed, is a duplicate; delete them
        $stm = $DBH->prepare('DELETE FROM imas_ipeds_group WHERE groupid=?');
        $stm->execute(array($oldgroup));
  		break;
	case "delgroup":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$stm = $DBH->prepare("DELETE FROM imas_groups WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		$stm = $DBH->prepare("UPDATE imas_users SET groupid=0 WHERE groupid=:groupid");
		$stm->execute(array(':groupid'=>$_GET['id']));
		$stm = $DBH->prepare("UPDATE imas_libraries SET groupid=0 WHERE groupid=:groupid");
		$stm->execute(array(':groupid'=>$_GET['id']));
		break;
	case "modltidomaincred":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		if ($_GET['id']=='new') {
			$query = "INSERT INTO imas_users (email,FirstName,LastName,SID,password,rights,groupid) VALUES ";
			$query .= "(:email, :FirstName, :LastName, :SID, :password, :rights, :groupid)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':email'=>$_POST['ltidomain'], ':FirstName'=>Sanitize::stripHtmlTags($_POST['ltidomain']), ':LastName'=>'LTIcredential',
				':SID'=>$_POST['ltikey'], ':password'=>$_POST['ltisecret'], ':rights'=>$_POST['createinstr'], ':groupid'=>$_POST['groupid']));
		} else {
			$query = "UPDATE imas_users SET email=:email,FirstName=:FirstName,LastName='LTIcredential',";
			$query .= "SID=:SID,password=:password,rights=:rights,groupid=:groupid WHERE id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':email'=>$_POST['ltidomain'], ':FirstName'=>$_POST['ltidomain'], ':SID'=>$_POST['ltikey'],
				':password'=>$_POST['ltisecret'], ':rights'=>$_POST['createinstr'], ':groupid'=>$_POST['groupid'], ':id'=>$_GET['id']));
		}
		break;
	case "delltidomaincred":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$stm = $DBH->prepare("DELETE FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		break;
	case "modfedpeers":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		if ($_GET['id']=='new') {
			$query = "INSERT INTO imas_federation_peers (peername,peerdescription,secret,url,lastpull) VALUES ";
			$query .= "(:peername, :peerdescription, :secret, :url, 0)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':peername'=>$_POST['peername'], ':peerdescription'=>$_POST['peerdescription'],
				':secret'=>$_POST['secret'], ':url'=>$_POST['url']));
		} else {
			$query = "UPDATE imas_federation_peers SET peername=:peername,peerdescription=:peerdescription,secret=:secret,url=:url WHERE id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':peername'=>$_POST['peername'], ':peerdescription'=>$_POST['peerdescription'],
				':secret'=>$_POST['secret'], ':url'=>$_POST['url'], ':id'=>$_GET['id']));
		}
		header('Location: ' . $GLOBALS['basesiteurl'] . '/admin/forms.php?action=listfedpeers&from='.Sanitize::encodeUrlParam($from));
		exit;
		break;
	case "delfedpeers":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$stm = $DBH->prepare("DELETE FROM imas_federation_peers WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		header('Location: ' . $GLOBALS['basesiteurl'] . '/admin/forms.php?action=listfedpeers&from='.Sanitize::encodeUrlParam($from));
		exit;
		break;
	case "removediag";
		if ($myrights <60) { echo "You don't have the authority for this action"; break;}
		$stm = $DBH->prepare("SELECT imas_users.id,imas_users.groupid FROM imas_users JOIN imas_diags ON imas_users.id=imas_diags.ownerid AND imas_diags.id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if (($myrights<75 && $row[0]==$userid) || ($myrights==75 && $row[1]==$groupid) || $myrights==100) {
			$stm = $DBH->prepare("DELETE FROM imas_diags WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			$stm = $DBH->prepare("DELETE FROM imas_diag_onetime WHERE diag=:diag");
			$stm->execute(array(':diag'=>$_GET['id']));
		}
		break;
	case "entermfa":
		$stm = $DBH->prepare("SELECT mfa FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$userid));
		$mfadata = $stm->fetchColumn(0);
		$error = '';
		if ($mfadata != '') {
			$mfadata = json_decode($mfadata, true);
			require('../includes/GoogleAuthenticator.php');
			$MFA = new GoogleAuthenticator();
			//check that code is valid and not a replay
			if ($MFA->verifyCode($mfadata['secret'], $_POST['mfatoken']) &&
			   ($_POST['mfatoken'] != $mfadata['last'] || time() - $mfadata['laston'] > 600)) {
				$_SESSION['mfaadminverified'] = true;
				$mfadata['last'] = $_POST['mfatoken'];
				$mfadata['laston'] = time();
				if (isset($_POST['mfatrust'])) {
					$trusttoken = $MFA->createSecret();
					setcookie('gat', $trusttoken, time()+60*60*24*365*10, $imasroot.'/', '', true, true);
					if (!isset($mfadata['trusted'])) {
						$mfadata['trusted'] = array();
					}
					$mfadata['trusted'][] = $trusttoken;
				}
				$stm = $DBH->prepare("UPDATE imas_users SET mfa = :mfa WHERE id = :uid");
				$stm->execute(array(':uid'=>$userid, ':mfa'=>json_encode($mfadata)));
				if (isset($_POST['mfatrust'])) {
					require("../header.php");
					echo '<p>This device is now trusted; you will not be asked for your 2-factor authentication on this device again.</p>';
					echo '<p>If you ever need to un-trust this device, you can clear all cookies, or disable 2-factor authentication in your account settings.</p>';
					echo '<p><a href="../index.php">Continue</a></p>';
					require("../footer.php");
					exit;
				}

			} else {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/forms.php?action=entermfa&error=true");
				exit;
			}
		}
		break;
}

session_write_close();
if ($myrights<75 || $from=='home') {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php");
} else if (empty($from)) {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/admin2.php");
} else if (isset($_GET['cid'])) {
	$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_GET['cid']).$btf);
} else if ($from=='admin2') {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/admin2.php");
} else if ($from=='userreports') {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/userreports.php");
} else if (substr($from,0,2)=='ud' || substr($from,0,2)=='gd') {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/$backloc");
} else {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/admin2.php");
}
exit;
?>
