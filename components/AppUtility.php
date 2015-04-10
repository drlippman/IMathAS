<?php

namespace app\components;


use Yii;
use yii\base\Component;

class AppUtility extends Component {

	public static function dump($data){
		echo "<pre>";
		print_r($data);
		echo "</pre>";
		die;
	}

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

}

?>