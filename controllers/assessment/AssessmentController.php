<?php

namespace app\controllers\assessment;

use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CategoryScoresUtility;
use app\components\CopyItemsUtility;
use app\components\filehandler;
use app\controllers\AppController;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\ContentTrack;
use app\models\Course;
use app\models\Exceptions;
use app\models\Forums;
use app\models\GbCats;
use app\models\Items;
use app\models\LinkedText;
use app\models\Log;
use app\models\LoginLog;
use app\models\Message;
use app\models\Outcomes;
use app\models\Questions;
use app\models\QuestionSet;
use app\models\SetPassword;
use app\models\Student;
use app\models\StuGroupMembers;
use app\models\Stugroups;
use app\models\StuGroupSet;
use app\models\Teacher;
use app\models\User;
use Yii;
use app\components\AppConstant;

include("../components/handled.php");
class AssessmentController extends AppController
{
    public function beforeAction($action)
    {
        $actionPath = Yii::$app->controller->action->id;
        $user = $this->getAuthenticatedUser();
        $courseId =  ($this->getRequestParams('cid') || $this->getRequestParams('courseId')) ? ($this->getRequestParams('cid')?$this->getRequestParams('cid'):$this->getRequestParams('courseId') ): AppUtility::getDataFromSession('courseId');
        return $this->accessForAssessmentController($user,$courseId,$actionPath);
    }
    /*
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
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $params = $this->getRequestParams();
        $courseId = $this->getParamVal('cid');
        $teacherId = $this->isTeacher($user['id'], $courseId);
        $this->noValidRights($teacherId);
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
                        $startDate = AppUtility::parsedatetime($params['sdate'], $params['stime']);
                    }
                    if ($params['edatetype'] == AppConstant::ALWAYS_TIME) {
                        $endDate = AppConstant::ALWAYS_TIME;
                    } else {
                        $endDate = AppUtility::parsedatetime($params['edate'], $params['etime']);
                    }
                    if ($params['doreview'] == AppConstant::NUMERIC_ZERO) {
                        $reviewDate = AppConstant::NUMERIC_ZERO;
                    } else if ($params['doreview'] == AppConstant::ALWAYS_TIME) {
                        $reviewDate = AppConstant::ALWAYS_TIME;
                    } else {
                        $reviewDate = AppUtility::parsedatetime($params['rdate'], $params['rtime']);
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
                $assessmentArray['courseid'] = intval($params['cid']);
                $assessmentArray['name'] = strval($params['name']);
                $assessmentArray['summary'] = strval($params['summary']);
                $assessmentArray['intro'] = strval($params['intro']);
                $assessmentArray['avail'] = intval($params['avail']);
                $assessmentArray['password'] = strval($params['assmpassword']);
                $assessmentArray['displaymethod'] = strval($params['displaymethod']);
                $assessmentArray['defpoints'] = intval($params['defpoints']);
                $assessmentArray['defattempts'] = intval($params['defattempts']);
                $assessmentArray['eqnhelper'] = intval($params['eqnhelper']);
                $assessmentArray['msgtoinstr'] = intval($params['msgtoinstr']);
                $assessmentArray['posttoforum'] = intval($params['posttoforum']);
                $assessmentArray['showtips'] = intval($params['showtips']);
                $assessmentArray['allowlate'] = intval($params['allowlate']);
                $assessmentArray['noprint'] = intval($params['noprint']);
                $assessmentArray['gbcategory'] = intval($params['gbcat']);
                $assessmentArray['cntingb'] = intval($params['cntingb']);
                $assessmentArray['minscore'] = intval($params['minscore']);
                $assessmentArray['reqscore'] = intval($params['reqscore']);
                $assessmentArray['reqscoreaid'] = intval($params['reqscoreaid']);
                $assessmentArray['exceptionpenalty'] = intval($params['exceptionpenalty']);
                $assessmentArray['groupmax'] = intval($params['groupmax']);
                $assessmentArray['groupsetid'] = intval($params['groupsetid']);
                $assessmentArray['defoutcome'] = intval($params['defoutcome']);
                $assessmentArray['showcat'] = intval($params['showqcat']);
                $assessmentArray['ltisecret'] = strval($params['ltisecret']);
                $assessmentArray['defpenalty'] = intval($params['defpenalty']);
                $assessmentArray['startdate'] = intval($startDate);
                $assessmentArray['enddate'] = intval($endDate);
                $assessmentArray['reviewdate'] = intval($reviewDate);
                $assessmentArray['timelimit'] = intval($timeLimit);
                $assessmentArray['shuffle'] = intval($shuffle);
                $assessmentArray['deffeedback'] = strval($defFeedback);
                $assessmentArray['tutoredit'] = intval($tutorEdit);
                $assessmentArray['showhints'] = intval($showHints);
                $assessmentArray['endmsg'] = strval($endMsg);
                $assessmentArray['deffeedbacktext'] = strval($defFeedbackText);
                $assessmentArray['istutorial'] = intval($isTutorial);
                $assessmentArray['isgroup'] = intval($isGroup);
                $assessmentArray['caltag'] = strval($calTag);
                $assessmentArray['calrtag'] = strval($calrTag);
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
                    $assessmentArray['id'] = intval($params['id']);
                    Assessments::updateAssessment($assessmentArray);
                    if ($from == 'gb') {
                        return $this->redirect(AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $courseId));
                    } else if ($from == 'mcd') {
                        return $this->redirect(AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $courseId));
                    } else if ($from == 'lti') {
                        return $this->redirect(AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $courseId));
                    } else {
                        return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' . $courseId));
                    }
                } else { //add new
                    $assessment = new Assessments();
                    $newAssessmentId = $assessment->createAssessment($assessmentArray);
                    $itemAssessment = new Items();
                    $itemId = $itemAssessment->saveItems($courseId, $newAssessmentId, 'Assessment');
                    $courseItemOrder = Course::getItemOrder($courseId);
                    $itemOrder = $courseItemOrder['itemorder'];
                    $items = unserialize($courseItemOrder['itemorder']);
                    $blockTree = explode('-', $block);
                    $sub =& $items;
                    for ($i = AppConstant::NUMERIC_ONE; $i < count($blockTree); $i++) {
                        $sub =& $sub[$blockTree[$i] - AppConstant::NUMERIC_ONE]['items']; //-1 to adjust for 1-indexing
                    }
                    if ($filter=='b') {
                        $sub[] = intval($itemId);
                    } else if ($filter=='t') {
                        array_unshift($sub, ($itemId));
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
                    $assessmentData['displaymethod'] = isset($CFG['AMS']['displaymethod'])?$CFG['AMS']['displaymethod']:"SkipAround";
                    $assessmentData['defpoints'] = isset($CFG['AMS']['defpoints'])?$CFG['AMS']['defpoints']: AppConstant::NUMERIC_TEN;
                    $assessmentData['defattempts'] = isset($CFG['AMS']['defattempts'])?$CFG['AMS']['defattempts']: AppConstant::NUMERIC_ONE;
                    $assessmentData['password'] = '';
                    $testType = isset($CFG['AMS']['testtype'])?$CFG['AMS']['testtype']: AppConstant::TEST_TYPE;
                    $showAnswer = isset($CFG['AMS']['showans'])?$CFG['AMS']['showans']: AppConstant::SHOW_ANSWER;
                    $assessmentData['defpenalty'] = isset($CFG['AMS']['defpenalty'])?$CFG['AMS']['defpenalty']: AppConstant::NUMERIC_TEN;
                    $assessmentData['shuffle'] = isset($CFG['AMS']['shuffle'])?$CFG['AMS']['shuffle']: AppConstant::NUMERIC_ZERO;
                    $assessmentData['minscore'] = isset($CFG['AMS']['minscore'])?$CFG['AMS']['minscore']: AppConstant::NUMERIC_ZERO;
                    $assessmentData['isgroup'] = isset($CFG['AMS']['isgroup'])?$CFG['AMS']['isgroup']: AppConstant::NUMERIC_ZERO;
                    $assessmentData['showhints'] = isset($CFG['AMS']['showhints'])?$CFG['AMS']['showhints']: AppConstant::NUMERIC_ONE;
                    $assessmentData['reqscore'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['reqscoreaid'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['groupsetid'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['noprint'] = isset($CFG['AMS']['noprint'])?$CFG['AMS']['noprint']: AppConstant::NUMERIC_ZERO;
                    $assessmentData['groupmax'] = isset($CFG['AMS']['groupmax'])?$CFG['AMS']['groupmax']: AppConstant::NUMERIC_SIX;
                    $assessmentData['allowlate'] = isset($CFG['AMS']['allowlate'])?$CFG['AMS']['allowlate']: AppConstant::NUMERIC_ONE;
                    $assessmentData['exceptionpenalty'] = isset($CFG['AMS']['exceptionpenalty'])?$CFG['AMS']['exceptionpenalty']: AppConstant::NUMERIC_ZERO;
                    $assessmentData['tutoredit'] = isset($CFG['AMS']['tutoredit'])?$CFG['AMS']['tutoredit']: AppConstant::NUMERIC_ZERO;
                    $assessmentData['eqnhelper'] = isset($CFG['AMS']['eqnhelper'])?$CFG['AMS']['eqnhelper']: AppConstant::NUMERIC_ZERO;
                    $assessmentData['ltisecret'] = '';
                    $assessmentData['caltag'] = isset($CFG['AMS']['caltag'])?$CFG['AMS']['caltag']: AppConstant::CALTAG;
                    $assessmentData['calrtag'] = isset($CFG['AMS']['calrtag'])?$CFG['AMS']['calrtag']: AppConstant::CALRTAG;
                    $assessmentData['showtips'] = isset($CFG['AMS']['showtips'])?$CFG['AMS']['showtips']:AppConstant::NUMERIC_TWO;
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

                $page_formActionTag = "add-assessment?block=$block&cid=$courseId";
                if (isset($_GET['id'])) {
                    $page_formActionTag .= "&id=" . $_GET['id'];
                }
                $page_formActionTag .= "&folder=" . $_GET['folder'] . "&from=" . $_GET['from'];
                $page_formActionTag .= "&tb=$filter";

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
                $query = Outcomes::getByCourseId($courseId);
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
        $this->includeCSS(['course/items.css', 'course/course.css','gradebook.css','assessment.css']);
        $this->includeJS(["editor/tiny_mce.js","assessment/addAssessment.js", "general.js"]);
        return $this->addAssessmentRenderData($course, $assessmentData, $saveTitle, $pageCopyFromSelect, $timeLimit, $assessmentSessionData, $testType, $skipPenalty, $showAnswer, $startDate, $endDate, $pageForumSelect, $pageAllowLateSelect, $pageGradebookCategorySelect, $gradebookCategory, $countInGb, $pointCountInGb, $pageTutorSelect, $minScoreType, $useDefFeedback, $defFeedback, $pageGroupSets, $pageOutcomesList, $pageOutcomes, $showQuestionCategory, $sDate, $sTime, $eDate, $eTime, $reviewDate, $reviewTime, $title, $pageTitle, $block, $body,$page_formActionTag);
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
                    return $this->redirect(AppUtility::getHomeURL().'course/course/course?cid='.$courseId);
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
    public function addAssessmentRenderData($course, $assessmentData, $saveTitle, $pageCopyFromSelect, $timeLimit, $assessmentSessionData, $testType, $skipPenalty, $showAnswer, $startDate, $endDate, $pageForumSelect, $pageAllowLateSelect, $pageGradebookCategorySelect, $gradebookCategory, $countInGb, $pointCountInGb, $pageTutorSelect, $minScoreType, $useDefFeedback, $defFeedback, $pageGroupSets, $pageOutcomesList, $pageOutcomes, $showQuestionCategory, $sDate, $sTime, $eDate, $eTime, $reviewDate, $reviewTime, $title, $pageTitle, $block, $body,$page_formActionTag)
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
            'startDate' => $startDate, 'endDate' => $endDate, 'title' => $title, 'pageTitle' => $pageTitle, 'block' => $block,'page_formActionTag' => $page_formActionTag]);
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
    public function actionShowTest() {
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $params = $this->getRequestParams();
        $userid = $user['id'];
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $sessionId = $this->getSessionId();
        $teacherid = $this->isTeacher($user['id'], $courseId);
        $userfullname = $user['FirstName'].' '.$user['LastName'];
        global $temp, $CFG, $questions, $seeds,$showansduring, $testsettings,$qi,$rawscores,$timesontask,$isdiag, $courseId, $attempts,$scores,$bestscores,$noindivscores,$showeachscore,$reattempting,$bestrawscores,$firstrawscores,$bestattempts,$bestseeds,$bestlastanswers,$lastanswers,$bestquestions;
        $myrights = $user['rights'];
        $sessiondata = $this->getSessionData($sessionId);;
        if (!isset($CFG['TE']['navicons'])) {
            $CFG['TE']['navicons'] = array(
                'untried'=>'te_blue_arrow.png',
                'canretrywrong'=>'te_red_redo.png',
                'canretrypartial'=>'te_yellow_redo.png',
                'noretry'=>'te_blank.gif',
                'correct'=>'te_green_check.png',
                'wrong'=>'te_red_ex.png',
                'partial'=>'te_yellow_check.png');
        }
        if (isset($guestid)) {
            $teacherid=$guestid;
        }
        if (!isset($sessiondata['sessiontestid']) && !isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
            $this->setErrorFlash(AppConstant::TEST_PAGE_NO_ACCESS);
            return $this->redirect($this->previousPage());
        }
        $actas = false;
        $isreview = false;
        if (isset($teacherid) && isset($_GET['actas'])) {
            $userid = $_GET['actas'];
            unset($teacherid);
            $actas = true;
        }

        include("../components/displayQuestion.php");
        include("../components/testutil.php");
        include("../components/asidutil.php");
        $inexception = false;
        $exceptionduedate = 0;
        /*
         * check to see if test starting test or returning to test
         */
        if (isset($_GET['id'])) {
            /*
             * check dates, determine if review
             */
            $aid = $_GET['id'];
            $isreview = false;

            $adata = Assessments::getAssessmentData($aid);
            $now = time();
            $assessmentclosed = false;

            if ($adata['avail']==0 && !isset($teacherid) && !isset($tutorid)) {
                $assessmentclosed = true;
            }

            if (!$actas) {
                if (isset($studentid)) {
                    $contentArray['userid'] = $userid;
                    $contentArray['courseid'] = $courseId;
                    $contentArray['type'] = 'assess';
                    $contentArray['typeid'] = $aid;
                    $contentArray['viewtime'] = $now;
                    $content = new ContentTrack();
                    $content->createTrack($contentArray);
                }
                $row = Exceptions::getStartDateEndDate($userid, $aid);
                if ($row!=null) {
                    if ($now<$row['startdate'] || $row['enddate']<$now) { //outside exception dates
                        if ($now > $adata['startdate'] && $now<$adata['reviewdate']) {
                            $isreview = true;
                        } else {
                            if (!isset($teacherid) && !isset($tutorid)) {
                                $assessmentclosed = true;
                            }
                        }
                    } else { //inside exception dates exception
                        if ($adata['enddate']<$now) { //exception is for past-due-date
                            $inexception = true; //only trigger if past due date for penalty
                        }
                    }
                    $exceptionduedate = $row['enddate'];
                } else { //has no exception
                    if ($now < $adata['startdate'] || $adata['enddate']<$now) { //outside normal dates
                        if ($now > $adata['startdate'] && $now<$adata['reviewdate']) {
                            $isreview = true;
                        } else {
                            if (!isset($teacherid) && !isset($tutorid)) {
                                $assessmentclosed = true;
                            }
                        }
                    }
                }
            }
            if ($assessmentclosed) {
                $temp .= '<p>'.'This assessment is closed'. '</p>';
                if ($adata['avail']>0) {
                    $viewedassess = array();
                    if ($adata['enddate']<$now && $adata['allowlate']>10 && !$actas && !isset($sessiondata['stuview'])) {
                        $query = ContentTrack::getTypeId($courseId, $userid,'gbviewasid');
                        foreach ($query as $r) {
                            $viewedassess[] = $r['typeid'];
                        }
                    }

                    if (!isset($teacherid) && !isset($tutorid) && !$actas && !isset($sessiondata['stuview'])) {
                        $latepasshrs = Course::getLatePassHrs($courseId);

                        $latepasses = Student::getLatePass($userid, $courseId);
                    } else {
                        $latepasses = 0;
                        $latepasshrs = 0;
                    }
                    if ($adata['allowlate']>10 && ($now - $adata['enddate'])<$latepasshrs*3600 && !in_array($aid,$viewedassess) && $latepasses>0 && !isset($sessiondata['stuview']) && !$actas ) {
                        $temp .= "<p><a href=".AppUtility::getURLFromHome('assessment','assessment/late-pass?cid='.$courseId.'&aid='.$aid).">".'Use LatePass'. "</a></p>";
                    }

                    if (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0 && $sessiondata['ltiitemid']==$aid) {
                        /*
                         * in LTI and right item
                         */
                        list($atype,$sa) = explode('-',$adata['deffeedback']);
                        if ($sa!='N') {
                            $query = AssessmentSession::getIdByUserIdAndAid($userid, $aid);
                            if (count($query)>0) {
                                $temp .= '<p><a href="'.AppUtility::getURLFromHome('gradebook','gradbook/view-assessment-details?cid='.$courseId.'&asid='.$query['id']).'">'.'View your assessment'. '</p>';
                            }
                        }
                    }
                }
                return $temp;
            }
            /*
             * check for password
             */
            $pwfail = false;
            if (trim($adata['password'])!='' && !isset($teacherid) && !isset($tutorid)) { //has passwd
                $pwfail = true;
                if (isset($_POST['password'])) {
                    if (trim($_POST['password'])==trim($adata['password'])) {
                        $pwfail = false;
                    } else {
                        $out = '<p>' . _('Password incorrect.  Try again.') . '<p>';
                    }
                }
                if ($pwfail) {
                    $temp .= $out;
                    $temp .=  '<h2>'.$adata['name'].'</h2>';
                    $temp .= '<p>'. "Password required for access".'</p>';
                    $temp .= "<form method=\"post\" enctype=\"multipart/form-data\" action=\"show-test?cid={$_GET['cid']}&amp;id={$_GET['id']}\">";
                    $temp .= "<p>Password: <input type=\"password\" name=\"password\" autocomplete=\"off\" /></p>";
                    $temp .= '<input type=submit value="'.'Submit'. '" />';
                    $temp .= "</form>";
                    return $temp;
                }
            }
            /*
             * get latepass info
             */
            if (!isset($teacherid) && !isset($tutorid) && !$actas && !isset($sessiondata['stuview'])) {
                $query = Student::getLatePass($userid, $adata['courseid']);
                $sessiondata['latepasses'] = $query['latepass'];
            } else {
                $sessiondata['latepasses'] = 0;
            }

            $sessiondata['istutorial'] = $adata['istutorial'];
            $_SESSION['choicemap'] = array();

            $line = AssessmentSession::getAssessmentSessionData($userid, $_GET['id']);

            if ($line == null) {
                /*
                 * starting test and get question set
                 */
                if (trim($adata['itemorder'])=='') {
                    $this->setErrorFlash(AppConstant::NO_QUESTIONS);
                    return $this->redirect($this->previousPage());
                }
                list($qlist,$seedlist,$reviewseedlist,$scorelist,$attemptslist,$lalist) = generateAssessmentData($adata['itemorder'],$adata['shuffle'],$aid);

                if ($qlist=='') {  //assessment has no questions!
                    $this->setErrorFlash(AppConstant::NO_QUESTIONS);
                    return $this->redirect($this->previousPage());
                }

                $bestscorelist = $scorelist.';'.$scorelist.';'.$scorelist;  //bestscores;bestrawscores;firstscores
                $scorelist = $scorelist.';'.$scorelist;  //scores;rawscores  - also used as reviewscores;rawreviewscores
                $bestattemptslist = $attemptslist;
                $bestseedslist = $seedlist;
                $bestlalist = $lalist;

                $starttime = time();

                $stugroupid = 0;
                if ($adata['isgroup']>0 && !$isreview && !isset($teacherid) && !isset($tutorid)) {
                    $query = Stugroups::getStuGrpId($userid, $adata['groupsetid']);
                    if (count($query)>0) {
                        $stugroupid = $query['id'];
                        $sessiondata['groupid'] = $stugroupid;
                    } else {
                        if ($adata['isgroup']==3) {
                            $temp .= AppConstant::NOT_GROUP_MEMBER;
                            $temp .= "<a href=".AppUtility::getURLFromHome('instructor','instructor/index?cid='.$_GET['cid']).">Back</a>";
                            return $temp;
                        }
                        $stuGrp = new Stugroups();
                        $stugroupid = $stuGrp->insertStuGrpData('Unnamed group', $adata['groupsetid']);
                        $sessiondata['groupid'] = 0;  //leave as 0 to trigger adding group members
                        $stuGrpMember = new StuGroupMembers();
                        $stuGrpMember->insertStuGrpMemberData($userid, $stugroupid);
                    }
                }
                $deffeedbacktext = addslashes($adata['deffeedbacktext']);
                if (isset($sessiondata['lti_lis_result_sourcedid']) && strlen($sessiondata['lti_lis_result_sourcedid'])>1) {
                    $ltisourcedid = addslashes(stripslashes($sessiondata['lti_lis_result_sourcedid'].':|:'.$sessiondata['lti_outcomeurl'].':|:'.$sessiondata['lti_origkey'].':|:'.$sessiondata['lti_keylookup']));
                } else {
                    $ltisourcedid = '';
                }
                $assessmentSessionData['userid'] = $userid;
                $assessmentSessionData['assessmentid'] = $_GET['id'];
                $assessmentSessionData['questions'] = $qlist;
                $assessmentSessionData['seeds'] = $seedlist;
                $assessmentSessionData['scores'] = $scorelist;
                $assessmentSessionData['attempts'] = $attemptslist;
                $assessmentSessionData['lastanswers'] = $lalist;
                $assessmentSessionData['starttime'] = $starttime;
                $assessmentSessionData['bestscores'] = $bestscorelist;
                $assessmentSessionData['bestattempts'] = $bestattemptslist;
                $assessmentSessionData['bestseeds'] = $bestseedslist;
                $assessmentSessionData['bestlastanswers'] = $bestlalist;
                $assessmentSessionData['reviewscores'] = $scorelist;
                $assessmentSessionData['reviewattempts'] = $attemptslist;
                $assessmentSessionData['reviewseeds'] = $reviewseedlist;
                $assessmentSessionData['reviewlastanswers'] = $lalist;
                $assessmentSessionData['agroupid'] = $stugroupid;
                $assessmentSessionData['feedback'] = $deffeedbacktext;
                $assessmentSessionData['lti_sourcedid'] = $ltisourcedid;
                $assessmentSession = new AssessmentSession();
                $asId = $assessmentSession->createSessionForAssessment($assessmentSessionData);
                if ($asId===false) {
                    $temp .= 'Error DupASID. <a href="show-test?cid='.$courseId.'&aid='.$aid.'">Try again</a>';
                }
                $sessiondata['sessiontestid'] = $asId;

                if ($stugroupid==0) {
                    $sessiondata['groupid'] = 0;
                } else {
                    /*
                     * if a group assessment and already in a group, we'll create asids for all the group members now
                     */
                    $query = StuGroupMembers::getUserId($stugroupid, $userid);
                    foreach($query as $uid){

                        $assessmentSessionData['userid'] = $uid;
                        $assessmentSessionData['lti_sourcedid'] = '';
                        $assessmentSession = new AssessmentSession();
                        $assessmentSession->createSessionForAssessment($assessmentSessionData);
                    }
                }
                $sessiondata['isreview'] = $isreview;
                if (isset($teacherid) || isset($tutorid) || $actas) {
                    $sessiondata['isteacher']=true;
                } else {
                    $sessiondata['isteacher']=false;
                }
                if ($actas) {
                    $sessiondata['actas']=$_GET['actas'];
                    $sessiondata['isreview'] = false;
                } else {
                    unset($sessiondata['actas']);
                }
                if (strpos($_SERVER['HTTP_REFERER'],'treereader')!==false) {
                    $sessiondata['intreereader'] = true;
                } else {
                    $sessiondata['intreereader'] = false;
                }

                $sessiondata['courseid'] = intval($_GET['cid']);
                $sessiondata['coursename'] = $course['name'];
                $sessiondata['coursetheme'] = $course['theme'];
                $sessiondata['coursetopbar'] =  $course['topbar'];
                $sessiondata['msgqtoinstr'] = (floor($course['msgset'] / 5))&2;
                $sessiondata['coursetoolset'] = $course['toolset'];
                if (isset($studentinfo['timelimitmult'])) {
                    $sessiondata['timelimitmult'] = $studentinfo['timelimitmult'];
                } else {
                    $sessiondata['timelimitmult'] = 1.0;
                }

                $this->writesessiondata($sessiondata, $sessionId);
                session_write_close();
                return $this->redirect(AppUtility::getURLFromHome('assessment','assessment/show-test'));
            } else { //returning to test

                $deffeedback = explode('-',$adata['deffeedback']);
                if ($myrights<6 || isset($teacherid) || isset($tutorid)) {  // is teacher or guest - delete out out assessment session
                    filehandler::deleteasidfilesbyquery2('userid',$userid,$aid,1);
                    AssessmentSession::deleteData($userid, $aid);
                    return $this->redirect(AppUtility::getURLFromHome('assessment','assessment/show-test?cid='.$_GET['cid'].'&id='.$aid));
                }
                //Return to test.
                $sessiondata['sessiontestid'] = $line['id'];
                $sessiondata['isreview'] = $isreview;
                if (isset($teacherid) || isset($tutorid) || $actas) {
                    $sessiondata['isteacher']=true;
                } else {
                    $sessiondata['isteacher']=false;
                }
                if ($actas) {
                    $sessiondata['actas']=$_GET['actas'];
                    $sessiondata['isreview'] = false;
                } else {
                    unset($sessiondata['actas']);
                }

                if ($adata['isgroup']==0 || $line['agroupid']>0) {
                    $sessiondata['groupid'] = $line['agroupid'];
                } else if (!isset($teacherid) && !isset($tutorid)) { //isgroup>0 && agroupid==0
                    //already has asid, but broken from group
                    $stuGrp = new Stugroups();
                    $stugroupid = $stuGrp->insertStuGrpData('Unnamed group', $adata['groupsetid']);
                    if ($adata['isgroup']==3) {
                        $sessiondata['groupid'] = $stugroupid;
                    } else {
                        $sessiondata['groupid'] = 0;  //leave as 0 to trigger adding group members
                    }

                    $stuGrpMember = new StuGroupMembers();
                    $stuGrpMember->insertStuGrpMemberData($userid, $stugroupid);

                    AssessmentSession::setGroupIdById($stugroupid,$line['id']);
                }

                $sessiondata['courseid'] = intval($_GET['cid']);
                $sessiondata['coursename'] = $course['name'];
                $sessiondata['coursetheme'] = $course['theme'];
                $sessiondata['coursetopbar'] =  $course['topbar'];
                $sessiondata['msgqtoinstr'] = (floor($course['msgset'] / 5))&2;
                $sessiondata['coursetoolset'] = $course['toolset'];

                if (isset($studentinfo['timelimitmult'])) {
                    $sessiondata['timelimitmult'] = $studentinfo['timelimitmult'];
                } else {
                    $sessiondata['timelimitmult'] = 1.0;
                }

                if (isset($sessiondata['lti_lis_result_sourcedid'])) {
                    $altltisourcedid = stripslashes($sessiondata['lti_lis_result_sourcedid'].':|:'.$sessiondata['lti_outcomeurl'].':|:'.$sessiondata['lti_origkey'].':|:'.$sessiondata['lti_keylookup']);
                    if ($altltisourcedid != $line['lti_sourcedid']) {
                        $altltisourcedid = addslashes($altltisourcedid);
                        AssessmentSession::setLtiSourceId($altltisourcedid, $line['id']);
                    }
                }
                $this->writesessiondata($sessiondata,$sessionId);
                session_write_close();
                return $this->redirect(AppUtility::getURLFromHome('assessment','assessment/show-test'));
            }
        }
        //already started test
        if (!isset($sessiondata['sessiontestid'])) {
            $temp  .= 'Error.  Access test from course page';
            return $temp;
        }
        $testid = addslashes($sessiondata['sessiontestid']);
        $asid = $testid;
        $isteacher = $sessiondata['isteacher'];
        if (isset($sessiondata['actas'])) {
            $userid = $sessiondata['actas'];
        }
        $line = AssessmentSession::getById($testid);
//        AppUtility::dump('ques');
        if (strpos($line['questions'],';')===false) {
            $questions = explode(",",$line['questions']);
            $bestquestions = $questions;
        } else {
            list($questions,$bestquestions) = explode(";",$line['questions']);
            $questions = explode(",",$questions);
            $bestquestions = explode(",",$bestquestions);
        }
        $seeds = explode(",",$line['seeds']);
        if (strpos($line['scores'],';')===false) {
            $scores = explode(",",$line['scores']);
            $noraw = true;
            $rawscores = $scores;
        } else {
            $sp = explode(';',$line['scores']);
            $scores = explode(',', $sp[0]);
            $rawscores = explode(',', $sp[1]);
            $noraw = false;
        }

        $attempts = explode(",",$line['attempts']);
        $lastanswers = explode("~",$line['lastanswers']);
        if ($line['timeontask']=='') {
            $timesontask = array_fill(0,count($questions),'');
        } else {
            $timesontask = explode(',',$line['timeontask']);
        }
        $lti_sourcedid = $line['lti_sourcedid'];
        if (trim($line['reattempting'])=='') {
            $reattempting = array();
        } else {
            $reattempting = explode(",",$line['reattempting']);
        }
        $bestseeds = explode(",",$line['bestseeds']);
        if ($noraw) {
            $bestscores = explode(',',$line['bestscores']);
            $bestrawscores = $bestscores;
            $firstrawscores = $bestscores;
        } else {
            $sp = explode(';',$line['bestscores']);
            $bestscores = explode(',', $sp[0]);
            $bestrawscores = explode(',', $sp[1]);
            $firstrawscores = explode(',', $sp[2]);
        }
        $bestattempts = explode(",",$line['bestattempts']);
        $bestlastanswers = explode("~",$line['bestlastanswers']);
        $starttime = $line['starttime'];

        if ($starttime == 0) {
            $starttime = time();
            AssessmentSession::setStartTime($starttime, $testid);
        }

        $testsettings = Assessments::getByAssessmentIdAsArray($line['assessmentid']);
        if ($testsettings['displaymethod']=='VideoCue' && $testsettings['viddata']=='') {
            $testsettings['displaymethod']= 'Embed';
        }
        if (preg_match('/ImportFrom:\s*([a-zA-Z]+)(\d+)/',$testsettings['intro'],$matches)==1) {
            if (strtolower($matches[1])=='link') {
                $vals = LinkedText::getText(intval($matches[2]));
                $testsettings['intro'] = str_replace($matches[0], $vals['text'], $testsettings['intro']);
            } else if (strtolower($matches[1])=='assessment') {
                $vals = Assessments::getAssessmentIntro(intval($matches[2]));
                $testsettings['intro'] = str_replace($matches[0], $vals['intro'], $testsettings['intro']);
            }
        }
        if (!$isteacher) {
            $rec = "data-base=\"assessintro-{$line['assessmentid']}\" ";
            $testsettings['intro'] = str_replace('<a ','<a '.$rec, $testsettings['intro']);
        }
        $timelimitkickout = ($testsettings['timelimit']<0);
        $testsettings['timelimit'] = abs($testsettings['timelimit']);
        //do time limit mult
        $testsettings['timelimit'] *= $sessiondata['timelimitmult'];
        list($testsettings['testtype'],$showans) = explode('-',$testsettings['deffeedback']);

//        list($testsettings['testtype'],$showans) = explode('-',$testsettings['deffeedback']);

        //if submitting, verify it's the correct assessment
        if (isset($_POST['asidverify']) && $_POST['asidverify']!=$testid) {
            $temp = 'Error.  It appears you have opened another assessment since you opened this one. ';
            $temp .= 'Only one open assessment can be handled at a time. Please reopen the assessment and try again. ';
            $temp .= "<a href=".AppUtility::getURLFromHome('instructor','instructor/index?cid='.$testsettings['courseid']).">";
            $temp .= 'Return to course page'."</a>";
            return $temp;
        }
        //verify group is ok
        if ($testsettings['isgroup']>0 && !$isteacher &&  ($line['agroupid']==0 || ($sessiondata['groupid']>0 && $line['agroupid']!=$sessiondata['groupid']))) {
            $temp = 'Error.  Looks like your group has changed for this assessment. Please reopen the assessment and try again.';
            $temp .= "<a href=".AppUtility::getURLFromHome('instructor','instructor/index?cid='.$testsettings['courseid']).">";
            $temp .= 'Return to course page'."</a>";
            return $temp;
        }

        $now = time();
        //check for dates - kick out student if after due date
        if ($testsettings['avail']==0 && !$isteacher) {
            $temp  .= 'Assessment is closed';
            $this->leavetestmsg($sessiondata);
            return $temp;
        }
        if (!isset($sessiondata['actas'])) {
            $row = Exceptions::getStartDateEndDate($userid, $line['assessmentid']);
            if ($row!=null) {
                if ($now<$row['startdate'] || $row['enddate']<$now) { //outside exception dates
                    if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
                        $isreview = true;
                    } else {
                        if (!$isteacher) {
                            $temp  .= 'Assessment is closed';
                            $this->leavetestmsg($sessiondata);
                            return $temp;
                        }
                    }
                } else { //in exception
                    if ($testsettings['enddate']<$now) { //exception is for past-due-date
                        $inexception = true;
                    }
                }
                $exceptionduedate = $row['enddate'];
            } else { //has no exception
                if ($now < $testsettings['startdate'] || $testsettings['enddate'] < $now) {//outside normal dates
                    if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
                        $isreview = true;
                    } else {
                        if (!$isteacher) {
                            $temp  .= 'Assessment is closed';
                            $this->leavetestmsg($sessiondata);
                            return $temp;
                        }
                    }
                }
            }
        } else {
            $row = Exceptions::getStartDateEndDate($sessiondata['actas'], $line['assessmentid']);
            if ($row!=null) {
                $exceptionduedate = $row['enddate'];
            }
        }
        $superdone = false;
        if ($isreview) {
            if (isset($_POST['isreview']) && $_POST['isreview']==0) {
                $temp  .= _('Due date has passed.  Submission rejected. ');
                $this->leavetestmsg($sessiondata);
                return $temp;
            }
            $testsettings['testtype']="Practice";
            $testsettings['defattempts'] = 0;
            $testsettings['defpenalty'] = 0;
            $showans = '0';

            $seeds = explode(",",$line['reviewseeds']);

            if (strpos($line['reviewscores'],';')===false) {
                $reviewscores = explode(",",$line['reviewscores']);
                $noraw = true;
                $reviewrawscores = $reviewscores;
            } else {
                $sp = explode(';',$line['reviewscores']);
                $reviewscores = explode(',', $sp[0]);
                $reviewrawscores = explode(',', $sp[1]);
                $noraw = false;
            }

            $attempts = explode(",",$line['reviewattempts']);
            $lastanswers = explode("~",$line['reviewlastanswers']);
            if (trim($line['reviewreattempting'])=='') {
                $reattempting = array();
            } else {
                $reattempting = explode(",",$line['reviewreattempting']);
            }
        } else if ($timelimitkickout) {
            $now = time();
            $timelimitremaining = $testsettings['timelimit']-($now - $starttime);
            //check if past timelimit
            if ($timelimitremaining<1 || isset($_GET['superdone'])) {
                $superdone = true;
                $_GET['done']=true;
            }
            //check for past time limit, with some leniency for javascript timing.
            //want to reject if javascript was bypassed
            if ($timelimitremaining < -1*max(0.05*$testsettings['timelimit'],5)) {
                $temp  .= 'Time limit has expired.  Submission rejected. ';
                $this->leavetestmsg($sessiondata);
                return $temp;
            }
        }

        $qi = getquestioninfo($questions,$testsettings);

        //check for withdrawn
        for ($i=0; $i<count($questions); $i++) {
            if ($qi[$questions[$i]]['withdrawn']==1 && $qi[$questions[$i]]['points']>0) {
                $bestscores[$i] = $qi[$questions[$i]]['points'];
                $bestrawscores[$i] = 1;
            }
        }

        $allowregen = (!$superdone && ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework"));
        $showeachscore = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="AsGo" || $testsettings['testtype']=="Homework");
        $showansduring = (($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework") && is_numeric($showans));
        $showansafterlast = ($showans==='F' || $showans==='J');
        $noindivscores = ($testsettings['testtype']=="EndScore" || $testsettings['testtype']=="NoScores");
        $reviewatend = ($testsettings['testtype']=="EndReview");
        $showhints = ($testsettings['showhints']==1);
        $showtips = $testsettings['showtips'];
        $regenonreattempt = (($testsettings['shuffle']&8)==8 && !$allowregen);
        if ($regenonreattempt) {
            $nocolormark = true;
        }

        $reloadqi = false;
        if (isset($_GET['reattempt'])) {
            if ($_GET['reattempt']=="all") {
                for ($i = 0; $i<count($questions); $i++) {
                    if ($attempts[$i]<$qi[$questions[$i]]['attempts'] || $qi[$questions[$i]]['attempts']==0) {
                        //$scores[$i] = -1;
                        if ($noindivscores) { //clear scores if
                            $bestscores[$i] = -1;
                            $bestrawscores[$i] = -1;
                        }
                        if (!in_array($i,$reattempting)) {
                            $reattempting[] = $i;
                        }
                        if (($regenonreattempt && $qi[$questions[$i]]['regen']==0) || $qi[$questions[$i]]['regen']==1) {
                            $seeds[$i] = rand(1,9999);
                            if (!$isreview) {
                                if (newqfromgroup($i)) {
                                    $reloadqi = true;
                                }
                            }
                            if (isset($qi[$questions[$i]]['answeights'])) {
                                $reloadqi = true;
                            }
                        }
                    }
                }
            } else if ($_GET['reattempt']=="canimprove") {
                $remainingposs = getallremainingpossible($qi,$questions,$testsettings,$attempts);
                for ($i = 0; $i<count($questions); $i++) {
                    if ($attempts[$i]<$qi[$questions[$i]]['attempts'] || $qi[$questions[$i]]['attempts']==0) {
                        if ($noindivscores || getpts($scores[$i])<$remainingposs[$i]) {
                            //$scores[$i] = -1;
                            if (!in_array($i,$reattempting)) {
                                $reattempting[] = $i;
                            }
                            if (($regenonreattempt && $qi[$questions[$i]]['regen']==0) || $qi[$questions[$i]]['regen']==1) {
                                $seeds[$i] = rand(1,9999);
                                if (!$isreview) {
                                    if (newqfromgroup($i)) {
                                        $reloadqi = true;
                                    }
                                }
                                if (isset($qi[$questions[$i]]['answeights'])) {
                                    $reloadqi = true;
                                }
                            }
                        }
                    }
                }
            } else {
                $toclear = $_GET['reattempt'];
                if ($attempts[$toclear]<$qi[$questions[$toclear]]['attempts'] || $qi[$questions[$toclear]]['attempts']==0) {
                    //$scores[$toclear] = -1;
                    if (!in_array($toclear,$reattempting)) {
                        $reattempting[] = $toclear;
                    }
                    if (($regenonreattempt && $qi[$questions[$toclear]]['regen']==0) || $qi[$questions[$toclear]]['regen']==1) {
                        $seeds[$toclear] = rand(1,9999);
                        if (!$isreview) {
                            if (newqfromgroup($toclear)) {
                                $reloadqi = true;
                            }
                        }
                        if (isset($qi[$questions[$toclear]]['answeights'])) {
                            $reloadqi = true;
                        }
                    }
                }
            }
            recordtestdata();
        }

        if (isset($_GET['regen']) && $allowregen && $qi[$questions[$_GET['regen']]]['allowregen']==1) {
            if (!isset($sessiondata['regendelay'])) {
                $sessiondata['regendelay'] = 2;
            }
            $doexit = false;
            if (isset($sessiondata['lastregen'])) {
                if ($now-$sessiondata['lastregen']<$sessiondata['regendelay']) {
                    $sessiondata['regendelay'] = 5;
                    $temp .= '<p>Hey, about slowing down and trying the problem before hitting regen?  Wait 5 seconds before trying again.</p><p>';
                    $log = new Log();
                    $log->createLog($now, 'Quickregen triggered by '. $userid);
                    if (!isset($sessiondata['regenwarnings'])) {
                        $sessiondata['regenwarnings'] = 1;
                    } else {
                        $sessiondata['regenwarnings']++;
                    }
                    if ($sessiondata['regenwarnings']>10) {
                        $log = new Log();
                        $log->createLog($now, 'Over 10 regen warnings triggered by '. $userid);
                    }
                    $doexit = true;
                }
                if ($now - $sessiondata['lastregen'] > 20) {
                    $sessiondata['regendelay'] = 2;
                }
            }
            $sessiondata['lastregen'] = $now;
            $this->writesessiondata($sessiondata, $sessionId);
            if ($doexit) { exit;}
            srand();
            $toregen = $_GET['regen'];
            $seeds[$toregen] = rand(1,9999);
            $scores[$toregen] = -1;
            $rawscores[$toregen] = -1;
            $attempts[$toregen] = 0;
            $newla = array();
            deletefilesifnotused($lastanswers[$toregen],$bestlastanswers[$toregen]);
            $laarr = explode('##',$lastanswers[$toregen]);
            foreach ($laarr as $lael) {
                if ($lael=="ReGen") {
                    $newla[] = "ReGen";
                }
            }
            $newla[] = "ReGen";
            $lastanswers[$toregen] = implode('##',$newla);
            $loc = array_search($toregen,$reattempting);
            if ($loc!==false) {
                array_splice($reattempting,$loc,1);
            }
            if (!$isreview) {
                if (newqfromgroup($toregen)) {
                    $reloadqi = true;
                }
            }
            if (isset($qi[$questions[$toregen]]['answeights'])) {
                $reloadqi = true;
            }
            recordtestdata();
        }

        if (isset($_GET['regenall']) && $allowregen) {
            srand();
            if ($_GET['regenall']=="missed") {
                for ($i = 0; $i<count($questions); $i++) {
                    if (getpts($scores[$i])<$qi[$questions[$i]]['points'] && $qi[$questions[$i]]['allowregen']==1) {
                        $scores[$i] = -1;
                        $rawscores[$i] = -1;
                        $attempts[$i] = 0;
                        $seeds[$i] = rand(1,9999);
                        $newla = array();
                        deletefilesifnotused($lastanswers[$i],$bestlastanswers[$i]);
                        $laarr = explode('##',$lastanswers[$i]);
                        foreach ($laarr as $lael) {
                            if ($lael=="ReGen") {
                                $newla[] = "ReGen";
                            }
                        }
                        $newla[] = "ReGen";
                        $lastanswers[$i] = implode('##',$newla);
                        $loc = array_search($i,$reattempting);
                        if ($loc!==false) {
                            array_splice($reattempting,$loc,1);
                        }
                        if (isset($qi[$questions[$i]]['answeights'])) {
                            $reloadqi = true;
                        }
                    }
                }
            } else if ($_GET['regenall']=="all") {
                for ($i = 0; $i<count($questions); $i++) {
                    if ($qi[$questions[$i]]['allowregen']==0) {
                        continue;
                    }
                    $scores[$i] = -1;
                    $rawscores[$i] = -1;
                    $attempts[$i] = 0;
                    $seeds[$i] = rand(1,9999);
                    $newla = array();
                    deletefilesifnotused($lastanswers[$i],$bestlastanswers[$i]);
                    $laarr = explode('##',$lastanswers[$i]);
                    foreach ($laarr as $lael) {
                        if ($lael=="ReGen") {
                            $newla[] = "ReGen";
                        }
                    }
                    $newla[] = "ReGen";
                    $lastanswers[$i] = implode('##',$newla);
                    $reattempting = array();
                    if (isset($qi[$questions[$i]]['answeights'])) {
                        $reloadqi = true;
                    }
                }
            } else if ($_GET['regenall']=="fromscratch" && $testsettings['testtype']=="Practice" && !$isreview) {
                filehandler::deleteasidfilesbyquery2('userid',$userid,$testsettings['id'],1);
                AssessmentSession::deleteData($userid, $testsettings['id']);
                return $this->redirect(AppUtility::getURLFromHome('assessment','assessment/show-test?cid='.$testsettings['courseid'].'&id='.$testsettings['id']));
            }
            recordtestdata();
        }

        if (isset($_GET['jumptoans']) && $showans==='J') {
            $tojump = $_GET['jumptoans'];
            $attempts[$tojump]=$qi[$questions[$tojump]]['attempts'];
            if ($scores[$tojump]<0){
                $scores[$tojump] = 0;
                $rawscores[$tojump] = 0;
            }
            recordtestdata();
            $reloadqi = true;
        }

        if ($reloadqi) {
            $qi = getquestioninfo($questions,$testsettings);
        }

        $isdiag = isset($sessiondata['isdiag']);
        if ($isdiag) {
            $diagid = $sessiondata['isdiag'];
        }
        $isltilimited = (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0 && $sessiondata['ltirole']=='learner');

        if (isset($CFG['GEN']['keeplastactionlog']) && isset($sessiondata['loginlog'.$testsettings['courseid']])) {
            $now = time();
            LoginLog::setLastAction($sessiondata['loginlog'.$testsettings['courseid']],$now);
        }

        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        $useeditor = 1;
        if (!isset($_POST['embedpostback'])) {

            if ($testsettings['eqnhelper']==1 || $testsettings['eqnhelper']==2) {
                $placeinhead = '<script type="text/javascript">var eetype='.$testsettings['eqnhelper'].'</script>';
                $this->includeJS('eqnhelper.js');
                $placeinhead .= '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';

            } else if ($testsettings['eqnhelper']==3 || $testsettings['eqnhelper']==4) {
                $this->includeCSS('mathquill.css');
                if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')!==false) {
                    $placeinhead = '';
                }
                $this->includeJS('mathquill_min.js','AMtoMQ.js');
                $placeinhead .= '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';

            }

            $useeqnhelper = $testsettings['eqnhelper'];

            //IP: eqntips
            if ($testsettings['showtips']==2) {
//                $this->includeJS('eqntips.js');
            }

            $placeinhead .= '';
            $cid = $testsettings['courseid'];
            if ($testsettings['displaymethod'] == "VideoCue") {
                $this->includeJS('ytapi.js');
            }
            if ($sessiondata['intreereader']) {
                $flexwidth = true;
            }
            if ($testsettings['noprint'] == 1) {
                $temp .= '<style type="text/css" media="print"> div.question, div.todoquestion, div.inactive { display: none;} </style>';
            }
            if ($isltilimited) {
                $temp .= "<span style=\"float:right;\">";
                if ($testsettings['msgtoinstr']==1) {
                    $query = Message::getMsgIds($userid, $courseId);
                    $msgcnt = count($query['id']);
                    $temp .= "<a href=\"/msgs/msglist.php?cid=$cid\" onclick=\"return confirm('".'This will discard any unsaved work.'. "');\">".'Messages'. " ";
                    if ($msgcnt>0) {
                        $temp .= '<span style="color:red;">('.$msgcnt.' new)</span>';
                    }
                    $temp .= '</a> ';
                }
                $latepasscnt = 0;
                if ($testsettings['allowlate']%10>1 && isset($exceptionduedate) && $exceptionduedate>0) {
                    $query = Course::getLatePassHrs($testsettings['courseid']);
                    $latepasshrs = $query['latepasshrs'];
                    $latepasscnt = round(($exceptionduedate - $testsettings['enddate'])/(3600*$latepasshrs));
                }
                if (($testsettings['allowlate']%10==1 || $testsettings['allowlate']%10-1>$latepasscnt) && $sessiondata['latepasses']>0 && !$isreview) {
                    $temp .= "<a href=\"/course/redeemlatepass.php?cid=$cid&aid={$testsettings['id']}\" onclick=\"return confirm('".'This will discard any unsaved work.'. "');\">".'Redeem LatePass'. "</a> ";
                }
                if ($isreview && !(isset($exceptionduedate) && $exceptionduedate>0) && $testsettings['allowlate']>10 && $sessiondata['latepasses']>0 && !isset($sessiondata['stuview']) && !$actas) {
                    $query = Course::getLatePassHrs($testsettings['courseid']);
                    $latepasshrs = $query['latepasshrs'];
                    $viewedassess = array();
                    $query = ContentTrack::getTypeId($testsettings['courseid'], $userid, 'gbviewasid');
                    foreach ($query as $row) {
                        $viewedassess[] = $row['typeid'];
                    }
                    if ((time() - $testsettings['enddate'])<$latepasshrs*3600 && !in_array($testsettings['id'],$viewedassess)) {
                        $temp .= "<a href=\"/course/redeemlatepass.php?cid=$cid&aid={$testsettings['id']}\" onclick=\"return confirm('".'This will discard any unsaved work.'. "');\">".'Redeem LatePass'. "</a> ";
                    }
                }

                if ($sessiondata['ltiitemid']==$testsettings['id'] && $isreview) {
                    if ($showans!='N') {
                        $temp .= '<p><a href="../course/gb-viewasid.php?cid='.$cid.'&asid='.$testid.'">'.'View your scored assessment'. '</a></p>';
                    }
                }
                $temp .= '</span>';
            }

            if ((!$sessiondata['isteacher'] || isset($sessiondata['actas'])) && ($testsettings['isgroup']==1 || $testsettings['isgroup']==2) && ($sessiondata['groupid']==0 || isset($_GET['addgrpmem']))) {
                if (isset($_POST['grpsubmit'])) {
                    if ($sessiondata['groupid']==0) {
                        $temp .= '<p>'.'Group error - lost group info'. '</p>';
                    }
                    $fieldstocopy = 'assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers,feedback,reviewseeds,reviewattempts,reviewscores,reviewlastanswers,reattempting,reviewreattempting';

                    $rowgrptest = AssessmentSession::getAssessmentSessionDataToCopy($fieldstocopy,$testid);
                    $rowgrptest = AppUtility::addslashes_deep($rowgrptest);
                    $insrow = "'".implode("','",$rowgrptest)."'";
                    $loginfo = "$userfullname creating group. ";
                    if (isset($CFG['GEN']['newpasswords'])) {
                        require_once("../components/password.php");
                    }
                    for ($i=1;$i<$testsettings['groupmax'];$i++) {
                        if (isset($_POST['user'.$i]) && $_POST['user'.$i]!=0) {
                            $query = User::getPwdUNameById($_POST['user'.$i]);
                            $thisusername = $query['FirstName'] . ' ' . $query['LastName'];
                            if ($testsettings['isgroup']==1) {
                                $actualpw = $query['password'];
                                $md5pw = md5($_POST['pw'.$i]);
                                if (!($actualpw==$md5pw || (isset($CFG['GEN']['newpasswords']) && password_verify($_POST['pw'.$i],$actualpw)))) {
                                    $temp .= "<p>$thisusername: ".'password incorrect'. "</p>";
                                    continue;
                                }
                            }

                            $thisuser = $_POST['user'.$i];
                            $row = AssessmentSession::getIdAndAGroupId($thisuser, $testsettings['id']);
                            if (count($row)>0) {
                                if ($row['agroupid']>0) {
                                    $temp .= "<p>". sprintf('%s already has a group.  No change made', $thisusername). "</p>";
                                    $loginfo .= "$thisusername already in group. ";
                                } else {
                                    $stuGrpMember = new StuGroupMembers();
                                    $stuGrpMember->insertStuGrpMemberData($userid, $sessiondata['groupid']);
                                    $fieldstocopy = explode(',',$fieldstocopy);
                                    $sets = array();
                                    foreach ($fieldstocopy as $k=>$val) {
                                        $sets[] = "$val='{$rowgrptest[$k]}'";
                                    }
                                    $setslist = implode(',',$sets);
                                    AssessmentSession::updateAssessmentSessionData($setslist,$row['id']);

                                    $temp .= "<p>". sprintf('%s added to group, overwriting existing attempt.', $thisusername). "</p>";
                                    $loginfo .= "$thisusername switched to group. ";
                                }
                            } else {
                                $stuGrpMember = new StuGroupMembers();
                                $stuGrpMember->insertStuGrpMemberData($_POST['user'.$i], $sessiondata['groupid']);

                                $assessmentSessionId = new AssessmentSession();
                                $assessmentSessionId->insertAssessmentSessionData($_POST['user'.$i],$insrow,$fieldstocopy);

                                $temp .= "<p>". sprintf('%s added to group.', $thisusername). "</p>";
                                $loginfo .= "$thisusername added to group. ";
                            }
                        }
                    }
                    $now = time();
                    if (isset($GLOBALS['CFG']['log'])) {
                        $log = new Log();
                        $log->createLog($now, addslashes($loginfo));
                    }
                } else {
                    $temp .=  '<div id="headershowtest" class="pagetitle"><h2>'.'Select group members'. '</h2></div>';
                    if ($sessiondata['groupid']==0) {
                        //a group should already exist
                        $query = Stugroups::getStuGrpId($userid, $testsettings['groupsetid']);
                        if (count($query)==0) {
                            $temp .= '<p>';
                            $temp .= 'Group error.  Please try reaccessing the assessment from the course page';
                            $temp .= '</p>';
                        }
                        $agroupid = $query['id'];
                        $sessiondata['groupid'] = $agroupid;
                        $this->writesessiondata($sessiondata, $sessionId);
                    } else {
                        $agroupid = $sessiondata['groupid'];
                    }


                    $temp .= 'Current Group Members:'. " <ul>";
                    $curgrp = array();
                    $query = StuGroupMembers::getByStuGrpAndUser($sessiondata['groupid']);
                    foreach ($query as $row) {
                        $curgrp[0] = $row['id'];
                        $temp .= "<li>{$row['LastName']}, {$row['FirstName']}</li>";
                    }
                    $temp .= "</ul>";

                    $curinagrp = array();
                    $query = Stugroups::getUserIdStuGrpAndMembers($testsettings['groupsetid']);
                    foreach ($query as $row) {
                        $curinagrp[] = $row['userid'];
                    }
                    $curids = implode(',',$curinagrp);
                    $selops = '<option value="0">' . _('Select a name..') . '</option>';

                    $query = User::getStudata($curids,$testsettings['courseid']);
                    foreach ($query as $row) {
                        $selops .= "<option value=\"{$row['id']}\">{$row['LastName']}, {$row['FirstName']}</option>";
                    }
                    //TODO i18n
                    $temp .= '<p>Each group member (other than the currently logged in student) to be added should select their name ';
                    if ($testsettings['isgroup']==1) {
                        $temp .= 'and enter their password ';
                    }
                    $temp .= 'here.</p>';
                    $temp .= '<form method="post" enctype="multipart/form-data" action="show-test?addgrpmem=true">';
                    $temp .= "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
                    $temp .= '<input type="hidden" name="disptime" value="'.time().'" />';
                    $temp .= "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
                    for ($i=1;$i<$testsettings['groupmax']-count($curgrp)+1;$i++) {
                        $temp .= '<br />'.'Username'. ': <select name="user'.$i.'">'.$selops.'</select> ';
                        if ($testsettings['isgroup']==1) {
                            $temp .='Password'. ': <input type="password" name="pw'.$i.'" autocomplete="off"/>';
                        }
                    }
                    $temp .= '<p><input type=submit name="grpsubmit" value="'.'Record Group and Continue'. '"/></p>';
                    $temp .= '</form>';

                    return $temp;
                }
            }

            //if was added to existing group, need to reload $questions, etc
//            $temp .= '<div id="headershowtest" class="pagetitle">';
//            $temp .= "<h2>{$testsettings['name']}</h2></div>\n";
            if (isset($sessiondata['actas'])) {
                $temp .= '<p style="color: red;">'.'Teacher Acting as ';
                $row = User::getPwdUNameById($sessiondata['actas']);
                $temp .= $row['FirstName'].' '.$row['LastName'];
                $temp .= '<p>';
            }

            if ($testsettings['testtype']=="Practice" && !$isreview) {
                $temp .= "<div class=right><span style=\"color:#f00\">Practice Test.</span>  <a href=\"show-test?regenall=fromscratch\">".'Create new version.'. "</a></div>";
            }
            if (!$isreview && !$superdone) {
                if ($exceptionduedate > 0) {
                    $timebeforedue = $exceptionduedate - time();
                } else {
                    $timebeforedue = $testsettings['enddate'] - time();
                }
                if ($timebeforedue < 0) {
                    $duetimenote = _('Past due');
                } else if ($timebeforedue < 24*3600) { //due within 24 hours
                    if ($timebeforedue < 300) {
                        $duetimenote = '<span style="color:#f00;">' . 'Due in under ';
                    } else {
                        $duetimenote = '<span>' . 'Due in ';
                    }
                    if ($timebeforedue>3599) {
                        $duetimenote .= floor($timebeforedue/3600). " " . 'hours' . ", ";
                    }
                    $duetimenote .= ceil(($timebeforedue%3600)/60). " " . 'minutes';
                    $duetimenote .= '. ';
                    if ($exceptionduedate > 0) {
                        $duetimenote .= 'Due' . " " . AppUtility::tzdate('D m/d/Y g:i a',$exceptionduedate);
                    } else {
                        $duetimenote .= 'Due' . " " . AppUtility::tzdate('D m/d/Y g:i a',$testsettings['enddate']);
                    }
                } else {
                    if ($testsettings['enddate']==2000000000) {
                        $duetimenote = '';
                    } else if ($exceptionduedate > 0) {
                        $duetimenote = 'Due' . " " . AppUtility::tzdate('D m/d/Y g:i a',$exceptionduedate);
                    } else {
                        $duetimenote = 'Due' . " " . AppUtility::tzdate('D m/d/Y g:i a',$testsettings['enddate']);
                    }
                }
            }
            $restrictedtimelimit = false;
            if ($testsettings['timelimit']>0 && !$isreview && !$superdone) {
                $now = time();
                $totremaining = $testsettings['timelimit']-($now - $starttime);
                $remaining = $totremaining;
                if ($timebeforedue < $remaining) {
                    $remaining = $timebeforedue - 5;
                    $restrictedtimelimit = true;
                }
                if ($testsettings['timelimit']>3600) {
                    $tlhrs = floor($testsettings['timelimit']/3600);
                    $tlrem = $testsettings['timelimit'] % 3600;
                    $tlmin = floor($tlrem/60);
                    $tlsec = $tlrem % 60;
                    $tlwrds = "$tlhrs " . _('hour');
                    if ($tlhrs > 1) { $tlwrds .= "s";}
                    if ($tlmin > 0) { $tlwrds .= ", $tlmin " . _('minute');}
                    if ($tlmin > 1) { $tlwrds .= "s";}
                    if ($tlsec > 0) { $tlwrds .= ", $tlsec " . _('second');}
                    if ($tlsec > 1) { $tlwrds .= "s";}
                } else if ($testsettings['timelimit']>60) {
                    $tlmin = floor($testsettings['timelimit']/60);
                    $tlsec = $testsettings['timelimit'] % 60;
                    $tlwrds = "$tlmin " . _('minute');
                    if ($tlmin > 1) { $tlwrds .= "s";}
                    if ($tlsec > 0) { $tlwrds .= ", $tlsec " . _('second');}
                    if ($tlsec > 1) { $tlwrds .= "s";}
                } else {
                    $tlwrds = $testsettings['timelimit'] . " " . _('second(s)');
                }
                if ($remaining < 0) {
                    $temp .= "<div class=right>". sprintf('Timelimit: %s.  Time Expired', $tlwrds). "</div>\n";
                } else {
                    if ($remaining > 3600) {
                        $hours = floor($remaining/3600);
                        $remaining = $remaining - 3600*$hours;
                    } else { $hours = 0;}
                    if ($remaining > 60) {
                        $minutes = floor($remaining/60);
                        $remaining = $remaining - 60*$minutes;
                    } else {$minutes=0;}
                    $seconds = $remaining;
                    $temp .= "<div class=right id=timelimitholder><span id=\"timercontent\">".'Timelimit'. ": $tlwrds. ";
                    if (!isset($_GET['action']) && $restrictedtimelimit) {
                        $temp .= '<span style="color:#0a0;">'.'Time limit shortened because of due date'. '</span> ';
                    }
                    $temp .= "<span id=\"timerwrap\"><span id=timeremaining ";
                    if ($totremaining<300) {
                        $temp .= 'style="color:#f00;" ';
                    }
                    $temp .= ">$hours:$minutes:$seconds</span> ".'remaining'. ".</span></span> <span onclick=\"toggletimer()\" style=\"color:#aaa;\" class=\"clickable\" id=\"timerhide\" title=\"".'Hide'."\">[x]</span></div>\n";
                    $temp .= "<script type=\"text/javascript\">\n";
                    $temp .= " hours = $hours; minutes = $minutes; seconds = $seconds; done=false;\n";
                    $temp .= " function updatetime() {\n";
                    $temp .= "	  seconds--;\n";
                    $temp .= "    if (seconds==0 && minutes==0 && hours==0) {done=true; ";
                    if ($timelimitkickout) {
                        $temp .= "		document.getElementById('timelimitholder').className = \"\";";
                        $temp .= "		document.getElementById('timelimitholder').style.color = \"#f00\";";
                        $temp .= "		document.getElementById('timelimitholder').innerHTML = \"".'Time limit expired - submitting now'. "\";";
                        $temp .= " 		document.getElementById('timelimitholder').style.fontSize=\"300%\";";
                        $temp .= "		if (document.getElementById(\"qform\") == null) { ";
                        $temp .= "			setTimeout(\"window.location.pathname='show-test?action=skip&superdone=true'\",2000); return;";
                        $temp .= "		} else {";
                        $temp .= "		var theform = document.getElementById(\"qform\");";
                        $temp .= " 		var action = theform.getAttribute(\"action\");";
                        $temp .= "		theform.setAttribute(\"action\",action+'&superdone=true');";
                        $temp .= "		if (doonsubmit(theform,true,true)) { setTimeout('document.getElementById(\"qform\").submit()',2000);}} \n";
                        $temp .= "		return 0;";
                        $temp .= "      }";

                    } else {
                        $temp .= "		alert(\"".'Time Limit has elapsed'. "\");}\n";
                    }
                    $temp .= "    if (seconds==0 && minutes==5 && hours==0) {document.getElementById('timeremaining').style.color=\"#f00\";}\n";
                    $temp .= "    if (seconds==5 && minutes==0 && hours==0) {document.getElementById('timeremaining').style.fontSize=\"150%\";}\n";
                    $temp .= "    if (seconds < 0) { seconds=59; minutes--; }\n";
                    $temp .= "    if (minutes < 0) { minutes=59; hours--;}\n";
                    $temp .= "	  str = '';\n";
                    $temp .= "	  if (hours > 0) { str += hours + ':';}\n";
                    $temp .= "    if (hours > 0 && minutes <10) { str += '0';}\n";
                    $temp .= "	  if (minutes >0) {str += minutes + ':';}\n";
                    $temp .= "	    else if (hours>0) {str += '0:';}\n";
                    $temp .= "      else {str += ':';}\n";
                    $temp .= "    if (seconds<10) { str += '0';}\n";
                    $temp .= "	  str += seconds + '';\n";
                    $temp .= "	  document.getElementById('timeremaining').innerHTML = str;\n";
                    $temp .= "    if (!done) {setTimeout(\"updatetime()\",1000);}\n";
                    $temp .= " }\n";
                    $temp .= " updatetime();\n";
                    $temp .= ' $(document).ready(function() {
			var s = $("#timerwrap");
			var pos = s.position();
			$(window).scroll(function() {
			   var windowpos = $(window).scrollTop();
			   if (windowpos >= pos.top) {
			     s.addClass("sticky");
			   } else {
			     s.removeClass("sticky");
			   }
			   });
			   });';
                    $temp .= ' function toggletimer() {
				if ($("#timerhide").text()=="[x]") {
					$("#timercontent").hide();
					$("#timerhide").text("['._("Show Timer").']");
					$("#timerhide").attr("title","'._("Show Timer").'");
				} else {
					$("#timercontent").show();
					$("#timerhide").text("[x]");
					$("#timerhide").attr("title","'._("Hide").'");
				}
			}';
                    $temp .= "</script>\n";
                }
            } else if ($isreview) {
                $temp .= "<div class=right style=\"color:#f00;clear:right;\">In Review Mode - no scores will be saved<br/><a href=\"show-test?regenall=all\">".'Create new versions of all questions.'. "</a></div>\n";
            } else if ($superdone) {
                $temp .= "<div class=right>".'Time limit expired'."</div>";
            } else {
                $temp .= "<div class=right>$duetimenote</div>\n";
            }
        } else {

        }
        if (strpos($testsettings['intro'],'[Q')!==false) {
            $testsettings['intro'] = preg_replace('/((<span|<strong|<em)[^>]*>)?\[Q\s+(\d+(\-(\d+))?)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?/','[Q $3]',$testsettings['intro']);
            if(preg_match_all('/\<p[^>]*>\s*\[Q\s+(\d+)(\-(\d+))?\s*\]\s*<\/p>/',$testsettings['intro'],$introdividers,PREG_SET_ORDER)) {
                $intropieces = preg_split('/\<p[^>]*>\s*\[Q\s+(\d+)(\-(\d+))?\s*\]\s*<\/p>/',$testsettings['intro']);
                foreach ($introdividers as $k=>$v) {
                    if (count($v)==4) {
                        $introdividers[$k][2] = $v[3];
                    } else if (count($v)==2) {
                        $introdividers[$k][2] = $v[1];
                    }
                }
                $testsettings['intro'] = array_shift($intropieces);
            }
        }
        if (isset($_GET['action'])) {
            if ($_GET['action']=="skip" || $_GET['action']=="seq") {
                $temp .= '<div class="right"><a href="#" onclick="togglemainintroshow(this);return false;">'._("Show Intro/Instructions").'</a></div>';
            }
            if ($_GET['action']=="scoreall") {
                //score test
                $GLOBALS['scoremessages'] = '';
                for ($i=0; $i < count($questions); $i++) {
                    if ($_POST['verattempts'][$i]!=$attempts[$i]) {
                        $temp .= sprintf(_('Question %d has been submitted since you viewed it.  Your answer just submitted was not scored or recorded.'), ($i+1)). "<br/>";
                    } else {
                        scorequestion($i,false);
                    }
                }
                //record scores
                $now = time();
                if (isset($_POST['disptime']) && !$isreview) {
                    $used = $now - intval($_POST['disptime']);
                    $timesontask[0] .= (($timesontask[0]=='')?'':'~').$used;
                }

                if (isset($_POST['saveforlater'])) {
                    recordtestdata(true);
                    if ($GLOBALS['scoremessages'] != '') {
                        $temp .= '<p>'.$GLOBALS['scoremessages'].'</p>';
                    }
                    $temp .= "<p>".'Answers saved, but not submitted for grading.  You may continue with the test, or come back to it later. ';
                    if ($testsettings['timelimit']>0) {$temp .= _('The timelimit will continue to count down');}
                    $temp .= "</p><p>".'<a href="show-test">Return to test</a> or'. ' ';
                    $this->leavetestmsg($sessiondata);

                } else {
                    recordtestdata();
                    if ($GLOBALS['scoremessages'] != '') {
                        $temp .= '<p>'.$GLOBALS['scoremessages'].'</p>';
                    }
                    $shown = $this->showscores($questions,$attempts,$testsettings);

                    $this->endtest($testsettings);
                    if ($shown) {$this->leavetestmsg($sessiondata);}
                }
            } else if ($_GET['action']=="shownext") {

                if (isset($_GET['score'])) {
                    $last = $_GET['score'];

                    if ($_POST['verattempts']!=$attempts[$last]) {
                        $temp .= "<p>".'The last question has been submittted since you viewed it, and that grade is shown below.  Your answer just submitted was not scored or recorded.'. "</p>";
                    } else {
                        if (isset($_POST['disptime']) && !$isreview) {
                            $used = $now - intval($_POST['disptime']);
                            $timesontask[$last] .= (($timesontask[$last]=='')?'':'~').$used;
                        }
                        $GLOBALS['scoremessages'] = '';
                        $rawscore = scorequestion($last);
                        if ($GLOBALS['scoremessages'] != '') {
                            $temp .= '<p>'.$GLOBALS['scoremessages'].'</p>';
                        }
                        //record score

                        recordtestdata();
                    }
                    if ($showeachscore) {
                        $possible = $qi[$questions[$last]]['points'];
                        $temp .= "<p>".'Previous Question'. ":<br/>";
                        if (getpts($rawscore)!=getpts($scores[$last])) {
                            $temp .= "<p>".'Score before penalty on last attempt: ';
                            $temp .= printscore($rawscore,$last);
                            $temp .= "</p>";
                        }
                        $temp .= _('Score on last attempt'). ": ";
                        $temp .= printscore($scores[$last],$last);
                        $temp .= "<br/>". 'Score in gradebook'. ": ";
                        $temp .= printscore($bestscores[$last],$last);

                        $temp .= "</p>\n";
                        if (hasreattempts($last)) {
                            $temp .= "<p><a href='#'>".'Reattempt last question'. "</a>.  ".'If you do not reattempt now, you will have another chance once you complete the test.'. "</p>\n";
                        }
                    }
                    if ($allowregen && $qi[$questions[$last]]['allowregen']==1) {
                        $temp .= "<p><a href='#'>".'Try another similar question'. "</a></p>\n";
                    }
                    //show next
                    unset($toshow);
                    for ($i=$last+1;$i<count($questions);$i++) {
                        if (unans($scores[$i]) || amreattempting($i)) {
                            $toshow=$i;
                            $done = false;
                            break;
                        }
                    }
                    if (!isset($toshow)) { //no more to show
                        $done = true;
                    }
                } else if (isset($_GET['to'])) {
                    $toshow = addslashes($_GET['to']);
                    $done = false;
                }


                if (!$done) { //can show next
                    $temp .= '<div class="right"><a href="#" onclick="togglemainintroshow(this);return false;">'._("Show Intro/Instructions").'</a></div>';
                    $temp .= filter("<div id=\"intro\" class=\"hidden\">{$testsettings['intro']}</div>\n");

                    $temp .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"show-test?action=shownext&amp;score=$toshow\" onsubmit=\"return doonsubmit(this)\">\n";
                    $temp .= "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
                    $temp .= '<input type="hidden" name="disptime" value="'.time().'" />';
                    $temp .= "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
                    showqinfobar($toshow,true,true,2);
                    basicshowq($toshow,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores);
                    $temp .= '<input type="submit" class="btn" value="'.'Continue'. '" />';
                } else { //are all done
                    $shown = $this->showscores($questions,$attempts,$testsettings);
                    $this->endtest($testsettings);
                    if ($shown) {$this->leavetestmsg($sessiondata);}
                }
            } else if ($_GET['action']=="skip") {

                if (isset($_GET['score'])) { //score a problem
                    $qn = $_GET['score'];

                    if ($_POST['verattempts']!=$attempts[$qn]) {
                        $temp .= "<p>".'This question has been submittted since you viewed it, and that grade is shown below.  Your answer just submitted was not scored or recorded.'. "</p>";
                    } else {
                        if (isset($_POST['disptime']) && !$isreview) {
                            $used = $now - intval($_POST['disptime']);
                            $timesontask[$qn] .= (($timesontask[$qn]=='')?'':'~').$used;
                        }
                        $GLOBALS['scoremessages'] = '';
                        $GLOBALS['questionmanualgrade'] = false;
                        $rawscore = scorequestion($qn);

                        $immediatereattempt = false;
                        if (!$superdone && $showeachscore && hasreattempts($qn)) {
                            if (!(($regenonreattempt && $qi[$questions[$toclear]]['regen']==0) || $qi[$questions[$toclear]]['regen']==1)) {
                                if (!in_array($qn,$reattempting)) {
                                    //$reattempting[] = $qn;
                                    $immediatereattempt = true;
                                }
                            }
                        }
                        //record score
                        recordtestdata();
                    }
                    if (!$superdone) {
                        $temp .= filter("<div id=intro class=hidden>{$testsettings['intro']}</div>\n");
                        $lefttodo = $this->shownavbar($questions,$scores,$qn,$testsettings['showcat']);

                        $temp .= "<div class=inset>\n";
                        $temp .= "<a name=\"beginquestions\"></a>\n";
                        if ($GLOBALS['scoremessages'] != '') {
                            $temp .= '<p>'.$GLOBALS['scoremessages'].'</p>';
                        }

                        if ($showeachscore) {
                            $possible = $qi[$questions[$qn]]['points'];
                            if (getpts($rawscore)!=getpts($scores[$qn])) {
                                $temp .= "<p>".'Score before penalty on last attempt: ';
                                $temp .= printscore($rawscore,$qn);
                                $temp .= "</p>";
                            }
                            $temp .= "<p>";
                            $temp .= _('Score on last attempt: ');
                            $temp .= printscore($scores[$qn],$qn);
                            $temp .= "</p>\n";
                            $temp .= "<p>".'Score in gradebook: ';
                            $temp .= printscore($bestscores[$qn],$qn);
                            $temp .= "</p>";
                            if ($GLOBALS['questionmanualgrade'] == true) {
                                $temp .= '<p><strong>'.'Note:'. '</strong> '.'This question contains parts that can not be auto-graded.  Those parts will count as a score of 0 until they are graded by your instructor'. '</p>';
                            }


                        } else {
                            $temp .= '<p>'._('Question Scored').'</p>';
                        }

                        $reattemptsremain = false;
                        if (hasreattempts($qn)) {
                            $reattemptsremain = true;
                        }

                        if ($allowregen && $qi[$questions[$qn]]['allowregen']==1) {
                            $temp .= '<p>';
                            if ($reattemptsremain && !$immediatereattempt) {
                                $temp .= "<a href=\"show-test?action=skip&amp;to=$qn&amp;reattempt=$qn\">".'Reattempt last question'. "</a>, ";
                            }
                            $temp .= "<a href=\"show-test?action=skip&amp;to=$qn&amp;regen=$qn\">".'Try another similar question'. "</a>";
                            if ($immediatereattempt) {
                                $temp .= _(", reattempt last question below, or select another question.");
                            } else {
                                $temp .= _(", or select another question");
                            }
                            $temp .= "</p>\n";
                        } else if ($reattemptsremain && !$immediatereattempt) {
                            $temp .= "<p><a href=\"show-test?action=skip&amp;to=$qn&amp;reattempt=$qn\">".'Reattempt last question'. "</a>";
                            if ($lefttodo > 0) {
                                $temp .=  _(", or select another question");
                            }
                            $temp .= '</p>';
                        } else if ($lefttodo > 0) {
                            $temp .= "<p>"._('Select another question').'</p>';
                        }

                        if ($reattemptsremain == false && $showeachscore && $showans!='N') {
                            //TODO i18n

                            $temp .= "<p>This question, with your last answer";
                            if (($showansafterlast && $qi[$questions[$qn]]['showans']=='0') || $qi[$questions[$qn]]['showans']=='F' || $qi[$questions[$qn]]['showans']=='J') {
                                $temp .= " and correct answer";
                                $showcorrectnow = true;
                            } else if ($showansduring && $qi[$questions[$qn]]['showans']=='0' && $qi[$questions[$qn]]['showans']=='0' && $showans==$attempts[$qn]) {
                                $temp .= " and correct answer";
                                $showcorrectnow = true;
                            } else {
                                $showcorrectnow = false;
                            }

                            $temp .= ', is displayed below</p>';
                            if (!$noraw && $showeachscore && $GLOBALS['questionmanualgrade'] != true) {
                                //$colors = scorestocolors($rawscores[$qn], '', $qi[$questions[$qn]]['answeights'], $noraw);
                                if (strpos($rawscores[$qn],'~')!==false) {
                                    $colors = explode('~',$rawscores[$qn]);
                                } else {
                                    $colors = array($rawscores[$qn]);
                                }
                            } else {
                                $colors = array();
                            }
                            if ($showcorrectnow) {
                                displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],2,false,$attempts[$qn],false,false,false,$colors);
                            } else {
                                displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],false,false,$attempts[$qn],false,false,false,$colors);
                            }
                            $contactlinks = showquestioncontactlinks($qn);
                            if ($contactlinks!='' && !$sessiondata['istutorial']) {
                                $temp .= '<div class="review">'.$contactlinks.'</div>';
                            }

                        } else if ($immediatereattempt) {
                            $next = $qn;
                            if (isset($intropieces)) {
                                foreach ($introdividers as $k=>$v) {
                                    if ($v[1]<=$next+1 && $next+1<=$v[2]) {//right divider
                                        if ($next+1==$v[1]) {
                                            $temp .= '<div><a href="#" id="introtoggle'.$k.'" onclick="toggleintroshow('.$k.'); return false;">'.'Hide Question Information'. '</a></div>';
                                            $temp .= '<div class="intro" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                        } else {
                                            $temp .= '<div><a href="#" id="introtoggle'.$k.'" onclick="toggleintroshow('.$k.'); return false;">'.'Show Question Information'. '</a></div>';
                                            $temp .= '<div class="intro" style="display:none;" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                        }
                                        break;
                                    }
                                }
                            }
                            $temp .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"show-test?action=skip&amp;score=$next\" onsubmit=\"return doonsubmit(this)\">\n";
                            $temp .= "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
                            $temp .= '<input type="hidden" name="disptime" value="'.time().'" />';
                            $temp .= "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
                            $temp .= "<a name=\"beginquestions\"></a>\n";
                            showqinfobar($next,true,true);
                            basicshowq($next,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores);
                            $temp .= '<input type="submit" class="btn" value="'. _('Submit'). '" />';
                            if (($showans=='J' && $qi[$questions[$next]]['showans']=='0') || $qi[$questions[$next]]['showans']=='J') {
                                $temp .= ' <input type="button" class="btn" value="'.'Jump to Answer'. '" onclick="if (confirm(\''.'If you jump to the answer, you must generate a new version to earn credit'. '\')) {window.location = \'show-test?action=skip&amp;jumptoans='.$next.'&amp;to='.$next.'\'}"/>';
                            }
                            $temp .= "</form>\n";

                        }

                        $temp .= "<br/><p>When you are done, <a href='#'>click here to see a summary of your scores</a>.</p>\n";

                        $temp .= "</div>\n";
                    }
                } else if (isset($_GET['to'])) { //jump to a problem
                    $next = $_GET['to'];
                    $temp .= filter("<div id=intro class=hidden>{$testsettings['intro']}</div>\n");

                    $lefttodo = $this->shownavbar($questions,$scores,$next,$testsettings['showcat']);
                    if (unans($scores[$next]) || amreattempting($next)) {
                        $temp .= "<div class=inset>\n";
                        if (isset($intropieces)) {
                            foreach ($introdividers as $k=>$v) {
                                if ($v[1]<=$next+1 && $next+1<=$v[2]) {//right divider
                                    if ($next+1==$v[1]) {
                                        $temp .= '<div><a href="#" id="introtoggle'.$k.'" onclick="toggleintroshow('.$k.'); return false;">'.'Hide Question Information'. '</a></div>';
                                        $temp .= '<div class="intro" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                    } else {
                                        $temp .= '<div><a href="#" id="introtoggle'.$k.'" onclick="toggleintroshow('.$k.'); return false;">'.'Show Question Information'. '</a></div>';
                                        $temp .= '<div class="intro" style="display:none;" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                    }
                                    break;
                                }
                            }
                        }
                        $temp .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"show-test?action=skip&amp;score=$next\" onsubmit=\"return doonsubmit(this)\">\n";
                        $temp .= "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
                        $temp .= '<input type="hidden" name="disptime" value="'.time().'" />';
                        $temp .= "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
                        $temp .= "<a name=\"beginquestions\"></a>\n";
                        showqinfobar($next,true,true);
                        basicshowq($next,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores);
                        $temp .= '<input type="submit" class="btn" value="'. _('Submit'). '" />';
                        if (($showans=='J' && $qi[$questions[$next]]['showans']=='0') || $qi[$questions[$next]]['showans']=='J') {
                            $temp .= ' <input type="button" class="btn" value="'.'Jump to Answer'. '" onclick="if (confirm(\''.'If you jump to the answer, you must generate a new version to earn credit'. '\')) {window.location = \'show-test?action=skip&amp;jumptoans='.$next.'&amp;to='.$next.'\'}"/>';
                        }
                        $temp .= "</form>\n";
                        $temp .= "</div>\n";
                    } else {
                        $temp .= "<div class=inset>\n";
                        $temp .= "<a name=\"beginquestions\"></a>\n";
                        $temp .= "You've already done this problem.". "\n";
                        $reattemptsremain = false;
                        if ($showeachscore) {
                            $possible = $qi[$questions[$next]]['points'];
                            $temp .= "<p>".'Score on last attempt: ';
                            $temp .= printscore($scores[$next],$next);
                            $temp .= "</p>\n";
                            $temp .= "<p>".'Score in gradebook: ';
                            $temp .= printscore($bestscores[$next],$next);
                            $temp .= "</p>";
                        }
                        if (hasreattempts($next)) {
                            //if ($showeachscore) {
                            $temp .= "<p><a href=\"show-test?action=skip&amp;to=$next&amp;reattempt=$next\">".'Reattempt this question'. "</a></p>\n";
                            //}
                            $reattemptsremain = true;
                        }
                        if ($allowregen && $qi[$questions[$next]]['allowregen']==1) {
                            $temp .= "<p><a href=\"show-test?action=skip&amp;to=$next&amp;regen=$next\">".'Try another similar question'. "</a></p>\n";
                        }
                        if ($lefttodo == 0) {
                            $temp .= "<a href=\"show-test?action=skip&amp;done=true\">".'When you are done, click here to see a summary of your score'. "</a>\n";
                        }
                        if (!$reattemptsremain && $showans!='N') {// && $showeachscore) {
                            $temp .= "<p>".'Question with last attempt is displayed for your review only'. "</p>";

                            if (!$noraw && $showeachscore) {
                                //$colors = scorestocolors($rawscores[$next], '', $qi[$questions[$next]]['answeights'], $noraw);
                                if (strpos($rawscores[$next],'~')!==false) {
                                    $colors = explode('~',$rawscores[$next]);
                                } else {
                                    $colors = array($rawscores[$next]);
                                }
                            } else {
                                $colors = array();
                            }
                            $qshowans = ((($showansafterlast && $qi[$questions[$next]]['showans']=='0') || $qi[$questions[$next]]['showans']=='F' || $qi[$questions[$next]]['showans']=='J') || ($showansduring && $qi[$questions[$next]]['showans']=='0' && $attempts[$next]>=$showans));
                            if ($qshowans) {
                                displayq($next,$qi[$questions[$next]]['questionsetid'],$seeds[$next],2,false,$attempts[$next],false,false,false,$colors);
                            } else {
                                displayq($next,$qi[$questions[$next]]['questionsetid'],$seeds[$next],false,false,$attempts[$next],false,false,false,$colors);
                            }
                            $contactlinks = showquestioncontactlinks($next);
                            if ($contactlinks!='') {
                                $temp .= '<div class="review">'.$contactlinks.'</div>';
                            }
                        }
                        $temp .= "</div>\n";
                    }
                }
                if (isset($_GET['done'])) { //are all done

                    $shown = $this->showscores($questions,$attempts,$testsettings);
                    $this->endtest($testsettings);
                    if ($shown) {$this->leavetestmsg($sessiondata);}
                }
            } else if ($_GET['action']=="seq") {
                if (isset($_GET['score'])) { //score a problem
                    $qn = $_GET['score'];
                    if ($_POST['verattempts']!=$attempts[$qn]) {
                        $temp .= "<p>".'The last question has been submitted since you viewed it, and that score is shown below. Your answer just submitted was not scored or recorded.'. "</p>";
                    } else {
                        if (isset($_POST['disptime']) && !$isreview) {
                            $used = $now - intval($_POST['disptime']);
                            $timesontask[$qn] .= (($timesontask[$qn]=='')?'':'~').$used;
                        }
                        $GLOBALS['scoremessages'] = '';
                        $rawscore = scorequestion($qn);
                        //record score
                        recordtestdata();
                    }

                    $temp .= "<div class=review style=\"margin-top:5px;\">\n";
                    if ($GLOBALS['scoremessages'] != '') {
                        $temp .= '<p>'.$GLOBALS['scoremessages'].'</p>';
                    }
                    $reattemptsremain = false;
                    if ($showeachscore) {
                        $possible = $qi[$questions[$qn]]['points'];
                        if (getpts($rawscore)!=getpts($scores[$qn])) {
                            $temp .= "<p>".'Score before penalty on last attempt: ';
                            $temp .= printscore($rawscore,$qn);
                            $temp .= "</p>";
                        }
                        $temp .= "<p>".'Score on last attempt: ';
                        $temp .= printscore($scores[$qn],$qn);
                        $temp .= "</p>\n";
                        $temp .= "<p>".'Score in gradebook: ';
                        $temp .= printscore($bestscores[$qn],$qn);
                        $temp .= "</p>";

                        if (hasreattempts($qn)) {
                            $temp .= "<p><a href=\"show-test?action=seq&amp;to=$qn&amp;reattempt=$qn\">".'Reattempt last question'. "</a></p>\n";
                            $reattemptsremain = true;
                        }
                    }
                    if ($allowregen && $qi[$questions[$qn]]['allowregen']==1) {
                        $temp .= "<p><a href=\"show-test?action=seq&amp;to=$qn&amp;regen=$qn\">".'Try another similar question'. "</a></p>\n";
                    }
                    unset($toshow);
                    if (canimprove($qn) && $showeachscore) {
                        $toshow = $qn;
                    } else {
                        for ($i=$qn+1;$i<count($questions);$i++) {
                            if (unans($scores[$i]) || amreattempting($i)) {
                                $toshow=$i;
                                $done = false;
                                break;
                            }
                        }
                        if (!isset($toshow)) {
                            for ($i=0;$i<$qn;$i++) {
                                if (unans($scores[$i]) || amreattempting($i)) {
                                    $toshow=$i;
                                    $done = false;
                                    break;
                                }
                            }
                        }
                    }
                    if (!isset($toshow)) { //no more to show
                        $done = true;
                    }
                    if (!$done) {
                        $temp .= "<p>".'Question scored. <a href="#curq">Continue with assessment</a>, or when you are done click <a href="show-test?action=seq&amp;done=true">here</a> to see a summary of your score.'. "</p>\n";
                        $temp .= "</div>\n";
                        $temp .= "<hr/>";
                    } else {
                        $temp .= "</div>\n";
                        //$temp .= "<a href=\"show-test?action=skip&done=true\">Click here to finalize and score test</a>\n";
                    }


                }
                if (isset($_GET['to'])) { //jump to a problem
                    $toshow = $_GET['to'];
                }
                if ($done || isset($_GET['done'])) { //are all done

                    $shown = $this->showscores($questions,$attempts,$testsettings);
                    $this->endtest($testsettings);
                    if ($shown) {$this->leavetestmsg($sessiondata);}
                } else { //show more test
                    $temp .= filter("<div id=intro class=hidden>{$testsettings['intro']}</div>\n");

                    $temp .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"show-test?action=seq&amp;score=$toshow\" onsubmit=\"return doonsubmit(this,false,true)\">\n";
                    $temp .= "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
                    $temp .= '<input type="hidden" name="disptime" value="'.time().'" />';
                    $temp .= "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
                    $temp .= "<input type=\"hidden\" name=\"verattempts\" value=\"{$attempts[$toshow]}\" />";

                    for ($i = 0; $i < count($questions); $i++) {
                        if (isset($intropieces)) {
                            foreach ($introdividers as $k=>$v) {
                                if ($v[1]==$i+1) {//right divider
                                    $temp .= '<div class="intro" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                    break;
                                }
                            }
                        }
                        $seqShowQuestion = seqshowqinfobar($i,$toshow);
                        $qavail = $seqShowQuestion[0];
                        $temp .= $seqShowQuestion[1];
                        if ($i==$toshow) {
                            $temp .= '<div class="curquestion">';
                            basicshowq($i,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores,false);
                            $temp .= '</div>';
                        } else if ($qavail) {
                            $temp .= "<div class=todoquestion>";
                            basicshowq($i,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores,true);
                            $temp .= "</div>";
                        } else {
                            basicshowq($i,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores,true);
                        }

                        if ($i==$toshow) {
                            $temp .= "<div><input type=\"submit\" class=\"btn\" value=".sprintf('Submit Question %d', ($i+1))." /></div><p></p>\n";
                        }
                        $temp .= '<hr class="seq"/>';
                    }

                }
            } else if ($_GET['action']=='embeddone') {
                $shown = $this->showscores($questions,$attempts,$testsettings);
                $this->endtest($testsettings);
                if ($shown) {$this->leavetestmsg($sessiondata);}
            } else if ($_GET['action']=='scoreembed') {
                $qn = $_POST['toscore'];
                $colors = array();
                $page = $_GET['page'];
                $divopen = false;
                if ($_POST['verattempts']!=$attempts[$qn]) {
                    $temp .= '<div class="prequestion">';
                    $temp .= _('This question has been submittted since you viewed it, and that grade is shown below.  Your answer just submitted was not scored or recorded.');
                    $divopen = true;
                } else {
                    if (isset($_POST['disptime']) && !$isreview) {
                        $used = $now - intval($_POST['disptime']);
                        $timesontask[$qn] .= (($timesontask[$qn]=='')?'':'~').$used;
                    }
                    $GLOBALS['scoremessages'] = '';
                    $GLOBALS['questionmanualgrade'] = false;
                    $rawscore = scorequestion($qn);

                    //record score
                    recordtestdata();

                    //is it video question?
                    if ($testsettings['displaymethod'] == "VideoCue") {

                        $viddata = unserialize($testsettings['viddata']);

                        foreach ($viddata as $i=>$v) {
                            if ($i>0 && isset($v[2]) && $v[2]==$qn) {
                                $temp .= '<div>';
                                $hascontinue = true;
                                if (isset($v[3]) && getpts($rawscore)>.99) {
                                    $temp .= '<span class="inlinebtn" onclick="thumbSet.jumpToTime('.$v[1].',true);">';
                                    $temp .= sprintf(_('Continue video to %s'), $v[5]). '</span> ';
                                    if (isset($viddata[$i+1])) {
                                        $temp .= '<span class="inlinebtn" onclick="thumbSet.jumpToTime('.$v[3].',false);">';
                                        $temp .= sprintf(_('Jump video to %s'), $viddata[$i+1][0]). '</span> ';
                                    }
                                } else if (isset($v[3])) {
                                    $temp .= '<span class="inlinebtn" onclick="thumbSet.jumpToTime('.$v[1].',true);">';
                                    $temp .= sprintf(_('Continue video to %s'), $v[5]). '</span> ';
                                } else if (isset($viddata[$i+1])) {
                                    $temp .= '<span class="inlinebtn" onclick="thumbSet.jumpToTime('.$v[1].',true);">';
                                    $temp .= sprintf(_('Continue video to %s'), $viddata[$i+1][0]). '</span> ';
                                } else {
                                    $hascontinue = false;
                                }
                                if (hasreattempts($qn) && getpts($rawscore)<.99) {
                                    if ($hascontinue) {
                                        $temp .= _('or try the problem again');
                                    } else {
                                        $temp .= _('Try the problem again');
                                    }
                                }

                                $temp .= '</div>';
                                break;
                            }
                        }
                    }

                    embedshowicon($qn);
                    if (!$sessiondata['istutorial']) {
                        $temp .= '<div class="prequestion">';
                        $divopen = true;
                        if ($GLOBALS['scoremessages'] != '') {
                            $temp .= '<p>'.$GLOBALS['scoremessages'].'</p>';
                        }
                        $reattemptsremain = false;
                        if ($showeachscore) {
                            $possible = $qi[$questions[$qn]]['points'];
                            if (getpts($rawscore)!=getpts($scores[$qn])) {
                                $temp .= "<p>".'Score before penalty on last attempt: ';
                                $temp .= printscore($rawscore,$qn);
                                $temp .= "</p>";
                            }
                            $temp .= "<p>";
                            $temp .= _('Score on last attempt: ');
                            $temp .= printscore($scores[$qn],$qn);
                            $temp .= "<br/>\n";
                            $temp .= _('Score in gradebook: ');
                            $temp .= printscore($bestscores[$qn],$qn);
                            if ($GLOBALS['questionmanualgrade'] == true) {
                                $temp .= '<br/><strong>'.'Note:'. '</strong> '.'This question contains parts that can not be auto-graded.  Those parts will count as a score of 0 until they are graded by your instructor';
                            }
                            $temp .= "</p>";


                        } else {
                            $temp .= '<p>'.'Question scored.'. '</p>';
                        }
                    }
                    if ($showeachscore && $GLOBALS['questionmanualgrade'] != true) {
                        if (!$noraw) {
                            if (strpos($rawscores[$qn],'~')!==false) {
                                $colors = explode('~',$rawscores[$qn]);
                            } else {
                                $colors = array($rawscores[$qn]);
                            }
                        } else {
                            $colors = scorestocolors($noraw?$scores[$qn]:$rawscores[$qn],$qi[$questions[$qn]]['points'],$qi[$questions[$qn]]['answeights'],$noraw);
                        }
                    }


                }
                if ($allowregen && $qi[$questions[$qn]]['allowregen']==1) {
                    $temp .= "<p><a href=\"show-test?regen=$qn&page=$page\">".'Try another similar question'. "</a></p>\n";
                }
                if (hasreattempts($qn)) {
                    if ($divopen) { $temp .= '</div>';}

                    ob_start();
                    basicshowq($qn,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores,false,$colors);
                    $quesout = ob_get_clean();
                    $quesout = substr($quesout,0,-7).'<br/><input type="button" class="btn" value="' . _('Submit') . '" onclick="assessbackgsubmit('.$qn.',\'submitnotice'.$qn.'\')" /><span id="submitnotice'.$qn.'"></span></div>';
                    $temp .= $quesout;

                } else {
                    if (!$sessiondata['istutorial']) {
                        $temp .= "<p>".'No attempts remain on this problem.'. "</p>";
                        if ($showeachscore) {
                            //TODO i18n
                            $msg =  "<p>This question, with your last answer";
                            if (($showansafterlast && $qi[$questions[$qn]]['showans']=='0') || $qi[$questions[$qn]]['showans']=='F' || $qi[$questions[$qn]]['showans']=='J') {
                                $msg .= " and correct answer";
                                $showcorrectnow = true;
                            } else if ($showansduring && $qi[$questions[$qn]]['showans']=='0' && $qi[$questions[$qn]]['showans']=='0' && $showans==$attempts[$qn]) {
                                $msg .= " and correct answer";
                                $showcorrectnow = true;
                            } else {
                                $showcorrectnow = false;
                            }
                            if ($showcorrectnow) {
                                $temp .= $msg . ', is displayed below</p>';
                                $temp .= '</div>';
                                displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],2,false,$attempts[$qn],false,false,true,$colors);
                            } else {
                                $temp .= $msg . ', is displayed below</p>';
                                $temp .= '</div>';
                                displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],0,false,$attempts[$qn],false,false,true,$colors);
                            }

                        } else {
                            $temp .= '</div>';
                            if ($showans!='N') {
                                displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],0,false,$attempts[$qn],false,false,true,$colors);
                            }
                        }
                    } else {
                        if ($divopen) { $temp .= '</div>';}
                    }

                }

                showqinfobar($qn,true,false,true);

                $temp .= '<script type="text/javascript">document.getElementById("disptime").value = '.time().';';
                if (strpos($testsettings['intro'],'[PAGE')!==false || $sessiondata['intreereader']) {
                    $temp .= 'embedattemptedtrack["q'.$qn.'"][1]=0;';
                    if (false && $showeachscore) {
                        $temp .= 'embedattemptedtrack["q'.$qn.'"][2]='. (canimprove($qn)?"1":"0") . ';';
                    }
                    if ($showeachscore) {
                        $pts = getpts($bestscores[$qn]);
                        $temp .= 'embedattemptedtrack["q'.$qn.'"][3]='. (($pts>0)?$pts:0) . ';';
                    }
                    $temp .= 'updateembednav();';
                }
                $temp .= '</script>';
                return $temp;

            }
        } else { //starting test display
            $canimprove = false;
            $hasreattempts = false;
            $ptsearned = 0;
            $perfectscore = false;

            for ($j=0; $j<count($questions);$j++) {
                $canimproveq[$j] = canimprove($j);
                $hasreattemptsq[$j] = hasreattempts($j);
                if ($canimproveq[$j]) {
                    $canimprove = true;
                }
                if ($hasreattemptsq[$j]) {
                    $hasreattempts = true;
                }
                $ptsearned += getpts($scores[$j]);
            }
            if ($testsettings['timelimit']>0 && !$isreview && !$superdone && $remaining < 0) {
                $temp .= '<script type="text/javascript">';
                $temp .= 'initstack.push(function() {';
                if ($timelimitkickout) {
                    $temp .= 'alert("'.'Your time limit has expired.  If you try to submit any questions, your submissions will be rejected.'. '");';
                } else {
                    $temp .= 'alert("'.'Your time limit has expired.  If you submit any questions, your assessment will be marked overtime, and will have to be reviewed by your instructor.'. '");';
                }
                $temp .= '});</script>';
            }
            if ($testsettings['displaymethod'] != "Embed") {
                $testsettings['intro'] .= "<p>" . 'Total Points Possible: ' . totalpointspossible($qi) . "</p>";
            }
            if ($testsettings['isgroup']>0) {
                $testsettings['intro'] .= "<p><span style=\"color:red;\">" . _('This is a group assessment.  Any changes effect all group members.') . "</span><br/>";
                if (!$isteacher || isset($sessiondata['actas'])) {
                    $testsettings['intro'] .= _('Group Members:') . " <ul>";
                    $query = AssessmentSession::getFromUser($sessiondata['groupid']);
                    foreach ($query as $row) {
                        $curgrp[] = $row['id'];
                        $testsettings['intro'] .= "<li>{$row['LastName']}, {$row['FirstName']}</li>";
                    }
                    $testsettings['intro'] .= "</ul>";

                    if ($testsettings['isgroup'] == 1 || $testsettings['isgroup'] == 2) {
                        if (count($curgrp)<$testsettings['groupmax']) {
                            $testsettings['intro'] .= "<a href=\"show-test?addgrpmem=true\">" . 'Add Group Members' . "</a></p>";
                        } else {
                            $testsettings['intro'] .= '</p>';
                        }
                    } else {
                        $testsettings['intro'] .= '</p>';
                    }
                }
            }
            if ($ptsearned==totalpointspossible($qi)) {
                $perfectscore = true;
            }
            if ($testsettings['displaymethod'] == "AllAtOnce") {
                $temp .= filter("<div class='test-etting-intro'>{$testsettings['intro']}</div>\n");
                $temp .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"show-test?action=scoreall\" onsubmit=\"return doonsubmit(this,true)\">\n";
                $temp .= "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
                $temp .= '<input type="hidden" name="disptime" value="'.time().'" />';
                $temp .= "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
                $numdisplayed = 0;
                for ($i = 0; $i < count($questions); $i++) {
                    if (unans($scores[$i]) || amreattempting($i)) {
                        basicshowq($i,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores);
                        showqinfobar($i,true,false,1);
                        $numdisplayed++;
                    }
                }
                if ($numdisplayed > 0) {
                    $temp .= '<br/><input type="submit" class="btn" value="'.'Submit'. '" />';
                    $temp .= '<input type="submit" class="btn margin-left-twenty" name="saveforlater" value="'.'Save answers'. '" onclick="return confirm(\''.'This will save your answers so you can come back later and finish, but not submit them for grading. Be sure to come back and submit your answers before the due date.'. '\');" />';
                    $temp .= "</form>\n";
                } else {
                    $temp .= startoftestmessage($perfectscore,$hasreattempts,$allowregen,$noindivscores,$testsettings['testtype']=="NoScores");
                    $temp .= "</form>\n";
                    $this->leavetestmsg($sessiondata);

                }
            } else if ($testsettings['displaymethod'] == "OneByOne") {
                for ($i = 0; $i<count($questions);$i++) {
                    if (unans($scores[$i]) || amreattempting($i)) {
                        break;
                    }
                }
                if ($i == count($questions)) {
                    $temp .= startoftestmessage($perfectscore,$hasreattempts,$allowregen,$noindivscores,$testsettings['testtype']=="NoScores");

                    $this->leavetestmsg($sessiondata);

                } else {
                    $temp .= filter("<div class='test-etting-intro'>{$testsettings['intro']}</div>\n");
                    $temp .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"show-test?action=shownext&amp;score=$i\" onsubmit=\"return doonsubmit(this)\">\n";
                    $temp .= "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
                    $temp .= '<input type="hidden" name="disptime" value="'.time().'" />';
                    $temp .= "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
                    showqinfobar($i,true,true,2);
                    basicshowq($i,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores);
                    $temp .= '<input type="submit" class="btn" value="'.'Next'. '" />';
                    $temp .= "</form>\n";
                }
            } else if ($testsettings['displaymethod'] == "SkipAround") {
                $temp .= filter("<div class='test-etting-intro'>{$testsettings['intro']}</div>\n");

                for ($i = 0; $i<count($questions);$i++) {
                    if (unans($scores[$i]) || amreattempting($i)) {
                        break;
                    }
                }
                $this->shownavbar($questions,$scores,$i,$testsettings['showcat']);
                if ($i == count($questions)) {
                    $temp .= "<div class=inset><br/>\n";
                    $temp .= "<a name=\"beginquestions\"></a>\n";

                    $temp .= startoftestmessage($perfectscore,$hasreattempts,$allowregen,$noindivscores,$testsettings['testtype']=="NoScores");

                    $this->leavetestmsg($sessiondata);

                } else {
                    $temp .= "<div class=inset>\n";
                    if (isset($intropieces)) {
                        foreach ($introdividers as $k=>$v) {
                            if ($v[1]<=$i+1 && $i+1<=$v[2]) {//right divider
                                $temp .= '<div><a href="#" id="introtoggle'.$k.'" onclick="toggleintroshow('.$k.'); return false;">'.'Hide Question Information'. '</a></div>';
                                $temp .= '<div class="intro" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                break;
                            }
                        }
                    }
                    $temp .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"show-test?action=skip&amp;score=$i\" onsubmit=\"return doonsubmit(this)\">\n";
                    $temp .= "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
                    $temp .= '<input type="hidden" name="disptime" value="'.time().'" />';
                    $temp .= "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
                    $temp .= "<a name=\"beginquestions\"></a>\n";
                    showqinfobar($i,true,true);
                    basicshowq($i,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores);
                    $temp .= '<input type="submit" class="btn" value="'.'Submit'. '" />';
                    if (($showans=='J' && $qi[$questions[$i]]['showans']=='0') || $qi[$questions[$i]]['showans']=='J') {
                        $temp .= ' <input type="button" class="btn" value="'.'Jump to Answer'. '" onclick="if (confirm(\''.'If you jump to the answer, you must generate a new version to earn credit'. '\')) {window.location = \'show-test?action=skip&amp;jumptoans='.$i.'&amp;to='.$i.'\'}"/>';
                    }
                    $temp .= "</form>\n";
                    $temp .= "</div>\n";

                }
            } else if ($testsettings['displaymethod'] == "Seq") {
                for ($i = 0; $i<count($questions);$i++) {
                    if ($canimproveq[$i]) {
                        break;
                    }
                }
                if ($i == count($questions)) {
                    $temp .= startoftestmessage($perfectscore,$hasreattempts,$allowregen,$noindivscores,$testsettings['testtype']=="NoScores");

                    $this->leavetestmsg($sessiondata);

                } else {
                    $curq = $i;
                    $temp .= filter("<div class='test-etting-intro'>{$testsettings['intro']}</div>\n");
                    $temp .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"show-test?action=seq&amp;score=$i\" onsubmit=\"return doonsubmit(this,false,true)\">\n";
                    $temp .= "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
                    $temp .= '<input type="hidden" name="disptime" value="'.time().'" />';
                    $temp .= "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
                    $temp .= "<input type=\"hidden\" name=\"verattempts\" value=\"{$attempts[$i]}\" />";
                    for ($i = 0; $i < count($questions); $i++) {
                        if (isset($intropieces)) {
                            foreach ($introdividers as $k=>$v) {
                                if ($v[1]==$i+1) {//right divider
                                    $temp .= '<div class="intro" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                    break;
                                }
                            }
                        }
                        $seqShowQuestion = seqshowqinfobar($i,$curq);
                        $qavail = $seqShowQuestion[0];
                        $temp .= $seqShowQuestion[1];
                        if ($i==$curq) {
                            $temp .= '<div class="curquestion">';
                            basicshowq($i,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores,false);
                            $temp .= '</div>';
                        } else if ($qavail) {
                            $temp .= "<div class=todoquestion>";
                            basicshowq($i,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores,true);
                            $temp .= "</div>";
                        } else {
                            basicshowq($i,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores,true);
                        }
                        if ($i==$curq) {
                            $temp .= "<div><input type=\"submit\" class=\"btn\" value=". sprintf('Submit Question %d', ($i+1))." /></div><p></p>\n";
                        }

                        $temp .= '<hr class="seq"/>';
                    }
                    $temp .= '</form>';
                }
            } else if ($testsettings['displaymethod'] == "Embed" || $testsettings['displaymethod'] == "VideoCue") {
                if (!isset($_GET['page'])) { $_GET['page'] = 0;}
                $intro = filter("<div class=\"intro\">{$testsettings['intro']}</div>\n");
                if ($testsettings['displaymethod'] == "VideoCue") {
                    $temp .= substr(trim($intro),0,-6);
                    if (!$sessiondata['istutorial']) {
                        $temp .= "<p><a href=\"show-test?action=embeddone\">".'When you are done, click here to see a summary of your score'. "</a></p>\n";
                    }
                    $temp .= '</div>';
                    $intro = '';
                }
                $temp .= '<script type="text/javascript">var assesspostbackurl="' .  'show-test?embedpostback=true&action=scoreembed&page='.$_GET['page'].'";</script>';
                //using the full test scoreall action for timelimit auto-submits
                $temp .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"show-test?action=scoreall\" onsubmit=\"return doonsubmit(this,false,true)\">\n";
                if (strpos($intro,'[PAGE')===false && $testsettings['displaymethod'] != "VideoCue") {
                    $temp .= '<div class="formcontents" style="margin-left:20px;">';
                }
                $temp .= "<input type=\"hidden\" id=\"asidverify\" name=\"asidverify\" value=\"$testid\" />";
                $temp .= '<input type="hidden" id="disptime" name="disptime" value="'.time().'" />';
                $temp .= "<input type=\"hidden\" id=\"isreview\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";

                //TODO i18n
                if (strpos($intro,'[QUESTION')===false) {
                    if (isset($intropieces)) {
                        $last = 1;
                        foreach ($introdividers as $k=>$v) {
                            if ($last<$v[1]-1) {
                                for ($j=$last;$j<$v[1];$j++) {
                                    $intro .= '[QUESTION '.$j.']';
                                }
                            }
                            $intro .= '<div class="intro" id="intropiece'.$k.'">'.$intropieces[$k].'</div>';
                            for ($j=$v[1];$j<=$v[2];$j++) {
                                $intro .= '[QUESTION '.$j.']';
                                $last = $j;
                            }
                        }
                        if ($last < count($questions)) {
                            for ($j=$last+1;$j<=count($questions);$j++) {
                                $intro .= '[QUESTION '.$j.']';
                            }
                        }
                    } else {
                        for ($j=1;$j<=count($questions);$j++) {
                            $intro .= '[QUESTION '.$j.']';
                        }
                    }
                } else {
                    $intro = preg_replace('/<p>((<span|<strong|<em)[^>]*>)?\[QUESTION\s+(\d+)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/','[QUESTION $3]',$intro);
                    $intro = preg_replace('/\[QUESTION\s+(\d+)\s*\]/','</div>[QUESTION $1]<div class="intro">',$intro);
                }
                if (strpos($intro,'[PAGE')!==false) {
                    $intro = preg_replace('/<p>((<span|<strong|<em)[^>]*>)?\[PAGE\s*([^\]]*)\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/','[PAGE $3]',$intro);
                    $intro = preg_replace('/\[PAGE\s*([^\]]*)\]/','</div>[PAGE $1]<div class="intro">',$intro);
                    $intropages = preg_split('/\[PAGE\s*([^\]]*)\]/',$intro,-1,PREG_SPLIT_DELIM_CAPTURE); //main pagetitle cont 1 pagetitle
                    if (!isset($_GET['page'])) { $_GET['page'] = 0;}
                    if ($_GET['page']==0) {
                        $temp .= $intropages[0];
                    }
                    $intro =  $intropages[2*$_GET['page']+2];
                    preg_match_all('/\[QUESTION\s+(\d+)\s*\]/',$intro,$matches,PREG_PATTERN_ORDER);
                    if (isset($matches[1]) && count($matches[1])>0) {
                        $qmin = min($matches[1])-1;
                        $qmax = max($matches[1]);
                    } else {
                        $qmin =0; $qmax = 0;
                    }
                    $dopage = true;
                    $dovidcontrol = false;
                    $this->showembednavbar($intropages,$_GET['page']);
                    $temp .= "<div class=inset>\n";
                    $temp .= "<a name=\"beginquestions\"></a>\n";
                } else if ($testsettings['displaymethod'] == "VideoCue") {
                    $viddata = unserialize($testsettings['viddata']);

                    //asychronously load YouTube API
                    //$temp .= '<script type="text/javascript">var tag = document.createElement(\'script\');tag.src = "//www.youtube.com/player_api";var firstScriptTag = document.getElementsByTagName(\'script\')[0];firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);</script>';
                    $temp .= '<script type="text/javascript">var tag = document.createElement(\'script\');tag.src = "//www.youtube.com/player_api";var firstScriptTag = document.getElementsByTagName(\'script\')[0];firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);</script>';


                    //tag.src = "//www.youtube.com/iframe_api";
                    $this->showvideoembednavbar($viddata);
                    $dovidcontrol = true;
                    $temp .= '<div class="inset" style="position: relative; margin-left: 225px; overflow: visible;">';
                    $temp .= "<a name=\"beginquestions\"></a>\n";
                    $temp .= '<div id="playerwrapper"><div id="player"></div></div>';
                    $outarr = array();
                    for ($i=1;$i<count($viddata);$i++) {
                        if (isset($viddata[$i][2])) {
                            $outarr[] = $viddata[$i][1].':{qn:'.$viddata[$i][2].'}';
                        }
                    }
                    $temp .= '<script type="text/javascript">var thumbSet = initVideoObject("'.$viddata[0].'",{'.implode(',',$outarr).'}); </script>';

                    $qmin = 0;
                    $qmax = count($questions);
                    $dopage = false;
                } else {
                    $qmin = 0;
                    $qmax = count($questions);
                    $dopage = false;
                    $dovidcontrol = false;
                    $this->showembedupdatescript();
                }

                for ($i = $qmin; $i < $qmax; $i++) {
                    if ($qi[$questions[$i]]['points']==0 || $qi[$questions[$i]]['withdrawn']==1) {
                        $intro = str_replace('[QUESTION '.($i+1).']','',$intro);
                        continue;
                    }
                    $quesout = '<div id="embedqwrapper'.$i.'" class="embedqwrapper"';
                    if ($dovidcontrol) { $quesout .= ' style="position: absolute; width:100%; visibility:hidden; top:0px;left:-1000px;" ';}
                    $quesout .= '>';
                    ob_start();
                    embedshowicon($i);
                    if (hasreattempts($i)) {

                        basicshowq($i,$showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores,false);
                        $quesout .= ob_get_clean();
                        $quesout = substr($quesout,0,-7).'<br/><input type="button" class="btn" value="'. _('Submit'). '" onclick="assessbackgsubmit('.$i.',\'submitnotice'.$i.'\')" /><span id="submitnotice'.$i.'"></span></div>';

                    } else {
                        if (!$sessiondata['istutorial']) {
                            $temp .= '<div class="prequestion">';
                            $temp .= "<p>".'No attempts remain on this problem.'. "</p>";
                            if ($allowregen && $qi[$questions[$i]]['allowregen']==1) {
                                $temp .= "<p><a href=\"show-test?regen=$i\">".'Try another similar question'. "</a></p>\n";
                            }
                            if ($showeachscore) {
                                //TODO i18n
                                $msg =  "<p>This question, with your last answer";
                                if (($showansafterlast && $qi[$questions[$i]]['showans']=='0') || $qi[$questions[$i]]['showans']=='F' || $qi[$questions[$i]]['showans']=='J') {
                                    $msg .= " and correct answer";
                                    $showcorrectnow = true;
                                } else if ($showansduring && $qi[$questions[$i]]['showans']=='0' && $qi[$questions[$i]]['showans']=='0' && $showans==$attempts[$i]) {
                                    $msg .= " and correct answer";
                                    $showcorrectnow = true;
                                } else {
                                    $showcorrectnow = false;
                                }
                                if ($showcorrectnow) {
                                    $temp .= $msg . ', is displayed below</p>';
                                    $temp .= '</div>';
                                    displayq($i,$qi[$questions[$i]]['questionsetid'],$seeds[$i],2,false,$attempts[$i],false,false,true);
                                } else {
                                    $temp .= $msg . ', is displayed below</p>';
                                    $temp .= '</div>';
                                    displayq($i,$qi[$questions[$i]]['questionsetid'],$seeds[$i],0,false,$attempts[$i],false,false,true);
                                }

                            } else {
                                $temp .= '</div>';
                                if ($showans!='N') {
                                    displayq($i,$qi[$questions[$i]]['questionsetid'],$seeds[$i],0,false,$attempts[$i],false,false,true);
                                }
                            }
                        }

                        $quesout .= ob_get_clean();
                    }
                    ob_start();
                    showqinfobar($i,true,false,true);
                    $reviewbar = ob_get_clean();
                    if (!$sessiondata['istutorial']) {
                        $reviewbar = str_replace('<div class="review">','<div class="review">'._('Question').' '.($i+1).'. ', $reviewbar);
                    }
                    $quesout .= $reviewbar;
                    $quesout .= '</div>';
                    $intro = str_replace('[QUESTION '.($i+1).']',$quesout,$intro);
                }
                $intro = preg_replace('/<div class="intro">\s*(&nbsp;|<p>\s*<\/p>|<\/p>|\s*)\s*<\/div>/','',$intro);
                $temp .= $intro;

                if ($dopage==true) {
                    $temp .= '<p>';
                    if ($_GET['page']>0) {
                        $temp .= '<a href="show-test?page='.($_GET['page']-1).'">Previous Page</a> ';
                    }
                    if ($_GET['page']<(count($intropages)-1)/2-1) {
                        if ($_GET['page']>0) { $temp .= '| ';}
                        $temp .= '<a href="show-test?page='.($_GET['page']+1).'">Next Page</a>';
                    }
                    $temp .= '</p>';
                }
                if (!$sessiondata['istutorial'] && $testsettings['displaymethod'] != "VideoCue") {
                    $temp .= "<p>" .'Total Points Possible: '. totalpointspossible($qi) . "</p>";
                }

                $temp .= '</div>'; //ends either inset or formcontents div
                if (!$sessiondata['istutorial'] && $testsettings['displaymethod'] != "VideoCue") {
                    $temp .= "<p><a href=\"show-test?action=embeddone\">".'When you are done, click here to see a summary of your score'. "</a></p>\n";
                }
                $temp .= '</form>';
            }
        }

        $this->includeJS(['general.js', 'eqntips.js', 'editor/tiny_mce.js', 'AMhelpers.js','confirmsubmit.js','assessment/showQuestion.js']);
        $this->includeCSS(['mathquill.css','mathtest.css']);
        $renderData = array('displayQuestions' => $temp, 'sessiondata' =>  $sessiondata, 'quesout' => $quesout, 'placeinhead' => $placeinhead, 'testsettings' => $testsettings, 'sessiondata' => $sessiondata, 'userfullname' => $userfullname, 'testid' => $testid, 'studentid' => $studentid, 'pwfail' => $pwfail, 'isdiag' => $isdiag);
        return $this->renderWithData('showTest', $renderData);
    }

    function leavetestmsg($sessiondata) {
        global $isdiag, $diagid, $isltilimited, $testsettings,$temp;
        if ($isdiag) {
            $temp .= "<a href=\"../diag/index.php?id=$diagid\">".'Exit Assessment'. "</a></p>\n";
        } else if ($isltilimited || $sessiondata['intreereader']) {

        } else {
            $temp .= "<a href=\"../../course/course/course?cid={$testsettings['courseid']}\">".'Return to Course Page'. "</a></p>\n";
        }
    }

    function showscores($questions,$attempts,$testsettings) {
        global $isdiag,$allowregen,$isreview,$noindivscores,$scores,$bestscores,$qi,$superdone,$timelimitkickout, $reviewatend,$temp;

        $total = 0;
        $lastattempttotal = 0;
        for ($i =0; $i < count($bestscores);$i++) {
            if (getpts($bestscores[$i])>0) { $total += getpts($bestscores[$i]);}
            if (getpts($scores[$i])>0) { $lastattempttotal += getpts($scores[$i]);}
        }

        $totpossible = totalpointspossible($qi);
        $average = 0;
        if($totpossible!=0)
        {
            $average = round(100*((float)$total)/((float)$totpossible),1);
        }

        $doendredirect = false;
        $outmsg = '';
        if ($testsettings['endmsg']!='') {
            $endmsg = unserialize($testsettings['endmsg']);
            $redirecturl = '';
            if (isset($endmsg['msgs'])) {
                foreach ($endmsg['msgs'] as $sc=>$msg) { //array must be reverse sorted
                    if (($endmsg['type']==0 && $total>=$sc) || ($endmsg['type']==1 && $average>=$sc)) {
                        $outmsg = $msg;
                        break;
                    }
                }
                if ($outmsg=='') {
                    $outmsg = $endmsg['def'];
                }
                if (!isset($endmsg['commonmsg'])) {$endmsg['commonmsg']='';}

                if (strpos($outmsg,'redirectto:')!==false) {
                    $redirecturl = trim(substr($outmsg,11));
                    $temp  .= "<input type=\"button\" value=\"".'Continue'. "\" onclick=\"window.location.href='$redirecturl'\"/>";
                    return false;
                }
            }
        }

        if ($isdiag) {
            global $userid;
            $userinfo = User::getById($userid);
            $temp  .= "<h3>{$userinfo['LastName']}, {$userinfo['FirstName']}: ";
            $temp  .= substr($userinfo['SID'],0,strpos($userinfo['SID'],'~'));
            $temp  .= "</h3>\n";
        }

        $temp  .= "<h3>".'Scores:'. "</h3>\n";

        if (!$noindivscores && !$reviewatend) {
            $temp  .= "<table class=scores>";
            for ($i=0;$i < count($scores);$i++) {
                $temp  .= "<tr><td>";
                if ($bestscores[$i] == -1) {
                    $bestscores[$i] = 0;
                }
                if ($scores[$i] == -1) {
                    $scores[$i] = 0;
                    $temp  .= _('Question') . ' ' . ($i+1) . ': </td><td>';
                    $temp  .= _('Last attempt: ');

                    $temp  .= _('Not answered');
                    $temp  .= "</td>";
                    $temp  .= "<td>  ".'Score in Gradebook: ';
                    $temp  .= printscore($bestscores[$i],$i);
                    $temp  .= "</td>";

                    $temp  .= "</tr>\n";
                } else {
                    $temp  .= _('Question') . ' ' . ($i+1) . ': </td><td>';
                    $temp  .= _('Last attempt: ');

                    $temp  .= printscore($scores[$i],$i);
                    $temp  .= "</td>";
                    $temp  .= "<td>  ".'Score in Gradebook: ';
                    $temp  .= printscore($bestscores[$i],$i);
                    $temp  .= "</td>";

                    $temp  .= "</tr>\n";
                }
            }
            $temp  .= "</table>";
        }
        global $testid;

        recordtestdata();

        if ($testtype!="NoScores") {

            $temp  .= "<p>". sprintf('Total Points on Last Attempts:  %d out of %d possible', $lastattempttotal, $totpossible). "</p>\n";

            //if ($total<$testsettings['minscore']) {
            if (($testsettings['minscore']<10000 && $total<$testsettings['minscore']) || ($testsettings['minscore']>10000 && $total<($testsettings['minscore']-10000)/100*$totpossible)) {
                $temp  .= "<p><b>". sprintf(_('Total Points Earned:  %d out of %d possible: '), $total, $totpossible);
            } else {
                $temp  .= "<p><b>". sprintf(_('Total Points in Gradebook: %d out of %d possible: '), $total, $totpossible);
            }

            $temp  .= "$average % </b></p>\n";

            if ($outmsg!='') {
                $temp  .= "<p style=\"color:red;font-weight: bold;\">$outmsg</p>";
                if ($endmsg['commonmsg']!='' && $endmsg['commonmsg']!='<p></p>') {
                    $temp  .= $endmsg['commonmsg'];
                }
            }

            //if ($total<$testsettings['minscore']) {
            if (($testsettings['minscore']<10000 && $total<$testsettings['minscore']) || ($testsettings['minscore']>10000 && $total<($testsettings['minscore']-10000)/100*$totpossible)) {
                if ($testsettings['minscore']<10000) {
                    $reqscore = $testsettings['minscore'];
                } else {
                    $reqscore = ($testsettings['minscore']-10000).'%';
                }
                $temp  .= "<p><span style=\"color:red;\"><b>". sprintf(_('A score of %s is required to receive credit for this assessment'), $reqscore). "<br/>".'Grade in Gradebook: No Credit (NC)'. "</span></p> ";
            }
        } else {
            $temp  .= "<p><b>".'Your scores have been recorded for this assessment.'. "</b></p>";
        }

        //if timelimit is exceeded
        $now = time();
        if (!$timelimitkickout && ($testsettings['timelimit']>0) && (($now-$GLOBALS['starttime']) > $testsettings['timelimit'])) {
            $over = $now-$GLOBALS['starttime'] - $testsettings['timelimit'];
            $temp  .= "<p>".'Time limit exceeded by'. " ";
            if ($over > 60) {
                $overmin = floor($over/60);
                $temp  .= "$overmin ".'minutes'. ", ";
                $over = $over - $overmin*60;
            }
            $temp  .= "$over ".'seconds'. ".<br/>\n";
            $temp  .= 'Grade is subject to acceptance by the instructor'. "</p>\n";
        }


        if (!$superdone) { // $total < $totpossible &&
            if ($noindivscores) {
                $temp  .= "<p>".'<a href="show-test?reattempt=all">Reattempt test</a> on questions allowed (note: where reattempts are allowed, all scores, correct and incorrect, will be cleared)'. "</p>";
            } else {
                if (canimproveany()) {
                    $temp  .= "<p>".'<a href="show-test?reattempt=canimprove">Reattempt test</a> on questions that can be improved where allowed'. "</p>";
                }
                if (hasreattemptsany()) {
                    $temp  .= "<p>".'<a href="show-test?reattempt=all">Reattempt test</a> on all questions where allowed'. "</p>";
                }
            }

            if ($allowregen) {
                $temp  .= "<p>".'<a href="show-test?regenall=missed">Try similar problems</a> for all questions with less than perfect scores where allowed.'. "</p>";
                $temp  .= "<p>".'<a href="show-test?regenall=all">Try similar problems</a> for all questions where allowed.'. "</p>";
            }
        }
        if ($testsettings['testtype']!="NoScores") {
            $hascatset = false;
            foreach($qi as $qii) {
                if ($qii['category']!='0') {
                    $hascatset = true;
                    break;
                }
            }
            if ($hascatset) {
                CategoryScoresUtility::catscores($questions,$bestscores,$testsettings['defpoints'],$testsettings['defoutcome'],$testsettings['courseid']);
            }
        }
        if ($reviewatend) {
            global $testtype, $scores, $saenddate, $isteacher, $istutor, $seeds, $attempts, $rawscores, $noraw;

            $showa=false;

            for ($i=0; $i<count($questions); $i++) {
                $temp  .= '<div>';
                if (!$noraw) {
                    if (strpos($rawscores[$i],'~')!==false) {
                        $col = explode('~',$rawscores[$i]);
                    } else {
                        $col = array($rawscores[$i]);
                    }
                } else {
                    $col = scorestocolors($noraw?$scores[$i]:$rawscores[$i], $qi[$questions[$i]]['points'], $qi[$questions[$i]]['answeights'],$noraw);
                }
                displayq($i, $qi[$questions[$i]]['questionsetid'],$seeds[$i],$showa,false,$attempts[$i],false,false,false,$col);
                $temp  .= "<div class=review>".'Question'." ".($i+1).". ".'Last Attempt:';
                $temp  .= printscore($scores[$i], $i);

                $temp  .= '<br/>'.'Score in Gradebook: ';
                $temp  .= printscore($bestscores[$i],$i);
                $temp  .= '</div>';
            }

        }
        return true;

    }

    function endtest($testsettings) {

        //unset($sessiondata['sessiontestid']);
    }

    function shownavbar($questions,$scores,$current,$showcat) {
        global $imasroot,$isdiag,$testsettings,$attempts,$qi,$allowregen,$bestscores,$isreview,$showeachscore,$noindivscores,$CFG,$temp;
        $todo = 0;
        $earned = 0;
        $poss = 0;
        $temp  .= "<a href=\"#beginquestions\"><img class=skipnav src=\"$imasroot/img/blank.gif\" alt=\"".'Skip Navigation'. "\" /></a>\n";
        $temp  .= "<div class='navbar' style='background-color: #f8f8f8 !important;'>";
        $temp  .= "<h4>".'Questions'. "</h4>\n";
        $temp  .= "<ul class=qlist>\n";
        for ($i = 0; $i < count($questions); $i++) {
            $temp  .= "<li>";
            if ($current == $i) { $temp  .= "<span class=current>";}
            if (unans($scores[$i]) || amreattempting($i)) {
                $todo++;
            }

            if ($isreview) {
                $thisscore = getpts($scores[$i]);
            } else {
                $thisscore = getpts($bestscores[$i]);
            }
            if ((unans($scores[$i]) && $attempts[$i]==0) || ($noindivscores && amreattempting($i))) {
                if (isset($CFG['TE']['navicons'])) {
                    $temp  .= "<img alt=\"untried\" src=\"$imasroot"."img/{$CFG['TE']['navicons']['untried']}\"/> ";
                } else {
                    $temp  .= "<img alt=\"untried\" src=\"$imasroot"."img/q_fullbox.gif\"/> ";
                }
            } else if (canimprove($i) && !$noindivscores) {
                if (isset($CFG['TE']['navicons'])) {
                    if ($thisscore==0 || $noindivscores) {
                        $temp  .= "<img alt=\"incorrect - can retry\" src=\"$imasroot"."img/{$CFG['TE']['navicons']['canretrywrong']}\"/> ";
                    } else {
                        $temp  .= "<img alt=\"partially correct - can retry\" src=\"$imasroot"."img/{$CFG['TE']['navicons']['canretrypartial']}\"/> ";
                    }
                } else {
                    $temp  .= "<img alt=\"can retry\" src=\"$imasroot"."img/q_halfbox.gif\"/> ";
                }
            } else {
                if (isset($CFG['TE']['navicons'])) {
                    if (!$showeachscore) {
                        $temp  .= "<img alt=\"cannot retry\" src=\"$imasroot"."img/{$CFG['TE']['navicons']['noretry']}\"/> ";
                    } else {
                        if ($thisscore == $qi[$questions[$i]]['points']) {
                            $temp  .= "<img alt=\"correct\" src=\"$imasroot"."img/{$CFG['TE']['navicons']['correct']}\"/> ";
                        } else if ($thisscore==0) {
                            $temp  .= "<img alt=\"incorrect - cannot retry\" src=\"$imasroot"."img/{$CFG['TE']['navicons']['wrong']}\"/> ";
                        } else {
                            $temp  .= "<img alt=\"partially correct - cannot retry\" src=\"$imasroot"."img/{$CFG['TE']['navicons']['partial']}\"/> ";
                        }
                    }
                } else {
                    $temp  .= "<img alt=\"cannot retry\" src=\"$imasroot"."img/q_emptybox.gif\"/> ";
                }
            }


            if ($showcat>1 && $qi[$questions[$i]]['category']!='0') {
                if ($qi[$questions[$i]]['withdrawn']==1) {
                    $temp  .= "<a href=\"show-test?action=skip&amp;to=$i\"><span class=\"withdrawn\">". ($i+1) . ") {$qi[$questions[$i]]['category']}</span></a>";
                } else {
                    $temp  .= "<a href=\"show-test?action=skip&amp;to=$i\">". ($i+1) . ") {$qi[$questions[$i]]['category']}</a>";
                }
            } else {
                if ($qi[$questions[$i]]['withdrawn']==1) {
                    $temp  .= "<a href=\"show-test?action=skip&amp;to=$i\"><span class=\"withdrawn\">Q ". ($i+1) . "</span></a>";
                } else {
                    $temp  .= "<a href=\"show-test?action=skip&amp;to=$i\">Q ". ($i+1) . "</a>";
                }
            }
            if ($showeachscore) {
                if (($isreview && canimprove($i)) || (!$isreview && canimprovebest($i))) {
                    $temp  .= ' (';
                } else {
                    $temp  .= ' [';
                }
                if ($isreview) {
                    $thisscore = getpts($scores[$i]);
                } else {
                    $thisscore = getpts($bestscores[$i]);
                }
                if ($thisscore<0) {
                    $temp  .= '0';
                } else {
                    $temp  .= $thisscore;
                    $earned += $thisscore;
                }
                $temp  .= '/'.$qi[$questions[$i]]['points'];
                $poss += $qi[$questions[$i]]['points'];
                if (($isreview && canimprove($i)) || (!$isreview && canimprovebest($i))) {
                    $temp  .= ')';
                } else {
                    $temp  .= ']';
                }
            }

            if ($current == $i) { $temp  .= "</span>";}

            $temp  .= "</li>\n";
        }
        $temp  .= "</ul>";
        if ($showeachscore) {
            if ($isreview) {
                $temp  .= "<p>".'Review: ';
            } else {
                $temp  .= "<p>".'Grade: ';
            }
            $temp  .= "$earned/$poss</p>";
        }
        if (!$isdiag && $testsettings['noprint']==0) {
            $temp  .= "<p><a href=\"#\" onclick=\"window.open('../../assessment/assessment/show-print-test','printver','width=400,height=300,toolbar=1,menubar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));return false;\">".'Print11111111 Version'. "</a></p> ";
        }

        $temp  .= "</div>\n";
        return $todo;
    }

    function showembedupdatescript() {
        global $imasroot,$scores,$bestscores,$showeachscore,$qi,$questions,$testsettings,$temp;

        $jsonbits = array();
        $pgposs = 0;
        for($j=0;$j<count($scores);$j++) {
            $bit = "\"q$j\":[0,";
            if (unans($scores[$j])) {
                $cntunans++;
                $bit .= "1,";
            } else {
                $bit .= "0,";
            }
            if (canimprove($j)) {
                $cntcanimp++;
                $bit .= "1,";
            } else {
                $bit .= "0,";
            }
            $curpts = getpts($bestscores[$j]);
            if ($curpts<0) { $curpts = 0;}
            $bit .= $curpts.']';
            $pgposs += $qi[$questions[$j]]['points'];
            $pgpts += $curpts;
            $jsonbits[] = $bit;
        }
        $temp  .= '<script type="text/javascript">var embedattemptedtrack = {'.implode(',',$jsonbits).'}; </script>';
        $temp  .= '<script type="text/javascript">function updateembednav() {
			var unanscnt = 0;
			var canimpcnt = 0;
			var pts = 0;
			var qcnt = 0;
			for (var i in embedattemptedtrack) {
				if (embedattemptedtrack[i][1]==1) {
					unanscnt++;
				}
				if (embedattemptedtrack[i][2]==1) {
					canimpcnt++;
				}
				pts += embedattemptedtrack[i][3];
				qcnt++;
			}
			var status = 0;';
        if ($showeachscore) {
            $temp  .= 'if (pts == '.$pgposs.') {status=2;} else if (unanscnt<qcnt) {status=1;}';
        } else {
            $temp  .= 'if (unanscnt == 0) { status = 2;} else if (unanscnt<qcnt) {status=1;}';
        }
        $temp  .= 'if (top !== self) {
				try {
					top.updateTRunans("'.$testsettings['id'].'", status);
				} catch (e) {}
			}
		      }</script>';
    }

    function showvideoembednavbar($viddata) {
        global $imasroot,$scores,$bestscores,$showeachscore,$qi,$questions,$testsettings,$temp;
        /*viddata[0] should be video id.  After that, should be [
        0: title for previous video segment,
        1: time to showQ / end of video segment, (in seconds)
        2: qn,
        3: time to jump to if right (and time for next link to start at) (in seconds)
        4: provide a link to watch directly after Q (T/F),
        5: title for the part immediately following the Q]
        */
        $temp  .= "<a href=\"#beginquestions\"><img class=skipnav src=\"$imasroot/img/blank.gif\" alt=\"".'Skip Navigation'. "\" /></a>\n";
        $temp  .= '<div class="navbar" style="width:175px">';
        $temp  .= '<ul class="qlist" style="margin-left:-10px">';
        $timetoshow = 0;
        for ($i=1; $i<count($viddata); $i++) {
            $temp  .= '<li style="margin-bottom:7px;">';
            $temp  .= '<a href="#" onclick="thumbSet.jumpToTime('.$timetoshow.',true);return false;">'.$viddata[$i][0].'</a>';
            if (isset($viddata[$i][2])) {
                $temp  .= '<br/>&nbsp;&nbsp;<a style="font-size:75%;" href="#" onclick="thumbSet.jumpToQ('.$viddata[$i][1].',false);return false;">'.'Jump to Question'. '</a>';
                if (isset($viddata[$i][4]) && $viddata[$i][4]==true) {
                    $temp  .= '<br/>&nbsp;&nbsp;<a style="font-size:75%;" href="#" onclick="thumbSet.jumpToTime('.$viddata[$i][1].',true);return false;">'.$viddata[$i][5].'</a>';
                }
            }
            if (isset($viddata[$i][3])) {
                $timetoshow = $viddata[$i][3];
            } else if (isset($viddata[$i][1])) {
                $timetoshow = $viddata[$i][1];
            }
            $temp  .= '</li>';
        }
        $temp  .= '</ul>';
        $temp  .= '</div>';
        $this->showembedupdatescript();
    }

    function showembednavbar($pginfo,$curpg) {
        global $imasroot,$scores,$bestscores,$showeachscore,$qi,$questions,$testsettings, $temp;
        $temp .= "<a href=\"#beginquestions\"><img class=skipnav src=\"$imasroot/img/blank.gif\" alt=\"".'Skip Navigation'. "\" /></a>\n";

        $temp  .= '<div class="navbar fixedonscroll" style="width:125px">';
        $temp  .= "<h4>".'Pages'. "</h4>\n";
        $temp  .= '<ul class="qlist" style="margin-left:-10px">';
        $jsonbits = array();
        $max = (count($pginfo)-1)/2;
        $totposs = 0;
        for ($i = 0; $i < $max; $i++) {
            $temp  .= '<li style="margin-bottom:7px;">';
            if ($curpg == $i) { $temp  .= "<span class=current>";}
            if (trim($pginfo[2*$i+1])=='') {
                $pginfo[2*$i+1] =  $i+1;
            }
            $temp  .= '<a href="show-test?page='.$i.'">'.$pginfo[2*$i+1].'</a>';
            if ($curpg == $i) { $temp  .= "</span>";}

            preg_match_all('/\[QUESTION\s+(\d+)\s*\]/',$pginfo[2*$i+2],$matches,PREG_PATTERN_ORDER);
            if (isset($matches[1]) && count($matches[1])>0) {
                $qmin = min($matches[1])-1;
                $qmax = max($matches[1]);

                $cntunans = 0;
                $cntcanimp = 0;
                $pgposs = 0;
                $pgpts = 0;
                for($j=$qmin;$j<$qmax;$j++) {
                    $bit = "\"q$j\":[$i,";
                    if (unans($scores[$j])) {
                        $cntunans++;
                        $bit .= "1,";
                    } else {
                        $bit .= "0,";
                    }
                    if (canimprove($j)) {
                        $cntcanimp++;
                        $bit .= "1,";
                    } else {
                        $bit .= "0,";
                    }
                    $curpts = getpts($bestscores[$j]);
                    if ($curpts<0) { $curpts = 0;}
                    $bit .= $curpts.']';
                    $pgposs += $qi[$questions[$j]]['points'];
                    $pgpts += $curpts;
                    $jsonbits[] = $bit;
                }
                $temp  .= '<br/>';

                if ($showeachscore) {
                    $temp  .= " <span id=\"embednavscore$i\" style=\"margin-left:8px\">".round($pgpts,1)." point".(($pgpts==1)?"":"s")."</span> out of $pgposs";
                } else {
                    $temp  .= " <span id=\"embednavunans$i\" style=\"margin-left:8px\">$cntunans</span> unattempted";
                }
                $totposs += $pgposs;
            }
            $temp  .= "</li>\n";
        }
        $temp  .= '</ul>';
        $temp  .= '<script type="text/javascript">var embedattemptedtrack = {'.implode(',',$jsonbits).'}; </script>';
        $temp  .= '<script type="text/javascript">function updateembednav() {
			var unanscnt = [];
			var unanstot = 0; var ptstot = 0;
			var canimpcnt = [];
			var pgpts = [];
			var pgmax = -1;
			var qcnt = 0;
			for (var i in embedattemptedtrack) {
				if (embedattemptedtrack[i][0] > pgmax) {
					pgmax = embedattemptedtrack[i][0];
				}
				qcnt++;
			}
			for (var i=0; i<=pgmax; i++) {
				unanscnt[i] = 0;
				canimpcnt[i] = 0;
				pgpts[i] = 0;

			}
			for (var i in embedattemptedtrack) {
				if (embedattemptedtrack[i][1]==1) {
					unanscnt[embedattemptedtrack[i][0]]++;
					unanstot++;
				}
				if (embedattemptedtrack[i][2]==1) {
					canimpcnt[embedattemptedtrack[i][0]]++;
				}
				pgpts[embedattemptedtrack[i][0]] += embedattemptedtrack[i][3];
				ptstot += embedattemptedtrack[i][3];
			}
			for (var i=0; i<=pgmax; i++) { ';

        if ($showeachscore) {
            $temp  .= 'var el = document.getElementById("embednavscore"+i);';
            $temp  .= 'if (el != null) {';
            $temp  .= '	el.innerHTML = pgpts[i] + ((pgpts[i]==1) ? " point" : " points");';
        } else {
            $temp  .= 'var el = document.getElementById("embednavunans"+i);';
            $temp  .= 'if (el != null) {';
            $temp  .= '	el.innerHTML = unanscnt[i];';
        }

        $temp  .= '}}
			var status = 0;';
        if ($showeachscore) {
            $temp  .= 'if (ptstot == '.$totposs.') {status=2} else if (unanstot<qcnt) {status=1;}';
        } else {
            $temp  .= 'if (unanstot == 0) { status = 2;} else if (unanstot<qcnt) {status=1;}';
        }
        $temp  .= 'if (top !== self) {
				try {
					top.updateTRunans("'.$testsettings['id'].'", status);
				} catch (e) {}
			}
		}</script>';
        $temp  .= '</div>';
    }

    public function actionShowSolution(){
        $id = intval($this->getParamVal('id'));
        $sig = $this->getParamVal('sig');
        $t = intval($this->getParamVal('t'));
        global $sessiondata;
        $flexwidth = true;
        $temp = '<p><b style="font-size:110%">'.AppUtility::t('Written Example').'</b> '._('of a similar problem').'</p>';
        if ($sig != md5($id.$sessiondata['secsalt'])) {
            $temp .= AppUtility::t("invalid signature - not authorized to view the solution for this problem");
        }
        require("../components/displayQuestion.php");
        $txt = displayq(0,$id,0,false,false,0,2+$t);
        $temp .= filter($txt);

        $renderData =array('temp' => $temp);
        return $this->renderWithData('showSolution',$renderData);
    }

    public function actionRecTrackAjax(){
        return $this->redirect('site','work-in-progress ');
    }

    public function actionWatchVideo(){
        $url = $this->getParamVal('url');
        global $urlmode;
        $doEmbed = false;
        $urlmode = AppUtility::urlMode();
        if (strpos($url,'youtube.com/watch')!==false) {
            //youtube
            $videoId = substr($url,strrpos($url,'v=')+2);
            if (strpos($videoId,'&')!==false) {
                $videoId = substr($videoId,0,strpos($videoId,'&'));
            }
            if (strpos($videoId,'#')!==false) {
                $videoId = substr($videoId,0,strpos($videoId,'#'));
            }
            $videoId = str_replace(array(" ","\n","\r","\t"),'',$videoId);
            $timeStart = '?rel=0';
            if (strpos($url,'start=')!==false) {
                preg_match('/start=(\d+)/',$url,$m);
                $timeStart .= '&'.$m[0];
            } else if (strpos($url,'t=')!==false) {
                preg_match('/t=((\d+)m)?((\d+)s)?/',$url,$m);
                $timeStart .= '&start='.((empty($m[2])?0:$m[2]*60) + (empty($m[4])?0:$m[4]*1));
            }

            if (strpos($url,'end=')!==false) {
                preg_match('/end=(\d+)/',$url,$m);
                $timeStart .= '&'.$m[0];
            }
            $doEmbed = true;
            $out = '<iframe width="640" height="510" src="'.$urlmode.'www.youtube.com/embed/'.$videoId.$timeStart.'" frameborder="0" allowfullscreen></iframe>';
        }
        if (strpos($url,'youtu.be/')!==false) {
            //youtube
            $videoId = substr($url,strpos($url,'.be/')+4);
            if (strpos($videoId,'#')!==false) {
                $videoId = substr($videoId,0,strpos($videoId,'#'));
            }
            if (strpos($videoId,'?')!==false) {
                $videoId = substr($videoId,0,strpos($videoId,'?'));
            }
            $videoId = str_replace(array(" ","\n","\r","\t"),'',$videoId);
            $timeStart = '?rel=0';
            if (strpos($url,'start=')!==false) {
                preg_match('/start=(\d+)/',$url,$m);
                $timeStart .= '&'.$m[0];
            } else if (strpos($url,'t=')!==false) {
                preg_match('/t=((\d+)m)?((\d+)s)?/',$url,$m);
                $timeStart .= '&start='.((empty($m[2])?0:$m[2]*60) + (empty($m[4])?0:$m[4]*1));
            }

            if (strpos($url,'end=')!==false) {
                preg_match('/end=(\d+)/',$url,$m);
                $timeStart .= '&'.$m[0];
            }
            $doEmbed = true;
            $out = '<iframe width="640" height="510" src="'.$urlmode.'www.youtube.com/embed/'.$videoId.$timeStart.'" frameborder="0" allowfullscreen></iframe>';
        }
        if (strpos($url,'vimeo.com/')!==false) {
            //youtube
            $videoId = substr($url,strpos($url,'.com/')+5);
            $doEmbed = true;
            $out = '<iframe width="640" height="510" src="http://player.vimeo.com/video/'.$videoId.'" frameborder="0" allowfullscreen></iframe>';
        }
        if ($doEmbed) {
            echo '<html><head><title>Video</title>';
            echo '<meta name="viewport" content="width=660, initial-scale=1">';
            echo '<style type="text/css"> html, body {margin: 0px} html {padding:0px} body {padding: 10px;}</style>';
            echo '<script type="text/javascript">childTimer = window.setInterval(function(){try{window.opener.popupwins[\'video\'] = window;} catch(e){}}, 300);</script>';
            echo '</head>';
            echo '<body>'.$out.'</body></html>';
        } else {
            header("Location: $url");
        }
    }

    public function actionShowPrintTest()
    {
        global $seeds, $questions, $temp;
        $user = $this->getAuthenticatedUser();
        $sessionId = $this->getSessionId();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $teacherId = $this->isTeacher($user['id'], $courseId);
        $myRights = $user['rights'];
        $sessionData = $this->getSessionData($sessionId);
        $scored = $this->getParamVal('scored');
        $asId = $this->getParamVal('asid');
        $isTeacher = (isset($isTeacher) || $sessionData['isteacher'] == true);
        if (isset($teacherId) && isset($scored)) {
            $scoredType = $scored;
            $scoredView = true;
            $showColorMark = true;
        } else {
            $scoredType = 'last';
            $scoredView = false;
        }
        include("../components/displayQuestion.php");
        include("../components/testutil.php");
        if ($isTeacher && isset($asId)) {
            $testId = $asId;
        } else {
            $testId = addslashes($sessionData['sessiontestid']);
        }
        $line = AssessmentSession::getById($testId);

        if (strpos($line['questions'],';')===false) {
            $questions = explode(",",$line['questions']);
            $bestQuestions = $questions;
        } else {
            list($questions,$bestQuestions) = explode(";",$line['questions']);
            $questions = explode(",",$questions);
            $bestQuestions = explode(",",$bestQuestions);
        }

        if ($scoredType == 'last') {
            $seeds = explode(",",$line['seeds']);
            $sp = explode(';',$line['scores']);
            $scores = explode(",",$sp[0]);
            $rawscores = explode(',', $sp[1]);
            $attempts = explode(",",$line['attempts']);
            $lastanswers = explode("~",$line['lastanswers']);
        } else {
            $seeds = explode(",",$line['bestseeds']);
            $sp = explode(';',$line['bestscores']);
            $scores = explode(",",$sp[0]);
            $rawscores = explode(',', $sp[1]);
            $attempts = explode(",",$line['bestattempts']);
            $lastanswers = explode("~",$line['bestlastanswers']);
            $questions = $bestQuestions;
        }
        $timesontask = explode("~",$line['timeontask']);
        if ($isTeacher) {
            if ($line['userid'] != $userId) {
                $row = User::getFirstLastName($line['userid']);
                $userFullName = $row['LastName']." ".$row['FirstName'];

            }
            $userId= $line['userid'];
        }
        $testSettings = Assessments::getAssessmentDataId($line['assessmentid']);
        list($testsettings['testtype'],$testsettings['showans']) = explode('-',$testSettings['deffeedback']);
        $qi = getquestioninfo($questions,$testSettings);

        $now = time();
        $isReview = false;
        if (!$scoredView && ($now < $testSettings['startdate'] || $testSettings['enddate'] < $now)) { //outside normal range for test
            $row = Exceptions::getStartDateEndDate($userId, $line['assessmentid']);
            if ($row!=null) {
                if ($now < $row['startdate'] || $row['enddate'] < $now) { //outside exception dates
                    if ($now > $testSettings['startdate'] && $now < $testSettings['reviewdate']) {
                        $isReview = true;
                    } else {
                        if (!$isTeacher) {
                            $temp .= "Assessment is closed";
                            $temp .= "<br/><a href=\"../course/course.php?cid={$testSettings['courseid']}\">Return to course page</a>";
                            exit;
                        }
                    }
                }
            } else { //no exception
                if ($now > $testSettings['startdate'] && $now < $testSettings['reviewdate']) {
                    $isReview = true;
                } else {
                    if (!$isTeacher) {
                        $temp .= "Assessment is closed";
                        $temp .=  "<br/><a href=\"../course/course.php?cid={$testSettings['courseid']}\">Return to course page</a>";
                        exit;
                    }
                }
            }
        }
        if ($isReview) {
            $seeds = explode(",",$line['reviewseeds']);
            $scores = explode(",",$line['reviewscores']);
            $attempts = explode(",",$line['reviewattempts']);
            $lastanswers = explode("~",$line['reviewlastanswers']);
        }

        $temp .= "<h4 style=\"float:right;\">Name: $userFullName </h4>\n";
        $temp .= "<h3>".$testSettings['name']."</h3>\n";
        $allowregen = ($testSettings['testtype'] == "Practice" || $testSettings['testtype']=="Homework");
        $showeachscore = ($testSettings['testtype']=="Practice" || $testSettings['testtype']=="AsGo" || $testSettings['testtype']=="Homework");
        $showansduring = (($testSettings['testtype']=="Practice" || $testSettings['testtype']=="Homework") && $testSettings['showans']!='N');
        $GLOBALS['useeditor']='reviewifneeded';
        $temp .= "<div class=breadcrumb>Print Ready Version</div>";

        $endtext = '';  $intropieces = array();

        if (strpos($testSettings['intro'], '[QUESTION')!==false) {
            //embedded type
            $intro = preg_replace('/<p>((<span|<strong|<em)[^>]*>)?\[QUESTION\s+(\d+)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/','[QUESTION $3]',$testSettings['intro']);
            $introsplit = preg_split('/\[QUESTION\s+(\d+)\]/', $intro, -1, PREG_SPLIT_DELIM_CAPTURE);

            for ($i=1;$i<count($introsplit);$i+=2) {
                $intropieces[$introsplit[$i]] = $introsplit[$i-1];
            }
            //no specific start text - will just go before first question
            $testSettings['intro'] = '';
            $endtext = $introsplit[count($introsplit)-1];
        } else if (strpos($testSettings['intro'], '[Q ')!==false) {
            //question info type
            $intro = preg_replace('/<p>((<span|<strong|<em)[^>]*>)?\[Q\s+(\d+(\-(\d+))?)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/','[Q $3]',$testSettings['intro']);
            $introsplit = preg_split('/\[Q\s+(.*?)\]/', $intro, -1, PREG_SPLIT_DELIM_CAPTURE);
            $testSettings['intro'] = $introsplit[0];
            for ($i=1;$i<count($introsplit);$i+=2) {
                $p = explode('-',$introsplit[$i]);
                $intropieces[$p[0]] = $introsplit[$i+1];
            }
        }
        $temp .= '<div class=intro>'.$testsettings['intro'].'</div>';
        if ($isTeacher && !$scoredView) {
            $temp .= '<p>'._('Showing Current Versions').'<br/><button type="button" class="btn" onclick="rendersa()">'._("Show Answers").'</button> <a href="show-print-test?cid='.$courseId.'&asid='.$testId.'&scored=best">'._('Show Scored View').'</a> <a href="show-print-test?cid='.$courseId.'&asid='.$testId.'&scored=last">'._('Show Last Attempts').'</a></p>';
        } else if ($isTeacher) {
            if ($scoredType=='last') {
                $temp .= '<p>'._('Showing Last Attempts').' <a href="show-print-test?cid='.$courseId.'&asid='.$testId.'&scored=best">'._('Show Scored View').'</a></p>';
            } else {
                $temp .= '<p>'._('Showing Scored View').' <a href="show-print-test?cid='.$courseId.'&asid='.$testId.'&scored=last">'._('Show Last Attempts').'</a></p>';
            }

        }
        if ($testSettings['showans'] == 'N') {
            $lastanswers = array_fill(0,count($questions),'');
        }

        for ($i = 0; $i < count($questions); $i++) {
            $qsetid = $qi[$questions[$i]]['questionsetid'];
            $cat = $qi[$questions[$i]]['category'];

            $showa = $isTeacher;
            if (isset($intropieces[$i+1])) {
                $temp .= '<div class="intro">'.$intropieces[$i+1].'</div>';
            }
            $temp .= '<div class="nobreak">';
            if (isset($_GET['descr'])) {
                $result = QuestionSet::getDescription($qsetid);
                $temp .= '<div>ID:'.$qsetid.', '.$result['id'].'</div>';
            } else {
                $points = $qi[$questions[$i]]['points'];
                $qattempts = $qi[$questions[$i]]['attempts'];
                if ($scoredView) {
                    $temp .= "<div>#".($i+1)." ";
                    $temp .= printscore($scores[$i], $i);
                    $temp .= "</div>";
                } else {
                    $temp .= "<div>#".($i+1)." Points possible: $points.  Total attempts: $qattempts</div>";
                }
            }

            if ($scoredView) {
                if (isset($rawscores[$i])) {
                    if (strpos($rawscores[$i],'~')!==false) {
                        $colors = explode('~',$rawscores[$i]);
                    } else {
                        $colors = array($rawscores[$i]);
                    }
                } else {
                    $colors = array();
                }
                displayq($i, $qsetid,$seeds[$i],2,false,$attempts[$i],false,false,false,$colors);

                $temp .= '<div class="review">';
                $laarr = explode('##',$lastanswers[$i]);

                if (count($laarr)>1) {
                    $temp .= "Previous Attempts:";
                    $cnt =1;
                    for ($k=0;$k<count($laarr)-1;$k++) {
                        if ($laarr[$k]=="ReGen") {
                            $temp .= ' ReGen ';
                        } else {
                            $temp.= "  <b>$cnt:</b> " ;
                            if (preg_match('/@FILE:(.+?)@/',$laarr[$k],$match)) {
                                $url = filehandler::getasidfileurl($match[1]);
                                $temp.= "<a href=\"$url\" target=\"_new\">".basename($match[1])."</a>";
                            } else {
                                if (strpos($laarr[$k],'$f$')) {
                                    if (strpos($laarr[$k],'&')) { //is multipart q
                                        $laparr = explode('&',$laarr[$k]);
                                        foreach ($laparr as $lk=>$v) {
                                            if (strpos($v,'$f$')) {
                                                $tmp = explode('$f$',$v);
                                                $laparr[$lk] = $tmp[0];
                                            }
                                        }
                                        $laarr[$k] = implode('&',$laparr);
                                    } else {
                                        $tmp = explode('$f$',$laarr[$k]);
                                        $laarr[$k] = $tmp[0];
                                    }
                                }
                                if (strpos($laarr[$k],'$!$')) {
                                    if (strpos($laarr[$k],'&')) { //is multipart q
                                        $laparr = explode('&',$laarr[$k]);
                                        foreach ($laparr as $lk=>$v) {
                                            if (strpos($v,'$!$')) {
                                                $tmp = explode('$!$',$v);
                                                $laparr[$lk] = $tmp[0];
                                            }
                                        }
                                        $laarr[$k] = implode('&',$laparr);
                                    } else {
                                        $tmp = explode('$!$',$laarr[$k]);
                                        $laarr[$k] = $tmp[0];
                                    }
                                }
                                if (strpos($laarr[$k],'$#$')) {
                                    if (strpos($laarr[$k],'&')) { //is multipart q
                                        $laparr = explode('&',$laarr[$k]);
                                        foreach ($laparr as $lk=>$v) {
                                            if (strpos($v,'$#$')) {
                                                $tmp = explode('$#$',$v);
                                                $laparr[$lk] = $tmp[0];
                                            }
                                        }
                                        $laarr[$k] = implode('&',$laparr);
                                    } else {
                                        $tmp = explode('$#$',$laarr[$k]);
                                        $laarr[$k] = $tmp[0];
                                    }
                                }

                                $temp.= str_replace(array('&','%nbsp;'),array('; ','&nbsp;'),strip_tags($laarr[$k]));
                            }
                            $cnt++;
                        }

                    }
                    $temp .= '. ';
                }
                if ($timesontask[$i]!='') {
                    $temp .= 'Average time per submission: ';
                    $timesarr = explode('~',$timesontask[$i]);
                    $avgtime = array_sum($timesarr)/count($timesarr);
                    if ($avgtime<60) {
                        $temp .= round($avgtime,1) . ' seconds ';
                    } else {
                        $temp .= round($avgtime/60,1) . ' minutes ';
                    }
                    $temp .= '<br/>';
                }
                $temp .= '</div>';

            } else {
                displayq($i,$qsetid,$seeds[$i],$showa,($testSettings['showhints']==1),$attempts[$i]);
            }
            $temp .= "<hr />";
            $temp .= '</div>';

        }
        if ($endtext != '') {
            $temp .= '<div class="intro">'.$endtext.'</div>';
        }

        $response = array('scoredView' => $scoredView, 'testSettings' => $testSettings, 'isTeacher' => $isTeacher, 'scoredType' => $scoredType, 'testId' => $testId, 'cid' => $courseId, 'temp' => $temp);
        return $this->renderWithData('showPrintTest',$response);
    }
}

