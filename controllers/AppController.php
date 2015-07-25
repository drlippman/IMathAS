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
use app\models\Course;
use app\models\DbSchema;
use app\models\Sessions;
use app\models\Student;
use app\models\User;
use yii\web\Controller;
use app\models\Teacher;
use yii\web\Session;
use Yii;

class AppController extends Controller
{

    public $enableCsrfValidation = false;


    function getBodyParams()
    {
        return $_POST;
    }

    function getRequestParams()
    {
        return $_REQUEST;
    }

    function getParam($key)
    {
        return $_REQUEST[$key];
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
        for($i = 0; $i < $cnt; $i++){
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

    public function getPostData()
    {
        return Yii::$app->request->post();
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
        isset($key) ? $key : $defaultVal;
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

    public function userAuthentication($user,$courseId){
        if($user->rights == AppConstant::STUDENT_RIGHT){
            $student = Student::getByCourseId($courseId, $user->id);
            if ($student == ''){

                $this->goBack();
               return $this->setErrorFlash(AppConstant::UNAUTHORIZED_ACCESS);
            }
        }else{
            $teacher = Teacher::getByUserId($user->id,$courseId);
            if($teacher == ''){
                $this->setErrorFlash(AppConstant::UNAUTHORIZED_ACCESS);
                $this->goBack();
            }
        }
    }

    public function checkSession($params){
        $session = Yii::$app->session;
        if ($session->isActive){
            $session->close();
        }
        $session->open();
        $sessionId = $session->getId();
        if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
            $urlmode = 'https://';
        } else {
            $urlmode = 'http://';
        }
        $randomString = $this->generaterandstring();
        $check =$this->checkeditorok();
        /*
         * check for bad session ids.
         */
        if (strlen($sessionId)< AppConstant::NUMERIC_TEN) {
            if (function_exists('session_regenerate_id')) {
                $sessionId = $session->regenerateID();
            }
            AppUtility::getURLFromHome('site','login');
        }
        $sessionData = array();
        $haveSession = Sessions::getById($sessionId);
        if ($haveSession > AppConstant::NUMERIC_ZERO) {
            $userid = $haveSession['userid'];
            $tzoffset = $haveSession['tzoffset'];
            $tzname = '';
            if (isset($haveSession['tzname']) && $haveSession['tzname']!='') {
                if (date_default_timezone_set($haveSession['tzname'])) {
                    $tzname = $haveSession['tzname'];
                }
            }
            $enc = $haveSession['sessiondata'];
            if ($enc!='0') {
                $sessiondata = unserialize(base64_decode($enc));
                /*
                 * delete own session if old and not posting
                 */
                if ((time()-$haveSession['time'])>AppConstant::MAX_SESSION_TIME && (!isset($params) || count($params)==AppConstant::NUMERIC_ZERO)) {
                    Sessions::deleteSession($userid);
                    unset($userid);
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
                $enc = base64_encode(serialize($sessionData));
                Sessions::setSessionId($sessionId,$enc);
                AppUtility::getURLFromHome('site','work-in-progress');
            }
        }
        $hasusername = isset($userid);
        $haslogin = isset($params['LoginForm']['password']);
        if (!$hasusername && !$haslogin && isset($params['guestaccess']) && isset($CFG['GEN']['guesttempaccts'])) {
            $haslogin = true;
            $params['username']='guest';
            $params['mathdisp'] = AppConstant::NUMERIC_ZERO;
            $params['graphdisp'] = AppConstant::NUMERIC_TWO;
        }
        if (isset($params['checksess']) && !$hasusername) {
            echo '<html><body>';
            echo 'Unable to establish a session. This is most likely caused by your browser blocking third-party cookies.  Please adjust your browser settings and try again.';
            echo '</body></html>';
            exit;
        }
        $verified = false;  $err = '';
        //Just put in username and password, trying to log in
        if ($haslogin && !$hasusername) {
            //clean up old sessions
            $now = time();
            $old = $now - AppConstant::GIVE_OLD_SESSION_TIME;
            Sessions::deleteByTime($old);

            if (isset($CFG['GEN']['guesttempaccts']) && $params['LoginForm']['username']=='guest') { // create a temp account when someone logs in w/ username: guest
                $dbData = DbSchema::getById(AppConstant::NUMERIC_TWO);
                $guestcnt = $dbData['id'];
                DbSchema::setById(AppConstant::NUMERIC_TWO);
                if (isset($CFG['GEN']['homelayout'])) {
                    $homelayout = $CFG['GEN']['homelayout'];
                } else {
                    $homelayout = '|0,1,2||0,1';
                }
                $userArray = array(
                    'SID' => 'guestacct'.$guestcnt,
                    'password' => '',
                    'rights' => 5,
                    'FirstName' => 'Guest',
                    'LastName' => 'Account',
                    'email' => 'none@none.com',
                    'msgnotify' => 0,
                    'homelayout' => $homelayout
                );
                $user = new User();
                $userid = $user->saveGuestUserRecord($userArray);
                $query = Course::getByAvailable($params);
                if ($query > 0) {
//                    foreach($query as $singfleData){
//                        $singfleData
//                    }
//                    $query = Student::
//                        "INSERT INTO imas_students (userid,courseid) VALUES ";
//                    $i = 0;
//                    while ($row = mysql_fetch_row($result)) {
//                        if ($i>0) {
//                            $query .= ',';
//                        }
//                        $query .= "($userid,{$row[0]})";
//                        $i++;
//                    }
//                    mysql_query($query) or die("Query failed : " . mysql_error());
                }

                $haveSession['id'] = $userid;
                $haveSession['rights'] = 5;
                $haveSession['groupid'] = 0;
                $params['password'] = 'temp';
                if (isset($CFG['GEN']['newpasswords'])) {
                    $haveSession['password'] =  AppUtility::passwordHash($params['password']);
                } else {
                    $haveSession['password'] = md5('temp');
                }
                $params['usedetected'] = true;
            } else {
                $haveSession = User::getByName($params['LoginForm']['username']);
            }

            if (($haveSession != null) && (
                    ((!isset($CFG['GEN']['newpasswords']) || $CFG['GEN']['newpasswords']!='only') && ((md5($haveSession['password'].$_SESSION['challenge']) == $params['password']) ||($haveSession['password'] == md5($params['password']))))
                    || (isset($CFG['GEN']['newpasswords']) && password_verify($params['password'],$haveSession['password']))	)) {
                unset($_SESSION['challenge']); //challenge is used up - forget it.
                $userid = $haveSession['id'];
                $groupid = $haveSession['groupid'];
                if ($haveSession['rights']==0) {
                    echo "You have not yet confirmed your registration.  You must respond to the email ";
                    echo "that was sent to you by IMathAS.";
                    exit;
                }

                $sessionData['useragent'] = $_SERVER['HTTP_USER_AGENT'];
                $sessionData['ip'] = $_SERVER['REMOTE_ADDR'];

                $sessionData['secsalt'] = $randomString();
                if ($params['access']==1) { //text-based
                    $sessionData['mathdisp'] = $params['mathdisp']; //to allow for accessibility
                    $sessionData['graphdisp'] = 0;
                    $sessionData['useed'] = 0;
                    $enc = base64_encode(serialize($sessionData));
                } else if ($params['access']==2) { //img graphs
                    //deprecated
                    $sessionData['mathdisp'] = 2-$params['mathdisp'];
                    $sessionData['graphdisp'] = 2;
                    $sessionData['useed'] = $check;
                    $enc = base64_encode(serialize($sessionData));
                } else if ($params['access']==4) { //img math
                    //deprecated
                    $sessionData['mathdisp'] = 2;
                    $sessionData['graphdisp'] = $params['graphdisp'];
                    $sessionData['useed'] = $check;
                    $enc = base64_encode(serialize($sessionData));
                } else if ($params['access']==3) { //img all
                    $sessionData['mathdisp'] = 2;
                    $sessionData['graphdisp'] = 2;
                    $sessionData['useed'] = $check;
                    $enc = base64_encode(serialize($sessionData));
                } else if ($params['access']==5) { //mathjax experimental
                    //deprecated, as mathjax is now default
                    $sessionData['mathdisp'] = 1;
                    $sessionData['graphdisp'] = $params['graphdisp'];
                    $sessionData['useed'] = $check;
                    $enc = base64_encode(serialize($sessionData));
                } else if (!empty($params['isok'])) {
                    $sessionData['mathdisp'] = 1;
                    $sessionData['graphdisp'] = 1;
                    $sessionData['useed'] = $check;
                    $enc = base64_encode(serialize($sessionData));
                } else {
                    $sessionData['mathdisp'] = 2-$params['mathdisp'];
                    $sessionData['graphdisp'] = $params['graphdisp'];
                    $sessionData['useed'] = $check;
                    $enc = base64_encode(serialize($sessionData));
                }
                if (isset($params['tzname']) && strpos(basename($_SERVER['PHP_SELF']),'upgrade.php')===false) {
                    $session = new Sessions();
                    $session->createSession($sessionId,$userid,$now,$params['tzoffset'],$params['tzname'],$enc);
                } else {
                    $session->createSession($sessionId,$userid,$now,$params['tzoffset'],'',$enc);
                }
                if (isset($CFG['GEN']['newpasswords']) && strlen($haveSession['password'])==32) { //old password - rehash it
                    $hashpw = AppUtility::passwordHash($params['password']);;
                    User::updateUser($now,$hashpw,$userid);
                } else {
                    User::updateUser($now,'',$userid);
                }

//                if (isset($_SERVER['QUERY_STRING'])) {
//                    $querys = '?'.$_SERVER['QUERY_STRING'].(isset($addtoquerystring)?'&'.$addtoquerystring:'');
//                } else {
//                    $querys = (isset($addtoquerystring)?'?'.$addtoquerystring:'');
//                }
//                header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . $querys);
            } else {
                if (empty($_SESSION['challenge'])) {
                    $badsession = true;
                } else {
                    $badsession = false;
                }
            }

        }

        if ($hasusername) {
            //$username = $_COOKIE['username'];
            $query = "SELECT SID,rights,groupid,LastName,FirstName,deflib";
            if (strpos(basename($_SERVER['PHP_SELF']),'upgrade.php')===false) {
                $query .= ',listperpage,hasuserimg';
            }
            $query .= " FROM imas_users WHERE id='$userid'";
            $result = mysql_query($query) or die("Query failed : " . mysql_error());
            $line = mysql_fetch_array($result, MYSQL_ASSOC);
            $username = $line['SID'];
            $myrights = $line['rights'];
            $groupid = $line['groupid'];
            $userdeflib = $line['deflib'];
            $listperpage = $line['listperpage'];
            $selfhasuserimg = $line['hasuserimg'];
            $userfullname = $line['FirstName'] . ' ' . $line['LastName'];
            $previewshift = -1;
            $basephysicaldir = rtrim(dirname(__FILE__), '/\\');
            if ($myrights==100 && (isset($_GET['debug']) || isset($sessiondata['debugmode']))) {
                ini_set('display_errors',1);
                error_reporting(E_ALL ^ E_NOTICE);
                if (isset($_GET['debug'])) {
                    $sessiondata['debugmode'] = true;
                    writesessiondata();
                }
            }
            if (isset($_GET['fullwidth'])) {
                $sessiondata['usefullwidth'] = true;
                $usefullwidth = true;
                writesessiondata();
            } else if (isset($sessiondata['usefullwidth'])) {
                $usefullwidth = true;
            }

            if (isset($_GET['mathjax'])) {
                $sessiondata['mathdisp'] = 1;
                writesessiondata();
            }

            if (isset($_GET['readernavon'])) {
                $sessiondata['readernavon'] = true;
                writesessiondata();
            }
            if (isset($_GET['useflash'])) {
                $sessiondata['useflash'] = true;
                writesessiondata();
            }
            if (isset($sessiondata['isdiag']) && strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false) {
                header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php");
            }
            if (isset($sessiondata['ltiitemtype'])) {
                $flexwidth = true;
                if ($sessiondata['ltiitemtype']==1) {
                    if (strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false && isset($_GET['cid']) && $sessiondata['ltiitemid']!=$_GET['cid']) {
                        echo "You do not have access to this page";
                        echo "<a href=\"$imasroot/course/course.php?cid={$sessiondata['ltiitemid']}\">Return to course page</a>";
                        exit;
                    }
                } else if ($sessiondata['ltiitemtype']==0 && $sessiondata['ltirole']=='learner') {
                    $breadcrumbbase = "<a href=\"$imasroot/assessment/showtest.php?cid={$_GET['cid']}&id={$sessiondata['ltiitemid']}\">Assignment</a> &gt; ";
                    $urlparts = parse_url($_SERVER['PHP_SELF']);
                    if (!in_array(basename($urlparts['path']),array('showtest.php','printtest.php','msglist.php','sentlist.php','viewmsg.php','msghistory.php','redeemlatepass.php','gb-viewasid.php','showsoln.php'))) {
                        //if (strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false && strpos(basename($_SERVER['PHP_SELF']),'printtest.php')===false && strpos(basename($_SERVER['PHP_SELF']),'msglist.php')===false && strpos(basename($_SERVER['PHP_SELF']),'sentlist.php')===false && strpos(basename($_SERVER['PHP_SELF']),'viewmsg.php')===false ) {
                        $query = "SELECT courseid FROM imas_assessments WHERE id='{$sessiondata['ltiitemid']}'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        $cid = mysql_result($result,0,0);
                        header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}");
                        exit;
                    }
                } else if ($sessiondata['ltirole']=='instructor') {
                    $breadcrumbbase = "<a href=\"$imasroot/ltihome.php?showhome=true\">LTI Home</a> &gt; ";
                } else {
                    $breadcrumbbase = '';
                }
            } else {
                $breadcrumbbase = "<a href=\"$imasroot/index.php\">Home</a> &gt; ";
            }

            if ((isset($_GET['cid']) && $_GET['cid']!="admin" && $_GET['cid']>0) || (isset($sessiondata['courseid']) && strpos(basename($_SERVER['PHP_SELF']),'showtest.php')!==false)) {
                if (isset($_GET['cid'])) {
                    $cid = $_GET['cid'];
                } else {
                    $cid = $sessiondata['courseid'];
                }
                $query = "SELECT id,locked,timelimitmult,section FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
                $result = mysql_query($query) or die("Query failed : " . mysql_error());
                $line = mysql_fetch_array($result, MYSQL_ASSOC);
                if ($line != null) {
                    $studentid = $line['id'];
                    $studentinfo['timelimitmult'] = $line['timelimitmult'];
                    $studentinfo['section'] = $line['section'];
                    if ($line['locked']>0) {
                        require("header.php");
                        echo "<p>You have been locked out of this course by your instructor.  Please see your instructor for more information.</p>";
                        echo "<p><a href=\"$imasroot/index.php\">Home</a></p>";
                        require("footer.php");
                        exit;
                    } else {
                        $now = time();
                        if (!isset($sessiondata['lastaccess'.$cid])) {
                            $query = "UPDATE imas_students SET lastaccess='$now' WHERE id=$studentid";
                            mysql_query($query) or die("Query failed : " . mysql_error());
                            $sessiondata['lastaccess'.$cid] = $now;
                            $query = "INSERT INTO imas_login_log (userid,courseid,logintime) VALUES ($userid,'$cid',$now)";
                            mysql_query($query) or die("Query failed : " . mysql_error());
                            $sessiondata['loginlog'.$cid] = mysql_insert_id();
                            writesessiondata();
                        } else if (isset($CFG['GEN']['keeplastactionlog'])) {
                            $query = "UPDATE imas_login_log SET lastaction=$now WHERE id=".$sessiondata['loginlog'.$cid];
                            mysql_query($query) or die("Query failed : " . mysql_error());
                        }
                    }
                } else {
                    $query = "SELECT id FROM imas_teachers WHERE userid='$userid' AND courseid='$cid'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    $line = mysql_fetch_array($result, MYSQL_ASSOC);
                    if ($line != null) {
                        if ($myrights>19) {
                            $teacherid = $line['id'];
                            if (isset($_GET['stuview'])) {
                                $sessiondata['stuview'] = $_GET['stuview'];
                                writesessiondata();
                            }
                            if (isset($_GET['teachview'])) {
                                unset($sessiondata['stuview']);
                                writesessiondata();
                            }
                            if (isset($sessiondata['stuview'])) {
                                $previewshift = $sessiondata['stuview'];
                                unset($teacherid);
                                $studentid = $line['id'];
                            }
                        } else {
                            $tutorid = $line['id'];
                        }
                    } else if ($myrights==100) {
                        $teacherid = $userid;
                        $adminasteacher = true;
                    } else {

                        $query = "SELECT id,section FROM imas_tutors WHERE userid='$userid' AND courseid='$cid'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        $line = mysql_fetch_array($result, MYSQL_ASSOC);
                        if ($line != null) {
                            $tutorid = $line['id'];
                            $tutorsection = trim($line['section']);
                        }

                    }
                }
                $query = "SELECT imas_courses.name,imas_courses.available,imas_courses.lockaid,imas_courses.copyrights,imas_users.groupid,imas_courses.theme,imas_courses.newflag,imas_courses.msgset,imas_courses.topbar,imas_courses.toolset,imas_courses.deftime,imas_courses.picicons ";
                $query .= "FROM imas_courses,imas_users WHERE imas_courses.id='$cid' AND imas_users.id=imas_courses.ownerid";
                $result = mysql_query($query) or die("Query failed : " . mysql_error());
                if (mysql_num_rows($result)>0) {
                    $crow = mysql_fetch_row($result);
                    $coursename = $crow[0]; //mysql_result($result,0,0);
                    $coursetheme = $crow[5]; //mysql_result($result,0,5);
                    $coursenewflag = $crow[6]; //mysql_result($result,0,6);
                    $coursemsgset = $crow[7]%5;
                    $coursetopbar = explode('|',$crow[8]);
                    $coursetopbar[0] = explode(',',$coursetopbar[0]);
                    $coursetopbar[1] = explode(',',$coursetopbar[1]);
                    $coursetoolset = $crow[9];
                    $coursedeftime = $crow[10]%10000;
                    if ($crow[10]>10000) {
                        $coursedefstime = floor($crow[10]/10000);
                    } else {
                        $coursedefstime = $coursedeftime;
                    }
                    $picicons = $crow[11];
                    if (!isset($coursetopbar[2])) { $coursetopbar[2] = 0;}
                    if ($coursetopbar[0][0] == null) {unset($coursetopbar[0][0]);}
                    if ($coursetopbar[1][0] == null) {unset($coursetopbar[1][0]);}
                    if (isset($studentid) && $previewshift==-1 && (($crow[1])&1)==1) {
                        echo "This course is not available at this time";
                        exit;
                    }
                    $lockaid = $crow[2]; //ysql_result($result,0,2);
                    if (isset($studentid) && $lockaid>0) {
                        if (strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false) {
                            require("header.php");
                            echo '<p>This course is currently locked for an assessment</p>';
                            echo "<p><a href=\"$imasroot/assessment/showtest.php?cid=$cid&id=$lockaid\">Go to Assessment</a> | <a href=\"$imasroot/index.php\">Go Back</a></p>";
                            require("footer.php");
                            //header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id=$lockaid");
                            exit;
                        }
                    }
                    unset($lockaid);
                    if ($myrights==75 && !isset($teacherid) && !isset($studentid) && $crow[4]==$groupid) {
                        //group admin access
                        $teacherid = $userid;
                        $adminasteacher = true;
                    } else if ($myrights>19 && !isset($teacherid) && !isset($studentid) && !isset($tutorid) && $previewshift==-1) {
                        if ($crow[3]==2) {
                            $guestid = $userid;
                        } else if ($crow[3]==1 && $crow[4]==$groupid) {
                            $guestid = $userid;
                        }
                    }
                }
            }
            $verified = true;
        }

        if (!$verified) {
            if (!isset($skiploginredirect) && strpos(basename($_SERVER['SCRIPT_NAME']),'directaccess.php')===false) {
                if (!isset($loginpage)) {
                    $loginpage = "loginpage.php";
                }
                require($loginpage);
                exit;
            }
        }

        if (!isset($coursename)) {
            $coursename = "Course Page";
        }
    }


function tzdate($string,$time) {
    global $tzoffset, $tzname;
    //$dstoffset = date('I',time()) - date('I',$time);
    //return gmdate($string, $time-60*($tzoffset+60*$dstoffset));
    if ($tzname != '') {
        return date($string, $time);
    } else {
        $serveroffset = date('Z') + $tzoffset*60;
        return date($string, $time-$serveroffset);
    }
    //return gmdate($string, $time-60*$tzoffset);
}

function writesessiondata() {
    global $sessiondata,$sessionid;
    $enc = base64_encode(serialize($sessiondata));
    $query = "UPDATE imas_sessions SET sessiondata='$enc' WHERE sessionid='$sessionid'";
    mysql_query($query) or die("Query failed : " . mysql_error());
}
function checkeditorok() {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($ua,'iPhone')!==false || strpos($ua,'iPad')!==false) {
        preg_match('/OS (\d+)_(\d+)/',$ua,$match);
        if ($match[1]>=5) {
            return 1;
        } else {
            return 0;
        }
    } else if (strpos($ua,'Android')!==false) {
        preg_match('/Android\s+(\d+)((?:\.\d+)+)\b/',$ua,$match);
        if ($match[1]>=4) {
            return 1;
        } else {
            return 0;
        }
    } else {
        return 1;
    }
}
function stripslashes_deep($value) {
    return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
}

function generaterandstring() {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $pass = '';
    for ($i=0;$i<10;$i++) {
        $pass .= substr($chars,rand(0,61),1);
    }
    return $pass;
}
}