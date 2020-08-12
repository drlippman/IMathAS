<?php

use \IMSGlobal\LTI;

require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

//Look to see if a hook file is defined, and include if it is
if (isset($GLOBALS['CFG']['hooks']['lti'])) {
    require_once($CFG['hooks']['lti']);
}

function getLTIMenuButton() {
    global $DBH;

    if (!isset($_GET['launchid'])) {
        return '';
    }

    $db = new Imathas_LTI_Database($DBH);
    $launch = LTI\LTI_Message_Launch::from_cache($_GET['launchid'], $db);
    $link = $db->get_link_assoc($resource_link['id'], $contextid, $platform_id);
    $localcourse = $db->get_local_course($contextid, $platform_id);

    $button = '<div class="dropdown inlinediv">
        <button 
        tabindex=0 
        class="dropdown-toggle" 
        data-toggle="dropdown" 
        aria-haspopup="true" 
        aria-expanded="false"
        ><span class="arrow-down">LTI</span></button>
    <div role="menu" class="dropdown-menu ltimenu">';

    if ($link->get_placementtype() == 'assess') {
        $typeid = $link->get_typeid();
        $stm = $DBH->prepare("SELECT name,avail,startdate,enddate,date_by_lti,ver,courseid FROM imas_assessments WHERE id=:id");
        $stm->execute(array(':id'=>$typeid));
        $line = $stm->fetch(PDO::FETCH_ASSOC);
        $cid = Sanitize::courseId($line['courseid']);
        $button .= '<div role=heading class="dropdown-header">';
        $button .= _("Assessment Management"). "</div>";
        $button .= '<ul class="dropdown-ul">';
        if ($line['ver'] > 1) {
            $button .= "<li><a href=\"../assess2/?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Preview assessment")."</a></li>";
            $button .= "<li><a href=\"../course/addassessment2.php?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Settings")."</a></li>";
            $button .= "<li><a href=\"../course/addquestions.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Questions")."</a></li>";        
            $button .= "<li><a href=\"../course/isolateassessgrade.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Grade list")."</a></li>";
            $button .= "<li><a href=\"../course/gb-itemanalysis2.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Item Analysis")."</a></li>";
        } else {
            $button .= "<li><a href=\"../assessment/showtest.php?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Preview assessment")."</a></li>";
            $button .= "<li><a href=\"../course/addassessment.php?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Settings")."</a></li>";
            $button .= "<li><a href=\"../course/addquestions.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Questions")."</a></li>";        
            $button .= "<li><a href=\"../course/isolateassessgrade.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Grade list")."</a></li>";
            $button .= "<li><a href=\"../course/gb-itemanalysis.php?cid=" . Sanitize::courseId($cid) . "&asid=average&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Item Analysis")."</a></li>";
        }
        $button .= '<li><a href="ltisync.php'. Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid).'">'._('Info and LMS Sync').'</li>';
        $button .= "</ul>";

    } else if (function_exists('lti_can_handle_redirect') &&
        lti_can_handle_redirect($link->get_placementtype()) &&
        function_exists('lti_ltimenu')
    ) {
        $button .= lti_ltimenu($link, $launch, $localcourse, $db);
    }

    $button .= '<div role=heading class="dropdown-header">'._('Course Management').'</div>';
    $button .= '<ul class="dropdown-ul">';
    $button .= '<li><a href="../course/listusers.php?cid='.$cid.'">'._('Roster').'</a></ul>';
    $button .= '<li><a href="../course/gradebook.php?cid='.$cid.'">'._('Gradebook').'</a></ul>';
    $button .= '<li><a href="../course/'.$chgassess.'?cid='.$cid.'">'._('Mass Change Assessments').'</a></ul>';
    if (isset($line['date_by_lti']) && $line['date_by_lti']===0) {
        $button .= '<li><a href="../course/masschgdates?cid='.$cid.'">'._('Mass Change Dates').'</a></ul>';
    }
    $button .= '<li><a href="../admin/forms.php?action=modify&cid='.$cid.'&id='.$cid.'">'._('Course Settings').'</a></ul>';
    $button .= '<li><a href="../course/copyitems.php?cid='.$cid.'">'._('Course Items: Copy').'</a></ul>';
    $button .= '<li><a href="../admin/ccexport.php?cid='.$cid.'">'._('Course Items: Export').'</a></ul>';
    $button .= '<li><a href="../course/course.php?cid='.$cid.'">'._('Full Course Contents').'</a></ul>';
    $button .= '</ul>';

    return $button;
}


