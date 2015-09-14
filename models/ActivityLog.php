<?php

namespace app\models;

use app\models\_base\BaseImasContentTrack;
use Yii;

class ActivityLog extends BaseImasContentTrack
{
    public static function getByCourseIdAndUserId($courseId,$userId,$orderBy,$sortBy){
        return  ActivityLog::find()->select(['type','typeid','viewtime','info'])->where(['courseid' => $courseId, 'userid' => $userId])->orderBy([$orderBy=>$sortBy])->all();
    }
}