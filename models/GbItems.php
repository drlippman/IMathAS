<?php

/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 5/6/15
 * Time: 7:13 PM
 */

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
        $name = $params['name'];
        $this->name = isset($name) ? $name : null;
        $this->points = isset($params['points']) ? $params['points'] : 0;
        $showdate = AppConstant::NUMERIC_ZERO;
        if($params['sdate-type'] == AppConstant::NUMERIC_ONE)
        {
            $showdate = AssessmentUtility::parsedatetime($params['sdate'],$params['stime']);
        }
        $this->showdate = $showdate;
        $this->gbcategory = $params['gradebook-category'];
        $this ->rubric = $params['rubric'];
        $this->cntingb = $params['cntingb'];
        $this->tutoredit = $params['tutoredit'];
        $this->outcomes = isset($params['outcomes']) ? $params['outcomes']:null ;
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
}