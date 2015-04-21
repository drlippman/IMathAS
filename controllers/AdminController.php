<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\AddNewUserForm;
use app\components\AppUtility;
use app\models\User;
use app\components\AppConstant;

class AdminController extends AppController
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionAddNewUser()
    {
        $model = new AddNewUserForm();
        if ($model->load(Yii::$app->request->post())){
            $params = Yii::$app->request->getBodyParams();
            $params = $params['AddNewUserForm'];
            $params['SID'] = $params['username'];
            $params['hideonpostswidget'] = AppConstant::ZERO_VALUE;
            $params['password'] = AppUtility::passwordHash($params['password']);
            $user = new User();
            $user->attributes = $params;
            $user->save();
            Yii::$app->session->setFlash('success', AppConstant::ADD_NEW_USER);
        }
        return $this->render('addNewUser', ['model' => $model,]);
    }

}