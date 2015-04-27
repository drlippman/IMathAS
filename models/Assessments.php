<?php
namespace app\models;


use app\models\_base\BaseImasAssessments;

class Assessments extends BaseImasAssessments
{
    public static function getById($courseId)
    {
        return static::findOne(['courseid' => $courseId]);
    }

    public static function getByAssessment($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }
} 