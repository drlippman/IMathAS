<?php
//var_dump($_GET);
//var_dump($_POST);
//IMathAS:  Main course page
//(c) 2006 David Lippman

/*** master php includes *******/
require_once "../init.php";


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$cid = Sanitize::courseId($_GET['cid']);
if (isset($_GET['secfilter'])) {
	$secfilter = $_GET['secfilter'];
	$_SESSION[$cid.'secfilter'] = $secfilter;
} else if (isset($_SESSION[$cid.'secfilter'])) {
	$secfilter = $_SESSION[$cid.'secfilter'];
} else {
	$secfilter = -1;
}
if (isset($_GET['rmode'])) {
	$rmode = $_GET['rmode'];
	$_SESSION[$cid.'rmode'] = $rmode;
} else if (isset($_SESSION[$cid.'rmode'])) {
	$rmode = $_SESSION[$cid.'rmode'];
} else {
	$rmode = 0;
}
$showemail = (($rmode&1)==1);
$showSID = (($rmode&2)==2);

$overwriteBody = 0;
$body = "";
$pagetitle = "";
$placeinhead = "";
$hasInclude = 0;
$istutor = isset($tutorid);
$isteacher = isset($teacherid);
if (!isset($CFG['GEN']['allowinstraddstus'])) {
	$CFG['GEN']['allowinstraddstus'] = false;
}
if (!isset($CFG['GEN']['allowinstraddtutors'])) {
	$CFG['GEN']['allowinstraddtutors'] = true;
}
$curBreadcrumb = $breadcrumbbase;
if (empty($_COOKIE['fromltimenu'])) {
    $curBreadcrumb .= " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
}
if (!isset($teacherid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = _('You need to log in as a teacher to access this page');
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING

	if (isset($_POST['posted']) && $_POST['posted']=="Unenroll") {
		$_GET['action'] = "unenroll";
	}
	if (isset($_POST['posted']) && $_POST['posted']=="Lock") {
		$_GET['action'] = "lock";
	}
	if (isset($_POST['lockinstead'])) {
		$_GET['action'] = "lock";
		$_POST['tolock'] = $_POST['tounenroll'] ?? $_GET['uid'];
	}

	if (isset($_GET['assigncode'])) {

		$curBreadcrumb .= " <a href=\"listusers.php?cid=$cid\">"._('Roster')."</a> &gt; "._('Assign Codes')."\n";
		$pagetitle = _('Assign Section/Code Numbers');

		if (isset($_POST['submit'])) {
			$keys = array_keys($_POST['sec'] ?? []);
			foreach ($keys as $stuid) {
				if ($_POST['sec'][$stuid]=='') {
					$_POST['sec'][$stuid] = null;
			  }
				if ($_POST['code'][$stuid]=='') {
					$_POST['code'][$stuid] = null;
				}
            }
            require_once '../includes/setSectionGroups.php';
			foreach ($keys as $stuid) {
				$stm = $DBH->prepare("UPDATE imas_students SET section=:section,code=:code WHERE id=:id AND courseid=:courseid ");
                $stm->execute(array(':section'=>$_POST['sec'][$stuid], ':code'=>$_POST['code'][$stuid], ':id'=>$stuid, ':courseid'=>$cid));
                setSectionGroups($_POST['uid'][$stuid], $cid, $_POST['sec'][$stuid]);
			}
			require_once '../includes/validatesections.php';
			validateSections($cid);
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
			exit;

		} else {
			$query = "SELECT imas_students.id,imas_students.userid,imas_users.FirstName,imas_users.LastName,imas_students.section,imas_students.code ";
			$query .= "FROM imas_students,imas_users WHERE imas_students.courseid=:courseid AND imas_students.userid=imas_users.id ";
			$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
			$resultStudentList = $DBH->prepare($query);
			$resultStudentList->execute(array(':courseid'=>$cid));
		}
	} elseif (isset($_GET['enroll']) && ($myrights==100 || (isset($CFG['GEN']['allowinstraddbyusername']) && $CFG['GEN']['allowinstraddbyusername']==true))) {

		$curBreadcrumb .= " <a href=\"listusers.php?cid=$cid\">"._('Roster')."</a> &gt; "._('Enroll Students')."\n";
		$pagetitle = _('Enroll an Existing User');

		if (isset($_POST['username'])) {
			$stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
			$stm->execute(array(':SID'=>$_POST['username']));
			$row = $stm->fetch(PDO::FETCH_NUM);
			if ($row === false) {
				$overwriteBody = 1;
				$body = _("Error, username doesn't exist.") . " <a href=\"listusers.php?cid=$cid&enroll=student\">" . _('Try again') . "</a>\n";
				if ($CFG['GEN']['allowinstraddstus']) {
					$body .= ' ' . _('or') . " <a href=\"listusers.php?cid=$cid&newstu=new\">" . _('create and enroll a new student') . "</a>";
				}
			} else {
				$id = $row[0];
				$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':userid'=>$id, ':courseid'=>$cid));
				if ($stm->fetch() !== false) {
					echo _("Teachers can't be enrolled as students - use Student View, or create a separate student account.");
					exit;
				}
				$stm = $DBH->prepare("SELECT id FROM imas_tutors WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':userid'=>$id, ':courseid'=>$cid));
				if ($stm->fetch() !== false) {
					echo _("Tutors can't be enrolled as students.");
					exit;
				}
				$stm = $DBH->prepare("SELECT id FROM imas_students WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':userid'=>$id, ':courseid'=>$cid));
				if ($stm->fetch() !== false) {
					echo _('This username is already enrolled in the class.');
					exit;
				}
				$stm = $DBH->prepare("SELECT deflatepass FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$cid));
				$row = $stm->fetch(PDO::FETCH_NUM);
				$deflatepass = $row[0];
				$query = "INSERT INTO imas_students (userid,courseid,latepass,section,code) ";
				$query .= "VALUES (:userid,:courseid,:latepass,:section,:code)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(":userid"=>$id,":courseid"=>$cid,":latepass"=>$deflatepass,
					":section"=>trim($_POST['section'])!=''?trim($_POST['section']):null,
					":code"=>trim($_POST['code'])!=''?trim($_POST['code']):null
					));
                require_once '../includes/setSectionGroups.php';
                setSectionGroups($id, $cid, $_POST['section']);
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
				exit;
			}

		}
	} elseif (isset($_GET['newstu']) && $CFG['GEN']['allowinstraddstus']) {
		$curBreadcrumb .= " <a href=\"listusers.php?cid=$cid\">"._('Roster')."</a> &gt; "._('Enroll Students')."\n";
		$pagetitle = _('Enroll a New Student');
		$placeinhead = '<script type="text/javascript" src="'.$staticroot.'/javascript/jquery.validate.min.js?v=122917"></script>';

		if (isset($_POST['SID'])) {
			require_once "../includes/newusercommon.php";
			$errors = checkNewUserValidation(array('SID','firstname','lastname','email','pw1'));
			if ($errors != '') {
				$overwriteBody = 1;
				$body = $errors . "<br/><a href=\"listusers.php?cid=$cid&newstu=new\">" . _('Try Again') . "</a>\n";
			} else {
				require_once "../includes/password.php";
				$pwhash = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
				
				$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify, forcepwreset) ";
				$query .= "VALUES (:SID, :password, :rights, :FirstName, :LastName, :email, :msgnotify, 1);";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$pwhash, ':rights'=>10,
					':FirstName'=>Sanitize::stripHtmlTags($_POST['firstname']),
					':LastName'=>Sanitize::stripHtmlTags($_POST['lastname']),
					':email'=>Sanitize::emailAddress($_POST['email']),
					':msgnotify'=>0));
				$newuserid = $DBH->lastInsertId();
				//$query = "INSERT INTO imas_students (userid,courseid) VALUES ($newuserid,'$cid')";
				$stm = $DBH->prepare("SELECT deflatepass FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$cid));
				$row = $stm->fetch(PDO::FETCH_NUM);
				$deflatepass = $row[0];
				$query = "INSERT INTO imas_students (userid,courseid,latepass,section,code) ";
				$query .= "VALUES (:userid,:courseid,:latepass,:section,:code)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(":userid"=>$newuserid,":courseid"=>$cid,":latepass"=>$deflatepass,
					":section"=>trim($_POST['section'])!=''?trim($_POST['section']):null,
					":code"=>trim($_POST['code'])!=''?trim($_POST['code']):null
					));
                require_once '../includes/setSectionGroups.php';
                setSectionGroups($newuserid, $cid, $_POST['section']);
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
				exit;
			}
		}
	} elseif (isset($_POST['posted']) && $_POST['posted']=="Copy Emails") {
		$curBreadcrumb .= " <a href=\"listusers.php?cid=$cid\">"._('Roster')."</a> &gt; "._('Copy Emails')."\n";
		$pagetitle = _('Copy Student Emails');
		if (!empty($_POST['checked'])) {
			$ulist = implode(',', array_map('intval', $_POST['checked']));
			$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email ";
			$query .= "FROM imas_students JOIN imas_users ON imas_students.userid=imas_users.id WHERE imas_students.courseid=:courseid AND imas_users.id IN ($ulist) ";
			$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid));
			$stuemails = array();
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$row[2] = str_replace('BOUNCED','',$row[2]);
				$name = $row[0] . ' ' . $row[1];
				$stuemails[] = '"'.Sanitize::encodeStringForDisplay(str_replace('"','',$name)) . '" ' . ' &lt;' . Sanitize::encodeStringForDisplay($row[2]) . '&gt;';
			}
			$stuemails = implode('; ',$stuemails);
		}

	} elseif (isset($_GET['chgstuinfo'])) {
		$curBreadcrumb .= " <a href=\"listusers.php?cid=$cid\">"._('Roster')."</a> &gt; "._('Change User Info')."\n";
		$pagetitle = _('Change Student Info');
		$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/jquery.validate.min.js?v=122917"></script>';
		require_once "../includes/newusercommon.php";

		$uidToUpdate = Sanitize::onlyInt($_GET['uid']);

		if (isset($_POST['timelimitmult'])) {
			$msgout = '';
			$stm = $DBH->prepare("SELECT iu.* FROM imas_users AS iu JOIN imas_students AS istu ON istu.userid=iu.id WHERE istu.courseid=? AND istu.userid=?");
			$stm->execute([$cid, $uidToUpdate]);
			$olddata = $stm->fetch(PDO::FETCH_ASSOC);
			if ($olddata === false) {
				echo _('Invalid userid');
				exit;
			}
			$jsondata = json_decode($olddata['jsondata'], true);
			if (!is_array($jsondata)) {
				$jsondata = [];
			}
			$chglog = [];
			if ($olddata['rights'] < $myrights && isset($_POST['SID']) && (
				$_POST['SID'] != $olddata['SID'] || $_POST['firstname'] != $olddata['FirstName'] || $_POST['lastname'] != $olddata['LastName'] ||
				$_POST['email'] != $olddata['email'] || isset($_POST['doresetpw'])
			)) {
				if (checkFormatAgainstRegex($_POST['SID'], $loginformat) && $_POST['SID'] != $olddata['SID']) {
					$un = $_POST['SID'];
					$stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
					$stm->execute(array(':SID'=>$un));
					if ($stm->fetch() !== false) {
						$updateusername = false;
					} else {
						$updateusername = true;
						$chglog['oldusername'] = $olddata['SID'];
					}
				} else {
					$updateusername = false;
				}
				
				if ($updateusername) {
					$msgout .= '<p>' . sprintf(_('Username changed to %s'), '<span class="pii-username">'.Sanitize::encodeStringForDisplay($un).'</span>') . '</p>';
				} else {
					$msgout .= '<p>' . _('Username left unchanged') . '</p>';
				}
				if ($_POST['firstname'] != $olddata['FirstName'] || $_POST['lastname'] != $olddata['LastName']) {
					$chglog['oldname'] = $olddata['LastName'] . ', ' . $olddata['FirstName'];
				}
				$query = "UPDATE imas_users SET FirstName=:FirstName,LastName=:LastName";

				$qarr = array(':FirstName'=>$_POST['firstname'], ':LastName'=>$_POST['lastname']);
				if ($updateusername) {
					$query .= ",SID=:SID";
					$qarr[':SID'] = $un;
				}
				if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/',$_POST['email']) ||
					(isset($CFG['acct']['emailFormat']) && !checkFormatAgainstRegex($_POST['email'], $CFG['acct']['emailFormat']))) {
					$msgout .= '<p>' . _('Invalid email address - left unchanged') . '</p>';
				} else {
					if ($_POST['email'] != $olddata['email']) {
						$chglog['oldemail'] = $olddata['email'];
					}
					$query .= ",email=:email";
					$qarr[':email'] = $_POST['email'];
				}
				if (isset($_POST['doresetpw'])) {
					if (isset($CFG['acct']['passwordFormat']) && !checkFormatAgainstRegex($_POST['pw1'], $CFG['acct']['passwordFormat'])) {
						$msgout .= '<p>' . _('Invalid password - left unchanged') . '</p>';
					} else {
						require_once "../includes/password.php";
						$newpw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
	
						$chglog['resetpw'] = 1;
						$query .= ",password=:password,forcepwreset=1";
						$qarr[':password'] = $newpw;
						$msgout .= '<p>' . _('Password updated') . '</p>';
					}
				}
				if (!empty($chglog)) {
					$query .= ',jsondata=:jsondata';
					$chglog['by'] = $userid;
					$jsondata['chglog'][time()] = $chglog;
					$qarr['jsondata'] = json_encode($jsondata);
				}
				
				$query .= " WHERE id=:id AND rights<:rights";
				$qarr[':id'] = $uidToUpdate;
				$qarr[':rights'] = $myrights;
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);

			} else {
				$msgout = '<p>' . _('Username, name, email, and password left unchanged.') . '</p>';
			}
			$code = $_POST['code'];
			$section = $_POST['section'];
			if (trim($_POST['section'])==='') {
				$section = null;
			}
			if (trim($_POST['code'])==='') {
				$code = null;
			}
			if (isset($_POST['locked'])) {
				$locked = time();
			} else {
				$locked = 0;
			}
			if (isset($_POST['hidefromcourselist'])) {
				$hide = 1;
			} else {
				$hide = 0;
			}
			$timelimitmult = floatval($_POST['timelimitmult']);
			//echo $timelimitmult;
			if ($timelimitmult <= 0) {
				$timelimitmult = '1.0';
			}
			$latepassmult = floatval($_POST['latepassmult']);
			if ($latepassmult <= 0) {
				$latepassmult = '1.0';
			}
			$latepasses = intval($_POST['latepasses']);
			//echo $timelimitmult;

			if ($locked==0) {
				$stm = $DBH->prepare("UPDATE imas_students SET code=:code,section=:section,locked=:locked,timelimitmult=:timelimitmult,latepassmult=:latepassmult,hidefromcourselist=:hidefromcourselist,latepass=:latepass WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':code'=>$code, ':section'=>$section, ':locked'=>$locked, ':timelimitmult'=>$timelimitmult, ':latepassmult'=>$latepassmult, ':hidefromcourselist'=>$hide, ':latepass'=>$latepasses, ':userid'=>$uidToUpdate, ':courseid'=>$cid));
			} else {
				$stm = $DBH->prepare("UPDATE imas_students SET code=:code,section=:section,timelimitmult=:timelimitmult,latepassmult=:latepassmult,hidefromcourselist=:hidefromcourselist,latepass=:latepass WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':code'=>$code, ':section'=>$section, ':timelimitmult'=>$timelimitmult, ':latepassmult'=>$latepassmult, ':hidefromcourselist'=>$hide, ':latepass'=>$latepasses, ':userid'=>$uidToUpdate, ':courseid'=>$cid));
				$stm = $DBH->prepare("UPDATE imas_students SET locked=:locked WHERE userid=:userid AND courseid=:courseid AND locked=0");
				$stm->execute(array(':locked'=>$locked, ':userid'=>$uidToUpdate, ':courseid'=>$cid));
            }
            require_once '../includes/setSectionGroups.php';
            setSectionGroups($uidToUpdate, $cid, $section);
			require_once '../includes/validatesections.php';
			validateSections($cid);

			require_once '../includes/userpics.php';

			// $_FILES[]['tmp_name'] is not user provided. This is safe.
			if (is_uploaded_file($_FILES['stupic']['tmp_name'])) {
				processImage($_FILES['stupic'],Sanitize::onlyInt($uidToUpdate),200,200);
				processImage($_FILES['stupic'],'sm'.Sanitize::onlyInt($uidToUpdate),40,40);
				$chguserimg = 1;
			} else if (isset($_POST['removepic'])) {
				deletecoursefile('userimg_'.Sanitize::onlyInt($uidToUpdate).'.jpg');
				deletecoursefile('userimg_sm'.Sanitize::onlyInt($uidToUpdate).'.jpg');
				$chguserimg = 0;
			} else {
				$chguserimg = -1;
			}
			if ($chguserimg != -1) {
				$stm = $DBH->prepare("UPDATE imas_users SET hasuserimg=:chguserimg WHERE id=:id");
				$stm->execute(array(':id'=>$uidToUpdate, ':chguserimg'=>$chguserimg));
			}


			require_once "../header.php";
			echo '<div class="breadcrumb">'.$curBreadcrumb.'</div>';
			echo '<div id="headerlistusers" class="pagetitle"><h1>'.$pagetitle.'</h1></div>';
			echo "<p>" . _('User info updated.') . " ";
			echo $msgout;
			echo "</p><p><a href=\"listusers.php?cid=$cid\">" . _('OK') . "</a></p>";
			require_once "../footer.php";

			//header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
		} else {
			$query = "SELECT imas_users.*,imas_students.code,imas_students.section,imas_students.locked,imas_students.timelimitmult,imas_students.latepassmult,imas_students.hidefromcourselist,imas_students.latepass FROM imas_users,imas_students ";
			$query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id=:id AND imas_students.courseid=:courseid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':id'=>$uidToUpdate, ':courseid'=>$cid));
			$lineStudent = $stm->fetch(PDO::FETCH_ASSOC);

		}

	} elseif ((isset($_POST['posted']) && ($_POST['posted']=="E-mail" || $_POST['posted']=="Message"))|| isset($_GET['masssend']))  {
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "masssend.php";
	} elseif ((isset($_POST['posted']) && $_POST['posted']=="Make Exception") || isset($_GET['massexception'])) {
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "massexception.php";
	} elseif (isset($_GET['action']) && $_GET['action']=="unenroll" && !isset($CFG['GEN']['noInstrUnenroll'])){
		$curBreadcrumb .= " <a href=\"listusers.php?cid=$cid\">"._('Roster')."</a> &gt; "._('Confirm Change')."\n";
		$pagetitle = _('Unenroll Students');
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "unenroll.php";

	} elseif (isset($_GET['action']) && $_GET['action']=="lock") {
		$curBreadcrumb .= " <a href=\"listusers.php?cid=$cid\">"._('Roster')."</a> &gt; "._('Confirm Change')."\n";
		$pagetitle = _('Lock Students');
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "lockstu.php";
	} elseif (isset($_POST['posted']) && $_POST['posted']=="Unlock") {
		$calledfrom='lu';
		require_once('lockstu.php');
		exit;
	} elseif (isset($_POST['action']) && $_POST['action']=="lockone" && is_numeric($_POST['uid'])) {
		$now = time();
		$stm = $DBH->prepare("UPDATE imas_students SET locked=:locked WHERE courseid=:courseid AND userid=:userid");
		$stm->execute(array(':locked'=>$now, ':courseid'=>$cid, ':userid'=>$_POST['uid']));

		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
		exit;
	} elseif (isset($_POST['action']) && $_POST['action']=="unlockone" && is_numeric($_POST['uid'])) {
		$now = time();
		$stm = $DBH->prepare("UPDATE imas_students SET locked=0 WHERE courseid=:courseid AND userid=:userid");
		$stm->execute(array(':courseid'=>$cid, ':userid'=>$_POST['uid']));

		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
		exit;
	} else if (isset($_POST['posted']) && $_POST['posted']=='maketutor' && !empty($_POST['checked'])) {
		$curBreadcrumb .= " <a href=\"listusers.php?cid=$cid\">"._('Roster')."</a> &gt; "._('Add Tutors')."\n";
		$pagetitle = _('Add Tutors');

		// get stu info
		$stus = array_map('intval', $_POST['checked']);
		$ph = Sanitize::generateQueryPlaceholders($stus);
		$stm = $DBH->prepare("SELECT iu.FirstName,iu.LastName,iu.id FROM imas_users AS iu 
							  JOIN imas_students AS istu ON istu.userid=iu.id
							  WHERE iu.id IN ($ph) AND istu.courseid=?");
		$stm->execute(array_merge($stus, [$cid]));
		$stusToPromote = $stm->fetchAll(PDO::FETCH_ASSOC);

		// get section info
		$isdiag = false;
		$sections = array();
		//if diagnostic, then we'll use level-2 selectors in place of sections.  level-2 selector is recorded into the
		//imas_students.section field, so filter will act the same.
		$stm = $DBH->prepare("SELECT sel2name,sel2list FROM imas_diags WHERE cid=:cid");
		$stm->execute(array(':cid'=>$cid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if ($row !== false) {
			$isdiag = true;
			list($limitname,$sel2list) = $row;
			$sel2list = str_replace('~',';',$sel2list);
			$sections = array_unique(explode(';',$sel2list));
		}

		//if not diagnostic, we'll work off the sections
		if (!$isdiag) {
			$stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE courseid=:courseid AND section IS NOT NULL");
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$sections[] = $row[0];
			}
			$limitname = "Section";

		}
		sort($sections);
	} else { //DEFAULT DATA MANIPULATION HERE

		$curBreadcrumb .= " "._('Roster')."\n";
		$pagetitle = _('Student Roster');
		$stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE imas_students.courseid=:courseid AND imas_students.section IS NOT NULL ORDER BY section");
		$stm->execute(array(':courseid'=>$cid));
		$sectionrows = $stm->fetchAll(PDO::FETCH_NUM);
		if (count($sectionrows)>0) {
			$hassection = true;
			$sectionselect = "<br/><select id=\"secfiltersel\" onchange=\"chgsecfilter()\"><option value=\"-1\" " ;
			if ($secfilter==-1) {$sectionselect .= 'selected=1';}
			$sectionselect .=  '>'._('All').'</option>';
			foreach ($sectionrows as $row) {
				$sectionselect .=  "<option value=\"" . Sanitize::encodeStringForDisplay($row[0]) . "\" ";
				if ($row[0]==$secfilter) {
					$sectionselect .=  'selected=1';
				}
				$sectionselect .=  ">". Sanitize::encodeStringForDisplay($row[0]) . "</option>";
			}
			$sectionselect .=  "</select>";
		} else {
			$hassection = false;
		}
		$stm = $DBH->prepare("SELECT count(id) FROM imas_students WHERE imas_students.courseid=:courseid AND imas_students.code IS NOT NULL");
		$stm->execute(array(':courseid'=>$cid));
		if ($stm->fetchColumn(0)>0) {
			$hascode = true;
		} else {
			$hascode = false;
		}

		if ($hassection) {
			$stm = $DBH->prepare("SELECT usersort FROM imas_gbscheme WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$sectionsort = ($row[0]==0);
		} else {
			$sectionsort = false;
		}
		$haslatepasses = false;

		$query = "SELECT imas_students.id,imas_students.userid,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.SID,imas_students.lastaccess,";
		$query .= "imas_students.section,imas_students.code,imas_students.locked,imas_users.hasuserimg,imas_students.timelimitmult,imas_students.latepassmult,imas_students.latepass ";
		$query .= "FROM imas_students,imas_users WHERE imas_students.courseid=:courseid AND imas_students.userid=imas_users.id ";
		if ($secfilter!=-1) {
			$query .= "AND imas_students.section=:section ";
		}
		if ($sectionsort) {
			$query .= "ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		} else {
			$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		}
		$resultDefaultUserList = $DBH->prepare($query);
		if ($secfilter!=-1) {
			$resultDefaultUserList->execute(array(':courseid'=>$cid, ':section'=>$secfilter));
		} else {
			$resultDefaultUserList->execute(array(':courseid'=>$cid));
		}
		$defaultUserList = array();
		while ($line=$resultDefaultUserList->fetch(PDO::FETCH_ASSOC)) {
			$defaultUserList[] = $line;
			if ($line['latepass']>0) {
				$haslatepasses=true;
			}
		}
		$hasSectionRowHeader = ($hassection)? "<th><label for=\"secfiltersel\">"._('Section')."</label>$sectionselect</th>" : "";
		$hasCodeRowHeader = ($hascode) ? "<th>"._('Code')."</th>" : "";
		$hasLatePassHeader = ($haslatepasses) ? "<th>"._('LatePasses')."</th>" : "";

	}
} //END DATA MANIPULATION

//$pagetitle = "Student List";

/******* begin html output ********/
if (empty($fileToInclude)) {

$placeinhead .= "<script type=\"text/javascript\">";
$placeinhead .= 'function chgsecfilter() { ';
$placeinhead .= '       var sec = document.getElementById("secfiltersel").value; ';
$address = $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid";
$placeinhead .= "       var toopen = '$address&secfilter=' + encodeURIComponent(sec);\n";
$placeinhead .= "  	window.location = toopen; \n";
$placeinhead .= "}\n";
$placeinhead .= '$(function() { $(".lal").attr("title","'._('View login log').'");
	$(".gl").attr("title","'._('View student grades').'");
	$(".ex").attr("title","'._('Set due date exceptions').'");
	$(".ui").attr("title","'._('Edit student profile and options').'");
	$(".ll").attr("title","'._('Lock student out of the course').'");
	$(".ull").attr("title","'._('Allow student access to the course').'");
	$("input[type=checkbox]").on("change",function() {$(this).parents("tr").toggleClass("highlight");});
	});';
$placeinhead .= "</script>";
$placeinhead .= '<script type="text/javascript">$(function() {
  var html = \'<span class="dropdown"><a role="button" tabindex=0 class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">INSERTNAME <img src="'.$staticroot.'/img/collapse.svg" width="10" class="mida" alt="" /></a>\';
  html += \'<ul role="menu" class="dropdown-menu">\';
  $("a[data-uid]").each(function (i,el) {
  	var uid = $(el).attr("data-uid");
	var thishtml = html.replace("INSERTNAME", el.textContent) + \' <li><a href="listusers.php?cid=\'+cid+\'&chgstuinfo=true&uid=\'+uid+\'">'._('Student profile and options').'</a></li>\';
	thishtml += \' <li><a href="gradebook.php?cid=\'+cid+\'&stu=\'+uid+\'&from=listusers">'._('View Grades').'</a></li>\';
	thishtml += \' <li><a href="viewloginlog.php?cid=\'+cid+\'&uid=\'+uid+\'">'._('Login Log').'</a></li>\';
	thishtml += \' <li><a href="viewactionlog.php?cid=\'+cid+\'&uid=\'+uid+\'">'._('Activity Log').'</a></li>\';
	if ($(el).parent(".greystrike").length) {
		thishtml += \' <li><a href="#" onclick="postRosterForm(\'+uid+\',\\\'unlockone\\\');return false;">'._('Unlock').'</a></li>\';
	} else {
		thishtml += \' <li><a href="#" onclick="postRosterForm(\'+uid+\',\\\'lockone\\\');return false;">'._('Lock out of course').'</a></li>\';
	}
	';
if (!isset($CFG['GEN']['noInstrUnenroll'])) {
	$placeinhead .= 'thishtml += \'<li role="separator" class="divider"></li>\';';
	//$placeinhead .= 'thishtml += \'<li><a href="#" onclick="postRosterForm(\'+uid+\',\\\'unenroll\\\');;return false;">'. _('Unenroll'). '</a></li>\'';
	$placeinhead .= 'thishtml += \'<li><a href="listusers.php?cid=\'+cid+\'&action=unenroll&uid=\'+uid+\'">'. _('Unenroll'). '</a></li>\'';
}
$placeinhead .= '
	thishtml += \'</ul></span> \';
	$(el).replaceWith(thishtml);
  });
  $(".dropdown-toggle").dropdown();
  });
  function postRosterForm(uid,action) {
  	$("<form>", {method: "POST", action: $("#qform").attr("action")})
	  .append($("<input>", {name:"action", value:action, type:"hidden"}))
	  .append($("<input>", {name:"uid", value:uid, type:"hidden"}))
	  .appendTo("body").submit();
  }
  function postWithSelform(val) {
	$("#qform").append($("<input>", {name:"posted", value:val, type:"hidden"})).submit();
  }
  function copyemails() {
	var ids = [];
	$("#myTable input:checkbox:checked").each(function(i) {
		ids.push(this.value);
	});
	GB_show("'._('Emails').'","viewemails.php?cid="+cid+"&ids="+ids.join("-"),500,500);
  }
  </script>';

require_once "../header.php";
$curdir = rtrim(dirname(__FILE__), '/\\');
}
/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	if (strlen($body)<2) {
		require_once "./$fileToInclude";
	} else {
		echo $body;
	}
} else {
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerlistusers" class="pagetitle"><h1><?php echo $pagetitle ?></h1></div>
<?php

	if (isset($_GET['assigncode'])) {
?>
	<form method=post action="listusers.php?cid=<?php echo $cid ?>&assigncode=1">
		<table class=gb>
        <caption class="sr-only"><?php echo _('Students'); ?></caption>
			<thead>
			<tr>
				<th><?php echo _('Name'); ?></th><th><?php echo _('Section'); ?></th><th><?php echo _('Code'); ?></th>
			</tr>
			</thead>
			<tbody>
<?php
		$i = 0;
		while ($line=$resultStudentList->fetch(PDO::FETCH_ASSOC)) {
			$i++;
?>
			<tr>
                <td><span class="pii-full-name" id="n<?php echo $i;?>"><?php echo Sanitize::encodeStringForDisplay($line['LastName']) . ", " . Sanitize::encodeStringForDisplay($line['FirstName']); ?></span>
                    <input type="hidden" name="uid[<?php echo Sanitize::onlyInt($line['id']); ?>]" value="<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>" />
                </td>
				<td><input type=text name="sec[<?php echo Sanitize::onlyInt($line['id']); ?>]" value="<?php echo Sanitize::encodeStringForDisplay($line['section']); ?>" aria-labelledby="n<?php echo $i;?>"/></td>
				<td><input type=text name="code[<?php echo Sanitize::onlyInt($line['id']); ?>]" value="<?php echo Sanitize::encodeStringForDisplay($line['code']); ?>" aria-labelledby="n<?php echo $i;?>"/></td>
			</tr>
<?php
		}
?>
			</tbody>
		</table>
		<button type=submit name=submit value=submit><?php echo _('Submit'); ?></button>
	</form>
<?php
	} elseif (isset($_GET['enroll']) && ($myrights==100 || (isset($CFG['GEN']['allowinstraddbyusername']) && $CFG['GEN']['allowinstraddbyusername']==true))) {
?>
	<form method=post action="listusers.php?enroll=student&cid=<?php echo $cid ?>">
		<span class=form><?php echo _('Username to enroll:'); ?></span>
		<span class=formright><input type="text" class="pii-username" name="username"></span><br class=form>
		<span class=form><?php echo _('Section (optional):'); ?></span>
		<span class=formright><input type="text" name="section"></span><br class=form>
		<span class=form><?php echo _('Code (optional):'); ?></span>
		<span class=formright><input type="text" name="code"></span><br class=form>
		<div class=submit><input type="submit" value="<?php echo _('Enroll'); ?>"></div>
	</form>
<?php
	} elseif (isset($_GET['newstu']) && $CFG['GEN']['allowinstraddstus']) {
?>

	<form method=post id=pageform class=limitaftervalidate action="listusers.php?cid=<?php echo $cid ?>&newstu=new">
	<span class=form><label for="SID"><?php echo $loginprompt;?>:</label></span> <input class="form pii-username" type=text size=12 id=SID name=SID><BR class=form>
	<span class=form><label for="pw1"><?php echo _('Choose a password:'); ?></label></span><input class="form pii-security" type=text size=20 id=pw1 name=pw1><BR class=form>
	<span class=form><label for="firstname"><?php echo _('Enter First Name:'); ?></label></span> <input class="form pii-first-name" type=text size=20 id=firstname name=firstname><BR class=form>
	<span class=form><label for="lastname"><?php echo _('Enter Last Name:'); ?></label></span> <input class="form pii-last-name" type=text size=20 id=lastname name=lastname><BR class=form>
	<span class=form><label for="email"><?php echo _('Enter E-mail address:'); ?></label></span>  <input class="form pii-email" type=text size=60 id=email name=email><BR class=form>
	<span class=form><?php echo _('Section (optional):'); ?></span>
		<span class=formright><input type="text" name="section"></span><br class=form>
	<span class=form><?php echo _('Code (optional):'); ?></span>
		<span class=formright><input type="text" name="code"></span><br class=form>
	<div class=submit><input type=submit value="<?php echo _('Create and Enroll'); ?>"></div>
	</form>

<?php
		require_once "../includes/newusercommon.php";
		showNewUserValidation("pageform");
	} elseif (isset($_POST['posted']) && $_POST['posted']=="Copy Emails") {
		if (empty($_POST['checked'])) {
			echo _('No student selected.') . " <a href=\"listusers.php?cid=$cid\">" . _('Try again') . "</a>";
		} else {
			echo '<textarea id="emails" rows="30" cols="60">'.Sanitize::encodeStringForDisplay($stuemails).'</textarea>';
			echo '<script type="text/javascript">addLoadEvent(function(){var el=document.getElementById("emails");el.focus();el.select();})</script>';
		}

	}elseif (isset($_GET['chgstuinfo'])) {
		if ($lineStudent['rights']<$myrights) {
			$disabled = '';
		} else {
			$disabled = 'disabled';
		}
?>
		<form enctype="multipart/form-data" id=pageform method=post action="listusers.php?cid=<?php echo $cid ?>&chgstuinfo=true&uid=<?php echo Sanitize::onlyInt($_GET['uid']) ?>" class="limitaftervalidate"/>
            <span class=form><label for="SID"><?php echo _('User Name (login name):'); ?></label></span>
			<input <?php echo $disabled;?> class="form pii-username" type=text size=20 id=SID name=SID value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['SID']); ?>"/><br class=form>
			<span class=form><label for="firstname"><?php echo _('First Name:'); ?></label></span>
			<input <?php echo $disabled;?> class="form pii-first-name" type=text size=20 id=firstname name=firstname value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['FirstName']); ?>"/><br class=form>
			<span class=form><label for="lastname"><?php echo _('Last Name:'); ?></label></span>
			<input <?php echo $disabled;?> class="form pii-last-name" type=text size=20 id=lastname name=lastname value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['LastName']); ?>"/><BR class=form>
			<span class=form><label for="email"><?php echo _('E-mail address:'); ?></label></span>
			<input <?php echo $disabled;?> class="form pii-email" type=text size=60 id=email name=email value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['email']); ?>"/><BR class=form>
			<span class=form><label for="stupic"><?php echo _('Picture:'); ?></label></span>
			<span class="formright">
			<?php
		if ($lineStudent['hasuserimg']==1) {
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				echo "<img class=\"pii-image\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_" . Sanitize::onlyInt($_GET['uid']) . ".jpg\" alt=\"" . _('User picture') . "\"/> <input type=\"checkbox\" name=\"removepic\" value=\"1\" /> " . _('Remove') . " ";
			} else {
				$curdir = rtrim(dirname(__FILE__), '/\\');
				$galleryPath = "$curdir/course/files/";
				echo "<img class=\"pii-image\" src=\"$imasroot/course/files/userimg_" . Sanitize::onlyInt($_GET['uid']) . ".jpg\" alt=\"" . _('User picture') . "\"/> <input type=\"checkbox\" name=\"removepic\" value=\"1\" /> " . _('Remove') . " ";
			}
		} else {
			echo _('No Pic') . " ";
		}
		?>
			<br/><input type="file" name="stupic"/></span><br class="form" />
			<span class=form><?php echo _('Section (optional):'); ?></span>
			<span class=formright><input type="text" name="section" value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['section']); ?>"/></span><br class=form>
			<span class=form><?php echo _('Code (optional):'); ?></span>
			<span class=formright><input type="text" name="code" value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['code']); ?>"/></span><br class=form>
			<span class=form><?php echo _('Time Limit Multiplier:'); ?></span>
			<span class=formright><input type="number" min="0.01" step="0.01" name="timelimitmult" value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['timelimitmult']); ?>"/></span><br class=form>
			<span class=form><?php echo _('LatePass Time Multiplier:'); ?></span>
			<span class=formright><input type="number" min="0.01" step="0.01" name="latepassmult" value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['latepassmult']); ?>"/></span><br class=form>
			<span class=form><?php echo _('LatePasses:'); ?></span>
			<span class=formright><input type="number" min="0" name="latepasses" value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['latepass']); ?>"/></span><br class=form>
			<span class=form><?php echo _('Lock out of course?'); ?></span>
			<span class=formright><input type="checkbox" name="locked" value="1" <?php if ($lineStudent['locked']>0) {echo ' checked="checked" ';} ?>/></span><br class=form>
			<span class="form"><?php echo _('Student has course hidden from course list?'); ?></span>
			<span class="formright"><input type="checkbox" name="hidefromcourselist" value="1" <?php if ($lineStudent['hidefromcourselist']>0) {echo ' checked="checked" ';} ?>/></span><br class=form>
			<span class=form><label for="doresetpw"><?php echo _('Reset password?'); ?></label></span>
			<span class=formright>
				<input <?php echo $disabled;?> type=checkbox name="doresetpw" id="doresetpw" value="1" onclick="$('#newpwwrap').toggle(this.checked)" />
				<span id="newpwwrap" style="display:none"><label for="pw1"><?php echo _('Set temporary password to:'); ?></label>
				<input type=text size=20 name="pw1" id="pw1" /></span>
			</span><br class=form />
			<div class=submit><input type=submit value="<?php echo _('Update Info'); ?>"></div>
		</form>

<?php
		$requiredrules = array(
            'pw1'=>'{depends: function(element) {return $("#doresetpw").is(":checked")}}',
            'email' => 'function(el) { return $("#SID").val().substring(0,4) != "lti-" }'
        );
		require_once "../includes/newusercommon.php";
		showNewUserValidation("pageform", array(), $requiredrules, array('originalSID'=>$lineStudent['SID']));
	} else if (isset($_POST['posted']) && $_POST['posted']=='maketutor' && count($_POST['checked'])> 0) {
		echo '<form enctype="multipart/form-data" id=pageform method=post action="managetutors.php?cid='.$cid.'">';

		echo '<p class=noticetext style="font-size:150%">'._('Warning').'</p>';
		echo '<p>'._('To promote these students to a tutor, they will have to be un-enrolled as a student, ');
		echo '<span class="noticetext">'._('which will DELETE ALL their student data').'</span>'._(', including assessment scores. ');
		echo _('If you are SURE you want to do this, check the boxes next to each student.').'</p>';
		echo '<p>'._('Un-enroll as a student and add as a tutor:').'</p>';

		echo '<table class="gb">
    		<caption class="sr-only">'._('Potential Tutors').'</caption>
			<thead>
				<tr>
					<th>'._('Add as tutor?').'</th>
					<th>'._('Name').'</th>
					<th>'._('Limit to').' '. Sanitize::encodeStringForDisplay($limitname). '</th>
				</tr>
			</thead>
			<tbody>';

		foreach ($stusToPromote as $k=>$u) {
			echo '<tr>';
			echo '<td><input type=checkbox name="promotetotutor[]" value="'.intval($u['id']).'" id="cb'.intval($k).'"></td>';
			echo '<td class="pii-full-name"><label id="n'.intval($k).'" for="cb'.intval($k).'">';
			echo Sanitize::encodeStringForDisplay($u['LastName'] . ', ' . $u['FirstName']);
			echo '</label></td>';
			echo '<td>';
			echo '<select name="section['.Sanitize::encodeStringForDisplay($u['id']).']" aria-labelledby="n'.intval($k).'">';
			echo '<option value="">'._('All').'</option>';
			foreach ($sections as $sec) {
				echo '<option value="'.Sanitize::encodeStringForDisplay($sec).'">'.Sanitize::encodeStringForDisplay($sec).'</option>';
			}
			echo '</select></td></tr>';
		}
		echo '</tbody></table>';

		echo '<p><button type=submit name=submit value=submit>'._('Submit').'</button></p>';
		echo '</form>';
	} else {
?>

	<script type="text/javascript">
	var picsize = 0;
	function rotatepics() {
		picsize = (picsize+1)%3;
		picshow(picsize);
	}
	function chgpicsize() {
		var size = document.getElementById("picsize").value;
		picshow(size);
	}
	function picshow(size) {
		if (size==0) {
			els = document.getElementById("myTable").getElementsByTagName("img");
			for (var i=0; i<els.length; i++) {
				els[i].style.display = "none";
			}
		} else {
			els = document.getElementById("myTable").getElementsByTagName("img");
			for (var i=0; i<els.length; i++) {
				els[i].style.display = "inline";
				if (els[i].getAttribute("src").match("userimg_sm")) {
					if (size==2) {
						els[i].setAttribute("src",els[i].getAttribute("src").replace("_sm","_"));
					}
				} else if (size==1) {
					els[i].setAttribute("src",els[i].getAttribute("src").replace("_","_sm"));
				}
			}
		}
	}
	function chgrmode() {
		var rmode = 1*$("#showemail").val()+2*$("#showsid").val();
		window.location = "listusers.php?cid="+cid+"&rmode="+rmode;
	}
	</script>
	<script type="text/javascript" src="<?php echo $staticroot ?>/javascript/tablesorter.js?v=112426"></script>
	<div class="cpmid">
	<?php
	echo '<span class="column" style="width:auto;">';
	echo "<a href=\"logingrid.php?cid=$cid\">" . _('View Login Grid') . "</a><br/>";
	echo "<a href=\"listusers.php?cid=$cid&assigncode=1\">" . _('Assign Sections and/or Codes') . "</a>";
	echo '</span>';
	echo '<span class="column" style="width:auto;">';
	echo "<a href=\"latepasses.php?cid=$cid\">" . _('Manage LatePasses') . "</a>";
	if ($CFG['GEN']['allowinstraddtutors']) {
		echo "<br/><a href=\"managetutors.php?cid=$cid\">" . _('Manage Tutors') . "</a>";
	}
    echo '</span>';
    if (empty($_COOKIE['fromltimenu'])) {
        echo '<span class="column" style="width:auto;">';
        if ($myrights==100 || (isset($CFG['GEN']['allowinstraddbyusername']) && $CFG['GEN']['allowinstraddbyusername']==true)) {
            echo "<a href=\"listusers.php?cid=$cid&enroll=student\">" . _('Enroll Student with known username') . "</a><br/>";
        }
        echo "<a href=\"enrollfromothercourse.php?cid=$cid\">" . _('Enroll from another course') . "</a>";
        if (isset($CFG['GEN']['allowinstraddstus']) && $CFG['GEN']['allowinstraddstus']==true) {
            echo '</span><span class="column" style="width:auto;">';
            echo "<a href=\"$imasroot/admin/importstu.php?cid=$cid\">" . _('Import Students from File') . "</a><br/>";
            echo "<a href=\"listusers.php?cid=$cid&newstu=new\">" . _('Create and Enroll new student') . "</a>";
        }
        echo '</span>';
    }
    echo '<br class="clear"/>';
	echo '</div>';
	echo '<p><label>'._('Pictures').': <select id="picsize" onchange="chgpicsize()">';
	echo "<option value=0 selected>", _('None'), "</option>";
	echo "<option value=1>", _('Small'), "</option>";
	echo "<option value=2>", _('Big'), "</option></select></label> ";
	echo '<label>'._('Email').': <select id="showemail" onchange="chgrmode()">';
	echo '<option value=1 '.($showemail==true?'selected':'').'>'._('Show').'</option>';
	echo '<option value=0 '.($showemail==true?'':'selected').'>'._('Hide').'</option>';
	echo '</select></label> ';
	echo '<label>'.Sanitize::encodeStringForDisplay($loginprompt).': <select id="showsid" onchange="chgrmode()">';
	echo '<option value=1 '.($showSID==true?'selected':'').'>'._('Show').'</option>';
	echo '<option value=0 '.($showSID==true?'':'selected').'>'._('Hide').'</option>';
	echo '</select></label> ';
	echo '</p>';
	?>
	<form id="qform" method=post action="listusers.php?cid=<?php echo $cid ?>">
	<?php
	echo _('Check:'), ' <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',true)">', _('All'), '</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',true,\'locked\')">', _('Non-locked'), '</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',false)">', _('None'), '</a> ';
	echo '<span class="dropdown">';
	echo ' <a tabindex=0 class="dropdown-toggle arrow-down" id="dropdownMenuWithsel" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
	echo _('With Selected').'</a>';
	echo '<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenuWithsel">';
	echo ' <li><a href="#" onclick="postWithSelform(\'Message\');return false;" title="',_("Send a message to the selected students"),'">', _('Message'), "</a></li>";
	if (!isset($CFG['GEN']['noEmailButton'])) {
		echo ' <li><a href="#" onclick="postWithSelform(\'E-mail\');return false;" title="',_("Send e-mail to the selected students"),'">', _('E-mail'), "</a></li>";
	}
	echo ' <li><a href="#" onclick="copyemails();return false;" title="',_("Copy e-mail addresses of the selected students"),'">', _('Copy E-mails'), "</a></li>";
	echo ' <li><a href="#" onclick="postWithSelform(\'Make Exception\');return false;" title="',_("Make due date exceptions for selected students"),'">',_('Make Exception'), "</a></li>";
	echo ' <li><a href="#" onclick="postWithSelform(\'Lock\');return false;" title="',_("Lock selected students out of the course"),'">', _('Lock'), "</a></li>";
	echo ' <li><a href="#" onclick="postWithSelform(\'Unlock\');return false;" title="',_("Un-Lock selected students from the course"),'">', _('Un-Lock'), "</a></li>";
	if (!isset($CFG['GEN']['allowinstraddtutors']) || $CFG['GEN']['allowinstraddtutors']==true) {
		echo ' <li><a href="#" onclick="postWithSelform(\'maketutor\');return false;" title="',_("Promote students to tutors"),'">', _('Add as Tutors'), "</a></li>";
	}

	if (!isset($CFG['GEN']['noInstrUnenroll'])) {
		echo '<li role="separator" class="divider"></li>';
		echo ' <li><a href="#" onclick="postWithSelform(\'Unenroll\');return false;" title="',_("Unenroll the selected students"),'">', _('Unenroll'), "</a></li>";
	}

	echo '</ul></span>';
	?>
		
	<table class=gb id=myTable>
    <caption class="sr-only"><?php echo _('Roster'); ?></caption>
		<thead>
		<tr>
			<th><span class="sr-only"><?php echo _('Checkboxes'); ?></span></th>
			<th><span class="sr-only"><?php echo _('Images'); ?></span></th>
			<?php echo $hasSectionRowHeader; ?>
			<?php echo $hasCodeRowHeader; ?>
			<th><?php echo _('Name'); ?></th>
			<th><span class="sr-only"><?php echo _('Notes'); ?></span></th>
			<?php
			if ($showSID) {
				echo '<th>'.Sanitize::encodeStringForDisplay($loginprompt).'</th>';
			}
			if ($showemail) {
				echo '<th>'._('Email').'</th>';
			}
			?>
			<th><?php echo _('Last Access'); ?> <span id="llt" class="sr-only"><?php echo _('View login log'); ?></span></th>
			<th id="gt"><?php echo _('Grades'); ?></th>
			<?php echo $hasLatePassHeader; ?>
		</tr>
		</thead>
		<tbody>
<?php
		$alt = 0;
		$numstu = 0;  $numunlocked = 0;
		$ln = 0;
		foreach ($defaultUserList as $line) {
			$ln++;
			if ($line['section']==null) {
				$line['section'] = '';
			}
			$line['email'] = str_replace('BOUNCED', '', $line['email']);
			$icons = '';
			$numstu++;
			if ($line['locked']>0) {
				$icons .= '<img src="'.$staticroot.'/img/lock.png" alt="'._('Locked').'" title="'._('Locked').'"/>';
			} else {
				$numunlocked++;
			}
			if ($line['timelimitmult']!=1 || $line['latepassmult']!=1) {
				$icons .= '<img src="'.$staticroot.'/img/time.png" alt="'._('Has a time limit or latepass multiplier set').'" title="'._('Has a time limit or latepass multiplier set').'"/> ';
			}
			if ($icons != '') {
				$icons = '<a href="listusers.php?cid='.$cid.'&chgstuinfo=true&uid='.Sanitize::onlyInt($line['userid']).'">'.$icons.'</a>';
			}

			$lastaccess = ($line['lastaccess']>0) ? tzdate("n/j/y g:ia",$line['lastaccess']) : _('never');

			$hasSectionData = ($hassection) ? "<td>".Sanitize::encodeStringForDisplay($line['section'])."</td>" : "";
			$hasCodeData = ($hascode) ? "<td>".Sanitize::encodeStringForDisplay($line['code'])."</td>" : "";
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>
				<td><input type=checkbox name="checked[]" id="userchk<?php echo Sanitize::onlyInt($line['userid']); ?>" 
					value="<?php echo Sanitize::onlyInt($line['userid']); ?>" <?php if ($line['locked']>0) echo 'class="locked"'?>></td>
				<td>
<?php

	if ($line['hasuserimg']==1) {
		if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
			echo "<img class=\"pii-image\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm" . Sanitize::onlyInt($line['userid']) . ".jpg\" style=\"display:none;\" alt=\"" . _('User picture') . "\" />";
		} else {
			echo "<img class=\"pii-image\" src=\"$imasroot/course/files/userimg_sm" . Sanitize::onlyInt($line['userid']) . ".jpg\" style=\"display:none;\" alt=\"" . _('User picture') . "\" />";
		}
	}
?>
				</td>
				<?php
				echo $hasSectionData;
				echo $hasCodeData;
		
				$nameline = Sanitize::encodeStringForDisplay($line['LastName']).', '.Sanitize::encodeStringForDisplay($line['FirstName']);
				//echo '<td><img data-uid="'. Sanitize::onlyInt($line['userid']) .'" src="'.$staticroot.'/img/gears.svg"/> ';
				echo '<th scope=row>';
				
				if ($line['locked']>0) {
					$lineclass = 'greystrike ';
				} else {
					$lineclass = '';
				}
				echo '<label for="userchk'. Sanitize::onlyInt($line['userid']) . '" class="' . $lineclass . 'pii-full-name" id="u'.$ln.'">';
				echo '<a data-uid="'. Sanitize::onlyInt($line['userid']).'">'.$nameline.'</a></label></th>';
				echo '<td>'.$icons.'</td>';
				if ($showSID) {
					echo '<td><span class="' . $lineclass . 'pii-username">'.Sanitize::encodeStringForDisplay($line['SID']).'</span></td>';
				}
				if ($showemail) {
					echo '<td><span class="' . $lineclass . '">'.Sanitize::emailAddress($line['email']).'</span></td>';
				}
				echo '<td><span class="' . $lineclass . '"><a href="viewloginlog.php?cid='.$cid.'&uid='.Sanitize::onlyInt($line['userid']).'" class="lal" id="la'.$ln.'" aria-labelledby="la'.$ln.' llt u'.$ln.'">'.$lastaccess.'</a></span></td>';

				?>

				<td><a href="gradebook.php?cid=<?php echo $cid ?>&stu=<?php echo Sanitize::onlyInt($line['userid']); ?>&from=listusers" class="gl" aria-labelledby="gt u<?php echo $ln;?>"><?php echo _('Grades'); ?></a></td>
				<?php
				if ($haslatepasses) {
					echo '<td>'.Sanitize::onlyInt($line['latepass']).'</td>';
				}
				?>
			</tr>
<?php
		}
?>

			</tbody>
		</table>
<?php
		echo "<p>"._('Number of students:')." <b>$numunlocked</b>";
		if ($numstu != $numunlocked) {
			echo " (" . sprintf(_('%d including locked students'), $numstu) . ")";
		}
		echo '</p>';
?>

        <?php
            $sortstr = 'false,false';
            if ($hassection) {
                $sortstr .= ',"S"';
            }
            if ($hascode) {
                $sortstr .= ',"S"';
            }
            $sortstr .= ',"S"';
            $sortstr .= ',false';
            if ($showSID) {
                $sortstr .= ',"S"';
            }
            if ($showemail) {
                $sortstr .= ',"S"';
            }
            $sortstr .= ',"D"';
            $sortstr .= ',false';
            if ($haslatepasses) {
                $sortstr .= ',"N"';
            }
        ?>
		<script type="text/javascript">
			initSortTable('myTable',Array(<?php echo $sortstr;?>),true);
		</script>
	</form>



	<p></p>
<?php
	}
}

require_once "../footer.php";
?>
