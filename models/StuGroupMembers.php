<?php

namespace app\models;

use Yii;
use app\models\_base\BaseImasStugroupmembers;
use yii\db\Query;

class StuGroupMembers extends BaseImasStugroupmembers
{

    public static function deleteMemberFromCourse($toUnEnroll, $stuGroups)
    {
        $query = StuGroupMembers::find()->where(['IN', 'stugroupid', $stuGroups])->andWhere(['IN', 'userid', $toUnEnroll])->all();
        if ($query) {
            foreach ($query as $object) {
                $object->delete();
            }
        }
    }

    public static function findByStuGroupId($groupId)
    {
        return StuGroupMembers::find()->select('userid')->where(['stugroupid'  => $groupId])->all();
    }

    public function insertStuGrpMemberData($userId, $newStuGrpId)
    {
        $this->userid = $userId;
        $this->stugroupid = $newStuGrpId;
        $this->save();
    }

    public static function deleteStuGroupMembers($grpId)
    {
        $query = StuGroupMembers::find()->where(['stugroupid' => $grpId])->all();
        if ($query) {
            foreach ($query as $data) {
                $data->delete();
            }
        }
    }

    public static function manageGrpSet($grpIds)
    {
        return StuGroupMembers::find()->select('stugroupid,userid')->where(['IN','stugroupid',$grpIds])->all();
    }

    public static function alreadyStuAdded($grpSetId, $stuList)
    {
        //TODO: fix below query
        $query = new Query();
        $query->select(['i_sgm.userid'])
            ->from(['imas_stugroupmembers as i_sgm'])
            ->join('INNER JOIN','imas_stugroups as i_sg','i_sgm.stugroupid=i_sg.id')
            ->where('i_sg.groupsetid= :uid');
        $query->andWhere(['IN','i_sgm.userid',$stuList]);
        $command = $query->createCommand();
        $data = $command->bindValue('uid', $grpSetId)->queryAll();
        return $data;
    }

    public static function removeGrpMember($uid, $grpId)
    {
        $query = StuGroupMembers::find()->where(['stugroupid' => $grpId])->andWhere(['userid' => $uid])->all();
        if ($query) {
            foreach ($query as $data) {
                $data->delete();
            }
        }
    }

    public static function deleteByStudGrpId($stugroupid)
    {
        $courseData = StuGroupMembers::findOne(['stugroupid', $stugroupid]);
        if ($courseData) {
            $courseData->delete();
        }
    }

    public static function getUserId($stugroupId, $userId){
        return StuGroupMembers::find()->select('userid')->where(['stugroupid' => $stugroupId])->andWhere(['<>','userid',$userId])->all();
    }

    public static function getByStuGrpAndUser($grpId)
    {
        $query = new Query();
        $query->select(['imas_users.id,imas_users.FirstName,imas_users.LastName'])
            ->from(['imas_users'])
            ->join('INNER JOIN','imas_stugroupmembers','imas_users.id=imas_stugroupmembers.userid')
            ->where('imas_stugroupmembers.stugroupid = :grpId')->orderBy('imas_users.LastName,imas_users.FirstName');
        $command = $query->createCommand();
        $data = $command->bindValue(':grpId', $grpId)->queryAll();
        return $data;
    }

    public static function getStudAndUserData($groupId)
    {
        $query = new Query();
        $query->select(['i_u.LastName','i_u.FirstName'])
            ->from(['imas_stugroupmembers AS i_sg','imas_users AS i_u'])
            ->where(['i_u.id=i_sg.userid']);
        $query->andWhere('i_sg.stugroupid= :groupId');
        $command = $query->createCommand()->bindValue('groupId', $groupId);
        $data = $command->queryAll();
        return $data;
    }

    public static function getByStuGrpWithUser($groupList)
    {
        //TODO: fix below query
        $query = new Query();
        $query->select(['isg.stugroupid,iu.LastName,iu.FirstName'])
            ->from(['imas_stugroupmembers AS isg'])
            ->join('INNER JOIN','imas_users as iu','isg.userid=iu.id')
            ->where(['IN','isg.stugroupid',$groupList]);
        $query->orderBy(['iu.LastName,iu.FirstName']);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getStudGroupAndStudGroupMemberData($userId,$groupSetId)
    {
        $query = new Query();
        $query->select('i_sg.id')
            ->from('imas_stugroups AS i_sg')
            ->join('INNER JOIN','imas_stugroupmembers as i_sgm','i_sgm.stugroupid=i_sg.id')
            ->where('i_sgm.userid =:userId');
        $query->andWhere('i_sg.groupsetid= :groupSetId');
        $command = $query->createCommand()->bindValues(['userId' => $userId, 'groupSetId' => $groupSetId]);
        $data = $command->queryAll();
        return $data;
    }

    public static function getById($groupId, $userId)
    {
        return StuGroupMembers::find()->select('id')->where(['stugroupid'  => $groupId, 'userid' => $userId])->all();
    }
}
