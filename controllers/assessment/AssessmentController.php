<?php

namespace app\controllers\assessment;

use app\components\AppUtility;
use app\controllers\AppController;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Exceptions;
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
        $to = isset($params['to']) ? trim($params['to']) : 0;
        $courseId = isset($params['id']) ? trim($params['cid']) : "";
        $assessment = Assessments::getByAssessmentId($assessmentId);
        $teacher = Teacher::getByUserId($user->getId(), $courseId);
        $assessmentSession = AssessmentSession::getAssessmentSession($user->id, $assessmentId);
        if(!$assessmentSession)
        {
            $assessmentSessionObject = new AssessmentSession();
            $assessmentSession = $assessmentSessionObject->saveAssessmentSession($assessment, $user->getId());
        }

        $response = AppUtility::showAssessment($user, $params, $assessmentId, $courseId, $assessment, $assessmentSession, $teacher, $to);

        $this->includeCSS(['showAssessment.css', 'mathtest.css']);
        $this->getView()->registerJs('var imasroot="openmath/";');
        $this->includeJS(['timer.js', 'ASCIIMathTeXImg_min.js', 'general.js', 'eqntips.js', 'editor/tiny_mce.js']);
        $responseData = array('response'=> $response);
        return $this->render('ShowAssessment', $responseData);

    }

    public function actionLatePass()
    {
        $this->guestUserHandler();
        $assessmentId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $studentId = $this->getUserId();
        $exception = Exceptions::getByAssessmentId($assessmentId);

        $assessment = Assessments::getByAssessmentId($assessmentId);
        $student = Student::getByCourseId($courseId, $studentId);
        $course = Course::getById($courseId);

        $addtime = $course->latepasshrs * 60 * 60;
        $currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
        $usedlatepasses = round(($assessment->allowlate - $assessment->enddate)/($course->latepasshrs * 3600));
        $startdate = $assessment->startdate;
        $enddate = $assessment->enddate + $addtime;
        $wave = 0;

        $param['assessmentid'] = $assessmentId;
        $param['userid'] = $studentId;
        $param['startdate'] = $startdate;
        $param['enddate'] = $enddate;
        $param['waivereqscore'] = $wave;
        $param['islatepass'] = 1;

        if(count($exception))
        {
            if ((($assessment->allowlate % 10) == 1 || ($assessment->allowlate % 10) - 1 > $usedlatepasses) && ($currentTime < $exception->enddate || ($assessment->allowlate > 10 && ($currentTime - $exception->enddate)< $course->latepasshrs * 3600)))
            {
                $latepass = $student->latepass;
                $student->latepass = $latepass - 1;
                $exception->enddate = $exception->enddate + $addtime;
                $exception->islatepass = $exception->islatepass + 1;
            }

            if($exception->islatepass != 0)
            {
                echo "<p>Un-use late-pass</p>";

                if ($currentTime > $assessment->enddate && $exception->enddate < $currentTime + $course->latepasshrs * 60 * 60)
                {

                    echo '<p>Too late to un-use this LatePass</p>';
                }
                else {
                    if ($currentTime < $assessment->enddate)
                    {
                        $exception->islatepass = $exception->islatepass - 1;
                    }
                    else {
                        //figure how many are unused
                        $n = floor(($exception->enddate - $currentTime)/($course->latepasshrs * 60 * 60));
                        $newend = $exception->enddate - $n * $course->latepasshrs * 60 * 60;
                        if ($exception->islatepass > $n)
                        {
                            $exception->islatepass = $exception->islatepass - $n;
                            $exception->enddate = $newend;
                        } else {
                            //dnt push anything into db.
                        }
                    }
                    echo "<p>Returning $n LatePass".($n > 1 ? "es":"")."</p>";
                    $student->latepass = $student->latepass + $n;
                }
            }
            else
            {
                echo '<p>Invalid</p>';
            }
            $exception->attributes = $param;
            $exception->save();
            $student->save();
        }
        $this->includeJS(['../js/latePass.js']);
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
} 