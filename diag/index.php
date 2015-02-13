<?php
	require("../config.php");
	require("../i18n/i18n.php");
	if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
		 $urlmode = 'https://';
	 } else {
		 $urlmode = 'http://';
	 }

	if (isset($sessionpath)) { session_save_path($sessionpath);}
 	ini_set('session.gc_maxlifetime',86400);
	session_start();
	$sessionid = session_id();
	
	if (!isset($_GET['id'])) {
		$query = "SELECT id,name FROM imas_diags WHERE public=3 OR public=7";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		//echo "<html><body><h1>Diagnostics</h1><ul>";
		$nologo = true;
		$infopath = isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'';
		$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/{$infopath}infopages.css\" type=\"text/css\">\n";
		$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/jstz_min.js\" ></script>";
		require("../header.php");
		$pagetitle = "Diagnostics";
		require((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'../')."infoheader.php");
		echo "<img class=\"floatleft\" src=\"$imasroot/img/ruler.jpg\"/>
		<div class=\"content\">
		<div id=\"headerdiagindex\" class=\"pagetitle\"><h2>", _('Available Diagnostics'), "</h2></div>
		<ul class=\"nomark\">";
		if (mysql_num_rows($result)==0) {
			echo "<li>", _('No diagnostics are available through this page at this time'), "</li>";
		}
		while ($row = mysql_fetch_row($result)) {
			echo "<li><a href=\"$imasroot/diag/index.php?id={$row[0]}\">{$row[1]}</a></li>";
		}
		echo "</ul></div>";
		require("../footer.php");
		exit;
	}
	$diagid = $_GET['id'];
	
	$query = "SELECT * from imas_diags WHERE id='$diagid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$pcid = $line['cid'];
	$diagid = $line['id'];
	if ($line['term']=='*mo*') {
		$diagqtr = date("M y");
	} else if ($line['term']=='*day*') {
		$diagqtr = date("M j y");
	} else {
		$diagqtr = $line['term'];
	}
	$sel1 = explode(',',$line['sel1list']);
	
	if (!($line['public']&1)) {
		echo "<html><body>", _('This diagnostic is not currently available to be taken'), "</body></html>";
		exit;
	}
	$userip = $_SERVER['REMOTE_ADDR'];
	$noproctor = false;
	if ($line['ips']!='') {
		foreach (explode(',',$line['ips']) as $ip) {
			if ($ip=='*') {
				$noproctor = true;
				break;
			} else if (strpos($ip,'*')!==FALSE) {
				$ip = substr($ip,0,strpos($ip,'*'));
				if ($ip == substr($userip,0,strlen($ip))) {
					$noproctor = true;
					break;
				}
			} else if ($ip==$userip) {
				$noproctor = true;
				break;
			}
		}
	}
	
	$query = "SELECT sessiondata FROM imas_sessions WHERE sessionid='$sessionid'";
	$result =  mysql_query($query) or die("Query failed : " . mysql_error());
	//if (isset($sessiondata['mathdisp'])) {
	if (mysql_num_rows($result)>0) {
	   $query = "DELETE FROM imas_sessions WHERE sessionid='$sessionid'";
	   mysql_query($query) or die("Query failed : " . mysql_error());
	   $sessiondata = array();
	   if (isset($_COOKIE[session_name()])) {
		   setcookie(session_name(), '', time()-42000, '/');
	   }
	   session_destroy();
	   header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php?id=$diagid");
	   exit;
	}

if (isset($_POST['SID'])) {
	$_POST['SID'] = trim(str_replace('-','',$_POST['SID']));
	if (trim($_POST['SID'])=='' || trim($_POST['firstname'])=='' || trim($_POST['lastname'])=='') {
		echo "<html><body>", _('Please enter your ID, first name, and lastname.'), "  <a href=\"index.php?id=$diagid\">", _('Try Again'), "</a>\n";
			exit; 
	}
	$query = "SELECT entryformat,sel1list from imas_diags WHERE id='$diagid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$entryformat = mysql_result($result,0,0);
	$sel1 = explode(',',mysql_result($result,0,1));
	$entrytype = substr($entryformat,0,1); //$entryformat{0};
	$entrydig = substr($entryformat,1); //$entryformat{1};
	$entrynotunique = false;
	if ($entrytype=='A' || $entrytype=='B') {
		$entrytype = chr(ord($entrytype)+2);
		$entrynotunique = true;
	}
	$pattern = '/^';
	if ($entrytype=='C') {
		$pattern .= '\w';
	} else if ($entrytype=='D') {
		$pattern .= '\d';
	} else if ($entrytype=='E') {
		$pattern .= '[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}';
	}
	if ($entrytype!='E') {
		if ($entrydig==0) {
			$pattern .= '+';
		} else {
			$pattern .= '{'.$entrydig.'}';
		}
	}
	$pattern .= '$/i';
	if (!preg_match($pattern,$_POST['SID'])) {
		echo "<html><body>", _('Your ID is not valid.  It should contain'), " ";
		if ($entrydig>0 && $entrytype!='E') {
			echo $entrydig.' ';
		}
		if ($entrytype=='C') {
			echo _('letters or numbers');
		} else if ($entrytype=='D') {
			echo _('numbers');
		} else if ($entrytype=='E') {
			echo _('an email address');
		}
		echo " <a href=\"index.php?id=$diagid\">", _('Try Again'), "</a>\n";
		exit;
	}
	
	if ($_POST['course']==-1) {
		echo "<html><body>", sprintf(_('Please select a %1$s and %2$s.'), $line['sel1name'], $line['sel2name']), "  <a href=\"index.php?id=$diagid\">", _('Try Again'), "</a>\n";
			exit; 
	}
	$pws = explode(';',$line['pws']);
	if (trim($pws[0])!='') {
		$basicpw = explode(',',$pws[0]);
	} else {
		$basicpw = array();
	}
	if (count($pws)>1 && trim($pws[1])!='') {
		$superpw = explode(',',$pws[1]);
	} else {
		$superpw = array();
	}
	//$pws = explode(',',$line['pws']);
	foreach ($basicpw as $k=>$v) {
		$basicpw[$k] = strtolower($v);
	}
	foreach ($superpw as $k=>$v) {
		$superpw[$k] = strtolower($v);
	}
	$diagSID = $_POST['SID'].'~'.addslashes($diagqtr).'~'.$pcid;
	if ($entrynotunique) {
		$diagSID .= '~'.preg_replace('/\W/','',$sel1[$_POST['course']]);
	}
	if (!$noproctor) {
		if (!in_array(strtolower($_POST['passwd']),$basicpw) && !in_array(strtolower($_POST['passwd']),$superpw)) {
			$query = "SELECT id,goodfor FROM imas_diag_onetime WHERE code='".strtoupper($_POST['passwd'])."' AND diag='$diagid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$passwordnotfound = false;
			if (mysql_num_rows($result)>0) {
				$row = mysql_fetch_row($result); //[0] = id, [1] = goodfor
				if ($row[1]==0) {  //onetime
					$query = "DELETE FROM imas_diag_onetime WHERE id={$row[0]}";
					mysql_query($query) or die("Query failed : " . mysql_error());
				} else { //set time expiry
					$now = time();
					if ($row[1]<100000000) { //is time its good for - not yet used
						$expiry = $now + $row[1]*60;
						$query = "UPDATE imas_diag_onetime SET goodfor=$expiry WHERE id={$row[0]}";
						mysql_query($query) or die("Query failed : " . mysql_error());
					} else if ($now<$row[1]) {//is expiry time and we're within it
						//alls good
					} else { //past expiry
						$query = "DELETE FROM imas_diag_onetime WHERE id={$row[0]}";
						mysql_query($query) or die("Query failed : " . mysql_error());
						$passwordnotfound = true;
					}
				}
			} else {
				$passwordnotfound = true;
			}
			if ($passwordnotfound) {
				$query = "SELECT password FROM imas_users WHERE SID='$diagSID'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0 && strtoupper(mysql_result($result,0,0))==strtoupper($_POST['passwd'])) {
					
				} else {
					echo "<html><body>", _('Error, password incorrect or expired.'), "  <a href=\"index.php?id=$diagid\">", _('Try Again'), "</a>\n";
					exit;
				}
			}
		}
	}
	$cnt = 0;
	$now = time();
	
	$query = "SELECT id FROM imas_users WHERE SID='$diagSID'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$userid = mysql_result($result,0,0);
		$allowreentry = ($line['public']&4);
		if (!in_array(strtolower($_POST['passwd']),$superpw) && (!$allowreentry || $line['reentrytime']>0)) {
			$aids = explode(',',$line['aidlist']);
			$paid = $aids[$_POST['course']];
			$query = "SELECT id,starttime FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='$paid'";
			$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($r2)>0) {
				if (!$allowreentry) {
					echo _("You've already taken this diagnostic."), "  <a href=\"index.php?id=$diagid\">", _('Back'), "</a>\n";
					exit;
				} else {
					$d = mysql_fetch_row($r2);
					$now = time();
					if ($now - $d[1] > 60*$line['reentrytime']) {
						echo _('Your window to complete this diagnostic has expired.'), "  <a href=\"index.php?id=$diagid\">", _('Back'), "</a>\n";
						exit;
					}
				}
			}
		}
		//if ($allowreentry) {
			
			$sessiondata['mathdisp'] = $_POST['mathdisp'];//1;
			$sessiondata['graphdisp'] = $_POST['graphdisp'];//1;
			//$sessiondata['mathdisp'] = 1;
			//$sessiondata['graphdisp'] = 1;
			$sessiondata['useed'] = 1;
			$sessiondata['isdiag'] = $diagid;
			$enc = base64_encode(serialize($sessiondata));
			if (!empty($_POST['tzname'])) {
				$tzname = $_POST['tzname'];
			} else {
				$tzname = '';
			}
			$query = "INSERT INTO imas_sessions (sessionid,userid,time,tzoffset,tzname,sessiondata) VALUES ('$sessionid','$userid',$now,'{$_POST['tzoffset']}','$tzname','$enc')";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$aids = explode(',',$line['aidlist']);
			$paid = $aids[$_POST['course']];
			if ((intval($line['forceregen']) & (1<<intval($_POST['course'])))>0) {
				$query = "DELETE FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='$paid' LIMIT 1";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}

			$query = "UPDATE imas_users SET lastaccess=$now WHERE id=$userid";
		 	$result = mysql_query($query) or die("Query failed : " . mysql_error());

			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$pcid&id=$paid");
			exit;

		//} else {
		//	echo "You've already taken this diagnostic.  <a href=\"index.php?id=$diagid\">Back</a>\n";
		//	exit;
		//}
	}
	
	$eclass = $sel1[$_POST['course']] . '@' . $_POST['teachers'];
	
	$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, lastaccess) ";
	$query .= "VALUES ('$diagSID','{$_POST['passwd']}',10,'{$_POST['firstname']}','{$_POST['lastname']}','$eclass',$now);";
	mysql_query($query) or die("Query failed : " . mysql_error());
	$userid = mysql_insert_id();
	$query = "INSERT INTO imas_students (userid,courseid,section) VALUES ('$userid','$pcid','{$_POST['teachers']}');";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$sessiondata['mathdisp'] = $_POST['mathdisp'];//1;
	$sessiondata['graphdisp'] = $_POST['graphdisp'];//1;
	$sessiondata['useed'] = 1;
	$sessiondata['isdiag'] = $diagid;
	$enc = base64_encode(serialize($sessiondata));
	if (!empty($_POST['tzname'])) {
		$tzname = $_POST['tzname'];
	} else {
		$tzname = '';
	}
	$query = "INSERT INTO imas_sessions (sessionid,userid,time,tzoffset,tzname,sessiondata) VALUES ('$sessionid','$userid',$now,'{$_POST['tzoffset']}','$tzname','$enc')";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$aids = explode(',',$line['aidlist']);
	$paid = $aids[$_POST['course']];
	
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$pcid&id=$paid");
	exit;
}


//allow custom login page for specific diagnostics
if (file_exists((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'')."diag$diagid.php")) {
	require((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'')."diag$diagid.php");
} else {
$nologo = true;
$infopath = isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'';
$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/{$infopath}infopages.css\" type=\"text/css\">\n";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/jstz_min.js\" ></script>";
require("../header.php");
$pagetitle =$line['name'];
require((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'../')."infoheader.php");
?>
<div style="margin-left: 30px">
<form method=post action="index.php?id=<?php echo $diagid; ?>">
<span class=form><?php echo $line['idprompt']; ?></span> <input class=form type=text size=12 name=SID><BR class=form>
<span class=form><?php echo _('Enter First Name:'); ?></span> <input class=form type=text size=20 name=firstname><BR class=form>
<span class=form><?php echo _('Enter Last Name:'); ?></span> <input class=form type=text size=20 name=lastname><BR class=form>

<script type="text/javascript">
var teach = new Array();

<?php
	
	$sel2 = explode(';',$line['sel2list']);
	foreach ($sel2 as $k=>$v) {
		echo "teach[$k] = new Array('".implode("','",explode('~',$sel2[$k]))."');\n";
	}
?>

function getteach() {
	var classbox = document.getElementById("course");
	var cl = classbox.options[classbox.selectedIndex].value;
	var teachbox = document.getElementById("teachers");
	if (cl > -1) {
		var list = teach[cl];
		teachbox.options.length = 0;
		for(i=0;i<list.length;i++)
		{
			teachbox.options[i] = new Option(list[i],list[i]);
		}
	}
}

</script>

<span class=form><?php printf(_('Select your %s'), $line['sel1name']); ?></span><span class=formright>
<select name="course" id="course" onchange="getteach()">
<option value="-1"><?php printf(_('Select a %s'), $line['sel1name']); ?></option>
<?php
for ($i=0;$i<count($sel1);$i++) {
	echo "<option value=\"$i\">{$sel1[$i]}</option>\n";
}
?>
</select></span><br class=form>

<span class=form><?php printf(_('Select your %s'), $line['sel2name']); ?></span><span class=formright>
<select name="teachers" id="teachers">
<option value="not selected"><?php printf(_('Select a %s first'), $line['sel1name']); ?></option>
</select></span><br class=form>

<?php
	if (!$noproctor) {
		echo "<b>", _('This test can only be accessed from this location with an access password'), "</b></br>\n";
		echo "<span class=form>", _('Access password:'), "</span>  <input class=form type=password size=40 name=passwd><BR class=form>";
	}
?>
<input type="hidden" id="tzoffset" name="tzoffset" value=""> 
<input type="hidden" id="tzname" name="tzname" value=""> 
<script>
  var thedate = new Date();  
  document.getElementById("tzoffset").value = thedate.getTimezoneOffset();  
  var tz = jstz.determine(); 
  document.getElementById("tzname").value = tz.name();
</script>	
<div id="submit" class="submit" style="display:none"><input type=submit value='<?php echo _('Access Diagnostic'); ?>'></div>
<input type=hidden name="mathdisp" id="mathdisp" value="2" />
<input type=hidden name="graphdisp" id="graphdisp" value="2" />
<?php
$allowreentry = ($line['public']&4);
$pws = explode(';',$line['pws']);
if ($noproctor && count($pws)>1 && trim($pws[1])!='' && (!$allowreentry || $line['reentrytime']>0)) {
	echo "<p>", _('No access code is required for this diagnostic.  However, if your testing window has expired, a proctor can enter a password to allow reaccess to this test.'), "</br>\n";
	echo "<span class=form>", _('Override password'), ":</span>  <input class=form type=password size=40 name=passwd><BR class=form>";
}
?>
</form>

<div id="bsetup">JavaScript is not enabled. JavaScript is required for <?php echo $installname; ?>. Please enable JavaScript and reload this page</div>

<script type="text/javascript">
function determinesetup() {
	document.getElementById("submit").style.display = "block";
	if (MathJaxCompatible && !ASnoSVG) {
		document.getElementById("bsetup").innerHTML = "Browser setup OK";
	} else {
		document.getElementById("bsetup").innerHTML = "Using image-based display";
	}
	if (MathJaxCompatible) {
		document.getElementById("mathdisp").value = "1";
	} 
	if (!ASnoSVG) {
		document.getElementById("graphdisp").value = "1";
	}
}
var existingonload = window.onload;
if (existingonload) {
	window.onload = function() {existingonload(); determinesetup();}
} else {
	window.onload = determinesetup;
}
</script>
<hr/><div class=right style="font-size:70%;">Built on <a href="http://www.imathas.com">IMathAS</a> &copy; 2006-2014 David Lippman</div>
</div>
</body>
</html>
<?php
}
?>
