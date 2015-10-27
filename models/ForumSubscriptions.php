<?php

namespace app\models;

use Yii;
use app\models\_base\BaseImasForumSubscriptions;

class ForumSubscriptions extends BaseImasForumSubscriptions
{
    public function AddNewEntry($forumId,$userId)
    {
        $this->forumid = $forumId;
        $this->userid = $userId;
        $this->save();
    }

    public static function deleteSubscriptionsEntry($itemId,$userId)
    {
        $entry = ForumSubscriptions::findOne(['forumid' => $itemId,'userid' => $userId ]);
        if($entry){
            $entry->delete();
        }
    }

    public static function getByForumIdUserId($forumId,$userId)
    {

        $subscriptionsData = ForumSubscriptions::findAll(['forumid'=> $forumId, 'userid'=>$userId]);
        if ($subscriptionsData){
            return $subscriptionsData;
        }
    }

    public static function getByManyForumIdsANdUserId($checkedlist,$currentUserId)
    {
        return self::find()->select('forumid')->where('IN','forumid',$checkedlist)->andWhere(['userid' => $currentUserId])->all();
    }
}