<?php

namespace app\controllers;

use app\models\CourseSettingForm;
use Yii;
use yii\web\Controller;
use app\models\AddNewUserForm;
use app\components\AppUtility;
use app\models\User;
use app\components\AppConstant;
use app\models\AdminDiagnosticForm;

class AdminController extends AppController
{
    public function actionIndex()
    {
        $sortBy = 'FirstName';
        $order = AppConstant::ASCENDING;
       $users = User::findAllUser($sortBy, $order);

        $sortBy = 'name';
        $order = AppConstant::ASCENDING;
        $courseData = CourseSettingForm::findCourseData($sortBy, $order);

        $this->includeCSS(['css/dashboard.css']);
       return $this->render('index',array('users' => $users, 'courseData' => $courseData));
    }

    public function actionAddNewUser()
    {
        $model = new AddNewUserForm();
        if ($model->load(Yii::$app->request->post())){

            $params = $this->getBodyParams();
            $params = $params['AddNewUserForm'];
            $params['SID'] = $params['username'];
            $params['hideonpostswidget'] = AppConstant::ZERO_VALUE;
            $params['password'] = AppUtility::passwordHash($params['password']);

            $user = new User();
            $user->attributes = $params;
            $user->save();

            $this->setSuccessFlash(AppConstant::ADD_NEW_USER);
        }
        return $this->render('addNewUser', ['model' => $model,]);
    }

    public function actionAdminDiagnostic()
    {
        $model = new AdminDiagnosticForm();

        if ($model->load(Yii::$app->request->post()))
        {
            $params = Yii::$app->request->getBodyParams();

            $params = $params['AdminDiagnosticForm'];
            $params['ownerid'] = Yii::$app->user->identity->SID;
            $params['name'] = $params['DiagnosticName'];
            $params['term'] = $params['TermDesignator'];
            $diag = new BaseImasDiags();
            $diag->attributes = $params;
            $diag->save();
        }
        return $this->render('adminDiagnostic',['model'=>$model]);
    }

}