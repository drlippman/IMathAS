<?php

namespace app\models;
use app\components\AppConstant;
use Yii;

use app\components\AppUtility;
use app\models\_base\BaseImasRubrics;

class Rubrics extends BaseImasRubrics
{
    public static function getByUserId($usrId)
    {
        $rubricsData = Rubrics::findAll(['ownerid' => $usrId]);
        return $rubricsData;
    }

    public function createNewEntry($params,$currentUserId,$rubricTextDataArray)
    {
//        AppUtility::dump($rubricTextDataArray);
        $this->ownerid = $currentUserId;
        $this->groupid = ($params['AddRubricForm']['ShareWithGroup']-AppConstant::NUMERIC_ONE);
//        $this->name = isset() ? $params['AddRubricForm']['Name'] : null;
        if($params['AddRubricForm']['Name']){
            $this->name =  $params['AddRubricForm']['Name'];
        }else{
            $this->name ='null';
        }
        $this->rubrictype = $params['AddRubricForm']['RubricType'];
        $this->rubric = $rubricTextDataArray;
//        AppUtility::dump($this);
        $this->save();
    }
    public static function getByUserIdAndRubricId($currentUserId,$rubricId)
    {
        $rubricsData = Rubrics::findone(['ownerid' => $currentUserId,'id' => $rubricId]);
        return $rubricsData;
    }
}

