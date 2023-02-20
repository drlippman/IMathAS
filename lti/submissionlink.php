<?php

if (isset($GLOBALS['CFG']['hooks']['lti'])) {
  require_once($CFG['hooks']['lti']);
  /**
   * see ltihooks.php.dist for details
   */
}

function link_to_submission($launch, $localuserid, $localcourse, $db) {
  $role = standardize_role($launch->get_roles());
  $contextid = $launch->get_platform_context_id();
  $platform_id = $launch->get_platform_id();
  $resource_link = $launch->get_resource_link();
  $target = parse_target_link($launch->get_target_link(), $db);
  $targetltiuserid = $launch->get_submission_review_user_id();
  if (empty($targetltiuserid)) {
      echo _('No target user id provided');
      exit;
  }
  if (empty($target) && function_exists('lti_can_handle_launch')) {
    if (lti_can_handle_launch($launch->get_target_link())) {
      $target = ['type'=>'ext'];
    }
  }
  if (empty($target)) {
    echo "Error parsing requested resource";
    exit;
  }

  // look to see if we already know where this link should point
  $link = $db->get_link_assoc($resource_link['id'], $contextid, $platform_id);
  if ($link === null) {
    $lineitemstr = $launch->get_lineitem();
    if ($lineitemstr !== false) {
        $link = $db->get_link_assoc_by_lineitem($lineitemstr, $localcourse->get_id());
    }
  }
  if ($link === null) {
    echo _('Cannot do a submission launch before an initial regular launch');
    exit;
  } 
  // OK, we have a link at this point, so now we'll redirect to it
  if ($link->get_placementtype() == 'assess') {
    $_SESSION['ltiitemtype'] = $link->get_typenum();
    $_SESSION['ltiitemid'] = $link->get_typeid();
    $_SESSION['ltiitemver'] = $localcourse->get_UIver();
    $_SESSION['ltirole'] = strtolower($role);

    $targetuserid = $db->get_local_userid($launch, $role);
    if ($targetuserid == false) {
        echo 'Cannot find target student';
        exit;
    }

    if (empty($localcourse->get_UIver())) {
      $localcourse->set_UIver($db->get_UIver($localcourse->get_courseid()));
    }
    if ($localcourse->get_UIver() == 1) {
      $targetasid = $db->get_old_asid($targetuserid, $link->get_typeid());
      if ($targetasid == false) {
          echo _('This student does not have an assessment record yet');
      }
      header(sprintf('Location: %s/course/gb-viewasid.php?cid=%d&aid=%d&uid=%d&asid=%d',
        $GLOBALS['basesiteurl'],
        $localcourse->get_courseid(),
        $link->get_typeid(),
        $targetuserid,
        $targetasid
      ));
    } else {
      header(sprintf('Location: %s/assess2/gbviewassess.php?cid=%d&aid=%d&uid=%d',
        $GLOBALS['basesiteurl'],
        $localcourse->get_courseid(),
        $link->get_typeid(),
        $targetuserid
      ));
    }
  } else if ($link->get_placementtype() == 'course') {
   
  } else if (function_exists('lti_redirect_submissionreview') && lti_is_reviewable($link->get_placementtype())) {
    $_SESSION['ltiitemtype'] = $link->get_typenum();
    $_SESSION['ltiitemid'] = $link->get_typeid();
    $_SESSION['ltiitemver'] = $localcourse->get_UIver();
    $_SESSION['ltirole'] = strtolower($role);

    lti_redirect_submissionreview($link);
  } else {
    echo 'Unsupported placementtype';
    print_r($link);
    exit;
  }
}
