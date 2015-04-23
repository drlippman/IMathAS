<?php

namespace app\controllers;

use app\components\AppConstant;
use app\models\_base\BaseImasCourses;
use app\models\_base\BaseImasDiags;
use app\models\AdminDiagnosticForm;
use app\models\ChangeUserInfoForm;
use app\models\CourseSettingForm;
use app\models\DiagnosticForm;
use app\models\ForgetPasswordForm;
use app\models\ForgetUsernameForm;
use app\models\LoginForm;
use app\models\RegistrationForm;
use app\models\StudentEnrollCourseForm;
use app\models\StudentRegisterForm;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\components\AppUtility;
use app\models\ChangePasswordForm;
use app\models\MessageForm;
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
        if(!$this->isGuestUser()){
            return $this->redirect('site/dashboard');
        }else{
            return $this->render('index');
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

        $this->includeCSS(['../css/login.css']);
        $this->includeJS(['../js/jstz_min.js', '../js/login.js']);

        return $this->render('login', [
            'model' => $model, 'challenge' => $challenge,
        ]);
    }

    /**
     * @return string
     * Controller for about us page
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * @return string
     * Instructor registration controller
     */
    public function actionRegistration()
    {
        $model = new RegistrationForm();
        if ($model->load(Yii::$app->request->post())) {

            $params = Yii::$app->request->getBodyParams();
            $params = $params['RegistrationForm'];
            $params['SID'] = $params['username'];

            $params['hideonpostswidget'] = AppConstant::ZERO_VALUE;
            $params['password'] = AppUtility::passwordHash($params['password']);


            $user = new User();
            $user->attributes = $params;
            $user->save();

            $toEmail = $user->email;
            $message = 'First Name: '.$user->FirstName.  "<br/>\n";
            $message .= 'Last Name: '.$user->LastName.  "<br/>\n";
            $message .= 'Email Name: '.$user->email.  "<br/>\n";
            $message .= 'User Name: '.$user->SID. "<br/>\n";

            AppUtility::sendMail(AppConstant::INSTRUCTOR_REQUEST_MAIL_SUBJECT, $message, $toEmail);

            $this->setSuccessFlash(AppConstant::INSTRUCTOR_REQUEST_SUCCESS);
        }
        return $this->render('registration', [
            'model' => $model,
        ]);
    }

    public function actionStudentRegister()
    {

        $model = new StudentRegisterForm();
        if ($model->load(Yii::$app->request->post())) {
            $params = Yii::$app->request->getBodyParams();
            $params = $params['StudentRegisterForm'];
            $status = User::createStudentAccount($params);
            if ($status)
            {
                $message = 'First Name: '.$params['FirstName'].  "<br/>\n";
                $message .= 'Last Name: '.$params['LastName'].  "<br/>\n";
                $message .= 'Email Name: '.$params['email'].  "<br/>\n";
                $message .= 'User Name: '.$params['username']. "<br/>\n";

                $email = Yii::$app->mailer->compose();
                $email->setTo($params['email'])
                    ->setSubject(AppConstant::STUDENT_REQUEST_MAIL_SUBJECT)
                    ->setHtmlBody($message)
                    ->send();
                Yii::$app->session->setFlash('success', AppConstant::STUDENT_REQUEST_SUCCESS);

            }


        }
        return $this->render('studentRegister', ['model' => $model,]);
    }

    /**
     * Method that redirects to a generic work in progress page
     * @return string
     */
    public function actionWorkInProgress()
    {
        return $this->render('progress');
    }

    public function actionDiagnostic()
    {
        $model = new DiagnosticForm();
        return $this->render('diagnostic', ['model' => $model]);
    }


    public function actionForgotPassword()
    {
        $model = new ForgetPasswordForm();
        if ($model->load(Yii::$app->request->post())) {
            $param = $this->getBodyParams();
            $username = $param['ForgetPasswordForm']['username'];

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
        }

        return $this->render('forgetPassword', ['model' => $model,]);
    }

    public function actionForgotUsername()
    {
        $model = new ForgetUsernameForm();
        if ($model->load(Yii::$app->request->post())) {
            $param = $this->getBodyParams();
            $toEmail = $param['ForgetUsernameForm']['email'];

            $user = User::findByEmail($toEmail);
            if ($user) {
                $message = "<h4>This is an automated message from OpenMath.  Do not respond to this email</h4>";
                $message .= "<p>Your email was entered in the Username Lookup page on OpenMath.  If you did not do this, you may ignore and delete this message.  ";
                $message .= "All usernames using this email address are listed below</p><p>";
                $message .= "Username: <b>" . $user->SID . " </b> <br/>.";
                AppUtility::sendMail(AppConstant::FORGOT_USER_MAIL_SUBJECT, $message, $toEmail);
            } else {
                $this->setErrorFlash(AppConstant::INVALID_EMAIL);
            }
        }
        return $this->render('forgetUsername', ['model' => $model,]);
    }


    public function actionCheckBrowser()
    {
        return $this->render('checkBrowser');

    }

    //////////////////////////////////////////////////////////////
    ////////////////// Logged in user functions //////////////////
    //////////////////////////////////////////////////////////////

    public function actionLogout()
    {
        if($this->getAuthenticatedUser()){
            Yii::$app->user->logout();
            return $this->goHome();
        }
    }


    public function actionDashboard()
    {
        $user = Yii::$app->user->identity;
        if ($user) {
            $this->includeCSS(['css/dashboard.css']);

            $this->getView()->registerJs('var usingASCIISvg = true;');
            $this->includeJS(["js/dashboard.js", "js/ASCIIsvg_min.js", "js/tablesorter.js"]);

            $userData = ['user' => $user];

            if ($user->rights === AppConstant::ADMIN_RIGHT){
                return $this->render('adminDashboard', $userData);
            }
            elseif ($user->rights === AppConstant::GUEST_RIGHT){
                return $this->render('adminDashboard', $userData);
            }
            elseif ($user->rights === AppConstant::STUDENT_RIGHT){
                return $this->render('studentDashboard', $userData);
            }
            elseif ($user->rights === AppConstant::TEACHER_RIGHT){
                return $this->render('instructorDashboard', $userData);
            }
            elseif ($user->rights === AppConstant::GROUP_ADMIN_RIGHT){
                return $this->render('adminDashboard', $userData);
            }
            else{
                return $this->render('adminDashboard', $userData);
            }
        }
        Yii::$app->session->setFlash('danger', AppConstant::LOGIN_FIRST);
        return $this->redirect('login');
    }

    public function actionChangePassword()
    {
        if (Yii::$app->user->identity) {
            $model = new ChangePasswordForm();
            if ($model->load(Yii::$app->request->post())) {
                $param = Yii::$app->request->getBodyParams();

                $oldPass = $param['ChangePasswordForm']['oldPassword'];
                $newPass = $param['ChangePasswordForm']['newPassword'];

                if (AppUtility::verifyPassword($oldPass, Yii::$app->user->identity->password)) {
                    $user = User::findByUsername(Yii::$app->user->identity->SID);
                    $password = AppUtility::passwordHash($newPass);
                    $user->password = $password;
                    $user->save();

                    Yii::$app->session->setFlash('success', 'Your password has been changed.');
                    return $this->redirect('change-password');
                } else {
                    Yii::$app->session->setFlash('danger', 'Old password did nit match.');
                    return $this->redirect('change-password');
                }
            }
            return $this->render('changePassword', ['model' => $model]);
        }
        return $this->redirect('login');
    }

    public function actionChangeUserInfo()
    {
        if( Yii::$app->session->get('user.identity'))
        {
            $tzname = $this->getUserTimezone();

            $user = Yii::$app->session->get('user.identity');
            $model = new ChangeUserInfoForm();
            if($model->load(Yii::$app->request->post()) && $model->checkPassword())
            {
                $params = Yii::$app->request->getBodyParams() ;
                $params = $params['ChangeUserInfoForm'];

                $model->file = UploadedFile::getInstance($model,'file');
                if($model->file)
                {
                    $model->file->saveAs(AppConstant::UPLOAD_DIRECTORY. $user->id.'.jpg');
                }
                User::saveUserRecord($params);
            }
            $this->includeJS(['js/changeUserInfo.js']);

            return $this->render('changeUserinfo',['model'=> $model, 'user' => isset($user->attributes)?$user->attributes:null,'tzname' => $tzname]);
        }
        return $this->redirect('login');
    }

    public function actionMessages()
    {
        if ($this->getAuthenticatedUser()) {
            $model = new MessageForm();
            return $this->render('messages', ['model' => $model,]);
        }
        return $this->redirect('login');
    }

    public function actionStudentEnrollCourse()
    {
        if (Yii::$app->user->identity) {
            $model = new StudentEnrollCourseForm();
            return $this->render('studentEnrollCourse', ['model' => $model,]);
        }
        return $this->redirect('login');
    }

    public function actionCourseSetting()
    {
            $model = new CourseSettingForm();

            if ($model->load(Yii::$app->request->post()))
            {
                AppUtility::dump($_POST);
                $params = Yii::$app->request->getBodyParams();
                $params = $params['CourseSettingForm'];
                $params['ownerid'] = Yii::$app->user->identity->id;
                $params['name'] = $params['courseName'];
                $params['enrollkey'] = $params['enrollmentKey'];
                $availables = isset($params['available']) ? $params['available'] : 3;
                $params['available'] = AppUtility::makeAvailable($availables);
                $params['picicons'] = $params['icons'];
                $params['allowunenroll'] = $params['selfUnenroll'];
                $params['copyrights'] = $params['copyCourse'];
                $params['msgset'] = $params['messageSystem'];
                $toolsets = isset($params['navigationLink']) ? $params['navigationLink'] : 7;
                $params['toolset']  = AppUtility::makeToolset($toolsets);

                $studentQuickPick = isset($params['studentQuickPick']) ? $params['studentQuickPick'] : null;
                $instructorQuickPick = isset($params['instructorQuickPick']) ? $params['instructorQuickPick'] : null;
                $quickPickBar = isset($params['quickPickBar']) ? $params['quickPickBar'] : null;
                $params['topbar'] = AppUtility::createTopBarString($studentQuickPick, $instructorQuickPick, $quickPickBar);

                $params['cploc']= $params['courseManagement'];
                $params['deflatepass']= $params['latePasses'];
                $params['theme']= $params['theme'];
                AppUtility::dump($params);
                $courseSetting = new BaseImasCourses();
                $params = AppUtility::removeEmptyAttributes($params);
                AppUtility::dump($params);
                $courseSetting->attributes = $params;
                $courseSetting->save();
            }
            $this->includeJS(["js/courseSetting.js"]);
            return $this->render('courseSetting', ['model' => $model]);
        }
}