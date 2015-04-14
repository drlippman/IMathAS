<?php

namespace app\controllers;

use app\components\AppConstant;
use app\models\_base\BaseImasSessions;
use app\models\ChangeUserInfoForm;
use app\models\CourseSettingForm;
use app\models\DiagnosticForm;
use app\models\LoginForm;
use app\models\RegistrationForm;
use app\models\studentEnrollCourseForm;
use app\models\StudentRegisterForm;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\ContactForm;
use app\components\AppUtility;
use app\models\ChangePasswordForm;
use app\models\MessageForm;

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
        if(Yii::$app->user->isGuest)
            return $this->render('index');
        else
            $this->redirect('site/dashboard');

    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            if (AppUtility::isOldSiteSupported()) {
                //Set session data
                ini_set('session.gc_maxlifetime', AppConstant::MAX_SESSION_TIME);
                ini_set('auto_detect_line_endings', true);
                $sessionid = session_id();

                $session_data['useragent'] = $_SERVER['HTTP_USER_AGENT'];
                $session_data['ip'] = $_SERVER['REMOTE_ADDR'];
                $session_data['secsalt'] = AppUtility::generateRandomString();

                $session_data['mathdisp'] = 1;
                $session_data['graphdisp'] = 1;
                $session_data['useed'] = AppUtility::checkEditOrOk();
                $enc = base64_encode(serialize($session_data));

                $session = new BaseImasSessions();
                if (isset($_POST['tzname']) && strpos(basename($_SERVER['PHP_SELF']), 'upgrade.php') === false) {
                    //$query = "INSERT INTO imas_sessions (sessionid,userid,time,tzoffset,tzname,sessiondata) VALUES ('$sessionid','$userid',$now,'{$_POST['tzoffset']}','{$_POST['tzname']}','$enc')";

                } else {
                    //$query = "INSERT INTO imas_sessions (sessionid,userid,time,tzoffset,sessiondata) VALUES ('$sessionid','$userid',$now,'{$_POST['tzoffset']}','$enc')";
                    $session->sessionid = $sessionid;
                    $session->userid = Yii::$app->getUser()->id;
                    $session->time = time();
                    $session->tzoffset = '-330';
                    $session->tzname = "Asia/calcutta";
                    $session->sessiondata = $enc;
                }
                $session->save();
                return Yii::$app->getResponse()->redirect(Yii::$app->homeUrl.'IMathAS');
            }
            $this->redirect('dashboard');
        } else {
            $challenge = base64_encode(microtime() . rand(0, 9999));
            $this->getView()->registerCssFile('../css/login.css');
            return $this->render('login', [
                'model' => $model, 'challenge' => $challenge,
            ]);
        }
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
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
            require("../components/password.php");
            $params = Yii::$app->request->getBodyParams();
            $params = $params['RegistrationForm'];
            $params['SID'] = $params['username'];
            $params['hideonpostswidget'] = AppConstant::ZERO_VALUE;
            $params['password'] = password_hash($params['password'], PASSWORD_DEFAULT);

            $user = new User();
            $user->attributes = $params;
            $user->save();
        }
        return $this->render('registration', [
            'model' => $model,
        ]);
    }

    public function actionStudentRegister()
    {
        $model = new StudentRegisterForm();
        if ($model->load(Yii::$app->request->post())) {
            StudentRegisterForm::Submit();
        }
        return $this->render('studentRegister', ['model' => $model,]);
    }

    /**
     * @return string
     * Controller for general work progress page
     */
    public function actionWorkInProgress()
    {
        return $this->render('progress');
    }

    public function actionDashboard()
    {
        $user = Yii::$app->user->identity;
        if ($user) {
            if ($user->rights === 100)
                return $this->render('adminDashboard', ['user' => $user]);
            elseif ($user->rights === 5)
                return $this->render('adminDashboard', ['user' => $user]);
            elseif ($user->rights === 10)
                return $this->render('studentDashboard', ['user' => $user]);
            elseif ($user->rights === 20)
                return $this->render('instructorDashboard', ['user' => $user]);
            elseif ($user->rights === 75)
                return $this->render('adminDashboard', ['user' => $user]);
        }
        Yii::$app->session->setFlash('error', AppConstant::LOGIN_FIRST);
        return $this->redirect('login');
    }

    public function actionChangePassword()
    {
        if( Yii::$app->user->identity)
        {
            $model = new ChangePasswordForm();
            return $this->render('changePassword', ['model' => $model,]);
        }
       return $this->redirect('login');
    }
//    public function actionChangeUserInfo()
//    {
//        $user = Yii::$app->user->identity;
//        $model = new changeUserInfoForm();
//        if($model->load(Yii::$app->request->post()))
//        {
//            $params = Yii::$app->request->getBodyParams();
//            $params = $params['changeUserInfoForm'];
//            $params['password'] = isset($params['changeUserInfoForm']['password'])?$params['changeUserInfoForm']['password']:Yii::$app->user->identity->password;
//            User::saveUserRecord($params);
//        }
//        return $this->render('changeuserinfo',['model'=> $model, 'user' => isset($user->attributes)?$user->attributes:null]);
//    }
    public function actionCourseSetting()
    {
        $model = new CourseSettingForm();
        return $this->render('courseSetting',['model'=>$model]);
    }
    public function actionDiagnostic()
    {
        $model = new DiagnosticForm();
        return $this->render('diagnostic',['model'=>$model]);
    }


    public function actionChangeUserInfo()
    {
        if( Yii::$app->user->identity)
        {
            $tzname = "Asia/Kolkata";

            $user = Yii::$app->user->identity;
            $model = new ChangeUserInfoForm();
            if($model->load(Yii::$app->request->post()))
            {
                $params = Yii::$app->request->getBodyParams();
                $params = $params['ChangeUserInfoForm'];
                User::saveUserRecord($params);
            }
            return $this->render('changeUserinfo',['model'=> $model, 'user' => isset($user->attributes)?$user->attributes:null,'tzname' => $tzname]);
        }
        return $this->redirect('login');
    }

    public function actionMessages()
    {
        if( Yii::$app->user->identity)
        {
            $model = new MessageForm();
            return $this->render('messages', ['model' => $model,]);
        }
       return $this->redirect('login');
    }

    public function actionStudentEnrollCourse()
    {
        if( Yii::$app->user->identity)
        {
            $model = new StudentEnrollCourseForm();
            return $this->render('studentEnrollCourse', ['model' => $model,]);
        }
        return $this->redirect('login');
    }
}