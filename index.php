<?php

//IMathAS:  Alt main page
//(c) 2010 David Lippman

/*** master includes ***/
require("./init.php");
$now = time();

//0: classes you're teaching
//1: classes you're tutoring
//2: classes you're taking
//10: messages widget
//11: forum posts widget

//pagelayout:  array of arrays.  pagelayout[0] = fullwidth header, [1] = left bar 25%, [2] = rigth bar 75%
//[3]: 0 for newmsg note next to courses, 1 for newpost note next to courses
$stm = $DBH->prepare("SELECT homelayout,hideonpostswidget,jsondata,email FROM imas_users WHERE id=:id");
$stm->execute(array(':id'=>$userid));
list($homelayout,$hideonpostswidget,$jsondata,$myemail) = $stm->fetch(PDO::FETCH_NUM);
$jsondata = json_decode($jsondata, true);
$courseListOrder = isset($jsondata['courseListOrder'])?$jsondata['courseListOrder']:null;

if ($hideonpostswidget!='') {
	$hideonpostswidget = explode(',',$hideonpostswidget);
} else {
	$hideonpostswidget = array();
}

$pagelayout = explode('|',$homelayout);
foreach($pagelayout as $k=>$v) {
	if ($v=='') {
		$pagelayout[$k] = array();
	} else {
		$pagelayout[$k] = explode(',',$v);
	}
}
//$pagelayout = array(array(),array(0,1,2),array(10,11),array());

$shownewmsgnote = in_array(0,$pagelayout[3]);
$shownewpostnote = in_array(1,$pagelayout[3]);

$showmessagesgadget = (in_array(10,$pagelayout[1]) || in_array(10,$pagelayout[0]) || in_array(10,$pagelayout[2]));
$showpostsgadget = (in_array(11,$pagelayout[1]) || in_array(11,$pagelayout[0]) || in_array(11,$pagelayout[2]));

$twocolumn = (count($pagelayout[1])>0 && count($pagelayout[2])>0);

$placeinhead = '
  <style type="text/css">
   div.pagetitle h2 {
  	margin-top: 0px;
   }
   div.sysnotice {
   	border: 1px solid #faa;
   	background-color: #fff3f3;
   	padding: 5px;
   	margin-bottom: 5px;
   	clear: both;
   }
   #homefullwidth { clear: both;}
  </style>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js\"></script>\n";
if ($myrights>15) {
	$placeinhead .= '<script type="text/javascript">$(function() {
  var html = \'<div class="coursedd dropdown"><a role="button" tabindex=0 class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="'.$staticroot.'/img/gears.png" alt="Options"/></a>\';
  html += \'<ul role="menu" class="dropdown-menu dropdown-menu-right">\';
  $(".courselist-teach li[data-cid]:not(.coursegroup)").css("clear","both").each(function (i,el) {
  	var cid = $(el).attr("data-cid");
  	if ($(el).attr("data-isowner")=="true" && '.($myrights>39?'true':'false').') {
  		var thishtml = html + \' <li><a href="admin/forms.php?from=home&action=modify&id=\'+cid+\'">'._('Settings').'</a></li>\';
  		thishtml += \' <li><a href="#" onclick="hidefromcourselist(this,\'+cid+\',\\\'teach\\\');return false;">'._('Hide from course list').'</a></li>\';
  		thishtml += \' <li><a href="admin/addremoveteachers.php?from=home&id=\'+cid+\'">'._('Add/remove teachers').'</a></li>\';
  		thishtml += \' <li><a href="admin/transfercourse.php?from=home&id=\'+cid+\'">'._('Transfer ownership').'</a></li>\';
  		thishtml += \' <li><a href="admin/forms.php?from=home&action=delete&id=\'+cid+\'">'._('Delete').'</a></li>\';
  		thishtml += \'</ul></div>\';
  		$(el).append(thishtml);
  	} else if ($(el).attr("data-isowner")!="true") {
  		var thishtml = html + \' <li><a href="#" onclick="hidefromcourselist(this,\'+cid+\',\\\'teach\\\');return false;">'._('Hide from course list').'</a></li>\';
  		thishtml += \' <li><a href="#" onclick="removeSelfAsCoteacher(this,\'+cid+\');return false;">'._('Remove yourself as a co-teacher').'</a></li>\';
  		thishtml += \'</ul></div>\';
  		$(el).append(thishtml);
  	}
  });
  $(".dropdown-toggle").dropdown();
  });
  function dismisssysnotice(t,el) {
  	var noticediv = $(el).closest(".sysnotice");
  	$.ajax({
  		url: imasroot+"/admin/dismisssysnotice.php?n="+t
  	}).done(function(msg) {
  		if (msg=="OK") {
  			noticediv.slideUp();
  		}
  	});
  }
  </script>';
}
$nologo = true;


//check for new posts - do for each type if there are courses
$newpostscnt = array();
$postcheckcids = array();
$postcheckstucids = array();
$page_coursenames = array(0=>_('None'));
$page_newpostlist = array();

$newmsgcnt = array();
if ($showmessagesgadget) {
	$page_newmessagelist = array();
	$query = "SELECT imas_msgs.id,imas_msgs.msgfrom,imas_msgs.title,imas_msgs.senddate,imas_users.LastName,imas_users.FirstName,imas_msgs.courseid ";
	$query .= "FROM imas_msgs LEFT JOIN imas_users ON imas_users.id=imas_msgs.msgfrom WHERE ";
	$query .= "imas_msgs.msgto=:msgto AND imas_msgs.viewed=0 AND deleted<2 ";
	$query .= "ORDER BY senddate DESC ";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':msgto'=>$userid));
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($newmsgcnt[$line['courseid']])) {
			$newmsgcnt[$line['courseid']] = 1;
		} else {
			$newmsgcnt[$line['courseid']]++;
		}
		$page_newmessagelist[] = $line;
	}
} else {
	//check for new messages
	$stm = $DBH->prepare("SELECT courseid,COUNT(id) FROM imas_msgs WHERE msgto=:msgto AND viewed=0 AND deleted<2 GROUP BY courseid");
	$stm->execute(array(':msgto'=>$userid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$newmsgcnt[$row[0]] = Sanitize::onlyInt($row[1]);
	}
}

$page_studentCourseData = array();

// check to see if the user is enrolled as a student
$query = "SELECT imas_courses.name,imas_courses.id,imas_courses.startdate,imas_courses.enddate,imas_students.hidefromcourselist,";
$query .= "IF(UNIX_TIMESTAMP()<imas_courses.startdate OR UNIX_TIMESTAMP()>imas_courses.enddate,0,1) as active ";
$query .= "FROM imas_students,imas_courses ";
$query .= "WHERE imas_students.courseid=imas_courses.id AND imas_students.userid=:userid ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=2) ORDER BY active DESC,imas_courses.name";
$stm = $DBH->prepare($query);
$stm->execute(array(':userid'=>$userid));
$stuhashiddencourses = false;
if ($stm->rowCount()==0) {
	$noclass = true;
} else {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($line['hidefromcourselist']==1) {
			$stuhashiddencourses = true;
		} else {
			$noclass = false;
			if (!empty($courseListOrder) && isset($courseListOrder['take'])) {
				$page_studentCourseData[$line['id']] = $line;
			} else {
				$page_studentCourseData[] = $line;
			}
			$page_coursenames[$line['id']] = $line['name'];
			if (!in_array($line['id'],$hideonpostswidget)) {
				$postcheckstucids[] = $line['id'];
			}
		}
	}
}

$page_teacherCourseData = array();
if ($myrights>10) {
	// check to see if the user is enrolled as a teacher
	$query = "SELECT imas_courses.name,imas_courses.id,imas_courses.available,imas_courses.startdate,imas_courses.enddate,imas_courses.cleanupdate,imas_courses.lockaid,imas_courses.ownerid,imas_teachers.hidefromcourselist,";
	$query .= "IF(UNIX_TIMESTAMP()<imas_courses.startdate OR UNIX_TIMESTAMP()>imas_courses.enddate,0,1) as active ";
	$query .= "FROM imas_teachers,imas_courses ";
	$query .= "WHERE imas_teachers.courseid=imas_courses.id AND imas_teachers.userid=:userid ";
    $query .= "AND (imas_courses.available=0 OR imas_courses.available=1";
    if ($myrights > 20) {
        $query .= " OR (imas_courses.available=4 AND imas_courses.ownerid=:ownerid)";
    }
    $query .= ") ORDER BY active DESC,imas_courses.name";
    $stm = $DBH->prepare($query);
    if ($myrights > 20) {
        $stm->execute(array(':userid'=>$userid, ':ownerid'=>$userid));
    } else {
        $stm->execute(array(':userid'=>$userid));
    }
	$teachhashiddencourses = false;
	if ($stm->rowCount()==0) {
		$noclass = true;
	} else {
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($line['hidefromcourselist']==1 || $line['available'] == 4) {
                $teachhashiddencourses = true;
			} else {
				$noclass = false;
				if (!empty($courseListOrder) && isset($courseListOrder['teach'])) {
					$page_teacherCourseData[$line['id']] = $line;
				} else {
					$page_teacherCourseData[] = $line;
				}

				$page_coursenames[$line['id']] = $line['name'];
				if (!in_array($line['id'],$hideonpostswidget)) {
					$postcheckcids[] = $line['id'];
				}
			}
		}
	}
}

$page_tutorCourseData = array();
// check to see if the user is enrolled as a tutor
$query = "SELECT imas_courses.name,imas_courses.id,imas_courses.available,imas_courses.startdate,imas_courses.enddate,imas_courses.lockaid,imas_tutors.hidefromcourselist,";
$query .= "IF(UNIX_TIMESTAMP()<imas_courses.startdate OR UNIX_TIMESTAMP()>imas_courses.enddate,0,1) as active ";
$query .= "FROM imas_tutors,imas_courses ";
$query .= "WHERE imas_tutors.courseid=imas_courses.id AND imas_tutors.userid=:userid ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ORDER BY active DESC,imas_courses.name";
$stm = $DBH->prepare($query);
$stm->execute(array(':userid'=>$userid));
$tutorhashiddencourses = false;
if ($stm->rowCount()==0) {
	$noclass = true;
} else {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($line['hidefromcourselist']==1) {
			$tutorhashiddencourses = true;
		} else {
			$noclass = false;
			if (!empty($courseListOrder) && isset($courseListOrder['tutor'])) {
				$page_tutorCourseData[$line['id']] = $line;
			} else {
				$page_tutorCourseData[] = $line;
			}
			$page_coursenames[$line['id']] = $line['name'];
			if (!in_array($line['id'],$hideonpostswidget)) {
				$postcheckstucids[] = $line['id'];
			}
		}
	}
}

//get new posts
//check for new posts in courses being taught.
//TODO:  changed since wasn't being hit before. Check it works right.
//  is the cost of calling this more correct version more than the benefit of
//  getting notified of group posts correctly?
$postcidlist = implode(',',$postcheckcids);
$postthreads = array();
if ($showpostsgadget && count($postcheckcids)>0) {
	/*$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,imas_forums.courseid FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid IN ($postcidlist) AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ORDER BY imas_forum_threads.lastposttime DESC";*/
	$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,imas_forums.courseid FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
	$query .= "AND imas_forums.courseid IN ($postcidlist) ";  //is int's from DB - safe
	$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid ";
	$query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) AND imas_forum_threads.lastposttime<:now ";
	$query .= "AND imas_forum_threads.lastposttime>:old ORDER BY imas_forum_threads.lastposttime DESC";

	$stm = $DBH->prepare($query);
	$stm->execute(array(':userid'=>$userid, ':now'=>$now, ':old'=>$now - 365*24*60*60));
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($newpostcnt[$line['courseid']])) {
			$newpostcnt[$line['courseid']] = 1;
		} else {
			$newpostcnt[$line['courseid']]++;
		}
		if ($newpostcnt[$line['courseid']]<10) {
			$page_newpostlist[] = $line;
			$postthreads[] = $line['threadid'];
		}
	}
} else if (count($postcheckcids)>0) {
	/*$query = "SELECT courseid,count(*) FROM ";
	$query .= "(SELECT imas_forums.courseid,imas_forum_threads.id FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid IN ";
	$query .= "($postcidlist) AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))) AS newitems ";
	$query .= "GROUP BY courseid";*/

	$now = time();
	$query = "SELECT imas_forums.courseid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
	$query .= "AND imas_forums.courseid IN ($postcidlist) ";    //is int's from DB - safe
	$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid ";
	$query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) AND imas_forum_threads.lastposttime<:now ";
	//this is not consistent with above...
	//$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:useridB)) ";
	$query .= "GROUP BY imas_forums.courseid";
	$stm2 = $DBH->prepare($query);
	$stm2->execute(array(':userid'=>$userid, ':now'=>$now));
	while ($row = $stm2->fetch(PDO::FETCH_NUM)) {
		$newpostcnt[$row[0]] = $row[1];
	}
}
//check for new posts in courses being taken
$poststucidlist = implode(',',$postcheckstucids);
if ($showpostsgadget && count($postcheckstucids)>0) {
	/*$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,imas_forums.courseid FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid IN ($postcidlist) AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ORDER BY imas_forum_threads.lastposttime DESC";*/
	$now = time();
	$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,imas_forums.courseid FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
	$query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
	$query .= "AND imas_forums.courseid IN ($poststucidlist) "; //is int's from DB - safe
	$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid ";
	$query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
	$query .= "AND imas_forum_threads.lastposttime<:now AND imas_forum_threads.lastposttime>:old ";
	$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:useridB)) ";
	$query .= "ORDER BY imas_forum_threads.lastposttime DESC";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':userid'=>$userid, ':useridB'=>$userid, ':now'=>$now, ':old'=>$now - 365*24*60*60));
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($newpostcnt[$line['courseid']])) {
			$newpostcnt[$line['courseid']] = 1;
		} else {
			$newpostcnt[$line['courseid']]++;
		}
		if ($newpostcnt[$line['courseid']]<10) {
			$page_newpostlist[] = $line;
			$postthreads[] = $line['threadid'];
		}
	}
} else if (count($postcheckstucids)>0) {
	$now = time();
	/*
	$query = "SELECT courseid,count(*) FROM ";
	$query .= "(SELECT imas_forums.courseid,imas_forum_threads.id FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid IN ";
	$query .= "($postcidlist) AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))) AS newitems ";
	$query .= "GROUP BY courseid";*/
	$query = "SELECT imas_forums.courseid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
	$query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
	$query .= "AND imas_forums.courseid IN ($poststucidlist) "; //int's from DB - safe
	$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid ";
	$query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) AND imas_forum_threads.lastposttime<:now ";
	$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:useridB)) ";
	$query .= "GROUP BY imas_forums.courseid";
	$stm2 = $DBH->prepare($query);
	$stm2->execute(array(':userid'=>$userid, ':useridB'=>$userid, ':now'=>$now));
	while ($row = $stm2->fetch(PDO::FETCH_NUM)) {
		$newpostcnt[$row[0]] = $row[1];
	}
}

if ($showmessagesgadget) {
	$topullcoursenames = array_values(array_diff(array_keys($newmsgcnt), array_keys($page_coursenames)));
	if (count($topullcoursenames)>0) {
		$ph = Sanitize::generateQueryPlaceholders($topullcoursenames);
		$stm = $DBH->prepare("SELECT id,name FROM imas_courses WHERE id IN ($ph)");
		$stm->execute($topullcoursenames);
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$page_coursenames[$row[0]] = $row[1];
		}
	}
}

if ($myrights==100) {
	$brokencnt = array();
	$stm = $DBH->query("SELECT userights,COUNT(id) FROM imas_questionset WHERE broken=1 AND deleted=0 GROUP BY userights");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$brokencnt[$row[0]] = $row[1];
	}
}
if (!empty($CFG['logquestionerrors']) && $myrights >= 20) {
    $stm = $DBH->prepare('SELECT count(DISTINCT iqe.qsetid) FROM imas_questionerrors AS iqe JOIN imas_questionset AS iqs ON iqe.qsetid=iqs.id WHERE iqs.ownerid=?');
    $stm->execute([$userid]);
    $qerrcnt = $stm->fetchColumn(0);
}
/*** done pulling stuff.  Time to display something ***/
require("header.php");
$msgtotal = array_sum($newmsgcnt);
if (!isset($CFG['GEN']['homelinkbox'])) {
	echo '<div class="floatright" id="homelinkbox" role="navigation" aria-label="'._('Site tools').'">';
	if (!isset($CFG['GEN']['hidedefindexmenu'])) {
		if ($myrights>5) {
			echo "<a href=\"forms.php?action=chguserinfo\">", _('Change User Info'), "</a> | \n";
			echo "<a href=\"forms.php?action=chgpwd\">", _('Change Password'), "</a> | \n";
		}
		echo '<a href="actions.php?action=logout">', _('Log Out'), '</a><br/>';
	}
	echo '<a href="msgs/msglist.php?cid=0">', _('Messages'), '</a>';
	if ($msgtotal>0) {
		echo ' <a href="msgs/newmsglist.php?cid=0" class="noticetext">', sprintf(_('New (%d)'), Sanitize::onlyFloat($msgtotal)), '</a>';
	}
	if ($myrights > 9) {
		echo " | <a href=\"help.php?section=usingimas\">", _('Help'), "</a>\n";
	}
	if ($myrights >=75) {
		echo '<br/><a href="admin/admin2.php">'._('Admin Page').'</a>';
	} else if (($myspecialrights&4)==4) {
		echo '<br/><a href="admin/listdiag.php">'._('Diagnostics').'</a>';
	} else if (!empty($userHasAdminMFA)) {
		echo '<br/><a href="admin/forms.php?action=entermfa">'._('Enable Admin Features').'</a>';
	}
	if ((($myspecialrights&32)==32) || $myrights == 100) {
		if ($myrights >=75 || ($myspecialrights&4)==4) {
			echo ' | ';
		} else {
			echo '<br/>';
		}
		echo '<a href="admin/userreports.php">'._('User Reports').'</a>';
	}
	echo '</div>';
}

echo '<div class="pagetitle" id="headerhome" role="banner"><h1>';
if (isset($CFG['GEN']['hometitle'])) {
	echo $CFG['GEN']['hometitle'];
} else {
	echo _('Welcome to'), " $installname, ";
    printf('<span class="pii-full-name">%s</span>', Sanitize::encodeStringForDisplay($userfullname));
}
echo '</h1>';
echo '</div>';
if (isset($_SESSION['emulateuseroriginaluser'])) {
	echo '<p>Currenting emulating this user.  <a href="util/utils.php?unemulateuser=true">Stop emulating user</a></p>';
}

if ($myrights==100 && count($brokencnt)>0) {
	echo '<div><span class="noticetext">'.Sanitize::onlyFloat(array_sum($brokencnt)).'</span> questions, '.(array_sum($brokencnt)-$brokencnt[0]).' public, reported broken systemwide</div>';
}
if (!empty($CFG['logquestionerrors']) && $myrights>=20 && $qerrcnt>0) {
    echo '<div><span class="noticetext">'.Sanitize::onlyInt($qerrcnt).'</span> of your questions have logged errors. <a target="_blank" href="util/questionerrors.php">View errors</a></div>';
}
if ($myrights<75 && ($myspecialrights&(16+32))!=0) {
	echo '<div>';
	if (($myspecialrights&(16+32))!=0) {
		echo '<a href="admin/forms.php?from=home&action=newadmin">'._('Add New User').'</a> ';
	}
	echo '</div>';
}
if ($myrights==100 || ($myspecialrights&64)!=0) {
	$stm = $DBH->query("SELECT status,count(userid) FROM imas_instr_acct_reqs WHERE status<10 GROUP BY status ORDER BY status");
	$newreqs = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$newreqs[$row[0]] = $row[1];
	}
	if (count($newreqs)>0) {
		echo '<div> There are <span class=noticetext>'.(isset($newreqs[0])?$newreqs[0]:0).'</span> new account requests';
		unset($newreqs[0]); // don't count below
        if (count($newreqs)>0) {
			echo ' and <span class=noticetext>'.array_sum($newreqs).'</span> pending requests';
		}
		echo '. <a href="admin/approvepending2.php?from=home">'._('Approve Pending Instructor Accounts').'</a>';
	}
}
if (isset($tzname) && isset($_SESSION['logintzname']) && $tzname!=$_SESSION['logintzname']) {
	echo '<div class="sysnotice">'.sprintf(_('Notice: You have requested that times be displayed based on the <b>%s</b> time zone, and your computer is reporting you are currently in a different time zone. Be aware that times will display based on the %s timezone as requested, not your local time'),$tzname,$tzname).'</div>';
}
if (substr($myemail,0,7)==='BOUNCED') {
	echo '<div class="sysnotice">';
	echo _('We have been unable to send emails to the address you have listed. Please update the email address in your profile.').' ';
	echo '<a href="forms.php?action=chguserinfo">'._('Edit Now').'</a>.';
	echo '</div>';
}

for ($i=0; $i<3; $i++) {
	if ($i==0) {
		echo '<div id="homefullwidth">';
	}
	if ($twocolumn) {
		if ($i==1) {
			echo '<div id="leftcolumn">';
		} else if ($i==2) {
			echo '<div id="rightcolumn">';
		}
	}
	for ($j=0; $j<count($pagelayout[$i]); $j++) {
		switch ($pagelayout[$i][$j]) {
			case 0:
				if ($myrights>10) {
					printCourses($page_teacherCourseData,_('Courses you\'re teaching'),'teach',$teachhashiddencourses);
				}
				break;
			case 1:
				printCourses($page_tutorCourseData,_('Courses you\'re tutoring'),'tutor',$tutorhashiddencourses);
				break;
			case 2:
				printCourses($page_studentCourseData,_('Courses you\'re taking'),'take',$stuhashiddencourses);
				break;
			case 10:
				printMessagesGadget();
				break;
			case 11:
				printPostsGadget();
				break;
		}
	}
	if ($i==2 || $twocolumn) {
		echo '</div>';
	}
}

require('footer.php');

function printCourses($data,$title,$type=null,$hashiddencourses=false) {
	global $myrights, $shownewmsgnote, $shownewpostnote, $imasroot, $userid, $username, $courseListOrder, $myspecialrights;
	if (count($data)==0 && $type=='tutor' && !$hashiddencourses) {return;}

	echo '<div role="navigation" aria-label="'.$title.'">';
	echo '<div class="block"><h2>'.$title.'</h2></div>';
	echo '<div class="blockitems"><ul class="courselist courselist-'.$type.'">';
	if (!empty($courseListOrder) && isset($courseListOrder[$type])) {
		$printed = array();
		printCourseOrder($courseListOrder[$type], $data, $type, $printed);
		$notlisted = array_diff(array_keys($data), $printed);
		foreach ($notlisted as $i) {
			if (isset($data[$i])) {
				printCourseLine($data[$i], $type);
			}
		}
	} else {
		for ($i=0; $i<count($data); $i++) {
			printCourseLine($data[$i], $type);
		}
	}
	if ($type=='teach' && $myrights>39 && count($data)==0) {
		echo '<li>', _('To add a course, click the button below'), '</li>';
	} else if ($type=='teach' && $myrights<13 && $myrights>10 && count($data)==0) {
		echo '<li>', _('Your instructor account has not been approved yet. Please be patient.'), '</li>';
	}
	echo '</ul>';

	if ($type=='teach') {
		$hasCleanup = false;
		foreach ($data as $v) {
			if ($v['cleanupdate']>1) {
				$hasCleanup = true;
				break;
			}
		}
		if ($hasCleanup) {
			echo '<p class="small info"><span class="warn">**</span> ';
			echo _('course is scheduled for cleanup').'</p>';
		}
	}
    if ($type=='take' && substr($username, 0, 4) != 'lti-') {
        echo '<div class="center"><a class="abutton" href="forms.php?action=enroll">', _('Enroll in a New Class'), '</a></div>';
    } else if ($type=='teach' && $myrights>39) {
        echo '<div class="center"><a class="abutton" href="admin/addcourse.php">', _('Add New Course'), '</a></div>';
    }

    echo '<div class="center">';
    if (count($data)>0) {
        echo '<a class="small" href="admin/modcourseorder.php?type='.$type.'">',_('Change Course Order'),'</a><br/>';
    }
    //echo '</div><div class="center">';
    echo '<a id="unhidelink'.$type.'" '.($hashiddencourses?'':'style="display:none"').' class="small" href="admin/unhidefromcourselist.php?type='.$type.'">',_('View hidden courses'),'</a> ';
    if ($type=='teach' && count($data)>0) {
        echo '<br/><a class="small" href="admin/forms.php?action=findstudent&from=home">',_('Find Student'),'</a> ';
    }
    echo '</div>';
    if ($type=='teach' && ($myrights>=75 || ($myspecialrights&4)==4)) {
        echo '<div class="center"><a class="abutton" href="admin/admin2.php">', _('Admin Page'), '</a></div>';
    }

    echo '</div>';
    echo '</div>';
}

function printCourseOrder($order, $data, $type, &$printed) {
	foreach ($order as $item) {
		if (is_array($item)) {
			echo '<li class="coursegroup"><b>'.Sanitize::encodeStringForDisplay($item['name']).'</b>';
			echo '<ul class="courselist">';
			printCourseOrder($item['courses'], $data, $type, $printed);
			echo '</ul></li>';
		} else if (isset($data[$item])) {
			printCourseLine($data[$item], $type);
			$printed[] = $item;
		}
	}
}

function printCourseLine($data, $type=null) {
	global $shownewmsgnote, $shownewpostnote, $userid;
	global $myrights, $newmsgcnt, $newpostcnt;
	$now = time();

	echo '<li';
	if ($type=='teach' && $myrights>19) {
		echo ' data-isowner="'.($data['ownerid']==$userid?'true':'false').'"';
		echo ' data-cid="'.$data['id'].'"';
	}
	echo '>';
	if ($type!='take' || $now>$data['startdate']) {
		echo '<a href="course/course.php?folder=0&cid='.$data['id'].'">';
		echo Sanitize::encodeStringForDisplay($data['name']).'</a>';
	} else {
		echo Sanitize::encodeStringForDisplay($data['name']);
	}
	if ($type=='teach' && $data['cleanupdate']>1) {
		echo ' <span style="color:orange;" title="'._('course is scheduled for cleanup').'">**</span>';
	}
	if (isset($data['available']) && (($data['available']&1)==1)) {
		echo ' <em style="color:green;" class=small>', _('Unavailable'), '</em>';
	}
	if (isset($data['startdate']) && $now<$data['startdate']) {
		echo ' <em style="color:green;" class=small>';
		echo _('Starts ').tzdate('m/d/Y', $data['startdate']);
		echo '</em>';
	} else if (isset($data['enddate']) && $now>$data['enddate']) {
		echo ' <em style="color:green;" class=small>';
		echo _('Ended ').tzdate('m/d/Y', $data['enddate']);
		echo '</em>';
	}

	if (isset($data['lockaid']) && $data['lockaid']>0) {
		echo ' <em style="color:green;">', _('Lockdown'), '</em>';
	}
	if ($shownewmsgnote && isset($newmsgcnt[$data['id']]) && $newmsgcnt[$data['id']]>0) {
		echo ' <a class="noticetext" href="msgs/msglist.php?page=-1&cid='.$data['id'].'">', sprintf(_('Messages (%d)'), $newmsgcnt[$data['id']]), '</a>';
	}
	if ($shownewpostnote && isset($newpostcnt[$data['id']]) && $newpostcnt[$data['id']]>0) {
		printf(' <a class="noticetext" href="forums/newthreads.php?from=home&cid=%d">%s</a>',$data['id'],
			_('Posts ('.Sanitize::onlyInt($newpostcnt[$data['id']]).')'));
		// echo ' <a class="noticetext" href="forums/newthreads.php?from=home&cid='.Sanitize::encodeUrlParam($data['id']).'">', sprintf(_('Posts (%d)'), $newpostcnt[$data['id']]), '</a>';
	}
	if ($type != 'teach' || ($data['ownerid']==$userid && $myrights<40) || $myrights<20) {
		echo '<div class="delx"><a href="#" onclick="return hidefromcourselist(this,'.$data['id'].',\''.$type.'\');" title="'._("Hide from course list").'" aria-label="'._("Hide from course list").'">x</a></div>';
	}
	echo '</li>';

}

function printMessagesGadget() {
	global $page_newmessagelist, $page_coursenames;
	echo '<div role="complementary" aria-label="'._('New messages').'">';
	echo '<div class="block"><h2>', _('New messages'), '</h2></div>';
	echo '<div class="blockitems">';
	if (count($page_newmessagelist)==0) {
		echo '<p>', _('No new messages'), '</p>';
		echo '</div></div>';
		return;
	}
	echo '<table class="gb" id="newmsglist"><thead><tr><th>', _('Message'), '</th><th>', _('From'), '</th><th>', _('Course'), '</th><th>' ,_('Sent'), '</th></tr></thead>';
	echo '<tbody>';
	foreach ($page_newmessagelist as $line) {
		echo '<tr>';
		if (trim($line['title'])=='') {
			$line['title'] = '['._('No Subject').']';
		}
		$n = 0;
		while (strpos($line['title'],'Re: ')===0) {
			$line['title'] = substr($line['title'],4);
			$n++;
		}
		if ($n==1) {
			$line['title'] = 'Re: '.Sanitize::encodeStringForDisplay($line['title']);
		} else if ($n>1) {
			$line['title'] = "Re<sup>$n</sup>: ".Sanitize::encodeStringForDisplay($line['title']);
		}
		echo "<td><a href=\"msgs/viewmsg.php?cid={$line['courseid']}&type=new&msgid={$line['id']}\">";
		echo $line['title'];
		echo '</a></td>';
		if ($line['LastName']==null) {
			if ($line['msgfrom']==0) {
				$line['fullname'] = _("[System Message]");
			} else {
				$line['fullname'] = _("[Deleted]");
			}
		} else {
			$line['fullname'] = sprintf('%s, %s', $line['LastName'], $line['FirstName']);
		}
		echo '<td><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($line['fullname']).'</span></td>';
		echo '<td>'.Sanitize::encodeStringForDisplay($page_coursenames[$line['courseid']] ?? '').'</td>';
		echo '<td>'.tzdate("D n/j/y, g:i a",$line['senddate']).'</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '<script type="text/javascript">initSortTable("newmsglist",Array("S","S","S","D"),false);</script>';
	echo '</div>';
	echo '</div>';

}
function printPostsGadget() {
	global $DBH,$page_newpostlist, $page_coursenames, $postthreads,$imasroot,$staticroot;
	echo '<div role="complementary" aria-label="'._('New forum posts').'">';
	echo '<div class="block">';
	//echo "<span class=\"floatright\"><a href=\"#\" onclick=\"GB_show('Forum Widget Settings','$imasroot/forms.php?action=forumwidgetsettings&greybox=true',800,'auto')\" title=\"Forum Widget Settings\"><img style=\"vertical-align:top\" src=\"$imasroot/img/gears.png\"/></a></span>";
	echo "<span class=\"floatright\"><a href=\"forms.php?action=forumwidgetsettings\"><img style=\"vertical-align:top\" src=\"$staticroot/img/gears.png\" alt=\"Settings\"/></a></span>";

	echo '<h2>', _('New forum posts'), '</h2></div>';
	echo '<div class="blockitems">';
	if (count($page_newpostlist)==0) {
		echo '<p>', _('No new posts'), '</p>';
		echo '</div></div>';
		return;
	}
	$threadlist = implode(',',$postthreads);
	$threaddata = array();
	$query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName FROM imas_forum_posts,imas_users ";
	$query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.id IN ($threadlist)";  //int vals from DB - safe
	$stm = $DBH->query($query);
	while ($tline = $stm->fetch(PDO::FETCH_ASSOC)) {
		$threaddata[$tline['id']] = $tline;
	}

	echo '<table class="gb" id="newpostlist"><thead><tr><th>', _('Thread'), '</th><th>', _('Started By'), '</th><th>', _('Course'), '</th><th>', _('Last Post'), '</th></tr></thead>';
	echo '<tbody>';
	foreach ($page_newpostlist as $line) {
		echo '<tr>';
		$subject = $threaddata[$line['threadid']]['subject'];
		if (trim($subject)=='') {
			$subject = '['._('No Subject').']';
		}
		$n = 0;
		while (strpos($subject,'Re: ')===0) {
			$subject = substr($subject,4);
			$n++;
		}
		if ($n==1) {
			$subject = 'Re: '.Sanitize::encodeStringForDisplay($subject);
		} else if ($n>1) {
			$subject = "Re<sup>$n</sup>: ".Sanitize::encodeStringForDisplay($subject);
		}
		echo "<td><a href=\"forums/posts.php?page=-3&cid={$line['courseid']}&forum={$line['id']}&thread={$line['threadid']}\">";
		echo $subject;
		echo '</a></td>';
		if ($threaddata[$line['threadid']]['isanon']==1) {
			echo '<td>', _('Anonymous'), '</td>';
		} else {
			echo '<td><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($threaddata[$line['threadid']]['LastName']).', '.Sanitize::encodeStringForDisplay($threaddata[$line['threadid']]['FirstName']).'</span></td>';
		}
		echo '<td>'.Sanitize::encodeStringForDisplay($page_coursenames[$line['courseid']]).'</td>';
		echo '<td>'.tzdate("D n/j/y, g:i a",$line['lastposttime']).'</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '<script type="text/javascript">initSortTable("newpostlist",Array("S","S","S","D"),false);</script>';

	echo '</div>';
	echo '</div>';
}
