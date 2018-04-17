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

if (($isteacher || isset($tutorid)) && isset($_POST['score'])) {
	if (isset($tutorid)) {
		//DB $query = "SELECT tutoredit FROM imas_forums WHERE id='$forumid'";
		//DB $res = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $row = mysql_fetch_row($res);
		$stm = $DBH->prepare("SELECT tutoredit FROM imas_forums WHERE id=:id");
		$stm->execute(array(':id'=>$forumid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if ($row[0] != 1) {
			//no rights to edit score
			exit;
		}
	}
	$existingscores = array();
	//DB $query = "SELECT refid,id FROM imas_grades WHERE gradetype='forum' AND gradetypeid='$forumid'";
	//DB $res = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($row = mysql_fetch_row($res)) {
	//DB $existingscores[$row[0]] = $row[1];
	$stm = $DBH->prepare("SELECT refid,id FROM imas_grades WHERE gradetype='forum' AND gradetypeid=:gradetypeid");
	$stm->execute(array(':gradetypeid'=>$forumid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$existingscores[$row[0]] = $row[1];
	}
	$postuserids = array();
	//DB $refids = "'".implode("','",array_keys($_POST['score']))."'";
	$refids = implode(',', array_map('intval', array_keys($_POST['score'])));
	//DB $query = "SELECT id,userid FROM imas_forum_posts WHERE id IN ($refids)";
	//DB $res = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($row = mysql_fetch_row($res)) {
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
				//DB $query = "UPDATE imas_grades SET score='$v',feedback='$feedback' WHERE id='{$existingscores[$k]}'";
				$stm = $DBH->prepare("UPDATE imas_grades SET score=:score,feedback=:feedback WHERE id=:id");
				$stm->execute(array(':score'=>$v, ':feedback'=>$feedback, ':id'=>$existingscores[$k]));
			} else {
				//DB $query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,refid,score,feedback) VALUES ";
				//DB $query .= "('forum','$forumid','{$postuserids[$k]}','$k','$v','$feedback')";
				$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,refid,score,feedback) VALUES ";
				$query .= "(:gradetype, :gradetypeid, :userid, :refid, :score, :feedback)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':gradetype'=>'forum', ':gradetypeid'=>$forumid, ':userid'=>$postuserids[$k], ':refid'=>$k, ':score'=>$v, ':feedback'=>$feedback));
			}
			//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		} else {
			if (isset($existingscores[$k])) {
				//DB $query = "DELETE FROM imas_grades WHERE id='{$existingscores[$k]}'";
				//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
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
//DB $query = "SELECT name,postby,replyby,settings,groupsetid,sortby,taglist,enddate,avail,postinstr,replyinstr,allowlate FROM imas_forums WHERE id='$forumid'";
//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
//DB list($forumname, $postby, $replyby, $forumsettings, $groupsetid, $sortby, $taglist, $enddate, $avail, $postinstr,$replyinstr, $allowlate) = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT name,postby,replyby,settings,groupsetid,sortby,taglist,enddate,avail,postinstr,replyinstr,allowlate FROM imas_forums WHERE id=:id");
$stm->execute(array(':id'=>$forumid));
list($forumname, $postby, $replyby, $forumsettings, $groupsetid, $sortby, $taglist, $enddate, $avail, $postinstr,$replyinstr, $allowlate) = $stm->fetch(PDO::FETCH_NUM);

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
	if (isset($_GET['ffilter'])) {
		$sessiondata['ffilter'.$forumid] = $_GET['ffilter'];
		writesessiondata();
	}
	if (!$isteacher) {
		//DB $query = 'SELECT i_sg.id,i_sg.name FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
		//DB $query .= "WHERE i_sgm.userid='$userid' AND i_sg.groupsetid='$groupsetid'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
		//DB list($groupid,$groupname) = mysql_fetch_row($result);
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
		if (isset($sessiondata['ffilter'.$forumid]) && $sessiondata['ffilter'.$forumid]>-1) {
			$groupid = $sessiondata['ffilter'.$forumid];
			$dofilter = true;
			$grpqs = "&grp=$groupid";
		} else {
			$groupid = 0;
		}
	}
	if ($dofilter) {
		$limthreads = array();
		if ($isteacher || $groupid==0) {
			//DB $query = "SELECT id FROM imas_forum_threads WHERE stugroupid='$groupid' AND forumid='$forumid'";
			$stm = $DBH->prepare("SELECT id FROM imas_forum_threads WHERE stugroupid=:stugroupid AND forumid=:forumid AND lastposttime<:now");
			$stm->execute(array(':stugroupid'=>$groupid, ':forumid'=>$forumid, ':now'=>$isteacher?2000000000:$now));
		} else {
			//DB $query = "SELECT id FROM imas_forum_threads WHERE (stugroupid=0 OR stugroupid='$groupid') AND forumid='$forumid'";
			$stm = $DBH->prepare("SELECT id FROM imas_forum_threads WHERE (stugroupid=0 OR stugroupid=:stugroupid) AND forumid=:forumid AND lastposttime<:now");
			$stm->execute(array(':stugroupid'=>$groupid, ':forumid'=>$forumid, ':now'=>$now));
		}
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
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
	$sessiondata['tagfilter'.$forumid] = stripslashes($_GET['tagfilter']);
	writesessiondata();
	$tagfilter = stripslashes($_GET['tagfilter']);
} else if (isset($sessiondata['tagfilter'.$forumid]) && $sessiondata['tagfilter'.$forumid]!='') {
	$tagfilter = $sessiondata['tagfilter'.$forumid];
} else {
	$tagfilter = '';
}
if ($tagfilter != '') {
	//DB $query = "SELECT threadid FROM imas_forum_posts WHERE tag='".addslashes($tagfilter)."'";
	$query = "SELECT threadid FROM imas_forum_posts WHERE tag=:tagfilter";
	if ($dofilter) {
		$query .= " AND threadid IN ($limthreads)";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':tagfilter'=>$tagfilter));
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$limthreads = array();
	//DB while ($row = mysql_fetch_row($result)) {
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

if (isset($_GET['search']) && trim($_GET['search'])!='') {
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=".Sanitize::courseId($cid)."\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	echo "<a href=\"thread.php?page=".Sanitize::onlyInt($page)."&cid=".Sanitize::courseId($cid)."&forum=".Sanitize::onlyInt($forumid)."\">Forum Topics</a> &gt; Search Results</div>\n";

	echo "<h2>Forum Search Results</h2>";

	if (!isset($_GET['allforums']) && $postbeforeview && !$canviewall) {
		//DB $query = "SELECT id FROM imas_forum_posts WHERE forumid='$forumid' AND parent=0 AND userid='$userid' LIMIT 1";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $oktoshow = (mysql_num_rows($result)>0);
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
	//DB $searchlikes = "(imas_forum_posts.message LIKE '%".implode("%' AND imas_forum_posts.message LIKE '%",$searchterms)."%')";
	//DB $searchlikes2 = "(imas_forum_posts.subject LIKE '%".implode("%' AND imas_forum_posts.subject LIKE '%",$searchterms)."%')";
	//DB $searchlikes3 = "(imas_users.LastName LIKE '%".implode("%' AND imas_users.LastName LIKE '%",$searchterms)."%')";
	if (isset($_GET['allforums'])) {
		//DB $query = "SELECT imas_forums.id,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.isanon FROM imas_forum_posts,imas_forums,imas_users ";
		//DB $query .= "WHERE imas_forum_posts.forumid=imas_forums.id ";
		$query = "SELECT imas_forums.id,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.isanon FROM imas_forum_posts,imas_forums,imas_users ";
		$query .= "WHERE imas_forum_posts.forumid=imas_forums.id ";
		$array = array();
		if (!$canviewall) {
			//DB $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now AND imas_forums.enddate>$now)) AND (imas_forums.settings&16)=0 ";
			$query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now AND imas_forums.enddate>$now)) AND (imas_forums.settings&16)=0 ";
		}
		$query .= "AND imas_users.id=imas_forum_posts.userid AND imas_forums.courseid=? ";
		$array[] = $cid;
	} else {
		//DB $query = "SELECT imas_forum_posts.forumid,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate ";
		//DB $query .= "FROM imas_forum_posts,imas_users WHERE imas_forum_posts.forumid='$forumid' AND imas_users.id=imas_forum_posts.userid AND ($searchlikes OR $searchlikes2 OR $searchlikes3)";
		$query = "SELECT imas_forum_posts.forumid,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate ";
		$query .= "FROM imas_forum_posts,imas_users WHERE imas_forum_posts.forumid=? AND imas_users.id=imas_forum_posts.userid ";
		$array = array($forumid);
	}
	//DB $query .= "AND imas_users.id=imas_forum_posts.userid AND imas_forums.courseid='$cid' AND ($searchlikes OR $searchlikes2 OR $searchlikes3)";
	//DB $query .= "AND imas_users.id=imas_forum_posts.userid AND imas_forums.courseid=:courseid AND (:searchlikes OR :searchlikes2 OR :searchlikes3)";
	//DB array_merge($array,[':courseid'=>$cid, ':searchlikes'=>$searchlikes, ':searchlikes2'=>$searchlikes2, ':searchlikes3'=>$searchlikes3]);
	$searchlikesarr = array();
	foreach ($searchterms as $t) {
		$searchlikesarr[] = '(imas_forum_posts.message LIKE ? OR imas_forum_posts.subject LIKE ? OR imas_users.LastName LIKE ?)';
		array_push($array, "%$t%", "%$t%", "%$t%");
	}
	$searchlikes = implode(' AND ', $searchlikesarr);
	$query .= "AND ($searchlikes) ";
	if ($dofilter) {
		//DB $query .= " AND imas_forum_posts.threadid IN ($limthreads)";
		$query .= " AND imas_forum_posts.threadid IN ($limthreads)";
	}

	$query .= " ORDER BY imas_forum_posts.postdate DESC";
	$stm = $DBH->prepare($query);
	$stm->execute($array);

	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
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
		printf("<br/>Posted by: %s, ", Sanitize::encodeStringForDisplay($name));
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
	//DB $query = "SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid='$forumid'";
	$query = "SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid=:forumid";
	if ($dofilter) {
		//DB $query .= " AND threadid IN ($limthreads)";
		$query .= " AND threadid IN ($limthreads)";
	}
	$stm= $DBH->prepare($query);
	$stm->execute(array(':forumid'=>$forumid));
	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$now = time();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		//DB $query = "SELECT id FROM imas_forum_views WHERE userid='$userid' AND threadid='{$row[0]}'";
		//DB $r2 = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$stm2 = $DBH->prepare("SELECT id FROM imas_forum_views WHERE userid=:userid AND threadid=:threadid");
		$stm2->execute(array(':userid'=>$userid, ':threadid'=>$row[0]));
		//DB if (mysql_num_rows($r2)>0) {
		if ($stm2->rowCount()>0) {
			//DB $r2id = mysql_result($r2,0,0);
			//DB $query = "UPDATE imas_forum_views SET lastview=$now WHERE id='$r2id'";
			//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			$r2id = $stm2->fetchColumn(0);
			$stm2 = $DBH->prepare("UPDATE imas_forum_views SET lastview=:lastview WHERE id=:id");
			$stm2->execute(array(':lastview'=>$now, ':id'=>$r2id));
		} else{
			//DB $query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','{$row[0]}',$now)";
			//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			$stm2 = $DBH->prepare("INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES (:userid, :threadid, :lastview)");
			$stm2->execute(array(':userid'=>$userid, ':threadid'=>$row[0], ':lastview'=>$now));
		}
	}
}

$duedates = '';
if (($postby>0 && $postby<2000000000) || ($replyby>0 && $replyby<2000000000)) {
	$exception = null; $latepasses = 0;
	require_once("../includes/exceptionfuncs.php");
	if (isset($studentid) && !isset($sessiondata['stuview'])) {
		//DB $query = "SELECT startdate,enddate,islatepass,waivereqscore,itemtype FROM imas_exceptions WHERE assessmentid='$forumid' AND userid='$userid' AND (itemtype='F' OR itemtype='P' OR itemtype='R')";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
			//DB $exception = mysql_fetch_row($result);
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

$caller = 'thread';
if (isset($_GET['modify']) || isset($_GET['remove']) || isset($_GET['move'])) {
	require("posthandler.php");
}

$pagetitle = "Threads";
$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\"); td.pointer:hover {text-decoration: underline;}\n</style>\n";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/thread.js\"></script>";
$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '" . $GLOBALS['basesiteurl'] . "/forums/savetagged.php?cid=$cid';";
$placeinhead .= '$(function() {$("img[src*=\'flag\']").attr("title","Flag Message");});';
$placeinhead .= "var tagfilterurl = '" . $GLOBALS['basesiteurl'] . "/forums/thread.php?page=$pages&cid=$cid&forum=$forumid';</script>";
require("../header.php");


echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Forum Topics</div>\n";
echo '<div id="headerthread" class="pagetitle"><h2>Forum: '.Sanitize::encodeStringForDisplay($forumname).'</h2></div>';

if ($duedates!='') {
	//$duedates contains HTML from above
	echo '<p id="forumduedates">'.$duedates.'</p>';
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
	echo '<div id="postreplyinstr" style="display:none;" class="intro">';
	if ($postinstr != '') {
		echo '<h4>'._('Posting Instructions').'</h4>';
		// $postinstr contains HTML.
		echo Sanitize::outgoingHtml($postinstr);
	}
	if ($replyinstr != '') {
		echo '<h4>'._('Reply Instructions').'</h4>';
		// $postinstr contains HTML.
		echo Sanitize::outgoingHtml($replyinstr);
	}
	echo '</div><br/>';
}

//DB $query = "SELECT threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate FROM imas_forum_posts ";
//DB $query .= "WHERE forumid='$forumid' ";
$query = "SELECT threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate FROM imas_forum_posts ";
$query .= "WHERE forumid=:forumid ";
if ($dofilter) {
	$query .= "AND threadid IN ($limthreads) ";
}
$query .= "GROUP BY threadid";
$stm = $DBH->prepare($query);
$stm->execute(array(':forumid'=>$forumid));
//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
$postcount = array();
$maxdate = array();

//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$postcount[$row[0]] = $row[1] -1;
	$maxdate[$row[0]] = $row[2];
}

//DB $query = "SELECT threadid,lastview,tagged FROM imas_forum_views WHERE userid='$userid'";
$query= "SELECT threadid,lastview,tagged FROM imas_forum_views WHERE userid=:userid";
if ($dofilter) {
	$query .= " AND threadid IN ($limthreads)";
}
$stm = $DBH->prepare($query);
$stm->execute(array(':userid'=>$userid));
// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
$lastview = array();
$flags = array();
//DB while ($row = mysql_fetch_row($result)) {
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
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB $numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
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
<label for="search">Search</label>: <input type=text name="search" id="search" /> <input type=checkbox name="allforums" id="allforums" /> <label for="allforums">All forums in course?</label> <input type="submit" value="Search"/>
</form>
<?php
if ($isteacher && $groupsetid>0) {
	if (isset( $sessiondata['ffilter'.$forumid])) {
		$curfilter = $sessiondata['ffilter'.$forumid];
	} else {
		$curfilter = -1;
	}

	$groupnames = array();
	$groupnames[0] = "Non-group-specific";
	//DB $query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY id";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid ORDER BY id");
	$stm->execute(array(':groupsetid'=>$groupsetid));
	$grpnums = 1;
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[1] == 'Unnamed group') {
			$row[1] .= " $grpnums";
			$grpnums++;
		}
		$groupnames[$row[0]] = $row[1];
	}
	natsort($groupnames);

	//DB $query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY id";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid ORDER BY id");
	$stm->execute(array(':groupsetid'=>$groupsetid));
	/*echo "<script type=\"text/javascript\">";
	echo 'function chgfilter() {';
	echo '  var ffilter = document.getElementById("ffilter").value;';
	echo "  window.location = \"thread.php?page=$pages&cid=$cid&forum=$forumid&ffilter=\"+ffilter;";
	echo '}';
	echo '</script>';*/
	echo '<p>Show posts for group: <select id="ffilter" onChange="chgfilter()"><option value="-1" ';
	if ($curfilter==-1) { echo 'selected="1"';}
	echo '>All groups</option>';
	foreach ($groupnames as $gid=>$gname) {
		echo "<option value=\"$gid\" ";
		if ($curfilter==$gid) { echo 'selected="1"';}
		echo ">".Sanitize::encodeStringForDisplay($gname)."</option>";
	}
	echo '</select></p>';
} else if ($groupsetid>0 && $groupid>0) {
	echo '<p><b>'._('Showing posts for group: ').Sanitize::encodeStringForDisplay($groupname).'</b> ';
	echo '<a class="small" href="#" onclick="basicahah(\'../course/showstugroup.php?cid='.$cid.'&gid='.Sanitize::onlyInt($groupid).'\',\'grouplistout\');$(this).hide();return false;">['._('Show group members').']</a> <span id="grouplistout"></span>';
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
				echo '<th>Group</th>';
			}
			?>
			<th>Replies</th><th>Views (Unique)</th><th>Last Post</th></tr>
		</thead>
		<tbody>
			<?php


			//DB $query = "SELECT imas_forum_posts.id,count(imas_forum_views.userid) FROM imas_forum_views,imas_forum_posts ";
			//DB $query .= "WHERE imas_forum_views.threadid=imas_forum_posts.id AND imas_forum_posts.parent=0 AND ";
			//DB $query .= "imas_forum_posts.forumid='$forumid' ";
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
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$uniqviews[$row[0]] = $row[1]-1;
			}

			//DB $query = "SELECT imas_forum_posts.*,imas_forum_threads.views as tviews,imas_users.LastName,imas_users.FirstName,imas_forum_threads.stugroupid FROM imas_forum_posts,imas_users,imas_forum_threads WHERE ";
			//DB $query .= "imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.parent=0 AND imas_forum_posts.forumid='$forumid' ";
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
				//DB $query .= "LIMIT $offset,$threadsperpage";
				$query .= "LIMIT $offset,$threadsperpage";
			}
			// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$stm = $DBH->prepare($query);
			$stm->execute(array(':forumid'=>$forumid, ':now'=>$isteacher?2000000000:$now));

			//DB $query = "SELECT imas_forum_posts.*,imas_forum_threads.views as tviews,imas_users.LastName,imas_users.FirstName,imas_forum_threads.stugroupid FROM imas_forum_posts,imas_users,imas_forum_threads WHERE ";
			//DB $query .= "imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.parent=0 AND imas_forum_posts.forumid='$forumid' ";
			//DB
			//DB if ($dofilter) {
			//DB 	$query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
			//DB }
			//DB if ($page==-1) {
			//DB 	$query .= "AND imas_forum_posts.threadid IN ($newpostlist) ";
			//DB } else if ($page==-2) {
			//DB 	$query .= "AND imas_forum_posts.threadid IN ($flaggedlist) ";
			//DB }
			//DB if ($sortby==0) {
			//DB 	$query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_posts.id DESC ";
			//DB } else if ($sortby==1) {
			//DB 	$query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_threads.lastposttime DESC ";
			//DB }
			//DB $offset = ($page-1)*$threadsperpage;
			//DB if ($page>0) {
			//DB 	$query .= "LIMIT $offset,$threadsperpage";//DB OFFSET $offset";
			//DB }
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB if (mysql_num_rows($result)==0) {
			if ($stm->rowCount()==0) {
				echo '<tr><td colspan='.(($isteacher && $grpaid>0 && !$dofilter)?5:4).'>No posts have been made yet.  Click Add New Thread to start a new discussion</td></tr>';
			}
			//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
				if (isset($postcount[$line['id']])) {
					$posts = $postcount[$line['id']];
					$lastpost = tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
				} else {
					$posts = 0;
					$lastpost = '';
				}
				echo "<tr id=\"tr".Sanitize::onlyInt($line['id'])."\"";
				if ($line['posttype']>0) {
					echo "class=sticky";
				} else if (isset($flags[$line['id']])) {
					echo "class=tagged";
				}
				echo "><td>";
				echo "<span class=\"right\">\n";
				if ($line['lastposttime']>$now) {
					echo "<img class=mida src=\"$imasroot/img/time.png\" alt=\"Scheduled\" title=\"Scheduled for later release\" /> ";
				}
				if ($line['tag']!='') { //category tags
					echo '<span class="forumcattag">'.Sanitize::encodeStringForDisplay($line['tag']).'</span> ';
				}

				if ($line['posttype']==0) {
					if (isset($flags[$line['id']])) {
						echo "<img class=\"pointer\" id=\"tag". Sanitize::onlyInt($line['id'])."\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggletagged(". Sanitize::onlyInt($line['id']) . ");return false;\" alt=\"Flagged\" />";
					} else {
						echo "<img class=\"pointer\" id=\"tag". Sanitize::onlyInt($line['id'])."\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggletagged(". Sanitize::onlyInt($line['id'])  . ");return false;\" alt=\"Not flagged\"/>";
					}
				} else if ($isteacher) {
					if ($line['posttype']==2) {
						echo "<img class=mida src=\"$imasroot/img/lock.png\" alt=\"Lock\" title=\"Locked (no replies)\" /> ";
					} else if ($line['posttype']==3) {
						echo "<img class=mida src=\"$imasroot/img/noview.png\" alt=\"No View\" title=\"Students can only see their own replies\" /> ";
					}
				}
				if ($isteacher || ($line['userid']==$userid && $allowmod && time()<$postby) || ($allowdel && $line['userid']==$userid && $posts==0)) {
					echo '<span class="dropdown">';
					echo '<a tabindex=0 class="dropdown-toggle" id="dropdownMenu'.Sanitize::onlyInt($line['id']).'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
					echo ' <img src="../img/gears.png" class="mida" alt="Options"/>';
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
				echo "<a href=\"posts.php?cid=$cid&forum=$forumid&thread=" .Sanitize::onlyInt($line['id']). "&page=". Sanitize::onlyInt($page) . Sanitize::encodeUrlParam($grpqs) .'">'. Sanitize::encodeStringForDisplay($line['subject']) ."</a></td>";
				if ($line['lastposttime']>$now) {
					echo '</i>';
				}
				printf("<td>%s</td>\n", Sanitize::encodeStringForDisplay($name));

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
				if ($lastpost=='' || $maxdate[$line['id']]>$lastview[$line['id']]) {
					echo "<span class=\"noticetext\">New</span>";
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
