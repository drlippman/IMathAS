<?php
//IMathAS:  "Quick Drill" player
//(c) 2009 David Lippman

require_once(__DIR__ . "/../includes/sanitize.php");

//options:  	id=questionsetid
//		cid=courseid (not required)
//		sa=show answer options:  0 - show score and answer if wrong
//					 1 - show score, but don't reshow w answer
//					 2 - don't show score
//					 3 - don't show score; show answer button
//					 4 - show score, don't show answer, make students redo
//		n=#  do n questions then stop
//		nc=#  do until nc questions are correct then stop
//		t=#  do as many questions as possible in t seconds


if (isset($_GET['public'])) {
	require("../init_without_validate.php");
	if (isset($sessionpath) && $sessionpath!='') { session_save_path($sessionpath);}
	ini_set('session.gc_maxlifetime',86400);
	ini_set('auto_detect_line_endings',true);
	header('P3P: CP="ALL CUR ADM OUR"');
	session_start();
	$_SESSION['publicquickdrill'] = true;
	function writesessiondata() {
		global $sessiondata;
		$_SESSION['data'] = base64_encode(serialize($sessiondata));
	}
	if (!isset($_SESSION['data']) || isset($_GET['reset'])) {
		$sessiondata = array();
	} else {
		$sessiondata = unserialize(base64_decode($_SESSION['data']));
	}
	if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
		$urlmode = 'https://';
	 } else {
		 $urlmode = 'http://';
	 }
	$public = '?public=true';
	$publica = '&public=true';
	$sessiondata['graphdisp'] = 1;
	$sessiondata['mathdisp'] = 2;
} else {
	require("../init.php");
	$public = '';
	$publica = '';
}
if (isset($_GET['reset'])) {
	writesessiondata();
	echo "Session reset";
	exit;
}
require("../assessment/displayq2.php");

$pagetitle = "Quick Drill";

if (isset($_GET['showresults']) && is_array($sessiondata['drillresults'])) {
	$qids = array_keys($sessiondata['drillresults']);
	$list = implode(',', array_map('intval', $qids));
	//DB $query = "SELECT id,description FROM imas_questionset WHERE id IN ($list) ORDER BY description";
	//DB $result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
	$stm = $DBH->query("SELECT id,description FROM imas_questionset WHERE id IN ($list) ORDER BY description");
	$out = '';
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$out .= "<p><b>".Sanitize::encodeStringForDisplay($row[1])."</b><ul>";
		foreach ($sessiondata['drillresults'][$row[0]] as $item) {
			$out .= '<li>';
			if ($item[0]=='n') {
				$out .= $item[1].' questions completed in '.Sanitize::encodeStringForDisplay($item[2]).', score: '.Sanitize::encodeStringForDisplay($item[3]);
			} elseif ($item[0]=='nc') {
				$out .= $item[1].' questions completed correctly in '.Sanitize::encodeStringForDisplay($item[2]).', '.Sanitize::encodeStringForDisplay($item[3]);
			} elseif ($item[0]=='t') {
				$out .= 'Score: '.Sanitize::encodeStringForDisplay($item[2]).', in '.Sanitize::encodeStringForDisplay($item[1]);
			}
			$out .= '</li>';
		}
		$out .= '</ul></p>';
	}
	echo $out;
	if (isset($_GET['email']) && isset($_GET['public']) && !isset($_POST['stuname'])) {
		$addy = 'quickdrill.php?public=true&showresults=true&email='.Sanitize::emailAddress($_GET['email']);
		echo '<p><b>Send results to instructor</b><br/>';
		echo "<form action=\"$addy\" method=\"post\">";
		echo 'Your name: <input type="text" name="stuname" /></p>';
		echo '<input type="submit" value="Send results" />';
		echo '</form>';
	} else if (isset($_GET['email'])) {
		if (isset($_GET['public']) && isset($_POST['stuname'])) {
			$stuname = $_POST['stuname'];
		} else {
			$stuname = $userfullname;
		}
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= "From: $sendfrom\r\n";
		$message  = "<h4>This is an automated message.  Do not respond to this email</h4>\r\n";
		$message .= "<p>Quick Drill Results for ".Sanitize::encodeStringForDisplay($stuname)."</p>";
		$message .= "<p>$out</p>";
		mail(Sanitize::emailAddress($_GET['email']),'QuickDrill Results',$message,$headers);
		echo "<p>Email Sent</p>";
	}
	exit;
}
if (isset($sessiondata['drill']) && empty($_GET['id'])) {
	//load from sessiondata
	$qsetid = $sessiondata['drill']['id'];
	$cid = $sessiondata['drill']['cid'];
	$sa = $sessiondata['drill']['sa'];
	$starttime = $sessiondata['drill']['starttime'];
	$mode = $sessiondata['drill']['mode'];
	if ($mode == 'cntdown') {
		$timelimit = $sessiondata['drill']['time'];
	}
	if (isset($sessiondata['drill']['n'])) {
		$n = $sessiondata['drill']['n'];
	}
	if (isset($sessiondata['drill']['nc'])) {
		$nc = $sessiondata['drill']['nc'];
	}
	$scores = $sessiondata['drill']['scores'];

	$showscore = ($sa==0 || $sa==1 || $sa==4);
	if (($mode=='cntup' || $mode=='cntdown') && $starttime==0)  {
		$sessiondata['drill']['starttime'] = time();
		$starttime = time();
	}

} else {
	//first access - load into sessiondata and refresh
	if (empty($_GET['id']) || $_GET['id']=='new') {
		if ($myrights>10) {
			linkgenerator();
		} else {
			echo "Error: Need to supply question ID in URL";
		}
		exit;
	} else {
		$sessiondata['drill'] = array();
		$sessiondata['drill']['id'] = $_GET['id'];
	}
	if (!empty($_GET['cid'])) {
		$sessiondata['drill']['cid'] = Sanitize::courseId($_GET['cid']);
	}  else {
		$sessiondata['drill']['cid'] = 0;
	}
	if (!empty($_GET['sa'])) {
		$sessiondata['drill']['sa'] = $_GET['sa'];
	} else {
		$sessiondata['drill']['sa'] = 0;
	}
	$sessiondata['drill']['mode'] = 'std';
	$sessiondata['drill']['scores'] = array();

	if (!empty($_GET['t'])) {
		$sessiondata['drill']['time'] = $_GET['t'];
		$sessiondata['drill']['mode'] = 'cntdown';
	}
	if (!empty($_GET['n'])) {
		$sessiondata['drill']['n'] = $_GET['n'];
		$sessiondata['drill']['mode'] = 'cntup';
	}
	if (!empty($_GET['nc'])) {
		$sessiondata['drill']['nc'] = $_GET['nc'];
		$sessiondata['drill']['mode'] = 'cntup';
	}
	if ($sessiondata['drill']['mode']=='cntup' || $sessiondata['drill']['mode']=='cntdown') {
		$sessiondata['drill']['starttime'] = 0;
	}
	if (!isset($sessiondata['drillresults'])) {
		$sessiondata['drillresults'] = array();
	}
	$sessiondata['coursetheme'] = $coursetheme;
	writesessiondata();

	if ($sessiondata['drill']['mode']=='cntup' || $sessiondata['drill']['mode']=='cntdown') {
		echo '<html><body>';
		echo "<a href=\"" . $GLOBALS['basesiteurl'] . "/course/quickdrill.php$public\">Start</a>";
		echo '</body></html>';
	} else {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/quickdrill.php$public");
	}
	exit;
}

$page_formAction = "quickdrill.php$public";

$showans = false;
if (isset($_POST['seed'])) {
	list($score,$rawscores) = scoreq(0,$qsetid,$_POST['seed'],$_POST['qn0']);
	//DB $lastanswers[0] = stripslashes($lastanswers[0]);
	$page_scoreMsg =  printscore($score,$qsetid,$_POST['seed']);
	if (getpts($score)<1 && $sa==0) {
		$showans = true;
		$seed = Sanitize::onlyInt($_POST['seed']);
	} else if (getpts($score)<1 && $sa==4) {
		$seed = Sanitize::onlyInt($_POST['seed']);
		unset($lastanswers);
	} else {
		unset($lastanswers);
		$seed = rand(1,9999);
	}
	$scores[] = $score;
	$sessiondata['drill']['scores'] = $scores;
	writesessiondata();
	$curscore = 0;
	foreach ($scores as $score) {
		$curscore += getpts($score);
	}
} else {
	$page_scoreMsg = '';
	$curscore = 0;
	$seed = rand(1,9999);
}

//$sessiondata['coursetheme'] = $coursetheme;
$flexwidth = true; //tells header to use non _fw stylesheet
$placeinhead = '<style type="text/css">div.question {width: auto;} div.review {width: auto; margin-top: 5px;}</style>';
$useeditor = 1;
require("../assessment/header.php");
if ($cid!=0) {
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; Drill</div>";
}

$timesup = false;
if ($mode=='cntup' || $mode=='cntdown') {
	$now = time();
	if ($mode=='cntup') {
		$cur = $now - $starttime;
	} else if ($mode=='cntdown') {
		$cur = $timelimit - ($now - $starttime);
	}
	if ($mode=='cntdown' && ($cur <=0 || isset($_GET['superdone']))) {
		$timesup = true;
	}
	if ($cur > 3600) {
		$hours = floor($cur/3600);
		$cur = $cur - 3600*$hours;
	} else { $hours = 0;}
	if ($cur > 60) {
		$minutes = floor($cur/60);
		$cur = $cur - 60*$minutes;
	} else {$minutes=0;}
	$seconds = $cur;
}

if (isset($n) && count($scores)==$n && !$showans) {  //if student has completed their n questions
	//print end-of-drill message for student
	//show time taken
	echo "<p>$n questions completed in ";
	if ($hours>0) { echo "$hours hours ";}
	if ($minutes>0) { echo "$minutes minutes ";}
	echo "$seconds seconds</p>";
	echo "<p>Score:  ".Sanitize::onlyFloat($curscore)." out of ".count($scores)." possible</p>";
	$addr = $GLOBALS['basesiteurl'] . "/course/quickdrill.php?id=$qsetid&cid=$cid&sa=$sa&n=$n$publica";
	echo "<p><a href=\"$addr\">Again</a></p>";
	if (!isset($sessiondata['drillresults'][$qsetid])) {
		$sessiondata['drillresults'][$qsetid] = array();
	}
	$sessiondata['drillresults'][$qsetid][] = array("n",$n,"$hours:$minutes:$seconds","$curscore out of ".count($scores));
	writesessiondata();
	require("../footer.php");
	exit;
}

if (isset($nc) && $curscore==$nc) {  //if student has completed their nc questions correctly
	//print end-of-drill message for student
	//show time taken
	echo "<p>$nc questions completed correctly in ";
	if ($hours>0) { echo "$hours hours ";}
	if ($minutes>0) { echo "$minutes minutes ";}
	echo "$seconds seconds</p>";

	echo "<p>".count($scores)." tries used</p>";
	$addr = $GLOBALS['basesiteurl'] . "/course/quickdrill.php?id=$qsetid&cid=$cid&sa=$sa&nc=$nc$publica";
	echo "<p><a href=\"$addr\">Again</a></p>";
	if (!isset($sessiondata['drillresults'][$qsetid])) {
		$sessiondata['drillresults'][$qsetid] = array();
	}
	$sessiondata['drillresults'][$qsetid][] = array("nc",$nc,"$hours:$minutes:$seconds",count($scores).' tries used');
	writesessiondata();
	require("../footer.php");
	exit;
}
if ($timesup == true) { //if time has expired
	//print end-of-drill success message for student
	//show total q's correct
	$cur = $timelimit;
	if ($cur > 3600) {
		$hours = floor($cur/3600);
		$cur = $cur - 3600*$hours;
	} else { $hours = 0;}
	if ($cur > 60) {
		$minutes = floor($cur/60);
		$cur = $cur - 60*$minutes;
	} else {$minutes=0;}
	$seconds = $cur;
	echo "<p>Score:  ".Sanitize::onlyFloat($curscore)." out of ".count($scores)." possible</p>";
	echo "<p>In ";
	if ($hours>0) { echo "$hours hours ";}
	if ($minutes>0) { echo "$minutes minutes ";}
	echo "$seconds seconds</p>";
	$addr = $GLOBALS['basesiteurl'] . "/course/quickdrill.php?id=$qsetid&cid=$cid&sa=$sa&t=$timelimit$publica";
	echo "<p><a href=\"$addr\">Again</a></p>";
	if (!isset($sessiondata['drillresults'][$qsetid])) {
		$sessiondata['drillresults'][$qsetid] = array();
	}
	$sessiondata['drillresults'][$qsetid][] = array("t","$hours:$minutes:$seconds","$curscore out of ".count($scores));
	writesessiondata();
	require("../footer.php");
	exit;
}

if ($showscore) {
	echo '<div class="review">Current score: '.Sanitize::onlyFloat($curscore)." out of ".count($scores);
	echo '</div>';
}
if ($mode=='cntup' || $mode=='cntdown') {
	echo "<script type=\"text/javascript\">\n";
	echo " hours = $hours; minutes = $minutes; seconds = $seconds; done=false;\n";
	echo " function updatetime() {\n";
	if ($mode=='cntdown') {
		echo "	  seconds--;\n";
	} else if ($mode=='cntup') {
		echo "	  seconds++;\n";
	}
	echo "    if (seconds==0 && minutes==0 && hours==0) {done=true; ";
	echo "		var theform = document.getElementById(\"qform\");";
	echo "		var action = theform.getAttribute(\"action\");";
	echo "		theform.setAttribute(\"action\",action+'&superdone=true');";
	echo "		if (doonsubmit(theform,true,true)) { theform.submit(); } \n";
	//setTimeout('document.getElementById(\"qform\").submit()',1000);} \n";
	echo "		return 0;";
	echo "    }";
	echo "    if (seconds < 0) { seconds=59; minutes--; }\n";
	echo "    if (minutes < 0) { minutes=59; hours--;}\n";
	echo "    if (seconds > 59) { seconds=0; minutes++; }\n";
	echo "    if (minutes > 59) { minutes=0; hours++;}\n";
	echo "	  str = '';\n";
	echo "	  if (hours > 0) { str += hours + ':';}\n";
	echo "    if (hours > 0 && minutes <10) { str += '0';}\n";
	echo "	  if (minutes >0) {str += minutes + ':';}\n";
	echo "	    else if (hours>0) {str += '0:';}\n";
	echo "      else {str += ':';}\n";
	echo "    if (seconds<10) { str += '0';}\n";
	echo "	  str += seconds + '';\n";
	echo "	  document.getElementById('timer').innerHTML = str;\n";
	echo "    if (!done) {setTimeout(\"updatetime()\",1000);}\n";
	echo " }\n";
	//echo " //updatetime();\n";
	echo " initstack.push(updatetime);";
	echo "</script>\n";
	echo "<div class=right id=timelimitholder>Time: <span id=\"timer\" style=\"font-size: 120%; color: red;\" ";
	echo ">$hours:$minutes:$seconds</span></div>\n";
}
?>
<script type="text/javascript">
function focusfirst() {
   var el = document.getElementById("tc0");
   if (el == null) {
   	   el = document.getElementById("qn0");
   }
   if (el == null) {
   	   el = document.getElementById("tc1000");
   }
   if (el == null) {
   	   el = document.getElementById("qn1000");
   }
   if (el != null) {
   	el.focus();
   }
}
initstack.push(focusfirst);
</script>


<?php

if ($page_scoreMsg != '' && $showscore) {
	echo '<div class="review">Score on last question: '.Sanitize::encodeStringForDisplay($page_scoreMsg);
	echo '</div>';
}

if ($showans) {
	echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit(this,true,true)\">\n";
	echo "<p>Displaying last question with solution <input type=submit name=\"next\" value=\"New Question\"/></p>\n";
	displayq(0,$qsetid,$seed,2,true,0);
	echo "</form>\n";
} else {
	if ($sa==3) {
		$doshowans = 1;
	} else {
		$doshowans = 0;
	}
	echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit(this,true,true)\">\n";
	echo "<input type=\"hidden\" name=\"seed\" value=\"".Sanitize::encodeStringForDisplay($seed)."\" />";
	displayq(0,$qsetid,$seed,$doshowans,true,0);
	if ($sa==3) {
		echo "<input type=submit name=\"next\" value=\"Next Question\">\n";
	} else {
		echo "<input type=submit name=\"check\" value=\"Check Answer\">\n";
	}
	echo "</form>\n";
}

require("../footer.php");


function getansweights($code,$seed) {
	$foundweights = false;
	if (($p = strpos($code,'answeights'))!==false) {
		$p = strpos($code,"\n",$p);
		$weights = sandboxgetweights($code,$seed);
		if (is_array($weights)) {
			return $weights;
		}

	}
	if (!$foundweights) {
		preg_match('/anstypes\s*=(.*)/',$line['control'],$match);
		$n = substr_count($match[1],',')+1;
		if ($n>1) {
			$weights = array_fill(0,$n-1,round(1/$n,3));
			$weights[] = 1-array_sum($line['answeights']);
			return $weights;
		} else {
			return array(1);
		}
	}
}

function sandboxgetweights($code,$seed) {
	srand($seed);
	eval(interpret('control','multipart',$code));
	if (!isset($answeights)) {
		return false;
	} else if (is_array($answeights)) {
		return $answeights;
	} else {
		return explode(',',$answeights);
	}
}

function printscore($sc,$qsetid,$seed) {
	global $DBH,$imasroot;
	$poss = 1;
	if (strpos($sc,'~')===false) {
		$sc = str_replace('-1','N/A',$sc);
		$out =  "$sc out of $poss";
		$pts = $sc;
		if (!is_numeric($pts)) { $pts = 0;}
	} else {
		//DB $query = "SELECT control FROM imas_questionset WHERE id='$qsetid'";
		//DB $result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		//DB $control = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT control FROM imas_questionset WHERE id=:id");
		$stm->execute(array(':id'=>$qsetid));
		$control = $stm->fetchColumn(0);
		$ptposs = getansweights($control,$seed);

		for ($i=0; $i<count($ptposs)-1; $i++) {
			$ptposs[$i] = round($ptposs[$i]*$poss,2);
		}
		//adjust for rounding
		$diff = $poss - array_sum($ptposs);
		$ptposs[count($ptposs)-1] += $diff;


		$pts = getpts($sc);
		$sc = str_replace('-1','N/A',$sc);
		//$sc = str_replace('~',', ',$sc);
		$scarr = explode('~',$sc);
		foreach ($scarr as $k=>$v) {
			if ($ptposs[$k]==0) {
				$pm = 'gchk'; $alt=_('Correct');
			} else if (!is_numeric($v) || $v==0) {
				$pm = 'redx'; $alt=_('Incorrect');
			} else if (abs($v-$ptposs[$k])<.011) {
				$pm = 'gchk'; $alt=_('Correct');
			} else {
				$pm = 'ychk'; $alt=_('Partially correct');
			}
			$bar = "<img src=\"$imasroot/img/$pm.gif\" alt=\"$alt\"/>";
			$scarr[$k] = "$bar $v/{$ptposs[$k]}";
		}
		$sc = implode(', ',$scarr);
		//$ptposs = implode(', ',$ptposs);
		$out =  "$pts out of $poss (parts: $sc)";
	}
	$bar = '<span class="scorebarholder">';
	if ($poss==0) {
		$w = 30;
	} else {
		$w = round(30*$pts/$poss);
	}
	if ($w==0) {$w=1;}
	if ($w < 15) {
	     $color = "#f".dechex(floor(16*($w)/15))."0";
	} else if ($w==15) {
	     $color = '#ff0';
	} else {
	     $color = "#". dechex(floor(16*(2-$w/15))) . "f0";
	}

	$bar .= '<span class="scorebarinner" style="background-color:'.$color.';width:'.$w.'px;">&nbsp;</span></span> ';
	return $bar . Sanitize::encodeStringForDisplay($out);
}

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) {
			return $sc;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) {
				$tot+=$s;
			}
		}
		return round($tot,1);
	}
}

function linkgenerator() {
	global $urlmode;
	$addr = $GLOBALS['basesiteurl'] . "/course/quickdrill.php";
	?>
<html>
<head>
 <title>Quick Drill Link Generator</title>
 <script type="text/javascript">
 var baseaddr = "<?php echo $addr;?>";
 function makelink() {
	 var id = document.getElementById("qid").value;
	 if (id=='') {alert("Question ID is required"); return false;}
	 var cid = document.getElementById("cid").value;
	 var sa = document.getElementById("sa").value;
	 var mode = document.getElementById("type").value;
	 var val = document.getElementById("val").value;
	 if (mode!='none' && val=='') { alert("need to specify N"); return false;}
	 var url = baseaddr + '?id=' + id + '&sa='+sa;
	 if (cid != '') {
		url += '&cid='+cid;
	 }
	 if (mode != 'none') {
		 url += '&'+mode+'='+val;
	 }
	 document.getElementById("output").innerHTML = "<p>URL to use: "+url+"</p><p><a href=\""+url+"\" target=\"_blank\">Try it</a></p>";
 }
 </script>
 </head>
 <body>
 <h2>Quick Drill Link Generator</h2>
 <table border=0>
 <tr><td>Question ID to use:</td><td><input type="text" size="5" id="qid" /></td></tr>
 <tr><td>Course ID (optional):</td><td><input type="text" size="5" id="cid" /></td></tr>
 <tr><td>Show answer option:</td><td><select id="sa">
 	<option value="0">Show score - reshow question with answer if wrong</option>
	<option value="1">Show score - don't reshow question w answer if wrong</option>
	<option value="4">Show score - don't show answer - make student redo same version if missed</option>
	<option value="2">Don't show score at all</option>
	<option value="3">Flash Cards Style: don't show score, but use Show Answer button</option>
	</select></td></tr>
 <tr><td>Behavior:</td><td><select id="type">
 	<option value="none">Just keep asking questions forever</option>
	<option value="n">Do N questions, then stop</option>
	<option value="nc">Do until N questions are correct, then stop</option>
	<option value="t">Do as many questions as possible in N seconds</option>
	</select><br/>
	Where N = <input type="text" size="4" id="val"/></td></tr>
</table>

<input type="button" value="Generate Link" onclick="makelink()"/>

<div id="output"></div>
</body>
</html>


	<?php

}

?>
