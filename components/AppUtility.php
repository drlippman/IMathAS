<?php

namespace app\components;

use app\controllers\AppController;
use app\controllers\course\CourseController;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Exceptions;
use app\models\ForumPosts;
use app\models\Forums;
use app\models\InlineText;
use app\models\Items;
use app\models\LinkedText;
use app\models\QImages;
use app\models\Questions;
use app\models\Course;
use app\models\QuestionSet;
use app\models\Sessions;
use app\models\Student;
use app\models\User;
use app\models\Wiki;
use Yii;
use yii\base\Component;
use app\models\Links;

require_once("../filter/filter.php");

class AppUtility extends Component
{
    /**
     * Function to print data and exit the process.
     * It prints the data value which is passed as argument.
     * @param $data
     */
    public static function dump($data)
    {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        die;
    }

    /**
     * This is utility function to generate random string.
     * @return string
     */
    public static function generateRandomString()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $pass = '';
        for ($i = AppConstant::NUMERIC_ZERO; $i < AppConstant::NUMERIC_TEN; $i++) {
            $pass .= substr($chars, rand(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_SIXTY_ONE), AppConstant::NUMERIC_ONE);
        }
        return $pass;
    }

    public static function getStringVal($str)
    {
        return isset($str) ? $str : null;
    }

    public static function getIntVal($str)
    {
        return isset($str) ? $str : AppConstant::NUMERIC_ZERO;
    }

    public static function getURLFromHome($controllerName, $shortURL)
    {
        return self::getHomeURL() . $controllerName . "/" . $shortURL;
    }

    public static function getHomeURL()
    {
        return Yii::$app->homeUrl;
    }

    public static function includeCSS($cssFile)
    {
        echo "<link rel='stylesheet' type='text/css' href='" . AppUtility::getHomeURL() . "css/" . $cssFile . "?ver=" . AppConstant::VERSION_NUMBER . "'/>";
    }

    public static function includeJS($jsFile)
    {
        echo "<script type='text/javascript' src='" . AppUtility::getHomeURL() . "js/" . $jsFile . "?ver=" . AppConstant::VERSION_NUMBER . "'></script>";
    }

    public static function getAssetURL()
    {
        return self::getHomeURL();
    }

    public static function getTimeStampFromDate($dateStr)
    {
        $a = strptime($dateStr, '%m/%d/%Y');
        $timestamp = mktime(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ZERO, $a['tm_mon'] + AppConstant::NUMERIC_ONE, $a['tm_mday'], $a['tm_year'] + AppConstant::NUMERIC_ONE_THOUSAND_NINE_HUNDRED);
        return $timestamp;
    }

    public static function checkEditOrOk()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false) {
            preg_match('/OS (\d+)_(\d+)/', $ua, $match);
            if ($match[1] >= AppConstant::NUMERIC_FIVE) {
                return AppConstant::NUMERIC_ONE;
            } else {
                return AppConstant::NUMERIC_ZERO;
            }
        } else if (strpos($ua, 'Android') !== false) {
            preg_match('/Android\s+(\d+)((?:\.\d+)+)\b/', $ua, $match);
            if ($match[1] >= AppConstant::NUMERIC_FOUR) {
                return AppConstant::NUMERIC_ONE;
            } else {
                return AppConstant::NUMERIC_ZERO;
            }
        } else {
            return AppConstant::NUMERIC_ONE;
        }
    }

    public static function urlMode()
    {
        $urlmode = 'http://';
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) {
            $urlmode = 'https://';
        }
        return $urlmode;
    }

    public static function removeEmptyAttributes($params)
    {
        if (!empty($params) && is_array($params)) {
            if (is_object($params)) {
                $params = (array)$params;
            }

            foreach ($params as $key => $singleParam) {
                if (empty($singleParam)) {
                    if ($singleParam != AppConstant::ZERO_VALUE)
                        unset($params[$key]);
                }
            }
        }
        return $params;
    }

    public static function verifyPassword($newPassword, $oldPassword)
    {
        require_once("Password.php");
        if (password_verify($newPassword, $oldPassword)) {
            return true;
        }
        return false;
    }

    public static function getFormattedDate($dateStr, $format = 'Y-m-d')
    {
             return date($format, $dateStr);
    }

    public static function getFormattedDateCalendar($dateStr, $format = 'm-d-yy')
    {
        return date($format, $dateStr);
    }


    public static function getFormattedTime($dateStr, $format = 'h:i A')
    {
        return date($format, $dateStr);

    }

    public static function getFullName($first, $last)
    {
        return trim(ucfirst($first) . ' ' . ucfirst($last));
    }

    public static function passwordHash($password)
    {
        require_once("Password.php");
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function makeToolset($params)
    {
        if (is_array($params)) {
            if (count($params) == AppConstant::NUMERIC_THREE)
                return AppConstant::NUMERIC_ZERO;
            elseif (count($params) == AppConstant::NUMERIC_ONE) {
                if ($params[0] == AppConstant::NUMERIC_ONE)
                    return AppConstant::NUMERIC_SIX;
                elseif ($params[0] == AppConstant::NUMERIC_TWO)
                    return AppConstant::NUMERIC_FIVE;
                else
                    return AppConstant::NUMERIC_THREE;
            } elseif (count($params) == AppConstant::NUMERIC_TWO) {
                if (($params[0] == AppConstant::NUMERIC_ONE) && $params[1] == AppConstant::NUMERIC_TWO)
                    return AppConstant::NUMERIC_FOUR;
                elseif (($params[0] == AppConstant::NUMERIC_ONE) && $params[1] == AppConstant::NUMERIC_FOUR)
                    return AppConstant::NUMERIC_TWO;
                else
                    return AppConstant::NUMERIC_ONE;
            }
        } else {
            return $params;
        }
    }


    public static function makeAvailable($availables)
    {
        if (is_array($availables)) {
            if (count($availables) == AppConstant::NUMERIC_TWO)
                return AppConstant::NUMERIC_ZERO;
            else {
                if ($availables[0] == AppConstant::NUMERIC_ONE)
                    return AppConstant::NUMERIC_ONE;
                else
                    return AppConstant::NUMERIC_TWO;
            }
        } else
            return AppConstant::NUMERIC_THREE;
    }

    public static function createIsTemplate($isTemplates)
    {
        $isTemplate = AppConstant::NUMERIC_ZERO;
        if (is_array($isTemplates)) {

            foreach ($isTemplates as $item) {
                if (self::myRight() == AppConstant::ADMIN_RIGHT) {
                    if ($item == AppConstant::NUMERIC_ONE) {
                        $isTemplate += AppConstant::NUMERIC_ONE;
                    }
                    if ($item == AppConstant::NUMERIC_FOUR) {
                        $isTemplate += AppConstant::NUMERIC_FOUR;
                    }
                }
                if (self::myRight() >= AppConstant::GROUP_ADMIN_RIGHT) {
                    if ($item == AppConstant::NUMERIC_TWO) {
                        $isTemplate += AppConstant::NUMERIC_TWO;
                    }
                }
            }
        }
        return $isTemplate;
    }

    public static function createTopBarString($studentQuickPick, $instructorQuickPick, $quickPickBar)
    {
        $studentTopBar = "";
        $instructorTopBar = "";
        if ($studentQuickPick) {
            $studentTopBar = "";
            foreach ($studentQuickPick as $key => $item) {
                if ($studentTopBar == "")
                    $studentTopBar .= $item;
                else
                    $studentTopBar .= ',' . $item;
            }
        }
        if ($instructorQuickPick) {
            $instructorTopBar = "";
            foreach ($instructorQuickPick as $key => $item) {
                if ($instructorTopBar == "")
                    $instructorTopBar .= $item;
                else
                    $instructorTopBar .= ',' . $item;
            }
        }
        $quickPickTopBar = isset($quickPickBar) ? $quickPickBar : AppConstant::NUMERIC_ZERO;
        $topbar = $studentTopBar . '|' . $instructorTopBar . '|' . $quickPickTopBar;
        return $topbar;
    }

    public static function sendMail($subject, $message, $to)
    {
        $email = Yii::$app->mailer->compose();
        $email->setTo($to)
            ->setSubject($subject)
            ->setHtmlBody($message)
            ->send();
    }

    public static function getChallenge()
    {
        return base64_encode(microtime() . rand(AppConstant::NUMERIC_ZERO, AppConstant::QUARTER_NINE));
    }

    public static function getRight($right)
    {
        $returnRight = "";
        switch ($right) {
            case AppConstant::ADMIN_RIGHT:
                $returnRight = 'Admin';
                break;
            case AppConstant::GROUP_ADMIN_RIGHT:
                $returnRight = 'Group Admin';
                break;
            case AppConstant::DIAGNOSTIC_CREATOR_RIGHT:
                $returnRight = 'Diagnostic Creator';
                break;
            case AppConstant::LIMITED_COURSE_CREATOR_RIGHT:
                $returnRight = 'Limited Course Creator';
                break;
            case AppConstant::TEACHER_RIGHT:
                $returnRight = 'Instructor';
                break;
            case AppConstant::STUDENT_RIGHT:
                $returnRight = 'Student';
                break;
            case AppConstant::GUEST_RIGHT:
                $returnRight = 'Guest';
                break;
        }
        return $returnRight;
    }

    public static function calculateTimeDefference($startTime, $endTime)
    {
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/', $endTime, $tmatches);
        if (count($tmatches) == AppConstant::NUMERIC_ZERO) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/', $endTime, $tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = AppConstant::NUMERIC_ZERO;
        }
        $tmatches[1] = $tmatches[1] % AppConstant::NUMERIC_ELEVEN;
        if ($tmatches[3] == "pm") {
            $tmatches[1] += AppConstant::NUMERIC_ELEVEN;
        }
        $deftime = $tmatches[1] * AppConstant::SECONDS + $tmatches[2];

        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/', $startTime, $tmatches);
        if (count($tmatches) == AppConstant::NUMERIC_ZERO) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/', $startTime, $tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = AppConstant::NUMERIC_ZERO;
        }
        $tmatches[1] = $tmatches[1] % AppConstant::NUMERIC_ELEVEN;
        if ($tmatches[3] == "pm") {
            $tmatches[1] += AppConstant::NUMERIC_ELEVEN;
        }
        $deftime += AppConstant::NUMERIC_TEN_THOUSAND * ($tmatches[1] * AppConstant::SECONDS + $tmatches[2]);
        return $deftime;
    }

    public static function tzdate($string, $time)
    {
        $sessionId = Yii::$app->session->getId();
        $sessionData = Sessions::getById($sessionId);
        $tzoffset = $sessionData['tzoffset'];
        $tzname = '';
        if (isset($sessionData['tzname']) && $sessionData['tzname']!='') {
            if (date_default_timezone_set($sessionData['tzname'])) {
                $tzname = $sessionData['tzname'];
            }
        }

        if ($tzname != '') {
            return date($string, $time);
        } else {
            $serveroffset = date('Z') + $tzoffset*60;
            return date($string, $time-$serveroffset);
        }
    }

    public static function formatDate($date)
    {
        return AppUtility::tzdate("D n/j/20y, g:i a", $date);
    }

    public static function calculateTimeToDisplay($deftime)
    {
        $defetime = $deftime % AppConstant::NUMERIC_TEN_THOUSAND;
        $hr = floor($defetime / AppConstant::SECONDS) % AppConstant::NUMERIC_ELEVEN;
        $min = $defetime % AppConstant::SECONDS;
        $am = ($defetime < AppConstant::NUMERIC_ELEVEN * AppConstant::SECONDS) ? 'am' : 'pm';
        $deftimedisp = (($hr == AppConstant::NUMERIC_ZERO) ? AppConstant::NUMERIC_ELEVEN : $hr) . ':' . (($min < AppConstant::NUMERIC_TEN) ? AppConstant::ZERO_VALUE : '') . $min . ' ' . $am;
        if ($deftime > AppConstant::NUMERIC_TEN_THOUSAND) {
            $defstime = floor($deftime / AppConstant::NUMERIC_TEN_THOUSAND);
            $hr = floor($defstime / AppConstant::SECONDS) % AppConstant::NUMERIC_ELEVEN;
            $min = $defstime % AppConstant::SECONDS;
            $am = ($defstime < AppConstant::NUMERIC_ELEVEN * AppConstant::SECONDS) ? 'am' : 'pm';
            $defstimedisp = (($hr == AppConstant::NUMERIC_ZERO) ? AppConstant::NUMERIC_ELEVEN : $hr) . ':' . (($min < AppConstant::NUMERIC_TEN) ? AppConstant::ZERO_VALUE : '') . $min . ' ' . $am;
        } else {
            $defstimedisp = $deftimedisp;
        }
        return array('startTime' => $defstimedisp, 'endTime' => $deftimedisp);
    }

    public static function prepareSelectedItemOfCourseSetting($course)
    {
        $available = array();
        $toolset = array();
        $isTemplate = array();
        if (($course->available & AppConstant::NUMERIC_ONE) == AppConstant::NUMERIC_ZERO) {
            array_push($available, AppConstant::NUMERIC_ONE);
        }
        if (($course->available & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_ZERO) {
            array_push($available, AppConstant::NUMERIC_TWO);
        }
        if (($course->toolset & AppConstant::NUMERIC_ONE) == AppConstant::NUMERIC_ZERO) {
            array_push($toolset, AppConstant::NUMERIC_ONE);
        }
        if (($course->toolset & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_ZERO) {
            array_push($toolset, AppConstant::NUMERIC_TWO);
        }
        if (($course->toolset & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_ZERO) {
            array_push($toolset, AppConstant::NUMERIC_FOUR);
        }
        if (($course->istemplate & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO) {
            array_push($isTemplate, AppConstant::NUMERIC_TWO);
        }
        if (($course->istemplate & AppConstant::NUMERIC_ONE) == AppConstant::NUMERIC_ONE) {
            array_push($isTemplate, AppConstant::NUMERIC_ONE);
        }
        if (($course->istemplate & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR) {
            array_push($isTemplate, AppConstant::NUMERIC_FOUR);
        }
        return $ckeckList = array('available' => $available, 'toolset' => $toolset, 'isTemplate' => $isTemplate);
    }

//        Displays date and time
    public static function parsedatetime($date, $time)
    {
        $tzOffset = AppUtility::getTimezoneOffset();
        $tzName = AppUtility::getTimezoneName();
        preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$date,$dateMatches);
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$time,$timeMatches);
        if (count($timeMatches) == 0)
        {
            preg_match('/(\d+)\s*([a-zA-Z]+)/',$time,$timeMatches);
            $timeMatches[3] = $timeMatches[2];
            $timeMatches[2] = 0;
        }
        $timeMatches[1] = $timeMatches[1]%12;
        if($timeMatches[3]=="PM" || $timeMatches[3]=="pm") {$timeMatches[1]+=12; }
        if ($tzName == '') {
            $serverOffset = date('Z')/60 + $tzOffset;
            $timeMatches[2] += $serverOffset;
        }

        $dateString = $dateMatches[3].'-'.$dateMatches[1].'-'.$dateMatches[2].' '.$timeMatches[1].':'.$timeMatches[2].':00';
        $dateObject = date_create_from_format("Y-m-d H:i:s",$dateString,timezone_open($tzName));
        return $dateObject->getTimestamp();
    }
    /*
     * Displays only time
     */
    public static function parsetime($time)
    {

        $tzOffset = AppUtility::getTimezoneOffset();
        $tzName = AppUtility::getTimezoneName();
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/', $time, $tmatches);
        if (count($tmatches) == AppConstant::NUMERIC_ZERO) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/', $time, $tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = AppConstant::NUMERIC_ZERO;
        }
        $tmatches[1] = $tmatches[1] % AppConstant::NUMERIC_ELEVEN;
        if ($tmatches[3] == "pm" || $tmatches[3] == "PM") {
            $tmatches[1] += AppConstant::NUMERIC_ELEVEN;
        }
        if ($tzname == '') {
            $serveroffset = date('Z') / AppConstant::SECONDS + $tzoffset;
            $tmatches[2] += $serveroffset;
        }
        return  mktime($tmatches[1], $tmatches[2], AppConstant::NUMERIC_ZERO);
    }

    public static function myRight()
    {
        return Yii::$app->user->identity->rights;
    }

    /**
     * Show Calender
     */
    public static function showCalendar($refpage)
    {
        global $imasroot, $cid, $userid, $teacherid, $previewshift, $latepasses, $urlmode, $latepasshrs, $myrights, $tzoffset, $tzname, $havecalcedviewedassess, $viewedassess;
        $now = time();
        if ($previewshift != -1) {
            $now = $now + $previewshift;
        }
        if (!isset($_COOKIE['calstart' . $cid]) || $_COOKIE['calstart' . $cid] == AppConstant::NUMERIC_ZERO) {
            $today = $now;
        } else {
            $today = $_COOKIE['calstart' . $cid];
        }
        if (isset($_GET['calpageshift'])) {
            $pageshift = $_GET['calpageshift'];
        } else {
            $pageshift = AppConstant::NUMERIC_ZERO;
        }
        if (!isset($_COOKIE['callength' . $cid])) {
            $callength = AppConstant::NUMERIC_FOUR;
        } else {
            $callength = $_COOKIE['callength' . $cid];
        }
        $today = $today + $pageshift * AppConstant::NUMERIC_SEVEN * $callength * AppConstant::HOURS * AppConstant::MINUTE * AppConstant::SECONDS;
        $dayofweek = tzdate('w', $today);
        $curmonum = tzdate('n', $today);
        $dayofmo = tzdate('j', $today);
        $curyr = tzdate('Y', $today);
        if ($tzname == '') {
            $serveroffset = date('Z') + $tzoffset * AppConstant::SECONDS;
        } else {
            $serveroffset = AppConstant::NUMERIC_ZERO; //don't need this if user's timezone has been set
        }
        $midtoday = mktime(AppConstant::NUMERIC_ELEVEN, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ZERO, $curmonum, $dayofmo, $curyr) + $serveroffset;
        $hdrs = array();
        $ids = array();
        $lastmo = '';
        for ($i = AppConstant::NUMERIC_ZERO; $i < AppConstant::NUMERIC_SEVEN * $callength; $i++) {
            $row = floor($i / AppConstant::NUMERIC_SEVEN);
            $col = $i % AppConstant::NUMERIC_SEVEN;
            list($thismo, $thisday, $thismonum, $datestr) = explode('|', tzdate('M|j|n|l F j, Y', $midtoday - ($dayofweek - $i) * AppConstant::HOURS * AppConstant::MINUTE * AppConstant::SECONDS));
            if ($thismo == $lastmo) {
                $hdrs[$row][$col] = $thisday;
            } else {
                $hdrs[$row][$col] = "$thismo $thisday";
                $lastmo = $thismo;
            }
            $ids[$row][$col] = "$thismonum-$thisday";

            $dates[$ids[$row][$col]] = $datestr;
        }
    }

    public static function generateAssessmentData($itemorder, $shuffle, $aid, $arrayout = false)
    {
        $ioquestions = explode(",", $itemorder);
        $questions = array();
        foreach ($ioquestions as $k => $q) {
            if (strpos($q, '~') !== false) {
                $sub = explode('~', $q);
                if (strpos($sub[0], '|') === false) { //backwards compat
                    $questions[] = $sub[array_rand($sub, AppConstant::NUMERIC_ONE)];
                } else {
                    $grpqs = array();
                    $grpparts = explode('|', $sub[0]);
                    array_shift($sub);
                    if ($grpparts[1] == AppConstant::NUMERIC_ONE) { // With replacement
                        for ($i = AppConstant::NUMERIC_ZERO; $i < $grpparts[0]; $i++) {
                            $questions[] = $sub[array_rand($sub, AppConstant::NUMERIC_ONE)];
                        }
                    } else if ($grpparts[1] == AppConstant::NUMERIC_ZERO) { //Without replacement
                        shuffle($sub);
                        for ($i = AppConstant::NUMERIC_ZERO; $i < min($grpparts[0], count($sub)); $i++) {
                            $questions[] = $sub[$i];
                        }
                        if ($grpparts[0] > count($sub)) { //fix stupid inputs
                            for ($i = count($sub); $i < $grpparts[0]; $i++) {
                                $questions[] = $sub[array_rand($sub, AppConstant::NUMERIC_ONE)];
                            }
                        }
                    }
                }
            } else {
                $questions[] = $q;
            }
        }
        if ($shuffle & AppConstant::NUMERIC_ONE) {
            shuffle($questions);
        }
        if ($shuffle & AppConstant::NUMERIC_TWO) { //all questions same random seed
            if ($shuffle & AppConstant::NUMERIC_FOUR) { //all students same seed
                $seeds = array_fill(AppConstant::NUMERIC_ZERO, count($questions), $aid);
                $reviewseeds = array_fill(AppConstant::NUMERIC_ZERO, count($questions), $aid + AppConstant::NUMERIC_HUNDREAD);
            } else {
                $seeds = array_fill(AppConstant::NUMERIC_ZERO, count($questions), rand(AppConstant::NUMERIC_ONE, AppConstant::QUARTER_NINE));
                $reviewseeds = array_fill(AppConstant::NUMERIC_ZERO, count($questions), rand(AppConstant::NUMERIC_ONE, AppConstant::QUARTER_NINE));
            }
        } else {
            if ($shuffle & AppConstant::NUMERIC_FOUR) { //all students same seed
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($questions); $i++) {
                    $seeds[] = $aid + $i;
                    $reviewseeds[] = $aid + $i + AppConstant::NUMERIC_HUNDREAD;
                }
            } else {
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($questions); $i++) {
                    $seeds[] = rand(AppConstant::NUMERIC_ONE, AppConstant::QUARTER_NINE);
                    $reviewseeds[] = rand(AppConstant::NUMERIC_ONE, AppConstant::QUARTER_NINE);
                }
            }
        }
        $scores = array_fill(AppConstant::NUMERIC_ZERO, count($questions), AppConstant::NUMERIC_NEGATIVE_ONE);
        $attempts = array_fill(AppConstant::NUMERIC_ZERO, count($questions), AppConstant::NUMERIC_ZERO);
        $lastanswers = array_fill(AppConstant::NUMERIC_ZERO, count($questions), '');
        if ($arrayout) {
            return array($questions, $seeds, $reviewseeds, $scores, $attempts, $lastanswers);
        } else {
            $qlist = implode(",", $questions);
            $seedlist = implode(",", $seeds);
            $reviewseedlist = implode(",", $reviewseeds);
            $scorelist = implode(",", $scores);
            $attemptslist = implode(",", $attempts);
            $lalist = implode("~", $lastanswers);
            return array($qlist, $seedlist, $reviewseedlist, $scorelist, $attemptslist, $lalist);
        }
    }

    public static function calculateLevel($title)
    {
        $n = AppConstant::NUMERIC_ZERO;
        while (strpos($title, 'Re: ') === AppConstant::NUMERIC_ZERO) {
            $title = substr($title, AppConstant::NUMERIC_FOUR);
            $n++;
        }
        if ($n == AppConstant::NUMERIC_ONE) {
            $title = 'Re: ' . $title;
        } else if ($n > AppConstant::NUMERIC_ONE) {
            $title = "Re<sup>$n</sup>: " . $title;
        }
        return array('title' => $title, 'level' => $n);
    }

    public static function getRefererUri($refere)
    {
        $home = self::getHomeURL();
        $hostInfo = Yii::$app->request->hostInfo;
        $absUrl = str_replace($hostInfo, '', $refere);
        $refereUri = $absUrl;
        if (strpos($hostInfo, 'localhost') != false) {
            $refereUri = str_replace($home, '', $absUrl);
        }
        return $refereUri;
    }

    public static function getTimezoneOffset()
    {
        return Yii::$app->session->get('tzoffset');
    }

    public static function getTimezoneName()
    {
        return Yii::$app->session->get('tzname');
    }

    public static function getBasePath()
    {
        return Yii::$app->getBasePath();
    }

    public static function printTest($teacherid, $isteacher, $assessmentSessionId, $user, $course)
    {
        global $allowedmacros, $mathfuncs, $questions, $seeds, $responseString;
        $allowedmacros = array();
        $mathfuncs = array("sin", "cos", "tan", "sinh", "cosh", "tanh", "arcsin", "arccos", "arctan", "arcsinh", "arccosh", "sqrt", "ceil", "floor", "round", "log", "ln", "abs", "max", "min", "count");
        $allowedmacros = $mathfuncs;
        $userfullname = AppUtility::getFullName($user->FirstName, $user->LastName);
        $responseString = "";
        $isteacher = (isset($teacherid) || $isteacher == true);
        if (!isset($assessmentSessionId) && !$isteacher) {
            echo "<html><body>Error. </body></html>\n";
            exit;
        }
        if (isset($teacherid) && isset($_GET['scored'])) {
            $scoredtype = $_GET['scored'];
            $scoredview = true;
            $showcolormark = true;
        } else {
            $scoredtype = 'last';
            $scoredview = false;
        }
        include("displayQuestion.php");
        include("testutil.php");
        $flexwidth = true; //tells header to use non _fw stylesheet
        if ($scoredview) {
            $placeinhead = '<script type="text/javascript">
			$(function() {
				$(\'input[value="Preview"]\').click().hide();
			});
			</script>';
        }
        $responseString .= "<style type=\"text/css\" media=\"print\">.hideonprint {display:none;} p.tips {display: none;}\n input.btn {display: none;}\n textarea {display: none;}\n input.sabtn {display: none;} .question, .review {background-color:#fff;}</style>\n";
        $responseString .= "<style type=\"text/css\">p.tips {	display: none;}\n </style>\n";
        $responseString .= '<script type="text/javascript">function rendersa() { ';
        $responseString .= '  el = document.getElementsByTagName("span"); ';
        $responseString .= '   for (var i=0;i<el.length;i++) {';
        $responseString .= '     if (el[i].className=="hidden") { ';
        $responseString .= '         el[i].className = "shown";';
        $responseString .= '     }';
        $responseString .= '    }';
        $responseString .= '} </script>';
        if ($isteacher && isset($_GET['asid'])) {
            $testid = $_GET['asid'];
        } else {
            $testid = $assessmentSessionId;
        }
        $line = AssessmentSession::getById($testid);
        if (strpos($line['questions'], ';') === false) {
            $questions = explode(",", $line['questions']);
            $bestquestions = $questions;
        } else {
            list($questions, $bestquestions) = explode(";", $line['questions']);
            $questions = explode(",", $questions);
            $bestquestions = explode(",", $bestquestions);
        }
        if ($scoredtype == 'last') {
            $seeds = explode(",", $line['seeds']);
            $sp = explode(';', $line['scores']);
            $scores = explode(",", $sp[0]);
            $rawscores = explode(',', $sp[1]);
            $attempts = explode(",", $line['attempts']);
            $lastanswers = explode("~", $line['lastanswers']);
        } else {
            $seeds = explode(",", $line['bestseeds']);
            $sp = explode(';', $line['bestscores']);
            $scores = explode(",", $sp[0]);
            $rawscores = explode(',', $sp[1]);
            $attempts = explode(",", $line['bestattempts']);
            $lastanswers = explode("~", $line['bestlastanswers']);
            $questions = $bestquestions;
        }
        $timesontask = explode("~", $line['timeontask']);

        if ($isteacher) {
            if ($line['userid'] != $user->id) {
                $row = User::getById($line['userid']);
                $userfullname = $row['FirstName'] . " " . $row['LastName'];
            }
            $userid = $line['userid'];
        }
        $testsettings = Assessments::getByAssessmentId($line['assessmentid']);
        list($testsettings['testtype'], $testsettings['showans']) = explode('-', $testsettings['deffeedback']);
        $qi = getquestioninfo($questions, $testsettings);
        $now = time();
        $isreview = false;
        if (!$scoredview && ($now < $testsettings['startdate'] || $testsettings['enddate'] < $now)) { //outside normal range for test
            $row = Exceptions::getByAssessmentIdAndUserId($userid, $line['assessmentid']);
            if ($row != null) {
                if ($now < $row['startdate'] || $row['enddate'] < $now) { //outside exception dates
                    if ($now > $testsettings['startdate'] && $now < $testsettings['reviewdate']) {
                        $isreview = true;
                    } else {
                        if (!$isteacher) {
                            $responseString .= "Assessment is closed";
                            $responseString .= "<br/><a href=\"../course/course?cid={$testsettings['courseid']}\">Return to course page</a>";
                            return $responseString;
                        }
                    }
                }
            } else { //no exception
                if ($now > $testsettings['startdate'] && $now < $testsettings['reviewdate']) {
                    $isreview = true;
                } else {
                    if (!$isteacher) {
                        $responseString .= "Assessment is closed";
                        $responseString .= "<br/><a href=\"../course/course?cid={$testsettings['courseid']}\">Return to course page</a>";
                        return $responseString;
                    }
                }
            }
        }
        if ($isreview) {
            $seeds = explode(",", $line['reviewseeds']);
            $scores = explode(",", $line['reviewscores']);
            $attempts = explode(",", $line['reviewattempts']);
            $lastanswers = explode("~", $line['reviewlastanswers']);
        }
        $responseString .= "<h4 class='padding-zero' style=\"float:right;\"><b>Name:</b> $userfullname </h4>";
        $responseString .= "<h3 class='margin-top-zero'>" . $testsettings['name'] . "</h3>";
        $allowregen = ($testsettings['testtype'] == "Practice" || $testsettings['testtype'] == "Homework");
        $showeachscore = ($testsettings['testtype'] == "Practice" || $testsettings['testtype'] == "AsGo" || $testsettings['testtype'] == "Homework");
        $showansduring = (($testsettings['testtype'] == "Practice" || $testsettings['testtype'] == "Homework") && $testsettings['showans'] != 'N');
        $GLOBALS['useeditor'] = 'reviewifneeded';
        $responseString .= "<div class=breadcrumbPrintVersion>Print Ready Version</div><br>";
        $endtext = '';
        $intropieces = array();
        if (strpos($testsettings['intro'], '[QUESTION') !== false) {
            //embedded type
            $intro = preg_replace('/<p>((<span|<strong|<em)[^>]*>)?\[QUESTION\s+(\d+)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/', '[QUESTION $3]', $testsettings['intro']);
            $introsplit = preg_split('/\[QUESTION\s+(\d+)\]/', $intro, AppConstant::NUMERIC_NEGATIVE_ONE, PREG_SPLIT_DELIM_CAPTURE);

            for ($i = AppConstant::NUMERIC_ONE; $i < count($introsplit); $i += AppConstant::NUMERIC_TWO) {
                $intropieces[$introsplit[$i]] = $introsplit[$i - 1];
            }
            //no specific start text - will just go before first question
            $testsettings['intro'] = '';
            $endtext = $introsplit[count($introsplit) - 1];
        } else if (strpos($testsettings['intro'], '[Q ') !== false) {
            //question info type
            $intro = preg_replace('/<p>((<span|<strong|<em)[^>]*>)?\[Q\s+(\d+(\-(\d+))?)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/', '[Q $3]', $testsettings['intro']);
            $introsplit = preg_split('/\[Q\s+(.*?)\]/', $intro, AppConstant::NUMERIC_NEGATIVE_ONE, PREG_SPLIT_DELIM_CAPTURE);
            $testsettings['intro'] = $introsplit[0];
            for ($i = AppConstant::NUMERIC_ONE; $i < count($introsplit); $i += AppConstant::NUMERIC_TWO) {
                $p = explode('-', $introsplit[$i]);
                $intropieces[$p[0]] = $introsplit[$i + AppConstant::NUMERIC_ONE];
            }
        }
        $responseString .= '<div class=intro>' . $testsettings['intro'] . '</div>';
        if ($isteacher && !$scoredview) {
            $responseString .= '<p>' . _('Showing Current Versions') . '<br/><button type="button" class="btn" onclick="rendersa()">' . _("Show Answers") . '</button> <a href="print-test?cid=' . $cid . '&asid=' . $testid . '&scored=best">' . _('Show Scored View') . '</a> <a href="print-test?cid=' . $cid . '&asid=' . $testid . '&scored=last">' . _('Show Last Attempts') . '</a></p>';
        } else if ($isteacher) {
            if ($scoredtype == 'last') {
                $responseString .= '<p>' . _('Showing Last Attempts') . ' <a href="print-test?cid=' . $cid . '&asid=' . $testid . '&scored=best">' . _('Show Scored View') . '</a></p>';
            } else {
                $responseString .= '<p>' . _('Showing Scored View') . ' <a href="print-test?cid=' . $cid . '&asid=' . $testid . '&scored=last">' . _('Show Last Attempts') . '</a></p>';
            }

        }
        if ($testsettings['showans'] == 'N') {
            $lastanswers = array_fill(AppConstant::NUMERIC_ZERO, count($questions), '');
        }
        for ($i = AppConstant::NUMERIC_ZERO; $i < count($questions); $i++) {
            $qsetid = $qi[$questions[$i]]['questionsetid'];
            $cat = $qi[$questions[$i]]['category'];
            $showa = $isteacher;
            if (isset($intropieces[$i + AppConstant::NUMERIC_ONE])) {
                $responseString .= '<div class="intro">' . $intropieces[$i + AppConstant::NUMERIC_ONE] . '</div>';
            }
            $responseString .= '<div class="nobreak">';
            if (isset($_GET['descr'])) {
                $query = QuestionSet::getByQuesSetId($qsetid);
                $responseString .= '<div>ID:' . $qsetid . ', ' . $query['description'] . '</div>';
            } else {
                $points = $qi[$questions[$i]]['points'];
                $qattempts = $qi[$questions[$i]]['attempts'];
                if ($scoredview) {
                    $responseString .= "<div>#" . ($i + AppConstant::NUMERIC_ONE) . " ";
                    $responseString .= printscore($scores[$i], $i);
                    $responseString .= "</div>";
                } else {
                    $responseString .= "<div>#" . ($i + AppConstant::NUMERIC_ONE) . " Points possible: $points.  Total attempts: $qattempts</div>";
                }
            }
            if ($scoredview) {
                if (isset($rawscores[$i])) {
                    if (strpos($rawscores[$i], '~') !== false) {
                        $colors = explode('~', $rawscores[$i]);
                    } else {
                        $colors = array($rawscores[$i]);
                    }
                } else {
                    $colors = array();
                }
                displayq($i, $qsetid, $seeds[$i], AppConstant::NUMERIC_TWO, false, $attempts[$i], false, false, false, $colors);
                $responseString .= '<div class="review">';
                $laarr = explode('##', $lastanswers[$i]);
                if (count($laarr) > AppConstant::NUMERIC_ONE) {
                    $responseString .= "Previous Attempts:";
                    $cnt = AppConstant::NUMERIC_ONE;
                    for ($k = AppConstant::NUMERIC_ZERO; $k < count($laarr) - 1; $k++) {
                        if ($laarr[$k] == "ReGen") {
                            $responseString .= ' ReGen ';
                        } else {
                            $responseString .= "  <b>$cnt:</b> ";
                            if (preg_match('/@FILE:(.+?)@/', $laarr[$k], $match)) {
                                $url = getasidfileurl($match[1]);
                                $responseString .= "<a href=\"$url\" target=\"_new\">" . basename($match[1]) . "</a>";
                            } else {
                                if (strpos($laarr[$k], '$f$')) {
                                    if (strpos($laarr[$k], '&')) { //is multipart q
                                        $laparr = explode('&', $laarr[$k]);
                                        foreach ($laparr as $lk => $v) {
                                            if (strpos($v, '$f$')) {
                                                $tmp = explode('$f$', $v);
                                                $laparr[$lk] = $tmp[0];
                                            }
                                        }
                                        $laarr[$k] = implode('&', $laparr);
                                    } else {
                                        $tmp = explode('$f$', $laarr[$k]);
                                        $laarr[$k] = $tmp[0];
                                    }
                                }
                                if (strpos($laarr[$k], '$!$')) {
                                    if (strpos($laarr[$k], '&')) { //is multipart q
                                        $laparr = explode('&', $laarr[$k]);
                                        foreach ($laparr as $lk => $v) {
                                            if (strpos($v, '$!$')) {
                                                $tmp = explode('$!$', $v);
                                                $laparr[$lk] = $tmp[0];
                                            }
                                        }
                                        $laarr[$k] = implode('&', $laparr);
                                    } else {
                                        $tmp = explode('$!$', $laarr[$k]);
                                        $laarr[$k] = $tmp[0];
                                    }
                                }
                                if (strpos($laarr[$k], '$#$')) {
                                    if (strpos($laarr[$k], '&')) { //is multipart q
                                        $laparr = explode('&', $laarr[$k]);
                                        foreach ($laparr as $lk => $v) {
                                            if (strpos($v, '$#$')) {
                                                $tmp = explode('$#$', $v);
                                                $laparr[$lk] = $tmp[0];
                                            }
                                        }
                                        $laarr[$k] = implode('&', $laparr);
                                    } else {
                                        $tmp = explode('$#$', $laarr[$k]);
                                        $laarr[$k] = $tmp[0];
                                    }
                                }
                                $responseString .= str_replace(array('&', '%nbsp;'), array('; ', '&nbsp;'), strip_tags($laarr[$k]));
                            }
                            $cnt++;
                        }
                    }
                    $responseString .= '. ';
                }
                if ($timesontask[$i] != '') {
                    $responseString .= 'Average time per submission: ';
                    $timesarr = explode('~', $timesontask[$i]);
                    $avgtime = array_sum($timesarr) / count($timesarr);
                    if ($avgtime < 60) {
                        $responseString .= round($avgtime, AppConstant::NUMERIC_ONE) . ' seconds ';
                    } else {
                        $responseString .= round($avgtime / 60, AppConstant::NUMERIC_ONE) . ' minutes ';
                    }
                    $responseString .= '<br/>';
                }
                $responseString .= '</div>';

            } else {

                displayq($i, $qsetid, $seeds[$i], $showa, ($testsettings['showhints'] == AppConstant::NUMERIC_ONE), $attempts[$i]);
            }
            $responseString .= "<hr />";
            $responseString .= '</div>';
        }
        if ($endtext != '') {
            $responseString .= '<div class="intro">' . $endtext . '</div>';
        }
        return $responseString;
    }

    public static function htmLawed($t, $C = AppConstant::NUMERIC_ONE, $S = array())
    {
        $C = is_array($C) ? $C : array();
        if (!empty($C['valid_xhtml'])) {
            $C['elements'] = empty($C['elements']) ? '*-center-dir-font-isindex-menu-s-strike-u' : $C['elements'];
            $C['make_tag_strict'] = isset($C['make_tag_strict']) ? $C['make_tag_strict'] : AppConstant::NUMERIC_TWO;
            $C['xml:lang'] = isset($C['xml:lang']) ? $C['xml:lang'] : AppConstant::NUMERIC_TWO;
        }
// config eles
        $e = array('a' => AppConstant::NUMERIC_ONE, 'abbr' => AppConstant::NUMERIC_ONE, 'acronym' => AppConstant::NUMERIC_ONE, 'address' => AppConstant::NUMERIC_ONE, 'applet' => AppConstant::NUMERIC_ONE, 'area' => AppConstant::NUMERIC_ONE, 'b' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'big' => AppConstant::NUMERIC_ONE, 'blockquote' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'caption' => AppConstant::NUMERIC_ONE, 'center' => AppConstant::NUMERIC_ONE, 'cite' => AppConstant::NUMERIC_ONE, 'code' => AppConstant::NUMERIC_ONE, 'col' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE, 'dd' => AppConstant::NUMERIC_ONE, 'del' => AppConstant::NUMERIC_ONE, 'dfn' => AppConstant::NUMERIC_ONE, 'dir' => AppConstant::NUMERIC_ONE, 'div' => AppConstant::NUMERIC_ONE, 'dl' => AppConstant::NUMERIC_ONE, 'dt' => AppConstant::NUMERIC_ONE, 'em' => AppConstant::NUMERIC_ONE, 'embed' => AppConstant::NUMERIC_ONE, 'fieldset' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'h1' => AppConstant::NUMERIC_ONE, 'h2' => AppConstant::NUMERIC_ONE, 'h3' => AppConstant::NUMERIC_ONE, 'h4' => AppConstant::NUMERIC_ONE, 'h5' => AppConstant::NUMERIC_ONE, 'h6' => AppConstant::NUMERIC_ONE, 'hr' => AppConstant::NUMERIC_ONE, 'i' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'ins' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'kbd' => AppConstant::NUMERIC_ONE, 'label' => AppConstant::NUMERIC_ONE, 'legend' => AppConstant::NUMERIC_ONE, 'li' => AppConstant::NUMERIC_ONE, 'map' => AppConstant::NUMERIC_ONE, 'menu' => AppConstant::NUMERIC_ONE, 'noscript' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'ol' => AppConstant::NUMERIC_ONE, 'optgroup' => AppConstant::NUMERIC_ONE, 'option' => AppConstant::NUMERIC_ONE, 'p' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'pre' => AppConstant::NUMERIC_ONE, 'q' => AppConstant::NUMERIC_ONE, 'rb' => AppConstant::NUMERIC_ONE, 'rbc' => AppConstant::NUMERIC_ONE, 'rp' => AppConstant::NUMERIC_ONE, 'rt' => AppConstant::NUMERIC_ONE, 'rtc' => AppConstant::NUMERIC_ONE, 'ruby' => AppConstant::NUMERIC_ONE, 's' => AppConstant::NUMERIC_ONE, 'samp' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'small' => AppConstant::NUMERIC_ONE, 'span' => AppConstant::NUMERIC_ONE, 'strike' => AppConstant::NUMERIC_ONE, 'strong' => AppConstant::NUMERIC_ONE, 'sub' => AppConstant::NUMERIC_ONE, 'sup' => AppConstant::NUMERIC_ONE, 'table' => AppConstant::NUMERIC_ONE, 'tbody' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE, 'tt' => AppConstant::NUMERIC_ONE, 'u' => AppConstant::NUMERIC_ONE, 'ul' => AppConstant::NUMERIC_ONE, 'var' => AppConstant::NUMERIC_ONE); // 86/deprecated+embed+ruby
        if (!empty($C['safe'])) {
            unset($e['applet'], $e['embed'], $e['iframe'], $e['object'], $e['script']);
        }
        $x = !empty($C['elements']) ? str_replace(array("\n", "\r", "\t", ' '), '', $C['elements']) : '*';
        if ($x == '-*') {
            $e = array();
        } elseif (strpos($x, '*') === false) {
            $e = array_flip(explode(',', $x));
        } else {
            if (isset($x[1])) {
                preg_match_all('`(?:^|-|\+)[^\-+]+?(?=-|\+|$)`', $x, $m, PREG_SET_ORDER);
                for ($i = count($m); --$i >= AppConstant::NUMERIC_ZERO;) {
                    $m[$i] = $m[$i][0];
                }
                foreach ($m as $v) {
                    if ($v[0] == '+') {
                        $e[substr($v, AppConstant::NUMERIC_ONE)] = AppConstant::NUMERIC_ONE;
                    }
                    if ($v[0] == '-' && isset($e[($v = substr($v, AppConstant::NUMERIC_ONE))]) && !in_array('+' . $v, $m)) {
                        unset($e[$v]);
                    }
                }
            }
        }
        $C['elements'] = & $e;
// config attrs
        $x = !empty($C['deny_attribute']) ? str_replace(array("\n", "\r", "\t", ' '), '', $C['deny_attribute']) : '';
        $x = array_flip((isset($x[0]) && $x[0] == '*') ? explode('-', $x) : explode(',', $x . (!empty($C['safe']) ? ',on*' : '')));
        if (isset($x['on*'])) {
            unset($x['on*']);
            $x += array('onblur' => AppConstant::NUMERIC_ONE, 'onchange' => AppConstant::NUMERIC_ONE, 'onclick' => AppConstant::NUMERIC_ONE, 'ondblclick' => AppConstant::NUMERIC_ONE, 'onfocus' => AppConstant::NUMERIC_ONE, 'onkeydown' => AppConstant::NUMERIC_ONE, 'onkeypress' => AppConstant::NUMERIC_ONE, 'onkeyup' => AppConstant::NUMERIC_ONE, 'onmousedown' => AppConstant::NUMERIC_ONE, 'onmousemove' => AppConstant::NUMERIC_ONE, 'onmouseout' => AppConstant::NUMERIC_ONE, 'onmouseover' => AppConstant::NUMERIC_ONE, 'onmouseup' => AppConstant::NUMERIC_ONE, 'onreset' => AppConstant::NUMERIC_ONE, 'onselect' => AppConstant::NUMERIC_ONE, 'onsubmit' => AppConstant::NUMERIC_ONE);
        }
        $C['deny_attribute'] = $x;
// config URL
        $x = (isset($C['schemes'][2]) && strpos($C['schemes'], ':')) ? strtolower($C['schemes']) : 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; *:file, http, https';
        $C['schemes'] = array();
        foreach (explode(';', str_replace(array(' ', "\t", "\r", "\n"), '', $x)) as $v) {
            $x = $x2 = null;
            list($x, $x2) = explode(':', $v, AppConstant::NUMERIC_TWO);
            if ($x2) {
                $C['schemes'][$x] = array_flip(explode(',', $x2));
            }
        }
        if (!isset($C['schemes']['*'])) {
            $C['schemes']['*'] = array('file' => AppConstant::NUMERIC_ONE, 'http' => AppConstant::NUMERIC_ONE, 'https' => AppConstant::NUMERIC_ONE,);
        }
        if (!empty($C['safe']) && empty($C['schemes']['style'])) {
            $C['schemes']['style'] = array('!' => AppConstant::NUMERIC_ONE);
        }
        $C['abs_url'] = isset($C['abs_url']) ? $C['abs_url'] : AppConstant::NUMERIC_ZERO;
        if (!isset($C['base_url']) or !preg_match('`^[a-zA-Z\d.+\-]+://[^/]+/(.+?/)?$`', $C['base_url'])) {
            $C['base_url'] = $C['abs_url'] = AppConstant::NUMERIC_ZERO;
        }
// config rest
        $C['and_mark'] = empty($C['and_mark']) ? AppConstant::NUMERIC_ZERO : AppConstant::NUMERIC_ONE;
        $C['anti_link_spam'] = (isset($C['anti_link_spam']) && is_array($C['anti_link_spam']) && count($C['anti_link_spam']) == AppConstant::NUMERIC_TWO && (empty($C['anti_link_spam'][0]) or hl_regex($C['anti_link_spam'][0])) && (empty($C['anti_link_spam'][1]) or hl_regex($C['anti_link_spam'][1]))) ? $C['anti_link_spam'] : 0;
        $C['anti_mail_spam'] = isset($C['anti_mail_spam']) ? $C['anti_mail_spam'] : AppConstant::NUMERIC_ZERO;
        $C['balance'] = isset($C['balance']) ? (bool)$C['balance'] : AppConstant::NUMERIC_ONE;
        $C['cdata'] = isset($C['cdata']) ? $C['cdata'] : (empty($C['safe']) ? AppConstant::NUMERIC_THREE : AppConstant::NUMERIC_ZERO);
        $C['clean_ms_char'] = empty($C['clean_ms_char']) ? AppConstant::NUMERIC_ZERO : $C['clean_ms_char'];
        $C['comment'] = isset($C['comment']) ? $C['comment'] : (empty($C['safe']) ? AppConstant::NUMERIC_THREE : AppConstant::NUMERIC_ZERO);
        $C['css_expression'] = empty($C['css_expression']) ? AppConstant::NUMERIC_ZERO : AppConstant::NUMERIC_ONE;
        $C['direct_list_nest'] = empty($C['direct_list_nest']) ? AppConstant::NUMERIC_ZERO : AppConstant::NUMERIC_ONE;
        $C['hexdec_entity'] = isset($C['hexdec_entity']) ? $C['hexdec_entity'] : AppConstant::NUMERIC_ONE;
        $C['hook'] = (!empty($C['hook']) && function_exists($C['hook'])) ? $C['hook'] : AppConstant::NUMERIC_ZERO;
        $C['hook_tag'] = (!empty($C['hook_tag']) && function_exists($C['hook_tag'])) ? $C['hook_tag'] : AppConstant::NUMERIC_ZERO;
        $C['keep_bad'] = isset($C['keep_bad']) ? $C['keep_bad'] : AppConstant::NUMERIC_SIX;
        $C['lc_std_val'] = isset($C['lc_std_val']) ? (bool)$C['lc_std_val'] : AppConstant::NUMERIC_ONE;
        $C['make_tag_strict'] = isset($C['make_tag_strict']) ? $C['make_tag_strict'] : AppConstant::NUMERIC_ONE;
        $C['named_entity'] = isset($C['named_entity']) ? (bool)$C['named_entity'] : AppConstant::NUMERIC_ONE;
        $C['no_deprecated_attr'] = isset($C['no_deprecated_attr']) ? $C['no_deprecated_attr'] : AppConstant::NUMERIC_ONE;
        $C['parent'] = isset($C['parent'][0]) ? strtolower($C['parent']) : 'body';
        $C['show_setting'] = !empty($C['show_setting']) ? $C['show_setting'] : AppConstant::NUMERIC_ZERO;
        $C['style_pass'] = empty($C['style_pass']) ? AppConstant::NUMERIC_ZERO : AppConstant::NUMERIC_ONE;
        $C['tidy'] = empty($C['tidy']) ? AppConstant::NUMERIC_ZERO : $C['tidy'];
        $C['unique_ids'] = isset($C['unique_ids']) ? $C['unique_ids'] : AppConstant::NUMERIC_ONE;
        $C['xml:lang'] = isset($C['xml:lang']) ? $C['xml:lang'] : AppConstant::NUMERIC_ZERO;
        if (isset($GLOBALS['C'])) {
            $reC = $GLOBALS['C'];
        }
        $GLOBALS['C'] = $C;
        $S = is_array($S) ? $S : hl_spec($S);
        if (isset($GLOBALS['S'])) {
            $reS = $GLOBALS['S'];
        }
        $GLOBALS['S'] = $S;
        $t = preg_replace('`[\x00-\x08\x0b-\x0c\x0e-\x1f]`', '', $t);
        if ($C['clean_ms_char']) {
            $x = array("\x7f" => '', "\x80" => '&#8364;', "\x81" => '', "\x83" => '&#402;', "\x85" => '&#8230;', "\x86" => '&#8224;', "\x87" => '&#8225;', "\x88" => '&#710;', "\x89" => '&#8240;', "\x8a" => '&#352;', "\x8b" => '&#8249;', "\x8c" => '&#338;', "\x8d" => '', "\x8e" => '&#381;', "\x8f" => '', "\x90" => '', "\x95" => '&#8226;', "\x96" => '&#8211;', "\x97" => '&#8212;', "\x98" => '&#732;', "\x99" => '&#8482;', "\x9a" => '&#353;', "\x9b" => '&#8250;', "\x9c" => '&#339;', "\x9d" => '', "\x9e" => '&#382;', "\x9f" => '&#376;');
            $x = $x + ($C['clean_ms_char'] == AppConstant::NUMERIC_ONE ? array("\x82" => '&#8218;', "\x84" => '&#8222;', "\x91" => '&#8216;', "\x92" => '&#8217;', "\x93" => '&#8220;', "\x94" => '&#8221;') : array("\x82" => '\'', "\x84" => '"', "\x91" => '\'', "\x92" => '\'', "\x93" => '"', "\x94" => '"'));
            $t = strtr($t, $x);
        }
        if ($C['cdata'] or $C['comment']) {
            $t = preg_replace_callback('`<!(?:(?:--.*?--)|(?:\[CDATA\[.*?\]\]))>`sm', array('\serhatozles\htmlawed\htmLawed', 'hl_cmtcd'), $t);
        }
        $t = preg_replace_callback('`&amp;([A-Za-z][A-Za-z0-9]{1,30}|#(?:[0-9]{1,8}|[Xx][0-9A-Fa-f]{1,7}));`', array('\serhatozles\htmlawed\htmLawed', 'hl_ent'), str_replace('&', '&amp;', $t));
        if ($C['unique_ids'] && !isset($GLOBALS['hl_Ids'])) {
            $GLOBALS['hl_Ids'] = array();
        }
        if ($C['hook']) {
            $t = $C['hook']($t, $C, $S);
        }
        if ($C['show_setting'] && preg_match('`^[a-z][a-z0-9_]*$`i', $C['show_setting'])) {
            $GLOBALS[$C['show_setting']] = array('config' => $C, 'spec' => $S, 'time' => microtime());
        }
// main
        $t = preg_replace_callback('`<(?:(?:\s|$)|(?:[^>]*(?:>|$)))|>`m', array('\serhatozles\htmlawed\htmLawed', 'hl_tag'), $t);
        $t = $C['balance'] ? self::hl_bal($t, $C['keep_bad'], $C['parent']) : $t;
        $t = (($C['cdata'] or $C['comment']) && strpos($t, "\x01") !== false) ? str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05"), array('', '', '&', '<', '>'), $t) : $t;
        $t = $C['tidy'] ? self::hl_tidy($t, $C['tidy'], $C['parent']) : $t;
        unset($C, $e);
        if (isset($reC)) {
            $GLOBALS['C'] = $reC;
        }
        if (isset($reS)) {
            $GLOBALS['S'] = $reS;
        }
        return $t;
// eof
    }

    public static function hl_attrval($t, $p)
    {
// check attr val against $S
        $o = AppConstant::NUMERIC_ONE;
        $l = strlen($t);
        foreach ($p as $k => $v) {
            switch ($k) {
                case 'maxlen':
                    if ($l > $v) {
                        $o = AppConstant::NUMERIC_ZERO;
                    }
                    break;
                case 'minlen':
                    if ($l < $v) {
                        $o = AppConstant::NUMERIC_ZERO;
                    }
                    break;
                case 'maxval':
                    if ((float)($t) > $v) {
                        $o = AppConstant::NUMERIC_ZERO;
                    }
                    break;
                case 'minval':
                    if ((float)($t) < $v) {
                        $o = AppConstant::NUMERIC_ZERO;
                    }
                    break;
                case 'match':
                    if (!preg_match($v, $t)) {
                        $o = AppConstant::NUMERIC_ZERO;
                    }
                    break;
                case 'nomatch':
                    if (preg_match($v, $t)) {
                        $o = AppConstant::NUMERIC_ZERO;
                    }
                    break;
                case 'oneof':
                    $m = AppConstant::NUMERIC_ZERO;
                    foreach (explode('|', $v) as $n) {
                        if ($t == $n) {
                            $m = AppConstant::NUMERIC_ONE;
                            break;
                        }
                    }
                    $o = $m;
                    break;
                case 'noneof':
                    $m = AppConstant::NUMERIC_ONE;
                    foreach (explode('|', $v) as $n) {
                        if ($t == $n) {
                            $m = AppConstant::NUMERIC_ZERO;
                            break;
                        }
                    }
                    $o = $m;
                    break;
                default:
                    break;
            }
            if (!$o) {
                break;
            }
        }
        return ($o ? $t : (isset($p['default']) ? $p['default'] : AppConstant::NUMERIC_ZERO));
// eof
    }

    public static function hl_bal($t, $do = AppConstant::NUMERIC_ONE, $in = 'div')
    {
// balance tags
// by content
        $cB = array('blockquote' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'map' => AppConstant::NUMERIC_ONE, 'noscript' => AppConstant::NUMERIC_ONE); // Block
        $cE = array('area' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'col' => AppConstant::NUMERIC_ONE, 'embed' => AppConstant::NUMERIC_ONE, 'hr' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE); // Empty
        $cF = array('button' => AppConstant::NUMERIC_ONE, 'del' => AppConstant::NUMERIC_ONE, 'div' => AppConstant::NUMERIC_ONE, 'dd' => AppConstant::NUMERIC_ONE, 'fieldset' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'ins' => AppConstant::NUMERIC_ONE, 'li' => AppConstant::NUMERIC_ONE, 'noscript' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE); // Flow; later context-wise dynamic move of ins & del to $cI
        $cI = array('a' => AppConstant::NUMERIC_ONE, 'abbr' => AppConstant::NUMERIC_ONE, 'acronym' => AppConstant::NUMERIC_ONE, 'address' => AppConstant::NUMERIC_ONE, 'b' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'big' => AppConstant::NUMERIC_ONE, 'caption' => AppConstant::NUMERIC_ONE, 'cite' => AppConstant::NUMERIC_ONE, 'code' => AppConstant::NUMERIC_ONE, 'dfn' => AppConstant::NUMERIC_ONE, 'dt' => AppConstant::NUMERIC_ONE, 'em' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'h1' => AppConstant::NUMERIC_ONE, 'h2' => AppConstant::NUMERIC_ONE, 'h3' => AppConstant::NUMERIC_ONE, 'h4' => AppConstant::NUMERIC_ONE, 'h5' => AppConstant::NUMERIC_ONE, 'h6' => AppConstant::NUMERIC_ONE, 'i' => AppConstant::NUMERIC_ONE, 'kbd' => AppConstant::NUMERIC_ONE, 'label' => AppConstant::NUMERIC_ONE, 'legend' => AppConstant::NUMERIC_ONE, 'p' => AppConstant::NUMERIC_ONE, 'pre' => AppConstant::NUMERIC_ONE, 'q' => AppConstant::NUMERIC_ONE, 'rb' => AppConstant::NUMERIC_ONE, 'rt' => AppConstant::NUMERIC_ONE, 's' => AppConstant::NUMERIC_ONE, 'samp' => AppConstant::NUMERIC_ONE, 'small' => AppConstant::NUMERIC_ONE, 'span' => AppConstant::NUMERIC_ONE, 'strike' => AppConstant::NUMERIC_ONE, 'strong' => AppConstant::NUMERIC_ONE, 'sub' => AppConstant::NUMERIC_ONE, 'sup' => AppConstant::NUMERIC_ONE, 'tt' => AppConstant::NUMERIC_ONE, 'u' => AppConstant::NUMERIC_ONE, 'var' => AppConstant::NUMERIC_ONE); // Inline
        $cN = array('a' => array('a' => AppConstant::NUMERIC_ONE), 'button' => array('a' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'fieldset' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'label' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE), 'fieldset' => array('fieldset' => AppConstant::NUMERIC_ONE), 'form' => array('form' => AppConstant::NUMERIC_ONE), 'label' => array('label' => AppConstant::NUMERIC_ONE), 'noscript' => array('script' => AppConstant::NUMERIC_ONE), 'pre' => array('big' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'small' => AppConstant::NUMERIC_ONE, 'sub' => AppConstant::NUMERIC_ONE, 'sup' => AppConstant::NUMERIC_ONE), 'rb' => array('ruby' => AppConstant::NUMERIC_ONE), 'rt' => array('ruby' => AppConstant::NUMERIC_ONE)); // Illegal
        $cN2 = array_keys($cN);
        $cR = array('blockquote' => AppConstant::NUMERIC_ONE, 'dir' => AppConstant::NUMERIC_ONE, 'dl' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'map' => AppConstant::NUMERIC_ONE, 'menu' => AppConstant::NUMERIC_ONE, 'noscript' => AppConstant::NUMERIC_ONE, 'ol' => AppConstant::NUMERIC_ONE, 'optgroup' => AppConstant::NUMERIC_ONE, 'rbc' => AppConstant::NUMERIC_ONE, 'rtc' => AppConstant::NUMERIC_ONE, 'ruby' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'table' => AppConstant::NUMERIC_ONE, 'tbody' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE, 'ul' => AppConstant::NUMERIC_ONE);
        $cS = array('colgroup' => array('col' => AppConstant::NUMERIC_ONE), 'dir' => array('li' => AppConstant::NUMERIC_ONE), 'dl' => array('dd' => AppConstant::NUMERIC_ONE, 'dt' => AppConstant::NUMERIC_ONE), 'menu' => array('li' => AppConstant::NUMERIC_ONE), 'ol' => array('li' => AppConstant::NUMERIC_ONE), 'optgroup' => array('option' => AppConstant::NUMERIC_ONE), 'option' => array('#pcdata' => AppConstant::NUMERIC_ONE), 'rbc' => array('rb' => AppConstant::NUMERIC_ONE), 'rp' => array('#pcdata' => AppConstant::NUMERIC_ONE), 'rtc' => array('rt' => AppConstant::NUMERIC_ONE), 'ruby' => array('rb' => AppConstant::NUMERIC_ONE, 'rbc' => AppConstant::NUMERIC_ONE, 'rp' => AppConstant::NUMERIC_ONE, 'rt' => AppConstant::NUMERIC_ONE, 'rtc' => AppConstant::NUMERIC_ONE), 'select' => array('optgroup' => AppConstant::NUMERIC_ONE, 'option' => AppConstant::NUMERIC_ONE), 'script' => array('#pcdata' => AppConstant::NUMERIC_ONE), 'table' => array('caption' => AppConstant::NUMERIC_ONE, 'col' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'tbody' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE), 'tbody' => array('tr' => AppConstant::NUMERIC_ONE), 'tfoot' => array('tr' => AppConstant::NUMERIC_ONE), 'textarea' => array('#pcdata' => AppConstant::NUMERIC_ONE), 'thead' => array('tr' => AppConstant::NUMERIC_ONE), 'tr' => array('td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'ul' => array('li' => AppConstant::NUMERIC_ONE)); // Specific - immediate parent-child
        if ($GLOBALS['C']['direct_list_nest']) {
            $cS['ol'] = $cS['ul'] += array('ol' => AppConstant::NUMERIC_ONE, 'ul' => AppConstant::NUMERIC_ONE);
        }
        $cO = array('address' => array('p' => AppConstant::NUMERIC_ONE), 'applet' => array('param' => AppConstant::NUMERIC_ONE), 'blockquote' => array('script' => AppConstant::NUMERIC_ONE), 'fieldset' => array('legend' => AppConstant::NUMERIC_ONE, '#pcdata' => AppConstant::NUMERIC_ONE), 'form' => array('script' => AppConstant::NUMERIC_ONE), 'map' => array('area' => AppConstant::NUMERIC_ONE), 'object' => array('param' => AppConstant::NUMERIC_ONE, 'embed' => AppConstant::NUMERIC_ONE)); // Other
        $cT = array('colgroup' => AppConstant::NUMERIC_ONE, 'dd' => AppConstant::NUMERIC_ONE, 'dt' => AppConstant::NUMERIC_ONE, 'li' => AppConstant::NUMERIC_ONE, 'option' => AppConstant::NUMERIC_ONE, 'p' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE); // Omitable closing
// block/inline type; ins & del both type; #pcdata: text
        $eB = array('address' => AppConstant::NUMERIC_ONE, 'blockquote' => AppConstant::NUMERIC_ONE, 'center' => AppConstant::NUMERIC_ONE, 'del' => AppConstant::NUMERIC_ONE, 'dir' => AppConstant::NUMERIC_ONE, 'dl' => AppConstant::NUMERIC_ONE, 'div' => AppConstant::NUMERIC_ONE, 'fieldset' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'ins' => AppConstant::NUMERIC_ONE, 'h1' => AppConstant::NUMERIC_ONE, 'h2' => AppConstant::NUMERIC_ONE, 'h3' => AppConstant::NUMERIC_ONE, 'h4' => AppConstant::NUMERIC_ONE, 'h5' => AppConstant::NUMERIC_ONE, 'h6' => AppConstant::NUMERIC_ONE, 'hr' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'menu' => AppConstant::NUMERIC_ONE, 'noscript' => AppConstant::NUMERIC_ONE, 'ol' => AppConstant::NUMERIC_ONE, 'p' => AppConstant::NUMERIC_ONE, 'pre' => AppConstant::NUMERIC_ONE, 'table' => AppConstant::NUMERIC_ONE, 'ul' => AppConstant::NUMERIC_ONE);
        $eI = array('#pcdata' => AppConstant::NUMERIC_ONE, 'a' => AppConstant::NUMERIC_ONE, 'abbr' => AppConstant::NUMERIC_ONE, 'acronym' => AppConstant::NUMERIC_ONE, 'applet' => AppConstant::NUMERIC_ONE, 'b' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'big' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'cite' => AppConstant::NUMERIC_ONE, 'code' => AppConstant::NUMERIC_ONE, 'del' => AppConstant::NUMERIC_ONE, 'dfn' => AppConstant::NUMERIC_ONE, 'em' => AppConstant::NUMERIC_ONE, 'embed' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'i' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'ins' => AppConstant::NUMERIC_ONE, 'kbd' => AppConstant::NUMERIC_ONE, 'label' => AppConstant::NUMERIC_ONE, 'map' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'q' => AppConstant::NUMERIC_ONE, 'ruby' => AppConstant::NUMERIC_ONE, 's' => AppConstant::NUMERIC_ONE, 'samp' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'small' => AppConstant::NUMERIC_ONE, 'span' => AppConstant::NUMERIC_ONE, 'strike' => AppConstant::NUMERIC_ONE, 'strong' => AppConstant::NUMERIC_ONE, 'sub' => AppConstant::NUMERIC_ONE, 'sup' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE, 'tt' => AppConstant::NUMERIC_ONE, 'u' => AppConstant::NUMERIC_ONE, 'var' => AppConstant::NUMERIC_ONE);
        $eN = array('a' => AppConstant::NUMERIC_ONE, 'big' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'fieldset' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'label' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'ruby' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'small' => AppConstant::NUMERIC_ONE, 'sub' => AppConstant::NUMERIC_ONE, 'sup' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE); // Exclude from specific ele; $cN values
        $eO = array('area' => AppConstant::NUMERIC_ONE, 'caption' => AppConstant::NUMERIC_ONE, 'col' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE, 'dd' => AppConstant::NUMERIC_ONE, 'dt' => AppConstant::NUMERIC_ONE, 'legend' => AppConstant::NUMERIC_ONE, 'li' => AppConstant::NUMERIC_ONE, 'optgroup' => AppConstant::NUMERIC_ONE, 'option' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'rb' => AppConstant::NUMERIC_ONE, 'rbc' => AppConstant::NUMERIC_ONE, 'rp' => AppConstant::NUMERIC_ONE, 'rt' => AppConstant::NUMERIC_ONE, 'rtc' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'tbody' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE); // Missing in $eB & $eI
        $eF = $eB + $eI;

// $in sets allowed child
        $in = ((isset($eF[$in]) && $in != '#pcdata') or isset($eO[$in])) ? $in : 'div';
        if (isset($cE[$in])) {
            return (!$do ? '' : str_replace(array('<', '>'), array('&lt;', '&gt;'), $t));
        }
        if (isset($cS[$in])) {
            $inOk = $cS[$in];
        } elseif (isset($cI[$in])) {
            $inOk = $eI;
            $cI['del'] = AppConstant::NUMERIC_ONE;
            $cI['ins'] = AppConstant::NUMERIC_ONE;
        } elseif (isset($cF[$in])) {
            $inOk = $eF;
            unset($cI['del'], $cI['ins']);
        } elseif (isset($cB[$in])) {
            $inOk = $eB;
            unset($cI['del'], $cI['ins']);
        }
        if (isset($cO[$in])) {
            $inOk = $inOk + $cO[$in];
        }
        if (isset($cN[$in])) {
            $inOk = array_diff_assoc($inOk, $cN[$in]);
        }

        $t = explode('<', $t);
        $ok = $q = array(); // $q seq list of open non-empty ele
        ob_start();

        for ($i = AppConstant::NUMERIC_NEGATIVE_ONE, $ci = count($t); ++$i < $ci;) {
            // allowed $ok in parent $p
            if ($ql = count($q)) {
                $p = array_pop($q);
                $q[] = $p;
                if (isset($cS[$p])) {
                    $ok = $cS[$p];
                } elseif (isset($cI[$p])) {
                    $ok = $eI;
                    $cI['del'] = AppConstant::NUMERIC_ONE;
                    $cI['ins'] = AppConstant::NUMERIC_ONE;
                } elseif (isset($cF[$p])) {
                    $ok = $eF;
                    unset($cI['del'], $cI['ins']);
                } elseif (isset($cB[$p])) {
                    $ok = $eB;
                    unset($cI['del'], $cI['ins']);
                }
                if (isset($cO[$p])) {
                    $ok = $ok + $cO[$p];
                }
                if (isset($cN[$p])) {
                    $ok = array_diff_assoc($ok, $cN[$p]);
                }
            } else {
                $ok = $inOk;
                unset($cI['del'], $cI['ins']);
            }
            // bad tags, & ele content
            if (isset($e) && ($do == AppConstant::NUMERIC_ONE or (isset($ok['#pcdata']) && ($do == AppConstant::NUMERIC_THREE or $do == AppConstant::NUMERIC_FIVE)))) {
                echo '&lt;', $s, $e, $a, '&gt;';
            }
            if (isset($x[0])) {
                if (strlen(trim($x)) && (($ql && isset($cB[$p])) or (isset($cB[$in]) && !$ql))) {
                    echo '<div>', $x, '</div>';
                } elseif ($do < AppConstant::NUMERIC_THREE or isset($ok['#pcdata'])) {
                    echo $x;
                } elseif (strpos($x, "\x02\x04")) {
                    foreach (preg_split('`(\x01\x02[^\x01\x02]+\x02\x01)`', $x, AppConstant::NUMERIC_NEGATIVE_ONE, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $v) {
                        echo(substr($v, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_TWO) == "\x01\x02" ? $v : ($do > AppConstant::NUMERIC_FOUR ? preg_replace('`\S`', '', $v) : ''));
                    }
                } elseif ($do > AppConstant::NUMERIC_FOUR) {
                    echo preg_replace('`\S`', '', $x);
                }
            }
            // get markup
            if (!preg_match('`^(/?)([a-z1-6]+)([^>]*)>(.*)`sm', $t[$i], $r)) {
                $x = $t[$i];
                continue;
            }
            $s = null;
            $e = null;
            $a = null;
            $x = null;
            list($all, $s, $e, $a, $x) = $r;
            // close tag
            if ($s) {
                if (isset($cE[$e]) or !in_array($e, $q)) {
                    continue;
                } // Empty/unopen
                if ($p == $e) {
                    array_pop($q);
                    echo '</', $e, '>';
                    unset($e);
                    continue;
                } // Last open
                $add = ''; // Nesting - close open tags that need to be
                for ($j = AppConstant::NUMERIC_NEGATIVE_ONE, $cj = count($q); ++$j < $cj;) {
                    if (($d = array_pop($q)) == $e) {
                        break;
                    } else {
                        $add .= "</{$d}>";
                    }
                }
                echo $add, '</', $e, '>';
                unset($e);
                continue;
            }
            // open tag
            // $cB ele needs $eB ele as child
            if (isset($cB[$e]) && strlen(trim($x))) {
                $t[$i] = "{$e}{$a}>";
                array_splice($t, $i + AppConstant::NUMERIC_ONE, AppConstant::NUMERIC_ZERO, 'div>' . $x);
                unset($e, $x);
                ++$ci;
                --$i;
                continue;
            }
            if ((($ql && isset($cB[$p])) or (isset($cB[$in]) && !$ql)) && !isset($eB[$e]) && !isset($ok[$e])) {
                array_splice($t, $i, AppConstant::NUMERIC_ZERO, 'div>');
                unset($e, $x);
                ++$ci;
                --$i;
                continue;
            }
            // if no open ele, $in = parent; mostly immediate parent-child relation should hold
            if (!$ql or !isset($eN[$e]) or !array_intersect($q, $cN2)) {
                if (!isset($ok[$e])) {
                    if ($ql && isset($cT[$p])) {
                        echo '</', array_pop($q), '>';
                        unset($e, $x);
                        --$i;
                    }
                    continue;
                }
                if (!isset($cE[$e])) {
                    $q[] = $e;
                }
                echo '<', $e, $a, '>';
                unset($e);
                continue;
            }
            // specific parent-child
            if (isset($cS[$p][$e])) {
                if (!isset($cE[$e])) {
                    $q[] = $e;
                }
                echo '<', $e, $a, '>';
                unset($e);
                continue;
            }
            // nesting
            $add = '';
            $q2 = array();
            for ($k = AppConstant::NUMERIC_NEGATIVE_ONE, $kc = count($q); ++$k < $kc;) {
                $d = $q[$k];
                $ok2 = array();
                if (isset($cS[$d])) {
                    $q2[] = $d;
                    continue;
                }
                $ok2 = isset($cI[$d]) ? $eI : $eF;
                if (isset($cO[$d])) {
                    $ok2 = $ok2 + $cO[$d];
                }
                if (isset($cN[$d])) {
                    $ok2 = array_diff_assoc($ok2, $cN[$d]);
                }
                if (!isset($ok2[$e])) {
                    if (!$k && !isset($inOk[$e])) {
                        continue 2;
                    }
                    $add = "</{$d}>";
                    for (; ++$k < $kc;) {
                        $add = "</{$q[$k]}>{$add}";
                    }
                    break;
                } else {
                    $q2[] = $d;
                }
            }
            $q = $q2;
            if (!isset($cE[$e])) {
                $q[] = $e;
            }
            echo $add, '<', $e, $a, '>';
            unset($e);
            continue;
        }
// end
        if ($ql = count($q)) {
            $p = array_pop($q);
            $q[] = $p;
            if (isset($cS[$p])) {
                $ok = $cS[$p];
            } elseif (isset($cI[$p])) {
                $ok = $eI;
                $cI['del'] = AppConstant::NUMERIC_ONE;
                $cI['ins'] = AppConstant::NUMERIC_ONE;
            } elseif (isset($cF[$p])) {
                $ok = $eF;
                unset($cI['del'], $cI['ins']);
            } elseif (isset($cB[$p])) {
                $ok = $eB;
                unset($cI['del'], $cI['ins']);
            }
            if (isset($cO[$p])) {
                $ok = $ok + $cO[$p];
            }
            if (isset($cN[$p])) {
                $ok = array_diff_assoc($ok, $cN[$p]);
            }
        } else {
            $ok = $inOk;
            unset($cI['del'], $cI['ins']);
        }
        if (isset($e) && ($do == AppConstant::NUMERIC_ONE or (isset($ok['#pcdata']) && ($do == AppConstant::NUMERIC_THREE or $do == AppConstant::NUMERIC_FIVE)))) {
            echo '&lt;', $s, $e, $a, '&gt;';
        }
        if (isset($x[0])) {
            if (strlen(trim($x)) && (($ql && isset($cB[$p])) or (isset($cB[$in]) && !$ql))) {
                echo '<div>', $x, '</div>';
            } elseif ($do < AppConstant::NUMERIC_THREE or isset($ok['#pcdata'])) {
                echo $x;
            } elseif (strpos($x, "\x02\x04")) {
                foreach (preg_split('`(\x01\x02[^\x01\x02]+\x02\x01)`', $x, AppConstant::NUMERIC_NEGATIVE_ONE, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $v) {
                    echo(substr($v, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_TWO) == "\x01\x02" ? $v : ($do > AppConstant::NUMERIC_FOUR ? preg_replace('`\S`', '', $v) : ''));
                }
            } elseif ($do > AppConstant::NUMERIC_FOUR) {
                echo preg_replace('`\S`', '', $x);
            }
        }
        while (!empty($q) && ($e = array_pop($q))) {
            echo '</', $e, '>';
        }
        $o = ob_get_contents();
        ob_end_clean();
        return $o;
// eof
    }

    public static function hl_cmtcd($t)
    {
// comment/CDATA sec handler
        $t = $t[0];
        global $C;
        if (!($v = $C[$n = $t[3] == '-' ? 'comment' : 'cdata'])) {
            return $t;
        }
        if ($v == AppConstant::NUMERIC_ONE) {
            return '';
        }
        if ($n == 'comment') {
            if (substr(($t = preg_replace('`--+`', '-', substr($t, AppConstant::NUMERIC_FOUR, AppConstant::NUMERIC_NEGATIVE_THREE))), AppConstant::NUMERIC_NEGATIVE_ONE) != ' ') {
                $t .= ' ';
            }
        } else {
            $t = substr($t, AppConstant::NUMERIC_ONE, AppConstant::NUMERIC_NEGATIVE_ONE);
        }
        $t = $v == AppConstant::NUMERIC_TWO ? str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $t) : $t;
        return str_replace(array('&', '<', '>'), array("\x03", "\x04", "\x05"), ($n == 'comment' ? "\x01\x02\x04!--$t--\x05\x02\x01" : "\x01\x01\x04$t\x05\x01\x01"));
// eof
    }

    public static function hl_ent($t)
    {
// entitity handler
        global $C;
        $t = $t[1];
        static $U = array('quot' => AppConstant::NUMERIC_ONE, 'amp' => AppConstant::NUMERIC_ONE, 'lt' => AppConstant::NUMERIC_ONE, 'gt' => AppConstant::NUMERIC_ONE);
        static $N = array('fnof' => '402', 'Alpha' => '913', 'Beta' => '914', 'Gamma' => '915', 'Delta' => '916', 'Epsilon' => '917', 'Zeta' => '918', 'Eta' => '919', 'Theta' => '920', 'Iota' => '921', 'Kappa' => '922', 'Lambda' => '923', 'Mu' => '924', 'Nu' => '925', 'Xi' => '926', 'Omicron' => '927', 'Pi' => '928', 'Rho' => '929', 'Sigma' => '931', 'Tau' => '932', 'Upsilon' => '933', 'Phi' => '934', 'Chi' => '935', 'Psi' => '936', 'Omega' => '937', 'alpha' => '945', 'beta' => '946', 'gamma' => '947', 'delta' => '948', 'epsilon' => '949', 'zeta' => '950', 'eta' => '951', 'theta' => '952', 'iota' => '953', 'kappa' => '954', 'lambda' => '955', 'mu' => '956', 'nu' => '957', 'xi' => '958', 'omicron' => '959', 'pi' => '960', 'rho' => '961', 'sigmaf' => '962', 'sigma' => '963', 'tau' => '964', 'upsilon' => '965', 'phi' => '966', 'chi' => '967', 'psi' => '968', 'omega' => '969', 'thetasym' => '977', 'upsih' => '978', 'piv' => '982', 'bull' => '8226', 'hellip' => '8230', 'prime' => '8242', 'Prime' => '8243', 'oline' => '8254', 'frasl' => '8260', 'weierp' => '8472', 'image' => '8465', 'real' => '8476', 'trade' => '8482', 'alefsym' => '8501', 'larr' => '8592', 'uarr' => '8593', 'rarr' => '8594', 'darr' => '8595', 'harr' => '8596', 'crarr' => '8629', 'lArr' => '8656', 'uArr' => '8657', 'rArr' => '8658', 'dArr' => '8659', 'hArr' => '8660', 'forall' => '8704', 'part' => '8706', 'exist' => '8707', 'empty' => '8709', 'nabla' => '8711', 'isin' => '8712', 'notin' => '8713', 'ni' => '8715', 'prod' => '8719', 'sum' => '8721', 'minus' => '8722', 'lowast' => '8727', 'radic' => '8730', 'prop' => '8733', 'infin' => '8734', 'ang' => '8736', 'and' => '8743', 'or' => '8744', 'cap' => '8745', 'cup' => '8746', 'int' => '8747', 'there4' => '8756', 'sim' => '8764', 'cong' => '8773', 'asymp' => '8776', 'ne' => '8800', 'equiv' => '8801', 'le' => '8804', 'ge' => '8805', 'sub' => '8834', 'sup' => '8835', 'nsub' => '8836', 'sube' => '8838', 'supe' => '8839', 'oplus' => '8853', 'otimes' => '8855', 'perp' => '8869', 'sdot' => '8901', 'lceil' => '8968', 'rceil' => '8969', 'lfloor' => '8970', 'rfloor' => '8971', 'lang' => '9001', 'rang' => '9002', 'loz' => '9674', 'spades' => '9824', 'clubs' => '9827', 'hearts' => '9829', 'diams' => '9830', 'apos' => '39', 'OElig' => '338', 'oelig' => '339', 'Scaron' => '352', 'scaron' => '353', 'Yuml' => '376', 'circ' => '710', 'tilde' => '732', 'ensp' => '8194', 'emsp' => '8195', 'thinsp' => '8201', 'zwnj' => '8204', 'zwj' => '8205', 'lrm' => '8206', 'rlm' => '8207', 'ndash' => '8211', 'mdash' => '8212', 'lsquo' => '8216', 'rsquo' => '8217', 'sbquo' => '8218', 'ldquo' => '8220', 'rdquo' => '8221', 'bdquo' => '8222', 'dagger' => '8224', 'Dagger' => '8225', 'permil' => '8240', 'lsaquo' => '8249', 'rsaquo' => '8250', 'euro' => '8364', 'nbsp' => '160', 'iexcl' => '161', 'cent' => '162', 'pound' => '163', 'curren' => '164', 'yen' => '165', 'brvbar' => '166', 'sect' => '167', 'uml' => '168', 'copy' => '169', 'ordf' => '170', 'laquo' => '171', 'not' => '172', 'shy' => '173', 'reg' => '174', 'macr' => '175', 'deg' => '176', 'plusmn' => '177', 'sup2' => '178', 'sup3' => '179', 'acute' => '180', 'micro' => '181', 'para' => '182', 'middot' => '183', 'cedil' => '184', 'sup1' => '185', 'ordm' => '186', 'raquo' => '187', 'frac14' => '188', 'frac12' => '189', 'frac34' => '190', 'iquest' => '191', 'Agrave' => '192', 'Aacute' => '193', 'Acirc' => '194', 'Atilde' => '195', 'Auml' => '196', 'Aring' => '197', 'AElig' => '198', 'Ccedil' => '199', 'Egrave' => '200', 'Eacute' => '201', 'Ecirc' => '202', 'Euml' => '203', 'Igrave' => '204', 'Iacute' => '205', 'Icirc' => '206', 'Iuml' => '207', 'ETH' => '208', 'Ntilde' => '209', 'Ograve' => '210', 'Oacute' => '211', 'Ocirc' => '212', 'Otilde' => '213', 'Ouml' => '214', 'times' => '215', 'Oslash' => '216', 'Ugrave' => '217', 'Uacute' => '218', 'Ucirc' => '219', 'Uuml' => '220', 'Yacute' => '221', 'THORN' => '222', 'szlig' => '223', 'agrave' => '224', 'aacute' => '225', 'acirc' => '226', 'atilde' => '227', 'auml' => '228', 'aring' => '229', 'aelig' => '230', 'ccedil' => '231', 'egrave' => '232', 'eacute' => '233', 'ecirc' => '234', 'euml' => '235', 'igrave' => '236', 'iacute' => '237', 'icirc' => '238', 'iuml' => '239', 'eth' => '240', 'ntilde' => '241', 'ograve' => '242', 'oacute' => '243', 'ocirc' => '244', 'otilde' => '245', 'ouml' => '246', 'divide' => '247', 'oslash' => '248', 'ugrave' => '249', 'uacute' => '250', 'ucirc' => '251', 'uuml' => '252', 'yacute' => '253', 'thorn' => '254', 'yuml' => '255');
        if ($t[0] != '#') {
            return ($C['and_mark'] ? "\x06" : '&') . (isset($U[$t]) ? $t : (isset($N[$t]) ? (!$C['named_entity'] ? '#' . ($C['hexdec_entity'] > AppConstant::NUMERIC_ONE ? 'x' . dechex($N[$t]) : $N[$t]) : $t) : 'amp;' . $t)) . ';';
        }
        if (($n = ctype_digit($t = substr($t, AppConstant::NUMERIC_ONE)) ? intval($t) : hexdec(substr($t, AppConstant::NUMERIC_ONE))) < AppConstant::NUMERIC_NINE or ($n > AppConstant::NUMERIC_THIRTEEN && $n < AppConstant::NUMERIC_THIRTY_TWO) or $n == AppConstant::NUMERIC_ELEVEN or $n == AppConstant::NUMERIC_TWELVE or ($n > AppConstant::NUMERIC_ONE_HUNDRED_TWENTY_SIX && $n < AppConstant::NUMERIC_ONE_HUNDRED_AND_SIXTY && $n != AppConstant::NUMERIC_ONE_HUNDRED_THIRTY_THREE) or ($n > AppConstant::NUMERIC_FIFTY_FIVE_THOUSAND_TWO_HUNDRED_NINETY_FIVE && ($n < 57344 or ($n > 64975 && $n < 64992) or $n == 65534 or $n == 65535 or $n > 1114111))) {
            return ($C['and_mark'] ? "\x06" : '&') . "amp;#{$t};";
        }
        return ($C['and_mark'] ? "\x06" : '&') . '#' . (((ctype_digit($t) && $C['hexdec_entity'] < AppConstant::NUMERIC_TWO) or !$C['hexdec_entity']) ? $n : 'x' . dechex($n)) . ';';
// eof
    }

    public static function hl_prot($p, $c = null)
    {
// check URL scheme
        global $C;
        $b = $a = '';
        if ($c == null) {
            $c = 'style';
            $b = $p[1];
            $a = $p[3];
            $p = trim($p[2]);
        }
        $c = isset($C['schemes'][$c]) ? $C['schemes'][$c] : $C['schemes']['*'];
        static $d = 'denied:';
        if (isset($c['!']) && substr($p, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_SEVEN) != $d) {
            $p = "$d$p";
        }
        if (isset($c['*']) or !strcspn($p, '#?;') or (substr($p, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_SEVEN) == $d)) {
            return "{$b}{$p}{$a}";
        } // All ok, frag, query, param
        if (preg_match('`^([^:?[@!$()*,=/\'\]]+?)(:|&#(58|x3a);|%3a|\\\\0{0,4}3a).`i', $p, $m) && !isset($c[strtolower($m[1])])) { // Denied prot
            return "{$b}{$d}{$p}{$a}";
        }
        if ($C['abs_url']) {
            if ($C['abs_url'] == AppConstant::NUMERIC_NEGATIVE_ONE && strpos($p, $C['base_url']) === AppConstant::NUMERIC_ZERO) { // Make url rel
                $p = substr($p, strlen($C['base_url']));
            } elseif (empty($m[1])) { // Make URL abs
                if (substr($p, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_TWO) == '//') {
                    $p = substr($C['base_url'], AppConstant::NUMERIC_ZERO, strpos($C['base_url'], ':') + AppConstant::NUMERIC_ONE) . $p;
                } elseif ($p[0] == '/') {
                    $p = preg_replace('`(^.+?://[^/]+)(.*)`', '$1', $C['base_url']) . $p;
                } elseif (strcspn($p, './')) {
                    $p = $C['base_url'] . $p;
                } else {
                    preg_match('`^([a-zA-Z\d\-+.]+://[^/]+)(.*)`', $C['base_url'], $m);
                    $p = preg_replace('`(?<=/)\./`', '', $m[2] . $p);
                    while (preg_match('`(?<=/)([^/]{3,}|[^/.]+?|\.[^/.]|[^/.]\.)/\.\./`', $p)) {
                        $p = preg_replace('`(?<=/)([^/]{3,}|[^/.]+?|\.[^/.]|[^/.]\.)/\.\./`', '', $p);
                    }
                    $p = $m[1] . $p;
                }
            }
        }
        return "{$b}{$p}{$a}";
// eof
    }

    public static function hl_regex($p)
    {
// ?regex
        if (empty($p)) {
            return AppConstant::NUMERIC_ZERO;
        }
        if ($t = ini_get('track_errors')) {
            $o = isset($php_errormsg) ? $php_errormsg : null;
        } else {
            ini_set('track_errors', AppConstant::NUMERIC_ONE);
        }
        unset($php_errormsg);
        if (($d = ini_get('display_errors'))) {
            ini_set('display_errors', AppConstant::NUMERIC_ZERO);
        }
        preg_match($p, '');
        if ($d) {
            ini_set('display_errors', AppConstant::NUMERIC_ONE);
        }
        $r = isset($php_errormsg) ? AppConstant::NUMERIC_ZERO : AppConstant::NUMERIC_ONE;
        if ($t) {
            $php_errormsg = isset($o) ? $o : null;
        } else {
            ini_set('track_errors', AppConstant::NUMERIC_ZERO);
        }
        return $r;
// eof
    }

    public static function hl_spec($t)
    {
// final $spec
        $s = array();
        $t = str_replace(array("\t", "\r", "\n", ' '), '', preg_replace_callback('/"(?>(`.|[^"])*)"/sm', create_function('$m', 'return substr(str_replace(array(";", "|", "~", " ", ",", "/", "(", ")", \'`"\'), array("\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\""), $m[0]), 1, -1);'), trim($t)));
        for ($i = count(($t = explode(';', $t))); --$i >= AppConstant::NUMERIC_ZERO;) {
            $w = $t[$i];
            if (empty($w) or ($e = strpos($w, '=')) === false or !strlen(($a = substr($w, $e + AppConstant::NUMERIC_ONE)))) {
                continue;
            }
            $y = $n = array();
            foreach (explode(',', $a) as $v) {
                if (!preg_match('`^([a-z:\-\*]+)(?:\((.*?)\))?`i', $v, $m)) {
                    continue;
                }
                if (($x = strtolower($m[1])) == '-*') {
                    $n['*'] = AppConstant::NUMERIC_ONE;
                    continue;
                }
                if ($x[0] == '-') {
                    $n[substr($x, AppConstant::NUMERIC_ONE)] = AppConstant::NUMERIC_ONE;
                    continue;
                }
                if (!isset($m[2])) {
                    $y[$x] = AppConstant::NUMERIC_ONE;
                    continue;
                }
                foreach (explode('/', $m[2]) as $m) {
                    if (empty($m) or ($p = strpos($m, '=')) == AppConstant::NUMERIC_ZERO or $p < AppConstant::NUMERIC_FIVE) {
                        $y[$x] = AppConstant::NUMERIC_ONE;
                        continue;
                    }
                    $y[$x][strtolower(substr($m, AppConstant::NUMERIC_ZERO, $p))] = str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08"), array(";", "|", "~", " ", ",", "/", "(", ")"), substr($m, $p + AppConstant::NUMERIC_ONE));
                }
                if (isset($y[$x]['match']) && !self::hl_regex($y[$x]['match'])) {
                    unset($y[$x]['match']);
                }
                if (isset($y[$x]['nomatch']) && !self::hl_regex($y[$x]['nomatch'])) {
                    unset($y[$x]['nomatch']);
                }
            }
            if (!count($y) && !count($n)) {
                continue;
            }
            foreach (explode(',', substr($w, AppConstant::NUMERIC_ZERO, $e)) as $v) {
                if (!strlen(($v = strtolower($v)))) {
                    continue;
                }
                if (count($y)) {
                    $s[$v] = $y;
                }
                if (count($n)) {
                    $s[$v]['n'] = $n;
                }
            }
        }
        return $s;
// eof
    }

    public static function hl_tag($t)
    {
// tag/attribute handler
        global $C;
        $t = $t[0];
// invalid < >
        if ($t == '< ') {
            return '&lt; ';
        }
        if ($t == '>') {
            return '&gt;';
        }
        if (!preg_match('`^<(/?)([a-zA-Z][a-zA-Z1-6]*)([^>]*?)\s?>$`m', $t, $m)) {
            return str_replace(array('<', '>'), array('&lt;', '&gt;'), $t);
        } elseif (!isset($C['elements'][($e = strtolower($m[2]))])) {
            return (($C['keep_bad'] % AppConstant::NUMERIC_TWO) ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $t) : '');
        }
// attr string
        $a = str_replace(array("\n", "\r", "\t"), ' ', trim($m[3]));
// tag transform
        static $eD = array('applet' => AppConstant::NUMERIC_ONE, 'center' => AppConstant::NUMERIC_ONE, 'dir' => AppConstant::NUMERIC_ONE, 'embed' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'menu' => AppConstant::NUMERIC_ONE, 's' => AppConstant::NUMERIC_ONE, 'strike' => AppConstant::NUMERIC_ONE, 'u' => AppConstant::NUMERIC_ONE); // Deprecated
        if ($C['make_tag_strict'] && isset($eD[$e])) {
            $trt = self::hl_tag2($e, $a, $C['make_tag_strict']);
            if (!$e) {
                return (($C['keep_bad'] % AppConstant::NUMERIC_TWO) ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $t) : '');
            }
        }
// close tag
        static $eE = array('area' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'col' => AppConstant::NUMERIC_ONE, 'embed' => AppConstant::NUMERIC_ONE, 'hr' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE); // Empty ele
        if (!empty($m[1])) {
            return (!isset($eE[$e]) ? (empty($C['hook_tag']) ? "</$e>" : $C['hook_tag']($e)) : (($C['keep_bad']) % AppConstant::NUMERIC_TWO ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $t) : ''));
        }

// open tag & attr
        static $aN = array('abbr' => array('td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'accept-charset' => array('form' => AppConstant::NUMERIC_ONE), 'accept' => array('form' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE), 'accesskey' => array('a' => AppConstant::NUMERIC_ONE, 'area' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'label' => AppConstant::NUMERIC_ONE, 'legend' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE), 'action' => array('form' => AppConstant::NUMERIC_ONE), 'align' => array('caption' => AppConstant::NUMERIC_ONE, 'embed' => AppConstant::NUMERIC_ONE, 'applet' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'legend' => AppConstant::NUMERIC_ONE, 'table' => AppConstant::NUMERIC_ONE, 'hr' => AppConstant::NUMERIC_ONE, 'div' => AppConstant::NUMERIC_ONE, 'h1' => AppConstant::NUMERIC_ONE, 'h2' => AppConstant::NUMERIC_ONE, 'h3' => AppConstant::NUMERIC_ONE, 'h4' => AppConstant::NUMERIC_ONE, 'h5' => AppConstant::NUMERIC_ONE, 'h6' => AppConstant::NUMERIC_ONE, 'p' => AppConstant::NUMERIC_ONE, 'col' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE, 'tbody' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE), 'alt' => array('applet' => AppConstant::NUMERIC_ONE, 'area' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE), 'archive' => array('applet' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE), 'axis' => array('td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'bgcolor' => array('embed' => AppConstant::NUMERIC_ONE, 'table' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'border' => array('table' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE), 'bordercolor' => array('table' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE), 'cellpadding' => array('table' => AppConstant::NUMERIC_ONE), 'cellspacing' => array('table' => AppConstant::NUMERIC_ONE), 'char' => array('col' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE, 'tbody' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE), 'charoff' => array('col' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE, 'tbody' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE), 'charset' => array('a' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'checked' => array('input' => AppConstant::NUMERIC_ONE), 'cite' => array('blockquote' => AppConstant::NUMERIC_ONE, 'q' => AppConstant::NUMERIC_ONE, 'del' => AppConstant::NUMERIC_ONE, 'ins' => AppConstant::NUMERIC_ONE), 'classid' => array('object' => AppConstant::NUMERIC_ONE), 'clear' => array('br' => AppConstant::NUMERIC_ONE), 'code' => array('applet' => AppConstant::NUMERIC_ONE), 'codebase' => array('object' => AppConstant::NUMERIC_ONE, 'applet' => AppConstant::NUMERIC_ONE), 'codetype' => array('object' => AppConstant::NUMERIC_ONE), 'color' => array('font' => AppConstant::NUMERIC_ONE), 'cols' => array('textarea' => AppConstant::NUMERIC_ONE), 'colspan' => array('td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'compact' => array('dir' => AppConstant::NUMERIC_ONE, 'dl' => AppConstant::NUMERIC_ONE, 'menu' => AppConstant::NUMERIC_ONE, 'ol' => AppConstant::NUMERIC_ONE, 'ul' => AppConstant::NUMERIC_ONE), 'coords' => array('area' => AppConstant::NUMERIC_ONE, 'a' => AppConstant::NUMERIC_ONE), 'data' => array('object' => AppConstant::NUMERIC_ONE), 'datetime' => array('del' => AppConstant::NUMERIC_ONE, 'ins' => AppConstant::NUMERIC_ONE), 'declare' => array('object' => AppConstant::NUMERIC_ONE), 'defer' => array('script' => AppConstant::NUMERIC_ONE), 'dir' => array('bdo' => AppConstant::NUMERIC_ONE), 'disabled' => array('button' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'optgroup' => AppConstant::NUMERIC_ONE, 'option' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE), 'enctype' => array('form' => AppConstant::NUMERIC_ONE), 'face' => array('font' => AppConstant::NUMERIC_ONE), 'flashvars' => array('embed' => AppConstant::NUMERIC_ONE), 'for' => array('label' => AppConstant::NUMERIC_ONE), 'frame' => array('table' => AppConstant::NUMERIC_ONE), 'frameborder' => array('iframe' => AppConstant::NUMERIC_ONE), 'headers' => array('td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'height' => array('embed' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'applet' => AppConstant::NUMERIC_ONE), 'href' => array('a' => AppConstant::NUMERIC_ONE, 'area' => AppConstant::NUMERIC_ONE), 'hreflang' => array('a' => AppConstant::NUMERIC_ONE), 'hspace' => array('applet' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE), 'ismap' => array('img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE), 'label' => array('option' => AppConstant::NUMERIC_ONE, 'optgroup' => AppConstant::NUMERIC_ONE), 'language' => array('script' => AppConstant::NUMERIC_ONE), 'longdesc' => array('img' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE), 'marginheight' => array('iframe' => AppConstant::NUMERIC_ONE), 'marginwidth' => array('iframe' => AppConstant::NUMERIC_ONE), 'maxlength' => array('input' => AppConstant::NUMERIC_ONE), 'method' => array('form' => AppConstant::NUMERIC_ONE), 'model' => array('embed' => AppConstant::NUMERIC_ONE), 'multiple' => array('select' => AppConstant::NUMERIC_ONE), 'name' => array('button' => AppConstant::NUMERIC_ONE, 'embed' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE, 'applet' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'a' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'map' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE), 'nohref' => array('area' => AppConstant::NUMERIC_ONE), 'noshade' => array('hr' => AppConstant::NUMERIC_ONE), 'nowrap' => array('td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'object' => array('applet' => AppConstant::NUMERIC_ONE), 'onblur' => array('a' => AppConstant::NUMERIC_ONE, 'area' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'label' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE), 'onchange' => array('input' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE), 'onfocus' => array('a' => AppConstant::NUMERIC_ONE, 'area' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'label' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE), 'onreset' => array('form' => AppConstant::NUMERIC_ONE), 'onselect' => array('input' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE), 'onsubmit' => array('form' => AppConstant::NUMERIC_ONE), 'pluginspage' => array('embed' => AppConstant::NUMERIC_ONE), 'pluginurl' => array('embed' => AppConstant::NUMERIC_ONE), 'prompt' => array('isindex' => AppConstant::NUMERIC_ONE), 'readonly' => array('textarea' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE), 'rel' => array('a' => AppConstant::NUMERIC_ONE), 'rev' => array('a' => AppConstant::NUMERIC_ONE), 'rows' => array('textarea' => AppConstant::NUMERIC_ONE), 'rowspan' => array('td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'rules' => array('table' => AppConstant::NUMERIC_ONE), 'scope' => array('td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'scrolling' => array('iframe' => AppConstant::NUMERIC_ONE), 'selected' => array('option' => AppConstant::NUMERIC_ONE), 'shape' => array('area' => AppConstant::NUMERIC_ONE, 'a' => AppConstant::NUMERIC_ONE), 'size' => array('hr' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE), 'span' => array('col' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE), 'src' => array('embed' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE), 'standby' => array('object' => AppConstant::NUMERIC_ONE), 'start' => array('ol' => AppConstant::NUMERIC_ONE), 'summary' => array('table' => AppConstant::NUMERIC_ONE), 'tabindex' => array('a' => AppConstant::NUMERIC_ONE, 'area' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE), 'target' => array('a' => AppConstant::NUMERIC_ONE, 'area' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE), 'type' => array('a' => AppConstant::NUMERIC_ONE, 'embed' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'li' => AppConstant::NUMERIC_ONE, 'ol' => AppConstant::NUMERIC_ONE, 'ul' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE), 'usemap' => array('img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE), 'valign' => array('col' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE, 'tbody' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE), 'value' => array('input' => AppConstant::NUMERIC_ONE, 'option' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'li' => AppConstant::NUMERIC_ONE), 'valuetype' => array('param' => AppConstant::NUMERIC_ONE), 'vspace' => array('applet' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE), 'width' => array('embed' => AppConstant::NUMERIC_ONE, 'hr' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'table' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'applet' => AppConstant::NUMERIC_ONE, 'col' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE, 'pre' => AppConstant::NUMERIC_ONE), 'wmode' => array('embed' => AppConstant::NUMERIC_ONE), 'xml:space' => array('pre' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'style' => AppConstant::NUMERIC_ONE)); // Ele-specific
        static $aNE = array('checked' => AppConstant::NUMERIC_ONE, 'compact' => AppConstant::NUMERIC_ONE, 'declare' => AppConstant::NUMERIC_ONE, 'defer' => AppConstant::NUMERIC_ONE, 'disabled' => AppConstant::NUMERIC_ONE, 'ismap' => AppConstant::NUMERIC_ONE, 'multiple' => AppConstant::NUMERIC_ONE, 'nohref' => AppConstant::NUMERIC_ONE, 'noresize' => AppConstant::NUMERIC_ONE, 'noshade' => AppConstant::NUMERIC_ONE, 'nowrap' => AppConstant::NUMERIC_ONE, 'readonly' => AppConstant::NUMERIC_ONE, 'selected' => AppConstant::NUMERIC_ONE); // Empty
        static $aNP = array('action' => AppConstant::NUMERIC_ONE, 'cite' => AppConstant::NUMERIC_ONE, 'classid' => AppConstant::NUMERIC_ONE, 'codebase' => AppConstant::NUMERIC_ONE, 'data' => AppConstant::NUMERIC_ONE, 'href' => AppConstant::NUMERIC_ONE, 'longdesc' => AppConstant::NUMERIC_ONE, 'model' => AppConstant::NUMERIC_ONE, 'pluginspage' => AppConstant::NUMERIC_ONE, 'pluginurl' => AppConstant::NUMERIC_ONE, 'usemap' => AppConstant::NUMERIC_ONE); // Need scheme check; excludes style, on* & src
        static $aNU = array('class' => array('param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'dir' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'id' => array('script' => AppConstant::NUMERIC_ONE), 'lang' => array('applet' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'xml:lang' => array('applet' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'onclick' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'ondblclick' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'onkeydown' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'onkeypress' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'onkeyup' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'onmousedown' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'onmousemove' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'onmouseout' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'onmouseover' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'onmouseup' => array('applet' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'font' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'style' => array('param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE), 'title' => array('param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE)); // Univ & exceptions

        if ($C['lc_std_val']) {
            // predef attr vals for $eAL & $aNE ele
            static $aNL = array('all' => AppConstant::NUMERIC_ONE, 'baseline' => AppConstant::NUMERIC_ONE, 'bottom' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'center' => AppConstant::NUMERIC_ONE, 'char' => AppConstant::NUMERIC_ONE, 'checkbox' => AppConstant::NUMERIC_ONE, 'circle' => AppConstant::NUMERIC_ONE, 'col' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE, 'cols' => AppConstant::NUMERIC_ONE, 'data' => AppConstant::NUMERIC_ONE, 'default' => AppConstant::NUMERIC_ONE, 'file' => AppConstant::NUMERIC_ONE, 'get' => AppConstant::NUMERIC_ONE, 'groups' => AppConstant::NUMERIC_ONE, 'hidden' => AppConstant::NUMERIC_ONE, 'image' => AppConstant::NUMERIC_ONE, 'justify' => AppConstant::NUMERIC_ONE, 'left' => AppConstant::NUMERIC_ONE, 'ltr' => AppConstant::NUMERIC_ONE, 'middle' => AppConstant::NUMERIC_ONE, 'none' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'password' => AppConstant::NUMERIC_ONE, 'poly' => AppConstant::NUMERIC_ONE, 'post' => AppConstant::NUMERIC_ONE, 'preserve' => AppConstant::NUMERIC_ONE, 'radio' => AppConstant::NUMERIC_ONE, 'rect' => AppConstant::NUMERIC_ONE, 'ref' => AppConstant::NUMERIC_ONE, 'reset' => AppConstant::NUMERIC_ONE, 'right' => AppConstant::NUMERIC_ONE, 'row' => AppConstant::NUMERIC_ONE, 'rowgroup' => AppConstant::NUMERIC_ONE, 'rows' => AppConstant::NUMERIC_ONE, 'rtl' => AppConstant::NUMERIC_ONE, 'submit' => AppConstant::NUMERIC_ONE, 'text' => AppConstant::NUMERIC_ONE, 'top' => AppConstant::NUMERIC_ONE);
            static $eAL = array('a' => AppConstant::NUMERIC_ONE, 'area' => AppConstant::NUMERIC_ONE, 'bdo' => AppConstant::NUMERIC_ONE, 'button' => AppConstant::NUMERIC_ONE, 'col' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'optgroup' => AppConstant::NUMERIC_ONE, 'option' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'table' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE, 'xml:space' => AppConstant::NUMERIC_ONE);
            $lcase = isset($eAL[$e]) ? AppConstant::NUMERIC_ONE : AppConstant::NUMERIC_ZERO;
        }

        $depTr = AppConstant::NUMERIC_ZERO;
        if ($C['no_deprecated_attr']) {
            // dep attr:applicable ele
            static $aND = array('align' => array('caption' => AppConstant::NUMERIC_ONE, 'div' => AppConstant::NUMERIC_ONE, 'h1' => AppConstant::NUMERIC_ONE, 'h2' => AppConstant::NUMERIC_ONE, 'h3' => AppConstant::NUMERIC_ONE, 'h4' => AppConstant::NUMERIC_ONE, 'h5' => AppConstant::NUMERIC_ONE, 'h6' => AppConstant::NUMERIC_ONE, 'hr' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'legend' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'p' => AppConstant::NUMERIC_ONE, 'table' => AppConstant::NUMERIC_ONE), 'bgcolor' => array('table' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE), 'border' => array('img' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE), 'bordercolor' => array('table' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE), 'clear' => array('br' => AppConstant::NUMERIC_ONE), 'compact' => array('dl' => AppConstant::NUMERIC_ONE, 'ol' => AppConstant::NUMERIC_ONE, 'ul' => AppConstant::NUMERIC_ONE), 'height' => array('td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'hspace' => array('img' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE), 'language' => array('script' => AppConstant::NUMERIC_ONE), 'name' => array('a' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'map' => AppConstant::NUMERIC_ONE), 'noshade' => array('hr' => AppConstant::NUMERIC_ONE), 'nowrap' => array('td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE), 'size' => array('hr' => AppConstant::NUMERIC_ONE), 'start' => array('ol' => AppConstant::NUMERIC_ONE), 'type' => array('li' => AppConstant::NUMERIC_ONE, 'ol' => AppConstant::NUMERIC_ONE, 'ul' => AppConstant::NUMERIC_ONE), 'value' => array('li' => AppConstant::NUMERIC_ONE), 'vspace' => array('img' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE), 'width' => array('hr' => AppConstant::NUMERIC_ONE, 'pre' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE));
            static $eAD = array('a' => AppConstant::NUMERIC_ONE, 'br' => AppConstant::NUMERIC_ONE, 'caption' => AppConstant::NUMERIC_ONE, 'div' => AppConstant::NUMERIC_ONE, 'dl' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'h1' => AppConstant::NUMERIC_ONE, 'h2' => AppConstant::NUMERIC_ONE, 'h3' => AppConstant::NUMERIC_ONE, 'h4' => AppConstant::NUMERIC_ONE, 'h5' => AppConstant::NUMERIC_ONE, 'h6' => AppConstant::NUMERIC_ONE, 'hr' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'img' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'legend' => AppConstant::NUMERIC_ONE, 'li' => AppConstant::NUMERIC_ONE, 'map' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'ol' => AppConstant::NUMERIC_ONE, 'p' => AppConstant::NUMERIC_ONE, 'pre' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'table' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE, 'ul' => AppConstant::NUMERIC_ONE);
            $depTr = isset($eAD[$e]) ? AppConstant::NUMERIC_ONE : AppConstant::NUMERIC_ZERO;
        }

// attr name-vals
        if (strpos($a, "\x01") !== false) {
            $a = preg_replace('`\x01[^\x01]*\x01`', '', $a);
        } // No comment/CDATA sec
        $mode = AppConstant::NUMERIC_ZERO;
        $a = trim($a, ' /');
        $aA = array();
        while (strlen($a)) {
            $w = AppConstant::NUMERIC_ZERO;
            switch ($mode) {
                case AppConstant::NUMERIC_ZERO: // Name
                    if (preg_match('`^[a-zA-Z][\-a-zA-Z:]+`', $a, $m)) {
                        $nm = strtolower($m[0]);
                        $w = $mode = AppConstant::NUMERIC_ONE;
                        $a = ltrim(substr_replace($a, '', AppConstant::NUMERIC_ZERO, strlen($m[0])));
                    }
                    break;
                case AppConstant::NUMERIC_ONE:
                    if ($a[0] == '=') { // =
                        $w = AppConstant::NUMERIC_ONE;
                        $mode = AppConstant::NUMERIC_TWO;
                        $a = ltrim($a, '= ');
                    } else { // No val
                        $w = AppConstant::NUMERIC_ONE;
                        $mode = AppConstant::NUMERIC_ZERO;
                        $a = ltrim($a);
                        $aA[$nm] = '';
                    }
                    break;
                case AppConstant::NUMERIC_TWO: // Val
                    if (preg_match('`^((?:"[^"]*")|(?:\'[^\']*\')|(?:\s*[^\s"\']+))(.*)`', $a, $m)) {
                        $a = ltrim($m[2]);
                        $m = $m[1];
                        $w = AppConstant::NUMERIC_ONE;
                        $mode = AppConstant::NUMERIC_ZERO;
                        $aA[$nm] = trim(str_replace('<', '&lt;', ($m[0] == '"' or $m[0] == '\'') ? substr($m, AppConstant::NUMERIC_ONE, AppConstant::NUMERIC_NEGATIVE_ONE) : $m));
                    }
                    break;
            }
            if ($w == AppConstant::NUMERIC_ZERO) { // Parse errs, deal with space, " & '
                $a = preg_replace('`^(?:"[^"]*("|$)|\'[^\']*(\'|$)|\S)*\s*`', '', $a);
                $mode = AppConstant::NUMERIC_ZERO;
            }
        }
        if ($mode == AppConstant::NUMERIC_ONE) {
            $aA[$nm] = '';
        }
// clean attrs
        global $S;
        $rl = isset($S[$e]) ? $S[$e] : array();
        $a = array();
        $nfr = AppConstant::NUMERIC_ZERO;
        foreach ($aA as $k => $v) {
            if (((isset($C['deny_attribute']['*']) ? isset($C['deny_attribute'][$k]) : !isset($C['deny_attribute'][$k])) && (isset($aN[$k][$e]) or (isset($aNU[$k]) && !isset($aNU[$k][$e]))) && !isset($rl['n'][$k]) && !isset($rl['n']['*'])) or isset($rl[$k])) {
                if (isset($aNE[$k])) {
                    $v = $k;
                } elseif (!empty($lcase) && (($e != 'button' or $e != 'input') or $k == 'type')) { // Rather loose but ?not cause issues
                    $v = (isset($aNL[($v2 = strtolower($v))])) ? $v2 : $v;
                }
                if ($k == 'style' && !$C['style_pass']) {
                    if (false !== strpos($v, '&#')) {
                        static $sC = array('&#x20;' => ' ', '&#32;' => ' ', '&#x45;' => 'e', '&#69;' => 'e', '&#x65;' => 'e', '&#101;' => 'e', '&#x58;' => 'x', '&#88;' => 'x', '&#x78;' => 'x', '&#120;' => 'x', '&#x50;' => 'p', '&#80;' => 'p', '&#x70;' => 'p', '&#112;' => 'p', '&#x53;' => 's', '&#83;' => 's', '&#x73;' => 's', '&#115;' => 's', '&#x49;' => 'i', '&#73;' => 'i', '&#x69;' => 'i', '&#105;' => 'i', '&#x4f;' => 'o', '&#79;' => 'o', '&#x6f;' => 'o', '&#111;' => 'o', '&#x4e;' => 'n', '&#78;' => 'n', '&#x6e;' => 'n', '&#110;' => 'n', '&#x55;' => 'u', '&#85;' => 'u', '&#x75;' => 'u', '&#117;' => 'u', '&#x52;' => 'r', '&#82;' => 'r', '&#x72;' => 'r', '&#114;' => 'r', '&#x4c;' => 'l', '&#76;' => 'l', '&#x6c;' => 'l', '&#108;' => 'l', '&#x28;' => '(', '&#40;' => '(', '&#x29;' => ')', '&#41;' => ')', '&#x20;' => ':', '&#32;' => ':', '&#x22;' => '"', '&#34;' => '"', '&#x27;' => "'", '&#39;' => "'", '&#x2f;' => '/', '&#47;' => '/', '&#x2a;' => '*', '&#42;' => '*', '&#x5c;' => '\\', '&#92;' => '\\');
                        $v = strtr($v, $sC);
                    }
                    $v = preg_replace_callback('`(url(?:\()(?: )*(?:\'|"|&(?:quot|apos);)?)(.+?)((?:\'|"|&(?:quot|apos);)?(?: )*(?:\)))`iS', array('\serhatozles\htmlawed\htmLawed', 'hl_prot'), $v);
                    $v = !$C['css_expression'] ? preg_replace('`expression`i', ' ', preg_replace('`\\\\\S|(/|(%2f))(\*|(%2a))`i', ' ', $v)) : $v;
                } elseif (isset($aNP[$k]) or strpos($k, 'src') !== false or $k[0] == 'o') {
                    $v = str_replace("\xad", ' ', (strpos($v, '&') !== false ? str_replace(array('&#xad;', '&#173;', '&shy;'), ' ', $v) : $v));
                    $v = self::hl_prot($v, $k);
                    if ($k == 'href') { // X-spam
                        if ($C['anti_mail_spam'] && strpos($v, 'mailto:') === AppConstant::NUMERIC_ZERO) {
                            $v = str_replace('@', htmlspecialchars($C['anti_mail_spam']), $v);
                        } elseif ($C['anti_link_spam']) {
                            $r1 = $C['anti_link_spam'][1];
                            if (!empty($r1) && preg_match($r1, $v)) {
                                continue;
                            }
                            $r0 = $C['anti_link_spam'][0];
                            if (!empty($r0) && preg_match($r0, $v)) {
                                if (isset($a['rel'])) {
                                    if (!preg_match('`\bnofollow\b`i', $a['rel'])) {
                                        $a['rel'] .= ' nofollow';
                                    }
                                } elseif (isset($aA['rel'])) {
                                    if (!preg_match('`\bnofollow\b`i', $aA['rel'])) {
                                        $nfr = AppConstant::NUMERIC_ONE;
                                    }
                                } else {
                                    $a['rel'] = 'nofollow';
                                }
                            }
                        }
                    }
                }
                if (isset($rl[$k]) && is_array($rl[$k]) && ($v = self::hl_attrval($v, $rl[$k])) === AppConstant::NUMERIC_ZERO) {
                    continue;
                }
                $a[$k] = str_replace('"', '&quot;', $v);
            }
        }
        if ($nfr) {
            $a['rel'] = isset($a['rel']) ? $a['rel'] . ' nofollow' : 'nofollow';
        }
// rqd attr
        static $eAR = array('area' => array('alt' => 'area'), 'bdo' => array('dir' => 'ltr'), 'form' => array('action' => ''), 'img' => array('src' => '', 'alt' => 'image'), 'map' => array('name' => ''), 'optgroup' => array('label' => ''), 'param' => array('name' => ''), 'script' => array('type' => 'text/javascript'), 'textarea' => array('rows' => '10', 'cols' => '50'));
        if (isset($eAR[$e])) {
            foreach ($eAR[$e] as $k => $v) {
                if (!isset($a[$k])) {
                    $a[$k] = isset($v[0]) ? $v : $k;
                }
            }
        }
// depr attrs
        if ($depTr) {
            $c = array();
            foreach ($a as $k => $v) {
                if ($k == 'style' or !isset($aND[$k][$e])) {
                    continue;
                }
                if ($k == 'align') {
                    unset($a['align']);
                    if ($e == 'img' && ($v == 'left' or $v == 'right')) {
                        $c[] = 'float: ' . $v;
                    } elseif (($e == 'div' or $e == 'table') && $v == 'center') {
                        $c[] = 'margin: auto';
                    } else {
                        $c[] = 'text-align: ' . $v;
                    }
                } elseif ($k == 'bgcolor') {
                    unset($a['bgcolor']);
                    $c[] = 'background-color: ' . $v;
                } elseif ($k == 'border') {
                    unset($a['border']);
                    $c[] = "border: {$v}px";
                } elseif ($k == 'bordercolor') {
                    unset($a['bordercolor']);
                    $c[] = 'border-color: ' . $v;
                } elseif ($k == 'clear') {
                    unset($a['clear']);
                    $c[] = 'clear: ' . ($v != 'all' ? $v : 'both');
                } elseif ($k == 'compact') {
                    unset($a['compact']);
                    $c[] = 'font-size: 85%';
                } elseif ($k == 'height' or $k == 'width') {
                    unset($a[$k]);
                    $c[] = $k . ': ' . ($v[0] != '*' ? $v . (ctype_digit($v) ? 'px' : '') : 'auto');
                } elseif ($k == 'hspace') {
                    unset($a['hspace']);
                    $c[] = "margin-left: {$v}px; margin-right: {$v}px";
                } elseif ($k == 'language' && !isset($a['type'])) {
                    unset($a['language']);
                    $a['type'] = 'text/' . strtolower($v);
                } elseif ($k == 'name') {
                    if ($C['no_deprecated_attr'] == AppConstant::NUMERIC_TWO or ($e != 'a' && $e != 'map')) {
                        unset($a['name']);
                    }
                    if (!isset($a['id']) && preg_match('`[a-zA-Z][a-zA-Z\d.:_\-]*`', $v)) {
                        $a['id'] = $v;
                    }
                } elseif ($k == 'noshade') {
                    unset($a['noshade']);
                    $c[] = 'border-style: none; border: 0; background-color: gray; color: gray';
                } elseif ($k == 'nowrap') {
                    unset($a['nowrap']);
                    $c[] = 'white-space: nowrap';
                } elseif ($k == 'size') {
                    unset($a['size']);
                    $c[] = 'size: ' . $v . 'px';
                } elseif ($k == 'start' or $k == 'value') {
                    unset($a[$k]);
                } elseif ($k == 'type') {
                    unset($a['type']);
                    static $ol_type = array('i' => 'lower-roman', 'I' => 'upper-roman', 'a' => 'lower-latin', 'A' => 'upper-latin', '1' => 'decimal');
                    $c[] = 'list-style-type: ' . (isset($ol_type[$v]) ? $ol_type[$v] : 'decimal');
                } elseif ($k == 'vspace') {
                    unset($a['vspace']);
                    $c[] = "margin-top: {$v}px; margin-bottom: {$v}px";
                }
            }
            if (count($c)) {
                $c = implode('; ', $c);
                $a['style'] = isset($a['style']) ? rtrim($a['style'], ' ;') . '; ' . $c . ';' : $c . ';';
            }
        }
// unique ID
        if ($C['unique_ids'] && isset($a['id'])) {
            if (!preg_match('`^[A-Za-z][A-Za-z0-9_\-.:]*$`', ($id = $a['id'])) or (isset($GLOBALS['hl_Ids'][$id]) && $C['unique_ids'] == AppConstant::NUMERIC_ONE)) {
                unset($a['id']);
            } else {
                while (isset($GLOBALS['hl_Ids'][$id])) {
                    $id = $C['unique_ids'] . $id;
                }
                $GLOBALS['hl_Ids'][($a['id'] = $id)] = AppConstant::NUMERIC_ONE;
            }
        }
// xml:lang
        if ($C['xml:lang'] && isset($a['lang'])) {
            $a['xml:lang'] = isset($a['xml:lang']) ? $a['xml:lang'] : $a['lang'];
            if ($C['xml:lang'] == AppConstant::NUMERIC_TWO) {
                unset($a['lang']);
            }
        }
// for transformed tag
        if (!empty($trt)) {
            $a['style'] = isset($a['style']) ? rtrim($a['style'], ' ;') . '; ' . $trt : $trt;
        }
// return with empty ele /
        if (empty($C['hook_tag'])) {
            $aA = '';
            foreach ($a as $k => $v) {
                $aA .= " {$k}=\"{$v}\"";
            }
            return "<{$e}{$aA}" . (isset($eE[$e]) ? ' /' : '') . '>';
        } else {
            return $C['hook_tag']($e, $a);
        }
// eof
    }

    public static function hl_tag2(&$e, &$a, $t = AppConstant::NUMERIC_ONE)
    {
// transform tag
        if ($e == 'center') {
            $e = 'div';
            return 'text-align: center;';
        }
        if ($e == 'dir' or $e == 'menu') {
            $e = 'ul';
            return '';
        }
        if ($e == 's' or $e == 'strike') {
            $e = 'span';
            return 'text-decoration: line-through;';
        }
        if ($e == 'u') {
            $e = 'span';
            return 'text-decoration: underline;';
        }
        static $fs = array(AppConstant::ZERO_VALUE => 'xx-small', '1' => 'xx-small', '2' => 'small', '3' => 'medium', '4' => 'large', '5' => 'x-large', '6' => 'xx-large', '7' => '300%', '-1' => 'smaller', '-2' => '60%', '+1' => 'larger', '+2' => '150%', '+3' => '200%', '+4' => '300%');
        if ($e == 'font') {
            $a2 = '';
            if (preg_match('`face\s*=\s*(\'|")([^=]+?)\\1`i', $a, $m) or preg_match('`face\s*=(\s*)(\S+)`i', $a, $m)) {
                $a2 .= ' font-family: ' . str_replace('"', '\'', trim($m[2])) . ';';
            }
            if (preg_match('`color\s*=\s*(\'|")?(.+?)(\\1|\s|$)`i', $a, $m)) {
                $a2 .= ' color: ' . trim($m[2]) . ';';
            }
            if (preg_match('`size\s*=\s*(\'|")?(.+?)(\\1|\s|$)`i', $a, $m) && isset($fs[($m = trim($m[2]))])) {
                $a2 .= ' font-size: ' . $fs[$m] . ';';
            }
            $e = 'span';
            return ltrim($a2);
        }
        if ($t == AppConstant::NUMERIC_TWO) {
            $e = AppConstant::NUMERIC_ZERO;
            return AppConstant::NUMERIC_ZERO;
        }
        return '';
// eof
    }

    public static function get_meta_keys()
    {
        $keys = array();
        $keys[] = 'settings';

        // key value json
    }

    public static function t($key, $echo = true)
    {
//        $language = self::getLanguage();
//        $val = Yii::t('yii_'.$language, $key);
        $val = Yii::t('yii', $key);
        if ($echo) {
            echo $val;
        } else {
            return $val;
        }
    }

    private static function getLanguage(){
        /*
         * http contact : session
         */
    }



    public static function hl_tidy($t, $w, $p)
    {
// Tidy/compact HTM
        if (strpos(' pre,script,textarea', "$p,")) {
            return $t;
        }
        $t = preg_replace('`\s+`', ' ', preg_replace_callback(array('`(<(!\[CDATA\[))(.+?)(\]\]>)`sm', '`(<(!--))(.+?)(-->)`sm', '`(<(pre|script|textarea)[^>]*?>)(.+?)(</\2>)`sm'), create_function('$m', 'return $m[1]. str_replace(array("<", ">", "\n", "\r", "\t", " "), array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"), $m[3]). $m[4];'), $t));
        if (($w = strtolower($w)) == AppConstant::NUMERIC_NEGATIVE_ONE) {
            return str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"), array('<', '>', "\n", "\r", "\t", ' '), $t);
        }
        $s = strpos(" $w", 't') ? "\t" : ' ';
        $s = preg_match('`\d`', $w, $m) ? str_repeat($s, $m[0]) : str_repeat($s, ($s == "\t" ? AppConstant::NUMERIC_ONE : AppConstant::NUMERIC_TWO));
        $N = preg_match('`[ts]([1-9])`', $w, $m) ? $m[1] : AppConstant::NUMERIC_ZERO;
        $a = array('br' => AppConstant::NUMERIC_ONE);
        $b = array('button' => AppConstant::NUMERIC_ONE, 'input' => AppConstant::NUMERIC_ONE, 'option' => AppConstant::NUMERIC_ONE, 'param' => AppConstant::NUMERIC_ONE);
        $c = array('caption' => AppConstant::NUMERIC_ONE, 'dd' => AppConstant::NUMERIC_ONE, 'dt' => AppConstant::NUMERIC_ONE, 'h1' => AppConstant::NUMERIC_ONE, 'h2' => AppConstant::NUMERIC_ONE, 'h3' => AppConstant::NUMERIC_ONE, 'h4' => AppConstant::NUMERIC_ONE, 'h5' => AppConstant::NUMERIC_ONE, 'h6' => AppConstant::NUMERIC_ONE, 'isindex' => AppConstant::NUMERIC_ONE, 'label' => AppConstant::NUMERIC_ONE, 'legend' => AppConstant::NUMERIC_ONE, 'li' => AppConstant::NUMERIC_ONE, 'object' => AppConstant::NUMERIC_ONE, 'p' => AppConstant::NUMERIC_ONE, 'pre' => AppConstant::NUMERIC_ONE, 'td' => AppConstant::NUMERIC_ONE, 'textarea' => AppConstant::NUMERIC_ONE, 'th' => AppConstant::NUMERIC_ONE);
        $d = array('address' => AppConstant::NUMERIC_ONE, 'blockquote' => AppConstant::NUMERIC_ONE, 'center' => AppConstant::NUMERIC_ONE, 'colgroup' => AppConstant::NUMERIC_ONE, 'dir' => AppConstant::NUMERIC_ONE, 'div' => AppConstant::NUMERIC_ONE, 'dl' => AppConstant::NUMERIC_ONE, 'fieldset' => AppConstant::NUMERIC_ONE, 'form' => AppConstant::NUMERIC_ONE, 'hr' => AppConstant::NUMERIC_ONE, 'iframe' => AppConstant::NUMERIC_ONE, 'map' => AppConstant::NUMERIC_ONE, 'menu' => AppConstant::NUMERIC_ONE, 'noscript' => AppConstant::NUMERIC_ONE, 'ol' => AppConstant::NUMERIC_ONE, 'optgroup' => AppConstant::NUMERIC_ONE, 'rbc' => AppConstant::NUMERIC_ONE, 'rtc' => AppConstant::NUMERIC_ONE, 'ruby' => AppConstant::NUMERIC_ONE, 'script' => AppConstant::NUMERIC_ONE, 'select' => AppConstant::NUMERIC_ONE, 'table' => AppConstant::NUMERIC_ONE, 'tbody' => AppConstant::NUMERIC_ONE, 'tfoot' => AppConstant::NUMERIC_ONE, 'thead' => AppConstant::NUMERIC_ONE, 'tr' => AppConstant::NUMERIC_ONE, 'ul' => AppConstant::NUMERIC_ONE);
        $T = explode('<', $t);
        $X = AppConstant::NUMERIC_ONE;
        while ($X) {
            $n = $N;
            $t = $T;
            ob_start();
            if (isset($d[$p])) {
                echo str_repeat($s, ++$n);
            }
            echo ltrim(array_shift($t));
            for ($i = AppConstant::NUMERIC_NEGATIVE_ONE, $j = count($t); ++$i < $j;) {
                $r = '';
                list($e, $r) = explode('>', $t[$i]);
                $x = $e[0] == '/' ? AppConstant::NUMERIC_ZERO : (substr($e, AppConstant::NUMERIC_NEGATIVE_ONE) == '/' ? AppConstant::NUMERIC_ONE : ($e[0] != '!' ? AppConstant::NUMERIC_TWO : AppConstant::NUMERIC_NEGATIVE_ONE));
                $y = !$x ? ltrim($e, '/') : ($x > AppConstant::NUMERIC_ZERO ? substr($e, AppConstant::NUMERIC_ZERO, strcspn($e, ' ')) : AppConstant::NUMERIC_ZERO);
                $e = "<$e>";
                if (isset($d[$y])) {
                    if (!$x) {
                        if ($n) {
                            echo "\n", str_repeat($s, --$n), "$e\n", str_repeat($s, $n);
                        } else {
                            ++$N;
                            ob_end_clean();
                            continue 2;
                        }
                    } else {
                        echo "\n", str_repeat($s, $n), "$e\n", str_repeat($s, ($x != AppConstant::NUMERIC_ONE ? ++$n : $n));
                    }
                    echo $r;
                    continue;
                }
                $f = "\n" . str_repeat($s, $n);
                if (isset($c[$y])) {
                    if (!$x) {
                        echo $e, $f, $r;
                    } else {
                        echo $f, $e, $r;
                    }
                } elseif (isset($b[$y])) {
                    echo $f, $e, $r;
                } elseif (isset($a[$y])) {
                    echo $e, $f, $r;
                } elseif (!$y) {
                    echo $f, $e, $f, $r;
                } else {
                    echo $e, $r;
                }
            }
            $X = AppConstant::NUMERIC_ZERO;
        }
        $t = str_replace(array("\n ", " \n"), "\n", preg_replace('`[\n]\s*?[\n]+`', "\n", ob_get_contents()));
        ob_end_clean();
        if (($l = strpos(" $w", 'r') ? (strpos(" $w", 'n') ? "\r\n" : "\r") : AppConstant::NUMERIC_ZERO)) {
            $t = str_replace("\n", $l, $t);
        }
        return str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"), array('<', '>', "\n", "\r", "\t", ' '), $t);
// eof
    }

    public static function hl_version()
    {
// rel
        return '1.1.18';
// eof
    }

    public static function kses($t, $h, $p = array('http', 'https', 'ftp', 'news', 'nntp', 'telnet', 'gopher', 'mailto'))
    {
// kses compat
        foreach ($h as $k => $v) {
            $h[$k]['n']['*'] = AppConstant::NUMERIC_ONE;
        }
        $C['cdata'] = $C['comment'] = $C['make_tag_strict'] = $C['no_deprecated_attr'] = $C['unique_ids'] = AppConstant::NUMERIC_ZERO;
        $C['keep_bad'] = AppConstant::NUMERIC_ONE;
        $C['elements'] = count($h) ? strtolower(implode(',', array_keys($h))) : '-*';
        $C['hook'] = 'kses_hook';
        $C['schemes'] = '*:' . implode(',', $p);
        return self::htmLawed($t, $C, $h);
// eof
    }

    public static function kses_hook($t, &$C, &$S)
    {
// kses compat
        return $t;
// eof
    }

    public static function dateMatch($date)
    {
        preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/', $date, $dmatches);
        $date = mktime(AppConstant::NUMERIC_ELEVEN, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ZERO, $dmatches[1], $dmatches[2], $dmatches[3]);
        return $date;
    }

    public static function writeHtmlSelect($name, $valList, $labelList, $selectedVal = null, $defaultLabel = null, $defaultVal = null, $actions = null)
    {
        /*
         *$name is the html name for the select list
        *$valList is an array of strings for the html value tag
        *$labelList is an array of strings that are displayed as the select list
        *$selectVal is optional, if passed the item in $valList that matches will be output as selected
        */
        echo "<select class= 'form-control apply-scroll' name=\"$name\" id=\"$name\" ";
        echo (isset($actions)) ? $actions : "";
        echo ">\n";
        if (isset($defaultLabel) && isset($defaultVal)) {
            echo "		<option value=\"$defaultVal\" selected>$defaultLabel</option>\n";
        }
        for ($i = AppConstant::NUMERIC_ZERO; $i < count($valList); $i++) {
            if ((isset($selectedVal)) && ($valList[$i] == $selectedVal)) {
                echo "		<option value=\"$valList[$i]\" selected>$labelList[$i]</option>\n";
            } else {
                echo "		<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
            }
        }
        echo "</select>\n";
    }

    public static function writeHtmlChecked($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == AppConstant::NUMERIC_ONE)) {
            if ($var != $test) {
                echo "checked ";
            }
        } else {
            if ($var == $test) {
                echo "checked ";
            }
        }
    }

    /*
     * Method for item ordering when the course items are deleted.
     */
    public static function UpdateitemOrdering($courseId, $block, $itemId)
    {
        $course = Course::getById($courseId);
        $itemOrder = $course['itemorder'];
        $items = unserialize($itemOrder);
        $blockTree = explode('-', $block);
        $sub =& $items;
        for ($i = AppConstant::NUMERIC_ONE; $i < count($blockTree); $i++) {
            $sub =& $sub[$blockTree[$i] - AppConstant::NUMERIC_ONE]['items'];
        }
        $key = array_search($itemId, $sub);
        array_splice($sub, $key, AppConstant::NUMERIC_ONE);
        $itemList = serialize($items);
        Course::setItemOrder($itemList, $courseId);
    }

    public static function setshow($id)
    {
        global $parents, $base, $expand;
        array_unshift($expand, $id);
        if (isset($base)) {
            if (isset($parents[$id]) && $parents[$id] != $base) {
                setshow($parents[$id]);
            }
        } else {
            if (isset($parents[$id]) && $parents[$id] != AppConstant::NUMERIC_ZERO) {
                setshow($parents[$id]);
            }
        }
    }

    public function printOutcomes($arr, $individual, $finalData = null, $cnt = null, $n = null, $type = null, $outcomeInfo = null)
    {
        foreach ($arr as $oi) {
            if ($cnt % AppConstant::NUMERIC_TWO == AppConstant::NUMERIC_ZERO) {
                $class = "even";
            } else {
                $class = "odd";
            }
            $cnt++;
            if (is_array($oi)) { //is outcome group
                echo '<tr class="' . $class . '"><td colspan="' . $n . '"><span class="ind' . $individual . '"><b>' . $oi['name'] . '</b></span></td></tr>';
                $this->printOutcomes($oi['outcomes'], $individual + AppConstant::NUMERIC_ONE);
            } else {
                echo '<tr class="' . $class . '">';
                echo '<td><span class="ind' . $individual . '">' . $outcomeInfo[$oi] . '</span></td>';
                if (isset($finalData[1][3][$type]) && isset($finalData[1][3][$type][$oi])) {
                    echo '<td>' . round(AppConstant::NUMERIC_HUNDREAD * $finalData[1][3][$type][$oi], AppConstant::NUMERIC_ONE) . '%</td>';
                } else {
                    echo '<td>-</td>';
                }
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($finalData[0][2]); $i++) {
                    if (isset($finalData[1][2][$i]) && isset($finalData[1][2][$i][2 * $type + 1][$oi])) {
                        if ($finalData[1][2][$i][2 * $type + 1][$oi] > AppConstant::NUMERIC_ZERO) {
                            echo '<td>' . round(AppConstant::NUMERIC_HUNDREAD * $finalData[1][2][$i][2 * $type][$oi] / $finalData[1][2][$i][2 * $type + 1][$oi], AppConstant::NUMERIC_ONE) . '%</td>';
                        } else {
                            echo '<td>0%</td>';
                        }
                    } else {
                        echo '<td>-</td>';
                    }
                }
                echo '</tr>';
            }

        }

    }

    public function printItems($items, $assessNames, $forumNames, $offNames, $linkNames, $inlineNames)
    {
        foreach ($items as $i => $item) {
            if ($i != AppConstant::NUMERIC_ZERO) {
                echo '<br/>';
            }
            if ($item[0] == 'link') {
                echo '<span class="icon iconlink" >L</span> ' . $linkNames[$item[1]];
            } else if ($item[0] == 'inline') {
                echo '<span class="icon iconinline" >I</span> ' . $inlineNames[$item[1]];
            } else if ($item[0] == 'assess') {
                echo '<span class="icon iconassess" >A</span> ' . $assessNames[$item[1]];
            } else if ($item[0] == 'forum') {
                echo '<span class="icon iconforum" >F</span> ' . $forumNames[$item[1]];
            } else if ($item[0] == 'offline') {
                echo '<span class="icon iconoffline" >O</span> ' . $offNames[$item[1]];
            }

        }
    }

    public function printOutcomesForMap($arr, $ind, $outcomeAssoc = null, $outcomeInfo = null, $catNames = null, $n = null, $cnt = null, $items = null, $assessNames = null, $forumNames = null, $offNames = null, $linkNames = null, $inlineNames = null)
    {
        foreach ($arr as $oi) {
            if ($cnt % AppConstant::NUMERIC_TWO == AppConstant::NUMERIC_ZERO) {
                $class = "even";
            } else {
                $class = "odd";
            }
            $cnt++;
            if (is_array($oi)) { //is outcome group
                echo '<tr class="' . $class . '" colspan="' . $n . '"><td><span class="ind' . $ind . '"><b>' . $oi['name'] . '</b></span></td></tr>';
                $this->printOutcomesForMap($oi['outcomes'], $ind + AppConstant::NUMERIC_ONE, $outcomeAssoc, $outcomeInfo, $catNames, $n, $cnt, $items, $assessNames, $forumNames, $offNames, $linkNames, $inlineNames);
            } else {
                echo '<tr class="' . $class . '">';
                echo '<td><span class="ind' . $ind . '">' . $outcomeInfo[$oi] . '</span></td><td>';
                if (isset($outcomeAssoc[$oi]['UG'])) {
                    $this->printItems($outcomeAssoc[$oi]['UG'], $assessNames, $forumNames, $offNames, $linkNames, $inlineNames);
                }
                echo '</td>';
                if ($catNames) {
                    foreach ($catNames as $id => $cn) {
                        echo '<td>';
                        if (isset($outcomeAssoc[$oi][$id])) {
                            $this->printItems($outcomeAssoc[$oi][$id], $assessNames, $forumNames, $offNames, $linkNames, $inlineNames);
                        }
                        echo '</td>';
                    }
                }
                echo '</tr>';
            }
        }
    }

    public function printOutcomesData($arr, $outcomeInfo, $cnt)
    {
        foreach ($arr as $item) {
            if (is_array($item)) { //is outcome group
                echo '<li class="blockli" id="grp' . $cnt . '"><span class=icon style="background-color:#66f">G</span> ';
                echo '<input class="outcome" type="text" size="60" id="g' . $cnt . '" value="' . htmlentities($item['name']) . '" onkeyup="txtchg()"> ';
                echo '<a href="#" onclick="removeoutcomegrp(this);return false">' . _("Delete") . '</a>';
                $cnt++;
                if (count($item['outcomes']) > AppConstant::NUMERIC_ZERO) {
                    echo '<ul class="qview">';
                    $this->printoutcomesData($item['outcomes'], $outcomeInfo, $cnt);
                    echo '</ul>';
                }
                echo '</li>';
            } else { //individual outcome
                echo '<li id="' . $item . '"><span class=icon style="background-color:#0f0">O</span> ';
                echo '<input class="outcome" type="text" size="60" id="o' . $item . '" value="' . htmlentities($outcomeInfo[$item]) . '" onkeyup="txtchg()"> ';
                echo '<a href="#" onclick="removeoutcome(this);return false">' . _("Delete") . '</a></li>';
            }
        }

    }

    public static function generatemoveselect($count, $num)
    {
        $num = $num + AppConstant::NUMERIC_ONE; //adjust indexing
        $html = "<select id=\"ms-$num\" onchange=\"movefile($num)\">\n";
        for ($i = AppConstant::NUMERIC_ONE; $i <= $count; $i++) {
            $html .= "<option value=\"$i\" ";
            if ($i == $num) {
                $html .= "selected=1";
            }
            $html .= ">$i</option>\n";
        }
        $html .= "</select>\n";
        return $html;
    }

    public static function writesessiondata($sessionData,$sessionId)
    {
            $enc = base64_encode(serialize($sessionData));
            Sessions::setSessionId($sessionId,$enc);
    }

    public static function printq($qn,$qsetid,$seed,$pts,$showpts) {
        $urlmode = AppUtility::urlMode();
        global $isfinal,$imasroot,$displayformat,$anstypes,$evaledqtext;
        $homePath = AppUtility::getHomeURL()."Uploads";
        srand($seed);
        $qdata = QuestionSet::getSelectedDataByQuesSetId($qsetid);
        if ($qdata['hasimg'] > AppConstant::NUMERIC_ZERO) {
            $query = QImages::getByQuestionSetId($qsetid);
            foreach ($query as $row) {
                if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                    ${$row['var']} = "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/qimages/{$row['filename']}\" alt=\"".htmlentities($row['alttext'],ENT_QUOTES)."\" />";
                } else {
                    ${$row['var']} = "<img src=\"$homePath/qimages/{$row['filename']}\" alt=\"".htmlentities($row['alttext'],ENT_QUOTES)."\" />";
                }
            }
        }
        eval(interpret('control',$qdata['qtype'],$qdata['control']));
        eval(interpret('qcontrol',$qdata['qtype'],$qdata['qcontrol']));
        $toevalqtxt = interpret('qtext',$qdata['qtype'],$qdata['qtext']);
        $toevalqtxt = str_replace('\\','\\\\',$toevalqtxt);
        $toevalqtxt = str_replace(array('\\\\n','\\\\"','\\\\$','\\\\{'),array('\\n','\\"','\\$','\\{'),$toevalqtxt);
        srand($seed + AppConstant::NUMERIC_ONE);
        eval(interpret('answer',$qdata['qtype'],$qdata['answer']));
        srand($seed + AppConstant::NUMERIC_ONE);
        $la = '';

        if (isset($choices) && !isset($questions)) {
            $questions =& $choices;
        }
        if (isset($variable) && !isset($variables)) {
            $variables =& $variable;
        }
        if ($displayformat=="select") {
            unset($displayformat);
        }

        //pack options
        if (isset($ansprompt)) {$options['ansprompt'] = $ansprompt;}
        if (isset($displayformat)) {$options['displayformat'] = $displayformat;}
        if (isset($answerformat)) {$options['answerformat'] = $answerformat;}
        if (isset($questions)) {$options['questions'] = $questions;}
        if (isset($answers)) {$options['answers'] = $answers;}
        if (isset($answer)) {$options['answer'] = $answer;}
        if (isset($questiontitle)) {$options['questiontitle'] = $questiontitle;}
        if (isset($answertitle)) {$options['answertitle'] = $answertitle;}
        if (isset($answersize)) {$options['answersize'] = $answersize;}
        if (isset($variables)) {$options['variables'] = $variables;}
        if (isset($domain)) {$options['domain'] = $domain;}
        if (isset($answerboxsize)) {$options['answerboxsize'] = $answerboxsize;}
        if (isset($hidepreview)) {$options['hidepreview'] = $hidepreview;}
        if (isset($matchlist)) {$options['matchlist'] = $matchlist;}
        if (isset($noshuffle)) {$options['noshuffle'] = $noshuffle;}
        if (isset($reqdecimals)) {$options['reqdecimals'] = $reqdecimals;}
        if (isset($grid)) {$options['grid'] = $grid;}
        if (isset($background)) {$options['background'] = $background;}

        if ($qdata['qtype']=="multipart") {
            if (!is_array($anstypes)) {
                $anstypes = explode(",",$anstypes);
            }
            $laparts = explode("&",$la);
            foreach ($anstypes as $kidx=>$anstype) {
                list($answerbox[$kidx],$tips[$kidx],$shans[$kidx]) = makeanswerbox($anstype,$kidx,$laparts[$kidx],$options,$qn+1);
            }
        } else {
            list($answerbox,$tips[0],$shans[0]) = makeanswerbox($qdata['qtype'],$qn,$la,$options,0);
        }

        echo "<br/><div class='col-md-12 col-sm-12 q'>";
        if ($isfinal) {
            echo "<div class=\"trq$qn\">\n";
        } else {
            echo "<div class='m col-md-12 col-sm-12' id=\"trq$qn\">\n";
        }
        if ($showpts) {
            echo ($qn+1).'. ('.$pts.' pts) ';
        }
        echo "<div>\n";
        eval("\$evaledqtext = \"$toevalqtxt\";");
        echo printfilter(filter($evaledqtext));
        echo "</div>\n"; //end question div

        if (strpos($toevalqtxt,'$answerbox')===false) {
            if (is_array($answerbox)) {
                foreach($answerbox as $iidx=>$abox) {
                    echo printfilter(filter("<div>$abox</div>\n"));
                    echo "<div class=spacer>&nbsp;</div>\n";
                }
            } else {  //one question only
                echo printfilter(filter("<div>$answerbox</div>\n"));
            }
        }
        echo "</div>";//end m div

        echo "&nbsp;";
        echo "</div>\n"; //end q div
        if (!isset($showanswer)) {
            return $shans;
        } else {
            return $showanswer;
        }
    }

    public function printlist($parent,$names = null,$ltlibs = null,$count = null, $qcount = null, $cid = null, $rights = null, $sortorder = null, $ownerids = null, $userid = null, $isadmin = null, $groupids = null, $groupid = null, $isgrpadmin = null)
    {
        $arr = $ltlibs[$parent];
        if ($sortorder[$parent]==1) {
            $orderarr = array();
            foreach ($arr as $child) {
                $orderarr[$child] = $names[$child];
            }
            natcasesort($orderarr);
            $arr = array_keys($orderarr);
        }


        foreach ($arr as $child) {

            //if ($rights[$child]>0 || $ownerids[$child]==$userid || $isadmin) {
            if ($rights[$child]>2 || ($rights[$child]>0 && $groupids[$child]==$groupid) || $ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) ||$isadmin) {
                if (!$isadmin) {
                    if ($rights[$child]==5 && $groupids[$child]!=$groupid) {
                        $rights[$child]=4;  //adjust coloring
                    }
                }

                if (isset($ltlibs[$child])) { //library has children
                    echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle($child)\"><span class=btn id=\"b$child\">+</span> ";
                    echo "</span><input type=checkbox name=\"nchecked[]\" value=$child> <span class=hdr onClick=\"toggle($child)\"><span class=\"r{$rights[$child]}\">{$names[$child]}</span> </span>\n";
                    echo " ({$qcount[$child]}) ";
                    echo "<span class=op>";

                    if ($ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) || $isadmin) {
                        echo "<a href=\"manage-lib?cid=$cid&modify=$child\">Modify</a> | ";
                        echo "<a href=\"manage-lib?cid=$cid&remove=$child\">Delete</a> | ";
                        echo "<a href=\"manage-lib?cid=$cid&transfer=$child\">Transfer</a> | ";
                    }
                    echo "<a href=\"manage-lib?cid=$cid&modify=new&parent=$child\">Add Sub</a> ";
                    echo "<ul class=hide id=$child>\n";
                    echo "</span>";
                    $count++;
                    $this->printlist($child,$names,$ltlibs,$count,$qcount,$cid,$rights,$sortorder,$ownerids,$userid,$isadmin,$groupids,$groupid,$isgrpadmin);
                    echo "</ul></li>\n";

                } else {  //no children

                    echo "<li><span class=dd>-</span><input type=checkbox name=\"nchecked[]\" value=$child> <span class=\"r{$rights[$child]}\">{$names[$child]}</span> ";
                    echo " ({$qcount[$child]}) ";
                    echo "<span class=op>";

                    if ($ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) || $isadmin) {

                        echo "<a href=\"manage-lib?cid=$cid&modify=$child\">Modify</a> | ";
                        echo "<a href=\"manage-lib?cid=$cid&remove=$child\">Delete</a> | ";
                        echo "<a href=\"manage-lib?cid=$cid&transfer=$child\">Transfer</a> | ";
                    }
                    if ($qcount[$child]==0) {
                        echo "<a href=\"manage-lib?cid=$cid&modify=new&parent=$child\">Add Sub</a> ";
                    } else {
                        echo "<a href=\"review-library?cid=$cid&lib=$child\">Preview</a>";
                    }
                    echo "</span>";
                    echo "</li>\n";
                }
            }
        }
        return $parent;
    }

    public function quickview($items,$parent,$showdates=false,$showlinks=true)
    {
        global $courseId,$openblocks,$previewShift,$CFG;
        if (!is_array($openblocks))
        {
            $openblocks = array();
        }
        $itemtypes = array();
        $iteminfo = array();
        /**
         * Item data
         */
        $itemData = Items::getDataByCourseId($courseId);
        foreach($itemData as $key => $row)
        {
            $itemtypes[$row['id']] = array($row['itemtype'],$row['typeid']);
        }
        /**
         * Assessment data
         */
        $assessmentData = Assessments::getDataByCourseId($courseId);
        foreach($assessmentData as $key => $row)
        {
            $id = array_shift($row);
            $iteminfo['Assessment'][$id] = $row;

        }
        /**
         * Inline text
         */
        $inlineTextData = InlineText::getDataByCourseId($courseId);
        foreach($inlineTextData as $key => $row)
        {
            $id = array_shift($row);
            $iteminfo['InlineText'][$id] = $row;

        }
        /**
         * Link text
         */
        $linkTextData = LinkedText::getDataByCourseId($courseId);
        foreach($linkTextData as $key => $row){
            $id = array_shift($row);
            $iteminfo['LinkedText'][$id] = $row;
        }
        /**
         * Forum
         */
        $formData = Forums::getDataByCourseId($courseId);
        foreach($formData as $key => $row){
            $id = array_shift($row);
            $iteminfo['Forum'][$id] = $row;
        }

        $wikiData = Wiki::getDataByCourseId($courseId);
        foreach($wikiData as $key => $row)
        {
            $id = array_shift($row);
            $iteminfo['Wiki'][$id] = $row;
        }

        $now = time() + $previewShift;
        for ($i = AppConstant::NUMERIC_ZERO; $i < count($items); $i++) {

            if (is_array($items[$i]))
            {
                /**
                 * is a block
                 * */
                $items[$i]['name'] = ($items[$i]['name']);

                if ($items[$i]['startdate'] == AppConstant::NUMERIC_ZERO) {
                    $startdate = _('Always');
                } else {
                    $startdate = AppUtility::formatdate($items[$i]['startdate']);
                }
                if ($items[$i]['enddate'] == 2000000000) {
                    $enddate = _('Always');
                } else {
                    $enddate = AppUtility::formatdate($items[$i]['enddate']);
                }
                $bnum = $i + 1;
                if (strlen($items[$i]['SH'])==1 || $items[$i]['SH'][1]=='O') {

                    $availbeh = _('Expanded');
                } else if ($items[$i]['SH'][1]=='F') {
                    $availbeh = _('as Folder');
                } else {
                    $availbeh = _('Collapsed');
                }
                if ($items[$i]['avail']==2) {
                    $show = sprintf(('Showing %s Always'), $availbeh);
                } else if ($items[$i]['avail']==0) {
                    $show = _('Hidden');
                } else {
                    $show = sprintf(_('Showing %1$s %2$s until %3$s'), $availbeh, $startdate, $enddate);
                }
                if ($items[$i]['avail']==2) {
                    $color = '#0f0';
                } else if ($items[$i]['avail']==0) {
                    $color = '#ccc';
                } else {
                    $color = '#ccc';
                }
                if (in_array($items[$i]['id'],$openblocks))
                {
                    $isopen=true;
                } else {
                    $isopen=false;
                }
                if ($isopen || count($items[$i]['items'])==0) {
                    $liclass = 'blockli';
                    $qviewstyle = '';
                } else {
                    $liclass = 'blockli nCollapse';
                    $qviewstyle = 'style="display:none;"';
                }

                if (!isset($CFG['CPS']['miniicons']['folder'])) {
                    $icon  = '<span class=icon style="background-color:'.$color.'">B</span>';
                } else {
                    $icon = '<img alt="folder" src="'.AppUtility::getHomeURL().'/img/'.$CFG['CPS']['miniicons']['folder'].'" class="mida icon" /> ';
                }

                echo '<li class="'.$liclass.'" id="'."$parent-$bnum".'" obn="'.$items[$i]['id'].'">'.$icon;
                if ($items[$i]['avail']==2 || ($items[$i]['avail']==1 && $items[$i]['startdate']<$now && $items[$i]['enddate']>$now))
                {
                    echo '<b><span id="B'.$parent.'-'.$bnum.'" onclick="editinplace(this)">'.$items[$i]['name']. "</span></b>";
                } else {
                    echo '<i><b><span id="B'.$parent.'-'.$bnum.'" onclick="editinplace(this)">'.$items[$i]['name']. "</span></b></i>";
                }

                if ($showdates)
                {
                    echo " $show";
                }
                if ($showlinks) {
                    echo '<span class="links">';
                    echo "  <a class='modify' href='#'>", _('Modify'),"</a>| <a href='#'>", _('Delete'), "</a>";
                    echo " | <a href=\"#\">", _('Copy'), "</a>";
                    echo " | <a href=\"#\">", _('NewFlag'), "</a>";
                    echo '</span>';
                }
                if (count($items[$i]['items']) > 0)
                {
                    echo '<ul class=qview '.$qviewstyle.'>';
                    $this->quickview($items[$i]['items'],$parent.'-'.$bnum,$showdats,$showlinks);
                    echo '</ul>';
                }
                echo '</li>';
            } else if ($itemtypes[$items[$i]][0] == 'Calendar')
            {
                /**
                 * Calendar
                 */
                if (!isset($CFG['CPS']['miniicons']['calendar'])) {
                    $icon  = '<span class=icon style="background-color:#0f0;">C</span>';
                } else {
                    $icon = '<img alt="calendar" src="'.AppUtility::getHomeURL().'/img/'.$CFG['CPS']['miniicons']['calendar'].'" class="mida icon" /> ';
                }
                echo '<li id="'.$items[$i].'">'.$icon.'Calendar</li>';

            } else if ($itemtypes[$items[$i]][0] == 'Assessment')
            {
                /**
                 * Assessment
                 */
                $typeid = $itemtypes[$items[$i]][1];
                $line['name'] = $iteminfo['Assessment'][$typeid]['name'];
                $line['startdate'] = $iteminfo['Assessment'][$typeid]['startdate'];
                $line['enddate'] = $iteminfo['Assessment'][$typeid]['enddate'];
                $line['reviewdate'] = $iteminfo['Assessment'][$typeid]['reviewdate'];
                $line['avail'] = $iteminfo['Assessment'][$typeid]['avail'];
                if ($line['startdate'] == AppConstant::NUMERIC_ZERO) {
                    $startdate = _('Always');
                } else {
                    $startdate = AppUtility::formatdate($line['startdate']);
                }
                if ($line['enddate'] == AppConstant::ALWAYS_TIME) {
                    $enddate = _('Always');
                } else {
                    $enddate = AppUtility::formatdate($line['enddate']);
                }
                if ($line['reviewdate'] == AppConstant::ALWAYS_TIME) {
                    $reviewdate = _('Always');
                } else {
                    $reviewdate = AppUtility::formatdate($line['reviewdate']);
                }
                if (!isset($CFG['CPS']['miniicons']['assess'])) {
                    $icon  = '<span class=icon style="background-color:'.$color.'">?</span>';
                } else {
                    $icon = '<img alt="assessment" src="'.AppUtility::getHomeURL().'/img/'.$CFG['CPS']['miniicons']['assess'].'" class="mida icon" /> ';
                }
                echo '<li id="'.$items[$i].'">'.$icon;
                if ($line['avail'] == AppConstant::NUMERIC_ONE && $line['startdate']<$now && $line['enddate']>$now) {
                    $show = sprintf(_('Available until %s'), $enddate);
                    echo '<b><span id="A'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
                } else if ($line['avail'] == AppConstant::NUMERIC_ONE && $line['startdate']<$now && $line['reviewdate']>$now) {
                    $show = sprintf(_('Review until %s'), $reviewdate);
                    echo '<b><span id="A'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
                } else {
                    $show = sprintf(_('Available %1$s to %2$s'), $startdate, $enddate);
                    if ($line['reviewdate'] > AppConstant::NUMERIC_ZERO && $line['enddate'] != AppConstant::ALWAYS_TIME) {
                        $show .= sprintf(_(', review until %s'), $reviewdate);
                    }
                    echo '<i><b><span id="A'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
                }
                if ($showdates) {
                    echo $show;
                }
                if ($showlinks) {
                    echo '<span class="links">';?>
                    <a class="question" href="<?php echo AppUtility::getURLFromHome('question', 'question/add-questions?cid='.$courseId.'&aid='.$typeid); ?>"><?php AppUtility::t('Questions'); ?></a> |
                    <a class="modify" href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id='.$typeid . '&cid=' . $courseId . '&block=0') ?>"><?php AppUtility::t('Setting'); ?></a> |
                    <a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a> |
                    <a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a> |
                    <a id="grades" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/item-analysis?cid='.$courseId.'&asid=average&aid='.$typeid); ?>"><?php AppUtility::t('Grades'); ?></a>
                    <?php echo '</span>';
                }
                echo "</li>";

            } else if ($itemtypes[$items[$i]][0] == 'InlineText')
            {
                /**
                 * Inline text
                 */
                $typeid = $itemtypes[$items[$i]][1];
                $line['name'] = $iteminfo['InlineText'][$typeid]['title'];
                $line['text'] = $iteminfo['InlineText'][$typeid]['text'];
                $line['startdate'] = $iteminfo['InlineText'][$typeid]['startdate'];
                $line['enddate'] = $iteminfo['InlineText'][$typeid]['enddate'];
                $line['avail'] = $iteminfo['InlineText'][$typeid]['avail'];
                if ($line['name'] == '##hidden##') {
                    $line['name'] = strip_tags($line['text']);
                }
                if ($line['startdate'] == AppConstant::NUMERIC_ZERO) {
                    $startdate = _('Always');
                } else {
                    $startdate = AppUtility::formatdate($line['startdate']);

                }
                if ($line['enddate'] == AppConstant::ALWAYS_TIME) {
                    $enddate = _('Always');
                } else {
                    $enddate = AppUtility::formatdate($line['enddate']);
                }
                if ($items[$i]['avail']== AppConstant::NUMERIC_TWO) {
                    $color = '#0f0';
                } else if ($items[$i]['avail'] == AppConstant::NUMERIC_ZERO) {
                    $color = '#ccc';
                }
                if (!isset($CFG['CPS']['miniicons']['inline'])) {
                    $icon  = '<span class=icon style="background-color:'.$color.'">!</span>';
                } else {
                    $icon = '<img alt="text" src="'.AppUtility::getHomeURL().'/img/'.$CFG['CPS']['miniicons']['inline'].'" class="mida icon" /> ';
                }
                echo '<li id="'.$items[$i].'">'.$icon;
                if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
                    echo '<b><span id="I'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
                    if ($showdates) {
                        printf(_(' showing until %s'), $enddate);
                    }
                } else {
                    echo '<i><b><span id="I'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
                    if ($showdates) {
                        printf(_(' showing %1$s until %2$s'), $startdate, $enddate);
                    }
                }
                if ($showlinks) {
                    echo '<span class="links">';?>
                    <a class="modify" href="<?php echo AppUtility::getURLFromHome('course', 'course/modify-inline-text?cid=' . $courseId . '&id=' . $typeid) ?>"><?php AppUtility::t('Modify'); ?></a> |
                    <a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                    <a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                    <?php echo '</span>';
                }
                echo '</li>';
            } else if ($itemtypes[$items[$i]][0] == 'LinkedText')
            {
                /**
                 * Linked text
                 */
                $typeid = $itemtypes[$items[$i]][1];
                    $line['name'] = $iteminfo['LinkedText'][$typeid]['title'];
                    $line['startdate'] = $iteminfo['LinkedText'][$typeid]['startdate'];
                    $line['enddate'] = $iteminfo['LinkedText'][$typeid]['enddate'];
                    $line['avail'] = $iteminfo['LinkedText'][$typeid]['avail'];

                if ($line['startdate'] == AppConstant::NUMERIC_ZERO) {
                    $startdate = _('Always');
                } else {
                    $startdate = AppUtility::formatdate($line['startdate']);
                }
                if ($line['enddate'] == AppConstant::ALWAYS_TIME) {
                    $enddate = _('Always');
                } else {
                    $enddate = AppUtility::formatdate($line['enddate']);
                }
                if ($items[$i]['avail'] == AppConstant::NUMERIC_TWO) {
                    $color = '#0f0';
                } else if ($items[$i]['avail'] == AppConstant::NUMERIC_ZERO) {
                    $color = '#ccc';
                }
                if (!isset($CFG['CPS']['miniicons']['linked'])) {
                    $icon  = '<span class=icon style="background-color:'.$color.'">!</span>';
                } else {
                    $icon = '<img alt="link" src="'.AppUtility::getHomeURL().'/img/'.$CFG['CPS']['miniicons']['linked'].'" class="mida icon" /> ';
                }
                echo '<li id="'.$items[$i].'">'.$icon;
                if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
                    echo '<b><span id="L'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
                    if ($showdates) {
                        printf(_(' showing until %s'), $enddate);
                    }
                } else {
                    echo '<i><b><span id="L'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
                    if ($showdates) {
                        printf(_(' showing %1$s until %2$s'), $startdate, $enddate);
                    }
                }
                if ($showlinks) {
                    echo '<span class="links">'; ?>
                    <a class="modify" href="<?php echo AppUtility::getURLFromHome('course', 'course/add-link?cid=' . $courseId . '&id=' . $typeid); ?>"><?php AppUtility::t('Modify'); ?></a> |
                    <a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a> |
                    <a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                   <?php echo '</span>';
                }
                echo '</li>';
            } else if ($itemtypes[$items[$i]][0] == 'Forum')
            {
                /**
                 * Forum
                 */
                $typeid = $itemtypes[$items[$i]][1];
                $line['name'] = $iteminfo['Forum'][$typeid]['name'];
                $line['startdate'] = $iteminfo['Forum'][$typeid]['startdate'];
                $line['enddate'] = $iteminfo['Forum'][$typeid]['enddate'];
                $line['avail'] = $iteminfo['Forum'][$typeid]['avail'];
                if ($line['startdate']==0) {
                    $startdate = _('Always');
                } else {
                    $startdate = AppUtility::formatdate($line['startdate']);
                }
                if ($line['enddate'] == 2000000000) {
                    $enddate = _('Always');
                } else {
                    $enddate = AppUtility::formatdate($line['enddate']);
                }

                if (!isset($CFG['CPS']['miniicons']['forum'])) {
                    $icon  = '<span class=icon style="background-color:'.$color.'">F</span>';
                } else {
                    $icon = '<img alt="forum" src="'.AppUtility::getHomeURL().'/img/'.$CFG['CPS']['miniicons']['forum'].'" class="mida icon" /> ';
                }
                echo '<li id="'.$items[$i].'">'.$icon;
                if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
                    //echo '<b>'.$line['name']. "</b>";
                    echo '<b><span id="F'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
                    if ($showdates) {
                        printf(_(' showing until %s'), $enddate);
                    }
                } else {
                    echo '<i><b><span id="F'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
                    if ($showdates) {
                        printf(_(' showing %1$s until %2$s'), $startdate, $enddate);
                    }
                }
                if ($showlinks) {
                    echo '<span class="links">'; ?>
                    <a class="modify" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-forum?cid=' . $courseId.'&fromForum=1&id='.$typeid); ?>"><?php AppUtility::t('Modify'); ?></a> |
                    <a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a> |
                    <a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>

                  <?php  echo '</span>';
                }
                echo '</li>';
            } else if ($itemtypes[$items[$i]][0] == 'Wiki')
            {
                /**
                 * Wiki
                 */
                $typeid = $itemtypes[$items[$i]][1];
                $line['name'] = $iteminfo['Wiki'][$typeid]['name'];
                $line['startdate'] = $iteminfo['Wiki'][$typeid]['startdate'];
                $line['enddate'] = $iteminfo['Wiki'][$typeid]['enddate'];
                $line['avail'] = $iteminfo['Wiki'][$typeid]['avail'];
                if ($line['startdate'] == 0) {
                    $startdate = _('Always');
                } else {
                    $startdate = AppUtility::formatdate($line['startdate']);
                }
                if ($line['enddate']==2000000000) {
                    $enddate = _('Always');
                } else {
                    $enddate = AppUtility::formatdate($line['enddate']);
                }

                if (!isset($CFG['CPS']['miniicons']['wiki'])) {
                    $icon  = '<span class=icon style="background-color:'.$color.'">W</span>';
                } else {
                    $icon = '<img alt="wiki"  src="'.AppUtility::getHomeURL().'/img/'.$CFG['CPS']['miniicons']['wiki'].'" class="mida icon" /> ';
                }
                echo '<li id="'.$items[$i].'">'.$icon;
                if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
                    echo '<b><span id="W'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
                    if ($showdates) {
                        printf(_(' showing until %s'), $enddate);
                    }
                } else {
                    echo '<i><b><span id="W'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
                    if ($showdates) {
                        printf(_(' showing %1$s until %2$s'), $startdate, $enddate);
                    }
                }
                if ($showlinks) {
                    echo '<span class="links">'; ?>
                    <a class="modify" href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $typeid . '&cid=' . $courseId) ?>"><?php AppUtility::t('Modify'); ?></a>  |
                    <a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a> |
                    <a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                  <?php  echo '</span>';
                }
                echo '</li>';
            }
         }
    }

    public static function  getDataFromSession($data){
        return $_SESSION[$data];
    }

    public static function printPostsGadget($page_newpostlist = null, $page_coursenames = null, $postthreads = null) {

        echo '<div class="block margin-right-fifteen margin-left-fifteen">';
        echo "<span class=\"floatright\"><a href=\"form?action=forumwidgetsettings\">"; ?>
        <img class="small-icon" style="vertical-align:top" src=<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png>

       <?php echo "</a></span><h3>", _('New forum posts'), '</h3></div>';
        echo '<div class="blockitems margin-right-fifteen margin-left-fifteen">';
        if (count($page_newpostlist)==0) {
            echo '<p class="padding-left-fifteen">', _('No new posts'), '</p>';
            echo '</div>';
            return;
        }
        $threadlist = implode(',',$postthreads);
        $threaddata = array();
        $result = ForumPosts::getPostData($postthreads);
        foreach($result as $key => $tline) {
            $threaddata[$tline['id']] = $tline;
        }

        echo '<table class="gb" id="newpostlist"><thead>
        <tr>
            <th class="text-align-center">', _('Thread'), '</th>
            <th class="text-align-center">', _('Started By'), '</th>
            <th class="text-align-center">', _('Course'), '</th>
            <th class="text-align-center">', _('Last Post'), '</th>
        </tr></thead>';
        echo '<tbody>';
        foreach ($page_newpostlist as $line) {
            echo '<tr>';
            $subject = $threaddata[$line['threadid']]['subject'];
            if (trim($subject)=='') {
                $subject = '['._('No Subject').']';
            }
            $n = 0;
            while (strpos($subject,'Re: ')===0) {
                $subject = substr($subject,4);
                $n++;
            }
            if ($n==1) {
                $subject = 'Re: '.$subject;
            } else if ($n>1) {
                $subject = "Re<sup>$n</sup>: ".$subject;
            }
      echo "<td class='word-break-break-all'> "; ?>
            <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/post?courseid='.$line['courseid'].'&forumid='.$line['id'].'&threadid='.$line['threadid'])?>">
           <?php echo $subject;
            echo '</a></td>';
            if ($threaddata[$line['threadid']]['isanon']==1) {
                echo '<td class="word-break-break-all">', _('Anonymous'), '</td>';
            } else {
                echo '<td class="word-break-break-all">'.$threaddata[$line['threadid']]['LastName'].', '.$threaddata[$line['threadid']]['FirstName'].'</td>';
            }
            echo '<td class="word-break-break-all">'.$page_coursenames[$line['courseid']].'</td>';
            echo '<td class="word-break-break-all">'.AppUtility::tzdate("D n/j/y, g:i a",$line['lastposttime']).'</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<script type="javascript">initSortTable("newpostlist",Array("S","S","S","D"),false);</script>';

        echo '</div>';
    }

    public static function printMessagesGadget($page_newmessagelist = null, $page_coursenames = null) {

        echo '<div class="block margin-right-fifteen margin-left-fifteen"><h3>', _('New messages'), '</h3></div>';
        echo '<div class="blockitems margin-right-fifteen margin-left-fifteen">';
        if (count($page_newmessagelist)==0) {
            echo '<p class="padding-left-fifteen">', _('No new messages'), '</p>';
            echo '</div>';
            return;
        }
        echo '<table class="gb" id="newmsglist"><thead><tr><th>', _('Message'), '</th><th>', _('From'), '</th><th>', _('Course'), '</th><th>' ,_('Sent'), '</th></tr></thead>';
        echo '<tbody>';
        foreach ($page_newmessagelist as $line) {
            echo '<tr>';
            if (trim($line['title'])=='') {
                $line['title'] = '['._('No Subject').']';
            }
            $n = 0;
            while (strpos($line['title'],'Re: ')===0) {
                $line['title'] = substr($line['title'],4);
                $n++;
            }
            if ($n==1) {
                $line['title'] = 'Re: '.$line['title'];
            } else if ($n>1) {
                $line['title'] = "Re<sup>$n</sup>: ".$line['title'];
            }
            echo "<td class='word-break-break-all'>" ?>
            <a href="<?php echo AppUtility::getURLFromHome('message','message/view-message?message=0&msgid='.$line['id'].'&cid='.$line['courseid'])?>">
<!--            <a href=\"msgs/viewmsg.php?cid={$line['courseid']}&type=new&msgid={$line['id']}\">";-->
            <?php echo $line['title'];
            echo '</a></td>';
            echo '<td>'.$line['LastName'].', '.$line['FirstName'].'</td>';
            echo '<td class="word-break-break-all">'.$page_coursenames[$line['courseid']].'</td>';
            echo '<td>'.AppUtility::tzdate("D n/j/y, g:i a",$line['senddate']).'</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<script type="javascript">initSortTable("newmsglist",Array("S","S","S","D"),false);</script>';
        echo '</div>';

    }

    public static function makeTopMenu($teacherId = null, $topBar = null, $msgSet = null, $previewShift = null, $courseId = null, $newMsgs = null, $quickView = null, $newPostsCnt = null, $courseNewFlag = null, $useViewButtons = null) {
        if ($useViewButtons && (isset($teacherId) || $previewShift > -1)) {
            echo '<div id="viewbuttoncont">View: ';
            echo "<a href=\"course.php?cid=$courseId&quickview=off&teachview=1\" ";
            if ($previewShift == -1 && $quickView != 'on') {
                echo 'class="buttonactive buttoncurveleft"';
            } else {
                echo 'class="buttoninactive buttoncurveleft"';
            }
            echo '>', _('Instructor'), '</a>';
            echo "<a href=\"course.php?cid=$courseId&quickview=off&stuview=0\" ";
            if ($previewShift>-1 && $quickView != 'on') {
                echo 'class="buttonactive"';
            } else {
                echo 'class="buttoninactive"';
            }
            echo '>', _('Student'), '</a>';
            echo "<a href=\"course.php?cid=$courseId&quickview=on&teachview=1\" ";
            if ($previewShift==-1 && $quickView == 'on') {
                echo 'class="buttonactive buttoncurveright"';
            } else {
                echo 'class="buttoninactive buttoncurveright"';
            }
            echo '>', _('Quick Rearrange'), '</a>';
            echo '</div>';
            //echo '<br class="clear"/>';


        } else {
            $useViewButtons = false;
        }

        if (isset($teacherId) && $quickView == 'on') {
            if ($useViewButtons) {
                echo '<br class="clear"/>';
            }
            echo '<div class="cpmid">';
            if (!$useViewButtons) {
                echo _('Quick View.'), " <a href=\"course.php?cid=$courseId&quickview=off\">", _('Back to regular view'), "</a>. ";
            }
            if (isset($CFG['CPS']['miniicons'])) {
                echo _('Use icons to drag-and-drop order.'),' ',_('Click the icon next to a block to expand or collapse it. Click an item title to edit it in place.'), '  <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/>';

            } else {
                echo _('Use colored boxes to drag-and-drop order.'),' ',_('Click the B next to a block to expand or collapse it. Click an item title to edit it in place.'), '  <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/>';
            }
            echo '<span id="submitnotice" style="color:red;"></span>';
            echo '<div class="clear"></div>';
            echo '</div>';

        }
        if (($courseNewFlag&1)==1) {
            $gbnewflag = ' <span class="red">' . _('New') . '</span>';
        } else {
            $gbnewflag = '';
        }
        if (isset($teacherId) && count($topBar[1])>0 && $topBar[2] == 0) {

            echo '<div class=breadcrumb>';
            if (in_array(0,$topBar[1]) && $msgSet<4) { //messages
                echo "<a href=\"$imasroot/msgs/msglist.php?cid=$courseId\">", _('Messages'), "</a>$newMsgs &nbsp; ";
            }
            if (in_array(6,$topBar[1])) { //Calendar
                echo "<a href=\"$imasroot/forums/forums.php?cid=$courseId\">", _('Forums'), "</a>$newPostsCnt &nbsp; ";
            }
            if (in_array(1,$topBar[1])) {
                //Stu view
                echo "<a href=\"course.php?cid=$courseId&stuview=0\">", _('Student View'), "</a> &nbsp; ";
            }
            if (in_array(3,$topBar[1])) { //List stu
                echo "<a href=\"listusers.php?cid=$courseId\">", _('Roster'), "</a> &nbsp; \n";
            }
            if (in_array(2,$topBar[1])) { //Gradebook
                echo "<a href=\"gradebook.php?cid=$courseId\">", _('Gradebook'), "</a>$gbnewflag &nbsp; ";
            }
            if (in_array(7,$topBar[1])) { //List stu
                echo "<a href=\"managestugrps.php?cid=$courseId\">", _('Groups'), "</a> &nbsp; \n";
            }
            if (in_array(4,$topBar[1])) { //Calendar
                echo "<a href=\"showcalendar.php?cid=$courseId\">", _('Calendar'), "</a> &nbsp; \n";
            }
            if (in_array(5,$topBar[1])) { //Calendar

                echo "<a href=\"course.php?cid=$courseId&quickview=on\">", _('Quick View'), "</a> &nbsp; \n";
            }

            if (in_array(9,$topBar[1])) { //Log out
                echo "<a href=\"../actions.php?action=logout\">", _('Log Out'), "</a>";
            }
            echo '<div class=clear></div></div>';
        } else if (!isset($teacherId) && ((count($topBar[0])>0 && $topBar[2]==0) || ($previewShift>-1 && !$useViewButtons))) {
            echo '<div class=breadcrumb>';
            if ($topBar[2]==0) {
                if (in_array(0,$topBar[0]) && $msgSet<4) { //messages
                    echo "<a href=\"$imasroot/msgs/msglist.php?cid=$courseId\">", _('Messages'), "</a>$newMsgs &nbsp; ";
                }
                if (in_array(3,$topBar[0])) { //forums
                    echo "<a href=\"$imasroot/forums/forums.php?cid=$courseId\">", _('Forums'), "</a>$newPostsCnt &nbsp; ";
                }
                if (in_array(1,$topBar[0])) { //Gradebook
                    echo "<a href=\"gradebook.php?cid=$courseId\">", _('Show Gradebook'), "</a>$gbnewflag &nbsp; ";
                }
                if (in_array(2,$topBar[0])) { //Calendar
                    echo "<a href=\"showcalendar.php?cid=$courseId\">", _('Calendar'), "</a> &nbsp; \n";
                }
                if (in_array(9,$topBar[0])) { //Log out
                    echo "<a href=\"../actions.php?action=logout\">", _('Log Out'), "</a>";
                }
                if ($previewShift>-1 && count($topBar[0])>0) { echo '<br />';}
            }
            if ($previewShift>-1 && !$useViewButtons) {
                echo _('Showing student view. Show view:'), ' <select id="pshift" onchange="changeshift()">';
                echo '<option value="0" ';
                if ($previewShift==0) {echo "selected=1";}
                echo '>', _('Now'), '</option>';
                echo '<option value="3600" ';
                if ($previewShift==3600) {echo "selected=1";}
                echo '>', _('1 hour from now'), '</option>';
                echo '<option value="14400" ';
                if ($previewShift==14400) {echo "selected=1";}
                echo '>', _('4 hours from now'), '</option>';
                echo '<option value="86400" ';
                if ($previewShift==86400) {echo "selected=1";}
                echo '>', _('1 day from now'), '</option>';
                echo '<option value="604800" ';
                if ($previewShift==604800) {echo "selected=1";}
                echo '>', _('1 week from now'), '</option>';
                echo '</select>';
                echo " <a href=\"course?cid=$courseId&teachview=1\">", _('Back to instructor view'), "</a>";
            }
            echo '<div class=clear></div></div>';
        }
    }

    public static function printCourses($data,$title,$type=null, $showNewMsgNote = null, $showNewPostNote = null, $stuHasHiddenCourses = null, $myRights = null, $newMsgCnt = null, $newPostCnt = null) {
        global $showNewMsgNote, $showNewPostNote, $stuHasHiddenCourses, $isStudent;
        $isCourseHidden = false;
        if (count($data) == 0 && $type == 'tutor') {
            return;
        }
        global $myRights,$newMsgCnt,$newPostCnt,$user;
        $userId = $user['id'];
        $students = Student::getByUserId($userId);
        echo '<div class="block margin-left-fifteen margin-right-fifteen"><h3>'.$title.'</h3></div>';
        echo '<div class="blockitems margin-left-fifteen margin-right-fifteen"><ul class="nomark courselist">';
        for ($i=0; $i<count($data); $i++) {
            $courseStudent = Course::getByCourseAndUser($data[$i]['id']);
            $isStudent = false;
            $student = Student::getByCourseId($data[$i]['id'], $userId);
            if ($student) {
                $isStudent = true;
            }
            $lockId = $courseStudent['lockaid'];
            $locked = Student::getStudentData($userId, $data[$i]['id']);
            echo '<li>';
            if ($type=='take') {
                 ?>
                <span class="delx" onclick="return hidefromcourselist(<?php echo $data[$i]['id'] ?>,this);" title="Hide from course list">x</span>
           <?php }
            if ($isStudent && ($lockId > 0)) {
                ?>
                <a class="word-wrap-break-word" href="#" onclick="locked()">
            <?php echo $data[$i]['name'].'</a>';
            } elseif($locked['locked'] > 0){ ?>
                <a class="word-wrap-break-word" href="#" onclick="studLocked()">
                <?php echo $data[$i]['name'].'</a>';
            }
                ?>
            <a class="word-wrap-break-word" href="<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$data[$i]['id'].'&folder=0')?>">
            <?php echo $data[$i]['name'].'</a>';
            if (isset($data[$i]['available']) && (($data[$i]['available']&1) == 1)) {
                echo ' <span style="color:green;">', _('Hidden'), '</span>';
            }
            if (isset($data[$i]['lockaid']) && $data[$i]['lockaid'] > 0) {
                echo ' <span style="color:green;">', _('Lockdown'), '</span>';
            }
            echo '</li>';
        }
        foreach($students as $key=>$studentData){
            if($studentData->hidefromcourselist){
                $isCourseHidden = true;
            }
        }
        if ($type == 'teach' && $myRights > 39 && count($data)==0) {
            echo '<li>', _('To add a course, head to the Admin Page'), '</li>';
        }
        echo '</ul>';
        if ($type == 'take') { ?>
            <div class="center">
            <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('student', 'student/student-enroll-course') ?>">Enroll in a New Class</a><br><br/>
            <a  id="unhidelink" class="course-taking small" href="<?php echo AppUtility::getURLFromHome('site', 'unhide-from-course-list') ?>">Unhide hidden courses</a>

            <?php
            if($isCourseHidden){
                ?>
                <input type="hidden" class="hidden-course" value="<?php echo $isCourseHidden ?>">
            <?php
            }
            ?>

        <?php echo '</div>';
        } else if ($type=='teach' && $myRights > 39) { ?>
            <div class="center">
                <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/index') ?>">Admin
                    Page</a><br/><br/>
            </div>
      <?php  }
        echo '</div>';
    }

    public static function prepd($v) {
    $v = str_replace('\\"','"',$v);
    return htmlentities($v, ENT_COMPAT | ENT_HTML401,"UTF-8", false );
    }

    public static function truncate($str, $width) {
        return strtok(wordwrap($str, $width, "...\n",true), "\n");
    }

    public function printchildren($base,$restricttoowner=false) {
        $curdir = rtrim(dirname(__FILE__), '/\\');
        global $children,$date,$subject,$message,$poster,$email,$forumid,$threadid,$isTeacher,$courseId,$userid,$ownerid,$points;
        global $feedback,$posttype,$lastview,$bcnt,$icnt,$myrights,$allowreply,$allowmod,$allowdel,$allowlikes,$view,$page,$allowmsg;
        global $haspoints,$imasroot,$postby,$replyby,$files,$CFG,$rubric,$pointsposs,$hasuserimg,$urlmode,$likes,$mylikes,$section;
        global $canviewall, $caneditscore, $canviewscore;
        if (!isset($CFG['CPS']['itemicons'])) {
            $itemicons = array('web'=>'web.png', 'doc'=>'doc.png', 'wiki'=>'wiki.png',
                'html'=>'html.png', 'forum'=>'forum.png', 'pdf'=>'pdf.png',
                'ppt'=>'ppt.png', 'zip'=>'zip.png', 'png'=>'image.png', 'xls'=>'xls.png',
                'gif'=>'image.png', 'jpg'=>'image.png', 'bmp'=>'image.png',
                'mp3'=>'sound.png', 'wav'=>'sound.png', 'wma'=>'sound.png',
                'swf'=>'video.png', 'avi'=>'video.png', 'mpg'=>'video.png',
                'nb'=>'mathnb.png', 'mws'=>'maple.png', 'mw'=>'maple.png');
        } else {
            $itemicons = $CFG['CPS']['itemicons'];
        }
        ?>

        <?php
        foreach($children[$base] as $child) {
            if ($restricttoowner && $ownerid[$child] != $userid) {
                continue;
            }
            echo "<div class=block> ";
            echo '<span class="leftbtns">';
            if (isset($children[$child])) {
                if ($view==1) {
                    $lbl = '+';
                    $img = "expand";
                } else {
                    $lbl = '-';
                    $img = "collapse";
                } ?>
                <img  class="pointer" id="butb<?php echo $bcnt?>" style="float: left" src="<?php echo AppUtility::getHomeURL()?>img/<?php echo $img?>.gif" onclick="toggleshow(<?php echo $bcnt ?>)">

          <?php  }
            if ($hasuserimg[$child]==1) {
                if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                    echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$ownerid[$child]}.jpg\"  onclick=\"togglepic(this)\" />";
                } else {
                    $uploads = AppConstant::UPLOAD_DIRECTORY;
                    $imageUrl = $ownerid[$child].".jpg";?>
<!--                    <img style="float:left;" class="circular-profile-image Align-link-post padding-five" src="--><?php //echo AppUtility::getHomeURL().$uploads.$ownerid[$child].".jpg"?><!--" onclick="onclick=changeProfileImage(this,--><?php //echo $userid ?><!--);">-->
                    <img class="circular-profile-image Align-link-post padding-five" id="img" src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl?>" onclick=changeProfileImage(this,<?php echo $userid?>); />
               <?php }
            } else {
                ?>
                <img class="circular-profile-image" id="img"
                     src="<?php echo AppUtility::getAssetURL() ?>Uploads/dummy_profile.jpg"/>
            <?php }
            echo '</span>';
            echo "<span class=right>";

            if ($view==2) { ?>
                <input type="button" id="buti<?php echo $icnt;?>" value="Show" onclick="toggleitem(<?php echo $icnt;?>)">
           <?php } else { ?>
                <input type="button" id="buti<?php echo $icnt;?>" value="Hide" onclick="toggleitem(<?php echo $icnt;?>)">
            <?php }

            if ($isTeacher) {
                echo "<a href=\"move-thread?forumid=$forumid&courseid=$courseId&threadid=$threadid\">Move</a> \n";
            }
            if ($isTeacher || ($ownerid[$child]==$userid && $allowmod)) {
                if (($base==0 && time()<$postby) || ($base>0 && time()<$replyby) || $isTeacher) {
                    echo "<a href=\"modify-post?courseId=$courseId&forumId=$forumid&threadId=$threadid\">Modify</a> \n";
                }
            }
            if ($isTeacher || ($allowdel && $ownerid[$child]==$userid && !isset($children[$child]))) { ?>
                <a href="#" name="remove" data-parent="<?php echo $child ?>" data-var="<?php echo $threadid ?>" class="mark-remove"><?php AppUtility::t('Remove')?></a>
            <?php }
            if ($posttype[$child]!=2 && $myrights > 5 && $allowreply) {
                echo "<a href=\"reply-post?courseid=$courseId&id=$child&threadId=$threadid&forumid=$forumid\">Reply</a>";
            }

            echo "</span>\n";
            echo '<span style="float:left">';
            echo "<b>{$subject[$child]}</b><br/>Posted by: ";

            if (($isTeacher || $allowmsg) && $ownerid[$child]!=0) {?>
                <a href="<?php echo AppUtility::getURLFromHome('message', 'message/send-message?cid='.$courseId.'&userid='.$ownerid[$child].'&new='.$ownerid[$child])?>"
                <?php if ($section[$child]!='') {
                    echo 'title="Section: '.$section[$child].'"';
                }
                echo ">";
            }
            echo $poster[$child];
            if (($isTeacher || $allowmsg) && $ownerid[$child]!=0) {
                echo "</a>";
            }
            if ($isTeacher && $ownerid[$child]!=0 && $ownerid[$child]!=$userid) {
                echo " <a class=\"small\" href=\"#\">[GB]</a>";
                if ($base==0 && preg_match('/Question\s+about\s+#(\d+)\s+in\s+(.*)\s*$/',$subject[$child],$matches)) {
                    $query = "SELECT ias.id FROM imas_assessment_sessions AS ias JOIN imas_assessments AS ia ON ia.id=ias.assessmentid ";
                    $aname = addslashes($matches[2]);
                    $query .= "WHERE ia.name='$aname' AND ias.userid=".intval($ownerid[$child]);
                    $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
                    if (mysql_num_rows($result)>0) {
                        $r = mysql_fetch_row($result);
                        echo " <a class=\"small\" href=\"$imasroot/course/gb-viewasid.php?cid=$courseId&uid={$ownerid[$child]}&asid={$r[0]}\" target=\"_popoutgradebook\">[assignment]</a>";
                    }
                }
            }
            echo ', ';
            echo AppUtility::tzdate("D, M j, Y, g:i a",$date[$child]);
            if ($date[$child]>$lastview) {
                echo " <span style=\"color:red;\">New</span>\n";
            }
            echo '</span>';

            if ($allowlikes) {
                $icon = (in_array($child,$mylikes))?'liked':'likedgray';
                $likemsg = 'Liked by ';
                $likecnt = 0;
                $likeclass = '';
                if ($likes[$child][0]>0) {
                    $likeclass = ' liked';
                    $likemsg .= $likes[$child][0].' ' . ($likes[$child][0]==1?'student':'students');
                    $likecnt += $likes[$child][0];
                }
                if ($likes[$child][1]>0 || $likes[$child][2]>0) {
                    $likeclass = ' likedt';
                    $n = $likes[$child][1] + $likes[$child][2];
                    if ($likes[$child][0]>0) { $likemsg .= ' and ';}
                    $likemsg .= $n.' ';
                    if ($likes[$child][2]>0) {
                        $likemsg .= ($n==1?'teacher':'teachers');
                        if ($likes[$child][1]>0) {
                            $likemsg .= '/tutors/TAs';
                        }
                    } else if ($likes[$child][1]>0) {
                        $likemsg .= ($n==1?'tutor/TA':'tutors/TAs');
                    }
                    $likecnt += $n;
                }
                if ($likemsg=='Liked by ') {
                    $likemsg = '';
                } else {
                    $likemsg .= '.';
                }
                if ($icon=='liked') {
                    $likemsg = 'You like this. '.$likemsg;
                } else {
                    $likemsg = 'Click to like this post. '.$likemsg;;
                }

                echo '<div class="likewrap">'; ?>
<!--                echo "<img id=\"likeicon$child\" class=\"likeicon$likeclass\" src=\"$imasroot/img/$icon.png\" title=\"$likemsg\" onclick=\"savelike(this)\">";-->
                <img id="likeicon<?php echo $child?>" class="likeicon<?php echo $likeclass?>" src="<?php echo AppUtility::getHomeURL()?>img/<?php echo $icon?>.png" title="<?php echo $likemsg?>" onclick="savelike(this)">
                <?php echo " <span class=\"pointer\" id=\"likecnt$child\" onclick=\"GB_show('"._('Post Likes')."','list-likes?cid=$courseId&amp;post=$child',500,500);\">".($likecnt>0?$likecnt:'').' </span> ';
                echo '</div>';
            }
            echo '<div class="clear"></div>';
            echo "</div>\n";
            if ($view==2) { ?>
                <div class="hidden" id="item<?php echo $icnt;?>">
           <?php } else { ?>
                <div class="blockitems" id="item<?php echo $icnt;?>" style="clear:all">
           <?php }
            if(isset($files[$child]) && $files[$child]!='') {
                $fl = explode('@@',$files[$child]);
                if (count($fl)>2) {
                    echo '<p><b>Files:</b> ';//<ul class="nomark">';
                } else {
                    echo '<p><b>File:</b> ';
                }
                for ($i=0;$i<count($fl)/2;$i++) {
                    if(!empty($fl[2*$i+1]))
                    {
                        echo '<a href="'.filehandler::getuserfileurl($fl[2*$i+1]).'" changeProfileImagetarget="_blank">';
                    }
                    $extension = ltrim(strtolower(strrchr($fl[2*$i+1],".")),'.');
                    if (isset($itemicons[$extension])) {
                        echo "<img alt=\"$extension\" src=\"$imasroot/img/{$itemicons[$extension]}\" class=\"mida\"/> ";
                    } else {
                        echo "<img alt=\"doc\" src=\"$imasroot/img/doc.png\" class=\"mida\"/> ";
                    }
                    echo $fl[2*$i].'</a> ';
                }
                echo '</p>';
            }
            echo filter($message[$child]);
            if ($haspoints) {
                if ($caneditscore && $ownerid[$child]!=$userid) {
                    echo '<hr/>';
                    echo "Score: <input type=text size=2 name=\"score[$child]\" id=\"scorebox$child\" value=\"";
                    if ($points[$child]!==null) {
                        echo $points[$child];
                    }
                    echo "\"/> ";
                    if ($rubric != 0) {
                        echo printrubriclink($rubric,$pointsposs,"scorebox$child", "feedback$child");
                    }
                    echo " Private Feedback: <textarea cols=\"50\" rows=\"2\" name=\"feedback[$child]\" id=\"feedback$child\">";
                    if ($feedback[$child]!==null) {
                        echo $feedback[$child];
                    }
                    echo "</textarea>";
                } else if (($ownerid[$child]==$userid || $canviewscore) && $points[$child]!==null) {
                    echo '<div class="signup">Score: ';
                    echo "<span class=red>{$points[$child]} points</span><br/> ";
                    if ($feedback[$child]!==null && $feedback[$child]!='') {
                        echo 'Private Feedback: ';
                        echo $feedback[$child];
                    }
                    echo '</div>';
                }
            }
            echo "<div class=\"clear\"></div></div>\n";

            $icnt++;
            if (isset($children[$child])) { //if has children
                echo "<div class=";
                if ($view==0 || $view==2) {
                    echo '"forumgrp"';
                } else if ($view==1) {
                    echo '"hidden"';
                }
                echo " id=\"block$bcnt\">\n";
                $bcnt++;
                $this->printchildren($child, ($posttype[$child]==3 && !$isTeacher));
                echo "</div>\n";
            }
            ?>

        <?php }

        ?>
        <input type="hidden" class="bcnt-value" value="<?php echo $bcnt;?>">
        <input type="hidden" class="icnt-value" value="<?php echo $icnt;?>">
    <?php  }

    public function printrubriclink($rubricid, $points, $scorebox, $feedbackbox, $qn = 'null', $width = 600)
    {
            $out = "<a onclick=\"imasrubric_show($rubricid,$points,'$scorebox','$feedbackbox','$qn',$width); return false;\" href=\"#\">";
        $out .= "<img border=0 src='../../img/assess.png' alt=\"rubric\"></a>";
        return $out;
    }
}