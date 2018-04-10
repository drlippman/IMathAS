<?php
//IMathAS:  Displays a linked text item
//(c) 2006 David Lippman
	require("../init.php");
	$linkedtextid = Sanitize::onlyInt($_GET['id']);
	$cid = Sanitize::courseId($_GET['cid']);
	if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($instrPreviewId)) {
		require("../header.php");
		echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
		require("../footer.php");
		exit;
	}
	if (empty($linkedtextid)) {
		echo "<html><body>No item specified. <a href=\"course.php?cid=$cid\">Try again</a></body></html>\n";
		exit;
	}
	if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],'treereader')!==false) {
		$shownav = false;
		$flexwidth = true;
		$nologo = true;
	} else {
		$shownav = true;
	}
	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);
	//DB $query = "SELECT text,title,target FROM imas_linkedtext WHERE id='{$_GET['id']}'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("SELECT text,title,target FROM imas_linkedtext WHERE id=:id");
	$stm->execute(array(':id'=>$linkedtextid));
	//DB $text = mysql_result($result, 0,0);
	//DB $title = mysql_result($result,0,1);
	//DB $target = mysql_result($result,0,2);
	list($text,$title,$target) = $stm->fetch(PDO::FETCH_NUM);
	$titlesimp = strip_tags($title);

	if (substr($text,0,8)=='exttool:') {
		$param = "linkid=".Sanitize::encodeUrlParam($linkedtextid)."&cid=$cid";

		if ($target==0) {
			$height = '500px';
			$width = '95%';
			$param .= '&target=iframe';
			$text = '<iframe id="exttoolframe" src="'.$imasroot.'/filter/basiclti/post.php?'.$param.'" height="'.$height.'" width="'.$width.'" ';
			$text .= 'scrolling="auto" frameborder="1" transparency>   <p>Error</p> </iframe>';
		} else {
			//redirect to post page
			$param .= '&target=new';
			header('Location: ' . $GLOBALS['basesiteurl'] . '/filter/basiclti/post.php?'. $param . "&r=" . Sanitize::randomQueryStringParam());
			exit;
		}
	} else if ((substr($text,0,4)=="http") && (strpos(trim($text)," ")===false)) { //is a web link
		$text = '<p><a href="'.Sanitize::url($text).'" target="_blank">'.Sanitize::encodeStringForDisplay($title).'</a> (will open in a new tab or window)</p>';
	} else if (substr(strip_tags($text),0,5)=="file:") {
		$filename = substr(strip_tags($text),5);
		require_once("../includes/filehandler.php");
		$alink = getcoursefileurl($filename);//$imasroot . "/course/files/".$filename;
		$text = '<p>Download file: <a href="'.Sanitize::url($alink).'">'.Sanitize::encodeStringForDisplay($title).'</a></p>';
	}

	$placeinhead = '';
	if (isset($studentid)) {
		$rec = "data-base=\"linkedintext-".$linkedtextid."\" ";
		$text = str_replace('<a ','<a '.$rec, $text);
		$placeinhead = '<script type="text/javascript">
			function recunload() {
				if (!recordedunload) {
					$.ajax({
						type: "POST",
						url: "'.$imasroot.'/course/rectrack.php?cid='.$cid.'",
						data: "unloadinglinked='.Sanitize::encodeStringForJavascript($linkedtextid).'",
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
	$(function() {$("#exttoolframe").css("height",$(window).height() - $(".midwrapper").position().top - ($(".midwrapper").height()-500) - ($("body").outerHeight(true) - $("body").innerHeight()));});
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
			echo " &gt; ".Sanitize::encodeStringForDisplay($titlesimp)."</div>";
		} else {
			echo "<div class=breadcrumb $fixbc>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
			echo "&gt; ".Sanitize::encodeStringForDisplay($titlesimp)."</div>";
			echo '<div id="headershowlinkedtext" class="pagetitle"><h2>'.Sanitize::encodeStringForDisplay($titlesimp).'</h2></div>';
		}
	}
	echo '<div class="linkedtextholder" style="padding-left:10px; padding-right: 10px;'.$pad.'">';
	$navbuttons = '';
	if ((isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==3) || isset($sessiondata['readernavon'])) {
		$now = time();
		//DB $query = "SELECT il.id,il.title,il.avail,il.startdate,il.enddate,ii.id AS itemid ";
		//DB $query .= "FROM imas_linkedtext as il JOIN imas_items AS ii ON il.id=ii.typeid AND ii.itemtype='LinkedText' ";
		//DB $query .= "WHERE ii.courseid='$cid' ";
		$query = "SELECT il.id,il.title,il.avail,il.startdate,il.enddate,ii.id AS itemid ";
		$query .= "FROM imas_linkedtext as il JOIN imas_items AS ii ON il.id=ii.typeid AND ii.itemtype='LinkedText' ";
		$query .= "WHERE ii.courseid=:courseid ";
		if (!$isteacher && !$istutor) {
			  $query .= "AND (il.avail=2 OR (il.avail=1 AND $now>il.startdate AND $now<il.enddate))";  //$now is safe INT
		}
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid));
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$itemdata = array();
		//DB while ($row = mysql_fetch_assoc($result)) {
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$itemdata[$row['itemid']] = $row;
			if ($row['id']==$linkedtextid) {
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
		//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		getflatlinkeditemlist(unserialize($row[0]));

		$navbuttons .= '<p>&nbsp;</p>';
		if ($thisitemloc>0) {
			$p = $itemdata[$flatlist[$thisitemloc-1]];
			if (isset($studentid) && !isset($sessiondata['stuview'])) {
				$rec = "data-base=\"linkedlink-".Sanitize::onlyInt($p['id'])."\" ";
			} else {
				$rec = '';
			}
			$navbuttons .= '<div class="floatleft" style="width:45%;text-align:center"><a '.$rec.' class="abutton" style="width:100%;padding:4px 0;height:auto;" href="showlinkedtext.php?cid='.$cid.'&id='.Sanitize::encodeUrlParam($p['id']).'">&lt; '._('Previous');
			$navbuttons .= '</a><p class="small" style="line-height:1.4em">'.Sanitize::encodeStringForDisplay($p['title']);
			$navbuttons .= '</p></div>';
		}
		if ($thisitemloc<count($flatlist)-2) {
			$p = $itemdata[$flatlist[$thisitemloc+1]];
			if (isset($studentid) && !isset($sessiondata['stuview'])) {
				$rec = "data-base=\"linkedlink-".Sanitize::onlyInt($p['id'])."\" ";
			} else {
				$rec = '';
			}
			$navbuttons .= '<div class="floatright" style="width:45%;text-align:center"><a '.$rec.' class="abutton" style="width:100%;padding:4px 0;height:auto;" href="showlinkedtext.php?cid='.$cid.'&id='.Sanitize::encodeUrlParam($p['id']).'"> '._('Next');
			$navbuttons .= ' &gt;</a><p class="small" style="line-height:1.4em">'.Sanitize::encodeStringForDisplay($p['title']);
			$navbuttons .= '</p></div>';
		}
		$navbuttons .= '<div class="clear"></div>';
	}
	if ($navbuttons != '') {
		if (strpos($text, 'smallattr')!==false) {
			$text = preg_replace('/(<hr[^>]*>\s*<div[^>]*smallattr[^>]*>)/sm', $navbuttons.'$1', $text);
		} else {
			$text .= '<hr/>'.$navbuttons;
		}
	}
	echo Sanitize::outgoingHtml(filter($text));
	echo '</div>';
	if ($shownav) {
		if (isset($_SESSION['backtrack'])) {
			echo "<div class=right><a href=\"course.php?cid=$cid&folder=".$_SESSION['backtrack'][1]."\">Back</a></div>\n";
		} else {
			echo "<div class=right><a href=\"course.php?cid=$cid\">Return to Course Page</a></div>\n";
		}
	}
	require("../footer.php");

?>
