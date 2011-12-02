<?php
//SimpleLTI producer launch negotiation

//TODO:  do we need to check launch_targets?

//in debugmode, accepts duplicate nonces, ignores time diffs
$debugmode = true;

include("config.php");

if ($enablesimplelti!=true) {
	echo "LTI not enabled";
	exit;
}

function reporterror($err) {
	 // Do status 400
	if ( $_REQUEST['action'] == 'launchresolve' ) {
	      print "<launchResponse>\n";
	      print "<status>fail</status>\n";
	      print "<code>BadPasswordDigest</code>\n";
	      print "<description>$err</description>\n";
	      print "</launchResponse>\n";
	 } else {
	      print "Error in the launch POST data:\n";
	      print $err;
	 }
	exit;	
}

function returnstudentnotice($err) {
	global $urlmode;
	//need to create url that will deliver this notice?
	//echo $not;
	$host = $_SERVER['HTTP_HOST'];
	$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	$theurl = "$urlmode$host$uri/ltilaunch.php?stuerr=".urlencode($nerr);
	
	if ( $_REQUEST['action'] == 'launchresolve' ) {
	    echo "<launchResponse>\n";
	    echo "   <status>success</status>\n";
	    echo "   <type>iframe</type>\n";
	    echo "   <launchUrl>".htmlspecialchars($theurl)."</launchUrl>\n";
	    echo"</launchResponse>\n";
	} else {
	   echo '<html><head><title>Error</title></head><body>';
	   echo $err;
	   echo '</body></html>';
	}
	exit;
}

if (!empty($_GET['aid'])) {
	$aid = $_GET['aid'];
	$itemtype = 0;
} else if (!empty($_GET['cid'])) {
	$cid = $_GET['cid'];
	$itemtype = 1;
} else {
	reporterror("No resource specified");
}

if (empty($_REQUEST['user_id'])) {
	reporterror("user_id is required");
} else {
	$ltiuserid = $_REQUEST['user_id'];
}
if (empty($_REQUEST['user_role'])) {
	reporterror("user_role is required");
} else {
	$ltirole = $_REQUEST['user_role'];
}
if (empty($_REQUEST['org_id'])) {
	reporterror("org_id is required");
} else {
	$ltiorg = $_REQUEST['org_id'];
}
if (empty($_REQUEST['sec_digest'])) {
	reporterror("sec_digest is required");
} else {
	$digest = $_REQUEST['sec_digest'];
}
if (empty($_REQUEST['sec_nonce'])) {
	reporterror("sec_nonce is required");
} else {
	$nonce = $_REQUEST['sec_nonce'];
}
if (empty($_REQUEST['sec_created'])) {
	reporterror("sec_created is required");
} else {
	$created = $_REQUEST['sec_created'];
}

$now = time();

if ($itemtype==0) { //accessing single assessment
	$query = "SELECT courseid,startdate,enddate,avail,ltisecret FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$cid = $line['courseid'];
	if ($line['avail']==0 || $now>$line['enddate'] || $now<$line['startdate']) {
		returnstudentnotice("This assessment is closed");
	}
	$secret = $line['ltisecret'];
} else if ($itemtype==1) { //accessing whole course
	$query = "SELECT available,ltisecret FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	if (!($line['avail']==0 || $line['avail']==2)) {
		returnstudentnotice("This course is not available");
	}
	$secret = $line['ltisecret'];
}
if (trim($secret)=='') {
	reporterror("No valid secret exists");
}

//check created by time
$createdtime = strtotime($created);
if (abs($now-$created)>60) {
	if (!$debugmode) {
		reporterror("Expired");
	}
}
//check nonce unique 
$query = "SELECT id FROM imas_ltinonces WHERE nonce='$nonce'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)>0) {
	if (!$debugmode) {
		reporterror("Duplicate nonce");
	}
} else {
	//record nonce to prevent reruns - we'll hold for 15 min
	$query = "INSERT INTO imas_ltinonces (nonce,time) VALUES ('$nonce','$now')";
	mysql_query($query) or die("Query failed : " . mysql_error());
}
//check sec digest
if (base64_encode(pack("H*", sha1($nonce.$created.$secret))) != $digest) {
	reporterror("Bad secret - digest mismatch");
}

//look if we know this student
$query = "SELECT userid FROM imas_ltiusers WHERE org='$ltiorg' AND ltiuserid='$ltiuserid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result) > 0) { //yup, we know them
	$userid = mysql_result($result,0,0);
} else {
	//echo "student not known?  id $ltiuserid, org $ltiorg";
	
	$query = "INSERT INTO imas_ltiusers (org,ltiuserid) VALUES ('$ltiorg','$ltiuserid')";
	mysql_query($query) or die("Query failed : " . mysql_error());
	$localltiuser = mysql_insert_id();
	if (!empty($_REQUEST['user_email'])) {
		$email = $_REQUEST['user_email'];
	} else {
		$email = "none@none.com";
	}
	if (!empty($_REQUEST['user_firstname'])) {
		$firstname = $_REQUEST['user_firstname'];
	} else {
		$firstname = "unknown";
	}
	if (!empty($_REQUEST['user_lastname'])) {
		$lastname = $_REQUEST['user_lastname'];
	} else {
		$lastname = "unknown";
	}
	if (!empty($_REQUEST['user_eid'])) {
		$sid = $_REQUEST['user_eid'];
		$query = "SELECT userid FROM imas_users WHERE SID='$sid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) { //eid already used as username; oh well
			$sid = "lti-".$localltiuser;
		}
	} else {
		$sid = "lti-".$localltiuser;
	}
	$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email) VALUES ";
	$query .= "('$sid','none',10,'$firstname','$lastname','$email')";
	mysql_query($query) or die("Query failed : " . mysql_error());
	$userid = mysql_insert_id();	
	$query = "UPDATE imas_ltiusers SET userid='$userid' WHERE id='$localltiuser'";
	mysql_query($query) or die("Query failed : " . mysql_error());
}
	
//see if student is enrolled
$query = "SELECT id FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result) == 0) { //nope, not enrolled
	$query = "INSERT INTO imas_students (userid,courseid) VALUES ('$userid','$cid')";
	mysql_query($query) or die("Query failed : " . mysql_error());
}

//save info in access table
$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
$pass = '';
for ($i=0;$i<10;$i++) {
	$pass .= substr($chars,rand(0,61),1);
}
$query = "INSERT INTO imas_ltiaccess (password,userid,itemid,itemtype,created) VALUES ";
if ($itemtype==0) { //is aid
	$query .= "('$pass','$userid','$aid',0,$now)";
} else if ($itemtype==1) { // is cid
	$query .= "('$pass','$userid','$cid',1,$now)";
}
mysql_query($query) or die("Query failed : " . mysql_error());
$accessid = mysql_insert_id();

//cleanup
$old = $now - 900; //old stuff - 15 min
$query = "DELETE FROM imas_ltinonces WHERE time<$old";
mysql_query($query) or die("Query failed : " . mysql_error());
$query = "DELETE FROM imas_ltiaccess WHERE created<$old";
mysql_query($query) or die("Query failed : " . mysql_error());

//we're done!  send back the launchresponse
$host = $_SERVER['HTTP_HOST'];
$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

$theurl = "$urlmode$host$uri/ltilaunch.php?id=".$accessid.'&code='.$pass;

if ( $_REQUEST['action'] == 'launchresolve' ) {
    echo "<launchResponse>\n";
    echo "   <status>success</status>\n";
    echo "   <type>iframe</type>\n";
    echo "   <launchUrl>".htmlspecialchars($theurl)."</launchUrl>\n";
    echo"</launchResponse>\n";
} else if ( ! headers_sent() && $_REQUEST['action'] == 'direct' ) {
    header("Location: $theurl");
} else {
    echo '<a href="'.$theurl.'" target="_new" >Click Here</a>'."\n";
}
?>
