<?php
//IMathAS: Pull Student responses on an assessment
//(c) 2009 David Lippman

require("../validate.php");
$isteacher = isset($teacherid);
$cid = $_GET['cid'];
$aid = $_GET['aid'];
if (!$isteacher) {
	echo "This page not available to students";
	exit;
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

if (isset($_POST['options'])) {
	//ready to output
	$outcol = 0;
	if (isset($_POST['pts'])) { $dopts = true; $outcol++;}
	if (isset($_POST['ba'])) { $doba = true; $outcol++;} 
	if (isset($_POST['la'])) { $dola = true; $outcol++;} 
	
	//get assessment info
	$query = "SELECT defpoints,name,itemorder FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$defpoints = mysql_result($result,0,0);
	$assessname = mysql_result($result,0,1);
	$itemorder = mysql_result($result,0,2);
	$itemarr = array();
	$itemnum = array();
	foreach (explode(',',$itemorder) as $k=>$itel) {
		if (strpos($itel,'~')!==false) {
			$sub = explode('~',$itel);
			if (strpos($sub[0],'|')!==false) {
				array_shift($sub);
			}
			foreach ($sub as $j=>$itsub) {
				$itemarr[] = $itsub;
				$itemnum[$itsub] = ($k+1).'-'.($j+1);
			}
		} else {
			$itemarr[] = $itel;
			$itemnum[$itel] = ($k+1);
		}
	}
	//get question info
	$qpts = array();
	$query = "SELECT id,points FROM imas_questions WHERE assessmentid='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		if ($row[1]==9999) {
			$qpts[$row[0]] = $defpoints;
		} else {
			$qpts[$row[0]] = $row[1];
		}
	}
	
	$gb = array();
	//create headers
	$gb[0][0] = "Name";
	$gb[1][0] = "";
	$qcol = array();
	foreach ($itemarr as $k=>$q) {
		$qcol[$q] = 1 + $outcol*$k;
		$offset = 0;
		if ($dopts) {
			$gb[0][1 + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][1 + $outcol*$k + $offset] = "Points (".$qpts[$q]." possible)";
			$offset++;
		}
		if ($doba) {
			$gb[0][1 + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][1 + $outcol*$k + $offset] = "Scored Answer";
			$offset++;
		}
		
		if ($dola) {
			$gb[0][1 + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][1 + $outcol*$k + $offset] = "Last Answer";
			$offset++;
		}
	}
	
	//create row headers
	$query = "SELECT iu.id,iu.FirstName,iu.LastName FROM imas_users AS iu JOIN ";
	$query .= "imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid='$cid' ";
	$query .= "ORDER BY iu.LastName, iu.FirstName";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$r = 2;
	$sturow = array();
	while ($row = mysql_fetch_row($result)) {
		$gb[$r] = array_fill(0,count($gb[0]),'');
		$gb[$r][0] = $row[2].', '.$row[1];
		$sturow[$row[0]] = $r;
		$r++;
	}
	
	//pull assessment data
	$query = "SELECT ias.questions,ias.bestscores,ias.bestattempts,ias.bestlastanswers,ias.lastanswers,ias.userid FROM imas_assessment_sessions AS ias,imas_students ";
	$query .= "WHERE ias.userid=imas_students.userid AND imas_students.courseid='$cid' AND ias.assessmentid='$aid'";
	$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		$questions = explode(',',$line['questions']);
		$scores = explode(',',$line['bestscores']);
		$bla = explode('~',$line['bestlastanswers']);
		$la =  explode('~',$line['lastanswers']);
		if (!isset($sturow[$line['userid']])) {
			continue;
		}
		$r = $sturow[$line['userid']];
		foreach ($questions as $k=>$ques) {
			
			$c = $qcol[$ques];
			$offset = 0;
			if ($dopts) {
				$gb[$r][$c+$offset] = getpts($scores[$k]);
				$offset++;
			}
			if ($doba) {
				$laarr = explode('##',$bla[$k]);
				$gb[$r][$c+$offset] = $laarr[count($laarr)-1];
				$offset++;
			}
			if ($dola) {
				$laarr = explode('##',$la[$k]);
				$gb[$r][$c+$offset] = $laarr[count($laarr)-1];
				$offset++;
			}
		}
	}
	header('Content-type: text/csv');
	header("Content-Disposition: attachment; filename=\"aexport-$aid.csv\"");
	foreach ($gb as $gbline) {
		$line = '';
		foreach ($gbline as $val) {
			 # remove any windows new lines, as they interfere with the parsing at the other end 
			  $val = str_replace("\r\n", "\n", $val); 
			  $val = str_replace("\n", " ", $val);
			  $val = str_replace(array("<BR>",'<br>','<br/>'), ' ',$val);
			  $val = str_replace("&nbsp;"," ",$val);
		
			  # if a deliminator char, a double quote char or a newline are in the field, add quotes 
			  if(ereg("[\,\"\n\r]", $val)) { 
				  $val = '"'.str_replace('"', '""', $val).'"'; 
			  }
			  $line .= $val.',';
		}
		# strip the last deliminator 
		$line = substr($line, 0, -1); 
		$line .= "\n";
		echo $line;
	}
	exit;
} else {
	//ask for options
	$pagetitle = "Assessment Export";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; <a href=\"gb-itemanalysis.php?aid=$aid&cid=$cid\">Item Analysis</a> ";
	echo '&gt; Assessment Export</div>';
	echo '<h2>Assessment Results Export</h2>';
	
	echo "<form method=\"post\" action=\"gb-aidexport.php?aid=$aid&cid=$cid\">";
	echo 'What do you want to include in the export:<br/>';
	echo '<input type="checkbox" name="pts" value="1"/> Points earned<br/>';
	echo '<input type="checkbox" name="ba" value="1"/> Scored Attempt<br/>';
	echo '<input type="checkbox" name="la" value="1"/> Last Attempt<br/>';
	echo '<input type="submit" name="options" value="Export" />';
	echo '<p>Export will be a commas separated values (.CSV) file, which can be opened in Excel</p>';
	echo '<p><b class="red">Note</b>: Attempt information from shuffled multiple choice, multiple answer, and matching questions will NOT be correct</p>';
	echo '</form>';
	require("../footer.php");
	
}
?>
