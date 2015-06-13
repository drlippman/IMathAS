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
    public static function getByAssessmentId($assessmentId)
    {
        return static::findOne(['assessmentid' => $assessmentId]);
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

    public static function modifyExistingException($userId, $assessmentId, $startdate, $enddate, $waivereqscore)
    {
        $exception = Exceptions::getByAssessmentIdAndUserId($userId,$assessmentId);
        $exception->startdate = $startdate;
        $exception->enddate = $enddate;
        $exception->waivereqscore = $waivereqscore;
        $exception->save();
    }

    public static function getById($id)
    {
        return static::findOne(['id' => $id]);
    }

    public static function deleteExceptionById($id)
    {
        $exception = Exceptions::getById($id);
        if($exception){
        $exception->delete();
        }
    }

} 