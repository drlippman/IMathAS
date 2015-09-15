<?php
namespace app\models;

use app\models\_base\BaseImasDrillassess;

class Drillassess extends BaseImasDrillassess
{
    public static function getByCourseId($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }
}