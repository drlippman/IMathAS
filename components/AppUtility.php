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

}

?>