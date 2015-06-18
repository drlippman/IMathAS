<?php

namespace app\components;


use app\models\Exceptions;
use app\models\Questions;
use Yii;
use yii\base\Component;

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
        for ($i = 0; $i < 10; $i++) {
            $pass .= substr($chars, rand(0, 61), 1);
        }
        return $pass;
    }

    public static function getStringVal($str)
    {
        return isset($str) ? $str : null;
    }

    public static function getIntVal($str)
    {
        return isset($str) ? $str : 0;
    }

    public static function getURLFromHome($controllerName, $shortURL)
    {
        return self::getHomeURL() . $controllerName . "/" . $shortURL;
    }

    public static function getHomeURL()
    {
        return Yii::$app->homeUrl;
    }

    public static function getAssetURL(){
        return self::getHomeURL();
    }

    public static function getTimeStampFromDate($dateStr)
    {
        $a = strptime($dateStr, '%m-%d-%Y');
        $timestamp = mktime(0, 0, 0, $a['tm_mon'] + 1, $a['tm_mday'], $a['tm_year'] + 1900);
        return $timestamp;
    }

    public static function checkEditOrOk()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false) {
            preg_match('/OS (\d+)_(\d+)/', $ua, $match);
            if ($match[1] >= 5) {
                return 1;
            } else {
                return 0;
            }
        } else if (strpos($ua, 'Android') !== false) {
            preg_match('/Android\s+(\d+)((?:\.\d+)+)\b/', $ua, $match);
            if ($match[1] >= 4) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 1;
        }
    }

    public static function urlMode()
    {
        $urlmode = '';
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) {
            $urlmode = 'https://';
        } else {
            $urlmode = 'http://';
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
                    if ($singleParam != '0')
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

    public static function getFormattedDate($dateStr, $format = 'Y-m-d'){
        return date($format, $dateStr);
    }

    public static function getFullName($first, $last){
        return trim(ucfirst($first).' '.ucfirst($last));
    }

    public static function passwordHash($password)
    {
        require_once("Password.php");
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function makeToolset($params)
    {
        if (is_array($params)) {
            if (count($params) == 3)
                return 0;
            elseif (count($params) == 1) {
                if ($params[0] == 1)
                    return 6;
                elseif ($params[0] == 2)
                    return 5;
                else
                    return 3;
            } elseif (count($params) == 2) {
                if (($params[0] == 1) && $params[1] == 2)
                    return 4;
                elseif (($params[0] == 1) && $params[1] == 4)
                    return 2;
                else
                    return 1;
            }
        } else {
            return $params;
        }
    }


    public static function makeAvailable($availables)
    {
        if (is_array($availables)) {
            if (count($availables) == 2)
                return 0;
            else {
                if ($availables[0] == 1)
                    return 1;
                else
                    return 2;
            }
        } else
            return 3;
    }

    public static function createIsTemplate($isTemplates)
    {
        $isTemplate = 0;
        if (is_array($isTemplates)) {

            foreach ($isTemplates as $item) {
                if (self::myRight() == AppConstant::ADMIN_RIGHT) {
                    if ($item == 1) {
                        $isTemplate += 1;
                    }
                    if ($item == 4) {
                        $isTemplate += 4;
                    }
                }
                if (self::myRight() >= AppConstant::GROUP_ADMIN_RIGHT) {
                    if ($item == 2) {
                        $isTemplate += 2;
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
        $quickPickTopBar = isset($quickPickBar) ? $quickPickBar : 0;
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
        return base64_encode(microtime() . rand(0, 9999));
    }


    public static function getRight($right)
    {
        $returnRight = "";
        switch ($right) {
            case 100:
                $returnRight = 'Admin';
                break;

            case 75:
                $returnRight = 'Group Admin';
                break;

            case 60:
                $returnRight = 'Diagnostic Creator';
                break;

            case 40:
                $returnRight = 'Limited Course Creator';
                break;

            case 20:
                $returnRight = 'Instructor';
                break;

            case 10:
                $returnRight = 'Student';
                break;

            case 5:
                $returnRight = 'Guest';
                break;
        }
        return $returnRight;
    }

    public static function calculateTimeDefference($startTime, $endTime)
    {
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/', $endTime, $tmatches);
        if (count($tmatches) == 0) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/', $endTime, $tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = 0;
        }
        $tmatches[1] = $tmatches[1] % 12;
        if ($tmatches[3] == "pm") {
            $tmatches[1] += 12;
        }
        $deftime = $tmatches[1] * 60 + $tmatches[2];

        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/', $startTime, $tmatches);
        if (count($tmatches) == 0) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/', $startTime, $tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = 0;
        }
        $tmatches[1] = $tmatches[1] % 12;
        if ($tmatches[3] == "pm") {
            $tmatches[1] += 12;
        }
        $deftime += 10000 * ($tmatches[1] * 60 + $tmatches[2]);

        return $deftime;
    }


    public static function tzdate($string, $time)
    {
        $tzoffset = Yii::$app->session->get('tzoffset');
        $tzname = Yii::$app->session->get('tzname');

        if ($tzname != '') {
            return date($string, $time);
        } else {
            $serveroffset = date('Z') + $tzoffset * 60;
            return date($string, $time - $serveroffset);
        }
    }

    public static function formatDate($date)
    {
        return AppUtility::tzdate("D n/j/20y, g:i a", $date);
    }

    public static function calculateTimeToDisplay($deftime)
    {
        $defetime = $deftime % 10000;
        $hr = floor($defetime / 60) % 12;
        $min = $defetime % 60;
        $am = ($defetime < 12 * 60) ? 'am' : 'pm';
        $deftimedisp = (($hr == 0) ? 12 : $hr) . ':' . (($min < 10) ? '0' : '') . $min . ' ' . $am;
        if ($deftime > 10000) {
            $defstime = floor($deftime / 10000);
            $hr = floor($defstime / 60) % 12;
            $min = $defstime % 60;
            $am = ($defstime < 12 * 60) ? 'am' : 'pm';
            $defstimedisp = (($hr == 0) ? 12 : $hr) . ':' . (($min < 10) ? '0' : '') . $min . ' ' . $am;
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

        if (($course->available & 1) == 0) {
            array_push($available, 1);
        }
        if (($course->available & 2) == 0) {
            array_push($available, 2);
        }

        if (($course->toolset & 1) == 0) {
            array_push($toolset, 1);
        }
        if (($course->toolset & 2) == 0) {
            array_push($toolset, 2);
        }
        if (($course->toolset & 4) == 0) {
            array_push($toolset, 4);
        }

        if (($course->istemplate & 2) == 2) {
            array_push($isTemplate, 2);
        }
        if (($course->istemplate & 1) == 1) {
            array_push($isTemplate, 1);
        }
        if (($course->istemplate & 4) == 4) {
            array_push($isTemplate, 4);

        }
        return $ckeckList = array('available' => $available, 'toolset' => $toolset, 'isTemplate' => $isTemplate);
    }

//        Displays date and time
    public static function parsedatetime($date, $time)
    {
        $tzoffset = self::getTimezoneOffset();
        $tzname = self::getTimezoneName();
        preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/', $date, $dmatches);
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/', $time, $tmatches);
        if (count($tmatches) == 0) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/', $time, $tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = 0;
        }
        $tmatches[1] = $tmatches[1] % 12;
        if ($tmatches[3] == "pm") {
            $tmatches[1] += 12;
        }

        if ($tzname == '') {
            $serveroffset = date('Z') / 60 + $tzoffset;
            $tmatches[2] += $serveroffset;
        }
        return mktime($tmatches[1], $tmatches[2], 0, $dmatches[1], $dmatches[2], $dmatches[3]);
    }

//    Displays only time
    public static function parsetime($time)
    {
        $tzoffset = self::getTimezoneOffset();
        $tzname = self::getTimezoneName();
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/', $time, $tmatches);
        if (count($tmatches) == 0) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/', $time, $tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = 0;
        }
        $tmatches[1] = $tmatches[1] % 12;
        if ($tmatches[3] == "pm") {
            $tmatches[1] += 12;
        }

        if ($tzname == '') {
            $serveroffset = date('Z') / 60 + $tzoffset;
            $tmatches[2] += $serveroffset;
        }
        return mktime($tmatches[1], $tmatches[2], 0);
    }

    public static function myRight()
    {
        return Yii::$app->user->identity->rights;
    }

    /*Show Calender*/
    public static function showCalendar($refpage)
    {

        global $imasroot, $cid, $userid, $teacherid, $previewshift, $latepasses, $urlmode, $latepasshrs, $myrights, $tzoffset, $tzname, $havecalcedviewedassess, $viewedassess;

        $now = time();
        if ($previewshift != -1) {
            $now = $now + $previewshift;
        }
        if (!isset($_COOKIE['calstart' . $cid]) || $_COOKIE['calstart' . $cid] == 0) {
            $today = $now;
        } else {
            $today = $_COOKIE['calstart' . $cid];
        }

        if (isset($_GET['calpageshift'])) {
            $pageshift = $_GET['calpageshift'];
        } else {
            $pageshift = 0;
        }
        if (!isset($_COOKIE['callength' . $cid])) {
            $callength = 4;
        } else {
            $callength = $_COOKIE['callength' . $cid];
        }

        $today = $today + $pageshift * 7 * $callength * 24 * 60 * 60;

        $dayofweek = tzdate('w', $today);
        $curmonum = tzdate('n', $today);
        $dayofmo = tzdate('j', $today);
        $curyr = tzdate('Y', $today);
        if ($tzname == '') {
            $serveroffset = date('Z') + $tzoffset * 60;
        } else {
            $serveroffset = 0; //don't need this if user's timezone has been set
        }
        $midtoday = mktime(12, 0, 0, $curmonum, $dayofmo, $curyr) + $serveroffset;


        $hdrs = array();
        $ids = array();

        $lastmo = '';
        for ($i = 0; $i < 7 * $callength; $i++) {
            $row = floor($i / 7);
            $col = $i % 7;

            list($thismo, $thisday, $thismonum, $datestr) = explode('|', tzdate('M|j|n|l F j, Y', $midtoday - ($dayofweek - $i) * 24 * 60 * 60));
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
                    $questions[] = $sub[array_rand($sub, 1)];
                } else {
                    $grpqs = array();
                    $grpparts = explode('|', $sub[0]);
                    array_shift($sub);
                    if ($grpparts[1] == 1) { // With replacement
                        for ($i = 0; $i < $grpparts[0]; $i++) {
                            $questions[] = $sub[array_rand($sub, 1)];
                        }
                    } else if ($grpparts[1] == 0) { //Without replacement
                        shuffle($sub);
                        for ($i = 0; $i < min($grpparts[0], count($sub)); $i++) {
                            $questions[] = $sub[$i];
                        }
                        //$grpqs = array_slice($sub,0,min($grpparts[0],count($sub)));
                        if ($grpparts[0] > count($sub)) { //fix stupid inputs
                            for ($i = count($sub); $i < $grpparts[0]; $i++) {
                                $questions[] = $sub[array_rand($sub, 1)];
                            }
                        }
                    }
                }
            } else {
                $questions[] = $q;
            }
        }
        if ($shuffle & 1) {
            shuffle($questions);
        }

        if ($shuffle & 2) { //all questions same random seed
            if ($shuffle & 4) { //all students same seed
                $seeds = array_fill(0, count($questions), $aid);
                $reviewseeds = array_fill(0, count($questions), $aid + 100);
            } else {
                $seeds = array_fill(0, count($questions), rand(1, 9999));
                $reviewseeds = array_fill(0, count($questions), rand(1, 9999));
            }
        } else {
            if ($shuffle & 4) { //all students same seed
                for ($i = 0; $i < count($questions); $i++) {
                    $seeds[] = $aid + $i;
                    $reviewseeds[] = $aid + $i + 100;
                }
            } else {
                for ($i = 0; $i < count($questions); $i++) {
                    $seeds[] = rand(1, 9999);
                    $reviewseeds[] = rand(1, 9999);
                }
            }
        }


        $scores = array_fill(0, count($questions), -1);
        $attempts = array_fill(0, count($questions), 0);
        $lastanswers = array_fill(0, count($questions), '');
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
        $n = 0;
        while (strpos($title, 'Re: ') === 0) {
            $title = substr($title, 4);
            $n++;
        }
        if ($n == 1) {
            $title = 'Re: ' . $title;
        } else if ($n > 1) {
            $title = "Re<sup>$n</sup>: " . $title;
        }
        return array('title' => $title, 'level' => $n);
    }

    public static function addslashes_deep($value) {
        return (is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value));
    }

    public static function showAssessment($user, $params, $assessmentId, $courseId, $assessment, $assessmentSession, $teacher, $next)
    {
        global $allowedmacros, $mathfuncs;
        $allowedmacros = array();
        $mathfuncs = array("sin","cos","tan","sinh","cosh","tanh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
        $allowedmacros = $mathfuncs;
        $introdividers = array();

        include("displayq2.php");
        include("testutil.php");
        include("asidutil.php");
        $isTeacher = false;
        if($teacher)
        {
            $isTeacher = true;
        }

        global $questions, $disallowedvar, $showhints, $testid, $qi, $responseString, $scores, $superdone, $bestquestions, $seeds, $noraw, $rawscores, $attempts, $lastanswers, $timesontask, $lti_sourcedid, $reattempting, $bestseeds, $bestrawscores, $bestscores, $firstrawscores, $bestattempts, $bestlastanswers, $starttime, $testsettings, $inexception, $isreview, $exceptionduedate;
        $superdone = false;
        $isreview = false;
        $testid = $assessmentSession->id;
        if($assessmentSession)
        {
            if (strpos($assessmentSession->questions,';') === false) {
                $questions = explode(",", $assessmentSession->questions);
                $bestquestions = $questions;
            } else {
                list($questions, $bestquestions) = explode(";", $assessmentSession->questions);
                $questions = explode(",", $questions);
                $bestquestions = explode(",", $bestquestions);
            }

            $seeds = explode(",", $assessmentSession->seeds);
            if (strpos($assessmentSession->scores,';')===false) {
                $scores = explode(",", $assessmentSession->scores);
                $noraw = true;
                $rawscores = $scores;
            } else {
                $sp = explode(';',$assessmentSession->scores);
                $scores = explode(',', $sp[0]);
                $rawscores = explode(',', $sp[1]);
                $noraw = false;
            }

            $attempts = explode(",", $assessmentSession->attempts);
            $lastanswers = explode("~",$assessmentSession->lastanswers);
            if ($assessmentSession->timeontask == '') {
                $timesontask = array_fill(0,count($questions), '');
            } else {
                $timesontask = explode(',', $assessmentSession->timeontask);
            }
            $lti_sourcedid = $assessmentSession->lti_sourcedid;

            if (trim($assessmentSession->reattempting) == '') {
                $reattempting = array();
            } else {
                $reattempting = explode(",", $assessmentSession->reattempting);
            }

            $bestseeds = explode(",", $assessmentSession->bestseeds);
            if ($noraw) {
                $bestscores = explode(',', $assessmentSession->bestscores);
                $bestrawscores = $bestscores;
                $firstrawscores = $bestscores;
            } else {
                $sp = explode(';', $assessmentSession->bestscores);
                $bestscores = explode(',', $sp[0]);
                $bestrawscores = explode(',', $sp[1]);
                $firstrawscores = explode(',', $sp[2]);
            }
            $bestattempts = explode(",", $assessmentSession->bestattempts);
            $bestlastanswers = explode("~", $assessmentSession->bestlastanswers);
            $starttime = $assessmentSession->starttime;


            if($starttime == 0)
            {
                $assessmentSession->starttime = time();
                $assessmentSession->save();
            }

            if($assessment)
            {
                $testsettings = $assessment->attributes;

                if ($testsettings['displaymethod'] == 'VideoCue' && $testsettings['viddata']=='') {
                    $testsettings['displaymethod'] = 'Embed';
                }
                if (preg_match('/ImportFrom:\s*([a-zA-Z]+)(\d+)/',$testsettings['intro'],$matches) == 1) {
                    if (strtolower($matches[1]) == 'link') {
                        $linkedText = Links::getById(intval($matches[2]));
                        $vals = $linkedText->text;
                        $testsettings['intro'] = str_replace($matches[0], $vals[0], $testsettings['intro']);
                    } else if (strtolower($matches[1])=='assessment') {
                        $importAssessment = Assessments::getByAssessmentId(intval($matches[2]));
                        $vals = $importAssessment->intro;
                        $testsettings['intro'] = str_replace($matches[0], $vals[0], $testsettings['intro']);
                    }
                }
            }

            if (!$isTeacher) {
                $rec = "data-base=\"assessintro-{$assessmentSession->assessmentid}\" ";
                $testsettings['intro'] = str_replace('<a ','<a '.$rec, $testsettings['intro']);
            }

            list($testsettings['testtype'], $testsettings['showans']) = explode('-', $testsettings['deffeedback']);

            $now = time();

            if ($testsettings['avail']==0 && !$isTeacher) {
                echo 'Assessment is closed';die;
                //    leavetestmsg();
            }

            $actas = 1;
            if ($actas) {

                $row = Exceptions::getByAssessmentIdAndUserId($user->id, $assessmentId);

                if ($row) {
                    if ($now < $row->startdate || $row->enddate < $now) { //outside exception dates
                        if ($now > $testsettings['startdate'] && $now < $testsettings['reviewdate']) {
                            $isreview = true;
                        } else {
                            if (!$isTeacher) {
                                echo 'Assessment is closed';die;
                                //leavetestmsg();
                            }
                        }
                    } else { //in exception
                        if ($testsettings['enddate'] < $now) { //exception is for past-due-date
                            $inexception = true;
                        }
                    }
                    $exceptionduedate = $row->enddate;
                } else { //has no exception
                    if ($now < $testsettings['startdate'] || $testsettings['enddate'] < $now) {//outside normal dates
                        if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
                            $isreview = true;
                        } else {
                            if (!$isTeacher) {
                                echo 'Assessment is closed';die;
                                // leavetestmsg();
                            }
                        }
                    }
                }
            }

            $qi = getquestioninfo($questions, $testsettings);


            for ($i = 0; $i < count($questions); $i++) {
                if ($qi[$questions[$i]]['withdrawn'] == 1 && $qi[$questions[$i]]['points'] > 0) {
                    $bestscores[$i] = $qi[$questions[$i]]['points'];
                    $bestrawscores[$i] = 1;
                }
            }

            //global $showtips;
            $allowregen = (!$superdone && ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework"));
            $showeachscore = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="AsGo" || $testsettings['testtype']=="Homework");
            $showansduring = (($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework") && is_numeric($testsettings['showans']));
            $showansafterlast = ($testsettings['showans']==='F' || $testsettings['showans']==='J');
            $noindivscores = ($testsettings['testtype']=="EndScore" || $testsettings['testtype']=="NoScores");
            $reviewatend = ($testsettings['testtype']=="EndReview");
            $showhints = ($testsettings['showhints']==1);
            $showtips = $testsettings['showtips'];
            $regenonreattempt = (($testsettings['shuffle']&8)==8 && !$allowregen);
            if ($regenonreattempt) {
                $nocolormark = true;
            }


            if ($testsettings['eqnhelper']==1 || $testsettings['eqnhelper']==2) {
                $placeinhead = "<script type='text/javascript'>var eetype='".$testsettings['eqnhelper']."</script>";
                $placeinhead .= "<script type='text/javascript' src = '".AppUtility::getHomeURL()."js/eqnhelper.js?v=030112'></script>";
                $placeinhead .= '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';

            } else if ($testsettings['eqnhelper']==3 || $testsettings['eqnhelper']==4) {
                $placeinhead = "<link rel='stylesheet' href='".AppUtility::getHomeURL()."/assessment/mathquill.css?v=102113' type='text/css' />";
                if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')!==false) {
                    $placeinhead .= '<!--[if lte IE 7]><style style="text/css">
				.mathquill-editable.empty { width: 0.5em; }
				.mathquill-rendered-math .numerator.empty, .mathquill-rendered-math .empty { padding: 0 0.25em;}
				.mathquill-rendered-math sup { line-height: .8em; }
				.mathquill-rendered-math .numerator {float: left; padding: 0;}
				.mathquill-rendered-math .denominator { clear: both;width: auto;float: left;}
				</style><![endif]-->';
                }
                $placeinhead .= "<script type='text/javascript' src='".AppUtility::getHomeURL()."js/mathquill_min.js?v=102113'></script>";
                $placeinhead .= "<script type='text/javascript' src='".AppUtility::getHomeURL()."js/mathquilled.js?v=070214'></script>";
                $placeinhead .= "<script type='text/javascript' src='".AppUtility::getHomeURL()."javascript/AMtoMQ.js?v=102113'></script>";
                $placeinhead .= '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';

            }
       
            $useeqnhelper = $testsettings['eqnhelper'];

            $responseString .= '<div id="headershowtest" class="pagetitle">';
            $responseString .= "<h2>{$testsettings['name']}</h2></div>\n";
            $responseString .= "<div class=right id=timelimitholder><span id='timercontent'></span><span id='timerhide' class='clickable' title='Hide' style='color:#aaa;' onclick='toggletimer()'>[x]</span> <span style='color: #000000;' class='time' id='expired'></span> </div>\n";

            if ($testsettings['testtype']=="Practice" && !$isreview) {
                echo "<div class=right><span style=\"color:#f00\">Practice Test.</span>  <a href=\"showtest.php?regenall=fromscratch\">", _('Create new version.'), "</a></div>";
            }

            if (!$isreview && !$superdone) {
                if ($exceptionduedate > 0) {
                    $timebeforedue = $exceptionduedate - time();
                } else {
                    $timebeforedue = $testsettings['enddate'] - time();
                }
                if ($timebeforedue < 0) {
                    $duetimenote = _('Past due');
                } else if ($timebeforedue < 24*3600) { //due within 24 hours
                    if ($timebeforedue < 300) {
                        $duetimenote = '<span style="color:#f00;">' . _('Due in under ');
                    } else {
                        $duetimenote = '<span>' . _('Due in ');
                    }
                    if ($timebeforedue>3599) {
                        $duetimenote .= floor($timebeforedue/3600). " " . _('hours') . ", ";
                    }
                    $duetimenote .= ceil(($timebeforedue%3600)/60). " " . _('minutes');
                    $duetimenote .= '. ';
                    if ($exceptionduedate > 0) {
                        $duetimenote .= _('Due') . " " . self::tzdate('D m/d/Y g:i a',$exceptionduedate);
                    } else {
                        $duetimenote .= _('Due') . " " . self::tzdate('D m/d/Y g:i a',$testsettings['enddate']);
                    }
                } else {
                    if ($testsettings['enddate']==2000000000) {
                        $duetimenote = '';
                    } else if ($exceptionduedate > 0) {
                        $duetimenote = _('Due') . " " . self::tzdate('D m/d/Y g:i a',$exceptionduedate);
                    } else {
                        $duetimenote = _('Due') . " " . self::tzdate('D m/d/Y g:i a',$testsettings['enddate']);
                    }
                }
            }

            $restrictedtimelimit = false;

            $responseString .= '<div class="right margin-right-inset"><a href="#" onclick="togglemainintroshow(this);return false;">'._("Show Intro/Instructions").'</a></div>';

            if (isset($_GET['score'])) { //score a problem
                $qn = $_GET['score'];

                if ($_POST['verattempts']!=$attempts[$qn]) {
                    $responseString .= "<p>This question has been submittted since you viewed it, and that grade is shown below.  Your answer just submitted was not scored or recorded.</p>";
                } else {
                    if (isset($_POST['disptime']) && !$isreview) {
                        $used = $now - intval($_POST['disptime']);
                        $timesontask[$qn] .= (($timesontask[$qn]=='')?'':'~').$used;
                    }
                    $GLOBALS['scoremessages'] = '';
                    $GLOBALS['questionmanualgrade'] = false;

                    $rawscore = scorequestion($qn);

                    $immediatereattempt = false;

                    if (!$superdone && $showeachscore && hasreattempts($qn)) {

                        if (!(($regenonreattempt && $qi[$questions[$toclear]]['regen']==0) || $qi[$questions[$toclear]]['regen']==1)) {
                            if (!in_array($qn,$reattempting)) {
                                //$reattempting[] = $qn;
                                $immediatereattempt = true;
                            }
                        }
                    }
                    //record score
                    recordtestdata();
                }
                if (!$superdone) {
                    $responseString .= filter("<div id=intro class=hidden>{$testsettings['intro']}</div>\n");
                    $lefttodo = self::shownavbar($questions,$scores,$qn,$testsettings['showcat'], $courseId, $assessmentId);

                    $responseString .= "<div class=inset>\n";
                    $responseString .= "<a name=\"beginquestions\"></a>\n";
                    if ($GLOBALS['scoremessages'] != '') {
                        $responseString .= '<p>'.$GLOBALS['scoremessages'].'</p>';
                    }

                    if ($showeachscore) {
                        $possible = $qi[$questions[$qn]]['points'];
                        if (getpts($rawscore)!=getpts($scores[$qn])) {
                            $responseString .= "<p>Score before penalty on last attempt: ";
                            $responseString .= printscore($rawscore,$qn);
                            $responseString .= "</p>";
                        }
                        $responseString .= "<p>";
                        $responseString .= 'Score on last attempt: ';
                        $responseString .= printscore($scores[$qn],$qn);
                        $responseString .= "</p>\n";
                        $responseString .= "<p>Score in gradebook: ";
                        $responseString .= printscore($bestscores[$qn],$qn);
                        $responseString .= "</p>";
                        if ($GLOBALS['questionmanualgrade'] == true) {
                            $responseString .= '<p><strong>Note: </strong> This question contains parts that can not be auto-graded.  Those parts will count as a score of 0 until they are graded by your instructor</p>';
                        }


                    } else {
                        $responseString .= '<p>Question Scored</p>';
                    }

                    $reattemptsremain = false;
                    if (hasreattempts($qn)) {
                        $reattemptsremain = true;
                    }

                    if ($allowregen && $qi[$questions[$qn]]['allowregen']==1) {
                        $responseString .= '<p>';
                        if ($reattemptsremain && !$immediatereattempt) {
                            $responseString .= "<a href=\"showtest.php?action=skip&amp;to=$qn&amp;reattempt=$qn\">Reattempt last question</a>, ";
                        }
                        $responseString .= "<a href=\"showtest.php?action=skip&amp;to=$qn&amp;regen=$qn\">Try another similar question</a>";
                        if ($immediatereattempt) {
                            $responseString .= _(", reattempt last question below, or select another question.");
                        } else {
                            $responseString .= _(", or select another question");
                        }
                        $responseString .= "</p>\n";
                    } else if ($reattemptsremain && !$immediatereattempt) {
                        $responseString .= "<p><a href=\"showtest.php?action=skip&amp;to=$qn&amp;reattempt=$qn\">Reattempt last question</a>";
                        if ($lefttodo > 0) {
                            $responseString .=  _(", or select another question");
                        }
                        $responseString .= '</p>';
                    } else if ($lefttodo > 0) {
                        $responseString .= "<p>"._('Select another question').'</p>';
                    }

                    if ($reattemptsremain == false && $showeachscore && $testsettings['showans']!='N') {
                        //TODO i18n

                        $responseString .= "<p>This question, with your last answer";
                        if (($showansafterlast && $qi[$questions[$qn]]['showans']=='0') || $qi[$questions[$qn]]['showans']=='F' || $qi[$questions[$qn]]['showans']=='J') {
                            $responseString .= " and correct answer";
                            $showcorrectnow = true;
                        } else if ($showansduring && $qi[$questions[$qn]]['showans']=='0' && $qi[$questions[$qn]]['showans']=='0' && $testsettings['showans']==$attempts[$qn]) {
                            $responseString .= " and correct answer";
                            $showcorrectnow = true;
                        } else {
                            $showcorrectnow = false;
                        }

                        $responseString .= ', is displayed below</p>';
                        if (!$noraw && $showeachscore && $GLOBALS['questionmanualgrade'] != true) {
                            //$colors = scorestocolors($rawscores[$qn], '', $qi[$questions[$qn]]['answeights'], $noraw);
                            if (strpos($rawscores[$qn],'~')!==false) {
                                $colors = explode('~',$rawscores[$qn]);
                            } else {
                                $colors = array($rawscores[$qn]);
                            }
                        } else {
                            $colors = array();
                        }
                        if ($showcorrectnow) {
                            displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],2,false,$attempts[$qn],false,false,false,$colors);
                        } else {
                            displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],false,false,$attempts[$qn],false,false,false,$colors);
                        }
                        $contactlinks = showquestioncontactlinks($qn);
                        if ($contactlinks!='' && !$sessiondata['istutorial']) {
                            $responseString .= '<div class="review">'.$contactlinks.'</div>';
                        }

                    } else if ($immediatereattempt) {
                        $next = $qn;
                        if (isset($intropieces)) {
                            foreach ($introdividers as $k=>$v) {
                                if ($v[1]<=$next+1 && $next+1<=$v[2]) {//right divider
                                    if ($next+1==$v[1]) {
                                        $responseString .= '<div><a href="#" id="introtoggle'.$k.'" onclick="toggleintroshow('.$k.'); return false;">Hide Question Information</a></div>';
                                        $responseString .= '<div class="intro" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                    } else {
                                        $responseString .= '<div><a href="#" id="introtoggle'.$k.'" onclick="toggleintroshow('.$k.'); return false;">Show Question Information</a></div>';
                                        $responseString .= '<div class="intro" style="display:none;" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                    }
                                    break;
                                }
                            }
                        }
                        $responseString .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"showtest.php?action=skip&amp;score=$next\" onsubmit=\"return doonsubmit(this)\">\n";
                        $responseString .= "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
                        $responseString .= '<input type="hidden" name="disptime" value="'.time().'" />';
                        $responseString .= "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
                        $responseString .= "<a name=\"beginquestions\"></a>\n";
                        basicshowq($next);
                        showqinfobar($next,true,true);
                        $responseString .= '<input type="submit" class="btn" value="'. _('Submit'). '" />';
                        if (($testsettings['showans']=='J' && $qi[$questions[$next]]['showans']=='0') || $qi[$questions[$next]]['showans']=='J') {
                            $responseString .= ' <input type="button" class="btn" value="Jump to Answer" onclick="if (confirm(If you jump to the answer, you must generate a new version to earn credit))) {window.location = \'showtest.php?action=skip&amp;jumptoans='.$next.'&amp;to='.$next.'\'}"/>';
                        }
                        $responseString .= "</form>\n";

                    }

                    $responseString .= "<br/><p>When you are done, <a href=\"showtest.php?action=skip&amp;done=true\">click here to see a summary of your scores</a>.</p>\n";

                    $responseString .= "</div>\n";
                }
            }else{
                $responseString .= filter("<div id=intro class='hidden margin-right-inset'>{$testsettings['intro']}</div>\n");

                $lefttodo = self::shownavbar($questions, $scores, $next, $testsettings['showcat'], $courseId, $assessmentId);

                if (unans($scores[$next]) || amreattempting($next)) {
                    $responseString .= "<div class=inset>\n";
                    if (isset($intropieces)) {
                        foreach ($introdividers as $k=>$v) {
                            if ($v[1]<=$next+1 && $next+1<=$v[2]) {//right divider
                                if ($next+1==$v[1]) {
                                    $responseString .= '<div><a href="#" id="introtoggle'.$k.'" onclick="toggleintroshow('.$k.'); return false;"> Hide Question Information</a></div>';
                                    $responseString .= '<div class="intro" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                } else {
                                    $responseString .= '<div><a href="#" id="introtoggle'.$k.'" onclick="toggleintroshow('.$k.'); return false;">Show Question Information</a></div>';
                                    $responseString .= '<div class="intro" style="display:none;" id="intropiece'.$k.'">'.filter($intropieces[$k]).'</div>';
                                }
                                break;
                            }
                        }
                    }

                    $responseString .= "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"show-assessment?id=".$assessmentId."&amp;cid=".$courseId."&amp;action=skip&amp;score=$next\" onsubmit=\"return doonsubmit(this)\">\n";
//                echo "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
//                echo '<input type="hidden" name="disptime" value="'.time().'" />';
//                echo "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";

                    $responseString .= "<a name=\"beginquestions\"></a>\n";
                    basicshowq($next);
                    showqinfobar($next ,true,true);
                    $responseString .= '<input type="submit" class="btn btn-primary" value="'. _('Submit'). '" />';
                    if (($testsettings['showans']=='J' && $qi[$questions[$next]]['showans']=='0') || $qi[$questions[$next]]['showans']=='J') {
                        $responseString .= ' <input type="button" class="btn" value="Jump to Answer" onclick="if (confirm(\'If you jump to the answer, you must generate a new version to earn credit\')) {window.location = \'showtest.php?action=skip&amp;jumptoans='.$next.'&amp;to='.$next.'\'}"/>';
                    }
                    $responseString .= "</form>\n";
                    $responseString .= "</div>\n";

                }else {
                    $responseString .= "<div class=inset>\n";
                    $responseString .= "<a name=\"beginquestions\"></a>\n";
                    $responseString .= "You've already done this problem.\n";
                    $reattemptsremain = false;
                    if ($showeachscore) {
                        $possible = $qi[$questions[$next]]['points'];
                        $responseString .= "<p>Score on last attempt: ";
                        $responseString .= printscore($scores[$next],$next);
                        $responseString .= "</p>\n";
                        $responseString .= "<p>Score in gradebook: ";
                        $responseString .= printscore($bestscores[$next],$next);
                        $responseString .= "</p>";
                    }
                    if (hasreattempts($next)) {
                        //if ($showeachscore) {
                        $responseString .= "<p><a href=\"showtest.php?action=skip&amp;to=$next&amp;reattempt=$next\">Reattempt this question</a></p>\n";
                        //}
                        $reattemptsremain = true;
                    }
                    if ($allowregen && $qi[$questions[$next]]['allowregen']==1) {
                        $responseString .= "<p><a href=\"showtest.php?action=skip&amp;to=$next&amp;regen=$next\">Try another similar question</a></p>\n";
                    }
                    if ($lefttodo == 0) {
                        $responseString .= "<a href=\"showtest.php?action=skip&amp;done=true\">When you are done, click here to see a summary of your score</a>\n";
                    }
                    if (!$reattemptsremain && $testsettings['showans']!='N') {// && $showeachscore) {
                        $responseString .= "<p>Question with last attempt is displayed for your review only</p>";

                        if (!$noraw && $showeachscore) {
                            //$colors = scorestocolors($rawscores[$next], '', $qi[$questions[$next]]['answeights'], $noraw);
                            if (strpos($rawscores[$next],'~')!==false) {
                                $colors = explode('~',$rawscores[$next]);
                            } else {
                                $colors = array($rawscores[$next]);
                            }
                        } else {
                            $colors = array();
                        }
                        $qshowans = ((($showansafterlast && $qi[$questions[$next]]['showans']=='0') || $qi[$questions[$next]]['showans']=='F' || $qi[$questions[$next]]['showans']=='J') || ($showansduring && $qi[$questions[$next]]['showans']=='0' && $attempts[$next]>=$testsettings['showans']));
                        if ($qshowans) {
                            displayq($next,$qi[$questions[$next]]['questionsetid'],$seeds[$next],2,false,$attempts[$next],false,false,false,$colors);
                        } else {
                            displayq($next,$qi[$questions[$next]]['questionsetid'],$seeds[$next],false,false,$attempts[$next],false,false,false,$colors);
                        }
                        $contactlinks = showquestioncontactlinks($next);
                        if ($contactlinks!='') {
                            $responseString .= '<div class="review">'.$contactlinks.'</div>';
                        }
                    }
                    $responseString .= "</div>\n";
                }
            }

        }

        return $responseString;
    }



    static function shownavbar($questions,$scores,$current,$showcat,$courseId,$assessmentId) {
        global $responseString, $isdiag,$testsettings,$attempts,$qi,$allowregen,$bestscores,$isreview,$showeachscore,$noindivscores;
        $showeachscore = 1;
        $todo = 0;
        $earned = 0;
        $poss = 0;
        $responseString .= "<a href='#beginquestions'><img class=skipnav src='".AppUtility::getHomeURL()."img/blank.gif' alt='Skip Navigation')'/></a>\n";
        $responseString .= "<div class=navbar>";
        $responseString .= "<h4>Questions</h4>\n";
        $responseString .= "<ul class=qlist>\n";
        for ($i = 0; $i < count($questions); $i++) {
            $responseString .= "<li>";
            if ($current == $i) { $responseString .= "<span class=current>";}
            if (unans($scores[$i]) || amreattempting($i)) {
                $todo++;
            }
            if ($isreview) {
                $thisscore = getpts($scores[$i]);
            } else {
                $thisscore = getpts($bestscores[$i]);
            }

            if ((unans($scores[$i]) && $attempts[$i]==0) || ($noindivscores && amreattempting($i))) {
                $responseString .= "<img alt='untried' src='".AppUtility::getHomeURL()."img/te_blue_arrow.png'/> ";
            } else if (canimprove($i) && !$noindivscores) {
                if ($thisscore==0 || $noindivscores) {
                    $responseString .= "<img alt=\"incorrect - can retry\" src='".AppUtility::getHomeURL()."img/te_red_redo.png'/> ";
                } else {
                    $responseString .= "<img alt=\"partially correct - can retry\" src='".AppUtility::getHomeURL()."img/{te_yellow_redo.png'/> ";
                }
            } else {
                if (!$showeachscore) {
                    $responseString .= "<img alt=\"cannot retry\" src='".AppUtility::getHomeURL()."img/te_blank.gif'/> ";
                } else {
                    if ($thisscore == $qi[$questions[$i]]['points']) {
                        $responseString .= "<img alt=\"correct\" src='".AppUtility::getHomeURL()."img/te_green_check.png'/> ";
                    } else if ($thisscore==0) {
                        $responseString .= "<img alt=\"incorrect - cannot retry\" src='".AppUtility::getHomeURL()."img/te_red_ex.png'/> ";
                    } else {
                        $responseString .= "<img alt=\"partially correct - cannot retry\" src='".AppUtility::getHomeURL()."img/te_yellow_check.png'/> ";
                    }
                }
            }


            if ($showcat > 1 && $qi[$questions[$i]]['category']!='0') {
                if ($qi[$questions[$i]]['withdrawn']==1) {
                    $responseString .= "<a href=\"showtest.php?to=$i\"><span class=\"withdrawn\">". ($i+1) . ") {$qi[$questions[$i]]['category']}</span></a>";
                } else {
                    $responseString .= "<a href=\"showtest.php?to=$i\">". ($i+1) . ") {$qi[$questions[$i]]['category']}</a>";
                }
            } else {
                if ($qi[$questions[$i]]['withdrawn']==1) {
                    $responseString .= "<a href=\"showtest.php?to=$i\"><span class=\"withdrawn\">Q ". ($i+1) . "</span></a>";
                } else {
                    $responseString .= "<a href=\"show-assessment?id=$assessmentId&amp;cid=$courseId&amp;to=$i\">Q ". ($i+1) . "</a>";
                }
            }
            if ($showeachscore) {
                if ((canimprove($i) && $isreview) || (!$isreview && canimprovebest($i))) {
                    $responseString .= ' (';
                } else {
                    $responseString .= ' [';
                }
                if ($isreview) {
                    $thisscore = getpts($scores[$i]);
                } else {
                    $thisscore = getpts($bestscores[$i]);
                }
                if ($thisscore < 0) {
                    $responseString .= '0';
                } else {
                    $responseString .= $thisscore;
                    $earned += $thisscore;
                }
                $responseString .= '/'.$qi[$questions[$i]]['points'];
                $poss += $qi[$questions[$i]]['points'];
                if (($isreview && canimprove($i)) || (!$isreview && canimprovebest($i))) {
                    $responseString .= ')';
                } else {
                    $responseString .= ']';
                }
            }

            if ($current == $i) { $responseString .= "</span>";}

            $responseString .= "</li>\n";
        }
        $responseString .= "</ul>";
        if ($showeachscore) {
            if ($isreview) {
                $responseString .= "<p>Review: ";
            } else {
                $responseString .= "<p>Grade: ";
            }
            $responseString .= $earned."/".$poss."</p>";
        }
        if (!$isdiag && $testsettings['noprint']==0) {
            $responseString .= "<p><a href='#' onclick=\"window.open('".AppUtility::getHomeURL()."assessment/assessment/print-test?aid=".$assessmentId."','printver','width=400,height=300,toolbar=1,menubar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));return false;\">Print Version</a></p> ";
        }

        $responseString .= "</div>\n";

        return $todo;
    }

    public static function getRefererUri($refere){
        $home = self::getHomeURL();
        $hostInfo = Yii::$app->request->hostInfo;
        $absUrl = str_replace($hostInfo, '', $refere);
        $refereUri = $absUrl;
        if(strpos($hostInfo, 'localhost') != false)
        {
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

    public static function printTest($teacherid, $isteacher, $assessmentSessionId, $user)
    {
        global $allowedmacros, $mathfuncs, $questions, $seeds, $responseString;
        $allowedmacros = array();
        $mathfuncs = array("sin","cos","tan","sinh","cosh","tanh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
        $allowedmacros = $mathfuncs;
        $userfullname = "";

        $responseString = "";
        $isteacher = (isset($teacherid) || $isteacher==true);

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

        include("displayq2.php");
        include("testutil.php");
        $flexwidth = true; //tells header to use non _fw stylesheet
        if ($scoredview) {
            $placeinhead = '<script type="text/javascript">
			$(function() {
				$(\'input[value="Preview"]\').click().hide();
			});
			</script>';
        }
        //$sessiondata['coursetheme'] = $coursetheme;

        $responseString .= "<style type=\"text/css\" media=\"print\">.hideonprint {display:none;} p.tips {display: none;}\n input.btn {display: none;}\n textarea {display: none;}\n input.sabtn {display: none;} .question, .review {background-color:#fff;}</style>\n";
        $responseString .= "<style type=\"text/css\">p.tips {	display: none;}\n </style>\n";
        $responseString .= '<script type="text/javascript">function rendersa() { ';
        $responseString .= '  el = document.getElementsByTagName("span"); ';
        $responseString .= '   for (var i=0;i<el.length;i++) {';
        $responseString .= '     if (el[i].className=="hidden") { ';
        $responseString .= '         el[i].className = "shown";';
        //echo '		 AMprocessNode(el)';
        $responseString .= '     }';
        $responseString .= '    }';
        $responseString .= '} </script>';

        if ($isteacher && isset($_GET['asid'])) {
            $testid = $_GET['asid'];
        } else {
            $testid = $assessmentSessionId;
        }

        $connection = Yii::$app->getDb();
        $query = "SELECT * FROM imas_assessment_sessions WHERE id='$testid'";
        $line = $connection->createCommand($query)->queryOne();
        if (strpos($line['questions'],';')===false) {
            $questions = explode(",",$line['questions']);
            $bestquestions = $questions;
        } else {
            list($questions,$bestquestions) = explode(";",$line['questions']);
            $questions = explode(",",$questions);
            $bestquestions = explode(",",$bestquestions);
        }
        if ($scoredtype=='last') {
            $seeds = explode(",",$line['seeds']);
            $sp = explode(';',$line['scores']);
            $scores = explode(",",$sp[0]);
            $rawscores = explode(',', $sp[1]);
            $attempts = explode(",",$line['attempts']);
            $lastanswers = explode("~",$line['lastanswers']);
        } else {
            $seeds = explode(",",$line['bestseeds']);
            $sp = explode(';',$line['bestscores']);
            $scores = explode(",",$sp[0]);
            $rawscores = explode(',', $sp[1]);
            $attempts = explode(",",$line['bestattempts']);
            $lastanswers = explode("~",$line['bestlastanswers']);
            $questions = $bestquestions;
        }

        $timesontask = explode("~",$line['timeontask']);

        if ($isteacher) {
            if ($line['userid'] != $user->id) {
                $query = "SELECT LastName,FirstName FROM imas_users WHERE id='{$line['userid']}'";
                $row = $connection->createCommand($query)->queryOne();
                $userfullname = $row[1]." ".$row[0];
            }
            $userid= $line['userid'];
        }

        $query = "SELECT * FROM imas_assessments WHERE id='{$line['assessmentid']}'";
        $testsettings = $connection->createCommand($query)->queryOne();
        list($testsettings['testtype'],$testsettings['showans']) = explode('-',$testsettings['deffeedback']);

        $qi = getquestioninfo($questions,$testsettings);



        $now = time();
        $isreview = false;
        if (!$scoredview && ($now < $testsettings['startdate'] || $testsettings['enddate']<$now)) { //outside normal range for test
            $query = "SELECT startdate,enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='{$line['assessmentid']}'";

            $row = $connection->createCommand($query)->queryOne();
            if ($row!=null) {
                if ($now<$row[0] || $row[1]<$now) { //outside exception dates
                    if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
                        $isreview = true;
                    } else {
                        if (!$isteacher) {
                            $responseString .= "Assessment is closed";
                            $responseString .= "<br/><a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to course page</a>";
                            return $responseString;
                        }
                    }
                }
            } else { //no exception
                if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
                    $isreview = true;
                } else {
                    if (!$isteacher) {
                        $responseString .= "Assessment is closed";
                        $responseString .= "<br/><a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to course page</a>";
                        return $responseString;
                    }
                }
            }
        }
        if ($isreview) {
            $seeds = explode(",",$line['reviewseeds']);
            $scores = explode(",",$line['reviewscores']);
            $attempts = explode(",",$line['reviewattempts']);
            $lastanswers = explode("~",$line['reviewlastanswers']);
        }

        $responseString .= "<h4 style=\"float:right;\">Name: $userfullname </h4>\n";
        $responseString .= "<h3>".$testsettings['name']."</h3>\n";


        $allowregen = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework");
        $showeachscore = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="AsGo" || $testsettings['testtype']=="Homework");
        $showansduring = (($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework") && $testsettings['showans']!='N');
        $GLOBALS['useeditor']='reviewifneeded';
        $responseString .= "<div class=breadcrumb>Print Ready Version</div>";

        $endtext = '';  $intropieces = array();
        if (strpos($testsettings['intro'], '[QUESTION')!==false) {
            //embedded type
            $intro = preg_replace('/<p>((<span|<strong|<em)[^>]*>)?\[QUESTION\s+(\d+)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/','[QUESTION $3]',$testsettings['intro']);
            $introsplit = preg_split('/\[QUESTION\s+(\d+)\]/', $intro, -1, PREG_SPLIT_DELIM_CAPTURE);

            for ($i=1;$i<count($introsplit);$i+=2) {
                $intropieces[$introsplit[$i]] = $introsplit[$i-1];
            }
            //no specific start text - will just go before first question
            $testsettings['intro'] = '';
            $endtext = $introsplit[count($introsplit)-1];
        } else if (strpos($testsettings['intro'], '[Q ')!==false) {
            //question info type
            $intro = preg_replace('/<p>((<span|<strong|<em)[^>]*>)?\[Q\s+(\d+(\-(\d+))?)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/','[Q $3]',$testsettings['intro']);
            $introsplit = preg_split('/\[Q\s+(.*?)\]/', $intro, -1, PREG_SPLIT_DELIM_CAPTURE);
            $testsettings['intro'] = $introsplit[0];
            for ($i=1;$i<count($introsplit);$i+=2) {
                $p = explode('-',$introsplit[$i]);
                $intropieces[$p[0]] = $introsplit[$i+1];
            }
        }


        $responseString .= '<div class=intro>'.$testsettings['intro'].'</div>';
        if ($isteacher && !$scoredview) {
            $responseString .= '<p>'._('Showing Current Versions').'<br/><button type="button" class="btn" onclick="rendersa()">'._("Show Answers").'</button> <a href="print-test?cid='.$cid.'&asid='.$testid.'&scored=best">'._('Show Scored View').'</a> <a href="print-test?cid='.$cid.'&asid='.$testid.'&scored=last">'._('Show Last Attempts').'</a></p>';
        } else if ($isteacher) {
            if ($scoredtype=='last') {
                $responseString .= '<p>'._('Showing Last Attempts').' <a href="print-test?cid='.$cid.'&asid='.$testid.'&scored=best">'._('Show Scored View').'</a></p>';
            } else {
                $responseString .= '<p>'._('Showing Scored View').' <a href="print-test?cid='.$cid.'&asid='.$testid.'&scored=last">'._('Show Last Attempts').'</a></p>';
            }

        }
        if ($testsettings['showans']=='N') {
            $lastanswers = array_fill(0,count($questions),'');
        }
        for ($i = 0; $i < count($questions); $i++) {
            //list($qsetid,$cat) = getqsetid($questions[$i]);
            $qsetid = $qi[$questions[$i]]['questionsetid'];
            $cat = $qi[$questions[$i]]['category'];

            $showa = $isteacher;
            if (isset($intropieces[$i+1])) {
                $responseString .= '<div class="intro">'.$intropieces[$i+1].'</div>';
            }
            $responseString .= '<div class="nobreak">';
            if (isset($_GET['descr'])) {
                $query = "SELECT description FROM imas_questionset WHERE id='$qsetid'";
                $result = $connection->createCommand($query)->queryOne();
                $responseString .= '<div>ID:'.$qsetid.', '.mysql_result($result,0,0).'</div>';
            } else {
                //list($points,$qattempts) = getpointspossible($questions[$i],$testsettings['defpoints'],$testsettings['defattempts']);
                $points = $qi[$questions[$i]]['points'];
                $qattempts = $qi[$questions[$i]]['attempts'];
                if ($scoredview) {
                    $responseString .= "<div>#".($i+1)." ";
                    $responseString .= printscore($scores[$i], $i);
                    $responseString .= "</div>";
                } else {
                    $responseString .= "<div>#".($i+1)." Points possible: $points.  Total attempts: $qattempts</div>";
                }
            }
            if ($scoredview) {
                //$col = scorestocolors($scores[$i], $qi[$questions[$i]]['points'], $qi[$questions[$i]]['answeights']);
                if (isset($rawscores[$i])) {
                    //$colors = scorestocolors($rawscores[$i],$pts[$questions[$i]],$answeights[$questions[$i]],false);
                    if (strpos($rawscores[$i],'~')!==false) {
                        $colors = explode('~',$rawscores[$i]);
                    } else {
                        $colors = array($rawscores[$i]);
                    }
                } else {
                    $colors = array();
                }
                displayq($i, $qsetid,$seeds[$i],2,false,$attempts[$i],false,false,false,$colors);

                $responseString .= '<div class="review">';
                $laarr = explode('##',$lastanswers[$i]);

                if (count($laarr)>1) {
                    $responseString .= "Previous Attempts:";
                    $cnt =1;
                    for ($k=0;$k<count($laarr)-1;$k++) {
                        if ($laarr[$k]=="ReGen") {
                            $responseString .= ' ReGen ';
                        } else {
                            $responseString .= "  <b>$cnt:</b> " ;
                            if (preg_match('/@FILE:(.+?)@/',$laarr[$k],$match)) {
                                $url = getasidfileurl($match[1]);
                                $responseString .= "<a href=\"$url\" target=\"_new\">".basename($match[1])."</a>";
                            } else {
                                if (strpos($laarr[$k],'$f$')) {
                                    if (strpos($laarr[$k],'&')) { //is multipart q
                                        $laparr = explode('&',$laarr[$k]);
                                        foreach ($laparr as $lk=>$v) {
                                            if (strpos($v,'$f$')) {
                                                $tmp = explode('$f$',$v);
                                                $laparr[$lk] = $tmp[0];
                                            }
                                        }
                                        $laarr[$k] = implode('&',$laparr);
                                    } else {
                                        $tmp = explode('$f$',$laarr[$k]);
                                        $laarr[$k] = $tmp[0];
                                    }
                                }
                                if (strpos($laarr[$k],'$!$')) {
                                    if (strpos($laarr[$k],'&')) { //is multipart q
                                        $laparr = explode('&',$laarr[$k]);
                                        foreach ($laparr as $lk=>$v) {
                                            if (strpos($v,'$!$')) {
                                                $tmp = explode('$!$',$v);
                                                $laparr[$lk] = $tmp[0];
                                            }
                                        }
                                        $laarr[$k] = implode('&',$laparr);
                                    } else {
                                        $tmp = explode('$!$',$laarr[$k]);
                                        $laarr[$k] = $tmp[0];
                                    }
                                }
                                if (strpos($laarr[$k],'$#$')) {
                                    if (strpos($laarr[$k],'&')) { //is multipart q
                                        $laparr = explode('&',$laarr[$k]);
                                        foreach ($laparr as $lk=>$v) {
                                            if (strpos($v,'$#$')) {
                                                $tmp = explode('$#$',$v);
                                                $laparr[$lk] = $tmp[0];
                                            }
                                        }
                                        $laarr[$k] = implode('&',$laparr);
                                    } else {
                                        $tmp = explode('$#$',$laarr[$k]);
                                        $laarr[$k] = $tmp[0];
                                    }
                                }

                                $responseString .= str_replace(array('&','%nbsp;'),array('; ','&nbsp;'),strip_tags($laarr[$k]));
                            }
                            $cnt++;
                        }

                    }
                    $responseString .= '. ';
                }
                if ($timesontask[$i]!='') {
                    $responseString .= 'Average time per submission: ';
                    $timesarr = explode('~',$timesontask[$i]);
                    $avgtime = array_sum($timesarr)/count($timesarr);
                    if ($avgtime<60) {
                        $responseString .= round($avgtime,1) . ' seconds ';
                    } else {
                        $responseString .= round($avgtime/60,1) . ' minutes ';
                    }
                    $responseString .= '<br/>';
                }
                $responseString .= '</div>';

            } else {
                displayq($i,$qsetid,$seeds[$i],$showa,($testsettings['showhints']==1),$attempts[$i]);
            }
            $responseString .= "<hr />";
            $responseString .= '</div>';

        }
        if ($endtext != '') {
            $responseString .= '<div class="intro">'.$endtext.'</div>';
        }
        return $responseString;
    }

}
