<?php

namespace app\models;
use app\components\AppConstant;
use Yii;

use app\components\AppUtility;
use app\models\_base\BaseImasRubrics;
use yii\db\Query;

class Rubrics extends BaseImasRubrics
{
    public static function getByUserId($usrId)
    {
        $rubricsData = Rubrics::findAll(['ownerid' => $usrId]);
        return $rubricsData;
    }

    public function createNewEntry($params,$currentUserId,$rubricTextDataArray)
    {
        $this->ownerid = $currentUserId;
        $this->groupid = ($params['ShareWithGroup']-AppConstant::NUMERIC_ONE);
        if($params['AddRubricForm']['Name']){
            $this->name =  $params['AddRubricForm']['Name'];
        }else{
            $this->name ='null';
        }
        $this->rubrictype = $params['rubtype'];
        $this->rubric = $rubricTextDataArray;
        $this->save();
       return $this->id;
    }
    public static function getByUserIdAndRubricId($currentUserId,$rubricId)
    {
        $rubricsData = Rubrics::findone(['ownerid' => $currentUserId,'id' => $rubricId]);
        return $rubricsData;
    }

    public static function getByUserIdAndGroupId($userId,$groupid,$list)
    {
        $rubricsData = Rubrics::find()->where(['in','id',$list])->andwhere('ownerid = :ownerid',[':ownerid' => $userId])
        ->orWhere( 'groupid = :groupid',[':groupid' => $groupid])->all();
        return $rubricsData;
    }

    public static function getByUserIdAndGroupIdAndList($userId,$groupid,$list)
    {
        $query = Yii::$app->db->createCommand("SELECT id FROM imas_rubrics WHERE id IN ($list) AND NOT (ownerid='$userId' OR groupid='$groupid')")->queryAll();
        return $query;
    }

    public static function getById($id)
    {
        $query = new Query();
        $query ->select(['name','rubrictype','rubric'])
                ->from('imas_rubrics')
                ->where(['id' => $id]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;

    }

    public static function getByUserIdAndGroupIdAndRubric($rubric,$userid,$groupid){
        $rubricsData = Rubrics::find()->where(['rubric'=> $rubric])->andWhere('ownerid = :ownerid',[':ownerid' => $userid] or ['groupid = :groupid',':groupid' => $groupid])->all();
        return $rubricsData;
    }

    public static function updateRubrics($params, $currentUserId, $rubricTextDataArray,$rubricId)
    {
        $rubricsData = Rubrics::find()->where(['ownerid' => $currentUserId])->Andwhere(['id' => $rubricId])->one();
        $ShareWithGroup = -1;
        if(isset($params['ShareWithGroup'])){
            $ShareWithGroup = 0;
        }
        $rubricsData ->groupid = $ShareWithGroup;
        if($params['AddRubricForm']['Name']){
            $rubricsData ->name =  $params['AddRubricForm']['Name'];
        }else{
            $rubricsData ->name ='null';
        }
        $rubricsData ->rubrictype = $params['rubtype'];
        $rubricsData ->rubric = $rubricTextDataArray;
        $rubricsData ->save();
    }

    public static function getIdAndName($userId, $groupId){
        $rubricsData = Rubrics::find()->where('ownerid = :ownerid',[':ownerid' => $userId])
            ->orWhere( 'groupid = :groupid',[':groupid' => $groupId])->orderBy('name')->all();
        return $rubricsData;
    }
}
