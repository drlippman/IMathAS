<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");



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
if (isset($_POST['versions'])) {
	$placeinhead = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/assessment/print.css?v=100213\"/>\n";
}

$nologo = true;
$cid = Sanitize::courseId($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
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
$assessver = 2;

if ($overwriteBody==1) {
	echo $body;
} if (!isset($_POST['versions'])) {
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"addquestions.php?cid=$cid&aid=$aid\">Add/Remove Questions</a> ";
	echo "&gt; Print Test</div>\n";

	echo '<div class="cpmid"><a href="printtest.php?cid='.$cid.'&amp;aid='.$aid.'">Generate for in-browser printing</a>';
	if (isset($CFG['GEN']['pandocserver'])) {
		echo ' | <a href="printlayoutword.php?cid='.$cid.'&amp;aid='.$aid.'">Generate for Word</a>';
	}
	echo '</div>';

	echo "<h1>Copy-and-Paste Print Version</h1>";

	echo '<p>This page will help you create a copy of this assessment that you should be able to cut and ';
	echo 'paste into Word or another word processor and adjust layout for printing</p>';

	echo "<form method=post action=\"printlayoutbare.php?cid=$cid&aid=$aid\">\n";
	echo '<span class="form">Number of different versions to generate:</span><span class="formright"><input type=text name=versions value="1" size="3"></span><br class="form"/>';
	echo '<span class="form">Format?</span><span class="formright"><input type="radio" name="format" value="trad" checked="checked" /> Form A: 1 2 3, Form B: 1 2 3<br/><input type="radio" name="format" value="inter"/> 1a 1b 2a 2b</span><br class="form"/>';
	echo '<span class="form">Generate answer keys?</span><span class="formright"> <input type=radio name=keys value=1 checked=1>Yes <input type=radio name=keys value=0>No</span><br class="form"/>';
	echo '<span class="form">Question separator:</span><span class="formright"><input type=text name="qsep" value="" /></span><br class="form"/>';
	echo '<span class="form">Version separator:</span><span class="formright"><input type=text name="vsep" value="+++++++++++++++" /> </span><br class="form"/>';
	echo '<span class="form">Math display:</span><span class="formright"><input type="radio" name="mathdisp" value="img" checked="checked" /> Images <input type="radio" name="mathdisp" value="text"/> Text <input type="radio" name="mathdisp" value="tex"/> TeX <input type="radio" name="mathdisp" value="textandimg"/> Images, then again in text</span><br class="form"/>';
	echo '<span class="form">Include question numbers and point values:</span><span class="formright"><input type="checkbox" name="showqn" checked="checked" /> </span><br class="form"/>';
	echo '<span class="form">Hide text entry lines?</span><span class="formright"><input type=checkbox name=hidetxtboxes ></span><br class="form"/>';

	echo '<div class="submit"><input type=submit value="Continue"></div></form>';

} else {
	require("../assessment/header.php");
	//DB $query = "SELECT itemorder,shuffle,defpoints,name,intro FROM imas_assessments WHERE id='$aid'";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
	$stm = $DBH->prepare("SELECT itemorder,shuffle,defpoints,name,intro FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	if (($introjson=json_decode($line['intro']))!==null) { //is json intro
		$line['intro'] = $introjson[0];
	}

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
	$fixedseeds = array();
	//DB $qlist = "'".implode("','",$questions)."'";
	$qlist = array_map('Sanitize::onlyInt', $questions);
	//DB $query = "SELECT id,points,questionsetid FROM imas_questions WHERE id IN ($qlist)";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query_placeholders = Sanitize::generateQueryPlaceholders($qlist);
	$stm = $DBH->prepare("SELECT id,points,questionsetid,fixedseeds FROM imas_questions WHERE id IN ($query_placeholders)");
	$stm->execute($qlist);
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[1]==9999) {
			$points[$row[0]] = $line['defpoints'];
		} else {
			$points[$row[0]] = $row[1];
		}
		$qn[$row[0]] = $row[2];
		if ($row[3]!==null && $row[3]!='') {
			$fixedseeds[$row[0]] = explode(',',$row[3]);
	}
	}


	$numq = count($questions);

?>
	<style type="text/css">
	
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
		if ($line['shuffle']&2) {  //all questions same random seed
			if ($shuffle&4) { //all students same seed
				$seeds[$j] = array_fill(0,count($questions),$aid+$j);
			} else {
				$seeds[$j] = array_fill(0,count($questions),rand(1,9999));
			}
		} else {
			if ($shuffle&4) { //all students same seed
				for ($i = 0; $i<count($questions);$i++) {
					if (isset($fixedseeds[$questions[$i]])) {
						$seeds[$j][] = $fixedseeds[$questions[$i]][$j%count($fixedseeds[$questions[$i]])];
					} else {
					$seeds[$j][] = $aid + $i + $j;
				}
				}
			} else {
				for ($i = 0; $i<count($questions);$i++) {
					if (isset($fixedseeds[$questions[$i]])) {
						$n = count($fixedseeds[$questions[$i]]);
						if (isset($fixedn[$i])) {
							$x = $fixedn[$i];
						} else {
							$x = rand(0,$n-1);
							$fixedn[$i] = $x;
						}
						$seeds[$j][] = $fixedseeds[$questions[$i]][($x+$j)%$n];
					} else {
					$seeds[$j][] = rand(1,9999);
				}
			}
		}
	}
	}

	for ($pt=0;$pt<$printtwice;$pt++) {
		if ($pt==1) {
			$sessiondata['mathdisp'] = 0;
			echo Sanitize::encodeStringForDisplay($_POST['vsep']).'<br/>';;

		}

		if ($_POST['format']=='trad') {
			for ($j=0; $j<$copies; $j++) {
				if ($j>0) { echo Sanitize::encodeStringForDisplay($_POST['vsep']).'<br/>';}

				$headerleft = '';
				$headerleft .= Sanitize::encodeStringForDisplay($line['name']);
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
				// $line['intro'] contains HTML.
				printf("<div id=intro>%s</div>\n", Sanitize::outgoingHtml($line['intro']));
				echo "</div>\n";
				echo "</div>\n";


				for ($i=0; $i<$numq; $i++) {
					if ($i>0) { echo Sanitize::encodeStringForDisplay($_POST['qsep']);}
					$sa[$j][$i] = printq($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($_POST['showqn']));
				}

			}

			if ($_POST['keys']>0) { //print answer keys
				for ($j=0; $j<$copies; $j++) {
					echo Sanitize::encodeStringForDisplay($_POST['vsep']).'<br/>';
					echo '<b>Key - Form ' . ($j+1) . "</b>\n";
					echo "<ol>\n";
					for ($i=0; $i<$numq; $i++) {
						echo '<li>';
						if (is_array($sa[$j][$i])) {
							echo Sanitize::outgoingHTML(printfilter(filter(implode(' ~ ',$sa[$j][$i]))));
						} else {
						  echo Sanitize::outgoingHTML(printfilter(filter($sa[$j][$i])));
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
			$headerleft .= Sanitize::encodeStringForDisplay($line['name']);
			if ((isset($_POST['iname']) || isset($_POST['cname'])) && isset($_POST['aname'])) {
				$headerleft .= "<br/>";
			}
			$headerright = '';
			echo "<div class=q>\n";
			echo "<div class=hdrm>\n";

			echo "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
			// $line['intro'] contains HTML.
			printf("<div id=intro>%s</div>\n", Sanitize::outgoingHtml($line['intro']));
			echo "</div>\n";
			echo "</div>\n";
			for ($i=0; $i<$numq; $i++) {
				if ($i>0) { echo Sanitize::encodeStringForDisplay($_POST['qsep']);}
				for ($j=0; $j<$copies;$j++) {
					if ($j>0) { echo Sanitize::encodeStringForDisplay($_POST['qsep']);}
					$sa[] = printq($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($_POST['showqn']));
				}
			}
			if ($_POST['keys']>0) { //print answer keys
				echo Sanitize::encodeStringForDisplay($_POST['vsep']).'<br/>';
				echo "<b>Key</b>\n";
				echo "<ol>\n";
				for ($i=0; $i<count($sa); $i++) {
					echo '<li>';
					if (is_array($sa[$i])) {
					  echo Sanitize::outgoingHTML(printfilter(filter(implode(' ~ ',$sa[$i]))));
					} else {
					  echo Sanitize::outgoingHTML(printfilter(filter($sa[$i])));
					}
					echo "</li>\n";
				}
				echo "</ol>\n";
			}
		}
	}
	$licurl = $GLOBALS['basesiteurl'] . '/course/showlicense.php?id=' . implode('-',$qn);
	echo '<hr/><p style="font-size:70%">License info at: <a href="'.Sanitize::url($licurl).'">'.Sanitize::encodeStringForDisplay($licurl).'</a></p>';

	echo "<div class=cbutn><a href=\"course.php?cid=$cid\">Return to course page</a></div>\n";



}

require("../footer.php");

function printq($qn,$qsetid,$seed,$pts,$showpts) {
	global $DBH,$RND,$isfinal,$imasroot,$urlmode;
	$isbareprint = true;
	$RND->srand($seed);

	//DB $query = "SELECT qtype,control,qcontrol,qtext,answer,hasimg FROM imas_questionset WHERE id='$qsetid'";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB $qdata = mysql_fetch_array($result, MYSQL_ASSOC);
	$stm = $DBH->prepare("SELECT qtype,control,qcontrol,qtext,answer,hasimg FROM imas_questionset WHERE id=:id");
	$stm->execute(array(':id'=>$qsetid));
	$qdata = $stm->fetch(PDO::FETCH_ASSOC);

	if ($qdata['hasimg']>0) {
		//DB $query = "SELECT var,filename,alttext FROM imas_qimages WHERE qsetid='$qsetid'";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT var,filename,alttext FROM imas_qimages WHERE qsetid=:qsetid");
		$stm->execute(array(':qsetid'=>$qsetid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if (substr($row[1],0,4)=='http') {
				${$row[0]} = "<img src=\"{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			} else if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				${$row[0]} = "<img src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/qimages/{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			} else {
				${$row[0]} = "<img src=\"$imasroot/assessment/qimages/{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			}
		}
	}
	eval(interpret('control',$qdata['qtype'],$qdata['control']));
	eval(interpret('qcontrol',$qdata['qtype'],$qdata['qcontrol']));
	$toevalqtxt = interpret('qtext',$qdata['qtype'],$qdata['qtext']);
	$toevalqtxt = str_replace('\\','\\\\',$toevalqtxt);
	$toevalqtxt = str_replace(array('\\\\n','\\\\"','\\\\$','\\\\{'),array('\\n','\\"','\\$','\\{'),$toevalqtxt);
	$RND->srand($seed+1);
	eval(interpret('answer',$qdata['qtype'],$qdata['answer']));
	$RND->srand($seed+2);
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
		echo ($qn+1).'. ('.Sanitize::encodeStringForDisplay($pts).' pts) ';
	}
	echo "<div>\n";
	//echo $toevalqtext;
	eval("\$evaledqtext = \"$toevalqtxt\";");
	echo printfilter(filter($evaledqtext));
	echo "</div>\n"; //end question div

	if (strpos($toevalqtxt,'$answerbox')===false) {
		if (is_array($answerbox)) {
			foreach($answerbox as $iidx=>$abox) {
			  echo Sanitize::outgoingHTML(printfilter(filter("<div>$abox</div>\n")));
				echo "<div class=spacer>&nbsp;</div>\n";
			}
		} else {  //one question only
		  echo Sanitize::outgoingHTML(printfilter(filter("<div>$answerbox</div>\n")));
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
