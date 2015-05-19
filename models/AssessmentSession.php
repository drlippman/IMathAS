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

    public static function createSessionForAssessment($params)
    {
        $params['starttime'] = '0';
        $assessmentSession = new AssessmentSession();
        $assessmentSession->attributes = $params;
        $assessmentSession->save();
    }
    public static function getById($id)
    {
        return static::findOne(['assessmentid' => $id]);
    }

    public static function getByUserId($uid)
    {
        return static::findOne(['userid' => $uid]);
    }
} 