<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 11/5/15
 * Time: 12:50 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasQuestions;

class Questions extends BaseImasQuestions
{
    public static function getByAssessmentId($id)
    {
        return static::findAll(['assessmentid' => $id]);
    }

    public static function getById($id)
    {
        return static::findAll(['id' => $id]);
    }

   public static function findQuestionForOuctome($dataId)
   {
        $query = Questions::find()->select('points,id,category')->where(['assessmentid' =>$dataId])->all();
       return $query;

   }

    public static function setQuestionByAssessmentId($assessmentId){
        $assessment = Questions::findOne(['assessmentid' => $assessmentId]);
        $assessment->points = 9999;
        $assessment->attempts = 9999;
        $assessment->penalty = 9999;
        $assessment->regen = 0;
        $assessment->showans = 0;
        $assessment->save();
    }

    public static function deleteByAssessmentId($assessmentId){
        $questionData = Questions::findAll(['assessmentid' => $assessmentId]);
        if($questionData){
            foreach ($questionData as $singleQuestion){
                $singleQuestion->delete();
            }
        }
    }
} 