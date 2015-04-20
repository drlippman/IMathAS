<?php

namespace app\controllers\course;

use app\components\AppUtility;
use Yii;
use app\controllers\AppController;
use yii\web\Controller;

class CourseController extends AppController
{
    public function actionIndex()
    {
        AppUtility::dump('hello');
    }

}