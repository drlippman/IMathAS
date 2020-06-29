<?php

function link_to_resource($launch, $localcourse, $db) {
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
        } else {
          echo 'Error - unable to establish link';
          exit;
        }
      }
    } else {
      echo 'Unsupported link type';
      exit;
    }
  }

  // OK, we have a link at this point, so now we'll redirect to it
  if ($link['placementtype'] == 'assess') {

    // TODO handle due date stuff

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
    header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid");
    header(sprintf('Location: %s/course/course.php?cid=%d',
      $GLOBALS['basesiteurl'],
      $localcourse['courseid']
    ));
  } else {
    echo 'Unsupported placementtype';
    exit;
  }
}
