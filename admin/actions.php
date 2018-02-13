<?php
//IMathAS:  Admin actions
//(c) 2006 David Lippman
require("../init.php");
require_once("../includes/password.php");

$from = 'admin';
if (isset($_GET['from'])) {
	if ($_GET['from']=='home') {
		$from = 'home';
	} else if ($_GET['from']=='admin2') {
		$from = 'admin2';
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
} else if (substr($_GET['from'],0,2)=='ud') {
	$breadcrumbbase .= '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$backloc.'">'._('User Details').'</a> &gt; ';
} else if (substr($_GET['from'],0,2)=='gd') {
	echo '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$backloc.'">'._('Group Details').'</a> &gt; ';
}

switch($_POST['action']) {
	case "emulateuser":
		if ($myrights < 100 ) { break;}
		$be = $_REQUEST['uid'];
		$stm = $DBH->prepare("UPDATE imas_sessions SET userid=:userid WHERE sessionid=:sessionid");
		$stm->execute(array(':userid'=>$be, ':sessionid'=>$sessionid));
		break;
	case "chgrights":
		if ($myrights < 75 && ($myspecialrights&16)!=16 && ($myspecialrights&32)!=32) { echo "You don't have the authority for this action"; break;}
		if ($_POST['newrights']>$myrights) {
			$_POST['newrights'] = $myrights;
		}
		$stm = $DBH->prepare("SELECT rights,groupid FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		list($oldrights,$oldgroupid) = $stm->fetch(PDO::FETCH_NUM);
		if ($row === false) {
			echo "invalid id";
			exit;
		}

		$stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
		$stm->execute(array(':SID'=>$_POST['adminname']));
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

		if ($myrights == 100 || ($myspecialrights&32)==32) { //update library groupids
			if ($_POST['group']==-1) {
				if (trim($_POST['newgroupname'])!='') {
					//check for existing with same name
					$stm = $DBH->prepare("SELECT id FROM imas_groups WHERE name REGEXP ?");
					$stm->execute(array('^[[:space:]]*'.str_replace('.','[.]',preg_replace('/\s+/', '[[:space:]]+', trim($_POST['newgroupname']))).'[[:space:]]*$'));
					$newgroup = $stm->fetchColumn(0);
					if ($newgroup === false) {
						$stm = $DBH->prepare("INSERT INTO imas_groups (name) VALUES (:name)");
						$stm->execute(array(':name'=>$_POST['newgroupname']));
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
			if (isset($_POST['doresetpw'])) {
				$query .= ',password=:password';
			}
			$query .= " WHERE id=:id AND groupid=:groupid AND rights<100";
			$stm = $DBH->prepare($query);
			$stm->execute($arr);
		}

		//if student being promoted, enroll in teacher enroll courses
		if ($oldrights<=10 && $_POST['newrights']>=20 && isset($CFG['GEN']['enrollonnewinstructor'])) {
			$valbits = array();
			$valvals = array();
			foreach ($CFG['GEN']['enrollonnewinstructor'] as $ncid) {
				$valbits[] = "(?,?)";
				array_push($valvals, $_GET['id'], $ncid);
			}
			$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid) VALUES ".implode(',',$valbits));
			$stm->execute($valvals);
			
			
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
	case "deladmin":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		if ($myrights < 100) {
			$stm = $DBH->prepare("DELETE FROM imas_users WHERE id=:id AND groupid=:groupid AND rights<100");
			$stm->execute(array(':id'=>$_GET['id'], ':groupid'=>$groupid));
		} else {
			$stm = $DBH->prepare("DELETE FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
		}
		if ($stm->rowCount()==0) { break;}
		$stm = $DBH->prepare("DELETE FROM imas_user_prefs WHERE userid=:userid");
		$stm->execute(array(':userid'=>$_GET['id']));
		$stm = $DBH->prepare("DELETE FROM imas_students WHERE userid=:userid");
		$stm->execute(array(':userid'=>$_GET['id']));
		$stm = $DBH->prepare("DELETE FROM imas_teachers WHERE userid=:userid");
		$stm->execute(array(':userid'=>$_GET['id']));
		$stm = $DBH->prepare("DELETE FROM imas_tutors WHERE userid=:userid");
		$stm->execute(array(':userid'=>$_GET['id']));
		$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE userid=:userid");
		$stm->execute(array(':userid'=>$_GET['id']));
		$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE userid=:userid");
		$stm->execute(array(':userid'=>$_GET['id']));

		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE msgto=:msgto AND isread>1");
		$stm->execute(array(':msgto'=>$_GET['id']));
		$stm = $DBH->prepare("UPDATE imas_msgs SET isread=isread+2 WHERE msgto=:msgto AND isread<2");
		$stm->execute(array(':msgto'=>$_GET['id']));
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE msgfrom=:msgfrom AND isread>1");
		$stm->execute(array(':msgfrom'=>$_GET['id']));
		$stm = $DBH->prepare("UPDATE imas_msgs SET isread=isread+4 WHERE msgfrom=:msgfrom AND isread<2");
		$stm->execute(array(':msgfrom'=>$_GET['id']));
		//todo: delete user picture files
		//todo: delete user file uploads
		require_once("../includes/filehandler.php");
		deletealluserfiles($_GET['id']);
		//todo: delete courses if any
		break;
	case "newadmin":
		if ($myrights < 75 && ($myspecialrights&16)!=16 && ($myspecialrights&32)!=32) { echo "You don't have the authority for this action"; break;}
		if ($_POST['newrights']>$myrights) {
			$_POST['newrights'] = $myrights;
		}
		$stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
		$stm->execute(array(':SID'=>$_POST['SID']));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if ($row != null) {
			echo "<html><body>Username is already used.\n";
			echo "<a href=\"forms.php?action=newadmin\">Try Again</a> or ";
			echo "<a href=\"forms.php?action=chgrights&id={$row[0]}\">Change rights for existing user</a></body></html>\n";
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
						$stm = $DBH->prepare("INSERT INTO imas_groups (name) VALUES (:name)");
						$stm->execute(array(':name'=>$_POST['newgroupname']));
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
		$stm = $DBH->prepare("INSERT INTO imas_users (SID,password,FirstName,LastName,rights,email,groupid,homelayout,specialrights) VALUES (:SID, :password, :FirstName, :LastName, :rights, :email, :groupid, :homelayout, :specialrights);");
		$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw, ':FirstName'=>$_POST['firstname'], ':LastName'=>$_POST['lastname'], ':rights'=>$_POST['newrights'], ':email'=>$_POST['email'], ':groupid'=>$newgroup, ':homelayout'=>$homelayout, ':specialrights'=>$specialrights));
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
		break;
	case "logout":
		$sessionid = session_id();
		$stm = $DBH->prepare("DELETE FROM imas_sessions WHERE sessionid=:sessionid");
		$stm->execute(array(':sessionid'=>$sessionid));
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}
		session_destroy();
		break;
	case "modify":
	case "addcourse":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}

		if (isset($CFG['CPS']['templateoncreate']) && isset($_POST['usetemplate']) && $_POST['usetemplate']>0) {
			$coursetocheck = intval($_POST['usetemplate']);
			$stm = $DBH->prepare("SELECT termsurl FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$coursetocheck));
			$terms = $stm->fetch(PDO::FETCH_NUM);
			if ($terms[0]!='') {
				if (!isset($_POST['termsagree'])) {
					require("../header.php");
					echo '<p>You must agree to the terms of use to copy this course.</p>';
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
			$stm = $DBH->prepare("SELECT istemplate,jsondata FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			list($old_istemplate, $old_jsondata) = $stm->fetch(PDO::FETCH_NUM);
		} else {
			$old_istemplate = 0;
		}
		if (isset($CFG['CPS']['theme']) && $CFG['CPS']['theme'][1]==0) {
			$theme = $CFG['CPS']['theme'][0];
		} else {
			$theme = $_POST['theme'];
		}

		//legacy values - remove eventually
		$picicons = 1;
		$hideicons = 0;

		if (isset($CFG['CPS']['unenroll']) && $CFG['CPS']['unenroll'][1]==0) {
			$unenroll = $CFG['CPS']['unenroll'][0];
		} else {
			$unenroll = $_POST['allowunenroll'] + $_POST['allowenroll'];
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
			preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$_POST['deftime'],$tmatches);
			if (count($tmatches)==0) {
				preg_match('/(\d+)\s*([a-zA-Z]+)/',$_POST['deftime'],$tmatches);
				$tmatches[3] = $tmatches[2];
				$tmatches[2] = 0;
			}
			$tmatches[1] = $tmatches[1]%12;
			if($tmatches[3]=="pm") {$tmatches[1]+=12; }
			$deftime = $tmatches[1]*60 + $tmatches[2];

			preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$_POST['defstime'],$tmatches);
			if (count($tmatches)==0) {
				preg_match('/(\d+)\s*([a-zA-Z]+)/',$_POST['defstime'],$tmatches);
				$tmatches[3] = $tmatches[2];
				$tmatches[2] = 0;
			}
			$tmatches[1] = $tmatches[1]%12;
			if($tmatches[3]=="pm") {$tmatches[1]+=12; }
			$deftime += 10000*($tmatches[1]*60 + $tmatches[2]);
		}

		if (isset($CFG['CPS']['deflatepass']) && $CFG['CPS']['deflatepass'][1]==0) {
			$deflatepass = $CFG['CPS']['deflatepass'][0];
		} else {
			$deflatepass = intval($_POST['deflatepass']);
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
		} else if (($old_istemplate&2)==2) {
			$istemplate |= 2;
		}
		if (($myspecialrights&2)==2 || $myrights==100) {
			if (isset($_POST['istemplate'])) {
				$istemplate |= 1;
			}
		} else if (($old_istemplate&1)==1) {
			$istemplate |= 1;
		}
		if ($myrights==100) {
			if (isset($_POST['isselfenroll'])) {
				$istemplate |= 4;
			}
			if (isset($_POST['isguest'])) {
				$istemplate |= 8;
			}
		}
		if (!isset($CFG['coursebrowserRightsToPromote'])) {
			$CFG['coursebrowserRightsToPromote'] = 40;
		}
		$updateJsonData = false;
		if ($CFG['coursebrowserRightsToPromote']>$myrights && ($old_istemplate&16)==16) {
			$istemplate |= 16;
		} else if (isset($_POST['promote']) && isset($_GET['id']) && $CFG['coursebrowserRightsToPromote']<=$myrights) {
			$browserprops = json_decode(file_get_contents(__DIR__.'/../javascript/'.$CFG['coursebrowser'], false, null, 25), true);

			$isok = ($copyrights>1);

			$jsondata = json_decode($old_jsondata, true);
			if ($jsondata===null) {
				$jsondata = array();
			}

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

		$_POST['ltisecret'] = trim($_POST['ltisecret']);

		if ($_POST['action']=='modify') {
			$query = "UPDATE imas_courses SET name=:name,enrollkey=:enrollkey,hideicons=:hideicons,available=:available,lockaid=:lockaid,picicons=:picicons,showlatepass=:showlatepass,";
			if ($updateJsonData) {
				$query .= "jsondata=:jsondata,";
			}
			$query .= "allowunenroll=:allowunenroll,copyrights=:copyrights,msgset=:msgset,toolset=:toolset,theme=:theme,ltisecret=:ltisecret,istemplate=:istemplate,deftime=:deftime,deflatepass=:deflatepass WHERE id=:id";
			$qarr = array(':name'=>$_POST['coursename'], ':enrollkey'=>$_POST['ekey'], ':hideicons'=>$hideicons, ':available'=>$avail, ':lockaid'=>$_POST['lockaid'],
				':picicons'=>$picicons, ':showlatepass'=>$showlatepass, ':allowunenroll'=>$unenroll, ':copyrights'=>$copyrights, ':msgset'=>$msgset,
				':toolset'=>$toolset, ':theme'=>$theme, ':ltisecret'=>$_POST['ltisecret'], ':istemplate'=>$istemplate,
				':deftime'=>$deftime, ':deflatepass'=>$deflatepass, ':id'=>$_GET['id']);
			if ($myrights<75) {
				$query .= " AND ownerid=:ownerid";
				$qarr[':ownerid']=$userid;
			}
			if ($updateJsonData) {
				$qarr[':jsondata'] = json_encode($jsondata);
			}
			$stm = $DBH->prepare($query);
			$stm->execute($qarr);
		} else {
			$blockcnt = 1;
			$itemorder = serialize(array());
			$DBH->beginTransaction();
			$query = "INSERT INTO imas_courses (name,ownerid,enrollkey,hideicons,picicons,allowunenroll,copyrights,msgset,toolset,showlatepass,itemorder,available,istemplate,deftime,deflatepass,theme,ltisecret,blockcnt) VALUES ";
			$query .= "(:name, :ownerid, :enrollkey, :hideicons, :picicons, :allowunenroll, :copyrights, :msgset, :toolset, :showlatepass, :itemorder, :available, :istemplate, :deftime, :deflatepass, :theme, :ltisecret, :blockcnt);";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':name'=>$_POST['coursename'], ':ownerid'=>$userid, ':enrollkey'=>$_POST['ekey'], ':hideicons'=>$hideicons, ':picicons'=>$picicons,
				':allowunenroll'=>$unenroll, ':copyrights'=>$copyrights, ':msgset'=>$msgset, ':toolset'=>$toolset, ':showlatepass'=>$showlatepass,
				':itemorder'=>$itemorder, ':available'=>$avail, ':istemplate'=>$istemplate, ':deftime'=>$deftime,
				':deflatepass'=>$deflatepass, ':theme'=>$theme, ':ltisecret'=>$_POST['ltisecret'], ':blockcnt'=>$blockcnt));
			$cid = $DBH->lastInsertId();
			//if ($myrights==40) {
				$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES (:userid, :courseid)");
				$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
			//}
			$useweights = intval(isset($CFG['GBS']['useweights'])?$CFG['GBS']['useweights']:0);
			$orderby = intval(isset($CFG['GBS']['orderby'])?$CFG['GBS']['orderby']:0);
			$defgbmode = intval(isset($CFG['GBS']['defgbmode'])?$CFG['GBS']['defgbmode']:21);
			$usersort = intval(isset($CFG['GBS']['usersort'])?$CFG['GBS']['usersort']:0);

			$stm = $DBH->prepare("INSERT INTO imas_gbscheme (courseid,useweights,orderby,defgbmode,usersort) VALUES (:courseid, :useweights, :orderby, :defgbmode, :usersort)");
			$stm->execute(array(':courseid'=>$cid, ':useweights'=>$useweights, ':orderby'=>$orderby, ':defgbmode'=>$defgbmode, ':usersort'=>$usersort));


			if (isset($CFG['CPS']['templateoncreate']) && isset($_POST['usetemplate']) && $_POST['usetemplate']>0) {

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
				$copystickyposts = true;
				$stm = $DBH->prepare("SELECT itemorder,ancestors,outcomes,latepasshrs FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$_POST['usetemplate']));
				$r = $stm->fetch(PDO::FETCH_NUM);
				$items = unserialize($r[0]);
				$ancestors = $r[1];
				$outcomesarr = $r[2];
				$latepasshrs = $r[3];
				if ($ancestors=='') {
					$ancestors = intval($_POST['usetemplate']);
				} else {
					$ancestors = intval($_POST['usetemplate']).','.$ancestors;
				}
				$outcomes = array();

				$query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
				$query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
				$query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
				$query .= "imas_assessments.courseid=:courseid AND imas_questionset.replaceby>0";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$_POST['usetemplate']));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$replacebyarr[$row[0]] = $row[1];
				}

				if ($outcomesarr!='') {
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
				copyallsub($items,'0',$newitems,$gbcats);
				doaftercopy($_POST['usetemplate']);
				$itemorder = serialize($newitems);
				$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt,ancestors=:ancestors,outcomes=:outcomes,latepasshrs=:latepasshrs WHERE id=:id");
				$stm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':ancestors'=>$ancestors, ':outcomes'=>$newoutcomearr, ':latepasshrs'=>$latepasshrs, ':id'=>$cid));
				//copy offline
				$offlinerubrics = array();
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
				copyrubrics();

			}
			$DBH->commit();

			require("../header.php");
			echo '<div class="breadcrumb">'.$breadcrumbbase.' Course Creation Confirmation</div>';
			echo '<h2>Your course has been created!</h2>';
			echo '<p>For students to enroll in this course, you will need to provide them two things:<ol>';
			echo '<li>The course ID: <b>'.$cid.'</b></li>';
			if (trim($_POST['ekey'])=='') {
				echo '<li>Tell them to leave the enrollment key blank, since you didn\'t specify one.  The enrollment key acts like a course ';
				echo 'password to prevent random strangers from enrolling in your course.  If you want to set an enrollment key, ';
				echo '<a href="forms.php?action=modify&id='.$cid.'">modify your course settings</a></li>';
			} else {
				echo '<li>The enrollment key: <b>'.$_POST['ekey'].'</b></li>';
			}
			echo '</ol></p>';
			echo '<p>If you forget these later, you can find them by viewing your course settings.</p>';
			echo '<a href="../course/course.php?cid='.$cid.'">Enter the Course</a>';
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

			if ($myrights == 75) {
				$stm = $DBH->prepare("SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id=:id AND imas_courses.ownerid=imas_users.id AND imas_users.groupid=:groupid");
				$stm->execute(array(':id'=>$_GET['id'], ':groupid'=>$groupid));
				if ($stm->rowCount()>0) {
					$stm = $DBH->prepare("DELETE FROM imas_courses WHERE id=:id");
					$stm->execute(array(':id'=>$_GET['id']));
				} else {
					break;
				}
			} else if ($myrights == 100) {
				$stm = $DBH->prepare("DELETE FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['id']));
			} else {
				$stm = $DBH->prepare("DELETE FROM imas_courses WHERE id=:id AND ownerid=:ownerid");
				$stm->execute(array(':id'=>$_GET['id'], ':ownerid'=>$userid));
			}
			if ($stm->rowCount()==0) { break;}

			$DBH->beginTransaction();
			$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			require_once("../includes/filehandler.php");
			while ($line = $stm->fetch(PDO::FETCH_NUM)) {
				deleteallaidfiles($line[0]);
				$stm2 = $DBH->prepare("DELETE FROM imas_questions WHERE assessmentid=:assessmentid");
				$stm2->execute(array(':assessmentid'=>$line[0]));
				$stm2 = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
				$stm2->execute(array(':assessmentid'=>$line[0]));
				$stm2 = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:assessmentid AND itemtype='A'");
				$stm2->execute(array(':assessmentid'=>$line[0]));
				$stm2 = $DBH->prepare("DELETE FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
				$stm2->execute(array(':assessmentid'=>$line[0]));
			}

			$stm = $DBH->prepare("DELETE FROM imas_assessments WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));


			$stm = $DBH->prepare("SELECT id FROM imas_drillassess WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			while ($line = $stm->fetch(PDO::FETCH_NUM)) {
				$stm2 = $DBH->prepare("DELETE FROM imas_drillassess_sessions WHERE drillassessid=:drillassessid");
				$stm2->execute(array(':drillassessid'=>$line[0]));
			}
			$stm = $DBH->prepare("DELETE FROM imas_drillassess WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));

			$stm = $DBH->prepare("SELECT id FROM imas_forums WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$stm2 = $DBH->prepare("SELECT id FROM imas_forum_posts WHERE forumid=:forumid AND files<>''");
				$stm2->execute(array(':forumid'=>$row[0]));
				while ($row = $stm2->fetch(PDO::FETCH_NUM)) {
					deleteallpostfiles($row[0]);
				}
				/*$q2 = "SELECT id FROM imas_forum_threads WHERE forumid='{$row[0]}'";
				$r2 = mysql_query($q2) or die("Query failed : " . mysql_error());
				while ($row2 = mysql_fetch_row($r2)) {
					$query = "DELETE FROM imas_forum_views WHERE threadid='{$row2[0]}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
				*/
				$query = "DELETE imas_forum_views FROM imas_forum_views JOIN ";
				$query .= "imas_forum_threads ON imas_forum_views.threadid=imas_forum_threads.id ";
				$query .= "WHERE imas_forum_threads.forumid=:forumid";
				$stm2 = $DBH->prepare($query);
				$stm2->execute(array(':forumid'=>$row[0]));

				$stm2 = $DBH->prepare("DELETE FROM imas_forum_posts WHERE forumid=:forumid");
				$stm2->execute(array(':forumid'=>$row[0]));

				$stm2 = $DBH->prepare("DELETE FROM imas_forum_threads WHERE forumid=:forumid");
				$stm2->execute(array(':forumid'=>$row[0]));

				$stm2 = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:assessmentid AND (itemtype='F' OR itemtype='P' OR itemtype='R')");
				$stm2->execute(array(':assessmentid'=>$row[0]));

			}
			$stm = $DBH->prepare("DELETE FROM imas_forums WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));

			$stm2 = $DBH->prepare("SELECT id FROM imas_wikis WHERE courseid=:courseid");
			$stm2->execute(array(':courseid'=>$_GET['id']));
			while ($wid = $stm2->fetch(PDO::FETCH_NUM)) {
				$stm3 = $DBH->prepare("DELETE FROM imas_wiki_revisions WHERE wikiid=:wikiid");
				$stm3->execute(array(':wikiid'=>$wid));
				$stm3 = $DBH->prepare("DELETE FROM imas_wiki_views WHERE wikiid=:wikiid");
				$stm3->execute(array(':wikiid'=>$wid));
			}
			$stm = $DBH->prepare("DELETE FROM imas_wikis WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));

			//delete inline text files
			$stm3 = $DBH->prepare("SELECT id FROM imas_inlinetext WHERE courseid=:courseid");
			$stm3->execute(array(':courseid'=>$_GET['id']));
			while ($ilid = $stm3->fetch(PDO::FETCH_NUM)) {
				$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
				$stm = $DBH->prepare("SELECT filename FROM imas_instr_files WHERE itemid=:itemid");
				$stm->execute(array(':itemid'=>$ilid[0]));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					if (substr($row[0],0,4)!='http') {
						$stm2 = $DBH->prepare("SELECT id FROM imas_instr_files WHERE filename=:filename");
						$stm2->execute(array(':filename'=>$row[0]));
						if ($stm2->rowCount()==1) {
							//unlink($uploaddir . $row[0]);
							deletecoursefile($row[0]);
						}
					}
				}
				//DB $query = "DELETE FROM imas_instr_files WHERE itemid='{$ilid[0]}'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("DELETE FROM imas_instr_files WHERE itemid=:itemid");
				$stm->execute(array(':itemid'=>$ilid[0]));
			}
			$stm = $DBH->prepare("DELETE FROM imas_inlinetext WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));

			//delete linked text files
			$stm = $DBH->prepare("SELECT text,points,id FROM imas_linkedtext WHERE courseid=:courseid AND text LIKE 'file:%'");
			$stm->execute(array(':courseid'=>$_GET['id']));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$stm2 = $DBH->prepare("SELECT id FROM imas_linkedtext WHERE text=:text");
				$stm2->execute(array(':text'=>$row[0]));
				if ($stm2->rowCount()==1) {
					//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
					$filename = substr($row[0],5);
					//unlink($uploaddir . $filename);
					deletecoursefile($filename);
				}
				if ($row[1]>0) {
					$stm2 = $DBH->prepare("DELETE FROM imas_grades WHERE gradetypeid=:gradetypeid AND gradetype='exttool'");
					$stm2->execute(array(':gradetypeid'=>$row[2]));
				}
			}


			$stm = $DBH->prepare("DELETE FROM imas_linkedtext WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			$stm = $DBH->prepare("DELETE FROM imas_items WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			$stm = $DBH->prepare("DELETE FROM imas_teachers WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			$stm = $DBH->prepare("DELETE FROM imas_students WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			$stm = $DBH->prepare("DELETE FROM imas_tutors WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));

			$stm = $DBH->prepare("SELECT id FROM imas_gbitems WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$stm2 = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid");
				$stm2->execute(array(':gradetypeid'=>$row[0]));
			}
			$stm = $DBH->prepare("DELETE FROM imas_gbitems WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			$stm = $DBH->prepare("DELETE FROM imas_gbscheme WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			$stm = $DBH->prepare("DELETE FROM imas_gbcats WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));

			$stm = $DBH->prepare("DELETE FROM imas_calitems WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));

			$stm = $DBH->prepare("SELECT id FROM imas_stugroupset WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$stm2 = $DBH->prepare("SELECT id FROM imas_stugroups WHERE groupsetid=:groupsetid");
				$stm2->execute(array(':groupsetid'=>$row[0]));
				while ($row2 = $stm2->fetch(PDO::FETCH_NUM)) {
					$stm3 = $DBH->prepare("DELETE FROM imas_stugroupmembers WHERE stugroupid=:stugroupid");
					$stm3->execute(array(':stugroupid'=>$row2[0]));
				}
				$stm4 = $DBH->prepare("DELETE FROM imas_stugroups WHERE groupsetid=:groupsetid");
				$stm4->execute(array(':groupsetid'=>$row[0]));
			}
			$stm = $DBH->prepare("DELETE FROM imas_stugroupset WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));

			$stm = $DBH->prepare("DELETE FROM imas_external_tools WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));
			$stm = $DBH->prepare("DELETE FROM imas_content_track WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['id']));

			$DBH->commit();
		}
		break;
	case "remteacher":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		$tids = array();
		if (isset($_GET['tid'])) {
			$tids = array($_GET['tid']);
		} else if (isset($_POST['tid'])) {
			$tids = $_POST['tid'];
			if (count($tids)==$_GET['tot']) {
				array_shift($tids);
			}
		}
		foreach ($tids as $tid) {
			if ($myrights < 100) {
				$stm = $DBH->prepare("SELECT imas_teachers.id FROM imas_teachers,imas_users WHERE imas_teachers.id=:id AND imas_teachers.userid=imas_users.id AND imas_users.groupid=:groupid");
				$stm->execute(array(':id'=>$tid, ':groupid'=>$groupid));
				if ($stm->rowCount()>0) {
					$stm = $DBH->prepare("DELETE FROM imas_teachers WHERE id=:id");
					$stm->execute(array(':id'=>$tid));
				} else {
					//break;
				}

				//$query = "DELETE imas_teachers FROM imas_users,imas_teachers WHERE imas_teachers.id='{$_GET['tid']}' ";
				//$query .= "AND imas_teachers.userid=imas_users.id AND imas_users.groupid='$groupid'";
			} else {
				$stm = $DBH->prepare("DELETE FROM imas_teachers WHERE id=:id");
				$stm->execute(array(':id'=>$tid));
			}
		}

		header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/forms.php?action=chgteachers&id=". Sanitize::courseId($_GET['cid']).'&from='.Sanitize::encodeUrlParam($from));
		exit;
	case "addteacher":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		if ($myrights < 100) {
			$stm = $DBH->prepare("SELECT imas_users.groupid FROM imas_users,imas_courses WHERE imas_courses.ownerid=imas_users.id AND imas_courses.id=:id");
			$stm->execute(array(':id'=>$_GET['cid']));
			if ($stm->fetchColumn(0) != $groupid) {
				break;
			}
		}
		$tids = array();
		if (isset($_GET['tid'])) {
			$tids = array($_GET['tid']);
		} else if (isset($_POST['atid'])) {
			$tids = $_POST['atid'];
		}
		$ins = array();
		$insval = array();
		foreach ($tids as $tid) {
			$ins[] = "(?,?)";
			array_push($insval, $tid, $_GET['cid']);
		}
		if (count($ins)>0) {
			$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES ".implode(',',$ins));
			$stm->execute($insval);
		}
		if (!isset($_POST['addandclose'])) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/forms.php?action=chgteachers&id=" .Sanitize::courseId($_GET['cid']).'&from='.Sanitize::encodeUrlParam($from));
			exit;
		}
		break;
	case "importmacros":
		if ($myrights < 100 || !$allowmacroinstall) { echo "You don't have the authority for this action"; break;}
		$uploaddir = rtrim(dirname("../config.php"), '/\\') .'/assessment/libs/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			if (strpos($uploadfile,'.php')!==FALSE) {
				$handle = fopen($uploadfile, "r");
				$atstart = true;
				if ($handle) {
					while (!feof($handle)) {
						$buffer = fgets($handle, 4096);
						if (strpos($buffer,"//")===0) {
							$trimmed = trim(substr($buffer,2));
							if ($trimmed{0}!='<' && substr($trimmed,-1)!='>') {
								$comments .= preg_replace('/^( +)/e', 'str_repeat("&nbsp;", strlen("$1"))', substr($buffer,2)) .  "<BR>";
							} else {
								$comments .= trim(substr($buffer,2));
							}
						} else if (strpos($buffer,"function")===0) {
							$func = substr($buffer,9,strpos($buffer,"(")-9);
							if ($comments!='') {
								$outlines .= "<h3><a name=\"$func\">$func</a></h3>\n";
								$funcs[] = $func;
								$outlines .= $comments;
								$comments = '';
							}
						} else if ($atstart && trim($buffer)=='') {
							$startcomments = $comments;
							$atstart = false;
							$comments = '';
						} else {
							$comments = '';
						}
					}
				}
				fclose($handle);
				$lib = basename($uploadfile,".php");
				$outfile = fopen($uploaddir . $lib.".html", "w");
				fwrite($outfile,"<html><body>\n<h1>Macro Library $lib</h1>\n");
				fwrite($outfile,$startcomments);
				fwrite($outfile,"<ul>\n");
				foreach($funcs as $func) {
					fwrite($outfile,"<li><a href=\"#$func\">$func</a></li>\n");
				}
				fwrite($outfile,"</ul>\n");
				fwrite($outfile, $outlines);
				fclose($outfile);
			}
			break;
		} else {
			require("../header.php");
			echo "<p>Error uploading file!</p>\n";
			require("../footer.php");
			exit;
		}
	case "importqimages":
		if ($myrights < 100 || !$allowmacroinstall) { echo "You don't have the authority for this action"; break;}
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			if (strpos($uploadfile,'.tar.gz')!==FALSE) {
				include("../includes/tar.class.php");
				require_once("../includes/filehandler.php");
				$tar = new tar();
				$tar->openTAR($uploadfile);
				if ($tar->hasFiles()) {
					if (getfilehandlertype('filehandlertypecfiles') == 's3') {
						$n = $tar->extractToS3("qimages","public");
					} else {
						$n = $tar->extractToDir("../assessment/qimages/");
					}
					require("../header.php");
					echo "<p>Extracted $n files.  <a href=\"admin2.php\">Continue</a></p>\n";
					require("../footer.php");
					exit;
				} else {
					require("../header.php");
					echo "<p>File appears to contain nothing</p>\n";
					require("../footer.php");
					exit;
				}

			}
			unlink($uploadfile);
			break;
		} else {
			require("../header.php");
			echo "<p>Error uploading file!</p>\n";
			require("../footer.php");
			exit;
		}
	case "importcoursefiles":
		if ($myrights < 100 || !$allowmacroinstall) { echo "You don't have the authority for this action"; break;}
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			if (strpos($uploadfile,'.zip')!==FALSE && class_exists('ZipArchive')) {
				require_once("../includes/filehandler.php");
				$zip = new ZipArchive();
				$res = $zip->open($uploadfile);
				$ne = 0;  $ns = 0;
				if ($res===true) {
					for($i = 0; $i < $zip->numFiles; $i++) {
						//if (file_exists("../course/files/".$zip->getNameIndex($i))) {
						if (doesfileexist('cfile',$zip->getNameIndex($i))) {
							$ns++;
						} else {
							$zip->extractTo("../course/files/", array($zip->getNameIndex($i)));
							relocatecoursefileifneeded("../course/files/".$zip->getNameIndex($i),$zip->getNameIndex($i));
							$ne++;
						}
					}
					require("../header.php");
					echo "<p>Extracted $ne files.  Skipped $ns files.  <a href=\"admin2.php\">Continue</a></p>\n";
					require("../footer.php");
					exit;
				} else {
					require("../header.php");
					echo "<p>File appears to contain nothing</p>\n";
					require("../footer.php");
					exit;
				}

			}
			unlink($uploadfile);
			break;
		} else {
			require("../header.php");
			echo "<p>Error uploading file!</p>\n";
			require("../footer.php");
			exit;
		}
	case "transfer":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		$exec = false;

		if ($myrights==75) {
			$stm = $DBH->prepare("SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id=:id AND imas_courses.ownerid=imas_users.id AND imas_users.groupid=:groupid");
			$stm->execute(array(':id'=>$_GET['id'], ':groupid'=>$groupid));
			if ($stm->rowCount()>0) {
				$stm = $DBH->prepare("UPDATE imas_courses SET ownerid=:ownerid WHERE id=:id");
				$stm->execute(array(':ownerid'=>$_POST['newowner'], ':id'=>$_GET['id']));
				$exec = true;
			}
			//$query = "UPDATE imas_courses,imas_users SET imas_courses.ownerid='{$_POST['newowner']}' WHERE ";
			//$query .= "imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
		} else if ($myrights==100) {
			$stm = $DBH->prepare("UPDATE imas_courses SET ownerid=:ownerid WHERE id=:id");
			$stm->execute(array(':ownerid'=>$_POST['newowner'], ':id'=>$_GET['id']));
			$exec = true;
		} else {
			$stm = $DBH->prepare("UPDATE imas_courses SET ownerid=:ownerid WHERE id=:id AND ownerid=:ownerid2");
			$stm->execute(array(':ownerid'=>$_POST['newowner'], ':id'=>$_GET['id'], ':ownerid2'=>$userid));
			$exec = true;
		}
		if ($exec && $stm->rowCount()>0) {
			$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
			$stm->execute(array(':courseid'=>$_GET['id'], ':userid'=>$_POST['newowner']));
			if ($stm->rowCount()==0) {
				$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES (:userid, :courseid)");
				$stm->execute(array(':userid'=>$_POST['newowner'], ':courseid'=>$_GET['id']));
			}
			$stm = $DBH->prepare("DELETE FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
			$stm->execute(array(':courseid'=>$_GET['id'], ':userid'=>$userid));
		}

		break;
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
		$stm = $DBH->prepare("INSERT INTO imas_groups (name) VALUES (:name)");
		$stm->execute(array(':name'=>$_POST['gpname']));
		break;
	case "modgroup":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$stm = $DBH->prepare("SELECT id FROM imas_groups WHERE name=:name AND id<>:id");
		$stm->execute(array(':name'=>$_POST['gpname'], ':id'=>$_GET['id']));
		if ($stm->rowCount()>0) {
			echo "<html><body>Group name already exists.  <a href=\"forms.php?action=modgroup&id=".Sanitize::encodeUrlParam($_GET['id'])."\">Try again</a></body></html>\n";
			exit;
		}
		$grptype = (isset($_POST['iscust'])?1:0);
		$stm = $DBH->prepare("UPDATE imas_groups SET name=:name,parent=:parent,grouptype=:grouptype WHERE id=:id");
		$stm->execute(array(':name'=>$_POST['gpname'], ':parent'=>$_POST['parentid'], ':grouptype'=>$grptype, ':id'=>$_GET['id']));
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
			$stm->execute(array(':email'=>$_POST['ltidomain'], ':FirstName'=>$_POST['ltidomain'], ':LastName'=>'LTIcredential',
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
}

session_write_close();
if ($myrights<75 || $from=='home') {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php");
} else if (empty($from)) {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/admin2.php");
} else if (isset($_GET['cid'])) {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_GET['cid']));
} else if ($from=='admin2') {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/admin2.php");
} else if (substr($from,0,2)=='ud' || substr($from,0,2)=='gd') {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/$backloc");
} else {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/admin2.php");
}
exit;
?>
