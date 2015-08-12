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
            $questionData = Questions::findOne(['id'=>$item]);
            array_push($questionDataArray,$questionData);
        }
        return $questionDataArray;
    }

    public function addQuestions($params){
        $this->assessmentid = isset($params['assessmentid']) ? $params['assessmentid'] : null;
        $this->questionsetid = isset($params['questionsetid']) ? $params['questionsetid'] : null;;
        $this->points = isset($params['points']) ? $params['points'] : null;;
        $this->attempts = isset($params['attempts']) ? $params['attempts'] : null;;
        $this->penalty = isset($params['penalty']) ? $params['penalty'] : null;;
        $this->category = isset($params['category']) ? $params['category'] : null;;
        $this->regen = isset($params['regen']) ? $params['regen'] : null;;
        $this->showans = isset($params['showans']) ? $params['showans'] : null;;
        $this->showhints = isset($params['showhints']) ? $params['showhints'] : null;;
        $this->save();
        return $this->id;
    }
    public static function findQuestionForGradebook($assessmentId)
    {
        $query = Questions::find()->select('points,id')->where(['assessmentid' =>$assessmentId])->all();
        return $query;
    }
    public static function updateWithdrawn($assessmentId)
    {
        $query = Questions::find()->where(['IN', 'assessmentid', $assessmentId])->all();
        if($query){
            foreach($query as $object){
                $object->withdrawn = AppConstant::NUMERIC_ZERO ;
                $object->save();
            }
        }
    }

    public static function setWithdrawn($assessmentId,$key){
    $questionData = Questions::findAll(['assessmentid' => $assessmentId]);
        if($questionData){
            foreach($questionData as $data){
                $data->withdrawn = $key;
                $data->save();
            }
        }
    }

    public static function updateWithPoints($withdraw,$points,$qidlist){
        $query = Questions::getByIdList($qidlist);
        if ($query){
            foreach($query as $object){
                $object->withdrawn = $withdraw;
                if($points){
                    $object->points = $points;
                }
                $object->save();
            }
        }
    }

    public static function getByIdList($ids){
        return Questions::find()->where(['IN', 'id', $ids])->all();
    }

    public static function getQuestionData($id){
        $query = \Yii::$app->db->createCommand("SELECT imas_questions.questionsetid,imas_questionset.description,imas_questionset.userights,imas_questionset.ownerid,imas_questionset.qtype,imas_questions.points,imas_questions.withdrawn,imas_questionset.extref,imas_users.groupid,imas_questions.showhints,imas_questionset.solution,imas_questionset.solutionopts FROM imas_questions,imas_questionset,imas_users WHERE imas_questions.id='$id AND imas_questionset.id=imas_questions.questionsetid AND imas_questionset.ownerid=imas_users.id ")->queryAll();
        return $query;
   }

    public static function getByQuestionSetId($allusedqids){
        $query = \Yii::$app->db->createCommand("SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allusedqids) GROUP BY questionsetid")->queryAll();
        return $query;
    }
    public static function getByAssessmentIdJoin($aidq){
        $query = "SELECT imas_questions.id,imas_questionset.id,imas_questionset.description,imas_questionset.qtype,imas_questionset.ownerid,imas_questionset.userights,imas_questionset.extref,imas_users.groupid FROM imas_questionset,imas_questions,imas_users";
        $query .= " WHERE imas_questionset.id=imas_questions.questionsetid AND imas_questionset.ownerid=imas_users.id AND imas_questions.assessmentid='$aidq'";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getQuestionCount($id){
        return $data = \Yii::$app->db->createCommand("SELECT COUNT(id) FROM imas_questions WHERE questionsetid='{$id}'")->queryAll();
}
    public static function updateQuestionSetId($aidarr){
        $query = "UPDATE imas_questions AS iq JOIN imas_questionset AS iqs ON iq.questionsetid=iqs.id ";
        if (!is_array($aidarr)) {
            $query .= "JOIN imas_assessments AS ia ON iq.assessmentid=ia.id ";
        }
        $query .= "SET iq.questionsetid=iqs.replaceby WHERE iqs.replaceby>0 ";
        if (is_array($aidarr)) {
            $query .= " AND iq.assessmentid IN (".implode(',',$aidarr).")";
        } else {
            $query .= " AND ia.courseid='$aidarr'";
        }
        \Yii::$app->db->createCommand($query)->query();
    }

    public static function FindAssessmentAndWithdrawn($aidarr){
        $query = "SELECT iq.assessmentid,iq.id,iq.withdrawn FROM imas_questions AS iq ";
        if (!is_array($aidarr)) {
            $query .= "JOIN imas_assessments AS ia ON iq.assessmentid=ia.id ";
        }
        $query .= "WHERE iq.withdrawn>0";

        if (is_array($aidarr)) {
            $query .= " AND iq.assessmentid IN (".implode(',',$aidarr).")";
        } else {
            $query .= " AND ia.courseid='$aidarr'";
        }
        $data = \Yii::$app->db->createCommand($query)->queryAll();
    }
}