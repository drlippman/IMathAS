<?php

namespace app\controllers;

use app\components\AppConstant;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\DiagOneTime;
use app\models\Diags;
use app\models\forms\ForgotPasswordForm;
use app\models\forms\ForgotUsernameForm;
use app\models\forms\LoginForm;
use app\models\forms\RegistrationForm;
use app\models\forms\ResetPasswordForm;
use app\models\ForumThread;
use app\models\Groups;
use app\models\Libraries;
use app\models\Message;
use app\models\QuestionSet;
use app\models\Sessions;
use app\models\Student;
use app\models\forms\StudentEnrollCourseForm;
use app\models\forms\StudentRegisterForm;
use app\models\Teacher;
use app\models\Tutor;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\components\AppUtility;
use app\models\forms\ChangePasswordForm;
use app\components\filehandler;
use app\components\UserPics;

class SiteController extends AppController
{
    public function beforeAction($action)
    {
        $user = $this->getAuthenticatedUser();
        $actionPath = Yii::$app->controller->action->id;
        $courseId =  ($this->getRequestParams('cid') || $this->getRequestParams('courseId')) ? ($this->getRequestParams('cid')?$this->getRequestParams('cid'):$this->getRequestParams('courseId') ): AppUtility::getDataFromSession('courseId');
        return $this->accessForSiteController($user,$actionPath,$courseId);
    }
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
        $this->layout = 'master';
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
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        if (!$this->isGuestUser()) {
            return $this->redirect(AppUtility::getURLFromHome('site','dashboard'));
        } else {
            return $this->redirect(AppUtility::getURLFromHome('site','login'));
        }
    }

    public function actionLogin()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->unauthorizedAccessHandler();
        $this->layout = 'nonLoggedUser';
        $params = $this->getRequestParams();
        $model = new LoginForm();
        if ($model->load($this->isPostMethod())) {
            global $tzoffset,$tzname;
            $tzoffset = $params['tzoffset'];
            $tzname = $params['tzname'];
            if ($model->login())
            {
                Yii::$app->session->set('tzoffset', $params['tzoffset']);
                Yii::$app->session->set('tzname', $params['tzname']);
                $sessionStatus = $this->checkSession($params);

                if($sessionStatus['status'] === true){
                    return $this->redirect('dashboard');
                }
                else{
                    if ($this->getAuthenticatedUser()) {
                        $sessionId = Yii::$app->session->getId();
                        Sessions::deleteBySessionId($sessionId);
                        Yii::$app->user->logout();
                    }
                    $this->setErrorFlash($sessionStatus['message']);
                }

            } else {
                $this->setErrorFlash(AppConstant::INVALID_USERNAME_PASSWORD);
            }
        }
        $challenge = AppUtility::getChallenge();
        $this->includeCSS(['login.css']);
        $this->includeJS(['jstz_min.js', 'login.js']);
        $responseData = array('model' => $model, 'challenge' => $challenge,);
        return $this->renderWithData('login', $responseData);
    }

    /**
     * @return string
     * Controller for about us page
     */
    public function actionDiagnostics()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->layout = 'master';
        $diagId = $this->getParamVal('id');
        $params = $this->getRequestParams();
        $imasroot = AppUtility::getHomeURL();
        $installname = AppUtility::getHomeURL();
        session_start();
        $sessionid = session_id();

        if (!isset($params['id']))
        {
            $displayDiagnostics = Diags::getByIdAndName();
        }
        $line = Diags::getAllDataById($diagId);
        $pcid = $line['cid'];
        $diagid = $line['id'];

        if ($line['term'] == '*mo*') {
            $diagqtr = date("M y");
        } else if ($line['term'] == '*day*') {
            $diagqtr = date("M j y");
        } else {
            $diagqtr = $line['term'];
        }
        $sel1 = explode(',',$line['sel1list']);
        $userip = $_SERVER['REMOTE_ADDR'];
        $noproctor = false;
        if ($line['ips'] != '')
        {
            foreach (explode(',',$line['ips']) as $ip) {
                if ($ip=='*') {
                    $noproctor = true;
                    break;
                } else if (strpos($ip,'*') !== FALSE) {
                    $ip = substr($ip,0,strpos($ip,'*'));
                    if ($ip == substr($userip,0,strlen($ip))) {
                        $noproctor = true;
                        break;
                    }
                } else if ($ip == $userip) {
                    $noproctor = true;
                    break;
                }
            }
        }
        $sessionIdDatas = Sessions::getBySessionId($sessionid);
        $sessionIdData = $sessionIdDatas['sessionid'];
        if (count($sessionIdData) > AppConstant::NUMERIC_ZERO) {
            Sessions::deleteBySessionId($sessionid);
            $sessiondata = array();
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time()-42000, '/');
            }
            session_destroy();
            return $this->redirect('site', 'diagnostics?id=' .$diagId);
        }
        if (isset($params['SID']))
        {
            $params['SID'] = trim(str_replace('-','',$params['SID']));

            if (trim($params['SID']) == '' || trim($params['firstname']) == '' || trim($params['lastname']) == '')
            {
                echo "<html><body>", _('Please enter your ID, first name, and lastname.'), "  <a href=\"#\">", _('Try Again'), "</a>\n";
                exit;
            }
            $result = Diags::getByDiagId($diagId);
            $entryformat = $result[0]['entryformat'];
            $sel1 = explode(',',$result[0]['sel1list']);
            $entrytype = substr($entryformat,0,1); //$entryformat{0};
            $entrydig = substr($entryformat,1); //$entryformat{1};
            $entrynotunique = false;
            if ($entrytype == 'A' || $entrytype == 'B')
            {
                $entrytype = chr(ord($entrytype) + 2);
                $entrynotunique = true;
            }
            $pattern = '/^';
            if ($entrytype == 'C')
            {
                $pattern .= '\w';
            } else if ($entrytype == 'D')
            {
                $pattern .= '\d';
            } else if ($entrytype == 'E')
            {
                $pattern .= '[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}';
            }

            if ($entrytype != 'E')
            {
                if ($entrydig == 0)
                {
                    $pattern .= '+';
                } else {
                    $pattern .= '{'.$entrydig.'}';
                }
            }
            $pattern .= '$/i';
            if (!preg_match($pattern, $params['SID']))
            {
                echo "<html><body>", _('Your ID is not valid.  It should contain'), " ";
                if ($entrydig > 0 && $entrytype != 'E') {
                    echo $entrydig.' ';
                }
                if ($entrytype=='C')
                {
                    echo _('letters or numbers');
                } else if ($entrytype=='D') {
                    echo _('numbers');
                } else if ($entrytype=='E') {
                    echo _('an email address');
                }
                echo "<a href='".AppUtility::getURLFromHome('site', 'diagnostics?id='.$diagId)."'>" , _('Try Again'), "</a>\n";
            }

            if ($params['course'] == -1)
            {
                echo "<html><body>", sprintf(_('Please select a %1$s and %2$s.'), $line['sel1name'], $line['sel2name']), "  <a href='".AppUtility::getURLFromHome('site', 'diagnostics?id='.$diagId)."'>" , _('Try Again'), "</a>\n";
                exit;
            }
            $pws = array();
            $pws = explode(';',$line['pws']);
            if (trim($pws[0])!='') {
                $basicpw = explode(',',$pws[0]);
            } else {
                $basicpw = array();
            }
            if (count($pws)>1 && trim($pws[1])!='') {
                $superpw = explode(',',$pws[1]);
            } else {
                $superpw = array();
            }
            //$pws = explode(',',$line['pws']);
            foreach ($basicpw as $k=>$v) {
                $basicpw[$k] = strtolower($v);
            }
            foreach ($superpw as $k=>$v) {
                $superpw[$k] = strtolower($v);
            }
            $diagSID = $params['SID'].'~'. $diagqtr.'~'.$pcid;
            if ($entrynotunique)
            {
                $diagSID .= '~'.preg_replace('/\W/','',$sel1[$params['course']]);
            }
            if (!$noproctor)
            {
                if (!in_array(strtolower($params['passwd']),$basicpw) && !in_array(strtolower($params['passwd']),$superpw)) {
                    $password = strtoupper($params['passwd']);
                    $result = DiagOneTime::getByCode($password, $diagId);
                    $passwordnotfound = false;
                    if (count($result) > 0)
                    {
                        $row = count($result); //[0] = id, [1] = goodfor
                        if ($row['goodfor'] == 0)
                        {  //onetime
                            DiagOneTime::deleteById($row['id']);
                        } else
                        { //set time expiry
                            $now = time();
                            if ($row['goodfor'] < 100000000)
                            { //is time its good for - not yet used
                                $expiry = $now + $row['goodfor']*60;
                                DiagOneTime::setGoodFor($row['id'], $expiry);
                            } else if ($now < $row['goodfor'])
                            {//is expiry time and we're within it
                                //alls good
                            } else { //past expiry
                                DiagOneTime::deleteById($row['id']);
                                $passwordnotfound = true;
                            }
                        }
                    } else {
                        $passwordnotfound = true;
                    }
                    if ($passwordnotfound) {
                        $result = User::getPassword($diagSID);
                        if (count($result) > 0 && strtoupper(mysql_result($result,0,0))==strtoupper($params['passwd'])) {

                        } else {
                            echo "<html><body>", _('Error, password incorrect or expired.'), "
                            <a href='".AppUtility::getURLFromHome('site', 'diagnostics?id='.$diagId)."'>" , _('Try Again'), "</a>\n";
                            exit;
                        }
                    }
                }
            }
            $cnt = 0;
            $now = time();

            $result = User::getBySId($diagSID);
            if (count($result) > 0)
            {
                $userid = $result[0]['id'];
                $allowreentry = ($line['public']&4);
                if (!in_array(strtolower($params['passwd']),$superpw) && (!$allowreentry || $line['reentrytime'] > 0))
                {
                    $aids = explode(',',$line['aidlist']);
                    $paid = $aids[$params['course']];
                    $r2 = AssessmentSession::getByIdAndStartTime($userid, $paid);
                    if (count($r2) > 0) {
                        if (!$allowreentry) {
                            echo _("You've already taken this diagnostic."), "  <a href='".AppUtility::getURLFromHome('site', 'diagnostics?id='.$diagId)."'>" , _('Back'), "</a>\n";
                            exit;
                        } else {
                            $d = count($r2);
                            $now = time();
                            if ($now - $d[1] > 60*$line['reentrytime']) {
                                echo _('Your window to complete this diagnostic has expired.'), "  <a href='".AppUtility::getURLFromHome('site', 'diagnostics?id='.$diagId)."'>" , _('Back'), "</a>\n";
                                exit;
                            }
                        }
                    }
                }

                $sessiondata['mathdisp'] = $params['mathdisp'];//1;
                $sessiondata['graphdisp'] = $params['graphdisp'];//1;
                $sessiondata['useed'] = 1;
                $sessiondata['isdiag'] = $diagid;
                $enc = base64_encode(serialize($sessiondata));
                if (!empty($params['tzname']))
                {
                    $tzname = $params['tzname'];
                } else {
                    $tzname = '';
                }
                $session = new Sessions();
                $session->createSession('c', $userid, $now,$params['tzoffset'],$tzname,$enc);
                $aids = explode(',',$line['aidlist']);
                $paid = $aids[$_POST['course']];
                if ((intval($line['forceregen']) & (1<<intval($_POST['course'])))>0) {
                   AssessmentSession::deleteData($userid,$paid);
                }

                User::setLastAccess($now, $userid);
                return $this->redirect('assessment', 'assessment/show-assessment?cid='.$pcid.'&id='.$paid);
            }

            $eclass = $sel1[$params['course']] . '@' . $params['teachers'];

            $ten = AppConstant::NUMERIC_TEN;
            $userData = new User();
            $userid = $userData->addUser($diagSID,$params['passwd'],$ten,$params['firstname'],$params['lastname'],$eclass,$now);

            $teacher = $params['teachers'];
            $studentData = new Student();

            $sessiondata['mathdisp'] = $params['mathdisp'];//1;
            $sessiondata['graphdisp'] = $params['graphdisp'];//1;
            $sessiondata['useed'] = 1;
            $sessiondata['isdiag'] = $diagid;
            $enc = base64_encode(serialize($sessiondata));
            if (!empty($params['tzname'])) {
                $tzname = $params['tzname'];
            } else {
                $tzname = '';
            }
            $sessionEntryData = new Sessions();
            $sessionEntryData->createSession($sessionid, $userid, $now, $params['tzoffset'], $tzname, $enc);

            $aids = explode(',',$line['aidlist']);
            $paid = $aids[$_POST['course']];

            return $this->redirect('assessment', 'assessment/show-assessment?cid='.$pcid.'&id='.$paid);
        }
        $this->includeJS(['jstz_min.js']);
        $responseData = array('line' => $line, 'diagid' => $diagid, 'params' => $params, 'displayDiagnostics' => $displayDiagnostics, 'imasroot' => $imasroot, 'installname' => $installname, 'sel1' => $sel1, 'noproctor' => $noproctor, 'pws' => $pws);
        return $this->renderWithData('diagnostics', $responseData);
    }

    /**
     * @return string
     * Instructor registration controller
     */
    public function actionRegistration()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->layout = 'nonLoggedUser';
        $model = new RegistrationForm();
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $params = $params['RegistrationForm'];
            $params['SID'] = $params['username'];
            $params['hideonpostswidget'] = AppConstant::ZERO_VALUE;
            $params['password'] = AppUtility::passwordHash($params['password']);

            $user = new User();
            $user->attributes = $params;
            $user->save();

            $toEmail = $user->email;
            $message = "<p>Welcome to OpenMath</p> ";
            $message .= "<p>Hi ".AppUtility::getFullName($user->FirstName, $user->LastName)." ,</p> ";
            $message .= '<p>We received a request for instructor account with following credentials.</p>';
            $message .= 'First Name: ' . $user->FirstName . "<br/>\n";
            $message .= 'Last Name: ' . $user->LastName . "<br/>\n";
            $message .= 'Email Name: ' . $user->email . "<br/>\n";
            $message .= 'User Name: ' . $user->SID . "<br/>\n";
            $message .= "</p>This is an automated message from OpenMath.  Do not respond to this email <br><br></p>";
            $message .= "</p>Best Regards,<br></p>";
            $message .= "</p>OpenMath Team<br></p>";
            AppUtility::sendMail(AppConstant::INSTRUCTOR_REQUEST_MAIL_SUBJECT, $message, $toEmail);
            $this->setSuccessFlash(AppConstant::INSTRUCTOR_REQUEST_SUCCESS);
            return $this->redirect(AppUtility::getURLFromHome('site','registration'));
        }
        $this->includeJS(["registration.js"]);
        $responseData = array('model' => $model,);
        return $this->renderWithData('registration', $responseData);
    }

    public function actionStudentRegister()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $flashMsg ='';
        $this->layout = 'nonLoggedUser';
        $model = new StudentRegisterForm();
        if ($model->load($this->isPostMethod())) {
            $params = $this->getRequestParams();
            $params = $params['StudentRegisterForm'];
            $status = User::createStudentAccount($params);
            if ($status) {
                $message = "<p>We received a request for student account with following credentials.</p> ";
                $message .= 'First Name: ' . $params['FirstName'] . "<br/>\n";
                $message .= 'Last Name: ' . $params['LastName'] . "<br/>\n";
                $message .= 'Email Name: ' . $params['email'] . "<br/>\n";
                $message .= 'User Name: ' . $params['username'] . "<br/>\n";
                $message .= "</p>This is an automated message from OpenMath.  Do not respond to this email <br><br></p>";
                $message .= "</p>Best Regards,<br></p>";
                $message .= "</p>OpenMath Team<br></p>";
                AppUtility::sendMail(AppConstant::STUDENT_REQUEST_MAIL_SUBJECT, $message, $params['email']);
                $this->setSuccessFlash('Account created successfully, please login to get into system.');
                return $this->redirect(AppUtility::getURLFromHome('site','login'));
            }
            $this->setErrorFlash('User already exist.');
        }
        $this->includeCSS(['studentRegister.css']); 
        $responseData = array('model' => $model,);
        return $this->renderWithData('studentRegister', $responseData);
    }

    /**
     * Method that redirects to a generic work in progress page
     * @return string
     */
    public function actionWorkInProgress()
    {
        return $this->renderWithData('progress');
    }

    public function actionForgotPassword()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->layout = 'nonLoggedUser';
        $model = new ForgotPasswordForm();
        if ($model->load($this->isPostMethod())) {
                $param = $this->getRequestParams();
                $username = $param['ForgotPasswordForm']['username'];
                $user = User::findByUsername($username);
            if($user)
            {
                $code = AppUtility::generateRandomString();
                $user->remoteaccess = $code;
                $user->save();
                $toEmail = $user->email;
                $id = $user->id;
                $message = "<p>Welcome to OpenMath</p> ";
                $message .= "<p>Hi ".AppUtility::getFullName($user->FirstName, $user->LastName).",</p> ";
                $message .= "<p>We received a request to reset the password associated with this e-mail address. If you made this request, please follow the instructions below.</p> ";
                $message .= "Username: <b>" . $user->SID." </b><br>";
                $message .= "<p>Click on the link below to reset your password using our secure server:</p>";
                $message .= "<p> URL: <a href=\"" . AppUtility::urlMode() . $_SERVER['HTTP_HOST'] . Yii::$app->homeUrl . "site/reset-password?id=$id&code=$code\">";
                $message .= AppUtility::urlMode() . $_SERVER['HTTP_HOST'] . Yii::$app->homeUrl . "site/reset-password?id=$id&code=$code</a>\r\n";
                $message .= "<p>If you did not request to have your password reset you can safely ignore this email. Rest assured your account is safe.</p>";
                $message .= "<p>If clicking the link does not seem to work, you can copy and paste the link into your browser's address window, or retype it there. Once you have returned to OpenMath, we will give instructions for resetting your password.</p>";
                $message .= "</p>This is an automated message from OpenMath.  Do not respond to this email. <br><br></p>";
                $message .= "<p>Best Regards,<br>OpenMath Team</p></p>";
                AppUtility::sendMail(AppConstant::FORGOT_PASS_MAIL_SUBJECT, $message, $toEmail);
                $model = new ForgotPasswordForm();
                //TODO: Add administrator email in there which was added while installing app.
                $this->setSuccessFlash('<p>An email with a password reset link has been sent your email address on record: <b>'.$user->email.'</b></p><p><p>If you do not see it in a few minutes, check your spam or junk box to see if the email ended up there.</p>If you still have trouble or the wrong email address is on file, contact your instructor - they can reset your password for you.</p>');
            }else{
                $this->setErrorFlash('Such username does not exist.');
            }
        }
        $this->includeCSS(['login.css']);
        $responseData = array('model' => $model,);
        return $this->renderWithData('forgotPassword', $responseData);
    }

    public function actionForgotUsername()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->layout = 'nonLoggedUser';
        $model = new ForgotUsernameForm();
        if ($model->load($this->isPostMethod())) {
            $param = $this->getRequestParams();
            $toEmail = $param['ForgotUsernameForm']['email'];
            $users = User::findByEmail($toEmail);
            if (!empty($users) && is_array($users)) {
                //Todo: Add install name in email.
                $message = "<p>Welcome to OpenMath</p> ";
                $message .= "<p>Hi,<br></p>";
                $message .= "<p>We received a request to get the username associated with this e-mail address ".$user->email.". If you have made this request, please see the Username associated with this email listed below.</p> ";
                foreach($users as $singleUser){
                    $lastLogin = ($singleUser['lastaccess'] == 0 ) ? "Never" : date("n/j/y g:ia",$singleUser['lastaccess']) ;
                    $message .= "<p>Username: <b>".$singleUser['SID']."</b>  Last logged in: ".$lastLogin."</br></p>";
                }
                $message .= "<p>If you did not request to have your username you can safely ignore this email. Rest assured your account is safe.</p>";
                $message .= "<br>This is an automated message from OpenMath.  Do not respond to this email</p><br>";
                $message .= "<p>Best Regards,<br>OpenMath Team</p></p>";
                AppUtility::sendMail(AppConstant::FORGOT_USER_MAIL_SUBJECT, $message, $toEmail);
                $model = new ForgotUsernameForm();
                $this->setSuccessFlash(count($users).' usernames match this email address and were emailed.');
            } else {
                $this->setErrorFlash(AppConstant::INVALID_EMAIL);
            }
        }
        $responseData = array('model' => $model,);
        $this->includeCSS(['login.css']);
        return $this->renderWithData('forgotUsername', $responseData);
    }

    public function actionCheckBrowser()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->layout = 'nonLoggedUser';
        return $this->renderWithData('checkBrowser');
    }

    public function actionResetPassword()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->layout = "master";
        $id = $this->getParamVal('id');
        $code = $this->getParamVal('code');
        $model = new ResetPasswordForm();
        $user = User::getByIdAndCode($id, $code);

        if($user)
        {
            if($this->isPost())
            {
                $params = $this->getRequestParams();
                $newPassword = $params['ResetPasswordForm']['newPassword'];
                $password = AppUtility::passwordHash($newPassword);
                $user->password = $password;
                $user->remoteaccess = null;
                $user->save();
                $this->setSuccessFlash('Your password is changed successfully.');
                return $this->redirect("login");
            }
        }else{
            $this->setErrorFlash('Reset Link has been expired.');
        }
        $responseData = array('model' => $model,);
        return $this->renderWithData('resetPassword', $responseData);
    }

    //////////////////////////////////////////////////////////////
    ////////////////// Logged in user functions //////////////////
    //////////////////////////////////////////////////////////////

    public function actionLogout()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */

        if ($this->getAuthenticatedUser()){
            $sessionId = Yii::$app->session->getId();
            Sessions::deleteBySessionId($sessionId);
            Yii::$app->user->logout();
        }
        return $this->redirect(AppUtility::getURLFromHome('site', 'login'));
    }

    public function actionDashboard()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        global $homeLayout,  $hideOnPostsWidget, $newMsgCnt,  $brokenCnt,  $user, $myRights,  $twoColumn,  $pagelayout,  $page_newmessagelist, $page_coursenames,  $page_newpostlist,  $postThreads,
        $showNewMsgNote, $showNewPostNote, $stuHasHiddenCourses,  $courses, $newPostCnt, $page_teacherCourseData, $page_tutorCourseData, $page_studentCourseData;
        $this->layout = 'master';
        if (!$this->isGuestUser()) {
            $user = $this->getAuthenticatedUser();
            $myRights = $user['rights'];
            $courses = Course::getByName($user->id);
            $studCourse = Course::getCourseOfStudent($user->id);
            $userLayoutData = User::getUserHomeLayoutInfo($user->id);
            $homeLayout = $userLayoutData['homelayout'];
            $hideOnPostsWidget = $userLayoutData['hideonpostswidget'];
            $from = $this->getParamVal('from');
//            $this->setSessionData('user',$user);
            $pagelayout = explode('|',$homeLayout);
            if ($hideOnPostsWidget != '') {
                $hideOnPostsWidget = explode(',',$hideOnPostsWidget);
            } else {
                $hideOnPostsWidget = array();
            }

            foreach($pagelayout as $k=>$v) {
                if ($v=='') {
                    $pagelayout[$k] = array();
                } else {
                    $pagelayout[$k] = explode(',',$v);
                }
            }
            $showNewMsgNote = in_array(0,$pagelayout[3]);

            $showNewPostNote = in_array(1,$pagelayout[3]);

            $showMessagesGadget = (in_array(10,$pagelayout[1]) || in_array(10,$pagelayout[0]) || in_array(10,$pagelayout[2]));
            $showPostsGadget = (in_array(11,$pagelayout[1]) || in_array(11,$pagelayout[0]) || in_array(11,$pagelayout[2]));

            $twoColumn = (count($pagelayout[1])>0 && count($pagelayout[2])>0);
            /**
             * check for new posts in courses being taken
             */

            $newpostscnt = array();
            $postcheckcids = array();
            $postcheckstucids = array();
            $page_coursenames = array();
//            $page_newpostlist = array();
            global $page_newmessagelist,$page_newpostlist;
            /**
             * check for new message in courses being taken
             */
            $newMsgCnt = array();
            if ($showMessagesGadget) {
//                $page_newmessagelist = array();

                $result = Message::getNewMessageData($user->id);
                foreach($result as $key => $line) {
                    if (!($newMsgCnt[$line['courseid']])) {
                        $newMsgCnt[$line['courseid']] = 1;
                    } else {
                        $newMsgCnt[$line['courseid']]++;
                    }
                    $page_newmessagelist[] = $line;
                }
            } else {
                /**
                 * check for new messages
                 */
                $result = Message::getUserById($user->id);
                foreach($result as $key => $row){
                    $newMsgCnt[$row['courseid']] = $row['COUNT(id)'];
                }
            }

            $page_studentCourseData = array();

            /**
             *
             * check to see if the user is enrolled as a student
             */
            $stuHasHiddenCourses = false;
            if ($studCourse == 0) {
                $noClass = true;
            } else {
                foreach($studCourse as $key => $line)
                {
                    if ($line['hidefromcourselist'] == 1) {
                        $stuHasHiddenCourses = true;
                    } else {
                        $noClass = false;
                        $page_studentCourseData[] = $line;
                        $page_coursenames[$line['id']] = $line['name'];
                        if (!in_array($line['id'],$hideOnPostsWidget)) {
                            $postcheckstucids[] = $line['id'];
                        }
                    }
                }
            }
            /**
             * Teacher
             */
            $page_teacherCourseData = array();
            if($myRights > AppConstant::STUDENT_RIGHT)
            {
                $teachCourse = Course::getCourseOfTeacher($user->id);
                if($teachCourse == AppConstant::NUMERIC_ZERO)
                {
                    $noClass = true;
                } else {
                $noClass = false;
                $tchcids = array();
                foreach($teachCourse as $key => $line)
                {
                    $page_teacherCourseData[] = $line;
                    $page_coursenames[$line['id']] = $line['name'];
                    if (!in_array($line['id'],($hideOnPostsWidget))) {
                        $postcheckstucids[] = $line['id'];
                    }
                 }
               }
            }
            /**
             * Tutor
             */
            $page_tutorCourseData = array();
            $resultTutor = Tutor::getTutorData($user->id);
            if($resultTutor == AppConstant::NUMERIC_ZERO)
            {
                $noClass = true;
            } else {
                $noClass = false;
                $tchcids = array();

                foreach($resultTutor as $key => $line) {
                    $page_tutorCourseData[] = $line;
                    $page_coursenames[$line['id']] = $line['name'];
                    if (!in_array($line['id'],$hideOnPostsWidget)) {
                        $postcheckstucids[] = $line['id'];
                    }
                }
            }
            /**
             * get new posts
             * check for new posts in courses being taught.
             */
            $postcidlist = $postcheckcids;
            $postThreads = array();

            if ($showPostsGadget && count($postcheckcids) > AppConstant::NUMERIC_ZERO) {
                $newPost = ForumThread::getNewPost($postcidlist, $user->id);
                foreach($newPost as $key => $line) {
                    if (!isset($newPostCnt[$line['courseid']])) {
                        $newPostCnt[$line['courseid']] = 1;
                    } else {
                        $newPostCnt[$line['courseid']]++;
                    }
                    if ($newPostCnt[$line['courseid']]<10) {
                        $page_newpostlist[] = $line;
                        $postThreads[] = $line['threadid'];
                    }
                }
            } else if (count($postcheckcids)>0) {
                $result = ForumThread::getPostData($postcidlist, $user->id);
                foreach($result as $key => $row)
                {
                    $newPostCnt[$row['courseid']] = $row['COUNT(imas_forum_threads.id)'];
                }
            }
            /**
             *
             * check for new posts in courses being taken
             */
            $poststucidlist = $postcheckstucids;
            $now = time();

            if ($showPostsGadget && count($postcheckstucids) > AppConstant::NUMERIC_ZERO) {
                $result = ForumThread::getNewPostData($poststucidlist, $now, $user->id);
                foreach($result as $key => $line) {
                    if (!isset($newPostCnt[$line['courseid']])) {
                        $newPostCnt[$line['courseid']] = AppConstant::NUMERIC_ONE;
                    } else {
                        $newPostCnt[$line['courseid']]++;
                    }
                    if ($newPostCnt[$line['courseid']] < 10) {
                        $page_newpostlist[] = $line;
                        $postThreads[] = $line['threadid'];
                    }
                }
            } else if (count($postcheckstucids) > 0) {
                $r2 = ForumThread::getPostThread($poststucidlist, $now, $user->id);
                foreach($r2 as $key => $row) {
                    $newPostCnt[$row['courseid']] = $row['COUNT(imas_forum_threads.id)'];
                }
            }
            if ($myRights == AppConstant::ADMIN_RIGHT) {
                $result = QuestionSet::getBrokenData();
                $brokenCnt = array();
                foreach($result as $key => $row){
                    $brokenCnt[$row['userights']] = $row['COUNT(id)'];
                }
            }
            if ($user) {
                $this->includeCSS(['dashboard.css']);
                $this->getView()->registerJs('var usingASCIISvg = true;');
                $this->includeJS(["dashboard.js", "ASCIIsvg_min.js", "tablesorter.js", "course.js"]);
                $responseData = array('homeLayout' => $homeLayout,'from' => $from,'hideOnPostsWidget' => $hideOnPostsWidget, 'newMsgCnt' => $newMsgCnt, 'brokenCnt' => $brokenCnt, 'user' => $user, 'myRights' => $myRights, 'twoColumn' => $twoColumn, 'pagelayout' => $pagelayout, 'page_newmessagelist' => $page_newmessagelist, 'page_coursenames' => $page_coursenames, 'page_newpostlist' => $page_newpostlist, 'postThreads' => $postThreads, 'showNewMsgNote' => $showNewMsgNote, 'showNewPostNote' => $showNewPostNote, 'stuHasHiddenCourses' => $stuHasHiddenCourses, 'courses' => $courses, 'newPostCnt' => $newPostCnt, 'page_teacherCourseData' => $page_teacherCourseData, 'page_tutorCourseData' => $page_tutorCourseData, 'page_studentCourseData' => $page_studentCourseData);
                return $this->renderWithData('dashboard', $responseData);
            }
        }
        $this->setErrorFlash(AppConstant::LOGIN_FIRST);
        return $this->redirect('login');
    }

    public function actionChangePassword()
    {
        /**
         * Can access: greater than equal to guest.
         *  Guest
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->guestUserHandler();
        $this->layout = 'nonLoggedUser';
        $model = new ChangePasswordForm();
        if ($model->load($this->isPostMethod())) {
            $param = $this->getRequestParams();

            $oldPass = $param['ChangePasswordForm']['oldPassword'];
            $newPass = $param['ChangePasswordForm']['newPassword'];

            $user = $this->getAuthenticatedUser();

            if (AppUtility::verifyPassword($oldPass, $user->password)) {
                $user = User::findByUsername($user->SID);
                $password = AppUtility::passwordHash($newPass);
                $user->password = $password;
                $user->save();

                $this->setSuccessFlash('Your password has been changed.');
                return $this->redirect('dashboard');
            } else {
                $this->setErrorFlash('Old password did not match.');
                return $this->redirect('change-password');
            }
        }
        $responseData = array('model' => $model,);
        return $this->renderWithData('changePassword', $responseData);
    }

    public function actionHelperGuide()
    {
        $params = $this->getRequestParams();
        $section = $params['section'];
        $this->layout = 'master';
        $responseData = (['section' => $section]);
        $this->includeCSS(['infopages.css']);
        return $this->renderWithData('helpForStudentAnswer', $responseData);
    }

    public function actionDocumentation()
    {
//        $this->layout = 'master';
        return $this->renderWithData('document');
    }

    public function actionHideFromCourseList()
    {
        /**
         * Can access: greater than equal to student.
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        if (!$this->isGuestUser()) {
            $userId = $this->getUserId();
            $course = $this->getRequestParams();
            Student::updateHideFromCourseList($userId, $course['courseId']);
            return $this->successResponse();
            } else {
                return $this->terminateResponse(AppConstant::NO_MESSAGE_FOUND);
            }
    }

    public function actionUnhideFromCourseList()
    {
        /**
         * Can access: greater than equal to student.
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $userId = $this->getUserId();
        if($params){
            $courseId = $this->getParamVal('cid');
            Student::updateHideFromCourseList($userId,$courseId);
        }
        $students = Student::findHiddenCourse($userId);
        $courseDetails = array();
        if($students){
            foreach($students  as $student){
                array_push($courseDetails, Course::getById($student->courseid));
            }
        }
        $responsedata = array('courseDetails' => $courseDetails);
        return $this->renderWithData('unhideFromCourseList',$responsedata);
    }

    public function actionHelpForStudentAnswer()
    {
        $this->includeCSS(['infopages.css']);
        return $this->renderWithData('helpForStudentAnswer');
    }

    public function actionInstructorDocument()
    {
        $this->layout = 'master';
        $this->includeCSS(['docs.css']);
        return $this->renderWithData('instructorDocument');
    }

    public function actionForm()
    {
        /**
         * Can access: greater than equal to student.
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->guestUserHandler();
        $this->layout = 'master';
        $sessionId = $this->getSessionId();
        $sessions =  Sessions::getById($sessionId);
        $tzname =  $sessions['tzname'];
        $userId = $this->getUserId();
        $user = User::findByUserId($userId);
        $myRights = $user['rights'];
        $action = $this->getParamVal('action');
        $groupId = AppConstant::NUMERIC_ZERO;
        switch($action) {
            case "chguserinfo":
                $pageTitle = 'Modify User Profile';
                $line = User::getById($userId);
                if ($myRights > AppConstant::STUDENT_RIGHT && $groupId > AppConstant::NUMERIC_ZERO) {
                    $r = Groups::getName($groupId);
                }
                if ($myRights > AppConstant::TEACHER_RIGHT)
                {
                   $lName = Libraries::getByName($line['deflib']);
                   $lName = $lName[0]['name'];
                }
                break;
            case "forumwidgetsettings":
                $pageTitle = 'Forum Widget Settings';
                $result = User::getUserHideOnPostInfo($user->id);
                $hideList = explode(',',($result['hideonpostswidget']));

                $coursesTeaching = Teacher::getDataByUserId($userId);
                $coursesTutoring = Tutor::getDataByUserId($userId);
                $coursesTaking = Student::getStudentByUserId($userId);
                break;
        }
        $this->includeJS(['jquery.min.js','question/addquestions.js', 'tablesorter.js', 'general.js']);
        $responseData = array('action' => $action, 'line' => $line, 'myRights' => $myRights, 'groupId' => $groupId, 'groupResult' => $r, 'lName' => $lName, 'tzname' => $tzname, 'userId' => $userId, 'hideList' => $hideList, 'coursesTaking' => $coursesTaking, 'coursesTeaching' => $coursesTeaching, 'coursesTutoring' => $coursesTutoring, 'pageTitle' => $pageTitle);
        return $this->renderWithData('form',$responseData);
    }

    public function actionAction()
    {
        /**
         * Can access: greater than equal to student.
         *  Student
         *  Teacher
         *  LCC
         *  Diagnostics
         *  Group Admin
         *  Admin
         */
        $this->guestUserHandler();
        $this->layout = 'master';
        $userId = $this->getUserId();
        $user = User::findByUserId($userId);
        $myRights = $user['rights'];
        $action = $this->getParamVal('action');
        $groupId = AppConstant::NUMERIC_ZERO;
        $params = $this->getRequestParams();
        if($action == 'chguserinfo')
        {
            if (($params['msgnot'])) {
                $msgNot = AppConstant::NUMERIC_ONE;
            } else {
                $msgNot = AppConstant::NUMERIC_ZERO;
            }

            if (isset($params['qrd']) || $myRights < AppConstant::TEACHER_RIGHT) {
                $qRightsDef = AppConstant::NUMERIC_ZERO;
            } else {
                $qRightsDef = AppConstant::NUMERIC_TWO;
            }
            if (isset($params['usedeflib'])) {
                $useDefLib = AppConstant::NUMERIC_ONE;
            } else {
                $useDefLib = AppConstant::NUMERIC_ZERO;
            }
            if ($myRights < AppConstant::TEACHER_RIGHT) {
                $defLib = AppConstant::NUMERIC_ZERO;
            } else {
                $defLib = $params['libs'];
            }

            $homeLayout[0] = array();
            $homeLayout[1] = array(0,1,2);
            $homeLayout[2] = array();

            if (isset($params['homelayout10'])) {
                $homeLayout[2][] = AppConstant::NUMERIC_TEN;
            }
            if (isset($params['homelayout11'])) {
                $homeLayout[2][] = AppConstant::NUMERIC_ELEVEN;
            }
            $homeLayout[3] = array();
            if (isset($params['homelayout3-0'])) {
                $homeLayout[3][] = AppConstant::NUMERIC_ZERO;
            }
            if (isset($params['homelayout3-1'])) {
                $homeLayout[3][] = AppConstant::NUMERIC_ONE;
            }
            foreach ($homeLayout as $k=>$v) {
                $homeLayout[$k] = implode(',',$v);
            }
            $perpage = intval($params['perpage']);
            if (isset($CFG['GEN']['fixedhomelayout']) && $CFG['GEN']['homelayout']) {
                $deflayout = explode('|',$CFG['GEN']['homelayout']);
                foreach ($CFG['GEN']['fixedhomelayout'] as $k) {
                    $homeLayout[$k] = $deflayout[$k];
                }
            }
            $layoutStr = implode('|',$homeLayout);
            if (is_uploaded_file($_FILES['stupic']['tmp_name'])) {
                UserPics::processImage($_FILES['stupic'],$userId,200,200);
                UserPics::processImage($_FILES['stupic'],'sm'.$userId,40,40);
                $chgUserImg = AppConstant::NUMERIC_ONE;
            } else if (isset($params['removepic'])) {
                filehandler::deletecoursefile($userId.'.jpg');
                filehandler::deletecoursefile($userId.'.jpg');
                $chgUserImg = AppConstant::NUMERIC_ZERO;
            } else {
                $chgUserImg = $user['hasuserimg'];
            }
            $firstName = $params['firstname'];
            $lastName = $params['lastname'];
            $email = $params['email'];

            if (empty($params["email"])) {
                $emailErr = "Email is required";
            } else {
                $email = ($params["email"]);
                // check if e-mail address is well-formed
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emailErr = "Invalid email format";
                }
            }
            User::updateUserDetails($userId, $firstName, $lastName, $email, $msgNot, $qRightsDef, $defLib, $useDefLib, $layoutStr, $perpage,$chgUserImg);
            if ($params['dochgpw']) {
                $line = User::getUserPassword($userId);
                if ((md5($params['oldpw']) == $line['password'] || (password_verify($params['oldpw'],$line['password']))) && ($params['newpw1'] == $params['newpw2']) && $myRights > 5)
                {
                    if (isset($CFG['GEN']['newpasswords'])) {
                        $md5pw = password_hash($params['newpw1'], PASSWORD_DEFAULT);
                    } else {
                        $md5pw =  AppUtility::passwordHash($params['newpw1']);
                    }
                    User::updateUserPassword($userId, $md5pw);
                } else {
                    $this->setErrorFlash("Password change failed. Try Again.");
                }
            }
            if ($params['settimezone']) {
                if (date_default_timezone_set($params['settimezone'])) {
                    $tzName = $params['settimezone'];
                    $sessionId = session_id();
                    Sessions::updatetzName($sessionId, $tzName);
                }
            }
            return $this->redirect('dashboard');
        }  else if ($action == "forumwidgetsettings") {
            $checked = $params['checked'];
            $all = explode(',',$params['allcourses']);
            foreach ($all as $k=>$v) {
                $all[$k] = intval($v);
            }
            $toHide = array_diff($all,$checked);
            $hideList = implode(',', $toHide);
            User::updateHideOnPost($userId, $hideList);
            return $this->redirect('dashboard');
        }
    }
}