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
		$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/wamaphome.css\" type=\"text/css\">\n";
		require("../header.php");
		echo <<<END
<div id="logo">
<img src="/img/wamaptxt.gif" alt="WAMAP.org: Washington Mathematics Assessment and Placement"/>
</div>

<ul id="navlist">
<li><a href="/index.php">About Us</a></li>
<li><a href="/info/classroom.html">Classroom</a></li>
<li><a href="/diag/index.php">Diagnostics</a></li>
<li><a href="/info/news.html">News</a></li>
</ul>

<div id="header">
<img class="floatright" src="/img/graph.gif" alt="graph image" />
<div class="vcenter">Diagnostics</div>
</div>
<img class="floatleft" src="/img/ruler.jpg"/>
<div class="content">
<h2>Available Diagnostics</h2>
<ul class="nomark">
END;
		if (mysql_num_rows($result)==0) {
			echo "<li>No diagnostics are available through this page at this time</li>";
		}
		while ($row = mysql_fetch_row($result)) {
			echo "<li><a href=\"/diag/index.php?id={$row[0]}\">{$row[1]}</a></li>";
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
	$diagqtr = $line['term'];
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
	if (trim($_POST['SID'])=='' || trim($_POST['firstname'])=='' || trim($_POST['lastname'])=='') {
		echo "<html><body>Please enter your SID number, first name, and lastname.  <a href=\"index.php?id=$diagid\">Try Again</a>\n";
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
	$_POST['SID'] = str_replace('-','',$_POST['SID']);
	$query = "SELECT id FROM imas_users WHERE SID='{$_POST['SID']}d$diagqtr'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$allowreentry = ($line['public']&4);
		if ($allowreentry) {
			$userid = mysql_result($result,0,0);
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
			

			$query = "UPDATE imas_users SET lastaccess=$now WHERE id=$userid";
		 	$result = mysql_query($query) or die("Query failed : " . mysql_error());

			header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$pcid&id=$paid");
			exit;

		} else {
			echo "You've already taken this diagnostic.  <a href=\"index.php?id=$diagid\">Back</a>\n";
			exit;
		}
	}
	
	$eclass = $sel1[$_POST['course']] . '@' . $_POST['teachers'];
	
	$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, lastaccess) ";
	$query .= "VALUES ('{$_POST['SID']}d$diagqtr','none',10,'{$_POST['firstname']}','{$_POST['lastname']}','$eclass',$now);";
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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo $line['name']; ?></title>
<style type="text/css">
<!--
@import url("../imas.css");
-->
</style>
<script src="<?php echo $imasroot;?>/javascript/ASCIIMathML.js" type="text/javascript"></script>
<script src="<?php echo $imasroot;?>/javascript/ASCIIsvg.js" type="text/javascript"></script>
<script src="<?php echo $imasroot;?>/course/editor/plugins/AsciiSvg/ASCIIsvgAddon.js" type="text/javascript"></script>
</head>
<body>
<h2><?php echo $line['name']; ?></h2>
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
<div class=submit><input type=submit value='Access Diagnostic'></div>
<input type=hidden name="mathdisp" id="mathdisp" value="2" />
<input type=hidden name="graphdisp" id="graphdisp" value="2" />
</form>

<div id="bsetup">This computer is not set up to display this test ideally, but you can continue with image-based display</div>

<script type="text/javascript">
if (!AMnoMathML && !ASnoSVG) {
	document.getElementById("bsetup").innerHTML = "Browser setup OK";
}
if (!AMnoMathML) {
	document.getElementById("mathdisp").value = "1";
} 
if (!ASnoSVG) {
	document.getElementById("graphdisp").value = "1";
}
</script>
<hr/><div class=right style="font-size:70%;">Built on <a href="http://imathas.sourceforge.net">IMathAS</a> &copy; 2006 David Lippman</div>
	
</body>
</html>
