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
            ->groupBy('imas_content_track.typeid')
            -> where('imas_students.courseid = :courseId')
            -> andWhere('imas_content_track.courseid = :courseId')
            -> andWhere(['imas_content_track.type' => 'extref'])
            -> andWhere(['IN','imas_content_track.typeid',$qlist]);
            ($secfilter != AppConstant::NUMERIC_NEGATIVE_ONE) ? $query->andWhere('imas_students.section = :secfilter') : $query->andWhere(':secfilter = :secfilter');
        $command = $query->createCommand()->bindValues([':courseId' => $courseId,':secfilter' => $secfilter]);
        $data = $command->queryAll();
        return $data;
    }

    public static function getDistinctUserIdUsingCourseIdAndQuestionId($courseId,$questionId,$secfilter)
    {
        $query = new Query();
        $query->select('DISTINCT ict.userid')->from('imas_content_track AS ict')
            ->join('INNER JOIN','imas_students AS ims','ict.userid=ims.userid')->where('ims.courseid= :courseId')
            ->andWhere('ict.courseid= :courseId')->andWhere(['ict.type' => 'extref'])->andWhere('ict.typeid= :questionId')->andWhere(['ims.locked' => 0]);
        if ($secfilter!=AppConstant::NUMERIC_NEGATIVE_ONE)
        {
        $query->andWhere('ims.section = :secfilter',[':secfilter' => $secfilter]);
        }
        $data = \Yii::$app->db->createCommand($query);
        $data->bindValue(':courseId',$courseId);
        $data->bindValue(':questionId',$questionId);
        return $data->queryAll();
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
        return self::find()->select(['typeid'])->where(['courseid' => $courseId])->andWhere(['userid' => $userId])->andWhere(['type' => 'gbviewasid'])->all();
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
        return self::find()->select(['userid','type','info'])->where(['courseid' => $courseId])->andWhere(['type' => 'inlinetext'])->andWhere(['typeid' => $typeId])->all();
    }

    public function getDataForLink($courseId,$typeId)
    {
        $temp = 'linkedsum,linkedlink,linkedintext,linkedvviacal';
        $tempArray[] = explode(',',$temp);
        return self::find()->select('userid,type,info')->where(['courseid' => $courseId])->andWhere(['IN', 'type', $tempArray[0]])->andWhere(['typeid' => $typeId])->all();
    }

    public function getDataForAssessment($courseId,$typeId)
    {
        $temp = 'assessintro,assessum,assess';
        $tempArray[] = explode(',',$temp);
        return self::find()->select('userid,type,info')->where(['courseid' => $courseId])->andWhere(['IN', 'type', $tempArray[0]])->andWhere(['typeid' => $typeId])->all();
    }

    public function getDataForWiki($courseId,$typeId)
    {
        $temp = 'wiki,wikiintext';
        $tempArray[] = explode(',',$temp);
        return self::find()->select('userid,type,info')->where(['courseid' => $courseId])->andWhere(['IN', 'type', $tempArray[0]])->andWhere(['typeid' => $typeId])->all();
    }

    public function getDataForForum($courseId,$typeId)
    {
        $temp = 'forumpost,forumreply';
        $tempArray[] = explode(',',$temp);
        return self::find()->select('userid,type,info')->where(['courseid' => $courseId])->andWhere(['IN', 'type', $tempArray[0]])->andWhere(['info' => $typeId])->all();
    }

    public static function getStatsData($courseId)
    {
        $query = "SELECT DISTINCT(CONCAT(SUBSTRING(type,1,1),typeid)) FROM imas_content_track WHERE courseid= :courseId AND type IN ('inlinetext','linkedsum','linkedlink','linkedintext','linkedviacal','assessintro','assess','assesssum','wiki','wikiintext') ";
        $data = \Yii::$app->db->createCommand($query);
        $data->bindValue('courseId',$courseId);
        return $data->queryAll();
    }

    public static function getId($coursId, $userId,$type, $tId)
    {
        return ContentTrack::find()->select('id')->where(['courseid' => $coursId, 'userid' => $userId, 'linkedlink' => $type, 'typeid' => $tId])->orderBy(['viewtime'=> SORT_DESC])->limit(1);
    }
}
