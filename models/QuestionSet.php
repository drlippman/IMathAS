<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 11/5/15
 * Time: 1:10 PM
 */

namespace app\models;


use app\models\_base\BaseImasQuestionset;

class QuestionSet extends BaseImasQuestionset
{
    public static function getByQuesSetId($id)
    {
        return static::findAll(['id' => $id]);
    }
    public static function getById($id)
    {
        $query = "SELECT qtext FROM imas_questionset WHERE id= 1";
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand($query);
        $qdata = $command->queryAll();
        return $qdata;

    }

} 