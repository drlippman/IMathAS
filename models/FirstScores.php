<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 4/9/15
 * Time: 7:39 PM
 */

namespace app\models;

use yii\db\Query;
use app\models\_base\BaseImasFirstscores;

class FirstScores extends BaseImasFirstscores
{

    public static function getDataForQuestionUsage($lastFirstUpdate)
    {
        $query = new Query();
        $query ->select(['qsetid','score','timespent'])
            ->from('imas_firstscores')
            ->where(['>','timespent','0'])
            ->andwhere(['<','timespent','1200'])
            ->andwhere(['>','id',$lastFirstUpdate])
             ->orderBy('qsetid');
        $command = $query->createCommand();
        return $command->queryAll();
    }

    public static function getMaxId()
    {
        return FirstScores::find()->max('id');
    }

} 