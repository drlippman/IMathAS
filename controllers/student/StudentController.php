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
    public function actionIndex()
    {
        AppUtility::dump('hello');
    }
    public function actionStudentEnrollCourse()
    {
        $model = new StudentEnrollCourseForm();

        if ($model->load(\Yii::$app->request->post())) {
            $param = \Yii::$app->request->getBodyParams();
            $param = $param['StudentEnrollCourseForm'];

            $course = Course::getByIdAndEnrollmentKey($param['courseId'], $param['enrollmentKey']);
            if ($course) {
                $teacher = Teacher::getByUserId(\Yii::$app->user->identity->id, $param['courseId']);
                $tutor = Tutor::getByUserId(\Yii::$app->user->identity->id, $param['courseId']);
                $alreadyEnroll = Student::getByCourseId($param['courseId'], \Yii::$app->user->identity->id);

                if (!$teacher && !$tutor && !$alreadyEnroll) {
                    $param['userid'] = \Yii::$app->user->identity->id;
                    $param['courseid'] = $param['courseId'];
                    $param = AppUtility::removeEmptyAttributes($param);
                    $student = new Student();
                    $student->create($param);

                    \Yii::$app->session->setFlash('success', 'You have been enrolled in course ' . $course->name . ' successfully');
                } else {
                    if ($teacher) {
                        \Yii::$app->session->setFlash('danger', 'You are a teacher for this course, and can not enroll as a student.Use Student View to see the class from a student perspective, or create a dummy student account.');
                    } elseif ($tutor) {
                        \Yii::$app->session->setFlash('danger', 'You are a tutor for this course, and can not enroll as a student.');
                    } else {
                        \Yii::$app->session->setFlash('danger', 'You are already enrolled in the course.');
                    }
                }
            } else {
                \Yii::$app->session->setFlash('danger', 'Invalid combination of enrollment key and course id.');
            }
        }
        return $this->render('studentEnrollCourse', ['model' => $model]);
    }

} 