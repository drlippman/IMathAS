<?php

namespace app\models;


use app\models\_base\BaseImasAssessmentSessions;

class AssessmentSession extends BaseImasAssessmentSessions
{
    public static function getById($id)
    {
        return static::findOne(['assessmentid' => $id]);
    }
} 