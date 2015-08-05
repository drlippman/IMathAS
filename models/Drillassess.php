<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 4/8/15
 * Time: 6:28 PM
 */
namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasDrillassess;

class Drillassess extends BaseImasDrillassess
{
    public static function getByCourseId($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }
}