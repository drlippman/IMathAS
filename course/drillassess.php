<?php
//IMathAS:  Drill Assess player (updated quickdrill)
//(c) 2011 David Lippman

require("../init.php");
require("../assessment/displayq2.php");


if (!isset($teacherid) && !isset($studentid) && !isset($tutorid)) {
	echo _("You don't have authority to access this item");
	exit;
}
if (empty($_GET['cid']) || empty($_GET['daid'])) {
	echo _("Invalid course id or drill assessment id");
	exit;
}

$pagetitle = _("Drill Assessment");

$cid = intval($_GET['cid']);
$daid = intval($_GET['daid']);

//DB $query = "SELECT * FROM imas_drillassess WHERE id='$daid' AND courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$stm = $DBH->prepare("SELECT * FROM imas_drillassess WHERE id=:id AND courseid=:courseid");
$stm->execute(array(':id'=>$daid, ':courseid'=>$cid));
if ($stm->rowCount()==0) {
	echo _("Invalid drill assessment id");
	exit;
}
//DB $dadata = mysql_fetch_array($result, MYSQL_ASSOC);
$dadata = $stm->fetch(PDO::FETCH_ASSOC);
$n = $dadata['n'];
$sa = $dadata['showtype'];
$showscore = ($sa==0 || $sa==1 || $sa==4);
$scoretype = $dadata['scoretype'];
$showtostu = $dadata['showtostu'];
$classbests = explode(',',$dadata['classbests']);
if ($scoretype{0}=='t') {
	$mode = 'cntdown';
	$torecord = 'cc';   //count  correct
} else {
	$mode = 'cntup';
	$stopattype = $scoretype{1};  //a: attempted, c: correct, s: streak
	$torecord = $scoretype{2}; //t: time, c: total count
}
$itemids = explode(',',$dadata['itemids']);
$itemdescr = explode(',',$dadata['itemdescr']);

//declare some globals to make things work
$scores = array();
$lastanswers = array();
$rawscores = array();

//DB $query = "SELECT * FROM imas_drillassess_sessions WHERE drillassessid='$daid' AND userid='$userid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$stm = $DBH->prepare("SELECT * FROM imas_drillassess_sessions WHERE drillassessid=:drillassessid AND userid=:userid");
$stm->execute(array(':drillassessid'=>$daid, ':userid'=>$userid));
if ($stm->rowCount()==0) {
	//new
	$curitem = -1;
	$seed = rand(1,9999);
	$scorerec = array();
	$scorerecarr = serialize($scorerec);
	//DB $query = "INSERT INTO imas_drillassess_sessions (drillassessid,userid,scorerec) VALUES ('$daid','$userid','$scorerecarr')";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("INSERT INTO imas_drillassess_sessions (drillassessid,userid,scorerec) VALUES (:drillassessid, :userid, :scorerec)");
	$stm->execute(array(':drillassessid'=>$daid, ':userid'=>$userid, ':scorerec'=>$scorerecarr));
	$starttime = 0;
} else {
	//DB $sessdata = mysql_fetch_array($result, MYSQL_ASSOC);
	$sessdata = $stm->fetch(PDO::FETCH_ASSOC);
	$curitem = $sessdata['curitem'];
	$curitemid = $itemids[$curitem];
	$seed = $sessdata['seed'];
	if ($sessdata['curscores']=='') {
		$curscores = array();
	} else {
		$curscores = explode(',',$sessdata['curscores']);
	}
	$starttime = $sessdata['starttime'];
	$scorerec = unserialize($sessdata['scorerec']);
}

//score a submitted question
$showans = false;
if (isset($_GET['score'])) {
	list($score,$rawscore) = scoreq(0,$curitemid,$seed,$_POST['qn0']);
	$scores[0] = $score;
	$rawscores[0] = $rawscore;
	//DB $lastanswers[0] = stripslashes($lastanswers[0]);
	$page_scoreMsg =  printscore($score,$curitemid,$seed);
	if (getpts($score)<.99 && $sa==0) {
		$showans = true;
	} else if (getpts($score)<.99 && $sa==4) {
		$lastanswers = array();
	} else {
		$lastanswers = array();
		$seed = rand(1,9999);
	}
	$curscores[] = getpts($score);
	$scorelist = implode(',',$curscores);
	//DB $query = "UPDATE imas_drillassess_sessions SET curscores='$scorelist',seed='$seed' WHERE id='{$sessdata['id']}'";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_drillassess_sessions SET curscores=:curscores,seed=:seed WHERE id=:id");
	$stm->execute(array(':curscores'=>$scorelist, ':seed'=>$seed, ':id'=>$sessdata['id']));
	if ($mode=='cntdown') {
		$page_scoreMsg .= "<p>" . sprintf(_("Current: %d question(s) correct"), countcorrect($curscores)) . "</p>";
	} else if ($stopattype=='a') {
		$page_scoreMsg .= "<p>" . sprintf(_("Current: %d question(s) attempted"), count($curscores)) . "</p>";
	} else if ($stopattype=='c') {
		$page_scoreMsg .= "<p>" . sprintf(_("Current: %d question(s) correct out of %d attempt(s)"), countcorrect($curscores), count($curscores)) . "</p>";
	} else if ($stopattype=='s') {
		$page_scoreMsg .= "<p>" . sprintf(_("Current: %d question streak (correct in a row) out of %d attempt(s)"), countstreak($curscores), count($curscores)) . "</p>";
	}

} else {
	$page_scoreMsg = '';
}

if (isset($_GET['start'])) {
	//start a new drill on this item from the itemlist
	$curitem = intval($_GET['start']);
	$curitemid = $itemids[$curitem];
	$starttime = time();
	$curscores = array();
	$scorelist = implode(',',$curscores);
	$seed = rand(1,9999);
	//DB $query = "UPDATE imas_drillassess_sessions SET curscores='$scorelist',seed='$seed',starttime=$starttime,curitem=$curitem WHERE id='{$sessdata['id']}'";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_drillassess_sessions SET curscores=:curscores,seed=:seed,starttime=:starttime,curitem=:curitem WHERE id=:id");
	$stm->execute(array(':curscores'=>$scorelist, ':seed'=>$seed, ':starttime'=>$starttime, ':curitem'=>$curitem, ':id'=>$sessdata['id']));
}

//check on time
$timesup = false;
$now = time();
if ($mode=='cntup') {
	$cur = $now - $starttime;
} else if ($mode=='cntdown') {
	$cur = $n - ($now - $starttime);
}
if ($mode=='cntdown' && ($cur <=0 || isset($_GET['superdone']))) {
	$timesup = true;
}
$timemsg = '';
if ($cur > 3600) {
	$hours = floor($cur/3600);
	$cur = $cur - 3600*$hours;
	$timemsg .= sprintf(_("%d hours, "), $hours);
} else { $hours = 0;}
if ($cur > 60) {
	$minutes = floor($cur/60);
	$cur = $cur - 60*$minutes;
	$timemsg .= sprintf(_("%d minutes, "), $minutes);
} else {$minutes=0;}
$seconds = $cur;
$timemsg .= sprintf(_("%d seconds."), $seconds);
//are we done?
$drillisdone = false;
if ($curitem > -1 && (($mode=='cntdown' && $timesup) ||
		($mode=='cntup' && (
			($stopattype=='a' && count($curscores)==$n) ||
			($stopattype=='c' && countcorrect($curscores)==$n) ||
			($stopattype=='s' && countstreak($curscores)==$n)
			)))) {
	$drillisdone = true;
	$isnewpbest = false;
	$isnewclassbest = false;
	$scoremsg = '';
	if ($stopattype=='a') {
		$scoremsg .= sprintf(_("%d questions completed "), $n);
	} else if ($stopattype=='c') {
		$scoremsg .= sprintf(_("%d questions answered correctly "), $n);
	} else if ($stopattype=='s') {
		$scoremsg .= sprintf(_("%d question streak completed "), $n);
	}
	if ($torecord=='cc') {
		$torecscore = countcorrect($curscores);
		if (!isset($scorerec[$curitem]) || $torecscore > max($scorerec[$curitem])) {
			$isnewpbest = true;
		}
		if (!isset($teacherid) && ($classbests[$curitem]==-1 || $torecscore > $classbests[$curitem])) {
			$classbests[$curitem] = $torecscore;
			$isnewclassbest = true;
		}
		$scoremsg .= sprintf(_("%d questions answered correctly in %d seconds"), $torecscore, $n);
	} else if ($torecord=='c') {
		$torecscore = count($curscores);
		if (!isset($scorerec[$curitem]) || $torecscore < min($scorerec[$curitem])) {
			$isnewpbest = true;
		}
		if (!isset($teacherid) && ($classbests[$curitem]==-1 || $torecscore < $classbests[$curitem])) {
			$classbests[$curitem] = $torecscore;
			$isnewclassbest = true;
		}
		$scoremsg .= sprintf(_("out of %d questions attempted"), $torecscore);
	} else if ($torecord=='t') {
		$torecscore = $now - $starttime;
		if (!isset($scorerec[$curitem]) || $torecscore < min($scorerec[$curitem])) {
			$isnewpbest = true;
		}
		if (!isset($teacherid) && ($classbests[$curitem]==-1 || $torecscore < $classbests[$curitem])) {
			$classbests[$curitem] = $torecscore;
			$isnewclassbest = true;
		}
		$scoremsg .= sprintf(_("in %s"), $timemsg);
	}

	$scorerec[$curitem][] = $torecscore;
	$scorerecarr = serialize($scorerec);
	//DB $query = "UPDATE imas_drillassess_sessions SET curitem=-1,scorerec='$scorerecarr' WHERE id='{$sessdata['id']}'";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_drillassess_sessions SET curitem=-1,scorerec=:scorerec WHERE id=:id");
	$stm->execute(array(':scorerec'=>$scorerecarr, ':id'=>$sessdata['id']));
	if ($isnewclassbest) {
		$bestarr = implode(',',$classbests);
		//DB $query = "UPDATE imas_drillassess SET classbests='$bestarr' WHERE id='$daid'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_drillassess SET classbests=:classbests WHERE id=:id");
		$stm->execute(array(':classbests'=>$bestarr, ':id'=>$daid));
	}
}

$showtips = isset($CFG['AMS']['showtips'])?$CFG['AMS']['showtips']:2;
$useeqnhelper = isset($CFG['AMS']['eqnhelper'])?$CFG['AMS']['eqnhelper']:0;
$flexwidth = true;
require("../assessment/header.php");
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">" . Sanitize::encodeStringForDisplay($coursename) . "</a> ";
echo "&gt; " . _("Drill Assessment") . "</div>";

echo '<div id="headerdrillassess" class="pagetitle"><h2>' . _("Drill Assessment") . '</h2></div>';

echo '<div class="intro">';
echo '<b>' . _("Goal: ") . '</b>';
if ($mode == 'cntdown') {
	$timelimitmin = floor($n/60);
	$timelimitsec = $n - $timelimitmin*60;
	echo _('Answer as many questions correctly as possible in ');
	if ($timelimitmin>0) {
		echo $timelimitmin . ' ' . (($timelimitmin>1) ? _('minutes') : _('minute'));
	}
	if ($timelimitsec>0) {
		echo ' '.$timelimitsec . ' ' . (($timelimitsec>1) ? _('seconds') : _('second'));
	}
} else {
	if ($stopattype=='a') {
		echo sprintf(_("Attempt %d questions"), Sanitize::onlyInt($n));
	} else if ($stopattype=='c') {
	    echo sprintf(_("Get %d questions correct"), Sanitize::onlyInt($n));
	} else if ($stopattype=='s') {
	    echo sprintf(_("Get a streak of %d questions correct in a row"), Sanitize::onlyInt($n));
	}
	if ($torecord=='t') {
		echo ' ' . _('in the shortest time possible');
	} else if ($torecord=='c') {
		echo ' ' . _('in the fewest total attempts');
	}
}
echo '</div>';

//display navigation header
echo '<div class="navbar" style="width:200px">';
echo '<h4>' . _('Drills') . '</h4>';
echo '<ul class="qlist">';
foreach ($itemdescr as $qn=>$descr) {
	echo '<li>';
	if ($qn==$curitem) {
		echo '<span class="current">';
	}
	echo "<a href=\"drillassess.php?cid=$cid&daid=$daid&start=$qn\">" . Sanitize::encodeStringForDisplay($descr) . "</a>";
	if ($qn==$curitem) {
		echo '</span>';
	}
	if ($showtostu>0) {
		echo '<ul class="qlist">';
		if (($showtostu&1)==1 && isset($scorerec[$qn])) {
			//show last score
			echo '<li>' . _('Last score') . ': ' . Sanitize::encodeStringForDisplay(dispscore($scorerec[$qn][count($scorerec[$qn])-1])) . '</li>';
		}
		if (($showtostu&2)==2 && isset($scorerec[$qn])) {
			//show best score
			if ($torecord=='cc') {
				echo '<li>' . _('Personal best') . ': ' . dispscore(max($scorerec[$qn])).'</li>';
			} else {
				echo '<li>' . _('Personal best') . ': ' . dispscore(min($scorerec[$qn])).'</li>';
			}
		}
		if (($showtostu&4)==4 && $classbests[$qn]!=-1 ) {
			//show best score
			echo '<li>'. _('Class best') . ': ' . Sanitize::encodeStringForDisplay(dispscore($classbests[$qn])) . '</li>';
		}
		echo '</ul>';
	}
	echo '</li>';
}
echo '</ul></div>';

//begin main display
echo '<div class="inset" style="margin-left:230px;">';

if ($curitem == -1) {
	//haven't started anything yet
	echo _('Select a drill to begin');
} else {
	//show last score if we have one
	if ($page_scoreMsg != '' && $showscore) {
		echo '<div class="review">' . _('Score on last question') . ': '.$page_scoreMsg.'</div>';
	}

	//are we done with this assessment?
	if ($drillisdone) {
		echo "<h4>" . _("Drill Complete") . "</h4>";
		echo "<p>" . Sanitize::encodeStringForDisplay($scoremsg) . "</p>";
		if (($showtostu&2)==2 && $isnewpbest) {
			echo '<p>' . _("Congrats! That's a new personal best!") . '</p>';
		}
		if (($showtostu&4)==4 && $isnewclassbest) {
			echo '<p>' . _("Congrats! That's a new class best!") . '</p>';
		}

	} else {
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
		echo "<div class=right id=timelimitholder>" . _("Time") . ": <span id=\"timer\" style=\"font-size: 120%; color: red;\" ";
		echo ">$hours:$minutes:$seconds</span></div>\n";

		?>
		<script type="text/javascript">
		function focusfirst() {
		   var el = document.getElementById("qn0");
		   if (el != null) {el.focus();}
		}
		initstack.push(focusfirst);
		</script>
		<?php
		//not done with assessment.
		$page_formAction = "drillassess.php?cid=$cid&daid=$daid";
		if ($showans) {
			echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"$page_formAction\"\">\n";
			echo "<p>" . _("Displaying last question with solution") . "<button type=\"submit\" name=\"next\" value=\"New Question\"/>" . _("New Question") . "</button></p>\n";
			echo "</form>\n";
			displayq(0,$curitemid,$seed,2,true,0);
		} else {
			if ($sa==3) {
				$doshowans = 1;
			} else {
				$doshowans = 0;
			}
			echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"$page_formAction&score=true\" onsubmit=\"doonsubmit(this)\">\n";
			displayq(0,$curitemid,$seed,$doshowans,true,0);
			if ($sa==3) {
				echo "<button type=\"submit\" name=\"next\" value=\"Next Question\">" . _("New Question") . "</button>\n";
			} else {
				echo "<button type=\"submit\" name=\"check\" value=\"Check Answer\">" . _("Check Answer") . "</button>\n";
			}
			echo "</form>\n";
		}
	}
}

echo '</div>';
require("../footer.php");



function countcorrect($sca) {
	$corr = 0;
	foreach ($sca as $sc) {
		if ($sc>.99) {
			$corr++;
		}
	}
	return $corr;
}

function countstreak($sca) {
	$corr = 0;
	for ($i=count($sca)-1;$i>-1;$i--) {
		if ($sca[$i]>.99) {
			$corr++;
		} else {
			break;
		}
	}
	return $corr;
}
function dispscore($sc) {
	global $torecord;
	if ($torecord=='t') {
		return formattime($sc);
	} else if ($torecord=='cc') {
		return $sc . ' ' . _('correct');
	} else {
		return $sc . ' ' . _('attempts');
	}
}

function formattime($cur) {
	if ($cur > 3600) {
		$hours = floor($cur/3600);
		$cur = $cur - 3600*$hours;
	} else { $hours = 0;}
	if ($cur > 60) {
		$minutes = floor($cur/60);
		if ($minutes<10) { $minutes = '0'.$minutes;}
		$cur = $cur - 60*$minutes;
	} else {$minutes='00';}
	$seconds = $cur;
	if ($seconds<10) { $seconds = '0'.$seconds;}
	return "$hours:$minutes:$seconds";
}
function getansweights($code,$seed) {
	$foundweights = false;
	$weights = sandboxgetweights($code,$seed);
	if (is_array($weights)) {
		return $weights;
	} else {
		return array(1);
	}
}

function sandboxgetweights($code,$seed) {
	srand($seed);
	eval(interpret('control','multipart',$code));
	if (!isset($answeights)) {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$n = count($anstypes);
		if ($n>1) {
			$answeights = array_fill(0,$n-1,round(1/$n,5));
			$answeights[] = 1-array_sum($answeights);
		} else {
			$answeights = array(1);
		}
	} else if (!is_array($answeights)) {
		$answeights =  explode(',',$answeights);
	}

	return $answeights;
}

function printscore($sc,$qsetid,$seed) {
	global $DBH,$imasroot;
	$poss = 1;
	if (strpos($sc,'~')===false) {
		$sc = str_replace('-1','N/A',$sc);
		$out =  sprintf(_("%s out of %d"), $sc, $poss);
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
		$weightsum = array_sum($ptposs);
		if ($weightsum>1.1) {
			$poss = $weightsum;
		} else {
			$poss = count($ptposs);
		}
		for ($i=0; $i<count($ptposs)-1; $i++) {
			$ptposs[$i] = round($ptposs[$i]/$weightsum*$poss,2);
		}
		//adjust for rounding
		$diff = $poss - array_sum($ptposs);
		$ptposs[count($ptposs)-1] += $diff;

		$pts = getpts($sc,$poss);
		$sc = str_replace('-1','N/A',$sc);
		//$sc = str_replace('~',', ',$sc);
		$scarr = explode('~',$sc);
		foreach ($scarr as $k=>$v) {
			$v = round($v * $poss, 2);
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
		$out =  sprintf(_("%s out of %d (parts: %s)"), $pts, $poss, $sc);
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
	return $bar . $out;
}

function getpts($sc,$poss=1) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) {
			return $sc*$poss;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) {
				$tot+=$s*$poss;
			}
		}
		return round($tot,1);
	}
}
?>
