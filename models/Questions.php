<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 11/5/15
 * Time: 12:50 PM
 */

namespace app\models;


use app\components\AppConstant;
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
        if($assessment){
            $assessment->points = 9999;
            $assessment->attempts = 9999;
            $assessment->penalty = 9999;
            $assessment->regen = 0;
            $assessment->showans = 0;
            $assessment->save();
        }
    }

    public static function deleteByAssessmentId($assessmentId){
        $questionData = Questions::findAll(['assessmentid' => $assessmentId]);
        if($questionData){
            foreach ($questionData as $singleQuestion){
                $singleQuestion->delete();
            }
        }
    }
    public static function setRubric($id, $data){
        $rubricData = Questions::findOne(['id' => $id]);
        if ($rubricData){
            $rubricData->rubric = $data;
            $rubricData->save();
        }
    }

    public static function getByItemOrder($itemorder){
        $questionDataArray = array();
        foreach($itemorder as $item){
            $questionData = Questions::findOne(['id',$item]);
            array_push($questionDataArray,$questionData);
        }
        return $questionDataArray;
    }

    public function addQuestions($params){
        $this->assessmentid = $params['assessmentid'];
        $this->questionsetid = $params['questionsetid'];
        $this->points = $params['points'];
        $this->attempts = $params['attempts'];
        $this->penalty = $params['penalty'];
        $this->category = $params['category'];
        $this->regen = $params['regen'];
        $this->showans = $params['showans'];
        $this->showhints = $params['showhints'];
        $this->save();
        return $this->id;
    }
    public static function findQuestionForGradebook($assessmentId)
    {
        $query = Questions::find()->select('points,id')->where(['assessmentid' =>$assessmentId])->all();
        return $query;
    }
    public static function updateWithdrawn($assesses)
    {
        $query = Questions::find()->where(['IN', 'assessmentid', $assesses])->all();
        if($query){
            foreach($query as $object){
                $object->withdrawn = AppConstant::NUMERIC_ZERO ;
                $object->save();
            }
        }
    }
} 