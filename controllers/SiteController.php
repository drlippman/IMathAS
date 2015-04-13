<?php

namespace app\controllers;

use app\components\AppConstant;
use app\models\_base\BaseImasSessions;
use app\models\LoginForm;
use app\models\RegistrationForm;
use app\models\StudentRegisterForm;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\ContactForm;
use app\components\AppUtility;

class SiteController extends Controller
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
        return $this->render('index');
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {

            if(AppUtility::isOldSiteSupported())
            {
                //Set session data
                ini_set('session.gc_maxlifetime',AppConstant::MAX_SESSION_TIME);
                ini_set('auto_detect_line_endings',true);
                $sessionid = session_id();

                $session_data['useragent'] = $_SERVER['HTTP_USER_AGENT'];
                $session_data['ip'] = $_SERVER['REMOTE_ADDR'];
                $session_data['secsalt'] = AppUtility::generateRandomString();

                $session_data['mathdisp'] = 1;
                $session_data['graphdisp'] = 1;
                $session_data['useed'] = AppUtility::checkEditOrOk();
                $enc = base64_encode(serialize($session_data));

                $session = new BaseImasSessions();
                if (isset($_POST['tzname']) && strpos(basename($_SERVER['PHP_SELF']),'upgrade.php')===false) {
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
               return Yii::$app->getResponse()->redirect('http://localhost/IMathAS');
            }
            $this->redirect('dashboard');
        } else {
            $challenge = base64_encode(microtime() . rand(0,9999));
            $this->getView()->registerJsFile('../../mathjax/MathJax.js');
            $this->getView()->registerJsFile('../js/jstz_min.js');
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
        if($model->load(Yii::$app->request->post()))
        {
            $params =$_REQUEST;
            $user= new User();

            $password = $params['RegistrationForm']['password'];

            require("../components/password.php");
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $user->FirstName= $params['RegistrationForm']['FirstName'];
            $user->LastName= $params['RegistrationForm']['LastName'];
            $user->email= $params['RegistrationForm']['email'];
            $user->SID= $params['RegistrationForm']['username'];
            $user->password= $password_hash;
            $user->hideonpostswidget = AppConstant::ZERO_VALUE;
            $user->save();
        }
        return $this->render('registration',[
            'model'=> $model,
        ]);
    }

    public function actionStudentRegister()
    {
        $model = new StudentRegisterForm();
        if ($model->load(Yii::$app->request->post()))
        {
            StudentRegisterForm::Submit();
        }
        return $this->render('studentRegister',['model'=> $model,]);
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
        return $this->render('adminDashboard');
    }
    public function actionStudentEnrollCourse()
    {
        $model = new studentEnrollCourseForm();
        return $this->render('studentEnrollCourse',['model'=> $model,]);
    }

}