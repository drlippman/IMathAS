<?php

namespace app\controllers;

use app\components\AppConstant;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\DiagOneTime;
use app\models\Diags;
use app\models\forms\ChangeUserInfoForm;
use app\models\forms\DiagnosticForm;
use app\models\forms\ForgotPasswordForm;
use app\models\forms\ForgotUsernameForm;
use app\models\forms\LoginForm;
use app\models\forms\RegistrationForm;
use app\models\forms\ResetPasswordForm;
use app\models\Message;
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
use yii\web\HttpException;
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
            return $this->redirect(AppUtility::getURLFromHome('site','dashboard'));
        } else {

            return $this->redirect(AppUtility::getURLFromHome('site','login'));
        }
    }

    public function actionLogin()
    {
        $this->unauthorizedAccessHandler();
        $this->layout = 'nonLoggedUser';
        $params = $this->getRequestParams();
        $model = new LoginForm();
        if ($model->load($this->isPostMethod())) {
            if ($model->login()) {
                Yii::$app->session->set('tzoffset', $this->getParamVal('tzoffset'));
                Yii::$app->session->set('tzname', $this->getParamVal('tzname'));
                $this->checkSession($params);
                return $this->redirect('dashboard');
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
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
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
                } else if (strpos($ip,'*')!==FALSE) {
                    $ip = substr($ip,0,strpos($ip,'*'));
                    if ($ip == substr($userip,0,strlen($ip))) {
                        $noproctor = true;
                        break;
                    }
                } else if ($ip==$userip) {
                    $noproctor = true;
                    break;
                }
            }
        }
        $sessionIdData = Sessions::getBySessionId($sessionid);
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
                echo "<html><body>", _('Please enter your ID, first name, and lastname.'), "  <a href=\"index.php?id=$diagid\">", _('Try Again'), "</a>\n";
//                exit;
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
//                exit;
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
            $diagSID = $params['SID'].'~'.addslashes($diagqtr).'~'.$pcid;
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
//                            exit;
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
//                            exit;
                        } else {
                            $d = count($r2);
                            $now = time();
                            if ($now - $d[1] > 60*$line['reentrytime']) {
                                echo _('Your window to complete this diagnostic has expired.'), "  <a href='".AppUtility::getURLFromHome('site', 'diagnostics?id='.$diagId)."'>" , _('Back'), "</a>\n";
//                                exit;
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

    public function actionDiagnostic()
    {
        $model = new DiagnosticForm();
        $responseData = array('model' => $model,);
        return $this->renderWithData('diagnostic', $responseData);
    }

    public function actionForgotPassword()
    {
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
                $this->setSuccessFlash('Password reset link sent to your registered email.');
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
        $this->layout = 'nonLoggedUser';
        $model = new ForgotUsernameForm();
        if ($model->load($this->isPostMethod())) {
            $param = $this->getRequestParams();
            $toEmail = $param['ForgotUsernameForm']['email'];
            $user = User::findByEmail($toEmail);
            if ($user) {
                $message = "<p>Welcome to OpenMath</p> ";
                $message .= "<p>Hi ".AppUtility::getFullName($user->FirstName, $user->LastName).",<br>";
                $message .= "<p>We received a request to get the username associated with this e-mail address ".$user->email.". If you have made this request, please see the Username associated with this email listed below.</p> ";
                $message .= "<p>Username: <b>".$user->SID."</b></br></p>";
                $message .= "<p>If you did not request to have your username you can safely ignore this email. Rest assured your account is safe.</p>";
                $message .= "<br>This is an automated message from OpenMath.  Do not respond to this email</p><br>";
                $message .= "<p>Best Regards,<br>OpenMath Team</p></p>";
                AppUtility::sendMail(AppConstant::FORGOT_USER_MAIL_SUBJECT, $message, $toEmail);
                $model = new ForgotUsernameForm();
                $this->setSuccessFlash('Username sent to your registered email.');
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
        $this->layout = 'nonLoggedUser';
        return $this->renderWithData('checkBrowser');
    }

    public function actionResetPassword()
    {
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
        if ($this->getAuthenticatedUser()) {
            $sessionId = Yii::$app->session->getId();
            Sessions::deleteBySessionId($sessionId);
            Yii::$app->user->logout();
            return $this->redirect(AppUtility::getURLFromHome('site', 'login'));
        }
    }

    public function actionDashboard()
    {
        if (!$this->isGuestUser()) {
            $user = $this->getAuthenticatedUser();
            $students = Student::getByUserId($user->id);
            $tutors = Tutor::getByUser($user->id);
            $teachers = Teacher::getTeacherByUserId($user->id);
            if($students){
                $users = $students;
            }else if($teachers){
                $users = $teachers;
            } elseif($tutors){
                $user = $tutors;
            }
            $isreadArray = array(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR, AppConstant::NUMERIC_EIGHT, AppConstant::NUMERIC_TWELVE);
            $msgCountArray = array();
            if($users){
                foreach ($users as $singleUser) {
                    $messageList = Message::getByCourseIdAndUserId($singleUser->courseid, $user->id);
                    $count = AppConstant::NUMERIC_ZERO;
                    if($messageList){
                        foreach($messageList as $message){
                            if(in_array($message->isread, $isreadArray))
                                $count++;
                        }
                    }
                    $tempArray = array('courseid' => $singleUser->courseid, 'msgCount' => $count);
                    array_push($msgCountArray,$tempArray);
                }
            }
            if ($user) {
                $this->includeCSS(['dashboard.css']);
                $this->getView()->registerJs('var usingASCIISvg = true;');
                $this->includeJS(["dashboard.js", "ASCIIsvg_min.js", "tablesorter.js"]);
                $userData = ['user' => $user, 'students' => $students, 'teachers' => $teachers, 'users' => $users, 'msgRecord' => $msgCountArray, 'tutors' => $tutors];
                return $this->renderWithData('dashboard', $userData);
            }
        }
        $this->setErrorFlash(AppConstant::LOGIN_FIRST);
        return $this->redirect('login');
    }

    public function actionChangePassword()
    {
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

    public function actionChangeUserInfo()
    {
        $this->guestUserHandler();
        $this->layout = 'nonLoggedUser';
        $tzname = AppUtility::getTimezoneName();
        $userid = $this->getUserId();
        $user = User::findByUserId($userid);
        $model = new ChangeUserInfoForm();
        $params = $this->getRequestParams();
        if ($this->isPostMethod())
        {
            if(!$params['ChangeUserInfoForm']['oldPassword'] && !$params['ChangeUserInfoForm']['password'] && !$params['ChangeUserInfoForm']['rePassword'])
            {
                $result = AppConstant::NUMERIC_TWO;
            }
            else
            {
                $result = $model->checkPassword($userid,$params);
            }
            if($result == AppConstant::NUMERIC_ONE)
            {
                $this->setErrorFlash('New password must match with confirm password');
                return $this->redirect('change-user-info');
            }
            else if($result == AppConstant::NUMERIC_ZERO)
            {
                $this->setErrorFlash('Incorrect old Password');
                return $this->redirect('Change-user-info');
            }
            else if($result == AppConstant::NUMERIC_TWO)
            {
                $params = $params['ChangeUserInfoForm'];
                $model->file = UploadedFile::getInstance($model, 'file');
                if ($model->file )
                {
                    $model->file->saveAs(AppConstant::UPLOAD_DIRECTORY . $user->id . '.jpg');
                    $model->remove= AppConstant::NUMERIC_ZERO;
                    if(AppConstant::UPLOAD_DIRECTORY.$user->id. '.jpg')
                    User::updateImgByUserId($userid);
                }
                if($model->remove == AppConstant::NUMERIC_ONE){
                    User::deleteImgByUserId($userid);
                    unlink(AppConstant::UPLOAD_DIRECTORY . $user->id . '.jpg');
                }
                User::saveUserRecord($params,$user);
                $this->setSuccessFlash('Changes updated successfully.');
                return $this->redirect('dashboard');
            }
        }
        $this->includeCSS(['dashboard.css']);
        $this->includeJS(['changeUserInfo.js']);
        $responseData = array('model' => $model, 'user' => isset($user->attributes) ? $user->attributes : null, 'tzname' => $tzname,'userId' => $userid);
        return $this->renderWithData('changeUserinfo', $responseData);
    }
    public function actionStudentEnrollCourse()
    {
        $this->guestUserHandler();
        $model = new StudentEnrollCourseForm();
        $responseData = array('model' => $model,);
        return $this->renderWithData('studentEnrollCourse', $responseData);
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
        return $this->renderWithData('document');
    }
    public function actionHideFromCourseList()
    {
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
        $this->includeCSS(['docs.css']);
        return $this->renderWithData('instructorDocument');
    }
}