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
	$placeinhead = '';
	if (isset($studentid)) {
		$rec = "data-base=\"linkedintext-{$_GET['id']}\" ";
		$text = str_replace('<a ','<a '.$rec, $text);
		$placeinhead = '<script type="text/javascript">
			function recunload() {
				if (!recordedunload) {
					$.ajax({
						type: "POST",
						url: "'.$imasroot.'/course/rectrack.php?cid='.$cid.'",
						data: "unloadinglinked='.$_GET['id'].'",
						async: false
					   });
					recordedunload = true;
				}
			}
			window.onunload = window.onbeforeunload = recunload;
		 </script>';
	}
	$placeinhead .= '<script type="text/javascript"> $(function() {
	$(".im_glossterm").addClass("hoverdef").each(function(i,el) { 
		$(el).attr("title",$(el).next(".im_glossdef").text());
	   });
	});
	</script>';
	require("../header.php");
	if ((isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==3)) {
		$fixbc = 'style="position:fixed;top:0;width:100%"';
		$pad = 'padding-top: 25px;';
	} else {
		$fixbc = '';  $pad = '';
	}
	if ($shownav) {
		if (isset($_SESSION['backtrack'])) {
			echo '<div class="breadcrumb" '.$fixbc.'>'.$_SESSION['backtrack'][0];
			echo " &gt; $titlesimp</div>";
		} else {
			echo "<div class=breadcrumb $fixbc>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; $titlesimp</div>";
			echo '<div id="headershowlinkedtext" class="pagetitle"><h2>'.$titlesimp.'</h2></div>';
		}
	}
	echo '<div class="linkedtextholder" style="padding-left:10px; padding-right: 10px;'.$pad.'">';
	$navbuttons = '';
	if ((isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==3) || isset($sessiondata['readernavon'])) {
		$now = time();
		$query = "SELECT il.id,il.title,il.avail,il.startdate,il.enddate,ii.id AS itemid 
			  FROM imas_linkedtext as il JOIN imas_items AS ii ON il.id=ii.typeid AND ii.itemtype='LinkedText'
			  WHERE ii.courseid='$cid' ";
		if (!$isteacher && !$istutor) {	 
			  $query .= "AND (il.avail=2 OR (il.avail=1 AND $now>il.startdate AND $now<il.enddate))";
		}
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$itemdata = array();
		while ($row = mysql_fetch_assoc($result)) {
			$itemdata[$row['itemid']] = $row;
			if ($row['id']==$_GET['id']) {
				$thisitemid = $row['itemid'];
			}
		}
		
		$flatlist = array();
		$thisitemloc = -1;
		function getflatlinkeditemlist($items) {
			global $flatlist, $itemdata, $now, $isteacher, $istutor, $thisitemloc,$thisitemid;
			foreach ($items as $it) {
				if (is_array($it)) {
					if ($isteacher || $istutor || $it['avail']==2 || ($it['avail']==1 && $now>$it['startdate'] && $now<$it['enddate'])) {
						getflatlinkeditemlist($it['items']);
					}
				} else {
					if (isset($itemdata[$it])) {
						$flatlist[] = $it;
						if ($it==$thisitemid) {
							$thisitemloc = count($flatlist)-1;
						}
					}
				}
			}
		}
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		getflatlinkeditemlist(unserialize($row[0]));
		
		$navbuttons .= '<p>&nbsp;</p>';
		if ($thisitemloc>0) {
			$p = $itemdata[$flatlist[$thisitemloc-1]];
			if (isset($studentid) && !isset($sessiondata['stuview'])) {
				$rec = "data-base=\"linkedlink-{$p['id']}\" ";
			} else {
				$rec = '';
			}
			$navbuttons .= '<div class="floatleft" style="width:45%;text-align:center"><a '.$rec.' class="abutton" style="width:100%;padding:4px 0;height:auto;" href="showlinkedtext.php?cid='.$cid.'&id='.$p['id'].'">&lt; '._('Previous');
			$navbuttons .= '</a><p class="small" style="line-height:1.4em">'.$p['title'];
			$navbuttons .= '</p></div>';
		}
		if ($thisitemloc<count($flatlist)-2) {
			$p = $itemdata[$flatlist[$thisitemloc+1]];
			if (isset($studentid) && !isset($sessiondata['stuview'])) {
				$rec = "data-base=\"linkedlink-{$p['id']}\" ";
			} else {
				$rec = '';
			}
			$navbuttons .= '<div class="floatright" style="width:45%;text-align:center"><a '.$rec.' class="abutton" style="width:100%;padding:4px 0;height:auto;" href="showlinkedtext.php?cid='.$cid.'&id='.$p['id'].'"> '._('Next');
			$navbuttons .= ' &gt;</a><p class="small" style="line-height:1.4em">'.$p['title'];
			$navbuttons .= '</p></div>';
		}
		$navbuttons .= '<div class="clear"></div>';
	}
	if ($navbuttons != '') {
		$text = preg_replace('/(<hr[^>]*>\s*<div[^>]*smallattr[^>]*>)/sm', $navbuttons.'$1', $text);
	}
	echo filter($text);
	echo '</div>';
	if ($shownav) {
		if (isset($_SESSION['backtrack'])) {
			echo "<div class=right><a href=\"course.php?cid={$_GET['cid']}&folder=".$_SESSION['backtrack'][1]."\">Back</a></div>\n";
		} else {
			echo "<div class=right><a href=\"course.php?cid={$_GET['cid']}\">Return to Course Page</a></div>\n";
		}
	}
	require("../footer.php");	

?>
