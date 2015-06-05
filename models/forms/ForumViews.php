<?php


namespace app\models\forms;
use app\components\AppUtility;
use yii\base\Model;
use Yii;
class ForumViews extends Model
{
    public static  function forumViews($threadid){

        $thread = Yii::$app->db->createCommand("SELECT * from  imas_forum_views WHERE threadid=$threadid")->queryAll();
        return $thread;

    }


}