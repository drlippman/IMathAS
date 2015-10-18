<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 13/4/15
 * Time: 8:32 PM
 */

namespace app\controllers;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasSessions;
use app\models\Assessments;
use app\models\Course;
use app\models\DbSchema;
use app\models\LoginLog;
use app\models\Sessions;
use app\models\Student;
use app\models\Tutor;
use app\models\User;
use yii\web\Controller;
use app\models\Teacher;
use yii\web\Session;
use app\models\Message;
use app\models\Thread;
use Yii;

class AppController extends Controller
{

    public $enableCsrfValidation = false;

    function getRequestParams()
    {
        return $_REQUEST;
    }

    function setSuccessFlash($message)
    {
        $this->_setFlash('success', $message);
    }

    function setErrorFlash($message)
    {
        $this->_setFlash('danger', $message);
    }

    function setWarningFlash($message)
    {
        $this->_setFlash('warning', $message);
    }

    private function _setFlash($type, $message)
    {
        \Yii::$app->session->setFlash($type, $message);
    }

    public function getDatabase()
    {
       return Yii::$app->getDb();
    }

    function unauthorizedAccessHandler()
    {
        if (!$this->isGuestUser()) {
            return $this->goHome();
            exit;
        }
    }

    function isGuestUser(){
        return \Yii::$app->user->isGuest;
    }

    function guestUserHandler($a= false, $isAjaxCall = false){
        if(self::isGuestUser())
        {
            if($isAjaxCall)
            {
//                return self::terminateResponse(AppConstant::UNAUTHORIZED_ACCESS);
                return false;
            }else{
                return $this->redirect(AppUtility::getHomeURL().'site/login');
            }
        }
    }

    function getUserId(){
        return $this->getAuthenticatedUser()->getId();
    }

    function getUserTimezone(){
        return AppConstant::DEFAULT_TIME_ZONE;
    }

    function includeCSS($cssFileArray){
        $this->includeAssets($cssFileArray, AppConstant::ASSET_TYPE_CSS);
    }

    function includeJS($jsFileArray){
        $this->includeAssets($jsFileArray, AppConstant::ASSET_TYPE_JS);
    }


    function includeAssets($fileArray, $assetType){
        $cnt = count($fileArray);
        $assetUrl = AppUtility::getAssetURL();
        for($i = AppConstant::NUMERIC_ZERO; $i < $cnt; $i++){
            $fileURL = $assetUrl . $assetType . "/" . $fileArray[$i];
            if($assetType == AppConstant::ASSET_TYPE_CSS){
                $this->getView()->registerCssFile($fileURL."?ver=".AppConstant::VERSION_NUMBER);
            }else{
                $this->getView()->registerJsFile($fileURL."?ver=".AppConstant::VERSION_NUMBER);
            }
        }
    }

    public function renderWithData($viewName, $data = array()){
        return $this->render($viewName, $data);
    }

    function getAuthenticatedUser(){
        return \Yii::$app->user->identity;
    }

    public function isPost(){
        return Yii::$app->request->getMethod() == 'POST';
    }

    public function successResponse($data = '')
    {
        return json_encode(array('status' =>AppConstant::RETURN_SUCCESS, 'data' => $data));
    }

    public function terminateResponse($msg)
    {
        return json_encode(array('status' => AppConstant::RETURN_ERROR, 'message' => $msg));
    }

    public function getParamVal($key){
        return Yii::$app->request->get($key);
    }

    public function getSanitizedValue($key, $defaultVal = '')
    {
        return isset($key) ? $key : $defaultVal;
    }

    public function isPostMethod(){
        return Yii::$app->request->post();
    }

    public function setReferrer()
    {
        $referrer = Yii::$app->request->getReferrer();
        if ($referrer) {
            Yii::$app->session->set('referrer', $referrer);
        }
    }

    function getUserRight(){
        $user = $this->getAuthenticatedUser();
        return $user->rights;
    }

    public function previousPage(){
        return Yii::$app->request->referrer;
    }

    public function userAuthentication($user,$courseId)
    {
        if($user->rights == AppConstant::STUDENT_RIGHT){
            $student = Student::getByCourseId($courseId, $user->id);
            if ($student == ''){

                $this->goBack();
               return $this->setErrorFlash(AppConstant::UNAUTHORIZED_ACCESS);
            }
        }else if($user->rights == AppConstant::GUEST_RIGHT)
        {}
        else
        {
            $teacher = Teacher::getByUserId($user->id,$courseId);
            if($teacher == ''){
                $this->setErrorFlash(AppConstant::UNAUTHORIZED_ACCESS);
                $this->goBack();
            }
        }
    }

    public function checkSession($params){
        global $CFG;
        $session = Yii::$app->session;
        if (isset($sessionpath) && $sessionpath!='') {
            session_save_path($sessionpath);
        }
        Yii::$app->session->set('session.gc_maxlifetime',AppConstant::MAX_SESSION_TIME);
        Yii::$app->session->set('auto_detect_line_endings',true);

        if ($_SERVER['HTTP_HOST'] != 'localhost') {
            session_set_cookie_params(AppConstant::NUMERIC_ZERO, '/', '.'.implode('.',array_slice(explode('.',$_SERVER['HTTP_HOST']),isset($CFG['GEN']['domainlevel'])?$CFG['GEN']['domainlevel']:-AppConstant::NUMERIC_TWO)));
        }
        if (isset($CFG['GEN']['randfunc'])) {
            $randf = $CFG['GEN']['randfunc'];
        } else {
            $randf = 'rand';
        }
        $session->open();
        $sessionId = $session->getId();
        Yii::$app->session->set('sessionId',$sessionId);
        if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
            $urlMode = 'https://';
        } else {
            $urlMode = 'http://';
        }
        $randomString = $this->generaterandstring();
        $check =$this->checkeditorok();

        $myRights = AppConstant::NUMERIC_ZERO;
        $isPublic = false;
        /*
         * Domain checks for special themes, etc. if desired
         */
        $requestAddress = $_SERVER['HTTP_HOST'] .$_SERVER['PHP_SELF'];
        if (isset($CFG['CPS']['theme'])) {
            $defaultCourseTheme = $CFG['CPS']['theme'][0];
        } else if (!isset($defaultCourseTheme)) {
            $defaultCourseTheme = "default.css";
        }
        $courseTheme = $defaultCourseTheme; //will be overwritten later if set
        if (!isset($CFG['CPS']['miniicons'])) {
            $CFG['CPS']['miniicons'] = array(
                'assess'=>'assess_tiny.png',
                'drill'=>'assess_tiny.png',
                'inline'=>'inline_tiny.png',
                'linked'=>'html_tiny.png',
                'forum'=>'forum_tiny.png',
                'wiki'=>'wiki_tiny.png',
                'folder'=>'folder_tiny.png',
                'calendar'=>'1day.png');
        }
        /*
         * check for bad session ids.
         */
        if (strlen($sessionId)< AppConstant::NUMERIC_TEN) {
            if (function_exists('session_regenerate_id')) {
                $sessionId = $session->regenerateID();
            }
            return $this->redirect(AppUtility::getURLFromHome('site','login'));
        }
        $sessionData = array();
        $haveSession = Sessions::getById($sessionId);
        if ($haveSession > AppConstant::NUMERIC_ZERO) {
            $userId = $haveSession['userid'];
            $tzoffset = $haveSession['tzoffset'];
            $tzname = '';
            if (isset($haveSession['tzname']) && $haveSession['tzname']!='') {
                if (date_default_timezone_set($haveSession['tzname'])) {
                    $tzname = $haveSession['tzname'];
                }
            }
            $sessionContent = $haveSession['sessiondata'];
            if ($sessionContent!=AppConstant::NUMERIC_ZERO) {
                $sessionData = unserialize(base64_decode($sessionContent));
                /*
                 * delete own session if old and not posting
                 */
                if ((time()-$haveSession['time'])>AppConstant::MAX_SESSION_TIME && (!isset($params) || count($params)==AppConstant::NUMERIC_ZERO)) {
                    Sessions::deleteSession($userId);
                    unset($userId);
                    $this->setErrorFlash('You need to login again');
                }
            } else {
                $sessionData['useragent'] = $_SERVER['HTTP_USER_AGENT'];
                $sessionData['ip'] = $_SERVER['REMOTE_ADDR'];
                $sessionData['mathdisp'] = $params['mathdisp'];
                $sessionData['graphdisp'] = $params['graphdisp'];
                $sessionData['useed'] = $check;
                $sessionData['secsalt'] = $randomString;
                if (isset($params['savesettings'])) {
                    setcookie('mathgraphprefs',$params['mathdisp'].'-'.$params['graphdisp'],AppConstant::ALWAYS_TIME);
                }
                $sessionContent = base64_encode(serialize($sessionData));
                Sessions::setSessionId($sessionId,$sessionContent);
                AppUtility::getURLFromHome('site','work-in-progress');
            }
        }
        $hasUserName = isset($userId);
        $hasLogin = isset($params['LoginForm']['password']);
        if (!$hasUserName && !$hasLogin && isset($params['guestaccess']) && isset($CFG['GEN']['guesttempaccts'])) {
            $hasLogin = true;
            $params['LoginForm']['username']='guest';
            $params['mathdisp'] = AppConstant::NUMERIC_ZERO;
            $params['graphdisp'] = AppConstant::NUMERIC_TWO;
        }
        if (isset($params['checksess']) && !$hasUserName) {
            echo '<html><body>';
            echo 'Unable to establish a session. This is most likely caused by your browser blocking third-party cookies.  Please adjust your browser settings and try again.';
            echo '</body></html>';
            exit;
        }
        $verified = false;  $err = '';
        /*
         * Just put in username and password, trying to log in
         */
        if ($hasLogin && !$hasUserName) {
            /*
             * clean up old sessions
             */
            $now = time();
            $old = $now - AppConstant::GIVE_OLD_SESSION_TIME;
            Sessions::deleteByTime($old);

            if (isset($CFG['GEN']['guesttempaccts']) && $params['LoginForm']['username']=='guest') { // create a temp account when someone logs in w/ username: guest
                $dbData = DbSchema::getById(AppConstant::NUMERIC_TWO);
                $guestCount = $dbData['id'];
                DbSchema::setById(AppConstant::NUMERIC_TWO);
                if (isset($CFG['GEN']['homelayout'])) {
                    $homeLayout = $CFG['GEN']['homelayout'];
                } else {
                    $homeLayout = '|0,1,2||0,1';
                }
                $userArray = array(
                    'SID' => 'guestacct'.$guestCount,
                    'password' => '',
                    'rights' => AppConstant::GUEST_RIGHT,
                    'FirstName' => 'Guest',
                    'LastName' => 'Account',
                    'email' => 'none@none.com',
                    'msgnotify' => AppConstant::NUMERIC_ZERO,
                    'homelayout' => $homeLayout
                );
                $user = new User();
                $userId = $user->saveGuestUserRecord($userArray);
                $courseIds = Course::getByAvailable($params);
                $newUser = new Student();
                if ($courseIds > AppConstant::NUMERIC_ZERO) {
                    foreach($courseIds as $id){
                        $newUser->createNewStudent($userId,$id,$params);
                    }
                }

                $haveSession['id'] = $userId;
                $haveSession['rights'] = AppConstant::GUEST_RIGHT;
                $haveSession['groupid'] = AppConstant::NUMERIC_ZERO;
                $params['LoginForm']['password'] = 'temp';
                if (isset($CFG['GEN']['newpasswords'])) {
                    $haveSession['password'] =  AppUtility::passwordHash($params['LoginForm']['password']);
                } else {
                    $haveSession['password'] = md5('temp');
                }
                $params['usedetected'] = true;
            } else {
                $haveSession = User::getByName($params['LoginForm']['username']);
            }
            if (($haveSession != null) && (((!isset($CFG['GEN']['newpasswords']) || $CFG['GEN']['newpasswords']!='only') && ((md5($haveSession['password'].$_SESSION['challenge']) == $params['LoginForm']['password']) ||($haveSession['password'] ==  AppUtility::passwordHash($params['LoginForm']['password']))))
                    || (password_verify($params['LoginForm']['password'],$haveSession['password']))	)) {
                unset($_SESSION['challenge']); //challenge is used up - forget it.
                $userId = $haveSession['id'];
                $groupId = $haveSession['groupid'];
                if ($haveSession['rights']==AppConstant::NUMERIC_ZERO) {
                    echo 'You have not yet confirmed your registration.  You must respond to the email that was sent to you by MyOpenMath.';
                    exit;
                }
                $sessionData['useragent'] = $_SERVER['HTTP_USER_AGENT'];
                $sessionData['ip'] = $_SERVER['REMOTE_ADDR'];
                $sessionData['secsalt'] = $randomString;
                if ($params['access']==AppConstant::NUMERIC_ONE) { //text-based
                    $sessionData['mathdisp'] = $params['mathdisp']; //to allow for accessibility
                    $sessionData['graphdisp'] = AppConstant::NUMERIC_ZERO;
                    $sessionData['useed'] = AppConstant::NUMERIC_ZERO;
                    $sessionContent = base64_encode(serialize($sessionData));
                } else if ($params['access']==AppConstant::NUMERIC_TWO) { //img graphs
                    //deprecated
                    $sessionData['mathdisp'] = AppConstant::NUMERIC_TWO-$params['mathdisp'];
                    $sessionData['graphdisp'] = AppConstant::NUMERIC_TWO;
                    $sessionData['useed'] = $check;
                    $sessionContent = base64_encode(serialize($sessionData));
                } else if ($params['access']==AppConstant::NUMERIC_FOUR) { //img math
                    //deprecated
                    $sessionData['mathdisp'] = AppConstant::NUMERIC_TWO;
                    $sessionData['graphdisp'] = $params['graphdisp'];
                    $sessionData['useed'] = $check;
                    $sessionContent = base64_encode(serialize($sessionData));
                } else if ($params['access']=AppConstant::NUMERIC_THREE) { //img all
                    $sessionData['mathdisp'] = AppConstant::NUMERIC_TWO;
                    $sessionData['graphdisp'] = AppConstant::NUMERIC_TWO;
                    $sessionData['useed'] = $check;
                    $sessionContent = base64_encode(serialize($sessionData));
                } else if ($params['access']==AppConstant::NUMERIC_FIVE) { //mathjax experimental
                    //deprecated, as mathjax is now default
                    $sessionData['mathdisp'] = AppConstant::NUMERIC_ONE;
                    $sessionData['graphdisp'] = $params['graphdisp'];
                    $sessionData['useed'] = $check;
                    $sessionContent = base64_encode(serialize($sessionData));
                } else if (!empty($params['isok'])) {
                    $sessionData['mathdisp'] = AppConstant::NUMERIC_ONE;
                    $sessionData['graphdisp'] = AppConstant::NUMERIC_ONE;
                    $sessionData['useed'] = $check;
                    $sessionContent = base64_encode(serialize($sessionData));
                } else {
                    $sessionData['mathdisp'] = AppConstant::NUMERIC_TWO-$params['mathdisp'];
                    $sessionData['graphdisp'] = $params['graphdisp'];
                    $sessionData['useed'] = $check;
                    $sessionContent = base64_encode(serialize($sessionData));
                }
                $session = new Sessions();
                if (isset($params['tzname']) && strpos(basename($_SERVER['PHP_SELF']),'upgrade.php')===false) {
                    $session->createSession($sessionId,$userId,$now,$params['tzoffset'],$params['tzname'],$sessionContent);
                } else {
                    $session->createSession($sessionId,$userId,$now,$params['tzoffset'],'',$sessionContent);
                }
                if (isset($CFG['GEN']['newpasswords']) && strlen($haveSession['password'])==AppConstant::NUMERIC_THIRTY_TWO) { //old password - rehash it
                    $hashPassword = AppUtility::passwordHash($params['password']);;
                    User::updateUser($now,$hashPassword,$userId);
                } else {
                    User::updateUser($now,'',$userId);
                }
                return $this->redirect('dashboard');
            } else {
                if (empty($_SESSION['challenge'])) {
                    $badSession = true;
                } else {
                    $badSession = false;
                }
            }
        }
        if ($hasUserName) {
            $userData = User::getById($userId);
            $userName = $userData['SID'];
            $myRights = $userData['rights'];
            $groupId = $userData['groupid'];
            $userDefLibrary = $userData['deflib'];
            if (strpos(basename($_SERVER['PHP_SELF']),'upgrade.php')===false) {
                $listPerPage = $userData['listperpage'];
                $selfHasUserImage = $userData['hasuserimg'];
            }
            $userFullName = $userData['FirstName'] . ' ' . $userData['LastName'];
            $previewShift = -AppConstant::NUMERIC_ONE;
            $basePhysicalDir = rtrim(dirname(__FILE__), '/\\');
            if ($myRights==AppConstant::ADMIN_RIGHT && (isset($params['debug']) || isset($sessionData['debugmode']))) {
                ini_set('display_errors',AppConstant::NUMERIC_ONE);
                error_reporting(E_ALL ^ E_NOTICE);
                if (isset($params['debug'])) {
                    $sessionData['debugmode'] = true;
                    $this->writesessiondata($sessionData,$sessionId);
                }
            }
            if (isset($params['fullwidth'])) {
                $sessionData['usefullwidth'] = true;
                $useFullWidth = true;
                $this->writesessiondata($sessionData,$sessionId);
            } else if (isset($sessionData['usefullwidth'])) {
                $useFullWidth = true;
            }

            if (isset($params['mathjax'])) {
                $sessionData['mathdisp'] = AppConstant::NUMERIC_ONE;
                $this->writesessiondata($sessionData,$sessionId);
            }

            if (isset($params['readernavon'])) {
                $sessionData['readernavon'] = true;
                $this->writesessiondata($sessionData,$sessionId);
            }
            if (isset($params['useflash'])) {
                $sessionData['useflash'] = true;
                $this->writesessiondata($sessionData,$sessionId);
            }
            if (isset($sessionData['isdiag']) && strpos(basename($_SERVER['PHP_SELF']),'show-assessment')===false) {
            }
            if (isset($sessionData['ltiitemtype'])) {
                $flexWidth = true;
                if ($sessionData['ltiitemtype']==AppConstant::NUMERIC_ONE) {
                    if (strpos(basename($_SERVER['PHP_SELF']),'show-assessment')===false && isset($params['cid']) && $sessionData['ltiitemid']!=$params['cid']) {
                        echo "You do not have access to this page";
                        if($myRights < AppConstant::TEACHER_RIGHT){
                            echo "<a href='".AppUtility::getURLFromHome('course','course/index?cid='.$sessionData['ltiitemid'])."'>Return to home page</a>";
                        }else{
                            echo "<a href='".AppUtility::getURLFromHome('instructor','instructor/index?cid='.$sessionData['ltiitemid'])."'>Return to home page</a>";
                        }
                        exit;
                    }
                } else if ($sessionData['ltiitemtype']==AppConstant::NUMERIC_ZERO && $sessionData['ltirole']=='learner') {
                    $urlParts = parse_url($_SERVER['PHP_SELF']);
                    if (!in_array(basename($urlParts['path']),array('show-assessment','print-test','messages','sent-message','view-message','view-conversation','work-in-progress','work-in-progress','work-in-progress'))) {
                        $assessment = Assessments::getByAssessmentId($sessionData['ltiitemid']);
                        $courseId = $assessment['id'];
                        AppUtility::getURLFromHome('instructor','instructor/index?cid='.$courseId.'&id='.$sessionData['ltiitemid']);
                        exit;
                    }
                } else if ($sessionData['ltirole']=='instructor') {
                    $breadcrumbBase = "<a href='".AppUtility::getURLFromHome('site','login')."'>LTI Home</a> &gt; ";
                } else {
                    $breadcrumbBase = '';
                }
            } else {
                $breadcrumbBase = "<a href='".AppUtility::getURLFromHome('site','login')."'>Home</a> &gt; ";
            }
            if ((isset($params['cid']) && $params['cid']!="admin" && $params['cid']>AppConstant::NUMERIC_ZERO) || (isset($sessionData['courseid']) && strpos(basename($_SERVER['PHP_SELF']),'show-assessment')!==false)) {
                if (isset($params['cid'])) {
                    $courseId = $params['cid'];
                } else {
                    $courseId = $sessionData['courseid'];
                }

                $studentData = Student::getByCourseId($courseId,$userId);
                if ($studentData != null) {
                    $studentId = $studentData['id'];
                    $studentInfo['timelimitmult'] = $studentData['timelimitmult'];
                    $studentInfo['section'] = $studentData['section'];
                    if ($studentData['locked']>AppConstant::NUMERIC_ZERO) {
                        echo "<p>You have been locked out of this course by your instructor.  Please see your instructor for more information.</p>";
                        return AppUtility::getURLFromHome('site','dashboard');
                    } else {
                        $now = time();
                        if (!isset($sessionData['lastaccess'.$courseId])) {
                            Student::setLastAccess($studentId,$now);
                            $sessionData['lastaccess'.$courseId] = $now;
                            $loginLog = new LoginLog();
                            $logId = $loginLog->createLog($userId,$courseId,$now);
                            $sessionData['loginlog'.$courseId] = $logId;
                            $this->writesessiondata($sessionData,$sessionId);
                        } else if (isset($CFG['GEN']['keeplastactionlog'])) {
                            LoginLog::setLastAction($sessionData['loginlog'.$courseId],$now);
                        }
                    }
                } else {
                    $teacherData = Teacher::getByUserId($userId,$courseId);
                    if ($teacherData != null) {
                        if ($myRights>AppConstant::STUDENT_RIGHT) {
                            $teacherId = $teacherData['id'];
                            if (isset($params['stuview'])) {
                                $sessionData['stuview'] = $params['stuview'];
                                $this->writesessiondata($sessionData,$sessionId);
                            }
                            if (isset($params['teachview'])) {
                                unset($sessionData['stuview']);
                                $this->writesessiondata($sessionData,$sessionId);
                            }
                            if (isset($sessionData['stuview'])) {
                                $previewShift = $sessionData['stuview'];
                                unset($teacherId);
                                $studentId = $teacherData['id'];
                            }
                        } else {
                            $tutorId = $teacherData['id'];
                        }
                    } else if ($myRights==AppConstant::ADMIN_RIGHT) {
                        $teacherId = $userId;
                        $adminAsTeacher = true;
                    } else {

                        $tutorData = Tutor::getByUserId($userId,$courseId);
                        if ($tutorData != null) {
                            $tutorId = $tutorData['id'];
                            $tutorSection = trim($tutorData['section']);
                        }

                    }
                }
                $courseData = Course::getByCourseAndUser($courseId);
                if ($courseData>AppConstant::NUMERIC_ZERO) {
                    $crow = $courseData;
                    $courseName = $crow[0]; //mysql_result($result,0,0);
                    $courseTheme = $crow[5]; //mysql_result($result,0,5);
                    $courseNewFlag = $crow[6]; //mysql_result($result,0,6);
                    $courseMsgSet = $crow[7]%AppConstant::NUMERIC_FIVE;
                    $courseTopbar = explode('|',$crow[8]);
                    $courseTopbar[0] = explode(',',$courseTopbar[0]);
                    $courseTopbar[1] = explode(',',$courseTopbar[1]);
                    $courseToolset = $crow[9];
                    $courseDefTime = $crow[10]%10000;
                    if ($crow[10]>10000) {
                        $courseDefSTime = floor($crow[10]/10000);
                    } else {
                        $courseDefSTime = $courseDefTime;
                    }
                    $picIcons = $crow[11];
                    if (!isset($courseTopbar[2])) { $courseTopbar[2] = AppConstant::NUMERIC_ZERO;}
                    if ($courseTopbar[0][0] == null) {unset($courseTopbar[0][0]);}
                    if ($courseTopbar[1][0] == null) {unset($courseTopbar[1][0]);}
                    if (isset($studentId) && $previewShift==-AppConstant::NUMERIC_ONE && (($crow[1])&AppConstant::NUMERIC_ONE)==AppConstant::NUMERIC_ONE) {
                        echo "This course is not available at this time";
                        exit;
                    }
                    $lockAssessId = $crow[2]; //ysql_result($result,0,2);
                    if (isset($studentId) && $lockAssessId>AppConstant::NUMERIC_ZERO) {
                        if (strpos(basename($_SERVER['PHP_SELF']),'show-assessment')===false) {
                            echo '<p>This course is currently locked for an assessment</p>';
                            echo "<p><a href='".AppUtility::getURLFromHome('assessment','assessment/show-assessment?cid='.$courseId.'&id='.$lockAssessId)."'>Go to Assessment</a> | <a href='".AppUtility::getURLFromHome('site','login')."'>Go Back</a></p>";
                            exit;
                        }
                    }
                    unset($lockAssessId);
                    if ($myRights==75 && !isset($teacherId) && !isset($studentId) && $crow[4]==$groupId) {
                        //group admin access
                        $teacherId = $userId;
                        $adminAsTeacher = true;
                    } else if ($myRights>19 && !isset($teacherId) && !isset($studentId) && !isset($tutorId) && $previewShift==-AppConstant::NUMERIC_ONE) {
                        if ($crow[3]==2) {
                            $guestId = $userId;
                        } else if ($crow[3]==AppConstant::NUMERIC_ONE && $crow[4]==$groupId) {
                            $guestId = $userId;
                        }
                    }
                }
            }
            $verified = true;
        }

        if (!isset($courseName)) {
            $courseName = "Course Page";
        }
    }

function writesessiondata($sessionData,$sessionId) {
    $sessionContent = base64_encode(serialize($sessionData));
    Sessions::setSessionId($sessionId,$sessionContent);
}
function checkeditorok() {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($ua,'iPhone')!==false || strpos($ua,'iPad')!==false) {
        preg_match('/OS (\d+)_(\d+)/',$ua,$match);
        if ($match[1]>=5) {
            return AppConstant::NUMERIC_ONE;
        } else {
            return AppConstant::NUMERIC_ZERO;
        }
    } else if (strpos($ua,'Android')!==false) {
        preg_match('/Android\s+(\d+)((?:\.\d+)+)\b/',$ua,$match);
        if ($match[1]>=4) {
            return AppConstant::NUMERIC_ONE;
        } else {
            return AppConstant::NUMERIC_ZERO;
        }
    } else {
        return AppConstant::NUMERIC_ONE;
    }
}
function stripslashes_deep($value) {
    return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
}

function generaterandstring() {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $pass = '';
    for ($i=AppConstant::NUMERIC_ZERO;$i<10;$i++) {
        $pass .= substr($chars,rand(AppConstant::NUMERIC_ZERO,61),AppConstant::NUMERIC_ONE);
    }
    return $pass;
}

   public function getSessionId(){
       return Yii::$app->session->getId();
   }

    public function getSessionData($sessionId){
        $session = Sessions::getById($sessionId);
        return unserialize(base64_decode($session['sessiondata']));
    }

    public function setSessionData($key,$value){
        return Yii::$app->session->set($key,$value);
    }

    public function getNotificationDataMessage($courseId,$user)
    {
        $message = Message::getByCourseIdAndUserId($courseId, $user->id);
        $isReadArray = array(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR, AppConstant::NUMERIC_EIGHT, AppConstant::NUMERIC_TWELVE);
        $msgList = array();
        if ($message) {
            foreach ($message as $singleMessage) {
                if (in_array($singleMessage->isread, $isReadArray))
                    array_push($msgList, $singleMessage);
            }
        }
        return count($msgList);
    }

    public function getNotificationDataForum($courseId,$user)
    {
        $NewPostCounts = Thread::findNewPostCnt($courseId, $user);
        $countPost = AppConstant::NUMERIC_ZERO;
        foreach ($NewPostCounts as $count) {
            $countPost = $countPost + $count['COUNT(imas_forum_threads.id)'];
        }
        return $countPost;
    }

    public function isTeacher($userId, $courseId){
        $isTeacher = false;
        $teacher = Teacher::getByUserId($userId,$courseId);
        if($teacher){
            $isTeacher = true;
        }
        return $isTeacher ;
    }

    public function isTutor($userId, $courseId){
        $isTutor = false;
        $tutor = Tutor::getByUserId($userId,$courseId);
        if($tutor){
            $isTutor = true;
        }
        return $isTutor ;
    }

    public static  function customizeDate($data)
    {
        return date('F d, o g:i a', $data);
    }

    public static function  dateToString()
    {
        return strtotime(date('F d, o g:i a'));

    }

    public function noValidRights($teacherId)
    {
        if(!$teacherId)
        {
            $this->setWarningFlash(AppConstant::NO_TEACHER_RIGHTS);
            return $this->redirect($this->goHome());
        }
        return true;
    }

    public function accessForAdmin($rights){
        if ($rights != AppConstant::ADMIN_RIGHT) {
            $this->setWarningFlash(AppConstant::REQUIRED_ADMIN_ACCESS);
            return $this->redirect($this->goHome());
        }
        return true;
    }

    public function accessForTeacher($user,$courseId){
        $teacherId = $this->isTeacher($user['id'], $courseId);
        if (($user['rights'] < AppConstant::TEACHER_RIGHT) || ($user['rights'] > AppConstant::STUDENT_RIGHT && !$teacherId)) {
            return $this->noValidRights($teacherId);
        }
        return true;
    }

    public function accessForTeacherAndStudent($user,$courseId,$actionPath){
        $teacherId = $this->isTeacher($user['id'], $courseId);
        if($user['rights'] == AppConstant::STUDENT_RIGHT && $actionPath == 'grade-book-student-detail'){
            return true;
        }else if (($user['rights'] < AppConstant::TEACHER_RIGHT) || ($user['rights'] > AppConstant::STUDENT_RIGHT && !$teacherId)) {
            return $this->noValidRights($teacherId);
        }else{
            return true;
        }
    }

    public function accessForRightsMoreThanStudent($rights){
        if ($rights < AppConstant::LIMITED_COURSE_CREATOR_RIGHT) {
            $this->setWarningFlash(AppConstant::UNAUTHORIZED);
            return $this->redirect($this->goHome());
        }
        return true;
    }
}