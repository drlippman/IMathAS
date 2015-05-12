<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 9/5/15
 * Time: 6:28 PM
 */

namespace app\models;


use app\models\_base\BaseImasAssessmentSessions;

class AssessmentSession extends BaseImasAssessmentSessions
{
    public static function getByAssessmentSessionId($id)
    {
        return static::findAll(['assessmentid' => $id]);
    }
} 