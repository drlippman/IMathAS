<?php

namespace app\controllers;

use app\components\AppConstant;
use app\models\forms\ChangeUserInfoForm;
use app\models\forms\DiagnosticForm;
use app\models\forms\ForgotPasswordForm;
use app\models\forms\ForgotUsernameForm;
use app\models\forms\LoginForm;
use app\models\forms\RegistrationForm;
use app\models\forms\ResetPasswordForm;
use app\models\Student;
use app\models\forms\StudentEnrollCourseForm;
use app\models\forms\StudentRegisterForm;
use app\models\Teacher;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\components\AppUtility;
use app\models\forms\ChangePasswordForm;
use app\models\forms\MessageForm;
use yii\web\UploadedFile;

class SiteController extends AppController
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        if (!$this->isGuestUser()) {
            return $this->redirect('site/dashboard');
        } else {
            return $this->renderWithData('index');
        }
    }

    public function actionLogin()
    {
        $this->unauthorizedAccessHandler();

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->login()) {
                return $this->redirect('dashboard');
            } else {
                $this->setErrorFlash(AppConstant::INVALID_USERNAME_PASSWORD);
            }
        }
        $challenge = AppUtility::getChallenge();

        $this->includeJS(['js/jstz_min.js', 'js/login.js']);

        return $this->renderWithData('login', [
            'model' => $model, 'challenge' => $challenge,
        ]);
    }

    /**
     * @return string
     * Controller for about us page
     */
    public function actionAbout()
    {
        return $this->renderWithData('about');
    }

    /**
     * @return string
     * Instructor registration controller
     */
    public function actionRegistration()
    {
        $model = new RegistrationForm();
        if ($model->load(Yii::$app->request->post())) {

            $params = $this->getBodyParams();
            $params = $params['RegistrationForm'];
            $params['SID'] = $params['username'];
            $params['hideonpostswidget'] = AppConstant::ZERO_VALUE;
            $params['password'] = AppUtility::passwordHash($params['password']);

            $user = new User();
            $user->attributes = $params;
            $user->save();

            $toEmail = $user->email;
            $message = 'First Name: ' . $user->FirstName . "<br/>\n";
            $message .= 'Last Name: ' . $user->LastName . "<br/>\n";
            $message .= 'Email Name: ' . $user->email . "<br/>\n";
            $message .= 'User Name: ' . $user->SID . "<br/>\n";
            AppUtility::sendMail(AppConstant::INSTRUCTOR_REQUEST_MAIL_SUBJECT, $message, $toEmail);
            $this->setSuccessFlash(AppConstant::INSTRUCTOR_REQUEST_SUCCESS);
            return $this->redirect(AppUtility::getURLFromHome('site','registration'));
        }
        return $this->renderWithData('registration', [
            'model' => $model,
        ]);
    }

    public function actionStudentRegister()
    {

        $flashMsg ='';
        $model = new StudentRegisterForm();
        if ($model->load(Yii::$app->request->post())) {
            $params = $this->getBodyParams();
            $params = $params['StudentRegisterForm'];
            $status = User::createStudentAccount($params);
            if ($status) {
                $message = 'First Name: ' . $params['FirstName'] . "<br/>\n";
                $message .= 'Last Name: ' . $params['LastName'] . "<br/>\n";
                $message .= 'Email Name: ' . $params['email'] . "<br/>\n";
                $message .= 'User Name: ' . $params['username'] . "<br/>\n";
                AppUtility::sendMail(AppConstant::STUDENT_REQUEST_MAIL_SUBJECT, $message, $params['email']);
                $this->setSuccessFlash('Account created successfully, please login to get into system.');
                return $this->redirect(AppUtility::getURLFromHome('site','login'));
            }
            $this->setErrorFlash('Error occurred, try again later.');
        }
        return $this->renderWithData('studentRegister', ['model' => $model,]);
    }

    /**
     * Method that redirects to a generic work in progress page
     * @return string
     */
    public function actionWorkInProgress()
    {
        return $this->renderWithData('progress');
    }

    public function actionDiagnostic()
    {
        $model = new DiagnosticForm();
        return $this->renderWithData('diagnostic', ['model' => $model]);
    }


    public function actionForgotPassword()
    {
        $model = new ForgotPasswordForm();
        if ($model->load(Yii::$app->request->post())) {
            $param = $this->getBodyParams();
            $username = $param['ForgotPasswordForm']['username'];

            $user = User::findByUsername($username);
            $code = AppUtility::generateRandomString();
            $user->remoteaccess = $code;
            $user->save();

            $toEmail = $user->email;
            $id = $user->id;

            $message = "<h4>This is an automated message from OpenMath.  Do not respond to this email</h4>\r\n";
            $message .= "<p>Your username was entered in the Reset Password page.  If you did not do this, you may ignore and delete this message. ";
            $message .= "If you did request a password reset, click the link below, or copy and paste it into your browser's address bar.  You ";
            $message .= "will then be prompted to choose a new password.</p>";
            $message .= "<a href=\"" . AppUtility::urlMode() . $_SERVER['HTTP_HOST'] . Yii::$app->homeUrl . "site/reset-password?id=$id&code=$code\">";
            $message .= AppUtility::urlMode() . $_SERVER['HTTP_HOST'] . Yii::$app->homeUrl . "site/reset-password?id=$id&code=$code</a>\r\n";

            AppUtility::sendMail(AppConstant::FORGOT_PASS_MAIL_SUBJECT, $message, $toEmail);
            $model = new ForgotPasswordForm();
            $this->setSuccessFlash('Password reset link sent to your registered email.');
        }

        return $this->renderWithData('forgotPassword', ['model' => $model,]);
    }

    public function actionForgotUsername()
    {
        $model = new ForgotUsernameForm();
        if ($model->load(Yii::$app->request->post())) {
            $param = $this->getBodyParams();
            $toEmail = $param['ForgotUsernameForm']['email'];

            $user = User::findByEmail($toEmail);
            if ($user) {
                $message = "<h4>This is an automated message from OpenMath.  Do not respond to this email</h4>";
                $message .= "<p>Your email was entered in the Username Lookup page on OpenMath.  If you did not do this, you may ignore and delete this message.  ";
                $message .= "All usernames using this email address are listed below</p><p>";
                $message .= "Username: <b>" . $user->SID . " </b> <br/>.";
                AppUtility::sendMail(AppConstant::FORGOT_USER_MAIL_SUBJECT, $message, $toEmail);
                $this->setSuccessFlash('Username sent to your registered email.');
            } else {
                $this->setErrorFlash(AppConstant::INVALID_EMAIL);
            }
        }
        return $this->renderWithData('forgotUsername', ['model' => $model,]);
    }

    public function actionCheckBrowser()
    {
        return $this->renderWithData('checkBrowser');

    }


    public function actionResetPassword()
    {
        $id = Yii::$app->request->get('id');
        $code = Yii::$app->request->get('code');
        $model = new ResetPasswordForm();
        $user = User::getByIdAndCode($id, $code);

        if($user)
        {
            if(Yii::$app->request->post())
            {
                $params = $this->getBodyParams();
                $newPassword = $params['ResetPasswordForm']['newPassword'];
                $password = AppUtility::passwordHash($newPassword);
                $user->password = $password;
                $user->remoteaccess = null;
                $user->save();
                $this->setSuccessFlash('Your password is changed successfully.');
            }

        }else{
            $this->setErrorFlash('Reset Link has been expired.');
        }

        return $this->renderWithData('resetPassword', ['model' => $model]);
    }


    //////////////////////////////////////////////////////////////
    ////////////////// Logged in user functions //////////////////
    //////////////////////////////////////////////////////////////

    public function actionLogout()
    {
        if ($this->getAuthenticatedUser()) {
            Yii::$app->user->logout();
            return $this->goHome();
        }
    }


    public function actionDashboard()
    {
        if (!$this->isGuestUser()) {
            $user = $this->getAuthenticatedUser();
            $students = Student::getByUserId($user->id);
            $teachers = Teacher::getTeacherByUserId($user->id);
            if ($user) {
                $this->includeCSS(['css/dashboard.css']);
                $this->getView()->registerJs('var usingASCIISvg = true;');
                $this->includeJS(["js/dashboard.js", "js/ASCIIsvg_min.js", "js/tablesorter.js"]);

                $userData = ['user' => $user, 'students' => $students, 'teachers' => $teachers];
                return $this->renderWithData('dashboard', $userData);
            }
        }

        $this->setErrorFlash(AppConstant::LOGIN_FIRST);
        return $this->redirect('login');
    }

    public function actionChangePassword()
    {
        $this->guestUserHandler();
        $model = new ChangePasswordForm();
        if ($model->load(Yii::$app->request->post())) {
            $param = $this->getBodyParams();

            $oldPass = $param['ChangePasswordForm']['oldPassword'];
            $newPass = $param['ChangePasswordForm']['newPassword'];

            $user = $this->getAuthenticatedUser();

            if (AppUtility::verifyPassword($oldPass, $user->password)) {
                $user = User::findByUsername($user->SID);
                $password = AppUtility::passwordHash($newPass);
                $user->password = $password;
                $user->save();

                $this->setSuccessFlash('Your password has been changed.');
                return $this->redirect('change-password');
            } else {
                $this->setErrorFlash('Old password did not match.');
                return $this->redirect('change-password');
            }
        }
        return $this->renderWithData('changePassword', ['model' => $model]);
    }

    public function actionChangeUserInfo()
    {
        $this->guestUserHandler();
        $tzname = $this->getUserTimezone();

        $user = $this->getAuthenticatedUser();
        $model = new ChangeUserInfoForm();
        if ($model->load(Yii::$app->request->post()) && $model->checkPassword()) {
            $params = Yii::$app->request->getBodyParams();
            $params = $params['ChangeUserInfoForm'];

            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file) {
                $model->file->saveAs(AppConstant::UPLOAD_DIRECTORY . $user->id . '.jpg');
            }
            User::saveUserRecord($params);
            $this->setSuccessFlash('Changes updated successfully.');
        }
        $this->includeJS(['js/changeUserInfo.js']);
        return $this->renderWithData('changeUserinfo', ['model' => $model, 'user' => isset($user->attributes) ? $user->attributes : null, 'tzname' => $tzname]);
    }

    public function actionStudentEnrollCourse()
    {
        $this->guestUserHandler();
        $model = new StudentEnrollCourseForm();
        return $this->renderWithData('studentEnrollCourse', ['model' => $model]);

    }
}