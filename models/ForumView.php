<?php


namespace app\models;

use app\components\AppUtility;
use app\models\_base\BaseImasForumViews;
use Yii;

class ForumView extends BaseImasForumViews {

    public static function updateFlagValue($row)
    {
        $query = Yii::$app->db->createCommand("UPDATE imas_forum_views SET tagged=(tagged^1) WHERE threadid='$row';'")->queryAll();

    }
}