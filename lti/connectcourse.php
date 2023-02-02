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
  global $imasroot,$staticroot,$installname,$coursetheme,$CFG;

  $flexwidth = true;
	$nologo = true;

  $platform_id = $launch->get_platform_id();
  $contexttitle = $launch->get_platform_context_title();

  // Figure out what course the LTI link was originally imported from
  $target = parse_target_link($launch->get_target_link(), $db);

  if (empty($target)) {
    $assoccourses = $db->get_all_courses($userid);
    if (count($assoccourses)==0) {
        echo sprintf(_("No courses found. Create a course on %s first."), $installname);
        exit;
    }
    $last_copied_cid = 0;
  } else if ($target['refcid'] === null) {
    // assessment ID existed, but wasn't able to find the course.
    echo sprintf(_("This link referenced assessment ID %d, but that assessment no longer exists on %s, making it impossible to know which course to copy or associate with. Try another link in your LMS course. If none work, you may need to do a fresh export/import."), $target['refaid'], $installname);
    exit;
  } else {
    $sourcecid = $target['refcid'];
    $last_copied_cid = $target['refcid'];

    // Get most recently copied course in LMS if available
    $context_history = $launch->get_platform_context_history();
    if (count($context_history)>0) {
        $cidlookup = $db->get_local_course($context_history[0], $launch);
        if ($cidlookup !== null) {
        $last_copied_cid = $cidlookup->get_courseid();
        }
    }

    // See if there are any courses user owns that we could associate with
    list($copycourses,$assoccourses,$sourceUIver) =
        $db->get_potential_courses($target,$last_copied_cid,$userid);
  }
  require('../header.php');

  echo '<form method=post action="setupcourse.php">';
  echo '<input type=hidden name=launchid value="'.$launch->get_launch_id().'"/>';
  echo '<h1>'._('Establish Course Connection').'</h1>';
  echo '<p>'.sprintf(_('Your LMS course is not yet linked with a course on %s.'),$installname).'</p>';

  if (empty($target)) {
    echo '<p>'.sprintf(_('If you have many assessments to link to, it will be faster to go to %s and use the Export feature to create an export cartridge and import that into the LMS. '), $installname).'<br>';
    echo _('If you wish to do that, <em>do that first</em> before establishing a course connection.').'</p>';

    echo '<p>'.sprintf(_('To continue connecting, select the %s course to associate this LMS course with'), $installname).'</p>';
    echo '<input type=hidden name=linktype value=assoc >';
    echo '<p><label for=assocselect>'._('Course to use:').'</label>';
    echo '<select id=assocselect name=assocselect>';
    foreach ($assoccourses as $cid=>$name) {
        echo '<option value="'.$cid.'" '.($last_copied_cid==$cid ? 'selected' : '') . '>';
        echo $cid . ': '.Sanitize::encodeStringForDisplay($name) . '</option>';
    }
    echo '</select></p>';
    echo '<p>',_('Students will be enrolled into the selected course when they open an assignment.'),'<p>';

  } else {

    if (count($assoccourses)>0) {
        echo '<p>'.sprintf(_('You can either have %s create a new course copy for you, or you can link this LMS course with an existing %s course. '), $installname, $installname).'</p>';
        echo '<p><input type=radio name=linktype value=copy id=linktypecopy checked> ';
        echo '<label for=linktypecopy>'._('Create a copy of a course').'</label>';
    } else {
        echo '<p>'.sprintf(_('%s will make a copy of the course content for you to use.'), $installname).'</p>';
        echo '<input type=hidden name=linktype value=copy>';
        echo '<p>';
    }
    echo '<span class=forcopy><br><label for=copyselect>'._('Course to copy:').'</label>';
    echo '<select id=copyselect name=copyselect>';
    foreach ($copycourses as $cid=>$name) {
        echo '<option value="'.$cid.'" '.($last_copied_cid==$cid ? 'selected' : '') . '>';
        echo $cid . ': '.Sanitize::encodeStringForDisplay($name);
        if ($cid == $sourcecid) {
            echo ' ('._('The originally imported course').')';
        } else if ($cid == $last_copied_cid) {
            echo ' ('._('The course your prior LMS course used').')';
        }
        echo '</option>';
    }
    echo '</select></p>';
    if (count($assoccourses)>0) {
        echo '<p><input type=radio name=linktype value=assoc id=linktypeassoc> ';
        echo '<label for=linktypeassoc>'._('Use an existing course').'</label>';
        echo '<span class=forassoc style="display:none;"><br><label for=assocselect>'._('Course to use:').'</label>';
        echo '<select id=assocselect name=assocselect>';
        foreach ($assoccourses as $cid=>$name) {
            echo '<option value="'.$cid.'" '.($last_copied_cid==$cid ? 'selected' : '') . '>';
            echo $cid . ': '.Sanitize::encodeStringForDisplay($name);
            if ($cid == $sourcecid) {
                echo ' ('._('The originally imported course').')';
            } else if ($cid == $last_copied_cid) {
                echo ' ('._('The course your prior LMS course used').')';
            }
            echo '</option>';
        }
        echo '</select></p>';
        echo '<p class=forassoc style="display:none;">',_('Students will be enrolled into the selected course when they open an assignment.'),'<p>';
    }
    if ($sourceUIver == 1) {
        echo '<p class=forcopy><label><input type="checkbox" name="usenewassess" checked />';
        echo _('Use new assessment interface') .'</label></p>';
    } else {
        echo '<input type=hidden name=usenewassess value=1 />';
    }
    echo '<p class=forcopy>', sprintf(_('A new course on %s titled <b>%s</b> will be created.'), 
            $installname,
            Sanitize::encodeStringForDisplay($contexttitle)
        ), '</p>';
  }
  echo '<button type=submit>'._('Continue').'</button>';
  echo '</form>';
  echo '<script>
  $(function() {
      $("input[name=linktype]").on("change", function () {
        $(".forcopy").toggle($("#linktypecopy").is(":checked"));
        $(".forassoc").toggle($("#linktypeassoc").is(":checked"));
      });
  });</script>';
  require('../footer.php');
}
