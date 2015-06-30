<?php

namespace app\controllers\assessment;

use app\components\AppUtility;
use app\controllers\AppController;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Exceptions;

use app\models\Questions;

use app\models\SetPassword;
use app\models\Student;
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
        if (isset($assessmentId)) {
            $assessmentSessionData = AssessmentSession::getByUserCourseAssessmentId($assessmentId,$courseId,$user);
            $assessmentData = Assessments::getByAssessmentId($assessmentId);
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
            $timeLimit = $assessmentData['timelimit']/60;
            if ($assessmentData['isgroup']==0) {
                $assessmentData['groupsetid']=0;
            }
            if ($assessmentData['deffeedbacktext']=='') {
                $usedEfFeedback = false;
                $defFeedback = "This assessment contains items that are not automatically graded.  Your grade may be inaccurate until your instructor grades these items.";
            } else {
                $usedEfFeedback = true;
                $defFeedback = $assessmentData['deffeedbacktext'];
            }
            if ($assessmentData['summary']=='') {
                //	$line['summary'] = "<p>Enter summary here (shows on course page)</p>";
            }
            if ($assessmentData['intro']=='') {
                //	$line['intro'] = "<p>Enter intro/instructions</p>";
            }
            $savetitle = "Save Changes";
        }else{//page load in add mode set default values

        }
        $now = time();
        if ($assessmentData['minscore']>10000) {
            $assessmentData['minscore'] -= 10000;
            $minScoreType = AppConstant::NUMERIC_ONE; //pct;
        } else {
            $minScoreType = AppConstant::NUMERIC_ZERO; //points;
        }
        if ($assessmentData['reviewdate'] > 0) {
            if ($assessmentData['reviewdate']=='2000000000') {
                $reviewDate = tzdate("m/d/Y",$assessmentData['enddate']+7*24*60*60);
                $reviewTime = $now; //tzdate("g:i a",$line['enddate']+7*24*60*60);
            } else {
                $reviewDate = tzdate("m/d/Y",$assessmentData['reviewdate']);
                $reviewTime = tzdate("g:i a",$assessmentData['reviewdate']);
            }
        } else {
            $reviewDate = tzdate("m/d/Y",$assessmentData['enddate']+7*24*60*60);
            $reviewTime = $now; //tzdate("g:i a",$line['enddate']+7*24*60*60);
        }

        $this->includeJS(["editor/tiny_mce.js", "course/assessment.js","general.js"]);
        return $this->renderWithData('addAssessment',['course' => $course]);
    }
} 