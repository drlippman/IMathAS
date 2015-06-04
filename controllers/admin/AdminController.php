<?php

namespace app\controllers\admin;

use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use app\models\_base\BaseImasDiags;
use app\models\Course;
use app\models\forms\ChangeRightsForm;
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
        $this->guestUserHandler();
        $sortBy = 'FirstName';
        $order = AppConstant::ASCENDING;
        $users = User::findAllUser($sortBy, $order);
        $this->includeCSS(['../css/dashboard.css']);
        return $this->renderWithData('index', ['users' => $users]);
    }

    public function actionAddNewUser()
    {
        $this->guestUserHandler();
        $model = new AddNewUserForm();
        if ($model->load(Yii::$app->request->post())){
            $params = $this->getBodyParams();
            $params = $params['AddNewUserForm'];
            $params['SID'] = $params['username'];
            $params['hideonpostswidget'] = AppConstant::ZERO_VALUE;
            $params['password'] = AppUtility::passwordHash($params['password']);

           $user = new User();
            $model = new AddNewUserForm();
            $user->attributes = $params;
            $user->save();
            $this->setSuccessFlash(AppConstant::ADD_NEW_USER);
        }
         $this->includeJS(["../js/courseSetting.js"]);
        return $this->renderWithData('addNewUser', ['model' => $model,]);
    }

    public function actionAdminDiagnostic()
    {
            $this->guestUserHandler();
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
            return $this->renderWithData('adminDiagnostic',['model'=>$model]);
    }


    public function actionGetAllCourseUserAjax()
    {
        $sortBy = 'FirstName';
        $order = AppConstant::ASCENDING;
        $courseData = Course::findCourseDataArray();
        $user = User::findAllUsersArray($sortBy, $order);

        return json_encode(array('status' => AppConstant::RETURN_SUCCESS, 'courses' => $courseData, 'users' => $user));
    }

    public function actionChangeRights()
    {
        $id = $this->getParamVal('id');
        $this->guestUserHandler();
        $model = new ChangeRightsForm();
        if ($model->load(Yii::$app->request->post())) {
            $params = $this->getBodyParams();
            $params = $params['ChangeRightsForm'];
            User::updateRights($id, $params['rights'], $params['groupid']);
            $this->setSuccessFlash('User confirmed successfully.');
            return $this->redirect(AppUtility::getURLFromHome('admin','admin/index'));
        }
        return $this->renderWithData('changeRights', ['model' => $model]);
    }

    public function actionHelpOfRights()
    {
        return $this->renderWithData('helpOfRights');
    }

}