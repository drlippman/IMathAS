<?php
//IMathAS:  Displays a linked text item
//(c) 2006 David Lippman
	require("../validate.php");
	$cid = $_GET['cid'];
		
	if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($guestid)) {
		require("../header.php");
		echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
		require("../footer.php");
		exit;
	}
	if (!isset($_GET['id'])) {
		echo "<html><body>No item specified. <a href=\"course.php?cid={$_GET['cid']}\">Try again</a></body></html>\n";
		exit;
	}
	$query = "SELECT text,title FROM imas_linkedtext WHERE id='{$_GET['id']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$text = mysql_result($result, 0,0);
	$title = mysql_result($result,0,1);
	$titlesimp = strip_tags($title);

	if (substr($text,3,5)=="Embed") {
		$toembed = substr($text,9,-4);
		$pts = explode(',',$toembed);
		$text = "<iframe height=\"{$pts[1]}\" src=\"{$pts[2]}\" style=\"position:absolute;width:100%;\"/>";
	}
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; $titlesimp</div>";
	echo '<div id="headershowlinkedtext" class="pagetitle"><h2>'.$titlesimp.'</h2></div>';
	echo '<div style="padding-left:10px; padding-right: 10px;">';
	echo filter($text);
	echo '</div>';
	
	echo "<div class=right><a href=\"course.php?cid={$_GET['cid']}\">Return to Course Page</a></div>\n";
	require("../footer.php");	

?>
