<?php
namespace app\models;


use app\components\AppConstant;
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

    public function insertForumData($currentUserId,$courseId,$forumId,$threadId,$threadIdOfPost,$type)
    {
        $now = time();
        $this->userid =  $currentUserId;
        $this->courseid = $courseId;
        if($type == AppConstant::NUMERIC_TWO)
        {
            $this->type = 'Forummod';
        }elseif($type == AppConstant::NUMERIC_ONE)
        {
            $this->type = 'Forumreply';
        }elseif($type == AppConstant::NUMERIC_ZERO)
        {
            $this->type = 'Forumpost';
        }
        $this->typeid = $threadId;
        $this->viewtime = $now;
        if($type == AppConstant::NUMERIC_ZERO)
        {
            $this->info = $forumId;
        }else
        {
            $this->info = $forumId.';'.$threadIdOfPost;
        }
        $this->save();
    }

} 