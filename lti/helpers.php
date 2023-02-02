<?php

if (isset($GLOBALS['CFG']['hooks']['lti'])) {
    require_once($CFG['hooks']['lti']);
    /**
     * see ltihooks.php.dist for details
     */
}

/**
 * Return a standardized role value based on an array of roles provided by the
 * LMS
 *
 * @param  array  $roles  roles array provided by the LMS
 * @return string  'Learner' or 'Instructor'
 */
function standardize_role(array $roles): string {
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

/**
 * Parses the launch data to find the user's name, if provided
 * @param  array  $data launch data
 * @return false|array of (firstname,lastname)
 */
function parse_name_from_launch(array $data) {
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

/**
 * Parse target link to determine what it's pointing to
 * @param  string   $targetlink  the target_link_uri the LMS provided
 * @param  Database $db
 * @return array  ('type'=>, 'refcid'=> ) and possibly others
 */
function parse_target_link(string $targetlink, \IMSGlobal\LTI\Database $db): array {
  $linkquery = parse_url($targetlink, PHP_URL_QUERY);
  if ($linkquery === null) {
    return [];
  }
  parse_str($linkquery, $param);

  if (!empty($param['refaid'])) {
    $out = ['type'=>'aid', 'refaid'=>$param['refaid'], 'refcid'=>$param['refcid']];
  } else if (!empty($param['refblock'])) {
    $out = ['type'=>'block', 'refblock'=>$param['refblock'], 'refcid'=>$param['refcid']];
  } else if (!empty($param['custom_place_aid'])) {
    $refcid = $db->get_course_from_aid($param['custom_place_aid']);
    $out = ['type'=>'aid', 'refaid'=>$param['custom_place_aid'], 'refcid'=>$refcid];
  } else if (!empty($param['place_aid'])) {
    $refcid = $db->get_course_from_aid($param['place_aid']);
    $out = ['type'=>'aid', 'refaid'=>$param['place_aid'], 'refcid'=>$refcid];
  } else if (function_exists('lti_parse_target_link')) {
    $out = lti_parse_target_link($targetlink);
  } else {
    $out = array();
  }
  return $out;
}
