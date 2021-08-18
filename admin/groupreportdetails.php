<?php
//IMathAS - Group super Details page
//(c) 2019 David Lippman

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
$old = 30; //old courses: no activity in __ days.

if ($myrights < 100 && (($myspecialrights&32)!=32)) {
 	$overwriteBody = 1;
	$body = "You don't have authority to view this page.";
} else if (empty($_GET['id'])) {
	$overwriteBody = 1;
	$body = 'No id provided';
} else {
	$now = time();
	$grp = Sanitize::onlyInt($_GET['id']);
	if ($grp==0) {
      $groupname = _('Default Group');    
    } else {
      $stm = $DBH->prepare("SELECT name FROM imas_groups WHERE id=:id");
      $stm->execute(array(':id'=>$grp));
      $groupname = $stm->fetchColumn(0);
    }
    
	//pull active courses for this group
	//exclude selfenroll and guestaccess courses
	$query = 'SELECT ic.id,ic.ownerid,count(istu.id) AS stucnt,MAX(istu.lastaccess) AS lastactivity 
	  FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id 
	  JOIN imas_students AS istu ON istu.courseid=ic.id WHERE
	  ic.enddate=2000000000 AND ic.available<4 AND iu.groupid=? AND (ic.istemplate&12)=0
	  GROUP BY istu.courseid
	  HAVING MAX(istu.lastaccess)>?
	UNION
	SELECT ic.id,ic.ownerid,count(istu.id) AS stucnt,MAX(istu.lastaccess) AS lastactivity 
	  FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id 
	  JOIN imas_students AS istu ON istu.courseid=ic.id WHERE
	  iu.groupid=? AND ic.available<4 AND ic.enddate<2000000000 AND ic.enddate>?
	  AND (ic.istemplate&12)=0
	  GROUP BY istu.courseid';
	  
	$stm = $DBH->prepare($query);
	$stm->execute(array($grp, $now-24*60*60*$old, $grp, $now));
	$coursedetails = array();
	$totalActiveStu = 0;
	$totalActiveCourses = 0;
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$coursedetails[$row['id']] = $row;
		$totalActiveStu += $row['stucnt'];
		$totalActiveCourses++;
	}
	
	if (count($coursedetails)>0) {
		$activecourseids = array_keys($coursedetails);
	} else {
		$activecourseids = array(0);
	}
	$ph = Sanitize::generateQueryPlaceholders($activecourseids);

	//pull LTI/not for these courses
	$totalLTICourses = 0;
	$totalActiveStuLTI = 0;
	if (count($coursedetails)>0) {
		$stm = $DBH->prepare("SELECT DISTINCT courseid FROM imas_lti_courses WHERE courseid IN ($ph)");
		$stm->execute($activecourseids);
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$coursedetails[$row['courseid']]['isLTI'] = 1;
			$totalLTICourses++;
			$totalActiveStuLTI += $coursedetails[$row['courseid']]['stucnt'];
		}
	}
	
	/*
	//get Group Templates
	$query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_users AS iu ";
	$query .= "ON ic.ownerid=iu.id WHERE iu.groupid=? AND ";
	$query .= "(ic.istemplate&2)=2 AND ic.copyrights=2 AND ic.available<4 ";
	$query .= "ORDER BY ic.name";
	$stm = $DBH->prepare($query);
	$stm->execute(array($grp));
	$grptemplates = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$grptemplates[$row['id']] = $row['name'];
	}
	*/
	//pull template courses
	$stm = $DBH->query("SELECT id,name FROM imas_courses WHERE (istemplate&1)=1 OR (istemplate&2)=2 ORDER BY name");
	$templates = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$templates[$row[0]] = $row[1];
	}
	$templateids = array_keys($templates);
	
	
	//pull templates used for these courses
	$templatecnt = array();
	if (count($coursedetails)>0) {
		$stm = $DBH->prepare("SELECT ancestors FROM imas_courses WHERE id IN ($ph)");
		$stm->execute($activecourseids);
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$templatematches = array_intersect(explode(',', $row['ancestors']), $templateids);
			if (count($templatematches)>0) {
			  $last = $templatematches[count($templatematches)-1];
			  if (!isset($templatecnt[$last])) {
				  $templatecnt[$last] = 1;
			  } else {
				  $templatecnt[$last]++;
			  }
			}
		}
	}
	
	//pull teachers
	$query = "SELECT iu.id,iu.SID,iu.FirstName,iu.LastName,iu.email,iu.rights,";
	$query .= "iu.lastaccess,it.courselist ";
	$query .= "FROM imas_users AS iu LEFT JOIN ";
	$query .= "(SELECT userid,GROUP_CONCAT(courseid) AS courselist ";
	$query .= " FROM imas_teachers WHERE courseid IN ($ph) GROUP BY userid) AS it ";
	$query .= "ON iu.id=it.userid WHERE iu.rights > 12 AND iu.rights<>76 AND iu.rights<>77 ";
	$query .= "AND iu.groupid=? ORDER BY iu.LastName,iu.FirstName";
	$stm = $DBH->prepare($query);
	$stm->execute(array_merge($activecourseids, array($grp)));
	$groupdata = array();
    while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
      $line['role'] = getRoleNameByRights($line['rights']);
      $line['lastaccess'] = ($line['lastaccess']>0) ? date("n/j/y",$line['lastaccess']) : _("Never");
      $thisstucnt = 0;
      $thisownerstucnt = 0;
      $thiscoursecnt = 0;
      $thisLTIcoursecnt = 0;
      $thisownercoursecnt = 0;
      $thisownerLTIcoursecnt = 0;
      if ($line['courselist']!== null && $line['courselist']!=='') {
      	  $courses = explode(',', $line['courselist']);
      	  foreach ($courses as $courseid) {
      	  	  $thisstucnt += $coursedetails[$courseid]['stucnt'];
      	  	  $thiscoursecnt++;
      	  	  if (!empty($coursedetails[$courseid]['isLTI'])) {
      	  	  	 $thisLTIcoursecnt++; 
      	  	  }
      	  	  if ($coursedetails[$courseid]['ownerid']==$line['id']) {
      	  	  	  $thisownerstucnt += $coursedetails[$courseid]['stucnt'];
      	  	  	  $thisownercoursecnt++;
      	  	  	  if (!empty($coursedetails[$courseid]['isLTI'])) {
      	  	  	  	  $thisownerLTIcoursecnt++; 
      	  	  	  }
      	  	  }
      	  }
      }
      $line['stucnt'] = $thisstucnt;
      $line['coursecnt'] = $thiscoursecnt;
      $line['lticoursecnt'] = $thisLTIcoursecnt;
      $line['ownerstucnt'] = $thisownerstucnt;
      $line['ownercoursecnt'] = $thisownercoursecnt;
      $line['ownerlticoursecnt'] = $thisownerLTIcoursecnt;
      $groupdata[] = $line;
    }
}

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js\"></script>\n";
require("../header.php");

if ($overwriteBody==1) {
 echo $body;
} else {
  	echo '<div class=breadcrumb>';
  	echo $breadcrumbbase .' <a href="userreports.php">'._('User Reports').'</a> &gt; ';
  	echo _('Group Detail').'</div>';
  	echo '<div id="headeradmin" class="pagetitle"><h1>'._('Group Detail').'</h1></div>';
  	
  	echo '<h2>'.Sanitize::encodeStringForDisplay($groupname).'</h2>';
  	
  	echo '<p>';
  	echo _('Active Courses') . ': ' . Sanitize::onlyInt($totalActiveCourses);
  	echo ' ('.sprintf(_('%d using LTI'), Sanitize::onlyInt($totalLTICourses)).')<br/>';
  	echo _('Students in Active Courses') . ': ' . Sanitize::onlyInt($totalActiveStu);
  	echo ' ('.sprintf(_('%d in LTI courses'), Sanitize::onlyInt($totalActiveStuLTI)).')';
  	echo '</p>';
  	          
  	/*
  	if (count($grptemplates)>0) {
  		echo '<p>'._('Group Templates').'</p>';
  		echo '<ul>';
  		foreach ($grptemplates as $id=>$name) {
  			echo '<li><a href="../course/course.php?cid='.Sanitize::onlyInt($id).'">';
  			echo Sanitize::encodeStringForDisplay($name);
  			echo '</a></li>';
  		}
  		echo '</ul>';
  	}	
  	*/
  	if (count($templatecnt)>0) {
   		echo '<p>'._('Templates Used in Active Courses').'</p>';
   		echo '<ul>';
   		foreach ($templatecnt as $tid=>$cnt) {
  			echo '<li><a href="../course/course.php?cid='.Sanitize::onlyInt($tid).'">';
  			echo Sanitize::encodeStringForDisplay($templates[$tid]);
  			echo '</a>';
  			echo ' ('.Sanitize::onlyInt($cnt).')</li>';
  		}
  		echo '</ul>';
  	}	

  	
  	echo '<table class=gb id="myTable">';
  	echo '<thead><tr>';
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Username').'</th>';
    echo '<th>'._('Email').'</th>';
    echo '<th>'._('Role').'</th>';
    echo '<th>'._('Last Login').'</th>';
    echo '<th>'._('Active Courses').'</th>';
    echo '<th>'._('Using LTI').'</th>';
    echo '<th>'._('Students').'</th>';
    echo '</tr><thead>';
    
    echo '<tbody>';
    $alt = 0;
    foreach ($groupdata as $r) {
    	if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
    	echo '<td><a href="userreportdetails.php?id='.Sanitize::onlyInt($r['id']).'"><span class="pii-full-name">';
    	echo Sanitize::encodeStringForDisplay($r['LastName'].', '.$r['FirstName']).'</span></a></td>';
    	echo '<td><span class="pii-username">'.Sanitize::encodeStringForDisplay($r['SID']).'</span></td>';
    	echo '<td><span class="pii-email">'.Sanitize::encodeStringForDisplay($r['email']).'</span></td>';
    	echo '<td>'.Sanitize::encodeStringForDisplay($r['role']).'</td>';
    	echo '<td>'.Sanitize::encodeStringForDisplay($r['lastaccess']).'</td>';
    	echo '<td>'.Sanitize::onlyInt($r['coursecnt']);
    	if ($r['coursecnt'] != $r['ownercoursecnt']) {
    		echo ' ('.Sanitize::onlyInt($r['ownercoursecnt']).'<sup>*</sup>)';
    	}
    	echo '</td>';
    	echo '<td>'.Sanitize::onlyInt($r['lticoursecnt']);
    	if ($r['lticoursecnt'] != $r['ownerlticoursecnt']) {
    		echo ' ('.Sanitize::onlyInt($r['ownerlticoursecnt']).'<sup>*</sup>)';
    	}
    	echo '</td>';
    	echo '<td>'.Sanitize::onlyInt($r['stucnt']);
    	if ($r['stucnt'] != $r['ownerstucnt']) {
    		echo ' ('.Sanitize::onlyInt($r['ownerstucnt']).'<sup>*</sup>)';
    	}
    	echo '</td>';
    	echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '<script type="text/javascript">
      initSortTable("myTable",Array("S","S","S","S","D","N","N","N"),true);
      </script>';
    
    echo '<p><sup>*</sup> '._('as course owner').'</p>';
}

echo '<p>&nbsp;</p><p>&nbsp;</p>';
require("../footer.php");
