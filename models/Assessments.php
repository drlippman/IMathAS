<?php
namespace app\models;


use app\models\_base\BaseImasAssessments;

class Assessments extends BaseImasAssessments
{
    public static function getById($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }

    public static function getByAssessment($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }

    public static function getByAssessmentId($id)
    {
        return static::findOne(['id' => $id]);
    }
} 