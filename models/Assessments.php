<?php
namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasAssessments;
use yii\db\Query;
use yii\debug\components\search\matchers\GreaterThan;

class Assessments extends BaseImasAssessments
{
    public static function getByCourseId($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }

    public static function getByAssessmentId($id)
    {
        return static::findOne(['id' => $id]);
    }

    public function create($values)
    {
        $this->attributes = $values;
        $this->save();
    }
    public static function findAllAssessmentForGradebook($courseId, $canviewall, $istutor, $isteacher, $catfilter, $time){
        $query = new Query();
        $query->select(['id', 'name','defpoints', 'deffeedback', 'timelimit', 'minscore', 'startdate', 'enddate', 'itemorder', 'gbcategory', 'cntingb', 'avail', 'groupsetid', 'allowlate'])
            ->from('imas_assessments')
            ->where(['courseid' => $courseId])
            ->andWhere(['>', 'avail', 0]);
        if(!$canviewall){
           $query->andWhere(['>', 'cntingb', 0]);
        }
        if($istutor){
            $query->andWhere(['<', 'tutoredit', 2]);
        }
        if(!$isteacher){
//            $query->andWhere(['<', 'startdate', $time]);
        }
        if($catfilter > -1){
            $query->andWhere(['gbcategory' => $catfilter]);
        }
        $query->orderBy('enddate','name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
} 