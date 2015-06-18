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
        $responseData = array('response'=> $response,'isQuestions' =>$isQuestions,'courseId' => $courseId,'now' => time(),'assessment' => $assessment ,'assessmentSession' => $assessmentSession,'isShowExpiredTime' =>$to);

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
} 