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
} 