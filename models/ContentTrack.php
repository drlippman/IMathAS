<?php
namespace app\models;
use app\components\AppConstant;
use app\components\AppUtility;
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
           if($secfilter != AppConstant::NUMERIC_NEGATIVE_ONE){
               $query->andWhere(['imas_students.section' => $secfilter]);
           }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getDistinctUserIdUsingCourseIdAndQuestionId($courseId,$questionId,$secfilter)
    {
        $query = new Query();
        $query = "SELECT DISTINCT ict.userid FROM imas_content_track AS ict JOIN imas_students AS ims ON ict.userid=ims.userid WHERE ims.courseid='$courseId' AND ict.courseid='$courseId' AND ict.type='extref' AND ict.typeid='$questionId' AND ims.locked=0 ";
        if ($secfilter!=AppConstant::NUMERIC_NEGATIVE_ONE)
        {
        $query .= " AND ims.section='$secfilter' ";
        }
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = ContentTrack::findOne(['courseid',$courseId]);
        if($courseData){
            $courseData->delete();
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

    public static function getByTypeId($courseId, $userId)
    {
        $query = new Query();
        $query->select(['typeid'])
            ->from(['imas_content_track'])
            ->where(['courseid' => $courseId]);
        $query->andWhere(['userid' => $userId]);
        $query->andWhere(['type' => 'gbviewasid']);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function createTrack($params){
        $data = AppUtility::removeEmptyAttributes($params);
        if($data){
            $this->attributes = $data;
            $this->save();
        }
    }

    public static function getTypeId($courseId, $userId,$type)
    {
        return ContentTrack::find()->select('typeid')->where(['courseid' => $courseId])->andWhere(['userid' => $userId])->andWhere(['type' => $type])->all();
    }

    public function insertFromGradebook($userId,$courseId,$type,$typeId,$time)
    {
        $this->userid = $userId;
        $this->courseid = $courseId;
        $this->type = $type;
        $this->typeid = $typeId;
        $this->viewtime = $time;
        $this->save();
    }

    public static function getDataByCourseId($courseId, $typeId)
    {
        $query = new Query();
        $query->select(['userid','type','info'])
            ->from(['imas_content_track'])
            ->where(['courseid' => $courseId]);
        $query->andWhere(['type' => 'inlinetext']);
        $query->andWhere(['typeid' => $typeId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function getDataForLink($courseId,$typeId)
    {
      $query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$courseId' AND type IN ('linkedsum','linkedlink','linkedintext','linkedvviacal') AND typeid='$typeId'";
      return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public function getDataForAssessment($courseId,$typeId)
    {
        $query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$courseId' AND type IN ('assessintro','assessum','assess') AND typeid='$typeId'";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public function getDataForWiki($courseId,$typeId)
    {
        $query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$courseId' AND type IN ('wiki','wikiintext') AND typeid='$typeId'";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public function getDataForForum($courseId,$typeId)
    {
        $query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$courseId' AND type IN ('forumpost','forumreply') AND info='$typeId'";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getStatsData($courseId)
    {
        $query = "SELECT DISTINCT(CONCAT(SUBSTRING(type,1,1),typeid)) FROM imas_content_track WHERE courseid='$courseId' AND type IN ('inlinetext','linkedsum','linkedlink','linkedintext','linkedviacal','assessintro','assess','assesssum','wiki','wikiintext') ";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }
}
