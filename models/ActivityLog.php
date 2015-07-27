<?php

namespace app\models;

use app\models\_base\BaseImasContentTrack;
use Yii;
use yii\db\Exception;
use app\components\AppUtility;
use app\components\AppConstant;
use app\models\_base\BaseImasLoginLog;

class ActivityLog extends BaseImasContentTrack
{
    public static function getByCourseIdAndUserId($courseId,$userId,$orderBy,$sortBy){
        return  ActivityLog::find()->select(['type','typeid','viewtime','info'])->where(['courseid' => $courseId, 'userid' => $userId])->orderBy([$orderBy=>$sortBy])->all();
    }
}