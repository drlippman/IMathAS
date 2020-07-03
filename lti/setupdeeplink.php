<?php

require('../init.php');

if ($myrights < 20) {
  exit;
}

require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

use \IMSGlobal\LTI;
if (!isset($_POST['launchid'])) {
  echo 'Error - missing launch id';
  exit;
}
if (empty($_POST['aid'])) {
  echo 'No assessment selected';
  exit;
}
$db = new Imathas_LTI_Database($DBH);
$launch = LTI\LTI_Message_Launch::from_cache($_POST['launchid'], $db);

$role = standardize_role($launch->get_roles());
$contextid = $launch->get_platform_context_id();
$ltiuserid = $launch->get_platform_user_id();
$platform_id = $launch->get_platform_id();

$localcourse = $db->get_local_course($contextid, $platform_id);

$deeplink = $launch->get_deep_link();

$aid = intval($_POST['aid']);
$stm = $DBH->prepare('SELECT courseid,name,startdate,enddate,ptsposs,ver,date_by_lti FROM imas_assessments WHERE id=?');
$stm->execute(array($aid));
$assessinfo = $stm->fetch(PDO::FETCH_ASSOC);
if ($assessinfo['courseid'] != $localcourse['courseid']) {
  echo 'Invalid assessment';
  exit;
}
$itemtype = 0; //assessment

$lineitem = LTI\LTI_Lineitem::new()
    ->set_tag($itemtype.'-'.$aid)
    ->set_score_maximum($assessinfo['ptsposs'])
    ->set_label($assessinfo['name']);
if (empty($assessinfo['date_by_lti']) && !empty($assessinfo['startdate'])) {
  $lineitem->set_start_date_time(date(DATE_ATOM, $assessinfo['startdate']));
}
if (empty($assessinfo['date_by_lti']) && !empty($assessinfo['enddate']) && $assessinfo['enddate'] < 2000000000) {
  $lineitem->set_end_date_time(date(DATE_ATOM, $assessinfo['enddate']));
}
$resource = LTI\LTI_Deep_Link_Resource::new()
    ->set_url($basesiteurl . '/lti/launch.php?refaid='.$aid.'&refcid='.$assessinfo['courseid'])
    ->set_title($assessinfo['name'])
    ->set_lineitem($lineitem);

$deeplink->output_response_form([$resource]);
