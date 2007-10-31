<?php
//IMathAS:  Grade all of one question for an assessment
//(c) 2007 David Lippman
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}


	$cid = $_GET['cid'];
	$stu = $_GET['stu'];
	$gbmode = $_GET['gbmode'];
	$aid = $_GET['aid'];
	$qid = $_GET['qid'];
	if (isset($_GET['ver'])) {
		$ver = $_GET['ver'];
	} else {
		$ver = 'graded';
	}
	
	if (isset($_GET['update'])) {
		$allscores = array();
		$locs = array();
		foreach ($_POST as $k=>$v) {
			if (strpos($k,'-')!==false) {
				$kp = explode('-',$k);
				if ($kp[0]=='ud') {
					$locs[$kp[1]] = $kp[2];
					if (count($kp)==3) {
						if ($v=='N/A') {
							$allscores[$kp[1]] = -1;
						} else {
							$allscores[$kp[1]] = $v;
						}
					} else {
						if ($v=='N/A') {
							$allscores[$kp[1]][$kp[3]] = -1;
						} else {
							$allscores[$kp[1]][$kp[3]] = $v;
						}
					}
				}
			}
		}
		
		$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions ";
		$query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_assessment_sessions.assessmentid='$aid' ";
		$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$cnt = 0;
		while($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
			if (isset($locs[$line['id']])) {
				$scores = explode(",",$line['bestscores']);
				if (is_array($allscores[$line['id']])) {
					$scores[$locs[$line['id']]] = implode('~',$allscores[$line['id']]);
				} else {
					$scores[$locs[$line['id']]] = $allscores[$line['id']];
				}
				$scorelist = implode(",",$scores);
				$feedback = $_POST['feedback-'.$line['id']];
				$query = "UPDATE imas_assessment_sessions SET bestscores='$scorelist',feedback='$feedback' WHERE id='{$line['id']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
		}
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid&gbmode=$gbmode&aid=$aid&asid=average");
		exit;
	}
	
	
	require("../assessment/displayq2.php");
	list ($qsetid,$cat) = getqsetid($qid);
	
	$query = "SELECT name,defpoints FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$aname = mysql_result($result,0,0);
	$defpoints = mysql_result($result,0,1);
	
	$query = "SELECT points FROM imas_questions WHERE id='$qid'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$points = mysql_result($result,0,0);
	if ($points==9999) {
		$points = $defpoints;
	}
	
	require("../assessment/header.php");
	echo "<style type=\"text/css\">p.tips {	display: none;}\n</style>\n";
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&gbmode=$gbmode&cid=$cid\">Gradebook</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&asid=average\">Item Analysis</a> ";
	echo "&gt; Grading a Question</div>";
	echo "<h2>Grading a Question in $aname</h2>";
	echo "<p><b>Warning</b>: This page may not work correctly if the question selected is part of a group of questions</p>";
	echo "<p>Note: Feedback is for whole assessment, not the individual question.</p>";
	
	echo '<script type="text/javascript">';
	echo 'function hidecorrect() {';
	echo '   var butn = document.getElementById("hctoggle");';
	echo '   if (butn.value=="Hide Perfect Score Questions") {';
	echo '      butn.value = "Show Perfect Score Questions";';
	echo '      var setdispto = "block";';
	echo '   } else { ';
	echo '      butn.value = "Hide Perfect Score Questions";';
	echo '      var setdispto = "none";';
	echo '   }';
	echo '   var divs = document.getElementsByTagName("div");';
	echo '   for (var i=0;i<divs.length;i++) {';
	echo '     if (divs[i].className=="iscorrect") { ';
	echo '         if (divs[i].style.display=="none") {';
	echo '               divs[i].style.display = "block";';
	echo '         } else { divs[i].style.display = "none"; }';
	echo '     }';
	echo '    }';
	echo '}';
	echo 'function hideNA() {';
	echo '   var butn = document.getElementById("hnatoggle");';
	echo '   if (butn.value=="Hide Not Answered Questions") {';
	echo '      butn.value = "Show Not Answered Questions";';
	echo '      var setdispto = "block";';
	echo '   } else { ';
	echo '      butn.value = "Hide Not Answered Questions";';
	echo '      var setdispto = "none";';
	echo '   }';
	echo '   var divs = document.getElementsByTagName("div");';
	echo '   for (var i=0;i<divs.length;i++) {';
	echo '     if (divs[i].className=="notanswered") { ';
	echo '         if (divs[i].style.display=="none") {';
	echo '               divs[i].style.display = "block";';
	echo '         } else { divs[i].style.display = "none"; }';
	echo '     }';
	echo '    }';
	echo '}';
	echo '</script>';
	echo '<input type=button id="hctoggle" value="Hide Perfect Score Questions" onclick="hidecorrect()" />';
	echo ' <input type=button id="hnatoggle" value="Hide Not Answered Questions" onclick="hideNA()" />';
	echo "<form id=\"mainform\" method=post action=\"gradeallq.php?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&update=true\">\n";
	echo "<p>";
	if ($ver=='graded') {
		echo "Showing Graded Attempts.  ";
		echo "<a href=\"gradeallq.php?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&ver=last\">Show Last Attempts</a>";
	} else if ($ver=='last') {
		echo "<a href=\"gradeallq.php?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&ver=graded\">Show Graded Attempts</a>.  ";
		echo "Showing Last Attempts.  ";
		echo "<br/><b>Note:</b> Grades and number of attempt used are for the Graded Attempt";
	}
	echo "</p>";
	$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions,imas_students ";
	$query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_students.userid=imas_users.id AND imas_students.courseid='$cid' AND imas_assessment_sessions.assessmentid='$aid' ";
	$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$cnt = 0;
	while($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		$questions = explode(',',$line['questions']);
		$scores = explode(",",$line['bestscores']);
		$attempts = explode(",",$line['bestattempts']);
		if ($ver=='graded') {
			$seeds = explode(",",$line['bestseeds']);
			$la = explode("~",$line['bestlastanswers']);
		} else if ($ver=='last') {
			$seeds = explode(",",$line['seeds']);
			$la = explode("~",$line['lastanswers']);
		}
		$loc = array_search($qid,$questions);
		echo "<h4>".$line['LastName'].', '.$line['FirstName']."</h4>";
		echo "<div ";
		if (getpts($scores[$loc])==$points) {
			echo 'class="iscorrect"';	
		} else if ($scores[$loc]==-1) {
			echo 'class="notanswered"';
		} else {
			echo 'class="iswrong"';
		}
		echo '>';
		$lastanswers[$cnt] = $la[$loc];
		displayq($cnt,$qsetid,$seeds[$loc],true,false,$attempts[$loc]);
		echo '</div>';
		
		echo "<div class=review>".$line['LastName'].', '.$line['FirstName'].': ';
		list($pt,$parts) = printscore($scores[$loc]);
		if ($parts=='') { 
			if ($pt==-1) {
				$pt = 'N/A';
			}
			echo "<input type=text size=4 name=\"ud-{$line['id']}-$loc\" value=\"$pt\">";
		} 
		if ($parts!='') {
			echo " Parts: ";
			$prts = explode(', ',$parts);
			for ($j=0;$j<count($prts);$j++) {
				if ($prts[$j]==-1) {
					$prts[$j] = 'N/A';
				}
				echo "<input type=text size=2 name=\"ud-{$line['id']}-$loc-$j\" value=\"{$prts[$j]}\"> ";
			}
		}
		echo " out of $points in {$attempts[$loc]} attempt(s)\n";
		
		$laarr = explode('##',$la[$loc]);
		if (count($laarr)>1) {
			echo "<br/>Previous Attempts:";
			$cntb =1;
			for ($k=0;$k<count($laarr)-1;$k++) {
				if ($laarr[$k]=="ReGen") {
					echo ' ReGen ';
				} else {
					echo "  <b>$cntb:</b> " . $laarr[$k];
					$cntb++;
				}
			}
		}
		
		//echo " <a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?cid=$cid&add=new&quoteq=$i-$qsetid-{$seeds[$i]}&to={$_GET['uid']}\">Use in Msg</a>";
		//echo " &nbsp; <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$line['id']}&clearq=$i\">Clear Score</a>";
		echo "<br/>Feedback: <textarea cols=50 rows=1 name=\"feedback-{$line['id']}\">{$line['feedback']}</textarea>";
		echo "</div>\n";
		$cnt++;
	}
	echo "<input type=submit value=\"Save Changes\"/>";
	echo "</form>";

	

	echo "<a href=\"gradebook.php?stu=$stu&cid=$cid&gbmode=$gbmode&aid=$aid&asid=average\">Back to Gradebook</a>";

	require("../footer.php");
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
	function printscore($sc) {
		if (strpos($sc,'~')===false) {

			return array($sc,'');
		} else {
			$pts = getpts($sc);
			$sc = str_replace('-1','N/A',$sc);
			$sc = str_replace('~',', ',$sc);
			return array($pts,$sc);
		}		
	}
?>


