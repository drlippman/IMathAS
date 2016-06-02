<?php

namespace app\controllers;
use app\models\abeer;


class AbeerController extends \yii\web\Controller
{
    public function actionIndex()
    {

        $model= new abeer();
        return $this->render('abeer',['model'=> $model]);
    }

}
