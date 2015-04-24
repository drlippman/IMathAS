<?php

namespace app\controllers\course;

use app\components\AppUtility;
use Yii;
use app\controllers\AppController;


class CourseController extends AppController
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionToolbar()
    {
        return $this->render('_toolbar');
    }

}