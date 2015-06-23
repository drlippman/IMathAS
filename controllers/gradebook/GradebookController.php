<?php

namespace app\controllers\gradebook;


use app\components\AppUtility;
use app\models\Course;
use app\models\forms\ManageTutorsForm;
use app\models\loginTime;
use app\models\Student;
use Yii;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;

class GradebookController extends AppController
{
    public function actionGradebook()
    {
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $sections = Student::findDistinctSection($courseId);
        $this->includeJS(['gradebook/gradebook.js']);
        $responseData = array('course' => $course, 'sections' => $sections);
        return $this->renderWithData('gradebook', $responseData);
    }
    public function actionDisplayGradebookAjax()
    {
        $params = $this->getRequestParams();
        $query = Student::findByCid($params['courseId']);
        return $this->successResponse();
    }
}