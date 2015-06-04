<?php

namespace app\controllers\instructor;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\Course;
use Yii;
use app\controllers\AppController;


class InstructorController extends AppController
{

    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $this->includeJS(['../js/course.js']);
        $this->includeCSS(['../css/_leftSide.css']);
        return $this->render('index', ['course' => $course]);
    }
}