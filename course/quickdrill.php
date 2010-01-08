<?php
//IMathAS:  "Quick Drill" player
//(c) 2009 David Lippman

//options:  	id=questionsetid
//		cid=courseid (not required)
//		sa=show answer options:  0 - show score and answer if wrong
//					 1 - show score, but don't reshow w answer
//					 2 - don't show score
//					 3 - don't show score; show answer button

require("../validate.php");
require("../assessment/displayq2.php");

$pagetitle = "Quick Drill";

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
	$scores = $sessiondata['drill']['scores'];
} else {
	//first access - load into sessiondata and refresh
	if (empty($_GET['id'])) {
		echo "Error: Need to supply question ID in URL";
		exit;
	} else {
		$sessiondata['drill'] = array();
		$sessiondata['drill']['id'] = $_GET['id'];
	}
	if (!empty($_GET['cid'])) {
		$sessiondata['drill']['cid'] = $_GET['cid'];
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
	if ($sessiondata['drill']['mode']=='cntup' || $sessiondata['drill']['mode']=='cntdown') {
		$sessiondata['drill']['starttime'] = time();
	}
	$sessiondata['coursetheme'] = $coursetheme;
	writesessiondata();
	header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/quickdrill.php");
}

$page_formAction = "quickdrill.php";

$showans = false;
if (isset($_POST['seed'])) {
	$score = scoreq(0,$qsetid,$_POST['seed'],$_POST['qn0']);
	$lastanswers[0] = stripslashes($lastanswers[0]);
	$page_scoreMsg =  printscore($score,$qsetid,$_POST['seed']);
	if (getpts($score)<1 && $sa==0) {
		$showans = true;
	} else {
		unset($lastanswers);
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
}

//$sessiondata['coursetheme'] = $coursetheme;
$flexwidth = true; //tells header to use non _fw stylesheet
$placeinhead = '<style type="text/css">div.question {width: auto;} div.review {width: auto;}</style>';
$useeditor = 1;
require("../assessment/header.php");
if ($cid!=0) {
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
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
	if ($cur <= 0) {
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

if (isset($n) && $curscore==$n) {  //if student has completed their n questions
	//print end-of-drill message for student
	//show time taken	
	echo "All done";
	exit;
}
if ($timesup == true) { //if time has expired
	//print end-of-drill success message for student
	//show total q's correct
	echo "Time's up";
	exit;
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
	echo "		alert(document.getElementById(\"qform\"));";
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
	echo "<div class=right id=timelimitholder>Time: <span id=\"timer\" ";
	echo ">$hours:$minutes:$seconds</span></div>\n";
		
}

if ($page_scoreMsg != '' && $sa < 2) {
	echo '<div class="review">Score on last question: '.$page_scoreMsg;
	echo '</div>';
}

if ($showans) {
	echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit()\">\n";
	echo "<p>Displaying last question with solution <input type=submit name=\"next\" value=\"New Question\"/></p>\n";
	displayq(0,$qsetid,$_POST['seed'],2,true,0);
	echo "</form>\n";
} else {
	if ($sa==3) {
		$doshowans = 1;
	} else {
		$doshowans = 0;
	}
	$seed = rand(1,9999);
	echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit()\">\n";
	echo "<input type=\"hidden\" name=\"seed\" value=\"$seed\" />";
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
	$poss = 1;
	if (strpos($sc,'~')===false) {
		$sc = str_replace('-1','N/A',$sc);
		$out =  "$sc out of $poss";
		$pts = $sc;
		if (!is_numeric($pts)) { $pts = 0;}
	} else {
		$query = "SELECT control FROM imas_questionset WHERE id='$qsetid'";
		$result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		$control = mysql_result($result,0,0);
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
				$pm = 'gchk';
			} else if (!is_numeric($v) || $v==0) { 
				$pm = 'redx';
			} else if (abs($v-$ptposs[$k])<.011) {
				$pm = 'gchk';
			} else {
				$pm = 'ychk';
			}
			$bar = "<img src=\"$imasroot/img/$pm.gif\" />";
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
	return $bar . $out;	
}

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		return $sc;
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
