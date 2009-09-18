<?php
//IMathAS:  BasicLTI Producer Code
//(c) David Lippman 2009
//based on demo code only - not on specs.  No guarantee of compliance with specs

include("config.php");
if ($enablesimplelti!=true) {
	echo "LTI not enabled";
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

//check to see if new LTI user is posting back user info
if (isset($_SESSION['ltiuserid'])) {
	//process form
	//create local user
	$userid = mysql_insert_id();
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
	require_once 'includes/lsioauthstore.php';
	
	//set up OAuth
	$store = new IMathASLTIOAuthDataStore();
	$server = new OAuthServer($store);
	$method = new OAuthSignatureMethod_HMAC_SHA1();
	$server->add_signature_method($method);
	$request = OAuthRequest::from_request();
	$base = $request->get_signature_base_string();
	try {
		$server->verify_request($request);
	} catch ($err) {
		reporterror($err);	
	}
	mark_nonce_used($request);
	
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
		$_SESSION['ltikey'] = $ltikey;
		
		////create form asking them for user info
		if ($lti_only) { 
			//using LTI for authentication; don't need username/password
			//only request name, email
			
		} else {
			//tying LTI to IMAthAS account
			//give option to provide existing account info, or
			//provide full new student info
		}
		
		exit;
	}
}

//if here, we know the local userid.

//determine request type, and check availability
$keyparts = explode('_',$consumer_key);
if (count($keyparts)<3) {
	$lti_only = false;
} else {
	if ($keyparts[2]==1) {
		$lti_only = true;
	} else {
		$lti_only = false;
	}
}
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
	





?>
