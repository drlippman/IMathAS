<?php

namespace app\components;


use Yii;
use yii\base\Component;

class AppUtility extends Component {

    /**
     * Function to print data and exit the process.
     * It prints the data value which is passed as argument.
     * @param $data
     */
    public static function dump($data){
		echo "<pre>";
		print_r($data);
		echo "</pre>";
		die;
	}

    /**
     * This is utility function to generate random string.
     * @return string
     */
    public static function generateRandomString() {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $pass = '';
        for ($i=0; $i<10; $i++) {
            $pass .= substr($chars,rand(0,61),1);
        }
        return $pass;
    }

    public static function getStringVal($str){
        return isset($str) ? $str : null;
    }

    public static function getIntVal($str){
        return isset($str) ? $str : 0;
    }

    public static function getURLFromHome($controllerName, $shortURL){
        return Yii::$app->homeUrl.$controllerName . "/".$shortURL;
    }

    public static function getHomeURL()
    {
        return Yii::$app->homeUrl;
    }


    public static function checkEditOrOk() {
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

    public static function urlMode()
    {
        $urlmode = '';
        if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
            $urlmode = 'https://';
        } else {
            $urlmode = 'http://';
        }
        return $urlmode;
    }

    public static function removeEmptyAttributes($params)
    {
        if(!empty($params) && is_array($params)){
            if(is_object($params)){
                $params = (array)$params;
            }

            foreach($params as $key => $singleParam){
                if(empty($singleParam)){
                    if($singleParam != '0')
                        unset($params[$key]);
                }
            }
        }
        return $params;
    }

    public static function verifyPassword($newPassword, $oldPassword)
    {
        require_once("Password.php");
        if(password_verify($newPassword, $oldPassword)){
            return true;
        }
        return false;
    }

    public static function passwordHash($password)
    {
        require_once("Password.php");
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function makeToolset($params)
    {
        if(is_array($params))
        {
            if(count($params) == 3)
                return 0;
            elseif(count($params) == 1)
            {
                if($params[0] == 1)
                    return 6;
                elseif($params[0] == 2)
                    return 5;
                else
                    return 3;
            }
            elseif(count($params) == 2)
            {
                if(($params[0] == 1) && $params[1] == 2)
                    return 4;
                elseif(($params[0] == 1) && $params[1] == 4)
                    return 2;
                else
                    return 1;
            }
        }else{
            return $params;
        }
    }


    public static function makeAvailable($availables)
    {
        if(is_array($availables))
        {
            if(count($availables) == 2)
                return 0;
            else{
                if($availables[0] == 1)
                    return 1;
                else
                    return 2;
            }
        }else
            return 3;
    }

    public static function createIsTemplate($isTemplates)
    {
        $isTemplate = 0;
        if(is_array($isTemplates))
        {

            foreach($isTemplates as $item)
            {
                if(self::myRight() == AppConstant::ADMIN_RIGHT)
                {
                    if($item == 1)
                    {
                        $isTemplate += 1;
                    }
                    if($item == 4)
                    {
                        $isTemplate += 4;
                    }
                }
                if(self::myRight() >= AppConstant::GROUP_ADMIN_RIGHT)
                {
                    if($item == 2)
                    {
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
        if($studentQuickPick)
        {
            $studentTopBar = "";
            foreach($studentQuickPick as $key => $item)
            {
                if($studentTopBar == "")
                    $studentTopBar .= $item;
                else
                    $studentTopBar .= ','.$item;
            }
        }

        if($instructorQuickPick)
        {
            $instructorTopBar = "";
            foreach($instructorQuickPick as $key => $item)
            {
                if($instructorTopBar == "")
                    $instructorTopBar .= $item;
                else
                    $instructorTopBar .= ','.$item;
            }
        }
        $quickPickTopBar = isset($quickPickBar) ? $quickPickBar : 0;
        $topbar = $studentTopBar.'|'.$instructorTopBar.'|'.$quickPickTopBar;
        return $topbar;
    }

    public static function sendMail($subject, $message, $to){
        $email = Yii::$app->mailer->compose();
        $email->setTo($to)
            ->setSubject($subject)
            ->setHtmlBody($message)
            ->send();
    }

    public static function getChallenge(){
        return base64_encode(microtime() . rand(0, 9999));
    }


    public static function getRight($right)
    {
        $returnRight = "";
        switch($right)
        {
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
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$endTime, $tmatches);
        if (count($tmatches)==0) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/',$endTime, $tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = 0;
        }
        $tmatches[1] = $tmatches[1]%12;
        if($tmatches[3]=="pm") {$tmatches[1]+=12; }
        $deftime = $tmatches[1]*60 + $tmatches[2];

        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$startTime, $tmatches);
        if (count($tmatches)==0) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/',$startTime, $tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = 0;
        }
        $tmatches[1] = $tmatches[1]%12;
        if($tmatches[3]=="pm") {$tmatches[1]+=12; }
        $deftime += 10000*($tmatches[1]*60 + $tmatches[2]);

        return $deftime;
    }


    public static function tzdate($string,$time)
    {
        global $tzoffset, $tzname;
        if ($tzname != '')
        {
            return date($string, $time);
        } else
        {
            $serveroffset = date('Z') + $tzoffset*60;
            return date($string, $time-$serveroffset);
        }
    }
    public static function formatDate($date)
    {
        return AppUtility::tzdate("D n/j/y, g:i a",$date);
    }

    public static function calculateTimeToDisplay($deftime)
    {
        $defetime = $deftime%10000;
        $hr = floor($defetime/60)%12;
        $min = $defetime%60;
        $am = ($defetime<12*60)?'am':'pm';
        $deftimedisp = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
        if ($deftime>10000) {
            $defstime = floor($deftime/10000);
            $hr = floor($defstime/60)%12;
            $min = $defstime%60;
            $am = ($defstime<12*60)?'am':'pm';
            $defstimedisp = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
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
    public static function parsedatetime($date, $time) {
        global $tzoffset, $tzname;
        preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$date,$dmatches);
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$time,$tmatches);
        if (count($tmatches)==0) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/',$time,$tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = 0;
        }
        $tmatches[1] = $tmatches[1]%12;
        if($tmatches[3]=="pm") {$tmatches[1]+=12; }

        if ($tzname=='') {
            $serveroffset = date('Z')/60 + $tzoffset;
            $tmatches[2] += $serveroffset;
        }
        return mktime($tmatches[1],$tmatches[2],0,$dmatches[1],$dmatches[2],$dmatches[3]);
    }

//    Displays only time
    public static function parsetime($time) {
        global $tzoffset, $tzname;
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$time,$tmatches);
        if (count($tmatches)==0) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/',$time,$tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = 0;
        }
        $tmatches[1] = $tmatches[1]%12;
        if($tmatches[3]=="pm") {$tmatches[1]+=12; }

        if ($tzname=='') {
            $serveroffset = date('Z')/60 + $tzoffset;
            $tmatches[2] += $serveroffset;
        }
        return mktime($tmatches[1],$tmatches[2],0);
    }

    public static function myRight()
    {
        return Yii::$app->user->identity->rights;
    }

    /*Show Calender*/
   public static function showCalendar($refpage) {

        global $imasroot,$cid,$userid,$teacherid,$previewshift,$latepasses,$urlmode, $latepasshrs, $myrights, $tzoffset, $tzname, $havecalcedviewedassess, $viewedassess;

        $now = time();
        if ($previewshift!=-1) {
            $now = $now + $previewshift;
        }
        if (!isset($_COOKIE['calstart'.$cid]) || $_COOKIE['calstart'.$cid] == 0) {
            $today = $now;
        } else {
            $today = $_COOKIE['calstart'.$cid];
        }

        if (isset($_GET['calpageshift'])) {
            $pageshift = $_GET['calpageshift'];
        } else {
            $pageshift = 0;
        }
        if (!isset($_COOKIE['callength'.$cid])) {
            $callength = 4;
        } else {
            $callength = $_COOKIE['callength'.$cid];
        }

        $today = $today + $pageshift*7*$callength*24*60*60;

        $dayofweek = tzdate('w',$today);
        $curmonum = tzdate('n',$today);
        $dayofmo = tzdate('j',$today);
        $curyr = tzdate('Y',$today);
        if ($tzname=='') {
            $serveroffset = date('Z') + $tzoffset*60;
        } else {
            $serveroffset = 0; //don't need this if user's timezone has been set
        }
        $midtoday = mktime(12,0,0,$curmonum,$dayofmo,$curyr)+$serveroffset;


        $hdrs = array();
        $ids = array();

        $lastmo = '';
        for ($i=0;$i<7*$callength;$i++) {
            $row = floor($i/7);
            $col = $i%7;

            list($thismo,$thisday,$thismonum,$datestr) = explode('|',tzdate('M|j|n|l F j, Y',$midtoday - ($dayofweek - $i)*24*60*60));
            if ($thismo==$lastmo) {
                $hdrs[$row][$col] = $thisday;
            } else {
                $hdrs[$row][$col] = "$thismo $thisday";
                $lastmo = $thismo;
            }
            $ids[$row][$col] = "$thismonum-$thisday";

            $dates[$ids[$row][$col]] = $datestr;
        }
    }

    function basicshowq($qn,$seqinactive=false,$colors=array()) {

        global $showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores;
        $qshowansduring = ($showansduring && $qi[$questions[$qn]]['showans']=='0');
        $qshowansafterlast = (($showansafterlast && $qi[$questions[$qn]]['showans']=='0') || $qi[$questions[$qn]]['showans']=='F' || $qi[$questions[$qn]]['showans']=='J');


        if (canimprove($qn)) {
            if ($qshowansduring && $attempts[$qn]>=$testsettings['showans']) {$showa = true;} else {$showa=false;}
        } else {
            $showa = (($qshowansduring || $qshowansafterlast) && $showeachscore);
        }

        $regen = ((($regenonreattempt && $qi[$questions[$qn]]['regen']==0) || $qi[$questions[$qn]]['regen']==1)&&amreattempting($qn));
        $thisshowhints = ($qi[$questions[$qn]]['showhints']==2 || ($qi[$questions[$qn]]['showhints']==0 && $showhints));
        if (!$noraw && $showeachscore) { //&& $GLOBALS['questionmanualgrade'] != true) {
            //$colors = scorestocolors($rawscores[$qn], '', $qi[$questions[$qn]]['answeights'], $noraw);
            if (strpos($rawscores[$qn],'~')!==false) {
                $colors = explode('~',$rawscores[$qn]);
            } else {
                $colors = array($rawscores[$qn]);
            }
        }
        if (!$seqinactive) {

            displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],$showa,$thisshowhints,$attempts[$qn],false,$regen,$seqinactive,$colors);
        } else {
            //print_r('hhh'); die;
            displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],$showa,false,$attempts[$qn],false,$regen,$seqinactive,$colors);
        }
    }


}
