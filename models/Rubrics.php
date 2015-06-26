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


}

