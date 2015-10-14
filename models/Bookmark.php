<?php
namespace app\models;
use app\models\_base\BaseImasBookmarks;
use Yii;

class Bookmark extends BaseImasBookmarks
{
    public static function updateValue($value)
    {
     return Bookmark::find()->where(['value' => $value]);
    }

    public static function getValue($userId, $courseId, $tr)
    {
        return Bookmark::find()->select('value')->where(['userid' => $userId, 'courseid' => $courseId, 'name' => $tr])->one();
    }
}