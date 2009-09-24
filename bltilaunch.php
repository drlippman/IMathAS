<?php
//IMathAS:  BasicLTI Producer Code
//(c) David Lippman 2009
//based on demo code only - not on specs.  No guarantee of compliance with specs
//launches with three types of keys
//   aid_###     : launches assessment with given id.  secret is ltisecret
//   cid_###     : launches course with given id.  secret is ltisecret
//   sso_userid  : launches single signon using given userid w/ rights 11. 
//                 secret value stored in DB password field.  Current must be manually editted in DB
//   all accept additional _0 or _1  :  0 is default, and links LMS account with a local account
//                                      1 using LMS for validation, does not ask for local account info
//  LMS MUST provide, in addition to key and secret:
//    user_id
//    tool_consumer_instance_guid  (LMS domain name)
//  LMS MAY provide:
//    lis_person_name_first
//    lis_person_name_last
//    lis_person_contact_email_primary

include("config.php");
if ($enablebasiclti!=true) {
	echo "BasicLTI not enabled";
	exit;
}

function reporterror($err) {
	require("header.php");
	echo "<p>$err</p>";
	require("footer.php");
	exit;
}

//start session
if (isset($sessionpath)) { session_save_path($sessionpath);}
ini_set('session.gc_maxlifetime',86400);
ini_set('auto_detect_line_endings',true);
session_start();
$sessionid = session_id();

$askforuserinfo = false;

//check to see if accessiblity page is posting back
if (isset($_GET['launch'])) {
	$query = "SELECT sessiondata,userid FROM imas_sessions WHERE sessionid='$sessionid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)==0) {
		reporterror("No authorized session exists");
	}
	list($enc,$userid) = mysql_fetch_row($result);
	$sessiondata = unserialize(base64_decode($enc));
	if ($_POST['access']==1) { //text-based
		 $sessiondata['mathdisp'] = $_POST['mathdisp'];
		 $sessiondata['graphdisp'] = 0;
		 $sessiondata['useed'] = 0; 
	 } else if ($_POST['access']==2) { //img graphs
		 $sessiondata['mathdisp'] = 2-$_POST['mathdisp'];
		 $sessiondata['graphdisp'] = 2;
		 $sessiondata['useed'] = 1; 
	 } else if ($_POST['access']==4) { //img math
		 $sessiondata['mathdisp'] = 2;
		 $sessiondata['graphdisp'] = $_POST['graphdisp'];
		 $sessiondata['useed'] = 1; 
	 } else if ($_POST['access']==3) { //img all
		 $sessiondata['mathdisp'] = 2;  
		 $sessiondata['graphdisp'] = 2;
		 $sessiondata['useed'] = 1; 
	 } else {
		 $sessiondata['mathdisp'] = 2-$_POST['mathdisp']; 
		 $sessiondata['graphdisp'] = $_POST['graphdisp'];
		 $sessiondata['useed'] = 1; 
	 }
	
	$enc = base64_encode(serialize($sessiondata));
	
	$now = time();
	$query = "UPDATE imas_users SET lastaccess='$now' WHERE id='$userid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$query = "UPDATE imas_sessions SET sessiondata='$enc',tzoffset='{$_POST['tzoffset']}'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$keyparts = explode('_',$_SESSION['ltikey']);
	if ($keyparts[0]=='aid') { //is aid
		$aid = intval($keyparts[1]);
		$query = "SELECT courseid FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$cid = mysql_result($result,0,0);
		header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id=$aid");
	} else if ($keyparts[0]=='cid') { //is cid
		$cid = intval($keyparts[1]);
		header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
	} else if ($keyparts[0]=='sso') {
		header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/index.php");
	} else {
		reporterror("Invalid action");
	}
	exit;	
} else if (isset($_GET['accessibility'])) {
	//time to output a postback to capture tzoffset and math/graph settings
	$pref = 0;
	if (isset($_COOKIE['mathgraphprefs'])) {
		 $prefparts = explode('-',$_COOKIE['mathgraphprefs']);
		 if ($prefparts[0]==2 && $prefparts[1]==2) { //img all
			$pref = 3;	 
		 } else if ($prefparts[0]==2) { //img math
			 $pref = 4;
		 } else if ($prefparts[1]==2) { //img graph
			 $pref = 2;
		 }	 
	}
	$nologo = true;
	require("header.php");
	echo "<h4>Logging in to $installname</h4>";
	echo "<form method=\"post\" action=\"{$_SERVER['PHP_SELF']}?launch=true\" ";
	if ($itemtype==0 && $tlwrds != '') {
		echo "onsubmit='return confirm(\"This assessment has a time limit of $tlwrds.  Click OK to start or continue working on the assessment.\")' ";
	}
	echo ">";
	?>
	<div id="settings"><noscript>JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.  
	Please enable JavaScript and reload this page</noscript></div>
	<input type="hidden" id="tzoffset" name="tzoffset" value="" /> 
	<script type="text/javascript"> 
		 function updateloginarea() {
			setnode = document.getElementById("settings"); 
			var html = ""; 
			html += 'Accessibility: ';
			html += "<a href='#' onClick=\"window.open('<?php echo $imasroot;?>/help.php?section=loggingin','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\">Help<\/a>";
			html += '<br/><input type="radio" name="access" value="0" <?php if ($pref==0) {echo "checked=1";} ?> />Detect my settings<br/>';
			html += '<input type="radio" name="access" value="2" <?php if ($pref==2) {echo "checked=1";} ?> />Force image-based graphs<br/>';
			html += '<input type="radio" name="access" value="4" <?php if ($pref==4) {echo "checked=1";} ?> />Force image-based math<br/>';
			html += '<input type="radio" name="access" value="3" <?php if ($pref==3) {echo "checked=1";} ?> />Force image based display<br/>';
			html += '<input type="radio" name="access" value="1">Use text-based display';
			
			if (AMnoMathML) {
				html += '<input type="hidden" name="mathdisp" value="0" />';
			} else {
				html += '<input type="hidden" name="mathdisp" value="1" />';
			}
			if (ASnoSVG) {
				html += '<input type="hidden" name="graphdisp" value="2" />';
			} else {
				html += '<input type="hidden" name="graphdisp" value="1" />';
			}
			html += '<div class="textright"><input type="submit" value="Login" /><\/div>';
			setnode.innerHTML = html; 
			var thedate = new Date();  
			document.getElementById("tzoffset").value = thedate.getTimezoneOffset(); 
		}
		var existingonload = window.onload;
		if (existingonload) {
			window.onload = function() {existingonload(); updateloginarea();}
		} else {
			window.onload = updateloginarea;
		}
	</script>
	</form>
	<?php
	require("footer.php");
	exit;	
	
} else if (isset($_GET['userinfo']) && isset($_SESSION['ltiuserid'])) {
	//check to see if new LTI user is posting back user info
	$ltiuserid = $_SESSION['ltiuserid'];
	$ltiorg = $_SESSION['ltiorg'];
	$keyparts = explode('_',$_SESSION['ltikey']);
	if (count($keyparts)<3) {
		$lti_only = false;
	} else {
		if ($keyparts[2]==1) {
			$lti_only = true;
		} else {
			$lti_only = false;
		}
	}
	if ($_GET['userinfo']=='set') {	
		//check input
		$infoerr = '';
		unset($userid);
		if ($lti_only) {
			if (empty($_POST['firstname']) || empty($_POST['lastname'])) {
				$infoerr = 'Please provide your name';
			}
			$_POST['email'] = 'none@none.com';
			$msgnot = 0;
		} else {
			if (!empty($_POST['curSID']) && !empty($_POST['curPW'])) {
				//provided current SID/PW pair
				$md5pw = md5($_POST['curPW']);
				$query = "SELECT password,id FROM imas_users WHERE SID='{$_POST['curSID']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_result($result,0,0)==$md5pw) {
					$userid=mysql_result($result,0,1);
				} else {
					$infoerr = 'Existing username/password provided are not valid.';
				}
			} else {
				//new info
				if (empty($_POST['SID']) || empty($_POST['pw1']) || empty($_POST['pw2']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['email'])) {
					$infoerr = 'Be sure to leave no requested information empty';
				} else if ($_POST['pw1'] != $_POST['pw2']) {
					$infoerr = 'Passwords don\'t match';
				} else if ($loginformat!='' && !preg_match($loginformat,$_POST['SID'])) {
					$infoerr = "$loginprompt is invalid";
				} else if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/',$_POST['email'])) {
					$infoerr = 'Invalid email address';
				} else {
					$query = "SELECT id FROM imas_users WHERE SID='{$_POST['SID']}'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($result)>0) {
						$infoerr = "$loginprompt '{$_POST['SID']}' already used.  Please select another.";
					}
				}
				if (isset($_POST['msgnot'])) {
					$msgnot = 1;
				} else {
					$msgnot = 0;
				}
				$md5pw = md5($_POST['pw1']);
			}
		}
		if ($infoerr=='') { // no error, so create!
			$query = "INSERT INTO imas_ltiusers (org,ltiuserid) VALUES ('$ltiorg','$ltiuserid')";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$localltiuser = mysql_insert_id();	
			if (!isset($userid)) {	
				if ($lti_only) {
					//make up a username/password for them
					$_POST['SID'] = 'lti-'.$localltiuser;
					$md5pw = 'pass'; //totally unusable since not md5'ed
				}
				$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify) VALUES ";
				$query .= "('{$_POST['SID']}','$md5pw',10,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}',$msgnot)";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$userid = mysql_insert_id();	
			}
			$query = "UPDATE imas_ltiusers SET userid='$userid' WHERE id='$localltiuser'";
			mysql_query($query) or die("Query failed : " . mysql_error());	
		} else {
			//uh-oh, had an error.  Better ask for user info again
			$askforuserinfo = true;
		}
	} else {
		//ask for student info
		$nologo = true;
		require("header.php");
		if (isset($infoerr)) {
			echo '<p style="color:red">'.$infoerr.'</p>';
		}
		echo "<form method=\"post\" action=\"{$_SERVER['PHP_SELF']}?userinfo=set\" ";	
		if ($lti_only) { 
			//using LTI for authentication; don't need username/password
			//only request name
			echo "<p>Please provide a little information about yourself:</p>";
			echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text size=20 id=firstnam name=firstname><BR class=form>\n";
			echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname><BR class=form>\n";
			echo "<div class=submit><input type=submit value='Submit'></div>\n";
			
		} else {
			$deffirst = '';
			$deflast = '';
			$defemail = '';
			if (isset($_SESSION['LMSfirstname'])) {
				$deffirst = $_SESSION['LMSfirstname'];
			}
			if (isset($_SESSION['LMSlastname'])) {
				$deflast = $_SESSION['LMSlastname'];
			}
			if (isset($_SESSION['LMSemail'])) {
				$defemail = $_SESSION['LMSemail'];
			}
			
			if (isset($_SESSION['ltiorgname'])) {
				$ltiorgname = $_SESSION['ltiorgname'];
			} else {
				$ltiorgname = $ltiorg;
			}
			
			//tying LTI to IMAthAS account
			//give option to provide existing account info, or provide full new student info
			echo "<p>If you already have an account on $installname, enter your username and ";
			echo "password below to enable automated signon from $ltiorgname</p>";
			echo "<span class=form><label for=\"curSID\">$loginprompt:</label></span> <input class=form type=text size=12 id=\"curSID\" name=\"curSID\"><BR class=form>\n";
			echo "<span class=form><label for=\"curPW\">Password:</label></span><input class=form type=password size=20 id=\"curPW\" name=\"curPW\"><BR class=form>\n";
			echo "<div class=submit><input type=submit value='Sign In'></div>\n";
			echo "<p>If you do not already have an account on $installname, provide the information below to create an account ";
			echo "and enable automated signon from $ltiorgname</p>";
			echo "<span class=form><label for=\"SID\">$longloginprompt:</label></span> <input class=form type=text size=12 id=SID name=SID><BR class=form>\n";
			echo "<span class=form><label for=\"pw1\">Choose a password:</label></span><input class=form type=password size=20 id=pw1 name=pw1><BR class=form>\n";
			echo "<span class=form><label for=\"pw2\">Confirm password:</label></span> <input class=form type=password size=20 id=pw2 name=pw2><BR class=form>\n";
			echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text value=\"$deffirst\" size=20 id=firstnam name=firstname><BR class=form>\n";
			echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text value=\"$deflast\" size=20 id=lastname name=lastname><BR class=form>\n";
			echo "<span class=form><label for=\"email\">Enter E-mail address:</label></span>  <input class=form type=text value=\"$defemail\" size=60 id=email name=email><BR class=form>\n";
			echo "<span class=form><label for=\"msgnot\">Notify me by email when I receive a new message:</label></span><input class=floatleft type=checkbox id=msgnot name=msgnot /><BR class=form>\n";
			echo "<div class=submit><input type=submit value='Create Account'></div>\n";
		}
		echo "</form>\n";
		require("footer.php");
		exit;	
			
	}
	
} else if (isset($_SESSION['ltiuserid']) && !isset($_POST['user_id'])) {
	//refreshed this page from accessibility options page so session already exists
	// (if user_id is set, then is new LTI request, so want to pass down to OAuth)
	//pull necessary info and continue
	$query = "SELECT userid FROM imas_sessions WHERE sessionid='$sessionid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)==0) {
		reporterror("No session recorded");
	} else {
		$userid = mysql_result($result,0,0);
	}
	
	$keyparts = explode('_',$_SESSION['ltikey']);
} else {
	//not postback of new LTI user info, so must be fresh request

	//verify necessary POST values for LTI.  OAuth specific will be checked later
	if (empty($_REQUEST['user_id'])) {
		reporterror("user_id is required");
	} else {
		$ltiuserid = $_REQUEST['user_id'];
	}
	if (empty($_REQUEST['roles'])) {
		reporterror("roles is required");
	} else {
		$ltirole = $_REQUEST['roles'];
	}
	if (empty($_REQUEST['tool_consumer_instance_guid'])) {
		reporterror("tool_consumer_instance_guid (LMS domain name) is required");
	} else {
		$ltiorg = $_REQUEST['tool_consumer_instance_guid'];
	}
	if (empty($_REQUEST['oauth_consumer_key'])) {
		reporterror("oauth_consumer_key (resource key) is required");
	} else {
		$ltikey = $_REQUEST['oauth_consumer_key'];
	}
	
	//check OAuth Signature!
	require_once 'includes/OAuth.php';
	require_once 'includes/ltioauthstore.php';
	
	//set up OAuth
	$store = new IMathASLTIOAuthDataStore();
	$server = new OAuthServer($store);
	$method = new OAuthSignatureMethod_HMAC_SHA1();
	$server->add_signature_method($method);
	$request = OAuthRequest::from_request();
	$base = $request->get_signature_base_string();
	try {
		$server->verify_request($request);
	} catch (Exception $e) {
		reporterror($e->getMessage());	
	}
	$store->mark_nonce_used($request);
	
	$keyparts = explode('_',$ltikey);
	
	//look if we know this student
	$query = "SELECT userid FROM imas_ltiusers WHERE org='$ltiorg' AND ltiuserid='$ltiuserid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result) > 0) { //yup, we know them
		$userid = mysql_result($result,0,0);
	} else {
		//student is not known.  Bummer.  Better figure out what to do with them :)
		
		//Store all LTI request data in session variable for reuse on submit
		//if we got this far, secret has already been verified
		$_SESSION['ltiuserid'] = $ltiuserid;
		$_SESSION['ltiorg'] = $ltiorg;
		
		//if doing lti_only, and first/last name were provided, go ahead and use them and don't ask
		if (count($keyparts)>2 && $keyparts[2]==1 && ((!empty($_REQUEST['lis_person_name_first']) && !empty($_REQUEST['lis_person_name_last'])) || !empty($_REQUEST['lis_person_name_full'])) ) {
			if (!empty($_REQUEST['lis_person_name_first']) && !empty($_REQUEST['lis_person_name_last'])) {
				$firstname = $_REQUEST['lis_person_name_first'];
				$lastname = $_REQUEST['lis_person_name_last'];
			} else {
				$firstname = '';
				$lastname = $_REQUEST['lis_person_name_full'];
			}
			if (!empty($_REQUEST['lis_person_contact_email_primary'])) {
				$email = $_REQUEST['lis_person_contact_email_primary'];
			} else {
				$email = 'none@none.com';
			}
			
			$query = "INSERT INTO imas_ltiusers (org,ltiuserid) VALUES ('$ltiorg','$ltiuserid')";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$localltiuser = mysql_insert_id();	
			if (!isset($userid)) {	
				//make up a username/password for them
				$_POST['SID'] = 'lti-'.$localltiuser;
				$md5pw = 'pass'; //totally unusable since not md5'ed
				$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify) VALUES ";
				$query .= "('{$_POST['SID']}','$md5pw',10,'$firstname','$lastname','$email',0)";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$userid = mysql_insert_id();	
			}
			$query = "UPDATE imas_ltiusers SET userid='$userid' WHERE id='$localltiuser'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		} else {
			////create form asking them for user info
			$askforuserinfo = true;
			$_SESSION['LMSfirstname'] = $_REQUEST['lis_person_name_first'];
			$_SESSION['LMSlastname'] = $_REQUEST['lis_person_name_last'];
			if (!empty($_REQUEST['lis_person_contact_email_primary'])) {
				$_SESSION['LMSemail'] = $_REQUEST['lis_person_contact_email_primary'];
			} 
		}
	}
	$_SESSION['ltikey'] = $ltikey;
}

if (count($keyparts)<3) {
	$lti_only = false;
} else {
	if ($keyparts[2]==1) {
		$lti_only = true;
	} else {
		$lti_only = false;
	}
}

//Do we need to ask for student's info?
//either first connect or bad info on first submit
if ($askforuserinfo == true) {
	if (!empty($_REQUEST['tool_consumer_instance_description'])) {
		$_SESSION['ltiorgname'] = $_REQUEST['tool_consumer_instance_description'];
	} 	
	header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?userinfo=ask");
	exit;	
	
}

//if here, we know the local userid.

//determine request type, and check availability
$now = time();
if ($keyparts[0]=='cid') {
	$cid = intval($keyparts[1]);
	$query = "SELECT available,ltisecret FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	if (!($line['avail']==0 || $line['avail']==2)) {
		reporterror("This course is not available");
	}
} else if ($keyparts[0]=='aid') {
	$aid = intval($keyparts[1]);
	$query = "SELECT courseid,startdate,enddate,avail,ltisecret FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$cid = $line['courseid'];
	if ($line['avail']==0 || $now>$line['enddate'] || $now<$line['startdate']) {
		reporterror("This assessment is closed");
	}
} else if ($keyparts[0]!='sso') {
	reporterror("invalid key. unknown action type");
}

//see if student is enrolled, if appropriate to action type
if ($keyparts[0]=='cid' || $keyparts[0]=='aid') {
	$query = "SELECT id FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result) == 0) { //nope, not enrolled
		$query = "INSERT INTO imas_students (userid,courseid) VALUES ('$userid','$cid')";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
}
	
//check if db session entry exists for session
$promptforsettings = false;
$query = "SELECT userid,sessiondata FROM imas_sessions WHERE sessionid='$sessionid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)>0) {
	//check that same userid, and that we're not jumping on someone else's 
	//existing session.  If so, then we need to create a new session.
	if (mysql_result($result,0,0)!=$userid) {
		session_regenerate_id();
		$sessionid = session_id();
		$sessiondata = array();
		$createnewsession = true;
	} else {
		//already have session.  Don't need to create one
		$sessiondata = unserialize(base64_decode(mysql_result($result,0,1)));
		if (!isset($sessiondata['mathdisp'])) {
			//for some reason settings are not set, so going to prompt
			$promptforsettings = true;
		}
		$createnewsession = false;
	}
} else {
	$sessiondata = array();
	$createnewsession = true;
}

//if assessment, going to check for timelimit
if ($keyparts[0]=='aid') {
	$query = "SELECT timelimit FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$timelimit = mysql_result($result,0,0);
	if ($timelimit>0) {
		 if ($timelimit>3600) {
			$tlhrs = floor($timelimit/3600);
			$tlrem = $timelimit % 3600;
			$tlmin = floor($tlrem/60);
			$tlsec = $tlrem % 60;
			$tlwrds = "$tlhrs hour";
			if ($tlhrs > 1) { $tlwrds .= "s";}
			if ($tlmin > 0) { $tlwrds .= ", $tlmin minute";}
			if ($tlmin > 1) { $tlwrds .= "s";}
			if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
			if ($tlsec > 1) { $tlwrds .= "s";}
		} else if ($line['timelimit']>60) {
			$tlmin = floor($timelimit/60);
			$tlsec = $timelimit % 60;
			$tlwrds = "$tlmin minute";
			if ($tlmin > 1) { $tlwrds .= "s";}
			if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
			if ($tlsec > 1) { $tlwrds .= "s";}
		} else {
			$tlwrds = $timelimit . " second(s)";
		}
	} else {
		$tlwrds = '';
	}
	//this sessiondata tells WAMAP to limit access to the specific resouce requested
	$sessiondata['ltiitemtype']=0;
	$sessiondata['ltiitemid'] = $aid;
}  else if ($keyparts[0]=='cid') { //is cid
	$sessiondata['ltiitemtype']=1;
	$sessiondata['ltiitemid'] = $cid;
}
$enc = base64_encode(serialize($sessiondata));
if ($createnewsession) {
	$query = "INSERT INTO imas_sessions (sessionid,userid,sessiondata,time) VALUES ('$sessionid','$userid','$enc',$now)";
} else {
	$query = "UPDATE imas_sessions SET sessiondata='$enc',userid='$userid' WHERE sessionid='$sessionid'";
}
mysql_query($query) or die("Query failed : " . mysql_error());
if (!$promptforsettings && !$createnewsession && !($keyparts[0]=='aid' && $tlwrds != '')) { 
	//redirect now if already have session and no timelimit
	$now = time();
	$query = "UPDATE imas_users SET lastaccess='$now' WHERE id='$userid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	if ($keyparts[0]=='aid') { //is aid
		header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id=$aid");
	} else if ($keyparts[0]=='cid') { //is cid
		header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
	} else if ($keyparts[0]=='sso') {
		header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/index.php");
	} 
	exit;	
} else {
	header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?accessibility=ask");
	exit;	
}



?>
