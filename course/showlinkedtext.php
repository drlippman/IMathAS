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
	if (strpos($_SERVER['HTTP_REFERER'],'treereader')!==false) {
		$shownav = false;
		$flexwidth = true;
	} else {
		$shownav = true;
	}
	$query = "SELECT text,title,target FROM imas_linkedtext WHERE id='{$_GET['id']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$text = mysql_result($result, 0,0);
	$title = mysql_result($result,0,1);
	$target = mysql_result($result,0,2);
	$titlesimp = strip_tags($title);

	if (substr($text,0,8)=='exttool:') {
		list($tool,$custom) = explode('~~',substr($text,8));
		$param = "linkid={$_GET['id']}&cid=$cid";
		
		if ($target==0) {
			$height = '500px';
			$width = '95%';
			$param .= '&target=iframe';
			$text = '<iframe src="'.$imasroot.'/filter/basiclti/post.php?'.$param.'" height="'.$height.'" width="'.$width.'" ';
			$text .= 'scrolling="auto" frameborder="1" transparency>   <p>Error</p> </iframe>';
			
		} else {
			//redirect to post page
			$param .= '&target=new';
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . '/filter/basiclti/post.php?'.$param);
			exit;
		}
	}
	
	
	require("../header.php");
	if ($shownav) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; $titlesimp</div>";
		echo '<div id="headershowlinkedtext" class="pagetitle"><h2>'.$titlesimp.'</h2></div>';
	}
	echo '<div style="padding-left:10px; padding-right: 10px;">';
	echo filter($text);
	echo '</div>';
	if ($shownav) {
		echo "<div class=right><a href=\"course.php?cid={$_GET['cid']}\">Return to Course Page</a></div>\n";
	}
	require("../footer.php");	

?>
