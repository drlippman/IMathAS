<?php
//LTI Launch
//Called from iframe using URL produced during LTIlaunchrequest.php negotiation

//TODO: Add something to validate to prevent change of URL from what was 
// authorized by lti code
/*
if ($sessiondata['itemtype']==0) {
	if (script being accessed is showtest) {
		if (isset($sessiondata['sessiontestid']) || $_GET['id'] = $sessiondata['itemid']) {
			we're good
		} else {
			die
		}
	} else {
		die
	}
} if itemtype==1 {
	if isset $_GET['cid'] and $sessiondata['itemid']!=$_GET['cid'] {
		die
	}
}
*/

require("config.php");
if (isset($sessionpath)) { session_save_path($sessionpath);}
ini_set('session.gc_maxlifetime',86400);
ini_set('auto_detect_line_endings',true);
session_start();
$sessionid = session_id();
$sessiondata = array();

function reporterror($err) {
	echo $err;
	exit;	
}

if (isset($_GET['launch'])) {
	$query = "SELECT sessiondata FROM imas_sessions WHERE sessionid='$sessionid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$enc = mysql_result($result,0,0);
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
		 $sessiondata['mathdisp'] = $_POST['mathdisp']; 
		 $sessiondata['graphdisp'] = $_POST['graphdisp'];
		 $sessiondata['useed'] = 1; 
	 }
	$enc = base64_encode(serialize($sessiondata));
	
	$query = "UPDATE imas_sessions SET sessiondata='$enc',tzoffset='{$_POST['tzoffset']}'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	if ($sessiondata['ltiitemtype']==0) { //is aid
		$aid = $sessiondata['ltiitemid'];
		//lookup cid?
		header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id=$aid");
	} else if ($sessiondata['ltiitemtype']==1) { //is cid
		$cid = $sessiondata['ltiitemid'];
		header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
	}
	exit;	
} 

if (empty($_GET['id']) || empty($_GET['code'])) {
	reporterror("Missing id or code");
}
$id = $_GET['id'];
$code = $_GET['code'];
$now = time();

$query = "SELECT userid,itemid,itemtype,time FROM imas_ltiaccess WHERE id='$id' AND password='$code'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows==0) {
	reporterror("Bad id or code, or being rerun");
} else {
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	if (abs($line['time'] - $now)>900) {
		reporterror("Access has expired - please refresh and try again");
	}
	$userid = $line['userid'];
	$itemtype = $line['itemtype'];
	if ($itemtype==0) { //aid
		$aid = $line['itemid'];
		$query = "SELECT courseid,timelimit FROM id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		list($cid,$timelimit) = mysql_fetch_row($result);
	} else if ($itemtype==1) { //cid
		$cid = $line['itemid'];
	}	
}

$sessiondata['ltiitemtype'] = $itemtype;
$sessiondata['ltiitemid'] = $itemid;
$enc = base64_encode(serialize($sessiondata));
$query = "INSERT INTO imas_sessions (sessionid,userid,sessiondata,time) VALUES ('$sessionid','$userid','$enc',$now)";
mysql_query($query) or die("Query failed : " . mysql_error());

//time to output a postback to capture tzoffset
?>
