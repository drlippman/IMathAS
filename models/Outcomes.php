<?php


namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasOutcomes;

class Outcomes extends BaseImasOutcomes {

    public  function SaveOutcomes($courseId,$outcome)
    {

            $this->name =$outcome;
            $this->courseid = $courseId;
            $this->save();

    }

    public static function getByCourseId($courseId){
        return Outcomes::find()->select('id,name')->where(['courseid' => $courseId])->all();
    }

} 