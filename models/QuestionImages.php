<?php

namespace app\models;


use app\models\_base\BaseImasQimages;
use yii\db\Query;

class QuestionImages extends BaseImasQimages
{
    public static function getByQuestionSetId($questionId)
    {
        return QuestionImages::find()->where(['qsetid' => $questionId])->all();
    }

}