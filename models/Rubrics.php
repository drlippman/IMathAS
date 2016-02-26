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
        $query = Yii::$app->db->createCommand("SELECT id FROM imas_rubrics WHERE id IN ($list) AND NOT (ownerid= '$userId' OR groupid='$groupid')")->queryAll();
        return $query;
    }

    public static function getById($id)
    {
        return self::find()->select(['name','rubrictype','rubric','groupid','id'])->where(['id' => $id])->one();
    }

    public static function getByUserIdAndGroupIdAndRubric($rubric,$userid,$groupid){
        $rubricsData = Rubrics::find()->where(['rubric'=> $rubric])->andWhere('ownerid = :ownerid',[':ownerid' => $userid] or ['groupid = :groupid',':groupid' => $groupid])->all();
        return $rubricsData;
    }

    public static function updateRubrics($params,$rubgrp,$rubricstring, $rubricId)
    {

        $rubricsData = Rubrics::find()->where(['id' => $rubricId])->one();
        $rubricsData ->name = $params['rubname'];
        $rubricsData ->rubrictype = $params['rubtype'];
        $rubricsData ->groupid = $rubgrp;
        $rubricsData ->rubric = $rubricstring;
        $rubricsData ->save();
    }

    public static function getIdAndName($userId, $groupId){
        $rubricsData = Rubrics::find()->where('ownerid = :ownerid',[':ownerid' => $userId])
            ->orWhere( 'groupid = :groupid',[':groupid' => $groupId])->orderBy('name')->all();
        return $rubricsData;
    }

    public function insertInToRubric($currentUserId,$params,$rubgrp,$rubricstring)
    {
        $this->ownerid = $currentUserId;
        $this->name = $params['rubname'];
        $this->rubrictype = $params['rubtype'];
        $this->groupid = $rubgrp;
        $this->rubric = $rubricstring;
        $this->save();
    }

    public static function getByOwnerId($userId,$groupId)
    {
        return Rubrics::find()->select('id,name')->where(['ownerid' => $userId])->orWhere(['groupid' => $groupId])->orderBy('name')->all();
    }

    public static function rubricDataByAssessmentId($subquery)
    {
         return Rubrics::find()->select('id,rubrictype,rubric')->where(['IN','id',$subquery])->all();
    }

    public static function getRubricByQuestionId($questionId)
    {
        $query = new Query();
        $query->select('imas_rubrics.id,imas_rubrics.rubrictype,imas_rubrics.rubric')->from('imas_rubrics')->
        join('INNER JOIN','imas_questions','imas_rubrics.id=imas_questions.rubric')->where('imas_questions.id = :questionId');
        $command = $query->createCommand();
        $data = $command->bindValue(':questionId',$questionId)->queryAll();
        return $data;
    }

    public static function getByRubricId($rubric)
    {
        return Rubrics::find()->select('id,rubrictype,rubric')->where(['id',$rubric])->all();
    }
}
