<?php


namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasForumSubscriptions;

class ForumSubscriptions extends BaseImasForumSubscriptions
{
    public function AddNewEntry($forumId,$userId)
    {
        $this->forumid = $forumId;
        $this->userid = $userId;
        $this->save();
    }
    public static function deleteSubscriptionsEntry($itemId)
    {
        $entry = ForumSubscriptions::findOne(['forumid' => $itemId]);
        if($entry){
            $entry->delete();
        }

    }
}