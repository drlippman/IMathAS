<?php

if (isset($GLOBALS['CFG']['hooks']['lti'])) {
  require_once($CFG['hooks']['lti']);
  /**
   * see ltihooks.php.dist for details
   */
}

function link_to_resource($launch, $localuserid, $localcourse, $db) {
  $role = standardize_role($launch->get_roles());
  $contextid = $launch->get_platform_context_id();
  $platform_id = $launch->get_platform_id();
  $resource_link = $launch->get_resource_link();
  $target = parse_target_link($launch->get_target_link(), $db);

  if (empty($target)) {
    echo "Error parsing requested resource. Make sure that the launch URL contains a resource identifier. If it does not, you will either need to use the export/import process to bring in the link, or use the LMS's content selection / deep linking tools.";
    exit;
  }

  // look to see if we already know where this link should point
  $link = $db->get_link_assoc($resource_link['id'], $contextid, $platform_id);
  if ($link === null) {
    // no link yet - establish one
    if ($target['type'] === 'aid') {
      $sourceaid = $target['refaid'];
      $destcid = $localcourse->get_courseid();
      // is an assessment launch
      if ($target['refcid'] == $destcid) {
        // see if aid is in the current course, we just use it
        $link = $db->make_link_assoc($sourceaid,'assess',$resource_link['id'],$contextid,$platform_id);
        $iteminfo = $db->get_assess_info($sourceaid);
        $db->set_or_create_lineitem($launch, $link, $iteminfo, $localcourse);
      } else {
        // need to find the assessment
        $destaid = false;
        if ($target['refcid'] === null) {
            // if assessment has been deleted so refcid is null
            // see if we already have a descendant of the assessment
            $destaid = $db->find_aid_by_loose_ancestor($sourceaid, $destcid);
        } else if ($target['refcid'] == $localcourse->get_copiedfrom()) {
          // aid is in the originally copied course - find our copy of it
          $destaid = $db->find_aid_by_immediate_ancestor($sourceaid, $destcid);
        }
        if ($destaid === false && $target['refcid'] !== null) {
          // try looking further back
          $destaid = $db->find_aid_by_ancestor_walkback(
            $sourceaid,
            $target['refcid'],
            $localcourse->get_copiedfrom(),
            $destcid);
        }
        if ($destaid === false) {
          // can't find assessment - copy it
          require_once(__DIR__.'/../includes/copycourse.php');
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
    } else if (function_exists('lti_can_ext_handle_launch') && lti_can_ext_handle_launch($launch->get_target_link())) {
      $link = lti_handle_launch($launch, $localcourse, $localuserid, $db);
    } else {
      echo 'Unsupported link type';
      print_r($link);
      exit;
    }
  } else {
    $db->set_or_update_lineitem($launch, $link, $localcourse);
  }
  // OK, we have a link at this point, so now we'll redirect to it
  if ($link->get_placementtype() == 'assess') {

    // handle due date setting stuff
    if ($link->get_date_by_lti() === null) {
      $dates = $db->get_dates_by_aid($link->get_typeid());
      $link->set_date_by_lti($dates['date_by_lti'])
        ->set_startdate($dates['startdate'])
        ->set_enddate($dates['enddate']);
    }
    $lms_duedate = $launch->get_due_date();
    // if date is available and no default due date yet, set it
    if ($lms_duedate !== false && ($link->get_date_by_lti()==1 || $link->get_date_by_lti()==2)) {
  		if ($role == 'Instructor') {
  			$newdatebylti = 2; //set/keep as instructor-set
  		} else {
  			$newdatebylti = 3; //mark as student-set
  		}
      //no default due date set yet, or is the instructor:  set the default due date
      $db->set_assessment_dates($link->get_typeid(), $lms_duedate, $newdatebylti);
    }
    if ($lms_duedate !== false && $role == 'Learner') {
      $db->set_or_update_duedate_exception($localuserid, $link, $lms_duedate);
    }
    // if no due date provided, but we're expecting one, throw error
    if ($link->get_date_by_lti()==1 && $lms_duedate === false) {
      if ($role == 'Instructor') {
  			echo sprintf(_('Your %s course is set to use dates sent by the LMS, but the LMS did not send a date.'), $GLOBALS['installname']);
        exit;
  		} else {
  			echo _('Tell your teacher that the LMS is not sending due dates.');
        exit;
      }
    }

    $_SESSION['ltiitemtype'] = $link->get_typenum();
    $_SESSION['ltiitemid'] = $link->get_typeid();
    $_SESSION['ltiitemver'] = $localcourse->get_UIver();
    $_SESSION['ltirole'] = strtolower($role);

    if (empty($localcourse->get_UIver())) {
      $localcourse->set_UIver($db->get_UIver($localcourse->get_courseid()));
    }
    if ($localcourse->get_UIver() == 1) {
      header(sprintf('Location: %s/assessment/showtext.php?cid=%d&aid=%d&ltilaunch=true',
        $GLOBALS['basesiteurl'],
        $localcourse->get_courseid(),
        $link->get_typeid()
      ));
    } else {
      header(sprintf('Location: %s/assess2/?cid=%d&aid=%d',
        $GLOBALS['basesiteurl'],
        $localcourse->get_courseid(),
        $link->get_typeid()
      ));
    }
  } else if ($link->get_placementtype() == 'course') {
    $_SESSION['ltiitemtype'] = $link->get_typenum();
    $_SESSION['ltiitemid'] = $localcourse->get_courseid();
    $_SESSION['ltiitemver'] = $localcourse->get_UIver();
    $_SESSION['ltirole'] = strtolower($role);

    header(sprintf('Location: %s/course/course.php?cid=%d',
      $GLOBALS['basesiteurl'],
      $localcourse->get_courseid()
    ));
  } else if (function_exists('lti_can_handle_redirect') && lti_can_handle_redirect($link->get_placementtype())) {
    $_SESSION['ltiitemtype'] = $link->get_typenum();
    $_SESSION['ltiitemid'] = $link->get_typeid();
    $_SESSION['ltiitemver'] = $localcourse->get_UIver();
    $_SESSION['ltirole'] = strtolower($role);

    lti_redirect_launch($link);
  } else {
    echo 'Unsupported placementtype';
    print_r($link);
    exit;
  }
}
