<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 26/8/15
 * Time: 8:27 PM
 */
namespace app\models;
use app\components\AppUtility;
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
        return Yii::$app->db->createCommand("SELECT value FROM imas_bookmarks WHERE userid= '$userId' AND courseid= '$courseId' AND name= '$tr'")->queryOne();
    }
}