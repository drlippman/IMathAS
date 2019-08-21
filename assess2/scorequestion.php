<?php
/*
 * IMathAS: Question Scoring Endpoint
 * (c) 2019 David Lippman
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * POST parameters:
 *  toscoreqn           Question number to score, or array of question numbers
 *  lastloaded          Time the question was last displayed
 *
 * Returns: partial assessInfo object, mainly including the scored question
 *          object, but may also update some assessInfo fields
 */


$no_session_handler = 'json_error';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

//error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// validate inputs
check_for_required('GET', array('aid', 'cid'));
check_for_required('POST', array('toscoreqn', 'lastloaded'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if ($isActualTeacher && isset($_GET['uid'])) {
  $uid = Sanitize::onlyInt($_GET['uid']);
} else {
  $uid = $userid;
}
// if toscoreqn is not an array, make it into one
if ($_POST['toscoreqn'] == -1 || $_POST['toscoreqn'] === '') {
  $qns = array();
  $lastloaded = array(Sanitize::onlyInt($_POST['lastloaded']));
  $timeactive = array();
  $verification = array();
} else {
  $qnstoscore = json_decode($_POST['toscoreqn'], true);
  $qns = array_keys($qnstoscore);
  $lastloaded = array_map('Sanitize::onlyInt', explode(',', $_POST['lastloaded']));
  $timeactive = array_map('Sanitize::onlyInt', explode(',', $_POST['timeactive']));
  $verification = json_decode($_POST['verification'], true);
}
$end_attempt = !empty($_POST['endattempt']);

$now = time();

// load settings
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

// reject if not available
if ($assess_info->getSetting('available') === 'practice' && !empty($_POST['practice'])) {
  $in_practice = true;
  $end_attempt = false;
} else if ($assess_info->getSetting('available') === 'yes' || $canViewAll) {
  $in_practice = false;
  if ($canViewAll) {
    $assess_info->overrideAvailable('yes');
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
    $now > $assess_record->getTimeLimitExpires() + 10) ||
    ($assess_info->getSetting('timelimit_type') == 'allow_overtime' &&
    $now > $assess_record->getTimeLimitGrace() + 10))
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
  if ($livepollStatus['curquestion'] - 1 !== $qns[0]) {
    echo '{"error": "livepoll_wrongquestion"}';
    exit;
  } else if ($livepollStatus['curstate'] != 2) {
    echo '{"error": "livepoll_notopen"}';
    exit;
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
  'extended_with', 'allowed_attempts', 'latepasses_avail', 'latepass_extendto',
  'showscores', 'timelimit'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);
//get attempt info
$assessInfoOut['has_active_attempt'] = $assess_record->hasActiveAttempt();
//get time limit expiration of current attempt, if appropriate
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0) {
  $assessInfoOut['timelimit_expires'] = $assess_record->getTimeLimitExpires();
  $assessInfoOut['timelimit_grace'] = $assess_record->getTimeLimitGrace();
}

if (count($qns) > 0) {
  // get current question version ids
  $qids = $assess_record->getQuestionIds($qns);

  // load question settings and code
  $assess_info->loadQuestionSettings($end_attempt ? 'all' : $qids, true);

  // Verify confirmation values (to ensure it hasn't been submitted since)
  if (!$assess_record->checkVerification($verification)) {
    // grab question settings data with HTML
    $showscores = $assess_info->showScoresDuring();
    $assessInfoOut['questions'] = array();
    foreach ($qns as $qn) {
      $assessInfoOut['questions'][$qn] = $assess_record->getQuestionObject($qn, $showscores, true, true);
    }
    $assessInfoOut['error'] = "already_submitted";
    echo json_encode($assessInfoOut);
    exit;
  }

  // Record a submission
  $submission = $assess_record->addSubmission($now);

  // Handle file autosaves - get file storage string from autosave
  foreach ($_POST as $k => $v) {
    if ($v === 'file-autosave') {
      $qref = substr($k,2);
      if ($qref >= 1000) {
        $qn = Math.floor($qref/1000) - 1;
        $pn = $qref % 1000;
      } else {
        $qn = $qref;
        $pn = 0;
      }
      $autosaves = $assess_record->getAutoSaves($qn);
      if (isset($autosaves['stuans'][$pn])) {
        $_POST[$k] = $autosaves['stuans'][$pn];
      }
    } else if (is_string($v) && substr($v,0,5) === '@FILE') {
      unset($_POST[$k]); // prevent faked file autosave loads
    }
  }

  // Score the questions
  $scoreErrors = array();
  foreach ($qns as $k=>$qn) {
    if (!isset($timeactive[$k])) {
      $timeactive[$k] = 0;
    }
    $parts_to_score = $assess_record->isSubmissionAllowed($qn, $qids[$qn]);
    // only score the non-blank ones
    foreach ($parts_to_score as $pn=>$v) {
      if ($v === true && !in_array($pn, $qnstoscore[$qn])) {
        $parts_to_score[$pn] = false;
      }
    }

    $errors = $assess_record->scoreQuestion($qn, $timeactive[$k], $submission, $parts_to_score);
    if (count($errors)>0) {
      $scoreErrors[$qn] = $errors;
    }
  }

  // If it's full test, we'll score time at the assessment attempt level
  if ($assess_info->getSetting('displaymethod') === 'full') {
    $minloaded = round(min($lastloaded)/1000); // front end sends milliseconds
    if ($minloaded > 0) {
      $assess_record->addTotalAttemptTime($now - $minloaded);
    }
  }

  // Update lastchange
  $assess_record->setLastChange($now);

  // Recalculate scores
  $assess_record->reTotalAssess($qns);

} else {
  $assess_info->loadQuestionSettings('all', false);
}

if ($end_attempt) {
  // sets assessment attempt as submitted and updates status
  $assess_record->setStatus(false, true);
  // Recalculate scores based on submitted assessment.
  // Since we already retotaled for newly submitted questions, we can
  // just reuse existing question scores
  $assess_record->reTotalAssess(array());
} else if ($assessInfoOut['submitby'] == 'by_question') {
  // checks to see if all questions are attempted and updates status
  $assess_record->checkByQuestionStatus();
}

if ($end_attempt) {
  // grab all questions settings and scores, based on end-of-assessment settings
  $showscores = $assess_info->showScoresAtEnd();
  $reshowQs = $assess_info->reshowQuestionsAtEnd();
  $assessInfoOut['questions'] = $assess_record->getAllQuestionObjects($showscores, true, $reshowQs, 'scored');
  $assessInfoOut['score'] = $assess_record->getAttemptScore();
  $totalScore = $assessInfoOut['score'];
  $assessInfoOut['has_active_attempt'] = false;

  //get prev attempt info
  $assessInfoOut['prev_attempts'] = $assess_record->getSubmittedAttempts();
  $assessInfoOut['scored_attempt'] = $assess_record->getScoredAttempt();

  if ($assessInfoOut['submitby'] == 'by_question') {
    $assessInfoOut['can_retake'] = false;
  } else {
    $assessInfoOut['can_retake'] = (count($assessInfoOut['prev_attempts']) < $assessInfoOut['allowed_attempts']);
  }

  // get endmsg
  if ($assessInfoOut['showscores'] != 'none') {
    $assessInfoOut['endmsg'] = AssessUtils::getEndMsg(
      $assess_info->getSetting('endmsg'),
      $totalScore,
      $assess_info->getSetting('points_possible')
    );
  }

} else {
  if ($assess_info->getSetting('displaymethod') === 'livepoll') {
    // don't show scores until question is closed for livepoll
    $showscores = false;
    $assess_info->overrideSetting('showscores', 'at_end');
    $assessInfoOut['showscores'] = 'at_end';

    if (!$isteacher) {
      // call the livepoll server with the result
      // always only one question
      $qn = $qns[0];

      // Get last raw scores and stuans to send to livepoll server
      $lastResults = $assess_record->getLastRawResult($qn);
      //TODO: Need to figure out the format they should be in (for multipart)
      //TODO: Or, just don't support multipart
      $rawscores = json_encode($lastResults['raw']);
      $lastAnswer = json_encode($lastResults['stuans']);

      $toSign = $aid.$qn.$uid.$rawscores.$lastAnswer;
      $now = time();
      if (isset($CFG['GEN']['livepollpassword'])) {
        $livepollsig = base64_encode(sha1($toSign . $CFG['GEN']['livepollpassword'] . $now, true));
      }
      $qs = Sanitize::generateQueryStringFromMap(array(
        'aid' => $aid,
        'qn' => $qn,
        'user' => $uid,
        'score' => $rawscores,
        'la' => $lastAnswer,
        'now' => $now,
        'sig' => $livepollsig
      ));
      $r = file_get_contents('https://'.$CFG['GEN']['livepollserver'].':3000/qscored?'.$qs);
      $assessInfoOut['lpres'] = $r;
      $assessInfoOut['lpq'] = 'https://'.$CFG['GEN']['livepollserver'].':3000/qscored?'.$qs;
    }
  } else {
    // grab question settings data with HTML
    $showscores = $assess_info->showScoresDuring();
  }
  $assessInfoOut['questions'] = array();
  foreach ($qns as $qn) {
    $assessInfoOut['questions'][$qn] = $assess_record->getQuestionObject($qn, $showscores, true, true);
  }
  if (count($scoreErrors)>0) {
    $assessInfoOut['scoreerrors'] = $scoreErrors;
  }
}

// Record record
$assess_record->saveRecord();

if ($assessInfoOut['submitby'] == 'by_question' || $end_attempt) {
  // update LTI grade
  $lti_sourcedid = $assess_record->getLTIsourcedId();
  if (strlen($lti_sourcedid) > 1) {
    $gbscore = $assess_record->getGbScore();
    require_once("../includes/ltioutcomes.php");
    $aidposs = $assess_info->getSetting('points_possible');
    calcandupdateLTIgrade($lti_sourcedid, $aid, $gbscore['gbscore'], false, $aidposs);
  }
}

//prep date display
prepDateDisp($assessInfoOut);

//output JSON object
echo json_encode($assessInfoOut);
