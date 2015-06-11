<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 11/5/15
 * Time: 12:50 PM
 */

namespace app\models;


use app\models\_base\BaseImasQuestions;

class Questions extends BaseImasQuestions
{
    public static function getByAssessmentId($id)
    {
        return static::findAll(['assessmentid' => $id]);
    }

    public static function getByExtraCredit($id)
    {
        return static::findAll(['extracredit' => $id]);
    }
} 