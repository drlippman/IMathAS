<?php

use \IMSGlobal\LTI;

if (isset($GLOBALS['CFG']['hooks']['lti'])) {
  require_once($CFG['hooks']['lti']);
}

/**
 * Display course connection form
 *
 * @param  LTI_Message_Launch $launch
 * @param  Database           $db
 * @param  int                $userid  of current user
 * @return void
 */
function connect_course(LTI\LTI_Message_Launch $launch, LTI\Database $db, int $userid): void {
  global $imasroot,$installname,$coursetheme,$CFG;

  $flexwidth = true;
	$nologo = true;

  $platform_id = $launch->get_platform_id();

  // Figure out what course the LTI link was originally imported from
  $target = parse_target_link($launch->get_target_link(), $db);

  if (empty($target)) {
    echo "Error parsing requested resource";
    exit;
  }
  $sourcecid = $target['refcid'];
  $last_copied_cid = $target['refcid'];

  // Get most recently copied course in LMS if available
  $context_history = $launch->get_platform_context_history();
  if (count($context_history)>0) {
    $cidlookup = $db->get_local_course($context_history[0], $platform_id);
    if ($cidlookup !== null) {
      $last_copied_cid = $cidlookup->get_courseid();
    }
  }

  // See if there are any courses user owns that we could associate with
  list($copycourses,$assoccourses,$sourceUIver) =
    $db->get_potential_courses($target,$last_copied_cid,$userid);

  require('../header.php');

  echo '<form method=post action="setupcourse.php">';
  echo '<input type=hidden name=launchid value="'.$launch->get_launch_id().'"/>';
  echo '<h1>'._('Establish Course Connection').'</h1>';
  echo '<p>'.sprintf(_('Your LMS course is not yet associated with a course on %s.'),$installname).'</p>';
  if (count($assoccourses)>0) {
    echo '<p>'.sprintf(_('You can either have %s create a new course copy for you, or you can link this LMS course with an existing %s course, in which case students will be added to that existing course. '), $installname, $installname).'</p>';
    echo '<p><input type=radio name=linktype value=copy id=linktypecopy checked> ';
    echo '<label for=linktypecopy>'._('Create a copy of a course').'</label>';
  } else {
    echo '<p>'.sprintf(_('%s will make a copy of the course content for you to use.')).'</p>';
    echo '<input type=hidden name=linktype value=copy>';
    echo '<p>';
  }
  echo '<span id=copyselectwrap><br><label for=copyselect>'._('Course to copy:').'</label>';
  echo '<select id=copyselect name=copyselect>';
  foreach ($copycourses as $cid=>$name) {
    echo '<option value="'.$cid.'" '.($last_copied_cid==$cid ? 'selected' : '') . '>';
    echo $cid . ': '.Sanitize::encodeStringForDisplay($name);
    if ($cid == $sourcecid) {
      echo ' ('._('The originally imported course').')';
    } else if ($cid == $last_copied_cid) {
      echo ' ('._('The course your prior LMS course used').')';
    }
  }
  echo '</select></p>';
  if (count($assoccourses)>0) {
    echo '<p><input type=radio name=linktype value=assoc id=linktypeassoc> ';
    echo '<label for=linktypeassoc>'._('Use an existing course').'</label>';
    echo '<span id=assocselectwrap><br><label for=assocselect>'._('Course to use:').'</label>';
    echo '<select id=assocselect name=assocselect>';
    foreach ($assoccourses as $cid=>$name) {
      echo '<option value="'.$cid.'" '.($last_copied_cid==$cid ? 'selected' : '') . '>';
      echo $cid . ': '.Sanitize::encodeStringForDisplay($name);
      if ($cid == $sourcecid) {
        echo ' ('._('The originally imported course').')';
      } else if ($cid == $last_copied_cid) {
        echo ' ('._('The course your prior LMS course used').')';
      }
    }
    echo '</select></p>';
  }
  if ($sourceUIver == 1) {
    echo '<p><label><input type="checkbox" name="usenewassess" checked />';
    echo _('Use new assessment interface (only applies if copying)') .'</label></p>';
  } else {
    echo '<input type=hidden name=usenewassess value=1 />';
  }
  echo '<button type=submit>'._('Continue').'</button>';
  echo '</form>';
  require('../footer.php');
}
