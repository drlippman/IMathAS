<?php

namespace app\models;

use Yii;
use yii\db\Exception;
use app\components\AppUtility;
use app\components\AppConstant;
use app\models\_base\BaseImasLoginLog;

class LoginLog extends BaseImasLoginLog
{
    public static function getByCourseIdAndUserId($courseId,$userId,$orderBy,$sortBy){
         return  LoginLog::find()->where(['courseid' => $courseId, 'userid' => $userId])->orderBy([$orderBy=>$sortBy])->all();
   }
}
