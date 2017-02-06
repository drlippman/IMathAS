<?php
//IMathAS:  Checks user's login - prompts if none.
//(c) 2006 David Lippman
 header('P3P: CP="ALL CUR ADM OUR"');

 $curdir = rtrim(dirname(__FILE__), '/\\');
 if (!file_exists("$curdir/config.php")) {
	 header('Location: http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/install.php");
 }
 require_once("$curdir/config.php");
 require("i18n/i18n.php");
 if (isset($sessionpath) && $sessionpath!='') { session_save_path($sessionpath);}
 ini_set('session.gc_maxlifetime',86400);
 ini_set('auto_detect_line_endings',true);

 $hostparts = explode('.',$_SERVER['HTTP_HOST']);
 if ($_SERVER['HTTP_HOST'] != 'localhost' && !is_numeric($hostparts[count($hostparts)-1])) {
 	 session_set_cookie_params(0, '/', '.'.implode('.',array_slice($hostparts,isset($CFG['GEN']['domainlevel'])?$CFG['GEN']['domainlevel']:-2)));
 }
 if (isset($CFG['GEN']['randfunc'])) {
 	 $randf = $CFG['GEN']['randfunc'];
 } else {
 	 $randf = 'rand';
 }

 session_start();
 $sessionid = session_id();
 if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
 	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
 }

 $myrights = 0;
 $myspecialrights = 0;
 $ispublic = false;
 //domain checks for special themes, etc. if desired
 $requestaddress = $_SERVER['HTTP_HOST'] .$_SERVER['PHP_SELF'];
 if (isset($CFG['CPS']['theme'])) {
 	 $defaultcoursetheme = $CFG['CPS']['theme'][0];
 } else if (!isset($defaultcoursetheme)) {
	 $defaultcoursetheme = "default.css";
 }
 $coursetheme = $defaultcoursetheme; //will be overwritten later if set
 if (!isset($CFG['CPS']['miniicons'])) {
	$CFG['CPS']['miniicons'] = array(
		 'assess'=>'assess_tiny.png',
		 'drill'=>'drill_tiny.png',
		 'inline'=>'inline_tiny.png',
		 'linked'=>'html_tiny.png',
		 'forum'=>'forum_tiny.png',
		 'wiki'=>'wiki_tiny.png',
		 'folder'=>'folder_tiny.png',
		 'calendar'=>'1day.png');
 }

 //check for bad sessionids.
 if (strlen($sessionid)<10) {
	 if (function_exists('session_regenerate_id')) { session_regenerate_id(); }
	echo "Error.  Please <a href=\"$imasroot/index.php\">try again</a>";
	exit;
 }
 $sessiondata = array();
 //DB $query = "SELECT * FROM imas_sessions WHERE sessionid='$sessionid'";
 //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
 //DB if (mysql_num_rows($result)>0) {
 $stm = $DBH->prepare("SELECT * FROM imas_sessions WHERE sessionid=:sessionid");
 $stm->execute(array(':sessionid'=>$sessionid));
 if ($stm->rowCount()>0) {
 	 //DB $line = mysql_fetch_assoc($result);
 	 $line = $stm->fetch(PDO::FETCH_ASSOC);
 	 $userid = $line['userid'];
 	 $tzoffset = $line['tzoffset'];
 	 $tzname = '';
 	 if (isset($line['tzname']) && $line['tzname']!='') {
 	 	if (date_default_timezone_set($line['tzname'])) {
 	 		$tzname = $line['tzname'];
 	 	}
 	 }
 	 $enc = $line['sessiondata'];
	 if ($enc!='0') {
		 $sessiondata = unserialize(base64_decode($enc));
		 //delete own session if old and not posting
		 if ((time()-$line['time'])>24*60*60 && (!isset($_POST) || count($_POST)==0)) {
			//DB $query = "DELETE FROM imas_sessions WHERE userid='$userid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_sessions WHERE userid=:userid");
			$stm->execute(array(':userid'=>$userid));
			unset($userid);
		 }
	 } else {
		 if (isset($_SERVER['QUERY_STRING'])) {
			 $querys = '?'.$_SERVER['QUERY_STRING'].(isset($addtoquerystring)?'&'.$addtoquerystring:'');
		 } else {
			 $querys = (isset($addtoquerystring)?'?'.$addtoquerystring:'');
		 }

		 $sessiondata['useragent'] = $_SERVER['HTTP_USER_AGENT'];
		 $sessiondata['ip'] = $_SERVER['REMOTE_ADDR'];
		 $sessiondata['mathdisp'] = $_POST['mathdisp'];
		 $sessiondata['graphdisp'] = $_POST['graphdisp'];
		 $sessiondata['useed'] = checkeditorok();
		 $sessiondata['secsalt'] = generaterandstring();
		 if (isset($_POST['savesettings'])) {
			 setcookie('mathgraphprefs',$_POST['mathdisp'].'-'.$_POST['graphdisp'],2000000000);
		 }
		 $enc = base64_encode(serialize($sessiondata));
		 //DB $query = "UPDATE imas_sessions SET sessiondata='$enc' WHERE sessionid='$sessionid'";
		 //DB mysql_query($query) or die("Query failed : " . mysql_error());
		 $stm = $DBH->prepare("UPDATE imas_sessions SET sessiondata=:sessiondata WHERE sessionid=:sessionid");
		 $stm->execute(array(':sessiondata'=>$enc, ':sessionid'=>$sessionid));

		// $now = time();
		//DB //$query = "INSERT INTO imas_log (time,log) VALUES ($now,'$userid from IP: {$_SERVER['REMOTE_ADDR']}')";
		//DB //mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (:now,:log)");
		$stm->execute(array(':now'=>$now, ':log'=>"$userid login from IP:{$_SERVER['REMOTE_ADDR']}"));


		 header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . $querys);
		 exit;
	 }

 }
 $hasusername = isset($userid);
 $haslogin = isset($_POST['password']);
 if (!$hasusername && !$haslogin && isset($_GET['guestaccess']) && isset($CFG['GEN']['guesttempaccts'])) {
 	 $haslogin = true;
 	 $_POST['username']='guest';
 	 $_POST['mathdisp'] = 0;
 	 $_POST['graphdisp'] = 2;
 }
 if (isset($_GET['checksess']) && !$hasusername) {
 	echo '<html><body>';
 	echo 'Unable to establish a session. This is most likely caused by your browser blocking third-party cookies.  Please adjust your browser settings and try again.';
 	echo '</body></html>';
 	exit;
 }
 $verified = false;  $err = '';
 //Just put in username and password, trying to log in
 if ($haslogin && !$hasusername) {
	  $now = time();
	  //clean up old sessions
	 //REMOVED since deleting old sessions can trigger login on LTI launches over 25 hours
	 // $old = $now - 25*60*60;
	 //DB $query = "DELETE FROM imas_sessions WHERE time<$old";
	 //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	 //$stm = $DBH->prepare("DELETE FROM imas_sessions WHERE time<:old");
	 //$stm->execute(array(':old'=>$old));
	 if (rand(1,100)==1) { //1% of the time clear out really old sessions
		 $reallyold = $now-30*24*60*60;
		 $stm = $DBH->prepare("DELETE FROM imas_sessions WHERE time<:time");
		 $stm->execute(array(':time'=>$reallyold));
	 }

	 if (isset($CFG['GEN']['guesttempaccts']) && $_POST['username']=='guest') { // create a temp account when someone logs in w/ username: guest
	 	//DB $query = 'SELECT ver FROM imas_dbschema WHERE id=2';
	 	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	 	//DB $guestcnt = mysql_result($result,0,0);
	 	$stm = $DBH->query('SELECT ver FROM imas_dbschema WHERE id=2');
	 	$guestcnt = $stm->fetchColumn(0);
	 	//DB $query = 'UPDATE imas_dbschema SET ver=ver+1 WHERE id=2';
	 	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	 	$stm = $DBH->query('UPDATE imas_dbschema SET ver=ver+1 WHERE id=2');

		if (isset($CFG['GEN']['homelayout'])) {
			$homelayout = $CFG['GEN']['homelayout'];
		} else {
			$homelayout = '|0,1,2||0,1';
		}
	 	//DB $query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify,homelayout) ";
	 	//DB $query .= "VALUES ('guestacct$guestcnt','',5,'Guest','Account','none@none.com',0,'$homelayout')";
	 	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	 	//DB $userid = mysql_insert_id();
	 	$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify,homelayout) ";
	 	$query .= "VALUES (:guestcnt,'',5,'Guest','Account','none@none.com',0,:homelayout)";
	 	$stm = $DBH->prepare($query);
	 	$stm->execute(array(':guestcnt'=>"guestacct$guestcnt", ':homelayout'=>$homelayout));
	 	$userid = $DBH->lastInsertId();

		//DB $query = "SELECT id FROM imas_courses WHERE (istemplate&8)=8 AND available<4";
		//DB if (isset($_GET['cid'])) { $query.= ' AND id='.intval($_GET['cid']); }
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
    $query = "SELECT id FROM imas_courses WHERE (istemplate&8)=8 AND available<4";
		if (isset($_GET['cid'])) { $query.= ' AND id=:id'; }
		$stm = $DBH->prepare($query);
    if (isset($_GET['cid'])) {
		    $stm->execute(array(':id'=>$_GET['cid']));
    } else {
      $stm->execute(array());
    }
		if ($stm->rowCount()>0) {
			$query = "INSERT INTO imas_students (userid,courseid) VALUES ";
			$i = 0;
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if ($i>0) { $query .= ',';}
				$query .= "($userid,{$row[0]})";  //INT's from DB - safe
				$i++;
			}
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
      $DBH->query($query);
		}

	 	$line['id'] = $userid;
	 	$line['rights'] = 5;
	 	$line['groupid'] = 0;
	 	$_POST['password'] = 'temp';
	 	if (isset($CFG['GEN']['newpasswords'])) {
	 		require_once("includes/password.php");
	 		$line['password'] =  password_hash('temp', PASSWORD_DEFAULT);
	 	} else {
	 		$line['password'] = md5('temp');
	 	}
	 	$_POST['usedetected'] = true;
	 } else {
		 //DB $query = "SELECT id,password,rights,groupid FROM imas_users WHERE SID = '{$_POST['username']}'";
		 //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		 //DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
		 $stm = $DBH->prepare("SELECT id,password,rights,groupid FROM imas_users WHERE SID=:SID");
		 $stm->execute(array(':SID'=>$_POST['username']));
		 $line = $stm->fetch(PDO::FETCH_ASSOC);
	 }
	// if (($line != null) && ($line['password'] == md5($_POST['password']))) {
	if (isset($CFG['GEN']['newpasswords'])) {
	 	require_once("includes/password.php");
	}
	if (($line != null) && (
	  ((!isset($CFG['GEN']['newpasswords']) || $CFG['GEN']['newpasswords']!='only') && ((md5($line['password'].$_SESSION['challenge']) == $_POST['password']) ||($line['password'] == md5($_POST['password']))))
	  || (isset($CFG['GEN']['newpasswords']) && password_verify($_POST['password'],$line['password']))	)) {
		 unset($_SESSION['challenge']); //challenge is used up - forget it.
		 $userid = $line['id'];
		 $groupid = $line['groupid'];
		 //for upgrades times:
		// if ($line['rights']<100) {
		//	 echo "The system is currently down for maintenence.  Please try again later.";
		//	 exit;
		// }
		 //
		 if ($line['rights']==0) {
			require("header.php");
			echo "You have not yet confirmed your registration.  You must respond to the email ";
			echo "that was sent to you by IMathAS.";
			require("footer.php");
			exit;
		 }

		 //$sessiondata['mathdisp'] = $_POST['mathdisp'];
		 //$sessiondata['graphdisp'] = $_POST['graphdisp'];
		 //$sessiondata['useed'] = $_POST['useed'];
		 $sessiondata['useragent'] = $_SERVER['HTTP_USER_AGENT'];
		 $sessiondata['ip'] = $_SERVER['REMOTE_ADDR'];
		 $sessiondata['secsalt'] = generaterandstring();
		 if ($_POST['access']==1) { //text-based
			 $sessiondata['mathdisp'] = $_POST['mathdisp']; //to allow for accessibility
			 $sessiondata['graphdisp'] = 0;
			 $sessiondata['useed'] = 0;
			 $enc = base64_encode(serialize($sessiondata));
		 } else if ($_POST['access']==2) { //img graphs
		 	 //deprecated
			 $sessiondata['mathdisp'] = 2-$_POST['mathdisp'];
			 $sessiondata['graphdisp'] = 2;
			 $sessiondata['useed'] = checkeditorok();
			 $enc = base64_encode(serialize($sessiondata));
		 } else if ($_POST['access']==4) { //img math
		 	 //deprecated
			 $sessiondata['mathdisp'] = 2;
			 $sessiondata['graphdisp'] = $_POST['graphdisp'];
			 $sessiondata['useed'] = checkeditorok();
			 $enc = base64_encode(serialize($sessiondata));
		 } else if ($_POST['access']==3) { //img all
			 $sessiondata['mathdisp'] = 2;
			 $sessiondata['graphdisp'] = 2;
			 $sessiondata['useed'] = checkeditorok();
			 $enc = base64_encode(serialize($sessiondata));
		 } else if ($_POST['access']==5) { //mathjax experimental
		 	 //deprecated, as mathjax is now default
		 	 $sessiondata['mathdisp'] = 1;
			 $sessiondata['graphdisp'] = $_POST['graphdisp'];
			 $sessiondata['useed'] = checkeditorok();
			 $enc = base64_encode(serialize($sessiondata));
		 } else if ($_POST['access']==6) { //katex experimental
		 	 $sessiondata['mathdisp'] = 6;
			 $sessiondata['graphdisp'] = $_POST['graphdisp'];
			 $sessiondata['useed'] = checkeditorok();
			 $enc = base64_encode(serialize($sessiondata));
		 } else if (!empty($_POST['isok'])) {
			 $sessiondata['mathdisp'] = 1;
			 $sessiondata['graphdisp'] = 1;
			 $sessiondata['useed'] = checkeditorok();
			 $enc = base64_encode(serialize($sessiondata));
		 } else {
		 	 $sessiondata['mathdisp'] = 2-$_POST['mathdisp'];
		 	 $sessiondata['graphdisp'] = $_POST['graphdisp'];
		 	 $sessiondata['useed'] = checkeditorok();
			 $enc = base64_encode(serialize($sessiondata));
		 }

		 if (!isset($_POST['tzoffset'])) {
			 $_POST['tzoffset'] = 0;
		 }
		 if (isset($_POST['tzname']) && strpos(basename($_SERVER['PHP_SELF']),'upgrade.php')===false) {
		 	 //DB $query = "INSERT INTO imas_sessions (sessionid,userid,time,tzoffset,tzname,sessiondata) VALUES ('$sessionid','$userid',$now,'{$_POST['tzoffset']}','{$_POST['tzname']}','$enc')";
       //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		 	 $stm = $DBH->prepare("INSERT INTO imas_sessions (sessionid,userid,time,tzoffset,tzname,sessiondata) VALUES (:sessionid, :userid, :time, :tzoffset, :tzname, :sessiondata)");
		 	 $stm->execute(array(':sessionid'=>$sessionid, ':userid'=>$userid, ':time'=>$now, ':tzoffset'=>$_POST['tzoffset'], ':tzname'=>$_POST['tzname'], ':sessiondata'=>$enc));
		 } else {
		 	 //DB $query = "INSERT INTO imas_sessions (sessionid,userid,time,tzoffset,sessiondata) VALUES ('$sessionid','$userid',$now,'{$_POST['tzoffset']}','$enc')";
       //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		 	 $stm = $DBH->prepare("INSERT INTO imas_sessions (sessionid,userid,time,tzoffset,sessiondata) VALUES (:sessionid, :userid, :time, :tzoffset, :sessiondata)");
		 	 $stm->execute(array(':sessionid'=>$sessionid, ':userid'=>$userid, ':time'=>$now, ':tzoffset'=>$_POST['tzoffset'], ':sessiondata'=>$enc));
		 }


		 if (isset($CFG['GEN']['newpasswords']) && strlen($line['password'])==32) { //old password - rehash it
		 	 $hashpw = password_hash($_POST['password'], PASSWORD_DEFAULT);
		 	 //DB $query = "UPDATE imas_users SET lastaccess=$now,password='$hashpw' WHERE id=$userid";
       //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		 	 $stm = $DBH->prepare("UPDATE imas_users SET lastaccess=:lastaccess,password=:password WHERE id=:id");
		 	 $stm->execute(array(':lastaccess'=>$now, ':password'=>$hashpw, ':id'=>$userid));
		 } else {
		 	 //DB $query = "UPDATE imas_users SET lastaccess=$now WHERE id=$userid";
       //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		 	 $stm = $DBH->prepare("UPDATE imas_users SET lastaccess=:lastaccess WHERE id=:id");
		 	 $stm->execute(array(':lastaccess'=>$now, ':id'=>$userid));
		 }


		 if (isset($_SERVER['QUERY_STRING'])) {
			 $querys = '?'.$_SERVER['QUERY_STRING'].(isset($addtoquerystring)?'&'.$addtoquerystring:'');
		 } else {
			 $querys = (isset($addtoquerystring)?'?'.$addtoquerystring:'');
		 }
		 //$now = time();
		 //DB //$query = "INSERT INTO imas_log (time,log) VALUES ($now,'$userid from IP: {$_SERVER['REMOTE_ADDR']}')";
		 //DB //mysql_query($query) or die("Query failed : " . mysql_error());

		 header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . $querys);
	 } else {
		 if (empty($_SESSION['challenge'])) {
			 $badsession = true;
		 } else {
		 	 $badsession = false;
		 }
		 /*  For login error tracking - requires add'l table
		 if ($line==null) {
			 $err = "Bad SID";
		 } else if ($_SESSION['challenge']!=$_POST['challenge']) {
			 $err = "Bad Challenge (post:{$_POST['challenge']}, sess: ".addslashes($_SESSION['challenge']).")";
		 } else {
			 $err = "Bad PW";
		 }
		 $err .= ','.addslashes($_SERVER['HTTP_USER_AGENT']);
		 $query = "INSERT INTO imas_failures (SID,challenge,sent,type) VALUES ";
		 $query .= "('{$_POST['username']}','{$_SESSION['challenge']}','{$_POST['password']}','$err')";
		 mysql_query($query) or die("Query failed : " . mysql_error());
		 */

	 }

 }
 //has logged in already
 if ($hasusername) {
	//check validity, if desired
	if (($sessiondata['useragent'] != $_SERVER['HTTP_USER_AGENT']) || ($sessiondata['ip'] != $_SERVER['REMOTE_ADDR'])) {
		//suggests sidejacking.  Delete session and require relogin
		/*
		$query = "DELETE FROM imas_sessions WHERE userid='$userid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . $querys);
		exit;
		*/
	}
	//$username = $_COOKIE['username'];
	$query = "SELECT SID,rights,groupid,LastName,FirstName,deflib";
	if (strpos(basename($_SERVER['PHP_SELF']),'upgrade.php')===false) {
		$query .= ',listperpage,hasuserimg,theme,specialrights,FCMtoken';
	}
	//DB $query .= " FROM imas_users WHERE id='$userid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
  $query .= " FROM imas_users WHERE id=:id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':id'=>$userid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	$username = $line['SID'];
	$myrights = $line['rights'];
	$myspecialrights = $line['specialrights'];
	$groupid = $line['groupid'];
	$userdeflib = $line['deflib'];
	$listperpage = $line['listperpage'];
	$selfhasuserimg = $line['hasuserimg'];
	$usertheme = $line['theme'];
	$FCMtoken = $line['FCMtoken'];
	if (isset($usertheme) && $usertheme!='') {
		$coursetheme = $usertheme;
	}
	$userfullname = $line['FirstName'] . ' ' . $line['LastName'];
	$previewshift = -1;
	$basephysicaldir = rtrim(dirname(__FILE__), '/\\');
	if ($myrights==100 && (isset($_GET['debug']) || isset($sessiondata['debugmode']))) {
		ini_set('display_errors',1);
		error_reporting(E_ALL ^ E_NOTICE);
		if (isset($_GET['debug'])) {
			$sessiondata['debugmode'] = true;
			writesessiondata();
		}
	}
	if (isset($_GET['fullwidth'])) {
		$sessiondata['usefullwidth'] = true;
		$usefullwidth = true;
		writesessiondata();
	} else if (isset($sessiondata['usefullwidth'])) {
		$usefullwidth = true;
	}

	if (isset($_GET['mathjax'])) {
		$sessiondata['mathdisp'] = 1;
		writesessiondata();
	}

	if (isset($_GET['readernavon'])) {
		$sessiondata['readernavon'] = true;
		writesessiondata();
	}
	if (isset($_GET['useflash'])) {
		$sessiondata['useflash'] = true;
		writesessiondata();
	}
	if (isset($_GET['graphdisp'])) {
		$sessiondata['graphdisp'] = $_GET['graphdisp'];
		writesessiondata();
	}
	if (isset($sessiondata['isdiag']) && strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false) {
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php");
	}
	if (isset($sessiondata['ltiitemtype'])) {
		$flexwidth = true;
		if ($sessiondata['ltiitemtype']==1) {
			if (strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false && isset($_GET['cid']) && $sessiondata['ltiitemid']!=$_GET['cid']) {
				echo "You do not have access to this page";
				echo "<a href=\"$imasroot/course/course.php?cid={$sessiondata['ltiitemid']}\">Return to course page</a>";
				exit;
			}
		} else if ($sessiondata['ltiitemtype']==0 && $sessiondata['ltirole']=='learner') {
			$breadcrumbbase = "<a href=\"$imasroot/assessment/showtest.php?cid={$_GET['cid']}&id={$sessiondata['ltiitemid']}\">Assignment</a> &gt; ";
			$urlparts = parse_url($_SERVER['PHP_SELF']);
			if (!in_array(basename($urlparts['path']),array('showtest.php','printtest.php','msglist.php','sentlist.php','viewmsg.php','msghistory.php','redeemlatepass.php','gb-viewasid.php','showsoln.php'))) {
			//if (strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false && strpos(basename($_SERVER['PHP_SELF']),'printtest.php')===false && strpos(basename($_SERVER['PHP_SELF']),'msglist.php')===false && strpos(basename($_SERVER['PHP_SELF']),'sentlist.php')===false && strpos(basename($_SERVER['PHP_SELF']),'viewmsg.php')===false ) {
				//DB $query = "SELECT courseid FROM imas_assessments WHERE id='{$sessiondata['ltiitemid']}'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $cid = mysql_result($result,0,0);
				$stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=:id");
				$stm->execute(array(':id'=>$sessiondata['ltiitemid']));
				$cid = $stm->fetchColumn(0);
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}");
				exit;
			}
		} else if ($sessiondata['ltirole']=='instructor') {
			$breadcrumbbase = "<a href=\"$imasroot/ltihome.php?showhome=true\">LTI Home</a> &gt; ";
		} else {
			$breadcrumbbase = '';
		}
	} else {
		$breadcrumbbase = "<a href=\"$imasroot/index.php\">Home</a> &gt; ";
	}

	if ((isset($_GET['cid']) && $_GET['cid']!="admin" && $_GET['cid']>0) || (isset($sessiondata['courseid']) && strpos(basename($_SERVER['PHP_SELF']),'showtest.php')!==false)) {
		if (isset($_GET['cid'])) {
			$cid = $_GET['cid'];
		} else {
			$cid = $sessiondata['courseid'];
		}
		//DB $query = "SELECT id,locked,timelimitmult,section,latepass FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
		$stm = $DBH->prepare("SELECT id,locked,timelimitmult,section,latepass,lastaccess FROM imas_students WHERE userid=:userid AND courseid=:courseid");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		if ($line != null) {
			$studentid = $line['id'];
			$studentinfo['timelimitmult'] = $line['timelimitmult'];
			$studentinfo['section'] = $line['section'];
			$studentinfo['latepasses'] = $line['latepass'];
			if ($line['locked']>0) {
				require("header.php");
				echo "<p>You have been locked out of this course by your instructor.  Please see your instructor for more information.</p>";
				echo "<p><a href=\"$imasroot/index.php\">Home</a></p>";
				require("footer.php");
				exit;
			} else {
				$now = time();
				if (!isset($sessiondata['lastaccess'.$cid]) || $now-$sessiondata['lastaccess'.$cid] > 24*3600) {
					//DB $query = "UPDATE imas_students SET lastaccess='$now' WHERE id=$studentid";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_students SET lastaccess=:lastaccess WHERE id=:id");
					$stm->execute(array(':lastaccess'=>$now, ':id'=>$studentid));
					$sessiondata['lastaccess'.$cid] = $now;
					//DB $query = "INSERT INTO imas_login_log (userid,courseid,logintime) VALUES ($userid,'$cid',$now)";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					//DB $sessiondata['loginlog'.$cid] = mysql_insert_id();
					$stm = $DBH->prepare("INSERT INTO imas_login_log (userid,courseid,logintime) VALUES (:userid, :courseid, :logintime)");
					$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':logintime'=>$now));
					$sessiondata['loginlog'.$cid] = $DBH->lastInsertId();
					writesessiondata();
				} else if (isset($CFG['GEN']['keeplastactionlog'])) {
					//DB $query = "UPDATE imas_login_log SET lastaction=$now WHERE id=".$sessiondata['loginlog'.$cid];
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_login_log SET lastaction=:lastaction WHERE id=:id");
					$stm->execute(array(':lastaction'=>$now, ':id'=>$sessiondata['loginlog'.$cid]));
				}
			}
		} else {
			//DB $query = "SELECT id FROM imas_teachers WHERE userid='$userid' AND courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
			$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			if ($line != null) {
				if ($myrights>19) {
					$teacherid = $line['id'];
					if (isset($_GET['stuview'])) {
						$sessiondata['stuview'] = $_GET['stuview'];
						writesessiondata();
					}
					if (isset($_GET['teachview'])) {
						unset($sessiondata['stuview']);
						writesessiondata();
					}
					if (isset($sessiondata['stuview'])) {
						$previewshift = $sessiondata['stuview'];
						unset($teacherid);
						$studentid = $line['id'];
					}
				} else {
					$tutorid = $line['id'];
				}
			} else if ($myrights==100) {
				$teacherid = $userid;
				$adminasteacher = true;
			} else {

				//DB $query = "SELECT id,section FROM imas_tutors WHERE userid='$userid' AND courseid='$cid'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
				$stm = $DBH->prepare("SELECT id,section FROM imas_tutors WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
				$line = $stm->fetch(PDO::FETCH_ASSOC);
				if ($line != null) {
					$tutorid = $line['id'];
					$tutorsection = trim($line['section']);
				}

			}
		}
		$query = "SELECT imas_courses.name,imas_courses.available,imas_courses.lockaid,imas_courses.copyrights,imas_users.groupid,imas_courses.theme,imas_courses.newflag,imas_courses.msgset,imas_courses.topbar,imas_courses.toolset,imas_courses.deftime,imas_courses.picicons,imas_courses.latepasshrs ";
		$query .= "FROM imas_courses JOIN imas_users ON imas_users.id=imas_courses.ownerid WHERE imas_courses.id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$cid));
		if ($stm->rowCount()>0) {
			//DB $crow = mysql_fetch_row($result);
      //TODO: change to assoc
			$crow = $stm->fetch(PDO::FETCH_NUM);
			$coursename = $crow[0]; //mysql_result($result,0,0);
			$coursetheme = $crow[5]; //mysql_result($result,0,5);
			if (isset($usertheme) && $usertheme!='') {
				$coursetheme = $usertheme;
			}
			$coursenewflag = $crow[6]; //mysql_result($result,0,6);
			$coursemsgset = $crow[7]%5;
			$coursetopbar = explode('|',$crow[8]);
			$coursetopbar[0] = explode(',',$coursetopbar[0]);
			$coursetopbar[1] = explode(',',$coursetopbar[1]);
			$coursetoolset = $crow[9];
			$coursedeftime = $crow[10]%10000;
			if ($crow[10]>10000) {
				$coursedefstime = floor($crow[10]/10000);
			} else {
				$coursedefstime = $coursedeftime;
			}
			$picicons = $crow[11];
			$latepasshrs = $crow[12];
			if (!isset($coursetopbar[2])) { $coursetopbar[2] = 0;}
			if ($coursetopbar[0][0] == null) {unset($coursetopbar[0][0]);}
			if ($coursetopbar[1][0] == null) {unset($coursetopbar[1][0]);}
			if (isset($studentid) && $previewshift==-1 && (($crow[1])&1)==1) {
				echo "This course is not available at this time";
				exit;
			}
			$lockaid = $crow[2]; //ysql_result($result,0,2);
			if (isset($studentid) && $lockaid>0) {
				if (strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false) {
					require("header.php");
					echo '<p>This course is currently locked for an assessment</p>';
					echo "<p><a href=\"$imasroot/assessment/showtest.php?cid=$cid&id=$lockaid\">Go to Assessment</a> | <a href=\"$imasroot/index.php\">Go Back</a></p>";
					require("footer.php");
					//header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id=$lockaid");
					exit;
				}
			}
			unset($lockaid);
			if ($myrights==75 && !isset($teacherid) && !isset($studentid) && $crow[4]==$groupid) {
				//group admin access
				$teacherid = $userid;
				$adminasteacher = true;
			} else if ($myrights>19 && !isset($teacherid) && !isset($studentid) && !isset($tutorid) && $previewshift==-1) {
				if ($crow[3]==2) {
					$guestid = $userid;
				} else if ($crow[3]==1 && $crow[4]==$groupid) {
					$guestid = $userid;
				}
			}
		}
	}
	$verified = true;

 }

 if (!$verified) {
	if (!isset($skiploginredirect) && strpos(basename($_SERVER['SCRIPT_NAME']),'directaccess.php')===false) {
		if (!isset($loginpage)) {
			 $loginpage = "loginpage.php";
		}
		require($loginpage);
		exit;
	}
 }

 function tzdate($string,$time) {
	  global $tzoffset, $tzname;
	  //$dstoffset = date('I',time()) - date('I',$time);
	  //return gmdate($string, $time-60*($tzoffset+60*$dstoffset));
	  if ($tzname != '') {
	  	  return date($string, $time);
	  } else {
		  $serveroffset = date('Z') + $tzoffset*60;
		  return date($string, $time-$serveroffset);
	  }
	  //return gmdate($string, $time-60*$tzoffset);
  }

  function writesessiondata() {
	  global $DBH,$sessiondata,$sessionid;
	  $enc = base64_encode(serialize($sessiondata));
	  $now = time();
	  //DB $query = "UPDATE imas_sessions SET sessiondata='$enc' WHERE sessionid='$sessionid'";
	  //DB mysql_query($query) or die("Query failed : " . mysql_error());
	  $stm = $DBH->prepare("UPDATE imas_sessions SET sessiondata=:sessiondata,time=:time WHERE sessionid=:sessionid");
	  $stm->execute(array(':sessiondata'=>$enc, ':time'=>$now, ':sessionid'=>$sessionid));
  }
  function checkeditorok() {
	  $ua = $_SERVER['HTTP_USER_AGENT'];
	  if (strpos($ua,'iPhone')!==false || strpos($ua,'iPad')!==false) {
	  	  preg_match('/OS (\d+)_(\d+)/',$ua,$match);
	  	  if ($match[1]>=5) {
	  	  	  return 1;
	  	  } else {
	  	  	  return 0;
	  	  }
	  } else if (strpos($ua,'Android')!==false) {
	  	  preg_match('/Android\s+(\d+)((?:\.\d+)+)\b/',$ua,$match);
	  	  if ($match[1]>=4) {
	  	  	  return 1;
	  	  } else {
	  	  	  return 0;
	  	  }
	  } else {
		  return 1;
	  }
  }
  //DB function stripslashes_deep($value) {
	//DB   return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
  //DB }
  if (!isset($coursename)) {
	  $coursename = "Course Page";
  }
  function generaterandstring() {
  	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$pass = '';
	for ($i=0;$i<10;$i++) {
		$pass .= substr($chars,rand(0,61),1);
	}
	return $pass;
  }
?>
