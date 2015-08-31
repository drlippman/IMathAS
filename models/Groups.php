<?php

namespace app\models;
use app\components\AppConstant;
use app\models\_base\BaseImasGroups;
use Yii;

use app\components\AppUtility;
use yii\db\Query;

class Groups extends BaseImasGroups
{

 public static function getIdAndName() {
     $user = Groups::find()->orderBy('name')->all();
     return $user;
 }
}

