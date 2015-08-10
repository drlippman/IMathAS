<?php

/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 5/6/15
 * Time: 7:13 PM
 */

namespace app\models;
use app\components\AppConstant;
use Yii;

use app\components\AppUtility;
use app\models\_base\BaseImasGrades;
use yii\db\Query;

class Grades extends BaseImasGrades
{
    public function createGradesByUserId($singal,$gbItemsId)
    {
        if($singal['gradeText'] || $singal['feedbackText'] || $singal['fromUploadFile'] == AppConstant::NUMERIC_ONE){
            $this->gradetypeid = $gbItemsId;
            $this->userid = $singal['studentId'];
            $this->score = $singal['gradeText'];
            $this->feedback = $singal['feedbackText'];
            $this->save();
        }
    }

    public static function GetOtherGrades($gradetypeselects, $limuser){
        $query = new Query();
        $query->select(['*'])
            ->from('imas_grades');
        foreach($gradetypeselects as $gradeSelect){
            $query->orWhere(["gradetype" => $gradeSelect['gradetype']])
                    ->andWhere(['IN', 'gradetypeid', $gradeSelect['gradetypeid']]);
        }
        if ($limuser > 0) {
            $query->andWhere(["userid" => $limuser]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function outcomeGrades($sel,$limuser)
    {
        $query = new Query();
        $query->select(['*'])
            ->from('imas_grades')
            ->where([$sel]);
        if ($limuser > 0)
        {
            $query->andWhere(["userid" => $limuser]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function deleteByGradeTypeId($linkId){
        $externalTool = 'exttool';
        $linkData = Grades::findAll(['gradetypeid'=> $linkId,'gradetype' => $externalTool]);
        if($linkData){
            foreach($linkData as $singleData){
                $singleData->delete();
            }
        }
    }

    public static function deleteGradesUsingType($gradeType, $tools, $toUnEnroll)
    {
        $query = Grades::find()->where(['gradetype' => $gradeType])->andWhere(['IN', 'gradetypeid', $tools])->andWhere(['IN', 'userid', $toUnEnroll])->all();
        if ($query) {
            foreach ($query as $grades) {
                $grades->delete();
            }
        }
    }
    public static function getByGradeTypeId($gbItemsId){
        return Grades::find('userid','score')->where(['gradetypeid' => $gbItemsId])->andWhere(['gradetype' => 'offline'])->all();
    }
    public function addGradeToStudent($cuserid,$gbItemsId,$feedback,$score){

                $this->gradetype = 'offline';
                $this->gradetypeid = $gbItemsId;
                $this->userid = $cuserid;
                $this->score = $score;
                $this->feedback = $feedback;
                $this->save();

    }
    public static function updateGradeToStudent($score,$feedback,$cuserid,$gbItemsId){

        $grade = Grades::find()->where(['userid' => $cuserid])->andWhere(['gradetypeid'=> $gbItemsId])->andWhere(['gradetype' => 'offline'])->one();
        if($grade){
            $grade->score = $score;
            $grade->feedback = $feedback;
            $grade->save();
        }
    }
    public static function deleteByGradeTypeIdAndGradeType($gradeId,$gradeType){
        $grades = Grades::find()->where(['gradetype' => $gradeType])->andWhere(['gradetypeid' => $gradeId])->all();
        if($grades){
            foreach($grades as $grade){
                $grade->delete();
            }
        }

    }
    public static function getByGradeTypeIdAndUserId($gbitemId,$grades)
    {
        return Grades::find('userid','score')->where(['gradetypeid' => $gbitemId])->andWhere(['gradetype' => 'offline'])->andWhere(['userid' => $grades])->all();
    }

}

