<?php
//IMathAS: Admin front page
//(c) David Lippman 2017

require("../init.php");


function getRoleNameByRights($rights) {
  switch ($rights) {
    case 5: return _("Guest"); break;
    case 10:return _("Student"); break;
    case 15: return _("Tutor/TA/Proctor"); break;
    case 20: return _("Teacher"); break;
    case 40: return _("LimCourseCreator"); break;
    case 75: return _("GroupAdmin"); break;
    case 100: return _("Admin"); break;
  }
}

$overwriteBody = 0;
$body = "";

$curBreadcrumb = $breadcrumbbase;

if ($myrights < 75) {
 	$overwriteBody = 1;
	$body = "You don't have authority to view this page.";
} else {
  //figure out which page we want to show, or redirect if needed
  if ($myrights==75) {
    //show the groupadmin page
    $page = 'groupadmin';
    $pagetitle = _("Group Administration");
    $curBreadcrumb = $curBreadcrumb  . _('Admin');
    $showgroup = $groupid;

  } else if (!empty($_GET['groupdetails'])) {
    //show the list of group users page
    $showgroup = Sanitize::onlyInt($_GET['groupdetails']);
    if ($showgroup==-1) {
      $groupname = _('Pending Users');
    }  else {
      $stm = $DBH->prepare("SELECT name FROM imas_groups WHERE id=:id");
      $stm->execute(array(':id'=>$showgroup));
      $groupname = $stm->fetchColumn(0);
    }
    $page = 'groupdetails';
    $pagetitle = _("Group Members").': '. $groupname;

    $curBreadcrumb = $curBreadcrumb . ' <a href="admin2.php">' . _('Admin') . '</a> &gt; ' . _("Group Members");



  } else if (!empty($_GET['finduser']) || !empty($_GET['findteacher'])) {
    //search for a user (teacher or regular)
    $words = array();
    $possible_users = array();
    if (trim($_GET['findteacher'])!=='') {
      $limitToTeacher = true;
      $words = preg_split('/\s+/', trim($_GET['findteacher']));
      $pagetitle = _("Select Teacher");
    } else if (trim($_GET['finduser'])!=='') {
      $limitToTeacher = false;
      $words = preg_split('/\s+/', trim($_GET['finduser']));
      $pagetitle = _("Select User");
    }
    if (count($words)==1 && strpos($words[0],'@')!==false) {
      $query = "SELECT id,LastName,FirstName,email,SID FROM imas_users WHERE (email=? OR SID=?)";
      if ($limitToTeacher) {
        $query .= " AND rights>11";
      }
      $stm = $DBH->prepare($query);
      $stm->execute(array($words[0], $words[0]));
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $possible_users[] = $row;
      }
    } else if (count($words)==1) {
      $query = "SELECT id,LastName,FirstName,email,SID FROM imas_users WHERE (LastName LIKE ? OR FirstName Like ? OR SID LIKE ?)";
      if ($limitToTeacher) {
        $query .= " AND rights>11";
      }
      $stm = $DBH->prepare($query);
      $stm->execute(array($words[0].'%', $words[0].'%', '%'.$words[0].'%'));
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if ($row['SID']==$words[0] || $row['LastName']==$words[0]) {
          $row['priority'] = 1;
        } else {
          $row['priority'] = 0;
        }
        $possible_users[] = $row;
      }
    } else if (count($words)==2) {
      $query = "SELECT id,LastName,FirstName,email,SID FROM imas_users WHERE ((LastName LIKE ? AND FirstName Like ?) OR (LastName LIKE ? AND FirstName Like ?))";
      if ($limitToTeacher) {
        $query .= " AND rights>11";
      }
      $stm = $DBH->prepare($query);
      $stm->execute(array($words[0].'%', $words[1].'%', $words[1].'%', $words[0].'%' ));
      $possible_users = array();
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $row['priority'] = 0;
        if ($row['LastName']==$words[0] || $row['LastName']==$words[1]) {
          $row['priority'] += 1;
        }
        if ($row['FirstName']==$words[0] || $row['FirstName']==$words[1]) {
          $row['priority'] += 1;
        }
        $possible_users[] = $row;
      }
    }
    //only one match - redirect to user details page
    if (count($possible_users)==1) {
      header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/userdetails.php?id=".Sanitize::encodeUrlParam($possible_users[0]['id']));
    	exit;
    }
    //sort by priority

    usort($possible_users, function($a,$b) {
      if ($a['priority']!=$b['priority']) {
        return $b['priority']-$a['priority'];
      } else if ($a['LastName']!=$b['LastName']) {
        return strcmp($a['LastName'],$b['LastName']);
      } else {
        return strcmp($a['FirstName'],$b['FirstName']);
      }
    });

    $page = 'pickuser';
    $curBreadcrumb = $curBreadcrumb . ' <a href="admin2.php">' . _('Admin') . '</a> &gt; ' . $pagetitle;

  } else if (!empty($_GET['findgroup'])) {
    $words = preg_split('/\s+/', trim(preg_replace('/[^\w\s]/','',$_GET['findgroup'])));
    $likearr = array();
    foreach ($words as $v) {
      $likearr[] = '%'.$v.'%';
    }
    $likes = implode(' OR ', array_fill(0, count($words), 'name LIKE ?'));
    $stm = $DBH->prepare("SELECT id,name FROM imas_groups WHERE $likes");
    $stm->execute($likearr);
    $possible_groups = array();
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $row['priority'] = 0;
      foreach ($words as $v) {
        if (preg_match('/\b'.$v.'\b/i', $row['name'])) {
          $row['priority'] ++;
        }
      }
      $possible_groups[] = $row;
    }
    //only one match - redirect to user details page
    if (count($possible_groups)==1) {
      header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/admin2.php?groupdetails=".Sanitize::encodeUrlParam($possible_groups[0]['id']));
      exit;
    }
    //sort by priority
    usort($possible_groups, function($a,$b) {
      if ($a['priority']!=$b['priority']) {
        return $b['priority']-$a['priority'];
      } else {
        return strcmp($a['name'],$b['name']);
      }
    });

    $pagetitle = _("Select Group");
    $page = 'pickgroup';
    $curBreadcrumb = $curBreadcrumb . ' <a href="admin2.php">' . _('Admin') . '</a> &gt; ' . $pagetitle;

  } else {
    $page = 'main';
    $pagetitle = _("Administration");
    $curBreadcrumb = $curBreadcrumb  . _('Admin');
  }

  //load group details data (shows on group admin too)
  if ($page=='groupadmin' || $page=='groupdetails') {
    if ($showgroup==-1) {
      $stm = $DBH->query("SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE rights=0 OR rights=12 ORDER BY LastName,FirstName");
    } else {

      if ($myrights==100) { //include pending users
        $stm = $DBH->prepare("SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE rights > 11 AND rights<>76 AND groupid=:groupid ORDER BY LastName,FirstName");
      } else {
        $stm = $DBH->prepare("SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE rights > 12 AND rights<>76 AND groupid=:groupid ORDER BY LastName,FirstName");
      }
      $stm->execute(array(':groupid'=>$showgroup));
    }
    $groupdata = array();
    while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
      $line['role'] = getRoleNameByRights($line['rights']);
      $line['lastaccess'] = ($line['lastaccess']>0) ? date("n/j/y g:i a",$line['lastaccess']) : "never";
      $groupdata[] = $line;
    }
  }
}

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
require("../header.php");

if ($overwriteBody==1) {
 echo $body;
} else {
  	echo '<div class=breadcrumb>',$curBreadcrumb,'</div>';
  	echo '<div id="headeradmin" class="pagetitle"><h2>',$pagetitle,'</h2></div>';
    if ($page=='groupadmin') {
      //group admin header top navigation
      echo '<div class=cpmid>';

      echo '<span class="column">';
      echo '<a href="../course/manageqset.php?cid=admin">',_('Manage Question Set'), '</a> <br/>';
    	echo '<a href="../course/managelibs.php?cid=admin">', _('Manage Libraries'), '</a>';
      echo '</span>';

      echo '<span class="column">';
      echo '<a href="exportlib.php?cid=admin">',_('Export Libraries'),'</a><br/>';
      echo '<a href="listdiag.php">',_('Diagnostics'),'</a> ';
      echo '</span>';

      echo '<span class="column">';
      echo '<a href="forms.php?from=admin2&action=newadmin&group='.Sanitize::encodeUrlParam($showgroup).'">'._('Add New User').'</a>';
      echo '</span>';

      echo '<div class=clear></div></div>';
    }
    if ($page=='pickuser') {
      if (count($possible_users)==0) {
        echo '<p>'._('No users found').'</p>';
      } else {
        echo '<table class="gb">';
        echo '<thead><tr>';
        echo '<th>'._('Name').'</th>';
        echo '<th>'._('Username').'</th>';
        echo '<th>'._('Email').'</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        $alt = 0;
        foreach ($possible_users as $user) {
          if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
          echo '<td><a href="userdetails.php?id='.Sanitize::encodeUrlParam($user['id']).'">';
          echo Sanitize::encodeStringForDisplay($user['LastName'].', '.$user['FirstName']) . '</a></td>';
          echo '<td>'.Sanitize::encodeStringForDisplay($user['SID']).'</td>';
          echo '<td>'.Sanitize::encodeStringForDisplay($user['email']).'</td>';
          echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
      }

    } else if ($page=='pickgroup') {

      echo '<ul class="nomark">';
      if (count($possible_groups)==0) {
        echo '<li>'._('No group found').'</li>';
      }
      foreach ($possible_groups as $group) {
        echo '<li><a href="admin2.php?groupdetails='.Sanitize::encodeUrlParam($group['id']).'">';
        echo Sanitize::encodeStringForDisplay($group['name']).'</a></li>';
      }
      echo '</ul>';

    } else if ($page=='groupadmin' || $page=='groupdetails') {
      if ($page=='groupadmin') {
        $from = 'admin2';
      } else {
        $from = 'gd'.Sanitize::encodeUrlParam($showgroup);
      }
      if ($showgroup>-1 && $page=='groupdetails') {
        echo '<div class="cpmid">';
        echo '<a href="forms.php?from='.$from.'&action=newadmin&group='.Sanitize::encodeUrlParam($showgroup).'">'._('Add New User').'</a>';
        echo ' | <a href="listdiag.php?show=g'.Sanitize::encodeUrlParam($showgroup).'">'._('Diagnostics').'</a>';
        echo '</div>';
      }
      echo '<table class=gb id="myTable">';
  		echo '<thead><tr>';
      echo '<th>'._('Name').'</th>';
      echo '<th>'._('Username').'</th>';
      echo '<th>'._('Email').'</th>';
      echo '<th>'._('Role').'</th>';
      echo '<th>'._('Last Login').'</th>';
      echo '<th>'._('Edit').'</th>';
      echo '<th>'._('Delete').'</th>';
      echo '</tr></thead>';
      echo '<tbody>';
      $alt = 0;
      foreach ($groupdata as $user) {
          if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
          if ($page=='groupdetails') {
            echo '<td><a href="userdetails.php?group='.$showgroup.'&id='.Sanitize::encodeUrlParam($user['id']).'">';
          } else {
            echo '<td><a href="userdetails.php?id='.Sanitize::encodeUrlParam($user['id']).'">';
          }
          echo Sanitize::encodeStringForDisplay($user['LastName'].', '.$user['FirstName']).'</a></td>';
          echo '<td>'.Sanitize::encodeStringForDisplay($user['SID']).'</td>';
          echo '<td>'.Sanitize::encodeStringForDisplay($user['email']).'</td>';
          echo '<td>'.Sanitize::encodeStringForDisplay($user['role']).'</td>';
          echo '<td>'.Sanitize::encodeStringForDisplay($user['lastaccess']).'</td>';
          echo '<td><a href="forms.php?from='.$from.'&action=chgrights&id='.$user['id'].'">'._('Edit').'</a></td>';
          echo '<td><a href="forms.php?from='.$from.'&action=deladmin&id='.$user['id'].'">'._('Delete').'</a></td>';
          echo '</tr>';
      }
      echo '</tbody></table>';
      echo '<script type="text/javascript">
  		  initSortTable("myTable",Array("S","S","S","S","D",false,false),true);
  		  </script>';

    } else if ($page=='main') {
      //MAIN full admin view

      //top navigation
      echo '<div class=cpmid>';
      echo '<span class="column">';
      echo '<a href="forms.php?from=admin2&action=listgroups">',_('Groups'),'</a> <br/>';
      echo '<a href="../util/utils.php">',_('Utilities'),'</a> <br/>';
      echo '<a href="listdiag.php">',_('Diagnostics'),'</a> ';
      echo '</span>';

      echo '<span class="column">';
      if ($enablebasiclti) {
    		echo '<a href="forms.php?from=admin2&action=listltidomaincred">',_('LTI Provider Creds'),'</a><br/>';
    	}
    	echo '<a href="forms.php?from=admin2&action=listfedpeers">',_('Federation Peers'),'</a><br/>';
    	echo '<a href="externaltools.php?cid=admin">',_('External Tools'),'</a>';
      echo '</span>';

      echo '<span class="column">';
      echo '<a href="../course/manageqset.php?cid=admin">',_('Manage Question Set'), '</a> <br/>';
    	echo '<a href="../course/managelibs.php?cid=admin">', _('Manage Libraries'), '</a>';
      echo '</span>';

      echo '<span class="column">';
      echo '<a href="exportlib.php?cid=admin">',_('Export Libraries'),'</a><br/>';
      echo '<a href="importlib.php?cid=admin">',_('Import Libraries'),'</a>';
      echo '</span>';

      echo '<div class=clear></div></div>';

      //search forms
      echo '<form method="get" action="admin2.php">';
      echo '<p>';
      echo '<span class="form"><label for="findteacher">',_('Find teacher'),'</lable>:</span>';
      echo '<span class="formright"><input name="findteacher" id="findteacher" size=30 /> ';
      echo '<button type="submit">',_('Go'),'</button> </span> <br class="form" />';

      echo '<span class="form"><label for="finduser">',_('Find user'),'</lable>:</span>';
      echo '<span class="formright"><input name="finduser" size=30 /> ';
      echo '<button type="submit">',_('Go'),'</button> ';
      echo '<input type=button value="',_('Add New User'),'" onclick="window.location=\'forms.php?from=admin2&action=newadmin\'">';
      echo '</span> <br class="form" />';
      echo '</p>';
      echo '</form>';

      echo '<form method="get" action="admin2.php"><p>';
      echo '<span class="form"><label for="findgroup">',_('Find group'),'</lable>:</span>';
      echo '<span class="formright"><input name="findgroup" size=30 /> ';
      echo '<button type="submit">',_('Go'),'</button> </span> <br class="form" />';
      echo '</p></form>';

      echo '<script type="text/javascript">$(function() {$("#findteacher").focus();});</script>';

    }

}

require("../footer.php");
