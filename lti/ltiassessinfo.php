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

$db = new Imathas_LTI_Database($DBH);
$launch = LTI\LTI_Message_Launch::from_cache($_GET['launchid'], $db);
$contextid = $launch->get_platform_context_id();
$platform_id = $launch->get_platform_id();
$resource_link = $launch->get_resource_link();
$link = $db->get_link_assoc($resource_link['id'], $contextid, $platform_id);
$localcourse = $db->get_local_course($contextid, $launch);

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
} else if (!empty($_POST['resync'])) {
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
    // $scores may include scores from multiple LMS courses, all tied to the same
    // imathas course. So we'll need to grab all the lineitems and match them up to 
    // users.
    $lineitems = [];
    $lineitems[$localcourse->get_id()] = $launch->get_lineitem();
    $stm = $DBH->prepare("SELECT lticourseid,lineitem FROM imas_lti_lineitems WHERE itemtype=0 AND typeid=?");
    $stm->execute([$aid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $lineitems[$row['lticourseid']] = $row['lineitem'];
    }

    require(__DIR__ . '/../includes/ltioutcomes.php');
    $cnt = 0;
    foreach ($scores as $scoredata) {
        if (!isset($lineitems[$scoredata['lticourseid']])) {
            continue; // don't have lineitem for this course
        }
        $sourcedid = 'LTI1.3:|:' . $scoredata['ltiuserid'] . 
            ':|:' . $lineitems[$scoredata['lticourseid']] . ':|:' . $platform_id;
        calcandupdateLTIgrade(
            $sourcedid,
            $aid,
            $scoredata['userid'],
            $scoredata['score'],
            true,
            $iteminfo['ptsposs'],
            false
        );
        $cnt++;
    }
    $scoreresendmsg = sprintf(_('Updates sent for %d students.'), $cnt);
    if (!empty($GLOBALS['CFG']['LTI']['usequeue'])) {
        $scoreresendmsg .= ' '._('It may take a couple minutes for the updates to show in the LMS.');
    }
} 


//HTML Output
$pagetitle = _("Info and LMS Sync");
$placeinhead = '<style>.flexform { display: flex; justify-content: space-between;}
 .flexform button {margin-left: 10px;}
 .borderwrap { border: 1px solid #ccc; padding: 10px; max-width:600px;}
 .borderwrap h3 { margin-top: 5px;}</style>';
require("../header.php");

echo '<div class="breadcrumb">'.$breadcrumbbase . ' '.$pagetitle.'</div>';

if ($link->get_placementtype() != 'assess') {
    echo 'Incorrect link type';
    exit;
}
$typeid = $link->get_typeid();
$query = "SELECT ia.name,ia.avail,ia.startdate,ia.enddate,ia.date_by_lti,
    ia.ver,ia.courseid,ic.name AS coursename FROM imas_assessments AS ia 
    JOIN imas_courses AS ic ON ic.id=ia.courseid WHERE ia.id=:id";
$stm = $DBH->prepare($query);
$stm->execute(array(':id'=>$typeid));
$line = $stm->fetch(PDO::FETCH_ASSOC);
$cid = Sanitize::courseId($line['courseid']);
echo "<h1>", Sanitize::encodeStringForDisplay($line['name']) , "</h1>";

echo '<h2>'._('Info').'</h2>';

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

echo '<p class=small>'.sprintf(_('This assessment is housed in %s course ID %s (%s)'), 
        $installname, 
        Sanitize::courseId($cid), 
        Sanitize::encodeStringForDisplay($line['coursename'])
    ).'</p>';


echo '<h2>'._('LMS Syncing Tools').'</h2>';



echo '<div class="borderwrap">';
echo '<h3>'._('Send scores to LMS').'</h3>';
if (!empty($scoreresendmsg)) {
    echo '<p class="noticetext">'.Sanitize::encodeStringForDisplay($scoreresendmsg).'</p>';
}
if (!empty($lineitemmsg)) {
    echo '<p class="noticetext">'.Sanitize::encodeStringForDisplay($lineitemmsg).'</p>';
}
if ($db->has_lineitem($link, $localcourse)) {
    echo '<p>',sprintf(_('%s has the info necessary for passing back grades.'),$installname),'</p>';
    if ($line['ver']>1) {
        echo '<form method=post action="ltiassessinfo.php?launchid=' .
            Sanitize::encodeStringForDisplay($launch->get_launch_id()).'" class="flexform">';
        echo '<label><input type=checkbox name="resyncall" value="1">';
        echo _('Include zeros for unattempted assessments').'</label>';
        echo '<button name="resync" type="submit" value="1">';
        echo _('Resend Scores to LMS').'</button>';
        echo '</form>';
    }
} else if ($launch->can_create_lineitem()) {
    echo '<form method=post action="ltiassessinfo.php?launchid=' .
        Sanitize::encodeStringForDisplay($launch->get_launch_id()).'" class="flexform">';
    echo '<p>',_('LMS does not currently have a grade column for passing back grades, but the LMS supports us creating a grade column.'),'</p>';
    echo '<button name="makelineitem" type="submit" value="1">';
    echo _('Create Grade Column').'</button>';
    echo '</form>';
} else {
    echo _('Does not currently have a grade column for passing back grades, and the LMS does not support us adding one.');
}
echo '</div>';


if ($launch->has_nrps() && empty($localcourse->get_allow_direct_login())) {
    echo '<div class="borderwrap">';
    echo '<h3>'._('Roster service').'</h3>';
	echo '<form method=post action="pullroster.php?launchid=' .
        Sanitize::encodeStringForDisplay($launch->get_launch_id()).'" class="flexform">';
    echo '<p>'.sprintf(_('The LMS offers a roster service that can be used to update your %s roster from the LMS roster.'),
		$installname).'</p>';
	echo '<br><button name="pullroster" type="submit" value="1">';
	echo _('Sync from LMS Roster').'</button>';
    echo '</form>';
    echo '</div>';
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
