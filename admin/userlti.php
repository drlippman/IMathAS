<?php
//IMathAS - User Details page
//(c) 2017 David Lippman

require("../init.php");

$overwriteBody = 0;
$body = "";

if ($myrights < 100) {
 	$overwriteBody = 1;
	$body = "You don't have authority to view this page.";
} else if (isset($_POST['removeuserlti'])) {
  $id = intval($_POST['removeuserlti']);
  //DB $query = "DELETE FROM imas_ltiusers WHERE id=$id";
  //DB mysql_query($query) or die("Query failed : " . mysql_error());
  $stm = $DBH->prepare("DELETE FROM imas_ltiusers WHERE id=:id");
  $stm->execute(array(':id'=>$id));
  if ($stm->rowCount()>0) {
    echo "OK";
  } else {
    echo "ERROR";
  }
  exit;
} else if (isset($_POST['removecourselti'])) {
  $id = intval($_POST['removecourselti']);
  $stm = $DBH->prepare("SELECT org,contextid FROM imas_lti_courses WHERE id=:id");
  $stm->execute(array(':id'=>$id));
  $row = $stm->fetch(PDO::FETCH_ASSOC);
  if ($row===false) {
  	  echo "ERROR";
  	  exit;
  }
  $stm = $DBH->prepare("DELETE FROM imas_lti_placements WHERE org=:org AND contextid=:contextid");
  $stm->execute(array(':org'=>$row['org'], ':contextid'=>$row['contextid']));
  
  $stm = $DBH->prepare("DELETE FROM imas_lti_courses WHERE id=:id");
  $stm->execute(array(':id'=>$id));
  echo "OK";
  exit;
} else if (empty($_GET['id'])) {
  $overwriteBody = 1;
  $body = 'No id provided';
} else {
  $uid = Sanitize::onlyInt($_GET['id']);
  $query = "SELECT iu.FirstName,iu.LastName,ig.name AS gname,ig.parent ";
  $query .= "FROM imas_users AS iu LEFT JOIN imas_groups AS ig ON iu.groupid=ig.id WHERE iu.id=:id";
  $stm = $DBH->prepare($query);
  $stm->execute(array(':id'=>$uid));
  $userinfo = $stm->fetch(PDO::FETCH_ASSOC);

  $stm = $DBH->prepare("SELECT org,id,ltiuserid FROM imas_ltiusers WHERE userid=:userid");
  $stm->execute(array(':userid'=>$uid));
  $user_lti = $stm->fetchAll(PDO::FETCH_ASSOC);
  if ($user_lti===false) {
    $user_lti = array();
  }

  $query = "SELECT ilc.org,ilc.id,ilc.courseid,ilc.contextid,ic.name FROM imas_lti_courses AS ilc ";
  $query .= "JOIN imas_teachers AS it ON ilc.courseid=it.courseid ";
  $query .= "JOIN imas_courses AS ic ON it.courseid=ic.id WHERE it.userid=:userid ";
  $query .= "ORDER BY ic.name";
  $stm = $DBH->prepare($query);
  $stm->execute(array(':userid'=>$uid));
  $course_lti = $stm->fetchAll(PDO::FETCH_ASSOC);
  if ($course_lti===false) {
    $course_lti = array();
  }
}

$curBreadcrumb = $breadcrumbbase .' <a href="admin2.php">'._('Admin').'</a> &gt; ';
$curBreadcrumb .= '<a href="userdetails.php?id='.$uid.'">'._('User Details').'</a> &gt; ';
$curBreadcrumb .= _('LTI Connections');

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
$placeinhead .= '<script type="text/javascript">
function removeuserlti(el,id) {
  return removelti(el,"user",id);
}
function removecourselti(el,id) {
  return removelti(el,"course",id);
}
function removelti(el,type,id) {
  if (confirm("'._('Are you SURE?').'")) {
    $.ajax({
      url: "userlti.php",
      type: "POST",
      data: "remove"+type+"lti="+id
    }).done(function(msg) {
      if (msg=="OK") {
        $(el).closest("tr").fadeOut(500,function() {
          //restripe
          var parenttable = $(el).closest("table");
          $(el).closest("tr").remove();
          $(parenttable).find("tbody tr:even").addClass("even").removeClass("odd");
          $(parenttable).find("tbody tr:odd").addClass("odd").removeClass("even");
        });
      } else {
        alert("Error removing LTI connection");
      }
    });
  }
  return false;
}
</script>';
require("../header.php");

if ($overwriteBody==1) {
  echo $body;
} else {
  echo '<div class=breadcrumb>',$curBreadcrumb, '</div>';
	echo '<div id="headeruserdetail" class="pagetitle"><h2>'._('LTI Connections').': ';
  echo Sanitize::encodeStringForDisplay($userinfo['LastName'].', '.$userinfo['FirstName']);
  echo '</h2></div>';

  echo '<p>'._('Group').': '.Sanitize::encodeStringForDisplay($userinfo['gname']).'</p>';

  echo '<h4>'._('LTI user connections').'</h4>';
  echo '<table class="gb" id="ltiusers"><thead><tr>';
  echo '<th>'._('Key:org').'</th>';
  echo '<th>'._('Remote Userid').'</th>';
  echo '<th>'._('Remove').'</th>';
  echo '</tr></thead><tbody>';
  $alt = 0;
  foreach ($user_lti as $u) {
    if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
    echo '<td>',Sanitize::encodeStringForDisplay($u['org']),'</td>';
    echo '<td>',Sanitize::encodeStringForDisplay($u['ltiuserid']),'</td>';
    echo '<td><a onclick="return removeuserlti(this,'.Sanitize::encodeStringForJavascript($u['id']).')" href="#">';
    echo _('Remove connection').'</a></td>';
    echo '</tr>';
  }
  echo '</tbody></table>';
  echo '<script type="text/javascript">
    initSortTable("ltiusers",Array("S","S",false),true);
    </script>';

  echo '<h4>'._('LTI course connections').'</h4>';
  echo '<table class="gb" id="lticourses"><thead><tr>';
  echo '<th>'._('Course').'</th>';
  echo '<th>'._('Course ID').'</th>';
  echo '<th>'._('Key:org').'</th>';
  echo '<th>'._('contextid').'</th>';
  echo '<th>'._('Remove').'</th>';
  echo '</tr></thead><tbody>';
  $alt = 0;
  foreach ($course_lti as $u) {
    if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
    echo '<td>',Sanitize::encodeStringForDisplay($u['name']),'</td>';
    echo '<td>',Sanitize::encodeStringForDisplay($u['courseid']),'</td>';
    echo '<td>',Sanitize::encodeStringForDisplay($u['org']),'</td>';
    echo '<td>',Sanitize::encodeStringForDisplay($u['contextid']),'</td>';
    echo '<td><a onclick="return removecourselti(this,'.Sanitize::encodeStringForJavascript($u['id']).')" href="#">';
    echo _('Remove connection').'</a></td>';
    echo '</tr>';
  }
  echo '</tbody></table>';
  echo '<script type="text/javascript">
    initSortTable("lticourses",Array("S","N","S","S",false),true);
    </script>';
}
require("../footer.php");
