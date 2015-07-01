<?php

namespace app\models;

use Yii;
use yii\db\Exception;
use app\components\AppUtility;
use app\components\AppConstant;
use app\models\_base\BaseImasLoginLog;
use yii\db\Query;

class LoginLog extends BaseImasLoginLog
{
    public static function getByCourseIdAndUserId($courseId,$userId,$orderBy,$sortBy){
         return  LoginLog::find()->where(['courseid' => $courseId, 'userid' => $userId])->orderBy([$orderBy=>$sortBy])->all();
   }
    public  static function findLoginCount($courseId){
        $query = new Query();
        $query->select(['userid', 'count(*) as count'])
            ->from('imas_login_log')
            ->where(['courseid' => $courseId])
            ->groupBy('userid');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
}
