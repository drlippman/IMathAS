<?php

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasQuestions;
use yii\db\Query;
use Yii;

class Questions extends BaseImasQuestions
{
    public static function getByAssessmentId($aid)
    {
        return static::findAll(['assessmentid' => $aid]);
    }

    public static function getById($id)
    {
        return static::findOne(['id' => $id]);
    }

    public static function findQuestionForOuctome($dataId)
    {
        $query = Questions::find()->select('points,id,category')->where(['assessmentid' => $dataId])->all();
        return $query;
    }

    public static function setQuestionByAssessmentId($assessmentId)
    {
        $assessment = Questions::findOne(['assessmentid' => $assessmentId]);
        if ($assessment) {
            $assessment->points = 9999;
            $assessment->attempts = 9999;
            $assessment->penalty = '9999';
            $assessment->regen = 0;
            $assessment->showans = 0;
            $assessment->save();
        }
    }

    public static function deleteByAssessmentId($assessmentId)
    {
        $questionData = Questions::findAll(['assessmentid' => $assessmentId]);
        if ($questionData) {
            foreach ($questionData as $singleQuestion) {
                $singleQuestion->delete();
            }
        }
    }

    public static function setRubric($id, $data)
    {
        $rubricData = Questions::findOne(['id' => $id]);
        if ($rubricData) {
            $rubricData->rubric = $data;
            $rubricData->save();
        }
    }

    public static function getByItemOrder($itemorder)
    {
        $questionDataArray = array();
        foreach ($itemorder as $item) {
            $questionData = Questions::findOne(['id' => $item]);
            array_push($questionDataArray, $questionData);
        }
        return $questionDataArray;
    }

    public function addQuestions($params)
    {
        $data = AppUtility::removeEmptyAttributes($params);
        $this->attributes = $data;
        $this->save();
        return $this->id;
    }

    public static function findQuestionForGradebook($assessmentId)
    {
        $query = Questions::find()->select('points,id')->where(['assessmentid' => $assessmentId])->all();
        return $query;
    }

    public static function updateWithdrawn($assessmentId)
    {
        $query = Questions::find()->where(['IN', 'assessmentid', $assessmentId])->all();
        if ($query) {
            foreach ($query as $object) {
                $object->withdrawn = AppConstant::NUMERIC_ZERO;
                $object->save();
            }
        }
    }

    public static function setWithdrawn($assessmentId, $key)
    {
        $questionData = Questions::findAll(['assessmentid' => $assessmentId]);
        if ($questionData) {
            foreach ($questionData as $data) {
                $data->withdrawn = $key;
                $data->save();
            }
        }
    }

    public static function updateWithPoints($withdraw, $points, $qidlist)
    {
        $query = Questions::getByIdList($qidlist);
        if ($query) {
            foreach ($query as $object) {
                $object->withdrawn = $withdraw;
                if ($points) {
                    $object->points = $points;
                }
                $object->save();
            }
        }
    }

    public static function getByIdList($ids)
    {
        return Questions::find()->where(['IN', 'id', $ids])->all();
    }

    public static function getQuestionData($id)
    {
        $query = "SELECT imas_questions.questionsetid,imas_questionset.description,imas_questionset.userights,imas_questionset.ownerid,imas_questionset.qtype,";
        $query .= "imas_questions.points,imas_questions.withdrawn,imas_questionset.extref,imas_users.groupid,imas_questions.showhints,imas_questionset.solution,";
        $query .= "imas_questionset.solutionopts FROM imas_questions,imas_questionset,imas_users WHERE imas_questions.id= :id AND imas_questionset.id=imas_questions.questionsetid AND imas_questionset.ownerid=imas_users.id ";
        $data = \Yii::$app->db->createCommand($query)->bindValue(':id',$id)->queryOne();
        return $data;
    }

    public static function getByQuestionSetId($allusedqids)
    {
        $query =  new Query();
        $query->select(['questionsetid','COUNT(id)'])
            ->from('imas_questions')->where(['IN', 'questionsetid', $allusedqids])->groupBy('questionsetid');
       return $query->createCommand()->queryAll();
    }

    public static function getByAssessmentIdJoin($aidq)
    {

        $query = "SELECT imas_questions.id,imas_questionset.id AS qid,imas_questionset.description,imas_questionset.qtype,imas_questionset.ownerid,imas_questionset.userights,imas_questionset.extref,imas_users.groupid
        FROM imas_questionset,imas_questions,imas_users";
        $query .= " WHERE imas_questionset.id=imas_questions.questionsetid AND imas_questionset.ownerid=imas_users.id AND imas_questions.assessmentid='$aidq'";
        $data=\Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getQuestionCount($id)
    {
        return $data = \Yii::$app->db->createCommand("SELECT COUNT(id) FROM imas_questions WHERE questionsetid= ':id' ")->bindValue(':id',$id)->queryAll();
    }

    public static function getByQuestionsIdAndAssessmentId($assessmentId)
    {
        $query = new Query();
        $query->select(['imas_questions.id', 'imas_questions.points', 'imas_questions.withdrawn', 'imas_questionset.qtype', 'imas_questionset.control', 'imas_questions.rubric', 'imas_questions.showhints', 'imas_questionset.extref', 'imas_questionset.ownerid'])
            ->from('imas_questionset')
            ->join('INNER JOIN',
                'imas_questions',
                'imas_questions.questionsetid=imas_questionset.id'
            )
            ->where('imas_questions.assessmentid = :assessmentId');
        $command = $query->createCommand();
        $data = $command->bindValue(':assessmentId',$assessmentId)->queryAll();
        return $data;
    }

    public static function getByLibrariesIdAndcategory($questionId)
    {
        $query = new Query();
        $query->select(['imas_questions.questionsetid', 'imas_questions.category', 'imas_libraries.name'])
            ->from('imas_questions')
            ->join(
                'LEFT JOIN',
                'imas_libraries',
                'imas_questions.category=imas_libraries.id'
            )
            ->where('imas_questions.id = :questionId');
        $command = $query->createCommand();
        $data = $command->bindValue(':questionId',$questionId)->queryOne();
        return $data;
    }

    public static function updateQuestionSetId($aidarr)
    {
        $placeholders= "";
        if($aidarr)
        {
            foreach($aidarr as $i => $singleAssignment){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }
        $query = "UPDATE imas_questions AS iq JOIN imas_questionset AS iqs ON iq.questionsetid=iqs.id ";
        if (!is_array($aidarr)) {
            $query .= "JOIN imas_assessments AS ia ON iq.assessmentid=ia.id ";
        }
        $query .= "SET iq.questionsetid=iqs.replaceby WHERE iqs.replaceby>0 ";
        if (is_array($aidarr)) {
            $query .= " AND iq.assessmentid IN ($placeholders)";
        } else {
            $query .= " AND ia.courseid=:aidarr";
        }
        $command = \Yii::$app->db->createCommand($query);

        if (is_array($aidarr)) {
            foreach($aidarr as $i => $parent){
                $command->bindValue(":".$i, $parent);
            }
        } else {
            $command->bindValue(':aidarr', $aidarr);
        }
        $command->query();
    }

    public static function FindAssessmentAndWithdrawn($aidarr)
    {
        $placeholders= "";
        if($aidarr)
        {
            foreach($aidarr as $i => $singleAssignment){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }
        $query = "SELECT iq.assessmentid,iq.id,iq.withdrawn FROM imas_questions AS iq ";
        if (!is_array($aidarr)) {
            $query .= "JOIN imas_assessments AS ia ON iq.assessmentid=ia.id ";
        }
        $query .= "WHERE iq.withdrawn>0";

        if (is_array($aidarr)) {
            $query .= " AND iq.assessmentid IN ($placeholders)";
        } else {
            $query .= " AND ia.courseid=:aidarr";
        }
        $command = \Yii::$app->db->createCommand($query);
        if (is_array($aidarr)) {
            foreach($aidarr as $i => $parent){
                $command->bindValue(":".$i, $parent);
            }
        } else {
           $command->bindValue(':aidarr', $aidarr);
        }
        $data =  $command->queryAll();
        return $data;
    }
    public static function setQuestionSetId($qsetid,$replaceby)
    {
        $query = 'UPDATE imas_questions LEFT JOIN imas_assessment_sessions ON imas_questions.assessmentid = imas_assessment_sessions.assessmentid ';
        $query .= "SET imas_questions.questionsetid=':replaceby' WHERE imas_assessment_sessions.id IS NULL AND imas_questions.questionsetid=':qsetid'";

        \Yii::$app->db->createCommand($query)->bindValues([':replaceby' => $replaceby, ':qsetid' => $qsetid])->query();

    }
    public static function numberOfQuestionByIdAndCategory($assessmentid)
    {
        return self::find()-> select(['id'])->where(['assessmentid' =>  $assessmentid])->andWhere(['<>','category','0'])->all();
    }

    public static function setQuestionSetIdById($qSetId, $id){
        $data = Questions::getById($id);
        if($data){
            $data->questionsetid = $qSetId;
            $data->save();
        }
    }

    public static function getQidCount($userId,$qSetId){
        $query = "SELECT count('imas_questions.id') AS qidCount
                    FROM imas_questions,imas_assessments,imas_courses
                    WHERE imas_assessments.id=imas_questions.assessmentid ";
        $query .= "AND imas_assessments.courseid=imas_courses.id
                    AND imas_questions.questionsetid= :qSetId AND imas_courses.ownerid<> :userId";
        $queryResult = Yii::$app->db->createCommand($query)->bindValues(['qSetId' => $qSetId, ':userId' => $userId])->queryOne();
        return $queryResult;
    }

    public static function updateQuestionData($checkedlist)
    {
        $questions = Questions::find()->where('IN', 'assessmentid', $checkedlist)->all();
        if($questions)
        {
            foreach($questions as $question)
            {
                $question->points = 9999;
                $question->attempts = 9999;
                $question->penalty = 9999;
                $question->regen = 0;
                $question->showans = 0;
                $question->save();
            }
        }
    }

    public static function updateQuestionFields($params, $id){
        $questionData = Questions::getById(['id' => $id]);
        $data = AppUtility::removeEmptyAttributes($params);
        if($questionData){
            $questionData->attributes = $data;
            $questionData->save();
        }
    }

    public static function deleteById($ids){
        $data = Questions::getByIdList($ids);
        if($data){
            foreach($data as $singleData){
                $singleData->delete();
            }
        }
    }

    public static function retrieveQuestionData($qids){
        $placeholders= "";
        if($qids)
        {
            foreach($qids as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }

        $query = "SELECT imas_questions.id, imas_questionset.description, imas_questions.points, imas_questions.attempts, imas_questions.showhints, imas_questionset.extref ";
        $query .= "FROM imas_questions,imas_questionset WHERE imas_questionset.id=imas_questions.questionsetid AND ";
        $query .= "imas_questions.id IN ($placeholders)";
        $command = \Yii::$app->db->createCommand($query);
        foreach($qids as $i => $parent){
            $command->bindValue(":".$i, $parent);
        }
        $data =  $command->queryAll();
        return $data;
    }

    public static function getPointsAndQsetId($qid)
    {
        $query = Questions::find()->select('id,points,questionsetid')->where(['IN', 'id', $qid])->all();
        return $query;

    }
    public static function getDataByJoin($aid)
    {

        $query = new Query();
        $query->select('iq.id,iqs.description')
            ->from('imas_questions AS iq')
            ->join(
                'INNER JOIN',
                'imas_questionset as iqs',
                'iq.questionsetid=iqs.id'
            )
            ->where('iq.assessmentId =:aId');
        $command = $query->createCommand();
        $data = $command->bindValue(':aId',$aid)->queryAll();
        return $data;
    }

    public static function getDataForModTutorial($userId,$id)
    {
        $query = "SELECT count(imas_questions.id) FROM imas_questions,imas_assessments,imas_courses WHERE imas_assessments.id=imas_questions.assessmentid ";
        $query .= "AND imas_assessments.courseid=imas_courses.id AND imas_questions.questionsetid= :id' AND imas_courses.ownerid<> :userId";
        $data = \Yii::$app->db->createCommand($query)->bindValues([':id' => $id ,':userId' => $userId])->queryAll();
        return $data;
    }

    public static function getIdCatPoints($dataId)
    {
        $query = Questions::find()->select('id,category,points')->where(['IN','id',$dataId])->all();
        return $query;
    }

    public static function retrieveQuestionDataForgradebook($qids)
    {
        $query = new Query();
        $query->select(['imas_questionset.description','imas_questions.id','imas_questions.points','imas_questions.withdrawn'])
            ->from('imas_questions')
            ->join('INNER JOIN',
                'imas_questionset',
                'imas_questionset.id=imas_questions.questionsetid'
            )
            ->where(['IN','imas_questions.id',explode(',',$qids)]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function copyQuestions($params)
    {
        $this->assessmentid = intval($params['assessmentid']);
        $this->questionsetid = intval($params['questionsetid']);
        $this->points = intval($params['points']);
        $this->attempts = intval($params['attempts']);
        $this->penalty = strval($params['penalty']);
        $this->category = strval($params['category']);
        $this->regen = intval($params['regen']);
        $this->showans = intval($params['showans']);
        $this->showhints = intval($params['showhints']);
        $this->save();printf($this->getErrors());
        return $this->id;
    }

    public static function getQuestionsAndQuestionSetData($qid)
    {
        $query = new Query();
        $query->select(['imas_questions.points','imas_questionset.control','imas_questions.rubric','imas_questionset.qtype'])
            ->from('imas_questions')
            ->join(
                'INNER JOIN',
                'imas_questionset',
                'imas_questions.questionsetid = imas_questionset.id'
            )
            ->where('imas_questions.id = :qid');
        $command = $query->createCommand()
        ->bindValue('qid',$qid);
        $data = $command->queryOne();
        return $data;
    }

    public static function getByINId($qlist)
    {
        return self::find()->select('id,points,questionsetid')->where(['IN', 'id', $qlist])->all();
    }

    public static function getQuestionDataByRubric($assessmentId)
    {
      return Questions::find()->select('rubric')->where(['assessmentid' => $assessmentId])->andWhere(['>','rubric',0])->distinct()->asArray()->all();
    }
}
