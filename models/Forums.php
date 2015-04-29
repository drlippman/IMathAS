<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 29/4/15
 * Time: 12:30 PM
 */

namespace app\models;


use app\models\_base\BaseImasForums;

class Forums extends BaseImasForums {

    public static function getByCourseId($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }

} 