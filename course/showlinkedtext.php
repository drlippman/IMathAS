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
	
	if (isset($studentid)) {
		$rec = "data-base=\"linkedintext-{$_GET['id']}\" ";
		$text = str_replace('<a ','<a '.$rec, $text);
	}
	
	require("../header.php");
	if ($shownav) {
		if (isset($_SESSION['backtrack'])) {
			echo '<div class="breadcrumb">'.$_SESSION['backtrack'][0];
			echo " &gt; $titlesimp</div>";
		} else {
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; $titlesimp</div>";
			echo '<div id="headershowlinkedtext" class="pagetitle"><h2>'.$titlesimp.'</h2></div>';
		}
	}
	echo '<div class="linkedtextholder" style="padding-left:10px; padding-right: 10px;">';
	echo filter($text);
	echo '</div>';
	if ($shownav) {
		if (isset($_GET['prev'])) {
			echo '<div class="floatleft" style="max-width:40%;height:auto;"><button type="button" onclick="window.location.href=\'showlinkedtext.php?cid='.$cid.'&id='.$_GET['prev'].'\'">&lt; '._('Previous');
			if (isset($_GET['prevtitle'])) {
				echo ': '.$_GET['prevtitle'];
			}
			echo '</button></div>';
		}
		if (isset($_GET['next'])) {
			echo '<div class="floatright style="max-width:40%;height:auto;"><button type="button" onclick="window.location.href=\'showlinkedtext.php?cid='.$cid.'&id='.$_GET['next'].'\'">'._('Next');
			if (isset($_GET['nexttitle'])) {
				echo ': '.$_GET['nexttitle'];
			}
			echo ' &gt;</button></div>';
		}
		echo '<div class="clear"></div>';
		if (isset($_SESSION['backtrack'])) {
			echo "<div class=right><a href=\"course.php?cid={$_GET['cid']}&folder=".$_SESSION['backtrack'][1]."\">Back</a></div>\n";
		} else {
			echo "<div class=right><a href=\"course.php?cid={$_GET['cid']}\">Return to Course Page</a></div>\n";
		}
	}
	require("../footer.php");	

?>
