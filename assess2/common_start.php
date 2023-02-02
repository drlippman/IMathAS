<?php
/*
 * IMathAS: Assessment launch endpoint
 * (c) 2019 David Lippman
 *
 * Some common processing useful at the start of scripts, after init.
 */

// handle other instructor previewing course - treat like teacher
if (isset($instrPreviewId)) {
  $teacherid=$instrPreviewId;
}

$isteacher = isset($teacherid);
$istutor = isset($tutorid);
$isstudent = isset($studentid);

if (!$isteacher && !$istutor && !$isstudent) {
  echo '{"error": "no_access"}';
  exit;
}

$canViewAll = $isteacher || $istutor;
$isActualTeacher = $isteacher && !isset($instrPreviewId);
$isRealStudent = (isset($studentid) && !isset($_SESSION['stuview']));

if (!$isRealStudent) {
  $studentinfo = array('latepasses' => 0, 'timelimitmult' => 1);
}

// extend time for uploads


/**
 * Check if the required parameters are set
 * @param  string $method   'GET' or 'POST'
 * @param  array $required  array of required parameter strings
 * @return void
 */
function check_for_required($method, $required) {
  foreach ($required as $r) {
    if (($method == 'POST' && (!isset($_POST[$r]) || $_POST[$r] === '')) ||
      ($method == 'GET' && (!isset($_GET[$r]) || $_GET[$r] === ''))
    ) {
      echo '{"error": "missing_param", "error_details": "Missing parameter '.sanitize::encodeStringForJavascript($r).'"}';
      exit;
    }
  }
}

function prepDateDisp(&$out) {
  $tochg = ['startdate', 'enddate', 'original_enddate', 'timelimit_expires', 'timelimit_grace', 'latepass_extendto'];
  foreach ($tochg as $key) {
    if (isset($out[$key])) {
      if ($out[$key] == 2000000000) {
        $out[$key . '_disp'] = _('None');
      } else {
        $out[$key . '_disp'] = tzdate("D n/j/y, g:i a", $out[$key]);
      }
    }
  }
}


// normalize $_POST['practice'] to boolean
if (!empty($_POST['practice']) && $_POST['practice'] === 'false') {
  $_POST['practice'] = false;
}

if ($_SERVER['HTTP_HOST'] == 'localhost') {
  //to help with development, while vue runs on 8080
  if (!empty($CFG['assess2-use-vue-dev'])) {
    header('Access-Control-Allow-Origin: '. $CFG['assess2-use-vue-dev-address']);
  }
  header("Access-Control-Allow-Credentials: true");
  header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
  header("Access-Control-Allow-Headers: Origin");
}

$useeditor = 1;

if (isset($CFG['GEN']['keeplastactionlog']) && isset($_SESSION['loginlog'.$testsettings['courseid']])) {
  $stm = $DBH->prepare("UPDATE imas_login_log SET lastaction=:lastaction WHERE id=:id");
  $stm->execute(array(':lastaction'=>time(), ':id'=>$_GET['cid']));
}
