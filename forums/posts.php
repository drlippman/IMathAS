<?php
//Displays forums posts
//(c) 2006 David Lippman

require_once "../init.php";


if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	require_once "../header.php";
	echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	require_once "../footer.php";
	exit;
}
if (isset($teacherid)) {
	$isteacher = true;
} else {
	$isteacher = false;
}
$istutor = isset($tutorid);

$cid = Sanitize::courseId($_GET['cid']);
$forumid = Sanitize::onlyInt($_GET['forum'] ?? 0);
$threadid = Sanitize::onlyInt($_GET['thread'] ?? 0);
$page = Sanitize::onlyInt($_GET['page'] ?? 1);
if ($page < 1) {
	// backwards compatability; handle existing page<0 links by remapping
	if ($page == -1) { $_GET['type'] = 'new';}
	else if ($page == -2) { $_GET['type'] = 'flagged';}
	else if ($page == -3) { $_GET['type'] = 'coursenew';}
	else if ($page == -4) { $_GET['type'] = 'threadsearch';}
	else if ($page == -5) { $_GET['type'] = 'courseflagged';}
	else if ($page == -6) { $_GET['type'] = 'postsearch';}
	$page = 1;
}
if (!empty($_GET['embed'])) {
	$flexwidth = true;
	$nologo = true;
}
$now = time();
$canviewall = (isset($teacherid) || isset($tutorid));

//$type carries the view kind (replacing the old negative-page sentinels):
//default/new/flagged are per-forum (thread.php); coursenew/courseflagged
//are course-wide (newthreads.php/flaggedthreads.php); threadsearch/
//postsearch are forums.php's search results (postsearch is not
//cache-eligible - see comment near the cache-context block below); allnew
//is index.php's cross-course "new forum posts" dashboard widget - unlike
//every other type it isn't scoped to a single course, so its threads
//carry their own cid rather than relying on the current one.
$type = $_GET['type'] ?? 'default';
if (!in_array($type, ['default','new','flagged','coursenew','courseflagged','threadsearch','postsearch','allnew'], true)) {
	$type = 'default';
}
$forumscoped = in_array($type, ['default','new','flagged'], true);
$typeqs = ($type != 'default') ? '&type='.urlencode($type) : '';

//If arriving via a Prev/Next click that stepped past the edge of the
//client-cached thread-list page (see forumthreadcache.js), resolve the
//boundary thread by pulling just that one adjacent page of thread ids,
//instead of the old per-item id-comparison queries.
if ($page >= 1 && isset($_GET['edge']) && ($_GET['edge']==='first' || $_GET['edge']==='last') && empty($_GET['thread'])) {
	require_once "threadlistfuncs.php";
	if ($forumscoped) {
		$stm = $DBH->prepare("SELECT sortby,groupsetid FROM imas_forums WHERE id=:id AND courseid=:cid");
		$stm->execute([':id'=>$forumid, ':cid'=>$cid]);
		$edgerow = $stm->fetch(PDO::FETCH_NUM);
		if ($edgerow !== false) {
			list($edgesortby, $edgegroupsetid) = $edgerow;
			$edgedofilter = false;
			$edgelimthreads = '0';
			if ($edgegroupsetid > 0) {
				if ($canviewall) {
					if (isset($_SESSION['ffilter'.$forumid]) && $_SESSION['ffilter'.$forumid] > -1) {
						$edgegroupid = $_SESSION['ffilter'.$forumid];
						$edgedofilter = true;
					}
				} else {
					$stm = $DBH->prepare("SELECT i_sg.id FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers AS i_sgm ON i_sgm.stugroupid=i_sg.id WHERE i_sgm.userid=:userid AND i_sg.groupsetid=:groupsetid");
					$stm->execute([':userid'=>$userid, ':groupsetid'=>$edgegroupsetid]);
					$edgegroupidcol = $stm->fetchColumn(0);
					$edgegroupid = ($edgegroupidcol !== false) ? $edgegroupidcol : 0;
					$edgedofilter = true;
				}
				if ($edgedofilter) {
					$stm = $DBH->prepare("SELECT id FROM imas_forum_threads WHERE (stugroupid=0 OR stugroupid=:grp) AND forumid=:forumid");
					$stm->execute([':grp'=>$edgegroupid, ':forumid'=>$forumid]);
					$edgeids = [];
					while ($r = $stm->fetch(PDO::FETCH_NUM)) { $edgeids[] = intval($r[0]); }
					$edgelimthreads = count($edgeids) ? implode(',', $edgeids) : '0';
				}
			}
			$edgefiltermode = ($type=='new') ? 'new' : (($type=='flagged') ? 'flagged' : 'none');
			$edgepageids = forumThreadIdsForPage($DBH, $forumid, $page, $listperpage, $edgesortby, $edgedofilter, $edgelimthreads, $canviewall, $now, $userid, $edgefiltermode);
			if (count($edgepageids) > 0) {
				$threadid = ($_GET['edge']==='first') ? $edgepageids[0] : $edgepageids[count($edgepageids)-1];
				$edgeseedids = $edgepageids;
				if ($edgedofilter) {
					//Carry the resolved group filter forward so the normal
					//group-resolution logic below (and the resulting cache
					//context) stays consistent with what this page was fetched with.
					$_GET['grp'] = intval($edgegroupid);
				}
			} else {
				$fallbackpage = ($_GET['edge']==='first') ? max(1, $page-1) : 1;
				header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/thread.php?cid=$cid&forum=$forumid&page=$fallbackpage$typeqs");
				exit;
			}
		}
	} else if ($type=='coursenew' || $type=='courseflagged') {
		$edgefiltermode = ($type=='coursenew') ? 'new' : 'flagged';
		$edgepageids = courseThreadIdsForPage($DBH, $cid, $page, $listperpage, $edgefiltermode, isset($teacherid), $userid, $now);
		if (count($edgepageids) > 0) {
			$edgepair = ($_GET['edge']==='first') ? $edgepageids[0] : $edgepageids[count($edgepageids)-1];
			$threadid = $edgepair[0];
			$forumid = $edgepair[1];
			$edgeseedids = $edgepageids;
		} else {
			$fallbackpage = ($_GET['edge']==='first') ? max(1, $page-1) : 1;
			$edgetarget = ($type=='coursenew') ? 'newthreads.php' : 'flaggedthreads.php';
			header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/$edgetarget?cid=$cid" . ($fallbackpage>1?"&page=$fallbackpage":''));
			exit;
		}
	}
	//threadsearch/postsearch/allnew never reach here: they always report
	//numpages:1 to the cache, so the JS never emits an edge= link.
}

if ($type=='threadsearch' || $type=='postsearch') {
	$redirecturl = $GLOBALS['basesiteurl'] . "/forums/forums.php?cid=$cid";
} else if ($type=='allnew') {
	$redirecturl = $GLOBALS['basesiteurl'] . "/index.php?";
} else if ($type=='coursenew') {
	$redirecturl = $GLOBALS['basesiteurl'] . "/forums/newthreads.php?cid=$cid";
} else if ($type=='courseflagged') {
	$redirecturl = $GLOBALS['basesiteurl'] . "/forums/flaggedthreads.php?cid=$cid";
} else {
	$redirecturl = $GLOBALS['basesiteurl'] . "/forums/thread.php?cid=$cid&forum=$forumid&page=$page$typeqs";
}
$query = "SELECT ifs.settings,ifs.replyby,ifs.defdisplay,ifs.name,ifs.points,ifs.groupsetid,igs.name AS igsname,ifs.postby,ifs.rubric,ifs.tutoredit,ifs.enddate,ifs.avail,ifs.allowlate,ifs.autoscore,ifs.courseid,ift.forumid ";
$query .= "FROM imas_forums AS ifs JOIN imas_forum_threads AS ift ON ifs.id=ift.forumid LEFT JOIN imas_stugroupset AS igs ON igs.id=ifs.groupsetid WHERE ifs.id=:id AND ift.id=:threadid AND ifs.courseid=:cid";
$stm = $DBH->prepare($query);
$stm->execute(array(':id'=>$forumid, ':threadid'=>$threadid, ':cid'=>$cid));
$row = $stm->fetch(PDO::FETCH_NUM);
if ($row === false) {
	echo "Invalid forum ID or thread ID";
	exit;
}
list($forumsettings, $replyby, $defdisplay, $forumname, $pointsposs, $groupsetid, $groupsetname, $postby, $rubric, $tutoredit, $enddate, $avail, $allowlate, $autoscore, $forumcourseid, $threadforum) = $row;

if ($forumcourseid != $cid) {
	echo "Invalid forum ID";
	exit;
} else if ($threadforum != $forumid) {
	echo "Invalid thread ID";
	exit;
}

if (isset($_GET['markunread'])) {
	$stm = $DBH->prepare("DELETE FROM imas_forum_views WHERE userid=:userid AND threadid=:threadid AND tagged=0");
	$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid));
    if ($stm->rowCount()==0) { // must be tagged
        $stm = $DBH->prepare("UPDATE imas_forum_views SET lastview=0 WHERE userid=:userid AND threadid=:threadid");
        $stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid));
    }
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

if (($postby>0 && $postby<2000000000) || ($replyby>0 && $replyby<2000000000)) {
	$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,is_lti,waivereqscore,itemtype FROM imas_exceptions WHERE assessmentid=:assessmentid AND userid=:userid AND (itemtype='F' OR itemtype='P' OR itemtype='R')");
	$stm->execute(array(':assessmentid'=>$forumid, ':userid'=>$userid));
	$exception = $stm->fetch(PDO::FETCH_ASSOC);
	if ($exception === false) {
		$exception = null;
	}
	require_once "../includes/exceptionfuncs.php";
	if (isset($studentid) && !isset($_SESSION['stuview'])) {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
	} else {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, false);
	}
	$infoline = array('replyby'=>$replyby, 'postby'=>$postby, 'enddate'=>$enddate, 'allowlate'=>$allowlate);
	list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepassP, $canuselatepassR, $postby, $replyby, $enddate) = $exceptionfuncs->getCanUseLatePassForums($exception, $infoline);
}
if (isset($studentid) && ($avail==0 || ($avail==1 && time()>$enddate))) {
	require_once "../header.php";
	echo '<p>This forum is closed.  <a href="course.php?cid='.$cid.'">Return to the course page</a></p>';
	require_once "../footer.php";
	exit;
}

$caneditscore = (isset($teacherid) || (isset($tutorid) && ($tutoredit&1)==1));
$canviewscore = (isset($teacherid) || (isset($tutorid) && $tutoredit!=2));

$allowreply = ($canviewall || (time()<$replyby));
$allowanon = (($forumsettings&1)==1);
$allowmod = ($isteacher || (($forumsettings&2)==2));
$allowdel = ($isteacher || (($forumsettings&4)==4));
$allowlikes = (($forumsettings&8)==8);
$postbeforeview = (($forumsettings&16)==16);
$haspoints =  ($pointsposs > 0);
$groupid = 0;



if ($groupsetid>0) {
	$isSectionGroups = ($groupsetname == '##autobysection##');
	if (!isset($_GET['grp'])) {
		if (!$canviewall) {
			$query = 'SELECT i_sg.id FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
			$query .= "WHERE i_sgm.userid=:userid AND i_sg.groupsetid=:groupsetid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':userid'=>$userid, ':groupsetid'=>$groupsetid));
			$groupidcol = $stm->fetchColumn(0);
			if ($groupidcol !== false) {
				$groupid = $groupidcol;
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
			if ($stm->fetch(PDO::FETCH_NUM) === false) {
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
	$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/rubric_min.js?v=101025"></script>';
	require_once "../includes/rubric.php";
}


if (isset($_GET['view'])) {
	$view = Sanitize::onlyInt($_GET['view']);
} else {
	$view = $defdisplay;  //0: expanded, 1: collapsed, 2: condensed
}

$caller = "posts";
require_once "posthandler.php";

$pagetitle = "Posts";
$placeinhead .= '<link rel="stylesheet" href="'.$staticroot.'/forums/forums.css?ver=011825" type="text/css" />';
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/posts.js?v=021326"></script>';
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/forumthreadcache.js?v=090226"></script>';
//$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
if ($caneditscore && $_SESSION['useed']!=0) {
	$useeditor = "noinit";
	$placeinhead .= '<script type="text/javascript"> initeditor("divs","fbbox",null,true);</script>';
}
require_once "../header.php";

if ($haspoints && $caneditscore && $rubric != 0) {
	$stm = $DBH->prepare("SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id=:id");
	$stm->execute(array(':id'=>$rubric));
	$row = $stm->fetch(PDO::FETCH_NUM);
	if ($row !== false) {
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
	$oktoshow = ($stm->fetch(PDO::FETCH_NUM) !== false);
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
		$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,imas_grades.score,imas_grades.feedback,imas_students.section,imas_students.id AS stuid,imas_teachers.id AS teacherid,imas_tutors.id AS tutorid "
			. "FROM imas_forum_posts JOIN imas_users ON imas_forum_posts.userid=imas_users.id "
			. "LEFT JOIN imas_students ON imas_students.userid=imas_forum_posts.userid AND imas_students.courseid=:courseid "
			. "LEFT JOIN imas_teachers ON imas_teachers.userid=imas_forum_posts.userid AND imas_teachers.courseid=:courseid "
			. "LEFT JOIN imas_tutors ON imas_tutors.userid=imas_forum_posts.userid AND imas_tutors.courseid=:courseid "
			. "LEFT JOIN imas_grades ON imas_grades.gradetype='forum' AND imas_grades.refid=imas_forum_posts.id "
			. "WHERE (imas_forum_posts.id=:id OR imas_forum_posts.threadid=:threadid) ORDER BY imas_forum_posts.id";	
	} else {
		$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,imas_students.section,imas_students.id AS stuid,imas_teachers.id AS teacherid,imas_tutors.id AS tutorid "
			. "FROM imas_forum_posts JOIN imas_users ON imas_forum_posts.userid=imas_users.id "
			. "LEFT JOIN imas_students ON imas_students.userid=imas_forum_posts.userid AND imas_students.courseid=:courseid "
			. "LEFT JOIN imas_teachers ON imas_teachers.userid=imas_forum_posts.userid AND imas_teachers.courseid=:courseid "
			. "LEFT JOIN imas_tutors ON imas_tutors.userid=imas_forum_posts.userid AND imas_tutors.courseid=:courseid "
			. "WHERE (imas_forum_posts.id=:id OR imas_forum_posts.threadid=:threadid) "
			. "ORDER BY imas_forum_posts.id";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':id'=>$threadid, ':threadid'=>$threadid));
	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$children = array(); $date = array(); $subject = array(); $re = array(); $message = array(); $posttype = array(); $likes = array(); $mylikes = array();
	$ownerid = array(); $files = array(); $points= array(); $feedback= array(); $poster= array(); $email= array(); $hasuserimg = array(); $section = array();
	$isstu = array(); $stus = []; $posttoforumaid = null; $userrole = [];
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
        if ($line['stuid'] !== null) {
            $stus[] = $line['userid'];
        }

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
			if ($line['teacherid'] !== null) {
				$userrole[$line['id']] = 'instructor';
			} else if ($line['tutorid'] !== null) {
				$userrole[$line['id']] = 'tutor';
			}
		}
		$likes[$line['id']] = array(0,0,0);

	}
    $posttoforumaidver = -1; $posttoforumqn = 0;
    if (preg_match('/Question\s+about\s+#(\d+)\s+in\s+(.*)\s*$/',$subject[$children[0][0]],$matches)) {
        $query = "SELECT ia.ver,ia.id,ias.id AS asid FROM imas_assessments AS ia LEFT JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid ";
        $query .= "AND ias.userid=:ownerid WHERE ia.courseid=:courseid AND (ia.name=:name OR ia.name=:name2) ORDER BY asid DESC";
        $stm = $DBH->prepare($query);
        $stm->execute(array(':courseid'=>$cid, ':name'=>$matches[2], ':name2'=>htmlentities($matches[2]), ':ownerid'=>intval($children[0][0])));
        $r = $stm->fetch(PDO::FETCH_ASSOC);
        if ($r !== false) {
            $posttoforumqn = intval($matches[1]);
            $posttoforumaidver = intval($r['ver']);
            $posttoforumaid = intval($r['id']);
        }
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
		require_once '../includes/filehandler.php';
	}

	//Prev/Next (and, for graded forums, the "Save Grades and View
	//Previous/Next" buttons) are populated client-side from the thread-list
	//cache (forumthreadcache.js), which thread.php/newthreads.php/
	//flaggedthreads.php/forums.php seed when rendering their thread lists.
	//Page -6 (forum post-content search) has no meaningful thread order and
	//is intentionally left uncached, so these controls just stay hidden there.

	//update view count
	$stm = $DBH->prepare("UPDATE imas_forum_posts SET views=:views WHERE id=:id");
	$stm->execute(array(':views'=>$newviews, ':id'=>$threadid));
	$stm = $DBH->prepare("UPDATE imas_forum_threads SET views=views+1 WHERE id=:id");
	$stm->execute(array(':id'=>$threadid));

	//mark as read
	$stm = $DBH->prepare("SELECT lastview,tagged FROM imas_forum_views WHERE userid=:userid AND threadid=:threadid");
	$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid));

	$viewrow = $stm->fetch(PDO::FETCH_NUM);
	if ($viewrow !== false) {
		list($lastview, $tagged) = $viewrow;
		$stm = $DBH->prepare("UPDATE imas_forum_views SET lastview=:lastview WHERE userid=:userid AND threadid=:threadid");
		$stm->execute(array(':lastview'=>$now, ':userid'=>$userid, ':threadid'=>$threadid));
	} else {
		$lastview = 0;
		$tagged = 0;
		$stm = $DBH->prepare("INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES (:userid, :threadid, :lastview) ON DUPLICATE KEY UPDATE lastview=VALUES(lastview)");
		$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid, ':lastview'=>$now));
	}
}
if (empty($_GET['embed'])) {
    echo "<div class=breadcrumb>";
    if (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0) {
        echo "$breadcrumbbase  <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }
    if ($type=='threadsearch' || $type=='postsearch') {
		echo "<a href=\"forums.php?cid=$cid\">Forum Search</a> ";
	} else if ($type=='allnew') {
		echo "<a href=\"thread.php?cid=$cid&forum=$forumid&page=1\">".Sanitize::encodeStringForDisplay($forumname)."</a> ";
	} else if ($type=='coursenew') {
		echo "<a href=\"newthreads.php?cid=$cid\">New Threads</a> ";
	} else if ($type=='courseflagged') {
		echo "<a href=\"flaggedthreads.php?cid=$cid\">Flagged Threads</a> ";
	} else {
		echo "<a href=\"thread.php?cid=$cid&forum=$forumid&page=$page$typeqs\">".Sanitize::encodeStringForDisplay($forumname)."</a> ";
	}
	echo "&gt; Posts</div>\n";
}

if (!$oktoshow) {
	echo '<p>This post is blocked. In this forum, you must post your own thread before you can read those posted by others.</p>';
} else {
	echo '<div id="headerposts" class="pagetitle"><h1>Forum: '.Sanitize::encodeStringForDisplay($forumname).'</h1></div>';
	echo "<b style=\"font-size: 120%\">"._('Post').': '. $re[$threadid] . Sanitize::encodeStringForDisplay($subject[$threadid]) . "</b><br/>\n";

	echo '<div class="stickyonscroll">';
	echo '<span id="prevth">Prev</span> ';
	echo '<span id="nextth">Next</span>';

	echo " | <a class=\"abutton\" role=\"button\" href=\"posts.php?cid=$cid&forum=$forumid&thread=$threadid&page=$page$typeqs&markunread=true\">Mark Unread</a> ";

	echo '<button type=button class="plain nopad" onclick="toggletagged('.$threadid.');" role="switch" aria-checked="'.($tagged?'true':'false').'" aria-label="'._('Tag post').'">';
	if ($tagged) {
		echo "<img class=\"pointer\" id=\"tag".$threadid."\" src=\"$staticroot/img/flagfilled.svg\" alt=\"\"/>";
	} else {
		echo "<img class=\"pointer\" id=\"tag".$threadid."\" src=\"$staticroot/img/flagempty.svg\" alt=\"\"/>";
	}
	echo '</button>';

	echo '| <button onclick="expandall()">'._('Expand All').'</button>';
	echo '<button onclick="collapseall()">'._('Collapse All').'</button> | ';
	echo '<button onclick="showall()">'._('Show All').'</button>';
	echo '<button onclick="hideall()">'._('Hide All').'</button>';
	echo '<span id="nextnew" style="display:none"> | <button onclick="shownextnew()">'._('Next New').'</button></span>';
	echo '</div>';
	echo '<div class="fixedonscrollpad"></div>';

	if ($forumscoped) {
		$navctx = [
			'cid' => $cid,
			'type' => $type,
			'scopeid' => intval($forumid),
			'page' => intval($page),
			'threadid' => intval($threadid),
			'grp' => (isset($groupsetid) && $groupsetid>0 && $groupid!=-1) ? intval($groupid) : null,
			'tagfilter' => $_SESSION['tagfilter'.$forumid] ?? '',
		];
	} else {
		//allnew spans multiple courses, so it uses a constant scopeid (0)
		//instead of the current cid - matching index.php's JS `cid` global,
		//which is always 0 there since that page never sets $cid.
		$navctx = [
			'cid' => $cid,
			'type' => $type,
			'scopeid' => ($type=='allnew') ? 0 : intval($cid),
			'page' => intval($page),
			'threadid' => intval($threadid),
			'grp' => null,
			'tagfilter' => '',
		];
	}
	if (!empty($edgeseedids)) {
		//Only possible when $page>=1 (see the edge-resolution block above).
		//This is a small, infrequent, user-triggered payload (one page's
		//worth of ids, only when a boundary is actually crossed) rather
		//than the "every list visit" case the listing pages avoid via
		//seedFromPage, so it's just sent directly.
		$seedctx = $navctx;
		if ($forumscoped) {
			//forumThreadIdsForPage() returns bare ids, all in this one forum.
			$seedctx['ids'] = array_map(function($id) use ($forumid) { return [$id, intval($forumid)]; }, array_values($edgeseedids));
		} else {
			//courseThreadIdsForPage() already returns [threadid,forumid] pairs
			//(a course-wide list spans forums).
			$seedctx['ids'] = array_values($edgeseedids);
		}
		$seedctx['threadsperpage'] = intval($listperpage);
		$seedctx['numpages'] = null;
		echo '<script>ForumThreadCache.seed(' . json_encode($seedctx) . ');</script>';
	}
	echo '<script>ForumThreadCache.applyLinks(' . json_encode($navctx) . ');</script>';

	/*if ($view==2) {
	echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&view=0\">View Expanded</a>";
} else {
echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&view=2\">View Condensed</a>";
}*/

function printchildren($base,$restricttoowner=false) {
	$curdir = rtrim(dirname(__FILE__), '/\\');
	global $DBH,$children,$date,$subject,$re,$message,$poster,$email,$forumid,$threadid,$isteacher,$cid,$userid,$ownerid,$points,$typeqs;
	global $feedback,$posttype,$lastview,$myrights,$allowreply,$allowmod,$allowdel,$allowlikes,$view,$page,$allowmsg,$userrole;
	global $haspoints,$imasroot,$postby,$replyby,$files,$CFG,$rubric,$pointsposs,$hasuserimg,$urlmode,$likes,$mylikes,$section;
	global $canviewall, $caneditscore, $canviewscore, $isstu, $posttoforumaidver, $posttoforumqn, $posttoforumaid, $staticroot;
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
		echo '<div class="postwrap';
		if ($date[$child]>$lastview) {
			echo ' newglow';
		}
		if (!empty($userrole[$child])) {
			echo ' postrole-'.$userrole[$child];
		}
		echo '" tabindex=-1>';
		echo '<div class="block flexgroup">';
		//echo '<span class=nowrap>';
		if (isset($children[$child])) {
			if ($view==1) {
				$lbl = '+';
				$img = "expand";
			} else {
				$lbl = '-';
				$img = "collapse";
			}
			echo '<button type=button class="plain nopad" aria-controls="childwrap'.$child.'" aria-expanded="'.($view==1?'false':'true').'" onClick="toggleshow(this)">';
			echo "<img class=\"expcol\" src=\"$staticroot/img/$img.gif\" alt=\"Expand/Collapse\" /></button>";
		}
		if ($hasuserimg[$child]==1) {
			echo '<button type=button class="plain nopad" onclick="togglepic(this)">';
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				echo "<img class=\"pii-image\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm{$ownerid[$child]}.jpg\" alt=\"User picture\" />";
			} else {
				echo "<img class=\"pii-image\" src=\"$imasroot/course/files/userimg_sm{$ownerid[$child]}.jpg\" alt=\"User picture\" />";
			}
			echo '</button>';
		}
		//echo '</span>';
		echo '<span style="flex-grow:1">';
		echo "<b>".$re[$child]. Sanitize::encodeStringForDisplay($subject[$child]) . "</b><br/>"._('Posted by').": ";
		//if ($isteacher && $ownerid[$child]!=0) {
		//	echo "<a href=\"mailto:{$email[$child]}\">";
		//} else if ($allowmsg && $ownerid[$child]!=0) {
		if (($canviewall || $allowmsg) && $ownerid[$child]!=0) {
			echo "<a href=\"../msgs/msglist.php?cid=$cid&add=new&to={$ownerid[$child]}\" ";
			if ($section[$child]!='') {
				echo 'title="Section: '.$section[$child].'"';
			}
			echo ">";
		}
		echo '<span class="pii-full-name">'.Sanitize::encodeStringForDisplay($poster[$child]).'</span>'; // This is the user's first and last name.
		if (($canviewall || $allowmsg) && $ownerid[$child]!=0) {
			echo "<span class=\"sr-only\">send message</span></a>";
		}
		if (!empty($userrole[$child])) {
			if ($userrole[$child] === 'instructor') {
				echo ' ('._('Instructor').')';
			} else if ($userrole[$child] === 'tutor') {
				echo ' ('._('Tutor/TA').')';
			} 
		}
		if ($isteacher && $ownerid[$child]!=0 && $ownerid[$child]!=$userid) {
			echo " <a class=\"small\" href=\"$imasroot/course/gradebook.php?cid=$cid&stu={$ownerid[$child]}\" target=\"_blank\">[GB]</a>";
            if ($posttoforumaidver > 1) { 
                // is post to forum post, ver > 1 so can make link for all students
                if ($isstu[$child]) {
                    echo " <a class=\"small\" href=\"$imasroot/assess2/gbviewassess.php?cid=$cid&uid={$ownerid[$child]}&aid={$posttoforumaid}#qwrap$posttoforumqn\" target=\"_blank\">[assignment]</a>";
                }
            } else if ($base==0 && $posttoforumaidver == 1 && preg_match('/Question\s+about\s+#(\d+)\s+in\s+(.*)\s*$/',$subject[$child],$matches)) {
                // old assess ver. Need asid
				$query = "SELECT ia.ver,ia.id,ias.id AS asid FROM imas_assessments AS ia LEFT JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid ";
				$query .= "AND ias.userid=:ownerid WHERE ia.courseid=:courseid AND (ia.name=:name OR ia.name=:name2) ORDER BY asid DESC";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$cid, ':name'=>$matches[2], ':name2'=>htmlentities($matches[2]), ':ownerid'=>intval($ownerid[$child])));
				$r = $stm->fetch(PDO::FETCH_ASSOC);
				if ($r !== false) {
					$qn = $matches[1];
					echo " <a class=\"small\" href=\"$imasroot/course/gb-viewasid.php?cid=$cid&uid={$ownerid[$child]}&asid={$r['asid']}#qwrap$qn\" target=\"_blank\">[assignment]</a>";
				}
			}
		}
		echo ', ';
		echo tzdate("D, M j, Y, g:i a",$date[$child]);

		if ($date[$child]>$lastview) {
			echo " <span class=noticetext>New</span>\n";
		}
		echo '</span>';

		// right buttons
		echo "<span class=nowrap>"; 
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

			//echo '<div class="likewrap">';
			echo '<button type=button id="likeicon'.$child.'" class="plain nopad" role="switch" aria-checked="' . ($icon=='liked'?'true':'false').'" onclick="savelike(this)">';
			echo "<img class=\"likeicon$likeclass\" src=\"$staticroot/img/$icon.png\" title=\"$likemsg\" alt=\"Like\">";
			echo '</button>';
			echo "<a href=\"#\" id=\"likecnt$child\" onclick=\"GB_show('"._('Post Likes')."','listlikes.php?cid=$cid&amp;post=$child',500,500);return false;\" aria-label=\"View likes\">".($likecnt>0?$likecnt:'').' </a> ';
			//echo '</div>';
		}
		if ($view==2) {
			echo "<button type=button class=\"shbtn\" onClick=\"toggleitem(this)\" aria-controls=\"pb$child\" aria-expanded=\"false\">"._('Show')."</button>\n";
		} else {
			echo "<button type=button class=\"shbtn\" onClick=\"toggleitem(this)\" aria-controls=\"pb$child\" aria-expanded=\"true\">"._('Hide')."</button>\n";
		}
		if ($posttype[$child]!=2 && $myrights > 5 && $allowreply) {
			$embedstr = isset($_GET['embed'])?'&embed=true':'';
			echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page$typeqs&modify=reply&replyto=$child$embedstr\" onclick=\"return checkchgstatus(0,$child)\">Reply</a> ";
		}
		if ($isteacher || ($ownerid[$child]==$userid && $allowmod && (($base==0 && time()<$postby) || ($base>0 && time()<$replyby))) || ($allowdel && $ownerid[$child]==$userid && !isset($children[$child]))) {
			echo '<span class="dropdown">';
			echo '<a tabindex=0 class="dropdown-toggle" id="dropdownMenu'.$child.'" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
			echo ' <img src="'.$staticroot.'/img/gears.svg" class="mida" alt="Options"/>';
			echo '</a>';
			echo '<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="dropdownMenu'.$child.'">';

			if ($isteacher) {
				echo "<li><a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page$typeqs&move=$child\">Move</a></li>\n";
			}
			if ($isteacher || ($ownerid[$child]==$userid && $allowmod)) {
				if (($base==0 && time()<$postby) || ($base>0 && time()<$replyby) || $isteacher) {
					echo "<li><a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page$typeqs&modify=$child\" onclick=\"return checkchgstatus(1,$child)\">Modify</a></li>\n";
				}
			}
			if ($isteacher || ($allowdel && $ownerid[$child]==$userid && !isset($children[$child]))) {
				echo "<li><a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page$typeqs&remove=$child\">Remove</a></li>\n";
			}

			echo '</ul></span>';
		}

		echo "</span>\n";
		echo '<div class="clear"></div>';
		echo "</div>\n";
		if ($view==2) {
			echo "<div class=\"blockitems hidden\" id=\"pb$child\">";
		} else {
			echo "<div class=\"blockitems\" style=\"clear:all\" id=\"pb$child\">";
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
				echo '<a href="'.getprivfileurl('ffiles/'.$child.'/'.$fl[2*$i+1]).'" target="_blank">';
				$extension = ltrim(strtolower(strrchr($fl[2*$i+1],".")),'.');
				if (isset($itemicons[$extension])) {
					echo "<img alt=\"$extension\" src=\"$staticroot/img/{$itemicons[$extension]}\" class=\"mida\" style=\"width:24px\"/> ";
				} else {
					echo "<img alt=\"doc\" src=\"$staticroot/img/doc.png\" class=\"mida\" style=\"width:24px\"/> ";
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
				echo "<label for=\"scorebox$child\">"._('Score')."</label>: <input class=scorebox type=text size=2 name=\"score[$child]\" id=\"scorebox$child\" value=\"";
				if ($points[$child]!==null) {
					echo $points[$child];
				}
				echo "\"/> ";
				if ($rubric != 0) {
					echo printrubriclink($rubric,$pointsposs,"scorebox$child", "feedback$child");
				}
				echo '<label for="feedback'.$child.'">'._('Private Feedback').'</label>: ';
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


		echo "<div class=\"clear\"></div></div></div>\n";
		echo '<div class="forumgrp'.(($view==1)?' hidden':'').'" id="childwrap'.$child.'">';
		if (isset($children[$child])) { //if has children
			printchildren($child, ($posttype[$child]==3 && !$isteacher));
		}
		echo "</div>\n";
		//}
	}
}
if ($caneditscore && $haspoints) {
	echo "<form method=post action=\"thread.php?cid=$cid&forum=$forumid&page=$page&thread=$threadid&score=true$typeqs\">";
}
printchildren(0);
if ($caneditscore && $haspoints) {
	echo '<div><button type="submit" name="save" value="save">'._('Save Grades').'</button></div>';
	if ($forumscoped) {
		//Hidden by default; forumthreadcache.js populates the hidden
		//thread-id inputs and unhides these buttons when it finds a
		//cached prev/next neighbor, avoiding a query on every pageview.
		//The submitted value is a stable action id (not the visible,
		//translated label), matched in thread.php's POST handler.
		//Excluded for course-wide types (coursenew/courseflagged/search):
		//thread.php's redirect target reuses the current $forumid, which
		//would be wrong for a "next" thread that lives in a different forum.
		echo '<input type="hidden" id="prevthinput" name="prevth" value=""/>';
		echo '<button type="submit" id="prevthbtn" name="save" value="saveprev" style="display:none">'._('Save Grades and View Previous').'</button>';
		echo '<input type="hidden" id="nextthinput" name="nextth" value=""/>';
		echo '<button type="submit" id="nextthbtn" name="save" value="savenext" style="display:none">'._('Save Grades and View Next').'</button>';
	}
	echo "</form>";
	if ($forumscoped) {
		echo '<script>ForumThreadCache.applyGradingNav(' . json_encode($navctx) . ');</script>';
	}
}
echo "<img src=\"$staticroot/img/expand.svg\" style=\"visibility:hidden\" alt=\"Expand\" />";
echo "<img src=\"$staticroot/img/collapse.svg\" style=\"visibility:hidden\" alt=\"Collapse\" />";

}
if (empty($_GET['embed'])) {
	//This previously always pointed at thread.php, which was wrong for the
	//course-wide/search types (they aren't reached via a per-forum list).
	if ($type=='threadsearch' || $type=='postsearch') {
		echo "<div class=right><a href=\"forums.php?cid=$cid\">"._('Back to Forum Search')."</a></div>\n";
	} else if ($type=='allnew') {
		echo "<div class=right><a href=\"$imasroot/index.php\">"._('Back to Home')."</a></div>\n";
	} else if ($type=='coursenew') {
		echo "<div class=right><a href=\"newthreads.php?cid=$cid\">"._('Back to New Threads')."</a></div>\n";
	} else if ($type=='courseflagged') {
		echo "<div class=right><a href=\"flaggedthreads.php?cid=$cid\">"._('Back to Flagged Threads')."</a></div>\n";
	} else {
		echo "<div class=right><a href=\"thread.php?cid=$cid&forum=$forumid&page=$page$typeqs\">"._('Back to Forum Topics')."</a></div>\n";
	}
} else {
	echo '<div class=right><button type="button" onclick="parent.GB_hide()">'._('Close').'</button></div>';
}
require_once "../footer.php";
?>
