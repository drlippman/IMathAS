<?php
/*
 * IMathAS: Gradebook - Get intro and interquestion_text for assessment
 * (c) 2021 David Lippman
 *
 * Method: GET
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *
 * Returns: assessInfo.intro and assessInfo.interquestion_text object
 */


$no_session_handler = 'json_error';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

header('Content-Type: application/json; charset=utf-8');

//validate inputs
check_for_required('GET', array('aid', 'cid'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);

//load settings without questions
$assess_info = new AssessInfo($DBH, $aid, $cid, false);

$assessInfoOut = $assess_info->extractSettings(['intro', 'interquestion_text']);

//output JSON object
echo json_encode($assessInfoOut, JSON_INVALID_UTF8_IGNORE);
