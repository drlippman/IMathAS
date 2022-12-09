<?php
//Displays forum threads
//(c) 2006 David Lippman

require("../init.php");
if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	require("../header.php");
	echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	require("../footer.php");
	exit;
}
if (isset($teacherid)) {
	$isteacher = true;
} else {
	$isteacher = false;
}

$threadsperpage = $listperpage;

$cid = Sanitize::courseId($_GET['cid']);
$forumid = Sanitize::onlyInt($_GET['forum']);
if (!isset($_GET['page']) || $_GET['page']=='') {
	$page = 1;
} else {
	$page = Sanitize::onlyInt($_GET['page']);
}

$query = "SELECT f.name,f.postby,f.replyby,f.settings,f.groupsetid,igs.name AS igsname,f.sortby,
    f.taglist,f.enddate,f.avail,f.description,f.postinstr,f.replyinstr,f.allowlate,f.autoscore,f.courseid 
    FROM imas_forums AS f LEFT JOIN imas_stugroupset AS igs ON igs.id=f.groupsetid WHERE f.id=:id";
$stm = $DBH->prepare($query);
$stm->execute(array(':id'=>$forumid));
list($forumname, $postby, $replyby, $forumsettings, $groupsetid, $groupsetname, $sortby, $taglist, $enddate, $avail, $description, $postinstr,$replyinstr, $allowlate, $autoscore, $forumcourseid) = $stm->fetch(PDO::FETCH_NUM);

if ($forumcourseid != $cid) {
	echo "Invalid forum ID";
	exit;
}

if (($isteacher || isset($tutorid)) && isset($_POST['score'])) {
	if (isset($tutorid)) {
		$stm = $DBH->prepare("SELECT tutoredit FROM imas_forums WHERE id=:id");
		$stm->execute(array(':id'=>$forumid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if ($row[0] != 1) {
			//no rights to edit score
			exit;
		}
	}
	$existingscores = array();
	$stm = $DBH->prepare("SELECT refid,id FROM imas_grades WHERE gradetype='forum' AND gradetypeid=:gradetypeid");
	$stm->execute(array(':gradetypeid'=>$forumid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$existingscores[$row[0]] = $row[1];
	}
	$postuserids = array();
	$refids = implode(',', array_map('intval', array_keys($_POST['score'])));
	$stm = $DBH->query("SELECT id,userid FROM imas_forum_posts WHERE id IN ($refids)");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$postuserids[$row[0]] = $row[1];
	}
	foreach($_POST['score'] as $k=>$v) {
		if (isset($_POST['feedback'.$k]) && $_POST['feedback'.$k]!='<p></p>') {
			$feedback = Sanitize::incomingHtml($_POST['feedback'.$k]);
		} else {
			$feedback = '';
		}
		if (is_numeric($v)) {
			if (isset($existingscores[$k])) {
				$stm = $DBH->prepare("UPDATE imas_grades SET score=:score,feedback=:feedback WHERE id=:id");
				$stm->execute(array(':score'=>$v, ':feedback'=>$feedback, ':id'=>$existingscores[$k]));
			} else {
				$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,refid,score,feedback) VALUES ";
				$query .= "(:gradetype, :gradetypeid, :userid, :refid, :score, :feedback)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':gradetype'=>'forum', ':gradetypeid'=>$forumid, ':userid'=>$postuserids[$k], ':refid'=>$k, ':score'=>$v, ':feedback'=>$feedback));
			}
		} else {
			if (isset($existingscores[$k])) {
				$stm = $DBH->prepare("DELETE FROM imas_grades WHERE id=:id");
				$stm->execute(array(':id'=>$existingscores[$k]));
			}
		}
	}
	if (isset($_POST['actionrequest'])) {
		list($action,$actionid) = explode(':',$_POST['actionrequest']);
		if ($action=='reply') {
			header('Location: ' . $GLOBALS['basesiteurl'] . '/forums/posts.php?'
				. Sanitize::generateQueryStringFromMap(array(
					'page' => Sanitize::onlyInt($page),
					'cid' => Sanitize::courseId($cid),
					'forum' => Sanitize::onlyInt($forumid),
					'thread' => Sanitize::encodeUrlParam($_GET['thread']),
					'modify' => 'reply',
					'replyto' => Sanitize::onlyInt($actionid),
				    'r' => Sanitize::randomQueryStringParam(),
				)));
		} else if ($action=='modify') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/posts.php?"
				. Sanitize::generateQueryStringFromMap(array(
					'page' => Sanitize::onlyInt($page),
					'cid' => Sanitize::courseId($cid),
					'forum' => Sanitize::onlyInt($forumid),
					'thread' => Sanitize::encodeUrlParam($_GET['thread']),
					'modify' => Sanitize::onlyInt($actionid),
				    'r' => Sanitize::randomQueryStringParam(),
				)));
		}
	} else if (isset($_POST['save']) && $_POST['save']=='Save Grades and View Previous') {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/posts.php?"
			. Sanitize::generateQueryStringFromMap(array(
				'page' => Sanitize::onlyInt($page),
				'cid' => Sanitize::courseId($cid),
				'forum' => Sanitize::onlyInt($forumid),
				'thread' => Sanitize::encodeUrlParam($_POST['prevth']),
			    'r' => Sanitize::randomQueryStringParam(),
			)));
	} else if (isset($_POST['save']) && $_POST['save']=='Save Grades and View Next') {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/posts.php?"
			. Sanitize::generateQueryStringFromMap(array(
				'page' => Sanitize::onlyInt($page),
				'cid' => Sanitize::courseId($cid),
				'forum' => Sanitize::onlyInt($forumid),
				'thread' => Sanitize::encodeUrlParam($_POST['nextth']),
			    'r' => Sanitize::randomQueryStringParam(),
			)));
	} else {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/thread.php?"
			. Sanitize::generateQueryStringFromMap(array(
				'page' => Sanitize::onlyInt($page),
				'cid' => Sanitize::courseId($cid),
				'forum' => Sanitize::onlyInt($forumid),
			    'r' => Sanitize::randomQueryStringParam(),
			)));
	}
	exit;
}

$duedates = '';
if (($postby>0 && $postby<2000000000) || ($replyby>0 && $replyby<2000000000)) {
	$exception = null; $latepasses = 0;
	require_once("../includes/exceptionfuncs.php");
	if (isset($studentid) && !isset($_SESSION['stuview'])) {
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,waivereqscore,itemtype FROM imas_exceptions WHERE assessmentid=:assessmentid AND userid=:userid AND (itemtype='F' OR itemtype='P' OR itemtype='R')");
		$stm->execute(array(':assessmentid'=>$forumid, ':userid'=>$userid));
		if ($stm->rowCount()>0) {
			$exception = $stm->fetch(PDO::FETCH_NUM);
		}
		$latepasses = $studentinfo['latepasses'];
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
	} else {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, false);
	}

	$infoline = array('replyby'=>$replyby, 'postby'=>$postby, 'enddate'=>$enddate, 'allowlate'=>$allowlate);
	list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepassP, $canuselatepassR, $postby, $replyby, $enddate) = $exceptionfuncs->getCanUseLatePassForums($exception, $infoline);
	if ($postby>0 && $postby<2000000000) {
		if ($postby>$now) {
			$duedates .= sprintf(_('New Threads due %s. '), tzdate("D n/j/y, g:i a",$postby));
		} else {
			$duedates .= sprintf(_('New Threads were due %s. '), tzdate("D n/j/y, g:i a",$postby));
		}
	}
	if ($replyby>0 && $replyby<2000000000) {
		//if ($duedates != '') {$duedates .= '<br/>';}
		if ($replyby>$now) {
			$duedates .= sprintf(_('Replies due %s. '), tzdate("D n/j/y, g:i a",$replyby));
		} else {
			$duedates .= sprintf(_('Replies were due %s. '), tzdate("D n/j/y, g:i a",$replyby));
		}
	}
	//if ($duedates != '' && ($canuselatepassP || $canuselatepassR || $canundolatepass)) {$duedates .= '<br/>';}
	if ($canuselatepassP || $canuselatepassR) {
		$duedates .= " <a href=\"$imasroot/course/redeemlatepassforum.php?cid=$cid&fid=$forumid&from=forum\">". _('Use LatePass'). "</a>";
		if ($canundolatepass) {
			$duedates .= ' |';
		}
	}
	if ($canundolatepass) {
		$duedates .= " <a href=\"$imasroot/course/redeemlatepassforum.php?cid=$cid&fid=$forumid&undo=true&from=forum\">". _('Un-use LatePass'). "</a>";
	}
}

if (isset($studentid) && ($avail==0 || ($avail==1 && time()>$enddate))) {
	require("../header.php");
		echo '<p>This forum is closed.  <a href="../course/course.php?cid='.$cid.'">Return to the course page</a></p>';
	require("../footer.php");
	exit;
}

$allowmod = (($forumsettings&2)==2);
$allowdel = ((($forumsettings&4)==4) || $isteacher);
$postbeforeview = (($forumsettings&16)==16);
$canviewall = (isset($teacherid) || isset($tutorid));
$dofilter = false;
$now = time();

$grpqs = '';
if ($groupsetid>0) {
    $isSectionGroups = ($groupsetname == '##autobysection##');
	if (isset($_GET['ffilter'])) {
		$_SESSION['ffilter'.$forumid] = $_GET['ffilter'];
	}
	if (!$isteacher) {
		$query = 'SELECT i_sg.id,i_sg.name FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
		$query .= "WHERE i_sgm.userid=:userid AND i_sg.groupsetid=:groupsetid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':userid'=>$userid, ':groupsetid'=>$groupsetid));
		if ($stm->rowCount()>0) {
			list($groupid,$groupname) = $stm->fetch(PDO::FETCH_NUM);
		} else {
			$groupid=0;
		}
		$dofilter = true;
	} else {
		if (isset($_SESSION['ffilter'.$forumid]) && $_SESSION['ffilter'.$forumid]>-1) {
			$groupid = $_SESSION['ffilter'.$forumid];
			$dofilter = true;
			$grpqs = "&grp=" . intval($groupid);
		} else {
			$groupid = 0;
		}
	}
	if ($dofilter) {
		$limthreads = array();
		if ($isteacher || $groupid==0) {
			$stm = $DBH->prepare("SELECT id FROM imas_forum_threads WHERE stugroupid=:stugroupid AND forumid=:forumid AND lastposttime<:now");
			$stm->execute(array(':stugroupid'=>$groupid, ':forumid'=>$forumid, ':now'=>$isteacher?2000000000:$now));
		} else {
			$stm = $DBH->prepare("SELECT id FROM imas_forum_threads WHERE (stugroupid=0 OR stugroupid=:stugroupid) AND forumid=:forumid AND lastposttime<:now");
			$stm->execute(array(':stugroupid'=>$groupid, ':forumid'=>$forumid, ':now'=>$now));
		}
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		    // This will always be a row ID (an integer). No need to sanitize.
			$limthreads[] = $row[0];
		}
		if (count($limthreads)==0) {
			$limthreads = '0';
		} else {
			$limthreads = implode(',',$limthreads); //INT from DB - safe
		}
	}
} else {
	$groupid = 0;
}

if (isset($_GET['tagfilter'])) {
	$_SESSION['tagfilter'.$forumid] = stripslashes($_GET['tagfilter']);
	$tagfilter = stripslashes($_GET['tagfilter']);
} else if (isset($_SESSION['tagfilter'.$forumid]) && $_SESSION['tagfilter'.$forumid]!='') {
	$tagfilter = $_SESSION['tagfilter'.$forumid];
} else {
	$tagfilter = '';
}
if ($tagfilter != '') {
	$query = "SELECT threadid FROM imas_forum_posts WHERE tag=:tagfilter";
	if ($dofilter) {
		$query .= " AND threadid IN ($limthreads)";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':tagfilter'=>$tagfilter));
	$limthreads = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$limthreads[] = $row[0];
	}
	if (count($limthreads)==0) {
		$limthreads = '0';
	} else {
		$limthreads = implode(',',$limthreads);  //INT from DB - safe
	}
	$dofilter = true;
}

$caller = 'thread';
if (isset($_GET['modify']) || isset($_GET['remove']) || isset($_GET['move'])) {
	require("posthandler.php");
}

if (isset($_GET['search']) && trim($_GET['search'])!='') {
	require("../header.php");
    echo "<div class=breadcrumb>";
    if (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0) {
		echo "$breadcrumbbase  <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	}
	echo "<a href=\"thread.php?page=".Sanitize::onlyInt($page)."&cid=".Sanitize::courseId($cid)."&forum=".Sanitize::onlyInt($forumid)."\">Forum Topics</a> &gt; Search Results</div>\n";

	echo "<h1>Forum Search Results</h1>";

	if (!isset($_GET['allforums']) && $postbeforeview && !$canviewall) {
		$stm = $DBH->prepare("SELECT id FROM imas_forum_posts WHERE forumid=:forumid AND parent=0 AND userid=:userid LIMIT 1");
		$stm->execute(array(':forumid'=>$forumid, ':userid'=>$userid));
		$oktoshow = ($stm->rowCount()>0);
		if (!$oktoshow) {
			echo '<p>'._('This search is blocked. In this forum, you must post your own thread before you can read those posted by others.').'</p>';
			require("../footer.php");
			exit;
		}
	}

	$safesearch = $_GET['search'];
	$safesearch = trim(str_replace(' and ', ' ',$safesearch));
	$searchterms = explode(" ",$safesearch);
	if (isset($_GET['allforums'])) {
		$query = "SELECT imas_forums.id,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.isanon FROM imas_forum_posts,imas_forums,imas_users ";
		$query .= "WHERE imas_forum_posts.forumid=imas_forums.id ";
		$array = array();
		if (!$canviewall) {
			$query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now AND imas_forums.enddate>$now)) AND (imas_forums.settings&16)=0 ";
		}
		$query .= "AND imas_users.id=imas_forum_posts.userid AND imas_forums.courseid=? ";
		$array[] = $cid;
	} else {
		$query = "SELECT imas_forum_posts.forumid,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,1,imas_forum_posts.isanon ";
		$query .= "FROM imas_forum_posts,imas_users WHERE imas_forum_posts.forumid=? AND imas_users.id=imas_forum_posts.userid ";
		$array = array($forumid);
	}
	$searchlikesarr = array();
	foreach ($searchterms as $t) {
		$searchlikesarr[] = '(imas_forum_posts.message LIKE ? OR imas_forum_posts.subject LIKE ? OR imas_users.LastName LIKE ?)';
		array_push($array, "%$t%", "%$t%", "%$t%");
	}
	$searchlikes = implode(' AND ', $searchlikesarr);
	$query .= "AND ($searchlikes) ";
	if ($dofilter) {
		$query .= " AND imas_forum_posts.threadid IN ($limthreads)";
	}

	$query .= " ORDER BY imas_forum_posts.postdate DESC";
	$stm = $DBH->prepare($query);
	$stm->execute($array);

	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo "<div class=block>";
		echo "<b>".Sanitize::encodeStringForDisplay($row[2])."</b>";
		if (isset($_GET['allforums'])) {
			echo ' (in '.Sanitize::encodeStringForDisplay($row[7]).')';
		}
		if ($row[8]==1) {
			$name = "Anonymous";
		} else {
		    $name = sprintf("%s %s", Sanitize::encodeStringForDisplay($row[4]),
                Sanitize::encodeStringForDisplay($row[5]));
		}
		printf("<br/>Posted by: <span class='pii-full-name'>%s</span>, ", Sanitize::encodeStringForDisplay($name));
		echo tzdate("F j, Y, g:i a",$row[6]);

		echo "</div><div class=blockitems>";
		echo Sanitize::outgoingHtml(filter($row[3]));
		echo "<p><a href=\"posts.php?cid=".Sanitize::courseId($cid)."&forum=".Sanitize::encodeUrlParam($row[0])."&thread=".Sanitize::encodeUrlParam($row[1])."\">Show full thread</a></p>";
		echo "</div>\n";
	}
	require("../footer.php");
	exit;
}

if (isset($_GET['markallread'])) {
	$query = "SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid=:forumid";
	if ($dofilter) {
		$query .= " AND threadid IN ($limthreads)";
	}
	$stm= $DBH->prepare($query);
	$stm->execute(array(':forumid'=>$forumid));
	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$now = time();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$stm2 = $DBH->prepare("SELECT id FROM imas_forum_views WHERE userid=:userid AND threadid=:threadid");
		$stm2->execute(array(':userid'=>$userid, ':threadid'=>$row[0]));
		if ($stm2->rowCount()>0) {
			$r2id = $stm2->fetchColumn(0);
			$stm2 = $DBH->prepare("UPDATE imas_forum_views SET lastview=:lastview WHERE id=:id");
			$stm2->execute(array(':lastview'=>$now, ':id'=>$r2id));
		} else{
			$stm2 = $DBH->prepare("INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES (:userid, :threadid, :lastview)");
			$stm2->execute(array(':userid'=>$userid, ':threadid'=>$row[0], ':lastview'=>$now));
		}
	}
}


$pagetitle = "Threads";
$placeinhead = "<style type=\"text/css\">\n@import url(\"$staticroot/forums/forums.css\"); td.pointer:hover {text-decoration: underline;}\n</style>\n";
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/thread.js?v=050220\"></script>";
$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '" . $GLOBALS['basesiteurl'] . "/forums/savetagged.php?cid=$cid';";
$placeinhead .= '$(function() {$("img[src*=\'flag\']").attr("title","Flag Message");});';
$placeinhead .= "var tagfilterurl = '" . $GLOBALS['basesiteurl'] . "/forums/thread.php?page=$page&cid=$cid&forum=$forumid';</script>";
require("../header.php");


if (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0) {
    echo "<div class=breadcrumb>$breadcrumbbase ";
    echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    echo _('Forum Topics').'</div>';
}
echo '<div id="headerthread" class="pagetitle"><h1>Forum: '.Sanitize::encodeStringForDisplay($forumname).'</h1></div>';

if ($duedates!='') {
	//$duedates contains HTML from above
	echo '<p id="forumduedates">'.$duedates.'</p>';
}
if ($description != '' && $description != '<p></p>') {
	echo '<div id="description" style="display:none;margin-bottom:10px;" class="intro">';
	echo Sanitize::outgoingHtml($description);
	echo '</div>';
}
if ($postinstr != '' || $replyinstr != '') {
	echo '<div id="postreplyinstr" style="display:none;margin-bottom:10px;" class="intro">';
	if ($postinstr != '') {
		echo '<h3>'._('Posting Instructions').'</h3>';
		// $postinstr contains HTML.
		echo Sanitize::outgoingHtml($postinstr);
	}
	if ($replyinstr != '') {
		echo '<h3>'._('Reply Instructions').'</h3>';
		// $postinstr contains HTML.
		echo Sanitize::outgoingHtml($replyinstr);
	}
	echo '</div>';
}
if ($description != '' && $description != '<p></p>') {
	echo '<a href="#" onclick="$(\'#description\').show();$(this).remove();return false;">';
	echo _('View Forum Description');
	echo '</a> ';
}
if ($postinstr != '' || $replyinstr != '') {
	echo '<a href="#" onclick="$(\'#postreplyinstr\').show();$(this).remove();return false;">';
	if ($postinstr != '' && $replyinstr != '') {
		echo _('View Post and Reply Instructions');
	} else if ($postinstr != '') {
		echo _('View Post Instructions');
	} else if ($replyinstr != '') {
		echo _('View Reply Instructions');
	}
	echo '</a>';
}

$query = "SELECT threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate FROM imas_forum_posts ";
$query .= "WHERE forumid=:forumid ";
if ($dofilter) {
	$query .= "AND threadid IN ($limthreads) ";
}
$query .= "GROUP BY threadid";
$stm = $DBH->prepare($query);
$stm->execute(array(':forumid'=>$forumid));
$postcount = array();
$maxdate = array();
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$postcount[$row[0]] = $row[1] -1;
	$maxdate[$row[0]] = $row[2];
}
$query= "SELECT threadid,lastview,tagged FROM imas_forum_views WHERE userid=:userid";
if ($dofilter) {
	$query .= " AND threadid IN ($limthreads)";
}
$stm = $DBH->prepare($query);
$stm->execute(array(':userid'=>$userid));
// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
$lastview = array();
$flags = array();
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$lastview[$row[0]] = $row[1];
	if ($row[2]==1) {
		$flags[$row[0]] = 1;
	}
}
$flaggedlist = implode(',', array_map('intval', array_keys($flags)));
//make new list
$newpost = array();
foreach (array_keys($maxdate) as $tid) {
	if (!isset($lastview[$tid]) || $lastview[$tid]<$maxdate[$tid]) {
		$newpost[] = $tid;
	}
}
$newpostlist = implode(',', array_map('intval', $newpost));
if ($page==-1 && count($newpost)==0) {
	$page = 1;
} else if ($page==-2 && count($flags)==0) {
	$page = 1;
}
$prevnext = '';
if ($page>0) {
	$query = "SELECT COUNT(id) FROM imas_forum_posts WHERE parent=0 AND forumid=:forumid";
	if ($dofilter) {
		$query .= " AND threadid IN ($limthreads)";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':forumid'=>$forumid));
	$numpages = ceil($stm->fetchColumn(0)/$threadsperpage);

	if ($numpages > 1) {
		$prevnext .= "Page: ";
		if ($page < $numpages/2) {
			$min = max(2,$page-4);
			$max = min($numpages-1,$page+8+$min-$page);
		} else {
			$max = min($numpages-1,$page+4);
			$min = max(2,$page-8+$max-$page);
		}
		if ($page==1) {
			$prevnext .= "<b>1</b> ";
		} else {
			$prevnext .= "<a href=\"thread.php?page=1&cid=".Sanitize::courseId($cid)."&forum=".Sanitize::onlyInt($forumid)."\">1</a> ";
		}
		if ($min!=2) { $prevnext .= " ... ";}
		for ($i = $min; $i<=$max; $i++) {
			if ($page == $i) {
				$prevnext .= "<b>$i</b> ";
			} else {
				$prevnext .= "<a href=\"thread.php?page=$i&cid=$cid&forum=$forumid\">$i</a> ";
			}
		}
		if ($max!=$numpages-1) { $prevnext .= " ... ";}
		if ($page == $numpages) {
			$prevnext .= "<b>$numpages</b> ";
		} else {
			$prevnext .= "<a href=\"thread.php?page=$numpages&cid=$cid&forum=$forumid\">$numpages</a> ";
		}
		$prevnext .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

		if ($page>1) {
			$prevnext .= "<a href=\"thread.php?page=".($page-1)."&cid=$cid&forum=$forumid\">Previous</a> ";
		} else {
			$prevnext .= "Previous ";
		}
		if ($page < $numpages) {
			$prevnext .= "| <a href=\"thread.php?page=".($page+1)."&cid=$cid&forum=$forumid\">Next</a> ";
		} else {
			$prevnext .= "| Next ";
		}

		echo "<div>$prevnext</div>";
	}
}
echo "<form method=get action=\"thread.php\">";
echo "<input type=hidden name=page value=\"".Sanitize::onlyInt($page)."\"/>";
echo "<input type=hidden name=cid value=\"$cid\"/>";
echo "<input type=hidden name=forum value=\"$forumid\"/>";

?>
<label for="search">Search</label>: <input type=text name="search" id="search" />
<?php if (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0) { ?>
<input type=checkbox name="allforums" id="allforums" /> <label for="allforums">All forums in course?</label>
<?php } ?>
<input type="submit" value="Search"/>
</form>
<?php
if ($isteacher && $groupsetid>0) {
	if (isset( $_SESSION['ffilter'.$forumid])) {
		$curfilter = $_SESSION['ffilter'.$forumid];
	} else {
		$curfilter = -1;
	}

    $groupnames = array();
	$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid ORDER BY id");
	$stm->execute(array(':groupsetid'=>$groupsetid));
	$grpnums = 1;
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[1] == 'Unnamed group') {
			$row[1] .= " $grpnums";
			$grpnums++;
		}
		$groupnames[$row[0]] = $row[1];
	}
    natsort($groupnames);
    $groupnames = [0=>$isSectionGroups ?  _("Non-section-specific") : _("Non-group-specific")] + $groupnames;
	/*echo "<script type=\"text/javascript\">";
	echo 'function chgfilter() {';
	echo '  var ffilter = document.getElementById("ffilter").value;';
	echo "  window.location = \"thread.php?page=$pages&cid=$cid&forum=$forumid&ffilter=\"+ffilter;";
	echo '}';
	echo '</script>';*/
    echo '<p><label for="ffilter">';
    if ($isSectionGroups) {
        echo _('Showing posts for section');
    } else {
        echo _('Showing posts for group');
    }
    echo '</label>: <select id="ffilter" onChange="chgfilter()"><option value="-1" ';
	if ($curfilter==-1) { echo 'selected="1"';}
    echo '>';
    echo $isSectionGroups ? _('All Sections') : _('All Groups');
    echo '</option>';
	foreach ($groupnames as $gid=>$gname) {
		echo "<option value=\"$gid\" ";
		if ($curfilter==$gid) { echo 'selected="1"';}
		echo ">".Sanitize::encodeStringForDisplay($gname)."</option>";
	}
	echo '</select></p>';
} else if ($groupsetid>0 && $groupid>0) {
    echo '<p><b>';
    if ($isSectionGroups) {
        echo _('Showing posts for section');
    } else {
        echo _('Showing posts for group');
    }
    echo ': '.Sanitize::encodeStringForDisplay($groupname).'</b> ';
    if (!$isSectionGroups) {
        echo '<a class="small" href="#" onclick="basicahah(\'../course/showstugroup.php?cid='.$cid.'&gid='.Sanitize::onlyInt($groupid).'\',\'grouplistout\');$(this).hide();return false;">['._('Show group members').']</a> <span id="grouplistout"></span>';
    }
	echo '</p>';
}
echo '<p>';
$toshow = array();
if (($myrights > 5 && time()<$postby) || $isteacher) {
	$toshow[] =  "<button type=\"button\" onclick=\"window.location.href='thread.php?page=". Sanitize::onlyInt($page)."&cid=$cid&forum=$forumid&modify=new'\">"._('Add New Thread')."</button>";
}
//if ($isteacher || isset($tutorid)) {
$toshow[] =  "<a href=\"postsbyname.php?page=". Sanitize::onlyInt($page)."&cid=$cid&forum=$forumid\">List Posts by Name</a>";
//}

if ($page<0) {
	$toshow[] =  "<a href=\"thread.php?cid=$cid&forum=$forumid&page=1\">Show All</a>";
} else {
	if (count($newpost)>0) {
		$toshow[] =  "<a href=\"thread.php?cid=$cid&forum=$forumid&page=-1\">Limit to New</a>";
	}
	$toshow[] =  "<a href=\"thread.php?cid=$cid&forum=$forumid&page=-2\">Limit to Flagged</a>";

	if ($taglist!='') {
		$p = strpos($taglist,':');

		$tagselect = 'Filter by '.Sanitize::encodeStringForDisplay(substr($taglist,0,$p)).': ';
		$tagselect .= '<select id="tagfilter" onChange="chgtagfilter()"><option value="" ';
		if ($tagfilter=='') {
			$tagselect .= 'selected="selected"';
		}
		$tagselect .= '>All</option>';
		$tags = explode(',',substr($taglist,$p+1));
		foreach ($tags as $tag) {
			$tagEncoded =  Sanitize::encodeStringForDisplay($tag);
			$tagselect .= '<option value="'.$tagEncoded.'" ';

			$tagQuotesEscaped =  str_replace('"','&quot;',$tag);
			if ($tagQuotesEscaped==$tagfilter) {$tagselect .= 'selected="selected"';}
			$tagselect .= '>'.$tagEncoded.'</option>';
		}
		$tagselect .= '</select>';
		$toshow[] = $tagselect;
	}

}
if (count($newpost)>0) {
	$toshow[] =  "<button type=\"button\" onclick=\"window.location.href='thread.php?page=". Sanitize::onlyInt($page)."&cid=$cid&forum=$forumid&markallread=true'\">"._('Mark all Read')."</button>";
}

echo implode(' | ',$toshow);

echo "</p>";

?>
<table class="forum gb">
	<thead>
		<tr><th>Topic</th><th>Started By</th>
			<?php
			if ($isteacher && $groupsetid>0 && !$dofilter) {
                if ($isSectionGroups) {
                    echo '<th>Section</th>';
                } else {
                    echo '<th>Group</th>';
                }
			}
			?>
			<th>Replies</th><th>Views (Unique)</th><th>Last Post</th></tr>
		</thead>
		<tbody>
			<?php
			$query = "SELECT imas_forum_posts.id,count(imas_forum_views.userid) FROM imas_forum_views,imas_forum_posts ";
			$query .= "WHERE imas_forum_views.threadid=imas_forum_posts.id AND imas_forum_posts.parent=0 AND ";
			$query .= "imas_forum_posts.forumid=:forumid ";
			if ($dofilter) {
				$query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
			}
			if ($page==-1) {
				$query .= "AND imas_forum_posts.threadid IN ($newpostlist) ";
			} else if ($page==-2) {
				$query .= "AND imas_forum_posts.threadid IN ($flaggedlist) ";
			}
			$query .= "GROUP BY imas_forum_posts.id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':forumid'=>$forumid));
			// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$uniqviews[$row[0]] = $row[1];
			}
			$query = "SELECT imas_forum_posts.*,imas_forum_threads.views as tviews,imas_users.LastName,imas_users.FirstName,imas_forum_threads.stugroupid,imas_forum_threads.lastposttime ";
			$query .= "FROM imas_forum_posts,imas_users,imas_forum_threads WHERE ";
			$query .= "imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.parent=0 AND imas_forum_posts.forumid=:forumid ";
			$query .= "AND imas_forum_threads.lastposttime<:now ";
			if ($dofilter) {
				$query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
			}
			if ($page==-1) {
				$query .= "AND imas_forum_posts.threadid IN ($newpostlist) ";
			} else if ($page==-2) {
				$query .= "AND imas_forum_posts.threadid IN ($flaggedlist) ";
			}
			if ($sortby==0) {
				$query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_posts.id DESC ";
			} else if ($sortby==1) {
				$query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_threads.lastposttime DESC ";
			}
			$offset = intval(($page-1)*$threadsperpage);
			$threadsperpage =intval($threadsperpage);
			if ($page>0) {
				$query .= "LIMIT $offset,$threadsperpage";
			}
			// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$stm = $DBH->prepare($query);
			$stm->execute(array(':forumid'=>$forumid, ':now'=>$isteacher?2000000000:$now));
			if ($stm->rowCount()==0) {
				echo '<tr><td colspan='.(($isteacher && $groupsetid>0 && !$dofilter)?5:4).'>No posts have been made yet.  Click Add New Thread to start a new discussion</td></tr>';
			}
			while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
				if (isset($postcount[$line['id']])) {
					$posts = $postcount[$line['id']];
					$lastpost = tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
				} else {
					$posts = 0;
					$lastpost = '';
				}
				$classes = array();
				if ($line['posttype']>0) {
					$classes[] = "sticky";
				}
				if (isset($flags[$line['id']])) {
					$classes[] = "tagged";
				}
				echo "<tr id=\"tr".Sanitize::onlyInt($line['id'])."\"";
				if (count($classes)>0) {
					 echo ' class="'.implode(' ',$classes).'"';
				}
				echo "><td>";
				echo "<span class=\"right\">\n";
				if ($line['lastposttime']>$now) {
					echo "<img class=mida src=\"$staticroot/img/time.png\" alt=\"Scheduled\" title=\"Scheduled for later release\" /> ";
				}
				if ($line['tag']!='') { //category tags
					echo '<span class="forumcattag">'.Sanitize::encodeStringForDisplay($line['tag']).'</span> ';
				}

				if (isset($flags[$line['id']])) {
					echo "<img class=\"pointer\" id=\"tag". Sanitize::onlyInt($line['id'])."\" src=\"$staticroot/img/flagfilled.gif\" onClick=\"toggletagged(". Sanitize::onlyInt($line['id']) . ");return false;\" alt=\"Flagged\" />";
				} else {
					echo "<img class=\"pointer\" id=\"tag". Sanitize::onlyInt($line['id'])."\" src=\"$staticroot/img/flagempty.gif\" onClick=\"toggletagged(". Sanitize::onlyInt($line['id'])  . ");return false;\" alt=\"Not flagged\"/>";
				}
				if ($isteacher) {
					if ($line['posttype']==2) {
						echo "<img class=mida src=\"$staticroot/img/lock.png\" alt=\"Lock\" title=\"Locked (no replies)\" /> ";
					} else if ($line['posttype']==3) {
						echo "<img class=mida src=\"$staticroot/img/noview.png\" alt=\"No View\" title=\"Students can only see their own replies\" /> ";
					}
				}
				if ($isteacher || ($line['userid']==$userid && $allowmod && time()<$postby) || ($allowdel && $line['userid']==$userid && $posts==0)) {
					echo '<span class="dropdown">';
					echo '<a tabindex=0 class="dropdown-toggle" id="dropdownMenu'.Sanitize::onlyInt($line['id']).'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
					echo ' <img src="'.$staticroot.'/img/gears.png" class="mida" alt="Options"/>';
					echo '</a>';
					echo '<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu'.Sanitize::onlyInt($line['id']).'">';

					if ($isteacher) {
						echo "<li><a href=\"thread.php?page=". Sanitize::onlyInt($page)."&cid=$cid&forum=". Sanitize::onlyInt($line['forumid'])."&move=". Sanitize::onlyInt($line['id']) ."\">Move</a></li> ";
					}
					if ($isteacher || ($line['userid']==$userid && $allowmod && time()<$postby)) {
						echo "<li><a href=\"thread.php?page=". Sanitize::onlyInt($page)."&cid=$cid&forum=". Sanitize::onlyInt($line['forumid'])."&modify=" .Sanitize::onlyInt($line['id'])."\">Modify</a></li> ";
					}
					if ($isteacher || ($allowdel && $line['userid']==$userid && $posts==0)) {
						echo "<li><a href=\"thread.php?page=". Sanitize::onlyInt($page) ."&cid=$cid&forum=". Sanitize::onlyInt($line['forumid'])."&remove=".Sanitize::onlyInt($line['id'])."\">Remove</a></li>";
					}
					echo '</ul></span>';
				}
				echo "</span>\n";
				if ($line['isanon']==1) {
					$name = "Anonymous";
				} else {
					$name = Sanitize::encodeStringForDisplay($line['LastName']) .", ". Sanitize::encodeStringForDisplay($line['FirstName']);
				}
				if ($line['lastposttime']>$now) {
					echo '<i class="grey">';
				}
				echo "<a href=\"posts.php?cid=$cid&forum=$forumid&thread=" .Sanitize::onlyInt($line['id']). "&page=". Sanitize::onlyInt($page) . $grpqs .'">'. Sanitize::encodeStringForDisplay($line['subject']) ."</a></td>";
				if ($line['lastposttime']>$now) {
					echo '</i>';
				}
				printf("<td><span class='pii-full-name'>%s</span></td>\n", Sanitize::encodeStringForDisplay($name));

				if ($isteacher && $groupsetid>0 && !$dofilter) {
					echo '<td class=c>'.Sanitize::encodeStringForDisplay($groupnames[$line['stugroupid']]).'</td>';
				}

				echo "<td class=c>".Sanitize::encodeStringForDisplay($posts)."</td>";

				if ($isteacher) {
					echo '<td class="pointer c" onclick="GB_show(\''._('Thread Views').'\',\'listviews.php?cid='.$cid.'&amp;thread='.Sanitize::onlyInt($line['id']).'\',500,500);">';
				} else {
					echo '<td class="c">';
				}
				echo Sanitize::encodeStringForDisplay($line['tviews']) ." (".Sanitize::encodeStringForDisplay($uniqviews[$line['id']]).")</td><td class=c>".Sanitize::encodeStringForDisplay($lastpost);
				if ($lastpost=='' || !isset($lastview[$line['id']]) || !isset($maxdate[$line['id']]) || $maxdate[$line['id']]>$lastview[$line['id']]) {
					echo " <span class=\"noticetext\">New</span>";
				}
				echo "</td></tr>\n";
			}
			?>
		</tbody>
	</table>
	<?php
	if (($myrights > 5 && time()<$postby) || $isteacher) {
		echo "<p><button type=\"button\" onclick=\"window.location.href='thread.php?page=".Sanitize::onlyInt($page)."&cid=$cid&forum=$forumid&modify=new'\">"._('Add New Thread')."</button></p>\n";
	}
	if ($prevnext!='') {
		echo "<p>$prevnext</p>";
	}

	require("../footer.php");
	?>
