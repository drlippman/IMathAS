<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");




 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Print Layout";
$flexwidth = true;


	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

}

/******* begin html output ********/
$placeinhead = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/assessment/mathtest.css\"/>\n";
$placeinhead .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/assessment/print.css\"/>\n";
$placeinhead .= "<script src=\"$imasroot/javascript/AMhelpers.js\" type=\"text/javascript\"></script>\n";

$nologo = true;
$loadgraphfilter = true;
$assessver = 2;
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {

	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);
	if (isset($_POST['vert'])) {
		$ph = 11 - $_POST['vert'];
		$pw = 8.5 - $_POST['horiz'];
		if ($_POST['browser']==1) {
			$ph -= .5;
			$pw -= .5;
		}
	} else if (isset ($_POST['pw'])) {
		$ph = $_POST['ph'];
		$pw = $_POST['pw'];
	}
	$isfinal = isset($_GET['final']);

	//DB $query = "SELECT itemorder,shuffle,defpoints,name,intro FROM imas_assessments WHERE id='$aid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
	$stm = $DBH->prepare("SELECT itemorder,shuffle,defpoints,name,intro FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	if (($introjson=json_decode($line['intro']))!==null) { //is json intro
		$line['intro'] = $introjson[0];
	}

	$ioquestions = explode(",",$line['itemorder']);
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
	$phs = $ph-0.6;
	$pws = $pw-0.5;
	$pwss = $pw-0.6;
?>
	<style type="text/css">
		div.a,div.b {
		  position: absolute;
		  left: 0px;
		  border: 1px solid;
		  width: <?php echo Sanitize::onlyFloat($pwss); ?>in;
		  height: <?php echo Sanitize::onlyFloat($phs); ?>in;
		}
		div.a {
		  border: 3px double #33f;
		}
		div.b {
		  border: 3px double #0c0;
		}

<?php
	if ($isfinal) {
		$heights = explode(',',$_POST['heights']);
		for ($i=0;$i<count($heights);$i++) {
			echo "div.trq$i {float: left; width: ".Sanitize::encodeStringForCSS($pw)."in; height: ".Sanitize::encodeStringForCSS($heights[$i])."in; padding: 0px; overflow: hidden;}\n";
		}
		echo "div.hdrm {width: ".Sanitize::encodeStringForCSS($pw)."in; padding: 0px; overflow: hidden;}\n";
	} else {
		$pt = 0;
		for ($i=0;$i<ceil($numq/3)+1;$i++) {
			echo "div#pg$i { top: " . Sanitize::onlyFloat($pt) . "in;}\n";
			$pt+=$ph;
			if ($_POST['browser']==1) {$pt -= .4;}
		}
	}
	if (isset($_POST['hidetxtboxes'])) {
		echo "input.text { display: none; }\n";
	}
?>

		div.floatl {
			float: left;
		}
		div.qnum {
			float: left;
			text-align: right;
			padding-right: 5px;
		}
		div#headerleft {
			float: left;
		}
		div#headerright {
			float: right;
			text-align: right;
		}
		div#intro {
			clear: both;
			padding-top: 5px;
			padding-bottom: 5px;
		}
		div.q {
			clear: both;
			padding: 0px;
			margin: 0px;
		}
		div.m {
			float: left;
			width: <?php echo Sanitize::encodeStringForCSS($pws); ?>in;
			border-bottom: 1px dashed #aaa;
			padding: 0px;
			overflow: hidden;
		}

		div.cbutn {
			float: left;
			padding-left: 5px;
		}
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
	<style type="text/css" media="print">

		div.a,div.b {
			display: none;
		}
		div.m {
			width: <?php echo Sanitize::encodeStringForCSS($pw); ?>in;
			border: 0px;
		}
		div.cbutn {
			display: none;
		}
		.pageb {
			border: 0px;
		}

	</style>

<?php
	if (!$isfinal) {
		for ($i=0;$i<ceil($numq/3)+1;$i++) { //print page layout divs
			echo "<div id=\"pg$i\" ";
			if ($i%2==0) {
				echo "class=a";
			} else {
				echo "class=b";
			}
			echo ">&nbsp;</div>\n";
		}
	}
	include("../assessment/displayq2.php");


	//echo "<div class=maintest>\n";
	echo "<form method=post action=\"printtest.php?cid=$cid&aid=$aid\" onSubmit=\"return packheights()\">\n";

	if ($isfinal) {
		$copies = $_POST['versions'];
	} else {
		$copies = 1;
	}
	$fixedn = array();
	for ($j=0; $j<$copies; $j++) {
		$seeds = array();
		if ($line['shuffle']&2) {  //set rand seeds
			$seeds = array_fill(0,count($questions),rand(1,9999));
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
					$seeds[] = $fixedseeds[$questions[$i]][($x+$j)%$n];
				} else {
				$seeds[] = rand(1,9999);
			}
		}
		}

		$headerleft = '';
		if (isset($_POST['aname'])) {
			$headerleft .= Sanitize::encodeStringForDisplay($line['name']);
		}
		if ($copies>1) {
			$headerleft .= ' - Form ' . ($j+1);
		}
		if ((isset($_POST['iname']) || isset($_POST['cname'])) && isset($_POST['aname'])) {
			$headerleft .= "<br/>";
		}
		if (isset($_POST['cname'])) {
			//DB $query = "SELECT name FROM imas_courses WHERE id=$cid";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $headerleft .= mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$cid));
			$headerleft .= Sanitize::encodeStringForDisplay($stm->fetchColumn(0));
			if (isset($_POST['iname'])) { $headerleft .= ' - ';}
		}
		if (isset($_POST['iname'])) {
			//DB $query = "SELECT LastName FROM imas_users WHERE id=$userid";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $headerleft .= mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT LastName FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$userid));
			$headerleft .= Sanitize::encodeStringForDisplay($stm->fetchColumn(0));
		}
		$headerright = '';
		if (isset($_POST['sname'])) {
			$headerright .= 'Name ____________________________';
			if (isset($_POST['otherheader'])) {
				$headerright .= '<br/>';
			}
		}
		if (isset($_POST['otherheader'])) {
			$headerright .= Sanitize::encodeStringForDisplay($_POST['otherheadertext']) . '____________________________';
		}

		echo "<div class=q>\n";
		if ($isfinal) {
			echo "<div class=hdrm>\n";
		} else {
			echo "<div class=m>\n";
		}
		echo "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
		// $line['intro'] contains HTML.
		printf("<div id=intro>%s</div>\n", Sanitize::outgoingHtml($line['intro']));
		echo "</div>\n";
		if (!$isfinal) {
			echo "<div class=cbutn><a href=\"printtest.php?cid=$cid&aid=$aid\">Cancel</a></div>\n";
		}
		echo "</div>\n";


		for ($i=0; $i<$numq; $i++) {
			$sa[$j][$i] = printq($i,$qn[$questions[$i]],$seeds[$i],$points[$questions[$i]]);
		}
		if ($isfinal) {
			echo "<p class=pageb>&nbsp;</p>\n";
		}
	}
	//echo "</table>\n";
	if (!$isfinal) {
?>

	<script type="text/javascript">
		var heights = new Array();
		for (var i=0; i<<?php echo $numq ?>; i++) {
			heights[i] = 2.5;
			document.getElementById("trq"+i).style.height = "2.5in";
		}
		function incspace(id,sp) {
			if (heights[id]+sp>.5) {
				heights[id] += sp;
				document.getElementById("trq"+id).style.height = heights[id]+"in";
			}

		}
		function packheights() {
			document.getElementById("heights").value = heights.join(",");
			return true;
		}
	</script>

<?php
		echo "<input type=hidden id=heights name=heights value=\"\">\n";
		echo "<input type=hidden name=pw value=\"".Sanitize::encodeStringForDisplay($pw)."\">\n";
		echo "<input type=hidden name=ph value=\"".Sanitize::encodeStringForDisplay($ph)."\">\n";
		if (isset($_POST['points'])) {
			echo "<input type=hidden name=points value=1>\n";
		}
		if (isset($_POST['aname'])) {
			echo "<input type=hidden name=aname value=1>\n";
		}
		if (isset($_POST['iname'])) {
			echo "<input type=hidden name=iname value=1>\n";
		}
		if (isset($_POST['cname'])) {
			echo "<input type=hidden name=cname value=1>\n";
		}
		if (isset($_POST['sname'])) {
			echo "<input type=hidden name=sname value=1>\n";
		}
		if (isset($_POST['hidetxtboxes'])) {
			echo "<input type=hidden name=hidetxtboxes value=1>\n";
		}
		if (isset($_POST['otherheader'])) {
			echo "<input type=hidden name=otherheader value=1>\n";
			echo "<input type=hidden name=otherheadertext value=\"".Sanitize::encodeStringForDisplay($_POST['otherheadertext'])."\">\n";
		}
		echo "<div class=q><div class=m>&nbsp;</div><div class=cbutn><input type=submit value=\"Continue\"></div></div>\n";
	} else if ($_POST['keys']>0) { //print answer keys
		for ($j=0; $j<$copies; $j++) {
			echo '<b>Key - Form ' . ($j+1) . "</b>\n";
			echo "<ol>\n";
			for ($i=0; $i<$numq; $i++) {
				echo '<li>';
				if (is_array($sa[$j][$i])) {
					echo Sanitize::outgoingHtml(filter(implode(' ~ ',$sa[$j][$i])));
				} else {
				  echo Sanitize::outgoingHtml(filter($sa[$j][$i]));
				}
				echo "</li>\n";
			}
			echo "</ol>\n";
			if ($_POST['keys']==2) {
				echo "<p class=pageb>&nbsp;</p>\n";
			}
		}
	}
	if ($isfinal) {
		$licurl = $GLOBALS['basesiteurl'] . '/course/showlicense.php?id=' . implode('-',$qn);
		echo '<hr/><p style="font-size:70%">License info at: <a href="'.Sanitize::url($licurl).'">'.Sanitize::encodeStringForDisplay($licurl).'</a></p>';
		echo "<div class=cbutn><a href=\"course.php?cid=$cid\">Return to course page</a></div>\n";
	}
	echo "</form>\n";


}

require("../footer.php");

function printq($qn,$qsetid,$seed,$pts) {
	global $DBH,$RND,$isfinal,$imasroot,$urlmode;
	$RND->srand($seed);

	//DB $query = "SELECT qtype,control,qcontrol,qtext,answer,hasimg FROM imas_questionset WHERE id='$qsetid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $qdata = mysql_fetch_array($result, MYSQL_ASSOC);
	$stm = $DBH->prepare("SELECT qtype,control,qcontrol,qtext,answer,hasimg FROM imas_questionset WHERE id=:id");
	$stm->execute(array(':id'=>$qsetid));
	$qdata = $stm->fetch(PDO::FETCH_ASSOC);

	if ($qdata['hasimg']>0) {
		//DB $query = "SELECT var,filename,alttext FROM imas_qimages WHERE qsetid='$qsetid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT var,filename,alttext FROM imas_qimages WHERE qsetid=:qsetid");
		$stm->execute(array(':qsetid'=>$qsetid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if (substr($row[1],0,4)=='http') {
				${$row[0]} = "<img src=\"{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			} else if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				${$row[0]} = "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/qimages/{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
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
	echo "<div class=qnum>".($qn+1).") ";
	if (isset($_POST['points'])) {
		echo '<br/>'.Sanitize::encodeStringForDisplay($pts).'pts';
	}
	echo "</div>\n";//end qnum div
	echo "<div class=floatl><div>\n";
	//echo $toevalqtext;
	eval("\$evaledqtext = \"$toevalqtxt\";");
	echo printfilter(filter($evaledqtext));
	echo "</div>\n"; //end question div

	if (strpos($toevalqtxt,'$answerbox')===false) {
		if (is_array($answerbox)) {
			foreach($answerbox as $iidx=>$abox) {
				echo Sanitize::outgoingHtml(printfilter(filter("<div>$abox</div>\n")));
				echo "<div class=spacer>&nbsp;</div>\n";
			}
		} else {  //one question only
		  echo Sanitize::outgoingHtml(printfilter(filter("<div>$answerbox</div>\n")));
		}


	}

	echo "</div>\n"; //end floatl div

	echo "</div>";//end m div
	if (!$isfinal) {
		echo "<div class=cbutn>\n";
		echo "<p><input type=button value=\"+1\" onclick=\"incspace($qn,1)\"><input type=button value=\"+.5\" onclick=\"incspace($qn,.5)\"><input type=button value=\"+.25\" onclick=\"incspace($qn,.25)\"><input type=button value=\"+.1\" onclick=\"incspace($qn,.1)\"><br/>";
		echo "<input type=button value=\"-1\" onclick=\"incspace($qn,-1)\"><input type=button value=\"-.5\" onclick=\"incspace($qn,-.5)\"><input type=button value=\"-.25\" onclick=\"incspace($qn,-.25)\"><input type=button value=\"-.1\" onclick=\"incspace($qn,-.1)\"></p>";
		echo "</div>\n"; //end cbutn div
	}
	echo "&nbsp;";
	echo "</div>\n"; //end q div
	if (!isset($showanswer)) {
		return $shans;
	} else {
		return $showanswer;
	}
}
?>
