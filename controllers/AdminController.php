<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class AdminController extends AppController
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}