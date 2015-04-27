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
        for ($i=0;$i<10;$i++) {
            $pass .= substr($chars,rand(0,61),1);
        }
        return $pass;
    }

    /**
     * This is a utility method to find out if we are supporting the old site.
     * Based on the value of this method a bunch of additional code would be executed to support the old site.
     * If we toggle the is_old_site_supported flag in the params.php file, this method would change the return value.
     * Also the default value (i.e. if is_old_site_supported is not specified in the params.php file), is true.
     * @return boolean
     */
    public static function isOldSiteSupported(){
        $is_old_site_supported = false;
        $is_old_site_supported_val = Yii::$app->params['is_old_site_supported'];
        if($is_old_site_supported_val){
            $is_old_site_supported = true;
        }
        return $is_old_site_supported;
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
 //       AppUtility::dump($newPassword);
        require_once("Password.php");
        if(password_verify($newPassword, $oldPassword)){
            return true;
        }
        return false;
//        return password_hash($password, PASSWORD_DEFAULT);
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
        $availablel = array();
        $toolset = array();
        $isTemplate = array();
        if (($course->available & 1) == 0) {
            array_push($availablel, 1);
        }
        if (($course->available & 2) == 0) {
            array_push($availablel, 2);
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

        if (($course->istemplate & 2) == 0) {
            array_push($isTemplate, 2);
        }
        if (($course->istemplate & 1) == 0) {
            array_push($isTemplate, 1);
        }
        if (($course->istemplate & 4) == 0) {
            array_push($isTemplate, 4);

        }

        return $ckeckList = array('availablel' => $availablel, 'toolset' => $toolset, 'isTemplate' => $isTemplate);
    }

}

?>