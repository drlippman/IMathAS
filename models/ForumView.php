<?php

/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 5/6/15
 * Time: 7:13 PM
 */

namespace app\models;
use Yii;

use app\components\AppUtility;
use app\models\_base\BaseImasForumPosts;
use app\models\_base\BaseImasForumViews;

class ForumView extends BaseImasForumViews
{


    public static function getbythreadId($threadid)
    {

        $views = Yii::$app->db->createCommand("SELECT userid,lastview FROM imas_forum_views where threadid = $threadid ")->queryAll();
        return $views;
    }
    public static  function forumViews($threadid){


        $thread = Yii::$app->db->createCommand("SELECT * from  imas_forum_views WHERE threadid = $threadid")->queryAll();
        return $thread;

    }
    public static function updateFlagValue($row)
    {
        $query = Yii::$app->db->createCommand("UPDATE imas_forum_views SET tagged=(tagged^1) WHERE threadid='$row';'")->queryAll();

    }

    public static function uniqueCount($threadid)
    {

        $count = Yii::$app->db->createCommand("SELECT count(userid)'usercount' FROM imas_forum_views where threadid = $threadid")->queryAll();
        return $count;
    }

} 

