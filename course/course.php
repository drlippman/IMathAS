<?php
//IMathAS:  Main course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("courseshowitems.php");
require("../includes/htmlutil.php");
require("../includes/calendardisp.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($instrPreviewId)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = _("You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n");
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = Sanitize::courseId($_GET['cid']);

	if (isset($teacherid) && isset($sessiondata['sessiontestid']) && !isset($sessiondata['actas']) && $sessiondata['courseid']==$cid) {
		//clean up coming out of an assessment
		require_once("../includes/filehandler.php");
		//deleteasidfilesbyquery(array('id'=>$sessiondata['sessiontestid']),1);
		deleteasidfilesbyquery2('id',$sessiondata['sessiontestid'],null,1);
		//DB $query = "DELETE FROM imas_assessment_sessions WHERE id='{$sessiondata['sessiontestid']}' LIMIT 1";
		//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE id=:id LIMIT 1");
		$stm->execute(array(':id'=>$sessiondata['sessiontestid']));
	}

	if (isset($teacherid) && isset($_GET['from']) && isset($_GET['to'])) {
		$from = $_GET['from'];
		$to = $_GET['to'];
		$block = $_GET['block'];
		//DB $query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $items = unserialize(mysql_result($result,0,0));
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));
		$blocktree = explode('-',$block);
		$sub =& $items;
		for ($i=1;$i<count($blocktree)-1;$i++) {
			$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
		if (count($blocktree)>1) {
			$curblock =& $sub[$blocktree[$i]-1]['items'];
			$blockloc = $blocktree[$i]-1;
		} else {
			$curblock =& $sub;
		}

		$blockloc = $blocktree[count($blocktree)-1]-1;
	   	//$sub[$blockloc]['items'] is block with items

		if (strpos($to,'-')!==false) {  //in or out of block
			if ($to[0]=='O') {  //out of block
				$itemtomove = $curblock[$from-1];  //+3 to adjust for other block params
				//$to = substr($to,2);
				array_splice($curblock,$from-1,1);
				if (is_array($itemtomove)) {
					array_splice($sub,$blockloc+1,0,array($itemtomove));
				} else {
					array_splice($sub,$blockloc+1,0,$itemtomove);
				}
			} else {  //in to block
				$itemtomove = $curblock[$from-1];  //-1 to adjust for 0 indexing vs 1 indexing
				array_splice($curblock,$from-1,1);
				$to = substr($to,2);
				if ($from<$to) {$adj=1;} else {$adj=0;}
				array_push($curblock[$to-1-$adj]['items'],$itemtomove);
			}
		} else { //move inside block
			$itemtomove = $curblock[$from-1];  //-1 to adjust for 0 indexing vs 1 indexing
			array_splice($curblock,$from-1,1);
			if (is_array($itemtomove)) {
				array_splice($curblock,$to-1,0,array($itemtomove));
			} else {
				array_splice($curblock,$to-1,0,$itemtomove);
			}
		}
		//DB $itemlist = addslashes(serialize($items));
		$itemlist = serialize($items);
		//DB $query = "UPDATE imas_courses SET itemorder='$itemlist' WHERE id='{$_GET['cid']}'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemlist, ':id'=>$cid));
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_GET['cid']) . "&r=" . Sanitize::randomQueryStringParam());
	}

	$stm = $DBH->prepare("SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,toolset,latepasshrs FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	if ($line == null) {
		$overwriteBody = 1;
		$body = _("Course does not exist.  <a hre=\"../index.php\">Return to main page</a>") . "</body></html>\n";
	}

	$allowunenroll = $line['allowunenroll'];
	$pagetitle = $line['name'];
	$items = unserialize($line['itemorder']);
	$msgset = $line['msgset']%5;
	$toolset = $line['toolset'];
	$latepasshrs = $line['latepasshrs'];
	$useleftnav = true;

	if (isset($teacherid) && isset($_GET['togglenewflag'])) { //handle toggle of NewFlag
		$sub =& $items;
		$blocktree = explode('-',$_GET['togglenewflag']);
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree)-1;$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
		$sub =& $sub[$blocktree[$i]-1];
		if (!isset($sub['newflag']) || $sub['newflag']==0) {
			$sub['newflag']=1;
		} else {
			$sub['newflag']=0;
		}
		//DB $itemlist = addslashes(serialize($items));
		$itemlist = serialize($items);
		//DB $query = "UPDATE imas_courses SET itemorder='$itemlist' WHERE id='$cid'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemlist, ':id'=>$cid));
	}

	//enable teacher guest access
	if (isset($instrPreviewId)) {
		$tutorid = $instrPreviewId;
	}

	if ((!isset($_GET['folder']) || $_GET['folder']=='') && !isset($sessiondata['folder'.$cid])) {
		$_GET['folder'] = '0';
		$sessiondata['folder'.$cid] = '0';
		writesessiondata();
	} else if ((isset($_GET['folder']) && $_GET['folder']!='') && (!isset($sessiondata['folder'.$cid]) || $sessiondata['folder'.$cid]!=$_GET['folder'])) {
		$sessiondata['folder'.$cid] = $_GET['folder'];
		writesessiondata();
	} else if ((!isset($_GET['folder']) || $_GET['folder']=='') && isset($sessiondata['folder'.$cid])) {
		$_GET['folder'] = $sessiondata['folder'.$cid];
	}
	if (!isset($_GET['quickview']) && !isset($sessiondata['quickview'.$cid])) {
		$quickview = false;
	} else if (isset($_GET['quickview'])) {
		$quickview = $_GET['quickview'];
		$sessiondata['quickview'.$cid] = $quickview;
		writesessiondata();
	} else if (isset($sessiondata['quickview'.$cid])) {
		$quickview = $sessiondata['quickview'.$cid];
	}
	if ($quickview=="on") {
		$_GET['folder'] = '0';
		//$useleftnav = false;
	}
	if (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==3) { //folder view
		if ($sessiondata['lti_keytype']!='cc-of') {
			$useleftnav = false;
		}
		$nocoursenav = true;
	}
	//get exceptions
	$now = time();
	$exceptions = array();
	if (!isset($teacherid) && !isset($tutorid)) {
		$exceptions = loadExceptions($cid, $userid);
	}
	//update block start/end dates to show blocks containing items with exceptions
	if (count($exceptions)>0) {
		upsendexceptions($items);
	}

	if ($_GET['folder']!='0') {
		$now = time();
		$blocktree = array_map('intval', explode('-',$_GET['folder']));
		$backtrack = array();
		for ($i=1;$i<count($blocktree);$i++) {
			$backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
			if (!isset($teacherid) && !isset($tutorid) && $items[$blocktree[$i]-1]['avail']<2 && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'] || $items[$blocktree[$i]-1]['avail']=='0')) {
				$_GET['folder'] = 0;
				$items = unserialize($line['itemorder']);
				unset($backtrack);
				unset($blocktree);
				break;
			}
			if (isset($items[$blocktree[$i]-1]['grouplimit']) && count($items[$blocktree[$i]-1]['grouplimit'])>0 && !isset($teacherid) && !isset($tutorid)) {
				if (!in_array('s-'.$studentinfo['section'],$items[$blocktree[$i]-1]['grouplimit'])) {
					echo 'Not authorized';
					exit;
				}
			}
			$items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
	}
	//DEFAULT DISPLAY PROCESSING
	//$jsAddress1 = $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_GET['cid']);
	$jsAddress2 = $GLOBALS['basesiteurl'] . "/course/";
	//$jsAddress2 = $GLOBALS['basesiteurl'] . "/course";

	$openblocks = Array(0);
	$prevloadedblocks = array(0);
	if (isset($_COOKIE['openblocks-'.$cid]) && $_COOKIE['openblocks-'.$cid]!='') {$openblocks = explode(',',$_COOKIE['openblocks-'.$cid]); $firstload=false;} else {$firstload=true;}
	if (isset($_COOKIE['prevloadedblocks-'.$cid]) && $_COOKIE['prevloadedblocks-'.$cid]!='') {$prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$cid]);}
	$plblist = implode(',',$prevloadedblocks);
	$oblist = implode(',',$openblocks);

	$curBreadcrumb = $breadcrumbbase;
	if (isset($backtrack) && count($backtrack)>0) {
		if (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==3) {
			$curBreadcrumb = '';
			$sendcrumb = '';
			$depth = substr_count($sessiondata['ltiitemid'][1],'-');
			for ($i=$depth-1;$i<count($backtrack);$i++) {
				if ($i>$depth-1) {
					$curBreadcrumb .= " &gt; ";
					$sendcrumb .= " &gt; ";
				}
				if ($i!=count($backtrack)-1) {
					$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder=" . Sanitize::encodeUrlParam($backtrack[$i][1]) . "\">";
				}
				//DB $sendcrumb .= "<a href=\"course.php?cid=$cid&folder={$backtrack[$i][1]}\">".stripslashes($backtrack[$i][0]).'</a>';
				$sendcrumb .= "<a href=\"course.php?cid=$cid&folder=" . Sanitize::encodeUrlParam($backtrack[$i][1]) . "\">".Sanitize::encodeStringForDisplay($backtrack[$i][0]).'</a>';
				//DB $curBreadcrumb .= stripslashes($backtrack[$i][0]);
				$curBreadcrumb .= Sanitize::encodeStringForDisplay($backtrack[$i][0]);
				if ($i!=count($backtrack)-1) {
					$curBreadcrumb .= "</a>";
				}
			}
			$curname = $backtrack[count($backtrack)-1][0];
			if (count($backtrack)>$depth) {
				$backlink = "<span class=right><a href=\"course.php?cid=$cid&folder=".Sanitize::encodeUrlParam($backtrack[count($backtrack)-2][1])."\">" . _('Back') . "</a></span><br class=\"form\" />";
			}
			$_SESSION['backtrack'] = array($sendcrumb,$backtrack[count($backtrack)-1][1]);

		} else {
			$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder=0\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
			for ($i=0;$i<count($backtrack);$i++) {
				$curBreadcrumb .= " &gt; ";
			if ($i!=count($backtrack)-1) {
				$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder=" . Sanitize::encodeUrlParam($backtrack[$i][1]) . "\">";
			}
				//DB $curBreadcrumb .= stripslashes($backtrack[$i][0]);
				$curBreadcrumb .= Sanitize::encodeStringForDisplay($backtrack[$i][0]);
				if ($i!=count($backtrack)-1) {
					$curBreadcrumb .= "</a>";
				}
            }
            $curname = $backtrack[count($backtrack)-1][0];
            if (count($backtrack)==1) {
                $backlink =  "<span class=right><a href=\"course.php?cid=$cid&folder=0\">" . _('Back') . "</a></span><br class=\"form\" />";
            } else {
                $backlink = "<span class=right><a href=\"course.php?cid=$cid&folder=".Sanitize::encodeUrlParam($backtrack[count($backtrack)-2][1])."\">" . _('Back') . "</a></span><br class=\"form\" />";
            }
		}
	} else {
		$curBreadcrumb .= Sanitize::encodeStringForDisplay($coursename);
		$curname = Sanitize::encodeStringForDisplay($coursename);
	}


	if ($msgset<4) {
	   //DB $query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND courseid='$cid' AND (isread=0 OR isread=4)";
	   //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	   //DB $msgcnt = mysql_result($result,0,0);
	   $stm = $DBH->prepare("SELECT COUNT(id) FROM imas_msgs WHERE msgto=:msgto AND courseid=:courseid AND (isread=0 OR isread=4)");
	   $stm->execute(array(':msgto'=>$userid, ':courseid'=>$cid));
	   $msgcnt = $stm->fetchColumn(0);
	   if ($msgcnt>0) {
		   $newmsgs = " <a href=\"$imasroot/msgs/msglist.php?page=-1&cid=$cid\" class=noticetext>" . sprintf(_('New (%d)'), $msgcnt) . "</a>";
	   } else {
		   $newmsgs = '';
	   }
	}
	/* very old
	$query = "SELECT count(*) FROM ";
	$query .= "(SELECT imas_forum_posts.threadid,max(imas_forum_posts.postdate),mfv.lastview FROM imas_forum_posts ";
	$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_posts.threadid AND mfv.userid='$userid' WHERE imas_forums.courseid='$cid' ";
	$query .= "GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))) AS newitems ";
	*/
	/*
	$query = "SELECT count(*) FROM ";
	$query .= "(SELECT imas_forum_threads.id FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid='$cid' AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))) AS newitems ";
	*/
	$now = time();
	//DB $query = "SELECT imas_forum_threads.forumid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
	//DB $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forums.courseid='$cid' ";
	//DB if (!isset($teacherid)) {
		//DB $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
	//DB }
	//DB $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' ";
	//DB $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
	//DB if (!isset($teacherid)) {
		//DB $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid='$userid')) ";
	//DB }
	//DB $query .= "GROUP BY imas_forum_threads.forumid";
	$query = "SELECT imas_forum_threads.forumid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forums.courseid=:courseid ";
	if (!isset($teacherid)) {
		$query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
	}
	$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid ";
	$query .= "WHERE imas_forum_threads.lastposttime<:now AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
	if (!isset($teacherid)) {
		$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userid2)) ";
	}
	$query .= "GROUP BY imas_forum_threads.forumid";
	$stm = $DBH->prepare($query);
	if (!isset($teacherid)) {
		$stm->execute(array(':now'=>$now, ':courseid'=>$cid, ':userid'=>$userid, ':userid2'=>$userid));
	} else {
		$stm->execute(array(':now'=>$now, ':courseid'=>$cid, ':userid'=>$userid));
	}



	/*$query = "SELECT imas_forum_threads.forumid,imas_forum_threads.id FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forums.courseid='$cid' ";
	$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' ";
	$query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
	$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid='$userid')) ";
	*/
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$newpostcnts = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$newpostcnts[$row[0]] = $row[1];
	}
	if (array_sum($newpostcnts)>0) {
		$newpostscnt = " <a href=\"$imasroot/forums/newthreads.php?cid=$cid\" class=noticetext>" . sprintf(_('New (%d)'), array_sum($newpostcnts)) . "</a>";
	} else {
		$newpostscnt = '';
	}

	//get items with content views, for enabling stats link
	/*
	//removed - always showing stats link now.
	if (isset($teacherid) || isset($tutorid)) {
		$hasstats = array();
		$query = "SELECT DISTINCT(CONCAT(SUBSTRING(type,1,1),typeid)) FROM imas_content_track WHERE courseid='$cid' AND type IN ('inlinetext','linkedsum','linkedlink','linkedintext','linkedviacal','assessintro','assess','assesssum','wiki','wikiintext') ";
		//not sure this is useful information, since this is in the list posts by name page, and we don't track forum views in content tracking
		//$query .= "UNION SELECT DISTINCT(CONCAT(SUBSTRING(type,1,1),info)) FROM imas_content_track WHERE courseid='$cid' AND type in ('forumpost','forumreply')";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$hasstats[$row[0]] = true;
		}
	}
	*/

	//get read linked items
	$readlinkeditems = array();
	if ($coursetheme=='otbsreader.css' && isset($studentid)) {
		//DB $query = "SELECT DISTINCT typeid FROM imas_content_track WHERE userid='$userid' AND type='linkedlink' AND courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT DISTINCT typeid FROM imas_content_track WHERE userid=:userid AND type='linkedlink' AND courseid=:courseid");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$readlinkeditems[$row[0]] = true;
		}
	}

	//get latepasses
	if (!isset($teacherid) && !isset($tutorid) && !$inInstrStuView && isset($studentinfo)) {
	   //$query = "SELECT latepass FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
	   //$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   //$latepasses = mysql_result($result,0,0);
	   $latepasses = $studentinfo['latepasses'];
	} else {
		$latepasses = 0;
	}
}

$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/course.js?v=072917\"></script>";
if (isset($tutorid) && isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==3) {
	$placeinhead .= '<script type="text/javascript">$(function(){$(".instrdates").hide();});</script>';
}

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {

	if (isset($teacherid)) {
 ?>
	<script type="text/javascript">
		//function moveitem(from,blk) {
		//	var to = document.getElementById(blk+'-'+from).value;
        //
		//	if (to != from) {
		//		var toopen = '<?php //echo $jsAddress1 ?>//&block=' + blk + '&from=' + from + '&to=' + to;
		//		window.location = toopen;
		//	}
		//}
		function moveDialog(block,item) {
			GB_show(_("Move Item"), imasroot+"/course/moveitem.php?cid="+cid+"&item="+item+"&block="+block, 600, "auto");
			return false;
		}
		function additem(blk,tb) {
			var type = document.getElementById('addtype'+blk+'-'+tb).value;
			if (tb=='BB' || tb=='LB') { tb = 'b';}
			if (type!='') {
				var toopen = '<?php echo $jsAddress2 ?>add' + type + '.php?block='+blk+'&tb='+tb+'&cid=<?php echo $cid; ?>';
				window.location = toopen;
			}
		}
	</script>

<?php
	}
?>
	<script type="text/javascript">
		var getbiaddr = 'getblockitems.php?cid=<?php echo $cid ?>&folder=';
		var oblist = '<?php echo Sanitize::encodeStringForJavascript($oblist); ?>';
		var plblist = '<?php echo Sanitize::encodeStringForJavascript($plblist); ?>';
		var cid = '<?php echo $cid ?>';
	</script>

<?php
	//check for course layout
	if (isset($CFG['GEN']['courseinclude'])) {
		require($CFG['GEN']['courseinclude']);
		if ($firstload) {
			echo "<script>document.cookie = 'openblocks-$cid=' + oblist;\n";
			echo "document.cookie = 'loadedblocks-$cid=0';</script>\n";
		}
		require("../footer.php");
		exit;
	}
?>
	<div class=breadcrumb>
		<?php
		if (isset($CFG['GEN']['logopad'])) {
			echo '<span class="padright hideinmobile" style="padding-right:'.$CFG['GEN']['logopad'].'">';
		} else {
			echo '<span class="padright hideinmobile">';
		}
		if (isset($instrPreviewId)) {
			echo '<span class="noticetext">', _('Instructor Preview'), '</span> ';
		}
		if (isset($sessiondata['ltiitemtype'])) {
			echo "<a href=\"#\" onclick=\"GB_show('"._('User Preferences')."','$imasroot/admin/ltiuserprefs.php?cid=$cid&greybox=true',800,'auto');return false;\" title=\""._('User Preferences')."\" aria-label=\""._('Edit User Preferences')."\">";
			echo "<span id=\"myname\">".Sanitize::encodeStringForDisplay($userfullname)."</span>";
			echo "<img style=\"vertical-align:top\" src=\"$imasroot/img/gears.png\" alt=\"\"/></a>";
		} else {
			echo Sanitize::encodeStringForDisplay($userfullname);
		}
		?>
		</span>
		<?php

		if ($useleftnav) {
			if ($didnavlist && !isset($teacherid) && !$inInstrStuView) {
				$incclass = 'class="hideifnavlist"';
			} else {
				$incclass = '';
			}
			echo '<span id="leftcontenttoggle" '.$incclass.' aria-hidden="true"><img alt="menu" style="cursor:pointer" src="'.$imasroot.'/img/menu.png"></span> ';
		}
		echo $curBreadcrumb;
		?>
		<div class=clear></div>
	</div>

<?php
	if ($useleftnav && isset($teacherid)) {
?>
	<div id="leftcontent" class="hiddenmobile" role="navigation" aria-label="<?php echo _('Instructor tool navigation');?>">
		<p class="showinmobile"><b><?php echo _('Views'); ?></b><br/>
			<a href="course.php?cid=<?php echo $cid ?>&stuview=on"><?php echo _('Student View'); ?></a><br/>
			<a href="course.php?cid=<?php echo $cid ?>&quickview=on"><?php echo _('Quick Rearrange'); ?></a>
		</p>

		<p>
		<b><?php echo _('Communication'); ?></b><br/>
			<a href="<?php echo $imasroot ?>/msgs/msglist.php?cid=<?php echo $cid ?>&folder=<?php echo Sanitize::encodeUrlParam($_GET['folder']); ?>" class="essen">
			<?php echo _('Messages'); ?></a> <?php echo $newmsgs; ?> <br/>
			<a href="<?php echo $imasroot ?>/forums/forums.php?cid=<?php echo $cid ?>&folder=<?php echo Sanitize::encodeUrlParam($_GET['folder']); ?>" class="essen">
			<?php echo _('Forums'); ?></a> <?php echo $newpostscnt; ?>
		</p>
	<?php
	if (isset($CFG['CPS']['leftnavtools']) && $CFG['CPS']['leftnavtools']=='limited') {
	?>
		<p><b><?php echo _('Tools'); ?></b><br/>
			<a href="managestugrps.php?cid=<?php echo $cid ?>"><?php echo _('Groups'); ?></a><br/>
			<a href="addoutcomes.php?cid=<?php echo $cid ?>"><?php echo _('Outcomes'); ?></a><br/>
		</p>
	<?php
	} else if (!isset($CFG['CPS']['leftnavtools']) || $CFG['CPS']['leftnavtools']!==false) {
	?>
		<p><b><?php echo _('Tools'); ?></b><br/>
			<a href="listusers.php?cid=<?php echo $cid ?>" class="essen"><?php echo _('Roster'); ?></a><br/>
			<a href="gradebook.php?cid=<?php echo $cid ?>" class="essen"><?php echo _('Gradebook'); ?></a> <?php if (($coursenewflag&1)==1) {echo '<span class="noticetext">', _('New'), '</span>';}?><br/>
	                <a href="coursereports.php?cid=<?php echo $cid ?>">Reports</a><br/>
			<a href="managestugrps.php?cid=<?php echo $cid ?>"><?php echo _('Groups'); ?></a><br/>
			<a href="addoutcomes.php?cid=<?php echo $cid ?>"><?php echo _('Outcomes'); ?></a><br/>
			<a href="showcalendar.php?cid=<?php echo $cid ?>"><?php echo _('Calendar'); ?></a><br/>
			<a href="coursemap.php?cid=<?php echo $cid ?>"><?php echo _('Course Map'); ?></a>
		</p>
	<?php
	}
	?>

		<p><b><?php echo _('Questions'); ?></b><br/>
			<a href="manageqset.php?cid=<?php echo $cid ?>"><?php echo _('Manage'); ?></a><br/>
			<a href="managelibs.php?cid=<?php echo $cid ?>"><?php echo _('Libraries'); ?></a>
		</p>
<?php
		if ($allowcourseimport) {
?>
		<p><b><?php echo _('Export/Import'); ?></b><br/>
			<a href="../admin/export.php?cid=<?php echo $cid ?>"><?php echo _('Export Question Set'); ?></a><br/>
			<a href="../admin/import.php?cid=<?php echo $cid ?>"><?php echo _('Import Question Set'); ?></a><br/>
			<a href="../admin/exportlib.php?cid=<?php echo $cid ?>"><?php echo _('Export Libraries'); ?></a><br/>
			<a href="../admin/importlib.php?cid=<?php echo $cid ?>"><?php echo _('Import Libraries'); ?></a>
		</p>
<?php
		}
?>
		<p><b><?php echo _('Course Items'); ?></b><br/>
			<a href="copyitems.php?cid=<?php echo $cid ?>"><?php echo _('Copy'); ?></a><br/>
			<a href="../admin/ccexport.php?cid=<?php echo $cid ?>"><?php echo _('Export'); ?></a>
		<?php if (!isset($CFG['GEN']['noimathasimportfornonadmins']) || $myrights>=75) { ?>
			<br/><a href="../admin/importitems2.php?cid=<?php echo $cid ?>"><?php echo _('Import'); ?></a>
		<?php } ?>
		</p>

		<p><b><?php echo _('Mass Change'); ?></b><br/>
			<a href="chgassessments.php?cid=<?php echo $cid ?>"><?php echo _('Assessments'); ?></a><br/>
			<a href="chgforums.php?cid=<?php echo $cid ?>"><?php echo _('Forums'); ?></a><br/>
			<a href="chgblocks.php?cid=<?php echo $cid ?>"><?php echo _('Blocks'); ?></a><br/>
			<a href="masschgdates.php?cid=<?php echo $cid ?>"><?php echo _('Dates'); ?></a><br/>
			<a href="timeshift.php?cid=<?php echo $cid ?>"><?php echo _('Time Shift'); ?></a>
		</p>
		<p>
			<a href="../admin/forms.php?action=modify&id=<?php echo $cid ?>&cid=<?php echo $cid ?>"><?php echo _('Course Settings'); ?></a><br/>
			<a href="<?php echo $imasroot ?>/help.php?section=coursemanagement"><?php echo _('Help'); ?></a><br/>
			<a href="../actions.php?action=logout"><?php echo _('Log Out'); ?></a>
		</p>
	</div>
	<div id="centercontent">
<?php
	} else if ($useleftnav && !isset($teacherid)) {
?>
		<div id="leftcontent" class="hiddenmobile"  role="navigation" aria-label="<?php echo _('Tools navigation');?>">

<?php
		if ($inInstrStuView) { //instructor in student view
?>
		  <p class="showinmobile"><b><?php echo _('Views'); ?></b><br/>
			<a href="course.php?cid=<?php echo $cid ?>&quickview=off&teachview=1"><?php echo _('Instructor View'); ?></a><br/>
			<a href="course.php?cid=<?php echo $cid ?>&quickview=on"><?php echo _('Quick Rearrange'); ?></a>
		  </p>
<?php
		}

		echo '<p>';
		if ($msgset<4) {
				echo '<a href="'.$imasroot.'/msgs/msglist.php?cid='.$cid.'&amp;folder=' . Sanitize::encodeUrlParam($_GET['folder']) . '" class="essen"> ';
				echo _('Messages').'</a> '.$newmsgs .' <br/>';
			}
			if (($toolset&2)==0) {
				echo '<a href="'.$imasroot.'/forums/forums.php?cid='.$cid.'&amp;folder=' . Sanitize::encodeUrlParam($_GET['folder']) . '" class="essen">';
				echo _('Forums').'</a> '.$newpostscnt.'<br/>';
			}
		if (($toolset&1)==0) {
			echo '<a href="showcalendar.php?cid='.$cid.'" class="essen">'._('Calendar').'</a><br/>';
		}
		echo '<a href="coursemap.php?cid='.$cid.'">'._('Course Map').'</a>';
		echo '</p>';

	?>

			<p>
			<a href="gradebook.php?cid=<?php echo $cid ?>" class="essen"><?php echo _('Gradebook'); ?></a> <?php if (($coursenewflag&1)==1) {echo '<span class="noticetext">', _('New'), '</span>';}?>
			</p>
	<?php
		if (!isset($sessiondata['ltiitemtype'])) { //don't show in LTI embed
	?>
			<p>
			<a href="../actions.php?action=logout"><?php echo _('Log Out'); ?></a><br/>
			<a href="<?php echo $imasroot ?>/help.php?section=usingimas"><?php printf(_('Help Using %s'), $installname); ?></a>
			</p>
			<?php
			if ($myrights > 5 && $allowunenroll==1) {
				echo "<p><a href=\"../forms.php?action=unenroll&cid=$cid\">", _('Unenroll From Course'), "</a></p>\n";
			}
		}
			?>
		</div>
		<div id="centercontent">
<?php
	}
   makeTopMenu();
   echo "<div id=\"headercourse\" class=\"pagetitle\"><h2>".Sanitize::encodeStringForDisplay($curname)."</h2></div>\n";

   if (count($items)>0) {


	   if ($quickview=='on' && isset($teacherid)) {
		   echo '<style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;}</style>';
		   echo "<script>var AHAHsaveurl = '$imasroot/course/savequickreorder.php?cid=$cid';";
		   echo 'var unsavedmsg = "'._("You have unrecorded changes.  Are you sure you want to abandon your changes?").'";';
		   echo 'var itemorderhash="'.md5(serialize($items)).'";';
		   echo "</script>";
		   echo "<script src=\"$imasroot/javascript/mootools.js\"></script>";
		   echo "<script src=\"$imasroot/javascript/nested1.js?v=011917\"></script>";
		   echo '<p><button type="button" onclick="quickviewexpandAll()">'._("Expand All").'</button> ';
		   echo '<button type="button" onclick="quickviewcollapseAll()">'._("Collapse All").'</button></p>';

		   echo '<ul id=qviewtree class=qview>';
		   quickview($items,0);
		   echo '</ul>';
		   echo '<p>&nbsp;</p>';
	   } else {
		   showitems($items,$_GET['folder']);
	   }

   } else {
	   if (isset($teacherid) && $quickview!='on') {
	   	   if ($_GET['folder']=='0') {
			echo '<p><b>Welcome to your course!</b></p>';
			echo '<p>To start by copying from another course, use the <a href="copyitems.php?cid='.$cid.'">Course Items: Copy</a> ';
			echo 'link along the left side of the screen.</p><p>If you want to build from scratch, use the "Add An Item" pulldown below to get started.</p><p>&nbsp;</p>';
	   	   }
	   	// $_GET['folder'] is sanitized in generateadditem()
	   	echo generateadditem($_GET['folder'],'t');
	   }
   }
   if (isset($backlink)) {
	   echo $backlink;
   }

   echo "</div>"; //centercontent

   if ($firstload) {
		echo "<script>document.cookie = 'openblocks-$cid=' + oblist;\n";
		echo "document.cookie = 'loadedblocks-$cid=0';</script>\n";
   }
}

require("../footer.php");

function makeTopMenu() {
	global $teacherid;
	global $msgset;
	global $imasroot;
	global $cid;
	global $quickview;
	global $CFG;
	global $inInstrStuView;
	global $useleftnav;

	if (isset($teacherid) || $inInstrStuView) {
		echo '<div id="viewbuttoncont" class="hideinmobile">';

		echo 'View: ';
		echo "<a href=\"course.php?cid=$cid&quickview=off&teachview=1\" ";
		if (!$inInstrStuView && $quickview != 'on') {
			echo 'class="buttonactive buttoncurveleft"';
		} else {
			echo 'class="buttoninactive buttoncurveleft"';
		}
		echo '>', _('Instructor'), '</a>';
		echo "<a href=\"course.php?cid=$cid&quickview=off&stuview=on\" ";
		if ($inInstrStuView && $quickview != 'on') {
			echo 'class="buttonactive"';
		} else {
			echo 'class="buttoninactive"';
		}
		echo '>', _('Student'), '</a>';
		echo "<a href=\"course.php?cid=$cid&quickview=on&teachview=1\" ";
		if (!$inInstrStuView && $quickview == 'on') {
			echo 'class="buttonactive buttoncurveright"';
		} else {
			echo 'class="buttoninactive buttoncurveright"';
		}
		echo '>', _('Quick Rearrange'), '</a>';
		echo '</div>';
		//echo '<br class="clear"/>';


	}

	if (isset($teacherid) && $quickview=='on') {
		echo '<div class="clear"></div>';

		echo '<div class="cpmid">';

		echo '<span class="showinmobile"><b>'._('Quick Rearrange.'), "</b> <a href=\"course.php?cid=$cid&quickview=off\">", _('Back to regular view'), "</a>.</span> ";

		if (isset($CFG['CPS']['miniicons'])) {
			echo _('Use icons to drag-and-drop order.'),' ',_('Click the icon next to a block to expand or collapse it. Click an item title to edit it in place.'), '  <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/>';

		} else {
			echo _('Use colored boxes to drag-and-drop order.'),' ',_('Click the B next to a block to expand or collapse it. Click an item title to edit it in place.'), '  <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/>';
		}
		 echo '<span id="submitnotice" class=noticetext></span>';
		 echo '<div class="clear"></div>';
		 echo '</div>';

	}

}




?>
