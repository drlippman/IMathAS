<?php

require('../init.php');

use \IMSGlobal\LTI;

require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

//Look to see if a hook file is defined, and include if it is
if (isset($GLOBALS['CFG']['hooks']['lti'])) {
    require_once($CFG['hooks']['lti']);
}
if (!isset($_GET['launchid'])) {
    echo _('Unable to identify launch information.  Please launch again from the LMS');
    exit;
}

$db = new Imathas_LTI_Database($DBH);
$launch = LTI\LTI_Message_Launch::from_cache($_GET['launchid'], $db);
$contextid = $launch->get_platform_context_id();
$platform_id = $launch->get_platform_id();
$resource_link = $launch->get_resource_link();
$role = standardize_role($launch->get_roles());
$link = $db->get_link_assoc($resource_link['id'], $contextid, $platform_id);
$chgassess = 'chgassessments2.php';

if ($role != 'Instructor') {
  echo _('This menu requires instructor access');
  exit;
}
echo '<div id="ltimenu">';

if ($link->get_placementtype() == 'assess') {
    $typeid = $link->get_typeid();
    $stm = $DBH->prepare("SELECT name,avail,startdate,enddate,date_by_lti,ver,courseid FROM imas_assessments WHERE id=:id");
    $stm->execute(array(':id'=>$typeid));
    $line = $stm->fetch(PDO::FETCH_ASSOC);
    $cid = Sanitize::courseId($line['courseid']);
    echo '<div role=heading class="dropdown-header">';
    echo _("Manage Assessment").': ';
    echo Sanitize::encodeStringForDisplay($line['name']). "</div>";
    echo '<ul class="dropdown-ul">';
    if ($line['ver'] > 1) {
        echo "<li><a href=\"$imasroot/assess2/?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Preview assessment")."</a></li>";
        echo "<li><a href=\"$imasroot/course/addassessment2.php?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Settings")."</a></li>";
        echo "<li><a href=\"$imasroot/course/addquestions2.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Questions")."</a></li>";
        echo "<li><a href=\"$imasroot/course/isolateassessgrade.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Grade list")."</a></li>";
        echo "<li><a href=\"$imasroot/course/gb-itemanalysis2.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Item Analysis")."</a></li>";
    } else {
        echo "<li><a href=\"$imasroot/assessment/showtest.php?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Preview assessment")."</a></li>";
        echo "<li><a href=\"$imasroot/course/addassessment.php?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Settings")."</a></li>";
        echo "<li><a href=\"$imasroot/course/addquestions2.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Questions")."</a></li>";
        echo "<li><a href=\"$imasroot/course/isolateassessgrade.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Grade list")."</a></li>";
        echo "<li><a href=\"$imasroot./course/gb-itemanalysis.php?cid=" . Sanitize::courseId($cid) . "&asid=average&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Item Analysis")."</a></li>";
        $chgassess = 'chgassessments.php';
    }
    echo '<li><a href="'.$imasroot.'/lti/ltiassessinfo.php?launchid='. Sanitize::encodeUrlParam($_GET['launchid']).'">'._('Info and LMS Sync').'</a></li>';
    echo "</ul>";

} else if (function_exists('lti_can_handle_redirect') &&
    lti_can_handle_redirect($link->get_placementtype()) &&
    function_exists('lti_ltimenu')
) {
    $localcourse = $db->get_local_course($contextid, $launch);
    echo lti_ltimenu($link, $launch, $localcourse, $db);
}

echo '<div role=heading class="dropdown-header">'._('Course Management').'</div>';
echo '<ul class="dropdown-ul">';
echo '<li><a href="'.$imasroot.'/course/listusers.php?cid='.$cid.'">'._('Roster').'</a></li>';
echo '<li><a href="'.$imasroot.'/course/gradebook.php?cid='.$cid.'">'._('Gradebook').'</a></li>';
echo '<li><a href="'.$imasroot.'/course/'.$chgassess.'?cid='.$cid.'">'._('Mass Change Assessments').'</a></li>';
if (function_exists('lti_ltimenu_coursemenu')) {
  lti_ltimenu_coursemenu();
}
if (isset($line['date_by_lti']) && $line['date_by_lti']===0) {
    echo '<li><a href="'.$imasroot.'/course/masschgdates?cid='.$cid.'">'._('Mass Change Dates').'</a></li>';
}
echo '<li><a href="'.$imasroot.'/admin/forms.php?action=modify&cid='.$cid.'&id='.$cid.'">'._('Course Settings').'</a></li>';
echo '<li><a href="'.$imasroot.'/course/copyitems.php?cid='.$cid.'">'._('Course Items: Copy').'</a></li>';
echo '<li><a href="'.$imasroot.'/admin/ccexport.php?cid='.$cid.'">'._('Course Items: Export').'</a></li>';
echo '<li><a href="'.$imasroot.'/course/course.php?cid='.$cid.'">'._('Full Course Contents').'</a></li>';
echo '</ul>';
echo '</div>';
