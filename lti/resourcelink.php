<?php

/**
 * TODO:
 *  refactor $localcourse and $link into classes, maybe $target
 *
 */
if (isset($GLOBALS['CFG']['hooks']['lti'])) {
  require_once($CFG['hooks']['bltilaunch']);
  /**
   * Hook should implement:
   *   ext_can_ext_handle_launch($targetlink),
   *    $targetlink is the raw target_link_uri
   *   ext_handle_launch($targetlink, $localcourse, $localuserid, $db, $resource_link_id, $contextid, $platform_id)
   *    $localcourse the linked course info, an array with indices 'courseid' and 'copiedfrom'
   *    function should call $db->make_link_assoc($typeid, $type, $resource_link_id, $contextid, $platform_id)
   *      to set the association, and return array('typeid'=>, 'placementtype'=>, 'typenum'=>)
   *      where placementtype is a short string, and typenum is a tinyint
   *    and call $db->set_or_create_lineitem($launch, $link, $info, $localcourse)
   *      if the item is going to passback a grade.  $info should be array with
   *      indices 'name' and 'ptsposs', and optionally date_by_lti, startdate, enddate
   *   ext_can_handle_redirect($placementtype)
   *   ext_redirect_launch($typeid, $placementtype)
   *    redirect to the content
   */
}

function link_to_resource($launch, $localuserid, $localcourse, $db) {
  $role = standardize_role($launch->get_roles());
  $contextid = $launch->get_platform_context_id();
  $platform_id = $launch->get_platform_id();
  $resource_link = $launch->get_resource_link();
  $target = parse_target_link($launch->get_target_link(), $db);
  if (empty($target)) {
    echo "Error parsing requested resource";
    exit;
  }

  // look to see if we already know where this link should point
  $link = $db->get_link_assoc($resource_link['id'], $contextid, $platform_id);
  if ($link === false) {
    // no link yet - establish one
    if ($target['type'] === 'aid') {
      $sourceaid = $target['refaid'];
      $destcid = $localcourse['courseid'];
      // is an assessment launch
      if ($target['refcid'] === $destcid) {
        // see if aid is in the current course, we just use it
        $link = $db->make_link_assoc($sourceaid,'assess',$resource_link['id'],$contextid,$platform_id);
        $iteminfo = $db->get_assess_info($sourceaid);
        $db->set_or_create_lineitem($launch, $link, $iteminfo, $localcourse);
      } else {
        // need to find the assessment
        $destaid = false;
        if ($target['refcid'] === $localcourse['copiedfrom']) {
          // aid is in the originally copied course - find our copy of it
          $destaid = $db->find_aid_by_immediate_ancestor($sourceaid, $destcid);
        }
        if ($destaid === false) {
          // try looking further back
          $destaid = $db->find_aid_by_ancestor_walkback(
            $sourceaid,
            $target['refcid'],
            $localcourse['copiedfrom'],
            $destcid);
        }
        if ($destaid === false) {
          // can't find assessment - copy it
          require(__DIR__.'/../includes/copycourse.php');
          $destaid = copyassess($sourceaid, $destcid);
        }
        if ($destaid !== false) {
          $link = $db->make_link_assoc($destaid,'assess',$resource_link['id'],$contextid,$platform_id);
          $iteminfo = $db->get_assess_info($destaid);
          $db->set_or_create_lineitem($launch, $link, $iteminfo, $localcourse);
        } else {
          echo 'Error - unable to establish link';
          exit;
        }
      }
    } else if (function_exists('ext_can_ext_handle_launch') && ext_can_ext_handle_launch($launch->get_target_link())) {
      $link = ext_handle_launch($launch->get_target_link(), $localcourse, $localuserid, $db, $resource_link_id, $contextid, $platform_id);
    } else {
      echo 'Unsupported link type';
      print_r($link);
      exit;
    }
  }
  // OK, we have a link at this point, so now we'll redirect to it
  if ($link['placementtype'] == 'assess') {

    // handle due date setting stuff
    if (!isset($link['date_by_lti'])) {
      $link = array_merge($link, $db->get_dates_by_aid($link['typeid']));
    }
    $lms_duedate = $launch->get_due_date();
    // if date is available and no default due date yet, set it
    if ($lms_duedate !== false && ($link['date_by_lti']==1 || $link['date_by_lti']==2)) {
  		if ($role == 'Instructor') {
  			$newdatebylti = 2; //set/keep as instructor-set
  		} else {
  			$newdatebylti = 3; //mark as student-set
  		}
      //no default due date set yet, or is the instructor:  set the default due date
      $db->set_assessment_dates($link['typeid'], $lms_duedate, $newdatebylti);
    }
    if ($lms_duedate !== false && $role == 'Learner') {
      $db->set_or_update_duedate_exception($localuserid, $link, $lms_duedate);
    }
    // if no due date provided, but we're expecting one, throw error
    if ($link['date_by_lti']==1 && $lms_duedate === false) {
      if ($role == 'Instructor') {
  			echo sprintf(_('Your %s course is set to use dates sent by the LMS, but the LMS did not send a date.'), $GLOBALS['installname']);
        exit;
  		} else {
  			echo _('Tell your teacher that the LMS is not sending due dates.');
        exit;
      }
    }

    $_SESSION['ltiitemtype'] = $link['typenum'];
    $_SESSION['ltiitemid'] = $link['typeid'];
    $_SESSION['ltiitemver'] = $localcourse['UIver'];
    $_SESSION['ltirole'] = strtolower($role);

    if (empty($localcourse['UIver'])) {
      $localcourse['UIver'] = $db->get_UIver($localcourse['courseid']);
    }
    if ($localcourse['UIver'] == 1) {
      header(sprintf('Location: %s/assessment/showtext.php?cid=%d&aid=%d&ltilaunch=true',
        $GLOBALS['basesiteurl'],
        $localcourse['courseid'],
        $link['typeid']
      ));
    } else {
      header(sprintf('Location: %s/assess2/?cid=%d&aid=%d',
        $GLOBALS['basesiteurl'],
        $localcourse['courseid'],
        $link['typeid']
      ));
    }
  } else if ($link['placementtype'] == 'course') {
    $_SESSION['ltiitemtype'] = $link['typenum'];
    $_SESSION['ltiitemid'] = $localcourse['courseid'];
    $_SESSION['ltiitemver'] = $localcourse['UIver'];
    $_SESSION['ltirole'] = strtolower($role);

    header(sprintf('Location: %s/course/course.php?cid=%d',
      $GLOBALS['basesiteurl'],
      $localcourse['courseid']
    ));
  } else if (function_exists('ext_can_handle_redirect') && ext_can_handle_redirect($link['placementtype'])) {
    $_SESSION['ltiitemtype'] = $link['typenum'];
    $_SESSION['ltiitemid'] = $link['typeid'];
    $_SESSION['ltiitemver'] = $localcourse['UIver'];
    $_SESSION['ltirole'] = strtolower($role);

    ext_redirect_launch($link['typeid'], $link['placementtype']);
  } else {
    echo 'Unsupported placementtype';
    print_r($link);
    exit;
  }
}
