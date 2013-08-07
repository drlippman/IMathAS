<?php
//Simple LTI Launch - *DEPRECATED*
//Called from iframe using URL produced during LTIlaunchrequest.php negotiation

if (isset($_GET['stuerr'])) {
	echo '<html><head><title>Error</title></head><body>';
	echo $_GET['stuerr'];
	echo '</body></html>';
	exit;
}
require("config.php");
if ($enablesimplelti!=true) {
	echo "LTI not enabled";
	exit;
}
if (isset($sessionpath)) { session_save_path($sessionpath);}
ini_set('session.gc_maxlifetime',86400);
ini_set('auto_detect_line_endings',true);
session_start();
$sessionid = session_id();

function reporterror($err) {
	echo $err;
	exit;	
}

if (isset($_GET['launch'])) {
	$query = "SELECT sessiondata,userid FROM imas_sessions WHERE sessionid='$sessionid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
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
	if ($sessiondata['ltiitemtype']==0) { //is aid
		$aid = $sessiondata['ltiitemid'];
		$query = "SELECT courseid FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$cid = mysql_result($result,0,0);
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id=$aid");
	} else if ($sessiondata['ltiitemtype']==1) { //is cid
		$cid = $sessiondata['ltiitemid'];
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
	}
	exit;	
} 

if (empty($_GET['id']) || empty($_GET['code'])) {
	reporterror("Missing id or code");
}
$id = $_GET['id'];
$code = $_GET['code'];
$now = time();

$query = "SELECT userid,itemid,itemtype,created FROM imas_ltiaccess WHERE id='$id' AND password='$code'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) {
	//no entry. Either no/pw or rerun
	//if rerun, check if session exists - might be cache-caused rerun
	$query = "SELECT userid,sessiondata FROM imas_sessions WHERE sessionid='$sessionid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$sessiondata = unserialize(base64_decode(mysql_result($result,0,1)));
		if (empty($sessiondata['ltiitemid'])) {
			reporterror("Bad id or code");
		}
		if ($sessiondata['ltiitemtype']==0) { //is aid
			$aid = $sessiondata['ltiitemid'];
			$query = "SELECT courseid FROM imas_assessments WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$cid = mysql_result($result,0,0);
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id=$aid");
		} else if ($sessiondata['ltiitemtype']==1) { //is cid
			$cid = $sessiondata['ltiitemid'];
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
		}
		exit;	
	} else {
		//no session, must be bad
		reporterror("Bad id or code, or being rerun");
	}
} else {
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	if (abs($line['created'] - $now)>900) {
		reporterror("Access has expired - please refresh and try again");
	}
	$userid = $line['userid'];
	$itemtype = $line['itemtype'];
	if ($itemtype==0) { //aid
		$aid = $line['itemid'];
		$query = "SELECT courseid,timelimit FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		list($cid,$timelimit) = mysql_fetch_row($result);
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
	} else if ($itemtype==1) { //cid
		$cid = $line['itemid'];
	}	
}
//prevent rerun
$query = "DELETE FROM imas_ltiaccess WHERE id='$id' AND password='$code'";
mysql_query($query) or die("Query failed : " . mysql_error());

//have we already established a session for this person?
$promptforsettings = false;
$query = "SELECT userid,sessiondata FROM imas_sessions WHERE sessionid='$sessionid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)>0) {
	//already have session.  Don't need to 	
	$sessiondata = unserialize(base64_decode(mysql_result($result,0,1)));
	if (!isset($sessiondata['mathdisp'])) {
		$promptforsettings = true;
	}
	$createnewsession = false;
} else {
	$sessiondata = array();
	$createnewsession = true;
}
$sessiondata['ltiitemtype'] = $itemtype;
$sessiondata['ltiitemid'] = $line['itemid'];
$enc = base64_encode(serialize($sessiondata));
if ($createnewsession) {
	$query = "INSERT INTO imas_sessions (sessionid,userid,sessiondata,time) VALUES ('$sessionid','$userid','$enc',$now)";
} else {
	$query = "UPDATE imas_sessions SET sessiondata='$enc',userid='$userid' WHERE sessionid='$sessionid'";
}
mysql_query($query) or die("Query failed : " . mysql_error());
if (!$promptforsettings && !$createnewsession && !($itemtype==0 && $tlwrds != '')) { 
	//redirect now if already have session and no timelimit
	$now = time();
	$query = "UPDATE imas_users SET lastaccess='$now' WHERE id='$userid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	if ($sessiondata['ltiitemtype']==0) { //is aid
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id=$aid");
	} else if ($sessiondata['ltiitemtype']==1) { //is cid
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
	}
	exit;	
	
} else {
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
require("header.php");
echo "<form method=\"post\" action=\"{$_SERVER['PHP_SELF']}?launch=true\" ";
if ($itemtype==0 && $tlwrds != '') {
	echo "onsubmit='return confirm(\"This assessment has a time limit of $tlwrds.  Click OK to start or continue working on the assessment.\")' ";
}
echo ">";
?>
<div id="settings">JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.  
Please enable JavaScript and reload this page</div>
<input type="hidden" id="tzoffset" name="tzoffset" value="" />
<script type="text/javascript">        
        var thedate = new Date();  
        document.getElementById("tzoffset").value = thedate.getTimezoneOffset();  
</script> 
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
}
?>
