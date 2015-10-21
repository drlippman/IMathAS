<?php

namespace app\controllers\student;

use app\components\AppConstant;
use app\components\AppUtility;
use app\controllers\AppController;
use app\models\Course;
use app\models\forms\StudentEnrollCourseForm;
use app\models\Student;
use app\models\Teacher;
use app\models\Tutor;


class StudentController extends AppController
{
    public function actionStudentEnrollCourse()
    {
        $user = $this->getAuthenticatedUser();
        if($user['rights'] == AppConstant::GUEST_RIGHT)
        {
            $this->setWarningFlash("Guest user can't access this page.");
            return $this->redirect($this->goHome());
        }
        $this->guestUserHandler();
        $this->layout = 'master';
        $model = new StudentEnrollCourseForm();
        if ($model->load($this->isPostMethod())) {
            $param = $this->getRequestParams();
            $param = $param['StudentEnrollCourseForm'];
            $course = Course::getEnrollData($param['courseId']);
            if ($course) {
                $teacher = Teacher::getByUserId($user->id, $param['courseId']);
                $tutor = Tutor::getByUserId($user->id, $param['courseId']);
                $alreadyEnroll = Student::getByCourseId($param['courseId'], $user->id);
                if (!$teacher && !$tutor && !$alreadyEnroll) {
                    $param['userid'] = $user->id;
                    $param['courseid'] = $param['courseId'];
                    $param = AppUtility::removeEmptyAttributes($param);
                    $student = new Student();
                    $student->create($param);
                    $this->setSuccessFlash(AppConstant::ENROLL_SUCCESS . $course->name . ' successfully');
                } else {
                    $errorMessage = AppConstant::ALREADY_ENROLLED;
                    if ($teacher) {
                        $errorMessage = AppConstant::TEACHER_CANNOT_ENROLL_AS_STUDENT;
                    } elseif ($tutor) {
                        $errorMessage = AppConstant::TUTOR_CANNOT_ENROLL_AS_STUDENT;
                    }
                    $this->setErrorFlash($errorMessage);
                }
            } elseif($course == null){
                $this->setErrorFlash(AppConstant::COURSE_NOT_FOUND);
            }elseif(($course['allowunenroll']&2)==2){
                $this->setErrorFlash(AppConstant::CLOSED_FOR_SELF_ENROLL);
            } else {
                $this->setErrorFlash(AppConstant::INVALID_COMBINATION);
            }
        }
        $responseData = array('model' => $model);
        return $this->renderWithData('studentEnrollCourse', $responseData);
    }
}