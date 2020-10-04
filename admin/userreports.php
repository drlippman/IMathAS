<?php
//IMathAS: User reports front page view
//(c) David Lippman 2018 for Lumen Learning

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

$curBreadcrumb = $breadcrumbbase;

if ($myrights < 100 && (($myspecialrights&32)!=32)) {
 	$overwriteBody = 1;
	$body = "You don't have authority to view this page.";
} else {
  //figure out which page we want to show, or redirect if needed
  if (isset($_GET['groupdetails'])) {
    //show the list of group users page
    $showgroup = Sanitize::onlyInt($_GET['groupdetails']);
    if ($showgroup==-1) {
      $groupname = _('Pending Users');
    } else if ($showgroup==0) {
      $groupname = _('Default Group');    
    } else {
      $stm = $DBH->prepare("SELECT name FROM imas_groups WHERE id=:id");
      $stm->execute(array(':id'=>$showgroup));
      $groupname = $stm->fetchColumn(0);
    }
    $page = 'groupdetails';
    $pagetitle = _("Group Members").': '. Sanitize::encodeStringForDisplay($groupname);

    $curBreadcrumb = $curBreadcrumb . ' <a href="userreports.php">' . _('User Reports') . '</a> &gt; ' . _("Group Members");

  } else if (!empty($_GET['findteacher'])) {
    require("../includes/userutils.php");
    
    //search for a user (teacher or regular)
    $limitToTeacher = true;
    $searchterm = $_GET['findteacher'];
    $pagetitle = _("Select Teacher");
    $possible_users = searchForUser($searchterm, $limitToTeacher);
    
    //only one match - redirect to user details page
    if (count($possible_users)==1) {
    	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/userreportdetails.php?id=".Sanitize::encodeUrlParam($possible_users[0]['id']). "&r=" .Sanitize::randomQueryStringParam());
    	exit;
    }
    
    $page = 'pickuser';
    $curBreadcrumb = $curBreadcrumb . ' <a href="userreports.php">' . _('User Reports') . '</a> &gt; ' . $pagetitle;

  } else if (!empty($_GET['listgroups'])) {
  	$stm = $DBH->prepare("SELECT ig.id,ig.name,COUNT(iu.id) as ucnt FROM imas_groups AS ig LEFT JOIN imas_users AS iu ON ig.id=iu.groupid GROUP BY ig.id ORDER BY ig.name");
    $stm->execute($likearr);
    $possible_groups = array();
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $row['priority'] = 0;
      $possible_groups[] = $row;
    }
    $pagetitle = _("Select Group");
    $page = 'pickgroup';
    $curBreadcrumb = $curBreadcrumb . ' <a href="userreports.php">' . _('User Reports') . '</a> &gt; ' . $pagetitle;
  } else if (!empty($_GET['findgroup'])) {
    $hasp1 = false;
    $findGroup = Sanitize::stripHtmlTags($_GET['findgroup']);
    $words = preg_split('/\s+/', trim(preg_replace('/[^\w\s]/','',$findGroup)));
    $likearr = array();
    foreach ($words as $v) {
      $likearr[] = '%'.$v.'%';
    }
    $likes = implode(' OR ', array_fill(0, count($words), 'ig.name LIKE ?'));                                                                                              
    $stm = $DBH->prepare("SELECT ig.id,ig.name,COUNT(iu.id) as ucnt FROM imas_groups AS ig LEFT JOIN imas_users AS iu ON ig.id=iu.groupid WHERE $likes GROUP BY ig.id ORDER BY ig.name");
    $stm->execute($likearr);
    $possible_groups = array();
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $row['priority'] = 0;
      foreach ($words as $v) {
        if (preg_match('/\b'.$v.'\b/i', $row['name'])) {
          $hasp1 = true;
          $row['priority']++;
        }
      }
      $possible_groups[] = $row;
    }
    //only one match - redirect to user details page
    if (count($possible_groups)==1) {
      header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/groupreportdetails.php?id=".Sanitize::encodeUrlParam($possible_groups[0]['id']). "&r=" .Sanitize::randomQueryStringParam());
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
    $curBreadcrumb = $curBreadcrumb . ' <a href="userreports.php">' . _('User Reports') . '</a> &gt; ' . $pagetitle;

  } else {
    $page = 'main';
    $pagetitle = _("User Reports");
    $curBreadcrumb = $curBreadcrumb  . _('User Reports');
  }

}

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js\"></script>\n";
require("../header.php");

if ($overwriteBody==1) {
 echo $body;
} else {
  	echo '<div class=breadcrumb>',$curBreadcrumb,'</div>';
  	echo '<div id="headeradmin" class="pagetitle"><h1>',$pagetitle,'</h1></div>';
  	
  	//top navigation
  	echo '<div class=cpmid>';
  	echo '<span class="column">';
  	echo '<a href="userreports.php?listgroups=true">',_('Groups List'),'</a> <br/>';
  	echo '<a href="../util/listnewteachers.php">',_('New Instructors Report');
  	echo '</span><span class="column">';
  	echo '<a href="forms.php?from=userreports&action=newadmin&group='.Sanitize::encodeUrlParam($showgroup).'">'._('Add New User').'</a>';
    echo '<br/><a href="../util/batchcreateinstr.php?from=userreports">'._('Batch Add Instructors').'</a>';
  	echo '</span>';
  	echo '<div class=clear></div></div>';
   
    if ($page=='pickuser') {
      if (count($possible_users)==0) {
        echo '<p>'._('No users found').'</p>';
      } else {
      	if ($hasp1) {
      		echo '<style type="text/css"> tr.p0 {color:#999;} tr.p2 {color:#060;}</style>';
      	}
        echo '<table class="gb" id="myTable">';
        echo '<thead><tr>';
        echo '<th>'._('Name').'</th>';
        echo '<th>'._('Username').'</th>';
        echo '<th>'._('Email').'</th>';
        echo '<th>'._('Role').'</th>';
        echo '<th>'._('Group').'</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        $alt = 0;
        foreach ($possible_users as $user) {
          $priorityclass = "p".Sanitize::onlyInt($user['priority']);
          if ($alt==0) {echo "<tr class=\"even $priorityclass\">"; $alt=1;} else {echo "<tr class=\"odd $priorityclass\">"; $alt=0;}
          echo '<td><a href="userreportdetails.php?id='.Sanitize::encodeUrlParam($user['id']).'">';
          echo Sanitize::encodeStringForDisplay($user['LastName'].', '.$user['FirstName']) . '</a></td>';
          echo '<td>'.Sanitize::encodeStringForDisplay($user['SID']).'</td>';
          echo '<td>'.Sanitize::encodeStringForDisplay($user['email']).'</td>';
          echo '<td>'.Sanitize::encodeStringForDisplay(getRoleNameByRights($user['rights'])).'</td>';
          if ($user['name']===null) {
          	  echo '<td></td>';
          } else {
          	  echo '<td>'.Sanitize::encodeStringForDisplay($user['name']).'</td>';
          }
          echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        if (count($possible_users)==200) {
        	echo '<p>'._('List cut off at 200 options.  Try narrowing the search').'</p>';
        }
        echo '<script type="text/javascript">
          initSortTable("myTable",Array("S","S","S","S","S"),true);
          </script>';
      }

    } else if ($page=='pickgroup') {

      if ($hasp1) {
      	echo '<style type="text/css"> .p0 {opacity:.5} .p2 a,.p3 a,.p4 a {color:#060;}</style>';
      	//echo '<style type="text/css"> li.p0 {opacity:.5} li.p2 a,li.p3 a,li.p4 a {color:#060;}</style>';
      }
      	echo '<table class="gb" id="myTable">';
        echo '<thead><tr>';
        echo '<th>'._('Group').'</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        $alt = 0;
        foreach ($possible_groups as $group) {
          $grpid = Sanitize::onlyInt($group['id']);
          $priorityclass = "p".Sanitize::onlyInt($group['priority']);
          if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
          echo '<td class="'.$priorityclass.'"><a href="groupreportdetails.php?id='.$grpid.'">';
          echo Sanitize::encodeStringForDisplay($group['name']).'</a> ';
          echo '('.Sanitize::onlyInt($group['ucnt']).')';
          echo '</td>';
          echo '</tr>';
          
        }
        echo '</tbody>';
        echo '</table>';
        echo '<script type="text/javascript">
          initSortTable("myTable",Array("S"),true);
          </script>';

    } else if ($page=='groupdetails') {
      $from = 'gd'.Sanitize::encodeUrlParam($showgroup);
      echo '<table class=gb id="myTable">';
  		echo '<thead><tr>';
      echo '<th>'._('Name').'</th>';
      echo '<th>'._('Username').'</th>';
      echo '<th>'._('Email').'</th>';
      echo '<th>'._('Role').'</th>';
      echo '<th>'._('Last Login').'</th>';
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
          echo '</tr>';
      }
      echo '</tbody></table>';
      echo '<script type="text/javascript">
  		  initSortTable("myTable",Array("S","S","S","S","D",true);
  		  </script>';

    } else if ($page=='main') {
      //MAIN full admin view

      //search forms
      echo '<form method="get" action="userreports.php">';
      echo '<p>';
      echo '<span class="form"><label for="findteacher">',_('Find teacher'),'</lable>:</span>';
      echo '<span class="formright"><input name="findteacher" id="findteacher" size=30 /> ';
      echo '<button type="submit">',_('Go'),'</button> </span> <br class="form" />';
      echo '</p>';
      echo '</form>';

      echo '<form method="get" action="userreports.php"><p>';
      echo '<span class="form"><label for="findgroup">',_('Find group'),'</lable>:</span>';
      echo '<span class="formright"><input name="findgroup" size=30 /> ';
      echo '<button type="submit">',_('Go'),'</button> </span> <br class="form" />';
      echo '</p></form>';

      echo '<script type="text/javascript">$(function() {$("#findteacher").focus();});</script>';

    }

}

require("../footer.php");
