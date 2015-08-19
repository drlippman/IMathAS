<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 5/8/15
 * Time: 8:34 PM
 */

namespace app\models;

use Yii;
use yii\db\Exception;
use app\components\AppUtility;
use app\models\_base\BaseImasStugroupmembers;
use yii\db\Query;

class StuGroupMembers extends BaseImasStugroupmembers{

    public static function deleteMemberFromCourse($toUnEnroll, $stuGroups)
    {
        $query = StuGroupMembers::find()->where(['IN', 'stugroupid', $stuGroups])->andWhere(['IN', 'userid', $toUnEnroll])->all();
        if($query){
            foreach($query as $object){
                $object->delete();
            }
        }
    }

    public static function findByStuGroupId($groupId)
    {
        $query = new Query();
        $query ->select('userid')
                ->from('imas_stugroupmembers')
                ->where(['stugroupid' => $groupId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function insertStuGrpMemberData($userId,$newStuGrpId)
    {
        $this->userid = $userId;
        $this->stugroupid = $newStuGrpId;
        $this->save();
    }

    public static function deleteStuGroupMembers($grpId)
    {
        $query = StuGroupMembers::find()->where(['stugroupid' => $grpId])->all();
        if($query)
        {
            foreach($query as $data)
            {
                $data->delete();
            }
        }
    }
}