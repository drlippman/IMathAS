<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");


	
 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Print Layout";

	
	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION	

}

/******* begin html output ********/
$placeinhead = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/assessment/mathtest.css\"/>\n";
if (isset($_POST['versions'])) {
	$placeinhead .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/assessment/print.css?v=100213\"/>\n";
}
$placeinhead .= "<script src=\"$imasroot/javascript/AMhelpers.js\" type=\"text/javascript\"></script>\n";

$nologo = true;
$cid = $_GET['cid'];
$aid = $_GET['aid'];
if (isset($_POST['mathdisp']) && $_POST['mathdisp']=='text') {
	$sessiondata['mathdisp'] = 0;
} else {
	$sessiondata['mathdisp'] = 2;	
}
if (isset($_POST['mathdisp']) && $_POST['mathdisp']=='tex') {
	$sessiondata['texdisp'] = true;
}
if (isset($_POST['mathdisp']) && $_POST['mathdisp']=='textandimg') {
	$printtwice = 2;
} else {
	$printtwice = 1;
}

$sessiondata['graphdisp'] = 2;
require("../assessment/header.php");

if ($overwriteBody==1) {
	echo $body;
} if (!isset($_POST['versions'])) {
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
	echo "&gt; Print Test</div>\n";
	
	//echo '<div class="cpmid"><a href="printtest.php?cid='.$cid.'&amp;aid='.$aid.'">Generate for printing</a></div>';
	
	echo "<h2>Copy-and-paste Print Version</h2>";
		
	echo '<p>This page will help you create a copy of this assessment that you should be able to cut and ';
	echo 'paste into Word or another word processor and adjust layout for printing</p>';
	
	echo "<form method=post action=\"printlayoutbare.php?cid=$cid&aid=$aid\">\n";
	echo "<p>Number of different versions to generate: <input type=text name=versions value=\"1\"></p>\n";
	echo '<p>Format?  <input type="radio" name="format" value="trad" checked="checked" /> Form A: 1 2 3 Form B: 1 2 3 <input type="radio" name="format" value="inter"/> 1a 1b 2a 2b</p>';
	echo "<p>Generate answer keys? <input type=radio name=keys value=0>No <input type=radio name=keys value=1 checked=1>Yes</p>\n";
	echo "<p>Question separator:  <input type=text name=\"qsep\" value=\"\" /></p>";
	echo "<p>Version separator: <input type=text name=\"vsep\" value=\"+++++++++++++++\" /> </p>";
	echo '<p>Math display: <input type="radio" name="mathdisp" value="img" checked="checked" /> Images <input type="radio" name="mathdisp" value="text"/> Text <input type="radio" name="mathdisp" value="tex"/> TeX <input type="radio" name="mathdisp" value="textandimg"/> Images, then again in text</p>';
	echo '<p>Include question numbers and point values: <input type="checkbox" name="showqn" checked="checked" /> </p>';
	echo "<p><input type=submit value=\"Continue\"></p></form>\n";
	
} else {		

	$query = "SELECT itemorder,shuffle,defpoints,name,intro FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	
	$ioquestions = explode(",",$line['itemorder']);
	$aname = $line['name'];
	$questions = array();
	foreach($ioquestions as $k=>$q) {
		if (strpos($q,'~')!==false) {
			$sub = explode('~',$q);
			if (strpos($sub[0],'|')===false) { //backwards compat
				$questions[] = $sub[array_rand($sub,1)];
			} else {
				$grpqs = array();
				$grpparts = explode('|',$sub[0]);
				array_shift($sub);
				if ($grpparts[1]==1) { // With replacement
					for ($i=0; $i<$grpparts[0]; $i++) {
						$questions[] = $sub[array_rand($sub,1)];
					}
				} else if ($grpparts[1]==0) { //Without replacement
					shuffle($sub);
					for ($i=0; $i<min($grpparts[0],count($sub)); $i++) {
						$questions[] = $sub[$i];
					}
					//$grpqs = array_slice($sub,0,min($grpparts[0],count($sub)));
					if ($grpparts[0]>count($sub)) { //fix stupid inputs
						for ($i=count($sub); $i<$grpparts[0]; $i++) {
							$questions[] = $sub[array_rand($sub,1)];
						}
					}
				}
			}
		} else {
			$questions[] = $q;
		}
	}
	
	$points = array();
	$qn = array();
	$qlist = "'".implode("','",$questions)."'";
	$query = "SELECT id,points,questionsetid FROM imas_questions WHERE id IN ($qlist)";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		if ($row[1]==9999) {
			$points[$row[0]] = $line['defpoints'];
		} else {
			$points[$row[0]] = $row[1];
		}
		$qn[$row[0]] = $row[2];
	}
	
	
	$numq = count($questions);

?>
	<style type="text/css">
	body {
			padding: 0px;
			margin: 0px;
		}
		form {
			padding: 0px;
			margin: 0px;
		}
		div.maintest {
			position: absolute;
			top: 0px;
			left: 0px;
		}
		.pageb {
				clear: both;
				padding: 0px;
				margin: 0px;
				page-break-after: always;
				border-bottom: 1px dashed #aaa;
			}
		div.mainbody {
			margin: 0px;
			padding: 0px;
		}
	</style>

<?php

	include("../assessment/displayq2.php");
	
	
	if (is_numeric($_POST['versions'])) {
		$copies = $_POST['versions'];
	} else {
		$copies = 1;
	}
	//add interlace output
	//add prettyprint along with text-based output option
	$seeds = array();
	for ($j=0; $j<$copies; $j++) {	
		$seeds[$j] = array();
		if ($line['shuffle']&2) {  //set rand seeds
			$seeds[$j] = array_fill(0,count($questions),rand(1,9999));	
		} else {
			for ($i = 0; $i<count($questions);$i++) {
				$seeds[$j][] = rand(1,9999);
			}
		}
	}
	
	for ($pt=0;$pt<$printtwice;$pt++) {
		if ($pt==1) {
			$sessiondata['mathdisp'] = 0;
			echo $_POST['vsep'].'<br/>';;
		
		}
		
		if ($_POST['format']=='trad') {
			for ($j=0; $j<$copies; $j++) {	
				if ($j>0) { echo $_POST['vsep'].'<br/>';}
				
				$headerleft = '';
				$headerleft .= $line['name'];
				if ($copies>1) {
					$headerleft .= ' - Form ' . ($j+1);
				}
				if ((isset($_POST['iname']) || isset($_POST['cname'])) && isset($_POST['aname'])) {
					$headerleft .= "<br/>";
				}
				$headerright = '';
				echo "<div class=q>\n";
				echo "<div class=hdrm>\n";
				
				echo "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
				echo "<div id=intro>{$line['intro']}</div>\n";
				echo "</div>\n";
				echo "</div>\n";
				
				
				for ($i=0; $i<$numq; $i++) {
					if ($i>0) { echo $_POST['qsep'];}
					$sa[$j][$i] = printq($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($_POST['showqn']));
				}
				
			}
		
			if ($_POST['keys']>0) { //print answer keys
				for ($j=0; $j<$copies; $j++) {
					echo $_POST['vsep'].'<br/>';
					echo '<b>Key - Form ' . ($j+1) . "</b>\n";
					echo "<ol>\n";
					for ($i=0; $i<$numq; $i++) {
						echo '<li>';
						if (is_array($sa[$j][$i])) {
							echo printfilter(filter(implode(' ~ ',$sa[$j][$i])));
						} else {
							echo printfilter(filter($sa[$j][$i]));
						}
						echo "</li>\n";
					}
					echo "</ol>\n";
					//if ($_POST['keys']==2) {
					//	echo "<p class=pageb>&nbsp;</p>\n";
					//}
				}
			}
		} else if ($_POST['format']=='inter') {
			
			$headerleft = '';
			$headerleft .= $line['name'];
			if ((isset($_POST['iname']) || isset($_POST['cname'])) && isset($_POST['aname'])) {
				$headerleft .= "<br/>";
			}
			$headerright = '';
			echo "<div class=q>\n";
			echo "<div class=hdrm>\n";
			
			echo "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
			echo "<div id=intro>{$line['intro']}</div>\n";
			echo "</div>\n";
			echo "</div>\n";
			for ($i=0; $i<$numq; $i++) {
				if ($i>0) { echo $_POST['qsep'];}
				for ($j=0; $j<$copies;$j++) {
					if ($j>0) { echo $_POST['qsep'];}
					$sa[] = printq($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($_POST['showqn']));
				}
			}
			if ($_POST['keys']>0) { //print answer keys
				echo $_POST['vsep'].'<br/>';
				echo "<b>Key</b>\n";
				echo "<ol>\n";
				for ($i=0; $i<count($sa); $i++) {
					echo '<li>';
					if (is_array($sa[$i])) {
						echo printfilter(filter(implode(' ~ ',$sa[$i])));
					} else {
						echo printfilter(filter($sa[$i]));
					}
					echo "</li>\n";
				}
				echo "</ol>\n";	
			}
		}
	}
	echo "<div class=cbutn><a href=\"course.php?cid=$cid\">Return to course page</a></div>\n";
	


}	

require("../footer.php");

function printq($qn,$qsetid,$seed,$pts,$showpts) {
	global $isfinal,$imasroot;
	srand($seed);

	$query = "SELECT qtype,control,qcontrol,qtext,answer,hasimg FROM imas_questionset WHERE id='$qsetid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$qdata = mysql_fetch_array($result, MYSQL_ASSOC);
	
	if ($qdata['hasimg']>0) {
		$query = "SELECT var,filename,alttext FROM imas_qimages WHERE qsetid='$qsetid'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			${$row[0]} = "<img src=\"$imasroot/assessment/qimages/{$row[1]}\" alt=\"{$row[2]}\" />";	
		}
	}
	eval(interpret('control',$qdata['qtype'],$qdata['control']));
	eval(interpret('qcontrol',$qdata['qtype'],$qdata['qcontrol']));
	$toevalqtxt = interpret('qtext',$qdata['qtype'],$qdata['qtext']);
	$toevalqtxt = str_replace('\\','\\\\',$toevalqtxt);
	$toevalqtxt = str_replace(array('\\\\n','\\\\"','\\\\$','\\\\{'),array('\\n','\\"','\\$','\\{'),$toevalqtxt);
	srand($seed+1);
	eval(interpret('answer',$qdata['qtype'],$qdata['answer']));
	srand($seed+2);
	$la = '';
	
	if (isset($choices) && !isset($questions)) {
		$questions =& $choices;
	}
	if (isset($variable) && !isset($variables)) {
		$variables =& $variable;
	}
	if ($displayformat=="select") {
		unset($displayformat);
	}
	
	//pack options
	if (isset($ansprompt)) {$options['ansprompt'] = $ansprompt;}
	if (isset($displayformat)) {$options['displayformat'] = $displayformat;}
	if (isset($answerformat)) {$options['answerformat'] = $answerformat;}
	if (isset($questions)) {$options['questions'] = $questions;}
	if (isset($answers)) {$options['answers'] = $answers;}
	if (isset($answer)) {$options['answer'] = $answer;}
	if (isset($questiontitle)) {$options['questiontitle'] = $questiontitle;}
	if (isset($answertitle)) {$options['answertitle'] = $answertitle;}
	if (isset($answersize)) {$options['answersize'] = $answersize;}
	if (isset($variables)) {$options['variables'] = $variables;}
	if (isset($domain)) {$options['domain'] = $domain;}	
	if (isset($answerboxsize)) {$options['answerboxsize'] = $answerboxsize;}
	if (isset($hidepreview)) {$options['hidepreview'] = $hidepreview;}
	if (isset($matchlist)) {$options['matchlist'] = $matchlist;}
	if (isset($noshuffle)) {$options['noshuffle'] = $noshuffle;}
	if (isset($reqdecimals)) {$options['reqdecimals'] = $reqdecimals;}
	if (isset($grid)) {$options['grid'] = $grid;}
	if (isset($background)) {$options['background'] = $background;}
	
	if ($qdata['qtype']=="multipart") {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$laparts = explode("&",$la);
		foreach ($anstypes as $kidx=>$anstype) {
			list($answerbox[$kidx],$tips[$kidx],$shans[$kidx]) = makeanswerbox($anstype,$kidx,$laparts[$kidx],$options,$qn+1);
		}
	} else {
		list($answerbox,$tips[0],$shans[0]) = makeanswerbox($qdata['qtype'],$qn,$la,$options,0);
	}
	
	echo "<div class=q>";
	if ($isfinal) {
		echo "<div class=\"trq$qn\">\n";
	} else {
		echo "<div class=m id=\"trq$qn\">\n";
	}
	if ($showpts) {
		echo ($qn+1).'. ('.$pts.' pts) ';	
	}
	echo "<div>\n";
	//echo $toevalqtext;
	eval("\$evaledqtext = \"$toevalqtxt\";");
	echo printfilter(filter($evaledqtext));
	echo "</div>\n"; //end question div
	
	if (strpos($toevalqtxt,'$answerbox')===false) {
		if (is_array($answerbox)) {
			foreach($answerbox as $iidx=>$abox) {
				echo printfilter(filter("<div>$abox</div>\n"));
				echo "<div class=spacer>&nbsp;</div>\n";
			}
		} else {  //one question only
			echo printfilter(filter("<div>$answerbox</div>\n"));
		}
		
		
	} 
	
	
	echo "</div>";//end m div
	
	echo "&nbsp;";
	echo "</div>\n"; //end q div
	if (!isset($showanswer)) {
		return $shans;
	} else {
		return $showanswer;
	}
}

?>
