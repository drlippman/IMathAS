<?php

function standardize_role($roles) {
  $contextPriorities = [
    'membership'=>3,
    'institution'=>2,
    'system'=>1
  ];
  $instructorRoles = ['Administrator','Faculty','Instructor','ContentDeveloper'];
  $currentRole = 'Learner';
  $currentPriority = 0;
  foreach ($roles as $role) {
    if (in_array($role, $instructorRoles)) {
      $currentRole = 'Instructor';
      $currentPriority = 1;
    } else if (preg_match('~http://purl.imsglobal.org/vocab/lis/v2/(membership|institution|system)(/person)?(#|/)(\w+)~', $role, $m)) {
      if ($contextPriorities[$m[1]] > $currentPriority) {
        $currentPriority = $contextPriorities[$m[1]] ;
        if (in_array($m[4], $instructorRoles)) {
          $currentRole = 'Instructor';
        } else {
          $currentRole = 'Learner';
        }
      }
    }
  }
  return $currentRole;
}

function parse_name_from_launch($data) {
  if (!empty($data['given_name']) || !empty($data['family_name'])) {
    $first = $data['given_name'];
    $last = $data['family_name'];
    return array($first,$last);
  } else if (!empty($data['name'])) {
    $last = $data['name'];
    return array('',$last);
  } else {
    return false;
  }
}
