<?php

function getCourseOpts($send, $selected=-1) {
  global $DBH, $userid;
  $course_array = array();
  $query = "SELECT i_c.id,i_c.name,i_c.msgset,2 AS userrole,imas_teachers.hidefromcourselist,";
  $query .= "IF(UNIX_TIMESTAMP()<i_c.startdate OR UNIX_TIMESTAMP()>i_c.enddate,0,1) as active ";
  $query .= "FROM imas_courses AS i_c JOIN imas_teachers ON ";
  $query .= "i_c.id=imas_teachers.courseid WHERE imas_teachers.userid=:userid ";
  if ($send) {
    $query .= "AND imas_teachers.hidefromcourselist=0 ";
  }
  $query .= "UNION SELECT i_c.id,i_c.name,i_c.msgset,1 AS userrole,imas_tutors.hidefromcourselist,";
  $query .= "IF(UNIX_TIMESTAMP()<i_c.startdate OR UNIX_TIMESTAMP()>i_c.enddate,0,1) as active ";
  $query .= "FROM imas_courses AS i_c JOIN imas_tutors ON ";
  $query .= "i_c.id=imas_tutors.courseid WHERE imas_tutors.userid=:userid2 ";
  if ($send) {
    $query .= "AND imas_tutors.hidefromcourselist=0 ";
  }
  $query .= "UNION SELECT i_c.id,i_c.name,i_c.msgset,0 AS userrole,imas_students.hidefromcourselist,";
  $query .= "IF(UNIX_TIMESTAMP()<i_c.startdate OR UNIX_TIMESTAMP()>i_c.enddate,0,1) as active ";
  $query .= "FROM imas_courses AS i_c JOIN imas_students ON ";
  $query .= "i_c.id=imas_students.courseid WHERE imas_students.userid=:userid3 ";
  if ($send) {
    $query .= "AND imas_students.hidefromcourselist=0 AND MOD(i_c.msgset,5)<3 ";
  } else {
    $query .= "AND MOD(i_c.msgset,5)<4 ";
  }
  $stm = $DBH->prepare($query);
  $stm->execute(array(':userid'=>$userid, ':userid2'=>$userid, ':userid3'=>$userid));
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($course_array[$row['userrole']])) {
      $course_array[$row['userrole']] = array();
    }
    $course_array[$row['userrole']][] = $row;
  }
  $courseopts = '';
  for ($i=2;$i>=0;$i--) {
    if (isset($course_array[$i])) {
      uasort($course_array[$i], function($a,$b) {
        if ($a['hidefromcourselist'] != $b['hidefromcourselist']) {  // hidden
          return $a['hidefromcourselist'] - $b['hidefromcourselist'];
        } else if ($a['active'] != $b['active']) {
          return $b['active'] - $a['active'];
        } else {
          return strnatcasecmp($a['name'],$b['name']);
        }
      });
      $courseopts .= '<optgroup label="';
      if ($i==2) { $courseopts .= _("Teaching"); }
      else if ($i==1) { $courseopts .= _("Tutoring"); }
      else if ($i==0) { $courseopts .= _("Student"); }
      $courseopts .= '">';
      foreach ($course_array[$i] as $r) {
        if ($r['hidefromcourselist']==1) {
          $prefix = _('Hidden: ');
        } else if ($r['active']==0) {
          $prefix = _('Inactive: ');
        } else {
          $prefix = '';
        }
        $courseopts .= '<option value="'.Sanitize::encodeStringForDisplay($r['id']).'"';
        if ($r['id'] == $selected) {
          $courseopts .= ' selected=1';
        }
        $courseopts .= '>'.Sanitize::encodeStringForDisplay($prefix . $r['name']).'</option>';
      }
      $courseopts .= '</optgroup>';
    }
  }
  return $courseopts;
}
