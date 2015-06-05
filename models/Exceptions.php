<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 15/5/15
 * Time: 5:58 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasExceptions;

class Exceptions extends BaseImasExceptions
{
    public static function getByAssessmentId($id)
    {
        return static::findOne(['assessmentid' => $id]);
    }

    public function create($param)
    {
        $this->attributes = $param;
        $this->save();
    }

    public static function getByAssessmentIdAndUserId($userId, $assessmentId)
    {
        return static::findOne(['assessmentid' => $assessmentId, 'userid' => $userId]);
    }

} 