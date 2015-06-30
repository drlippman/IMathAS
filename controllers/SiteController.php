<?php

namespace app\controllers;

use app\components\AppConstant;
use app\models\Course;
use app\models\forms\ChangeUserInfoForm;
use app\models\forms\DiagnosticForm;
use app\models\forms\ForgotPasswordForm;
use app\models\forms\ForgotUsernameForm;
use app\models\forms\LoginForm;
use app\models\forms\RegistrationForm;
use app\models\forms\ResetPasswordForm;
use app\models\Message;
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

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->login()) {

                Yii::$app->session->set('tzoffset', $this->getParam('tzoffset'));
                Yii::$app->session->set('tzname', $this->getParam('tzname'));

                return $this->redirect('dashboard');
            } else {
                $this->setErrorFlash(AppConstant::INVALID_USERNAME_PASSWORD);
            }
        }
        $challenge = AppUtility::getChallenge();
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
        return $this->renderWithData('diagnostics');
    }

    /**
     * @return string
     * Instructor registration controller
     */
    public function actionRegistration()
    {
        $model = new RegistrationForm();
        if ($model->load($this->getPostData())) {

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
        $model = new StudentRegisterForm();
        if ($model->load($this->getPostData())) {
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
        $model = new ForgotPasswordForm();
        if ($model->load($this->getPostData())) {
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
        $responseData = array('model' => $model,);
        return $this->renderWithData('forgotPassword', $responseData);
    }

    public function actionForgotUsername()
    {
        $model = new ForgotUsernameForm();
        if ($model->load($this->getPostData())) {
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
        return $this->renderWithData('forgotUsername', $responseData);
    }

    public function actionCheckBrowser()
    {
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
                $this->redirect('login');

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
            $isreadArray = array(0, 4, 8, 12);
            $msgCountArray = array();
            if($users){
                foreach ($users as $singleUser) {
                    $messageList = Message::getByCourseIdAndUserId($singleUser->courseid, $user->id);
                    $count = 0;
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
        $model = new ChangePasswordForm();
        if ($model->load($this->getPostData())) {
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
                return $this->redirect(AppUtility::getURLFromHome('site','login'));
            } else {
                $this->setErrorFlash('Old password did not match.');
                return $this->redirect('change-password');
            }
        }
        $responseData = array('model' => $model,);
        return $this->renderWithData('changePassword', ['model' => $model]);
    }

    public function actionChangeUserInfo()
    {
        $this->guestUserHandler();
        $tzname = AppUtility::getTimezoneName();
        $userid = $this->getUserId();
        $user = User::findByUserId($userid);
        $model = new ChangeUserInfoForm();
        if ($model->load($this->getPostData()) && $model->checkPassword()) {
            $params = $this->getRequestParams();
            $params = $params['ChangeUserInfoForm'];

            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file ) {
                $model->file->saveAs(AppConstant::UPLOAD_DIRECTORY . $user->id . '.jpg');
                $model->remove=0;
                if(AppConstant::UPLOAD_DIRECTORY.$user->id. '.jpg')
                User::updateImgByUserId($userid);
            }
            if($model->remove == 1){
                User::deleteImgByUserId($userid);
                unlink(AppConstant::UPLOAD_DIRECTORY . $user->id . '.jpg');
            }
            User::saveUserRecord($params,$user);
            $this->setSuccessFlash('Changes updated successfully.');
            $this->redirect('dashboard');
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
        return $this->renderWithData('help');
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
        $this->includeCSS(['doc.css']);
        return $this->renderWithData('instructorDocument');
    }


}