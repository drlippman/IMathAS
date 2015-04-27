<?php

namespace app\controllers\admin;

use app\controllers\AppController;
use app\models\_base\BaseImasDiags;
use app\models\forms\CourseSettingForm;
use Yii;
use app\models\forms\AddNewUserForm;
use app\components\AppUtility;
use app\models\User;
use app\components\AppConstant;
use app\models\forms\AdminDiagnosticForm;

class AdminController extends AppController
{
    public function actionIndex()
    {
        if(!$this->isGuestUser())
        {
            $sortBy = 'FirstName';
            $order = AppConstant::ASCENDING;
            $users = User::findAllUser($sortBy, $order);

            $sortBy = 'name';
            $order = AppConstant::ASCENDING;
            $courseData = CourseSettingForm::findCourseData($sortBy, $order);

            $this->includeCSS(['../css/dashboard.css']);
            return $this->render('index',array('users' => $users, 'courseData' => $courseData));
        }else{
            return $this->redirect(Yii::$app->homeUrl.'site/login');
        }
    }

    public function actionAddNewUser()
    {
        if(!$this->isGuestUser())
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
        }else{
            return $this->redirect(Yii::$app->homeUrl.'site/login');
        }
    }

    public function actionAdminDiagnostic()
    {
        if(!$this->isGuestUser())
        {
            $model = new AdminDiagnosticForm();

            if ($model->load(Yii::$app->request->post()))
            {
                $params = $this->getBodyParams();
                $user = $this->getAuthenticatedUser();

                $params = $params['AdminDiagnosticForm'];
                $params['ownerid'] = $user->SID;
                $params['name'] = $params['DiagnosticName'];
                $params['term'] = $params['TermDesignator'];
                $diag = new BaseImasDiags();
                $diag->attributes = $params;
                $diag->save();
            }
            return $this->render('adminDiagnostic',['model'=>$model]);
        }else{
            return $this->redirect(Yii::$app->homeUrl.'site/login');
        }
    }
}