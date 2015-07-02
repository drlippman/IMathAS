<?php

namespace app\controllers\assessment;

use app\components\AppUtility;
use app\controllers\AppController;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Exceptions;

use app\models\Forums;
use app\models\GbCats;
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
        $to = isset($params['to']) ? $params['to'] : 0;
        $courseId = isset($params['cid']) ? trim($params['cid']) : "";
        $assessment = Assessments::getByAssessmentId($assessmentId);
        $teacher = Teacher::getByUserId($user->getId(), $courseId);
        $assessmentSession = AssessmentSession::getAssessmentSession($user->id, $assessmentId);
        if(!$assessmentSession)
        {
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
        $wave = 0;

        $param['assessmentid'] = $assessmentId;
        $param['userid'] = $studentId;
        $param['startdate'] = $startdate;
        $param['enddate'] = $enddate;
        $param['waivereqscore'] = $wave;
        $latepass = $student->latepass;
        $student->latepass = $latepass - 1;
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
        if ($this->isPostMethod())
        {
            $params = $this->getRequestParams();
            $password = $params['SetPassword']['password'];
            if($password == $assessment->password)
            {
                return $this->redirect(AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id));
            }
            else
            {
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
        if($user)
        {
            $assessmentId = $this->getParam('aid');
            $assessmentSession = AssessmentSession::getAssessmentSession($user->id, $assessmentId);
            if($assessmentSession)
            {
                $courseId = $assessmentSession->assessment->course->id;
                $teacher = Teacher::getByUserId($user->id, $courseId);
                if($teacher)
                {
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
        $course = Course::getById($courseId);
        $assessmentId = $params['id'];
        if($assessmentId) {
            $assessmentData = Assessments::getByAssessmentId($assessmentId);
            if (isset($params['id'])) {
                $assessmentSessionData = AssessmentSession::getByUserCourseAssessmentId($assessmentId,$courseId,$user);
                list($testType,$showAnswer) = explode('-',$assessmentData['deffeedback']);
                $startDate = $assessmentData['startdate'];
                $endDate = $assessmentData['enddate'];
                $gradebookCategory = $assessmentData['gbcategory'];
                if ($testType=='Practice') {
                    $pointCountInGb = $assessmentData['cntingb'];
                    $CountInGb = AppConstant::NUMERIC_ONE;
                } else {
                    $CountInGb = $assessmentData['cntingb'];
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
            }else{//page load in add mode set default values
            }
            $now = time();
            if ($assessmentData['minscore']>AppConstant::NUMERIC_THOUSAND) {
                $assessmentData['minscore'] -= AppConstant::NUMERIC_THOUSAND;
                $minScoreType = AppConstant::NUMERIC_ONE; //pct;
            } else {
                $minScoreType = AppConstant::NUMERIC_ZERO; //points;
            }
            if ($assessmentData['reviewdate'] > AppConstant::NUMERIC_ZERO) {
                if ($assessmentData['reviewdate']=='2000000000') {
                    $reviewDate = AppUtility::tzdate("m/d/Y",$assessmentData['enddate']+AppConstant::WEEK_TIME);
                    $reviewTime = $now; //tzdate("g:i a",$line['enddate']+7*24*60*60);
                } else {
                    $reviewDate = AppUtility::tzdate("m/d/Y",$assessmentData['reviewdate']);
                    $reviewTime = AppUtility::tzdate("g:i a",$assessmentData['reviewdate']);
                }
            } else {
                $reviewDate = AppUtility::tzdate("m/d/Y",$assessmentData['enddate']+AppConstant::WEEK_TIME);
                $reviewTime = $now; //tzdate("g:i a",$line['enddate']+7*24*60*60);
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

            $query = Outcomes::getByCourseId($courseId);
            $pageOutcomes = array();
            if ($query) {
                foreach($query as $singleData) {
                    $pageOutcomes[$singleData['id']] = $singleData['name'];
                    $key++;
                }
            }
            $pageOutcomes[0] = AppConstant::DEFAULT_OUTCOMES;
/////////////////////////////////////////////////////////////////////////////////////////////////////////
            $pageOutcomesList = array(array(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ZERO));
            if ($key>AppConstant::NUMERIC_ZERO) {//there were outcomes
                $query = $course['outcomes'];
                $outcomeArray = unserialize($query);
                $result = $this->flatArray($outcomeArray);
                foreach($result as $singlePage){
                        array_push($pageOutcomesList,$singlePage);
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
        $this->includeJS(["editor/tiny_mce.js", "course/assessment.js","general.js","assessment/addAssessment.js"]);
        return $this->renderWithData('addAssessment',['course' => $course,'assessmentData' => $assessmentData,
        'saveTitle'=>$saveTitle, 'pageCopyFromSelect' => $pageCopyFromSelect, 'timeLimit' => $timeLimit,
        'assessmentSessionData' => $assessmentSessionData, 'testType' => $testType,'skipPenalty' => $skipPenalty,
        'showAnswer' => $showAnswer,'startDate' => $startDate,'endDate' => $endDate, 'pageForumSelect' => $pageForumSelect,
        'pageAllowLateSelect' => $pageAllowLateSelect,'pageGradebookCategorySelect' => $pageGradebookCategorySelect,
        'gradebookCategory'=> $gradebookCategory, 'countInGradebook' => $CountInGb, 'pointCountInGradebook' => $pointCountInGb,
        'pageTutorSelect' => $pageTutorSelect, 'minScoreType' => $minScoreType, 'useDefFeedback' => $useDefFeedback,
        'defFeedback' => $defFeedback, 'pageGroupSets' => $pageGroupSets,'pageOutcomesList' => $pageOutcomesList,
        'pageOutcomes' => $pageOutcomes, 'showQuestionCategory' => $showQuestionCategory]);
    }

    public function flatArray($outcomesData) {
        global $pageOutcomesList;
        foreach ($outcomesData as $singleData) {
            if (is_array($singleData)) { //outcome group
                $pageOutcomesList[] = array($singleData['name'], AppConstant::NUMERIC_ONE);
                $this->flatArray($singleData['outcomes']);
            } else {
                $pageOutcomesList[] = array($singleData, AppConstant::NUMERIC_ZERO);
            }
        }
        return $pageOutcomesList;
    }
}