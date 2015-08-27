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
            $this->type = 'forummod';
        }elseif($type == AppConstant::NUMERIC_ONE)
        {
            $this->type = 'forumreply';
        }elseif($type == AppConstant::NUMERIC_ZERO)
        {
            $this->type = 'forumpost';
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

    public static function getCourseIdUsingStudentTableJoin($courseId,$qlist,$secfilter)
    {
        $query = new Query();
        $query -> select(['imas_content_track.typeid','imas_content_track.userid'])
            -> from('imas_content_track')
            ->join(	'INNER JOIN',
                'imas_students',
                'imas_content_track.userid=imas_students.userid'
            )
            -> distinct('imas_content_track.userid')
            ->groupBy(['imas_content_track.typeid'])
            -> where(['imas_students.courseid' => $courseId])
            -> andWhere(['imas_content_track.courseid' => $courseId])
            -> andWhere(['imas_content_track.type' => 'extref'])
            -> andWhere(['IN','imas_content_track.typeid',$qlist]);
           if($secfilter != -1){
               $query->andWhere(['imas_students.section' => $secfilter]);
           }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
} 