<?php
namespace app\models;


use app\models\_base\BaseImasCalitems;

class CalItem extends BaseImasCalitems
{
    public static function getByCourseId($courseId)
    {
        return CalItem::findAll(['courseid' => $courseId]);
    }
} 