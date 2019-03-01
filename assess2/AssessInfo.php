<?php
/*
 * IMathAS: Assessment Settings Class
 * (c) 2019 David Lippman
 */

require_once('../includes/exceptionfuncs.php');

/**
 * Primary class for working with assessment settings
 */
class AssessInfo
{
  private $curAid = null;
  private $cid = null;
  private $DBH = null;
  private $assessData = null;
  private $questionData = array();
  private $exception = null;
  private $exceptionfunc = null;


 /**
  * Construct object and lookup settings.
  * @param object   $DBH      PDO Database Handler
  * @param integer  $aid      Assessment ID
  * @param integer  $cid      Course ID
  * @param mixed $questions   questions to load settings for.
  *                           accepts false for no load, 'all' for all,
  *                           or an array of question IDs.
  */
  function __construct($DBH, $aid, $cid, $questions = false) {
    $this->DBH = $DBH;
    $this->curAid = $aid;
    $this->cid = $cid;
    $this->loadAssessSettings();
    if ($questions !== false) {
      $this->loadQuestionSettings($questions);
    }
  }

  /**
   * Load and normalize assessment settings.  Typically called internally.
   * @return void
   */
  public function loadAssessSettings() {
      $stm = $this->DBH->prepare("SELECT * FROM imas_assessments WHERE id=? AND courseid=?");
      $stm->execute(array($this->curAid, $this->cid));
      $assessData = $stm->fetch(PDO::FETCH_ASSOC);

      if ($assessData === false) {
        echo '{error: "Invalid assessment ID"}';
        exit;
      }

      $this->assessData = self::normalizeSettings($assessData);
      $this->setAvailable();
  }

  /**
   * Looks up and applies an exception, if one exists.
   * @param  integer   $uid       User ID to look up exception for
   * @param  boolean  $isstu      Whether the user is a student
   * @param  integer $latepasses  The number of latepasses the user has
   * @param  integer $latepasshrs How many hours latepasses extend due dates
   * @param  integer $courseenddate The course end date
   * @return void
   */
  public function loadException($uid, $isstu, $latepasses=0, $latepasshrs=24, $courseenddate=2000000000) {
    $query = "SELECT startdate,enddate,islatepass,is_lti,exceptionpenalty ";
    $query .= "FROM imas_exceptions WHERE userid=? AND assessmentid=?";
    $stm = $this->DBH->prepare($query);
    $stm->execute(array($uid, $this->curAid));
    $this->exception = $stm->fetch(PDO::FETCH_NUM);

    if ($this->exception !== null && $this->exception[4] !== null) {
      //override default exception penalty
      $this->assessData['exceptionpenalty'] = $this->exception[4];
    }

    $this->exceptionfunc = new ExceptionFuncs($uid, $this->cid, $isstu, $latepasses, $latepasshrs);

    list($useexception, $canundolatepass, $canuselatepass) =
      $this->exceptionfunc->getCanUseAssessException($this->exception, $this->assessData);

    if ($useexception) {
      if (empty($this->exception[3])) { //if not LTI-set, show orig due date
        $this->assessData['original_enddate'] = $this->assessData['enddate'];
        if ($this->exception[2] == 0) {
          $this->assessData['extended_with'] = array('type'=>'manual');
        } else {
          $this->assessData['extended_with'] = array(
            'latepass' => 'manual',
            'n' => $this->exception[2]
          );
        }
      }
      $this->assessData['startdate'] = $this->exception[0];
      $this->assessData['enddate'] = $this->exception[1];
      $this->setAvailable();
    }

    //determine if latepasses can be used, and how many are needed
    if ($canuselatepass) {
      if (time() > $this->assessData['enddate']) {
        //past the end date - calc how many to reopen
        $LPneeded = $this->exceptionfunc->calcLPneeded($this->assessData['enddate']);
      } else {
        $LPneeded = 1;
      }
      $this->assessData['can_use_latepass'] = $LPneeded;
      $this->assessData['latepasses_avail'] = $latepasses;

      $LPcutoff = $this->assessData['LPcutoff'];
      if ($LPcutoff<$this->assessData['enddate']) {
        $LPcutoff = 0;  //ignore nonsensical values
      }
      $limitedByCourseEnd = (strtotime("+".($latepasshrs*$LPneeded)." hours", $this->assessData['enddate']) > $courseenddate && ($LPcutoff==0 || $LPcutoff>$courseenddate));
      $limitedByLPcutoff = ($LPcutoff>0 && strtotime("+".($latepasshrs*$LPneeded)." hours", $this->assessData['enddate']) > $LPcutoff && $LPcutoff<$courseenddate);
      if ($limitedByCourseEnd) {
        $this->assessData['latepass_extendto'] = $courseenddate;
      } else if ($limitedByLPcutoff) {
        $this->assessData['latepass_extendto'] = $LPcutoff;
      } else {
        $this->assessData['latepass_extendto'] = strtotime("+".($latepasshrs*$LPneeded)." hours", $this->assessData['enddate']);
      }

    } else {
      $this->assessData['can_use_latepass'] = 0;
    }
  }

 /**
  * Load question settings from the DB.
  * @param mixed $qids   Optional. questions to load settings for.
  *                      accepts false for no load, 'all' for all,
  *                      or an array of question IDs.
  *                      Default 'all'.
  * @return void
  */
  public function loadQuestionSettings($qids = 'all') {
    if (is_array($qids)) {
      $ph = Sanitize::generateQueryPlaceholders($qids);
      $stm = $this->DBH->prepare("SELECT * FROM imas_questions WHERE id IN ($ph)");
      $stm->execute($qids);
    } else {
      $stm = $this->DBH->prepare('SELECT * FROM imas_questions WHERE assessmentid = ?');
      $stm->execute(array($this->curAid));
    }
    while ($qrow = $stm->fetch(PDO::FETCH_ASSOC)) {
      $this->questionData[$qrow['id']] = self::normalizeQuestionSettings($qrow, $this->assessData);
    }
  }

  /**
   * Applies a time limit multiplier
   * @param  array $multiplier  Time limit multiplier
   * @return void
   */
  public function applyTimelimitMultiplier($multiplier) {
    $this->assessData['timelimit_multiplier'] = $multiplier;
  }

  /**
   * Extracts settings from assessData
   * @param  array $keys  Array of string keys into assessData to return
   * @return array        Associative array of extracted settings
   */
  public function extractSettings($keys) {
    $out = array();
    foreach ($keys as $key) {
      if (isset($this->assessData[$key])) {
        $out[$key] = $this->assessData[$key];
      }
    }
    return $out;
  }

  /**
   * Gets a setting from assessData by key
   * @param  string $key  Key into assessData to return
   * @return mixed        value of assessData[key]
   */
  public function getSetting($key) {
    if (isset($this->assessData[$key])) {
      return $this->assessData[$key];
    } else {
      return false;
    }
  }

 /**
  * Gets a flattened array of all question IDs for the assessment.
  * @return array Array of all question IDs in assessment.
  */
  public function getAllQuestionIds() {
    $out = array();
    foreach ($this->assessData['itemorder'] as $qid) {
      if (is_array($qid)) {
        $out = array_merge($out, $qid['qids']);
      } else {
        $out[] = $qid;
      }
    }
    return $out;
  }

 /**
  * Gets the number of questions in the assessment.
  * @return integer number of questions
  */
  public function getQuestionCount() {
    $cnt = 0;
    foreach ($this->assessData['itemorder'] as $qid) {
      if (is_array($qid)) {
        if ($qid['type'] == 'pool') {
          $cnt += $qid['n'];
        }
      } else {
        $cnt++;
      }
    }
    return $cnt;
  }

  /**
   * Get whether the assessment has a password
   * @return boolean true if requires a password
   */
  public function hasPassword() {
    return ($this->assessData['password'] != '');
  }

  private function setAvailable() {
    $now = time();
    if ($this->assessData['avail'] == 0) {
      //hard hidden
      $available = 0;
    } else if ($now < $this->assessData['startdate']) {
      //before start date
      $available = 1;
    } else if ($now < $this->assessData['enddate']) {
      //currently available
      $available = 2;
    } else if ($this->assessData['allow_practice']) {
      //past due, allow late
      $available = 3;
    } else {
      $available = 4;
    }
    $this->assessData['available'] = $available;
  }

  /**
  * Select an initial set of questions and seeds for an assessment record.
  * @param  boolean $ispractice   Optional. Set true if generating for practice mode.
  *                               Default false.
  * @param  integer $take         Optional.  The take # for by-assessment retakes.
  *                               Default 0.
  * @param  boolean $oldquestions Optional. An array of previous questions
  *                               used in the asessment.
  * @param  boolean $oldseeds     Optional. An array of previous seeds
  **                              used in the asessment.
  * @return array                 array($questions, $seeds), where each is
  *                               an array of values
  */
  public function assignQuestionsAndSeeds($ispractice = false, $take = 0, $oldquestions = false, $oldseeds = false) {
    $qout = array();
    $seeds = array();

    if ($oldquestions !== false && $oldseeds !== false) {
      $oldseeds = array_combine($oldquestions, $oldseeds);
    }
    foreach ($this->assessData['itemorder'] as $qid) {
      //if is some type of grouping of questions
      if (is_array($qid)) {
        if ($qid['type'] == 'pool') {
          if ($qid['replace'] == true) {
            //select with replacement
            for ($i=0; $i < $qid['n']; $i++) {
              $qout[] = $qid['qids'][array_rand($qid['qids'], 1)];
            }
          } else {
            //select without replacement
            if ($oldquestions !== false) {
              //if we have old questions, put the unused questions first
              //in selection
              $unused = array_diff($qid['qids'], $oldquestions);
              $used = array_intersect($qid['qids'], $oldquestions);
              shuffle($unused);
              shuffle($used);
              $qid['qids'] = array_merge($unused, $used);
            } else {
              shuffle($qid['qids']);
            }

            for ($i=0; $i < min($qid['n'], count($qid['qids'])); $i++) {
              $qout[] = $qid['qids'][$i];
            }
            //if we want more than there are questions
            if ($qid['n'] > count($qid['qids'])) {
              for ($i = count($qid['qids']); $i < $qid['n']; $i++) {
                $qout[] = $qid['qids'][array_rand($qid['qids'], 1)];
              }
            }
          }
        }
      } else {
        $qout[] = $qid;
      }
    }

    if ($this->assessData['shuffle']&1) {
      //shuffle all
      shuffle($qout);
    } else if ($this->assessData['shuffle']&16) {
      //shuffle all but first
      $firstq = array_shift($qout);
      shuffle($qout);
      array_unshift($qout, $firstq);
    }

    //pick seeds
    if ($this->assessData['shuffle']&2) { //all questions same seed
      if ($this->assessData['shuffle']&4) { //all students same seed
        $seeds = array_fill(0, count($qout), $ispractice?$this->curAid+100:$this->curAid);
      } else {
        do {
          $newseed = rand(1,9999);
        } while ($oldseeds !== false && $newseed == $oldseeds[$oldquestions[0]]);

        $seeds = array_fill(0, count($qout), $newseed);
      }
    } else {
      if ($this->assessData['shuffle']&4) { //all students same seed
        foreach ($qout as $i=>$qid) {
          if ($this->questionData[$qid]['fixedseeds'] !== null) {
            //using fixed seed list
            $n = count($this->questionData[$qid]['fixedseeds']);
            $seeds[] = $this->questionData[$qid]['fixedseeds'][($ispractice?1:0) % $n];
          } else {
            //pick seed based on assessment ID
            $seeds[] = $i + $this->curAid + $take + $ispractice?100:0;
          }
        }
      } else { //regular selection
        foreach ($qout as $i=>$qid) {
          if ($this->questionData[$qid]['fixedseeds'] !== null) {
            //using fixed seed list
            if ($oldseeds !== false && count($this->questionData[$qid]['fixedseeds']) > 1) {
              //if we have oldseeds, remove it from selection
              $unused = array_diff($this->questionData[$qid]['fixedseeds'], $oldseeds[$qid]);
              $newseed = $unused[array_rand($unused, 1)];
            } else {
              $newseed = $this->questionData[$qid]['fixedseeds'][rand(0, $n-1)];
            }
          } else {
            //random seed
            $looplimit = 0;
            do {
              $newseed = rand(1,9999);
            } while ($looplimit < 10 && $oldseeds !== false && $oldseeds[$qid] == $newseed);
          }
          $seeds[] = $newseed;
        }
      }
    }
    return array($qout, $seeds);
  }


  /**
  * Regen the question ID and seed.  Only changes the question if it was pooled.
  * Attempts to ensure the new question ID from a pool is previously unused.
  *
  * @param  boolean $oldquestion  The previous assigned question.
  * @param  boolean $oldseeds     An array of previous seeds used for this question.
  * @param  boolean $oldquestions Optional. An array of previous questions
  *                               used in the asessment.
  * @return array                 array($question, $seed)
  */
  public function regenQuestionAndSeed($oldquestion, $oldseeds, $oldquestions=array()) {
    $newq = $oldquestion;  //by default, reuse same question
    if (!in_array($oldquestion, $this->assessData['itemorder'])) {
      //the question must be in a grouping.  Find the group.
      foreach ($this->assessData['itemorder'] as $qid) {
        if (is_array($qid) && in_array($oldquestion, $qid['qids'])) {
          $group = $qid['qids'];
          $grouptype = $qid['type'];
          break;
        }
      }
      //if it's a pool, pick an unused question from the pool
      if (isset($group) && $grouptype == 'pool') {
        $unused = array_diff($pool, $oldquestions);
        if (count($unused) == 0) {
          //if all of them have been used, just make sure we don't pick
          //the current one again
          $unused = array_diff($pool, array($oldquestion));
        }
        $newq = $unused[array_rand($unused,1)];
      }
    }

    if ($this->assessData['shuffle']&4 || $this->assessData['shuffle']&2) {
      //all students same seed or all questions same seed - don't regen
      $newseed = $oldseeds[0];
    } else {
      if ($this->questionData[$newq]['fixedseeds'] !== null) {
        //using fixed seed list. find any unused seeds
        if (count($this->questionData[$newq]['fixedseeds']) == 1) {
          //only one seed so use it
          $newseed = $this->questionData[$newq]['fixedseeds'][0];
        } else {
          $unused = array_diff($this->questionData[$newq]['fixedseeds'], $oldseeds);
          if (count($unused) == 0) {
            //if all used, just make sure it's not the same as the last
            $unused = array_diff($this->questionData[$newq]['fixedseeds'], array($oldseeds[count($oldseeds)-1]));
          }
          $newseed = $unused[array_rand($unused, 1)];
        }
      } else {
        //regular seed pick
        $looplimit = 0;
        do {
          $newseed = rand(1,9999);
          $looplimit++;
        } while ($looplimit < 10 && in_array($newseed, $oldseeds));
      }
    }
    return array($newq, $newseed);
  }

 /**
  * Normalizes question settings pulled from the database
  * and replaces them with defaults when appropriate.
  *
  * @param  array $settings  Question settings assoc array from database.
  * @param  array $defaults  Assessment settings assoc array.
  * @return array            Normalized $settings array.
  */
  static function normalizeQuestionSettings($settings, $defaults) {
    if ($settings['points'] == 9999) {
      $settings['points'] = $defaults['defpoints'];
    }
    if ($settings['attempts'] == 9999) {
      $settings['attempts'] = $defaults['defattempts'];
    }
    if ($settings['penalty'] == 9999) {
      $settings['penalty'] = $defaults['defpenalty'];
      $settings['penalty_n'] = $defaults['defpenalty_n'];
    } else {
      if ($settings['penalty'][0]==='L') {
        $settings['penalty_n'] = 'last';
        $settings['penalty'] = substr($settings['penalty'], 1);
      } else if ($settings['penalty'][0]==='S') {
        $settings['penalty_n'] = $settings['penalty'][1];
        $settings['penalty'] = substr($settings['penalty'], 2);
      } else {
        $settings['penalty_n'] = 1;
      }
    }
    if ($settings['regenpenalty'] == 9999) {
      $settings['regenpenalty'] = $defaults['defregenpenalty'];
      $settings['regenpenalty_n'] = $defaults['defregenpenalty_n'];
    } else {
      if ($settings['regenpenalty'][0]==='S') {
        $settings['regenpenalty_n'] = $settings['regenpenalty'][1];
        $settings['regenpenalty'] = substr($settings['regenpenalty'], 2);
      } else {
        $settings['regenpenalty_n'] = 0;
      }
    }
    if ($settings['regen'] == 1 || $defaults['submitby'] == 'by_assessment') {
      $settings['regens'] = 1;
    } else {
      $settings['regens'] = $defaults['defregens'];
    }
    unset($settings['regen']);

    if ($settings['showans'] == '0') {
      $settings['showans'] = $defaults['showans'];
      if ($settings['showans'] == 'after_n') {
        $settings['showans_aftern'] = $defaults['showans_aftern'];
      }
    } else if (is_numeric($settings['showans'])) {
      $settings['showans'] = 'after_n';
      $settings['showans_aftern'] = intval($settings['showans']);
    } else if ($settings['showans'] == 'N') {
      $settings['showans'] = 'never';
    } else if ($settings['showans'] == 'L') {
      $settings['showans'] = 'after_lastattempt';
    } else if ($settings['showans'] == 'T') {
      $settings['showans'] = 'after_take';
    } else if ($settings['showans'] == 'W') {
      $settings['showans'] = 'with_score';
    }

    if ($settings['showans'] == -1) {
      $settings['showans'] = $defaults['showans'];
    }

    if (!empty($settings['fixedseeds'])) {
      $settings['fixedseeds'] = explode(',', $settings['fixedseeds']);
    } else {
      $settings['fixedseeds'] = null;
    }

    foreach ($settings as $k=>$v) {
      //convert numeric strings to numbers
      if (is_string($v) && is_numeric($v)) {
        $settings[$k] = $v + 0;
      }
    }

    return $settings;
  }


 /**
  * Normalizes assessment settings pulled from the database
  * @param  array $settings   Assessment settings assoc array from the database.
  * @return array             Normalized $settings.
  */
  static function normalizeSettings($settings) {
    //break apara defpenalty, defregenpenalty
    if ($settings['defpenalty'][0]==='L') {
      $settings['defpenalty_n'] = 'last';
      $settings['defpenalty'] = substr($settings['defpenalty'], 1);
    } else if ($settings['defpenalty'][0]==='S') {
      $settings['defpenalty_n'] = $settings['defpenalty'][1];
      $settings['defpenalty'] = substr($settings['defpenalty'], 2);
    } else {
      $settings['defpenalty_n'] = 1;
    }

    if ($settings['defregenpenalty'][0]==='S') {
      $settings['defregenpenalty_n'] = intval($settings['defregenpenalty'][1]);
      $settings['defregenpenalty'] = substr($settings['defregenpenalty'], 2);
    } else {
      $settings['defregenpenalty_n'] = 1;
    }

    //if by-assessment, define take values
    if ($settings['submitby'] == 'by_assessment') {
      $settings['allowed_takes'] = $settings['defregens'];
      $settings['retake_penalty'] = array(
        'penalty' => $settings['defregenpenalty'],
        'n' => $settings['defregenpenalty_n']
      );
    }

    //unpack showans after_#
    if (strlen($settings['showans']) == 7) {
      $settings['showans_aftern'] = intval(substr($settings['showans'], 6));
      $settings['showans'] = 'after_n';
    }

    //unpack minscore
    if ($settings['minscore'] > 10000) {
      $settings['minscore'] = $settings['minscore'] - 10000;
      $settings['minscore_type'] = 'percent';
    } else {
      $settings['minscore_type'] = 'points';
    }

    //unpack timelimit
    if ($settings['timelimit'] < 0) {
      $settings['timelimit'] = abs($settings['timelimit']);
      $settings['timelimit_type'] = 'kick_out';
    } else {
      $settings['timelimit_type'] = 'allow_overtime';
    }
    $settings['timelimit_multiplier'] = 1;

    //unpack intro
    $introjson = json_decode($settings['intro'], true);
    if ($introjson === null) {
      $settings['interquestion_text'] = array();
    } else {
      $settings['intro'] = $introjson[0];
      $settings['interquestion_text'] = array_slice($introjson, 1);
    }

    //unpack resources
    $settings['resources'] = json_decode($settings['extrefs']);
    unset($settings['extrefs']);

    //rename points possible
    $settings['points_possible'] = $settings['ptsposs'];
    unset($settings['ptsposs']);

    //unpack practice mode
    if ($settings['reviewdate'] == 2000000000) {
      $settings['allow_practice'] = true;
    } else {
      $settings['allow_practice'] = false;
    }

    //handle IP-form passwords
    if ($settings['password'] != '' &&
      preg_match('/^\d{1,3}\.(\*|\d{1,3})\.(\*|\d{1,3})\.[\d\*\-]+/', $settings['password']) &&
      self::isIPinRange($_SERVER['REMOTE_ADDR'], $settings['password'])
    ) {
      $settings['password'] = '';
    }

    //handle safe exam browser passwords
    if ($settings['password'] != '' && !empty($_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH'])) {
      $testhash = hash("sha256", 'https//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] . trim($settings['password']));
      if ($testhash == $_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH']) {
        $settings['password'] = '';
      }
    }

    //unpack itemorder
    $itemorder = json_decode($settings['itemorder'], true);
    //temp handling of old format
    if ($itemorder === null) {
      $order = explode(',', $settings['itemorder']);
      foreach ($order as $k=>$v) {
        $sub = explode('~', $v);
        if (count($sub)>1) {
          $pts = explode('|', $sub[0]);
          $order[$k] = array(
            'type' => 'pool',
            'n' => $pts[0],
            'replace' => ($pts[1]==1),
            'qids' => array_slice($sub, 1)
          );
        }
      }
      $settings['itemorder'] = $order;
    } else {
      $settings['itemorder'] = $itemorder;
    }

    foreach ($settings as $k=>$v) {
      //convert numeric strings to numbers
      if (is_string($v) && is_numeric($v)) {
        $settings[$k] = $v + 0;
      }
    }

    return $settings;
  }

  /**
   * Check if the given IP address is in the desired range
   *
   * @param  string  $userip The user's IP address to check
   * @param  string  $range  The IP range to try.
   *                         This is a comma-separated list of IPs,
   *                         and elements may include * for wildcard or
   *                         value-value for ranges like
   *                         12.3.5.*, or 12.34.6.12-35
   * @return boolean         true if userip is in the range
   */
  static function isIPinRange($userip, $range) {
    $ips = array_map('trim', explode(',', $range));
    $userip = explode('.', $userip);
		$isoneIPok = false;
    foreach ($ips as $ip) {
      $ip = explode('.', $ip);
      $thisIPok = true;
      for ($i=0;$i<3;$i++) {
        $pts = explode('-', $ip[$i]);
        if (count($pts) == 2 && $userip[$i] >= $pts[0] && $userip[$i] <= $pts[0]) {
          continue;
        } else if ($ip[$i] == '*') {
          continue;
        } else if ($ip[$i] == $userip[$i]) {
          continue;
        } else {
          $thisIPok = false;
          break;
        }
      }
      if ($thisIPok) {
				$isoneIPok = true;
				break;
			}
    }
    return $isoneIPok;
  }
}
