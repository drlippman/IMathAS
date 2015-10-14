<?php

namespace app\models;
use app\components\AppConstant;
use app\components\AssessmentUtility;
use Yii;

use app\components\AppUtility;
use app\models\_base\BaseImasGbitems;
use yii\db\Query;

class GbItems extends BaseImasGbitems
{
    public function createGbItemsByCourseId($courseId,$params)
    {
        $this->courseid = $courseId;
        $this->name = $params['name'] ? $params['name'] : ' ';
        $this->points = $params['points'] ? $params['points'] : AppConstant::NUMERIC_ZERO;
        $this->showdate = $params['showdate'];
        $this->gbcategory = $params['gradebook-category'];
        $this ->rubric = $params['rubric'];
        $this->cntingb = $params['cntingb'];
        $this->tutoredit = $params['tutoredit'];
        $this->outcomes = isset($params['outcomes']) ? $params['outcomes']:' ' ;
        $this->save();
        return $this->id;
    }
    public static function findAllOfflineGradeItem($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now){

        $query = new Query();
        $query->select(['*'])
            ->from('imas_gbitems')
            ->where(['courseid'=>$courseId]);
        if (!$canviewall) {
            $query->andWhere(['<','showdate', $now]);
        }
        if (!$canviewall) {
            $query->andWhere(['>','cntingb', 0]);
        }
        if ($istutor) {
            $query->andWhere(['<','tutoredit', 2]);
        }
        if ($catfilter>-1) {
            $query->andWhere(['gbcategory' => $catfilter]);
        }
        $query->orderBy('showdate');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function getbyCourseId($courseId)
    {
        $gradeNames = GbItems::find()->where(['courseid' => $courseId])->all();
        return $gradeNames;
    }
    public static function deleteById($gradeId)
    {
        $grade = GbItems::find()->where(['id' => $gradeId])->one();
        if($grade) {
            $grade->delete();
        }
    }
    public static function updateGrade($gradeId,$AssignValue,$temp)
    {
        $grade = GbItems::find()->where(['id' => $gradeId])->one();
        if($grade){
           if($temp == AppConstant::NUMERIC_ONE){
               $grade->showdate = $AssignValue;
           }else if($temp == AppConstant::NUMERIC_TWO){
               $grade->cntingb = $AssignValue;
           }else if($temp == AppConstant::NUMERIC_THREE){
               $grade->tutoredit = $AssignValue;
           }elseif($temp == AppConstant::NUMERIC_FOUR){
               $grade->gbcategory = $AssignValue;
           }
            $grade->save();
            }
    }

    public static  function findOfflineGradeItemForOutcomes($courseId,$istutor,$catfilter, $now)
    {
        $query = new Query();
        $query->select(['*'])
            ->from('imas_gbitems')
            ->where(['courseid'=>$courseId])
            ->andWhere(['<>','outcomes',''])
            ->andWhere(['<','showdate',$now])
            ->andWhere(['>','cntingb',0])
            ->andWhere(['<','cntingb',3]);
        if ($istutor) {
            $query->andWhere(['<','tutoredit', 2]);
        }
        if ($catfilter>-1) {
            $query->andWhere(['gbcategory' => $catfilter]);
        }
        $query->orderBy('showdate');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function updateGbCat($catList){

        foreach($catList as $category){
            $query = GbItems::findOne(['gbcategory' => $category]);
            if($query){
                $query->gbcategory = AppConstant::NUMERIC_ZERO;
                $query->save();
            }
        }
    }

    public static function setRubric($id, $data){
        $rubricData = GbItems::findOne(['id' => $id]);
        if ($rubricData){
            $rubricData->rubric = $data;
            $rubricData->save();
        }
    }
    public static function deleteByCourseId($courseId)
    {
        $query = GbItems::find()->where(['courseid' => $courseId])->all();
        if($query){
            foreach($query as $object){
                $object->delete();
            }
        }
    }
    public static function getById($gbItem){
     return GbItems::find()->where(['id' => $gbItem])->one();
    }

    public static function getGbItemsForOutcomeMap($courseId)
    {
        $query = new Query();
        $query->select(['id','name','gbcategory','outcomes'])
               ->from('imas_gbitems ')
                ->where(['courseid' => $courseId])
                ->andWhere(['<>','outcomes','']);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;

    }
    public static function updateGbItemsByCourseId($gbItemId , $params)
    {
        $GbItems = GbItems::find()->where(['id' => $gbItemId])->one();
        $GbItems->name = $params['name'] ? $params['name'] : null;
        $GbItems->points = $params['points'] ? $params['points'] : AppConstant::NUMERIC_ZERO;
        $GbItems->showdate = $params['showdate'];
        $GbItems->gbcategory = $params['gradebook-category'];
        $GbItems->rubric = $params['rubric'];
        $GbItems->cntingb = $params['cntingb'];
        $GbItems->tutoredit = $params['tutoredit'];
        $GbItems->outcomes = isset($params['outcomes']) ? $params['outcomes']:null ;
        $GbItems->save();
    }

    public static function getDataForCopyCourse($ctc)
    {
        $query = new Query();
        $query->select(['name','points','showdate','gbcategory','cntingb','tutoredit','rubric'])
            ->from('imas_gbitems ')
            ->where(['courseid' => $ctc]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function insertData($courseId,$params,$rubric)
    {
        $this->courseid = $courseId;
        $this->name = $params['name'];
        $this->points = $params['points'];
        $this->showdate = $params['showdate'];
        $this->gbcategory = $params['gbcategory'];
        $this ->rubric = $rubric;
        $this->cntingb = $params['cntingb'];
        $this->tutoredit = $params['tutoredit'];
        $this->save();
        return $this->id;
    }

    public static function getByCourseIdAll($courseId)
    {
        return Forums::find()->select('id')->where(['courseid' => $courseId])->all();
    }

    public static function deleteByCId($courseId)
    {
        $courseData = GbItems::findOne(['courseid',$courseId]);
        if($courseData){
            $courseData->delete();
        }
    }
}