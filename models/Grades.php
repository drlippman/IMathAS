<?php

namespace app\models;

use app\components\AppConstant;
use Yii;

use app\components\AppUtility;
use app\models\_base\BaseImasGrades;
use yii\db\Query;

class Grades extends BaseImasGrades
{
    public function createGradesByUserId($grade)
    {
            $this->gradetypeid = $grade['gradetypeid'];
            $this->userid = $grade['userid'];
            $this->score = $grade['score'];
            $this->feedback = $grade['feedback'];
            $this->gradetype = $grade['gradetype'];
            $this->save();
            return $this;
    }

    public static function GetOtherGrades($gradetypeselects, $limuser){
            $sel = implode(' OR ',$gradetypeselects);
            $query = "SELECT * FROM imas_grades WHERE ($sel)";
            if ($limuser>0)
            {
                $query .= " AND userid= :userId ";
            }
        $data = \Yii::$app->db->createCommand($query);
        $data->bindValue('userId',$limuser);
        return $data->queryAll();
    }

    public static function outcomeGrades($sel,$limuser)
    {
        $query = new Query();
        $query->select(['*'])
            ->from('imas_grades')
            ->where($sel);
        if ($limuser > 0)
        {
            $query->andWhere('userid=:limuser',[':limuser' => $limuser]);
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

    public static function updateGradeToStudent($score,$feedback,$userid,$gbItemsId)
    {
        $grade = Grades::find()->where(['userid' => $userid])->andWhere(['gradetypeid'=> $gbItemsId])->andWhere(['gradetype' => 'offline'])->one();
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
        $query = new Query();
        $query	->select(['userid','score','feedback'])
            ->from('imas_grades')
            ->where(['gradetype' => 'offline'])
            ->andWhere('gradetypeid = :gbitemId');
        $grades != 'all' ? $query->andWhere('userid=:grades') : $query->andWhere(':grades = :grades');
        $command = $query->createCommand()
            ->bindValues(['gbitemId' => $gbitemId, 'grades' => $grades]);
        $data = $command->queryAll();
        return $data;
    }

    public static function updateScoreToStudent($score,$feedback,$studentId,$gbitem)
    {
        $grade = Grades::find()->where(['userid' => $studentId])
            ->andWhere(['gradetype' => 'exttool'])->andWhere(['gradetypeid' => $gbitem])->one();
        $grade->score = $score;
        $grade->feedback = $feedback;
        $grade->save();
    }

    public static function getUserId($gbItem,$kl)
    {
       return Grades::find()->where(['gradetype' => 'offline'])->andWhere(['gradetypeid' => $gbItem])->andWhere(['IN','userid',$kl])->all();
    }

    public static function deleteByUserId($userId)
    {
        $grades = Grades::find()->where(['userid' => $userId])->all();
        foreach($grades as $grade)
        {
            $grade->delete();
        }
    }

    public static function deleteById($id)
    {
        $grades = Grades::find()->where(['gradetypeid' => $id, 'gradetype' => 'exttool'])->all();
        foreach($grades as $grade)
        {
            $grade->delete();
        }
    }

    public static function deleteByGradeId($id)
    {
        $grades = Grades::find()->where(['gradetype' => 'offline', 'gradetypeid' => $id])->all();
        foreach($grades as $grade)
        {
            $grade->delete();
        }
    }

    public static function getExternalToolUserId($gbItem,$users)
    {
        return Grades::find()->where(['gradetype' => 'exttool'])
            ->andWhere(['gradetypeid' => $gbItem])->andWhere(['IN','userid',$users])->all();
    }

    public static function getExternalToolData($gbitemId,$grades)
    {
        $query = new Query();
        $query	->select(['userid','score','feedback'])
            ->from('imas_grades')
            ->where(['gradetype' => 'exttool'])
            ->andWhere(['gradetypeid' => $gbitemId]);
        if($grades != 'all'){
            $query->andWhere(['userid' => $grades]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getForumData($forumId,$userId,$key)
    {
        return Grades::find()->where(['gradetype' => 'forum'])->andWhere(['gradetypeid' => $forumId])->andWhere(['userid' => $userId])->andWhere(['IN','refid',$key])->all();
    }

    public static function updateForumData($score,$feedback,$forumId,$userId,$key)
    {
        $grades =  Grades::find()->where(['gradetype' => 'forum'])->andWhere(['gradetypeid' => $forumId])->andWhere(['userid' => $userId])->andWhere(['refid' => $key])->all();
        if($grades)
        {
            foreach($grades as $grade)
            {
                $grade->score = $score;
                $grade->feedback = $feedback;
                $grade->save();
            }
        }
    }

    public static function deleteForumData($forumId,$userId,$key)
    {
        $grades =  Grades::find()->where(['gradetype' => 'forum'])->andWhere(['gradetypeid' => $forumId])->andWhere(['userid' => $userId])->andWhere(['refid' => $key])->all();
        if($grades)
        {
            foreach($grades as $grade)
            {
                $grade->delete();
            }
        }
    }

    public function insertForumDataInToGrade($grade)
    {
        $this->gradetypeid = $grade['gradetypeid'];
        $this->userid = $grade['userid'];
        $this->refid = $grade['refid'];
        $this->score = $grade['score'];
        $this->feedback = $grade['feedback'];
        $this->gradetype = $grade['gradetype'];
        $this->save();
    }

    public static function getForumDataUsingUserId($userId,$forumId)
    {
        return Grades::find()->where(['gradetype' => 'forum'])->andWhere(['gradetypeid' => $forumId])->andWhere(['userid' => $userId])->all();
    }

    public static function getByGradeTypeIdAndGradeType($gradeType,$gradeTypeId)
    {
        return Grades::find()->where(['gradetype' => $gradeType])->andWhere(['gradetypeid' => $gradeTypeId])->all();
    }

    public static function updateById($score,$feedback,$id)
    {
        $grades =  Grades::find()->where(['id' => $id])->all();
        if($grades)
        {
            foreach($grades as $grade)
            {
                $grade->score = $score;
                $grade->feedback = $feedback;
                $grade->save();
            }
        }
    }

    public static function deleteByOnlyId($id)
    {
        $grade = Grades::find()->where(['id' => $id])->one();
        if($grade)
        {
            $grade->delete();
        }
    }

    public static function getGradesData($id)
    {
        $query = "SELECT ifp.subject,ig.score FROM imas_forum_posts AS ifp LEFT JOIN imas_grades AS ig ON ";
        $query .= "ig.gradetype='forum' AND ifp.id=ig.refid WHERE ifp.id=:id";
        return Yii::$app->db->createCommand($query)->bindValue(':id',$id)->queryOne();
    }

    public static function getId($Id)
    {
        return Grades::find()->select('id')->where(['gradetype' => 'forum', 'refid' => $Id])->all();
    }

    public static function updateScore($id,$score)
    {
        $grades = Grades::find()->where(['id' => $id])->all();
        if($grades)
        {
            foreach($grades as $grade)
            {
                $grade->score = $score;
                $grade->save();
            }
        }
    }

    public function insertGrades($grade)
    {
            $this->gradetypeid = $grade['gradetypeid'];
            $this->userid = $grade['userid'];
            $this->refid = $grade['refid'];
            $this->score = $grade['score'];
            $this->gradetype = $grade['gradetype'];
            $this->save();
            return $this;
    }
}