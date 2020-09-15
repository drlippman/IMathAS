<?php
/*
 * IMathAS: Question Load Endpoint
 * (c) 2019 David Lippman
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * POST parameters:
 *  qn                  Question number to display
 *  regen  Optional     Set true to initiate a try-a-similar-question regen
 *
 * Returns: partial assessInfo object, mainly including the desired question
 *          object, but may also update some assessInfo fields
 */


$no_session_handler = 'json_error';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

header('Content-Type: application/json; charset=utf-8');

// validate inputs
check_for_required('GET', array('aid', 'cid'));
check_for_required('POST', array('qn'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if ($isActualTeacher && isset($_GET['uid'])) {
  $uid = Sanitize::onlyInt($_GET['uid']);
} else {
  $uid = $userid;
}
$qn = Sanitize::onlyInt($_POST['qn']);
$doRegen = !empty($_POST['regen']);
$jumpToAnswer = !empty($_POST['jumptoans']);

$now = time();

// load settings including question info
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

$preview_all = ($canViewAll && !empty($_POST['preview_all']));

// reject if not available
if ($assess_info->getSetting('available') === 'practice' && !empty($_POST['practice'])) {
  $in_practice = true;
} else if ($assess_info->getSetting('available') === 'yes' && !empty($_POST['practice'])) {
  echo '{"error": "not_practice"}';
  exit;
} else if ($assess_info->getSetting('available') === 'yes' || $canViewAll) {
  $in_practice = false;
  if ($canViewAll) {
    $assess_info->overrideAvailable('yes', $uid!=$userid || $preview_all);
  }
} else {
  echo '{"error": "not_avail"}';
  exit;
}

// load user's assessment record
$assess_record = new AssessRecord($DBH, $assess_info, $in_practice);
$assess_record->loadRecord($uid);

// make sure a record exists
if (!$assess_record->hasRecord() || !$assess_record->hasActiveAttempt()) {
  echo '{"error": "not_ready"}';
  exit;
}

// reject start if has current attempt, time limit expired, and is kick out
if (!$in_practice &&
  $assess_record->hasActiveAttempt() &&
  $assess_info->getSetting('timelimit') > 0 &&
  (($assess_info->getSetting('timelimit_type') == 'kick_out' &&
    $now > $assess_record->getTimeLimitExpires() + 5) || // TODO: adjust
    ($assess_info->getSetting('timelimit_type') == 'allow_overtime' &&
    $now > $assess_record->getTimeLimitGrace() + 5))
) {
  echo '{"error": "timelimit_expired"}';
  exit;
}

// if there's no active assessment attempt, exit
if (!$assess_record->hasUnsubmittedAttempt()) {
  echo '{"error": "not_ready"}';
  exit;
}

// if livepoll, look up status and verify
if (!$isteacher && $assess_info->getSetting('displaymethod') === 'livepoll') {
  $stm = $DBH->prepare("SELECT * FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
  $stm->execute(array(':assessmentid'=>$aid));
  $livepollStatus = $stm->fetch(PDO::FETCH_ASSOC);
  if ($livepollStatus['curquestion']-1 != $qn) {
    echo '{"error": "livepoll_wrongquestion"}';
    exit;
  }
  // override showscores value to prevent score marks
  if ($livepollStatus['curstate'] != 4) {
    $assess_info->overrideSetting('showscores', 'at_end');
    $assess_info->overrideSetting('showans', 'never');
  }
}

// If in practice, now we overwrite settings
if ($in_practice) {
  $assess_info->overridePracticeSettings();
}

// grab any assessment info fields that may have updated:
// has_active_attempt, timelimit_expires,
// prev_attempts (if we just closed out a version?)
// and those not yet loaded:
// help_features, intro, resources, video_id, category_urls
$include_from_assess_info = array(
  'available', 'startdate', 'enddate', 'original_enddate', 'submitby',
  'extended_with', 'allowed_attempts', 'showscores', 'enddate_in'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);
//get attempt info
$assessInfoOut['has_active_attempt'] = $assess_record->hasActiveAttempt();
//get time limit expiration of current attempt, if appropriate
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0) {
  $assessInfoOut['timelimit_expiresin'] = $assess_record->getTimeLimitExpires() - $now;
  $assessInfoOut['timelimit_gracein'] = max($assess_record->getTimeLimitGrace() - $now, 0);
}

// get current question version
list($qid, $qidstoload) = $assess_record->getQuestionId($qn);

// load question settings and code
$assess_info->loadQuestionSettings($qidstoload, true, false);

// For livepoll, verify seed and generate new question version if needed
if (!$isteacher && $assess_info->getSetting('displaymethod') === 'livepoll') {
  $curQuestionObject = $assess_record->getQuestionObject($qn, false, false, false);
  if ($curQuestionObject['seed'] != $livepollStatus['seed']) {
    // teacher has changed seed. Need to generate a new question version.
    $qid = $assess_record->buildNewQuestionVersion($qn, $qid, $livepollStatus['seed']);
  }
}

// Try a Similar Question, if requested
if ($doRegen) {
  if (!isset($_SESSION['regendelay'])) {
    $_SESSION['regendelay'] = 2;
  }
  if (isset($_SESSION['lastregen'])) {
    if ($now-$_SESSION['lastregen']<$_SESSION['regendelay']) {
      $_SESSION['regendelay'] = 5;
      if (!isset($_SESSION['regenwarnings'])) {
        $_SESSION['regenwarnings'] = 1;
      } else {
        $_SESSION['regenwarnings']++;
      }
      if ($_SESSION['regenwarnings']>10) {
        $stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (:time, :log)");
        $stm->execute(array(':time'=>$now, ':log'=>"Over 10 regen warnings triggered by $userid"));
      }
      echo '{"error": "fast_regen"}';
      exit;
    }
    if ($now - $_SESSION['lastregen'] > 20) {
      $_SESSION['regendelay'] = 2;
    }
  }
  $_SESSION['lastregen'] = $now;
  if ($assess_record->canRegenQuestion($qn, $qid)) {
    $qid = $assess_record->buildNewQuestionVersion($qn, $qid);
    $assess_info->loadQuestionSettings(array($qid), true);
  } else {
    echo '{"error": "out_of_regens"}';
    exit;
  }
}

// jump to answer, if requested
if ($jumpToAnswer) {
  $assess_record->doJumpToAnswer($qn, $qid);
}

// grab question settings data with HTML
if ($assess_info->getSetting('displaymethod') === 'livepoll') {
  $showscores = ($livepollStatus['curstate'] == 4);

  if ($isteacher) {
    // trigger additional jsParams for livepoll results display
    $GLOBALS['capturedrawinit'] = true;
    $GLOBALS['capturechoiceslivepoll'] = true;
  }
} else {
  $showscores = $assess_info->showScoresDuring();
}
$assessInfoOut['questions'] = array(
  $qn => $assess_record->getQuestionObject($qn, $showscores, true, true)
);

// save autosaves, if set 
$assessInfoOut['saved_autosaves'] = false;
if (!empty($_POST['autosave-tosaveqn'])) {
    $qns = json_decode($_POST['autosave-tosaveqn'], true);
    $lastloaded = json_decode($_POST['autosave-lastloaded'], true);
    $verification = json_decode($_POST['autosave-verification'], true);
    if ($_POST['autosave-timeactive'] == '') {
        $timeactive = [];
    } else {
        $timeactive = json_decode($_POST['autosave-timeactive'], true);
    }
    if ($qns !== null && $lastloaded !== null && $timeactive !== null) {
        list($qids,$toloadqids) = $assess_record->getQuestionIds(array_keys($qns));

        // load question settings and code
        $assess_info->loadQuestionSettings($toloadqids, false, false);
        if ($assess_record->checkVerification($verification)) {
            // autosave the requested parts
            foreach ($qns as $qn=>$parts) {
                if (!isset($timeactive[$qn])) {
                    $timeactive[$qn] = 0;
                }
                $ok_to_save = $assess_record->isSubmissionAllowed($qn, $qids[$qn], $parts);
                foreach ($parts as $part) {
                    if ($ok_to_save === true || $ok_to_save[$part]) {
                     $assess_record->setAutoSave($now, $timeactive[$qn], $qn, $part);
                    }
                }
                if (isset($_POST['sw' . $qn])) {  //autosaving work
                    $assess_record->setAutoSave($now, $timeactive[$qn], $qn, 'work');
                }
            }
            $assessInfoOut['saved_autosaves'] = true;
        }
    }
}

// save record if needed
$assess_record->saveRecordIfNeeded();

//prep date display
prepDateDisp($assessInfoOut);

//output JSON object
echo json_encode($assessInfoOut, JSON_INVALID_UTF8_IGNORE);
