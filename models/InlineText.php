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
        return InlineText::findAll(['courseid' => $courseId]);
    }

    public static function getById($id)
    {
        return InlineText::findOne(['id' => $id]);
    }
} 