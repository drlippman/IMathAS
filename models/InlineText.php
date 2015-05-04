<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 2/5/15
 * Time: 4:17 PM
 */

namespace app\models;


use app\models\_base\BaseImasInlinetext;

class InlineText extends BaseImasInlinetext
{
    public static function getByCourseId($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }
} 