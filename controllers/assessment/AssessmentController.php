<?php

namespace app\controllers\assessment;

use app\components\AppUtility;
use app\components\AssessmentUtility;
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
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $assessmentId = isset($params['id']) ? trim($params['id']) : "";
        $to = isset($params['to']) ? $params['to'] : AppConstant::NUMERIC_ZERO;
        $courseId = isset($params['cid']) ? trim($params['cid']) : "";
        $assessment = Assessments::getByAssessmentId($assessmentId);
        $teacher = Teacher::getByUserId($user->getId(), $courseId);
        $assessmentSession = AssessmentSession::getAssessmentSession($user->id, $assessmentId);
        if(!$assessmentSession){
            $assessmentSessionObject = new AssessmentSession();
            $assessmentSession = $assessmentSessionObject->saveAssessmentSession($assessment, $user->getId());
        }
        $response = AppUtility::showAssessment($user, $params, $assessmentId, $courseId, $assessment, $assessmentSession, $teacher, $to);
        $isQuestions  = Questions::getByAssessmentId($assessmentId);
        $this->includeCSS(['showAssessment.css', 'mathtest.css']);
        $this->getView()->registerJs('var imasroot="openmath/";');
        $this->includeJS(['timer.js', 'ASCIIMathTeXImg_min.js', 'general.js', 'eqntips.js', 'editor/tiny_mce.js']);
        $responseData = array('response'=> $response,'isQuestions' =>$isQuestions, 'courseId' => $courseId, 'now' => time(),'assessment' => $assessment ,'assessmentSession' => $assessmentSession,'isShowExpiredTime' =>$to);
        return $this->render('ShowAssessment', $responseData);
    }

    public function actionLatePass()
    {
        $this->guestUserHandler();
        $assessmentId = Yii::$app->request->get('id');
        $courseId = Yii::$app->request->get('cid');
        $studentId = Yii::$app->user->identity->id;
        $exceptionAssessment = Exceptions::getByAssessmentId($assessmentId);
        $assessment = Assessments::getByAssessmentId($assessmentId);
        $student = Student::getByCourseId($courseId, $studentId);
        $startdate = $assessment->startdate;
        $enddate = $assessment->enddate;
        $wave = AppConstant::NUMERIC_ZERO;
        $param['assessmentid'] = $assessmentId;
        $param['userid'] = $studentId;
        $param['startdate'] = $startdate;
        $param['enddate'] = $enddate;
        $param['waivereqscore'] = $wave;
        $latepass = $student->latepass;
        $student->latepass = $latepass - AppConstant::NUMERIC_ONE;
        $exception = new Exceptions();
        $exception->attributes = $param;
        $exception->save();
        $student->save();
        $this->redirect(AppUtility::getURLFromHome('course','course/index?id='.$assessmentId.'&cid='.$courseId));
    }
    /**
     * Display password, when assessment need password.
     */
    public function actionPassword()
    {
        $this->guestUserHandler();
        $model = new SetPassword();
        $assessmentId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $assessment = Assessments::getByAssessmentId($assessmentId);
        if ($this->isPostMethod()){
            $params = $this->getRequestParams();
            $password = $params['SetPassword']['password'];
            if($password == $assessment->password){
                return $this->redirect(AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id));
            }
            else{
                $this->setErrorFlash(AppConstant::SET_PASSWORD_ERROR);
            }
        }
        $returnData = array('model' => $model, 'assessments' => $assessment);
        return $this->renderWithData('setPassword', $returnData);
    }

    public function actionPrintTest()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $isTeacher = false;
        $printData = '';
        if($user){
            $assessmentId = $this->getParam('aid');
            $assessmentSession = AssessmentSession::getAssessmentSession($user->id, $assessmentId);
            if($assessmentSession){
                $courseId = $assessmentSession->assessment->course->id;
                $teacher = Teacher::getByUserId($user->id, $courseId);
                if($teacher){
                    $isTeacher = true;
                    $teacherId = $teacher->id;
                }
                $printData = AppUtility::printTest($teacherId, $isTeacher, $assessmentSession->id, $user);
                $this->includeCSS(['showAssessment.css', 'mathtest.css', 'print.css']);
                $responseData = array('response' => $printData);
                return $this->renderWithData('printTest', $responseData);
            }
        }
    }

    public function actionAddAssessment(){
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $courseId =$this->getParamVal('cid');
        $block = $this->getParamVal('block');
        $course = Course::getById($courseId);
        $assessmentId = $params['id'];
        if (isset($params['from'])) {
            $from = $params['from'];
        }else {
            $from = 'cp';
        }
        if (isset($params['tb'])) {
            $filter = $params['tb'];
        } else {
            $filter = 'b';
        }
        if($courseId) {
            $assessmentData = Assessments::getByAssessmentId($assessmentId);
                if(isset($params['clearattempts'])){
                    /*
                     * For Updating Question
                     */
                } elseif($params['name']!= null) {//if the form has been submitted
                    if ($params['avail']==AppConstant::NUMERIC_ONE) {
                        if ($params['sdatetype']==AppConstant::NUMERIC_ZERO) {
                            $startDate = AppConstant::NUMERIC_ZERO;
                        } else {
                            $startDate = AssessmentUtility::parsedatetime($params['sdate'],$params['stime']);
                        }
                        if ($params['edatetype']==AppConstant::ALWAYS_TIME) {
                            $endDate = AppConstant::ALWAYS_TIME;
                        } else {
                            $endDate = AssessmentUtility::parsedatetime($params['edate'],$params['etime']);
                        }
                        if ($params['doreview']==AppConstant::NUMERIC_ZERO) {
                            $reviewDate = AppConstant::NUMERIC_ZERO;
                        } else if ($params['doreview']==AppConstant::ALWAYS_TIME) {
                            $reviewDate = AppConstant::ALWAYS_TIME;
                        } else {
                            $reviewDate = AssessmentUtility::parsedatetime($params['rdate'],$params['rtime']);
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
                    if (isset($params['reattemptsdiffver']) && $params['deffeedback']!="Practice" && $params['deffeedback']!="Homework") {
                        $shuffle += AppConstant::NUMERIC_EIGHT;
                    }
                    if ($params['minscoretype']==AppConstant::NUMERIC_ONE && trim($params['minscore'])!='' && $params['minscore']>AppConstant::NUMERIC_ZERO) {
                        $params['minscore'] = intval($params['minscore'])+AppConstant::NUMERIC_THOUSAND;
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
                    if (isset($params['latepassafterdue']) && $params['allowlate']>AppConstant::NUMERIC_ZERO) {
                        $params['allowlate'] += AppConstant::NUMERIC_TEN;
                    }
                    $timeLimit = $params['timelimit']*AppConstant::SECONDS;
                    if (isset($params['timelimitkickout'])) {
                        $timeLimit = AppConstant::NUMERIC_NEGATIVE_ONE*$timeLimit;
                    }
                    if (isset($params['usedeffb'])) {
                        $defFeedbackText = $params['deffb'];
                    } else {
                        $defFeedbackText = '';
                    }
                    if ($params['deffeedback']=="Practice" || $params['deffeedback']=="Homework") {
                        $defFeedback = $params['deffeedback'].'-'.$params['showansprac'];
                    } else {
                        $defFeedback = $params['deffeedback'].'-'.$params['showans'];
                    }
                    if (!isset($params['doposttoforum'])) {
                        $params['posttoforum'] = AppConstant::NUMERIC_ZERO;
                    }
                    if (isset($params['msgtoinstr'])) {
                        $params['msgtoinstr'] = AppConstant::NUMERIC_ONE;
                    } else {
                        $params['msgtoinstr'] = AppConstant::NUMERIC_ZERO;
                    }
                    if ($params['skippenalty']==AppConstant::NUMERIC_TEN) {
                        $params['defpenalty'] = 'L'.$params['defpenalty'];
                    } else if ($params['skippenalty']>AppConstant::NUMERIC_ZERO) {
                        $params['defpenalty'] = 'S'.$params['skippenalty'].$params['defpenalty'];
                    }
                    if ($params['copyfrom']!=AppConstant::NUMERIC_ZERO) {
                        $query = Assessments::getByAssessmentId($params['copyfrom']);
                        list($timeLimit,$params['minscore'],$params['displaymethod'],$params['defpoints'],
                            $params['defattempts'],$params['defpenalty'],$defFeedback,$shuffle,$params['gbcat'],
                            $params['assmpassword'],$params['cntingb'],$tutorEdit,$params['showqcat'],$copyIntro,
                            $copySummary,$copyStartDate,$copyEndDate,$copyReviewDate,$isGroup,$params['groupmax'],
                            $params['groupsetid'],$showHints,$params['reqscore'],$params['reqscoreaid'],$params['noprint'],
                            $params['allowlate'],$params['eqnhelper'],$endMsg,$params['caltagact'],$params['caltagrev'],
                            $defFeedbackText,$params['showtips'],$params['exceptionpenalty'],$params['ltisecret'],
                            $params['msgtoinstr'],$params['posttoforum'],$isTutorial,$params['defoutcome']) = AppUtility::addslashes_deep($query);
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
                    if ($params['deffeedback']=="Practice") {
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
                    $updategroupset='';
                    if (isset($params['id']) && $params['isgroup']>AppConstant::NUMERIC_ZERO && $params['groupsetid']>AppConstant::NUMERIC_ZERO) {
                        $isok = true;
                        $query = $assessmentData['isgroup'];
                        if ($query==AppConstant::NUMERIC_ZERO) {
                            /*check to see if students have already started assessment
                            *don't really care if groups exist - just whether asids exist
                            */
                            $assessmentSessionData = AssessmentSession::getByUserCourseAssessmentId($assessmentId,$courseId);
                            if ($assessmentSessionData>AppConstant::NUMERIC_ZERO) {
                                $this->setErrorFlash(AppConstant::ASSESSMENT_ALREADY_STARTED);
                                exit;
                            }
                        }
                        $updategroupset = "groupsetid='{$params['groupsetid']}',";
                    }
                    if ($params['isgroup']>AppConstant::NUMERIC_ZERO && isset($params['groupsetid']) && $params['groupsetid']==AppConstant::NUMERIC_ZERO) {
                        //create new groupset
                        $stuGroupSet = new StuGroupSet();
                        $query = $stuGroupSet->createGroupSet($courseId,$params['name']);
                        $params['groupsetid'] = $query;
                        $updategroupset = "groupsetid='{$params['groupsetid']}',";
                    }
                    $calTag = $params['caltagact'];
                    $calrTag = $params['caltagrev'];
                    $params['name'] = addslashes(htmlentities(stripslashes($params['name'])));
//                    require_once("../includes/htmLawed.php");
//                    $htmlawedconfig = array('elements'=>'*-script');
                    if ($params['summary']==AppConstant::DEFAULT_ASSESSMENT_SUMMARY) {
                        $params['summary'] = '';
                    } else {
//                        $params['summary'] = addslashes(htmLawed(stripslashes($params['summary']),$htmlawedconfig));
                    }
                    if ($params['intro']==AppConstant::DEFAULT_ASSESSMENT_INTRO) {
                        $params['intro'] = '';
                    } else {
//                        $params['intro'] = addslashes(htmLawed(stripslashes($params['intro']),$htmlawedconfig));
                    }
                if (isset($params['id'])) {  //already have id; update
                    if ($isGroup==AppConstant::NUMERIC_ZERO) { //set agroupid=0 if switching from groups to non groups
                        $query = $assessmentData['isgroup'];
                        if ($query>AppConstant::NUMERIC_ZERO) {
                            AssessmentSession::setGroupId($assessmentId);
                        }
                    } else { /*if switching from nogroup to groups and groups already exist, need set agroupids if asids exist already
                              *NOT ALLOWED CURRENTLY
                              */
                    }
                    Assessments::updateAssessment($params,$timeLimit,$isGroup,$showHints,$tutorEdit,$defFeedback,$shuffle,$calTag,$calrTag,$defFeedbackText,$isTutorial,$endMsg,$startDate,$endDate,$reviewDate);
                    if ($from=='gb') {
                        return $this->redirect(AppUtility::getURLFromHome('site','work-in-progress?cid='. $courseId));
                    } else if ($from=='mcd') {
                        return $this->redirect(AppUtility::getURLFromHome('site','work-in-progress?cid='. $courseId));
                    } else if ($from=='lti') {
                        return $this->redirect(AppUtility::getURLFromHome('site','work-in-progress?cid='. $courseId));
                    } else {
                        return $this->redirect(AppUtility::getURLFromHome('instructor','instructor/index?cid='. $courseId));
                    }
                } else { //add new
                    if (!isset($params['copyendmsg'])) {
                        $endMsg = '';
                    }
                    $assessment = new Assessments();
                    $newAssessmentId = $assessment->createAssessment($params,$startDate,$endDate,$reviewDate,$timeLimit,$shuffle,$defFeedback,$tutorEdit,$showHints,$endMsg,$defFeedbackText,$isTutorial);
                    $itemAssessment = new Items();
                    $itemId = $itemAssessment->saveItems($courseId,$newAssessmentId,'Assessment');
                    $itemOrder = $course['itemorder'];
                    $items = unserialize($itemOrder);
                    $blockTree = explode('-',$block);
                    $sub =& $items;
                    for ($i=AppConstant::NUMERIC_ONE;$i<count($blockTree);$i++) {
                        $sub =& $sub[$blockTree[$i]-AppConstant::NUMERIC_ONE]['items']; //-1 to adjust for 1-indexing
                    }
                    if ($filter=='b') {
                        $sub[] = $itemId;
                    } else if ($filter=='t') {
                        array_unshift($sub,intval($itemId));
                    }
                    $itemList = addslashes(serialize($items));
                    Course::setItemOrder($itemList,$courseId);
                    return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
                }
            }else {
                if (isset($params['id'])) {//page load in modify mode
                    $title = AppConstant::MODIFY_ASSESSMENT;
                    $pageTitle =  AppConstant::MODIFY_ASSESSMENT;
                    $assessmentSessionData = AssessmentSession::getByUserCourseAssessmentId($assessmentId,$courseId);
                    list($testType,$showAnswer) = explode('-',$assessmentData['deffeedback']);
                    $startDate = $assessmentData['startdate'];
                    $endDate = $assessmentData['enddate'];
                    $gradebookCategory = $assessmentData['gbcategory'];
                    if ($testType=='Practice') {
                        $pointCountInGb = $assessmentData['cntingb'];
                        $countInGb = AppConstant::NUMERIC_ONE;
                    } else {
                        $countInGb = $assessmentData['cntingb'];
                        $pointCountInGb = AppConstant::NUMERIC_THREE;
                    }
                    $showQuestionCategory = $assessmentData['showcat'];
                    $timeLimit = $assessmentData['timelimit']/AppConstant::SECONDS;
                    if ($assessmentData['isgroup']==AppConstant::NUMERIC_ZERO) {
                        $assessmentData['groupsetid']=AppConstant::NUMERIC_ZERO;
                    }
                    if ($assessmentData['deffeedbacktext']=='') {
                        $useDefFeedback = false;
                        $defFeedback = AppConstant::DEFAULT_FEEDBACK;
                    } else {
                        $useDefFeedback = true;
                        $defFeedback = $assessmentData['deffeedbacktext'];
                    }
                    if ($assessmentData['summary']=='') {
                    }
                    if ($assessmentData['intro']=='') {
                    }
                    $saveTitle = AppConstant::SAVE_BUTTON;
                }else {//page load in add mode set default values
                    $title = AppConstant::ADD_ASSESSMENT;
                    $pageTitle =  AppConstant::ADD_ASSESSMENT;
                    $assessmentData['name'] = AppConstant::DEFAULT_ASSESSMENT_NAME;
                    $assessmentData['summary'] = AppConstant::DEFAULT_ASSESSMENT_SUMMARY;
                    $assessmentData['intro'] = AppConstant::DEFAULT_ASSESSMENT_INTRO;
                    $startDate = time()+AppConstant::SECONDS*AppConstant::SECONDS;
                    $endDate = time() + AppConstant::WEEK_TIME;
                    $assessmentData['startdate'] = $startDate;
                    $assessmentData['enddate'] = $endDate;
                    $assessmentData['avail'] = AppConstant::NUMERIC_ONE;
                    $assessmentData['reviewdate'] = AppConstant::NUMERIC_ZERO;
                    $timeLimit = AppConstant::NUMERIC_ZERO;
                    $assessmentData['displaymethod']= "SkipAround";
                    $assessmentData['defpoints'] = AppConstant::NUMERIC_TEN;
                    $assessmentData['defattempts'] = AppConstant::NUMERIC_ONE;
                    $assessmentData['password'] = '';
                    $testType = "AsGo";
                    $showAnswer = "A";
                    $assessmentData['defpenalty'] = AppConstant::NUMERIC_TEN;
                    $assessmentData['shuffle'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['minscore'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['isgroup'] = AppConstant::NUMERIC_ZERO;
                    $assessmentData['showhints']= AppConstant::NUMERIC_ONE;
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
                    $assessmentData['caltag'] = '?';
                    $assessmentData['calrtag'] = 'R';
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
                if ($assessmentData['minscore']>AppConstant::NUMERIC_THOUSAND) {
                    $assessmentData['minscore'] -= AppConstant::NUMERIC_THOUSAND;
                    $minScoreType = AppConstant::NUMERIC_ONE; //pct;
                } else {
                    $minScoreType = AppConstant::NUMERIC_ZERO; //points;
                }
                    $courseDefTime = $course['deftime']%AppConstant::NUMERIC_THOUSAND;
                    $hour = floor($courseDefTime/AppConstant::SECONDS)%AppConstant::NUMERIC_TWELVE;
                    $minutes = $courseDefTime%AppConstant::SECONDS;
                    $am = ($courseDefTime<AppConstant::NUMERIC_TWELVE*AppConstant::SECONDS)?'am':'pm';
                    $defTime = (($hour==AppConstant::NUMERIC_ZERO)?AppConstant::NUMERIC_TWELVE:$hour).':'.(($minutes<AppConstant::NUMERIC_TEN)?'0':'').$minutes.' '.$am;
                    $hour = floor($courseDefTime/AppConstant::SECONDS)%AppConstant::NUMERIC_TWELVE;
                    $minutes = $courseDefTime%AppConstant::SECONDS;
                    $am = ($courseDefTime<AppConstant::NUMERIC_TWELVE*AppConstant::SECONDS)?'am':'pm';
                    $defStartTime = (($hour==AppConstant::NUMERIC_ZERO)?AppConstant::NUMERIC_TWELVE:$hour).':'.(($minutes<AppConstant::NUMERIC_TEN)?'0':'').$minutes.' '.$am;
                if ($startDate!=AppConstant::NUMERIC_ZERO) {
                    $sDate = AppUtility::tzdate("m/d/Y",$startDate);
                    $sTime = AppUtility::tzdate("g:i a",$startDate);
                } else {
                    $sDate = AppUtility::tzdate("m/d/Y",time());
                    $sTime = $defStartTime; //$stime = tzdate("g:i a",time());
                }
                if ($endDate!=AppConstant::ALWAYS_TIME) {
                    $eDate = AppUtility::tzdate("m/d/Y",$endDate);
                    $eTime = AppUtility::tzdate("g:i a",$endDate);
                } else {
                    $eDate = AppUtility::tzdate("m/d/Y",time()+AppConstant::WEEK_TIME);
                    $eTime = $defTime; //tzdate("g:i a",time()+7*24*60*60);
                }
                if ($assessmentData['reviewdate'] > AppConstant::NUMERIC_ZERO) {
                    if ($assessmentData['reviewdate']=='2000000000') {
                        $reviewDate = AppUtility::tzdate("m/d/Y",$assessmentData['enddate']+AppConstant::WEEK_TIME);
                        $reviewTime = $defTime; //tzdate("g:i a",$assessmentData['enddate']+7*24*60*60);
                    } else {
                        $reviewDate = AppUtility::tzdate("m/d/Y",$assessmentData['reviewdate']);
                        $reviewTime = AppUtility::tzdate("g:i a",$assessmentData['reviewdate']);
                    }
                } else {
                    $reviewDate = AppUtility::tzdate("m/d/Y",$assessmentData['enddate']+AppConstant::WEEK_TIME);
                    $reviewTime = $defTime; //tzdate("g:i a",$assessmentData['enddate']+7*24*60*60);
                }
                    if (!isset($params['id'])) {
                        $sTime = $defStartTime;
                        $eTime = $defTime;
                        $reviewTime = $defTime;
                    }
                if ($assessmentData['defpenalty']{AppConstant::NUMERIC_ZERO}==='L') {
                    $assessmentData['defpenalty'] = substr($assessmentData['defpenalty'],AppConstant::NUMERIC_ONE);
                    $skipPenalty=AppConstant::NUMERIC_TEN;
                } else if ($assessmentData['defpenalty']{AppConstant::NUMERIC_ZERO}==='S') {
                    $skipPenalty = $assessmentData['defpenalty']{AppConstant::NUMERIC_ONE};
                    $assessmentData['defpenalty'] = substr($assessmentData['defpenalty'],AppConstant::NUMERIC_TWO);
                } else {
                    $skipPenalty = AppConstant::NUMERIC_ZERO;
                }
                $query = Assessments::getByCourse($courseId);
                $pageCopyFromSelect = array();
                $key=AppConstant::NUMERIC_ZERO;
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
                    foreach($query as $singleData) {
                        $pageOutcomes[$singleData['id']] = $singleData['name'];
                        $key++;
                    }
                }
                $pageOutcomes[0] = AppConstant::DEFAULT_OUTCOMES;
                $pageOutcomesList = array(array(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ZERO));
                if ($key>AppConstant::NUMERIC_ZERO) {//there were outcomes
                    $query = $course['outcomes'];
                    $outcomeArray = unserialize($query);
                    $result = $this->flatArray($outcomeArray);
                    if($result){
                        foreach($result as $singlePage){
                            array_push($pageOutcomesList,$singlePage);
                        }
                    }
                }
                $query = StuGroupSet::getByCourseId($courseId);
                $pageGroupSets = array();
                if ($assessmentSessionData && $assessmentData['isgroup']==AppConstant::NUMERIC_ZERO) {
                    $query = StuGroupSet::getByJoin($courseId);
                } else {
                    $query = StuGroupSet::getByCourseId($courseId);
                }
                $pageGroupSets['val'][0] = AppConstant::NUMERIC_ZERO;
                $pageGroupSets['label'][0] = AppConstant::GROUP_SET;
                foreach ($query as $singleData) {
                    $pageGroupSets['val'][$key] = $singleData['id'];
                    $pageGroupSets['label'][$key] = $singleData['name'];
                    $key++;
                }
                $pageTutorSelect['label'] = array(AppConstant::TUTOR_NO_ACCESS,AppConstant::TUTOR_READ_SCORES,AppConstant::TUTOR_READ_WRITE_SCORES);
                $pageTutorSelect['val'] = array(AppConstant::NUMERIC_TWO,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ONE);
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
                for ($key=AppConstant::NUMERIC_ONE;$key<AppConstant::NUMERIC_NINE;$key++) {
                    $pageAllowLateSelect['val'][] = $key+AppConstant::NUMERIC_ONE;
                    $pageAllowLateSelect['label'][] = "Up to $key";
                }
            }
        }
        $this->includeJS(["editor/tiny_mce.js", "course/assessment.js","general.js","assessment/addAssessment.js"]);
        return $this->renderWithData('addAssessment',['course' => $course,'assessmentData' => $assessmentData,
        'saveTitle'=>$saveTitle, 'pageCopyFromSelect' => $pageCopyFromSelect, 'timeLimit' => $timeLimit,
        'assessmentSessionData' => $assessmentSessionData, 'testType' => $testType,'skipPenalty' => $skipPenalty,
        'showAnswer' => $showAnswer,'startDate' => $startDate,'endDate' => $endDate, 'pageForumSelect' => $pageForumSelect,
        'pageAllowLateSelect' => $pageAllowLateSelect,'pageGradebookCategorySelect' => $pageGradebookCategorySelect,
        'gradebookCategory'=> $gradebookCategory, 'countInGradebook' => $countInGb, 'pointCountInGradebook' => $pointCountInGb,
        'pageTutorSelect' => $pageTutorSelect, 'minScoreType' => $minScoreType, 'useDefFeedback' => $useDefFeedback,
        'defFeedback' => $defFeedback, 'pageGroupSets' => $pageGroupSets,'pageOutcomesList' => $pageOutcomesList,
        'pageOutcomes' => $pageOutcomes, 'showQuestionCategory' => $showQuestionCategory,'sDate' => $sDate,
        'sTime' => $sTime, 'eDate' => $eDate, 'eTime' => $eTime, 'reviewDate' => $reviewDate, 'reviewTime' => $reviewTime,
        'startDate' => $startDate, 'endDate' => $endDate, 'title' => $title, 'pageTitle' => $pageTitle]);
    }

    public function flatArray($outcomesData) {
        global $pageOutcomesList;
        if($outcomesData){
            foreach ($outcomesData as $singleData) {
                if (is_array($singleData)) { //outcome group
                    $pageOutcomesList[] = array($singleData['name'], AppConstant::NUMERIC_ONE);
                    $this->flatArray($singleData['outcomes']);
                } else {
                    $pageOutcomesList[] = array($singleData, AppConstant::NUMERIC_ZERO);
                }
            }
        }
        return $pageOutcomesList;
    }
}