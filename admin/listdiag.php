<?php
//IMathAS - User Details page
//(c) 2017 David Lippman

//TODO:  fix diag delete breadcrumbs

require("../init.php");

$overwriteBody = 0;
$body = "";

if (($myspecialrights&4)!=4 && $myrights < 100) {
 	$overwriteBody = 1;
	$body = "You don't have authority to view this page.";
}  else {
  if ($myrights<75 || (isset($_GET['show']) && $_GET['show']{0}=='u')) {  //only show own
    if ($myrights<75) {
      $showuser = $userid;
      $from = 'ld';
    } else {
      $showuser = Sanitize::onlyInt(substr($_GET['show'],1));
      $from = 'ldu'.$showuser;
    }
    $query = "SELECT d.id,d.name,d.public,d.cid,ic.name AS cname FROM imas_diags as d JOIN imas_users AS u ON u.id=d.ownerid ";
    $query .= "JOIN imas_courses AS ic ON d.cid=ic.id ";
    $query .= "WHERE d.ownerid=:ownerid ORDER BY d.name";
    $stm = $DBH->prepare($query);
    $stm->execute(array(':ownerid'=>$showuser));
    $diags = $stm->fetchAll(PDO::FETCH_ASSOC);

    $stm = $DBH->prepare("SELECT LastName,FirstName FROM imas_users WHERE id=:userid");
    $stm->execute(array(':userid'=>$showuser));
    $userdisplayname = implode(', ',$stm->fetch(PDO::FETCH_NUM));

    $list = 'self';
  } else if ($myrights<100 || (isset($_GET['show']) && $_GET['show']{0}=='g')) {  //show group's
    if ($myrights==100) {
      $showgroup = Sanitize::onlyInt(substr($_GET['show'],1));
    } else {
      $showgroup = $groupid;
    }
    $from = 'ldg'.$showgroup;

    $query = "SELECT d.id,d.name,d.public,d.cid,ic.name AS cname,u.FirstName,u.LastName,u.id AS uid FROM imas_diags as d ";
    $query .= "JOIN imas_courses AS ic ON d.cid=ic.id ";
    $query .= "JOIN imas_users AS u ON u.id=d.ownerid ";
    $query .= "WHERE u.groupid=:groupid ORDER BY d.name";
    $stm = $DBH->prepare($query);
    $stm->execute(array(':groupid'=>$showgroup));
    $diags = $stm->fetchAll(PDO::FETCH_ASSOC);

    $stm = $DBH->prepare("SELECT name FROM imas_groups WHERE id=:groupid");
    $stm->execute(array(':groupid'=>$showgroup));
    $groupname = $stm->fetchColumn(0);
    $list = 'group';
  } else {  //show all
    $query = "SELECT d.id,d.name,d.public,d.cid,ic.name AS cname,u.FirstName,u.LastName,u.id AS uid,g.name AS gname FROM imas_diags as d ";
    $query .= "JOIN imas_courses AS ic ON d.cid=ic.id ";
    $query .= "JOIN imas_users AS u ON u.id=d.ownerid ";
    $query .= "LEFT JOIN imas_groups AS g ON u.groupid=g.id ";
    $query .= "ORDER BY d.name";
    $stm = $DBH->query($query);
    $diags = $stm->fetchAll(PDO::FETCH_ASSOC);
    $list = 'all';
    $from = 'ld';
  }
}

if ($myrights<75) {
  $curBreadcrumb = $breadcrumbbase;
} else {
  $curBreadcrumb = $breadcrumbbase .' <a href="admin2.php">'._('Admin').'</a> &gt; ';
  if ($list=='self') {
    $curBreadcrumb .= '<a href="userdetails.php?id='.$showuser.'">'._('User Details').'</a> &gt; ';
  } else if ($list=='group' && $myrights==100) {
    $curBreadcrumb .= '<a href="admin2.php?groupdetails='.$showgroup.'">'._('Group Details').'</a> &gt; ';
  }
}
$curBreadcrumb .= _('Diagnostics');

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
$placeinhead .= '<script type="text/javascript">
$(function() {
  var html = \'<span class="dropdown"><a role="button" tabindex=0 class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="../img/gears.png" alt="Options"/></a>\';
  html += \'<ul role="menu" class="dropdown-menu">\';
  $("tr td:first-child").css("clear","both").each(function (i,el) {
    var href = $(el).find("a").attr("href");
    var did = href.substr(href.indexOf("id=")+3);
    thishtml = html + \' <li><a href="diagsetup.php?from='.$from.'&id=\'+did+\'">'._('Modify').'</a></li>\';
    thishtml += \' <li><a href="forms.php?from=home&action=removediag&id=\'+did+\'">'._('Delete').'</a></li>\';
    thishtml += \' <li><a href="diagonetime.php?from='.$from.'&id=\'+did+\'">'._('One-time Passwords').'</a></li>\';
    thishtml += \'</ul></span> \';
    $(el).prepend(thishtml);

  });
  $(".dropdown-toggle").dropdown();
});
</script>';
require("../header.php");

if ($overwriteBody==1) {
  echo $body;
} else {
  echo '<div class=breadcrumb>',$curBreadcrumb, '</div>';
  echo '<div id="headerdiaglist" class="pagetitle"><h1>'._('Diagnostics');
  if ($list=='self') {
    echo ': '.Sanitize::encodeStringForDisplay($userdisplayname);
  } else if ($list=='group') {
    echo ': '.Sanitize::encodeStringForDisplay($groupname);
  }
  echo '</h1></div>';
	echo '<div class="cpmid">';
	echo '<a href="diagsetup.php">'._('Add New Diagnostic').'</a>';
	echo '</div>';
  if (count($diags)==0) {
    echo '<p>'._('No Diagnostics to display').'</p>';
  } else {
    echo '<table class="gb" id="diaglist"><thead><tr>';
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Available').'</th>';
    echo '<th>'._('Public').'</th>';
    echo '<th>'._('Course').'</th>';
    if ($list=='group' || $list=='all') {
      echo '<th>'._('Owner').'</th>';
    }
    if ($list=='all') {
      echo '<th>'._('Group').'</th>';
    }
    echo '</tr></thead><tbody>';
    $alt = 0;
    foreach ($diags as $diag) {
      if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
      echo '<td><a href="../diag/index.php?id='.Sanitize::encodeUrlParam($diag['id']).'">';
      echo Sanitize::encodeStringForDisplay($diag['name']).'</a></td>';
      echo '<td>',(($diag['public']&1) ? _("Yes") : _("No")),'</td>';
      echo '<td>',(($diag['public']&2) ? _("Yes") : _("No")),'</td>';
      echo '<td><a href="../course/course.php?id='.Sanitize::encodeUrlParam($diag['cid']).'">';
      echo Sanitize::encodeStringForDisplay($diag['cname']).'</a></td>';
      if ($list=='group' || $list=='all') {
        echo '<td>',Sanitize::encodeStringForDisplay($diag['LastName'].', '.$diag['FirstName']),'</td>';
      }
      if ($list=='all') {
      	if ($diag['gname']===null) {
      		$diag['gname'] = _('Default');
      	}
        echo '<td>',Sanitize::encodeStringForDisplay($diag['gname']),'</td>';
      }
    }
    echo '</tbody></table>';
    if ($list=='all') {
      echo '<script type="text/javascript">initSortTable("diaglist",Array("S","S","S","S","S","S"),true);</script>';
    } else if ($list=='group') {
      echo '<script type="text/javascript">initSortTable("diaglist",Array("S","S","S","S","S"),true);</script>';
    } else {
      echo '<script type="text/javascript">initSortTable("diaglist",Array("S","S","S","S"),true);</script>';
    }
  }
}
require("../footer.php");
