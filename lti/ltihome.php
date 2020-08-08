<?php
//IMathAS: LTI instructor home page
//(c) 2020 David Lippman

use \IMSGlobal\LTI;

require("../init.php");
if (!isset($_SESSION['ltirole']) || $_SESSION['ltirole']!='instructor') {
	echo _("Not authorized to view this page");
	exit;
}

if (!isset($_GET['launchid'])) {
    // this should have gotten inserted by general.js out of sessionstorage
    echo _('Missing launch id');
    exit;
}

require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

//Look to see if a hook file is defined, and include if it is
if (isset($GLOBALS['CFG']['hooks']['lti'])) {
  require_once($CFG['hooks']['lti']);
}

$db = new Imathas_LTI_Database($DBH);
$launch = LTI\LTI_Message_Launch::from_cache($_GET['launchid'], $db);
$contextid = $launch->get_platform_context_id();
$platform_id = $launch->get_platform_id();
$resource_link = $launch->get_resource_link();
$link = $db->get_link_assoc($resource_link['id'], $contextid, $platform_id);
$localcourse = $db->get_local_course($contextid, $platform_id);

//Handle Postbacks
if (!empty($_POST['makelineitem'])) {
	// want to create a line item
	$iteminfo = false;
	if ($link->get_placementtype() == 'assess') {
		$iteminfo = $db->get_assess_info($link->get_typeid());
	} else if (function_exists('lti_get_item_info')) {
		$iteminfo = lti_get_item_info($link);
	}
	if ($iteminfo !== false) {
		$result = $db->set_or_create_lineitem($launch, $link, $iteminfo, $localcourse);
		if ($result === false) {
			$lineitemmsg = _('Failed to create the grade column.');
		} else {
			$lineitemmsg = _('Successfully created the grade column.');
		}
	}
} else if (!empty($_POST['resync']) || !empty($_POST['resyncall'])) {
    $includeempty = !empty($_POST['resyncall']);
    $aid = $link->get_typeid();
    $iteminfo = $db->get_assess_info($aid);
    $scores = $db->get_assess_grades(
        $localcourse->get_courseid(),
        $aid, 
        $platform_id,
        $iteminfo['submitby']=='by_assessment',
        $includeempty
    );
    $lineitem = $launch->get_lineitem();
    require(__DIR__ . '/../includes/ltioutcomes.php');
    $cnt = 0;
    foreach ($scores as $scoredata) {
        $sourcedid = 'LTI1.3:|:' . $scoredata['ltiuserid'] . 
            ':|:' . $lineitem . ':|:' . $platform_id;
        calcandupdateLTIgrade(
            $sourcedid,
            $aid,
            $uid,
            $score,
            true,
            $iteminfo['ptsposs']
        );
        $cnt++;
    }
    $scoreresendmsg = sprintf(_('Updates sent for %d students.'), $cnt);
    if (!empty($GLOBALS['CFG']['LTI']['usequeue'])) {
        $scoreresendmsg .= ' '._('It may take a couple minutes for the updates to show in the LMS.');
    }
}


//HTML Output
$pagetitle = "LTI Home";
require("../header.php");

if ($link->get_placementtype() == 'course') {
    $cid = $link->get_typeid();
	echo '<h2>'._('LTI Placement of whole course').'</h2>';
    echo "<p><a href=\"../course/course.php?cid=" . Sanitize::courseId($cid) . "\">"._("Enter course")."</a></p>";

} else if ($link->get_placementtype() == 'assess') {
    $typeid = $link->get_typeid();
	$stm = $DBH->prepare("SELECT name,avail,startdate,enddate,date_by_lti,ver,courseid FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$typeid));
    $line = $stm->fetch(PDO::FETCH_ASSOC);
    $cid = Sanitize::courseId($line['courseid']);
	echo "<h2>".sprintf(_("Assessment Management: %s"), Sanitize::encodeStringForDisplay($line['name'])) . "</h2>";
	if ($line['ver'] > 1) {
		echo "<p><a href=\"../assess2/?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Preview assessment")."</a> | ";
		echo "<a href=\"../course/isolateassessgrade.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Grade list")."</a> ";
		echo "| <a href=\"../course/gb-itemanalysis2.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Item Analysis")."</a>";
	} else {
		echo "<p><a href=\"../assessment/showtest.php?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Preview assessment")."</a> | ";
		echo "<a href=\"../course/isolateassessgrade.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Grade list")."</a> ";
		echo "| <a href=\"../course/gb-itemanalysis.php?cid=" . Sanitize::courseId($cid) . "&asid=average&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Item Analysis")."</a>";
	}
	echo "</p>";

	$now = time();
	echo '<p>';
	if ($line['avail']==0) {
		echo _('Currently unavailable to students.');
	} else if ($line['date_by_lti']==1) {
		echo _('Waiting for the LMS to send a date');
	} else if ($line['date_by_lti']>1) {
		echo sprintf(_('Default due date set by LMS. Available until: %s.'),formatdate($line['enddate']));
		echo '</p><p>';
		if ($line['date_by_lti']==2) {
			echo _('This default due date was set by the date reported by the LMS in your instructor launch, and may change when the first student launches the assignment. ');
		} else {
			echo _('This default due date was set by the first student launch. ');
		}
		echo _('Be aware some LMSs will send unexpected dates on instructor launches, so don\'t worry if the date shown in the assessment preview is different than you expected or different than the default due date. ');
		echo '</p><p>';
		echo _('If the LMS reports a different due date for an individual student when they open this assignment, this system will handle that by setting a due date exception. ');
	} else if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) { //regular show
		echo _("Currently available to students.")."  ";
		echo sprintf(_("Available until %s"), formatdate($line['enddate']));
	} else {
		echo sprintf(_('Currently unavailable to students. Available %s until %s'),formatdate($line['startdate']),formatdate($line['enddate']));
	}
	echo '</p>';

    if ($line['ver']>1) {
        $addassess = 'addassessment2.php';
        $chgassess = 'chgassessments2.php';
    } else {
        $addassess = 'addassessment.php';
        $chgassess = 'chgassessments.php';
    }
    echo "<p><a href=\"../course/$addassess?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Settings")."</a> | ";
    echo "<a href=\"../course/addquestions.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Questions")."</a></p>";

    echo '<p>&nbsp;</p><p class=small>'.sprintf(_('This assessment is housed in course ID %s'),Sanitize::courseId($cid)).'</p>';
    echo '<div class=small>';
    if (!empty($lineitemmsg)) {
        echo '<p class="noticetext">'.Sanitize::encodeStringForDisplay($lineitemmsg).'</p>';
    }
    if (!empty($scoreresendmsg)) {
        echo '<p class="noticetext">'.Sanitize::encodeStringForDisplay($scoreresendmsg).'</p>';
    }
    
    if ($db->has_lineitem($link, $localcourse)) {
        echo _('Has info necessary for passing back grades.');
        if ($line['ver']>1) {
            echo '<form method=post action="ltihome.php?launchid=' .
                Sanitize::encodeStringForDisplay($launch->get_launch_id()).'">';
            echo '<br><button name="resync" type="submit" value="1">';
            echo _('Resend scores to LMS').'</button> ';
            echo '<button name="resyncall" type="submit" value="1">';
            echo _('Resend scores to LMS, including zeros for unattempted').'</button>';
            echo '</form>';
        }
    } else if ($launch->can_create_lineitem()) {
        echo '<form method=post action="ltihome.php?launchid=' .
            Sanitize::encodeStringForDisplay($launch->get_launch_id()).'">';
        echo _('LMS does not currently have a grade column for passing back grades, but the LMS supports us creating a grade column.');
        echo '<br><button name="makelineitem" type="submit" value="1">';
        echo _('Create Grade Column').'</button>';
        echo '</form>';
    } else {
        echo _('Does not currently have a grade column for passing back grades, and the LMS does not support us adding one.');
    }
    echo '</div>';

    echo '<h2>'._('Course Management').'</h2>';
    echo '<p><a href="../course/listusers.php?cid='.$cid.'">'._('Roster').'</a>';
    echo '<br><a href="../course/gradebook.php?cid='.$cid.'">'._('Gradebook').'</a>';
    echo '<br><a href="../course/'.$chgassess.'?cid='.$cid.'">'._('Mass Change Assessments').'</a>';
    if ($line['date_by_lti']===0) {
        echo '<br><a href="../course/masschgdates?cid='.$cid.'">'._('Mass Change Dates').'</a>';
    }
    echo '<br><a href="../admin/forms.php?action=modify&cid='.$cid.'&id='.$cid.'">'._('Course Settings').'</a>';
    echo '<br><a href="../course/copyitems.php?cid='.$cid.'">'._('Course Items: Copy').'</a>';
    echo '<br><a href="../admin/ccexport.php?cid='.$cid.'">'._('Course Items: Export').'</a>';
    echo '<br><a href="../course/course.php?cid='.$cid.'">'._('Full Course Contents').'</a>';
    echo '</p>';

} else if (function_exists('lti_can_handle_redirect') &&
	lti_can_handle_redirect($link->get_placementtype()) &&
	function_exists('lti_ltihome')
) {
	lti_ltihome($link, $launch, $localcourse, $db);
}
if ($launch->has_nrps() && empty($localcourse->get_allow_direct_login())) {
	echo '<p>'.sprintf(_('The LMS offers a roster service, which allows you to update your %s roster to include all students in the LMS.'),
		$installname).'</p>';
	echo '<form method=post action="pullroster.php?launchid=' .
		Sanitize::encodeStringForDisplay($launch->get_launch_id()).'">';
	echo '<br><button name="pullroster" type="submit" value="1">';
	echo _('Pull Roster from LMS').'</button>';
	echo '</form>';
}
require("../footer.php");

function formatdate($date) {
	if ($date==0 || $date==2000000000) {
		return 'Always';
	} else {
		return tzdate("D n/j/y, g:i a",$date);
	}
}

?>
