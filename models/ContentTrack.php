<?php
namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasCalitems;
use app\models\_base\BaseImasContentTrack;
use yii\db\Query;

class ContentTrack extends BaseImasContentTrack
{
    public static  function getByCourseIdUserIdAndType($courseId,$userId)
    {
        return ContentTrack::find()->where(['courseid' => $courseId])->andWhere(['userid' => $userId])->andWhere(['type' => 'gbviewasid'])->all();
    }
    public static function deleteUsingCourseAndUid($toUnEnroll, $courseId)
    {
        $query = ContentTrack::find()->where(['IN', 'userid', $toUnEnroll])->andWhere(['courseid' => $courseId])->all();
        if($query){
            foreach($query as $object){
                $object->delete();
            }
        }
    }

} 