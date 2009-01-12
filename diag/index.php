<?php
	require("../config.php");

	if (isset($sessionpath)) { session_save_path($sessionpath);}
 	ini_set('session.gc_maxlifetime',86400);
	session_start();
	$sessionid = session_id();
	
	if (!isset($_GET['id'])) {
		$query = "SELECT id,name FROM imas_diags WHERE public=3 OR public=7";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		//echo "<html><body><h1>Diagnostics</h1><ul>";
		$nologo = true;
		$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages.css\" type=\"text/css\">\n";
		require("../header.php");
		$pagetitle = "Diagnostics";
		require("../infoheader.php");
		echo <<<END
<img class="floatleft" src="$imasroot/img/ruler.jpg"/>
<div class="content">
<h2>Available Diagnostics</h2>
<ul class="nomark">
END;
		if (mysql_num_rows($result)==0) {
			echo "<li>No diagnostics are available through this page at this time</li>";
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
		echo "<html><body>This diagnostic is not currently available to be taken</body></html>";
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
	   header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php?id=$diagid");
	   exit;
	}

if (isset($_POST['SID'])) {
	$_POST['SID'] = trim(str_replace('-','',$_POST['SID']));
	if (trim($_POST['SID'])=='' || trim($_POST['firstname'])=='' || trim($_POST['lastname'])=='') {
		echo "<html><body>Please enter your ID, first name, and lastname.  <a href=\"index.php?id=$diagid\">Try Again</a>\n";
			exit; 
	}
	$query = "SELECT entryformat from imas_diags WHERE id='$diagid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$entryformat = mysql_result($result,0,0);
	$entrytype = substr($entryformat,0,1); //$entryformat{0};
	$entrydig = substr($entryformat,1); //$entryformat{1};
	$pattern = '/^';
	if ($entrytype=='C') {
		$pattern .= '\w';
	} else if ($entrytype=='D') {
		$pattern .= '\d';
	}
	if ($entrydig==0) {
		$pattern .= '+';
	} else {
		$pattern .= '{'.$entrydig.'}';
	}
	$pattern .= '$/';
	if (!preg_match($pattern,$_POST['SID'])) {
		echo "<html><body>Your ID is not valid.  It should contain ";
		if ($entrydig>0) {
			echo $entrydig.' ';
		}
		if ($entrytype=='C') {
			echo 'letters or numbers';
		} else if ($entrytype=='D') {
			echo 'numbers';
		}
		echo " <a href=\"index.php?id=$diagid\">Try Again</a>\n";
		exit;
	}
	
	if ($_POST['course']==-1) {
		echo "<html><body>Please select a {$line['sel1name']} and {$line['sel2name']}.  <a href=\"index.php?id=$diagid\">Try Again</a>\n";
			exit; 
	}
	if (!$noproctor) {
		
		$pws = explode(',',$line['pws']);
		if (!in_array($_POST['passwd'],$pws) || $line['pws']=='') {
			echo "<html><body>Error, password incorrect.  <a href=\"index.php?id=$diagid\">Try Again</a>\n";
			exit;
		}
	}
	$cnt = 0;
	$now = time();
	$query = "SELECT id FROM imas_users WHERE SID='{$_POST['SID']}~$diagqtr~$pcid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$userid = mysql_result($result,0,0);
		$allowreentry = ($line['public']&4);
		if (!$allowreentry) {
			$aids = explode(',',$line['aidlist']);
			$paid = $aids[$_POST['course']];
			$query = "SELECT id FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='$paid'";
			$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($r2)>0) {
				echo "You've already taken this diagnostic.  <a href=\"index.php?id=$diagid\">Back</a>\n";
				exit;
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
			$query = "INSERT INTO imas_sessions VALUES ('$sessionid','$userid',$now,'{$_POST['tzoffset']}','$enc')";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$aids = explode(',',$line['aidlist']);
			$paid = $aids[$_POST['course']];
			if ((intval($line['forceregen']) & (1<<intval($_POST['course'])))>0) {
				$query = "DELETE FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='$paid' LIMIT 1";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}

			$query = "UPDATE imas_users SET lastaccess=$now WHERE id=$userid";
		 	$result = mysql_query($query) or die("Query failed : " . mysql_error());

			header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$pcid&id=$paid");
			exit;

		//} else {
		//	echo "You've already taken this diagnostic.  <a href=\"index.php?id=$diagid\">Back</a>\n";
		//	exit;
		//}
	}
	
	$eclass = $sel1[$_POST['course']] . '@' . $_POST['teachers'];
	
	$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, lastaccess) ";
	$query .= "VALUES ('{$_POST['SID']}~$diagqtr~$pcid','none',10,'{$_POST['firstname']}','{$_POST['lastname']}','$eclass',$now);";
	mysql_query($query) or die("Query failed : " . mysql_error());
	$userid = mysql_insert_id();
	$query = "INSERT INTO imas_students (userid,courseid) VALUES ('$userid','$pcid');";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$sessiondata['mathdisp'] = $_POST['mathdisp'];//1;
	$sessiondata['graphdisp'] = $_POST['graphdisp'];//1;
	$sessiondata['useed'] = 1;
	$sessiondata['isdiag'] = $diagid;
	$enc = base64_encode(serialize($sessiondata));
	$query = "INSERT INTO imas_sessions VALUES ('$sessionid','$userid',$now,'{$_POST['tzoffset']}','$enc')";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$aids = explode(',',$line['aidlist']);
	$paid = $aids[$_POST['course']];
	
	header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$pcid&id=$paid");
	exit;
}

/*
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo $line['name']; ?></title>
<style type="text/css">
<!--
@import url("../imas.css");
-->
</style>
<script src="<?php echo $imasroot;?>/javascript/mathgraphcheck.js" type="text/javascript"></script>
</head>
<body>
*/
//allow custom login page for specific diagnostics
if (file_exists("diag$diagid.php")) {
	require("diag$diagid.php");
} else {
$nologo = true;
$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages.css\" type=\"text/css\">\n";
require("../header.php");
$pagetitle =$line['name'];
require("../infoheader.php");
?>
<div style="margin-left: 30px">
<form method=post action="index.php?id=<?php echo $diagid; ?>">
<span class=form><?php echo $line['idprompt']; ?></span> <input class=form type=text size=12 name=SID><BR class=form>
<span class=form>Enter First Name:</span> <input class=form type=text size=20 name=firstname><BR class=form>
<span class=form>Enter Last Name:</span> <input class=form type=text size=20 name=lastname><BR class=form>

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

<span class=form>Select your <?php echo $line['sel1name']; ?></span><span class=formright>
<select name="course" id="course" onchange="getteach()">
<option value="-1">Select a <?php echo $line['sel1name']; ?></option>
<?php
for ($i=0;$i<count($sel1);$i++) {
	echo "<option value=\"$i\">{$sel1[$i]}</option>\n";
}
?>
</select></span><br class=form>

<span class=form>Select your <?php echo $line['sel2name']; ?></span><span class=formright>
<select name="teachers" id="teachers">
<option value="not selected">Select a <?php echo $line['sel1name']; ?> first</option>
</select></span><br class=form>

<?php
	if (!$noproctor) {
		echo "<b>This test can only be accessed from this location with an access password</b></br>\n";
		echo "<span class=form>Access password:</span>  <input class=form type=password size=40 name=passwd><BR class=form>";
	}
?>
<input type=hidden id=tzoffset name=tzoffset value="">
<script>
  var thedate = new Date();  
  document.getElementById("tzoffset").value = thedate.getTimezoneOffset();  
</script>	
<div id="submit" class="submit" style="display:none"><input type=submit value='Access Diagnostic'></div>
<input type=hidden name="mathdisp" id="mathdisp" value="2" />
<input type=hidden name="graphdisp" id="graphdisp" value="2" />
</form>

<div id="bsetup">JavaScript is not enabled. JavaScript is required for <?php echo $installname; ?>. Please enable JavaScript and reload this page</div>

<script type="text/javascript">
function determinesetup() {
	document.getElementById("submit").style.display = "block";
	if (!AMnoMathML && !ASnoSVG) {
		document.getElementById("bsetup").innerHTML = "Browser setup OK";
	} else {
		document.getElementById("bsetup").innerHTML = "Using image-based display";
	}
	if (!AMnoMathML) {
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
<hr/><div class=right style="font-size:70%;">Built on <a href="http://imathas.sourceforge.net">IMathAS</a> &copy; 2006 David Lippman</div>
</div>
</body>
</html>
<?php
}
?>
