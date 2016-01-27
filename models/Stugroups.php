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
        return Stugroups::find()->select(['id'])->where(['groupsetid' => $deleteGrpSet])->all();
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
        return Stugroups::find()->select(['id', 'name'])->where(['groupsetid' =>  $copyGrpSet])->all();
    }

    public static function findByGrpSetIdToManageSet($grpSetId)
    {

        return Stugroups::find()->select(['id', 'name'])->where(['groupsetid' =>  $grpSetId])->orderBy('id')->all();
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

    public static function getStuGrpId($userId, $groupSetId)
    {
        $query = new Query();
        $query->select('i_sg.id,i_sg.name')->from('imas_stugroups as i_sg')->join('INNER JOIN','imas_stugroupmembers as i_sgm','i_sg.id=i_sgm.stugroupid')
            ->where('i_sgm.userid = :userId')->andWhere('i_sg.groupsetid= :grpSetId');
        $command = $query->createCommand();
        $data = $command->bindValue(':userId',$userId)->bindValue('grpSetId', $groupSetId)->queryAll();
        return $data;
    }

    public static function getUserIdStuGrpAndMembers($grpSetId)
    {
        $query = new Query();
         $query->select('i_sgm.userid')->from('imas_stugroups as i_sg')->join('INNER JOIN','imas_stugroupmembers as i_sgm','i_sg.id=i_sgm.stugroupid')
            ->where('i_sg.groupsetid= :grpSetId');
        $command = $query->createCommand();
        $data = $command->bindValue('grpSetId', $grpSetId)->queryAll();
        return $data;
    }
    public static function getStuGrpDataForGradebook($userId,$grpSetId)
    {
        $query = new Query();
        $query->select('i_sg.id')->from('imas_stugroups as i_sg')->join('INNER JOIN',
            'imas_stugroupmembers as i_sgm',
            'i_sg.id=i_sgm.stugroupid')
            ->where('i_sgm.userid = :userId')->andWhere('i_sg.groupsetid= :grpSetId');
        $command = $query->createCommand();

        return $command->bindValue('grpSetId', $grpSetId)->bindValue('userId', $userId)->queryOne();
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