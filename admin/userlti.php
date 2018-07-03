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
  $id = Sanitize::onlyInt($_POST['removecourselti']);
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
} else if (isset($_POST['removeplacementlti'])) {
  $id = intval($_POST['removeplacementlti']);
  //DB $query = "DELETE FROM imas_ltiusers WHERE id=$id";
  //DB mysql_query($query) or die("Query failed : " . mysql_error());
  $stm = $DBH->prepare("DELETE FROM imas_lti_placements WHERE id=:id");
  $stm->execute(array(':id'=>$id));
  if ($stm->rowCount()>0) {
    echo "OK";
  } else {
    echo "ERROR";
  }
  exit;
} else if (!empty($_GET['contextid'])) {
  $contextid = Sanitize::simpleString($_GET['contextid']);
  $query = "SELECT ilp.id,ilp.linkid,ilp.typeid,ilp.placementtype,ia.name FROM ";
  $query .= "imas_lti_placements AS ilp LEFT JOIN imas_assessments AS ia ON ilp.typeid=ia.id AND ilp.placementtype='assess' ";
  $query .= "WHERE ilp.contextid=? UNION ";
  $query .= "SELECT ilp.id,ilp.linkid,ilp.typeid,ilp.placementtype,ic.name FROM ";
  $query .= "imas_lti_placements AS ilp LEFT JOIN imas_courses AS ic ON ilp.typeid=ic.id AND ilp.placementtype='course'";
  $query .= "WHERE ilp.contextid=?";
  $stm = $DBH->prepare($query);
  $stm->execute(array($contextid, $contextid));
  echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC), JSON_HEX_TAG);
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

  $query = "SELECT ilc.org,ilc.id,ilc.courseid,ilc.contextid,ilc.contextlabel,ic.name FROM imas_lti_courses AS ilc ";
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
function removeplacement(el,id) {
  return removelti(el,"placement",id);
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
$(function() {
  $(".contextid").on("click", function(event) {
    var contextid = $(event.target).html().split("<br")[0];
    $.ajax({
      url: "userlti.php",
      data: "contextid="+contextid,
      dataType: "json"
    }).done(function(msg) {
      $("#placements tbody").empty();
      $.each(msg, function (i, item) {
        var tr = $("<tr>").append(
          $("<td>").text(item.placementtype),
          $("<td>").text(item.name),
          $("<td>").text(item.typeid),
          $("<td>").text(item.linkid),
          $("<td>").append(
            $("<a>").text("Remove connection")
             .attr("href","#")
             .attr("onclick","return removeplacement(this,"+item.id+")")
          )
        )
        $("#placements tbody").append(tr);
      });
      $("#placementwrap h4").text(_("LTI placements in contextid: ")+contextid);
      $("#placements").find("tbody tr:even").addClass("even");
      $("#placements").find("tbody tr:odd").addClass("odd");
      $("#placementwrap").show();
    });
  });
})
</script>';
require("../header.php");

if ($overwriteBody==1) {
  echo $body;
} else {
  echo '<div class=breadcrumb>',$curBreadcrumb, '</div>';
	echo '<div id="headeruserdetail" class="pagetitle"><h1>'._('LTI Connections').': ';
  echo Sanitize::encodeStringForDisplay($userinfo['LastName'].', '.$userinfo['FirstName']);
  echo '</h1></div>';

  echo '<p>'._('Group').': '.Sanitize::encodeStringForDisplay($userinfo['gname']).'</p>';

  echo '<h3>'._('LTI user connections').'</h3>';
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

  echo '<h3>'._('LTI course connections').'</h3>';
  echo '<table class="gb" id="lticourses"><thead><tr>';
  echo '<th>'._('Course').'</th>';
  echo '<th>'._('Course ID').'</th>';
  echo '<th>'._('Key:org').'</th>';
  echo '<th>'._('contextid / label').'</th>';
  echo '<th>'._('Remove').'</th>';
  echo '</tr></thead><tbody>';
  $alt = 0;
  foreach ($course_lti as $u) {
    if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
    echo '<td>',Sanitize::encodeStringForDisplay($u['name']),'</td>';
    echo '<td>',Sanitize::encodeStringForDisplay($u['courseid']),'</td>';
    echo '<td>',Sanitize::encodeStringForDisplay($u['org']),'</td>';
    echo '<td class="contextid pointer">',Sanitize::encodeStringForDisplay($u['contextid']);
    if ($u['contextlabel'] != '') {
    	    echo '<br/>',Sanitize::encodeStringForDisplay($u['contextlabel']);
    }
    echo '</td>';
    echo '<td><a onclick="return removecourselti(this,'.Sanitize::encodeStringForJavascript($u['id']).')" href="#">';
    echo _('Remove connection').'</a></td>';
    echo '</tr>';
  }
  echo '</tbody></table>';
  echo '<script type="text/javascript">
    initSortTable("lticourses",Array("S","N","S","S",false),true);
    </script>';
  echo '<p>'._('Click a contextid above to list the associated placements').'</p>';
  echo '<div id="placementwrap" style="display:none"><h3></h3>';
  echo '<table id="placements" class="gb"><caption></caption><thead><tr>';
  echo '<th>',_('Type'),'</th>';
  echo '<th>',_('Item'),'</th>';
  echo '<th>',_('Item ID'),'</th>';
  echo '<th>',_('Linkid'),'</th>';
  echo '<th>',_('Remove'),'</th>';
  echo '</tr></thead><tbody></tbody></table></div>';
}
require("../footer.php");
