<?php
  $init_session_start = true;
	require("../init_without_validate.php");

	if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
		 $urlmode = 'https://';
	 } else {
		 $urlmode = 'http://';
	 }

	// session already started in init
	$sessionid = session_id();

	function decodeSelector($sel) {
		return str_replace(array('@c@','@s@','@t@'), array(',',';','~'), $sel);
	}

	if (!isset($_GET['id'])) {
		//echo "<html><body><h1>Diagnostics</h1><ul>";
		$nologo = true;
        $loadinginfoheader = true;;
		$infopath = isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'';
		$placeinhead = "<link rel=\"stylesheet\" href=\"$staticroot/{$infopath}infopages.css\" type=\"text/css\">\n";
		$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/jstz_min.js\" ></script>";
		require("../header.php");
		$pagetitle = "Diagnostics";
		require((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'../')."infoheader.php");
		echo "<img class=\"floatleft\" src=\"$staticroot/img/ruler.jpg\" alt=\"Picture of a ruler\"/>
		<div class=\"content\">
		<div id=\"headerdiagindex\" class=\"pagetitle\"><h1>", _('Available Diagnostics'), "</h1></div>
		<ul class=\"nomark\">";
		$stm = $DBH->query("SELECT id,name FROM imas_diags WHERE public=3 OR public=7");
		if ($stm->rowCount()==0) {
			echo "<li>", _('No diagnostics are available through this page at this time'), "</li>";
		}
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo "<li><a href=\"$imasroot/diag/index.php?id=" . Sanitize::onlyInt($row[0]) . "\">".Sanitize::encodeStringForDisplay($row[1])."</a></li>";
		}
		echo "</ul></div>";
		require("../footer.php");
		exit;
	}
	$diagid = Sanitize::onlyInt($_GET['id']);
	$stm = $DBH->prepare("SELECT * from imas_diags WHERE id=:id");
	$stm->execute(array(':id'=>$diagid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
    if ($line===false) {
        echo 'Invalid diagnostic id';
        exit;
    }
	$pcid = $line['cid'];
	$diagid = $line['id'];
	if ($line['term']=='*mo*') {
		$diagqtr = date("M y");
	} else if ($line['term']=='*day*') {
		$diagqtr = date("M j y");
	} else {
		$diagqtr = $line['term'];
	}
	$sel1 = array_map('decodeSelector', explode(',',$line['sel1list']));
	$entryformat = $line['entryformat'];

	if ($line['sel1name'][0]=='!') {
		$line['sel1name'] = substr($line['sel1name'], 1);
	} else {
		$line['sel1name'] = sprintf(_('Select your %s'), $line['sel1name']);
	}
	if ($line['sel2name'][0]=='!') {
		$line['sel2name'] = substr($line['sel2name'], 1);
	} else {
		$line['sel2name'] = sprintf(_('Select your %s'), $line['sel2name']);
	}

	if (!($line['public']&1)) {
		echo "<html><body>", _('This diagnostic is not currently available to be taken'), "</body></html>";
		exit;
	}
	$userip = $_SERVER['REMOTE_ADDR'];
	$noproctor = (trim($line['pws'], " ;") === '');
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
	if (!empty($_SESSION)) {
	   $_SESSION = array();
	   if (isset($_COOKIE[session_name()])) {
		   setcookie(session_name(), '', time()-42000, '/', '', false, true);
	   }
	   session_destroy();
	   header('Location: ' . $GLOBALS['basesiteurl'] . "/diag/index.php?id=" . Sanitize::onlyInt($diagid));
	   exit;
	}

if (isset($_POST['SID'])) {
	$_POST['SID'] = trim(str_replace('-','',$_POST['SID']));
	if (trim($_POST['SID'])=='' || trim($_POST['firstname'])=='' || trim($_POST['lastname'])=='') {
		echo "<html><body>", _('Please enter your ID, first name, and lastname.'), "  <a href=\"index.php?id=" . Sanitize::onlyInt($diagid) . "\">", _('Try Again'), "</a>\n";
			exit;
	}

	$entrytype = substr($entryformat,0,1); //$entryformat[0];
	$entrydig = substr($entryformat,1); //$entryformat[1];
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
			echo Sanitize::encodeStringForDisplay($entrydig).' ';
		}
		if ($entrytype=='C') {
			echo _('letters or numbers');
		} else if ($entrytype=='D') {
			echo _('numbers');
		} else if ($entrytype=='E') {
			echo _('an email address');
		}
		echo " <a href=\"index.php?id=" . Sanitize::onlyInt($diagid) . "\">", _('Try Again'), "</a>\n";
		exit;
	}

	if ($_POST['course']==-1) {
		echo "<html><body>", Sanitize::encodeStringForDisplay(sprintf(_('Please make selections for "%1$s" and "%2$s".'), $line['sel1name'], $line['sel2name'])), "  <a href=\"index.php?id=" . Sanitize::onlyInt($diagid) . "\">", _('Try Again'), "</a>\n";
			exit;
	}
    if (!isset($_POST['teachers'])) {
        $_POST['teachers'] = '';
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
	$diagSID = $_POST['SID'].'~'.$diagqtr.'~'.$pcid;
	if ($entrynotunique) {
		$diagSID .= '~'.preg_replace('/\W/','',$sel1[$_POST['course']]);
	}
	if (strlen($diagSID)>50) {
		$diagSID = substr($diagSID,0,50);
	}
	if (!$noproctor) {
		if (!in_array(strtolower($_POST['passwd']),$basicpw) && !in_array(strtolower($_POST['passwd']),$superpw)) {
			$stm = $DBH->prepare("SELECT id,goodfor FROM imas_diag_onetime WHERE code=:code AND diag=:diag");
			$stm->execute(array(':code'=>strtoupper($_POST['passwd']), ':diag'=>$diagid));
			$passwordnotfound = false;
			if ($stm->rowCount()>0) {
				$row = $stm->fetch(PDO::FETCH_NUM);
				if ($row[1]==0) {  //onetime
					$stm = $DBH->prepare("DELETE FROM imas_diag_onetime WHERE id=:id");
					$stm->execute(array(':id'=>$row[0]));
				} else { //set time expiry
					$now = time();
					if ($row[1]<100000000) { //is time its good for - not yet used
						$expiry = $now + $row[1]*60;
						$stm = $DBH->prepare("UPDATE imas_diag_onetime SET goodfor=:goodfor WHERE id=:id");
						$stm->execute(array(':goodfor'=>$expiry, ':id'=>$row[0]));
					} else if ($now<$row[1]) {//is expiry time and we're within it
						//alls good
					} else { //past expiry
						$stm = $DBH->prepare("DELETE FROM imas_diag_onetime WHERE id=:id");
						$stm->execute(array(':id'=>$row[0]));
						$passwordnotfound = true;
					}
				}
			} else {
				$passwordnotfound = true;
			}
			if ($passwordnotfound) {
				$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID");
				$stm->execute(array(':SID'=>$diagSID));
				if ($stm->rowCount()>0 && strtoupper($stm->fetchColumn(0))==strtoupper($_POST['passwd'])) {

				} else {
					echo "<html><body>", _('Error, password incorrect or expired.'), "  <a href=\"index.php?id=" . Sanitize::onlyInt($diagid) . "\">", _('Try Again'), "</a>\n";
					exit;
				}
			}
		}
	}
	$cnt = 0;
	$now = time();

	$aids = explode(',',$line['aidlist']);
	$paid = $aids[$_POST['course']];
	$stm2 = $DBH->prepare("SELECT ver FROM imas_assessments WHERE id=:assessmentid");
	$stm2->execute(array(':assessmentid'=>$paid));
	$aVer = $stm2->fetchColumn(0);

	$query = "SELECT iu.id,istu.id FROM imas_users AS iu ";
	$query .= "LEFT JOIN imas_students AS istu ON iu.id=istu.userid ";
	$query .= "AND istu.courseid=? WHERE iu.SID=?";
	$stm = $DBH->prepare($query);
	$stm->execute(array($pcid, $diagSID));
	if ($stm->rowCount()>0) {
		list($userid, $stuid) = $stm->fetch(PDO::FETCH_NUM);
		if ($stuid == null) { // was unenrolled from course. reenroll
			if (!isset($_POST['timelimitmult'])) {
				$_POST['timelimitmult'] = 1;
			}
			$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,section,timelimitmult) VALUES (:userid, :courseid, :section, :timelimitmult);");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$pcid, ':section'=>$_POST['teachers'], ':timelimitmult'=>$_POST['timelimitmult']));
		}
		$allowreentry = ($line['public']&4);
		if (!in_array(strtolower($_POST['passwd'] ?? ''),$superpw) && (!$allowreentry || $line['reentrytime']>0)) {
			$d = null;
			$stm2 = $DBH->prepare("SELECT id,starttime FROM imas_assessment_sessions WHERE userid=:userid AND assessmentid=:assessmentid");
			$stm2->execute(array(':userid'=>$userid, ':assessmentid'=>$paid));
			if ($stm2->rowCount()>0) {
				$d = $stm2->fetch(PDO::FETCH_NUM);
			} else {
				$stm2 = $DBH->prepare("SELECT userid,starttime FROM imas_assessment_records WHERE userid=:userid AND assessmentid=:assessmentid");
				$stm2->execute(array(':userid'=>$userid, ':assessmentid'=>$paid));
				if ($stm2->rowCount()>0) {
					$d = $stm2->fetch(PDO::FETCH_NUM);
				}
			}
			if ($d !== null) {
				if (!$allowreentry) {
					echo _("You've already taken this diagnostic."), "  <a href=\"index.php?id=" . Sanitize::onlyInt($diagid) . "\">", _('Back'), "</a>\n";
					exit;
				} else {
					$now = time();
					if ($now - $d[1] > 60*$line['reentrytime']) {
						echo _('Your window to complete this diagnostic has expired.'), "  <a href=\"index.php?id=" . Sanitize::onlyInt($diagid) . "\">", _('Back'), "</a>\n";
						exit;
					}
				}
			}
		}
		//if ($allowreentry) {

			$_SESSION['mathdisp'] = $CFG['UP']['mathdisp'] ?? 7;
			$_SESSION['graphdisp'] = $CFG['UP']['graphdisp'] ?? 1;

			$_SESSION['useed'] = 1;
			$_SESSION['isdiag'] = $diagid;
      if ($aVer > 1) {
			  $_SESSION['diag_aver'] = array($aVer, $pcid, $paid);
      } else {
        $_SESSION['diag_aver'] = array($aVer);
      }

			if (!empty($_POST['tzname'])) {
				$tzname = $_POST['tzname'];
			} else {
				$tzname = '';
			}
			$_SESSION['userid'] = $userid;
			$_SESSION['time'] = $now;
			$_SESSION['tzoffset'] = $_POST['tzoffset'];
			$_SESSION['tzname'] = $tzname;

			if ((intval($line['forceregen']) & (1<<intval($_POST['course'])))>0) {
				$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE userid=:userid AND assessmentid=:assessmentid LIMIT 1");
				$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$paid));
				$stm = $DBH->prepare("DELETE FROM imas_assessment_records WHERE userid=:userid AND assessmentid=:assessmentid LIMIT 1");
				$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$paid));
			}
			$stm = $DBH->prepare("UPDATE imas_users SET lastaccess=:lastaccess WHERE id=:id");
			$stm->execute(array(':lastaccess'=>$now, ':id'=>$userid));

			if ($aVer > 1) {
				header(sprintf('Location: %s/assess2/index.php?cid=%s&aid=%d', $GLOBALS['basesiteurl'],
			        Sanitize::onlyInt($pcid), Sanitize::onlyInt($paid)));
			} else {

		    header(sprintf('Location: %s/assessment/showtest.php?cid=%s&id=%d', $GLOBALS['basesiteurl'],
                Sanitize::onlyInt($pcid), Sanitize::onlyInt($paid)));
			}
			exit;

		//} else {
		//	echo "You've already taken this diagnostic.  <a href=\"index.php?id=$diagid\">Back</a>\n";
		//	exit;
		//}
	}

	$eclass = $sel1[$_POST['course']] . '@' . $_POST['teachers'];
	$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, lastaccess) ";
	$query .= "VALUES (:SID, :password, :rights, :FirstName, :LastName, :email, :lastaccess);";
	$stm = $DBH->prepare($query);
	if (!isset($_POST['passwd'])) {
		$_POST['passwd'] = "none";
	}
	$stm->execute(array(':SID'=>$diagSID, ':password'=>$_POST['passwd'], ':rights'=>10,
    ':FirstName'=>Sanitize::stripHtmlTags($_POST['firstname']),
    ':LastName'=>Sanitize::stripHtmlTags($_POST['lastname']),
    ':email'=>Sanitize::stripHtmlTags($eclass),
    ':lastaccess'=>$now));
	$userid = $DBH->lastInsertId();
	if (!isset($_POST['timelimitmult'])) {
		$_POST['timelimitmult'] = 1;
	}
	$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,section,timelimitmult) VALUES (:userid, :courseid, :section, :timelimitmult);");
	$stm->execute(array(':userid'=>$userid, ':courseid'=>$pcid, ':section'=>$_POST['teachers'], ':timelimitmult'=>$_POST['timelimitmult']));

	$_SESSION['mathdisp'] = $CFG['UP']['mathdisp'] ?? 7;
	$_SESSION['graphdisp'] = $CFG['UP']['graphdisp'] ?? 1;
	$_SESSION['useed'] = 1;
    $_SESSION['isdiag'] = $diagid;
    if ($aVer > 1) {
        $_SESSION['diag_aver'] = array($aVer, $pcid, $paid);
    } else {
        $_SESSION['diag_aver'] = array($aVer);
    }
	$enc = base64_encode(serialize($_SESSION));
	if (!empty($_POST['tzname'])) {
		$tzname = $_POST['tzname'];
	} else {
		$tzname = '';
	}
	$_SESSION['userid'] = $userid;
	$_SESSION['time'] = $now;
	$_SESSION['tzoffset'] = $_POST['tzoffset'];
	$_SESSION['tzname'] = $tzname;

	$aids = explode(',',$line['aidlist']);
	$paid = $aids[$_POST['course']];
	if ($aVer > 1) {
		header(sprintf('Location: %s/assess2/index.php?cid=%s&aid=%d', $GLOBALS['basesiteurl'],
	        Sanitize::onlyInt($pcid), Sanitize::onlyInt($paid)));
	} else {
		header(sprintf('Location: %s/assessment/showtest.php?cid=%s&id=%d', $GLOBALS['basesiteurl'],
	        Sanitize::onlyInt($pcid), Sanitize::onlyInt($paid)));
	}
	exit;
}


//allow custom login page for specific diagnostics
if (file_exists((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'')."diag$diagid.php")) {
	require((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'')."diag$diagid.php");
} else {
$nologo = true;
$infopath = isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'';
$placeinhead = "<link rel=\"stylesheet\" href=\"$staticroot/{$infopath}infopages.css\" type=\"text/css\">\n";
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/jstz_min.js\" ></script>";
$flexwidth = true;
require("../header.php");
$pagetitle =$line['name'];
require((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'../')."infoheader.php");
?>
<div style="margin-left: 30px">
<form method=post action="index.php?id=<?php echo Sanitize::onlyInt($diagid); ?>">
<span class=form><?php echo Sanitize::encodeStringForDisplay($line['idprompt']); ?></span> <input class=form type=text size=12 name=SID><BR class=form>
<span class=form><?php echo _('Enter First Name:'); ?></span> <input class=form type=text size=20 name=firstname><BR class=form>
<span class=form><?php echo _('Enter Last Name:'); ?></span> <input class=form type=text size=20 name=lastname><BR class=form>

<script type="text/javascript">
var teach = new Array();

<?php

	$sel2 = explode(';',$line['sel2list']);
	foreach ($sel2 as $k=>$v) {
		$sel2opts = array_map('Sanitize::encodeStringForJavascript', array_map('decodeSelector', explode('~',$sel2[$k])));
		echo "teach[$k] = new Array('".implode("','", $sel2opts)."');\n";
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

<span class=form><?php echo Sanitize::encodeStringForDisplay($line['sel1name']); ?></span><span class=formright>
<select name="course" id="course" onchange="getteach()">
<option value="-1"><?php echo Sanitize::encodeStringForDisplay($line['sel1name']); ?></option>
<?php
for ($i=0;$i<count($sel1);$i++) {
	echo "<option value=\"$i\">".Sanitize::encodeStringForDisplay($sel1[$i])."</option>\n";
}
?>
</select></span><br class=form>

<span class=form><?php echo Sanitize::encodeStringForDisplay($line['sel2name']); ?></span><span class=formright>
<select name="teachers" id="teachers">
<option value="not selected"><?php echo Sanitize::encodeStringForDisplay($line['sel1name']); ?></option>
</select></span><br class=form>

<?php
	if (!$noproctor) {
		$aids = array_map('Sanitize::onlyInt', explode(',',$line['aidlist']));
		$ph = Sanitize::generateQueryPlaceholders($aids);
		$stm = $DBH->prepare("SELECT count(id) FROM imas_assessments WHERE id IN ($ph) AND ABS(timelimit)>0");
		$stm->execute($aids);
		$hasTimeLimit = ($stm->fetchColumn(0)>0);
		echo "<b>", _('This test can only be accessed from this location with an access password'), "</b><br/>\n";
		echo "<span class=form>", _('Access password:'), "</span>  <input class=form type=password size=40 name=passwd><BR class=form>";
		if ($hasTimeLimit) {
			echo "<span class=form>", _('Time limit (if timed):'), "</span>  ";
			echo '<select name=timelimitmult><option value="1">'._('Standard').'</option><option value="1.5">'._('1.5x standard').'</option>';
			echo '<option value="2">'._('2x standard').'</option></select><BR class=form>';
		}

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

<?php
$allowreentry = ($line['public']&4);
$pws = explode(';',$line['pws']);
if ($noproctor && count($pws)>1 && trim($pws[1])!='' && (!$allowreentry || $line['reentrytime']>0)) {
	echo "<p>", _('No access code is required for this diagnostic.  However, if your testing window has expired, a proctor can enter a password to allow reaccess to this test.'), "<br/>\n";
	echo "<span class=form>", _('Override password'), ":</span>  <input class=form type=password size=40 name=passwd><BR class=form>";
}
?>
</form>
<div id="bsetup">JavaScript is not enabled. JavaScript is required for <?php echo $installname; ?>. Please enable JavaScript and reload this page</div>
</div>
<script type="text/javascript">
    $(function() {
        document.getElementById("submit").style.display = "block";
        document.getElementById("bsetup").style.display = "none";
    });
</script>
<hr/>
<div class=right style="font-size:70%;">Built on <a href="http://www.imathas.com">IMathAS</a> &copy; 2006-<?php echo date("Y");?> David Lippman</div>

<?php
require("../footer.php");
}
?>
