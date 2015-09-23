<?php

namespace app\controllers\assessment;

use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CopyItemsUtility;
use app\components\filehandler;
use app\controllers\AppController;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Exceptions;
use app\models\Forums;
use app\models\GbCats;
use app\models\Items;
use app\models\Outcomes;
use app\models\Questions;
use app\models\QuestionSet;
use app\models\SetPassword;
use app\models\Student;
use app\models\StuGroupSet;
use app\models\Teacher;
use Yii;
use app\components\AppConstant;
class AssessmentController extends AppController
{
    public function actionShowAssessment()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $assessmentId = isset($params['id']) ? trim($params['id']) : "";
        $to = isset($params['to']) ? $params['to'] : AppConstant::NUMERIC_ZERO;
        $courseId = isset($params['cid']) ? trim($params['cid']) : "";
        $course = Course::getById($courseId);
        $assessment = Assessments::getByAssessmentId($assessmentId);
        $teacher = Teacher::getByUserId($user->getId(), $courseId);
        $assessmentSession = AssessmentSession::getAssessmentSession($user->id, $assessmentId);
        if (!$assessmentSession) {
            $assessmentSessionObject = new AssessmentSession();
            $assessmentSession = $assessmentSessionObject->saveAssessmentSession($assessment, $user->getId());
        }
        $response = AppUtility::showAssessment($user, $params, $assessmentId, $courseId, $assessment, $assessmentSession, $teacher, $to);
        $isQuestions = Questions::getByAssessmentId($assessmentId);
        $this->includeCSS(['showAssessment.css', 'mathtest.css']);
        $this->getView()->registerJs('var imasroot="openmath/";');
        $this->includeJS(['timer.js', 'ASCIIMathTeXImg_min.js', 'general.js', 'eqntips.js', 'editor/tiny_mce.js']);
        $responseData = array('response' => $response, 'isQuestions' => $isQuestions, 'courseId' => $courseId, 'now' => time(), 'assessment' => $assessment, 'assessmentSession' => $assessmentSession, 'isShowExpiredTime' => $to, 'user' => $user, 'course' => $course);
        return $this->render('ShowAssessment', $responseData);
    }

    public function actionLatePass()
    {
        $this->guestUserHandler();
        $assessmentId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $studentId = $this->getAuthenticatedUser();
        $exceptionAssessment = Exceptions::getByAssessmentId($assessmentId);
        $assessment = Assessments::getByAssessmentId($assessmentId);
        $student = Student::getByCourseId($courseId, $studentId);
        $startDate = $assessment->startdate;
        $endDate = $assessment->enddate;
        $wave = AppConstant::NUMERIC_ZERO;
        $param['assessmentid'] = $assessmentId;
        $param['userid'] = $studentId;
        $param['startdate'] = $startDate;
        $param['enddate'] = $endDate;
        $param['waivereqscore'] = $wave;
        $latepass = $student->latepass;
        $student->latepass = $latepass - AppConstant::NUMERIC_ONE;
        $exception = new Exceptions();
        $exception->attributes = $param;
        $exception->save();
        $student->save();
        $this->redirect(AppUtility::getURLFromHome('course', 'course/index?id=' . $assessmentId . '&cid=' . $courseId));
    }

    /**
     * Display password, when assessment need password.
     */
    public function actionPassword()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $model = new SetPassword();
        $assessmentId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $assessment = Assessments::getByAssessmentId($assessmentId);
        if ($this->isPost()) {
            $params = $this->getRequestParams();
            $password = $params['SetPassword']['password'];
            if ($password == $assessment->password) {
                return $this->redirect(AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id . '&cid=' . $course->id));
            } else {
                $this->setErrorFlash(AppConstant::SET_PASSWORD_ERROR);
            }
        }
        $this->includeCSS(['course/items.css']);
        $returnData = array('model' => $model, 'assessments' => $assessment);
        return $this->renderWithData('setPassword', $returnData);
    }

    public function actionPrintTest()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $user = $this->getAuthenticatedUser();
        $isTeacher = false;
        $printData = '';
        if ($user) {
            $assessmentId = $this->getParamVal('aid');
            $assessmentSession = AssessmentSession::getAssessmentSession($user->id, $assessmentId);
            if ($assessmentSession) {
                $courseId = $assessmentSession->assessment->course->id;
                $assessmentName = $assessmentSession->assessment->name;
                $assessmentId = $assessmentSession->assessment->id;
                $course = $assessmentSession->assessment->course->name;
                $teacher = Teacher::getByUserId($user->id, $courseId);
                if ($teacher) {
                    $isTeacher = true;
                    $teacherId = $teacher->id;
                }
                $printData = AppUtility::printTest($teacherId, $isTeacher, $assessmentSession->id, $user, $course);
                $this->includeCSS(['showAssessment.css', 'mathtest.css', 'print.css']);
                $responseData = array('response' => $printData, 'course' => $course, 'assessmentName' => $assessmentName, 'assessmentId' => $assessmentId);
                return $this->renderWithData('printTest', $responseData);
            }
        }
    }

    public function actionAddAssessment()
    {
        $this->getAuthenticatedUser();
        $this->layout = 'master';
        $params = $this->getRequestParams();
        $courseId = $this->getParamVal('cid');
        $block = $this->getParamVal('block');
        $course = Course::getById($courseId);
        $assessmentId = $params['id'];
        $assessmentArray = array();
        list($params, $from, $filter) = $this->blockFilter($params);
        if ($courseId) {
            $assessmentData = Assessments::getByAssessmentId($assessmentId);
            if (isset($params['clearattempts'])) {
                /*
                 * For Updating Question
                 */
                if ($params['clearattempts']=="confirmed") {
                    filehandler::deleteallaidfiles($params['id']);
                    AssessmentSession::deleteByAssessmentId($params['id']);
                    Questions::setWithdrawn($params['id'], AppConstant::NUMERIC_ZERO);
                    return $this->redirect(AppUtility::getURLFromHome('assessment','assessment/add-assessment?cid='.$params['cid'].'&id='.$params['id']));
                } else {
                    $overwriteBody = AppConstant::NUMERIC_ONE;

                    $assessmentName = $assessmentData['name'];
                    $body = "<h3>$assessmentName</h3>";
                    $body .= "<p>Are you SURE you want to delete all attempts (grades) for this assessment?</p>";
                    $body .= "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='".AppUtility::getURLFromHome('assessment','assessment/add-assessment?cid='.$params['cid'].'&id='.$params['id'].'&clearattempts=confirmed')."'\">\n";
                    $body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='".AppUtility::getURLFromHome('assessment','assessment/add-assessment?cid='.$params['cid'].'&id='.$params['id'])."'\"></p>\n";
                }
            } elseif ($params['name'] != null) {//if the form has been submitted
                if ($params['avail'] == AppConstant::NUMERIC_ONE) {
                    if ($params['sdatetype'] == AppConstant::NUMERIC_ZERO) {
                        $startDate = AppConstant::NUMERIC_ZERO;
                    } else {
                        $startDate = AssessmentUtility::parsedatetime($params['sdate'], $params['stime']);
                    }
                    if ($params['edatetype'] == AppConstant::ALWAYS_TIME) {
                        $endDate = AppConstant::ALWAYS_TIME;
                    } else {
                        $endDate = AssessmentUtility::parsedatetime($params['edate'], $params['etime']);
                    }
                    if ($params['doreview'] == AppConstant::NUMERIC_ZERO) {
                        $reviewDate = AppConstant::NUMERIC_ZERO;
                    } else if ($params['doreview'] == AppConstant::ALWAYS_TIME) {
                        $reviewDate = AppConstant::ALWAYS_TIME;
                    } else {
                        $reviewDate = AssessmentUtility::parsedatetime($params['rdate'], $params['rtime']);
                    }
                } else {
                    $startDate = AppConstant::NUMERIC_ZERO;
                    $endDate = AppConstant::ALWAYS_TIME;
                    $reviewDate = AppConstant::NUMERIC_ZERO;
                }
                if (isset($params['shuffle'])) {
                    $shuffle = AppConstant::NUMERIC_ONE;
                } else {
                    $shuffle = AppConstant::NUMERIC_ZERO;
                }
                if (isset($params['sameseed'])) {
                    $shuffle += AppConstant::NUMERIC_TWO;
                }
                if (isset($params['samever'])) {
                    $shuffle += AppConstant::NUMERIC_FOUR;
                }
                if (isset($params['reattemptsdiffver']) && $params['deffeedback'] != "Practice" && $params['deffeedback'] != "Homework") {
                    $shuffle += AppConstant::NUMERIC_EIGHT;
                }
                if ($params['minscoretype'] == AppConstant::NUMERIC_ONE && trim($params['minscore']) != '' && $params['minscore'] > AppConstant::NUMERIC_ZERO) {
                    $params['minscore'] = intval($params['minscore']) + AppConstant::NUMERIC_TEN_THOUSAND;
                }
                $isGroup = $params['isgroup'];
                if (isset($params['showhints'])) {
                    $showHints = AppConstant::NUMERIC_ONE;
                } else {
                    $showHints = AppConstant::NUMERIC_ZERO;
                }
                if (isset($params['istutorial'])) {
                    $isTutorial = AppConstant::NUMERIC_ONE;
                } else {
                    $isTutorial = AppConstant::NUMERIC_ZERO;
                }
                $tutorEdit = intval($params['tutoredit']);
                $params['allowlate'] = intval($params['allowlate']);
                if (isset($params['latepassafterdue']) && $params['allowlate'] > AppConstant::NUMERIC_ZERO) {
                    $params['allowlate'] += AppConstant::NUMERIC_TEN;
                }
                $timeLimit = $params['timelimit'] * AppConstant::SECONDS;
                if (isset($params['timelimitkickout'])) {
                    $timeLimit = AppConstant::NUMERIC_NEGATIVE_ONE * $timeLimit;
                }
                if (isset($params['usedeffb'])) {
                    $defFeedbackText = $params['deffb'];
                } else {
                    $defFeedbackText = '';
                }
                if ($params['deffeedback'] == "Practice" || $params['deffeedback'] == "Homework") {
                    $defFeedback = $params['deffeedback'] . '-' . $params['showansprac'];
                } else {
                    $defFeedback = $params['deffeedback'] . '-' . $params['showans'];
                }
                if (!isset($params['doposttoforum'])) {
                    $params['posttoforum'] = AppConstant::NUMERIC_ZERO;
                }
                if (isset($params['msgtoinstr'])) {
                    $params['msgtoinstr'] = AppConstant::NUMERIC_ONE;
                } else {
                    $params['msgtoinstr'] = AppConstant::NUMERIC_ZERO;
                }
                if ($params['skippenalty'] == AppConstant::NUMERIC_TEN) {
                    $params['defpenalty'] = 'L' . $params['defpenalty'];
                } else if ($params['skippenalty'] > AppConstant::NUMERIC_ZERO) {
                    $params['defpenalty'] = 'S' . $params['skippenalty'] . $params['defpenalty'];
                }
                if (!isset($params['copyendmsg'])) {
                    $endMsg = '';
                }
                if ($params['copyfrom'] != AppConstant::NUMERIC_ZERO) {
                    $copyAssessement = Assessments::getByAssessmentId($params['copyfrom']);
                    $timeLimit = $copyAssessement['timelimit'];
                    $params['minscore'] = $copyAssessement['minscore'];
                    $params['displaymethod'] = $copyAssessement['displaymethod'];
                    $params['defpoints'] = $copyAssessement['displaymethod'];
                    $params['defattempts'] = $copyAssessement['defattempts'];
                    $params['defpenalty'] = $copyAssessement['defpenalty'];
                    $defFeedback = $copyAssessement['deffeedback'];
                    $shuffle = $copyAssessement['shuffle'];
                    $params['gbcat'] = $copyAssessement['gbcategory'];
                    $params['assmpassword'] = $copyAssessement['password'];
                    $params['cntingb'] = $copyAssessement['cntingb'];
                    $tutorEdit = $copyAssessement['tutoredit'];
                    $params['showqcat'] = $copyAssessement['showcat'];
                    $copyIntro = $copyAssessement['intro'];
                    $copySummary = $copyAssessement['summary'];
                    $copyStartDate = $copyAssessement['startdate'];
                    $copyEndDate = $copyAssessement['enddate'];
                    $copyReviewDate = $copyAssessement['reviewdate'];
                    $isGroup = $copyAssessement['isgroup'];
                    $params['groupmax'] = $copyAssessement['groupmax'];
                    $params['groupsetid'] = $copyAssessement['groupsetid'];
                    $showHints = $copyAssessement['showhints'];
                    $params['reqscore'] = $copyAssessement['reqscore'];
                    $params['reqscoreaid'] = $copyAssessement['reqscoreaid'];
                    $params['noprint'] = $copyAssessement['noprint'];
                    $params['allowlate'] = $copyAssessement['allowlate'];
                    $params['eqnhelper'] = $copyAssessement['eqnhelper'];
                    $endMsg = $copyAssessement['endmsg'];
                    $params['caltagact'] = $copyAssessement['caltag'];
                    $params['caltagrev'] = $copyAssessement['calrtag'];
                    $defFeedbackText = $copyAssessement['deffeedbacktext'];
                    $params['showtips'] = $copyAssessement['showtips'];
                    $params['exceptionpenalty'] = $copyAssessement['exceptionpenalty'];
                    $params['ltisecret'] = $copyAssessement['ltisecret'];
                    $params['msgtoinstr'] = $copyAssessement['msgtoinstr'];
                    $params['posttoforum'] = $copyAssessement['posttoforum'];
                    $isTutorial = $copyAssessement['istutorial'];
                    $params['defoutcome'] = $copyAssessement['defoutcome'];
                    if (isset($params['copyinstr'])) {
                        $params['intro'] = $copyIntro;
                    }
                    if (isset($params['copysummary'])) {
                        $params['summary'] = $copySummary;
                    }
                    if (isset($params['copydates'])) {
                        $startDate = $copyStartDate;
                        $endDate = $copyEndDate;
                        $reviewDate = $copyReviewDate;
                    }
                    if (isset($params['removeperq'])) {
                        Questions::setQuestionByAssessmentId($assessmentId);
                    }
                }
                if ($params['deffeedback'] == "Practice") {
                    $params['cntingb'] = $params['pcntingb'];
                }
                if (isset($params['ltisecret'])) {
                    $params['ltisecret'] = trim($params['ltisecret']);
                } else {
                    $params['ltisecret'] = '';
                }
                /*is updating, switching from nongroup to group, and not creating new groupset, check if groups and asids already exist
                 *if so, cannot handle
                 */
                $updategroupset = '';
                if (isset($params['id']) && $params['isgroup'] > AppConstant::NUMERIC_ZERO && $params['groupsetid'] > AppConstant::NUMERIC_ZERO) {
                    $isok = true;
                    $query = $assessmentData['isgroup'];
                    if ($query == AppConstant::NUMERIC_ZERO) {
                        /*check to see if students have already started assessment
                        *don't really care if groups exist - just whether asids exist
                        */
                        $assessmentSessionData = AssessmentSession::getByUserCourseAssessmentId($assessmentId, $courseId);
                        if ($assessmentSessionData > AppConstant::NUMERIC_ZERO) {
                            $this->setErrorFlash(AppConstant::ASSESSMENT_ALREADY_STARTED);
                            exit;
                        }
                    }
                    $updategroupset = "groupsetid='{$params['groupsetid']}',";
                }
                if ($params['isgroup'] > AppConstant::NUMERIC_ZERO && isset($params['groupsetid']) && $params['groupsetid'] == AppConstant::NUMERIC_ZERO) {
                    /*
                     * create new groupset
                     */
                    $stuGroupSet = new StuGroupSet();
                    $query = $stuGroupSet->createGroupSet($courseId, $params['name']);
                    $params['groupsetid'] = $query;
                    $updategroupset = "groupsetid='{$params['groupsetid']}',";
                }
                $calTag = $params['caltagact'];
                $calrTag = $params['caltagrev'];
                require_once("../components/htmLawed.php");
                $params['name'] = htmlentities(stripslashes($params['name']));
                $htmlawedconfig = array('elements'=>'*-script');
                if ($params['summary'] == AppConstant::DEFAULT_ASSESSMENT_SUMMARY) {
                    $params['summary'] = '';
                } else {
                    $params['summary'] = addslashes(htmLawed(stripslashes($params['summary']),$htmlawedconfig));
                }
                if ($params['intro'] == AppConstant::DEFAULT_ASSESSMENT_INTRO) {
                    $params['intro'] = '';
                } else {
                    $params['intro'] = addslashes(htmLawed(stripslashes($params['intro']),$htmlawedconfig));
                }
                $assessmentArray['courseid'] = $params['cid'];
                $assessmentArray['name'] = $params['name'];
                $assessmentArray['summary'] = $params['summary'];
                $assessmentArray['intro'] = $params['intro'];
                $assessmentArray['avail'] = $params['avail'];
                $assessmentArray['password'] = $params['assmpassword'];
                $assessmentArray['displaymethod'] = $params['displaymethod'];
                $assessmentArray['defpoints'] = $params['defpoints'];
                $assessmentArray['defattempts'] = $params['defattempts'];
                $assessmentArray['eqnhelper'] = $params['eqnhelper'];
                $assessmentArray['msgtoinstr'] = $params['msgtoinstr'];
                $assessmentArray['posttoforum'] = $params['posttoforum'];
                $assessmentArray['showtips'] = $params['showtips'];
                $assessmentArray['allowlate'] = $params['allowlate'];
                $assessmentArray['noprint'] = $params['noprint'];
                $assessmentArray['gbcategory'] = $params['gbcat'];
                $assessmentArray['cntingb'] = $params['cntingb'];
                $assessmentArray['minscore'] = $params['minscore'];
                $assessmentArray['reqscore'] = $params['reqscore'];
                $assessmentArray['reqscoreaid'] = $params['reqscoreaid'];
                $assessmentArray['exceptionpenalty'] = $params['exceptionpenalty'];
                $assessmentArray['groupmax'] = $params['groupmax'];
                $assessmentArray['groupsetid'] = $params['groupsetid'];
                $assessmentArray['defoutcome'] = $params['defoutcome'];
                $assessmentArray['showcat'] = $params['showqcat'];
                $assessmentArray['ltisecret'] = $params['ltisecret'];
                $assessmentArray['defpenalty'] = $params['defpenalty'];
                $assessmentArray['startdate'] = $startDate;
                $assessmentArray['enddate'] = $endDate;
                $assessmentArray['reviewdate'] = $reviewDate;
                $assessmentArray['timelimit'] = $timeLimit;
                $assessmentArray['shuffle'] = $shuffle;
                $assessmentArray['deffeedback'] = $defFeedback;
                $assessmentArray['tutoredit'] = $tutorEdit;
                $assessmentArray['showhints'] = $showHints;
                $assessmentArray['endmsg'] = $endMsg;
                $assessmentArray['deffeedbacktext'] = $defFeedbackText;
                $assessmentArray['istutorial'] = $isTutorial;
                $assessmentArray['isgroup'] = $isGroup;
                $assessmentArray['caltag'] = $calTag;
                $assessmentArray['calrtag'] = $calrTag;
                if ($params['id']) {  //already have id; update
                    if ($isGroup == AppConstant::NUMERIC_ZERO) { //set agroupid=0 if switching from groups to non groups
                        $query = $assessmentData['isgroup'];
                        if ($query > AppConstant::NUMERIC_ZERO) {
                            AssessmentSession::setGroupId($assessmentId);
                        }
                    } else { /*if switching from nogroup to groups and groups already exist, need set agroupids if asids exist already
                              *NOT ALLOWED CURRENTLY
                              */
                    }
                    $assessmentArray['id'] = $params['id'];
                    Assessments::updateAssessment($assessmentArray);
                    if ($from == 'gb') {
                        return $this->redirect(AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $courseId));
                    } else if ($from == 'mcd') {
                        return $this->redirect(AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $courseId));
                    } else if ($from == 'lti') {
                        return $this->redirect(AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $courseId));
                    } else {
                        return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $courseId));
                    }
                } else { //add new
                    $assessment = new Assessments();
                    $newAssessmentId = $assessment->createAssessment($assessmentArray);
                    $itemAssessment = new Items();
                    $itemId = $itemAssessment->saveItems($courseId, $newAssessmentId, 'Assessment');
                    $courseItemOrder = Course::getItemOrder($courseId);
                    $itemOrder = $courseItemOrder->itemorder;
                    $items = unserialize($itemOrder);
                    $blockTree = explode('-', $block);
                    $sub =& $items;
                    for ($i = AppConstant::NUMERIC_ONE; $i < count($blockTree); $i++) {
                        $sub =& $sub[$blockTree[$i] - AppConstant::NUMERIC_ONE]['items']; //-1 to adjust for 1-indexing
                    }
                    if ($filter == 'b') {
                        $sub[] = intval($itemId);
                    } else if ($filter == 't') {
                        array_unshift($sub, intval($itemId));
                    }
                    $itemList = serialize($items);
                    Course::setItemOrder($itemList, $courseId);
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $course->id . '&aid=' . $newAssessmentId));
                }
            } else {
                if (isset($params['id'])) {//page load in modify mode
                    $title = AppConstant::MODIFY_ASSESSMENT;
                    $pageTitle = AppConstant::MODIFY_ASSESSMENT;
                    $assessmentSessionData = AssessmentSession::getByUserCourseAssessmentId($assessmentId, $courseId);
                    list($testType, $showAnswer) = explode('-', $assessmentData['deffeedback']);
                    $startDate = $assessmentData['startdate'];
                    $endDate = $assessmentData['enddate'];
                    $gradebookCategory = $assessmentData['gbcategory'];
                    if ($testType == 'Practice') {
                        $pointCountInGb = $assessmentData['cntingb'];
                        $countInGb = AppConstant::NUMERIC_ONE;
                    } else {
                        $countInGb = $assessmentData['cntingb'];
                        $pointCountInGb = AppConstant::NUMERIC_THREE;
                    }
                    $showQuestionCategory = $assessmentData['showcat'];
                    $timeLimit = $assessmentData['timelimit'] / AppConstant::SECONDS;
                    if ($assessmentData['isgroup'] == AppConstant::NUMERIC_ZERO) {
                        $assessmentData['groupsetid'] = AppConstant::NUMERIC_ZERO;
                    }
                    if ($assessmentData['deffeedbacktext'] == '') {
                        $useDefFeedback = false;
                        $defFeedback = AppConstant::DEFAULT_FEEDBACK;
                    } else {
                        $useDefFeedback = true;
                        $defFeedback = $assessmentData['deffeedbacktext'];
                    }
                    if ($assessmentData['summary'] == '') {
                        $assessmentData['summary'] = AppConstant::DEFAULT_ASSESSMENT_SUMMARY;
                    }
                    if ($assessmentData['intro'] == '') {
                        $assessmentData['intro'] = AppConstant::DEFAULT_ASSESSMENT_INTRO;
                    }
                    $saveTitle = AppConstant::SAVE_BUTTON;
                } else {//page load in add mode set default values
                    $title = AppConstant::ADD_ASSESSMENT;
                    $pageTitle = AppConstant::ADD_ASSESSMENT;
                    $assessmentData['name'] = AppConstant::DEFAULT_ASSESSMENT_NAME;
                    $assessmentData['summary'] = AppConstant::DEFAULT_ASSESSMENT_SUMMARY;
                    $assessmentData['intro'] = AppConstant::DEFAULT_ASSESSMENT_INTRO;
                    $startDate = time() + AppConstant::SECONDS * AppConstant::SECONDS;
                    $endDate = time() + AppConstant::WEEK_TIME;
                    $assessmentData['startdate'] = $startDate;
                    $assessmentData['enddate'] = $endDate;
                    $assessmentData['avail'] = AppConstant::NUMERIC_ONE;
                    $assessmentData['reviewdate'] = AppConstant::NUMERIC_ZERO;
                    $timeLimit = AppConstant::NUMERIC_ZERO;
                    $assessmentData['displaymethod'] = "SkipAround";
                    $assessmentData['defpoints'] = AppConstant::NUMERIC_TEN;
                    $assessmentData['defattempts'] = AppConstant::NUMERIC_ONE;
                    $assessmentData['password'] = '';
                    $testType = AppConstant::TEST_TYPE;
                    $showAnswer = AppConstant::SHOW_ANSWER;
                    $assessmentData['defpenalty'] = AppConstant::NUMERIC_TEN;
                    $assessmentData['shuffle'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['minscore'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['isgroup'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['showhints'] = AppConstant::NUMERIC_ONE;
                    $assessmentData['reqscore'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['reqscoreaid'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['groupsetid'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['noprint'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['groupmax'] = AppConstant::NUMERIC_SIX;
                    $assessmentData['allowlate'] = AppConstant::NUMERIC_ONE;
                    $assessmentData['exceptionpenalty'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['tutoredit'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['eqnhelper'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['ltisecret'] = '';
                    $assessmentData['caltag'] = AppConstant::CALTAG;
                    $assessmentData['calrtag'] = AppConstant::CALRTAG;
                    $assessmentData['showtips'] = AppConstant::NUMERIC_TWO;
                    $useDefFeedback = false;
                    $defFeedback = AppConstant::DEFAULT_FEEDBACK;
                    $gradebookCategory = AppConstant::NUMERIC_ZERO;
                    $countInGb = AppConstant::NUMERIC_ONE;
                    $pointCountInGb = AppConstant::NUMERIC_THREE;
                    $showQuestionCategory = AppConstant::NUMERIC_ZERO;
                    $assessmentData['posttoforum'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['msgtoinstr'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['defoutcome'] = AppConstant::NUMERIC_ZERO;
                    $assessmentSessionData = false;
                    $saveTitle = AppConstant::CREATE_BUTTON;
                }
                if ($assessmentData['minscore'] > AppConstant::NUMERIC_TEN_THOUSAND) {
                    $assessmentData['minscore'] -= AppConstant::NUMERIC_TEN_THOUSAND;
                    $minScoreType = AppConstant::NUMERIC_ONE; //pct;
                } else {
                    $minScoreType = AppConstant::NUMERIC_ZERO; //points;
                }
                $courseDefTime = $course['deftime'] % AppConstant::NUMERIC_TEN_THOUSAND;
                $hour = floor($courseDefTime / AppConstant::SECONDS) % AppConstant::NUMERIC_TWELVE;
                $minutes = $courseDefTime % AppConstant::SECONDS;
                $am = ($courseDefTime < AppConstant::NUMERIC_TWELVE * AppConstant::SECONDS) ? AppConstant::AM : AppConstant::PM;
                $defTime = (($hour == AppConstant::NUMERIC_ZERO) ? AppConstant::NUMERIC_TWELVE : $hour) . ':' . (($minutes < AppConstant::NUMERIC_TEN) ? '0' : '') . $minutes . ' ' . $am;
                $hour = floor($courseDefTime / AppConstant::SECONDS) % AppConstant::NUMERIC_TWELVE;
                $minutes = $courseDefTime % AppConstant::SECONDS;
                $am = ($courseDefTime < AppConstant::NUMERIC_TWELVE * AppConstant::SECONDS) ? AppConstant::AM : AppConstant::PM;
                $defStartTime = (($hour == AppConstant::NUMERIC_ZERO) ? AppConstant::NUMERIC_TWELVE : $hour) . ':' . (($minutes < AppConstant::NUMERIC_TEN) ? '0' : '') . $minutes . ' ' . $am;
                if ($startDate != AppConstant::NUMERIC_ZERO) {
                    $sDate = AppUtility::tzdate("m/d/Y", $startDate);
                    $sTime = AppUtility::tzdate("g:i a", $startDate);
                } else {
                    $sDate = AppUtility::tzdate("m/d/Y", time());
                    $sTime = $defStartTime;
                }
                if ($endDate != AppConstant::ALWAYS_TIME) {
                    $eDate = AppUtility::tzdate("m/d/Y", $endDate);
                    $eTime = AppUtility::tzdate("g:i a", $endDate);
                } else {
                    $eDate = AppUtility::tzdate("m/d/Y", time() + AppConstant::WEEK_TIME);
                    $eTime = $defTime;
                }
                if ($assessmentData['reviewdate'] > AppConstant::NUMERIC_ZERO) {
                    if ($assessmentData['reviewdate'] == AppConstant::ALWAYS_TIME) {
                        $reviewDate = AppUtility::tzdate("m/d/Y", $assessmentData['enddate'] + AppConstant::WEEK_TIME);
                        $reviewTime = $defTime;
                    } else {
                        $reviewDate = AppUtility::tzdate("m/d/Y", $assessmentData['reviewdate']);
                        $reviewTime = AppUtility::tzdate("g:i a", $assessmentData['reviewdate']);
                    }
                } else {
                    $reviewDate = AppUtility::tzdate("m/d/Y", $assessmentData['enddate'] + AppConstant::WEEK_TIME);
                    $reviewTime = $defTime;
                }
                if (!isset($params['id'])) {
                    $sTime = $defStartTime;
                    $eTime = $defTime;
                    $reviewTime = $defTime;
                }
                if ($assessmentData['defpenalty']{AppConstant::NUMERIC_ZERO} === 'L') {
                    $assessmentData['defpenalty'] = substr($assessmentData['defpenalty'], AppConstant::NUMERIC_ONE);
                    $skipPenalty = AppConstant::NUMERIC_TEN;
                } else if ($assessmentData['defpenalty']{AppConstant::NUMERIC_ZERO} === 'S') {
                    $skipPenalty = $assessmentData['defpenalty']{AppConstant::NUMERIC_ONE};
                    $assessmentData['defpenalty'] = substr($assessmentData['defpenalty'], AppConstant::NUMERIC_TWO);
                } else {
                    $skipPenalty = AppConstant::NUMERIC_ZERO;
                }
                $query = Assessments::getByCourse($courseId);
                $pageCopyFromSelect = array();
                $key = AppConstant::NUMERIC_ZERO;
                if ($query) {
                    foreach ($query as $singleData) {
                        $pageCopyFromSelect['val'][$key] = $singleData['id'];
                        $pageCopyFromSelect['label'][$key] = $singleData['name'];
                        $key++;
                    }
                }
                $query = GbCats::getByCourseId($courseId);
                $pageGradebookCategorySelect = array();
                if ($query) {
                    foreach ($query as $singleData) {
                        $pageGradebookCategorySelect['val'][$key] = $singleData['id'];
                        $pageGradebookCategorySelect['label'][$key] = $singleData['name'];
                        $key++;
                    }
                }
                $query = Outcomes::getByCourse($courseId);
                $pageOutcomes = array();
                if ($query) {
                    foreach ($query as $singleData) {
                        $pageOutcomes[$singleData['id']] = $singleData['name'];
                        $key++;
                    }
                }
                $pageOutcomes[0] = AppConstant::DEFAULT_OUTCOMES;
                $pageOutcomesList = array(array(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ZERO));
                if ($key > AppConstant::NUMERIC_ZERO) {//there were outcomes
                    $query = $course['outcomes'];
                    $outcomeArray = unserialize($query);
                    $result = $this->flatArray($outcomeArray);
                    if ($result) {
                        foreach ($result as $singlePage) {
                            array_push($pageOutcomesList, $singlePage);
                        }
                    }
                }
                $query = StuGroupSet::getByCourseId($courseId);
                $pageGroupSets = array();
                if ($assessmentSessionData && $assessmentData['isgroup'] == AppConstant::NUMERIC_ZERO) {
                    $query = StuGroupSet::getByJoin($courseId);
                } else {
                    $query = StuGroupSet::getByCourseId($courseId);
                }
                $pageGroupSets['val'][0] = AppConstant::NUMERIC_ZERO;
                $pageGroupSets['label'][0] = AppConstant::GROUP_SET;
                $key = AppConstant::NUMERIC_ONE;
                foreach ($query as $singleData) {
                    $pageGroupSets['val'][$key] = $singleData['id'];
                    $pageGroupSets['label'][$key] = $singleData['name'];
                    $key++;
                }
                $pageTutorSelect['label'] = array(AppConstant::TUTOR_NO_ACCESS, AppConstant::TUTOR_READ_SCORES, AppConstant::TUTOR_READ_WRITE_SCORES);
                $pageTutorSelect['val'] = array(AppConstant::NUMERIC_TWO, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ONE);
                $pageForumSelect = array();
                $query = Forums::getByCourse($courseId);
                $pageForumSelect['val'][0] = AppConstant::NUMERIC_ZERO;
                $pageForumSelect['label'][0] = AppConstant::NONE;
                foreach ($query as $singleData) {
                    $pageForumSelect['val'][] = $singleData['id'];
                    $pageForumSelect['label'][] = $singleData['name'];
                }
                $pageAllowLateSelect = array();
                $pageAllowLateSelect['val'][0] = AppConstant::NUMERIC_ZERO;
                $pageAllowLateSelect['label'][0] = AppConstant::NONE;
                $pageAllowLateSelect['val'][1] = AppConstant::NUMERIC_ONE;
                $pageAllowLateSelect['label'][1] = AppConstant::UNLIMITED;
                for ($key = AppConstant::NUMERIC_ONE; $key < AppConstant::NUMERIC_NINE; $key++) {
                    $pageAllowLateSelect['val'][] = $key + AppConstant::NUMERIC_ONE;
                    $pageAllowLateSelect['label'][] = "Up to $key";
                }
            }
        }
        $this->includeCSS(['course/items.css', 'course/course.css']);
        $this->includeJS(["editor/tiny_mce.js","assessment/addAssessment.js", "general.js"]);
        return $this->addAssessmentRenderData($course, $assessmentData, $saveTitle, $pageCopyFromSelect, $timeLimit, $assessmentSessionData, $testType, $skipPenalty, $showAnswer, $startDate, $endDate, $pageForumSelect, $pageAllowLateSelect, $pageGradebookCategorySelect, $gradebookCategory, $countInGb, $pointCountInGb, $pageTutorSelect, $minScoreType, $useDefFeedback, $defFeedback, $pageGroupSets, $pageOutcomesList, $pageOutcomes, $showQuestionCategory, $sDate, $sTime, $eDate, $eTime, $reviewDate, $reviewTime, $title, $pageTitle, $block, $body);
    }

    public function flatArray($outcomesData)
    {
        global $pageOutcomesList;
        if ($outcomesData) {
            foreach ($outcomesData as $singleData) {
                /*
                 * outcome group
                 */
                if (is_array($singleData)) {
                    $pageOutcomesList[] = array($singleData['name'], AppConstant::NUMERIC_ONE);
                    $this->flatArray($singleData['outcomes']);
                } else {
                    $pageOutcomesList[] = array($singleData, AppConstant::NUMERIC_ZERO);
                }
            }
        }
        return $pageOutcomesList;
    }

    public function actionChangeAssessment()
    {
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $params = $this->getRequestParams();
        $courseId =$this->getParamVal('cid');
        $course = Course::getById($courseId);
        $isTeacher = $this->isTeacher($user['id'],$course['id']);
        $key = AppConstant::NUMERIC_ONE;
        $gbCatsId[0] = AppConstant::NUMERIC_ZERO;
        $gbCatsLabel[0] ='Default';
        $gbCatsData = GbCats::getByCourseId($courseId);
        foreach ($gbCatsData as $singleGbCatsData) {
            $gbCatsId[$key] = $singleGbCatsData['id'];
            $gbCatsLabel[$key] = $singleGbCatsData['name'];
            $key++;
        }
        $overWriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        // SECURITY CHECK DATA PROCESSING
        if ($isTeacher != AppConstant::NUMERIC_ONE) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        }
        if($this->isPostMethod()){
            if (isset($params['checked'])) { //if the form has been submitted
                $checked = array();
                foreach ($params['checked'] as $id) {
                    $id = intval($id);
                    if ($id != AppConstant::NUMERIC_ZERO) {
                        $checked[] = $id;
                    }
                }
                $checkedList = "'" . implode("','", $checked) . "'";
                $count = AppConstant::NUMERIC_ZERO;
                foreach($params as $key=>$singleParams){
                    if(!is_array($key) && substr($key,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_THREE) === 'chg'){
                        $count++;
                    }
                }
                if($count == AppConstant::NUMERIC_ZERO && !isset($params['removeperq']) && !isset($params['chgendmsg'])){
                    $this->setWarningFlash(AppConstant::NO_SETTING);
                    return $this->redirect('change-assessment?cid='.$courseId);
                }
                $sets = array();
                if (isset($params['chgdocopyopt'])) {
                    $toCopy = 'password,timelimit,displaymethod,defpoints,defattempts,deffeedback,defpenalty,eqnhelper,showhints,allowlate,noprint,shuffle,gbcategory,cntingb,caltag,calrtag,minscore,exceptionpenalty,groupmax,showcat,msgtoinstr,posttoforum';
                    $row = Assessments::CommonMethodToGetAssessmentData($toCopy,$params['copyopt']);
                    $toCopyArray = explode(',', $toCopy);
                    foreach ($toCopyArray as $k=>$item) {
                        $sets[] = "$item='".addslashes($row[$k])."'";
                    }
                } else {
                    $turnOnShuffle = AppConstant::NUMERIC_ZERO;
                    $turnOffShuffle = AppConstant::NUMERIC_ZERO;
                    if (isset($params['chgshuffle'])) {
                        if (isset($params['shuffle'])) {
                            $turnOnShuffle += AppConstant::NUMERIC_ONE;
                        } else {
                            $turnOffShuffle += AppConstant::NUMERIC_ONE;
                        }
                    }
                    if (isset($params['chgsameseed'])) {
                        if (isset($params['sameseed'])) {
                            $turnOnShuffle += AppConstant::NUMERIC_TWO;
                        } else {
                            $turnOffShuffle += AppConstant::NUMERIC_TWO;
                        }
                    }
                    if (isset($params['chgsamever'])) {
                        if (isset($params['samever'])) {
                            $turnOnShuffle += AppConstant::NUMERIC_FOUR;
                        } else {
                            $turnOffShuffle += AppConstant::NUMERIC_FOUR;
                        }
                    }
                    if (isset($params['chgdefattempts'])) {
                        if (isset($params['reattemptsdiffver'])) {
                            $turnOnShuffle += AppConstant::NUMERIC_EIGHT;
                        } else {
                            $turnOffShuffle += AppConstant::NUMERIC_EIGHT;
                        }
                    }
                    if (isset($params['chgallowlate'])) {
                        $allowLate = intval($params['allowlate']);
                        if (isset($params['latepassafterdue']) && $allowLate > AppConstant::NUMERIC_ZERO) {
                            $allowLate += AppConstant::NUMERIC_TEN;
                        }
                    }
                    if (isset($params['chghints'])) {
                        if (isset($params['showhints'])) {
                            $showHints = AppConstant::NUMERIC_ONE;
                        } else {
                            $showHints = AppConstant::NUMERIC_ZERO;
                        }
                    }
                    if ($params['skippenalty'] == AppConstant::NUMERIC_TEN) {
                        $params['defpenalty'] = 'L' . $params['defpenalty'];
                    } else if ($params['skippenalty'] > AppConstant::NUMERIC_ZERO) {
                        $params['defpenalty'] = 'S' . $params['skippenalty'] . $params['defpenalty'];
                    }
                    if ($params['deffeedback'] == "Practice" || $params['deffeedback'] == "Homework") {
                        $defaultFeedback = $params['deffeedback'] . '-' . $params['showansprac'];
                        if (($turnOffShuffle & AppConstant::NUMERIC_EIGHT) != AppConstant::NUMERIC_EIGHT) {
                            $turnOffShuffle += AppConstant::NUMERIC_EIGHT;
                        }
                        if (($turnOnShuffle & AppConstant::NUMERIC_EIGHT) == AppConstant::NUMERIC_EIGHT) {
                            $turnOnShuffle -= AppConstant::NUMERIC_EIGHT;
                        }
                    } else {
                        $defaultFeedback = $params['deffeedback'] . '-' . $params['showans'];
                    }
                    if (isset($params['chgtimelimit'])) {
                        $timeLimit = $params['timelimit'] * AppConstant::NUMERIC_SIXTY;
                        if (isset($params['timelimitkickout'])) {
                            $timeLimit = AppConstant::NUMERIC_NEGATIVE_ONE * $timeLimit;
                        }
                        $sets[] = "timelimit='$timeLimit'";
                    }
                    if (isset($params['chgtutoredit'])) {
                        $sets[] = "tutoredit='{$params['tutoredit']}'";
                    }
                    if (isset($params['chgdisplaymethod'])) {
                        $sets[] = "displaymethod='{$params['displaymethod']}'";
                    }
                    if (isset($params['chgdefpoints'])) {
                        $sets[] = "defpoints='{$params['defpoints']}'";
                    }
                    if (isset($params['chgdefattempts'])) {
                        $sets[] = "defattempts='{$params['defattempts']}'";
                    }
                    if (isset($params['chgdefpenalty'])) {
                        $sets[] = "defpenalty='{$params['defpenalty']}'";
                    }
                    if (isset($params['chgfeedback'])) {
                        $sets[] = "deffeedback='$defaultFeedback'";
                    }
                    if (isset($params['chggbcat'])) {
                        $sets[] = "gbcategory='{$params['gbcat']}'";
                    }
                    if (isset($params['chgallowlate'])) {
                        $sets[] = "allowlate='$allowLate'";
                    }
                    if (isset($params['chgexcpen'])) {
                        $sets[] = "exceptionpenalty='{$params['exceptionpenalty']}'";
                    }
                    if (isset($params['chgpassword'])) {
                        $sets[] = "password='{$params['assmpassword']}'";
                    }
                    if (isset($params['chghints'])) {
                        $sets[] = "showhints='$showHints'";
                    }
                    if (isset($params['chgshowtips'])) {
                        $sets[] = "showtips='{$params['showtips']}'";
                    }
                    if (isset($params['chgnoprint'])) {
                        $sets[] = "noprint='{$params['noprint']}'";
                    }
                    if (isset($params['chgisgroup'])) {
                        $sets[] = "isgroup='{$params['isgroup']}'";
                    }
                    if (isset($params['chggroupmax'])) {
                        $sets[] = "groupmax='{$params['groupmax']}'";
                    }
                    if (isset($params['chgcntingb'])) {
                        $sets[] = "cntingb='{$params['cntingb']}'";
                    }
                    if (isset($params['chgminscore'])) {
                        if ($params['minscoretype'] == AppConstant::NUMERIC_ONE && trim($params['minscore']) != '' && $params['minscore'] > AppConstant::NUMERIC_ZERO) {
                            $params['minscore'] = intval($params['minscore']) + AppConstant::NUMERIC_TEN_THOUSAND;
                        }
                        $sets[] = "minscore='{$params['minscore']}'";
                    }
                    if (isset($params['chgshowqcat'])) {
                        $sets[] = "showcat='{$params['showqcat']}'";
                    }
                    if (isset($params['chgeqnhelper'])) {
                        $sets[] = "eqnhelper='{$params['eqnhelper']}'";
                    }
                    if (isset($params['chgcaltag'])) {
                        $caltag = $params['caltagact'];
                        $sets[] = "caltag='$caltag'";
                        $calrtag = $params['caltagrev'];
                        $sets[] = "calrtag='$calrtag'";
                    }
                    if (isset($params['chgmsgtoinstr'])) {
                        if (isset($params['msgtoinstr'])) {
                            $sets[] = "msgtoinstr=1";
                        } else {
                            $sets[] = "msgtoinstr=0";
                        }
                    }
                    if (isset($params['chgposttoforum'])) {
                        if (isset($params['doposttoforum'])) {
                            $sets[] = "posttoforum='{$params['posttoforum']}'";
                        } else {
                            $sets[] = "posttoforum=0";
                        }
                    }
                    if (isset($params['chgdeffb'])) {
                        if (isset($params['usedeffb'])) {
                            $sets[] = "deffeedbacktext='{$params['deffb']}'";
                        } else {
                            $sets[] = "deffeedbacktext=''";
                        }
                    }
                    if (isset($params['chgreqscore'])) {
                        $sets[] = "reqscore=0";
                        $sets[] = "reqscoreaid=0";
                    }
                    if (isset($params['chgistutorial'])) {
                        if (isset($params['istutorial'])) {
                            $sets[] = "istutorial=1";
                        } else {
                            $sets[] = "istutorial=0";
                        }
                    }
                    if ($turnOnShuffle != AppConstant::NUMERIC_ZERO || $turnOffShuffle != AppConstant::NUMERIC_ZERO) {
                        $shuffle = "shuffle = ((shuffle";
                        if ($turnOffShuffle > AppConstant::NUMERIC_ZERO) {
                            $shuffle .= " & ~$turnOffShuffle)";
                        } else {
                            $shuffle .= ")";
                        }
                        if ($turnOnShuffle > AppConstant::NUMERIC_ZERO) {
                            $shuffle .= " | $turnOnShuffle";
                        }
                        $shuffle .= ")";
                        $sets[] = $shuffle;
                    }
                }
                if (isset($params['chgavail'])) {
                    $sets[] = "avail='{$params['avail']}'";
                }
                if (isset($params['chgintro'])) {
                    $assessmentData = Assessments::getByAssessmentId($params['intro']);
                    $sets[] = "intro='" . addslashes($assessmentData['intro']) . "'";
                }
                if (isset($params['chgsummary'])) {
                    $assessmentData = Assessments::getByAssessmentId($params['summary']);
                    $sets[] = "summary='" . addslashes($assessmentData['summary']) . "'";
                }
                if (isset($params['chgdates'])) {
                    $assessmentData = Assessments::getByAssessmentId($params['dates']);
                    $sets[] = "startdate='{$assessmentData['startdate']}',enddate='{$assessmentData['enddate']}',reviewdate='{$assessmentData['reviewdate']}'";
                }
                if (isset($params['chgcopyendmsg'])) {
                    $assessmentData = Assessments::getByAssessmentId($params['copyendmsg']);
                    $sets[] = "endmsg='" . addslashes($assessmentData['endmsg']) . "'";
                }
                if (count($sets) > AppConstant::NUMERIC_ZERO) {
                    $setsList = implode(',', $sets);

                    Assessments::updateAssessmentData($setsList,$checkedList);
                }
                if (isset($params['removeperq'])) {
                    Questions::updateQuestionData($checkedList);
                }
                if (isset($params['chgendmsg'])) {
                    if(strlen($checkedList) > AppConstant::NUMERIC_THREE){
                        return $this->redirect('assessment-message?cid='.$courseId.'&checked='.$checkedList);
                    }else{
                        return $this->redirect('assessment-message?cid='.$courseId.'&aid='.$checkedList);
                    }
                } else {
                    $this->setWarningFlash(AppConstant::CHANGE_ASSESSMENT_SUCCESSFULLY);
                    return $this->redirect(AppUtility::getHomeURL().'instructor/instructor/index?cid='.$courseId);
                }
            } else {
                $this->setWarningFlash(AppConstant::NO_ASSESSMENT);
                return $this->redirect('change-assessment?cid=' . $courseId);
            }
        }
        //DATA MANIPULATION FOR INITIAL LOAD
        $line['displaymethod']= isset($CFG['AMS']['displaymethod'])?$CFG['AMS']['displaymethod']:"SkipAround";
        $line['defpoints'] = isset($CFG['AMS']['defpoints'])?$CFG['AMS']['defpoints']:AppConstant::NUMERIC_TEN;
        $line['defattempts'] = isset($CFG['AMS']['defattempts'])?$CFG['AMS']['defattempts']:AppConstant::NUMERIC_ONE;
        $testType = isset($CFG['AMS']['testtype'])?$CFG['AMS']['testtype']:"AsGo";
        $showAns = isset($CFG['AMS']['showans'])?$CFG['AMS']['showans']:"A";
        $line['defpenalty'] = isset($CFG['AMS']['defpenalty'])?$CFG['AMS']['defpenalty']:AppConstant::NUMERIC_TEN;
        $line['shuffle'] = isset($CFG['AMS']['shuffle'])?$CFG['AMS']['shuffle']:AppConstant::NUMERIC_ZERO;
        $line['minscore'] = isset($CFG['AMS']['minscore'])?$CFG['AMS']['minscore']:AppConstant::NUMERIC_ZERO;
        $line['showhints']=isset($CFG['AMS']['showhints'])?$CFG['AMS']['showhints']:AppConstant::NUMERIC_ONE;
        $line['noprint'] = isset($CFG['AMS']['noprint'])?$CFG['AMS']['noprint']:AppConstant::NUMERIC_ZERO;
        $line['groupmax'] = isset($CFG['AMS']['groupmax'])?$CFG['AMS']['groupmax']:AppConstant::NUMERIC_SIX;
        $line['allowlate'] = isset($CFG['AMS']['allowlate'])?$CFG['AMS']['allowlate']:AppConstant::NUMERIC_ONE;
        $line['exceptionpenalty'] = isset($CFG['AMS']['exceptionpenalty'])?$CFG['AMS']['exceptionpenalty']:AppConstant::NUMERIC_ZERO;
        $line['tutoredit'] = isset($CFG['AMS']['tutoredit'])?$CFG['AMS']['tutoredit']:AppConstant::NUMERIC_ZERO;
        $line['eqnhelper'] = isset($CFG['AMS']['eqnhelper'])?$CFG['AMS']['eqnhelper']:AppConstant::NUMERIC_ZERO;
        $line['caltag'] = isset($CFG['AMS']['caltag'])?$CFG['AMS']['caltag']:'?';
        $line['calrtag'] = isset($CFG['AMS']['calrtag'])?$CFG['AMS']['calrtag']:'R';
        $line['showtips'] = isset($CFG['AMS']['showtips'])?$CFG['AMS']['showtips']:AppConstant::NUMERIC_ONE;
        if ($line['defpenalty']{0}==='L') {
            $line['defpenalty'] = substr($line['defpenalty'],AppConstant::NUMERIC_ONE);
            $skipPenalty = AppConstant::NUMERIC_TEN;
        } else if ($line['defpenalty']{0}==='S') {
            $skipPenalty = $line['defpenalty']{1};
            $line['defpenalty'] = substr($line['defpenalty'],2);
        } else {
            $skipPenalty = AppConstant::NUMERIC_ZERO;
        }
        $items = unserialize($course['itemorder']);
        global $parents,$sums,$names,$types,$gitypeids,$ids,$prespace;
        $agbCats = array();
        CopyItemsUtility::getsubinfo($items,'0','','Assessment','&nbsp;&nbsp;');
        $assessments = Assessments::getByCourseId($courseId); //retrieved all assessment from course
        if (count($assessments) == AppConstant::NUMERIC_ZERO) {
            $pageAssessListMsg = AppConstant::NO_ASSESSMENT_TO_CHANGE;
        } else {
            $pageAssessListMsg = "";
            $i=AppConstant::NUMERIC_ZERO;
            $pageAssessmentSelect = array();
            foreach($assessments as $assessment){
                $pageAssessmentSelect['val'][$i] = $assessment['id'];
                $pageAssessmentSelect['label'][$i] = $assessment['name'];
                $agbCats[$assessment['id']] = $assessment['gbcategory'];
                $i++;
            }
        }
        $pageForumSelect = array();
        $forums = Forums::getByCourseId($courseId);//retrieved all forum from course
        $pageForumSelect['val'][0] = AppConstant::NUMERIC_ZERO;
        $pageForumSelect['label'][0] = AppConstant::NONE;
        foreach($forums as $forum){
            $pageForumSelect['val'][] = $forum['id'];
            $pageForumSelect['label'][] = $forum['name'];
        }
        $pageAllowLateSelect = array();
        $pageAllowLateSelect['val'][0] = AppConstant::NUMERIC_ZERO;
        $pageAllowLateSelect['label'][0] = AppConstant::NONE;
        $pageAllowLateSelect['val'][1] = AppConstant::NUMERIC_ONE;
        $pageAllowLateSelect['label'][1] = AppConstant::UNLIMITED;
        for ($k= AppConstant::NUMERIC_ONE;$k< AppConstant::NUMERIC_NINE;$k++) {
            $pageAllowLateSelect['val'][] = $k+AppConstant::NUMERIC_ONE;
            $pageAllowLateSelect['label'][] = "Up to $k";
        }
        $this->includeCSS(['assessment.css']);
        $this->includeJS(["general.js","assessment/changeAssessment.js"]);
        $responseData = array('ids' => $ids,'testtype' => $testType,'showans' => $showAns,'skippenalty' => $skipPenalty,'page_assessListMsg' => $pageAssessListMsg,
            'page_allowlateSelect' => $pageAllowLateSelect,'page_forumSelect' => $pageForumSelect,'agbcats' => $agbCats,'page_assessSelect' => $pageAssessmentSelect,
            'gbcatsLabel' => $gbCatsLabel,'gbcatsId' => $gbCatsId,'overWriteBody' => $overWriteBody,'body' => $body,'isTeacher' => $isTeacher,'course' => $course,
            'parents' => $parents,'line' => $line,'sums' => $sums,'names' => $names,'types' => $types,'gitypeids' => $gitypeids,'prespace' => $prespace);
        return $this->renderWithData('changeAssessment',$responseData);
    }

    public function actionAssessmentMessage() {
        $params = $this->getRequestParams();
        $user = $this->getAuthenticatedUser();
        $courseId = $params['cid'];
        $this->layout = 'master';
        $course = Course::getById($courseId);
        $isTeacherId = $this->isTeacher($user['id'],$courseId);
        if (!(isset($isTeacherId))) {
            echo AppConstant::NO_TEACHER_RIGHTS;
            exit;
        }
        if (isset($params['record'])) {
            $endMessage = array();
            $endMessage['type'] = $params['type'];
            $endMessage['def'] = stripslashes($params['msg'][0]);
            $i= AppConstant::NUMERIC_ONE;
            $messageArray = array();
            while (isset($params['sc'][$i]) && !empty($params['sc'][$i]) ) {
                $key = (int)$params['sc'][$i];
                if ($key>AppConstant::NUMERIC_ZERO) {
                    $messageArray[$key] = stripslashes($params['msg'][$i]);
                }
                $i++;
            }
            krsort($messageArray);
            $endMessage['msgs'] = $messageArray;
            $endMessage['commonmsg'] = $params['commonmsg'];
            $messageString = serialize($endMessage);
            if (isset($params['aid'])) {
                Assessments::setEndMessage($params['aid'],$messageString);
            } else if (isset($params['aidlist'])) {
                $sets = "endmsg='" .$messageString. "'";
                Assessments::updateAssessmentData($sets,$params['aidlist']);
            }
            return $this->redirect(AppUtility::getURLFromHome('instructor','instructor/index?cid='.$courseId));
        }
        if (!isset($params['checked'])) {
            $query = Assessments::getByAssessmentId($params['aid']);
            $endMessage = $query['endmsg'];
        } else {
            $endMessage = '';
            if (count($params['checked'])==AppConstant::NUMERIC_ZERO) {
                $this->setWarningFlash(AppConstant::NO_ASSESSEMENT_SELECTED);
                exit;
            }
        }
        if ($endMessage!='') {
            $endMessage = unserialize($endMessage);
        } else {
            $endMessage = array();
            $endMessage['def'] = '';
            $endMessage['type'] = AppConstant::NUMERIC_ZERO;
            $endMessage['msgs'] = array();
            $endMessage['commonmsg'] = '';
        }
        $this->includeCSS(['assessment.css']);
        $this->includeJS(['editor/tiny_mce.js','assessment/addAssessment.js','general.js']);
        $responseData = array('course' => $course,'params' => $params,'endmsg'=>$endMessage);
        return $this->renderWithData('assessmentMessage',$responseData);
    }

    public function actionShowLicense(){
        $this->getAuthenticatedUser();
        $this->layout = 'master';
        $params = $this->getRequestParams();
        if (empty($params['id'])) {
            $this->setErrorFlash("No IDs specified");
            return $this->redirect($this->previousPage());
        }

        $ids = explode('-',$params['id']);
        foreach ($ids as $k=>$id) {
            $ids[$k] = intval($id);
        }

        $this->includeCSS(['assessment.css']);
        $licenseData = QuestionSet::getLicenseData($ids);
        $renderData = array('licenseData' => $licenseData);
        return $this->renderWithData('showLicense', $renderData);
    }

    /**
     * @param $course
     * @param $assessmentData
     * @param $saveTitle
     * @param $pageCopyFromSelect
     * @param $timeLimit
     * @param $assessmentSessionData
     * @param $testType
     * @param $skipPenalty
     * @param $showAnswer
     * @param $startDate
     * @param $endDate
     * @param $pageForumSelect
     * @param $pageAllowLateSelect
     * @param $pageGradebookCategorySelect
     * @param $gradebookCategory
     * @param $countInGb
     * @param $pointCountInGb
     * @param $pageTutorSelect
     * @param $minScoreType
     * @param $useDefFeedback
     * @param $defFeedback
     * @param $pageGroupSets
     * @param $pageOutcomesList
     * @param $pageOutcomes
     * @param $showQuestionCategory
     * @param $sDate
     * @param $sTime
     * @param $eDate
     * @param $eTime
     * @param $reviewDate
     * @param $reviewTime
     * @param $title
     * @param $pageTitle
     * @param $block
     * @return string
     */
    public function addAssessmentRenderData($course, $assessmentData, $saveTitle, $pageCopyFromSelect, $timeLimit, $assessmentSessionData, $testType, $skipPenalty, $showAnswer, $startDate, $endDate, $pageForumSelect, $pageAllowLateSelect, $pageGradebookCategorySelect, $gradebookCategory, $countInGb, $pointCountInGb, $pageTutorSelect, $minScoreType, $useDefFeedback, $defFeedback, $pageGroupSets, $pageOutcomesList, $pageOutcomes, $showQuestionCategory, $sDate, $sTime, $eDate, $eTime, $reviewDate, $reviewTime, $title, $pageTitle, $block, $body)
    {
        return $this->renderWithData('addAssessment', ['course' => $course, 'assessmentData' => $assessmentData,
            'saveTitle' => $saveTitle, 'pageCopyFromSelect' => $pageCopyFromSelect, 'timeLimit' => $timeLimit,
            'assessmentSessionData' => $assessmentSessionData, 'testType' => $testType, 'skipPenalty' => $skipPenalty,
            'showAnswer' => $showAnswer, 'startDate' => $startDate, 'endDate' => $endDate, 'pageForumSelect' => $pageForumSelect,
            'pageAllowLateSelect' => $pageAllowLateSelect, 'pageGradebookCategorySelect' => $pageGradebookCategorySelect,
            'gradebookCategory' => $gradebookCategory, 'countInGradebook' => $countInGb, 'pointCountInGradebook' => $pointCountInGb,
            'pageTutorSelect' => $pageTutorSelect, 'minScoreType' => $minScoreType, 'useDefFeedback' => $useDefFeedback,
            'defFeedback' => $defFeedback, 'pageGroupSets' => $pageGroupSets, 'pageOutcomesList' => $pageOutcomesList,
            'pageOutcomes' => $pageOutcomes, 'showQuestionCategory' => $showQuestionCategory, 'sDate' => $sDate,'body'=>$body,
            'sTime' => $sTime, 'eDate' => $eDate, 'eTime' => $eTime, 'reviewDate' => $reviewDate, 'reviewTime' => $reviewTime,
            'startDate' => $startDate, 'endDate' => $endDate, 'title' => $title, 'pageTitle' => $pageTitle, 'block' => $block]);
    }

    /**
     * @param $params
     * @return array
     */
    public function blockFilter($params)
    {
        if (isset($params['from'])) {
            $from = $params['from'];
        } else {
            $from = 'cp';
        }
        if (isset($params['tb'])) {
            $filter = $params['tb'];
            return array($params, $from, $filter);
        } else {
            $filter = 'b';
            return array($params, $from, $filter);
        }
    }
}