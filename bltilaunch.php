<?php
//IMathAS:  BasicLTI Producer Code
//(c) David Lippman 2009
//
//
//launches with four types of key/secrets
//   username     : of a user with rights 11 or 76 (has to manually entered into database, along with a plaintext password as the secret)
//   aid_###      : launches assessment with given id.  secret is course's ltisecret
//   cid_###      : launches course with given id.  secret is course's ltisecret
//   sso_username : launches single signon using given userid w/ rights 11 or 76. 
//                 secret value stored in DB password field.  Currently must be manually editted in DB
//   aid_, cid_, and sso_ types accept additional _0 or _1  :  0 is default, and links LMS account with a local account
//                                      1 using LMS for validation, does not ask for local account info
//
//  for sso_ and username types, if associated user has rights 11, instructors must link accounts;
//     rights 76 allows TC to create instructor accounts
//
//  LMS MUST provide, in addition to key and secret:
//    user_id
//    
//  LMS MAY provide:
//    lis_person_name_given
//    lis_person_name_family
//    lis_person_contact_email_primary
//    tool_consumer_instance_guid  (LMS domain name)


header('P3P: CP="ALL CUR ADM OUR"');
include("config.php");
if (!get_magic_quotes_gpc()) {
	$_REQUEST = array_map('addslashes_deep', $_REQUEST);
}

 if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
 }
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
if ($_SERVER['HTTP_HOST'] != 'localhost') {
	 session_set_cookie_params(0, '/', '.'.implode('.',array_slice(explode('.',$_SERVER['HTTP_HOST']),isset($CFG['GEN']['domainlevel'])?$CFG['GEN']['domainlevel']:-2)));
}
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
	
	$query = "UPDATE imas_sessions SET sessiondata='$enc',tzoffset='{$_POST['tzoffset']}' WHERE sessionid='$sessionid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$keyparts = explode('_',$_SESSION['ltikey']);
	if ($sessiondata['ltiitemtype']==0) { //is aid
		$aid = $sessiondata['ltiitemid'];
		$query = "SELECT courseid FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$cid = mysql_result($result,0,0);
		if ($sessiondata['ltirole'] == 'learner') {
			$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
			$query .= "('$userid','$cid','assesslti','$aid',$now,'')";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id=$aid");
	} else if ($sessiondata['ltiitemtype']==1) { //is cid
		$cid = $sessiondata['ltiitemid'];
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
	} else if ($sessiondata['ltiitemtype']==2) {
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/index.php");
	} else { //will only be instructors hitting this option
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/ltihome.php");
	}
	exit;	
} else if (isset($_GET['accessibility'])) {
	$query = "SELECT sessiondata,userid FROM imas_sessions WHERE sessionid='$sessionid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)==0) {
		reporterror("No authorized session exists");
	}
	list($enc,$userid) = mysql_fetch_row($result);
	$sessiondata = unserialize(base64_decode($enc));
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
	if ($sessiondata['ltiitemtype']==0 && $sessiondata['ltitlwrds'] != '') {
		echo "onsubmit='return confirm(\"This assessment has a time limit of {$sessiondata['ltitlwrds']}.  Click OK to start or continue working on the assessment.\")' >";
		echo "<p style=\"color:red;\">This assessment has a time limit of {$sessiondata['ltitlwrds']}.</p>";
	} else {
		echo ">";
	}
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
	$ltirole = $_SESSION['ltirole'];
	$keyparts = explode('_',$_SESSION['ltikey']);
	$name_only = false;
	if (count($keyparts)==1 && $ltirole=='learner') {
		$name_only = true;
	} else if (count($keyparts)>2 && $keyparts[2]==1 && $ltirole=='learner') {
		$name_only = true;
	}
	if ($ltirole=='learner' || $_SESSION['lti_keyrights']==76) {
		$allow_acctcreation = true;
	} else {
		$allow_acctcreation = false;
	}
	if ($_GET['userinfo']=='set') {	
		//check input
		$infoerr = '';
		unset($userid);
		if ($name_only) {
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
				if (mysql_num_rows($result)==0) {
					$infoerr = 'Username (key) is not valid';
				} else {
					if (mysql_result($result,0,0)==$md5pw) {
						$userid=mysql_result($result,0,1);
					} else {
						$infoerr = 'Existing username/password provided are not valid.';
					}
				}
			} else {
				if (!$allow_acctcreation) {
					$infoerr = 'Must link to an existing account';
				}
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
			if (!isset($userid) && $allow_acctcreation) {	
				if ($name_only) {
					//make up a username/password for them
					$_POST['SID'] = 'lti-'.$localltiuser;
					$md5pw = 'pass'; //totally unusable since not md5'ed
				}
				if ($ltirole=='instructor') {
					if (isset($CFG['LTI']['instrrights'])) {
						$rights = $CFG['LTI']['instrrights'];
					} else {
						$rights = 40;
					}
				} else {
					$rights = 10;
				}
				$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify) VALUES ";
				$query .= "('{$_POST['SID']}','$md5pw',$rights,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}',$msgnot)";
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
		if ($name_only) { 
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
			
			//strip off prepended org info before display
			$ltiorgparts = explode(':',$ltiorgname);
			if (count($ltiorgparts)>2) {
				array_shift($ltiorgparts);
				$ltiorgname = implode(':',$ltiorgparts);
			} else {
				$ltiorgname = $ltiorgparts[1];
			}
			
			//tying LTI to IMAthAS account
			//give option to provide existing account info, or provide full new student info
			if ($allow_acctcreation) {
				echo "<p>If you already have an account on $installname, enter your username and ";
				echo "password below to enable automated signon from $ltiorgname</p>";
			} else {
				echo "<p>Enter your username and ";
				echo "password for $installname below to enable automated signon from $ltiorgname</p>";
			}
			echo "<span class=form><label for=\"curSID\">$loginprompt:</label></span> <input class=form type=text size=12 id=\"curSID\" name=\"curSID\"><BR class=form>\n";
			echo "<span class=form><label for=\"curPW\">Password:</label></span><input class=form type=password size=20 id=\"curPW\" name=\"curPW\"><BR class=form>\n";
			echo "<div class=submit><input type=submit value='Sign In'></div>\n";
			if ($allow_acctcreation) {
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
			} else {
				echo "<p>If you do not already have an account on $installname, please visit the site to request an account.</p>";
			}
		}
		echo "</form>\n";
		require("footer.php");
		exit;	
			
	}
	
} else if (isset($_SESSION['ltiuserid']) && !isset($_REQUEST['oauth_consumer_key'])) {
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
		reporterror("Unable to launch - User information not provided (user_id is required)");
	} else {
		$ltiuserid = $_REQUEST['user_id'];
	}
	
	if (empty($_REQUEST['context_id'])) {
		reporterror("Unable to launch - Course information not provided (context_id is required)");
	}
	
	if (isset($_SESSION['ltiuserid']) && $_SESSION['ltiuserid']!=$ltiuserid) {
		//new user - need to clear out session
		session_destroy();
		session_start();
		session_regenerate_id();
		$sessionid = session_id();
		$_SESSION = array();
		setcookie(session_name(),session_id());
	}
	
	/*if (empty($_REQUEST['roles'])) {
		reporterror("roles is required");
	} else {
		$ltirole = $_REQUEST['roles'];
	}*/
	if (empty($_REQUEST['tool_consumer_instance_guid'])) {
		$ltiorg = 'Unknown';
	} else {
		$ltiorg = $_REQUEST['tool_consumer_instance_guid'];
	}
	if (empty($_REQUEST['oauth_consumer_key'])) {
		reporterror("Unable to launch - oauth_consumer_key (resource key) is required");
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
		$requestinfo = $server->verify_request($request);
	} catch (Exception $e) {
		reporterror($e->getMessage());	
	}
	$store->mark_nonce_used($request);
	
	$keyparts = explode('_',$ltikey);
	$_SESSION['ltiorigkey'] = $ltikey;
	
	// prepend ltiorg with courseid or sso+userid to prevent cross-instructor hacking  
	if ($keyparts[0]=='cid' || $keyparts[0]=='placein') {  //cid:org
		$_SESSION['ltilookup'] = 'c';
		$ltiorg = $keyparts[1].':'.$ltiorg;
		if ($keyparts[0]=='placein') {
			$keytype = 'gc';
		} else {
			$keytype = 'c';
		}
		if (isset($_REQUEST['custom_place_aid'])) { //common catridge blti placement using cid_### or placein_### key type
			$placeaid = intval($_REQUEST['custom_place_aid']);
			$query = "SELECT courseid FROM imas_assessments WHERE id='$placeaid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$sourcecid = mysql_result($result,0,0);
			if ($keyparts[1]==$sourcecid) { //is key is for source course; treat like aid_### placement
				$keyparts[0] = 'aid';
				$keyparts[1] = $placeaid;
				$ltikey = implode('_',$keyparts);
				$keytype = 'a';
			} else {  //key is for a different course; mark as cc placement
				$keytype = 'cc-c';
				$_SESSION['place_aid'] = array($sourcecid,$_REQUEST['custom_place_aid']);
			}
		}		
	} else if ($keyparts[0]=='aid') {   //also cid:org
		$_SESSION['ltilookup'] = 'a';
		$aid = intval($keyparts[1]);
		$query = "SELECT courseid FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$ltiorg = mysql_result($result,0,0) . ':' . $ltiorg;
		$keytype = 'a';
	} else if ($keyparts[0]=='sso') {  //ssouserid:org
		$_SESSION['ltilookup'] = 'u';
		$ltiorg = $keyparts[0].$keyparts[1]. ':' . $ltiorg;
		$keytype = 's';
	} else {
		$_SESSION['ltilookup'] = 'u';
		$ltiorg = $ltikey.':'.$ltiorg;
		$keytype = 'g';
		if (isset($_REQUEST['custom_place_aid'])) {
			$placeaid = intval($_REQUEST['custom_place_aid']);
			$keytype = 'cc-g';
			$query = "SELECT courseid FROM imas_assessments WHERE id='$placeaid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$sourcecid = mysql_result($result,0,0);
			$_SESSION['place_aid'] = array($sourcecid,$_REQUEST['custom_place_aid']);
		}
	}
	
	
	//Store all LTI request data in session variable for reuse on submit
	//if we got this far, secret has already been verified
	$_SESSION['ltiuserid'] = $ltiuserid;
	$_SESSION['ltiorg'] = $ltiorg;
	$ltirole = strtolower($_REQUEST['roles']);          
	if (strpos($ltirole,'instructor')!== false || strpos($ltirole,'administrator')!== false) {
		$ltirole = 'instructor';
	} else {
		$ltirole = 'learner';
	}
	$_SESSION['ltirole'] = $ltirole;
	$_SESSION['lti_context_id'] = $_REQUEST['context_id'];
	$_SESSION['lti_context_label'] = (!empty($_REQUEST['context_label']))?$_REQUEST['context_label']:$_REQUEST['context_id'];
	$_SESSION['lti_resource_link_id'] = $_REQUEST['resource_link_id'];
	$_SESSION['lti_lis_result_sourcedid'] = $_REQUEST['lis_result_sourcedid'];
	$_SESSION['lti_outcomeurl'] = $_REQUEST['lis_outcome_service_url'];
	$_SESSION['lti_context_label'] = $_REQUEST['context_label'];
	$_SESSION['lti_key'] = $ltikey;
	$_SESSION['lti_keytype'] = $keytype;
	$_SESSION['lti_keyrights'] = $requestinfo[0]->rights;
	if (isset($_REQUEST['selection_directive']) && $_REQUEST['selection_directive']=='select_link') {
		$_SESSION['selection_return'] = $_REQUEST['launch_presentation_return_url'];
	}
	
	
	
	//look if we know this student
	$query = "SELECT userid FROM imas_ltiusers WHERE org='$ltiorg' AND ltiuserid='$ltiuserid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result) > 0) { //yup, we know them
		$userid = mysql_result($result,0,0);
	} else {
		//student is not known.  Bummer.  Better figure out what to do with them :)
		
		//go ahead and create the account if:
		//has name information (should we skip?)
		//domain level placement and (student or instructor with acceptable key rights)
		//a _1 type placement and (student or instructor with acceptable key rights)
		if (((!empty($_REQUEST['lis_person_name_given']) && !empty($_REQUEST['lis_person_name_family'])) || !empty($_REQUEST['lis_person_name_full'])) &&
		   ((count($keyparts)==1 && $ltirole=='learner') ||
		   (count($keyparts)>2 && $keyparts[2]==1 && $ltirole=='learner'))) {
			if (!empty($_REQUEST['lis_person_name_given']) && !empty($_REQUEST['lis_person_name_family'])) {
				$firstname = $_REQUEST['lis_person_name_given'];
				$lastname = $_REQUEST['lis_person_name_family'];
			} else {
				$firstname = '';
				$lastname = $_REQUEST['lis_person_name_full'];
			}
			//if (!empty($_REQUEST['lis_person_contact_email_primary'])) {
			//	$email = $_REQUEST['lis_person_contact_email_primary'];
			//} else {
				$email = 'none@none.com';
			//}
			
			$query = "INSERT INTO imas_ltiusers (org,ltiuserid) VALUES ('$ltiorg','$ltiuserid')";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$localltiuser = mysql_insert_id();	
			if (!isset($userid)) {	
				//make up a username/password for them
				$_POST['SID'] = 'lti-'.$localltiuser;
				$md5pw = 'pass'; //totally unusable since not md5'ed
				if ($ltirole=='instructor') {
					if (isset($CFG['LTI']['instrrights'])) {
						$rights = $CFG['LTI']['instrrights'];
					} else {
						$rights = 40;
					}
				} else {
					$rights = 10;
				}
				$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify) VALUES ";
				$query .= "('{$_POST['SID']}','$md5pw',$rights,'$firstname','$lastname','$email',0)";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$userid = mysql_insert_id();	
			}
			$query = "UPDATE imas_ltiusers SET userid='$userid' WHERE id='$localltiuser'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		} else {
			////create form asking them for user info
			$askforuserinfo = true;
			$_SESSION['LMSfirstname'] = $_REQUEST['lis_person_name_given'];
			$_SESSION['LMSlastname'] = $_REQUEST['lis_person_name_family'];
			if (!empty($_REQUEST['lis_person_contact_email_primary'])) {
				$_SESSION['LMSemail'] = $_REQUEST['lis_person_contact_email_primary'];
			} 
		}
	}
	$_SESSION['ltikey'] = $ltikey;
}



//Do we need to ask for student's info?
//either first connect or bad info on first submit
if ($askforuserinfo == true) {
	if (!empty($_REQUEST['tool_consumer_instance_description'])) {
		$_SESSION['ltiorgname'] = $_REQUEST['tool_consumer_instance_description'];
	} 	
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?userinfo=ask");
	exit;	
	
}

//if here, we know the local userid.

//if it's a common catridge placement and we're here, then either we're using domain credentials, or
//course credentials for a non-source course.

//see if lti_courses is created
//  if not, see if source cid is instructors course
// 	if so, set lti_course
//	if not, create a new blank course
//
//see if courseid==source course cid
//  if not, copy assessment into course, set placement
//  if so, set placement

//determine request type, and check availability
$now = time();

//general placement or common catridge placement - look for placement, or create if know info
if (((count($keyparts)==1 || $_SESSION['lti_keytype']=='gc') && $_SESSION['ltirole']!='instructor') || $_SESSION['lti_keytype']=='cc-g' || $_SESSION['lti_keytype']=='cc-c') { 
	$query = "SELECT placementtype,typeid FROM imas_lti_placements WHERE ";
	$query .= "contextid='{$_SESSION['lti_context_id']}' AND linkid='{$_SESSION['lti_resource_link_id']}' ";
	$query .= "AND org='{$_SESSION['ltiorg']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)==0) {
		if (isset($_SESSION['place_aid'])) {
			//look to see if we've already linked this context_id with a course
			$query = "SELECT courseid FROM imas_lti_courses WHERE contextid='{$_SESSION['lti_context_id']}' ";
			$query .= "AND org='{$_SESSION['ltiorg']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)==0) {
				if ($_SESSION['lti_keytype']=='cc-g') {
					//if instructor, see if the source course is ours
					$copycourse = true;
					if ($_SESSION['ltirole']=='instructor') {
						$query = "SELECT id FROM imas_teachers WHERE courseid='{$_SESSION['place_aid'][0]}' AND userid='$userid'";
						$result = mysql_query($query) or die("Query failed : " . mysql_error());
						if (mysql_num_rows($result)>0) {
							$copycourse=false;
							$destcid = $_SESSION['place_aid'][0];
						}
					} else {
						reporterror("Course link not established yet");
					}
					if ($copycourse) {
						//create a course  
						//creating a copy of a template course
						$blockcnt = 1;
						$itemorder = addslashes(serialize(array()));
						$randkey = uniqid();
						$hideicons = isset($CFG['CPS']['hideicons'])?$CFG['CPS']['hideicons'][0]:0;
						$picicons = isset($CFG['CPS']['picicons'])?$CFG['CPS']['picicons'][0]:0;
						$allowunenroll = isset($CFG['CPS']['allowunenroll'])?$CFG['CPS']['allowunenroll'][0]:0;
						$copyrights = isset($CFG['CPS']['copyrights'])?$CFG['CPS']['copyrights'][0]:0;
						$msgset = isset($CFG['CPS']['msgset'])?$CFG['CPS']['msgset'][0]:0;
						$msgmonitor = (floor($msgset/5))&1;
						$msgQtoInstr = (floor($msgset/5))&2;
						$msgset = $msgset%5;
						$cploc = isset($CFG['CPS']['cploc'])?$CFG['CPS']['cploc'][0]:1;
						$topbar = isset($CFG['CPS']['topbar'])?$CFG['CPS']['topbar'][0]:array(array(),array(),0);
						$theme = isset($CFG['CPS']['theme'])?$CFG['CPS']['theme'][0]:$defaultcoursetheme;
						$chatset = isset($CFG['CPS']['chatset'])?$CFG['CPS']['chatset'][0]:0;
						$showlatepass = isset($CFG['CPS']['showlatepass'])?$CFG['CPS']['showlatepass'][0]:0;
						
						$avail = 0;
						$lockaid = 0;
						$query = "INSERT INTO imas_courses (name,ownerid,enrollkey,hideicons,picicons,allowunenroll,copyrights,msgset,chatset,showlatepass,itemorder,topbar,cploc,available,theme,ltisecret,blockcnt) VALUES ";
						$query .= "('{$_SESSION['lti_context_label']}','$userid','$randkey','$hideicons','$picicons','$unenroll','$copyrights','$msgset',$chatset,$showlatepass,'$itemorder','$topbar','$cploc','$avail','$theme','$randkey','$blockcnt');";
						mysql_query($query) or die("Query failed : " . mysql_error());
						$destcid  = mysql_insert_id();
						$query = "INSERT INTO imas_teachers (userid,courseid) VALUES ('$userid','$destcid')";
						mysql_query($query) or die("Query failed : " . mysql_error());
						
					}
					$query = "INSERT INTO imas_lti_courses (org,contextid,courseid) VALUES ";
					$query .= "('{$_SESSION['ltiorg']}','{$_SESSION['lti_context_id']}',$destcid)";
					mysql_query($query) or die("Query failed : " . mysql_error());
				} else if ($_SESSION['lti_keytype']=='cc-c') {
					$copyaid = true;
					//link up key/secret course
					$query = "INSERT INTO imas_lti_courses (org,contextid,courseid) VALUES ";
					$query .= "('{$_SESSION['ltiorg']}','{$_SESSION['lti_context_id']}','{$keyparts[1]}')";
					mysql_query($query) or die("Query failed : " . mysql_error());
					$destcid = $keyparts[1];
				}
			} else {
				$destcid = mysql_result($result,0,0);
			}
			if ($destcid==$_SESSION['place_aid'][0]) {
				//aid is in destination course - just make placement
				$aid = $_SESSION['place_aid'][1];
			} else {
				//aid is in source course.  Let's look and see if there's an assessment in destination with the same title.
				$query = "SELECT name FROM imas_assessments WHERE id='{$_SESSION['place_aid'][1]}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$sourceassessname = addslashes(mysql_result($result,0,0));
				$query = "SELECT id FROM imas_assessments WHERE name='$sourceassessname' AND courseid='$destcid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$aid = mysql_result($result,0,0);
				} else {
				// no assessment with same title - need to copy assessment from destination to source course
					require("includes/copyiteminc.php");
					$query = "SELECT id FROM imas_items WHERE itemtype='Assessment' AND typeid='{$_SESSION['place_aid'][1]}'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					$cid = $destcid;
					$newitem = copyitem(mysql_result($result,0,0),array());
					$query = "SELECT typeid FROM imas_items WHERE id=$newitem";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					$aid = mysql_result($result,0,0);
					$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					$items = unserialize(mysql_result($result,0,0));
					$items[] = $newitem;
					$items = addslashes(serialize($items));
					$query = "UPDATE imas_courses SET itemorder='$items' WHERE id='$cid'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			}	
			$query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
			$query .= "('{$_SESSION['ltiorg']}','{$_SESSION['lti_context_id']}','{$_SESSION['lti_resource_link_id']}','assess','$aid')";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$keyparts = array('aid',$aid);
			
		} else {
			reporterror("This placement is not yet set up");
		}
	} else {
		$row = mysql_fetch_row($result);
		if ($row[0]=='course') {
			$keyparts = array('cid',$row[1]);
		} else if ($row[0]=='assess') {
			$keyparts = array('aid',$row[1]);
		} else {
			reporterror("Invalid placement type");
		}
		
	}
}

//is course level placement
if ($keyparts[0]=='cid' || $keyparts[0]=='placein') {
	$cid = intval($keyparts[1]);
	$query = "SELECT available,ltisecret FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	if ($_SESSION['ltirole']!='instructor') {
		if (!($line['avail']==0 || $line['avail']==2)) {
			reporterror("This course is not available");
		}
	} 
} else if ($keyparts[0]=='aid') {   //is assessment level placement
	$aid = intval($keyparts[1]);
	$query = "SELECT courseid,startdate,enddate,reviewdate,avail,ltisecret FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$cid = $line['courseid'];
	if ($_SESSION['ltirole']!='instructor') {
		//if ($line['avail']==0 || $now>$line['enddate'] || $now<$line['startdate']) {
		//	reporterror("This assessment is closed");
		//}
		if ($line['avail']==0) {
			//reporterror("This assessment is closed");
		}
		$query = "SELECT startdate,enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid'";
		$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result2);
		if ($row!=null) {
			if ($now<$row[0] || $row[1]<$now) { //outside exception dates
				if ($now > $line['startdate'] && $now < $line['reviewdate']) {
					$isreview = true;
				} else {
					//reporterror("This assessment is closed");
				}
			} else { //inside exception dates exception
				if ($line['enddate']<$now) { //exception is for past-due-date
					$inexception = true; //only trigger if past due date for penalty
				}
			}
			$exceptionduedate = $row[1];
		} else { //has no exception
			if ($now < $line['startdate'] || $line['enddate'] < $now) { //outside normal dates
				if ($now > $line['startdate'] && $now < $line['reviewdate']) {
					$isreview = true;
				} else {
					//reporterror("This assessment is closed");
				}
			}
		}
		
	}
} else if ($keyparts[0]!='sso' && $_SESSION['ltirole']!='instructor') {
	reporterror("invalid key. unknown action type");
} 

//see if student is enrolled, if appropriate to action type
if ($keyparts[0]=='cid' || $keyparts[0]=='aid' || $keyparts[0]=='placein') {
	if ($_SESSION['ltirole']=='instructor') {
		$query = "SELECT id FROM imas_teachers WHERE userid='$userid' AND courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result) == 0) { //nope, not a teacher.  Set as tutor for this context_id
			$query = "SELECT id FROM imas_tutors WHERE userid='$userid' AND courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result) == 0) { //nope, not a tutor already.  Set as tutor for this context_id
				$query = "INSERT INTO imas_tutors (userid,courseid,section) VALUES ('$userid','$cid','{$_SESSION['lti_context_label']}')";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		$timelimitmult = 1;
	} else {
		$query = "SELECT id,timelimitmult FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result) == 0) { //nope, not enrolled
			$query = "SELECT id FROM imas_teachers WHERE userid='$userid' AND courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result) == 0) { //nope, not a teacher either in stuview.  
				$query = "SELECT id FROM imas_tutors WHERE userid='$userid' AND courseid='$cid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result) == 0) { //nope, not a tutor either.  Add new student
					$query = "INSERT INTO imas_students (userid,courseid,section) VALUES ('$userid','$cid','{$_SESSION['lti_context_label']}')";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else {
				$_SESSION['ltirole']='instructor';
				$setstuviewon = true;
			}
			$timelimitmult = 1;
		} else {
			$timelimitmult = mysql_result($result,0,1);
		}
	}
}
	
//check if db session entry exists for session
$promptforsettings = false;
$query = "SELECT userid,sessiondata FROM imas_sessions WHERE sessionid='$sessionid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$SESS = $_SESSION;
if (mysql_num_rows($result)>0) {
	//check that same userid, and that we're not jumping on someone else's 
	//existing session.  If so, then we need to create a new session.
	if (mysql_result($result,0,0)!=$userid) {
		session_destroy();
		session_start();
		session_regenerate_id();
		$sessionid = session_id();
		setcookie(session_name(),session_id());
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
	$timelimit = abs(mysql_result($result,0,0)*$timelimitmult);
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
		} else if ($timelimit>60) {
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
	$sessiondata['ltitlwrds'] = $tlwrds;
	$sessiondata['ltiitemtype']=0;
	$sessiondata['ltiitemid'] = $aid;
}  else if ($keyparts[0]=='cid') { //is cid
	$sessiondata['ltiitemtype']=1;
	$sessiondata['ltiitemid'] = $cid;
} else if ($keyparts[0]=='sso') { //is sso
	$sessiondata['ltiitemtype']=2;
} else {
	$sessiondata['ltiitemtype']=-1;
}
$sessiondata['ltiorg'] = $SESS['ltiorg'];
$sessiondata['ltirole'] = $SESS['ltirole'];
$sessiondata['lti_context_id']  = $SESS['lti_context_id']; 
$sessiondata['lti_resource_link_id']  = $SESS['lti_resource_link_id']; 
$sessiondata['lti_lis_result_sourcedid']  = stripslashes($SESS['lti_lis_result_sourcedid']);
$sessiondata['lti_outcomeurl']  = $SESS['lti_outcomeurl'];
$sessiondata['lti_context_label'] = $SESS['lti_context_label'];
$sessiondata['lti_launch_get'] = $SESS['lti_launch_get'];
$sessiondata['lti_key'] = $SESS['lti_key'];
$sessiondata['lti_keytype'] = $SESS['lti_keytype'];
$sessiondata['lti_keylookup'] = $SESS['ltilookup'];
$sessiondata['lti_origkey'] = $SESS['ltiorigkey'];
if (isset($SESS['selection_return'])) {
	$sessiondata['lti_selection_return'] = $SESS['selection_return'];
}

if (isset($setstuviewon) && $setstuviewon==true) {
	$sessiondata['stuview'] = 0;
}

if ($_SESSION['lti_keytype']=='gc') {
	$sessiondata['lti_launch_get']['cid'] = $keyparts[1];
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
		if ($sessiondata['ltirole'] == 'learner') {
			$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
			$query .= "('$userid','$cid','assesslti','$aid',$now,'')";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id=$aid&ltilaunch=true");
	} else if ($keyparts[0]=='cid') { //is cid
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
	} else if ($keyparts[0]=='sso') {
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/index.php");
	} else { //will only be instructors hitting this option
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/ltihome.php");
	}
	exit;	
} else {
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?accessibility=ask");
	exit;	
}



?>
