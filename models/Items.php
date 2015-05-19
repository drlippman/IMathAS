<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 6/5/15
 * Time: 12:34 PM
 */

namespace app\models;


use app\models\_base\BaseImasItems;

class Items extends BaseImasItems
{
    public static function getByCourseId($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }

    public static function getById($id)
    {
        return static::findOne(['id' => $id]);
    }
}