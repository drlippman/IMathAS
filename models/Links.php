<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 30/4/15
 * Time: 3:59 PM
 */

namespace app\models;


use app\models\_base\BaseImasLinkedtext;

class Links extends BaseImasLinkedtext
{
    public static function getByCourseId($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }
} 