<?php


namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasOutcomes;
use yii\db\Query;

class Outcomes extends BaseImasOutcomes {

    public  function SaveOutcomes($courseId,$outcome)
    {

            $this->name =$outcome;
            $this->courseid = $courseId;
            $this->save();
            return $this->id;

    }
    public static function getByCourseId($courseId)
    {

        $query = new Query();
        $query -> select(['id','name'])
               ->from('imas_outcomes')
                ->where(['courseid' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getByCourse($courseId){
        return Outcomes::find()->select('id,name')->where(['courseid' => $courseId])->all();
    }

} 