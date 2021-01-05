<?php
/*
 * IMathAS: Assessment Settings Class
 * (c) 2019 David Lippman
 */

require_once(__DIR__ . '/../includes/exceptionfuncs.php');
require_once(__DIR__ . '/../includes/Rand.php');
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
  private $questionSetData = array();
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
      $get_code = ($this->assessData['displaymethod'] === 'full');
      $this->loadQuestionSettings($questions, $get_code);
    }
  }

  /**
   * Return the current course ID
   * @return int Course ID
   */
  public function getCourseId() {
    return $this->cid;
  }

  /**
   * Return the current assessment ID
   * @return int  Assessment ID
   */
  public function getAssessmentId() {
    return $this->curAid;
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
        echo '{"error": "invalid_aid"}';
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
    if (!$isstu && $this->assessData['date_by_lti'] > 0 && isset($_SESSION['lti_duedate'])) {
      // fake exception for teachers from LTI
      $this->exception = array(0, $_SESSION['lti_duedate'], 0, 1, 0);
    } else if (!$isstu) {
      $this->exception = false;
    } else {
      $query = "SELECT startdate,enddate,islatepass,is_lti,exceptionpenalty,waivereqscore,timeext,attemptext ";
      $query .= "FROM imas_exceptions WHERE userid=? AND assessmentid=?";
      $stm = $this->DBH->prepare($query);
      $stm->execute(array($uid, $this->curAid));
      $this->exception = $stm->fetch(PDO::FETCH_NUM);
    }

    $this->assessData['hasexception'] = ($this->exception !== false);

    if ($this->exception !== false && $this->exception[4] !== null) {
      //override default exception penalty
      $this->assessData['exceptionpenalty'] = $this->exception[4];
    }

    $this->exceptionfunc = new ExceptionFuncs($uid, $this->cid, $isstu, $latepasses, $latepasshrs);

    if ($latepasses > 0) {
      list($useexception, $canundolatepass, $canuselatepass) =
        $this->exceptionfunc->getCanUseAssessException($this->exception, $this->assessData);
    } else {
      $useexception = $this->exceptionfunc->getCanUseAssessException($this->exception, $this->assessData, true);
      $canuselatepass = false;
    }

    // use time limit extension even if rest of exception isn't used
    if ($this->exception !== false && $this->exception[6] != 0) {
        $this->assessData['timeext'] = intval($this->exception[6]);
    }
    if ($this->exception !== false && $this->exception[7] != 0) {
        $this->assessData['attemptext'] = intval($this->exception[7]);
        // apply additional attempts
        if ($this->assessData['submitby'] == 'by_assessment') {
            $this->assessData['allowed_attempts'] += $this->assessData['attemptext'];
        } else {
            // if question settings already loaded, apply extension
            foreach ($this->questionData as $i=>$set) {
                if ($set['regen'] != 1) {
                    $this->questionData[$i]['regens_max'] += $this->assessData['attemptext'];
                }
            }
        }
    }

    if ($useexception) {
      if (empty($this->exception[3]) || $this->exception[2] > 0) {
        //if not LTI-set, or if LP used, show orig due date
        $this->assessData['original_enddate'] = $this->assessData['enddate'];
        if ($this->exception[2] == 0) {
          $this->assessData['extended_with'] = array('type'=>'manual');
        } else {
          $this->assessData['extended_with'] = array(
            'type' => 'latepass',
            'n' => intval($this->exception[2])
          );
        }
      }
      $this->assessData['startdate'] = intval($this->exception[0]);
      $this->assessData['enddate'] = intval($this->exception[1]);
      $this->assessData['enddate_in'] = $this->assessData['enddate'] - time() - 5;
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
   * Determine whether latepass use is being blocked by practice mode access
   * Also sets assessData['latepass_blocked_by_practice']
   * @return boolean
   */
  public function getLatePassBlockedByView() {
    if ($this->assessData['hasexception']) {
      list($useexception,$LPblocked) =
        $this->exceptionfunc->getCanUseAssessException(
          $this->exception,
          $this->assessData,
          false,
          true
        );
    } else {
      $LPblocked = $this->exceptionfunc->getLatePassBlockedByView($this->assessData,0);
    }
    $this->assessData['latepass_blocked_by_practice'] = $LPblocked;
    return $this->assessData['latepass_blocked_by_practice'];
  }

  /**
   * Look up whether prereq has been waived
   * @return boolean true if prereq is waived
   */
  private function waiveReqScore () {
      if ($this->exception === null) {
        return false;
      } else {
        return $this->exception[5];
      }
  }

 /**
  * Load question settings from the DB.
  * @param mixed $qids   Optional. questions to load settings for.
  *                      accepts false for no load, 'all' for all,
  *                      or an array of question IDs.
  *                      Default 'all'.
  * @param boolean $get_code  set True to load the question code and other fields
  *                           from imas_questionset.  Gets stored in $questionSetData
  * @param boolean $get_cats  whether to load question category info (def true)
  * @return void
  */
  public function loadQuestionSettings($qids = 'all', $get_code = false, $get_cats = true) {
    if (is_array($qids)) {
      $ph = Sanitize::generateQueryPlaceholders($qids);
      $stm = $this->DBH->prepare("SELECT * FROM imas_questions WHERE id IN ($ph)");
      $stm->execute(array_values($qids));
    } else {
      $stm = $this->DBH->prepare('SELECT * FROM imas_questions WHERE assessmentid = ?');
      $stm->execute(array($this->curAid));
    }
    $qsids = array();
    $tolookupAids = array();
    $tolookupOutcomes = array();
    while ($qrow = $stm->fetch(PDO::FETCH_ASSOC)) {
      if (!$get_cats) {
        unset($qrow['category']);
      }
      $this->questionData[$qrow['id']] = self::normalizeQuestionSettings($qrow, $this->assessData);
      $qsids[] = $qrow['questionsetid'];
      if ($get_cats) {
        $this->questionData[$qrow['id']]['origcategory'] = $this->questionData[$qrow['id']]['category'];
        $category = &$this->questionData[$qrow['id']]['category'];

        if ($category === '') {
            // do nothing
        } else if (is_numeric($category)) {
            if (intval($category) === 0) {
            $category = $this->assessData['defoutcome'];
            }
            $tolookupOutcomes[$qrow['id']] = $category;
        } else if (0==strncmp($category,"AID-",4)) {
            $tolookupAids[$qrow['id']] = substr($category, 4);
        }
      }
    }
    if (count($tolookupAids) > 0 && $get_cats) {
      $uniqAids = array_values(array_unique($tolookupAids));
      $ph = Sanitize::generateQueryPlaceholders($uniqAids);
      $stm = $this->DBH->prepare("SELECT id,name FROM imas_assessments WHERE id IN ($ph) AND courseid=?");
      $stm->execute(array_merge($uniqAids, array($this->cid)));
      $aidmap = array();
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $aidmap[$row['id']] = $row['name'];
      }
      foreach ($tolookupAids as $qid=>$aid) {
        // TODO: include enough for link to assessment too
        $this->questionData[$qid]['category'] = $aidmap[$aid];
      }
    }
    if (count($tolookupOutcomes) > 0 && $get_cats) {
      $uniqOutcomes = array_values(array_unique($tolookupOutcomes));
      $ph = Sanitize::generateQueryPlaceholders($uniqOutcomes);
      $stm = $this->DBH->prepare("SELECT id,name FROM imas_outcomes WHERE id IN ($ph) AND courseid=?");
      $stm->execute(array_merge($uniqOutcomes, array($this->cid)));
      $outcomemap = array();
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $outcomemap[$row['id']] = $row['name'];
      }
      foreach ($tolookupOutcomes as $qid=>$oid) {
        $this->questionData[$qid]['category'] = $outcomemap[$oid];
      }
    }
    if ($get_code && count($qsids) > 0) {
      $ph = Sanitize::generateQueryPlaceholders($qsids);
      $stm = $this->DBH->prepare("SELECT * FROM imas_questionset WHERE id IN ($ph)");
      $stm->execute(array_values($qsids));
      while ($qsrow = $stm->fetch(PDO::FETCH_ASSOC)) {
        $this->questionSetData[$qsrow['id']] = $qsrow;
      }
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
      if ($key == 'interquestion_text' && !isset($this->assessData['textmap'])) {
        $this->generateTextMap();
      }
      if (isset($this->assessData[$key])) {
        $out[$key] = $this->assessData[$key];
      }
    }
    return $out;
  }

  /**
   * Gets question settings that will be output in question object
   * @return array        Associative array of extracted settings
   */
  public function getQuestionSettings($id) {
    $by_q = array('regens_max');
    $base = array('tries_max','retry_penalty','retry_penalty_after',
      'showans','showans_aftern','points_possible','questionsetid',
      'category', 'withdrawn', 'jump_to_answer','showwork');
    $out = array();
    if (!isset($this->questionData[$id])) {
        return false;
    }
    foreach ($base as $field) {
        if (isset($this->questionData[$id][$field])) {
            $out[$field] = $this->questionData[$id][$field];
        } else {
            $out[$field] = null;
        }
    }
    if ($this->assessData['submitby'] == 'by_question') {
      foreach ($by_q as $field) {
        $out[$field] = $this->questionData[$id][$field];
      }
    }
    if (!isset($this->assessData['textmap'])) {
        $this->generateTextMap();
    }
    if (isset($this->assessData['textmap'][$id])) {
        $out['text'] = $this->assessData['textmap'][$id];
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
    } else if ($key === 'original_enddate' && isset($this->assessData['enddate'])) {
      return $this->assessData['enddate'];
    } else {
      return false;
    }
  }

  /**
   * Override an assessment setting value
   * @param  string $key   the setting name
   * @param  string $value the value to override with
   * @return void
   */
  public function overrideSetting($key, $value) {
    $this->assessData[$key] = $value;
  }

  /**
   * Get a setting value from the question Data
   * @param  int $id    The question ID
   * @param  string $field  The setting field to grab
   * @return mixed  the setting value
   */
  public function getQuestionSetting($id, $field) {
    return $this->questionData[$id][$field];
  }

  public function getAllQuestionPoints() {
    $out = array();
    foreach ($this->questionData as $qid=>$v) {
      $out[$qid] = $v['points_possible'];
    }
    return $out;
  }

  public function getAllQuestionPointsAndCats() {
    $out = array();
    foreach ($this->questionData as $qid=>$v) {
      $out[$qid] = ['points'=>$v['points_possible'], 'cat'=>$v['origcategory']];
    }
    return $out;
  }

  /**
   * Get the questionset code data
   * @param  int $qsid   questionset ID
   * @return array of data for the question code
   */
  public function getQuestionSetData($qsid) {
    return $this->questionSetData[$qsid];
  }

  /**
   * Returns time limit adjusted with time limit multiplier
   * @return [type] [description]
   */
  public function getAdjustedTimelimit() {
    return $this->assessData['timelimit_multiplier'] * $this->assessData['timelimit'];
  }

  /**
   * Returns time limit grace adjusted with time limit multiplier
   * @return [type] [description]
   */
  public function getAdjustedTimelimitGrace() {
    return $this->assessData['timelimit_multiplier'] * $this->assessData['overtime_grace'];
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

  /**
   * Check if the password is correct.
   *
   * @param  string $pw The password entered by the user
   * @return boolean    True if password is correct, or if there is
   *                    no password on the assessment
   *
   * Note: IP-based passwords are handled in the initial load setting
   * normalization, so those aren't checked here.
   */
  public function checkPassword($pw) {
    return ($this->assessData['password'] == '' ||
        $this->assessData['password'] == trim($pw)
    );
  }

  /*
    Override the "available" setting, and enddate and timelimit
   */
  public function overrideAvailable($val, $overrideTimelimit=true) {
    if ($this->assessData['available'] !== 'yes' && $val == 'yes') {
      // necessary to override to prevent due date timer from causing problems
      $this->assessData['enddate'] = 2000000000;
    }
    if ($overrideTimelimit) {
      $this->assessData['timelimit'] = 0;
    }
    $this->assessData['available'] = $val;
  }

  /**
   * Set the 'available' assessData based on other settings
   */
  private function setAvailable() {
    $now = time();
    if ($this->assessData['avail'] == 0) {
      //hard hidden
      $available = 'hidden';
    } else if ($now < $this->assessData['startdate']) {
      //before start date
      $available = 'notyet';
    } else if ($now < $this->assessData['enddate'] + 10) {
      //currently available
      $available = 'yes';
    } else if ($this->assessData['allow_practice']) {
      //past due, allow late
      $available = 'practice';
    } else {
      $available = 'pastdue';
    }
    $this->assessData['available'] = $available;
  }

  /**
   * Check whether a prereq exists and if the requirements have been met
   * Updates assessData['available'] if not.
   *
   * @param  int $uid   The user ID
   * @return void
   */
  public function checkPrereq($uid) {
    if ($this->assessData['reqscore'] > 0 &&
        $this->assessData['reqscoreaid'] > 0 &&
        !$this->waiveReqScore()
    ) {
      $query = "SELECT iar.score,ia.ptsposs,ia.name FROM imas_assessments AS ia LEFT JOIN ";
			$query .= "imas_assessment_records AS iar ON iar.assessmentid=ia.id AND iar.userid=? ";
			$query .= "WHERE ia.id=?";
      $stm = $this->DBH->prepare($query);
      $stm->execute(array($uid, $this->assessData['reqscoreaid']));
      list($prereqscore,$reqscoreptsposs,$prereqname) = $stm->fetch(PDO::FETCH_NUM);
      if ($prereqscore === null) {
				$isBlocked = true;
			} else {
        $isBlocked = false;
        if ($this->assessData['reqscoretype']&2) { //using percent-based
					if (round(100*$prereqscore/$reqscoreptsposs,1)+.02<abs($this->assessData['reqscore'])) {
						$isBlocked = true;
					}
				} else if ($prereqscore+.02<abs($this->assessData['reqscore'])) { //points based
					$isBlocked = true;
				}
      }
      if ($isBlocked) {
        $this->assessData['available'] = 'needprereq';
        $this->assessData['reqscorename'] = $prereqname;
        $this->assessData['reqscorevalue'] = ($this->assessData['reqscore']) .
          (($this->assessData['reqscoretype']&2) ? '%' : ' '._('points'));
      }
    }
  }

  /**
   * Determine whether we are showing scores during the assessment
   * @return boolean  true if showing scores during
   */
  public function showScoresDuring() {
    return ($this->assessData['showscores'] == 'during');
  }

  /**
   * Determine whether we are showing detailed scores at end of assessment attempt
   * @return boolean  true if showing scores at end
   */
  public function showScoresAtEnd() {
    $showscores = $this->assessData['showscores'];
    return ($showscores == 'during' || $showscores == 'at_end');
  }

  /**
   * Determine whether we are reshowing questions in gb / addwork
   * @return boolean  true if showing question at end
   */
  public function reshowQuestionsInGb() {
    //$showscores = $this->assessData['showscores'];
    //return ($showscores == 'at_end' || $showscores == 'during');
    $viewingb = $this->assessData['viewingb'];
    return ($viewingb == 'immediately' || $viewingb == 'after_take' ||
      ($viewingb == 'after_due' && time() > $this->assessData['enddate']));
  }

  /**
   * Determine whether we are reshowing questions at end
   * @return boolean  true if showing question at end
   */
  public function reshowQuestionsAtEnd() {
    $showscores = $this->assessData['showscores'];
    $viewingb = $this->assessData['viewingb'];
    return ($showscores == 'at_end' || $showscores == 'during' ||
      $viewingb == 'immediately' || $viewingb == 'after_take' ||
      ($viewingb == 'after_due' && time() > $this->assessData['enddate']));
  }


  /**
  * Select an initial set of questions and seeds for an assessment record.
  * @param  boolean $ispractice   Optional. Set true if generating for practice mode.
  *                               Default false.
  * @param  integer $attempt      Optional.  The attempt # for by-assessment attempts.
  *                               Default 0.
  * @param  boolean $oldquestions Optional. An array of previous questions
  *                               used in the asessment.
  * @param  boolean $oldseeds     Optional. An array of previous seeds
  **                              used in the asessment.
  * @return array                 array($questions, $seeds), where each is
  *                               an array of values
  */
  public function assignQuestionsAndSeeds($ispractice = false, $attempt = 0, $oldquestions = false, $oldseeds = false) {
    $qout = array();
    $seeds = array();
    $RND = new Rand();

    if ($this->assessData['shuffle']&4) {
      // if set for all students same random seed, it makes sense they'd get
      // the same questions from pool and shuffle order as well
      $RND->srand($this->curAid + $attempt + ($ispractice ? 1000 : 0));
    }
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
              $qout[] = intval($qid['qids'][$RND->array_rand($qid['qids'], 1)]);
            }
          } else {
            //select without replacement
            if ($oldquestions !== false) {
              //if we have old questions, put the unused questions first
              //in selection
              $unused = array_diff($qid['qids'], $oldquestions);
              $used = array_intersect($qid['qids'], $oldquestions);
              $RND->shuffle($unused);
              $RND->shuffle($used);
              $qid['qids'] = array_merge($unused, $used);
            } else {
              $RND->shuffle($qid['qids']);
            }

            for ($i=0; $i < min($qid['n'], count($qid['qids'])); $i++) {
              $qout[] = intval($qid['qids'][$i]);
            }
            //if we want more than there are questions
            if ($qid['n'] > count($qid['qids'])) {
              for ($i = count($qid['qids']); $i < $qid['n']; $i++) {
                $qout[] = intval($qid['qids'][$RND->array_rand($qid['qids'], 1)]);
              }
            }
          }
        }
      } else {
        $qout[] = intval($qid);
      }
    }

    if ($this->assessData['shuffle']&16) {
        //shuffle all but first
        $firstq = array_shift($qout);
    }
    if ($this->assessData['shuffle']&32) {
        //shuffle all but last
        $lastq = array_pop($qout);
    }
    if ($this->assessData['shuffle']&(1+16+32)) {
          // has any shuffle flag set
          if (!empty($this->assessData['pagebreaks'])) {
            $shift = ($this->assessData['shuffle']&16)?1:0;
            $lastgroupq = 0;
            $newqout = [];
            foreach ($this->assessData['pagebreaks'] as $breakn=>$breakq) {
                if ($breakq === 0) { continue; }
                $thisgroup = [];
                for ($i=$lastgroupq; $i < $breakq - $shift; $i++) {
                    $thisgroup[] = $qout[$i];
                }
                $lastgroupq = $breakq - $shift;
                $RND->shuffle($thisgroup);
                $newqout = array_merge($newqout, $thisgroup);
            }
            $thisgroup = [];
            for ($i=$lastgroupq;$i < count($qout); $i++) {
                $thisgroup[] = $qout[$i];
            }
            $RND->shuffle($thisgroup);
            $qout = array_merge($newqout, $thisgroup);
          } else {
            $RND->shuffle($qout);
          }
    }
    if ($this->assessData['shuffle']&16) {
      array_unshift($qout, $firstq);
    }
    if ($this->assessData['shuffle']&32) {
        array_push($qout, $lastq);
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
            $seeds[] = $i + $this->curAid + $attempt + ($ispractice?100:0);
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
              $n = count($this->questionData[$qid]['fixedseeds']);
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
          if ($qid['type'] == 'pool' && $qid['n'] < count($qid['qids'])) {
            // only do redraw if not picking n from n
            $group = $qid['qids'];
            $grouptype = $qid['type'];
          }
          break;
        }
      }
      //if it's a pool, pick an unused question from the pool
      if (isset($group) && $grouptype == 'pool') {
        $unused = array_diff($group, $oldquestions);
        if (count($unused) == 0) {
          //if all of them have been used, just make sure we don't pick
          //the current one again
          $unused = array_diff($group, array($oldquestion));
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
    // make sure they're ints
    $newq = intval($newq);
    $newseed = intval($newseed);
    return array($newq, $newseed);
  }

  /**
   * Overrides assessment settings with practice mode defaults
   * @return void
   */
  public function overridePracticeSettings() {
    if ($this->assessData['displaymethod'] != 'video_cued') {
      $this->assessData['displaymethod'] = 'skip';
    }
    $this->assessData['submitby'] = 'by_question';
    $this->assessData['showscores'] = 'during';
    $this->assessData['showans'] = 'with_score';
    $this->assessData['deftries'] = 999; // unlimited
    $this->assessData['defregens'] = 999; // unlimited
    $this->assessData['shuffle'] &= ~4;  // disable "all stu same version"
    $this->assessData['timelimit'] = 0;
    $this->assessData['retake_penalty'] = array('penalty'=>0, 'n'=>1);
    unset($this->assessData['allowed_attempts']);
    foreach ($this->questionData as $i=>$v) {
      $this->questionData[$i]['tries_max'] = 999; // unlimited
      $this->questionData[$i]['regens_max'] = 999; // unlimited
      $this->questionData[$i]['retry_penalty'] = 0;
      $this->questionData[$i]['showans'] = 'with_score';
    }
  }

  /**
   * Redeem LatePass(es) to extend the assessment
   * @param  int  $uid              User ID
   * @param  integer $latepasshrs   Number of hours a latepass extends assessments
   * @param  integer $courseenddate The course end date
   * @return boolean  True if latepass successfully redeemed
   */
  public function redeemLatePass($uid, $latepasshrs=24, $courseenddate=2000000000) {
    $now = time();
    if ($this->assessData['can_use_latepass'] > 0) {
      $LPneeded = $this->assessData['can_use_latepass'];
      $stm = $this->DBH->prepare("UPDATE imas_students SET latepass=latepass-:lps WHERE userid=:userid AND courseid=:courseid AND latepass>=:lps2");
      $stm->execute(array(
        ':lps'=>$LPneeded,
        ':lps2'=>$LPneeded,
        ':userid'=>$uid,
        ':courseid'=>$this->cid
      ));
      if ($stm->rowCount()>0) {
        $enddate = min(
          strtotime("+".($latepasshrs*$LPneeded)." hours", $this->assessData['enddate']),
          $courseenddate
        );
        if ($LPcutoff>0) {
          $enddate = min($enddate, $LPcutoff);
        }
        if ($this->assessData['hasexception']) { //already have exception
          $stm = $this->DBH->prepare("UPDATE imas_exceptions SET enddate=:enddate,islatepass=islatepass+:lps WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
          $stm->execute(array(':lps'=>$LPneeded, ':userid'=>$uid,
            ':assessmentid'=>$this->curAid, ':enddate'=>$enddate));
        } else {
          $stm = $this->DBH->prepare("INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES (:userid, :assessmentid, :startdate, :enddate, :islatepass, :itemtype)");
          $stm->execute(array(':userid'=>$uid, ':assessmentid'=>$this->curAid,
            ':startdate'=>$this->assessData['startdate'], ':enddate'=>$enddate,
            ':islatepass'=>$LPneeded, ':itemtype'=>'A'));
        }
        return true;
      }
    }
    return false;
  }

  /**
   * Parses and reformats viddata
   * @return array JSON object with video id and cues
   */
  public function getVideoCues() {
    if ($this->assessData['viddata'] === '') {
      return array();
    }
    $viddata = json_decode($this->assessData['viddata'], true);
    if ($viddata !== null) {
      return $viddata;
    }
    $tmpdata = unserialize($this->assessData['viddata']);
    $out = array();
    $vidid = array_shift($tmpdata);
    if (is_array($vidid)) {
      $out['vidid'] = $vidid[0];
      $out['vidar'] = $vidid[1];
    } else {
      $out['vidid'] = $vidid;
      $out['vidar'] = "16:9";
    }
    $out['cues'] = array();
    foreach ($tmpdata as $cue=>$data) {
      $out['cues'][$cue] = array(
        'title' => $data[0]
      );
      if (isset($data[1])) {
        $out['cues'][$cue]['time'] = intval($data[1]);
      }
      if (isset($data[2])) {
        $out['cues'][$cue]['qn'] = intval($data[2]);
      }
      if (isset($data[3])) {
        $out['cues'][$cue]['followuptime'] = intval($data[3]);
        $out['cues'][$cue]['followuplink'] = $data[4];
        $out['cues'][$cue]['followuptitle'] = $data[5];
      }
    }
    return $out;
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
      $settings['points_possible'] = $defaults['defpoints'];
    } else {
      $settings['points_possible'] = $settings['points'];
    }
    unset($settings['points']);
    if ($settings['attempts'] == 9999) {
      $settings['tries_max'] = $defaults['deftries'];
    } else {
      $settings['tries_max'] = $settings['attempts'];
    }
    if ($settings['penalty'] == 9999) {
      $settings['retry_penalty'] = $defaults['defpenalty'];
      $settings['retry_penalty_after'] = $defaults['defpenalty_after'];
    } else {
      if ($settings['penalty'][0]==='L') {
        $settings['retry_penalty_after'] = 'last';
        $settings['retry_penalty'] = intval(substr($settings['penalty'], 1));
      } else if ($settings['penalty'][0]==='S') {
        $settings['retry_penalty_after'] = intval($settings['penalty'][1]);
        $settings['retry_penalty'] = intval(substr($settings['penalty'], 2));
      } else {
        $settings['retry_penalty_after'] = 1;
        $settings['retry_penalty'] = intval($settings['penalty']);
      }
    }

    if ($settings['regen'] == 1 || $defaults['submitby'] == 'by_assessment') {
      $settings['regens_max'] = 1;
    } else {
      $settings['regens_max'] = $defaults['defregens'];
      if (!empty($defaults['attemptext'])) {
          // extend attempts if exception set
          $settings['regens_max'] += $defaults['attemptext'];
      }
    }

    $settings['jump_to_answer'] = false;
    if ($settings['showans'] == '0') {
      $settings['showans'] = $defaults['showans'];
      if ($settings['showans'] == 'after_n') {
        $settings['showans_aftern'] = $defaults['showans_aftern'];
      } else {
        $settings['showans_aftern'] = 0;
      }
      $settings['jump_to_answer'] = $defaults['jump_to_answer'];
    } else if (is_numeric($settings['showans'])) {
      $settings['showans'] = 'after_n';
      $settings['showans_aftern'] = intval($settings['showans']);
    }
    if ($settings['showans'] == 'N') {
      $settings['showans'] = 'never';
    }
    /* question showans limited to never / default

    else if ($settings['showans'] == 'L') {
      $settings['showans'] = 'after_lastattempt';
    } else if ($settings['showans'] == 'J') {
      $settings['showans'] = 'after_lastattempt';
      $settings['jump_to_answer'] = true;
    } else if ($settings['showans'] == 'T') {
      $settings['showans'] = 'after_attempt';
    } else if ($settings['showans'] == 'W') {
      $settings['showans'] = 'with_score';
    }
    */

    if ($settings['showhints'] == -1) {
      $settings['showhints'] = $defaults['showhints'];
    }

    if ($settings['showwork'] == -1) {
      $settings['showwork'] = $defaults['showwork'];
    }

    if (!empty($settings['fixedseeds'])) {
      $settings['fixedseeds'] = array_map('intval', explode(',', $settings['fixedseeds']));
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
    // set global assessUIver
    $GLOBALS['assessUIver'] = $settings['ver'];
    $GLOBALS['useeqnhelper'] = ($settings['eqnhelper'] > 0);
    $GLOBALS['showtips'] = $settings['showtips'];

    $settings['enddate_in'] = $settings['enddate'] - time() - 5;

    // adjust for language change
    $settings['deftries'] = $settings['defattempts'];

    //break apara defpenalty, defregenpenalty
    if ($settings['defpenalty'][0]==='L') {
      $settings['defpenalty_after'] = 'last';
      $settings['defpenalty'] = intval(substr($settings['defpenalty'], 1));
    } else if ($settings['defpenalty'][0]==='S') {
      $settings['defpenalty_after'] = intval($settings['defpenalty'][1]);
      $settings['defpenalty'] = intval(substr($settings['defpenalty'], 2));
    } else {
      $settings['defpenalty_after'] = 1;
    }

    if ($settings['defregenpenalty'][0]==='S') {
      $settings['defregenpenalty_after'] = intval($settings['defregenpenalty'][1]);
      $settings['defregenpenalty'] = intval(substr($settings['defregenpenalty'], 2));
    } else {
      $settings['defregenpenalty_after'] = 1;
      $settings['defregenpenalty'] = intval($settings['defregenpenalty']);
    }
    $settings['retake_penalty'] = array(
      'penalty' => intval($settings['defregenpenalty']),
      'n' => intval($settings['defregenpenalty_after'])
    );

    // if LivePoll, force all stu same random seed
    // force by-question submission
    if ($settings['displaymethod'] === 'livepoll') {
      $settings['shuffle'] = $settings['shuffle'] | 4;
      $settings['submitby'] = 'by_question';
    }

    //if by-assessment, define attempt values
    if ($settings['submitby'] == 'by_assessment') {
      $settings['allowed_attempts'] = $settings['defregens'];
    }

    $settings['jump_to_answer'] = false;
    //unpack showans after_#
    if (strlen($settings['showans']) === 7) {
      $settings['showans_aftern'] = intval(substr($settings['showans'], 6));
      $settings['showans'] = 'after_n';
    } else if ($settings['showans'] === 'jump_to_answer') {
      $settings['jump_to_answer'] = true;
      $settings['showans'] = 'after_lastattempt';
    }



    //unpack minscore
    if ($settings['minscore'] > 10000) {
      $settings['minscore'] = $settings['minscore'] - 10000;
      $settings['minscore_type'] = 'percent';
    } else {
      $settings['minscore_type'] = 'points';
    }

    //unpack timelimit
    if ($settings['timelimit'] < 0 || $settings['overtime_grace'] == 0) {
      $settings['timelimit'] = abs($settings['timelimit']);
      $settings['timelimit_type'] = 'kick_out';
      $settings['overtime_grace'] = 0;
    } else {
      $settings['timelimit_type'] = 'allow_overtime';
    }
    $settings['timelimit_multiplier'] = 1;

    //unpack intro
    $introjson = json_decode($settings['intro'], true);
    $pagebreaks = [];
    if ($introjson === null) {
      $settings['interquestion_text'] = array();
    } else {
      $settings['intro'] = $introjson[0];
      $settings['interquestion_text'] = array_slice($introjson, 1);
      foreach ($settings['interquestion_text'] as $k=>$v) {
        if (isset($v['pagetitle'])) {
          $settings['interquestion_text'][$k]['pagetitle'] = html_entity_decode($v['pagetitle']);
        }
        if ($settings['displaymethod'] !== 'full') {
            unset($settings['interquestion_text'][$k]['ispage']);
        } else if (!empty($v['ispage'])) {
            $pagebreaks[] = $v['displayBefore'];
        }
      }
    }
    $settings['pagebreaks'] = $pagebreaks;

    // decode entities in title
    $settings['name'] = html_entity_decode($settings['name']);

    //unpack resources
    $settings['resources'] = array();
    if ($settings['extrefs'] !== '') {
      $settings['extrefs'] = json_decode($settings['extrefs']);
      if ($settings['extrefs'] !== null) {
        $settings['resources'] = $settings['extrefs'];
      }
    }
    unset($settings['extrefs']);

    // handle help features
    $settings['help_features'] = array(
      'message' => ($settings['msgtoinstr'] == 1),
      'forum' => intval($settings['posttoforum'])
    );
    unset($settings['msgtoinstr']);
    unset($settings['posttoforum']);

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
      AssessUtils::isIPinRange($_SERVER['REMOTE_ADDR'], $settings['password'])
    ) {
      $settings['password'] = '';
    }

    //handle safe exam browser passwords
    if ($settings['password'] != '' && !empty($_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH'])) {
      $testhash = hash("sha256", $GLOBALS['urlmode'].$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] . trim($settings['password']));
      if ($testhash == $_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH']) {
        $settings['password'] = '';
      }
    }

    // fix old-format reqscore "grey out"
    if ($settings['reqscore'] < 0) {
        $settings['reqscoretype'] |= 1;
        $settings['reqscore'] = abs($settings['reqscore']);
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
          if (count($pts)==1) { //really old assessment format
            $order[$k] = array(
              'type' => 'pool',
              'n' => 1,
              'replace' => 0,
              'qids' => array_map('intval', $sub)
            );
          } else {
            $order[$k] = array(
              'type' => 'pool',
              'n' => $pts[0],
              'replace' => ($pts[1]==1),
              'qids' => array_map('intval', array_slice($sub, 1))
            );
          }
        } else {
          $order[$k] = intval($v);
        }
      }
      $settings['itemorder'] = $order;
    } else if (!is_array($itemorder)) {
      $settings['itemorder'] = array($itemorder);
    } else {
      $settings['itemorder'] = $itemorder;
    }

    foreach ($settings as $k=>$v) {
      //convert numeric strings to numbers
      if (is_string($v) && is_numeric($v) && $k != 'name') {
        $settings[$k] = $v + 0;
      }
    }

    $settings['showworktype'] = ($settings['showwork'] & 4);
    $settings['showwork'] = ($settings['showwork'] & 3);

    return $settings;
  }

  private function generateTextMap() {
    if (($this->assessData['shuffle']&(1+16+32)) === 0) {
        // no shuffling; skip textmap
        return;
    }
    $textmap = [];
    if (count($this->assessData['interquestion_text']) > 0) {
        $curqn = 0;
        $lastitemfirsttext = 0;
        foreach ($this->assessData['itemorder'] as $item) {
            $thistextmap = [];
            $storedfirst = false;
            for ($i=$lastitemfirsttext; $i < count($this->assessData['interquestion_text']); $i++) {
                $curtext = $this->assessData['interquestion_text'][$i];
                if ($curqn >= $curtext['displayBefore'] && $curqn <= $curtext['displayUntil']) {
                    if (!empty($curtext['ispage'])) {
                        foreach ($thistextmap as $v) {
                            $this->assessData['interquestion_text'][$v]['beforebreak'] = true;
                        }
                        $thistextmap = [];
                        $storedfirst = false;
                        continue; 
                    }
                    $thistextmap[] = $i;
                    if (!$storedfirst) {
                        $lastitemfirsttext = $i;
                        $storedfirst = true;
                    }
                } else if ($curtext['displayBefore'] > $curqn) { // getting too big; stop
                    break;
                }
            }
            if (is_array($item)) { // is a grouping
                if (count($thistextmap) > 0) {
                    foreach ($item['qids'] as $qid) {
                        $textmap[$qid] = $thistextmap;
                    }
                }
                $curqn += $item['n'];
            } else {
                if (count($thistextmap) > 0) {
                    $textmap[$item] = $thistextmap;
                }
                $curqn++;
            }
        }
        foreach ($this->assessData['interquestion_text'] as $k=>$v) {
            if ($v['displayBefore'] >= $curqn) {
                $this->assessData['interquestion_text'][$k]['atend'] = 1;
            }
            if (empty($v['ispage']) && empty($v['beforebreak'])) { 
                // keep original position for pages and text before the break
                unset($this->assessData['interquestion_text'][$k]['displayBefore']);
                unset($this->assessData['interquestion_text'][$k]['displayUntil']);
            }
            unset($this->assessData['interquestion_text'][$k]['beforebreak']);
        }
    }
    $this->assessData['textmap'] = $textmap;
  }

  /**
   * Checks to see if the intro needs any processing and does it
   * @return void
   */
  public function processIntro () {
    if (!isset($this->assessData['intro'])) {
      return false;
    }
    if (preg_match('/ImportFrom:\s*([a-zA-Z]+)(\d+)/',$this->assessData['intro'],$matches)==1) {
      if (strtolower($matches[1])=='link') {
        $stm = $this->DBH->prepare('SELECT text FROM imas_linkedtext WHERE id=:id');
        $stm->execute(array(':id'=>$matches[2]));
        $vals = $stm->fetch(PDO::FETCH_NUM);
        $this->assessData['intro'] = str_replace($matches[0], $vals[0], $this->assessData['intro']);
      } else if (strtolower($matches[1])=='assessment') {
        $stm = $this->DBH->prepare('SELECT intro FROM imas_assessments WHERE id=:id');
        $stm->execute(array(':id'=>$matches[2]));
        $vals = $stm->fetch(PDO::FETCH_NUM);
        $this->assessData['intro'] = str_replace($matches[0], $vals[0], $this->assessData['intro']);
      }
  	}
  }

}
