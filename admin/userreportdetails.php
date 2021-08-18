<?php
//IMathAS - User Details page
//(c) 2017 David Lippman

require("../init.php");
require("../includes/newusercommon.php");

function getRoleNameByRights($rights) {
  switch ($rights) {
    case 5: return _("Guest"); break;
    case 10: return _("Student"); break;
    case 12: return _("Pending"); break;
    case 15: return _("Tutor/TA/Proctor"); break;
    case 20: return _("Teacher"); break;
    case 40: return _("LimCourseCreator"); break;
    case 75: return _("GroupAdmin"); break;
    case 100: return _("Admin"); break;
  }
}

$overwriteBody = 0;
$body = "";
$old = 30; //old courses: no activity in __ days.

$curBreadcrumb = $breadcrumbbase .' <a href="userreports.php">'._('User Reports').'</a> &gt; ';
$curBreadcrumb .= _('User Detail');

if ($myrights < 100 && (($myspecialrights&32)!=32)) {
 	$overwriteBody = 1;
	$body = "You don't have authority to view this page.";
} else if (empty($_GET['id'])) {
  $overwriteBody = 1;
  $body = 'No id provided';
} else {
  $now = time();

  //pull basic user info
  $uid = Sanitize::onlyInt($_GET['id']);
  $query = "SELECT iu.SID,iu.FirstName,iu.LastName,iu.email,iu.rights,iu.lastaccess,iu.groupid,ig.name AS gname,iu.specialrights,ig.parent ";
  $query .= "FROM imas_users AS iu LEFT JOIN imas_groups AS ig ON iu.groupid=ig.id WHERE iu.id=:id";
  $stm = $DBH->prepare($query);
  $stm->execute(array(':id'=>$uid));

  $userinfo = $stm->fetch(PDO::FETCH_ASSOC);
  $userinfo['role'] = getRoleNameByRights($userinfo['rights']);
  $userinfo['lastaccess'] = ($userinfo['lastaccess']>0) ? date("n/j/y g:i a",$userinfo['lastaccess']) : "never";
  if ($userinfo['parent']>0) {
    $group_stm = $DBH->prepare('SELECT name FROM imas_groups WHERE id=:id');
    $group_stm->execute(array(':id'=>$userinfo['parent']));
    $r = $group_stm->fetch(PDO::FETCH_NUM);
    $userinfo['parentgroup'] = $r[0];
  }

  if ($userinfo===false) {
    $overwriteBody = 1;
    $body = 'Invalid id provided';
  } else {
  	$errors = '';
	if (isset($_POST['pw1'])) {
		  $errors = checkNewUserValidation(array('pw1'));
		  if ($errors == '') {
			if (isset($CFG['GEN']['newpasswords'])) {
				require_once("../includes/password.php");
				$newpw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
			} else {
				$newpw = md5($_POST['pw1']);
			}
			$stm = $DBH->prepare("UPDATE imas_users SET password=:pw,forcepwreset=1 WHERE id=:uid");
			$stm->execute(array(':pw'=>$newpw, ':uid'=>$uid));
			$errors = _('Password Reset');
		  }
	}

	//pull template courses
	$stm = $DBH->query("SELECT id,name FROM imas_courses WHERE (istemplate&1)=1 OR (istemplate&2)=2 ORDER BY name");
	$templates = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$templates[$row[0]] = $row[1];
	}
	$templateids = array_keys($templates);

    //courses teaching list
    $query = "SELECT imas_courses.id,imas_courses.ownerid,imas_courses.name,imas_courses.available,";
    $query .= "imas_courses.lockaid,imas_courses.copyrights,imas_users.FirstName,imas_users.LastName,";
    $query .= "imas_users.groupid,imas_teachers.hidefromcourselist,imas_courses.ancestors,";
    $query .= "imas_courses.startdate,imas_courses.enddate ";
    $query .= "FROM imas_courses JOIN imas_users ON imas_courses.ownerid=imas_users.id ";
    $query .= "JOIN imas_teachers ON imas_teachers.courseid=imas_courses.id WHERE imas_teachers.userid=:uid ";
    $query .= " ORDER BY imas_courses.name";
    $stm = $DBH->prepare($query);
  	$stm->execute(array(':uid'=>$uid));
    $courses_teaching = array();
    $totalactivecourses = 0;
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $newrow = array();
      $newrow['name'] = $row['name'];
      $newrow['id'] = $row['id'];
      $newrow['available'] = $row['available'];
      $newrow['owner'] = ($row['ownerid']!=$uid)?$row['LastName'].', '.$row['FirstName']:'';
      $newrow['canedit'] = ($row['ownerid']==$userid || $myrights==100 || ($myrights>=75 && $row['groupid']==$groupid));
      $newrow['deleted'] = ($row['available']==4);
      $newrow['hidden'] = ($row['hidefromcourselist']==1);
      $newrow['showlink'] = ($row['copyrights']==2 || ($row['groupid']==$groupid && $row['copyrights']==1) || $myrights == 100);
      if ($row['available']==4) {
        $newrow['status'] = array(_('Deleted'));
      } else {
        $newrow['status'] = array(($row['available']==0)?_('Available to students'):_('Hidden from students'));
        if ($row['lockaid']>0) {
          $newrow['status'][] = _('Locked for Assessment');
        }
        if ($row['hidefromcourselist']==1) {
          $newrow['status'][] = '<span class="hocp">'._("Hidden on User's Home Page").'</span>';
        }
      }
      if ($row['enddate']<2000000000) { //if we have an enddate, use it
      	  if ($now<$row['startdate'] && $now>$row['enddate']) {
      	  	  $newrow['active'] = 1;
      	  	  $totalactivecourses++;
      	  } else {
      	  	  $newrow['active'] = 0;
      	  }
      } else {
      	  $newrow['active'] = -1;
      }
      $newrow['stucnt'] = 0;
      $newrow['lastactivity'] = 0;
      $newrow['template'] = '';
      $templatematches = array_values(array_intersect(explode(',', $row['ancestors']), $templateids));
      if (count($templatematches)>0) {
      	  $newrow['template'] = $templates[$templatematches[count($templatematches)-1]];
      }
      $courses_teaching[$row['id']] = $newrow;
    }

    $totalactivestudirect = 0;
    $totalactivestuLTI = 0;
    if (count($courses_teaching)>0) {
    	//pull LTI/not
    	$courseids = array_keys($courses_teaching);
    	$ph = Sanitize::generateQueryPlaceholders($courseids);
    	$stm = $DBH->prepare("SELECT courseid FROM imas_lti_courses WHERE courseid IN ($ph)");
    	$stm->execute($courseids);
    	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    		$courses_teaching[$row['courseid']]['isLTI'] = 1;
    	}

    	//pull last student access and stucnt
    	$query = "SELECT ic.id,COUNT(istu.id) AS stucnt,MAX(istu.lastaccess) AS lastactivity ";
    	$query .= "FROM imas_courses AS ic JOIN imas_students AS istu ";
    	$query .= "ON ic.id=istu.courseid WHERE ic.id IN ($ph) GROUP BY ic.id";
    	$stm = $DBH->prepare($query);
    	$stm->execute($courseids);
    	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    		if ($courses_teaching[$row['id']]['active']==-1) {//don't know if it's active
    			if ($row['lastactivity']<$now-$old*24*60*60) {
    				$courses_teaching[$row['id']]['active'] = 0;
    			} else {
    				$courses_teaching[$row['id']]['active'] = 1;
    				$totalactivecourses++;
    			}
    		}
    		if ($courses_teaching[$row['id']]['active']==1) { //if active
    			if (!empty($courses_teaching[$row['id']]['isLTI'])) {
    				$totalactivestuLTI += $row['stucnt'];
    			} else {
    				$totalactivestudirect += $row['stucnt'];
    			}
    		}
    		$courses_teaching[$row['id']]['lastactivity'] = $row['lastactivity'];
    		$courses_teaching[$row['id']]['stucnt'] = $row['stucnt'];
    	}
    }

    function sortteaching($a,$b) {
    	if ($a['active']==$b['active']) {
    		return ($a['id']-$b['id']);
    	} else {
    		return ($b['active']-$a['active']);
    	}
    }
    uasort($courses_teaching, 'sortteaching');

    //pull courses tutoring
    $query = "SELECT imas_courses.id,imas_courses.ownerid,imas_courses.name,imas_courses.available,imas_courses.lockaid,imas_users.FirstName,imas_users.LastName,imas_users.groupid,imas_tutors.hidefromcourselist ";
    $query .= "FROM imas_courses JOIN imas_users ON imas_courses.ownerid=imas_users.id ";
    $query .= "JOIN imas_tutors ON imas_tutors.courseid=imas_courses.id WHERE imas_tutors.userid=:uid AND imas_courses.available<4 ";
    $query .= "ORDER BY imas_courses.name";
    $stm = $DBH->prepare($query);
    $stm->execute(array(':uid'=>$uid));
    $courses_tutoring = array();
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $newrow = array();
      $newrow['name'] = $row['name'];
      $newrow['id'] = $row['id'];
      $newrow['available'] = $row['available'];
      $newrow['owner'] = $row['LastName'].', '.$row['FirstName'];
      $newrow['status'] = array(($row['available']==0)?_('Available to students'):_('Hidden from students'));
      $newrow['hidden'] = ($row['hidefromcourselist']==1);
      $newrow['canedit'] = ($myrights==100 || $row['groupid']==$groupid);
      if ($newrow['lockaid']>0) {
        $newrow['status'][] = _('Locked for Assessment');
      }
      if ($newrow['hidefromcourselist']==1) {
        $newrow['status'][] = '<span class="hocp">'._("Hidden on User's Home Page").'</span>';
      }
      $courses_tutoring[] = $newrow;
    }

    //pull courses taking
    $query = "SELECT imas_courses.id,imas_courses.ownerid,imas_courses.name,imas_courses.available,imas_courses.lockaid,imas_users.FirstName,imas_users.LastName,imas_users.groupid,imas_students.hidefromcourselist ";
    $query .= "FROM imas_courses JOIN imas_users ON imas_courses.ownerid=imas_users.id ";
    $query .= "JOIN imas_students ON imas_students.courseid=imas_courses.id WHERE imas_students.userid=:uid AND imas_courses.available<4 ";
    $query .= "ORDER BY imas_courses.name";
    $stm = $DBH->prepare($query);
    $stm->execute(array(':uid'=>$uid));
    $courses_taking = array();
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $newrow = array();
      $newrow['id'] = $row['id'];
      $newrow['available'] = $row['available'];
      $newrow['name'] = $row['name'];
      $newrow['owner'] = $row['LastName'].', '.$row['FirstName'];
      $newrow['status'] = array(($row['available']==0)?_('Available to students'):_('Hidden from students'));
      $newrow['hidden'] = ($row['hidefromcourselist']==1);
      $newrow['canedit'] = ($myrights==100 || $row['groupid']==$groupid);
      if ($row['lockaid']>0) {
        $newrow['status'][] = _('Locked for Assessment');
      }
      if ($row['hidefromcourselist']==1) {
        $newrow['status'][] = '<span class="hocp">'._("Hidden on User's Home Page").'</span>';
      }
      $courses_taking[] = $newrow;
    }
  }
}

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js\"></script>\n";
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/jquery.validate.min.js?v=122917"></script>';
$placeinhead .= '<style type="text/css">
li.unhide {
  display: none;
}
td.hocptd li.unhide {
  display: inherit;
}
td.hocptd li.hide {
  display: none;
}
</style>';
require("../header.php");

if ($overwriteBody==1) {
 echo $body;
} else {
  echo '<div class=breadcrumb>',$curBreadcrumb, '</div>';
	echo '<div id="headeruserdetail" class="pagetitle"><h1>'._('User Detail').': <span class="pii-full-name">';
  echo Sanitize::encodeStringForDisplay($userinfo['LastName'].', '.$userinfo['FirstName']);
  echo '</span></h1></div>';


  //basic user info
  echo '<p>';
  echo _('Username').': <span class="pii-username">'.Sanitize::encodeStringForDisplay($userinfo['SID']).'</span><br/>';
  echo _('Role').': '.Sanitize::encodeStringForDisplay($userinfo['role']);
  if ($userinfo['gname']!==null) {
    echo '<br/>'._('Group').': ';
    echo '<a href="groupreportdetails.php?id='.Sanitize::onlyInt($userinfo['groupid']).'">';
    echo Sanitize::encodeStringForDisplay($userinfo['gname']);
    echo '</a>';
    if (isset($userinfo['parentgroup'])) {
      echo ' ('._('Subgroup of').': '.Sanitize::encodeStringForDisplay(trim($userinfo['parentgroup'])).')';
    }
  }
  echo '<br/>'._('Email').': <span class="pii-email">'.Sanitize::encodeStringForDisplay($userinfo['email']).'</span>';
  echo '<br/>'._('Last Login').': '.Sanitize::encodeStringForDisplay($userinfo['lastaccess']);
  echo '<br/>'._('Active Courses').': '.Sanitize::onlyInt($totalactivecourses);
  echo '<br/>'._('Total Active Students').': ';
  echo Sanitize::onlyInt($totalactivestudirect).' '. _('direct').', ';
  echo Sanitize::onlyInt($totalactivestuLTI).' '. _('via LTI');
  echo '</p>';

  if ($errors != '') {
  	  echo '<p class=noticetext>'.$errors.'</p>';
  }
  echo '<form method="post" id="pwform" class=limitaftervalidate>';
  echo '<p><a href="#" onclick="$(\'#pwreset\').show();return false;">';
  echo _('Reset Password').'</a>';
  echo ' <span style="display:none;" id="pwreset"><label>'._('Set temporary password to: ');
  echo '<input id="pw1" class="pii-security" name="pw1" type="text" /></label> ';
  echo '<input type=submit><span>';
  echo '</p></form>';
  showNewUserValidation('pwform');

  if ((count($courses_teaching)>0 || count($courses_tutoring)>0) && count($courses_taking)>0) {
    //jump nav
    echo '<p>'._('Jump to').': ';
    if (count($courses_teaching)>0) {
      echo '<a href="#courses-teaching-header">'._('Courses Teaching').'</a> | ';
    }
    if (count($courses_tutoring)>0) {
      echo '<a href="#courses-tutoring-header">'._('Courses Tutoring').'</a> | ';
    }
    if (count($courses_taking)>0) {
      echo '<a href="#courses-taking-header">'._('Courses Taking').'</a> ';
    }
    echo '</p>';
  }

  if (count($courses_teaching)>0) {
    echo '<h3 id="courses-teaching-header">'._('Courses Teaching').'</h3>';
    echo '<table class="gb" id="courses-teaching"><thead><tr>';
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Course ID').'</th>';
    echo '<th>'._('Active').'</th>';
    echo '<th>'._('Student Count').'</th>';
    echo '<th>'._('Last Activity').'</th>';
    echo '<th>'._('LTI?').'</th>';
    echo '<th>'._('Status').'</th>';
    echo '<th>'._('Owner (if not user)').'</th>';
    echo '<th>'._('Based on').'</th>';
    echo '</tr></thead><tbody>';
    $alt = 0;
    foreach ($courses_teaching as $course) {
      if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
      echo '<td ';
      echo 'data-cid='.Sanitize::onlyInt($course['id']).' ';
      if ($course['hidden']) {
        echo 'class="hocptd" ';
      }
      echo '>';
      if ($course['canedit']) {
      	  echo '<img src="'.$staticroot.'/img/gears.png"/> ';
      }
      if ($course['showlink']) {
      	  echo '<a href="../course/course.php?cid='.Sanitize::encodeUrlParam($course['id']).'">';
      }
      if ($course['available']!=0) {
        echo '<i>';
      }
      if ($course['deleted']) {
        echo '<span style="color:#faa;text-decoration: line-through;">';
      }
      echo Sanitize::encodeStringForDisplay($course['name']);
      if ($course['deleted']) {
        echo '</span>';
      }
      if ($course['available']!=0) {
        echo '</i>';
      }
      if ($course['showlink']) {
      	  echo '</a>';
      }
      echo '</td>';
      echo '<td>'.Sanitize::encodeStringForDisplay($course['id']).'</td>';
      echo '<td>'.($course['active']==1?_('Active'):_('Old')).'</td>';
      if ($course['stucnt']>0) {
      	  echo '<td>'.Sanitize::onlyInt($course['stucnt']).'</td>';
      } else {
      	  echo '<td>-</td>';
      }
      if ($course['lastactivity']>0) {
      	  echo '<td>'.date("n/j/y",$course['lastactivity']).'</td>';
      } else {
      	  echo '<td>-</td>';
      }
      echo '<td>'.(!empty($course['isLTI'])?_('LTI'):_('No')).'</td>';
      echo '<td>'.implode('<br/>',$course['status']).'</td>';
      echo '<td><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($course['owner']).'</span></td>';
      echo '<td>'.Sanitize::encodeStringForDisplay($course['template']).'</td>';
      echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<script type="text/javascript">
      initSortTable("courses-teaching",Array("S","N","S","N","D","S","S","S"),true);
      </script>';
  }
  if (count($courses_tutoring)>0) {
    echo '<h3 id="courses-teaching-header">'._('Courses Tutoring').'</h3>';
    echo '<table class="gb" id="courses-tutoring"><thead><tr>';
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Course ID').'</th>';
    echo '<th>'._('Status').'</th>';
    echo '<th>'._('Course Owner').'</th>';
    echo '</tr></thead><tbody>';
    $alt = 0;
    foreach ($courses_tutoring as $course) {
      if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
      echo '<td ';
      echo 'data-cid='.Sanitize::onlyInt($course['id']).' ';
      if ($course['hidden']) {
        echo 'class="hocptd" ';
      }
      echo '>';
      if ($course['canedit']) {
      	      echo '<img src="'.$staticroot.'/img/gears.png"/> ';
      }
      echo '<a href="../course/course.php?cid='.Sanitize::encodeUrlParam($course['id']).'">';
      if ($course['available']!=0) {
        echo '<i>';
      }
      echo Sanitize::encodeStringForDisplay($course['name']);
      if ($course['available']!=0) {
        echo '</i>';
      }
      echo '</a>';
      echo '</td>';
      echo '<td>'.Sanitize::encodeStringForDisplay($course['id']).'</td>';
      echo '<td>'.implode('<br/>',$course['status']).'</td>';
      echo '<td><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($course['owner']).'</span></td>';
      echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<script type="text/javascript">
      initSortTable("courses-tutoring",Array("S","N","S","S"),true);
      </script>';

  }
  if (count($courses_taking)>0) {
    echo '<h3 id="courses-taking-header">'._('Courses Taking').'</h3>';
    echo '<table class="gb" id="courses-taking"><thead><tr>';
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Course ID').'</th>';
    echo '<th>'._('Status').'</th>';
    echo '<th>'._('Course Owner').'</th>';
    echo '</tr></thead><tbody>';
    $alt = 0;
    foreach ($courses_taking as $course) {
      if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
      echo '<td ';
      echo 'data-cid='.Sanitize::onlyInt($course['id']).' ';
      if ($course['hidden']) {
        echo 'class="hocptd" ';
      }
      echo '>';
      if ($course['canedit']) {
      	      echo '<img src="'.$staticroot.'/img/gears.png"/> ';
      }
      echo '<a href="../course/course.php?cid='.Sanitize::encodeUrlParam($course['id']).'">';
      if ($course['available']!=0) {
        echo '<i>';
      }
      echo Sanitize::encodeStringForDisplay($course['name']);
      if ($course['available']!=0) {
        echo '</i>';
      }
      echo '</a>';
      echo '</td>';
      echo '<td>'.Sanitize::encodeStringForDisplay($course['id']).'</td>';
      echo '<td>'.implode('<br/>',$course['status']).'</td>';
      echo '<td><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($course['owner']).'</span></td>';
      echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<script type="text/javascript">
      initSortTable("courses-taking",Array("S","N","S","S"),true);
      </script>';

  }

  //TODO: Implement course hide/unhide from here.
  echo '<script type="text/javascript">
	var detailPageUser = "'.Sanitize::encodeStringForDisplay($uid).'";
  function unhidecourse(el) {
    var coursetochange = $(el).closest("td").attr("data-cid");
    var tableid = $(el).closest("table").attr("id");
    var tochg = "take";
    if (tableid.match(/teach/)) {
      tochg = "teach";
    } else if (tableid.match(/tutor/)) {
      tochg = "tutor";
    }
    $.ajax({
      url: "unhidefromcourselist.php",
      type: "GET",
      data: {"type": tochg, "tohide": coursetochange, "user": detailPageUser, "ajax": true}
    }).done(function(msg) {
      if (msg=="OK") {
        $(el).closest("tr").find("span.hocp").remove();
        $(el).closest("tr").find("td:nth-child(3)").find("br:last").remove();
        $(el).closest("tr").find("td:first-child").removeClass("hocptd");
      } else {
        alert("Error unhiding course");
      }
    });
  }
  function hidecourse(el) {
    var coursetochange = $(el).closest("td").attr("data-cid");
    var tableid = $(el).closest("table").attr("id");
    var tochg = "take";
    if (tableid.match(/teach/)) {
      tochg = "teach";
    } else if (tableid.match(/tutor/)) {
      tochg = "tutor";
    }
    $.ajax({
      url: "hidefromcourselist.php",
      type: "GET",
      data: {"type": tochg, "tohide": coursetochange, "user": detailPageUser, "ajax": true}
    }).done(function(msg) {
      if (msg=="OK") {
        var hiddenstr = "<span class=hocp>'._("Hidden on User's Home Page").'</span>";
        $(el).closest("tr").find("td:nth-child(3)").append("<br/>"+hiddenstr);
        $(el).closest("tr").find("td:first-child").addClass("hocptd");
      } else {
        alert("Error hiding course");
      }
    });
  }

  $(function() {
    var html = \'<span class="dropdown"><a role="button" tabindex=0 class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="'.$staticroot.'/img/gears.png" alt="Options"/></a>\';
    html += \'<ul role="menu" class="dropdown-menu">\';
    $("tr td:first-child").css("clear","both").each(function (i,el) {
      var cid = $(el).attr("data-cid");
      var thishtml = html + \' <li class="unhide"><a href="#" onclick="unhidecourse(this);return false;">'._('Return to home page course list').'</a></li>\';
      thishtml += \' <li class="hide"><a href="#" onclick="hidecourse(this);return false;">'._('Hide from home page course list').'</a></li>\';

        thishtml += \' <li><a href="forms.php?from=ud'.$uid.'&action=modify&id=\'+cid+\'">'._('Settings').'</a></li>\';
        thishtml += \' <li><a href="addremoveteachers.php?from=ud'.$uid.'&id=\'+cid+\'">'._('Add/remove teachers').'</a></li>\';
        thishtml += \' <li><a href="transfercourse.php?from=ud'.$uid.'&id=\'+cid+\'">'._('Transfer ownership').'</a></li>\';
        thishtml += \' <li><a href="teacherauditlog.php?userid='.$uid.'&cid=\'+cid+\'">'._('Teacher Audit Log').'</a></li>\';
        thishtml += \' <li><a href="forms.php?from=ud'.$uid.'&action=delete&id=\'+cid+\'">'._('Delete').'</a></li>\';
        thishtml += \'</ul></span> \';
        $(el).find("img").replaceWith(thishtml);
    });
    $(".dropdown-toggle").dropdown();
    });
    </script>';
}

echo '<p>&nbsp;</p><p>&nbsp;</p>';
require("../footer.php");
