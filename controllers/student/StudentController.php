<?php

namespace app\controllers\student;

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
        $this->guestUserHandler();
        $model = new StudentEnrollCourseForm();
        if ($model->load($this->isPostMethod())) {
            $param = $this->getRequestParams();
            $param = $param['StudentEnrollCourseForm'];
            $user = $this->getAuthenticatedUser();

            $course = Course::getByIdAndEnrollmentKey($param['courseId'], $param['enrollmentKey']);
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

                    $this->setSuccessFlash('You have been enrolled in course ' . $course->name . ' successfully');
                } else {
                    $errorMessage = 'You are already enrolled in the course.';
                    if ($teacher) {
                        $errorMessage = 'You are a teacher for this course, and can not enroll as a student.Use Student View to see the class from a student perspective, or create a dummy student account.';
                    } elseif ($tutor) {
                        $errorMessage = 'You are a tutor for this course, and can not enroll as a student.';
                    }
                    $this->setErrorFlash($errorMessage);
                }
            } else {
                $this->setErrorFlash('Invalid combination of enrollment key and course id.');
            }
        }
        $responseData = array('model' => $model);
        return $this->renderWithData('studentEnrollCourse', $responseData);
    }
}