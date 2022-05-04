<?php
//Displays forums posts
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

$cid = Sanitize::courseId($_GET['cid']);
$forumid = Sanitize::onlyInt($_GET['forum']);
$threadid = Sanitize::onlyInt($_GET['thread']);
$page = Sanitize::onlyInt($_GET['page']);
if (!empty($_GET['embed'])) {
	$flexwidth = true;
	$nologo = true;
}
$now = time();

//special "page"s
//-1 new posts from forum page
//-2 tagged posts from forum page
//-3 new posts from newthreads page
//-4 forum search
//-5 tagged posts page

if ($page==-4) {
	$redirecturl = $GLOBALS['basesiteurl'] . "/forums/forums.php?cid=$cid";
} else if ($page==-3) {
	$redirecturl = $GLOBALS['basesiteurl'] . "/forums/newthreads.php?cid=$cid";
} else if ($page==-5) {
	$redirecturl = $GLOBALS['basesiteurl'] . "/forums/flaggedthreads.php?cid=$cid";
} else {
	$redirecturl = $GLOBALS['basesiteurl'] . "/forums/thread.php?cid=$cid&forum=$forumid&page=$page";
}
$query = "SELECT ifs.settings,ifs.replyby,ifs.defdisplay,ifs.name,ifs.points,ifs.groupsetid,ifs.postby,ifs.rubric,ifs.tutoredit,ifs.enddate,ifs.avail,ifs.allowlate,ifs.courseid,ift.forumid ";
$query .= "FROM imas_forums AS ifs JOIN imas_forum_threads AS ift ON ifs.id=ift.forumid WHERE ifs.id=:id AND ift.id=:threadid";
$stm = $DBH->prepare($query);
$stm->execute(array(':id'=>$forumid, ':threadid'=>$threadid));
if ($stm->rowCount()==0) {
	echo "Invalid forum ID or thread ID";
	exit;
}
list($forumsettings, $replyby, $defdisplay, $forumname, $pointsposs, $groupset, $postby, $rubric, $tutoredit, $enddate, $avail, $allowlate, $forumcourseid, $threadforum) = $stm->fetch(PDO::FETCH_NUM);
if ($forumcourseid != $cid) {
	echo "Invalid forum ID";
	exit;
} else if ($threadforum != $forumid) {
	echo "Invalid thread ID";
	exit;
}

if (isset($_GET['markunread'])) {
	$stm = $DBH->prepare("DELETE FROM imas_forum_views WHERE userid=:userid AND threadid=:threadid");
	$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid));
	header('Location: ' . $redirecturl . "&r=" . Sanitize::randomQueryStringParam());
	exit;
}
if (isset($_GET['marktagged'])) {
	$stm = $DBH->prepare("UPDATE imas_forum_views SET tagged=1 WHERE userid=:userid AND threadid=:threadid");
	$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid));
	header('Location: ' . $redirecturl . "&r=" . Sanitize::randomQueryStringParam());
	exit;
} else if (isset($_GET['markuntagged'])) {
	$stm = $DBH->prepare("UPDATE imas_forum_views SET tagged=0 WHERE userid=:userid AND threadid=:threadid");
	$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid));
	header('Location: ' . $redirecturl . "&r=" . Sanitize::randomQueryStringParam());
	exit;
}
$stm = $DBH->prepare("SELECT settings,replyby,defdisplay,name,points,groupsetid,postby,rubric,tutoredit,enddate,avail,allowlate,autoscore FROM imas_forums WHERE id=:id");
$stm->execute(array(':id'=>$forumid));
list($forumsettings, $replyby, $defdisplay, $forumname, $pointsposs, $groupset, $postby, $rubric, $tutoredit, $enddate, $avail, $allowlate, $autoscore) = $stm->fetch(PDO::FETCH_NUM);
if (($postby>0 && $postby<2000000000) || ($replyby>0 && $replyby<2000000000)) {
	$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,waivereqscore,itemtype FROM imas_exceptions WHERE assessmentid=:assessmentid AND userid=:userid AND (itemtype='F' OR itemtype='P' OR itemtype='R')");
	$stm->execute(array(':assessmentid'=>$forumid, ':userid'=>$userid));
	if ($stm->rowCount()>0) {
		$exception = $stm->fetch(PDO::FETCH_NUM);
	} else {
		$exception = null;
	}
	require_once("../includes/exceptionfuncs.php");
	if (isset($studentid) && !isset($_SESSION['stuview'])) {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
	} else {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, false);
	}
	$infoline = array('replyby'=>$replyby, 'postby'=>$postby, 'enddate'=>$enddate, 'allowlate'=>$allowlate);
	list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepassP, $canuselatepassR, $postby, $replyby, $enddate) = $exceptionfuncs->getCanUseLatePassForums($exception, $infoline);
}
if (isset($studentid) && ($avail==0 || ($avail==1 && time()>$enddate))) {
	require("../header.php");
	echo '<p>This forum is closed.  <a href="course.php?cid='.$cid.'">Return to the course page</a></p>';
	require("../footer.php");
	exit;
}

$allowreply = ($isteacher || (time()<$replyby));
$allowanon = (($forumsettings&1)==1);
$allowmod = ($isteacher || (($forumsettings&2)==2));
$allowdel = ($isteacher || (($forumsettings&4)==4));
$allowlikes = (($forumsettings&8)==8);
$postbeforeview = (($forumsettings&16)==16);
$haspoints =  ($pointsposs > 0);
$groupid = 0;

$canviewall = (isset($teacherid) || isset($tutorid));
$caneditscore = (isset($teacherid) || (isset($tutorid) && ($tutoredit&1)==1));
$canviewscore = (isset($teacherid) || (isset($tutorid) && $tutoredit!=2));

if ($groupset>0) {
	if (!isset($_GET['grp'])) {
		if (!$canviewall) {
			$query = 'SELECT i_sg.id FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
			$query .= "WHERE i_sgm.userid=:userid AND i_sg.groupsetid=:groupsetid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':userid'=>$userid, ':groupsetid'=>$groupset));
			if ($stm->rowCount()>0) {
				$groupid = $stm->fetchColumn(0);
			} else {
				$groupid=0;
			}
		} else {
			$groupid = -1;
		}
	} else {
		if (!$canviewall) {
			$groupid = intval($_GET['grp']);
			$stm = $DBH->prepare("SELECT id FROM imas_stugroupmembers WHERE stugroupid=:stugroupid AND userid=:userid");
			$stm->execute(array(':stugroupid'=>$groupid, ':userid'=>$userid));
			if ($stm->rowCount()==0) {
				echo 'Invalid group - try again';
				exit;
			}
		} else {
			$groupid = intval($_GET['grp']);
		}
	}
}
$placeinhead = '';
if ($haspoints && $caneditscore && $rubric != 0) {
	$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/rubric_min.js?v=022622"></script>';
	require("../includes/rubric.php");
}


if (isset($_GET['view'])) {
	$view = $_GET['view'];
} else {
	$view = $defdisplay;  //0: expanded, 1: collapsed, 2: condensed
}

$caller = "posts";
include("posthandler.php");

$pagetitle = "Posts";
$placeinhead .= '<link rel="stylesheet" href="'.$staticroot.'/forums/forums.css?ver=010619" type="text/css" />';
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/posts.js?v=011517"></script>';
//$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
if ($caneditscore && $_SESSION['useed']!=0) {
	$useeditor = "noinit";
	$placeinhead .= '<script type="text/javascript"> initeditor("divs","fbbox",null,true);</script>';
}
require("../header.php");

if ($haspoints && $caneditscore && $rubric != 0) {
	$stm = $DBH->prepare("SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id=:id");
	$stm->execute(array(':id'=>$rubric));
	if ($stm->rowCount()>0) {
		$row = $stm->fetch(PDO::FETCH_NUM);
		// $row data is sanitized by printrubrics().
		echo printrubrics(array($row));
	}
}

$allowmsg = false;
if (!$canviewall) {
	$stm = $DBH->prepare("SELECT msgset FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	if (($stm->fetchColumn(0)%5)==0) {
		$allowmsg = true;
	}
}
if ($postbeforeview && !$canviewall) {
	$stm = $DBH->prepare("SELECT id FROM imas_forum_posts WHERE forumid=:forumid AND parent=0 AND userid=:userid LIMIT 1");
	$stm->execute(array(':forumid'=>$forumid, ':userid'=>$userid));
	$oktoshow = ($stm->rowCount()>0);
	if (!$oktoshow) {
		$stm = $DBH->prepare("SELECT posttype FROM imas_forum_posts WHERE id=:id");
		$stm->execute(array(':id'=>$threadid));
		$oktoshow = ($stm->fetchColumn(0)>0);
	}
} else {
	$oktoshow = true;
}

if ($oktoshow) {
	if ($haspoints) {
		$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,imas_grades.score,imas_grades.feedback,imas_students.section,imas_students.id AS stuid FROM ";
		$query .= "imas_forum_posts JOIN imas_users ON imas_forum_posts.userid=imas_users.id ";
		$query .= "LEFT JOIN imas_students ON imas_students.userid=imas_forum_posts.userid AND imas_students.courseid=:courseid ";
		$query .= "LEFT JOIN imas_grades ON imas_grades.gradetype='forum' AND imas_grades.refid=imas_forum_posts.id ";
		$query .= "WHERE (imas_forum_posts.id=:id OR imas_forum_posts.threadid=:threadid) ORDER BY imas_forum_posts.id";
	} else {
		$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,imas_students.section,imas_students.id AS stuid FROM ";
		$query .= "imas_forum_posts JOIN imas_users ON imas_forum_posts.userid=imas_users.id ";
		$query .= "LEFT JOIN imas_students ON imas_students.userid=imas_forum_posts.userid AND imas_students.courseid=:courseid ";
		$query .= "WHERE (imas_forum_posts.id=:id OR imas_forum_posts.threadid=:threadid) ORDER BY imas_forum_posts.id";
		//$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg from imas_forum_posts,imas_users ";
		//$query .= "WHERE imas_forum_posts.userid=imas_users.id AND (imas_forum_posts.id='$threadid' OR imas_forum_posts.threadid='$threadid') ORDER BY imas_forum_posts.id";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':id'=>$threadid, ':threadid'=>$threadid));
	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$children = array(); $date = array(); $subject = array(); $re = array(); $message = array(); $posttype = array(); $likes = array(); $mylikes = array();
	$ownerid = array(); $files = array(); $points= array(); $feedback= array(); $poster= array(); $email= array(); $hasuserimg = array(); $section = array();
	$isstu = array();
	while ($line =  $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($line['parent']==0) {
			if ($line['replyby']!=null) {
				$allowreply = ($canviewall || (time()<$line['replyby']));
			}
		}

		if ($line['id']==$threadid) {
			$newviews = $line['views']+1;
		}
		$isstu[$line['id']] = ($line['stuid'] !== null);
		$children[$line['parent']][] = $line['id'];
		$date[$line['id']] = $line['postdate'];
		$n = 0;
		while (strpos($line['subject'],'Re: ')===0) {
			$line['subject'] = substr($line['subject'],4);
			$n++;
		}
		if ($n==1) {
			$re[$line['id']] = _('Re').': ';
		} else if ($n>1) {
			$re[$line['id']] = _('Re')."<sup>$n</sup>: ";
		} else {
			$re[$line['id']] = '';
		}

		$subject[$line['id']] = $line['subject'];
		if ($_SESSION['graphdisp']==0) {
			$line['message'] = preg_replace('/<embed[^>]*alt="([^"]*)"[^>]*>/',"[$1]", $line['message']);
		}
		$message[$line['id']] = $line['message'];
		$posttype[$line['id']] = $line['posttype'];
		$ownerid[$line['id']] = $line['userid'];
		$hasuserimg[$line['id']] = $line['hasuserimg'];

		if ($line['files']!='') {
			$files[$line['id']] = $line['files'];
		}
		if ($haspoints && $line['score']!==null) {
			$points[$line['id']] = 1*$line['score'];
			$feedback[$line['id']] = $line['feedback'];
		} else {
			$points[$line['id']] = $line['score'] ?? null;
			$feedback[$line['id']] = null;
		}
		if ($line['isanon']==1) {
			$poster[$line['id']] = "Anonymous";
			$ownerid[$line['id']] = 0;
		} else {
			$poster[$line['id']] = $line['FirstName'] . ' ' . $line['LastName'];
			$section[$line['id']] = $line['section'];
			$email[$line['id']] = $line['email'];
		}
		$likes[$line['id']] = array(0,0,0);

	}

	if ($allowlikes) {
		//get likes
		$query = "SELECT postid,type,count(*) FROM imas_forum_likes WHERE threadid=:threadid ";
		$query .= "GROUP BY postid,type";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':threadid'=>$threadid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$likes[$row[0]][$row[1]] = $row[2];
		}
		$stm = $DBH->prepare("SELECT postid FROM imas_forum_likes WHERE threadid=:threadid AND userid=:userid");
		$stm->execute(array(':threadid'=>$threadid, ':userid'=>$userid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$mylikes[] = $row[0];
		}
	}

	if (count($files)>0) {
		require_once('../includes/filehandler.php');
	}

	//get next/prev before marked as read
	$prevth = '';
	$nextth = '';
	if ($page==-3 || $page==-5) { //came from new threads or flagged threads
		if ($page==-3) {
			$query = "SELECT imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime FROM imas_forum_threads ";
			$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forum_threads.lastposttime<:now ";
			$array = array(':now'=>$now);
			if (!isset($teacherid)) {
			  $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
			}
			$query .= "LEFT JOIN imas_forum_views AS mfv ";
			$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid WHERE imas_forums.courseid=:courseid ";
			$array[':userid']=  $userid;
			$array[':courseid']=$cid;
			if (!isset($teacherid)) {
			  $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userid2)) ";
			  $array[':userid2']=$userid;
			}
			$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ORDER BY imas_forum_threads.lastposttime DESC";
		} else {
			$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime FROM imas_forum_threads ";
			$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forum_threads.lastposttime<:now ";
			$array = array(':now'=>$now);
			if (!isset($teacherid)) {
			  $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
			}
			$query .= "LEFT JOIN imas_forum_views AS mfv ";
			$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid WHERE imas_forums.courseid=:courseid ";
			$array[':userid']=  $userid;
			$array[':courseid']=$cid;
			if (!isset($teacherid)) {
			  $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userid2)) ";
			  $array[':userid2']=$userid;
			}
			$query .= "AND (mfv.tagged=1) ORDER BY imas_forum_threads.lastposttime DESC";
		}
		$stm = $DBH->prepare($query);
		$stm->execute($array);
		$lastrow = array();
		$atcur = false;
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($atcur) {
				$nextth = $row['threadid'];
				$nextthforum = $row['id'];
				break;
			}
			if ($row['id']==$forumid && $row['threadid']==$threadid) { //found current
				if (count($lastrow)>1) {
					$prevth = $lastrow['threadid'];
					$prevthforum = $lastrow['id'];
				}
				$atcur = true;
			} else if (isset($_GET['olpt']) && $_GET['olpt']>$row['lastposttime']) {
				if (count($lastrow)>1) {
					$prevth = $lastrow['threadid'];
					$prevthforum = $lastrow['id'];
				}
				$nextth = $row['threadid'];
				$nextthforum = $row['id'];
				break;
			}
			$lastrow = $row;
		}
	} else {
		$query = "SELECT id FROM imas_forum_threads WHERE forumid=:forumid AND id<:threadid AND lastposttime<:now ";
		$array = array(':forumid'=>$forumid, ':threadid'=>$threadid, ':now'=>$now);
		if ($groupset>0 && $groupid!=-1) {
			$query .= "AND (stugroupid=:stugroupid OR stugroupid=0) ";
			$array[':stugroupid']=$groupid;
		}
		$query .= "ORDER BY id DESC LIMIT 1";
		//$query = "SELECT id FROM imas_forum_posts WHERE forumid='$forumid' AND threadid<'$threadid' AND parent=0 ORDER BY threadid DESC LIMIT 1";
		$stm = $DBH->prepare($query);
		$stm->execute($array);
		// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());

		if ($stm->rowCount()>0) {
			$prevth = $stm->fetchColumn(0);
			$prevthforum = $forumid;
		}
		$query ="SELECT id FROM imas_forum_threads WHERE forumid=:forumid AND id>:threadid AND lastposttime<:now ";
		$array = array(':forumid'=>$forumid, ':threadid'=>$threadid, ':now'=>$now);
		if ($groupset>0 && $groupid!=-1) {
			$query .= "AND (stugroupid=:stugroupid OR stugroupid=0) ";
			$array[':stugroupid']=$groupid;
		}
		$query .= "ORDER BY id LIMIT 1";
		$stm = $DBH->prepare($query);
		$stm->execute($array);
		//$query = "SELECT id FROM imas_forum_posts WHERE forumid='$forumid' AND threadid>'$threadid' AND parent=0 ORDER BY threadid LIMIT 1";
		// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());

		if ($stm->rowCount()>0) {
			$nextth = $stm->fetchColumn(0);
			$nextthforum = $forumid;
		}
	}

	//update view count
	$stm = $DBH->prepare("UPDATE imas_forum_posts SET views=:views WHERE id=:id");
	$stm->execute(array(':views'=>$newviews, ':id'=>$threadid));
	$stm = $DBH->prepare("UPDATE imas_forum_threads SET views=views+1 WHERE id=:id");
	$stm->execute(array(':id'=>$threadid));

	//mark as read
	$stm = $DBH->prepare("SELECT lastview,tagged FROM imas_forum_views WHERE userid=:userid AND threadid=:threadid");
	$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid));

	if ($stm->rowCount()>0) {
		list($lastview, $tagged) = $stm->fetch(PDO::FETCH_NUM);
		$stm = $DBH->prepare("UPDATE imas_forum_views SET lastview=:lastview WHERE userid=:userid AND threadid=:threadid");
		$stm->execute(array(':lastview'=>$now, ':userid'=>$userid, ':threadid'=>$threadid));
	} else {
		$lastview = 0;
		$tagged = 0;
		$stm = $DBH->prepare("INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES (:userid, :threadid, :lastview)");
		$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid, ':lastview'=>$now));
	}
}
if (empty($_GET['embed'])) {
    echo "<div class=breadcrumb>";
    if (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0) {
        echo "$breadcrumbbase  <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }
    if ($page==-4) {
		echo "<a href=\"forums.php?cid=$cid\">Forum Search</a> ";
	} else if ($page==-3) {
		echo "<a href=\"newthreads.php?cid=$cid\">New Threads</a> ";
	} else if ($page==-5) {
		echo "<a href=\"flaggedthreads.php?cid=$cid\">Flagged Threads</a> ";
	} else {
		echo "<a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">".Sanitize::encodeStringForDisplay($forumname)."</a> ";
	}
	echo "&gt; Posts</div>\n";
}

if (!$oktoshow) {
	echo '<p>This post is blocked. In this forum, you must post your own thread before you can read those posted by others.</p>';
} else {
	echo '<div id="headerposts" class="pagetitle"><h1>Forum: '.Sanitize::encodeStringForDisplay($forumname).'</h1></div>';
	echo "<b style=\"font-size: 120%\">"._('Post').': '. $re[$threadid] . Sanitize::encodeStringForDisplay($subject[$threadid]) . "</b><br/>\n";

	echo '<div class="fixedonscroll">';
	if ($prevth != '') {
		echo "<a href=\"posts.php?cid=$cid&forum=$prevthforum&thread=".Sanitize::onlyInt($prevth)."&page=$page&grp=".Sanitize::onlyInt($groupid)."\">Prev</a> ";
	} else {
		echo "Prev ";
	}

	if ($nextth != '') {
		echo "<a href=\"posts.php?cid=$cid&forum=$nextthforum&thread=".Sanitize::onlyInt($nextth)."&page=$page&grp=".Sanitize::onlyInt($groupid)."\">Next</a>";
	} else {
		echo "Next";
	}

	echo " | <a href=\"posts.php?cid=$cid&forum=$forumid&thread=$threadid&page=$page&markunread=true\">Mark Unread</a> ";
	if ($tagged) {
		echo "| <img class=\"pointer\" id=\"tag$threadid\" src=\"$staticroot/img/flagfilled.gif\" onClick=\"toggletagged($threadid);return false;\" alt=\"Flagged\" /> ";
	} else {
		echo "| <img class=\"pointer\" id=\"tag$threadid\" src=\"$staticroot/img/flagempty.gif\" onClick=\"toggletagged($threadid);return false;\" alt=\"Not flagged\"/> ";
	}

	echo '| <button onclick="expandall()">'._('Expand All').'</button>';
	echo '<button onclick="collapseall()">'._('Collapse All').'</button> | ';
	echo '<button onclick="showall()">'._('Show All').'</button>';
	echo '<button onclick="hideall()">'._('Hide All').'</button>';
	echo '</div>';

	/*if ($view==2) {
	echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&view=0\">View Expanded</a>";
} else {
echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&view=2\">View Condensed</a>";
}*/

function printchildren($base,$restricttoowner=false) {
	$curdir = rtrim(dirname(__FILE__), '/\\');
	global $DBH,$children,$date,$subject,$re,$message,$poster,$email,$forumid,$threadid,$isteacher,$cid,$userid,$ownerid,$points;
	global $feedback,$posttype,$lastview,$myrights,$allowreply,$allowmod,$allowdel,$allowlikes,$view,$page,$allowmsg;
	global $haspoints,$imasroot,$postby,$replyby,$files,$CFG,$rubric,$pointsposs,$hasuserimg,$urlmode,$likes,$mylikes,$section;
	global $canviewall, $caneditscore, $canviewscore, $isstu,$staticroot;
	if (!isset($CFG['CPS']['itemicons'])) {
		$itemicons = array('web'=>'web.png', 'doc'=>'doc.png', 'wiki'=>'wiki.png',
		'html'=>'html.png', 'forum'=>'forum.png', 'pdf'=>'pdf.png',
		'ppt'=>'ppt.png', 'zip'=>'zip.png', 'png'=>'image.png', 'xls'=>'xls.png',
		'gif'=>'image.png', 'jpg'=>'image.png', 'bmp'=>'image.png',
		'mp3'=>'sound.png', 'wav'=>'sound.png', 'wma'=>'sound.png',
		'swf'=>'video.png', 'avi'=>'video.png', 'mpg'=>'video.png',
		'nb'=>'mathnb.png', 'mws'=>'maple.png', 'mw'=>'maple.png');
	} else {
		$itemicons = $CFG['CPS']['itemicons'];
	}
	foreach($children[$base] as $child) {
		if ($restricttoowner && $ownerid[$child] != $userid) {
			continue;
		}
		echo "<div class=block> ";
		echo '<span class="leftbtns">';
		if (isset($children[$child])) {
			if ($view==1) {
				$lbl = '+';
				$img = "expand";
			} else {
				$lbl = '-';
				$img = "collapse";
			}
			echo "<img class=\"pointer expcol\" src=\"$staticroot/img/$img.gif\" onClick=\"toggleshow(this)\" alt=\"Expand/Collapse\"/> ";
		}
		if ($hasuserimg[$child]==1) {
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				echo "<img class=\"pii-image\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm{$ownerid[$child]}.jpg\"  onclick=\"togglepic(this)\" alt=\"User picture\"/>";
			} else {
				echo "<img class=\"pii-image\" src=\"$imasroot/course/files/userimg_sm{$ownerid[$child]}.jpg\"  onclick=\"togglepic(this)\" alt=\"User picture\"/>";
			}
		}
		echo '</span>';
		echo "<span class=right>";

		if ($view==2) {
			echo "<input type=button class=\"shbtn\" value=\"Show\" onClick=\"toggleitem(this)\">\n";
		} else {
			echo "<input type=button class=\"shbtn\" value=\"Hide\" onClick=\"toggleitem(this)\">\n";
		}
		if ($posttype[$child]!=2 && $myrights > 5 && $allowreply) {
			$embedstr = isset($_GET['embed'])?'&embed=true':'';
			echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=reply&replyto=$child$embedstr\" onclick=\"return checkchgstatus(0,$child)\">Reply</a> ";
		}
		if ($isteacher || ($ownerid[$child]==$userid && $allowmod && (($base==0 && time()<$postby) || ($base>0 && time()<$replyby))) || ($allowdel && $ownerid[$child]==$userid && !isset($children[$child]))) {
			echo '<span class="dropdown">';
			echo '<a tabindex=0 class="dropdown-toggle" id="dropdownMenu'.$child.'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
			echo ' <img src="'.$staticroot.'/img/gears.png" class="mida" alt="Options"/>';
			echo '</a>';
			echo '<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="dropdownMenu'.$child.'">';

			if ($isteacher) {
				echo "<li><a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&move=$child\">Move</a></li>\n";
			}
			if ($isteacher || ($ownerid[$child]==$userid && $allowmod)) {
				if (($base==0 && time()<$postby) || ($base>0 && time()<$replyby) || $isteacher) {
					echo "<li><a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=$child\" onclick=\"return checkchgstatus(1,$child)\">Modify</a></li>\n";
				}
			}
			if ($isteacher || ($allowdel && $ownerid[$child]==$userid && !isset($children[$child]))) {
				echo "<li><a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&remove=$child\">Remove</a></li>\n";
			}

			echo '</ul></span>';
		}

		echo "</span>\n";
		echo '<span style="float:left">';
		echo "<b>".$re[$child]. Sanitize::encodeStringForDisplay($subject[$child]) . "</b><br/>"._('Posted by').": ";
		//if ($isteacher && $ownerid[$child]!=0) {
		//	echo "<a href=\"mailto:{$email[$child]}\">";
		//} else if ($allowmsg && $ownerid[$child]!=0) {
		if (($isteacher || $allowmsg) && $ownerid[$child]!=0) {
			echo "<a href=\"../msgs/msglist.php?cid=$cid&add=new&to={$ownerid[$child]}\" ";
			if ($section[$child]!='') {
				echo 'title="Section: '.$section[$child].'"';
			}
			echo ">";
		}
		echo '<span class="pii-full-name">'.Sanitize::encodeStringForDisplay($poster[$child]).'</span>'; // This is the user's first and last name.
		if (($isteacher || $allowmsg) && $ownerid[$child]!=0) {
			echo "</a>";
		}
		if ($isteacher && $ownerid[$child]!=0 && $ownerid[$child]!=$userid) {
			echo " <a class=\"small\" href=\"$imasroot/course/gradebook.php?cid=$cid&stu={$ownerid[$child]}\" target=\"_blank\">[GB]</a>";
			if ($base==0 && preg_match('/Question\s+about\s+#(\d+)\s+in\s+(.*)\s*$/',$subject[$child],$matches)) {
				$query = "SELECT ia.ver,ia.id,ias.id AS asid FROM imas_assessments AS ia LEFT JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid ";
				$query .= "AND ias.userid=:ownerid WHERE ia.courseid=:courseid AND (ia.name=:name OR ia.name=:name2) ORDER BY asid DESC";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$cid, ':name'=>$matches[2], ':name2'=>htmlentities($matches[2]), ':ownerid'=>intval($ownerid[$child])));
				if ($stm->rowCount()>0) {
					$qn = $matches[1];
					$r = $stm->fetch(PDO::FETCH_ASSOC);
					if ($r['ver'] > 1) {
						echo " <a class=\"small\" href=\"$imasroot/assess2/gbviewassess.php?cid=$cid&uid={$ownerid[$child]}&aid={$r['id']}#qwrap$qn\" target=\"_blank\">[assignment]</a>";
					} else if ($r['asid'] !== null) {
						echo " <a class=\"small\" href=\"$imasroot/course/gb-viewasid.php?cid=$cid&uid={$ownerid[$child]}&asid={$r['asid']}#qwrap$qn\" target=\"_blank\">[assignment]</a>";
					}
				}
			}
		}
		echo ', ';
		echo tzdate("D, M j, Y, g:i a",$date[$child]);

		if ($date[$child]>$lastview) {
			echo " <span class=noticetext>New</span>\n";
		}
		echo '</span>';

		if ($allowlikes) {
			$icon = (in_array($child,$mylikes))?'liked':'likedgray';
			$likemsg = 'Liked by ';
			$likecnt = 0;
			$likeclass = '';
			if ($likes[$child][0]>0) {
				$likeclass = ' liked';
				$likemsg .= $likes[$child][0].' ' . ($likes[$child][0]==1?'student':'students');
				$likecnt += $likes[$child][0];
			}
			if ($likes[$child][1]>0 || $likes[$child][2]>0) {
				$likeclass = ' likedt';
				$n = $likes[$child][1] + $likes[$child][2];
				if ($likes[$child][0]>0) { $likemsg .= ' and ';}
				$likemsg .= $n.' ';
				if ($likes[$child][2]>0) {
					$likemsg .= ($n==1?'teacher':'teachers');
					if ($likes[$child][1]>0) {
						$likemsg .= '/tutors/TAs';
					}
				} else if ($likes[$child][1]>0) {
					$likemsg .= ($n==1?'tutor/TA':'tutors/TAs');
				}
				$likecnt += $n;
			}
			if ($likemsg=='Liked by ') {
				$likemsg = '';
			} else {
				$likemsg .= '.';
			}
			if ($icon=='liked') {
				$likemsg = 'You like this. '.$likemsg;
			} else {
				$likemsg = 'Click to like this post. '.$likemsg;;
			}

			echo '<div class="likewrap">';
			echo "<img id=\"likeicon$child\" class=\"likeicon$likeclass\" src=\"$staticroot/img/$icon.png\" title=\"$likemsg\" onclick=\"savelike(this)\" alt=\"Like\">";
			echo " <span class=\"pointer\" id=\"likecnt$child\" onclick=\"GB_show('"._('Post Likes')."','listlikes.php?cid=$cid&amp;post=$child',500,500);\">".($likecnt>0?$likecnt:'').' </span> ';
			echo '</div>';
		}
		echo '<div class="clear"></div>';
		echo "</div>\n";
		if ($view==2) {
			echo "<div class=\"blockitems hidden\">";
		} else {
			echo "<div class=\"blockitems\" style=\"clear:all\">";
		}
		if(isset($files[$child]) && $files[$child]!='') {
			$fl = explode('@@',$files[$child]);
			if (count($fl)>2) {
				echo '<p><b>Files:</b> ';//<ul class="nomark">';
			} else {
				echo '<p><b>File:</b> ';
			}
			for ($i=0;$i<count($fl)/2;$i++) {
				//if (count($fl)>2) {echo '<li>';}
				echo '<a href="'.getuserfileurl('ffiles/'.$child.'/'.$fl[2*$i+1]).'" target="_blank">';
				$extension = ltrim(strtolower(strrchr($fl[2*$i+1],".")),'.');
				if (isset($itemicons[$extension])) {
					echo "<img alt=\"$extension\" src=\"$staticroot/img/{$itemicons[$extension]}\" class=\"mida\"/> ";
				} else {
					echo "<img alt=\"doc\" src=\"$staticroot/img/doc.png\" class=\"mida\"/> ";
				}
				echo $fl[2*$i].'</a> ';
				//if (count($fl)>2) {echo '</li>';}
			}
			//if (count($fl)>2) {echo '</ul>';}
			echo '</p>';
		}
		echo filter($message[$child]);
		if ($haspoints) {
			if ($caneditscore && $isstu[$child]) {
				echo '<hr/>';
				echo "Score: <input class=scorebox type=text size=2 name=\"score[$child]\" id=\"scorebox$child\" value=\"";
				if ($points[$child]!==null) {
					echo $points[$child];
				}
				echo "\"/> ";
				if ($rubric != 0) {
					echo printrubriclink($rubric,$pointsposs,"scorebox$child", "feedback$child");
				}
				echo " Private Feedback: ";
				if ($_SESSION['useed']==0) {
					echo "<textarea class=scorebox cols=\"50\" rows=\"2\" name=\"feedback$child\" id=\"feedback$child\">";
					if ($feedback[$child]!==null) {
						echo Sanitize::encodeStringForDisplay($feedback[$child]);
					}
					echo "</textarea>";
				} else {
					echo '<div class="fbbox" id="feedback'.$child.'">';
					if ($feedback[$child]!==null) {
						echo Sanitize::outgoingHtml($feedback[$child]);
					}
					echo '</div>';
				}
			} else if (($ownerid[$child]==$userid || $canviewscore) && $points[$child]!==null) {
				echo '<div class="signup">Score: ';
				echo "<span class=red>{$points[$child]} points</span><br/> ";
				if ($feedback[$child]!==null && $feedback[$child]!='') {
					echo 'Private Feedback: ';
					echo '<div>'.Sanitize::outgoingHtml($feedback[$child]).'</div>';
				}
				echo '</div>';
			}
		}


		echo "<div class=\"clear\"></div></div>\n";
		echo '<div class="forumgrp'.(($view==1)?' hidden':'').'">';
		if (isset($children[$child])) { //if has children
			printchildren($child, ($posttype[$child]==3 && !$isteacher));
		}
		echo "</div>\n";
		//}
	}
}
if ($caneditscore && $haspoints) {
	echo "<form method=post action=\"thread.php?cid=$cid&forum=$forumid&page=$page&thread=$threadid&score=true\">";
}
printchildren(0);
if ($caneditscore && $haspoints) {
	echo '<div><input type=submit name="save" value="Save Grades" /></div>';
	if ($prevth!='' && $page!=-3) {
		echo '<input type="hidden" name="prevth" value="'.Sanitize::encodeStringForDisplay($prevth).'"/>';
		echo '<input type="submit" name="save" value="Save Grades and View Previous"/>';
	}
	if ($nextth!='' && $page!=-3) {
		echo '<input type="hidden" name="nextth" value="'.Sanitize::encodeStringForDisplay($nextth).'"/>';
		echo '<input type="submit" name="save" value="Save Grades and View Next"/>';
	}
	echo "</form>";
}
echo "<img src=\"$staticroot/img/expand.gif\" style=\"visibility:hidden\" alt=\"Expand\" />";
echo "<img src=\"$staticroot/img/collapse.gif\" style=\"visibility:hidden\" alt=\"Collapse\" />";

}
if (empty($_GET['embed'])) {
	echo "<div class=right><a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">Back to Forum Topics</a></div>\n";
} else {
	echo '<div class=right><button type="button" onclick="parent.GB_hide()">'._('Close').'</button></div>';
}
require("../footer.php");
?>
