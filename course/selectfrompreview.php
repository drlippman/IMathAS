<?php
//IMathAS:  Select assessment items from preview
//(c) 2006 David Lippman
	
	if (isset($_GET['offset'])) {
		$offset = $_GET['offset'];
	} else {
		$offset = 0;
	}
	
	
	if ($offset>0) {
		$last = $offset -1;
		echo "<a href=\"selectfrompreview.php?cid=$cid&source=$source&offset=$last&lib=$lib\">Last</a> ";
	} else {
		echo "Last ";
	}
	
	if ($offset<$cnt-1) {
		$next = $offset +1;
		echo "<a href=\"selectfrompreview.php?cid=$cid&source=$source&offset=$next&lib=$lib\">Next</a>";
	} else {
		echo "Next";
	}
	
	
	$seed = rand(0,10000);
	require("../assessment/displayq2.php");
	if (isset($_POST['seed'])) {
		$score = scoreq(0,$qsetid,$_POST['seed'],$_POST['qn0']);
		echo "<p>Score on last answer: $score/1</p>\n";
	}
	
	echo "<form method=post action=\"selectfrompreview.php?cid=$cid&source=$source&offset=$offset&lib=$lib\" onsubmit=\"doonsubmit()\">\n";
	echo "<input type=hidden name=seed value=\"$seed\">\n";
	unset($lastanswers);
	displayq(0,$qsetid,$seed,true);
	echo "<input type=submit value=\"Submit\">\n";
	echo "</form>\n";
	
	require("../footer.php");
?>
	
