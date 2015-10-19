<?php

namespace app\models;

use yii\db\Query;
use app\models\_base\BaseImasFirstscores;

class FirstScores extends BaseImasFirstscores
{

    public static function getDataForQuestionUsage($lastFirstUpdate)
    {
        return self::find()->select(['qsetid','score','timespent'])->where(['>','timespent','0'])->andWhere(['<','timespent','1200'])
            ->andWhere(['>','id',$lastFirstUpdate])->orderBy('qsetid')->all();
    }

    public static function getMaxId()
    {
        return FirstScores::find()->max('id');
    }

} 