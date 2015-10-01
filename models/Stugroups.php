<?php

namespace app\models;

use app\components\AppUtility;
use Yii;
use app\models\_base\BaseImasStugroups;
use yii\db\Query;

class Stugroups extends BaseImasStugroups
{
    public static function findByCourseId($courseId)
    {
        $query = new Query();
        $query->select(['imas_stugroups.id'])
            ->from('imas_stugroups')
            ->join('INNER JOIN',
                'imas_stugroupset',
                'imas_stugroups.groupsetid=imas_stugroupset.id'
            )
            ->where('imas_stugroupset.courseid= :groupsetid', [':groupsetid' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function findByGrpSetId($copyGrpSet)
    {
        return Stugroups::find()->where(['groupsetid' => $copyGrpSet])->one();
    }

    public function insertStuGrpData($stuGroupName, $NewGrpSetId)
    {
        $this->name = $stuGroupName;
        $this->groupsetid = $NewGrpSetId;
        $this->save();
        return $this->id;

    }

    public static function findByGrpSetIdToDlt($deleteGrpSet)
    {
        $query = new Query();
        $query->select(['id'])
            ->from('imas_stugroups')
            ->where('groupsetid= :groupsetid', [':groupsetid' => $deleteGrpSet]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function deleteGrp($grpId)
    {
        $query = Stugroups::find()->where(['id' => $grpId])->all();
        if ($query) {
            foreach ($query as $data) {
                $data->delete();
            }
        }

    }

    public static function findByGrpSetIdForCopy($copyGrpSet)
    {
        $query = new Query();
        $query->select(['id', 'name'])
            ->from('imas_stugroups')
            ->where('groupsetid= :groupsetid', [':groupsetid' => $copyGrpSet]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function findByGrpSetIdToManageSet($grpSetId)
    {
        $query = new Query();
        $query->select(['id', 'name'])
            ->from('imas_stugroups')
            ->where('groupsetid= :groupsetid', [':groupsetid' => $grpSetId]);
        $query->orderBy('id');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;

    }

    public static function getById($renameGrp)
    {
        return Stugroups::find()->where(['id' => $renameGrp])->one();

    }

    public static function renameGrpName($renameGrp, $grpName)
    {
        $query = Stugroups::find()->where(['id' => $renameGrp])->one();
        if ($query) {
            $query->name = $grpName;
            $query->save();
        }
    }

    public function insertStuGrpName($grpSetId, $newGrpName)
    {
        $this->groupsetid = $grpSetId;
        $this->name = $newGrpName;
        $this->save();
        return $this->id;
    }

    public static function getAllIdName()
    {

        $query = new Query();
        $query->select(['id', 'name'])
            ->from('imas_stugroups');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getByGrpSetId($groupsetId)
    {
        return Stugroups::find()->select('id')->where(['groupsetid' => $groupsetId])->all();
    }

    public static function deleteByStudGrpId($groupsetId)
    {
        $courseData = Stugroups::findOne(['groupsetid', $groupsetId]);
        if ($courseData) {
            $courseData->delete();
        }
    }

    public static function getStuGrpId($userId, $groupSetId){
        $query = 'SELECT i_sg.id FROM imas_stugroups as i_sg JOIN imas_stugroupmembers as i_sgm ON i_sg.id=i_sgm.stugroupid ';
        $query .= "WHERE i_sgm.userid='$userId' AND i_sg.groupsetid= $groupSetId";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getUserIdStuGrpAndMembers($grpSetId)
    {
        $data = Yii::$app->db->createCommand("SELECT i_sgm.userid FROM imas_stugroups as i_sg JOIN imas_stugroupmembers as i_sgm ON i_sg.id=i_sgm.stugroupid  WHERE i_sg.groupsetid= :grpSetId ");
        $data->bindValue('grpSetId', $grpSetId);
        $query = $data->queryAll();
        return $query;
    }
    public static function getStuGrpDataForGradebook($userId,$grpSetId)
    {
        $query = 'SELECT i_sg.id FROM imas_stugroups as i_sg JOIN imas_stugroupmembers as i_sgm ON i_sg.id=i_sgm.stugroupid  WHERE i_sgm.userid='.$userId.' AND i_sg.groupsetid='.$grpSetId;
        $data = Yii::$app->db->createCommand($query);
        $data->bindValue('grpSetId', $grpSetId);
        $data->bindValue('i_sgm.userid', $userId);
        $query = $data->queryOne();
        return $query;
    }
    public static function getByGrpSetIdAndName($groupsetId)
    {
        return Stugroups::find()->select(['id','name'])->where(['groupsetid' => $groupsetId])->orderBy('id')->all();
    }

    public static function getByGrpSetOrderByName($groupsetId)
    {
        return Stugroups::find()->select(['id','name'])->where(['groupsetid' => $groupsetId])->orderBy('name')->all();
    }

    public static function getByName($groupId)
    {
        return Stugroups::find()->select('name')->where(['id' => $groupId])->all();
    }
}