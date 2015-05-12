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
} 