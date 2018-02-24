<?php
//IMathAS - User Details page
//(c) 2017 David Lippman

require("../init.php");

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

$curBreadcrumb = $breadcrumbbase .' <a href="admin2.php">'._('Admin').'</a> &gt; ';
if (isset($_GET['group'])) {
  $curBreadcrumb .= '<a href="admin2.php?groupdetails='.Sanitize::onlyInt($_GET['group']).'">'._('Group Details').'</a> &gt; ';
}
$curBreadcrumb .= _('User Detail');

if ($myrights < 75) {
 	$overwriteBody = 1;
	$body = "You don't have authority to view this page.";
}  if (empty($_GET['id'])) {
  $overwriteBody = 1;
  $body = 'No id provided';
} else {
  //pull basic user info
  $uid = Sanitize::onlyInt($_GET['id']);
  $query = "SELECT iu.FirstName,iu.LastName,iu.email,iu.rights,iu.lastaccess,ig.name AS gname,iu.specialrights,ig.parent ";
  $query .= "FROM imas_users AS iu LEFT JOIN imas_groups AS ig ON iu.groupid=ig.id WHERE iu.id=:id";
  if ($myrights<100) {
    $query .= " AND iu.groupid=:groupid";
  }
  $stm = $DBH->prepare($query);
  if ($myrights<100) {
    $stm->execute(array(':id'=>$uid, ':groupid'=>$groupid));
  } else {
    $stm->execute(array(':id'=>$uid));
  }
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
    //courses teaching list
    $query = "SELECT imas_courses.id,imas_courses.ownerid,imas_courses.name,imas_courses.available,imas_courses.lockaid,imas_users.FirstName,imas_users.LastName,imas_users.groupid,imas_teachers.hidefromcourselist ";
    $query .= "FROM imas_courses JOIN imas_users ON imas_courses.ownerid=imas_users.id ";
    $query .= "JOIN imas_teachers ON imas_teachers.courseid=imas_courses.id WHERE imas_teachers.userid=:uid ";
    $query .= " ORDER BY imas_courses.name";
    $stm = $DBH->prepare($query);
  	$stm->execute(array(':uid'=>$uid));
    $courses_teaching = array();
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $newrow = array();
      $newrow['name'] = $row['name'];
      $newrow['id'] = $row['id'];
      $newrow['available'] = $row['available'];
      $newrow['owner'] = ($row['ownerid']!=$uid)?$row['LastName'].', '.$row['FirstName']:'';
      $newrow['canedit'] = ($row['ownerid']==$uid || $myrights==100 || $row['groupid']==$groupid);
      $newrow['deleted'] = ($row['available']==4);
      $newrow['hidden'] = ($row['hidefromcourselist']==1);
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
      $courses_teaching[] = $newrow;
    }

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
      if ($newrow['lockaid']>0) {
        $newrow['status'][] = _('Locked for Assessment');
      }
      if ($newrow['hidefromcourselist']==1) {
        $newrow['status'][] = '<span class="hocp">'._("Hidden on User's Home Page").'</span>';
      }
      $courses_taking[] = $newrow;
    }
  }
}

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
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
	echo '<div id="headeruserdetail" class="pagetitle"><h2>'._('User Detail').': ';
  echo Sanitize::encodeStringForDisplay($userinfo['LastName'].', '.$userinfo['FirstName']);
  echo '</h2></div>';


  //sub nav links
  echo '<div class="cpmid"><a href="forms.php?from=ud'.$uid.'&action=chgrights&id='.$uid.'">'._('Edit User').'</a>';
  echo ' | <a href="../util/utils.php?emulateuser='.$uid.'">'. _('Emulate User').'</a>';
  if ($myrights==100) {
    echo ' | <a href="userlti.php?id='.$uid.'">'. _('LTI Connections').'</a>';
  }
  if ($userinfo['rights']==100 || ($userinfo['specialrights']&4)==4) {
    echo ' | <a href="listdiag.php?show=u'.$uid.'">'. _('Diagnostics').'</a>';
  }
  echo '</div>';

  //basic user info
  echo '<p>'._('Role').': '.Sanitize::encodeStringForDisplay($userinfo['role']);
  if ($userinfo['gname']!==null) {
    echo '<br/>'._('Group').': '.Sanitize::encodeStringForDisplay($userinfo['gname']);
    if (isset($userinfo['parentgroup'])) {
      echo ' ('._('Subgroup of').': '.Sanitize::encodeStringForDisplay(trim($userinfo['parentgroup'])).')';
    }
  }
  echo '<br/>'._('Email').': '.Sanitize::encodeStringForDisplay($userinfo['email']);
  echo '<br/>'._('Last Login').': '.Sanitize::encodeStringForDisplay($userinfo['lastaccess']).'</p>';

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
    echo '<h4 id="courses-teaching-header">'._('Courses Teaching').'</h4>';
    echo '<table class="gb" id="courses-teaching"><thead><tr>';
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Course ID').'</th>';
    echo '<th>'._('Status').'</th>';
    echo '<th>'._('Owner (if not user)').'</th>';
    echo '</tr></thead><tbody>';
    $alt = 0;
    foreach ($courses_teaching as $course) {
      if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
      echo '<td ';
      echo 'data-cid='.Sanitize::onlyInt($course['id']).' ';
      if (!$course['canedit']) {
      	  echo 'data-noedit=1 ';    
      }
      if ($course['hidden']) {
        echo 'class="hocptd" ';
      }
      echo '>';
      echo '<img src="../img/gears.png"/> ';
      echo '<a href="../course/course.php?cid='.Sanitize::encodeUrlParam($course['id']).'">';
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
      echo '</a>';
      echo '</td>';
      echo '<td>'.Sanitize::encodeStringForDisplay($course['id']).'</td>';
      echo '<td>'.implode('<br/>',$course['status']).'</td>';
      echo '<td>'.Sanitize::encodeStringForDisplay($course['owner']).'</td>';
      echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<script type="text/javascript">
      initSortTable("courses-teaching",Array("S","N","S","S"),true);
      </script>';
  }
  if (count($courses_tutoring)>0) {
    echo '<h4 id="courses-teaching-header">'._('Courses Tutoring').'</h4>';
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
      	      echo '<img src="../img/gears.png"/> ';
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
      echo '<td>'.Sanitize::encodeStringForDisplay($course['owner']).'</td>';
      echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<script type="text/javascript">
      initSortTable("courses-tutoring",Array("S","N","S","S"),true);
      </script>';

  }
  if (count($courses_taking)>0) {
    echo '<h4 id="courses-taking-header">'._('Courses Taking').'</h4>';
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
      	      echo '<img src="../img/gears.png"/> ';
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
      echo '<td>'.Sanitize::encodeStringForDisplay($course['owner']).'</td>';
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
    var html = \'<span class="dropdown"><a role="button" tabindex=0 class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="../img/gears.png" alt="Options"/></a>\';
    html += \'<ul role="menu" class="dropdown-menu">\';
    $("tr td:first-child").css("clear","both").each(function (i,el) {
      var cid = $(el).attr("data-cid");
      var thishtml = html + \' <li class="unhide"><a href="#" onclick="unhidecourse(this);return false;">'._('Return to home page course list').'</a></li>\';
      thishtml += \' <li class="hide"><a href="#" onclick="hidecourse(this);return false;">'._('Hide from home page course list').'</a></li>\';

      if ($(el).attr("data-noedit")!=1) {  
        thishtml += \' <li><a href="forms.php?from=ud'.$uid.'&action=modify&id=\'+cid+\'">'._('Settings').'</a></li>\';
        thishtml += \' <li><a href="addremoveteachers.php?from=ud'.$uid.'&id=\'+cid+\'">'._('Add/remove teachers').'</a></li>\';
        thishtml += \' <li><a href="transfercourse.php?from=ud'.$uid.'&id=\'+cid+\'">'._('Transfer ownership').'</a></li>\';
        thishtml += \' <li><a href="forms.php?from=ud'.$uid.'&action=delete&id=\'+cid+\'">'._('Delete').'</a></li>\';
        thishtml += \'</ul></span> \';
        $(el).find("img").replaceWith(thishtml);
      } else {
      	thishtml += \' <li><a href="#" onclick="removeSelfAsCoteacher(this,\'+cid+\',\\\'tr\\\','.$uid.');return false;">'._('Remove as a co-teacher').'</a></li>\';
      	thishtml += \'</ul></span> \';
      	$(el).find("img").replaceWith(thishtml);
      }
    });
    $(".dropdown-toggle").dropdown();
    });
    </script>';
}

echo '<p>&nbsp;</p><p>&nbsp;</p>';
require("../footer.php");
